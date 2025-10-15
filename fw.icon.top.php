<?php
$start = microtime(true);
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
ExecTtime($start,__LINE__);
$start = microtime(true);
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
ExecTtime($start,__LINE__);
$start = microtime(true);
$GLOBALS["CLASS_SOCKETS"]=new sockets();
ExecTtime($start,__LINE__);
$start = microtime(true);
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.cpu.percent.inc");
$GLOBALS["LICENSE_EXPIRED"]=False;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){exit();}
ExecTtime($start,__LINE__);
$start = microtime(true);
clean_xss_deep();
ExecTtime($start,__LINE__);

if(isset($_GET["notifs"])){notifs();exit;}
if(isset($_GET["seen-updated"])){see_updated();exit;}
if(isset($_GET["SetToken"])){SetToken();exit;}
if(isset($_POST["SetToken"])){SetTokenConfirm();exit;}
if(isset($_GET["cronos"])){Cronos();exit;}
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
	$ID=$_GET["seen-updated"];
	$q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
	$q->QUERY_SQL("UPDATE history SET asseen=1 WHERE ID=$ID");
	header("content-type: application/x-javascript");
	echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
}
function Cronos():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $f[]="LoadAjaxSilent('artica-notifs-barr','$page?notifs=yes');";
    $HTMLTITLE=null;

    $FileCookyKey=md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"]);
    if(is_file("/etc/artica-postfix/settings/Daemons/$FileCookyKey.HTMLTITLE")){$HTMLTITLE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("$FileCookyKey.HTMLTITLE");}
    if(isset($_COOKIE["HTMLTITLE"])){$HTMLTITLE=$_COOKIE["HTMLTITLE"];}

    if(!is_null($HTMLTITLE)){
        $HTMLTITLE=trim($HTMLTITLE);
    }else{
        $HTMLTITLE="%s (%v)";
    }


    if(strpos("  $HTMLTITLE ", "%s")>0){
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $HTMLTITLE=str_replace("%s", $MyHostname, $HTMLTITLE);
    }
    if(strpos("  $HTMLTITLE ", "%v")>0){
        $MyHostname=trim(@file_get_contents("VERSION"));
        $HTMLTITLE=str_replace("%v", $MyHostname, $HTMLTITLE);
    }

    $HTMLTITLE=str_replace("'", "`", $HTMLTITLE);
    $f[]="document.title = '$HTMLTITLE'";

    $date=date("H:i:s");
    $f[]='$("#faclock").html("'.$date.'");';

    $CURRENT_CPU_AVG=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURRENT_CPU_AVG"));
    $RT_CPU_AVG=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURRENT_CPU_AVG"));
    if(floatval($RT_CPU_AVG)>0){
        $CURRENT_CPU_AVG=$RT_CPU_AVG;
    }
    VERBOSE("CURRENT_CPU_AVG = [$CURRENT_CPU_AVG]",__LINE__);
    $MEM_USED=explode(",",trim(@file_get_contents("/etc/artica-postfix/DASHBOARD_MEM_CUR")));
    $MEM_USED_PERC=intval($MEM_USED[0]);
    $cpu = floatval($CURRENT_CPU_AVG);
    if ($MEM_USED_PERC == 0) {
                $sock = new sockets();
                $data = $sock->REST_API("/system/status");

                $json = json_decode($data);
                if (json_last_error() > JSON_ERROR_NONE) {
                    writelogs("REST API: /system/status " . json_last_error_msg(), __FUNCTION__, __FILE__, __LINE__);

                }
                if (!$json->Status) {
                    writelogs("REST API: /system/status Return false!", __FUNCTION__, __FILE__, __LINE__);
                }
                $MEM_USED_PERC = $json->CurMemPrc;
            }
    VERBOSE("CPU PERCEN $cpu MEM PERC: $MEM_USED_PERC", __LINE__);
    $cpu_color = "text-muted";
            $mem_color = "text-muted";
            if ($MEM_USED_PERC > 70) {
                $mem_color = "text-primary";
            }
            if ($MEM_USED_PERC > 80) {
                $mem_color = "text-warning";
            }
            if ($MEM_USED_PERC > 90) {
                $mem_color = "text-danger";
            }

            if ($cpu > 70) {
                $cpu_color = "text-primary";
            }
            if ($cpu > 80) {
                $cpu_color = "text-warning";
            }
            if ($cpu > 90) {
                $cpu_color = "text-danger";
            }
        $docid="document.getElementById";
        $f[] = "function updateBars() {";
        $f[] = "const cpuUsage = '$cpu'";
        $f[] = "const memUsage = '$MEM_USED_PERC'";
        $f[] = "if( $docid('top-cpu-text') ){";
        $f[] = "    $docid('top-cpu-text').className='$cpu_color';";
        $f[] = "    $docid('top-ram-text').className='$mem_color';";
        $f[] = "    $docid('cpu-fill').style.width = cpuUsage + '%';";
        $f[] = "    $docid('mem-fill').style.width = memUsage + '%';";
        $f[] = "    $docid('cpu-percent').textContent = cpuUsage + '%';";
        $f[] = "    $docid('mem-percent').textContent = memUsage + '%';";
        $f[] = "    $docid('cpu-fill').style.backgroundColor = getColor(cpuUsage);";
        $f[] = "    $docid('mem-fill').style.backgroundColor = getColor(memUsage);";
        $f[] = "    }";
        $f[] = "if( $docid('dash-cpu-title') ){";
        $f[] = "    $docid('dash-cpu-title').textContent = cpuUsage + '%';";
        $f[] = "    }";
        $f[] = "}";
        $f[] = "function getColor(usage) {";
        $f[] = "if (usage < 50) return '#4caf50'; // Green";
        $f[] = "if (usage < 80) return '#ff9800'; // Orange";
        $f[] = "return '#f44336'; // Red";
        $f[] = "}";
        $f[]="updateBars()";

    echo @implode("\n",$f);
    return true;
}
function refresh_interval(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $html[]="Loadjs('$page?cronos=yes');";
    $html[]="LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
    $html[]="RefreshNotifs();";
    echo @implode("\n",$html);

}
function start():bool{
   $page=CurrentPageName();
   $tpl=new template_admin();
   header("content-type: application/x-javascript");
   echo $tpl->RefreshInterval_Loadjs("artica-notifs-barr",$page,"refresh-interval=yes",10);
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
    $PYTHON_CARE = true;
    $users          = new usersMenus();
    $GLOBALS["CLASS_USERS"]=$users;
    if(!$users->AsAnAdministratorGeneric){return null;}
    $tpl            = new template_admin();
    $LicenseINGP    = 0;
    $ERR            = array();
    $page           = CurrentPageName();
    $FINAL_TIME     = 0;
    $compiled       = php_uname('v');
    $certs = array();
    $UnboundEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

      if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
            $REVERSE_APPLIANCE=true;
      }
    $errorFile="/usr/share/artica-postfix/ressources/logs/NFQueue/error.txt";
    if(is_file($errorFile)) {
        $ERR[]=@file_get_contents($errorFile);
    }

   if($REVERSE_APPLIANCE){
        $SEE_SSHPROXY=false;
        $LIBCACHE_PERL=false;
        $PYTHON_CARE=false;
        $AD_CARE=false;
   }

    $php_error=_php_error();
    if($php_error<>null){
        $ERR[]=$php_error;
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

    $CHECK_FRAMEWORK=CHECK_FRAMEWORK();
    $hostname=php_uname('n');
    VERBOSE("RESOLV -> $hostname",__LINE__);
    $hostname_addr=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($hostname);
    VERBOSE("RESOLV -> $hostname == $hostname_addr",__LINE__);
    if($hostname_addr==$hostname){
        $ERR[]="{unable_to_resolve} &laquo;$hostname&raquo;||{PLEASE_ADD_IN_ETCHOSTS}||||js:window.location.href ='/hostfile'";

    }
    if(!$CHECK_FRAMEWORK){
        $ERR[] = "{FRAMEWORK_COM_ERROR}||{FRAMEWORK_COM_ERROR_EXPLAIN}||DANGER||js:Loadjs('fw.framework.error.php');";
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

    
    //$CRC32_INTERFACES_DB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CRC32_INTERFACES_DB");
    //$CRC32_INTERFACES_CURRENT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CRC32_INTERFACES_CURRENT");
    $GrubSectionSeen = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GrubSectionSeen"));
    $SSHProxySectionSeen = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHProxySectionSeen"));
    $BTMMPWarn = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BTMMPWarn"));
    $LicensingServerError=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicensingServerError"));

    if(strlen($LicensingServerError)>3){$ERR[] = "{license_server_inacc}||||||js:Loadjs('fw.system.watchdog.php?licenseserver-js=yes');";
    }


    if($BTMMPWarn>0){
        $BTMMPWarnText=$tpl->_ENGINE_parse_body("{BTMMPWarn}");
        $BTMMPWarnText=str_replace("%s",$BTMMPWarn,$BTMMPWarnText);
        $ERR[] = "{failedloginattempts}||$BTMMPWarnText||WARN||js:Loadjs('fw.system.watchdog.php?failedloginattempts-js=yes');||js:Loadjs('$page?SetToken=BTMMPWarn&value=0');";
    }

    /*
    if($CRC32_INTERFACES_CURRENT<>null){
        if($CRC32_INTERFACES_DB<>$CRC32_INTERFACES_CURRENT) {
            $ERR[] = "{apply_network_configuration}||{notif_netchange}||INFO||js:Loadjs('fw.network.apply.php');";
        }
    }
*/
    if(!$users->AsDockerWeb) {
        $HideBootManagerIco = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideBootManagerIco"));
        if ($HideBootManagerIco == 0) {
            if ($GrubSectionSeen == 0) {
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableGrubSkin", 1);
                $ERR[] = "{new_feature_notice}||{boot_manager}||INFO||js:Loadjs('fw.system.information.php?boot-manager-js&seen=yes');||js:Loadjs('$page?SetToken=HideBootManagerIco');";
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
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));


    if($Enablehacluster==1){
        $NOTIF_APP_HAPROXY_CLUSTER=NOTIF_APP_HAPROXY_CLUSTER($UPDATES_ARRAY);
        if($NOTIF_APP_HAPROXY_CLUSTER<>null){$ERR[]=$NOTIF_APP_HAPROXY_CLUSTER;}
        //APP_HAPROXY_CLUSTER
    }

    $NOTIF_X_TABLES_PACKAGE_INSTALLED=NOTIF_X_TABLES_PACKAGE_INSTALLED($UPDATES_ARRAY);
    if(count($NOTIF_X_TABLES_PACKAGE_INSTALLED)>0){
        foreach ($NOTIF_X_TABLES_PACKAGE_INSTALLED as $NGINX_NOTE){
            $ERR[]=$NGINX_NOTE;
        }
    }

    $NOTIF_AUTOFS=NOTIF_AUTOFS($UPDATES_ARRAY);
    if($NOTIF_AUTOFS<>null){$ERR[]=$NOTIF_AUTOFS;}

    $NOTIF_SAMBA=NOTIF_SAMBA($UPDATES_ARRAY);
    if($NOTIF_SAMBA<>null){$ERR[]=$NOTIF_SAMBA;}

    $NOTIF_APP_PROFTPD=NOTIF_APP_PROFTPD($UPDATES_ARRAY);
    if($NOTIF_APP_PROFTPD<>null){$ERR[]=$NOTIF_APP_PROFTPD;}

    $NOTIF_MONIT=NOTIF_MONIT($UPDATES_ARRAY);
    if($NOTIF_MONIT<>null){$ERR[]=$NOTIF_MONIT;}

    $NOTIF_NAGIOS=NOTIF_NAGIOS($UPDATES_ARRAY);
    if($NOTIF_NAGIOS<>null){$ERR[]=$NOTIF_NAGIOS;}

    $NOTIF_APP_OPENVPN=NOTIF_APP_OPENVPN($UPDATES_ARRAY);
    if($NOTIF_APP_OPENVPN<>null){$ERR[]=$NOTIF_APP_OPENVPN;}

    $NOTIF_APP_GO_SHIELD_VERSION=NOTIF_APP_GO_SHIELD_VERSION($UPDATES_ARRAY);
    if($NOTIF_APP_GO_SHIELD_VERSION<>null){$ERR[]=$NOTIF_APP_GO_SHIELD_VERSION;}

    $NOTIF_ADREST_VERSION=NOTIF_ADREST_VERSION($UPDATES_ARRAY);
    if($NOTIF_ADREST_VERSION<>null){$ERR[]=$NOTIF_ADREST_VERSION;}

     $NOTIF_APP_MYSQL_VERSION=NOTIF_APP_MYSQL_VERSION($UPDATES_ARRAY);
    if($NOTIF_APP_MYSQL_VERSION<>null){$ERR[]=$NOTIF_APP_MYSQL_VERSION;}

    $NOTIF_APP_MSKTUTIL_VERSION=NOTIF_APP_MSKTUTIL_VERSION($UPDATES_ARRAY);
    if($NOTIF_APP_MSKTUTIL_VERSION<>null){$ERR[]=$NOTIF_APP_MSKTUTIL_VERSION;}

    $NOTIF_APP_UFDBGUARD=NOTIF_APP_UFDBGUARD($UPDATES_ARRAY);
    if($NOTIF_APP_UFDBGUARD<>null){$ERR[]=$NOTIF_APP_UFDBGUARD;}

    $NOTIFS_UNBOUNDD=NOTIFS_UNBOUND($UPDATES_ARRAY);
    if($NOTIFS_UNBOUNDD<>null){$ERR[]=$NOTIFS_UNBOUNDD;}

    $NOTIF_APP_WAZHU_VERSION=NOTIF_APP_WAZHU_VERSION($UPDATES_ARRAY);
    if($NOTIF_APP_WAZHU_VERSION<>null){$ERR[]=$NOTIF_APP_WAZHU_VERSION;}

    $NOTIF_APP_ZABBIX_VERSION=NOTIF_APP_ZABBIX_VERSION($UPDATES_ARRAY);
    if($NOTIF_APP_ZABBIX_VERSION<>null){$ERR[]=$NOTIF_APP_ZABBIX_VERSION;}

    $NOTIF_NETDATA=NOTIF_NETDATA($UPDATES_ARRAY);
    if($NOTIF_NETDATA<>null){$ERR[]=$NOTIF_NETDATA;}

    $NOTIF_CLAMAV=NOTIF_CLAMAV($UPDATES_ARRAY);
    if($NOTIF_CLAMAV<>null){$ERR[]=$NOTIF_CLAMAV;}

    $NOTIFS_NGINX=NOTIFS_NGINX($UPDATES_ARRAY);
    if(count($NOTIFS_NGINX)>0){
        foreach ($NOTIFS_NGINX as $NGINX_NOTE){
            $ERR[]=$NGINX_NOTE;
        }
    }

    $NOTIFS_RDS_PROXY=NOTIFS_RDS_PROXY($UPDATES_ARRAY);
    if(count($NOTIFS_RDS_PROXY)>0){
        foreach ($NOTIFS_RDS_PROXY as $NGINX_NOTE){
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
    $CVE_2022_29155=CVE_2022_29155();
    if(count($CVE_2022_29155)>0){
        foreach ($CVE_2022_29155 as $NOTE){
            $ERR[]=$NOTE;
        }
    }

    VERBOSE("<hr>NOTIFS_SYSLOGD -----------------------------------------------",__LINE__);
    $NOTIFS_SYSLOGD=NOTIFS_SYSLOGD($UPDATES_ARRAY);
    if(count($NOTIFS_SYSLOGD)>0){
        foreach ($NOTIFS_SYSLOGD as $NOTE){
            $ERR[]=$NOTE;
        }
    }

    $NOTIFS_DNSDIST=NOTIFS_DNSDIST($UPDATES_ARRAY);
    if(count($NOTIFS_DNSDIST)>0){
        foreach ($NOTIFS_DNSDIST as $NOTE){
            $ERR[]=$NOTE;
        }
    }
    $NOTIFS_DNSDIST9=NOTIFS_DNSDIST9($UPDATES_ARRAY);
    if(strlen($NOTIFS_DNSDIST9)>10){
            $ERR[]=$NOTIFS_DNSDIST9;
        }

    $NOTIFS_APP_CROWDSEC=NOTIFS_APP_CROWDSEC($UPDATES_ARRAY);
    if(strlen($NOTIFS_APP_CROWDSEC)>10){
        $ERR[]=$NOTIFS_APP_CROWDSEC;
    }


    VERBOSE("<hr>NOTIFS_REDIS -----------------------------------------------",__LINE__);
    $NOTIFS_REDIS=NOTIFS_REDIS($UPDATES_ARRAY);
    if(count($NOTIFS_REDIS)>0){
        foreach ($NOTIFS_REDIS as $NOTE){
            $ERR[]=$NOTE;
        }
    }

    VERBOSE("<hr>NOTIF_WP_CLI -----------------------------------------------",__LINE__);
    $NOTIF_WP_CLI=NOTIF_WP_CLI($UPDATES_ARRAY);
    if(count($NOTIF_WP_CLI)>0){
        foreach ($NOTIF_WP_CLI as $NOTE){
            $ERR[]=$NOTE;
        }
    }

    $STOP_APP_NGINX_CONSOLE_WARN=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STOP_APP_NGINX_CONSOLE_WARN"));
    if($STOP_APP_NGINX_CONSOLE_WARN==0) {
        VERBOSE("<hr>NOTIFS_APP_NGINX_CONSOLE -----------------------------------------------", __LINE__);
        $NOTIFS_APP_NGINX_CONSOLE = NOTIFS_APP_NGINX_CONSOLE($UPDATES_ARRAY);
        if (count($NOTIFS_APP_NGINX_CONSOLE) > 0) {
            foreach ($NOTIFS_APP_NGINX_CONSOLE as $NOTE) {
                $ERR[] = $NOTE;
            }
        }
    }

    $NOTIFS_PDNS=NOTIFS_PDNS($UPDATES_ARRAY);
    if(count($NOTIFS_PDNS)>0){
        foreach ($NOTIFS_PDNS as $NOTE){
            $ERR[]=$NOTE;
        }
    }

    $NOTFS_CICAP=NOTIFS_CICAP($UPDATES_ARRAY);
    if(count($NOTFS_CICAP)>0){
        foreach ($NOTFS_CICAP as $NOTE){
            $ERR[]=$NOTE;
        }
    }


	if($users->SQUID_INSTALLED){
	    VERBOSE("NOTIFS_SQUID",__LINE__);
		$NOTIFS=$icon->NOTIFS_SQUID($UPDATES_ARRAY);
		if(count($NOTIFS)>0){
			foreach ($NOTIFS as $notif){
				$ERR[]=$notif;
			}
		}
	}

    if(!$users->AsDockerWeb) {
        $MIGRATION_STEP = MIGRATION_STEP();
        if ($MIGRATION_STEP <> null) {
            $ERR[] = $MIGRATION_STEP;
        }
    }
    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION<7) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/status"));
        $DEBIAN_VERSION = $json->DebianVersion;
    }


	$HideDebianIco=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideDebianIco"));
    $HideDebian10DnsDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideDebian10DnsDist"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableDNSDist==1){
         if($DEBIAN_VERSION<12){
            if($HideDebian10DnsDist==0){
                $SOFT_NO_LONGER_SUPPORT=$tpl->_ENGINE_parse_body("{SOFT_NO_LONGER_SUPPORT}");
                $SOFT_NO_LONGER_SUPPORT=str_replace("%p","{APP_DNSDIST}",$SOFT_NO_LONGER_SUPPORT);
                $SOFT_NO_LONGER_SUPPORT=str_replace("%deb","<strong>Debian 12</strong>",$SOFT_NO_LONGER_SUPPORT);
                $ERR[] = "{APP_DNSDIST}||$SOFT_NO_LONGER_SUPPORT||WARN||||js:Loadjs('$page?SetToken=HideDebian10DnsDist');";
            }
        }
    }

    if(!$users->AsDockerWeb) {
        if($DEBIAN_VERSION<10){
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
        $ArticaAutoUpateOfficial = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
        $ArticaAutoUpateNightly = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
        $ArticaDisablePatchs = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));
        $HideArticaAutoUpdateIco = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaAutoUpdateIco"));

        if ($HideArticaAutoUpdateIco == 0) {
            if ($users->AsSystemAdministrator) {
                $TEST_UPDATE = true;
                if ($ArticaAutoUpateOfficial == 0 and $ArticaAutoUpateNightly == 0 and $ArticaDisablePatchs == 1) {
                    $TEST_UPDATE = false;
                }

                if ($TEST_UPDATE) {
                    $Updates = 0;
                    $q = new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
                    if ($q->TABLE_EXISTS("system_schedules")) {
                        $ligne = $q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM system_schedules WHERE TaskType=83");
                        $Updates = intval($ligne["tcount"]);
                    }
                    if ($Updates == 0) {
                        $ERR[] = "{schedule}: {update_artica}||{no_update_artica}||INFO||js:window.location.href='/artica-tasks-update';||js:Loadjs('$page?SetToken=HideArticaAutoUpdateIco');";
                    }
                }
            }
        }


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
        if (!is_file("/etc/artica-postfix/settings/Daemons/ArticaAutoUpateOfficial")) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaAutoUpateOfficial", 1);
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
                $results = $q->QUERY_SQL("SELECT * FROM history WHERE asseen=0 ORDER BY updated DESC");
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

        if (isset($UPDATES_ARRAY["APP_XKERNEL"])) {
            $ERR[] = APP_XKERNEL();
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
    if($PostfixEnable==1) {
        if (!is_file("/usr/bin/mhonarc")) {
            $ERR[] = "{Warninmhonarc}||js:Loadjs('fw.php7.0-mhonarc.php')";
        }
    }

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

        if($PYTHON_CARE) {
            $python_error = python_error();
            if ($python_error <> null) {
                $ERR[] = $python_error;
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


        if (intval(@file_get_contents("/etc/artica-postfix/LOCAL_GEN_EXECUTED")) == 0) {

            $ERR[] = "{LOCALES_NOT_COMPILED}||js:Loadjs('fw.system.locales.progress.php')";
        } else {

            $LOCALE = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));
            if ($LOCALE == null) {
                $ERR[] = "{LOCALES_NOT_DEFINED}||js:Loadjs('fw.system.locale.php')";
            }
            $search = ' UTF-8';
            if(preg_match("/{$search}/i", $LOCALE)) {
                $ERR[] = "{NEED_REBUILD_LOCALES}||js:Loadjs('fw.system.locale.php')";
            }


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
    if(!$users->AsDockerWeb) {
        if (is_file("/home/artica/SQLITE/bugzilla.db")) {
            $q = new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
            $ligne2 = $q->mysqli_fetch_array("SELECT count(*) as tcount FROM `bugs`");
            if ($ligne2["tcount"] > 0) {
                $ERR[] = "{$ligne2["tcount"]} {tickets_not_closed}||js:LoadAjaxSilent('MainContent','fw.bugzilla.php');";
            }
        }
    }




$class_text="text-danger";
$icon=ico_emergency;
    $CC=0;
    foreach ($ERR as $error) {
        $error = trim($error);
        if ($error == null) {continue;}
        $CC++;
        }

if($CC==0){return;}

echo "
<a class=\"dropdown-toggle count-info\" data-toggle=\"dropdown\" href=\"#\">
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
		if(isset($LEVEL_AV[$LEVEL])){
		    if($LEVEL=="WARN"){
                $class_text="text-warning";
                $btn="btn-warning";
                $icon=ico_emergency;
            }


        }

        $hide=null;

		if(isset($ft[4])){
            $hideNotif=$ft[4];
            if(preg_match("#^js:(.+)#", $hideNotif,$fs)){$js2=$fs[1];}
            if(!is_null($js2)) {
                if (trim($js2) <> null) {
                    $hide = "<button style=\"text-transform: capitalize;\" 
                            class=\"btn btn-primary btn-xs\" type=\"button\" 
				OnClick=\"$js2\">" . $tpl->_ENGINE_parse_body("{hide}") .
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
		if(isset($ft[2])){if($ft[2]=="INFO"){
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
echo "</ul>";
echo "<script>\n";
echo "if(document.getElementById('WSUSOFFLINE-STATE') ){ LoadAjaxSilent('WSUSOFFLINE-STATE','fw.wsusoffline.php?status=yes');}";
    echo "if(document.getElementById('widget-hostname') ){ 
        LoadAjaxSilent('widget-hostname','fw.index.php?widget-hostname=yes');}";
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

function python_error():string{
    return "";
    $page=CurrentPageName();
	if(!is_file("/usr/bin/pip")){return "{WarningPythonPip}||js:Loadjs('fw.python-pip.php')";}
	if(!is_file("/home/artica/SQLITE/python-packages.db")){return "{WarningPythonCollection}||js:Loadjs('fw.python-collection.php')";}
	$q=new lib_sqlite("/home/artica/SQLITE/python-packages.db");
    $EnableElasticSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableElasticSearch"));
	$HidePythonElasticSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HidePythonElasticSearch"));
    if($EnableElasticSearch==0){$HidePythonElasticSearch=1;}

	if($HidePythonElasticSearch==0) {
        $ligne = $q->mysqli_fetch_array("SELECT package_version FROM python_packages WHERE package_name='elasticsearch'");
        if (strlen(trim($ligne["package_version"])) == 0) {
            return "{WarningPythonElasticSearch}||||js:Loadjs('fw.python-pip.install.php?package=elasticsearch')||DANGER||js:Loadjs('$page?SetToken=HidePythonElasticSearch');";
        }

    }
	$ligne=$q->mysqli_fetch_array("SELECT package_version FROM python_packages WHERE package_name='python-memcached'");
	if(strlen(trim($ligne["package_version"]))==0){
		return "{WarningPythonMemCached}||js:Loadjs('fw.python-pip.install.php?package=python-memcached')";
	}

return "";
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
function MIGRATION_STEP():string{
    if(!is_file("/usr/share/artica-postfix/fw.license.migration.php")) {return "";}
    $Migration = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Migration"));
    if($Migration == 1){return "";}
    return  "{license_migration_title}||{license_migration_body}||DANGER||js:Loadjs('fw.license.migration.php');";
}
function NOTIF_HOTFIX_OFF():string{
    $sock=new sockets();
    $sock->REST_API("/system/artica/latestHotfix");
    $page=CurrentPageName();
    $UPDATE_TO_HOTFIX=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UPDATE_TO_HOTFIX");
    if (strlen($UPDATE_TO_HOTFIX)<5){
        return "";
    }
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
function APP_XKERNEL():string{
    $HideArticaXTNDPIco=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaXTNDPIco"));
    $NEEDRESTART=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NEEDRESTART"));
    if($NEEDRESTART==1){
        return "";
    }

    if($HideArticaXTNDPIco==1){return "";}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/xtables"));
    if($json->Status){return "";}

    if(!property_exists($json,"Version")) {return "";}
    $kernver=$json->Version;
    $page       = CurrentPageName();
    $tpl=new template_admin();

    $def    = $tpl->_ENGINE_parse_body("{kernel_modules_update_explain}");
    $def    = str_replace("%s","$kernver",$def);

    return "{kernel_modules_update}||$def||WARN||js:Loadjs('fw.system.upgrade-software.php?product=APP_XTABLES');||js:Loadjs('$page?SetToken=HideArticaXTNDPIco');";
}

function NOTIF_APP_PROFTPD($UPDATES_ARRAY){
    $tphp = "fw.system.upgrade-software.php";
    $EnableProFTPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProFTPD"));
    if($EnableProFTPD==0){return "";}
    $HideNewVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewProftpdVer"));
    if($HideNewVer==1){return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}
    $tpl                = new template_admin();
    $page               = CurrentPageName();

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_PROFTPD",
            "TOKEN_VER"=>"ProFTPDVersion",
            "TOKEN_ENABLED"=>"EnableProFTPD")
    );


    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }

    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_PROFTPD}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_PROFTPD')||WARN||||js:Loadjs('$page?SetToken=$Token');";


}
function NOTIF_APP_HAPROXY_CLUSTER($UPDATES_ARRAY):string{
    $tphp = "fw.system.upgrade-software.php";
    $HideNewVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewHaclusterVer"));
    if($HideNewVer==1){return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $AVAILABLE_VER      = $tpl->HAPROXY_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER == 0) {
        VERBOSE("APP_HAPROXY_CLUSTER AVAILABLE_VER=$AVAILABLE_VER",__LINE__);
        return "";}

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $NewVer=$UPDATES_ARRAY["APP_HAPROXY"][$AVAILABLE_VER]["VERSION"];
    VERBOSE("APP_HAPROXY New version: $NewVer",__LINE__);
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_HAPROXY_CLUSTER}", $STEXT);
    $STEXT = str_replace("%ver", $master_version, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    return "$STEXT||js:Loadjs('$tphp?product=APP_HAPROXY')||WARN||||js:Loadjs('$page?SetToken=HideNewHaclusterVer');";


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

function NOTIF_SAMBA($UPDATES_ARRAY):string{

    $Installed= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_INSTALLED"));
    if($Installed==0){
        return "";
    }
    $tphp = "fw.system.upgrade-software.php";
    $HideNewVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewSambaVer"));
    if($HideNewVer==1){return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}
    $MAIN_KEY="APP_SAMBA";
    if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return "";}

    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $AVAILABLE_VER      = $tpl->SAMBA_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);

    if ($AVAILABLE_VER == 0) {
        VERBOSE("Samba AVAILABLE_VER=$AVAILABLE_VER",__LINE__);
        return "";
    }

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    $NewVer=$UPDATES_ARRAY[$MAIN_KEY][$AVAILABLE_VER]["VERSION"];
    VERBOSE("New version: $NewVer",__LINE__);
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{".$MAIN_KEY."}", $STEXT);
    $STEXT = str_replace("%ver", $master_version, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    return "$STEXT||js:Loadjs('$tphp?product=$MAIN_KEY')||WARN||||js:Loadjs('$page?SetToken=HideNewSambaVer');";
}
function NOTIF_AUTOFS($UPDATES_ARRAY):string{

    $AutoFSInstalled= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSInstalled"));
    if($AutoFSInstalled==0){
        return "";
    }
    $tphp = "fw.system.upgrade-software.php";
    $HideNewVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewAutoFSVer"));
    if($HideNewVer==1){return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}
    $MAIN_KEY="APP_AUTOFS";
    if(!isset($UPDATES_ARRAY[$MAIN_KEY])){return "";}

    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $AVAILABLE_VER      = $tpl->AUTOFS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);

    if ($AVAILABLE_VER == 0) {
        VERBOSE("AutoFS AVAILABLE_VER=$AVAILABLE_VER",__LINE__);
        return "";
    }

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_AUTOFS_VERSION");
    $NewVer=$UPDATES_ARRAY[$MAIN_KEY][$AVAILABLE_VER]["VERSION"];
    VERBOSE("New version: $NewVer",__LINE__);
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{".$MAIN_KEY."}", $STEXT);
    $STEXT = str_replace("%ver", $master_version, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    return "$STEXT||js:Loadjs('$tphp?product=$MAIN_KEY')||WARN||||js:Loadjs('$page?SetToken=HideNewAutoFSVer');";
}
function NOTIF_MONIT($UPDATES_ARRAY):string{
    $tphp = "fw.system.upgrade-software.php";
    $HideNewVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewMonitVer"));
    if($HideNewVer==1){return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $AVAILABLE_VER      = $tpl->MONIT_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER == 0) {
        VERBOSE("Monit AVAILABLE_VER=$AVAILABLE_VER",__LINE__);
        return "";}

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MONIT_VERSION");
    $NewVer=$UPDATES_ARRAY["APP_MONIT"][$AVAILABLE_VER]["VERSION"];
    VERBOSE("New version: $NewVer",__LINE__);
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_MONIT}", $STEXT);
    $STEXT = str_replace("%ver", $master_version, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    return "$STEXT||js:Loadjs('$tphp?product=APP_MONIT')||WARN||||js:Loadjs('$page?SetToken=HideNewMonitVer');";
}
function NOTIF_NAGIOS($UPDATES_ARRAY):string{
    $EnableDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNagiosClient"));

    if($EnableDaemon==0){
        VERBOSE("EnableDaemon=$EnableDaemon",__LINE__);
        return "";
    }


    $tphp = "fw.system.upgrade-software.php";
    $HideNewVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewNagiosVer"));
    if($HideNewVer==1){return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $AVAILABLE_VER      = $tpl->NAGIOS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER == 0) {
        VERBOSE("Nagios AVAILABLE_VER=$AVAILABLE_VER",__LINE__);
        return "";}

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_VERSION");
    $NewVer=$UPDATES_ARRAY["APP_NAGIOS_CLIENT"][$AVAILABLE_VER]["VERSION"];
    VERBOSE("New version: $NewVer",__LINE__);
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_NAGIOS_CLIENT}", $STEXT);
    $STEXT = str_replace("%ver", $master_version, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    return "$STEXT||js:Loadjs('$tphp?product=APP_NAGIOS_CLIENT')||WARN||||js:Loadjs('$page?SetToken=HideNewNagiosVer');";
}
function NOTIF_NETDATA($UPDATES_ARRAY){
    $tphp = "fw.system.upgrade-software.php";
    $HideNewVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewNetDataVer"));
    if($HideNewVer==1){return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}
    $EnableDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDATAEnabled"));

    if($EnableDaemon==0){
        VERBOSE("EnableDaemon=$EnableDaemon",__LINE__);
        return "";
    }
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $AVAILABLE_VER      = $tpl->NETDATA_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER == 0) {
        VERBOSE("Netdata AVAILABLE_VER=$AVAILABLE_VER",__LINE__);
        return "";}
    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NETDATA_VERSION");
    $NewVer=$UPDATES_ARRAY["APP_NETDATA"][$AVAILABLE_VER]["VERSION"];
    VERBOSE("New version: $NewVer",__LINE__);
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_NETDATA}", $STEXT);
    $STEXT = str_replace("%ver", $master_version, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    return "$STEXT||js:Loadjs('$tphp?product=APP_NETDATA')||WARN||||js:Loadjs('$page?SetToken=HideNewNetDataVer');";


}


function NOTIF_CLAMAV($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}
    $EnableClamavDaemon = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavDaemon"));
    if($EnableClamavDaemon==0){return "";}
    $HideNewClamdVer    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideNewClamdVer"));
    if($HideNewClamdVer==1){return "";}
    $AVAILABLE_VER      = $tpl->CLAMAV_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER == 0) { return "";}
    $NewVer=$UPDATES_ARRAY["APP_CLAMAV"][$AVAILABLE_VER]["VERSION"];
    return "{APP_AV_SCANNER_UPDATE} $NewVer||{APP_AV_SCANNER_UPDATE_TEXT}||WARN||js:Loadjs('fw.system.upgrade-software.php?product=APP_CLAMAV');||js:Loadjs('$page?SetToken=HideNewClamdVer');";
}


function NOTIFS_RDS_PROXY($UPDATES_ARRAY):array{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $ERR = array();

    $tphp = "fw.system.upgrade-software.php";
    $EnableRDPProxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"));
    if ($EnableRDPProxy == 0) {
        return $ERR;
    }
    if (!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {
        return $ERR;
    }
    $APP_RDPPROXY_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");



    $Token = "HideCiCAPNewVerIco$APP_RDPPROXY_VERSION";
    $HideNewVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewVer == 1) {
        return $ERR;
    }
    $AVAILABLE_VER = $tpl->PRDPROXY_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER > 0){
        $NewVer = $UPDATES_ARRAY["APP_RDPPROXY"][$AVAILABLE_VER]["VERSION"];
        $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
        $STEXT = str_replace("%product", "{APP_RDPPROXY}", $STEXT);
        $STEXT = str_replace("%ver", $APP_RDPPROXY_VERSION, $STEXT);
        $STEXT = str_replace("%next", $NewVer, $STEXT);
        $ERR[] = "$STEXT||js:Loadjs('$tphp?product=APP_RDPPROXY')||WARN||||js:Loadjs('$page?SetToken=$Token');";

    }
    return $ERR;
}
function NOTIFS_SYSLOGD($UPDATES_ARRAY):array{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $ERR = array();
    $tphp = "fw.system.upgrade-software.php";

    if (!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {
        return $ERR;
    }
    $APP_RSYSLOGD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION");
    VERBOSE("APP_RSYSLOGD_VERSION = $APP_RSYSLOGD_VERSION",__LINE__);
    $Token = "HideSyslogdNewVerIco$APP_RSYSLOGD_VERSION";
    $HideNewCicapVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewCicapVer == 1) {
        return $ERR;
    }

    $AVAILABLE_VER = $tpl->SYSLOG_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER > 0){
        $NewVer = $UPDATES_ARRAY["APP_SYSLOGD"][$AVAILABLE_VER]["VERSION"];
        $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
        $STEXT = str_replace("%product", "{APP_RSYSLOG}", $STEXT);
        $STEXT = str_replace("%ver", $APP_RSYSLOGD_VERSION, $STEXT);
        $STEXT = str_replace("%next", $NewVer, $STEXT);
        $ERR[] = "$STEXT||js:Loadjs('$tphp?product=APP_SYSLOGD')||WARN||||js:Loadjs('$page?SetToken=$Token');";

    }
    return $ERR;
}
function NOTIFS_CICAP($UPDATES_ARRAY):array{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $ERR = array();

    $tphp = "fw.system.upgrade-software.php";
    $CicapEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
    if ($CicapEnabled == 0) {
        return $ERR;
    }
    if (!$GLOBALS["CLASS_USERS"]->AsSquidAdministrator) {
        return $ERR;
    }
    $CicapVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");
    $Token = "HideCiCAPNewVerIco$CicapVersion";
    $HideNewCicapVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewCicapVer == 1) {
        return $ERR;
    }
    $AVAILABLE_VER = $tpl->CICAP_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER > 0){
        $NewVer = $UPDATES_ARRAY["APP_C_ICAP"][$AVAILABLE_VER]["VERSION"];
        $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
        $STEXT = str_replace("%product", "{APP_C_ICAP}", $STEXT);
        $STEXT = str_replace("%ver", $CicapVersion, $STEXT);
        $STEXT = str_replace("%next", $NewVer, $STEXT);
        $ERR[] = "$STEXT||js:Loadjs('$tphp?product=APP_C_ICAP')||WARN||||js:Loadjs('$page?SetToken=$Token');";

    }
    return $ERR;
}


