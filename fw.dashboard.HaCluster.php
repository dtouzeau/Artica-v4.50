<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(isset($_POST["HaClusterID"])){save();exit;}
if(isset($_GET["page"])){page();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["purge-progress"])){purge_popup_js();exit;}
if(isset($_GET["purge-popup"])){purge_popup();exit;}
if(isset($_GET["flat"])){haclient_flat();exit;}
if(isset($_GET["status"])){Status();exit;}
if(isset($_GET["form"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["action"])){action();exit;}
if(isset($_GET["hacluster-server-ping"])){hacluster_server_ping();exit;}
start();

function purge(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{squid_purge_dns_explain}","none","none","Loadjs('$page?purge-progress=yes')");
}
function purge_popup_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{empty_cache}","$page?purge-popup=yes");
}

function action():bool{
    $action=intval($_GET["action"]);

    if($action==0){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/stop");
        return admin_tracks("Stopping the HaCLuster Client status");

    }
    if($action==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/start");
        return admin_tracks("Starting the HaCLuster Client status");
    }
    return true;
}

function form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return  $tpl->js_dialog1("{parameters}","$page?form-popup=yes");
}
function purge_popup(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
    $ARRAY["CMD"]="dnsfilterd.php?purge=yes";
    $ARRAY["TITLE"]="{empty_cache}";
    $ARRAY["AFTER"]="dialogInstance1s.close();LoadAjax('dashboard-dnsfilterd','$page?page=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$t')";
    $html="<div id='$t'></div><script>$jsrestart</script>";
    echo $html;
}

