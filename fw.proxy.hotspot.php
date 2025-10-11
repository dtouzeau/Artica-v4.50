<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["HotSpotWIFI4EU_ENABLE"])){Save();exit;}
if(isset($_POST["HotSpotAutoLogin"])){Save();exit;}
if(isset($_POST["HotSpotTermsConditions"])){Save();exit;}
if(isset($_POST["HotSpotDebug"])){Save();exit;}
if(isset($_POST["HotSpotAuthenticateEach"])){Save();exit;}
if(isset($_POST["HotSpotTemplateID"])){Save();exit;}
if(isset($_POST["HotSpotVoucherRemovePass"])){Save();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hotspot-top-status"])){top_satus();exit;}
if(isset($_GET["hotspot-tokey-enabled"])){set_info_restart();exit;}
if(isset($_GET["hotspot-ad-repair"])){reconfigure();exit;}
if(isset($_GET["last-config"])){last_config();exit;}

if(isset($_GET["form-timeout-popup"])){form_timeout_popup();exit;}
if(isset($_GET["form-timeouts-js"])){form_timeout_js();exit;}

if(isset($_GET["form-service-js"])){form_service_js();exit;}
if(isset($_GET["form-service-popup"])){form_service_popup();exit;}

if(isset($_GET["form-pages-js"])){form_pages_js();exit;}
if(isset($_GET["form-pages-popup"])){form_pages_popup();exit;}

if(isset($_GET["form-auth-js"])){form_auth_js();exit;}
if(isset($_GET["form-auth-popup"])){form_auth_popup();exit;}

if(isset($_GET["form-register-js"])){form_register_js();exit;}
if(isset($_GET["form-register-popup"])){form_register_popup();exit;}

if(isset($_GET["form-wifi4eu-js"])){form_wifi4eu_js();exit;}
if(isset($_GET["form-wifi4eu-popup"])){form_wifi4eu_popup();exit;}





page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{web_portal_authentication}",
        "fas fa-hotel","{HotSpot_text}","$page?tabs=yes",
        "hotspot-config","progress-hotspot-restart",false,"table-hotspot");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{hotspot_auth}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function reconfigure(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");

    $jsrestart= $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log","progress-hotspot-restart",
        "LoadAjaxSilent('hotspot-top-status','$page?hotspot-top-status=yes');",
        "LoadAjaxSilent('hotspot-top-status','$page?hotspot-top-status=yes');");


    echo $jsrestart;

}
function set_info_restart(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Token=$_GET["hotspot-tokey-enabled"];
    $value=intval($_GET["value"]);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($Token,$value);

    header("content-type: application/x-javascript");

    $jsrestart= $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log",
        "progress-hotspot-restart",
        "LoadAjaxSilent('hotspot-top-status','$page?hotspot-top-status=yes');",
        "LoadAjaxSilent('hotspot-top-status','$page?hotspot-top-status=yes');");



    echo $jsrestart;
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{general_settings}"]="$page?table-start=yes";
    $array["{networks}"]="fw.proxy.hotspot.network.php";
    $array["{active_directory_groups}"]="fw.proxy.hotspot.adgroups.php";

    $array["{skins}"]="fw.proxy.hotspot.textes.php";
    $array["{skins} WIFI4EU"]="fw.proxy.hotspot.wifi4eu.php";
    echo $tpl->tabs_default($array);
}
function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='hotspot-main-status'></div>
    <script>LoadAjaxSilent('hotspot-main-status','$page?table=yes');</script>";
    return true;
}

