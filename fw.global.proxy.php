<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["WgetBindIpAddress"])){save();exit;}
if(isset($_POST["ArticaProxyServerEnabled"])){save();exit;}
if(isset($_POST["NoCheckSquid"])){save();exit;}
if(isset($_POST["InternetAccess"])){InternetAccess();exit;}
if(isset($_POST["InternalCheckSquid"])){local_proxy_save();exit;}
if(isset($_GET["proxy-js"])){proxy_js();exit;}
if(isset($_GET["popup-proxy"])){proxy_popup();exit;}
if(isset($_GET["artica-client-js"])){artica_client_js();exit;}
if(isset($_GET["internetaccess-js"])){internetaccess_js();exit;}
if(isset($_GET["internetaccess-popup"])){internetaccess_popup();exit;}
if(isset($_GET["artica-client-popup"])){artica_client_popup();exit;}
if(isset($_POST["DisableMsftncsi"])){DisableMsftncsi();exit();}

if(isset($_GET["artica-client-watch-js"])){artica_client_watchdog_js();exit;}
if(isset($_GET["artica-client-watch-popup"])){artica_client_watchdog_popup();exit;}
if(isset($_GET["local-proxy-js"])){local_proxy_js();exit;}
if(isset($_GET["local-proxy-popup"])){local_proxy_popup();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{internet_access} (Proxy)",
        "fas fa-globe","{internet_access_proxy_text}",
    "$page?table=yes","global-proxy","progress-globalproxy-restart",false,"table-loader-globalproxy-service");

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{internet_access}",$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function artica_client_watchdog_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{watchdog}","$page?artica-client-watch-popup=yes");
    return true;
}
function local_proxy_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{use_local_proxy}","$page?local-proxy-popup=yes",550);
    return true;
}
function proxy_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{ArticaProxyServerEnabled}","$page?popup-proxy=yes");
    return true;
}
function artica_client_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{artica_downloader}","$page?artica-client-popup=yes");
    return true;

}
function internetaccess_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{access_to_internet}","$page?internetaccess-popup=yes");
    return true;
}

function internetaccess_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($NoInternetAccess==1){
        $InternetAccess=0;
    }else{
        $InternetAccess=1;
    }
    $form[]=$tpl->field_checkbox("InternetAccess","{access_to_internet}",$InternetAccess);




    $jsafter="LoadAjaxSilent('table-loader-globalproxy-service','$page?table=yes');dialogInstance1.close();";
    echo $tpl->form_outside(null, $form,"{no_internet_explain}","{apply}",$jsafter,"AsSystemAdministrator");
    return true;
}
function InternetAccess():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();



    if($_POST["InternetAccess"]==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NoInternetAccess",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NoInternetAccess",1);
    }
    $sock=new sockets();
    $sock->REST_API("/system/nointernetaccess");

    return true;
}

function artica_client_watchdog_popup():bool{
    $arrcp=array();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DisableMsftncsi=0;
    $EnableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMsftncsi"));
    if($EnableMsftncsi==0){
        $DisableMsftncsi=1;
    }
    $form[]=$tpl->field_checkbox("DisableMsftncsi","{not_watchdog_internet}",$DisableMsftncsi);

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $InternetAccessSquid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMsftncsi"));

    if($SQUIDEnable==1){
        $form[]=$tpl->field_checkbox("InternetAccessSquid","{not_watchdog_internet} ({APP_SQUID})",$InternetAccessSquid);
    }


    $jsafter="LoadAjaxSilent('table-loader-globalproxy-service','$page?table=yes');dialogInstance1.close();";

    echo $tpl->form_outside(null, $form,"{not_watchdog_internet_explain}","{apply}",$jsafter,"AsSystemAdministrator");
    return true;

}

