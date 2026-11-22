<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

// Aucun controle de privilege n'existait ici: "AsSystemAdministrator" n'apparaissait
// que comme argument d'affichage de button_autnonome, ce qui masque un bouton mais
// ne protege pas le point d'entree. Le telechargement et la suppression d'incidents,
// ainsi que les enregistrements de reglages systeme, etaient donc accessibles a tout
// compte authentifie.
$users=new usersMenus();
if(!$users->AsSystemAdministrator){
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
    exit;
}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["params-general-js"])){params_general_js();exit;}
if(isset($_GET["params-general-popup"])){params_general_popup();exit;}

if(isset($_GET["params-monit-js"])){params_monit_js();exit;}
if(isset($_GET["params-monit-popup"])){params_monit_popup();exit;}


// Le dispatch de ce formulaire s'appuyait sur EnableMsftncsi, retire avec la
// surveillance Internet par interface (aucun consommateur cote demon). La cle
// est desormais systemctlDefuncReboot, autre champ toujours poste par le meme
// form_outside -- sans quoi le formulaire ne s'enregistrerait plus du tout.
if(isset($_POST["systemctlDefuncReboot"])){Save();exit;}
if(isset($_POST["MonitReportLoadVG1mn"])){Save();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["settings2"])){settings2();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["incidents"])){incidents_search();exit;}
if(isset($_GET["search-incidents"])){incidents_found();exit;}
if(isset($_GET["incidents-summary"])){minc_summary();exit;}
if(isset($_GET["incident-contents"])){minc_contents();exit;}
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
if(isset($_GET["restart-monit-do"])){restart_monit_do();exit;}
page();

function restart_monit_do(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    // Fires /system/monit/restart (real Stop()+Start() in the daemon).
    // Replaces the legacy framework/monit.php?restart=yes which was a no-op.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/monit/restart");
    echo "setTimeout(function(){LoadAjax('monit-params','$page?settings2=yes');},5000);";
}

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
    $time=intval($_POST["incident-delete"]);
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
    $systemMaxOverloaded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("systemMaxOverloaded"));
    if($systemMaxOverloaded==0){$systemMaxOverloaded=17;}

    $systemctlDefuncReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("systemctlDefuncReboot"));

    if($systemctlDefuncReboot==0){
        $systemctlDefuncReboot=300;
    }

    $form[]=$tpl->field_numeric("systemctlDefuncReboot","{reboot} systemctl {max_processes}",$systemctlDefuncReboot);

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
    $page=CurrentPageName();
    // Migrated 2026-04-23: previously routed through framework/monit.php?restart=yes
    // whose function body was empty — the restart never actually fired. Now hits
    // the new /system/monit/restart endpoint via the restart-monit-do handler
    // (which fires a real Stop()+Start() in the daemon).
    return "LoadAjax('monit-params','$page?settings2=yes');dialogInstance1.close();"
        ."Loadjs('$page?restart-monit-do=yes');";
}

/**
 * Onglet des reglages (maquette stitch_artica_watchdog_dashboard) :
 * un bandeau de 3 tuiles d'etat, puis une carte par famille de reglages.
 * Une fonction par tuile/carte ; la CSS et le JS sont concatenes HORS du
 * moteur de traduction (il lirait chaque bloc {…} comme un jeton).
 */
function settings2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $v=mnt_values();

    $html=array();
    $html[]="<div class='mnt-wrap ak'>";
    $html[]="<section class='mnt-status'>";
    $html[]=mnt_tile_daemon();
    $html[]=mnt_tile_incidents();
    $html[]=mnt_tile_coverage($v);
    $html[]="</section>";
    $html[]="<div class='mnt-grid'>";
    $html[]=mnt_card_load($v);
    $html[]=mnt_card_resources($v);
    $html[]=mnt_card_purge($v);
    $html[]=mnt_card_reboot($v);
    $html[]=mnt_card_bandwidth($v);
    $html[]="</div>";
    $html[]="</div>";

    $TINY_ARRAY["TITLE"]="{system_health_checking}";
    $TINY_ARRAY["ICO"]="fas fa-dog";
    $TINY_ARRAY["EXPL"]="{system_health_checking_explain}";
    $TINY_ARRAY["URL"]="system-watchdog";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo mnt_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).mnt_js($page).
        "<script>NoSpinner();$jstiny</script>";
    return true;
}

/** Valeurs des reglages, avec les memes defauts que le demon (monit/monit.go). */
function mnt_values():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $v=array();
    foreach(array("MonitSpaceFree","MonitSpaceFreeCycles","MonitCPUUsage","MonitCPUUsageCycles",
                "MonitMemUsage","MonitMemUsageCycles","MonitReportLoadVG1mn","MonitReportLoadVG1mnCycles",
                "MonitReportLoadVG5mn","MonitReportLoadVG5mnCycles","MonitReportLoadVG15mn",
                "MonitReportLoadVG15mnCycles","MonitMemPurgeCache","MonitMemPurgeCacheCycles",
                "systemMaxOverloaded","systemctlDefuncReboot","DailyReboot","EnableBandwithCalculation") as $key){
        $v[$key]=intval($s->GET_INFO($key));
    }
    $defaults=array("MonitReportLoadVG1mnCycles"=>5,"MonitReportLoadVG5mnCycles"=>15,
        "MonitReportLoadVG15mnCycles"=>60,"MonitSpaceFreeCycles"=>30,"MonitMemUsageCycles"=>5,
        "MonitMemPurgeCache"=>70,"MonitMemPurgeCacheCycles"=>5,"MonitSpaceFree"=>5,
        "MonitCPUUsageCycles"=>15,"systemMaxOverloaded"=>17,"systemctlDefuncReboot"=>300);
    foreach($defaults as $key=>$def){
        if($v[$key]==0){$v[$key]=$def;}
    }
    if($v["MonitCPUUsage"]>0 && $v["MonitCPUUsage"]<50){$v["MonitCPUUsage"]=90;}
    if($v["MonitMemUsage"]>0 && $v["MonitMemUsage"]<50){$v["MonitMemUsage"]=90;}

    $hour=trim((string)$s->GET_INFO("DailyRebootHour"));
    if($hour==""){$hour="06:00:00";}
    $v["DailyRebootHour"]=substr($hour,0,5);
    return $v;
}

/** "pendant 5 minutes" — le nombre en gras, l'unite au singulier pour 1. */
function mnt_duration($minutes):string{
    $minutes=intval($minutes);
    $unit=$minutes>1?"{minutes}":"{minute}";
    return "<span class='mnt-dur'>{during} <strong>$minutes</strong> $unit</span>";
}

/** Pastille "Pas de surveillance" : un mot et un trait, jamais un gris seul. */
function mnt_off_pill():string{
    return "<span class='ak-pill ak-off'><span class='ak-dot'></span>{no_monitoring}</span>";
}

/** Bouton "Modifier" d'une carte. */
function mnt_edit_button($js,$label="{modify}"):string{
    return "<button type='button' class='ak-btn' onclick=\"$js\"><span class='mnt-btn-ico'><i class='fas fa-pen'></i></span>$label</button>";
}

/** En-tete d'une carte : pastille d'icone, titre, etiquette facultative, bouton. */
function mnt_card_head($ico,$title,$tag,$button,$icoClass=""):string{
    $h="<div class='mnt-card-head'>";
    $h.="<div class='mnt-card-title'><span class='ak-ico ak-ico-sm ak-ico-sq $icoClass'><i class='$ico'></i></span>";
    $h.="<h2>$title</h2>$tag</div>$button</div>";
    return $h;
}

