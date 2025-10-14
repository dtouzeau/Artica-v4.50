<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["nic-apply"])){nic_apply_interface();exit;}
if(isset($_POST["macvlansource"])){ModifyMacVlanName_save();exit;}
if(isset($_GET["ModifyMacVlanName-js"])){ModifyMacVlanName_js();exit;}
if(isset($_GET["ModifyMacVlanName-popup"])){ModifyMacVlanName_popup();exit;}

if(isset($_GET["nic-enable-interface"])){nic_enable_interface();exit;}
if(isset($_GET["nic-disable-interface"])){nic_disable_interface();exit;}
if(isset($_GET["nic-disable-dhcp"])){nic_disable_dhcp();exit;}
if(isset($_GET["nic-enable-dhcp"])){nic_enable_dhcp();exit;}
if(isset($_GET["nic-checksum-offloading"])){nic_checksum_offloading();exit;}
if(isset($_GET["nic-use-span-enable"])){nic_use_span_enable();exit;}
if(isset($_GET["nic-use-span-disable"])){nic_use_span_disable();exit;}
if(isset($_GET["nic-name-interface"])){nic_name_interface_js();exit;}
if(isset($_GET["nic-name-interface-popup"])){nic_name_interface_popup();exit;}
if(isset($_GET["nic-internet-check"])){nic_internet_check_save();exit;}
if(isset($_POST["NicName"])){nic_name_interface_save();exit;}

if(isset($_GET["nic-address"])){nic_addr_js();exit;}
if(isset($_GET["nic-address-popup"])){nic_addr_popup();exit;}
if(isset($_POST["NicAddr"])){nic_addr_save();exit;}

if(isset($_GET["nic-gateway"])){nic_gateway_js();exit;}
if(isset($_GET["nic-gateway-popup"])){nic_gateway_popup();exit;}
if(isset($_POST["NicGateway"])){nic_gateway_save();exit;}



if(isset($_GET["carrier-change-explain-js"])){carrier_change_explain_js();exit;}
if(isset($_GET["carrier-change-explain-popup"])){carrier_change_explain_popup();exit;}
if(isset($_GET["masq-interface"])){Masquerade_switch();exit;}
if(isset($_GET["ArpScanner"])){ArpScanner();exit;}
if(isset($_GET["refresh-tables"])){echo refreshjs();exit;}
if(isset($_GET["section-nics"])){section_nic_only();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["OSPF"])){Save_OSPF();exit;}
if(isset($_GET["AsGateway-options"])){AsGateway_options_js();exit;}
if(isset($_GET["AsGateway-options-popup"])){AsGateway_options_popup();exit;}
if(isset($_POST["EnableVPNPPTCompliance"])){AsGateway_options_save();exit;}
if(isset($_GET["nic-config-deny"])){nic_config_deny();exit;}
if(isset($_GET["ping-result"])){ping_results_js();exit;}
if(isset($_GET["ping-popup"])){ping_results_popup();exit;}

if(isset($_GET["add-macvlan-js"])){macvlan_js();exit;}
if(isset($_GET["add-macvlan-popup"])){macvlan_popup();exit;}
if(isset($_POST["macvlan"])){macvlan_save();exit;}
if(isset($_GET["delete-macvlan"])){macvlan_delete();exit;}
if(isset($_POST["delete-macvlan"])){macvlan_delete_perform();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status2"])){status2();exit;}
if(isset($_GET["status2-vlan-build-js"])){status2_vlan_build_js();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["nic-config-tab"])){nic_config_tab();exit;}
if(isset($_POST["save_nic"])){nic_save();exit;}
if(isset($_POST["IfConfigdelayed"])){option_save();exit;}
if(isset($_POST["save_nic_virtual"])){nic_virtuals_save();exit;}
if(isset($_POST["SysCtlEnable"])){nic_security_save();exit;}
if(isset($_GET["nic-config-js"])){nic_config_js();exit;}
if(isset($_GET["nic-config"])){nic_config();exit;}
if(isset($_GET["nic-config2"])){nic_config2();exit;}
if(isset($_GET["nic-security"])){nic_security();exit;}
if(isset($_GET["nic-features"])){nic_features();exit;}
if(isset($_GET["mii-tools"])){nic_mii_tool();exit;}

if(isset($_GET["AsGateway-js"])){AsGateway_js();exit;}
if(isset($_POST["AsGateway"])){AsGateway_save();exit;}
if(isset($_GET["netoptimize-js"])){NetOptimize_js();exit;}
if(isset($_POST["netoptimize"])){NetOptimize_save();exit;}

if(isset($_GET["YourFirewall-js"])){YourFirewall_js();exit;}
if(isset($_POST["YourFirewall"])){YourFirewall_save();exit;}

if(isset($_GET["nic-virtuals"])){nic_virtuals();exit;}
if(isset($_GET["nic-virtuals-list"])){nic_virtuals_search();exit;}

if(isset($_GET["nic-virtual-js"])){nic_virtuals_js();exit;}
if(isset($_GET["nic-virtual"])){nic_virtuals_popup();exit;}
if(isset($_GET["nic-virtual-delete-js"])){nic_virtuals_delete_js();exit;}
if(isset($_POST["delete_nic_virtual"])){nic_virtuals_delete();exit;}

//Bond
if(isset($_GET["nic-bond"])){nic_bond();exit;}
if(isset($_POST["save-bond-interface"])){save_bond_interface();exit;}
if(isset($_POST["addbondinterface"])){AddBondSave();exit;}
if(isset($_GET["add-bond-js"])){AddBond();exit;}
if(isset($_GET["load-bond-info-js"])){load_bond_info_js();exit;}
if(isset($_GET["load-bond-info-popup"])){load_bond_info_popup();exit;}

if(isset($_GET["nic-vlan-start"])){nic_vlan_start();exit;}
if(isset($_GET["nic-vlan-stop"])){nic_vlan_stop();exit;}


if(isset($_GET["nic-vlans"])){nic_vlans();exit;}
if(isset($_GET["nic-vlans-list"])){nic_vlans_list();exit;}
if(isset($_GET["nic-vlan-js"])){nic_vlan_js();exit;}
if(isset($_GET["nic-vlan"])){nic_vlan_popup();exit;}
if(isset($_GET["nic-vlan-enable"])){nic_vlan_enable();exit;}
if(isset($_GET["nic-vlan-masquerade"])){nic_vlan_masquerade();exit;}
if(isset($_POST["save_nic_vlan"])){nic_vlan_save();exit;}
if(isset($_GET["nic-vlan-delete-js"])){nic_vlan_delete_js();exit;}
if(isset($_POST["delete_nic_vlan"])){nic_vlan_delete();exit;}

if(isset($_GET["sysctl-js"])){sysctl_js();exit;}
if(isset($_GET["sysctl-popup"])){sysctl_popup();exit;}

if(isset($_GET["sysctl2-js"])){sysctl2_js();exit;}
if(isset($_GET["sysctl2-popup"])){sysctl2_popup();exit;}
if(isset($_GET["sysctl-proxyjs"])){sysctl2_proxy();exit;}


if(isset($_POST["NicProfile"])){saveNicProfile();exit;}

if(isset($_POST["EnableSystemNetworkOptimize"])){EnableSystemNetworkOptimize();exit;}
if(isset($_GET["disabled-interfaces-js"])){disabled_interfaces_js();exit;}
if(isset($_GET["disabled-interfaces-popup"])){disabled_interfaces_popup();exit;}
if(isset($_GET["disabled-interfaces-table"])){disabled_interfaces_table();exit;}
if(isset($_GET["enable-interface-js"])){disabled_interfaces_save();exit;}
if(isset($_GET["isFW"])){isFW();exit;}
if(isset($_GET["masquerade"])){masquerade();exit;}
if(isset($_GET["proxyarp"])){proxyarp();exit;}
if(isset($_GET["MPTCP-js"])){MTPTCP_JS();exit;}
if(isset($_GET["MTPTCP-popup"])){MTPTCP_POPUP();exit;}
if(isset($_GET["multipath-section"])){multip_path_section();exit;}
if(isset($_GET["multipath-table"])){multip_path_table();exit;}
if(isset($_GET["multip-path-js"])){multipath_js();exit;}
if(isset($_GET["mulipath-popup"])){multipath_popup();exit;}
if(isset($_POST["multip-path-gateway"])){multipath_save();exit;}
if(isset($_GET["multip-path-delete"])){multipath_delete();exit;}

if(isset($_GET["disable-watchdog"])){disable_watchdog_js();exit;}
if(isset($_GET["enable-watchdog"])){enable_watchdog_js();exit;}


if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}


page();

function load_bond_info_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{$_GET["load-bond-info-js"]} {info}","$page?load-bond-info-popup={$_GET["load-bond-info-js"]}");
}
function load_bond_info_popup(){
    $GLOBALS['CLASS_SOCKETS']->getFrameWork("network.php?bond-stats=true&bond={$_GET["load-bond-info-popup"]}");
    $data = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Bond-Stats-{$_GET["load-bond-info-popup"]}");
    echo nl2br($data);

    //echo @implode("\n", $html);
}
function ArpScanner():bool{
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableARPScanner",$_GET["ArpScanner"]);
    header("content-type: application/x-javascript");
    echo "LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/myself/restart");
    return true;
}
function Masquerade_switch():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["masq-interface"];

    $MASQ_js="Loadjs('$page?masq-interface=$eth');";
    $id="MASQ$eth";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    $ligne=$q->mysqli_fetch_array("SELECT firewall_masquerade from nics WHERE Interface='$eth'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $firewall_masquerade=intval($ligne["firewall_masquerade"]);
    VERBOSE("firewall_masquerade = $firewall_masquerade",__LINE__);

    if($firewall_masquerade==0){
        VERBOSE("firewall_masquerade ===> 1",__LINE__);
        $q->QUERY_SQL("UPDATE nics set firewall_masquerade=1 WHERE Interface='$eth'");
        if(!$q->ok){
            echo $tpl->js_error($q->mysql_error);
            return false;
        }
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        if($q->TABLE_EXISTS("pnic_bridges")) {
            $q->QUERY_SQL("UPDATE pnic_bridges set masquerading=1 WHERE nic_to='$eth'");
            if (!$q->ok) {
                echo $tpl->js_error($q->mysql_error);
                return false;
            }
        }
        $dicontent=base64_encode($tpl->button_medium("MASQUERADE","ok",$MASQ_js));
        echo "document.getElementById('$id').innerHTML=base64_decode('$dicontent');";
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/routers/buildnohup");
        return admin_tracks("Enable MASQUERADE on Interface $eth");
    }

    VERBOSE("firewall_masquerade ===> 0",__LINE__);
    $q->QUERY_SQL("UPDATE nics set firewall_masquerade=0 WHERE Interface='$eth'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    if($q->TABLE_EXISTS("pnic_bridges")) {
        $q->QUERY_SQL("UPDATE pnic_bridges set masquerading=0 WHERE nic_to='$eth'");
        if (!$q->ok) {
            echo $tpl->js_error($q->mysql_error);
            return false;
        }
    }

    if($q->TABLE_EXISTS("firehol_masquerade")) {
        $q->QUERY_SQL("DELETE FROM firehol_masquerade WHERE nic='$eth'");
        if (!$q->ok) {
            echo $tpl->js_error($q->mysql_error);
            return false;
        }
    }

    $dicontent=base64_encode($tpl->_ENGINE_parse_body($tpl->button_medium("{inactive}","default",$MASQ_js)));

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/routers/buildnohup");
    echo "document.getElementById('$id').innerHTML=base64_decode('$dicontent');";
    return admin_tracks("Disable MASQUERADE on Interface $eth");
   }
function nic_config_deny():bool{
    $tpl=new template_admin();
    return $tpl->js_error("{nic_config_deny}");
}

function ModifyMacVlanName_js():bool{
    $tpl=new template_admin();
    $value=$_GET["value"];
    $page=CurrentPageName();
    $md=$_GET["md"];
    return $tpl->js_dialog8("{rename} $value","$page?ModifyMacVlanName-popup=$value&md=$md",550);
}

function ModifyMacVlanName_popup():bool{
    $value=$_GET["ModifyMacVlanName-popup"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(preg_match("#^veth([0-9]+)$#",$value,$matches)){
        $index=$matches[1];
    }
    $form[]=$tpl->field_hidden("macvlansource",$value);
    $form[]=$tpl->field_numeric("macvlansuffix","{suffix2}",$index);
    $security="AsSystemAdministrator";
    $js="dialogInstance8.close();LoadAjaxSilent('div-works-$value','$page?nic-config2=$value&md=$md');";
    $html[]=$tpl->form_outside("", $form,"{modSuffixInterface}","{apply}",$js.refreshjs($value),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ModifyMacVlanName_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $macvlansource=$_POST["macvlansource"];
    $macvlansuffix=$_POST["macvlansuffix"];
    $macvlanNext="veth$macvlansuffix";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="UPDATE nics SET Interface='$macvlanNext' WHERE Interface='$macvlansource'";
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL("UPDATE nics SET Interface='$macvlanNext' WHERE Interface='$macvlansource'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/delete/$macvlansource");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/build/$macvlanNext");
    return admin_tracks("Change interface name from $macvlansource to $macvlanNext");
}



function options_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return $tpl->js_dialog("{options}","$page?options-popup=yes");
}
function ping_results_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $data=$_GET["ping-result"];
    return $tpl->js_dialog("PING","$page?ping-popup=$data");
}

function options_popup(){
    $tpl=new template_admin();

    $bondEnabled = intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInterfaceBond")));


    $IfConfigdelayed=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IfConfigdelayed")));
    $form[]=$tpl->field_checkbox("IfConfigdelayed","{delayed_startup}",$IfConfigdelayed,false,"{delayed_startup_network}");
    $form[]=$tpl->field_checkbox("EnableInterfaceBond","{bond}",$bondEnabled,false,"{EnableInterfaceBond}");
    $rf=refreshjs();
    $hml[]=$tpl->form_outside("{options}", @implode("\n", $form),null,"{apply}","$rf","AsSystemAdministrator");
    echo @implode("\n", $hml);
}
function option_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?delayed-nets={$_POST["IfConfigdelayed"]}&bonding={$_POST["EnableInterfaceBond"]}");
}

function Save_OSPF(){
    $eth    = $_GET["OSPF"];
    $caz    = new system_nic($eth);
    $ospf   = $caz->ospf_enable;
    if($ospf==1){$ospf=0;}else{$ospf=1;}
    $caz->ospf_enable=$ospf;
    $caz->NoReboot=true;
    $caz->SaveNic();


}

function disable_watchdog_js(){
    $md=$_GET["md"];
    $page=CurrentPageName();
    $eth=$_GET["disable-watchdog"];
    $nic=new system_nic($eth,true);
    $nic->watchdog=0;
    $nic->SaveNic();

    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.interfaces-watchdog.log";
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.interfaces-watchdog.progress";
    $ARRAY["CMD"]="network.php?monit-interfaces=yes";
    $ARRAY["TITLE"]="{please_wait_building_network}";
    $ARRAY["AFTER"]="LoadAjax('network-interfaces-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-interface-$eth');LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    header("content-type: application/x-javascript");
    echo $jsrestart."\n";

}
function enable_watchdog_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["enable-watchdog"];
    $nic=new system_nic($eth,true);
    $md=$_GET["md"];
    if($nic->enabled==0){
        $tpl->js_error("{this_interface_is_disabled}");
        return;
    }

    if($nic->IPADDR=="0.0.0.0"){
        $tpl->js_error("{this_interface_is_not_configured}");
        return;
    }


    $nic->watchdog=1;
    $nic->SaveNic();

    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.interfaces-watchdog.log";
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.interfaces-watchdog.progress";
    $ARRAY["CMD"]="network.php?monit-interfaces=yes";
    $ARRAY["TITLE"]="{please_wait_building_network}";
    $ARRAY["AFTER"]="LoadAjax('network-interfaces-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-interface-$eth');LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    header("content-type: application/x-javascript");
    echo $jsrestart."\n";

}
function disabled_interfaces_save(){
	$page=CurrentPageName();
	$eth=$_GET["enable-interface-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	
	
	$q->QUERY_SQL("UPDATE nics SET `enabled`='1' WHERE `Interface`='$eth'");
	header("content-type: application/x-javascript");
	if(!$q->ok){
		echo "alert('MySQL error ! ".$q->mysql_error."');\n";
		return;
	}
	
	echo "// eth:$eth\n";
	echo "LoadAjaxSilent('disabled_interfaces_popup','$page?disabled-interfaces-table=yes');\n";
	echo "LoadAjax('network-interfaces-table','$page?table=yes');\n";
}
function masquerade(){
    $eth=$_GET["masquerade"];
    $nicz=new system_nic($eth);
    if($nicz->firewall_masquerade==0){$nicz->firewall_masquerade=1;}else{$nicz->firewall_masquerade=0;}
    $nicz->NoReboot=true;
    $nicz->SaveNic();
    admin_tracks("Define interface $eth as masquerade to $nicz->firewall_masquerade");
    $sock=new sockets();
    $sock->REST_API("/system/network/routers/nohupbuild");

}
function proxyarp(){
    $eth=$_GET["proxyarp"];
    $nicz=new system_nic($eth);
    if($nicz->proxyarp==0){$nicz->proxyarp=1;}else{$nicz->proxyarp=0;}
    $nicz->NoReboot=true;
    if(!$nicz->SaveNic()){
        echo $nicz->mysql_error;
        return false;
    }
    admin_tracks("Define interface $eth as Proxy ARP to $nicz->proxyarp");
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("net.ipv4.conf.$eth.proxy_arp",$nicz->proxyarp);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return true;
}
function isFW(){
	$eth=$_GET["isFW"];
	$nicz=new system_nic($eth);
	if($nicz->isFW==0){$nicz->isFW=1;}else{$nicz->isFW=0;}
    admin_tracks("Define interface $eth as Firewall to $nicz->isFW");
	$nicz->SaveNic();
	
}



function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $users=new usersMenus();
    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));


    $array["{YOUR_INTERFACES}"]="$page?status=yes";
    if($users->AsSystemAdministrator) {
        if($DisableNetworking==0) {
            $array["{nic_infos} ({details})"] = "$page?table-start=yes";
        }
    }
	$array["{open_ports}"]="fw.openports.php";
    echo $tpl->tabs_default($array);

}

function nic_config_js(){
    $md=$_GET["md"];
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	$eth=$_GET["nic-config-js"];
	$nicz=new system_nic($eth,true);
	$title="$nicz->netzone: $nicz->NICNAME ($eth)";
	$tpl->js_dialog1($title, "$page?nic-config-tab=$eth&md=$md",1024);
}
function nic_name_interface_js():bool{
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["nic-name-interface"];
    return $tpl->js_dialog5("$eth {name}", "$page?nic-name-interface-popup=$eth&md=$md",750);
}
function nic_addr_js():bool{
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["nic-address"];
    return $tpl->js_dialog5("$eth {address}", "$page?nic-address-popup=$eth&md=$md",750);
}
function nic_gateway_js():bool{
    $md=$_GET["md"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["nic-gateway"];
    return $tpl->js_dialog5("$eth {gateway}", "$page?nic-gateway-popup=$eth&md=$md",750);
}


function disabled_interfaces_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	return $tpl->js_dialog1("{disabled_interfaces}", "$page?disabled-interfaces-popup=yes");
	
}

function sysctl_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

	return $tpl->js_dialog2("{kernelnetworkoptimization}", "$page?sysctl-popup=yes");
}

function sysctl2_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    return $tpl->js_dialog2("{kernelnetworkoptimization}", "$page?sysctl2-popup=yes");
}

function MTPTCP_JS():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        return $tpl->js_no_privileges();
    }

	return $tpl->js_dialog2("{APP_MULTIPATH_TCP}", "$page?MTPTCP-popup=yes");
}

function multip_path_section():bool{
	$page=CurrentPageName();
	$eth=$_GET["multipath-section"];
	echo "<div id='multipath-div'></div><script>LoadAjax('multipath-div','$page?multipath-table=$eth');</script>";
	return true;
}


function MTPTCP_POPUP():bool{
	$tpl=new template_admin();
	$APP_MULTIPATH_TCP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MULTIPATH_TCP_VERSION");
	
	$html[]="<H2>{APP_MULTIPATH_TCP} v$APP_MULTIPATH_TCP_VERSION</H2>";
	$html[]="<table id='table-MTPTCP-snmp' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{key}</th>";
	$html[]="<th data-sortable=false class='text-capitalize center' data-type='text'>{value}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$TRCLASS=null;
	$f=explode("\n",@file_get_contents("/proc/net/mptcp_net/snmp"));
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^(.+?)\s+([0-9]+)#", $line,$re)){continue;}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td><strong>$re[1]</strong></td>";
		$html[]="<td style='width:1%' nowrap>". FormatNumber($re[2])."</td>";
		$html[]="</tr>";

	}
	

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-MTPTCP-snmp').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }) });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	return true;
}


function EnableSystemNetworkOptimize(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
}

function disabled_interfaces_popup(){
	$page=CurrentPageName();
	echo "<div id='disabled_interfaces_popup'></div><script>LoadAjaxSilent('disabled_interfaces_popup','$page?disabled-interfaces-table=yes');</script>";
	
}

function disabled_interfaces_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$TRCLASS=null;
	
	$html[]="<table id='table-disable-interfaces-table' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{interface}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:right;padding-right:2px;'>{tcp_address}</th>";
	$html[]="<th data-sortable=false class='text-capitalize center' data-type='text'>{enable}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT Interface FROM nics WHERE enabled=0";
	$results=$qlite->QUERY_SQL($sql);
	
	
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$enable=$tpl->icon_check(0,"Loadjs('$page?enable-interface-js={$ligne["Interface"]}')",null,"AsSystemAdministrator");
		$text_class=null;
		$nicz=new system_nic($ligne["Interface"]);
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\">$nicz->netzone: $nicz->NICNAME ({$ligne["Interface"]})</a></td>";
		$html[]="<td class=\"$text_class\" style='text-align:right'>$nicz->IPADDR</a></td>";
		$html[]="<td class=\"$text_class\" style='width:1%' class='center' nowrap>{$enable}</center></td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-disable-interfaces-table').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }) });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function status(){
    $page=CurrentPageName();


    echo "<div id='netz-interfaces-status'></div>
    <script>
    function InterfaceMainStatusLoad(){
    LoadAjaxSilent('netz-interfaces-status','$page?status2=yes');
    }\nInterfaceMainStatusLoad();</script>";
}

function status_ipv6($nic):string{
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/ip6/$nic");
    $json=json_decode($data);
    $f=array();
    foreach ($json as $index=>$ipcl) {
        $ipaddr = $ipcl->IpAddr;
        $cdir = $ipcl->NetMask;
        $f[]="<div><span style='font-size: 13px' id='$index'>$ipaddr/$cdir</span>";
    }
    if(count($f)==0){
        return "";
    }
    return @implode("",$f);
}

