<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["tab"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["RestartOpenLDAP"])){save();exit;}
if(isset($_POST["LokiListenInterface"])){save();exit;}
if(isset($_POST["EnableMultipleOrganizations"])){save();exit;}
if(isset($_POST["LdapDBCachesize"])){save();exit;}


if(isset($_GET["remove-database"])){remove_database_ask();exit;}
if(isset($_POST["removedb"])){remove_database_perform();exit;}
if(isset($_GET["flat"])){flat_config();exit;}
if(isset($_GET["form-service-js"])){form_service_js();exit;}
if(isset($_GET["form-service-popup"])){form_service_popup();exit;}
if(isset($_GET["form-service2-js"])){form_service2_js();exit;}
if(isset($_GET["form-service2-popup"])){form_service2_popup();exit;}
if(isset($_GET["form-service3-js"])){form_service3_js();exit;}
if(isset($_GET["form-service3-popup"])){form_service3_popup();exit;}


page();


function form_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return  $tpl->js_dialog1("{parameters}","$page?form-service-popup=yes");
}
function form_service2_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog1("{parameters}","$page?form-service2-popup=yes");
}
function form_service3_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog1("{ldap_configure_bdbd}","$page?form-service3-popup=yes");
}


function remove_database_ask():bool{
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs("/grafana/loki/removedb",
        "loki.progress","loki.progress.log",
        "progress-loki-restart");
    return $tpl->js_confirm_delete("{REMOVE_DATABASE}<hr>{rebuild_database_warn}",
        "removedb","yes",$jsrestart);
}
function remove_database_perform():bool{
    return admin_tracks("Remove Grafana Loki database server");
}






function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_LOKI}",ico_database,"{APP_LOKI_EXPLAIN}",
        "$page?tab=yes","loki-database","progress-loki-restart",false,"table-loader-openldap-service");

    $tpl=new template_admin("{APP_LOKI}",$html);

	if(isset($_GET["main-page"])){
		echo $tpl->build_firewall();
		return;
	}
	


	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?table=yes";
    echo $tpl->tabs_default($array);

}
function table(){
    $page=CurrentPageName();
    echo "<div id='loki-parameters'></div>
    <script>LoadAjax('loki-parameters','$page?table2=yes');</script>";
}