//
function NOTIF_WP_CLI($UPDATES_ARRAY){
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement"));
    if($EnableSoft==0) {return $ERR;}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return $ERR;}
    $WP_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WP_CLIENT_VERSION");
    $Token              = "HideWpCliNewVerIco$WP_CLIENT_VERSION";
    $HideNewVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewVer == 1) {return $ERR;}
    $AVAILABLE_VER = $tpl->WP_CLI_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER ==0 ) {return $ERR;}
    $NewVer = $UPDATES_ARRAY["WP_CLIENT"][$AVAILABLE_VER]["VERSION"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{WP_CLIENT}", $STEXT);
    $STEXT = str_replace("%ver", $WP_CLIENT_VERSION, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=WP_CLIENT')||WARN||||js:Loadjs('$page?SetToken=$Token');";
    return $ERR;
}
function NOTIFS_APP_NGINX_CONSOLE($UPDATES_ARRAY):array{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();

    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return $ERR;}
    $CurrentVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION");
    $Token              = "HideArticaNginxNewVerIco$CurrentVer";
    $HideNewVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewVer == 1) {return $ERR;}
    $AVAILABLE_VER = $tpl->APP_NGINX_CONSOLE_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER ==0 ) {return $ERR;}
    $NewVer = $UPDATES_ARRAY["APP_NGINX_CONSOLE"][$AVAILABLE_VER]["VERSION"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_ARTICAWEBCONSOLE}", $STEXT);
    $STEXT = str_replace("%ver", $CurrentVer, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_NGINX_CONSOLE')||WARN||||js:Loadjs('$page?SetToken=$Token');";
    return $ERR;
}

