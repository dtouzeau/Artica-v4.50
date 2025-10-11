<?php

include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["deny-usb-js"])){deny_usb_js();exit;}
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
if(isset($_GET["upgrade-php-47-js"])){upgrade_php_47_js();exit;}
if(isset($_POST["uprade-php-47"])){admin_tracks("Perform Upgrade to PHP 4.7");die();}
if(isset($_GET["go-exec-version"])){go_exec_version();exit;}
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
    $html[]=$tpl->form_outside("{boot_manager_explain}",$form,"https://wiki.articatech.com/en/system/system-console/boot","{apply}",$js,"AsSystemAdministrator");
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
function upgrade_php_47_js(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $page=CurrentPageName();

    $js=$tpl->framework_buildjs("system.php?upgrade-php-47=yes","upgrade.php47.progress",
        "upgrade.php47.log","progress-system-info-restart","LoadAjax('table-loader-system','$page?table=yes');");

    $tpl->js_confirm_execute("{perform_upgrade} 7.4x","uprade-php-47","yes",$js);
}


function elastic_remove_perform(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("elasticsearch.php?remove-all=yes");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_INSTALLED",0);
}
function apparmor_progress(){
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.apparmor.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.apparmor.log";
    $ARRAY["CMD"]="apparmor.php?grub=yes";
    $ARRAY["TITLE"]="AppArmor";
    $ARRAY["AFTER"]="LoadAjax('table-loader-system','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    header("content-type: application/x-javascript");
    echo  "Loadjs('fw.progress.php?content=$prgress&mainid=progress-system-info-restart')";
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{system_information}: {your_server}";
    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    $DEBIAN_VERSION_NAME=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION_NAME");
    $DEBIAN_VERSION_NAME=str_replace("GNU/Linux","",$DEBIAN_VERSION_NAME);
    $t=time();
    $bg_image="";
    if($DEBIAN_VERSION==10){
        $bg_image=";min-height:90px;padding-right:20px;background-image:url(img/debian-10-Buster.png?t=$t);background-repeat: no-repeat;background-position: top right;";
    }
    if($DEBIAN_VERSION==12){
        $bg_image=";min-height:90px;padding-right:20px;background-image:url(img/debian-12-Bookworm.png?t=$t);background-repeat: no-repeat;background-position: top right;";
    }

    $jsrestart=$tpl->framework_buildjs("/system/refresh",
    "system.refreshcpu.progress",
    "system.refreshcpu.progress.txt",
    "progress-system-info-restart","LoadAjax('table-loader-system','$page?table=yes');");


    $bt=$tpl->button_autnonome("{refresh_system_information}", $jsrestart, "fa-info");
    $bt="<div style='float:left;margin-right: 12px'>$bt</div>";

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$bt=null;}

    $explain="<H2 style='$bg_image'>$DEBIAN_VERSION_NAME</H2>";
    $html=$tpl->page_header($title,"fa-duotone fa-server",$bt.$explain,"$page?table=yes","system-info","progress-system-info-restart",false,"table-loader-system");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
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
    $tpl                        = new template_admin();
    $IPTABLES_MODULES_INFO_TIME = intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("IPTABLES_MODULES_INFO_TIME"));
    $tt                         = $tpl->time_diff_min($IPTABLES_MODULES_INFO_TIME);

    if($tt>30) {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("firehol.php?modules-infos=yes");
    }
    $IPTABLES_MODULES_INFO=unserialize( $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_MODULES_INFO"));



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

function table(){
    $tpl            = new template_admin();
    $users          = new usersMenus();
    $page           = CurrentPageName();

    $LINUX_INFO_TXT = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("LINUX_INFO_TXT");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?dmicode=yes");
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER==0){
        $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?CPU-NUMBER=yes"));
    }
    $datas          = unserialize(@file_get_contents(PROGRESS_DIR."/dmicode.array"));
    $INSTALL_TIME   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("INSTALL_TIME"));
    $NextSP         = 0;
    $publicip       = @file_get_contents("ressources/logs/web/myIP.conf");
    $MANUFACTURER   = null;
    $tr             = array();
    $PRODUCT        = "";
    $CHASSIS        = "";

    $img="img/server-256.png";
    if(is_array($datas)){
        $MANUFACTURER =$datas["MANUFACTURER"];
        $PRODUCT=$datas["PRODUCT"];
        $CHASSIS=$datas["CHASSIS"];
        $md5Chassis=md5("{$datas["MANUFACTURER"]}{$datas["CHASSIS"]}{$datas["PRODUCT"]}");
        VERBOSE("CHASSIS: $md5Chassis",__LINE__);
        if(is_file("img/vendors/$md5Chassis.jpg")){$img="img/vendors/$md5Chassis.jpg";}
        if(is_file("img/vendors/$md5Chassis.jpeg")){$img="img/vendors/$md5Chassis.jpeg";}
        if(is_file("img/vendors/$md5Chassis.png")){$img="img/vendors/$md5Chassis.png";}
        if($MANUFACTURER<>null){$tr[]=$MANUFACTURER;}
        if($PRODUCT<>null){$tr[]=$PRODUCT;}
        if($CHASSIS<>null){$tr[]=$CHASSIS;}

    }
    if($publicip==null){$publicip="x.x.x.x";}
    $bg_image="";
    $LINUX_INFO_ARRAY=array();

    $tbl=explode("\n",$LINUX_INFO_TXT);
    $newtbl=array();
    foreach ($tbl as $line){
        $line=trim($line);
        if(preg_match("#^Processor.*?:(.*)#",$line,$rz)){
            $PROCESSOR["Processor"]=trim($rz[1]);
            continue;
        }
        if(preg_match("#^Vendor name.*?:(.*)#",$line,$rz)){
            $PROCESSOR["Vendor name"]=trim($rz[1]);
            continue;
        }
        if(preg_match("#^Current CPU Frequency.*?:(.*)#",$line,$rz)){
            $PROCESSOR["Current CPU Frequency"]=trim($rz[1]);
            continue;
        }
        if(preg_match("#^Max CPU Frequency.*?:(.*)#",$line,$rz)){
            $PROCESSOR["Max CPU Frequency"]=trim($rz[1]);
            continue;
        }
        if(preg_match("#^Cache Size.*?:(.*)#",$line,$rz)){
            $PROCESSOR["Cache Size"]=trim($rz[1]);
            continue;
        }
        if(preg_match("#Desktop Environment#",$line,$rz)){continue;}

        $newtbl[]=$line;

    }
    $LINUX_INFO_TXT=@implode("\n",$newtbl);



    if(!preg_match_all("#([A-Za-z]+)\s+\{(.+?)\}#s",$LINUX_INFO_TXT,$LINUX_INFO_ARRAY)){echo "<H1>No data</H1>";return;}
    $c=0;
    $NewVer=null;


    $jsrestart=$tpl->framework_buildjs("/system/refresh",
        "system.refreshcpu.progress",
        "system.refreshcpu.progress.txt",
        "system-progress-barr","LoadAjax('table-loader-system','$page?table=yes');");

    $bt=$tpl->button_autnonome("{refresh_system_information}",$jsrestart,"fa-sync-alt","AsSystemAdministrator",350);

    $leftlogo="<img src='$img' style='margin:5px;background-color:white' class='img-circle circle-border m-b-md' alt=''>";

    if ($GLOBALS["CLASS_SOCKETS"]->GET_INFO("QEMU_HOST") == 1) {
        $leftlogo="<img src='img/qemu-256.png' style='margin:5px;background-color:white' class='img-circle circle-border m-b-md' alt=''>";
    }



    $html[]="<div style='width:100%' id='system-progress-barr'>";
    $html[]="<div style='width:100%'>";
    $html[]="<div style='width:95%;$bg_image'><span style='font-weight: normal;font-size: 47px'></span></div>";
    $html[]="<table style='width:100%' id='fw-system-info-div-detect'>
