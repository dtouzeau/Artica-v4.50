<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["dynacls"])){dynacls();exit;}
if(isset($_GET['app-status'])){app_status();exit;}
if(isset($_GET["flot1"])){flot1();exit;}
if(isset($_GET["flot2"])){flot2();exit;}
if(isset($_GET["flot3"])){flot3();exit;}
if(isset($_GET["flot4"])){flot4();exit;}
if(isset($_GET["flot5"])){flot5();exit;}
xgen();



function xgen(){
$OPENVPN=false;	
$users=new usersMenus();
$page=CurrentPageName();
$subtitle=null;

if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NUTANIX_HOST"))==1){$subtitle="Nutanix Edition";}
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XEN_HOST"))==1){$subtitle="XenServer Edition";}
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST"))==1){$subtitle="VMWare Edition";}
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("QEMU_HOST"))==1){$subtitle="Qemu Edition";}


$title="{your_system}";
$hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
if($hostname==null){$sock=new sockets();$hostname=$sock->getFrameWork("system.php?hostname-g=yes");}
if($hostname==null){$hostname="localhost.localdomain";}

$Server_infos="<a class='btn btn-white btn-bitbucket' 
	OnClick=\"ShowHideSysinfos()\">
		<i class='fa fa-wrench'></i></a>";

if(!$users->AsAnAdministratorGeneric){$Server_infos=null;}

$t=time();
$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\">
	<h1 class=ng-binding>$title <span id='title-hostname'>$hostname</span>	$Server_infos</h1>
	<div style='margin-top:-17px;margin-bottom:-20px;text-align:left;margin-left:427px'>$subtitle</div>
	</div>
</div>
<div class=\"wrapper wrapper-content animated fadeInRight\">
	<div class=row id='applications-status'></div>
	<div class=row id='applications-status2'></div>
</div>
<script>
	LoadAjaxSilent('applications-status','fw.system.status.php');
</script>
";

if(isset($_GET["content"])) {
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	return;
}

$tpl=new template_admin($title,$html);
echo $tpl->build_firewall("choose-proxy=yes");

}



function ulogd_status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->APP_ULOGD_INSTALLED){return $tpl->status_array("{APP_ULOGD}","{not_installed}",false,true);}
	$UlogdEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UlogdEnabled"));
	$UlogdVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UlogdVersion");
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if($FireHolEnable==0){$UlogdEnabled=0;}
	if($UlogdEnabled==0){return $tpl->status_array("{APP_ULOGD}",null,false,true,"v$UlogdVersion");}
	
	$sock->getFrameWork('ulogd.php?status=yes');
	$ini=new Bs_IniHandler("ressources/logs/web/ulogd.status");
	return $tpl->DAEMON_STATUS_ROW("APP_ULOGD",$ini,null,0);

}



function ip_audit_status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->IPAUDIT_INSTALLED){return $tpl->status_array("{APP_IPAUDIT}","{not_installed}",false,true);}
	$IpAuditEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IpAuditEnabled"));
	$IpAuditVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IpAuditVersion");
	if($IpAuditVersion<>null){$IpAuditVersion="v{$IpAuditVersion}";}
	if($IpAuditEnabled==0){return $tpl->status_array("{APP_IPAUDIT}","$IpAuditVersion",false,true);}
	
	$sock->getFrameWork('ipaudit.php?status=yes');
	$ini=new Bs_IniHandler("ressources/logs/web/ipaudit.status");
	return $tpl->DAEMON_STATUS_ROW("APP_IPAUDIT",$ini,null,0);
	
}

function suricata_status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->SURICATA_INSTALLED){return $tpl->status_array("{IDS}","{not_installed}",false,true);}
	$EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
	$SuricataVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataVersion");
	if($EnableSuricata==0){return $tpl->status_array("{IDS}","v$SuricataVersion",false,true);}
	
	$sock->getFrameWork('suricata.php?daemon-status=yes');
	$ini=new Bs_IniHandler("ressources/logs/web/suricata.status");
	return $tpl->DAEMON_STATUS_ROW("IDS",$ini,null,0);
}
function openvpn_status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$users=new usersMenus();
	
	if(!$users->OPENVPN_INSTALLED){return $tpl->status_array("{APP_OPENVPN}","{not_installed}",false,true);}
	$EnableOpenVPN=0;
	$vpn=new openvpn();
	if($vpn->main_array["GLOBAL"]["ENABLE_SERVER"]==1){$EnableOpenVPN=1;}
	$OpenVPNVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNVersion");
	if($OpenVPNVersion<>null){$OpenVPNVersion="v{$OpenVPNVersion}";}
	if($EnableOpenVPN==0){return $tpl->status_array("{APP_OPENVPN}","$OpenVPNVersion",false,true);}
	$OpenVPNCNXNUmber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNCNXNUmber"));
	
	$sock->getFrameWork("cmd.php?openvpn-status=yes");
	$ini=new Bs_IniHandler("ressources/logs/web/openvpn.status");
	return $tpl->DAEMON_STATUS_ROW("OPENVPN_SERVER",$ini,"$OpenVPNCNXNUmber {sessions}");
}
function flot1(){
	
	$data=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/FIREWALL.IPAUDIT.24H"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeMB($data["ip1bytes"],$_GET["id"]);
	
			
}
function flot2(){
	$data=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/FIREWALL.IPAUDIT.24H"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeMB($data["ip2bytes"],$_GET["id"]);
}

function flot3(){
	$data=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNStatsnClients"));
	$tpl=new template_admin();
	$tpl->graph_date_line_int($data,$_GET["id"]);
}
function flot4(){
	$data=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNStatsBytesIn"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeKB($data,$_GET["id"]);
}
function flot5(){
	$data=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNStatsBytesOut"));
	$tpl=new template_admin();
	$tpl->graph_date_line_sizeKB($data,$_GET["id"]);
}

function dynacls(){
	
}

