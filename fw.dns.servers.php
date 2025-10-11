<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["SAVE_DNSDOMAINS"])){dnsdomains_edit_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["table2-status"])){table2_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["SquidDNSUseSystem"])){DNS_PROXY_SAVE();exit;}
if(isset($_POST["DOMAINS1"])){DNS_SERVERS_SAVE();exit;}
if(isset($_GET["namebench"])){NAMEBENCH();exit;}
if(isset($_GET["namebench-start"])){NAMEBENCH_START();exit;}
if(isset($_POST["QUERY_COUNT"])){NAMEBENCH_SAVE();exit;}
if(isset($_GET["namebench-report-js"])){NAMEBENCH_REPORT_JS();exit;}
if(isset($_GET["namebench-report"])){NAMEBENCH_REPORT();exit;}
if(isset($_GET["popup-js"])){popup_js();exit;}
if(isset($_GET["dnscache-status"])){dnscache_status();exit;}
if(isset($_GET["dnscache-add-js"])){dnscache_add_js();exit;}
if(isset($_GET["dnscache-add-popup"])){dnscache_add_popup();exit;}
if(isset($_POST["add_redirect"])){dnscache_add_redirect();exit;}
if(isset($_GET["dnscache-del-js"])){dnscache_del_redirect();exit;}
if(isset($_POST["dnscache-del-js"])){dnscache_del_redirect_confirm();exit;}
if(isset($_GET["dns-edit-js"])){dnscache_edit_js();exit;}
if(isset($_GET["dnscache-edit-popup"])){dnscache_edit_popup();exit;}
if(isset($_GET["dnscache-edit-popup2"])){dnscache_edit_popup2();exit;}
if(isset($_GET["dnscache-edit-popup3"])){dnscache_edit_popup3();exit;}
if(isset($_POST["dnscache-edit"])){dnscache_edit_save();exit;}
if(isset($_POST["dnscache-typ5"])){dnscache_typ5_save();exit;}
if(isset($_GET["dns-domains-js"])){dnsdomains_edit_js();exit;}
if(isset($_GET["dns-domains-popup"])){dnsdomains_edit_popup();exit;}
if(isset($_GET["cloudflared-status"])){cloudflared_status();exit;}

if(isset($_GET["dnscache-edit-options-js"])){dnscache_edit_options_js();exit;}
if(isset($_GET["dnscache-edit-options-popup"])){dnscache_edit_options_popup();exit;}
if(isset($_POST["dnscache-edit-options"])){dnscache_edit_options_save();exit;}

if(isset($_GET["SafeSearch-edit-options-js"])){dnscache_edit_safesearch_js();exit;}
if(isset($_GET["SafeSearch-edit-options-popup"])){dnscache_edit_safesearch_popup();exit;}
if(isset($_POST["EnableGoogleSafeSearch"])){dnscache_edit_safesearch_save();exit;}

if(isset($_GET["dns-standard-js"])){dns_standards_js();exit;}
if(isset($_GET["dns-standard-popup"])){dns_standards_popup();exit;}
if(isset($_GET["restart-localdnscache"])){dnscache_local_restart();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html=$tpl->page_header("{dns_forwarders}","fa fa-server","{dns_servers_explain}","$page?tabs=yes","dns-servers","progress-firehol-restart",false,"table-loader-dns-servers");
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{dns_servers} ",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	
	echo $tpl->_ENGINE_parse_body($html);

}

function popup_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog5("{dns_servers}","$page?table=yes&bypopup=yes",1200);

}
function dnscache_local_restart():bool{
	$sock=new sockets();
	$sock->REST_API("/dnscache/restart");
	sleep(1);
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	return true;
}


function NAMEBENCH_START(){
	$page=CurrentPageName();
	echo "<div style='margin-top:10px' id='namebench-progress'></div><div id='namebench-start'></div><script>LoadAjax('namebench-start','$page?namebench=yes');</script>";
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{dns_servers}"]="$page?table=yes";
	$NAMEBENCH_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NAMEBENCH_INSTALLED"));
	$EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
	if($EnableDNSCryptProxy==1){
		$array["{public_dns_servers}"]="fw.dnscrypt-proxy.list.php";
	}
	
	
	if($NAMEBENCH_INSTALLED==1){
		$array["{dns_benchmark}"]="$page?namebench-start=yes";
	}
	echo $tpl->tabs_default($array);

}

function NAMEBENCH(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidNameServer1=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer1"));
	$SquidNameServer2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNameServer2"));
	$UnboundInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"));
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	if($UnboundInstalled==0){$UnboundEnabled=1;}
	$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
	if($EnablePDNS==1){$DNS[]="127.0.0.1";}
	if($UnboundEnabled==1){$DNS[]="127.0.0.1";}
	if($SquidNameServer1<>null){$DNS[]=$SquidNameServer1;}
	if($SquidNameServer2<>null){$DNS[]=$SquidNameServer2;}
	$resolv=new resolv_conf();
	if($resolv->MainArray["DNS1"]<>null){$DNS[]=$resolv->MainArray["DNS1"];}
	if($resolv->MainArray["DNS2"]<>null){$DNS[]=$resolv->MainArray["DNS2"];}
	if($resolv->MainArray["DNS3"]<>null){$DNS[]=$resolv->MainArray["DNS3"];}
	$NAMEBENCH_ARRAY=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NAMEBENCH_ARRAY"));
	
	$data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NameBenchReport");
	if(strlen($data)>100){
		$html[]=$tpl->button_autnonome("{display_report}", "Loadjs('$page?namebench-report-js')", "fa-pie-chart");
	}
	
	for($i=0;$i<8;$i++){
		if(!isset($NAMEBENCH_ARRAY["DNS$i"])){$NAMEBENCH_ARRAY["DNS$i"]=$DNS[$i];}
		$form[]=$tpl->field_text("DNS$i", "{dns_server}", $DNS[$i]);
		
	}
	if(intval($NAMEBENCH_ARRAY["QUERY_COUNT"])==0){$NAMEBENCH_ARRAY["QUERY_COUNT"]=80;}
	if(intval($NAMEBENCH_ARRAY["PING_TIMEOUT"])==0){$NAMEBENCH_ARRAY["PING_TIMEOUT"]=2;}
	$form[]=$tpl->field_numeric("QUERY_COUNT","{query_count}",$NAMEBENCH_ARRAY["QUERY_COUNT"]);
	$form[]=$tpl->field_numeric("PING_TIMEOUT","{PING_TIMEOUT}",$NAMEBENCH_ARRAY["PING_TIMEOUT"]);
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/admin.dashboard.dnsperfs.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/admin.dashboard.dnsperfs.progress.txt";
	$ARRAY["CMD"]="system.php?dnsperf-progress=yes";
	$ARRAY["TITLE"]="{html_report})";
	$ARRAY["AFTER"]="LoadAjax('namebench-start','$page?namebench=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=namebench-progress')";

	//dns_benchmark

	$TINY_ARRAY["TITLE"]="{dns_benchmark}";
	$TINY_ARRAY["ICO"]="fa-solid fa-signal";
	$TINY_ARRAY["EXPL"]="{check_your_dns_servers_performance}";
	$TINY_ARRAY["URL"]="dns-servers";
	$TINY_ARRAY["BUTTONS"]=null;
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]=$tpl->form_outside("{performance_report}", @implode("\n", $form),
			null,"{launch_scan}",$jsafter,"AsDnsAdministrator");

	$html[]="<script>$jstiny</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function NAMEBENCH_SAVE(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($_POST), "NAMEBENCH_ARRAY");
	
}
function NAMEBENCH_REPORT_JS(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{dns_performance}", "$page?namebench-report=yes");
}

