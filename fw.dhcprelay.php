<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_POST["dhcprelay-agent"])){agent_save();exit;}
if(isset($_GET["agent-js"])){agent_js();exit;}
if(isset($_GET["agent-popup"])){agent_popup();exit;}
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

    $tpl->table_form_section("{general_settings}");
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes');");
    $tpl->table_form_field_text("{listen_interfaces}",$DHCPRelayInterface,ico_nic);
    $tpl->table_form_field_text("{upstream_network_interfaces}",$DHCPRelayUPInterface,ico_nic);
    $tpl->table_form_field_text("{downstream_network_interfaces}",$DHCPRelayDoInterface,ico_nic);
    $tpl->table_form_field_text("{DHCP_SERVERS}",$SERVER_TEXT,ico_server);
    agent_flat($tpl,$page);

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


    echo $tpl->table_form_compile(2);
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

// ---- Option 82 (relay agent information) --------------------------------
// Réglages validés et enregistrés par articarest (POST /dhcpd/relay/agent,
// dhcpd/dhcpdrelay/agentopts.go) : la page ne valide rien elle-même.

function agent_state():array{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcpd/relay/agent"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["agent"]) || !is_array($json["agent"])){
        return array();
    }
    return $json["agent"];
}

function agent_policies():array{
    return array("replace"=>"{dhcprelay_policy_replace}","append"=>"{dhcprelay_policy_append}",
        "forward"=>"{dhcprelay_policy_forward}","discard"=>"{dhcprelay_policy_discard}");
}
function agent_rid_types():array{
    return array("none"=>"{dhcprelay_rid_none}","hostname"=>"{dhcprelay_rid_hostname}",
        "giaddr"=>"{dhcprelay_rid_giaddr}","custom"=>"{dhcprelay_rid_custom}");
}

function agent_flat($tpl,$page):bool{
    $tpl->table_form_section("{dhcprelay_option82}");
    $st=agent_state();
    if(count($st)==0){
        // Route absente : articarest plus ancien que la fonction.
        $tpl->table_form_field_js("");
        $tpl->table_form_field_warn("{dhcprelay_option82_unavailable}");
        return false;
    }
    $s=$st["settings"];
    $enabled=!empty($s["agent_options"]);
    $policies=agent_policies();
    $types=agent_rid_types();
    $policy=isset($policies[$s["policy"]])?$policies[$s["policy"]]:$s["policy"];
    $type=isset($types[$s["remote_id_type"]])?$types[$s["remote_id_type"]]:$s["remote_id_type"];

    $tpl->table_form_field_js("Loadjs('$page?agent-js=yes');");
    $tpl->table_form_field_bool("{dhcprelay_add_option82}",$enabled?1:0,ico_params);
    if(!$enabled){return true;}
    $tpl->table_form_field_text("{dhcprelay_policy}",$policy,ico_params);
    if($s["remote_id_type"]=="custom"){
        $type=$type.": ".htmlspecialchars((string)$s["remote_id"]);
    }
    $tpl->table_form_field_text("{dhcprelay_remote_id}",$type,ico_server);
    $cids=array();
    if(isset($s["circuit_ids"]) && is_array($s["circuit_ids"])){
        ksort($s["circuit_ids"]);
        foreach($s["circuit_ids"] as $iface=>$value){
            $cids[]=htmlspecialchars($iface)." = ".htmlspecialchars((string)$value);
        }
    }
    $cidtext=count($cids)==0?"{dhcprelay_circuit_default}":@implode(", ",$cids);
    $tpl->table_form_field_text("{dhcprelay_circuit_ids}",$cidtext,ico_nic);

    $wantsIDs=$s["remote_id_type"]!="none" || count($cids)>0;
    if($wantsIDs && empty($st["supported"])){
        $tpl->table_form_field_warn("{dhcprelay_option82_unsupported}");
    }
    if(isset($st["ignored_circuit_ids"]) && is_array($st["ignored_circuit_ids"]) && count($st["ignored_circuit_ids"])>0){
        $list=htmlspecialchars(@implode(", ",$st["ignored_circuit_ids"]));
        $tpl->table_form_field_warn("{dhcprelay_circuit_ignored}: $list");
    }
    return true;
}

