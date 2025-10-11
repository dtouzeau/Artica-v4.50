<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["params-general-js"])){params_general_js();exit;}
if(isset($_GET["params-general-popup"])){params_general_popup();exit;}

if(isset($_GET["params-monit-js"])){params_monit_js();exit;}
if(isset($_GET["params-monit-popup"])){params_monit_popup();exit;}


if(isset($_POST["EnableMsftncsi"])){Save();exit;}
if(isset($_POST["MonitReportLoadVG1mn"])){Save();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["settings2"])){settings2();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["incidents"])){incidents_search();exit;}
if(isset($_GET["search-incidents"])){incidents_found();exit;}
if(isset($_GET["incident-page"])){incidents_page();exit;}
if(isset($_GET["incident-download"])){incident_download();exit;}
if(isset($_GET["incident-delete"])){incident_delete_js();exit;}
if(isset($_POST["incident-delete"])){incident_delete();exit;}
if(isset($_POST["remove-all-incidents"])){incidents_remove_perform();exit;}
if(isset($_GET["remove-all-incidents-js"])){incidents_remove_js();exit;}

if(isset($_GET["reboot-js"])){reboot_js();exit;}
if(isset($_GET["reboot"])){reboot_params();exit;}
if(isset($_POST["DailyReboot"])){reboot_save();exit;}
if(isset($_GET["monitoring-js"])){monitoring_js();exit;}
if(isset($_GET["monitoring-popup"])){monitoring_popup();exit;}
if(isset($_POST["MonitMonitoring"])){monitoring_save();exit;}
page();

function incidents_remove_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
   return $tpl->js_confirm_delete("{incidents}","remove-all-incidents","yes","$function()");
}
function incidents_remove_perform():bool{
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
    $q->QUERY_SQL("DELETE FROM perfs_queue");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }
    return admin_tracks("Removed all records from the incidents section");
}
function params_general_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{parameters}","$page?params-general-popup=yes",650);
}
function params_monit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{parameters}","$page?params-monit-popup=yes",650);
}
function reboot_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{DAILY_REBOOT}","$page?reboot=yes",650);
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Saving Watchdog parameters");
}
function monitoring_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->getFrameWork("artica.php?restart-webconsole-wait=yes");
    return admin_tracks_post("Saving watchdog for monitoring page");
}
function incident_delete_js(){
    $tpl=new template_admin();
    $time=$_GET["incident-delete"];
    $tpl->js_confirm_delete("{report} $time","incident-delete",$time,"$('#$time').remove()");
}
function monitoring_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{monitoring_page}","$page?monitoring-popup=yes");
}
function monitoring_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MonitMonitoring = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMonitoring"));
    $MonitMonitoringNetwork = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMonitoringNetwork"));
    if ($MonitMonitoringNetwork == null) {
        $MonitMonitoringNetwork = "192.168.0.0/16\n10.0.0.0/8\n172.16.0.0/12";
    }

    $form[]=$tpl->field_checkbox("MonitMonitoring","{enable_feature}",$MonitMonitoring);
    $form[]=$tpl->field_textareacode("MonitMonitoringNetwork","{allowed_network}",$MonitMonitoringNetwork);
    echo $tpl->form_outside(null,$form,null,"{apply}","LoadAjax('monit-params','$page?settings2=yes');dialogInstance1.close();","AsSystemAdministrator");
    return true;
}
function incident_delete():bool{
    $time=$_POST["incident-delete"];
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
    $sql="DELETE FROM perfs_queue WHERE zDate='$time'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Removed record $time from the incidents section");
}
function incidents_page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{incidents}",
        "fa fa-bell","{system_health_checking_explain}",
        "$page?incidents=yes","incidents","progress-monit-restart",false,"table-loader-monit");




    $tpl=new template_admin(null,$html);
    echo $tpl->build_firewall();
    return true;


}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{system_health_checking}",
        "fas fa-dog","{system_health_checking_explain}",
        "$page?tabs=yes","system-watchdog","progress-monit-restart",false,"table-loader-monit");



if(isset($_GET["main-page"])){
    $tpl=new template_admin(null,$html);
    echo $tpl->build_firewall();
    return true;
}

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $array["{parameters}"] = "$page?settings=yes";
    $array["{incidents}"] = "$page?incidents=yes";
    $array["{events}"] = "fw.system.monit.events.php";

    echo $tpl->tabs_default($array);
}

