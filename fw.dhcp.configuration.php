<?php
if(isset($_POST["none"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["none"])){die();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["dhcprelay-new-js"])){dhcp_relay_new_js();exit;}
if(isset($_GET["dhcprelay-new-popup"])){dhcp_relay_new_popup();exit;}
if(isset($_POST["DHCPRelayNew"])){dhcp_relay_new_save();exit;}

if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["table-config"])){table_config();exit;}
if(isset($_POST["AgentInterface"])){DHCPAgent_save();exit;}
if(isset($_GET["AsDHCPAgent"])){AsDHCPAgent();exit;}
if(isset($_GET["main-button"])){main_button();exit;}
if(isset($_GET["interface"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["router-interface"])){gateway_save();exit;}
if(isset($_GET["gateway-js"])){gateway_js();exit;}
if(isset($_GET["gateway-popup"])){gateway_popup();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_POST["configfile"])){config_file_save();exit;}
if(isset($_GET["flush-leases"])){flush_leases_ask();exit;}
if(isset($_GET["flush-leases-confirm"])){flush_leases_confirm();exit;}
if(isset($_GET["spt-js"])){support_tool_js();exit;}
if(isset($_GET["start"])){page_start();exit;}
if(isset($_GET["spopup-interface"])){spopup_interface();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");

    $html=$tpl->page_header("{APP_DHCP} v$DHCPDVersion","fas fa-cogs","{EnableDHCPServer_text}",
        "$page?start=yes","dhcp-config","progress-dhcp-restart",false,"table-start-dhcp-service");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_DHCP} v$DHCPDVersion",$html);
		echo $tpl->build_firewall();
		return true;
	}
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dhcp_relay_new_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog3("{new_dhcp_relay_rule}","$page?dhcprelay-new-popup=yes");
}
function dhcp_relay_new_popup():bool{
    $security = "ASDCHPAdmin";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $form[]=$tpl->field_hidden("DHCPRelayNew","yes");
    $form[]=$tpl->field_interfaces("interface","{listen_interface}");
    $form[]=$tpl->field_text("rulename","{rulename}","",true);
    $html[]=$tpl->form_outside("", $form,null,"{create}","dialogInstance3.close();LoadAjax('kea-lists','$page?interfaces=yes');",$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dhcp_relay_new_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $interface=$_POST["interface"];

    if($interface=="lo") {
        echo $tpl->post_error("lo Incorrect");
        return false;
    }
    if($interface=="") {
        echo $tpl->post_error("Interface Null Incorrect");
        return false;
    }

    $rulename=$_POST["rulename"];
    $q->QUERY_SQL("INSERT INTO dhcpd_relays (interface,rulename) VALUES ('$interface','$rulename')");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Create a new DHCP relay rule $rulename for $interface");
}
function interfaces():bool{

    $net=new networking();
    $interfaces=$net->Local_interfaces();
    $tpl=new template_admin();
    $page=CurrentPageName();


    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $c=0;
    foreach ($interfaces as $interface=>$Ipaddress){


        $nic=new system_nic($interface);

        $results=$q->QUERY_SQL("SELECT ID,rulename FROM dhcpd_relays WHERE interface='$interface'");
        if($results){
            foreach ($results as $ligne){
                $interfaceR="Relay:{$ligne["ID"]}";
                $dhcpSock=new dhcp_socks($interfaceR);
                $tpl->table_form_field_js("Loadjs('fw.dhcp.configuration.php?spopup-interface=$interfaceR')","ASDCHPAdmin");
                $service_enabled=intval($dhcpSock->GET("service_enabled"));
                $rulename=$ligne["rulename"];
                if($service_enabled==0){
                    $tpl->table_form_field_bool("$rulename",0,ico_nic);
                    continue;
                }
                $dhcpd=new dhcpd(0,1,$interfaceR);
                $RelayAddress=$dhcpSock->GET("RelayAddress");
                $CircuitID=$dhcpSock->GET("CircuitID");
                $AgentID=$dhcpSock->GET("AgentID");
                $tb=array();
                if(!is_null($RelayAddress)){
                    if(strlen($RelayAddress)>1) {
                        $tb[]=$RelayAddress;
                    }
                }
                if(strlen($CircuitID)>1) {
                    $tb[]=$CircuitID;
                }
                if(strlen($AgentID)>1) {
                    $tb[]=$AgentID;
                }
                if(count($tb)>0){
                    $interfaceR = "<br><small>{filter}: (".@implode(" {and} ",$tb).")</small>";
                }
                $tpl->table_form_field_text($rulename,"{nic}:$interface $interfaceR<br> $dhcpd->subnet/$dhcpd->netmask - $dhcpd->range1 - $dhcpd->range2<br><small>{receive_dhcp_requests_relay}</small>",ico_nic);
                $c++;
            }
        }
        $dhcpd=new dhcpd(0,1,$interface);
        $dhcpSock=new dhcp_socks($interface);
        $tpl->table_form_field_js("Loadjs('fw.dhcp.configuration.php?spopup-interface=$interface')","ASDCHPAdmin");
        if($dhcpd->service_enabled==0){
            $tpl->table_form_field_bool("$nic->NICNAME",0,ico_nic);
            continue;
        }
        $AsDHCPAgent=intval($dhcpSock->GET("AsDHCPAgent"));
        if($AsDHCPAgent==1) {
            $c++;
            $CircuitID = $dhcpSock->GET("CircuitID");
            $DHCPServers=$dhcpSock->GET("DHCPServers");
            $ico_fl=ico_arrow_right;
            $tpl->table_form_field_text($nic->NICNAME, "{APP_DHCP_RELAY} (Circuit: $CircuitID) <i class='$ico_fl'></i> $DHCPServers", ico_nic);
            continue;
        }

        $tpl->table_form_field_text($nic->NICNAME,"$dhcpd->subnet/$dhcpd->netmask - $dhcpd->range1 - $dhcpd->range2<br><small>{receive_dhcp_requests_directly}</small>",ico_nic);
        $c++;


    }

    if($c==0){
        echo $tpl->div_error("{err_dhcp_nocard}");
    }
    echo "<div style='padding:15px'>";
    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    echo "</div>";
    return true;
}

function page_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsReconFigure=$tpl->framework_buildjs(
        "/dhcpd/service/reconfigure","dhcpd.progress",
        "dhcpd.progress.log","progress-dhcp-restart","","","","ASDCHPAdmin");

    $DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");
    $topbuttons[] = array($jsReconFigure, ico_refresh, "{reconfigure}");
    $topbuttons[] = array("Loadjs('$page?dhcprelay-new-js=yes')",ico_sensor,"{new_dhcp_relay_rule}");


    $TINY_ARRAY["TITLE"]="{APP_DHCP} v$DHCPDVersion";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{EnableDHCPServer_text}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $Interval=$tpl->RefreshInterval_js("dhcp-service",$page,"status=yes",3);
$html="<table style='width:100%'>
				<tr>
					<td style='width:220px;vertical-align:top'><div id='dhcp-service'></div></td>
					<td style='width:78%;padding-left:15px;vertical-align:top'><div id='table-loader-dhcp-service'></div></td>
				</tr>
			</table>
<script>
	LoadAjax('table-loader-dhcp-service','$page?interfaces=yes');
    $jstiny
	$Interval
</script>";
echo $html;
return true;

}
function spopup_interface():bool{
    $iface=$_GET["spopup-interface"];
    $nic=new system_nic($iface);
    $tpl=new template_admin();
    $page   = CurrentPageName();
    return $tpl->js_dialog1($nic->NICNAME,"$page?interface=$iface");
}

