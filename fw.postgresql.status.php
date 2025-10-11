<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["none"])){die();}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["InfluxRestartMem"])){SaveNoRestart();exit;}
if(isset($_POST["InfluxUseRemote"])){Save();exit;}
if(isset($_POST["InfluxListenInterface"])){Save();exit;}
if(isset($_GET["services-status"])){service_status();exit;}

if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["remove-database-ask"])){remove_database_ask();exit;}
if(isset($_GET["params-fixed"])){params_fixed();exit;}
if(isset($_GET["remote-database-js"])){remote_database_js();exit;}
if(isset($_GET["remote-database-popup"])){remote_database_popup();exit;}
if(isset($_GET["default-params-js"])){default_params_js();exit;}
if(isset($_GET["default-params-popup"])){default_params_popup();exit;}

if(isset($_GET["delay-start-js"])){delay_start_js();exit;}
if(isset($_GET["delay-start-popup"])){delay_start_popup();exit;}
if(isset($_POST["PostgreSQLDelayStart"])){delay_start_save();exit;}

if(isset($_GET["processes-js"])){params_process_js();exit;}
if(isset($_GET["processes-popup"])){params_process_popup();exit;}
if(isset($_POST["PostGresSQLMaxWorkerProcesses"])){params_process_save();exit;}
if(isset($_GET["max-memory-js"])){max_memory_js();exit;}
if(isset($_GET["max-memory-popup"])){max_memory_popup();exit;}


status();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{tables}"]="fw.postgresql.tables.php";
    $array["{backup}"]="fw.postgresql.backup.php";
    $array["{events}"]="fw.postgresql.events.php";
    echo $tpl->tabs_default($array);

}

function remove_database_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/postgresql/removedb",
        "postgres.remove.progress","postgres.remove.progress.txt",
    "progress-postgresql-restart","LoadAjax('table-postgresqlstatus','$page?tabs=yes')");




    $tpl->js_confirm_delete("{REMOVE_DATABASE}<hr>{rebuild_database_warn}","none",$tpl->javascript_parse_text("{database}"),$jsrestart);
}




function status():bool{
    $page=CurrentPageName();
   $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $tpl=new template_admin();
   $html=$tpl->page_header("PostgreSQL $version &raquo;&raquo; {service_status}",
       "fas fa-database","{PostgreSQL_explain}","$page?tabs=yes",
        "PostgreSQL","progress-postgresql-restart",false,"table-postgresqlstatus");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function service_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $sock=new sockets();
    $sock->REST_API("/postgresql/status");
    $StatusFile=PROGRESS_DIR."/postgres.status";
    $ini->loadFile($StatusFile);


    $jsrestart=$tpl->framework_buildjs(
        "/postgresql/restart",
        "postgres.progress",
        "postgres.log",
        "progress-postgresql-restart",
        "LoadAjax('table-postgresqlstatus','$page?tabs=yes');"
    );

    $html[]=$tpl->SERVICE_STATUS($ini, "APP_POSTGRES",$jsrestart);
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_PBBOUNCER","");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px' valign='top'>";
    $html[]="<table style='width:100%'>";

    $Interval=$tpl->RefreshInterval_js("postgresql-all-status",$page,"services-status=yes",3);

    $html[]="<tr><td>
	<div class=\"ibox\" id='postgresql-all-status' style='margin-top:10px'></div>
    <div>".$tpl->button_autnonome("{REMOVE_DATABASE}","Loadjs('$page?remove-database-ask=yes')","fas fa-trash","AsDatabaseAdministrator",335,"btn-danger")."</div>

	
    <div id='influx-db-size'></div>
    <script>
        Loadjs('$page?graph1=yes');
       $Interval
    </script>
    </td>
    </tr>";


    $html[]="</table>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
    $html[]="<div id='main-postgresql-params'></div>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="</table>";
    $html[]="<script>LoadAjax('main-postgresql-params','$page?params-fixed=yes');</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function remote_database_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{remote_database}","$page?remote-database-popup=yes");
}
function default_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{settings}","$page?default-params-popup=yes");
}
function max_memory_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{restart_if_memory_exceed}","$page?max-memory-popup=yes",650);
}
function delay_start_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{delayed_startup}","$page?delay-start-popup=yes",650);
}
function params_process_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{processes}","$page?processes-popup=yes",650);
}
function remote_database_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
    $InfluxUseRemoteIpaddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemoteIpaddr"));
    $InfluxUseRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemotePort"));
    if($InfluxUseRemotePort==0){$InfluxUseRemotePort=5432;}

    $form[]=$tpl->field_checkbox("InfluxUseRemote","{enable}",$InfluxUseRemote, "InfluxUseRemoteIpaddr,InfluxUseRemotePort");
    $form[]=$tpl->field_text("InfluxUseRemoteIpaddr","{server_address}",$InfluxUseRemoteIpaddr);
    $form[]=$tpl->field_numeric("InfluxUseRemotePort","{remote_server_port}",$InfluxUseRemotePort);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",restart_form_js(),"AsDatabaseAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function restart_form_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsrestart=$tpl->framework_buildjs("/postgresql/restart",
        "postgres.progress",
        "postgres.log","progress-postgresql-restart","LoadAjax('table-postgresqlstatus','$page?table=yes');dialogInstance1.close()");

    $f[]="dialogInstance1.close()";
    $f[]="LoadAjax('main-postgresql-params','$page?params-fixed=yes')";
    $f[]=$jsrestart;
    return @implode(";",$f);
}

