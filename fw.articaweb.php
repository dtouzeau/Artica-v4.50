<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.webconsole.params.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["restart-console-js"])){wait_restart_perform();exit;}
if(isset($_GET["restart-schedule-js"])){restart_schedule_js();exit;}
if(isset($_GET["restart-schedule-popup"])){restart_schedule_popup();exit;}
if(isset($_GET["unix-js"])){unix_js();exit;}
if(isset($_GET["speedjs-js"])){speed_js();exit;}
if(isset($_GET["speedjs-popup"])){speed_js_popup();exit;}
if(isset($_POST["RemoveHeadjs"])){speed_js_save();exit;}
if(isset($_GET["reverse-proxy-js"])){reverse_proxy_js();exit;}
if(isset($_GET["reverse-proxy-popup"])){reverse_proxy_popup();exit;}
if(isset($_POST["ArticaWebReverse"])){reverse_proxy_save();exit;}
if(isset($_GET["reverse-api-js"])){reverse_api_js();exit;}
if(isset($_GET["reverse-api-popup"])){reverse_api_popup();exit;}
if(isset($_POST["ArticaWebToAPI"])){reverse_api_save();exit;}

if(isset($_GET["unix-popup"])){unix_popup();exit;}
if(isset($_GET["nic-popup"])){nic_popup();exit;}
if(isset($_GET["nic-js"])){nic_js();exit;}
if(isset($_GET["http-js"])){http_js();exit;}
if(isset($_GET["http-popup"])){http_popup();exit;}
if(isset($_GET["phpfpm-js"])){phpfpm_js();exit;}
if(isset($_GET["phpfpm-popup"])){phpfpm_popup();exit;}
if(isset($_GET["sessions-js"])){sessions_js();exit;}
if(isset($_GET["sessions-popup"])){sessions_popup();exit;}
if(isset($_POST["ArticaWebConsoleRestartScheduleH"])){restart_schedule_save();exit;}

if(isset($_GET["2fa-js"])){_2fa_js();exit;}
if(isset($_GET["2fa-popup"])){_2fa_popup();exit;}

if(isset($_GET["general-js"])){general_js();exit;}
if(isset($_GET["general-popup"])){general_popup();exit;}
if(isset($_POST["PhpFPMArticaMaxChildren"])){phpfpm_save();exit;}
if(isset($_POST["SessionCookieLifetime"])){design_save();exit;}
if(isset($_POST["ArticaHttpUseSSL"])){save();exit;}
if(isset($_POST["LighttpdArticaListenInterface"])){save();exit;}


if(isset($_POST["EnableLockUnixConsole"])){design_save();exit;}
if(isset($_POST["WebConsoleGoogle2FA"])){design_save();exit;}
if(isset($_POST["EnableShowPasswords"])){design_save();exit;}
if(isset($_POST["HideArticaVersion"])){design_save();exit;}
if(isset($_POST["ArticaWebOldLogin"])){design_save();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-static"])){table_static();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["wait-restart-js"])){wait_restart_js();exit;}
if(isset($_GET["wait-restart-popup"])){wait_restart_popup();exit;}

if(isset($_GET["reload-js"])){reload_js();exit;}
if(isset($_GET["restart-js"])){restart_js();exit;}

if(isset($_GET["reload-css"])){reloadcss();exit;}
if(isset($_GET["skin"])){skin();exit;}
if(isset($_GET["file-uploaded"])){fileUpload();exit;}

page();
function fileUpload(){
    $filename=$_GET["file-uploaded"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("customLogoName",$filename);

   $filepath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    $imagedata = file_get_contents($filepath);
    $base64 = base64_encode($imagedata);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("customLogo",$base64);
    unlink($filepath);
}
function wait_restart_js():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{webconsole} ({restarting})","$page?wait-restart-popup=yes");
    return true;

}

function restart_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{webconsole} ({restarting})","$page?wait-restart-popup=yes");
}

function reload_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/webconsole/reload");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->js_error("{error}<br>".json_last_error_msg());
        return true;
    }
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return true;
    }

    $tpl->js_display_results("{success} {reload} {webconsole}");
    echo "LoadAjaxTiny('openldap-status','$page?status=yes');\n";
    return true;
}


function wait_restart_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/restart");
    return true;
}

function GET_INTERFACEIP():string{
    $LighttpdArticaListenInterface  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaListenInterface"));
    if($LighttpdArticaListenInterface==null){
        return $_SERVER["SERVER_ADDR"];
    }
    if(strpos($LighttpdArticaListenInterface,",")>0){
        $tb=explode(",",$LighttpdArticaListenInterface);
        foreach ($tb as $eth) {
            $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/ip/$eth");
            $json=json_decode($data);
            if (json_last_error()> JSON_ERROR_NONE) {
               continue;
            }
            $ipaddr=$json->IpAddr;
            if($ipaddr=="127.0.0.1"){
                continue;
            }

            return $ipaddr;
        }
        return $_SERVER["SERVER_ADDR"];
    }
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/ip/$LighttpdArticaListenInterface");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $_SERVER["SERVER_ADDR"];
    }
    $ipaddr=$json->IpAddr;
    if($ipaddr=="127.0.0.1"){
        return $_SERVER["SERVER_ADDR"];
    }

    return $ipaddr;
}
function wait_restart_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $proto="http";
    $ArticaHttpUseSSL               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));


    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
    $ipaddr=GET_INTERFACEIP();

    if($ArticaHttpUseSSL==1){
        $proto="https";
    }

    $FINAL_URL="$proto://$ipaddr:$ArticaHttpsPort/";

    $f[]="<H1>{restarting} {webconsole} {please_wait}...</H1>";
    $f[]="<H2 class='center' style='margin:20px'>{label_redirect} $FINAL_URL</H2>";
    $f[]="<div id=\"counter\" class='center' style='font-size: 80px;margin:50px'></div>";
    $f[]="";
    $f[]="<script>";
    $f[]="    var count = 15;";
    $f[]="    document.getElementById('counter').textContent = count;";
    $f[]="    var interval = setInterval(function() {";
    $f[]="        count--;";
    $f[]="        document.getElementById('counter').textContent = count;";
    $f[]="        if (count == 10) {";
    $f[]="          Loadjs('$page?restart-console-js=yes');";
    $f[]="        }";
    $f[]="        if (count <= 0) {";
    $f[]="            clearInterval(interval);";
    $f[]="            window.location.href = \"$FINAL_URL\"; // replace with your desired URL";
    $f[]="        }";
    $f[]="    }, 1000); // every second";
    $f[]="</script>";
    $f[]="";
    echo $tpl->_ENGINE_parse_body($f);

    return true;
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));

    $PRIVS=false;
    if($EnableActiveDirectoryFeature==1){
        $PRIVS=true;
    }

    $array["{parameters}"]="$page?table=yes";
    $array["{skin}"]="$page?skin=yes";
    $array["{fingerprint}"]="fw.articaweb.fingerprint.php";

    if($PRIVS) {
        $array["{privileges}"] = "fw.articaweb.privileges.php";
    }
    echo $tpl->tabs_default($array);
}
function general_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{webconsole} ({general})","$page?general-popup=yes");
    return true;
}
function _2fa_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("2FA","$page?2fa-popup=yes");
    return true;
}
function unix_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{EnableLockUnixConsole}","$page?unix-popup=yes");
    return true;
}
function speed_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("Javascript","$page?speedjs-popup=yes");
    return true;
}
function speed_js_popup(){
    $RemoveHeadjs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoveHeadjs"));
    $page=CurrentPageName();
    $tpl=new template_admin();

    if($RemoveHeadjs==0){
        $RemoveHeadjs=1;
    }else{
        $RemoveHeadjs=0;
    }

    $form[]=$tpl->field_checkbox("RemoveHeadjs", "{speedjs}", $RemoveHeadjs);
    $jsafter[]="window.location.reload(true);";
    $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",@implode(";",$jsafter),$security,false);
    return true;
}
function speed_js_save(){
    if($_POST["RemoveHeadjs"]==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RemoveHeadjs",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RemoveHeadjs",1);
    }

}