function NOTIFS_REDIS($UPDATES_ARRAY):array{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
    if($EnableSoft==0) {return $ERR;}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return $ERR;}
    $APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
    $Token              = "HideRedisNewVerIco$APP_REDIS_SERVER_VERSION";
    $HideNewVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewVer == 1) {return $ERR;}
    $AVAILABLE_VER = $tpl->REDIS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
    if ($AVAILABLE_VER ==0 ) {return $ERR;}
    $NewVer = $UPDATES_ARRAY["APP_REDIS_SERVER"][$AVAILABLE_VER]["VERSION"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_REDIS_SERVER}", $STEXT);
    $STEXT = str_replace("%ver", $APP_REDIS_SERVER_VERSION, $STEXT);
    $STEXT = str_replace("%next", $NewVer, $STEXT);
    $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_REDIS_SERVER')||WARN||||js:Loadjs('$page?SetToken=$Token');";
    return $ERR;
}
function NOTIFS_APP_CROWDSEC($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
    if($EnableSoft==0) {return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsDnsAdministrator) {return "";}


    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_CROWDSEC",
            "TOKEN_VER"=>"APP_CROWDSEC_VERSION",
            "TOKEN_ENABLED"=>"EnableCrowdSec")
    );


    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }

    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_CROWDSEC}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_CROWDSEC')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}
