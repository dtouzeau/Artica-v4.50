<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_POST["EnableStatsComRemoteSyslog"])){save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["settings-js"])){settings_js();exit;}
if(isset($_GET["settings-popup"])){settings_popup();exit;}
if(isset($_GET["params"])){params_flat();exit;}
if(isset($_GET["main-top"])){main_top();exit;}

page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

//

    $html=$tpl->page_header("{statistics_service}","fad fa-chart-pie",
        "{statistics_service_explain}",
        "$page?table=yes",
        "statistics-service","progress-arsc-restart",false,"table-loader-arsc-pages");


    if(isset($_GET["main-page"])){$tpl=new template_admin("{statistics_service}",$html);
    echo $tpl->build_firewall();return;}


    echo $tpl->_ENGINE_parse_body($html);

}

function settings_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{statistics_service}","$page?settings-popup=yes",650);
}

function save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $EnableStatsComRemoteSyslog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsComRemoteSyslog"));
    $StatsComRemoteSyslogServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServer"));
    $StatsComRemoteSyslogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServerPort"));

    if($EnableStatsComRemoteSyslog==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/statscom/reload");
        return admin_tracks("Enable remote StatsCom to $StatsComRemoteSyslogServer:$StatsComRemoteSyslogServerPort");

    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/statscom/reload");
    return admin_tracks("Disable remote StatsCom");
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'><div id='nc-status'></div></td>";
    $html[]="<td style='width:100%;vertical-align: top'>";
    $html[]="<div id='main-top'></div>";
    $html[]="<div id='main-params'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $js=$tpl->RefreshInterval_js("nc-status",$page,"status=yes");

    $html[]="<script>$js;LoadAjaxSilent('main-params','$page?params=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function params_flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableStatsComRemoteSyslog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsComRemoteSyslog"));
    if($EnableStatsComRemoteSyslog==0){
        $tpl=new template_admin();
        $tpl->table_form_field_js("Loadjs('$page?settings-js=yes')","AsSquidAdministrator");
        $tpl->table_form_section("","{send_syslog_articastats}");
        $tpl->table_form_field_bool("{remote_syslog_server}",0,ico_servcloud2);
        echo $tpl->table_form_compile();
        return true;
    }

    $StatsComRemoteSyslogServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServer"));
    $StatsComRemoteSyslogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServerPort"));
    $StatsComRemoteSyslogServerTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServerTCP"));
    $StatsComRemoteSyslogUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogUseSSL"));
    $StatsComRemoteSyslogCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogCertificate"));
    if($StatsComRemoteSyslogServerPort==0){$StatsComRemoteSyslogServerPort=514;}
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes')","AsSquidAdministrator");

    $proto="udp";
    if($StatsComRemoteSyslogServerTCP==1){
        $proto="tcp";
    }
    if($StatsComRemoteSyslogUseSSL==1){
        $proto="ssl";
    }

    $tpl->table_form_field_text("{remote_syslog_server}","$proto://$StatsComRemoteSyslogServer:$StatsComRemoteSyslogServerPort",ico_servcloud2);

    if($StatsComRemoteSyslogUseSSL==1){
        $tpl->table_form_field_text("{certificate}","$StatsComRemoteSyslogCertificate",ico_certificate);
    }
    echo $tpl->table_form_compile();
    return true;
}

function settings_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $EnableStatsComRemoteSyslog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsComRemoteSyslog"));
    $StatsComRemoteSyslogServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServer"));
    $StatsComRemoteSyslogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServerPort"));
    $StatsComRemoteSyslogServerTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogServerTCP"));
    $StatsComRemoteSyslogUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogUseSSL"));
    $StatsComRemoteSyslogCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StatsComRemoteSyslogCertificate"));
    if($StatsComRemoteSyslogServerPort==0){$StatsComRemoteSyslogServerPort=514;}


    $form[] = $tpl->field_checkbox("EnableStatsComRemoteSyslog", "{enable_feature}", $EnableStatsComRemoteSyslog,"StatsComRemoteSyslogServer");
    $form[] = $tpl->field_ipv4("StatsComRemoteSyslogServer", "{remote_syslog_server}", $StatsComRemoteSyslogServer);
    $form[] = $tpl->field_numeric("StatsComRemoteSyslogServerPort","{listen_port}",$StatsComRemoteSyslogServerPort);
    $form[] = $tpl->field_checkbox("StatsComRemoteSyslogServerTCP","{enable_tcpsockets}",$StatsComRemoteSyslogServerTCP);
    $form[] = $tpl->field_checkbox("StatsComRemoteSyslogUseSSL", "{useSSL}", $StatsComRemoteSyslogUseSSL);
    $form[] = $tpl->field_certificate("StatsComRemoteSyslogCertificate", "{certificate}", $StatsComRemoteSyslogCertificate);

    $html[]=$tpl->form_outside("",$form,null,"{apply}","dialogInstance2.close();LoadAjaxSilent('main-params','$page?params=yes');","AsSquidAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);

}
function main_top():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=$_SESSION["STATSCOM_REMOTE_TOP"];
    if(!$json->Status){
        if(!property_exists($json,"EnableRemote")){
            return true;
        }
    }
    if($json->EnableRemote==0){
        return true;
    }

    $html[]="<table style='width:100%;margin-top:-10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;padding-left:15px'>";

    if($json->RemoteCountFailed==0){
        $html[]=$tpl->widget_h("green",ico_bug,0,"{errors}");
    }else{
        $html[]=$tpl->widget_h("yellow",ico_bug,$tpl->FormatNumber($json->RemoteCountFailed),"{errors}");
    }
    $html[]="</td>";
    $html[]="<td style='width:50%;padding-left:15px'>";
    if($json->RemoteCountSuccess==0){
        $html[]=$tpl->widget_h("gray","fas fa-cloud-showers-heavy",0,"{connections}");
    }else{
        $html[]=$tpl->widget_h("green","fas fa-cloud-showers-heavy",$tpl->FormatNumber($json->RemoteCountSuccess),"{connections}");
    }
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/statscom/remote/status"));
    $_SESSION["STATSCOM_REMOTE_TOP"]=$json;

    echo "<script>LoadAjaxSilent('main-top','$page?main-top=yes');</script>";

    if(!$json->Status){
        if(!property_exists($json,"EnableRemote")){
            echo $tpl->widget_rouge("<small style='color:white'>$json->Error</small>","{error}");
            return true;
        }
    }
    if($json->EnableRemote==0){
        echo $tpl->widget_grey("{disabled}","{status}");
        return true;
    }

    if(!$json->Status){
        echo $tpl->widget_rouge("<small style='color:white'>$json->Error</small>","{error}");
        return true;
    }
    echo $tpl->widget_vert("$json->Server:$json->ServerPort OK","{status}");
    return true;

}