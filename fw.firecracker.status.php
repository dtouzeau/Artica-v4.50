<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users=new usersMenus();if(!$users->AsVirtualBoxManager){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["service-status"])){status();exit;}
if(isset($_GET["vmlinux-status"])){vmlinux_status();exit;}
if(isset($_GET["dhcp-status"])){dhcp_status();exit;}

if(isset($_GET["images-status"])){images_status();exit;}
if(isset($_GET["global-config"])){flat_config();exit;}
if(isset($_POST["FCDHCPInterface"])){dhcp_save();exit;}
if(isset($_GET["dhcp-js"])){dhcp_js();exit;}
if(isset($_GET["dhcp-popup"])){dhcp_popup();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $APP_FIRECRACKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");
    $html=$tpl->page_header("{APP_FIRECRACKER} v$APP_FIRECRACKER_VERSION",
        "fas fa-tachometer-alt","{APP_FIRECRACKER_EXPLAIN}","$page?table=yes",
        "firecracker-status","progress-firecracker-restart",false,"table-firecracker");


	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_FIRECRACKER}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
}

function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'>";
    $html[]="<div id='firecracker-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:100%;padding-left: 10px;vertical-align: top'>";
    $html[]="<table style='width:100%;margin-top:-8px'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;vertical-align: top'><div id='vmlinux-status'></div></td>";
    $html[]="<td style='width:33%;vertical-align: top;padding-left: 5px'><div id='images-status'></div></td>";
    $html[]="<td style='width:33%;vertical-align: top;padding-left: 5px'><div id='dhcp-status'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:100%;vertical-align: top' colspan='3'><div id='global-config'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

    $APP_FIRECRACKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");


    $topbuttons=array();
    $TINY_ARRAY["TITLE"]="{APP_FIRECRACKER} v$APP_FIRECRACKER_VERSION";
    $TINY_ARRAY["ICO"]="fas fa-tachometer-alt";
    $TINY_ARRAY["EXPL"]="{APP_FIRECRACKER_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $js=$tpl->RefreshInterval_js("firecracker-status",$page,"service-status=yes");

    echo "<script>
        $jstiny
        LoadAjaxSilent('global-config','$page?global-config=yes');
        LoadAjaxSilent('vmlinux-status','$page?vmlinux-status=yes');
        LoadAjaxSilent('images-status','$page?images-status=yes');
        LoadAjaxSilent('dhcp-status','$page?dhcp-status=yes');
        
        $js
        </script>";
	return false;
	

}
function status(){
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API('/firecracker/status'));

    $btn["ico"]=ico_support;
    $btn["name"]="{help}";
    $btn["js"]="s_PopUpFull('https://wiki.articatech.com/en/micronodes',1024,768,'Wiki');";

    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg(),$btn));
        return false;

    }
    if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error ,$btn));
            return false;
        }


    $jrestart=$tpl->framework_buildjs("/firecracker/restart", "firecracker.progress","firecracker.log","progress-firecracker-restart");

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $page=CurrentPageName();
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_FIRECRACKER",$jrestart);
    $html[]="<script>";
    $html[]=" LoadAjaxSilent('dhcp-status','$page?dhcp-status=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function vmlinux_status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_FIRECR('/status'));

    $btn["ico"]=ico_support;
    $btn["name"]="{help}";
    $btn["js"]="s_PopUpFull('https://wiki.articatech.com/en/micronodes',1024,768,'Wiki');";

    if(!$json->Status) {
        echo $tpl->widget_h("red",ico_engine_warning,"{error}","Kernel version",$btn);
        return false;
    }

    echo $tpl->widget_h("green",ico_engine,$json->VMLinuxVersion,"Kernel version",$btn);
    return true;
}
function dhcp_status():bool{

    $btn["ico"]=ico_support;
    $btn["name"]="{help}";
    $btn["js"]="s_PopUpFull('https://wiki.articatech.com/en/micronodes',1024,768,'Wiki');";
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_FIRECR('/dhcp/status'));
    if(!$json->Status) {
        echo $tpl->widget_h("red",ico_networks,"{error}","{APP_DHCP}",$btn);
        return false;
    }

    $btn["ico"]=ico_retweet;
    $btn["name"]="{restart}";
    $btn["js"]=$tpl->framework_buildjs("firecrack:/dhcp/restart","firecracker.progress","firecracker.log","progress-firecracker-restart");


    $Class=$json->Info;
    if( !$Class->running){
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("red",ico_networks,"{stopped}","{APP_DHCP}",$btn));
        return false;
    }
    //$mem=FormatBytes($json->memory_kb);
    echo $tpl->_ENGINE_parse_body($tpl->widget_h("green",ico_networks,"{running}","{APP_DHCP}",$btn));

    return true;
}

