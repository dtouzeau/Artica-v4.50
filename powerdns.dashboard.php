<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');


$users=new usersMenus();
if(!$users->AsDnsAdministrator){
	echo "<script>GoToIndex();</script>\n";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["filter2-section"])){filter2_section();exit;}
if(isset($_GET["infra-section"])){infra_section();exit;}
if(isset($_GET["control-section"])){control_section();exit;}
if(isset($_GET["monitor-section"])){monitor_section();exit;}
if(isset($_GET["update-section"])){update_section();exit;}
if(isset($_GET["debug-section"])){debug_section();exit;}

page();

//http://www.mad-hacking.net/documentation/linux/applications/dns/simple-forward-dns.xml
//SELECT content,ttl,prio,type,domain_id,disabled,name,auth FROM records WHERE disabled=0 and type='SOA' and name='articatech.net'

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$messaging_stopped_explain=null;
	$js_infrasection="LoadAjaxRound('infra-section','$page?infra-section=yes');";
	$js_filtersection="LoadAjaxRound('filter2-section','$page?filter2-section=yes');";
	$js_controlsection="LoadAjaxRound('control-section','$page?control-section=yes');";
	$js_monitorsection="LoadAjaxRound('monitor-section','$page?monitor-section=yes');";
	$js_debugsection="LoadAjaxRound('debug-section','$page?debug-section=yes');";
	$js_updatesection="LoadAjaxRound('update-section','$page?update-section=yes');";
	$sock=new sockets();
	$sock->getFrameWork("services.php?pdns-status=yes");
	
	if(!is_dir(PROGRESS_DIR."/cache")){@mkdir(PROGRESS_DIR."/cache",0755,true);}

	$powerDNSVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSVersion");
	
	$html="
	<input type='hidden' id='thisIsThePDNBSDashBoard' value='1'>
	<div style='margin-top:30px;margin-bottom:30px;font-size:40px;passing-left:30px;'>{DNS_SERVICE} v$powerDNSVersion&nbsp;|&nbsp;<a href=\"javascript:blur()\" OnClick=\"javascript:GotoPDNSInfos()\" style='text-decoration:underline;color:black'>{infos}</a></div>
	<div style='padding-left:30px;padding-right:30px'>			
	<table style='width:100%'>
	<tr>
		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/filter-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:40px;margin-bottom:20px'>{dns_filter}</div>
				<div id='filter2-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			
			
			
			<tr>
			<td valign='top' style='width:96px'><img src='img/infrastructure-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:40px;margin-bottom:20px'>{service_parameters}</div>
				<div style='text-align:right;margin-top:-27px;padding-right:107px'><a href=\"javascript:blur();\" 
				OnClick=\"Loadjs('pdns.reconfigure.progress.php');\"
				style='text-decoration:underline;font-size:14px'>&laquo;&laquo;{reconfigure_service}&raquo;&raquo;<a></div>
				<div id='infra-section' style='padding-left:15px'></div>
			</td>
			</tr>
			
			
			
			
			</table>
			
		</td>
	</tr>
	<tr style='height:30px'>
		<td style='width:50%;vertical-align:top'>&nbsp;</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>&nbsp;</td>
	</tr>
			

	<tr>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/dns-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:40px;margin-bottom:20px'>{records}</div>
				<div id='control-section' style='padding-left:15px'></div>
			</td>
			
			</tr>
			</table>

		</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/graph-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:40px;margin-bottom:20px'>{monitor}</div>
				<div id='monitor-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>	
		
	<tr style='height:30px'>
		<td style='width:50%;vertical-align:top'>&nbsp;</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>&nbsp;</td>
	</tr>		
	<tr>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/maintenance-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:40px;margin-bottom:20px'>{update}</div>
				<div id='update-section' style='padding-left:15px'></div>
			</td>
			
			</tr>
			</table>

		</td>
		<td style='width:50%;vertical-align:top;border-left:4px solid #CCCCCC;padding-left:15px'>
			<table style='width:100%'>
			<tr>
				<td valign='top' style='width:96px'><img src='img/technical-support-96.png' style='width:96px'></td>
				<td valign='top' style='width:99%'>
					<div style='font-size:40px;margin-bottom:20px'>{support_and_debug}</div>
					<div id='debug-section' style='padding-left:15px'></div>
				</td>
			</tr>
			</table>
		</td>
	</tr>		
	</table>
	</div>
	<script>
		$js_filtersection
		$js_infrasection
		$js_controlsection
		$js_monitorsection
		$js_debugsection
		$js_updatesection
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function monitor_section(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$OKStats=true;
	
	
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/all.pdns.status");
	
	
	
	$statusS[]=DAEMON_STATUS_ROUND("APP_PDNS",$ini,null);
	$statusS[]=DAEMON_STATUS_ROUND("APP_PDNS_INSTANCE",$ini,null);
	$statusS[]=DAEMON_STATUS_ROUND("PDNS_RECURSOR",$ini,null);
	$statusS[]=DAEMON_STATUS_ROUND("APP_POWERADMIN",$ini,null);
	$statusS[]=DAEMON_STATUS_ROUND("APP_PDNS_CLIENT",$ini,null);
	$statusS[]=DAEMON_STATUS_ROUND("APP_DSC",$ini,null);
	
	
	$PDNSStatsInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsInstalled"));
	$PDNSStatsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsEnabled"));
	

	$dsc_icon="arrow-right-24.png";
	$dsc_color="#000000";
	$dsc_text=null;
	$dsc_js="GotoPDNSStats()";
	$dsc_js2="GoToStatsDNS()";
	
	if($PDNSStatsEnabled==0){
		$dsc_icon="arrow-right-24-grey.png";
		$dsc_color="#898989";
		$dsc_text=" <span style='font-size:12px'>({disabled})</span>";
		$dsc_js2="blur();";
	
	}
	
	if($PDNSStatsInstalled==0){
		$dsc_icon="arrow-right-24-grey.png";
		$dsc_color="#898989";
		$dsc_text=" <span style='font-size:12px'>({not_installed})</span>";
		$dsc_js="blur()";
		$dsc_js2="blur();";
		
	}
	
	
	$status="
<table style='width:100%'>
			<tr>
			<td style='width:50%'>
				<table style='width:100%'>
					<tr>
						<td valign='middle' style='width:25px'>
						<img src='img/arrow-right-24.png'>
					</td>
					<td valign='middle' style='font-size:18px;width:99%;color:black'>".
						texttooltip("{events}","position:left:{events}","GotoNetworkPowerDNSLOGS()")."</td>
					</tr>
				</table>
			</td>
		<td style='width:50%'>
		<table style='width:100%'>
			<tr>
				<td valign='middle' style='width:25px'><img src='img/$dsc_icon'></td>
				<td valign='middle' style='font-size:18px;width:99%;color:$dsc_color'>".texttooltip("{statistics_options}$dsc_text","position:left:{statistics}","$dsc_js")."</td>
			</tr>
			<tr>
				<td valign='middle' style='width:25px'><img src='img/$dsc_icon'></td>
				<td valign='middle' style='font-size:18px;width:99%;color:$dsc_color'>".texttooltip("{statistics}$dsc_text","position:left:{statistics}","$dsc_js2")."</td>
			</tr>
						
		</table>
	</td>
</tr>
</table>
	
	
	
	
	<hr>

	". CompileTr2($statusS)."";
	
	
	
	
	$html=$tpl->_ENGINE_parse_body($status);	
	echo $html;
	
				
	
}


