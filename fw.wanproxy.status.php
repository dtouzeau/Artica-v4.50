<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["WanproxyMode"])){Save();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}
if(isset($_GET["wanproxy-status"])){wanproxy_status();exit;}
if(isset($_GET["socks-status"])){socks_status();exit;}
page();

function ufdbdebug_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{debug_mode}", "$page?ufdbdebug-popup=yes");

}
function ufdbconf_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{file_configuration}", "$page?ufdbconf-popup=yes");
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_WANPROXY} &raquo;&raquo; {service_status}","fad fa-compress-alt","{WANPROXY_ABOUT}"
        ,"$page?table=yes",
        "wanproxy","progress-wanproxy-restart",false,"table-wanproxystatus");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_WANPROXY} &raquo;&raquo; {service_status}",$html);
        echo $tpl->build_firewall();
        return;
    }


	echo $tpl->_ENGINE_parse_body($html);

}

function curl_socks(){
    $WanproxyParentPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyParentPort"));
    if($WanproxyParentPort==0){$WanproxyParentPort=8088;}
    $tpl=new template_admin();
    $ch = curl_init();
    $headers=array();
    //curl_setopt($ch, CURLOPT_INTERFACE, $Interface);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate",
        "Cache-Control: no-cache,must revalidate", 'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, "http://www.msftncsi.com/ncsi.txt");
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
    curl_setopt($ch, CURLOPT_PROXY,"127.0.0.1:$WanproxyParentPort");
    curl_setopt($ch, CURLOPT_HEADERFUNCTION,
        function($curl, $header) use (&$headers)
        {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
                return $len;

            $headers[strtolower(trim($header[0]))]= trim($header[1]);

            return $len;
        }
    );

    curl_exec($ch);
    $CURLINFO_HTTP_CODE = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    $curl_strerr=curl_strerror($curl_errno);

    if($CURLINFO_HTTP_CODE==200){
        echo $tpl->widget_vert("{connection} HTTP","OK");
        return true;
    }

    echo $tpl->widget_rouge("$curl_strerr","{failed}");
    return true;

}

function socks_status(){
    $tpl=new template_admin();
    $WanproxyMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyMode"));
    if($WanproxyMode<>"client"){return false;}
    $WanProxyClientUseSock=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyClientUseSock"));
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){$WanProxyClientUseSock=1;}
    if($WanProxyClientUseSock==0){return false;}
    curl_socks();

    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/redsocks/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart=$tpl->framework_buildjs(
        "/redsocks/restart",
        "redsocks.progress",
        "redsocks.progress.log",
        "progress-wanproxy-restart");


    $html[]=$tpl->SERVICE_STATUS($ini, "APP_REDSOCKS",$jsrestart);
    echo @implode("\n",$html);
    return true;
}

function wanproxy_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wanproxy.php?status=yes");

    $jsrestart=$tpl->framework_buildjs("wanproxy.php?reconfigure=yes",
        "wanproxy.progress","wanproxy.progress.log",
        "progress-wanproxy-restart","LoadAjax('wanproxy-status','$page?wanproxy-status=yes');");
    $ini->loadFile(PROGRESS_DIR."/wanproxy.status");

    $html[]=$tpl->SERVICE_STATUS($ini, "APP_WANPROXY",$jsrestart);
    $html[]="<script>";
    $html[]="function WanProxyStatusRefresh(){";
    $html[]="\tif(!document.getElementById('wanproxy-status')){ return false;}";
    $html[]="LoadAjaxSilent('socks-status','$page?socks-status=yes');";
    $html[]="LoadAjaxSilent('wanproxy-status','$page?wanproxy-status=yes');";
    $html[]="}";
    $html[]="setTimeout(\"WanProxyStatusRefresh()\",6000);";
    $html[]="</script>";
    echo @implode("\n",$html);
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $btn_config_js="s_PopUpFull('/WanProxyMetrics/','1024','900');";
    $btn_config=$tpl->button_autnonome("{pmetrics}", "$btn_config_js", "far fa-tachometer-alt-slow","AsSquidAdministrator",335);
    //pmetrics
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px' valign='top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td>";
	$html[]="<div id='wanproxy-status'></div>";
    $html[]="</td></tr>";
    $html[]="<tr><td>$btn_config</td></tr>";
    $html[]="<tr><td style='padding-top:8px'>";
    $html[]="<div id='socks-status'></div>";
    $html[]="</td></tr>";
	
	$list[null]="{not_defined}";
	$list["parent"]="{WAN_PARENT}";
	$list["client"]="{WAN_CLIENT}";

	
$html[]="</table></td>";