function NAMEBENCH_REPORT(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$dns_performance_time=null;
	$data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NameBenchReport");

	if(preg_match("#<body>(.*?)</body>#is", $data,$re)){$data=$re[1];}
	$data=str_replace("<img src=","</center><center><img src=",$data);

	$DNSPerfsDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NameBenchReport"));
	if($DNSPerfsDate>0){
		$dns_performance_time="&laquo;".$tpl->time_to_date($DNSPerfsDate,true)."&raquo;";
	}

	$html[]="<h2>{dns_performance}&nbsp;&nbsp;$dns_performance_time</h2>";
	$html[]="<div class=NameBenchReport>";
	$html[]=$data;
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function table(){
	$page=CurrentPageName();
	$html="<div id='dns-servers-main-table'></div><script>LoadAjax('dns-servers-main-table','$page?table2=yes');</script>";
	echo $html;
}

function dns_perfs_to_ico($Score){
	$icon="<span class='label label-danger'>{very_low}</span>&nbsp;";
	if($Score==0){
		return "<span class='label label-danger'>{failed}</span>&nbsp;";
	}
	if($Score>20){
		$icon="<span class='label label-warning'>{poor}</span>&nbsp;";
	}
	if($Score>30){
		$icon="<span class='label label-warning'>{medium}</span>&nbsp;";
	}

	if($Score>45){
		$icon="<span class='label label-primary'>{good}</span>&nbsp;";
	}

	return $icon;

}
function dnscache_edit_safesearch_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$tpl->js_dialog2("modal:{options}","$page?SafeSearch-edit-options-popup=yes&function=$function");
	return true;

}



function dnscache_edit_safesearch_save():bool{
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
	$sock=new sockets();
	$sock->REST_API("/unbound/control/reconfigure");
	admin_tracks_post("Saving SafeSearch(s) For DNS Cache service.");
	return true;
}
function dnscache_edit_safesearch_popup():bool{
	$tpl=new template_admin();
	$function=$_GET["function"];
	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	$EnableBraveSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBraveSafeSearch"));
	$EnableDuckduckgoSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDuckduckgoSafeSearch"));
	$EnableYandexSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYandexSafeSearch"));
	$EnablePixabaySafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePixabaySafeSearch"));
	$EnableQwantSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQwantSafeSearch"));
	$EnableBingSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBingSafeSearch"));
	$EnableYoutubeSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYoutubeSafeSearch"));
	$EnbaleYoutubeModerate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnbaleYoutubeModerate"));


	$form[]=$tpl->field_checkbox("EnableGoogleSafeSearch","Google SafeSearch",$EnableGoogleSafeSearch,false,"{safesearch_explain}");
	$form[]=$tpl->field_checkbox("EnableQwantSafeSearch","Qwant SafeSearch",$EnableQwantSafeSearch,false,"{qwant_safesearch_explain}");
	$form[]=$tpl->field_checkbox("EnableBraveSafeSearch","Brave SafeSearch",$EnableBraveSafeSearch,false,"{qwant_safesearch_explain}");
	$form[]=$tpl->field_checkbox("EnableBingSafeSearch","Bing SafeSearch",$EnableBingSafeSearch,false,"");
	$form[]=$tpl->field_checkbox("EnableYoutubeSafeSearch","Youtube (strict)",$EnableYoutubeSafeSearch,false,"");
	$form[]=$tpl->field_checkbox("EnbaleYoutubeModerate","Youtube (Moderate)",$EnbaleYoutubeModerate,false,"");
	$form[]=$tpl->field_checkbox("EnableDuckduckgoSafeSearch","Duckduckgo",$EnableDuckduckgoSafeSearch,"");
	$form[]=$tpl->field_checkbox("EnableYandexSafeSearch","Yandex",$EnableYandexSafeSearch,"");
	$form[]=$tpl->field_checkbox("EnablePixabaySafeSearch","Pixabay",$EnablePixabaySafeSearch,"");

    $page=CurrentPageName();
	$jsRestart="Loadjs('$page?restart-localdnscache');dialogInstance2.close();$function();";


	$html[]="<div id='safesearch-progress' style='margin-top:10px;margin-bottom:10px'></div>";
	$html[]=$tpl->form_outside("", $form,null,"{apply}",$jsRestart,"AsDnsAdministrator",true);
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function dnscache_edit_options_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$tpl->js_dialog2("modal:{options}","$page?dnscache-edit-options-popup=yes&function=$function");
	return true;
}
function dnscache_edit_watchdog_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$tpl->js_dialog2("modal:{options}","$page?dnscache-edit-watchdog-popup=yes&function=$function");
	return true;

}
function dns_standards_js():bool{
	$tpl = new template_admin();
	$page = CurrentPageName();
	return $tpl->js_dialog2("modal:{dns_used_by_the_system}", "$page?dns-standard-popup=yes");

}
function dnscache_add_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$tpl->js_dialog2("modal:{new_dns_server}","$page?dnscache-add-popup=yes&function=$function");
	return true;
}
function dnsdomains_edit_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$tpl->js_dialog2("modal:{InternalDomain}","$page?dns-domains-popup=yes&function=$function");
	return true;

}
function dnscache_edit_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$i=intval($_GET["dns-edit-js"]);
	$title="{nameserver} $i";
	if($i==1){
		$title="{primary_dns}";
	}
	if($i==2){
		$title="{secondary_dns}";
	}

	$tpl->js_dialog2("modal:$title","$page?dnscache-edit-popup=$i&function=$function");
	return true;
}
function dnscache_add_popup(){

	$tpl=new template_admin();
	$page=CurrentPageName();
	$form[]=$tpl->field_hidden("add_redirect", 0);
	$form[]=$tpl->field_hidden("useTLS", "0");
	$form[]=$tpl->field_hidden("recursive", 0);
	$form[]=$tpl->field_text("zone", "{domain}", null,true);
	$form[]=$tpl->field_ipaddr("hostname", "{ipaddr}", null,true);
	$form[]=$tpl->field_numeric("port","{listen_port}",53);

	$function=$_GET["function"];
	$function_js="$function()";

	$bname="{add}";
	$html[]=$tpl->form_outside("{new_forward_zone}", @implode("\n", $form),"{ADD_DNS_ZONE_TEXT}",$bname,
		"$function_js;dialogInstance2.close();","AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function dnscache_edit_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$i=intval($_GET["dnscache-edit-popup"]);

	$html[]="<div id='dnscache-edit-popup-$i'></div>";
	$html[]="<script>LoadAjaxSilent('dnscache-edit-popup-$i','$page?dnscache-edit-popup2=$i&function=$function');</script>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;

}
function dnscache_edit_save():bool{
	$i=intval($_POST["dnscache-edit"]);
	$keyServe="DNS$i";
	$resolv=new resolv_conf();
	$ruletype=$_POST["ruletype"];
	$_SESSION["DNSCACHE_TMP"]=$_POST;

	CurSyslog("Saving$keyServe -> Rule $ruletype");

	if($ruletype==1){
		admin_tracks("Save Public DNS server $i as Google");
		$resolv->MainArray[$keyServe]="8.8.8.8";
		$resolv->save();
	}
	if($ruletype==2){
		admin_tracks("Save Public DNS server $i as Cloudflare");
		$resolv->MainArray[$keyServe]="1.1.1.1";
		$resolv->save();
	}
	if($ruletype==3){
		admin_tracks("Save Public DNS server $i as Quad9");
		$resolv->MainArray[$keyServe]="9.9.9.9"; //https://dns.quad9.net/dns-query
		$resolv->save();
	}
	if($ruletype==4){
		admin_tracks("Save Public DNS server $i as OpenDNS");
		$resolv->MainArray[$keyServe]="208.67.222.222"; //https://doh.opendns.com/dns-query
		$resolv->save();
	}
	if($ruletype==5){
		admin_tracks("Save Public DNS server $i as DoH European servers");
		$resolv->MainArray[$keyServe]="";
		$resolv->save();
	}
	if($ruletype==6){
		admin_tracks("Save Public DNS server $i as DoH European servers");
		$resolv->MainArray[$keyServe]="255.255.255.255";
		$resolv->save();
	}

	if($ruletype==7){
		admin_tracks("Save Public DNS server $i as None");
		$resolv->MainArray[$keyServe]="";
		$resolv->save();
	}
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/restart");

	return true;
}
function CurSyslog($text){
        openlog("rsyslog.conf", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
}
function dnscache_edit_popup3(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$_POST=$_SESSION["DNSCACHE_TMP"];
	$function=$_GET["function"];
	$ruletype=$_POST["ruletype"];
	$i=intval($_POST["dnscache-edit"]);


	$keyServe="DNS$i";


	if($ruletype==1){
		echo "<script>;dialogInstance2.close();$function();</script>";
		return true;
	}
	if($ruletype==2){
		echo "<script>;dialogInstance2.close();$function();</script>";
		return true;
	}
	if($ruletype==3){
		echo "<script>;dialogInstance2.close();$function();</script>";
		return true;
	}
	if($ruletype==4){
		echo "<script>;dialogInstance2.close();$function();</script>";
		return true;
	}
	if($ruletype==7){
		echo "<script>;dialogInstance2.close();$function();</script>";
		return true;
	}
	if($ruletype==6){
		echo "<script>;dialogInstance2.close();$function();</script>";
		return true;
	}
	$resolv=new resolv_conf();
	$form[]=$tpl->field_hidden("dnscache-typ5", "$i");
	$form[]=$tpl->field_ipv4("dnscache-typ5-value","big:",$resolv->MainArray[$keyServe]);

	$title="{nameserver} $i";
	if($i==1){
		$title="{primary_dns}";
	}
	if($i==2){
		$title="{secondary_dns}";
	}

	$function=$_GET["function"];
	$function_js="$function()";


	echo $tpl->form_outside($title,$form,null,"{apply}","$function_js;dialogInstance2.close();","AsDnsAdministrator");
	return true;
}
function dnscache_typ5_save():bool{
	$i=intval($_POST["dnscache-typ5"]);
	$ip=$_POST["dnscache-typ5-value"];
	$keyServe="DNS$i";
	$resolv=new resolv_conf();
	$resolv->MainArray[$keyServe]=$ip;
	$resolv->save();
	return true;
}

function dnscache_edit_options_popup():bool{
	$resolv=new resolv_conf();
	$tpl=new template_admin();
	$DnsProxyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsProxyCache"));
	$DNSProxyOutGoing=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSProxyOutGoing");


	$DNSCacheListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCacheListenInterface"));
	if($DnsProxyCache==0){$DnsProxyCache=10485760;}
	$DnsProxyCacheKB=$DnsProxyCache/1024;
	$DnsProxyCacheMB=round($DnsProxyCacheKB/1024);
	$form[]=$tpl->field_hidden("dnscache-edit-options","yes");
	$form[]=$tpl->field_numeric("TIMEOUT", "{xtimeout} ({seconds})", $resolv->MainArray["TIMEOUT"]);
	$form[]=$tpl->field_numeric("DnsProxyCache", "{ipcache_size} (MB)",$DnsProxyCacheMB);


	$form[]=$tpl->field_section("{listen_interface}","{LOCAL_CACHE_DNS_INTERFACE}");
	$form[]=$tpl->field_interfaces("DNSCacheListenInterface","nooloopNone:{listen_interface}",$DNSCacheListenInterface);

	$form[]=$tpl->field_interfaces("DNSProxyOutGoing","nooloopNone:{outgoing_interface}",$DNSProxyOutGoing);

	$function=$_GET["function"];
	$function_js="$function()";
	echo $tpl->form_outside(null,$form,null,"{apply}","$function_js;dialogInstance2.close();","AsDnsAdministrator");
	return true;
}
function dnscache_edit_options_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$MB=$_POST["DnsProxyCache"];
	$KB=$MB*1024;
	$BYTES=$KB*1024;
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsProxyCache",$BYTES);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSCacheListenInterface",$_POST["DNSCacheListenInterface"]);
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/restart");
	admin_tracks_post("Save global DNS parameters");
	return true;
}


