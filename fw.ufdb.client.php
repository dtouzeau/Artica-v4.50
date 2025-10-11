<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string', null);}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["UfdbgClientDebug"])){save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{WEB_FILTERING}: {client_parameters} ({connector})","fad fa-exchange-alt","{ufdbgclient_explain}","$page?start=yes","webfiltering-client","progress-firehol-restart",false,"table-loader-ufdbclient-service");
	


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{WEB_FILTERING}: {client_parameters} ({connector})");
        return;
    }


	echo $tpl->_ENGINE_parse_body($html);

}

function start(){
    $page=CurrentPageName();
    echo "<div id='table-loader-ufdbclient-start'></div>
    <script>LoadAjax('table-loader-ufdbclient-start','$page?table=yes');</script>
    ";
}

function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLUSTER_CLI=true;
	$childwarn=null;

    $UfdbgClientDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgClientDebug"));
	$RemoteUfdbguardAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbguardAddr"));
	$RemoteUfdbguardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbguardPort"));
    $UfdbgclientSockTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgclientSockTimeOut"));

	if($RemoteUfdbguardPort==0){$RemoteUfdbguardPort=3977;}
	if($RemoteUfdbguardAddr==null){$RemoteUfdbguardAddr="127.0.0.1";}
    if($UfdbgclientSockTimeOut==0){$UfdbgclientSockTimeOut=21;}

	$redirect_behaviorA[null]="{default}";
	$redirect_behaviorA["url"]="{redirect_connexion}";
	$redirect_behaviorA["url-rewrite"]="{rewrite_url}";
	
	
	$HTTP_CODE[301]="{Moved_Permanently} (301)";
	$HTTP_CODE[302]="{Moved_Temporarily} (302)";
	$HTTP_CODE[303]="{http_code_see_other} (303)";
	$HTTP_CODE[307]="{Moved_Temporarily} (307)";
	
	$SquidGuardRedirectBehavior=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardRedirectBehavior");
	$SquidGuardRedirectHTTPCode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardRedirectHTTPCode"));
	$UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
    $SquidGuardWebExternalUriDecrypted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUriDecrypted"));
	if($SquidGuardRedirectHTTPCode==0){$SquidGuardRedirectHTTPCode=302;}
    $RedirectorsArray=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
    if(!isset($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=10;}
    if(!isset($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
    if(!isset($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
    if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=4;}

    if(!isset($RedirectorsArray["url_rewrite_reset_sp723"])){
        $RedirectorsArray["url_rewrite_children"]=10;
        $RedirectorsArray["url_rewrite_startup"]=5;
        $RedirectorsArray["url_rewrite_idle"]=1;
        $RedirectorsArray["url_rewrite_concurrency"]=4;
    }
	

	$form[]=$tpl->field_checkbox("UfdbgClientDebug","{debug}",$UfdbgClientDebug,false,"");
	$form[]=$tpl->field_numeric("UfdbgclientSockTimeOut","{socket_timeout} {seconds}",$UfdbgclientSockTimeOut);

    $SquidGuardWebExternalUri=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUri"));
    $SquidGuardWebExternalUriSSL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUriSSL"));

    if($SquidGuardWebExternalUri==null){$SquidGuardWebExternalUri="http://articatech.net/block.html";}
    if($SquidGuardWebExternalUriSSL==null){$SquidGuardWebExternalUriSSL="articatech.net";}


    $form[]=$tpl->field_section("{UfdbUseGlobalWebPage}","{UfdbUseGlobalWebPage_explain}");
    $form[]=$tpl->field_text("SquidGuardWebExternalUri", "{fulluri}", $SquidGuardWebExternalUri,false);
    $form[]=$tpl->field_text("SquidGuardWebExternalUriSSL", "{fulluri} HTTPS", $SquidGuardWebExternalUriSSL,false);
    $form[]=$tpl->field_checkbox("SquidGuardWebExternalUriDecrypted", "{decrypted_ssl_websites}", $SquidGuardWebExternalUriDecrypted,false);




	$form[]=$tpl->field_section("{squid_redirectors}","{squid_redirectors_howto}");
	$form[]=$tpl->field_numeric("url_rewrite_children","$childwarn{url_rewrite_children}",$RedirectorsArray["url_rewrite_children"],"{url_rewrite_children_text}");
	$form[]=$tpl->field_numeric("url_rewrite_startup","{url_rewrite_startup}",$RedirectorsArray["url_rewrite_startup"],"{url_rewrite_startup_text}");
	$form[]=$tpl->field_numeric("url_rewrite_idle","{url_rewrite_idle}",$RedirectorsArray["url_rewrite_idle"],"{url_rewrite_idle_text}");
	$form[]=$tpl->field_numeric("url_rewrite_concurrency","{url_rewrite_concurrency}",$RedirectorsArray["url_rewrite_concurrency"],"{url_rewrite_concurrency_text}");

    if($RedirectorsArray["url_rewrite_concurrency"]>32){$RedirectorsArray["url_rewrite_concurrency"]=32;}
	$form[]=$tpl->field_section("{UseRemoteUfdbguardService}",'{ufdbclient_parms_explain}');
	$form[]=$tpl->field_checkbox("UseRemoteUfdbguardService","{UseRemoteUfdbguardService}",$UseRemoteUfdbguardService,"RemoteUfdbguardAddr,RemoteUfdbguardPort");
	$form[]=$tpl->field_ipaddr("RemoteUfdbguardAddr", "{remote_server}", $RemoteUfdbguardAddr);
	$form[]=$tpl->field_numeric("RemoteUfdbguardPort","{remote_port}",$RemoteUfdbguardPort);

    $jsrestart=$tpl->framework_buildjs("/goshield/connector/reconfigure",
        "go.shield.reconfigure.progress",
        "go.shield.reconfigure.progress.log",
        "progress-firehol-restart",
        "LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');LoadAjax('table-loader-ufdbclient-start','$page?table=yes');",null,null,"AsProxyMonitor");


	$html=$tpl->form_outside("{parameters}", @implode("\n", $form),null,
        "{apply}",$jsrestart,"AsDansGuardianAdministrator");
	echo $html;
	
	
}

function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if($_POST["UseRemoteUfdbguardService"]==1){
        if($_POST["RemoteUfdbguardAddr"]=="127.0.0.1"){$_POST["UseRemoteUfdbguardService"]=0; }
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebExternalUri",$_POST["SquidGuardWebExternalUri"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebExternalUriSSL",$_POST["SquidGuardWebExternalUriSSL"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebExternalUriDecrypted",$_POST["SquidGuardWebExternalUriDecrypted"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbgClientDebug",$_POST["UfdbgClientDebug"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbgclientSockTimeOut",$_POST["UfdbgclientSockTimeOut"]);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RemoteUfdbguardAddr",$_POST["RemoteUfdbguardAddr"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RemoteUfdbguardPort",$_POST["RemoteUfdbguardPort"]);



    if($_POST["UseRemoteUfdbguardService"]==1){
        $nic=new networking();
        $nicZ=$nic->Local_interfaces();
        foreach ($nicZ as $yinter=>$line){
            $znic=new system_nic($yinter);
            $IPADDR=$znic->IPADDR;
            if($_POST["RemoteUfdbguardAddr"]=="$IPADDR"){$_POST["UseRemoteUfdbguardService"]=0;break;}
        }
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbGuardDisabledRedirectors",0);
    $RedirectorsArray=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

    foreach ($_POST as $key=>$value){
		if(preg_match("#^url_rewrite_#", $key)){
			$RedirectorsArray[$key]=$value;
		}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key, $value);
	}

    $RedirectorsArray["url_rewrite_reset_sp723"]=time();
	$datas=base64_encode(serialize($RedirectorsArray));
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "SquidClientParams");
	
	
}