<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.ntpd.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["popup1"])){popup1();exit;}
if(isset($_POST["timezones"])){SaveTime();exit;}
if(isset($_POST["Day"])){SaveTime();exit;}
if(isset($_POST["NTPDUseSpecifiedServers"])){section_ntpclient_save();exit;}
if(isset($_GET["refresh-field"])){refresh_field();exit;}

if(isset($_GET["section-timezone-js"])){section_timezone_js();exit;}
if(isset($_GET["section-timezone-popup"])){section_timezone_popup();exit;}

if(isset($_GET["section-syncad-js"])){section_syncad_js();exit;}
if(isset($_GET["section-syncad-popup"])){section_syncad_popup();exit;}

if(isset($_GET["section-ntpclient-js"])){section_ntpclient_js();exit;}
if(isset($_GET["section-ntpclient-popup"])){section_ntpclient_popup();exit;}

if(isset($_GET["section-chclock-js"])){section_chclock_js();exit;}
if(isset($_GET["section-chclock-popup"])){section_chclock_popup();exit;}
if(isset($_GET["crony-status"])){crony_status();exit;}




js();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{server_time2}", "$page?popup=yes",1150);
}

function popup():bool{
    $page=CurrentPageName();
    echo "<div id='sync-client'></div><div id='system-clock-table'></div><script>LoadAjax('system-clock-table','$page?popup1=yes');</script>";
    return true;

}
function section_timezone_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{timezone}","$page?section-timezone-popup=yes");
}
function section_syncad_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{synchronize_time_with_ad}","$page?section-syncad-popup=yes");
}
function section_ntpclient_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("NTP","$page?section-ntpclient-popup=yes");
}
function section_chclock_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{system_clock}","$page?section-chclock-popup=yes");
}



function section_timezone_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ntp=new ntpd();
    $arrayTimzone=$ntp->timezonearray();
    $timezone_def=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("timezones"));
    $languages=Local_array();
    $langbox[null]="{select}";
    foreach ($languages as $lang){$langbox[$lang]=$lang;}
    $LOCALE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));


    $form[]=$tpl->field_array_hash($arrayTimzone, "timezones", "{timezone} ({system})", $timezone_def);
    $form[]=$tpl->field_array_hash($langbox, "LOCALE", "{locale}", $LOCALE,false,"{LOCALES_EXPLAIN}");

    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null,$form,null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_syncad_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));

    $form[] = $tpl->field_checkbox("NtpdateAD", "url:https://wiki.articatech.com/en/active-directory/Time-synchronization-with-Active-Directory;{synchronize_time_with_ad}", $NtpdateAD, false, "{synchronize_time_with_ad_explain}");

    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null,$form,"{synchronize_time_with_ad_explain}","{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function crony_status():bool{
    $tpl=new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return false;
    }


    if (!$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));
        return false;
    }

    $jsRestart=$tpl->framework_buildjs("/chrony/restart",
        "ntpd.progress","ntpd.progress.log","sync-client"
    );

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_NTPD", $jsRestart));
    return true;
}



function section_js_form():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsRestart=null;
    return "dialogInstance2.close();LoadAjax('system-clock-table','$page?popup1=yes');$jsRestart";
}
function section_chclock_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    exec("/bin/date \"+%Y-%m-%d\"",$results);
    $day=@implode("",$results);
    $results=array();
    exec("/bin/date \"+%H:%M:%S\"",$results);
    $time=@implode("",$results);


    $form[]=$tpl->field_date("Day","{today}",$day);
    $form[]=$tpl->field_clock("Hour","{this_hour}",$time);
    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null,$form,"{clocks_text}","{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_ntpclient_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ntp=new ntpd();
    $ServersList=array();

    $NTPDUseSpecifiedServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"));
    $NTPClientDefaultServerList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList");

    $sserv = $ntp->ServersList();
    foreach ($sserv as $num => $val) {
        $ServersList[$num] = $num;
    }



    if ($NTPDUseSpecifiedServers == 1) {
        $tpl->field_hidden("NTPClientDefaultServerList", $NTPClientDefaultServerList);

    } else {
        $form[] = $tpl->field_array_hash($ServersList, "NTPClientDefaultServerList", "{default_ntp_servers}", $NTPClientDefaultServerList);
    }
    $form[] = $tpl->field_checkbox("NTPDUseSpecifiedServers", "{use_specified_time_servers}", $NTPDUseSpecifiedServers);

    $js="dialogInstance2.close();LoadAjax('system-clock-table','$page?popup1=yes');";
    $jsrestart=$tpl->framework_buildjs("/chrony/sync","ntpd.progress","ntpd.progress.log","sync-client","$js");
    $security="AsSystemAdministrator";
    $tpl->form_add_button("{ntp_servers}","Loadjs('fw.system.ntp.servers.php')");
    $html[]=$tpl->form_outside(null,$form,"{synchronize_time_with_ad_explain}","{apply}", "LoadAjax('system-clock-table','$page?popup1=yes');$jsrestart",$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_ntpclient_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/sync");
    return admin_tracks("Save NTP parameters");
}