function status2_dhcp($Interface,$nicz):string{
    $tpl=new template_admin();
    $UDHCPD_INSTALLED   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UDHCPD_INSTALLED"));
    $UDHCPD = $tpl->button_medium("{not_installed}", "none", null);


    if(!isset($GLOBALS["UDHCP_STATUS"])) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/udhcpd/status"));
        foreach ($json->Info as $key => $pid) {
            $udhcpd_status[$key] = $pid;
        }
    }
    if($UDHCPD_INSTALLED==1){
        $udhcp_js="Loadjs('fw.udhcpd.php?interface=$Interface');";
        if($nicz->udhcpd==1){
            $UDHCPD = $tpl->button_medium("{active2}","warning",$udhcp_js);
            if(isset($udhcpd_status[$Interface])){
                $UDHCPD = $tpl->button_medium("{stopped}","error",$udhcp_js);
                if($udhcpd_status[$Interface]>0){
                    $UDHCPD = $tpl->button_medium("{running}","ok",$udhcp_js);
                }
            }
            return $UDHCPD;
        }
    }
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    if($EnableDHCPServer==1 OR $EnableKEA==1){
        $dhcp       = new dhcpd(0, 1, $Interface);
        if($dhcp->service_enabled==1){
            $udhcp_js="Loadjs('fw.dhcp.configuration.php?spopup-interface=$Interface')";
            return $tpl->button_medium("{running}","ok",$udhcp_js);

        }


    }
    return $UDHCPD;
}

function Widget_isGateway():string{
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $users              = new usersMenus();
    $EnableArticaAsGateway=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway")));

    $MustBeAgateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MustBeAgateway"));
    $EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));

    if($Enablehacluster==1){
        $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
        if($HaClusterTransParentMode==1){
            $MustBeAgateway=1;
        }
    }
    if($EnableRedSocks==1){
        $MustBeAgateway=1;
    }

    $LockButton=false;
    if ($MustBeAgateway==1) {
        $EnableArticaAsGateway=1;
        $LockButton=true;
    }
    if($EnableArticaAsGateway==0) {
        $button["name"]="{enable}";
        $button["js"]="Loadjs('$page?AsGateway-js=1')";

        $button2["name"]="{options}";
        $button2["ico"]="fa fa-wrench";
        $button2["js"]="Loadjs('$page?AsGateway-options=yes')";

        if(!$users->AsSystemAdministrator){
            $button["js"]="blur()";
            $button2["js"]="blur();";
        }
        return $tpl->widget_h("gray", "far fa-ethernet", "{disabled}", "{act_as_gateway}",$button,$button2);

    }

        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/isgateway"));

        $button["name"]="{disable}";
        $button["js"]="Loadjs('$page?AsGateway-js=0')";

        $button2["name"]="{options}";
        $button2["ico"]="fa fa-wrench";
        $button2["js"]="Loadjs('$page?AsGateway-options=yes')";

        if($LockButton){
            $button["name"]="{locked}";
            $button["js"]="blur()";
            $button["ico"]=ico_lock;
        }

        if(!$users->AsSystemAdministrator){
            $button["js"]="blur()";
            $button2["js"]="blur();";
        }


        if($json->ip_forward==0){
            return  $tpl->widget_h("yellow", "far fa-ethernet", "{not_saved}", "{act_as_gateway}",$button,$button2);
        }
        return $tpl->widget_h("green", "far fa-ethernet", "{enabled}", "{act_as_gateway}", $button, $button2);



}

function status2(){
    $security           = "AsSystemAdministrator";
	$tpl                = new template_admin();
	$t                  = time();
	$page               = CurrentPageName();
    $users              = new usersMenus();
    $pattern_exclude    ="^(dummy|teql|ip6tnl|tunl|gre|ifb|sit|gretap|erspan)[0-9]+";
    $intpath            ="/usr/share/artica-postfix/ressources/logs/web/interface.array";
    $GW                 = Widget_isGateway();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/listnics"));
    $zPhysicalsInterfaces=unserialize(base64_decode($data->nics));

    $DefaultGateways=array();
    if(property_exists($data, "Gateways")){
        foreach($data->Gateways as $gateway){
            $interface=$gateway->interface;
            $DefaultGateways[$interface]=array(
                "gw"=>$gateway->gatewayIP,
                "metric"=>$gateway->metric
            );

        }
    }
    $MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
	$EnableOSPFD        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOSPFD"));
	$EnableTailScaleService = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
    $TRCLASS=null;

	foreach ($zPhysicalsInterfaces as $Interface){
        $PhysicalsInterfaces[$Interface]=true;
    }

    $EnableVLANs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs");
	$qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="SELECT Interface FROM nics WHERE enabled=1";
	$results=$qlite->QUERY_SQL($sql);

	if(!$qlite->ok){echo $qlite->mysql_error_html();}
	VERBOSE("$sql ".count($results)." items",__LINE__);
	if($GLOBALS["VERBOSE"]){print_r($results);}
	
	$IntefaceCount=0;


    $Interface="";
    $EnableSystemNetworkOptimize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSystemNetworkOptimize"));
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));

    if($EnableSystemNetworkOptimize==0) {
        $button["ico"]=ico_check;
        $button["name"]="{enable}";
        $button["js"]="Loadjs('$page?netoptimize-js=1')";
        if(!$users->AsSystemAdministrator){$button=array();}
        $OPTZ = $tpl->widget_h("gray", "fas fa-signal", "{disabled}", "{SystemNetworkOptimize}",$button);
    }else{
        $button["ico"]="fas fa-signal";
        $button["name"]="{disable}";
        $button["js"]="Loadjs('$page?netoptimize-js=0')";
        if(!$users->AsSystemAdministrator){$button=array();}
        $OPTZ = $tpl->widget_h("green", "fas fa-signal", "{enabled}", "{SystemNetworkOptimize}",$button);
    }
    if($FireHolEnable==0) {
        $button["ico"]=ico_check;
        $button["name"]="{enable}";
        $button["js"]="Loadjs('$page?YourFirewall-js=1')";
        if(!$users->AsFirewallManager){$button=array();}
        $FW = $tpl->widget_h("gray", "fad fa-shield-alt", "{disabled}", "{your_firewall}",$button);
    }else{
        $button["ico"]="fad fa-shield-alt";
        $button["name"]="{disable}";
        $button["js"]="Loadjs('$page?YourFirewall-js=0')";
        if(!$users->AsFirewallManager){$button=array();}
        $FW = $tpl->widget_h("green", "fad fa-shield-alt", "{enabled}", "{your_firewall}",$button);
    }

    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/reconfigure-newtork.progress";
    $ARRAY["CMD"]="/system/network/reconfigure-restart";
    $ARRAY["TITLE"]="{please_wait_building_network}";
    $ARRAY["AFTER"]="LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=netz-interfaces-status')";

    $apply="<button class='btn btn-primary btn-xs' type='button' OnClick=\"$jsrestart;\">{apply}</button>";
    if(!$users->AsSystemAdministrator){$apply=null;}
	
	$html[]="<div style='margin-top:20px'>";
    $html[]="<table style='width:100%'>
	    <tr>
	    <td style='vertical-align:top;width:200px;padding:8px'>$GW</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$OPTZ</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$FW</td>
	    </tr>
	   </table>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th nowrap='' colspan='2'><H3>{status}</H3></th>";
    $html[]="<th nowrap=''>&nbsp;</th>";
    $html[]="<th nowrap=''><H3>{interface}</H3></th>";
    $html[]="<th nowrap='' class='center'><H3>MASQ</H3></th>";
    $html[]="<th nowrap='' class='center'><H3>OSPF</H3></th>";
    $html[]="<th nowrap='' class='center'><H3>ARP</H3></th>";
    $html[]="<th nowrap='' class='center'><H3>DHCP</H3></th>";

    $html[]="<th nowrap=''><H3>&nbsp;</H3></th>";
    $html[]="<th nowrap=''><H3>{speed}</H3></th>";
    $html[]="<th nowrap=''>&nbsp;</th>";
    $html[]="<th nowrap=''><H3>{reception}</H3></th>";
    $html[]="<th nowrap=''>&nbsp;</th>";
    $html[]="<th nowrap=''><H3>{transmission}</H3></th>";
    $html[]="<th nowrap=''><H3>{rejected}</H3></th>";
    $html[]="<th nowrap=''></th>";

    $OpenVswitchEnable  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVswitchEnable"));
    $EnableVnStat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVnStat"));
    $PARPROUTED_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PARPROUTED_INSTALLED"));

    if($EnableTailScaleService==1){
        $results[]=array(
            "Interface"=>"tailscale0",
        );
    }

    $H3="<span style='font-size:12px'>";
    $ALREADY_INTERFACE=array();
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
	foreach($results as $index=>$ligne) {
        $md=md5(serialize($ligne));
        if (!isset($PhysicalsInterfaces[$ligne["Interface"]])) {
            VERBOSE(" ------------------- Interface $Interface Not a physical interface", __LINE__);
            continue;
        }
        $Interface = $ligne["Interface"];
        $text_class = null;
        $ALREADY_INTERFACE[$Interface] = true;
        if (preg_match("#$pattern_exclude#", $Interface)) {
            continue;
        }
        VERBOSE($ligne["Interface"], __LINE__);
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?ifconfig-array=$Interface");
        $MAIN = unserialize(@file_get_contents($intpath));
        $nicz = new system_nic($Interface);
        $NICNAME = $nicz->NICNAME;
        $UseSPAN = $nicz->UseSPAN;
        $speed = intval($MAIN["SPEED"]);
        $TX = $MAIN["TX"];
        $RX = $MAIN["RX"];
        $DROP = $MAIN["DROP"];
        $ico_vnstat = "";
        $IPADDR = $tpl->icon_nothing();
        $rx_icon = $tpl->icon_nothing();
        $drop_icon = $tpl->icon_nothing();
        $tx_icon = $tpl->icon_nothing();
        $OSPF = $tpl->icon_nothing();
        $macvlan_text = null;
        $ipv6Text = "";
        $defaultGatewayTips="";
        if(isset($DefaultGateways[$Interface])) {
            $defaultGatewayTips ="<br><i>{default_gateway} {$DefaultGateways[$Interface]["gw"]} {metric} {$DefaultGateways[$Interface]["metric"]}</i>";
        }

        $UDHCPD=status2_dhcp($Interface,$nicz);
        if ($nicz->macvlan == 1) {
            $macvlan_text = "&nbsp;<span class='label label-info'>Macvlan " . $nicz->physical . "</span>";
        }
        if ($nicz->ipvlan == 1) {
            $macvlan_text = "&nbsp;<span class='label label-info'>IPvlan " . $nicz->physical . "</span>";
        }

        if ($EnableipV6 == 1) {
            $ipv6Text = status_ipv6($Interface);
        }

        VERBOSE(" ------------------- $index Interface $Interface", __LINE__);
        if (isset($STATUS_INTERFACE[$Interface])) {
            VERBOSE(" -------------------$index  Interface $Interface ALREADY SET", __LINE__);
            continue;
        }


        $JS = "Loadjs('fw.network.interfaces.php?nic-config-js=$Interface&md=$md');";
        if ($Interface == "tailscale0") {
            $JS = "Loadjs('fw.network.interfaces.php?nic-config-deny=$Interface');";
        }


        $PARPROUTED = $tpl->button_medium("{not_installed}", "none");
        $speed_text = $tpl->icon_nothing();
        $button = $tpl->icon_nothing();

        if ($speed > 0) {
            $speed_text = $speed . "Mbits/sec";
            if ($speed == 10000) {
                $speed_text = "10 Gigabits/s";
            }
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        if($EnableOSPFD==1){
            $OSPF=$tpl->icon_check($nicz->ospf_enable,
                "Loadjs('$page?OSPF=".urlencode($Interface)."')",null,$security);
        }


        if ($MUNIN_CLIENT_INSTALLED == 1) {
            if ($EnableMunin == 1) {
                $button["name"] = "{statistics}";
                $button["js"] = "Loadjs('fw.network.interfaces.munin.php?interface={$ligne["Interface"]}')";
                $button["icon"] = "fas fa-chart-bar";
                if(!$users->AsSystemAdministrator){$button=array();}
            }
        }
        $PingGateway=null;

        $NetStatus=new NetStatus($Interface);
        if($OpenVswitchEnable==0){
            $nicz->virtualbridge=0;
        }

        if ($NetStatus->InterfaceRunning){
            $IPADDR=$NetStatus->IPADDR;
            $label="label-primary";
            $text_icon="{active2}";
            if(($UseSPAN==0) AND ($nicz->virtualbridge==0)){
                $PingGateway=status2_pingGateway($Interface,$nicz->GATEWAY);
            }
        }else {
            $label = "label-warning";
            $text_icon = "{inactive}";
            $OSPF = $tpl->icon_nothing();
        }
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $ligne3=$q->mysqli_fetch_array("SELECT firewall_masquerade from nics WHERE Interface='$Interface'");
        $firewall_masquerade=intval($ligne3["firewall_masquerade"]);
        $MASQ_js="Loadjs('$page?masq-interface=$Interface');";
        if($firewall_masquerade==1){
            $MASQ="<span id='MASQ$Interface'>".$tpl->button_medium("MASQUERADE","ok",$MASQ_js)."</span>";
        }else{
            $MASQ="<span id='MASQ$Interface'>".$tpl->button_medium("{inactive}","default",$MASQ_js)."</span>";

        }


        if($PARPROUTED_INSTALLED==1){
            $PARPROUTED_JS="Loadjs('fw.parprouted.php?interface=$Interface');";
            $PARPROUTED = $tpl->button_medium("{inactive}","none",$PARPROUTED_JS);
            if($nicz->parprouted==1){
                $PARPROUTED = $tpl->button_medium("{active2}","ok",$PARPROUTED_JS);
            }
        }


        $carrier_changes=intval(@file_get_contents("/sys/class/net/$Interface/carrier_changes"));

        if($RX>0) {
            $rx_icon = "<i class='fas fa-arrow-to-bottom'></i>&nbsp;" . FormatBytes($RX / 1024);
        }

        if($TX>0) {
            $tx_icon = "<i class='fas fa-arrow-to-top'></i>&nbsp;" . FormatBytes($TX / 1024);
        }
        if($DROP>0) {
            $drop_icon = "<i class='fas fa-hand-paper'></i>&nbsp;" . FormatNumber($DROP);
        }

        if($UseSPAN==1){
            $IPADDR="0.0.0.0";
            $label="label-info";
            $text_icon="{mirror}";
            $OSPF=$tpl->icon_nothing();
            $UDHCPD=$tpl->icon_nothing();
            $apply=$tpl->icon_nothing();
            $PARPROUTED=$tpl->icon_nothing();
            $MASQ=$tpl->icon_nothing();
        }

        $carrier_changes_text=null;
        if($carrier_changes>2){
            $carrier_changes=$carrier_changes/2;
            $carrier_changes_text="&nbsp;".$tpl->td_href("<span class='label label-warning'><i class='fad fa-plug'></i> <strong>$carrier_changes</strong></span>","",
                    "Loadjs('$page?carrier-change-explain-js=yes')");
        }

        $bondStatus="";
        if(preg_match("#^bond[0-9]+#", $Interface)){
            $bondStatus=$tpl->icon_loupe(true,"Loadjs('$page?load-bond-info-js=$Interface')");
        }


        if(!$users->AsSystemAdministrator){
            $apply=$tpl->icon_nothing();
            $OSPF=$tpl->icon_nothing();
            $PARPROUTED=$tpl->icon_nothing();
            $UDHCPD=$tpl->icon_nothing();
            $MASQ=$tpl->icon_nothing();
        }


        $icon="<span class='label $label font-bold'>$text_icon</span>";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$icon</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$PingGateway</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>".
            $tpl->td_href("$H3$Interface</span>",null,$JS).
            "</td>";
        $html[]="<td class=\"$text_class\" style='width:99%' nowrap>
".$tpl->td_href("$H3$IPADDR&nbsp;&nbsp;($NICNAME)</span>",null,$JS)."$carrier_changes_text$macvlan_text$ipv6Text$defaultGatewayTips</span></td>";
        $ico_graphs_rx=$tpl->icon_stats("");
        $ico_graphs_tx=$tpl->icon_stats("");
        if(is_file("img/squid/{$Interface}rx-hourly.png")){
            $ico_graphs_rx=$tpl->icon_stats("Loadjs('fw.rrd.php?img={$Interface}rx')");
            $ico_graphs_tx=$tpl->icon_stats("Loadjs('fw.rrd.php?img={$Interface}tx')");
        }


        if($EnableVnStat==1){
            $ico_vnstat=$tpl->icon_chart("Loadjs('fw.system.vnstati.php?interface=$Interface')")."&nbsp;&nbsp;";
        }

        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$MASQ</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$OSPF</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$PARPROUTED</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$UDHCPD</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$apply</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$speed_text</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$ico_vnstat$ico_graphs_rx</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$rx_icon</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$ico_graphs_tx</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$tx_icon</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$drop_icon</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$bondStatus</td>";
		$html[]="</tr>";
        $IntefaceCount++;
        $STATUS_INTERFACE[$Interface]=true;
        if($EnableVLANs==1){
            VERBOSE("STATUS VLAN OF $Interface..",__LINE__);
            $arrayvlan=status2_vlan($TRCLASS,$Interface);
            if(count($arrayvlan)>0){
                $TRCLASS=$arrayvlan["TRCLASS"];
                $html[]=$arrayvlan["TABLE"];
            }
        }
	}
    $html[]=status2_ghosts($ALREADY_INTERFACE,$TRCLASS);


	$html[]="</table>";
    $topbuttons=array();

    $jsrestart=$tpl->framework_buildjs("/system/network/reconfigure-restart",
        "reconfigure-newtork.progress",
        "exec.virtuals-ip.php.html","progress-firehol-restart",
        "LoadAjax('netz-interfaces-status','$page?status2=yes');"
    ); // apply_network_configuration

    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    $EnableWebBandwhich=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebBandwhich"));
    $EnableARPScanner=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableARPScanner"));
    if($EnableWebBandwhich==1) {
        $topbuttons[] = array("s_PopUp('/bandwhich/',1024,768,);",
            ico_monitor, "{network_monitor}");
    }
    if($users->AsSystemAdministrator) {
        if ($DisableNetworking == 0) {
            $topbuttons[] = array($jsrestart,
                ico_save, "{apply_network_configuration}");
        }
    }

    if($EnableARPScanner==1){
        $topbuttons[] = array("Loadjs('$page?ArpScanner=0')",
            ico_monitor, "{APP_NETDISCOVER} ({active2})");
    }else{
        $topbuttons[] = array("Loadjs('$page?ArpScanner=1')",
            ico_monitor, "{APP_NETDISCOVER} ({inactive2})");
    }


    $btns=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{network_interfaces}";
    $TINY_ARRAY["ICO"]="fa fa-exchange";
    $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
</script>";

    if($DisableNetworking==0) {
        if ($IntefaceCount == 0) {
            echo $tpl->FATAL_ERROR_SHOW_128("{no_interface_saved_explain}");
        }
    }
	echo $tpl->_ENGINE_parse_body($html);
}
function ping_results_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=base64_decode($_GET["ping-popup"]);
    $json = json_decode($data);
    if (!$json->Status) {
        echo $tpl->div_error($json->Error."||".$json->Info);
        return;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("PING:||".$json->Info));
}
function status2_pingGateway($iface,$gateway):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if($gateway=="0.0.0.0"){
        return "";
    }
    if(strlen($gateway)<4){
        return "";
    }

    $sock=new sockets();
    $data=$sock->REST_API("/system/network/ping/$gateway/$iface");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return "";
    }
    $b64=base64_encode($data);

    if (!$json->Status) {
        $label=$tpl->td_href("<span class='label label-danger'>PING ..</span>",null,"Loadjs('$page?ping-result=$b64')");
        return $tpl->_ENGINE_parse_body($label);
    }
    $label=$tpl->td_href("<span class='label label-primary'>PING OK</span>",null,"Loadjs('$page?ping-result=$b64')");
    return $tpl->_ENGINE_parse_body($label);
}

function status2_ghosts($ALREADY_INTERFACES,$TRCLASS):string{
    $html=array();
    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $data=$sock->REST_API("/system/network/interfaces");
    $speed_text="";
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        VERBOSE("json status == FALSE",__LINE__);
        return "";
    }

    $H3="<span style='font-size:12px'>";
    foreach($json->interfaces as $index=>$ligne){
        $Interface=$ligne->Name;
        if($Interface=="lo"){continue;}
        VERBOSE("Interface == $Interface",__LINE__);
        if(isset($ALREADY_INTERFACES[$Interface])){
            continue;
        }

        $NICNAME=$Interface;
        $State=$ligne->State; // up|broadcast|multicast|running
        $text_class="text-black";
        $carrier_changes_text="";
        $OSPF       = $tpl->icon_nothing();
        $PARPROUTED = $tpl->icon_nothing();
        $UDHCPD = $tpl->icon_nothing();
        $MASQ=$tpl->icon_nothing();
        $apply = $tpl->icon_nothing();
        $RX=$ligne->RxBytes;
        $TX=$ligne->TxBytes;
        $DROP=$ligne->Drop;
        if ($ligne->Speed > 0) {
            $speed_text = $ligne->Speed . "Mbits/sec";
            if ($ligne->Speed == "10000") {
                $speed_text = "10 Gigabits/s";
            }
        }


        $rx_icon   = $tpl->icon_nothing();
        $tx_icon     = $tpl->icon_nothing();
        $drop_icon = $tpl->icon_nothing();
        $bondStatus = $tpl->icon_nothing();
        $gateway=$ligne->Gateway;
        $ipv6Text="";
        $macvlan_text="";
        $PingStatys="";
        $IPADDR="";
        $icon               ="<i class=\"text-info fas fa-ethernet\"></i>";
        if(!is_array($ligne->Addresses)){
            $ligne->Addresses=array();
        }
        if($RX>0) {
            $rx_icon = "<i class='fas fa-arrow-to-bottom'></i>&nbsp;" . FormatBytes($RX / 1024);
        }

        if($TX>0) {
            $tx_icon = "<i class='fas fa-arrow-to-top'></i>&nbsp;" . FormatBytes($TX / 1024);
        }
        if($DROP>0) {
            $drop_icon = "<i class='fas fa-hand-paper'></i>&nbsp;" . FormatNumber($DROP);
        }




        if(count($ligne->Addresses)>0) {
            $IPADDR = $ligne->Addresses[0];
            unset($ligne->Addresses[0]);
        }
        if(count($ligne->Addresses)>0){
            $simplode=@implode(", ",$ligne->Addresses);
            $carrier_changes_text=sprintf("<small>(%s)</small>",$simplode);
        }

        if(strtoupper($ligne->Status)=="DOWN"){
            $text_class="text-muted";
            $icon               ="<span class='label label-default'>{inactive}</span>";
        }
        if(strtoupper($ligne->Status)=="UP"){
            $text_class="text-black";
            $icon               ="<span class='label label-primary'>{active2}</span>";
            $PingStatys=status2_pingGateway($Interface,$gateway);
        }

        if(preg_match("#^(.+?):([0-9]+)$#",$NICNAME,$m)){
            $IPADDR=$tpl->td_href($IPADDR,"","Loadjs('$page?nic-virtual-js={$m[2]}&eth={$m[1]}&function=InterfaceMainStatusLoad')");
        }

        if(preg_match("#^eth[0-9]+$#",$Interface)) {
            $md = md5($Interface);
            $JS = "Loadjs('fw.network.interfaces.php?nic-config-js=$Interface&md=$md');";
            if ($Interface == "tailscale0") {
                $JS = "Loadjs('fw.network.interfaces.php?nic-config-deny=$Interface');";
            }
            $Interface = $tpl->td_href($Interface, "", $JS);
            $IPADDR= $tpl->td_href($IPADDR, "", $JS);
        }else{
            VERBOSE("$Interface no match ^eth[0-9]+$",__LINE__);
        }


        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$icon</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$PingStatys</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$Interface</span></td>";
        $html[]="<td class=\"$text_class\" style='width:99%' nowrap>$H3$IPADDR&nbsp;&nbsp;($NICNAME)$carrier_changes_text$macvlan_text$ipv6Text</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$MASQ</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$OSPF</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$PARPROUTED</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$UDHCPD</></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$apply</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$speed_text</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$rx_icon</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$tx_icon</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$H3$drop_icon</span></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$bondStatus</td>";
        $html[]="</tr>";

    }

    if(count($html)==0){
        return "";
    }

    return @implode("\n",$html);
}

