<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
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
    $bt_js="OnClick=\"Loadjs('fw.system.events.tiny.php?file=exec.squid-reboot.php');\"";

    if($ReloadProxyAfterReboot==0){
        echo $tpl->_ENGINE_parse_body("<span class='label'>{disabled}</span>");
        return true;
    }

    $sock=new sockets();
    $sock->getFrameWork("cron.php?listfiles=yes");
    $array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cron.lists"));
    if(isset($array["squid-reboot"])){
        echo $tpl->_ENGINE_parse_body("<span class='label label-primary' $pointers $bt_js>{active2}</span>");
        return true;
    }
    echo $tpl->_ENGINE_parse_body("<span class='label label-warning'>{inactive}</span>");
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
        "cicap.php?uninstall-progress=yes&with-clamav=yes",
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

    $jsinstall= $tpl->framework_buildjs("ksrn.php?disable-go-shield-server=yes",
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
	$handle=fopen("/etc/squid3/squid-block.acl","r");
	if(!$handle){
		echo $tpl->FATAL_ERROR_SHOW_128("Unable to open /etc/squid3/squid-block.acl\n");
		return;
	}
	
	echo "<table class='table table-striped'><thead><tr><th>URLS</th></tr></thead><tbody>";
	echo "<tbody>";
	while (!feof($handle)) {
		$value=trim(fgets($handle));
		$value=str_replace("\\", "", $value);
		$len=strlen( (string) $value);
		if($len>130){$value=substr($value,0, 130)."<strong>...</strong>";}
		echo "<tr><td>$value</td></tr>";
	}
	echo "</tbody></table>";
	fclose($handle);
}

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
	//enable_watchdog_squid_explain
	
	$array["{status}"]="$page?status=yes";
    $array["{performance}"]="fw.proxy.status.performance.php";
    $array["{members}"]="fw.proxy.status.ipmembers.php";
    $array["{watchdog}"]="$page?watchdog=yes";
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

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?squid-service=yes");
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


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.monit.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.monit.progress.log";
    $ARRAY["CMD"]="squid2.php?monit-config=yes";
    $ARRAY["TITLE"]="{watchdog}";
    $ARRAY["AFTER"]="blur()";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-squidstatus-restart')";

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
function widget_cache():string{
    $tpl=new template_admin();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/mgrinfo"));
    $MgrInfo=$data->Info;
    $storage_swap_size=$MgrInfo->storage_swap_size;
    $storage_swap_capacity=$MgrInfo->storage_swap_capacity;
    $ALL_CACHES=FormatBytes($storage_swap_size);
    $ALL_CACHES_PERC=$storage_swap_capacity;


    if($ALL_CACHES_PERC>0){
        $color="green";
        if($ALL_CACHES_PERC>80){$color="yellow";}
        if($ALL_CACHES_PERC>95){$color="red";}
        return $tpl->widget_h("$color","fas fa-hdd","$ALL_CACHES_PERC%",
            "{used_cache} ($ALL_CACHES)","minheight:150px");
    }

    return $tpl->widget_h("grey","fas fa-hdd","{none}","{used_cache}","minheight:150px");

}

//

function widget_cpus(){
    $tpl=new template_admin();
    $CPU=CountOfSNMP();
    $color="green";
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?CPU-NUMBER=yes"));
    }

    $Squid5min=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Squid5min"));
    if(!isset($Squid5min["cpu_usage"])){$Squid5min["cpu_usage"]=0;}
    $cpu_usage=round($Squid5min["cpu_usage"],2);
    if($cpu_usage>70){
        $color="yellow";
    }
    if($cpu_usage>90){
        $color="red";
    }
    return $tpl->widget_h("$color","fas fa-microchip","{$cpu_usage}%","CPUs $CPU/$CPU_NUMBER",
        "minheight:150px");
}

function  widget_latency():string{
    $tpl=new template_admin();
    $color="lazur";
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
    $button["css"]="minheight:150px";
    $button["name"]="{statistics}";
    $button["ico"]=ico_statistics;
    $button["js"]="Loadjs('fw.rrd.php?img=squidlatency')";

    return $tpl->widget_h("$color",ico_speed,"$SQUID_LATEST_LATENCY{$unit}","{response_time}",
        $button);
}
function secondsToMinutes($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = intval($seconds) % 60;
    return [$minutes, $remainingSeconds];
}

