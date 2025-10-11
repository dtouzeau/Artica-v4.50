<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["SquidReconfigureAtBoot"])){saveall();exit;}
if(isset($_GET["connected-ports"])){connected_port_section();exit;}
if(isset($_GET["connected-ports-list"])){connected_port_list();exit;}
if(isset($_POST["SaveConnectedPort"])){connected_port_save();exit;}
if(isset($_POST["SquidICPPort"])){communications_ports_save();exit;}
if(isset($_GET["port-js"])){connected_port_js();exit;}
if(isset($_GET["port-popup"])){connected_port_popup();exit;}
if(isset($_GET["port-popup-start"])){connected_port_popup_start();exit;}
if(isset($_GET["port-delete-js"])){connected_port_delete_js();exit;}
if(isset($_POST["port-delete"])){connected_port_delete();exit;}
if(isset($_GET["transparent-ports"])){transparent_ports();exit;}
if(isset($_GET["communications-ports"])){communications_ports();exit;}
if(isset($_GET["transparent-wbl-js"])){transparent_ports_exclude_js();exit;}
if(isset($_GET["transparent-wbl"])){transparent_ports_exclude_popup();exit;}
if(isset($_POST["proxy_ports_wbl"])){transparent_ports_exclude_save();exit;}
if(isset($_GET["troubleshooting"])){troubleshooting_ports();exit;}
if(isset($_GET["SquidMgrOutGoingInterface-popup"])){SquidMgrOutGoingInterface_popup();exit;}
if(isset($_GET["SquidMgrOutGoingInterface-js"])){SquidMgrOutGoingInterface_js();exit;}
if(isset($_POST["SquidMgrOutGoingInterface"])){SquidMgrOutGoingInterface_save();exit;}
page();

