<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.autofs.inc");

if(isset($_POST["RotateClock"])){Save();exit;}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["main-defaultsize-js"])){main_default_size_js();exit;}
if(isset($_GET["main-default-size-popup"])){main_default_size_popup();exit;}
if(isset($_GET["remote-syslog-status"])){main_logsink_status();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_POST["LegalLogArticaServer"])){legal_log_server_save();exit;}
if(isset($_POST["BackupMaxDaysDir"])){Save();exit;}
if(isset($_POST["BackupSquidLogsUseNas"])){Save();exit;}
if(isset($_POST["SquidRotateAutomount"])){Save();exit;}
if(isset($_POST["LogSinkClient"])){main_logsink_Save();exit;}
if(isset($_GET["import-js"])){import_js();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_POST["nic-settings"])){Save_nic();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_GET["legal-log-server"])){legal_log_server();exit;}
if(isset($_GET["legal-log-server-start"])){legal_log_server_start();exit;}
if(isset($_GET["legal-log-status"])){legal_log_server_status();exit;}
if(isset($_GET["legal-log-server-status"])){legal_log_server_service_status();exit;}
if(isset($_GET["main-logsink-js"])){main_logsink_js();exit;}
if(isset($_GET["main-logsink-popup"])){main_logsink_popup();exit;}
if(isset($_GET["ChangeSystemLog-js"])){ChangeSystemLog_js();exit;}
if(isset($_GET["ChangeSystemLog-popup"])){ChangeSystemLog_popup();exit;}
if(isset($_POST["ChangeSystemLogDirectory"])){ChangeSystemLog_Save();exit;}
if(isset($_GET["ChangeSystemLog-confirm"])){ChangeSystemLog_Confirm();exit;}
if(isset($_POST["ChangeSystemLogDirectoryConfirm"])){die();}

if(isset($_GET["nas-squid-js"])){nas_js();exit;}
if(isset($_GET["nas-squid-popup"])){nas_popup();exit;}
if(isset($_GET["decrypt-backups-js"])){decrypt_backup_js();exit;}
if(isset($_POST["decrypt-backups"])){decrypt_backup_perform();exit;}
if(isset($_GET["main-section-js"])){main_section_js();exit;}
if(isset($_GET["main-section-popup"])){main_section_popup();exit;}

if(isset($_GET["main-autofs-js"])){main_autofs_js();exit;}
if(isset($_GET["main-autofs-popup"])){main_autofs_popup();exit;}

if(isset($_GET["tab"])){tabs();exit;}

page();

function nas_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{NAS_storage}","$page?nas-squid-popup=yes");
}
function import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{import}","$page?import-popup=yes");
}
function import_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $html[]="<div id='upload-black-progress'>";
    $html[]=$tpl->div_explain("{upload_log_explain}");
    $html[]="<div class='center' style='margin:30px'>";
    $html[]=$tpl->button_upload("{upload} (*.gz,*.log)",$page,null,"&function=$function");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function file_uploaded():bool{
    $tpl=new template_admin();
    $file="/usr/share/artica-postfix/ressources/conf/upload/{$_GET["file-uploaded"]}";

    if(!preg_match("#\.(gz|log)$#i",basename($file))){
        $tpl->js_error("$file unexpected file..");
        @unlink($file);
        return false;
    }

    $jscompile=$tpl->framework_buildjs(
        "/legal/logs/uploaded/{$_GET["file-uploaded"]}",
        "squid.legal.progress",
        "squid.legal.txt",
        "upload-black-progress","dialogInstance1.close()");


    header("content-type: application/x-javascript");
    echo "$jscompile\n";
    return true;

}

function main_section_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{parameters}","$page?main-section-popup=yes");
}
function main_logsink_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{logs_sink}","$page?main-logsink-popup=yes");
}
function ChangeSystemLog_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{system_logs_path}","$page?ChangeSystemLog-popup=yes");
}
function main_default_size_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{parameters}","$page?main-default-size-popup=yes");
}

function main_autofs_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{automount_center}","$page?main-autofs-popup=yes");
}

function decrypt_backup_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
   echo $tpl->js_confirm_execute("{decrypt_backups}<br>{apply_upgrade_help}","decrypt-backups","yes");
}
function decrypt_backup_perform():bool{
    admin_tracks("Legal Logs: send order to decrypt all backups");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidRotateEnableCrypt",0);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("syslog.php?decrypt-backup=yes");
    return true;
}