function  widget_requests():string{
    $tpl=new template_admin();
    $Squid5minStorage=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Squid5minStorage"));
    $Squid5min=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Squid5min"));
    if(!isset($Squid5min["client_http.requests"])){$Squid5min["client_http.requests"]=0;}

    $client_http_requests_min       = round($Squid5min["client_http.requests"],2);
    $client_http_requests_max       = round($Squid5minStorage["client_http.requests"],2);
    $client_http_requests_MAX_pie   = $client_http_requests_max-$client_http_requests_min;
    $client_http_requests_MAX_pie=round($client_http_requests_MAX_pie,2);
    if ($client_http_requests_MAX_pie>0){
        return $tpl->widget_h("lazur", "fas fa-tachometer-alt-average", "$client_http_requests_min/$client_http_requests_MAX_pie", "{requests_per_seconds}", "minheight:150px");
    }
    return $tpl->widget_h("lazur", "fas fa-tachometer-alt-average", "$client_http_requests_min", "{requests_per_seconds}", "minheight:150px");
}
function widget_memory():string{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));
    if(!$json->Status){
        return $tpl->widget_h("red","fad fa-memory","{error}","{memory_cache}","minheight:150px");
    }

    if(!property_exists($json->Info,"storage_memsize")) {
        return $tpl->widget_h("grey", "fad fa-memory", "{unknown}", "{memory_cache}", "minheight:150px");
    }

    $MemKB=FormatBytes($json->Info->storage_memsize);
    $STORAGE_MEM_PRC_USED=$json->Info->storage_mem_capacity;

    return $tpl->widget_h("lazur","fad fa-memory","$STORAGE_MEM_PRC_USED%/$MemKB","{memory_cache}","minheight:150px");
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




    $jsrefres="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');LoadAjax('applications-squid-status','$page?applications-squid-status=yes')";
	$SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
	
	if($SquidCachesProxyEnabled==0){

        if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $js_install = $tpl->framework_buildjs("squid2.php?install-cache-service=yes",
                "squid.access.center.progress",
                "squid.access.center.progress.log", "progress-squidstatus-restart", $jsrefres);
            $button["name"] = "{install_feature}";
            $button["js"] = $js_install;
        }else{
            $button["name"] = "{install_feature}";
            $button["js"] = "blur()";
        }

		$cache_capacity=$tpl->widget_h("gray","fas fa-database","{disabled}","{caching}",$button);
	}else{

        $js_uninstall=$tpl->framework_buildjs("squid2.php?disable-cache-service=yes",
            "squid.access.center.progress",
            "squid.access.center.progress.log","progress-squidstatus-restart",$jsrefres);
        $button["name"]="{uninstall}";
        $button["js"]=$js_uninstall;

		$CountOfSizeCaches=CountOfSizeCaches();
		if($CountOfSizeCaches==0){
			$cache_capacity=$tpl->widget_h("red","fas fa-database","{nothing}","{caching}",$button);
		}else{
			$CountOfSizeCaches=FormatBytes($CountOfSizeCaches);
			$cache_capacity=$tpl->widget_h("green","fas fa-database","$CountOfSizeCaches","{caching}",$button);


		}
		
	}


    $widget_filedescriptors = widget_filedescriptors();
	$users=new usersMenus();
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if(!$users->SQUID_INSTALLED){$PROXY=false;}
	if($SQUIDEnable==0){$PROXY=false;}
$html[]="<div class='row  wrapper wrapper-content border-bottom  white-bg' style='margin-top:0px'>";
$html[]="";


$widget_members=widget_members();
$html[]="<table style='width:100%'>";
$html[]="<tr>";
$html[]="<td style='vertical-align:top;width:338px !important;'>";
$html[]="<div style='width:338px'>";
$html[]="<div id='squid-service-status' style='width:338px'></div>";
$html[]="<div id='proxy-tools' style='width:338px'></div>";
$html[]="<div style='width:338px !important'>$KSRN</div>";
$html[]="</div>";
$html[]="</td>";
$html[]="<td style='vertical-align:top;width:90%;padding-left:20px'>";
$html[]="<div id='squid-cache-buttons' style='margin-bottom:5px;margin-top:-14px'></div>";

    $widget_memory=widget_memory();
    $widget_cache=widget_cache();
    $widget_requests=widget_requests();
    $widget_cicap=widget_cicap();
    $widget_acls=widget_acls();
    $widget_goshield=widget_goshield();
    $widget_theshield=widget_theshield();
    $widget_latency=widget_latency();
    $widget_cpus=widget_cpus();
