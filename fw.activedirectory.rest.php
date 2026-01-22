<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_POST["ActiveDirectoryRestInterface"])){save();exit;}
if(isset($_POST["ActiveDirectoryRestShellEnable"])){save();exit;}
if(isset($_POST["ActiveDirectoryRestTestUser"])){save();exit;}
if(isset($_POST["ActiveDirectoryRestLetsEncryptIface"])){save();exit;}
if(isset($_GET["revoke-token"])){revoke_tokens();exit;}

if(isset($_GET["status"])){webapi_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_GET["debianagent-status"])){debianagent_status();exit;}
if(isset($_GET["debian-agent-params-js"])){debian_agent_params_js();exit;}
if(isset($_GET["debian-agent-params-popup"])){debian_agent_params_popup();exit;}
if(isset($_POST["ListenInterface"])){debian_agent_params_save();exit;}

if(isset($_GET["section-memcache-js"])){section_memcache_js();exit;}
if(isset($_GET["section-memcache-popup"])){section_memcache_popup();exit;}
if(isset($_POST["UseMemCacheClient"])){section_memcache_save();exit;}

if(isset($_GET["section-service-js"])){section_service_js();exit;}
if(isset($_GET["section-service-popup"])){section_service_popup();exit;}

if(isset($_GET["section-features-js"])){section_features_js();exit;}
if(isset($_GET["section-features-popup"])){section_features_popup();exit;}

if(isset($_GET["section-auth-js"])){section_auth_js();exit;}
if(isset($_GET["section-auth-popup"])){section_auth_popup();exit;}

if(isset($_GET["section-letsencrypt-js"])){section_letsencrypt_js();exit;}
if(isset($_GET["section-letsencrypt-popup"])){section_letsencrypt_popup();exit;}

if(isset($_GET["tiny"])){Tiny();exit;}

page();
function page(){
    //
    $page=CurrentPageName();
    $raccourci="ad-webapi";
    $tpl=new template_admin();

    $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");

    $html=$tpl->page_header("{SQUID_AD_RESTFULL} v$ARTICAREST_VERSION",
        "fad fa-monitor-heart-rate","{SQUID_AD_RESTFULL_EXPLAIN}",
        "$page?tabs=yes",$raccourci,"progress-webapi-restart",false,"table-loader-webapi");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}
function revoke_tokens():bool{
    $token=$_GET["revoke-token"];
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/delete-token/".urlencode($token));
    return admin_tracks("Revoke Debian agent token $token");
}

