<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.webconsole.params.inc");
// saml_sp: lecture/validation de la configuration d'authentification federee
include_once(dirname(__FILE__)."/ressources/class.saml.sp.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["webshell-js"])){webshell_js();exit;}
if(isset($_GET["webshell-popup"])){webshell_popup();exit;}
if(isset($_POST["EnableUnixShell"])){webshell_save();exit;}

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
if(isset($_GET["mtls-bridge-js"])){aw_mtls_bridge_js();exit;}
if(isset($_GET["mtls-bridge-popup"])){aw_mtls_bridge_popup();exit;}
if(isset($_POST["WebConsoleEnablemTLSClient"])){aw_mtls_bridge_save();exit;}
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


if(isset($_GET["saml-js"])){saml_js();exit;}
if(isset($_GET["saml-popup"])){saml_popup();exit;}
if(isset($_GET["saml-check"])){saml_check();exit;}
if(isset($_GET["saml-genkey-js"])){saml_genkey_js();exit;}
if(isset($_GET["saml-genkey"])){saml_genkey();exit;}
if(isset($_POST["ArticaWebSAMLIdpEntityId"])){saml_save();exit;}

if(isset($_GET["general-js"])){general_js();exit;}
if(isset($_GET["general-popup"])){general_popup();exit;}
if(isset($_POST["PhpFPMArticaMaxChildren"])){phpfpm_save();exit;}
if(isset($_POST["SessionCookieLifetime"])){design_save();exit;}
if(isset($_POST["ArticaHttpUseSSL"])){save();exit;}
if(isset($_POST["LighttpdArticaListenInterface"])){save();exit;}


if(isset($_POST["EnableLockUnixConsole"])){design_save();exit;}
if(isset($_POST["EnableShowPasswords"])){design_save();exit;}
if(isset($_POST["HideArticaVersion"])){design_save();exit;}
if(isset($_POST["ArticaWebOldLogin"])){design_save();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-static"])){table_static();exit;}
if(isset($_GET["aw-sslc"])){aw_sslc_host();exit;}
if(isset($_GET["aw-restart-confirm-js"])){aw_restart_confirm_js();exit;}
if(isset($_POST["aw-restart-confirm"])){aw_restart_confirm_save();exit;}
if(isset($_GET["aw-sslc-remove-js"])){aw_sslc_remove_js();exit;}
if(isset($_POST["aw-sslc-remove"])){aw_sslc_remove_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["wait-restart-js"])){wait_restart_js();exit;}
if(isset($_GET["wait-restart-popup"])){wait_restart_popup();exit;}

if(isset($_GET["reload-js"])){reload_js();exit;}
if(isset($_GET["restart-js"])){restart_js();exit;}

if(isset($_GET["reload-css"])){reloadcss();exit;}
if(isset($_GET["skin"])){skin();exit;}
if(isset($_GET["skin-export"])){skin_export();exit;}
if(isset($_GET["skin-import-js"])){skin_import_js();exit;}
if(isset($_GET["skin-import-popup"])){skin_import_popup();exit;}
if(isset($_GET["skin-import"])){skin_import();exit;}
if(isset($_GET["skin-reset-js"])){skin_reset_js();exit;}
if(isset($_POST["skin-reset"])){skin_reset();exit;}
if(isset($_GET["file-uploaded"])){fileUpload();exit;}

page();

/**
 * Les trois cibles de televersement de l'onglet Skin : cle du contenu, cle du
 * nom, extensions acceptees, taille maximale en octets du fichier decode.
 */
function brand_upload_targets():array{
    return array(
        "logo"=>array("data"=>"customLogo","name"=>"customLogoName",
            "ext"=>array("png","jpg","jpeg"),"max"=>2097152),
        "font"=>array("data"=>"ArticaBrandFontFile","name"=>"ArticaBrandFontFileName",
            "ext"=>array("woff2"),"max"=>153600),
        "favicon"=>array("data"=>"ArticaBrandFavicon","name"=>"ArticaBrandFaviconName",
            "ext"=>array("ico"),"max"=>262144),
    );
}

/**
 * Reprend un fichier televerse par fw.upload.php et le range dans Valkey.
 *
 * Durcie : le dispatch de ce fichier precede tout controle de privilege -- le
 * AsSystemAdministrator vit dans skin(), pas dans le dispatcher -- et le nom
 * etait concatene brut dans un chemin, puis lu ET unlink(). Une valeur
 * "../../../.." sortait donc du repertoire d'upload, sous l'identite www-data
 * qui possede tout /usr/share/artica-postfix.
 */
function fileUpload(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo "alert('Permission denied');";return;}

    // basename() coupe la traversee, la liste blanche coupe le reste.
    $filename=basename((string)$_GET["file-uploaded"]);
    if(!preg_match('/^[A-Za-z0-9._-]{1,128}$/',$filename)){echo "alert('Invalid file name');";return;}

    $targets=brand_upload_targets();
    $target="logo";
    if(isset($_GET["target"]) && isset($targets[$_GET["target"]])){$target=$_GET["target"];}
    $cfg=$targets[$target];

    $ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
    if(!in_array($ext,$cfg["ext"])){
        echo "alert('Expected: .".addslashes(@implode(', .',$cfg["ext"]))."');";
        return;
    }

    $dir="/usr/share/artica-postfix/ressources/conf/upload";
    $filepath="$dir/$filename";
    if(!is_file($filepath)){echo "alert('Upload not found');";return;}
    // Le chemin resolu doit rester dans le repertoire d'upload -- ceinture et
    // bretelles apres basename(), au cas ou un lien symbolique traine.
    $real=realpath($filepath);
    if($real===false || strpos($real,realpath($dir)."/")!==0){echo "alert('Invalid path');";return;}

    if(filesize($real)>$cfg["max"]){
        @unlink($real);
        echo "alert('File too large');";
        return;
    }

    $imagedata=@file_get_contents($real);
    @unlink($real);
    if($imagedata===false || strlen($imagedata)<16){echo "alert('Unreadable file');";return;}

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($cfg["name"],$filename);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($cfg["data"],base64_encode($imagedata));
    if(function_exists("brand_cache_flush")){brand_cache_flush();}
    admin_tracks("Skin: uploaded $target file $filename");

    // Appele par Loadjs() : la reponse est du JavaScript. Recharge l'onglet Skin
    // pour afficher le nouveau nom de fichier actif.
    echo "LoadAjaxSilent('articaweb-skin','$page?skin=yes');";
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
    if(!aw_is_admin()){return $tpl->js_no_privileges();}
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
    if(!aw_is_admin()){return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/restart");
    admin_tracks("Restart the Web Console");
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
function webshell_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{webshell_console}","$page?webshell-popup=yes",650);
}
function webshell_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableUnixShell=1;

    $DisableUnixShell=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableUnixShell"));
    if($DisableUnixShell==1){
        $EnableUnixShell=0;
    }
   echo $tpl->BigCircleCheckbox("EnableUnixShell","{enable_feature}",
        "{webshell_explain}",$EnableUnixShell,
       "dialogInstance2.close();LoadAjaxSilent('artica-web','$page?table-static=yes')");
    return true;
}
function webshell_save():bool{
    $EnableUnixShell=intval($_POST["EnableUnixShell"]);
    if($EnableUnixShell==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableUnixShell",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableUnixShell",1);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/htopweb/restart");
    return true;
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

    $EnableNamespaces=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNamespaces"));
    if($EnableNamespaces==1){
        $WebConsoleNamespaceID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleNamespaceID"));
        $form[]=$tpl->field_NameSpaces("WebConsoleNamespaceID",$WebConsoleNamespaceID);
    }

    echo  $tpl->form_outside(null,$form,
        null,"{apply}",js_after_forms(),$security);

    return true;
}

function http_popup():bool{
    $tpl=new template_admin();

    $ArticaHttpUseSSL               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));
    $LighttpdArticaCertificateName  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaCertificateName"));
    $SSLCipherSuite                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSLCipherSuite"));
    $UseHttp2                       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseHttp2"));
    $APP_NGINX_CONSOLE_HTTPV2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_HTTPV2"));




    $security="AsSystemAdministrator";
    $NoXSSProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoXSSProtection"));
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));


    if($SSLCipherSuite==null){$SSLCipherSuite="ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK";}


    $LighttpdAllowAuthenticateScreen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdAllowAuthenticateScreen"));
    $form[]=$tpl->field_checkbox("ArticaHttpUseSSL","{ssl}",$ArticaHttpUseSSL,"SSLCipherSuite","");
    $form[]=$tpl->field_certificate("LighttpdArticaCertificateName", "{certificate}",$LighttpdArticaCertificateName);
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

    $maxtime_array=aw_session_lifetimes($SessionCookieLifetime);

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