$html[]="
<table style='width:100%'>
<tr>
<td style='width:33%'>$widget_members</td>
<td style='width:33%;padding-left:5px'>$widget_requests</td>
<td style='width:33%;padding-left:5px'>$widget_latency</td>
</tr>
<tr>
<td style='width:33%'>$widget_acls</td>
<td style='width:33%;padding-left:5px'>$widget_goshield</td>
<td style='width:33%;padding-left:5px'>$widget_theshield</td>
</tr>
<tr>
<td colspan='3'>$widget_cicap</td>
</tr>


<tr>
<td style='width:33%' >$widget_memory</td>
<td style='width:33%;padding-left:5px'>$widget_cpus</td>
<td style='width:33%;padding-left:5px'>$widget_filedescriptors</td>
</tr>
<tr>
<td style='width:33%' >$widget_cache</td>
<td style='width:33%;padding-left:5px'>$cache_capacity</td>
<td style='width:33%;padding-left:5px'></td>
</tr>


</table>
";
$t=time();
    if (file_exists("img/squid/squidband-hourly.flat.png")){
        $html[]="<div class='center'><img src='img/squid/squidband-hourly.flat.png?t=$t'></div>";
    }
    if (file_exists("img/squid/squidband-day.flat.png")){
        $html[]="<div class='center'><img src='img/squid/squidband-day.flat.png?t=$t'></div>";
    }
    if (file_exists("img/squid/squidband-week.flat.png")){
        $html[]="<div class='center'><img src='img/squid/squidband-week.flat.png?t=$t'></div>";
    }
    if (file_exists("img/squid/squidband-month.flat.png")){
        $html[]="<div class='center'><img src='img/squid/squidband-month.flat.png?t=$t'></div>";
    }
$html[]="</td>";
$html[]="</tr></table></div>";

$jsSquidServiceStatus=$tpl->RefreshInterval_js("squid-service-status",$page,"squid-service-status=yes");

$html[]="<script>";
$html[]="$jsSquidServiceStatus;";
$html[]="LoadAjaxSilent('squid-cache-buttons','$page?squid-cache-buttons=yes');";
$html[]="LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";

		$html[]="</script>";
$tpl=new template_admin();
return $tpl->_ENGINE_parse_body(@implode("\n",$html));


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

function widget_members():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $METRICS_PROXY_CLIENTS_NUMBER = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("METRICS_PROXY_CLIENTS_NUMBER"));
// /proxy/metrics/sessions
    $METRICS_MAX_PROXY_CLIENTS_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("METRICS_MAX_PROXY_CLIENTS_NUMBER"));

    if($METRICS_PROXY_CLIENTS_NUMBER>0) {
        $button["css"]="minheight:150px";
        $button["name"]="{statistics}";
        $button["ico"]=ico_statistics;
        $button["js"]="Loadjs('fw.rrd.php?img=proxyusers')";

        return $tpl->widget_h("green", ico_users, "$METRICS_PROXY_CLIENTS_NUMBER/$METRICS_MAX_PROXY_CLIENTS_NUMBER", "{members}/Max",$button);
    }



    return $tpl->widget_h("gray",ico_users,"{none}","{members}/{clients}","minheight:150px");
}
function widget_goshield(){
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $page=CurrentPageName();
    $tpl=new template_admin();

    if($Go_Shield_Server_Enable==0){
        $button["name"] = "{install_feature}";
        $button["js"] = "Loadjs('$page?goshield-install-js=yes');";
        return $tpl->widget_h("grey",ico_goshield,"{inactive2}","{KSRN_SERVER2}",$button,"minheight:150px");

    }
    $button["name"] = "{disable_feature}";
    $button["js"] = "Loadjs('$page?goshield-uninstall-js=yes');";
    return $tpl->widget_h("green",ico_goshield,"{active2}","{KSRN_SERVER2}",$button,"minheight:150px");

}
function widget_theshield():string{
    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $page=CurrentPageName();
    $tpl=new template_admin();

    if($Go_Shield_Server_Enable==0){
        return $tpl->widget_h("grey",ico_shield,"{inactive2}","{reputation_services}",null,"minheight:150px");
    }

    if($KSRNEnable==0){
        $button["name"] = "{install_feature}";
        $button["js"] = "Loadjs('$page?theshield-install-js=yes');";
        return $tpl->widget_h("grey",ico_shield,"{inactive2}","{reputation_services}",$button,"minheight:150px");

    }
    $kInfos         = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kInfos"));
    if(!isset($kInfos["enable"])){$kInfos["enable"]=0;}
    if(!isset($kInfos["status"])){$kInfos["status"]=null;}
    if($kInfos["enable"]==1){
        $button["name"] = "{disable_feature}";
        $button["js"] = "Loadjs('$page?theshield-uninstall-js=yes');";
        $button["css"]="minheight:150px";
        $button2["name"] = "{license}";
        $button2["js"] = "Loadjs('fw.ksrn.license.php?js=yes')";
        return $tpl->widget_h("green",ico_shield,"{active2}","{reputation_services}",$button,$button2);

    }

    $button["name"] = "{disable_feature}";
    $button["js"] = "Loadjs('$page?theshield-uninstall-js=yes');";
    $button["css"]="minheight:150px";
    $button2["name"] = "{license}";
    $button2["js"] = "Loadjs('fw.ksrn.license.php?js=yes')";
    return $tpl->widget_h("yellow",ico_shield,"{license_error}","{reputation_services}",$button,$button2);


}

