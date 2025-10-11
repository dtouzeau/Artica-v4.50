<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["services-status"])){status_rotation();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["patterns"])){patterns();exit;}
if(isset($_GET["patterns2"])){patterns2();exit;}
if(isset($_POST["ClamavStreamMaxLength"])){save();exit;}
if(isset($_POST["ConcurrentDatabaseReload"])){save();exit;}
if(isset($_POST["ClamavRefreshDaemonMemory"])){save();exit;}
if(isset($_POST["remove-clamav"])){remove_clamav_confirm();exit;}
if(isset($_GET["upload-js"])){upload_js();exit;}
if(isset($_GET["upload-popup"])){upload_popup();exit;}
if(isset($_GET["file-uploaded"])){uploaded();exit;}
if(isset($_GET["remove-clamav"])){remove_clamav_js();exit;}
if(isset($_GET["clamav-status-parameters"])){clamav_status_parameters();exit;}
if(isset($_GET["section-main-js"])){section_main_js();exit;}
if(isset($_GET["section-main-popup"])){section_main_popup();exit;}
if(isset($_GET["section-watchdog-js"])){section_watchdog_js();exit;}
if(isset($_GET["section-watchdog-popup"])){section_watchdog_popup();exit;}
if(isset($_GET["section-ConcurrentDatabaseReload-js"])){section_ConcurrentDatabaseReload_js();exit;}
if(isset($_GET["section-ConcurrentDatabaseReload-popup"])){section_ConcurrentDatabaseReload_popup();exit;}
if(isset($_GET["uninstall-confirm-js"])){uninstall_confirm_js();exit;}
if(isset($_POST["uninstall"])){uninstall_confirm_perform();exit;}
page();


function upload_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{upload_signatures}","$page?upload-popup=yes");
}
function section_main_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{parameters}","$page?section-main-popup=yes");
}
function section_watchdog_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{watchdog}","$page?section-watchdog-popup=yes");
}
function section_ConcurrentDatabaseReload_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog("{ConcurrentDatabaseReload}","$page?section-ConcurrentDatabaseReload-popup=yes");
}


function remove_clamav_js(){
    $tpl=new template_admin();
    $js="document.location.href='/packages-center'";
    $progress=$tpl->framework_buildjs("clamav.php?remove-all=yes","clamd.progress","clamd.log","progress-clamav-restart",$js);
    $tpl->js_confirm_execute("{uninstall_text}","remove-clamav","none",$progress);

}
function remove_clamav_confirm(){
    admin_tracks("Uninstall clamav software (emergency mode)");
}

function uploaded(){
    $page=CurrentPageName();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/clamav.update.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/clamav.update.progress.txt";
    $ARRAY["CMD"]="clamav.php?manual-update=$fileencode";
    $ARRAY["TITLE"]="{upload_signatures}";
    $ARRAY["AFTER"]="LoadAjax('table-clamav','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $clamd_restart="Loadjs('fw.progress.php?content=$prgress&mainid=clamav-upload-pattern')";

    echo $clamd_restart;

}

function upload_popup(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]=$tpl->div_explain("{CLAMAV_UPLOAD_EXPLAIN}");
    $html[]="<div id='clamav-upload-pattern'></div>";
    $html[]="<div class='center' style='margin:30px'>";
    $html[]=$tpl->button_upload("{upload file} (zip)",$page);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");


    $html=$tpl->page_header("{APP_CLAMAV} $ClamAVDaemonVersion","fab fa-medrt",
        "{APP_CLAMAV_TEXT}","$page?tabs=yes","clamav","progress-clamav-restart",false,"table-clamav");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: ClamAV Parameters",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{service_status}"]="$page?table=yes";
	$array["{patterns_versions}"]="$page?patterns=yes";
	$array["{schedules}"]="fw.system.tasks.php?microstart=yes&ForceTaskType=81";
	$array["{service_events}"]="fw.clamav.clamd.events.php";
	$array["{update_events}"]="fw.clamav.freshclam.events.php";
	
	echo $tpl->tabs_default($array);
}