function reboot_params():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $DailyReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DailyReboot"));
    $DailyRebootHour=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DailyRebootHour"));
    if($DailyRebootHour==null){$DailyRebootHour="06:00:00";}

    $form[]=$tpl->field_checkbox("DailyReboot","{scheduled_reboot}",$DailyReboot,true);
    $form[]=$tpl->field_clock("DailyRebootHour","{time}",$DailyRebootHour);
    echo $tpl->form_outside("{DAILY_REBOOT_SERVER}",$form,"{DAILY_REBOOT_SERVER_EXPLAIN}","{apply}","LoadAjax('monit-params','$page?settings2=yes');dialogInstance1.close();","AsSystemAdministrator");
    return true;
}

function reboot_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/schedule/reboot"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    
    return admin_tracks_post("Saving system daily reboot");
}
function settings():bool{
    $page=CurrentPageName();
    echo "<div id='monit-params'></div>
    <script>LoadAjax('monit-params','$page?settings2=yes');</script>
    ";
    return true;
}

function params_general_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMsftncsi"));
    $systemMaxOverloaded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("systemMaxOverloaded"));
    if($systemMaxOverloaded==0){$systemMaxOverloaded=17;}

    $systemctlDefuncReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("systemctlDefuncReboot"));

    if($systemctlDefuncReboot==0){
        $systemctlDefuncReboot=300;
    }

    $form[]=$tpl->field_numeric("systemctlDefuncReboot","{reboot} systemctl {max_processes}",$systemctlDefuncReboot);

    $form[]=$tpl->field_checkbox("EnableMsftncsi","{network_awareness}",$EnableMsftncsi,false,"{network_awareness_explain}");
    $form[]=$tpl->field_text("systemMaxOverloaded","{max_system_load}",$systemMaxOverloaded);
    echo $tpl->form_outside("",$form,null,"{apply}",jsrestart(),"AsSystemAdministrator");
    return true;

}
function params_monit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $LOAD[0]="{no_monitoring}";
    $t=time();
    for($i=1;$i<151;$i++){$LOAD[$i]=$i;}

    $MINUTES[1]="{during} 1 {minute}";
    for($i=2;$i<121;$i++){$MINUTES[$i]="{during} $i {minutes}";}
    $CPU[0]="{no_monitoring}";
    for($i=50;$i<101;$i++){$CPU[$i]="{$i}%";}
    for($i=1;$i<51;$i++){$HDPERC[$i]="{$i}%";}

    $MonitSpaceFree=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSpaceFree"));
    $MonitSpaceFreeCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSpaceFreeCycles"));

    $MonitCPUUsage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitCPUUsage"));
    $MonitCPUUsageCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitCPUUsageCycles"));

    $MonitMemUsage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemUsage"));
    $MonitMemUsageCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemUsageCycles"));

    $MonitReportLoadVG1mn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG1mn"));
    $MonitReportLoadVG1mnCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG1mnCycles"));

    if($MonitReportLoadVG1mnCycles==0){$MonitReportLoadVG1mnCycles=5;}

    $MonitReportLoadVG5mn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG5mn"));
    $MonitReportLoadVG5mnCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG5mnCycles"));

    if($MonitReportLoadVG5mnCycles==0){$MonitReportLoadVG5mnCycles=15;}

    $MonitReportLoadVG15mn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG15mn"));
    $MonitReportLoadVG15mnCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG15mnCycles"));

    if($MonitReportLoadVG15mnCycles==0){$MonitReportLoadVG15mnCycles=60;}


    $MonitMemPurgeCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCache"));
    $MonitMemPurgeCacheCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCacheCycles"));
    $MonitMemPurgeCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCacheLevel"));
    $MonitMemPurgeCacheLevelCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCacheLevelCycles"));
    if($MonitSpaceFreeCycles==0){$MonitSpaceFreeCycles=30;}
    if($MonitMemUsageCycles==0){$MonitMemUsageCycles=5;}
    if($MonitMemPurgeCache==0){$MonitMemPurgeCache=70;}
    if($MonitMemPurgeCacheCycles==0){$MonitMemPurgeCacheCycles=5;}
    if($MonitMemPurgeCacheLevel==0){$MonitMemPurgeCacheLevel=3;}
    if($MonitMemPurgeCacheLevelCycles==0){$MonitMemPurgeCacheLevelCycles=15;}

    if($MonitSpaceFree==0){$MonitSpaceFree=5;}

    if($MonitCPUUsage>0){
        if($MonitCPUUsage<50){
            $MonitCPUUsage=90;
        }
    }

    if($MonitMemUsage>0){
        if($MonitMemUsage<50){
            $MonitMemUsage=90;
        }
    }

    if($MonitCPUUsageCycles==0){$MonitCPUUsageCycles=15;}


    $form[]=$tpl->field_array_hash($LOAD,"MonitReportLoadVG1mn",
        "{if_system_load_exceed}:(1 {minute})",$MonitReportLoadVG1mn);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitReportLoadVG1mnCycles","&nbsp;",$MonitReportLoadVG1mnCycles);

    $form[]=$tpl->field_array_hash($LOAD,"MonitReportLoadVG5mn",
        "{if_system_load_exceed}:(5 {minutes})",$MonitReportLoadVG5mn);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitReportLoadVG5mnCycles","&nbsp;",$MonitReportLoadVG5mnCycles);

    $form[]=$tpl->field_array_hash($LOAD,"MonitReportLoadVG15mn",
        "{if_system_load_exceed}:(15 {minutes})",$MonitReportLoadVG15mn);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitReportLoadVG15mnCycles","&nbsp;",$MonitReportLoadVG15mnCycles);

    $form[]=$tpl->field_section("CPU / {memory} / {disks}");

    $form[]=$tpl->field_array_hash($CPU,"MonitCPUUsage",
        "{if_system_cpu_exceed}",$MonitCPUUsage);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitCPUUsageCycles","&nbsp;",$MonitCPUUsageCycles);

    $form[]=$tpl->field_array_hash($CPU,"MonitMemUsage",
        "{if_system_memory_exceed}",$MonitMemUsage);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitMemUsageCycles","&nbsp;",$MonitMemUsageCycles);

    $form[]=$tpl->field_array_hash($CPU,"MonitMemPurgeCache",
        "{purge_kernel_memory_cache_when_exceed}",$MonitMemPurgeCache);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitMemPurgeCacheCycles","&nbsp;",$MonitMemPurgeCacheCycles);

    $form[]=$tpl->field_array_hash($HDPERC,"MonitSpaceFree",
        "{warn_if_free_space_less_than}",$MonitSpaceFree);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitSpaceFreeCycles","&nbsp;",$MonitSpaceFreeCycles);

    echo $tpl->form_outside("",$form,null,"{apply}",jsrestart(),"AsSystemAdministrator");
    return true;

}