function support_tool_js():bool{

    $tpl=new template_admin();
    $after="document.location.href='/ressources/logs/web/dhcpd.support.tar.gz'";
    $js=$tpl->framework_buildjs("/dhcpd/service/support",
        "dhcpd.tool","dhcpd.tool.log","progress-dhcp-restart",$after);
   return  $tpl->js_confirm_execute("{generate_support_tool_ask}", "none", "none",$js);
}

function flush_leases_ask(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $tpl->js_dialog_confirm_action("{Clear_DHCP_Leases}","none","none","Loadjs('$page?flush-leases-confirm=yes')");

}

function flush_leases_confirm(){
    $tpl=new template_admin();
    $jsafter=$tpl->framework_buildjs("/dhcpd/leases/flush",
        "dhcdp.leases.empty.progress","dhcdp.leases.empty.progress.txt","progress-dhcp-restart");

    header("content-type: application/x-javascript");
    echo "$jsafter\n";

}

function gateway_js(){

    $Interface=$_GET["gateway-js"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{multiple_gateways}: $Interface","$page?gateway-popup=$Interface");

}

function gateway_popup(){
    $tpl=new template_admin();
    $security = "ASDCHPAdmin";
    $Interface=$_GET["gateway-popup"];
    $dhcp_socks=new dhcp_socks($Interface);
    $routers=$dhcp_socks->GET("routers");
    $DATA=explode(",",$routers);

    $form[]=$tpl->field_textareacode("routers","{routers}",@implode("\n",$DATA));
    $tpl->field_hidden("router-interface",$Interface);
    echo $tpl->form_outside("{multiple_gateways}",$form,null,"{apply}","dialogInstance2.close();",$security);
}

function gateway_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Interface=$_POST["router-interface"];
    $dhcp_socks=new dhcp_socks($Interface);
    $explode=explode("\n",$_POST["routers"]);
    $line=$dhcp_socks->CleanIpaddr1(@implode(",",$explode));
    $dhcp_socks->SET("routers",$line);
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{default_service}"]="$page?interface=default";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT * FROM `nics` WHERE enabled=1 ORDER BY Interface");
    if(count($results)>1){
        foreach ($results as $index=>$ligne){
            $Interface=$ligne["Interface"];
            $NICNAME=$ligne["NICNAME"];
            $array["$NICNAME ($Interface)"]="$page?interface=$Interface";
        }

    }
    echo $tpl->tabs_default($array);
}