function debianagent_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $EnableDebianAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDebianAgent"));
    $APP_DEBIAN_NETWORK_AGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DEBIAN_NETWORK_AGENT_VERSION");
    if($EnableDebianAgent==0){
        $jsInstall = $tpl->framework_buildjs("/debianagent/install",
            "debian-agent.progress",
            "debian-agent.progress.log",
            "progress-webapi-restart");

        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{install}";
        $btn[0]["icon"] = ico_cd;
        $btn[0]["js"] = $jsInstall;

        $widget_tests = $tpl->widget_style1("gray-bg", ico_stop,  "{APP_DEBIAN_NETWORK_AGENT} v$APP_DEBIAN_NETWORK_AGENT_VERSION","{inactive2}", $btn);
        echo $tpl->_ENGINE_parse_body($widget_tests);
        return true;
    }

    $health=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/health"));

    if(property_exists($health,"Health")){
        $APP_DEBIAN_NETWORK_AGENT_VERSION=$health->Health->version;
    }

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/status");

    if(!function_exists("json_decode")){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",
            ico_bug,"json_decode no such function, please restart Web console","{error}"));
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body( $tpl->widget_style1("bg-red",
            ico_bug,json_last_error_msg(),"{error}"));
        return true;
    }

    if(!property_exists($json,"Info")){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",
            ico_bug,"Protocol error","{error}"));
        return true;
    }

    $jsUninstall = $tpl->framework_buildjs("/debianagent/uninstall",
        "debian-agent.progress",
        "debian-agent.progress.log",
        "progress-webapi-restart");

    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{uninstall}";
    $btn[0]["icon"] = ico_trash;
    $btn[0]["js"] = $jsUninstall;

    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $Key="APP_DEBIAN_NETWORK_AGENT";
    if(!isset( $bsini->_params[$Key])){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",
            ico_bug,"Data error","{error}",$btn));
        return true;
    }
    //  [service_name] => APP_DEBIAN_NETWORK_AGENT [master_version] => 0.0.0
    // [service_cmd] => /usr/sbin/artica-phpfpm-service -start-debian-agent
    // [pid_path] => /run/debian-agent.pid [watchdog_features] => 1
    // [family] => network [installed] => 1 [application_installed] => 1
    // [service_disabled] => 1 [running] => 1 [master_pid] => 3521711
    // [master_time] => 343 [processes_number] => 1 [master_memory] => 13176 [uptime] => 5 minutes [maxfd] => 65536 [curfd] => 10 )
    if(intval($bsini->_params[$Key]["running"])==0){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",ico_bug,
            "{APP_DEBIAN_NETWORK_AGENT} v$APP_DEBIAN_NETWORK_AGENT_VERSION","{stopped}",$btn));
        return true;
    }

    $mem=FormatBytes($bsini->_params[$Key]["master_memory"]);
    $jsRestart = $tpl->framework_buildjs("/debianagent/restart",
        "debian-agent.progress",
        "debian-agent.progress.log",
        "progress-webapi-restart");
    $btn[1]["margin"] = 0;
    $btn[1]["name"] = "{restart}";
    $btn[1]["icon"] = ico_refresh;
    $btn[1]["js"] = $jsRestart;

    $btn[2]["margin"] = 0;
    $btn[2]["name"] = "{parameters}";
    $btn[2]["icon"] = ico_params;
    $btn[2]["js"] = "Loadjs('$page?debian-agent-params-js=yes');";

    $MainStatus=$tpl->_ENGINE_parse_body($tpl->widget_style1("green-bg",ico_run,
        "{APP_DEBIAN_NETWORK_AGENT} v$APP_DEBIAN_NETWORK_AGENT_VERSION","{running}<br><small style='font-size:14px;color:white'>{memory}:$mem</small>",$btn));

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='33%'>$MainStatus</td>";
    $html[]="<td style='33%;padding-left:5px'>";
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/tokens"));
    $TokensCount=0;
    if(property_exists($data->Info,"tokens") && is_array($data->Info->tokens)){
        $TokensCount=count($data->Info->tokens);
    }
    if($TokensCount==0){
        $Data2=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/create-token"));
        if(!$Data2->Status){
            $html[]=$tpl->_ENGINE_parse_body($tpl->widget_style1("bg-red",ico_bug, $Data2->Status,"{error}",$btn));
            $html[]="</td>";
            $html[]="</table>";
            echo $tpl->_ENGINE_parse_body($html);
            return false;
        }
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/tokens"));
    }
    $Token=$data->Info->tokens[0]->token;
    $date = preg_replace('/\.(\d{6})\d+/', '.$1', $Tokens=$data->Info->tokens[0]->expires_at);
    $dt = new DateTime($date);
    $timestamp = $dt->getTimestamp();
    $zdatye=$tpl->time_to_date($timestamp,true);

    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{remove}";
    $btn[0]["icon"] = ico_trash;
    $btn[0]["js"] = "Loadjs('$page?revoke-token=$Token');";

    $html[]=$tpl->_ENGINE_parse_body($tpl->widget_style1("green-bg",ico_certificate,
        "{token}","$Token<br><small style='font-size:14px;color:white'>{expire} $zdatye</small>",$btn));
    $html[]="</td>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);


return false;
}
function config_file_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{APP_UNBOUND} >> {config_file}", "$page?config-file-popup=yes");

}
function section_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{parameters}","$page?section-service-popup=yes");
}

