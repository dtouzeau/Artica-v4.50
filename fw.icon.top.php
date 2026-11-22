<?php
// Post SP10 - 13/10/2026
# v10/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.cpu.percent.inc");
$GLOBALS["LICENSE_EXPIRED"]=False;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){exit();}
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
clean_xss_deep();

if(isset($_GET["notifs"])){notifs();exit;}
if(isset($_GET["support-dismiss"])){support_dismiss();exit;}
if(isset($_GET["seen-updated"])){see_updated();exit;}
if(isset($_GET["SetToken"])){SetToken();exit;}
if(isset($_POST["SetToken"])){SetTokenConfirm();exit;}
if(isset($_GET["deb10-js"])){deb10_js();exit;}
if(isset($_GET["deb10-popup"])){deb10_popup();exit;}


if(isset($_GET["refresh-interval"])){refresh_interval();exit;}
//ABDEV 1/3
if(isset($_GET['adblock'])){
    if(intval($_GET['adblock'])==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("adb_detected",1);
    }
    else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("adb_detected",0);
    }
}
//END ABDEV
start();

function see_updated(){
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }


	$ID=intval($_GET["seen-updated"]);
	$q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
	if($ID>0 && $q->TABLE_EXISTS("history")){
		$q->QUERY_SQL("UPDATE history SET asseen=1 WHERE ID=$ID");
	}
	header("content-type: application/x-javascript");
	echo "LoadAjaxSilent('artica-notifs-barr','{$HTTP_X_ARTICA_SUBFOLDER}fw.icon.top.php?notifs=yes');";
}

function refresh_interval(){
    header("content-type: application/x-javascript");
    $html[]="RefreshNotifs();";
    echo @implode("\n",$html);

}
function start():bool{
   $page=CurrentPageName();
   $tpl=new template_admin();
   header("content-type: application/x-javascript");
   //echo $tpl->RefreshInterval_js("artica-notifs-barr",$page,"fw.icon.top.php?notifs=yes",10);
   return true;
}
function SetToken(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $Token      = trim($_GET["SetToken"]);
    if(isset($_GET["value"])){
        $Token="$Token:".$_GET["value"];
    }
    $tpl->js_confirm_execute("{hide_this_information}","SetToken",$Token,"LoadAjaxSilent('artica-notifs-barr','$page?notifs=yes');");

}