function params_fixed():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
    $tpl->table_form_field_js("Loadjs('$page?remote-database-js=yes')");
    if($InfluxUseRemote==1) {
        $InfluxUseRemoteIpaddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemoteIpaddr"));
        $InfluxUseRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemotePort"));
        if($InfluxUseRemotePort==0){$InfluxUseRemotePort=5432;}
        $tpl->table_form_field_text("{remote_database}","$InfluxUseRemoteIpaddr:$InfluxUseRemotePort",ico_link);
    }else{
        $tpl->table_form_field_bool("{remote_database}",0,ico_link);
        $InfluxListenInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxListenInterface");
        $InfluxRestartMem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxRestartMem"));
        $PostgreSQLSharedBuffer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLSharedBuffer"));
        if($PostgreSQLSharedBuffer==0){$PostgreSQLSharedBuffer=32;}


        $PostgreSQLEffectiveCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLEffectiveCacheSize"));
        if($PostgreSQLEffectiveCacheSize==0){$PostgreSQLEffectiveCacheSize=256;}

        $PostgreSQLDelayStart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLDelayStart"));

        $PostgreSQLWorkMem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLWorkMem"));
        if($PostgreSQLWorkMem==0){$PostgreSQLWorkMem=200;}
        if($PostgreSQLWorkMem<50){$PostgreSQLWorkMem=200;}


        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/realpath"));
        $PostGresSQLDatabaseDirectory=$json->Info;

        if($InfluxListenInterface==null){$InfluxListenInterface="lo";}
        $tpl->table_form_field_js("Loadjs('$page?default-params-js=yes')","AsDatabaseAdministrator");
        $tpl->table_form_field_text("{listen_interface}",$InfluxListenInterface,ico_interface);

        $tpl->table_form_field_js("Loadjs('$page?max-memory-js=yes')","AsDatabaseAdministrator");
        if($InfluxRestartMem<5){
            $tpl->table_form_field_bool("{restart_if_memory_exceed}",0,ico_watchdog);
        }else {

            $tpl->table_form_field_text("{restart_if_memory_exceed}", $InfluxRestartMem . "MB", ico_watchdog);
        }
        $tpl->table_form_field_js("Loadjs('$page?default-params-js=yes')","AsDatabaseAdministrator");
        $tpl->table_form_field_text("{shared_buffer}","{$PostgreSQLSharedBuffer}MB",ico_mem);
        $tpl->table_form_field_text("{effective_cache_size}",$PostgreSQLEffectiveCacheSize."MB",ico_mem);
        $tpl->table_form_field_text("{work_mem}",$PostgreSQLWorkMem."MB",ico_mem);

        $PostGresSQLMaxWorkerProcesses=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresSQLMaxWorkerProcesses"));
        if($PostGresSQLMaxWorkerProcesses==0){
            $PostGresSQLMaxWorkerProcesses=8;
        }

        $PostGresSQLMaxParallelWorkersPerGather=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresSQLMaxParallelWorkersPerGather"));
        if($PostGresSQLMaxParallelWorkersPerGather==0){
            $PostGresSQLMaxParallelWorkersPerGather=2;
        }


        $tpl->table_form_field_js("Loadjs('$page?processes-js=yes')");
        $tpl->table_form_field_text("{max_worker_processes}",
            "$PostGresSQLMaxWorkerProcesses {processes}",ico_cpu);
        $tpl->table_form_field_text("{max_parallel_workers}",
            "$PostGresSQLMaxParallelWorkersPerGather {processes}",ico_cpu);

        $tpl->table_form_field_js("Loadjs('fw.postgres.directory.php')");
        $tpl->table_form_field_text("{database_storage_path}",$PostGresSQLDatabaseDirectory,ico_directory);

        $tpl->table_form_field_js("Loadjs('$page?delay-start-js=yes')");
        if($PostgreSQLDelayStart==0){
            $tpl->table_form_field_bool("{delayed_startup}",0,ico_timeout);
        }else{
            $tpl->table_form_field_text("{delayed_startup}","$PostgreSQLDelayStart {seconds}",ico_timeout);
        }
    }


    echo $tpl->table_form_compile();

    //$topbuttons[] = array("Loadjs('$page?refresh-js=yes')", ico_refresh, "{status}");

    $addon="{status}";
    $PostgreSQLTotalBytes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLTotalBytes"));
    if($PostgreSQLTotalBytes>0){
        $addon="{database} ".FormatBytes($PostgreSQLTotalBytes/1024);
    }

    $Optimize=$tpl->framework_buildjs("/postgresql/optimize",
        "postgres.maintenance","postgres.maintenance.txt",
        "progress-postgresql-restart");

    $topbuttons[] = array($Optimize,ico_speed,"{optimize}");
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; $addon";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";

    return true;

}
function params_process_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save PostGresSQL Processes configuration");
}
function delay_start_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PostgreSQLDelayStart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLDelayStart"));
    $ico=ico_timeout;
    $f[]="dialogInstance1.close()";
    $f[]="LoadAjax('main-postgresql-params','$page?params-fixed=yes')";
    $form[]=$tpl->field_numeric("PostgreSQLDelayStart","{seconds}",$PostgreSQLDelayStart);
    $html[]=$tpl->form_outside("", @implode("\n", $form),"{delayed_startup}|{delayed_startup_service_explain}|$ico","{apply}",@implode(";",$f),"AsDatabaseAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function delay_start_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save PostGresSQL Delay Start configuration to {$_POST["PostgreSQLDelayStart"]} seconds");
}
function params_process_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $PostGresSQLMaxWorkerProcesses=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresSQLMaxWorkerProcesses"));
    if($PostGresSQLMaxWorkerProcesses==0){
        $PostGresSQLMaxWorkerProcesses=8;
    }

    $PostGresSQLMaxParallelWorkersPerGather=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresSQLMaxParallelWorkersPerGather"));
    if($PostGresSQLMaxParallelWorkersPerGather==0){
        $PostGresSQLMaxParallelWorkersPerGather=2;
    }
    $form[]=$tpl->field_numeric("PostGresSQLMaxWorkerProcesses","{max_worker_processes}",$PostGresSQLMaxWorkerProcesses);

    $form[]=$tpl->field_numeric("PostGresSQLMaxParallelWorkersPerGather","{max_parallel_workers}",$PostGresSQLMaxParallelWorkersPerGather);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",restart_form_js(),"AsDatabaseAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function max_memory_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $f[]="dialogInstance1.close()";
    $f[]="LoadAjax('main-postgresql-params','$page?params-fixed=yes')";

    $InfluxRestartMem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxRestartMem"));
    $form[]=$tpl->field_numeric("InfluxRestartMem","{restart_if_memory_exceed} (MB)",$InfluxRestartMem,"{influx_restart_if_memory_exceed}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",@implode(";",$f),"AsDatabaseAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function default_params_popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $InfluxListenInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxListenInterface");
    if($InfluxListenInterface==null){$InfluxListenInterface="lo";}

    $PostgreSQLSharedBuffer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLSharedBuffer"));
    if($PostgreSQLSharedBuffer==0){$PostgreSQLSharedBuffer=32;}


    $PostgreSQLEffectiveCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLEffectiveCacheSize"));
    if($PostgreSQLEffectiveCacheSize==0){$PostgreSQLEffectiveCacheSize=256;}


    $PostgreSQLWorkMem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLWorkMem"));
    if($PostgreSQLWorkMem==0){$PostgreSQLWorkMem=200;}
    if($PostgreSQLWorkMem<50){$PostgreSQLWorkMem=200;}

    $form[]=$tpl->field_interfaces("InfluxListenInterface","{listen_interface}",$InfluxListenInterface);


    $form[]=$tpl->field_numeric("PostgreSQLSharedBuffer","{shared_buffer} (MB)",$PostgreSQLSharedBuffer,"{PostgreSQLSharedBuffer}");
    $form[]=$tpl->field_numeric("PostgreSQLEffectiveCacheSize","{effective_cache_size} (MB)",$PostgreSQLEffectiveCacheSize,"{PostgreSQLEffectiveCacheSize}");
    $form[]=$tpl->field_numeric("PostgreSQLWorkMem","{work_mem} (MB)",$PostgreSQLWorkMem,"{PostgreSQLWorkMem}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",restart_form_js(),"AsDatabaseAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Change PostGreSQL main parameters");
    return true;

}
function SaveNoRestart():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/monit");
    admin_tracks_post("Change PostGreSQL main parameters");
    return true;
}

function graph1(){

    $cacheFile=PROGRESS_DIR."/InfluxDB.state";
    $tpl=new templates();
    $ARRAY=unserialize(@file_get_contents($cacheFile));
    $ARRAY["PART"]=$ARRAY["PART"]/1024;

    $PART=intval($ARRAY["PART"])-intval($ARRAY["SIZEKB"]);

    $MAIN["Partition " .FormatBytes($ARRAY["PART"])]=$PART;
    $MAIN["DB ".FormatBytes($ARRAY["SIZEKB"])]=$ARRAY["SIZEKB"];

    $PieData=$MAIN;
    $highcharts=new highcharts();
    $highcharts->container="influx-db-size";
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{database_size}";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{database_size} ".FormatBytes($ARRAY["SIZEKB"]));
    echo $highcharts->BuildChart();
}
