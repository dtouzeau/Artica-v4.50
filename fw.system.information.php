<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10 - Service Pack 10

include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.metrics.kit.inc');
include_once(dirname(__FILE__) . '/ressources/class.lowmem.inc');

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["kpis"])){si_kpis();exit;}
if(isset($_GET["deny-usb-js"])){deny_usb_js();exit;}
if(isset($_GET["hypervisor-js"])){hypervisor_js();exit;}
if(isset($_GET["hypervisor-popup"])){hypervisor_popup();exit;}
if(isset($_POST["hypervisor"])){hypervisor_save();exit;}

if(isset($_GET["KernelNmiWatchdog-js"])){kernel_nmi_watchdog_js();exit;}
if(isset($_GET["kernel-modules-disabled-js"])){kernel_modules_disabled_js();exit;}
if(isset($_GET["loaded-modules-js"])){loaded_kernel_modules_js();exit;}
if(isset($_GET["loaded-modules-popup"])){loaded_kernel_modules_popup();exit;}
if(isset($_POST["kernel_modules_disabled"])){kernel_modules_disabled_save();exit;}
if(isset($_GET["apparmor-js"])){apparmor_js();exit;}
if(isset($_GET["apparmor-progress"])){apparmor_progress();exit;}
if(isset($_POST["apparmor"])){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableAppArmor",$_POST["apparmor"]);exit;}
if(isset($_GET["reset-rrd"])){reset_rrd_js();exit;}
if(isset($_POST["reset-rrd"])){reset_rrd_perform();exit;}
if(isset($_GET["elastic-remove"])){elastic_remove_js();exit;}
if(isset($_POST["elastic-remove"])){elastic_remove_perform();exit;}
if(isset($_GET["php-elts-js"])){php_elts_js();exit;}
if(isset($_POST["php-elts"])){admin_tracks("Fix PHP2Shell: PHP 7.3 from the Freexian Extended LTS repository");die();}
if(isset($_GET["boot-manager-js"])){boot_manager_js();exit;}
if(isset($_GET["boot-manager-popup"])){boot_manager_popup();exit;}
if(isset($_POST["DisableGrubSkin"])){boot_manager_save();exit;}
if(isset($_GET["sshproxy-js"])){sshproxy_js();exit;}
if(isset($_GET["sshproxy-popup"])){sshproxy_popup();exit;}
if(isset($_GET["fsdesc-js"])){fsdesc_js();exit;}
if(isset($_GET["fsdesc-tabs"])){fsdesc_tabs();exit;}
if(isset($_GET["fsdesc-processes"])){fsdesc_processes();exit;}


if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_POST["reset"])){reset_confirm();exit;}
if(isset($_GET["docker-js"])){docker_js();exit;}
if(isset($_POST["docker"])){docker_save();exit;}
if(isset($_GET["intelqa"])){intelqa_js();exit;}
if(isset($_GET["intelqa-popup"])){intelqa_popup();exit;}
if(isset($_GET["conntrack-tracking-js"])){conntrack_tracking_js();exit;}
if(isset($_GET["conntrack-tracking-popup"])){conntrack_tracking_popup();exit;}
if(isset($_POST["DisableConntrackTracking"])){conntrack_tracking_save();exit;}
page();


function boot_manager_js():bool{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    return $tpl->js_dialog1("{boot_manager}","$page?boot-manager-popup=yes");
}

function deny_usb_js():bool{
    $page               = CurrentPageName();
    $SystemDenyUsb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemDenyUsb"));
    if($SystemDenyUsb==0){
        $SystemDenyUsb=1;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SystemDenyUsb",$SystemDenyUsb);
    }else{
        $SystemDenyUsb=0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SystemDenyUsb",$SystemDenyUsb);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/set-permissions");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-system','$page?table=yes');";
    return admin_tracks("Set Deny USB to $SystemDenyUsb");
}

function intelqa_js(){
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }
    return $tpl->js_dialog1("Intel QuickAssist {status}","$page?intelqa-popup=yes");

}
function fsdesc_js():bool{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    return $tpl->js_dialog1("{file_descriptors}","$page?fsdesc-tabs=yes");
}
function fsdesc_tabs():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $array["{processes}"]="$page?fsdesc-processes=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function fsdesc_processes(){
    $tpl                = new template_admin();
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/system/filedescriptors/list"));

    $html[]="
<table class=\"table table-hover\" id='fsdesc'>
	<thead>
    	<tr>
        	<th nowrap >{PID}</th>
        	<th nowrap>{file_descriptors}</small></th>
        	<th nowrap>{process}</small></th>
        </tr>
  	</thead>
	<tbody>
";
    $c=0;
foreach ($json->Processes as $array){
    $c++;
    if($array->FdCount==0){
        continue;
    }
    if($c>250){
        break;
    }
    $fd=$tpl->FormatNumber($array->FdCount);
    $cmd=$array->CmdLine;
    if(strpos($cmd,"/dnsdist")>0){
        $cmd="{APP_DNSDIST}";
    }
    if(strpos($cmd,"/memcached")>0){
        $cmd="{APP_MEMCACHED}";
    }

    if(strpos($cmd,"/rsyslogd")>0){
        $cmd="{APP_SYSLOGD}";
    }
    if(strpos($cmd,"/articarest")>0){
        $cmd="{SQUID_AD_RESTFULL}";
    }
    if(strpos(" $cmd","artica-webconsole")>0){
        $cmd="{web_interface}";
    }
    if(strpos($cmd,"/pgbouncer")>0){
        $cmd="{APP_PBBOUNCER}";
    }
    if(strpos($cmd,"/postgres")>0){
        $cmd="{APP_POSTGRES}";
    }
    if(strpos(" $cmd","postgres:")>0){
        $cmd="{APP_POSTGRES}";
    }
    if(strpos(" $cmd","php-fpm:")>0){
        $cmd="{ARTICA_PHPFPM}";
    }
    if(strpos(" $cmd","/htopweb.pid")>0){
        $cmd="Web For HTOP";
    }
    if(strpos(" $cmd","artica-smtpd-config")>0){
        $cmd="Artica Notification service";
    }
    if(strpos(" $cmd","bin/monit")>0){
        $cmd="{APP_MONIT}";
    }
    if(strpos(" $cmd","bin/cron")>0){
        $cmd="Cron Service";
    }
    if(strpos(" $cmd","bin/CRON")>0){
        $cmd="Cron Service";
    }
    if(strpos(" $cmd","in/squid")>0){
        $cmd="{APP_SQUID}";
    }


    $html[]="<tr id='md$c'>
                <td style='width:1%'  nowrap >$array->PID</td>
                <td style='width:1%'  nowrap >$fd</td>
				<td style='width:100%'>$cmd</td>
				</tr>";

}
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function intelqa_popup():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/intelqat/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
        return true;
    }
    $json->Info=str_replace("\n","<br>",$json->Info);
    if (!$json->Status){
        echo $tpl->div_error("$json->Error||$json->Info");
        return true;
    }
    echo $tpl->div_explain("Intel QuickAssist {status}||$json->Info");
    return true;
}


function reset_js():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $js=$tpl->framework_buildjs("system.php?reset-all=yes",
        "system.reset.progress","system.reset.progress.log","progress-system-info-restart","document.location.href='/logoff.php'",null,null,"AsSystemAdministrator");
    $tpl->js_confirm_execute("{reset_artica_ask}","reset","yes",$js);
    return true;

}
function reset_confirm():bool{
    admin_tracks("Confirm reset the full system !!!");
    return true;
}

function docker_js():bool{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    return $tpl->js_confirm_execute("Artica Docker Edition<br>{unlock_unstable_feature_ask}","docker","yes","LoadAjax('table-loader-system','$page?table=yes');");
}
function docker_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDockerManagement",1);
    return admin_tracks("Activate the Artica For Docker Edition unstable feature !");
}