function saveall(){
    $tpl    =new template_admin();
    $tpl->SAVE_POSTs();
}
function SquidMgrOutGoingInterface_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidMgrListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));

    return $tpl->js_dialog("127.0.0.1:$SquidMgrListenPort >> {outgoing_interface}", "$page?SquidMgrOutGoingInterface-popup=yes");
}
function SquidMgrOutGoingInterface_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $SquidMgrOutGoingInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrOutGoingInterface");

    $form[]=$tpl->field_interfaces("SquidMgrOutGoingInterface","{outgoing_interface}",$SquidMgrOutGoingInterface);

    $jsrestart=$tpl->framework_buildjs("/proxy/ports/reconfig",
        "squid.listenport.progress","ssquid.listenport.log",
        "progress-squid-ports-restart");

    $jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');BootstrapDialog1.close();$jsrestart";

    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function SquidMgrOutGoingInterface_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks("Change listen interface for working proxy port 127.0.0.1 outgoing address");

}
function connected_port_js(){
	$ID=intval($_GET["port-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$title=$tpl->javascript_parse_text("{new_port}");
	include_once(dirname(__FILE__)."/ressources/class.squid.ports.patch.inc");
	Patch_table_squid_ports();
	
	
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT ipaddr,port,WCCP FROM proxy_ports WHERE ID=$ID");
		if(intval($ligne["WCCP"])==1){$wccp="WCCP:";}
		$title="{listen_port}: {$ligne["ipaddr"]}:{$ligne["port"]}";
	
	}
	
	$tpl->js_dialog($title, "$page?port-popup-start=$ID");
	
}

function transparent_ports(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$EnableSquidTransparent=0;
	$EnableSquidTransparentSSL=0;
	$SquidTransparentSSLCert=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentSSLCert"));
	$SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
	$EnableSquidTproxy=0;

	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND transparent=1 AND UseSSL=0";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne){$port[]=$ligne["port"];}
	if(count($port)>0){$EnableSquidTransparent=1;}
	
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND transparent=1 AND UseSSL=1";
	$results = $q->QUERY_SQL($sql);
	$sport=array();
	foreach($results as $index=>$ligne){$sport[]=$ligne["port"];}
	if(count($sport)>0){$EnableSquidTransparentSSL=1;}
	
	
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND TProxy=1 AND UseSSL=0";
	$results = $q->QUERY_SQL($sql);
	$port=array();
	foreach($results as $index=>$ligne){$port[]=$ligne["port"];}
	if(count($port)>0){
		$EnableSquidTransparent=1;
		$EnableSquidTproxy=1;
		$Tports=@implode(", ", $port);
	}
	
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND TProxy=1 AND UseSSL=1";
	$results = $q->QUERY_SQL($sql);
	$sport=array();
	foreach($results as $index=>$ligne){$sport[]=$ligne["port"];}
	if(count($sport)>0){$EnableSquidTransparentSSL=1;}
	
	$SquidTransparentInterfaceIN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceIN"));
	$SquidTransparentInterfaceOUT=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceOUT"));
	$SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
	if($SquidSSLUrgency==1){echo $tpl->FATAL_ERROR_SHOW_128("{proxy_in_ssl_emergency_mode}");}

	$form[]=$tpl->field_section("{router_mode}","{explain_http_proxy_router_mode}");
	$form[]=$tpl->field_checkbox("SquidInRouterMode","{router_mode}",$SquidInRouterMode);
	$form[]=$tpl->field_section("{enable_this_proxy_transparent}","{explain_http_proxy_transparent}");
	$form[]=$tpl->field_checkbox("EnableSquidTransparent","{enable_this_proxy_transparent}",$EnableSquidTransparent,false);
	$form[]=$tpl->field_checkbox("EnableSquidTransparentSSL","{catch_ssl_requests}",$EnableSquidTransparentSSL,false,"{transparent_ssl_explain}");
	$form[]=$tpl->field_link("{whitelisted_src_networks}",null, "Loadjs('fw.proxy.ports.php?transparent-wbl-js=yes&include=0')");
	$form[]=$tpl->field_link("{whitelisted_destination_networks}",null, "Loadjs('$page?transparent-wbl-js=yes&include=1')");
	
	
	//$ex[1]='{whitelisted_destination_networks}';
	//$ex[0]="{whitelisted_src_networks}";
	//$title="<strong style=font-size:30px>".$tpl->javascript_parse_text("{listen_ports}: {$ex[$_GET["include"]]}")."</strong>";
	
	
	

	
	
	$form[]=$tpl->field_section("{tproxy_method}","{tproxy_method_explain}");
	$form[]=$tpl->field_checkbox("EnableSquidTproxy","{enable_this_proxy_transparent} (Tproxy)",$EnableSquidTproxy,false);
	
	
	$form[]=$tpl->field_section("{interfaces}","{tproxy_interfaces_explain}");
	$form[]=$tpl->field_interfaces("SquidTransparentInterfaceIN", "{listen_interface}", $SquidTransparentInterfaceIN);
	$form[]=$tpl->field_interfaces("SquidTransparentInterfaceOUT", "{outgoing_interface}", $SquidTransparentInterfaceOUT,"{outgoing_interface_explain_proxy}");
	
	
	
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	$form[]=$tpl->field_section("{certificate}","{proxy_certificate_explain}");
	$form[]=$tpl->field_array_hash($sslcertificates, "SquidTransparentSSLCert", "{use_certificate_from_certificate_center}", $SquidTransparentSSLCert);
	$html[]=$tpl->form_outside("{transparent_method}", @implode("\n", $form),"{transparent_mode_limitations}","{apply}","Loadjs('fw.proxy.ports.apply.php');","AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function transparent_ports_exclude_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ex[1]='{whitelisted_destination_networks}';
	$ex[0]="{whitelisted_src_networks}";
	$title=$ex[$_GET["include"]];
	$tpl->js_dialog1($title, "$page?transparent-wbl=yes&include={$_GET["include"]}&portid={$_GET["portid"]}");
}
function transparent_ports_exclude_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ex[1]='{whitelisted_destination_networks}';
	$ex[0]="{whitelisted_src_networks}";
	$title=$ex[$_GET["include"]];
	$form[]=$tpl->field_hidden("portid",$_GET["portid"]);
	$form[]=$tpl->field_hidden("include", $_GET["include"]);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT pattern  FROM `proxy_ports_wbl` WHERE include={$_GET["include"]} AND portid={$_GET["portid"]}";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	$f=array();
	if(count($results)>0){
		foreach ($results as $index=>$ligne){
			$f[]=$ligne["pattern"];
		}
	}

	if($_GET["include"]==1) {
            if (count($f) == 0) {
                $f[] = "10.0.0.0/8";
                $f[] = "192.168.0.0/16";
                $f[] = "172.16.0.0/12";
            }
    }

    $jsrestart=$tpl->framework_buildjs("/firewall/proxy/ports",
        "squid.transparent.build","squid.transparent.build.log",
    "fiw-restart","dialogInstance1.close();LoadAjax('proxy-transparent-ports','fw.proxy.transparent.php?table=yes')");

	
	$form[]=$tpl->field_textareacode("proxy_ports_wbl", "{networks}", @implode("\n", $f));
	$html[]="<div id='fiw-restart' style='width:99%;margin-bottom:10px;margin-top:5px'></div>";
	$html[]=$tpl->form_outside("$title", @implode("\n", $form),"{subnet_simple_explain}","{apply}",$jsrestart,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function transparent_ports_exclude_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$include=$_POST["include"];
	$portid=$_POST["portid"];
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$q->QUERY_SQL("DELETE FROM proxy_ports_wbl WHERE include=$include AND portid=$portid");
	$f=explode("\n",$_POST["proxy_ports_wbl"]);
	$n=array();
	$ip=new IP();
	$ERR=array();
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if($ip->isValid($line)){
			$n[]="($include,$portid,'$line')";
			continue;
		}
		if($ip->IsACDIR($line)){
			$n[]="($include,$portid,'$line')";
			continue;
		}
		if($ip->IsARange($line)){
			$n[]="($include,$portid,'$line')";
			continue;			
		}
		$ERR[]=$line;
	}
	
	if(count($n)>0){
		$q->QUERY_SQL("INSERT INTO proxy_ports_wbl (include,portid,pattern) VALUES ".@implode(",", $n));
		if(!$q->ok){echo $q->mysql_error_html();}
	}
	if(count($ERR)>0){echo "$ERR Errors " .@implode("\n", $ERR)."\n";}
}

function communications_ports(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$SquidICPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidICPPort"));
	$SquidHTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidICPPort"));
	$udp_incoming_address=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("udp_incoming_address"));
	$udp_outgoing_address=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("udp_outgoing_address"));
	
	$SNMPDCommunity=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDCommunity");
	$SquidSNMPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSNMPPort"));
	if($SquidSNMPPort==0){$SquidSNMPPort=3401;}
	if($SNMPDCommunity==null){$SNMPDCommunity="public";}
	$t=time();
	$SNMPDNetwork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDNetwork");
	if($SNMPDNetwork==null){$SNMPDNetwork="default";}
    $EnableProxyInSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyInSNMPD"));
	
	
	
	$form[]=$tpl->field_numeric("SquidICPPort","{icp_port}",$SquidICPPort,"{icp_port_explain}");
	$form[]=$tpl->field_numeric("SquidHTCPPort","{htcp_port}",$SquidHTCPPort,"{htcp_port_explain}");
	
	$form[]=$tpl->field_section("{UDP_PROTOCOL}","{udp_incoming_outgoing_address}");
	$form[]=$tpl->field_interfaces("udp_incoming_address", "{incoming_interface}", $udp_incoming_address);
	$form[]=$tpl->field_interfaces("udp_outgoing_address", "{outgoing_interface}", $udp_outgoing_address);

	$example="snmpwalk -v 2c -c $SNMPDCommunity  {$_SERVER["SERVER_ADDR"]}:$SquidSNMPPort .1.3.6.1.4.1.3495.1";
    if($EnableProxyInSNMPD==1){
        $example="snmpwalk -v 2c -c $SNMPDCommunity {$_SERVER["SERVER_ADDR"]} .1.3.6.1.4.1.3495.1";
    }
	
	$form[]=$tpl->field_section("{monitor_proxy_service} (SNMP)","{monitor_proxy_service_snmpd_explain}<br><strong>$example</strong>");
	$form[]=$tpl->field_numeric("SquidSNMPPort","{listen_port} (SNMPv2c)",$SquidSNMPPort,null);
    if($EnableProxyInSNMPD==0){$form[]=$tpl->field_text("SNMPDCommunity","{snmp_community}",$SNMPDCommunity,null);}
    if($EnableProxyInSNMPD==0){$form[]=$tpl->field_ipaddr("SNMPDNetwork", "{remote_snmp_console_ip}", $SNMPDNetwork);}


    // squid.articarest.nohup /proxy/general/nohup/restart

    $jsrestart=$tpl->framework_buildjs("/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log","progress-squid-ports-restart","LoadAjax('proxy-transparent-ports-status','$page?status=yes');RefreshSecondInterfaceBarrs()");

	$html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSquidAdministrator");

    $TINY_ARRAY["TITLE"]="{communications_ports}";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}