function cache_section(){
	
	$ahref_caches="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToCaches();\">";
	
}


function infra_section(){
	include_once(dirname(__FILE__)."/ressources/class.squid.inc");
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$icon="arrow-right-24.png";
	$SSLColor="#000000";
	$icon_ssl="arrow-right-24.png";
	$icon_ssl_enc="arrow-right-24.png";
	$ssl_enc_color="#000000";
	$colornet="#000000";
	$GotoSSLEncrypt="GotoSSLEncrypt()";
	$iconnet="arrow-right-24.png";
	$main=new maincf_multi("master");
	$EnablePostfixHaProxy=intval($main->GET("EnablePostfixHaProxy"));
	
	
	$main=new main_cf();
	$array_mynetworks=$main->array_mynetworks;

	if(count($array_mynetworks)==0){
		$iconnet="alert-24.png";
		$colornet="#D22C2C";
	}
	
	
	$array["forward-zones"]='{forward_zones}';
	
	$network="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".
		texttooltip("{forward_zones}","position:left:{forward_zones}","GoToPDNSForwardZones()")."</td>
	</tr>";	
	
	

	
	

		
	$tr[]="<tR><td colspan=2>&nbsp;</td></tr>";
	$tr[]="<table style='width:100%'>
	<tr>
	<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold'>{parameters}:</td>
	</tr>";
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{interface_settings}",
					"position:left:{interface_settings}","Loadjs('system.pdns.menus.php')")."</td>
	</tr>";

	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{global_parameters}",
					"position:left:{global_parameters}","GotoNetworkPowerDNS()")."</td>
	</tr>";	
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{listen_ip}",
					"position:left:{global_parameters}","GotoPDNSListenIP()")."</td>
	</tr>";
	

	
	

	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/arrow-right-24.png'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{database}",
					"position:left:{database}","GotoPDNSDatabase()")."</td>
	</tr>";	
	
	
	
	
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."</table></td>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr2))."</td>
	</tr>
	</table>
	";
	
	
	echo $final;
}