function dnscache_edit_popup2():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=$_GET["function"];
	$i=intval($_GET["dnscache-edit-popup2"]);
	$dnstype[1]="<strong>Google</strong><br>Google {APP_DOH_BACKEND}";
	$dnstype[2]="<strong>Cloudflare</strong><br>Cloudflare {APP_DOH_BACKEND}";
	$dnstype[3]="<strong>Quad9</strong><br>Quad9 {APP_DOH_BACKEND}";
	$dnstype[4]="<strong>OpenDNS</strong><br>OpenDNS {APP_DOH_BACKEND}";
	$dnstype[5]="<strong>{DNS_SERVER}</strong><br>{DNS_SERVER} {standard}";
	$dnstype[6]="<strong>{EuropeanpublicDNSresolvers}</strong><br>{EuropeanpublicDNSresolvers_explain}";
	$dnstype[7]="<strong class='text-danger'>{none}</strong><br>{do_not_use}";



	$title="{nameserver} $i";
	if($i==1){
		$title="{primary_dns}";
	}
	if($i==2){
		$title="{secondary_dns}";
	}


	$jsafter="LoadAjaxSilent('dnscache-edit-popup-$i','$page?dnscache-edit-popup3=$i&function=$function');";

	$keyServe="DNS$i";
	$resolv=new resolv_conf();
	$ipadr=$resolv->MainArray[$keyServe];
	$main["8.8.8.8"]=1;
	$main["1.1.1.1"]=2;
	$main["9.9.9.9"]=3;
	$main["208.67.222.222"]=4;
	$main["255.255.255.255"]=6;
	if(!isset($main[$ipadr])){
		$type=5;
	}else{
		$type=$main[$ipadr];
	}
	$form[]=$tpl->field_hidden("dnscache-edit", "$i");
	$form[]=$tpl->field_hidden("enabled","1");
	$form[]=$tpl->field_array_checkboxes2Columns($dnstype,"ruletype",$type,false,null);
	$html=$tpl->form_outside($title." $ipadr",$form,null,"{next}",$jsafter,"AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	return true;

}



