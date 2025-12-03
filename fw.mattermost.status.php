<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["flat-config"])){table();exit;}
if(isset($_GET["service-status"])){status();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["mattermost-status"])){status();exit;}
if(isset($_POST["ElasticSearchAddress"])){Save();exit;}
if(isset($_POST["ElasticsearchEnableAuthFilebeat"])){Save();exit;}
if(isset($_POST["FilebeatEnableDiskQueue"])){Save();exit;}
if(isset($_POST["FilebeatIndexIsILM"])){Save();exit;}
if(isset($_POST["FilebeatTemplateOverwrite"])){Save();exit;}

if(isset($_GET["auth-js"])){auth_js();exit;}
if(isset($_GET["auth-popup"])){auth_popup();exit;}

if(isset($_GET["srv-addr-js"])){srv_addr_js();exit;}
if(isset($_GET["srv-addr-popup"])){srv_addr_popup();exit;}

if(isset($_GET["disk-queue-js"])){disk_queue_js();exit;}
if(isset($_GET["disk-queue-popup"])){disk_queue_popup();exit;}

if(isset($_GET["disk-memory-js"])){disk_memory_js();exit;}
if(isset($_GET["disk-memory-popup"])){disk_memory_popup();exit;}

if(isset($_GET["disk-index-js"])){disk_index_js();exit;}
if(isset($_GET["disk-index-popup"])){disk_index_popup();exit;}

if(isset($_GET["disk-template-js"])){disk_template_js();exit;}
if(isset($_GET["disk-template-popup"])){disk_template_popup();exit;}

page();

function page(){
    $page=CurrentPageName();
    $Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MATTERMOST_VERSION");
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_MATTERMOST} v$Version","mattermostTitle",
        "{APP_MATTERMOST_EXPLAIN}","$page?start=yes","mattermost-status",
        "progress-mattermost-restart",
        false,"table-mattermost-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_MATTERMOST}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'><div id='mattermost-status'></div></td>";
    $html[]="<td style='width:99%;;vertical-align: top'><div id='flat-config'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("mattermost-status",$page,"service-status=yes");
    $html[]="LoadAjax('flat-config','$page?flat-config=yes');";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}
function status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mattermost/status"));
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $jsRestart=$tpl->framework_buildjs("/mattermost/restart",
        "mattermost.restart",
        "mattermost.restart.log","progress-mattermost-restart");

    $jsRestartPostgreSQL=$tpl->framework_buildjs("/mattermost/postgres/restart",
        "mattermost.pg.restart",
        "mattermost.pg.restart?log","progress-mattermost-restart");



    $postgres=$tpl->SERVICE_STATUS($bsini,
        "APP_MATTERMOST_POSTGRES",$jsRestartPostgreSQL);

    echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($bsini,
            "APP_MATTERMOST",$jsRestart) ."<br>$postgres");
    return true;
}
function srv_addr_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{elasticsearch_address}", "$page?srv-addr-popup=yes");
}
function disk_queue_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{queue}", "$page?disk-queue-popup=yes",900);
}
function disk_memory_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{queue}", "$page?disk-memory-popup=yes",900);
}
function auth_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{authentication}", "$page?auth-popup=yes",650);
}
function disk_index_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{index}", "$page?disk-index-popup=yes",650);
}
function disk_template_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{template}", "$page?disk-template-popup=yes",650);
}

function disk_index_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $FilebeatIndexIsILM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatIndexIsILM"));

    echo $tpl->BigCircleCheckbox("FilebeatIndexIsILM",
    "{index}",
    "{fbeat_idx_expl}",
    $FilebeatIndexIsILM,
    "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');");
    return true;
}
function disk_template_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $FilebeatTemplateOverwrite=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatTemplateOverwrite"));

    echo $tpl->BigCircleCheckbox("FilebeatTemplateOverwrite",
        "{overwrite_template}",
        "{filbeat_template_overwrite}",
        $FilebeatTemplateOverwrite,
        "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');");
    return true;
}

