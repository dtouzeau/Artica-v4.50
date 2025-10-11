<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
define("syslogSuff", base64_decode("QVJUSUNBX0xJQ0VOU0U="));
define("kcloudSyskey",base64_decode("S1NSTl9MSUNFTlNF"));
define("kreload",base64_decode("L3Vzci9zYmluL3NxdWlkIC1mIC9ldGMvc3F1aWQzIC1rIHJlY29uZmlndXJl"));
if (!file_exists('/etc/artica-postfix/settings/Daemons/NewLicServer')) {
    @touch("/etc/artica-postfix/settings/Daemons/NewLicServer");
}
$GLOBALS["isRegistered"]        = @file_get_contents('/etc/artica-postfix/settings/Daemons/NewLicServer');
$GLOBALS["CLASS_SOCKETS"]       = new sockets();
$GLOBALS["observations"]        = null;
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if (isset($argv[2])) {
    if ($argv[2]=="--verbose") {
        $GLOBALS["observations"]=null;
    }
    elseif ($argv[2]=="--force") {
        $GLOBALS["observations"]=null;
    } else {
        $GLOBALS["observations"]=$argv[2];
    }
}
if ($argv[1]=="--kinfo") {
    kinfo();
    exit();
}

if ($argv[1]=="--ktrial") {
    ktrial();
    exit();
}
if ($argv[1]=="--perms") {
    perms();
    exit();
}
$GLOBALS["MYPID"]=getmypid();

$unix=new unix();
$uptime=$unix->ServerRunSince();
if (is_file("/etc/artica-postfix/FROM_ISO")) {
    if ($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1) {
        echo "/etc/artica-postfix/FROM_ISO Time less than 1mn\n";
        exit();
    }
}
echo "Server running since ".$uptime." minutes...\n";
if (!$GLOBALS["FORCE"]) {
    if ($uptime < 5) {
        echo "Server run since " . $uptime . " minutes ( need 5mn, restart later)\n";
        build_progress_influx("Server run since " . $uptime . " minutes ( need 5mn, restart later)", 110);
        build_progress("Server run since " . $uptime . " minutes ( need 5mn, restart later)", 110);
        exit();
    }
}