function start(){
    $page=CurrentPageName();
    $html="<div id='dashboard-hacluster'></div>
<script>LoadAjax('dashboard-hacluster','$page?page=yes');</script>";

    echo $html;
}
function page(){

    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table style='width:100%;margin-top:15px'><tr>
        <td style='width:350px;vertical-align:top'>
            <div id='hacluster-client-status'></div>    
            <div id='package-cluster-status'></div>
       </td>
        <td style='width:99%;vertical-align:top'>
         <div id='hacluster-server-ping'></div>
         <div id='hacluster-client-flat'></div>
        </td>
        </tr></table>";
///hacluster/client/server/ping
    $js1=$tpl->RefreshInterval_js("hacluster-client-status",$page,"status=yes");
    $js3=$tpl->RefreshInterval_js("hacluster-client-flat",$page,"flat=yes");
    $js2=$tpl->RefreshInterval_js("package-cluster-status","fw.system.cluster.client.php","status=yes");

    $html[]="<script>";
    $html[]=$js1;
    $html[]=$js2;
    $html[]=$js3;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function Status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $html[]="<div id='haclient-restart'></div>";
    $html[]=HaClusterClientStatus();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function HaClusterClientStatus():string{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsrestart=$tpl->framework_buildjs("/hacluster/client/restart",
        "haclient.progress",
        "haclient.progress.txt",
        "haclient-restart",
        "LoadAjax('hacluster-client-status','$page?status=yes');"
    );

    $remove=$tpl->framework_buildjs("/hacluster/client/uninstall",
        "haclient.progress",
        "haclient.progress.txt",
        "haclient-restart",
        "LoadAjax('hacluster-client-status','$page?status=yes');"
    );

    $removedb=$tpl->button_autnonome("{disconnect}",$remove,"fas fa-trash","AsSystemAdministrator",250,"btn-danger");
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==0){
        return $tpl->_ENGINE_parse_body($tpl->widget_grey("{feature_disabled}", "{inactive2}"));
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/status"));
    if (!$json->Status) {
        $html[] = $tpl->widget_rouge($json->Error, "{error}");
        $html[] = "<div class='center' style='margin:20px'>$removedb</div>";
        return $tpl->_ENGINE_parse_body($html);
    }

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    $html[] = $tpl->SERVICE_STATUS($ini, "APP_HACLUSTER_CLIENT:width=400", $jsrestart);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/run"));
    if(!$json->Status){
        $html[] = "<div class='center' style='margin:20px'>$removedb</div>";
        return $tpl->_ENGINE_parse_body($html);
    }
    $html[] = "<table style='margin-top:10px' class='center'>";
    $html[] = "<tr>";

    $removedb=$tpl->button_autnonome("{disconnect}",$remove,"fas fa-trash","AsSystemAdministrator",190,"btn-danger");

    $Action=$tpl->button_autnonome("{stop}","Loadjs('$page?action=0')",ico_run,"AsSystemAdministrator",190,"btn-primary");

    if($json->Rule==0){
        $Action=$tpl->button_autnonome("{run}","Loadjs('$page?action=1')",ico_run,"AsSystemAdministrator",190,"btn-warning");
    }
    $html[] = "<td style='width:50%;text-align:center'>$Action</td>";
    $html[] = "<td style='padding-left:10px;width:50%;text-align:center'>$removedb</td>";

    $html[] = "</tr>";
    $html[] = "</table>";
    $html[] = "</div>";
    $html[]="<script>LoadAjaxSilent('hacluster-server-ping','$page?hacluster-server-ping=yes');</script>";
    return $tpl->_ENGINE_parse_body($html);


}


function haclient_flat(){
    $kerberosActiveDirectorySuffix=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectorySuffix"));

    $tpl=new template_admin();
    $page=CurrentPageName();

    $hacluster_id=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterID");
    $HaClusterIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
    $kerberosActiveDirectoryHost=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryHost");
    $KerberosSPN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosSPN");
    $kerberosRealm=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosRealm");
    $HaClusterClientInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientInterface");
    $HaClusterClientListenPort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientListenPort");
    if($HaClusterClientListenPort==0){
        $HaClusterClientListenPort=58787;
    }
    $HaClusterClientListenPort_status="";
    $periods[0]="{realtime}";
    $periods[5]="5 {minutes}";
    $periods[15]="15 {minutes}";

    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $HaClusterMyMasterAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMyMasterAddr"));

    if($HaClusterClient==1) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->HACLUSTERCLIENT_API("/status"));

        if ($json->Status) {
            $HaClusterStatus = strtoupper($json->HaClusterStatus);
            $HaPeriod = $periods[$json->HaClusterClientMaxLoadPeriod];
            $HaClusterClientMaxClient = $json->HaClusterClientMaxClient;
            $LastError = $json->LastError;
            $LastErrorTime = $json->LastErrorTime;

            if(property_exists($json, "HTTPServerRunning")) {
                if(!$json->HTTPServerRunning) {
                    $HaClusterClientListenPort_status="<span style='text-danger'>Err</span>";
                    $html[]=$tpl->div_error("{error} {listen_port} $HaClusterClientListenPort");
                }else{
                    $HaClusterClientListenPort_status="OK";
                }
            }




            $connections = $tpl->FormatNumber($json->Connection);
            if ($HaClusterStatus == "DOWN") {
                $tpl->table_form_field_text("{connections}", "$connections - {down}", ico_statistics, true);
            }
            if (strlen($LastError) > 3) {
                $LastError=str_replace("HaClusterClient.go","",$LastError);
                $LastTime = distanceOfTimeInWords($LastErrorTime, time());
                $tpl->table_form_field_text("{error}", "<small style='text-transform: none'>$LastTime: $LastError</small>", ico_error);
            }


            if ($HaClusterStatus == "UP") {
                $tpl->table_form_field_text("{connections}", "$connections - OK", ico_statistics);
            }

            if(property_exists($json, "Urls")) {
                $zz=array();
                $zz[]="<ul>";
                foreach ($json->Urls as $url) {
                    $zz[]="<li><a href=\"$url\" target='_top'>$url</a></li>";
                }
                $zz[]="</ul>";
                $tpl->table_form_field_text("{urlsTotest}", "<span style='font-size:12px;text-transform: none'>".@implode("",$zz)."</span>", ico_earth);
            }


            $tpl->table_form_field_text("{version}", $json->version, ico_infoi);
            $tpl->table_form_field_bool("{debug}", $json->HaClusterClientDebug, ico_bug);
            $tpl->table_form_field_bool("{monitor_cpu_usage}", $json->HaClusterForceAgentForceMoniCPU, ico_cpu);
            $tpl->table_form_field_text("{Max_Load}", $json->HaClusterClientMaxLoad . " ($HaPeriod)", ico_timeout);
            $tpl->table_form_field_text("{Max_Load} ({emergency})", $json->HaClusterClientEmergencyLoad . " ($HaPeriod)", ico_timeout);



            if ($HaClusterClientMaxClient > 0) {
                $tpl->table_form_field_text("Max. {members}", $HaClusterClientMaxClient, ico_member);
            }


        } else {
            $tpl->table_form_field_text("{connections}", $json->Error, ico_bug, true);
        }

        if(property_exists($json, "ClientCertificate")) {
            $ClientCertificate=$json->ClientCertificate;
            $data = openssl_x509_parse($ClientCertificate);
            if(!$data){
                $tpl->table_form_field_text("{client_certificate}", "{error}", ico_ssl,true);
            }else {
                $subject = $data["subject"]["CN"];
                $tpl->table_form_field_text("{client_certificate}", $subject, ico_ssl);
            }
        }
    }
    $tpl->table_form_section("{main_settings}");
    $tpl->table_form_field_js("");
    if(strlen($HaClusterMyMasterAddr)>3){
        $tpl->table_form_field_text("{master_server}",$HaClusterMyMasterAddr,ico_server);
    }else{
        $tpl->table_form_field_bool("{master_server}",0,ico_server);
    }

    $tpl->table_form_field_js("Loadjs('$page?form=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{ID}",$hacluster_id,ico_sensor);

    if (strlen($HaClusterClientInterface)>2){
        $tpl->table_form_field_text("{outgoing_interface}",$HaClusterClientInterface,ico_nic);
    }else{
        $tpl->table_form_field_text("{outgoing_interface}","{all}",ico_nic);
    }

    $tpl->table_form_field_text("{listen_port}","27899,$HaClusterClientListenPort ($HaClusterClientListenPort_status)",ico_nic);
    $tpl->table_form_field_text("{lb_ipaddr}",$HaClusterIP,ico_server);
    $tpl->table_form_section("Active Directory");
    $tpl->table_form_field_text("{ad_full_hostname}",$kerberosActiveDirectoryHost,ico_microsoft);
    $tpl->table_form_field_text("{ldap_suffix}",$kerberosActiveDirectorySuffix,ico_microsoft);
    $tpl->table_form_field_text("{KERBSPN}",$KerberosSPN,ico_microsoft);
    $tpl->table_form_field_text("{kerberos_realm}",$kerberosRealm,ico_microsoft);
    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body($html);

}