function jsrestart():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return "LoadAjax('monit-params','$page?settings2=yes');dialogInstance1.close();".$tpl->framework_buildjs("monit.php?restart=yes",
        "exec.monit.progress",
        "exec.monit.progress.txt",
        "progress-monit-restart",
        "LoadAjax('monit-params','$page?settings2=yes')");
}

function settings2():bool{
    $bsini=new Bs_IniHandler();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LOAD[0]="{no_monitoring}";
    $t=time();
    for($i=1;$i<151;$i++){
        $LOAD[$i]=$i;}


    $bsini->loadString(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork('cmd.php?monit-ini-status=yes')));


    $status=$tpl->SERVICE_STATUS($bsini, "APP_MONIT",jsrestart());

    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
    $incidents=$q->COUNT_ROWS("perfs_queue");

    if($incidents==0){
        $status=$status.$tpl->widget_vert("{incidents}",0);
    }else{
        $status=$status.$tpl->widget_jaune("{incidents}",$incidents);
    }

    $MINUTES[0]="";
    $MINUTES[1]="{during} 1 {minute}";
    for($i=2;$i<121;$i++){
        $MINUTES[$i]="{during} $i {minutes}";
    }

    $CPU[0]="{no_monitoring}";
    for($i=50;$i<101;$i++){

        $CPU[$i]="{$i}%";

    }
    for($i=1;$i<51;$i++){

        $HDPERC[$i]="{$i}%";

    }
    $EnableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMsftncsi"));


    $MonitSpaceFree=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSpaceFree"));
    $MonitSpaceFreeCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSpaceFreeCycles"));

    $MonitCPUUsage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitCPUUsage"));
    $MonitCPUUsageCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitCPUUsageCycles"));

    $MonitMemUsage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemUsage"));
    $MonitMemUsageCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemUsageCycles"));

    $MonitReportLoadVG1mn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG1mn"));
    $MonitReportLoadVG1mnCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG1mnCycles"));

    if($MonitReportLoadVG1mnCycles==0){$MonitReportLoadVG1mnCycles=5;}

    $MonitReportLoadVG5mn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG5mn"));
    $MonitReportLoadVG5mnCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG5mnCycles"));

    if($MonitReportLoadVG5mnCycles==0){$MonitReportLoadVG5mnCycles=15;}

    $MonitReportLoadVG15mn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG15mn"));
    $MonitReportLoadVG15mnCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitReportLoadVG15mnCycles"));

    if($MonitReportLoadVG15mnCycles==0){$MonitReportLoadVG15mnCycles=60;}

    $MonitMonitoring=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMonitoring"));
    $MonitMemPurgeCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCache"));
    $MonitMemPurgeCacheCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCacheCycles"));
    $MonitMemPurgeCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCacheLevel"));
    $MonitMemPurgeCacheLevelCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitMemPurgeCacheLevelCycles"));
    if($MonitSpaceFreeCycles==0){$MonitSpaceFreeCycles=30;}
    if($MonitMemUsageCycles==0){$MonitMemUsageCycles=5;}
    if($MonitMemPurgeCache==0){$MonitMemPurgeCache=70;}
    if($MonitMemPurgeCacheCycles==0){$MonitMemPurgeCacheCycles=5;}
    if($MonitMemPurgeCacheLevel==0){$MonitMemPurgeCacheLevel=3;}
    if($MonitMemPurgeCacheLevelCycles==0){$MonitMemPurgeCacheLevelCycles=15;}

    if($MonitSpaceFree==0){$MonitSpaceFree=5;}

    if($MonitCPUUsage>0){
        if($MonitCPUUsage<50){
            $MonitCPUUsage=90;
        }
    }

    if($MonitMemUsage>0){
        if($MonitMemUsage<50){
            $MonitMemUsage=90;
        }
    }

    if($MonitCPUUsageCycles==0){$MonitCPUUsageCycles=15;}

    $systemMaxOverloaded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("systemMaxOverloaded"));
    if($systemMaxOverloaded==0){$systemMaxOverloaded=17;}

    $FREE[1]="pagecache";
    $FREE[2]="dentries & inodes";
    $FREE[3]="{all}";

    $systemctlDefuncReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("systemctlDefuncReboot"));

    if($systemctlDefuncReboot==0){
        $systemctlDefuncReboot=300;
    }

    $tpl->table_form_field_js("Loadjs('$page?params-general-js=yes')");
    $tpl->table_form_field_text("{reboot}","$systemctlDefuncReboot systemctl zombies",ico_refresh);
    $tpl->table_form_field_bool("{network_awareness}",$EnableMsftncsi,ico_nic);
    $tpl->table_form_field_text("{max_system_load}",$systemMaxOverloaded,ico_max);


    $DailyReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DailyReboot"));
    $DailyRebootHour=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DailyRebootHour"));
    if($DailyRebootHour==null){$DailyRebootHour="06:00:00";}

    $tpl->table_form_field_js("Loadjs('$page?reboot-js=yes')");
    if($DailyReboot==0){
        $tpl->table_form_field_text("{DAILY_REBOOT}","{inactive2}",ico_refresh);
    }else{
        $tpl->table_form_field_text("{DAILY_REBOOT}","{scheduled_reboot} $DailyRebootHour",ico_refresh);
    }

    //$tpl->table_form_field_js("Loadjs('$page?monitoring-js=yes')");
    //$tpl->table_form_field_bool("{monitoring_page}",$MonitMonitoring,ico_html);

    $tpl->table_form_field_js("Loadjs('$page?params-monit-js=yes')");

    if($MonitReportLoadVG1mn==0){
        $MonitReportLoadVG1mnCycles=0;
    }
    if($MonitReportLoadVG5mn==0){
        $MonitReportLoadVG5mnCycles=0;
    }
    if($MonitReportLoadVG15mn==0){
        $MonitReportLoadVG15mnCycles=0;
    }
    if($MonitMemUsage==0){
        $MonitMemUsageCycles=0;
    }
    if($MonitMemPurgeCache==0){
        $MonitMemPurgeCacheCycles=0;
    }
    if($MonitSpaceFree==0){
        $MonitSpaceFreeCycles=0;
    }
    if($MonitMemUsage==0){
        $MonitMemUsageCycles=0;
    }
    if($MonitCPUUsage==0){
        $MonitCPUUsageCycles=0;
    }
    $tpl->table_form_field_text("{if_system_load_exceed} (1mn)",
        "$LOAD[$MonitReportLoadVG1mn] $MINUTES[$MonitReportLoadVG1mnCycles]" ,ico_max);

    $tpl->table_form_field_text("{if_system_load_exceed} (5mn)",
        "$LOAD[$MonitReportLoadVG5mn] $MINUTES[$MonitReportLoadVG5mnCycles]" ,ico_max);

    $tpl->table_form_field_text("{if_system_load_exceed} (15mn)",
        "$LOAD[$MonitReportLoadVG15mn] $MINUTES[$MonitReportLoadVG15mnCycles]" ,ico_max);

    $tpl->table_form_field_text("{if_system_cpu_exceed}",
        "$CPU[$MonitCPUUsage] $MINUTES[$MonitCPUUsageCycles]" ,ico_max);

    $tpl->table_form_field_text("{if_system_memory_exceed}",
        "$CPU[$MonitMemUsage] $MINUTES[$MonitMemUsageCycles]" ,ico_mem);

    $tpl->table_form_field_text("{purge_kernel_memory_cache_when_exceed}",
        "$CPU[$MonitMemPurgeCache] $MINUTES[$MonitMemPurgeCacheCycles]" ,ico_linux);

    $tpl->table_form_field_text("{warn_if_free_space_less_than}",
        "$HDPERC[$MonitSpaceFree] $MINUTES[$MonitSpaceFreeCycles]" ,ico_hd);


    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align:top'>$status</td>";
    $html[]="<td style='width:99%;padding-left: 20px;vertical-align:top'>".$tpl->table_form_compile()."</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{system_health_checking}";
    $TINY_ARRAY["ICO"]="fas fa-dog";
    $TINY_ARRAY["EXPL"]="{system_health_checking_explain}";
    $TINY_ARRAY["URL"]="system-watchdog";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function incidents_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div style='margin-top:20px'>";
    $html[]=$tpl->search_block($page,"","","","&search-incidents=yes");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function incident_download():bool{

    $time=intval($_GET["incident-download"]);
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
    $sql="SELECT file FROM perfs_queue WHERE zDate='$time'";
    $ligne=$q->mysqli_fetch_array($sql);
    $data=base64_decode($ligne["file"]);

    $filesize=strlen($data);
    header('Content-type: application/x-tgz');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$time.tar.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: $filesize");
    ob_clean();
    flush();
    echo $data;
    return admin_tracks("Downloaded incident $time");
}

