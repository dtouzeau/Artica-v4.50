<?php
// Post SP10 - 13/10/2026
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
if(isset($_GET["pgp-reset-js"])){pgp_reset_js();exit;}
if(isset($_POST["pgp-reset"])){pgp_reset();exit;}


status();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{tables}"]="fw.postgresql.tables.php";
    $array["{backup}"]="fw.postgresql.backup.php";
    $array["{events}"]="fw.postgresql.events.php";
    $array["{metrics}"]="fw.postgresql.metrics.php?main-view=yes&range=1h";
    echo $tpl->tabs_default($array);

}

function remove_database_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!pgp_is_admin()){$tpl->js_no_privileges();return;}

    // Privilege carried by the signed task too: fw.progress.php checks it again before the route runs.
    $jsrestart=$tpl->framework_buildjs("/postgresql/removedb",
        "postgres.remove.progress","postgres.remove.progress.txt",
    "progress-postgresql-restart","LoadAjax('table-postgresqlstatus','$page?tabs=yes')",null,null,"AsDatabaseAdministrator");




    $tpl->js_confirm_delete("{REMOVE_DATABASE}<hr>{rebuild_database_warn}","none",$tpl->javascript_parse_text("{database}"),$jsrestart);
}




function status():bool{
    $page=CurrentPageName();
   $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $tpl=new template_admin();
   $html=$tpl->page_header("PostgreSQL $version &raquo;&raquo; {service_status}",
       ico_database,"{PostgreSQL_explain}","$page?tabs=yes",
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

    <script>
       $Interval
    </script>
    </td>
    </tr>";


    $html[]="</table>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:10px'>";
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

// Settings panel of the Status tab (?params-fixed=yes): a parameter list and a memory
// grid, after the stitch_postgresql_dashboard_optimization mockup. The values shown are
// the ones articarest actually writes into postgresql.conf (pgp_memory()), not the raw
// settings: a 0 means "computed from the RAM" and used to be displayed as a made-up default.
function params_fixed():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(pgp_disabled()){return params_fixed_disabled();}
    $InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));

    $html=array();
    $html[]="<div class='pgp ak'>";
    $html[]=pgp_card_params($InfluxUseRemote);
    if($InfluxUseRemote==0){
        $html[]=pgp_card_memory(pgp_memory());
        $html[]=pgp_card_storage();
    }
    $html[]="</div>";

    $addon="{status}";
    $PostgreSQLTotalBytes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLTotalBytes"));
    if($PostgreSQLTotalBytes>0){
        $addon="{database} ".FormatBytes($PostgreSQLTotalBytes/1024);
    }

    $Optimize=$tpl->framework_buildjs("/postgresql/optimize",
        "postgres.maintenance","postgres.maintenance.txt",
        "progress-postgresql-restart");

    $topbuttons[] = array($Optimize,ico_speed,"{optimize}");
    $topbuttons[] = array("Loadjs('$page?remove-database-ask=yes')",ico_trash,"{REMOVE_DATABASE}","AsDatabaseAdministrator");
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; $addon";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";

    echo pgp_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// Same definition as the daemon's watchdog (postgresqld/watchdog.go): DisablePostGres=1
// or EnablePostGreSQL=0 means the service is not wanted on this appliance.
function pgp_disabled():bool{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"))==1){return true;}
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostGreSQL"))==0;
}

// Disabled service: the parameters and memory cards describe a service that does not
// run, so they are replaced by the explanation. "Enable PostgreSQL" calls
// /postgresql/install: postgresqld.Install() sets EnablePostGreSQL=1 and
// DisablePostGres=0 itself, and only once it knows the binary is present.
function params_fixed_disabled():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $enablejs=$tpl->framework_buildjs("/postgresql/install",
        "postgres.progress","postgres.log","progress-postgresql-restart",
        "LoadAjax('table-postgresqlstatus','$page?tabs=yes');");

    $html=array();
    $html[]=$tpl->div_warning("{postgresql_is_disabled}||{postgresql_is_disabled_explain}");

    $topbuttons=array();
    $topbuttons[]=array($enablejs,ico_play,"{enable_postgresql}","AsDatabaseAdministrator");
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_POSTGRESQL_VERSION");
    $TINY_ARRAY["TITLE"]="PostgreSQL $version &raquo;&raquo; {disabled}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{PostgreSQL_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function pgp_is_admin():bool{
    $users=new usersMenus();
    return $users->AsDatabaseAdministrator;
}

// Server-side guard of the write handlers: the buttons are hidden for the others, but
// the POST keys are answered for any session.
function pgp_require_admin():bool{
    if(pgp_is_admin()){return true;}
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
    return false;
}