function ktrial($noprgress=false):bool{
    $unix       = new unix();
    $licexpir   = base64_decode("e2xpY2Vuc2VfZXhwaXJlZH0=");
    $MsgFCCS    = base64_decode("RmFpbGVkIHRvIGNvbnRhY3QgY2xvdWQgc2VydmVy");
    $cmdADD     = null;
    $kcloudCur  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO(kcloudSyskey));

    if ($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()) {
        $kInfos["status"] = "{gold_license}";
        $kInfos["expire"] = 0;
        $kInfos["ispaid"] = 1;
        $kInfos["enable"] = 1;
        $kInfos["reseller"] = null;
        $kInfos["TIME"] = strtotime('+10 years');
        $kInfos["FINAL_TIME"] = 0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
        build_progress("{success}", 100);
        krsync($kcloudCur,$kInfos["enable"]);
        return true;
    }


    if ($GLOBALS['isRegistered']==1) {
        $SERVER_INFO = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        echo "Checking license on the cloud server in normal mode\n";
        if (!$noprgress) {build_progress("Ping the cloud server...", 40);}

        echo "[" . __LINE__ . "] Contacting api/k/trial\n";
        if (!isset($SERVER_INFO["X-API-KEY"])) {$SERVER_INFO["X-API-KEY"]=null;}
        $SERVER_INFO["observations"]=$GLOBALS["observations"];


        $curl = new ccurl("https://licensing.artica.center/api/k/trial", false, null);
        $curl->parms["X-API-KEY"] = $SERVER_INFO["X-API-KEY"];
        $curl->parms["SERVER_INFO"] = base64_encode(serialize($SERVER_INFO));
        $curl->NoLocalProxy();

        if(!$curl->get()){
            $unix->ToSyslog("License info: Request engine failed with error: [$curl->error] Err.".__LINE__,false,syslogSuff);
            build_progress("Request engine failed with error [$curl->error]", 110);
            return false;
        }

        $response = json_decode(getLastLines($curl->orginal_data));

        if (!property_exists($response, "status")) {
            $unix->ToSyslog("{KSRN} info: Communication error status is not a property Err.".__LINE__, false, syslogSuff);
            build_progress("{license_server_error}: Communication error status is not a property...", 110);
            $t=explode("\n",$curl->orginal_data);
            foreach ($t as $line){
                $line=trim($line);
                if($line==null){continue;}
                echo $line."\n";
            }
            return false;
        }



        if (!$response->status) {
            $unix->ToSyslog("License info: $MsgFCCS Err.".__LINE__, false, syslogSuff);
            echo "**********************************\n";
            echo "*\n";
            echo "* $MsgFCCS..*\n";
            echo "*\n";
            echo "**********************************\n";
            $Msg=base64_decode("SW52YWxpZCBhcGkga2V5");
            if ($response->message=="$Msg") {
                echo "***** $Msg ****\n";
                $kInfos["status"] = "$Msg";
                $kInfos["expire"] = 0;
                $kInfos["ispaid"] = 0;
                $kInfos["enable"] = 0;
                $kInfos["reseller"] = null;
                $kInfos["TIME"] = time();
                $kInfos["FINAL_TIME"] = 0;
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                $unix->ToSyslog("{KSRN} info: $Msg Err.".__LINE__, false, syslogSuff);
                krsync($kcloudCur,$kInfos["enable"]);
                build_progress("$Msg", 110);
            }
            if (isset($response->error)) {
                if ($response->error =="Invalid API key ") {
                    echo "***** $response->error ****\n";
                    $kInfos["status"] = "$response->error";
                    $kInfos["expire"] = 0;
                    $kInfos["ispaid"] = 0;
                    $kInfos["enable"] = 0;
                    $kInfos["reseller"] = null;
                    $kInfos["TIME"] = time();
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                    $unix->ToSyslog("{KSRN} info: $response->error Err.".__LINE__, false, syslogSuff);
                    krsync($kcloudCur,$kInfos["enable"]);
                    build_progress("$response->error", 110);
                }
            }
        }

        if ($response->status && $response->message == "1") {
            echo "OK the server as been contacted....[" . __LINE__ . "]\n";
            echo "OK Analyze answer from the cloud server....[" . __LINE__ . "]\n";
            echo "Data Lenght: " . strlen($curl->data) . " bytes\n";
            if (strlen($curl->data) == 0) {
                build_progress("{error} O Size byte", 110);
                return false;
            }
            echo "**********************************************\n";
            echo "*\n";
            echo "*\n";
            echo "*              Congratulations\n";
            echo "*        Your {KSRN} trial is  - Active -\n";
            echo "*\n";
            echo "*\n";
            echo "**********************************************\n";
            echo "OK {KSRN} trial is Active....Reloading[" . __LINE__ . "]\n";
            krsync($kcloudCur,1);
            build_progress("{reloading} {success}", 50);
            kinfo();
        }


        if ($response->status && $response->message == "2") {
            echo "***** {KSRN} is disabled ****\n";
            $kInfos["status"] = "{KSRN} is disabled";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: {KSRN} is disabled Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{KSRN} is disabled", 110);
        }
        if ($response->status && $response->message == "3") {
            echo "***** Server not registerd in LCC ****\n";
            $kInfos["status"] = "Server not registerd in LCC";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: Server not registerd in LCC Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("Server not registerd in LCC", 110);
        }

        if ($response->status && $response->message == "4") {
            echo "***** {KSRN} key already set, reloading ****\n";
            build_progress("{KSRN} key already set, reloading", 50);
            kinfo();
        }
        if ($response->status && $response->message == "5") {
            echo "***** {KSRN} key disabled on LCC ****\n";
            $kInfos["status"] = "{KSRN} key disabled on LCC";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: {KSRN} key disabled on LCC Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{KSRN} key disabled on LCC", 110);
        }
    } else {
        $SERVER_INFO = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        
        echo "Checking license on the cloud server in community mode\n";
        if (!$noprgress) {
            build_progress("Ping the cloud server...", 40);
        }
        echo "[" . __LINE__ . "] Contacting api/k/trial\n";
        if (!isset($SERVER_INFO["X-API-KEY"])) {
            $SERVER_INFO["X-API-KEY"]=null;
        }
        $curl = new ccurl("https://licensing.artica.center/api/k/trial", false, null);
        $curl->parms["X-API-KEY"] = "a00fb634db1c52fd72fc4aa077028de0";
        $curl->parms["SERVER_INFO"] = base64_encode(serialize($SERVER_INFO));
        $curl->NoLocalProxy();
        $curl->get();
        $response = json_decode(getLastLines($curl->orginal_data));
        print_r($response);

        if (!property_exists($response, "status")) {
            echo "Communication error status is not a property\n";
            $unix->ToSyslog("{KSRN} info: Communication error status is not a property Err.".__LINE__, false, syslogSuff);
            build_progress("{license_server_error}: Communication error status is not a property...", 110);
            echo "$curl->orginal_data\n";
            return false;
        }

        if (!$response->status) {
            $unix->ToSyslog("License info: $MsgFCCS Err.".__LINE__, false, syslogSuff);
            echo "**********************************\n";
            echo "*\n";
            echo "* $MsgFCCS..*\n";
            echo "*\n";
            echo "**********************************\n";
            $Msg=base64_decode("SW52YWxpZCBhcGkga2V5");

            if(!property_exists($response,"message")){
                echo "Invalid answer with $curl->orginal_data\n";
            }

            if ($response->message=="$Msg") {
                echo "***** $Msg ****\n";
                $kInfos["status"] = "$Msg";
                $kInfos["expire"] = 0;
                $kInfos["ispaid"] = 0;
                $kInfos["enable"] = 0;
                $kInfos["reseller"] = null;
                $kInfos["TIME"] = time();
                $kInfos["FINAL_TIME"] = 0;
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                $unix->ToSyslog("{KSRN} info: $Msg Err.".__LINE__, false, syslogSuff);
                krsync($kcloudCur,$kInfos["enable"]);
                build_progress("$Msg", 110);
            }
            if (isset($response->error)) {
                if ($response->error =="Invalid API key ") {
                    echo "***** $response->error ****\n";
                    $kInfos["status"] = "$response->error";
                    $kInfos["expire"] = 0;
                    $kInfos["ispaid"] = 0;
                    $kInfos["enable"] = 0;
                    $kInfos["reseller"] = null;
                    $kInfos["TIME"] = time();
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                    $unix->ToSyslog("{KSRN} info: $response->error Err.".__LINE__, false, syslogSuff);
                    krsync($kcloudCur,$kInfos["enable"]);
                    build_progress("$response->error", 110);
                }
            }
        }


        if ($response->status && $response->message == "1") {
            $finaltime = $response->expire;
            echo "OK the server as been contacted....[" . __LINE__ . "]\n";
            if ($finaltime > 0) {
                echo "Final Time: $finaltime against " . time() . "....[" . __LINE__ . "]\n";
                if (time() > $finaltime) {
                    $kInfos["status"] = $licexpir;
                    $kInfos["expire"] = 0;
                    $kInfos["ispaid"] = 0;
                    $kInfos["enable"] = 0;
                    $kInfos["reseller"] = null;
                    $kInfos["TIME"] = time();
                    if ($finaltime > 0) {
                        $kInfos["expire"] = $finaltime;
                    }
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                    krsync($kcloudCur,$kInfos["enable"]);
                    build_progress("$licexpir", 110);
                    return false;
                }
                $reste = distanceOfTimeInWords(time(), $finaltime);
                echo "license_status: expire in $reste [" . __LINE__ . "]\n";
            }
            echo "OK Analyze answer from the cloud server....[" . __LINE__ . "]\n";

            echo "Data Lenght: " . strlen($curl->data) . " bytes\n";
            if (strlen($curl->data) == 0) {
                build_progress("{error} O Size byte", 110);
                return false;
            }
            echo "**********************************************\n";
            echo "*\n";
            echo "*\n";
            echo "*              Congratulations\n";
            echo "*        Your {KSRN} is  - Active -\n";
            echo "*\n";
            echo "*\n";
            echo "**********************************************\n";
            echo "OK {KSRN} is Active....[" . __LINE__ . "]\n";
            if ($finaltime > 0) {
                $kInfos["expire"] = $finaltime;
            }
            $kInfos["status"] = "{license_active}";
            $kInfos["ispaid"] = $response->ispaid;
            $kInfos["enable"] = $response->enable;
            $kInfos["reseller"] = $response->reseller;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            if ($cmdADD != null) {
                shell_exec($cmdADD);
            }
            build_progress("{license} {refresh}", 100);
            $unix->ToSyslog("{KSRN} info: {license_active} Code:".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$response->enable);
            build_progress("{license} {success}", 100);
            return false;
        }
        if ($response->status && $response->message == "2") {
            echo "***** {KSRN} is disable ****\n";
            $kInfos["status"] = "{KSRN} is disabled";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: {KSRN} is disabled Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{KSRN} is disabled", 110);
        }
        if ($response->status && $response->message == "3") {
            echo "***** Server not registerd in LCC ****\n";
            $kInfos["status"] = "Server not registerd in LCC";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: Server not registerd in LCC Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("Server not registerd in LCC", 110);
        }
        if ($response->status && $response->message == "4") {
            echo "***** {KSRN} key already set, reloading ****\n";
            build_progress("{KSRN} key already set, reloading", 50);
            kinfo();
            return true;
        }
        if ($response->status && $response->message == "5") {
            echo "***** {KSRN} key disabled on LCC ****\n";
            $kInfos["status"] = "{KSRN} key disabled on LCC";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: {KSRN} key disabled on LCC Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{KSRN} key disabled on LCC", 110);
        }
    }

    if(property_exists($response,"message")){
        echo "Receive message : $response->message\n";
    }

    build_progress("None of cases can be used, contact sales team.", 110);
    return true;
}