function status2_vlan_build_js(){
    $page               = CurrentPageName();
    $tpl=new template_admin();

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?build-vlans=yes");

    sleep(2);
    echo $tpl->js_display_results("{nic_settings} {done}");
    echo "LoadAjax('netz-interfaces-status','$page?status2=yes');\n";
}

function status2_vlan($TRCLASS,$Interface){
    $tpl=new template_admin();
    $page               = CurrentPageName();
    $UDHCPD_INSTALLED   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UDHCPD_INSTALLED"));
    $intpath            ="/usr/share/artica-postfix/ressources/logs/web/interface.array";

    $q                  = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql                = "SELECT * FROM nics_vlan WHERE nic='{$Interface}' ORDER BY ID"; //
    $results            = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return array();}
    if(count($results)==0){return array();}
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/reconfigure-newtork.progress";
    $ARRAY["CMD"]="/system/network/reconfigure-restart";
    $ARRAY["TITLE"]="{please_wait_building_network}";
    $ARRAY["AFTER"]="LoadAjax('netz-interfaces-status','$page?status2=yes');";

    $jsrestart="document.getElementById('netz-interfaces-status').innerHTML='<div class=center><img src=img/Eclipse-0.9s-400px.gif alt=a></div>';Loadjs('$page?status2-vlan-build-js=yes')";

     $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/udhcpd/status"));
     foreach ($json->Info as $key=>$pid){
         $udhcpd_status[$key]=$pid;
     }


    //


    $apply="<button class='btn btn-primary btn-xs' type='button' OnClick=\"$jsrestart;\">{apply}</button>";

    if(!$q->FIELD_EXISTS("nics_vlan","udhcpd")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `udhcpd` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nics_vlan","udhcpd_conf")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `udhcpd_conf` TEXT NOT NULL default 'YTowOnt9'");
    }


    foreach ($results as $index=>$ligne){
        $UDHCPD     = $tpl->button_medium("{not_installed}","none",null);
        $PARPROUTED = $tpl->icon_nothing();
        $OSPF       = $tpl->icon_nothing();
        $speed_text = $tpl->icon_nothing();
        $udhcpd     = intval($ligne["udhcpd"]);
        $masquerade = intval($ligne["masquerade"]);
        $text_class = null;
        $nic        ="vlan{$ligne["ID"]}";
        $NICNAME    = $nic;
        $masquerade_ico=null;

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?ifconfig-array=$nic");
        $MAIN       = unserialize(@file_get_contents($intpath));
        $TX         = $MAIN["TX"];
        $RX         = $MAIN["RX"];
        $DROP       = $MAIN["DROP"];
        $rx_icon    = $tpl->icon_nothing();
        $drop_icon  = $tpl->icon_nothing();
        $tx_icon    = $tpl->icon_nothing();


        if($RX>0) {$rx_icon = "<i class='fas fa-arrow-to-bottom'></i>&nbsp;" . FormatBytes($RX / 1024);}
        if($TX>0) {$tx_icon = "<i class='fas fa-arrow-to-top'></i>&nbsp;" . FormatBytes($TX / 1024);}
        if($DROP>0) {$drop_icon = "<i class='fas fa-hand-paper'></i>&nbsp;" . FormatNumber($DROP);}

        $NetStatus=new NetStatus($nic);



        if($UDHCPD_INSTALLED==1){
            $udhcp_js="Loadjs('fw.udhcpd.php?interface=$nic');";
            if($udhcpd==1){
                $UDHCPD = $tpl->button_medium("{active2} $udhcpd_status[$nic]","warning",$udhcp_js);
                if(isset($udhcpd_status[$nic])){
                    $UDHCPD = $tpl->button_medium("{stopped}","error",$udhcp_js);
                    if($udhcpd_status[$nic]>0){
                        $UDHCPD = $tpl->button_medium("{running}","ok",$udhcp_js);
                    }
                }

            }else{
                $UDHCPD = $tpl->button_medium("{inactive}","none",$udhcp_js);
            }
        }


        if ($NetStatus->InterfaceRunning){
            $IPADDR=$NetStatus->IPADDR;
            $label="label-primary";
            $text_icon="{active2}";
        }else{
            $label="label-warning";
            $text_icon="{inactive}";

        }

        if($masquerade==1){
            $masquerade_ico="&nbsp;&nbsp;<span class='label label-info'>Masquerade</span>";
        }

        $JS="Loadjs('fw.network.interfaces.php?nic-vlan-js={$ligne["ID"]}&eth=$Interface');";

        $icon="<span class='label $label font-bold'>$text_icon</span>";
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$icon</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$Interface</H3></td>";
        $html[]="<td class=\"$text_class\" style='width:99%' nowrap>
".$tpl->td_href("<H3>$IPADDR&nbsp;&nbsp;($NICNAME)$masquerade_ico</H3>",null,"$JS")."</td>";

        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>$OSPF</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$PARPROUTED</H3></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap>-</td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$UDHCPD</H3></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$apply</H3></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$speed_text</H3></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$rx_icon</H3></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$tx_icon</H3></td>";
        $html[]="<td class=\"$text_class\" style='width:1%' nowrap><H3>$drop_icon</H3></td>";
        $html[]="</tr>";


    }

    $arrayvlan["TRCLASS"]=$TRCLASS;
    $arrayvlan["TABLE"]=@implode("\n",$html);
    return $arrayvlan;

}

function sysctl_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$NetCoreRmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreRmemMax"));
	$NetIpv4TcpMemLow=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMemLow"));
	$NetIpv4TcpMemPress=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMemPress"));
	$NetIpv4TcpMemPages=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMemPages"));
	$DisableTCPEn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableTCPEn"));
	$EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
	$DisableTCPWindowScaling=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableTCPWindowScaling"));
	if($NetCoreRmemMax==0){$NetCoreRmemMax=212992;}
	if($NetIpv4TcpMemLow==0){$NetIpv4TcpMemLow=187212;}
	if($NetIpv4TcpMemPress==0){$NetIpv4TcpMemPress=249617;}
	if($NetIpv4TcpMemPages==0){$NetIpv4TcpMemPages=374424;}
	
	$EnableKernelBBR=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKernelBBR"));
	$EnableSystemNetworkOptimize=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSystemNetworkOptimize")));
	$EnableArticaAsGateway=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway")));
	
	
	
	$rmem_max[0]="{reset}";
	$rmem_max["4096"]="4096 Bytes";
	$rmem_max["16384"]="16K";
	$rmem_max["87380"]="85K";
	$rmem_max["187212"]="180K";
	$rmem_max["212992"]="208K";
	$rmem_max["249617"]="240K";
	$rmem_max["262144"]="256K";
	$rmem_max["374424"]="365K";
	$rmem_max["524288"]="512K";
	$rmem_max["2097152"]="2M";
	$rmem_max["4194304"]="4M";
	$rmem_max["6291456"]="6M";
	$rmem_max["8388608"]="8M";
	$rmem_max["16777216"]="16M";
	$rmem_max["67108864"]="64M";
	$rmem_max["134217728"]="128M";
	
	$NetIpv4TcpRmemMin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRmemMin"));
	$NetIpv4TcpRmemDef=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRmemDef"));
	$NetIpv4TcpRmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRmemMax"));
	if($NetIpv4TcpRmemMin==0){$NetIpv4TcpRmemMin=4096;}
	if($NetIpv4TcpRmemDef==0){$NetIpv4TcpRmemDef=87380;}
	if($NetIpv4TcpRmemMax==0){$NetIpv4TcpRmemMax=6291456;}
	
	
	$NetIpv4TcpWmemMin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWmemMin"));
	$NetIpv4TcpWmemDef=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWmemDef"));
	$NetIpv4TcpWmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWmemMax"));
	if($NetIpv4TcpWmemMin==0){$NetIpv4TcpWmemMin=4096;}
	if($NetIpv4TcpWmemDef==0){$NetIpv4TcpWmemDef=16384;}
	if($NetIpv4TcpWmemMax==0){$NetIpv4TcpWmemMax=4194304;}
	

	//https://wwwx.cs.unc.edu/~sparkst/howto/network_tuning.php
	//http://vietlux.blogspot.fr/2012/07/squid-proxy-tuning-for-high-perfomance.html
	//https://fasterdata.es.net/host-tuning/linux/
	
	$form[]=$tpl->field_checkbox("EnableSystemNetworkOptimize","{EnableSystemNetworkOptimize}",$EnableSystemNetworkOptimize);
	
	$form[]=$tpl->field_checkbox("EnableipV6","{enable_ipv6}",$EnableipV6,false,"{enable_ipv6_text}");
	$form[]=$tpl->field_checkbox("EnableArticaAsGateway","{ARTICA_AS_GATEWAY}",$EnableArticaAsGateway,false,"{ip_forward_text}");

	$form[]=$tpl->field_checkbox("EnableKernelBBR","Bottleneck Bandwidth and RTT","$EnableKernelBBR",false,"{BBR_EXPLAIN}");
	$form[]=$tpl->field_checkbox("DisableTCPWindowScaling","{DisableTCPWindowScaling}",$DisableTCPWindowScaling,false,"{DisableTCPWindowScaling_explain}");
	$form[]=$tpl->field_checkbox("DisableTCPEn","{DisableTCPEn}",$DisableTCPEn,false,"{DisableTCPEn_explain}");
	
	$form[]=$tpl->field_section("TCP TIME_WAIT");
	
	$NetIpv4TcpTwReuse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwReuse"));
	$NetIpv4TcpTwRecycle=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwRecycle"));
	
	
	
	$NetIpv4TcpFinTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpFinTimeOut"));
	if($NetIpv4TcpFinTimeOut==0){$NetIpv4TcpFinTimeOut=60;}
	$form[]=$tpl->field_checkbox("NetIpv4TcpTwReuse","{tcp_time_wait_reuse}",$NetIpv4TcpTwReuse,false,"{tcp_time_wait_reuse_explain}");
	$form[]=$tpl->field_checkbox("NetIpv4TcpTwRecycle","{tcp_time_wait_recycle}",$NetIpv4TcpTwRecycle,false,"{tcp_time_wait_recycle_explain}");
	
	for($i=4;$i<61;$i++){$tcp_fin_timeout[$i]="$i {seconds}";}
	$form[]=$tpl->field_array_hash($tcp_fin_timeout, "NetIpv4TcpFinTimeOut", "{tcp_fin_timeout}", $NetIpv4TcpFinTimeOut,false,"{tcp_fin_timeout_explain}");

	
	
	
	$form[]=$tpl->field_section("{TCP_Autotuning_settings}","{net_ipv4_tcp_mem_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetCoreRmemMax", "{NetCoreRmemMax}", $NetCoreRmemMax,false,"{NetCoreRmemMax_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpMemLow", "{low_threshold}", $NetIpv4TcpMemLow,false,"{NetIpv4TcpMemLow_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpMemPress", "{pressuring_memory}", $NetIpv4TcpMemPress,false,"{NetIpv4TcpMemPress}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpMemPages", "{max_memory_pages}", $NetIpv4TcpMemPages,false,"{NetIpv4TcpMemPress}");

	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpRmemMin", "{NetIpv4TcpRmemMin}", $NetIpv4TcpRmemMin,false,"{NetIpv4TcpRmemMin_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpRmemDef", "{NetIpv4TcpRmemDef}", $NetIpv4TcpRmemDef,false,"{NetIpv4TcpRmemDef_explain}");

	$form[]=$tpl->field_section(null,"{net_ipv4_tcp_wmem_explain}");
	
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpWmemMin", "{NetIpv4TcpWmemMin}", $NetIpv4TcpWmemMin,false,"{NetIpv4TcpRmemMin_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpWmemDef", "{NetIpv4TcpWmemDef}", $NetIpv4TcpWmemDef,false,"{NetIpv4TcpWmemDef_explain}");
	$form[]=$tpl->field_array_hash($rmem_max, "NetIpv4TcpWmemMax", "{NetIpv4TcpWmemMax}", $NetIpv4TcpWmemMax,false,"{NetIpv4TcpWmemMax_explain}");



	$hml[]=$tpl->form_outside("{kernelnetworkoptimization}", @implode("\n", $form),null,"{apply}","","AsSystemAdministrator");
	echo @implode("\n", $hml);
	
}

function saveNicProfile(){
    $tpl=new template_admin();
if (!isset($_POST["NetIpv4TcpWindowScaling"])){
    $_POST["NetIpv4TcpMem"]="1149471 1532631 52298942";
    $_POST["NetIpv4TcpWMem"]="4096 65536 16777216";
    $_POST["NetIpv4TcpRMem"]="4096 87380 16777216";
    $_POST["NetCoreWmemMax"]=16777216;
    $_POST["NetCoreRmemMax"]=16777216;
    if ($_POST["NicProfile"]==10) {
        $_POST["NetIpv4TcpWMem"]="4096 65536 33554432";
        $_POST["NetIpv4TcpRMem"]="4096 87380 33554432";
        $_POST["NetCoreWmemMax"]=67108864;
        $_POST["NetCoreRmemMax"]=67108864;
    }
    $_POST["NetIpv4TcpReordering"]=3;
    $_POST["NetIpv4TcpMaxSynBackLog"]=4096;
    $_POST["NetCoreNetdevMaxBacklog"]=65536;
    $_POST["NetCoreRmemDefault"]=12582912;
    $_POST["NetCoreWmemDefault"]=12582912;
    $_POST["NetIpv4TcpTimestamps"]=1;
    $_POST["NetCoreOptmemMax"]=20480;
    $_POST["NetIpv4TcpLowLatency"]=0;
    $_POST["NetIpv4TcpNoMetricsSave"]=0;
    $_POST["NetIpv4TcpMaxTWBuckets"]=1440000;
    $_POST["NetIpv4TcpKeepAliveTime"]=3600;
    $_POST["NetIpv4TcpRfc1337"]=1;
    $_POST["NetIpv4TcpSynackRetries"]=5;
    $_POST["NetIpv4TcpSynRetries"]=5;
    $_POST["NetIpv4TcpFinTimeOut"]=30;
    $_POST["NetIpv4TcpTwRecycle"]=0;
    $_POST["NetIpv4TcpTwReuse"]=1;
    $_POST["NetIpv4TcpEcn"]=2;
    $_POST["NetIpv4TcpWindowScaling"]=1;
    $tpl->SAVE_POSTs();
}
if(isset($_POST["NetIpv4TcpWindowScaling"])) {
    $tpl->SAVE_POSTs();

}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
}
function sysctl2_proxy():bool{
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NicProfile",10);
    $Configs=array(
        "NetCoreSomaxConn"=>4096,
        "NetCoreNetdevMaxBacklog"=>5000,
        "NetCoreRmemMax"=>16777216,
        "NetCoreWmemMax"=>16777216,
        "NetIpv4TcpRMem"=>"4096 87380 16777216",
        "NetIpv4TcpWMem"=>"4096 65536 16777216",
        "NetIpv4TcpTwReuse"=>1,
        "NetIpv4TcpFinTimeOut"=>15,
        "NetIpv4TcpWindowScaling"=>1,
        "NetIpv4TcpMaxSynBackLog"=>4096,
        "NetIpv4TcpSynackRetries"=>2,
        "netipv4tcpfastopen"=>1,
        "ChangeKernelTcp"=>1,
        "EnableSystemOptimize"=>1,
        "EnableSystemNetworkOptimize"=>1,
    );

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE `nics` SET checksum_offloading=1");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        return false;
    }
    foreach ($Configs as $Key=>$Value) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($Key,$Value);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    echo "Loadjs('$page?sysctl2-js=yes');";

    return admin_tracks_post("Save Kernel optimization for Proxy service");

}

