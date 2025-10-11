<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-flat"])){table_flat();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_POST["EnableGoogleSafeSearch"])){save();exit;}
if(isset($_GET["unbound-status"])){unbound_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_GET["table-js"])){table_js();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("SafeSearch(s)","fas fa-filter","{safesearch_explain}",
        "$page?table-start=yes","safesearch","progress-unbound-restart",false,"table-loader-safesearch");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);

}

function table_start(){
    $page=CurrentPageName();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $tpl=new template_admin();
        echo $tpl->div_error($tpl->_ENGINE_parse_body("{no_license}"));
    }
    echo "<div id='safesearch-table'></div><script>LoadAjax('safesearch-table','$page?table-flat=yes')</script>";
}

function table_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("SafeSearchs","$page?table=yes");

}

function table_flat(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
    $EnableBraveSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBraveSafeSearch"));
    $EnableDuckduckgoSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDuckduckgoSafeSearch"));
    $EnableYandexSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYandexSafeSearch"));
    $EnablePixabaySafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePixabaySafeSearch"));
    $EnableQwantSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQwantSafeSearch"));
    $EnableBingSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBingSafeSearch"));
    $EnableYoutubeSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYoutubeSafeSearch"));
    $EnbaleYoutubeModerate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnbaleYoutubeModerate"));

    $tpl->table_form_field_js("Loadjs('$page?table-js=yes')");


    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $tpl->table_form_field_js("");
        $EnableGoogleSafeSearch=0;
        $EnableQwantSafeSearch=0;
        $EnableBraveSafeSearch=0;
        $EnableBingSafeSearch=0;
        $EnableYoutubeSafeSearch=0;
        $EnbaleYoutubeModerate=0;
        $EnableDuckduckgoSafeSearch=0;
        $EnableYandexSafeSearch=0;
        $EnablePixabaySafeSearch=0;

    }


    $tpl->table_form_field_bool("Google SafeSearch",$EnableGoogleSafeSearch,ico_check);
    $tpl->table_form_field_bool("Qwant SafeSearch",$EnableQwantSafeSearch,ico_check);
    $tpl->table_form_field_bool("Brave SafeSearch",$EnableBraveSafeSearch,ico_check);
    $tpl->table_form_field_bool("Bing SafeSearch",$EnableBingSafeSearch,ico_check);
    $tpl->table_form_field_bool("Youtube (strict)",$EnableYoutubeSafeSearch,ico_check);
    $tpl->table_form_field_bool("Youtube (Moderate)",$EnbaleYoutubeModerate,ico_check);
    $tpl->table_form_field_bool("Duckduckgo SafeSearch",$EnableDuckduckgoSafeSearch,ico_check);
    $tpl->table_form_field_bool("Yandex SafeSearch",$EnableYandexSafeSearch,ico_check);
    $tpl->table_form_field_bool("Pixabay SafeSearch",$EnablePixabaySafeSearch,ico_check);
    echo $tpl->table_form_compile();

}

function table(){

	$tpl=new template_admin();
	if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMinTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMinTTL", 3600);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMAXTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMAXTTL", 172800);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheNEGTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheNEGTTL", 3600);}
	

	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
    $EnableBraveSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBraveSafeSearch"));
    $EnableDuckduckgoSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDuckduckgoSafeSearch"));
    $EnableYandexSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYandexSafeSearch"));
    $EnablePixabaySafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePixabaySafeSearch"));
	$EnableQwantSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQwantSafeSearch"));
	$EnableBingSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBingSafeSearch"));
    $EnableYoutubeSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYoutubeSafeSearch"));
    $EnbaleYoutubeModerate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnbaleYoutubeModerate"));
	$form[]=$tpl->field_checkbox("EnableGoogleSafeSearch","Google SafeSearch",$EnableGoogleSafeSearch,false,"{safesearch_explain}");
	$form[]=$tpl->field_checkbox("EnableQwantSafeSearch","Qwant SafeSearch",$EnableQwantSafeSearch,false,"{qwant_safesearch_explain}");

    $form[]=$tpl->field_checkbox("EnableBraveSafeSearch","Brave SafeSearch",$EnableBraveSafeSearch,false,"{qwant_safesearch_explain}");


    $form[]=$tpl->field_checkbox("EnableBingSafeSearch","Bing SafeSearch",$EnableBingSafeSearch,false,"");
	$form[]=$tpl->field_checkbox("EnableYoutubeSafeSearch","Youtube (strict)",$EnableYoutubeSafeSearch,false,"");
	$form[]=$tpl->field_checkbox("EnbaleYoutubeModerate","Youtube (Moderate)",$EnbaleYoutubeModerate,false,"");

	$form[]=$tpl->field_checkbox("EnableDuckduckgoSafeSearch","Duckduckgo",$EnableDuckduckgoSafeSearch,"");
    $form[]=$tpl->field_checkbox("EnableYandexSafeSearch","Yandex",$EnableYandexSafeSearch,"");
    $form[]=$tpl->field_checkbox("EnablePixabaySafeSearch","Pixabay",$EnablePixabaySafeSearch,"");

    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $page=CurrentPageName();
    $js="dialogInstance2.close();LoadAjax('safesearch-table','$page?table-flat=yes');Loadjs('fw.dns.unbound.restart.php');";

    if($UnboundEnabled==1){
        $js=$tpl->framework_buildjs("/unbound/reconfigure",
            "unbound.reconfigure.progress","unbound.reconfigure.progress.log",
            "unbound-restart-progress","dialogInstance2.close();LoadAjax('safesearch-table','$page?table-flat=yes');","LoadAjax('safesearch-table','$page?table-flat=yes');");
    }
    $html[]="<div id='unbound-restart-progress'></div>";
    $html[]=$tpl->form_outside(null, $form,null,"{apply}",$js,"AsDnsAdministrator",true);
	echo $tpl->_ENGINE_parse_body($html);
}


function save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
}