function pgp_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

// RAM in MB, computed like futils.MemoryPercentage() (sysinfo Totalram = MemTotal).
function pgp_ram_percent_mb($percent):int{
    $c=@file_get_contents("/proc/meminfo");
    if(!is_string($c) || !preg_match('/^MemTotal:\s+([0-9]+)\s+kB/m',$c,$re)){return 0;}
    return intval(floor(intval($re[1])*1024*$percent/100/1048576));
}

// Effective memory settings, mirroring articarest postgresqld (Configurator.go
// SharedBuffer(), BuildConfig(), memtuning.go). Keep the two in step.
function pgp_memory():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $M=array();
    $M["ram"]=pgp_ram_percent_mb(100);

    $raw=intval($sock->GET_INFO("PostgreSQLSharedBuffer"));
    $max=pgp_ram_percent_mb(25);
    $v=$raw;
    if($v==0 || $v>$max){$v=$max;}
    if($v>4000){$v=4000;}
    $M["shared"]=array("value"=>$v,"auto"=>$raw==0,"max"=>min($max,4000));

    $raw=intval($sock->GET_INFO("PostgreSQLEffectiveCacheSize"));
    $max=pgp_ram_percent_mb(40);
    if($max<=0){$max=512;}
    $v=$raw;
    if($v<=0 || $v>$max){$v=$max;}
    $M["cache"]=array("value"=>$v,"auto"=>$raw<=0,"max"=>$max);

    $maxcon=intval($sock->GET_INFO("PostgreSQLMaxCon"));
    if($maxcon<350){$maxcon=350;}
    $raw=intval($sock->GET_INFO("PostgreSQLWorkMem"));
    $v=$raw;
    if($v<=0){
        $v=4;
        if($M["ram"]>0){
            $v=intval(floor($M["ram"]*25/100/$maxcon));
            if($v<4){$v=4;}
            if($v>64){$v=64;}
        }
    }
    $M["work"]=array("value"=>$v,"auto"=>$raw<=0,"maxcon"=>$maxcon);

    $raw=intval($sock->GET_INFO("PostGresSQLMaxWorkerProcesses"));
    $M["workers"]=array("value"=>$raw==0?8:$raw,"auto"=>$raw==0);
    $raw=intval($sock->GET_INFO("PostGresSQLMaxParallelWorkersPerGather"));
    $M["parallel"]=array("value"=>$raw==0?2:$raw,"auto"=>$raw==0);
    return $M;
}

function pgp_edit_button($js):string{
    if($js=="" || !pgp_is_admin()){return "";}
    return "<button type='button' class='ak-act' title='{modify}' aria-label='{modify}' OnClick=\"$js\"><i class='fa-regular fa-pen-to-square'></i></button>";
}

function pgp_pill_inactive():string{
    return "<span class='ak-pill ak-off'><span class='ak-dot'></span>{inactive}</span>";
}

function pgp_param_row($ico,$label,$explain,$value,$js):string{
    $h[]="<div class='pgp-row'>";
    $h[]="<div class='pgp-row-l'><span class='ak-ico ak-ico-sq ak-off'><i class='$ico'></i></span>";
    $h[]="<div class='pgp-row-txt'><div class='pgp-row-label'>$label</div><div class='pgp-row-expl'>$explain</div></div></div>";
    $h[]="<div class='pgp-row-r'>$value".pgp_edit_button($js)."</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function pgp_card_params($InfluxUseRemote):string{
    $page=CurrentPageName();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $rows=array();

    $value=pgp_pill_inactive();
    if($InfluxUseRemote==1){
        $ip=trim($sock->GET_INFO("InfluxUseRemoteIpaddr"));
        $port=intval($sock->GET_INFO("InfluxUseRemotePort"));
        if($port==0){$port=5432;}
        $value="<span class='ak-chip pgp-val'>".pgp_e("$ip:$port")."</span>";
    }
    $rows[]=pgp_param_row(ico_link,"{remote_database}","{pgp_remote_explain}",$value,"Loadjs('$page?remote-database-js=yes')");

    if($InfluxUseRemote==0){
        $iface=trim((string)$sock->GET_INFO("InfluxListenInterface"));
        if($iface==""){$iface="lo";}
        $rows[]=pgp_param_row(ico_interface,"{listen_interface}","{pgp_listen_explain}",
            "<span class='ak-chip pgp-val'>".pgp_e($iface)."</span>","Loadjs('$page?default-params-js=yes')");

        $json=json_decode($sock->REST_API("/postgresql/realpath"));
        $dir="";
        if(is_object($json) && property_exists($json,"Info")){$dir=trim((string)$json->Info);}
        $value=$dir=="" ? "<span class='ak-chip pgp-val'>-</span>" : "<span class='ak-chip pgp-val'>".pgp_e($dir)."</span>";
        $rows[]=pgp_param_row(ico_directory,"{database_storage_path}","{pgp_storage_explain}",$value,"Loadjs('fw.postgres.directory.php')");

        $delay=intval($sock->GET_INFO("PostgreSQLDelayStart"));
        $value=$delay==0 ? pgp_pill_inactive() : "<span class='ak-chip pgp-val'>$delay {seconds}</span>";
        $rows[]=pgp_param_row(ico_timeout,"{delayed_startup}","{pgp_delay_explain}",$value,"Loadjs('$page?delay-start-js=yes')");

        $mem=intval($sock->GET_INFO("InfluxRestartMem"));
        $value=$mem<5 ? pgp_pill_inactive() : "<span class='ak-chip ak-warn pgp-val'>$mem MB</span>";
        $rows[]=pgp_param_row(ico_watchdog,"{restart_if_memory_exceed}","{pgp_watchdog_explain}",$value,"Loadjs('$page?max-memory-js=yes')");
    }

    $h[]="<section class='ak-card'>";
    $h[]="<div class='ak-card-h pgp-card-head'><div><h3>{service_parameters}</h3><p>{pgp_params_explain}</p></div></div>";
    $h[]="<div class='pgp-rows'>".@implode("",$rows)."</div>";
    $h[]="</section>";
    return @implode("",$h);
}