function section_memcache_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{use_memory_cache_service}","$page?section-memcache-popup=yes");
}
function debian_agent_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{parameters}","$page?debian-agent-params-popup=yes",650);
}
function section_memcache_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ENabled=intval(ValkeyClient::httpGetInfo("UseMemCacheClient"));
    echo $tpl->BigCircleCheckbox("UseMemCacheClient","{use_memory_cache_service}","{use_memory_cache_service_explain}",$ENabled,"BootstrapDialog1.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');");
    return true;
}
function section_memcache_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $UseMemCacheClient=$_POST=intval($_POST["UseMemCacheClient"]);
    $ENabled=intval(ValkeyClient::httpGetInfo("UseMemCacheClient"));
    writelogs("$ENabled === $UseMemCacheClient",__FUNCTION__,__FILE__,__LINE__);
    if($ENabled==$UseMemCacheClient){
        return false;
    }
    if($UseMemCacheClient==1){
        writelogs("Install Memcache service for Artica REST API service",__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/artmem/install");
        return admin_tracks("Memcache service installed for Artica REST API service");
    }
    writelogs("uninstall Memcache service for Artica REST API service",__FUNCTION__,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/artmem/uninstall");
    return admin_tracks("Memcache service uninstalled for Artica REST API service");
}

function section_features_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{features}","$page?section-features-popup=yes");
}
function section_auth_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{test_authentication}","$page?section-auth-popup=yes");
}
function section_letsencrypt_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{letsencrypt_http_service}","$page?section-letsencrypt-popup=yes");
}

function section_js_formNoRestart():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');";
}
function section_js_form():string{
    $page=CurrentPageName();
    $jsRestart=restart_array();
    return "BootstrapDialog1.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');$jsRestart";
}
function section_service_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestInterface   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestInterface"));
    $ActiveDirectoryRestPort        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPort"));
    $ActiveDirectoryRestSSL         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestSSL"));
    $ActiveDirectoryRestCert        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestCert"));
    $ActiveDirectoryRestDebug       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestDebug"));

    if($ActiveDirectoryRestInterface==null){$ActiveDirectoryRestInterface="lo";}
    if($ActiveDirectoryRestPort==0){$ActiveDirectoryRestPort=9503;}

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestDebug", "{debug}", $ActiveDirectoryRestDebug);
    $form[] = $tpl->field_interfaces("ActiveDirectoryRestInterface", "nodef:{listen_interfaces}", $ActiveDirectoryRestInterface);
    $form[] = $tpl->field_numeric("ActiveDirectoryRestPort", "{listen_port}", $ActiveDirectoryRestPort);
    $form[] = $tpl->field_checkbox("ActiveDirectoryRestSSL", "{ssl}", $ActiveDirectoryRestSSL,false);
    $form[] = $tpl->field_certificate("ActiveDirectoryRestCert","{certificate}",$ActiveDirectoryRestCert);

    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function debian_agent_params_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $params=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/params"),true);
    $Conf=$params["Params"];
    $form[]=$tpl->field_interfaces("ListenInterface","{listen_interface}",$Conf["listen_interface"]);
    $form[]=$tpl->field_numeric("ListenPort","{listen_port}",$Conf["listen_port"]);
    $zips="";
    if(isset($Conf["security"]["ip_whitelist"]) && count($Conf["security"]["ip_whitelist"])>0) {
        $zips=@implode(",",$Conf["security"]["ip_whitelist"]);
    }
    $form[]=$tpl->field_text("ip_whitelist","{trusted_networks}",$zips);
    $security="AsSystemAdministrator";
    $js="dialogInstance2.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", $js,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function debian_agent_params_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ListenInterface=$_POST["ListenInterface"];
    $ListenPort=intval($_POST["ListenPort"]);
    $ip_whitelist=urlencode($_POST["ip_whitelist"]);
    $uri="/debianagent/iface/$ListenInterface/$ListenPort";
    $GLOBALS["CLASS_SOCKETS"]->REST_API($uri);
    $uri="/debianagent/whitelists/$ip_whitelist";
    $GLOBALS["CLASS_SOCKETS"]->REST_API($uri);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/restart");
    return admin_tracks("Save Debian Agent listen to $ListenInterface:$ListenPort whitelist:{$_POST["ip_whitelist"]}");
}
function section_features_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestShellEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellEnable"));
    $ActiveDirectoryRestShellPass   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellPass"));
    $ActiveDirectoryRestSnapsEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestSnapsEnable"));

    $form[] = $tpl->field_text("ActiveDirectoryRestShellPass","{passphrase} X-Auth-Token",$ActiveDirectoryRestShellPass);
    $form[] = $tpl->field_checkbox("ActiveDirectoryRestShellEnable","{allow_execute_scripts}",$ActiveDirectoryRestShellEnable);
    $form[] = $tpl->field_checkbox("ActiveDirectoryRestSnapsEnable","{allow_snapshots}",$ActiveDirectoryRestSnapsEnable);


    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_letsencrypt_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestLetsEncrypt    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncrypt"));
    $ActiveDirectoryRestLetsEncryptIface    = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncryptIface"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestLetsEncrypt","{enable_feature}",$ActiveDirectoryRestLetsEncrypt);
    $form[]=$tpl->field_interfaces("ActiveDirectoryRestLetsEncryptIface","{interface}",$ActiveDirectoryRestLetsEncryptIface);

     $security="AsSystemAdministrator";
     $letsencrypt_http_service_explain2=$tpl->_ENGINE_parse_body("{letsencrypt_http_service_explain2}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),"{letsencrypt_http_service_explain}<br>$letsencrypt_http_service_explain2","{apply}", section_js_formNoRestart(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function section_auth_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestTestUser    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestUser"));
    $ActiveDirectoryRestUser        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestUser"));
    $ActiveDirectoryRestPass        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPass"));
    $ActiveDirectoryRestTestURL     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestURL"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestTestUser","{enable_feature}",$ActiveDirectoryRestTestUser,
        "ActiveDirectoryRestTestURL,ActiveDirectoryRestUser,ActiveDirectoryRestPass");
    $form[] = $tpl->field_text("ActiveDirectoryRestTestURL","{uri_test}",$ActiveDirectoryRestTestURL);
    $form[] = $tpl->field_text("ActiveDirectoryRestUser","{username}",$ActiveDirectoryRestUser);
    $form[] = $tpl->field_password2("ActiveDirectoryRestPass","{password}",$ActiveDirectoryRestPass);


    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{networks_restrictions}"]="fw.activedirectory.rest.restrictions.php";
    $array["{events}"]="fw.activedirectory.rest.events.php";
    echo $tpl->tabs_default($array);
    return true;
}

