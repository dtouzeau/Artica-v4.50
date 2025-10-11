<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["ENABLE_SERVER"])){SAVE_SERVER();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["strongswan-status"])){strongswan_status();exit;}
if(isset($_GET["strongswan-vici"])){strongswan_vici_status();exit;}
if(isset($_GET["strongswan-vici-parser"])){strongswan_vici_parser_status();exit;}
page();


function strongswan_status(){
    $page=CurrentPageName();
    

	$sock=new sockets();
	$sock->getFrameWork("strongswan.php?status=yes");
	$bsini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/strongswan.status");
	$tpl=new template_admin();

	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.install.php";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/strongswan.install.php.log";
	$ARRAY["CMD"]="strongswan.php?restart=yes";
	$ARRAY["TITLE"]="{APP_STRONGSWAN} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('strongswan-status','$page?strongswan-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

	$final[]=$tpl->SERVICE_STATUS($bsini, "APP_STRONGSWAN",$jsRestart);

    VERBOSE(count($final)." row",__LINE__);
    echo $tpl->_ENGINE_parse_body($final);
	
}

function strongswan_vici_status(){
    $page=CurrentPageName();

    $sock=new sockets();
    $sock->getFrameWork("strongswan.php?status-vici=yes");
     $bsini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/strongswan.status");
    $tpl=new template_admin();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php.log";
    $ARRAY["CMD"]="strongswan.php?restart-parser=yes";
    $ARRAY["TITLE"]="{APP_STRONGSWAN_VICI} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('strongswan-status-vici','$page?strongswan-vici=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_STRONGSWAN_VICI",$jsRestart);

    VERBOSE(count($final)." row",__LINE__);
    echo $tpl->_ENGINE_parse_body($final);

}

function strongswan_vici_parser_status(){
    $page=CurrentPageName();

    $sock=new sockets();
    $sock->getFrameWork("strongswan.php?status-vici-parser=yes");
    $bsini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/strongswan.status");
    $tpl=new template_admin();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php.log";
    $ARRAY["CMD"]="strongswan.php?restart-parser=yes";
    $ARRAY["TITLE"]="{APP_STRONGSWAN_VICI_PARSER} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('strongswan-status-vici-parser','$page?strongswan-vici-parser=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_STRONGSWAN_VICI_PARSER",$jsRestart);

    VERBOSE(count($final)." row",__LINE__);
    echo $tpl->_ENGINE_parse_body($final);

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

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$StrongswanVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongswanVersion");
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{STRONGSWAN_SERVER_SETTINGS} v$StrongswanVersion</h1><p>{APP_STRONGSWAN_TEXT}</p>$btn</div>
	
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/ipsec-settings');	
	
	LoadAjax('table-loader','$page?main=yes');

	</script>";

	$tpl=new templates();
    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Settings',$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}