function sshproxy_js(){
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $tpl->js_dialog1("{new_feature_notice}:{APP_SSHPROXY}","$page?sshproxy-popup=yes");
    return true;

}
function sshproxy_popup(){
    $tpl                = new template_admin();
    $btn=$tpl->button_autnonome("Artica Wiki", "s_PopUpFull('http://www.articabox.com/?pk_campaign=FromArticaInstall','1024','1024')", "fa-link",null,350);

    $html[]="<table style='width:100%'>";
    $html[]="<td style='vertical-align: top'><i class='fa-8x ".ico_terminal."'></i></td>";
    $html[]="<td style='vertical-align:top;padding-left:20px'>";
    $html[]="<H1>{APP_SSHPROXY}</H1>";
    $html[]="<p style='font-size:16px;margin:20px'>{APP_SSHPROXY_EXPLAIN}</p>";
    $html[]="<div style='text-align:right;margin-top:70px;'>$btn</div>";
    $html[]="</td>";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHProxySectionSeen",1);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function boot_manager_popup(){
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $GrubSectionSeen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubSectionSeen"));
    $DisableGrubSkin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableGrubSkin"));
    if($GrubSectionSeen==0){
        $DisableGrubSkin=1;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableGrubSkin",1);
    }
    $GrubTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubTimeOut"));
    if($GrubTimeOut==0){$GrubTimeOut=5;}



    $colors["black"]="black";
    $colors["blue"]="blue";
    $colors["brown"]="brown";
    $colors["cyan"]="cyan";
    $colors["dark-gray"]="dark-gray";
    $colors["green"]="green";
    $colors["light-cyan"]="light-cyan";
    $colors["light-blue"]="light-blue";
    $colors["light-green"]="light-green";
    $colors["light-gray"]="light-gray";
    $colors["light-magenta"]="light-magenta";
    $colors["light-red"]="light-red";
    $colors["magenta"]="magenta";
    $colors["red"]="red";
    $colors["white"]="white";
    $colors["yellow"]="yellow";

    $GrubColorFont=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubColorFont"));
    if($GrubColorFont==null){$GrubColorFont="green";}

    $GrubAzerty=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubAzerty"));
    if($GrubColorFont==null){$GrubColorFont="green";}

    $GrubBackColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubBackColor"));
    if($GrubBackColor==null){$GrubBackColor="black";}

    $GrubColorFontOver=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubColorFontOver"));
    if($GrubColorFontOver==null){$GrubColorFontOver="light-green";}

    $GrubMenuTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubMenuTitle"));
    if($GrubMenuTitle==null){$GrubMenuTitle="%hostname %ver";}
    $GrubBackColorOver=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubBackColorOver"));
    if($GrubBackColorOver==null){$GrubBackColorOver="black";}
    $RescueRootNoPassword=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RescueRootNoPassword"));

    $tpl->field_hidden("GrubSectionSeen",1);
    $form[]=$tpl->field_checkbox_disbaleON("DisableGrubSkin","{disabled}",$DisableGrubSkin);
    $form[]=$tpl->field_numeric("GrubTimeOut","{ttl} ({seconds})",$GrubTimeOut);
    $form[]=$tpl->field_checkbox("GrubAzerty","{keyboard} azerty",$GrubAzerty);
    $form[]=$tpl->field_text("GrubMenuTitle","{menu_title}",$GrubMenuTitle);
    $form[]=$tpl->field_array_hash($colors,"GrubColorFont","{font_color}",$GrubColorFont);
    $form[]=$tpl->field_array_hash($colors,"GrubBackColor","{background_color}",$GrubBackColor);
    $form[]=$tpl->field_array_hash($colors,"GrubColorFontOver","{font_color} (over)",$GrubColorFontOver);
    $form[]=$tpl->field_array_hash($colors,"GrubBackColorOver","{background_color} (over)",$GrubBackColorOver);
    $form[]=$tpl->field_checkbox("RescueRootNoPassword","{bootrescuewithoutpassword}",$RescueRootNoPassword,false,"{bootrescuewithoutpassword_explain}");

    $js=$tpl->framework_buildjs("/system/grub/update",
        "grub.progress","grub.log",
        "grub-progress",
        "dialogInstance1.close();LoadAjax('fw-system-info','$page?infos=yes');"
    );
    $html[]="<div id='grub-progress'></div>";
    $html[]=$tpl->div_explain("{boot_manager_explain}");
    $html[]=$tpl->form_outside("",$form,"https://wiki.articatech.com/en/system/system-console/boot","{apply}",$js,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function boot_manager_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Updating boot loader settings");
    return true;
}

function apparmor_js():bool{
    $tpl                = new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $EnableAppArmor		= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAppArmor"));

    $page               = CurrentPageName();
    $js=$tpl->framework_buildjs("/system/grub/update","grub.progress","grub.progress.log","progress-system-info-restart","LoadAjax('table-loader-system','$page?table=yes');");
    if($EnableAppArmor == 1){
        $tpl->js_confirm_execute("{apparmor_protection_disable}","apparmor",0,$js);
    }else{
        $tpl->js_confirm_execute("{apparmor_protection_enable}","apparmor",1,$js);
    }
return true;
}

function reset_rrd_js():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $page=CurrentPageName();
    return $tpl->js_confirm_delete("{reset} RRD {database}","reset-rrd","yes","LoadAjax('table-loader-system','$page?table=yes');");

}

function reset_rrd_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/rrd/reset");
    return admin_tracks("All RRD databases was removed");
}

function elastic_remove_js(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $page=CurrentPageName();
    $tpl->js_confirm_delete("{APP_ELASTICSEARCH}","elastic-remove","yes","LoadAjax('table-loader-system','$page?table=yes');");

}
// PHP2Shell (CVE-2026-109618/109619) : sur Debian 10, PHP 7.3 n'est corrige que
// par le depot Extended LTS de Freexian. Le daemon ajoute le depot, met a niveau
// les paquets php7.3 installes et relance PHP-FPM (aptget/php_elts.go). Sur une
// console passee autrefois en PHP 7.4 (sans correctif), il redescend en 7.3.
function php_elts_js(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("/system/php/elts/upgrade","php.elts.progress",
        "php.elts.log","progress-system-info-restart","LoadAjax('table-loader-system','$page?table=yes');");
    $confirm="{php2shell_fix_confirm}";
    $elts=si_php_elts_status(array("debian_version"=>10));
    if(is_array($elts) && ($elts["action"]??"")=="downgrade"){$confirm="{php2shell_downgrade_confirm}";}
    $tpl->js_confirm_execute($confirm,"php-elts","yes",$js);
}
// Etat PHP2Shell lu dans le daemon, seulement sur Debian 10 (null ailleurs ou
// si le daemon ne connait pas la route).
function si_php_elts_status(array $d){
    if($d["debian_version"]<>10){return null;}
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/php/elts/status"),true);
    if(!is_array($j) || empty($j["Status"]) || !isset($j["Info"]) || !is_array($j["Info"])){return null;}
    return $j["Info"];
}


function elastic_remove_perform(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("elasticsearch.php?remove-all=yes");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_INSTALLED",0);
}
function apparmor_progress(){
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.apparmor.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.apparmor.log";
    $ARRAY["CMD"]="/system/apparmor/bootloader";
    $ARRAY["TITLE"]="AppArmor";
    $ARRAY["AFTER"]="LoadAjax('table-loader-system','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    header("content-type: application/x-javascript");
    echo  "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-system-info-restart')";
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{system_information}: {your_server}";
    $DEBIAN_VERSION_NAME=trim(str_replace("GNU/Linux","",(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION_NAME")));
    $explain="{si_page_explain}";
    if($DEBIAN_VERSION_NAME<>""){
        $explain="<span class='label label-primary'>".htmlspecialchars($DEBIAN_VERSION_NAME)."</span>&nbsp;{si_page_explain}";
    }
    $html=$tpl->page_header($title,"fa-duotone fa-server",$explain,"$page?table=yes","system-info","progress-system-info-restart",false,"table-loader-system");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function loaded_kernel_modules_js(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }
    $tpl->js_dialog2("{loaded_kernel_modules}","fw.settings.php?modules-start=yes&OnlyLoaded=yes",950);
}
function loaded_kernel_modules_popup(){
    // Le contenu vient de fw.settings.php?modules-start=yes&OnlyLoaded=yes
    // (js_dialog2 ci-dessus) ; cette entrée ne collecte plus rien (lot D).
}
function kernel_nmi_watchdog_js():bool{
    $KernelNmiWatchdog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KernelNmiWatchdog"));
    if($KernelNmiWatchdog==0){
        $KernelNmiWatchdog=1;
    }else{
        $KernelNmiWatchdog=0;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KernelNmiWatchdog",$KernelNmiWatchdog);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/grub/update");
    $page=CurrentPageName();
    echo "LoadAjax('table-loader-system','$page?table=yes');";
    return admin_tracks("Set kernel.nmi_watchdog to $KernelNmiWatchdog");
}
function hypervisor_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog4("Hypervisor","$page?hypervisor-popup=yes",500);
}
function hypervisor_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/hypervisor/status"),true);
    if(!isset($json["Status"]) OR !isset($json["hypervisor"])){
        echo $tpl->div_error("{protocol_error}");
        return false;
    }
$Curkey=$json["hypervisor"];

    foreach ($json["keys"] as $v=>$k){
        $KeyText=str_replace("_HOST","",$v);
        $Hash[$v]=$KeyText;
    }

    $form[]=$tpl->field_array_hash($Hash,"hypervisor","Hypervisor",$Curkey);
    $html[]=$tpl->form_outside("",$form,"","{apply}","LoadAjax('table-loader-system','$page?table=yes');",
        "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function hypervisor_save():bool{
    $hypervisor=$_POST["hypervisor"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/hypervisor/pin/$hypervisor");
    return admin_tracks("System: Pin the hypervisor to $hypervisor");
}


// The Valkey key is an opt-OUT (DisableConntrackTracking=1 turns tracking off),
// so the row below inverts it for display only: the checkbox in the dialog is
// labelled by what it actually writes, keeping the field name identical to the
// setting key — mismatching those two is how a control ends up saving the wrong
// setting.
function conntrack_tracking_js():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }
    $page=CurrentPageName();
    return $tpl->js_dialog4("{connection_tracking}","$page?conntrack-tracking-popup=yes",600);
}
function conntrack_tracking_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DisableConntrackTracking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableConntrackTracking"));

    // BigCircleCheckbox renders no named input: it POSTs the field to this page
    // by itself on change, then runs this $after. So there is no form to submit
    // — an {apply} button here would post nothing and only look functional.
    $after="dialogInstance4.close();LoadAjax('table-loader-system','$page?table=yes');";
    $html[]=$tpl->BigCircleCheckbox("DisableConntrackTracking","{disable_connection_tracking}",
        "{disable_connection_tracking_explain}",$DisableConntrackTracking,$after);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function conntrack_tracking_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    // Converge the kernel now; otherwise the change would only land at the next
    // 3-min watchdog pass and the refreshed row would still show the old state.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/conntrackd/tracking/apply");
    return admin_tracks_post("Updating connection tracking settings");
}

