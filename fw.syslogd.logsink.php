<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.syslog.tls.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["remove"])){remove_host_confirm();exit;}
if(isset($_GET["remove"])){remove_host();exit;}
if(isset($_POST["client_hostname"])){client_package_save();exit;}
if(isset($_POST["RsyslogInterface"])){save();exit;}
if(isset($_GET["refeshindex"])){refeshindex();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["logs-sink-install"])){log_sink_install();exit;}
if(isset($_GET["logs-sink-uninstall"])){log_sink_uninstall();exit;}
if(isset($_GET["disable-ssl-ask"])){disable_ssl_ask();exit;}
if(isset($_POST["disable-ssl"])){disable_ssl_confirm();exit;}
if(isset($_GET["download-ca"])){download_ca();exit;}
if(isset($_GET["client-package-js"])){client_package_js();exit;}
if(isset($_GET["client-package-popup"])){client_package_popup();exit;}
if(isset($_GET["client-package-download"])){client_package_download();exit;}
if(isset($_GET["logsink-status"])){service_status();exit;}
if(isset($_GET["server-list"])){servers_list();exit;}
if(isset($_GET["reconfigure"])){reconfigure();exit;}
page();

function refeshindex():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $js_progress=$tpl->framework_buildjs("/syslog/logsink/scan",
        "logs-sink-refresh.progress","logs-sink-refresh.log","server-list",
        "LoadAjax('server-list','$page?server-list=yes');",
        "LoadAjax('server-list','$page?server-list=yes');"
    );

    echo $js_progress;
    return true;
}

function reconfigure():bool{
    $sock=new sockets();
    $sock->REST_API("/syslog/reconfigure");
    $tpl=new template_admin();
   return  $tpl->js_config_applied("{success}");
}

function remove_host():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $host=base64_decode($_GET["remove"]);
    $id=$_GET["id"];
    $tpl->js_confirm_delete($host,"remove",$host,"$('#$id').remove();LoadAjaxSilent('top-status-syslog','$page?top-status=yes');");
    return true;
}
function remove_host_confirm():bool{
    $host=$_POST["remove"];
    admin_tracks("Remove syslog of $host from Log Sink");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/logsink/remove/host/$host");
    return true;
}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{logs_sink} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION")}",
        ico_logsink,"{log_sink_explain}","$page?status=yes","log-sink",
        "progress-syslod-restart",false,"table-loader-logsink-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SYSLOGD}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function client_package_download():bool{
    return syslogtls_client_package_download();
}
function download_ca():bool{
    $RsyslogCertificates=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogCertificates")));

    $data=$RsyslogCertificates["CA"];
    $fsize=strlen($data);
    $hostname=php_uname('n');
    header('Content-type:application/x-x509-ca-cert');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$hostname.ca\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $data;
    return true;
}

function log_sink_install():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSyslogLogSink",1);
    header("content-type: application/x-javascript");

    $rebuild=$tpl->framework_buildjs(
        "/syslog/reconfigure","syslog.restart.progress",
        "syslog.restart.progress.log","syslog-restart-status",
        "LoadAjax('table-loader-syslod-service','$page?tabs=yes')");

    echo $rebuild;
    admin_tracks("Activate Logs Sink feature in syslog");
    return true;
}
function log_sink_uninstall():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSyslogLogSink",0);
    header("content-type: application/x-javascript");

    $rebuild=$tpl->framework_buildjs(
        "/syslog/reconfigure","syslog.restart.progress",
        "syslog.restart.progress.log","syslog-restart-status",
        "document.location.href='/syslogd'");


    echo $rebuild;
    admin_tracks("Disable Logs Sink feature in syslog");
    return true;
}
function client_package_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{create_client_package}","$page?client-package-popup=yes");
    return true;
}
function client_package_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter="dialogInstance2.close();document.location.href='/$page?client-package-download=yes';";
    $html[]="<div id='client-ssl-create'></div>";
    $html[]=syslogtls_client_package_form($tpl,$jsafter);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function client_package_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $error=syslogtls_client_package_build($_POST["client_hostname"],$_POST["server_hostname"],$_POST["client_password"]);
    if($error<>""){
        echo $tpl->post_error($error);
        return false;
    }
    admin_tracks("Creating Log sink client package for ".$_POST["client_hostname"]);
    return true;
}
function ServiceStatus():string{
    $tpl        = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/syslog/status");
    $json=json_decode($data);
    $page       = CurrentPageName();
    $jsrestart=$tpl->framework_buildjs("/syslog/reconfigure","syslog.restart.progress","syslog.restart.log","progress-syslod-restart","LoadAjax('table-loader-syslod-service','$page?tabs=yes');");

    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $final[]=$tpl->SERVICE_STATUS($ini, "APP_RSYSLOG",$jsrestart);

    return @implode("\n",$final);
}
function service_status():bool{
    echo ServiceStatus();
    return true;
}