function control_section(){
	include_once(dirname(__FILE__) . "/ressources/class.mysql.powerdns.inc");
	$sock=new sockets();
	$tpl=new templates();
	$q=new mysql_pdns();
	
	$PowerAdminInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminInstalled"));
	$EnablePowerAdmin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePowerAdmin"));
	$EnablePowerAdminConsole=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePowerAdminConsole"));
	$PDNSWpad=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSWpad"));
	$users=new usersMenus();
	
	
	$OKQuota=true;
	
	
	$color_quota="black";
	$tr[]="<table style='width:100%'>";
	

	$poweradmin_icon="arrow-right-24.png";
	$poweradmin_color="#000000";
	$poweradmin_text=null;
	$poweradmin_js="GotoPowerAdmin()";
	
	$wpad_icon="arrow-right-24.png";
	$wpad_color="#000000";
	$wpad_text=null;
	$wpad_js="GotoPDNSWPAD()";
	
	
	if($PDNSWpad==0){

		$wpad_icon="arrow-right-24-grey.png";
		$wpad_color="#898989";
		$wpad_text=" <span style='font-size:12px'>({disabled})</span>";
		
	}

	if($EnablePowerAdmin==0){
		$poweradmin_icon="arrow-right-24-grey.png";
		$poweradmin_color="#898989";
		$poweradmin_text=" <span style='font-size:12px'>({disabled})</span>";
		
		$PDNSInstallTR="<tr>
				<td valign='middle' style='width:16px'><img src='img/arrow-right-16.png'></td>
				<td valign='middle' style='font-size:16px;width:99%;color:black'>".
						texttooltip("{enable_feature}","position:right:{enable_feature} {webinterface}","Loadjs('poweradmin.enable.php')")."</td>
			</tr>";
		
	}
	$CountOFDomains_text=null;
	$CountOfRecords_text=null;
	if($PowerAdminInstalled==0){
		$poweradmin_icon="arrow-right-24-grey.png";
		$poweradmin_color="#898989";
		$poweradmin_text=" <span style='font-size:12px'>({not_installed})</span>";
		$poweradmin_js="blur();";
		$PDNSInstallTR="<tr>
				<td valign='middle' style='width:16px'><img src='img/16-install-soft.png'></td>
				<td valign='middle' style='font-size:16px;width:99%;color:black'>".
						texttooltip("{install_service}","position:right:{install} {webinterface}","Loadjs('poweradmin.first.install.php')")."</td>
			</tr>";
		
	}else{
		$CountOFDomains=$q->COUNT_ROWS("domains");
		$CountOfRecords=$q->COUNT_ROWS("records");
		if($CountOFDomains>0){
			$CountOFDomains_text=" <span style='font-size:12px'>($CountOFDomains {domains})</span>";
		}
		
		if($CountOfRecords>0){
			$CountOfRecords_text=" <span style='font-size:12px'>($CountOfRecords)</span>";
			
		}
	}
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{forward_dns}",
			"position:right:{forward_dns_explain}","GotoPDNSForward()")."</td>
	</tr>
	";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{local_domains}$CountOFDomains_text",
				"position:right:{local_domains}","GotoPDNSLocalDomains()")."</td>
	</tr>
	";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{DNS_RECORDS}$CountOfRecords_text",
				"position:right:{DNS_RECORDS}","GotoPDNSRecords()")."</td>
	</tr>
	";
	
	
	if($EnablePowerAdminConsole==1){
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/$poweradmin_icon'>
		</td>
		<td valign='middle' style='font-size:18px;width:99%;color:$poweradmin_color'>".texttooltip("{webinterface}$poweradmin_text",
					"position:right:{webinterface}",$poweradmin_js)."</td>
		</tr>
		<tr>
			<td style='width:25px'>&nbsp;</td>
			<td><table>$PDNSInstallTR</table></tD>
		</tr>";
	}

	$tr[]="</table>";
	
	$tr2[]="<table style='width:100%'>";
	

	$tr2[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$wpad_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$wpad_color'>".texttooltip("{wpad_rules}$wpad_text",
				"position:right:{wpad_rules}",$wpad_js)."</td>
	</tr>";
	
	
	$tr2[]="</table>";
	
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."</td>
		<td style='width:50%' valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n",$tr2))."</td>
	</tr>
	</table>
	";
	
	echo $final;
	
	
}