function form_register_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HotSpotAutoLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoLogin"));
    $HotSpotAutoSMTPFrom=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoSMTPFrom"));
    $HotSpotAutoNoSMTP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoNoSMTP"));

    $HotSpotAutoLoginMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoLoginMaxTime"));
    if($HotSpotAutoLoginMaxTime==0){$HotSpotAutoLoginMaxTime=5;}
    if($HotSpotAutoSMTPFrom==null){$HotSpotAutoSMTPFrom="root@localhost.local";}


    $UfdbguardSMTPNotifs=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbguardSMTPNotifs"));
    if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}

    $LockSMTP=true;
    if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==1){
        $LockSMTP=false;
    }

    if($HotSpotAutoNoSMTP==0) {
        if ($LockSMTP) {
            echo $tpl->div_warning("{hotspot_notification_service_warning}");
        }
    }


    $form[]=$tpl->field_checkbox("HotSpotAutoLogin","{enable_hotspot_autologin}",$HotSpotAutoLogin,"HotSpotAutoSMTPFrom,HotSpotAutoLoginMaxTime","{enable_hotspot_autologin_explain}");
    if(!$LockSMTP) {
        $form[] = $tpl->field_numeric("HotSpotAutoLoginMaxTime", "{max_time_register}", $HotSpotAutoLoginMaxTime);
        $form[] = $tpl->field_email("HotSpotAutoSMTPFrom", "{smtp_sender}", $HotSpotAutoSMTPFrom);
    }

    $form[]=$tpl->field_checkbox("HotSpotAutoNoSMTP","{simply_require_email}",$HotSpotAutoNoSMTP);
    $HotSpotAutoCustomForm=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoCustomForm"));
    $form[]=$tpl->field_checkbox("HotSpotAutoCustomForm","{use_custom_form}",$HotSpotAutoCustomForm);


    $jsrestart[]="LoadAjaxSilent('hotspot-main-status','$page?table=yes');";
    $jsrestart[]="dialogInstance2.close()";
    $jsrestart[]=form_jsrestart();
    echo $tpl->form_outside(null, $form,null,"{apply}",@implode("\n",$jsrestart),"AsSquidAdministrator");
    return true;

}

function form_wifi4eu_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $HotSpotWIFI4EU_ENABLE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_ENABLE"));
    $HotSpotWIFI4EU_UUID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_UUID"));
    $HotSpotWIFI4EU_LANG=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_LANG"));
    $HotSpotWIFI4EU_DEBUG=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_DEBUG"));
    $WIFI4EU_LANG['bg']="Bulgarian";
    $WIFI4EU_LANG['hr']="Croatian";
    $WIFI4EU_LANG['cs']="Czech";
    $WIFI4EU_LANG['da']="Danish";
    $WIFI4EU_LANG['nl']="Dutch";
    $WIFI4EU_LANG['en']="English";
    $WIFI4EU_LANG['et']="Estonian";
    $WIFI4EU_LANG['fi']="Finnish";
    $WIFI4EU_LANG['fr']="French";
    $WIFI4EU_LANG['de']="German";
    $WIFI4EU_LANG['el']="Greek";
    $WIFI4EU_LANG['hu']="Hungarian";
    $WIFI4EU_LANG['ga']="Irish";
    $WIFI4EU_LANG['it']="Italian";
    $WIFI4EU_LANG['lv']="Latvian";
    $WIFI4EU_LANG['lt']="Lithuanian";
    $WIFI4EU_LANG['mt']="Maltese";
    $WIFI4EU_LANG['pl']="Polish";
    $WIFI4EU_LANG['pt']="Portuguese";
    $WIFI4EU_LANG['ro']="Romanian";
    $WIFI4EU_LANG['sk']="Slovak";
    $WIFI4EU_LANG['sl']="Slovenian";
    $WIFI4EU_LANG['es']="Spanish";
    $WIFI4EU_LANG['sv']="Swedish";

    $form[]=$tpl->field_section("{APP_WIFI4EU}","{APP_WIFI4EU_DESC}");
    $form[]=$tpl->field_checkbox("HotSpotWIFI4EU_ENABLE","{APP_WIFI4EU_ENABLE}",$HotSpotWIFI4EU_ENABLE,"HotSpotWIFI4EU_UUID,HotSpotWIFI4EU_LANG,HotSpotWIFI4EU_DEBUG");
    $form[]=$tpl->field_text("HotSpotWIFI4EU_UUID", "{APP_WIFI4EU_UUID}", $HotSpotWIFI4EU_UUID);
    $form[]=$tpl->field_array_hash($WIFI4EU_LANG,"HotSpotWIFI4EU_LANG","{APP_WIFI4EU_LANG}",$HotSpotWIFI4EU_LANG,false);
    $form[]=$tpl->field_checkbox("HotSpotWIFI4EU_DEBUG","{APP_WIFI4EU_DEBUG}",$HotSpotWIFI4EU_DEBUG,false);

    $jsrestart[]="LoadAjaxSilent('hotspot-main-status','$page?table=yes');";
    $jsrestart[]="dialogInstance2.close()";
    $jsrestart[]=form_jsrestart();

    $last_config="{you_need_to_compile}";
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }

    echo $tpl->form_outside("{last_config}:&nbsp;<span id='last-config'>$last_config</span>", $form,null,"{apply}",@implode("\n",$jsrestart),"AsSquidAdministrator");
    return true;


}