function sysctl2_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $NicProfile=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NicProfile"));

    $NicProfileArray[1]="1GE";
    $NicProfileArray[10]="10GE";

    //net.ipv4.tcp_mem
    $NetIpv4TcpMem = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMem");
    if ($NicProfile==0){
        $NetIpv4TcpMem="1149471 1532631 2298942";
    }
    //net.ipv4.tcp_wmem
    $NetIpv4TcpWMem = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWMem");
    if ($NicProfile==0){
        $NetIpv4TcpWMem="4096 65536 16777216";
    }
    //net.ipv4.tcp_rmem
    $NetIpv4TcpRMem = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRMem");
    if ($NicProfile==0){
        $NetIpv4TcpRMem="4096 87380 16777216";
    }
    //net.core.wmem_max
    $NetCoreWmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreWmemMax"));
    if($NetCoreWmemMax==0){
        $NetCoreWmemMax=16777216;
    }
    //net.core.rmem_max
    $NetCoreRmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreRmemMax"));
    if($NetCoreRmemMax==0){
        $NetCoreRmemMax=16777216;
    }
    //net.core.netdev_max_backlog
    $NetCoreNetdevMaxBacklog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreNetdevMaxBacklog"));
    if($NetCoreNetdevMaxBacklog==0){
        $NetCoreNetdevMaxBacklog=65536;
    }

    //net.core.wmem_default
    $NetCoreWmemDefault=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreWmemDefault"));
    if($NetCoreWmemDefault==0){
        $NetCoreWmemDefault=31457280;
    }

    //net.core.rmem_default
    $NetCoreRmemDefault=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreRmemDefault"));
    if($NetCoreRmemDefault==0){
        $NetCoreRmemDefault=31457280;
    }

    //net.ipv4.tcp_timestamps
    $NetIpv4TcpTimestamps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTimestamps"));
    if($NicProfile==0){
        $NetIpv4TcpTimestamps=1;
    }

    //net.core.optmem_max
    $NetCoreOptmemMax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetCoreOptmemMax"));
    if($NetCoreOptmemMax==0){
        $NetCoreOptmemMax=20480;
    }

    //net.ipv4.tcp_max_syn_backlog
    $NetIpv4TcpMaxSynBackLog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMaxSynBackLog"));
    if($NetIpv4TcpMaxSynBackLog==0){
        $NetIpv4TcpMaxSynBackLog=4096;
    }

    //net.ipv4.tcp_low_latency
    $NetIpv4TcpLowLatency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpLowLatency"));

    //net.ipv4.tcp_no_metrics_save
    $NetIpv4TcpNoMetricsSave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpNoMetricsSave"));

    //net.ipv4.tcp_max_tw_buckets
    $NetIpv4TcpMaxTWBuckets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpMaxTWBuckets"));
    if($NetIpv4TcpMaxTWBuckets==0){
        $NetIpv4TcpMaxTWBuckets=1440000;
    }

    //net.ipv4.tcp_reordering
    $NetIpv4TcpReordering=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpReordering"));
    if($NicProfile==0){
        $NetIpv4TcpReordering=3;
    }

    //net.ipv4.tcp_keepalive_time
    $NetIpv4TcpKeepAliveTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpKeepAliveTime"));
    if($NetIpv4TcpKeepAliveTime==0){
        $NetIpv4TcpKeepAliveTime=3600;
    }

    //net.ipv4.tcp_rfc1337
    $NetIpv4TcpRfc1337=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpRfc1337"));
    if($NicProfile==0){
        $NetIpv4TcpRfc1337=1;
    }

    //net.ipv4.tcp_synack_retries
    $NetIpv4TcpSynackRetries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpSynackRetries"));
    if($NicProfile==0){
        $NetIpv4TcpSynackRetries=5;
    }

    //net.ipv4.tcp_syn_retries
    $NetIpv4TcpSynRetries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpSynRetries"));
    if($NicProfile==0){
        $NetIpv4TcpSynRetries=5;
    }

    //net.ipv4.tcp_ecn
    $NetIpv4TcpEcn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpEcn"));
    if($NicProfile==0){
        $NetIpv4TcpEcn=2;
    }
    //net.ipv4.tcp_tw_reuse
    $NetIpv4TcpTwReuse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwReuse"));
    if($NicProfile==0){
        $NetIpv4TcpTwReuse=1;
    }
    //net.ipv4.tcp_tw_recycle
    $NetIpv4TcpTwRecycle=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpTwRecycle"));
    //net.ipv4.tcp_fin_timeout
    $NetIpv4TcpFinTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpFinTimeOut"));
    if($NicProfile==0){$NetIpv4TcpFinTimeOut=30;}
    //net.ipv4.tcp_window_scaling
    $NetIpv4TcpWindowScaling=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIpv4TcpWindowScaling"));
    if($NicProfile==0){$NetIpv4TcpWindowScaling=1;}
    //net.ipv4.tcp_congestion_control
    $EnableKernelBBR=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKernelBBR"));
    if($NicProfile==0){$EnableKernelBBR=1;}
    $EnableArticaAsGateway=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway")));
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    $ChangeKernelTcp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChangeKernelTcp"));
    $netipv4tcpfastopen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("netipv4tcpfastopen"));
    $netipv4tcp_sack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("netipv4tcp_sack"));
    $netipv4tcp_slow_start_after_idle=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("netipv4tcp_slow_start_after_idle"));
    if (!file_exists("/etc/artica-postfix/settings/Daemons/netipv4tcpfastopen")){
        $netipv4tcpfastopen=3;
    }
    if (!file_exists("/etc/artica-postfix/settings/Daemons/netipv4tcp_sack")){
        $netipv4tcp_sack=1;
    }


    //https://wwwx.cs.unc.edu/~sparkst/howto/network_tuning.php
    //http://vietlux.blogspot.fr/2012/07/squid-proxy-tuning-for-high-perfomance.html
    //https://fasterdata.es.net/host-tuning/linux/
    $form[]=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/tune-sysctl','1024','800')","fa-solid fa-headset",null,null,"btn-blue");
    $form[]="&nbsp;";
    $form[]=$tpl->button_inline("{optimize_proxy_service}","Loadjs('$page?sysctl-proxyjs=yes')",ico_speed,null,null);


    $form[]=$tpl->field_section("{kernelnetworkoptimization}",null);
    $form[]=$tpl->field_array_select($NicProfileArray, "NicProfile", "{nic_profile}", $NicProfile,"{nic_profile_explain}","dialogInstance2.close();");
    $form[]=$tpl->field_checkbox("EnableipV6","{enable_ipv6}",$EnableipV6,false,"{enable_ipv6_text}");
    $form[]=$tpl->field_checkbox("EnableArticaAsGateway","{ARTICA_AS_GATEWAY}",$EnableArticaAsGateway,false,"{ip_forward_text}");
    $form[]=$tpl->field_checkbox("EnableKernelBBR","Bottleneck Bandwidth and RTT","$EnableKernelBBR",false,"{BBR_EXPLAIN}");

    $form[]=$tpl->field_section("{ChangeKernelTcpTitle}","{ChangeKernelTcpExplain}",true);

    $form[]=$tpl->field_checkbox("ChangeKernelTcp","{ChangeKernelTcp}",$ChangeKernelTcp,"NetIpv4TcpReordering,NetIpv4TcpWindowScaling,NetIpv4TcpEcn,NetIpv4TcpTwReuse,NetIpv4TcpTwRecycle,NetIpv4TcpFinTimeOut,NetIpv4TcpSynRetries,NetIpv4TcpSynackRetries,NetIpv4TcpRfc1337,NetIpv4TcpKeepAliveTime,NetIpv4TcpMaxTWBuckets,NetIpv4TcpNoMetricsSave,NetIpv4TcpLowLatency,NetCoreOptmemMax,NetIpv4TcpTimestamps,NetCoreRmemDefault,NetCoreWmemDefault,NetCoreNetdevMaxBacklog,NetIpv4TcpMaxSynBackLog,NetCoreRmemMax,NetCoreWmemMax,NetIpv4TcpRMem,NetIpv4TcpWMem,NetIpv4TcpMem,netipv4tcpfastopen,netipv4tcp_slow_start_after_idle,netipv4tcp_sack");

    $form[]=$tpl->field_checkbox("NetIpv4TcpWindowScaling","{NetIpv4TcpWindowScaling}",$NetIpv4TcpWindowScaling,false,"{NetIpv4TcpWindowScalingExplain}");
    for($i=0;$i<3;$i++){$tcp_ecn[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_ecn,"NetIpv4TcpEcn","{NetIpv4TcpEcn}",$NetIpv4TcpEcn,false,"{NetIpv4TcpEcnExplain}");
    for($i=0;$i<3;$i++){$tcp_twreuse[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_twreuse,"NetIpv4TcpTwReuse","{tcp_time_wait_reuse}",$NetIpv4TcpTwReuse,false,"{tcp_time_wait_reuse_explain}");
    for($i=0;$i<4;$i++){$tcp_fastopen[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_fastopen,"netipv4tcpfastopen","{netipv4tcpfastopen}",$netipv4tcpfastopen,false,"{netipv4tcpfastopen_explain}");
    $form[]=$tpl->field_checkbox("netipv4tcp_slow_start_after_idle","{tcp_slow_start_after_idle}",$netipv4tcp_slow_start_after_idle,false,"{tcp_slow_start_after_idle_explain}");
    $form[]=$tpl->field_checkbox("netipv4tcp_sack","{netipv4tcp_sack}",$netipv4tcp_sack,false,"{netipv4tcp_sack_explain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpTwRecycle","{tcp_time_wait_recycle}",$NetIpv4TcpTwRecycle,false,"{tcp_time_wait_recycle_explain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpFinTimeOut", "{tcp_fin_timeout}", $NetIpv4TcpFinTimeOut,"{tcp_fin_timeout_explain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpSynRetries", "{NetIpv4TcpSynRetries}", $NetIpv4TcpSynRetries,"{NetIpv4TcpSynRetriesExplain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpSynackRetries", "{NetIpv4TcpSynackRetries}", $NetIpv4TcpSynackRetries,"{NetIpv4TcpSynackRetriesExplain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpRfc1337","{NetIpv4TcpRfc1337}",$NetIpv4TcpRfc1337,false,"{NetIpv4TcpRfc1337Explain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpKeepAliveTime", "{NetIpv4TcpKeepAliveTime}", $NetIpv4TcpKeepAliveTime,"{NetIpv4TcpKeepAliveTimeExplain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpMaxTWBuckets", "{NetIpv4TcpMaxTWBuckets}", $NetIpv4TcpMaxTWBuckets,"{NetIpv4TcpMaxTWBucketsExplain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpNoMetricsSave","{NetIpv4TcpNoMetricsSave}",$NetIpv4TcpNoMetricsSave,false,"{NetIpv4TcpNoMetricsSaveExplain}");
    $form[]=$tpl->field_checkbox("NetIpv4TcpLowLatency","{NetIpv4TcpLowLatency}",$NetIpv4TcpLowLatency,false,"{NetIpv4TcpLowLatencyExplain}");

    $form[]=$tpl->field_numeric("NetIpv4TcpReordering","{NetIpv4TcpReordering}",$NetIpv4TcpReordering,"{NetIpv4TcpReorderingExplain}");

    $form[]=$tpl->field_numeric("NetCoreOptmemMax", "{NetCoreOptmemMax}", $NetCoreOptmemMax,"{NetCoreOptmemMaxExplain}");
    for($i=0;$i<3;$i++){$tcp_timestamps[$i]="$i";}
    $form[]=$tpl->field_array_hash($tcp_timestamps, "NetIpv4TcpTimestamps", "{NetIpv4TcpTimestamps}", $NetIpv4TcpTimestamps,false,"{NetIpv4TcpTimestampsExplain}");
    $form[]=$tpl->field_numeric("NetCoreRmemDefault", "{NetCoreRmemDefault}", $NetCoreRmemDefault,"{NetCoreRmemDefaultExplain}");
    $form[]=$tpl->field_numeric("NetCoreWmemDefault", "{NetCoreWmemDefault}", $NetCoreWmemDefault,"{NetCoreWmemDefaultExplain}");
    $form[]=$tpl->field_numeric("NetCoreNetdevMaxBacklog", "{NetCoreNetdevMaxBacklog}", $NetCoreNetdevMaxBacklog,"{NetCoreNetdevMaxBacklogExplain}");
    $form[]=$tpl->field_numeric("NetIpv4TcpMaxSynBackLog", "{NetIpv4TcpMaxSynBackLog}", $NetIpv4TcpMaxSynBackLog,"{NetIpv4TcpMaxSynBackLogExplain}");
    $form[]=$tpl->field_numeric("NetCoreRmemMax", "{NetCoreRmemMax1}", $NetCoreRmemMax,"{NetCoreRmemMaxExplain1}");
    $form[]=$tpl->field_numeric("NetCoreWmemMax", "{NetCoreWmemMax}", $NetCoreWmemMax,"{NetCoreWmemMaxExplain}");
    $form[]=$tpl->field_text("NetIpv4TcpRMem", "{NetIpv4TcpRMem}", $NetIpv4TcpRMem,false,"{NetIpv4TcpRMemExplain}");
    $form[]=$tpl->field_text("NetIpv4TcpWMem", "{NetIpv4TcpWMem}", $NetIpv4TcpWMem,false,"{NetIpv4TcpWMemExplain}");
    $form[]=$tpl->field_text("NetIpv4TcpMem", "{NetIpv4TcpMem}", $NetIpv4TcpMem,false,"{NetIpv4TcpMemExplain}");



    $jsrestart="dialogInstance2.close();";

    $hml[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
    echo @implode("\n", $hml);
    return true;
}

function nic_virtuals_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
    $nicid_text="";
	$nicid=intval($_GET["nic-virtual-js"]);
	$nicz=new system_nic($eth);
	if($nicid==0){$nicid_text="{new_interface}";}
    $function=$_GET["function"];
	$title="$nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
	$tpl->js_dialog2($title, "$page?nic-virtual=$nicid&eth=$eth&function=$function");
}
function nic_vlan_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["eth"];
    $nicid=intval($_GET["nic-vlan-js"]);
    $nicz=new system_nic($eth);
    if($nicid==0){$nicid_text="{new_vlan}";}
    $title="VLAN: $nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
    $tpl->js_dialog2($title, "$page?nic-vlan=$nicid&eth=$eth");
}
function nic_virtuals_delete_js(){
	$nicid=intval($_GET["nic-virtual-delete-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
    $rf=refreshjs();
	$tpl->js_confirm_delete("{interface} {$_GET["eth"]}:$nicid ", "delete_nic_virtual", $nicid,
	"$rf;LoadAjax('nics-virtuals','$page?nic-virtuals-list=$eth');");
}
function nic_vlan_delete_js(){
    $nicid=intval($_GET["nic-vlan-delete-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["md"];
    $rf=refreshjs();
    $tpl->js_confirm_delete("{interface} vlan$nicid ", "delete_nic_vlan", $nicid,
        "$('#$md').remove();$rf");

}

function nic_vlan_delete(){

    $ID=intval($_POST["delete_nic_vlan"]);
    $qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $qlite->QUERY_SQL("DELETE FROM nics_vlan WHERE ID=$ID");
    if(!$qlite->ok){echo $qlite->mysql_error;exit;}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?build-vlans=yes");

}

function nic_virtuals_delete(){
	$ID=intval($_POST["delete_nic_virtual"]);
	$qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$fwlite=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$sql="SELECT nic,ipaddr FROM nics_virtuals WHERE ID='$ID'";
	$results=$qlite->QUERY_SQL($sql);
	
	$ligne=$results[0];
	$ipaddr=$ligne["ipaddr"];
	$eth="{$ligne["nic"]}:{$ID}";
	
	
	
	
	$sql="DELETE FROM nics_virtuals WHERE ID=$ID";
	$qlite->QUERY_SQL($sql);
	if(!$qlite->ok){echo $qlite->mysql_error;return;}
	if($fwlite->TABLE_EXISTS("iptables_bridge")){
		$sql="DELETE FROM iptables_bridge WHERE nics_virtuals_id=$ID";
        $fwlite->QUERY_SQL($sql);
		if(!$fwlite->ok){echo $fwlite->mysql_error;return;}
	}
	



    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?down-interface=$eth");
		
		
}

function nic_vlan_masquerade(){
    $ID=intval($_GET["nic-vlan-masquerade"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");
    }
    $ligne=$q->mysqli_fetch_array("SELECT masquerade FROM nics_vlan WHERE ID='$ID'");
    $enabled=intval($ligne["masquerade"]);

    if($enabled==1){
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $q->QUERY_SQL("UPDATE nics_vlan SET `masquerade`=0 WHERE ID=$ID");
        if(!$q->ok){$tpl->js_error($q->mysql_error);exit;}
        admin_tracks("Disable masquerade for vlan interface $ID");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?build-vlans=yes");
        return;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics_vlan SET masquerade=1 WHERE ID='$ID'");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        exit;
    }

    admin_tracks("Enable masquerade for vlan interface $ID");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?build-vlans=yes");

}

function nic_vlan_enable(){
    $ID=intval($_GET["nic-vlan-enable"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");
    }

    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM nics_vlan WHERE ID='$ID'");
    $enabled=intval($ligne["enabled"]);
    $sock=new sockets();
    if($enabled==1){

        $data=$sock->REST_API("/system/network/vlan/unlink/$ID");
        $json=json_decode($data);
        if(!$json->Status){
            $tpl->js_error("unlink/$ID API error $json->Error");
            return false;
        }

        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $q->QUERY_SQL("UPDATE nics_vlan SET `enabled`=0 WHERE ID=$ID");
        if(!$q->ok){$tpl->js_error($q->mysql_error);exit;}
        return admin_tracks("Disable vlan interface $ID");
    }

    $data=$sock->REST_API("/system/network/vlan/start/$ID");
    $json=json_decode($data);
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics_vlan SET enabled=1 WHERE ID='$ID'");
    if(!$q->ok){
        $tpl->js_error($q->mysql_error);
        exit;
    }
    return admin_tracks("Enable vlan interface $ID");
}

function nic_vlan_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $eth=$_GET["eth"];
    $nicid=intval($_GET["nic-vlan"]);
    if($nicid==0){$nicid_text="{new_vlan}";}
    $nicz=new system_nic($eth);
    $ligne=array();
    $title="VLAN: $nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
    $rf=refreshjs();
    $jsafter="$rf;LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');LoadAjax('netz-interfaces-status','$page?status2=yes');dialogInstance2.close();";
    $title_button="{add}";

    if($nicid>0){
        $rf=refreshjs();
        $jsafter="$rf;LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');";
        $sql="SELECT * FROM nics_vlan WHERE ID='$nicid'";
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
            $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");
        }

        $results=$q->QUERY_SQL($sql);
        $ligne=$results[0];
        $title_button="{apply}";
    }

    if(isset($_GET["default-datas"])){
        $default_array=unserialize(base64_decode($_GET["default-datas"]));
        if(is_array($default_array)){
            $ligne["nic"]=$default_array["NIC"];
            if(preg_match("#(.+?)\.([0-9]+)$#",$default_array["IP"],$re)){
                if($re[2]>254){$re[2]=1;}
                $re[2]=$re[2]+1;
                $ligne["ipaddr"]="{$re[1]}.{$re[2]}";
                $ligne["gateway"]=$default_array["GW"];
                $ligne["netmask"]=$default_array["NETMASK"];
            }
        }
    }

    for($i=1;$i<4095;$i++){
        $VLANS[$i]=$i;
    }


    if($ligne["netmask"]==null){$ligne["netmask"]=$nicz->NETMASK;}
    $form[]=$tpl->field_hidden("save_nic_vlan", $eth);
    $form[]=$tpl->field_hidden("ID", $nicid);
    $form[]=$tpl->field_ipaddr("ipaddr", "{tcp_address}", $ligne["ipaddr"],true);
    $form[]=$tpl->field_array_hash($VLANS,"vlanid", "nonull:VLAN ID", $ligne["vlanid"],true);
    $form[]=$tpl->field_ipaddr("netmask", "{netmask}", $ligne["netmask"],true);
    $form[]=$tpl->field_cdir("cdir", "CDIR", $ligne["cdir"],true);
    $form[]=$tpl->field_checkbox("ForceGateway","{use_a_gateway}",$ligne["ForceGateway"]);
    $form[]=$tpl->field_checkbox("masquerade","MASQUERADE",$ligne["masquerade"]);
    $form[]=$tpl->field_ipaddr("gateway", "{gateway}", $ligne["gateway"]);
    $form[]=$tpl->field_numeric("metric","{metric}",$ligne["metric"]);
    $security="AsSystemAdministrator";
    $html=$tpl->form_outside($title, @implode("\n", $form),null,$title_button,$jsafter,$security);
    echo $tpl->_ENGINE_parse_body($html);

}
function nic_vlan_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="CREATE TABLE IF NOT EXISTS `nics_vlan` (
			  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `nic` TEXT NOT NULL,
			  `org` TEXT NOT NULL,
			  `ipaddr` TEXT NOT NULL,
			  `netmask` TEXT NOT NULL,
			  `cdir` TEXT NOT NULL,
			  `gateway` TEXT NOT NULL,
			  `broadcast` TEXT NOT NULL,
			  `network` TEXT NOT NULL,
			  `vlanid` INTEGER NOT NULL,
			  `metric` INTEGER NOT NULL,
			  `udhcpd` INTEGER NOT NULL DEFAULT 0,
			  `ForceGateway` INTEGER NOT NULL DEFAULT 0,            
			  `masquerade` INTEGER NOT NULL DEFAULT 0,
			  `enabled` INTEGER NOT NULL DEFAULT 1)";
    $q->QUERY_SQL($sql);
    if(!$q->FIELD_EXISTS("nics_vlan","ForceGateway")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `ForceGateway` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nics_vlan","udhcpd")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `udhcpd` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){
        $q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL DEFAULT 0");
    }

    $nic=$_POST["save_nic_vlan"];
    $ID=intval($_POST["ID"]);
    $ipaddr=$_POST["ipaddr"];
    $netmask=$_POST["netmask"];
    $ForceGateway=$_POST["ForceGateway"];
    $gateway=$_POST["gateway"];
    $network=$_POST["cdir"];
    $broadcast=$_POST["broadcast"];
    $enabled=1;
    $org="local";
    $metric=$_POST["metric"];
    $vlanid=$_POST["vlanid"];
    $masquerade=intval($_POST["masquerade"]);


    if($ID==0){
        $sql="INSERT INTO nics_vlan(  `nic`,`org`,`ipaddr`,`netmask`,`cdir`,`gateway`,`broadcast`,`network` ,  `vlanid` ,  `metric` ,  `ForceGateway`, `enabled`,`masquerade`) VALUES ('$nic','$org','$ipaddr','$netmask','$network','$gateway','$broadcast','$network','$vlanid','$metric','$ForceGateway','$enabled','$masquerade');";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}

        $sock=new sockets();
        $sock->REST_API("/system/network/vlan/build");
        return admin_tracks("Create new vlan interface $ID $ipaddr/$netmask masquerade=$masquerade");


    }

    $sql="UPDATE nics_vlan SET `nic` ='$nic',  `org` ='$org',  `ipaddr` ='$ipaddr',
    `netmask` ='$netmask',  `cdir` ='$network',  
    `gateway` ='$gateway',  `broadcast` ='$broadcast',
    `network` ='$network',  `vlanid` =$vlanid,`masquerade`='$masquerade',
    `metric` =$metric, `ForceGateway` =$ForceGateway WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}

    $sock=new sockets();
    $sock->REST_API("/system/network/vlan/reconfigure/$ID");
    return admin_tracks("Update vlan interface $ID $ipaddr/$netmask masquerade=$masquerade");
}

function nic_virtuals_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
    $nicid_text="";
    $function=$_GET["function"];
	$nicid=intval($_GET["nic-virtual"]);	
	if($nicid==0){$nicid_text="{new_interface}";}
	$nicz=new system_nic($eth);
	$title="$nicz->netzone: $nicz->NICNAME ($eth) $nicid_text";
    $ligne=array();
	if(strlen($function)<3){
        $function="blur";
    }
	$users=new usersMenus();

	
	if($users->LinuxDistriCode=="DEBIAN"){
		if(preg_match("#Debian\s+([0-9]+)\.#",$users->LinuxDistriFullName,$re)){
			$DEBIAN_MAJOR=$re[1];
			if($DEBIAN_MAJOR==6){$FailOver=1;}
		}
	
	}
    $rf=refreshjs();
	$jsafter="$rf;$function();dialogInstance2.close();";
	$title_button="{add}";
	
	if($nicid>0){
		$jsafter="$rf;$function();";
		$sql="SELECT * FROM nics_virtuals WHERE ID='$nicid'";
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$results=$q->QUERY_SQL($sql);
		$ligne=$results[0];
		$title_button="{apply}";
	}
	
	if(isset($_GET["default-datas"])){
		$default_array=unserialize(base64_decode($_GET["default-datas"]));
		if(is_array($default_array)){
			$ligne["nic"]=$default_array["NIC"];
			if(preg_match("#(.+?)\.([0-9]+)$#",$default_array["IP"],$re)){
				if($re[2]>254){$re[2]=1;}
				$re[2]=$re[2]+1;
				$ligne["ipaddr"]="$re[1].$re[2]";
				$ligne["gateway"]=$default_array["GW"];
				$ligne["netmask"]=$default_array["NETMASK"];
			}
		}
	}
	
	if($ligne["metric"]==0){$ligne["metric"]=100+$_GET["ID"];}

	if($ligne["netmask"]==null){$ligne["netmask"]=$nicz->NETMASK;}
	$form[]=$tpl->field_hidden("save_nic_virtual", $eth);
	$form[]=$tpl->field_hidden("ID", $nicid);
	$form[]=$tpl->field_ipaddr("ipaddr", "{tcp_address}", $ligne["ipaddr"],true);
	$form[]=$tpl->field_ipaddr("netmask", "{netmask}", $ligne["netmask"],true);
	$form[]=$tpl->field_cdir("cdir", "CDIR", $ligne["cdir"],true);
	$form[]=$tpl->field_checkbox("ForceGateway","{use_a_gateway}",$ligne["ForceGateway"]);
	$form[]=$tpl->field_ipaddr("gateway", "{gateway}", $ligne["gateway"]);
	$form[]=$tpl->field_numeric("metric","{metric}",$ligne["metric"]);
	$form[]=$tpl->field_checkbox("failover","failover",$ligne["failover"]);
	
	
	
	$security="AsSystemAdministrator";
	$html=$tpl->form_outside($title, @implode("\n", $form),null,$title_button,$jsafter,$security);
	echo $tpl->_ENGINE_parse_body($html);

	
}
function nic_virtuals_save():bool{
	$tpl=new template_admin();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}		
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return false;}
	foreach ($_POST as $key=>$val){$_POST[$key]=url_decode_special_tool($val);}reset($_POST);
	$_POST["nic"]=$_POST["save_nic_virtual"];
	
	if($_POST["nic"]==null){echo $tpl->_ENGINE_parse_body("{nic}=null");exit;}
	$PING=trim($GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?ping=".urlencode($_POST["ipaddr"])));
	
	if($PING=="TRUE"){
		echo $tpl->javascript_parse_text("{$_POST["ipaddr"]}:<br>{ip_already_exists_in_the_network}");
		return true;
	}
	
	if($_POST["failover"]==1){
		$_POST["gateway"]=$_POST["ipaddr"];
		$_POST["netmask"]="255.255.255.255";
		$_POST["ForceGateway"]=0;
		
	}
	if($_POST["metric"]==0){$_POST["metric"]=lastmetric();}
	
	$NoGatewayForVirtualNetWork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoGatewayForVirtualNetWork");
	if(!is_numeric($NoGatewayForVirtualNetWork)){$NoGatewayForVirtualNetWork=0;}	
	
	if($NoGatewayForVirtualNetWork==1){$_POST["gateway"]=null;}
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

	
	$sql="INSERT INTO nics_virtuals (nic,org,ipaddr,netmask,cdir,gateway,ForceGateway,failover,metric,openvpn_nic,ipv6)
	VALUES('{$_POST["nic"]}','none','{$_POST["ipaddr"]}','{$_POST["netmask"]}',
	'{$_POST["cdir"]}','{$_POST["gateway"]}',{$_POST["ForceGateway"]},{$_POST["failover"]},{$_POST["metric"]},'0','0');
	";
	
	if($_POST["ID"]>0){
		$sql="UPDATE nics_virtuals SET nic='{$_POST["nic"]}',
		ipaddr='{$_POST["ipaddr"]}',
		netmask='{$_POST["netmask"]}',
		cdir='{$_POST["cdir"]}',
		gateway='{$_POST["gateway"]}',
		ForceGateway='{$_POST["ForceGateway"]}',
		failover='{$_POST["failover"]}',
		metric='{$_POST["metric"]}'
		WHERE ID={$_POST["ID"]}";
	}

    writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}


    $sock=new sockets();
    $sock->REST_API("/system/network/virtual/interfaces/build/{$_POST["nic"]}");


	return admin_tracks("Create or Update Virtual Network Interface from {$_POST["nic"]} {$_POST["ipaddr"]}");
}
function lastmetric(){
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT metric as tcount FROM `nics` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	$hash[$ligne["metric"]]=$ligne["metric"];

	$sql="SELECT metric as tcount FROM `nics_vlan` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	$hash[$ligne["metric"]]=$ligne["metric"];

	$sql="SELECT metric as tcount FROM `nic_virtuals` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
	$results=$q->QUERY_SQL($sql);$ligne=$results[0];
	$hash[$ligne["metric"]]=$ligne["metric"];

	krsort($hash[$ligne["metric"]]);
	foreach ($hash as $a=>$b){
        VERBOSE("$a=>$b",__LINE__);
        $f[]=$b;
    }

	return $f[0]+1;

}