function restart_array():string{
    $page   = CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("watch:/articarest/rest",
        "active-directory-rest.restart","active-directory-rest.restart.log",
        "progress-webapi-restart","LoadAjaxSilent('adrest-status','$page?status=yes');Loadjs('$page?tiny=yes');");


    //REST_ARTWATCH
}

function webunix_status():string{
    $tpl    = new template_admin();
    $htopwebrestart=$tpl->framework_buildjs("/system/htopweb/restart","htopweb.restart.progress","htopweb.restart.progress.log","progress-webapi-restart");
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/webunix/status");

    if(!function_exists("json_decode")){
        return $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");

    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }

    if(!property_exists($json,"Info")){
        return  $tpl->widget_rouge("{error}","protocol error");

    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($bsini, "APP_SHELLINABOX",$htopwebrestart);
}


function artmem_status():string{
    $tpl    = new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/artmem/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs(
        "/artmem/restart","artmem.progress","artmem.progress.logs",
        "progress-webapi-restart");

    return $tpl->SERVICE_STATUS($ini, "APP_ARTMEM",$jsrestart);
}
function redis_status():string{
    $tpl    = new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/redis/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs(
        "/redis/restart","progress-webapi-restart","progress-webapi-restart.logs",
        "progress-webapi-restart");

    return $tpl->SERVICE_STATUS($ini, "APP_REDIS_SERVER",$jsrestart);
}

function pogocache_status():string{
    if(!class_exists("ValkeyClient")){
        return redis_status();
    }

    $UseMemCacheClient=intval(ValkeyClient::httpGetInfo("UseMemCacheClient"));
    if($UseMemCacheClient==1){
        return artmem_status();
    }
    $PogoCacheEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PogoCacheEnabled");
    if($PogoCacheEnabled==0){
        return redis_status();
    }

    $tpl    = new template_admin();
    $htopwebrestart=$tpl->framework_buildjs("/pogocache/restart","progress-webapi-restart","progress-webapi-restart.log","progress-webapi-restart");
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/pogocache/status");

    if(!function_exists("json_decode")){
        return $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");

    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }

    if(!property_exists($json,"Info")){
        return  $tpl->widget_rouge("{error}","protocol error");

    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($bsini, "APP_POGOCACHE",$htopwebrestart);
}

function webapi_status():bool{

    $tpl    = new template_admin();


    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/adrest/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }

    if(!property_exists($json,"info")){
        echo $tpl->widget_rouge("{error}","Protocol error");
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->info);
    $jsRestart=restart_array();
    $final[]=$tpl->SERVICE_STATUS($bsini, "SQUID_AD_RESTFULL",$jsRestart);
    $final[]=pogocache_status();
    $final[]=webunix_status();
    $page=CurrentPageName();

    $ARTICAREST_VERSION="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ping"),true);
    if($json["Status"]){
        $ARTICAREST_VERSION=$json["Version"];
    }

    if($ARTICAREST_VERSION=="") {
        $ARTICAREST_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
    }


    $final[]="<script>";
    $final[]="$('#active-directory-rest-version').html('$ARTICAREST_VERSION');";
    $final[]="LoadAjaxSilent('debianagent-status','$page?debianagent-status=yes');";
    $final[]="</script>";
    echo $tpl->_ENGINE_parse_body($final);
    return true;

}
function table():bool{
    $page=CurrentPageName();
    echo "<div style='margin-top:10px' id='progress-webapi-start'></div>
<script>LoadAjaxSilent('progress-webapi-start','$page?table1=yes')</script>";
    return true;

}

