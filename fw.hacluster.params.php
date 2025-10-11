<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once("/usr/share/artica-postfix/ressources/class.ActiveDirectory.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["HaClusterUseLBAsDNS"])){Save();exit;}

page();

function page(){
    $page=CurrentPageName();
    $html[]="<div id='hacluster-central-config' class='row border-bottom white-bg dashboard-header' style='padding-top:10px'></div>";
    $html[]="<script>LoadAjax('hacluster-central-config','$page?table=yes');</script>";
    echo @implode("\n",$html);
}

function table(){
        $page=CurrentPageName();
        $t=time();
        $tpl=new template_admin();
        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    $jsrestart=$tpl->framework_buildjs("hacluster.php?connect-nodes=yes",
        "hacluster.connect.progress","hacluster.connect.txt",
        "connect-$t","LoadAjaxSilent('hacluster-central-config','$page?table=yes');");

    $HaClusterDisableProxyProtocol=intval($HaClusterGBConfig["HaClusterDisableProxyProtocol"]);
    $HaClusterRemoveRealtimeLogs=intval($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]);
    $HaClusterUseHaClient=intval($HaClusterGBConfig["HaClusterUseHaClient"]);
    $HaClusterClientMaxLoad=intval($HaClusterGBConfig["HaClusterClientMaxLoad"]);
    $HaClusterClientMaxLoadPeriod=intval($HaClusterGBConfig["HaClusterClientMaxLoadPeriod"]);
    $HaClusterClientMoniCPU=intval($HaClusterGBConfig["HaClusterClientMoniCPU"]);
    $HaClusterClientEmergencyLoad=intval($HaClusterGBConfig["HaClusterClientEmergencyLoad"]);

    if(!isset($HaClusterGBConfig["HaClusterDecryptSSL"])){$HaClusterGBConfig["HaClusterDecryptSSL"]=0;}
    if($HaClusterClientEmergencyLoad==0){
        $HaClusterClientEmergencyLoad=15;
    }
    if($HaClusterClientEmergencyLoad<3){
        $HaClusterClientEmergencyLoad=3;
    }

    if($HaClusterClientMaxLoad<3){$HaClusterClientMaxLoad=3;}
    if($HaClusterDisableProxyProtocol==0){
        $HaClusterEnableProxyProtocol=1;
    }else{
        $HaClusterEnableProxyProtocol=0;
    }
    $form[]=$tpl->field_section("{infrastructure}");
    $form[]=$tpl->field_checkbox("HaClusterEnableProxyProtocol","{proxy_protocol}",$HaClusterEnableProxyProtocol);

    $periods[0]="{realtime}";
    $periods[5]="5 {minutes}";
    $periods[15]="15 {minutes}";

    $form[]=$tpl->field_section("{APP_HACLUSTER_CLIENT}");
    $form[]=$tpl->field_checkbox("HaClusterUseHaClient","{use_haclusterclient}",$HaClusterUseHaClient,
        "HaClusterClientMoniCPU,HaClusterClientMaxLoad,HaClusterClientMaxLoadPeriod");
    $form[]=$tpl->field_checkbox("HaClusterClientMoniCPU","{monitor_cpu_usage}",$HaClusterClientMoniCPU);
    $form[]=$tpl->field_numeric("HaClusterClientMaxLoad","{Max_Load}",$HaClusterClientMaxLoad);

    $form[]=$tpl->field_numeric("HaClusterClientEmergencyLoad","{Max_Load} ({emergency})",$HaClusterClientEmergencyLoad);

    $form[]=$tpl->field_array_buttons($periods,"HaClusterClientMaxLoadPeriod","{period}",$HaClusterClientMaxLoadPeriod);


    $form[]=$tpl->field_section("{dns_used_by_the_system}");
        $form[]=$tpl->field_checkbox("HaClusterUseLBAsDNS","{use_load_balancer_as_dns}",intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]));
        $form[]=$tpl->field_ipv4("DNS1", "{primary_dns} ", $HaClusterGBConfig["DNS1"]);
        $form[]=$tpl->field_ipv4("DNS2", "{secondary_dns} ", $HaClusterGBConfig["DNS2"]);
        $form[]=$tpl->field_text("DOMAINS1", "{InternalDomain} 1:", $HaClusterGBConfig["DOMAINS1"]);
        $form[]=$tpl->field_text("DOMAINS2", "{InternalDomain} 2:", $HaClusterGBConfig["DOMAINS2"]);


        $form[]=$tpl->field_section("{decrypt_ssl}");

        $form[]=$tpl->field_checkbox("HaClusterDecryptSSL",
            "{activate_ssl_on_http_port}",
            $HaClusterGBConfig["HaClusterDecryptSSL"],false,"{activate_ssl_on_http_port_explain}");

        $form[]=$tpl->field_certificate("HaClusterCertif","{proxy_certificate}",
            $HaClusterGBConfig["HaClusterCertif"],"{hacluster_Tproxy_certificate}",null);

        if(intval($HaClusterGBConfig["sslcrtd_program_dbsize"])==0){$HaClusterGBConfig["sslcrtd_program_dbsize"]=8;}
        $form[]=$tpl->field_numeric("sslcrtd_program_dbsize","{sslcrtd_program_dbsize} (MB)",$HaClusterGBConfig["sslcrtd_program_dbsize"]);

    $form[]=$tpl->field_section("{events}");
    $form[]=$tpl->field_checkbox("HaClusterRemoveRealtimeLogs","{disable_realtime_backends}",$HaClusterRemoveRealtimeLogs);



        $html[]="<div id='connect-$t'></div>";
        $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsrestart,"AsDnsAdministrator",false,false,"margin:0:fad fa-sliders-v");

        echo $tpl->_ENGINE_parse_body($html);

}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();

    if($_POST["HaClusterEnableProxyProtocol"]==1){
        $_POST["HaClusterDisableProxyProtocol"]=0;
}else{
        $_POST["HaClusterDisableProxyProtocol"]=1;
    }


    if($_POST["HaClusterDecryptSSL"]==1){
        if(trim($_POST["HaClusterCertif"])==null){
            echo "jserror:".$tpl->javascript_parse_text("{select} ! {proxy_certificate}");
            return;
        }
    }
    foreach ($_POST as $key=>$val) {
        admin_tracks("Update HaCluster backends option $key with $val");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterGBConfig",serialize($_POST));


}