function nic_bond():bool{
    $eth=$_GET["nic-bond"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="SELECT * FROM `bond_interfaces` WHERE `nic`='$eth'";
    $results=$q->QUERY_SQL($sql);

    if(intval($results[0]["miimon"])  == 0) {$results[0]["miimon"] =100;}
    if(intval($results[0]["downdelay"])  == 0) {$results[0]["downdelay"] =200;}
    if(intval($results[0]["updelay"])  == 0) {$results[0]["updelay"] =100;}
    $listInterfaces=array();
    $availableInterfaces=$GLOBALS["CLASS_UNIX"]->NETWORK_ALL_INTERFACES();
    foreach ($availableInterfaces as $index=>$values)
    {
        if($index=='lo' || preg_match('/bond/i', $index)|| preg_match('/tun/i', $index)){
           unset($availableInterfaces[$index]);
       }

    }
    foreach ($availableInterfaces as $key=>$item) {
        $listInterfaces[$key] = $key;
    }
    $selectMembers = array();
    $selectMembersExplode = explode(",",$results[0]["members"]);
    foreach ($selectMembersExplode as $members){
        $selectMembers["$members"]="$members";
    }
    $modes[0]="Balance-rr";
    $modes[1]="Active-Backup";
    $modes[2]="Balance XOR";
    $modes[3]="Broadcast";
    $modes[4]="IEEE 802.3ad / LACP";
    $modes[5]="Transmit Load Balancing";
    $modes[6]="Adaptive Load Balancing";

    $xmit_hash_policy["layer2"]="layer2";
    $xmit_hash_policy["layer2+3"]="layer2+3";
    $xmit_hash_policy["layer3+4"]="layer3+4";
    $xmit_hash_policy["encap2+3"]="encap2+3";
    $xmit_hash_policy["encap3+4"]="encap3+4";
    $lacp_rate[0]="slow";
    $lacp_rate[1]="fast";
    $form[]=$tpl->field_hidden("save-bond-interface", $eth);
    $form[]=$tpl->field_hidden("bond-id", intval($results[0]["ID"]));
    $form[]=$tpl->field_section("{bond}","{bond_explain}");
    $form[]=$tpl->field_picklist($listInterfaces,"members","{bond_members}",$selectMembers);
    $form[]=$tpl->field_array_hash($listInterfaces,"primary_member","{primary_member}",$results[0]["primary_member"],false,"{primary_member_explain}");
    $form[]=$tpl->field_array_hash($modes,"bond_mode","{bond_mode}",intval($results[0]["mode"]),true,"{bond_mode_explain}");
    $form[]=$tpl->field_numeric("bond_miimon","{bond_miimon}",intval($results[0]["miimon"]),"{bond_miimon_explain}");
    $form[]=$tpl->field_numeric("bond_downdelay","{bond_downdelay}",intval($results[0]["downdelay"]),"{bond_downdelay_explain}");
    $form[]=$tpl->field_numeric("bond_updelay","{bond_updelay}",intval($results[0]["updelay"]),"{bond_updelay_explain}");
    $form[]=$tpl->field_section("{bond_extra}","{bond_extra_explain}");
    $form[]=$tpl->field_array_hash($xmit_hash_policy,"xmit_hash_policy","{xmit_hash_policy}",$results[0]["xmit_hash_policy"],false,"{xmit_hash_policy_explain}");
    $form[]=$tpl->field_array_hash($lacp_rate,"lacp_rate","{lacp_rate}",$results[0]["lacp_rate"],false,"{lacp_rate_explain}");
    $priv           = "AsSystemAdministrator";
    //$jsRestart      = "restart_js()";
    $rf=refreshjs();

    $jsafter="$rf;dialogInstance2.close();";
    $myform         = $tpl->form_outside(null, $form,null,"{apply}",$jsafter,$priv);
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}

function save_bond_interface(){
    $tpl=new templates();
    $eth=$_POST["save-bond-interface"];
    $ID=intval($_POST["bond-id"]);
    $mode=intval($_POST["bond_mode"]);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    //Error Detection
    if(empty($_POST["members"])){
        echo "jserror:".$tpl->javascript_parse_text("{select_at_least_one_member}");return;
    }

    if(!$mode==1 || !$mode==5 || !$mode==6){
        if(!empty($_POST["primary_member"])){
            echo "jserror:".$tpl->javascript_parse_text("{primary_interfaces_is_only_supported_in_active_backup_mode}");return;
        }

    }
    if($mode==1 || $mode==5 || $mode==6){
        if(empty($_POST["primary_member"])){
            echo "jserror:".$tpl->javascript_parse_text("{active_backup_mode_needs_one_primary_interface}");return;
        }
        $primaryIsmember = array();
        $explodeMembers = explode(",",$_POST["members"]);
        foreach ($explodeMembers as $members){
            $primaryIsmember["$members"]="$members";
        }
        if(!array_key_exists($_POST["primary_member"], $primaryIsmember)) {
            echo "jserror:".$tpl->javascript_parse_text("{primary_member_is_not_in_member_list}");return;
        }
    }

    if($mode==2 || $mode==4 || $mode==5 || $mode==6) {
        if(empty($_POST["xmit_hash_policy"])){
            echo "jserror:".$tpl->javascript_parse_text("{xmit_hash_policy_mandatory}");return;
        }
    }

    if($mode==2 || $mode==4 || $mode==5 || $mode==6 ) {
        if(intval($_POST["lacp_rate"])>1){
            $_POST["lacp_rate"]=0;
        }
        if(!isset($_POST["lacp_rate"])){
            echo "jserror:".$tpl->javascript_parse_text("{lacp_rate_mandatory}");return;
        }
    }

    if($mode==0 || $mode==1 || $mode==3) {
        if(!empty($_POST["xmit_hash_policy"])){
            echo "jserror:".$tpl->javascript_parse_text("{xmit_hash_policy_not_supported}");return;
        }
    }

    if($mode==0 || $mode==1 || $mode==3) {

        if(isset($_POST["lacp_rate"])){
            if(strlen($_POST["lacp_rate"])>0){
                echo "jserror:".$tpl->javascript_parse_text("{lacp_rate_not_supported}");return;
            }

        }
    }

    $sql="INSERT INTO bond_interfaces (nic,members,primary_member,mode,miimon,downdelay,updelay,xmit_hash_policy,lacp_rate) VALUES('$eth','{$_POST["members"]}','{$_POST["primary_member"]}','$mode','{$_POST["bond_miimon"]}','{$_POST["bond_downdelay"]}','{$_POST["bond_updelay"]}','{$_POST["xmit_hash_policy"]}','{$_POST["lacp_rate"]}');";

    if($ID>0){
        $sql="UPDATE bond_interfaces SET nic='$eth',
		members='{$_POST["members"]}',
		primary_member='{$_POST["primary_member"]}',
		mode='$mode',
		miimon='{$_POST["bond_miimon"]}',
		downdelay='{$_POST["bond_downdelay"]}',
		updelay='{$_POST["bond_updelay"]}',
        xmit_hash_policy='{$_POST["xmit_hash_policy"]}',
        lacp_rate='{$_POST["lacp_rate"]}'
		WHERE ID={$ID}";
    }


    writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);

    $q->QUERY_SQL($sql);
    if(!$q->ok){if(preg_match("#Unknown col#i", $q->mysql_error)){$q->BuildTables();$q->QUERY_SQL($sql);}}
    if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
    $resetNic = explode(",",$_POST["members"]);
    foreach ($resetNic as $nicToReset) {
        $sql = "DELETE from `nics` where `Interface`='$nicToReset'";
        $q->QUERY_SQL($sql);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ETHCONFIG-$nicToReset","");
        $nicInfo = $GLOBALS["CLASS_UNIX"]->InterfaceToIP("$nicToReset");
        if(is_array(($nicInfo))){
            if($nicInfo["IP"]!=="0.0.0.0"){
                writelogs("RESET IFACE {$nicInfo["IP"]} {$nicInfo["NETMASK"]} $nicToReset",__FUNCTION__,__FILE__,__LINE__);
                $ippref = $GLOBALS["CLASS_UNIX"]->CleanInterfacesForBondMembers($nicToReset,$nicInfo["IP"],$nicInfo["NETMASK"]);
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?reset-nics=yes&eth=$nicToReset&ippref=$ippref");
            }
        }
    }
}
function nic_virtuals(){

	$eth=$_GET["nic-virtuals"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='nics-virtual-buttons' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&nic-virtuals-list=$eth");
    echo $tpl->_ENGINE_parse_body($html);
}
function nic_vlans():bool{
    $page=CurrentPageName();
    $eth=$_GET["nic-vlans"];
    $tpl=new template_admin();

    $topbuttons[] = array("Loadjs('$page?nic-vlan-js=0&eth=$eth');", ico_plus, "{new_vlan}");
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    $html[]="</div>";

    $html[]="<div id='nics-vlans'></div>
	<script>
		LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');
	</script>	
	";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_config_tab(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["nic-config-tab"];
    $md=$_GET["md"];
	$MIITOOLS=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?mii-tools=yes&eth=$eth"));
    $EnableVLANs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs");
	$bonding = false;
    if(preg_match("#^bond[0-9]+#", $eth)){
        $bonding = true;
    }
	$array[$eth]="$page?nic-config=$eth&md=$md";
    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
    if($EnableipV6==1){
        $array["IPv6"]="fw.network.interfaces.ipv6.php?nic=$eth&md=$md";
    }


    if($bonding) {
        $array["{bond}"]="$page?nic-bond=$eth&md=$md";
    }

	if(preg_match("#^(wlan|wlp)[0-9]+#", $eth)){
		$array["Wifi"]="fw.network.wifi.php?nic=$eth&md=$md";
	}

	$array["Multipath"]="$page?multipath-section=$eth&md=$md";
	$array["{security}"]="$page?nic-security=$eth&md=$md";
	$array["{features}"]="$page?nic-features=$eth&md=$md";
    if(!$bonding) {
        $array["{ip_aliasing}"] = "$page?nic-virtuals=$eth&md=$md";
    }

    $array["{mirror}"] = "fw.network.interfaces.mirror.php?eth=$eth&md=$md";


	if($EnableVLANs==1){
        $array["VLAN"]="$page?nic-vlans=$eth&md=$md";
    }

	if(isset($MIITOOLS["{flow_control}"])) {
        if ($MIITOOLS["{flow_control}"]) {
            $array[$eth] = "$page?mii-tools=$eth&md=$md";
        }
    }
	
	//$array["{infos}"]="$page?infos=yes&mac=$mac&CallBackFunction=$CallBackFunction";
	
	echo $tpl->tabs_default($array);
}


function nic_mii_tool(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=null;
	$MIITOOLS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?mii-tools=yes&eth=$eth")));
	
	
	$form_miitolsA[null]="{select}";
	$form_miitolsHT["HD"]="Half duplex";
	$form_miitolsHT["FD"]="Full duplex";
	
	foreach ($MIITOOLS["CAP"] as $val=>$b){
		$caption=$val;
		if(strpos($MIITOOLS["INFOS"], $val)>0){$MII_DEFAULT=$val;}
		if(preg_match("#([0-9]+)(.*?)-([A-Z]+)#", $val,$re)){
			$caption="{$re[1]} {$re[2]} {$form_miitolsHT[$re[3]]}";
		}
		$form_miitolsA[$val]=$caption;
	
	}
	
	if($MIITOOLS["FLOWC"]==1){$explflw=" {flow_control}";}
	
	
	$form[]=$tpl->field_checkbox("autonegotiation","Autonegotiation",$MIITOOLS["AUTONEG"]);
	$form[]=$tpl->field_checkbox("flow-control","{flow_control}",$MIITOOLS["FLOWC"]);
	$form[]=$tpl->field_array_hash($form_miitolsA, "media", "{type}", $MII_DEFAULT);
	$security="AsSystemAdministrator";
	$html=$tpl->form_outside(null, @implode("\n", $form),"{$MIITOOLS["INFOS"]} $explflw","{apply}","LoadAjax('network-interfaces-table','$page?table=yes');",$security);
	echo $tpl->_ENGINE_parse_body($html);
	
}

function nic_security(){
    $md=$_GET["md"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$EnableipV6=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworksManagement");
	if(!is_numeric($DisableNetworksManagement)){$DisableNetworksManagement=0;}
	$Interface=$_GET["nic-security"];
	$niClass=new system_nic($Interface);
	
	$title="$niClass->netzone: $niClass->NICNAME ($Interface)";
	$form[]=$tpl->field_hidden("nic_security", $Interface);
	$form[]=$tpl->field_checkbox("SysCtlEnable","{enabled}",$niClass->SysCtlEnable,true);
	$form[]=$tpl->field_checkbox("RPFilter","{IPspoofingprotection}",$niClass->RPFilter,false,"{IPspoofingprotection_text}");
	$form[]=$tpl->field_checkbox("LogMartians","{log_martians}",$niClass->LogMartians,false,"{log_martians_text}");
	$form[]=$tpl->field_checkbox("AcceptSourceRoute","{accept_source_route}",$niClass->AcceptSourceRoute,false,"{accept_source_route_text}");
	$form[]=$tpl->field_checkbox("forwarding","{tcp_forwarding}",$niClass->forwarding,false,"{tcp_forwarding}");
	$form[]=$tpl->field_checkbox("MCForwarding","{MCForwarding}",$niClass->MCForwarding,false,"{MCForwarding}");
	$form[]=$tpl->field_checkbox("AcceptRedirects","{AcceptRedirects}",$niClass->AcceptRedirects,false,"{AcceptRedirects}");
	$form[]=$tpl->field_checkbox("SendRedirects","{SendRedirects}",$niClass->SendRedirects,false,"{SendRedirects}");
	$security="AsSystemAdministrator";
	$html=$tpl->form_outside("{security}", @implode("\n", $form),null,"{apply}","LoadAjax('network-interfaces-table','$page?table=yes');",$security);
	echo $tpl->_ENGINE_parse_body($html);

}
function nic_config():bool{
    $page=CurrentPageName();
    $eth=$_GET["nic-config"];
    $md=$_GET["md"];
    $html="<div id='progress-interface-$eth'></div>
    <div id='div-works-$eth'></div>
    <script>LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');</script>";
    echo $html;
    return true;
}
function nic_enable_interface():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-enable-interface"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET enabled=1 WHERE Interface='$eth'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Enable the Network Interface $eth");
}
function nic_disable_interface():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-disable-interface"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET enabled=0 WHERE Interface='$eth'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Disable the Network Interface $eth");
}
function nic_disable_dhcp():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-disable-dhcp"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET dhcp=0 WHERE Interface='$eth'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Disable the DHCP for the Network Interface $eth");
}
function nic_enable_dhcp():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-enable-dhcp"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET dhcp=1 WHERE Interface='$eth'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Enable the DHCP for the Network Interface $eth");
}
function nic_use_span_enable():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-use-span-enable"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET UseSPAN=1 WHERE Interface='$eth'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Enable the SPAN Mode for the Network Interface $eth");
}
function nic_use_span_disable():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-use-span-disable"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE nics SET UseSPAN=0 WHERE Interface='$eth'");
    if(!$q->ok){
        return $tpl->js_error($q->mysql_error);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    return admin_tracks("Disable the SPAN mode for the Network Interface $eth");
}