function dnscache_del_redirect():bool{
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ID=intval($_GET["dnscache-del-js"]);
	$ligne=$q->mysqli_fetch_array("SELECT * FROM pdns_fwzones WHERE ID='$ID'");
	$hostname=$ligne["hostname"].":".$ligne["port"];
	$zone=trim($ligne["zone"]);
	$tpl=new template_admin();
	$md=$_GET["md"];
	$function_js="$('#$md').remove()";
	$tpl->js_confirm_delete("$zone &raquo;&raquo; $hostname","dnscache-del-js",$ID,$function_js);
	return true;

}
function dnscache_del_redirect_confirm():bool{
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ID=intval($_POST["dnscache-del-js"]);
	$ligne=$q->mysqli_fetch_array("SELECT * FROM pdns_fwzones WHERE ID='$ID'");
	$hostname=$ligne["hostname"].":".$ligne["port"];
	$zone=trim($ligne["zone"]);
	$tpl=new template_admin();
	$q->QUERY_SQL("DELETE FROM pdns_fwzones WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return false;}
	admin_tracks("Remove DNS frowarder $hostname for zone $zone");
	return true;
}
function dnscache_add_redirect():bool{
	$ipv4=new IP();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	if($_POST["hostname"]==null){return false;}
	if($_POST["zone"]==null){return false;}
	if(!$ipv4->isValid($_POST["hostname"])){
		echo "Invalid {$_POST["hostname"]}\n";
		return false;
	}
	if(!is_numeric($_POST["port"])){$_POST["port"]=53;}
	$sql="INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS) VALUES('{$_POST["zone"]}','{$_POST["port"]}','{$_POST["hostname"]}','0}','0')";

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
	admin_tracks_post("Add new DNS redirectory in local DNS Cache");
	return true;
}

function cloudflared_status():bool{
	$tpl=new template_admin();
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cloudflared/status"));

	if (json_last_error()> JSON_ERROR_NONE) {
		echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
		return false;

	}else {
		if (!$json->Status) {
			echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
			return false;
		} else {
			$ssh_status = new Bs_IniHandler();
			$ssh_status->loadString($json->Info);
			echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ssh_status,
				"APP_CLOUDFLARE_DNS", ""));
		}
	}
	return true;
}
function dnsdist_status():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	//fw.dns.unbound.php?dnsdist-status-left=yes
	$data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
	$json = json_decode($data);
	if (json_last_error() > JSON_ERROR_NONE) {
		echo $tpl->div_error("ARTICA REST API ERROR||" . json_last_error_msg());
	} else {
		if (!$json->Status) {
			echo $tpl->div_error("ARTICA REST API ERROR||" .$json->Error);
		}
	}

	$bsini = new Bs_IniHandler(PROGRESS_DIR . "/dnsdist.status");

	$APP_DNSDIST_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
	preg_match("#^([0-9]+)\.([0-9]+)#", $APP_DNSDIST_VERSION, $re);
	$major = $re[1];
	$minor = $re[2];
	if ($major == 1) {
		if ($minor < 6) {
			$help_url = "https://wiki.articatech.com/dns/load-balancer/upgrading";
			$js_help = "s_PopUpFull('$help_url','1024','900');";
			$tpl = new template_admin();
			$upgrade_required_software = $tpl->_ENGINE_parse_body("{upgrade_required_software}");
			$upgrade_required_software = str_replace("%s", $APP_DNSDIST_VERSION, $upgrade_required_software);
			echo $tpl->div_error("{upgrade_required}||$upgrade_required_software)||$js_help");

		}
	}

	echo "<div style='margin-top:10px;'>";
	echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($bsini, "APP_DNSDIST", ""));
	echo "</div>";
	return true;

}
function unbound_status():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$UnboundRedisEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"));

	$tpl=new template_admin();
	$sock=new sockets();
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/status"));
	$bsini=new Bs_IniHandler();
	$bsini->loadString($json->Info);
	$EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
	$PDNSStatsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsEnabled"));

	$jsRestart=$tpl->framework_buildjs("/unbound/restart","unbound.restart.progress",
		"unbound.restart.log","dns-apply-system","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')");

	$json=json_decode($sock->REST_API("/dns/collector/status"));
	$bsiniCollector=new Bs_IniHandler();
	$bsiniCollector->loadString($json->Info);
	$jsRestartCollector=$tpl->framework_buildjs("/dns/collector/restart","dns-collector.progress",
		"dns-collector.log",
		"dns-apply-system","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')");



	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
	$ARRAY["CMD"]="pdns.php?restart-dsc=yes";
	$ARRAY["TITLE"]="{APP_DSC} {restarting_service}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDSC="Loadjs('fw.progress.php?content=$prgress&mainid=dns-apply-system')";


	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
	$ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
	$ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDNSCrypt="Loadjs('fw.progress.php?content=$prgress&mainid=dns-apply-system')";


	$jsRedisRestart=$tpl->framework_buildjs("/unbound/redis/restart",
		"unbound-redis.progress",
		"unbound-redis.log",
		"dns-apply-system","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')");


	$final[]="<div style='margin-top:10px'>";
	$final[]=$tpl->SERVICE_STATUS($bsini, "APP_UNBOUND",$jsRestart);

	if($UnboundRedisEnabled==1){
		$final[]=$tpl->SERVICE_STATUS($bsini, "UBOUND_REDIS",$jsRedisRestart);
	}

	$final[]=$tpl->SERVICE_STATUS($bsiniCollector, "APP_DNS_COLLECTOR",$jsRestartCollector);

	if($PDNSStatsEnabled==1) {
		$final[] = $tpl->SERVICE_STATUS($bsini, "APP_DSC", $jsRestartDSC);
	}
	if($EnableDNSCryptProxy==1) {
		$final[] = $tpl->SERVICE_STATUS($bsini, "APP_DNSCRYPT_PROXY", $jsRestartDNSCrypt);
	}
	$final[]="</div>";
	echo $tpl->_ENGINE_parse_body($final);
	return true;
}
function dnscache_status():bool{
	$page=CurrentPageName();
	$EnableCloudflared=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCloudflared"));
	if($EnableCloudflared==1){
		echo "<div id='cloudflared-status' style='margin-top:10px'></div>\n";
		echo "<script>LoadAjaxSilent('cloudflared-status','$page?cloudflared-status=yes')</script>";
	}



	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	if($UnboundEnabled==1){
		return unbound_status();
	}

	$tpl=new template_admin();
	$users=new usersMenus();
	$DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
	$EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
	if($EnableDNSDist==1){
		return dnsdist_status();
	}
	VERBOSE("DoNotUseLocalDNSCache = $DoNotUseLocalDNSCache",__LINE__);
	if(is_file("/etc/artica-postfix/DoNotUseLocalDNSCache")){
		$DoNotUseLocalDNSCache=1;
		VERBOSE("DoNotUseLocalDNSCache ======> 1",__LINE__);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DoNotUseLocalDNSCache",1);
	}

	$green="green";
	$text="{enabled}";
	$ico="fas fa-thumbs-up";


	if($DoNotUseLocalDNSCache==1){
		VERBOSE("BUILD DISABLED",__LINE__);
		$jsafter=$tpl->framework_buildjs(
			"/dnscache/install","dnscache.progress","dnscache.log","dnscache-install-progress",
			"LoadAjax('dns-servers-main-table','$page?table2=yes');");
		$green="grey";
		$text="{disabled}";
		$ico="fas fa-thumbs-down";
		$button["name"] = "{install}";
		$button["js"] = $jsafter;
		$button["ico"]="fa-solid fa-compact-disc";

		if(!$users->AsSystemAdministrator){
			$button=array();
		}
		echo "<div id='dnscache-install-progress'></div>";
		echo $tpl->_ENGINE_parse_body($tpl->widget_h("gray","fa-solid fa-compact-disc","{disabled}","{APP_LOCAL_DNSCACHE}",$button));
		return true;
	}


	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/status"));

	if (json_last_error()> JSON_ERROR_NONE) {
		$APP_LOCAL_DNSCACHE=$tpl->widget_rouge("API ERROR",json_last_error_msg());

	}else {
		if (!$json->Status) {
			$APP_LOCAL_DNSCACHE = $tpl->widget_rouge("API ERROR", $json->Error);
		} else {
			$ssh_status = new Bs_IniHandler();
			$ssh_status->loadString($json->Info);
			$APP_LOCAL_DNSCACHE = $tpl->SERVICE_STATUS($ssh_status, "APP_LOCAL_DNSCACHE",
				"Loadjs('$page?restart-localdnscache')");
		}
	}
	$jsremove=$tpl->framework_buildjs(
		"/dnscache/uninstall","dnscache.progress","dnscache.log","dns-apply-system",
		"LoadAjax('dns-servers-main-table','$page?table2=yes');");

	$button["name"] = "{uninstall}";
	$button["js"] = $jsremove;
	$button["ico"]="fa-solid fa-compact-disc";

	if(!$users->AsSystemAdministrator){
		$button=array();
	}

	echo $tpl->_ENGINE_parse_body($tpl->widget_h($green,$ico,$text,"{APP_LOCAL_DNSCACHE}",$button));
	echo $tpl->_ENGINE_parse_body($APP_LOCAL_DNSCACHE);
	return true;


}
function table2_status(){
	$tpl=new template_admin();
	$STATUS_FILE=PROGRESS_DIR ."/squid.dnsdist.status";
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?dnsdist-status=yes");
	$APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");

	preg_match("#^([0-9]+)\.([0-9]+)#",$APP_DNSDIST_VERSION,$re);
	$major=$re[1];
	$minor=$re[2];
	if($major==1){
		if($minor<6){
			$help_url="https://wiki.articatech.com/dns/load-balancer/upgrading";
			$js_help="s_PopUpFull('$help_url','1024','900');";
			$button=$tpl->button_autnonome("{UFDBGUARD_TITLE_2}","$js_help","fad fa-question",null,0,"btn-warning");
			$upgrade_required_software=$tpl->_ENGINE_parse_body("{upgrade_required_software}");
			$upgrade_required_software=str_replace("%s",$APP_DNSDIST_VERSION,$upgrade_required_software);
			echo $tpl->div_warning("{upgrade_required}||$upgrade_required_software||$button");
		}
	}



	$bsini=new Bs_IniHandler($STATUS_FILE);
	$page=CurrentPageName();
	echo $tpl->SERVICE_STATUS($bsini, "APP_DNSDIST","");
	$dnsdis=new dnsdist_status("127.0.0.253");
	if(!$dnsdis->generic_stats()){
		echo $tpl->div_error($dnsdis->error);
		return false;
	}


	$queries=$dnsdis->mainStats["queries"];
	$prc=$dnsdis->mainStats["cache_rate"];

	echo $tpl->_ENGINE_parse_body($tpl->widget_h("green","fas fa-percent","{$prc}%","{cache_rate}"));
	echo $tpl->_ENGINE_parse_body($tpl->widget_h("green","fas fa-satellite-dish",$tpl->FormatNumber($queries),"{queries}"));

	echo "<script>\n";
	echo "function SquidDNsLBRefresh(){\n";
	echo "if(!document.getElementById(\"proxy-dnsdist\") ){return;}\n";
	echo "LoadAjaxSilent('proxy-dnsdist','$page?table2-status=yes');\n";
	echo "}\n";
	echo "setTimeout(\"SquidDNsLBRefresh()\",3000);\n";
	echo "</script>";

	return true;
}

