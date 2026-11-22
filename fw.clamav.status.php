<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Verrou de la politique Artica Meta "ClamAV" (reglages tenus en lecture seule).
include_once(dirname(__FILE__)."/ressources/class.clamav.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["services-status"])){status_rotation();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["patterns"])){patterns();exit;}
if(isset($_GET["patterns2"])){patterns2();exit;}
if(clamav_meta_posted("ClamavStreamMaxLength")){save();exit;}
if(clamav_meta_posted("ClamdNotReloadProduction")){reload_policy_save();exit;}
if(clamav_meta_posted("ClamavRefreshDaemonMemory")){save();exit;}
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
    $progress=$tpl->framework_buildjs("/clamd/purge","clamd.progress","clamd.log","progress-clamav-restart",$js);
    $tpl->js_confirm_execute("{uninstall_text}","remove-clamav","none",$progress);

}
function remove_clamav_confirm(){
    admin_tracks("Uninstall clamav software (emergency mode)");
}

function uploaded(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $page=CurrentPageName();
    $filename=$_GET["file-uploaded"];

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/clamav.update.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/clamav.update.progress.txt";
    $ARRAY["CMD"]="/clamd/signatures/upload?file=".rawurlencode($filename);
    $ARRAY["TITLE"]="{upload_signatures}";
    $ARRAY["AFTER"]="LoadAjax('table-clamav','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $clamd_restart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=clamav-upload-pattern')";

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
			$w_used_databases=$tpl->widget_h("yellow",ico_database,"$used_databases/45","{used_databases}");
		}else{
			$w_used_databases=$tpl->widget_h("green",ico_database,"$used_databases/45","{used_databases}");
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


    // politique Artica Meta : un reglage tenu n'est jamais ecrit ici (le demon refuserait tout en 423)
    $_POST=clamav_meta_strip_post($_POST);
    if(count($_POST)==0){return true;}
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

	
    // Vue « bases de signatures » (maquette stitch_proxy_antivirus_list).
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/pattern-status"),true);
    $apiError="";
    if(!is_array($json)){$apiError="{error}: /clamd/pattern-status";}
    elseif(isset($json["Status"]) && !$json["Status"]){$apiError=(string)($json["Error"] ?? "{error}");}
    $results=(is_array($json) && isset($json["data"]) && is_array($json["data"]))?$json["data"]:array();

    $now=time(); $total=0; $official=0; $third=0; $newest=0; $dailyVersion=""; $names=array();
    foreach($results as $r){
        $total+=intval($r["signatures"] ?? 0);
        if(clamdb_is_official((string)($r["dbname"] ?? ""))){$official++;}else{$third++;}
        $ts=strtotime((string)($r["patterndate"] ?? ""));
        if($ts!==false && $ts>$newest){$newest=$ts;}
        if(preg_match('#^daily\.#',(string)($r["dbname"] ?? ""))){$dailyVersion=(string)($r["version"] ?? "");}
        $names[]=htmlspecialchars((string)($r["dbname"] ?? ""));
    }
    usort($results,function($a,$b){ return strcmp((string)($b["patterndate"] ?? ""),(string)($a["patterndate"] ?? "")); });
    $state=clamdb_state(count($results),$newest,$now);
    $version=htmlspecialchars((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion"));
    $jsSettings="Loadjs('$page?section-main-js=yes')";
    $jsSchedules="\$('a[data-url*=\'ForceTaskType=81\']').first().trigger('click');";

    $h=array();
    $h[]="<div class='clamdb ak'>";
    if($apiError!=""){ $h[]=$tpl->div_error($apiError); }

    // Indicateurs
    $ageTxt=$newest>0?htmlspecialchars(clamdb_age($newest,$now)):"-";
    $pill=array("green"=>"<span class='ak-pill ak-ok'>{clamdb_fresh}</span>",
        "amber"=>"<span class='ak-pill ak-warn'>{clamdb_aging}</span>",
        "red"=>"<span class='ak-pill ak-err'>{clamdb_outdated}</span>","empty"=>"");
    $h[]="<div class='ak-grid-4'>";
    $h[]="<div class='ak-fig clamdb-kpi'><div class='clamdb-kpi-top'><span class='ak-fig-l'>{clamdb_loaded_signatures}</span><span class='ak-ico ak-ico-sm ak-ok'><i class='fas fa-database'></i></span></div>"
        ."<div class='clamdb-kpi-num'>".number_format($total,0,","," ")."</div>"
        ."<div class='ak-fig-s ak-ok clamdb-kpi-sub'>".($version!=""?"<i class='fas fa-circle-check'></i> ClamAV $version":"&nbsp;")."</div></div>";
    $h[]="<div class='ak-fig clamdb-kpi'><div class='clamdb-kpi-top'><span class='ak-fig-l'>{clamdb_last_update}</span><span class='ak-ico ak-ico-sm ak-ok'><i class='fas fa-clock'></i></span></div>"
        ."<div class='clamdb-kpi-val'><span class='clamdb-kpi-mid'>$ageTxt</span>{$pill[$state]}</div>"
        ."<div class='ak-fig-s clamdb-kpi-sub'><a class='clamdb-link' href=\"javascript:blur();\" OnClick=\"$jsSchedules\">{schedules} <i class='fas fa-arrow-right'></i></a></div></div>";
    $h[]="<div class='ak-fig clamdb-kpi'><div class='clamdb-kpi-top'><span class='ak-fig-l'>{clamdb_active_databases}</span><span class='ak-ico ak-ico-sm ak-ok'><i class='fas fa-shield-halved'></i></span></div>"
        ."<div class='clamdb-kpi-val'><span class='clamdb-kpi-mid'>$official {clamdb_official}</span><span class='ak-pill ak-off'>{clamdb_third_title} &middot; $third</span></div>"
        ."<div class='ak-fig-s clamdb-kpi-sub'>".(count($names)>0?implode(", ",$names):"{clamdb_none_loaded}")."</div></div>";
    $h[]="<div class='ak-fig clamdb-kpi'><div class='clamdb-kpi-top'><span class='ak-fig-l'>{clamdb_daily_base}</span><span class='ak-ico ak-ico-sm ak-ok'><i class='fas fa-bolt'></i></span></div>"
        ."<div class='clamdb-kpi-num clamdb-teal'>".($dailyVersion!=""?"v".htmlspecialchars($dailyVersion):"-")."</div>"
        ."<div class='ak-fig-s clamdb-kpi-sub'>{clamdb_daily_sub}</div></div>";
    $h[]="</div>";

    // Bandeau de fraicheur, calcule sur la base la plus recente (main et bytecode
    // restent legitimement vieilles de plusieurs mois).
    $banner=array(
        "green"=>array("ak-ok","fa-circle-check","{clamdb_banner_ok}","{clamdb_banner_ok_sub}"),
        "amber"=>array("ak-warn","fa-clock","{clamdb_banner_warn}","{clamdb_banner_warn_sub}"),
        "red"=>array("ak-err","fa-triangle-exclamation","{clamdb_banner_bad}","{clamdb_banner_bad_sub}"),
        "empty"=>array("ak-err","fa-shield","{clamdb_banner_empty}","{clamdb_banner_empty_sub}"));
    list($bc,$bi,$bt,$bs)=$banner[$state];
    $h[]="<div class='ak-banner $bc clamdb-banner'><div class='clamdb-banner-l'><span class='clamdb-banner-ico'><i class='fas $bi'></i></span>"
        ."<div><div class='clamdb-banner-t'>$bt</div><div class='clamdb-banner-s'>$bs</div></div></div>";
    if($state!="green"){
        $h[]="<a class='ak-btn ak-btn-primary' href=\"javascript:blur();\" OnClick=\"$freshclam_update\"><i class='fas fa-download'></i> {clamdb_update_now}</a>";
    }
    $h[]="</div>";

    // Cartes des bases
    $h[]="<div class='clamdb-head'><div class='clamdb-head-t'>{clamdb_files_title}</div><div class='clamdb-head-s'>{clamdb_files_sub}</div></div>";
    $h[]="<div class='ak-grid-3'>";
    foreach($results as $r){
        $h[]=clamdb_card($tpl,$r,$total,$now);
    }
    if($third==0){
        $h[]="<div class='ak-card clamdb-card clamdb-card-add'><div class='clamdb-card-top'><span class='ak-ico ak-ico-sq ak-off'><i class='fas fa-puzzle-piece'></i></span>"
            ."<div><div class='clamdb-name'>{clamdb_third_title}</div></div></div>"
            ."<p class='clamdb-add-p'>{clamdb_third_explain}</p>"
            ."<a class='ak-btn' href=\"javascript:blur();\" OnClick=\"$jsSettings\"><i class='fas fa-circle-plus'></i> {clamdb_third_enable}</a></div>";
    }
    $h[]="</div>";

    // Service de mise a jour
    $h[]="<div class='ak-card clamdb-info'><span class='ak-ico ak-ico-sq'><i class='fas fa-circle-info'></i></span>"
        ."<div class='clamdb-info-b'><div class='clamdb-info-t'>{clamdb_freshclam_title}</div><div class='clamdb-info-s'>{clamdb_freshclam_explain}</div></div>"
        ."<a class='ak-btn' href=\"javascript:blur();\" OnClick=\"$jsSchedules\"><i class='fas fa-calendar'></i> {schedules}</a></div>";
    $h[]="</div>";

    $TINY_ARRAY["TITLE"]="{patterns_versions}";
    $TINY_ARRAY["ICO"]="fab fa-medrt";
    $TINY_ARRAY["EXPL"]="{clamav_antivirus_databases_explain}";
    $TINY_ARRAY["URL"]="clamav";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Feuille de style hors de _ENGINE_parse_body: ses blocs {...} y seraient lus comme des jetons.
    echo clamdb_style().$tpl->_ENGINE_parse_body(@implode("\n", $h))."<script>NoSpinner();$jstiny</script>";
}

// Bases publiees par ClamAV (main, daily, bytecode en .cvd ou .cld) ; tout le reste est tiers.
function clamdb_is_official(string $name):bool{
    return preg_match('#^(main|daily|bytecode)\.(cvd|cld)$#',$name)==1;
}

// Etat global de fraicheur: vert sous 24 h, ambre jusqu'a 3 jours, rouge au-dela.
function clamdb_state(int $count,int $newest,int $now):string{
    if($count==0 || $newest==0){return "empty";}
    $age=$now-$newest;
    if($age<=86400){return "green";}
    if($age<=259200){return "amber";}
    return "red";
}

function clamdb_age(int $ts,int $now):string{
    return distanceOfTimeInWords($ts,$now);
}

function clamdb_card($tpl,array $r,int $total,int $now):string{
    $name=(string)($r["dbname"] ?? "");
    $sigs=intval($r["signatures"] ?? 0);
    $date=(string)($r["patterndate"] ?? "");
    $ts=strtotime($date);
    $official=clamdb_is_official($name);
    $kind="third"; $ico="fa-puzzle-piece"; $sub="{clamdb_desc_third}";
    if(preg_match('#^daily\.#',$name)){$kind="daily";$ico="fa-bolt";$sub="{clamdb_desc_daily}";}
    if(preg_match('#^main\.#',$name)){$kind="main";$ico="fa-folder-open";$sub="{clamdb_desc_main}";}
    if(preg_match('#^bytecode\.#',$name)){$kind="bytecode";$ico="fa-terminal";$sub="{clamdb_desc_bytecode}";}
    // main et bytecode changent rarement: leur age n'est pas un signal d'alerte.
    $dot="ak-ok";
    if($kind=="daily" || $kind=="third"){
        $st=clamdb_state(1,$ts===false?0:$ts,$now);
        if($st=="amber"){$dot="ak-warn";}
        if($st=="red" || $st=="empty"){$dot="ak-err";}
    }
    $age=$ts!==false?htmlspecialchars(clamdb_age($ts,$now)):"-";
    if($kind=="main" || $kind=="bytecode"){$age.=" <span class='clamdb-muted'>· {clamdb_stable}</span>";}
    $pct=$total>0?$sigs*100/$total:0;
    $pctTxt=$pct<1?"&lt;1 %":number_format($pct,0,","," ")." %";
    $bar=$sigs>0?max(1,min(100,round($pct))):0;
    $badge=$official?"<span class='ak-pill ak-ok'>{clamdb_official_badge}</span>":"<span class='ak-pill ak-info'>{clamdb_third_badge}</span>";
    return "<div class='ak-card clamdb-card'><div class='clamdb-card-top'><span class='ak-ico ak-ico-sq'><i class='fas $ico'></i></span>"
        ."<div class='clamdb-card-id'><div><span class='clamdb-name'>".htmlspecialchars($name)."</span>$badge</div><div class='clamdb-sub'>$sub</div></div>"
        ."<span class='ak-dot $dot clamdb-dot'></span></div>"
        ."<div class='clamdb-meta'><div><span>{clamdb_build_date}</span><span>".htmlspecialchars($date)."</span></div>"
        ."<div><span>{clamdb_age_lbl}</span><span>$age</span></div>"
        ."<div><span>{version}</span>".(trim((string)($r["version"] ?? ""))==""?"<span>-</span>":"<span class='clamdb-ver'>".htmlspecialchars((string)$r["version"])."</span>")."</div></div>"
        ."<div class='clamdb-sig'><span class='clamdb-sig-n'>".number_format($sigs,0,","," ")."</span><span class='clamdb-muted'>$pctTxt {clamdb_of_catalog}</span></div>"
        ."<div class='ak-bar'><span style='width:$bar%'></span></div></div>";
}

function clamdb_style():string{
    if(isset($GLOBALS["CLAMDB_STYLE"])){return "";}
    $GLOBALS["CLAMDB_STYLE"]=true;
    // Tuiles, cartes, pastilles, bandeau, boutons, jauge : kit ak- (angular.css.php).
    return "<style>
.clamdb{margin-top:10px;display:flex;flex-direction:column;gap:20px}
.clamdb-kpi{flex-direction:column;align-items:stretch;justify-content:flex-start;gap:10px;min-height:140px}
.clamdb-kpi-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
.clamdb-kpi-num{font-size:32px;font-weight:700;line-height:38px;color:#111d23}
.clamdb-kpi-val{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.clamdb-kpi-mid{font-size:24px;font-weight:700;line-height:30px;color:#111d23}
.clamdb-kpi-sub{margin-top:auto}
.clamdb-teal{color:#00504a}
.clamdb-link{font-weight:700;color:#006a62!important;text-decoration:none!important;cursor:pointer}
.clamdb-link:hover{text-decoration:underline!important}
.clamdb a.ak-btn{text-decoration:none}
.clamdb-banner{justify-content:space-between;gap:16px;flex-wrap:wrap;border-radius:12px;padding:16px 20px}
.clamdb-banner-l{display:flex;align-items:center;gap:14px}
.clamdb-banner-ico{font-size:24px}
.clamdb-banner-t{font-size:16px;font-weight:700;color:#111d23}
.clamdb-banner-s{font-size:13px;color:#3d4947;margin-top:2px}
.clamdb-head-t{font-size:20px;font-weight:700}
.clamdb-head-s{font-size:13px;color:#3d4947;margin-top:2px}
.clamdb-card{padding:20px;display:flex;flex-direction:column;gap:14px}
.clamdb-card-add{background:#f4faff;border-style:dashed}
.clamdb-card-top{display:flex;align-items:flex-start;gap:12px}
.clamdb-card-id{flex:1;min-width:0}
.clamdb-name{font-size:18px;font-weight:700;margin-right:8px;word-break:break-all}
.clamdb-sub{font-size:13px;color:#6d7a77;margin-top:4px}
.clamdb-dot{width:10px;height:10px;margin-top:6px}
.clamdb-meta{background:#e9f6fd;border-radius:10px;padding:12px 16px;display:flex;flex-direction:column;gap:8px;font-size:13px;color:#3d4947}
.clamdb-meta>div{display:flex;justify-content:space-between;gap:10px;align-items:center}
.clamdb-meta>div>span:last-child{text-align:right;color:#111d23}
.clamdb-ver{background:#fff;border-radius:6px;padding:2px 8px;font-weight:600}
.clamdb-muted{color:#6d7a77;font-size:13px}
.clamdb-sig{display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin-top:auto}
.clamdb-sig-n{font-size:22px;font-weight:700}
.clamdb-add-p{font-size:13px;line-height:19px;color:#3d4947;margin:0;flex:1}
.clamdb-info{display:flex;align-items:center;gap:16px;padding:18px 20px;flex-wrap:wrap}
.clamdb-info-b{flex:1;min-width:220px}
.clamdb-info-t{font-size:16px;font-weight:700}
.clamdb-info-s{font-size:13px;color:#3d4947;margin-top:2px}
</style>";
}

// Panneau des parametres (maquette Stitch 2026-11-02) -- debut
// Un widget = une fonction ; clamavset_model() lit tout une seule fois.
function clamav_status_parameters():bool{
    $m=clamavset_model();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]=clamav_meta_banner($tpl);
    $html[]="<div class='ak ak-grid-2 cvs-grid'>";
    $html[]=clamavset_card_reloads($m,$tpl);
    $html[]=clamavset_card_watchdog($m,$tpl);
    $html[]=clamavset_card_limits($m,$tpl);
    $html[]=clamavset_card_features($m,$tpl);
    $html[]="</div>";
    echo clamavset_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).clamavset_js($page);
    return true;
}

// clamavset_model : les reglages tels que clamd les APPLIQUE (clamavDaemon.Build),
// pas tels que l'ancienne carte les affichait : MaxScanSize = ClamavMaxFileSize,
// MaxFiles fige a 10000, taille de fichier 20 Mo par defaut.
function clamavset_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["RefreshTime"]=intval($s->GET_INFO("ClamavRefreshDaemonTime"));
    $m["RefreshMem"]=intval($s->GET_INFO("ClamavRefreshDaemonMemory"));
    $m["Stream"]=intval($s->GET_INFO("ClamavStreamMaxLength"));
    if($m["Stream"]==0){$m["Stream"]=12;}
    $m["FileSize"]=intval($s->GET_INFO("ClamavMaxFileSize"));
    if($m["FileSize"]==0){$m["FileSize"]=20;}
    $m["ScanSize"]=$m["FileSize"];
    $m["MaxFiles"]=10000;
    $m["Recursion"]=intval($s->GET_INFO("ClamavMaxRecursion"));
    if($m["Recursion"]==0){$m["Recursion"]=5;}
    $m["Phishing"]=intval($s->GET_INFO("PhishingScanURLs"));
    $m["Unofficial"]=intval($s->GET_INFO("EnableClamavUnofficial"));
    $m["SecuriteInfo"]=intval($s->GET_INFO("EnableClamavSecuriteInfo"));
    $m["SecuriteSchedule"]=intval($s->GET_INFO("EnableClamavSecuriteSchedule"));
    if($m["SecuriteSchedule"]==0){$m["SecuriteSchedule"]=360;}
    $m["rp"]=reload_policy_get();
    $m["edit"]=(new template_admin())->IsSecurity("AsSystemAdministrator");
    return $m;
}

function clamavset_head($icon,$title,$sub,$js,$m):string{
    $h=array();
    $h[]="<div class='cvs-head'>";
    $h[]="<span class='ak-ico ak-ico-sm ak-ico-sq' aria-hidden='true'><i class='$icon'></i></span>";
    $h[]="<div class='cvs-titles'><div class='cvs-title'>$title</div><div class='cvs-sub'>$sub</div></div>";
    if($m["edit"]){
        $h[]="<button type='button' class='ak-btn' OnClick=\"$js\" aria-label='{clamavset_edit}: $title'><i class='fa-solid fa-pen' aria-hidden='true'></i><span>{clamavset_edit}</span></button>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

function clamavset_pill($on):string{
    if(intval($on)==1){
        return "<span class='ak-pill ak-ok'><i class='fa-solid fa-check' aria-hidden='true'></i>{clamavset_on}</span>";
    }
    return "<span class='ak-pill ak-off'><i class='fa-solid fa-minus' aria-hidden='true'></i>{clamavset_off}</span>";
}

function clamavset_when($ts):string{
    $ts=intval($ts);
    if(date("Y-m-d",$ts)==date("Y-m-d")){return date("H:i",$ts);}
    return date("Y-m-d H:i",$ts);
}

function clamavset_card_reloads($m,$tpl):string{
    $page=CurrentPageName();
    $rp=$m["rp"];
    $conc=intval($rp["ConcurrentDatabaseReload"]);
    $nrp=intval($rp["ClamdNotReloadProduction"]);
    $window=htmlspecialchars($rp["ProductionTimeStart"])." &ndash; ".htmlspecialchars($rp["ProductionTimeEnd"]);
    $h=array();
    $h[]="<section class='ak-card cvs-card cvs-full' aria-label='{clamavset_reloads}'>";
    $h[]=clamavset_head("fa-solid fa-rotate","{clamavset_reloads}","{clamavset_reloads_sub}","Loadjs('$page?section-ConcurrentDatabaseReload-js=yes')",$m);

    foreach(array("ReloadPendingSince"=>"{clamd_reload_pending}","RestartPendingSince"=>"{clamd_restart_pending}") as $k=>$label){
        if(intval($rp[$k])>0){
            $h[]="<div class='ak-banner ak-warn cvs-pending' role='status'><i class='fa-solid fa-hourglass-half' aria-hidden='true'></i><span>$label ".clamavset_when($rp[$k])." <span class='ak-muted'>({clamavset_at_most_24h})</span></span></div>";
        }
    }

    $h[]="<div class='cvs-rows'>";
    $h[]="<div class='cvs-row'><span class='cvs-row-l'><i class='fa-solid fa-memory' aria-hidden='true'></i>{ConcurrentDatabaseReload}</span>".clamavset_pill($conc)."</div>";
    $h[]="<div class='cvs-row'><span class='cvs-row-l'><i class='fa-solid fa-clock' aria-hidden='true'></i>{ClamdNotReloadProduction}<span class='cvs-suffix'>$window</span></span>".clamavset_pill($nrp)."</div>";
    $h[]="</div>";

    // Verdict calcule des deux reglages.
    if($conc==1 && $nrp==1){
        $h[]="<div class='ak-banner ak-off cvs-verdict' role='status'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span>{clamavset_verdict_both}</span></div>";
    }elseif($conc==1){
        $h[]="<div class='ak-banner ak-ok cvs-verdict' role='status'><i class='fa-solid fa-circle-check' aria-hidden='true'></i><span>{clamavset_verdict_nonblocking}</span></div>";
    }elseif($nrp==1){
        $h[]="<div class='ak-banner ak-ok cvs-verdict' role='status'><i class='fa-solid fa-circle-check' aria-hidden='true'></i><span>{clamavset_verdict_deferred}</span></div>";
    }else{
        $h[]="<div class='ak-banner ak-warn cvs-verdict' role='status'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><span>{clamavset_verdict_freeze}</span></div>";
    }
    $h[]=clamavset_memory($rp);
    $h[]="</section>";
    return @implode("\n",$h);
}

// Jauge : clamd (plein) dans ce qu'il peut atteindre (clamd + memoire libre),
// repere a 2,2 x clamd = ce qu'exige un rechargement non bloquant (ConcurrentMemoryFits).
function clamavset_memory($rp):string{
    $rss=intval($rp["ClamdRSSMB"]);
    $avail=intval($rp["MemAvailableMB"]);
    $h=array();
    $h[]="<div class='cvs-mem'>";
    $h[]="<div class='cvs-mem-head'><span class='cvs-mem-t'>{clamavset_memory}</span>";
    if($rss<=0 || $avail<=0){
        $h[]="</div><div class='ak-muted'>{clamavset_memory_unknown}</div></div>";
        return @implode("",$h);
    }
    $need=intval(round($rss*2.2));
    $scale=max($rss+$avail,$need);
    $pRss=round($rss*100/$scale,1);
    $pTot=round(($rss+$avail)*100/$scale,1);
    $pNeed=round($need*100/$scale,1);
    if($pNeed>99){$pNeed=99;}
    $frss=FormatNumber($rss);
    $favail=FormatNumber($avail);
    $h[]="<span class='cvs-mem-v'><strong>$frss MB</strong> {clamavset_clamd_memory} / $favail MB {clamavset_available}</span></div>";
    $h[]="<div class='cvs-bar' role='progressbar' aria-valuemin='0' aria-valuemax='$scale' aria-valuenow='$rss' aria-label='$frss MB {clamavset_clamd_memory}, $favail MB {clamavset_available}'>";
    $h[]="<span class='cvs-bar-free' style='width:$pTot%'></span>";
    $h[]="<span class='cvs-bar-used' style='width:$pRss%'></span>";
    $h[]="<span class='cvs-bar-need' style='left:$pNeed%'></span>";
    $h[]="</div>";
    $h[]="<div class='cvs-mem-legend'><span><i class='cvs-dot cvs-dot-used'></i>{clamavset_clamd_memory}</span><span><i class='cvs-dot cvs-dot-free'></i>{clamavset_available}</span><span><i class='cvs-tick'></i>{clamavset_needed_nonblocking}</span></div>";
    if(empty($rp["ConcurrentMemoryOK"])){
        $h[]="<div class='cvs-mem-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{clamavset_memory_short}</div>";
    }
    $h[]="</div>";
    return @implode("\n",$h);
}

function clamavset_card_watchdog($m,$tpl):string{
    $page=CurrentPageName();
    $hoursEX=array(0=>"{never}",15=>"15 {minutes}",30=>"30 {minutes}",60=>"1 {hour}",120=>"2 {hours}",180=>"3 {hours}",420=>"7 {hours}",480=>"8 {hours}");
    $each=isset($hoursEX[$m["RefreshTime"]])?$hoursEX[$m["RefreshTime"]]:$m["RefreshTime"]." {minutes}";
    $mem=$m["RefreshMem"]>0?FormatNumber($m["RefreshMem"])." MB":"{clamavset_off}";
    $h=array();
    $h[]="<section class='ak-card cvs-card' aria-label='{watchdog}'>";
    $h[]=clamavset_head("fa-solid fa-shield-heart","{watchdog}","{clamavset_watchdog_sub}","Loadjs('$page?section-watchdog-js=yes')",$m);
    $h[]="<dl class='cvs-kv'>";
    $h[]="<div><dt>{srv_clamav.RefreshDaemon}</dt><dd>$each</dd></div>";
    $h[]="<div><dt>{refresh_daemon_MB}</dt><dd>$mem</dd></div>";
    $h[]="</dl>";
    if($m["RefreshTime"]==0 && $m["RefreshMem"]==0){
        $h[]="<div class='cvs-foot'><i class='fa-solid fa-circle-info' aria-hidden='true'></i>{clamavset_watchdog_inactive}</div>";
    }
    $h[]="</section>";
    return @implode("\n",$h);
}

function clamavset_card_limits($m,$tpl):string{
    $page=CurrentPageName();
    $rows=array(
        "{srv_clamav.StreamMaxLength}"=>$m["Stream"]." MB",
        "{srv_clamav.MaxFileSize}"=>$m["FileSize"]." MB",
        "{srv_clamav.MaxScanSize}"=>$m["ScanSize"]." MB",
        "{srv_clamav.ClamAvMaxFilesInArchive}"=>FormatNumber($m["MaxFiles"]),
        "{srv_clamav.ClamAvMaxRecLevel}"=>$m["Recursion"],
    );
    $h=array();
    $h[]="<section class='ak-card cvs-card' aria-label='{clamavset_limits}'>";
    $h[]=clamavset_head("fa-solid fa-ruler-combined","{clamavset_limits}","{clamavset_limits_sub}","Loadjs('$page?section-main-js=yes')",$m);
    $h[]="<dl class='cvs-kv'>";
    foreach($rows as $k=>$v){$h[]="<div><dt>$k</dt><dd>$v</dd></div>";}
    $h[]="</dl>";
    $h[]="<div class='cvs-foot'><i class='fa-solid fa-circle-info' aria-hidden='true'></i>{clamavset_limits_caption}</div>";
    $h[]="</section>";
    return @implode("\n",$h);
}

function clamavset_card_features($m,$tpl):string{
    $page=CurrentPageName();
    $Timez=array(5=>"5 {minutes}",10=>"10 {minutes}",15=>"15 {minutes}",30=>"30 {minutes}",60=>"1 {hour}",120=>"2 {hours}",180=>"3 {hours}",360=>"6 {hours}",720=>"12 {hours}",1440=>"1 {day}",2880=>"2 {days}");
    $sched=isset($Timez[$m["SecuriteSchedule"]])?$Timez[$m["SecuriteSchedule"]]:$m["SecuriteSchedule"]." {minutes}";
    $sec=$m["SecuriteInfo"]==1?"<span class='cvs-suffix'>{update_each} $sched</span>":"";
    $h=array();
    $h[]="<section class='ak-card cvs-card cvs-full' aria-label='{features}'>";
    $h[]=clamavset_head("fa-solid fa-puzzle-piece","{features}","{clamavset_features_sub}","Loadjs('$page?section-main-js=yes')",$m);
    $h[]="<div class='cvs-rows'>";
    $h[]="<div class='cvs-row'><span class='cvs-row-l'><i class='fa-solid fa-earth-europe' aria-hidden='true'></i>{srv_clamav.PhishingScanURLs}</span>".clamavset_pill($m["Phishing"])."</div>";
    $h[]="<div class='cvs-row'><span class='cvs-row-l'><i class='fa-solid fa-database' aria-hidden='true'></i>{clamav_unofficial}</span>".clamavset_pill($m["Unofficial"])."</div>";
    $h[]="<div class='cvs-row'><span class='cvs-row-l'><i class='fa-solid fa-database' aria-hidden='true'></i>{securiteinfo_antivirus_databases}$sec</span>".clamavset_pill($m["SecuriteInfo"])."</div>";
    $h[]="</div>";
    $h[]="</section>";
    return @implode("\n",$h);
}

// Rafraichissement : un seul timer global, saute quand une modale est ouverte,
// s'arrete tout seul quand l'hote a quitte le DOM.
function clamavset_js($page):string{
    return "<script>
(function(){
 if(window.ClamavSetTimer){clearTimeout(window.ClamavSetTimer);}
 function tick(){
   var host=document.getElementById('clamav-status-parameters');
   if(!host){return;}
   var modal=document.querySelector('.bootstrap-dialog.in, .modal.in, .sweet-alert.visible, .sweet-alert.showSweetAlert');
   if(modal){window.ClamavSetTimer=setTimeout(tick,15000);return;}
   LoadAjaxSilent('clamav-status-parameters','$page?clamav-status-parameters=yes');
 }
 window.ClamavSetTimer=setTimeout(tick,60000);
})();
</script>";
}

function clamavset_style():string{
    if(isset($GLOBALS["CLAMAVSET_STYLE"])){return "";}
    $GLOBALS["CLAMAVSET_STYLE"]=true;
    $acc="#006a62";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){$acc=$a["accent_dark"];}
    }
    // Carte, pastilles, bandeaux, bouton : kit ak- (angular.css.php).
    return "<style>
.cvs-grid{gap:24px;margin:10px}
.cvs-card{padding:24px;display:flex;flex-direction:column;gap:16px}
.cvs-full{grid-column:1 / -1}
@media (max-width:1200px){.cvs-grid{grid-template-columns:1fr}}
.cvs-head{display:flex;align-items:flex-start;gap:14px;padding-bottom:14px;border-bottom:1px solid rgba(188,201,198,.5)}
.cvs-titles{flex:1 1 auto;min-width:0}
.cvs-title{font-size:16px;font-weight:600;line-height:22px}
.cvs-sub{font-size:13px;color:#3d4947;margin-top:2px}
.cvs-rows{border:1px solid rgba(188,201,198,.6);border-radius:10px;background:#fafcfc}
.cvs-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 18px;font-size:14px}
.cvs-row+.cvs-row{border-top:1px solid rgba(188,201,198,.5)}
.cvs-row-l{display:flex;align-items:center;gap:12px;min-width:0;flex-wrap:wrap}
.cvs-row-l>i{color:#3d4947;width:16px;text-align:center}
.cvs-suffix{color:#6d7a77;font-size:13px;margin-left:4px}
.cvs-verdict{gap:12px;padding:14px 18px;border-radius:10px;font-weight:600}
.cvs-pending{padding:12px 16px;border-radius:10px;font-weight:600}
.cvs-pending .ak-muted{color:inherit;font-weight:400}
.cvs-mem{border:1px solid rgba(188,201,198,.6);border-radius:10px;padding:16px 18px}
.cvs-mem-head{display:flex;justify-content:space-between;align-items:baseline;gap:16px;flex-wrap:wrap;margin-bottom:12px}
.cvs-mem-t{font-size:14px;font-weight:600}
.cvs-mem-v{font-size:13px;color:#3d4947}
.cvs-mem-v strong{color:#111d23}
.cvs-bar{position:relative;height:12px;border-radius:999px;background:#e5eceb;overflow:visible}
.cvs-bar-free{position:absolute;left:0;top:0;bottom:0;border-radius:999px;background:#b9dcd8}
.cvs-bar-used{position:absolute;left:0;top:0;bottom:0;border-radius:999px;background:$acc}
.cvs-bar-need{position:absolute;top:-4px;bottom:-4px;width:2px;background:#865300}
.cvs-mem-legend{display:flex;flex-wrap:wrap;gap:18px;margin-top:10px;font-size:13px;color:#3d4947}
.cvs-mem-legend span{display:inline-flex;align-items:center;gap:6px}
.cvs-dot{display:inline-block;width:10px;height:10px;border-radius:3px}
.cvs-dot-used{background:$acc}
.cvs-dot-free{background:#b9dcd8}
.cvs-tick{display:inline-block;width:2px;height:12px;background:#865300}
.cvs-mem-warn{margin-top:10px;display:flex;align-items:center;gap:8px;color:#865300;font-size:13px;font-weight:600}
.cvs-kv{margin:0}
.cvs-kv>div{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid rgba(188,201,198,.5);font-size:14px}
.cvs-kv dt{font-weight:400;color:#111d23}
.cvs-kv dd{margin:0;font-weight:600;text-align:right;white-space:nowrap}
.cvs-foot{margin-top:auto;display:flex;gap:8px;align-items:flex-start;color:#6d7a77;font-size:13px}
</style>";
}
// Panneau des parametres -- fin

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
    $hoursEX[420]="7 {hours}";
    $hoursEX[480]="8 {hours}";

    $form[]=clamav_meta_fld_hash($tpl,$hoursEX, "ClamavRefreshDaemonTime", "{srv_clamav.RefreshDaemon}", $ClamavRefreshDaemonTime,false,"{srv_clamav.RefreshDaemon_text}");
    $form[]=clamav_meta_fld($tpl,"field_numeric","ClamavRefreshDaemonMemory","{refresh_daemon_MB} (M)",$ClamavRefreshDaemonMemory,"{srv_clamav.ClamavRefreshDaemonMemory}");

    $watchdogKeys=array("ClamavRefreshDaemonTime","ClamavRefreshDaemonMemory");
    if(clamav_meta_any_held($watchdogKeys)){$html[]=clamav_meta_banner($tpl);}
    if(clamav_meta_all_held($watchdogKeys)){clamav_meta_lock_form($tpl);}
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjaxSilent('clamav-status-parameters','$page?clamav-status-parameters=yes');",
        "AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
}

// reload_policy_get : GET /clamd/settings, tableau vide si le daemon ne connaît pas la route.
function reload_policy_get():array{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/clamd/settings"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["data"]) || !is_array($json["data"])){
        return array("ConcurrentDatabaseReload"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConcurrentDatabaseReload")),
            "ClamdNotReloadProduction"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamdNotReloadProduction")),
            "ReloadPendingSince"=>0,"RestartPendingSince"=>0,"ConcurrentMemoryOK"=>true,
            "ClamdRSSMB"=>0,"MemAvailableMB"=>0,"ProductionTimeNow"=>false,
            "ProductionTimeStart"=>"06:00","ProductionTimeEnd"=>"22:00");
    }
    return $json["data"];
}

function section_ConcurrentDatabaseReload_popup():bool{
    $tpl=new template_admin();
    $rp=reload_policy_get();
    $ConcurrentDatabaseReload=intval($rp["ConcurrentDatabaseReload"]);
    $ClamdNotReloadProduction=intval($rp["ClamdNotReloadProduction"]);
    $hours=htmlspecialchars($rp["ProductionTimeStart"]." - ".$rp["ProductionTimeEnd"]);

    $html[]=$tpl->div_explain("{clamd_reload_policy}||{clamd_reload_policy_explain}");
    if(empty($rp["ConcurrentMemoryOK"])){
        $html[]=$tpl->div_warning("{clamd_concurrent_memory_warning}||clamd: ".intval($rp["ClamdRSSMB"])."MB / {available}: ".intval($rp["MemAvailableMB"])."MB");
    }

    if(clamav_meta_any_held(array("ConcurrentDatabaseReload","ClamdNotReloadProduction"))){$html[]=clamav_meta_banner($tpl);}
    $form[]=clamav_meta_fld($tpl,"field_checkbox","ConcurrentDatabaseReload","{ConcurrentDatabaseReload}",$ConcurrentDatabaseReload,false,"{ConcurrentDatabaseReload_text}");
    $form[]=clamav_meta_fld($tpl,"field_checkbox","ClamdNotReloadProduction","{ClamdNotReloadProduction} ($hours)",$ClamdNotReloadProduction,false,"{ClamdNotReloadProduction_text}");
    $form[]=$tpl->field_checkbox("RestartNow","{clamd_restart_now}",0,false,"{clamd_restart_now_text}");
    $html[]=$tpl->form_outside("", @implode("\n", $form),"","{apply}",
        "BootstrapDialog1.close();LoadAjaxSilent('clamav-status-parameters','".CurrentPageName()."?clamav-status-parameters=yes');",
        "AsSystemAdministrator",true);

    // Impact de chaque réglage sur chaque fonctionnalité du service.
    $rows=array("auto_update","securiteinfo","unofficial","manual_update","upload","whitelist","apply","memory","traffic","protection");
    $html[]="<h3 style='margin-top:20px'>{clamd_reload_impacts}</h3>";
    $html[]="<table class='table table-bordered' style='width:100%'>";
    $html[]="<thead><tr><th style='width:1%' nowrap>{feature}</th><th style='width:49%'>{ConcurrentDatabaseReload}</th><th style='width:50%'>{ClamdNotReloadProduction}</th></tr></thead><tbody>";
    foreach($rows as $r){
        $html[]="<tr>";
        $html[]="<td style='width:1%' nowrap><strong>{clamd_feature_$r}</strong></td>";
        $html[]="<td>{clamd_impact_concurrent_$r}</td>";
        $html[]="<td>{clamd_impact_noprod_$r}</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody></table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// reload_policy_save : POST /clamd/settings — le daemon régénère clamd.conf,
// freshclam.conf et clamav-unofficial-sigs.conf et redémarre ce qu'il faut.
function reload_policy_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // politique Artica Meta : une cle tenue n'est pas envoyee (champ absent = inchange ; le demon
    // refuserait l'enregistrement en 423)
    $data=array("RestartNow"=>intval($_POST["RestartNow"] ?? 0)==1);
    foreach(array("ConcurrentDatabaseReload","ClamdNotReloadProduction") as $k){
        if(clamav_meta_key_held($k) || !isset($_POST[$k])){continue;}
        $data[$k]=intval($_POST[$k])==1?1:0;
    }
    $resp=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/clamd/settings",$data);
    $json=json_decode($resp,true);
    if(!is_array($json)){
        $raw=trim(str_replace(array("'","\\"),array(" "," "),$resp));
        echo $tpl->post_error($raw==""?"{error}":substr($raw,0,300));
        return false;
    }
    if(empty($json["Status"])){
        echo $tpl->post_error(isset($json["Error"])?(string)$json["Error"]:"{error}");
        return false;
    }
    admin_tracks("ClamAV database reload settings: ConcurrentDatabaseReload=".($data["ConcurrentDatabaseReload"] ?? "-")." ClamdNotReloadProduction=".($data["ClamdNotReloadProduction"] ?? "-")." (".(isset($json["data"]["action"])?$json["data"]["action"]:"").")");
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



    $form[]=clamav_meta_fld($tpl,"field_numeric","ClamavStreamMaxLength","{srv_clamav.StreamMaxLength} (M)",$ClamavStreamMaxLength,"{srv_clamav.StreamMaxLength_text}");
    $form[]=clamav_meta_fld($tpl,"field_numeric","ClamavMaxFileSize","{srv_clamav.MaxObjectSize} (M)",$ClamavMaxFileSize,"{srv_clamav.MaxObjectSize_text}");
    // ClamavMaxScanSize retire 15/11 : clamd applique MaxScanSize = ClamavMaxFileSize (clamavDaemon.Build)
    $form[]=clamav_meta_fld($tpl,"field_numeric","ClamavMaxFiles","{srv_clamav.ClamAvMaxFilesInArchive}",$ClamavMaxFiles,"{srv_clamav.ClamAvMaxFilesInArchive}");
    // champ "MaxFileSize" retire 15/11 : cle lue par personne, doublon de ClamavMaxFileSize
    $form[]=clamav_meta_fld($tpl,"field_numeric","ClamavMaxRecursion","{srv_clamav.ClamAvMaxRecLevel}",$ClamavMaxRecursion);
    $form[]=clamav_meta_fld($tpl,"field_checkbox","PhishingScanURLs","{srv_clamav.PhishingScanURLs}",$PhishingScanURLs,false,"{srv_clamav.PhishingScanURLs_text}");


    $form[]=clamav_meta_fld($tpl,"field_checkbox","EnableClamavSecuriteInfo","{securiteinfo_antivirus_databases}",$EnableClamavSecuriteInfo,false,"{securite_info_explain}");
    $form[]=clamav_meta_fld_hash($tpl,$Timez, "EnableClamavSecuriteSchedule", "{update_each}", $EnableClamavSecuriteSchedule,"{srv_clamav.RefreshDaemon_text}");



    $form[]=clamav_meta_fld($tpl,"field_checkbox","EnableClamavUnofficial","{enable_clamav_unofficial}",$EnableClamavUnofficial,false,"{clamav_unofficial_text}");

    $mainKeys=array("ClamavStreamMaxLength","ClamavMaxFileSize","ClamavMaxFiles","ClamavMaxRecursion","PhishingScanURLs",
        "EnableClamavSecuriteInfo","EnableClamavSecuriteSchedule","EnableClamavUnofficial");
    $html=array();
    if(clamav_meta_any_held($mainKeys)){$html[]=clamav_meta_banner($tpl);}
    if(clamav_meta_all_held($mainKeys)){clamav_meta_lock_form($tpl);}
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",section_js_form(),
        "AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'):string{$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}