function SetTokenConfirm(){
    $Token      = trim($_POST["SetToken"]);
    $val=1;
    if(strpos($Token,":")>0){
        $zToken=explode(":",$Token);
        $Token=$zToken[0];
        $val=$zToken[1];
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($Token,$val);

    $NewVal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if($NewVal!=$val){
        echo "Issue while saving information (".__LINE__.")";
    }

}


function ExecTtime($start,$line){
    if(!isset($GLOBALS["VERBOSE"])){return;}
    $end = microtime(true);
    $executionTime = $end - $start;
    VERBOSE("Execution time ".number_format($executionTime, 4) . " seconds",$line);
}
function notifs(){
    $start = microtime(true);
    include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");
    include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
    ExecTtime($start,__LINE__);

    $REVERSE_APPLIANCE =false;
    $SEE_SSHPROXY = true;
    $LIBCACHE_PERL = true;
    $AD_CARE = true;
    $LIBMILTER = false;
    $users          = new usersMenus();
    $GLOBALS["CLASS_USERS"]=$users;
    if(!$users->AsAnAdministratorGeneric){return null;}
    $tpl            = new template_admin();

    // Toutes les notifications lisent articarest (GET_INFO, REST_API). Daemon
    // arrete, chaque cle vaut 0 ou "" : la cloche se remplirait de fausses
    // alertes (ex. "Fin de support de votre OS" sur une Debian 13). On n'affiche rien.
    if(!notifs_articarest_alive()){
        echo "<script>\n".notifs_minibars_js($users,$tpl)."\n</script>\n";
        return;
    }
    $LicenseINGP    = 0;
    $ERR            = array();
    $page           = CurrentPageName();
    $FINAL_TIME     = 0;
    $compiled       = php_uname('v');
    $certs = array();
    $UnboundEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){$REVERSE_APPLIANCE=true;}

    $VM_DISK_SLOW=VM_DISK_SLOW();
    $NEED_ZSWAP=NEED_ZSWAP();
    if($VM_DISK_SLOW<>null){$ERR[]=$VM_DISK_SLOW;}
    if($NEED_ZSWAP<>null){$ERR[]=$NEED_ZSWAP;}
    $NOTIF_NOT_CATEGORIZED=NOTIF_NOT_CATEGORIZED();
    if(strlen($NOTIF_NOT_CATEGORIZED)>3){
        $ERR[]=$NOTIF_NOT_CATEGORIZED;
    }

    $FOUND_AD_SERVER=FOUND_AD_SERVER();
    if($FOUND_AD_SERVER<>null){$ERR[]=$FOUND_AD_SERVER;}

    $NOTIF_SUPPORT_UNREAD=NOTIF_SUPPORT_UNREAD();
    if(count($NOTIF_SUPPORT_UNREAD)>0){
        foreach ($NOTIF_SUPPORT_UNREAD as $NOTE){
            $ERR[]=$NOTE;
        }
    }

    // Failles du noyau EN COURS D'EXECUTION (articarest aptget/kernel_cve.go) :
    // une entree par CVE tant que la machine n'a pas redemarre sur un noyau corrige.
    foreach (NOTIF_KERNEL_CVES() as $NOTE){
        $ERR[]=$NOTE;
    }

    $NOTIF_SUPPORT_TICKETS=NOTIF_SUPPORT_TICKETS();
    if(count($NOTIF_SUPPORT_TICKETS)>0){
        foreach ($NOTIF_SUPPORT_TICKETS as $NOTE){
            $ERR[]=$NOTE;
        }
    }


    $errorFile="/usr/share/artica-postfix/ressources/logs/NFQueue/error.txt";
    if(is_file($errorFile)) {
        $ERR[]=@file_get_contents($errorFile);
    }

   if($REVERSE_APPLIANCE){
        $SEE_SSHPROXY=false;
        $LIBCACHE_PERL=false;
        $AD_CARE=false;
   }

    $php_error=_php_error();
    if($php_error<>null){
        $ERR[]=$php_error;
    }
    $RPF_FILTER=RPF_FILTER();
    if(strlen($RPF_FILTER)>2){
        $ERR[]=$RPF_FILTER;
    }


	$UPDATES_ARRAY  = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo"));
    $UfdbguardSMTPNotifs=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbguardSMTPNotifs"));
    if(function_exists("json_decode")) {
        $certs = json_decode(json_encode(unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CERT_EXPIRE_DATES"))), true);
    }
    foreach($certs as $array) {
        $name="";
        $date="";
        $ts="";
        foreach($array as $k=>$v){
            if($k=="common_name"){
                $name=$v;
            }
            if($k=="dateto"){
                $date=$v;
            }
            if($k=="dateto64"){
                $ts=$v;
            }
        }
        $cdate =intval($ts);
        $today = time();
        $difference = $cdate - $today;
        if ($difference < 0) { $difference = 0; }
        $dayleft=floor($difference/60/60/24);
        $txt="{certificate} $name {ExpiresSoon} ($dayleft {days})";
        if ($dayleft<31){
            $ERR[] = "{certificates_center}||$txt||DANGER||js:document.location.href='/certificate-center'";
        }
    }
    // Volume des archives de journaux (articarest, logrotate.Capacity —
    // /logs/capacity/check, en cache une heure cote daemon) : cloche quand le
    // volume de BackupMaxDaysDir ne peut pas tenir BackupMaxDays jours.
    $cap=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/logs/capacity/check"),true);
    if(is_array($cap) && !empty($cap["Status"]) && isset($cap["Data"]) && is_array($cap["Data"]) && empty($cap["Data"]["sufficient"])){
        $captitle=str_replace("|","/",(string)($cap["Data"]["title"] ?? "{VolStockLogsInsuffisant}"));
        $captxt=str_replace("|","/",(string)($cap["Data"]["text"] ?? ""));
        $captxt=$tpl->_ENGINE_parse_body($captxt);
        $captitle=$tpl->_ENGINE_parse_body($captitle);
        $ERR[] = "$captitle||$captxt||WARN||js:Loadjs('fw.proxy.rotate.php');";
    }


    
    if(!is_array($UfdbguardSMTPNotifs)){$UfdbguardSMTPNotifs=array();}
    if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}

    if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==1){
        $SMTPNotifEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMTPNotifEmergency"));
        if($SMTPNotifEmergency==1){
            $ERR[] = "{SMTPNotifEmergency}||{SMTPNotifEmergency_explain}||DANGER||js:document.location.href='/notifications'";
        }
    }
    if(is_file("/home/artica/SQLITE_TEMP/system.perf.queue.db")) {
        $q = new lib_sqlite("/home/artica/SQLITE_TEMP/system.perf.queue.db");
        $incidents = $q->COUNT_ROWS("perfs_queue");
        if($incidents>0){
            $text=$tpl->_ENGINE_parse_body("{incidents_found}");
            $text=str_replace("%s",$incidents,$text);
            $ERR[] = "{incidents}||$text||DANGER||js:document.location.href='/incidents'";
        }
    }
    $hostname=php_uname('n');
    VERBOSE("RESOLV -> $hostname",__LINE__);
    $hostname_addr=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($hostname);
    VERBOSE("RESOLV -> $hostname == $hostname_addr",__LINE__);
    if($hostname_addr==$hostname){
        $ERR[]="{unable_to_resolve} &laquo;$hostname&raquo;||{PLEASE_ADD_IN_ETCHOSTS}||||js:window.location.href ='/hostfile'";

    }

    if($SQUIDEnable==1){
        $TProxyFortiEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TProxyFortiEnable"));
        if($TProxyFortiEnable==0) {
            $HideFGateTproxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideFGateTproxy"));
            if ($HideFGateTproxy == 0) {
                $ERR[] = "{fortigate_use}||{fortigate_use_tproxy}||MOREINFO||js:Loadjs('fw.fortigate.tproxy.php?tooltip-js=yes');||js:Loadjs('$page?SetToken=HideFGateTproxy');";
            }
        }
    }

    VERBOSE("--> APP_ARP_SCANNER()",__LINE__);
    $APP_ARP_SCANNER=APP_ARP_SCANNER();
    if(strlen($APP_ARP_SCANNER)>2){
        $ERR[]=$APP_ARP_SCANNER;
    }

    $ArticaHotFixDevs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHotFixDevs"));
    if($ArticaHotFixDevs==1){
       $NOTIF_HOTFIX_DEV=NOTIF_HOTFIX_DEV();
       if(strlen($NOTIF_HOTFIX_DEV)>1){
           $ERR[] =$NOTIF_HOTFIX_DEV;
       }
    }
    $NOTIF_HOTFIX_OFF=NOTIF_HOTFIX_OFF();
    if(strlen($NOTIF_HOTFIX_OFF)>1){
        $ERR[] =$NOTIF_HOTFIX_OFF;
    }


    $GrubSectionSeen = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubSectionSeen"));
    $SSHProxySectionSeen = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHProxySectionSeen"));
    $BTMMPWarn = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BTMMPWarn"));
    $LicensingServerError=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicensingServerError"));

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()) {
        if (strlen($LicensingServerError) > 3) {
            $ERR[] = "{license_server_inacc}||||||js:Loadjs('fw.system.watchdog.php?licenseserver-js=yes');";
        }
    }


    if($BTMMPWarn>0){
        $BTMMPWarnText=$tpl->_ENGINE_parse_body("{BTMMPWarn}");
        $BTMMPWarnText=str_replace("%s",$BTMMPWarn,$BTMMPWarnText);
        $ERR[] = "{failedloginattempts}||$BTMMPWarnText||WARN||js:Loadjs('fw.system.watchdog.php?failedloginattempts-js=yes');||js:Loadjs('$page?SetToken=BTMMPWarn&value=0');";
    }

    $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
    if($EnableSuricata==0){
        $HideSuricataManagerIco = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideSuricataManagerIco"));
        if ($HideSuricataManagerIco == 0) {
            $ERR[] = "{didyouknow}||{did_you_know_ids}||INFO||js:Loadjs('fw.ids.wizard.php?didyouknow-js=yes');||js:Loadjs('$page?SetToken=HideSuricataManagerIco');";
        }

    }

    if(!$users->AsDockerWeb) {
        $HideBootManagerIco = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideBootManagerIco"));
        if ($HideBootManagerIco == 0) {
            if ($GrubSectionSeen == 0) {
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableGrubSkin", 1);
                $ERR[] = "{new_feature_notice}||{boot_manager_explain}||INFO||js:Loadjs('fw.system.information.php?boot-manager-js&seen=yes');||js:Loadjs('$page?SetToken=HideBootManagerIco');";
            }
        }
        if($SQUIDEnable==1) {
            $EnableArticaAuthenticationAgent = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAuthenticationAgent"));
            $HideAAAuthIco = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideAAAuthIco"));
            if($EnableArticaAuthenticationAgent==0 && $HideAAAuthIco ==0){
                $ERR[] = "{didyouknow}||{feature_artica_auth_agent_explain}||MOREINFO||js:Loadjs('fw.artica.auth.agent.php?tooltip-js=yes');||js:Loadjs('$page?SetToken=HideAAAuthIco');";
            }
        }


        if($SEE_SSHPROXY) {
            if ($SSHProxySectionSeen == 0) {
                $ERR[] = "{new_feature_notice}||{APP_SSHPROXY}||INFO||js:Loadjs('fw.system.information.php?sshproxy-js=yes');||js:Loadjs('$page?SetToken=SSHProxySectionSeen');";

            }
        }
        $SnapShotRestaured_val = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotRestaured");
        if (strlen($SnapShotRestaured_val) > 2) {
            $SnapShotRestaured = unserialize($SnapShotRestaured_val);
            if (isset($SnapShotRestaured["FILENAME"])) {
                $text = $tpl->_ENGINE_parse_body("{snapshot_restored_info}");
                $text = str_replace("%s", "<strong>{$SnapShotRestaured["FILENAME"]}</strong>", $text);
                $ERR[] = "{your_snapshot}||$text||INFO||js:Loadjs('fw.snapshot.restored.php');||js:Loadjs('$page?SetToken=SnapShotRestaured');";
            }
        }
    }



	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()) {
        $LicenseInfos = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        $LicenseINGP = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseINGP"));
        if (isset($LicenseInfos["FINAL_TIME"])) {
            $FINAL_TIME = intval($LicenseInfos["FINAL_TIME"]);
        }
        if ($LicenseINGP == 0) {
            if ($FINAL_TIME > 0) {
                $ExpiresSoon = intval(time_between_day_Web($FINAL_TIME));
                if ($ExpiresSoon > 0) {
                    if ($ExpiresSoon < 10) {
                        $ERR[] = "{artica_license}||{ExpiresSoon} $ExpiresSoon {days}||ERR||js:Loadjs('fw.license.php?js-expire-soon-explain=yes');";
                    }
                }
                if ($ExpiresSoon < 1) {
                    $GLOBALS["LICENSE_EXPIRED"] = True;
                    $ERR[] = "{artica_license}: {expired}||{license_expired_explain}";
                }
            }
        }
    }
    if($LicenseINGP>0){
        if($LicenseINGP>time()) {
            $LicenseINGPDistance = distanceOfTimeInWords(time(), $LicenseINGP);
            $ERR[] = "{artica_license}: {grace_period}||{ExpiresSoon}: $LicenseINGPDistance";
        }
    }

    //ABDEV 2/3
    $adb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("adb_detected"));
    if($adb==1){
        $ERR[] = "{adb_detected_title}||{adb_detected_body}||WARNING||null";
    }
    //END ABDEV

    $HideVerifPackage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VerifPackages"));
    if($HideVerifPackage==0) {
        $VerifP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VerifPackages");
        $VerifPackages = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($VerifP);
        if(!is_array($VerifPackages)){$VerifPackages=array();}
        if($GLOBALS["VERBOSE"]){echo "VerifP:$VerifP<br>\n";print_r($VerifPackages);}
        if (count($VerifPackages) > 0) {
            foreach ($VerifPackages as $package) {
                $package = trim($package);
                if ($package == null) {continue;}
                VERBOSE("VERIF PACKAGE: $package",__LINE__);
                $pp[] = $package;

            }
            if (count($pp) > 0) {
                $text = $tpl->_ENGINE_parse_body("{x_packages_must_be_installed}");
                $text = str_replace("%s", count($pp), $text);
                $dbtext = @implode(", ", $pp);
                $ERR[] = "$text||$dbtext||WARN||js:Loadjs('fw.system.verifpackages.php');||js:Loadjs('$page?SetToken=HideVerifPackage');";
            }
        }
    }
    // Nouvelles versions des paquets geres par Artica : UNE notification, le
    // detail est le tableau de fw.updates.packages.php (class.updates.packages.inc).
    $NOTIF_ARTICA_PACKAGES=NOTIF_ARTICA_PACKAGES($UPDATES_ARRAY);
    if($NOTIF_ARTICA_PACKAGES<>""){$ERR[]=$NOTIF_ARTICA_PACKAGES;}

    // NOTIFS_NGINX ne porte plus que le mode urgence de nginx.
    $NOTIFS_NGINX=NOTIFS_NGINX($UPDATES_ARRAY);
    if(count($NOTIFS_NGINX)>0){
        foreach ($NOTIFS_NGINX as $NGINX_NOTE){
            $ERR[]=$NGINX_NOTE;
        }
    }

    $icon=new icontop();
    $DNSDIST=$icon->dnsdist();
    if(count($DNSDIST)>0){
        foreach ($DNSDIST as $NOTE){
            $ERR[]=$NOTE;
        }
    }

    $CVE_2021_44142=CVE_2021_44142(); //APP_SAMBA_VERSION
    if(count($CVE_2021_44142)>0){
        foreach ($CVE_2021_44142 as $NOTE){
            $ERR[]=$NOTE;
        }
    }


    // NOTIFS_DNSDIST ne porte plus que l'alerte � aucune interface d'ecoute �.
    $NOTIFS_DNSDIST=NOTIFS_DNSDIST($UPDATES_ARRAY);
    if(count($NOTIFS_DNSDIST)>0){
        foreach ($NOTIFS_DNSDIST as $NOTE){
            $ERR[]=$NOTE;
        }
    }


	if($SQUIDEnable==1){
	    VERBOSE("NOTIFS_SQUID",__LINE__);
		$NOTIFS=$icon->NOTIFS_SQUID($UPDATES_ARRAY);
		if(count($NOTIFS)>0){
			foreach ($NOTIFS as $notif){
                // NOTIFS_SQUID est relu depuis un cache Valkey : une valeur ecrite
                // avant le deplacement des versions vers fw.updates.packages.php
                // porterait encore l'annonce d'une nouvelle version de Squid.
                if(strpos((string)$notif,"upgrade-software.php?product=APP_SQUID")!==false){continue;}
				$ERR[]=$notif;
			}
		}
	}

    $ArticaRestStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/status"),true);
    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION<7) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/status"));
        $DEBIAN_VERSION = intval($json->DebianVersion ?? 0);
    }
    if($DEBIAN_VERSION==10){
        $HideDebian10SupportMonths=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideDebian10SupportMonths"));
        if($HideDebian10SupportMonths==0) {
            $ERR[] = "{info_end_of_support}||{info_end_of_support_months}||MOREINFO||js:Loadjs('$page?deb10-js=yes');||js:Loadjs('$page?SetToken=HideDebian10SupportMonths');";
        }
    }


	$HideDebianIco=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideDebianIco"));
    $HideDebian10DnsDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideDebian10DnsDist"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableDNSDist==1){
         if($DEBIAN_VERSION>0 && $DEBIAN_VERSION<12){
            if($HideDebian10DnsDist==0){
                $SOFT_NO_LONGER_SUPPORT=$tpl->_ENGINE_parse_body("{SOFT_NO_LONGER_SUPPORT}");
                $SOFT_NO_LONGER_SUPPORT=str_replace("%p","{APP_DNSDIST}",$SOFT_NO_LONGER_SUPPORT);
                $SOFT_NO_LONGER_SUPPORT=str_replace("%deb","<strong>Debian 12</strong>",$SOFT_NO_LONGER_SUPPORT);
                $ERR[] = "{APP_DNSDIST}||$SOFT_NO_LONGER_SUPPORT||WARN||||js:Loadjs('$page?SetToken=HideDebian10DnsDist');";
            }
        }
    }

    if(!$users->AsDockerWeb) {
        if($DEBIAN_VERSION>0 && $DEBIAN_VERSION<10){ // 0 = version inconnue, pas une vieille Debian
            if($HideDebianIco==0){
                $ERR[] = "{error_old_debian_version}||{error_limited_support_text}||WARN||||js:Loadjs('$page?SetToken=HideSystemdIco');";
            }
        }
        $HideSystemdIco = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideSystemdIco"));
        $GRUBPC_DEVICE_ERROR = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("GRUBPC_DEVICE_ERROR");
        $SYSTEMD_REMOVED = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMD_REMOVED"));


        if ($GRUBPC_DEVICE_ERROR == "DISK") {
            $ERR[] = "{clone_detected}: {do_not_reboot}||{GRUBPC_DEVICE_ERROR}||DANGER||js:Loadjs('fw.clone.detected.php')";
        }

    }

    if(!$users->AsDockerWeb) {
        $tpl = new template_admin();
        $EnableRDPProxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"));
        if ($EnableRDPProxy == 1) {
            $APP_RDPPROXY_UPGRADE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_UPGRADE"));
            if ($APP_RDPPROXY_UPGRADE == 1) {
                $ERR[] = "{NEED_UPGRADE_RDS_PROXY}||{NEED_UPGRADE_RDS_PROXY_TEXT}||DANGER||js:Loadjs('fw.rdpproxy.upgrade.php');";
            }
        }
    }

    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($UnboundEnabled==1) {
        $UnboundV = $tpl->UnBoundVersionArray();
        $UnboundVStatus = False;
        if (!isset($UnboundV["MAJOR"])) {
            $UnboundV["MAJOR"] = 0;
        }
        if (!isset($UnboundV["MINOR"])) {
            $UnboundV["MINOR"] = 0;
        }
        if (!isset($UnboundV["REVISION"])) {
            $UnboundV["REVISION"] = 0;
        }

        VERBOSE("UNBOUND M:{$UnboundV["MAJOR"]} MIN:{$UnboundV["MINOR"]}");

        if ($UnboundV["MAJOR"] > 0) {
            if ($UnboundV["MINOR"] > 8) {
                $UnboundVStatus = True;
            }
        }

        if ($users->AsDnsAdministrator) {
            if (!$UnboundVStatus) {
                $unbound_wrong_version_text = $tpl->_ENGINE_parse_body("{unbound_wrong_version_text}");
                $unbound_wrong_version_text = str_replace("%ver", $UnboundV["MAJOR"] . "." . $UnboundV["MINOR"] . "." . $UnboundV["REVISION"], $unbound_wrong_version_text);
                $ERR[] = "{APP_UNBOUND}||{$unbound_wrong_version_text}||DANGER||js:Loadjs('fw.system.upgrade-software.php?product=APP_UNBOUND');";
            }
        }
    }

	$NOT_MONITOR["APP_UDEVD"]=true;
    if(is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")) {
        $ini = new Bs_IniHandler();
        $ini->loadFile("/usr/share/artica-postfix/ressources/logs/global.status.ini");
        if (property_exists($ini, "_params")) {
            if (is_array($ini->_params)) {
                foreach ($ini->_params as $key => $array) {
                    if (isset($NOT_MONITOR[$key])) {
                        continue;
                    }
                    if(!isset($array["service_disabled"])){$array["service_disabled"]=0;}
                    if(!isset($array["running"])){$array["running"]=0;}

                    $service_name = $array["service_name"];
                    $installed = 1;
                    if (isset($array["installed"])) {
                        $installed = $array["installed"];
                    }
                    if ($installed == 0) {
                        continue;
                    }

                    $service_disabled = intval($array["service_disabled"]);
                    if ($service_disabled == 0) {
                        continue;
                    }
                    $running = intval($array["running"]);
                    if ($GLOBALS["VERBOSE"]) {
                        echo "<li>Service: $service_name disabled=$service_disabled; running = $running</li>\n";
                    }
                    if ($running == 1) {
                        continue;
                    }


                    if ($key == "APP_POSTGRES") {
                        $ERR[] = "{{$service_name}} {stopped}||js:Loadjs('fw.postgresql.restart.php');";
                        continue;
                    }
                }
            }
        }
    }

	$ini=new Bs_IniHandler();
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	if(is_array($ini->_params)){
		foreach ($ini->_params as $key=>$array){
			if(isset($NOT_MONITOR[$key])){continue;}
            if(!isset($array["service_disabled"])){continue;}
			$service_name=$array["service_name"];
			$installed=1;
			if(isset($array["installed"])){$installed=$array["installed"];}
            if(!isset($array["running"])){$array["running"]=0;}
			if($installed==0){continue;}
			$service_disabled=intval($array["service_disabled"]);
			if($service_disabled==0){continue;}
			$running=intval($array["running"]);
			if($GLOBALS["VERBOSE"]){echo "<li>Service: $service_name disabled=$service_disabled; running = $running</li>\n";}
			if($running==1){continue;}

			if($key=="APP_POSTGRES"){
				$ERR[]="{{$service_name}} {stopped}||js:Loadjs('fw.postgresql.restart.php');";
				continue;
			}
        }
	}

    if(!$users->AsDockerWeb) {
        if (method_exists($GLOBALS["CLASS_SOCKETS"], 'INFO_EXISTS')) {
            if (!$GLOBALS["CLASS_SOCKETS"]->INFO_EXISTS("ArticaAutoUpateOfficial")) {
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaAutoUpateOfficial", 1);
            }
        }
        $ArticaAutoUpateOfficial = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
        $ArticaAutoUpateNightly = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
        $CURVER = @file_get_contents("VERSION");
        $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));

        $key_lts = update_find_lts($ArticaUpdateRepos);

        if ($key_lts > 0) {
            $CURVER_KEY = str_replace(".", "", $CURVER);
            $LTS = $ArticaUpdateRepos["LTS"];
            $Lastest = $LTS[$key_lts]["VERSION"];
            if ($key_lts > $CURVER_KEY) {
                $NEW_LTS_TEXT = $tpl->_ENGINE_parse_body("{NEW_LTS_TEXT}");
                $NEW_LTS_TEXT = str_replace("%s", $Lastest, $NEW_LTS_TEXT);
                if ($users->AsSystemAdministrator) {
                    $ERR[] = "Artica LTS $Lastest||$NEW_LTS_TEXT||INFO||js:Loadjs('fw.update.lts.php')||btntxt:{update_artica}";
                } else {
                    $ERR[] = "Artica LTS $Lastest||$NEW_LTS_TEXT||INFO||||";
                }
            }
        }

        if (is_array($ArticaUpdateRepos)) {
            if(isset($ArticaUpdateRepos["OFF"])) {
                if ($ArticaAutoUpateOfficial == 1) {
                    $key_offical = update_find_latest($ArticaUpdateRepos);
                    $CURVER_KEY = str_replace(".", "", $CURVER);
                    $OFFICIALS = $ArticaUpdateRepos["OFF"];
                    $Lastest = $OFFICIALS[$key_offical]["VERSION"];

                    if ($key_offical > $CURVER_KEY) {
                        $ERR[] = "{update_artica}: $Lastest||{NEW_RELEASE_TEXT}||INFO||js:document.location.href='/fw.update.progress.php'||btntxt:{update_now}";
                    } else {
                        if ($ArticaAutoUpateNightly == 1) {
                            $key_nightly = update_find_latest_nightly($ArticaUpdateRepos);
                            $NIGHTLY = $ArticaUpdateRepos["NIGHT"];
                            $Lastest = $NIGHTLY[$key_nightly]["VERSION"];
                            if ($key_nightly > $CURVER_KEY) {
                                $ERR[] = "{update_artica}: $Lastest||{NEW_RELEASE_TEXT}||INFO||js:document.location.href='/fw.update.progress.php'||btntxt:{update_now}";
                            }
                        }

                    }
                }
            }
        }
        $NextSP = $GLOBALS["CLASS_SOCKETS"]->isNextSP();
        if ($NextSP > 0) {
            $tb = explode(".", $NextSP);
            if (isset($tb[0])) {
                $SmallVer = "$tb[0]";
            }
            if (isset($tb[1])) {
                $SmallVer = "$tb[0].$tb[1]";
            }
            $ERR[] = "{update_artica}: $SmallVer Service Pack $NextSP||{NEW_RELEASE_TEXT}||INFO||js:document.location.href='/fw.update.progress.php'||btntxt:{update_now}";

        }
    }
    if(!$users->AsDockerWeb) {
        if ($users->AsSystemAdministrator) {
            if (is_file("/home/artica/SQLITE/nightly.db")) {
                $q = new lib_sqlite("/home/artica/SQLITE/nightly.db");
                // Installation neuve : la table history n'existe qu'apres le premier controle des mises a jour.
                $results = array();
                if ($q->TABLE_EXISTS("history")) {
                    $results = $q->QUERY_SQL("SELECT * FROM history WHERE asseen=0 ORDER BY updated DESC");
                    if (!is_array($results)) { $results = array(); }
                }
                $page = CurrentPageName();

                if (count($results) > 2) {
                    $ERR[] = "{remove_all_update_notifications}||js:Loadjs('fw.updates.php?seen-all=yes')||INFO||btntxt:{gotit}";
                }

                foreach ($results as $index => $ligne) {
                    $ID = $ligne["ID"];
                    $version = $ligne["version"];
                    $updated = $tpl->time_to_date($ligne["updated"], true);
                    $STEXT = $tpl->_ENGINE_parse_body("{NEW_UPDATED_TEXT}");
                    $STEXT = str_replace("%ver", "$version", $STEXT);
                    $STEXT = str_replace("%time", "$updated", $STEXT);
                    $ERR[] = "$STEXT||js:Loadjs('$page?seen-updated=$ID')||INFO||btntxt:{hide_info_def}";
                }

            }


            $MAIN_APT_GET_JSON=NOTIF_MAIN_APT_GET_JSON();
            if(strlen($MAIN_APT_GET_JSON)>5){
                $ERR[] =$MAIN_APT_GET_JSON;
            }



        }

        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST")) == 1) {
            if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_INSTALLED")) == 0) {
                if (!is_file("/etc/init.d/open-vm-tools")) {
                    $ERR[] = "{APP_VMWARE_TOOLS_NOT_INSTALLED}||js:Loadjs('fw.system.vmtools.install.php')||WARN"; //open-vm-tools
                }
            }
        }
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HYPERV_HOST")) == 1) {
            if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HYPERV_DAEMONS_INSTALLED")) == 0) {
                $ERR[] = "{APP_HYPERV_TOOLS_NOT_INSTALLED}||js:Loadjs('fw.php7.0-hyperv-daemons.php')||WARN"; //hyperv-daemons
            }
        }


        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("QEMU_HOST")) == 1) {
            if (!is_file("/usr/sbin/qemu-ga")) {
                $ERR[] = "{QEMU_AGENT_NOT_INSTALLED}||js:Loadjs('fw.php7.0-QemuGuestAgent.php')||WARN"; //qemu-guest-agent

            }

        }

        $ERR[]=NOTIF_LATEST_KERNEL();

    }
	$HideCVE2021P26708=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCVE2021P26708"));
	if($HideCVE2021P26708==0) {

        if (preg_match("#\(.*?([0-9]+)-([0-9]+)-([0-9]+).*?\)#", $compiled, $re)) {
            $Year = $re[1];
            $Month = $re[2];
            $Day = $re[3];
            $stime = strtotime("$Year-$Month-$Day 00:00:00");
            if ($stime < 1611961200) {
                $ERR[] = "{security}: CVE-2021-26708||||DANGER||||js:Loadjs('$page?SetToken=HideCVE2021P26708');";
            }
        }
    }

    if(!$users->AsDockerWeb) {
        $NEEDRESTART = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NEEDRESTART"));
        if ($NEEDRESTART == 1) {
            $ERR[] = "{need_reboot}";
        }
    }


	$DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
	VERBOSE("DEBIAN_VERSION = $DEBIAN_VERSION",__LINE__);

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    if($PowerDNSEnableClusterSlave==1){
        $PowerDNSClusterClientStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientStop"));
        if($PowerDNSClusterClientStop==1){
            $ERR[] = "{cluster_replication_freezed}||";

        }

    }

	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledTemp"))==1){
		$ERR[]="{APP_UFDBGUARDD_PAUSED}||js:Loadjs('fw.proxy.disable.php')";
	}

	if(!extension_loaded("pdo_sqlite")){
		$ERR[]="{Warninphp70sqlite3}||js:Loadjs('fw.php7.0-sqlite3.php')";
	}
	if ( !class_exists( 'Memcached' ) ) {
		$ERR[]="{Warninphp70memcached}||js:Loadjs('fw.php7.0-memcached.php')";

	}


    $EnableProFTPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProFTPD"));
    if($EnableProFTPD==1) {
        if (!is_file("/usr/lib/proftpd/mod_sql_sqlite.so")) {
            $ERR[] = "{WarninProftpdModSqlite}||js:Loadjs('fw.php7.0-ProftpdModSqlite.php')";
        }
    }
    if($AD_CARE) {
        $WindowsActiveDirectoryKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
        if ($WindowsActiveDirectoryKerberos == 1) {
            if (!is_file("/usr/bin/krenew")) {
                $ERR[] = "{krenewMissing}||js:Loadjs('fw.aptget-install.php?pkg=kstart')";
            }
        }
    }

    $PostfixEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("EnablePostfix"));


    if ($UnboundEnabled==1){
        if (!is_file("/usr/lib/x86_64-linux-gnu/libprotobuf-c.so.1.0.0")) {
            //libprotobuf-c1
            $ERR[] = "{libprotobufc1missing}||js:Loadjs('fw.php7.0-libprotobufc1.php')";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libfstrm.so.0")) {
            //libfstrm0
            $ERR[] = "{libfstrm0missing}||js:Loadjs('fw.php7.0-libfstrm0.php')";
        }

    }

    if(!$users->AsDockerWeb) {

        if($LIBMILTER) {
            if (!is_file("/usr/lib/x86_64-linux-gnu/libmilter.so.1.0.1")) {
                $ERR[] = "{libmilter101Missing}||js:Loadjs('fw.php7.0-libmilter.php')";
            }
        }


        if($SQUIDEnable==1) {
            if (!is_file("/usr/lib/x86_64-linux-gnu/libmnl.so.0")) {
                $ERR[] = "{libmnldevMissing}||js:Loadjs('fw.aptget-install.php?pkg=libmnl0')";
            }
        }




        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("need_to_reboot_webconsole")) == 1) {
            $ERR[] = "{rebootconsole}||js:Loadjs('fw.rebootconsole.php?ask=yes')";
        }
        $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
        if($DisableNetworking==0) {
            $qlite = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $sql = "SELECT count(*) as tcount FROM nics WHERE enabled=1";
            $ligne = $qlite->mysqli_fetch_array($sql);
            if(!$qlite->ok) {
                $ERR[]="{error}<br>$qlite->mysql_error||js:document.location.href='/nics'";
            }else {
                if(!isset($ligne["tcount"])){
                    $ligne["tcount"]=0;
                }
                if (intval($ligne["tcount"]) == 0) {
                    $ERR[] = "{edit_interface_error}||js:document.location.href='/nics'";
                }
            }
        }
        $LOCALES_NOT_COMPILED=LOCALES_NOT_COMPILED($ArticaRestStatus);
        if(strlen($LOCALES_NOT_COMPILED)>1){
            $ERR[] = $LOCALES_NOT_COMPILED;
        }



        $EnableDNSFilterd = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
        if ($EnableDNSFilterd == 1) {
            $DNSFILTERD_NUM_DATABASES = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFILTERD_NUM_DATABASES"));
            if ($DNSFILTERD_NUM_DATABASES == 0) {
                $ERR[] = "{NO_DNS_FILTER_DATABASE_LOADED}||js:Loadjs('fw.ufdb.databases.update.php');";
            }
        }

    }

    if($AD_CARE) {
        $ERR = CheckAds($ERR);
    }

	$PDNSInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSInstalled"));
	$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
	if($PDNSInstalled==1){if($EnablePDNS==1){
		include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
		$q=new mysql_pdns();
		if($q->TABLE_EXISTS("pdnsutil_chkzones")) {
            $sql = "SELECT COUNT(*) AS tcount FROM pdnsutil_chkzones";
            $ligne2 = mysqli_fetch_array($q->QUERY_SQL($sql));
            if ($ligne2["tcount"] > 0) {
                $ERR[] = "{$ligne2["tcount"]} {dns_errors}||js:Loadjs('fw.pdns.domains.status.php?domain-id=0')";
            }
        }
	}}


    $class_text="text-danger";
    $icon=ico_emergency;
    $CC=0;
    foreach ($ERR as $error) {
        $error = trim($error);
        if ($error == null) {continue;}
        $CC++;
    }

    // Barres CPU/RAM du bandeau: la minuterie qui les alimente etait armee en fin
    // de fonction, donc APRES le return ci-dessous. Sur une appliance sans aucune
    // notification ($CC==0) elle n'etait jamais posee, et les deux barres restaient
    // figees sur le "0%" statique emis par fw-top-bar.php -- alors que
    // fw.system.status.php?top-cpu=yes repondait parfaitement. Le bandeau n'a aucun
    // rapport avec la cloche: on arme la minuterie dans les deux branches // Dispo uniquement si Admin.

    $jsMiniBars=notifs_minibars_js($users,$tpl);

    if($CC==0){
        echo "<script>\n$jsMiniBars\n</script>\n";
        return;
    }


    echo "<a class=\"dropdown-toggle count-info\" data-toggle=\"dropdown\" href=\"#\">
        <i class=\"text-warning fa fa-bell\"></i>  <span class=\"label label-danger\">$CC</span>
    </a>
    <ul class=\"dropdown-menu dropdown-alerts\">";

    foreach ($ERR as $error){
	$explain    = null;
    $button     = null;
    if($error==null){continue;}

    $LEVEL_AV["DANGER"]=true;
    $LEVEL_AV["WARN"]=true;

	if(strpos($error, "||")){
        $LEVEL=null;
		$class_text="text-danger";
		$icon=ico_emergency;
        $button=null;
		$ft=explode("||",$error);
		$explain=$ft[1];
		$error=$ft[0];
        if(isset($ft[2])){$LEVEL=$ft[2];}
		$js=null;$js2=null;
		$btn="btn-danger";
		$btn_text="{fix_it}";
        $btn_hide="{hide}";
		if(isset($LEVEL_AV[$LEVEL])){
		    if($LEVEL=="WARN"){
                $class_text="text-warning";
                $btn="btn-warning";
                $icon=ico_emergency;
            }


        }

        if($LEVEL=="MOREINFO") {
            $btn_hide = "{i_am_not_interested}";
            $btn_text="{more_information}";
        }
        $hide=null;

        // btnhide: choisit le libelle du bouton de rejet, comme btntxt: le fait
        // pour celui d'action. Lu ICI et non dans la boucle plus bas: $btn_hide
        // est consomme des le bloc suivant.
        foreach ($ft as $ixh=>$ch){
            if($ixh<5){continue;}
            if(!is_string($ch)){continue;}
            if(preg_match("#^btnhide:(.+)#",$ch,$fh)){$btn_hide=$fh[1];$ft[$ixh]=null;}
        }

		if(isset($ft[4])){
            $hideNotif=$ft[4];
            if(preg_match("#^js:(.+)#", $hideNotif,$fs)){$js2=$fs[1];}
            if(!is_null($js2)) {
                if (trim($js2) <> null) {
                    $hide = "<button style=\"text-transform: capitalize;\" 
                            class=\"btn btn-primary btn-xs\" type=\"button\" 
				OnClick=\"$js2\">" . $tpl->_ENGINE_parse_body($btn_hide) .
                        "</button>&nbsp;";
                }
            }

            $ft[4]=null;
        }


		foreach ($ft as $index=>$content){
			if($index==0){continue;}
			if($index==1){continue;}
			if($index==2){continue;}
            if($content==null){continue;}
			if(preg_match("#^js:(.+)#", $content,$fz)){$js=$fz[1];unset($ft[$index]);continue;}
			if(preg_match("#btntxt:(.+)#", $content,$fz)){$btn_text=$fz[1];unset($ft[$index]);continue;}
		}


		if(preg_match("#^js:(.+)#", $explain,$fz)){$js=$fz[1];$explain=null;}
		if(isset($ft[2])){
            if($ft[2]=="INFO" || $ft[2]=="MOREINFO"){
		        $btn="btn-info";
		        $icon="fa-info";
		        $class_text="text-info";
		    }
		}


		if($js<>null or $hide<>null){
		 	$button="<div style='text-align:right;margin-top:5px'>$hide";
		 	if($js<>null) {
                $button = "$button<button style=\"text-transform: capitalize;\"   
                                    class=\"btn $btn btn-xs\" type=\"button\" 
                                    OnClick=\"$js\">" . $tpl->_ENGINE_parse_body($btn_text) ;
                $button = "$button</button>";
                }
                $button="$button</div>";
		}
	}
	$error=$tpl->_ENGINE_parse_body($error);
	if($explain<>null){$explain="<br><small style='color:black;font-weight:normal'>".$tpl->_ENGINE_parse_body($explain)."</small>";}
echo "<li style='font-weight:bold;font-size:14px'>
<div>
	<i class=\"$class_text fa $icon fa-fw\"></i> <span class=$class_text>$error$explain</span>
	$button
</div>
</li>
<li class=\"divider\"></li>";
}

    $js=$jsMiniBars;   // calculee plus haut, pour etre aussi emise quand $CC==0

    echo "</ul>\n";
    echo "<script>\n";
    echo "\tif(document.getElementById('WSUSOFFLINE-STATE') ){\n\t\tLoadAjaxSilent('WSUSOFFLINE-STATE','fw.wsusoffline.php?status=yes');\n\t}\n";
    echo "$js\n";
    echo "</script>\n";


}