function form_auth_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $Timez2[1]="1 {minute} ( for testing )";
    $Timez2[2]="2 {minutes}";
    $Timez2[5]="5 {minutes}";
    $Timez2[10]="10 {minutes}";
    $Timez2[15]="15 {minutes}";
    $Timez2[30]="30 {minutes}";
    $Timez2[60]="1 {hour}";
    $Timez2[120]="2 {hours}";
    $explain="https://wiki.articatech.com/en/proxy-service/hotspot";



    $HotSpotVoucherRemovePass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotVoucherRemovePass"));

    $form[] = $tpl->field_checkbox("HotSpotVoucherRemovePass", "{vouchers_rooms}: {remove_password_field}", $HotSpotVoucherRemovePass, false);


    $jsrestart[]="LoadAjaxSilent('hotspot-main-status','$page?table=yes');";
    $jsrestart[]="dialogInstance2.close()";
    $jsrestart[]=form_jsrestart();

    $last_config="{you_need_to_compile}";
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }

    echo $tpl->form_outside("{last_config}:&nbsp;<span id='last-config'>$last_config</span>", $form,null,"{apply}",@implode("\n",$jsrestart),"AsSquidAdministrator");
    return true;
}


function form_timeout_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{timeouts} {sessions}","$page?form-timeout-popup=yes");
    return true;
}
function form_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{service}","$page?form-service-popup=yes");
    return true;
}
function form_pages_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{service}","$page?form-pages-popup=yes");
    return true;
}
function form_auth_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{authentication}","$page?form-auth-popup=yes");
    return true;
}
function form_register_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }

    $tpl->js_dialog2("{self_register} - {last_config}: $last_config","$page?form-register-popup=yes");
    return true;
}
function form_wifi4eu_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{APP_WIFI4EU}","$page?form-wifi4eu-popup=yes");
    return true;
}

function form_pages_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HotSpotTermsConditions=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotTermsConditions"));
    $HotSpotLandingPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLandingPage"));
    $HotSpotLostLandingPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLostLandingPage"));
    $HotSpotRedirectUI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRedirectUI"));
    $form[]=$tpl->field_text("HotSpotRedirectUI", "{HotSpotRedirectUI}", $HotSpotRedirectUI);
    $form[]=$tpl->field_text("HotSpotLostLandingPage", "{lost_landing_page}", $HotSpotLostLandingPage,false,"{lost_landing_page_explain}");
    $form[]=$tpl->field_text("HotSpotLandingPage", "{landing_page}", $HotSpotLandingPage,false,"{landing_page_hotspot_explain}");
    $form[] = $tpl->field_checkbox("HotSpotTermsConditions", "{Terms_Conditions}", $HotSpotTermsConditions, false);


    $jsrestart[]="LoadAjaxSilent('hotspot-main-status','$page?table=yes');";
    $jsrestart[]="dialogInstance2.close()";
    $jsrestart[]=form_jsrestart();

    $last_config="{you_need_to_compile}";
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }

    echo $tpl->form_outside("{last_config}:&nbsp;<span id='last-config'>$last_config</span>", $form,null,"{apply}",@implode("\n",$jsrestart),"AsSquidAdministrator");
    return true;
}