//ConcurrentDatabaseReload
function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();
	$EnableClamavSecuriteInfo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavSecuriteInfo"));
	$EnableClamavUnofficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavUnofficial"));

    $EnableClamavDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon"));
    VERBOSE("EnableClamavDaemon = $EnableClamavDaemon",__LINE__);

    $used_databases=0;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/databases"));
    if(property_exists($json,"databases")){
        if(!is_null($json->databases)) {
            $used_databases = count($json->databases);
        }
    }
    if($used_databases==0){
		$w_used_databases=$tpl->widget_h("red",ico_emergency,"$used_databases/45","{used_databases}");
		echo $tpl->FATAL_ERROR_SHOW_128("{missing_clamav_pattern_databases}");
	}else{
		if($used_databases<4){
			$w_used_databases=$tpl->widget_h("yellow","fas fa-database","$used_databases/45","{used_databases}");
		}else{
			$w_used_databases=$tpl->widget_h("green","fas fa-database","$used_databases/45","{used_databases}");
		}
	}
	
	
	
	
	if($EnableClamavUnofficial==1){
		$StatsUnofficial=$tpl->widget_h("green","fas fa-thumbs-up","{active2}","{clamav_unofficial}");
	}else{
		$StatsUnofficial=$tpl->widget_h("grey","fas fa-thumbs-down","{inactive}","{clamav_unofficial}");
	}
	if($EnableClamavSecuriteInfo==1){
		$StatsSecuriteInfo=$tpl->widget_h("green","fas fa-thumbs-up","{active2}","{securiteinfo_antivirus_databases}");
	}else{
		$StatsSecuriteInfo=$tpl->widget_h("grey","fas fa-thumbs-down","{inactive}","{securiteinfo_antivirus_databases}");
	}
	


	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align:top'>";
	$html[]="	<table style='width:100%'>";
	$html[]="<tr>";
    $html[]="<td>";
    $html[]="<div class=\"ibox\" style='border-top:0'>";
    $html[]="<div class=\"ibox-content\" style='border-top:0' id='services-status'>";

    $html[]="</div>";
    $html[]="</div>";
    $html[]="</td>";
    $html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";

	$html[]="<td style='vertical-align:top'>
	<table style='width:100%'>
	<tr><td style='width:33%;padding:10px;'>$StatsSecuriteInfo</td>
	<td style='width:33%;padding:10px;'>$StatsUnofficial</td>
	<td style='width:33%;padding:10px;'>$w_used_databases</td>
	</tr>
	</table>";

    $html[]="<div id='clamav-status-parameters'></div>";


	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";

    $ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");
    $TINY_ARRAY["TITLE"]="{APP_CLAMAV} $ClamAVDaemonVersion";
    $TINY_ARRAY["ICO"]="fab fa-medrt";
    $TINY_ARRAY["EXPL"]="{APP_CLAMAV_TEXT}";
    $TINY_ARRAY["URL"]="clamav";


    $freshclam_update=$tpl->framework_buildjs(
        "/freshclam/run",
        "clamav.update.progress",
        "clamav.update.progress.txt",
        "progress-clamav-restart","LoadAjaxSilent('table-clamav','$page?tabs=yes')"

    );

    $topbuttons[] = array("Loadjs('$page?uninstall-confirm-js=yes');",ico_trash,"{uninstall}");
    $topbuttons[] = array($freshclam_update,ico_download,"{udate_clamav_databases}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);


    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $js=$tpl->RefreshInterval_js("services-status",$page,"services-status=yes");
    $html[]="<script>";

    $html[]="LoadAjaxSilent('clamav-status-parameters','$page?clamav-status-parameters=yes');";
    $html[]=$jstiny;
    $html[]=$js;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function status_rotation(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/status"));
    $ini=new Bs_IniHandler();

    $clamd_restart=$tpl->framework_buildjs("/clamd/restart",
        "clamd.restart","clamd.restart.logs","progress-clamav-restart");

    $uninstall=$tpl->button_autnonome("{remove_software}",
        "Loadjs('$page?remove-clamav=yes')",
        "fad fa-ban",
        "AsSystemAdministrator",
        335,"btn-danger"

    );

    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
        return false;

    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
            return false;
        } else {
            $ini->loadString($json->Info);
            $html[]=$tpl->SERVICE_STATUS($ini, "APP_CLAMAV",$clamd_restart);
        }
    }
    $APP_FRESHCLAM_STATUS=null;

    $main[0]["name"]="Youtube";
    $main[0]["js"]="s_PopUpFull('https://youtu.be/lfHMhnw8nhY','1024','900');";
    $main[0]["icon"]="fab fa-youtube";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FRESHCLAM_INSTALLED"))==0){
        $APP_FRESHCLAM_STATUS=$tpl->widget_rouge("{APP_FRESHCLAM_NOT_INSTALLED_EXPLAIN}","{APP_FRESHCLAM_NOT_INSTALLED}",$main);
    }

    $freshclam_restart=$tpl->framework_buildjs("/freshclam/restart",
        "clamav.freshclam.progress","clamav.freshclam.progress.logs","progress-clamav-restart");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/freshclam/status"));
    $ini=new Bs_IniHandler();
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
        return false;

    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
            return false;
        } else {
            $ini->loadString($json->Info);
            $html[]=$tpl->SERVICE_STATUS($ini, "APP_FRESHCLAM",$freshclam_restart);
        }
    }


    $html[]=$APP_FRESHCLAM_STATUS;
    $html[]=$uninstall;
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function save():bool{
	$tpl=new template_admin();

    if(isset($_POST["ClamavRefreshDaemonMemory"])){
        if(intval($_POST["ClamavRefreshDaemonMemory"])>0){
            if($_POST["ClamavRefreshDaemonMemory"]<1500){
                $_POST["ClamavRefreshDaemonMemory"]=1500;
            }
        }
    }


    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/reconfigure");
    return true;
}

