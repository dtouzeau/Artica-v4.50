<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["explainthis"])){explainthis();exit;}
if(isset($_POST["SquidAllowSmartPhones"])){Save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{global_rules}",
        "fa-solid fa-ballot-check","{permanent_authorizations}",
    "$page?table=yes","proxy-global-rules","progress-firehol-restart",false,"table-loader-cache-level");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{global_rules}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$AllowSquidDropBox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidDropBox"));
	$AllowSquidSkype=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidSkype"));
	$AllowSquidOffice365=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidOffice365"));
	$AllowSquidGoogle=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidGoogle"));
	$AllowSquidWhatsApp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidWhatsApp"));
	$AllowSquidTeamViewer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidTeamViewer"));
	$AllowSquidKaspersky=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidKaspersky"));
	
	$AllowSquidOtherProtocols=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidOtherProtocols"));
	$AllowSquidHSTS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidHSTS"));
	$SquidAllowSmartPhones=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowSmartPhones"));
	$AllowSquidCompatibility=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidCompatibility"));
	$AllowSquidWhitelistsSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidWhitelistsSSL"));
    $AllowWindowsUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowWindowsUpdates"));
    $AllowSquidGoogleHearth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidGoogleHearth"));
    $DisableForm=false;
    $SquidDisableWhiteLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableWhiteLists"));
    if($SquidDisableWhiteLists==1){
        $AllowSquidDropBox=0;
        $AllowSquidSkype=0;
        $AllowSquidOffice365=0;
        $AllowSquidGoogle=0;
        $AllowSquidWhatsApp=0;
        $AllowSquidTeamViewer=0;
        $AllowSquidKaspersky=0;
        $AllowSquidOtherProtocols=0;
        $AllowSquidHSTS=0;
        $SquidAllowSmartPhones=0;
        $AllowSquidCompatibility=0;
        $AllowSquidWhitelistsSSL=0;
        $AllowWindowsUpdates=0;
        $AllowSquidGoogleHearth=0;
        $DisableForm=true;
        echo $tpl->div_warning("{aclgbdisabled}");
    }

	$form[]=$tpl->field_checkbox("AllowWindowsUpdates","{ProxyDedicateMicrosoftRules}",$AllowWindowsUpdates,false,"{ProxyDedicateMicrosoftRules}");

	$form[]=$tpl->field_checkbox("SquidAllowSmartPhones","{AllowSmartphonesRuleText}",$SquidAllowSmartPhones,false,"{AllowSmartphonesRuleExplain}");
	$form[]=$tpl->field_checkbox("AllowSquidDropBox","{AllowSquidDropBox}",$AllowSquidDropBox,false,"{AllowSquidDropBox_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidWhatsApp","{AllowSquidWhatsApp}",$AllowSquidWhatsApp,false,"{AllowSquidWhatsApp_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidSkype","{AllowSquidSkype}",$AllowSquidSkype,false,"{AllowSquidSkype_explain}");
    $AllowSquidMicrosoft=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidMicrosoft"));


    $form[]=$tpl->field_checkbox("AllowSquidMicrosoft","{AllowSquidMicrosoft}",$AllowSquidMicrosoft,false,"{AllowSquidMicrosoft_explain}");
	
	$form[]=$tpl->field_checkbox("AllowSquidOffice365","{AllowSquidOffice365}",$AllowSquidOffice365,false,"{AllowSquidOffice365_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidTeamViewer","{AllowSquidTeamViewer}",$AllowSquidTeamViewer,false,"{AllowSquidTeamViewer_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidGoogle","{AllowSquidGoogle}",$AllowSquidGoogle,false,"{AllowSquidGoogle_explain}");
    $form[]=$tpl->field_checkbox("AllowSquidGoogleHearth","{AllowSquidGoogleHearth}",$AllowSquidGoogleHearth,false,"{AllowSquidGoogleHearth_explain}");


	$form[]=$tpl->field_checkbox("AllowSquidKaspersky","{AllowSquidKaspersky}",$AllowSquidKaspersky,false,"{AllowSquidKaspersky_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidCompatibility","{AllowSquidCompatibility}",$AllowSquidCompatibility,false,"{AllowSquidCompatibility_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidOtherProtocols","{AllowSquidOtherProtocols}",$AllowSquidOtherProtocols,false,"{AllowSquidOtherProtocols_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidHSTS","{AllowSquidHSTS}",$AllowSquidHSTS,false,"{AllowSquidHSTS_explain}");
	$form[]=$tpl->field_checkbox("AllowSquidWhitelistsSSL","{AllowSquidWhitelistsSSL}",$AllowSquidWhitelistsSSL,false,"{AllowSquidWhitelistsSSL_explain}");
    $form[]=$tpl->field_hidden("AllowSquidWhitelistsSSLHidden",$AllowSquidWhitelistsSSL);
    $page=CurrentPageName();
    $jsCompile=$tpl->framework_buildjs("/proxy/whitelists/nohupcompile","squid.global.whitelists.progress","squid.global.whitelists.progress.log","progress-firehol-restart","LoadAjax('table-loader-cache-level','$page?table=yes');");

	$security="AsSquidAdministrator";
	
	
	$html[]=$tpl->form_outside("", $form,null,"{apply}",$jsCompile,$security,false,$DisableForm);
	echo $tpl->_ENGINE_parse_body( $html);
}
function Save(){
	foreach ($_POST as $num=>$ligne){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($num, $ligne);
	}
    if(intval($_POST["AllowSquidWhitelistsSSL"]) != intval($_POST["AllowSquidWhitelistsSSLHidden"])) {

        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("/proxy/ssl/build");

    }
}