function form_jsrestart():string{
    $tpl=new template_admin();
    $page=CurrentPageName();

    return $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log",
        "progress-hotspot-restart",
        "LoadAjaxSilent('hotspot-top-status','$page?hotspot-top-status=yes');",
        "");
}
function form_service_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HotSpotListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $HotSpotListenEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenEnableSSL"));
    $HotSpotListenSSLPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenSSLPort"));
    $HotSpotListenSSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenSSLCertificate"));
    if($HotSpotListenSSLPort==0){$HotSpotListenSSLPort=8026;}
    if($HotSpotListenPort==0){$HotSpotListenPort=8025;}
    $HotSpotBindInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBindInterface"));
    $HotSpotDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotDebug"));

    if($EnableNginx==1) {
        $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_nginx_explain}");

    }else{

        $form[]=  $tpl->field_interfaces("HotSpotBindInterface", "{interface}", $HotSpotBindInterface);
        $form[] = $tpl->field_numeric("HotSpotListenPort","{http_port}",$HotSpotListenPort);
        $form[] = $tpl->field_checkbox("HotSpotListenEnableSSL","{UseSSL}",$HotSpotListenEnableSSL,"HotSpotListenSSLPort,HotSpotListenSSLCertificate");
        $form[] = $tpl->field_numeric("HotSpotListenSSLPort","{ssl_port}",$HotSpotListenSSLPort);
        $form[] = $tpl->field_certificate("HotSpotListenSSLCertificate","{ssl_certificate}",$HotSpotListenSSLCertificate);
    }
    $form[]=$tpl->field_hidden("HotSpotTemplateID",4);

    $zHotSpotHardwareIdent[0]="{ipaddr} {or} {ComputerMacAddress}";
    $zHotSpotHardwareIdent[1]="{ipaddr}";
    $zHotSpotHardwareIdent[2]="{ComputerMacAddress}";
    $HotSpotHardwareIdent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotHardwareIdent"));

    $form[]=$tpl->field_array_hash($zHotSpotHardwareIdent, "zHotSpotHardwareIdent", "{authentication_method}", $HotSpotHardwareIdent);
    $form[]=$tpl->field_checkbox("HotSpotDebug", "{debug}", $HotSpotDebug);


    $jsrestart[]="LoadAjaxSilent('hotspot-main-status','$page?table=yes');";
    $jsrestart[]="dialogInstance2.close()";
    $jsrestart[]=form_jsrestart();

    $last_config="{you_need_to_compile}";
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }

    echo $tpl->form_outside("{last_config}:&nbsp;<span id='last-config'>$last_config</span>", $form,null,"{apply}",@implode("\n",$jsrestart),"AsSquidAdministrator");
    return true;

}
function form_timeout_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $last_config="{you_need_to_compile}";
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }

    $Timez[0]="{unlimited}";
    $Timez[30]="30 {minutes}";
    $Timez[60]="1 {hour}";
    $Timez[120]="2 {hours}";
    $Timez[180]="3 {hours}";
    $Timez[360]="6 {hours}";
    $Timez[720]="12 {hours}";
    $Timez[1440]="1 {day}";
    $Timez[2880]="2 {days}";
    $Timez[10080]="1 {week}";
    $Timez[20160]="2 {weeks}";
    $Timez[40320]="1 {month}";

    $HotSpotAuthenticateEach=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthenticateEach"));
    $HotSpotDisableAccountTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotDisableAccountTime"));
    $HotSpotRemoveAccountTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRemoveAccountTime"));

    $form[]=$tpl->field_array_hash($Timez, "HotSpotAuthenticateEach", "{re_authenticate_each}", $HotSpotAuthenticateEach);
    $form[]=$tpl->field_array_hash($Timez, "HotSpotDisableAccountTime", "{disable_account_in}", $HotSpotDisableAccountTime);
    $form[]=$tpl->field_array_hash($Timez, "HotSpotRemoveAccountTime", "{remove_account_in}", $HotSpotRemoveAccountTime);

    $jsrestart[]="LoadAjaxSilent('hotspot-main-status','$page?table=yes');";
    $jsrestart[]="dialogInstance2.close()";
    $jsrestart[]=form_jsrestart();



    echo $tpl->form_outside("{last_config}:&nbsp;<span id='last-config'>$last_config</span>", $form,null,"{apply}",@implode("\n",$jsrestart),"AsSquidAdministrator");
    return true;
}

