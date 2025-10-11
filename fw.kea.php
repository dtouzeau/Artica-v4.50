<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_POST["configfile"])){config_file_save();exit;}
if(isset($_GET["kea-status-top"])){top_status();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["Params"])){Params_js();exit;}
if(isset($_GET["Params-popup"])){Params_Popup();exit;}
if(isset($_POST["KeaDebug"])){Params_Save();exit;}
if(isset($_GET["dhcprelay-new-js"])){dhcp_relay_new_js();exit;}
if(isset($_GET["dhcprelay-new-popup"])){dhcp_relay_new_popup();exit;}
if(isset($_POST["DHCPRelayNew"])){dhcp_relay_new_save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KEA_VERSION");

    $html=$tpl->page_header("{APP_KEA_DHCPD4} v$DHCPDVersion","fa fa-gauge-high","{APP_KEA_DHCPD4_TEXT}","$page?table=yes","kea-status",
        "progress-kea-restart",false,"table-loader-kea-service");
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_KEA_DHCPD4} v$DHCPDVersion",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

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

function top_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dhcpd_free=null;

    $KeaLeasesNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KeaLeasesNumber"));
    $DHCPD_COUNT_OF_QUERIES=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOFDHCPLast"));
    $dhcpd_leases=FormatNumber($KeaLeasesNumber);



    $html[]="
    <div class=\"col-lg-4\">
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fa fa-desktop fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {computers_number} </span>
	<h2 class=\"font-bold\">$DHCPD_COUNT_OF_QUERIES</h2>
	</div>
	</div>
	</div>
	</div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	
	<!-- ------------------------------------------------------------------------------------------------- -->
	<div class=\"col-lg-4\">
	    <div class=\"widget style1 navy-bg\">
	        <div class=\"row\">
	            <div class=\"col-xs-4\">
	                <i class=\"fas fa-laptop fa-5x\"></i>
	            </div>
	            <div class=\"col-xs-8 text-right\">
	                <span> {leases} </span>
	                <h2 class=\"font-bold\">$dhcpd_leases</h2>
	            </div>
	        </div>
	    </div>
    </div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	";

    if($dhcpd_free<>null){
        $html[]="	<div class=\"col-lg-4\">
	    <div class=\"widget style1 navy-bg\">
	        <div class=\"row\">
	            <div class=\"col-xs-4\">
	                <i class=\"fas fa-pause fa-5x\"></i>
	            </div>
	            <div class=\"col-xs-8 text-right\">
	                <span> {free} IP</span>
	                <h2 class=\"font-bold\">$dhcpd_free</h2>
	            </div>
	        </div>
	    </div>
    </div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	";

    }
    echo $tpl->_ENGINE_parse_body($html);
}
function table():bool{
	$tpl=new template_admin();
    $page=CurrentPageName();


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:220px;vertical-align:top'><div id='dhcp-kea-service'></div></td>";
    $html[]="<td style='width:78%;vertical-align:top'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr><td>";
    $html[]="<div id='kea-status-top'></div>";
    $html[]="</td></tr>";
    $html[]="<tr><td>";
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log","progress-dhcp-restart",
            "LoadAjax('kea-status','$page?table=yes')"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
    }




     $jsReconFigure=$tpl->framework_buildjs(
         "/kea/dhcp/reconfigure","kea.service.progress",
         "kea.service.log","progress-kea-restart","","","","ASDCHPAdmin");


    $topbuttons[] = array($jsReconFigure, ico_refresh, "{reconfigure}");
    $dhcpd_leases=$tpl->FormatNumber(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KeaLeasesNumber")));
    $topbuttons[] = array("Loadjs('fw.kea.leases.php')", ico_computer, "{leases} $dhcpd_leases");
    $topbuttons[] = array("Loadjs('$page?dhcprelay-new-js=yes')",ico_sensor,"{new_dhcp_relay_rule}");
    

    $DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KEA_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_KEA_DHCPD4} v$DHCPDVersion";
    $TINY_ARRAY["ICO"]="fa fa-gauge-high";
    $TINY_ARRAY["EXPL"]="{APP_KEA_DHCPD4_TEXT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $html[]="<div id='kea-lists' style='margin-top:10px'></div>";
    $html[]="</td></tr>";
    $html[]="</table>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]=$tpl->RefreshInterval_js("dhcp-kea-service","$page","status=yes");
    $html[]="LoadAjax('kea-lists','$page?interfaces=yes');";
    $html[]="</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function Params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{parameters}","$page?Params-popup=yes");
}
function Params_Popup():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $KeaDebug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KeaDebug"));
    for ($i = 0; $i < 100; $i++) {
        $ArrayDebug[$i] = $i;
    }
    $form[]=$tpl->field_array_hash($ArrayDebug,"KeaDebug","{debug}",$KeaDebug);
    $jsafter=$tpl->framework_buildjs(
        "/kea/dhcp/reload","kea.service.progress",
        "kea.service.log","progress-kea-restart");

    echo $tpl->form_outside("",$form,"","{apply}",
        "LoadAjax('kea-lists','$page?interfaces=yes');dialogInstance1.close();$jsafter","ASDCHPAdmin");

    return true;

}
function Params_Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}