function NOTIFS_DNSDIST9($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableSoft==0) {return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsDnsAdministrator) {return "";}


    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_DNSDIST9",
            "TOKEN_VER"=>"APP_DNSDIST_VERSION",
            "TOKEN_ENABLED"=>"EnableDNSDist")
    );


    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }

    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_DNSDIST9}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_DNSDIST9')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}
function NOTIFS_DNSDIST($UPDATES_ARRAY){
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableSoft==0) {return $ERR;}
    if(!$GLOBALS["CLASS_USERS"]->AsDnsAdministrator) {return $ERR;}
    $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $Token              = "HideDNSDISTNewVerIco$APP_DNSDIST_VERSION";
    //

    $HideNewNginxVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewNginxVer == 0) {
        $AVAILABLE_VER = $tpl->DNSDIST_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
        if ($AVAILABLE_VER >0 ) {
            $NewVer = $UPDATES_ARRAY["APP_DNSDIST"][$AVAILABLE_VER]["VERSION"];
            $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
            $STEXT = str_replace("%product", "{APP_DNSDIST}", $STEXT);
            $STEXT = str_replace("%ver", $APP_DNSDIST_VERSION, $STEXT);
            $STEXT = str_replace("%next", $NewVer, $STEXT);
            $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_DNSDIST')||WARN||||js:Loadjs('$page?SetToken=$Token');";
        }
    }

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

