<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["EnableLDAPSyncProv"])){cluster_save();exit;}
if(isset($_GET["cluster"])){cluster();exit;}
if(isset($_GET["cluster2"])){cluster2();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["tab"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["RestartOpenLDAP"])){save();exit;}
if(isset($_POST["LokiListenInterface"])){save();exit;}
if(isset($_POST["EnableMultipleOrganizations"])){save();exit;}
if(isset($_POST["LdapDBCachesize"])){save();exit;}


if(isset($_GET["suffix-js"])){suffix_js();exit;}
if(isset($_GET["suffix-popup"])){suffix_popup();exit;}
if(isset($_GET["join-js"])){join_js();exit;}
if(isset($_GET["join-popup"])){join_popup();exit;}
if(isset($_POST["ChangeLDAPSuffixFrom"])){suffix_save();exit;}
if(isset($_POST["REMOTE_ARTICA_SERVER"])){join_save();exit;}
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
function join_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{join_a_cluster}","$page?join-popup=yes");
}

function join_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $WizardLdapCluster=serialize($_POST);
    admin_tracks("Run wizard to join an OpenLDAP Master {$_POST["REMOTE_ARTICA_SERVER"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardLdapCluster",$WizardLdapCluster);
}

function join_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $btname="{connect}";
    $title="{connect}";

    $WizardLdapCluster=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardLdapCluster"));
    $REMOTE_ARTICA_SERVER=$WizardLdapCluster["REMOTE_ARTICA_SERVER"];
    $REMOTE_ARTICA_SERVER_PORT=intval($WizardLdapCluster["REMOTE_ARTICA_SERVER_PORT"]);
    $REMOTE_ARTICA_USERNAME=trim($WizardLdapCluster["REMOTE_ARTICA_USERNAME"]);
    $REMOTE_ARTICA_PASSWORD=trim($WizardLdapCluster["REMOTE_ARTICA_PASSWORD"]);
    if($REMOTE_ARTICA_SERVER_PORT==0){$REMOTE_ARTICA_SERVER_PORT=9000;}
    if($REMOTE_ARTICA_USERNAME==null){$REMOTE_ARTICA_USERNAME="Manager";}
    $form[]= $tpl->field_text("REMOTE_ARTICA_SERVER","{REMOTE_ARTICA_SERVER}",$REMOTE_ARTICA_SERVER,true);
    $form[]= $tpl->field_numeric("REMOTE_ARTICA_SERVER_PORT","{REMOTE_ARTICA_SERVER_PORT}",$REMOTE_ARTICA_SERVER_PORT,true);
    $form[]= $tpl->field_text("REMOTE_ARTICA_USERNAME","{REMOTE_ARTICA_USERNAME}",$REMOTE_ARTICA_USERNAME,true);
    $form[]= $tpl->field_password("REMOTE_ARTICA_PASSWORD","{REMOTE_ARTICA_PASSWORD}",$REMOTE_ARTICA_PASSWORD,true);

    $applyjs=$tpl->framework_buildjs(
        "openldap.php?join=yes",
        "openldap.join.prog",
        "openldap.join.log",
        "progress-loki-restart",
        "LoadAjaxSilent('cluster-parameters','$page?cluster2=yes');"

    );

    $html[]=$tpl->form_outside($title, @implode("\n", $form),"{wizard_com_artica_api}",$btname,
        "dialogInstance2.close();$applyjs",
        "AsSquidAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);

// wizard_com_artica_api
}

function suffix_js(){
    $id=$_GET["id"];
    $value=$_GET["value"];
    $valuencode=urlencode($value);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{ldap_suffix}","$page?suffix-popup=$valuencode&id=$id");
}

function suffix_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $id=$_GET["id"];
    $value=$_GET["suffix-popup"];

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.chsuffix.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.chsuffix.progress.txt";
    $ARRAY["CMD"]="openldap.php?change-suffix=yes";
    $ARRAY["TITLE"]="{suffix}";
    $ARRAY["AFTER"]="window.location.href ='logoff.php';";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-openldap-suffix')";

    $tpl->field_hidden("ChangeLDAPSuffixFrom",$value);
    $html[]="<div id='progress-openldap-suffix'></div>";
    $form[]=$tpl->field_text("ChangeLDAPSuffixTo","{ldap_suffix}",$value,true);
    $html[]=$tpl->form_outside("{modify}: {ldap_suffix}", @implode("\n", $form),
        "{openldap_change_suffix_warning}","{modify}",$jsrestart,$security);

    echo $tpl->_ENGINE_parse_body($html);
}

function suffix_save(){
    $sock=new sockets();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    
    if(strtolower($_POST["ChangeLDAPSuffixTo"]) == strtolower($_POST["ChangeLDAPSuffixFrom"]) ){
        echo $tpl->post_error("{no_change}");
        exit;
    }
    admin_tracks("Change LDAP Database suffix from {$_POST["ChangeLDAPSuffixFrom"]} to {$_POST["ChangeLDAPSuffixTo"]}");
    $sock->SaveConfigFile(base64_encode($_POST["ChangeLDAPSuffixTo"]),"ChangeLDAPSuffixTo");
    $sock->SaveConfigFile(base64_encode($_POST["ChangeLDAPSuffixFrom"]),"ChangeLDAPSuffixFrom");
}