function patterns(){
    $page=CurrentPageName();
    echo "<div id='patterns-version'></div>
<script>LoadAjaxSilent('patterns-version','$page?patterns2=yes');</script>";
}

function patterns2(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	
    $freshclam_update=$tpl->framework_buildjs(
        "/freshclam/run",
        "clamav.update.progress",
        "clamav.update.progress.txt",
        "progress-clamav-restart","LoadAjaxSilent('patterns-version','$page?patterns2=yes')"

    );


    $jsrestart=$tpl->framework_buildjs("/clamd/sigtooldb",
        "clamav.status.db.progress","clamav.status.db.log","progress-clamav-restart","LoadAjaxSilent('patterns-version','$page?patterns2=yes')"
    );


    $topbuttons[] = array($freshclam_update, ico_download, "{udate_clamav_databases}");
    $topbuttons[] = array($jsrestart, ico_retweet, "{synchronize}");
    $topbuttons[] = array("Loadjs('$page?upload-js=yes');", ico_upload, "{upload}");

	
	$html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{databases}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' style='width:1%'>{version}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='number' style='width:1%'>{signatures}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
	$TRCLASS=null;
	$results=$q->QUERY_SQL("SELECT * FROM pattern_status ORDER BY patterndate");
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$date=$ligne["patterndate"];
		$version=$ligne["version"];
		$signatures=FormatNumber($ligne["signatures"]);
		$time=strtotime($date);
		$distance=distanceOfTimeInWords($time,time());
		$dbname=$ligne["dbname"];
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' id='$index'><strong>$dbname</strong></td>";
		$html[]="<td style='width:99%' nowrap>$date ($distance)</td>";
		$html[]="<td style='width:1%'>$version</td>";
		$html[]="<td style='width:1%' nowrap>$signatures</td>";
		$html[]="</tr>";
		
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{patterns_versions}";
    $TINY_ARRAY["ICO"]="fab fa-medrt";
    $TINY_ARRAY["EXPL"]="{clamav_antivirus_databases_explain}";
    $TINY_ARRAY["URL"]="clamav";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	$jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function clamav_status_parameters():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $hoursEX[0]="{never}";
    $hoursEX[15]="15 {minutes}";
    $hoursEX[30]="30 {minutes}";
    $hoursEX[60]="1 {hour}";
    $hoursEX[120]="2 {hours}";
    $hoursEX[180]="3 {hours}";
    $hoursEX[420]="4 {hours}";
    $hoursEX[480]="8 {hours}";

    $Timez[5]="5 {minutes}";
    $Timez[10]="10 {minutes}";
    $Timez[15]="15 {minutes}";
    $Timez[30]="30 {minutes}";
    $Timez[60]="1 {hour}";
    $Timez[120]="2 {hours}";
    $Timez[180]="3 {hours}";
    $Timez[360]="6 {hours}";
    $Timez[720]="12 {hours}";
    $Timez[1440]="1 {day}";
    $Timez[2880]="2 {days}";

    $ClamavRefreshDaemonTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonTime"));
    $ClamavRefreshDaemonMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonMemory"));

    $ClamavStreamMaxLength=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavStreamMaxLength"));
    if($ClamavStreamMaxLength==null){$ClamavStreamMaxLength="12";}

    $ClamavMaxFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxFileSize"));
    if($ClamavMaxFileSize==0){$ClamavMaxFileSize=12;}

    $ClamavMaxScanSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxScanSize"));
    if($ClamavMaxScanSize==0){$ClamavMaxScanSize=15;}


    $ClamavMaxRecursion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxRecursion"));
    if($ClamavMaxRecursion==0){$ClamavMaxRecursion=5;}

    $ClamavMaxFiles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxFiles"));
    if($ClamavMaxFiles==0){$ClamavMaxFiles=1000;}

    $ConcurrentDatabaseReload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConcurrentDatabaseReload"));


    $PhishingScanURLs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhishingScanURLs"));
    $EnableClamavSecuriteInfo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavSecuriteInfo"));
    $EnableClamavSecuriteSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavSecuriteSchedule"));
    $EnableClamavUnofficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavUnofficial"));
    if($EnableClamavSecuriteSchedule==0){$EnableClamavSecuriteSchedule=360;}

    $lim=array();
    if($ClamavRefreshDaemonTime>0){
        $lim[]="{srv_clamav.RefreshDaemon} $hoursEX[$ClamavRefreshDaemonTime]";
    }
    if($ClamavRefreshDaemonMemory>0){
        $lim[]="{refresh_daemon_MB} {$ClamavRefreshDaemonMemory}MB";
    }



    $tpl->table_form_field_js("Loadjs('$page?section-watchdog-js=yes')","AsSystemAdministrator");

    if(count($lim)>0) {
        $tpl->table_form_field_text("{watchdog}: {memory_usage}",
            @implode(", ",$lim), ico_watchdog);
    }else{
        $tpl->table_form_field_bool("{watchdog}: {memory_usage}",0,ico_watchdog);
    }


    $lim=array();
    $lim[]="{srv_clamav.StreamMaxLength} {$ClamavStreamMaxLength}MB";
    $lim[]="{srv_clamav.MaxObjectSize} {$ClamavMaxFileSize}MB";
    $lim[]="{srv_clamav.MaxFileSize} {$ClamavMaxFileSize}MB";
    $lim[]="{srv_clamav.MaxScanSize} {$ClamavMaxScanSize}MB";

    $tpl->table_form_field_js("Loadjs('$page?section-ConcurrentDatabaseReload-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{ConcurrentDatabaseReload}",$ConcurrentDatabaseReload,ico_memory);

    $tpl->table_form_field_js("Loadjs('$page?section-main-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{memory_usage} {limits}",@implode(", ",$lim),ico_memory);



    $lim=array();
    $lim[]="{srv_clamav.ClamAvMaxFilesInArchive} $ClamavMaxFiles";
    $lim[]="{srv_clamav.ClamAvMaxRecLevel} $ClamavMaxRecursion";

    $tpl->table_form_field_text("{limits}",@implode(" ",$lim),ico_memory);

    $tpl->table_form_section("{features}");
    $tpl->table_form_field_bool("{srv_clamav.PhishingScanURLs}",$PhishingScanURLs,ico_earth);
    $tpl->table_form_field_bool("{clamav_unofficial}",$EnableClamavUnofficial,ico_database);
    if($EnableClamavSecuriteInfo==0){
        $tpl->table_form_field_bool("{securiteinfo_antivirus_databases}",$EnableClamavSecuriteInfo,ico_database);
    }else{
        $tpl->table_form_field_text("{securiteinfo_antivirus_databases}","{update_each} $Timez[$EnableClamavSecuriteSchedule]",ico_database);
    }

    echo $tpl->table_form_compile();
return true;

}