// Storage tile: share of the partition taken by the database (red), by other files
// (grey, only when the free space could be read) and free (pale green), in a peity donut.
// InfluxDB.state is written by articarest InfluxDbSize(): SIZEKB in KiB, PART in bytes.
function pgp_card_storage():string{
    $state=PROGRESS_DIR."/InfluxDB.state";
    if(!is_file($state) || (time()-filemtime($state))>3600){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/dbsize");   // recomputed in the background
    }
    $ARRAY=@unserialize(@file_get_contents($state));
    if(!is_array($ARRAY)){$ARRAY=array();}
    $dbKB=isset($ARRAY["SIZEKB"]) ? intval($ARRAY["SIZEKB"]) : 0;
    $partKB=isset($ARRAY["PART"]) ? intval(intval($ARRAY["PART"])/1024) : 0;

    if($dbKB==0 || $partKB==0){
        $body="<div class='pgp-store-empty'>{database_size}: -</div>";
        return pgp_storage_frame("-",$body);
    }

    $freeKB=-1;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/realpath"));
    if(is_object($json) && property_exists($json,"Info") && trim((string)$json->Info)<>""){
        $free=@disk_free_space(trim((string)$json->Info));
        if($free!==false){$freeKB=intval($free/1024);}
    }

    $slices=array();
    $slices[]=array("{database}",$dbKB,"#ef4444");
    if($freeKB>=0){
        $otherKB=$partKB-$dbKB-$freeKB;
        if($otherKB>0){$slices[]=array("{others}",$otherKB,"#cbd5e1");}
        $slices[]=array("{free_space}",$freeKB,"#bbf7d0");
    }else{
        // Free space unreadable: the rest of the partition, without telling what fills it.
        $slices[]=array("{partition}",max(0,$partKB-$dbKB),"#bbf7d0");
    }

    $values=array();$fills=array();$legend=array();
    foreach($slices as $sl){
        $values[]=max(1,intval($sl[1]/1024));
        $fills[]="\"$sl[2]\"";
        $pc=round($sl[1]*100/$partKB,1);
        $legend[]="<div class='pgp-leg'><span class='pgp-leg-l'><span class='pgp-leg-dot' style='background:$sl[2]'></span>$sl[0]</span>"
            ."<span class='pgp-leg-v'>".FormatBytes($sl[1])." <span>$pc%</span></span></div>";
    }
    $id="pgp-donut-".md5(microtime());
    $body="<div class='pgp-store'>"
        ."<div class='pgp-donut'><span id='$id'>".@implode(",",$values)."</span>"
        ."<div class='pgp-donut-c'><span>{database}</span><strong>".FormatBytes($dbKB)."</strong></div></div>"
        ."<div class='pgp-legend'>".@implode("",$legend)."</div></div>"
        ."<script>$('#$id').peity('donut',{fill:[".@implode(",",$fills)."],radius:80,innerRadius:58,height:160,width:160});</script>";
    return pgp_storage_frame(FormatBytes($partKB),$body);
}

