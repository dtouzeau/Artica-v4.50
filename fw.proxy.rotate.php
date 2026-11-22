<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
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
    if(!fw_progress_page_priv("AsSquidAdministrator|AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $tpl=new template_admin();
    $_GET["file-uploaded"]=fw_progress_upload_name($_GET["file-uploaded"] ?? "");   // REST segment, see fw_progress_upload_name
    if($_GET["file-uploaded"]===""){return fw_progress_bad_value("file-uploaded");}
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
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/logrotate/legal/decrypt"));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $error=(is_object($json) && isset($json->Error))?$json->Error:"articarest: ".json_last_error_msg();
        echo $error;
        return false;
    }
    return true;
}



function Save():bool{
    $tpl=new template_admin();
    if(isset($_POST["RotateClock"])) {
        $sch = explode(":", $_POST["RotateClock"]);
        $_POST["LogRotateH"] = $sch[0];
        $_POST["LogRotateM"] = $sch[1];
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

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/watchdog/monit");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/logs/apply");
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

    // L'ancienne version disposait la vignette de service et le formulaire dans
    // un <table> de MISE EN PAGE (colonne gauche figee a 250px). En flex, le
    // formulaire reprend la largeur quand la fenetre retrecit.
    $html[]="<div style='display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap'>";
    $html[]="<div id='legal-log-server-status' style='flex:0 0 250px;max-width:100%'></div>";
    $html[]="<div style='flex:1 1 380px;min-width:0'>";
    $html[]=$tpl->form_outside("{parameters}",$form,null,"{apply}",$jsrestart);
    $html[]="</div>";
    $html[]="</div>";

    // Bloc de script concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque corps d'accolade JavaScript pour un
    // {token}.
    $script="<script>LoadAjax('legal-log-server-status','$page?legal-log-server-status=yes');</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
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
        $ARRAY["CMD"]="/proxy/legal-logs/connect";
        $ARRAY["AFTER"]="LoadAjax('legal-logs-client-params','$page?legal-log-server=yes');";
        $prgress=base64_encode(serialize($ARRAY));
        $applyjs="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-logrotate-restart')";
    }else{
        $LegalLogSyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogSyslogPort"));
        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/legallogs.progress";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/legallogs.progress.txt";
        $ARRAY["TITLE"]="{APP_LEGAL_LOGS_SERVER}";
        $ARRAY["CMD"]="/proxy/legal-logs/disconnect";
        $ARRAY["AFTER"]="LoadAjax('legal-logs-client-params','$page?legal-log-server=yes');";
        $prgress=base64_encode(serialize($ARRAY));
        $applyjs="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-logrotate-restart')";


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
    // Meme tableau de MISE EN PAGE que ci-dessus, colonne gauche figee a 350px.
    $html[]="<div style='display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap'>";
    $html[]="<div id='remote-syslog-status' style='flex:0 0 350px;max-width:100%;padding-top:8px'></div>";
    $html[]="<div id='proxy-rotate-main-params' style='flex:1 1 380px;min-width:0'></div>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]="LoadAjax('proxy-rotate-main-params','$page?main=yes');";
    $html[]=$tpl->RefreshInterval_js("remote-syslog-status",$page,"remote-syslog-status=yes");
    $html[]="</script>";
    echo @implode("\n",$html);
}

/* ===========================================================================
 * Onglet Parameters (maquette Stitch « Legal logs settings », 2026-11-21).
 *
 * Seules des donnees reelles :
 *  - les reglages lus par le daemon (logrotate/*.go) et par artica-rotate
 *    (chiffrement : SquidRotateEnableCrypt, LogRotateCrypt) ;
 *  - GET /system/directory/target/{/var/log} : emplacement reel et taille (Kio) ;
 *  - GET /logs/capacity (logrotate.Capacity, cache 1 h) : taille des archives,
 *    debit journalier, disque libre, projection de saturation.
 * La retention appliquee est PurgeBackupMaxDays (varlog.go) : toujours active,
 * BackupMaxDays pour les archives, BackupMaxDaysAccess (365 j, sans champ dans
 * la console) pour les archives d'acces du proxy. SquidRotateClean,
 * BackupLogsMaxStoragePercent et SquidRotateForceRestart ne sont lus par aucun
 * code : la page ne les affiche plus et la fenetre ne les propose plus.
 * La colonne de gauche (#remote-syslog-status) n'est pas touchee.
 * ========================================================================= */

