<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["filebeat-status"])){status();exit;}
if(isset($_POST["ElasticSearchAddress"])){Save();exit;}
if(isset($_POST["ElasticsearchEnableAuthFilebeat"])){Save();exit;}

if(isset($_GET["srv-addr-js"])){srv_addr_js();exit;}
if(isset($_GET["srv-addr-popup"])){srv_addr_popup();exit;}



page();

function page(){
    $page=CurrentPageName();
    $Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_VERSION");
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_FILEBEAT} v$Version","fas fa-file-medical-alt",
        "{APP_FILEBEAT_EXPLAIN}","$page?start=yes","filebeat-status","progress-filebeat-restart",
        false,"table-filebeat-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_OPENSSH}",$html);
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
    $html[]="<td style='width:240px'><div id='filebeat-status'></div></td>";
    $html[]="<td style='width:240px'><div id='flat-config'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("filebeat-status",$page,"service-status=yes");
    $html[]="LoadAjax('flat-config','$page?flat-config=yes');";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}
function status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/filebeat/status"));
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $jsRestart=$tpl->framework_buildjs("/filebeat/restart","filebeat.restart.progress",
        "filebeat.restart.log","progress-filebeat-restart");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/filebeat/stats"),true);

    $eventsSent="";
    $queueInfo="";
    $queueMaxEvents=0;
    $queueActiveEvents=0;
    $queueDroppedEvents=0;
    $queueFailedEvents=0;
    $queueRetryEvents=0;
    $queueAckedEvents=0;

    if (!empty($json["Info"])) {
        $info = json_decode($json["Info"], true); // decode as associative array

        if (isset($info["filebeat"])) {
            $events_sent = $info["filebeat"]["events"]["done"];
            $eventsSent = $tpl->widget_h(
                "green",
                "fas fa-satellite-dish",
                FormatNumber($events_sent),
                "{sent_events}"
            );
            if(isset($info["libbeat"]["pipeline"]["queue"])){
                if(isset($info["libbeat"]["pipeline"]["queue"]["max_events"])) {
                    $queueMaxEvents = intval($info["libbeat"]["pipeline"]["queue"]["max_events"]);
                }
                if(isset($info["libbeat"]["pipeline"]["queue"]["acked"])) {
                    $queueAckedEvents = intval($info["libbeat"]["pipeline"]["queue"]["acked"]);
                }
                if(isset($info["libbeat"]["pipeline"]["queue"]["active"])) {
                    $queueActiveEvents = intval($info["libbeat"]["pipeline"]["queue"]["active"]);
                }
                if(isset($info["libbeat"]["pipeline"]["queue"]["retry"])) {
                    $queueRetryEvents = intval($info["libbeat"]["pipeline"]["queue"]["retry"]);
                }
                if(isset($info["libbeat"]["pipeline"]["queue"]["failed"])) {
                    $queueFailedEvents = intval($info["libbeat"]["pipeline"]["queue"]["failed"]);
                }
                if(isset($info["libbeat"]["pipeline"]["queue"]["dropped"])) {
                    $queueDroppedEvents = intval($info["libbeat"]["pipeline"]["queue"]["dropped"]);
                }

                $queueInfo = $tpl->widget_h(
                    "grey",
                    "fas fa-traffic-light",
                    "<span class=\"label label-success \">{maximum} {events} {in} {queue} $queueMaxEvents</span><br><span class=\"label label-success \">{acked} {events} $queueAckedEvents</span><br><span class=\"label label-info \">{active} {events} $queueActiveEvents</span><br><span class=\"label label-warning \">{retry} {events} $queueRetryEvents</span><br><span class=\"label label-danger \">{drop} {events} $queueDroppedEvents</span><br><span class=\"label label-danger \">{failed} {events} $queueFailedEvents</span>",
                    "{queue}"
                );
            }

        }
    }

    echo $tpl->SERVICE_STATUS($bsini, "APP_FILEBEAT",$jsRestart) ."$eventsSent<br>$queueInfo";

}
function srv_addr_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{elasticsearch_address}", "$page?srv-addr-popup=yes");
}