<tr>
<td class=center style='width:1%;padding:10px;vertical-align:top'>
	<div class='widget-head-color-box navy-bg p-lg text-center'>
		<small class='font-bold no-margins'> ".@implode(", ", $tr) ."</small>
		$leftlogo
	</div>
	<p>&nbsp;</p>
	$bt
</td>
<td style='padding:10px;vertical-align:top'>";
    $html[]="<table class=table>";
    $Myversion          = trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
    $CurrentServicePack = $GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes");
    $hostname           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    $reboot             = null;

    if(method_exists($GLOBALS["CLASS_SOCKETS"],"isNextSP")){
        $NextSP=$GLOBALS["CLASS_SOCKETS"]->isNextSP();
    }


    if($NextSP>0){
        $NewVer="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class='label label-warning'>{new_version}: <strong>Service Pack $NextSP</strong></span>";
    }

    $NEEDRESTART=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NEEDRESTART"));
    if($NEEDRESTART==1){
        $reboot="&nbsp;<span class='label label-warning'>{need_reboot}</span>";
    }

    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION<10){
        $html[]="<tr>;
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><i class='text-danger ".ico_emergency." '></i></strong></td>
                <td style='width:99% !important;'><strong class='text-danger'>".$tpl->td_href("{error_old_debian_version}","{error_limited_support_text}")."</strong></td>
                </tr>";

    }

    $LicenseINGP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseINGP"));
    if($LicenseINGP>0){
        if($LicenseINGP>time()) {
            $LicenseINGPDistance = distanceOfTimeInWords(time(), $LicenseINGP);

            $html[]="<tr>;
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><i class='text-danger ".ico_emergency." '></i></strong></td>
                <td style='width:99% !important;'><strong class='text-danger'>".$tpl->td_href("{artica_license}: {grace_period} ({ExpiresSoon} $LicenseINGPDistance)","{artica_license}")."</strong></td>
                </tr>";


        }
    }



    //$SYSTEMD_REMOVED=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMD_REMOVED"));

    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{hostname}:</strong></td>
                <td style='width:99% !important;'>".$tpl->td_href($hostname,null,"Loadjs('fw.system.info.php?chhostname-js=yes')")."$reboot
                </td>
                </tr>";