function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$strongswan=new strongswan();
	$nic=new networking();
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();

	if(!is_file("/etc/artica-postfix/settings/Daemons/strongSwanCachecrls")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("strongSwanCachecrls", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/strongSwanCharondebug")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("strongSwanCharondebug", 2);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/strongSwanCharonstart")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("strongSwanCharonstart", 1);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/strongSwanStrictcrlpolicy")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("strongSwanStrictcrlpolicy", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/strongSwanUniqueids")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("strongSwanUniqueids", 1);}
	
	$strongSwanCachecrls=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCachecrls");
	$strongSwanCharondebug=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCharondebug");
	$strongSwanCharonstart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCharonstart"));
	$strongSwanStrictcrlpolicy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanStrictcrlpolicy"));
	$strongSwanUniqueids=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanUniqueids"));
    $Strongswanretention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Strongswanretention"));
    $EnableStrongSwanLogSyslog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongSwanLogSyslog"));
    $StrongSwanLogSyslogServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongSwanLogSyslogServer"));
    $StrongSwanLogSyslogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongSwanLogSyslogServerPort"));
    $StrongSwanLogSyslogServerTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongSwanLogSyslogServerTCP"));
    $StrongSwanLogSyslogUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongSwanLogSyslogUseSSL"));
    $StrongSwanLogSyslogCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongSwanLogSyslogCertificate"));
    $StrongSwanLogSyslogDoNotStorelogsLocally=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongSwanLogSyslogDoNotStorelogsLocally"));

    if($Strongswanretention==0){$Strongswanretention=7;}

    $charondebugList[-1]=-1;
    $charondebugList[0]=0;
    $charondebugList[1]=1;
    $charondebugList[2]=2;
    $charondebugList[3]=3;
    $charondebugList[4]=4;

    $strictcrlpolicyList[0]='no';
    $strictcrlpolicyList[1]='yes';
    $strictcrlpolicyList[2]='ifuri';

    $uniqueidsList[0]='no';
    $uniqueidsList[1]='yes';
    $uniqueidsList[2]='never';
    $uniqueidsList[3]='replace';
	$uniqueidsList[4]='keep';
	
	$StrongswanListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongswanListenInterface"));
	$StrongswanEnableDNSWINS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongswanEnableDNSWINS"));
	$strongSwanEnableDHCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanEnableDHCP"));
	$StrongswanDHCPListenInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongswanDHCPListenInterface");

    $form[]=$tpl->field_hidden("ENABLE_SERVER",$strongswan->main_array["GLOBAL"]["ENABLE_SERVER"]);
    $form[]=$tpl->field_checkbox("strongSwanCharonstart","{strongSwanCharonstart}",$strongSwanCharonstart,false,"{strongSwanCharonstart_explain}");

    $form[]=$tpl->field_checkbox("strongSwanCachecrls","{strongSwanCachecrls}",$strongSwanCachecrls,false,"{strongSwanCachecrls_explain}");

	$form[]=$tpl->field_array_hash($charondebugList,"strongSwanCharondebug","{strongSwanCharondebug}",$strongSwanCharondebug,false,"{strongSwanCharondebug_explain}");
    $form[]=$tpl->field_array_hash($strictcrlpolicyList,"strongSwanStrictcrlpolicy","{strongSwanStrictcrlpolicy}",$strongSwanStrictcrlpolicy,false,"{strongSwanStrictcrlpolicy_explain}");
	$form[]=$tpl->field_array_hash($uniqueidsList,"strongSwanUniqueids","{strongSwanUniqueids}",$strongSwanUniqueids,false,"{strongSwanUniqueids_explain}");
	$form[]=$tpl->field_interfaces("StrongswanListenInterface", "{listen_interfaces}", $StrongswanListenInterface);
	$form[]=$tpl->field_section("{DNSWINS_SERVICE_STRONGSWAN}","{DNSWINS_SERVICE_STRONGSWAN_EXPLAIN}");
	$form[]=$tpl->field_checkbox("StrongswanEnableDNSWINS","{enable}",$StrongswanEnableDNSWINS,false);

	$form[]=$tpl->field_ipaddr("VPN_DNS_1","{dns_server} 1",$strongswan->main_array["GLOBAL"]["VPN_DNS_1"],false);
	$form[]=$tpl->field_ipaddr("VPN_DNS_2","{dns_server} 2",$strongswan->main_array["GLOBAL"]["VPN_DNS_2"],false);
	
	$form[]=$tpl->field_ipaddr("VPN_WINS_1","{wins_server} 1",$strongswan->main_array["GLOBAL"]["VPN_WINS_1"],false);
	$form[]=$tpl->field_ipaddr("VPN_WINS_2","{wins_server} 2",$strongswan->main_array["GLOBAL"]["VPN_WINS_2"],false);

	$form[]=$tpl->field_section("{DHCP_SERVICE_STRONGSWAN}","{DHCP_SERVICE_STRONGSWAN_EXPLAIN}");
	$form[]=$tpl->field_checkbox("strongSwanEnableDHCP","{enable}",$strongSwanEnableDHCP,false);
	$form[]=$tpl->field_checkbox("strongSwanDHCPForceServerAddress","{strongSwanDHCPForceServerAddress}",$strongswan->main_array["GLOBAL"]["strongSwanDHCPForceServerAddress"],false,"{strongSwanDHCPForceServerAddress_EXPLAIN}");
	$form[]=$tpl->field_checkbox("strongSwanDHCPIdentityLease","{strongSwanDHCPIdentityLease}",$strongswan->main_array["GLOBAL"]["strongSwanDHCPIdentityLease"],false,"{strongSwanDHCPIdentityLease_EXPLAIN}");
	$form[]=$tpl->field_interfaces("StrongswanDHCPListenInterface", "{listen_interfaces}", $StrongswanDHCPListenInterface);

	$form[]=$tpl->field_ipaddr("strongSwanDHCPServer","{dhcp_server}",$strongswan->main_array["GLOBAL"]["strongSwanDHCPServer"],false);
    $form[]=$tpl->field_section("{LOG_STRONGSWAN}","{LOG_STRONGSWAN_EXPLAIN}");
    $form[]=$tpl->field_numeric("Strongswanretention","{retention_time} (Days)",$Strongswanretention,"{retention_time_text}");
    $form[] = $tpl->field_checkbox("EnableStrongSwanLogSyslog", "{send_syslog_logs}", $EnableStrongSwanLogSyslog, "StrongSwanLogSyslogServer");

    $form[] = $tpl->field_checkbox("StrongSwanLogSyslogDoNotStorelogsLocally", "{not_store_log_locally}", $StrongSwanLogSyslogDoNotStorelogsLocally);
    $form[] = $tpl->field_ipv4("StrongSwanLogSyslogServer", "{remote_syslog_server}", $StrongSwanLogSyslogServer);
    $form[] = $tpl->field_numeric("StrongSwanLogSyslogServerPort", "{listen_port}", $StrongSwanLogSyslogServerPort);
    $form[] = $tpl->field_checkbox("StrongSwanLogSyslogServerTCP", "{enable_tcpsockets}", $StrongSwanLogSyslogServerTCP);
    $form[] = $tpl->field_checkbox("StrongSwanLogSyslogUseSSL", "{useSSL}", $StrongSwanLogSyslogUseSSL);
    $form[] = $tpl->field_certificate("StrongSwanLogSyslogCertificate", "{certificate}", $StrongSwanLogSyslogCertificate);


    $tpl->FORM_IN_ARRAY=false;
	

	
	$tpl->form_add_button("{reconfigure_service}", $tpl->button_strongswan_reconfigure(true));

	
	$myform=$tpl->form_outside("{general_settings}", @implode("\n", $form),"{strongswan_whatis}");

	$html[]="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='strongswan-status' style='margin-top:15px'></div><div id='strongswan-status-vici' style='margin-top:15px'></div><div id='strongswan-status-vici-parser' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('strongswan-status','$page?strongswan-status=yes');</script>	
	<script>LoadAjaxSilent('strongswan-status-vici','$page?strongswan-vici=yes');</script>
	<script>LoadAjaxSilent('strongswan-status-vici-parser','$page?strongswan-vici-parser=yes');</script>
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
						
}