function agent_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{dhcprelay_option82}","$page?agent-popup=yes",750);
}

function agent_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $st=agent_state();
    if(count($st)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{dhcprelay_option82_unavailable}"));
        return false;
    }
    $s=$st["settings"];
    $cids=array();
    if(isset($s["circuit_ids"]) && is_array($s["circuit_ids"])){
        ksort($s["circuit_ids"]);
        foreach($s["circuit_ids"] as $iface=>$value){$cids[]="$iface=$value";}
    }
    $form[]=$tpl->field_hidden("dhcprelay-agent","yes");
    $form[]=$tpl->field_checkbox("agent_options","{dhcprelay_add_option82}",empty($s["agent_options"])?0:1,false,"{dhcprelay_add_option82_explain}");
    $form[]=$tpl->field_array_hash(agent_policies(),"policy","{dhcprelay_policy}",$s["policy"],true,"{dhcprelay_policy_explain}");
    $form[]=$tpl->field_array_hash(agent_rid_types(),"remote_id_type","{dhcprelay_remote_id}",$s["remote_id_type"],true,"{dhcprelay_remote_id_explain}");
    $form[]=$tpl->field_text("remote_id","{dhcprelay_remote_id_value}",(string)$s["remote_id"],false,"{dhcprelay_remote_id_value_explain}");
    $form[]=$tpl->field_textareacode("circuit_ids","{dhcprelay_circuit_ids}",@implode("\n",$cids),"{dhcprelay_circuit_ids_explain}");

    $html[]=$tpl->div_explain("{dhcprelay_option82}||{dhcprelay_option82_explain}");
    if(empty($st["supported"])){
        $html[]=$tpl->div_warning("{dhcprelay_option82_unsupported}");
    }
    $html[]=$tpl->form_outside("",$form,"","{apply}",
        "dialogInstance1.close();LoadAjaxSilent('flat-config','$page?flat-config=yes');LoadAjaxSilent('service-status','$page?service-status=yes');",
        "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function agent_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        echo $tpl->post_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $tpl->CLEAN_POST();
    $cids=array();
    foreach(explode("\n",str_replace("\r","",(string)($_POST["circuit_ids"] ?? ""))) as $line){
        $line=trim($line);
        if($line=="" || substr($line,0,1)=="#"){continue;}
        $pos=strpos($line,"=");
        if($pos===false){$cids[$line]="";continue;}
        $cids[trim(substr($line,0,$pos))]=trim(substr($line,$pos+1));
    }
    $data=array(
        "agent_options"=>intval($_POST["agent_options"] ?? 0)==1,
        "policy"=>(string)($_POST["policy"] ?? "replace"),
        "remote_id_type"=>(string)($_POST["remote_id_type"] ?? "none"),
        "remote_id"=>(string)($_POST["remote_id"] ?? ""),
        // objet JSON même vide : un tableau PHP vide deviendrait [] et Go refuserait.
        "circuit_ids"=>(object)$cids,
    );
    $resp=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/dhcpd/relay/agent",$data);
    $json=json_decode($resp,true);
    if(!is_array($json)){
        $raw=trim(str_replace(array("'","\\"),array(" "," "),$resp));
        echo $tpl->post_error($raw==""?"{error}":substr($raw,0,300));
        return false;
    }
    if(empty($json["Status"])){
        echo $tpl->post_error(isset($json["Error"])?(string)$json["Error"]:"{error}");
        return false;
    }
    admin_tracks("DHCP relay: option 82 settings saved (agent_options=".($data["agent_options"]?1:0).
        ", policy={$data["policy"]}, remote_id_type={$data["remote_id_type"]}, circuit_ids=".count($cids).")");
    return true;
}