function ActiveInactif($val,$urljs=null):string{
	$tpl=new template_admin();
	if($urljs==null){
		$page=CurrentPageName();
		$urljs="Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')";
	}


	if($val==1){
		return $tpl->td_href("<i class='fa-solid fa-toggle-on'></i>&nbsp;{active2}",null,$urljs);
	}
	return $tpl->td_href("<span style='color:#CCCCCC'><i class='fa-solid fa-toggle-off'></i>&nbsp;{inactive}</span>",null,$urljs);
}

function table_dnscache():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$resolv=new resolv_conf();
	$UnboundEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	if(!$resolv->isValidDomain($resolv->MainArray["DOMAINS1"])){$resolv->MainArray["DOMAINS1"]="localhost.local";}
	$html[]="<div id='dns-apply-system'></div>";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT * FROM pdns_fwzones";
	$results=$q->QUERY_SQL($sql);
	$TRCLASS=null;
	$INT[]="<table style='width:100%;margin-top:20px'>";


    $UseDNSForEUBackends=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseDNSForEUBackends"));
    $tpl->table_form_field_js("");

    if($UseDNSForEUBackends==0){
        $edit=$tpl->icon_parameters("Loadjs('fw.dns.dnsforeu.php')","AsDnsAdministrator");
        $color="rgb(185, 182, 182)";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $INT[] = "<tr class='$TRCLASS' style='height: 60px' id='UseDNSForEUBackends'>";
        $INT[] = "<td style='width:1%;padding-left:10px;color:$color'><i class='".ico_clouds." fa-2x' style='color:$color'></i></td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px;color:$color' nowrap>{UseDNSForEUBackends}:</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px;color:$color'><strong>{inactive}</strong></td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";
    }else{
        $edit=$tpl->icon_parameters("Loadjs('fw.dns.dnsforeu.php')","AsDnsAdministrator");
        $DNSForEUBackendsTypes[0] = "{inactive2}";
        $DNSForEUBackendsTypes[1] = "{protective_resolution}";
        $DNSForEUBackendsTypes[2] = "{child_protection}";
        $DNSForEUBackendsTypes[3] = "{ads_protection}";
        $DNSForEUBackendsTypes[4] = "{child_protection} & {ads_protection}";
        $DNSForEUBackendsTypes[5] = "{unfiltered_resolution}";

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $INT[] = "<tr class='$TRCLASS' style='height: 60px' id='UseDNSForEUBackends'>";
        $INT[] = "<td style='width:1%;padding-left:10px;'><i class='".ico_clouds." fa-2x' style=''></i></td>";
        $INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px;' nowrap>{UseDNSForEUBackends}:</td>";
        $INT[] = "<td style='width:99%;font-size:large;padding-left: 10px;'><strong>$DNSForEUBackendsTypes[$UseDNSForEUBackends]</strong></td>";
        $INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
        $INT[] = "</tr>";
    }



	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$hostname=$ligne["hostname"].":".$ligne["port"];
		$zone=trim($ligne["zone"]);
		$md=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$delete=$tpl->icon_delete("Loadjs('$page?dnscache-del-js=$ID&function=RefreshDNSMainSection&md=$md')","AsDnsAdministrator");
		$INT[] = "<tr class='$TRCLASS' style='height: 60px' id='$md'>";
		$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-duotone fa fa-server fa-2x' id='$index'></i></td>";
		$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>$zone:</td>";
		$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$hostname</strong></td>";
		$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$delete</td>";
		$INT[] = "</tr>";

	}

	for($i=1;$i<4;$i++) {
		$labelName="{nameserver} $i";
		if($i==1){$labelName="{primary_dns}";}
		if($i==2){$labelName="{secondary_dns}";}
		$KEYDNS="DNS$i";
		if (!isset($resolv->MainArray[$KEYDNS])){$resolv->MainArray[$KEYDNS]="{not_used}";}

		if ($resolv->MainArray[$KEYDNS] == null) {
			$resolv->MainArray[$KEYDNS]="{not_used}";
		}
		$DNSName = $resolv->MainArray[$KEYDNS];
			if ($TRCLASS == "footable-odd") {
				$TRCLASS = null;
			} else {
				$TRCLASS = "footable-odd";
			}

			if ($DNSName == "8.8.8.8") {
				$DNSName = "Google DNS Over HTTPS";
			}
			if ($DNSName == "1.1.1.1") {
				$DNSName = "Cloudflare DNS Over HTTPS";
			}
			if ($DNSName == "9.9.9.9") {
				$DNSName = "Quad9 DNS Over HTTPS";
			}
			if ($DNSName == "208.67.222.222") {
				$DNSName = "OpenDNS DNS Over HTTPS";
			}
			if ($DNSName == "255.255.255.255") {
				$DNSName = "{EuropeanpublicDNSresolvers}";
			}


		$edit=$tpl->icon_parameters("Loadjs('$page?dns-edit-js=$i&function=RefreshDNSMainSection')","AsDnsAdministrator");
		$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
		$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-duotone fa fa-server fa-2x'></i></td>";
		$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>$labelName:</td>";
		$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$DNSName</strong></td>";
		$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
		$INT[] = "</tr>";
		}

	for($i=1;$i<4;$i++) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$edit=$tpl->icon_parameters("Loadjs('$page?dns-domains-js=yes&domain=$i&function=RefreshDNSMainSection')","AsDnsAdministrator");
		$labelName="{InternalDomain} $i";
		$DomainName=$resolv->MainArray["DOMAINS$i"];
		$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
		$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-duotone fa-earth-americas fa-2x'></i></td>";
		$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>$labelName:</td>";
		$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$DomainName</strong></td>";
		$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
		$INT[] = "</tr>";

	}

	$INT[] = "<tr class='' style='height: 80px'>";
	$INT[] = "<td colspan=4><H1>{service_options}</H1>";
	$INT[] = "</tr>";


		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$edit=$tpl->icon_parameters("Loadjs('$page?dnscache-edit-options-js=yes&domain=".time()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	$DNSCacheListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSCacheListenInterface"));
	if($DNSCacheListenInterface==null){
		$DNSCacheListenInterface_text="{none} (127.0.0.55)";
	}else{
		$nic=new system_nic($DNSCacheListenInterface);
		$DNSCacheListenInterface_text=$nic->NICNAME." ($nic->IPADDR)";
	}
	$DNSProxyOutGoing=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSProxyOutGoing"));
	if($DNSProxyOutGoing==""){
		$DNSProxyOutGoing="eth0";
	}
	$nic=new system_nic($DNSProxyOutGoing);
	$DNSProxyOutGoingInterface=$nic->NICNAME." ($nic->IPADDR)";


	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-ethernet fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>{listen_interface}:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$DNSCacheListenInterface_text</strong></td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-ethernet fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>{outgoing_interface}:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$DNSProxyOutGoingInterface</strong></td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";



	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$edit=$tpl->icon_parameters("Loadjs('$page?dnscache-edit-options-js=yes&domain=".time()."&function=RefreshDNSMainSection')","AsDnsAdministrator");

	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-duotone far fa-tools fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>{xtimeout}:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>{$resolv->MainArray["TIMEOUT"]}</strong> ({seconds})</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$edit=$tpl->icon_parameters("Loadjs('$page?dnscache-edit-options-js=yes&domain=".time()."&function=RefreshDNSMainSection')","AsDnsAdministrator");

	$DnsProxyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsProxyCache"));
	if($DnsProxyCache==0){$DnsProxyCache=10485760;}
	$DnsProxyCacheKB=$DnsProxyCache/1024;
	$DnsProxyCacheMB=round($DnsProxyCacheKB/1024);


	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-duotone far fa-tools fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>{ipcache_size}:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>$DnsProxyCacheMB</strong> MB</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";
	$INT[] = "<tr class='' style='height: 80px'>";
	$INT[] = "<td colspan=4><H1>SafeSearch</H1>";
	$INT[] = "</tr>";

	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	$EnableBraveSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBraveSafeSearch"));
	$EnableDuckduckgoSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDuckduckgoSafeSearch"));
	$EnableYandexSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYandexSafeSearch"));
	$EnablePixabaySafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePixabaySafeSearch"));
	$EnableQwantSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQwantSafeSearch"));
	$EnableBingSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBingSafeSearch"));
	$EnableYoutubeSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYoutubeSafeSearch"));
	$EnbaleYoutubeModerate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnbaleYoutubeModerate"));


	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");

	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-brands fa-google fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Google SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnableGoogleSafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";


	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-brands fa-youtube fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Youtube SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnbaleYoutubeModerate)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-brands fa-youtube fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Youtube SafeSearch (strict):</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnableYoutubeSafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-brands fa-edge-legacy fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Bing SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnableBingSafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";


	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-shield-minus fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Qwant SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnableQwantSafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-shield-minus fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Yandex SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnableYandexSafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-shield-minus fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>DuckDuckGo SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnableDuckduckgoSafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-shield-minus fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Brave SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnableBraveSafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

	$edit=$tpl->icon_parameters("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".microtime()."&function=RefreshDNSMainSection')","AsDnsAdministrator");
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$INT[] = "<tr class='$TRCLASS' style='height: 60px'>";
	$INT[] = "<td style='width:1%;padding-left:10px'><i class='fa-solid fa-shield-minus fa-2x'></i></td>";
	$INT[] = "<td style='width:1%;font-size:large;text-align:left;padding-left:10px' nowrap>Pixabay SafeSearch:</td>";
	$INT[] = "<td style='width:99%;font-size:large;padding-left: 10px'><strong>".ActiveInactif($EnablePixabaySafeSearch)."</td>";
	$INT[] = "<td style='width:1%;padding-left:10px;padding-right:10px'>$edit</td>";
	$INT[] = "</tr>";