function restart_schedule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{restart_service} {each_day}","$page?restart-schedule-popup=yes");
    return true;
}
function http_js():bool{
   $page=CurrentPageName();
   $tpl=new template_admin();
   return $tpl->js_dialog1("{http_engine}","$page?http-popup=yes");
}
function reverse_proxy_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{reverse_proxy}","$page?reverse-proxy-popup=yes");
}
function reverse_api_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{inlude_rest_api}","$page?reverse-api-popup=yes");

}
function nic_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{listen_interfaces}","$page?nic-popup=yes");
}
function phpfpm_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{APP_ARTICAPHPFPM}","$page?phpfpm-popup=yes");
}
function sessions_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{sessions_timeout}","$page?sessions-popup=yes");
    return true;
}

function phpfpm_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $PhpFPMArticaMaxChildren=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaMaxChildren"));
    if($PhpFPMArticaMaxChildren==0){
        $PhpFPMArticaMaxChildren=20;
    }
    $PhpFPMArticaMaxRequests=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaMaxRequests"));
    if($PhpFPMArticaMaxRequests==0){
        $PhpFPMArticaMaxRequests=100;
    }

    $PhpFPMArticaFrameWorkMaxChildren=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaFrameWorkMaxChildren"));
    if($PhpFPMArticaFrameWorkMaxChildren==0){
        $PhpFPMArticaFrameWorkMaxChildren=10;
    }
    $PhpFPMArticaFrameWorkMaxRequests=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaFrameWorkMaxRequests"));
    if($PhpFPMArticaFrameWorkMaxRequests==0){
        $PhpFPMArticaFrameWorkMaxRequests=100;
    }
    $tpl->table_form_field_text("{webconsole}","{MaxChildren} $PhpFPMArticaMaxChildren, {max_requests} $PhpFPMArticaMaxRequests",ico_timeout);
    $tpl->table_form_field_text("{artica-framework}","{MaxChildren} $PhpFPMArticaFrameWorkMaxChildren, {max_requests} $PhpFPMArticaFrameWorkMaxRequests",ico_timeout);


    $form[]=$tpl->field_section("{webconsole}");
    $form[]=$tpl->field_numeric("PhpFPMArticaMaxChildren", "{MaxChildren}", $PhpFPMArticaMaxChildren,"{PHP_FCGI_CHILDREN}");
    $form[]=$tpl->field_numeric("PhpFPMArticaMaxRequests", "{max_requests}", $PhpFPMArticaMaxRequests,"{PHP_FCGI_MAX_REQUESTS}");



    $form[]=$tpl->field_section("{artica-framework}");
    $form[]=$tpl->field_numeric("PhpFPMArticaMaxChildren", "{MaxChildren}", $PhpFPMArticaFrameWorkMaxChildren,"{PHP_FCGI_CHILDREN}");
    $form[]=$tpl->field_numeric("PhpFPMArticaMaxRequests", "{max_requests}", $PhpFPMArticaFrameWorkMaxRequests,"{PHP_FCGI_MAX_REQUESTS}");

    $jsafter[]="dialogInstance1.close();";
    $jsafter[]="Loadjs('$page?reload-css=yes')";
    $jsafter[]="LoadAjaxSilent('artica-web','$page?table-static=yes')";

        $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",@implode(";",$jsafter),$security,false);
    return true;
}
function phpfpm_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/fpm/reload");
    return admin_tracks_post("Saving PHP-FPM parameters");
}

function nic_popup():bool{
    $tpl=new template_admin();
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $LighttpdArticaListenInterface  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaListenInterface"));
    $security="AsSystemAdministrator";
    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}

    $form[]=$tpl->field_interfaces_choose("LighttpdArticaListenInterface", "{listen_interface}", $LighttpdArticaListenInterface);
    $form[]=$tpl->field_numeric("ArticaHttpsPort", "{listen_port}", $ArticaHttpsPort);
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",js_after_forms(),$security);

    return true;
}

function http_popup():bool{
    $tpl=new template_admin();

    $ArticaHttpUseSSL               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));
    $LighttpdArticaDisableSSLv2     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaDisableSSLv2"));
    $LighttpdArticaCertificateName  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaCertificateName"));
    $SSLCipherSuite                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSLCipherSuite"));
    $UseHttp2                       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseHttp2"));
    $APP_NGINX_CONSOLE_HTTPV2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_HTTPV2"));




    $security="AsSystemAdministrator";
    $NoXSSProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoXSSProtection"));
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));


    if($SSLCipherSuite==null){$SSLCipherSuite="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}


    $LighttpdAllowAuthenticateScreen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdAllowAuthenticateScreen"));
    $form[]=$tpl->field_checkbox("ArticaHttpUseSSL","{ssl}",$ArticaHttpUseSSL,"LighttpdArticaDisableSSLv2,SSLCipherSuite","");
    $form[]=$tpl->field_certificate("LighttpdArticaCertificateName", "{certificate}",$LighttpdArticaCertificateName);
    $form[]=$tpl->field_checkbox("LighttpdArticaDisableSSLv2","{disableSSLv2},TLS 1.0,1.1",$LighttpdArticaDisableSSLv2,false);
    $form[]=$tpl->field_text("SSLCipherSuite", "{ssl_ciphers}", $SSLCipherSuite);
    if($APP_NGINX_CONSOLE_HTTPV2==0){
        $form[]=$tpl->field_hidden("UseHttp2",0);
    }else {
        $form[] = $tpl->field_checkbox("UseHttp2", "{use} HTTP2", $UseHttp2);
    }
    $form[]=$tpl->field_checkbox("LighttpdArticaClientAuth","{authenticate_ssl_client}",$LighttpdArticaClientAuth,"LighttpdAllowAuthenticateScreen",null,false,$security);
    $form[]=$tpl->field_checkbox("LighttpdAllowAuthenticateScreen","{AllowAuthenticateScreen}",$LighttpdAllowAuthenticateScreen);

    $form[]=$tpl->field_checkbox("NoXSSProtection","{Enable_XSS_Protection}",$NoXSSProtection,false,null);
    $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",js_after_forms(),$security);
    return true;
}