/** Ligne cliquable d'un tableau de seuils. */
function mnt_row($js,$label,$valueHtml,$durHtml,$off=false,$dot=true):string{
    $cls=$off?"mnt-row mnt-row-off":"mnt-row";
    $h="<div class='$cls' role='button' tabindex='0' onclick=\"$js\" onkeydown='MntKey(event,this)'>";
    $h.="<div class='mnt-row-lbl'>".($dot?"<span class='mnt-dot'></span>":"")."<span class='mnt-row-txt'>$label</span></div>";
    $h.="<div class='mnt-row-val'>$valueHtml</div>";
    $h.="<div class='mnt-row-dur'>$durHtml<span class='mnt-chev'><i class='fas fa-chevron-right'></i></span></div>";
    $h.="</div>";
    return $h;
}

/** Valeur en pourcentage avec sa mini-barre de position sur 0-100 %. */
function mnt_percent($value):string{
    $value=intval($value);
    $w=max(0,min(100,$value));
    return "<span class='ak-bar mnt-bar'><span style='width:$w%'></span></span>".
        "<span class='mnt-num'>$value<span class='mnt-unit'>%</span></span>";
}

/** Tuile 1 : etat du demon Monit, lu sur /system/monit/status. */
function mnt_tile_daemon():string{
    $page=CurrentPageName();
    $ini=new Bs_IniHandler();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/monit/status"));
    $p=array();
    if(is_object($json) && property_exists($json,"Info") && $json->Status){
        $ini->loadString((string)$json->Info);
        if(is_array($ini->_params)){
            foreach($ini->_params as $section){if(is_array($section)){$p=$section;break;}}
        }
    }

    $state="<span class='ak-pill ak-off'><span class='ak-dot'></span>{unknown}</span>";
    if(count($p)>0){
        if(intval($p["application_installed"] ?? 1)==0){
            $state="<span class='ak-pill ak-off'><span class='ak-dot'></span>{not_installed}</span>";
        }elseif(intval($p["running"] ?? 0)==1){
            $state="<span class='ak-pill ak-ok'><span class='ak-dot'></span>{running}</span>";
        }else{
            $state="<span class='ak-pill ak-err'><span class='ak-dot'></span>{stopped}</span>";
        }
    }
    $running=intval($p["running"] ?? 0)==1;
    $uptime=$running?mnt_uptime((string)($p["uptime"] ?? "")):"";
    $mem=$running?FormatBytes(intval($p["master_memory"] ?? 0)):"—";
    $fd="—";
    if($running && intval($p["maxfd"] ?? 0)>0){
        $cur=intval($p["curfd"] ?? 0); $max=intval($p["maxfd"]);
        $health=max(0,100-(($cur/$max)*100));
        $fd="$cur / $max <span class='mnt-ok'>(".sprintf("%.0f",$health)."% {health})</span>";
    }

    $h="<div class='ak-card mnt-tile'>";
    $h.="<div class='mnt-tile-head'><span class='ak-kicker'>{APP_MONIT}</span>";
    $h.="<button type='button' class='ak-btn' onclick=\"Loadjs('$page?restart-monit-do=yes')\">";
    $h.="<span class='mnt-btn-ico'><i class='fas fa-rotate'></i></span>{restart}</button></div>";
    $h.="<div class='mnt-tile-state'>$state";
    if($uptime<>""){$h.="<span class='mnt-sub'>{uptime} : <strong>".htmlspecialchars($uptime)."</strong></span>";}
    $h.="</div>";
    $h.="<div class='mnt-kpis'>";
    $h.="<div><span class='mnt-kpi-l'>{memory}</span><span class='mnt-kpi-v'>$mem</span></div>";
    $h.="<div><span class='mnt-kpi-l'>{monit_file_descriptors}</span><span class='mnt-kpi-v'>$fd</span></div>";
    $h.="</div></div>";
    return $h;
}

/** "14 days 6 hours 22 min" -> "14d 6h 22mn" (3 unites au plus). */
function mnt_uptime($uptime):string{
    $units=array("year"=>"y","month"=>"mo","week"=>"w","day"=>"d","hour"=>"h","min"=>"mn","sec"=>"s");
    $out=array();
    if(preg_match_all('#([0-9]+)\s*([a-zA-Z]+)#',$uptime,$re,PREG_SET_ORDER)){
        foreach($re as $m){
            $unit=strtolower($m[2]);
            foreach($units as $needle=>$abbr){
                if(strpos($unit,$needle)===0){$out[]=$m[1].$abbr;break;}
            }
            if(count($out)>=3){break;}
        }
    }
    if(count($out)==0){return trim($uptime);}
    return @implode(" ",$out);
}

/** Tuile 2 : incidents enregistres (file de rapports de performances). */
function mnt_tile_incidents():string{
    $tpl=new template_admin();
    $count=0; $last=0;
    if(is_file("/home/artica/SQLITE_TEMP/system.perf.queue.db")){
        $q=new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
        $count=intval($q->COUNT_ROWS("perfs_queue"));
        if($count>0){
            $ligne=$q->mysqli_fetch_array("SELECT MAX(zDate) AS last FROM perfs_queue");
            if(is_array($ligne)){$last=intval($ligne["last"] ?? 0);}
        }
    }

    $h="<div class='ak-card mnt-tile'>";
    $h.="<div class='mnt-tile-head'><span class='ak-kicker'>{incidents}</span></div>";
    $h.="<div class='mnt-inc'>";
    $h.="<span class='mnt-big'>$count</span><div>";
    if($count==0){
        $h.="<span class='ak-pill ak-ok'><i class='fas fa-circle-check'></i> {monit_no_incident}</span>";
    }else{
        $h.="<span class='ak-pill ak-warn'><i class='fas fa-triangle-exclamation'></i> $count {monit_incidents_to_review}</span>";
        if($last>0){
            $h.="<span class='mnt-sub mnt-sub-block'>{monit_last_incident} : ".htmlspecialchars($tpl->time_to_date($last,true))."</span>";
        }
    }
    $h.="</div></div>";
    $h.="<div class='mnt-tile-foot'><span></span>";
    $h.="<a href='javascript:blur();' class='mnt-link' onclick='MntGoIncidents()'>{monit_view_incidents} <span aria-hidden='true'>&rarr;</span></a>";
    $h.="</div></div>";
    return $h;
}

/** Les 7 regles ecrites dans monitrc, et si chacune est armee. */
function mnt_thresholds($v):array{
    return array(
        array("{if_system_load_exceed} (1 min)",$v["MonitReportLoadVG1mn"]>0),
        array("{if_system_load_exceed} (5 min)",$v["MonitReportLoadVG5mn"]>0),
        array("{if_system_load_exceed} (15 min)",$v["MonitReportLoadVG15mn"]>0),
        array("{if_system_cpu_exceed}",$v["MonitCPUUsage"]>0),
        array("{if_system_memory_exceed}",$v["MonitMemUsage"]>0),
        array("{purge_kernel_memory_cache_when_exceed}",$v["MonitMemPurgeCache"]>5),
        array("{warn_if_free_space_less_than}",$v["MonitSpaceFree"]>0),
    );
}

/** Tuile 3 : combien de seuils sont reellement surveilles. */
function mnt_tile_coverage($v):string{
    $rules=mnt_thresholds($v);
    $total=count($rules); $on=0; $seg="";
    foreach($rules as $r){
        if($r[1]){$on++;}
        $state=$r[1]?"{active2}":"{no_monitoring}";
        $seg.="<span class='mnt-seg".($r[1]?"":" mnt-seg-off")."' title=\"$r[0] : $state\"></span>";
    }
    $off=$total-$on;
    $h="<div class='ak-card mnt-tile'>";
    $h.="<div class='mnt-tile-head'><span class='ak-kicker'>{monit_coverage}</span></div>";
    $h.="<div class='mnt-cov'><span class='mnt-big mnt-big-sm'>$on / $total</span> <span class='mnt-sub'>{monit_thresholds_monitored}</span></div>";
    $h.="<div class='mnt-segs'>$seg</div>";
    $h.="<div class='mnt-tile-foot'>";
    $h.="<span class='mnt-legend'><span class='mnt-sq'></span>$on {active2}</span>";
    $h.="<span class='mnt-legend'><span class='mnt-sq mnt-sq-off'></span>$off {monit_unmonitored}</span>";
    $h.="</div></div>";
    return $h;
}