function connected_port_delete_js(){
	$ID=intval($_GET["port-delete-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_ports WHERE ID=$ID");
	$title="{listen_port}:  {$ligne["nic"]}:{$ligne["port"]}";
	$jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');";
	$tpl->js_confirm_delete($title, "port-delete", $ID,$jsafter);
	
}
function connected_port_delete(){
	$ID=intval($_POST["port-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$q->QUERY_SQL("DELETE FROM proxy_ports WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM squid_balancers WHERE ID=$ID");
	CheckPointers();
}
function connected_port_popup_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["port-popup-start"]);
    $html[]="<div id='connected-port-popup-$ID'></div>";
    $html[]="<script>LoadAjax('connected-port-popup-$ID','$page?port-popup=$ID');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function connected_port_popup():bool{
	$ID=intval($_GET["port-popup"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$btname="{add}";
	$title=$tpl->javascript_parse_text("{new_port}");
	$jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');BootstrapDialog1.close();";


	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_ports WHERE ID=$ID");
		$title="{$ligne["nic"]}:{$ligne["port"]}";
		if($ligne["nic"]==null){$title="{listen_port}: {$ligne["port"]}";}
		$btname="{apply}";
		$jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');";
        $Params=unserialize($ligne["Params"]);
    }

    if(!isset($Params["tcpkeepalive"]["enabled"])){$Params["tcpkeepalive"]["enabled"]=0;}
	$ip=new networking();
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$array[null]        = "{all}";
	$array2[null]       = "{all}";
	$CountOfInterfaces  = 0;
	foreach ($interfaces as $eth){
		if(preg_match("#^(gre|dummy)#", $eth)){continue;}
		if($eth=="lo"){continue;}
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        $CountOfInterfaces++;
	}
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	if($ligne["ipaddr"]==null){$ligne["ipaddr"]="0.0.0.0";}
	if($ligne["port"]==0){$ligne["port"]=rand(1024,63000);}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}

    $qCert=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $results=$qCert->QUERY_SQL("SELECT ID,commonName,organizationName FROM subcertificates WHERE Certype='1' ORDER BY commonName ASC");
    $subcertificates=array();
    foreach ($results as $index=>$ligneCert) {
        $subcertificates[$ligneCert["ID"]]="{$ligneCert["commonName"]} ({$ligneCert["organizationName"]})";
    }
    $keepalive_class="label-primary";
    $keepalive_txt="{active2}";
    if($Params["tcpkeepalive"]["enabled"]==0){
        $keepalive_class="label-default";
        $keepalive_txt="{disabled}";
    }


	$form[] = $tpl->field_hidden("SaveConnectedPort",$ID);
	$form[] = $tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[] = $tpl->field_numeric("port","{listen_port}",$ligne["port"]);
    $form[] = $tpl->td_button("TCP Keepalive Timeout", "$keepalive_txt: {modify}", "Loadjs('fw.proxy.ports.tcpkeepalive.php?portid=$ID');",null,null,$keepalive_class);
	$form[] = $tpl->field_text("PortName", "{service_name2}",  $ligne["PortName"]);
	$form[] = $tpl->field_text("xnote", "{description}",  $ligne["xnote"]);
	$form[] = $tpl->field_checkbox("ProxyProtocol","{proxy_protocol}",$ligne["ProxyProtocol"],false,"{proxy_protocol_explain}");
	
	$form[]=$tpl->field_checkbox("NoAuth","{disable_authentication}",$ligne["NoAuth"]);
	$form[]=$tpl->field_checkbox("NoCache","{disable_cache}",$ligne["NoCache"]);
	$form[]=$tpl->field_checkbox("NoFilter","{disable_webfiltering}",$ligne["NoFilter"]);
    $form[]=$tpl->field_checkbox("AuthParentPort","{AuthParentPort}",$ligne["AuthParentPort"]);

    if($CountOfInterfaces>1) {
        $form[] = $tpl->field_interfaces("nic", "{listen_interface}", $ligne["nic"]);
        $form[] = $tpl->field_interfaces("outgoing_addr", "{forward_interface}", $ligne["outgoing_addr"]);
    }else{
        $tpl->field_hidden("nic",null);
        $tpl->field_hidden("outgoing_addr",null);
    }


	if(count($subcertificates)>0) {
        $subcertificates[0]="{none}";
        $form[] = $tpl->field_section("{secure_proxy}","{secure_proxy_explain}");
        $form[] = $tpl->field_checkbox("SSLPort", "{explicit_https_port}", $ligne["SSLPort"], false, "{explicit_https_port_explain}");
        $form[] = $tpl->field_checkbox("AuthSSL", "{authenticate_ssl_client}", $ligne["AuthSSL"], false, "{authenticate_ssl_client_explain}");
        $form[] = $tpl->field_array_hash($subcertificates, "subcertificate", "{server_certificate}",$ligne["subcertificate"]);
    }else{
        $ligne["SSLPort"]=0;
	    $tpl->field_hidden("SSLPort",0);
        $tpl->field_hidden("subcertificate",0);
        $tpl->field_hidden("AuthSSL",0);
    }

    if($ligne["SSLPort"]==0) {
        $form[] = $tpl->field_section("{activate_ssl_on_http_port}","{activate_ssl_on_http_port_explain}");
        $form[] = $tpl->field_checkbox("UseSSL", "{decrypt_ssl}", $ligne["UseSSL"], "sslcertificate", "{listen_port_ssl_explain}");
        $form[] = $tpl->field_array_hash($sslcertificates, "sslcertificate", "{use_certificate_from_certificate_center}", $ligne["sslcertificate"]);
        if($ID>0) {
            $form[] = $tpl->td_button("{create_certificate}", "{wizard}: {create_certificate}", "Loadjs('fw.proxy.ports.sslwizard.php?portid=$ID');");
        }
    }else{
        $tpl->field_hidden("UseSSL",1);
        $tpl->field_hidden("sslcertificate",null);
    }
	$security="AsSquidAdministrator";
	$html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}

function communications_ports_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	foreach ($_POST as $key=>$val){
		$sock->SET_INFO($key, $val);
		
	}
	
	
	
}

