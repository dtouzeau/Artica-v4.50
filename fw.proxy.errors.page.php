<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");

if(isset($_GET["form-js"])){parameters_js();exit;}
if(isset($_GET["form-popup"])){parameters_popup();exit;}
if(isset($_POST["SquidGuardDenyConnect"])){SquidGuardDenyConnect_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["web-error-page-middle-section"])){web_error_page_middle_section();exit;}
if(isset($_POST["WebErrorPageServiceInDebug"])){web_error_page_save();exit;}
if(isset($_POST["UfdbUseInternalServiceEnableSSL"])){web_error_page_save();exit;}


if(isset($_POST["SquidTemplateSimple"])){SquidTemplateSimple();exit;}
if(isset($_GET["service-status-start"])){service_status_start();exit;}
if(isset($_GET["service-status-events"])){service_status_events();exit;}
if(isset($_GET["service-status-events-search"])){service_status_events_search();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["SquidGuardDenyConnect-js"])){SquidGuardDenyConnect_js();exit;}
if(isset($_GET["SquidGuardDenyConnect-popup"])){SquidGuardDenyConnect_popup();exit;}
if(isset($_GET["form-squid-templates-js"])){form_squid_templates_params_js();exit;}
if(isset($_GET["form-squid-templates-popup"])){form_squid_templates_params_popup();exit;}
if(isset($_GET["form-ssl-js"])){form_ssl_js();exit;}
if(isset($_GET["form-ssl-popup"])){form_ssl_popup();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$TITLE="{errors_pages}";
	$sock=new sockets();
	$SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==0){$TITLE="{templates}";}

    $html=$tpl->page_header($TITLE,"fas fa-file-alt",
        "{proxy_errors_pages_explain}","$page?tabs=yes",
        "proxy-errors","progress-pxtempl-restart",false,"table-loader-errors-pages");


	if(isset($_GET["main-page"])){$tpl=new template_admin($TITLE,$html);
        echo $tpl->build_firewall();return true;
    }
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$SQUIDEnable=intval($sock->GET_INFO("SQUIDEnable"));

    $array["{service_status}"]="$page?service-status-start=yes";
	$array["{template_manager}"]="fw.proxy.templates.manager.php";
	if($SQUIDEnable==1){
		$array["{errors_pages}"]="fw.proxy.templates.error.squid.php";
	}
    $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));
    if($UfdbUseInternalService==1){
        $array["{service_events}"]="$page?service-status-events=yes";

    }
	
	echo $tpl->tabs_default($array);
	return true;
	
}
function parameters_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{proxy_error_pages}","$page?form-popup=yes",750);
}
function form_ssl_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{UseSSL}","$page?form-ssl-popup=yes",750);
}

