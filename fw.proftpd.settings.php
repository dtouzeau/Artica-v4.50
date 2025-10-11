<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ProFTPDInterfaces"])){proftpd_save();exit;}
if(isset($_POST["ProFTPDUseTLS"])){proftpd_save();exit;}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["ssl"])){mail_ssl();exit;}
if(isset($_GET["ssl-popup"])){mail_ssl_popup();exit;}
if(isset($_GET["main-popup"])){main_popup();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$version=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDVersion"));

    $html=$tpl->page_header("{APP_PROFTPD} v.$version","fas fa-tachometer-alt","{APP_PROFTPD_EXPLAIN}","$page?table-start=yes","ftp-service","progress-proftpd-restart",false,"table-loader-proftpd-service");





    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROFTPD} {members}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function main():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog2("{general_settings}","$page?main-popup=yes",550);
}
function mail_ssl():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog2("Secure FTP","$page?ssl-popup=yes",550);
}

function table_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $VsFTPDPassive=intval($sock->GET_INFO("VsFTPDPassive"));
    $ProFTPDInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDInterfaces"));
    $pasv_min_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VsFTPDPassiveMinPort"));
    $pasv_max_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VsFTPDPassiveMaxPort"));
    $VsFTPDFileOpenMode=$sock->GET_INFO("VsFTPDFileOpenMode");
    $VsFTPDLocalUmask=$sock->GET_INFO("VsFTPDLocalUmask");
    if($VsFTPDFileOpenMode==null){$VsFTPDFileOpenMode="0666";}
    if($VsFTPDLocalUmask==null){$VsFTPDLocalUmask="077";}
    $ProFTPDRootLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDRootLogin"));
    $VsFTPDPassiveAddr=$sock->GET_INFO("VsFTPDPassiveAddr");
    $VsFTPDLocalMaxRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VsFTPDLocalMaxRate"));

    $ProFTPDUseTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDUseTLS"));
    $ProFTPDCertificateName=$sock->GET_INFO("ProFTPDCertificateName");


    $tpl->table_form_field_js("Loadjs('$page?main=yes')","AsSystemAdministrator");
    if($ProFTPDInterfaces==null){$ProFTPDInterfaces="{all}";}
    $tpl->table_form_field_text("{listen_interfaces}",$ProFTPDInterfaces,ico_nic);

    $tpl->table_form_field_js("Loadjs('$page?ssl=yes')","AsSystemAdministrator");
    if($ProFTPDUseTLS==0){
        $tpl->table_form_field_bool("FTPs",0,ico_certificate);
    }else{

        $tpl->table_form_field_text("FTPs","{certificate}: $ProFTPDCertificateName",ico_certificate);
    }
    $tpl->table_form_field_js("Loadjs('$page?main=yes')","AsSystemAdministrator");
    if($VsFTPDPassive==1){
        if(strlen($VsFTPDPassiveAddr)<3){$VsFTPDPassiveAddr="0.0.0.0";}
        $tpl->table_form_field_text("{enable_passive_mode}","<small>{ports} $pasv_min_port-$pasv_max_port, {pasv_address}: $VsFTPDPassiveAddr</small>",ico_timeout);
    }else{
        $tpl->table_form_field_bool("{enable_passive_mode}",0,ico_timeout);
    }
    $tpl->table_form_field_text("{files_permissions}",$VsFTPDFileOpenMode,ico_file);
    $tpl->table_form_field_text("{directories_permissions}",$VsFTPDLocalUmask,ico_directory);



    $tpl->table_form_field_bool("{PermitRootLogin}",$ProFTPDRootLogin,ico_admin);
    $tpl->table_form_field_text("{max_rate}",$VsFTPDLocalMaxRate,ico_file);

    $version=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDVersion"));

    $uninstall=$tpl->framework_buildjs("/proftpd/uninstall","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","window.location.href ='/index'");

    $jsrestart=$tpl->framework_buildjs("/proftpd/restart","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","LoadAjax('proftpd-service','$page?status=yes');");

    $TINY_ARRAY["TITLE"]="{APP_PROFTPD} v.$version";
    $TINY_ARRAY["ICO"]="fas fa-folder-open";
    $TINY_ARRAY["EXPL"]="{proxypac_explain}";
    $topbuttons[] = array($uninstall, ico_trash, "{uninstall}","AsSystemAdministrator");
    $topbuttons[] = array($jsrestart, ico_refresh, "{restart}","AsSystemAdministrator");

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $js=$tpl->RefreshInterval_js("proftpd-service",$page,"status=yes");

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align:top'><div id='proftpd-service'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>$js;$headsjs</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function mail_ssl_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $ProFTPDUseTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDUseTLS"));
    $ProFTPDCertificateName=$sock->GET_INFO("ProFTPDCertificateName");

    $jsrestart=$tpl->framework_buildjs("/proftpd/restart","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","LoadAjax('proftpd-service','$page?status=yes');");
    $form[]=$tpl->field_checkbox("ProFTPDUseTLS","{UseSSL}",$ProFTPDUseTLS,true);
    $form[]=$tpl->field_certificate("ProFTPDCertificateName","nonull:{certificate}",$ProFTPDCertificateName);
    echo $tpl->form_outside(null, $form,null,"{apply}","dialogInstance2.close();LoadAjax('table-loader-proftpd-service','$page?table-start=yes');$jsrestart","AsSystemAdministrator");
    return true;
}
function main_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$VsFTPDPassive=$sock->GET_INFO("VsFTPDPassive");
	if(!is_numeric($VsFTPDPassive)){$VsFTPDPassive=1;}
	$VsFTPDPassiveAddr=$sock->GET_INFO("VsFTPDPassiveAddr");
	$pasv_min_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VsFTPDPassiveMinPort"));
	$pasv_max_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VsFTPDPassiveMaxPort"));
	$VsFTPDFileOpenMode=$sock->GET_INFO("VsFTPDFileOpenMode");
	$VsFTPDLocalUmask=$sock->GET_INFO("VsFTPDLocalUmask");
	if($VsFTPDFileOpenMode==null){$VsFTPDFileOpenMode="0666";}
	if($VsFTPDLocalUmask==null){$VsFTPDLocalUmask="077";}
	$ProFTPDRootLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDRootLogin"));
	
	$VsFTPDLocalMaxRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VsFTPDLocalMaxRate"));
	$ProFTPDInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDInterfaces"));

    $jsrestart=$tpl->framework_buildjs("/proftpd/restart","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","LoadAjax('proftpd-service','$page?status=yes');");

	$umask["022"]="{permissive} 755";
	$umask["026"]="{moderate} 751";
	$umask["027"]="{moderate} 750";
	$umask["077"]="{severe}	700";
	
	if($pasv_min_port==0){$pasv_min_port=40000;}
	if($pasv_max_port==0){$pasv_max_port=40200;}
	
	$form[]=$tpl->field_interfaces_choose("ProFTPDInterfaces","{listen_interfaces}",$ProFTPDInterfaces,false,null);
	$form[]=$tpl->field_checkbox("VsFTPDPassive","{enable_passive_mode}",$VsFTPDPassive,false,"{enable_passive_mode_explain}");
	$form[]=$tpl->field_numeric("VsFTPDPassiveMinPort","{pasv_min_port}",$pasv_min_port,"{pasv_minmax_port_explain}");
	$form[]=$tpl->field_numeric("VsFTPDPassiveMaxPort","{pasv_max_port}",$pasv_max_port,"{pasv_minmax_port_explain}");
	$form[]=$tpl->field_ipaddr("VsFTPDPassiveAddr","{pasv_address}",$VsFTPDPassiveAddr,false,"{pasv_address_explain}");
	$form[]=$tpl->field_numeric("VsFTPDFileOpenMode","{files_permissions}",$VsFTPDFileOpenMode,"{VsFTPDFileOpenMode}");
	$form[]=$tpl->field_numeric("VsFTPDLocalUmask","{directories_permissions}",$VsFTPDLocalUmask,null);
	$form[]=$tpl->field_checkbox("ProFTPDRootLogin","{PermitRootLogin}",$ProFTPDRootLogin,false,"");
	$form[]=$tpl->field_numeric("VsFTPDLocalMaxRate","{max_rate}",$VsFTPDLocalMaxRate,null);


	echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}","dialogInstance2.close();LoadAjax('table-loader-proftpd-service','$page?table-start=yes');$jsrestart","AsSystemAdministrator");
   return true;
	
}

function proftpd_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	foreach ($_POST as $num=>$val){
		
		$sock->SET_INFO($num, $val);
	}

}


function status():bool{
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();
    $data=$sock->REST_API("/proftpd/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $jsrestart=$tpl->framework_buildjs("/proftpd/restart","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","LoadAjax('proftpd-service','$page?status=yes');");
	echo $tpl->SERVICE_STATUS($bsini, "APP_PROFTPD",$jsrestart);
    return true;
	
	
}