function popup1(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ntp=new ntpd();
    $tt=time();
    exec("/bin/date \"+%Y-%m-%d\"",$results);
    $day=@implode("",$results);
    $results=array();
    exec("/bin/date \"+%H:%M:%S\"",$results);
    $time=@implode("",$results);
    $ServersList=array();
    $ChronydEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydEnabled"));
    $NTPDUseSpecifiedServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"));
    $NTPClientDefaultServerList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList");
    $isNtpdateAD=$GLOBALS["CLASS_SOCKETS"]->isNtpdateAD();
    if($isNtpdateAD){
        $ChronydEnabled=1;
    }
    if($NTPClientDefaultServerList==null){$NTPClientDefaultServerList="United States";}
    $arrayTimzone=$ntp->timezonearray();
    $timezone_def=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("timezones"));
    $LOCALE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));


    $languages=Local_array();
    $langbox[null]="{select}";
    foreach ($languages as $lang){$langbox[$lang]=$lang;}
    $script_tz = date_default_timezone_get();
    $tpl->table_form_section("{system_clock}");
    $tpl->table_form_field_js("Loadjs('$page?section-chclock-js=yes')");
    $tpl->table_form_field_text("{time}","$day - $time",ico_clock_desk);
    $tpl->table_form_field_js("Loadjs('$page?section-timezone-js=yes')");
    $tpl->table_form_field_text("{timezone}",$script_tz." <small>(PHP)</small><br>".$arrayTimzone[$timezone_def]." <small>({system})</small>",ico_clock);
    $tpl->table_form_field_text("{locale}",$LOCALE,ico_language);

    if($ChronydEnabled==1) {
        $tpl->table_form_section("{APP_NTPD}");
    }

    $sserv = $ntp->ServersList();
    foreach ($sserv as $num => $val) {
        $ServersList[$num] = $num;
    }

    if($ChronydEnabled==1) {
        $tpl->table_form_field_js("Loadjs('$page?section-ntpclient-js=yes')");
        if (!$isNtpdateAD) {
            if ($NTPDUseSpecifiedServers == 1) {
                $q = new lib_sqlite("/home/artica/SQLITE/ntp.db");
                $sql = "SELECT * FROM ntpd_servers ORDER BY `ntpd_servers`.`order` ASC";
                $results = $q->QUERY_SQL($sql);
                $serverenc = array();
                foreach ($results as $index => $ligne) {
                    $serverenc[] = $ligne["ntp_servers"];
                }
                $tpl->table_form_field_text("{use_specified_time_servers}", @implode(", ", $serverenc), ico_server);
            } else {
                $tpl->table_form_field_text("{default_ntp_servers}", $ServersList[$NTPClientDefaultServerList], ico_server);
            }
        }else{
            $tpl->table_form_field_text("{ntp_server}","Active Directory", ico_server);

        }
    }


    $jsAfter="LoadAjax('system-clock-table','$page?popup1=yes');";
    $jsrestart=$tpl->framework_buildjs("/chrony/sync","ntpd.progress","ntpd.progress.log","sync-client","$jsAfter");



    if($ChronydEnabled==1){
        $tpl->table_form_field_js($jsrestart);
        $tpl->table_form_field_button("{sync_time}","{sync_time}",ico_refresh);
    }else{

        $install=$tpl->framework_buildjs("/ntpd/install",
            "ntpd.progress",
            "ntpd.progress.log",
            "sync-client","LoadAjax('system-clock-table','$page?popup1=yes');");
        $tpl->table_form_field_js($install);
        $tpl->table_form_field_button("{APP_NTPD}","{install}",ico_cd);

    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:450px;vertical-align:top'><div id='crony-status'></div></td>";
    $html[]="<td style='width:100%;vertical-align:top;padding-left: 10px'>";
    $html[]=$tpl->table_form_compile();
    $html[]="</td></tr></table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("crony-status",$page,"crony-status=yes");
    $html[]="</script>";
    echo @implode("\n", $html);

}