/** Carte A : seuils de charge moyenne et plafond des taches de fond. */
function mnt_card_load($v):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?params-monit-js=yes')";
    $jsGeneral="Loadjs('$page?params-general-js=yes')";

    $rows="";
    foreach(array(array("1mn","1 min"),array("5mn","5 min"),array("15mn","15 min")) as $p){
        $val=$v["MonitReportLoadVG".$p[0]];
        if($val==0){
            $rows.=mnt_row($js,"{if_system_load_exceed} ($p[1])",mnt_off_pill(),"<span class='mnt-dash'>—</span>",true);
            continue;
        }
        $rows.=mnt_row($js,"{if_system_load_exceed} ($p[1])","<span class='mnt-num'>$val</span>",
            mnt_duration($v["MonitReportLoadVG".$p[0]."Cycles"]));
    }

    $h="<section class='ak-card mnt-card'>";
    $h.=mnt_card_head("fas fa-gauge-high","{monit_card_load}","",mnt_edit_button($js));
    $h.="<p class='mnt-explain'>{monit_card_load_explain}</p>";
    $h.="<div class='mnt-table'><div class='mnt-thead'><div>{period}</div><div>{threshold}</div><div>{duration}</div></div>$rows</div>";
    $h.="<div class='mnt-table mnt-table-gap'>";
    $h.=mnt_row($jsGeneral,"{max_system_load}<span class='mnt-row-sub'>{monit_max_load_explain}</span>",
        "<span class='mnt-num'>".$v["systemMaxOverloaded"]."</span>","",false,false);
    $h.="</div>";
    $h.=mnt_card_load_foot($v);
    $h.="</section>";
    return $h;
}

/** Pied de la carte A : charge mesuree a l'instant sur l'appliance. */
function mnt_card_load_foot($v):string{
    $load=function_exists("sys_getloadavg")?@sys_getloadavg():false;
    if(!is_array($load) || count($load)<3){return "";}
    $txt=sprintf("%.2f / %.2f / %.2f",$load[0],$load[1],$load[2]);
    $high=$v["MonitReportLoadVG1mn"]>0 && $load[0]>=$v["MonitReportLoadVG1mn"];
    $flag=$high?"<span class='mnt-warn-txt'>{high}</span>":"<span class='mnt-ok'>{normal}</span>";
    return "<div class='mnt-card-foot'><span>{monit_load_now} : <strong>$txt</strong></span>$flag</div>";
}

/** Carte B : seuils CPU, memoire et espace disque. */
function mnt_card_resources($v):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?params-monit-js=yes')";
    $rows="";
    $items=array(
        array("{if_system_cpu_exceed}","MonitCPUUsage"),
        array("{if_system_memory_exceed}","MonitMemUsage"),
        array("{warn_if_free_space_less_than}","MonitSpaceFree"),
    );
    foreach($items as $it){
        $val=$v[$it[1]];
        if($val==0){
            $rows.=mnt_row($js,$it[0],mnt_off_pill(),"<span class='mnt-dash'>—</span>",true,false);
            continue;
        }
        $rows.=mnt_row($js,$it[0],mnt_percent($val),mnt_duration($v[$it[1]."Cycles"]),false,false);
    }
    $h="<section class='ak-card mnt-card'>";
    $h.=mnt_card_head("fas fa-microchip","{monit_card_resources}","",mnt_edit_button($js));
    $h.="<p class='mnt-explain'>{monit_card_resources_explain}</p>";
    $h.="<div class='mnt-table'><div class='mnt-thead'><div>{resource}</div><div>{threshold}</div><div>{duration}</div></div>$rows</div>";
    $h.=mnt_card_resources_foot();
    $h.="</section>";
    return $h;
}

/** Pied de la carte B : memoire utilisee et espace libre de / a l'instant. */
function mnt_card_resources_foot():string{
    $parts=array();
    $mem=@file_get_contents("/proc/meminfo");
    if(is_string($mem) && preg_match('#MemTotal:\s+([0-9]+)#',$mem,$t) && preg_match('#MemAvailable:\s+([0-9]+)#',$mem,$a)){
        if(intval($t[1])>0){
            $used=100-((intval($a[1])/intval($t[1]))*100);
            $parts[]="{monit_memory_now} : <strong>".sprintf("%.0f",$used)."%</strong>";
        }
    }
    $total=@disk_total_space("/");
    $free=@disk_free_space("/");
    if($total>0 && $free!==false){
        $parts[]="{free_space} (/) : <strong>".sprintf("%.0f",($free/$total)*100)."%</strong>";
    }
    if(count($parts)==0){return "";}
    return "<div class='mnt-card-foot'><span>".@implode(" &nbsp;·&nbsp; ",$parts)."</span></div>";
}

/** Carte C : action automatique, purge du cache memoire du noyau. */
function mnt_card_purge($v):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?params-monit-js=yes')";
    $off=$v["MonitMemPurgeCache"]<=5;
    $value=$off?mnt_off_pill():mnt_percent($v["MonitMemPurgeCache"]);
    $dur=$off?"<span class='mnt-dash'>—</span>":mnt_duration($v["MonitMemPurgeCacheCycles"]);
    $h="<section class='ak-card mnt-card'>";
    $h.=mnt_card_head("fas fa-broom","{monit_card_purge}","<span class='ak-tag ak-accent'>{action}</span>",mnt_edit_button($js));
    $h.="<p class='mnt-explain'>{monit_card_purge_explain}</p>";
    $h.="<div class='mnt-table'><div class='mnt-thead'><div>{action}</div><div>{threshold}</div><div>{duration}</div></div>";
    $h.=mnt_row($js,"{purge_kernel_memory_cache_when_exceed}",$value,$dur,$off,false);
    $h.="</div></section>";
    return $h;
}

/** Carte D : les deux reglages qui redemarrent l'appliance. */
function mnt_card_reboot($v):string{
    $page=CurrentPageName();
    $jsDaily="Loadjs('$page?reboot-js=yes')";
    $jsZombies="Loadjs('$page?params-general-js=yes')";
    $daily=$v["DailyReboot"]==1;

    $h="<section class='ak-card mnt-card mnt-card-impact'>";
    $h.=mnt_card_head("fas fa-power-off","{monit_card_reboot}",
        "<span class='ak-tag ak-warn'>{monit_reboot_impact}</span>","","ak-warn");
    $h.="<p class='mnt-explain'>{monit_card_reboot_explain}</p>";

    $h.="<div class='mnt-sub-card'>";
    $h.="<span class='ak-sw".($daily?" on":"")."' aria-hidden='true'></span>";
    $h.="<div class='mnt-sub-body'><div class='mnt-sub-title'>{DAILY_REBOOT} ";
    $h.=$daily?"<span class='ak-tag ak-ok'>{active2}</span>":"<span class='ak-tag'>{inactive2}</span>";
    $h.="</div><div class='mnt-sub-text'>{DAILY_REBOOT_SERVER_EXPLAIN}</div>";
    if($daily){$h.="<div class='mnt-hour'>".htmlspecialchars($v["DailyRebootHour"])."</div>";}
    $h.="</div>".mnt_edit_button($jsDaily)."</div>";

    $h.="<div class='mnt-sub-card'>";
    $h.="<span class='mnt-sub-ico'><i class='fas fa-ghost'></i></span>";
    $h.="<div class='mnt-sub-body'><div class='mnt-sub-title'>{monit_zombies_reboot} <span class='ak-tag ak-ok'>{active2}</span></div>";
    $h.="<div class='mnt-sub-text'>{monit_zombies_reboot_explain}</div>";
    $h.="<div class='mnt-chip'><span class='mnt-num'>".$v["systemctlDefuncReboot"]."</span> {monit_zombies_unit}</div>";
    $h.="</div>".mnt_edit_button($jsZombies)."</div>";

    $h.="</section>";
    return $h;
}