function lgl_int(string $key,int $def,int $min=1):int{
    $v=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
    return $v<$min?$def:$v;
}
function lgl_str(string $key,string $def=""):string{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
    return $v===""?$def:$v;
}
// Echappe une valeur de reglage : HTML ET accolades (le moteur prendrait {x} pour un token).
function lgl_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}
// Un token dont chaque %s recoit une valeur deja echappee (texte brut sinon).
function lgl_t($tpl,string $token,array $vals=array()):string{
    $txt=$tpl->_ENGINE_parse_body("{".$token."}");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    $i=0;
    return preg_replace_callback('/%s/',function($m) use ($vals,&$i){
        $v=isset($vals[$i])?(string)$vals[$i]:"";
        $i++;
        return $v;
    },$txt);
}
// Taille en octets -> « 12.4 GB » (FormatBytes compte en Kio).
function lgl_bytes(float $b):string{
    if($b<0){return "&mdash;";}
    return FormatBytes($b/1024,true);
}
// « 2025-11-23 » -> « November 23, 2025 » (mois en token).
function lgl_day(string $ymd):string{
    $t=strtotime($ymd);
    if($t===false){return lgl_e($ymd);}
    return "{".date("F",$t)."} ".date("j",$t).", ".date("Y",$t);
}

// Le modele : tout ce que les cartes affichent, lu une fois.
function lgl_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    // Archivage
    $m["only_schedule"]=intval($s->GET_INFO("SquidRotateOnlySchedule"))==1;
    $m["size_mb"]=lgl_int("LogsRotateDefaultSizeRotation",100,5);
    $h=lgl_str("LogRotateH","00");$mi=lgl_str("LogRotateM","05");
    $m["clock"]=sprintf("%02d:%02d",intval($h),intval($mi));
    // Emplacements
    $m["work_dir"]=lgl_str("LogRotatePath","/home/logrotate");
    $m["backup_dir"]=lgl_str("BackupMaxDaysDir","/home/logrotate_backup");
    // Retention (PurgeBackupMaxDays : <=0 vaut 365)
    $m["max_days"]=lgl_int("BackupMaxDays",365);
    $m["max_days_access"]=lgl_int("BackupMaxDaysAccess",365);
    // Vie privee
    $ex=preg_split('/[\s,;]+/',(string)$s->GET_INFO("SquidLogsExceptions"));
    $m["exceptions"]=array();
    foreach($ex as $ip){$ip=trim($ip);if($ip!==""){$m["exceptions"][]=$ip;}}
    $m["crypt"]=intval($s->GET_INFO("SquidRotateEnableCrypt"))==1;
    $m["crypted_files"]=is_file("/etc/artica-postfix/crypted-legal-logs");
    // Copies externes
    $m["cluster"]=intval($s->GET_INFO("HaClusterClient"))==1;
    $m["sink"]=intval($s->GET_INFO("LogSinkClient"))==1;
    $proto=intval($s->GET_INFO("LogSinkClientTCP"))==1?"tcp":"udp";
    $port=intval($s->GET_INFO("LogSinkClientPort"));
    if($port==0){$port=514;}
    $m["sink_dest"]="$proto://".trim((string)$s->GET_INFO("LogSinClientServer")).":$port";
    $autofs=intval($s->GET_INFO("AutoFSEnabled"));
    if(intval($s->GET_INFO("SquidPerformance"))>2){$autofs=0;}
    if(intval($s->GET_INFO("EnableIntelCeleron"))==1){$autofs=0;}
    $m["autofs_installed"]=$autofs==1;
    $m["automount"]=intval($s->GET_INFO("SquidRotateAutomount"))==1;
    $m["automount_dest"]="/automounts/".trim((string)$s->GET_INFO("SquidRotateAutomountRes"))."/".trim((string)$s->GET_INFO("SquidRotateAutomountFolder"));
    $m["nas"]=intval($s->GET_INFO("BackupSquidLogsUseNas"))==1;
    $m["nas_dest"]=trim((string)$s->GET_INFO("BackupSquidLogsNASIpaddr"))."/".trim((string)$s->GET_INFO("BackupSquidLogsNASFolder"));
    $m["external"]=$m["nas"] || $m["automount"] || ($m["cluster"]?true:$m["sink"]);
    // /var/log
    $m["varlog_path"]="";$m["varlog_kib"]=-1;
    $json=json_decode((string)$s->REST_API("/system/directory/target/".base64_encode("/var/log")));
    if(is_object($json) && isset($json->Status) && $json->Status){
        $m["varlog_path"]=isset($json->Info)?(string)$json->Info:"";
        $m["varlog_kib"]=isset($json->Size)?floatval($json->Size):-1;
    }
    // Capacite du disque des archives
    $m["cap"]=null;
    $json=json_decode((string)$s->REST_API("/logs/capacity"));
    if(is_object($json) && isset($json->Status) && $json->Status && isset($json->Data) && is_object($json->Data)){
        $m["cap"]=$json->Data;
    }
    return $m;
}

