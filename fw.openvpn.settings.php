<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_POST["SnortRulesCode"])){Save_gen();exit;}
if(isset($_POST["ENABLE_SERVER"])){SAVE_SERVER();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main-start"])){main_start();exit;}
if(isset($_GET["main-flat"])){main_flat();exit;}
if(isset($_GET["openvpn-status"])){openvpn_status();exit;}

if(isset($_GET["section-nic-js"])){section_nic_js();exit;}
if(isset($_GET["section-net-js"])){section_net_js();exit;}
if(isset($_GET["section-proto-js"])){section_proto_js();exit;}
if(isset($_GET["section-dhcp-js"])){section_dhcp_js();exit;}
if(isset($_GET["section-firewall-js"])){section_firewall_js();exit;}
if(isset($_GET["section-infos-js"])){section_infos_js();exit;}
if(isset($_GET["section-log-js"])){section_log_js();exit;}


if(isset($_GET["section-nic-popup"])){section_nic_popup();exit;}
if(isset($_GET["section-net-popup"])){section_net_popup();exit;}
if(isset($_GET["section-proto-popup"])){section_proto_popup();exit;}
if(isset($_GET["section-dhcp-popup"])){section_dhcp_popup();exit;}
if(isset($_GET["section-firewall-popup"])){section_firewall_popup();exit;}
if(isset($_GET["section-infos-popup"])){section_infos_popup();exit;}
if(isset($_GET["section-log-popup"])){section_log_popup();exit;}

page();
function openvpn_status():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $data = $sock->REST_API("/openvpn/service/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
        return true;
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsRestart="Loadjs('$page?restart-js=yes');";
    echo $tpl->SERVICE_STATUS($ini, "APP_OPENVPN",$jsRestart);

    echo "<script>\n";
    echo "function OpenVPNStatusCadence(){\n";
    echo "LoadAjaxSilent('openvpn-status','$page?openvpn-status=yes');\n";
    echo "}\n";
    echo "if ( document.getElementById('openvpn-status') ){\n";
    echo "setTimeout(\"OpenVPNStatusCadence()\",3000);\n";
    echo "}\n";
    echo "</script>\n";
    return true;
}
function restart_js():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsVPNManager){
        return $tpl->js_no_privileges();
    }
    $sock=new sockets();
    $sock->REST_API("/openvpn/service/restart");
    return $tpl->js_executed_background("{success}");
}

function jsTiny(){
    $version="&nbsp;";
    $error="{openvpn_whatis}";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/openvpn/service/version");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        $error="<span class='text-danger font-boldfw'>".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}</span>";

    }else {
        if (!$json->Status) {
            $TINY_ARRAY["DANGER"]=true;
            $ico=ico_engine_warning;
            $error="<span class='text-danger font-bold'><i class='$ico'></i>&nbsp;$json->Error</span>";
        }else{
            $version=sprintf(" v%s",$json->Info);
        }
    }


    $TINY_ARRAY["TITLE"]="{OPENVPN_SERVER_SETTINGS}$version";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{APP_OPENVPN_TEXT}<br>$error";
    $TINY_ARRAY["BUTTONS"]="";
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}


function nic_settings(){
	$nic=new system_nic($_POST["nic-settings"]);
	$nic->firewall_policy=$_POST["firewall_policy"];
	$nic->firewall_behavior=$_POST["firewall_behavior"];
	$nic->firewall_masquerade=$_POST["firewall_masquerade"];
	$nic->firewall_artica=$_POST["firewall_artica"];
	//$nic->DenyCountries=$_POST["DenyCountries"];
	$nic->SaveNic();
}

function section_js():string{
    $page=CurrentPageName();
    $js[]="dialogInstance2.close();";
    $js[]="LoadAjaxSilent('openvpn-flat','$page?main-flat=yes');";
    return @implode(";",$js);
}