function pgp_storage_frame($total,$body):string{
    return "<section class='ak-card'>"
        ."<div class='ak-card-h pgp-card-head'><div><h3>{storage}</h3><p>{database_size} / {partition}</p></div>"
        ."<span class='ak-chip'>{partition}: $total</span></div>"
        .$body."</section>";
}

function pgp_mem_tile($param,$title,$value,$unit,$hint,$auto,$js,$hintok=false):string{
    $badge=$auto ? "<span class='ak-tag ak-off'>{automatic}</span>" : "";
    $edit=pgp_edit_button($js);
    if($js==""){$badge="<span class='ak-tag ak-off'>{fixed}</span>";}
    $hc=$hintok ? " pgp-hint-ok" : "";
    $h[]="<div class='pgp-tile'>";
    $h[]="<div class='pgp-tile-top'><span class='pgp-param'>$param</span><span class='pgp-tile-act'>$badge$edit</span></div>";
    $h[]="<div class='pgp-tile-body'><div><div class='pgp-tile-title'>$title</div>";
    $h[]="<div class='pgp-tile-val'>$value <span>$unit</span></div></div>";
    $h[]="<span class='pgp-hint$hc'>$hint</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

function pgp_card_memory($M):string{
    $page=CurrentPageName();
    $js_mem="Loadjs('$page?default-params-js=yes')";
    $js_proc="Loadjs('$page?processes-js=yes')";

    $t[]=pgp_mem_tile("shared_buffers","{shared_buffer}",$M["shared"]["value"],"MB",
        "{recommended}: 25% = ".$M["shared"]["max"]." MB",$M["shared"]["auto"],$js_mem);
    $t[]=pgp_mem_tile("effective_cache_size","{effective_cache_size}",$M["cache"]["value"],"MB",
        "{maximum}: 40% = ".$M["cache"]["max"]." MB",$M["cache"]["auto"],$js_mem);
    $t[]=pgp_mem_tile("work_mem","{work_mem}",$M["work"]["value"],"MB",
        "&times; ".$M["work"]["maxcon"]." {connections}",$M["work"]["auto"],$js_mem);
    $t[]=pgp_mem_tile("max_worker_processes","{max_worker_processes}",$M["workers"]["value"],"{processes}",
        "{default}: 8",$M["workers"]["auto"],$js_proc);
    $t[]=pgp_mem_tile("max_parallel_workers_per_gather","{max_parallel_workers}",$M["parallel"]["value"],"{processes}",
        "{default}: 2",$M["parallel"]["auto"],$js_proc);
    $t[]=pgp_mem_tile("autovacuum_max_workers","{pgp_autovacuum_workers}",3,"{workers}",
        "autovacuum on",false,"",true);

    $reset="";
    if(pgp_is_admin()){
        $reset="<button type='button' class='ak-link' OnClick=\"Loadjs('$page?pgp-reset-js=yes')\">{pgp_restore_recommended}</button>";
    }
    $h[]="<section class='ak-card'>";
    $h[]="<div class='ak-card-h pgp-card-head'><div><h3>{pgp_memory_title}</h3><p>{pgp_memory_explain}</p></div>$reset</div>";
    $h[]="<div class='pgp-grid'>".@implode("",$t)."</div>";
    $h[]="</section>";
    return @implode("",$h);
}

function pgp_reset_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!pgp_is_admin()){$tpl->js_no_privileges();return false;}
    $jsrestart=$tpl->framework_buildjs("/postgresql/restart",
        "postgres.progress","postgres.log","progress-postgresql-restart",
        "LoadAjax('main-postgresql-params','$page?params-fixed=yes');");
    return $tpl->js_confirm_execute("{pgp_restore_recommended_ask}","pgp-reset","yes",
        "LoadAjax('main-postgresql-params','$page?params-fixed=yes');$jsrestart");
}

// Back to 0 = computed by articarest from the RAM at the next configuration build.
function pgp_reset():bool{
    if(!pgp_require_admin()){return false;}
    foreach(array("PostgreSQLSharedBuffer","PostgreSQLEffectiveCacheSize","PostgreSQLWorkMem",
                "PostGresSQLMaxWorkerProcesses","PostGresSQLMaxParallelWorkersPerGather") as $key){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,0);
    }
    return admin_tracks("PostgreSQL memory and process settings returned to their automatic values");
}

function pgp_css():string{
    if(isset($GLOBALS["PGP_CSS"])){return "";}
    $GLOBALS["PGP_CSS"]=true;
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){$ac=brand_ui_accents(brand_palette());}
    $ad=$ac["accent_dark"];$al=$ac["accent_line"];
    return "<style>