function table():bool{
	$tpl=new template_admin();
    $page=CurrentPageName();
	$HotSpotRedirectUI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRedirectUI"));
	$HotSpotAutoLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoLogin"));
	$HotSpotAutoSMTPFrom=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoSMTPFrom"));
	$HotSpotAuthenticateEach=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthenticateEach"));
	$HotSpotDisableAccountTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotDisableAccountTime"));
	$HotSpotRemoveAccountTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRemoveAccountTime"));
	$HotSpotBindInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBindInterface"));
	$HotSpotAutoLoginMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoLoginMaxTime"));
	$HotSpotDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotDebug"));
	$HotSpotLandingPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLandingPage"));
	$HotSpotLostLandingPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLostLandingPage"));
	$HotSpotHardwareIdent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotHardwareIdent"));

	$zHotSpotHardwareIdent[0]="{ipaddr} {or} {ComputerMacAddress}";
    $zHotSpotHardwareIdent[1]="{ipaddr}";
    $zHotSpotHardwareIdent[2]="{ComputerMacAddress}";

    //WIFI4UE
    $HotSpotWIFI4EU_ENABLE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_ENABLE"));
    $HotSpotWIFI4EU_UUID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_UUID"));
    $HotSpotWIFI4EU_LANG=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_LANG"));
    $HotSpotWIFI4EU_DEBUG=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_DEBUG"));
    $WIFI4EU_LANG['bg']="Bulgarian";
    $WIFI4EU_LANG['hr']="Croatian";
    $WIFI4EU_LANG['cs']="Czech";
    $WIFI4EU_LANG['da']="Danish";
    $WIFI4EU_LANG['nl']="Dutch";
    $WIFI4EU_LANG['en']="English";
    $WIFI4EU_LANG['et']="Estonian";
    $WIFI4EU_LANG['fi']="Finnish";
    $WIFI4EU_LANG['fr']="French";
    $WIFI4EU_LANG['de']="German";
    $WIFI4EU_LANG['el']="Greek";
    $WIFI4EU_LANG['hu']="Hungarian";
    $WIFI4EU_LANG['ga']="Irish";
    $WIFI4EU_LANG['it']="Italian";
    $WIFI4EU_LANG['lv']="Latvian";
    $WIFI4EU_LANG['lt']="Lithuanian";
    $WIFI4EU_LANG['mt']="Maltese";
    $WIFI4EU_LANG['pl']="Polish";
    $WIFI4EU_LANG['pt']="Portuguese";
    $WIFI4EU_LANG['ro']="Romanian";
    $WIFI4EU_LANG['sk']="Slovak";
    $WIFI4EU_LANG['sl']="Slovenian";
    $WIFI4EU_LANG['es']="Spanish";
    $WIFI4EU_LANG['sv']="Swedish";


	$Timez[0]="{unlimited}";
	$Timez[30]="30 {minutes}";
	$Timez[60]="1 {hour}";
	$Timez[120]="2 {hours}";
	$Timez[180]="3 {hours}";
	$Timez[360]="6 {hours}";
	$Timez[720]="12 {hours}";
	$Timez[1440]="1 {day}";
	$Timez[2880]="2 {days}";
	$Timez[10080]="1 {week}";
	$Timez[20160]="2 {weeks}";
	$Timez[40320]="1 {month}";
	
	$Timez2[1]="1 {minute} ( for testing )";
	$Timez2[2]="2 {minutes}";
	$Timez2[5]="5 {minutes}";
	$Timez2[10]="10 {minutes}";
	$Timez2[15]="15 {minutes}";
	$Timez2[30]="30 {minutes}";
	$Timez2[60]="1 {hour}";
	$Timez2[120]="2 {hours}";
	$explain="https://wiki.articatech.com/en/proxy-service/hotspot";

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
	if($HotSpotAutoLoginMaxTime==0){$HotSpotAutoLoginMaxTime=5;}

	if($HotSpotAutoSMTPFrom==null){$HotSpotAutoSMTPFrom="root@localhost.local";}
    $HotSpotListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));
    $HotSpotListenEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenEnableSSL"));
    $HotSpotListenSSLPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenSSLPort"));
    $HotSpotTermsConditions=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotTermsConditions"));

    $HotSpotListenSSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenSSLCertificate"));
    if($HotSpotListenSSLPort==0){$HotSpotListenSSLPort=8026;}
    if($HotSpotListenPort==0){$HotSpotListenPort=8025;}


    $tpl->table_form_section("{service}");
    $tpl->table_form_field_js("Loadjs('$page?form-service-js=yes')");
    if($EnableNginx==1) {
        $tpl->table_form_field_text("{listen_interface}","{UfdbUseInternalService_nginx_explain}",ico_nic);


    }else{
        if($HotSpotBindInterface==null){$HotSpotBindInterface="{all}";}
        if($HotSpotListenSSLCertificate==null){$HotSpotListenSSLCertificate="{all}";}
        $tpl->table_form_field_text("{listen_interface}","$HotSpotBindInterface:$HotSpotListenPort",ico_nic);
        if($HotSpotListenEnableSSL==0){
            $tpl->table_form_field_bool("{UseSSL}",$HotSpotListenEnableSSL,ico_ssl);
        }else{
            $tpl->table_form_field_text("{UseSSL}","$HotSpotBindInterface:$HotSpotListenSSLPort <small style='text-transform:none'>($HotSpotListenSSLCertificate)</small>",ico_ssl);
        }
    }
    $tpl->table_form_field_text("{authentication_method}",$zHotSpotHardwareIdent[$HotSpotHardwareIdent],ico_computer_down);
    $tpl->table_form_field_bool("{debug}",$HotSpotDebug,ico_bug);

    $tpl->table_form_section("{pages}/{Terms_Conditions}");
    $tpl->table_form_field_js("Loadjs('$page?form-pages-js=yes')");
    $tpl->table_form_field_text("{HotSpotRedirectUI}","<small style='text-transform: none'>$HotSpotRedirectUI</small>",ico_link);
    if($HotSpotLostLandingPage<>null) {
        $tpl->table_form_field_text("{lost_landing_page}","<small style='text-transform: none'>$HotSpotLostLandingPage</small>", ico_link);
    }
    if($HotSpotLandingPage<>null) {
        $tpl->table_form_field_text("{landing_page}", "<small style='text-transform: none'>$HotSpotLandingPage</small>", ico_link);
    }
    $tpl->table_form_field_bool("{Terms_Conditions}",$HotSpotTermsConditions,ico_params);



    $HotSpotVoucherRemovePass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotVoucherRemovePass"));
    $HotSpotVoucherEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotVoucherEnable"));



    $tpl->table_form_section("{authentication}");
    $tpl->table_form_field_js("Loadjs('$page?form-timeouts-js=yes')");
    $tpl->table_form_field_text("{sessions}","<small>{re_authenticate_each} <strong>$Timez[$HotSpotAuthenticateEach]</strong>, ".
        " {disable_account_in} <strong>$Timez[$HotSpotDisableAccountTime] </strong>, ".
        " {remove_account_in} <strong>$Timez[$HotSpotRemoveAccountTime]</strong></small>",ico_timeout);

    $HotSpotAuthentVoucher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentVoucher"));


    if($HotSpotAuthentVoucher==1){
        $tpl->table_form_field_js("Loadjs('$page?form-auth-js=yes')");
        if($HotSpotVoucherRemovePass==1) {
            $tpl->table_form_field_text("{vouchers_rooms}", "{remove_password_field}", ico_user_lock);
        }else{
            $tpl->table_form_field_bool("{vouchers_rooms}", 1, ico_user_lock);
        }
    }


    $UfdbguardSMTPNotifs=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbguardSMTPNotifs")));
    if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}

    $LockSMTP=true;
    if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==1){
        $LockSMTP=false;
    }

    $tpl->table_form_field_js("Loadjs('$page?form-register-js=yes')");
    if($HotSpotAutoLogin==0){
        $tpl->table_form_field_bool("{self_register}", $HotSpotAutoLogin, ico_email_check);
        }else{
            $HotSpotAutoNoSMTP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoNoSMTP"));
            if($HotSpotAutoNoSMTP==1){$LockSMTP=true;}
            if(!$LockSMTP){
                $HotSpotAutoLoginText="$Timez2[$HotSpotAutoLoginMaxTime] {smtp_sender} $HotSpotAutoSMTPFrom";
            }else {
                $HotSpotAutoLoginText ="<small>{simply_require_email}</small>";
            }

            $tpl->table_form_field_text("{self_register}",$HotSpotAutoLoginText,ico_email_check);
            $HotSpotAutoCustomForm=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoCustomForm"));
            $tpl->table_form_field_bool("{use_custom_form}",$HotSpotAutoCustomForm,ico_form);

        }

      $tpl->table_form_field_js("Loadjs('$page?form-wifi4eu-js=yes')");
    if($HotSpotWIFI4EU_ENABLE==0){
        $tpl->table_form_field_bool("{APP_WIFI4EU}", $HotSpotWIFI4EU_ENABLE, ico_user_lock);

    }else{
        $tpl->table_form_field_text("{APP_WIFI4EU}","{APP_WIFI4EU_UUID} $HotSpotWIFI4EU_UUID {APP_WIFI4EU_LANG} $WIFI4EU_LANG[$HotSpotWIFI4EU_LANG]",ico_user_lock);
    }

    $jsrestart = $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log",
        "progress-hotspot-restart",
        "LoadAjaxSilent('hotspot-top-status','$page?hotspot-top-status=yes');",
        "");


    $Host=$_SERVER["SERVER_ADDR"];
    $REMOTE_ADDR=$_SERVER["REMOTE_ADDR"];
    if(strpos($Host,":")>0){
        $tb=explode(":",$Host);
        $Host=$tb[0];
    }



    $uri="s_PopUpFull('http://$Host:$HotSpotListenPort/hotspot.php?info=eyJNQUMiOiIiLCJTUkMiOiIxOTIuMTY4LjEuMjQ4IiwiRU1BSUwiOiIiLCJLRVkiOiIxOTIuMTY4LjEuMjQ4In0=&ip=$REMOTE_ADDR&user=&url=http://www.google.com/',1024,768,'Monitor');";
    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"$uri;\"><i class='fa-solid fa-file-magnifying-glass'></i> {view2}:{portal_page} </label>";
    $bts[] = "<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\"><i class='fa-solid fa-retweet'></i> {reconfigure} </label>";


    $bts[]="</div>";