//<div id='unbound-status' style='margin-top:10px'></div>
//<div id='dns-apply-system'></div>


	$INT[] = "</table>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:337px;vertical-align: top'>";
	$html[]="<div id='dnscache-status' style='min-width: 337px !important'>";
	if($UnboundEnabled==1){
		$html[]="<div id='dns-apply-system'></div>";
		$html[]="<div id='unbound-status' style='margin-top:10px'></div>";
	}
	$html[]="</div>";
	$html[]="</td>";
	$html[]="<td style='padding-left:20px;vertical-align: top'>";
	$html[]="<div style='margin-top:10px'>";
	$html[]=$tpl->_ENGINE_parse_body($tpl->div_explain("{local_dns_cache_warning}"));
	$html[]="</div>";
	$html[]=@implode("\n",$INT);
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";

	$jsRestart="Loadjs('fw.dns.servers.php?restart-localdnscache');";
	$btns="";
	$users=new usersMenus();
	if($users->AsDnsAdministrator) {
		$btns = $tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?dnscache-add-js=yes&function=RefreshDNSMainSection');\"><i class='fa fa-plus'></i> {new_dns_server} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsRestart\"><i class='fa fa-save'></i> {reconfigure_service} </label>
			</div>");
	}

	$TINY_ARRAY["TITLE"]="{dns_used_by_the_system}";
	$TINY_ARRAY["ICO"]="fa fa-server";
	$TINY_ARRAY["EXPL"]="{dns_servers_explain}<br>{APP_DNS_LOCAL_CACHE_EXPLAIN}";
	$TINY_ARRAY["URL"]="dns-servers";
	$TINY_ARRAY["BUTTONS"]=$btns;
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";





	$html[]="<script>";
	$html[]="function RefreshDNSMainSection(){";
	$html[]="LoadAjax('dns-servers-main-table','$page?table2=yes');";
	$html[]="}";
	$RefreshDNSCacheSection=$tpl->RefreshInterval_js("dnscache-status",$page,"dnscache-status=yes");

	if($UnboundEnabled==1){
		$RefreshDNSCacheSection=$tpl->RefreshInterval_js("unbound-status","fw.dns.unbound.php","unbound-status=yes");
	}

	$html[]="function RefreshDNSCacheSection(){";
	$html[]=$RefreshDNSCacheSection;
	$html[]="$jstiny;";
	$html[]="}";
	$html[]="RefreshDNSCacheSection();";
	$html[]="</script>";

	echo $tpl->_ENGINE_parse_body($html);