function lgl_card_head(string $icon,string $title,string $editJs=""):string{
    $btn="";
    if($editJs!==""){
        $btn="<button type='button' class='lgl-btn' OnClick=\"$editJs\"><i class='fas fa-pen' aria-hidden='true'></i>{lgl_edit}</button>";
    }
    return "<div class='lgl-head'><h3><i class='fas $icon' aria-hidden='true'></i>$title</h3>$btn</div>";
}

// 1. Bandeau d'accroche : puces lues dans les reglages reels.
function lgl_banner($tpl,array $m):string{
    $chips=array();
    $chips[]=array("fa-lock","{lgl_chip_unmodified}");
    $chips[]=array("fa-calendar-check",lgl_t($tpl,"lgl_chip_kept",array($m["max_days_access"])));
    if($m["crypt"]){$chips[]=array("fa-key","{lgl_chip_crypt}");}
    if($m["nas"]){$chips[]=array("fa-server","{lgl_chip_nas}");}
    if($m["cluster"]){$chips[]=array("fa-diagram-project","{lgl_chip_lb}");}
    elseif($m["sink"]){$chips[]=array("fa-tower-broadcast","{lgl_chip_sink}");}
    if($m["automount"]){$chips[]=array("fa-folder-tree","{lgl_chip_mount}");}
    $c="";
    foreach($chips as $ch){$c.="<span class='lgl-chip'><i class='fas {$ch[0]}' aria-hidden='true'></i>{$ch[1]}</span>";}
    return "<section class='lgl-card lgl-banner'><div class='lgl-bico' aria-hidden='true'><i class='fas fa-file-shield'></i></div>"
        ."<div class='lgl-btxt'><h2>{lgl_title}</h2><p>{lgl_text}</p><div class='lgl-chips'>$c</div></div></section>";
}

// 2a. Alerte : aucune copie hors de l'appliance.
function lgl_warn_local($tpl,array $m):string{
    if($m["external"]){return "";}
    $page=CurrentPageName();
    return "<div class='lgl-alert' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><p>{lgl_warn_local}</p>"
        ."<button type='button' class='lgl-btn warn' OnClick=\"Loadjs('$page?nas-squid-js=yes');\">{lgl_add_nas}</button></div>";
}

// 2b. Alerte : le daemon prevoit la saturation du disque des archives.
function lgl_warn_capacity($tpl,array $m):string{
    $c=$m["cap"];
    if($c===null || !isset($c->sufficient) || $c->sufficient){return "";}
    $date=isset($c->saturation_date) && $c->saturation_date!==""?lgl_day((string)$c->saturation_date):"&mdash;";
    return "<div class='lgl-alert red' role='alert'><i class='fas fa-hard-drive' aria-hidden='true'></i><p>"
        .lgl_t($tpl,"lgl_warn_full",array("<b>$date</b>"))."</p></div>";
}