function connected_port_save(){
		$tpl=new template_admin();
		$tpl->CLEAN_POST_XSS();
		
		$ID=$_POST["SaveConnectedPort"];
		$ipaddr=$_POST["ipaddr"];
		$port=intval($_POST["port"]);
		$xnote=mysql_escape_string2($_POST["xnote"]);
		$PortName=mysql_escape_string2($_POST["PortName"]);
		$enabled=$_POST["enabled"];
		$transparent=intval($_POST["transparent"]);
		$TProxy=intval($_POST["TProxy"]);
		$Parent=intval($_POST["Parent"]);
		$outgoing_addr=$_POST["outgoing_addr"];
		$UseSSL=$_POST["UseSSL"];
		$sslcertificate=$_POST["sslcertificate"];
		$WCCP=intval($_POST["WCCP"]);
		$ICP=intval($_POST["ICP"]);
		$FTP=intval($_POST["FTP"]);
		$WANPROXY=intval($_POST["WANPROXY"]);
		$WANPROXY_PORT=intval($_POST["WANPROXY_PORT"]);
		$FTP_TRANSPARENT=intval($_POST["FTP_TRANSPARENT"]);
		$MIKROTIK_PORT=intval($_POST["MIKROTIK_PORT"]);
		$SOCKS=intval($_POST["SOCKS"]);
		$NoAuth=intval($_POST["NoAuth"]);
	    $SSLPort=intval($_POST["SSLPort"]);
		$NoCache=intval($_POST["NoCache"]);
		$NoFilter=intval($_POST["NoFilter"]);
		$subcertificate=intval($_POST["subcertificate"]);
        $AuthSSL=intval($_POST["AuthSSL"]);
        $AuthParentPort=intval($_POST["AuthParentPort"]);
	    $ProxyProtocol=intval($_POST["ProxyProtocol"]);
		$nic=$_POST["nic"];

		if($SSLPort==1){
            $UseSSL=1;
		    if($subcertificate==0){
		        echo "jserror:Please choose a Server certificate";
		        return;
            }
            $sslcertificate=null;
        }

		$SquidAllow80Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllow80Port"));
		$tpl=new templates();
		$SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
	
		if($port==3401){echo "3401, reserved port\n";return;}
		if($port==3978){echo "3978, reserved port\n";return;}
		if($port==3977){echo "3977, reserved port\n";return;}
		if($port==8889){echo "$port Internal Framework port not allowed!\n";return;}
		if($port==8888){echo "$port Internal Framework port not allowed!\n";return;}
		if($port==9000){echo "$port Artica Web Console port not allowed!\n";return;}
	
		if($port>65535){$port=65535;}
		if($WANPROXY_PORT>65635){$WANPROXY_PORT=65635;}
		if($MIKROTIK_PORT>65635){$MIKROTIK_PORT=65635;}
	
		if($MIKROTIK_PORT==1){
			$FTP=0;
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$Parent=0;
			$FTP_TRANSPARENT=0;
			$_POST["is_nat"]=0;
			if($nic==null){
				echo $tpl->javascript_parse_text("{mikrotik_error_interface}");
				return;
			}
		}
	
		if($FTP_TRANSPARENT==1){
			$transparent=0;$TProxy=0;$WCCP=0;$Parent=0;$ICP=0;$FTP=0;
			if(!is_file("/usr/sbin/frox")){
				echo $tpl->javascript_parse_text("{module_in_squid_not_installed}");
				return;
					
			}
	
			if($WANPROXY_PORT==0){
				echo $tpl->javascript_parse_text("{error} {missing_valuefor}: {proxy_port}");
				return;
			}
	
		}
	
	
	
		if($FTP==1){
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$Parent=0;
			$FTP_TRANSPARENT=0;
			$sslcertificate=null;
			$UseSSL=0;
			$nic=null;
			$outgoing_addr=null;
			$SquidAllow80Port=1;
			$_POST["is_nat"]=0;
			$SSLPort=0;
			$ICP=0;
		}
	
	
	
		if($SquidAllow80Port==0){
			if($port==80){
				echo "$port 80 HTTP port not allowed!\n";
				return;
			}
	
			if($port==21){
				echo "$port 21 FTP port not allowed!\n";
				return;
			}
            if($SSLPort==0) {
                if ($port == 443) {
                    echo "$port 443 SSL port not allowed!\n";
                    return;
                }
            }
		}
	
	
	
	
		if($port==$SquidMgrListenPort){
			echo "$port == Manager port $SquidMgrListenPort (not allowed!)\n";
			return;
	
		}
	
	
	
		if(intval($_POST["is_nat"])==1){
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$WANPROXY_PORT=0;
			$FTP_TRANSPARENT=0;
		}
	
		if($ICP==1){
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$Parent=0;
			$sslcertificate=null;
			$UseSSL=0;
			$nic=null;
			$outgoing_addr=null;
			$WANPROXY_PORT=0;
			$FTP_TRANSPARENT=0;
		}

		if($WCCP==1){
			$WANPROXY_PORT=0;
			if($nic==null){
				echo $tpl->javascript_parse_text("{wccp_error_interface}");
				return;
			}
	
		}
	
	
		$is_nat=intval($_POST["is_nat"]);
		$zMD5=md5(serialize($_POST));
		$sqladd="INSERT INTO proxy_ports (WANPROXY_PORT,WANPROXY,FTP,ICP,Parent,WCCP,is_nat,nic,ipaddr,port,xnote,enabled,transparent,TProxy,outgoing_addr,PortName,UseSSL,sslcertificate,NoAuth,MIKROTIK_PORT,FTP_TRANSPARENT,SOCKS,ProxyProtocol,NoCache,NoFilter,zMD5,AuthForced,AuthPort,NoHotspot,SSLPort,subcertificate,AuthSSL,AuthParentPort)
		VALUES ('$WANPROXY_PORT','$WANPROXY','$FTP','$ICP','$Parent','$WCCP','$is_nat','$nic','$ipaddr','$port','$xnote','$enabled','$transparent','$TProxy','$outgoing_addr','$PortName','$UseSSL','$sslcertificate',$NoAuth,'$MIKROTIK_PORT','$FTP_TRANSPARENT','$SOCKS','$ProxyProtocol',$NoCache,$NoFilter,'$zMD5',0,0,0,$SSLPort,'$subcertificate',$AuthSSL,$AuthParentPort)";
	
	
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

    if(!$q->FIELD_EXISTS("proxy_ports","NoHotspot")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD NoHotspot INTEGER NOT NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("proxy_ports","AuthParentPort")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD AuthParentPort INTEGER NOT NULL DEFAULT '0'");
    }

    if(!$q->FIELD_EXISTS("proxy_ports","SSLPort")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD SSLPort INTEGER NOT NULL DEFAULT '0'");
    }

    if(!$q->FIELD_EXISTS("proxy_ports","subcertificate")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD subcertificate INTEGER NOT NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("proxy_ports","AuthSSL")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD AuthSSL INTEGER NOT NULL DEFAULT '0'");
    }



	
		if($ID==0){
			$sql="SELECT ID,PortName FROM proxy_ports WHERE port='$port' AND nic='$nic'";
			$ligne=$q->mysqli_fetch_array($sql);
			$ID=intval($ligne["ID"]);
	
			if($ID>0){
				$PortName=$ligne["PortName"];
				echo $tpl->javascript_parse_text("$port: {error_port_already_usedby}:$PortName");
				return;
			}
	
		}
	
	
		$sqledit="UPDATE proxy_ports SET
		WANPROXY_PORT='$WANPROXY_PORT',
		FTP_TRANSPARENT='$FTP_TRANSPARENT',
		WANPROXY='$WANPROXY',
		FTP='$FTP',
		ICP='$ICP',
		Parent='$Parent',
		TProxy='$TProxy',
		WCCP='$WCCP',
		is_nat='$is_nat',
		transparent='$transparent',
		ipaddr='$ipaddr',
		port='$port',
		nic='$nic',
		xnote='$xnote',
		MIKROTIK_PORT='$MIKROTIK_PORT',
		enabled='$enabled',nic='$nic',`is_nat`='$is_nat',
		outgoing_addr='$outgoing_addr',
		PortName='$PortName',
		SOCKS='$SOCKS',
		UseSSL='$UseSSL',
		SSLPort='$SSLPort',
		AuthSSL='$AuthSSL',
		subcertificate='$subcertificate',
		sslcertificate='$sslcertificate',
		ProxyProtocol='$ProxyProtocol',
        AuthParentPort ='$AuthParentPort',                       
		`NoAuth`='$NoAuth', `NoCache`='$NoCache', `NoFilter`='$NoFilter',`NoHotspot`='0'
		WHERE ID=$ID";
	
		$InfluxAdminPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminPort"));
		$EnableUnifiController=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminPort"));
		if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
	
		if($port==$InfluxAdminPort){echo "Failed, reserved port Influx Admin Port\n";return;}
		if($port==9900){echo "Failed, reserved port WanProxy Monitor\n";return;}
		if($port==5432){echo "Failed, reserved port PostgreSQL query port\n";return;}
		if($EnableUnifiController==1){if($port==8088){echo "Failed, reserved port Unifi HTTP Port\n";return;}}
		if($port==13298){echo "Failed, reserved port Proxy NAT backend port\n";return;}
		if($SquidAllow80Port==0){if($port==21){echo "Failed, reserved port Local FTP service port\n";return;}}
		if($port==25){echo "Failed, reserved port Local SMTP service port\n";return;}
		if($port==31337){echo "Failed, reserved port Local RedSocks\n";return;}
	
	
	
		if($ID>0){$q->QUERY_SQL($sqledit);}else{$q->QUERY_SQL($sqladd);}
		if(!$q->ok){echo $q->mysql_error;}else{echo $q->last_id;}
		CheckPointers();
	
	
	}
	function CheckPointers(){
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND is_nat=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){
		    $sock->SET_INFO("EnableTransparent27", 1);
		}else{
		      $sock->SET_INFO("EnableTransparent27", 0);
		}
	
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND WCCP=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){$sock->SET_INFO("SquidWCCPEnabled", 1);}else{$sock->SET_INFO("SquidWCCPEnabled", 0); }
	
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND FTP=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){$sock->SET_INFO("ServiceFTPEnabled", 1);}else{$sock->SET_INFO("ServiceFTPEnabled", 0); }
	
		$sock=new sockets();
		$sock->getFrameWork("ftp-proxy.php?reconfigure-silent=yes");
	
	}	