function Save():bool{
    $tpl=new template_admin();
    if(isset($_POST["RotateClock"])) {
        $sch = explode(":", $_POST["RotateClock"]);
        $_POST["LogRotateH"] = $sch[0];
        $_POST["LogRotateM"] = $sch[1];
    }
	if(isset($_POST["WebDavSquidLogsEnabled"])){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?webdav-service=yes");
    }

    if(isset($_POST["SquidRotateEnableCrypt"])){
        if($_POST["SquidRotateEnableCrypt"]==1) {
            $len = strlen($_POST["LogRotateCrypt"]);
            if($len>0){
                if ($len < 32) {
                    echo $tpl->post_error("{encrypt_backups}: {passphrase} (32 {characters}) {current} $len !");
                    return false;
                }
            }else{
                unset($_POST["SquidRotateEnableCrypt"]);
                unset($_POST["LogRotateCrypt"]);
            }
        }

    }
    $tpl->SAVE_POSTs();

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?monit-config=yes");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?rotatebuild=yes");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?global-logging-center=yes");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/reconfigure");

    admin_tracks("Logs rotation parameters as been modified.");
    return true;
}

function legal_log_server_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LegallogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegallogServerPort"));
    if($LegallogServerPort==0){$LegallogServerPort=10514;}
    
    $form[]=$tpl->field_numeric("LegallogServerPort","{listen_port} (TCP)",$LegallogServerPort);

    $jsrestart=$tpl->framework_buildjs("/syslog/reconfigure",
        "syslog.restart.progress",
        "syslog.restart.log","progress-syslod-restart",
        "LoadAjax('table-loader-syslod-service','$page?tabs=yes');");

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='width:250px'><div id='legal-log-server-status'></div></td>";
    $html[]="<td valign='top' style='padding-left:20px'>";
    $html[]=$tpl->form_outside("{parameters}",$form,null,"{apply}",$jsrestart);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="LoadAjax('legal-log-server-status','$page?legal-log-server-status=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function legal_log_server_service_status(){

    $tpl=new template_admin();
    $LegalLogsProxyServers=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogsProxyServers"));
    $BackupMaxDaysDirSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDirSize"));
    $BackupMaxDaysDirPercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDirPercent"));
    $BackupMaxDaysDirScanTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDirScanTime"));
    $smstyle="style='color:white;font-size:16px'";

    if($BackupMaxDaysDirSize >  500){

        $BackupMaxDaysDirSize_text=FormatBytes($BackupMaxDaysDirSize/1024);
        $sdate=$tpl->time_to_date($BackupMaxDaysDirScanTime,true);
        $widget=$tpl->widget_vert("{disk_space_used}","{$BackupMaxDaysDirPercent}%<br><small $smstyle>$BackupMaxDaysDirSize_text ($sdate)</small>");

        if($BackupMaxDaysDirPercent>80){
            $widget=$tpl->widget_jaune("{disk_space_used}","{$BackupMaxDaysDirPercent}%<br><small $smstyle>$BackupMaxDaysDirSize_text ($sdate)</small>");
        }
        if($BackupMaxDaysDirPercent>95){
            $widget=$tpl->widget_rouge("{disk_space_used}","{$BackupMaxDaysDirPercent}%<br><small $smstyle>$BackupMaxDaysDirSize_text ($sdate)</small>");
        }

    }else{
        $widget=$tpl->widget_grey("{disk_space_used}","{unknown}");
    }

    $html[]=$widget;
    $html[]="<p>&nbsp;</p>";



    if(count($LegalLogsProxyServers)>0){
        $tt="{client}";
        if(count($LegalLogsProxyServers)>1){
            $tt="{clients}";
        }
        $html[]=$tpl->widget_vert("{artica_clients}",count($LegalLogsProxyServers)." $tt");
    }else{
        $html[]=$tpl->widget_grey("{artica_clients}","0 {client}");
    }



    echo $tpl->_ENGINE_parse_body($html);

}
function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{legal_logs}","fa fa-file-archive","{legal_logs_explain}",
        "$page?tab=yes","logs-rotate","progress-logrotate-restart",false,"table-loader");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{legal_logs}",$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function tabs():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $LegallogServer     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegallogServer"));
    $users              = new usersMenus();


    if($users->AsProxyMonitor) {
        $array["{parameters}"]="$page?main-start=yes";
    }



    echo $tpl->tabs_default($array);
    return true;
}