function form_squid_templates_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{parameters}","$page?form-squid-templates-popup=yes",750);

}
function SquidGuardDenyConnect_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{ssl_decrypt_compatibility}","$page?SquidGuardDenyConnect-popup=yes",650);
    return true;
}
function SquidGuardDenyConnect_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidGuardDenyConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardDenyConnect"));

    $form[] = $tpl->field_checkbox("SquidGuardDenyConnect",
            "{ssl_decrypt_compatibility}",$SquidGuardDenyConnect);

    $ssl_decrypt_compatibility_explain=$tpl->_ENGINE_parse_body("{ssl_decrypt_compatibility_explain}");
    $link="<a href=\"/webfiltering-policies\">{web_filter_policies}</a>";
    $ssl_decrypt_compatibility_explain=str_replace("%s",$link,$ssl_decrypt_compatibility_explain);

    $jsrestart=$tpl->framework_buildjs("squid2.php?global-ufdb-client=yes",
        "squid.access.center.progress",
        "squid.access.center.progress.log",
        "SquidGuardDenyConnect-progress",
        "dialogInstance1.close();"
    );

    echo "<div id='SquidGuardDenyConnect-progress' style='margin-bottom:30px'></div>".
        $tpl->form_outside(null,$form,$ssl_decrypt_compatibility_explain,
        "{apply}","LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');$jsrestart",
        "AsDansGuardianAdministrator");
    return true;
}
function SquidGuardDenyConnect_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Save Proxy MAN-IN-THE-MIDDLE compatibility to {$_POST["SquidGuardDenyConnect"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardDenyConnect",$_POST["SquidGuardDenyConnect"]);
}
function form_ssl_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UfdbUseInternalServiceEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
    $UfdbUseInternalServiceHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));
    $UfdbUseInternalServiceCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceCertificate"));
    if($UfdbUseInternalServiceHTTPSPort==0){$UfdbUseInternalServiceHTTPSPort=9026;}

    $form[] = $tpl->field_section("{UseSSL}");
    $form[] = $tpl->field_checkbox("UfdbUseInternalServiceEnableSSL","{UseSSL}",$UfdbUseInternalServiceEnableSSL,"UfdbUseInternalServiceHTTPSPort,UfdbUseInternalServiceCertificate");
    $form[] = $tpl->field_numeric("UfdbUseInternalServiceHTTPSPort","{ssl_port}",$UfdbUseInternalServiceHTTPSPort);
    $form[] = $tpl->field_certificate("UfdbUseInternalServiceCertificate","{ssl_certificate}",$UfdbUseInternalServiceCertificate);



    $jsrestart="dialogInstance1.close();".
        "LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');".
        $tpl->framework_buildjs("/weberror/restart",
            "weberror.progress","weberror.log","ufdbweberror-restart");


    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsDansGuardianAdministrator");
    return true;
}