function kernel_modules_disabled_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $kernel_modules_disabled=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("kernel.modules_disabled"));

    if($kernel_modules_disabled==1){
        $tpl->js_dialog_confirm_action("<strong>{disable_feature}</strong><br>{kernel_modules_disabled}",
            "kernel_modules_disabled",0,"LoadAjax('table-loader-system','$page?table=yes');");

    }else{
        $tpl->js_dialog_confirm_action("<strong>{enable_feature}</strong><br>{kernel_modules_disabled}<br>This operation should make some services unavailable like the FireWall and nDPI.",
            "kernel_modules_disabled",1,"LoadAjax('table-loader-system','$page?table=yes');");
    }

return true;

}

function kernel_modules_disabled_save(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KernelModulesDisabledSaved",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KernelModulesDisabled",$_POST["kernel_modules_disabled"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    admin_tracks("Disable loading Kernel modules");

}

// ---------------------------------------------------------------------------
// Rendering — design /home/claude/images/stitch_system_info, kit class.metrics.kit.inc.
// table() keeps its contract: every modal of this page (and fw.license.php,
// fw.system.info.php) reloads it with LoadAjax('table-loader-system','?table=yes').
// ---------------------------------------------------------------------------

function table(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $users  = new usersMenus();
    $admin  = $users->AsSystemAdministrator;
    $d      = si_collect();

    $html=array();
    $html[]="<div class='mtkw si-w' id='system-progress-barr'>";
    // under 2500 MB of RAM articarest turns the cosmetic features off: say so first (class.lowmem.inc)
    $lowmem=lowmem_warning_text(lowmem_state($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/lowmem"),$d["mem"]["total"] ?? 0));
    if($lowmem<>""){$html[]=$tpl->div_warning($lowmem);}
    $html[]="<div id='si-kpis'>".si_kpis_html($tpl,$d)."</div>";
    $html[]=si_summary_card($page,$d,$admin);
    $html[]="<div class='si-grid'>";
    $html[]="<div class='si-left'>".si_chassis_card($d)."</div>";
    $html[]="<div class='si-right'>";
    si_section_hardware($tpl,$page,$d,$admin);
    si_section_os($tpl,$page,$d,$admin);
    si_section_options($tpl,$page,$d);
    si_section_detected($tpl,$d);
    $html[]=$tpl->table_form_compile(2);
    $html[]="</div></div></div>";

    $topbuttons=array();
    if($admin){
        $jsrestart=$tpl->framework_buildjs("/system/refresh","system.refreshcpu.progress",
            "system.refreshcpu.progress.txt","progress-system-info-restart",
            "LoadAjax('table-loader-system','$page?table=yes');");
        $topbuttons[]=array($jsrestart,ico_refresh,"{refresh_system_information}");
    }
    $TINY=array();
    $TINY["TITLE"]="{system_information}: {your_server}";
    $TINY["ICO"]="fa-duotone fa-server";
    $TINY["EXPL"]="{si_page_explain}";
    $TINY["URL"]="system-info";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    // Styles and scripts are concatenated outside the parser (CSS/JS braces are not tokens).
    $script="<script>".$tpl->RefreshInterval_js("si-kpis",$page,"kpis=yes",15)
        ."Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
    echo mtk_css().si_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
}

function si_kpis(){
    $tpl=new template_admin();
    echo mtk_css().$tpl->_ENGINE_parse_body(si_kpis_html($tpl,si_collect(true)));
}

// Everything the page shows, gathered once. $light=true skips what the KPI row does not need.
function si_collect($light=false):array{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $d=array();
    $d["hostname"]=trim((string)$S->GET_INFO("myhostname"));
    $d["cores"]=intval($S->GET_INFO("CPU_NUMBER"));
    if($d["cores"]==0){
        $j=json_decode((string)$S->REST_API("/system/cpu-number"));
        $d["cores"]=(is_object($j) && !empty($j->success))?intval($j->cpu_number):0;
    }
    $d["arch"]=php_uname('m');
    $d["kernel"]=php_uname('r');
    $d["debian_name"]=trim(preg_replace('/\s+/',' ',str_replace("GNU/Linux","",(string)$S->GET_INFO("DEBIAN_VERSION_NAME"))));
    $d["debian_version"]=intval($S->GET_INFO("DEBIAN_VERSION"));
    $d["debian_point"]=trim((string)@file_get_contents("/etc/debian_version"));
    $d["need_reboot"]=intval($S->GET_INFO("NEEDRESTART"))==1;
    $d["xkernel"]=si_xkernel_update();
    $d["artica_version"]=trim((string)@file_get_contents("/usr/share/artica-postfix/VERSION"));
    $d["sp"]=$S->SPVersion();

    $stats=json_decode((string)$S->REST_API("/sysmonitor/stats"),true);
    $d["cpu_percent"]=null;
    if(isset($stats["data"]["latest"]["cpu_percent"])){$d["cpu_percent"]=floatval($stats["data"]["latest"]["cpu_percent"]);}
    $d["mem"]=si_meminfo();

    $d["nics"]=array(); $d["gateway"]="";
    $net=json_decode((string)$S->REST_API("/system/network/interfaces"),true);
    if(isset($net["interfaces"]) && is_array($net["interfaces"])){
        foreach($net["interfaces"] as $nic){
            if(!is_array($nic) || ($nic["Name"] ?? "")=="lo"){continue;}
            $d["nics"][]=$nic;
            if($d["gateway"]=="" && strlen((string)($nic["Gateway"] ?? ""))>0){$d["gateway"]=(string)$nic["Gateway"];}
        }
    }
    if($light){return $d;}

    // Forced refresh, as the page always did: its answer IS the data, no second call.
    $dmi=json_decode((string)$S->REST_API_POST("/system/dmidecode/refresh",array()),true);
    $d["dmi"]=(isset($dmi["data"]) && is_array($dmi["data"]))?$dmi["data"]:array();
    $hv=json_decode((string)$S->REST_API("/system/hypervisor/status"),true);
    $d["hv"]=is_array($hv)?$hv:array();
    $d["baremetal"]=!empty($d["hv"]["baremetal"]);
    $d["install_time"]=intval($S->GET_INFO("INSTALL_TIME"));
    $d["public_ip"]=trim((string)@file_get_contents("ressources/logs/web/myIP.conf"));
    $d["locale"]=trim((string)$S->GET_INFO("LOCALE"));
    $d["uuid"]=(string)$S->GET_INFO("SYSTEMID");
    $d["next_sp"]=method_exists($S,"isNextSP")?intval($S->isNextSP()):0;
    $sm=json_decode((string)$S->REST_API("/system/harddrives/smart"),true);
    $d["disks"]=(isset($sm["Disks"]) && is_array($sm["Disks"]))?$sm["Disks"]:array();
    $fd=json_decode((string)$S->REST_API("/system/filedescriptors/list"),true);
    $d["fd"]=(is_array($fd) && !empty($fd["Status"]))?$fd:array();
    return $d;
}

// /proc/meminfo is world-readable and exact; no daemon needed for a total and a free figure.
function si_meminfo():array{
    $m=array("total"=>0,"available"=>0);
    foreach(@file("/proc/meminfo") ?: array() as $line){
        if(preg_match('/^MemTotal:\s+([0-9]+)/',$line,$re)){$m["total"]=floatval($re[1])*1024;}
        if(preg_match('/^MemAvailable:\s+([0-9]+)/',$line,$re)){$m["available"]=floatval($re[1])*1024;}
    }
    return $m;
}

function si_kpis_html($tpl,array $d):string{
    $h=array();
    $h[]="<div class='mtk-kpis'>";
    $h[]=si_kpi_cpu($d);
    $h[]=si_kpi_memory($d);
    $h[]=si_kpi_system($d);
    $h[]=si_kpi_network($d);
    $h[]="</div>";
    return @implode("",$h);
}

function si_load_badge($pct):string{
    if($pct<30){return "<span class='mtk-badge mtk-badge-ok'>{si_load_low}</span>";}
    if($pct<70){return "<span class='mtk-badge mtk-badge-warn'>{si_load_mid}</span>";}
    return "<span class='mtk-badge mtk-badge-crit'>{si_load_high}</span>";
}

function si_kpi_cpu(array $d):string{
    $pct=$d["cpu_percent"];
    $val=($pct===null)?"-":sprintf("%.1f",$pct)."<small style='font-size:18px;font-weight:700'>%</small>";
    $badge=($pct===null)?"":si_load_badge($pct);
    $bar=($pct===null)?0:min(100,max(1,$pct));
    $cls=($pct!==null && $pct>=70)?" mtk-bar-crit":(($pct!==null && $pct>=30)?" mtk-bar-warn":"");
    $value="<div class='mtk-kpi-val' style='justify-content:space-between'><span class='mtk-kpi-num'>$val</span>$badge</div>"
          ."<div class='mtk-kpi-sub'>{$d["cores"]} {cpu_cores} &middot; ".htmlspecialchars($d["arch"])."</div>";
    return si_kpi_frame("{processor}",$value,"fas fa-microchip","<div class='mtk-bar$cls' style='width:100%;margin-top:0'><i style='width:$bar%'></i></div>");
}

function si_kpi_memory(array $d):string{
    $t=$d["mem"]["total"]; $a=$d["mem"]["available"];
    if($t<=0){ return si_kpi_frame("{memory}","<div class='mtk-kpi-val'><span class='mtk-kpi-num'>-</span></div>","fas fa-memory",""); }
    $used=$t-$a; $pct=$used*100/$t;
    $value="<div class='mtk-kpi-val' style='justify-content:space-between'><span class='mtk-kpi-num'>".sprintf("%.1f",$pct)
          ."<small style='font-size:18px;font-weight:700'>%</small></span>"
          ."<span class='mtk-mono'>".mtk_bytes($used)." / ".mtk_bytes($t)."</span></div>"
          ."<div class='mtk-kpi-sub'>{free}: ".mtk_bytes($a)."</div>";
    return si_kpi_frame("{memory}",$value,"fas fa-memory",
        "<div class='mtk-bar mtk-bar-alt' style='width:100%;margin-top:0'><i style='width:".min(100,max(1,$pct))."%'></i></div>");
}

function si_kpi_system(array $d):string{
    $name=$d["debian_name"]; $code="";
    if(preg_match('/^(.*?)\s*\((.+)\)\s*$/',$name,$re)){$name=trim($re[1]);$code=$re[2];}
    if(preg_match('/^[0-9.]+$/',$d["debian_point"])){$name=preg_replace('/\s[0-9.]+$/',"",$name)." ".$d["debian_point"];}
    if($name==""){$name="Linux";}
    $chip=($code=="")?"":"<span class='mtk-badge mtk-badge-ok' style='text-transform:capitalize'>".htmlspecialchars($code)."</span>";
    $value="<div class='mtk-kpi-val' style='justify-content:space-between'><span class='mtk-kpi-num' style='font-size:22px'>".htmlspecialchars($name)."</span>$chip</div>"
          ."<div class='mtk-kpi-sub mono'>Linux ".htmlspecialchars($d["kernel"])."</div>";
    if($d["need_reboot"]){
        $foot="<span class='mtk-warn'><i class='fas fa-circle-exclamation'></i> {need_reboot}</span>";
    }elseif($d["xkernel"]<>""){
        $foot="<span class='mtk-warn'><i class='fas fa-circle-exclamation'></i> {kernel_modules_update}</span>";
    }else{
        $foot="<span class='mtk-mono'>Artica ".htmlspecialchars($d["artica_version"])." &middot; SP ".htmlspecialchars((string)$d["sp"])."</span>";
    }
    return si_kpi_frame("{si_system_kernel}",$value,"fas fa-terminal",$foot);
}

function si_kpi_network(array $d):string{
    $up=array(); $parts=array();
    foreach($d["nics"] as $nic){
        $isUp=strtoupper((string)($nic["Status"] ?? ""))=="UP";
        $name=(string)($nic["Name"] ?? "");
        if($isUp){
            $up[]=$name;
            $ips=is_array($nic["Addresses"] ?? null)?$nic["Addresses"]:array();
            $parts[]=htmlspecialchars($name).": ".htmlspecialchars(count($ips)>0?$ips[0]:"up");
        }
    }
    $n=count($up);
    $state=($n>0)?"<span class='mtk-ok' style='font-size:12px'><span class='mtk-dot' style='margin:0'>{online}</span></span>"
                 :"<span class='mtk-warn' style='font-size:12px'>{down}</span>";
    $value="<div class='mtk-kpi-val' style='justify-content:space-between'><span class='mtk-kpi-num'>$n<small style='font-size:15px;font-weight:600;color:#64748b'> / ".count($d["nics"])."</small></span>$state</div>"
          ."<div class='mtk-kpi-sub mono'>".(count($parts)>0?@implode(" &middot; ",array_slice($parts,0,2)):"-")."</div>";
    $foot="<span>{default_gateway}</span><span class='mtk-mono'>".htmlspecialchars($d["gateway"]==""?"-":$d["gateway"])."</span>";
    return si_kpi_frame("{network_interfaces}",$value,"fas fa-network-wired",$foot);
}

// The kit's card with a smaller icon chip, as the mock-up's system tiles.
function si_kpi_frame($label,$valueHtml,$icon,$footHtml):string{
    return "<div class='mtk-kpi si-kpi'><div class='mtk-kpi-top'><div style='flex:1;min-width:0'>"
        ."<div class='mtk-kpi-lbl'>$label</div>$valueHtml</div>"
        ."<div class='mtk-kpi-ico si-kpi-ico'><i class='$icon'></i></div></div>"
        ."<div class='mtk-kpi-foot'>$footHtml</div></div>";
}

/** Brand logo of a hardware manufacturer, "" when none ships. Add a brand: one line + one file. */
function si_brand_logo($manufacturer):string{
    // Patterns match the SMBIOS "System Manufacturer" strings as vendors write them.
    // HPE is deliberately absent: no free HPE logo exists, and HP's would be wrong on it.
    $brands=array(
        "#^dell\\b#i"                                   =>"dell",
        "#^(hp|hewlett[- ]?packard)\\b(?!.*enterprise)#i"=>"hp",
        "#^lenovo\\b#i"                                 =>"lenovo",
        "#^super ?micro#i"                               =>"supermicro",
        "#^fujitsu#i"                                    =>"fujitsu",
        "#^cisco#i"                                      =>"cisco",
        "#^asus#i"                                       =>"asus",
        "#^huawei#i"                                     =>"huawei",
        "#^acer\\b#i"                                   =>"acer",
        "#^(micro-star|msi\\b)#i"                       =>"msi",
        "#^apple\\b#i"                                  =>"apple",
        "#^intel\\b#i"                                  =>"intel",
        "#^toshiba#i"                                    =>"toshiba",
        "#^synology#i"                                   =>"synology",
        "#^qnap#i"                                       =>"qnap",
        "#raspberry ?pi#i"                               =>"raspberrypi",
        "#^xiaomi#i"                                     =>"xiaomi",
        "#^samsung#i"                                    =>"samsung",
    );
    foreach($brands as $re=>$file){
        $file="img/vendors/brands/$file.svg";
        if(preg_match($re,trim((string)$manufacturer)) && is_file($file)){return $file;}
    }
    return "";
}

// Picture of the chassis card: the exact model's photo first, else the brand logo,
// else a generic server; a hypervisor always wins on a VM.
function si_chassis_image(array $d):string{
    $img="img/server-256.png";
    $dmi=$d["dmi"];
    if($d["baremetal"]){
        $logo=si_brand_logo($dmi["manufacturer"] ?? "");
        if($logo<>""){$img=$logo;}
    }
    $md5=md5(($dmi["manufacturer"] ?? "").($dmi["chassis"] ?? "").($dmi["product"] ?? ""));
    foreach(array("jpg","jpeg","png") as $ext){ if(is_file("img/vendors/$md5.$ext")){$img="img/vendors/$md5.$ext";} }
    if(!$d["baremetal"]){
        $logo=si_hypervisor_logo($d);
        if($logo<>""){return $logo;}
    }
    $S=$GLOBALS["CLASS_SOCKETS"];
    $map=array("HYPERV_HOST"=>"img/server-hyperv-256.png","NUTANIX_HOST"=>"img/server-nutanix-256.png",
        "XENSERVER_HOST"=>"img/server-citrix-256.png","VMWARE_HOST"=>"img/server-vmware-256.png","QEMU_HOST"=>"img/qemu-256.png");
    foreach($map as $key=>$file){ if(intval($S->GET_INFO($key))==1){$img=$file;} }
    return $img;
}

function si_hypervisor_name(array $d):string{
    $hv=(string)($d["hv"]["pinned"] ?? "");
    if($hv==""){$hv=(string)($d["hv"]["hypervisor"] ?? "");}
    return ucfirst(strtolower(str_replace("_HOST","",$hv)));
}

/** Logo of the detected (or pinned) hypervisor, "" on bare metal or when none ships. */
function si_hypervisor_logo(array $d):string{
    $hv=(string)($d["hv"]["pinned"] ?? "");
    if($hv==""){$hv=(string)($d["hv"]["hypervisor"] ?? "");}
    // Keys are those of articarest Optimize/VMsDetector.go. KVM has no logo of its own:
    // its guests run on QEMU, whose logo stands for it. XenServer is Citrix's.
    $logos=array(
        "VMWARE_HOST"=>"vmware","XENSERVER_HOST"=>"citrix","NUTANIX_HOST"=>"nutanix",
        "HYPERV_HOST"=>"hyperv","AWS_HOST"=>"amazonwebservices","GCP_HOST"=>"googlecloud",
        "VIRTUALBOX_HOST"=>"virtualbox","KVM_HOST"=>"qemu","QEMU_HOST"=>"qemu",
    );
    if(!isset($logos[$hv])){return "";}
    $file="img/vendors/brands/{$logos[$hv]}.svg";
    return is_file($file)?$file:"";
}

// Left column: the machine itself. On bare metal it names the brand, model and chassis
// type read from the SMBIOS tables; on a VM, the hypervisor.
function si_chassis_card(array $d):string{
    $tpl=new template_admin();
    $dmi=$d["dmi"];
    $rows=array();
    if($d["baremetal"]){
        $rows[]=array("{manufacturer}",$dmi["manufacturer"] ?? "","");
        $rows[]=array("{model}",$dmi["product"] ?? "","si-acc");
        $rows[]=array("{si_chassis_type}",$dmi["chassis_type"] ?? "","");
        $rows[]=array("{serial_number}",$dmi["serial"] ?? "","");
        $rows[]=array("{si_bios_version}",$dmi["bios_version"] ?? "","");
        $kind="{si_physical_server}";
    }else{
        $rows[]=array("{si_hypervisor}",si_hypervisor_name($d),"",si_hypervisor_logo($d));
        $rows[]=array("{model}",$dmi["product"] ?? "","si-acc");
        $kind="{virtual_machine}";
    }
    $h=array();
    $h[]="<div class='si-chassis'>";
    $h[]="<div class='si-ch-head'><span class='mtk-pulse'></span><span class='si-ch-host'>".htmlspecialchars(strtoupper($d["hostname"]))."</span>";
    $h[]="<span class='si-ch-kind'>$kind</span></div>";
    $h[]=si_chassis_visual($d);
    $h[]="<div class='si-ch-rows'>";
    foreach($rows as $r){
        if(trim((string)$r[1])==""){continue;}
        $ico=(isset($r[3]) && $r[3]<>"")?"<img src='{$r[3]}' alt='' class='si-brand'>":"";
        $h[]="<div class='si-ch-row'><span>{$r[0]}</span><b class='{$r[2]}'>$ico".htmlspecialchars((string)$r[1])."</b></div>";
    }
    $h[]="</div>";
    $proc=(string)($dmi["proc_type"] ?? "");
    $h[]="<div class='si-ch-notes'>";
    if($proc<>""){$h[]="<div><i class='fas fa-microchip'></i><b>".htmlspecialchars($proc)."</b></div>";}
    $h[]="<div><i class='far fa-clock'></i>{uptime}: <b>".$tpl->uptime()."</b></div>";
    $h[]="</div></div>";
    return @implode("",$h);
}

// The machine drawn as a chassis: one bay per physical disk (up to four), the brand
// logo and model on the bezel. Without disks to show, the brand logo or photo alone.
function si_chassis_visual(array $d):string{
    $dmi=$d["dmi"];
    $disks=array();
    foreach($d["disks"] as $disk){
        if(is_array($disk) && empty($disk["IsRemovable"])){$disks[]=$disk;}
    }
    if(count($disks)==0){
        return "<div class='si-ch-img'><img src='".si_chassis_image($d)."' alt=''></div>";
    }
    $logo=$d["baremetal"]?si_brand_logo($dmi["manufacturer"] ?? ""):si_hypervisor_logo($d);
    $label=trim(($d["baremetal"]?($dmi["product"] ?? ""):si_hypervisor_name($d)));
    $h=array();
    $h[]="<div class='si-rack'><div class='si-box'><div class='si-bays".(count($disks)==1?" si-bays-1":"")."'>";
    foreach(array_slice($disks,0,4) as $disk){$h[]=si_disk_bay($disk);}
    $h[]="</div>";
    if(count($disks)>4){$h[]="<div class='si-bays-more'>+".(count($disks)-4)."</div>";}
    $h[]="<div class='si-bezel'><span>".($logo<>""?"<img src='$logo' alt=''>":"").htmlspecialchars(strtoupper($label))."</span>";
    $h[]="<i></i><i class='t'></i></div>";
    $h[]="</div></div>";
    return @implode("",$h);
}

// One drive bay. The LEDs follow the drive temperature reported by SMART: there is no
// health verdict in that API, so the bay claims nothing it cannot measure.
function si_disk_bay(array $disk):string{
    $temp=intval($disk["Temperature"] ?? 0);
    $led="ok";
    if($temp>50){$led="warn";}
    if($temp>60){$led="crit";}
    $type=strtoupper(trim((string)($disk["DiskType"] ?? "")));
    $family=strtoupper(trim((string)($disk["family"] ?? "")));
    $kind=trim("$family $type");
    $size=floatval($disk["DiskSize"] ?? 0);
    $model=str_replace("_"," ",(string)($disk["model"] ?? ""));
    $title=htmlspecialchars(trim($model." ".(string)($disk["serial_number"] ?? "")));
    $h="<div class='si-bay' title=\"$title\">";
    $h.="<div class='si-bay-h'><span class='si-leds'><i class='$led'></i><i class='$led'></i></span>";
    $h.="<span>".htmlspecialchars(strtoupper((string)($disk["Device"] ?? "")))."</span></div>";
    $h.="<div class='si-grille'><i></i><i></i><i></i></div>";
    $h.="<div class='si-bay-f'><span>".htmlspecialchars($kind==""?"DISK":$kind)."</span>";
    $h.="<span>".($size>0?mtk_bytes($size):"")."</span></div>";
    if($temp>0){$h.="<div class='si-bay-t $led'><i class='fas fa-temperature-half'></i> $temp&deg;C</div>";}
    $h.="</div>";
    return $h;
}

function si_summary_card($page,array $d,$admin):string{
    $dmi=$d["dmi"];
    $sub=htmlspecialchars($d["hostname"]);
    $brand=trim(($dmi["manufacturer"] ?? "")." ".($dmi["product"] ?? ""));
    if($d["baremetal"] && $brand<>""){$sub.=" &middot; ".htmlspecialchars($brand);}
    $reset="";
    if($admin){
        $reset="<a class='si-btn' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?reset-js=yes');\"><i class='fas fa-rotate-left'></i> {reset} {installation}</a>";
    }
    $ver="Artica ".htmlspecialchars($d["artica_version"])." &middot; SP ".htmlspecialchars((string)$d["sp"]);
    if($d["next_sp"]>0){$ver.=" <span class='mtk-badge mtk-badge-warn'>{new_version}: SP {$d["next_sp"]}</span>";}
    return "<div class='si-summary'><div class='si-sum-l'><div class='mtk-kpi-ico'><i class='fas fa-server'></i></div><div>"
        ."<div class='si-sum-t'>{si_summary_title} <span class='mtk-badge mtk-badge-ok'>{online}</span></div>"
        ."<div class='si-sum-s'>$sub</div></div></div>"
        ."<div class='si-sum-r'>$reset<span class='mtk-mono si-ver'>$ver</span></div></div>";
}

function si_section_hardware($tpl,$page,array $d,$admin){
    $tpl->table_form_section("{si_sec_hardware}","{si_sec_hardware_explain}");
    $tpl->table_form_field_js("");
    $dmi=$d["dmi"];
    if($d["baremetal"]){
        if(($dmi["manufacturer"] ?? "")<>""){
            $logo=si_brand_logo($dmi["manufacturer"]);
            $logo=($logo=="")?"":"<img src='$logo' alt='' class='si-brand'>";
            $tpl->table_form_field_text("{manufacturer}",$logo.htmlspecialchars($dmi["manufacturer"]),"fas fa-industry");
        }
        if(($dmi["product"] ?? "")<>""){$tpl->table_form_field_text("{model}",htmlspecialchars($dmi["product"]),"fas fa-tag");}
        if(($dmi["chassis_type"] ?? "")<>""){$tpl->table_form_field_text("{si_chassis_type}",htmlspecialchars($dmi["chassis_type"]),"fas fa-box");}
        if(($dmi["serial"] ?? "")<>""){$tpl->table_form_field_text("{serial_number}",htmlspecialchars($dmi["serial"]),"fas fa-barcode");}
        if(($dmi["bios_version"] ?? "")<>""){$tpl->table_form_field_text("{si_bios_version}",htmlspecialchars($dmi["bios_version"]),"fas fa-microchip");}
    }
    $proc="{$d["cores"]} {cpu_cores} &middot; ".htmlspecialchars($d["arch"]);
    if(($dmi["proc_type"] ?? "")<>""){$proc=htmlspecialchars($dmi["proc_type"])." &middot; ".$proc;}
    if($d["cpu_percent"]!==null){$proc.="&nbsp;&nbsp;".si_load_badge($d["cpu_percent"]);}
    $tpl->table_form_field_text("{processor}",$proc,"fas fa-microchip");

    $t=$d["mem"]["total"]; $a=$d["mem"]["available"];
    if($t>0){
        $tpl->table_form_field_text("{memory}",mtk_bytes($t)." {installed}&nbsp;&nbsp;<span class='mtk-mono'>"
            .mtk_bytes($t-$a)." {used} / ".mtk_bytes($a)." {free}</span>","fas fa-memory");
    }
    $nics=array();
    foreach($d["nics"] as $nic){
        $isUp=strtoupper((string)($nic["Status"] ?? ""))=="UP";
        $ips=is_array($nic["Addresses"] ?? null)?$nic["Addresses"]:array();
        $txt=htmlspecialchars((string)$nic["Name"]).": ".($isUp?htmlspecialchars(count($ips)>0?implode(", ",$ips):"up"):"{down}");
        if($isUp && intval($nic["Speed"] ?? 0)>0){$txt.=" <span class='mtk-mono'>".intval($nic["Speed"])." Mb/s</span>";}
        $nics[]=$txt;
    }
    if(count($nics)>0){$tpl->table_form_field_text("{network_interfaces}",@implode("<br>",$nics),"fas fa-network-wired");}
    if($d["gateway"]<>""){$tpl->table_form_field_text("{default_gateway}",htmlspecialchars($d["gateway"]),"fas fa-route");}
    $tpl->table_form_field_text("{uptime}","<span class='mtk-live'><span class='mtk-pulse'></span>".$tpl->uptime()."</span>","fas fa-clock");
    $tpl->table_form_field_text("{public_ip}",htmlspecialchars($d["public_ip"]==""?"x.x.x.x":$d["public_ip"]),"fas fa-globe");
    if($d["locale"]<>""){
        $tpl->table_form_field_js("Loadjs('fw.system.locale.php')","AsSystemAdministrator");
        $tpl->table_form_field_text("{locale}",htmlspecialchars($d["locale"]),"fas fa-language");
        $tpl->table_form_field_js("");
    }
    if($admin){
        // Mudar o uuid a mao invalida o nome dentro do certificado, e a maquina
        // perde a licenca sem que nada o explique. No modelo novo o uuid vem do
        // certificado: aqui mostra-se, nao se edita.
        $uuid_js=$GLOBALS["CLASS_SOCKETS"]->USE_LICENSE_V2()?"":"Loadjs('fw.license.php?ch-uuid=".urlencode($d["uuid"])."')";
        $tpl->table_form_field_js($uuid_js,"AsSystemAdministrator");
        $tpl->table_form_field_text("Artica {uuid}","<span class='mtk-mono'>".htmlspecialchars($d["uuid"])."</span>","fas fa-fingerprint");
        $tpl->table_form_field_js("");
    }
    if(($dmi["vmware_serial"] ?? "")<>""){
        $tpl->table_form_field_text("vCenter BIOS UUID","<span class='mtk-mono'>".htmlspecialchars($dmi["vmware_serial"])."</span>","fas fa-fingerprint");
    }
}

function si_section_os($tpl,$page,array $d,$admin){
    $S=$GLOBALS["CLASS_SOCKETS"];
    $tpl->table_form_section("{si_sec_os}","{si_sec_os_explain}");
    $tpl->table_form_field_js("");
    if($d["debian_version"]>0 && $d["debian_version"]<10){
        $tpl->table_form_field_warning($tpl->td_href("{error_old_debian_version}","{error_limited_support_text}"));
    }
    $elts=si_php_elts_status($d);
    if(is_array($elts) && !empty($elts["vulnerable"])){
        $inst=htmlspecialchars((string)$elts["installed_version"]);
        $msg="{php2shell_vulnerable} (PHP $inst)";
        if(($elts["action"]??"")=="downgrade"){$msg="{php2shell_php74_vulnerable} (PHP $inst)";}
        if($admin){$msg=$tpl->td_href($msg,null,"Loadjs('$page?php-elts-js=yes')");}
        $tpl->table_form_field_warning($msg);
    }
    $LicenseINGP=intval($S->GET_INFO("LicenseINGP"));
    if($LicenseINGP>time()){
        $tpl->table_form_field_warning("{artica_license}: {grace_period} ({ExpiresSoon} ".distanceOfTimeInWords(time(),$LicenseINGP).")");
    }

    $host=htmlspecialchars($d["hostname"]);
    if($d["need_reboot"]){$host.="&nbsp;&nbsp;<span class='mtk-badge mtk-badge-warn'>{need_reboot}</span>";}
    $tpl->table_form_field_js("Loadjs('fw.system.info.php?chhostname-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{hostname}",$host,"fas fa-server");
    $tpl->table_form_field_js("");

    $ver=htmlspecialchars($d["artica_version"])." Service Pack ".htmlspecialchars((string)$d["sp"]);
    if($d["next_sp"]>0){$ver.="&nbsp;&nbsp;<span class='mtk-badge mtk-badge-warn'>{new_version}: Service Pack {$d["next_sp"]}</span>";}
    $tpl->table_form_field_text("{version}",$ver,"fas fa-code-branch");

    $compiled=php_uname('v'); $CVES=array();
    if(preg_match("#\(.*?([0-9]+)-([0-9]+)-([0-9]+).*?\)#",$compiled,$re)){
        $stime=strtotime("{$re[1]}-{$re[2]}-{$re[3]} 00:00:00");
        $compiled=str_replace("{$re[1]}-{$re[2]}-{$re[3]}",$tpl->time_to_date($stime)." - ".distanceOfTimeInWords($stime,time()),$compiled);
        if($stime<1611961200){
            foreach(array("CVE-2021-26708","CVE-2021-20239","CVE-2021-20235","CVE-2021-20265","CVE-2021-20268") as $cve){
                $CVES[]="<span class='mtk-badge mtk-badge-crit'>$cve</span>";
            }
        }
    }
    $kern="Linux ".htmlspecialchars($d["kernel"])." &middot; ".htmlspecialchars($d["arch"])
         ."<br><span class='mtk-mono'>".htmlspecialchars($compiled)."</span>";
    if($d["xkernel"]<>""){$kern.="<br>".$d["xkernel"];}
    $tpl->table_form_field_text("{kernel}",$kern,"fas fa-terminal");

    if(intval($S->GET_INFO("CVE-2022-29155"))==1){$CVES[]="<span class='mtk-badge mtk-badge-crit'>CVE-2022-29155</span>";}
    if(intval($S->GET_INFO("CVE-2021-44142"))==1){
        $CVES[]=$tpl->td_href("<span class='mtk-badge mtk-badge-crit'>CVE-2021-44142</span>",null,"Loadjs('fw.system.upgrade-samba.php')");
    }
    $squid=si_squid_update();
    if($squid<>""){$CVES[]=$squid;}
    $tpl->table_form_field_text("{important_updates}",count($CVES)>0?@implode(" ",$CVES):"<span class='mtk-badge mtk-badge-mute'>{nothing}</span>","fas fa-shield-virus");

    if(isset($d["hv"]["Status"])){
        $hv=si_hypervisor_name($d);
        if($hv==""){$hv="{si_physical_server}";}
        $logo=si_hypervisor_logo($d);
        if($logo<>""){$hv="<img src='$logo' alt='' class='si-brand'>$hv";}
        if(strlen((string)($d["hv"]["pinned"] ?? ""))>0){$hv.="&nbsp;<span class='mtk-badge mtk-badge-warn'>{locked}</span>";}
        $tpl->table_form_field_js("Loadjs('$page?hypervisor-js=yes')","AsSystemAdministrator");
        $tpl->table_form_field_text("{si_hypervisor}",$hv,"fas fa-cloud");
    }
    // Inverted for display: the keys are opt-outs, the rows state the feature.
    $tpl->table_form_field_js("Loadjs('$page?conntrack-tracking-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{connection_tracking}",intval($S->GET_INFO("DisableConntrackTracking"))==1?0:1,"fas fa-arrows-left-right");
    $tpl->table_form_field_js("Loadjs('$page?boot-manager-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{boot_manager}",intval($S->GET_INFO("DisableGrubSkin"))==1?0:1,"fas fa-power-off");
    $tpl->table_form_field_js("");

    if($d["install_time"]>0){
        $tpl->table_form_field_text("{install_date}",$tpl->time_to_date($d["install_time"])
            ." <span class='mtk-mono'>".distanceOfTimeInWords($d["install_time"],time())."</span>","fas fa-calendar");
    }
    if(count($d["fd"])>0){
        $cur=floatval($d["fd"]["Total_allocated"] ?? 0); $max=floatval($d["fd"]["Max"] ?? 0);
        $fs=$tpl->FormatNumber($cur)." / ".$tpl->FormatNumber($max);
        if($max>0){
            $pct=$cur*100/$max;
            $cls=$pct>=80?"mtk-badge-crit":($pct>=50?"mtk-badge-warn":"mtk-badge-ok");
            $fs.="&nbsp;&nbsp;<span class='mtk-badge $cls'>".($pct<0.1?"&lt; 0.1":sprintf("%.1f",$pct))." %</span>";
        }
        $tpl->table_form_field_js("Loadjs('$page?fsdesc-js=yes')");
        $tpl->table_form_field_text("{file_descriptors}",$fs,"fas fa-file-lines");
        $tpl->table_form_field_js("");
    }
    $php=PHP_VERSION;
    if($admin && is_array($elts) && !empty($elts["vulnerable"])){
        $php.="&nbsp;&nbsp;".$tpl->td_href("<span class='mtk-badge mtk-badge-crit'>{php2shell_fix}</span>",null,"Loadjs('$page?php-elts-js=yes')");
    }
    $tpl->table_form_field_text("{php version}",$php,"fab fa-php");
}

function si_section_options($tpl,$page,array $d){
    $S=$GLOBALS["CLASS_SOCKETS"];
    $tpl->table_form_section("{si_sec_options}","{si_sec_options_explain}");
    $tpl->table_form_field_js("");
    $tpl->table_form_field_bool("{enable_intel_celeron}",intval($S->GET_INFO("EnableIntelCeleron"))==1?0:1,"fas fa-microchip");
    $tpl->table_form_field_bool("{enable_system_optimization}",intval($S->GET_INFO("EnableSystemOptimize"))==1?0:1,"fas fa-gauge-high");

    $tpl->table_form_field_js("Loadjs('$page?deny-usb-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{deny_usb}",intval($S->GET_INFO("SystemDenyUsb")),"fab fa-usb");

    $sysctl=json_decode((string)$S->REST_API("/system/kernel/get/kernel.nmi_watchdog"));
    $nmiWanted=intval($S->GET_INFO("KernelNmiWatchdog"));
    $nmiLive=(is_object($sysctl) && property_exists($sysctl,"Value"))?intval($sysctl->Value):0;
    $tpl->table_form_field_js("Loadjs('$page?KernelNmiWatchdog-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("NMI Watchdog".($nmiLive<>$nmiWanted?"||{after_rebooting}":""),$nmiWanted,"fas fa-heart-pulse");

    $apparmor=1;
    foreach(@file("/etc/default/grub") ?: array() as $line){
        if(preg_match("#GRUB_CMDLINE_LINUX=.*?apparmor=0#",$line)){$apparmor=0;}
    }
    $tpl->table_form_field_js("Loadjs('$page?apparmor-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{apparmor_protection}",$apparmor,"fas fa-shield-halved");

    $tpl->table_form_field_js("");
    if(intval($S->GET_INFO("IntelQATInstalled"))==1 && intval($S->GET_INFO("IntelQATEnabled"))==1){
        $tpl->table_form_field_js("Loadjs('$page?intelqa=yes')","AsSystemAdministrator");
        $tpl->table_form_field_text("Intel QuickAssist","<span class='mtk-badge mtk-badge-ok'>{active2} v".htmlspecialchars((string)$S->GET_INFO("IntelQATVersion"))."</span>","fas fa-bolt");
        $tpl->table_form_field_js("");
    }else{
        $tpl->table_form_field_bool("Intel QuickAssist",0,"fas fa-bolt");
    }
    if(intval($S->GET_INFO("EnableDockerManagement"))==0){
        $tpl->table_form_field_js("Loadjs('$page?docker-js=yes')","AsSystemAdministrator");
        $tpl->table_form_field_bool("Artica Docker edition",0,"fab fa-docker");
        $tpl->table_form_field_js("");
    }
    $hour=trim((string)$S->GET_INFO("DailyRebootHour"));
    if($hour==""){$hour="06:00:00";}
    $daily=intval($S->GET_INFO("DailyReboot"));
    $tpl->table_form_field_bool("{DAILY_REBOOT_SERVER}".($daily==1?"||{each} $hour":""),$daily,"fas fa-rotate");

    $live=intval($S->KERNEL_GET("kernel.modules_disabled"));
    $wanted=intval($S->GET_INFO("KernelModulesDisabled"));
    $saved=intval($S->GET_INFO("KernelModulesDisabledSaved"));
    $tpl->table_form_field_js("Loadjs('$page?kernel-modules-disabled-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{kernel_modules_disabled}".(($saved==1 && $wanted<>$live)?"||{you_should_reboot_the_server}":""),$live,"fas fa-puzzle-piece");
    // /proc/modules is world-readable: the framework helper that used to count them is gone.
    $loaded=count(@file("/proc/modules") ?: array());
    $tpl->table_form_field_js("Loadjs('$page?loaded-modules-js=yes')");
    $tpl->table_form_field_text("{loaded_kernel_modules}",$loaded,"fas fa-cubes");
    $tpl->table_form_field_js("");
    // Computed by the articarest watchdog from the running kernel (module loaded,
    // built in or installable): wireguard.KernelCompatible().
    $wg=intval($S->GET_INFO("APP_WIREGUARD_INSTALLED"))==1
        ?"<span class='mtk-badge mtk-badge-ok'>{yes}</span>"
        :"<span class='mtk-badge mtk-badge-mute'>{no}</span>";
    $tpl->table_form_field_text("{wireguard_compatibility}||{wireguard_compatibility_explain}",$wg,"fas fa-shield-halved");

    if(intval($S->GET_INFO("ELASTICSEARCH_INSTALLED"))==1){
        $tpl->table_form_field_js("Loadjs('$page?elastic-remove=yes')","AsSystemAdministrator");
        $tpl->table_form_field_text("{APP_ELASTICSEARCH}","<span class='mtk-badge mtk-badge-warn'>{remove2}</span>","fas fa-magnifying-glass");
        $tpl->table_form_field_js("");
    }
    $rrd=json_decode((string)$S->REST_API("/rrd/size"));
    $size=is_object($rrd)?FormatBytes(floatval($rrd->Info ?? 0)):"-";
    $tpl->table_form_field_js("Loadjs('$page?reset-rrd=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("RRD {database}","$size&nbsp;&nbsp;<span class='mtk-badge mtk-badge-warn'>{reset}</span>","fas fa-database");
    $tpl->table_form_field_js("");
}

// What linux-info.sh reported (PCI devices, controllers...), minus what the sections above show.
function si_section_detected($tpl,array $d){
    $txt=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LINUX_INFO_TXT");
    $keep=array();
    foreach(explode("\n",$txt) as $line){
        $line=trim($line);
        if(preg_match("#^(Processor|Vendor name|Current CPU Frequency|Max CPU Frequency|Cache Size).*?:#",$line)){continue;}
        if(preg_match("#Desktop Environment#",$line)){continue;}
        $keep[]=$line;
    }
    if(!preg_match_all("#([A-Za-z]+)\s+\{(.+?)\}#s",@implode("\n",$keep),$blocks)){return;}

    $rows=array();
    foreach($blocks[2] as $block){
        $key=""; $seen=array();
        foreach(explode("\n",$block) as $line){
            $line=trim($line);
            if($line==""){continue;}
            if(preg_match("#^(.+?):(.+)#",$line,$ri)){
                $key=trim($ri[1]); $val=trim($ri[2]);
                if($val=="" || $key=="" || $key=="sh" || preg_match("#No such file or directory#i",$val)){$key="";continue;}
                if(isset($seen[md5($line)])){continue;}
                $seen[md5($line)]=true;
                $rows["{".strtolower($key)."}"]=str_replace(array(" days","of RAM"),array(" {days}",""),$val);
            }elseif($key<>"" && isset($rows["{".strtolower($key)."}"])){
                $rows["{".strtolower($key)."}"].="<br>".htmlspecialchars($line);
            }
        }
    }
    $out=array();
    foreach($rows as $key=>$val){
        $val=trim($val);
        if($val==""){continue;}
        if(preg_match("#(kernel|uptime|User name|vendor name|current cpu frequency|date\/time|load average|hostname)#i",$key)){continue;}
        if(preg_match("#(XDG_CURRENT_DESKTOP|can not find|not running|Load Average|GPL v)#",$val)){continue;}
        // CPU flags and per-core frequency dumps are noise in an identity card.
        if(preg_match("#flags#i",$key) || substr_count($val,"<br>")>8){continue;}
        $out[$key]=$val;
    }
    if(count($out)==0){return;}
    $tpl->table_form_section("{si_sec_detected}","{si_sec_detected_explain}");
    $tpl->table_form_field_js("");
    foreach($out as $key=>$val){
        $tpl->table_form_field_text($key,$val,si_detected_icon($key,$val));
    }
}

function si_detected_icon($key,$val):string{
    if(preg_match("#(Wireless)#i",$val)){return "fas fa-wifi";}
    if(preg_match("#(Ethernet|NIC|vmxnet|network)#i",$key.$val)){return "fas fa-ethernet";}
    if(preg_match("#(cpu|Cache Size|Flags|Processor)#i",$key)){return "fas fa-microchip";}
    if(preg_match("#(Memory|RAM)#i",$key)){return "fas fa-memory";}
    if(preg_match("#(disk|storage|sata|nvme|scsi|raid)#i",$key.$val)){return "fas fa-hard-drive";}
    if(preg_match("#(usb)#i",$key.$val)){return "fab fa-usb";}
    if(preg_match("#(VGA|graphic|display)#i",$key.$val)){return "fas fa-display";}
    return "fas fa-circle-info";
}

/** "" when nothing to do, else the link to the Squid update. */
function si_squid_update():string{
    $S=$GLOBALS["CLASS_SOCKETS"];
    if(intval($S->GET_INFO("SQUIDEnable"))==0){return "";}
    $tpl=new template_admin();
    $UPDATES_ARRAY=unserialize(base64_decode((string)$S->GET_INFO("v4softsRepo")));
    if(!is_array($UPDATES_ARRAY)){return "";}
    $AVAILABLE_SQUID=$tpl->SQUID_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if($AVAILABLE_SQUID==0){return "";}
    $NewVer=$UPDATES_ARRAY["APP_SQUID"][$AVAILABLE_SQUID]["VERSION"];
    $cls=intval($S->GET_INFO("HideNewSquidVer"))==1?"mtk-badge-mute":"mtk-badge-warn";
    return $tpl->td_href("<span class='mtk-badge $cls'>{SQUID_NEWVERSION} $NewVer</span>","{SQUID_NEWVERSION_TEXT}",
        "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID');");
}

/** "" when the nDPI kernel modules match the running kernel, else the update link. */
function si_xkernel_update():string{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $UPDATES_ARRAY=unserialize(base64_decode((string)$S->GET_INFO("v4softsRepo")));
    if(!is_array($UPDATES_ARRAY) || !isset($UPDATES_ARRAY["APP_XTABLES"])){return "";}
    $tpl=new template_admin();
    $kernver=php_uname("r");
    $kernbin=$tpl->kernel_binary_ver();
    if(!isset($UPDATES_ARRAY["APP_XTABLES"][$kernbin])){return "";}
    if(is_file("/usr/lib/modules/$kernver/extra/xt_ndpi.ko")){return "";}
    $cls=intval($S->GET_INFO("HideArticaXTNDPIco"))==1?"mtk-badge-mute":"mtk-badge-warn";
    $def=str_replace("%s",$kernver,$tpl->_ENGINE_parse_body("{kernel_modules_update_explain}"));
    return $tpl->td_href("<span class='mtk-badge $cls'><i class='fas fa-circle-exclamation'></i> {kernel_modules_update}</span>",$def,
        "Loadjs('fw.system.upgrade-software.php?product=APP_XTABLES');");
}

// Page-scoped part of the design: two-column layout, dark chassis card, summary card.
function si_css():string{
    if(isset($GLOBALS["SI_CSS_DONE"])){return "";}
    $GLOBALS["SI_CSS_DONE"]=true;
    $ac=mtk_accents();
    $c=array();
    $c[]="<style type='text/css'>";
    $c[]=".si-w .si-kpi{min-height:140px}";
    $c[]=".si-w .si-kpi-ico{width:36px;height:36px;font-size:16px;border-radius:10px}";
    $c[]=".si-grid{display:grid;grid-template-columns:minmax(280px,4fr) minmax(0,8fr);gap:24px;margin-top:20px;align-items:start}";
    $c[]="@media (max-width:1100px){.si-grid{grid-template-columns:minmax(0,1fr)}}";
    $c[]=".si-left{position:sticky;top:10px}";
    $c[]=".si-chassis{background:#fff;border:1px solid rgba(226,232,240,.9);border-radius:16px;padding:20px;color:#1e293b;box-shadow:0 1px 2px rgba(15,23,42,.04)}";
    $c[]=".si-ch-head{display:flex;align-items:center;gap:10px;padding-bottom:14px;border-bottom:1px solid #f1f5f9}";
    $c[]=".si-ch-host{font-family:monospace;font-size:13px;font-weight:700;letter-spacing:.05em;color:#1e293b;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".si-ch-kind{font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:3px 9px;white-space:nowrap}";
    $c[]=".si-ch-img{margin:16px auto;width:150px;height:150px;border-radius:50%;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;overflow:hidden}";
    $c[]=".si-ch-img img{max-width:130px;max-height:130px}";
    $c[]=".si-ch-img img[src$='.svg']{width:104px;height:104px}";
    $c[]=".si-brand{height:20px;width:20px;vertical-align:middle;margin-right:8px}";
    $c[]=".si-rack{margin:16px 0;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;box-shadow:inset 0 2px 4px rgba(15,23,42,.05)}";
    $c[]=".si-box{max-width:250px;margin:0 auto;background:rgba(226,232,240,.9);border:1px solid #cbd5e1;border-radius:8px;padding:10px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".si-bays{display:grid;grid-template-columns:1fr 1fr;gap:10px}";
    $c[]=".si-bays-1{grid-template-columns:1fr}";
    $c[]=".si-bay{background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:9px;display:flex;flex-direction:column;justify-content:space-between;min-height:132px;box-shadow:0 1px 2px rgba(15,23,42,.05)}";
    $c[]=".si-bay-h{display:flex;justify-content:space-between;align-items:center;font-family:monospace;font-size:9px;font-weight:600;color:#94a3b8}";
    $c[]=".si-leds{display:flex;gap:4px}.si-leds i{width:6px;height:6px;border-radius:50%;background:#10b981}";
    $c[]=".si-leds i.warn{background:#f59e0b}.si-leds i.crit{background:#e11d48}";
    $c[]=".si-grille{display:flex;flex-direction:column;gap:4px;margin:12px 0}";
    $c[]=".si-grille i{display:block;height:4px;background:#f1f5f9;border:1px solid rgba(226,232,240,.6);border-radius:9px}";
    $c[]=".si-bay-f{display:flex;justify-content:space-between;gap:6px;padding-top:7px;border-top:1px solid #f1f5f9;font-family:monospace;font-size:9px;font-weight:600;color:{$ac["accent_dark"]}}";
    $c[]=".si-bay-t{font-family:monospace;font-size:9px;color:#64748b;margin-top:4px}";
    $c[]=".si-bay-t.warn{color:#b45309}.si-bay-t.crit{color:#be123c}";
    $c[]=".si-bays-more{text-align:center;font-family:monospace;font-size:10px;color:#64748b;margin-top:6px}";
    $c[]=".si-bezel{display:flex;align-items:center;gap:5px;margin-top:10px;padding:6px 3px 0;border-top:1px solid rgba(203,213,225,.8)}";
    $c[]=".si-bezel span{flex:1;min-width:0;display:flex;align-items:center;gap:6px;font-family:monospace;font-size:8px;font-weight:700;letter-spacing:.12em;color:#64748b;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}";
    $c[]=".si-bezel img{width:14px;height:14px;flex:0 0 auto}";
    $c[]=".si-bezel>i{width:6px;height:6px;border-radius:50%;background:#10b981}.si-bezel>i.t{background:#14b8a6}";
    $c[]=".si-ch-rows{display:flex;flex-direction:column;gap:8px}";
    $c[]=".si-ch-row{display:flex;justify-content:space-between;align-items:center;gap:10px;background:#f8fafc;border:1px solid rgba(226,232,240,.8);border-radius:8px;padding:9px 13px}";
    $c[]=".si-ch-row .si-brand{width:16px;height:16px;margin-right:6px}";
    $c[]=".si-ch-row span{color:#64748b;text-transform:uppercase;letter-spacing:.06em;font-size:10px;font-weight:600;white-space:nowrap;font-family:monospace}";
    $c[]=".si-ch-row b{color:#1e293b;font-family:monospace;font-size:13px;font-weight:700;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".si-ch-row b.si-acc{color:{$ac["accent_dark"]}}";
    $c[]=".si-ch-notes{margin-top:16px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b;display:flex;flex-direction:column;gap:6px}";
    $c[]=".si-ch-notes div{display:flex;align-items:center;gap:8px;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".si-ch-notes i{color:#94a3b8;width:14px;text-align:center;flex:0 0 auto}";
    $c[]=".si-ch-notes b{color:#334155;font-weight:600;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".si-summary{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;box-shadow:0 1px 2px rgba(15,23,42,.04);margin-top:20px}";
    $c[]=".si-sum-l{display:flex;align-items:center;gap:14px;min-width:0}";
    $c[]=".si-sum-l .mtk-kpi-ico{width:42px;height:42px;font-size:18px}";
    $c[]=".si-sum-t{font-size:16px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:10px;flex-wrap:wrap}";
    $c[]=".si-sum-s{font-size:12px;color:#64748b;margin-top:2px}";
    $c[]=".si-sum-r{display:flex;align-items:center;gap:8px;flex-wrap:wrap}";
    $c[]=".si-btn{display:inline-flex;align-items:center;gap:6px;background:{$ac["accent_dark"]};color:#fff!important;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:600;text-decoration:none!important}";
    $c[]=".si-btn:hover{opacity:.9}";
    $c[]=".si-ver{font-size:12px;padding:5px 10px;font-weight:600}";
    $c[]="</style>";
    return @implode("\n",$c);
}