$reset="{reset} {installation}";
    $bt_reset="&nbsp;".$tpl->td_href("<span class='label label-primary'>$reset</span>",null,
        "Loadjs('$page?reset-js=yes')");

    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{your_server}:</strong></td>
                <td style='width:99% !important;'>$MANUFACTURER $PRODUCT $CHASSIS $bt_reset</td>
                </tr>";


    $DisableGrubSkin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableGrubSkin"));
    if($DisableGrubSkin==1){
        $bt_boot=$tpl->td_href("<span class='label label-default'>{disabled}</span>",null,
            "Loadjs('$page?boot-manager-js=yes')");
    }else{

        $bt_boot=$tpl->td_href("<span class='label label-primary'>{active2}</span>",null,
            "Loadjs('$page?boot-manager-js=yes')");
    }



    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{boot_manager}:</strong></td>
                <td style='width:99% !important;'>$bt_boot</td>
                </tr>";

    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{version}:</strong></td>
                <td style='width:99% !important;'>$Myversion Service Pack $CurrentServicePack$NewVer</td>
                </tr>";

    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>Go-Exec {version}:</strong></td>
                <td style='width:99% !important;'><div id='go-exec-version'><div></td>
                </tr>";


    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{public_ip}:</strong></td>
                <td style='width:99% !important;'>$publicip</td>
                </tr>";


    $LOCALE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));


    if(!is_null($LOCALE)){
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{locale}:</strong></td>
                <td style='width:99% !important;'>".$tpl->td_href($LOCALE,"","Loadjs('fw.system.locale.php')")."</td>
                </tr>";
    }

    if($INSTALL_TIME>0){
        $install_text=$tpl->time_to_date($INSTALL_TIME);
        $install_was=distanceOfTimeInWords($INSTALL_TIME,time());

        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{install_date}:</strong></td>
                <td style='width:99% !important;'>$install_text ($install_was)</td>
                </tr>";

    }
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{uptime}:</strong></td>
                <td style='width:99% !important;'>".$tpl->uptime()."</td>
                </tr>";
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/system/filedescriptors/list"));
    if($json->Status){
        $fs="{current}: ".$tpl->FormatNumber($json->Total_allocated) ."&nbsp;/&nbsp;".$tpl->FormatNumber($json->Max);
        $fs=$tpl->td_href($fs,null,"Loadjs('$page?fsdesc-js=yes')");
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{file_descriptors}:</strong></td>
                <td style='width:99% !important;'>$fs</td>
                </tr>";

    }




    $uuid_val=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
    $uuid_link=$tpl->td_href($uuid_val,"{change_uuid}","Loadjs('fw.license.php?ch-uuid=$uuid_val')");
    if(!$users->AsSystemAdministrator){$uuid_link=null;}
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>Artica {uuid}:</strong></td>
                <td style='width:99% !important;'>$uuid_link</td>
                </tr>";


    $DMIDECODE_CACHE=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DMIDECODE_CACHE"));
    if(isset($DMIDECODE_CACHE["VMWARE_SERIAL"])){
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>vCenter's BIOS UUID:</strong></td>
                <td style='width:99% !important;'>{$DMIDECODE_CACHE["VMWARE_SERIAL"]}</td>
                </tr>";

    }


    $CVES=array();
    $compiled=php_uname('v');
    if(preg_match("#\(.*?([0-9]+)-([0-9]+)-([0-9]+).*?\)#",$compiled,$re)){
        $Year   = $re[1];
        $Month  = $re[2];
        $Day    = $re[3];
        $stime=strtotime("$Year-$Month-$Day 00:00:00");
        $distance=distanceOfTimeInWords($stime,time());
        $compiled=str_replace("$Year-$Month-$Day",$tpl->time_to_date($stime)." - $distance",$compiled);
        if($stime<1611961200){
            $CVES[]="<span class='label label-danger'>CVE-2021-26708</span>";
            $CVES[]="<span class='label label-danger'>CVE-2021-20239</span>";
            $CVES[]="<span class='label label-danger'>CVE-2021-20235</span>";
            $CVES[]="<span class='label label-danger'>CVE-2021-20265</span>";
            $CVES[]="<span class='label label-danger'>CVE-2021-20268</span>";

        }
    }

    $CVE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CVE-2022-29155"));
    if($CVE==1){
        $CVES[]=$tpl->td_href("<span class='label label-danger'>CVE-2022-29155</span>",null,"Loadjs('fw.system.upgrade-openldap.php')");
    }
    $CVE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CVE-2021-44142"));
    if($CVE==1){
        $CVES[]=$tpl->td_href("<span class='label label-danger'>CVE-2021-44142</span>",null,"Loadjs('fw.system.upgrade-samba.php')");
    }

    $KernelVersion=php_uname('r');
    $html[]="<tr><td colspan=3><p>&nbsp;</p></tr>";

    $token_upgrade=null;
    $phpversion = explode('.',PHP_VERSION);
    if($phpversion[0]==7){
        if($phpversion[1]<4){
            $token_upgrade="&nbsp;&nbsp;<a href=\"#\" OnClick=\"Loadjs('$page?upgrade-php-47-js=yes');\">
                    <span class='label label-primary'>{perform_upgrade} 7.4x</span></a>";
            if(!$users->AsSystemAdministrator){$token_upgrade=null;}
        }
    }



    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{php version}:</strong></td>
                <td style='width:99% !important;'>{$phpversion[0]}.{$phpversion[1]}.{$phpversion[2]}$token_upgrade</td>
                </tr>";


    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{kernel}:</strong></td>
                <td style='width:99% !important;'>$KernelVersion</td>
                </tr>";
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{kernel architecture}:</strong></td>
                <td style='width:99% !important;'>". php_uname('m')."</td>
                </tr>";


    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{kernel compiled}:</strong></td>
                <td style='width:99% !important;'>$compiled</td>
                </tr>";

    $html[]=APP_XKERNEL();

    if(count($CVES)>0){
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{important_updates}:</strong></td>
                <td style='width:99% !important;'>". @implode(' ',$CVES)."</td>
                </tr>";

    }else{
        $APP_SQUID=APP_SQUID();
        $important_updates=true;
        if($APP_SQUID<>null){ $html[]=$APP_SQUID;$important_updates=false;}

        if($important_updates) {
            $html[] = "<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{important_updates}:</strong></td>
                <td style='width:99% !important;'><span class='label label'>{nothing}</span></td>
                </tr>";
        }

    }
    $html[]="<tr><td colspan=3><p>&nbsp;</p></tr>";
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{cpu_cores}:</strong></td>
                <td style='width:99% !important;'>$CPU_NUMBER</td>
                </tr>";

    foreach ($PROCESSOR as $key=>$val){
        $key=strtolower($key);
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{{$key}}:</strong></td>
                <td style='width:99% !important;'>$val</td>
                </tr>";
    }
    $EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));






    $apparmor_protection=1;
    $grub=explode("\n",@file_get_contents("/etc/default/grub"));
    foreach ($grub as $line){
        if(preg_match("#GRUB_CMDLINE_LINUX=.*?apparmor=0#",$line)){
            $apparmor_protection=0;
        }
    }
    $html[]="<tr><td colspan=3><p>&nbsp;</p></tr>";
    $EnableSystemOptimize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSystemOptimize"));

    if($EnableIntelCeleron==1){
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{enable_intel_celeron}:</strong></td>
                <td style='width:99% !important;'><span class='label label'>{inactive2}</span></td>
                </tr>";

    }else{
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{enable_intel_celeron}:</strong></td>
                <td style='width:99% !important;'><span class='label label-primary'>{active2}</span></td>
                </tr>";

    }

    $SystemDenyUsb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemDenyUsb"));
    $href="<a href=\"#\" OnClick=\"Loadjs('$page?deny-usb-js=yes');\">";
