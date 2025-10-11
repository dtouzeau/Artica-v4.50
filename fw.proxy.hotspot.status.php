<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["HotSpotTemplateID"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["hotspot-web-status"])){status();exit;}
if(isset($_GET["wizard-js"])){wizard_js();exit;}
if(isset($_GET["wizard-popup"])){wizard_popup();exit;}
if(isset($_POST["iface_internet"])){wizard_save1();exit;}
if(isset($_GET["wizard-popup2"])){wizard_popup2();exit;}
if(isset($_POST["guest_network"])){wizard_save2();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $HOTSPOTWEB_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");
    if($HOTSPOTWEB_VERSION==null){$HOTSPOTWEB_VERSION="4.x";}

    $html=$tpl->page_header("{web_portal_authentication} v$HOTSPOTWEB_VERSION",
        "fas fa-tachometer-alt","{HotSpot_text}","$page?table=yes",
        "hotspot-status","progress-hotspot-restart",false,"table-hotspot");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{hotspot_auth}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{general_settings}"]="$page?table=yes";
    $array["{networks}"]="fw.proxy.hotspot.network.php";
    $array["{active_directory_groups}"]="fw.proxy.hotspot.adgroups.php";
    $array["{skins}"]="fw.proxy.hotspot.textes.php";
    $array["{skins} WIFI4EU"]="fw.proxy.hotspot.wifi4eu.php";
    echo $tpl->tabs_default($array);
}
function is_authExists(){
    $HotSpotAuthentAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentAD"));
    if($HotSpotAuthentAD==1){return true;}
    $HotSpotAuthentLocalLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentLocalLDAP"));
    if($HotSpotAuthentLocalLDAP==1){return true;}
    $HotSpotAuthentVoucher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAuthentVoucher"));
    if($HotSpotAuthentVoucher==1){return true;}
    $HotSpotAutoLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotAutoLogin"));
    if($HotSpotAutoLogin==1){return true;}
    $HotSpotWIFI4EU_ENABLE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotWIFI4EU_ENABLE"));
    if($HotSpotWIFI4EU_ENABLE==1){return true;}

}
function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$users=new usersMenus();
    $EnableSquidMicroHotSpot = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
	$HotSpotRedirectUI=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotRedirectUI"));
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'>";
    $html[]="<div id='hotspot-web-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;padding-left: 10px;vertical-align: top'>";

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";



    if($Go_Shield_Server_Enable==0){
        $Go_Shield_Connector_Addr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Addr"));
        if(empty($Go_Shield_Connector_Addr)){$Go_Shield_Connector_Addr="127.0.0.1";}
        if($Go_Shield_Connector_Addr=="127.0.0.1"){
            $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("red",
                "far fa-times-circle", "{KSRN_SERVER2}", "{disabled}"));
        }
        $html[]="</tr>";
        $html[]="</table>";
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    if($HotSpotRedirectUI==null){
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("red",
            "fad fa-unlink", "{artica_proxy_plugin}", "{disconnected}"));
    }else{
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green",
            "fas fa-link", "{connected}", "{artica_proxy_plugin}"));
    }
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:100%;vertical-align: top'>";

    if($HotSpotRedirectUI==null){
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("red", "fas fa-external-link", "{HotSpotRedirectUI}", "{error_no_redirect_uri}"));
    }else{
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green", "fas fa-external-link",
            "{defined}", "{HotSpotRedirectUI}"));
    }
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:100%;vertical-align: top'>";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('hotspot.php?status=yes');
    $ini=new Bs_IniHandler(PROGRESS_DIR."/APP_HOTSPOT.status");

    if($ini->_params["APP_HOTSPOT"]["running"]==0){
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("red", "far fa-globe-europe", "{stopped}","{APP_HOTSPOT} {web_service}"));
    }else{
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green", "far fa-globe-europe", "{running}","{APP_HOTSPOT} {web_service}"));
    }

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:100%;vertical-align: top'>";
    if(!$GLOBALS["CLASS_SOCKETS"]->HOTSPOT_IS_AUTH_EXISTS()){
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("red", "fad fa-user-check", "{authentication}", "{no_auth_defined}"));

    }else{
        $html[] = $tpl->_ENGINE_parse_body($tpl->widget_h("green",
                "fad fa-user-check", "{defined}", "{authentication}"));
    }
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

    $topbuttons[] = array("Loadjs('fw.proxy.hotspot.status.php?wizard-js=yes');", ico_wizard, "{hotspotwizard}");
    //$topbuttons=array();

    $HOTSPOTWEB_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");
    if($HOTSPOTWEB_VERSION==null){$HOTSPOTWEB_VERSION="4.x";}

    $TINY_ARRAY["TITLE"]="{web_portal_authentication} v$HOTSPOTWEB_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{HotSpot_text}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo "<script>
        $jstiny
        LoadAjax('hotspot-web-status','$page?hotspot-web-status=yes');
        </script>";
	return false;
	

}
function status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('hotspot.php?status=yes');
    $ini=new Bs_IniHandler(PROGRESS_DIR."/APP_HOTSPOT.status");

    $jrestart=$tpl->framework_buildjs("hotspot.php?restart=yes",
        "hotspot.progress","hotspot.log","progress-hotspot-restart",
        "LoadAjax('hotspot-web-status','$page?hotspot-web-status=yes');",
        "LoadAjax('hotspot-web-status','$page?hotspot-web-status=yes');");

   echo $tpl->SERVICE_STATUS($ini, "APP_HOTSPOT",$jrestart);
}
function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $EnableSquidMicroHotSpot=intval($_POST["EnableSquidMicroHotSpot"]);
	if($_POST["HotSpotWIFI4EU_ENABLE"]==1){
        $_POST["HotSpotEntrepriseTemplate"]=0;
        $_POST["HotSpotAutoLogin"]=0;
        $_POST["HotSpotAuthentAD"]=0;
        $_POST["HotSpotAuthentLocalLDAP"]=0;
        $_POST["HotSpotAuthentVoucher"]=0;

    }
	if($_POST["HotSpotRedirectUI2"]<>null){
		$_POST["HotSpotRedirectUI"]=$_POST["HotSpotRedirectUI2"];
		unset($_POST["HotSpotRedirectUI2"]);
	}

    if($EnableSquidMicroHotSpot==1) {
        if ($_POST["HotSpotRedirectUI"] == null) {
            echo $tpl->post_error("{you_need_uri_redirection}");
            return false;

        }
    }


	$tpl->SAVE_POSTs();
	
	
	
}
function wizard_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog4("{START_WIZARD}","$page?wizard-popup=yes",550);
}
function  wizard_popup():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    if(!$json){
        $array["iface_internet"]="";
        $array["iface_guest"]="";
        $array["guest_network"]="";
        $array["guest_netmask"]="";
        $tmp=json_encode($array);
        $json=json_decode($tmp);
    }

    $html[]="<div id='hotspot-wizard-progress'>".$tpl->div_explain("{APP_HOTSPOT}||{hotspot_wizardv2}")."</div>";
    $html[]="<div id='hotspot-wizard-div'>";
    $form[]=$tpl->field_interfaces("iface_internet","{listen_interface} (Internet)",$json->iface_internet);
    $form[]=$tpl->field_interfaces("iface_guest","{listen_interface} ({guest_network})",$json->iface_guest);
    $html[]=$tpl->form_outside("",$form,"","{next}","LoadAjax('hotspot-wizard-div','$page?wizard-popup2=yes');");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function wizard_save1():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    if(!$json){
        $array["iface_internet"]="";
        $array["iface_guest"]="";
        $array["guest_network"]="";
        $array["guest_netmask"]="";
        $tmp=json_encode($array);
        $json=json_decode($tmp);
    }
    if($_POST["iface_guest"]==null){
        $_POST["iface_guest"]=$_POST["iface_internet"];
    }
    if($_POST["iface_internet"]==null){
        $_POST["iface_internet"]=$_POST["iface_guest"];
    }
    $json->iface_internet=$_POST["iface_internet"];
    $json->iface_guest=$_POST["iface_guest"];
    $tmp=json_encode($json);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOTSPOT_WIZARD",$tmp);
    return admin_tracks("Saving HotSpot wizard inet OUT=$json->iface_internet IN=$json->iface_guest");
}