function nic_checksum_offloading():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $eth=$_GET["nic-checksum-offloading"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");



    $ligne=$q->mysqli_fetch_array("SELECT Interface,checksum_offloading WHERE Interface='$eth");
    if(!isset($ligne["Interface"])){
        $ligne["Interface"]="";
    }
    $enable=0;
    if(intval($ligne["checksum_offloading"])==0){
        $enable=1;
    }


    $sql="UPDATE nics SET checksum_offloading=$enable WHERE Interface='$eth'";
    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (NICNAME,netzone,enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw,NoInternetCheck,checksum_offloading) VALUES ('$eth','$eth',1,'0.0.0.0','0.0.0.0','0.0.0.0',0,1500,'$eth',1,'','','','','','0.0.0.0','','',1,0,'','',0,1)";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->post_error($q->mysql_error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Set the TCP/IP Checksum Offloading to $enable for the Network Interface $eth");
}
function nic_internet_check_save():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $md=$_GET["md"];
    $eth=$_GET["nic-internet-check"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT NoInternetCheck,Interface WHERE Interface='$eth");
    if(!isset($ligne["Interface"])){
        $ligne["Interface"]="";
    }
    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (NICNAME,netzone,enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw,NoInternetCheck) VALUES ('$eth','$eth',1,'0.0.0.0','0.0.0.0','0.0.0.0',0,1500,'$eth',1,'','','','','','0.0.0.0','','',1,0,'','',1)";
    }else{
        $NoInternetCheck=intval($ligne["NoInternetCheck"]);
        if($NoInternetCheck==1){
            $NoInternetCheck=0;
        }else{
            $NoInternetCheck=1;
        }
        $sql="UPDATE nics SET NoInternetCheck='$NoInternetCheck' WHERE Interface='$eth'";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        return   $tpl->js_error($q->mysql_error);

    }
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update interface set not check internet to $NoInternetCheck");
}
function nic_name_interface_save():bool{
    $eth=$_POST["NicName"];
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $NICNAME=$_POST["NICNAME"];
    $netzone=$_POST["netzone"];
    $ligne=$q->mysqli_fetch_array("SELECT Interface FROM nics WHERE Interface='$eth'");
    if(!isset($ligne["Interface"])){
        $ligne["Interface"]="";
    }
    $sql="UPDATE nics SET NICNAME='$NICNAME',netzone='$netzone'  WHERE Interface='$eth'";

    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (NICNAME,netzone,enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw) VALUES ('$NICNAME','$netzone',1,'0.0.0.0','0.0.0.0','0.0.0.0',0,1500,'$eth',1,'','','','','','0.0.0.0','','',1,0,'','')";

    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->post_error("--------- $eth ---------\n".$q->mysql_error."\n$sql");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update interface name $eth to $NICNAME/$netzone");
}
function nic_name_interface_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $md=$_GET["md"];
    $eth=$_GET["nic-name-interface-popup"];
    $nic=new system_nic($eth,true);
    $form[]=$tpl->field_hidden("NicName",$eth);
    $form[]=$tpl->field_text("NICNAME", "{name}", $nic->NICNAME);
    $form[]=$tpl->field_text("netzone", "{netzone}", $nic->netzone);
    $js[]="dialogInstance5.close();";
    $js[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $js[]=refreshjs($eth);

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",@implode(";",$js),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_addr_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $md=$_GET["md"];
    $eth=$_GET["nic-address-popup"];
    $nic=new system_nic($eth,true);
    $form[]=$tpl->field_hidden("NicAddr",$eth);
    $form[]=$tpl->field_ipaddr("IPADDR", "{tcp_address}", $nic->IPADDR);
    $form[]=$tpl->field_ipaddr("NETMASK", "{netmask}", $nic->NETMASK);
    $form[]=$tpl->field_ipaddr("BROADCAST", "{broadcast}", $nic->BROADCAST);
    $form[]=$tpl->field_numeric("mtu","MTU",$nic->mtu);
    $form[]=$tpl->field_numeric("txqueuelen","{txqueuelen}",$nic->txqueuelen);
    $js[]="dialogInstance5.close();";
    $js[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $js[]=refreshjs($eth);

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",@implode(";",$js),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_gateway_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $md=$_GET["md"];
    $eth=$_GET["nic-gateway-popup"];
    $nic=new system_nic($eth,true);
    $form[]=$tpl->field_hidden("NicGateway",$eth);
    $form[]=$tpl->field_ipaddr("GATEWAY", "{gateway}", $nic->GATEWAY);
    $form[]=$tpl->field_checkbox("defaultroute","{default_gateway}",$nic->defaultroute,false);
    $form[]=$tpl->field_numeric("metric","{metric}",$nic->metric);
    $js[]="dialogInstance5.close();";
    $js[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $js[]=refreshjs($eth);

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",@implode(";",$js),$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_addr_save():bool{
    $eth=$_POST["NicAddr"];
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $IPADDR=$_POST["IPADDR"];
    $NETMASK=$_POST["NETMASK"];
    $BROADCAST=$_POST["BROADCAST"];
    $MTU=intval($_POST["MTU"]);
    $txqueuelen=intval($_POST["txqueuelen"]);

    $ligne=$q->mysqli_fetch_array("SELECT Interface FROM nics WHERE Interface='$eth'");
    if(!isset($ligne["Interface"])){$ligne["Interface"]="";}
    $sql="UPDATE nics SET `IPADDR`='$IPADDR',`NETMASK`='$NETMASK',`BROADCAST`='$BROADCAST',
                txqueuelen=$txqueuelen, mtu=$MTU WHERE Interface='$eth'";
    if(strlen($ligne["Interface"])<3){
        $sql="INSERT INTO nics (enabled,IPADDR,NETMASK,BROADCAST,txqueuelen,mtu,Interface,AUTO,BRIDGE_PORTS,BRIDGE_STP,DNS_SEARCH,VLAN_ROW_DEVICE,BOOTPROTO,GATEWAY,DNS1,DNS2,metric,defaultroute,NETWORK,ipv6gw) VALUES (1,'$IPADDR','$NETMASK','$BROADCAST',$txqueuelen,$MTU,'$eth',1,'','','','','','0.0.0.0','','',1,0,'','')";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update $eth network interface address to $IPADDR/$NETMASK - $BROADCAST");
}
function nic_gateway_save():bool{
    $eth=$_POST["NicGateway"];
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $GATEWAY=$_POST["GATEWAY"];
    $defaultroute=intval($_POST["defaultroute"]);
    $metric=intval($_POST["metric"]);
    if($defaultroute==1){
        $q->QUERY_SQL("UPDATE nics SET `defaultroute`='0' WHERE defaultroute='1'");
    }
    $q->QUERY_SQL("UPDATE nics SET `defaultroute`='$defaultroute',`metric`='$metric',`GATEWAY`='$GATEWAY'  WHERE Interface='$eth'");
    if(!$q->ok){
        echo  $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
    return admin_tracks("Update $eth network interface gateway address to $GATEWAY default route to $defaultroute and metric $metric");
}
function nic_config2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $security="AsSystemAdministrator";
    $md=$_GET["md"];
    $eth=$_GET["nic-config2"];
    $nic=new system_nic($eth,true);
    if($nic->NoInternetCheck==0){$InternetCheck=1;}else{$InternetCheck=0;}

    if($nic->NotSaved){
        if(preg_match("#^veth#",$eth)){
            echo $tpl->_ENGINE_parse_body($tpl->div_error("{this_interface_not_saved}"));
            return true;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{this_interface_not_saved}"));
    }
    if($nic->macvlan==1){
        $tpl->table_form_button("{delete}","Loadjs('$page?delete-macvlan=$eth')",$security,ico_trash);
        $macvlan_info=$tpl->_ENGINE_parse_body("{macvlan_info}");
        $macvlan_info=str_replace("%s","<strong>$nic->physical</strong>",$macvlan_info);
        $tpl->table_form_section("$eth: Macvlan",$macvlan_info);
    }
    if($nic->ipvlan==1){
        $macvlan_info=str_replace("Macvlan","IPvlan",$tpl->_ENGINE_parse_body("{macvlan_info}"));
        $macvlan_info=str_replace("%s","<strong>$nic->physical</strong>",$macvlan_info);
        $tpl->table_form_section("$eth: IPvlan",$macvlan_info);
        $tpl->table_form_button("{delete}","Loadjs('$page?delete-macvlan=$eth')",$security,ico_trash);
    }

    if($nic->macvlan==1) {
        $tpl->table_form_field_js("Loadjs('$page?ModifyMacVlanName-js=yes&value=$eth&md=$md');",$security);
        $tpl->table_form_field_text("Macvlan",$eth,ico_nic);
    }
    if($nic->enabled==0){
        $tpl->table_form_field_js("Loadjs('$page?nic-enable-interface=$eth&md=$md');",$security);
        $tpl->table_form_field_bool("{enabled}",0,ico_check);
        echo $tpl->table_form_compile();
        return true;
    }
    $tpl->table_form_field_js("Loadjs('$page?nic-disable-interface=$eth&md=$md');",$security);
    $tpl->table_form_field_bool("{enabled}",1,ico_check);

    $tpl->table_form_field_js("Loadjs('$page?nic-name-interface=$eth&md=$md');",$security);
    $tpl->table_form_field_text("{name}","$nic->NICNAME - $nic->netzone",ico_nic);


    if($nic->UseSPAN==1){
        $tpl->table_form_field_js("Loadjs('$page?nic-use-span-disable=$eth&md=$md');",$security);
        $tpl->table_form_field_bool("{free_mode} (SPAN)",1,ico_check);
        echo $tpl->table_form_compile();
        return true;
    }
    $tpl->table_form_field_js("Loadjs('$page?nic-use-span-enable=$eth&md=$md');",$security);
    $tpl->table_form_field_bool("{free_mode} (SPAN)",0,ico_check);


    if($nic->dhcp==1) {
        $tpl->table_form_field_js("Loadjs('$page?nic-disable-dhcp=$eth&md=$md');",$security);
        $tpl->table_form_field_bool("{use_dhcp}",1,ico_check);
        $tpl->table_form_field_js("Loadjs('$page?nic-checksum-offloading=$eth&md=$md');",$security);
        $tpl->table_form_field_bool("TCP/IP Checksum Offloading",$nic->checksum_offloading,ico_check);
        $tpl->table_form_field_js("Loadjs('$page?nic-internet-check=$eth&md=$md');",$security);
        $tpl->table_form_field_bool("{internet_access}",$InternetCheck,ico_check);
        echo $tpl->table_form_compile();
        return true;
    }
    $tpl->table_form_field_js("Loadjs('$page?nic-enable-dhcp=$eth&md=$md');",$security);
    $tpl->table_form_field_bool("{use_dhcp}",0,ico_check);
    $tpl->table_form_field_js("Loadjs('$page?nic-checksum-offloading=$eth&md=$md');",$security);
    $tpl->table_form_field_bool("TCP/IP Checksum Offloading",$nic->checksum_offloading,ico_check);
    $tpl->table_form_field_js("Loadjs('$page?nic-internet-check=$eth&md=$md');",$security);
    $tpl->table_form_field_bool("{internet_access}",$InternetCheck,ico_check);

    $mtu="";
    $txqueuelen="";
    $nic->IPADDR=trim($nic->IPADDR);
    if($nic->mtu>900){
        $mtu="&nbsp;&nbsp;&nbsp;<span class='label label-success' style='font-size:14px'>MTU $nic->mtu</span>";
    }
    if($txqueuelen>990){
        $txqueuelen="&nbsp;&nbsp;&nbsp;<span class='label label-success' style='font-size:14px'>TQL $txqueuelen</span>";
    }
    if($nic->IPADDR=="0.0.0.0"){
        $nic->IPADDR="";
    }
    $BROADCAST="";
    if(strlen($nic->BROADCAST)>3){
        $BROADCAST="<br><small style='font-size: 12px'>{broadcast} $nic->BROADCAST</small>";
    }


    $tpl->table_form_field_js("Loadjs('$page?nic-address=$eth&md=$md');",$security);
    if(strlen($nic->IPADDR)==0){
        $tpl->table_form_field_bool("{address}",0,ico_nic);
    }else{
        $ClassIP=new IP();
        $nic->NETMASK=$ClassIP->mask2cdr($nic->NETMASK);
        $tpl->table_form_field_text("{address}","$nic->IPADDR/$nic->NETMASK$mtu$txqueuelen$BROADCAST",ico_nic);
    }



    if($nic->GATEWAY=="0.0.0.0"){
        $nic->GATEWAY="";
    }
    $IP=new IP();
    if(!$IP->isValid($nic->GATEWAY)){
        $nic->GATEWAY="";
    }
    $tpl->table_form_field_js("Loadjs('$page?nic-gateway=$eth&md=$md');",$security);
    if($nic->GATEWAY==""){
        $tpl->table_form_field_bool("{gateway}",0,ico_sensor);
    }else{
        $def="";
        $etric="";

        if($nic->defaultroute==1){
            $def="&nbsp;&nbsp;&nbsp;<span class='label label-primary' style='font-size:14px'>{default_gateway}</span>";
        }
        if($nic->metric>0){
            $etric="&nbsp;&nbsp;&nbsp;<span class='label label-info' style='font-size:14px'>{metric} $nic->metric</span>";
        }
        $tpl->table_form_field_text("{gateway}","$nic->GATEWAY$def$etric",ico_sensor);
    }

    $tpl->table_form_button("{apply}","Loadjs('$page?nic-apply=$eth&md=$md');",$security);
    echo $tpl->table_form_compile();
    return true;
}
function nic_apply_interface():bool{
    $eth=$_GET["nic-apply"];
    $md=$_GET["md"];
    $iface=urlencode($eth);
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/build/$iface");
    header("content-type: application/x-javascript");
    $html[]="if ( document.getElementById('div-works-$eth') ){";
    $html[]="LoadAjaxSilent('div-works-$eth','$page?nic-config2=$eth&md=$md');";
    $html[]="}";
    $html[]="if ( document.getElementById('netz-interfaces-status') ){";
    $html[]="LoadAjaxSilent('netz-interfaces-status','fw.network.interfaces.php?status2=yes');";
    $html[]="}";
    echo @implode("\n",$html);
    return admin_tracks("Apply network configuration of $eth");
}

function nic_config2_old(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$EnableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMsftncsi"));
	$InternetCheck_disabled=false;
    $md=$_GET["md"];
	$eth=$_GET["nic-config2"];
	
	$nic=new system_nic($eth,true);
	if($nic->NoInternetCheck==0){$InternetCheck=1;}else{$InternetCheck=0;}
	if($EnableMsftncsi==0){$InternetCheck_disabled=true;}

    if($nic->macvlan==1) {
        $form[]=$tpl->field_text_button("ModifyMacVlanName","{name}",$eth);
    }
    if($nic->macvlan==1){
        $tpl->form_add_button("{delete}", "Loadjs('$page?delete-macvlan=$eth&md=$md')");
        $macvlan_info=$tpl->_ENGINE_parse_body("{macvlan_info}");
        $macvlan_info=str_replace("%s","<strong>$nic->physical</strong>",$macvlan_info);
        $html[]="<div style='margin-top:5px'>";
        $html[]=$tpl->div_explain("$eth: Macvlan||$macvlan_info");
        $html[]="</div>";
    }
    if($nic->ipvlan==1){
        $tpl->form_add_button("{delete}", "Loadjs('$page?delete-macvlan=$eth')");
        $macvlan_info=str_replace("Macvlan","IPvlan",$tpl->_ENGINE_parse_body("{macvlan_info}"));
        $macvlan_info=str_replace("%s","<strong>$nic->physical</strong>",$macvlan_info);
        $html[]="<div style='margin-top:5px'>";
        $html[]=$tpl->div_explain("$eth: IPvlan||$macvlan_info");
        $html[]="</div>";
    }
    $security="AsSystemAdministrator";
	$html[]=$tpl->form_outside("",  $form,null,"{apply}",refreshjs($eth),$security);
	echo $tpl->_ENGINE_parse_body($html);
}

function refreshjs($eth=null):string{
    $page=CurrentPageName();
    $md="";
    if(isset($_GET['md'])){
        $md=$_GET['md'];
    }

    $MAINZ["netz-interfaces-status"]="$page?status2=yes";
    $MAINZ["network-interfaces-table"]="$page?table=yes";
    if($eth<>null){$MAINZ["div-works-$eth"]="$page?nic-config2=$eth&md=$md";}
    foreach ($MAINZ as $div=>$js) {
        $jsa[] = "if( document.getElementById('$div') ){";
        $jsa[] = "LoadAjax('$div','$js');";
        $jsa[] = "}";
        }

    return @implode("",$jsa);

}

function nic_features(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$nic=$_GET["nic-features"];
	$niclass=new system_nic($nic);
	$title="$niclass->netzone: $niclass->NICNAME ($nic)";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?ethtool-k=yes&nic=$nic");
	
	$f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/ethtool_$nic.txt"));
	$switch["on"]=1;
	$switch["off"]=0;
	
	$switch1[0]="<span class='label'>{inactive}</span>";
	$switch1[1]="<span class='label label-primary'>&nbsp;&nbsp;{active2}&nbsp;&nbsp;</span>";

	foreach ($f as $ligne){
		$ligne=trim($ligne);
		if(preg_match("#Cannot get#", $ligne)){continue;}
		$fixed=false;
		if(!preg_match("#(.+?):\s+(.+)#", $ligne,$re)){continue;}
		$key=trim($re[1]);
		$value=$re[2];
		if(preg_match("#(on|off)#", strtolower($value),$re)){$val=$re[1];}
		if(preg_match("#fixed#", $value,$re)){$fixed=true;}
		$ARRAY[$key]["VAL"]=$switch[$val];
		$ARRAY[$key]["FIX"]=$fixed;
	}

	$html[]="<H2>$title</H2>";
    $html[]="<table style='width:55%;margin-left:10%;margin-right:10%;'>";

	
	foreach ($ARRAY as $num=>$ligne){
		$key_extra=null;
		$key=$num;
		$key=str_replace("-", " ", $key);
	
		if($ligne["FIX"]){
		    $html[]="<tr>";
            $html[]="<td style='padding-top:5px;text-align:right'><strong>$key:</strong></td>";
            $html[]="<td style='padding-top:5px;padding-left:10px;width:1%'>".$switch1[$ligne["VAL"]]."</span>";
            $html[]="</tr>";
			continue;
		}

        $html[]="<tr>";
        $html[]="<td style='padding-top:5px;text-align:right'><strong>$key:</strong></td>";
        $html[]="<td style='padding-top:5px;padding-left:10px;width:1%'>".$switch1[$ligne["VAL"]]."</span>";
        $html[]="</tr>";

	
	}
    $html[]="</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function nic_security_save(){
	
	$niClass=new system_nic($_POST["nic_security"]);
	unset($_POST['nic_security']);
	reset($_POST);
	foreach ($_POST as $key=>$val){
		$val=url_decode_special_tool($val);
		$niClass->$key=$val;
	}
	$niClass->SaveNic();
}

function nic_save(){
	$DNS_1=null;$DNS_2=null;
	$tpl=new templates();
	$ip=new networking();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}
	
	foreach ($_POST as $key=>$val){$_POST[$key]=url_decode_special_tool($val);}reset($_POST);
		
	
	
	if(isset($_POST["NICNAME"])){
		$NICNAME=trim(url_decode_special_tool($_POST["NICNAME"]));
	}
    if($_POST["netzone"]==null){
		echo "Network Zone must be defined\n";
		return;
	}

	

	$UseSPAN=intval($_POST["UseSPAN"]);
	$nic=trim($_POST["save_nic"]);
	$IPADDR=trim($_POST["IPADDR"]);
	$NETMASK=trim($_POST["NETMASK"]);
	$GATEWAY=trim($_POST["GATEWAY"]);
	$BROADCAST=trim($_POST["BROADCAST"]);
	if($GATEWAY=="no"){$GATEWAY="0.0.0.0";}
	if($GATEWAY==null){$GATEWAY="0.0.0.0";}
	if(isset($_POST["DNS_1"])){$DNS_1=$_POST["DNS_1"];}
	if(isset($_POST["DNS_2"])){$DNS_2=$_POST["DNS_2"];}

	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

	if($q->TABLE_EXISTS("nic_virtuals")) {
        $sql = "SELECT ipaddr FROM nic_virtuals WHERE ipaddr='$IPADDR'";
        $results = $q->QUERY_SQL($sql);
        $ligne = $results[0];
        if ($ligne["ipaddr"] <> null) {
            echo $tpl->javascript_parse_text("{already_used}: $IPADDR (Virtual)");
            return;
        }
    }
    if($q->TABLE_EXISTS("nics_vlan")) {
        $sql = "SELECT ipaddr FROM nics_vlan WHERE ipaddr='$IPADDR'";
        $results = $q->QUERY_SQL($sql);
        $ligne = $results[0];
        if ($ligne["ipaddr"] <> null) {
            echo $tpl->javascript_parse_text("{already_used}: $IPADDR (VLAN)");
            return;
        }
    }

	if(intval($_POST["enabled"])==1){
		if($UseSPAN<>1){
			if($_POST["dhcp"]<>1){
				if(!$ip->checkIP($IPADDR)){echo "CheckIP: Address: $IPADDR = False;\n";return;}
				if(!$ip->checkIP($NETMASK)){echo "CheckIP: NetMask $NETMASK = False;\n";return;}
				if($GATEWAY<>"0.0.0.0"){
					if(!$ip->checkIP($GATEWAY)){echo "CheckIP: Gateway $GATEWAY = False;\n";return;}
				}
			}
			if($DNS_1<>null){
				if(!$ip->checkIP($DNS_1)){echo "CheckIP: DNS 1 $DNS_1 = False;\nOr set null value to remove this message";return;}
			}
				
			if($DNS_2<>null){
				if(!$ip->checkIP($DNS_2)){echo "CheckIP: DNS 2 $DNS_2 = False;\nOr set null value to remove this message";return;}
			}
				
		}
	}

	$htmltools=new htmltools_inc();
	$_POST["netzone"]=$htmltools->StripSpecialsChars($_POST["netzone"]);

	if($_POST["InternetCheck"]==1){$InternetCheck=0;}else{$InternetCheck=1;}


	$nics=new system_nic($nic,true);
	$text[]="$NICNAME $nic $IPADDR";
	if($NICNAME<>null){ $nics->NICNAME=$NICNAME; }
	$nics->eth=$nic;
	$nics->IPADDR=$IPADDR;
	$nics->NETMASK=$NETMASK;
	$nics->GATEWAY=$GATEWAY;
	$nics->BROADCAST=$BROADCAST;
	$nics->UseSPAN=$UseSPAN;
	if($DNS_1<>null){$nics->DNS1=$DNS_1; }
	if($DNS_2<>null){ $nics->DNS2=$DNS_2; }
	$nics->dhcp=$_POST["dhcp"];
	$nics->metric=$_POST["metric"];
	$nics->enabled=intval($_POST["enabled"]);
    $nics->txqueuelen=intval($_POST["txqueuelen"]);
	$nics->netzone=$_POST["netzone"];
	$nics->NoInternetCheck=$InternetCheck;
	if(isset($_POST["watchdog"])) {
        $nics->watchdog = intval($_POST["watchdog"]);
    }
	$nics->checksum_offloading=intval($_POST["checksum_offloading"]);
	$nics->mtu=$_POST["mtu"];

	if(isset($_POST["defaultroute"])){
		$nics->defaultroute=$_POST["defaultroute"];
	}

	if(isset($_POST["Bridged"])){
		$nics->Bridged=$_POST["Bridged"];
		$text[]="Bridged, ";
	}
	if(isset($_POST["BridgedTo"])){
		$nics->BridgedTo=$_POST["BridgedTo"];
		$text[]="{$_POST["BridgedTo"]}";
	}

	if(isset($_POST["defaultroute"])){
		$nics->defaultroute=$_POST["defaultroute"];
	}


	if($_POST["noreboot"]=="noreboot"){
		$nics->NoReboot=true;
		if($nics->SaveNic()){
			return;
		}
	}

	$nics->SaveNic();
	
	$sql="UPDATE `nics` SET `enabled`='{$_POST["enabled"]}' WHERE `Interface`='$nic'";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?monit-interfaces=yes");

}
function section_nic_only(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
    $btn=array();
    $TINY_ARRAY["TITLE"]="{network_interfaces}: {details}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-ethernet";
    $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html=$tpl->page_header("{network_interfaces}",
        "fa-duotone fa-ethernet","{network_interfaces_explain}",
    "$page?table=yes","nics","progress-firehol-restart",false,"table-loader-interfaces"
    );


        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;




}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    //

    $html=$tpl->page_header("{network_interfaces}","fa fa-exchange","{network_interfaces_explain}","$page?tabs=yes","interfaces","progress-firehol-restart",false,"table-loader-interfaces");




if(isset($_GET["main-page"])){
	$tpl=new template_admin(null,$html);
	echo $tpl->build_firewall();

}


	echo $tpl->_ENGINE_parse_body($html);

}
function _nic_enabled_not_set($nicz,$ACTUAL_IPADDR):bool{

    if($nicz->dhcp==1){return false;}
    if(is_null($nicz->IPADDR)){$nicz->IPADDR="";}
    if($nicz->UseSPAN==1){
        VERBOSE("_nic_enabled_not_set [$nicz->IPADDR] --> USE SPAN = TRUE",__LINE__);
        return false;
    }

    if(trim($nicz->IPADDR)==null){
        VERBOSE("_nic_enabled_not_set [$nicz->IPADDR] --> IS NULL",__LINE__);
        return true;
    }
    if($ACTUAL_IPADDR<>null) {
        if ($nicz->IPADDR == $ACTUAL_IPADDR) {
            VERBOSE("_nic_enabled_not_set [$nicz->IPADDR] ==== $ACTUAL_IPADDR",__LINE__);
            return false;
        }
    }
    $IP=new IP();
    if($IP->isValid($nicz->IPADDR)){return false;}

    return true;

}
function _nic_enabled_not_configured($nicz,$ACTUAL_IPADDR):bool{
    VERBOSE("_nic_enabled_not_configured...",__LINE__);
    if ($nicz->enabled == 0) {
        VERBOSE("This NIC is disabled",__LINE__);
        return false;
    }

    if ($nicz->dhcp == 1) {
        return false;
    }
    if ($nicz->UseSPAN == 1) {
        return false;
    }
    VERBOSE("Configured IP = {$nicz->IPADDR} receive IP: $ACTUAL_IPADDR",__LINE__);
    if ($nicz->IPADDR == $ACTUAL_IPADDR) {
        return false;
    }

    return true;

}
function table_start(){
    $page           = CurrentPageName();
    echo "<div id='network-interfaces-table' style='margin-top: 15px'></div><script>LoadAjax('network-interfaces-table','$page?table=yes');</script>";

}

function table(){
	$tpl                = new template_admin();
	$page               = CurrentPageName();
    $ALREADY_MASQ       = array();
    $q                  = new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results            = $q->QUERY_SQL("SELECT nic FROM firehol_masquerade WHERE enabled=1");
    foreach ($results as $index=>$ligne){$ALREADY_MASQ[$ligne["nic"]]=true;}
    $q                  = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $PERISTENT=array();
    $res=$q->QUERY_SQL("SELECT * FROM persistent");
    if(!$q->ok){
        VERBOSE("<H1>$q->mysql_error</H1>");
    }
    foreach ($res as $index=>$ligne){
        $PERISTENT[$ligne["MacAddr"]]=$ligne["Iface"];
    }

    if(!isset($_GET["search"])){$_GET["search"]="";}
	$qico               = "<i class=\"fad fa-question\"></i>&nbsp;";
	$muted              = "style='color:#b9b9b9 !important'";
	$DisableNetworking  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    $EnableVLANs        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs"));
    $OpenVswitchEnable  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVswitchEnable"));
	$users              = new usersMenus();
	$ComputerMacAddress = $tpl->javascript_parse_text("{ComputerMacAddress}");
    $LshwCnetwork       = base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LshwCnetwork"));
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
    $LSHW               = json_decode($LshwCnetwork);
    if(!$LSHW){
        $LSHW=array();
    }
    $EnableMunin        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    $bondEnabled        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInterfaceBond"));

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?masquerade-interfaces=yes");

    $MASQUERADES=unserialize(@file_get_contents(PROGRESS_DIR."/MASQUERADE.eths"));


	foreach ($LSHW as $index=>$lshw_class) {
	    if(!property_exists($lshw_class,"logicalname")){continue;}
        $logicalname=$lshw_class->logicalname;
	    if(property_exists($lshw_class,"product")){
	        $HARDWARES[$logicalname]["product"]=$lshw_class->product;
        }
        if(property_exists($lshw_class,"vendor")){
            $HARDWARES[$logicalname]["vendor"]=$lshw_class->vendor;
        }

        if(property_exists($lshw_class,"capabilities")){
            if(property_exists($lshw_class->capabilities,"100bt-fd")){
                $HARDWARES[$logicalname]["MAXCP"]="100Mbit/s";
            }
            if(property_exists($lshw_class->capabilities,"1000bt-fd")){
                $HARDWARES[$logicalname]["MAXCP"]="1Gbit/s";
            }
            if(property_exists($lshw_class->capabilities,"10000bt-fd")){
                $HARDWARES[$logicalname]["MAXCP"]="10Gbit/s";
            }
        }

        if(property_exists($lshw_class,"configuration")){
            if(property_exists($lshw_class->configuration,"driver")){
                $HARDWARES[$logicalname]["driver"]=$lshw_class->configuration->driver;
            }

        }
    }

	if(!$MUNIN_CLIENT_INSTALLED){$EnableMunin=0;}

    $jsrestart=$tpl->framework_buildjs("/system/network/reconfigure-restart",
        "reconfigure-newtork.progress",
        "exec.virtuals-ip.php.html","progress-firehol-restart","LoadAjax('network-interfaces-table','$page?table=yes');"
    ); // apply_network_configuration

	$apply=$tpl->_ENGINE_parse_body("{apply_network_configuration}");

	if(!isset($_GET["eth"])){$_GET["eth"]=null;}


    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	
	if($users->AsSystemAdministrator) {
        if ($DisableNetworking == 0) {
            $btn[] = "<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart;\"><i class='fa fa-repeat'></i> $apply </label>";
        }
    }
	
	$sql="SELECT COUNT(Interface) as tcount FROM nics WHERE enabled=0";
	$ligne=$q->mysqli_fetch_array($sql);
	
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
	$CountOfDisabled=$ligne["tcount"];

	if($CountOfDisabled>0){
		$btn[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?disabled-interfaces-js=yes');\"><i class='fa fa-circle-o'></i> $CountOfDisabled {disabled_interfaces} </label>";
	}

    $btn[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?sysctl2-js=yes');\"><i class='fa fa-fighter-jet'></i> {kernelnetworkoptimization} </label>";


    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?options-js=yes');\"><i class='fas fa-cogs'></i> {options} </label>";
	

	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MULTIPATH_TCP_ENABLED"))==1){
        $btn[]="<label class=\"btn btn btn-default\" OnClick=\"Loadjs('$page?MPTCP-js=yes');\"><i class='far fa-exchange'></i> {APP_MULTIPATH_TCP} </label>";
		
	}

	if($EnableMunin==1){
        $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.network.interfaces.netstats.php');\">
            <i class='fas fa-chart-bar'></i> {statistics} </label>";
	}
    $btn[]="<label class=\"btn btn btn-blue\" OnClick=\"s_PopUp('https://wiki.articatech.com/en/network','1024','800');\">
            <i class='fa-solid fa-headset'></i> {online_help} </label>";
    if (!$bondEnabled){
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?add-macvlan-js=yes');\"><i class='fa fa-project-diagram'></i> {new_virtual_interface} </label>";
}
    if ($bondEnabled){
        $btn[]="<label><div class=\"dropdown\">
  <button class=\"btn btn btn-primary dropdown-toggle\" style='border-bottom-left-radius: 0;border-top-left-radius: 0' type=\"button\" id=\"dropdownMenuButton\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
  {new_interface} <span class=\"caret\"></span>
  </button>
  <ul class=\"dropdown-menu\">
	<li><a class=\"dropdown-item\" href=\"#\" OnClick=\"Loadjs('$page?add-macvlan-js=yes');\">{new_virtual_interface}</a></li>
	<li><a class=\"dropdown-item\" href=\"#\" OnClick=\"Loadjs('$page?add-bond-js=yes');\">{add_bond_interface}</a></li>
  </ul>
</div></label>";}

    $btn[]="</div>";

	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";

	$_SESSION["DHCPL_SEARCH"]=trim(strtolower($_GET["search"]));

	$thClasses="data-sortable=true class='text-capitalize' data-type='text' style='text-align:right;padding-right:2px';";
	$thNormal="data-sortable=true class='text-capitalize center' data-type='text' nowrap";


    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]=$tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{interface}</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th $thClasses'>{tcp_address}</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th $thClasses nowrap>{netmask}</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th $thClasses nowrap>$ComputerMacAddress</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th $thNormal >{gateway}</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th $thNormal>{internet_access}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th $thNormal>Watchdog</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th $thNormal>{APP_PROXY_ARP}</th>");
    $html[]=$tpl->_ENGINE_parse_body("<th $thNormal>Masquerade</th>");
	$html[]=$tpl->_ENGINE_parse_body("<th $thNormal>{firewall}</th>");

	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader-interfaces','$page?table=yes&eth={$_GET["eth"]}');";
    $EnableIwConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);

	$proc_net_dev=explode("\n",@file_get_contents("/proc/net/dev"));

	foreach ($proc_net_dev as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?):\s+[0-9]+#",$line,$re)){continue;}
        if($re[1]=="lo"){continue;}
        VERBOSE("/proc/net/dev: Interface {$re[1]}",__LINE__);
        $datas[]=trim($re[1]);
    }

    $IwConfigInterfaceS=array();

	if($EnableIwConfig==1){
	    VERBOSE("/usr/sbin/iw dev",__LINE__);
	    exec("/usr/sbin/iw dev 2>&1",$IwConfigResults);
	    foreach ($IwConfigResults as $IwConfigLine){
            $IwConfigLine=trim($IwConfigLine);
            if($IwConfigLine==null){continue;}
            if(!preg_match("#^Interface\s+(.+)#",$IwConfigLine,$re)){
                VERBOSE($IwConfigLine." NO MATCH",__LINE__);
                continue;
            }
            $IwConfigInterfaceS[$re[1]]=True;
        }
    }


	$tcp=new networking();
	$IPBANS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaIpListBanned")));
    if(!$IPBANS){
        $IPBANS=array();
    }
	$EnableMsftncsi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMsftncsi"));
	
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="SELECT Interface FROM nics ORDER BY metric";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<div class='alert alert-danger'>$q->mysql_error</div>";}
	foreach ($results as $index=>$ligne){
        $datas[]=$ligne["Interface"];
        $MYSQL_NIC[$ligne["Interface"]]=$ligne["Interface"];}
	
	$FIREHOLE=false;
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if($FireHolEnable==1){$FIREHOLE=true;}
	$TRCLASS=null;

    if($bondEnabled==1){
        $bondMembers=array();
        $bondNic=array();
        $sqlBond="SELECT * FROM `bond_interfaces`";
        $resultsBond=$q->QUERY_SQL($sqlBond);
        if($q->ok) {
            foreach ($resultsBond as $index => $ligne) {
                $explodeMember = explode(",",$ligne["members"]);
                $bondNic["{$ligne["nic"]}"]="{$ligne["nic"]}";
                $bondMembers["bond"]="{$ligne["nic"]}";
                $bondMembers["md5"]=md5(serialize($ligne));
                foreach ($explodeMember as $members) {
                    $bondMembers["$members"]="$members";

                }

            }
        }
    }
    foreach ($datas as $val){$H[$val]=$val;}



    ksort($H);
    $datas=array();
    foreach ($H as $val=>$none){$datas[]=$val;}

	foreach ($datas as $val){
        $NETADDR_TEXT="";
        $metric_text="";
        $NIC_DISABLED=false;
	    VERBOSE("LOOP: Interface $val",__LINE__);
		if(trim($val)==null){continue;}
		if(preg_match('#master#',$val)){continue;}
        if(preg_match("#^(ovs-|switch)#",$val)){continue;}
		if(preg_match("#^(erspan|dummy|teql|ip6tnl|tunl|gre|ifb|sit|gretap|vlan)[0-9]+#",$val)){continue;}
		if(preg_match("#^virt#", $val)){$FORCE_GATEWAY=true;}
		if(preg_match("#wccp[0-9]+#", $val)){$WCCP_INTERFACE=true;}
		if($EnableIwConfig==0){
		    if(preg_match("#(wlan|wlp)[0-9]+#", $val)){
                VERBOSE("LOOP: Interface $val EnableIwConfig == 0, Continue",__LINE__);
		        continue;}
		}
        $wire                   = null;
		$text_class             = null;
		$metric_warning         = null;
		$FIREHOLE_ROW           = $tpl->icon_nothing();
        $macvlan_icon           = null;
		$access_internet        = "-";
		$error                  = null;
		$why                    = null;
		$nicz                   = new system_nic($val);
		$NETADDR                = array();
		$MUST_CHANGE            = false;
		$NOT_CONFIGURED=false;
		$watchdog=$nicz->watchdog;
        $text_class_ico="text-navy";
		if($nicz->Bridged==1){continue;}
		if(isset($MYSQL_NIC[$val])){
			if($nicz->enabled==0){
                VERBOSE("LOOP: Interface $val ! ! Disabled !!! Abort",__LINE__);
                $NIC_DISABLED=True;
                $text_class="text-muted";
                $text_class_ico=$text_class;

			}
		}
        $md=md5($val);
        if($nicz->macvlan==1){
            $macvlan_icon="&nbsp;<span class='label label-info'>Macvlan ".$nicz->physical."</span>";
        }
        if($nicz->ipvlan==1){
            $macvlan_icon="&nbsp;<span class='label label-info'>IPvlan ".$nicz->physical."</span>";
        }

		if($FIREHOLE){
			$FIREHOLE_ROW=$tpl->icon_check($nicz->isFW,
                "Loadjs('$page?isFW=$val')",
                null,"AsFirewallManager");
		}

        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $ligne3=$q->mysqli_fetch_array("SELECT firewall_masquerade from nics WHERE Interface='$val'");
        if(!isset($ligne3["firewall_masquerade"])){ $ligne3["firewall_masquerade"]=0; }
        $firewall_masquerade=intval($ligne3["firewall_masquerade"]);

        $MASQUERADE=$tpl->icon_check($firewall_masquerade, "Loadjs('$page?masquerade=$val')", null,"AsFirewallManager");

        if($firewall_masquerade==1){
            if(!isset($MASQUERADES[$val])){
                $error="$error&nbsp;<span class='label label-warning'>Masquerade {not_configured}</span>";
            }
        }
        $PROXY_ARP=$tpl->icon_check($nicz->proxyarp,
            "Loadjs('$page?proxyarp=$val')",
            null,"AsFirewallManager");

		if(isset($ALREADY_MASQ[$val])){
            $MASQUERADE="<span class='fas fa-check'></span>";
        }

		unset($MYSQL_NIC[$val]);
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nicstatus/$val"));
        $nicinfos=$data->Info;
        $tbl=explode(";",$nicinfos);
		if($GLOBALS["VERBOSE"]){ foreach ($tbl as $indexDebug=>$LIneDebug){ VERBOSE("LOOP: Interface $val NicInfo[$indexDebug] = $LIneDebug",__LINE__); } }

        if(isset($IPBANS[$tbl[0]])) {
            if ($IPBANS[$tbl[0]]) {
                continue;
            }
        }
		$dhcp_text=null;
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$tcp->ifconfig(trim($val));

		if(isset($IwConfigInterfaceS[$val])){
            $wire=" (wireless)";
        }

		$gateway    = $nicz->GATEWAY;
		VERBOSE("GATEWAY: $val -- $gateway", __LINE__);
		if($nicz->defaultroute==1){$defaultroute_text="<i class='fas fa-check-square-o'></i>";}
		if($nicz->FireQOS==1){$qos_text="{enabled}";$qos_color="black";}
		if($nicz->enabled==0){$color="#8E8E8E";$qos_color="#8E8E8E";}
        if($nicz->IPADDR=="127.0.0.1"){$nicz->IPADDR=null;}
		if($nicz->IPADDR=="0.0.0.0"){$nicz->IPADDR=null;}
        if($nicz->NETMASK=="0.0.0.0"){$nicz->NETMASK=null;}
        if($nicz->GATEWAY=="no"){$nicz->GATEWAY=null;}
        $NetStatus = new NetStatus($val);

        $ACTUAL_IPADDR=$NetStatus->IPADDR;
        $ACTUAL_NETMASK=$NetStatus->NETMASK;
        $ACTUAL_GATEWAY=$NetStatus->GATEWAY;
        $ComputerMacAddress=$NetStatus->MacAddr;
        $ComputerMacAddressError="";
        if(preg_match("#^veth#",$val)){
            unset($PERISTENT[$ComputerMacAddress]);
        }

        if($nicz->macvlan==0 && $nicz->ipvlan==0 && !preg_match("#^bond[0-9]+#", $val)){
        if(isset($PERISTENT[$ComputerMacAddress])) {
            $PERISTENT_INTERFACE = trim($PERISTENT[$ComputerMacAddress]);
            if (strlen($PERISTENT_INTERFACE) > 1) {
                if ($PERISTENT_INTERFACE <> $val) {
                    $tt = new system_nic($PERISTENT_INTERFACE);
                    $ComputerMacAddressError = "<br><span class='label label-danger'>{expected}: $PERISTENT_INTERFACE $tt->NICNAME</span>";
                }
            }
        }
        }
        $ComputerMacAddress=$tpl->td_href($ComputerMacAddress,"","Loadjs('fw.network.persistent-net.php')");
		if($gateway=="no"){$gateway="0.0.0.0";}
		if( _nic_enabled_not_set($nicz,$ACTUAL_IPADDR)){
		    VERBOSE("_nic_enabled_not_set --> TRUE",__LINE__);
            $nicz->enabled=0;
            $text_class="text-muted";
            $text_class_ico=$text_class;
        }
        if(_nic_enabled_not_configured($nicz,$ACTUAL_IPADDR)) {
            $why = $why . " $nicz->IPADDR / {$ACTUAL_IPADDR}";
            $NOT_CONFIGURED = true;
            $text_class_ico="text-warning";
        }
        if($nicz->NotSaved){
            $NOT_CONFIGURED = true;
        }

        if($bondEnabled==1){
            if(preg_match("#^bond[0-9]+#", $val)){
                if (array_key_exists($val,  $bondNic)) {
                    $sockmd5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("{$bondMembers["bond"]}-md5-control");
                    if($bondMembers["md5"] !== $sockmd5){
                        $why = $why . " to make it in production";
                        $NOT_CONFIGURED = false;
                        $MUST_CHANGE=true;
                        $text_class_ico="text-warning";
                    }
                }
            }
        }
        VERBOSE("<H2>$val</H2> OpenVswitchEnable=$OpenVswitchEnable, virtualbridge=$nicz->virtualbridge",__LINE__);
        $VirtualBridge="";



        if($nicz->enabled == 1) {
            if ($nicz->dhcp == 0) {
                if ($ACTUAL_IPADDR == null) {
                    $text_class_ico="text-warning";
                    $error = "$error<br><strong class=small>{waiting_network_reload}</strong>";
                }
            }
            if ($nicz->dhcp == 1) {
                $dhcp_text = "(DHCP)";
                $ip = new IP();
                if ($ip->isValid($ACTUAL_IPADDR)) {
                    $MUST_CHANGE = false;
                }
            }

            if($ACTUAL_IPADDR<>null){$NETADDR[]="<strong>$ACTUAL_IPADDR</strong>";}


            $NETADDR=table_virtuals_list($val,$NETADDR);
            if($EnableVLANs==1) {
                $NETADDR=table_vlan_list($val,$NETADDR);

            }
            if($bondEnabled==1){
                $NETADDR=table_bond_list($val,$NETADDR);
            }


        }
		if($nicz->enabled==0){$MUST_CHANGE=false;}
		if($nicz->enabled==1){
			
			if($NOT_CONFIGURED){
			    $access_internet="<i class='fas fa-exclamation-triangle'></i>";
			    $error=$error."<br><small class='text-warning'>{this_interface_is_not_configured} $why</small>";}

			if($MUST_CHANGE){
				$access_internet="<i class='fas fa-exclamation-triangle'></i>";
				if(!$NOT_CONFIGURED){
				    $error=$error."<br><small class='text-warning'>{need_to_apply_network_settings_interface} $why</small>";
				}
			}
			if($nicz->NoInternetCheck==0){
				if($EnableMsftncsi==1){
				    $access_internet="<i class='fas fa-check'></i>";
				}
			}
		}else{
            $PROXY_ARP=$tpl->icon_nothing();
        }

        $hardware_textes=array();
		$hardware_text=null;
        if(isset($HARDWARES[$val])){
            if(isset($HARDWARES[$val]["MAXCP"])){
                $hardware_textes[]="<strong>{$HARDWARES[$val]["MAXCP"]}</strong>";
            }
            if(isset($HARDWARES[$val]["product"])){
                $hardware_textes[]="{$HARDWARES[$val]["product"]}";
            }
            if(isset($HARDWARES[$val]["vendor"])){
                $hardware_textes[]="{$HARDWARES[$val]["vendor"]}";
            }
            if(isset($HARDWARES[$val]["driver"])){
                $hardware_textes[]="({$HARDWARES[$val]["driver"]})";
            }

        }else{
            VERBOSE("HARDWARES[$val] not set",__LINE__);
        }

        VERBOSE("HARDWARES[$val] == ".count($hardware_textes),__LINE__);
        if( count($hardware_textes)>0 ){
            $hardware_text="<br><small>".@implode(" ",$hardware_textes)."</small>";
        }


		$url="Loadjs('$page?nic-config-js=$val&md=$md');";
		if($DisableNetworking==1){$url="blur()";}
		if($val=="tailscale0"){
		    $url="Loadjs('fw.network.interfaces.php?nic-config-deny=tailscale0');";
		}
		$href="<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold' class='$text_class'>";

        if(!is_array($NETADDR)){$NETADDR=array();}

        if($nicz->UseSPAN==0) {
            if (count($NETADDR) == 0) {
                $NETADDR_TEXT = $tpl->icon_nothing();
                if ($nicz->IPADDR <> null) {
                    $NETADDR_TEXT = "<span $muted>{$qico}$nicz->IPADDR</span>";
                    $ComputerMacAddress = "<span $muted>{$qico}$ComputerMacAddress</span>";
                } else {
                    $ComputerMacAddress = "<span $muted>$qico$ComputerMacAddress</span>";
                }

            } else {
                $NETADDR_TEXT = @implode("<br>", $NETADDR);
            }
        }

		if(trim($ACTUAL_NETMASK)==null){
		    $ACTUAL_NETMASK=$tpl->icon_nothing();
		    if($nicz->NETMASK<>null){
                $ACTUAL_NETMASK = "<span $muted>{$qico}$nicz->NETMASK</span>";
            }
		}

        $gateway_strong=null;
        $gateway_strong2=null;
        $gateway_text=null;
        $NoMetric=false;
        if($gateway=="0.0.0.0"){$NoMetric=true;$gateway=$ACTUAL_GATEWAY; $nicz->metric=null;}
        if($nicz->GATEWAY==""){$NoMetric=true;}
        if($nicz->enabled == 0) {$NoMetric=true;}
        if($nicz->UseSPAN==1) {$NoMetric=true;}

		if(!$NoMetric){
            if (!isset($METRICz[$nicz->metric])) {
                if($nicz->UseSPAN==0) {$METRICz[$nicz->metric] = true;}
            } else {
                $metric_warning = "&nbsp;<span class='label label-warning'>{metric_already_used}</span>";
            }
        }

		if($nicz->defaultroute==1){
		    $gateway_strong="<strong>";
		    $gateway_strong2="</strong>";
		    $gateway_text="&nbsp;<i>({default_gateway})</i>";
		}
		if($nicz->metric>0){
            $metric_text="<br><small>{metric} $nicz->metric</small>";
        }

        if(trim($ACTUAL_GATEWAY)==null){
            $gateway_strong=null;
            $gateway_strong2=null;
            $gateway=$tpl->icon_nothing();
            if($nicz->GATEWAY<>null){
                $gateway = "<span $muted>{$qico}$nicz->GATEWAY$gateway_text$metric_text</span>";
                $gateway_text=null;
                $metric_text=null;
            }else{
                $metric_text=null;
            }
        }

		$MULTIPATH_GATEWAYS=MULTIPATH_GATEWAYS($val);
		if(count($MULTIPATH_GATEWAYS)>0){
			VERBOSE("LOOP: Interface $val MULTIPATH_GATEWAYS($val) >0 == ".count($MULTIPATH_GATEWAYS),__LINE__);
			$gateway=@implode(", ", $MULTIPATH_GATEWAYS);
			VERBOSE("LOOP: Interface $val MULTIPATH_GATEWAYS($val) >0 == '$gateway'",__LINE__);
		}
        $watchdog_field=$tpl->icon_nothing();
        if($DisableNetworking==0){
            if($watchdog==1){$watchdog_field="<li class='fa fa-check'></li>";}
        }
        $icon="<i class=\"$text_class_ico fas fa-ethernet\"></i>";

        if($NIC_DISABLED){
            $text_class="text-muted";
            $text_class_ico=$text_class;
            $metric_warning=null;
            $error="&nbsp;<span class='label'>{disabled}</span>";
            $icon="<i class='fal fa-ethernet'></i>";
        }
        if($wire<>null){
            $icon="<i class=\"fas fa-wifi $text_class\"></i>";
        }
        $width1="class=\"$text_class center\" align=right width=1% nowrap";

        if($val=="tailscale0"){
            $metric_warning=null;
            $metric_text=null;$gateway_text=null;$error=null;$hardware_text=null;
        }

        if($ComputerMacAddress==null){$ComputerMacAddress=$tpl->icon_nothing();}


        if($nicz->enabled==1){
            if($nicz->mirror==1){
                $wire= "&nbsp;&nbsp;<span class='label label-inverse'>{mirror} $nicz->mirrorgateway</span>";
            }
        }

        if($nicz->UseSPAN==1){
            $FIREHOLE_ROW       =$tpl->icon_nothing();
            $PROXY_ARP          = $tpl->icon_nothing();
            $MASQUERADE         = $tpl->icon_nothing();
            $watchdog_field     = $tpl->icon_nothing();
            $access_internet    = $tpl->icon_nothing();
            $error              = null;
            $gateway            = $tpl->icon_nothing();
            $NETADDR_TEXT       = "<strong>0.0.0.0</strong>";
            $wire               = "&nbsp;&nbsp;"."<span class='label label-inverse'>{mirror}</span>";
            $text_class         = null;
            $icon               ="<i class=\"text-info fas fa-ethernet\"></i>";
        }
        if($bondEnabled==1) {
            if (array_key_exists($val, $bondMembers)) {
                $text_class = "text-info";
                $metric_warning = null;
                $error = "&nbsp;<span class='label'>{bonded_to} {$bondMembers["bond"]}</span>";
                $icon = "<i class='fa fa-project-diagram'></i>";
                $href = null;
                $FIREHOLE_ROW = $tpl->icon_nothing();
                $PROXY_ARP = $tpl->icon_nothing();
                $MASQUERADE = $tpl->icon_nothing();
                $watchdog_field = $tpl->icon_nothing();
                $access_internet = $tpl->icon_nothing();
                $gateway = $tpl->icon_nothing();
            }
        }
        if($OpenVswitchEnable==1){
            if($nicz->virtualbridge==1){
                $icoArr=ico_arrow_right;
                $NOT_CONFIGURED = false;
                $MUST_CHANGE=false;
                $VirtualBridge="&nbsp;<span class='label label-blue'>{virtual_switch} {bonded_to} switch$val &nbsp;<i class='$icoArr'></i>&nbsp;{$val}h0</span>";
                $error = "";
                $text_class="";
                $icon="<i class='text-info fas fa-bezier-curve'></i>";
                $NETADDR_TEXT=$tpl->icon_nothing();
                $ACTUAL_NETMASK=$tpl->icon_nothing();
                $gateway_text="";
                $gateway_strong="";
                $gateway=$tpl->icon_nothing();
                $metric_text="";
                $gateway_strong2="";
                $access_internet=$tpl->icon_nothing();
            }
            if(preg_match("#^switch#",$val)){
                $metric_warning="";
                $ComputerMacAddressError="";
                $href="";
            }
            if(preg_match("#^(.+?)h([0-9]+)$#",$val,$m)){
                $error = "";
                $Index = intval($m[2]);
                if($m[2]==0) {
                    $metric_text = "";
                    $metric_warning = "";
                    $ComputerMacAddressError = "";
                    $url = "Loadjs('$page?nic-config-js=$m[1]&md=$md');";
                    // Loadjs('fw.network.interfaces.php?nic-config-js=$m[1]&md=$md');";
                    if ($DisableNetworking == 1) {
                        $url = "blur()";
                    }
                    $href = "<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold' class='$text_class'>";

                    $text_class = "font-bold";
                    $access_internet = $tpl->icon_nothing();
                    $znic = new system_nic($m[1]);
                    if ($znic->NoInternetCheck == 0) {
                        if ($EnableMsftncsi == 1) {
                            $access_internet = "<i class='fas fa-check'></i>";
                        }
                    }
                }
            }
        }


		$html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"center $text_class\" style='width:1%' nowrap>$icon</td>";
		$html[]=$tpl->_ENGINE_parse_body("<td class=\"$text_class\">$href$nicz->netzone: $nicz->NICNAME ($val)</a>$VirtualBridge$wire$metric_warning$error$hardware_text$macvlan_icon</td>");
		$html[]=$tpl->_ENGINE_parse_body("<td $width1>$NETADDR_TEXT</a> $dhcp_text</td>");
		$html[]=$tpl->_ENGINE_parse_body("<td $width1>$ACTUAL_NETMASK</a></td>");
		$html[]=$tpl->_ENGINE_parse_body("<td $width1>$ComputerMacAddress</a>$ComputerMacAddressError</td>");
		$html[]=$tpl->_ENGINE_parse_body("<td $width1>$gateway_strong$gateway$gateway_strong2$metric_text<small>$gateway_text</small></a></td>");
		$html[]=$tpl->_ENGINE_parse_body("<td $width1>$access_internet</a></td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $width1>$watchdog_field</a></td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $width1>$PROXY_ARP</a></td>");
        $html[]=$tpl->_ENGINE_parse_body("<td $width1>$MASQUERADE</td>");
		$html[]=$tpl->_ENGINE_parse_body("<td $width1>$FIREHOLE_ROW</td>");
		$html[]="</tr>";


	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='11'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $TINY_ARRAY["TITLE"]="{network_interfaces}: {details}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-ethernet";
    $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="
<script>
	$.address.state('/');
	$.address.value('/nics');
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }) });
	$jstiny