if($SystemDenyUsb==0){
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{deny_usb}:</strong></td>
                <td style='width:99% !important;'>$href<span class='label label'>{inactive2}</span></a></td>
                </tr>";

}else{
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{deny_usb}:</strong></td>
                <td style='width:99% !important;'>$href<span class='label label-primary'>{active2}</span></a></td>
                </tr>";

}


    $sysctl=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/kernel/get/kernel.nmi_watchdog"));
    $KernelNmiWatchdog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KernelNmiWatchdog"));
    $KernelNmiWatchdog2=0;
    if(property_exists($sysctl,"Value")){
        $KernelNmiWatchdog2=intval($sysctl->Value);
    }
    $href="<a href=\"#\" OnClick=\"Loadjs('$page?KernelNmiWatchdog-js=yes');\">";
    $rebootVMI="";
    if($KernelNmiWatchdog2<>$KernelNmiWatchdog){
        $rebootVMI="&nbsp;<span class='label label-warning'>{after_rebooting}</span>";
    }

    if($KernelNmiWatchdog==0){

        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>NMI Watchdog:</strong></td>
                <td style='width:99% !important;'>$href<span class='label label'>{inactive2}</span></a>$rebootVMI</td>
                </tr>";

    }else{
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>NMI Watchdog:</strong></td>
                <td style='width:99% !important;'>$href<span class='label label-primary'>{active2}</span></a>$rebootVMI</td>
                </tr>";

    }

    $IntelQATInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IntelQATInstalled"));
    $IntelQATEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IntelQATEnabled"));
    if($IntelQATInstalled==1){
        if($IntelQATEnabled==1){
            $IntelQATVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IntelQATVersion");
            $Label=$tpl->td_href("<span class='label label-primary'>{active2} v$IntelQATVersion</span>",null,"Loadjs('$page?intelqa=yes')");
            $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>Intel QuickAssist:</strong></td>
                <td style='width:99% !important;'>$Label</td>
                </tr>";
        }else{
            $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>Intel QuickAssist:</strong></td>
                <td style='width:99% !important;'><span class='label label-default'>{inactive2}</td>
                </tr>";
        }


    }else{
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>Intel QuickAssist:</strong></td>
                <td style='width:99% !important;'><span class='label label-default'>{inactive2}</td>
                </tr>";
    }


    if($EnableSystemOptimize==1){
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{enable_system_optimization}:</strong></td>
                <td style='width:99% !important;'><span class='label label'>{inactive2}</span></td>
                </tr>";

    }else{
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{enable_system_optimization}:</strong></td>
                <td style='width:99% !important;'><span class='label label-primary'>{active2}</span></td>
                </tr>";

    }

    if($apparmor_protection==1) {
        $html[] = "<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{apparmor_protection}:</strong></td>
                <td style='width:99% !important;'>
                <a href=\"#\" OnClick=\"Loadjs('$page?apparmor-js=yes');\">
                <span class='label label-primary'>{active2}</span></a>
                </td>
                </tr>";
    }else{
        $html[] = "<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{apparmor_protection}:</strong></td>
                <td style='width:99% !important;'>
                <a href=\"#\" OnClick=\"Loadjs('$page?apparmor-js=yes');\">
                <span class='label label'>{inactive2}</span></a></td>
                </tr>";
    }
    $EnableDockerManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerManagement"));
    if($EnableDockerManagement==0){
        $html[] = "<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>Artica Docker edition:</strong></td>
                <td style='width:99% !important;'>
                <a href=\"#\" OnClick=\"Loadjs('$page?docker-js=yes');\">
                <span class='label label-default'>{inactive2}</span></a>
                </td>
                </tr>";

    }


    $DailyReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DailyReboot"));
    $DailyRebootHour=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DailyRebootHour"));
    if($DailyRebootHour==null){$DailyRebootHour="06:00:00";}

    if($DailyReboot==1){
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{DAILY_REBOOT_SERVER}:</strong></td>
                <td style='width:99% !important;'><span class='label label-primary'>{active2} {each} $DailyRebootHour</td>
                </tr>";

    }else{
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{DAILY_REBOOT_SERVER}:</strong></td>
                <td style='width:99% !important;'><span class='label label'>{inactive2}</span></td>
                </tr>";

    }
    $kernel_modules_disabled=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("kernel.modules_disabled"));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("kernel.php?nb-modules-loaded=yes");
    $loaded_modules=intval(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/NB_MODULES_LOADED"));


    $KernelModulesDisabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KernelModulesDisabled"));
    $KernelModulesDisabledSaved=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KernelModulesDisabledSaved"));

    if($KernelModulesDisabledSaved==1){
        if($KernelModulesDisabled<>$kernel_modules_disabled){
            $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{kernel_modules_disabled}:</strong></td>
                <td style='width:99% !important;'><span class='label label-warning'>{you_should_reboot_the_server}</td>
                </tr>";

        }
    }


    if($kernel_modules_disabled==1){
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{kernel_modules_disabled}:</strong></td>
                <td style='width:99% !important;'>
                    <a href=\"#\" OnClick=\"Loadjs('$page?kernel-modules-disabled-js=yes');\">
                    <span class='label label-primary'>{active2}</span></a>
                </td>
                    
                </tr>";

    }else{
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{kernel_modules_disabled}:</strong></td>
                <td style='width:99% !important;'>
                    <a href=\"#\" OnClick=\"Loadjs('$page?kernel-modules-disabled-js=yes');\">
                    <span class='label label'>{inactive2}</span>
                    </span>
                </td>
                </tr>";

    }
    $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{loaded_kernel_modules}:</strong></td>
                <td style='width:99% !important;'>
                <a href=\"#\" OnClick=\"Loadjs('$page?loaded-modules-js=yes');\">
                <span class='label label-primary'>{$loaded_modules}</span></a></td>
                </tr>";



    $ELASTICSEARCH_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_INSTALLED"));
    if($ELASTICSEARCH_INSTALLED==1){
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{APP_ELASTICSEARCH} (ElasticSearch):</strong></td>
                <td style='width:99% !important;'>
                    <a href=\"#\" OnClick=\"Loadjs('$page?elastic-remove=yes');\">
                    <span class='label label-primary'>{remove2}</span></a>
                </td>
                </tr>";

    }else{
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>{APP_ELASTICSEARCH} (ElasticSearch):</strong></td>
                <td style='width:99% !important;'>
                    <span class='label label'>{removed}</span>
                </td>
                </tr>";

    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rrd/size"));
    $RRD_SIZE_DATABASES=FormatBytes($json->Info);
        $html[]="<tr>
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><strong>RRD {database} ($RRD_SIZE_DATABASES):</strong></td>
                <td style='width:99% !important;'>
                    <a href=\"#\" OnClick=\"Loadjs('$page?reset-rrd=yes');\">
                    <span class='label label-warning'>{reset} {database}</span>
                    </a>
                </td>
                </tr>";




    $html[]="<tr><td colspan=3><p>&nbsp;</p></tr>";


    foreach ($LINUX_INFO_ARRAY[1] as $titles){
        $XVALS = array();
        if(!preg_match("#(system|Hardware|devices|information)#i",$titles)) {
            $html[] = "<tr>
        <thead>
            <th colspan='3' style='text-align: left'><H2>$titles</H2></th>
        </thead>
        </tr>";
        }


        $data=explode("\n",$LINUX_INFO_ARRAY[2][$c]);
        $ALREADY=array();
        foreach ($data as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#^(.+?):(.+)#",$line,$ri)){

                $key=trim($ri[1]);
                $val=trim($ri[2]);
                if($val==null){continue;}
                $val=str_replace(" days"," {days}",$val);
                $val=str_replace("of RAM","",$val);
                if(preg_match("#No such file or directory#i",$val)){continue;}
                if($key==null){continue;}
                if($key=="sh"){continue;}
                if($key=="/usr/share/artica-postfix/bin/linux-info.sh"){continue;}
                $md5=md5($line);
                if(isset($ALREADY[$md5])){continue;}


                $XVALS["{".strtolower($key)."}"]=$val;
                $ALREADY[$md5]=true;

            }else{
                if($XVALS["{".strtolower($key)."}"]<>null) {
                    $XVALS["{".strtolower($key)."}"] = $XVALS["{".strtolower($key)."}"] . "<br>" . $line;
                    $ALREADY[$md5]=true;
                }
            }
        }



        foreach ($XVALS as $key=>$val){
            $val=trim($val);
            if($val==null){continue;}
            if($key==null){continue;}


            $img    = null;
            if(preg_match("#kernel#i",$key)){continue;}
            if(preg_match("#(User name|vendor name|current cpu frequency|date\/time|load average|hostname)#i",$key)){continue;}
            if(preg_match("#(XDG_CURRENT_DESKTOP|can not find|not running|Load Average|GPL v)#",$val)){continue;}
            if(preg_match("#(Gigabit Ethernet Controller|virtual NIC driver|vmxnet)#i",$val)){
                $img="<img src='img/ethernet_32.png' alt=''>";

            }
            if(preg_match("#\s+Wireless\s+#i",$val)) {
                $img = "<img src='img/wifi_32.png' alt=''>";
            }
            if(preg_match("#intel\s+#i",$val)) {
                $img = "<img src='img/intel_32.png' alt=''>";
            }
            if($img==null) {
                if (preg_match("#VMware#i", $val)) {
                    $img = "<img src='img/vmware_32.png' alt=''>";


                }

                if (preg_match("#\s+cpu\s+#i", $key)) {
                    $img = "<img src='img/cpu_32.png' alt=''>";
                }
                if (preg_match("#(Cache Size|Flags|CPU Cores|Processor)#i", $key)) {
                    $img = "<img src='img/cpu_32.png' alt=''>";
                }
                if (preg_match("#Physical Memory#i", $key)) {
                    $img = "<img src='img/memory_32.png' alt=''>";
                }




            }

            if($key=="{uptime}"){continue;}


            if($img==null){$img="&nbsp;";}
            $html[]="<tr>
            <td style='width:1% !important;' nowrap>$img</td>
            <td style='width:1% !important;text-align:right' nowrap><strong>$key:</strong></td>
            <td style='width:99% !important;'>$val</td>
            </tr>";
        }


        $c++;

    }


    $html[]="</table></td></tr></table></div>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('go-exec-version','$page?go-exec-version=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function APP_SQUID():string{
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){return "";}
    $tpl=new template_admin();
    $UPDATES_ARRAY  = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo")));
    $HideNewSquidVer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewSquidVer"));
    $AVAILABLE_SQUID = $tpl->SQUID_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_SQUID == 0) {return "";}
    $NewVer=$UPDATES_ARRAY["APP_SQUID"][$AVAILABLE_SQUID]["VERSION"];
    $text_class = "text-warning";

    if($HideNewSquidVer==1){
        $text_class="text-muted";
    }

    return  "<tr>;
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><i class='$text_class ".ico_emergency." '></i></strong></td>
                <td style='width:99% !important;'><strong class='$text_class'>" . $tpl->td_href("{SQUID_NEWVERSION} $NewVer", "{SQUID_NEWVERSION_TEXT}", "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID');") . "</strong></td>
                </tr>";


}

