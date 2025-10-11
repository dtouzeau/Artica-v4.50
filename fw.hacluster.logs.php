<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HaClusterRemoteSyslogEnabled"])){Save();exit;}

if(isset($_GET["popup"])){table();exit;}

js();


function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{events}/{log_retention}","$page?popup=yes",650);
}

function Save():bool{

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/reconfigure");
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks("Save log retentions and syslog HaCluster feature");
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $HaClusterRemoveLogsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoveLogsEnabled"));
    $HaClusterRemoveLogsSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoveLogsSize"));
    $HaClusterRemoteSyslogEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteSyslogEnabled"));
    $HaClusterRemoteUseTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteUseTCPPort"));
    $HaClusterRemoteSyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteSyslogPort"));
    $HaClusterRemoteSyslogAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterRemoteSyslogAddr"));
    if($HaClusterRemoteSyslogPort==0){$HaClusterRemoteSyslogPort=514;}
    if($HaClusterRemoveLogsSize==0){$HaClusterRemoveLogsSize=500;}

    $form[]=$tpl->field_checkbox("HaClusterRemoveLogsEnabled","{remove_local_access_events}",$HaClusterRemoveLogsEnabled);


    $f[]="LoadAjaxSilent('hacluster-parameters','fw.hacluster.status.php?parameters-table=yes');";
    $f[]="dialogInstance2.close()";

    $form[]=$tpl->field_numeric("HaClusterRemoveLogsSize","{ArticaMaxLogsSize} (MB)",$HaClusterRemoveLogsSize,"{ArticaMaxLogsSize_text}");
    $form[]=$tpl->field_section("{squid_syslog_text}");
    $form[]=$tpl->field_checkbox("HaClusterRemoteSyslogEnabled","{enabled}",$HaClusterRemoteSyslogEnabled,"HaClusterRemoteUseTCPPort,HaClusterRemoteSyslogAddr,HaClusterRemoteSyslogPort");
    $form[]=$tpl->field_checkbox("HaClusterRemoteUseTCPPort","{useTCPPort}",$HaClusterRemoteUseTCPPort,false);
    $form[]=$tpl->field_text("HaClusterRemoteSyslogAddr","{remote_syslog_server}",$HaClusterRemoteSyslogAddr);
    $form[]=$tpl->field_numeric("HaClusterRemoteSyslogPort","{listen_port}",$HaClusterRemoteSyslogPort);

    $html[]=$tpl->form_outside(null,$form,null,"{apply}",@implode(";",$f),"AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);

}