<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10 - Service Pack 10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
$GLOBALS["ECAP_CLAMAV_ENABLE"]=true;

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["MsftncsiReport-js"])){MsftncsiReport_js();exit();}
if(isset($_GET["MsftncsiReport-popup"])){MsftncsiReport_popup();exit();}

if(isset($_GET["reconfigure-proxy-services"])){reconfigure_proxy_services_status();exit;}
if(isset($_GET["applications-squid-status"])){ echo app_status();exit;}
if(isset($_GET["bbr-js"])){bbr_js();exit;}
if(isset($_GET["bbr-popup"])){bbr_popup();exit;}
if(isset($_GET["bbr-popup-inner"])){echo bbr_popup_inner();exit;}
if(isset($_POST["EnableKernelBBR"])){bbr_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["dynacls"])){dynacls();exit;}
if(isset($_GET['app-status'])){app_status();exit;}
if(isset($_GET["status"])){page_status();exit;}
if(isset($_GET["requests"])){page_requests();exit;}
if(isset($_GET["traffic"])){page_traffic();exit;}
if(isset($_GET["cache"])){page_cache();exit;}
if(isset($_GET["objects"])){page_objects();exit;}
if(isset($_GET["watchdog"])){page_watchdog();exit;}
if(isset($_GET["squid-cache-buttons"])){squid_cache_buttons();exit;}
if(isset($_GET["squid-service-status"])){squid_service_status();exit;}
if(isset($_POST["watchdog"])){page_watchdog_save();exit;}
if(isset($_GET["proxy-tools"])){proxy_tools();exit;}
if(isset($_GET["emergency-js"])){emergency_js();exit;}
if(isset($_POST["emergency-enable"])){emergency_enable();exit;}
if(isset($_POST["emergency-disable"])){emergency_disable();exit;}
if(isset($_GET["emergency-off"])){emergency_remove_off();exit;}
if(isset($_GET["URLhaus"])){URLhaus_js();exit;}
if(isset($_GET["URLhaus-popup"])){URLhaus_popup();exit;}
if(isset($_GET["c-icap-install-js"])){cicap_install_js();exit;}
if(isset($_GET["c-icap-install-popup"])){cicap_install_popup();exit;}
if(isset($_GET["c-icap-uninstall-js"])){cicap_uninstall_ask();exit;}
if(isset($_POST["c-icap-uninstall"])){cicap_uninstall_confirm();exit;}

if(isset($_GET["goshield-uninstall-js"])){goshield_uninstall_js();exit;}
if(isset($_POST["goshield-uninstall"])){goshield_uninstall_confirm();exit;}
if(isset($_GET["goshield-install-js"])){goshield_install_js();exit;}
if(isset($_GET["goshield-install-popup"])){goshield_install_popup();exit;}

if(isset($_GET["theshield-install-js"])){theshield_install_js();exit;}
if(isset($_GET["theshield-install-popup"])){theshield_install_popup();exit;}
if(isset($_GET["theshield-uninstall-js"])){theshield_uninstall_js();exit;}

if(isset($_GET["ecap-clamav-install-js"])){ecap_clamav_install_js();exit;}
if(isset($_GET["ecap-clamav-uninstall-js"])){ecap_clamav_uninstall_ask();exit;}
if(isset($_GET["ecap-clamav-install-popup"])){ecap_clamav_install_popup();exit;}
if(isset($_POST["ecap-clamav-uninstall"])){ecap_clamav_install_confirm();exit;}



if(isset($_GET["flot1"])){flot1();exit;}
if(isset($_GET["flot2"])){flot2();exit;}
if(isset($_GET["flot3"])){flot3();exit;}
if(isset($_GET["flot4"])){flot4();exit;}
if(isset($_GET["flot5"])){flot5();exit;}
xgen();


function cicap_uninstall_confirm():bool{
    admin_tracks($_POST["c-icap-uninstall"]);
    return true;
}



function emergency_js():bool{
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs("/proxy/emergency/enable",
        "squid.urgency.disable.progress",
        "squid.urgency.disable.progress.txt",
        "progress-squidstatus-restart" );
    $tpl->js_confirm_execute("{squid_urgency_explain}","emergency-enable","yes",$jsrestart);
    return true;
}

function emergency_remove_off(){

    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/proxy/emergency/disable",
        "squid.urgency.disable.progress",
        "squid.urgency.disable.progress.txt",
        "progress-squidstatus-restart" );
    $tpl->js_confirm_execute("{disable_emergency_mode}","emergency-disable","yes",$jsrestart);
}

function emergency_enable(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUrgency", 1);
    admin_tracks("Turn to ON the Proxy service Global Emergency mode");
}
function emergency_disable():bool{
   return admin_tracks("Turn to FF the Proxy service Global Emergency mode");
}
function reconfigure_proxy_services_status():bool{
    $ReloadProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReloadProxyAfterReboot"));
    $tpl=new template_admin();

    $pointers="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\" ";
    // articarest runs it at start-up, once per boot (squid/after_reboot.go,
    // ex exec.squid-reboot.php + cron @reboot): no cron to look for any more.
    // Its events carry the name "proxy-after-reboot".
    $bt_js="OnClick=\"Loadjs('fw.system.events.tiny.php?file=proxy-after-reboot');\"";

    if($ReloadProxyAfterReboot==0){
        echo $tpl->_ENGINE_parse_body("<span class='label'>{disabled}</span>");
        return true;
    }
    // The snapshot restore at boot wins over the reconfiguration (exclusive options).
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestoreSnapshotProxyAfterReboot"))==1){
        echo $tpl->_ENGINE_parse_body("<span class='label label-warning'>{inactive}</span>");
        return true;
    }
    echo $tpl->_ENGINE_parse_body("<span class='label label-primary' $pointers $bt_js>{active2}</span>");
    return true;

}
function cicap_install_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{enable_c_icap}", "$page?c-icap-install-popup=yes",540);
}
function MsftncsiReport_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{error_internet_proxy}", "$page?MsftncsiReport-popup=yes",540);
}


function ecap_clamav_install_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{integrated_antivirus}", "$page?ecap-clamav-install-popup=yes",540);

}

function goshield_install_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{install} {KSRN_SERVER2}", "$page?goshield-install-popup=yes",540);
    return true;
}
function theshield_install_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{install} {reputation_services}", "$page?theshield-install-popup=yes",540);
    return true;

}
function cicap_install_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<p style='font-size:16px;margin-bottom: 30px'>{enable_c_icap_text}</p>";
    $html[]="<div style='margin-top:20px;margin-bottom:20px' id='icap-progress'>&nbsp;</div>";
    $html[]="<div style='text-align:right'>";

    $jsinstall=$tpl->framework_buildjs(
        "/cicap/install",
    "cicap.install.progress",
    "cicap.install.log","icap-progress","LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes');dialogInstance1.close();");


    $html[]=$tpl->button_autnonome("{enable_c_icap}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ecap_clamav_install_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<p style='font-size:16px;margin-bottom: 30px'>{integrated_antivirus_explain}</p>";
    $html[]="<div style='margin-top:20px;margin-bottom:20px' id='ecap-progress'>&nbsp;</div>";
    $html[]="<div style='text-align:right'>";

    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/install",
        "squid.ecap.progress",
        "squid.ecap.progress.log","ecap-progress","LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes');dialogInstance1.close();");


    $html[]=$tpl->button_autnonome("{integrated_antivirus}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function goshield_install_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<p style='font-size:16px;margin-bottom: 30px'>{filtering_service_explain}</p>";
    $html[]="<div style='margin-top:20px;margin-bottom:20px' id='goshield-progress'>&nbsp;</div>";
    $html[]="<div style='text-align:right'>";



    $jsinstall= $tpl->framework_buildjs("/goshield/install",
        "go.shield.server.progress",
        "go.shield.server.log",
        "goshield-progress","LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes');dialogInstance1.close();");

    $html[]=$tpl->button_autnonome("{install} {KSRN_SERVER2}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function theshield_install_popup():bool{
    //reputation_services
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<p style='font-size:16px;margin-bottom: 30px'>{KSRN_EXPLAIN}</p>";
    $html[]="<div style='margin-top:20px;margin-bottom:20px' id='ksrnfeatures-progress'>&nbsp;</div>";
    $html[]="<div style='text-align:right'>";

    $jsinstall = $tpl->framework_buildjs("/theshield/install", "ksrn.progress",
        "ksrn.log",
        "ksrnfeatures-progress","LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes');dialogInstance1.close();");

    $html[]=$tpl->button_autnonome("{install} {reputation_services}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function cicap_uninstall_ask():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsinstall=$tpl->framework_buildjs(
        "/cicap/uninstall",
        "cicap.install.progress",
        "cicap.install.log","progress-squidstatus-restart",
        "LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes')");

    echo $tpl->js_confirm_execute("{disable_feature} {antivirus_proxy}",
        "c-icap-uninstall","{disable_feature} {antivirus_proxy}",$jsinstall);
    return true;
}
function ecap_clamav_uninstall_ask():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsinstall=$tpl->framework_buildjs(
        "/proxy/ecap/uninstall",
        "squid.ecap.progress",
        "squid.ecap.progress.log","progress-squidstatus-restart",
        "LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes')");

    echo $tpl->js_confirm_execute("{disable_feature} {antivirus_proxy} eCAP",
        "ecap-clamav-uninstall","{disable_feature} {antivirus_proxy}",$jsinstall);
    return true;
}
function ecap_clamav_install_confirm():bool{
    admin_tracks("Uninstall Antivirus for Proxy (eCAP) mode");
    return true;
}
function goshield_uninstall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsinstall= $tpl->framework_buildjs("/goshield/uninstall",
        "go.shield.server.progress",
        "go.shield.server.log",
        "progress-squidstatus-restart","LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes')");

    echo $tpl->js_confirm_execute("{disable_feature} {KSRN_SERVER2}",
        "goshield-uninstall","{disable_feature} {KSRN_SERVER2}",$jsinstall);
    return true;

}
function goshield_uninstall_confirm():bool{
    admin_tracks($_POST["goshield-uninstall"]);
    return true;
}
function theshield_uninstall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $jsinstall= $tpl->framework_buildjs("ksrn.php?uninstall=yes", "ksrn.progress",
        "ksrn.log",
        "progress-squidstatus-restart","LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('applications-squid-status','$page?applications-squid-status=yes')");


    echo $tpl->js_confirm_execute("{disable_feature} {reputation_services}",
        "goshield-uninstall","{disable_feature} {reputation_services}",$jsinstall);
    return true;
}