function incidents_found(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $t=time();

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{incidents}</th>";
    $html[]="<th data-sortable=false>DOWN</th>";
    $html[]="<th data-sortable=false>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $search=$tpl->CLEAN_BAD_XSS($_GET["search"]);
    $search="*$search*";
    $search=str_replace("**","%",$search);
    $search=str_replace("**","%",$search);
    $search=str_replace("*","%",$search);
    $search=str_replace("%%","%",$search);
    $TRCLASS=null;
    $prc1=$tpl->table_td1prc();

    if(!is_file("/home/artica/SQLITE_TEMP/system.perf.queue.db")){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data}"));
        return true;
    }
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
    $sql="SELECT subject,zDate FROM perfs_queue WHERE subject LIKE '$search' ORDER BY zDate DESC LIMIT 250";
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }


    $font="style='font-size:14px'";
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
        $time = $ligne["zDate"];



        $download=$tpl->button_autnonome("{download}","document.location.href='$page?incident-download=$time'",ico_download,"AsSystemAdministrator");
        $delete=$tpl->button_autnonome("{delete}","Loadjs('$page?incident-delete=$time')",ico_trash,"AsSystemAdministrator",0,"btn-danger");
        $zdate=$tpl->time_to_date($time,true);
        $html[]="<tr class='$TRCLASS' id='$time'>";
        $html[]="<td nowrap style='width:1%'><span class='label label-default' $font>$zdate</span></td>";
        $html[]="<td $font>{$ligne["subject"]}</td>";
        $html[]="<td $prc1>$download</td>";
        $html[]="<td $prc1>$delete</td>";
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

    $topbuttons[] = array("Loadjs('$page?remove-all-incidents-js=yes&function=$function')", ico_trash, "{delete_all_logs}");

    $TINY_ARRAY["TITLE"]="{incidents}";
    $TINY_ARRAY["ICO"]="fa fa-bell";
    $TINY_ARRAY["EXPL"]="{incidents_explain}";
    $TINY_ARRAY["URL"]="incidents";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<script>";
    $html[]="$jstiny
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);


}