// Une tuile de copie externe.
function lgl_tile(string $icon,string $title,string $sub,string $state,string $dest,string $help,string $js):string{
    $word=array("on"=>"{lgl_active}","off"=>"{lgl_off}","na"=>"{lgl_not_installed}","lb"=>"{lgl_by_lb}");
    $cls=$state=="on"?" on":($state=="off"?"":" na");
    $tag=$js!==""?"button type='button' OnClick=\"$js\"":"div";
    $end=$js!==""?"button":"div";
    $destHtml=$dest!==""?"<div class='dest'>".lgl_e($dest)."</div>":"";
    $setup=($state=="off" && $js!=="")?"<span class='lnk'>{lgl_setup}</span>":"";
    $subHtml=$sub!==""?"<div class='sub'>$sub</div>":"";
    return "<$tag class='lgl-tile$cls'><div class='top'><span class='nm'><i class='fas $icon' aria-hidden='true'></i>$title</span>"
        ."<span class='st'><span class='dot' aria-hidden='true'></span>{$word[$state]}</span></div>$subHtml$destHtml"
        ."<div class='hp'><span>$help</span>$setup</div></$end>";
}

// 3. Ou sont gardes les enregistrements : dossiers + trois copies possibles.
function lgl_where($tpl,array $m):string{
    $page=CurrentPageName();
    $h=array();
    $h[]="<section class='lgl-card'>".lgl_card_head("fa-hard-drive","{lgl_where_t}","Loadjs('$page?main-section-js=yes');");
    $h[]="<p class='lgl-lead'>{lgl_where_text}</p>";
    $h[]="<div class='lgl-folder'><div><div class='lbl'>{lgl_archive_folder}</div><div class='sub'>{lgl_prep_folder} <span class='mono'>".lgl_e($m["work_dir"])."</span></div></div>"
        ."<div class='mono big'>".lgl_e($m["backup_dir"])."</div></div>";
    $h[]="<div class='lgl-tiles'>";
    $h[]=lgl_tile("fa-server","{lgl_nas_t}","",$m["nas"]?"on":"off",$m["nas"]?$m["nas_dest"]:"","{lgl_nas_help}","Loadjs('$page?nas-squid-js=yes');");
    if($m["cluster"]){
        $h[]=lgl_tile("fa-tower-broadcast","{lgl_sink_t}","{logs_sink}","lb","","{lgl_lb_help}","");
    }else{
        $h[]=lgl_tile("fa-tower-broadcast","{lgl_sink_t}","{logs_sink}",$m["sink"]?"on":"off",$m["sink"]?$m["sink_dest"]:"","{lgl_sink_help}","Loadjs('$page?main-logsink-js=yes');");
    }
    if(!$m["autofs_installed"] && !$m["automount"]){
        $h[]=lgl_tile("fa-folder-tree","{lgl_mount_t}","{automount_center}","na","","{lgl_mount_help}","");
    }else{
        $h[]=lgl_tile("fa-folder-tree","{lgl_mount_t}","{automount_center}",$m["automount"]?"on":"off",$m["automount"]?$m["automount_dest"]:"","{lgl_mount_help}","Loadjs('$page?main-autofs-js=yes');");
    }
    $h[]="</div></section>";
    return @implode("",$h);
}

// 4. Quand les enregistrements sont archives.
function lgl_when($tpl,array $m):string{
    $page=CurrentPageName();
    if($m["only_schedule"]){
        $big=lgl_t($tpl,"lgl_when_daily",array($m["clock"]));
        $sub=lgl_t($tpl,"lgl_when_daily_help",array($m["size_mb"]));
    }else{
        $big=lgl_t($tpl,"lgl_when_size",array($m["size_mb"]));
        $sub="{lgl_when_size_help}";
    }
    return "<section class='lgl-card'>".lgl_card_head("fa-clock","{lgl_when_t}","Loadjs('$page?main-defaultsize-js=yes');")
        ."<div class='lgl-big'>$big</div><p class='lgl-help'>$sub</p>"
        ."<p class='lgl-note'><i class='fas fa-rotate' aria-hidden='true'></i>{lgl_when_reload}</p></section>";
}

// Anneau SVG : part du disque occupee (mesuree contre la taille reelle du volume).
function lgl_ring(float $pct):string{
    $pct=max(0,min(100,$pct));
    $r=26;$c=2*M_PI*$r;$on=round($c*$pct/100,2);$off=round($c-$on,2);
    $col=$pct>=90?"#dc2626":($pct>=75?"#d97706":"var(--lgl-accent)");
    return "<svg class='lgl-ring' width='64' height='64' viewBox='0 0 64 64' role='img' aria-label='".round($pct)."%'>"
        ."<circle cx='32' cy='32' r='$r' fill='none' stroke='#e5e7eb' stroke-width='8'/>"
        ."<circle cx='32' cy='32' r='$r' fill='none' stroke='$col' stroke-width='8' stroke-dasharray='$on $off' transform='rotate(-90 32 32)' stroke-linecap='round'/>"
        ."<text x='32' y='36' text-anchor='middle'>".round($pct)."%</text></svg>";
}