.pgp{display:flex;flex-direction:column;gap:20px;margin-top:10px;color:#1e293b}
.pgp-card-head{gap:16px;padding:18px 24px;background:#f8fafc}
.pgp-card-head h3{margin:0;font-size:16px;font-weight:700;color:#0f172a}
.pgp-card-head p{margin:4px 0 0;font-size:13px;font-weight:400;color:#64748b}
.pgp-row{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px 16px;padding:16px 24px;border-bottom:1px solid #f1f5f9}
.pgp-row:last-child{border-bottom:0}
.pgp-row:hover{background:#fcfdfe}
.pgp-row-l{display:flex;align-items:center;gap:16px;min-width:0}
.pgp-row-label{font-size:15px;font-weight:600;color:#0f172a}
.pgp-row-expl{font-size:13px;color:#64748b;margin-top:2px}
.pgp-row-r{display:flex;align-items:center;gap:12px;flex:0 0 auto;margin-left:auto}
.pgp-val{max-width:340px;overflow:hidden;text-overflow:ellipsis}
.pgp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:16px;padding:20px 24px}
.pgp-tile{border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;background:#fff;box-sizing:border-box}
.pgp-tile:hover{border-color:$al}
.pgp-tile-top{display:flex;justify-content:space-between;align-items:center;gap:8px;min-height:26px}
.pgp-param{font-family:'JetBrains Mono',Consolas,monospace;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pgp-tile-act{display:flex;align-items:center;gap:4px;flex:0 0 auto}
.pgp-tile-body{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:4px 10px;margin-top:10px}
.pgp-tile-title{font-size:14px;font-weight:600;color:#0f172a}
.pgp-tile-val{font-size:28px;font-weight:700;color:$ad;line-height:1.2;margin-top:6px}
.pgp-tile-val span{font-size:13px;font-weight:500;color:#64748b}
.pgp-hint{font-size:12px;color:#94a3b8;white-space:nowrap;margin-left:auto;padding-bottom:4px}
.pgp-hint-ok{color:$ad}
.pgp-store{display:flex;align-items:center;gap:40px;padding:24px;flex-wrap:wrap}
.pgp-donut{position:relative;width:160px;height:160px;flex:0 0 auto}
.pgp-donut-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.pgp-donut-c span{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8}
.pgp-donut-c strong{font-size:18px;color:#0f172a}
.pgp-legend{flex:1 1 260px;display:flex;flex-direction:column;gap:12px;min-width:0}
.pgp-leg{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-bottom:10px;border-bottom:1px solid #f1f5f9}
.pgp-leg:last-child{border-bottom:0;padding-bottom:0}
.pgp-leg-l{display:flex;align-items:center;gap:10px;font-size:14px;color:#334155}
.pgp-leg-dot{width:12px;height:12px;border-radius:4px;display:inline-block}
.pgp-leg-v{font-size:14px;font-weight:600;color:#0f172a;white-space:nowrap}
.pgp-leg-v span{font-weight:400;color:#94a3b8;margin-left:6px}
.pgp-store-empty{padding:24px;color:#94a3b8}
</style>";
}
function params_process_save():bool{
    if(!pgp_require_admin()){return false;}
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
    if(!pgp_require_admin()){return false;}
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

    // Stored settings, not effective values: 0 = computed by articarest from the RAM
    // (pgp_memory()). Pre-filling the form with a default would pin it at the next save.
    $PostgreSQLSharedBuffer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLSharedBuffer"));
    $PostgreSQLEffectiveCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLEffectiveCacheSize"));
    $PostgreSQLWorkMem=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLWorkMem"));

    $form[]=$tpl->field_interfaces("InfluxListenInterface","{listen_interface}",$InfluxListenInterface);


    $form[]=$tpl->field_numeric("PostgreSQLSharedBuffer","{shared_buffer} (MB, 0 = {automatic})",$PostgreSQLSharedBuffer,"{PostgreSQLSharedBuffer}");
    $form[]=$tpl->field_numeric("PostgreSQLEffectiveCacheSize","{effective_cache_size} (MB, 0 = {automatic})",$PostgreSQLEffectiveCacheSize,"{PostgreSQLEffectiveCacheSize}");
    $form[]=$tpl->field_numeric("PostgreSQLWorkMem","{work_mem} (MB, 0 = {automatic})",$PostgreSQLWorkMem,"{PostgreSQLWorkMem}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",restart_form_js(),"AsDatabaseAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    if(!pgp_require_admin()){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Change PostGreSQL main parameters");
    return true;

}
function SaveNoRestart():bool{
    if(!pgp_require_admin()){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/monit");
    admin_tracks_post("Change PostGreSQL main parameters");
    return true;
}
