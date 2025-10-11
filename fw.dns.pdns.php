<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["PowerDNSLogLevel"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["pdns-status"])){pdns_status();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}

page();

function ufdbdebug_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{debug_mode}", "$page?ufdbdebug-popup=yes");

}
function ufdbconf_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{file_configuration}", "$page?ufdbconf-popup=yes");
	
}
function pdns_status(){
    $page=CurrentPageName();
    $ini=new Bs_IniHandler();
    $sock=new sockets();
    $tpl=new template_admin();
//    $sock->getFrameWork("pdns.php?status=yes");
//    $ini->loadFile(PROGRESS_DIR."/pdns.status");
    $data=$sock->REST_API("/pdns/status");
    $json=json_decode($data);

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.restart.log";
    $ARRAY["CMD"]="pdns.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('table-pdnsstatus','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $pdns_restart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pdns-restart');";

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/recusor.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/recusor.restart.log";
    $ARRAY["CMD"]="pdns.php?restart-recusor=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('table-pdnsstatus','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $recusrsor_restart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pdns-restart');";



	$html="
<div class=\"ibox-content\" style='border-top:0px'>". $tpl->SERVICE_STATUS($ini, "APP_PDNS",$pdns_restart)."</div>
 	<div class=\"ibox-content\" style='border-top:0px'>". $tpl->SERVICE_STATUS($ini, "APP_PDNS_CLIENT",$pdns_restart)."</div>
   	<div class=\"ibox-content\" style='border-top:0px'>". $tpl->SERVICE_STATUS($ini, "PDNS_RECURSOR",$recusrsor_restart)."</div>";

	echo $tpl->_ENGINE_parse_body($html);

	
}



