<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["DenyDnsResolve"])){save();exit;}
if(isset($_GET["main-form"])){main_settings_js();exit;}
if(isset($_GET["main-popup"])){main_settings();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $PROXYPAC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
    if($PROXYPAC_VERSION<>null){$PROXYPAC_VERSION=" v{$PROXYPAC_VERSION}";}
    $html=$tpl->page_header("{APP_PROXY_PAC}$PROXYPAC_VERSION",
        "fad fa-scroll-old",
        "{wpad_service_explain}",
        "$page?table=yes",
        "proxypac-status","progress-proxypac-restart",false,"table-loader-proxypac-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROXY_PAC}",$html);
        echo $tpl->build_firewall();
        return;
    }

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function main_settings_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{main_parameters}","$page?main-popup");
    return true;
}

function main_settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SessionCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacCacheTime"));
    $ProxyPacListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenInterface"));
    $ProxyPacListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenPort"));
    $ProxyPacLockScript=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScript"));
    $ProxyPacLockScriptContent=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScriptContent");
    $DenyDnsResolve=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DenyDnsResolve"));
    $ProxyPacDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacDebug"));
    if($SessionCache==0){$SessionCache=10;}
    $ProxyPACSQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPACSQL"));
    $security="AsSquidAdministrator";
    if($ProxyPacListenPort==0){$ProxyPacListenPort=80;}
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $ProxyPacSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacSSL"));
    $ProxyPacCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacCertificate"));

    $form[]=$tpl->field_checkbox("ProxyPacDebug","{debug}",$ProxyPacDebug,false);

    if($EnableNginx==0) {
        $form[] = $tpl->field_interfaces("ProxyPacListenInterface","{listen_interface}",$ProxyPacListenInterface);
        $form[] = $tpl->field_numeric("ProxyPacListenPort", "{listen_port}", $ProxyPacListenPort);
        $form[] = $tpl->field_checkbox("ProxyPacSSL","{useSSL}",$ProxyPacSSL,"ProxyPacCertificate");
        $form[] = $tpl->field_certificate("ProxyPacCertificate","{ssl_certificate}",$ProxyPacCertificate);

    }else{
        $form[]=$tpl->div_explain("{APP_NGINX}||{webservice_using_nginx}||wiki:https://wiki.articatech.com/en/reverse-proxy/architecture/proxy-pac");
    }
    $form[]=$tpl->field_checkbox("DenyDnsResolve","{do_not_resolv_ipaddr_wpad}",$DenyDnsResolve,false,"{do_not_resolv_ipaddr_wpad_ex}");
    $form[]=$tpl->field_checkbox("ProxyPacLockScript","{lock_script_with_this_script}",$ProxyPacLockScript);
    $form[]=$tpl->field_textareacode("ProxyPacLockScriptContent", "{script2}", $ProxyPacLockScriptContent);

    $jsrestart=$tpl->framework_buildjs(
        "/proxypac/reconfigure",
        "autoconfiguration.apply.progress",
        "autoconfiguration.apply.log",
        "progress-proxypac-restart",
        "LoadAjaxTiny('proxypac-status','$page?status=yes');"
    );


    $jsSimul="Loadjs('fw.proxypac.simul.php')";
    $html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",
        "dialogInstance1.close();LoadAjaxTiny('table-loader-proxypac-service','$page?table=yes');$jsrestart",$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:450px;vertical-align:top'>";
	$html[]="<div id='proxypac-status'></div>";
	$html[]="</td>";
	$html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";

	$SessionCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacCacheTime"));
    $ProxyPacListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenInterface"));
    $ProxyPacListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacListenPort"));
    $ProxyPacLockScript=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScript"));
	$ProxyPacLockScriptContent=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacLockScriptContent");
	$DenyDnsResolve=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DenyDnsResolve"));
	$ProxyPacDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacDebug"));
    $ProxyPacSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacSSL"));
	if($SessionCache==0){$SessionCache=10;}
	$ProxyPACSQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPACSQL"));
    $ProxyPacCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyPacCertificate"));
	$security="AsSquidAdministrator";
    if($ProxyPacListenPort==0){$ProxyPacListenPort=80;}
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $proto="HTTP";
    if($ProxyPacSSL==1){$proto="HTTPs ($ProxyPacCertificate)";}

    $tpl->table_form_field_js("Loadjs('$page?main-form=yes')");
    $tpl->table_form_section("{main_parameters}");
    $tpl->table_form_field_bool("{debug}",$ProxyPacDebug,ico_bug);
    if($EnableNginx==1) {
        $tpl->table_form_field_text("{listen}","127.0.0.1:9505",ico_nic);
    }else{
        if($ProxyPacListenInterface==null){
            $ProxyPacListenInterface="{all_interfaces}";
        }
        $tpl->table_form_field_text("{listen}","$proto&nbsp;$ProxyPacListenInterface:$ProxyPacListenPort",ico_nic);
    }

    $tpl->table_form_field_bool("{do_not_resolv_ipaddr_wpad}",$DenyDnsResolve,ico_proto);
    $tpl->table_form_field_bool("{lock_script_with_this_script}",$ProxyPacLockScript,ico_script);

	$jsrestart=$tpl->framework_buildjs(
        "/proxypac/reconfigure",
        "autoconfiguration.apply.progress",
        "autoconfiguration.apply.log",
        "progress-proxypac-restart",
        "LoadAjaxTiny('proxypac-status','$page?status=yes');"
    );
    $html[]=$tpl->table_form_compile();

    $jsSimul="Loadjs('fw.proxypac.simul.php')";

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$jsSimul\"><i class='fas fa-vial'></i> {test_your_rules} </label>";
    $btns[]="</div>";

    $PROXYPAC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
    if($PROXYPAC_VERSION<>null){$PROXYPAC_VERSION=" v{$PROXYPAC_VERSION}";}


    $TINY_ARRAY["TITLE"]="{APP_PROXY_PAC}$PROXYPAC_VERSION";
    $TINY_ARRAY["ICO"]="fad fa-scroll-old";
    $TINY_ARRAY["EXPL"]="{wpad_service_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);

    $js=$tpl->RefreshInterval_js("proxypac-status",$page,"status=yes");

	$html[]="<script>";
    $html[]= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]= "$js;</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function save(){
	$tpl=new template_admin();

    if($_POST["ProxyPacSSL"]==1){
        if(intval($_POST["ProxyPacListenPort"])==80){
            $_POST["ProxyPacListenPort"]=443;
        }
    }else{
        if(intval($_POST["ProxyPacListenPort"])==443){
            $_POST["ProxyPacListenPort"]=80;
        }
    }

	$tpl->SAVE_POSTs();
    admin_tracks_post("Saving Proxy PAC settings");

	
}

function status(){
	$tpl=new template_admin();
	$PROXYPAC_RQS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_RQS"));
    $PROXYPAC_RQS_TEXT=$tpl->FormatNumber($PROXYPAC_RQS);
	$page=CurrentPageName();

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid-autoconf.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/proxypac.restart.log";
	$ARRAY["CMD"]="/proxypac/restart";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="LoadAjaxTiny('proxypac-status','$page?status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-proxypac-restart')";


    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxypac/status"));
    if (!$json->Status) {
       echo  $tpl->widget_rouge($json->Error, "{error}");
       return false;
    }

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);

	echo $tpl->SERVICE_STATUS($ini, "APP_PROXY_PAC",$jsrestart);

	if($PROXYPAC_RQS==0){
	    echo $tpl->widget_grey("{requests}","{none}");
	    return false;
    }
	echo $tpl->widget_vert("{requests}",$PROXYPAC_RQS_TEXT);
    return true;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}