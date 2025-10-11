<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_POST["DHCPRelayInterface"])){Save();exit;}
if(isset($_GET["main"])){main_page();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["flat-config"])){flat_config();exit;}
if(isset($_GET["settings-js"])){settings_js();exit;}
if(isset($_GET["settings-popup"])){settings_popup();exit;}


page();
function page():bool{
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    $DHCPDVersion   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");

    $html=$tpl->page_header("{APP_DHCP_RELAY} v$DHCPDVersion","fas fa-exchange","{APP_DHCP_RELAY_EXPLAIN}","$page?main=yes","dhcprelay","progress-dhcprelay-restart",false,"table-loader-dhcprelay-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DHCP_RELAY}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save(){
    $tpl                = new template_admin();
    $tpl->SAVE_POSTs();

}

function main_page(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();

    $html[]="<table style='width:100%;margin-top:10px'>
	<tr>
		<td valign='top' style='width:350px;vertical-align-top' class='center'><div id='service-status'></div></td>
		<td valign='top'><div id='flat-config'></div></td>";
    $html[]="</tr></table>";
    $html[]="<script>";
    $html[]="LoadAjax('service-status','$page?service-status=yes');";
    $html[]="LoadAjax('flat-config','$page?flat-config=yes');";
    $html[]="</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function flat_config():bool{

    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $DHCPRelayInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayInterface"));
    $DHCPRelayUPInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayUPInterface"));
    $DHCPRelayDoInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayDoInterface"));
    $DHCPRelayServers   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayServers"));

    if($DHCPRelayInterface==""){
        $DHCPRelayInterface="{none}";
    }
    if($DHCPRelayUPInterface==""){
        $DHCPRelayUPInterface="{none}";
    }
    if($DHCPRelayDoInterface==""){
        $DHCPRelayDoInterface="{none}";
    }
    $ipClass=new IP();
    $SERVER_ARRAY=array();
    $SERVER_TEXT="{none}";
    if($DHCPRelayServers<>null){
        $tbl=explode("\n",$DHCPRelayServers);
        foreach ($tbl as $ip){
            if($ip==null){continue;}
            if(!$ipClass->isValid($ip)){continue;}
            $SERVER_ARRAY[]=$ip;
        }

    }
    if(count($SERVER_ARRAY)>0){
        $SERVER_TEXT=@implode(",",$SERVER_ARRAY);
    }

    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes');");
    $tpl->table_form_field_text("{listen_interfaces}",$DHCPRelayInterface,ico_nic);
    $tpl->table_form_field_text("{upstream_network_interfaces}",$DHCPRelayUPInterface,ico_nic);
    $tpl->table_form_field_text("{downstream_network_interfaces}",$DHCPRelayDoInterface,ico_nic);
    $tpl->table_form_field_text("{DHCP_SERVERS}",$SERVER_TEXT,ico_server);

    $s_PopUp="s_PopUp('https://wiki.articatech.com/network/dhcp/dhcp-relay','1024','800')";
    $topbuttons[] = array($s_PopUp,ico_support, "WIKI URL");


    $DHCPDVersion   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");
    $TINY_ARRAY["ICO"]="fas fa-exchange";
    $TINY_ARRAY["TITLE"]="{APP_DHCP_RELAY} v$DHCPDVersion";
    $TINY_ARRAY["EXPL"]="{APP_DHCP_RELAY_EXPLAIN} ";
    $TINY_ARRAY["URL"]="dhcprelay";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["DANGER"]=false;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    echo $tpl->table_form_compile();
    echo "<script>$jstiny</script>";
    return true;

}
function settings_js():bool{

    $page               = CurrentPageName();
    $tpl                = new template_admin();
    return $tpl->js_dialog1("{general_settings}","$page?settings-popup=yes");
}
function settings_popup():bool{

    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $tpl->CLUSTER_CLI   = True;
    $DHCPRelayInterface = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayInterface");
    $DHCPRelayServers   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayServers"));
    $DHCPRelayUPInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayUPInterface"));
    $DHCPRelayDoInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPRelayDoInterface"));



    $form[]=$tpl->field_interfaces_choose("DHCPRelayInterface", "{listen_interfaces}", $DHCPRelayInterface);
    $form[]=$tpl->field_interfaces_choose("DHCPRelayUPInterface", "{upstream_network_interfaces}", $DHCPRelayUPInterface);
    $form[]=$tpl->field_interfaces_choose("DHCPRelayDoInterface", "{downstream_network_interfaces}", $DHCPRelayDoInterface);
    $form[]=$tpl->field_textareacode("DHCPRelayServers","{DHCP_SERVERS}",$DHCPRelayServers,"{DHCP_SERVERS_EXPLAIN}");




    $jsrestart=$tpl->framework_buildjs("/dhcpd/relay/restart",
        "dhcrelay.progress","dhcrelay.restart.log",
        "progress-dhcprelay-restart","LoadAjax('service-status','$page?service-status=yes');");


    $html[]=$tpl->form_outside(null, $form,null,"{apply}","LoadAjax('flat-config','$page?flat-config=yes');dialogInstance1.close();$jsrestart","AsSystemAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function service_status():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();


    $jsrestart=$tpl->framework_buildjs("/dhcpd/relay/restart",
        "dhcrelay.progress","dhcrelay.restart.log",
        "progress-dhcprelay-restart","LoadAjax('service-status','$page?service-status=yes');");
    $ini                = new Bs_IniHandler();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcpd/relay/status"));
    $ini->loadString($json->Info);
    echo $tpl->SERVICE_STATUS($ini, "APP_DHCP_RELAY",$jsrestart);
    return true;
}