function NOTIFS_UNBOUND($UPDATES_ARRAY){
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($EnableSoft==0) {return $ERR;}
    if(!$GLOBALS["CLASS_USERS"]->AsDnsAdministrator) {return $ERR;}



    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_UNBOUND",
            "TOKEN_VER"=>"UnboundVersion",
            "TOKEN_ENABLED"=>"UnboundEnabled")
    );


    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }

    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_UNBOUND}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_UNBOUND')||WARN||||js:Loadjs('$page?SetToken=$Token');";


}

function NOTIF_APP_UFDBGUARD($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}


    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_UFDBGUARDD",
            "TOKEN_VER"=>"UFDBDaemonVersion",
            "TOKEN_ENABLED"=>"EnableUfdbGuard")
    );


    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }

    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_UFDBGUARDD}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_UFDBGUARDD')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}

function NOTIF_APP_ZABBIX_VERSION($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZabbixAgent"));
    if($EnableSoft==0) {return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_ZABBIX_AGENT",
            "TOKEN_VER"=>"APP_ZABBIX_AGENT_VERSION",
            "TOKEN_ENABLED"=>"EnableZabbixAgent")
    );

    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }
    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_ZABBIX_AGENT}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_ZABBIX_AGENT')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}

function NOTIF_APP_WAZHU_VERSION($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();
    $EnableSoft         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWazhuCLient"));
    if($EnableSoft==0) {return "";}
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_WAZHU",
            "TOKEN_VER"=>"APP_WAZHU_VERSION",
            "TOKEN_ENABLED"=>"EnableWazhuCLient")
    );

    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }
    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_WAZHU}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_WAZHU')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}