// 5c. Le disque des archives (GET /logs/capacity).
function lgl_disk($tpl,array $m):string{
    $c=$m["cap"];
    if($c===null){
        return "<div class='lgl-fact disk'><div class='lbl'>{lgl_disk_t}</div><div class='val'>&mdash;</div><p class='lgl-help'>{lgl_disk_none}</p></div>";
    }
    $used=isset($c->fs_used_pct)?floatval($c->fs_used_pct):-1;
    $free=isset($c->fs_free_bytes)?floatval($c->fs_free_bytes):-1;
    $total=isset($c->fs_total_bytes)?floatval($c->fs_total_bytes):-1;
    $ring=$used>=0?lgl_ring($used):"";
    $lines=array();
    if($free>=0 && $total>0){$lines[]=lgl_t($tpl,"lgl_disk_free",array(lgl_bytes($free),lgl_bytes($total)));}
    $ab=isset($c->archive_bytes)?floatval($c->archive_bytes):0;
    if($ab>0 && isset($c->oldest_day) && $c->oldest_day!==""){
        $lines[]=lgl_t($tpl,"lgl_disk_archives",array("<b>".lgl_bytes($ab)."</b>",lgl_day((string)$c->oldest_day)));
    }elseif($ab<=0){
        $lines[]=lgl_t($tpl,"lgl_disk_noarch");
    }
    if(isset($c->rate_avg_mb_per_day) && floatval($c->rate_avg_mb_per_day)>=0){
        $lines[]=lgl_t($tpl,"lgl_disk_rate",array(number_format(floatval($c->rate_avg_mb_per_day),1)));
    }
    $verdict="";
    if(isset($c->sufficient) && $c->sufficient && isset($c->required_gb) && floatval($c->required_gb)>=0){
        $verdict="<p class='lgl-ok'><i class='fas fa-circle-check' aria-hidden='true'></i>"
            .lgl_t($tpl,"lgl_disk_enough",array(isset($c->max_days)?intval($c->max_days):intval($m["max_days"]),number_format(floatval($c->required_gb),1)))."</p>";
    }
    return "<div class='lgl-fact disk'><div class='lbl'>{lgl_disk_t}</div><div class='lgl-disk'>$ring<div class='tx'>"
        .@implode("<br>",$lines)."</div></div>$verdict</div>";
}

// 5. Combien de temps les archives sont gardees.
function lgl_keep($tpl,array $m):string{
    $page=CurrentPageName();
    $ex=lgl_t($tpl,"lgl_keep_example",array($m["max_days_access"]));
    $other="";
    if($m["max_days"]!=$m["max_days_access"]){
        $other="<div class='lgl-fact'><div class='lbl'>{lgl_keep_other}</div><div class='val'>".lgl_t($tpl,"lgl_n_days",array($m["max_days"]))."</div>"
            ."<p class='lgl-help'>{lgl_keep_other_help}</p></div>";
    }
    return "<section class='lgl-card'>".lgl_card_head("fa-calendar-check","{lgl_keep_t}","Loadjs('$page?main-section-js=yes');")
        ."<div class='lgl-facts'>"
        ."<div class='lgl-fact'><div class='lbl'>{lgl_keep_proxy}</div><div class='val'>".lgl_t($tpl,"lgl_n_days",array($m["max_days_access"]))."</div>"
        ."<p class='lgl-help'>{lgl_keep_proxy_help}</p></div>$other"
        .lgl_disk($tpl,$m)
        ."</div><p class='lgl-help'>{lgl_keep_text}</p><div class='lgl-example'><b>{lgl_example}</b> $ex</div></section>";
}