return true;



}
function dnsdomains_edit_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$resolv=new resolv_conf();
	$form[]=$tpl->field_hidden("SAVE_DNSDOMAINS","yes");
	$form[]=$tpl->field_text("DOMAINS1", "{InternalDomain} 1", $resolv->MainArray["DOMAINS1"]);
	$form[]=$tpl->field_text("DOMAINS2", "{InternalDomain} 2", $resolv->MainArray["DOMAINS2"]);
	$form[]=$tpl->field_text("DOMAINS3", "{InternalDomain} 3", $resolv->MainArray["DOMAINS3"]);
	$function=$_GET["function"];
	$function_js="$function()";
	echo $tpl->form_outside(null,$form,null,"{apply}","$function_js;dialogInstance2.close();","AsDnsAdministrator");
	return true;
}
function dnsdomains_edit_save():bool{
	$resolv=new resolv_conf();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	foreach ($_POST as $key=>$val){
		$resolv->MainArray[$key]=$val;
	}
	$resolv->save();
	admin_tracks_post("Save DNS local domains");
	return true;
}

function table2(){
	$DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
	VERBOSE("DoNotUseLocalDNSCache=$DoNotUseLocalDNSCache",__LINE__);
	if($DoNotUseLocalDNSCache==0){
		table_dnscache();
		exit;
	}
	$DNSAdded=false;
	$tpl=new template_admin();
	$page=CurrentPageName();
	$resolv=new resolv_conf();
	if(!isset($resolv->MainArray["DNS3"])){$resolv->MainArray["DNS3"]="";}
	$UnboundInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"));
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	if(!$resolv->isValidDomain($resolv->MainArray["DOMAINS1"])){$resolv->MainArray["DOMAINS1"]="localhost.local";}
	$HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

	if($HaClusterClient==0) {
		if ($UnboundInstalled == 1) {
			if ($UnboundEnabled == 1) {
				$q = new lib_sqlite("/home/artica/SQLITE/dns.db");
				$sql = "SELECT *  FROM pdns_fwzones WHERE zone='*'";
				$results = $q->QUERY_SQL($sql);

				foreach ($results as $index => $ligne) {
					$hostname = $ligne["hostname"] . ":" . $ligne["port"];
					$tpl->table_form_field_text("{APP_UNBOUND}","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class=\"fas fa-arrow-alt-right\"></i>&nbsp;$hostname",ico_earth);
				}
			}
		}
	}
	if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled")) == 0 && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist")) == 0 ) {
		$resolv->MainArray["DontUseLocalDns"]=0;
	}

	$HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
	if(!$HaClusterGBConfig){
		$HaClusterGBConfig=array();
	}
	if(!is_array($HaClusterGBConfig)){
		$HaClusterGBConfig=array();
	}
	if(!isset($HaClusterGBConfig["HaClusterUseLBAsDNS"])){$HaClusterGBConfig["HaClusterUseLBAsDNS"]=0;}
	$HaClusterUseLBAsDNS=intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]);
	if($HaClusterClient==1) {
		if($HaClusterUseLBAsDNS==1) {
			$HaClusterIP = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
			if (preg_match("#http(s|):\/\/(.+?):[0-9]+#", $HaClusterIP, $re)) {$HaClusterIP = $re[2];}
			$tpl->table_form_field_text("{APP_HAPROXY_CLUSTER}","&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class=\"fas fa-arrow-alt-right\"></i>&nbsp;$HaClusterIP",ico_server);
			$resolv->MainArray["DNS1"]="";
			$resolv->MainArray["DNS2"]="";
			$resolv->MainArray["DNS3"]="";
			$DNSAdded=true;
		}
	}
	$icon_dns1=null;
	$icon_dns2=null;
	if(isset($perfs[$resolv->MainArray["DNS1"]])){
		$icon_dns1=$perfs[$resolv->MainArray["DNS1"]];
	}
	if(isset($perfs[$resolv->MainArray["DNS2"]])){
		$icon_dns2=$perfs[$resolv->MainArray["DNS2"]];
	}

	$usetcp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ResolvConfUseTCP"));
	$EnableCloudflared=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCloudflared"));
	VERBOSE("EnableCloudflared = $EnableCloudflared",__LINE__);
	if($EnableCloudflared==1){
		if($resolv->MainArray["DNS1"]=="1.1.1.1"){
			$resolv->MainArray["DNS1"]="{APP_CLOUDFLARE_DNS}";
		}
			if($resolv->MainArray["DNS2"]=="1.1.1.1"){
				$resolv->MainArray["DNS2"]="{APP_CLOUDFLARE_DNS}";
			}
			if($resolv->MainArray["DNS3"]=="1.1.1.1"){
				$resolv->MainArray["DNS3"]="{APP_CLOUDFLARE_DNS}";
			}
	}
	if(is_null($resolv->MainArray["DNS3"])){
		$resolv->MainArray["DNS3"]="";
	}

	$UseDNSForEUBackends=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseDNSForEUBackends"));
	$tpl->table_form_field_js("Loadjs('fw.dns.dnsforeu.php')");
	if($UseDNSForEUBackends==0){
		$tpl->table_form_field_bool("{UseDNSForEUBackends}",0,ico_clouds);
	}else{
		$DNSForEUBackendsTypes[0] = "{inactive2}";
		$DNSForEUBackendsTypes[1] = "{protective_resolution}";
		$DNSForEUBackendsTypes[2] = "{child_protection}";
		$DNSForEUBackendsTypes[3] = "{ads_protection}";
		$DNSForEUBackendsTypes[4] = "{child_protection} & {ads_protection}";
		$DNSForEUBackendsTypes[5] = "{unfiltered_resolution}";
		$tpl->table_form_field_text("{UseDNSForEUBackends}",$DNSForEUBackendsTypes[$UseDNSForEUBackends],ico_clouds);

	}


	$tpl->table_form_field_js("Loadjs('$page?dns-standard-js=yes')");
	$UseEuropeenDNSBackends=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseEuropeenDNSBackends"));




	$EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
	if($EnableDNSDist==0){
		$UseEuropeenDNSBackends=0;
	}
	if($UseEuropeenDNSBackends==0) {

		if (strlen($resolv->MainArray["DNS1"]) > 2) {
			$DNSAdded=true;
			$tpl->table_form_field_text("{primary_dns}", $resolv->MainArray["DNS1"] . " $icon_dns1", ico_server);
		}
		if (strlen($resolv->MainArray["DNS2"]) > 2) {
			$DNSAdded=true;
			$tpl->table_form_field_text("{secondary_dns}", $resolv->MainArray["DNS2"] . " $icon_dns2", ico_server);
		}
		if (strlen($resolv->MainArray["DNS3"]) > 2) {
			$DNSAdded=true;
			$tpl->table_form_field_text("{nameserver} 3", $resolv->MainArray["DNS3"], ico_server);
		}
		if(!$DNSAdded){
			$tpl->table_form_field_text("{nameserver}", "{none}", ico_server);
		}

	}else{
		$array=array("dns0.eu","quad9.net","doh.sb","dns.mullvad.net",
			"anycast.uncensoreddns.org","dnspub.restena.lu","dns.digitale-gesellschaft.ch","dns.artikel10.org");
		$cc=0;
		foreach ($array as $dnsserv){
			$cc++;
			$tpl->table_form_field_text("{nameserver} $cc", "<span style='text-transform:none'>DoH $dnsserv</span>", ico_server);

		}
	}
	if(!isset($resolv->MainArray["DOMAINS2"])){
		$resolv->MainArray["DOMAINS2"]="";
	}
	if(!isset($resolv->MainArray["DOMAINS3"])){
		$resolv->MainArray["DOMAINS3"]="";
	}

	if (strlen($resolv->MainArray["DOMAINS1"]) > 2) {
		$tpl->table_form_field_text("{InternalDomain}", $resolv->MainArray["DOMAINS1"], ico_earth);
	}
	if (strlen($resolv->MainArray["DOMAINS2"]) > 2) {
		$tpl->table_form_field_text("{InternalDomain}", $resolv->MainArray["DOMAINS2"], ico_earth);
	}
	if (strlen($resolv->MainArray["DOMAINS3"]) > 2) {
		$tpl->table_form_field_text("{InternalDomain}", $resolv->MainArray["DOMAINS3"], ico_earth);
	}

	if($usetcp==1){
			$cf[]="{useTCPPort}";
	}

	$cf[]="{xtimeout} {$resolv->MainArray["TIMEOUT"]} {seconds}";
	$cf[]="{max-attempts} {$resolv->MainArray["ATTEMPTS"]} {times}";
	if($resolv->MainArray["USEROTATION"]==1){
		$cf[]="{UseRotation}";
	}
	if($resolv->MainArray["DontUseLocalDns"]==1){
		$cf[]="{not} {use} {local_dns_service}";
	}


	$tpl->table_form_field_text("{timeouts}",@implode(", ",$cf),ico_timeout);

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:337px;vertical-align: top'>";
	$html[]="<div id='dnscache-status' style='min-width: 337px !important'></div></td>";
	$html[]="<td style='padding-left:20px;vertical-align:top'>";
	$html[]=$tpl->table_form_compile();
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";


	$TINY_ARRAY["TITLE"]="{dns_used_by_the_system}";
	$TINY_ARRAY["ICO"]="fa fa-server";
	$TINY_ARRAY["EXPL"]="{dns_servers_explain}";
	$TINY_ARRAY["URL"]="dns-servers";
	$TINY_ARRAY["BUTTONS"]=null;
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$js=$tpl->RefreshInterval_js("dnscache-status",$page,"dnscache-status=yes");
	$html[]="<script>$jstiny;$js;</script>";
	echo $tpl->_ENGINE_parse_body($html);
}