function _2fa_popup():bool{
    $tpl=new template_admin();
    $WebConsoleGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleGoogle2FA"));
    $form[]=$tpl->field_checkbox("WebConsoleGoogle2FA","{PAM_GOOGLE_AUTHENTICATOR}",$WebConsoleGoogle2FA);
    $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",js_after_forms(),$security,false);
    return true;
}
function restart_schedule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ArticaWebConsoleRestartSchedule["H"]=$_POST["ArticaWebConsoleRestartScheduleH"];
    $ArticaWebConsoleRestartSchedule["M"]=$_POST["ArticaWebConsoleRestartScheduleM"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaWebConsoleRestartSchedule",serialize($ArticaWebConsoleRestartSchedule));
    $sock=new sockets();
    $sock->REST_API("/myself/restart");
}
function restart_schedule_popup():bool{
    $tpl=new template_admin();
    $ArticaWebConsoleRestartSchedule=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebConsoleRestartSchedule"));
    if(!isset($ArticaWebConsoleRestartSchedule["H"])){
        $ArticaWebConsoleRestartSchedule["H"]=2;
    }
    if(!isset($ArticaWebConsoleRestartSchedule["M"])){
        $ArticaWebConsoleRestartSchedule["M"]=45;
    }

    for($i=0;$i<24;$i++){
        $text=$i;
        if($i<10){
            $text="0".$i;
        }
        $Hours[$i]=$text;
    }
    for($i=1;$i<60;$i++){
        $text=$i;
        if($i<10){
            $text="0".$i;
        }
        $Mins[$i]=$text;
    }

    $form[]=$tpl->field_array_hash($Hours,"ArticaWebConsoleRestartScheduleH","{hour}",$ArticaWebConsoleRestartSchedule["H"]);
    $form[]=$tpl->field_array_hash($Mins,"ArticaWebConsoleRestartScheduleM","{minutes}",$ArticaWebConsoleRestartSchedule["M"]);
    $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",js_after_forms(),$security,false);
    return true;
}
function unix_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableLockUnixConsole=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLockUnixConsole"));
    $UnixConsolePassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnixConsolePassword"));

    $form[] = $tpl->field_checkbox("EnableLockUnixConsole","{EnableLockUnixConsole}",$EnableLockUnixConsole,
        "UnixConsolePassword","{EnableLockUnixConsole_explain}");
    $form[] = $tpl->field_password2("UnixConsolePassword","{system_console_password}",$UnixConsolePassword);
    $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}","dialogInstance1.close();LoadAjaxSilent('artica-web','$page?table-static=yes')",$security,false);
    return true;
}

function general_popup():bool{
    $tpl=new template_admin();
    $ArticaWebOldLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebOldLogin"));
    $StandardDropDown=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StandardDropDown"));
    $EnableShowPasswords=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShowPasswords"));

    $form[]=$tpl->field_checkbox("ArticaWebOldLogin","{enable_login} v4.30x",$ArticaWebOldLogin);
    $form[]=$tpl->field_checkbox("StandardDropDown","{standard_dropdown}",$StandardDropDown);
    $form[] = $tpl->field_checkbox("EnableShowPasswords","{EnableShowPasswords}",$EnableShowPasswords,false, "{EnableShowPasswords_explain}");

    $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",js_after_forms(),$security,false);

    return true;
}
function sessions_popup():bool{
    $tpl=new template_admin();

    $EnableEaccelerator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableEaccelerator"));
    $EnablePHPXMLRPC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPXMLRPC"));
    $EnablePHPReadline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPReadline"));
    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $SessionInactivitytime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionInactivitytime"));
    $php5UploadMaxFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5UploadMaxFileSize"));
    $php5PostMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5PostMaxSize"));
    $php5MemoryLimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5MemoryLimit"));
    $EnableGoogleCharts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleCharts"));
    if($php5UploadMaxFileSize<1024){$php5UploadMaxFileSize=1024;}
    if($php5PostMaxSize<1024){$php5PostMaxSize=1024;}
    if($php5MemoryLimit<1024){$php5MemoryLimit=1024;}
    $EacceleratorMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EacceleratorMemory"));
    if($EacceleratorMemory==0){$EacceleratorMemory=512;}

    $maxtime_array[0]="{never}";
    $maxtime_array[10]="10 {minutes}";
    $maxtime_array[15]="15 {minutes}";
    $maxtime_array[60]="1 {hour}";
    $maxtime_array[120]="2 {hours}";
    $maxtime_array[380]="3 {hours}";
    $maxtime_array[420]="4 {hours}";
    $maxtime_array[480]="8 {hours}";
    $maxtime_array[720]="12 {hours}";
    $maxtime_array[1440]="1 {day}";
    $maxtime_array[2880]="1 {days}";
    $maxtime_array[10080]="1 {week}";

    $form[]=$tpl->field_array_hash($maxtime_array,"SessionCookieLifetime", "{php5SessionGCMaxlifeTime}", $SessionCookieLifetime);
    $form[]=$tpl->field_numeric("SessionInactivitytime","{inactivity_time} ({minutes})", $SessionInactivitytime);

    $form[]=$tpl->field_section("{additional_modules}");
    $form[]=$tpl->field_checkbox("EnableEaccelerator","{ENABLE_APP_EACCELERATOR2},(opCache)",$EnableEaccelerator,"EacceleratorMemory","{opcache_explain}");
    $form[]=$tpl->field_numeric("EacceleratorMemory", "{memory_cache} (MB)", $EacceleratorMemory,"{opcache_memory_consumption}");



    $form[]=$tpl->field_checkbox("EnablePHPXMLRPC","{APP_XMLRPC_PHP}",$EnablePHPXMLRPC,false,"{APP_XMLRPC_PHP}");
    $form[]=$tpl->field_checkbox("EnablePHPReadline","{APP_READLINE_PHP}",$EnablePHPReadline,false,"{APP_READLINE_PHP}");
    $form[]=$tpl->field_checkbox("EnableGoogleCharts","{APP_GOOGLE_CHARTS}",$EnableGoogleCharts,false,"{APP_GOOGLE_CHARTS}");



    $form[]=$tpl->field_section("{limits}");
    $form[]=$tpl->field_numeric("php5UploadMaxFileSize", "{php5UploadMaxFileSize} (MB)", $php5UploadMaxFileSize);
    $form[]=$tpl->field_numeric("php5PostMaxSize", "{php5PostMaxSize} (MB)", $php5PostMaxSize);
    $form[]=$tpl->field_numeric("php5MemoryLimit", "{php5MemoryLimit} (MB)", $php5MemoryLimit);


    $security="AsSystemAdministrator";
    echo  $tpl->form_outside(null,$form,
        null,"{apply}",js_after_forms(),$security,false);

    return true;
}

//
function js_after_forms():string{
    $page=CurrentPageName();
    $jsafter[]="dialogInstance1.close();";
    $jsafter[]="Loadjs('$page?reload-css=yes')";
    $jsafter[]="LoadAjaxSilent('artica-web','$page?table-static=yes')";
    $jsafter[]="Loadjs('$page?wait-restart-js=yes');";
    return @implode(";",$jsafter);
}