function _php_error():string{

    $functions=array();
    $bad=array();
    $functions[]="curl_init";
    $functions[]="pg_fetch_assoc";
    $functions[]="json_encode";
    $functions[]="json_decode";


    foreach ($functions as $function) {
        if (!function_exists($function)) {
            $bad[] = $function;
        }
    }

    if(!extension_loaded("pdo_sqlite")){
        $bad[] = "pdo_sqlite";
    }
    if(count($bad)==0){return "";}

    return "{missing_php_modules}: ".@implode(", ",$bad)."||{missing_php_modules_explain}||||DANGER||";
}

function CheckAds($ERR){
	$sock=new sockets();
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if($EnableKerbAuth==0){return $ERR;}
	if($GLOBALS["LICENSE_EXPIRED"]){return $ERR;}
	include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");

	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
	$ERR=test_ldap_connection($array,$ERR);
	$ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
	if(!is_array($ActiveDirectoryConnections)){return $ERR;}
	if(count($ActiveDirectoryConnections)==0){return $ERR;}
	foreach ($ActiveDirectoryConnections as $index=>$ligne){
        if(!is_numeric($index)){continue;}if(!isset($ligne["LDAP_SERVER"])){continue;}
        $ERR=test_ldap_connection($ligne,$ERR);
    }
	return $ERR;

}
function test_ldap_connection($array,$ERR){
    if(!isset($array["LDAP_SUFFIX"])) {
        return $ERR;
    }

    if(isset($GLOBALS["ALREADY_LDAP_SUFFIX"][$array["LDAP_SUFFIX"]])){
        return $ERR;
    }

    $ad=new ActiveDirectory();
    $GLOBALS["ALREADY_LDAP_SUFFIX"][$array["LDAP_SUFFIX"]]=true;
    if(!$ad->test_ldap_connection($array["LDAP_SUFFIX"])){
        $ERR[]="{failed_connect_ldap} {$GLOBALS["LDAP_CONNECTION_FAILED"]}||js:document.location.href='/ad-ldap';";
    }

    return $ERR;
}
function update_find_latest($array):int{
    if(!is_array($array)){return 0;}
    if(!isset($array["OFF"])){return 0;}
	if(!is_array($array["OFF"])){return 0;}
	$MAIN=$array["OFF"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}if($key>$keyMain){$keyMain=$key;}}
	return intval($keyMain);
}
function update_find_lts($array):int{
    if(!isset($array["LTS"])){return 0;}
    if(!is_array($array)){return 0;}
    if(!is_array($array["LTS"])){return 0;}
    $MAIN=$array["LTS"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}
        if($key>$keyMain){$keyMain=$key;}}
    return intval($keyMain);
}