function srv_addr_popup():bool{
    $UseSSL=1;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ElasticSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress"));
    $ElasticSearchAddress1=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress1"));
    $ElasticSearchAddress2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress2"));


    $FileBeatSSLVerificationMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FileBeatSSLVerificationMode"));

    $FileBeatSSLVerificationModes[0]="{full2}";
    $FileBeatSSLVerificationModes[1]="{certificate}";
    $FileBeatSSLVerificationModes[2]="{none}";


    $ElasticsearchRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchRemotePort"));
    $ElasticSearchProtocol=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchProtocol"));
    if(empty($ElasticSearchProtocol)){$ElasticSearchProtocol='http';}
    if($ElasticsearchRemotePort==0){$ElasticsearchRemotePort=9200;}
    if($ElasticSearchProtocol=="http"){
        $UseSSL=0;
    }
    $form[]=$tpl->field_ipv4("ElasticSearchAddress","{elasticsearch_address}",$ElasticSearchAddress,true);
    $form[]=$tpl->field_ipv4("ElasticSearchAddress1","{elasticsearch_address} 2",$ElasticSearchAddress1);
    $form[]=$tpl->field_ipv4("ElasticSearchAddress2","{elasticsearch_address} 3",$ElasticSearchAddress2);


    $form[]=$tpl->field_numeric("ElasticSearchRemotePort","{elasticsearch_remote_port}",$ElasticsearchRemotePort);
    $form[]=$tpl->field_checkbox("UseSSL", "{UseSSL}", $UseSSL);

    $form[]=$tpl->field_array_hash($FileBeatSSLVerificationModes, "FileBeatSSLVerificationMode", "nonull:{ssl.verification_mode}", $FileBeatSSLVerificationMode);

    echo $tpl->form_outside("",$form,null,
        "{apply}",
        "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');",
        "AsWebStatisticsAdministrator");
    return true;
}
function disk_queue_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $FilebeatEnableDiskQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatEnableDiskQueue"));
    $FilebeatDiskMaxSize=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskMaxSize"));
    if(strlen($FilebeatDiskMaxSize)==0){$FilebeatDiskMaxSize="10GB";}
    $DiskSize["512MiB"]="512MiB";
    for ($i = 1; $i <= 20; $i++) {
        $DiskSize["{$i}GB"]="{$i}GB";
    }
    $FilebeatDiskReadAhead=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskReadAhead"));
    if($FilebeatDiskReadAhead==0){$FilebeatDiskReadAhead=512;}
    $FilebeatDiskWriteAhead=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskWriteAhead"));
    if($FilebeatDiskWriteAhead==0){$FilebeatDiskWriteAhead=2048;}
    $FilebeatDiskRetryInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskRetryInterval"));
    if($FilebeatDiskRetryInterval==0){$FilebeatDiskRetryInterval=1;}
    $FilebeatDiskMaxRetryInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskMaxRetryInterval"));
    if($FilebeatDiskMaxRetryInterval==0){$FilebeatDiskMaxRetryInterval=30;}

    $form[]=$tpl->field_checkbox("FilebeatEnableDiskQueue","{store_queue_ondisk}",$FilebeatEnableDiskQueue,"FilebeatDiskMaxSize,FilebeatDiskReadAhead,FilebeatDiskWriteAhead,FilebeatDiskRetryInterval");
    $form[]=$tpl->field_array_hash($DiskSize, "FilebeatDiskMaxSize", "nonull:{size}", $FilebeatDiskMaxSize);

    $form[]=$tpl->field_numeric("FilebeatDiskReadAhead","{read_ahead} ({events})",$FilebeatDiskReadAhead);
    $form[]=$tpl->field_numeric("FilebeatDiskWriteAhead","{write_ahead} ({events})",$FilebeatDiskWriteAhead);
    $form[]=$tpl->field_numeric("FilebeatDiskRetryInterval","{retry_interval} ({seconds})",$FilebeatDiskRetryInterval);
    $form[]=$tpl->field_numeric("FilebeatDiskMaxRetryInterval","{maxretries} ({seconds})",$FilebeatDiskMaxRetryInterval);


    echo $tpl->form_outside("",$form,null,
        "{apply}",
        "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');",
        "AsWebStatisticsAdministrator");
    return true;
}
function disk_memory_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $FilebeatEnableDiskQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatEnableDiskQueue"));

    $FilebeatMemMaxEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatMemMaxEvents"));
    if($FilebeatMemMaxEvents==0){$FilebeatMemMaxEvents=3200;}
    $FilebeatMemFlushMinEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatMemFlushMinEvents"));
    if($FilebeatMemFlushMinEvents==0){$FilebeatMemFlushMinEvents=1600;}
    $FilebeatMemFlushTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatMemFlushTimeout"));
    if($FilebeatMemFlushTimeout==0){$FilebeatMemFlushTimeout=10;}

    $form[]=$tpl->field_checkbox("FilebeatEnableDiskQueue","{store_queue_ondisk}",$FilebeatEnableDiskQueue);

    $form[]=$tpl->field_numeric("FilebeatMemMaxEvents","{max_records_in_memory}",$FilebeatMemMaxEvents);
    $form[]=$tpl->field_numeric("FilebeatMemFlushMinEvents","{flush_minimum_events}",$FilebeatMemFlushMinEvents);
    $form[]=$tpl->field_numeric("FilebeatMemFlushTimeout","{flush_timeout} ({seconds})",$FilebeatMemFlushTimeout);

    echo $tpl->form_outside("",$form,null,
        "{apply}",
        "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');",
        "AsWebStatisticsAdministrator");
    return true;
}