function dns_standards_popup():bool{

	$tpl=new template_admin();
	$page=CurrentPageName();
	$resolv=new resolv_conf();
	$btname="{apply}";


	if(!$resolv->isValidDomain($resolv->MainArray["DOMAINS1"])){$resolv->MainArray["DOMAINS1"]="localhost.local";}
	$HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

	if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled")) == 0 && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist")) == 0 ) {
		$resolv->MainArray["DontUseLocalDns"]=0;
	}

	if($HaClusterClient==1) {$btname=null;}



	$jsafter="dialogInstance2.close();LoadAjax('dns-servers-main-table','$page?table2=yes');LoadAjax('applications-squid-status','fw.proxy.status.php?applications-squid-status=yes');";

	$usetcp=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ResolvConfUseTCP"));


	$html[]="<div id='dns-apply-system'></div>";
	$EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
	if($EnableDNSDist==1){
		$UseEuropeenDNSBackends=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseEuropeenDNSBackends"));
		$form[]=$tpl->field_checkbox("UseEuropeenDNSBackends","{EuropeanpublicDNSresolvers}",$UseEuropeenDNSBackends);


	}


	$form[]=$tpl->field_ipv4("DNS1", "{primary_dns}", $resolv->MainArray["DNS1"], false,null,false);
	$form[]=$tpl->field_ipv4("DNS2", "{secondary_dns}", $resolv->MainArray["DNS2"], false,null,false);
	$form[]=$tpl->field_ipv4("DNS3", "{nameserver} 3 ", $resolv->MainArray["DNS3"], false,null,false);
	$form[]=$tpl->field_text("DOMAINS1", "{InternalDomain} 1", $resolv->MainArray["DOMAINS1"]);
	$form[]=$tpl->field_text("DOMAINS2", "{InternalDomain} 2", $resolv->MainArray["DOMAINS2"]);
	$form[]=$tpl->field_text("DOMAINS3", "{InternalDomain} 3", $resolv->MainArray["DOMAINS3"]);
	$form[]=$tpl->field_numeric("TIMEOUT", "{xtimeout} ({seconds})", $resolv->MainArray["TIMEOUT"]);
	$form[]=$tpl->field_numeric("ATTEMPTS", "{max-attempts} ({times})", $resolv->MainArray["ATTEMPTS"]);
	$form[]=$tpl->field_checkbox("USEROTATION","{UseRotation}",$resolv->MainArray["USEROTATION"]);
	$form[]=$tpl->field_checkbox("DontUseLocalDns","{not} {use} {local_dns_service}",$resolv->MainArray["DontUseLocalDns"]);
	$form[]=$tpl->field_checkbox("ResolvConfUseTCP","{useTCPPort}",$usetcp);
	$html[]=$tpl->form_outside(null, $form,null,$btname,
		"$jsafter","AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}




function DNS_SERVERS_SAVE():bool{
	$resolv=new resolv_conf();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	if(isset($_POST["ResolvConfUseTCP"])){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ResolvConfUseTCP",$_POST["ResolvConfUseTCP"]);
	}

	if(isset($_POST["UseEuropeenDNSBackends"])){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseEuropeenDNSBackends",$_POST["UseEuropeenDNSBackends"]);
	}

	if(isset($_POST["DnsProxyCache"])){
		$DnsProxyCacheMB=$_POST["DnsProxyCache"];
		$DnsProxyCacheKB=$DnsProxyCacheMB*1024;
		$DnsProxyCacheBYTES=$DnsProxyCacheKB*1024;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsProxyCache",$DnsProxyCacheBYTES);
	}

	foreach ($_POST as $key=>$value){
		$resolv->MainArray[$key]=$value;
	}

	$resolv->save();
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");

	$EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
	if($EnableDNSDist==1){
		$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/php/restart");
	}
	$SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==1){
		$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/reload");
	}
    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if($UnboundEnabled==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
    }


	return admin_tracks_post("Saving DNS parameters");
}

function DNS_PROXY_SAVE():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(isset($_POST["ResolvConfUseTCP"])){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ResolvConfUseTCP",$_POST["ResolvConfUseTCP"]);
	}
	if(isset($_POST["DnsProxyCache"])){
		$DnsProxyCacheMB=$_POST["DnsProxyCache"];
		$DnsProxyCacheKB=$DnsProxyCacheMB*1024;
		$DnsProxyCacheBYTES=$DnsProxyCacheKB*1024;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsProxyCache",$DnsProxyCacheBYTES);
	}

		if ($_POST["SquidDNSUseSystem"] == 1) {
			$_POST["SquidDNSUseSystem"] = 0;
		} else {
			$_POST["SquidDNSUseSystem"] = 1;
		}


		$libmem=new lib_memcached();
		$libmem->Delkey("GOOD_DNS_SERVERS");
		$tpl->SAVE_POSTs();
		return admin_tracks_post("Saving DNS parameters");
}

?>	
	
	