// 6. Vie privee et protection.
function lgl_privacy($tpl,array $m):string{
    $page=CurrentPageName();
    $ips="";
    if(count($m["exceptions"])==0){
        $ips="<p class='lgl-empty'>{lgl_ex_none}</p>";
    }else{
        foreach($m["exceptions"] as $ip){$ips.="<span class='lgl-ip'>".lgl_e($ip)."</span>";}
        $ips="<div class='lgl-ips'>$ips</div>";
    }
    if($m["crypt"]){
        $st="<span class='lgl-state on'><i class='fas fa-lock' aria-hidden='true'></i>{lgl_crypt_on}</span>";
    }else{
        $st="<span class='lgl-state'><i class='fas fa-lock-open' aria-hidden='true'></i>{lgl_crypt_off}</span>";
    }
    $dec="";
    if($m["crypted_files"]){
        $dec="<div class='lgl-dec'><span>{lgl_crypted_files}</span><button type='button' class='lgl-link' OnClick=\"Loadjs('$page?decrypt-backups-js=yes');\">{decrypt_backups}</button></div>";
    }
    return "<section class='lgl-card'>".lgl_card_head("fa-user-shield","{lgl_priv_t}","Loadjs('$page?main-section-js=yes');")
        ."<div class='lgl-cols'><div><div class='lbl'>{lgl_ex_t}</div>$ips<p class='lgl-help'>{lgl_ex_help}</p></div>"
        ."<div><div class='lbl'>{lgl_crypt_t}</div>$st<p class='lgl-help'>{lgl_crypt_help}</p>$dec</div></div></section>";
}

// 7. Ou l'appliance ecrit ses journaux.
function lgl_varlog($tpl,array $m):string{
    $page=CurrentPageName();
    $path=$m["varlog_path"]!==""?lgl_e($m["varlog_path"]):"/var/log";
    $size=$m["varlog_kib"]>=0?" &middot; <b>".FormatBytes($m["varlog_kib"],true)."</b>":"";
    return "<section class='lgl-card lgl-varlog'><i class='fas fa-folder-open ico' aria-hidden='true'></i>"
        ."<div class='tx'><div class='t'>{lgl_varlog_t} <span class='mono pill'>$path$size</span></div><p class='lgl-help'>{lgl_varlog_help}</p></div>"
        ."<button type='button' class='lgl-btn' OnClick=\"Loadjs('$page?ChangeSystemLog-js=yes');\"><i class='fas fa-folder-tree' aria-hidden='true'></i>{lgl_move}</button></section>";
}