function main_button(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $MainInterface=$_GET["main-button"];
    $dhcp       = new dhcp_socks($MainInterface);
    $AsDHCPAgent=intval($dhcp->GET("AsDHCPAgent"));


    if($AsDHCPAgent==1){
        $serverButton= $tpl->button_autnonome("{server_mode}","Loadjs('$page?AsDHCPAgent=0&interface=$MainInterface')",ico_server,"AsSystemAdministrator",335,"btn-default");
        $clientButton=$tpl->button_autnonome("{APP_DHCP_RELAY}","blur()",ico_sensor,"AsSystemAdministrator",335,"btn-primary");
    }else{
        $serverButton= $tpl->button_autnonome("{server_mode}","blur()",ico_server,"AsSystemAdministrator",335,"btn-primary");
        $clientButton=$tpl->button_autnonome("{APP_DHCP_RELAY}","Loadjs('$page?AsDHCPAgent=1&interface=$MainInterface')",ico_sensor,"AsSystemAdministrator",335,"btn-default");
    }

    $html[]="<table style='margin-top:10px;width: 100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;text-align:center;padding:10px'>$serverButton</td>";
    $html[]="<td style='width:50%;text-align:center;padding: 10px'>$clientButton</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function AsDHCPAgent():bool{
    $page               = CurrentPageName();
    $interface=$_GET["interface"];
    $AsDHCPAgent=intval($_GET["AsDHCPAgent"]);
    $dhcp       = new dhcp_socks($interface);
    $dhcp->SET("AsDHCPAgent",$AsDHCPAgent);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcphelper/check");
    $js[]="LoadAjaxSilent('kea-main-button','$page?main-button=$interface');";
    $js[]="LoadAjaxSilent('DHCP-MAIN-CONFIG','$page?table-config=$interface');";
    echo @implode("\n",$js);
    return true;
}
function table(){
    $MainInterface      = $_GET["interface"];
    $page               = CurrentPageName();
    echo "<div id='DHCP-MAIN-CONFIG'></div>
        <script>LoadAjaxSilent('DHCP-MAIN-CONFIG','$page?table-config=$MainInterface');</script>";
}