</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table_bond_list($eth,$NETADDR){
    $sql = "SELECT * FROM bond_interfaces WHERE nic='$eth'";
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $resultsVirtuals = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "<div class='alert alert-danger'>" . $q->mysql_error_html(true) . "</div>";
    }
    foreach ($resultsVirtuals as $index => $ligne) {
        $ifaces = explode(",",$ligne["members"]);
        foreach ($ifaces as $members){

            $NETADDR[] = "<i style='font-size:11px'>Members: {$members}</i>";
        }

    }
    return $NETADDR;
}
function table_virtuals_list($eth,$NETADDR)
{
    $sql = "SELECT ipaddr,ID FROM nics_virtuals WHERE nic='$eth'";
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $resultsVirtuals = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "<div class='alert alert-danger'>" . $q->mysql_error_html(true) . "</div>";
    }
    foreach ($resultsVirtuals as $index => $ligne) {
        $NETADDR[] = "<i style='font-size:11px'>virtual: {$ligne["ipaddr"]}</i>";
    }
    return $NETADDR;
}
function table_vlan_list($eth,$NETADDR)
{
    $qvlan = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $resultsVLANs = $qvlan->QUERY_SQL("SELECT * FROM nics_vlan WHERE nic='$eth' ORDER BY ID");
    if (!$qvlan->ok) {
        echo "<div class='alert alert-danger'>" . $qvlan->mysql_error_html(true) . "</div>";
    }
    VERBOSE("SELECT * FROM nics_vlan WHERE nic='$eth' ORDER BY ID = ".count($resultsVLANs)." lines...");

    foreach ($resultsVLANs as $index => $ligne) {
        $NETADDR[] = "<i style='font-size:11px'>vlan: {$ligne["ipaddr"]}</i>";
    }

    return $NETADDR;}