function main(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Conserve de l'ancienne page : desactive a chaque affichage le client et le
    // serveur de journaux legaux historiques (LegalLogArticaClient, LegallogServer),
    // que articarest lit encore (squid/legal_logs_client.go, logsink). Ne pas retirer
    // sans avoir d'abord retire ces lectures cote daemon.
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaClient",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegallogServer",0);
    $m=lgl_model();
    $html=array();
    $html[]="<div class='lgl'>";
    $html[]=lgl_banner($tpl,$m);
    $html[]=lgl_warn_capacity($tpl,$m);
    $html[]=lgl_warn_local($tpl,$m);
    $html[]=lgl_where($tpl,$m);
    $html[]=lgl_when($tpl,$m);
    $html[]=lgl_keep($tpl,$m);
    $html[]=lgl_privacy($tpl,$m);
    $html[]=lgl_varlog($tpl,$m);
    $html[]="</div>";

    $topbuttons=array();
    $export_logs_now=$tpl->framework_buildjs("/proxy/rotate","squid.rotate.progress","squid.rotate.progress.txt","progress-logrotate-restart");
    $topbuttons[]=array($export_logs_now,ico_export,"{export_logs_now}");
    $topbuttons[]=array("Loadjs('$page?import-js=yes')",ico_import,"{import}");
    if($m["crypted_files"]){
        $topbuttons[]=array("Loadjs('$page?decrypt-backups-js=yes')","fa-duotone fa-box-open","{decrypt_backups}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{legal_logs}";
    $TINY_ARRAY["ICO"]="fa fa-file-archive";
    $TINY_ARRAY["EXPL"]="{legal_logs_explain}";
    $TINY_ARRAY["URL"]="logs-rotate";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo lgl_style().$tpl->_ENGINE_parse_body(@implode("",$html))."<script>$jstiny</script>";
    return true;
}

function lgl_style():string{
    if(!empty($GLOBALS["LGL_CSS"])){return "";}
    $GLOBALS["LGL_CSS"]=true;
    $ac=array("accent"=>"#005447","accent_dark"=>"#003d33","accent_soft"=>"rgba(0,84,71,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return "<style>
.lgl{--lgl-accent:{$ac["accent"]};--lgl-dark:{$ac["accent_dark"]};--lgl-soft:{$ac["accent_soft"]};display:flex;flex-direction:column;gap:16px;max-width:960px;padding-top:8px;color:#0f172a;font-size:14px}
.lgl *{box-sizing:border-box}
.lgl-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.04);padding:20px}
.lgl-banner{display:flex;gap:16px;align-items:flex-start}
.lgl-bico{flex:0 0 44px;width:44px;height:44px;border-radius:10px;background:var(--lgl-soft);color:var(--lgl-accent);display:flex;align-items:center;justify-content:center;font-size:20px}
.lgl-btxt h2{margin:0 0 6px 0;font-size:19px;font-weight:600;color:#0f172a}
.lgl-btxt p{margin:0;font-size:14px;line-height:1.55;color:#475569}
.lgl-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.lgl-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;background:#f2f6f6;border:1px solid #e1ebe9;font-size:13px;color:#1e293b}
.lgl-chip i,.lgl-chip svg{color:var(--lgl-accent)}
.lgl-alert{display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#78350f}
.lgl-alert>i,.lgl-alert>svg{color:#d97706;font-size:18px;flex:0 0 auto}
.lgl-alert p{margin:0;flex:1;font-size:14px}
.lgl-alert.red{background:#fef2f2;border-color:#fecaca;color:#7f1d1d}
.lgl-alert.red>i,.lgl-alert.red>svg{color:#dc2626}
.lgl-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid #eef0f2}
.lgl-head h3{margin:0;font-size:18px;font-weight:600;display:flex;gap:10px;align-items:center;color:#0f172a}
.lgl-head h3 i,.lgl-head h3 svg{color:var(--lgl-accent)}
.lgl-btn{display:inline-flex;align-items:center;gap:7px;height:34px;padding:0 14px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#1e293b;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap}
.lgl-btn:hover{border-color:var(--lgl-accent);color:var(--lgl-dark)}
.lgl-btn.warn{background:#fef3c7;border-color:#fcd34d;color:#78350f;font-weight:600}
.lgl-btn:focus-visible,.lgl-tile:focus-visible,.lgl-link:focus-visible{outline:2px solid var(--lgl-accent);outline-offset:2px}
.lgl-lead{margin:0 0 14px 0;color:#475569}
.lgl-folder{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;margin-bottom:14px}
.lgl .lbl{font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;margin-bottom:4px}
.lgl-folder .sub{font-size:13px;color:#6b7280}
.lgl .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;overflow-wrap:anywhere}
.lgl .mono.big{font-size:15px;font-weight:600;color:#0f172a;text-align:right}
.lgl-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.lgl-tile{display:flex;flex-direction:column;gap:6px;text-align:left;padding:14px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;font:inherit;color:#334155;min-height:112px;min-width:0}
button.lgl-tile{cursor:pointer}
button.lgl-tile:hover{border-color:var(--lgl-accent)}
.lgl-tile.on{border-color:#bfd4d1;background:#f2f6f6}
.lgl-tile.na{background:#fafafa;color:#94a3b8}
.lgl-tile .top{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.lgl-tile .nm{display:flex;gap:8px;align-items:center;font-weight:600;font-size:14px;color:#0f172a}
.lgl-tile.na .nm{color:#64748b}
.lgl-tile .nm i,.lgl-tile .nm svg{color:#94a3b8}
.lgl-tile.on .nm i,.lgl-tile.on .nm svg{color:var(--lgl-accent)}
.lgl-tile .st{display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:500;color:#6b7280;white-space:nowrap}
.lgl-tile.on .st{color:var(--lgl-accent);font-weight:600}
.lgl-tile .dot{width:7px;height:7px;border-radius:50%;background:#cbd5e1}
.lgl-tile.on .dot{background:#10b981}
.lgl-tile .sub{font-size:13px;color:#94a3b8;margin-top:-4px}
.lgl-tile .dest{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:var(--lgl-dark);font-weight:600;overflow-wrap:anywhere}
.lgl-tile .hp{display:flex;justify-content:space-between;align-items:flex-end;gap:8px;margin-top:auto;font-size:13px;line-height:1.45}
.lgl-tile .lnk{color:var(--lgl-accent);font-weight:600;white-space:nowrap}
.lgl-big{font-size:22px;font-weight:600;color:#0f172a;margin-bottom:4px}
.lgl-help{margin:4px 0 0 0;font-size:13px;color:#6b7280;line-height:1.5}
.lgl-note{display:flex;gap:8px;align-items:center;margin:14px 0 0 0;padding-top:12px;border-top:1px solid #eef0f2;font-size:13px;color:#475569}
.lgl-note i,.lgl-note svg{color:#94a3b8}
.lgl-facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:12px}
.lgl-fact{padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;min-width:0}
.lgl-fact .val{font-size:22px;font-weight:600;color:#0f172a}
.lgl-fact.disk{grid-column:span 2}
.lgl-disk{display:flex;gap:14px;align-items:center;font-size:13px;color:#334155;line-height:1.6}
.lgl-ring{flex:0 0 64px}
.lgl-ring text{font-size:13px;font-weight:600;fill:#0f172a}
.lgl-ok{display:flex;gap:6px;align-items:center;margin:8px 0 0 0;font-size:13px;color:var(--lgl-dark)}
.lgl-example{padding:10px 14px;border-radius:8px;background:#f2f6f6;border:1px solid #e1ebe9;font-size:13px;color:#334155;margin-top:10px}
.lgl-cols{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.lgl-ips{display:flex;flex-wrap:wrap;gap:6px;margin:4px 0}
.lgl-ip{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;padding:3px 9px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;color:#0f172a;white-space:nowrap}
.lgl-empty{margin:4px 0;font-size:14px;color:#334155}
.lgl-state{display:inline-flex;align-items:center;gap:7px;padding:4px 11px;border-radius:999px;background:#f3f4f6;border:1px solid #e5e7eb;font-size:13px;font-weight:600;color:#475569;margin:4px 0}
.lgl-state.on{background:#f2f6f6;border-color:#bfd4d1;color:var(--lgl-dark)}
.lgl-dec{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:12px;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#475569}
.lgl-link{background:none;border:0;padding:0;color:var(--lgl-accent);font-weight:600;font-size:13px;cursor:pointer}
.lgl-link:hover{color:var(--lgl-dark);text-decoration:underline}
.lgl-varlog{display:flex;align-items:center;gap:14px;padding:16px 20px}
.lgl-varlog .ico{color:var(--lgl-accent);font-size:20px}
.lgl-varlog .tx{flex:1;min-width:0}
.lgl-varlog .t{font-weight:600;font-size:15px;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.lgl-varlog .pill{font-weight:400;padding:2px 8px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0}
@media (max-width:1200px){.lgl-tiles{grid-template-columns:1fr}.lgl-cols{grid-template-columns:1fr}.lgl-fact.disk{grid-column:auto}}
@media (prefers-reduced-motion:no-preference){.lgl-btn,.lgl-tile{transition:border-color .15s ease,color .15s ease}}
</style>";
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
//
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
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

	
	
	$SquidRotateAutomount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomount"));
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
    if(!$GLOBALS["CLASS_SOCKETS"]->INFO_EXISTS("SquidLogsExceptions")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogsExceptions", "127.0.0.1");}

	$disabled=false;
    $SquidRotateEnableCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateEnableCrypt"));






	$form[]=$tpl->field_text("LogRotatePath","{temporay_storage_path}",$LogRotatePath,false,"{temporay_storage_path_explain}");
	$form[]=$tpl->field_text("BackupMaxDaysDir","{backup_folder}",$BackupMaxDaysDir,false,"{BackupMaxDaysDir_explain}");


	// SquidRotateClean, BackupLogsMaxStoragePercent et SquidRotateForceRestart ne
	// sont lus par aucun code (articarest, artica-rotate) : la purge par age
	// (PurgeBackupMaxDays) s'applique toujours. Les champs ont ete retires.
	$form[]=$tpl->field_numeric("BackupMaxDays", "{lgl_f_maxdays}", $BackupMaxDays,"{lgl_f_maxdays_help}");
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