function NOTIF_ADREST_VERSION($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"SQUID_AD_RESTFULL",
            "TOKEN_VER"=>"ARTICAREST_VERSION",
            )
    );

    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }
    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{SQUID_AD_RESTFULL}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=SQUID_AD_RESTFULL')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}
function NOTIF_APP_MSKTUTIL_VERSION($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_MSKTUTIL",
            "TOKEN_VER"=>"MSKTUTIL_VERSION",
            "TOKEN_ENABLED"=>"MSKTUTIL_INSTALLED")
    );

    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }
    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_MSKTUTIL}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_MSKTUTIL')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}



function NOTIF_APP_MYSQL_VERSION($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_MYSQL",
            "TOKEN_VER"=>"APP_MYSQL_VERSION",
            "TOKEN_ENABLED"=>"EnableMySQL")
    );

    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }
    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_MYSQL}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_MYSQL')||WARN||||js:Loadjs('$page?SetToken=$Token');";
}
function NOTIF_APP_OPENVPN($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $EnableOpenVPNServer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNServer"));
    if ($EnableOpenVPNServer == 0) {
        return "";
    }
    if (!$GLOBALS["CLASS_USERS"]->AsVPNManager) {
        return "";
    }
    if (!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {
        return "";
    }

    $RESULTS = $tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY" => $UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY" => "APP_OPENVPN",
            "TOKEN_VER" => "OpenVPNVersion",
            "TOKEN_ENABLED" => "EnableOpenVPNServer")
    );

    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }
    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_OPENVPN}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_OPENVPN')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}
