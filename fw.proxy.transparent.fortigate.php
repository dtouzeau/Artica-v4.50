<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["parameters-flat"])){parameters_flat();exit;}


page();

function page():bool{

        $page=CurrentPageName();
        $tpl=new template_admin();
        $html[]="<table style='width:100%;margin-top:10px'>";
        $html[]="<tr>";
        $html[]="<td style='width:240px;vertical-align: top'><div id='tproxy-status'></div></td>";
        $html[]="<td style='width:99%;vertical-align: top;padding-left:10px'><div id='tproxy-params'></div></td>";
        $html[]="</tr>";
        $html[]="<script>";
        $js=$tpl->RefreshInterval_js("tproxy-status",$page,"status=yes");

        $html[]="$js";
        $html[]="LoadAjaxSilent('tproxy-params','$page?parameters-flat=yes');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
}

function parameters_flat(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    $TINY_ARRAY["TITLE"]="{fortigate_tproxy}";
    $TINY_ARRAY["ICO"]=ico_firewall;
    $TINY_ARRAY["EXPL"]="{fortigate_tproxy_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $FortigateTproxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortigateTproxyEnabled"));


    if($FortigateTproxyEnabled==0){
        $after=$tpl->framework_buildjs("/proxy/fortigate/install",
            "squid.fortigate.progress",
            "squid.fortigate.log",
            "progress-squid-ports-restart",
            "LoadAjaxSilent('tproxy-params','$page?parameters-flat=yes');"
        );
    }else {

        $after = $tpl->framework_buildjs("/proxy/fortigate/install",
            "squid.fortigate.progress",
            "squid.fortigate.log",
            "progress-squid-ports-restart", "LoadAjaxSilent('tproxy-params','$page?parameters-flat=yes');");
    }

    $tpl->table_form_field_js($after);
    $tpl->table_form_field_bool("{fortigate_tproxy}",$FortigateTproxyEnabled,ico_check);
    $tpl->table_form_field_js("");
    if($FortigateTproxyEnabled==0){
        $tpl->table_form_field_bool("{http_port} (35025)",false,ico_nic);
        $tpl->table_form_field_bool("{ssl_port} (35026)",false,ico_nic);
    }



}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $FortigateTproxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FortigateTproxyEnabled"));
    if($FortigateTproxyEnabled==0){
        echo $tpl->_ENGINE_parse_body($tpl->widget_grey("{status}","{feature_disabled}"));
        return true;
    }

    return true;

}