function tabs(){
	$HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$users=new usersMenus();
	$array["{service_status}"]="$page?table=yes";
	$PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
	
	
	if($PowerDNSEnableRecursor==1){
		$array["{networks_restrictions}"]="fw.pdns.restrictions.php?tinypage-unbound=yes";
	}
	
	if($HideCorporateFeatures==0){
		$array["{backup}"]="fw.system.tasks.php?sub-main=yes&ForceTaskType=82";

	}
	
	echo $tpl->tabs_default($array);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");

    $html=$tpl->page_header("{APP_PDNS} $APP_PDNS_VERSION &raquo;&raquo; {service_parameters}",
        "fa-regular fa-screwdriver-wrench",
        "{APP_PDNS_EXPLAIN}","$page?table=yes","pdns-config","progress-pdns-restart",false,"table-pdnsstatus");

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: PowerDNS Parameters",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px' valign='top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
    $html[]="<td valign='top' style='width:400px'>";
    $html[]="<div class=\"ibox\" style='width:400px' id='pdns-status'></div>";
    $html[]="</td>";
    $html[]="</tr>";
	

	$PowerDNSMaxCacheEntries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSMaxCacheEntries"));
	$PowerDNSMaxPacketCacheEntries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSMaxPacketCacheEntries"));
	
	//$PowerDNSLogsQueries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSLogsQueries"));
	$PDNSRestartIfUpToMB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRestartIfUpToMB"));
	$PowerDisableDisplayVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDisableDisplayVersion"));
	$PowerActHasMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerActHasMaster"));
	$PowerDNSDNSSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSDNSSEC"));
	
	$PowerChroot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerChroot"));
	$PowerActAsSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerActAsSlave"));
	$PowerDNSLogLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSLogLevel"));
	$PowerSkipCname=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerSkipCname"));
	$PowerDNSRecursorQuerLocalAddr=$sock->GET_INFO("PowerDNSRecursorQuerLocalAddr");
	$powerDNSVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSVersion"));
	//$PDNSUseHostsTable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSUseHostsTable"));
	$PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
	$PowerDNSRecursorLogLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSRecursorLogLevel"));
		
	if($PDNSRestartIfUpToMB==0){$PDNSRestartIfUpToMB=700;}
	if($PowerDNSLogLevel==0){$PowerDNSLogLevel=4;}
	if($PowerDNSRecursorLogLevel==0){$PowerDNSRecursorLogLevel=4;}
	if($PowerDNSMaxCacheEntries==0){$PowerDNSMaxCacheEntries=1000000;}
	if($PowerDNSMaxPacketCacheEntries==0){$PowerDNSMaxPacketCacheEntries=1000000;}
	
	$PowerDNSPerfs=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSPerfs"));
	if(!isset($PowerDNSPerfs["cache-ttl"])){$PowerDNSPerfs["cache-ttl"]=3600;}
	if(!isset($PowerDNSPerfs["negquery-cache-ttl"])){$PowerDNSPerfs["negquery-cache-ttl"]=7200;}
	if(!isset($PowerDNSPerfs["query-cache-ttl"])){$PowerDNSPerfs["query-cache-ttl"]=300;}
	if(!isset($PowerDNSPerfs["recursive-cache-ttl"])){$PowerDNSPerfs["recursive-cache-ttl"]=7200;}
	$PDNSQLPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSQLPassword");
	if($PDNSQLPassword==null){$PDNSQLPassword="powerdns";}
	$PDNSRESTFulApiKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRESTFulApiKey"));
	$EnablePDNSRESTFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNSRESTFul"));
	
	
	$TIMES[10]="10 {seconds}";
	$TIMES[20]="20 {seconds}";
	$TIMES[30]="30 {seconds}";
	$TIMES[60]="1 {minute}";
	$TIMES[300]="5 {minutes}";
	$TIMES[900]="15 {minutes}";
	$TIMES[1800]="30 {minutes}";
	$TIMES[3600]="1 {hour}";
	$TIMES[7200]="2 {hours}";
	$TIMES[10800]="3 {hours}";
	$TIMES[14400]="4 {hours}";
	$TIMES[604800]="7 {days}";
	
	
	if(!is_numeric($PDNSRestartIfUpToMB)){$PDNSRestartIfUpToMB=700;}
	$net=new networking();
	$ips=$net->ALL_IPS_GET_ARRAY();
	
	if($PowerDNSRecursorQuerLocalAddr==null){
		$net->ifconfig("eth0");
		if($net->tcp_addr<>null){
			if($net->tcp_addr<>"0.0.0."){
				$PowerDNSRecursorQuerLocalAddr=$net->tcp_addr;
			}
		}
	
	}

	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/pdns.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/pdns.progress.log";
	$ARRAY["CMD"]="pdns.php?update=yes";
	$ARRAY["TITLE"]="{update_databases}";
	$ARRAY["AFTER"]="dialogInstance1.close();LoadAjax('table-pdnsstatus','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsupdate="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pdns-restart');";
	
	$ARRAY=array();
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.restart.log";
	$ARRAY["CMD"]="pdns.php?restart-all=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="dialogInstance1.close();LoadAjax('table-pdnsstatus','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-pdns-restart');";

	for($i=3;$i<10;$i++){$loglevels[$i]=$i;}
	
	
$html[]="</table></td>";

$html[]="<td style='width:99%;vertical-align:top'>";

$PowerDNSRecursorInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSRecursorInterface"));
$PowerDNSListenInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenInterfaces"));
if($PowerDNSListenInterfaces==null){$PowerDNSListenInterfaces="lo,eth0";}

$form[]=$tpl->field_interfaces_choose("PowerDNSListenInterfaces", "{listen_interfaces}", $PowerDNSListenInterfaces);
$form[]=$tpl->field_array_hash($loglevels, "PowerDNSLogLevel", "{log_level}", $PowerDNSLogLevel);
//$form[]=$tpl->field_checkbox("PowerDNSLogsQueries","{log_queries}",$PowerDNSLogsQueries,false,null);


    if($EnablePDNSRESTFul==1){
        $form[]=$tpl->field_text("PDNSRESTFulApiKey","{API_KEY}",$PDNSRESTFulApiKey);


    }




//$form[]=$tpl->field_checkbox("PDNSUseHostsTable","{PDNSUseHostsTable}",$PDNSUseHostsTable,false,"{PDNSUseHostsTable_explain}");
$form[]=$tpl->field_checkbox("PowerActHasMaster","{ActHasMaster}",$PowerActHasMaster,false,"{PDNS_MASTER_EXPLAIN}");
$form[]=$tpl->field_checkbox("PowerActAsSlave","{ActHasSlave}",$PowerActAsSlave,false,null);
$form[]=$tpl->field_checkbox("PowerDNSDNSSEC","{DNSSEC}",$PowerDNSDNSSEC,false,"{DNSSEC_ABOUT}");
$form[]=$tpl->field_checkbox("PowerDisableDisplayVersion","{DisableDisplayVersion}",$PowerDisableDisplayVersion,false,null);
$form[]=$tpl->field_checkbox("PowerChroot","{chroot}",$PowerChroot,false,null);
$form[]=$tpl->field_password("PDNSQLPassword", "{WWWMysqlPassword}", $PDNSQLPassword);

if($PowerDNSEnableRecursor==1){
	$form[]=$tpl->field_section("{APP_PDNS_RECURSOR}");
	$form[]=$tpl->field_interfaces("PowerDNSRecursorQuerLocalAddr", "{listen_interface}", $PowerDNSRecursorQuerLocalAddr);
	$form[]=$tpl->field_interfaces("PowerDNSRecursorInterface", "{outgoing_interface}", $PowerDNSRecursorInterface);
	$form[]=$tpl->field_array_hash($loglevels, "PowerDNSRecursorLogLevel", "{log_level}", $PowerDNSRecursorLogLevel);
	
}

$form[]=$tpl->field_section("{timeouts}");
$form[]=$tpl->field_numeric("PowerDNSMaxCacheEntries","{MaxCacheEntries}",$PowerDNSMaxCacheEntries,"{PowerDNSMaxCacheEntries}");
$form[]=$tpl->field_numeric("PowerDNSMaxPacketCacheEntries","{MaxPacketCacheEntries}",$PowerDNSMaxPacketCacheEntries,"{PowerDNSMaxPacketCacheEntries}");
$form[]=$tpl->field_array_hash($TIMES, "cache-ttl", "{cache-ttl}", $PowerDNSPerfs["cache-ttl"]);
$form[]=$tpl->field_array_hash($TIMES, "negquery-cache-ttl", "{negquery-cache-ttl}", $PowerDNSPerfs["negquery-cache-ttl"]);
$form[]=$tpl->field_array_hash($TIMES, "query-cache-ttl", "{query-cache-ttl}", $PowerDNSPerfs["query-cache-ttl"]);
$form[]=$tpl->field_array_hash($TIMES, "recursive-cache-ttl", "{recursive-cache-ttl}", $PowerDNSPerfs["recursive-cache-ttl"]);





//enable_internet_recursor
$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsDnsAdministrator");
$html[]="</td>";
$html[]="</tr>";

$html[]="</table><script>LoadAjax('pdns-status','$page?pdns-status=yes');</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function Save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	
	
	foreach ($_POST as $key=>$value){
		$PowerDNSPerfs[$key]=$value;
		$sock->SET_INFO($key, $value);
	}
	$sock->SaveConfigFile(base64_encode(serialize($PowerDNSPerfs)), "PowerDNSPerfs");
	$sock->getFrameWork("pdns.php?dnssec=yes");
}