function widget_acls():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SQUIDACLsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
    $refresh="LoadAjax('applications-squid-status','$page?applications-squid-status=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";
    if($SQUIDACLsEnabled==0){

        $js=$tpl->framework_buildjs("/proxy/acls/enable",
            "squid.enable.acls.progress",
            "squid.enable.acls.progress.log",
            "progress-squidstatus-restart",$refresh);

        $button["name"] = "{install_feature}";
        $button["js"] = $js;
        return $tpl->widget_h("grey",ico_firewall,"{inactive2}","{WAF_LEFT}",$button,"minheight:150px");

    }

    $js=$tpl->framework_buildjs("/proxy/acls/disable",
        "squid.enable.acls.progress",
        "squid.enable.acls.progress.log",
        "progress-squidstatus-restart",$refresh);

    $button["name"] = "{disable_feature}";
    $button["js"] = $js;
    return $tpl->widget_h("green",ico_firewall,"{active2}","{WAF_LEFT}",$button,"minheight:150px");
}

function widget_ecap_clamav():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableeCapClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableeCapClamav"));

    if($EnableeCapClamav==1){
        $eCAPClamavEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("eCAPClamavEmergency"));
        if($eCAPClamavEmergency==1){
            $button["name"] = "{disable_emergency_mode}";
            $button["js"] = "Loadjs('fw.proxy.emergency.remove.php');";
            return $tpl->widget_h("yellow",ico_antivirus,"{emergency}","{antivirus_proxy} eCAP",$button,"minheight:150px");
        }

    }

    if($EnableeCapClamav==0){
        $button["name"] = "{install_feature}";
        $button["js"] = "Loadjs('$page?ecap-clamav-install-js=yes');";
        return $tpl->widget_h("grey",ico_antivirus,"{inactive2}","{antivirus_proxy} eCAP",$button,"minheight:150px");

    }
    $button["name"] = "{disable_feature}";
    $button["js"] = "Loadjs('$page?ecap-clamav-uninstall-js=yes');";
    return $tpl->widget_h("green",ico_antivirus,"{active2}","{antivirus_proxy}: eCAP",$button,"minheight:150px");
}

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
        return $tpl->widget_h("grey",ico_antivirus,"{not_installed}","{antivirus_proxy}","minheight:150px");

    }
    if($CicapEnabled==0){
        $button["name"] = "{install_feature}";
        $button["js"] = "Loadjs('$page?c-icap-install-js=yes');";
        return $tpl->widget_h("grey",ico_antivirus,"{inactive2}","{antivirus_proxy} ICAP",$button,"minheight:150px");

    }
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($data->Info);


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/info"));
    $REQUESTS=$json->Info->requests;
    $REQUESTS=$tpl->FormatNumber($REQUESTS);
    if(!isset($ini->_params["APP_C_ICAP"]["running"])){
        $ini->_params["APP_C_ICAP"]["running"]=0;
    }
    if($ini->_params["APP_C_ICAP"]["running"]==0){
        return $tpl->widget_h("red",ico_antivirus,"{stopped}","{antivirus_proxy}",null,"minheight:150px");
    }

    $button["name"] = "{disable_feature}";
    $button["js"] = "Loadjs('$page?c-icap-uninstall-js=yes');";

    return $tpl->widget_h("green",ico_antivirus,$REQUESTS,"{antivirus_proxy}: {request}",$button,"minheight:150px");



}