function NOTIF_APP_GO_SHIELD_CRASHES():string{

    $GOSHIELD_CRASHES=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GOSHIELD_CRASHES"));
    if (strlen($GOSHIELD_CRASHES)<2){
            return "";
    }
    return "";
}

function NOTIF_APP_GO_SHIELD_VERSION($UPDATES_ARRAY):string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return "";}

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_GO_SHIELD_SERVER",
            "TOKEN_VER"=>"APP_GO_SHIELD_VERSION",
            "TOKEN_ENABLED"=>"Go_Shield_Server_Enable")
    );

    if(!isset($RESULTS["NEW_VER"])){
        return "";
    }
    $Token=$RESULTS["HIDE_TOKEN"];
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{GO_SHIELD_SERVER}", $STEXT);
    $STEXT = str_replace("%ver", $RESULTS["CUR_VER"], $STEXT);
    $STEXT = str_replace("%next", $RESULTS["NEW_VER"], $STEXT);
    return  "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_GO_SHIELD_SERVER')||WARN||||js:Loadjs('$page?SetToken=$Token');";

}

function NOTIFS_PDNS($UPDATES_ARRAY):array{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();
    $EnableFeature         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
    if($EnableFeature==0) {return $ERR;}
    if(!$GLOBALS["CLASS_USERS"]->AsDnsAdministrator) {return $ERR;}
    $PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");
    $Token              = "HidePDNSNewVerIco$PDNS_VERSION";

    $HideNewNginxVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewNginxVer == 0) {
        $AVAILABLE_VER = $tpl->PDNS_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
        if ($AVAILABLE_VER >0 ) {
            $NewVer = $UPDATES_ARRAY["APP_PDNS"][$AVAILABLE_VER]["VERSION"];
            $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
            $STEXT = str_replace("%product", "{APP_PDNS}", $STEXT);
            $STEXT = str_replace("%ver", $PDNS_VERSION, $STEXT);
            $STEXT = str_replace("%next", $NewVer, $STEXT);
            $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_PDNS')||WARN||||js:Loadjs('$page?SetToken=$Token');";
        }
    }


    return $ERR;
}
function  NOTIF_X_TABLES_PACKAGE_INSTALLED($UPDATES_ARRAY):array{
    $tpl                = new template_admin();
    $ERR                = array();
    $KERNEL             = php_uname('r');
    $page               = CurrentPageName();
    $Token              = "HidexTablesNewVerIco$KERNEL";

    $HideToken = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if($HideToken==1){return $ERR;}
    VERBOSE("Kernel Version = $KERNEL",__LINE__);

    if(is_file("/lib/modules/$KERNEL/extra/xt_ndpi.ko")){return $ERR;}
    VERBOSE("/lib/modules/$KERNEL/extra/xt_ndpi.ko... Is there a new one ?",__LINE__);
    if(!isset($UPDATES_ARRAY["APP_XTABLES"])){
        VERBOSE("UPDATES_ARRAY[APP_XTABLES]) not set.",__LINE__);
        return $ERR;
    }

    $KERNEL=str_replace("-amd64","",$KERNEL);
    foreach ($UPDATES_ARRAY["APP_XTABLES"] as $index=>$main){
        $VERSION=$main["VERSION"];
        VERBOSE("$VERSION <> $KERNEL ?",__LINE__);
        if($VERSION<>$KERNEL){continue;}
        VERBOSE("Key:$index : $VERSION",__LINE__);
        $STEXT=$tpl->_ENGINE_parse_body("{APP_NDPI_NEW}");
        $STEXT=str_replace("%s",$KERNEL,$STEXT);
        $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_XTABLES&filter-key=$index')||WARN||||js:Loadjs('$page?SetToken=$Token');";
        return $ERR;

    }

    return $ERR;
}