function nico_infos($MainInterface):string{

    if(preg_match("#Relay:([0-9]+)#",$MainInterface,$matches)){
        $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        VERBOSE("ID:$matches[1]",__LINE__);
        $ligne=$q->mysqli_fetch_array("SELECT interface FROM dhcpd_relays WHERE ID=$matches[1]");
        if(!$q->ok){
            echo $q->mysql_error;
        }
        $MainInterface=$ligne["interface"];
    }

    $dhcp       = new dhcpd(0, 1, $MainInterface);

    if ($MainInterface == "default") {
        if ($dhcp->listen_nic == null) {
            $dhcp->listen_nic = "eth0";
        }
    }else{
        $dhcp->listen_nic = $MainInterface;
    }


    $nic_info=array();
    $nic_info[]="<div class='widget navy-bg no-padding' style='margin-top:10px'>
<div class='p-m'>
    <table style='width:20%;'>";
    $nic_info[]="<tr>";
    $nic_info[]="<td style='width:1%;vertical-align: top' nowrap><i class='fas fa-ethernet fa-5x'></i></td>";
    $nic_info[]="<td style='width:99%;padding-left:10px;vertical-align: top'>";
    $nic_info[]="<table style='width:100%'>";
    $nicz=new system_nic($dhcp->listen_nic);
    if($dhcp->EnableVLANs==1 && $dhcp->vlan_id>0){

        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $sql="SELECT * FROM nics_vlan WHERE ID=$dhcp->vlan_id and nic='$dhcp->listen_nic'"; //
        $results=$q->mysqli_fetch_array($sql);
        $vlanid=intval($results["vlanid"]);
        if($vlanid>0) {
            $eth_text = "vlan{$results["ID"]}";
            $nicz->NICNAME = $nicz->NICNAME . " - $eth_text tag $vlanid";
            $nicz->IPADDR = $results["ipaddr"];
            $nicz->NETMASK = $results["netmask"];
            $nicz->netzone = "$nicz->netzone/$eth_text";
        }
    }
    $nic_info[]="<tr>";
    $nic_info[]="<td style='vertical-align: top' colspan='2' nowrap=''>";
    $nic_info[]="<h2>{netzone}: $nicz->netzone</h2></td>";
    $nic_info[]="</tr>";
    $nic_info[]="<tr>";
    $nic_info[]="<td style='width:50%;text-align:right;vertical-align: top'>";
    $nic_info[]="<strong>{name}:</strong></td>";
    $nic_info[]="<td style='vertical-align: top'  nowrap=''><strong>$nicz->NICNAME</strong></td>";
    $nic_info[]="</tr>";
    $nic_info[]="<tr>";
    $nic_info[]="<td style='width:50%;text-align:right;vertical-align: top' nowrap=''>";
    $nic_info[]="<strong>{tcp_address}:</strong></td>";
    $nic_info[]="<td style='vertical-align: top' ><strong>$nicz->IPADDR/$nicz->NETMASK</strong></td>";
    $nic_info[]="</tr>";
    $nic_info[]="</td>";
    $nic_info[]="</tr>";
    $nic_info[]="</table>";
    $nic_info[]="</td>";
    $nic_info[]="</tr>";
    $nic_info[]="</table></div></div>";
    $nic_info[]="<div id='kea-main-button'></div>";
    return @implode("\n",$nic_info);
}

