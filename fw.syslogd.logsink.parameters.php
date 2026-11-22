<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.autofs.inc");
include_once(dirname(__FILE__)."/ressources/class.syslog.tls.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["LogSynWazuh"])){backup_parameters_save();exit;}
if(isset($_POST["LogSynRTEnabled"])){backup_parameters_save();exit;}
if(isset($_POST["LogSynBackupEnable"])){backup_parameters_save();exit;}
if(isset($_GET["backup-params"])){backup_parameters_js();exit;}
if(isset($_GET["realtime-params"])){realtime_parameters_js();exit;}
if(isset($_GET["realtime-params-popup"])){realtime_parameters_popup();exit;}

if(isset($_GET["wazuh-params"])){wazuh_parameters_js();exit;}
if(isset($_GET["wazuh-params-popup"])){wazuh_parameters_popup();exit;}
if(isset($_GET["directory-params-popup"])){directory_params_popup();exit;}



if(isset($_GET["backup-params-popup"])){backup_parameters_popup();exit;}
if(isset($_POST["client_hostname"])){client_package_save();exit;}
if(isset($_POST["RsyslogInterface"])){save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status-start"])){status_start();exit;}
if(isset($_GET["client-package-popup"])){client_package_popup();exit;}
if(isset($_GET["client-package-download"])){client_package_download();exit;}
if(isset($_GET["logsink-status"])){service_status();exit;}
if(isset($_GET["server-list"])){servers_list();exit;}
if(isset($_GET["run-backup"])){run_backup_js();exit;}
if(isset($_GET["directory-params"])){directory_params_js();exit;}
if(isset($_POST["LogSinkWorkDir"])){directory_params_save();exit;}
if(isset($_GET["directory-params-popup"])){directory_params_popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
page();




function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{logs_sink} {parameters}",
        ico_logsink,"{log_sink_explain}","$page?tabs=yes","log-sink-parameters",
        "progress-syslod-restart",false,"table-loader-logsink-parameters");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SYSLOGD}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{service_parameters}"]="fw.syslogd.php?status=yes";
    $array["{logs_sink} {parameters}"]="$page?status-start=yes";
    echo $tpl->tabs_default($array);
}

function run_backup_js():bool{
    $tpl=new template_admin();
    admin_tracks("Launched Log Sink backup task manually");
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/logsink/backup?force=1"));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $error=(is_object($json) && isset($json->Error))?$json->Error:"articarest: ".json_last_error_msg();
        $tpl->js_error($error);
        return false;
    }
    $tpl->js_display_results("{run_backup_performed}",false,$tpl->javascript_parse_text("{run_backup}"));
    return true;
}
function directory_params_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{global_directory}","$page?directory-params-popup=yes");
    return true;
}


function backup_parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $AutoFSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled"));
    if($AutoFSEnabled==0){
        $tpl->js_error("{ERROR_NO_AUTOFS}");
        return true;
    }

    $tpl->js_dialog1("{backup}","$page?backup-params-popup=yes");
    return true;
}
function realtime_parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{realtime_events_squid}","$page?realtime-params-popup=yes");
    return true;
}
function wazuh_parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{APP_WAZHU}","$page?wazuh-params-popup=yes");
    return true;
}
function directory_params_popup():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $LogSyncMoveDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSyncMoveDir"));
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}

    if($LogSyncMoveDir<>null){
        $warn=$tpl->_ENGINE_parse_body("{warning_directory_move}");
        $warn=str_replace("%s",$LogSyncMoveDir,$warn);
        echo $tpl->div_warning($warn);
        return true;
    }

    $form[]=$tpl->field_browse_directory("LogSinkWorkDir","{directory}",$LogSinkWorkDir,null,true);
    $jsrestart="dialogInstance1.close();LoadAjax('logsink-status-params','$page?status=yes');";
    $html[]=$tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function directory_params_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}

    if($_POST["LogSinkWorkDir"]==$LogSinkWorkDir){
        echo $tpl->post_error("OLD:[$LogSinkWorkDir] == [{$_POST["LogSinkWorkDir"]}] !");
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LogSyncMoveDir",$_POST["LogSinkWorkDir"]);
    admin_tracks("Move Log Sink $LogSinkWorkDir working directory to {$_POST["LogSinkWorkDir"]}");
    return true;
}