function kinfo($noprgress=false):bool{
    $unix       = new unix();
    $licexpir   = base64_decode("e2xpY2Vuc2VfZXhwaXJlZH0=");
    $MsgFCCS    = base64_decode("RmFpbGVkIHRvIGNvbnRhY3QgY2xvdWQgc2VydmVy");
    $kcloudCur  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO(kcloudSyskey));
    $cmdADD     = null;
    $KSRNEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));

    if($KSRNEnable==0){
        $unix->Popuplate_cron_delete("ksrn-stats");
        return false;
    }

    if(!is_file("/etc/cron.d/ksrn-stats")) {
        $GLOBALS["CLASS_SOCKETS"]->build_progress(46, "{installing}");
        $unix->Popuplate_cron_make("ksrn-stats", "*/10 * * * *", "exec.ksrn.statistics.php");
        UNIX_RESTART_CRON();
    }


    if ($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()) {
        $kInfos["status"] = "{gold_license}";
        $kInfos["expire"] = 0;
        $kInfos["ispaid"] = 1;
        $kInfos["enable"] = 1;
        $kInfos["reseller"] = null;
        $kInfos["TIME"] = strtotime('+10 years');
        $kInfos["FINAL_TIME"] = 0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
        krsync($kcloudCur,$kInfos["enable"]);
        build_progress("{success}", 100);
        return true;
    }


    if ($GLOBALS['isRegistered']==1) {
        $SERVER_INFO = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        
        echo "Checking license on the cloud server\n";
        if (!$noprgress) {
            build_progress("Ping the cloud server...", 40);
        }
        echo "[" . __LINE__ . "] Contacting api/k/info\n";
        if (!isset($SERVER_INFO["X-API-KEY"])) {
            $SERVER_INFO["X-API-KEY"]=null;
        }
        $curl = new ccurl("https://licensing.artica.center/api/k/info", false, null);
        $curl->parms["X-API-KEY"] = $SERVER_INFO["X-API-KEY"];
        $curl->parms["SERVER_INFO"] = base64_encode(serialize($SERVER_INFO));
        $curl->NoLocalProxy();
        $curl->get();
        $response = json_decode(getLastLines($curl->orginal_data));

        if (!property_exists($response, "status")) {
            $unix->ToSyslog("{KSRN} info: Communication error status is not a property Err.".__LINE__, false, syslogSuff);
            build_progress("{license_server_error}: Communication error status is not a property...", 110);
            echo "$curl->orginal_data\n";
            return false;
        }


        if (!$response->status) {
            $unix->ToSyslog("License info: $MsgFCCS Err.".__LINE__, false, syslogSuff);
            echo "**********************************\n";
            echo "*\n";
            echo "* $MsgFCCS..*\n";
            echo "*\n";
            echo "**********************************\n";
            $Msg=base64_decode("SW52YWxpZCBhcGkga2V5");
            if ($response->message=="$Msg") {
                echo "***** $Msg ****\n";
                $kInfos["status"] = "$Msg";
                $kInfos["expire"] = 0;
                $kInfos["ispaid"] = 0;
                $kInfos["enable"] = 0;
                $kInfos["reseller"] = null;
                $kInfos["TIME"] = time();
                $kInfos["FINAL_TIME"] = 0;
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                $unix->ToSyslog("{KSRN} info: $Msg Err.".__LINE__, false, syslogSuff);
                krsync($kcloudCur,$kInfos["enable"]);
                build_progress("$Msg", 110);
            }
            if (isset($response->error)) {
                if ($response->error =="Invalid API key ") {
                    echo "***** $response->error ****\n";
                    $kInfos["status"] = "$response->error";
                    $kInfos["expire"] = 0;
                    $kInfos["ispaid"] = 0;
                    $kInfos["enable"] = 0;
                    $kInfos["reseller"] = null;
                    $kInfos["TIME"] = time();
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                    $unix->ToSyslog("{KSRN} info: $response->error Err.".__LINE__, false, syslogSuff);
                    krsync($kcloudCur,$kInfos["enable"]);
                    build_progress("$response->error", 110);
                }
            }
        }

        if ($response->status && $response->message == "1") {
            $finaltime = $response->expire;
            echo "OK the server as been contacted....[" . __LINE__ . "]\n";
            if ($finaltime > 0) {
                echo "Final Time: $finaltime against " . time() . "....[" . __LINE__ . "]\n";
                if (time() > $finaltime) {
                    $kInfos["status"] = $licexpir;
                    $kInfos["expire"] = 0;
                    $kInfos["ispaid"] = 0;
                    $kInfos["enable"] = 0;
                    $kInfos["reseller"] = null;
                    $kInfos["TIME"] = time();
                    if ($finaltime > 0) {
                        $kInfos["expire"] = $finaltime;
                    }
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                    krsync($kcloudCur,$kInfos["enable"]);
                    build_progress("$licexpir", 110);
                    return false;
                }
                $reste = distanceOfTimeInWords(time(), $finaltime);
                echo "license_status: expire in $reste [" . __LINE__ . "]\n";
            }
            echo "OK Analyze answer from the cloud server....[" . __LINE__ . "]\n";
            echo "Data Lenght: " . strlen($curl->data) . " bytes\n";
            if (strlen($curl->data) == 0) {
                build_progress("{error} O Size byte", 110);
                return false;
            }
            echo "**********************************************\n";
            echo "*\n";
            echo "*\n";
            echo "*              Congratulations\n";
            echo "*        Your {KSRN} is  - Active -\n";
            echo "*\n";
            echo "*\n";
            echo "**********************************************\n";
            echo "OK {KSRN} is Active....[" . __LINE__ . "]\n";
            if ($finaltime > 0) {
                $kInfos["expire"] = $finaltime;
            }
            $kInfos["status"] = "{license_active}";
            $kInfos["ispaid"] = $response->ispaid;
            $kInfos["enable"] = $response->enable;
            $kInfos["reseller"] = $response->reseller;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            if ($cmdADD != null) {
                shell_exec($cmdADD);
            }
            build_progress("{license} {refresh}", 100);
            $unix->ToSyslog("{KSRN} info: {license_active} Code:".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{license} {success}", 100);
            return false;
        }
        if ($response->status && $response->message == "2") {
            echo "***** {KSRN} is disable ****\n";
            $kInfos["status"] = "{license_marked_disabled}";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: {KSRN} is disabled Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{KSRN} is disabled", 110);
        }
        if ($response->status && $response->message == "3") {
            echo "***** Error N.".__LINE__." Server not registerd in LCC ****\n";
            $kInfos["status"] = "Not Registered";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["NoLic"]=1;
            $kInfos["NoRegister"]=1;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: Server not registerd in LCC Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("Server not registerd in LCC", 110);
        }
    } else {
        $SERVER_INFO = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
        
        echo "Checking license on the cloud server ". __LINE__."\n";
        if (!$noprgress) {
            build_progress("{connection_to_cloud_service}...", 40);
        }
        echo "[" . __LINE__ . "] Contacting api/k/info\n";
        if (!isset($SERVER_INFO["X-API-KEY"])) {
            $SERVER_INFO["X-API-KEY"]=null;
        }
        $curl = new ccurl("https://licensing.artica.center/api/k/info", false, null);
        $curl->parms["X-API-KEY"] = "a00fb634db1c52fd72fc4aa077028de0";
        $curl->parms["SERVER_INFO"] = base64_encode(serialize($SERVER_INFO));
        $curl->NoLocalProxy();
        $curl->get();
        $response = json_decode(getLastLines($curl->orginal_data));

        if (!property_exists($response, "status")) {
            $unix->ToSyslog("License info: Communication error status is not a property. Err.".__LINE__, false, syslogSuff);
            build_progress("{license_server_error}: Communication error status is not a property...", 110);
            echo "$curl->orginal_data\n";
            return false;
        }


        if (!$response->status) {
            $unix->ToSyslog("License info: $MsgFCCS Err.".__LINE__, false, syslogSuff);
            echo "**********************************\n";
            echo "*\n";
            echo "* $MsgFCCS..*\n";
            echo "*\n";
            echo "**********************************\n";
            $Msg=base64_decode("SW52YWxpZCBhcGkga2V5");
            if ($response->message=="$Msg") {
                echo "***** $Msg ****\n";
                $kInfos["status"] = "$Msg";
                $kInfos["expire"] = 0;
                $kInfos["ispaid"] = 0;
                $kInfos["enable"] = 0;
                $kInfos["reseller"] = null;
                $kInfos["TIME"] = time();
                $kInfos["FINAL_TIME"] = 0;
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                $unix->ToSyslog("{KSRN} info: $Msg Err.".__LINE__, false, syslogSuff);
                krsync($kcloudCur,$kInfos["enable"]);
                build_progress("$Msg", 110);
            }
            if (isset($response->error)) {
                if ($response->error =="Invalid API key ") {
                    echo "***** $response->error ****\n";
                    $kInfos["status"] = "$response->error";
                    $kInfos["expire"] = 0;
                    $kInfos["ispaid"] = 0;
                    $kInfos["enable"] = 0;
                    $kInfos["reseller"] = null;
                    $kInfos["TIME"] = time();
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                    $unix->ToSyslog("{KSRN} info: $response->error Err.".__LINE__, false, syslogSuff);
                    krsync($kcloudCur,$kInfos["enable"]);
                    build_progress($response->error, 110);
                }
            }
        }


        if ($response->status && $response->message == "1") {
            $finaltime = $response->expire;
            echo "OK the server as been contacted....[" . __LINE__ . "]\n";
            if ($finaltime > 0) {
                echo "Final Time: $finaltime against " . time() . "....[" . __LINE__ . "]\n";
                if (time() > $finaltime) {
                    $kInfos["status"] = $licexpir;
                    $kInfos["expire"] = 0;
                    $kInfos["ispaid"] = 0;
                    $kInfos["enable"] = 0;
                    $kInfos["reseller"] = null;
                    $kInfos["NoLic"]=0;
                    $kInfos["NoRegister"]=0;
                    $kInfos["TIME"] = time();
                    if ($finaltime > 0) {
                        $kInfos["expire"] = $finaltime;
                    }
                    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
                    krsync($kcloudCur,$kInfos["enable"]);
                    build_progress("$licexpir", 110);
                    return false;
                }
                $reste = distanceOfTimeInWords(time(), $finaltime);
                echo "license_status: expire in $reste [" . __LINE__ . "]\n";
            }
            echo "OK Analyze answer from the cloud server....[" . __LINE__ . "]\n";

            echo "Data Lenght: " . strlen($curl->data) . " bytes\n";
            if (strlen($curl->data) == 0) {
                build_progress("{error} O Size byte", 110);
                return false;
            }
            echo "**********************************************\n";
            echo "*\n";
            echo "*\n";
            echo "*              Congratulations\n";
            echo "*        Your {KSRN} is  - Active -\n";
            echo "*\n";
            echo "*\n";
            echo "**********************************************\n";
            echo "OK {KSRN} is Active....[" . __LINE__ . "]\n";
            if ($finaltime > 0) {
                $kInfos["expire"] = $finaltime;
            }
            $kInfos["status"] = "{license_active}";
            $kInfos["ispaid"] = $response->ispaid;
            $kInfos["enable"] = $response->enable;
            $kInfos["reseller"] = $response->reseller;
            $kInfos["NoLic"]=0;
            $kInfos["NoRegister"]=0;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            if ($cmdADD != null) {
                shell_exec($cmdADD);
            }
            build_progress("{license} {refresh}", 100);
            $unix->ToSyslog("{KSRN} info: {license_active} Code:".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{license} {success}", 100);
            return false;
        }
        if ($response->status && $response->message == "2") {
            echo "***** {KSRN} is disable ****\n";
            $kInfos["status"] = "{KSRN} {no_license}";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["NoLic"]=1;
            $kInfos["NoRegister"]=0;
            $kInfos["reseller"] = null;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: {KSRN} {license_is_disabled_onsrv} Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("{KSRN} {license_is_disabled_onsrv}", 110);
        }
        if ($response->status && $response->message == "3") {
            echo "***** Server not registerd in LCC ****\n";
            $kInfos["status"] = "Server not registerd in LCC";
            $kInfos["expire"] = 0;
            $kInfos["ispaid"] = 0;
            $kInfos["enable"] = 0;
            $kInfos["reseller"] = null;
            $kInfos["NoRegister"]=1;
            $kInfos["TIME"] = time();
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kInfos",base64_encode( serialize($kInfos) )  );
            $unix->ToSyslog("{KSRN} info: Server not registerd in LCC Err.".__LINE__, false, syslogSuff);
            krsync($kcloudCur,$kInfos["enable"]);
            build_progress("Server not registerd in LCC", 110);
        }
    }
    return true;
}

function getLastLines($string, $n = 1):string{
    $lines = explode("\n", $string);
    $lines = array_slice($lines, -$n);
    return implode("\n", $lines);
}

function build_progress($text, $pourc):bool{
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/artica.k.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
    return true;
}

function krsync($cur,$next):bool{
    $unix           =new unix();

    if($GLOBALS["VERBOSE"]){echo "[DEBUG]: ".kcloudSyskey." Current:$cur == New:$next\n";}
    if($next==1){
        @file_put_contents(base64_decode("L3Zhci9saWIvc3F1aWQvLnNybi5saWM="),time());
    }else{
        @unlink(base64_decode("L3Zhci9saWIvc3F1aWQvLnNybi5saWM="));
    }

    if($cur==$next){return true;}
    if($next==1) {
        $unix->ToSyslog("[INFO]: ".base64_decode("U3RhbXAgbGljZW5zZSB0byBUcnVl"), false, "ksrn");
    }else{
        $unix->ToSyslog("[INFO]: ".base64_decode("U3RhbXAgbGljZW5zZSB0byBGYWxzZQ=="),false,"ksrn");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO(kcloudSyskey,$next);
    return true;
}

function perms():bool{
    return true;
}