$HOTSPOTWEB_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");
if($HOTSPOTWEB_VERSION==null){$HOTSPOTWEB_VERSION="4.x";}
    $TINY_ARRAY["TITLE"]="{web_portal_authentication} v$HOTSPOTWEB_VERSION: {parameters}";
    $TINY_ARRAY["ICO"]="fas fa-hotel";
    $TINY_ARRAY["EXPL"]="{HotSpot_text}";
    $TINY_ARRAY["URL"]="hotspot-config";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $last_config="{you_need_to_compile}";
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }

    echo "<div style='margin-top:10px' id='hotspot-top-status'>&nbsp;</div>";
	echo $tpl->table_form_compile();
    echo "<script>";
    echo "LoadAjaxSilent('hotspot-top-status','$page?hotspot-top-status=yes');\n";
    echo "$jstiny</script>";

    return true;
}
function last_config():bool{
    $last_config="{you_need_to_compile}";
    $lasttime=intval(@file_get_contents("/home/artica/web_templates/hotspot.SavedTime"));
    if($lasttime>0){
        $last_config=distanceOfTimeInWords($lasttime,time());
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($last_config);
    return true;
}

function HotSpotRedirectUICheck($url):string{
    if(!preg_match("#(http|https):\/#",$url)){
        $url="http://$url";
    }
    $parse=parse_url($url);
    if(!isset($parse["port"])){$parse["port"]=0;}
    if(!isset($parse["host"])){$parse["host"]=php_uname("n");}
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $port=$parse["port"];
    $host=$parse["host"];
    $zurl[]="http://";
    $zurl[]=$host;

    if($EnableNginx==1){
        if($port>0){
            $zurl[]=":$port";
        }
        return @implode($zurl);
    }
    $HotSpotListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));

    //if($HotSpotListenSSLPort==0){$HotSpotListenSSLPort=8026;}
    if($HotSpotListenPort==0){$HotSpotListenPort=8025;}


    if(intval($HotSpotListenPort)<>80) {
        $zurl[] = ":" . $HotSpotListenPort;
    }
    return @implode($zurl);
}