function DisableMsftncsi():bool{
    $DisableMsftncsi=intval($_POST["DisableMsftncsi"]);
    if($DisableMsftncsi==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMsftncsi",0);

        if(isset($_POST["InternetAccessSquid"])){
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableMsftncsi",$_POST["InternetAccessSquid"]);
        }

        return true;
    }
    if(isset($_POST["InternetAccessSquid"])){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableMsftncsi",$_POST["InternetAccessSquid"]);
    }


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMsftncsi",1);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/proxy/save");
    return true;
}
function artica_client_popup():bool{
    $arrcp=array();
    $tpl=new template_admin();
    $page=CurrentPageName();

    $WgetBindIpAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WgetBindIpAddress"));
    $CurlUserAgent=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlUserAgent"));
    $WgetTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WgetTimeOut"));
    if($CurlUserAgent==null){$CurlUserAgent="Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0";}
    $ip=new networking();
    if($WgetTimeOut==0){$WgetTimeOut=20;}



    $form[]=$tpl->field_text("CurlUserAgent", "User Agent", $CurlUserAgent);
    $form[]=$tpl->field_interfaces("WgetBindIpAddress","{WgetBindIpAddress}",$WgetBindIpAddress);
    $form[]=$tpl->field_numeric("WgetTimeOut", "{timeout} ({seconds})", $WgetTimeOut);


    $jsafter="LoadAjaxSilent('table-loader-globalproxy-service','$page?table=yes');dialogInstance1.close();";

    echo $tpl->form_outside(null, $form,null,"{apply}",$jsafter,"AsSystemAdministrator");
    return true;
}
function local_proxy_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $NoCheckSquid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoCheckSquid"));
    $InternalCheckSquid=1;
    if($NoCheckSquid==1){
        $InternalCheckSquid=0;
    }
    $jsafter="LoadAjaxSilent('table-loader-globalproxy-service','$page?table=yes');dialogInstance1.close();";
    $form[]=$tpl->field_checkbox("InternalCheckSquid","{use_local_proxy}",$InternalCheckSquid);
    echo $tpl->form_outside(null, $form,"{local_proxy_explain}","{apply}",$jsafter,"AsSystemAdministrator");
    return true;
}
function local_proxy_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(intval($_POST["InternalCheckSquid"])==1){
        $NoCheckSquid=0;
    }else{
        $NoCheckSquid=1;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NoCheckSquid",$NoCheckSquid);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/proxy/save");
}
function proxy_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ini=new Bs_IniHandler();
    $ini->loadString($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxySettings"));

    $ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
    $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
    $ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
    if($ArticaProxyServerPort==0){$ArticaProxyServerPort=3128;}
    $ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
    $ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
    if($ArticaProxyServerEnabled=="yes"){$ArticaProxyServerEnabled=1;};
    if($ArticaProxyServerEnabled=="no"){$ArticaProxyServerEnabled=0;};


    $ip=new networking();
    if(is_array($ip->array_TCP)){
        foreach ($ip->array_TCP as $eth=>$cip){
            if($cip==null){continue;}
            if($cip=="127.0.0.1"){continue;}
            $nic=new system_nic($eth);
            if($nic->enabled==0){continue;}
            $arrcp[$cip]="$nic->netzone - $nic->NICNAME ($cip)";
        }
    }



    unset($arrcp["127.0.0.1"]);
    $jsafter="LoadAjaxSilent('table-loader-globalproxy-service','$page?table=yes');dialogInstance1.close();";


    $form[]=$tpl->field_checkbox("ArticaProxyServerEnabled","{ArticaProxyServerEnabled}",$ArticaProxyServerEnabled);
    $form[]=$tpl->field_text("ArticaProxyServerName", "{ArticaProxyServerName}", $ArticaProxyServerName);
    $form[]=$tpl->field_numeric("ArticaProxyServerPort","{ArticaProxyServerPort}",$ArticaProxyServerPort);
    $form[]=$tpl->field_text("ArticaProxyServerUsername", "{ArticaProxyServerUsername}", $ArticaProxyServerUsername);
    $form[]=$tpl->field_password("ArticaProxyServerUserPassword", "{ArticaProxyServerUserPassword}", $ArticaProxyServerUserPassword);
    echo $tpl->form_outside(null, $form,null,"{apply}",$jsafter,"AsSystemAdministrator");
    return true;
}