function table2(){
    $page = CurrentPageName();
    $tpl = new template_admin();

    $jsrestart = $tpl->framework_buildjs(
        "/grafana/restart",
        "loki.progress",
        "loki.progress.log",
        "progress-loki-restart",
        ""
    );


    $jsremove = "Loadjs('$page?remove-database=yes');";
    $topbuttons[] = array($jsrestart, ico_refresh, "{restart_service}");
    $topbuttons[] = array($jsremove, ico_trash, "{remove_database}");

    $html[] = "<table style='width:100%;margin-top:10px'>";
    $html[] = "<tr>";
    $html[] = "<td style='width:450px;vertical-align:top'>";
    $html[] = "<div id='loki-status'></div>";
    $html[] = "</td>";
    $html[] = "<td style='width:100%;vertical-align:top;padding-left:20px'>";
    $html[] = "<div id='loki-flat'></div>";
    $html[] = "</td>";
    $LokiVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiVersion");
    if ($LokiVersion <> null) {
        $LokiVersion = " v$LokiVersion";
    }
    $TINY_ARRAY["TITLE"] = "{APP_LOKI}$LokiVersion";
    $TINY_ARRAY["ICO"] = ico_database;
    $TINY_ARRAY["EXPL"] = "{APP_LOKI_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"] = $tpl->table_buttons($topbuttons);
    $headsjs = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    $js = $tpl->RefreshInterval_js("loki-status", $page, "status=yes");

    $html[] = "</tr>";
    $html[] = "</table>";
    $html[] = "<script>";
    $html[] = $js;
    $html[] = $headsjs;
    $html[] = "LoadAjax('loki-flat','$page?flat=yes');";
    $html[] = "</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function flat_config(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $security="AsSystemAdministrator";

    $loglevel_hash=array(
        0=>"{default}",
        1=>"{debug}",
        2=>"{info}",
        3=>"{warn}",
        4=>"{error}",
    );
    $CACHE_AGES[1]="1 {hour}";
    $CACHE_AGES[2]="2 {hours}";
    $CACHE_AGES[3]="3 {hours}";
    $CACHE_AGES[6]="6 {hours}";
    $CACHE_AGES[12]="12 {hours}";
    $CACHE_AGES[24]="1 {day}";
    $CACHE_AGES[48]="2 {days}";
    $CACHE_AGES[72]="3 {days}";
    $CACHE_AGES[180]="1 {week}";
    $CACHE_AGES[360]="2 {weeks}";
    $CACHE_AGES[720]="1 {month}";
    $CACHE_AGES[2160]="3 {months}";
    $CACHE_AGES[4380]="6 {months}";
    $CACHE_AGES[8760]="1 {year}";

    $RestartOpenLDAP = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartOpenLDAP"));
    $RestartLDAPEach = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartLDAPEach"));
    $LokiDebugLevel = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiDebugLevel"));
    $LokiRetentionPeriod= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiRetentionPeriod"));
    if ($LokiRetentionPeriod==0){
        $LokiRetentionPeriod=8760;
    }

    $LockLdapConfig = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockLdapConfig"));
    $LdapAllowAnonymous = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapAllowAnonymous"));
    $LokiListenInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiListenInterface"));
    $LokiHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiHTTPPort"));
    $EnableVirtualDomainsInMailBoxes = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes"));
    $EnableMultipleOrganizations = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));
    $OpenLDAPEnableSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPEnableSSL"));
    $OpenLDAPCertificate = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPCertificate"));
    

    if($LokiHTTPPort==0){
        $LokiHTTPPort=3100;
    }

    if(strlen($LokiListenInterface)<2){
        $LokiListenInterface="{loopback}";
    }

    $tpl->table_form_field_js("Loadjs('$page?form-service-js=yes')",$security);
    $tpl->table_form_field_text("{listen_interface}", $LokiListenInterface.":$LokiHTTPPort", ico_nic);
    $tpl->table_form_field_text("{log_level}", $loglevel_hash[$LokiDebugLevel], ico_bug);

    if($OpenLDAPEnableSSL==0){
        $tpl->table_form_field_bool("{use_ssl}",0,ico_ssl);
    }else{
        $tpl->table_form_field_text("{use_ssl}", "{listen_port} 636", ico_ssl);
        $tpl->table_form_field_text("{certificate}", $OpenLDAPCertificate, ico_certificate);
    }

    $tpl->table_form_field_text("{postscreen_cache_retention_time}",$CACHE_AGES[$LokiRetentionPeriod],ico_hd);



    $tpl->table_form_field_js("Loadjs('$page?form-service2-js=yes')",$security);
    $tpl->table_form_field_bool("{EnableMultipleOrganizations}",$EnableMultipleOrganizations,ico_sitemap);
    $tpl->table_form_field_bool("{multidomains}",$EnableVirtualDomainsInMailBoxes,ico_earth);
    $tpl->table_form_field_bool("{allowanonymouslogin}",$LdapAllowAnonymous,ico_unknown);
    if($LockLdapConfig==1){
        $tpl->table_form_field_bool("{LockLdapConfig}",1,ico_lock);
    }

    $EnableOpenLDAPRestFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"));
    if($EnableOpenLDAPRestFul==1){
        $OpenLDAPRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPRestFulApi"));
        $tpl->table_form_field_text("{API_KEY}",$OpenLDAPRestFulApi);
    }else{
        $tpl->table_form_field_bool("RESTFull API",0,ico_computer_down);
    }
    $OpenLDAPUseMDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPUseMDB"));
    if($OpenLDAPUseMDB==0) {
        $tpl->table_form_field_js("Loadjs('$page?form-service3-js=yes')",$security);
        $LdapDBSetCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBSetCachesize");
        $LdapDBSCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBCachesize");
        if($LdapDBSetCachesize==null){$LdapDBSetCachesize=5120000;}
        if($LdapDBSCachesize==null){$LdapDBSCachesize=1000;}
        $LdapDBSetCachesizeMo=($LdapDBSetCachesize/1024)/1000;
        $tpl->table_form_field_text("{ldap_cache_size}","$LdapDBSCachesize / $LdapDBSetCachesizeMo MB",ico_caching);
    }


    echo $tpl->table_form_compile();
}