/**
 * Hotfix installe sur cette machine, lu a la source : la ligne 2 de
 * fw.updates.php ($GLOBALS["HOTFIX"]="20260908-02";), que le paquet hotfix
 * reecrit a chaque application. C'est la meme lecture que
 * exec.artica.update.manu.php::CurrentHotFix() et futils.ArticaCurrentHotfix()
 * cote daemon. Retourne "" si aucun hotfix n'est installe.
 */
function hotfix_current():string{
    $f=explode("\n",(string)@file_get_contents(dirname(__FILE__)."/fw.updates.php"));
    foreach ($f as $line){
        if(preg_match("#GLOBALS\[.*?HOTFIX.*?\].*?([0-9\-]+)#",$line,$re)){
            return $re[1];
        }
    }
    return "";
}
/** "20260908-02" -> 2026090802 : les hotfix se comparent comme des entiers. */
function hotfix_to_int(string $ver):int{
    return intval(preg_replace("#[^0-9]#","",$ver));
}
/**
 * Vrai si $candidate est STRICTEMENT plus recent que le hotfix installe.
 * UPDATE_TO_HOTFIX est un reglage persistant que le daemon ne recalcule qu'a
 * son rythme (cache 5 min + index depot) : apres l'application d'un hotfix il
 * peut encore designer une version deja depassee. La page ne doit donc jamais
 * l'afficher sans le comparer a ce qui tourne reellement.
 */