function SAVE_SERVER(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	$q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
	if($_POST["StrongswanEnableDNSWINS"]==1){
		if(empty($_POST["VPN_DNS_1"]) || empty($_POST["VPN_WINS_1"])){
			echo "jserror:DNS1 or WINS1 can't be empty";
			return false;
		}
	}

	if($_POST["strongSwanEnableDHCP"]==1){
		if(empty($_POST["strongSwanDHCPServer"])){
			echo "jserror:DHCP Server can't be empty";
			return false;
		}
	}
    if ( filter_var($_POST["Strongswanretention"], FILTER_VALIDATE_INT) === false ) {
        echo "jserror:Retention time should be integer";
        return false;
    }

	$sock->SET_INFO("strongSwanCachecrls", url_decode_special_tool($_POST["strongSwanCachecrls"]));
	$sock->SET_INFO("strongSwanCharondebug", url_decode_special_tool($_POST["strongSwanCharondebug"]));
	$sock->SET_INFO("strongSwanCharonstart", url_decode_special_tool($_POST["strongSwanCharonstart"]));
	$sock->SET_INFO("strongSwanStrictcrlpolicy", url_decode_special_tool($_POST["strongSwanStrictcrlpolicy"]));
	$sock->SET_INFO("strongSwanUniqueids", url_decode_special_tool($_POST["strongSwanUniqueids"]));
	$sock->SET_INFO("StrongswanListenInterface", url_decode_special_tool($_POST["StrongswanListenInterface"]));
	$sock->SET_INFO("StrongswanEnableDNSWINS", url_decode_special_tool($_POST["StrongswanEnableDNSWINS"]));
	$sock->SET_INFO("strongSwanEnableDHCP", url_decode_special_tool($_POST["strongSwanEnableDHCP"]));
	$sock->SET_INFO("StrongswanDHCPListenInterface", url_decode_special_tool($_POST["StrongswanDHCPListenInterface"]));
    $sock->SET_INFO("Strongswanretention", intval($_POST["Strongswanretention"]));
    $sock->SET_INFO("EnableStrongSwanLogSyslog", url_decode_special_tool($_POST["EnableStrongSwanLogSyslog"]));
    $sock->SET_INFO("StrongSwanLogSyslogDoNotStorelogsLocally", url_decode_special_tool($_POST["StrongSwanLogSyslogDoNotStorelogsLocally"]));
    $sock->SET_INFO("StrongSwanLogSyslogServer", url_decode_special_tool($_POST["StrongSwanLogSyslogServer"]));
    $sock->SET_INFO("StrongSwanLogSyslogServerPort", url_decode_special_tool($_POST["StrongSwanLogSyslogServerPort"]));
    $sock->SET_INFO("StrongSwanLogSyslogServerTCP", url_decode_special_tool($_POST["SStrongSwanLogSyslogServerTCP"]));
    $sock->SET_INFO("StrongSwanLogSyslogUseSSL", url_decode_special_tool($_POST["StrongSwanLogSyslogUseSSL"]));
    $sock->SET_INFO("StrongSwanLogSyslogCertificate", url_decode_special_tool($_POST["StrongSwanLogSyslogCertificate"]));


	$ini=new Bs_IniHandler();
	$ini->loadString(@file_get_contents('/etc/artica-postfix/settings/Daemons/ArticastrongSwanSettings'));
	reset($_POST);foreach ($_POST as $key=>$val){
		$ini->_params["GLOBAL"][$key]=url_decode_special_tool($val);
		
	}
	$sock->SaveConfigFile($ini->toString(), "ArticastrongSwanSettings");

	foreach ($_POST as $num=>$ligne){
		$ligne=url_decode_special_tool($ligne);
		$strongSwanWizard[$num]=$ligne;
	}
	$sock->SaveConfigFile(base64_encode(serialize($strongSwanWizard)), "strongSwanWizard");
	
}

