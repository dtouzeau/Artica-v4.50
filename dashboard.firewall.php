<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
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
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if(isset($_GET["nat-section"])){nat_section();exit;}
if(isset($_GET["bridge-section"])){bridge_section();exit;}
if(isset($_GET["tasks-section"])){tasks_section();exit;}
if(isset($_GET["monitor-section"])){monitor_section();exit;}
if(isset($_GET["nics-section"])){nics_section();exit;}
if(isset($_GET["services-section"])){services_section();exit;}
if(isset($_GET["RulesStatus-js"])){rules_status_js();exit;}
if(isset($_GET["RulesStatus-popup"])){rules_status_popup();exit;}

if(isset($_GET["RestartStatus-js"])){restart_status_js();exit;}
if(isset($_GET["RestartStatus-popup"])){restart_status_popup();exit;}



page();

function rules_status_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$config_file=$tpl->javascript_parse_text("{config_file}");
	echo "YahooWin('1024','$page?RulesStatus-popup=yes','$config_file');";
	
}
function restart_status_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$config_file=$tpl->javascript_parse_text("{restart} {events}");
	echo "YahooWin('1024','$page?RestartStatus-popup=yes','$config_file');";

}
function rules_status_popup(){
	
	echo "<textarea style='margin-top:5px;font-family:Courier New;
font-weight:bold;width:98%;height:600px;border:5px solid #8E8E8E;
overflow:auto;font-size:16px !important' id='text-$t'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleDumpRules")."</textarea>";
}
function restart_status_popup(){

	echo "<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:600px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important' id='text-$t'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleRestartDump")."</textarea>";
}
function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$firehol_version=$sock->getFrameWork("firehol.php?firehol-version=yes");
	$t=time();
	$html="
	<div style='margin-top:30px;margin-bottom:30px;font-size:40px;passing-left:30px;'>{your_firewall} v.$firehol_version &laquo;&laquo;". texttooltip("{refresh}","{refresh}","FireWallDashBoardSequence()")."&raquo;</div>
	<p class=text-info style='font-size:18px'>{fw_dedicated_interface}</p>
	<div style='padding-left:30px;padding-right:30px'>	
	<table style='width:100%'>
	<tr>
		<td style='width:50%;vertical-align:top'>
		<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/interfaces-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{network_interfaces}</div>
				<div id='nics-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
		</td>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/users-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{dedicated_interface}</div>
				<div id='bridge-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>
<tr>
<tr style='height:70px'><td colspan=2>&nbsp;</td></tr>

		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/tasks-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{tasks}</div>
				<div id='tasks-section' style='padding-left:15px'></div>
			</td>
			
			</tr>
			</table>
		</td>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/fw-services-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{rules_and_services}</div>
				<div id='services-section' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>
	</tr>	
<tr style='height:70px'><td colspan=2>&nbsp;</td></tr>	
	
	
	
	
	<tr style='height:30px'><td colspan=2>&nbsp;</td></tr>
	<tr>
		<td style='width:50%;vertical-align:top'>
		</td>
		<td style='width:50%;vertical-align:top'>
			<table style='width:100%'>
			<tr>
			<td valign='top' style='width:96px'><img src='img/graph-96.png' style='width:96px'></td>
			<td valign='top' style='width:99%'>
				<div style='font-size:30px;margin-bottom:20px'>{monitor}</div>
				<div id='monitor-section-$t' style='padding-left:15px'></div>
			</td>
			</tr>
			</table>
			
		</td>	
	</table>
	</div>
	<script>
	
	function FireWallDashBoardSequence(){
		
		LoadAjaxRound('bridge-section','$page?bridge-section=yes');
		LoadAjaxRound('tasks-section','$page?tasks-section=yes');
		LoadAjaxRound('monitor-section-$t','$page?monitor-section=yes');
		LoadAjaxRound('nics-section','$page?nics-section=yes');
		LoadAjaxRound('services-section','$page?services-section=yes');
		
	}
	FireWallDashBoardSequence();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function tasks_section(){
	
	$sock=new sockets();
	$tpl=new templates();
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/32-stop.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{stop_firewall}",null,"Loadjs('firehol.progress.php?comand=stop')")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/start-32.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{start_firewall}",null,"Loadjs('firehol.progress.php?comand=start')")."</td>
	</tr>";	
	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/reconfigure-32.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{reconfigure_firewall}",null,"Loadjs('firehol.progress.php');")."</td>
	</tr>";
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/32-install-soft.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{reinstall_firewall}",null,"Loadjs('firehol.wizard.install.progress.php?ask=yes');")."</td>
	</tr>";	
	
	$tr[]="
	<tr>
	<td valign='middle' style='width:25px'><img src='img/stop2-32.png'></td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{disable_firewall}",null,"Loadjs('firehol.wizard.disable.progress.php');")."</td>
	</tr>";	
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	
	
	
	
}