function hotfix_is_newer(string $candidate):bool{
    $cand=hotfix_to_int($candidate);
    if($cand==0){return false;}
    $cur=hotfix_to_int(hotfix_current());
    return $cand>$cur;
}
function NOTIF_HOTFIX_OFF():string{
    $sock=new sockets();
    $sock->REST_API("/system/artica/latestHotfix");
    $page=CurrentPageName();
    $UPDATE_TO_HOTFIX=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UPDATE_TO_HOTFIX"));
    if (strlen($UPDATE_TO_HOTFIX)<5){
        return "";
    }
    if(!hotfix_is_newer($UPDATE_TO_HOTFIX)){return "";}
    $ToKenHide="HideOffHotFix$UPDATE_TO_HOTFIX";
    $Hideen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($ToKenHide));
    if($Hideen==1){return "";}

    $tpl=new template_admin();
    $new_available_hotfix_text=$tpl->_ENGINE_parse_body("{new_available_hotfix_text}");
    $new_available_hotfix_text=str_replace("%s",$UPDATE_TO_HOTFIX,$new_available_hotfix_text);
    return  "{new_available_hotfix}||$new_available_hotfix_text||INFO||js:Loadjs('fw.updates.php?update-hotfix-js=yes');||js:Loadjs('$page?SetToken=$ToKenHide');";
}
function NOTIF_HOTFIX_DEV():string{
    if(!function_exists("json_decode")) {return "";}
    $sock=new sockets();
    $data=$sock->REST_API("/system/artica/hotfix/devs");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        VERBOSE("NOTIF_HOTFIX_DEV ERROR : " .json_last_error_msg(),__LINE__);
        return "";
    }
    //var_dump($json);
    //$CurrentHotFix=$json->CurrentHotFix;
    if(!property_exists($json,"HotFixStringAvailabe")) {return "";}
    $HotFixStringAvailabe=$json->HotFixStringAvailabe;
    VERBOSE("NOTIF_HOTFIX_DEV HotFixStringAvailabe=$HotFixStringAvailabe",__LINE__);
    if(!is_null($HotFixStringAvailabe)) {
        if (strlen($HotFixStringAvailabe) < 4) {
            return "";
        }
    }
    if(!hotfix_is_newer((string)$HotFixStringAvailabe)){return "";}
    $tpl=new template_admin();
    $new_available_hotfix_text=$tpl->_ENGINE_parse_body("{new_available_hotfix_text}");
    $new_available_hotfix_text=str_replace("%s",$HotFixStringAvailabe,$new_available_hotfix_text);
    return  "{new_available_hotfix}||$new_available_hotfix_text||INFO||js:Loadjs('fw.system.upgrade-hotfix.php');";

}
function update_find_latest_nightly($array):int{
	if(!is_array($array["NIGHT"])){return 0;}
	$MAIN=$array["NIGHT"];
	$keyMain=0;
	foreach ($MAIN as $key=>$ligne){
	    $key=intval($key);
	    if($key==0){continue;}
	    if($key>$keyMain){$keyMain=$key;}
	}

	return intval($keyMain);
}