/** Carte E : calcul de la bande passante Internet (pleine largeur). */
function mnt_card_bandwidth($v):string{
    $on=$v["EnableBandwithCalculation"]==1;
    $state=$on?"<span class='ak-tag ak-ok'>{enabled}</span>":"<span class='ak-tag'>{disabled}</span>";
    $h="<section class='ak-card mnt-card mnt-card-full mnt-card-row'>";
    $h.="<span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-chart-line'></i></span>";
    $h.="<div class='mnt-sub-body'><div class='mnt-card-title'><h2>{tests_speed}</h2>$state</div>";
    $h.="<p class='mnt-explain mnt-explain-tight'>{monit_bandwidth_explain}</p></div>";
    $h.=mnt_edit_button("Loadjs('fw.system.status.bandwidth.php')");
    $h.="</section>";
    return $h;
}

/** JS de la page : clavier sur les lignes, lien vers l'onglet Incidents. */
function mnt_js($page):string{
    return "<script>
function MntKey(e,el){ if(e.keyCode==13||e.keyCode==32){ e.preventDefault(); el.click(); } }
function MntGoIncidents(){
  var a=document.querySelector('a[data-url=\"$page?incidents=yes\"]');
  if(a){ a.click(); }
}
</script>";
}

/** CSS du kit de la page (une fois par requete). */
function mnt_style():string{
    if(isset($GLOBALS["MNT_STYLE_DONE"])){return "";}
    $GLOBALS["MNT_STYLE_DONE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.mnt-wrap{margin-top:20px}
.mnt-status{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin-bottom:24px}
.mnt-tile,.mnt-card{transition:border-color .15s}
.mnt-tile{padding:20px 22px;display:flex;flex-direction:column;justify-content:space-between;gap:14px;min-width:0}
.mnt-card:hover,.mnt-tile:hover{border-color:#cbd5e1}
.mnt-tile-head{display:flex;align-items:center;justify-content:space-between;gap:10px}
.mnt-tile-state{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.mnt-tile-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:13px;color:#3d4947;border-top:1px solid #f1f5f9;padding-top:12px}
.mnt-sub{font-size:13px;color:#3d4947}
.mnt-sub strong{color:#111d23;font-variant-numeric:tabular-nums}
.mnt-sub-block{display:block;margin-top:6px}
.mnt-kpis{display:grid;grid-template-columns:1fr 1fr;gap:16px;border-top:1px solid #f1f5f9;padding-top:12px}
.mnt-kpi-l{display:block;font-size:13px;color:#3d4947}
.mnt-kpi-v{display:block;font-size:15px;font-weight:600;color:#111d23;margin-top:2px;font-variant-numeric:tabular-nums}
.mnt-ok{color:#006a62;font-weight:600}
.mnt-warn-txt{color:#865300;font-weight:600}
.mnt-inc{display:flex;align-items:center;gap:18px}
.mnt-big{font-size:40px;line-height:1;font-weight:700;color:#111d23;font-variant-numeric:tabular-nums}
.mnt-big-sm{font-size:26px}
.mnt-link{color:#006a62;font-weight:600;text-decoration:none}
.mnt-link:hover{text-decoration:underline;color:#00504a}
.mnt-link:focus-visible,.mnt-row:focus-visible{outline:2px solid $dark;outline-offset:2px}
.mnt-cov{display:flex;align-items:baseline;gap:8px}
.mnt-segs{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
.mnt-seg{height:12px;border-radius:3px;background:$accent}
.mnt-seg-off{background:#e2e8f0}
.mnt-legend{display:inline-flex;align-items:center;gap:6px}
.mnt-sq{width:10px;height:10px;border-radius:2px;background:$accent}
.mnt-sq-off{background:#e2e8f0}
.mnt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;align-items:stretch}
.mnt-card{padding:24px;min-width:0}
.mnt-card-full{grid-column:1 / -1}
.mnt-card-impact{border-left:4px solid #d98324}
.mnt-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.mnt-card-title{display:flex;align-items:center;gap:12px;flex-wrap:wrap;min-width:0}
.mnt-card-title h2{font-size:18px;font-weight:600;color:#111d23;margin:0}
.mnt-explain{font-size:14px;color:#3d4947;margin:12px 0 18px 0;line-height:1.5}
.mnt-explain-tight{margin:4px 0 0 0}
.mnt-btn-ico{font-size:12px;display:inline-flex}
.mnt-table{border:1px solid #e2e8f0;border-radius:10px;overflow:hidden}
.mnt-table-gap{margin-top:12px}
.mnt-thead,.mnt-row{display:grid;grid-template-columns:minmax(0,1fr) 160px 170px;gap:16px;align-items:center;padding:0 18px}
.mnt-thead{height:42px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.mnt-thead div:nth-child(2){text-align:right}
.mnt-row{min-height:50px;padding-top:8px;padding-bottom:8px;border-top:1px solid #f1f5f9;cursor:pointer;transition:background .15s}
.mnt-thead+.mnt-row{border-top:0}
.mnt-table>.mnt-row:first-child{border-top:0}
.mnt-row:hover{background:#f8fafc}
.mnt-row-lbl{font-size:15px;color:#111d23;display:flex;align-items:center;gap:10px;min-width:0}
.mnt-row-txt{min-width:0}
.mnt-row-off .mnt-row-lbl{color:#3d4947}
.mnt-row-sub{display:block;font-size:13px;color:#3d4947;margin-top:2px}
.mnt-dot{width:7px;height:7px;border-radius:50%;background:$accent;flex:0 0 auto}
.mnt-row-off .mnt-dot{background:#bcc9c6}
.mnt-row-val{display:flex;align-items:center;justify-content:flex-end;gap:10px}
.mnt-row-dur{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:14px;color:#3d4947}
.mnt-dur strong{color:#111d23;font-weight:600}
.mnt-chev{font-size:11px;color:#bcc9c6;opacity:0;transition:opacity .15s}
.mnt-row:hover .mnt-chev,.mnt-row:focus-visible .mnt-chev{opacity:1}
.mnt-num{font-size:16px;font-weight:700;color:#111d23;font-variant-numeric:tabular-nums}
.mnt-unit{font-size:13px;font-weight:400;color:#3d4947;margin-left:2px}
.mnt-bar{width:80px;display:inline-block}
.mnt-dash{color:#6d7a77}
.mnt-card-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:18px;padding-top:14px;border-top:1px solid #f1f5f9;font-size:13px;color:#3d4947}
.mnt-card-foot strong{color:#111d23;font-variant-numeric:tabular-nums}
.mnt-sub-card{display:flex;align-items:flex-start;gap:16px;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;margin-top:12px}
.mnt-sub-body{flex:1 1 auto;min-width:0}
.mnt-sub-title{font-size:15px;font-weight:600;color:#111d23;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.mnt-sub-text{font-size:13px;color:#3d4947;margin-top:4px;line-height:1.5}
.mnt-sub-ico{width:34px;height:20px;display:inline-flex;align-items:center;justify-content:center;color:#3d4947;font-size:15px;flex:0 0 auto}
.mnt-hour{font-size:22px;font-weight:700;color:#111d23;margin-top:6px;font-variant-numeric:tabular-nums}
.mnt-chip{display:inline-flex;align-items:baseline;gap:6px;margin-top:8px;padding:4px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;color:#3d4947}
.mnt-card-row{display:flex;align-items:center;gap:16px}
@media(max-width:1100px){.mnt-status{grid-template-columns:1fr}.mnt-grid{grid-template-columns:1fr}}
</style>";
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

function incident_delete_js(){
    $tpl=new template_admin();
    // $time est un horodatage: il alimente un selecteur et un DELETE.
    $time=intval($_GET["incident-delete"]);
    $title=$tpl->time_to_date($time,true);
    $q=new lib_sqlite(minc_db());
    $l=$q->mysqli_fetch_array("SELECT subject FROM perfs_queue WHERE zDate=$time");
    $subject=preg_replace('/[^A-Za-z0-9 .,:;%()\/_@+=-]/','',(string)($l["subject"] ?? ""));
    if(strlen($subject)>90){$subject=substr($subject,0,90)."...";}
    if($subject!==""){$title.=" - $subject";}
    $tpl->js_confirm_delete($title,"incident-delete",$time,"MincRemove($time)");
}

// ── Onglet Incidents (maquette stitch_network_appliance_incident_console, 2026-11-06) ──
// Une ligne de perfs_queue = un rapport : zDate (horodatage, identifiant), subject
// (texte libre) et file (archive .tar.gz en base64). Ni gravité, ni catégorie, ni
// statut : le sujet est la seule description. La base est vidée d'un bloc au-delà
// de 1 000 Mo (articarest logrotate/Cleaning.go perfQueueSizeCapMB).

function minc_db():string{ return "/home/artica/SQLITE_TEMP/system.perf.queue.db"; }
function minc_cap_bytes():int{ return 1000*1024*1024; }
/** Au-delà, l'archive n'est pas ouverte pour l'aperçu (décodage en mémoire). */
function minc_preview_max():int{ return 64*1024*1024; }

/** Texte libre venu de la base : échappé, et les accolades/crochets neutralisés pour le moteur de jetons. */
function minc_txt($s):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}
function minc_tr(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}
function minc_bytes($b):string{
    $b=floatval($b); $u=array("B","KB","MB","GB"); $i=0;
    while($b>=1024 && $i<count($u)-1){$b=$b/1024;$i++;}
    return ($i==0?number_format($b,0):number_format($b,$b>=100?0:1))." ".$u[$i];
}
function minc_ago(int $ts,int $now):string{
    $d=$now-$ts;
    if($d<60){return minc_tr("minc_just_now");}
    if($d<3600){return minc_tr("minc_ago_min",array(intdiv($d,60)));}
    if($d<86400){return minc_tr("minc_ago_h",array(intdiv($d,3600)));}
    if($d<172800){return minc_tr("minc_yesterday");}
    return minc_tr("minc_ago_d",array(intdiv($d,86400)));
}
function minc_accents():array{
    $ac=array("accent"=>"#0f766e","accent_dark"=>"#115e59","accent_soft"=>"rgba(15,118,110,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

// ── Modèle ──

function minc_model():array{
    $now=time();
    $m=array("ok"=>is_file(minc_db()),"total"=>0,"oldest"=>0,"last_ts"=>0,"last_subject"=>"",
        "last24"=>0,"days"=>array(),"size"=>0,"now"=>$now);
    $today=strtotime(date("Y-m-d 00:00:00",$now));
    for($i=29;$i>=0;$i--){ $m["days"][date("Y-m-d",$today-$i*86400)]=0; }
    if(!$m["ok"]){return $m;}
    foreach(array("","-wal") as $sfx){
        if(is_file(minc_db().$sfx)){$m["size"]+=intval(@filesize(minc_db().$sfx));}
    }
    $q=new lib_sqlite(minc_db());
    $l=$q->mysqli_fetch_array("SELECT count(*) AS n, min(zDate) AS oldest FROM perfs_queue");
    $m["total"]=intval($l["n"] ?? 0);
    $m["oldest"]=intval($l["oldest"] ?? 0);
    if($m["total"]==0){return $m;}
    $l=$q->mysqli_fetch_array("SELECT zDate, subject FROM perfs_queue ORDER BY zDate DESC LIMIT 1");
    $m["last_ts"]=intval($l["zDate"] ?? 0);
    $m["last_subject"]=(string)($l["subject"] ?? "");
    $since=$today-29*86400;
    $results=$q->QUERY_SQL("SELECT zDate FROM perfs_queue WHERE zDate>=".intval($since));
    if(!is_array($results)){$results=array();}
    foreach($results as $r){
        $ts=intval($r["zDate"]);
        $k=date("Y-m-d",$ts);
        if(isset($m["days"][$k])){$m["days"][$k]++;}
        if($ts>=$now-86400){$m["last24"]++;}
    }
    return $m;
}

// ── Composants ──

function minc_style():string{
    if(!empty($GLOBALS["MINC_CSS_DONE"])){return "";}
    $GLOBALS["MINC_CSS_DONE"]=true;
    $ac=minc_accents(); $a=$ac["accent"]; $ad=$ac["accent_dark"]; $as=$ac["accent_soft"];
    return "<style>
.minc-wrap{margin-top:12px;font-size:13px}
.minc-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
.minc-tile{padding:14px 18px;min-width:0;display:flex;flex-direction:column;min-height:112px}
.minc-th{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.minc-lbl{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;line-height:16px}
.minc-val{display:flex;align-items:baseline;gap:8px;margin-top:4px}
.minc-v{font-size:28px;font-weight:700;letter-spacing:-.02em;line-height:34px;font-variant-numeric:tabular-nums;white-space:nowrap}
.minc-v.warn{color:#b45309}.minc-v.crit{color:#b91c1c}
.minc-u{font-size:13px;color:#64748b;white-space:nowrap}
.minc-ft{margin-top:auto;padding-top:6px;font-size:13px;color:#475569;line-height:18px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.minc-ft b{color:#0f172a;font-weight:600}
.minc-sp{display:block;margin-left:auto}
.minc-act{padding:16px 20px;margin-bottom:16px}
.minc-act-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px}
.minc-t{font-size:16px;font-weight:600;line-height:24px}
.minc-t i{color:$ad;margin-right:8px}
.minc-s{font-size:12px;color:#64748b;line-height:16px}
.minc-lg{display:flex;gap:14px;align-items:center;font-size:13px;color:#475569}
.minc-lg i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px;vertical-align:-1px}
.minc-chart{display:flex;gap:10px}
.minc-y{display:flex;flex-direction:column;justify-content:space-between;height:120px;font-size:11px;color:#94a3b8;text-align:right;width:22px;font-variant-numeric:tabular-nums}
.minc-plot{flex:1;min-width:0}
.minc-bars{position:relative;display:flex;align-items:flex-end;gap:4px;height:120px;border-bottom:1px solid #e2e8f0;background:linear-gradient(#f1f5f9,#f1f5f9) 0 0/100% 1px no-repeat,linear-gradient(#f1f5f9,#f1f5f9) 0 50%/100% 1px no-repeat}
.minc-bar{flex:1;min-width:0;height:100%;display:flex;align-items:flex-end;cursor:default}
.minc-bar span{display:block;width:100%;border-radius:3px 3px 0 0;background:$a;min-height:0}
.minc-bar.zero span{background:#e2e8f0;height:2px!important}
.minc-bar.today span{background:$ad}
.minc-bar:hover span{filter:brightness(.85)}
.minc-x{display:flex;gap:4px;margin-top:6px}
.minc-x span{flex:1;min-width:0;font-size:11px;color:#94a3b8;white-space:nowrap;overflow:visible}
.minc-x span.today{color:#0f172a;font-weight:600}
.minc-empty{justify-content:center;min-height:120px;font-size:13px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:10px;padding:16px}
.minc-empty .ok{color:#16a34a}
.minc-tbl-card{margin-top:4px}
.minc-tbar{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f1f5f9;color:#64748b;font-size:13px}
.minc-tbl td{vertical-align:top}
.minc-tbl tr.minc-r:hover td{background:#fafbfc}
.minc-tbl tr.minc-r.open td{background:#f8fafc}
.minc-date{font-weight:600;white-space:nowrap}
.minc-rel{font-size:12px;color:#64748b;margin-top:2px;white-space:nowrap}
.minc-subj{font-size:14px;line-height:21px;word-break:break-word;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.minc-r.open .minc-subj{-webkit-line-clamp:unset;display:block}
.minc-wrap button:focus-visible,.minc-wrap a:focus-visible{outline:2px solid $ad;outline-offset:2px}
.minc-d td{padding:0 20px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb}
.minc-panel{border-left:3px solid $a;padding:14px 0 0 16px}
.minc-ph{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
.minc-pt{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#334155}
.minc-pt i{color:$ad;margin-right:8px}
.minc-pb{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px}
.minc-files{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px 14px}
.minc-files-h{display:flex;justify-content:space-between;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;padding-bottom:6px}
.minc-files ul{list-style:none;margin:0;padding:0;max-height:260px;overflow:auto}
.minc-files li{display:flex;justify-content:space-between;gap:12px;padding:6px 0;border-top:1px solid #f1f5f9}
.minc-files li span:first-child{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.minc-files li i{color:#94a3b8;margin-right:6px}
.minc-files li .sz{color:#64748b;white-space:nowrap;font-variant-numeric:tabular-nums}
.minc-why{background:#0f172a;color:#e2e8f0;border-radius:10px;padding:12px 14px;min-width:0}
.minc-why-h{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#5eead4;margin-bottom:8px}
.minc-why pre{margin:0;background:transparent;border:0;padding:0;color:#e2e8f0;white-space:pre-wrap;word-break:break-word;font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12px;line-height:19px;max-height:260px;overflow:auto}
.minc-why.none{background:#f8fafc;color:#64748b;border:1px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;text-align:center}
.minc-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 20px;font-size:13px;color:#64748b}
.minc-note{display:flex;align-items:center;gap:8px;margin-top:14px;font-size:13px;color:#64748b}
@media(max-width:1200px){.minc-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.minc-pb{grid-template-columns:minmax(0,1fr)}}
@media(prefers-reduced-motion:no-preference){.minc-panel{animation:mincIn .15s ease-out}}
@keyframes mincIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
</style>";
}

function minc_tile_total(array $m):string{
    $foot=$m["oldest"]>0?minc_tr("minc_oldest",array("<b>".date("d M Y",$m["oldest"])."</b>")):"&mdash;";
    return "<div class='ak-card minc-tile'><div class='minc-th'><span class='minc-lbl'>{incidents}</span>"
        ."<i class='fas fa-bell' style='color:#94a3b8' aria-hidden='true'></i></div>"
        ."<div class='minc-val'><span class='minc-v'>".number_format($m["total"])."</span><span class='minc-u'>{minc_total_stored}</span></div>"
        ."<div class='minc-ft'>$foot</div></div>";
}
/** Mini-barres (30 jours) façon peity, aujourd'hui en ambre s'il y a eu un incident. */
function minc_spark(array $days):string{
    $vals=array_values($days); $n=count($vals); $max=max(1,max($vals));
    $w=4; $gap=1; $h=28; $out="";
    foreach($vals as $i=>$v){
        $bh=$v>0?max(2,round($v*$h/$max)):1;
        $fill=($i==$n-1 && $v>0)?"#d97706":($v>0?"#94a3b8":"#e2e8f0");
        $out.=sprintf("<rect x='%d' y='%d' width='%d' height='%d' rx='0.5' fill='%s'/>",$i*($w+$gap),$h-$bh,$w,$bh,$fill);
    }
    $tw=$n*($w+$gap);
    return "<svg class='minc-sp' width='$tw' height='$h' viewBox='0 0 $tw $h' aria-hidden='true'>$out</svg>";
}
function minc_tile_24h(array $m):string{
    $cls=$m["last24"]>0?" warn":"";
    $chip=$m["last24"]>0?"<span class='ak-pill ak-warn'><i class='fas fa-clock' aria-hidden='true'></i>{minc_recent}</span>":"<span class='ak-pill ak-ok'><i class='fas fa-check' aria-hidden='true'></i>{minc_quiet}</span>";
    $sum30=array_sum($m["days"]);
    return "<div class='ak-card minc-tile'><div class='minc-th'><span class='minc-lbl'>{minc_last24}</span>$chip</div>"
        ."<div class='minc-val'><span class='minc-v$cls'>".number_format($m["last24"])."</span>".minc_spark($m["days"])."</div>"
        ."<div class='minc-ft'>".minc_tr("minc_last30",array("<b>".number_format($sum30)."</b>"))."</div></div>";
}
function minc_tile_last(array $m):string{
    if($m["last_ts"]==0){
        return "<div class='ak-card minc-tile'><div class='minc-th'><span class='minc-lbl'>{minc_last_incident}</span></div>"
            ."<div class='minc-val'><span class='minc-v'>&mdash;</span></div><div class='minc-ft'>{minc_empty}</div></div>";
    }
    $subj=minc_txt($m["last_subject"]);
    return "<div class='ak-card minc-tile'><div class='minc-th'><span class='minc-lbl'>{minc_last_incident}</span>"
        ."<span class='minc-u'><i class='far fa-clock' aria-hidden='true'></i> ".date("H:i:s",$m["last_ts"])."</span></div>"
        ."<div class='minc-val'><span class='minc-v'>".minc_ago($m["last_ts"],$m["now"])."</span></div>"
        ."<div class='minc-ft ak-mono' title=\"$subj\">$subj</div></div>";
}
function minc_tile_storage(array $m):string{
    $cap=minc_cap_bytes();
    $pct=$cap>0?min(100,$m["size"]*100/$cap):0;
    $tone=$pct>=90?"#dc2626":($pct>=75?"#d97706":minc_accents()["accent"]);
    $cls=$pct>=90?" crit":($pct>=75?" warn":"");
    $r=14; $c=2*M_PI*$r; $len=$c*$pct/100;
    $ring="<svg width='32' height='32' viewBox='0 0 36 36' role='img' aria-label='".number_format($pct,1)." %'>"
        ."<circle cx='18' cy='18' r='$r' fill='none' stroke='#e2e8f0' stroke-width='4.5'/>"
        .sprintf("<circle cx='18' cy='18' r='%d' fill='none' stroke='%s' stroke-width='4.5' stroke-dasharray='%.2f %.2f' transform='rotate(-90 18 18)' stroke-linecap='round'/>",$r,$tone,$len,$c)."</svg>";
    list($v,$u)=explode(" ",minc_bytes($m["size"]),2);
    return "<div class='ak-card minc-tile'><div class='minc-th'><span class='minc-lbl'>{minc_storage}</span>"
        ."<span class='minc-u'>".minc_tr("minc_capacity",array(number_format($pct,1)." %"))."</span></div>"
        ."<div class='minc-val'><span class='minc-v$cls'>$v</span><span class='minc-u'>$u ".minc_tr("minc_of_cap",array("1,000 MB"))."</span><span class='minc-sp'>$ring</span></div>"
        ."<div class='minc-ft' title=\"".strip_tags(minc_tr("minc_cap_note",array("1,000 MB")))."\">".minc_tr("minc_cap_note",array("1,000 MB"))."</div></div>";
}
function minc_tiles(array $m):string{
    return "<div class='minc-tiles'>".minc_tile_total($m).minc_tile_24h($m).minc_tile_last($m).minc_tile_storage($m)."</div>";
}

/** Barres par jour sur 30 jours, mesurées contre le jour le plus chargé. */
function minc_activity_card(array $m):string{
    $ac=minc_accents();
    $vals=$m["days"]; $max=max($vals); $sum=array_sum($vals);
    $h ="<div class='ak-card minc-act'><div class='minc-act-h'><div><div class='minc-t'><i class='fas fa-chart-column' aria-hidden='true'></i>{minc_per_day}</div>";
    $h.="<div class='minc-s'>{minc_last30_sub}</div></div>";
    if($sum>0){
        $h.="<div class='minc-lg'><span><i style='background:{$ac["accent"]}'></i>{minc_daily}</span><span><i style='background:{$ac["accent_dark"]}'></i>{minc_today}</span>"
            ."<span>".minc_tr("minc_peak",array("<b>".number_format($max)."</b>"))."</span></div>";
    }
    $h.="</div>";
    if($sum<=0){
        return $h."<div class='ak-empty-c minc-empty'><i class='fas fa-circle-check ok' aria-hidden='true'></i>{minc_no_30}</div></div>";
    }
    $top=max(2,$max); $mid=intdiv($top,2);
    $bars=array(); $xs=array(); $i=0; $n=count($vals);
    foreach($vals as $day=>$v){
        $ts=strtotime($day);
        $label=date("d M",$ts);
        $today=($i==$n-1);
        $cls=($v==0?" zero":"").($today?" today":"");
        $tip=minc_tr("minc_tip",array($label,number_format($v)));
        $bars[]="<div class='minc-bar$cls' title=\"$tip\" aria-label=\"$tip\" role='img'><span style='height:".sprintf('%.1f',$v*100/$top)."%'></span></div>";
        $show=($i%5==0) || $today;
        $xs[]="<span".($today?" class='today'":"").">".($show?($today?"{minc_today}":$label):"")."</span>";
        $i++;
    }
    $h.="<div class='minc-chart'><div class='minc-y' aria-hidden='true'><span>$top</span><span>$mid</span><span>0</span></div>"
        ."<div class='minc-plot'><div class='minc-bars'>".implode("",$bars)."</div><div class='minc-x' aria-hidden='true'>".implode("",$xs)."</div></div></div>";
    return $h."</div>";
}

/** ?incidents-summary=yes : tuiles + barres, rechargées après chaque suppression. */
function minc_summary():bool{
    $tpl=new template_admin();
    $m=minc_model();
    echo minc_style().$tpl->_ENGINE_parse_body(minc_tiles($m).minc_activity_card($m));
    return true;
}

function incidents_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]="<div class='minc-wrap ak'><div id='minc-summary'></div>";
    $html[]=$tpl->search_block($page,"","","","&search-incidents=yes");
    $html[]="<div class='minc-note'><i class='fas fa-circle-info' aria-hidden='true'></i>{minc_support_note}</div>";
    $html[]="</div>";
    $html[]="<script>LoadAjaxSilent('minc-summary','$page?incidents-summary=yes');</script>";
    echo minc_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

/** Une ligne de rapport et sa ligne de détail (vide tant qu'on ne la déplie pas). */
function minc_row(string $page,int $time,string $subject,int $b64len,int $now,string $function):string{
    $tpl=new template_admin();
    $size=intval($b64len*3/4);
    $show=minc_tr("minc_show"); $dl=minc_tr("minc_download"); $del=minc_tr("minc_delete");
    $h ="<tr class='minc-r' id='minc-r-$time' data-t='$time'>";
    $h.="<td style='width:190px'><div class='minc-date'>".$tpl->time_to_date($time,true)."</div><div class='minc-rel'>".minc_ago($time,$now)."</div></td>";
    $h.="<td><div class='minc-subj'>".minc_txt($subject)."</div></td>";
    $h.="<td class='ak-end' style='width:110px'><span class='ak-tag'>".minc_bytes($size)."</span></td>";
    $h.="<td style='width:140px'><div class='ak-acts'>";
    $h.="<button type='button' class='ak-act' aria-expanded='false' aria-controls='minc-d-$time' title=\"$show\" aria-label=\"$show\" OnClick=\"MincToggle($time,this)\"><i class='fas fa-chevron-down' aria-hidden='true'></i></button>";
    $h.="<button type='button' class='ak-act' title=\"$dl\" aria-label=\"$dl\" OnClick=\"document.location.href='$page?incident-download=$time'\"><i class='fas fa-download' aria-hidden='true'></i></button>";
    $h.="<button type='button' class='ak-act ak-danger' title=\"$del\" aria-label=\"$del\" OnClick=\"Loadjs('$page?incident-delete=$time&function=$function')\"><i class='fas fa-trash-can' aria-hidden='true'></i></button>";
    $h.="</div></td></tr>";
    $h.="<tr class='minc-d' id='minc-d-$time' data-t='$time' hidden><td colspan='4'><div id='minc-c-$time'></div></td></tr>";
    return $h;
}

function incidents_found(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Whitelist: $function part dans un OnClick sans echappement.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $now=time();

    $rows=array();
    if(is_file(minc_db())){
        $q=new lib_sqlite(minc_db());
        // CLEAN_BAD_XSS retire deja la quote, mais l'echappement doit etre
        // explicite: la protection ne doit pas dependre d'un filtre XSS.
        $search=$tpl->CLEAN_BAD_XSS((string)($_GET["search"] ?? ""));
        $like="%".$q->sqlite_escape_string2(str_replace("*","%",trim($search)))."%";
        $results=$q->QUERY_SQL("SELECT subject,zDate,length(file) AS b64 FROM perfs_queue WHERE subject LIKE '$like' ORDER BY zDate DESC LIMIT 250");
        if(!$q->ok){
            echo $tpl->div_error(htmlspecialchars($q->mysql_error));
        }
        if(!is_array($results)){$results=array();}
        foreach($results as $ligne){
            $rows[]=minc_row($page,intval($ligne["zDate"]),(string)$ligne["subject"],intval($ligne["b64"]),$now,$function);
        }
    }

    $html=array();
    $html[]="<div class='minc-wrap ak'><div class='ak-card minc-tbl-card'>";
    $html[]="<div class='minc-tbar'><span><i class='fas fa-magnifying-glass' aria-hidden='true'></i> {minc_search_note}</span></div>";
    if(count($rows)==0){
        $html[]="<div style='padding:20px'><div class='ak-empty-c minc-empty'><i class='fas fa-circle-check ok' aria-hidden='true'></i><b>{minc_empty}</b><span>{minc_empty_help}</span></div></div>";
    }else{
        $html[]="<table class='ak-tbl minc-tbl'><thead><tr><th>{minc_time}</th><th>{minc_incident}</th><th class='ak-end'>{minc_report}</th><th class='ak-end'>{minc_actions}</th></tr></thead>";
        $html[]="<tbody id='minc-rows'>".implode("\n",$rows)."</tbody></table>";
        $html[]="<div class='minc-foot'><span id='minc-count'>".minc_tr("minc_showing",array(number_format(count($rows))))."</span><span class='ak-pg' id='minc-pager'></span></div>";
    }
    $html[]="</div></div>";

    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?remove-all-incidents-js=yes&function=$function')", ico_trash, "{minc_delete_all}");
    $TINY_ARRAY["TITLE"]="{incidents}";
    $TINY_ARRAY["ICO"]="fa fa-bell";
    $TINY_ARRAY["EXPL"]="{incidents_explain}";
    $TINY_ARRAY["URL"]="incidents";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>NoSpinner();\n$jstiny\n";
    // Les tuiles suivent toute relecture du tableau (recherche, suppression).
    $html[]="if(document.getElementById('minc-summary')){LoadAjaxSilent('minc-summary','$page?incidents-summary=yes');}";
    $html[]="</script>";
    echo minc_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).minc_js($page);
    return true;
}

/** Dépliage, suppression d'une ligne et pagination par rapport (la ligne de détail suit sa ligne). */
function minc_js(string $page):string{
    $flags=JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
    $L=json_encode(array("show"=>strip_tags(minc_tr("minc_show")),"hide"=>strip_tags(minc_tr("minc_hide")),
        "showing"=>strip_tags(minc_tr("minc_showing_of"))),$flags);
    return "<script>(function(){
var L=$L, size=50, cur=1;
function mains(){var tb=document.getElementById('minc-rows');if(!tb){return [];}var o=[];for(var i=0;i<tb.rows.length;i++){if(tb.rows[i].className.indexOf('minc-r')===0&&tb.rows[i].className.indexOf('minc-d')<0){o.push(tb.rows[i]);}}return o;}
function draw(){var r=mains(),n=r.length,pages=Math.max(1,Math.ceil(n/size));if(cur>pages){cur=pages;}
 for(var i=0;i<n;i++){var on=(i>=(cur-1)*size&&i<cur*size);r[i].style.display=on?'':'none';var d=document.getElementById('minc-d-'+r[i].getAttribute('data-t'));if(d&&!on){d.hidden=true;r[i].classList.remove('open');}}
 var c=document.getElementById('minc-count');if(c){var a=n?((cur-1)*size+1):0,b=Math.min(n,cur*size);c.textContent=L.showing.replace('%s',a+'-'+b).replace('%s',n);}
 var box=document.getElementById('minc-pager');if(!box){return;}if(pages<2){box.innerHTML='';return;}
 var h='<a class=\"'+(cur==1?'off':'')+'\" data-p=\"'+(cur-1)+'\" aria-label=\"Previous\">&lsaquo;</a>';
 var end=Math.min(pages,cur+2),start=Math.max(1,end-4);end=Math.min(pages,start+4);
 for(var p=start;p<=end;p++){h+='<a class=\"'+(p==cur?'cur':'')+'\" data-p=\"'+p+'\">'+p+'</a>';}
 h+='<a class=\"'+(cur==pages?'off':'')+'\" data-p=\"'+(cur+1)+'\" aria-label=\"Next\">&rsaquo;</a>';box.innerHTML=h;
 var as=box.getElementsByTagName('a');for(var k=0;k<as.length;k++){as[k].onclick=function(){var p=parseInt(this.getAttribute('data-p'),10);if(p>=1&&p<=pages&&p!=cur){cur=p;draw();}return false;};}}
window.MincToggle=function(t,btn){var d=document.getElementById('minc-d-'+t),r=document.getElementById('minc-r-'+t);if(!d){return;}
 var open=d.hidden;d.hidden=!open;r.classList.toggle('open',open);btn.setAttribute('aria-expanded',open?'true':'false');
 btn.title=open?L.hide:L.show;btn.setAttribute('aria-label',btn.title);btn.innerHTML='<i class=\"fas fa-chevron-'+(open?'up':'down')+'\" aria-hidden=\"true\"></i>';
 if(open&&!d.getAttribute('data-loaded')){d.setAttribute('data-loaded','1');LoadAjaxSilent('minc-c-'+t,'$page?incident-contents='+t);}};
window.MincRemove=function(t){var r=document.getElementById('minc-r-'+t),d=document.getElementById('minc-d-'+t);if(r){r.parentNode.removeChild(r);}if(d){d.parentNode.removeChild(d);}draw();
 if(document.getElementById('minc-summary')){LoadAjaxSilent('minc-summary','$page?incidents-summary=yes');}};
draw();
})();</script>";
}

/**
 * Entrées d'une archive tar (après gunzip) : nom, taille, type. Lit l'en-tête
 * ustar et les noms longs GNU ('L'). Rend null si ce n'est pas une archive tar.
 */
function minc_tar_entries(string $tar,int $max=500):?array{
    $out=array(); $pos=0; $len=strlen($tar); $long=null;
    while($pos+512<=$len && count($out)<$max){
        $hdr=substr($tar,$pos,512);
        if(trim($hdr,"\0")===""){break;}
        $sum=0;
        for($i=0;$i<512;$i++){ $sum+=($i>=148 && $i<156)?32:ord($hdr[$i]); }
        if(octdec(trim(substr($hdr,148,8),"\0 "))!=$sum){ return count($out)>0?$out:null; }
        $name=rtrim(substr($hdr,0,100),"\0");
        $size=octdec(trim(substr($hdr,124,12),"\0 "));
        $type=$hdr[156];
        if(substr($hdr,257,5)==="ustar"){
            $prefix=rtrim(substr($hdr,345,155),"\0");
            if($prefix!==""){$name=$prefix."/".$name;}
        }
        $data=$pos+512;
        $pos=$data+intval(ceil($size/512))*512;
        if($type==="L"){ $long=rtrim(substr($tar,$data,$size),"\0"); continue; }
        if($long!==null){ $name=$long; $long=null; }
        if($type==="5"){ continue; }
        $out[]=array("name"=>ltrim($name,"./"),"size"=>$size,"offset"=>$data);
    }
    return $out;
}

/** ?incident-contents=<time> : fichiers de l'archive et texte de WhyThisReport.txt. */
function minc_contents():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Le contenu des archives (journaux, processus) est réservé, même si la page est ouverte autrement.
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
        return false;
    }
    $time=intval($_GET["incident-contents"]);
    $q=new lib_sqlite(minc_db());
    $l=$q->mysqli_fetch_array("SELECT length(file) AS b64 FROM perfs_queue WHERE zDate=$time");
    $dl="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"document.location.href='$page?incident-download=$time'\"><i class='fas fa-download' aria-hidden='true'></i>{minc_download_tgz}</button>";
    $head="<div class='minc-panel'><div class='minc-ph'><span class='minc-pt'><i class='fas fa-file-zipper' aria-hidden='true'></i>{minc_contents} &mdash; $time.tar.gz</span>$dl</div>";
    if(!is_array($l) || !isset($l["b64"])){
        echo $tpl->_ENGINE_parse_body($head."<div class='ak-empty-c minc-empty'>{minc_not_found}</div></div>");
        return true;
    }
    if(intval($l["b64"])*3/4>minc_preview_max()){
        echo $tpl->_ENGINE_parse_body($head."<div class='ak-empty-c minc-empty'>{minc_too_big}</div></div>");
        return true;
    }
    $l=$q->mysqli_fetch_array("SELECT file FROM perfs_queue WHERE zDate=$time");
    $gz=base64_decode((string)($l["file"] ?? ""),true);
    $tar=$gz!==false?@gzdecode($gz):false;
    $entries=is_string($tar)?minc_tar_entries($tar):null;
    if($entries===null){
        echo $tpl->_ENGINE_parse_body($head."<div class='ak-empty-c minc-empty'>{minc_bad_archive}</div></div>");
        return true;
    }
    $why=null; $total=0; $li=array();
    foreach($entries as $e){
        $total+=$e["size"];
        if($why===null && strtolower(basename($e["name"]))==="whythisreport.txt"){
            $why=substr($tar,$e["offset"],min($e["size"],16384));
        }
        if(count($li)<200){
            $li[]="<li><span class='ak-mono' title=\"".minc_txt($e["name"])."\"><i class='far fa-file-lines' aria-hidden='true'></i>".minc_txt($e["name"])."</span><span class='sz ak-mono'>".minc_bytes($e["size"])."</span></li>";
        }
    }
    if(count($entries)>200){$li[]="<li><span>".minc_tr("minc_more_files",array(number_format(count($entries)-200)))."</span></li>";}
    $files="<div class='minc-files'><div class='minc-files-h'><span>".minc_tr("minc_files",array(number_format(count($entries))))."</span><span>".minc_tr("minc_uncompressed",array(minc_bytes($total)))."</span></div><ul>".implode("",$li)."</ul></div>";
    if($why===null){
        $whyHtml="<div class='minc-why none'>{minc_no_why}</div>";
    }else{
        $whyHtml="<div class='minc-why'><div class='minc-why-h'><i class='far fa-file-lines' aria-hidden='true'></i> WhyThisReport.txt</div><pre>".minc_txt($why)."</pre></div>";
    }
    echo $tpl->_ENGINE_parse_body($head."<div class='minc-pb'>$files$whyHtml</div></div>");
    return true;
}
