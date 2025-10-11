<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.autofs.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["InfluxAdminRetentionTime"])){Save();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["tab"])){tabs();exit;}

if(isset($_GET["clean-now"])){clean_now_js();exit;}
if(isset($_GET["clean-now-popup"])){clean_now_popup();exit;}
if(isset($_GET["clean-after-save"])){clean_after_save();exit;}
if(isset($_POST["CleanNow"])){$_SESSION["CleanNow"]=$_POST["CleanNow"];exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){search_events();exit;}
if(isset($_GET["js"])){js();exit;}

page();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{retentions}","$page?tab=yes&bypopup=yes",850);
}
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}
function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{retentions}","fa fa-archive",
        "{retentions_explain}","$page?tab=yes","logs-retentions",
    "progress-logrotate-restart",false,"table-loader");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{legal_logs}",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}
function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";

}


function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    $SystemEventsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemEventsRetentionTime"));
    $PDNSStatsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if($SystemEventsRetentionTime==0){$SystemEventsRetentionTime=15;}
    if($PDNSStatsRetentionTime==0){$PDNSStatsRetentionTime=5;}

    $LogsRotateDefaultSizeRotation=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation");
    if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $InfluxAdminRetentionTime=5;
        $SystemEventsRetentionTime=7;
        $PDNSStatsRetentionTime=5;
    }


    $form[]=$tpl->field_numeric("InfluxAdminRetentionTime","{StatisticsRetentionPeriod} ({general})",$InfluxAdminRetentionTime);
    $form[]=$tpl->field_numeric("PDNSStatsRetentionTime","{PDNSStatsRetentionTime} {days}",
        $PDNSStatsRetentionTime,"{PDNSStatsRetentionTime_explain}");


    $form[]=$tpl->field_section("{logs_retention_parameters}","{logs_retention_parameters_explain}");
    $form[]=$tpl->field_numeric("SystemEventsRetentionTime","{SystemEventsRetentionTime} ({days})",$SystemEventsRetentionTime);
    $form[]=$tpl->field_numeric("LogsRotateDefaultSizeRotation",
        "{remove_if_files_exceed} (MB)", $LogsRotateDefaultSizeRotation,null);

    $tpl->form_add_button("{clean_databases}","Loadjs('$page?clean-now=yes')");


    $html[]=$tpl->form_outside("",$form,null,"{apply}",null,"AsSystemAdministrator",true);

    if(!isset($_GET["bypopup"])) {
        $TINY_ARRAY["TITLE"] = "{statistics_retention_parameters}";
        $TINY_ARRAY["ICO"] = "fa fa-archive";
        $TINY_ARRAY["EXPL"] = "{statistics_retention_explain}";
        $TINY_ARRAY["URL"] = "logs-retentions";
        $TINY_ARRAY["BUTTONS"] = null;
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
        echo "<script>$jstiny</script>";
    }

    echo $tpl->_ENGINE_parse_body($html);

}
function clean_now_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{clean_databases}", "$page?clean-now-popup=yes",550);
}
function clean_now_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$InfluxAdminRetentionTime=5;}

    $jsrestart=$tpl->framework_buildjs("/postgresql/cleandb",
        "squid.statistics.purge.progress","squid.statistics.purge.progress.txt",
        "clean-database-progress","dialogInstance6.close();");


    $html[]="<div id='clean-database-progress'></div><div class='center style='margin:20px'>";
    $html[]=$tpl->button_autnonome("{purge_statistics_database} {$InfluxAdminRetentionTime} days",$jsrestart,ico_trash,"AsWebStatisticsAdministrator",450)."</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search_events(){
    $tpl        = new template_admin();
    $search     = trim(strtolower($_GET["search"]));
    $MAIN       = $tpl->format_search_protocol($search);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/clean-postgres.syslog";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("influx.php?retention-search=$line");


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    $data=explode("\n",@file_get_contents($tfile));
    rsort($data);

    foreach ($data as $line){
        if(!preg_match("#^(.*?)\s+(.*?)\s+([0-9:]+).*?clean-db\[([0-9]+)\]:.*?\{([0-9]+)\}\s+(.*)#",$line,$re)){continue;}
        $textclass="text-muted";
        $FTime=$re[1]." ".$re[2]. " ".$re[3];
        $pid=$re[4];
        $sline=$re[5];
        $events=$re[6];

        if(preg_match("#rows removed#i",$line,$re)){
            $textclass="text-success font-bold";
        }
        if(preg_match("#(error|fatal|failed)#i",$line,$re)){
            $textclass="text-danger font-bold";
        }
        if(preg_match("#(success|done)#i",$line,$re)){
            $textclass="text-muted font-bold";
        }
        $html[]="<tr>
				<td style='width:1%;' nowrap class='$textclass'>$FTime</td>
				<td style='width:1%;' nowrap class='$textclass'>$pid</td>
				<td class='$textclass'>[$sline] $events</td>
				</tr>";

    }

    $html[]="</tbody></table>";

    echo $tpl->_ENGINE_parse_body($html);

}