// Une entree de cloche par faille du noyau signalee vulnerable par le daemon
// (GET /system/debian/kernel/cves). Pas de bouton de rejet : l'entree disparait
// d'elle-meme au redemarrage sur un noyau corrige. Daemon trop ancien (route
// absente) ou reponse illisible : rien.
function NOTIF_KERNEL_CVES():array{
    $out=array();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/debian/kernel/cves"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["Data"]) || !is_array($json["Data"])){
        return $out;
    }
    foreach ($json["Data"] as $row){
        if(!is_array($row) || empty($row["vulnerable"])){continue;}
        $cve=preg_replace('#[^A-Z0-9-]#','',(string)($row["cve"] ?? ""));
        if($cve==""){continue;}
        // Aucun correctif pour cette serie (Debian 10 en 4.19) : la mise a jour du
        // noyau de la console resterait en 4.19, le bouton ouvre la page du wiki
        // (noyau ELTS linux-6.1). Le champ 4 est le bouton de rejet : laisse vide.
        if(empty($row["fixed_in"])){
            $url="https://wiki.articatech.com/maintenance/security/$cve";
            $out[]="$cve||Please update the Linux kernel||DANGER||js:s_PopUpFull('$url',1024,768,'Wiki');||||btntxt:{more_information}";
            continue;
        }
        $out[]="$cve||Please update the Linux kernel||DANGER||js:Loadjs('fw.system.upgrade-kernel.php');";
    }
    return $out;
}

function NOTIF_LATEST_KERNEL():string{
    $page       = CurrentPageName();
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/debian/latestkernel");
    $json=json_decode($data);
    if($json->Status){
        VERBOSE("Status = TRUE",__LINE__);
        return "";}



    if(!property_exists($json,"version")){
        return "";
    }
    if(!property_exists($json,"latest")){
        return "";
    }
    if(is_null($json->version)){return "";}
    if(strlen($json->version)<3){return "";}
    $json->version = str_replace( "+deb13", "",$json->version);
    $json->version = str_replace("+deb14", "",$json->version);
	$json->version = str_replace("+deb15", "",$json->version);
	$json->version = str_replace( "+deb16", "",$json->version);
	$json->version = str_replace( "+deb17", "",$json->version);
	$json->version = str_replace( "+deb18", "",$json->version);
	$json->version = str_replace( "+deb19", "",$json->version);
	$json->version = str_replace( "+deb20", "",$json->version);

    $json->latest = str_replace( "+deb13", "",$json->latest);
    $json->latest = str_replace("+deb14", "",$json->latest);
    $json->latest = str_replace("+deb15", "",$json->latest);
    $json->latest = str_replace( "+deb16", "",$json->latest);
    $json->latest = str_replace( "+deb17", "",$json->latest);
    $json->latest = str_replace( "+deb18", "",$json->latest);
    $json->latest = str_replace( "+deb19", "",$json->latest);
    $json->latest = str_replace( "+deb20", "",$json->latest);
    $json->latest = str_replace( "+deb20", "",$json->latest);

    $tpl=new template_admin();
    $upgrade_kernel1=$tpl->_ENGINE_parse_body("{upgrade_kernel1}");
    $upgrade_kernel1=str_replace("%s","&laquo;<strong>$json->latest&raquo;</strong>",$upgrade_kernel1);
    $upgrade_kernel1=str_replace("%v",$json->version,$upgrade_kernel1);

    return "{kernel_update}||$upgrade_kernel1||WARN||js:Loadjs('fw.system.upgrade-kernel.php');||js:Loadjs('$page?SetToken=HideArticaXTNDPIco');";

}