function popup_old(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ntp=new ntpd();
    $tt=time();
    exec("/bin/date \"+%Y-%m-%d\"",$results);
    $day=@implode("",$results);
    $results=array();
    exec("/bin/date \"+%H:%M:%S\"",$results);
    $time=@implode("",$results);
    $ServersList=array();
    $ChronydEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydEnabled"));
    $NTPDUseSpecifiedServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"));
    $NTPClientDefaultServerList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList");
    $isNtpdateAD=$GLOBALS["CLASS_SOCKETS"]->isNtpdateAD();
    if($isNtpdateAD){
        $ChronydEnabled=1;
    }




    if($NTPClientDefaultServerList==null){$NTPClientDefaultServerList="United States";}
    $arrayTimzone=$ntp->timezonearray();
    $timezone_def=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("timezones"));
    $LOCALE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));


    $languages=Local_array();
    $langbox[null]="{select}";
    foreach ($languages as $lang){$langbox[$lang]=$lang;}

    $script_tz = date_default_timezone_get();
    $form[]=$tpl->field_info("timezone_infos","{timezone} (PHP)",$script_tz);
    $form[]=$tpl->field_array_hash($arrayTimzone, "timezones", "{timezone} ({system})", $timezone_def);
    $form[]=$tpl->field_array_hash($langbox, "LOCALE", "{locale}", $LOCALE,false,"{LOCALES_EXPLAIN}");

    if($isNtpdateAD){
        $form[]=$tpl->field_section("{synchronize_time_with_ad}","{synchronize_time_with_ad_explain}");
    }

    if($ChronydEnabled==1){
        if(!$isNtpdateAD) {
            $NTPDClientPool = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDClientPool"));
            if ($NTPDClientPool == 0) {
                $NTPDClientPool = 120;
            }
            $NTPDClientPools[60] = "{each} {hour}";
            $NTPDClientPools[120] = "{each} 2 {hours}";
            $NTPDClientPools[180] = "{each} 3 {hours}";
            $NTPDClientPools[240] = "{each} 4 {hours}";

            $sserv = $ntp->ServersList();
            foreach ($sserv as $num => $val) {
                $ServersList[$num] = $num;
            }

            $form[] = $tpl->field_info("Day", "{today}", $day);
            $form[] = $tpl->field_info("Hour", "{this_hour}", $time, null, "Hour$tt");
            $form[] = $tpl->field_array_hash($NTPDClientPools, "NTPDClientPool", "NTP: {update_time}", $NTPDClientPool);


            if ($NTPDUseSpecifiedServers == 1) {
                $tpl->field_hidden("NTPClientDefaultServerList", $NTPClientDefaultServerList);

            } else {
                $form[] = $tpl->field_array_hash($ServersList, "NTPClientDefaultServerList", "{default_ntp_servers}", $NTPClientDefaultServerList);
            }


            $form[] = $tpl->field_checkbox("NTPDUseSpecifiedServers", "{use_specified_time_servers}", $NTPDUseSpecifiedServers, false, array(
                "AUTOSAVE" => true,
                "BUTTON" => true,
                "BUTTON_CAPTION" => "{ntp_servers}",
                "BUTTON_JS" => "Loadjs('fw.system.ntp.servers.php')"

            ));
        }
    }else{
        $form[]=$tpl->field_date("Day","{today}",$day);
        $form[]=$tpl->field_clock("Hour","{this_hour}",$time,"Hour$tt");
    }


    $jsAfter="BootstrapDialog1.close();Loadjs('$page');";
    $jsrestart=$tpl->framework_buildjs("/chrony/sync","ntpd.progress","ntpd.progress.log","sync-client","$jsAfter");
    if($ChronydEnabled==1){$tpl->form_add_button("{sync_time}",$jsrestart);}


    $html[]="<div id='sync-client'></div>";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),"{clocks_text}","{apply}","$jsAfter","AsSystemAdministrator");

    $html[]="";

    echo @implode("\n", $html);

}

function refresh_field(){
    $page=CurrentPageName();
    exec("/bin/date \"+%H:%M:%S\"",$results);
    $time=@implode("",$results);
    $field=$_GET["refresh-field"];

    $ChronydEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydEnabled"));
    if($ChronydEnabled==1){$ChronydEnabled=1;}
    header("content-type: application/x-javascript");
    $tt=time();
    $html="
function refresh_field$tt(){
	var ChronydEnabled=$ChronydEnabled;
	if(!document.getElementById('$field') ){return;}
	if(ChronydEnabled==1){ document.getElementById('$field').innerHTML='$time';}
	if(ChronydEnabled==0){document.getElementById('$field').value='$time';}
	Loadjs('$page?refresh-field=$field');	
}
	
	setTimeout('refresh_field$tt()',5000)";

    echo $html;

}