function URLhaus_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("URL Haus", "$page?URLhaus-popup=yes");
    return true;
}
function URLhaus_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$acl="/etc/squid3/squid-block.acl";
	$handle=@fopen($acl,"r");
	if(!$handle){
		echo $tpl->FATAL_ERROR_SHOW_128("Unable to open $acl");
		return;
	}

	$rows=array();
	while (!feof($handle)) {
		$value=trim(fgets($handle));
		// La derniere iteration d'une boucle feof() lit une chaine vide: elle
		// produisait une ligne de tableau vide a chaque affichage.
		if($value===""){continue;}
		$rows[]=urlhaus_row($value);
	}
	fclose($handle);
	$shown=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{urls}</th>";
	$html[]="</tr></thead><tbody id='urlhaus-rows'>";
	if($shown==0){
		$html[]="<tr><td><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown</span>";
	$html[]="<span class='awc-pg' id='urlhaus-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(100,"urlhaus-rows","urlhaus-pager");
}

/**
 * Une URL de la liste de blocage. Elle etait tronquee a 130 caracteres avec des
 * points de suspension: sur une liste de blocage, la partie coupee est
 * justement celle qui distingue deux entrees d'un meme domaine. L'URL complete
 * est desormais rendue, le conteneur .awc-scroll gerant le debordement.
 */
function urlhaus_row($value):string{
	$value=str_replace("\\", "", $value);
	// Le contenu du fichier partait BRUT dans le HTML: une entree contenant des
	// balises etait interpretee par le navigateur.
	return "<tr><td class='awc-mono' style='word-break:break-all'>".htmlspecialchars($value)."</td></tr>";
}

/**
 * Une rangee de widgets de statut. Les tuiles vides (widget non applicable a
 * cette installation) sont ecartees: en tableau elles laissaient une cellule
 * blanche qui decalait visuellement la rangee.
 */
function xgen(){
$OPENVPN=false;	
$users=new usersMenus();
$page=CurrentPageName();
    $tpl=new template_admin();
$title="{your_proxy}";
$realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
if($realsquidversion<>null){$realsquidversion="&nbsp;v$realsquidversion";}
if($users->STATS_APPLIANCE){$title="{SQUID_STATS1}";$realsquidversion=null;}

$html=$tpl->page_header("$title$realsquidversion",
        "fas fa-tachometer-alt","{proxy_service_about}","$page?tabs=yes","proxy-status","progress-squidstatus-restart");



if(isset($_GET["main-page"])){
	$tpl=new template_admin(null,$html);
	echo $tpl->build_firewall();
	return;
}

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
    $MUNIN=false;
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
	if($MUNIN_CLIENT_INSTALLED==1){if($EnableMunin==1){$MUNIN=true;}}
    $DisableMinimalSquidStatistics = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMinimalSquidStatistics"));
    $ArticaProxyToSIEM= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxyToSIEM"));

    if($ArticaProxyToSIEM==1){
        $DisableMinimalSquidStatistics=1;
    }


	//enable_watchdog_squid_explain
	// <i class="fa-sharp fa-solid fa-chart-pie"></i>
	$array["<i class='fas fa-tachometer-alt'></i>&nbsp;{status}"]="$page?status=yes";
    if($DisableMinimalSquidStatistics==0){
        $array["<i class='fa-solid fa-chart-pie'></i>&nbsp;{statistics}"]="fw.proxy.status.minimal.statistics.php";
    }
    $array["<i class='fa-solid fa-jet-fighter'></i>&nbsp;{performance}"]="fw.proxy.status.performance.php";
    $array["<i class='fa-solid fa-users'></i>&nbsp;{members}"]="fw.proxy.status.ipmembers.php";
    $array["<i class='fa-solid fa-shield-dog'></i>&nbsp;{watchdog}"]="fw.proxy.status.watchdog.php";
	if($MUNIN){
		$array["{requests}"]="$page?requests=yes";
		$array["{traffic}"]="$page?traffic=yes";
		$array["{caches}"]="$page?cache=yes";
		$array["{objects}"]="$page?objects=yes";
	}
	
	
	echo $tpl->tabs_default($array);
}

function page_watchdog_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UrlCheckingEnable",$_POST["UrlCheckingEnable"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UrlCheckingAddress",$_POST["UrlCheckingAddress"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UrlCheckingAction",$_POST["UrlCheckingAction"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UrlCheckingProxyPort",$_POST["UrlCheckingProxyPort"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UrlCheckingInterval",$_POST["UrlCheckingInterval"]);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("internetwatchuri",$_POST["internetwatchuri"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UrlNetAnalyze",$_POST["UrlNetAnalyze"]);


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ReloadProxyAfterReboot",$_POST["ReloadProxyAfterReboot"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RestoreSnapshotProxyAfterReboot",$_POST["RestoreSnapshotProxyAfterReboot"]);



    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    $MonitConfig["watchdog"]=$_POST["watchdog"];

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MonitSquidMaxCPU",$_POST["MonitSquidMaxCPU"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MonitSquidMaxCPUCycles",$_POST["MonitSquidMaxCPUCycles"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MonitSquidMaxMemory",$_POST["MonitSquidMaxMemory"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MonitSquidMaxMemoryCycles",$_POST["MonitSquidMaxMemoryCycles"]);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidWatchdogMonitConfig",base64_encode(serialize($MonitConfig)));
    admin_tracks("Proxy watchdog parameters saved Url Checking: {$_POST["UrlCheckingEnable"]} CPU Watchdog = {$_POST["watchdog"]}");
    admin_tracks("Proxy watchdog parameters Reconfigure all services after reboot = {$_POST["ReloadProxyAfterReboot"]}");

    // Monit thresholds + URL checking / network analysis crons (articarest).
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/watchdog/monit");
}

function page_watchdog(){
    $tpl=new template_admin();
    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    $MonitSquidMaxCPU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxCPU"));
    $MonitSquidMaxCPUCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxCPUCycles"));
    if($MonitSquidMaxCPUCycles==0){$MonitSquidMaxCPUCycles=5;}

    $ReloadProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReloadProxyAfterReboot"));
    $RestoreSnapshotProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestoreSnapshotProxyAfterReboot"));



    if(!isset($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
    if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
    $LogsRotateDefaultSizeRotation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation"));
    if($LogsRotateDefaultSizeRotation<5){$LogsRotateDefaultSizeRotation=100;}


    // articarest: monit of Squid + watchdog crons (squid2.php?monit-config called an
    // undefined function and never applied anything).
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.monit.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.monit.progress.log";
    $ARRAY["CMD"]="/proxy/watchdog/monit";
    $ARRAY["PRIV"]="AsSquidAdministrator";
    $ARRAY["TITLE"]="{watchdog}";
    $ARRAY["AFTER"]="blur()";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-squidstatus-restart')";

    $zUrlCheckingAction[0]="{do_nothing}";
    $zUrlCheckingAction[1]="{APP_SQUID}: {reconfigure}";
    $zUrlCheckingAction[2]="{APP_SQUID}: {restart}";
    $zUrlCheckingAction[3]="{APP_SQUID}: {reload}";

    $timeout[0]="1mn";
    $timeout[5]="5mn";
    $timeout[10]="10mn";
    $timeout[15]="15mn";
    $timeout[30]="30mn";

    $MonitSquidMaxMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemory"));
    $MonitSquidMaxMemoryCycles=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MonitSquidMaxMemoryCycles"));
    $UrlCheckingEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingEnable"));
    $UrlCheckingAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAddress"));
    $UrlCheckingAction=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingAction"));
    $UrlCheckingProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingProxyPort"));
    $UrlCheckingInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingInterval"));
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $HaClusterProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProxyPort"));
    if($UrlCheckingAction==0){$UrlCheckingAction=3;}
    if($UrlCheckingAddress==null){$UrlCheckingAddress="https://www.google.com";}


    $ql=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT * FROM proxy_ports WHERE enabled=1";
    $resultsPorts = $ql->QUERY_SQL($sql);
    foreach ($resultsPorts as $index=>$lignePorts){
        $eth=$lignePorts["nic"];
        $port=$lignePorts["port"];
        $IDPort=$lignePorts["ID"];
        if($HaClusterClient==1){
            if($port==$HaClusterProxyPort){continue;}
        }
        $PortDirectionS[$IDPort]="{port} $port [$eth]";
    }

    $CPU[0]="{disabled}";
    for($i=50;$i<101;$i++){ $CPU[$i]="{$i}%"; }
    $CPU[110]="{disabled}";

    $MINUTES[1]="{during} 1 {minute}";
    for($i=2;$i<121;$i++){
        $MINUTES[$i]="{during} $i {minutes}";
    }

    $form[]=$tpl->field_section("{after_reboot}");
    $form[]=$tpl->field_checkbox("ReloadProxyAfterReboot","span:{reconfigure_proxy_services}",$ReloadProxyAfterReboot,false,"url:https://wiki.articatech.com/en/proxy-service/troubleshooting/reconfigure-reboot;{ReloadProxyAfterReboot}");


    $form[]=$tpl->field_checkbox("RestoreSnapshotProxyAfterReboot","{RestoreSnapshotProxyAfterReboot}",$RestoreSnapshotProxyAfterReboot,false,"{RestoreSnapshotProxyAfterReboot_explain}");
    //$form[]=$tpl->field_checkbox("FiledescriptorsProxyAfterReboot","{FiledescriptorsProxyAfterReboot}",$FiledescriptorsProxyAfterReboot,false,"{FiledescriptorsProxyAfterReboot_explain}");

    $form[]=$tpl->field_section("URL Checking");
    $internetwatchuri=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("internetwatchuri"));
    $UrlNetAnalyze=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlNetAnalyze"));
    if($internetwatchuri==null){$internetwatchuri="https://www.google.com";}

    $form[]=$tpl->field_checkbox("UrlNetAnalyze","{UrlNetAnalyze}",$UrlNetAnalyze,"internetwatchuri","{UrlNetAnalyze_explain}");
    $form[]=$tpl->field_text("internetwatchuri","{url}",$internetwatchuri);

    $form[]=$tpl->field_checkbox("UrlCheckingEnable","{enable}",$UrlCheckingEnable,"UrlCheckingAddress,UrlCheckingAction,UrlCheckingProxyPort");
    $form[]=$tpl->field_text("UrlCheckingAddress","{url}",$UrlCheckingAddress);
    $form[]=$tpl->field_array_hash($PortDirectionS,"UrlCheckingProxyPort","{listen_port}",$UrlCheckingProxyPort);
    $form[]=$tpl->field_array_hash($zUrlCheckingAction,"UrlCheckingAction","{then}",$UrlCheckingAction);
    $form[]=$tpl->field_array_hash($timeout,"UrlCheckingInterval","{interval}",$UrlCheckingInterval);

    $form[]=$tpl->field_section("{restart_service}");
    $form[]=$tpl->field_checkbox("watchdog","{enable}",$MonitConfig["watchdog"],"MonitSquidMaxCPU,MonitSquidMaxCPUCycles");
    $form[]=$tpl->field_array_hash($CPU,"MonitSquidMaxCPU","{if_system_cpu_exceed}",$MonitSquidMaxCPU);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitSquidMaxCPUCycles","&nbsp;",$MonitSquidMaxCPUCycles);

    $form[]=$tpl->field_array_hash($CPU,"MonitSquidMaxMemory","{if_system_memory_exceed}",$MonitSquidMaxMemory);
    $form[]=$tpl->field_array_hash($MINUTES,"MonitSquidMaxMemoryCycles","&nbsp;",$MonitSquidMaxMemoryCycles);


    $TINY_ARRAY["TITLE"]="{watchdog}";
    $TINY_ARRAY["ICO"]="fa-solid fa-shield-dog";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $page=CurrentPageName();
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsSquidAdministrator").
        "<script>LoadAjaxSilent('reconfigure_proxy_services','$page?reconfigure-proxy-services=yes');$jstiny</script>";



}

function page_traffic(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="squid_traffic-day.png";
	$f[]="squid_traffic-week.png";
	$f[]="squid_traffic-month.png"; 
	$f[]="squid_traffic-year.png";

	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}

    $TINY_ARRAY["TITLE"]="{statistics}: {traffic}";
    $TINY_ARRAY["ICO"]="fa-regular fa-chart-line";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
	
}