function cache_section(){
	
	$ahref_caches="<a href=\"javascript:blur();\"
			OnClick=\"javascript:GoToCaches();\">";
	
}


function bridge_section(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$icon="arrow-right-24.png";
	
	
	$tr[]="";
	$tr[]="<table style='width:100%'>";


	
	if($users->AsFirewallManager){
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{web_console}","position:right:{web_console}","s_PopUpFull('fw.index.php',1024,768,'Firewall Management console')")."</td>
	</tr>";
	}
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/info-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{video}: FireWall administrators privileges",
			"position:right:{video}","s_PopUpFull('https://youtu.be/6t779Na9MwQ',1024,768,'FireWall administrators privileges')")."</td>
	</tr>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/info-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{video}: FireWall with Active Directory group privilege",
				"position:right:{video}","s_PopUpFull('https://youtu.be/2KHo8oxpbJk',1024,768,'FireWall administrators privileges with Active Directory')")."</td>
	</tr>";
		
	
	
	

	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
}

function control_section(){
	$sock=new sockets();
	$tpl=new templates();
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("Active Directory","position:right:{dashboard_activedirectory_explain}","GoToActiveDirectory()")."</td>
	</tr>";
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	
	
	
}

function monitor_section(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$UlogdEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UlogdEnabled"));
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	
	$text_ids=null;
	$icon_ids="arrow-right-24.png";
	$color_ids="black";
	$js_ids="GotoSuricata()";
	
	
	$text_ulogd=null;
	$icon_ulogd="arrow-right-24.png";
	$color_ulogd="black";
	$js_ulogd="GotoUlogd()";
	
	
	$EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
	
	if($EnableSuricata==0){
		$icon_ids="arrow-right-24-grey.png";
		$color_ids="#898989";
		$text_ids=" <span style='font-size:12px'>({disabled})</span>";
	}
	
	if(!is_file("/usr/bin/suricata")){
		$icon_ids="arrow-right-24-grey.png";
		$color_ids="#898989";
		$text_ids=" <span style='font-size:12px'>({not_installed})</span>";
		$js_ids="blur();";
	}
	
	
	if($UlogdEnabled==0){
		$icon_ulogd="arrow-right-24-grey.png";
		$color_ulogd="#898989";
		$text_ulogd=" <span style='font-size:12px'>({disabled})</span>";
			
		
	}
	
	
	if(!$users->APP_ULOGD_INSTALLED){
		$icon_ulogd="arrow-right-24-grey.png";
		$color_ulogd="#898989";
		$text_ulogd=" <span style='font-size:12px'>({not_installed})</span>";
		$js_ulogd="blur();";
		
	}
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ids'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_ids'>".texttooltip("{IDS}$text_ids",
			"position:right:{IDS}",$js_ids)."</td>
		</tr>";	

/*
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_ulogd'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_ulogd'>".
	texttooltip("{APP_ULOGD}$text_ulogd","position:right:{APP_ULOGD_EXPLAIN}","$js_ulogd")."</td>
	</tr>";	
	
*/	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{traffic_analysis}","position:right:{traffic_analysis_explain}","GotoNTOPNG()")."</td>
	</tr>";
	
		$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	
	
	
	
}

function nat_section(){
	$sock=new sockets();
	$tpl=new templates();

	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/add-24.png'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{new_nat}",
			"position:right:{nat_title}","Loadjs('system.network.nat.php?rule-js=yes&ID=0&t=".time()."',true);")."</td>
	</tr>";	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon'>
	</td>
	<td valign='middle' style='font-size:18px;width:99%'>".texttooltip("{rules}","position:right:{nat_title}","GotoNATRules()")."</td>
	</tr>";
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}