function filter2_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	$PDSNInUfdb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDSNInUfdb"));
	$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
	$PDNSUseHostsTable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSUseHostsTable"));
	$UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
	$EnableUfdbErrorPage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbErrorPage"));
	$EnablePDNSRecurseRestrict=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNSRecurseRestrict"));
	$SquidUFDBUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgency"));
	
	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	$EnableQwantSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQwantSafeSearch"));
	$EnableBingSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBingSafeSearch"));
	$EnableYoutubeSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYoutubeSafeSearch"));
	$SafeSearch=0;
	if($EnableGoogleSafeSearch+$EnableQwantSafeSearch+$EnableBingSafeSearch+$EnableYoutubeSafeSearch > 0){
		$SafeSearch=1;
	}
	
	
	
	$PDNSClient=1;
	$dnsfilter_color="#000000";
	$icon="arrow-right-24.png";
	$dnsfilter_icon="arrow-right-24.png";
	$clamav_icon="arrow-right-24.png";
	$dnsfilter_text=null;
	$dnsfilter_js="GotoDnsFilter()";
	$UfdbguardRules_js="GoToUfdbguardRules('PowerDNS')";
	$UfdbguardRules_icon="arrow-right-16.png";
	$UfdbguardRules_color="#000000";
	$text_category=null;
	
	
	$explain_category="{your_categories_explain}";
	$js_categories="GotoYourcategories();";
	$icon_category="arrow-right-16.png";
	
	$icon_error_page="arrow-right-24.png";
	$color_error_page="black";
	$js_error_page="GotoUfdbErrorPage()";
	$text_error_page=null;
	
	$unlock_icon="arrow-right-16.png";
	$unlock_script="GotoUfdbUnlockPages()";
	$unlock_color="black";
	
	$ufdb_client_icon="arrow-right-24.png";
	$ufdb_client_color="black";
	$ufdb_client_text=null;
	$ufdb_client_js="GotoPDNSClient()";
	
	$EnableGoogleSafeSearch_icon="arrow-right-24.png";
	$EnableGoogleSafeSearch_color="#000000";
	$EnableGoogleSafeSearch_text=null;
	$EnableGoogleSafeSearch_js="GoToPDNSSafeSearch()";
	
	$EnableQwantSafeSearch_icon="arrow-right-24.png";
	$EnableQwantSafeSearch_color="#000000";
	$EnableQwantSafeSearch_text=null;
	$EnableQwantSafeSearch_js="GoToPDNSQwantSafeSearch()";
	
	if($SafeSearch==0){
		$EnableGoogleSafeSearch_icon="arrow-right-24-grey.png";
		$EnableGoogleSafeSearch_color="#898989";
		$EnableGoogleSafeSearch_text=" <span style='font-size:12px'>({disabled})</span>";
	}

	
	$EnablePDNSRecurseRestrict_icon="arrow-right-24.png";
	$EnablePDNSRecurseRestrict_color="#000000";
	$EnablePDNSRecurseRestrict_text=null;
	
	if($EnablePDNSRecurseRestrict==0){
		$EnablePDNSRecurseRestrict_icon="arrow-right-24-grey.png";
		$EnablePDNSRecurseRestrict_color="#898989";
		$EnablePDNSRecurseRestrict_text=" <span style='font-size:12px'>({disabled})</span>";
		
	}
	
	
	if($EnableUfdbGuard==0){$PDSNInUfdb=0;}
	
	if(($PDSNInUfdb+$PDNSUseHostsTable)==0){
		$PDNSClient=0;
	}
	
	
	if($PDSNInUfdb==1){
		if($SquidUFDBUrgency==1){
			$dnsfilter_color="#d32d2d";
			$dnsfilter_icon="alert-24.png";
			$dnsfilter_text=" <span style='font-size:12px'>{proxy_in_webfiltering_emergency_mode}</span>";
		}
	}
	
	
	if($PDSNInUfdb==0){
		$EnableUfdbErrorPage=0;
		$dnsfilter_color="#898989";
		$UfdbguardRules_color="#898989";
		$dnsfilter_text=" <span style='font-size:12px'>({disabled})</span>";
		$dnsfilter_icon="arrow-right-24-grey.png";
		$UfdbguardRules_icon="arrow-right-16-grey.png";
		$UfdbguardRules_js="blur();";
		$icon_category="arrow-right-16-grey.png";
		$color_category="#898989";
		$explain_category=" <span style='font-size:12px'>({disabled})</span>";
	}
	
	if($EnableUfdbErrorPage==0){
		$unlock_icon="arrow-right-16-grey.png";
		$unlock_color="#898989";
		$unlock_script="blur();";
		$icon_error_page="arrow-right-24-grey.png";
		$text_error_page=" <span style='font-size:12px'>({disabled})</span>";
		$color_error_page="#898989";
			
	}

	
	$explain_category="{your_categories_explain}";
	
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$icon_category="arrow-right-16-grey.png";
		$color_category="#898989";
		$explain_category="{this_feature_is_disabled_corp_license}";
		
		$dnsfilter_color="#898989";
		$UfdbguardRules_color="#898989";
		$dnsfilter_text=" <span style='font-size:12px'>({license_error})</span>";
		$text_category=" <span style='font-size:12px'>({license_error})</span>";
		$dnsfilter_icon="arrow-right-24-grey.png";
		$UfdbguardRules_icon="arrow-right-16-grey.png";
		$UfdbguardRules_js="blur();";
	}
	

	
	$ufdb_clientlog_icon="arrow-right-16.png";
	$ufdb_clientlog_js="GotoPDNSClientLogs()";
	

	
	if($PDNSClient==0){
		
		$unlock_icon="arrow-right-24-grey.png";
		$unlock_script="blur()";
		$unlock_color="#898989";
		
		$ufdb_client_icon="arrow-right-24-grey.png";
		$ufdb_client_color="#898989";
		$ufdb_client_text=" <span style='font-size:12px'>({disabled})</span>";
		$ufdb_client_js="blur()";
		
		$ufdb_clientlog_icon="arrow-right-16-grey.png";
		$ufdb_clientlog_js="blur()";
	}

	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{networks}:</td>
	</tr>";
	
	 
	
	$tr[]="
	<!-- ".__LINE__."  -->
	<tr>
		<td valign='middle' style='width:25px'><img src='img/$EnablePDNSRecurseRestrict_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$EnablePDNSRecurseRestrict_color'>".
		texttooltip("{networks_restrictions}{$EnablePDNSRecurseRestrict_text}","position:right:{restrictions}","GotoPDNSRestrictions()")."</td>
	</tr>";
	$tr[]="
	<!-- ".__LINE__."  -->
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$EnableGoogleSafeSearch_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$EnableGoogleSafeSearch_color'>".
		texttooltip("SafeSearch$EnableGoogleSafeSearch_text","position:right:SafeSearch Google,Bing,Youtube,Qwant","$EnableGoogleSafeSearch_js")."</td>
	</tr>";
	

	
	
	
	$tr[]="<!-- ".__LINE__."  -->";
	$tr[]="<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>&nbsp;</td>
	</tr>";
	
	$tr[]="<tr>
		<td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>{dns_filter}:</td>
	</tr>";
	
	$tr[]="<tr>
		<td valign='middle' style='width:25px'><img src='img/$dnsfilter_icon'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$dnsfilter_color'>".
		texttooltip("{global_parameters}$dnsfilter_text","position:right:{dns_filters_ufdb_text}",$dnsfilter_js)."</td>
	</tr>		
	<tr>
		<td valign='middle' style='width:25px'>&nbsp;</td>
		<td valign='middle' style='width:100%'>
			<table style='width:100%'>
				<tr>
					<td valign='middle' style='width:25px'><img src='img/$UfdbguardRules_icon'></td>
					<td valign='middle' style='font-size:16px;width:99%;color:$UfdbguardRules_color'>".texttooltip("{webfiltering_rules}",
					"position:right:{webfiltering_rules}",$UfdbguardRules_js)."</td>
				</tr>";	