function section_nic_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{server_settings}","$page?section-nic-popup=yes");
}
function section_net_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{network_parameters}","$page?section-net-popup=yes");
}
function section_proto_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{protocol}","$page?section-proto-popup=yes");
}
function section_dhcp_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{DHCP_SERVICE_OPENVPN}","$page?section-dhcp-popup=yes");
}
function section_firewall_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{FIREWALL_SERVICE_OPENVPN}","$page?section-firewall-popup=yes");
}
function section_infos_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{service_informations}","$page?section-infos-popup=yes");
}
function section_log_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{events}","$page?section-log-popup=yes");
}
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{OPENVPN_SERVER_SETTINGS}",
        "fas fa-cogs","{APP_OPENVPN_TEXT}<br>{openvpn_whatis}","$page?main-start=yes",
        "openvpn-settings","progress-openvpn-restart",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function main_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='openvpn-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%;padding-left:10px'>
		    <div id='openvpn-flat'></div>
		</td>
	</tr>
	</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('openvpn-status','$page?openvpn-status=yes');";
    $html[]="LoadAjaxSilent('openvpn-flat','$page?main-flat=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function main_flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $vpn=new openvpn();
    $nic=new networking();
    $sock=new sockets();
    $page=CurrentPageName();
    $users=new usersMenus();
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $squid_reverse=new squid_reverse();
    $sslcertificates=$squid_reverse->ssl_certificates_list();
    buildDefaults();
    $OpenVpnPasswordCert=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVpnPasswordCert");
    $OpenVPNCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNCertificate");
    $EnableOpenVPNEndUserPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNEndUserPage"));
    $MaxClients=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNMaxClients"));
    $OpenVPNTUNMTU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTUNMTU"));
    $OpenVPNTCPLIMIT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTCPLIMIT"));
    $OpenVPNPing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNPing"));
    $OpenVPNRenegSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNRenegSec"));
    $OpenVPNPingRestart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNPingRestart"));
    $OpenVPNFragments=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNFragments"));
    $OpenVPNUseLZO=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNUseLZO"));
    $OpenVPNClientTOClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNClientTOClient"));
    $OpenVPNVerboseMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVerboseMode"));
    $OpenVPNTunInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTunInterface");
    $OpenVPNTransParentSquid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTransParentSquid"));
    $OpenVPNRedirectGateway=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNRedirectGateway");
    $OpenVPNBlockLocal=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNBlockLocal");
    $OpenVPNAuthenticate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNAuthenticate");
    $OpenVPNClientCertNotRequired=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNClientCertNotRequired");
    $OpenVPNMSSFIX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNMSSFIX"));
    $EnableOpenVPNFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNFirewall"));



    if($MaxClients==0){$MaxClients=500;}

    for($i=0;$i<12;$i++){
        $VERB[$i]=$i;
    }

    for($i=0;$i<256;$i++){
        $TunInterfaces["tun{$i}"]="tun{$i}";
    }

    if($OpenVPNTunInterface==null){$OpenVPNTunInterface="tun0";}


    $CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];
    if($CLIENT_NAT_PORT==null){$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];}
    if(intval($vpn->main_array["GLOBAL"]["LISTEN_PORT"])==0){$vpn->main_array["GLOBAL"]["LISTEN_PORT"]=1194;}
    if(trim($vpn->main_array["GLOBAL"]["IP_START"])==null){$vpn->main_array["GLOBAL"]["IP_START"]="10.8.0.0";}
    if(trim($vpn->main_array["GLOBAL"]["NETMASK"])==null){$vpn->main_array["GLOBAL"]["NETMASK"]="255.255.255.0";}
    if(intval($vpn->main_array["GLOBAL"]["PROXYPORT"])<10){$vpn->main_array["GLOBAL"]["PROXYPORT"]=8080;}
    if(trim($vpn->main_array["GLOBAL"]["LOCAL_BIND"])==null){$vpn->main_array["GLOBAL"]["LOCAL_BIND"]="eth0";}


    $listen=sprintf("%s://%s:%s",$vpn->main_array["GLOBAL"]["LISTEN_PROTO"],$vpn->main_array["GLOBAL"]["LOCAL_BIND"],$vpn->main_array["GLOBAL"]["LISTEN_PORT"]);
    $tpl->table_form_section("{server_settings}");
    $tpl->table_form_field_js("Loadjs('$page?section-nic-js=yes')","AsVPNManager");
    $init_error=null;

    $openvpn_local=false;
    if($OpenVPNRedirectGateway==1){

        if($vpn->main_array["GLOBAL"]["IPTABLES_ETH"]==null){
            $openvpn_local=true;
            $init_error=$tpl->_ENGINE_parse_body("{openvpn_handle_all_traffic_not_interface}");
            $init_error="<br><span class='text-danger' style='font-size: small'>$init_error</span>";
        }
    }
    $IPTABLES_ETH="";
    if($vpn->main_array["GLOBAL"]["IPTABLES_ETH"]<>null){
        $IPTABLES_ETH=" <li class='".ico_arrow_right." fa-1x'></li> {$vpn->main_array["GLOBAL"]["IPTABLES_ETH"]}";
    }

    $tpl->table_form_field_text("{openvpn_local}","<span style='text-transform: initial'>$listen <li class='".ico_arrow_right." fa-1x'></li> $OpenVPNTunInterface$IPTABLES_ETH</span>$init_error",ico_nic,$openvpn_local);

    $OpenVPNCertificate_error=false;
    if(strlen($OpenVPNCertificate)<2){
        $OpenVPNCertificate_error=true;
        $OpenVPNCertificate="<span class='text-danger font-bold'>{not_configured}&nbsp;</span>";
    }
    $tpl->table_form_field_text("{certificate}","<span style='text-transform: initial'>$OpenVPNCertificate</span>",ico_certificate,$OpenVPNCertificate_error);

    /*
    $tpl->table_form_field_js("Loadjs('$page?section-firewall-js=yes')","AsVPNManager");
    if($EnableOpenVPNFirewall==0){
        $tpl->table_form_field_bool("{EnableOpenVPNFirewall}",0,ico_shield);
    }else{
        $pport=array();
        if(count($openvpn_firewall_ports_allow)>0){
            $pport[]="{ports_allow} ".@implode(",",$openvpn_firewall_ports_allow);
        }
        if(count($openvpn_firewall_ports_allow)>0){
            $pport[]="{ports_deny} ".@implode(",",$openvpn_firewall_ports_allow);
        }
        if(count($pport)>0){
            $tpl->table_form_field_text("{FIREWALL_SERVICE_OPENVPN}","<small>".@implode(",",$pport)."</small>",ico_shield);
        }else{
            $tpl->table_form_field_bool("{EnableOpenVPNFirewall}",1,ico_shield);
        }
    }
    */





    $f[]="{MaxClients} $MaxClients";

    if ($OpenVPNTUNMTU>0){
        $f[]="{MTU} $OpenVPNTUNMTU";
    }
    if ($OpenVPNTCPLIMIT>0){
        $f[]="{OpenVPNTCPLIMIT} $OpenVPNTCPLIMIT";
    }
    if ($OpenVPNFragments>0){
        $f[]="{internal_fragmentation_algorithm} $OpenVPNFragments bytes";
    }
    if ($OpenVPNMSSFIX>0){
        $f[]="MSSFIX $OpenVPNMSSFIX bytes";
    }

    $network_parameters=@implode(", ",$f);
    $tpl->table_form_field_js("Loadjs('$page?section-net-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{network_parameters}","<small>$network_parameters</small>",ico_timeout);



    $f=array();
    if($OpenVPNUseLZO==1){
        $f[]="{UseLZO}";
    }else{
        $f[]="{UseLZO} {no}";
    }
    $f[]="{OpenVPNPing} {no}";

    if($OpenVPNRenegSec>0){
        $f[]="{renegsec} $OpenVPNRenegSec {seconds}";
    }
    $protocl_parameters=@implode(", ",$f);
    $tpl->table_form_field_js("Loadjs('$page?section-proto-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{protocol}","<small>$protocl_parameters</small>",ico_proto);


    $tpl->table_form_field_js("Loadjs('$page?section-dhcp-js=yes')","AsVPNManager");


    $f=array();
    $dns=array();
    $f[]="{from_ip_address} {$vpn->main_array["GLOBAL"]["IP_START"]}/{$vpn->main_array["GLOBAL"]["NETMASK"]}";

    if(strlen($vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"])>3){
        $dns[]=$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"];
    }
    if(strlen($vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"])>3){
        $dns[]=$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"];
    }

    if (count($dns)>0){
        $f[]="DNS ".@implode(",",$dns);
    }

    if( $vpn->main_array["GLOBAL"]["REMOVE_SERVER_DEFAULT_ROUTE"]==1){
        $f[]="{remove_server_route}";
    }
    if(strlen($vpn->main_array["GLOBAL"]["WAKEUP_IP"])>3){
        $f[]="{wake_up_ip} {$vpn->main_array["GLOBAL"]["WAKEUP_IP"]}";
    }

    //  $form[]=$tpl->field_checkbox("EnableOpenVPNEndUserPage","{EnableOpenVPNEndUserPage}",$EnableOpenVPNEndUserPage,false,"{EnableOpenVPNEndUserPage_explain}");
    //  $form[]=$tpl->field_checkbox("OpenVPNClientCertNotRequired","{OpenVPNClientCertNotRequired}",$OpenVPNClientCertNotRequired,false,"{OpenVPNClientCertNotRequired_explain}");
    // $form[]=$tpl->field_checkbox("OpenVPNAuthenticate","{enable_authentication}",$OpenVPNAuthenticate,false,"{enable_authentication_vpn_explain}");

    //$OpenVPNAuthenticate=1;
    // Always True because now

    if($EnableOpenVPNEndUserPage==1){
      //  $f[]="{EnableOpenVPNEndUserPage}"; Feature not maintain.
    }
    if($OpenVPNClientCertNotRequired==1){
        // the ssl certificate is always generated in engine
       $f[]="{OpenVPNClientCertNotRequired}";
    }
    if($OpenVPNAuthenticate==1){
        $f[]="{enable_authentication}";
    }
    if($OpenVPNRedirectGateway==1){
        $f[]="{OpenVPNRedirectGateway}";
    }
    if($OpenVPNBlockLocal==1){
        $f[]="{OpenVPNBlockLocal}";
    }
//    if($OpenVPNClientTOClient==1){
  //      $f[]="{OpenVPNClientTOClient}";
   // }


    $dhcp_params=@implode(", ",$f);
    $tpl->table_form_field_text("{DHCP_SERVICE_OPENVPN}","<small>$dhcp_params</small>",ico_users);


    $tpl->table_form_field_js("Loadjs('$page?section-infos-js=yes')","AsVPNManager");

    if(strlen($vpn->main_array["GLOBAL"]["PUBLIC_IP"])==0){
        $vpn->main_array["GLOBAL"]["PUBLIC_IP"]="<span class='text-danger font-bold'>{not_configured}&nbsp;</span>";
    }

    $text[]="{public_ip_addr} {$vpn->main_array["GLOBAL"]["PUBLIC_IP"]}:$CLIENT_NAT_PORT";

    if($vpn->main_array["GLOBAL"]["USE_RPROXY"]==1){
        $text[]="{reverse_proxy} {$vpn->main_array["GLOBAL"]["PROXYADDR"]}:{$vpn->main_array["GLOBAL"]["PROXYPORT"]}";
    }
    $service_informations=@implode(", ",$text);
    $tpl->table_form_field_text("{service_informations}","<small>$service_informations</small>",ico_infoi);

    $OpenVPNLegalLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNLegalLogs"));
    if($OpenVPNVerboseMode<2){$OpenVPNVerboseMode=2;}


    $logs[]="{log_level} $OpenVPNVerboseMode";
    if($OpenVPNLegalLogs==0){
        $logs[]="{legal_logs} {inactive2}";
    }else{
        $logs[]="{legal_logs} {active2}";
    }
    $events=@implode(", ",$logs);
    $tpl->table_form_field_js("Loadjs('$page?section-log-js=yes')","AsVPNManager");
    $tpl->table_form_field_text("{events}","<small>$events</small>",ico_eye);
    echo sprintf("%s\n<script>%s</script>",$tpl->table_form_compile(),jsTiny());

}
function section_log_popup():bool{
    $tpl=new template_admin();
    for($i=2;$i<12;$i++){
        $VERB[$i]=$i;
    }
    $OpenVPNLegalLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNLegalLogs"));
    $OpenVPNVerboseMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVerboseMode"));
    if($OpenVPNVerboseMode<2){$OpenVPNVerboseMode=2;}
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_array_hash($VERB,"OpenVPNVerboseMode","{log_level}",$OpenVPNVerboseMode,false);
    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager",true);
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}

function section_net_popup(){
    $tpl=new template_admin();
    $OpenVPNFragments=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNFragments"));
    $MaxClients=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNMaxClients"));
    $OpenVPNTUNMTU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTUNMTU"));
    $OpenVPNTCPLIMIT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTCPLIMIT"));
    $OpenVPNInternalFragmentationAlgorithm=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNInternalFragmentationAlgorithm"));
    $OpenVPNMSSFIX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNMSSFIX"));

    if($MaxClients==0){$MaxClients=500;}

    $TCP_LIMIT[64]=64;
    $TCP_LIMIT[256]=256;
    $TCP_LIMIT[512]=512;
    $TCP_LIMIT[1024]=1024;
    $TCP_LIMIT[0]="{default}";


    $MTUS[1024]=1024;
    $MTUS[1472]=1472;
    $MTUS[1500]=1500;
    $MTUS[2000]=2000;
    $MTUS[6000]=6000;
    $MTUS[9000]=9000;
    $MTUS[12000]=12000;
    $MTUS[24000]=24000;
    $MTUS[36000]=36000;
    $MTUS[48000]=48000;
    $MTUS[60000]=60000;
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_numeric("OpenVPNMaxClients", "{MaxClients}", $MaxClients);
    $form[]=$tpl->field_array_hash($TCP_LIMIT,"OpenVPNTCPLIMIT","{OpenVPNTCPLIMIT}",$OpenVPNTCPLIMIT,
        false,"{OpenVPNTCPLIMIT_EXPLAIN}");
    $form[]=$tpl->field_array_hash($MTUS,"OpenVPNTUNMTU","{MTU}",$OpenVPNTUNMTU,false,"{MTU_EXPLAIN}");
    $form[]=$tpl->field_numeric("OpenVPNFragments", "{internal_fragmentation_algorithm} (bytes)", $OpenVPNFragments,"{openvpn_fragment_explain}");
    $form[]=$tpl->field_numeric("OpenVPNMSSFIX", "MSSFIX (bytes)", $OpenVPNMSSFIX,"{openvpn_mssfix_explain}");

    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}
function section_nic_popup():bool{
    $tpl=new template_admin();
    $vpn=new openvpn();
    $squid_reverse=new squid_reverse();
    $sslcertificates=$squid_reverse->ssl_certificates_list();
    for($i=0;$i<256;$i++){
        $TunInterfaces["tun{$i}"]="tun{$i}";
    }
    $OpenVPNTunInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNTunInterface");
    if($OpenVPNTunInterface==null){$OpenVPNTunInterface="tun0";}
    $OpenVPNCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNCertificate");

    $form[]=$tpl->field_hidden("ENABLE_SERVER",$vpn->main_array["GLOBAL"]["ENABLE_SERVER"]);
    $form[]=$tpl->field_array_hash($tpl->ArrayNics(true),"LOCAL_BIND","{openvpn_local}",$vpn->main_array["GLOBAL"]["LOCAL_BIND"],false,"{openvpn_local_text}");

    $form[]=$tpl->field_array_hash($TunInterfaces,"OpenVPNTunInterface","{listen_interface}",$OpenVPNTunInterface,false);

    $form[]=$tpl->field_array_hash($tpl->ArrayNics(),"IPTABLES_ETH","{openvpn_access_interface}",$vpn->main_array["GLOBAL"]["IPTABLES_ETH"],
        false,"{openvpn_access_interface_text}");

    $form[]=$tpl->field_numeric("LISTEN_PORT", "{listen_port}", $vpn->main_array["GLOBAL"]["LISTEN_PORT"]);
    $form[]=$tpl->field_array_hash(array("tcp"=>"TCP","udp"=>"UDP"),"LISTEN_PROTO","{protocol}",$vpn->main_array["GLOBAL"]["LISTEN_PROTO"],false);

    $form[]=$tpl->field_array_hash($sslcertificates,"OpenVPNCertificate","{certificate}",$OpenVPNCertificate,false);



    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}

function section_firewall_popup(){
    $tpl=new template_admin();
    $EnableOpenVPNFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNFirewall"));
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $openvpn_firewall_ports_allow=array();
    $openvpn_firewall_ports_deny=array();
    $sql="SELECT ports FROM vpn_global_fw_rules_allow ORDER BY ports";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $openvpn_firewall_ports_allow[]=$ligne["ports"];
    }

    $sql="SELECT ports FROM vpn_global_fw_rules_deny ORDER BY ports";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $openvpn_firewall_ports_deny[]=$ligne["ports"];
    }
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_checkbox("EnableOpenVPNFirewall","{EnableOpenVPNFirewall}",
        $EnableOpenVPNFirewall,false,"{EnableOpenVPNFirewall_explain}");
    $form[]=$tpl->field_textareacode("openvpn_firewall_ports_allow", "{ports_allow}", @implode("\n", $openvpn_firewall_ports_allow));
    $form[]=$tpl->field_textareacode("openvpn_firewall_ports_deny", "{ports_deny}", @implode("\n", $openvpn_firewall_ports_deny));
    $myform=$tpl->form_outside(null, $form,"{FIREWALL_SERVICE_OPENVPN_EXPLAIN}","{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}

function section_infos_popup(){

    $tpl=new template_admin();
    $vpn=new openvpn();
    $tpl=new template_admin();

    $CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["CLIENT_NAT_PORT"];
    if($CLIENT_NAT_PORT==null){$CLIENT_NAT_PORT=$vpn->main_array["GLOBAL"]["LISTEN_PORT"];}

    if(intval($vpn->main_array["GLOBAL"]["PROXYPORT"])<20){
        $vpn->main_array["GLOBAL"]["PROXYPORT"]=3128;
    }
    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_ipaddr("PUBLIC_IP","{public_ip_addr}",$vpn->main_array["GLOBAL"]["PUBLIC_IP"],false);
    $form[]=$tpl->field_numeric("CLIENT_NAT_PORT", "{listen_port}", $CLIENT_NAT_PORT);
    $form[]=$tpl->field_checkbox("USE_RPROXY","{reverse_proxy}",$vpn->main_array["GLOBAL"]["USE_RPROXY"],"PROXYADDR,PROXYPORT","{OPENVPN_EXPLAIN_PROXY}");
    $form[]=$tpl->field_text("PROXYADDR", "{proxy_addr}", $vpn->main_array["GLOBAL"]["PROXYADDR"]);
    $form[]=$tpl->field_numeric("PROXYPORT", "{proxy_port}", $vpn->main_array["GLOBAL"]["PROXYPORT"]);
    $myform=$tpl->form_outside(null, $form,"{openvpn_ippub_explain}","{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}

function section_proto_popup(){
    $tpl=new template_admin();
    $OpenVPNRenegSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNRenegSec"));
    $OpenVPNUseLZO=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNUseLZO"));

    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_numeric("OpenVPNRenegSec", "{renegsec} ({seconds})", $OpenVPNRenegSec);
    //$form[]=$tpl->field_numeric("OpenVPNPing", "{OpenVPNPing} ({seconds})", $OpenVPNPing,"{OpenVPNPing_explain}");
    $form[]=$tpl->field_checkbox("OpenVPNUseLZO","{UseLZO}",$OpenVPNUseLZO,false);
    $myform=$tpl->form_outside(null, $form,null,"{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
}
function section_dhcp_popup(){
    $tpl=new template_admin();
    $vpn=new openvpn();
    $OpenVPNAuthenticate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNAuthenticate");
    $OpenVPNClientCertNotRequired=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNClientCertNotRequired");
    $EnableOpenVPNEndUserPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNEndUserPage"));
    $OpenVPNRedirectGateway=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNRedirectGateway");
    $OpenVPNClientTOClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNClientTOClient"));
    $OpenVPNBlockLocal=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNBlockLocal");
    if(trim($vpn->main_array["GLOBAL"]["IP_START"])==null){$vpn->main_array["GLOBAL"]["IP_START"]="10.8.0.0";}
    if(trim($vpn->main_array["GLOBAL"]["NETMASK"])==null){$vpn->main_array["GLOBAL"]["NETMASK"]="255.255.255.0";}

    $form[]=$tpl->field_hidden("ENABLE_SERVER",1);
    $form[]=$tpl->field_ipaddr("IP_START","{from_ip_address}",$vpn->main_array["GLOBAL"]["IP_START"],false);
    $form[]=$tpl->field_ipaddr("NETMASK","{netmask}",$vpn->main_array["GLOBAL"]["NETMASK"],false);
    $form[]=$tpl->field_ipaddr("VPN_DNS_DHCP_1","{dns_server} 1",$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_1"],false);
    $form[]=$tpl->field_ipaddr("VPN_DNS_DHCP_2","{dns_server} 2",$vpn->main_array["GLOBAL"]["VPN_DNS_DHCP_2"],false);

    $form[]=$tpl->field_ipaddr("WAKEUP_IP","{wake_up_ip}",$vpn->main_array["GLOBAL"]["WAKEUP_IP"],false,"{vpn_server_wakeupip_client_explain}");

    $form[]=$tpl->field_checkbox("REMOVE_SERVER_DEFAULT_ROUTE","{remove_server_route}",
        $vpn->main_array["GLOBAL"]["REMOVE_SERVER_DEFAULT_ROUTE"],false,"{remove_server_route_vpn_explain}");

  //  $form[]=$tpl->field_checkbox("EnableOpenVPNEndUserPage","{EnableOpenVPNEndUserPage}",$EnableOpenVPNEndUserPage,false,"{EnableOpenVPNEndUserPage_explain}");
  $form[]=$tpl->field_checkbox("OpenVPNClientCertNotRequired","{OpenVPNClientCertNotRequired}",$OpenVPNClientCertNotRequired,false,"{OpenVPNClientCertNotRequired_explain}");
   $form[]=$tpl->field_checkbox("OpenVPNAuthenticate","{enable_authentication}",$OpenVPNAuthenticate,false,"{enable_authentication_vpn_explain}");

    $form[]=$tpl->field_checkbox("OpenVPNRedirectGateway","{OpenVPNRedirectGateway}",
        $OpenVPNRedirectGateway,false,"{OpenVPNRedirectGateway_explain}");

    $form[]=$tpl->field_checkbox("OpenVPNBlockLocal","{OpenVPNBlockLocal}",
        $OpenVPNBlockLocal,false,"{OpenVPNBlockLocal_explain}");

   // $form[]=$tpl->field_checkbox("OpenVPNClientTOClient","{OpenVPNClientTOClient}",$OpenVPNClientTOClient,false,"{OpenVPNClientTOClient_explain}");



    $myform=$tpl->form_outside(null, $form,"{LOCAL_NETWORK} {SERVER_MODE_TUNE}","{apply}",section_js(),"AsVPNManager");
    echo $tpl->_ENGINE_parse_body($myform);
    return true;
}



function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$vpn=new openvpn();
	$nic=new networking();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();

	$OpenVpnPasswordCert=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVpnPasswordCert");
	$OpenVPNPing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNPing"));
	$OpenVPNPingRestart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNPingRestart"));
	if(intval($vpn->main_array["GLOBAL"]["LISTEN_PORT"])==0){$vpn->main_array["GLOBAL"]["LISTEN_PORT"]=1194;}
	if(intval($vpn->main_array["GLOBAL"]["PROXYPORT"])<10){$vpn->main_array["GLOBAL"]["PROXYPORT"]=8080;}
	if(trim($vpn->main_array["GLOBAL"]["LOCAL_BIND"])==null){$vpn->main_array["GLOBAL"]["LOCAL_BIND"]="eth0";}


	
	//doc fr http://lehmann.free.fr/openvpn/OpenVPNMan/OpenVPNMan-1.5.0.fr.1.0.html

/*	
	if($users->SQUID_INSTALLED){
		$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
		if($SQUIDEnable==0){
			$OpenVPNTransParentSquid=0;
			$form[]=$tpl->field_checkbox("OpenVPNTransParentSquid","{use_web_proxy_in_transparent}",
					$OpenVPNTransParentSquid,false,"{use_web_proxy_in_transparent_explain}",true);
			
		}else{
			$form[]=$tpl->field_checkbox("OpenVPNTransParentSquid","{use_web_proxy_in_transparent}",
					$OpenVPNTransParentSquid,false,"{use_web_proxy_in_transparent_explain}",false);
		}
	}
*/	
	
	

	
	

	

	
	


	

	



	
	$tpl->form_add_button("{reconfigure_service}", $tpl->button_openvpn_reconfigure(true));


    $myform=$tpl->form_outside("{general_settings}", @implode("\n", $form),"{openvpn_whatis}");
    $html[]="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='openvpn-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('openvpn-status','$page?openvpn-status=yes');</script>	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
						
}

function SAVE_SERVER():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");


    if(isset($_POST["OpenVPNBlockLocal"])) {
        if ($_POST["OpenVPNBlockLocal"] == 1) {
            $_POST["OpenVPNRedirectGateway"] = 1;
        }
    }

    $tpl->SAVE_POSTs();

    if(isset($_POST["openvpn_firewall_ports_allow"])) {
        $allowports = explode("\n", $_POST["openvpn_firewall_ports_allow"]);

        foreach ($allowports as $line) {
            $line = trim(strtolower($line));
            if ($line == null) {
                continue;
            }
            if (!is_numeric($line)) {
                echo "jserror:Only numeric values in firewall ports";
                die();
            };
            $line = $q->sqlite_escape_string2($line);
            $allowportarray[] = "('$line')";

        }
        $q->QUERY_SQL("DELETE FROM `vpn_global_fw_rules_allow`");
        if(count($allowportarray)>0){
            $q->QUERY_SQL("INSERT INTO `vpn_global_fw_rules_allow` (`ports`) VALUES ".@implode(",", $allowportarray));
            if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
        }
    }
    if(isset($_POST["openvpn_firewall_ports_deny"])) {
        $denyports = explode("\n", $_POST["openvpn_firewall_ports_deny"]);
        foreach ($denyports as $line) {
            $line = trim(strtolower($line));
            if ($line == null) {
                continue;
            }
            if (!is_numeric($line)) {
                echo "jserror:Only numeric values in firewall ports";
                die();
            };
            $line = $q->sqlite_escape_string2($line);
            $denyportarray[] = "('$line')";

        }

        $q->QUERY_SQL("DELETE FROM `vpn_global_fw_rules_deny`");
        if (count($denyportarray) > 0) {
            $q->QUERY_SQL("INSERT INTO `vpn_global_fw_rules_deny` (`ports`) VALUES " . @implode(",", $denyportarray));
            if (!$q->ok) {
                echo $tpl->post_error($q->mysql_error);
                return false;
            }
        }
    }

	$ini=new Bs_IniHandler();
	$ini->loadString($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaOpenVPNSettings"));
	reset($_POST);foreach ($_POST as $key=>$val){
		$ini->_params["GLOBAL"][$key]=url_decode_special_tool($val);
	}
	$sock->SaveConfigFile($ini->toString(), "ArticaOpenVPNSettings");


    $OpenVPNWizard=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNWizard")));
	foreach ($_POST as $num=>$ligne){
		$ligne=url_decode_special_tool($ligne);
		$OpenVPNWizard[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($OpenVPNWizard)), "OpenVPNWizard");
    $sock->REST_API("/openvpn/service/restart");

	return true;
}

function Save_gen(){
	$sock=new sockets();
	$sock->SET_INFO("SnortRulesCode", $_POST["SnortRulesCode"]);
	$sock->SET_INFO("SuricataFirewallPurges", $_POST["SuricataFirewallPurges"]);
	$sock->SET_INFO("SuricataPurges", $_POST["SuricataPurges"]);	
	
}
function buildDefaults():bool{
    if(!is_file("/etc/artica-postfix/settings/Daemons/OpenVPNInternalFragmentationAlgorithm")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNInternalFragmentationAlgorithm", 1);
    }
    if(!is_file("/etc/artica-postfix/settings/Daemons/OpenVPNPing")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNPing", 10);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/OpenVPNPingRestart")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNPingRestart", 120);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/OpenVPNFragments")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNFragments", 0);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/OpenVPNUseLZO")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNUseLZO", 1);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/OpenVPNVerboseMode")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNVerboseMode", 3);}
    if(!is_file("/etc/artica-postfix/settings/Daemons/OpenVPNRedirectGateway")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNRedirectGateway", 1);}
    return true;
}