function interfaces(){

    $net=new networking();
    $interfaces=$net->Local_interfaces();
    $tpl=new template_admin();
    $page=CurrentPageName();

    $tpl->table_form_field_js("Loadjs('$page?Params=yes')","ASDCHPAdmin");
    $KeaDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KeaDebug"));
    if($KeaDebug==0) {
        $tpl->table_form_field_bool("{debug}", $KeaDebug, ico_bug);
    }else{
        $tpl->table_form_field_bool("{debug}",1,ico_bug);
    }
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
                    $interfaceR = "[".@implode("|",$tb)."]:";
                }
                $tpl->table_form_field_text("$rulename","$interfaceR $dhcpd->subnet/$dhcpd->netmask - $dhcpd->range1 - $dhcpd->range2",ico_nic);
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
            $tpl->table_form_field_text("$nic->NICNAME", "{APP_DHCP_RELAY} (Circuit: $CircuitID) <i class='$ico_fl'></i> $DHCPServers", ico_nic);
            continue;
        }

        $tpl->table_form_field_text("$nic->NICNAME","$dhcpd->subnet/$dhcpd->netmask - $dhcpd->range1 - $dhcpd->range2",ico_nic);
        $c++;


    }

    if($c==0){
        echo $tpl->div_error("{err_dhcp_nocard}");
    }
    echo "<div style='padding:15px'>";
    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    echo "</div>";



}

function dhcp_save():bool{
	$sock=new sockets();
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return false;}
	$IPClass=new IP();
	
	$dhcp=new dhcpd(0,1);
	$sock=new sockets();
	
	$sock->SET_INFO('EnableDHCPUseHostnameOnFixed',$_POST["EnableDHCPUseHostnameOnFixed"]);
	$sock->SET_INFO("IncludeDHCPLdapDatabase", $_POST["IncludeDHCPLdapDatabase"]);

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
	
	$dhcp->EnableArticaAsDNSFirst=$_POST["EnableArticaAsDNSFirst"];
	$dhcp->do_no_verify_range=$_POST["do_no_verify_range"];
	
	$MASK=$IPClass->maskTocdir($dhcp->subnet, $dhcp->netmask);
	
	if(!$IPClass->isInRange($dhcp->range1, $MASK)){
		echo "$dhcp->subnet/$dhcp->netmask = $MASK\n$dhcp->range1 invalid for $MASK\n";
		return false;
	}
	if(!$IPClass->isInRange($dhcp->range2, $MASK)){
	echo "$dhcp->subnet/$dhcp->netmask = $MASK\n$dhcp->range1 invalid for $MASK\n";
	return false;
	}
	if($dhcp->ddns_domainname==null){$dhcp->ddns_domainname="home.lan";}
	$dhcp->Save();
    return true;
}

function dhcphelper_status():string{
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $EnableDHCPHelper=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPHelper"));
    if($EnableDHCPHelper==0){return "";}
    $data = $sock->REST_API("/dhcphelper/status");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return  $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));

    }
    if(!$json->Status){
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));

    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($ini, "APP_DHCP_RELAY");
}
function status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();

    echo dhcphelper_status();


    $data = $sock->REST_API("/kea/status");

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

    $DHCP4_PARSER_FAIL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCP4_PARSER_FAIL");
    if(strlen($DHCP4_PARSER_FAIL)>5){
        if(preg_match("#DHCP4_PARSER_FAIL\s+(.+?)\(#",$DHCP4_PARSER_FAIL ,$re)){$DHCP4_PARSER_FAIL =$re[1];}
        $DHCP4_PARSER_FAIL=str_replace("failed to create or run parser for configuration element","",$DHCP4_PARSER_FAIL);
        $DHCP4_PARSER_FAIL=$tpl->div_error($DHCP4_PARSER_FAIL);
        echo $tpl->_ENGINE_parse_body($DHCP4_PARSER_FAIL);
    }



    $APP_KEA_DHCPD4=$tpl->framework_buildjs(
        "/kea/dhcp/restart","kea.service.progress",
        "kea.service.log","progress-kea-restart");

    $APP_KEA_DDNS=$tpl->framework_buildjs(
        "/kea/ddns/restart","kea.service.progress",
        "kea.service.log","progress-kea-restart");

    $APP_KEA_CTRL_AGENT=$tpl->framework_buildjs(
        "/kea/agent/restart","kea.service.progress",
        "kea.service.log","progress-kea-restart");

	echo $tpl->SERVICE_STATUS($ini, "APP_KEA_DHCPD4",$APP_KEA_DHCPD4);
    echo $tpl->SERVICE_STATUS($ini, "APP_KEA_DDNS",$APP_KEA_DDNS);
    echo $tpl->SERVICE_STATUS($ini, "APP_KEA_CTRL_AGENT",$APP_KEA_CTRL_AGENT);



    echo "<script>LoadAjaxSilent('kea-status-top','$page?kea-status-top=yes')</script>";
	
	
}

function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog6("{APP_DHCP} >> {config_file}", "$page?config-file-popup=yes",1000);

}
function config_file_save(){

	$data=url_decode_special_tool($_POST["configfile"]);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config", $data);
}

function config_file_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("dhcpd.php?config-file=yes");
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config");
	$form[]=$tpl->field_textareacode("configfile", null, $data);

    $jsafter=$tpl->framework_buildjs("/dhcpd/service/restart",
        "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");

	echo $tpl->form_outside("{config_file}", @implode("", $form),null,"{apply}",$jsafter,"AsSystemAdministrator");

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}