function page_objects(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="squid_objectsize-day.png";
	$f[]="squid_objectsize-week.png";
	$f[]="squid_objectsize-month.png";
	$f[]="squid_objectsize-year.png";
	
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}

    $TINY_ARRAY["TITLE"]="{statistics}: {objects}";
    $TINY_ARRAY["ICO"]="fa-regular fa-chart-line";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}

function page_cache(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="squid_cache-day.png";
	$f[]="squid_cache-week.png";
	$f[]="squid_cache-month.png";
	$f[]="squid_cache-year.png";

    $TINY_ARRAY["TITLE"]="{statistics}: {caches}";
    $TINY_ARRAY["ICO"]="fa-regular fa-chart-line";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
	
}

function page_status(){
	$page=CurrentPageName();
	echo "<div class=\"wrapper wrapper-content animated fadeInRight\">
	<div class=row id='applications-squid-status'></div>
	</div>
	<script>LoadAjax('applications-squid-status','$page?applications-squid-status=yes');</script>";
	
}
/** Tuile: taux de remplissage du cache DISQUE (storage_swap). */
/* ------------------------------------------------------------------------- *
 *  Tableau de bord du proxy — design « Precision Enterprise Interface »
 *  (/home/claude/images/stitch_proxy_dashboard_modernization.zip)
 *
 *  Composants portes a la page, prefixe pxs-. Les tuiles « glass » de
 *  widget_h()/SERVICE_STATUS() restent le design PARTAGE de la console (colonne
 *  de gauche de ce handler, et toutes les autres pages): ce tableau de bord ne
 *  les redefinit pas, il pose ses propres composants a cote.
 *
 *  Une fonction par composant (regle « un widget = une fonction »).
 * ------------------------------------------------------------------------- */

/**
 * Feuille de style du tableau de bord, emise UNE fois par requete.
 *
 * A concatener AUTOUR de _ENGINE_parse_body(), jamais dedans: sa regex
 * #{(.+?)}# prend chaque corps de regle CSS pour un {jeton} et declenche une
 * recherche memcached par regle.
 *
 * Pas de font-family: la maquette demande Inter et JetBrains Mono, qu'aucune
 * feuille de la console ne charge. Herite donc de la police du theme -- une
 * police absente serait remplacee au hasard du poste, ce qui donnerait un
 * tableau de bord different d'un navigateur a l'autre.
 */