function APP_ARP_SCANNER():string{
    if(!function_exists("json_decode")) {return "";}
    $ARPScannerSeen= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARPScannerSeen"));
    if($ARPScannerSeen==1){
        return "";
    }
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/arpscan/count");
    $json=json_decode($data);
    if(!$json->Status) {
        return "";
    }

    $about_arpscanner=$tpl->_ENGINE_parse_body("{about_arpscanner}");
    $about_arpscanner=str_replace("%s",$json->count,$about_arpscanner);
    return  "Passive ARP Scanner||$about_arpscanner||INFO||js:Loadjs('fw.networks.php?arpscanner-js=yes');";



}





// Reponses du support reperees par la surveillance des 3 minutes
// (supporttool/watch.go) et pas encore ecartees.
//
// Deux boutons: celui d'action ouvre le fil pour lire la reponse, celui de
// rejet la retire de la cloche. Le format d'entree les porte aux positions 3 et
// 4; btnhide: en 5 nomme le second « {ignore} » plutot que « {hide} ».
function NOTIF_SUPPORT_UNREAD():array{
    $ERR=array();
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator){return $ERR;}
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/support/unread")),true);
    if(!is_array($resp) || empty($resp["Status"])){return $ERR;}
    $data=isset($resp["data"])?$resp["data"]:null;
    if(!is_array($data)){return $ERR;}
    $page=CurrentPageName();
    foreach($data as $u){
        if(!is_array($u)){continue;}
        $tid=intval(isset($u["ticketId"])?$u["ticketId"]:0);
        $thid=intval(isset($u["threadId"])?$u["threadId"]:0);
        if($tid<1 || $thid<1){continue;}
        $number=preg_replace("/[^0-9]/","",(string)(isset($u["number"])?$u["number"]:""));
        $subject=support_notif_clean(isset($u["subject"])?$u["subject"]:"");
        $from=support_notif_clean(isset($u["from"])?$u["from"]:"");
        $prev=support_notif_clean(isset($u["preview"])?$u["preview"]:"");
        if(strlen($prev)>160){$prev=substr($prev,0,160)."...";}
        $title="{new_message} #".$number.($subject!==""?" - ".$subject:"");
        $explain=($from!==""?$from." &middot; ":"").$prev;
        $ERR[]=$title."||".$explain."||INFO"
              ."||js:Loadjs('fw.support.php?show-js=".$tid."');"
              ."||js:Loadjs('".$page."?support-dismiss=".$thid."');"
              ."||btnhide:{ignore}";
    }
    return $ERR;
}

// Le geste « {ignore} ». Loadjs EXECUTE la reponse: on rend le rafraichissement
// de la cloche, sans quoi l'entree resterait a l'ecran jusqu'au prochain cycle.
function support_dismiss(){
    $id=intval($_GET["support-dismiss"]);
    if($id<1){return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/support/unread/dismiss/".$id);
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
}

// Tickets de support encore ouverts.
//
// Le bouton d'action de la cloche porte DEJA le libelle {fix_it} par defaut
// (voir notifs(): $btn_text="{fix_it}"): c'est le bouton demande, il n'y a
// aucun jeton a declarer. Le clic ouvre le fil de discussion par
// fw.support.php?show-js=<id>, qui passe par js_dialog1 -- donc une modale
// par-dessus la page courante, sans quitter ce que l'operateur consultait.
//
// Format d'une entree: titre||explication||NIVEAU||js:<code>. L'action est en
// position 3 et non 1: en position 1 elle EFFACE l'explication
// (notifs(): if(preg_match("#^js:(.+)#",$explain)) { $js=...; $explain=null; }),
// or on veut les deux. La position 4 est reservee au bouton « masquer », qui
// n'a pas de sens ici: l'entree disparait d'elle-meme quand le ticket se ferme.
function NOTIF_SUPPORT_TICKETS():array{
    $ERR=array();
    // Un ticket peut contenir des donnees d'exploitation: meme exigence que les
    // ecritures de fw.support.php.
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator){return $ERR;}
    // Aucun compte de support: on ne paie pas l'aller-retour REST. La cloche est
    // rebatie a chaque rafraichissement du bandeau, sur toutes les pages.
    if(intval(base64_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportMID")))<1){return $ERR;}

    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/support/db/tickets")),true);
    if(!is_array($resp) || empty($resp["Status"])){return $ERR;}
    if(!isset($resp["data"]) || !is_array($resp["data"])){return $ERR;}

    $tpl=new template_admin();
    $n=0;
    foreach($resp["data"] as $t){
        if(!is_array($t)){continue;}
        $id=intval($t["id"] ?? 0);
        if($id<1){continue;}
        // Le portail ne publie pas « ouvert » mais un statut: tout ce qui n'est
        // ni clos ni indesirable reste a traiter. Et un ticket supprime cote
        // portail garde son statut « active » -- c'est l'etat qui le dit.
        $status=strtolower(trim((string)($t["status"] ?? "")));
        if($status==="closed" || $status==="spam"){continue;}
        if(strtolower(trim((string)($t["state"] ?? "")))==="deleted"){continue;}
        // Une cloche n'est pas une liste de tickets: au-dela, la page de support.
        if($n>=10){break;}
        $n++;

        $number=preg_replace("/[^0-9]/","",(string)($t["number"] ?? ""));
        $subject=support_notif_clean($t["subject"] ?? "");
        if($subject===""){$subject="#".$number;}
        $updated=intval($t["updatedAt"] ?? 0);

        $title="{support} #".$number." - ".$subject;
        $explain="{open}";
        if($updated>0){$explain.=" &middot; {last_update} ".$tpl->time_to_date($updated,true);}

        $ERR[]=$title."||".$explain."||INFO||js:Loadjs('fw.support.php?show-js=".$id."');";
    }
    return $ERR;
}

// Le sujet vient du portail, donc de l'exterieur. Deux dangers propres a cette
// cloche, en plus de l'echappement HTML: « || » est le SEPARATEUR de champs --
// une barre verticale dans un sujet decalerait tous les champs suivants et
// transformerait le niveau en explication -- et _ENGINE_parse_body lit tout
// {mot} comme un jeton a resoudre.
function support_notif_clean($s):string{
    $s=str_replace(array("|","{","}"),array("/","(",")"),(string)$s);
    return htmlspecialchars(trim($s),ENT_QUOTES,"UTF-8");
}



//

// La notification unique � x paquets geres par Artica a mettre a jour �. Son
// bouton de rejet passe par fw.updates.packages.php et non par SetToken : la
// valeur memorisee est une empreinte calculee cote serveur, que SetTokenConfirm
// (qui compare des entiers) ne saurait relire.
function NOTIF_ARTICA_PACKAGES($UPDATES_ARRAY):string{
    include_once(dirname(__FILE__)."/ressources/class.updates.packages.inc");
    $rows=artica_packages_updates(false,$UPDATES_ARRAY);
    $count=count($rows);
    if($count==0){return "";}
    if(pkgupd_notif_hidden($rows)){return "";}
    $tpl=new template_admin();
    $title=str_replace("%s",$count,$tpl->_ENGINE_parse_body("{x_artica_packages_to_update}"));
    return "$title||{x_artica_packages_to_update_explain}||WARN||js:Loadjs('fw.updates.packages.php?js=yes');||js:Loadjs('fw.updates.packages.php?hide-notif-js=yes');||btntxt:{details}";
}

function NOTIFS_DNSDIST($UPDATES_ARRAY){
    $ERR                = array();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableSoft==0) {return $ERR;}
    if(!$GLOBALS["CLASS_USERS"]->AsDnsAdministrator) {return $ERR;}
    // La nouvelle version de dnsdist est une ligne de fw.updates.packages.php.

    $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
    $Count=0;
    foreach ($PowerDNSListenAddr as $interface){
        $interface=trim($interface);
        if (strlen($interface)<3){
            continue;
        }
        $Count++;
    }
    if($Count==0){
        $ERR[] = "{APP_DNSDIST}<br>{no_listen_interfaces_defined}||js:Loadjs('fw.dns.dnsdist.settings.php?dnsdist-interface-js=yes');||WARN||||";

    }

    return $ERR;
}

function RPF_FILTER():string{
    $page               = CurrentPageName();
    $Token ="NoRPFilterWarn";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token)==1)){return "";}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/kernel/rpfilter/validate"));
    if (json_last_error()> JSON_ERROR_NONE) {
        return "";

    }
    if (!$json->Status) {
       return "";
    }
    if(strlen($json->Info)<2){
        return "";
    }
    return  "$json->Info||js:Loadjs('fw.network.interfaces.php?AsGateway-options=yes')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}