function parameters_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
    $WebErrorPageServiceInDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebErrorPageServiceInDebug"));
    $UfdbUseInternalServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHostname"));
    if($UfdbUseInternalServiceHostname==null){$UfdbUseInternalServiceHostname=php_uname("n");}
    $UfdbUseInternalServiceHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    if($UfdbUseInternalServiceHTTPPort==0){$UfdbUseInternalServiceHTTPPort=9025;}

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $WebErrorPageListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebErrorPageListenInterface"));

    $HaClusterUseCentralErrorPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseCentralErrorPage"));
    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==0){
        $HaClusterUseCentralErrorPage=0;
    }
    if($HaClusterUseCentralErrorPage==1){
        $EnableNginx=0;
    }

    if($EnableNginx==1) {
        $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_nginx_explain}");
    }else{
        $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_explain}");
    }

    if($SquidTemplateid==0){$SquidTemplateid=1;}
    $form[] = $tpl->field_checkbox("WebErrorPageServiceInDebug","{debug}",$WebErrorPageServiceInDebug);
    $form[] = $tpl->field_templates("SquidTemplateid", "{default_template}", $SquidTemplateid);

    if($EnableNginx==1) {
        $form[] = $tpl->field_section("{UfdbUseInternalService}", "{UfdbUseInternalService_nginx_explain}");

    }else{
        $form[] = $tpl->field_section("&nbsp;");
        $form[] =$tpl->field_interfaces("WebErrorPageListenInterface","{listen_interface}",$WebErrorPageListenInterface);
        if($HaClusterUseCentralErrorPage==0) {
            $form[] = $tpl->field_numeric("UfdbUseInternalServiceHTTPPort", "{http_port}", $UfdbUseInternalServiceHTTPPort);
             $form[] = $tpl->field_text("UfdbUseInternalServiceHostname","{hostname}",$UfdbUseInternalServiceHostname);
        }

    }
    $jsrestart="dialogInstance1.close();".
        "LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');".
        $tpl->framework_buildjs("/weberror/restart",
        "weberror.progress","weberror.log","ufdbweberror-restart");


    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsDansGuardianAdministrator");
    return true;
}
function service_status_start():bool{
    $page=CurrentPageName();
    echo "<div id='web-error-page-middle-section'></div>
    <script>LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes')</script>
    ";
    return true;
}
function web_error_page_middle_section():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
    $NoTitle1=false;
    $WebErrorPagesCompiled=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebErrorPagesCompiled"));

    $EnableHaClusterForceURI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaClusterForceURI"));
    $HaClusterForceURI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterForceURI"));
    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));


    if($HaClusterClient==0){
        $EnableHaClusterForceURI=0;
    }
    if($EnableHaClusterForceURI==1){
        $NoTitle1=true;
        $EnableHaClusterForceURIExpl=$tpl->_ENGINE_parse_body("{EnableHaClusterForceURI}");
        $EnableHaClusterForceURIExpl=str_replace("%s","<strong class='text-danger'>$HaClusterForceURI</strong>",$EnableHaClusterForceURIExpl);
        $tpl->table_form_section("{redirect_url} (HaCluster)",$EnableHaClusterForceURIExpl);

    }


    $WebErrorPageServiceInDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebErrorPageServiceInDebug"));
    $UfdbUseInternalServiceHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    if($UfdbUseInternalServiceHTTPPort==0){$UfdbUseInternalServiceHTTPPort=9025;}
    $UfdbUseInternalServiceEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
    $UfdbUseInternalServiceHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));
    $UfdbUseInternalServiceCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceCertificate"));
    $UfdbUseInternalServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHostname"));
    if($UfdbUseInternalServiceHostname==null){$UfdbUseInternalServiceHostname=php_uname("n");}
    $UfdbUseInternalServiceHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    if($UfdbUseInternalServiceHTTPPort==0){$UfdbUseInternalServiceHTTPPort=9025;}
    if($UfdbUseInternalServiceHTTPSPort==0){$UfdbUseInternalServiceHTTPSPort=9026;}
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $WebErrorPageListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebErrorPageListenInterface"));
    if($WebErrorPageListenInterface==null){$WebErrorPageListenInterface="*";}
    if($SquidTemplateid==0){$SquidTemplateid=1;}

    $SquidTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
    $FTPTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FTPTemplateid"));
    $SquidTemplateSimple=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateSimple"));
    $SquidHTTPTemplateLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage"));
    if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}

    $SquidHTTPTemplateNoProxyVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoProxyVersion"));
    $SquidHTTPTemplateNoVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoVersion"));

    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $WizardSavedSettings=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
    if(!isset($WizardSavedSettings["mail"])){$WizardSavedSettings["mail"]="root@localhost";}
    if(!isset($LicenseInfos["EMAIL"])){$LicenseInfos["EMAIL"]=null;}
    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));



    $cache_mgr_user=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("cache_mgr_user");
    if($cache_mgr_user==null){
        $sock=new sockets();$sock->SET_INFO("cache_mgr_user", $LicenseInfos["EMAIL"]);
        $cache_mgr_user=$LicenseInfos["EMAIL"];
    }


    if($SquidTemplateid==0){$SquidTemplateid=1;}
    if($FTPTemplateid==0){$FTPTemplateid=2;}
    foreach ($WebErrorPagesCompiled as $index=>$ligne2){
        if(!isset($ligne2["url"])){
            continue;
        }

        if(isset($ALREAD[$ligne2["url"]])){
            continue;
        }

        if(strlen($ligne2["url"])<4){
            continue;
        }
        $tpl->table_form_field_text("{redirect_url}","<span style='text-transform: none'>". $ligne2["url"]."</span>",ico_link);
        $ALREAD[$ligne2["url"]]=true;
    }

    $tpl->table_form_field_js("Loadjs('$page?form-js=yes')","AsDansGuardianAdministrator");
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne = $q->mysqli_fetch_array("SELECT TemplateName FROM templates_manager WHERE ID=$SquidTemplateid");
    if($ligne["TemplateName"]==null){$ligne["TemplateName"]="{default}";}
    $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));

    if($EnableHaClusterForceURI==1){
        $EnableNginx=0;
        $UfdbUseInternalServiceEnableSSL=0;
        $UfdbUseInternalServiceHTTPPort=9025;
    }



    if($UfdbUseInternalService==1) {
        if ($EnableNginx == 1) {
            $tpl->table_form_section("{UfdbUseInternalService}", "{UfdbUseInternalService_nginx_explain}");
            $tpl->table_form_field_bool("{debug}", $WebErrorPageServiceInDebug, ico_bug);
            $tpl->table_form_field_text("{default_template}", $ligne["TemplateName"], ico_params);
            $tpl->table_form_field_text("{http_port}", "127.0.0.1:9577", ico_interface);
        } else {
            if(!$NoTitle1) {
                $tpl->table_form_section("{UfdbUseInternalService}", "{UfdbUseInternalService_explain}");
            }
            $tpl->table_form_field_bool("{debug}", $WebErrorPageServiceInDebug, ico_bug);
            $tpl->table_form_field_text("{default_template}", $ligne["TemplateName"], ico_params);
            $tpl->table_form_field_js("Loadjs('$page?form-js=yes')", "AsSquidAdministrator");
            $tpl->table_form_field_text("{http_port}", "$WebErrorPageListenInterface:$UfdbUseInternalServiceHTTPPort ($UfdbUseInternalServiceHostname)", ico_interface);

            $tpl->table_form_field_js("Loadjs('$page?form-ssl-js=yes')", "AsSquidAdministrator");
            if($EnableHaClusterForceURI==1){
                $tpl->table_form_field_js("", "AsSquidAdministrator");
            }
            if ($UfdbUseInternalServiceEnableSSL == 0) {
                $tpl->table_form_field_bool("{ssl_port}", 0, ico_ssl);
            } else {
                $tpl->table_form_field_text("{ssl_port}", "$WebErrorPageListenInterface:$UfdbUseInternalServiceHTTPSPort ($UfdbUseInternalServiceHostname)", ico_interface);

                $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/infos/$UfdbUseInternalServiceCertificate/0"));
                if(!$json->Status) {
                    $tpl->table_form_field_text("{certificate}",$json->Error,ico_certificate,true);
                }else{
                    $tpl->table_form_field_certificate("$UfdbUseInternalServiceCertificate");
                }
            }

        }

        $tpl->table_form_field_js("Loadjs('$page?form-js=yes')", "AsSquidAdministrator");
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE UseSSL=1 AND enabled=1");
        $SquidGuardDenyConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardDenyConnect"));
        $c=0;
        foreach ($results as $index=>$ligne){
           $c++;
        }
        if($c==0){
            if($SquidGuardDenyConnect==1){
                $tpl->table_form_field_js("Loadjs('$page?SquidGuardDenyConnect-js=yes')","AsDansGuardianAdministrator");
                $tpl->table_form_field_text("{ssl_decrypt_compatibility}","{active} !!!!",ico_proto,true);
            }else{
                $tpl->table_form_field_js("","");
                $tpl->table_form_field_info("{ssl_decrypt_compatibility}","{none}");
            }
        }else{
            $tpl->table_form_field_js("Loadjs('$page?SquidGuardDenyConnect-js=yes')","AsDansGuardianAdministrator");
            $tpl->table_form_field_bool("{ssl_decrypt_compatibility}",$SquidGuardDenyConnect,ico_proto);
        }
    }else{
        $tpl->table_form_field_bool("{UfdbUseInternalService}", 0, ico_disabled);
    }
    if($SQUIDEnable==1) {
        $tpl->table_form_field_js("Loadjs('$page?form-squid-templates-js=yes')", "AsDansGuardianAdministrator");
        $tpl->table_form_section("{APP_SQUID}", "{proxy_error_pages_explain}");
        $tpl->table_form_field_bool("{use_simple_template_mode}", $SquidTemplateSimple, ico_html);

        $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligneTT = $q->mysqli_fetch_array("SELECT TemplateName FROM templates_manager WHERE ID=$SquidTemplateid");
        $SquidTemplateid_text = $ligneTT["TemplateName"];

        $tpl->table_form_field_text("{proxy_error_pages}", $SquidTemplateid_text, ico_html);

        $ligneTT = $q->mysqli_fetch_array("SELECT TemplateName FROM templates_manager WHERE ID=$FTPTemplateid");
        $FTPTemplateid_text = $ligneTT["TemplateName"];
        $tpl->table_form_field_text("{ftp_template}", $FTPTemplateid_text, ico_html);

        $flang = template_langs_array($SquidHTTPTemplateLanguage);
        $tpl->table_form_field_text("{language} ({default})", $flang[$SquidHTTPTemplateLanguage], ico_language);

        $tpl->table_form_section("SMTP");
        $tpl->table_form_field_text("{cache_mgr_user}", $cache_mgr_user, ico_admin);
        $SquidSMTPTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMTPTimeout"));
        if($SquidSMTPTimeout==0){
            $SquidSMTPTimeout=10;
        }
        $SquidDifferedSMTPMessages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDifferedSMTPMessages"));
        $tpl->table_form_field_bool("{use_smtp_queue}", $SquidDifferedSMTPMessages, ico_database);

        $tpl->table_form_field_text("{service_smtp} {timeout}", $SquidSMTPTimeout." {seconds}", ico_timeout);

        $optsz=array();

        if ($SquidHTTPTemplateNoVersion == 1) {
            $optsz[] = "{remove_artica_version}";
        }
        if ($SquidHTTPTemplateNoProxyVersion == 1) {
            $optsz[] = "{remove_proxy_version}";
        }
        if (count($optsz) == 0) {
            $optsz[] = "{none}";
        }
        $tpl->table_form_field_text("{options}", @implode(", ", $optsz), ico_options);
    }




    $html[]="<div id='ufdbweberror-restart'></div>";
    $html[]="<table style='width:100%;margin-top:10px' >";
    $html[]="<tr>";
    $html[]="<td style='width:450px;vertical-align:top'>";
    $html[]="<div id='ufdbweberror-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";
    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $TITLE="{errors_pages}";
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){$TITLE="{templates}";}



    $js="s_PopUp('/proxy-templates-manager','1024','800')";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$js\">
	<i class='fa-solid fa-file-dashed-line'></i> {template_manager} </label>";
    $btns[]="</div>";

    $TINY_ARRAY["TITLE"]=$TITLE;
    $TINY_ARRAY["ICO"]="fas fa-file-alt";
    $TINY_ARRAY["EXPL"]="{proxy_errors_pages_explain}";
    $TINY_ARRAY["URL"]="proxy-errors";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $sAuto=$tpl->RefreshInterval_js("ufdbweberror-status",$page,"status=yes");

    $html[]="<script>";
    $html[]="$sAuto;";
    $html[]="$jstiny";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function template_langs_array($SquidHTTPTemplateLanguage):array{
    $languages=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
    foreach ($languages as $lang=>$line){if($lang=="templates"){continue;}$flang[$lang]="$lang";}

    $xtpl=new template_simple();
    reset($xtpl->arrayxLangs);
    foreach ($xtpl->arrayxLangs as $lang=>$xarr){
        foreach ($xarr as $index=>$z){unset($flang[$z]);}
    }
    $flang[null]="{not_defined}";
    $flang[$SquidHTTPTemplateLanguage]=$SquidHTTPTemplateLanguage;
    unset($flang["templates"]);
    ksort($flang);
    return $flang;
}