function images_status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_FIRECR('/status'));

    if(!$json->Status) {
        echo $tpl->widget_h("red",ico_cd,"{error}","Kernel version");
        return false;
    }
    $c=0;
    foreach ($json->Images as $image) {
        $c++;
    }

    $btn["ico"]=ico_support;
    $btn["name"]="{help}";
    $btn["js"]="s_PopUpFull('https://wiki.articatech.com/en/micronodes',1024,768,'Wiki');";

    if($c==0) {
        echo $tpl->widget_h("grey", ico_cd, 0, "{images}",$btn);
        return true;
    }
    echo $tpl->widget_h("green", ico_cd, $c, "{images}",$btn);
    return true;
}
function flat_config():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $FCDHCPInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPInterface");
    $FCDHCPStart=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPStart");
    $FCDHCPEnd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPEnd");
    $FCDHCPSubnet=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPSubnet");
    $FCDHCPGateway=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPGateway");
    $FCDHCPDNS1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPDNS1");
    $FCDHCPDNS2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPDNS2");
    $FCDHCPDomain=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPDomain");
    $FCDHCPInterfaceBool=false;
    if($FCDHCPInterface==null){
        echo $tpl->div_error("{error_no_virtual_switch}");
        $FCDHCPInterfaceBool=true;
    }

    $tpl->table_form_section("DHCP");
    $tpl->table_form_field_js("Loadjs('$page?dhcp-js=yes')");
    $tpl->table_form_field_text("{virtual_switch}",$FCDHCPInterface,ico_nic,$FCDHCPInterfaceBool);
    $tpl->table_form_field_text("{ipfrom}",$FCDHCPStart,ico_computer);
    $tpl->table_form_field_text("{ipto}",$FCDHCPEnd,ico_computer);
    $tpl->table_form_field_text("{gateway}",$FCDHCPGateway,ico_sensor,);
    $tpl->table_form_field_text("{netmask}",$FCDHCPSubnet,ico_networks);
    $tpl->table_form_field_text("{DNSServer} 1",$FCDHCPDNS1,ico_database);
    $tpl->table_form_field_text("{DNSServer} 2",$FCDHCPDNS2,ico_database);
    $tpl->table_form_field_text("{ddns_domainname}",$FCDHCPDomain,ico_earth);
    echo $tpl->table_form_compile();
    return true;
}

function dhcp_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{APP_DHCP} {general_settings}","$page?dhcp-popup=yes");
}

function dhcp_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $FCDHCPInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPInterface");
    $FCDHCPStart=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPStart");
    $FCDHCPEnd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPEnd");
    $FCDHCPSubnet=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPSubnet");
    $FCDHCPGateway=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPGateway");
    $FCDHCPDNS1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPDNS1");
    $FCDHCPDNS2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPDNS2");
    $FCDHCPDomain=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FCDHCPDomain");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_FIRECR("/switch/list"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $switchs=array();
    foreach ($json->switchs as $switch){
        $switchs[$switch]=$switch;
    }
    if( count($switchs)==0){
        echo $tpl->div_error("{error_no_virtual_switch}");
        return false;
    }
    $buttonname="{apply}";
    $form[]=$tpl->field_array_hash($switchs,"FCDHCPInterface","{virtual_switch}",$FCDHCPInterface,true);
    $form[]=$tpl->field_ipaddr("FCDHCPStart", "{ipfrom}",  $FCDHCPStart);
    $form[]=$tpl->field_ipaddr("FCDHCPEnd", "{ipto}", $FCDHCPEnd);
    $form[]=$tpl->field_ipaddr("FCDHCPSubnet", "{netmask}",$FCDHCPSubnet);
    $form[]=$tpl->field_ipaddr("FCDHCPGateway", "{gateway}",  $FCDHCPGateway);
    $form[]=$tpl->field_ipaddr("FCDHCPDNS1", "{DNSServer} 1", $FCDHCPDNS1);
    $form[]=$tpl->field_ipaddr("FCDHCPDNS2", "{DNSServer} 2", $FCDHCPDNS2);
    $form[]=$tpl->field_text("FCDHCPDomain", "{ddns_domainname}",  $FCDHCPDomain);

    $js[]="LoadAjaxSilent('global-config','$page?global-config=yes');";
    $js[]="dialogInstance2.close();";

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,$buttonname,@implode(";",$js),"AsVirtualBoxManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dhcp_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save DHCP configuration for MicroVMs");

}