function wazuh_parameters_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LogSynWazuh=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynWazuh"));
    $LogSynRTMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynRTMaxSize"));
    if($LogSynRTMaxSize==0){$LogSynRTMaxSize=80;}
    $form[] = $tpl->field_checkbox("LogSynWazuh", "{enable_this_option}", $LogSynWazuh, true);

    $form[]=$tpl->field_numeric("LogSynRTMaxSize","{srv_clamav.MaxFileSize} (MB)",$LogSynRTMaxSize);


    $jsrestart=$tpl->framework_buildjs("/syslog/reconfigure",
        "syslog.restart.progress",
        "syslog.restart.log",
        "progress-syslod-restart",
        "LoadAjax('logsink-status-params','$page?status=yes');");



    $jsrestart="dialogInstance1.close();LoadAjax('logsink-status-params','$page?status=yes');$jsrestart";
    $html[]=$tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function realtime_parameters_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LogSynRTEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynRTEnabled"));
    $LogSynRTMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynRTMaxSize"));
    if($LogSynRTMaxSize==0){$LogSynRTMaxSize=80;}
    $form[] = $tpl->field_checkbox("LogSynRTEnabled", "{enable_this_option}", $LogSynRTEnabled, true);

    $form[]=$tpl->field_numeric("LogSynRTMaxSize","{srv_clamav.MaxFileSize} (MB)",$LogSynRTMaxSize);


    $jsrestart=$tpl->framework_buildjs("/syslog/reconfigure",
        "syslog.restart.progress",
        "syslog.restart.log",
        "progress-syslod-restart",
        "LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');LoadAjax('logsink-status-params','$page?status=yes');");



    $jsrestart="dialogInstance1.close();LoadAjax('logsink-status-params','$page?status=yes');$jsrestart";
    $html[]=$tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function backup_parameters_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LogSynBackupEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupEnable"));
    $schedules[1]="{each} 1 {hour}";
    $schedules[2]="{each} 2 {hours}";
    $schedules[4]="{each} 4 {hours}";
    $schedules[8]="{each} 8 {hours}";
    $schedules[9]="{each_day} 00:01";
    $schedules[24]="{each_day} 01:00";
    $schedules[25]="{each_day} 02:00";
    $schedules[26]="{each_day} 03:00";
    $schedules[27]="{each_day} 04:00";


    $LogSynBackupResource=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupResource");
    $LogSynBackupSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupSchedule"));
    $LogSynBackupRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupRetention"));
    if($LogSynBackupRetention==0){$LogSynBackupRetention=7;}
    if($LogSynBackupSchedule==0){$LogSynBackupSchedule=9;}

    $AUTOFSR[null]="{select}";
    $autofs=new autofs();
    $hashZ=$autofs->automounts_Browse();
    foreach ($hashZ as $localmount=>$array){$AUTOFSR[$localmount]=$localmount;}

    $form[] = $tpl->field_checkbox("LogSynBackupEnable", "{enable_this_option}", $LogSynBackupEnable, true);
    $form[] = $tpl->field_array_hash($AUTOFSR, "LogSynBackupResource", "{automount_ressource}", $LogSynBackupResource);
    $form[] = $tpl->field_array_hash($schedules,"LogSynBackupSchedule","{backup_every}",$LogSynBackupSchedule);
    $form[]=$tpl->field_numeric("LogSynBackupRetention","{log_retention} ({days})",$LogSynBackupRetention);

    $jsrestart="dialogInstance1.close();LoadAjax('logsink-status-params','$page?status=yes');";
    $html[]=$tpl->form_outside(null, $form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

    return true;
}
function backup_parameters_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Saving Log sink parameters");
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/logsink/schedules"));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        echo (is_object($json) && isset($json->Error))?$json->Error:"articarest: ".json_last_error_msg();
        return false;
    }
    return true;
}

function client_package_download():bool{
    return syslogtls_client_package_download();
}
function client_package_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter="dialogInstance2.close();document.location.href='/$page?client-package-download=yes';";
    $html[]="<div id='client-ssl-create'></div>";
    $html[]=syslogtls_client_package_form($tpl,$jsafter);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function client_package_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $error=syslogtls_client_package_build($_POST["client_hostname"],$_POST["server_hostname"],$_POST["client_password"]);
    if($error<>""){
        echo $tpl->post_error($error);
        return false;
    }
    admin_tracks("Creating Log sink client package for ".$_POST["client_hostname"]);
    return true;
}
function status_start():bool{
    $page=CurrentPageName();
    echo "<div id='logsink-status-params'></div><script>LoadAjax('logsink-status-params','$page?status=yes');</script>";
    return true;
}