function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    
    $html=$tpl->page_header("{listen_ports}","fad fa-plug",
        "{listen_ports_v4_explain}","$page?tabs=yes","proxy-ports","progress-squid-ports-restart");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/privs");
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{connected_ports}"]="$page?connected-ports=yes";
	$array["{transparent_ports}"]="fw.proxy.transparent.php";
    $array["{remote_ports}"]="fw.proxy.general.php?SafePorts=yes";
	$array["{communications_ports}"]="$page?communications-ports=yes";
    $array["{troubleshooting}"]="$page?troubleshooting=yes";

	echo $tpl->tabs_default($array);
}
function connected_port_section(){
	$page=CurrentPageName();
	echo "<div id='table-connected-proxy-ports'></div><script>LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');</script>";
}
function connected_port_list():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $TRCLASS = null;
    $missing_certificate = "&nbsp;<span class='label label-danger'>{missing_certificate}</span>";
    $invalid_certificate = "&nbsp;<span class='label label-danger'>{invalid_certificate}</span>";


    $SquidSSLUrgency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
    $HaClusterTransParentMode = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $HaClusterTproxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTproxy"));
    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

    $jsrestart = "Loadjs('fw.proxy.apply.ports.php');";
    
    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";

    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{tcp_address}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{listen_port}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>HTTPS</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{cache}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>AUTH.</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{filter}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</th>");
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";

    $SquidMgrListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
    $EnableUfdbGuard = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));

    if ($TRCLASS == "footable-odd") {
        $TRCLASS = null;
    } else {
        $TRCLASS = "footable-odd";
    }
    $text_class = null;
    $tooltip_error = null;
    $AsFilter = true;
    $users = new usersMenus();
    $sock = new sockets();

    if ($EnableUfdbGuard == 0) {
        $AsFilter = false;
    }
    if (!$users->APP_UFDBGUARD_INSTALLED) {
        $AsFilter = false;
    }

    $status = "<span class='label label-primary'>OK</span>";

    $fp = @fsockopen("127.0.0.1", $SquidMgrListenPort, $errno, $errstr, 1);
    if (!$fp) {
        $status = "<span class='label label-danger'>{failed}</span>";
        $tooltip_error = "&nbsp;<span class='label label-danger'>$errstr</span>";
    }

    $SquidMgrOutGoingInterface_text="{all_interfaces}";
    $SquidMgrOutGoingInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrOutGoingInterface");
    if(strlen($SquidMgrOutGoingInterface)>3){
        $nic=new system_nic($SquidMgrOutGoingInterface);
        $SquidMgrOutGoingInterface_text=$nic->NICNAME. "($SquidMgrOutGoingInterface)";
    }

    $SquidMgrOutGoingInterface_text=$tpl->td_href($SquidMgrOutGoingInterface_text,"","Loadjs('$page?SquidMgrOutGoingInterface-js=yes')");

    $html[] = "<tr class='$TRCLASS'>";
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$status</td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">127.0.0.1</td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\"><strong>$SquidMgrListenPort</strong> " . $tpl->javascript_parse_text("{internal_port}({used_by_artica})$tooltip_error") . "<br><i>{outgoing_interface}:$SquidMgrOutGoingInterface_text</i></td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\"><i class='fas fa-check'></i></td>");
    $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\">&nbsp;</td>");
    $html[] = "</tr>";

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $HaClusterClient = 0;
    }

    if ($HaClusterClient == 1) {
        if ($HaClusterTransParentMode == 1) {
            if ($HaClusterTproxy > 0) {

                $status = "<span class='label label-primary'>OK</span>";

                $fp = @fsockopen("127.0.0.1", $HaClusterTproxy, $errno, $errstr, 1);
                if (!$fp) {
                    $status = "<span class='label label-danger'>{failed}</span>";
                    $tooltip_error = "&nbsp;<span class='label label-danger'>$errstr</span>";
                }

                $html[] = "<tr class='$TRCLASS'>";
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$status</td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">0.0.0.0</td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\"><strong>$HaClusterTproxy</strong> " . $tpl->javascript_parse_text("HaCluster {transparent}") . "<br><strong style='color:#0612C6'>" . $tpl->javascript_parse_text("{proxy_protocol}$tooltip_error") . "</strong></td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\"><i class='fas fa-check'></i></td>");
                $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
                $html[] = "</tr>";
            }
        }
    }
    $sql = "SELECT * FROM proxy_ports";
    $results = $q->QUERY_SQL($sql);
    $ALL_TEXT = $tpl->_ENGINE_parse_body("{all_interfaces}");

    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $PortName = $tpl->javascript_parse_text($ligne["PortName"]);
        $certificateEX = null;
        $status = "<span class='label label-primary'>OK</span>";
        $port = $ligne["port"];
        $AuthParentPort = intval($ligne["AuthParentPort"]);
        $nic = $ligne["nic"];
        $AuthParentIco = null;
        $tooltip_error = null;
        $ssl_ico = null;
        $ID = $ligne["ID"];
        $SSLPort = intval($ligne["SSLPort"]);
        $deCrypt = intval($ligne["UseSSL"]);
        $subcertificate = intval($ligne["subcertificate"]);
        if ($ligne["enabled"] == 0) {
            $status = "<span class='label label'>{disabled}</span>";
        }

        if ($nic == null) {
            $ipaddr = null;
            if ($ligne["enabled"] == 1) {
                $fp = @fsockopen("127.0.0.1", $port, $errno, $errstr, 1);
            }
        } else {
            $znic = new system_nic($nic);
            $ipaddr = $znic->IPADDR;
            if ($ligne["enabled"] == 1) {
                $fp = @fsockopen($ipaddr, $port, $errno, $errstr, 1);
            }
        }

        if ($ligne["enabled"] == 1) {
            if (!$fp) {
                $status = "<span class='label label-danger'>{failed}</span>";
                $tooltip_error = "&nbsp;<span class='label label-danger'>$errstr</span>";
            }
        }


        $UseSSL = "&nbsp;";
        $xnote = "&nbsp;";
        $NoAuth = "";
        $NoCache = "<i class='fas fa-check'></i>";
        $NoFilter = "<i class='fas fa-check'></i>";
        $enabled = "<i class='fas fa-check'></i>";
        if ($AuthParentPort == 1) {
            $AuthParentIco = "&nbsp;<span class='label label-primary'>{AuthParentPort}</span>";
        }

        if ($SSLPort == 1) {
            $ssl_ico = "&nbsp;<span class='label label-primary'>Secure Proxy</span>";
            if (intval($ligne["AuthSSL"]) == 1) {
                $ssl_ico = "&nbsp;<span class='label label-primary'>Secure Proxy</span>&nbsp;<span class='label label-primary'>{client_certificate}</span>";
            }

            $data = $sock->REST_API("/certificate/server/$subcertificate/0");
            $json = json_decode($data);
            if (!$json->Status) {
                $ssl_ico = $ssl_ico . $invalid_certificate . "<br><i class='fas fa-exclamation-triangle'></i>&nbsp;<span class='text-danger'>$json->Error</span>";
            }

        }

        if ($deCrypt == 1) {
            VERBOSE("$port $PortName UseSSL = YES", __LINE__);
            if ($SquidSSLUrgency == 1) {
                $text_class = "text-danger";
                $tooltip_error = "$tooltip_error&nbsp;<span class='label label-danger'>{proxy_in_ssl_emergency_mode}</span>";
                if ($SSLPort == 1) {
                    $ssl_ico = "&nbsp;<span class='label label-danger'>Secure Proxy</span>";

                }
            }
            $UseSSL = $tpl->td_href("<span class='label label-warning'>{decrypt_ssl}</span>",
                "{view}", "Loadjs('fw.proxy.ports.ssl.php?port-id=$ID')");

            if ($SquidSSLUrgency == 0) {
                $sslcertificate = $ligne["sslcertificate"];
                if (strlen($sslcertificate) > 3) {
                    VERBOSE("slcertificate = [$sslcertificate]", __LINE__);
                    $certificate_filename = md5($sslcertificate);
                    $keyout = "/etc/squid3/ssl/$certificate_filename.dyn";
                    if (!is_file($keyout)) {
                        $ssl_ico = $missing_certificate;
                    }
                    $arrayCert = openssl_x509_parse(@file_get_contents($keyout));
                    if (!isset($arrayCert["issuer"])) {
                        $ssl_ico = $invalid_certificate;
                    } else {
                        $certificateEX = "<br><small>{certificate} <strong>$sslcertificate</strong>";
                        $certificateEX = $certificateEX . " {owner}: " .
                            $arrayCert["issuer"]["O"] . ", " . $arrayCert["issuer"]["OU"] . "</small>";

                    }
                }
            }
        }
        if ($deCrypt == 0) {
            VERBOSE("$port $PortName UseSSL = NO", __LINE__);
        }

        if ($ligne["NoAuth"] == 1) {
            $NoAuth = "&nbsp;<span class='label label-warning'>{disable_authentication}</span>";
        }
        if ($ligne["NoCache"] == 1) {
            $NoCache = "&nbsp;";
        }
        if ($ligne["NoFilter"] == 1) {
            $NoFilter = "&nbsp;";
        }
        if ($ligne["enabled"] == 0) {
            $enabled = "&nbsp;";
        }
        if(!is_null($ipaddr)) {
            $ipaddr = str_replace("0.0.0.0", $ALL_TEXT, $ipaddr);
        }
        if ($ipaddr == null) {
            $ipaddr = $ALL_TEXT;
        }
        if ($ligne["xnote"] <> null) {
            $xnote = "&nbsp;<i>{$ligne["xnote"]}</i>";
        }


        if ($ligne["ProxyProtocol"] == 1) {
            $xnote = $xnote . "<br><strong style='color:#0612C6'>" . $tpl->javascript_parse_text("{proxy_protocol}") . "</strong>";

        }


        $js = "Loadjs('$page?port-js=$ID');";
        $ipaddr_lnk = $tpl->td_href($ipaddr, null, $js);
        $PortName_lnk = $tpl->td_href("$port $PortName", null, $js);
        $delete_lnk = $tpl->icon_delete("Loadjs('$page?port-delete-js=$ID')", "AsSquidAdministrator");

        if (($ligne["transparent"] == 1) or ($ligne["TProxy"] == 1)) {
            $ipaddr_lnk = $ipaddr;
            $PortName_lnk = "$port $PortName";
            $delete_lnk = "&nbsp;";
            $NoAuth = "&nbsp;";
            $NoCache = "<i class='fas fa-check'></i>";
            $enabled = "<i class='fas fa-check'></i>";
        }
        if (!$AsFilter) {
            $NoFilter = "&nbsp;";
        }
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$status</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$ipaddr_lnk</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$PortName_lnk $AuthParentIco$xnote$ssl_ico$tooltip_error$certificateEX{$NoAuth}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\">$UseSSL</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\">$NoCache</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\">$NoFilter</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\">$enabled</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\">$delete_lnk</td>");
        $html[] = "</tr>";

    }

    if ($HaClusterClient == 1) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $HaClusterNoAuthPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNoAuthPort"));
        $status = "<span class='label label-primary'>OK</span>";
        $fp = @fsockopen("127.0.0.1", $HaClusterNoAuthPort, $errno, $errstr, 1);
        if (!$fp) {
            $status = "<span class='label label-danger'>{failed}</span>";
            $tooltip_error = "&nbsp;<span class='label label-danger'>$errstr</span>";
        }
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$status</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">{all_interfaces}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\"><strong>$HaClusterNoAuthPort</strong> " . $tpl->javascript_parse_text("{APP_PARENTLB}/HaCluster ({disable_authentication})") . "<br><strong style='color:#0612C6'>" . $tpl->javascript_parse_text("{proxy_protocol}$tooltip_error") . "</strong></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class center\"><i class='fas fa-check'></i></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">&nbsp;</td>");
        $html[] = "</tr>";
    }




    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    if ($users->AsSquidAdministrator) {
        if ($PowerDNSEnableClusterSlave == 0) {

            $topbuttons[] = array("Loadjs('$page?port-js=0')", ico_plus, "{new_port}");
            $results = $q->QUERY_SQL("SELECT * FROM transparent_ports WHERE enabled=1");
            $c = 0;
            foreach ($results as $ligne) {
                $c++;
            }

            if($c==0){
                $topbuttons[] = array("Loadjs('fw.proxy.wizard.transparent.php')", ico_cd, "{transparent_mode}");
            }

            $topbuttons[] = array("Loadjs('fw.proxy.ports.ssl.php')", ico_certificate, "{activate_ssl_decryption}");
            $topbuttons[] = array("Loadjs('fw.proxy.ssl.status.php?powershell-js=yes')", ico_certificate, "{cert_deploy}");



        }
        $topbuttons[] = array($jsrestart, ico_save, "{reconfigure_proxy_ports_restart}");
    }

    $topbuttons[] = array("LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');", ico_refresh, "{refresh}");




	$colspan=9;
    $html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='$colspan'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);

    $TINY_ARRAY["TITLE"]="{listen_ports} ({connected_ports})";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="{listen_ports_v4_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]="</script>";


	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function troubleshooting_ports(){
    $page   =   CurrentPageName();
    $tpl    =   new template_admin();
    $time   =   time();


    $jsrestart=$tpl->framework_buildjs("/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log","$time","LoadAjax('proxy-transparent-ports-status','$page?status=yes');RefreshSecondInterfaceBarrs()");

    $SquidReconfigureAtBoot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReconfigureAtBoot"));


    $form[]=$tpl->field_checkbox("SquidReconfigureAtBoot","{reconfigure_proxy_ports_restart}",$SquidReconfigureAtBoot,false,"{reconfigure_proxy_ports_restart_explain}");
    $html[]="<div id='$time'></div>";
    $html[]=$tpl->form_outside("{after_rebooting}",$form,null,"{apply}",$jsrestart,"AsSquidAdministrator");

    $TINY_ARRAY["TITLE"]="{troubleshooting}";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body($html);



}

