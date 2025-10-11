<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");


if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["form-service-popup"])){form_service_popup();exit;}
if(isset($_GET["form-service-js"])){form_service_js();exit;}
if(isset($_GET["flat"])){flat_config();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_GET["jstiny"])){jsTiny();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_IPERF3} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("iperf3Version")}",
        ico_eye,"{APP_IPERF3_EXPLAIN}","$page?table=yes","iperf3",
        "progress-ipref3-restart",false,"table-loader-iperf3-service");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_IPERF3}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function form_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog1("{parameters}","$page?form-service-popup=yes");
}
function table():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tableid    = time();

    $jstiny="Loadjs('$page?jstiny=yes')";



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align:top'><div id='iperf3-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:10px'>";
    $html[]="<div id='progress-compile-replace-$tableid' style='margin-top:20px'></div>";
    $html[]="<div id='iperf3-flat'></div>";



    $jsReferh=$tpl->RefreshInterval_js("iperf3-status",$page,"status=yes");
    $html[]="<script>";
    $html[]="LoadAjax('iperf3-flat','$page?flat=yes');";
    $html[]=$jsReferh;
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function jsTiny():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $EnableIperf3=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIperf3"));
    $jstiny="Loadjs('$page?jstiny=yes')";
    $rebuild=$tpl->framework_buildjs(
        "/iperf3/restart","progress-ipref3-restart",
        "iperf3.progress",
        "iperf3.progress.log",
        "LoadAjax('section-iperf3-rules','$page?table=yes')");

    if($EnableIperf3==0){
        $install=$tpl->framework_buildjs(
            "/iperf3/install",
            "iperf3.progress",
            "iperf3.progress.log",
            "progress-ipref3-restart",
            "$jstiny"
        );
        $topbuttons[]=array($install,ico_cd,"{install_feature}");
    }else{
        $uninstall=$tpl->framework_buildjs(
            "/iperf3/uninstall",
            "iperf3.progress",
            "iperf3.progress.log",
            "progress-ipref3-restart",
            "$jstiny"
        );
        $topbuttons[]=array($uninstall,ico_trash,"{uninstall_service}");
    }

    $TINY_ARRAY["TITLE"]="{APP_IPERF3} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("iperf3Version")}";
    $TINY_ARRAY["ICO"]=ico_speed;
    $TINY_ARRAY["EXPL"]="{APP_IPERF3_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);


    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    return true;
}

function form_service_popup(){

    $Iperf3Interface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Iperf3Interface"));
    $tpl = new template_admin();
    $security="AsSystemAdministrator";
    $Iperf3Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("iperf3Port"));
    if($Iperf3Port==0){
        $Iperf3Port=5900;
    }

    $form[] = $tpl->field_interfaces("Iperf3Interface", "nodef:{listen_interface}", $Iperf3Interface);
    $form[] = $tpl->field_numeric("iperf3Port", "{listen_port}",$Iperf3Port, $Iperf3Interface);
    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",jsrestart(),$security);
}
function jsrestart():string{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $jsrestart="";
    $EnableIperf3=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIperf3"));
    if($EnableIperf3==1) {
        $jsrestart=$tpl->framework_buildjs(
            "/iperf3/restart",
            "iperf3.progress",
            "iperf3.progress.log",
            "progress-ipref3-restart"
        );
    }
    return "dialogInstance1.close();LoadAjax('section-iperf3-rules','$page?table=yes')$jsrestart;";
}
function flat_config(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $Iperf3Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("iperf3Port"));
    if($Iperf3Port==0){
        $Iperf3Port=5900;
    }
    $Iperf3Interface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("iperf3Interface");
    if($Iperf3Interface==""){
        $Iperf3Interface="0.0.0.0";
    }
    $tpl->table_form_field_js("Loadjs('$page?form-service-js=yes')",$security);
    $tpl->table_form_field_text("{listen}","$Iperf3Interface:$Iperf3Port",ico_nic);
    echo $tpl->table_form_compile();

}

function status():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $EnableIperf3=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIperf3"));

    if($EnableIperf3==0){
        echo $tpl->widget_grey("{APP_IPERF3}","{feature_disabled}");
        return false;
    }
    $jsrestart=$tpl->framework_buildjs(
        "/iperf3/restart",
        "iperf3.progress",
        "iperf3.progress.log",
        "progress-ipref3-restart"
    );


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/iperf3/status"));
    $ini=new Bs_IniHandler();

    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
        return false;

    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
            return false;
        } else {
            $ini->loadString($json->Info);
            $html[]=$tpl->SERVICE_STATUS($ini, "APP_IPERF3",$jsrestart);
        }
    }

    echo $tpl->_ENGINE_parse_body($html);


    return true;
}