function skin(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $TitleOfArticaPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TitleOfArticaPage"));
    $TextOfArticaPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TextOfArticaPage"));
    if($TitleOfArticaPage==null){$TitleOfArticaPage="%SERVERNAME%";}
    if($TextOfArticaPage==null){$TextOfArticaPage=$tpl->_ENGINE_parse_body("{default_login_explain}");}
    $TitleLogon                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TitleLogon"));
    $LoginTitle2                = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LoginTitle2"));
    $HideArticaVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaVersion"));
    $HideArticaLogo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaLogo"));
    $useCustomLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCustomLogo"));
    $customLogoName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("customLogoName"));
    $HideVirtualizationVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideVirtualizationVersion"));

    $ArticaLoginBackGroundColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLoginBackGroundColor"));
    $ArticaLoginFontColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLoginFontColor"));
    $ArticaBackGroundColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaBackGroundColor"));
    $ArticaFontColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColor"));
    if($ArticaFontColor==null){$ArticaFontColor="#676a6c";}
    if($ArticaBackGroundColor==null){$ArticaBackGroundColor="#ffffff";}
    $ArticaFontColorTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColorTitle"));
    $ArticaFontColorFields=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColorFields"));
    if($TitleLogon==null){$TitleLogon="{Connection}";}
    if($LoginTitle2==null){$LoginTitle2="%SERVERNAME%";}
    if($ArticaFontColorFields==null){$ArticaFontColorFields="#ffffff";}
    $StandardDropDown=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StandardDropDown"));

    $ArticaBackGroundBodyColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaBackGroundBodyColor"));
    if($ArticaBackGroundBodyColor==null){$ArticaBackGroundBodyColor="#f3f3f4";}
    if($ArticaLoginBackGroundColor==null){$ArticaLoginBackGroundColor="#283437";}
    if($ArticaLoginFontColor==null){$ArticaLoginFontColor="#a7b1c2";}
    if($ArticaFontColorTitle==null){$ArticaFontColorTitle="#ffffff";}


    $form[]=$tpl->field_section("{LOGON_PAGE}");
    $form[]=$tpl->field_checkbox("HideArticaLogo","{remove_artica_logo}",$HideArticaLogo);
    $form[]=$tpl->field_checkbox("useCustomLogo","{use_custom_logo}",$useCustomLogo,"customLogo");
    $form[]=$tpl->field_button_upload("{logo}","file-uploaded",$customLogoName);
    $form[]=$tpl->field_checkbox("HideArticaVersion","{remove_artica_version}",$HideArticaVersion);
    $form[]=$tpl->field_checkbox("HideVirtualizationVersion","{HideVirtualizationVersion}",$HideVirtualizationVersion);


    $form[]=$tpl->field_text("TitleLogon","{page_title} 2",$TitleLogon);
    $form[]=$tpl->field_text("LoginTitle2","{subtitle}",$LoginTitle2);
    $form[]=$tpl->field_color("ArticaLoginFontColor","{font_color} ({subtitle})",$ArticaLoginFontColor);
    $form[]=$tpl->field_textareacode("TextOfArticaPage","{description}",$TextOfArticaPage);
    $form[]=$tpl->field_color("ArticaLoginBackGroundColor","{background_color}",$ArticaLoginBackGroundColor);
    $form[]=$tpl->field_color("ArticaFontColorTitle","{font_color}",$ArticaFontColorTitle);
    $form[]=$tpl->field_color("ArticaFontColorFields","{font_color} ({fields})",$ArticaFontColorFields);

    $form[]=$tpl->field_section("{webconsole} ({general})");
    $form[]=$tpl->field_text("TitleOfArticaPage","{page_title}",$TitleOfArticaPage);
    $form[]=$tpl->field_color("ArticaBackGroundColor","{background_color}",$ArticaBackGroundColor);
    $form[]=$tpl->field_color("ArticaBackGroundBodyColor","{html_body}",$ArticaBackGroundBodyColor);
    $form[]=$tpl->field_color("ArticaFontColor","{font_color}",$ArticaFontColor);
    $form[]=$tpl->field_checkbox("StandardDropDown","{standard_dropdown}",$StandardDropDown);
    $html[]=$tpl->form_outside(null, @implode("\n", $form),
        null,"{apply}","Loadjs('$page?reload-css=yes');",$security,true);

    echo $tpl->_ENGINE_parse_body($html);
}