function pxs_style():string{
    if(!empty($GLOBALS["PXS_CSS_DONE"])){return "";}
    $GLOBALS["PXS_CSS_DONE"]=true;
    return "<style>
/* Le composant declare son propre modele de boite: sans cela ses hauteurs
   (bouton a 32px) dependraient du reset de la page hote -- mesure 34px sur une
   page sans border-box global. */
.pxs-card,.pxs-card *{box-sizing:border-box}
/* CSS grid et non flex: en flex, align-items:stretch etire la CELLULE mais la
   carte gardait sa hauteur naturelle -- dans une meme rangee, une tuile avec
   jauge et note de pied depassait sa voisine qui n'en a pas. Ici les colonnes
   sont egales (auto-fill + 1fr) et grid-auto-rows:1fr egalise toutes les
   rangees entre elles, y compris la derniere. */
.pxs-grid{display:grid;grid-template-columns:repeat(12,1fr);grid-auto-rows:1fr;gap:16px;margin:0 0 16px 0}
.pxs-grid>div{display:flex;min-width:0}
.pxs-grid>div>.pxs-card{flex:1 1 auto;min-width:0}
/* Largeur d'une tuile en colonnes de la grille: la composition des rangees est
   decidee par l'appelant (pxs_grid), de sorte qu'une rangee remplisse toute la
   largeur -- sinon la derniere laisse des cellules vides. Ces regles sont moins
   specifiques que « .pxs-grid>div » ci-dessous: les replis les emportent. */
.pxs-c1{grid-column:span 1}.pxs-c2{grid-column:span 2}.pxs-c3{grid-column:span 3}
.pxs-c4{grid-column:span 4}.pxs-c5{grid-column:span 5}.pxs-c6{grid-column:span 6}
.pxs-c7{grid-column:span 7}.pxs-c8{grid-column:span 8}.pxs-c9{grid-column:span 9}
.pxs-c10{grid-column:span 10}.pxs-c11{grid-column:span 11}.pxs-c12{grid-column:span 12}
.pxs-card{background:#fff;border:1px solid #e2e8f0;border-radius:6px;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:box-shadow .15s,border-color .15s}
.pxs-card:hover{box-shadow:0 4px 12px -2px rgba(15,23,42,.08);border-color:#cbd5e1}
.pxs-kpi{position:relative;display:flex;flex-direction:column;padding:12px 14px 12px 17px;height:100%;width:100%}
.pxs-bot{margin-top:auto}
.pxs-kpi:before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:6px 0 0 6px;background:#94a3b8}
.pxs-on .pxs-kpi:before{background:#10b981}
.pxs-info .pxs-kpi:before{background:#0d9488}
.pxs-warn .pxs-kpi:before{background:#f59e0b}
.pxs-err .pxs-kpi:before{background:#ef4444}
.pxs-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.pxs-label{font-size:11px;font-weight:500;line-height:14px;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
.pxs-ico{color:#94a3b8;font-size:14px;flex:0 0 auto}
.pxs-val{font-size:28px;font-weight:700;line-height:32px;letter-spacing:-.03em;color:#0f172a;margin-top:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pxs-off .pxs-val{color:#94a3b8}
.pxs-unit{font-size:13px;font-weight:400;letter-spacing:0;color:#64748b;margin-left:4px}
.pxs-meter{height:4px;border-radius:9999px;background:#e2e8f0;margin-top:10px;overflow:hidden}
.pxs-meter>span{display:block;height:100%;background:#94a3b8}
.pxs-on .pxs-meter>span{background:#10b981}
.pxs-info .pxs-meter>span{background:#0d9488}
.pxs-warn .pxs-meter>span{background:#f59e0b}
.pxs-err .pxs-meter>span{background:#ef4444}
.pxs-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:8px;font-size:12px;line-height:16px;color:#64748b}
.pxs-a{color:#0d9488!important;text-decoration:none!important;font-weight:500;white-space:nowrap;cursor:pointer}
.pxs-a:hover{text-decoration:underline!important}
.pxs-sec{padding:14px 16px;margin-bottom:16px}
.pxs-sec-h{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
.pxs-sec-t{font-size:16px;font-weight:600;line-height:24px;letter-spacing:-.01em;color:#0f172a}
.pxs-sec-t i{color:#0d9488;margin-right:8px}
.pxs-sec-c{font-size:12px;line-height:16px;color:#64748b;white-space:nowrap}
.pxs-mods{display:grid;grid-template-columns:repeat(12,1fr);grid-auto-rows:1fr;gap:12px}
.pxs-mods>div{display:flex;min-width:0}
.pxs-mods>div>.pxs-mod{flex:1 1 auto;min-width:0}
.pxs-mod{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;height:100%}
.pxs-mod-i{width:40px;height:40px;border-radius:6px;background:#f0fdfa;color:#0d9488;display:inline-flex;align-items:center;justify-content:center;font-size:16px;flex:0 0 auto}
.pxs-mod-off .pxs-mod-i{background:#f1f5f9;color:#94a3b8}
.pxs-mod-b{flex:1 1 auto;min-width:0}
.pxs-mod-t{display:flex;align-items:center;gap:8px;flex-wrap:nowrap;font-size:14px;font-weight:600;line-height:20px;color:#0f172a}
.pxs-mod-n{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.pxs-mod-t .pxs-badge{flex:0 0 auto}
.pxs-mod-off .pxs-mod-t{color:#64748b}
.pxs-mod-s{font-size:12px;line-height:16px;color:#64748b;margin-top:2px;overflow:hidden;text-overflow:ellipsis}
.pxs-badge{display:inline-block;font-size:11px;font-weight:500;line-height:14px;letter-spacing:.02em;padding:3px 8px;border-radius:9999px;background:#f1f5f9;color:#94a3b8;white-space:nowrap}
.pxs-badge-on{background:#f0fdfa;color:#0d9488}
.pxs-badge-warn{background:#fffbeb;color:#b45309}
.pxs-badge-err{background:#fef2f2;color:#dc2626}
.pxs-acts{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;flex:0 0 auto}
.pxs-bt{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border:1px solid #cbd5e1;border-radius:4px;background:#fff;color:#334155!important;font-size:13px;font-weight:500;white-space:nowrap;text-decoration:none!important;cursor:pointer}
.pxs-bt:hover{background:#f8fafc;color:#0f172a!important}
.pxs-bt-p{background:#0d9488;border-color:#0d9488;color:#fff!important}
.pxs-bt-p:hover{background:#0f766e;border-color:#0f766e;color:#fff!important}
.pxs-bt-d{background:#fef2f2;border-color:#fecaca;color:#dc2626!important}
.pxs-bt-d:hover{background:#ef4444;border-color:#ef4444;color:#fff!important}
.pxs-graphs{display:flex;flex-direction:column;gap:12px}
.pxs-graphs img{display:block;max-width:100%;height:auto;margin:0 auto}
/* Replis: deux tuiles par rangee sous 1200px, une seule sous 820px. Ces
   selecteurs (classe + type) sont plus specifiques que .pxs-cN, donc ils
   l'emportent sans !important. */
@media(max-width:1200px){.pxs-grid>div,.pxs-mods>div{grid-column:span 6}
/* Compte impair: la derniere tuile prend la largeur entiere, sinon elle laisse
   une demi-rangee vide a cote d'elle (mesure a 1024px: 496px de vide). */
.pxs-grid>div:last-child:nth-child(odd),.pxs-mods>div:last-child:nth-child(odd){grid-column:span 12}}
@media(max-width:820px){.pxs-grid>div,.pxs-mods>div{grid-column:span 12}
.pxs-mod{flex-wrap:wrap}.pxs-acts{justify-content:flex-start}}
</style>";
}

/**
 * Une tuile de metrique: intitule, valeur, jauge facultative, note de pied.
 *
 * $k: LABEL, ICON, VALUE, UNIT, METER (0-100 ou null), FOOT, LINK
 * (array NAME/JS), STATE parmi on|info|warn|err|off -- l'etat colore le
 * liseret de gauche et la jauge, et grise la valeur quand rien ne tourne.
 */
function pxs_kpi(array $k):string{
    $state=isset($k["STATE"])?preg_replace('/[^a-z]/','',(string)$k["STATE"]):"off";
    if($state==""){$state="off";}
    $ico=isset($k["ICON"])?(string)$k["ICON"]:"";
    $unit=(isset($k["UNIT"]) && trim((string)$k["UNIT"])!=="")
        ?"<span class='pxs-unit'>".$k["UNIT"]."</span>":"";
    $h ="<div class='pxs-card pxs-$state'><div class='pxs-kpi'>";
    $h.="<div class='pxs-head'><span class='pxs-label'>".$k["LABEL"]."</span>";
    if($ico!==""){$h.="<i class='$ico pxs-ico'></i>";}
    $h.="</div>";
    $h.="<div class='pxs-val'>".$k["VALUE"]."$unit</div>";
    // Jauge et note de pied dans un bloc plaque en bas (.pxs-bot): les tuiles
    // d'une meme grille font la meme hauteur, donc sans cela une tuile sans
    // jauge laisserait un vide au milieu au lieu du bas.
    $bot="";
    if(isset($k["METER"]) && $k["METER"]!==null){
        $m=floatval($k["METER"]);
        if($m<0){$m=0;}
        if($m>100){$m=100;}
        $bot.="<div class='pxs-meter'><span style='width:".round($m,2)."%'></span></div>";
    }
    $foot=isset($k["FOOT"])?(string)$k["FOOT"]:"";
    $link="";
    if(isset($k["LINK"]) && is_array($k["LINK"]) && isset($k["LINK"]["NAME"])){
        $ljs=isset($k["LINK"]["JS"])?$k["LINK"]["JS"]:"blur();";
        $link="<a class='pxs-a' href=\"javascript:blur();\" OnClick=\"$ljs\">"
             .$k["LINK"]["NAME"]."&nbsp;&rsaquo;</a>";
    }
    if($foot!=="" || $link!==""){
        $bot.="<div class='pxs-foot'><span>$foot</span>$link</div>";
    }
    if($bot!==""){ $h.="<div class='pxs-bot'>$bot</div>"; }
    $h.="</div></div>";
    return $h;
}

/**
 * La grille des tuiles de metriques, sur 12 colonnes.
 *
 * $rows decrit la composition voulue -- array(4,3) = quatre tuiles puis trois.
 * Les tuiles d'une meme rangee font alors exactement la meme largeur ET la
 * rangee remplit toute la largeur: aucune cellule vide en bout de grille, ce
 * qu'une grille en auto-fill laissait forcement (7 tuiles sur 5 colonnes).
 * grid-auto-rows:1fr egalise ensuite les rangees ENTRE ELLES, donc les sept
 * tuiles ont la meme hauteur, jauge ou pas, note de pied ou pas.
 *
 * Une tuile vide n'occupe pas de cellule, et la composition est rattrapee si
 * elle ne couvre pas tout: les tuiles restantes terminent la derniere rangee.
 */
function pxs_grid(array $tiles,array $rows=array()):string{
    $cells=array();
    foreach($tiles as $t){
        if(trim((string)$t)===""){continue;}
        $cells[]=$t;
    }
    $total=count($cells);
    if($total==0){return "";}
    if(count($rows)==0){$rows=array($total);}

    $h="<div class='pxs-grid'>";
    $i=0;
    foreach($rows as $n){
        $n=intval($n);
        if($n<1){continue;}
        if($i>=$total){break;}
        // Une rangee incomplete (moins de tuiles que prevu) partage la largeur
        // entre ce qui reste, plutot que de laisser des trous.
        $left=$total-$i;
        if($n>$left){$n=$left;}
        $span=intval(12/$n);
        if($span<1){$span=1;}
        for($k=0;$k<$n;$k++){
            $h.="<div class='pxs-c$span'>".$cells[$i]."</div>";
            $i++;
        }
    }
    while($i<$total){
        $h.="<div class='pxs-c3'>".$cells[$i]."</div>";
        $i++;
    }
    $h.="</div>";
    return $h;
}

/**
 * Une tuile de module: pastille d'icone, nom, etat, detail mesure, actions.
 *
 * $m: ICON, TITLE, BADGE, SUBTITLE, STATE (on|warn|err|off), ACTIONS
 * (array de array NAME/JS/ICON/KIND avec KIND parmi primary|danger|ghost).
 * Le sous-titre porte une MESURE (nombre de requetes, taille du cache) et non
 * une phrase de presentation: c'est ce que l'exploitant vient lire.
 */
function pxs_module(array $m):string{
    $state=isset($m["STATE"])?(string)$m["STATE"]:"off";
    $badge="pxs-badge";
    if($state=="on"){$badge.=" pxs-badge-on";}
    if($state=="warn"){$badge.=" pxs-badge-warn";}
    if($state=="err"){$badge.=" pxs-badge-err";}
    $wrap=($state=="on")?"pxs-mod":"pxs-mod pxs-mod-off";
    $h ="<div class='$wrap'>";
    $h.="<span class='pxs-mod-i'><i class='".$m["ICON"]."'></i></span>";
    $h.="<div class='pxs-mod-b'><div class='pxs-mod-t'>"."<span class='pxs-mod-n'>".$m["TITLE"]."</span>";
    if(isset($m["BADGE"]) && trim((string)$m["BADGE"])!==""){
        $h.="<span class='$badge'>".$m["BADGE"]."</span>";
    }
    $h.="</div>";
    if(isset($m["SUBTITLE"]) && trim((string)$m["SUBTITLE"])!==""){
        $h.="<div class='pxs-mod-s'>".$m["SUBTITLE"]."</div>";
    }
    $h.="</div>";
    $acts=array();
    if(isset($m["ACTIONS"]) && is_array($m["ACTIONS"])){
        foreach($m["ACTIONS"] as $a){
            if(!is_array($a) || !isset($a["NAME"])){continue;}
            $kind=isset($a["KIND"])?(string)$a["KIND"]:"";
            $cls="pxs-bt";
            if($kind=="primary"){$cls.=" pxs-bt-p";}
            if($kind=="danger"){$cls.=" pxs-bt-d";}
            $i=(isset($a["ICON"]) && $a["ICON"]!=="")?"<i class='".$a["ICON"]."'></i>":"";
            $js=isset($a["JS"])?$a["JS"]:"blur();";
            $acts[]="<a class='$cls' href=\"javascript:blur();\" OnClick=\"$js\">$i".$a["NAME"]."</a>";
        }
    }
    if(count($acts)>0){$h.="<div class='pxs-acts'>".@implode("",$acts)."</div>";}
    $h.="</div>";
    return $h;
}

/** La carte qui regroupe les modules de filtrage, avec leur compte. */
function pxs_modules_card(array $mods):string{
    $tiles=array();
    foreach($mods as $m){
        if(trim((string)$m)===""){continue;}
        $tiles[]=$m;
    }
    if(count($tiles)==0){return "";}
    // Deux par rangee; un compte impair donne toute la largeur au dernier,
    // plutot qu'une cellule vide a cote de lui.
    $cells=array();
    $n=count($tiles);
    foreach($tiles as $idx=>$m){
        $span=($n%2==1 && $idx==$n-1)?12:6;
        $cells[]="<div class='pxs-c$span'>$m</div>";
    }
    $h ="<div class='pxs-card pxs-sec'>";
    $h.="<div class='pxs-sec-h'>";
    $h.="<div class='pxs-sec-t'><i class='fas fa-shield-halved'></i>{proxy_modules_services}</div>";
    $h.="<div class='pxs-sec-c'>".count($cells)." {subsystems_listed}</div>";
    $h.="</div>";
    $h.="<div class='pxs-mods'>".@implode("",$cells)."</div>";
    $h.="</div>";
    return $h;
}

/** La carte des courbes de bande passante (images generees par le collecteur). */
function pxs_graphs_card(array $images):string{
    $imgs=array();
    foreach($images as $src){
        if(trim((string)$src)===""){continue;}
        $imgs[]="<img src='".htmlspecialchars((string)$src)."' alt=''>";
    }
    if(count($imgs)==0){return "";}
    $h ="<div class='pxs-card pxs-sec'>";
    $h.="<div class='pxs-sec-h'><div class='pxs-sec-t'><i class='fas fa-chart-area'></i>{bandwidth}</div></div>";
    $h.="<div class='pxs-graphs'>".@implode("",$imgs)."</div>";
    $h.="</div>";
    return $h;
}

function widget_cache():string{
    $SquidMicrosoftConnectedCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMicrosoftConnectedCache"));
    $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));
    if($SquidMicrosoftConnectedCache==1){$SquidDisableMemoryCache=1;}
    if($SquidDisableMemoryCache==1){
        return pxs_kpi(array("LABEL"=>"{used_cache}","ICON"=>"fas fa-hdd",
            "STATE"=>"off","VALUE"=>"{inactive2}"));
    }

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/mgrinfo"));
    if(!is_object($data) || !property_exists($data,"Info") || !is_object($data->Info)){
        return pxs_kpi(array("LABEL"=>"{used_cache}","ICON"=>"fas fa-hdd",
            "STATE"=>"err","VALUE"=>"{error}"));
    }
    $MgrInfo=$data->Info;
    $ALL_CACHES=FormatBytes($MgrInfo->storage_swap_size);
    $ALL_CACHES_PERC=floatval($MgrInfo->storage_swap_capacity);

    if($ALL_CACHES_PERC>0){
        $state="on";
        if($ALL_CACHES_PERC>80){$state="warn";}
        if($ALL_CACHES_PERC>95){$state="err";}
        return pxs_kpi(array("LABEL"=>"{used_cache}","ICON"=>"fas fa-hdd","STATE"=>$state,
            "VALUE"=>$ALL_CACHES_PERC,"UNIT"=>"%","METER"=>$ALL_CACHES_PERC,"FOOT"=>$ALL_CACHES));
    }
    return pxs_kpi(array("LABEL"=>"{used_cache}","ICON"=>"fas fa-hdd",
        "STATE"=>"off","VALUE"=>"{none}","FOOT"=>$ALL_CACHES));
}

/** Tuile: charge CPU du proxy, et nombre d'instances SMP actives. */
/**
 * Module: le service de cache disque (installation, capacite).
 *
 * Etait construit en ligne dans app_status() sous le nom $cache_capacity, avec
 * sa capacite en guise de valeur. C'est un service qu'on installe ou retire,
 * donc un module; la capacite devient le detail mesure du sous-titre, et le
 * taux de remplissage reste une tuile de metrique (widget_cache()).
 */
function widget_caching():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $M=array("ICON"=>ico_database,"TITLE"=>"{caching}","STATE"=>"off","BADGE"=>"{inactive2}");

    $SquidMicrosoftConnectedCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMicrosoftConnectedCache"));
    $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));
    if($SquidMicrosoftConnectedCache==1){$SquidDisableMemoryCache=1;}
    if($SquidDisableMemoryCache==1){ return pxs_module($M); }

    $jsrefres="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');"
        ."LoadAjaxSilent('top-barr','fw-top-bar.php');"
        ."LoadAjax('applications-squid-status','$page?applications-squid-status=yes')";
    $SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));

    if($SquidCachesProxyEnabled==0){
        // Hors licence Corporate le bouton reste affiche mais inerte, comme avant:
        // le retirer laisserait croire que la fonction n'existe pas.
        $js="blur();";
        if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $js=$tpl->framework_buildjs("/proxy/caches/feature/on",
                "squid.access.center.progress","squid.access.center.progress.log",
                "progress-squidstatus-restart",$jsrefres);
        }
        $M["ACTIONS"]=array(array("NAME"=>"{install_feature}","ICON"=>"fas fa-circle-plus",
            "KIND"=>"primary","JS"=>$js));
        return pxs_module($M);
    }

    $js=$tpl->framework_buildjs("/proxy/caches/feature/off",
        "squid.access.center.progress","squid.access.center.progress.log",
        "progress-squidstatus-restart",$jsrefres);
    $M["ACTIONS"]=array(array("NAME"=>"{uninstall}","ICON"=>"fas fa-ban","JS"=>$js));

    $CountOfSizeCaches=CountOfSizeCaches();
    if($CountOfSizeCaches==0){
        // Service installe mais AUCUN cache defini: ni actif ni inactif.
        $M["STATE"]="warn";
        $M["BADGE"]="{nothing}";
        return pxs_module($M);
    }
    $M["STATE"]="on";
    $M["BADGE"]="{active2}";
    $M["SUBTITLE"]=FormatBytes($CountOfSizeCaches);
    return pxs_module($M);
}

function widget_cpus(){
    $CPU=CountOfSNMP();
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        $_cpuJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/cpu-number"));
        $CPU_NUMBER=(is_object($_cpuJson) && !empty($_cpuJson->success))?intval($_cpuJson->cpu_number):2;
    }

    $Squid5min=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Squid5min"));
    if(!is_array($Squid5min)){$Squid5min=array();}
    if(!isset($Squid5min["cpu_usage"])){$Squid5min["cpu_usage"]=0;}
    $cpu_usage=round($Squid5min["cpu_usage"],2);

    $state="on";
    if($cpu_usage>70){$state="warn";}
    if($cpu_usage>90){$state="err";}
    return pxs_kpi(array("LABEL"=>"CPUs ($CPU/$CPU_NUMBER)","ICON"=>"fas fa-microchip",
        "STATE"=>$state,"VALUE"=>$cpu_usage,"UNIT"=>"%","METER"=>$cpu_usage,
        "FOOT"=>"$CPU/$CPU_NUMBER SMP"));
}

/** Tuile: temps de reponse observe, avec son unite adaptee a l'ordre de grandeur. */
function  widget_latency():string{
    $SQUID_LATEST_LATENCY=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_LATEST_LATENCY"));
    $unit="ms";
    if($SQUID_LATEST_LATENCY>0){
        $unit="s";
        $SQUID_LATEST_LATENCY=$SQUID_LATEST_LATENCY /1000;
    }

    if($SQUID_LATEST_LATENCY>60){
        list($minutes, $remainingSeconds) = secondsToMinutes($SQUID_LATEST_LATENCY);
        if($remainingSeconds>0) {
            $unit="";
            $SQUID_LATEST_LATENCY = "{$minutes}mn,{$remainingSeconds}s";
        }else{
            $SQUID_LATEST_LATENCY=$minutes;
            $unit="mn";
        }
    }

    return pxs_kpi(array("LABEL"=>"{response_time}","ICON"=>ico_speed,
        "STATE"=>($SQUID_LATEST_LATENCY>0)?"info":"off",
        "VALUE"=>$SQUID_LATEST_LATENCY,"UNIT"=>$unit,
        "LINK"=>array("NAME"=>"{statistics}","JS"=>"Loadjs('fw.proxy.status.latencies.php?js=yes')")));
}

function secondsToMinutes($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = intval($seconds) % 60;
    return [$minutes, $remainingSeconds];
}
/** Tuile: requetes par seconde, et le maximum observe sur les 5 dernieres minutes. */
function  widget_requests():string{
    $Squid5minStorage=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Squid5minStorage"));
    $Squid5min=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Squid5min"));
    // Les deux cles peuvent manquer (proxy qui vient de demarrer): les lire sans
    // garde emet un warning PHP 8 et rend la tuile vide.
    if(!is_array($Squid5min)){$Squid5min=array();}
    if(!is_array($Squid5minStorage)){$Squid5minStorage=array();}
    if(!isset($Squid5min["client_http.requests"])){$Squid5min["client_http.requests"]=0;}
    if(!isset($Squid5minStorage["client_http.requests"])){$Squid5minStorage["client_http.requests"]=0;}

    $now = round($Squid5min["client_http.requests"],2);
    $max = round($Squid5minStorage["client_http.requests"],2);
    $peak= round($max-$now,2);

    $k=array("LABEL"=>"{requests_per_seconds}","ICON"=>"fas fa-tachometer-alt-average",
        "STATE"=>($now>0)?"info":"off","VALUE"=>$now,"UNIT"=>"req/s");
    if($peak>0){ $k["FOOT"]="{maximum} $peak req/s"; }
    return pxs_kpi($k);
}

/** Tuile: part du cache MEMOIRE utilisee, et sa taille. */
function widget_memory():string{
    $SquidMicrosoftConnectedCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMicrosoftConnectedCache"));
    $SquidDisableMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableMemoryCache"));
    if($SquidMicrosoftConnectedCache==1){$SquidDisableMemoryCache=1;}
    if($SquidDisableMemoryCache==1){
        return pxs_kpi(array("LABEL"=>"{memory_cache}","ICON"=>"fad fa-memory",
            "STATE"=>"off","VALUE"=>"{inactive2}"));
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));
    if(!is_object($json) || empty($json->Status)){
        return pxs_kpi(array("LABEL"=>"{memory_cache}","ICON"=>"fad fa-memory",
            "STATE"=>"err","VALUE"=>"{error}"));
    }
    if(!property_exists($json->Info,"storage_memsize")) {
        return pxs_kpi(array("LABEL"=>"{memory_cache}","ICON"=>"fad fa-memory",
            "STATE"=>"off","VALUE"=>"{unknown}"));
    }

    $MemKB=FormatBytes($json->Info->storage_memsize);
    $STORAGE_MEM_PRC_USED=floatval($json->Info->storage_mem_capacity);
    return pxs_kpi(array("LABEL"=>"{memory_cache}","ICON"=>"fad fa-memory","STATE"=>"info",
        "VALUE"=>$STORAGE_MEM_PRC_USED,"UNIT"=>"% / $MemKB","METER"=>$STORAGE_MEM_PRC_USED));
}

function page_requests(){
	
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="squid_requests-day.png";
	$f[]="squid_requests-week.png";
	$f[]="squid_requests-month.png";
	$f[]="squid_requests-year.png";

	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}

    $TINY_ARRAY["TITLE"]="{statistics}: {requests}";
    $TINY_ARRAY["ICO"]="fa-regular fa-chart-line";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	echo "<script>$jstiny</script>";
	if(!$OUTPUT){
		$tpl=new template_admin();
		echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}


function app_status(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/mgrinfo"));
    $MgrInfo=$data->Info;
    $TOTAL_REQUESTS=FormatNumber($MgrInfo->http_requests);
	$AVERAGE_REQUESTS=FormatNumber(round($MgrInfo->average_http_requests/60))."/s";
    $disable_icon                   = "far fa-times-circle";
    $kInfos         = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    $KSRNEmergency          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEmergency"));
    $MacToUidPHP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidPHP"));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}
    if($MacToUidPHP==1){$kInfos["enable"]=0;}

    if($kInfos["enable"]==1) {
        $KSRN = $tpl->widget_h("green", "fa fa-thumbs-up", "{enabled}", "{KSRN}");
        if ($KSRNEmergency == 1) {
            $KSRN = $tpl->widget_h("gray", $disable_icon, "<span style='font-size:25px'>{emergency_mode}</span>", "{KSRN}");
        }
    }else{
        $KSRN = $tpl->widget_h("gray", $disable_icon, "<span style='font-size:25px'>{disabled}</span>", "{KSRN}");
    }




    // Le service de cache est desormais un MODULE (widget_caching()), et son
    // installation/retrait y vit avec le reste de sa logique.
	$users=new usersMenus();
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!$users->SQUID_INSTALLED){$PROXY=false;}
	if($SQUIDEnable==0){$PROXY=false;}
$html[]="<div class='row  wrapper wrapper-content border-bottom  white-bg' style='margin-top:0px'>";
$html[]="";


// Mise en page en flex. La colonne de gauche etait figee a 338px par un
// !important repete sur quatre elements, alors qu'une tuile de service en
// mesure 335: la colonne de droite ne pouvait pas se replier sous un ecran
// etroit et la grille de widgets debordait horizontalement....
$html[]="<div style='display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start'>";
$html[]="<div style='flex:0 0 auto'>";
$html[]="<div id='squid-service-status'></div>";
$html[]="<div id='proxy-tools'></div>";
$html[]="<div>$KSRN</div>";
$html[]="</div>";
$html[]="<div style='flex:1 1 640px;min-width:0'>";
$html[]="<div id='squid-cache-buttons' style='margin-bottom:5px;margin-top:-14px'></div>";

// Deux rangees de MESURES, puis la carte des MODULES, puis les courbes: ce que
// l'exploitant lit en premier (des chiffres) n'est plus melange avec ce sur quoi
// il agit (installer, desactiver). Les tuiles se replient toutes seules
// (flex-basis 220px), sans cellule vide en fin de rangee.
// Composition 4 + 3, celle de la maquette: quatre tuiles puis trois plus larges,
// chaque rangee occupant toute la largeur et toutes les tuiles la meme hauteur.
$html[]=pxs_grid(array(
	widget_members(),widget_requests(),widget_latency(),widget_cpus(),
	widget_memory(),widget_filedescriptors(),widget_cache(),
),array(4,3));
$html[]=pxs_modules_card(array(
	widget_goshield(),widget_acls(),widget_theshield(),widget_cicap(),widget_caching(),widget_bbr(),
));
$t=time();
    // Les quatre courbes ne sont pas toujours generees: la carte ne s'affiche
    // qu'avec ce qui existe reellement, et disparait entierement sinon.
    $graphs=array();
    foreach(array("hourly","day","week","month") as $period){
        if(file_exists("img/squid/squidband-$period.flat.png")){
            $graphs[]="img/squid/squidband-$period.flat.png?t=$t";
        }
    }
    $html[]=pxs_graphs_card($graphs);
$html[]="</div>";
$html[]="</div></div>";

$jsSquidServiceStatus=$tpl->RefreshInterval_js("squid-service-status",$page,"squid-service-status=yes");

$html[]="<script>";
$html[]="$jsSquidServiceStatus;";
$html[]="LoadAjaxSilent('squid-cache-buttons','$page?squid-cache-buttons=yes');";
$html[]="LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";

		$html[]="</script>";
$tpl=new template_admin();
// La feuille des composants est concatenee AUTOUR de _ENGINE_parse_body(): sa
// regex #{(.+?)}# prendrait chaque corps de regle CSS pour un {jeton}.
return pxs_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));


}
function CountOfSNMP():int{
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    if(!is_array($SquidSMPConfig)){$SquidSMPConfig=array();}
    if(count($SquidSMPConfig)==0){$SquidSMPConfig[1]=1;}
    $CPUZ=array();
    foreach ($SquidSMPConfig as $num=>$val){
        if($val==null){continue;}
        $val=intval($val);
        if($val==0){continue;}
        $CPUZ[$num]=true;
    }
    return count($CPUZ);

}

/** Tuile: postes vus par le proxy, rapportes au maximum enregistre. */
function widget_members():string{
    $METRICS_PROXY_CLIENTS_NUMBER = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("METRICS_PROXY_CLIENTS_NUMBER"));
    $METRICS_MAX_PROXY_CLIENTS_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("METRICS_MAX_PROXY_CLIENTS_NUMBER"));

    if($METRICS_PROXY_CLIENTS_NUMBER<=0){
        return pxs_kpi(array("LABEL"=>"{members} / Max","ICON"=>ico_users,
            "STATE"=>"off","VALUE"=>"{none}"));
    }

    $k=array("LABEL"=>"{members} / Max","ICON"=>ico_users,"STATE"=>"on",
        "VALUE"=>$METRICS_PROXY_CLIENTS_NUMBER,
        "LINK"=>array("NAME"=>"{statistics}","JS"=>"Loadjs('fw.proxy.status.ipmembers.php?js=yes')"));
    if($METRICS_MAX_PROXY_CLIENTS_NUMBER>0){
        $prc=($METRICS_PROXY_CLIENTS_NUMBER/$METRICS_MAX_PROXY_CLIENTS_NUMBER)*100;
        $k["UNIT"]="/ $METRICS_MAX_PROXY_CLIENTS_NUMBER";
        $k["METER"]=$prc;
        $k["FOOT"]=round($prc,1)."%";
    }
    return pxs_kpi($k);
}

/** Module: le service de filtrage local (go-shield-server). */
function widget_goshield(){
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $page=CurrentPageName();

    if($Go_Shield_Server_Enable==0){
        return pxs_module(array("ICON"=>ico_goshield,"TITLE"=>"{KSRN_SERVER2}","STATE"=>"off",
            "BADGE"=>"{inactive2}","ACTIONS"=>array(
                array("NAME"=>"{install_feature}","ICON"=>"fas fa-circle-plus","KIND"=>"primary",
                      "JS"=>"Loadjs('$page?goshield-install-js=yes');"))));
    }
    return pxs_module(array("ICON"=>ico_goshield,"TITLE"=>"{KSRN_SERVER2}","STATE"=>"on",
        "BADGE"=>"{active2}","ACTIONS"=>array(
            array("NAME"=>"{disable_feature}","ICON"=>"fas fa-ban",
                  "JS"=>"Loadjs('$page?goshield-uninstall-js=yes');"))));
}

/** Module: les services de reputation (KSRN), qui s'appuient sur go-shield. */
function widget_theshield():string{
    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $page=CurrentPageName();
    $M=array("ICON"=>ico_shield,"TITLE"=>"{reputation_services}","STATE"=>"off","BADGE"=>"{inactive2}");

    // Sans le service de filtrage, la reputation n'a aucun moteur: pas d'action
    // proposee ici, elle se rearme depuis la tuile du service de filtrage.
    if($Go_Shield_Server_Enable==0){ return pxs_module($M); }

    if($KSRNEnable==0){
        $M["ACTIONS"]=array(array("NAME"=>"{install_feature}","ICON"=>"fas fa-circle-plus",
            "KIND"=>"primary","JS"=>"Loadjs('$page?theshield-install-js=yes');"));
        return pxs_module($M);
    }

    $kInfos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    if(!is_array($kInfos)){$kInfos=array();}
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}

    $M["ACTIONS"]=array(
        array("NAME"=>"{disable_feature}","ICON"=>"fas fa-ban",
              "JS"=>"Loadjs('$page?theshield-uninstall-js=yes');"),
        array("NAME"=>"{license}","ICON"=>"fas fa-key","JS"=>"Loadjs('fw.ksrn.license.php?js=yes')"));

    if($kInfos["enable"]==1){
        $M["STATE"]="on";
        $M["BADGE"]="{active2}";
        return pxs_module($M);
    }
    // Active mais licence refusee: etat a part entiere, ni actif ni inactif.
    $M["STATE"]="warn";
    $M["BADGE"]="{license_error}";
    return pxs_module($M);
}

/** Module: les ACLs du proxy. */
function widget_acls():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SQUIDACLsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
    $refresh="LoadAjax('applications-squid-status','$page?applications-squid-status=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";

    if($SQUIDACLsEnabled==0){
        $js=$tpl->framework_buildjs("/proxy/acls/enable",
            "squid.enable.acls.progress","squid.enable.acls.progress.log",
            "progress-squidstatus-restart",$refresh);
        return pxs_module(array("ICON"=>ico_firewall,"TITLE"=>"{WAF_LEFT}","STATE"=>"off",
            "BADGE"=>"{inactive2}","ACTIONS"=>array(
                array("NAME"=>"{install_feature}","ICON"=>"fas fa-circle-plus","KIND"=>"primary","JS"=>$js))));
    }

    $js=$tpl->framework_buildjs("/proxy/acls/disable",
        "squid.enable.acls.progress","squid.enable.acls.progress.log",
        "progress-squidstatus-restart",$refresh);
    return pxs_module(array("ICON"=>ico_firewall,"TITLE"=>"{WAF_LEFT}","STATE"=>"on",
        "BADGE"=>"{active2}","ACTIONS"=>array(
            array("NAME"=>"{disable_feature}","ICON"=>"fas fa-ban","JS"=>$js))));
}

/** Module: l'antivirus de flux par eCAP. */
function widget_ecap_clamav():string{
    $page=CurrentPageName();
    $EnableeCapClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableeCapClamav"));

    if($EnableeCapClamav==1){
        $eCAPClamavEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavEmergency"));
        if($eCAPClamavEmergency==1){
            return pxs_module(array("ICON"=>ico_antivirus,"TITLE"=>"{antivirus_proxy} eCAP",
                "STATE"=>"warn","BADGE"=>"{emergency}","ACTIONS"=>array(
                    array("NAME"=>"{disable_emergency_mode}","ICON"=>"fas fa-triangle-exclamation",
                          "KIND"=>"danger","JS"=>"Loadjs('fw.proxy.emergency.remove.php');"))));
        }
        return pxs_module(array("ICON"=>ico_antivirus,"TITLE"=>"{antivirus_proxy}: eCAP",
            "STATE"=>"on","BADGE"=>"{active2}","ACTIONS"=>array(
                array("NAME"=>"{disable_feature}","ICON"=>"fas fa-ban",
                      "JS"=>"Loadjs('$page?ecap-clamav-uninstall-js=yes');"))));
    }

    return pxs_module(array("ICON"=>ico_antivirus,"TITLE"=>"{antivirus_proxy} eCAP",
        "STATE"=>"off","BADGE"=>"{inactive2}","ACTIONS"=>array(
            array("NAME"=>"{install_feature}","ICON"=>"fas fa-circle-plus","KIND"=>"primary",
                  "JS"=>"Loadjs('$page?ecap-clamav-install-js=yes');"))));
}

/** Module: l'antivirus de flux par ICAP (c-icap), avec son compteur de requetes. */
function widget_cicap():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
    $C_ICAP_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_INSTALLED"));
    $CicapEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));

    if($EnableClamavInCiCap==0){$CicapEnabled=0;}
    if($CicapEnabled==0){
        if($GLOBALS["ECAP_CLAMAV_ENABLE"]){return widget_ecap_clamav();}
    }

    if($C_ICAP_INSTALLED==0){
        return pxs_module(array("ICON"=>ico_antivirus,"TITLE"=>"{antivirus_proxy}",
            "STATE"=>"off","BADGE"=>"{not_installed}"));
    }
    if($CicapEnabled==0){
        return pxs_module(array("ICON"=>ico_antivirus,"TITLE"=>"{antivirus_proxy} ICAP",
            "STATE"=>"off","BADGE"=>"{inactive2}","ACTIONS"=>array(
                array("NAME"=>"{install_feature}","ICON"=>"fas fa-circle-plus","KIND"=>"primary",
                      "JS"=>"Loadjs('$page?c-icap-install-js=yes');"))));
    }

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString(is_object($data)?$data->Info:"");
    if(!isset($ini->_params["APP_C_ICAP"]["running"])){
        $ini->_params["APP_C_ICAP"]["running"]=0;
    }
    if($ini->_params["APP_C_ICAP"]["running"]==0){
        return pxs_module(array("ICON"=>ico_antivirus,"TITLE"=>"{antivirus_proxy} ICAP",
            "STATE"=>"err","BADGE"=>"{stopped}"));
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/info"));
    $REQUESTS=(is_object($json) && property_exists($json,"Info") && is_object($json->Info))
        ?$tpl->FormatNumber($json->Info->requests):"0";
    return pxs_module(array("ICON"=>ico_antivirus,"TITLE"=>"{antivirus_proxy} ICAP",
        "STATE"=>"on","BADGE"=>"{active2}","SUBTITLE"=>"$REQUESTS {request}",
        "ACTIONS"=>array(
            array("NAME"=>"{disable_feature}","ICON"=>"fas fa-ban",
                  "JS"=>"Loadjs('$page?c-icap-uninstall-js=yes');"))));
}

/** Tuile: descripteurs de fichiers consommes par le proxy. */
function widget_filedescriptors(){
    $link=array("NAME"=>"{settings}","JS"=>"Loadjs('fw.proxy.filedesc.php?filedesc-form-js=yes')");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));
    if(!is_object($json) || empty($json->Status)){
        return pxs_kpi(array("LABEL"=>"{file_descriptors}","ICON"=>"fas fa-file-medical-alt",
            "STATE"=>"err","VALUE"=>"{error}","LINK"=>$link));
    }
    if(!property_exists($json->Info,"current_file_descriptors")
        || intval($json->Info->current_file_descriptors)==0) {
        return pxs_kpi(array("LABEL"=>"{file_descriptors}","ICON"=>"fas fa-file-medical-alt",
            "STATE"=>"off","VALUE"=>"{unknown}","LINK"=>$link));
    }

    $prc=($json->Info->current_file_descriptors_in_use / $json->Info->current_file_descriptors)*100;
    $prc=round($prc,2);
    // percentage, quand le demon le calcule, fait foi sur le ratio local.
    if(property_exists($json->Info,"percentage")){ $prc=round(floatval($json->Info->percentage),2); }
    $state="on";
    if($prc>70){$state="warn";}
    if($prc>90){$state="err";}
    return pxs_kpi(array("LABEL"=>"{file_descriptors}","ICON"=>"fas fa-file-medical-alt",
        "STATE"=>$state,"VALUE"=>$prc,"UNIT"=>"%","METER"=>$prc,
        "FOOT"=>intval($json->Info->current_file_descriptors_in_use)."/".intval($json->Info->current_file_descriptors),
        "LINK"=>$link));
}

function MsftncsiReport_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MsftncsiReport=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MsftncsiReport");
    if(strlen($MsftncsiReport)<5){
        $html="<div style='font-size:18px;margin:30px'>{internet_access} {success}</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    $json=json_decode($MsftncsiReport);

    if(!property_exists($json,"success")) {
        $html="<div style='font-size:18px;margin:30px'>{internet_access} {success}</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if($json->success){
        $html="<div style='font-size:18px;margin:30px'>{internet_access} {success}</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }



    $jsRestart=$tpl->framework_buildjs("/proxy/msftncsi","MsftncsiReport.progress","MsftncsiReport.log","MsftncsiReport","Loadjs('$page?MsftncsiReport-js=yes')");
    $btn_config=$tpl->button_autnonome("{attempt_again}", $jsRestart, ico_refresh,"AsSystemAdministrator",412,"btn-warning");


    $time="<strong>".$tpl->time_to_date($json->time,true)."</strong>";
    // Pentest 2026-11-04 : erreurs de connexion du test Internet (donnee externe) affiche sans encodage (XSS stockee).
    $ProxyError=req_html($json->with_proxy->ProxyError);
    $ProxyError=str_replace("ERR_CONNECT_FAIL 101","{ERR_CONNECT_FAIL_101}",$ProxyError);
    $ProxyError=str_replace("ERR_READ_ERROR 0","{ERR_READ_ERROR_0}",$ProxyError);


    $pp[] = "<span class='text-danger'>";
    if($json->with_proxy->HTTPCode>0) {
        $pp[] = "{error_code}: <strong>" . intval($json->with_proxy->HTTPCode)."</strong>";
    }
    if(strlen($json->with_proxy->GoError)>1){
        $pp[] = "{connection_error}: <strong>" . req_html($json->with_proxy->GoError)."</strong>";
    }
    $pp[] = "{APP_SQUID}:&nbsp;<strong>$ProxyError</strong>";
    $pp[] = "</span>";
    $ppText=$tpl->_ENGINE_parse_body(@implode("<br>",$pp));

    if(strlen($json->parents_proxy)>3){
        $json->parents_proxy=str_replace("ERR_CONNECT_FAIL 101","{ERR_CONNECT_FAIL_101}",$json->parents_proxy);
        $json->parents_proxy=str_replace("ERR_READ_ERROR 0","{ERR_READ_ERROR_0}",$json->parents_proxy);
        $json->without_proxy->GoError=$json->parents_proxy;
    }

    if(strlen($json->without_proxy->GoError)==0){
        $MsftncsiReportSingle=$tpl->_ENGINE_parse_body("{MsftncsiReportSingle}");
        $MsftncsiReportSingle=str_replace("%d",$time,$MsftncsiReportSingle);
        $MsftncsiReportSingle=str_replace("%ps", $ppText,$MsftncsiReportSingle);
        $html="<div id='MsftncsiReport'><div style='font-size:16px;margin:30px'>$MsftncsiReportSingle
<div class='center' style='margin-top:30px'>$btn_config</div></div></div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $pp=array();

    $pp[] = "<span class='text-danger'>";
    if($json->without_proxy->HTTPCode>0) {
        $pp[] = "{error_code}: <strong>" . intval($json->without_proxy->HTTPCode) . "</strong>";
    }
    $pp[] = "{connection_error}: <strong>" . req_html($json->without_proxy->GoError)."</strong>";
    $pp[] = "</span>";
    $ppText2=$tpl->_ENGINE_parse_body(@implode("<br>",$pp));
    $MsftncsiReportSingle=$tpl->_ENGINE_parse_body("{MsftncsiReportFull}");
    $MsftncsiReportSingle=str_replace("%d",$time,$MsftncsiReportSingle);
    $MsftncsiReportSingle=str_replace("%ps",$ppText ,$MsftncsiReportSingle);
    $MsftncsiReportSingle=str_replace("%as", $ppText2,$MsftncsiReportSingle);
    $html="<div id='MsftncsiReport'><div style='font-size:16px;margin:30px'>$MsftncsiReportSingle<div class='center' style='margin-top:30px'>$btn_config</div></div></div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

function squid_service_status_report():string{

    $MsftncsiReport=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MsftncsiReport");
    if(strlen($MsftncsiReport)<5){
        return "";
    }
    $json=json_decode($MsftncsiReport);

    if(!property_exists($json,"success")) {
        return "";
    }
    if($json->success ){
        return "";
    }


    if(strlen($json->with_proxy->ProxyError)>2) {
        $tpl=new template_admin();
        $page=CurrentPageName();
        $button["name"]="{report}";
        $button["js"]="Loadjs('$page?MsftncsiReport-js=yes')";
        return $tpl->_ENGINE_parse_body($tpl->widget_h("yellow", "fas fa-wifi-slash",  "{error}","{error_internet_proxy}", $button));
    }
    return "";
}

function squid_service_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();




    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/service/status"));

    echo squid_service_status_report();
    echo "<script>LoadAjaxSilent('proxy-tools','$page?proxy-tools=yes');</script>";

    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){

        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$json->Error","{error}"));
        return true;
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
   $html[]=$tpl->SERVICE_STATUS($ini, "APP_SQUID");

   $EnableSquidInDebugMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidInDebugMode"));
   if($EnableSquidInDebugMode==1){
       $data = $sock->REST_API("/proxy/debug/status");
       $json = json_decode($data);
       if (json_last_error() > JSON_ERROR_NONE) {
           echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
           return true;
       }

       $restartService=$tpl->framework_buildjs(
           "/proxy/debug/restart",
           "squid.debug.progress",
           "squid.debug.log",
           "progress-squidstatus-restart",
           "LoadAjax('applications-squid-status','$page?applications-squid-status=yes')"

       );

       if(!$json->Status){
           echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
           return true;
       }
       $ini=new Bs_IniHandler();
       $ini->loadString($json->Info);
       $html[]=$tpl->SERVICE_STATUS($ini, "APP_SQUID_DEBUG",$restartService);

   }
   echo $tpl->_ENGINE_parse_body($html);
   return true;
}

function CountOfSizeCaches(){
	// Through articarest (squid/proxy_caches_crud.go): the page no longer opens caches.db.
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/caches"),true);
	if(!is_array($json) || empty($json["success"]) || !isset($json["data"]) || !is_array($json["data"])){return 0;}
	$size=0;
	foreach ($json["data"] as $ligne){
		if(trim((string)$ligne["cachename"])==""){continue;}
		if(empty($ligne["enabled"])){continue;}
		if(!empty($ligne["remove"])){continue;}
		if($ligne["cache_type"]=="rock"){continue;}
		$size=$size+intval($ligne["cache_size"])*1024;
	}
	return $size;
}

function squid_cache_buttons(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $title="{your_proxy}";
    $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
    if($realsquidversion<>null){$realsquidversion="&nbsp;v$realsquidversion";}



    $after="LoadAjaxSilent('squid-cache-buttons','$page?squid-cache-buttons=yes');LoadAjaxSilent('squid-service-status','$page?squid-service-status=yes');";

    $js_stop=$tpl->framework_buildjs(
        "/proxy/service/stop",
        "squid.quick.progress",
        "squid.quick.log","squid-cache-buttons",$after,null,"{stop_service} {fast}","AsProxyMonitor"
    );
    $js_start=$tpl->framework_buildjs(
        "/proxy/service/start",
        "squid.quick.progress",
        "squid.quick.log","squid-cache-buttons",$after,null,null,"AsProxyMonitor"
    );
    $js_restart=$tpl->framework_buildjs(
        "/proxy/service/restart",
        "squid.quick.rprogress",
        "squid.quick.rlog","squid-cache-buttons",$after,null,"{restart_service} {APP_SQUID} {fast}","AsProxyMonitor"
    );

   $debugjs="Loadjs('fw.proxy.debug.php')";
    $EnableSquidInDebugMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidInDebugMode"));
    $user=new usersMenus();
    $bts=array();
    if($user->AsProxyMonitor) {
        $bts[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
        $bts[] = "<label class=\"btn btn btn-danger\" OnClick=\"$js_stop\">
                        <i class='fas fa-stop-circle'></i> {stop} {APP_SQUID}
                  </label>";
        $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"$js_start\">
                        <i class='fas fa-play-circle'></i> {start} {APP_SQUID}
                  </label>";

        $bts[] = "<label class=\"btn btn btn-warning\" OnClick=\"$js_restart\">
                        <i class='fas fa-play-circle'></i> {restart} {APP_SQUID}
                  </label>";

        if($EnableSquidInDebugMode==1) {
            $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"$debugjs\">
                        <i class='".ico_bug."'></i> {APP_SQUID_DEBUG}
                  </label>";
        }



        $bts[] = "</div>";
    }
    $TINY_ARRAY["TITLE"]="$title$realsquidversion";
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{proxy_service_about}";
    $TINY_ARRAY["URL"]="proxy-status";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function proxy_tools(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js_emergency="Loadjs('$page?emergency-js');";
    $js_emergency_stop="Loadjs('$page?emergency-off')";
    $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    if($SquidUrgency==0){
        $button_emergency="<button class='btn btn-primary btn-lg' type='button' OnClick=\"$js_emergency\" style='margin-bottom:5px'>{turn_into_emergency}</button>";
    }else{
        $button_emergency="<button class='btn btn-danger btn-lg' type='button' OnClick=\"$js_emergency_stop\" style='margin-bottom:5px'>{disable_emergency_mode}</button>";
    }


    // Deux boutons empiles: un tableau d'une colonne n'apportait rien et figeait
    // la largeur a 338px, alors que la colonne qui les recoit est desormais
    // fluide.
    $html="<div style='display:flex;flex-direction:column'>"
        ."$button_emergency"
        ."<button class='btn btn-primary btn-lg' type='button' OnClick=\"Loadjs('fw.proxy.actions.php')\" style='margin-bottom:5px'>{tools}</button>"
        ."</div>";


    echo $tpl->_ENGINE_parse_body($html);
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

/* ------------------------------------------------------------------------- *
 *  TCP BBR -- tuile + modale. L'etat affiche est TOUJOURS celui du noyau
 *  (GET /system/bbr/status), jamais la cle EnableKernelBBR : une case cochee
 *  sur un noyau sans BBR doit se lire « Non supporte », pas « Actif ».
 *  Spec articarest : docs/superpowers/specs/2026-09-23-tcp-bbr-design.md
 * ------------------------------------------------------------------------- */

/** Statut reel, ou null si le daemon ne connait pas la route (binaire plus ancien). */
function bbr_status_api(){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/bbr/status"));
    if(!is_object($json) || empty($json->success) || !isset($json->data)){return null;}
    return $json->data;
}

/** Pure : modele de tuile pxs_module() a partir du statut. */
function bbr_tile_model($st):array{
    $cc=htmlspecialchars(strval($st->current_cc ?? ""));
    $M=array("ICON"=>"fas fa-gauge-high","TITLE"=>"{tcp_bbr}","STATE"=>"off","BADGE"=>"{inactive2}","SUBTITLE"=>$cc);
    switch(strval($st->state ?? "")){
        case "active":
            $M["STATE"]="on";$M["BADGE"]="{active2}";
            $M["SUBTITLE"]="bbr &middot; ".htmlspecialchars(strval($st->current_qdisc ?? ""));
            break;
        case "unsupported":
            $M["STATE"]="warn";$M["BADGE"]="{tcp_bbr_not_supported}";$M["SUBTITLE"]="{tcp_bbr_kernel_required}";
            break;
        case "error":
            $M["STATE"]="err";$M["BADGE"]="{tcp_bbr_apply_error}";
            break;
    }
    if(!empty($st->cluster_managed)){$M["SUBTITLE"]="HaCluster &middot; ".$M["SUBTITLE"];}
    return $M;
}

function widget_bbr():string{
    $st=bbr_status_api();
    if($st===null){return "";}
    $page=CurrentPageName();
    $M=bbr_tile_model($st);
    $M["ACTIONS"]=array(array("NAME"=>"{parameters}","ICON"=>"fas fa-sliders","KIND"=>"ghost",
        "JS"=>"Loadjs('$page?bbr-js=yes')"));
    return pxs_module($M);
}

function bbr_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{tcp_bbr}","$page?bbr-popup=yes",650);
}

function bbr_popup():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("<div id='bbr-popup'>".bbr_popup_inner()."</div>");
    return true;
}

function bbr_popup_inner():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=array();
    $html[]="<div class='row'><div class='col-md-12'>".$tpl->div_explain("{tcp_bbr_explain}")."</div></div>";
    $st=bbr_status_api();
    if($st===null){return $tpl->_ENGINE_parse_body(@implode("\n",$html));}
    $after=bbr_after_js("LoadAjaxSilent('bbr-popup','$page?bbr-popup-inner=yes');"
        ."LoadAjax('applications-squid-status','$page?applications-squid-status=yes');");
    if(!empty($st->cluster_managed)){
        $html[]="<div class='row'><div class='col-md-12'>".$tpl->div_explain("{tcp_bbr_managed_by_cluster}")."</div></div>";
    }else{
        $html[]=$tpl->BigCircleCheckbox("EnableKernelBBR","{enable_tcp_bbr}","{tcp_bbr_kernel_required}",
            !empty($st->configured)?1:0,$after,"AsSquidAdministrator");
    }
    $html[]=bbr_diag_table($st);
    return $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

/** JS execute apres l'enregistrement du BigCircleCheckbox. Le composant ne
 *  met la reponse qu'en console : bbr_save() n'ecrit rien en cas de succes,
 *  donc toute reponse non vide est un refus a MONTRER (droits, 409, daemon). */
function bbr_after_js(string $reload):string{
    return "if(resp && String(resp).trim().length>0){alert(String(resp).trim());}".$reload;
}

/** Bloc de diagnostic : etat REEL relu apres application. */
function bbr_diag_table($st):string{
    $conf=!empty($st->configured)?"{enabled}":"{disabled}";
    if(!empty($st->cluster_managed)){$conf.=" (HaCluster)";}
    $rows=array(
        array("{tcp_bbr_kernel_support}",!empty($st->supported)?"{tcp_bbr_supported}":"{tcp_bbr_not_supported}"),
        array("{tcp_bbr_configured}",$conf),
        array("{tcp_bbr_algorithm}",htmlspecialchars(strval($st->current_cc ?? ""))),
        array("{tcp_bbr_qdisc}",htmlspecialchars(strval($st->current_qdisc ?? ""))),
    );
    if(strval($st->message ?? "")!==""){$rows[]=array("{status}",htmlspecialchars(strval($st->message)));}
    $h="<table class='table' style='margin-top:15px'><tbody>";
    foreach($rows as $r){$h.="<tr><td style='width:50%'><strong>$r[0]</strong></td><td>$r[1]</td></tr>";}
    return $h."</tbody></table>";
}

/** Enregistrement BigCircleCheckbox : n'ecrit RIEN en cas de succes (sinon la
 *  modale ne se rafraichit pas) ; ecrit l'erreur sinon -- jamais masquee. */
function bbr_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}
    $v=intval($_POST["EnableKernelBBR"])==1?1:0;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/system/bbr/$v"));
    if(!is_object($json) || empty($json->success)){
        $err=(is_object($json) && isset($json->error))?": ".strval($json->error):"";
        echo $tpl->_ENGINE_parse_body("{tcp_bbr_apply_error}").$err;
        return false;
    }
    if(strval($json->outcome ?? "")=="apply_failed"){
        echo $tpl->_ENGINE_parse_body("{tcp_bbr_apply_error}");
        return false;
    }
    return true;
}