function SaveTime(){
    $tpl=new template_admin();
    $ChronydEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydEnabled"));
    writelogs("ChronydEnabled = $ChronydEnabled",__FUNCTION__,__FILE__,__LINE__);
    $tpl->CLEAN_POST();
    if($ChronydEnabled==0){
        //$newdate="MMDDhhmmYY.ss";
        if(isset($_POST["Day"])) {
            ini_set('display_errors', 1);
            ini_set('error_reporting', E_ALL);
            ini_set('error_prepend_string', null);
            ini_set('error_append_string', null);
            $stime = $_POST["Day"] . " " . $_POST["Hour"];
            $xtime = strtotime($stime);
            if (!$xtime) {
                echo $tpl->_ENGINE_parse_body("{error_converting_date}: $stime");
                return false;
            }
            $month = date("m", $xtime);
            $day = date("d", $xtime);
            $hour = date("H", $xtime);
            $minute = date("i", $xtime);
            $year = date("Y", $xtime);
            $seconds = date("s", $xtime);
            $newdate = "$month$day$hour$minute$year.$seconds";
            $sock = new sockets();
            writelogs("New date = $newdate",__FUNCTION__,__FILE__,__LINE__);
            $sock->getFrameWork("cmd.php?SetServerTime=$newdate");
        }
    }
    $sock=new sockets();
    $timezone_def=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("timezones"));
    if(isset($_POST["timezones"])){
        if($_POST["timezones"]<>$timezone_def){
            $sock->SET_INFO("timezones", $_POST["timezones"]);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/php/ini/build");
        }else{
            $script_tz = date_default_timezone_get();
            if($_POST["timezones"]<>$script_tz){
                $sock->SET_INFO("timezones", $_POST["timezones"]);
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/php/ini/build");
            }
        }
    }


    if(isset($_POST["LOCALE"])){
        $LOCALE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCALE"));
        if($LOCALE<>$_POST["LOCALE"]){
            $sock->SET_INFO("LOCALE", $_POST["LOCALE"]);
            admin_tracks("Modifies locales to {$_POST["LOCALE"]}");
            $sock->REST_API("/savelocales");
        }
    }



    if(isset($_POST["NTPDClientPool"])){$sock->SET_INFO("NTPDClientPool", $_POST["NTPDClientPool"]);}
    if(isset($_POST["NTPClientDefaultServerList"])){$sock->SET_INFO("NTPClientDefaultServerList", $_POST["NTPClientDefaultServerList"]);}
    if(isset($_POST["NTPDUseSpecifiedServers"])){$sock->SET_INFO("NTPDUseSpecifiedServers", $_POST["NTPDUseSpecifiedServers"]);}
    if($ChronydEnabled==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/reconfigure");
    }
    return admin_tracks_post("Change Network time service parameters");
}