function srv_addr_popup():bool{
    $UseSSL=1;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ElasticSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress"));
    $ElasticsearchRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchRemotePort"));
    $ElasticSearchProtocol=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchProtocol"));
    if(empty($ElasticSearchProtocol)){$ElasticSearchProtocol='http';}
    if($ElasticsearchRemotePort==0){$ElasticsearchRemotePort=9200;}
    if($ElasticSearchProtocol=="http"){
        $UseSSL=0;
    }
    $form[]=$tpl->field_ipv4("ElasticSearchAddress","{elasticsearch_address}",$ElasticSearchAddress,true);
    $form[]=$tpl->field_numeric("ElasticSearchRemotePort","{elasticsearch_remote_port}",$ElasticsearchRemotePort);
    $form[]=$tpl->field_checkbox("UseSSL", "{UseSSL}", $UseSSL);
    echo $tpl->form_outside("",$form,null,
        "{apply}",
        "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');",
        "AsWebStatisticsAdministrator",true);
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


function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsReload=$tpl->framework_buildjs("/filebeat/restart","filebeat.restart.progress","filebeat.restart.log","progress-filebeat-restart","LoadAjax('table-filebeat-status','$page?table=yes');");

    if(!is_file("/etc/artica-postfix/elasticsearch_remote_configured")){
        $html[]=$tpl->div_error("{filebeat_elasticsearch_not_configured}");
    }
    $ElasticSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress"));
    $ElasticsearchRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchRemotePort"));
    $ElasticSearchProtocol=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchProtocol"));
    if($ElasticsearchRemotePort==0){$ElasticsearchRemotePort=9200;}
    $ElasticsearchEnableAuthFilebeat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchEnableAuthFilebeat"));
    $ElasticSearchUsernameFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchUsernameFilebeat"));
    $ElasticSearchPasswordFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchPasswordFilebeat"));


    $tpl->table_form_field_js("Loadjs('$page?srv-addr-js=yes')","AsSystemAdministrator");
    if(strlen($ElasticSearchAddress)<3){
        $tpl->table_form_field_bool("{elasticsearch_address}",0,ico_server);
    }else{
        $tpl->table_form_field_text("{elasticsearch_address}","$ElasticSearchProtocol://$ElasticSearchAddress:$ElasticsearchRemotePort",ico_server);
    }
    $tpl->table_form_field_js("Loadjs('$page?auth-js=yes')","AsSystemAdministrator");
    if($ElasticsearchEnableAuthFilebeat==0){
        $tpl->table_form_field_bool("{authentication}",0,ico_user);
    }else{
        $tpl->table_form_field_text("{authentication}",$ElasticSearchUsernameFilebeat,ico_user);
    }



    //Queue Type
    $FilebeatEnableDiskQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatEnableDiskQueue"));
    //Mem Queue
    $FilebeatMemMaxEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatMemMaxEvents"));
    if($FilebeatMemMaxEvents==0){$FilebeatMemMaxEvents=3200;}
    $FilebeatMemFlushMinEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatMemFlushMinEvents"));
    if($FilebeatMemFlushMinEvents==0){$FilebeatMemFlushMinEvents=1600;}
    $FilebeatMemFlushTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatMemFlushTimeout"));
    if($FilebeatMemFlushTimeout==0){$FilebeatMemFlushTimeout=10;}

    //Disk Queue
    $FilebeatDiskMaxSize=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskMaxSize"));
    if(strlen($FilebeatDiskMaxSize)==0){$FilebeatDiskMaxSize="10GB";}

    $FilebeatDiskReadAhead=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskReadAhead"));
    if($FilebeatDiskReadAhead==0){$FilebeatDiskReadAhead=512;}
    $FilebeatDiskWriteAhead=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskWriteAhead"));
    if($FilebeatDiskWriteAhead==0){$FilebeatDiskWriteAhead=2048;}
    $FilebeatDiskRetryInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskRetryInterval"));
    if($FilebeatDiskRetryInterval==0){$FilebeatDiskRetryInterval=1;}
    $FilebeatDiskMaxRetryInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatDiskMaxRetryInterval"));
    if($FilebeatDiskMaxRetryInterval==0){$FilebeatDiskMaxRetryInterval=30;}
    $DiskSize["512MiB"]="512MiB";
    for ($i = 1; $i <= 20; $i++) {
        $DiskSize["{$i}GB"]="{$i}GB";
    }
    $FilebeatIndexIsILM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatIndexIsILM"));
    $FilebeatTemplateOverwrite=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FilebeatTemplateOverwrite"));






    $form[]=$tpl->field_checkbox("FilebeatIndexIsILM","{index} {is} ILM",$FilebeatIndexIsILM);
    $form[]=$tpl->field_checkbox("FilebeatTemplateOverwrite","{template} {overwrite}",$FilebeatTemplateOverwrite);

    $form[]=$tpl->field_section("{queue}");
    //mem
    $form[]=$tpl->field_numeric("FilebeatMemMaxEvents","{maximum} {events}",$FilebeatMemMaxEvents);
    $form[]=$tpl->field_numeric("FilebeatMemFlushMinEvents","{flush} {minimum} {events}",$FilebeatMemFlushMinEvents);
    $form[]=$tpl->field_numeric("FilebeatMemFlushTimeout","{flush} {timeout}",$FilebeatMemFlushTimeout);
    //disk
    $form[]=$tpl->field_checkbox("FilebeatEnableDiskQueue","{enable} {disk} {queue}",$FilebeatEnableDiskQueue,"FilebeatDiskMaxSize,FilebeatDiskReadAhead,FilebeatDiskWriteAhead,FilebeatDiskRetryInterval");
    $form[]=$tpl->field_array_hash($DiskSize, "FilebeatDiskMaxSize", "nonull:{size}", $FilebeatDiskMaxSize);
    $form[]=$tpl->field_numeric("FilebeatDiskReadAhead","{read} {ahead}",$FilebeatDiskReadAhead);
    $form[]=$tpl->field_numeric("FilebeatDiskWriteAhead","{write} {ahead}",$FilebeatDiskWriteAhead);
    $form[]=$tpl->field_numeric("FilebeatDiskRetryInterval","{retry} {interval}",$FilebeatDiskRetryInterval);
    $form[]=$tpl->field_numeric("FilebeatDiskMaxRetryInterval","{max} {retry} {interval}",$FilebeatDiskMaxRetryInterval);

    $formula=$tpl->form_outside("{APP_ELASTICSEARCH}",$form,null,"{apply}",$jsReload,"AsWebStatisticsAdministrator",true);

    $html[]=$formula;

    $html[]="</td>";
    $html[]="</tr>";

    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

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

    $ElasticSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchAddress"));
    $ElasticsearchRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchRemotePort"));
    $ElasticSearchProtocol=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchProtocol"));
    if(empty($ElasticSearchProtocol)){$ElasticSearchProtocol='http';}
    if($ElasticsearchRemotePort==0){$ElasticsearchRemotePort=9200;}

    $ElasticsearchEnableAuthFilebeat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchEnableAuthFilebeat"));
    $ElasticSearchUsernameFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchUsernameFilebeat"));
    $ElasticSearchPasswordFilebeat=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchPasswordFilebeat"));

    $ch = curl_init();
    $method = "GET";
    $url = "$ElasticSearchProtocol://$ElasticSearchAddress:$ElasticsearchRemotePort/_cluster/stats?human&pretty";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    if($ElasticsearchEnableAuthFilebeat==1) {
        curl_setopt($ch, CURLOPT_USERPWD, "$ElasticSearchUsernameFilebeat:$ElasticSearchPasswordFilebeat");
    }
    $result = curl_exec($ch);

    if ($result === false) {
        $Error=curl_error($ch);
        echo "jserror:return network error code $Error";
        if(function_exists("curl_close")) {
            curl_close($ch);
        }
        return false;
    }

    $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($responseCode >= 400) {
        echo "jserror:return HTTP error code $responseCode";
        if(function_exists("curl_close")) {
            curl_close($ch);
        }
        return false;
    }

    return admin_tracks_post("Saving Filebeat parameters");

}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
    $tmp1 = round((float) $number, $decimals);
    while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
        $tmp1 = $tmp2;
    return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}