function section_js_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsinstall=$tpl->framework_buildjs("/clamd/reconfigure",
        "clamd.progress",
        "clamd.progress.logs",
        "progress-clamav-restart"
    );

    return "BootstrapDialog1.close();LoadAjaxSilent('clamav-status-parameters','$page?clamav-status-parameters=yes');;$jsinstall";
}

function uninstall_confirm_js():bool{

    $tpl=new template_admin();
    $uninstall=$tpl->framework_buildjs("/clamd/uninstall",
        "clamd.progress","clamd.progress.logs","progress-clamav-restart","document.location.href='/index';");
    return $tpl->js_confirm_execute("{uninstall}: {APP_CLAMAV}", "uninstall", "yes",$uninstall);

}
function uninstall_confirm_perform():bool{
    admin_tracks("Uninstall Clamav Daemon service");
    return true;
}

function section_watchdog_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ClamavRefreshDaemonTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonTime"));
    $ClamavRefreshDaemonMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonMemory"));

    $hoursEX[0]="{never}";
    $hoursEX[15]="15 {minutes}";
    $hoursEX[30]="30 {minutes}";
    $hoursEX[60]="1 {hour}";
    $hoursEX[120]="2 {hours}";
    $hoursEX[180]="3 {hours}";
    $hoursEX[420]="4 {hours}";
    $hoursEX[480]="8 {hours}";

    $form[]=$tpl->field_array_hash($hoursEX, "ClamavRefreshDaemonTime", "{srv_clamav.RefreshDaemon}", $ClamavRefreshDaemonTime,false,"{srv_clamav.RefreshDaemon_text}");
    $form[]=$tpl->field_numeric("ClamavRefreshDaemonMemory","{refresh_daemon_MB} (M)",$ClamavRefreshDaemonMemory,"{srv_clamav.ClamavRefreshDaemonMemory}");

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjaxSilent('clamav-status-parameters','$page?clamav-status-parameters=yes');",
        "AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
}