function reloadcss(){
    header("content-type: application/x-javascript");
echo "
        (function() {
            var h, a, f;
		a = document.getElementsByTagName('link');
		for (h = 0; h < a.length; h++) {
            f = a[h];
            if (f.rel.toLowerCase().match(/stylesheet/) && f.href) {
                var g = f.href.replace(/(&|\?)rnd=\d+/, '');
				f.href = g + (g.match(/\?/) ? '&' : '?');
				f.href += 'rnd=' + (new Date().valueOf());
			}
		} // for
	})()
	
	";

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION");
    $html=$tpl->page_header("{web_interface_settings} v$VER",
        "far fa-browser","{web_interface_settings_text}","$page?tabs=yes","webconsole"
    ,"progress-articaweb-restart",false,"table-loader-articaweb-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica Web Console",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function design_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

    if (strlen($_POST["customLogo"])<2){
        $_POST["useCustomLogo"]=0;
    }

    if(isset($_POST["UnixConsolePassword"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnixConsolePassword",$_POST["UnixConsolePassword"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unix/console");
    }
}
function table():bool{
    $page=CurrentPageName();
    echo "<div id='artica-web'></div><script>LoadAjaxSilent('artica-web','$page?table-static=yes');</script>";
    return true;

}
function reverse_proxy_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save Web console reverse-proxy compliance settings");
}
function reverse_api_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    $security                   = "AsSystemAdministrator";

    $ArticaWebToAPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebToAPI"));
    $ArticaWebToAPIPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebToAPIPath");
    if(strlen($ArticaWebToAPIPath)<3){
        $ArticaWebToAPIPath="webapi";
    }
    $form[]=$tpl->field_checkbox("ArticaWebToAPI","{inlude_rest_api}",$ArticaWebToAPI);
    $form[]=$tpl->field_text("ArticaWebToAPIPath","{path}",$ArticaWebToAPIPath);

    $jsafter[]="dialogInstance1.close();";
    $jsafter[]="Loadjs('$page?reload-css=yes')";
    $jsafter[]="LoadAjaxSilent('artica-web','$page?table-static=yes')";

    echo  $tpl->form_outside(null,$form,
        "{inlude_rest_api_explain}","{apply}",@implode(";",$jsafter),$security,false);
    return true;
}
function reverse_api_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/reload");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error("{error}<br>".json_last_error_msg());
        return true;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return true;
    }
    return admin_tracks_post("Save Web console reverse-proxy to WEB API service (enabled={$_POST["ArticaWebToAPI"]}) to {$_POST["ArticaWebToAPIPath"]}");
}
function reverse_proxy_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    $security                   = "AsSystemAdministrator";
    $ArticaWebReverse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReverse"));
    $ArticaWebReversePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReversePort"));
    $ArticaWebReversePProto=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReversePProto"));
    $ArticaWebReverseInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReverseInterface"));
    if($ArticaWebReversePort==0){$ArticaWebReversePort=8999;}

    $form[]=$tpl->field_checkbox("ArticaWebReverse","{use_reverse}",$ArticaWebReverse);
    $form[]=$tpl->field_interfaces("ArticaWebReverseInterface","{listen_interface}",$ArticaWebReverseInterface);
    $form[]=$tpl->field_numeric("ArticaWebReversePort","{listen_port}",$ArticaWebReversePort);
    $form[]=$tpl->field_checkbox("ArticaWebReversePProto","{proxy_protocol}",$ArticaWebReversePProto);
    echo  $tpl->form_outside(null,$form,
        "{articaweb_reverse_explain}","{apply}",js_after_forms(),$security,false);
    return true;
}
function table_static(){
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    $q                          = new lib_sqlite("/home/artica/SQLITE/webconsole.db");

        $verhttp2supported=1221; //Only nginx version >=1.22.1 support http2
    $http2disabled=true;
    $webconsoleversion    = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION"));
    $webconsoleversion = str_replace(".", "", $webconsoleversion);
    $webconsoleversion = intval($webconsoleversion);
    if($webconsoleversion>=$verhttp2supported){
        $http2disabled=false;
    }
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:450px;vertical-align:top'>";
    $html[]="<div id='openldap-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";



    $ArticaHttpUseSSL               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $LighttpdArticaListenInterface  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaListenInterface"));
    $LighttpdArticaDisableSSLv2     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaDisableSSLv2"));
    $LighttpdArticaCertificateName  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaCertificateName"));
    $SSLCipherSuite                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSLCipherSuite"));
    $RemoveHeadjs= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoveHeadjs"));

    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
    $EnablePHPXMLRPC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPXMLRPC"));
    $EnablePHPReadline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPReadline"));

    $php5UploadMaxFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5UploadMaxFileSize"));
    $php5PostMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5PostMaxSize"));
    $php5MemoryLimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5MemoryLimit"));
    $EnableShowPasswords=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShowPasswords"));
    $EnableGoogleCharts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleCharts"));
    $ArticaWebOldLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebOldLogin"));
    $StandardDropDown=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StandardDropDown"));

    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $SessionInactivitytime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionInactivitytime"));
    $EnableLockUnixConsole=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLockUnixConsole"));
    $NoXSSProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoXSSProtection"));
    $UseHttp2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseHttp2"));
    if($php5UploadMaxFileSize<1024){$php5UploadMaxFileSize=1024;}
    if($php5PostMaxSize<1024){$php5PostMaxSize=1024;}
    if($php5MemoryLimit<1024){$php5MemoryLimit=1024;}

    $ArticaWebConsoleRestartSchedule=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebConsoleRestartSchedule"));
    if(!$ArticaWebConsoleRestartSchedule){$ArticaWebConsoleRestartSchedule=array();}
    if(!isset($ArticaWebConsoleRestartSchedule["H"])){
        $ArticaWebConsoleRestartSchedule["H"]=2;
    }
    if(!isset($ArticaWebConsoleRestartSchedule["M"])){
        $ArticaWebConsoleRestartSchedule["M"]=45;
    }




        $PhpFPMArticaMaxChildren=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaMaxChildren"));
        if($PhpFPMArticaMaxChildren==0){
            $PhpFPMArticaMaxChildren=30;
        }
        $PhpFPMArticaMaxRequests=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaMaxRequests"));
        if($PhpFPMArticaMaxRequests==0){
            $PhpFPMArticaMaxRequests=100;
        }

    $PhpFPMArticaFrameWorkMaxChildren=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaFrameWorkMaxChildren"));
    if($PhpFPMArticaFrameWorkMaxChildren==0){
        $PhpFPMArticaFrameWorkMaxChildren=50;
    }
    $PhpFPMArticaFrameWorkMaxRequests=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PhpFPMArticaFrameWorkMaxRequests"));
    if($PhpFPMArticaFrameWorkMaxRequests==0){
        $PhpFPMArticaFrameWorkMaxRequests=100;
    }

    //daemons_number //lighttp_max_proc
    // MaxChildren =  {PHP_FCGI_CHILDREN}
    //max_requests = PHP_FCGI_MAX_REQUESTS


    if($SSLCipherSuite==null){$SSLCipherSuite="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}

    $security="AsSystemAdministrator";



    $tpl->table_form_section("{webconsole} ({general})");
    $tpl->table_form_field_js("Loadjs('$page?general-js=yes')");
    $tpl->table_form_field_bool("{enable_login} v4.30x",$ArticaWebOldLogin,ico_params);
    $tpl->table_form_field_bool("{standard_dropdown}",$StandardDropDown,ico_field);
    $tpl->table_form_field_bool("{EnableShowPasswords}",$EnableShowPasswords,ico_field);



    $tpl->table_form_field_js("Loadjs('$page?speedjs-js=yes')");
    if($RemoveHeadjs==0) {
        $tpl->table_form_field_bool("{speedjs}", 1, ico_field);
    }else{
        $tpl->table_form_field_bool("{speedjs}", 0, ico_field);
    }




    $tpl->table_form_section("{APP_ARTICAPHPFPM}");
    $tpl->table_form_field_js("Loadjs('$page?phpfpm-js=yes')");
     $tpl->table_form_field_text("{webconsole}","{MaxChildren} $PhpFPMArticaMaxChildren, {max_requests} $PhpFPMArticaMaxRequests",ico_timeout);
    $tpl->table_form_field_text("{artica-framework}","{MaxChildren} $PhpFPMArticaFrameWorkMaxChildren, {max_requests} $PhpFPMArticaFrameWorkMaxRequests",ico_timeout);



    $tpl->table_form_section("{authentication}");


    $AuthLinkRestrictions=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthLinkRestrictions")));
    if(!is_array($AuthLinkRestrictions)){$AuthLinkRestrictions=array();}
    if(count($AuthLinkRestrictions)==0){
        $AuthLinkRestrictions[] = "192.168.0.0/16";
        $AuthLinkRestrictions[] = "10.0.0.0/8";
        $AuthLinkRestrictions[] = "172.16.0.0/12";
    }
    $tpl->table_form_field_js("Loadjs('fw.articaweb.authlink.restrictions.php?ClusterLists-js=yes')",$security);
    $tpl->table_form_field_text("{clients_restrictions} (AUTH LINK)",
        @implode(", ",$AuthLinkRestrictions),ico_user_lock);

    $WebConsoleGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleGoogle2FA"));

    $tpl->table_form_field_js("Loadjs('$page?2fa-js=yes')",$security);
    $tpl->table_form_field_bool("{PAM_GOOGLE_AUTHENTICATOR}",$WebConsoleGoogle2FA,ico_user_lock);
    $tpl->table_form_field_js("Loadjs('$page?unix-js=yes')");
    $tpl->table_form_field_bool("{EnableLockUnixConsole}",$EnableLockUnixConsole,ico_user_lock);

    $tpl->table_form_section("{http_engine}");
    $ArticaWebReverse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReverse"));
    $tpl->table_form_field_js("Loadjs('$page?reverse-proxy-js=yes')",$security);


    if($ArticaWebReverse==0) {
        $tpl->table_form_field_bool("{use_reverse}", $ArticaWebReverse, ico_arrow_right);
    }else{
        $ArticaWebReversePProtoT="";
        $ArticaWebReversePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReversePort"));
        $ArticaWebReversePProto=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReversePProto"));
        $ArticaWebReverseInterface=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebReverseInterface"));
        if(strlen($ArticaWebReverseInterface)<3){
            $ArticaWebReverseInterface="*";;
        }
        if($ArticaWebReversePProto==1){
            $ArticaWebReversePProtoT=" <small>({proxy_protocol})</small>";
        }
        $tpl->table_form_field_text("{use_reverse}","$ArticaWebReverseInterface:$ArticaWebReversePort$ArticaWebReversePProtoT",ico_arrow_right);

    }
    $ArticaWebToAPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebToAPI"));
    $ArticaWebToAPIPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebToAPIPath");
    $tpl->table_form_field_js("Loadjs('$page?reverse-api-js=yes')",$security);
    if(strlen($ArticaWebToAPI)<3){
        $ArticaWebToAPIPath="webapi";
    }
    if($ArticaWebToAPI==0) {
        $tpl->table_form_field_bool("{inlude_rest_api}", 0, ico_arrow_right);
    }else{
        $tpl->table_form_field_text("{inlude_rest_api}","<span style='text-transform: none'>/$ArticaWebToAPIPath</span>", ico_arrow_right);
    }



    $tpl->table_form_field_js("Loadjs('$page?restart-schedule-js=yes')",$security);
    $tpl->table_form_field_text("{restart_service}","{each_day} {$ArticaWebConsoleRestartSchedule["H"]}H{$ArticaWebConsoleRestartSchedule["M"]}",ico_clock);


    $sql="CREATE TABLE IF NOT EXISTS `ngx_stream_access_module` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zorder` INTEGER,
		`serviceid` INTEGER,
		`allow` INTEGER,
		`item` text
	)";
    $q->QUERY_SQL($sql);
    if(!$q->INDEX_EXISTS("ngx_stream_access_module","KeyService")) {
        $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyService ON ngx_stream_access_module (serviceid,zorder)");
    }
    $maxtime_array[0]="{never}";
    $maxtime_array[10]="10 {minutes}";
    $maxtime_array[15]="15 {minutes}";
    $maxtime_array[60]="1 {hour}";
    $maxtime_array[120]="2 {hours}";
    $maxtime_array[380]="3 {hours}";
    $maxtime_array[420]="4 {hours}";
    $maxtime_array[480]="8 {hours}";
    $maxtime_array[720]="12 {hours}";
    $maxtime_array[1440]="1 {day}";
    $maxtime_array[2880]="1 {days}";
    $maxtime_array[10080]="1 {week}";


    $ngx_stream_access_module=$q->COUNT_ROWS("ngx_stream_access_module")." {items}";
    $ARTICA_WEB_SAVED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_WEB_SAVED"));


    $tpl->table_form_field_js("Loadjs('$page?http-js=yes')");
    if($ARTICA_WEB_SAVED>0){
        $tpl->table_form_field_text("{last_save}",$tpl->time_to_date($ARTICA_WEB_SAVED),ico_clock);
    }
    $ssl="(";
    if($ArticaHttpUseSSL==1){
        $ssl="(ssl $LighttpdArticaCertificateName";
    }
    if($LighttpdArticaDisableSSLv2==1){
        $ssl="$ssl, {disableSSLv2}";
    }
    $ssl=$ssl.")";


    $tpl->table_form_field_js("Loadjs('$page?nic-js=yes')");
    if($LighttpdArticaListenInterface==null){$LighttpdArticaListenInterface="{all}";}
    $tpl->table_form_field_text("{listen_interface}","$LighttpdArticaListenInterface:$ArticaHttpsPort $ssl",ico_nic);


    $tpl->table_form_field_js("Loadjs('$page?http-js=yes')");
    if($ArticaHttpUseSSL==1){
        $SSLCipherSuite=substr($SSLCipherSuite,0,45)."...";
        $tpl->table_form_field_text("{ssl_ciphers}",$SSLCipherSuite,ico_ssl);
    }

    $APP_NGINX_CONSOLE_HTTPV2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_HTTPV2"));

    $APP_NGINX_CONSOLE_SUB_MODULE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_SUB_MODULE"));

    if($APP_NGINX_CONSOLE_HTTPV2==1) {
        $tpl->table_form_field_bool("{use} HTTP2", $UseHttp2, ico_ssl);
    }else{
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{use} HTTP2",0, ico_ssl);
    }
    if($APP_NGINX_CONSOLE_SUB_MODULE==1) {
        $tpl->table_form_field_bool("{ngx_http_sub_module}", 1, ico_form);
    }else{
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{ngx_http_sub_module}",0, ico_form);
    }


$tpl->table_form_field_js("Loadjs('$page?http-js=yes')");
    $tpl->table_form_field_bool("{Enable_XSS_Protection}",$NoXSSProtection,ico_shield);

    $LighttpdAllowAuthenticateScreen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdAllowAuthenticateScreen"));
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
    $tpl->table_form_field_bool("{authenticate_ssl_client}",$LighttpdArticaClientAuth,ico_ssl);
    if($LighttpdArticaClientAuth==1){
        $tpl->table_form_field_js("Loadjs('fw.articaweb.cert.php')");
        $tpl->table_form_field_button("{members}","{certificates}",ico_ssl);
        $tpl->table_form_field_js("Loadjs('$page?http-js=yes')");
        $tpl->table_form_field_bool("{AllowAuthenticateScreen}",$LighttpdAllowAuthenticateScreen,ico_ssl);
    }

    //GEO_IP_COUNTRIES_LIST
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    $PHP_GEOIP_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    if($PHP_GEOIP_INSTALLED==0){
        $EnableGeoipUpdate=0;
    }
    if($EnableGeoipUpdate==0){
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{limit_countries}",$EnableGeoipUpdate,ico_earth);
    }else{
        $tpl->table_form_field_js("Loadjs('fw.articaweb.geoip.php')","AsSystemAdministrator");
        $ArticaWebDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebDenyCountries")));
        $c=0;
        foreach ($ArticaWebDenyCountries as $CN=>$none){
            if (strlen($CN)<2){
                continue;
            }
            $c++;
        }


        if($c==0){
            $tpl->table_form_field_bool("{limit_countries}",0,ico_earth);
        }else{
            $tpl->table_form_field_text("{limit_countries}","$c {countries}",ico_earth);
        }
    }


    $tpl->table_form_field_js("Loadjs('fw.articaweb.ngx_stream_access_module.php');");
    $tpl->table_form_field_button("{limit_access}","{manage} <span id='CountOfStreamAccessModule'>$ngx_stream_access_module</span>",ico_shield);


    //$form[]=$tpl->field_checkbox("DisableMemCacheSettings","{DISABLE_MEMCACHE_SETTINGS}",$DisableMemCacheSettings,false,"{DISABLE_MEMCACHE_SETTINGS_EXPLAIN}");

    $tpl->table_form_field_js("s_PopUp('/phpinfos','800','800')");
    $tpl->table_form_field_button("{additional_modules}","{more_infos}",ico_params);

    $tpl->table_form_section("{sessions_timeout}");

    $tpl->table_form_field_js("Loadjs('$page?sessions-js=yes')");

    $tpl->table_form_field_text("{php5UploadMaxFileSize}","{$php5UploadMaxFileSize}MB",ico_timeout);
    $tpl->table_form_field_text("{php5PostMaxSize}","{$php5PostMaxSize}MB",ico_timeout);
    $tpl->table_form_field_text("{php5MemoryLimit}","{$php5MemoryLimit}MB",ico_timeout);
    $tpl->table_form_field_text("{php5SessionGCMaxlifeTime}",$maxtime_array[$SessionCookieLifetime],ico_timeout);

    if($SessionInactivitytime==0){
        $SessionInactivitytime="{never}";
    }else{
        $SessionInactivitytime="$SessionInactivitytime {minutes}";
    }

    $tpl->table_form_field_text("{inactivity_time}","$SessionInactivitytime",ico_timeout);
    $tpl->table_form_field_bool("{APP_XMLRPC_PHP}",$EnablePHPXMLRPC,ico_params);
    $tpl->table_form_field_bool("{APP_READLINE_PHP}",$EnablePHPReadline,ico_params);
    $tpl->table_form_field_bool("{APP_GOOGLE_CHARTS}",$EnableGoogleCharts,ico_params);

    $VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION");


    $topbuttons[] = array("Loadjs('$page?reload-js=yes')",ico_refresh,"{reload}");
    $topbuttons[] = array("Loadjs('$page?restart-js=yes')",ico_refresh,"{restart}");

    $htopwebrestart=$tpl->framework_buildjs("/system/htopweb/restart","htopweb.restart.progress","htopweb.restart.progress.log","progress-articaweb-restart");
    $topbuttons[] = array($htopwebrestart,ico_refresh,"{restart} HTOPWEB");


    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM webconsole_events");
    $tcount=intval($ligne["tcount"]);
    if($tcount>0){
        $tcount=$tpl->FormatNumber($tcount);
        $topbuttons[] = array("Loadjs('fw.articaweb.events.php')",ico_eye,"$tcount {events}");
    }
    $TINY_ARRAY["TITLE"]="{web_interface_settings} v$VER";
    $TINY_ARRAY["ICO"]="far fa-browser";
    $TINY_ARRAY["EXPL"]="{web_interface_settings_text}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>LoadAjaxTiny('openldap-status','$page?status=yes');\n$jstiny</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function table_form(){
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    $q                          = new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $DisableMemCacheSettings    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMemCacheSettings"));

    $http2disabled=false;

    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:450px;vertical-align:top'>";
    $html[]="<div id='openldap-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";



    $ArticaHttpUseSSL               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));
    $ArticaHttpsPort                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $LighttpdArticaListenInterface  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaListenInterface"));
    $LighttpdArticaDisableSSLv2     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaDisableSSLv2"));
    $LighttpdArticaCertificateName  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaCertificateName"));
    $SSLCipherSuite                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSLCipherSuite"));

    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}

    $EnableEaccelerator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableEaccelerator"));
    $EnablePHPXMLRPC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPXMLRPC"));
    $EnablePHPReadline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPReadline"));

    $php5UploadMaxFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5UploadMaxFileSize"));
    $php5PostMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5PostMaxSize"));
    $php5MemoryLimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5MemoryLimit"));
    $EnableShowPasswords=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShowPasswords"));
    $EnableGoogleCharts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleCharts"));
    $ArticaWebOldLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebOldLogin"));

    $SessionCookieLifetime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    $SessionInactivitytime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionInactivitytime"));
    $EnableLockUnixConsole=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLockUnixConsole"));
    $UnixConsolePassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnixConsolePassword"));
    $NoXSSProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoXSSProtection"));
    $UseHttp2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseHttp2"));
        if($php5UploadMaxFileSize<1024){$php5UploadMaxFileSize=1024;}
    if($php5PostMaxSize<1024){$php5PostMaxSize=1024;}
    if($php5MemoryLimit<1024){$php5MemoryLimit=1024;}
    $EacceleratorMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EacceleratorMemory"));
    if($EacceleratorMemory==0){$EacceleratorMemory=512;}

    if($SSLCipherSuite==null){$SSLCipherSuite="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}

    $security="AsSystemAdministrator";


    $form[]=$tpl->field_checkbox("ArticaWebOldLogin","{enable_login} v4.30x",$ArticaWebOldLogin);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),
        null,"{apply}","Loadjs('$page?reload-css=yes');",$security,true);

    $AuthLinkRestrictions=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthLinkRestrictions")));
    if(!is_array($AuthLinkRestrictions)){$AuthLinkRestrictions=array();}
    if(count($AuthLinkRestrictions)==0){
        $AuthLinkRestrictions[] = "192.168.0.0/16";
        $AuthLinkRestrictions[] = "10.0.0.0/8";
        $AuthLinkRestrictions[] = "172.16.0.0/12";
    }
    $tpl    = new template_admin();
    $form   = array();

    $form[]=$tpl->field_none_bt("AuthLinkRestrictions","{clients_restrictions} (AUTH LINK)",@implode(", ",$AuthLinkRestrictions),"{edit}","Loadjs('fw.articaweb.authlink.restrictions.php?ClusterLists-js=yes')","{elastic_cluster_explain}");

    $WebConsoleGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleGoogle2FA"));

    $form[]=$tpl->field_checkbox("WebConsoleGoogle2FA","{PAM_GOOGLE_AUTHENTICATOR}",$WebConsoleGoogle2FA);

    $form[] = $tpl->field_checkbox("EnableLockUnixConsole","{EnableLockUnixConsole}",$EnableLockUnixConsole,
              "UnixConsolePassword","{EnableLockUnixConsole_explain}");
    $form[] = $tpl->field_password2("UnixConsolePassword","{system_console_password}",$UnixConsolePassword);
    $form[] = $tpl->field_checkbox("EnableShowPasswords","{EnableShowPasswords}",$EnableShowPasswords,false,
              "{EnableShowPasswords_explain}");
    $html[] = $tpl->form_outside("{passwords_policy}", @implode("\n", $form),
              null,"{apply}","LoadAjaxTiny('openldap-status','$page?status=yes');",$security,true);



    $tpl    = new template_admin();
    $form   = array();
    $form[] = $tpl->field_section("{http_engine}");


    $sql="CREATE TABLE IF NOT EXISTS `ngx_stream_access_module` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zorder` INTEGER,
		`serviceid` INTEGER,
		`allow` INTEGER,
		`item` text
	)";
    $q->QUERY_SQL($sql);
    if(!$q->INDEX_EXISTS("ngx_stream_access_module","KeyService")) {
        $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyService ON ngx_stream_access_module (serviceid,zorder)");
    }
    $maxtime_array[0]="{never}";
    $maxtime_array[10]="10 {minutes}";
    $maxtime_array[15]="15 {minutes}";
    $maxtime_array[60]="1 {hour}";
    $maxtime_array[120]="2 {hours}";
    $maxtime_array[380]="3 {hours}";
    $maxtime_array[420]="4 {hours}";
    $maxtime_array[480]="8 {hours}";
    $maxtime_array[720]="12 {hours}";
    $maxtime_array[1440]="1 {day}";
    $maxtime_array[2880]="1 {days}";
    $maxtime_array[10080]="1 {week}";


    $ngx_stream_access_module=$q->COUNT_ROWS("ngx_stream_access_module")." {items}";
    $ARTICA_WEB_SAVED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_WEB_SAVED"));

    if($ARTICA_WEB_SAVED>0){
        $form[]=$tpl->field_info("ARTICA_WEB_SAVED","{last_save}",$tpl->time_to_date($ARTICA_WEB_SAVED));
    }else{
        $form[]=$tpl->field_info("ARTICA_WEB_SAVED","{last_save}","{never}");
    }

    $form[]=$tpl->field_numeric("ArticaHttpsPort", "{listen_port}", $ArticaHttpsPort);
    $form[]=$tpl->field_interfaces("LighttpdArticaListenInterface", "{listen_interface}", $LighttpdArticaListenInterface);

    $form[]=$tpl->field_checkbox("ArticaHttpUseSSL","{ssl}",$ArticaHttpUseSSL,"LighttpdArticaDisableSSLv2,SSLCipherSuite","");
    $form[]=$tpl->field_certificate("LighttpdArticaCertificateName", "{certificate}",$LighttpdArticaCertificateName);
    $form[]=$tpl->field_checkbox("LighttpdArticaDisableSSLv2","{disableSSLv2},TLS 1.0,1.1",$LighttpdArticaDisableSSLv2,false);
    $form[]=$tpl->field_text("SSLCipherSuite", "{ssl_ciphers}", $SSLCipherSuite);

    $LighttpdAllowAuthenticateScreen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdAllowAuthenticateScreen"));
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
    $bts=array("BUTTON"=>true,"BUTTON_JS"=>"Loadjs('fw.articaweb.cert.php')","BUTTON_CAPTION"=>"{certificate}");
    $form[]=$tpl->field_checkbox("LighttpdArticaClientAuth","{authenticate_ssl_client}",$LighttpdArticaClientAuth,"LighttpdAllowAuthenticateScreen",$bts,false,$security);
    $form[]=$tpl->field_checkbox("LighttpdAllowAuthenticateScreen","{AllowAuthenticateScreen}",$LighttpdAllowAuthenticateScreen);


    $form[]=$tpl->td_button("{limit_access}", "{manage}", "Loadjs('fw.articaweb.ngx_stream_access_module.php');","<span id='CountOfStreamAccessModule'>$ngx_stream_access_module</span>");

    $APP_NGINX_CONSOLE_HTTPV2    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_HTTPV2"));



    $form[]=$tpl->field_checkbox("DisableMemCacheSettings","{DISABLE_MEMCACHE_SETTINGS}",$DisableMemCacheSettings,false,"{DISABLE_MEMCACHE_SETTINGS_EXPLAIN}");

    if($APP_NGINX_CONSOLE_HTTPV2==1) {
        $form[] = $tpl->field_checkbox("UseHttp2", "{use} HTTP2", $UseHttp2);
    }else{
        $form[]=$tpl->field_hidden("UseHttp2", 0);
    }

    $form[]=$tpl->field_checkbox("NoXSSProtection","{Enable_XSS_Protection}",$NoXSSProtection,false,null);

    $form[]=$tpl->field_section("{sessions_timeout}");

    $form[]=$tpl->field_array_hash($maxtime_array,"SessionCookieLifetime", "{php5SessionGCMaxlifeTime}", $SessionCookieLifetime);
    $form[]=$tpl->field_numeric("SessionInactivitytime","{inactivity_time} ({minutes})", $SessionInactivitytime);




    $form[]=$tpl->field_section("{additional_modules}");
    if(method_exists($tpl,"field_button")) {
        $form[] = $tpl->field_button("{more_infos}", "{SAMBA_ERROR_CLICK}", "s_PopUp('/phpinfos','800','800')");
    }
    //$form[]=$tpl->field_checkbox("EnableEaccelerator","{ENABLE_APP_EACCELERATOR2},(opCache)",$EnableEaccelerator,"EacceleratorMemory","{opcache_explain}");
    $form[]=$tpl->field_numeric("EacceleratorMemory", "{memory_cache} (MB)", $EacceleratorMemory,"{opcache_memory_consumption}");



    $form[]=$tpl->field_checkbox("EnablePHPXMLRPC","{APP_XMLRPC_PHP}",$EnablePHPXMLRPC,false,"{APP_XMLRPC_PHP}");
    $form[]=$tpl->field_checkbox("EnablePHPReadline","{APP_READLINE_PHP}",$EnablePHPReadline,false,"{APP_READLINE_PHP}");
    $form[]=$tpl->field_checkbox("EnableGoogleCharts","{APP_GOOGLE_CHARTS}",$EnableGoogleCharts,false,"{APP_GOOGLE_CHARTS}");



    $form[]=$tpl->field_section("{limits}");
    $form[]=$tpl->field_numeric("php5UploadMaxFileSize", "{php5UploadMaxFileSize} (MB)", $php5UploadMaxFileSize);
    $form[]=$tpl->field_numeric("php5PostMaxSize", "{php5PostMaxSize} (MB)", $php5PostMaxSize);
    $form[]=$tpl->field_numeric("php5MemoryLimit", "{php5MemoryLimit} (MB)", $php5MemoryLimit);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/fpm.reload.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/fpm.reload.progress.txt";
    $ARRAY["CMD"]="nginx.php?reconfigre-php-fpm=yes";
    $ARRAY["TITLE"]="{apply_parameters}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-articaweb-restart')";

    $html[]=$tpl->form_outside("{general_settings}", @implode("\n", $form),
        "{artica_web_warning}","{apply}",$jsrestart,$security);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>LoadAjaxTiny('openldap-status','$page?status=yes');</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function save(){
    $_POST["ARTICA_WEB_SAVED"]=time();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(isset($_POST["SessionInactivitytime"])) {
        $_POST["SessionInactivitytime"] = intval($_POST["SessionInactivitytime"]);
        $_POST["SessionCookieLifetime"] = intval($_POST["SessionCookieLifetime"]);
    }
    $_SESSION["SESSARTTIMEOUT"] = time();


    $APP_NGINX_CONSOLE_HTTPV2    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_HTTPV2"));

    if($APP_NGINX_CONSOLE_HTTPV2==1){
        $_POST["UseHttp2"]=0;
    }


    if(isset($_POST["LighttpdArticaCertificateName"])){
        writelogs("LighttpdArticaCertificateName={$_POST["LighttpdArticaCertificateName"]}",__FUNCTION__,__FILE__,__LINE__);
        if(strlen($_POST["LighttpdArticaCertificateName"])>3){
            $certname=$_POST["LighttpdArticaCertificateName"];
            $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/nginxcheck/$certname/0");
            $json=json_decode($data);
            if (json_last_error()> JSON_ERROR_NONE) {
                echo $tpl->div_error(json_last_error_msg());
                return false;
            }
            if(!$json->Status){
                echo $tpl->post_error($json->Error);
                return false;
            }
        }
    }
    $tpl->SAVE_POSTs();

    if(isset($_POST["LighttpdArticaClientAuth"])) {
        $LighttpdArticaClientAuth = intval($_POST["LighttpdArticaClientAuth"]);
        if ($LighttpdArticaClientAuth == 1) {
            $LighttpdServerCertificate = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdServerCertificate"));
            if (strlen($LighttpdServerCertificate) < 20) {
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("webconsole.php?server-certificate=yes");
            }
        }
    }


return true;
}