function table_config(){
    $DEFAULT_SERVICE    = false;
    $error              = null;
    $MainInterface      = $_GET["table-config"];
    $page               = CurrentPageName();
    $ldap               = new clladp();
    $domains            = $ldap->hash_get_all_domains();
    $tpl                = new template_admin();
    $dhcpSock       = new dhcp_socks($MainInterface);
    $users              = new usersMenus();
    $buttonname         = "{apply}";
    $ASRELAY             = false;
    $EnableDHCPServer   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    $EnableDHCPUseHostnameOnFixed = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPUseHostnameOnFixed"));
    $IncludeDHCPLdapDatabase = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IncludeDHCPLdapDatabase"));
    $DHCPDAutomaticFixIPAddresses = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDAutomaticFixIPAddresses"));
    $DHCPAddNewComputers = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPAddNewComputers"));
    $DHCPDInPowerDNS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInPowerDNS"));
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $AsDHCPAgent=intval($dhcpSock->GET("AsDHCPAgent"));
    $SWITCHBUTTON=true;
    if (preg_match("#Relay:[0-9]+#", $MainInterface)) {
        $SWITCHBUTTON = false;
        $ASRELAY = true;
    }


    if ($MainInterface == "default") {$DEFAULT_SERVICE = true;}
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $ligne=$q->mysqli_fetch_array("SELECT `interface` FROM `dhcpd` WHERE `key`='listen_nic' AND `val`='$MainInterface'");
    if($ligne["interface"]=="default"){
        $q->QUERY_SQL("DELETE FROM  `dhcpd` WHERE `interface`='default'");
        $ligne["interface"]="";
    }



    $security = "ASDCHPAdmin";
    if ($EnableDHCPServer == 0) {$security = "disabled";}
    $dhcp       = new dhcpd(0, 1, $MainInterface);
    if ($dhcp->ddns_domainname == null) {
        $dhcp->ddns_domainname = "home.lan";
    }


    if ($MainInterface == "default") {
        if ($dhcp->listen_nic == null) {
            $dhcp->listen_nic = "eth0";
        }
    }else{
        $dhcp->listen_nic = $MainInterface;
    }

    $nicz=new system_nic($dhcp->listen_nic);
    $nic_text=nico_infos($MainInterface);

    preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$nicz->IPADDR,$re);
    if(!preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$dhcp->range2)){$dhcp->range2=null;}
	
	if($dhcp->subnet==null){
		$dhcp->subnet="$re[1].$re[2].$re[3].0";
	}
	if($dhcp->netmask==null){
		$dhcp->netmask=$nicz->NETMASK;
	}
	if($dhcp->gateway==null){
		$dhcp->gateway=$nicz->GATEWAY;
	}
	if($dhcp->range1==null){
		$dhcp->range1="$re[1].$re[2].$re[3].20";
	}
	if($dhcp->range2==null){
		$dhcp->range2="$re[1].$re[2].$re[3].252";
	}
	if($dhcp->broadcast==null){
		$dhcp->broadcast="$re[1].$re[2].$re[3].255";
	}
	if($users->POWER_DNS_INSTALLED){
		$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
		if($EnablePDNS==1){
			$form[]=$tpl->field_checkbox("DHCPDInPowerDNS","{DHCPDInPowerDNS}",$DHCPDInPowerDNS,false,"{DHCPDInPowerDNS_explain}");
		}
	}
	$tpl->field_hidden("MainInterface",$MainInterface);


    if($DEFAULT_SERVICE) {
        $form[] = $tpl->field_interfaces("listen_nic", "{listen_interface}", $dhcp->listen_nic, false);
        if($dhcp->EnableVLANs==1) {
            $form[] = $tpl->field_interfaces_vlan($dhcp->listen_nic,"vlan_id","{listen_interface} (VLAN)",$dhcp->vlan_id);
        }

        $form[] = $tpl->field_checkbox("IncludeDHCPLdapDatabase", "{dhcp_use_local_database}", $IncludeDHCPLdapDatabase, "DHCPAddNewComputers,DHCPDAutomaticFixIPAddresses,deny_unkown_clients", "{dhcp_use_local_database_explain}");
        $form[] = $tpl->field_checkbox("DHCPAddNewComputers",
            "{DHCPAddNewComputers}", $DHCPAddNewComputers, false, "{DHCPAddNewComputers_explain}");
        $form[] = $tpl->field_checkbox("DHCPDAutomaticFixIPAddresses",
            "{IncludeDHCPLdapDatabase}", $DHCPDAutomaticFixIPAddresses, false, "{DHCPDAutomaticFixIPAddresses_explain}");

        $form[] = $tpl->field_checkbox("EnableDHCPUseHostnameOnFixed",
            "{EnableDHCPUseHostnameOnFixed}", $EnableDHCPUseHostnameOnFixed, false, "{EnableDHCPUseHostnameOnFixed_explain}");

    }else{
        $tpl->field_hidden("listen_nic",$MainInterface);
        $tpl->field_hidden("vlan_id",$dhcp->vlan_id);
        $tpl->field_hidden("IncludeDHCPLdapDatabase",$IncludeDHCPLdapDatabase);
        $tpl->field_hidden("DHCPAddNewComputers",$DHCPAddNewComputers);
        $tpl->field_hidden("DHCPDAutomaticFixIPAddresses",$DHCPDAutomaticFixIPAddresses);
        $tpl->field_hidden("EnableDHCPUseHostnameOnFixed",$EnableDHCPUseHostnameOnFixed);
        $form[]=$tpl->field_checkbox("service_enabled","{service_enable}",$dhcp->service_enabled,true);
   }

    if(!$ASRELAY) {
        $form[] = $tpl->field_checkbox("DHCPauthoritative", "{authoritative}", $dhcp->authoritative, false, "{authoritativeDHCP_explain}");
    }


    if ($ASRELAY) {
        $CircuitID=$dhcpSock->GET("CircuitID");
        $AgentID=$dhcpSock->GET("AgentID");
        $RelayAddress=$dhcpSock->GET("RelayAddress");
        $form[]=$tpl->field_text("RelayAddress","{APP_DHCP_RELAY} {ipaddr}",$RelayAddress,true);
        $form[]=$tpl->field_text("CircuitID","Circuit ID",$CircuitID);
        $form[]=$tpl->field_text("AgentID","Agent ID",$AgentID);
    }


    $form[] = $tpl->field_checkbox("deny_unkown_clients", "{deny_unkown_clients}",
        $dhcp->deny_unkown_clients, false, "{deny_unkown_clients_explain}");

    if($EnableKEA==0){
        $form[] = $tpl->field_checkbox("DHCPPing_check", "{DHCPPing_check}", $dhcp->ping_check, false, "{DHCPPing_check_explain}");
        $form[]=$tpl->field_checkbox("get_lease_hostnames","{get_lease_hostnames}",$dhcp->get_lease_hostnames,false,"{get_lease_hostnames_text}");
    }

	
	$form[]=$tpl->field_section("{network_attribution}","{dhcp_network_attribution_explain}");
	$form[]=$tpl->field_checkbox("do_no_verify_range","{do_no_verify_range}",$dhcp->do_no_verify_range,false,"");
	$form[]=$tpl->field_ipaddr("subnet", "{subnet}", $dhcp->subnet);
	$form[]=$tpl->field_ipaddr("netmask", "{netmask}", $dhcp->netmask);
	$form[]=$tpl->field_ipaddr("range1", "{ipfrom}", $dhcp->range1);
	$form[]=$tpl->field_ipaddr("range2", "{ipto}", $dhcp->range2);


	$form[]=$tpl->field_section("{computers_parameters}");
	if(count($domains)==0){
		$form[]=$tpl->field_text("ddns_domainname", "{ddns_domainname}", $dhcp->ddns_domainname);
	
	}else{
		$form[]=$tpl->field_array_hash($domains, "ddns_domainname", "{ddns_domainname}", $dhcp->ddns_domainname);
	}

    $slabels[]="{hardware_attribution}";
    $sjs[]="Loadjs('fw.dhcp.configuration.hardware.php?interface=$MainInterface')";

	$slabels[]="{multiple_gateways}";
	$sjs[]="Loadjs('$page?gateway-js=$MainInterface')";

    $slabels[]="{routing_rules}";
    $sjs[]="Loadjs('fw.dhcp.configuration.routing.php?interface=$MainInterface')";

	$form[]=$tpl->field_ipaddr("gateway", "{gateway}", $dhcp->gateway,false,null,false,"Loadjs('$page?gateway-js=$MainInterface')");
	$form[]=$tpl->field_link($slabels,null,$sjs);

	$form[]=$tpl->field_ipaddr("broadcast", "{broadcast}", $dhcp->broadcast);
	$form[]=$tpl->field_ipaddr("DNS_1", "{DNSServer} 1", $dhcp->DNS_1);
	$form[]=$tpl->field_ipaddr("DNS_2", "{DNSServer} 2", $dhcp->DNS_2);
	$form[]=$tpl->field_ipaddr("WINSDHCPSERV", "{wins_server}", $dhcp->WINS);
	$form[]=$tpl->field_ipaddr("ntp_server", "{ntp_server} ({optional})", $dhcp->ntp_server);
	$form[]=$tpl->field_text("local-pac-server", "{wpad_label}", $dhcp->local_pac_server,false,"{wpad_label_text}");
	$form[]=$tpl->field_text("browser-portal-page", "{portal_page}",$dhcp->browser_portal_page,false,"{portal_page_explain}");
	
	$form[]=$tpl->field_section("{timeouts}");
	$form[]=$tpl->field_numeric("max_lease_time","{max_lease_time} ({seconds})",$dhcp->max_lease_time,"{max_lease_time_text}");


    $form[]=$tpl->field_section("PXE");
    $form[]=$tpl->field_checkbox("pxe_enable","{enable}",$dhcp->pxe_enable,"pxe_file,pxe_server","{EnablePXEDHCP}");
    $form[]=$tpl->field_text("pxe_file","{pxe_file}",$dhcp->pxe_file);
    $form[]=$tpl->field_ipv4("pxe_server","{pxe_server}",$dhcp->pxe_server);


    $html[]=$nic_text;
    if($AsDHCPAgent==1){
        $form=DHCPAgent_form($MainInterface);
    }

    $btonjs="";
	$html[]=$tpl->form_outside("",$form,null,$buttonname,restart_script(),$security);

    if($SWITCHBUTTON){
        $btonjs="LoadAjaxSilent('kea-main-button','$page?main-button=$MainInterface')";
    }


    $html[]="<script>";
    $html[]=$btonjs;
    $html[]="</script>";
	echo $error.$tpl->_ENGINE_parse_body($html);
	
}
function DHCPAgent_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $MainInterface=$_POST["AgentInterface"];
    $dhcpSock       = new dhcp_socks($MainInterface);
    foreach ($_POST as $key => $value) {
        $dhcpSock->SET($key,$value);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcphelper/check");
    return admin_tracks_post("Saving DHCP relay configuration");
}