function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter=null;
    $sock=new sockets();
    $ini=new Bs_IniHandler();
    $ini->loadString($sock->GET_INFO("ArticaProxySettings"));
    $CurlUserAgent=trim($sock->GET_INFO("CurlUserAgent"));
    $ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
    $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
    $ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
    if($ArticaProxyServerPort==0){$ArticaProxyServerPort=3128;}
    $ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
    if($ArticaProxyServerEnabled=="yes"){$ArticaProxyServerEnabled=1;};
    if($ArticaProxyServerEnabled=="no"){$ArticaProxyServerEnabled=0;};
    $NoCheckSquid=intval($sock->GET_INFO("NoCheckSquid"));
    $WgetBindIpAddress=trim($sock->GET_INFO("WgetBindIpAddress"));
    $EnableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMsftncsi"));
    $WgetTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WgetTimeOut"));

    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($NoInternetAccess==1){
        $InternetAccess=0;
    }else{
        $InternetAccess=1;
    }
    $tpl->table_form_field_js("Loadjs('$page?internetaccess-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{access_to_internet}",$InternetAccess,ico_earth);

    if($InternetAccess==1) {
        $tpl->table_form_section("{APP_SQUID}");
        $tpl->table_form_field_js("Loadjs('$page?proxy-js=yes')", "AsSystemAdministrator");
        $tpl->table_form_field_bool("{ArticaProxyServerEnabled}", $ArticaProxyServerEnabled, ico_earth);
        if ($ArticaProxyServerEnabled == 1) {
            if ($ArticaProxyServerUsername <> null) {
                $ArticaProxyServerUsername = " ($ArticaProxyServerUsername)";
            }
            $tpl->table_form_field_text("{ArticaProxyServerName}", "$ArticaProxyServerName:$ArticaProxyServerPort$ArticaProxyServerUsername", ico_server);

        }
        if ($WgetBindIpAddress == null) {
            $WgetBindIpAddress = "{default}";
        }

        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        if($SQUIDEnable==1) {
            $UseLocalProxy=1;
            if ($NoCheckSquid == 1) { $UseLocalProxy =0;}
            $tpl->table_form_field_js("Loadjs('$page?local-proxy-js=yes')", "AsSystemAdministrator");
            $tpl->table_form_field_bool("{use_local_proxy}", $UseLocalProxy, ico_server);
        }


        $tpl->table_form_section("{artica_downloader}");
        $tpl->table_form_field_js("Loadjs('$page?artica-client-js=yes')", "AsSystemAdministrator");




        if($WgetTimeOut==0){$WgetTimeOut=20;}
        $tpl->table_form_field_text("User-Agent", $CurlUserAgent, ico_proto);
        $tpl->table_form_field_text("{WgetBindIpAddress}", $WgetBindIpAddress, ico_interface);
        $tpl->table_form_field_text("{timeout}", "$WgetTimeOut {seconds}", ico_timeout);



        $not_watchdog_internet = 1;
        if ($EnableMsftncsi == 1) {
            $not_watchdog_internet = 0;
        }

        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $DisableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMsftncsi"));


        $tpl->table_form_section("{watchdog}");
        $tpl->table_form_field_js("Loadjs('$page?artica-client-watch-js=yes')", "AsSystemAdministrator");
        $tpl->table_form_field_bool("{not_watchdog_internet}", $not_watchdog_internet, ico_interface);
        if($SQUIDEnable==1) {
            $tpl->table_form_field_bool("{not_watchdog_internet} ({APP_SQUID})", $DisableMsftncsi, ico_interface);
        }


    }


    echo $tpl->table_form_compile();
    return true;

}

function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();


    if(isset($_POST["NoCheckSquid"])) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NoCheckSquid", $_POST["NoCheckSquid"]);
    }
    if(isset($_POST["WgetBindIpAddress"])) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WgetBindIpAddress", $_POST["WgetBindIpAddress"]);
    }
    if(isset($_POST["CurlUserAgent"])) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CurlUserAgent", $_POST["CurlUserAgent"]);
    }
    if(isset($_POST["WgetTimeOut"])) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WgetTimeOut", $_POST["WgetTimeOut"]);
    }



    $conf[]="[PROXY]";

    foreach ($_POST as $key=>$val){
		$conf[]="$key=$val";
	}
	
    if(trim($_POST["ArticaProxyServerUserPassword"])<>null){$p=":{$_POST["ArticaProxyServerUserPassword"]}";}
	if(trim($_POST["ArticaProxyServerUsername"])<>null){$at="{$_POST["ArticaProxyServerUsername$p@"]}";}
	if(trim($_POST["ArticaProxyServerPort"])<>null){$port=":{$_POST["ArticaProxyServerPort"]}";}
	
	$uri="http://$at{$_POST["ArticaProxyServerName"]}$port";
	
	$conf[]="ArticaCompiledProxyUri=$uri\n";

    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(@implode("\n",$conf),"ArticaProxySettings");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/proxy/save");
}