function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_LOKI}","fas fa-database","{APP_LOKI_EXPLAIN}",
        "$page?tab=yes","loki-database","progress-loki-restart",false,"table-loader-openldap-service");

    $tpl=new template_admin("{APP_LOKI}",$html);

	if(isset($_GET["main-page"])){
		echo $tpl->build_firewall();
		return;
	}
	


	echo $tpl->_ENGINE_parse_body($html);

}
function cluster_save(){

    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $EnableLDAPSyncProv=intval($_POST["EnableLDAPSyncProv"]);
    if($EnableLDAPSyncProv==0){
        admin_tracks("Set LDAP Server Cluster replication to OFF");
        return true;
    }
    if($EnableLDAPSyncProv==1){
        admin_tracks("Set LDAP Server Cluster replication to ON Client mode={$_POST["EnableLDAPSyncProvClient"]}");
    }
    return true;

}
function cluster(){
    $page=CurrentPageName();
    echo "<div id='cluster-parameters'></div>
    <script>LoadAjax('cluster-parameters','$page?cluster2=yes');</script>";
}
function cluster2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";

    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");

    $SyncProvUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncProvUserDN");
    $LDAPSyncProvClientServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientServer");
    $LDAPSyncProvClientSearchBase=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientSearchBase");
    $LDAPSyncProvClientBindDN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindDN");
    $LDAPSyncProvClientBindPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindPassword");

    $LDAPSyncProvID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvID"));
    if($LDAPSyncProvID==0){$LDAPSyncProvID=1;}
    if($LDAPSyncProvID>999){$LDAPSyncProvID=999;}

    $title_add=null;

    if($EnableLDAPSyncProv==0){$title_add=" ({disabled})";}
    if($EnableLDAPSyncProv==1){
        $title_add=" ({master_mode})";
        if($EnableLDAPSyncProvClient==1){
            $title_add=" ({client_mode})";
        }else{
            $LokiListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LokiListenInterface"));
            if($LokiListenInterface==null){$LokiListenInterface="lo";}
            if($LokiListenInterface=="lo"){
                echo $tpl->div_error("{error_server_listen_loopback}");
            }
        }
    }


    $form[]=$tpl->field_checkbox("EnableLDAPSyncProv","{enable_ldap_sync_service}",$EnableLDAPSyncProv,true);
    $form[]=$tpl->field_numeric("LDAPSyncProvID","{uuid}",$LDAPSyncProvID);
    $form[]=$tpl->field_section("{server_mode}");
    $form[]=$tpl->field_browse_ldap_user("SyncProvUserDN","{sync_user}",$SyncProvUser);

    $form[]=$tpl->field_section("{client_mode}");
    $form[]=$tpl->field_checkbox("EnableLDAPSyncProvClient","{enable_client_mode}",$EnableLDAPSyncProvClient,"LDAPSyncProvClientServer,LDAPSyncProvClientSearchBase,LDAPSyncProvClientBindDN,LDAPSyncProvClientBindPassword");
    $form[]=$tpl->field_text("LDAPSyncProvClientServer","{ldap_server}",$LDAPSyncProvClientServer);
    $form[]=$tpl->field_text("LDAPSyncProvClientSearchBase","{searchbasedn}",$LDAPSyncProvClientSearchBase);
    $form[]=$tpl->field_text("LDAPSyncProvClientBindDN","{ldap_user_dn}",$LDAPSyncProvClientBindDN);
    $form[]=$tpl->field_password("LDAPSyncProvClientBindPassword","{ldap_password}",$LDAPSyncProvClientBindPassword);


    $jsrestart=$tpl->framework_buildjs(
        "/grafana/restart",
        "loki.progress",
        "loki.progress.log",
        "progress-loki-restart",
        "LoadAjaxSilent('cluster-parameters','$page?cluster2=yes');"
    );

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'><div id='cluster-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align: top'>";
    $html[]=$tpl->form_outside("{LDAP} {cluster}", @implode("\n", $form),null,"{apply}",$jsrestart,$security);

    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?join-js=yes');\"><i class='fas fa-sign-in'></i> {join_a_cluster} </label>";
    $bts[]="</div>";


    $TINY_ARRAY["TITLE"]="{APP_LOKI}:{cluster}$title_add";
    $TINY_ARRAY["ICO"]="fad fa-exchange";
    $TINY_ARRAY["EXPL"]="{APP_LOKI_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('cluster-status','$page?status=yes');";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?table=yes";
    $array["{cluster}"]="$page?cluster=yes";
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
    $TINY_ARRAY["ICO"] = "fas fa-database";
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