function status():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:90%;margin-top:20px'>";
	$html[]="<tr>";
    $html[]="<td valign='top' style='width:350px'>";
    $html[]="<div class='center' id='logsink-status'></div></td>";
    $html[]="<td style='vertical-align: top;padding-left:20px;padding-right: 20px'>";
    $html[]="<div id='top-status-syslog' style='margin-bottom:10px;padding-left: 10px'></div>";
    $html[]="<div id='server-list'></div>";

    $html[]="</td></tr></table>";


    $RsyslogTCPUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogTCPUseSSL"));
    if($RsyslogTCPUseSSL==1) {
        $topbuttons[] = array("Loadjs('$page?client-package-js=yes');", ico_certificate, "{create_client_package}");
    }


    $topbuttons[] = array("LoadAjax('logsink-status','$page?logsink-status=yes');LoadAjax('top-status-syslog','$page?top-status=yes');", ico_refresh, "{refresh}");

    $s_PopUp="s_PopUp('https://wiki.articatech.com/system/syslog/log-sink','1024','800')";
    $topbuttons[] = array("Loadjs('$page?refeshindex=yes')", ico_retweet, "{analyze} {hosts}");
    $topbuttons[] = array("Loadjs('$page?reconfigure=yes')", ico_refresh, "{reconfigure}");
    $topbuttons[] = array($s_PopUp, ico_support, "Wiki URL");


    $TINY_ARRAY["TITLE"]="{logs_sink} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION")}";
    $TINY_ARRAY["ICO"]=ico_logsink;
    $TINY_ARRAY["EXPL"]="{log_sink_explain}";
    $TINY_ARRAY["URL"]="log-sink";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>";
    $Interval=$tpl->RefreshInterval_js("logsink-status",$page,"logsink-status=yes",3);
    $Interval2=$tpl->RefreshInterval_js("top-status-syslog",$page,"top-status=yes",10);
    $Interval3=$tpl->RefreshInterval_js("server-list",$page,"server-list=yes",12);

    $html[]=$Interval;
    $html[]=$Interval2;
    $html[]=$Interval3;
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function servers_list():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $html[]="<table style='width:80%;margin:30px'>";
    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    $c=0;

    $html[]="<table id='table-$t' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th colspan='2'>{hosts}</th>";
    $html[]="<th >{size}</th>";
    $html[]="<th >DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    foreach ($SyslogSinkStatus as $hostname=>$array){
        if($hostname=="ALL_DATES"){continue;}
        $c++;
        $hostname_encode=base64_encode($hostname);
        $id=md5($hostname);
        $hostname=$tpl->td_href($hostname,null,"Loadjs('fw.syslogd.browse.php?all-table-js=yes&hostname=$hostname_encode')");
        $html[]="<tr id='$id'>";
        $html[]="<td width=1%><i class='".ico_server."'></i></td>";
        $html[]="<td width=99%>&nbsp;&nbsp;$hostname</td>";
        $html[]="<td style='width:1%' nowrap>". FormatBytes($array["SIZE"]/1024)."</td>";
        $html[]="<td width=1%>".$tpl->icon_delete("Loadjs('$page?remove=$hostname_encode&id=$id')","AsSystemAdministrator")."</td>";
        $html[]="<tr>";

    }
    if($c==0){

        $html[]="<tr id='$id'>";
        $html[]="<td width=1%>&nbsp;</td>";
        $html[]="<td width=99%>&nbsp;&nbsp;{none_connected_server}</td>";
        $html[]="<td style='width:1%' nowrap>&nbsp;</td>";
        $html[]="<td width=1% style='padding-left:10px'>&nbsp;</td>";
        $html[]="<tr>";
    }

    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function SyslogLogSink_status():string{
    $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
    $page=CurrentPageName();
    $tpl=new template_admin();

    if($EnableSyslogLogSink==0) {
        $btn[] = array("name" => "{install}", "js" => "Loadjs('$page?logs-sink-install=yes');", "icon" => ico_cd);
        return $tpl->widget_grey("{logs_sink}", "{disabled}", $btn);
    }
    $btn[]=array("name"=>"{uninstall}","js"=>"Loadjs('$page?logs-sink-uninstall=yes');","icon"=>"fas fa-trash-alt");
    $btn[]=array("name"=>"{browse}","js"=>"Loadjs('fw.syslogd.browse.php?all-table-js=yes');","icon"=>"fa-solid fa-file-zipper");

    $SyslogSinkStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogSinkStatus"));
    $Clients=0;
    $size=0;

    if(is_array($SyslogSinkStatus)) {
        foreach ($SyslogSinkStatus as $fname => $array) {
            VERBOSE("..................$fname",__LINE__);
            if(!isset($array["SIZE"])){continue;}
            $size = $size + intval($array["SIZE"]);
            $Clients++;
        }
    }

    if($Clients==0) {
        return $tpl->widget_vert("{logs_sink}", "{active2}", $btn);
    }

    $info = "$Clients {clients} <small style='color:white'>(" . FormatBytes($size / 1024) . ")</small>";
    return $tpl->widget_vert("{logs_sink}",$info,$btn);


}
function top_status_css():string{
return "minheight:257px;margin-top:3px;width:350px";

}
function top_status_ssl():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ico=ico_certificate;
    $warn=ico_emergency;

    if(!is_file("/usr/lib/x86_64-linux-gnu/rsyslog/lmnsd_ossl.so")) {
        return $tpl->widget_h("grey", $warn, "{missing} lmnsd_ossl.so", "{UseSSL}",
            null, top_status_css());
    }

    $RsyslogTCPUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogTCPUseSSL"));

    $js=$tpl->framework_buildjs("/syslog/tls/enable",
        "syslog.ssl.progress","syslog.ssl.log","progress-syslod-restart",
        "LoadAjax('top-status-syslog','$page?top-status=yes');");

    if($RsyslogTCPUseSSL==0){
        $button["name"] = "{install}";
        $button["js"] = $js;
        $button["ico"]="fa-solid fa-compact-disc";
        return $tpl->widget_h("grey",$ico, "{disabled}", "{UseSSL}",$button,top_status_css());

    }
    $RsyslogCertificatesArr=array();

    $RsyslogCertificates=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogCertificates");
    if($RsyslogCertificates<>null){
        $tmp=base64_decode($RsyslogCertificates);
        $RsyslogCertificatesArr=unserialize($tmp);
    }

    if(!isset($RsyslogCertificatesArr["CA"])){
        $button["name"] = "{install}";
        $button["js"] = $js;
        $button["ico"]="fa-solid fa-compact-disc";
        return $tpl->widget_h("red",$warn, "{missing} CA", "{UseSSL}",$button,top_status_css());

    }



    $button["name"] = "{uninstall}";
    $button["js"] = "Loadjs('$page?disable-ssl-ask=yes');";
    $button["ico"]="fa fa-trash";
    $button["css"]=top_status_css();

    $button2["name"] = "{download} CA";
    $button2["js"] = "document.location.href='/$page?download-ca=yes'";
    $button2["ico"]="fa-solid fa-download";


    return $tpl->widget_h("green",$ico, "{enabled}",
        "{UseSSL}",$button,$button2);




}
function disable_ssl_ask():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $js=$tpl->framework_buildjs("/syslog/tls/disable",
        "syslog.ssl.progress","syslog.ssl.log","progress-syslod-restart",
        "LoadAjax('top-status-syslog','$page?top-status=yes');");

    echo $tpl->js_confirm_delete("{UseSSL}","disable-ssl","yes",$js);
    return true;
}
function disable_ssl_confirm():bool{
    admin_tracks("Disable SSL protocol on the syslog service");
    return true;
}


function top_status_syslog():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $SYSLOG_MSG_RECEIVED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSLOG_MSG_RECEIVED"));
    if($SYSLOG_MSG_RECEIVED>0){
        $SYSLOG_MSG_RECEIVED=$tpl->FormatNumber($SYSLOG_MSG_RECEIVED);
       return $tpl->widget_vert("{received_messages}",$SYSLOG_MSG_RECEIVED);
    }
    return $tpl->widget_grey("{received_messages}","{no_data}");

}

function top_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%;'>";
    $html[]="<tr>";


    $html[]="<td valign='top' style='padding-left:10px'>";
    $html[]=top_status_syslog();
    $html[]="</td>";

    $html[]="<td valign='top' style='padding-left:10px'>";
    $html[]=top_status_ssl();
    $html[]="</td>";

    $html[]="<td valign='top' style='padding-left:10px'>";
    $html[]=SyslogLogSink_status();
    $html[]="</td>";

    $html[]="</tr>";
    $html[]="</table>";



    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}