function Save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $EnableSquidMicroHotSpot=0;
    if(isset($_POST["EnableSquidMicroHotSpot"])) {
        $EnableSquidMicroHotSpot = intval($_POST["EnableSquidMicroHotSpot"]);
    }

    if(isset($_POST["HotSpotWIFI4EU_ENABLE"])) {
        if ($_POST["HotSpotWIFI4EU_ENABLE"] == 1) {
            $_POST["HotSpotEntrepriseTemplate"] = 0;
            $_POST["HotSpotAutoLogin"] = 0;
            $_POST["HotSpotAuthentAD"] = 0;
            $_POST["HotSpotAuthentLocalLDAP"] = 0;
            $_POST["HotSpotAuthentVoucher"] = 0;
        }
    }

    if(isset($_POST["HotSpotRedirectUI2"])) {
        if ($_POST["HotSpotRedirectUI2"] <> null) {
            $_POST["HotSpotRedirectUI"] = $_POST["HotSpotRedirectUI2"];
            unset($_POST["HotSpotRedirectUI2"]);
        }
    }
    if(isset($_POST["HotSpotRedirectUI"])) {
        if ($EnableSquidMicroHotSpot == 1) {
            if ($_POST["HotSpotRedirectUI"] == null) {
                echo $tpl->post_error("{you_need_uri_redirection}");
                return false;

            }
        }
        $_POST["HotSpotRedirectUI"] = HotSpotRedirectUICheck($_POST["HotSpotRedirectUI"]);
    }



	$tpl->SAVE_POSTs();
	return true;
}
function top_satus():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tds="width:33%;padding-left:5px;vertical-align:top";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='$tds'>";
    $html[]=top_status_ad();
    $html[]="</td>";
    $html[]="<td style='$tds'>";
    $html[]=top_status_ldap();
    $html[]="</td>";

    $html[]="<td style='$tds'>";
    $html[]=top_status_voucher();
    $html[]="</td>";


    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>LoadAjaxSilent('last-config','$page?last-config=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function top_status_voucher():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $HotSpotAuthentVoucher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentVoucher"));

    if($HotSpotAuthentVoucher==0) {

        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        if($EnableOpenLDAP==1){
            $top_status_autoform=top_status_autoform();
            if(strlen($top_status_autoform)>3){
                return $top_status_autoform;
            }
        }
        $button["name"] = "{install_feature}";
        $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAuthentVoucher&value=1');";
        return $tpl->widget_h("gray","fa-solid fa-bed","{disabled}","{enable_voucher_method}",$button);
    }

    $button["name"] = "{uninstall}";
    $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAuthentVoucher&value=0');";
    return $tpl->widget_h("green","fa-solid fa-bed","{active2}","{vouchers_rooms}",$button);

}
function top_status_autoform():string{
    $HotSpotAutoLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoLogin"));
    if($HotSpotAutoLogin==0){return "";}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $button["name"] = "{uninstall}";
    $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAutoLogin&value=0');";
    return $tpl->widget_h("green",ico_email_check,"{active2}","{self_register}",$button);

}
function top_status_ldap():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    if($EnableOpenLDAP==0){
        $top_status_autoform=top_status_autoform();
        if(strlen($top_status_autoform)>3){
            return $top_status_autoform;
        }
    }
    $HotSpotAuthentLocalLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentLocalLDAP"));

    if($EnableOpenLDAP==0) {

        return $tpl->widget_h("gray","fas fa-database","{uninstalled}","{authentication}: {local_ldap}","minheight:150px");

    }

    if($HotSpotAuthentLocalLDAP==0) {
        $button["name"] = "{install_feature}";
        $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAuthentLocalLDAP&value=1');";
        return $tpl->widget_h("gray","fas fa-database","{disabled}","{authentication}: {local_ldap}",$button);
    }

    $button["name"] = "{uninstall}";
    $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAuthentLocalLDAP&value=0');";
    return $tpl->widget_h("green","fas fa-database","{active2}","{authentication}: {local_ldap}",$button);

}