function form_popup(){
    $page=currentPageName();
    $tpl=new template_admin();
    $kerberosActiveDirectorySuffix=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectorySuffix"));

    VERBOSE("FORM",__LINE__);
    $hacluster_id=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterID");
    $HaClusterIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
    $kerberosActiveDirectoryHost=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectoryHost");
    $kerberosActiveDirectory2Host=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosActiveDirectory2Host");
    $KerberosUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosUsername");
    $KerberosPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosPassword");
    $KerberosSPN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosSPN");
    $kerberosRealm=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("kerberosRealm");
    $HaClusterClientInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientInterface");


    $form[]=$tpl->field_info("HaClusterID","{ID}",$hacluster_id);
    $form[]=$tpl->field_text("HaClusterIP","{lb_ipaddr}",$HaClusterIP);
    $form[]=$tpl->field_interfaces("HaClusterClientInterface","{outgoing_interface}",$HaClusterClientInterface);


    $form[]=$tpl->field_section("{active_directory}");
    $form[]=$tpl->field_email("KerberosUsername", "{username}", $KerberosUsername);
    $form[]=$tpl->field_password2("KerberosPassword", "{password}", $KerberosPassword);
    $form[]=$tpl->field_text("kerberosActiveDirectoryHost", "{ad_full_hostname}", $kerberosActiveDirectoryHost,false,"{ad_quick_1}");
    $form[]=$tpl->field_text("kerberosActiveDirectorySuffix", "{ldap_suffix}", $kerberosActiveDirectorySuffix);
    $form[]=$tpl->field_text("kerberosActiveDirectory2Host", "{FQDNDC2}", $kerberosActiveDirectory2Host,false,"{ad_quick_1}");
    $form[]=$tpl->field_info("KerberosSPN", "{KERBSPN}", $KerberosSPN);
    $form[]=$tpl->field_text("kerberosRealm", "{kerberos_realm}", $kerberosRealm);


    $js_disconnect=$tpl->framework_buildjs("/hacluster/client/disconnect",
    "haclient.progress","haclient.progress.log","disconnect-hacluster","window.location.reload();");




    $tpl->form_add_button("{disconnect}",$js_disconnect);
    $html[]="<div id='disconnect-hacluster'></div>";
    $html[]=$tpl->form_outside("{parameters}", $form,null,"{apply}","LoadAjax('hacluster-client-flat','$page?flat=yes');","AsSystemAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);

}

function save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/setup");
}
function hacluster_server_ping():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/server/ping"));
    if(!$json->Status){
        $json->Error=str_replace("ClusterClientHTTP.go[acluster/ClusterClientHTTP.HTTPGetData:","[",$json->Error);
        echo "<div style='margin-top:-15px'>".$tpl->div_error($json->Error)."</div>";
        return false;
    }
    return true;
}








function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}