function table1(){

    $tpl                            = new template_admin();
    $page                           = CurrentPageName();

    $ActiveDirectoryRestInterface   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestInterface"));
    $ActiveDirectoryRestDebug       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestDebug"));
    $ActiveDirectoryRestPort        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPort"));
    $ActiveDirectoryRestSSL         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestSSL"));
    $ActiveDirectoryRestCert        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestCert"));

    $ActiveDirectoryRestTestUser    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestUser"));
    $ActiveDirectoryRestUser        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestUser"));
    $ActiveDirectoryRestPass        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPass"));
    $ActiveDirectoryRestTestURL     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestURL"));

    $ActiveDirectoryRestShellEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellEnable"));
    $ActiveDirectoryRestSnapsEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestSnapsEnable"));

    if($ActiveDirectoryRestTestURL==null){$ActiveDirectoryRestTestURL="https://www.clubic.com";}
    if($ActiveDirectoryRestPort==0){ $ActiveDirectoryRestPort=9503; }

    $ActiveDirectoryRestLetsEncrypt    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncrypt"));
    $ActiveDirectoryRestLetsEncryptIface    = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncryptIface"));

    $UseMemCacheClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseMemCacheClient"));

    $EnableDebianAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDebianAgent"));
    if($EnableDebianAgent==1) {
        $params = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/params"));
        $tpl->table_form_field_js("Loadjs('$page?debian-agent-params-js=yes');");
        $tpl->table_form_field_text("{APP_DEBIAN_NETWORK_AGENT}", "{$params->Params->listen_addr}:{$params->Params->listen_port}", ico_interface);
    }else{
        $tpl->table_form_field_bool("{APP_DEBIAN_NETWORK_AGENT}",0,ico_interface);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-service-js=yes')");
    if($ActiveDirectoryRestInterface==null){$ActiveDirectoryRestInterface="127.0.0.1";}
    $tpl->table_form_field_bool("{debug}",$ActiveDirectoryRestDebug,ico_bug);


    $tpl->table_form_field_js("Loadjs('$page?section-service-js=yes')");
    if($ActiveDirectoryRestInterface=="127.0.0.1"){
        $ActiveDirectoryRestSSL=0;
        $tpl->table_form_field_text("{listen_interfaces}","unix:articarest.sock",ico_interface);
    }else {
        $tpl->table_form_field_text("{listen_interfaces}", $ActiveDirectoryRestInterface . ":$ActiveDirectoryRestPort", ico_interface);
    }
    if($ActiveDirectoryRestSSL==0) {
        $tpl->table_form_field_bool("{ssl}", $ActiveDirectoryRestSSL, ico_ssl);
    }else{
        $tpl->table_form_field_text("{certificate}",$ActiveDirectoryRestCert,ico_ssl);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-letsencrypt-js=yes')");
    if($ActiveDirectoryRestLetsEncrypt==0){
        $tpl->table_form_field_bool("{letsencrypt_http_service}", 0, ico_certificate);
    }else{
        if(strlen($ActiveDirectoryRestLetsEncryptIface)<3){
            $ActiveDirectoryRestLetsEncryptIface="0.0.0.0";
        }
        $tpl->table_form_field_text("{letsencrypt_http_service}","{listen} $ActiveDirectoryRestLetsEncryptIface:80",ico_ssl);
    }


    $tpl->table_form_section("{features}");

    $tpl->table_form_field_js("Loadjs('$page?section-memcache-js=yes')");
    $tpl->table_form_field_bool("{use_memory_cache_service}",$UseMemCacheClient,ico_memory);

    $tpl->table_form_field_js("Loadjs('$page?section-features-js=yes')");

    $ActiveDirectoryRestShellPass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellPass"));
    if(strlen($ActiveDirectoryRestShellPass)>0){
        $tpl->table_form_field_bool("{API_KEY}",1,ico_lock);

    }else{
        $tpl->table_form_field_bool("{API_KEY}",0,ico_lock);
    }
    $tpl->table_form_field_bool("{allow_execute_scripts}",$ActiveDirectoryRestShellEnable,ico_script);
    $tpl->table_form_field_bool("{allow_snapshots}",$ActiveDirectoryRestSnapsEnable,ico_archive);


    if($ActiveDirectoryRestTestUser==0){
        $tpl->table_form_field_bool("{test_authentication}",$ActiveDirectoryRestShellEnable,ico_watchdog);
    }else{
        $tpl->table_form_field_text("{test_authentication}","$ActiveDirectoryRestUser@$ActiveDirectoryRestTestURL",ico_watchdog);
    }

    $tpl->table_form_field_js("Loadjs('$page?section-auth-js=yes')");

    $myform=$tpl->table_form_compile();

    $Interval=$tpl->RefreshInterval_js("adrest-status",$page,"status=yes",3);


    $html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='adrest-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>
		<div id='debianagent-status' style='padding-left:17px'></div>
		$myform</td>
	</tr>
	</table>
	<script>
	
	$Interval;Loadjs('$page?tiny=yes');</script>	
	";


    echo $tpl->_ENGINE_parse_body($html);
}

function Tiny():bool{
    $tpl                            = new template_admin();
    $ARTICAREST_VERSION="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ping"),true);
    if($json["Status"]){
        $ARTICAREST_VERSION=$json["Version"];
    }

    if($ARTICAREST_VERSION=="") {
        $ARTICAREST_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
    }





    $TINY_ARRAY["TITLE"]="{SQUID_AD_RESTFULL} v<span id='active-directory-rest-version'>$ARTICAREST_VERSION</span>";
    $TINY_ARRAY["ICO"]="fad fa-monitor-heart-rate";
    $TINY_ARRAY["EXPL"]="{SQUID_AD_RESTFULL_EXPLAIN}";
    $TINY_ARRAY["URL"]="ad-webapi";
    $jsrestart=restart_array();
    $topbuttons[] = array($jsrestart, ico_refresh, "{restart_service}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
    return true;
}




function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($UnboundEnabled==0){$_POST["EnableUnboundBlackLists"]=0;}
    $EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));

    if(isset($_POST["InComingInterfaces"])){
        $array=explode(",",$_POST["InComingInterfaces"]);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(@implode("\n", $array), "PowerDNSListenAddr");
        unset($_POST["InComingInterfaces"]);
    }

    $tpl->SAVE_POSTs();

    if($_POST["EnableUnboundBlackLists"]<>$EnableUnboundBlackLists){

        if($_POST["EnableUnboundBlackLists"]==1){
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?blacklists-enable=yes");
        }else{
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("unbound.php?blacklists-disable=yes");
        }

    }


    if($_POST["EnableUnBoundSNMPD"]<>$EnableUnBoundSNMPD){$GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");}





}