function DHCPAgent_form($MainInterface):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $security = "ASDCHPAdmin";
    $dhcpSock       = new dhcp_socks($MainInterface);
    $service_enabled=intval($dhcpSock->GET("service_enabled"));
    $CircuitID=$dhcpSock->GET("CircuitID");
    $AgentID=$dhcpSock->GET("AgentID");

    if($CircuitID==""){
        $CircuitID=$MainInterface;
    }


    if($AgentID==""){
        $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $AgentID=$hostname;
    }
    $DHCPServers=$dhcpSock->GET("DHCPServers");
    $html[]="<div id='kea-main-button'></div>";
    $form[]=$tpl->field_hidden("AgentInterface",$MainInterface);
    $form[]=$tpl->field_checkbox("service_enabled","{service_enable}",$service_enabled,true);

    $form[]=$tpl->field_text("CircuitID","Circuit ID",$CircuitID);
    $form[]=$tpl->field_text("AgentID","Agent ID",$AgentID);
    $form[]=$tpl->field_text("DHCPServers","{DHCP_SERVERS}",$DHCPServers);
    $html[]=$tpl->form_outside("", $form,null,"{apply}",restart_script(),$security);

    $html[]="<script>";
    $html[]="LoadAjaxSilent('kea-main-button','$page?main-button=$MainInterface')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function dhcp_save(){

	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}
	$IPClass=new IP();

	$MainInterface=$_POST["MainInterface"];
	
	$dhcp=new dhcpd(0,1,$MainInterface);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO('EnableDHCPUseHostnameOnFixed',$_POST["EnableDHCPUseHostnameOnFixed"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IncludeDHCPLdapDatabase", $_POST["IncludeDHCPLdapDatabase"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPAddNewComputers", $_POST["DHCPAddNewComputers"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPDAutomaticFixIPAddresses", $_POST["DHCPDAutomaticFixIPAddresses"]);

    if(isset($_POST["DHCPDInPowerDNS"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPDInPowerDNS", $_POST["DHCPDInPowerDNS"]);
    }

    if(isset($_POST["service_enabled"])){
        $dhcp->service_enabled=$_POST["service_enabled"];
    }

	$dhcp->listen_nic=$_POST["listen_nic"];
	$dhcp->deny_unkown_clients=$_POST["deny_unkown_clients"];
	$dhcp->ddns_domainname=$_POST["ddns_domainname"];
	$dhcp->max_lease_time=$_POST["max_lease_time"];
	$dhcp->get_lease_hostnames=$_POST["get_lease_hostnames"];
	$dhcp->netmask=$_POST["netmask"];
	$dhcp->range1=$_POST["range1"];
	$dhcp->range2=$_POST["range2"];
	$dhcp->subnet=$_POST["subnet"];
	$dhcp->broadcast=$_POST["broadcast"];
	$dhcp->WINS=$_POST["WINS"];
	$dhcp->ping_check=$_POST["DHCPPing_check"];
	$dhcp->authoritative=$_POST["DHCPauthoritative"];
	$dhcp->local_pac_server=$_POST["local-pac-server"];
	$dhcp->browser_portal_page=$_POST["browser-portal-page"];
	$dhcp->gateway=$_POST["gateway"];
	$dhcp->DNS_1=$_POST["DNS_1"];
	$dhcp->DNS_2=$_POST["DNS_2"];
	$dhcp->ntp_server=$_POST["ntp_server"];
	$dhcp->pxe_enable=$_POST["pxe_enable"];
    $dhcp->pxe_file=$_POST["pxe_file"];
    $dhcp->pxe_server=$_POST["pxe_server"];
    $dhcp->vlan_id=$_POST["vlan_id"];
	
	$dhcp->EnableArticaAsDNSFirst=$_POST["EnableArticaAsDNSFirst"];
	$dhcp->do_no_verify_range=$_POST["do_no_verify_range"];
	
	$MASK=$IPClass->maskTocdir($dhcp->subnet, $dhcp->netmask);
	
	if(!$IPClass->isInRange($dhcp->range1, $MASK)){
		echo "jserror:$dhcp->subnet/$dhcp->netmask = $MASK $dhcp->range1 invalid for $MASK\n";
		return;
	}
	if(!$IPClass->isInRange($dhcp->range2, $MASK)){
	echo "jserror:$dhcp->subnet/$dhcp->netmask = $MASK $dhcp->range1 invalid for $MASK\n";
	return;
	}
	if($dhcp->ddns_domainname==null){$dhcp->ddns_domainname="home.lan";}

    if(isset($_POST["CircuitID"])){
        $dhcpsock=new dhcp_socks($MainInterface);
        $dhcpsock->SET("CircuitID",$_POST["CircuitID"]);
        $dhcpsock->SET("AgentID",$_POST["AgentID"]);
        $dhcpsock->SET("RelayAddress",$_POST["RelayAddress"]);


    }

	$dhcp->Save(true,$MainInterface);
}


function status():bool{
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();

    $jsafter=$tpl->framework_buildjs("/dhcpd/service/restart",
    "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");

    $btn_config=$tpl->button_autnonome("{apply_parameters}", $jsafter,
        "fas fa-file-check","ASDCHPAdmin",335);

    //<i class="fas fa-trash"></i>
    $btn_flush=$tpl->button_autnonome("{Clear_DHCP_Leases}", "Loadjs('$page?flush-leases=yes')",
        "fas fa-trash","ASDCHPAdmin",335,"btn-warning");


	$btn_config1=$tpl->button_autnonome("{config_file}", "Loadjs('$page?config-file-js=yes')",
        "fas fa-file-code","ASDCHPAdmin",335);


    $btn_config2=$tpl->button_autnonome("Support Tool", "Loadjs('$page?spt-js=yes')",
        "fas fa-cloud-upload","ASDCHPAdmin",335);

    $data = $sock->REST_API("/dhcpd/service/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        $status=$tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));

    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error", "{error}"));
            return true;
        }
    }
    if($json->Status) {
        $ini = new Bs_IniHandler();
        $ini->loadString($json->Info);
        $status = $tpl->SERVICE_STATUS($ini, "APP_DHCP", restart_script());
    }

	echo $status."<table><tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_config)."</td></tr>
        <tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_flush)."</td></tr>
        <tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_config1)."</td></tr>
        <tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_config2)."</td></tr>
        
</table>";
return true;
	
}