function legal_log_server_start(){
    $page=CurrentPageName();
    $LegallogServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegallogServer"));
    $param="legal-log-server";
    if($LegallogServer==1){

        $param="legal-log-status";
    }


    echo "<div id='legal-logs-client-params' style='margin-top:20px'></div>
    <script>LoadAjax('legal-logs-client-params','$page?$param=yes');</script>";
}

function legal_log_server_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}

function legal_log_server(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));
    $LegalLogArticaServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaServer"));
    $LegalLogArticaPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaPort"));
    $LegalLogArticaUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaUsername"));
    $LegalLogArticaPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaPassword"));
    if($LegalLogArticaPort==0){$LegalLogArticaPort=9000;}
    if($LegalLogArticaUsername==null){$LegalLogArticaUsername="Manager";}

    $html[]= "<div id='legal_log_server_progress'></div>";


    if($LegalLogArticaClient==0){
        $btname="{connect}";
        $title="{connect}";
        $form[]= $tpl->field_text("LegalLogArticaServer","{REMOTE_ARTICA_SERVER}",$LegalLogArticaServer,true);
        $form[]= $tpl->field_numeric("LegalLogArticaPort","{REMOTE_ARTICA_SERVER_PORT}",$LegalLogArticaPort,true);
        $form[]= $tpl->field_text("LegalLogArticaUsername","{REMOTE_ARTICA_USERNAME}",$LegalLogArticaUsername,true);
        $form[]= $tpl->field_password("LegalLogArticaPassword","{REMOTE_ARTICA_PASSWORD}",$LegalLogArticaPassword,true);

        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/legallogs.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/legallogs.progress.txt";
        $ARRAY["TITLE"]="{APP_LEGAL_LOGS_SERVER}";
        $ARRAY["CMD"]="squid.php?legal-logs-progress=yes";
        $ARRAY["AFTER"]="LoadAjax('legal-logs-client-params','$page?legal-log-server=yes');";
        $prgress=base64_encode(serialize($ARRAY));
        $applyjs="Loadjs('fw.progress.php?content=$prgress&mainid=progress-logrotate-restart')";
    }else{
        $LegalLogSyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogSyslogPort"));
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/legallogs.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/legallogs.progress.txt";
        $ARRAY["TITLE"]="{APP_LEGAL_LOGS_SERVER}";
        $ARRAY["CMD"]="squid.php?legal-logs-disconnect=yes";
        $ARRAY["AFTER"]="LoadAjax('legal-logs-client-params','$page?legal-log-server=yes');";
        $prgress=base64_encode(serialize($ARRAY));
        $applyjs="Loadjs('fw.progress.php?content=$prgress&mainid=progress-logrotate-restart')";


        $btname="{disconnect}";
        $title="$LegalLogArticaServer:$LegalLogSyslogPort";
        $form[]= $tpl->field_info("LegalLogArticaServer","{REMOTE_ARTICA_SERVER}",$LegalLogArticaServer);
        $form[]= $tpl->field_info("LegalLogArticaPort","{REMOTE_ARTICA_SERVER_PORT}",$LegalLogArticaPort);
        $form[]= $tpl->field_info("LegalLogSyslogPort","{remote_port}",$LegalLogSyslogPort);

    }



    $html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$applyjs,"AsSquidAdministrator",true);


    $TINY_ARRAY["TITLE"]="{APP_LEGAL_LOGS_SERVER}";
    $TINY_ARRAY["ICO"]="far fa-share-all";
    $TINY_ARRAY["EXPL"]="{APP_LEGAL_LOGS_SERVER_FROM}";
    $TINY_ARRAY["URL"]="logs-rotate";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function nas_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
    $BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
    $BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
    $BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
    $BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
    $BackupSquidLogsNASFolder2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder2");
    if($BackupSquidLogsNASFolder2==null){$BackupSquidLogsNASFolder2="artica-backup-syslog";}
    $disabled=false;



    $form[]=$tpl->field_checkbox("BackupSquidLogsUseNas","{use_remote_nas}",$BackupSquidLogsUseNas,"BackupSquidLogsNASIpaddr,BackupSquidLogsNASFolder,BackupSquidLogsNASFolder2,BackupSquidLogsNASUser,BackupSquidLogsNASPassword","{BackupSquidLogsUseNas_explain}");
    $form[]=$tpl->field_text("BackupSquidLogsNASIpaddr","{hostname}",$BackupSquidLogsNASIpaddr,false,"",$disabled);
    $form[]=$tpl->field_text("BackupSquidLogsNASFolder","{shared_folder}",$BackupSquidLogsNASFolder,false,"",$disabled);
    $form[]=$tpl->field_text("BackupSquidLogsNASFolder2","{storage_directory}",$BackupSquidLogsNASFolder2,false,"",$disabled);
    $form[]=$tpl->field_text("BackupSquidLogsNASUser","{username}",$BackupSquidLogsNASUser,false,"",$disabled);
    $form[]=$tpl->field_password("BackupSquidLogsNASPassword","{password}",$BackupSquidLogsNASPassword,false,"",$disabled);

    $html[]="<div style='margin-top:5px' id='squid-logs-nas-progress'>&nbsp;</div>";


    $jsTestNas=$tpl->framework_buildjs("/logrotate/testnas",
        "squid.nas.storage.progress","squid.nas.storage.progress.log",
    "squid-logs-nas-progress");



    $form[]=$tpl->form_add_button("{test_connection}",$jsTestNas);
    $html[]=$tpl->form_outside(null, $form,"{log_retention_nas_text}","{apply}",form_push_js(),"AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function form_push_js():string{
    $page=CurrentPageName();
    $f[]="if(typeof dialogInstance1 == 'object'){ dialogInstance1.close();}";
    $f[]="LoadAjax('proxy-rotate-main-params','$page?main=yes');";
    $f[]="if(document.getElementById('section-syslog-rules')){";
    $f[]="LoadAjax('section-syslog-rules','fw.syslogd.remote.php?table=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('hacluster-parameters')){";
    $f[]="LoadAjaxSilent('hacluster-parameters','fw.hacluster.status.php?parameters-table=yes');";
    $f[]="}";

    return @implode("",$f);
}