function APP_XKERNEL():string{
    $UPDATES_ARRAY  = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo")));
    $HideArticaXTNDPIco=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaXTNDPIco"));
    if(!isset($UPDATES_ARRAY["APP_XTABLES"])){
        VERBOSE("APP_XKERNEL not in array [FALSE]",__LINE__);

        return "";}
    $tpl        = new template_admin();
    $kernver    = php_uname("r");
    $kernbin    = $tpl->kernel_binary_ver();
    $text_class = "text-warning";
    if (!isset($UPDATES_ARRAY["APP_XTABLES"][$kernbin])) {
        VERBOSE("APP_XKERNEL/$kernbin not in array [FALSE]",__LINE__);
        return "";}
    if($HideArticaXTNDPIco==1){
        $text_class="text-muted";
    }

    $def    = $tpl->_ENGINE_parse_body("{kernel_modules_update_explain}");
    $def    = str_replace("%s","$kernver",$def);
    if(is_file("/usr/lib/modules/$kernver/extra/xt_ndpi.ko")){
        VERBOSE("/usr/lib/modules/$kernver/extra/xt_ndpi.ko OK",__LINE__);
        return "";}

    return  "<tr>;
                <td style='width:1% !important;' nowrap>&nbsp;</td>
                <td style='width:1% !important;text-align:right' nowrap><i class='$text_class ".ico_emergency." '></i></strong></td>
                <td style='width:99% !important;'><strong class='$text_class'>" . $tpl->td_href("{kernel_modules_update}", "$def", "Loadjs('fw.system.upgrade-software.php?product=APP_XTABLES');") . "</strong></td>
                </tr>";
}

function go_exec_version(){
    $sock=new sockets();
    $version=$sock->go_exec_version();
    if($version=="0.0.0"){
        echo "<span class='label label-danger'>$sock->mysql_error</span>";
        return false;
    }
    echo $version;
    return true;
}