if($UseRemoteUfdbguardService==0){
			$tr[]="
			<tr>
			<td valign='middle' style='width:25px'><img src='img/$icon_category'></td>
			<td valign='middle' style='font-size:16px;width:99%;color:$color_category'>".
			texttooltip("{your_categories}$text_category","position:right:$explain_category",$js_categories)."</td>
			</tr>";	
		}																		
$tr[]="</table>
		</td>
	</tr>";
	

	$tr[]="<!-- ".__LINE__."  -->";
	$tr[]="<tr><td valign='middle' colspan=2 style='font-size:22px;font-weight:bold;color:black'>&nbsp;</td></tr>";

	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$ufdb_client_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$ufdb_client_color'>".texttooltip("{client_parameters}$ufdb_client_text","position:right:{ufdbgclient_explain}","$ufdb_client_js")."</td>
	</tr>
	<tr>
	<td valign='middle' style='width:25px'>&nbsp;</td>
	<td valign='middle'>
	<table style='width:100%'>
	<tr>
	<td valign='middle' style='width:16px'>
	<img src='img/$ufdb_clientlog_icon'>
	</td>
	<td valign='middle' style='font-size:14px;width:99%;color:$ufdb_client_color'>".
	texttooltip("{events} $ufdb_client_text",
			"position:right:{events}","$ufdb_clientlog_js")."</td>
						</tr>
					</table>
			</td>
			</tr>
	
			";	
	
	$tr[]="<!-- ".__LINE__."  --></table>";
	$tr2=array();
	$tr2[]="<table style='width:100%'>";
		$tr2[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon_error_page'></td>
		<td valign='middle' style='font-size:18px;width:99%;color:$color_error_page'>".texttooltip("{banned_page_webservice}$text_error_page","position:right:{deny_web_page_text}",$js_error_page)."</td>
		</tr>		<tr>
		 <td valign='middle' style='width:25px'>&nbsp;</td>
		 <td valign='middle'>
			<table style='width:100%'>
				<tr>
					<td valign='middle' style='width:16px'><img src='img/$unlock_icon'></td>
					<td valign='middle' style='font-size:14px;width:99%;color:$unlock_color'>".texttooltip("{rules}",
				"position:right:{unlock_rules_explain_text}",$unlock_script)."</td>
				</tr>
								
			</table>
		</td>
		</tr>
	";
		$tr2[]="</table>";
	$final="
	<table style='width:100%'>
	<tr>
		<td style='width:50%' valign='top'>
				".$tpl->_ENGINE_parse_body(@implode("\n", $tr))."
		</td>
		<td style='width:50%' valign='top'>
			".$tpl->_ENGINE_parse_body(@implode("\n", $tr2))."
		</td>
	</tr>
	</table>
	";
	
	echo $final;
	
}