function auth_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ElasticsearchEnableAuthFilebeat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchEnableAuthFilebeat"));
    $ElasticSearchUsernameFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchUsernameFilebeat"));
    $ElasticSearchPasswordFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchPasswordFilebeat"));

    $form[]=$tpl->field_checkbox("ElasticsearchEnableAuthFilebeat","{authentication}",$ElasticsearchEnableAuthFilebeat,"ElasticSearchUsernameFilebeat,ElasticSearchPasswordFilebeat");
    $form[]=$tpl->field_text("ElasticSearchUsernameFilebeat", "{username}", $ElasticSearchUsernameFilebeat);
    $form[]=$tpl->field_password("ElasticSearchPasswordFilebeat", "{password}", $ElasticSearchPasswordFilebeat);

    echo $tpl->form_outside("",$form,null,
        "{apply}",
        "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');",
        "AsWebStatisticsAdministrator",true);
    return true;
}


function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsReload=$tpl->framework_buildjs("/filebeat/restart","filebeat.restart.progress","filebeat.restart.log","progress-mattermost-restart","LoadAjax('table-mattermost-status','$page?table=yes');");

    if(!is_file("/etc/artica-postfix/elasticsearch_remote_configured")){
        $html[]=$tpl->div_error("{filebeat_elasticsearch_not_configured}");
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mattermost/stats"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    $tpl->table_form_field_text("{members}",$json->stats->users_count,ico_users);
    $tpl->table_form_field_text("{members} ({daily})",$json->stats->active_users_count_daily,ico_users);
    $tpl->table_form_field_text("{members} ({monthly})",$json->stats->active_users_count_monthly,ico_users);
    $tpl->table_form_field_text("{sessions}",$json->stats->sessions_count,ico_nic);
    $tpl->table_form_field_text("{posts_count}",$json->stats->posts_count,ico_message);
    $tpl->table_form_field_text("{teams_count}",$json->stats->teams_count,ico_group);

    $megabytes_count=$json->stats->megabytes_count;
    $megabytes_countKB=$megabytes_count*1024;
    $megabytes_text=FormatBytes($megabytes_countKB);
    $tpl->table_form_field_text("{files}",$json->stats->files_count. " ($megabytes_text)",ico_file);

    $text=$json->stats->incoming_webhooks_count."/".$json->stats->outgoing_webhooks_count;
    $tpl->table_form_field_text("{bots_count}",$json->stats->bots_count,ico_file);
    $tpl->table_form_field_text("WebHooks",$text,ico_link);
    $tpl->table_form_field_text("{commands_count}",$json->stats->commands_count,ico_computer_ssh);

    echo $tpl->table_form_compile();

return true;
}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    if(isset($_POST["UseSSL"])){
        $_POST["ElasticSearchProtocol"]="http";
        if(intval($_POST["UseSSL"])==1){
            $_POST["ElasticSearchProtocol"]="https";
        }
        unset($_POST["UseSSL"]);
    }



   if(isset($_POST["ElasticsearchEnableAuthFilebeat"])) {
       $ElasticSearchUsernameFilebeat = $_POST["ElasticSearchUsernameFilebeat"];
       $ElasticSearchPasswordFilebeat = $_POST["ElasticSearchPasswordFilebeat"];

       if ($_POST["ElasticsearchEnableAuthFilebeat"] == 1) {
           if (empty($ElasticSearchUsernameFilebeat)) {
               echo "jserror:Username is mandatory";
               return false;
           }
           if (empty($ElasticSearchPasswordFilebeat)) {
               echo "jserror:Password is mandatory";
               return false;
           }
       }
   }
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/filebeat/restart");
    return admin_tracks_post("Saving Filebeat parameters");

}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
    $tmp1 = round((float) $number, $decimals);
    while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
        $tmp1 = $tmp2;
    return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}