// ---------------------------------------------------------------------------
// Onglet « Skin » : refonte d'apres /home/claude/images/stitch_skin_settings_ui_redesign
// (Artica Infrastructure Light). Un widget = une fonction. La feuille de style et
// le JavaScript sont concatenes AUTOUR de _ENGINE_parse_body(), jamais dedans :
// son regex #{(.+?)}# lirait chaque corps de regle CSS / bloc JS comme un {token}.
// Les cles POST restent EXACTEMENT celles de l'ancien formulaire : design_save()
// (dispatche sur $_POST["HideArticaVersion"]) et fileUpload() sont inchanges.
// ---------------------------------------------------------------------------
function skin_css():string{
    if(isset($GLOBALS["SKIN_CSS_DONE"])){return "";}
    $GLOBALS["SKIN_CSS_DONE"]=true;
    return "<style>
.skn{color:#0f172a;font-size:13px;line-height:1.45;max-width:1240px}
.skn *{box-sizing:border-box}
.skn-card{margin-bottom:20px}
.skn-hd{display:flex;align-items:center;gap:14px;padding:18px 24px;border-bottom:1px solid #f1f5f9}
.skn-hd-ico{width:44px;height:44px;border-radius:8px;background:#f0fdfa;border:1px solid #ccfbf1;color:#0d9488;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 auto}
.skn-hd-t{font-size:15px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.skn-hd-s{font-size:12px;color:#64748b;margin-top:2px}
.skn-body{padding:24px}
.skn-cols{display:flex;gap:40px;flex-wrap:wrap}
.skn-col{flex:1 1 420px;min-width:300px}
.skn-sec{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#0f172a;margin:0 0 14px 0}
.skn-grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.skn-tg{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;min-height:74px}
.skn-tg-t{font-size:13px;font-weight:700;color:#0f172a}
.skn-tg-s{font-size:12px;color:#64748b;margin-top:2px}
.skn-lbl{display:flex;align-items:baseline;justify-content:space-between;gap:10px;font-size:13px;font-weight:600;color:#0f172a;margin:18px 0 8px 0}
.skn-lbl-h{font-size:11px;font-weight:400;color:#94a3b8;text-align:right}
.skn-lbl-h span{font-family:'Courier New',Courier,monospace;color:#64748b}
.skn-in{width:100%;height:36px;border:1px solid #e2e8f0;border-radius:4px;background:#fff;color:#0f172a;font-size:13px;padding:0 12px;outline:none}
.skn-in:focus{border-color:#0d9488;box-shadow:0 0 0 2px rgba(13,148,136,.2)}
.skn-mono{font-family:'Courier New',Courier,monospace}
.skn-inwrap{position:relative}
.skn-inwrap .skn-in{padding-right:64px}
.skn-macro{position:absolute;right:8px;top:8px;height:20px;line-height:18px;padding:0 8px;border:1px solid #e2e8f0;border-radius:4px;background:#f1f5f9;color:#475569;font-size:11px;font-family:'Courier New',Courier,monospace}
.skn-ta-wrap{border:1px solid #e2e8f0;border-radius:4px;background:#fff;overflow:hidden}
.skn-ta-bar{background:#f8fafc;border-bottom:1px solid #f1f5f9;padding:5px 12px;font-size:11px;color:#64748b;font-family:'Courier New',Courier,monospace}
.skn-ta{width:100%;min-height:118px;border:0;outline:none;padding:10px 12px;font-size:13px;line-height:1.6;color:#0f172a;font-family:'Courier New',Courier,monospace;resize:vertical;display:block}
.skn-drop{border:2px dashed #cbd5e1;border-radius:8px;background:#f8fafc;padding:26px 16px;text-align:center;margin-top:8px;transition:border-color .15s,background .15s;cursor:pointer}
.skn-drop-over{border-color:#0d9488;background:#f0fdfa}
.skn-drop-ico{width:56px;height:56px;border-radius:50%;background:#fff;border:1px solid #ccfbf1;color:#0d9488;display:inline-flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px}
.skn-drop-t{font-size:13px;color:#475569}
.skn-drop-t a{color:#0d9488;font-weight:600;text-decoration:underline;cursor:pointer}
.skn-drop-f{font-size:12px;color:#64748b;margin-top:8px}
.skn-drop-f span{font-family:'Courier New',Courier,monospace;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:1px 6px;color:#0f172a}
.skn-drop input[type=file]{display:none}
.skn-pal{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.skn-cl{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px}
.skn-cl-t{font-size:12px;font-weight:600;color:#0f172a;margin-bottom:8px}
.skn-cl-r{display:flex;align-items:center;gap:8px}
.skn-cl-sw{width:36px;height:36px;border-radius:6px;border:1px solid #cbd5e1;flex:0 0 auto;cursor:pointer;position:relative;overflow:hidden}
.skn-cl-sw input[type=color]{position:absolute;inset:-6px;width:150%;height:150%;border:0;padding:0;cursor:pointer;background:transparent}
.skn-cl .skn-in{height:32px;font-family:'Courier New',Courier,monospace;text-transform:uppercase;padding:0 8px;min-width:0}
.skn-cl-lg .skn-cl-sw{width:44px;height:44px}
.skn-cl-lg .skn-in{height:44px;font-size:14px}
.skn-row2{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.skn-tg-w{margin-top:18px;min-height:0}
.skn-foot{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-bottom:20px}
@media(max-width:900px){.skn-grid2,.skn-row2{grid-template-columns:1fr}.skn-cols{gap:24px}}
</style>";
}

/** Accumulateur JS de l'onglet : rendu APRES _ENGINE_parse_body() (voir skin_css). */
function skin_js_push($js){
    if(!isset($GLOBALS["SKIN_JS"])){$GLOBALS["SKIN_JS"]=array();}
    $GLOBALS["SKIN_JS"][]=$js;
}
function skin_js_compile():string{
    if(!isset($GLOBALS["SKIN_JS"])){return "";}
    $js=$GLOBALS["SKIN_JS"];$GLOBALS["SKIN_JS"]=array();
    if(count($js)==0){return "";}
    return "<script>".@implode("\n",$js)."</script>";
}

/** En-tete de carte : chip d'icone, titre en capitales, sous-titre. */
function skin_card_head($ico,$title,$explain):string{
    return "<div class='skn-hd'><span class='skn-hd-ico'><i class='$ico'></i></span>".
           "<div><div class='skn-hd-t'>$title</div><div class='skn-hd-s'>$explain</div></div></div>";
}

/** Titre de section a puce teal. */
function skin_section($title):string{
    return "<div class='skn-sec'><span class='ak-dot ak-accent'></span>$title</div>";
}

/**
 * Interrupteur d'un reglage 0/1. Le champ cache porte la cle POST ; le
 * clic ne fait que basculer sa valeur -- tout part avec « Appliquer »,
 * comme les anciennes cases a cocher.
 */
function skin_toggle($field,$title,$explain,$value,$extraclass=""):string{
    $value=(intval($value)==1)?1:0;
    $on=($value==1)?" on":"";
    return "<div class='skn-tg $extraclass'><div><div class='skn-tg-t'>$title</div><div class='skn-tg-s'>$explain</div></div>".
           "<input type='hidden' name='$field' id='skn-$field' value='$value'>".
           "<span class='ak-sw$on' onclick=\"SkinToggle(this,'skn-$field');\"></span></div>";
}

/** Champ texte ; $hint est un texte d'aide a droite du libelle, $macro un badge dans le champ. */
function skin_text_field($field,$label,$value,$hint="",$macro="",$mono=false):string{
    $h=array();
    $h[]="<div class='skn-lbl'><span>$label</span>".($hint!=""?"<span class='skn-lbl-h'>$hint</span>":"")."</div>";
    $cls="skn-in".($mono?" skn-mono":"");
    $v=htmlspecialchars($value,ENT_QUOTES);
    if($macro!=""){
        $h[]="<div class='skn-inwrap'><input type='text' class='$cls' name='$field' id='skn-$field' value=\"$v\"><span class='skn-macro'>$macro</span></div>";
    }else{
        $h[]="<input type='text' class='$cls' name='$field' id='skn-$field' value=\"$v\">";
    }
    return @implode("\n",$h);
}

/** Zone de texte monospace avec barre d'information (HTML autorise). */
function skin_textarea($field,$label,$value,$hint,$bar):string{
    $h=array();
    $h[]="<div class='skn-lbl'><span>$label</span><span class='skn-lbl-h'>$hint</span></div>";
    $h[]="<div class='skn-ta-wrap'><div class='skn-ta-bar'>$bar</div>";
    $h[]="<textarea class='skn-ta' name='$field' id='skn-$field'>".htmlspecialchars($value,ENT_QUOTES)."</textarea></div>";
    return @implode("\n",$h);
}

/** Tuile couleur : pastille (input color natif) + valeur hexadecimale synchronisees. */
function skin_color_field($field,$label,$value,$large=false):string{
    $value=trim($value);
    if(!preg_match("/^#[0-9a-fA-F]{6}$/",$value)){$value="#ffffff";}
    $v=htmlspecialchars($value,ENT_QUOTES);
    $cls="skn-cl".($large?" skn-cl-lg":"");
    return "<div class='$cls'><div class='skn-cl-t'>$label</div><div class='skn-cl-r'>".
           "<span class='skn-cl-sw' id='skn-sw-$field' style='background:$v'><input type='color' value='$v' oninput=\"SkinColorPick('$field',this.value);\"></span>".
           "<input type='text' class='skn-in' name='$field' id='skn-$field' value='$v' maxlength='7' oninput=\"SkinColorType('$field',this.value);\">".
           "</div></div>";
}

/**
 * Zone de depot du logo. Reutilise le flux d'upload existant : jquery.fileupload
 * vers /fw.upload.php, puis Loadjs('$page?file-uploaded=<nom>') qui appelle
 * fileUpload() (memorise customLogoName + customLogo en base64) et recharge l'onglet.
 */
function skin_dropzone($target,$label,$hint,$current):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Uploading=$tpl->javascript_parse_text("{uploading}");
    $UploadFailed=$tpl->javascript_parse_text("{failed}");
    $targets=brand_upload_targets();
    $accept="";
    if(isset($targets[$target])){
        $accept=".".@implode(",.",$targets[$target]["ext"]);
    }
    $h=array();
    $h[]="<div class='skn-lbl'><span>$label</span><span class='skn-lbl-h'>$hint</span></div>";
    $h[]="<div class='skn-drop' id='skn-drop-$target' onclick=\"document.getElementById('skn-file-$target').click();\">";
    $h[]="<div class='skn-drop-ico'><i class='fas fa-cloud-arrow-up'></i></div>";
    $h[]="<div class='skn-drop-t' id='skn-drop-t-$target'>{skin_drop_logo_here} <a>{skin_browse_files}</a></div>";
    if($current!=""){
        $h[]="<div class='skn-drop-f'>{skin_active_file}: <span>".htmlspecialchars($current)."</span></div>";
    }else{
        $h[]="<div class='skn-drop-f'>{skin_no_logo_file}</div>";
    }
    $h[]="<input id='skn-file-$target' type='file' name='files' accept='".htmlspecialchars($accept)."'>";
    $h[]="</div>";
    $js=array();
    $js[]="$('#skn-file-$target').fileupload({url:'/fw.upload.php',dataType:'json',dropZone:$('#skn-drop-$target'),";
    $js[]="add:function(e,data){document.getElementById('skn-drop-t-$target').innerHTML='$Uploading...';data.submit();},";
    $js[]="progressall:function(e,data){var p=parseInt(data.loaded/data.total*100,10);document.getElementById('skn-drop-t-$target').innerHTML='$Uploading '+p+'%';},";
    $js[]="done:function(e,data){var up=(data.result&&data.result.files)?data.result.files[0]:null;";
    $js[]="if(up==null||up.error){swal({title:'$UploadFailed',text:(up&&up.error)?up.error:'$UploadFailed',type:'error',confirmButtonColor:'#ed5565'});return;}";
    $js[]="Loadjs('$page?target=$target&file-uploaded='+encodeURIComponent(up.name));}});";
    $js[]="$(document).on('dragover',function(e){e.preventDefault();});";
    $js[]="$(document).on('drop',function(e){e.preventDefault();});";
    $js[]="$('#skn-drop-$target').on('dragover',function(){\$(this).addClass('skn-drop-over');}).on('dragleave drop',function(){\$(this).removeClass('skn-drop-over');});";
    skin_js_push(@implode("",$js));
    return @implode("\n",$h);
}

/** Carte « Ecran de connexion ». */
function skin_card_login(array $v):string{
    $tpl=new template_admin();
    $h=array();
    $h[]="<div class='ak-card skn-card'>";
    $h[]=skin_card_head("fas fa-lock","{LOGON_PAGE}","{skin_login_card_explain}");
    $h[]="<div class='skn-body'><div class='skn-cols'>";
    // colonne gauche : visibilite + logo
    $h[]="<div class='skn-col'>";
    $h[]=skin_section("{skin_display_visibility}");
    $h[]="<div class='skn-grid2'>";
    $h[]=skin_toggle("HideArticaLogo","{remove_artica_logo}","{skin_remove_logo_explain}",$v["HideArticaLogo"]);
    $h[]=skin_toggle("useCustomLogo","{use_custom_logo}","{skin_custom_logo_explain}",$v["useCustomLogo"]);
    $h[]=skin_toggle("HideArticaVersion","{remove_artica_version}","{skin_hide_version_explain}",$v["HideArticaVersion"]);
    $h[]=skin_toggle("HideVirtualizationVersion","{skin_hide_hypervisor}","{skin_hide_hypervisor_explain}",$v["HideVirtualizationVersion"]);
    $h[]="</div>";
    $h[]=skin_dropzone("logo","{skin_logo_upload}","{skin_logo_upload_hint}",$v["customLogoName"]);
    $h[]="</div>";
    // colonne droite : textes + palette
    $h[]="<div class='skn-col'>";
    $h[]=skin_section("{skin_texts_variables}");
    $h[]="<div class='skn-row2'>";
    $h[]="<div>".skin_text_field("TitleLogon","{skin_login_title}",$v["TitleLogon"])."</div>";
    $h[]="<div>".skin_text_field("LoginTitle2","{skin_login_subtitle}",$v["LoginTitle2"],"","{macro}",true)."</div>";
    $h[]="</div>";
    $h[]=skin_textarea("TextOfArticaPage","{skin_login_description}",$v["TextOfArticaPage"],
        "<span>&lt;br&gt;</span> &bull; <span>&lt;strong&gt;</span> &bull; <span>&lt;a&gt;</span>","HTML &bull; UTF-8");
    $h[]="<div class='skn-lbl'><span>{skin_login_palette}</span></div>";
    $h[]="<div class='skn-pal'>";
    $h[]=skin_color_field("ArticaLoginBackGroundColor","{background_color}",$v["ArticaLoginBackGroundColor"]);
    $h[]=skin_color_field("ArticaFontColorTitle","{font_color}",$v["ArticaFontColorTitle"]);
    $h[]=skin_color_field("ArticaLoginFontColor","{skin_subtitle_font_color}",$v["ArticaLoginFontColor"]);
    $h[]=skin_color_field("ArticaFontColorFields","{skin_fields_font_color}",$v["ArticaFontColorFields"]);
    $h[]="</div>";
    $h[]="</div>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

/** Carte « Console Web (general & dashboard) ». */
function skin_card_console(array $v):string{
    $h=array();
    $h[]="<div class='ak-card skn-card'>";
    $h[]=skin_card_head("fas fa-gear","{webconsole} ({general})","{skin_console_card_explain}");
    $h[]="<div class='skn-body'>";
    $h[]="<div class='skn-row2'>";
    $h[]="<div>".skin_text_field("TitleOfArticaPage","{skin_browser_tab_title}",$v["TitleOfArticaPage"],"","{macro}",true)."</div>";
    $h[]="<div><div class='skn-lbl'><span>{skin_console_palette}</span></div><div class='skn-pal'>";
    $h[]=skin_color_field("ArticaBackGroundBodyColor","{skin_body_color}",$v["ArticaBackGroundBodyColor"]);
    $h[]=skin_color_field("ArticaBackGroundColor","{skin_cards_color}",$v["ArticaBackGroundColor"]);
    $h[]=skin_color_field("ArticaFontColor","{font_color}",$v["ArticaFontColor"]);
    $h[]="</div></div>";
    $h[]="</div>";
    $h[]=skin_toggle("StandardDropDown","{standard_dropdown}","{skin_native_dropdown_explain}",$v["StandardDropDown"],"skn-tg-w");
    $h[]="</div></div>";
    return @implode("\n",$h);
}

/** Barre d'action : un seul bouton « Appliquer » qui poste tout le formulaire. */
function skin_apply_bar():string{
    return "<div class='skn-foot'><button type='button' class='ak-btn ak-btn-primary ak-btn-lg' id='skn-apply' onclick='SkinApply();'><i class='fas fa-check'></i> {apply}</button></div>";
}

/**
 * JS de l'onglet. SkinApply() reproduit le contrat de form_outside : reponse
 * vide = succes (toast + rechargement des CSS), « jserror:… » = popup, autre
 * texte = message d'erreur. design_save() est selectionne cote PHP par la
 * presence de HideArticaVersion dans le POST.
 */
function skin_form_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ok=$tpl->javascript_parse_text("{success_form_submited}");
    $js=array();
    $js[]="function SkinToggle(el,id){var on=!el.classList.contains('on');el.className='ak-sw'+(on?' on':'');document.getElementById(id).value=on?1:0;}";
    $js[]="function SkinColorPick(f,v){document.getElementById('skn-'+f).value=v.toUpperCase();document.getElementById('skn-sw-'+f).style.background=v;}";
    $js[]="function SkinColorType(f,v){if(/^#[0-9a-fA-F]{6}$/.test(v)){document.getElementById('skn-sw-'+f).style.background=v;document.querySelector('#skn-sw-'+f+' input').value=v;}}";
    $js[]="function SkinApply(){var bt=document.getElementById('skn-apply');bt.disabled=true;";
    $js[]="var data={};$('#articaweb-skin [name]').not('[type=file]').each(function(){data[this.name]=this.value;});";
    $js[]="$.post('$page',data).done(function(res){bt.disabled=false;res=(res||'').toString();";
    $js[]="if(res.length>3){var m=/jserror:(.+)/.exec(res);if(m){swal({title:'Oops...',text:'<strong>'+m[1].replace(/[\\r\\n#]/g,'<br>')+'</strong>',html:true,type:'error'});}else{swal({title:'Oops...',text:res,type:'error'});}return;}";
    $js[]="toastr.options.timeOut=2000;toastr.options.progressBar=true;toastr.success('$ok');Loadjs('$page?reload-css=yes');";
    $js[]="}).fail(function(x,t,e){bt.disabled=false;swal({title:'Oops...',text:e||t,type:'error'});});}";
    return @implode("\n",$js);
}

/** Valeurs courantes de l'onglet, avec les memes defauts que l'ancien formulaire. */
function skin_values():array{
    $tpl=new template_admin();
    $s=$GLOBALS["CLASS_SOCKETS"];
    $v=array();
    foreach(array("HideArticaVersion","HideArticaLogo","useCustomLogo","HideVirtualizationVersion","StandardDropDown") as $k){
        $v[$k]=intval($s->GET_INFO($k));
    }
    foreach(array("TitleOfArticaPage","TextOfArticaPage","TitleLogon","LoginTitle2","customLogoName",
        "ArticaLoginBackGroundColor","ArticaLoginFontColor","ArticaBackGroundColor","ArticaFontColor",
        "ArticaFontColorTitle","ArticaFontColorFields","ArticaBackGroundBodyColor") as $k){
        $v[$k]=trim((string)$s->GET_INFO($k));
    }
    if($v["TitleOfArticaPage"]==""){$v["TitleOfArticaPage"]="%SERVERNAME%";}
    if($v["TextOfArticaPage"]==""){$v["TextOfArticaPage"]=$tpl->_ENGINE_parse_body("{default_login_explain}");}
    if($v["TitleLogon"]==""){$v["TitleLogon"]=$tpl->_ENGINE_parse_body("{Connection}");}
    if($v["LoginTitle2"]==""){$v["LoginTitle2"]="%SERVERNAME%";}
    if($v["ArticaFontColor"]==""){$v["ArticaFontColor"]="#676a6c";}
    if($v["ArticaBackGroundColor"]==""){$v["ArticaBackGroundColor"]="#ffffff";}
    if($v["ArticaFontColorFields"]==""){$v["ArticaFontColorFields"]="#ffffff";}
    if($v["ArticaBackGroundBodyColor"]==""){$v["ArticaBackGroundBodyColor"]="#f3f3f4";}
    if($v["ArticaLoginBackGroundColor"]==""){$v["ArticaLoginBackGroundColor"]="#283437";}
    if($v["ArticaLoginFontColor"]==""){$v["ArticaLoginFontColor"]="#a7b1c2";}
    if($v["ArticaFontColorTitle"]==""){$v["ArticaFontColorTitle"]="#ffffff";}
    foreach(array("ArticaBrandPrimary","ArticaBrandAccent","ArticaBrandDeep",
        "ArticaBrandBtnLight","ArticaBrandBtnDark","ArticaBrandHeaderBg",
        "ArticaBrandLink","ArticaBrandLinkHover","ArticaBrandTextLight",
        "ArticaBrandFontName","ArticaBrandFontFileName","ArticaBrandFaviconName") as $k){
        $v[$k]=trim((string)$s->GET_INFO($k));
    }
    // La palette resolue sert de valeur d'exemple aux champs avances : un champ
    // vide se lit alors « herite », et non « casse ».
    $v["BRAND_DERIVED"]=array();
    if(function_exists("brand_resolve")){$v["BRAND_DERIVED"]=brand_resolve();}
    return $v;
}

/** Les trois pastilles de marque, en grand format. */
function skin_brand_colors(array $v):string{
    $h=array();
    $h[]=skin_section("{brand_colors_section}");
    $h[]="<div class='skn-pal'>";
    $h[]=skin_color_field("ArticaBrandPrimary","{brand_primary}",$v["ArticaBrandPrimary"],true);
    $h[]=skin_color_field("ArticaBrandAccent","{brand_accent}",$v["ArticaBrandAccent"],true);
    $h[]=skin_color_field("ArticaBrandDeep","{brand_deep}",$v["ArticaBrandDeep"],true);
    $h[]="</div>";
    return @implode("\n",$h);
}

/** Nom de famille + depot du .woff2. */
function skin_brand_font(array $v):string{
    $h=array();
    $h[]=skin_section("{brand_font_section}");
    $h[]=skin_text_field("ArticaBrandFontName","{brand_font_name}",$v["ArticaBrandFontName"],
        "{brand_font_name_hint}");
    $h[]=skin_dropzone("font","{brand_font_upload}","{brand_font_upload_hint}",
        $v["ArticaBrandFontFileName"]);
    return @implode("\n",$h);
}

/** Depot du .ico. */
function skin_brand_favicon(array $v):string{
    $h=array();
    $h[]=skin_section("{brand_favicon_section}");
    $h[]=skin_dropzone("favicon","{brand_favicon_upload}","{brand_favicon_upload_hint}",
        $v["ArticaBrandFaviconName"]);
    return @implode("\n",$h);
}

/**
 * Les six surcharges de role, repliees. Chaque champ vide affiche sa valeur
 * derivee en indication : c'est ce qui fait lire « herite » plutot que « vide ».
 */
function skin_brand_advanced(array $v):string{
    $d=$v["BRAND_DERIVED"];
    $rows=array(
        array("ArticaBrandBtnLight","{brand_btn_light}","btn_light"),
        array("ArticaBrandBtnDark","{brand_btn_dark}","btn_dark"),
        array("ArticaBrandHeaderBg","{brand_header_bg}","header_bg"),
        array("ArticaBrandLink","{brand_link}","link"),
        array("ArticaBrandLinkHover","{brand_link_hover}","link_hover"),
        array("ArticaBrandTextLight","{brand_text_light}","text_light"),
    );
    $h=array();
    $h[]="<details style='margin-top:18px'>";
    $h[]="<summary style='cursor:pointer;font-size:12px;font-weight:700;letter-spacing:.05em;".
         "text-transform:uppercase;color:#0f172a'>{brand_advanced}</summary>";
    $h[]="<div class='skn-tg-s' style='margin:8px 0 12px 0'>{brand_advanced_explain}</div>";
    $h[]="<div class='skn-pal'>";
    foreach($rows as $r){
        $hint="";
        if(isset($d[$r[2]])){$hint=" ({brand_derived} ".$d[$r[2]].")";}
        $h[]=skin_color_field($r[0],$r[1].$hint,$v[$r[0]]);
    }
    $h[]="</div>";
    $h[]="</details>";
    return @implode("\n",$h);
}

/** Carte « Identite visuelle ». */
function skin_card_brand(array $v):string{
    $h=array();
    $h[]="<div class='ak-card skn-card'>";
    $h[]=skin_card_head("fas fa-palette","{brand_card_title}","{brand_card_explain}");
    $h[]="<div class='skn-body'><div class='skn-cols'>";
    $h[]="<div class='skn-col'>";
    $h[]=skin_brand_colors($v);
    $h[]=skin_brand_advanced($v);
    $h[]="</div>";
    $h[]="<div class='skn-col'>";
    $h[]=skin_brand_font($v);
    $h[]=skin_brand_favicon($v);
    $h[]="</div>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

function skin(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS}"));return;}
    $v=skin_values();
    $html=array();
    $html[]="<div class='skn' id='articaweb-skin' style='margin-top:10px'>";
    if(function_exists("brand_corp_license") && !brand_corp_license()){
        $html[]=$tpl->div_warning("{brand_requires_license}");
    }
    $html[]=skin_card_login($v);
    $html[]=skin_card_brand($v);
    $html[]=skin_card_console($v);
    $html[]=skin_apply_bar();
    $html[]="</div>";
    skin_js_push(skin_form_js());
    skin_js_push(skin_tiny());
    skin_js_push("NoSpinner();");
    echo skin_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).skin_js_compile();
}


// ---------------------------------------------------------------------------
// Export / import du design
//
// La liste des cles vit dans class.brand.inc (brand_export_keys) : elle sert de
// source unique aux deux sens ET de liste blanche a l'import. Les deux handlers
// verifient le role ET la licence cote serveur -- masquer un bouton n'est pas
// un controle d'acces.
// ---------------------------------------------------------------------------

/** Le garde commun aux deux operations. Rend "" si tout va bien, sinon le motif. */
function skin_transfer_denied():string{
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){return "Permission denied";}
    if(!function_exists("brand_corp_license")){return "Corporate license required";}
    if(!brand_corp_license()){return "Corporate license required";}
    return "";
}

/** Le design courant, en JSON compresse, servi en telechargement. */
function skin_export(){
    if(skin_transfer_denied()!=""){header("HTTP/1.1 403 Forbidden");exit;}
    $data=brand_export_payload(brand_export_collect());
    $host=preg_replace('/[^A-Za-z0-9._-]/','',(string)@gethostname());
    if($host==""){$host="artica";}
    $name="artica-skin-$host-".date("Ymd-His").".json.gz";
    header("Content-Type: application/gzip");
    header("X-Content-Type-Options: nosniff");
    header("Content-Disposition: attachment; filename=\"$name\"");
    header("Content-Length: ".strlen($data));
    admin_tracks("Skin: exported design");
    echo $data;
}

/** Confirmation de la remise a zero du design. */
function skin_reset_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        header("content-type: application/x-javascript");
        echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
        return;
    }
    $jsafter="LoadAjaxSilent('articaweb-skin','$page?skin=yes');Loadjs('$page?reload-css=yes');";
    $tpl->js_confirm_execute("{skin_reset_explain}","skin-reset","yes",$jsafter);
}

/**
 * Remet le design d'usine : les 32 cles de brand_export_keys() ecrites vides,
 * c'est-a-dire exactement ce que produirait l'import d'un fichier sans cle --
 * chaque lecteur traite deja "" comme « valeur par defaut ». La liste unique
 * garantit qu'une cle ajoutee au design plus tard sera aussi remise a zero.
 *
 * Rien n'est echo en cas de succes : js_confirm_execute() prend toute reponse
 * non vide pour une erreur.
 */
function skin_reset():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        echo $tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
        return false;
    }
    if(!function_exists("brand_export_keys")){
        echo "class.brand.inc is missing";
        return false;
    }
    $keys=brand_export_keys();
    foreach($keys as $k){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,"");
    }
    if(function_exists("brand_cache_flush")){brand_cache_flush();}
    return admin_tracks("Skin: reset to the factory design (".count($keys)." keys cleared)");
}

/** Ouvre la modale d'import. */
function skin_import_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->js_dialog2("{skin_import_title}","$page?skin-import-popup=yes",560);
}

/** Zone de depot du fichier d'import. Un widget, une fonction. */
function skin_import_dropzone():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Uploading=$tpl->javascript_parse_text("{uploading}");
    $UploadFailed=$tpl->javascript_parse_text("{failed}");
    $h=array();
    $h[]="<div class='skn-drop' id='skn-imp-drop' onclick=\"document.getElementById('skn-imp-file').click();\">";
    $h[]="<div class='skn-drop-ico'><i class='fas fa-file-import'></i></div>";
    $h[]="<div class='skn-drop-t' id='skn-imp-t'>{skin_import_drop} <a>{skin_browse_files}</a></div>";
    $h[]="<div class='skn-drop-f'>{skin_import_hint}</div>";
    $h[]="<input id='skn-imp-file' type='file' name='files' accept='.gz'>";
    $h[]="</div>";
    $js=array();
    $js[]="<script>";
    $js[]="$('#skn-imp-file').fileupload({url:'/fw.upload.php',dataType:'json',dropZone:$('#skn-imp-drop'),";
    $js[]="add:function(e,data){document.getElementById('skn-imp-t').innerHTML='$Uploading...';data.submit();},";
    $js[]="progressall:function(e,data){var p=parseInt(data.loaded/data.total*100,10);document.getElementById('skn-imp-t').innerHTML='$Uploading '+p+'%';},";
    $js[]="done:function(e,data){var up=(data.result&&data.result.files)?data.result.files[0]:null;";
    $js[]="if(up==null||up.error){swal({title:'$UploadFailed',text:(up&&up.error)?up.error:'$UploadFailed',type:'error',confirmButtonColor:'#ed5565'});return;}";
    $js[]="Loadjs('$page?skin-import='+encodeURIComponent(up.name));}});";
    $js[]="$('#skn-imp-drop').on('dragover',function(){\$(this).addClass('skn-drop-over');}).on('dragleave drop',function(){\$(this).removeClass('skn-drop-over');});";
    $js[]="</script>";
    return @implode("\n",$h).@implode("",$js);
}

/** Contenu de la modale d'import. */
function skin_import_popup(){
    $tpl=new template_admin();
    $denied=skin_transfer_denied();
    if($denied!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS}"));return;}
    $h=array();
    $h[]="<div class='skn' style='padding:10px'>";
    $h[]=$tpl->div_warning("{skin_import_warning}");
    $h[]=skin_import_dropzone();
    $h[]="</div>";
    // La feuille de l'onglet porte .skn-drop ; la modale est chargee seule, il
    // faut donc la lui joindre. Concatenee APRES le parse, jamais dedans.
    echo skin_css().$tpl->_ENGINE_parse_body(@implode("\n",$h));
}

/**
 * Applique un design importe.
 *
 * Reprend le durcissement de fileUpload() : garde de role et de licence,
 * basename(), liste blanche de caracteres et d'extension, confinement realpath,
 * plafond de taille. Puis gzdecode, json_decode et validation -- chaque etape
 * gardee, car la charge vient de l'exterieur.
 */
function skin_import(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();

    $denied=skin_transfer_denied();
    if($denied!=""){echo "alert('".addslashes($denied)."');";return;}

    $filename=basename((string)$_GET["skin-import"]);
    if(!preg_match('/^[A-Za-z0-9._-]{1,128}$/',$filename)){echo "alert('Invalid file name');";return;}
    if(strtolower(pathinfo($filename,PATHINFO_EXTENSION))!="gz"){echo "alert('Expected: .gz');";return;}

    $dir="/usr/share/artica-postfix/ressources/conf/upload";
    $filepath="$dir/$filename";
    if(!is_file($filepath)){echo "alert('Upload not found');";return;}
    $real=realpath($filepath);
    if($real===false || strpos($real,realpath($dir)."/")!==0){echo "alert('Invalid path');";return;}
    if(filesize($real)>8388608){@unlink($real);echo "alert('File too large');";return;}

    $raw=@file_get_contents($real);
    @unlink($real);
    if($raw===false || strlen($raw)<16){echo "alert('Unreadable file');";return;}
    $json=@gzdecode($raw);
    if($json===false){echo "alert('Not a gzip file');";return;}
    $data=json_decode($json,true);
    if(!is_array($data)){echo "alert('Not a valid design file');";return;}
    $err=brand_import_validate($data);
    if($err!=""){echo "alert('".addslashes($err)."');";return;}

    $values=brand_import_filter($data);
    foreach($values as $k=>$v){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,$v);
    }
    if(function_exists("brand_cache_flush")){brand_cache_flush();}
    admin_tracks("Skin: imported design from $filename (".count($values)." keys)");

    $ok=$tpl->javascript_parse_text("{success_form_submited}");
    echo "dialogInstance2.close();";
    echo "toastr.options.timeOut=2000;toastr.options.progressBar=true;toastr.success('$ok');";
    echo "LoadAjaxSilent('articaweb-skin','$page?skin=yes');Loadjs('$page?reload-css=yes');";
}

/**
 * Boutons de l'en-tete de l'onglet. Rien sans licence Corporate.
 *
 * L'export ne peut PAS passer par Loadjs : Loadjs evalue la reponse comme du
 * JavaScript et avalerait le gzip. document.location laisse le
 * Content-Disposition faire son travail sans quitter la page.
 */
function skin_tiny():string{
    // La fonction absente veut dire que class.brand.inc n'est pas deploye :
    // la fonctionnalite entiere manque, il n'y a rien a montrer, meme grise.
    if(!function_exists("brand_corp_license")){return "";}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $licensed=brand_corp_license();

    // Sans licence, les deux actions restent VISIBLES mais inertes. Les retirer
    // -- ce que faisait « return "" » -- ne laissait aucun moyen de relier leur
    // absence a la banniere de licence affichee dans l'onglet : l'exploitant
    // voyait un en-tete sans boutons, sans rien qui l'explique.
    //
    // table_buttons() rend inerte tout bouton dont le JS fait moins de deux
    // caracteres : OnClick="blur()" et classe neutre. On s'appuie sur ce
    // chemin plutot que de dupliquer son balisage.
    //
    // Le title porte le POURQUOI. Guillemets doubles obligatoires : la chaine
    // finit dans un litteral JS a guillemets SIMPLES cote fw.progress.php, qui
    // echappe les apostrophes -- une apostrophe ici casserait l'en-tete entier.
    $why=$licensed?"":" title=\"{brand_requires_license}\"";
    $exportjs=$licensed?"document.location='$page?skin-export=yes';":"";
    $importjs=$licensed?"Loadjs('$page?skin-import-js=yes')":"";
    $lexport=$licensed?"{export}":"<span$why>{export}</span>";
    $limport=$licensed?"{import}":"<span$why>{import}</span>";

    $topbuttons=array();
    $topbuttons[]=array($exportjs,"fas fa-file-export",$lexport);
    $topbuttons[]=array($importjs,"fas fa-file-import",$limport);
    // Hors licence lui aussi : revenir au design d'usine n'accorde rien, et c'est
    // le seul moyen d'effacer un design pose avant l'expiration de la licence.
    $topbuttons[]=array("Loadjs('$page?skin-reset-js=yes')","fas fa-arrow-rotate-left","{reset}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
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
function design_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return false;
    }
    if(!$users->CORP_LICENSE){
        echo $tpl->post_error("{error_no_licence}");
        return false;
    }
    $tpl->SAVE_POSTs();
    if(function_exists("brand_cache_flush")){brand_cache_flush();}

    if (strlen($_POST["customLogo"])<2){
        $_POST["useCustomLogo"]=0;
    }

    if(isset($_POST["UnixConsolePassword"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnixConsolePassword",$_POST["UnixConsolePassword"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unix/console");
    }
    return admin_tracks_post("Saving Web console skin - design");
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
// Onglet Parameters (maquette stitch_artica_web_console_settings, 2026-11-01).
// Colonne gauche : les deux tuiles SERVICE_STATUS (inchangees, ?status=yes),
// puis certificat client, sessions et comportement de la console. Colonne
// droite : adresse d'acces, controle d'acces, service HTTP, moteur PHP,
// maintenance. Un widget = une fonction, un modele unique aw_model().
function table_static(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=aw_model();
    $h=array();
    $h[]="<div class='aw'><div class='aw-layout'>";
    $h[]="<aside class='aw-side'>";
    $h[]="<div id='openldap-status'></div>";
    $h[]="<div id='client-certificate-status'>".aw_card_sslc($m)."</div>";
    $h[]=aw_card_sessions($m);
    $h[]=aw_card_behaviour($m);
    $h[]="</aside>";
    $h[]="<div class='aw-main'>";
    $h[]=aw_card_access($m);
    $h[]=aw_card_control($m);
    $h[]="<div class='aw-pair'>".aw_card_http($m).aw_card_php($m)."</div>";
    $h[]=aw_card_maintenance($m);
    $h[]="</div></div></div>";
    $js="<script>LoadAjaxTiny('openldap-status','$page?status=yes');\n".aw_tiny($m)."\nNoSpinner();</script>";
    echo aw_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
}

// ?aw-sslc=yes : la modale du certificat client recharge cette carte.
function aw_sslc_host():bool{
    $tpl=new template_admin();
    echo aw_style().$tpl->_ENGINE_parse_body(aw_card_sslc(aw_model()));
    return true;
}

function aw_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

function aw_get($key):string{
    return trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
}

function aw_int($key,$default=0):int{
    $v=intval(aw_get($key));
    return $v==0?$default:$v;
}

function aw_esc($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES);
    return str_replace(array("{","}"),array("&#123;","&#125;"),$s);
}

// Duree de vie de session : cle en minutes -> libelle. Une seule table pour la
// page et la modale (l'ancienne rangeait "3 hours" sous 380 et "1 days" sous 2880).
// Une valeur enregistree avec l'ancienne table (380, 420) reste proposee telle
// quelle : sinon la liste selectionnerait "Never" et l'enregistrement l'ecraserait.
function aw_session_lifetimes($current=-1):array{
    $a=array(0=>"{never}",10=>"10 {minutes}",15=>"15 {minutes}",60=>"1 {hour}",120=>"2 {hours}",
        180=>"3 {hours}",240=>"4 {hours}",480=>"8 {hours}",720=>"12 {hours}",1440=>"1 {aw_day}",
        2880=>"2 {aw_days}",10080=>"1 {week}");
    $current=intval($current);
    if($current>0 && !isset($a[$current])){$a[$current]="$current {minutes}";ksort($a);}
    return $a;
}

function aw_model():array{
    $m=array();
    $m["admin"]=aw_is_admin();
    $m["ssl"]=intval(aw_get("ArticaHttpUseSSL"))==1;
    $m["port"]=aw_int("ArticaHttpsPort",9000);
    $m["iface"]=aw_get("LighttpdArticaListenInterface");
    $m["ifaces"]=array();
    foreach(explode(",",str_replace(" ",",",$m["iface"])) as $i){ if(trim($i)<>""){$m["ifaces"][]=trim($i);} }
    // Adresse vers laquelle la console redirige apres un redemarrage (premiere IP non locale).
    $m["host"]=count($m["ifaces"])==0?"*":(string)GET_INTERFACEIP();
    if($m["host"]==""){$m["host"]=$m["ifaces"][0];}
    $m["cert"]=aw_get("LighttpdArticaCertificateName");
    $m["ciphers"]=aw_get("SSLCipherSuite");
    $m["ns"]="";
    if(intval(aw_get("EnableNamespaces"))==1){
        $nsid=intval(aw_get("WebConsoleNamespaceID"));
        if($nsid>0){
            $tpl=new template_admin();
            $m["ns"]=(string)$tpl->NameSpaceIntToStr($nsid);
        }
    }
    $m["http2_avail"]=intval(aw_get("APP_NGINX_CONSOLE_HTTPV2"))==1;
    $m["http2"]=intval(aw_get("UseHttp2"));
    $m["sub_module"]=intval(aw_get("APP_NGINX_CONSOLE_SUB_MODULE"))==1;
    $m["saved"]=intval(aw_get("ARTICA_WEB_SAVED"));

    $m["rev"]=intval(aw_get("ArticaWebReverse"))==1;
    $m["rev_port"]=aw_int("ArticaWebReversePort",8999);
    $m["rev_iface"]=aw_get("ArticaWebReverseInterface");
    $m["rev_pproto"]=intval(aw_get("ArticaWebReversePProto"))==1;
    $m["api"]=intval(aw_get("ArticaWebToAPI"))==1;
    $m["api_path"]=aw_get("ArticaWebToAPIPath");
    if(strlen($m["api_path"])<3){$m["api_path"]="webapi";}

    $m["client_auth"]=intval(aw_get("LighttpdArticaClientAuth"))==1;
    $m["auth_screen"]=intval(aw_get("LighttpdAllowAuthenticateScreen"));
    $m["sslc"]=aw_sslc_state($m["client_auth"]);
    $m["authlink"]=array();
    $al=unserialize(base64_decode(aw_get("AuthLinkRestrictions")));
    if(is_array($al)){ foreach($al as $n){ if(trim((string)$n)<>""){$m["authlink"][]=trim((string)$n);} } }
    $m["authlink_default"]=count($m["authlink"])==0;
    if($m["authlink_default"]){$m["authlink"]=array("192.168.0.0/16","10.0.0.0/8","172.16.0.0/12");}
    $m["saml_on"]=saml_sp::enabled();
    $m["saml_errors"]=$m["saml_on"]?saml_sp::config_errors():array();
    $m["geoip"]=intval(aw_get("PHP_GEOIP_INSTALLED"))==1 && intval(aw_get("EnableGeoipUpdate"))==1;
    $m["countries"]=0;
    $deny=unserialize(base64_decode(aw_get("ArticaWebDenyCountries")));
    if(is_array($deny)){ foreach($deny as $cn=>$none){ if(strlen((string)$cn)>=2){$m["countries"]++;} } }
    $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $m["stream_rules"]=intval($q->COUNT_ROWS("ngx_stream_access_module"));
    $m["xss"]=intval(aw_get("NoXSSProtection"));
    $m["lock_unix"]=intval(aw_get("EnableLockUnixConsole"));
    $m["webshell"]=intval(aw_get("DisableUnixShell"))==0;

    $m["fpm_children"]=aw_int("PhpFPMArticaMaxChildren",30);
    $m["fpm_requests"]=aw_int("PhpFPMArticaMaxRequests",100);
    $m["fw_children"]=aw_int("PhpFPMArticaFrameWorkMaxChildren",50);
    $m["fw_requests"]=aw_int("PhpFPMArticaFrameWorkMaxRequests",100);
    $m["upload"]=max(1024,intval(aw_get("php5UploadMaxFileSize")));
    $m["post"]=max(1024,intval(aw_get("php5PostMaxSize")));
    $m["memory"]=max(1024,intval(aw_get("php5MemoryLimit")));
    $m["xmlrpc"]=intval(aw_get("EnablePHPXMLRPC"));
    $m["readline"]=intval(aw_get("EnablePHPReadline"));
    $m["gcharts"]=intval(aw_get("EnableGoogleCharts"));

    $m["lifetime"]=intval(aw_get("SessionCookieLifetime"));
    $m["inactivity"]=intval(aw_get("SessionInactivitytime"));

    $m["old_login"]=intval(aw_get("ArticaWebOldLogin"));
    $m["dropdown"]=intval(aw_get("StandardDropDown"));
    $m["showpwd"]=intval(aw_get("EnableShowPasswords"));
    $m["speedjs"]=intval(aw_get("RemoveHeadjs"))==0?1:0;

    $sched=unserialize(aw_get("ArticaWebConsoleRestartSchedule"));
    if(!is_array($sched)){$sched=array();}
    $m["restart_h"]=isset($sched["H"])?intval($sched["H"]):2;
    $m["restart_m"]=isset($sched["M"])?intval($sched["M"]):45;
    $qe=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $l=$qe->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM webconsole_events");
    $m["events"]=is_array($l)?intval($l["tcount"] ?? 0):0;
    $m["version"]=aw_get("APP_NGINX_CONSOLE_VERSION");
    return $m;
}

// Certificat client : off, pas de certificat, defini mais pas encore applique
// par nginx, installe.
function aw_sslc_state($enabled):string{
    if(!$enabled){return "off";}
    $arr=unserialize(base64_decode(aw_get("LighttpdServerCertificate")));
    if(!is_array($arr) || !isset($arr["ca_data"]) || !isset($arr["crt_data"])){return "nocert";}
    $x=@openssl_x509_parse(base64_decode((string)$arr["crt_data"]));
    if(!is_array($x) || !isset($x["subject"])){return "nocert";}
    foreach(explode("\n",(string)@file_get_contents("/etc/artica-postfix/webconsole.conf")) as $line){
        if(preg_match("#^\s*ssl_client_certificate\s+#",$line)){return "installed";}
    }
    return "pending";
}

// ---- Elements ----
function aw_chip($kind,$text,$icon=""):string{
    $ico=$icon<>""?"<span class='aw-ico'><i class='$icon'></i></span>":"";
    $tone=($kind=="on")?"ok":$kind;
    return "<span class='ak-pill ak-$tone'>$ico$text</span>";
}

function aw_chip_bool($v):string{
    if(intval($v)==1){return aw_chip("on","{aw_on}","fas fa-check");}
    return aw_chip("off","{aw_off}","fas fa-minus");
}

// Reglage qui affaiblit la securite quand il est actif.
function aw_chip_risk($v):string{
    if(intval($v)==1){return aw_chip("warn","{aw_on}","fas fa-triangle-exclamation");}
    return aw_chip("off","{aw_off}","fas fa-minus");
}

function aw_tags($items):string{
    $h=array();
    foreach($items as $i){ $h[]="<span class='aw-tag ak-mono'>".aw_esc($i)."</span>"; }
    return "<span class='aw-tags'>".@implode("",$h)."</span>";
}

function aw_mb($mb):string{
    $mb=intval($mb);
    if($mb>=1024 && $mb%1024==0){return ($mb/1024)." GB";}
    if($mb>=1024){return round($mb/1024,1)." GB";}
    return "$mb MB";
}

function aw_card_open($icon,$title,$sub,$extra=""):string{
    return "<section class='ak-card aw-card$extra'><div class='aw-head'><span class='aw-head-ico'><i class='$icon'></i></span>"
        ."<h3 class='aw-title'>$title</h3><span class='aw-sub'>$sub</span></div><div class='aw-body'>";
}

function aw_card_close():string{
    return "</div></section>";
}

function aw_row($m,$label,$value,$js=""):string{
    if($js<>"" && $m["admin"]){
        return "<div class='aw-row aw-click' role='button' tabindex='0' aria-label=\"{aw_edit}: $label\" OnClick=\"$js\" "
            ."onkeydown=\"if(event.key==='Enter'||event.key===' '){this.click();return false;}\">"
            ."<span class='aw-label'>$label</span><span class='aw-value'>$value</span>"
            ."<span class='aw-pen' aria-hidden='true'><i class='fas fa-pen'></i></span></div>";
    }
    return "<div class='aw-row'><span class='aw-label'>$label</span><span class='aw-value'>$value</span><span class='aw-pen-sp'></span></div>";
}

// ---- Colonne gauche ----
function aw_card_sslc($m):string{
    $st=$m["sslc"];
    $map=array(
        "off"=>array("off","{aw_off}","fas fa-circle-minus","{aw_sslc_off_text}"),
        "nocert"=>array("warn","{aw_no_certificate}","fas fa-triangle-exclamation","{aw_sslc_nocert_text}"),
        "pending"=>array("warn","{aw_pending}","fas fa-hourglass-half","{aw_sslc_pending_text}"),
        "installed"=>array("on","{aw_installed}","fas fa-certificate","{aw_sslc_installed_text}"),
    );
    list($kind,$label,$icon,$text)=$map[$st];
    $btn="";
    if($m["admin"]){
        $page=CurrentPageName();
        if($st=="off"){
            $btn="<button type='button' class='ak-btn' OnClick=\"Loadjs('fw.articaweb.cert.php?check=yes');\">{aw_activate}</button>";
        }elseif($st=="installed"){
            $btn="<button type='button' class='ak-btn ak-btn-danger' OnClick=\"Loadjs('$page?aw-sslc-remove-js=yes');\">"
                ."<span class='aw-ico'><i class='fas fa-trash-can'></i></span>{aw_remove}</button>";
        }else{
            $btn="<button type='button' class='ak-btn' OnClick=\"Loadjs('fw.articaweb.cert.php');\">{aw_edit}</button>";
        }
    }
    $h=array();
    $h[]=aw_card_open("fas fa-id-card","{authenticate_ssl_client}","");
    $h[]="<div class='aw-sslc'>".aw_chip($kind,$label,$icon)."</div>";
    $h[]="<p class='aw-note'>$text</p>";
    if($btn<>""){$h[]="<div class='aw-actions'>$btn</div>";}
    $h[]=aw_card_close();
    return @implode("",$h);
}

function aw_card_sessions($m):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?sessions-js=yes')";
    $lt=aw_session_lifetimes();
    $life=isset($lt[$m["lifetime"]])?$lt[$m["lifetime"]]:intval($m["lifetime"])." {minutes}";
    $inac=$m["inactivity"]>0?intval($m["inactivity"])." {minutes}":"{never}";
    $h=array();
    $h[]=aw_card_open("fas fa-clock","{aw_sessions}","{aw_sessions_sub}");
    $h[]=aw_row($m,"{aw_max_lifetime}","<span class='aw-num'>$life</span>",$js);
    $h[]=aw_row($m,"{inactivity_time}","<span class='aw-num'>$inac</span>",$js);
    $h[]=aw_card_close();
    return @implode("",$h);
}

function aw_card_behaviour($m):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?general-js=yes')";
    $h=array();
    $h[]=aw_card_open("fas fa-sliders","{aw_behaviour}","{aw_behaviour_sub}");
    $h[]=aw_row($m,"{aw_legacy_login}",aw_chip_bool($m["old_login"]),$js);
    $h[]=aw_row($m,"{standard_dropdown}",aw_chip_bool($m["dropdown"]),$js);
    $h[]=aw_row($m,"{EnableShowPasswords}",aw_chip_risk($m["showpwd"]),$js);
    $h[]=aw_row($m,"{speedjs}",aw_chip_bool($m["speedjs"]),"Loadjs('$page?speedjs-js=yes')");
    $h[]=aw_card_close();
    return @implode("",$h);
}

// ---- Colonne droite ----
function aw_card_access($m):string{
    $page=CurrentPageName();
    $scheme=$m["ssl"]?"https":"http";
    $host=aw_esc($m["host"]);
    $where=count($m["ifaces"])==0?"{aw_all_interfaces}":aw_esc(@implode(", ",$m["ifaces"]));
    if($m["ns"]<>""){$where="{namespace} ".aw_esc($m["ns"]);}
    $chips=array();
    $chips[]="<span class='ak-pill ak-off'>$where</span>";
    if($m["ssl"]){
        $cert=$m["cert"]<>""?aw_esc($m["cert"]):"{aw_default_cert}";
        $chips[]=aw_chip("on","SSL &middot; $cert","fas fa-lock");
    }else{
        $chips[]=aw_chip("warn","{aw_no_ssl}","fas fa-lock-open");
    }
    if(!$m["http2_avail"]){
        $chips[]="<span title=\"{aw_not_in_build}\">".aw_chip("off","HTTP2 &middot; {aw_unavailable}","fas fa-ban")."</span>";
    }else{
        $chips[]=aw_chip($m["http2"]==1?"on":"off","HTTP2 &middot; ".($m["http2"]==1?"{aw_on}":"{aw_off}"),$m["http2"]==1?"fas fa-check":"fas fa-minus");
    }
    $saved="";
    if($m["saved"]>0){
        $saved="<div class='aw-saved'><span>{aw_last_saved}</span><strong class='ak-mono'>".date("Y-m-d H:i",$m["saved"])."</strong></div>";
    }
    $h=array();
    $h[]="<section class='ak-card aw-card aw-hero'><div class='aw-hero-top'><div class='aw-hero-main'>";
    $h[]="<div class='aw-eyebrow'>{aw_primary_address}</div>";
    $h[]="<div class='aw-url-line'><span class='aw-url ak-mono'>$scheme://$host:{$m["port"]}</span>".@implode("",$chips)."</div>";
    $h[]="</div>$saved</div>";
    $h[]="<div class='aw-hero-links'>";
    $h[]=aw_hero_link($m,"fas fa-diagram-project","{use_reverse}",aw_reverse_value($m),"Loadjs('$page?reverse-proxy-js=yes')");
    $api=$m["api"]?"<span class='aw-tag ak-mono'>/".aw_esc($m["api_path"])."</span>":aw_chip("off","{aw_off}","fas fa-minus");
    $h[]=aw_hero_link($m,"fas fa-code","{inlude_rest_api}",$api,"Loadjs('$page?reverse-api-js=yes')");
    $h[]=aw_hero_link($m,"fas fa-file-certificate","{aw_mtls_clients}",aw_mtls_bridge_value(),"Loadjs('$page?mtls-bridge-js=yes')");
    $h[]="</div></section>";
    return @implode("",$h);
}

function aw_reverse_value($m):string{
    if(!$m["rev"]){return aw_chip("off","{aw_off}","fas fa-minus");}
    $iface=$m["rev_iface"]==""?"*":aw_esc($m["rev_iface"]);
    $v="<span class='aw-tag ak-mono'>$iface:{$m["rev_port"]}</span>";
    if($m["rev_pproto"]){$v.=aw_chip("on","{proxy_protocol}","fas fa-check");}
    return $v;
}

// ---- Acces clients mTLS : pont console web <-> passerelle SquidMTLSClient ----
// nginx relaie /mtls/... et /ssh-mtls a la socket du pont sans verifier la chaine
// du certificat client ; SquidMTLSClient verifie tout. Activation et retour
// arriere : POST /webconsole/mtls-bridge (rest_mtls_bridge.go).

/** L'etat du pont, ou null. */
function aw_mtls_bridge_state(){
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/mtls-bridge"));
    if(!is_object($json) || empty($json->success) || !isset($json->data) || !is_object($json->data)){return null;}
    return $json->data;
}

function aw_mtls_bridge_value():string{
    $st=aw_mtls_bridge_state();
    if($st===null){return aw_chip("off","{error}","fas fa-circle-xmark");}
    if(empty($st->enabled)){return aw_chip("off","{aw_off}","fas fa-minus");}
    if(empty($st->running) || empty($st->configured)){return aw_chip("warn","{aw_mtls_bridge_incomplete}","fas fa-triangle-exclamation");}
    return "<span class='aw-tag ak-mono'>/mtls/</span>".aw_chip("on","{aw_on}","fas fa-check");
}

function aw_mtls_bridge_js():bool{
    $tpl=new template_admin();
    if(!aw_is_admin()){return $tpl->js_no_privileges();}
    $page=CurrentPageName();
    return $tpl->js_dialog2("{aw_mtls_clients}","$page?mtls-bridge-popup=yes",700);
}

function aw_mtls_bridge_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $st=aw_mtls_bridge_state();
    if($st===null){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error_backend}"));return false;}
    $html=array();
    if(empty($st->enabled) && trim((string)($st->refusal ?? ""))<>""){
        $html[]=$tpl->div_warning("{aw_mtls_clients}||".htmlspecialchars((string)$st->refusal));
    }
    $after="if(String(resp).indexOf('jserror:')===0){ctrl.input.checked=!ctrl.input.checked;"
        ."swal({title:'Oops...',text:'<strong>'+String(resp).substring(8)+'</strong>',html:true,type:'error'});}"
        ."LoadAjaxSilent('artica-web','$page?table-static=yes');";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$tpl->BigCircleCheckbox("WebConsoleEnablemTLSClient","{aw_mtls_clients}","{aw_mtls_clients_explain}",empty($st->enabled)?0:1,$after);
    return true;
}

function aw_mtls_bridge_save():bool{
    $tpl=new template_admin();
    if(!aw_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $enabled=intval($_POST["WebConsoleEnablemTLSClient"])==1;
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/webconsole/mtls-bridge",array("enabled"=>$enabled)));
    if(!is_object($json)){echo $tpl->post_error("{error_backend}");return false;}
    if(empty($json->success)){
        echo $tpl->post_error(isset($json->error) && trim((string)$json->error)<>""?(string)$json->error:"{error_backend}");
        return false;
    }
    return admin_tracks("Web console: mTLS clients access ".($enabled?"enabled":"disabled"));
}

function aw_hero_link($m,$icon,$label,$value,$js):string{
    $inner="<span class='aw-hl-ico'><i class='$icon'></i></span><span class='aw-hl-label'>$label</span><span class='aw-hl-val'>$value</span>";
    if(!$m["admin"]){return "<div class='aw-hl'>$inner</div>";}
    return "<button type='button' class='aw-hl aw-hl-btn' OnClick=\"$js\" aria-label=\"{aw_edit}: $label\">$inner"
        ."<span class='aw-pen' aria-hidden='true'><i class='fas fa-pen'></i></span></button>";
}

function aw_saml_alert($m):string{
    if(!$m["saml_on"] || count($m["saml_errors"])==0){return "";}
    $e=array();
    foreach($m["saml_errors"] as $err){ $e[]="<li>".aw_esc($err)."</li>"; }
    return "<div class='aw-alert' role='alert'><span class='aw-ico'><i class='fas fa-circle-exclamation'></i></span>"
        ."<div><strong>{saml_sso}: {saml_misconfigured}</strong> &mdash; {aw_saml_lockout}<ul>".@implode("",$e)."</ul></div></div>";
}

function aw_card_control($m):string{
    $page=CurrentPageName();
    $http="Loadjs('$page?http-js=yes')";
    $h=array();
    $h[]=aw_card_open("fas fa-shield-halved","{aw_access_control}","{aw_access_control_sub}");
    $h[]=aw_saml_alert($m);
    $h[]="<div class='aw-cols'><div class='aw-colx'>";
    $h[]=aw_row($m,"{authenticate_ssl_client}",aw_chip_bool($m["client_auth"]?1:0),$http);
    if($m["client_auth"]){
        $h[]=aw_row($m,"{AllowAuthenticateScreen}",aw_chip_bool($m["auth_screen"]),$http);
    }
    $al=aw_tags($m["authlink"]);
    if($m["authlink_default"]){$al.="<span class='aw-muted'>{aw_defaults}</span>";}
    $h[]=aw_row($m,"{clients_restrictions} (AUTH LINK)",$al,"Loadjs('fw.articaweb.authlink.restrictions.php?ClusterLists-js=yes')");
    if(!$m["saml_on"]){
        $saml=aw_chip("off","{aw_off}","fas fa-minus");
    }elseif(count($m["saml_errors"])>0){
        $saml=aw_chip("err","{aw_misconfigured}","fas fa-circle-xmark");
    }else{
        $saml=aw_chip("on","{aw_on}","fas fa-check");
    }
    $h[]=aw_row($m,"{saml_sso}",$saml,"Loadjs('$page?saml-js=yes')");
    $h[]=aw_row($m,"{Enable_XSS_Protection}",aw_chip_bool($m["xss"]),$http);
    $h[]="</div><div class='aw-colx'>";
    if($m["geoip"]){
        $c=$m["countries"]>0?"<span class='aw-num'>{$m["countries"]} {aw_countries}</span>":aw_chip("off","{aw_off}","fas fa-minus");
        $h[]=aw_row($m,"{limit_countries}",$c,"Loadjs('fw.articaweb.geoip.php')");
    }else{
        $h[]=aw_row($m,"{limit_countries}","<span title=\"{aw_geoip_off}\">".aw_chip("off","{aw_unavailable}","fas fa-ban")."</span>");
    }
    $h[]=aw_row($m,"{limit_access}","<span class='aw-num'><span id='CountOfStreamAccessModule'>{$m["stream_rules"]} {aw_items}</span></span>","Loadjs('fw.articaweb.ngx_stream_access_module.php')");
    $h[]=aw_row($m,"{EnableLockUnixConsole}",aw_chip_bool($m["lock_unix"]),"Loadjs('$page?unix-js=yes')");
    $ws=$m["webshell"]?"<span title=\"{aw_webshell_risk}\">".aw_chip_risk(1)."</span>":aw_chip_bool(0);
    $h[]=aw_row($m,"{webshell_console}",$ws,"Loadjs('$page?webshell-js=yes')");
    $h[]="</div></div>";
    $h[]=aw_card_close();
    return @implode("",$h);
}

function aw_ciphers_value($m):string{
    if($m["ciphers"]==""){return aw_chip("off","{aw_defaults}");}
    $list=array();
    foreach(explode(":",$m["ciphers"]) as $c){ $c=trim($c); if($c<>""){$list[]=$c;} }
    return "<details class='aw-details'><summary>{aw_custom} (".count($list).")</summary>".aw_tags($list)."</details>";
}

function aw_card_http($m):string{
    $page=CurrentPageName();
    $http="Loadjs('$page?http-js=yes')";
    $iface=count($m["ifaces"])==0?"<span class='aw-num'>{aw_all_interfaces}</span>":aw_tags($m["ifaces"]);
    if($m["ns"]<>""){$iface="<span class='aw-num'>{namespace} ".aw_esc($m["ns"])."</span>";}
    $h=array();
    $h[]=aw_card_open("fas fa-server","{http_engine}","{aw_http_sub}");
    $h[]=aw_row($m,"{listen_interface}",$iface."<span class='aw-tag ak-mono'>:{$m["port"]}</span>","Loadjs('$page?nic-js=yes')");
    if($m["ssl"]){
        $cert=$m["cert"]<>""?aw_esc($m["cert"]):"{aw_default_cert}";
        $h[]=aw_row($m,"{aw_ssl}","<span class='aw-tag ak-mono'>$cert</span>",$http);
        $h[]=aw_row($m,"{aw_ciphers}",aw_ciphers_value($m),$http);
    }else{
        $h[]=aw_row($m,"{aw_ssl}",aw_chip("warn","{aw_no_ssl}","fas fa-lock-open"),$http);
    }
    if($m["http2_avail"]){
        $h[]=aw_row($m,"HTTP2",aw_chip_bool($m["http2"]),$http);
    }else{
        $h[]=aw_row($m,"HTTP2","<span title=\"{aw_not_in_build}\">".aw_chip("off","{aw_unavailable}","fas fa-ban")."</span>");
    }
    $sub=$m["sub_module"]?aw_chip("on","{aw_module_present}","fas fa-check"):aw_chip("off","{aw_unavailable}","fas fa-ban");
    $h[]=aw_row($m,"{ngx_http_sub_module}",$sub);
    $h[]=aw_row($m,"{aw_additional_modules}","<button type='button' class='ak-link' OnClick=\"s_PopUp('/phpinfos','800','800');\">PHP info"
        ."<span class='aw-ico'><i class='fas fa-arrow-up-right-from-square'></i></span></button>");
    $h[]=aw_card_close();
    return @implode("",$h);
}

function aw_card_php($m):string{
    $page=CurrentPageName();
    $fpm="Loadjs('$page?phpfpm-js=yes')";
    $ses="Loadjs('$page?sessions-js=yes')";
    $h=array();
    $h[]=aw_card_open("fas fa-microchip","{aw_php_engine}","{aw_php_sub}");
    $h[]=aw_row($m,"{webconsole}","<span class='aw-num'>{$m["fpm_children"]} {aw_children} &middot; {$m["fpm_requests"]} {aw_req_child}</span>",$fpm);
    $h[]=aw_row($m,"{artica-framework}","<span class='aw-num'>{$m["fw_children"]} {aw_children} &middot; {$m["fw_requests"]} {aw_req_child}</span>",$fpm);
    $h[]=aw_row($m,"{php5UploadMaxFileSize}","<span class='aw-num'>".aw_mb($m["upload"])."</span>",$ses);
    $h[]=aw_row($m,"{php5PostMaxSize}","<span class='aw-num'>".aw_mb($m["post"])."</span>",$ses);
    $h[]=aw_row($m,"{php5MemoryLimit}","<span class='aw-num'>".aw_mb($m["memory"])."</span>",$ses);
    $h[]=aw_row($m,"{aw_xmlrpc}",aw_chip_bool($m["xmlrpc"]),$ses);
    $h[]=aw_row($m,"{aw_readline}",aw_chip_bool($m["readline"]),$ses);
    $h[]=aw_row($m,"{APP_GOOGLE_CHARTS}",aw_chip_bool($m["gcharts"]),$ses);
    $h[]=aw_card_close();
    return @implode("",$h);
}

function aw_card_maintenance($m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $h=array();
    $h[]=aw_card_open("fas fa-screwdriver-wrench","{aw_maintenance}","{aw_maintenance_sub}");
    $h[]="<div class='aw-cols'><div class='aw-colx'>";
    $at=sprintf("%02d:%02d",$m["restart_h"],$m["restart_m"]);
    $h[]=aw_row($m,"{aw_daily_restart}","<span class='aw-num'>{aw_every_day_at} <strong class='ak-mono'>$at</strong></span>","Loadjs('$page?restart-schedule-js=yes')");
    $h[]="</div><div class='aw-colx'>";
    if($m["events"]>0){
        $h[]=aw_row($m,"{events}","<span class='aw-num'>".$tpl->FormatNumber($m["events"])." {aw_events}</span>","Loadjs('fw.articaweb.events.php')");
    }else{
        $h[]=aw_row($m,"{events}",aw_chip("off","{aw_no_events}"));
    }
    $h[]="</div></div>";
    $h[]=aw_card_close();
    return @implode("",$h);
}

function aw_tiny($m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $top=array();
    $top[]=array("Loadjs('$page?reload-js=yes')",ico_refresh,"{aw_reload}");
    $top[]=array("Loadjs('$page?aw-restart-confirm-js=yes')","fas fa-power-off","{restart}");
    $top[]=array($tpl->framework_buildjs("/system/htopweb/restart","htopweb.restart.progress","htopweb.restart.progress.log",
        "progress-articaweb-restart",null,null,null,"AsSystemAdministrator"),ico_refresh,"{restart} HTOPWEB");
    if($m["events"]>0){
        $top[]=array("Loadjs('fw.articaweb.events.php')",ico_eye,$tpl->FormatNumber($m["events"])." {events}");
    }
    $T=array();
    $T["TITLE"]="{web_interface_settings} v".$m["version"];
    $T["ICO"]="far fa-browser";
    $T["EXPL"]="{web_interface_settings_text}";
    $T["URL"]="webconsole";
    $T["BUTTONS"]=$tpl->table_buttons($top);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($T)))."');";
}

// ?aw-restart-confirm-js=yes : redemarrer coupe la console de tous les administrateurs.
function aw_restart_confirm_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!aw_is_admin()){return $tpl->js_no_privileges();}
    return $tpl->js_dialog_confirm_action("{aw_confirm_restart}","aw-restart-confirm","none","Loadjs('$page?restart-js=yes');");
}

function aw_restart_confirm_save():bool{
    $tpl=new template_admin();
    if(!aw_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    return admin_tracks("Confirmed the restart of the Web Console");
}

// ?aw-sslc-remove-js=yes : retirer l'authentification par certificat client.
function aw_sslc_remove_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!aw_is_admin()){return $tpl->js_no_privileges();}
    return $tpl->js_dialog_confirm_action("{aw_confirm_sslc_remove}","aw-sslc-remove","none",
        "LoadAjaxSilent('artica-web','$page?table-static=yes');");
}

function aw_sslc_remove_save():bool{
    $tpl=new template_admin();
    if(!aw_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdArticaClientAuth",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerClientAuth","");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerCertDown",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdServerCertificate","");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerEnforce",0);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/reconfigure");
    return admin_tracks("Removed the client certificate authentication of the Web Console");
}

function aw_style():string{
    if(isset($GLOBALS["AW_STYLE"])){return "";}
    $GLOBALS["AW_STYLE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.aw{color:#111827;margin-top:16px}
.aw *{box-sizing:border-box}
.aw-ico{display:inline-flex;font-size:12px}
.aw-layout{display:grid;grid-template-columns:335px minmax(0,1fr);gap:20px;align-items:start}
.aw-side,.aw-main{display:flex;flex-direction:column;gap:16px;min-width:0}
.aw-pair{display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:16px;align-items:start}
.aw-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(360px,1fr));column-gap:28px}
.aw-colx{min-width:0}
.aw-card{padding:16px 20px}
.aw-head{display:flex;align-items:center;gap:10px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap}
.aw-head-ico{width:30px;height:30px;border-radius:8px;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:14px;flex:0 0 auto}
.aw-title{margin:0;font-size:16px;font-weight:600;line-height:20px;color:#111827;text-transform:none}
.aw-sub{margin-left:auto;font-size:13px;color:#6b7280;text-align:right}
.aw-row{display:flex;align-items:center;gap:10px;min-height:44px;padding:6px 0;border-top:1px solid #f1f5f9}
.aw-body>.aw-row:first-child,.aw-colx>.aw-row:first-child{border-top:0}
.aw-click{cursor:pointer;margin:0 -10px;padding-left:10px;padding-right:10px;border-radius:6px}
.aw-click:hover{background:#f8fafc}
.aw-click:focus-visible,.aw-hl-btn:focus-visible,.aw-details summary:focus-visible{outline:2px solid $dark;outline-offset:2px}
.aw-label{flex:1 1 auto;min-width:96px;font-size:14px;color:#374151}
.aw-value{display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:6px;flex:0 1 auto;min-width:0;max-width:72%;font-size:14px;font-weight:500;color:#111827;text-align:right}
.aw-pen,.aw-pen-sp{flex:0 0 14px;color:#9ca3af;font-size:12px}
.aw-pen{opacity:0;transition:opacity .15s}
.aw-click:hover .aw-pen,.aw-click:focus-visible .aw-pen,.aw-hl-btn:hover .aw-pen,.aw-hl-btn:focus-visible .aw-pen{opacity:1}
.aw-num{white-space:nowrap}
.aw-muted{font-size:13px;font-weight:400;color:#6b7280}
.aw-tags{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:4px;min-width:0}
.aw-tag{display:inline-block;max-width:100%;padding:2px 8px;border:1px solid #e5e7eb;border-radius:6px;background:#f9fafb;font-size:13px;font-weight:400;color:#1f2937;overflow-wrap:anywhere;text-align:left}
.aw-details{text-align:right;max-width:100%}
.aw-details summary{cursor:pointer;font-weight:600;color:$dark;list-style-position:inside}
.aw-details .aw-tags{margin-top:8px}
.aw-hero{border-top:3px solid $dark;padding:18px 22px}
.aw-hero-top{display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap}
.aw-hero-main{flex:1 1 auto;min-width:0}
.aw-eyebrow{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7280}
.aw-url-line{display:flex;align-items:center;flex-wrap:wrap;gap:8px 10px;margin-top:6px}
.aw-url{font-size:24px;font-weight:600;line-height:32px;color:#111827;overflow-wrap:anywhere}
.aw-saved{display:flex;flex-direction:column;align-items:flex-end;margin-left:auto;gap:2px;font-size:13px;color:#6b7280;white-space:nowrap}
.aw-saved strong{color:#111827;font-weight:500}
.aw-hero-links{display:flex;flex-wrap:wrap;gap:8px 24px;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9}
.aw-hl{display:inline-flex;align-items:center;flex-wrap:wrap;gap:8px;font-size:14px;color:#374151;background:transparent;border:0;padding:4px 6px;margin:0 -6px;border-radius:6px;text-align:left}
.aw-hl-btn{cursor:pointer}
.aw-hl-btn:hover{background:#f8fafc}
.aw-hl-ico{color:$dark;display:inline-flex}
.aw-hl-val{display:inline-flex;flex-wrap:wrap;gap:6px;align-items:center}
.aw-alert{display:flex;gap:10px;align-items:flex-start;margin:12px 0 4px;padding:12px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:14px;line-height:20px}
.aw-alert .aw-ico{font-size:16px;margin-top:2px}
.aw-alert ul{margin:6px 0 0;padding-left:18px}
.aw-sslc{margin-top:14px}
.aw-note{margin:10px 0 0;font-size:13px;color:#6b7280;line-height:19px}
.aw-actions{display:flex;justify-content:flex-end;margin-top:12px}
@media (max-width:1200px){.aw-layout{grid-template-columns:1fr}}
@media (prefers-reduced-motion:reduce){.aw-pen{transition:none}}
</style>";
}
// Fin de l'onglet Parameters


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

    if($APP_NGINX_CONSOLE_HTTPV2==0){
        $_POST["UseHttp2"]=0;
    }


    if(isset($_POST["LighttpdArticaCertificateName"])){
        writelogs("LighttpdArticaCertificateName={$_POST["LighttpdArticaCertificateName"]}",__FUNCTION__,__FILE__,__LINE__);
        // Validate only a certificate the operator CHANGED. The form always posts
        // the current value; when it is the daemon's default (the host name, not
        // in the certificates database — nginx then serves the default
        // certificate), checking it refused every save of this form, XSS
        // protection included ("Could not find configuration for artica-lb").
        $CurrentCertificateName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaCertificateName"));
        if(strlen($_POST["LighttpdArticaCertificateName"])>3 && $_POST["LighttpdArticaCertificateName"]!==$CurrentCertificateName){
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
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/client-ca/create");
            }
        }
    }


return true;
}

function status(){
    $sock=new sockets();
    $tpl                        = new template_admin();
    $page                       = CurrentPageName();
    // Le statut arrive dans Info. Le fichier articaweb.status n'est relu que face
    // a un articarest plus ancien, qui renvoyait Info vide.
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/webconsole/status"));
    $ini                        = new Bs_IniHandler();
    if(is_object($json) && isset($json->Info) && trim((string)$json->Info)<>""){
        $ini->loadString((string)$json->Info);
    }else{
        $ini                    = new Bs_IniHandler(PROGRESS_DIR."/articaweb.status");
    }
    echo $tpl->SERVICE_STATUS($ini, "APP_ARTICAWEBCONSOLE");
    echo $tpl->SERVICE_STATUS($ini, "ARTICA_PHPFPM");


    // Le code a usage unique de la console a ete retire: il reposait sur un
    // secret UNIQUE partage par tous les administrateurs (Artica2FAToken), ce
    // qui n'est pas une authentification a deux facteurs par identite.
    // L'authentification multifacteur est desormais celle du fournisseur
    // d'identite, via l'authentification unique SAML.
    // Le code a usage unique de SSH (route daemon /ssh/2fa/qrcode) n'est pas concerne.
}

/**
 * =====================================================================
 *  Authentification federee SAML
 * =====================================================================
 * Conception: docs/superpowers/specs/2026-08-12-webconsole-saml-sso-design.md
 * La lecture et la validation vivent dans ressources/class.saml.sp.inc; cette
 * page ne fait que presenter et ecrire.
 */

function saml_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{saml_sso}","$page?saml-popup=yes",860);
}

/**
 * Formulaire de configuration.
 *
 * Le certificat est presente decode (PEM lisible) et re-encode a l'ecriture:
 * il est stocke en base64 dans Valkey.
 */
/**
 * Etat du materiel de signature du fournisseur de service.
 *
 * N'expose QUE des elements publics -- sujet, empreinte, validite. La cle privee ne quitte
 * jamais le serveur: la relire pour l'afficher n'apporterait rien et l'exposerait au cache du
 * navigateur, aux captures d'ecran et aux journaux de proxy.
 */
function saml_signing_state($tpl,$page):string{
    $cert=saml_sp::sp_cert();
    if($cert===""){
        return $tpl->div_info("{saml_sp_key_none}||{saml_sp_key_none_explain}")
              ."<div style='margin:8px 0 14px 0;text-align:right'>"
              ."<a href='javascript:blur();' class='btn btn-default btn-sm' "
              ."OnClick=\"Loadjs('$page?saml-genkey-js=yes')\">{saml_sp_key_generate}</a></div>";
    }

    $pem="-----BEGIN CERTIFICATE-----\n".chunk_split($cert,64,"\n")."-----END CERTIFICATE-----\n";
    $x=@openssl_x509_parse($pem);
    $subject=is_array($x)&&isset($x["subject"]["CN"])?strval($x["subject"]["CN"]):"?";
    $until=is_array($x)&&isset($x["validTo_time_t"])?intval($x["validTo_time_t"]):0;
    $fp=@openssl_x509_fingerprint($pem,"sha256");
    $ready=saml_sp::sp_signing_ready();

    $rows=array();
    $rows[]="<b>{saml_sp_key_subject}</b>: ".htmlspecialchars($subject);
    if($until>0){ $rows[]="<b>{saml_sp_key_until}</b>: ".date("Y-m-d",$until); }
    if(is_string($fp)&&$fp!==""){ $rows[]="<b>SHA-256</b>: <span style='font-family:monospace;font-size:11px'>".htmlspecialchars($fp)."</span>"; }

    $box=$ready
        ? $tpl->div_success("{saml_sp_key_ok}||".implode(" &nbsp;|&nbsp; ",$rows))
        : $tpl->div_error("{saml_err_sp_key_mismatch}||".implode(" &nbsp;|&nbsp; ",$rows));

    return $box."<div style='margin:8px 0 14px 0;text-align:right'>"
          ."<a href='javascript:blur();' class='btn btn-default btn-sm' "
          ."OnClick=\"Loadjs('$page?saml-genkey-js=yes')\">{saml_sp_key_regenerate}</a></div>";
}

/**
 * Generation d'un couple de signature auto-signe.
 *
 * Un certificat de signature de fournisseur de service est presque toujours auto-signe: il ne
 * sert pas a prouver une identite au sens TLS, seulement a lier les messages a une cle que le
 * fournisseur connait par les metadonnees. Le generer ici evite a l'exploitant d'aller le
 * fabriquer ailleurs -- et surtout evite qu'il colle une cle de serveur web, reutilisation
 * qu'on veut decourager.
 *
 * ATTENTION: regenerer INVALIDE la confiance existante. Le fournisseur verifie avec l'ancien
 * certificat tant que les metadonnees ne sont pas reimportees.
 */
function saml_genkey_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $title=addslashes($tpl->javascript_parse_text("{saml_sp_key_generate}"));
    $text=addslashes($tpl->javascript_parse_text("{saml_sp_key_generate_warn}"));
    $yes=addslashes($tpl->javascript_parse_text("{yes}"));
    $cancel=addslashes($tpl->javascript_parse_text("{cancel}"));
    // swal() et non confirm(): convention du depot.
    echo "swal({ title:'$title', text:'$text', type:'warning', showCancelButton:true,"
        ." confirmButtonColor:'#ed5565', confirmButtonText:'$yes', cancelButtonText:'$cancel' },"
        ." function(isConfirm){ if(!isConfirm){return;}"
        ." LoadAjaxTiny('saml-check','$page?saml-genkey=yes');"
        ." Loadjs('$page?saml-js=yes'); });";
    return true;
}

function saml_genkey():bool{
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];

    if(!function_exists("openssl_pkey_new")){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{saml_err_sp_key_generate}: openssl"));
        return false;
    }
    $cn=preg_replace('#^https?://#','',saml_sp::base_url());
    if(trim($cn)===""){ $cn="artica-webconsole"; }

    $key=@openssl_pkey_new(array("private_key_bits"=>2048,"private_key_type"=>OPENSSL_KEYTYPE_RSA));
    if($key===false){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{saml_err_sp_key_generate}"));
        return false;
    }
    // 10 ans: un certificat de signature SAML expire est une panne totale et silencieuse,
    // et rien ici ne surveille son echeance. Mieux vaut une duree longue et une regeneration
    // deliberee qu'une expiration un dimanche.
    $csr=@openssl_csr_new(array("commonName"=>$cn),$key,array("digest_alg"=>"sha256"));
    $crt=($csr===false)?false:@openssl_csr_sign($csr,null,$key,3650,array("digest_alg"=>"sha256"));
    $keyPem=""; $crtPem="";
    if($crt!==false){ @openssl_x509_export($crt,$crtPem); @openssl_pkey_export($key,$keyPem); }
    if($crtPem===""||$keyPem===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{saml_err_sp_key_generate}"));
        return false;
    }

    $body=preg_replace('/\s+/','',preg_replace('/-----[A-Z ]+-----/','',$crtPem));
    $sock->SET_INFO("ArticaWebSAMLSpCert",base64_encode($body));
    // La cle garde ses en-tetes: voir saml_sp::sp_key().
    $sock->SET_INFO("ArticaWebSAMLSpKey",base64_encode(trim($keyPem)));
    saml_sp::audit("sp-key-generated",$cn);
    admin_tracks("SAML: generated a new service provider signing key");
    return true;
}

function saml_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $sock=$GLOBALS["CLASS_SOCKETS"];

    $enabled=intval($sock->GET_INFO("ArticaWebSAML"));
    $cert=saml_sp::idp_cert();
    $certPem="";
    if($cert!==""){
        $certPem="-----BEGIN CERTIFICATE-----\n".chunk_split($cert,64,"\n")."-----END CERTIFICATE-----";
    }

    $form=array();
    $form[]=$tpl->field_checkbox("ArticaWebSAML","{enable_feature}",$enabled);
    $form[]=$tpl->field_text("ArticaWebSAMLIdpEntityId","{saml_idp_entityid}",
        $sock->GET_INFO("ArticaWebSAMLIdpEntityId"),true,"{saml_idp_entityid_explain}");
    $form[]=$tpl->field_text("ArticaWebSAMLIdpSSO","{saml_idp_sso}",
        $sock->GET_INFO("ArticaWebSAMLIdpSSO"),true,"{saml_idp_sso_explain}");
    $form[]=$tpl->field_text("ArticaWebSAMLIdpSLO","{saml_idp_slo}",
        $sock->GET_INFO("ArticaWebSAMLIdpSLO"),false,"{saml_idp_slo_explain}");
    // field_textarea_normal(name,label,value,width,height,explain): la variante
    // simple, qui affiche vraiment la zone de saisie -- field_textarea() la
    // masque derriere un resume cliquable, peu pratique pour coller un PEM.
    $form[]=$tpl->field_textarea_normal("ArticaWebSAMLIdpCert","{saml_idp_cert}",
        $certPem,"100%","190px","{saml_idp_cert_explain}");
    $form[]=$tpl->field_text("ArticaWebSAMLSpEntityId","{saml_sp_entityid}",
        saml_sp::sp_entity_id(),false,"{saml_sp_entityid_explain}");
    $form[]=$tpl->field_text("ArticaWebSAMLBaseUrl","{saml_base_url}",
        $sock->GET_INFO("ArticaWebSAMLBaseUrl"),false,"{saml_base_url_explain}");
    $form[]=$tpl->field_text("ArticaWebSAMLNameIdField","{saml_nameid_field}",
        $sock->GET_INFO("ArticaWebSAMLNameIdField"),false,"{saml_nameid_field_explain}");

    // Signature des messages sortants. LA CLE PRIVEE N'EST JAMAIS RENVOYEE AU NAVIGATEUR:
    // on n'affiche que l'etat du materiel. La remplacer se fait en collant un nouveau couple,
    // ou en regenerant -- jamais en relisant ce qui est stocke.
    $form[]=$tpl->field_checkbox("ArticaWebSAMLSignAuthn","{saml_sign_authn}",
        intval($sock->GET_INFO("ArticaWebSAMLSignAuthn")),false,"{saml_sign_authn_explain}");
    $form[]=$tpl->field_checkbox("ArticaWebSAMLSignLogout","{saml_sign_logout}",
        intval($sock->GET_INFO("ArticaWebSAMLSignLogout")),false,"{saml_sign_logout_explain}");
    $form[]=$tpl->field_textarea_normal("ArticaWebSAMLSpKeyIn","{saml_sp_key}",
        "","100%","120px","{saml_sp_key_explain}");
    $form[]=$tpl->field_textarea_normal("ArticaWebSAMLSpCertIn","{saml_sp_cert}",
        "","100%","120px","{saml_sp_cert_explain}");

    $html=array();
    $html[]=$tpl->div_explain("{saml_sso}||{saml_sso_explain}");
    $html[]=saml_signing_state($tpl,$page);
    $html[]=$tpl->form_outside(null,@implode("\n",$form),null,"{apply}",
        "dialogInstance2.close();LoadAjaxSilent('artica-web','$page?table-static=yes')",$security,false);
    $html[]="<div style='margin-top:12px;text-align:right'>";
    $html[]="<a href='javascript:blur();' class='btn btn-default btn-sm' "
           ."OnClick=\"LoadAjaxTiny('saml-check','$page?saml-check=yes')\">{saml_check}</a>&nbsp;";
    $html[]="<a href='fw.login.saml.php?metadata=yes' target='_blank' "
           ."class='btn btn-default btn-sm'>{saml_metadata}</a>";
    $html[]="</div>";
    $html[]="<div id='saml-check' style='margin-top:12px'></div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

/**
 * Ecriture.
 *
 * Ne passe PAS par SAVE_POSTs(): le certificat doit etre encode, et surtout
 * l'activation doit etre refusee tant que la configuration ne tient pas. Une
 * activation avec une configuration invalide rend la console inaccessible: le
 * seul retour possible serait la console Unix.
 *
 * form_outside considere toute reponse non vide comme une erreur: on n'echoit
 * donc rien en cas de succes.
 */
function saml_save():bool{
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $tpl->CLEAN_POST();

    // Le certificat arrive en PEM (ou en corps base64 nu): on stocke le corps
    // encode en base64, ce que idp_cert() sait relire dans les deux sens.
    $certIn=trim(strval($_POST["ArticaWebSAMLIdpCert"] ?? ""));
    $certBody=preg_replace('/\s+/','',preg_replace('/-----[A-Z ]+-----/','',$certIn));
    $sock->SET_INFO("ArticaWebSAMLIdpCert",$certBody===""?"":base64_encode($certBody));

    $sock->SET_INFO("ArticaWebSAMLIdpEntityId",trim(strval($_POST["ArticaWebSAMLIdpEntityId"] ?? "")));
    $sock->SET_INFO("ArticaWebSAMLIdpSSO",trim(strval($_POST["ArticaWebSAMLIdpSSO"] ?? "")));
    $sock->SET_INFO("ArticaWebSAMLIdpSLO",trim(strval($_POST["ArticaWebSAMLIdpSLO"] ?? "")));
    $sock->SET_INFO("ArticaWebSAMLSpEntityId",trim(strval($_POST["ArticaWebSAMLSpEntityId"] ?? "")));
    $sock->SET_INFO("ArticaWebSAMLBaseUrl",rtrim(trim(strval($_POST["ArticaWebSAMLBaseUrl"] ?? "")),"/"));
    $sock->SET_INFO("ArticaWebSAMLNameIdField",trim(strval($_POST["ArticaWebSAMLNameIdField"] ?? "")));

    // Materiel de signature du fournisseur de service.
    // LES DEUX CHAMPS SONT TOUJOURS AFFICHES VIDES (la cle n'est jamais renvoyee au
    // navigateur): un champ vide signifie donc "ne pas toucher", JAMAIS "effacer". Sans cette
    // regle, le premier enregistrement des reglages voisins detruirait la cle.
    $keyIn=trim(strval($_POST["ArticaWebSAMLSpKeyIn"] ?? ""));
    if($keyIn!==""){
        // Conservee avec ses en-tetes: PKCS#1 et PKCS#8 ne s'enveloppent pas pareil.
        $sock->SET_INFO("ArticaWebSAMLSpKey",base64_encode($keyIn));
    }
    $spCertIn=trim(strval($_POST["ArticaWebSAMLSpCertIn"] ?? ""));
    if($spCertIn!==""){
        $spBody=preg_replace('/\s+/','',preg_replace('/-----[A-Z ]+-----/','',$spCertIn));
        $sock->SET_INFO("ArticaWebSAMLSpCert",$spBody===""?"":base64_encode($spBody));
    }
    $sock->SET_INFO("ArticaWebSAMLSignAuthn",intval($_POST["ArticaWebSAMLSignAuthn"] ?? 0));
    $sock->SET_INFO("ArticaWebSAMLSignLogout",intval($_POST["ArticaWebSAMLSignLogout"] ?? 0));

    $wanted=intval($_POST["ArticaWebSAML"] ?? 0);
    if($wanted==1){
        // GET_INFO memorise les valeurs pour la duree de la requete: les
        // controles ci-dessous doivent porter sur ce qui vient d'etre ecrit.
        $errors=saml_check_errors_fresh();
        if(count($errors)>0){
            $sock->SET_INFO("ArticaWebSAML","0");
            echo $tpl->_ENGINE_parse_body("{saml_refuse_enable}<br>".@implode("<br>",$errors));
            return false;
        }
    }
    $sock->SET_INFO("ArticaWebSAML",strval($wanted));
    admin_tracks_post("Save WebConsole SAML single sign-on settings (enabled=$wanted)");
    return true;
}

/**
 * Controles de configuration sur les valeurs POSTees.
 *
 * saml_sp::config_errors() relit Valkey, dont la couche socket a mis les
 * anciennes valeurs en cache pour la duree de la requete: on refait donc les
 * memes controles sur ce que l'exploitant vient de soumettre.
 */
function saml_check_errors_fresh():array{
    $err=array();
    if(trim(strval($_POST["ArticaWebSAMLIdpEntityId"] ?? ""))===""){ $err[]="{saml_err_idp_entityid}"; }

    $sso=trim(strval($_POST["ArticaWebSAMLIdpSSO"] ?? ""));
    if($sso===""){ $err[]="{saml_err_idp_sso}"; }
    elseif(!filter_var($sso,FILTER_VALIDATE_URL)){ $err[]="{saml_err_idp_sso_invalid}"; }

    $slo=trim(strval($_POST["ArticaWebSAMLIdpSLO"] ?? ""));
    if($slo!=="" && !filter_var($slo,FILTER_VALIDATE_URL)){ $err[]="{saml_err_idp_slo_invalid}"; }

    $certIn=trim(strval($_POST["ArticaWebSAMLIdpCert"] ?? ""));
    $body=preg_replace('/\s+/','',preg_replace('/-----[A-Z ]+-----/','',$certIn));
    if($body===""){ $err[]="{saml_err_idp_cert}"; return $err; }
    $pem="-----BEGIN CERTIFICATE-----\n".chunk_split($body,64,"\n")."-----END CERTIFICATE-----\n";
    $x=@openssl_x509_parse($pem);
    if(!is_array($x)){ $err[]="{saml_err_idp_cert_invalid}"; }
    elseif(isset($x["validTo_time_t"]) && intval($x["validTo_time_t"])>0 && intval($x["validTo_time_t"])<time()){
        $err[]="{saml_err_idp_cert_expired}";
    }
    return $err;
}

/**
 * Verification, telle que la console peut la faire seule.
 *
 * Elle ne valide PAS l'application declaree cote fournisseur d'identite: seule
 * une vraie connexion le fera. Elle dit ce qui est lisible ici et ce que
 * l'exploitant doit declarer la-bas.
 */
function saml_check():bool{
    $tpl=new template_admin();
    $errors=saml_sp::config_errors();
    $html=array();

    if(count($errors)>0){
        $html[]=$tpl->div_error("{saml_check_failed}<br>".@implode("<br>",$errors));
    }else{
        $html[]=$tpl->div_success("{saml_check_ok}");
    }

    $cert=saml_sp::idp_cert();
    if($cert!==""){
        $pem="-----BEGIN CERTIFICATE-----\n".chunk_split($cert,64,"\n")."-----END CERTIFICATE-----\n";
        $x=@openssl_x509_parse($pem);
        if(is_array($x)){
            $tpl->table_form_section("{certificate}");
            $tpl->table_form_field_text("{subject}",
                htmlspecialchars(strval($x["name"] ?? "")),ico_ssl);
            if(isset($x["validTo_time_t"])){
                // {expiration} n'existe pas dans le dictionnaire: il s'affichait
                // tel quel a l'ecran. {expire_date} est le jeton du depot.
                $tpl->table_form_field_text("{expire_date}",
                    $tpl->time_to_date(intval($x["validTo_time_t"]),true),ico_clock_desk);
            }
        }
    }

    $tpl->table_form_section("{saml_declare_on_idp}");
    $tpl->table_form_field_text("{saml_sp_entityid}",
        "<span style='font-family:monospace'>".htmlspecialchars(saml_sp::sp_entity_id())."</span>",ico_server);
    $tpl->table_form_field_text("{saml_acs_url}",
        "<span style='font-family:monospace'>".htmlspecialchars(saml_sp::acs_url())."</span>",ico_arrow_right);
    // Rappel utile en grappe: l'adresse ci-dessus est celle de CE noeud, chaque
    // noeud doit avoir la sienne declaree dans la meme application.
    $tpl->table_form_field_warning("{saml_cluster_hint}");

    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}
