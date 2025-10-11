<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["EnableDNSRootInts"])){save();exit;}
if(isset($_GET["unbound-status"])){unbound_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_UNBOUND} v{$UnboundVersion}</h1>
	<p>{didyouknow_unbound}</p>
	</div>

	</div>



	<div class='row'><div id='progress-unbound-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-dns-servers'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/dns-cache');
	LoadAjax('table-loader-dns-servers','$page?tabs=yes');

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}
function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog1("{APP_UNBOUND} >> {config_file}", "$page?config-file-popup=yes");

}
function config_file_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("sshd.php?config-file=yes");
	$data=@file_get_contents("/etc/unbound/unbound.conf");
	$form[]=$tpl->field_textareacode("configfile", null, $data);


	echo $tpl->form_outside("{config_file}", @implode("", $form),"{display_generated_configuration_file}",null,"","AsSystemAdministrator");

}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$MUNIN=false;
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
	if($MUNIN_CLIENT_INSTALLED==1){if($EnableMunin==1){$MUNIN=true;}}

	$array["{status}"]="$page?table=yes";
    $array["{networks_restrictions}"]="fw.pdns.restrictions.php?tinypage-unbound=yes";
	$array["{cache}"]="fw.dns.unbound.cache.php";
	
	$EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
	
	if($EnableDNSCryptProxy==1){
		$array["{APP_DNSCRYPT_PROXY}"]="fw.dnscrypt-proxy.php";
	}
	
	
	
	
	if($MUNIN){
		$array["{statistics}"]="fw.unbound.statistics.php";
	}
	echo $tpl->tabs_default($array);

}