function update_section(){
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$MilterGreyListEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled"));
	$SMTP_MILTERGREY_ARTICA=intval(@file_get_contents("/usr/share/artica-postfix/ressources/smtp-cache/SMTP_MILTERGREY_ARTICA"));
	$PDSNInUfdb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDSNInUfdb"));
	
	$icon="arrow-right-24.png";
	$clamav_icon=$icon;
	$tr[]="<table style='width:100%'>";

	if($PDSNInUfdb==1){
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{update_webfiltering_engine}",
				"position:top:{update_webfiltering_engine}","UfdbGuardUpdateEngine();")."</td>
		</tr>";
		
		$tr[]="
		<tr>
		<td valign='middle' style='width:25px'><img src='img/$icon'></td>
		<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{webfilter_databases}",
				"position:top:{webfilter_databases_update_explain}","GoToWebfilteringDBstatus()")."</td>
	</tr>";
		
	}
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{APP_DSC}",
				"position:top:{APP_DSC}","GoToDSCUpdate()")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{reinstall}",
			"position:top:{reinstall}","Loadjs('pdns.first.install.php')")."</td>
	</tr>";
	
	
	
	$tr[]="</table>";
	$html =$tpl->_ENGINE_parse_body(@implode("\n", $tr));

	echo $html;
	
}