function form_squid_templates_params_popup():bool{
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$SquidTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateid"));
	$FTPTemplateid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FTPTemplateid"));
	$SquidTemplateSimple=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTemplateSimple"));
	$SquidHTTPTemplateLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateLanguage"));
	if($SquidHTTPTemplateLanguage==null){$SquidHTTPTemplateLanguage="en-us";}
	
	$SquidHTTPTemplateNoProxyVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoProxyVersion"));
	$SquidHTTPTemplateNoVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTTPTemplateNoVersion"));
	
	$LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
	$WizardSavedSettings=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}

	
	$languages=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db"));
    foreach ($languages as $lang=>$line){if($lang=="templates"){continue;}$flang[$lang]="$lang";}
	
	$xtpl=new template_simple();
	reset($xtpl->arrayxLangs);
    foreach ($xtpl->arrayxLangs as $lang=>$xarr){
        foreach ($xarr as $index=>$z){unset($flang[$z]);}
    }
	$flang[null]="{not_defined}";
	$flang[$SquidHTTPTemplateLanguage]=$SquidHTTPTemplateLanguage;
	unset($flang["templates"]);
	ksort($flang);
	
	$cache_mgr_user=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("cache_mgr_user");
	if($cache_mgr_user==null){

		$sock=new sockets();$sock->SET_INFO("cache_mgr_user", $LicenseInfos["EMAIL"]);
		$cache_mgr_user=$LicenseInfos["EMAIL"];
	}
	
	
	if($SquidTemplateid==0){$SquidTemplateid=1;}
	if($FTPTemplateid==0){$FTPTemplateid=2;}
    $SquidDifferedSMTPMessages=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDifferedSMTPMessages");

    $SquidSMTPTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMTPTimeout"));
    if($SquidSMTPTimeout==0){
        $SquidSMTPTimeout=10;
    }

	
	$form[]=$tpl->field_checkbox("SquidTemplateSimple","{use_simple_template_mode}",$SquidTemplateSimple,false,"{use_simple_template_mode_squid_explain}");
	$form[]=$tpl->field_templates("SquidTemplateid", "{proxy_error_pages}", $SquidTemplateid);
	$form[]=$tpl->field_templates("FTPTemplateid", "{ftp_template}", $FTPTemplateid);
	$form[]=$tpl->field_array_hash($flang, "SquidHTTPTemplateLanguage", "{language} ({default})", $SquidHTTPTemplateLanguage);
	$form[]=$tpl->field_email("cache_mgr_user", "{cache_mgr_user}", $cache_mgr_user,false,"{cache_mgr_user_text}");

    // Patch 08/10/2025 - Differed SMTP messages.
    $form[]=$tpl->field_checkbox("SquidDifferedSMTPMessages","{use_smtp_queue}",$SquidDifferedSMTPMessages,false,"{use_smtp_queue_notifs_explain}");
    $form[]=$tpl->field_numeric("SquidSMTPTimeout","{service_smtp} {timeout} ({seconds})",$SquidSMTPTimeout);

	$form[]=$tpl->field_checkbox("SquidHTTPTemplateNoVersion","{remove_artica_version}",$SquidHTTPTemplateNoVersion);
	$form[]=$tpl->field_checkbox("SquidHTTPTemplateNoProxyVersion","{remove_proxy_version}",$SquidHTTPTemplateNoProxyVersion);

    $jsrestart="LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');".
        $tpl->framework_buildjs("/proxy/templates",
            "squid.templates.single.progress","squid.templates.single.log","ufdbweberror-restart-pr","dialogInstance1.close();LoadAjaxTiny('ufdbweberror-status','$page?status=yes');");

	
    echo "<div id='ufdbweberror-restart-pr'></div>";
	echo $tpl->form_outside("", $form,"{proxy_error_pages_explain}","{apply}",$jsrestart,"AsSquidAdministrator",true);
    return true;
}
function web_error_page_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/weberror/rules"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
    }

    return admin_tracks_post("Saving Web error page service");
}