function section_ConcurrentDatabaseReload_popup():bool{
    $tpl=new template_admin();
    $ConcurrentDatabaseReload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConcurrentDatabaseReload"));

    $form[]=$tpl->field_section("{feature}:{ConcurrentDatabaseReload}","{ConcurrentDatabaseReload_text}");
    $form[]=$tpl->field_checkbox("ConcurrentDatabaseReload","{enable}",$ConcurrentDatabaseReload,false);
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",section_js_form(),
        "AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_main_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();



    $Timez[5]="5 {minutes}";
    $Timez[10]="10 {minutes}";
    $Timez[15]="15 {minutes}";
    $Timez[30]="30 {minutes}";
    $Timez[60]="1 {hour}";
    $Timez[120]="2 {hours}";
    $Timez[180]="3 {hours}";
    $Timez[360]="6 {hours}";
    $Timez[720]="12 {hours}";
    $Timez[1440]="1 {day}";
    $Timez[2880]="2 {days}";



    $ClamavStreamMaxLength=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavStreamMaxLength"));
    if($ClamavStreamMaxLength==null){$ClamavStreamMaxLength="12";}

    $ClamavMaxFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxFileSize"));
    if($ClamavMaxFileSize==0){$ClamavMaxFileSize=12;}

    $ClamavMaxScanSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxScanSize"));
    if($ClamavMaxScanSize==0){$ClamavMaxScanSize=15;}


    $ClamavMaxRecursion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxRecursion"));
    if($ClamavMaxRecursion==0){$ClamavMaxRecursion=5;}

    $ClamavMaxFiles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavMaxFiles"));
    if($ClamavMaxFiles==0){$ClamavMaxFiles=1000;}


    $PhishingScanURLs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhishingScanURLs"));
    $EnableClamavSecuriteInfo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavSecuriteInfo"));
    $EnableClamavSecuriteSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavSecuriteSchedule"));
    $EnableClamavUnofficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavUnofficial"));
    if($EnableClamavSecuriteSchedule==0){$EnableClamavSecuriteSchedule=360;}



    $form[]=$tpl->field_numeric("ClamavStreamMaxLength","{srv_clamav.StreamMaxLength} (M)",$ClamavStreamMaxLength,"{srv_clamav.StreamMaxLength_text}");
    $form[]=$tpl->field_numeric("ClamavMaxFileSize","{srv_clamav.MaxObjectSize} (M)",$ClamavMaxFileSize,"{srv_clamav.MaxObjectSize_text}");
    $form[]=$tpl->field_numeric("ClamavMaxScanSize","{srv_clamav.MaxScanSize} (M)",$ClamavMaxScanSize,"{srv_clamav.MaxScanSize_text}");
    $form[]=$tpl->field_numeric("ClamavMaxFiles","{srv_clamav.ClamAvMaxFilesInArchive}",$ClamavMaxFiles,"{srv_clamav.ClamAvMaxFilesInArchive}");
    $form[]=$tpl->field_numeric("MaxFileSize","{srv_clamav.MaxFileSize} (M)",$ClamavMaxFileSize);
    $form[]=$tpl->field_numeric("ClamavMaxRecursion","{srv_clamav.ClamAvMaxRecLevel} (M)",$ClamavMaxRecursion);
    $form[]=$tpl->field_checkbox("PhishingScanURLs","{srv_clamav.PhishingScanURLs}",$PhishingScanURLs,false,"{srv_clamav.PhishingScanURLs_text}");


    $form[]=$tpl->field_checkbox("EnableClamavSecuriteInfo","{securiteinfo_antivirus_databases}",$EnableClamavSecuriteInfo,false,"{securite_info_explain}");
    $form[]=$tpl->field_array_hash($Timez, "EnableClamavSecuriteSchedule", "{update_each}", $EnableClamavSecuriteSchedule,"{srv_clamav.RefreshDaemon_text}");



    $form[]=$tpl->field_checkbox("EnableClamavUnofficial","{enable_clamav_unofficial}",$EnableClamavUnofficial,false,"{clamav_unofficial_text}");

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",section_js_form(),
        "AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'):string{$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}