function isSomeThingAuth():string{
    $tpl=new template_admin();
    if($GLOBALS["CLASS_SOCKETS"]->HOTSPOT_IS_AUTH_EXISTS()){
        VERBOSE("is_authExists == TRUE",__LINE__);
        return "";
    }
    return $tpl->_ENGINE_parse_body($tpl->widget_h("red", "fad fa-user-check", "{authentication}", "{no_auth_defined}","minheight:150px"));
}
function top_status_ad():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $HotSpotAuthentAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentAD"));

    if($HotSpotAuthentAD==0){
        $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
        if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $isSomeThingAuth=isSomeThingAuth();
            if(strlen($isSomeThingAuth)>2){return $isSomeThingAuth;}
        }
        if($EnableActiveDirectoryFeature==0){
            $isSomeThingAuth=isSomeThingAuth();
            if(strlen($isSomeThingAuth)>2){return $isSomeThingAuth;}
        }
    }


    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==0){$HotSpotAuthentAD=0;}

    if($HotSpotAuthentAD==1) {
        if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $button["name"] = "{uninstall}";
            $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAuthentAD&value=0');";
            return $tpl->widget_h("red", "fab fab fa-windows", "{license_error}",
                "{active_directory_authentication}",$button);
        }
    }

    if($EnableActiveDirectoryFeature==0) {
        return $tpl->widget_h("gray","fab fab fa-windows","{uninstalled}","{active_directory_authentication}","minheight:150px");
    }

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        return $tpl->widget_h("gray", "fab fab fa-windows", "{license_error}",
            "{active_directory_authentication}","minheight:150px");
    }


    if($HotSpotAuthentAD==0) {
        $button["name"] = "{install_feature}";
        $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAuthentAD&value=1');";
        return $tpl->widget_h("gray","fab fab fa-windows","{disabled}","{active_directory_authentication}",$button);
    }

    if(!is_file("/home/artica/web_templates/hotspot.AdCnx")){
        $button["name"] = "{repair}";
        $button["js"] = "Loadjs('$page?hotspot-ad-repair=yes');";
        return $tpl->widget_h("yellow","fab fab fa-windows","{not_configured}","{active_directory_authentication}",$button);
    }

    $f=explode("\n",@file_get_contents("/home/artica/web_templates/hotspot.AdCnx"));
    $c=0;
    foreach ($f as $line){
        if(strpos(" $line","|")==0){continue;}
        $c++;
    }
    if($c==0){
        $button["name"] = "{repair}";
        $button["js"] = "Loadjs('$page?hotspot-ad-repair=yes');";
        return $tpl->widget_h("yellow","fab fab fa-windows","{not_connected}","{active_directory_authentication}",$button);
    }

    $button["name"] = "{uninstall}";
    $button["js"] = "Loadjs('$page?hotspot-tokey-enabled=HotSpotAuthentAD&value=0');";
    return $tpl->widget_h("green","fab fab fa-windows","{active2}","{active_directory_authentication}",$button);
}