function status():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();

    $tpl->table_form_section("{backup}");
    $schedules[1]="{each} 1 {hour}";
    $schedules[2]="{each} 2 {hours}";
    $schedules[4]="{each} 4 {hours}";
    $schedules[8]="{each} 8 {hours}";
    $schedules[9]="{each_day} 00:01";
    $schedules[24]="{each_day} 01:00";
    $schedules[25]="{each_day} 02:00";
    $schedules[26]="{each_day} 03:00";
    $schedules[27]="{each_day} 04:00";
    $LogSynBackupEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupEnable"));
    $AutoFSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled"));
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}
    $LogSyncMoveDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSyncMoveDir"));

    $tpl->table_form_field_js("Loadjs('$page?directory-params=yes')","AsSystemAdministrator");
    $ico_directory=ico_directory;

    if($LogSyncMoveDir<>null){
        $ico_directory=ico_running;
        $LogSinkWorkDir="$LogSinkWorkDir&nbsp;&nbsp;<i class=\"fa-solid fa-arrow-right-long-to-line fa-beat\"></i>&nbsp;&nbsp;$LogSyncMoveDir";
    }
    $tpl->table_form_field_text("{global_directory}",$LogSinkWorkDir,$ico_directory);



    if($AutoFSEnabled==0){
        $LogSynBackupEnable=0;

    }
    $tpl->table_form_field_js("Loadjs('$page?backup-params=yes')","AsSystemAdministrator");
    $LogSynBackupSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupSchedule"));
    if($LogSynBackupSchedule==0){
        $LogSynBackupSchedule=9;
    }
    $tpl->table_form_field_bool("{enable_backup}",$LogSynBackupEnable,ico_backup_remote);
    if($LogSynBackupEnable==1) {
        $tpl->table_form_field_text("{backup_every}", $schedules[$LogSynBackupSchedule], ico_timeout);
        $LogSynBackupResource=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupResource");
        $LogSynBackupRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupRetention"));
        if($LogSynBackupRetention==0){$LogSynBackupRetention=7;}

        $tpl->table_form_field_text("{automount_ressource}", $LogSynBackupResource, ico_folder);
        $tpl->table_form_field_text("{log_retention}", $LogSynBackupRetention ." {days}", ico_timeout);

    }else{
        $warn1="";
        if($AutoFSEnabled==0){
            $warn1="<br><small>{automount_must_be_enabled}";
        }
        $tpl->table_form_field_warning("{backup_feature_not_enabled}$warn1");

    }

    $LogSynRTEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynRTEnabled"));
    $LogSynRTMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynRTMaxSize"));
    if($LogSynRTMaxSize==0){$LogSynRTMaxSize=80;}

    $tpl->table_form_field_js("Loadjs('$page?realtime-params=yes')","AsSystemAdministrator");
    $tpl->table_form_section("{realtime_events_squid}");
    $tpl->table_form_field_bool("{enable_this_option}",$LogSynRTEnabled,ico_search_in_file);
    if($LogSynRTEnabled==1) {
        $tpl->table_form_field_text("{srv_clamav.MaxFileSize}", $LogSynRTMaxSize . " MB", ico_file);
    }

    $APP_WAZHU_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_INSTALLED"));
    $EnableWazhuCLient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWazhuCLient"));
    $LogSynWazuh=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynWazuh"));
    if($APP_WAZHU_INSTALLED==0){$EnableWazhuCLient=0;}
    if($EnableWazhuCLient==0) {
        $tpl->table_form_field_js("blur()", "AsSystemAdministrator");
        $tpl->table_form_field_text("{APP_WAZHU}", "{not_installed}", ico_sensor);
    }else{
        $tpl->table_form_field_js("Loadjs('$page?wazuh-params=yes')", "AsSystemAdministrator");
        $tpl->table_form_field_bool("{APP_WAZHU}",$LogSynWazuh,ico_sensor);
    }


    if($LogSynBackupEnable==1) {
        $topbuttons[] = array("Loadjs('$page?run-backup=yes');", ico_play, "{run_backup}");
    }
    $s_PopUp="s_PopUp('https://wiki.articatech.com/system/syslog/log-sink','1024','800')";
    $topbuttons[] = array($s_PopUp, ico_support, "Wiki URL");


    $config["TITLE"]="{APP_WAZHU}";
    $config["TOKEN_INSTALLED"]="APP_WAZHU_INSTALLED";
    $config["TOKEN"]="EnableWazhuCLient";


    $TINY_ARRAY["TITLE"]="{logs_sink} &nbsp;&raquo;&nbsp; {parameters}";
    $TINY_ARRAY["ICO"]=ico_logsink;
    $TINY_ARRAY["EXPL"]="{log_sink_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    echo "<script>$headsjs</script>";
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

function top_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%;'>";
    $html[]="<tr>";


    $html[]="<td valign='top' style='padding-left:10px'>";
    $html[]=top_status_syslog();
    $html[]="</td>";

    $html[]="<td valign='top' style='padding-left:10px'>";
    $html[]=top_status_ssl();
    $html[]="</td>";

    $html[]="<td valign='top' style='padding-left:10px'>";
    $html[]=SyslogLogSink_status();
    $html[]="</td>";

    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>LoadAjax('server-list','$page?server-list=yes');</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}