function tabs(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $bypopup="";
    if(isset($_GET["bypopup"])){
        $bypopup="&bypopup=yes";
    }
    $array["{parameters}"]="$page?parameters=yes$bypopup";
    $array["{events}"]="$page?events=yes$bypopup";
    echo "<div style='margin-top:10px'>";
    echo $tpl->tabs_default($array);
    echo "</div>";
}

function main(){
	$tpl=new template_admin();
	$AutoFSEnabled=1;
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	
	if($SquidPerformance>2){$AutoFSEnabled=0;}
	if($EnableIntelCeleron==1){$AutoFSEnabled=0;}

    $SquidRotateOnlySchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateOnlySchedule"));
	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	$BackupMaxDays=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	$LogRotateH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateH");
	$LogRotateM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateM");

	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=365;}
	

	
	if($LogRotateH==null){$LogRotateH="00";}
	if($LogRotateM==null){$LogRotateM="05";}
	
	$t=time();
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	
	$BackupSquidLogsUseNas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas");
	$BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	$SquidLogRotateFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}

	
	$BackupLogsMaxStoragePercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupLogsMaxStoragePercent"));
	
	if($BackupLogsMaxStoragePercent==0){$BackupLogsMaxStoragePercent=50;}
	$SquidRotateAutomount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomount"));
	$SquidRotateClean=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateClean"));
	$SquidRotateAutomountRes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountRes");
	$SquidRotateAutomountFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountFolder");
	
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	$BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASFolder2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder2");
	if($BackupSquidLogsNASFolder2==null){$BackupSquidLogsNASFolder2="artica-backup-syslog";}
	
	$SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
	$t=time();


	
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidLogsExceptions")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogsExceptions", "127.0.0.1");}
	
	
	$SquidLogsExceptions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogsExceptions");
	
	
	
	$AUTOFSR[null]="{select}";
	if($AutoFSEnabled==1){
		$autofs=new autofs();
		$hashZ=$autofs->automounts_Browse();
		if(file_exists('ressources/usb.scan.inc')){include("ressources/usb.scan.inc");}
		foreach ($hashZ as $localmount=>$array){$AUTOFSR[$localmount]="{$localmount}";}
	}
	
	
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
	

	$disabled=false;

	$form[]=$tpl->field_clock("RotateClock", "{schedule}", "$LogRotateH:$LogRotateM","");
    $form[]=$tpl->field_checkbox("SquidRotateOnlySchedule", "{only_by_schedule}",
            $SquidRotateOnlySchedule,false,"{only_by_schedule_squid_rotation_explain}");






	$form[]=$tpl->field_text("LogRotatePath","{temporay_storage_path}",$LogRotatePath,false,"{temporay_storage_path_explain}",$disabled);
	$form[]=$tpl->field_text("BackupMaxDaysDir","{backup_folder}",$BackupMaxDaysDir,false,"{BackupMaxDaysDir_explain}",$disabled);
	$form[]=$tpl->field_checkbox("SquidRotateAutomount", "{automount_ressource}", $SquidRotateAutomount,"SquidRotateAutomountFolder,SquidRotateAutomountFolder","{automount_ressource_explain}");
	$form[]=$tpl->field_array_hash($AUTOFSR,"SquidRotateAutomountRes","{resource}",$SquidRotateAutomountRes,false,null);
	$form[]=$tpl->field_text("SquidRotateAutomountFolder","{directory}",$SquidRotateAutomountFolder,false,"{SquidRotateAutomountFolder}",$disabled);
	$form[]=$tpl->field_checkbox("SquidRotateClean", "{clean_old_files}", $SquidRotateClean,false,"{clean_old_files_accesslog_explain}");
	$form[]=$tpl->field_numeric("BackupMaxDays", "{max_storage_days}", $BackupMaxDays,"{max_storage_days_log_explain}");
	$form[]=$tpl->field_numeric("BackupLogsMaxStoragePercent", "{max_percent_storage} (%)", $BackupLogsMaxStoragePercent,"{BackupLogsMaxStoragePercent_explain}");
	$form[]=$tpl->field_text("SquidLogsExceptions","{SquidLogsExceptions}",$SquidLogsExceptions,false,"{SquidLogsExceptions_explain}");
	
	
	$users=new usersMenus();
	if($users->APP_DAVSERVER){
		$WebDavSquidLogsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsEnabled"));
		$WebDavSquidLogsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsPort"));
		$WebDavSquidLogsInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsInterface");
		$WebDavSquidLogsUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsUsername");
		$WebDavSquidLogsPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsPassword");
		$WebDavSquidLogsAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsInterface"));
		if($WebDavSquidLogsPort==0){$WebDavSquidLogsPort=8008;}
		$form[]=$tpl->field_section("{access_to_storage}","{access_to_storage_text}");
		$form[]=$tpl->field_checkbox("{enable_webdav_service}","WebDavSquidLogsEnabled",$WebDavSquidLogsEnabled,"WebDavSquidLogsAuth,WebDavSquidLogsPort,WebDavSquidLogsInterface,WebDavSquidLogsUsername,WebDavSquidLogsPassword");
		$form[]=$tpl->field_numeric("WebDavSquidLogsPort", "{listen_port}", $WebDavSquidLogsPort);
		$form[]=$tpl->field_interfaces("WebDavSquidLogsInterface","{interface}",$WebDavSquidLogsInterface);
		$form[]=$tpl->field_checkbox("{EnableAuth}","WebDavSquidLogsAuth",$WebDavSquidLogsAuth,"WebDavSquidLogsUsername,WebDavSquidLogsPassword");
		$form[]=$tpl->field_text("WebDavSquidLogsUsername","{username}",$WebDavSquidLogsUsername,false);
		$form[]=$tpl->field_password("WebDavSquidLogsPassword","{password}",$WebDavSquidLogsPassword,false);
	}



	
	$form[]=$tpl->field_section("{NAS_storage}","{log_retention_nas_text}");

	$form[]=$tpl->field_checkbox("BackupSquidLogsUseNas","{use_remote_nas}",$BackupSquidLogsUseNas,"BackupSquidLogsNASIpaddr,BackupSquidLogsNASFolder,BackupSquidLogsNASFolder2,BackupSquidLogsNASUser,BackupSquidLogsNASPassword","{BackupSquidLogsUseNas_explain}");
	$form[]=$tpl->field_text("BackupSquidLogsNASIpaddr","{hostname}",$BackupSquidLogsNASIpaddr,false,"",$disabled);
	$form[]=$tpl->field_text("BackupSquidLogsNASFolder","{shared_folder}",$BackupSquidLogsNASFolder,false,"",$disabled);
	$form[]=$tpl->field_text("BackupSquidLogsNASFolder2","{storage_directory}",$BackupSquidLogsNASFolder2,false,"",$disabled);
	$form[]=$tpl->field_text("BackupSquidLogsNASUser","{username}",$BackupSquidLogsNASUser,false,"",$disabled);
	$form[]=$tpl->field_password("BackupSquidLogsNASPassword","{password}",$BackupSquidLogsNASPassword,false,"",$disabled);
	
	


	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.nas.storage.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.nas.storage.progress.txt";
	$ARRAY["CMD"]="squid.php?nas-storage-progress=yes";
	$ARRAY["TITLE"]="{test_connection}::{use_remote_nas}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsTestNas="Loadjs('fw.progress.php?content=$prgress&mainid=progress-logrotate-restart')";
	
    $export_logs_now=$tpl->framework_buildjs("/proxy/rotate","squid.rotate.progress","squid.rotate.progress.txt","progress-logrotate-restart");
	
	$form[]=$tpl->form_add_button("{NAS_storage}:{test_connection}",$jsTestNas);
	$form[]=$tpl->form_add_button("{export_logs_now}",$export_logs_now);

    $js[]="if(typeof dialogInstance1 == 'object'){ dialogInstance1.close();}";
    $js[]="if ( document.getElementById('artica-backup-parameters-static') ){";
    $js[]="LoadAjax('artica-backup-parameters-static','fw.artica.backup.php?parameters2=yes');";
    $js[]="}";
	
	$tpl->form_privileges="AsSquidAdministrator";
    $html[]="<div style='margin-top:20Px'>&nbsp;</div>";
	$html[]=$tpl->form_outside("{log_retention}", $form,"{squid_backuped_logs_explain}","{apply}",@implode("",$js),"AsSquidAdministrator",true);

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}