function Local_array():array{
    $f[]="aa_DJ.UTF-8 UTF-8";
    $f[]="aa_DJ ISO-8859-1";
    $f[]="aa_ER UTF-8";
    $f[]="aa_ER@saaho UTF-8";
    $f[]="aa_ET UTF-8";
    $f[]="af_ZA.UTF-8 UTF-8";
    $f[]="af_ZA ISO-8859-1";
    $f[]="am_ET UTF-8";
    $f[]="an_ES.UTF-8 UTF-8";
    $f[]="an_ES ISO-8859-15";
    $f[]="ar_AE.UTF-8 UTF-8";
    $f[]="ar_AE ISO-8859-6";
    $f[]="ar_BH.UTF-8 UTF-8";
    $f[]="ar_BH ISO-8859-6";
    $f[]="ar_DZ.UTF-8 UTF-8";
    $f[]="ar_DZ ISO-8859-6";
    $f[]="ar_EG.UTF-8 UTF-8";
    $f[]="ar_EG ISO-8859-6";
    $f[]="ar_IN UTF-8";
    $f[]="ar_IQ.UTF-8 UTF-8";
    $f[]="ar_IQ ISO-8859-6";
    $f[]="ar_JO.UTF-8 UTF-8";
    $f[]="ar_JO ISO-8859-6";
    $f[]="ar_KW.UTF-8 UTF-8";
    $f[]="ar_KW ISO-8859-6";
    $f[]="ar_LB.UTF-8 UTF-8";
    $f[]="ar_LB ISO-8859-6";
    $f[]="ar_LY.UTF-8 UTF-8";
    $f[]="ar_LY ISO-8859-6";
    $f[]="ar_MA.UTF-8 UTF-8";
    $f[]="ar_MA ISO-8859-6";
    $f[]="ar_OM.UTF-8 UTF-8";
    $f[]="ar_OM ISO-8859-6";
    $f[]="ar_QA.UTF-8 UTF-8";
    $f[]="ar_QA ISO-8859-6";
    $f[]="ar_SA.UTF-8 UTF-8";
    $f[]="ar_SA ISO-8859-6";
    $f[]="ar_SD.UTF-8 UTF-8";
    $f[]="ar_SD ISO-8859-6";
    $f[]="ar_SY.UTF-8 UTF-8";
    $f[]="ar_SY ISO-8859-6";
    $f[]="ar_TN.UTF-8 UTF-8";
    $f[]="ar_TN ISO-8859-6";
    $f[]="ar_YE.UTF-8 UTF-8";
    $f[]="ar_YE ISO-8859-6";
    $f[]="az_AZ.UTF-8 UTF-8";
    $f[]="as_IN.UTF-8 UTF-8";
    $f[]="ast_ES.UTF-8 UTF-8";
    $f[]="ast_ES ISO-8859-15";
    $f[]="be_BY.UTF-8 UTF-8";
    $f[]="be_BY CP1251";
    $f[]="be_BY@latin UTF-8";
    $f[]="ber_DZ UTF-8";
    $f[]="ber_MA UTF-8";
    $f[]="bg_BG.UTF-8 UTF-8";
    $f[]="bg_BG CP1251";
    $f[]="bn_BD UTF-8";
    $f[]="bn_IN UTF-8";
    $f[]="br_FR.UTF-8 UTF-8";
    $f[]="br_FR ISO-8859-1";
    $f[]="br_FR@euro ISO-8859-15";
    $f[]="bs_BA.UTF-8 UTF-8";
    $f[]="bs_BA ISO-8859-2";
    $f[]="byn_ER UTF-8";
    $f[]="ca_AD.UTF-8 UTF-8";
    $f[]="ca_AD ISO-8859-15";
    $f[]="ca_ES.UTF-8 UTF-8";
    $f[]="ca_ES ISO-8859-1";
    $f[]="ca_ES@euro ISO-8859-15";
    $f[]="ca_ES.UTF-8@valencia UTF-8";
    $f[]="ca_ES@valencia ISO-8859-15";
    $f[]="ca_FR.UTF-8 UTF-8";
    $f[]="ca_FR ISO-8859-15";
    $f[]="ca_IT.UTF-8 UTF-8";
    $f[]="ca_IT ISO-8859-15";
    $f[]="crh_UA UTF-8";
    $f[]="cs_CZ.UTF-8 UTF-8";
    $f[]="cs_CZ ISO-8859-2";
    $f[]="csb_PL UTF-8";
    $f[]="cy_GB.UTF-8 UTF-8";
    $f[]="cy_GB ISO-8859-14";
    $f[]="da_DK.UTF-8 UTF-8";
    $f[]="da_DK ISO-8859-1";
    $f[]="da_DK.ISO-8859-15 ISO-8859-15";
    $f[]="de_AT.UTF-8 UTF-8";
    $f[]="de_AT ISO-8859-1";
    $f[]="de_AT@euro ISO-8859-15";
    $f[]="de_BE.UTF-8 UTF-8";
    $f[]="de_BE ISO-8859-1";
    $f[]="de_BE@euro ISO-8859-15";
    $f[]="de_CH.UTF-8 UTF-8";
    $f[]="de_CH ISO-8859-1";
    $f[]="de_DE.UTF-8 UTF-8";
    $f[]="de_DE ISO-8859-1";
    $f[]="de_DE@euro ISO-8859-15";
    $f[]="de_LI.UTF-8 UTF-8";
    $f[]="de_LU.UTF-8 UTF-8";
    $f[]="de_LU ISO-8859-1";
    $f[]="de_LU@euro ISO-8859-15";
    $f[]="dz_BT UTF-8";
    $f[]="el_GR.UTF-8 UTF-8";
    $f[]="el_GR ISO-8859-7";
    $f[]="el_CY.UTF-8 UTF-8";
    $f[]="el_CY ISO-8859-7";
    $f[]="en_AU.UTF-8 UTF-8";
    $f[]="en_AU ISO-8859-1";
    $f[]="en_BW.UTF-8 UTF-8";
    $f[]="en_BW ISO-8859-1";
    $f[]="en_CA.UTF-8 UTF-8";
    $f[]="en_CA ISO-8859-1";
    $f[]="en_DK.UTF-8 UTF-8";
    $f[]="en_DK.ISO-8859-15 ISO-8859-15";
    $f[]="en_DK ISO-8859-1";
    $f[]="en_GB.UTF-8 UTF-8";
    $f[]="en_GB ISO-8859-1";
    $f[]="en_GB.ISO-8859-15 ISO-8859-15";
    $f[]="en_HK.UTF-8 UTF-8";
    $f[]="en_HK ISO-8859-1";
    $f[]="en_IE.UTF-8 UTF-8";
    $f[]="en_IE ISO-8859-1";
    $f[]="en_IE@euro ISO-8859-15";
    $f[]="en_IN UTF-8";
    $f[]="en_NG UTF-8";
    $f[]="en_NZ.UTF-8 UTF-8";
    $f[]="en_NZ ISO-8859-1";
    $f[]="en_PH.UTF-8 UTF-8";
    $f[]="en_PH ISO-8859-1";
    $f[]="en_SG.UTF-8 UTF-8";
    $f[]="en_SG ISO-8859-1";
    $f[]="en_US.UTF-8 UTF-8";
    $f[]="en_US ISO-8859-1";
    $f[]="en_US.ISO-8859-15 ISO-8859-15";
    $f[]="en_ZA.UTF-8 UTF-8";
    $f[]="en_ZA ISO-8859-1";
    $f[]="en_ZW.UTF-8 UTF-8";
    $f[]="en_ZW ISO-8859-1";
    $f[]="eo.UTF-8 UTF-8";
    $f[]="eo ISO-8859-3";
    $f[]="es_AR.UTF-8 UTF-8";
    $f[]="es_AR ISO-8859-1";
    $f[]="es_BO.UTF-8 UTF-8";
    $f[]="es_BO ISO-8859-1";
    $f[]="es_CL.UTF-8 UTF-8";
    $f[]="es_CL ISO-8859-1";
    $f[]="es_CO.UTF-8 UTF-8";
    $f[]="es_CO ISO-8859-1";
    $f[]="es_CR.UTF-8 UTF-8";
    $f[]="es_CR ISO-8859-1";
    $f[]="es_DO.UTF-8 UTF-8";
    $f[]="es_DO ISO-8859-1";
    $f[]="es_EC.UTF-8 UTF-8";
    $f[]="es_EC ISO-8859-1";
    $f[]="es_ES.UTF-8 UTF-8";
    $f[]="es_ES ISO-8859-1";
    $f[]="es_ES@euro ISO-8859-15";
    $f[]="es_GT.UTF-8 UTF-8";
    $f[]="es_GT ISO-8859-1";
    $f[]="es_HN.UTF-8 UTF-8";
    $f[]="es_HN ISO-8859-1";
    $f[]="es_MX.UTF-8 UTF-8";
    $f[]="es_MX ISO-8859-1";
    $f[]="es_NI.UTF-8 UTF-8";
    $f[]="es_NI ISO-8859-1";
    $f[]="es_PA.UTF-8 UTF-8";
    $f[]="es_PA ISO-8859-1";
    $f[]="es_PE.UTF-8 UTF-8";
    $f[]="es_PE ISO-8859-1";
    $f[]="es_PR.UTF-8 UTF-8";
    $f[]="es_PR ISO-8859-1";
    $f[]="es_PY.UTF-8 UTF-8";
    $f[]="es_PY ISO-8859-1";
    $f[]="es_SV.UTF-8 UTF-8";
    $f[]="es_SV ISO-8859-1";
    $f[]="es_US.UTF-8 UTF-8";
    $f[]="es_US ISO-8859-1";
    $f[]="es_UY.UTF-8 UTF-8";
    $f[]="es_UY ISO-8859-1";
    $f[]="es_VE.UTF-8 UTF-8";
    $f[]="es_VE ISO-8859-1";
    $f[]="et_EE.UTF-8 UTF-8";
    $f[]="et_EE ISO-8859-1";
    $f[]="et_EE.ISO-8859-15 ISO-8859-15";
    $f[]="eu_ES.UTF-8 UTF-8";
    $f[]="eu_ES ISO-8859-1";
    $f[]="eu_ES@euro ISO-8859-15";
    $f[]="eu_FR.UTF-8 UTF-8";
    $f[]="eu_FR ISO-8859-1";
    $f[]="eu_FR@euro ISO-8859-15";
    $f[]="fa_IR UTF-8";
    $f[]="fi_FI.UTF-8 UTF-8";
    $f[]="fi_FI ISO-8859-1";
    $f[]="fi_FI@euro ISO-8859-15";
    $f[]="fil_PH UTF-8";
    $f[]="fo_FO.UTF-8 UTF-8";
    $f[]="fo_FO ISO-8859-1";
    $f[]="fr_BE.UTF-8 UTF-8";
    $f[]="fr_BE ISO-8859-1";
    $f[]="fr_BE@euro ISO-8859-15";
    $f[]="fr_CA.UTF-8 UTF-8";
    $f[]="fr_CA ISO-8859-1";
    $f[]="fr_CH.UTF-8 UTF-8";
    $f[]="fr_CH ISO-8859-1";
    $f[]="fr_FR.UTF-8 UTF-8";
    $f[]="fr_FR ISO-8859-1";
    $f[]="fr_FR@euro ISO-8859-15";
    $f[]="fr_LU.UTF-8 UTF-8";
    $f[]="fr_LU ISO-8859-1";
    $f[]="fr_LU@euro ISO-8859-15";
    $f[]="fur_IT UTF-8";
    $f[]="fy_NL UTF-8";
    $f[]="fy_DE UTF-8";
    $f[]="ga_IE.UTF-8 UTF-8";
    $f[]="ga_IE ISO-8859-1";
    $f[]="ga_IE@euro ISO-8859-15";
    $f[]="gd_GB.UTF-8 UTF-8";
    $f[]="gd_GB ISO-8859-15";
    $f[]="gez_ER UTF-8";
    $f[]="gez_ER@abegede UTF-8";
    $f[]="gez_ET UTF-8";
    $f[]="gez_ET@abegede UTF-8";
    $f[]="gl_ES.UTF-8 UTF-8";
    $f[]="gl_ES ISO-8859-1";
    $f[]="gl_ES@euro ISO-8859-15";
    $f[]="gu_IN UTF-8";
    $f[]="gv_GB.UTF-8 UTF-8";
    $f[]="gv_GB ISO-8859-1";
    $f[]="ha_NG UTF-8";
    $f[]="he_IL.UTF-8 UTF-8";
    $f[]="he_IL ISO-8859-8";
    $f[]="hi_IN UTF-8";
    $f[]="hr_HR.UTF-8 UTF-8";
    $f[]="hr_HR ISO-8859-2";
    $f[]="hsb_DE.UTF-8 UTF-8";
    $f[]="hsb_DE ISO-8859-2";
    $f[]="hu_HU.UTF-8 UTF-8";
    $f[]="hu_HU ISO-8859-2";
    $f[]="hy_AM UTF-8";
    $f[]="hy_AM.ARMSCII-8 ARMSCII-8";
    $f[]="ia UTF-8";
    $f[]="id_ID.UTF-8 UTF-8";
    $f[]="id_ID ISO-8859-1";
    $f[]="ig_NG UTF-8";
    $f[]="ik_CA UTF-8";
    $f[]="is_IS.UTF-8 UTF-8";
    $f[]="is_IS ISO-8859-1";
    $f[]="it_CH.UTF-8 UTF-8";
    $f[]="it_CH ISO-8859-1";
    $f[]="it_IT.UTF-8 UTF-8";
    $f[]="it_IT ISO-8859-1";
    $f[]="it_IT@euro ISO-8859-15";
    $f[]="iu_CA UTF-8";
    $f[]="iw_IL.UTF-8 UTF-8";
    $f[]="iw_IL ISO-8859-8";
    $f[]="ja_JP.UTF-8 UTF-8";
    $f[]="ja_JP.EUC-JP EUC-JP";
    $f[]="ka_GE.UTF-8 UTF-8";
    $f[]="ka_GE GEORGIAN-PS";
    $f[]="kk_KZ.UTF-8 UTF-8";
    $f[]="kk_KZ PT154";
    $f[]="kl_GL.UTF-8 UTF-8";
    $f[]="kl_GL ISO-8859-1";
    $f[]="km_KH UTF-8";
    $f[]="kn_IN UTF-8";
    $f[]="ko_KR.UTF-8 UTF-8";
    $f[]="ko_KR.EUC-KR EUC-KR";
    $f[]="ks_IN UTF-8";
    $f[]="ku_TR.UTF-8 UTF-8";
    $f[]="ku_TR ISO-8859-9";
    $f[]="kw_GB.UTF-8 UTF-8";
    $f[]="kw_GB ISO-8859-1";
    $f[]="ky_KG UTF-8";
    $f[]="lg_UG.UTF-8 UTF-8";
    $f[]="lg_UG ISO-8859-10";
    $f[]="li_BE UTF-8";
    $f[]="li_NL UTF-8";
    $f[]="lo_LA UTF-8";
    $f[]="lt_LT.UTF-8 UTF-8";
    $f[]="lt_LT ISO-8859-13";
    $f[]="lv_LV.UTF-8 UTF-8";
    $f[]="lv_LV ISO-8859-13";
    $f[]="mai_IN UTF-8";
    $f[]="mg_MG.UTF-8 UTF-8";
    $f[]="mg_MG ISO-8859-15";
    $f[]="mi_NZ.UTF-8 UTF-8";
    $f[]="mi_NZ ISO-8859-13";
    $f[]="mk_MK.UTF-8 UTF-8";
    $f[]="mk_MK ISO-8859-5";
    $f[]="ml_IN UTF-8";
    $f[]="mn_MN UTF-8";
    $f[]="mr_IN UTF-8";
    $f[]="ms_MY.UTF-8 UTF-8";
    $f[]="ms_MY ISO-8859-1";
    $f[]="mt_MT.UTF-8 UTF-8";
    $f[]="mt_MT ISO-8859-3";
    $f[]="nb_NO.UTF-8 UTF-8";
    $f[]="nb_NO ISO-8859-1";
    $f[]="nds_DE UTF-8";
    $f[]="nds_NL UTF-8";
    $f[]="ne_NP UTF-8";
    $f[]="nl_BE.UTF-8 UTF-8";
    $f[]="nl_BE ISO-8859-1";
    $f[]="nl_BE@euro ISO-8859-15";
    $f[]="nl_NL.UTF-8 UTF-8";
    $f[]="nl_NL ISO-8859-1";
    $f[]="nl_NL@euro ISO-8859-15";
    $f[]="nn_NO.UTF-8 UTF-8";
    $f[]="nn_NO ISO-8859-1";
    $f[]="nr_ZA UTF-8";
    $f[]="nso_ZA UTF-8";
    $f[]="oc_FR.UTF-8 UTF-8";
    $f[]="oc_FR ISO-8859-1";
    $f[]="om_ET UTF-8";
    $f[]="om_KE.UTF-8 UTF-8";
    $f[]="om_KE ISO-8859-1";
    $f[]="or_IN UTF-8";
    $f[]="pa_IN UTF-8";
    $f[]="pa_PK UTF-8";
    $f[]="pap_AN UTF-8";
    $f[]="pl_PL.UTF-8 UTF-8";
    $f[]="pl_PL ISO-8859-2";
    $f[]="pt_BR.UTF-8 UTF-8";
    $f[]="pt_BR ISO-8859-1";
    $f[]="pt_PT.UTF-8 UTF-8";
    $f[]="pt_PT ISO-8859-1";
    $f[]="pt_PT@euro ISO-8859-15";
    $f[]="ro_RO.UTF-8 UTF-8";
    $f[]="ro_RO ISO-8859-2";
    $f[]="ru_RU.UTF-8 UTF-8";
    $f[]="ru_RU.KOI8-R KOI8-R";
    $f[]="ru_RU ISO-8859-5";
    $f[]="ru_RU.CP1251 CP1251";
    $f[]="ru_UA.UTF-8 UTF-8";
    $f[]="ru_UA KOI8-U";
    $f[]="rw_RW UTF-8";
    $f[]="sa_IN UTF-8";
    $f[]="sc_IT UTF-8";
    $f[]="se_NO UTF-8";
    $f[]="si_LK UTF-8";
    $f[]="sid_ET UTF-8";
    $f[]="sk_SK.UTF-8 UTF-8";
    $f[]="sk_SK ISO-8859-2";
    $f[]="sl_SI.UTF-8 UTF-8";
    $f[]="sl_SI ISO-8859-2";
    $f[]="so_DJ.UTF-8 UTF-8";
    $f[]="so_DJ ISO-8859-1";
    $f[]="so_ET UTF-8";
    $f[]="so_KE.UTF-8 UTF-8";
    $f[]="so_KE ISO-8859-1";
    $f[]="so_SO.UTF-8 UTF-8";
    $f[]="so_SO ISO-8859-1";
    $f[]="sq_AL.UTF-8 UTF-8";
    $f[]="sq_AL ISO-8859-1";
    $f[]="sr_ME UTF-8";
    $f[]="sr_RS UTF-8";
    $f[]="sr_RS@latin UTF-8";
    $f[]="ss_ZA UTF-8";
    $f[]="st_ZA.UTF-8 UTF-8";
    $f[]="st_ZA ISO-8859-1";
    $f[]="sv_FI.UTF-8 UTF-8";
    $f[]="sv_FI ISO-8859-1";
    $f[]="sv_FI@euro ISO-8859-15";
    $f[]="sv_SE.UTF-8 UTF-8";
    $f[]="sv_SE ISO-8859-1";
    $f[]="sv_SE.ISO-8859-15 ISO-8859-15";
    $f[]="ta_IN UTF-8";
    $f[]="te_IN UTF-8";
    $f[]="tg_TJ.UTF-8 UTF-8";
    $f[]="tg_TJ KOI8-T";
    $f[]="th_TH.UTF-8 UTF-8";
    $f[]="th_TH TIS-620";
    $f[]="ti_ER UTF-8";
    $f[]="ti_ET UTF-8";
    $f[]="tig_ER UTF-8";
    $f[]="tk_TM UTF-8";
    $f[]="tl_PH.UTF-8 UTF-8";
    $f[]="tl_PH ISO-8859-1";
    $f[]="tn_ZA UTF-8";
    $f[]="tr_CY.UTF-8 UTF-8";
    $f[]="tr_CY ISO-8859-9";
    $f[]="tr_TR.UTF-8 UTF-8";
    $f[]="tr_TR ISO-8859-9";
    $f[]="ts_ZA UTF-8";
    $f[]="tt_RU.UTF-8 UTF-8";
    $f[]="tt_RU@iqtelif.UTF-8 UTF-8";
    $f[]="ug_CN UTF-8";
    $f[]="uk_UA.UTF-8 UTF-8";
    $f[]="uk_UA KOI8-U";
    $f[]="ur_PK UTF-8";
    $f[]="uz_UZ.UTF-8 UTF-8";
    $f[]="uz_UZ ISO-8859-1";
    $f[]="uz_UZ@cyrillic UTF-8";
    $f[]="ve_ZA UTF-8";
    $f[]="vi_VN UTF-8";
    $f[]="vi_VN.TCVN TCVN5712-1";
    $f[]="wa_BE.UTF-8 UTF-8";
    $f[]="wa_BE ISO-8859-1";
    $f[]="wa_BE@euro ISO-8859-15";
    $f[]="wo_SN UTF-8";
    $f[]="xh_ZA.UTF-8 UTF-8";
    $f[]="xh_ZA ISO-8859-1";
    $f[]="yi_US.UTF-8 UTF-8";
    $f[]="yi_US CP1255";
    $f[]="yo_NG UTF-8";
    $f[]="zh_CN.UTF-8 UTF-8";
    $f[]="zh_CN.GB18030 GB18030";
    $f[]="zh_CN.GBK GBK";
    $f[]="zh_CN GB2312";
    $f[]="zh_HK.UTF-8 UTF-8";
    $f[]="zh_HK BIG5-HKSCS";
    $f[]="zh_SG.UTF-8 UTF-8";
    $f[]="zh_SG.GBK GBK";
    $f[]="zh_SG GB2312";
    $f[]="zh_TW.UTF-8 UTF-8";
    $f[]="zh_TW BIG5";
    $f[]="zh_TW.EUC-TW EUC-TW";
    $f[]="zu_ZA.UTF-8 UTF-8";
    $f[]="zu_ZA ISO-8859-1";


    return $f;
}