function widget_filedescriptors(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $button["name"]="{settings}";
    $button["js"]="Loadjs('fw.proxy.filedesc.php?filedesc-form-js=yes')";


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));
    if(!$json->Status){
         return $tpl->widget_h("red","fas fa-file-medical-alt","{error}","{file_descriptors}",$button);
    }

    if(!property_exists($json->Info,"current_file_descriptors")) {
        return $tpl->widget_h("grey", "fas fa-file-medical-alt", "{unknown}", "{file_descriptors}", $button);
    }

    $prc=0;
    if($json->Info->current_file_descriptors>0) {
        $prc = ($json->Info->current_file_descriptors_in_use / $json->Info->current_file_descriptors) * 100;
        $prc = round($prc, 2);
    }
    $prc1=$tpl->widget_h("green","fas fa-file-medical-alt","{$prc}%","{file_descriptors}",$button);
    if($json->Info->current_file_descriptors==0){
        $prc1=$tpl->widget_h("grey","fas fa-file-medical-alt","{unknown}","{file_descriptors}",$button);
    }


    if(property_exists($json->Info,"percentage")) {
        if (intval($json->Info->percentage) > 70) {
            $prc1 = $tpl->widget_h("yellow", "fas fa-file-medical-alt", "{$json->Info->percentage}%", "{file_descriptors}", $button);

        }
        if (intval($json->Info->percentage) > 90) {
            $prc1 = $tpl->widget_h("red", "fas fa-file-medical-alt", "{$json->Info->percentage}%", "{file_descriptors}", $button);

        }
    }
    return $prc1;

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
    $ProxyError=$json->with_proxy->ProxyError;
    $ProxyError=str_replace("ERR_CONNECT_FAIL 101","{ERR_CONNECT_FAIL_101}",$ProxyError);
    $ProxyError=str_replace("ERR_READ_ERROR 0","{ERR_READ_ERROR_0}",$ProxyError);


    $pp[] = "<span class='text-danger'>";
    if($json->with_proxy->HTTPCode>0) {
        $pp[] = "{error_code}: <strong>" . $json->with_proxy->HTTPCode."</strong>";
    }
    if(strlen($json->with_proxy->GoError)>1){
        $pp[] = "{connection_error}: <strong>" . $json->with_proxy->GoError."</strong>";
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
        $pp[] = "{error_code}: <strong>" . $json->without_proxy->HTTPCode . "</strong>";
    }
    $pp[] = "{connection_error}: <strong>" . $json->without_proxy->GoError."</strong>";
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
	
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$sql="SELECT *  FROM squid_caches_center ORDER BY zOrder";
	$results=$q->QUERY_SQL($sql);
	$size=0;
	foreach ($results as $index=>$ligne){
        $cache_type=$ligne["cache_type"];
		$cachename=$ligne["cachename"];
		$cache_size=$ligne["cache_size"];
		if($cachename==null){continue;}
		if($ligne["enabled"]==0){continue;}
		if($ligne["remove"]==1){continue;}
        if($cache_type=="rock"){continue;}
		$cache_size=$cache_size*1024;
		$size=$size+$cache_size;
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
        $button_emergency="<button class='btn btn-primary btn-lg' type='button' OnClick=\"$js_emergency\" style='width:338px;margin-bottom:5px'>{turn_into_emergency}</button>";
    }else{
        $button_emergency="<button class='btn btn-danger btn-lg' type='button' OnClick=\"$js_emergency_stop\" style='width:338px;margin-bottom:5px'>{disable_emergency_mode}</button>";
    }


    $html="<table style='width:100%'>
			<tr>
			<td>$button_emergency</td>
			</tr>
			<tr>
			<td><button class='btn btn-primary btn-lg' type='button' OnClick=\"Loadjs('fw.proxy.actions.php')\" style='width:338px;margin-bottom:5px'>{tools}</button></td>
			</tr>					
			</table>";


    echo $tpl->_ENGINE_parse_body($html);
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}