function NOTIFS_NGINX($UPDATES_ARRAY):array{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $ERR                = array();

    $EnableNginx        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $NginxEmergency     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxEmergency"));
    $NGINX_MIGRATION_440= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_MIGRATION_440"));
    if($EnableNginx==0) {return $ERR;}




    if(!$GLOBALS["CLASS_USERS"]->AsSystemAdministrator) {return $ERR;}
    $APP_NGINX_VERSION  = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    $Token              = "HideNgInxNewVerIco$APP_NGINX_VERSION";

    $HideNewNginxVer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if ($HideNewNginxVer == 0) {
        $AVAILABLE_VER = $tpl->NGINX_LATEST_AVAILABLE_VERSION($UPDATES_ARRAY);
        if ($AVAILABLE_VER >0 ) {
            $NewVer = $UPDATES_ARRAY["APP_NGINX"][$AVAILABLE_VER]["VERSION"];
            $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
            $STEXT = str_replace("%product", "{APP_NGINX}", $STEXT);
            $STEXT = str_replace("%ver", $APP_NGINX_VERSION, $STEXT);
            $STEXT = str_replace("%next", $NewVer, $STEXT);
            $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-software.php?product=APP_NGINX')||WARN||||js:Loadjs('$page?SetToken=$Token');";
        }
    }
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

function CVE_2022_29155():array{
    $page=CurrentPageName();
    $Token              = "HideCVE202229155I";
    $CVE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CVE-2022-29155"));
    if($CVE==0){return array();}
    $HideToken = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token));
    if($HideToken==1){return array();}
    $STEXT="{security}:{APP_OPENLDAP} CVE-2022-29155";
    $ERR[] = "$STEXT||js:Loadjs('fw.system.upgrade-openldap.php')||DANGER||||js:Loadjs('$page?SetToken=$Token');";
    return $ERR;

}
function CHECK_FRAMEWORK():bool{
    $phpver=explode(".",phpversion());
    $MAJOR=$phpver[0];
    $MINOR=$phpver[1];
    if($MAJOR>6){
        if($MINOR>1){
            $hollodotme=true;
        }
    }

    if(!$hollodotme) {
        if(!is_file("/usr/bin/php-cgi")){
           return false;
        }
        return true;
    }

    include_once (dirname(__FILE__)."/ressources/class.framework.inc");
    $frame=new fcgi_framework("index.php");
    $results=$frame->Get();
    $OK=true;
    if(!$frame->ok){
        VERBOSE("CHECK_FRAMEWORK ->index.php ERROR $frame->IOERROR",__LINE__);
        $OK=false;
    }
    if(!preg_match("#<OK>#",$results)){
        $OK=false;
    }
    return $OK;
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