function status(){
    $tpl=new template_admin();
    $UfdbUseInternalService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"));
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/status"));



    if($UfdbUseInternalService==0){

        $jenable=$tpl->framework_buildjs("/weberror/install",
            "weberror.progress","weberror.log","ufdbweberror-restart","LoadAjaxTiny('ufdbweberror-status','$page?status=yes');LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');");


        $btn[0]["js"] = $jenable;
        $btn[0]["name"] = "{activate}";
        $btn[0]["icon"] = "far fa-shield-check";
        $html[] = $tpl->widget_grey("{service_status}", "{disabled}", $btn);
    }else{
        $jrestart=$tpl->framework_buildjs("/weberror/restart",
            "weberror.progress","weberror.log","ufdbweberror-restart");
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        $html[]=$tpl->SERVICE_STATUS($ini, "WEB_ERROR_PAGE",$jrestart);

        $jenable=$tpl->framework_buildjs("/weberror/uninstall",
            "weberror.progress","weberror.log","ufdbweberror-restart","LoadAjaxSilent('web-error-page-middle-section','$page?web-error-page-middle-section=yes');");

        $EnableHaClusterForceURI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaClusterForceURI"));
        $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

        if($HaClusterClient==0){
            $EnableHaClusterForceURI=0;
        }

        if($EnableHaClusterForceURI==0) {
            $btn[0]["js"] = $jenable;
            $btn[0]["name"] = "{uninstall}";
            $btn[0]["icon"] = "fas fa-trash-alt";
            $html[] = $tpl->widget_vert("{service_status}", "{active2}", $btn);
        }else{
            $html[] = $tpl->widget_vert("{service_status}", "{active2}/HaCluster");
        }

        if(property_exists($json,"Metric")){
            $html[] = $tpl->widget_vert("{connections} {$json->Metric->mainaddr}", $tpl->FormatNumber($json->Metric->counter));
        }

    }

    $js="s_PopUp('/proxy-templates-manager','1200','800')";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"$js\">
	<i class='fa-solid fa-file-dashed-line'></i> {template_manager} </label>";
    $btns[]="</div>";

    $WEBERRORPAGE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBERRORPAGE_VERSION");
    $TINY_ARRAY["TITLE"]="{WEB_ERROR_PAGE}: {parameters} v$WEBERRORPAGE_VERSION";
    $TINY_ARRAY["ICO"]="fad fa-page-break";
    $TINY_ARRAY["EXPL"]="{proxy_errors_pages_explain}";
    $TINY_ARRAY["URL"]="proxy-errors";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function SquidTemplateSimple(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
	
}
function service_status_events(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&service-status-events-search=yes");
    echo "</div>";
}
function service_status_events_search(){
        $tpl        = new template_admin();
        $MAIN       = $tpl->format_search_protocol($_GET["search"],false,false,false,true);
        $line       = base64_encode(serialize($MAIN));
        $tfile      = PROGRESS_DIR."/weberror.events.syslog";
        $pat        = PROGRESS_DIR."/weberror.events.pattern";

        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("weberror.php?events=$line");
        $data=explode("\n",@file_get_contents($tfile));
        krsort($data);
        $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th style='width:1%' nowrap>{date}</th>
        	<th style='width:1%' nowrap>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";


        foreach ($data as $line){
            $line=trim($line);
            $class=null;
            if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#",$line,$re)){
                continue;
            }
            $time=$re[1]." ".$re[2]." ".$re[3];
            $pid=$re[4];
            $event=$re[5];

            if(preg_match("#Success#i",$event)){
                $class="text-info";
            }
            if(preg_match("#started version#i",$event)){
                $class="text-info font-bold";
            }

            if(preg_match("#Error(:|\s)#i",$event)){
                $class="text-danger font-bold";
            }

            if(preg_match("#failed to #",$event)){
                $class="text-danger font-bold";
            }
            if(preg_match("#Reconfiguring Web#",$event)){
                $class="text-info font-bold";
            }

            $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$time</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td style='width:99%' nowrap class='$class'>$event</td>
				</tr>";

        }



        $html[]="</tbody></table>";
        $html[]="<div><i>".@file_get_contents($pat)."</i></div>";
        $TINY_ARRAY["TITLE"]="{WEB_ERROR_PAGE}: {service_events}";
        $TINY_ARRAY["ICO"]=ico_eye;
        $TINY_ARRAY["EXPL"]="{proxy_errors_pages_explain}";
        $TINY_ARRAY["URL"]="proxy-errors";
        $TINY_ARRAY["BUTTONS"]=null;
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
        $html[]="<script>$jstiny</script>";

        echo $tpl->_ENGINE_parse_body($html);

    }