function form_service_popup(){
    $LokiListenInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiListenInterface"));
    $LokiDebugLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiDebugLevel"));
    $loglevel_hash=array(
        0=>"{default}",
        1=>"{debug}",
        2=>"{info}",
        3=>"{warn}",
        4=>"{error}",
    );
    $tpl = new template_admin();
    $ldap=new clladp();
    $security="AsSystemAdministrator";


    $form[] = $tpl->field_interfaces("LokiListenInterface", "nodef:{listen_interface}", $LokiListenInterface);
    $form[]=$tpl->field_array_hash($loglevel_hash, "LokiDebugLevel", "nonull:{log_level}", $LokiDebugLevel,true,null);
    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",jsrestart(),$security);
}
function form_service2_popup(){
    $tpl = new template_admin();
    $security="AsSystemAdministrator";

    $CACHE_AGES[0]="{never}";
    $CACHE_AGES[30]="30 {minutes}";
    $CACHE_AGES[60]="1 {hour}";
    $CACHE_AGES[120]="2 {hours}";
    $CACHE_AGES[360]="6 {hours}";
    $CACHE_AGES[720]="12 {hours}";
    $CACHE_AGES[1440]="1 {day}";
    $CACHE_AGES[2880]="2 {days}";
    $CACHE_AGES[4320]="3 {days}";
    $CACHE_AGES[10080]="1 {week}";
    $CACHE_AGES[20160]="2 {weeks}";
    $CACHE_AGES[43200]="1 {month}";


    $RestartOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartOpenLDAP"));
    $RestartLDAPEach=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartLDAPEach"));

    $LockLdapConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockLdapConfig"));
    $LdapAllowAnonymous=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapAllowAnonymous"));

    $EnableVirtualDomainsInMailBoxes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes"));
    $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));

    if($RestartLDAPEach==0){$RestartLDAPEach=4320;}

    $form[]=$tpl->field_checkbox("EnableMultipleOrganizations","{EnableMultipleOrganizations}",$EnableMultipleOrganizations,false,"{EnableMultipleOrganizations_explain}");
    $form[]=$tpl->field_checkbox("EnableVirtualDomainsInMailBoxes","{multidomains}",$EnableVirtualDomainsInMailBoxes,false,"{multidomains_explain}");
    $form[]=$tpl->field_checkbox("RestartOpenLDAP","{restart_openldap_periodically}",$RestartOpenLDAP,false,"{restart_openldap_periodically_explain}");
    $form[]=$tpl->field_array_hash($CACHE_AGES, "RestartLDAPEach", "{restart_service_each}", $RestartLDAPEach,true,null);
    $form[]=$tpl->field_checkbox("LdapAllowAnonymous","{allowanonymouslogin}",$LdapAllowAnonymous,false,"{allowanonymouslogin_explain}");

    $form[]=$tpl->field_checkbox("LockLdapConfig","{LockLdapConfig}",$LockLdapConfig,false,"{LockLdapConfig_explain}");

    $EnableOpenLDAPRestFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"));
    if($EnableOpenLDAPRestFul==1){
        $OpenLDAPRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPRestFulApi"));
        $form[]=$tpl->field_text("OpenLDAPRestFulApi", "{API_KEY}", $OpenLDAPRestFulApi);
    }

    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",jsrestart(),$security);
}
function form_service3_popup(){
    $tpl = new template_admin();
    $security="AsSystemAdministrator";

    $LdapDBSetCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBSetCachesize");
    $LdapDBSCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBCachesize");
    if($LdapDBSetCachesize==null){$LdapDBSetCachesize=5120000;}
    if($LdapDBSCachesize==null){$LdapDBSCachesize=1000;}
    $LdapDBSetCachesizeMo=($LdapDBSetCachesize/1024)/1000;
    $form[] = $tpl->field_numeric("set_cachesize", "{set_cachesize} (MB)", $LdapDBSetCachesizeMo);
    $form[] = $tpl->field_numeric("LdapDBCachesize", "{ldap_cache_size}", $LdapDBSCachesize);
    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",jsrestart(),$security);
}

function jsrestart():string{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $jsrestart = $tpl->framework_buildjs(
        "/grafana/restart",
        "loki.progress",
        "loki.progress.log",
        "progress-loki-restart",
        ""
    );
    return "dialogInstance1.close();LoadAjax('loki-flat','$page?flat=yes');$jsrestart;";
}
function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();	
	$tpl->SAVE_POSTs();
	
}

function status():bool{

	$tpl=new template_admin();
	$page=CurrentPageName();

    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/grafana/status");

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
        "/grafana/restart",
        "loki.progress",
        "loki.progress.log",
        "progress-loki-restart",
        "LoadAjax('loki-parameters','$page?table2=yes');"
    );
	
	echo $tpl->SERVICE_STATUS($ini, "APP_LOKI",$jsrestart);

   return true;
	
}