function wizard_popup2():bool{
    $tpl=new template_admin();
    $page=currentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    $iface=new system_nic($json->iface_guest);
    if($json->guest_network==null){
        $guest_netmask=$iface->GetCDIRNetwork($iface->IPADDR,$iface->NETMASK);
        $tr=explode("/",$guest_netmask);
        $json->guest_network=$tr[0];
        $json->guest_netmask=$tr[1];
    }

        $jsafter=$tpl->framework_buildjs("/proxy/hotspot/wizard","hotspot-wizard.progress","hostspot.wizard.log","hotspot-wizard-progress","document.location.href='/hotspot-config'");

    $form[]=$tpl->field_ipv4("guest_network","{from_ip}",$json->guest_network);
    $form[]=$tpl->field_numeric("guest_netmask","{netmask}",$json->guest_netmask);
    $html[]=$tpl->form_outside("{guest_network}",$form,"","{build}",$jsafter);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function wizard_save2():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOT_WIZARD"));
    $json->guest_network=$_POST["guest_network"];
    $json->guest_netmask=$_POST["guest_netmask"];
    $tmp=json_encode($json);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOTSPOT_WIZARD",$tmp);
    return admin_tracks("Saving HotSpot wizard Guest =$json->guest_network/$json->guest_netmask");
}