function debug_section(){
	$tpl=new templates();
	$users=new usersMenus();
	$rebuild_database_warn=$tpl->javascript_parse_text("{rebuild_database_warn}");
	$check_resolution=$tpl->_ENGINE_parse_body("{check_resolution}");
	$icon="arrow-right-24.png";
	$tr[]="<table style='width:100%'>";
	

	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{recreate_database}","position:top:{recreate_database_text}","javascript:RebuildPDNSDB();")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/$icon'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{check_resolution}","position:top:{check_resolution_dns_engine}","javascript:YahooWin5('990','pdns.php?digg=yes','$check_resolution');")."</td>
	</tr>";

	

	$tr[]="</table>
			
			
<script>
	var x_RestartPDNS=function (obj) {
		var results=obj.responseText;
		if (results.length>0){alert(results);}
	}


	function RebuildPDNSDB(){
		if(!confirm('$rebuild_database_warn')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('RebuildPDNSDB','yes');
		XHR.sendAndLoad('pdns.php', 'POST',x_RestartPDNS);
	}
	
</script>			
			
";
		
		
		$html=$tpl->_ENGINE_parse_body(@implode("\n", $tr));
		$monitor_file=PROGRESS_DIR."/cache/".md5("DEBUGSECTION".$tpl->language.$_SESSION["uid"]);
		@file_put_contents($monitor_file, $html);
		echo $html;
	
	
}
function AsMainOrgAdmin(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->AsPostfixAdministrator){return true;}
	
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}