function nics_section(){
	$sock=new sockets();
	$tpl=new templates();
	$datas=TCP_LIST_NICS();
	
	
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	
	while (list ($num, $val) = each ($datas) ){
		writelogs("Found: $val",__FUNCTION__,__FILE__,__LINE__);
		$val=trim($val);
		$nic=new system_nic($val);
		if($nic->enabled==0){continue;}
	
		$BEHA["reject"]="{strict_mode}";
		$BEHA["accept"]="{trusted_mode}";
		
		
		$BEHA2[0]="{not_defined}";
		$BEHA2[1]="{act_as_lan}";
		$BEHA2[2]="{act_as_wan}";
		
		$b1=$BEHA2[$nic->firewall_behavior]."/".$BEHA[$nic->firewall_policy];

		if($nic->firewall_artica==1){
			$b1=$b1."<br>{accept_artica_w}";
		}
	
		$tr[]="<tr>
		<td valign='middle' style='width:25px'>
		<img src='img/interfaces-24.png'>
		</td>
		<td valign='middle' style='font-size:20px;width:99%'>".texttooltip("$val: $nic->NICNAME<br><span style='font-size:14px'>$b1</span>",
					"position:right:$nic->IPADDR - $nic->netzone","GoToNicFirewallConfiguration('$val')")."</td>
		</tr>

								
		";
	}
	

	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));	

}
function services_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$IPSetInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSetInstalled"));
	$EnableIpBlocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIpBlocks"));
	$icon="arrow-right-24.png";
	
	$tr[]="<table style='width:100%'>";
	

	
	$EnableSecureGateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSecureGateway"));
	$icon_secure_gateway="arrow-right-24.png";
	$color_secure_gateway="black";
	$text_secure_gateway=null;
	
	$block_countries_icon="arrow-right-24.png";
	$block_countries_color="black";
	$block_countries_js="GotoIpBlocks()";
	
	$RulesStatus_icon="arrow-right-24.png";
	$RulesStatus_color="black";
	$RulesStatus_js="Loadjs('$page?RulesStatus-js=yes')";
	
	$RestartStatus_icon="arrow-right-24.png";
	$RestartStatus_color="black";
	$RestartStatus_js="Loadjs('$page?RestartStatus-js=yes')";
	
	$size=@filesize("/etc/artica-postfix/settings/Daemons/FireHoleDumpRules");
	if($size<20){
		$RulesStatus_icon="arrow-right-24-grey.png";
		$RulesStatus_color="#898989";
		$RulesStatus_js="blur()";
		
	}
	
	$size=@filesize("/etc/artica-postfix/settings/Daemons/FireHoleRestartDump");
	if($size<20){
		$RestartStatus_icon="arrow-right-24-grey.png";
		$RestartStatus_color="#898989";
		$RestartStatus_js="blur()";
	
	}
	
	$block_countries_text=null;
	
	if($EnableSecureGateway==0){
		$icon_secure_gateway="arrow-right-24-grey.png";
		$color_secure_gateway="#898989";
		$text_secure_gateway=" <span style='font-size:12px'>({disabled})</span>";
	}
	
	if($EnableIpBlocks==0){
		$block_countries_icon="arrow-right-24-grey.png";
		$block_countries_color="#898989";
		$block_countries_text=" <span style='font-size:12px'>({disabled})</span>";
		
	}
	
	if($IPSetInstalled==0){
		$block_countries_icon="arrow-right-24-grey.png";
		$block_countries_color="#898989";
		$block_countries_text=" <span style='font-size:12px'>({ERROR_IPSET_NOT_INSTALLED})</span>";
		$block_countries_js="blur();";
	}
	
	
	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$RulesStatus_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$RulesStatus_color'>".texttooltip("{config_file}","{all_rules}","$RulesStatus_js")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$RestartStatus_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$RestartStatus_color'>".texttooltip("{restart} {events}","{all_rules}","$RestartStatus_js")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/arrow-right-24.png'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:black'>".texttooltip("{objects}","{objects}","GoToSquidAclsGroups()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$block_countries_icon'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$block_countries_color'>".texttooltip("{block_countries}$block_countries_text","{ipblocks_text}","GotoIpBlocks()")."</td>
	</tr>";	
	
	$tr[]="<tr>
	<td valign='middle' style='width:25px'>
	<img src='img/$icon_secure_gateway'></td>
	<td valign='middle' style='font-size:18px;width:99%;color:$color_secure_gateway'>".texttooltip("{secure_gateway}$text_secure_gateway","{secure_gateway_explain}","GotoGatewaySecure()")."</td>
	</tr>";
	
	$tr[]="</table>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
}