function main_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width: 350px;padding-top:8px'>";
    $html[]="<div id='remote-syslog-status'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align: top;width: 99%;padding-left: 20px'><div id='proxy-rotate-main-params'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $Interval=$tpl->RefreshInterval_js("remote-syslog-status",$page,"remote-syslog-status=yes");
    $html[]="LoadAjax('proxy-rotate-main-params','$page?main=yes');";
    $html[]=$Interval;
    $html[]="</script>";
    echo @implode("",$html);
}

function main(){
    $tpl=new template_admin();
    $AutoFSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled"));
    $SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
    $EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
    $page=CurrentPageName();

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaClient",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegallogServer",0);

    if($SquidPerformance>2){$AutoFSEnabled=0;}
    if($EnableIntelCeleron==1){$AutoFSEnabled=0;}
    $SquidRotateOnlySchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateOnlySchedule"));
    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    $BackupMaxDays=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDays");
    $BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
    $LogRotateH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateH");
    $LogRotateM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateM");
    $LogsRotateDefaultSizeRotation=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation");
    if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
    if(!is_numeric($BackupMaxDays)){$BackupMaxDays=365;}
    if($LogRotateH==null){$LogRotateH="00";}
    if($LogRotateM==null){$LogRotateM="05";}
    if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    $SquidLogRotateFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogRotateFreq"));
    if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
    $BackupLogsMaxStoragePercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupLogsMaxStoragePercent"));
    if($BackupLogsMaxStoragePercent==0){$BackupLogsMaxStoragePercent=50;}
    $SquidRotateClean=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateClean"));

    $SquidLogsExceptions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogsExceptions");
    if(!is_file("/etc/artica-postfix/settings/Daemons/SquidLogsExceptions")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogsExceptions", "127.0.0.1");}

    $LogsRotateDefaultSizeRotation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation"));
    if($LogsRotateDefaultSizeRotation<5){$LogsRotateDefaultSizeRotation=100;}

    $sock=new sockets();
    $target=base64_encode("/var/log");
    $json=json_decode($sock->REST_API("/system/directory/target/$target"));
    $size=FormatBytes($json->Size);
    $tpl->table_form_field_js("Loadjs('$page?ChangeSystemLog-js=yes')");
    $tpl->table_form_field_info("{system_logs_path}",$json->Info." ($size)",ico_folder);



    $tpl->table_form_field_js("Loadjs('$page?main-section-js=yes')");
    $tpl->table_form_field_info("{SquidLogsExceptions}",$SquidLogsExceptions,ico_computer_down);

    $tpl->table_form_field_js("Loadjs('$page?main-defaultsize-js=yes')");
    if($SquidRotateOnlySchedule==0){
        $tpl->table_form_field_info("{log_rotation}","{export_log_if_size_exceed} {$LogsRotateDefaultSizeRotation}MB",ico_clock);
    }else{
        $tpl->table_form_field_info("{log_rotation}","{every_day_at} $LogRotateH:$LogRotateM",ico_clock);
        $tpl->table_form_field_text("{max_log_file_size}","{$LogsRotateDefaultSizeRotation}MB",ico_weight);
    }
-
    $tpl->table_form_field_js("Loadjs('$page?main-section-js=yes')");
    $tpl->table_form_field_info("{temporay_storage_path}",$LogRotatePath,ico_folder);
    $tpl->table_form_field_info("{backup_folder}",$BackupMaxDaysDir,ico_folder);

    if($SquidRotateClean==1){
        $tpl->table_form_field_info("{clean_old_files}","$BackupMaxDays {days}",ico_trash);
    }else{
        $tpl->table_form_field_info("{clean_old_files}","{never}",ico_trash);
    }

    $legal_log_external_explain=$tpl->_ENGINE_parse_body("{legal_log_external_explain}");
    $legal_log_external_explain=str_replace("%s","<a href='https://wiki.articatech.com/en/system/syslog/log-sink' target=_top>{logs_sink}</a>",$legal_log_external_explain);
    $tpl->table_form_section("{external_storage}",$legal_log_external_explain);

    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

    if($HaClusterClient==1){
        $tpl->table_form_field_js("");
        $tpl->table_form_field_info("{logs_sink}", "{use_lb_system}", ico_forward);

    }else {

        $tpl->table_form_field_js("Loadjs('$page?main-logsink-js=yes')");
        $LogSinkClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClient"));
        if ($LogSinkClient == 0) {
            $tpl->table_form_field_info("{logs_sink}", "{inactive2}", ico_forward);

        } else {
            $proto = "udp";

            $LogSinkClientPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientPort"));
            $LogSinkClientServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinClientServer"));
            $LogSinkClientTCP = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientTCP"));
            if ($LogSinkClientTCP == 1) {
                $proto = "tcp";
            }
            $tpl->table_form_field_info("{logs_sink}", "$proto://$LogSinkClientServer:$LogSinkClientPort", ico_forward);
        }
    }

    $SquidRotateAutomount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomount"));
    $SquidRotateAutomountRes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountRes");
    $SquidRotateAutomountFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountFolder");

    if($SquidRotateAutomount==0) {
        if($AutoFSEnabled==0) {
            $tpl->table_form_field_js("");
            $tpl->table_form_field_info("{automount_center}", "{not_installed}", ico_storage);
        }else{
            $tpl->table_form_field_js("Loadjs('$page?main-autofs-js=yes')");
            $tpl->table_form_field_info("{automount_center}", "{inactive2}", ico_storage);
        }

    }else{
        $tpl->table_form_field_js("Loadjs('$page?main-autofs-js=yes')");
        $tpl->table_form_field_info("{automount_center}","/automounts/$SquidRotateAutomountRes/$SquidRotateAutomountFolder",ico_storage);
    }

    $BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));

    $tpl->table_form_field_js("Loadjs('$page?nas-squid-js=yes')");
    if($BackupSquidLogsUseNas==0){
        $tpl->table_form_field_info("{NAS_storage}", "{inactive2}", ico_storage);
    }else{
        $BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
        $BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
        $tpl->table_form_field_info("{use_remote_nas}", "$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder", ico_storage);
    }



    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";


    $export_logs_now=$tpl->framework_buildjs("/proxy/rotate","squid.rotate.progress","squid.rotate.progress.txt","progress-logrotate-restart");



    $topbuttons[] = array($export_logs_now,ico_export,"{export_logs_now}");
    $topbuttons[] = array("Loadjs('$page?import-js=yes')",ico_import,"{import}");

    if(is_file("/etc/artica-postfix/crypted-legal-logs")) {
        $topbuttons[] = array("Loadjs('$page?decrypt-backups-js=yes')","fa-duotone fa-box-open","{decrypt_backups}");
    }


    $TINY_ARRAY["TITLE"]="{legal_logs}";
    $TINY_ARRAY["ICO"]="fa fa-file-archive";
    $TINY_ARRAY["EXPL"]="{legal_logs_explain}";
    $TINY_ARRAY["URL"]="logs-rotate";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$tpl->table_form_compile();
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
function main_autofs_popup(){
    $tpl=new template_admin();
    $AutoFSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled"));
    $page=CurrentPageName();

    $AUTOFSR[null]="{select}";
    if($AutoFSEnabled==1){
        $autofs=new autofs();
        $hashZ=$autofs->automounts_Browse();
        if(file_exists('ressources/usb.scan.inc')){include("ressources/usb.scan.inc");}
        foreach ($hashZ as $localmount=>$array){$AUTOFSR[$localmount]="{$localmount}";}
    }

    $SquidRotateAutomount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomount"));
    $SquidRotateAutomountRes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountRes");
    $SquidRotateAutomountFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountFolder");

    $form[] = $tpl->field_checkbox("SquidRotateAutomount", "{automount_ressource}", $SquidRotateAutomount, "SquidRotateAutomountFolder,SquidRotateAutomountFolder", "{automount_ressource_explain}");
    $form[] = $tpl->field_array_hash($AUTOFSR, "SquidRotateAutomountRes", "{resource}", $SquidRotateAutomountRes);
    $form[] = $tpl->field_text("SquidRotateAutomountFolder", "{directory}", $SquidRotateAutomountFolder, false, "{SquidRotateAutomountFolder}");

    $tpl->form_privileges="AsSquidAdministrator";
    $html[]="<div style='margin-top:20px'>&nbsp;</div>";
    $html[]=$tpl->form_outside("{automount_center}", @implode("\n", $form),null,
        "{apply}",
        form_push_js(),
        "AsSquidAdministrator",true);

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function main_default_size_popup():bool{
    $tpl=new template_admin();
    $SquidRotateOnlySchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateOnlySchedule"));
    $LogsRotateDefaultSizeRotation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation"));
    if($LogsRotateDefaultSizeRotation<5){
        $LogsRotateDefaultSizeRotation=100;
    }
    $LogRotateH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateH");
    $LogRotateM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateM");
    if($LogRotateH==null){$LogRotateH="00";}
    if($LogRotateM==null){$LogRotateM="05";}

    for($i=0;$i<24;$i++){
        $H=$i;
        if($i<10){$H="0$i";}
        $Hours[$i]=$H;
    }

    for($i=0;$i<60;$i++){
        $M=$i;
        if($i<10){$M="0$i";}
        $Mins[$i]=$M;
    }

    $form[]=$tpl->field_clock("RotateClock", "{schedule}", "$LogRotateH:$LogRotateM","");
    $form[]=$tpl->field_checkbox("SquidRotateOnlySchedule", "{only_by_schedule}",
        $SquidRotateOnlySchedule,"SquidRotateForceRestart","{only_by_schedule_squid_rotation_explain}");

    $form[] = $tpl->field_numeric("LogsRotateDefaultSizeRotation",
        "{max_log_file_size} (MB)", $LogsRotateDefaultSizeRotation);

    $tpl->form_privileges="AsSquidAdministrator";
    $html[]="<div style='margin-top:20px'>&nbsp;</div>";
    $html[]=$tpl->form_outside("", $form, "{artica_log_rotation_explain}","{apply}",
        form_push_js(),"AsSquidAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function main_section_popup():bool{
	$tpl=new template_admin();

	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
    $page=CurrentPageName();
	
	if($SquidPerformance>2){$AutoFSEnabled=0;}
	if($EnableIntelCeleron==1){$AutoFSEnabled=0;}
    $BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));

	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	$BackupMaxDays=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
    if(!is_numeric($BackupMaxDays)){$BackupMaxDays=365;}

	

	

	
	$t=time();
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	

	$SquidLogRotateFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}

	
	$BackupLogsMaxStoragePercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupLogsMaxStoragePercent"));
	
	if($BackupLogsMaxStoragePercent==0){$BackupLogsMaxStoragePercent=50;}
	$SquidRotateAutomount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomount"));
	$SquidRotateClean=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateClean"));
	$SquidRotateAutomountRes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountRes");
	$SquidRotateAutomountFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountFolder");
	

	
	$SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
	$t=time();
	$SYSLOG=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSyslogAdd"));
	if(trim($SYSLOG["PERSO_EVENT"])==null){$SYSLOG["PERSO_EVENT"]="%>eui %>a %[ui %[un %tl %rm %ru HTTP/%rv %>Hs %<st %Ss:%Sh %{User-Agent}>h %{X-Forwarded-For}>h %<A %>A %tr %mt";}

	
	$freq[20]="20mn";
	$freq[30]="30mn";
	$freq[60]="1h";
	$freq[120]="2h";
	$freq[300]="5h";
	$freq[600]="10h";
	$freq[1440]="24h";
	$freq[2880]="48h";
	$freq[4320]="3 {days}";
	$freq[10080]="1 {week}";
	
	

    $SquidLogsExceptions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogsExceptions");
    if(!is_file("/etc/artica-postfix/settings/Daemons/SquidLogsExceptions")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogsExceptions", "127.0.0.1");}

	$disabled=false;
    $SquidRotateForceRestart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateForceRestart"));
    $SquidRotateEnableCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateEnableCrypt"));



    $form[]=$tpl->field_checkbox("SquidRotateForceRestart", "{restart_proxy_service}",
            $SquidRotateForceRestart);



	$form[]=$tpl->field_text("LogRotatePath","{temporay_storage_path}",$LogRotatePath,false,"{temporay_storage_path_explain}");
	$form[]=$tpl->field_text("BackupMaxDaysDir","{backup_folder}",$BackupMaxDaysDir,false,"{BackupMaxDaysDir_explain}");


	$form[]=$tpl->field_checkbox("SquidRotateClean", "{clean_old_files}", $SquidRotateClean,false,"{clean_old_files_accesslog_explain}");
	$form[]=$tpl->field_numeric("BackupMaxDays", "{max_storage_days}", $BackupMaxDays,"{max_storage_days_log_explain}");
	$form[]=$tpl->field_numeric("BackupLogsMaxStoragePercent", "{max_percent_storage} (%)", $BackupLogsMaxStoragePercent,"{BackupLogsMaxStoragePercent_explain}");
	$form[]=$tpl->field_text("SquidLogsExceptions","{SquidLogsExceptions}",$SquidLogsExceptions,false,"{SquidLogsExceptions_explain}");

    $LogRotateCrypt = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateCrypt"));
    if(!is_file("/etc/artica-postfix/crypted-legal-logs")) {
        $form[] = $tpl->field_section("{encryption}");
        $form[] = $tpl->field_checkbox("SquidRotateEnableCrypt", "{encrypt_backups}",
            $SquidRotateEnableCrypt);
        $form[] = $tpl->field_password2("LogRotateCrypt", "{passphrase} (32 {characters})",
            $LogRotateCrypt);
    }


	$tpl->form_privileges="AsSquidAdministrator";
    $html[]="<div style='margin-top:20px'>&nbsp;</div>";
	$html[]=$tpl->form_outside("", $form, null,"{apply}",
        form_push_js(),"AsSquidAdministrator",true);

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ChangeSystemLog_popup(){
    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $target=base64_encode("/var/log");
    $json=json_decode($sock->REST_API("/system/directory/target/$target"));
    $jsrestart=form_push_js();
    $jsrestart="$jsrestart;Loadjs('$page?ChangeSystemLog-confirm=yes')";
    $form[]=$tpl->field_browse_directory("ChangeSystemLogDirectory","{system_logs_path}",$json->Info);
    $html[]=$tpl->form_outside("",$form,"",
        "{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function ChangeSystemLog_Confirm(){
    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $target=base64_encode("/var/log");
    $json=json_decode($sock->REST_API("/system/directory/target/$target"));
    $ChangeSystemLogDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChangeSystemLogDirectory");
    $ChangeSystemLogDirectoryUU=base64_encode($ChangeSystemLogDirectory);
    $restart=$tpl->framework_buildjs("/system/directory/move/logs/$ChangeSystemLogDirectoryUU",
    "movelogs.progress","movelogs.log","progress-logrotate-restart");

    return $tpl->js_confirm_execute("{move} $json->Info -> $ChangeSystemLogDirectory/var/log","ChangeSystemLogDirectoryConfirm","yes",$restart);

}
function ChangeSystemLog_Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ChangeSystemLogDirectory",$_POST["ChangeSystemLogDirectory"]);
    return admin_tracks("Change /var/log directory to {$_POST["ChangeSystemLogDirectory"]}");
}
function LogsSinkEnabled():bool{
    $ActAsASyslogServer     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));
    if($ActAsASyslogServer==0){return false;}
    $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
    if($EnableSyslogLogSink==0){return false;}
    return true;
}
function main_logsink_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(LogsSinkEnabled()){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/status"));
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_RSYSLOG",""));
        return true;
    }

    $LogSinkClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClient"));

    if($LogSinkClient==0){
        $btn[0]["name"] = "{setup}";
        $btn[0]["icon"] = ico_cd;
        $btn[0]["js"] = "Loadjs('$page?main-logsink-js=yes')";
        echo $tpl->widget_grey("{APP_LOGSINK}","{inactive2}",$btn);
        return true;
    }

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/client/logsink/status"));

    if(!$data->Status){
        $btn[0]["name"] = "{setup}";
        $btn[0]["icon"] = ico_cd;
        $btn[0]["js"] = "Loadjs('$page?main-logsink-js=yes')";
        echo $tpl->widget_rouge("{APP_LOGSINK}",$data->Error,$btn);
        return false;

    }
    $Events_text="";
    $SYSLOG_SEND_RULE_STATS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSLOG_SEND_RULE_STATS"));
    $Events=intval($SYSLOG_SEND_RULE_STATS[0]["PROCESSED"]);
    if($Events>0){
        $Events=$tpl->FormatNumber($Events);
        $Events_text="<br><small style='color:white;font-size:18px;font-weight: bold'>$Events {events}</small>";
    }
    $LogSinkClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientPort"));
    $LogSinkClientServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinClientServer"));
    $btn[0]["name"] = "{setup}";
    $btn[0]["icon"] = ico_cd;
    $btn[0]["js"] = "Loadjs('$page?main-logsink-js=yes')";
    echo $tpl->widget_vert("{APP_LOGSINK}","<div ><small style='color:white'>$LogSinkClientServer:$LogSinkClientPort</small>$Events_text</div>",$btn);
    return true;
}

