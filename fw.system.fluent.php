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



if(isset($_GET["status"])){webapi_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}

if(isset($_GET["section-service-js"])){section_service_js();exit;}
if(isset($_GET["section-service-popup"])){section_service_popup();exit;}

if(isset($_GET["section-features-js"])){section_features_js();exit;}
if(isset($_GET["section-features-popup"])){section_features_popup();exit;}

if(isset($_GET["section-auth-js"])){section_auth_js();exit;}
if(isset($_GET["section-auth-popup"])){section_auth_popup();exit;}
if(isset($_GET["tiny"])){Tiny();exit;}

page();
function page(){
    //
    $page=CurrentPageName();
    $raccourci="fluent-bit-status";
    $tpl=new template_admin();

    $FluentBitVersion =$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FluentBitVersion");

    $html=$tpl->page_header("Fluent BIt v$FluentBitVersion
",
        "fa-solid fa-dove","{APP_FLUENTBIT_EXPLAIN}",
        "$page?tabs=yes",$raccourci,"progress-fluentb-restart",false,"table-loader-fluentb");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }



    echo $tpl->_ENGINE_parse_body($html);

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

function section_js_form():string{
    $page=CurrentPageName();
    $jsRestart=restart_array();
    return "BootstrapDialog1.close();LoadAjaxSilent('progress-fluentbit-start','$page?table1=yes');$jsRestart";
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
    return $tpl->framework_buildjs("/fluentbit/restart",
        "fluentbit.progress","fluentbit.progress.log",
        "progress-fluentb-restart","Loadjs('$page?tiny=yes');");
}

function webapi_status():bool{

    $tpl    = new template_admin();

    $sock=new sockets();
    $data=$sock->REST_API("/fluentbit/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }

    if(!property_exists($json,"Info")){
        echo $tpl->widget_rouge("{error}","Info not found");
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $jsRestart=restart_array();
    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_FLUENTBIT",$jsRestart);
    echo $tpl->_ENGINE_parse_body($final);
    return true;

}
function table():bool{
    $page=CurrentPageName();
    echo "<div style='margin-top:10px' id='progress-fluentbit-start'></div>
<script>LoadAjaxSilent('progress-fluentbit-start','$page?table1=yes')</script>";
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


    $tpl->table_form_field_js("Loadjs('$page?section-service-js=yes')");
    if($ActiveDirectoryRestInterface==null){$ActiveDirectoryRestInterface="127.0.0.1";}
    $tpl->table_form_field_bool("{debug}",$ActiveDirectoryRestDebug,ico_bug);
    $tpl->table_form_field_text("{listen_interfaces}",$ActiveDirectoryRestInterface.":$ActiveDirectoryRestPort",ico_interface);
    if($ActiveDirectoryRestSSL==0) {
        $tpl->table_form_field_bool("{ssl}", $ActiveDirectoryRestSSL, ico_ssl);
    }else{
        $tpl->table_form_field_text("{certificate}",$ActiveDirectoryRestCert,ico_ssl);
    }


    $tpl->table_form_section("{features}");
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
    $Interval=$tpl->RefreshInterval_js("fluentbit-status",$page,"status=yes",3);


    $html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='fluentbit-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>
	$Interval;Loadjs('$page?tiny=yes');
	</script>	
	";


    echo $tpl->_ENGINE_parse_body($html);
}

function Tiny():bool{
    $tpl                            = new template_admin();
    $FluentBitVersion =$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FluentBitVersion");
    $TINY_ARRAY["TITLE"]="Fluent Bit v$FluentBitVersion";
    $TINY_ARRAY["ICO"]="fa-solid fa-dove";
    $TINY_ARRAY["EXPL"]="{APP_FLUENTBIT_EXPLAIN}";
    $TINY_ARRAY["URL"]="fluent-bit-status";
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