function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->ASDCHPAdmin){$tpl->js_no_privileges();return;}
	$tpl->js_dialog6("{APP_DHCP} >> {config_file}", "$page?config-file-popup=yes",1000);

}
function config_file_save(){

	$data=url_decode_special_tool($_POST["configfile"]);
	@file_put_contents(PROGRESS_DIR."/dhcpd.config", $data);
}

function restart_script(){
    $page=CurrentPageName();
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $tpl=new template_admin();

    $js[]="if(document.getElementById('table-loader-dhcp-service') ){";
    $js[]="LoadAjax('table-loader-dhcp-service','fw.dhcp.configuration.php?interfaces=yes');";
    $js[]="}";

    if($EnableKEA==1){
        $js[]=$tpl->framework_buildjs(
            "/kea/dhcp/reload","kea.service.progress",
            "kea.service.log","progress-kea-restart","LoadAjax('kea-lists','fw.kea.php?interfaces=yes');");

        return @implode(" ",$js);

    }

    $js[]=$tpl->framework_buildjs("/dhcpd/service/restart", "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");

    return @implode(" ",$js);
}

function config_file_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("dhcpd.php?config-file=yes");
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config");
	$form[]=$tpl->field_textareacode("configfile", null, $data);
    echo $tpl->form_outside("{config_file}", @implode("", $form),null,"{apply}",restart_script(),"ASDCHPAdmin");

}