function main_logsink_popup():bool{
    $tpl=new template_admin();

    $LogSinkClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClient"));
    $LogSinkClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientPort"));
    $LogSinkClientServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinClientServer"));
    $LogSinkClientTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientTCP"));
    $LogSinkClientQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientQueue"));
    $LogSinkClientQueueSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientQueueSize"));
    if($LogSinkClientQueue==0){$LogSinkClientQueue=10000;}
    if($LogSinkClientQueueSize==0){$LogSinkClientQueueSize=1000;}
    if($LogSinkClientPort==0){$LogSinkClientPort=514;}
    $jsrestart=form_push_js();



    $form[]=$tpl->field_checkbox("LogSinkClient","{enable}",$LogSinkClient,true);
    $form[]=$tpl->field_text("LogSinClientServer","{remote_syslog_server}",$LogSinkClientServer,true);
    $form[]=$tpl->field_numeric("LogSinkClientPort","{remote_port}",$LogSinkClientPort);
    $form[] = $tpl->field_array_hash(array("1" => "TCP/IP", "0" => "UDP"), "LogSinkClientTCP", "{protocol}", $LogSinkClientTCP);
    $form[]=$tpl->field_section("{cache}");
    $form[]=$tpl->field_numeric("LogSinkClientQueue","{max_records_in_memory} ({events})",$LogSinkClientQueue);
    $form[]=$tpl->field_numeric("LogSinkClientQueueSize","{queue_size} (MB)",$LogSinkClientQueueSize);
    $html[]=$tpl->form_outside("",$form,"{log_sink_explain}",
        "{apply}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

    return true;
}
function main_logsink_Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $LogSinkClient=intval($_POST["LogSinkClient"]);
    if($LogSinkClient==1){
        $LogSinkClientPort=intval($_POST["LogSinkClientPort"]);
        $LogSinClientServer=trim($_POST["LogSinClientServer"]);
        $LogSinkClientTCP=intval($_POST["LogSinkClientTCP"]);

        if($LogSinkClientTCP==0){

            $sock=new sockets();
            $data=$sock->REST_API("/system/network/checkudp/$LogSinClientServer/$LogSinkClientPort");
            $json=json_decode($data);
            if (json_last_error()> JSON_ERROR_NONE) {
                echo $tpl->post_error(json_last_error_msg());
                return false;

            }
            if(!$json->Status){
                echo $tpl->post_error("UDP: $LogSinClientServer:$LogSinkClientPort $json->Error");
                return false;
            }

        }
    }
    $tpl->SAVE_POSTs();

    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/logging/reconfigure");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/reconfigure");
    return admin_tracks_post("Saving the use of a Log Sink server");
}