function NOTIF_NOT_CATEGORIZED():string{
    $page=CurrentPageName();

    $GoShieldNotCategorized=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldNotCategorized"));
    if($GoShieldNotCategorized==1){
        return "";
    }
    $GoShieldNotCategorizedSeen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoShieldNotCategorizedSeen"));
    if($GoShieldNotCategorizedSeen==1){
        return "";
    }
    $GoShieldServerEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    if($GoShieldServerEnable==0){
        return "";
    }
    return "{improve_website_categorization}||{improve_website_categorization2}||INFO||js:Loadjs('fw.ksrn.uncategorized.php?ask-js=yes')||js:Loadjs('$page?SetToken=GoShieldNotCategorizedSeen');";


}




function NOTIF_APP_GO_SHIELD_CRASHES():string{

    $GOSHIELD_CRASHES=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GOSHIELD_CRASHES"));
    if (strlen($GOSHIELD_CRASHES)<2){
            return "";
    }
    return "";
}



function NOTIFS_NGINX($UPDATES_ARRAY):array{
    $ERR                = array();

    $EnableNginx        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $NginxEmergency     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxEmergency"));
    if($EnableNginx==0) {return $ERR;}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return $ERR;}
    // La nouvelle version de nginx est une ligne de fw.updates.packages.php.
    if ($NginxEmergency == 1) {
        $ERR[] = "{nginx_emergency_mode}||js:Loadjs('fw.nginx.emergency.remove.php');";
    }

    return $ERR;
}
function CVE_2021_44142():array{
    $page=CurrentPageName();
    $Token              = "HideCVE202144142I";
    $CVE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CVE-2021-44142"));
    if($CVE==0){return array();}
    $HideToken = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if($HideToken==1){return array();}
    $STEXT="{security}:{APP_SAMBA} CVE-2021-44142";
    $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-samba.php')||DANGER||||js:Loadjs('$page?SetToken=$Token');";
    return $ERR;
}

function NOTIF_MAIN_APT_GET_JSON():string{
    $DisableOsSystemUpdate = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableOsSystemUpdate"));
    if ($DisableOsSystemUpdate == 1) {
        return "";
    }
    if(!function_exists("json_decode")) {
        return "";
    }

    $MAIN_APT_GET = json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MAIN_APT_GET_JSON"));
    if (json_last_error() > JSON_ERROR_NONE) {
       VERBOSE(json_last_error(),__LINE__);
        return "";
    }
    if(!property_exists($MAIN_APT_GET, "package_number")) {
        VERBOSE("property_exists package_number FALSE",__LINE__);
        return "";
    }
    $package_number=$MAIN_APT_GET->package_number;
    if($package_number==0){return "";}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/apt/status"));
    if(property_exists($json,"Pid")){
        if($json->Pid>0){
            $tpl=new template_admin();
            $info_apt_running=$tpl->_ENGINE_parse_body("{info_apt_running}");
            $info_apt_running=str_replace("%s",$json->minutes,$info_apt_running);
            return "$info_apt_running||||INFO";
        }
    }
    return "$package_number {system_packages_can_be_upgraded}||js:document.location.href='/os-update'||WARN";

}
function notifs_articarest_alive():bool{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ping"),true);
    return is_array($json) && !empty($json["Status"]);
}
function notifs_minibars_js($users,$tpl):string{
    // Dispo uniquement pour les administrateurs.
    if($users->AsProxyMonitor
        or $users->AsSystemAdministrator
        or $users->AsDebianSystem
        or $users->AsFirewallManager
        or $users->AsMailBoxAdministrator
        or $users->AsVPNManager){
        return $tpl->RefreshInterval_Loadjs("mini-bars", "fw.system.status.php", "top-cpu=yes");
    }
    return "";
}
function deb10_js():bool{
    $page=currentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{info_end_of_support}","$page?deb10-popup=yes");
}
function deb10_popup():bool{
    $tpl=new template_admin();
    $html[]=$tpl->div_success("Linux Debian 10||<p style='font-size:14px'>{info_end_of_support_debian10}</p>");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function LOCALES_NOT_COMPILED($ArticaRestStatus):string{

    if (!isset($ArticaRestStatus["LocalGenExecTime"])) {
        return "";
    }

    if (isset($ArticaRestStatus["LocalRunning"]) && $ArticaRestStatus["LocalRunning"]) {
        return "";
    }

    $LocalGenExecTime = 0;
    if (isset($ArticaRestStatus["LocalGenExecTime"])) {
        $LocalGenExecTime = intval($ArticaRestStatus["LocalGenExecTime"]);
    }

    if ($LocalGenExecTime == 0) {
        return "{LOCALES_NOT_COMPILED}||js:Loadjs('fw.system.locales.progress.php')";
    }

    $LOCALE = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));
    if ($LOCALE == null) {
        return "{LOCALES_NOT_DEFINED}||js:Loadjs('fw.system.locale.php')";
    }
    $search = ' UTF-8';
    if (preg_match("/$search/i", $LOCALE)) {
        return "{NEED_REBUILD_LOCALES}||js:Loadjs('fw.system.locale.php')";
    }
    return "";
}




function FOUND_AD_SERVER():string{
    $DisableAdDiscover=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAdDiscover"));
    if($DisableAdDiscover==1){
        return "";
    }
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==1){
        return "";
    }
    $DiscoveredAD=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DiscoveredAD"));
    VERBOSE("DiscoveredAD=$DiscoveredAD",__LINE__);
    if(strlen($DiscoveredAD)<4){
        return "";
    }
    $page=CurrentPageName();
    return  "{ad_discovered}||{ad_discovered}: <strong>$DiscoveredAD</strong>||MOREINFO||js:Loadjs('fw.activedirectory.discovered.php?js=yes');||js:Loadjs('$page?SetToken=DisableAdDiscover');";
}


function NEED_ZSWAP():string{
    $page=CurrentPageName();
    $EnableZram=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZram"));
    if($EnableZram==1){
        return "";
    }
    $Token="DisableCheckZSwap";
    $DisableCheckZSwap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if($DisableCheckZSwap==1){
        return "";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NeedZswap"),true);
    if(!isset($json["NeedzSwap"])){
        return "";
    }
    if($json["NeedzSwap"]==0){
        return "";
    }

    return "{memory_pressure}||{need_zswap}||DANGER||js:document.location.href='/system-memory'||js:Loadjs('$page?SetToken=$Token');";
}
function VM_DISK_SLOW():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $VM_DISK_SLOW=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VM_DISK_SLOW"));
    if($VM_DISK_SLOW==0){return VM_DISK_TWEAK();}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSlowStorage"))==1){return VM_DISK_TWEAK();}
    $VM_DISK_SLOW_DEVICE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VM_DISK_SLOW_DEVICE");
    $VM_DISK_SLOW_MESSAGE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VM_DISK_SLOW_MESSAGE");
    $Token="DisableSlowStorage";
    $STEXT=$tpl->_ENGINE_parse_body("{slow_storage_detected_on} $VM_DISK_SLOW_DEVICE");
    $ERR = "$STEXT||$VM_DISK_SLOW_MESSAGE||DANGER||js:Loadjs('fw.slow.storage.php')||js:Loadjs('$page?SetToken=$Token');";
    return $ERR;
}
function VM_DISK_TWEAK():string{
    $ERR="";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Token="DisableVMDiskOptimize";
    $DiskVMMustOptimize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DiskVMMustOptimize"));
    $DiskVMOptimized=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DiskVMOptimized"));
    if($DiskVMOptimized==1){return "";}
    if($DiskVMMustOptimize==0){return $ERR;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableVMDiskOptimize"))==1){return $ERR;}
    $optimizations_for_vm=$tpl->_ENGINE_parse_body("{optimizations_for_vm}");
    $ERR = "$optimizations_for_vm||{optimizations_for_vm_exp}||WARN||js:Loadjs('fw.slow.storage.php')||js:Loadjs('$page?SetToken=$Token');";
    return $ERR;
}