function status(){
    $sock=new sockets();
    $tpl                        = new template_admin();
    $page                       = CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/webconsole/status");
    $ini                        = new Bs_IniHandler(PROGRESS_DIR."/articaweb.status");
    $WebConsoleGoogle2FA        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleGoogle2FA"));

    echo $tpl->SERVICE_STATUS($ini, "APP_ARTICAWEBCONSOLE");
    echo $tpl->SERVICE_STATUS($ini, "ARTICA_PHPFPM");

    echo "<div id='client-certificate-status'></div>
    <script>LoadAjaxSilent('client-certificate-status','fw.articaweb.status.php?status=yes');</script>";

    if($WebConsoleGoogle2FA==0){
        $uuid=base64_decode( $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?system-unique-id=yes"));
        $local_img=dirname(__FILE__)."/img/2FA$uuid.png";
        if(is_file($local_img)){@unlink($local_img);}
        echo $tpl->widget_grey("{2fa}", "{disabled}");
    }else{
        $url=Get2faimg();
        $html[]="<div style='margin-top:10px'>";
        $html[]="<div style='vertical-align:top;width:335px'>
			<div class='widget navy-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
			<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>Google Authenticator</H2>
			<img src='$url'>
			</div>
			</div>";
        $html[]="";
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body($html);
    }
}

function Get2faimg():string{
    $uuid=base64_decode( $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?system-unique-id=yes"));
    $local_img=dirname(__FILE__)."/img/2FA$uuid.png";
    if(is_file($local_img)){return "/img/2FA$uuid.png?t=".time();}
    include_once(dirname(__FILE__)."/ressources/externals/PHPGangsta/google2fa.inc");
    include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
    $Artica2FAToken=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Artica2FAToken"));
    if($Artica2FAToken==null){
        $ga = new PHPGangsta_GoogleAuthenticator();
        $Artica2FAToken = $ga->createSecret();
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Artica2FAToken",$Artica2FAToken);

    }
    $uname=posix_uname();
    $hostname=trim($uname["nodename"]);
    $ga = new PHPGangsta_GoogleAuthenticator();
    $qrCodeUrl = $ga->getQRCodeGoogleUrl("Artica - $hostname", $Artica2FAToken);
    $curl=new ccurl($qrCodeUrl);
    if($curl->GetFile($local_img)){
        return "/img/2FA$uuid.png?t=".time();
    }
    return $qrCodeUrl;
}