function unbound_status(){
	
	$sock=new sockets();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
	$bsini=new Bs_IniHandler(PROGRESS_DIR."/dnsdist.status");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
	$PDNSStatsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsEnabled"));
	
    $jsRestart=$tpl->framework_buildjs("/unbound/restart",
        "unbound.restart.progress","unbound.restart.log",
        "progress-unbound-restart","LoadAjaxSilent('unbound-status','$page?unbound-status=yes');");

	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
	$ARRAY["CMD"]="pdns.php?restart-dsc=yes";
	$ARRAY["TITLE"]="{APP_DSC} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDSC="Loadjs('fw.progress.php?content=$prgress&mainid=progress-unbound-restart')";
	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
	$ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
	$ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('unbound-status','$page?unbound-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDNSCrypt="Loadjs('fw.progress.php?content=$prgress&mainid=progress-unbound-restart')";
	

	
	
	
	
	$btn_config=$tpl->button_autnonome("{config_file}", "Loadjs('$page?config-file-js=yes')", "fas fa-file-code","AsSystemAdministrator",335);
	
	$final[]=$tpl->SERVICE_STATUS($bsini, "APP_UNBOUND",$jsRestart);

	if($PDNSStatsEnabled==1) {
        $final[] = $tpl->SERVICE_STATUS($bsini, "APP_DSC", $jsRestartDSC);
    }


    if($EnableDNSCryptProxy==1) {
        $final[] = $tpl->SERVICE_STATUS($bsini, "APP_DNSCRYPT_PROXY", $jsRestartDNSCrypt);
    }


    $final[]="$btn_config";
    VERBOSE(count($final)." row",__LINE__);
    echo $tpl->_ENGINE_parse_body($final);
	
}

function table(){

	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$resolv=new resolv_conf();
	$users=new usersMenus();
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	$EnableDNSRootInts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSRootInts"));
	$UnboundDisplayVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundDisplayVersion"));
	
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMinTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMinTTL", 3600);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheMAXTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheMAXTTL", 172800);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/UnBoundCacheNEGTTL")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnBoundCacheNEGTTL", 3600);}



    $ipclass=new IP();
	$UnBoundCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheSize"));
	$UnBoundCacheMinTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
	$UnBoundCacheMAXTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));
	$UnBoundCacheNEGTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheNEGTTL"));
	$UnboundOutGoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundOutGoingInterface"));
	$EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
	$ListenOnlyLoopBack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ListenOnlyLoopBack"));
	$EnableUnboundLogQueries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundLogQueries"));

    $UnboundLogSyslogServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundLogSyslogServer"));
    $UnboundLogSyslogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundLogSyslogServerPort"));
    $UnboundLogSyslogServerTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundLogSyslogServerTCP"));
    $UnboundLogSyslogUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundLogSyslogUseSSL"));
    $UnboundLogSyslogCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundLogSyslogCertificate"));
    if($UnboundLogSyslogServerPort==0){$UnboundLogSyslogServerPort=514;}
    $UnboundLogSyslogDoNotStorelogsLocally=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundLogSyslogDoNotStorelogsLocally"));

	$forcesafesearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoogleSafeSearchAddress"));
	if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}
	if($forcesafesearch==null){$forcesafesearch=$GLOBALS["CLASS_SOCKETS"]->gethostbyname("forcesafesearch.google.com");}
	if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}

	$UnboundTLSEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSEnable"));
    $UnboundTLSCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSCertificate"));


    if($UnBoundCacheMinTTL==0){$UnBoundCacheMinTTL=3600;}
    if($UnBoundCacheMAXTTL==0){$UnBoundCacheMAXTTL=172800;}
    if($UnBoundCacheNEGTTL==0){$UnBoundCacheNEGTTL=3600;}


    $TIMES[-1]="{not_used}";
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
    $TIMES[28800]="8 {hours}";
    $TIMES[57600]="16 {hours}";
    $TIMES[86400]="1 {day}";
    $TIMES[172800]="2 {days}";
    $TIMES[604800]="7 {days}";
	
	if($UnBoundCacheSize==0){$UnBoundCacheSize=100;}
	
	$PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
	
	$InComingInterfaces=@implode(",", $PowerDNSListenAddr);
	
	
	
	$form[]=$tpl->field_checkbox("UnboundDisplayVersion","{display_servername_version}",$UnboundDisplayVersion,false,null);
	$form[]=$tpl->field_checkbox("EnableDNSRootInts","{EnableDNSRootInts}",$EnableDNSRootInts,false,"{EnableDNSRootInts_explain}");
	
	
	$form[]=$tpl->field_interfaces_choose("InComingInterfaces", "{listen_interfaces}", $InComingInterfaces);
	$form[]=$tpl->field_checkbox("ListenOnlyLoopBack","{listen_only_loopback}",$ListenOnlyLoopBack);
	$form[]=$tpl->field_interfaces("UnboundOutGoingInterface", "{outgoing_interface}", $UnboundOutGoingInterface);

    $form[] = $tpl->field_section("{DNSOTLS}","{DNSOTLS_EXPLAIN}");


    $UnboundTLSEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSEnable"));
    $UnboundTLSCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSCertificate"));
    $UnboundTLSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundTLSPort"));
    if($UnboundTLSPort==0){$UnboundTLSPort=853;}

    $form[] = $tpl->field_checkbox("UnboundTLSEnable", "{DNSOTLS_ENABLE}", $UnboundTLSEnable,"UnboundTLSCertificate","UnboundTLSPort");
    $form[]=$tpl->field_numeric("UnboundTLSPort","{listen_port}",$UnboundTLSPort);
    $form[]=$tpl->field_certificate("UnboundTLSCertificate","{certificate}",$UnboundTLSCertificate);


    $form[] = $tpl->field_section("{events}","");
    $form[]=$tpl->field_checkbox("EnableUnboundLogQueries","{log_queries}",$EnableUnboundLogQueries);
    $form[] = $tpl->field_checkbox("UnboundLogSyslogDoNotStorelogsLocally", "{not_store_log_locally}", $UnboundLogSyslogDoNotStorelogsLocally);


    $form[] = $tpl->field_section("{cache}");
	$form[]=$tpl->field_numeric("UnBoundCacheSize", "{cache_size} (MB)", $UnBoundCacheSize);
	$form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheMinTTL", "{cache-ttl} (Min)", $UnBoundCacheMinTTL);
	$form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheMAXTTL", "{cache-ttl} (Max)", $UnBoundCacheMAXTTL);
	$form[]=$tpl->field_array_hash($TIMES, "UnBoundCacheNEGTTL", "{negquery-cache-ttl}", $UnBoundCacheNEGTTL);
	
	$form[]=$tpl->field_checkbox("EnableUnboundBlackLists","{activate_dns_blacklists}",$EnableUnboundBlackLists,false,"{activate_dns_blacklists_explain}");

	

	$myform=$tpl->form_outside("{local_dns_service}", @implode("\n", $form),null,"{apply}","Loadjs('fw.dns.unbound.restart.php');","AsDnsAdministrator");
	
	$html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'><div id='unbound-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('unbound-status','$page?unbound-status=yes');</script>	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	if($_POST["UnboundEnabled"]==0){$_POST["EnableUnboundBlackLists"]=0;}
	$EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
	
	if(isset($_POST["InComingInterfaces"])){
		$array=explode(",",$_POST["InComingInterfaces"]);
		$sock->SaveConfigFile(@implode("\n", $array), "PowerDNSListenAddr");
		unset($_POST["InComingInterfaces"]);
	}
	
	
	
	foreach ($_POST as $key=>$val){
		$val=url_decode_special_tool($val);
		$sock->SET_INFO($key, $val);
	}
	
	if($_POST["EnableUnboundBlackLists"]<>$EnableUnboundBlackLists){$sock->getFrameWork("unbound.php?blacklists-enable=yes");}
	
	
		
	
	
	
}