$html[]="<td style='width:99%;vertical-align:top'>";
	$WanproxyMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyMode"));
	$WanproxyInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyInterface"));
	$WanproxyParentPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyParentPort"));
    $WanProxyDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyDebug"));
	$WanproxySquidPortID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxySquidPortID"));
	$WanProxyMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyMemory"));
	$WanProxyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyCache"));
    $WanProxyParentUseSock=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyParentUseSock"));
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $WanProxyParentUseSockLock = false;
    $WanProxyClientUseSockLock = false;
	$WanProxyDestAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyDestAddr"));
	$WanproxyDestPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyDestPort"));

    $WanProxyClientUseSock=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyClientUseSock"));
    $WanProxyClientHookPorts=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyClientHookPorts"));
    $WanProxyParentSockIfOut=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyParentSockIfOut"));

    if($WanProxyClientHookPorts==null){
        $WanProxyClientHookPorts="80,443,21";
    }
	
	$WanProxyUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyUsername"));
	$WanProxyPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyPassword"));
	
	if($WanProxyMemory==0){$WanProxyMemory=128;}
	if($WanProxyCache==0){$WanProxyCache=1;}
	if($WanproxyParentPort==0){$WanproxyParentPort=8088;}
	if($WanproxyDestPort==0){$WanproxyDestPort=8088;}

    if($SQUIDEnable==0){
        $WanProxyParentUseSock=1;
        $WanProxyParentUseSockLock=true;
        $WanProxyClientUseSock=1;
        $WanProxyClientUseSockLock=true;
    }


	if($WanproxyMode==null){
        echo $tpl->div_warning("{wanproxy_method_explain}");
    }
	
$form[]=$tpl->field_array_select($list, "WanproxyMode", "{mode}", $WanproxyMode,null,"LoadAjax('table-wanproxystatus','$page?table=yes');");
    $form[] = $tpl->field_checkbox("WanProxyDebug","{debug}",$WanProxyDebug);


if($WanproxyMode=="parent") {
    $form[] = $tpl->field_section("{WAN_PARENT}", "{wanproxy_parent_mode}");
    $form[]=$tpl->field_numeric("WanproxyParentPort","{listen_port}",$WanproxyParentPort);
    $form[] = $tpl->field_interfaces("WanproxyInterface", "nooloop:nodef:{listen_interface}", $WanproxyInterface);


    $form[] = $tpl->field_checkbox("WanProxyParentUseSock","{use_socks5_proxy}",$WanProxyParentUseSock,"WanProxyParentSockIfOut",null,$WanProxyParentUseSockLock);
    $form[] = $tpl->field_interfaces("WanProxyParentSockIfOut","{outgoing_interface}",$WanProxyParentSockIfOut);


    if($SQUIDEnable==1) {
        $form[] = $tpl->field_squid_ports("WanproxySquidPortID", "{proxy_port}", $WanproxySquidPortID);
    }
}
if($WanproxyMode=="client") {
    $form[] = $tpl->field_section("{WAN_CLIENT}", "{wanproxy_client_mode}");
    $form[] = $tpl->field_checkbox("WanProxyClientUseSock","{use_socks5_proxy}",
        $WanProxyClientUseSock,"WanProxyClientHookPorts",null,$WanProxyClientUseSockLock);
    $form[] = $tpl->field_text("WanProxyClientHookPorts","{transparent_ports}",$WanProxyClientHookPorts);


    $form[] = $tpl->field_text("WanProxyDestAddr", "{remote_address}", $WanProxyDestAddr);
    $form[] = $tpl->field_numeric("WanproxyDestPort", "{remote_port}", $WanproxyDestPort);

    if($SQUIDEnable==1) {
        $form[] = $tpl->field_section("{APP_SQUID}");
        $form[] = $tpl->field_numeric("WanproxyParentPort", "{listen_port}", $WanproxyParentPort);
        $form[] = $tpl->field_text("WanProxyUsername", "{username}", $WanProxyUsername);
        $form[] = $tpl->field_password2("WanProxyPassword", "{password}", $WanProxyPassword);
    }
}

if($WanproxyMode<>null) {
    $form[] = $tpl->field_section("{memory}/{caching}");
    $form[] = $tpl->field_numeric("WanProxyMemory", "{memory_cache} (MB)", $WanProxyMemory);
    $form[] = $tpl->field_numeric("WanProxyCache", "{caches_on_disk} (GB)", $WanProxyCache);
}

    $jsrestart=$tpl->framework_buildjs("wanproxy.php?reconfigure=yes",
        "wanproxy.progress","wanproxy.progress.log",
        "progress-wanproxy-restart","LoadAjax('wanproxy-status','$page?wanproxy-status=yes');");

$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSquidAdministrator");
$html[]="</td>";
$html[]="</tr>";

$html[]="</table>";
$html[]="<script>LoadAjax('wanproxy-status','$page?wanproxy-status=yes');</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function Save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs(null,"Saving WanProxy parameters");

}