function nic_vlan_info($json,$ID){
    $array=$json->Interfaces;
    foreach ($array as $main){
        if ($main->ID==$ID){
            return $main;
        }
    }
    $Info=array();
    $Info["isError"]=true;
    $Info["error"]="Interface not found";
    return json_encode($Info);

}
function nic_vlan_start():bool{
    //nic-vlan-start=$ID&eth=$eth
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["nic-vlan-start"]);
    $eth=$_GET["eth"];
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/vlan/start/$ID");
    $json=json_decode($data);
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');";
    return admin_tracks("Configure VLAN ID $ID on $eth");
}
function nic_vlan_stop():bool{
    //nic-vlan-start=$ID&eth=$eth
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["nic-vlan-stop"]);
    $eth=$_GET["eth"];
    $sock=new sockets();
    $data=$sock->REST_API("/system/network/vlan/stop/$ID");
    $json=json_decode($data);
    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('nics-vlans','$page?nic-vlans-list=$eth');";
    return admin_tracks("Bring down VLAN ID $ID on $eth");
}
function  nic_vlans_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $text_class=null;
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    $sock=new sockets();
    $data=$sock->REST_API("/system/network/vlan/list");
    $json=json_decode($data);
    if(!$json->Status){
        echo $tpl->div_error("{error}||$json->Error");
    }

    if(!$q->FIELD_EXISTS("nics_vlan","masquerade")){$q->QUERY_SQL("ALTER TABLE nics_vlan ADD `masquerade` INTEGER NOT NULL default 0");}

    $eth=$_GET["nic-vlans-list"];
    $t=time();
    $html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Nic</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Mac</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>TX/RX</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>MASQ.</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $sql="SELECT * FROM nics_vlan WHERE nic='$eth' ORDER BY ID"; //
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);return false;}
    $net=new networking();

    $td1="class=\"$text_class\" style='width:1%'";
    $TRCLASS=null;
    $unpc="$td1 nowrap";
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $ID=$ligne["ID"];
        $enabled=intval($ligne["enabled"]);
        $JsonInfo=nic_vlan_info($json,$ID);


        $vlanid=$ligne["vlanid"];
        $eth_text="vlan$ID";
        if($ligne["cdir"]==null){
            $ligne["cdir"]=$net->array_TCP[$ligne["nic"]];

        }
        $Action=$tpl->icon_stop("Loadjs('$page?nic-vlan-stop=$ID&eth=$eth')");
        $text_class=null;
        $Status="<span class='label label-primary'>{running}</span>";
        $Error="";

        $url="Loadjs('$page?nic-vlan-js=$ID&eth=$eth');";
        $href="<a href=\"javascript:blur()\" OnClick=\"$url\" style='font-weight:bold'>";

        $delete=$tpl->icon_delete("Loadjs('$page?nic-vlan-delete-js=$ID&md={$md}')","AsSystemAdministrator");
        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?nic-vlan-enable=$ID')",null,"AsSystemAdministrator");

        $masquerade=$tpl->icon_check($ligne["masquerade"],"Loadjs('$page?nic-vlan-masquerade=$ID')",null,"AsSystemAdministrator");

        if($JsonInfo->isError){
            $Status="<span class='label label-danger'>{inactive2}</span>";
            $Error="<br><small class='text-danger'>$JsonInfo->error</small>";
            $Action=$tpl->icon_run("Loadjs('$page?nic-vlan-start=$ID&eth=$eth')");
        }
        if(!$JsonInfo->isUp){
            $Status="<span class='label label-warning'>{down}</span>";
            $Action=$tpl->icon_run("Loadjs('$page?nic-vlan-start=$ID&eth=$eth')");
        }

        $iparray=array();
        foreach ($JsonInfo->currentIPs as $ipaddrs){
            $iparray[]=$ipaddrs."<br>";
        }

        if(count($iparray)==0){
            $Status="<span class='label label-warning'>No IP</span>";
        }

        if($enabled==0){
            $Status="<span class='label label-default'>{disabled}</span>";
            $Action="&nbsp;";
        }

        $RxBytes=FormatBytes($JsonInfo->stats->RxBytes/1024);
        $TxBytes=FormatBytes($JsonInfo->stats->TxBytes/1024);



        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $unpc >$Status</td>";
        $html[]="<td $unpc >$Action</td>";
        $html[]="<td $unpc >$href$eth_text.$vlanid$Error</a></td>";
        $html[]="<td class=\"$text_class\">$JsonInfo->macAddress</td>";
        $html[]="<td class=\"$text_class\" nowrap>".implode("",$iparray)."</td>";
        $html[]="<td class=\"$text_class\">{$ligne["gateway"]}</td>";
        $html[]="<td class=\"$text_class\">$TxBytes/$RxBytes</td>";
        $html[]="<td $unpc class='center' >$masquerade</td>";
        $html[]="<td $unpc class='center'>$enable</td>";
        $html[]="<td $unpc class='center'>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
		<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
		</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function nic_virtuals_search(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$eth=$_GET["nic-virtuals-list"];
    $function=$_GET["function"];
	$t=time();
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{interface}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{tcp_address}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{netmask}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $topbuttons[] = array("Loadjs('$page?nic-virtual-js=0&eth=$eth&function=$function')", ico_plus, "{new_interface}");

    $buttons=base64_encode($tpl->th_buttons($topbuttons));
	
	$sql="SELECT * FROM nics_virtuals WHERE nic='{$eth}' ORDER BY ID"; //

    if(strlen($_GET["search"])){
        $search="*{$_GET["search"]}*";
        $search=str_replace($search,"**","*");
        $search=str_replace($search,"**","*");
        $search=str_replace($search,"*","%");
        $sql="SELECT * FROM nics_virtuals WHERE nic='{$eth}' AND ( ipaddr LIKE '$search') ORDER BY ID";
    }

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	$net=new networking();
	$interfaces=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?ifconfig-interfaces=yes")));
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
        $eth_text="{$ligne["nic"]}:{$ligne["ID"]}";
		if($ligne["cdir"]==null){
			$ligne["cdir"]=$net->array_TCP[$ligne["nic"]];

		}
		
		$text_class="alert alert-warning";
        if($interfaces[$eth_text]<>null){
			$text_class=null;
        }
		
		if($ligne["ipv6"]==1){
			$text_class=null;
        }
		
	
		$url="Loadjs('$page?nic-virtual-js={$ligne["ID"]}&eth={$ligne["nic"]}&function=$function');";
        $eth_text=$tpl->td_href($eth_text,"",$url);
		
		$delete=$tpl->icon_delete("Loadjs('$page?nic-virtual-delete-js={$ligne["ID"]}&eth={$ligne["nic"]}')","AsSystemAdministrator");
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]=$tpl->_ENGINE_parse_body("<td class=\"$text_class\">$eth_text</td>");
		$html[]=$tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["ipaddr"]}</td>");
		$html[]=$tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["gateway"]}</td>");
		$html[]=$tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["netmask"]}</a></td>");
		$html[]=$tpl->_ENGINE_parse_body("<td class=\"$text_class center\">$delete</td>");
		$html[]="</tr>";
		}
	
		$html[]="</tbody>";
		$html[]="<tfoot>";
		
		$html[]="<tr>";
		$html[]="<td colspan='5'>";
		$html[]="<ul class='pagination pull-right'></ul>";
		$html[]="</td>";
		$html[]="</tr>";
		$html[]="</tfoot>";
		$html[]="</table>";
		$html[]="
		<script>
		
		 document.getElementById('nics-virtual-buttons').innerHTML=base64_decode('$buttons');
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
		</script>";
		
		echo $tpl->_ENGINE_parse_body($html);
}

function multip_path_table(){
    $TRCLASS=null;
	$eth=$_GET["multipath-table"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$t=time();
	$nothing=$tpl->icon_nothing();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?multip-path-js=0&eth=$eth');\"><i class='fa fa-plus'></i> {new_gateway} </label>";
	$html[]="</div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";
	
	$html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{gateway}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{weight}</th>";
	$html[]="<th data-sortable=false style='width:1%'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$nic=new system_nic($eth);
	if($nic->GATEWAY<>"0.0.0.0") {
        $html[] = "<tr class='$TRCLASS' id='none'>";
        $html[] = "<td><strong>$nic->GATEWAY</strong></td>";
        $html[] = "<td style='width:1%' class=center>1</center></th>";
        $html[] = "<td style='width:1%' class=center>$nothing</center></th>";
        $html[] = "</tr>";
    }
	

	$sql="SELECT * FROM `multipath` WHERE `nic`='$eth' ORDER BY `weight`";
	$results=$q->QUERY_SQL($sql);

	foreach ($results as $index=>$ligne){
		$md=md5(serialize($ligne));
	
		$delete=$tpl->icon_delete("Loadjs('$page?multip-path-delete={$ligne["ID"]}&md=$md')","AsSystemAdministrator");
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
		$ID=$ligne["ID"];
		$gateway=$ligne["gateway"];
		$weight=$ligne["weight"];
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>$gateway</strong></td>";
		$html[]="<td style='width:1%' class=center>$weight</center></td>";
		$html[]="<td style='width:1%' class=center>$delete</center></td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";	
	
	echo $tpl->_ENGINE_parse_body($html);
}

function multipath_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=$_GET["multipath-js"];
	$eth=$_GET["eth"];
	if($ID==0){
		$title="{new_gateway}";
	}else{
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$ligne=$q->mysqli_fetch_array("SELECT gateway from `multipath` WHERE ID='$ID'");
		$title=$ligne["gateway"];
	}
	
	$tpl->js_dialog4("$eth: $title", "$page?mulipath-popup=$ID&eth=$eth");
}
function multipath_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$eth=$_GET["eth"];
	$jsafter=null;
	$bt="{add}";
	$ID=$_GET["multipath-popup"];
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$ligne=$q->mysqli_fetch_array("SELECT * from `multipath` WHERE ID='$ID'");
		$title=$ligne["gateway"];
	}else{
		$jsafter="LoadAjax('multipath-div','$page?multipath-table=$eth');";
	}
	
	if(intval($ligne["weight"])==0){$ligne["weight"]=1;}
	$tpl->field_hidden("multipath-eth", $eth);
	$tpl->field_hidden("multipath-id", $ID);
	$form[]=$tpl->field_ipaddr("multip-path-gateway", "{gateway}", $ligne["gateway"]);
	$form[]=$tpl->field_numeric("weight","{weight}",$ligne["weight"]);
	echo $tpl->form_outside($title, $form,null,$bt,"dialogInstance4.close();$jsafter","AsSystemAdministrator");
}

function multipath_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["multipath-id"];
	$gateway=$_POST["multip-path-gateway"];
	$weight=$_POST["weight"];
	$eth=$_POST["multipath-eth"];

	if($gateway=="0.0.0.0"){echo "wrong gateway $gateway\n";return false;}

	if($ID==0){
		$sql="INSERT INTO `multipath` (nic,weight,gateway) VALUES ('$eth','$weight','$gateway')";
	}else{
		$sql="UPDATE `multipath` SET gateway='$gateway',weight='$weight' WHERE ID=$ID";
	}
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return false;}
	return admin_tracks_post("Saving network multipath gateway");
	
}

function multipath_delete():bool{
	$ID=$_GET["multip-path-delete"];
	$md=$_GET["md"];
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$q->QUERY_SQL("DELETE FROM `multipath` WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return false;}
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();\n";
	return admin_tracks_post("Remove network multipath gateway");
}
function MULTIPATH_GATEWAYS($eth){
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$sql="CREATE TABLE IF NOT EXISTS `multipath` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`nic` TEXT NOT NULL,`weight` INTEGER NOT NULL DEFAULT 1, `gateway` TEXT NOT NULL)";
	$q->QUERY_SQL($sql);
	$sql="SELECT * FROM `multipath` WHERE `nic`='$eth' ORDER BY `weight`";
	$results=$q->QUERY_SQL($sql);
	if(count($results)==0){return array();}
	$ipclass=new IP();

	$GATEWAYS=array();

	foreach ($results as $index=>$ligne){
		$ligne["gateway"]=trim($ligne["gateway"]);
		$weight=intval($ligne["weight"]);
		if($weight==0){$weight=1;}
		if(!$ipclass->isIPAddress($ligne["gateway"])){continue;}
		$GATEWAYS[$ligne["gateway"]]=$weight;
	}

	if(count($GATEWAYS)==0){
		VERBOSE("GATEWAY: NOT SET", __LINE__);
		$nic=new system_nic($eth);
		VERBOSE("GATEWAY: $eth default=$nic->GATEWAY", __LINE__);
		return array($nic->GATEWAY);}

	$nic=new system_nic($eth);
	VERBOSE("GATEWAY: $eth default=$nic->GATEWAY", __LINE__);
	$GATEWAYS[$nic->GATEWAY]=$nic->metric;

	$f=array();
	foreach ($GATEWAYS as $gateway=>$weight){
		VERBOSE("GATEWAY: $gateway ($weight)", __LINE__);
		$f[]="$gateway";

	}
	
	return $f;

}

function AddBond() {
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $rf=refreshjs();
    $jsafter="$rf;LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $tpl->js_confirm_execute("{add_bond_interface_confirmation}","addbondinterface",1,$jsafter);
    return true;
}

function AddBondSave():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/bond/add"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Add New Bond Interface");

}

function macvlan_js():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $tpl->js_dialog("{new_virtual_interface}","$page?add-macvlan-popup=yes");
    return true;
}
function macvlan_popup():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $type=array();
    $type["macvlan"]="macvlan";
    $type["ipvlan"]="ipvlan";
    $form[]=$tpl->field_hidden("macvlan","yes");
    $form[]=$tpl->field_interfaces("interface","nooloopNoDefphy:{physical_network_interface}");
    $form[]=$tpl->field_array_hash($type,'veth_type','{type}',$type["macvlan"],true,null,null,false);
    $jsrestart="BootstrapDialog1.close();LoadAjax('network-interfaces-table','$page?table=yes');";
    $hml[]=$tpl->form_outside("{virtual_interfaces}", @implode("\n", $form),"-{macvlan_explain}<br><br>-{ipvlan_explain}","{add}",$jsrestart,"AsSystemAdministrator");
    echo @implode("\n", $hml);
    return true;
}
function macvlan_save():bool{
    $tpl    = new template_admin();
    $catz    = new system_nic();
    if(!$catz->macvlan_create($_POST["interface"],$_POST["veth_type"])){
        echo $tpl->post_error($catz->mysql_error);
    }
    return true;
}
function macvlan_delete(){
    $eth=$_GET["delete-macvlan"];
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $md=$_GET["md"];
    $tpl->js_confirm_delete("Macvlan $eth","delete-macvlan",$eth,"dialogInstance1.close();$('#$md').remove();");
}
function macvlan_delete_perform(){
    $eth=$_POST["delete-macvlan"];
    admin_tracks("Remove macvlan interface $eth");
    $nic=new system_nic($eth);
    $nic->RemoveInterface($eth);

}
function carrier_change_explain_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return $tpl->js_dialog2("{about2}", "$page?carrier-change-explain-popup=yes");
}
function carrier_change_explain_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{carrier_changes}");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function AsGateway_js():bool{
    $enable = $_GET["AsGateway-js"];
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $jsafter="LoadAjax('netz-interfaces-status','$page?status2=yes');";
    if($enable==1){
        $tpl->js_confirm_execute("{enable_artica_as_gateway_ask}","AsGateway",1,$jsafter);
    }else{
        $tpl->js_confirm_execute("{disable_artica_as_gateway_ask}","AsGateway",0,$jsafter);
    }
    return true;
}

function YourFirewall_progress($enable=0):string{
    $page       = CurrentPageName();
    $config[1]  = "/firewall/install";
    $config[0]  = "/firewall/uninstall";
    $jsafter    = "LoadAjax('netz-interfaces-status','$page?status2=yes');";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/firehol.reconfigure.progress";
    $ARRAY["LOG_FILE"]      = PROGRESS_DIR . "/firehol.reconfigure.log";
    $ARRAY["CMD"]           = $config[$enable];
    $ARRAY["TITLE"]         = "{your_firewall}";
    $ARRAY["AFTER"]         = $jsafter;
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=netz-interfaces-status')";
    return $jsrestart;


}
function YourFirewall_save():bool{
    if($_POST["YourFirewall"]==0){
        admin_tracks("Uninstall the Firewall service");
        return true;
    }
    admin_tracks("Install the Firewall service");
    return true;
}

function AsGateway_options_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
   return $tpl->js_dialog2("{options}", "$page?AsGateway-options-popup=yes");

}
function AsGateway_options_popup():bool{
    $tpl    = new template_admin();
    $EnableVPNPPTCompliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVPNPPTCompliance"));
    $page=CurrentPageName();

    $DisableNetworking =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
    $form[]=$tpl->field_checkbox("DisableNetworking","{DenyHaproxyConf}",$DisableNetworking);
    $form[]=$tpl->field_checkbox("EnableVPNPPTCompliance","{EnableVPNPPTCompliance}",$EnableVPNPPTCompliance);



    $hml[]=$tpl->form_outside("{options}", $form,null,"{apply}",
        "dialogInstance2.close();LoadAjax('table-loader-interfaces','$page?tabs=yes');","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($hml);
    return true;

}
function AsGateway_options_save():bool{
    $EnableVPNPPTCompliance=$_POST["EnableVPNPPTCompliance"];
    admin_tracks("Setp VPN PPTP compliance to $EnableVPNPPTCompliance");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableVPNPPTCompliance",$EnableVPNPPTCompliance);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableNetworking",intval($_POST["DisableNetworking"]));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return true;
}


function YourFirewall_js():bool{
    $enable = $_GET["YourFirewall-js"];
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $jsafter=YourFirewall_progress($enable);
    if($enable==1){
        $tpl->js_confirm_execute("{activate_firewall_ask}","YourFirewall",1,$jsafter);
    }else{
        $tpl->js_confirm_execute("{disable_firewall_ask}","YourFirewall",0,$jsafter);
    }
    return true;

}


function NetOptimize_save(){
    admin_tracks("Switch Kernel network optimization to {$_POST["netoptimize"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSystemNetworkOptimize",$_POST["netoptimize"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
}

function NetOptimize_js(){
    $enable = $_GET["netoptimize-js"];
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $users  = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $jsafter="LoadAjax('netz-interfaces-status','$page?status2=yes');";
    if($enable==1){
        $tpl->js_confirm_execute("{SystemNetworkOptimize_enable_explain}","netoptimize",1,$jsafter);
    }else{
        $tpl->js_confirm_execute("{SystemNetworkOptimize_disable_explain}","netoptimize",0,$jsafter);
    }
    return true;
}

function AsGateway_save(){
    admin_tracks("Switch gateway mode to {$_POST["AsGateway"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArticaAsGateway",$_POST["AsGateway"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");

}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}