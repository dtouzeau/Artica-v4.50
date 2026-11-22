<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
// Client unique des routes /unbound/api/* d'articarest (spec 019 lot 4c).
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
// Verrou d'une politique Meta dnscache : les cles SafeSearch tenues ne passent pas par /setinfo.
include_once(dirname(__FILE__)."/ressources/class.unbound.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["SAVE_DNSDOMAINS"])){dnsdomains_edit_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["table2-status"])){table2_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["SquidDNSUseSystem"])){DNS_PROXY_SAVE();exit;}
if(isset($_POST["DOMAINS1"])){DNS_SERVERS_SAVE();exit;}
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
// *-js : ne rend que du JavaScript, qui POSTe dnscache-restart (aucune action sur un GET).
if(isset($_GET["restart-localdnscache"])){dnscache_local_restart_js();exit;}
if(isset($_POST["dnscache-restart"])){dnscache_local_restart();exit;}
if(isset($_GET["api-notices-js"])){dnsfw_api_notices_js();exit;}

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
/** Liste blanche de la fonction de rafraichissement : elle part dans du JavaScript (skill §7b). */
function dnsfw_function():string{
	return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}
function dnsfw_function_call($function):string{
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);
	return ($function!=="")?"$function();":"";
}
function dnsfw_bool($v):int{
	return intval($v)==1?1:0;
}
/** Une adresse de resolveur : vide ou une adresse IP. */
function dnsfw_ip_ok($v):bool{
	$v=trim((string)$v);
	return $v==="" || filter_var($v,FILTER_VALIDATE_IP)!==false;
}
/** Un domaine de recherche de resolv.conf : vide ou un nom DNS. */
function dnsfw_search_domain_ok($v):bool{
	$v=trim((string)$v);
	return $v==="" || (strlen($v)<=253 && (bool)preg_match('/^[A-Za-z0-9_]([A-Za-z0-9_-]{0,62})(\.[A-Za-z0-9_]([A-Za-z0-9_-]{0,62}))*$/',$v));
}
function dnsfw_iface_ok($v):bool{
	$v=trim((string)$v);
	return $v==="" || (bool)preg_match('/^[A-Za-z0-9._:@-]{1,32}$/',$v);
}
/** Le redemarrage du cache local : *-js (GET) -> POST dnscache-restart. */
function dnscache_local_restart_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!dnsfw_can_edit()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	$page=CurrentPageName();
	$t=time();
	$js=array();
	$js[]="var xDnsRestart$t=function(obj){";
	$js[]="\tvar res=obj.responseText;";
	// post_error() : HTML deja echappe ; tout autre corps (page 504, notice PHP) en texte.
	$js[]="\tif(res.length>3){".unbound_api::swal_js("res")."}";
	$js[]="};";
	$js[]="var XHR=new XHRConnection();";
	$js[]="XHR.appendData('dnscache-restart','yes');";
	$js[]="XHR.sendAndLoad('$page','POST',xDnsRestart$t);";
	echo implode("\n",$js);
}
function dnscache_local_restart():bool{
	$tpl=new template_admin();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/restart");
	return admin_tracks("Restart the local DNS cache service");
}



function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{dns_servers}"]="$page?table=yes";
	$EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));
	if($EnableDNSCryptProxy==1){
		$array["{public_dns_servers}"]="fw.dnscrypt-proxy.list.php";
	}
	echo $tpl->tabs_default($array);

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
	$function=dnsfw_function();
	$tpl->js_dialog2("modal:{options}","$page?SafeSearch-edit-options-popup=yes&function=$function");
	return true;

}



/** Les cles du formulaire SafeSearch, seules ecrites (SAVE_POSTs ecrivait toute cle postee). */
function dnsfw_safesearch_keys():array{
	return array("EnableGoogleSafeSearch","EnableQwantSafeSearch","EnableBraveSafeSearch","EnableBingSafeSearch",
		"EnableYoutubeSafeSearch","EnbaleYoutubeModerate","EnableDuckduckgoSafeSearch","EnableYandexSafeSearch","EnablePixabaySafeSearch");
}
function dnscache_edit_safesearch_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	// Configuration d'unbound : refus sur un esclave du cluster (texte d'articarest).
	$refusal=(new unbound_api())->legacy_write_refusal();
	if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
	$meta=unbound_meta_refuse_keys(array_intersect(array_keys($_POST),dnsfw_safesearch_keys()));
	if($meta!==""){ echo $meta;return false;}
	$set=array();
	foreach(dnsfw_safesearch_keys() as $k){
		if(!isset($_POST[$k])){continue;}
		$v=dnsfw_bool($_POST[$k]);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,$v);
		$set[]="$k=$v";
	}
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
	return admin_tracks("Saving SafeSearch(s) For DNS Cache service: ".implode(", ",$set));
}
function dnscache_edit_safesearch_popup():bool{
	$tpl=new template_admin();
	$function=dnsfw_function();
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
	$DebianAgentLocked=$GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked();
	if($DebianAgentLocked){
		return true;
	}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=dnsfw_function();
	$tpl->js_dialog2("modal:{options}","$page?dnscache-edit-options-popup=yes&function=$function");
	return true;
}
function dns_standards_js():bool{
	$DebianAgentLocked=$GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked();
	if($DebianAgentLocked){
		return true;
	}
	$tpl = new template_admin();
	$page = CurrentPageName();
	return $tpl->js_dialog2("modal:{dns_used_by_the_system}", "$page?dns-standard-popup=yes");

}
function dnscache_add_js():bool{
	$DebianAgentLocked=$GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked();
	if($DebianAgentLocked){
		return true;
	}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist XSS
	$tpl->js_dialog2("modal:{new_dns_server}","$page?dnscache-add-popup=yes&function=$function");
	return true;
}
function dnsdomains_edit_js():bool{
	$DebianAgentLocked=$GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked();
	if($DebianAgentLocked){
		return true;
	}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=dnsfw_function();
	return $tpl->js_dialog2("modal:{InternalDomain}","$page?dns-domains-popup=yes&function=$function");


}
function dnscache_edit_js():bool{
	$DebianAgentLocked=$GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked();
	if($DebianAgentLocked){
		return true;
	}
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=dnsfw_function();
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

	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist XSS
	$function_js=($function!=="")?"$function();":"";
	$function_js.="Loadjs('$page?api-notices-js=yes')";

	$bname="{add}";
	$html[]=$tpl->form_outside("{new_forward_zone}", @implode("\n", $form),"{ADD_DNS_ZONE_TEXT}",$bname,
		"$function_js;dialogInstance2.close();","AsDnsAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function dnscache_edit_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=dnsfw_function();
	$i=intval($_GET["dnscache-edit-popup"]);

	$html[]="<div id='dnscache-edit-popup-$i'></div>";
	$html[]="<script>LoadAjaxSilent('dnscache-edit-popup-$i','$page?dnscache-edit-popup2=$i&function=$function');</script>";
	echo $tpl->_ENGINE_parse_body($html);
	return true;

}
function dnscache_edit_save():bool{
	$tpl=new template_admin();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$i=intval($_POST["dnscache-edit"]);
	$ruletype=intval($_POST["ruletype"] ?? 0);
	if($i<1 || $i>3 || $ruletype<1 || $ruletype>7){ echo $tpl->post_error("{wrong_value}");return false;}
	$keyServe="DNS$i";
	$resolv=new resolv_conf();
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
	$function=dnsfw_function();
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
		echo "<script>dialogInstance2.close();$function();</script>";
		return true;
	}
	if($ruletype==7){
		echo "<script>dialogInstance2.close();$function();</script>";
		return true;
	}
	if($ruletype==6){
		echo "<script>dialogInstance2.close();$function();</script>";
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

	$function=dnsfw_function();
	$function_js="$function()";


	echo $tpl->form_outside($title,$form,null,"{apply}","$function_js;dialogInstance2.close();","AsDnsAdministrator");
	return true;
}
function dnscache_typ5_save():bool{
	$tpl=new template_admin();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$i=intval($_POST["dnscache-typ5"]);
	$ip=trim((string)($_POST["dnscache-typ5-value"] ?? ""));
	// La valeur part telle quelle dans resolv.conf.
	if($i<1 || $i>3 || !dnsfw_ip_ok($ip)){ echo $tpl->post_error("{ipaddr}: {wrong_value}");return false;}
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

	$function=dnsfw_function();
	$function_js="$function()";
	echo $tpl->form_outside(null,$form,null,"{apply}","$function_js;dialogInstance2.close();","AsDnsAdministrator");
	return true;
}
function dnscache_edit_options_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$MB=intval($_POST["DnsProxyCache"] ?? 0);
	if($MB<1 || $MB>65536){ echo $tpl->post_error("{ipcache_size}: {wrong_value}");return false;}
	$iface=trim((string)($_POST["DNSCacheListenInterface"] ?? ""));
	if(!dnsfw_iface_ok($iface)){ echo $tpl->post_error("{listen_interface}: {wrong_value}");return false;}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsProxyCache",$MB*1024*1024);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSCacheListenInterface",$iface);
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/restart");
	return admin_tracks("Save global DNS parameters: cache {$MB}MB, interface $iface");
}


function dnscache_edit_popup2():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=dnsfw_function();
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



/*
 * Zones de transfert du panneau (spec 019 lot 4c). Avec unbound, une zone simple
 * passe par /unbound/api/forward-zones* (validation, unbound-checkconf complet,
 * application, preuve, refus 409 enrichi sur un esclave). Les autres natures
 * (groupe, transparente, DNSSEC), la zone « * » ou « . », un nom a une seule
 * etiquette, et toute zone sans unbound (cache DNS local, dnsdist) gardent
 * l'ancien chemin : privilege, refus esclave, requete liee — l'id et chaque
 * champ partaient non echappes —, puis regeneration d'unbound s'il tourne.
 */
function dnsfw_api_notices_js(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	echo unbound_api::notices_js($tpl->javascript_parse_text("{warning}",0,true),function($s) use ($tpl){
		return $tpl->javascript_parse_text($s,0,true);
	});
}
function dnsfw_can_edit():bool{
	$tpl=new template_admin();
	return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}
function dnsfw_unbound():bool{
	return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"))==1;
}
/** Nature d'une ligne pdns_fwzones (= zoneKind d'articarest). */
function dnsfw_kind($ligne):string{
	if(intval($ligne["bulk"] ?? 0)==1){return "multi";}
	if(intval($ligne["transparent"] ?? 0)==1){return "transparent";}
	if(intval($ligne["onlyinsecure"] ?? 0)==1){return "dnssec";}
	return "forward";
}
/** Une zone que l'API accepte (= validateDomain : deux etiquettes au moins). */
function dnsfw_api_zone_ok($zone):bool{
	$z=strtolower(rtrim(trim((string)$zone),"."));
	return (bool)preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/',$z);
}
/** Zone de l'ancien chemin : « * », « . » ou un nom DNS, recopie tel quel dans la configuration. */
function dnsfw_legacy_zone_ok($zone):bool{
	$z=trim((string)$zone);
	if($z==="*" || $z==="."){return true;}
	if(strlen($z)>253 || strpos($z,"..")!==false){return false;}
	return (bool)preg_match('/^[A-Za-z0-9_]([A-Za-z0-9_-]{0,62})(\.[A-Za-z0-9_]([A-Za-z0-9_-]{0,62}))*\.?$/',$z);
}
function dnsfw_row($id){
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM pdns_fwzones WHERE ID=".intval($id));
	return (is_array($ligne) && isset($ligne["ID"]))?$ligne:null;
}

function dnscache_del_redirect():bool{
	$tpl=new template_admin();
	$ID=intval($_GET["dnscache-del-js"]);
	$ligne=dnsfw_row($ID);
	$hostname=is_array($ligne)?$ligne["hostname"].":".$ligne["port"]:"";
	$zone=is_array($ligne)?trim($ligne["zone"]):"#$ID";
	// md et function partaient bruts dans le JavaScript execute apres la suppression.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$function_js="$('#$md').remove();Loadjs('".CurrentPageName()."?api-notices-js=yes');";
	$tpl->js_confirm_delete(htmlspecialchars("$zone » $hostname"),"dnscache-del-js",$ID,$function_js);
	return true;

}
function dnscache_del_redirect_confirm():bool{
	$tpl=new template_admin();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$ID=intval($_POST["dnscache-del-js"]);
	$ligne=dnsfw_row($ID);
	if(!is_array($ligne)){ echo $tpl->post_error("{error}: id");return false;}
	$hostname=$ligne["hostname"].":".$ligne["port"];
	$zone=trim($ligne["zone"]);
	$kind=dnsfw_kind($ligne);
	if(dnsfw_unbound() && $kind==="forward"){
		$r=(new unbound_api())->delete("/unbound/api/forward-zones/$ID");
		if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
		unbound_api::queue($r);
		return admin_tracks("Remove DNS forwarder $hostname for zone $zone");
	}
	$refusal=(new unbound_api())->legacy_write_refusal();
	if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL_PARAMS("DELETE FROM pdns_fwzones WHERE ID=?",array($ID));
	if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
	if(dnsfw_unbound()){
		$GLOBALS["CLASS_SOCKETS"]->REST_API(($kind==="transparent" || $kind==="dnssec")?"/unbound/control/reconfigure":"/unbound/build/zones");
	}
	admin_tracks("Remove DNS forwarder $hostname for zone $zone ($kind)");
	return true;
}
function dnscache_add_redirect():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$zone=trim((string)($_POST["zone"] ?? ""));
	$server=trim((string)($_POST["hostname"] ?? ""));
	if($zone==="" || $server===""){ echo $tpl->post_error("{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}");return false;}
	if(filter_var($server,FILTER_VALIDATE_IP)===false){
		echo $tpl->post_error("{ipaddr}: {wrong_value}");
		return false;
	}
	$port=intval($_POST["port"] ?? 0);
	if($port==0){$port=53;}
	if($port<1 || $port>65535){ echo $tpl->post_error("{listen_port}: {wrong_value}");return false;}
	if(dnsfw_unbound() && dnsfw_api_zone_ok($zone)){
		$r=(new unbound_api())->post("/unbound/api/forward-zones",array("zone"=>$zone,"server"=>$server,"port"=>$port,"use_tls"=>false));
		if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
		unbound_api::queue($r);
		return admin_tracks("Add DNS forwarder $server:$port for zone $zone");
	}
	if(!dnsfw_legacy_zone_ok($zone)){ echo $tpl->post_error("{domain}: {wrong_value}");return false;}
	$refusal=(new unbound_api())->legacy_write_refusal();
	if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS) VALUES (?,?,?,0,0)",array($zone,$port,$server));
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
	if(dnsfw_unbound()){
		$GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
	}
	return admin_tracks("Add DNS forwarder $server:$port for zone $zone");
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
		"unbound.restart.log","dns-apply-system","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')",
		null,null,"AsDnsAdministrator","{APP_UNBOUND} {restarting_service}");





	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/pdns.dsc.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/pdns.dsc.progress.txt";
	$ARRAY["CMD"]="pdns.php?restart-dsc=yes";
	$ARRAY["TITLE"]="{APP_DSC} {restarting_service}";
	$ARRAY["PRIV"]="AsDnsAdministrator";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDSC="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=dns-apply-system')";
	if(!$tpl->IsSecurity("AsDnsAdministrator")){$jsRestartDSC="Loadjs('fw.progress.php?badprivs=yes')";}


	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/DNSCryptProxy.restart.progress.log";
	$ARRAY["CMD"]="dnscrypt-proxy.php?restart=yes";
	$ARRAY["TITLE"]="{APP_DNSCRYPT_PROXY} {restarting_service}";
	$ARRAY["PRIV"]="AsDnsAdministrator";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestartDNSCrypt="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=dns-apply-system')";
	if(!$tpl->IsSecurity("AsDnsAdministrator")){$jsRestartDNSCrypt="Loadjs('fw.progress.php?badprivs=yes')";}


	$jsRedisRestart=$tpl->framework_buildjs("/unbound/redis/restart",
		"unbound-redis.progress",
		"unbound-redis.log",
		"dns-apply-system","LoadAjaxSilent('unbound-status','$page?unbound-status=yes')",
		null,null,"AsDnsAdministrator","{UBOUND_REDIS} {restarting_service}");


	$final[]="<div style='margin-top:10px'>";
	$final[]=$tpl->SERVICE_STATUS($bsini, "APP_UNBOUND",$jsRestart);

	if($UnboundRedisEnabled==1){
		$final[]=$tpl->SERVICE_STATUS($bsini, "UBOUND_REDIS",$jsRedisRestart);
	}

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
	// Pas de <div id='dns-apply-system'> ici : ce fragment est recharge toutes les
	// quelques secondes par RefreshInterval_js("dnscache-status"), et la barre de
	// progression d'une tache en cours y etait effacee a chaque passage (la suite
	// de la tache n'avait plus ou s'ecrire). La zone vit AU-DESSUS du conteneur.
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
			"LoadAjax('dns-servers-main-table','$page?table2=yes');",null,null,"AsSystemAdministrator",
			"{APP_LOCAL_DNSCACHE} {installing_service}");
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
		"LoadAjax('dns-servers-main-table','$page?table2=yes');",null,null,"AsSystemAdministrator",
		"{APP_LOCAL_DNSCACHE} {uninstalling_service}");

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

// ---------------------------------------------------------------------------
// Panneau « DNS used by the system », branche cache DNS local (maquette
// stitch_dns_servers, 2026-11-07). Le statut du service reste a gauche
// (dnscache-status, rafraichi), les reglages passent en cartes a droite :
// resolveurs amont, zones de transfert, domaines de recherche, options du
// service, SafeSearch. Un modele unique (dnss_model) alimente chaque carte.
// ---------------------------------------------------------------------------

function dnss_h($s):string{
	return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}
// Traduit un token puis remplace %s : sprintf ne peut pas agir sur "{token}" brut.
function dnss_tr(string $token,array $args=array()):string{
	$tpl=new template_admin();
	$s=$tpl->_ENGINE_parse_body("{".$token."}");
	if(count($args)==0){return $s;}
	return vsprintf(str_replace("%d","%s",$s),$args);
}

function dnscache_safesearch_list():array{
	return array(
		array("EnableGoogleSafeSearch","Google","Google SafeSearch","fa-brands fa-google"),
		array("EnbaleYoutubeModerate","Youtube","Youtube SafeSearch","fa-brands fa-youtube"),
		array("EnableYoutubeSafeSearch","Youtube (strict)","Youtube SafeSearch (strict)","fa-brands fa-youtube"),
		array("EnableBingSafeSearch","Bing","Bing SafeSearch","fa-brands fa-edge-legacy"),
		array("EnableQwantSafeSearch","Qwant","Qwant SafeSearch","fa-solid fa-shield-halved"),
		array("EnableYandexSafeSearch","Yandex","Yandex SafeSearch","fa-solid fa-shield-halved"),
		array("EnableDuckduckgoSafeSearch","DuckDuckGo","DuckDuckGo SafeSearch","fa-solid fa-shield-halved"),
		array("EnableBraveSafeSearch","Brave","Brave SafeSearch","fa-solid fa-shield-halved"),
		array("EnablePixabaySafeSearch","Pixabay","Pixabay SafeSearch","fa-solid fa-shield-halved"));
}
function dnscache_options_js():string{
	$page=CurrentPageName();
	return "Loadjs('$page?dnscache-edit-options-js=yes&domain=".time()."&function=RefreshDNSMainSection')";
}
function dnscache_nic_label($iface):string{
	$nic=new system_nic($iface);
	return dnss_h($nic->NICNAME." ($nic->IPADDR)");
}

// Toutes les donnees des cartes, lues une seule fois.
function dnss_model():array{
	$sock=$GLOBALS["CLASS_SOCKETS"];
	$resolv=new resolv_conf();
	$m=array();
	$m["edit"]=!$sock->DebianAgentLocked() && dnsfw_can_edit();

	$m["resolvers"]=array();
	for($i=1;$i<4;$i++){
		$v=isset($resolv->MainArray["DNS$i"])?trim((string)$resolv->MainArray["DNS$i"]):"";
		$m["resolvers"][$i]=$v;
	}
	$m["dns4eu"]=intval($sock->GET_INFO("UseDNSForEUBackends"));

	$m["zones"]=array();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$results=$q->QUERY_SQL("SELECT * FROM pdns_fwzones ORDER BY zone");
	if(is_array($results)){
		foreach($results as $ligne){$m["zones"][]=$ligne;}
	}

	$m["domains"]=array();
	for($i=1;$i<4;$i++){
		$m["domains"][$i]=isset($resolv->MainArray["DOMAINS$i"])?trim((string)$resolv->MainArray["DOMAINS$i"]):"";
	}
	if(!$resolv->isValidDomain($m["domains"][1])){$m["domains"][1]="localhost.local";}

	$listen=trim((string)$sock->GET_INFO("DNSCacheListenInterface"));
	$out=trim((string)$sock->GET_INFO("DNSProxyOutGoing"));
	if($out==""){$out="eth0";}
	$m["listen"]=$listen==""?"Loopback (127.0.0.55)":dnscache_nic_label($listen);
	$m["outgoing"]=dnscache_nic_label($out);
	$m["timeout"]=intval(isset($resolv->MainArray["TIMEOUT"])?$resolv->MainArray["TIMEOUT"]:0);
	$cache=intval($sock->GET_INFO("DnsProxyCache"));
	if($cache==0){$cache=10485760;}
	$m["cache_mb"]=round(($cache/1024)/1024);

	$m["safesearch"]=array();
	$m["safesearch_on"]=0;
	foreach(dnscache_safesearch_list() as $e){
		$on=intval($sock->GET_INFO($e[0]))==1;
		if($on){$m["safesearch_on"]++;}
		$m["safesearch"][]=array("short"=>$e[1],"label"=>$e[2],"ico"=>$e[3],"on"=>$on);
	}
	return $m;
}

function dnss_card_head(string $ico,string $title,string $extra,string $right):string{
	return "<div class='ak-card-h dnss-card-head'><div class='dnss-card-title'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$ico' aria-hidden='true'></i></span><h3>$title</h3>$extra</div>$right</div>";
}
function dnss_row(string $label,string $value,string $btn=""):string{
	return "<div class='dnss-row'><span class='dnss-label'>$label</span><span class='dnss-value'>$value$btn</span></div>";
}

function dnss_zone_count(int $n):string{
	if($n==1){return dnss_tr("dnss_zone_one");}
	return dnss_tr("dnss_zones_n",array($n));
}

// ── Style et JS ──────────────────────────────────────────────────────────────
function dnss_style():string{
	if(isset($GLOBALS["DNSS_STYLE"])){return "";}
	$GLOBALS["DNSS_STYLE"]=true;
	$accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent"])){$accent=$a["accent"];}
		if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
	}
	// Carte, en-tete, table, etiquettes, puces, boutons : kit ak- (angular.css.php).
	return "<style>
.dnss-root{display:flex;gap:20px;align-items:flex-start;margin-top:10px;color:#1f2933}
.dnss-side{flex:0 0 337px;min-width:337px}
.dnss-main{flex:1 1 auto;min-width:0}
.dnss-grid{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:20px;align-items:start}
.dnss-col{display:flex;flex-direction:column;gap:20px;min-width:0}
@media(max-width:1599px){.dnss-grid{grid-template-columns:minmax(0,1fr)}}
@media(max-width:1100px){.dnss-root{flex-direction:column;align-items:stretch}.dnss-side{flex:none;width:100%;min-width:0}.dnss-main{width:100%}}
.dnss-card-head{padding:14px 20px;min-height:60px}
.dnss-card-title{display:flex;align-items:center;gap:10px;min-width:0}
.dnss-card-title h3{margin:0;font-size:15px;font-weight:600;color:#1f2933;white-space:nowrap}
.dnss-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 20px;min-height:52px;border-top:1px solid #eef1f4}
.dnss-card-head+.dnss-row{border-top:0}
.dnss-row-sub{background:#fafbfc}
.dnss-label{font-size:13px;color:#5b6573;white-space:nowrap}
.dnss-label i{color:#8a94a3;margin-right:6px}
.dnss-value{display:flex;align-items:center;gap:10px;min-width:0;font-size:14px;font-weight:500;color:#1f2933;text-align:right}
.dnss-value .ak-chip,.dnss-value .ak-tag{max-width:100%;overflow:hidden;text-overflow:ellipsis}
.dnss-doh{font-size:10px;font-weight:700;letter-spacing:.04em;color:#1d4ed8;background:#e8efff;border-radius:4px;padding:1px 5px;margin-left:6px}
.dnss-none{color:#8a94a3;font-style:italic;font-weight:400}
.dnss-notice-btn:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}
.dnss-scroll{overflow-x:auto}
.dnss-tbl th{white-space:nowrap}
.dnss-tbl td{font-size:13px}
.dnss-tbl tbody tr:hover{background:#fbfcfd}
.dnss-zone{font-weight:700;font-family:monospace;font-size:13px;word-break:break-all}
.dnss-act{width:1%;text-align:right;white-space:nowrap}
.dnss-tags{display:inline-flex;gap:6px}
.dnss-dash{color:#8a94a3}
.dnss-empty p{margin:0}
.dnss-empty-ico{width:48px;height:48px;border-radius:50%;background:#f1f4f7;color:#8a94a3;display:inline-flex;align-items:center;justify-content:center;font-size:18px}
.dnss-notice{border:1px solid #f0d9b0;border-left:4px solid #b7791f;background:#fff8eb;border-radius:8px;margin-bottom:20px}
.dnss-notice-line{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#5c3d0b;font-size:14px}
.dnss-notice-line>i,.dnss-notice-line>svg{color:#b7791f;flex:0 0 auto}
.dnss-notice-text{flex:1 1 auto}
.dnss-notice-btn{border:0;background:transparent;color:#865300;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap;padding:4px 6px;border-radius:4px}
.dnss-notice-btn[aria-expanded=true] svg,.dnss-notice-btn[aria-expanded=true] i{transform:rotate(180deg)}
.dnss-notice-more{padding:0 16px 14px 44px;color:#5c3d0b;font-size:13px;line-height:1.6}
.dnss-ss-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;padding:16px 20px}
.dnss-ss{display:flex;align-items:center;gap:8px;border:1px solid #e3e7ec;border-radius:6px;padding:8px 10px;font-size:13px;color:#5b6573;min-width:0}
.dnss-ss-on{border-color:$accent;background:$soft;color:#1f2933}
.dnss-ss-ico{flex:0 0 auto;font-size:13px;color:#8a94a3}
.dnss-ss-on .dnss-ss-ico{color:$dark}
.dnss-ss-name{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dnss-ss-state{flex:0 0 auto;font-size:13px;white-space:nowrap;color:#5b6573}
.dnss-ss-on .dnss-ss-state{color:#1a7f4b;font-weight:600}
@media(prefers-reduced-motion:no-preference){.dnss-notice-btn svg,.dnss-notice-btn i{transition:transform .15s}}
</style>";
}

// ---------------------------------------------------------------------------
// Partie droite de la branche « cache DNS local » (maquette
// stitch_artica_local_dns_cache_admin, 2026-11-20). La colonne d'etat du
// service a gauche (dns-apply-system, dnscache-status) n'est PAS touchee.
// Ecarte de la maquette : « Primary Zone » / « Default » sous les domaines de
// recherche (aucune donnee), le sous-titre « Underlying daemon network binding ».
// ---------------------------------------------------------------------------
function dnlc_known():array{
	return array("8.8.8.8"=>"Google","8.8.4.4"=>"Google","1.1.1.1"=>"Cloudflare","1.0.0.1"=>"Cloudflare",
		"9.9.9.9"=>"Quad9","149.112.112.112"=>"Quad9","208.67.222.222"=>"OpenDNS","208.67.220.220"=>"OpenDNS",
		"255.255.255.255"=>"{EuropeanpublicDNSresolvers}");
}
// internal | public | "" (vide ou non-IP : un nom d'hote ne se classe pas)
function dnlc_kind(string $ip):string{
	if($ip===""){return "";}
	if($ip==="255.255.255.255"){return "public";}
	if(!filter_var($ip,FILTER_VALIDATE_IP)){return "";}
	if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)){return "internal";}
	return "public";
}
function dnlc_model():array{
	$m=dnss_model();
	$known=dnlc_known();
	$m["res"]=array();$m["configured"]=0;$m["has_internal"]=false;$m["has_public"]=false;$m["has_doh"]=false;
	foreach($m["resolvers"] as $i=>$ip){
		$k=dnlc_kind($ip);
		$m["res"][$i]=array("ip"=>$ip,"kind"=>$k,"name"=>isset($known[$ip])?$known[$ip]:"");
		if($ip!==""){$m["configured"]++;}
		if($k=="internal"){$m["has_internal"]=true;}
		if($k=="public"){$m["has_public"]=true;}
		if(isset($known[$ip])){$m["has_doh"]=true;}
	}
	$m["mixed"]=$m["has_internal"] && $m["has_public"];
	return $m;
}
function dnlc_h($s):string{return dnss_h($s);}
function dnlc_help(string $tok):string{return "<p class='dnlc-help'>{".$tok."}</p>";}
function dnlc_card_head(string $ico,string $title,string $count,string $right,string $sub):string{
	$c=$count!==""?"<span class='ak-count'>$count</span>":"";
	return "<div class='dnlc-head'><div class='dnlc-ht'><span class='dnlc-hico' aria-hidden='true'><i class='$ico'></i></span><div><h3>$title $c</h3><p>$sub</p></div></div>"
		.($right!==""?"<div class='dnlc-hr'>$right</div>":"")."</div>";
}
function dnlc_btn(string $js,string $ico,string $label,string $cls="dnlc-btn"):string{
	return "<button type='button' class='$cls' OnClick=\"$js\"><i class='$ico' aria-hidden='true'></i> $label</button>";
}
function dnlc_icon_btn(string $js,string $ico,string $aria,string $cls="dnlc-ib"):string{
	return "<button type='button' class='$cls' aria-label=\"$aria\" title=\"$aria\" OnClick=\"$js\"><i class='$ico' aria-hidden='true'></i></button>";
}

function dnlc_banner(array $m):string{
	$chips=array();
	$chips[]="<span class='dnlc-pill'><i class='fas fa-bolt' aria-hidden='true'></i>".dnss_tr("dnlc_chip_cache",array($m["cache_mb"]))."</span>";
	$chips[]="<span class='dnlc-pill'><i class='fas fa-shuffle' aria-hidden='true'></i>{dnlc_chip_fastest}</span>";
	if($m["has_doh"]){$chips[]="<span class='dnlc-pill'><i class='fas fa-lock' aria-hidden='true'></i>{dnlc_chip_doh}</span>";}
	if($m["safesearch_on"]>0){
		$chips[]="<span class='dnlc-pill'><i class='fas fa-shield-halved' aria-hidden='true'></i>".dnss_tr("dnlc_chip_ss",array($m["safesearch_on"],count($m["safesearch"])))."</span>";
	}
	return "<section class='dnlc-card dnlc-banner'><span class='dnlc-bico' aria-hidden='true'><i class='fas fa-server'></i></span>"
		."<div class='dnlc-btxt'><h2>{dnlc_banner_title}</h2><p>{dnlc_banner_text}</p><div class='dnlc-chips'>".implode("",$chips)."</div></div></section>";
}
// Seulement quand la liste melange serveurs internes et publics.
function dnlc_mixed_alert(array $m):string{
	if(!$m["mixed"]){return "";}
	$page=CurrentPageName();
	$btn=$m["edit"]?dnlc_btn("Loadjs('$page?dnscache-add-js=yes&function=RefreshDNSMainSection')","fas fa-plus","{dnlc_add_zone}","dnlc-btn dnlc-btn-warn"):"";
	return "<div class='dnlc-alert' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
		."<div><b>{dnlc_mixed_title}</b><p>{dnlc_mixed_text}</p></div>$btn</div>";
}
function dnlc_resolver_value(array $r):string{
	if($r["ip"]===""){return "<span class='dnss-none'>{not_used}</span>";}
	$ip=dnlc_h($r["ip"]);
	if($r["name"]!==""){
		$v="<span class='dnlc-chip' title=\"$ip\">{$r["name"]} <span class='dnlc-doh' title=\"{dnlc_doh_help}\">DoH</span></span>";
	}else{
		$v="<span class='dnlc-chip dnlc-mono'>$ip</span>";
	}
	if($r["kind"]=="internal"){$v.="<span class='dnlc-kind'>{dnlc_internal}</span>";}
	if($r["kind"]=="public"){$v.="<span class='dnlc-kind'>{dnlc_public}</span>";}
	return $v;
}
function dnlc_card_resolvers(array $m):string{
	$page=CurrentPageName();
	$labels=array(1=>"{primary_dns}",2=>"{secondary_dns}",3=>"{dnlc_ns3}");
	$h=array("<section class='dnlc-card'>");
	$h[]=dnlc_card_head("fas fa-server","{dnss_upstream}",dnss_tr("dnlc_configured",array($m["configured"],3)),"","{dnlc_upstream_help}");
	$h[]="<ol class='dnlc-res'>";
	foreach($labels as $i=>$label){
		$r=$m["res"][$i];
		$js="Loadjs('$page?dns-edit-js=$i&function=RefreshDNSMainSection')";
		$act="";
		if($m["edit"]){
			$act=$r["ip"]===""?"<a href='javascript:blur();' class='dnlc-link' OnClick=\"$js\">{dnlc_add}</a>":dnlc_icon_btn($js,"fas fa-pen","{dnss_edit} $label");
		}
		$h[]="<li><span class='dnlc-num'>$i</span><span class='dnlc-rl'>$label</span><span class='dnlc-rv'>".dnlc_resolver_value($r)."</span><span class='dnlc-ra'>$act</span></li>";
	}
	$h[]="</ol>";
	if(!$m["mixed"] && $m["configured"]>1){
		$h[]="<p class='dnlc-note'><i class='fas fa-circle-info' aria-hidden='true'></i> {dnlc_fastest_note}</p>";
	}
	$h[]=dnlc_dns4eu($m);
	$h[]="</section>";
	return implode("",$h);
}
function dnlc_dns4eu(array $m):string{
	$types=array(0=>"{inactive2}",1=>"{protective_resolution}",2=>"{child_protection}",3=>"{ads_protection}",
		4=>"{child_protection} &amp; {ads_protection}",5=>"{unfiltered_resolution}");
	$v=isset($types[$m["dns4eu"]])?$m["dns4eu"]:0;
	$pill=$v==0?"<span class='dnlc-mode off'><i class='fas fa-minus' aria-hidden='true'></i>{$types[$v]}</span>"
		:"<span class='dnlc-mode'><i class='fas fa-check' aria-hidden='true'></i>{$types[$v]}</span>";
	$btn=$m["edit"]?dnlc_btn("Loadjs('fw.dns.dnsforeu.php')","fas fa-gear","{dnsys_configure}","dnlc-btn dnlc-btn-sec"):"";
	return "<div class='dnlc-eu'><div class='dnlc-eu-h'><span class='dnlc-rl'><i class='fas fa-shield-halved' aria-hidden='true'></i> {UseDNSForEUBackends}</span><span class='dnlc-eu-r'>$pill$btn</span></div>"
		.dnlc_help("dnlc_dns4eu_help")."</div>";
}
function dnlc_zone_tags(array $z):string{
	$t=array();
	$add=function($cls,$label,$tip) use (&$t){$t[]="<span class='dnlc-tag$cls' title=\"$tip\" tabindex='0'>$label</span>";};
	if(intval($z["useTLS"] ?? 0)==1){$add("","TLS","{dnlc_tip_tls}");}
	if(intval($z["recursive"] ?? 0)==1){$add("","{dnss_recursive}","{dnlc_tip_recursive}");}
	if(intval($z["transparent"] ?? 0)==1){$add("","{transparent}","{dnlc_tip_transparent}");}
	if(intval($z["bulk"] ?? 0)==1){$add("","{group}","{dnlc_tip_group}");}
	if(intval($z["insecure"] ?? 0)==1 || intval($z["onlyinsecure"] ?? 0)==1){$add(" warn","{dnss_insecure}","{dnlc_tip_insecure}");}
	if(count($t)==0){return "<span class='dnss-dash' aria-hidden='true'>—</span>";}
	return "<span class='dnlc-tags'>".implode("",$t)."</span>";
}
function dnlc_zone_row(array $z,bool $edit):string{
	$page=CurrentPageName();
	$ID=intval($z["ID"]);
	$md=md5(serialize($z));
	$zone=dnlc_h(trim((string)$z["zone"]));
	$srv=dnlc_h($z["hostname"].":".$z["port"]);
	$del=$edit?dnlc_icon_btn("Loadjs('$page?dnscache-del-js=$ID&function=RefreshDNSMainSection&md=$md')","fas fa-trash-can","{dnss_delete_zone} $zone","dnlc-ib dnlc-del"):"";
	return "<tr id='$md'><td class='dnlc-zone'>$zone</td><td class='dnlc-mono'>$srv</td><td>".dnlc_zone_tags($z)."</td><td class='dnlc-act'>$del</td></tr>";
}
function dnlc_card_zones(array $m):string{
	$page=CurrentPageName();
	$n=count($m["zones"]);
	$js="Loadjs('$page?dnscache-add-js=yes&function=RefreshDNSMainSection')";
	$add=$m["edit"]?dnlc_btn($js,"fas fa-plus","{new_forward_zone}","dnlc-btn"):"";
	$h=array("<section class='dnlc-card'>");
	$h[]=dnlc_card_head("fas fa-diagram-project","{forward_zones}",$n>0?dnss_zone_count($n):"",$n>0?$add:"","{dnlc_zones_help}");
	if($n==0){
		$h[]="<div class='dnlc-empty'><span class='dnlc-eico' aria-hidden='true'><i class='fas fa-diagram-project'></i></span><b>{dnlc_zones_empty_title}</b><p>{dnlc_zones_empty_text}</p>$add</div></section>";
		return implode("",$h);
	}
	$h[]="<div class='dnlc-scroll'><table class='dnlc-tbl'><thead><tr><th scope='col'>{domain}</th><th scope='col'>{dnlc_server}</th><th scope='col'>{dnss_options}</th><th scope='col'><span class='sr-only'>{dnss_actions}</span></th></tr></thead><tbody>";
	foreach($m["zones"] as $z){$h[]=dnlc_zone_row($z,$m["edit"]);}
	$h[]="</tbody></table></div></section>";
	return implode("",$h);
}
function dnlc_card_domains(array $m):string{
	$page=CurrentPageName();
	$btn=$m["edit"]?dnlc_icon_btn("Loadjs('$page?dns-domains-js=yes&domain=1&function=RefreshDNSMainSection')","fas fa-pen","{dnss_edit} {search_domains}"):"";
	$h=array("<section class='dnlc-card'>");
	$h[]=dnlc_card_head("fas fa-globe","{search_domains}","",$btn,"{dnlc_domains_help}");
	$h[]="<ol class='dnlc-res dnlc-dom'>";
	foreach($m["domains"] as $i=>$d){
		$v=$d==""?"<span class='dnss-none'>{not_used}</span>":"<span class='dnlc-chip dnlc-mono'>".dnlc_h($d)."</span>";
		$h[]="<li><span class='dnlc-num'>$i</span><span class='dnlc-rv'>$v</span></li>";
	}
	$h[]="</ol></section>";
	return implode("",$h);
}
function dnlc_opt_row(string $label,string $value,string $help):string{
	return "<div class='dnlc-opt'><div><span class='dnlc-rl'>$label</span>".dnlc_help($help)."</div><span class='dnlc-ov'>$value</span></div>";
}
function dnlc_card_options(array $m):string{
	$btn=$m["edit"]?dnlc_icon_btn(dnscache_options_js(),"fas fa-pen","{dnss_edit} {service_options}"):"";
	$h=array("<section class='dnlc-card'>");
	$h[]=dnlc_card_head("fas fa-sliders","{service_options}","",$btn,"{dnlc_options_help}");
	$h[]=dnlc_opt_row("{listen_interface}","<span class='dnlc-mono'>{$m["listen"]}</span>","dnlc_listen_help");
	$h[]=dnlc_opt_row("{outgoing_interface}","<span class='dnlc-mono'>{$m["outgoing"]}</span>","dnlc_out_help");
	$h[]=dnlc_opt_row("{dnss_timeout}",$m["timeout"]>0?dnss_tr("dnlc_seconds",array($m["timeout"])):"<span class='dnss-none'>—</span>","dnlc_timeout_help");
	$h[]=dnlc_opt_row("{ipcache_size}","<b>{$m["cache_mb"]}</b>&nbsp;MB","dnlc_cache_help");
	$h[]="</section>";
	return implode("",$h);
}
function dnlc_ss_tile(array $e):string{
	$st=$e["on"]?"<span class='dnlc-ss-st'><i class='fas fa-check' aria-hidden='true'></i>{dnss_on}</span>":"<span class='dnlc-ss-st'><i class='fas fa-minus' aria-hidden='true'></i>{dnss_off}</span>";
	return "<div class='dnlc-ss".($e["on"]?" on":"")."' title=\"".dnlc_h($e["label"])."\"><span class='dnlc-ss-ico' aria-hidden='true'><i class='{$e["ico"]}'></i></span><span class='dnlc-ss-n'>".dnlc_h($e["short"])."</span>$st</div>";
}
function dnlc_card_safesearch(array $m):string{
	$page=CurrentPageName();
	$btn=$m["edit"]?dnlc_btn("Loadjs('$page?SafeSearch-edit-options-js=yes&domain=".time()."&function=RefreshDNSMainSection')","fas fa-list-check","{dnlc_choose_engines}","dnlc-btn dnlc-btn-sec"):"";
	$h=array("<section class='dnlc-card'>");
	$h[]=dnlc_card_head("fas fa-shield-halved","SafeSearch",dnss_tr("dnlc_ss_count",array($m["safesearch_on"],count($m["safesearch"]))),$btn,"{dnlc_ss_help}");
	$h[]="<div class='dnlc-ss-grid'>";
	foreach($m["safesearch"] as $e){$h[]=dnlc_ss_tile($e);}
	$h[]="</div></section>";
	return implode("",$h);
}
function dnlc_style():string{
	$acc="#005447";$dark="#003d33";$soft="#e6eeed";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent_dark"])){$acc=$a["accent_dark"];}
	}
	return "<style>
.dnlc{display:flex;flex-direction:column;gap:20px;min-width:0;color:#1f2937}
.dnlc-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);min-width:0}
.dnlc-banner{display:flex;gap:18px;align-items:flex-start;padding:22px 24px;border-left:4px solid $acc}
.dnlc-bico{flex:0 0 48px;height:48px;border-radius:12px;background:$soft;color:$acc;display:flex;align-items:center;justify-content:center;font-size:20px}
.dnlc-btxt{min-width:0}
.dnlc-btxt h2{margin:0 0 6px 0;font-size:19px;font-weight:600;color:#111827;line-height:1.3}
.dnlc-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55;max-width:860px}
.dnlc-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.dnlc-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f2f6f6;border:1px solid $soft;color:$acc;font-size:13px}
.dnlc-alert{display:flex;gap:14px;align-items:flex-start;padding:16px 18px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#78350f}
.dnlc-alert>i,.dnlc-alert>svg{color:#d97706;font-size:18px;margin-top:2px;flex:0 0 auto}
.dnlc-alert>div{flex:1 1 auto;min-width:0}
.dnlc-alert b{display:block;font-size:14px;color:#78350f}
.dnlc-alert p{margin:4px 0 0 0;font-size:13px;line-height:1.55}
.dnlc-grid{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:20px;align-items:start}
.dnlc-col{display:flex;flex-direction:column;gap:20px;min-width:0}
@media(max-width:1599px){.dnlc-grid{grid-template-columns:minmax(0,1fr)}}
.dnlc-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:12px 16px;padding:16px 20px 12px 20px;border-bottom:1px solid #eef0f3}
.dnlc-ht{display:flex;gap:12px;align-items:flex-start;min-width:0;flex:1 1 260px}
.dnlc-hico{flex:0 0 34px;height:34px;border-radius:8px;background:#f2f6f6;color:$acc;display:flex;align-items:center;justify-content:center;font-size:15px}
.dnlc-ht h3{margin:0;font-size:15px;font-weight:600;color:#111827;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.dnlc-ht p{margin:3px 0 0 0;font-size:13px;color:#6b7280;line-height:1.5}
.dnlc-hr{display:flex;gap:8px;align-items:center;flex:0 0 auto}
.dnlc-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1px solid $acc;background:$acc;color:#fff;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.dnlc-btn:hover{background:$dark;border-color:$dark}
.dnlc-btn-sec{background:#fff;color:$acc;border-color:#bfd4d1}
.dnlc-btn-sec:hover{background:$soft;color:$dark}
.dnlc-btn-warn{background:#fff;color:#92400e;border-color:#fcd34d;flex:0 0 auto}
.dnlc-btn-warn:hover{background:#fef3c7;color:#78350f;border-color:#f59e0b}
.dnlc-ib{width:32px;height:32px;border-radius:8px;border:1px solid #e5e7eb;background:#fff;color:#4b5563;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto}
.dnlc-ib:hover{background:$soft;color:$acc;border-color:#bfd4d1}
.dnlc-del:hover{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.dnlc-btn:focus-visible,.dnlc-ib:focus-visible,.dnlc-link:focus-visible,.dnlc-tag:focus-visible{outline:2px solid $acc;outline-offset:2px}
.dnlc-link{color:$acc;font-weight:600;font-size:13px;text-decoration:underline;cursor:pointer}
.dnlc-res{list-style:none;margin:0;padding:0}
.dnlc-res li{display:flex;align-items:center;gap:12px;padding:0 20px;min-height:52px;border-top:1px solid #f1f3f5}
.dnlc-res li:first-child{border-top:0}
.dnlc-num{flex:0 0 24px;height:24px;border-radius:50%;background:#f3f4f6;color:#4b5563;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center}
.dnlc-rl{font-size:13px;color:#4b5563;font-weight:600;white-space:nowrap}
.dnlc-res .dnlc-rl{flex:0 0 160px}
.dnlc-rv{flex:1 1 auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0}
.dnlc-ra{flex:0 0 auto}
.dnlc-chip{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:6px;background:#f3f4f6;color:#111827;font-size:13px;font-weight:600;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dnlc-mono{font-family:monospace;font-size:13px}
.dnlc-doh{font-size:13px;font-weight:700;letter-spacing:.02em;line-height:1.2;color:$acc;background:$soft;border-radius:4px;padding:1px 5px}
.dnlc-kind{font-size:13px;color:#6b7280;border:1px solid #e5e7eb;border-radius:999px;padding:1px 8px}
.dnlc-note{margin:0;padding:10px 20px 12px 20px;font-size:13px;color:#6b7280}
.dnlc-note i{margin-right:4px}
.dnlc-eu{border-top:1px solid #eef0f3;background:#fafbfb;padding:12px 20px 14px 20px;border-radius:0 0 12px 12px}
.dnlc-eu-h{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.dnlc-eu-r{display:flex;align-items:center;gap:10px}
.dnlc-mode{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:$soft;color:$acc;font-size:13px;font-weight:600}
.dnlc-mode.off{background:#f3f4f6;color:#4b5563}
.dnlc-help{margin:4px 0 0 0;font-size:13px;color:#6b7280;line-height:1.5}
.dnlc-scroll{overflow-x:auto}
.dnlc-tbl{width:100%;border-collapse:collapse}
.dnlc-tbl th{text-align:left;font-size:13px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;padding:10px 20px;border-bottom:1px solid #eef0f3;white-space:nowrap}
.dnlc-tbl td{padding:12px 20px;border-bottom:1px solid #f1f3f5;font-size:13px;color:#111827;vertical-align:middle}
.dnlc-tbl tr:last-child td{border-bottom:0}
.dnlc-zone{font-family:monospace;font-weight:700;word-break:break-all}
.dnlc-act{width:1%;text-align:right;white-space:nowrap}
.dnlc-tags{display:inline-flex;flex-wrap:wrap;gap:6px}
.dnlc-tag{font-size:13px;padding:1px 8px;border-radius:999px;background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;cursor:help}
.dnlc-tag.warn{background:#fffbeb;color:#92400e;border-color:#fde68a}
.dnlc-empty{text-align:center;padding:32px 24px;display:flex;flex-direction:column;align-items:center;gap:8px}
.dnlc-eico{width:48px;height:48px;border-radius:50%;background:#f2f6f6;color:$acc;display:flex;align-items:center;justify-content:center;font-size:18px}
.dnlc-empty b{font-size:15px;color:#111827}
.dnlc-empty p{margin:0 0 6px 0;font-size:13px;color:#6b7280;max-width:460px;line-height:1.55}
.dnlc-opt{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:12px 20px;border-top:1px solid #f1f3f5}
.dnlc-head+.dnlc-opt{border-top:0}
.dnlc-opt>div{min-width:0}
.dnlc-ov{font-size:14px;color:#111827;text-align:right;white-space:nowrap;flex:0 0 auto}
.dnlc-ss-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;padding:16px 20px}
.dnlc-ss{display:flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:8px;padding:9px 10px;font-size:13px;color:#4b5563;min-width:0}
.dnlc-ss.on{border-color:#bfd4d1;background:#f2f6f6;color:#111827}
.dnlc-ss-ico{flex:0 0 auto;color:#9ca3af}
.dnlc-ss.on .dnlc-ss-ico{color:$acc}
.dnlc-ss-n{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dnlc-ss-st{flex:0 0 auto;display:inline-flex;align-items:center;gap:4px;white-space:nowrap}
.dnlc-ss.on .dnlc-ss-st{color:$acc;font-weight:600}
@media(max-width:1300px){.dnlc-res .dnlc-rl{flex-basis:130px}}
@media(prefers-reduced-motion:no-preference){.dnlc-card{animation:dnlcIn .15s ease-out}}
@keyframes dnlcIn{from{opacity:0}to{opacity:1}}
</style>";
}

function dnscache_topbuttons($tpl,$page):string{
	$users=new usersMenus();
	if(!$users->AsDnsAdministrator){return "";}
	$top=array();
	$top[]=array("Loadjs('$page?dnscache-add-js=yes&function=RefreshDNSMainSection');",ico_plus,"{new_forward_zone}");
	$top[]=array("Loadjs('$page?restart-localdnscache');",ico_refresh,"{reconfigure_service}");
	return $tpl->table_buttons($top);
}

function table_dnscache():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	$m=dnlc_model();

	$html=array();
	$html[]="<div class='dnss-root ak'>";
	$html[]="<div class='dnss-side'>";
	// Zone de progression HORS du conteneur rafraichi : elle survit aux
	// rechargements de dnscache-status.
	$html[]="<div id='dns-apply-system'></div>";
	$html[]="<div id='dnscache-status'>";
	if($UnboundEnabled==1){
		$html[]="<div id='unbound-status' style='margin-top:10px'></div>";
	}
	$html[]="</div></div>";
	$html[]="<div class='dnss-main dnlc'>";
	$html[]=dnlc_banner($m);
	$html[]=dnlc_mixed_alert($m);
	$html[]="<div class='dnlc-grid'>";
	$html[]="<div class='dnlc-col'>".dnlc_card_resolvers($m).dnlc_card_zones($m)."</div>";
	$html[]="<div class='dnlc-col'>".dnlc_card_domains($m).dnlc_card_options($m).dnlc_card_safesearch($m)."</div>";
	$html[]="</div></div></div>";

	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{dns_used_by_the_system}";
	$TINY_ARRAY["ICO"]="fa fa-server";
	$TINY_ARRAY["EXPL"]="{dns_servers_explain}<br>{APP_DNS_LOCAL_CACHE_EXPLAIN}";
	$TINY_ARRAY["URL"]="dns-servers";
	$TINY_ARRAY["BUTTONS"]=dnscache_topbuttons($tpl,$page);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$RefreshDNSCacheSection=$tpl->RefreshInterval_js("dnscache-status",$page,"dnscache-status=yes");
	if($UnboundEnabled==1){
		$RefreshDNSCacheSection=$tpl->RefreshInterval_js("unbound-status","fw.dns.unbound.php","unbound-status=yes");
	}

	$js=array();
	$js[]="<script>";
	$js[]="function RefreshDNSMainSection(){";
	$js[]="LoadAjax('dns-servers-main-table','$page?table2=yes');";
	$js[]="}";
	$js[]="function RefreshDNSCacheSection(){";
	$js[]=$RefreshDNSCacheSection;
	$js[]="$jstiny;";
	$js[]="}";
	$js[]="RefreshDNSCacheSection();";
	$js[]="</script>";

	// CSS et script restent hors de l'analyse : la regex #{(.+?)}# y verrait un
	// {token} par bloc.
	echo dnss_style().dnlc_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).@implode("\n",$js);
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
	$function=dnsfw_function();
	$function_js="$function()";
	echo $tpl->form_outside(null,$form,null,"{apply}","$function_js;dialogInstance2.close();","AsDnsAdministrator");
	return true;
}
function dnsdomains_edit_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$resolv=new resolv_conf();
	// Liste fermee : toute cle postee partait dans resolv.conf.
	$set=array();
	foreach (array("DOMAINS1","DOMAINS2","DOMAINS3") as $key){
		if(!isset($_POST[$key])){continue;}
		if(!dnsfw_search_domain_ok($_POST[$key])){ echo $tpl->post_error("{InternalDomain}: {wrong_value}");return false;}
		$set[$key]=trim((string)$_POST[$key]);
	}
	foreach($set as $key=>$val){$resolv->MainArray[$key]=$val;}
	$resolv->save();
	return admin_tracks("Save DNS local domains ".implode(" ",$set));
}

// ===== DNS du systeme sans cache DNS local (maquette stitch_dns_manager_console) =====
// table2() rend la colonne de gauche inchangee (widget de service, rafraichi) et,
// a droite, des cartes construites sur un modele lu une seule fois.

// Le chemin reel suit articarest resolv.parseMainArray() : un service DNS local
// (Unbound sans « enforce », dnsdist, PowerDNS Recursor) passe en tete (127.0.0.1)
// sauf si DontUseLocalDns=1 ; un client de cluster HA prend les DNS du cluster.
function dnsys_model():array{
	$sock=$GLOBALS["CLASS_SOCKETS"];
	$resolv=new resolv_conf();
	$gi=function($k) use ($sock){ return intval($sock->GET_INFO($k)); };
	$m=array();
	$m["locked"]=(bool)$sock->DebianAgentLocked();
	$m["edit"]=!$m["locked"] && dnsfw_can_edit();
	$m["error"]="";
	$m["cluster"]=$gi("HaClusterClient")==1;
	$m["cluster_lb"]=false;
	$unbound=$gi("UnboundEnabled")==1;
	$dnsdist=$gi("EnableDNSDist")==1;
	$recursor=$gi("PowerDNSEnableRecursor")==1;

	$m["dont_local"]=intval($resolv->MainArray["DontUseLocalDns"] ?? 0)==1;
	if(!$unbound && !$dnsdist){$m["dont_local"]=false;}
	$m["root"]=$unbound && $gi("EnableDNSRootInts")==1;
	$m["doh"]=$dnsdist && $gi("UseEuropeenDNSBackends")==1;
	$m["local"]=!$m["dont_local"] && (($unbound && $gi("UnboundEnforceUnknownDomains")==0) || $dnsdist || $recursor);

	$m["servers"]=array();
	for($i=1;$i<4;$i++){
		$v=trim((string)($resolv->MainArray["DNS$i"] ?? ""));
		if(strlen($v)>2){$m["servers"][$i]=$v;}
	}
	$m["domains"]=array();
	for($i=1;$i<4;$i++){
		$v=trim((string)($resolv->MainArray["DOMAINS$i"] ?? ""));
		if($i==1 && !$resolv->isValidDomain($v)){$v="localhost.local";}
		if(strlen($v)>2){$m["domains"][]=$v;}
	}
	$m["timeout"]=intval($resolv->MainArray["TIMEOUT"] ?? 2);
	$m["attempts"]=intval($resolv->MainArray["ATTEMPTS"] ?? 2);
	$m["rotation"]=intval($resolv->MainArray["USEROTATION"] ?? 0)==1;
	$m["tcp"]=$gi("ResolvConfUseTCP")==1;
	$m["cloudflared"]=$gi("EnableCloudflared")==1;
	$m["dns4eu"]=$gi("UseDNSForEUBackends");
	$m["dns4eu_effective"]=$unbound || $dnsdist;

	// Amont « * » de l'Unbound local : la ou partent vraiment les requetes du cache.
	$m["forwarders"]=array();
	if($unbound && !$m["root"]){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$results=$q->QUERY_SQL("SELECT hostname,port FROM pdns_fwzones WHERE zone='*'");
		if(is_array($results)){
			foreach($results as $ligne){$m["forwarders"][]=$ligne["hostname"].":".$ligne["port"];}
		}
	}
	if($m["cluster"]){$m=dnsys_cluster_model($m);}

	if($m["cluster"]){$m["mode"]=$m["cluster_lb"]?"cluster_lb":"cluster";}
	elseif($m["doh"]){$m["mode"]="doh";}
	elseif($m["local"]){$m["mode"]=$m["root"]?"root":"local";}
	else{$m["mode"]="direct";}
	return $m;
}
// Client de cluster HA : serveurs et domaines viennent de la configuration du cluster.
function dnsys_cluster_model(array $m):array{
	$m["edit"]=false;
	$m["local"]=false;
	$m["root"]=false;
	$m["doh"]=false;
	$m["forwarders"]=array();
	$m["servers"]=array();
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->HACLUSTERCLIENT_API("/config"),true);
	if(!is_array($json)){$json=array();}
	if(isset($json["Status"]) && !$json["Status"]){
		$m["error"]=(string)($json["Error"] ?? "{error}");
		return $m;
	}
	$gb=@unserialize((string)($json["HaClusterGBConfig"] ?? ""));
	if(!is_array($gb)){$gb=array();}
	if(intval($gb["HaClusterUseLBAsDNS"] ?? 0)==1){
		$m["cluster_lb"]=true;
		$addr=trim((string)($json["HaClusterUseAddr"] ?? ""));
		if($addr!=="" && $addr!=="0.0.0.0"){$m["servers"][1]=$addr;}
		return $m;
	}
	if(strlen(trim((string)($gb["DNS1"] ?? "")))>2){$m["servers"][1]=trim($gb["DNS1"]);}
	if(strlen(trim((string)($gb["DNS2"] ?? "")))>2){$m["servers"][2]=trim($gb["DNS2"]);}
	$domains=array();
	foreach(array("DOMAINS1","DOMAINS2") as $k){
		if(strlen(trim((string)($gb[$k] ?? "")))>3){$domains[]=trim($gb[$k]);}
	}
	if(count($domains)>0){$m["domains"]=$domains;}
	return $m;
}

function dnsys_h($s):string{return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");}
function dnsys_onoff(bool $on):string{
	if($on){return "<span class='ak-pill ak-ok'><i class='fas fa-check' aria-hidden='true'></i>{dnsys_on}</span>";}
	return "<span class='ak-pill ak-off'><i class='fas fa-minus' aria-hidden='true'></i>{dnsys_off}</span>";
}

// Bande du chemin de resolution : noeuds dans l'ordre reel + mode.
function dnsys_path(array $m):string{
	$nodes=array(array("fas fa-server","{dnsys_node_system}",""));
	$modes=array(
		"direct"=>"{dnsys_mode_direct}","local"=>"{dnsys_mode_local}","root"=>"{dnsys_mode_root}",
		"doh"=>"{dnsys_mode_doh}","cluster"=>"{dnsys_mode_cluster}","cluster_lb"=>"{dnsys_mode_cluster_lb}");
	switch($m["mode"]){
		case "local":
			$nodes[]=array("fas fa-database","{dnsys_node_local}","is-acc");
			$nodes[]=array("fas fa-network-wired","{dnsys_node_servers}","");
			break;
		case "root":
			$nodes[]=array("fas fa-database","{dnsys_node_local}","is-acc");
			$nodes[]=array("fas fa-sitemap","{dnsys_node_root}","");
			break;
		case "doh":
			$nodes[]=array("fas fa-database","{dnsys_node_local}","");
			$nodes[]=array("fas fa-lock","{dnsys_node_doh}","is-acc");
			break;
		case "cluster_lb":
			$nodes[]=array("fas fa-code-branch","{dnsys_node_cluster}","is-acc");
			break;
		default:
			$nodes[]=array("fas fa-network-wired","{dnsys_node_servers}","is-acc");
	}
	$nodes[]=array("fas fa-globe","{dnsys_node_internet}","");
	$h=array();
	foreach($nodes as $i=>$n){
		if($i>0){$h[]="<i class='fas fa-arrow-right dnsys-arrow' aria-hidden='true'></i>";}
		$h[]="<span class='dnsys-node'><span class='dnsys-node-i {$n[2]}' aria-hidden='true'><i class='{$n[0]}'></i></span><b>{$n[1]}</b></span>";
	}
	return "<section class='ak-card dnsys-path' aria-label=\"{dnsys_path_aria}\"><div class='dnsys-flow'>".implode("",$h)."</div>"
		."<span class='ak-tag dnsys-mode'><span class='ak-dot' aria-hidden='true'></span>{$modes[$m["mode"]]}</span></section>";
}

// Libelle d'un serveur : Cloudflared remplace 1.1.1.1 par son relais local.
function dnsys_server_value(array $m,string $ip):string{
	if($m["cloudflared"] && $ip=="1.1.1.1"){
		return "<span class='ak-tag' title='1.1.1.1'>{APP_CLOUDFLARE_DNS}</span>";
	}
	return "<span class='ak-chip'>".dnsys_h($ip)."</span>";
}
function dnsys_server_row(int $n,string $label,string $value):string{
	return "<li class='dnsys-srv'><span class='dnsys-srv-n' aria-hidden='true'>$n</span><span class='dnsys-srv-l'>$label</span><span class='dnsys-srv-v'>$value</span></li>";
}
function dnsys_edit_link(array $m):string{
	if(!$m["edit"]){return "";}
	$page=CurrentPageName();
	return "<button type='button' class='ak-btn ak-btn-sm ak-btn-ghost' OnClick=\"Loadjs('$page?dns-standard-js=yes')\"><i class='fas fa-pen' aria-hidden='true'></i>{dnsys_edit}</button>";
}

function dnsys_card_servers(array $m):string{
	$labels=array(1=>"{primary_dns}",2=>"{secondary_dns}",3=>"{dnsys_dns3}");
	$h=array();
	$h[]="<section class='ak-card'>";
	$h[]=dnss_card_head("fas fa-server","{dnsys_servers_title}","",dnsys_edit_link($m));
	$h[]="<div class='dnsys-body'>";
	if($m["error"]!==""){
		$h[]="<div class='ak-banner ak-err'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> ".dnsys_h($m["error"])."</div>";
	}elseif($m["mode"]=="doh"){
		$doh=array("dns0.eu","quad9.net","doh.sb","dns.mullvad.net","anycast.uncensoreddns.org","dnspub.restena.lu","dns.digitale-gesellschaft.ch","dns.artikel10.org");
		$h[]="<p class='dnsys-help'>{dnsys_doh_note}</p><div class='ak-chips dnsys-chips'>";
		foreach($doh as $d){$h[]="<span class='ak-chip'>DoH $d</span>";}
		$h[]="</div>";
	}elseif($m["mode"]=="root"){
		$h[]="<div class='ak-banner ak-info'><i class='fas fa-sitemap' aria-hidden='true'></i> {dnsys_root_note}</div>";
	}elseif(count($m["servers"])==0){
		$h[]="<div class='ak-banner ak-err' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {dnsys_none}</div>";
	}else{
		if($m["mode"]=="local"){
			$h[]="<p class='dnsys-help'><i class='fas fa-circle-info' aria-hidden='true'></i> {dnsys_local_note}</p>";
		}
		$h[]="<ol class='dnsys-srvs'>";
		$n=0;
		foreach($m["servers"] as $i=>$ip){
			$n++;
			$label=$labels[$i];
			if($m["cluster_lb"]){$label="{dnsys_cluster_lb}";}
			$h[]=dnsys_server_row($n,$label,dnsys_server_value($m,$ip));
		}
		$h[]="</ol>";
		if(count($m["servers"])>1){
			$h[]="<p class='dnsys-help'><i class='fas fa-circle-info' aria-hidden='true'></i> ".($m["rotation"]?"{dnsys_order_rotation}":"{dnsys_order_note}")."</p>";
		}
	}
	if(count($m["forwarders"])>0){
		$h[]="<div class='dnsys-sub'><span class='dnsys-sub-t'>{dnsys_fwd_title}</span><span class='ak-chips'>";
		foreach($m["forwarders"] as $f){$h[]="<span class='ak-chip'>".dnsys_h($f)."</span>";}
		$h[]="</span></div>";
	}
	if($m["cluster"]){
		$h[]="<div class='ak-banner ak-warn dnsys-cluster'><i class='fas fa-code-branch' aria-hidden='true'></i> {hacluster_via_lb}</div>";
	}
	$h[]="</div></section>";
	return implode("",$h);
}

function dnsys_card_domains(array $m):string{
	$h=array();
	$h[]="<section class='ak-card'>";
	$h[]=dnss_card_head("fas fa-earth-europe","{InternalDomain}","",dnsys_edit_link($m));
	$h[]="<div class='dnsys-body'>";
	if(count($m["domains"])==0){
		$h[]="<span class='dnss-none'>{dnsys_no_domain}</span>";
	}else{
		$h[]="<div class='ak-chips dnsys-chips'>";
		foreach($m["domains"] as $d){$h[]="<span class='ak-chip'>".dnsys_h($d)."</span>";}
		$h[]="</div>";
	}
	$h[]="<p class='dnsys-help'>{dnsys_domains_help}</p></div></section>";
	return implode("",$h);
}

function dnsys_card_options(array $m):string{
	$h=array();
	$h[]="<section class='ak-card'>";
	$h[]=dnss_card_head("fas fa-sliders","{dnsys_options_title}","",dnsys_edit_link($m));
	$h[]=dnss_row("{dnsys_timeout}","<span class='ak-chip'>".intval($m["timeout"])." s</span>");
	$h[]=dnss_row("{dnsys_attempts}","<span class='ak-chip'>".intval($m["attempts"])."</span>");
	$h[]=dnss_row("{UseRotation}",dnsys_onoff($m["rotation"]));
	$h[]=dnss_row("{useTCPPort}",dnsys_onoff($m["tcp"]));
	$h[]=dnss_row("{dnsys_no_local}",dnsys_onoff($m["dont_local"]));
	$h[]="</section>";
	return implode("",$h);
}

function dnsys_card_dns4eu(array $m):string{
	if($m["cluster"] || $m["root"]){return "";}
	$types=array(0=>"{inactive2}",1=>"{protective_resolution}",2=>"{child_protection}",3=>"{ads_protection}",
		4=>"{child_protection} & {ads_protection}",5=>"{unfiltered_resolution}");
	$v=isset($types[$m["dns4eu"]])?$m["dns4eu"]:0;
	$on=$v>0 && $m["dns4eu_effective"];
	$chip=$on?"<span class='ak-pill ak-ok'><i class='fas fa-check' aria-hidden='true'></i>{$types[$v]}</span>"
		:"<span class='ak-pill ak-off'><span class='ak-dot' aria-hidden='true'></span>{$types[$v]}</span>";
	$btn="";
	if($m["edit"]){
		$btn="<button type='button' class='ak-btn ak-btn-sm' OnClick=\"Loadjs('fw.dns.dnsforeu.php')\"><i class='fas fa-gear' aria-hidden='true'></i>{dnsys_configure}</button>";
	}
	// Ambre seulement pour un choix fait mais sans effet ; sinon une note neutre.
	if($m["dns4eu_effective"]){
		$note="<p class='dnsys-help'><i class='fas fa-circle-info' aria-hidden='true'></i> {dnsys_dns4eu_note}</p>";
	}elseif($v>0){
		$note="<div class='ak-banner ak-warn'><i class='fas fa-circle-info' aria-hidden='true'></i> <span>{dnsys_dns4eu_note} {dnsys_dns4eu_unused}</span></div>";
	}else{
		$note="<p class='dnsys-help'><i class='fas fa-circle-info' aria-hidden='true'></i> {dnsys_dns4eu_note} {dnsys_dns4eu_unused}</p>";
	}
	return "<section class='ak-card dnsys-dns4eu'>".dnss_card_head("fas fa-cloud","{UseDNSForEUBackends}","","<span class='dnsys-acts'>$chip$btn</span>")
		."<div class='dnsys-body'>$note</div></section>";
}

// Regles propres aux cartes ci-dessus ; mise en page et cartes : dnss_style() + kit ak-.
function dnsys_style():string{
	if(isset($GLOBALS["DNSYS_STYLE"])){return "";}
	$GLOBALS["DNSYS_STYLE"]=true;
	$dark="#047857";$soft="rgba(16,185,129,.12)";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
	}
	return "<style>
.dnsys-main{display:flex;flex-direction:column;gap:16px}
.dnsys-main *{box-sizing:border-box}
.dnsys-path{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 20px}
.dnsys-flow{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.dnsys-node{display:inline-flex;align-items:center;gap:8px;font-size:14px;color:#111d23}
.dnsys-node b{font-weight:600}
.dnsys-node-i{width:32px;height:32px;border-radius:8px;background:#f1f5f9;color:#3d4947;display:inline-flex;align-items:center;justify-content:center;font-size:14px}
.dnsys-node-i.is-acc{background:$soft;color:$dark}
.dnsys-arrow{color:#94a3b8;font-size:13px}
.dnsys-mode{font-weight:600}
.dnsys-body{padding:16px 20px;display:flex;flex-direction:column;gap:12px}
.dnsys-help{margin:0;font-size:13px;color:#6d7a77;line-height:1.5}
.dnsys-help i,.dnsys-help svg{color:#94a3b8;margin-right:4px}
.dnsys-srvs{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.dnsys-srv{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fcfdfe}
.dnsys-srv-n{flex:0 0 28px;width:28px;height:28px;border-radius:999px;background:$soft;color:$dark;font-weight:700;font-size:13px;display:inline-flex;align-items:center;justify-content:center}
.dnsys-srv-l{flex:1 1 auto;font-size:14px;font-weight:500;color:#111d23;min-width:0}
.dnsys-srv-v{flex:0 0 auto;max-width:60%;overflow:hidden;text-overflow:ellipsis}
.dnsys-chips{flex-wrap:wrap}
.dnsys-sub{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding-top:12px;border-top:1px solid #eef1f4}
.dnsys-sub-t{font-size:13px;color:#3d4947;font-weight:600}
.dnsys-pair{align-items:start}
.dnsys-acts{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap}
.dnsys-dns4eu{background:#f8fafc}
@media(max-width:1200px){.dnsys-pair{grid-template-columns:minmax(0,1fr)}}
</style>";
}

function dnsys_tiny(array $m):string{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$buttons=array();
	if($m["edit"]){
		$buttons[]=array("Loadjs('$page?dns-standard-js=yes')",ico_edit,"{dnsys_edit_servers}");
	}
	$buttons[]=array("LoadAjax('dns-servers-main-table','$page?table2=yes')",ico_refresh,"{refresh}");
	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{dns_used_by_the_system}";
	$TINY_ARRAY["ICO"]="fa fa-server";
	$TINY_ARRAY["EXPL"]="{dns_servers_explain}";
	$TINY_ARRAY["URL"]="dns-servers";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($buttons);
	return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function table2():bool{
	$DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
	$tpl=new template_admin();
	$page=CurrentPageName();
	if($GLOBALS["CLASS_SOCKETS"]->DebianAgentLocked()){
		echo $tpl->_ENGINE_parse_body($tpl->div_warning("Artica Meta||{block_by_meta}"));
	}
	if($DoNotUseLocalDNSCache==0){
		table_dnscache();
		exit;
	}
	$m=dnsys_model();
	$html=array();
	$html[]="<div class='dnss-root ak'>";
	$html[]="<div class='dnss-side'>";
	// Zone de progression HORS du conteneur rafraichi, cf. dnscache_status().
	$html[]="<div id='dns-apply-system'></div>";
	$html[]="<div id='dnscache-status'></div>";
	$html[]="</div>";
	$html[]="<div class='dnss-main dnsys-main'>";
	$html[]=dnsys_path($m);
	$html[]=dnsys_card_servers($m);
	$html[]="<div class='ak-grid-2 dnsys-pair'>".dnsys_card_domains($m).dnsys_card_options($m)."</div>";
	$html[]=dnsys_card_dns4eu($m);
	$html[]="</div></div>";

	$js=$tpl->RefreshInterval_js("dnscache-status",$page,"dnscache-status=yes");
	echo dnss_style().dnsys_style().$tpl->_ENGINE_parse_body(implode("\n",$html))
		."<script>".dnsys_tiny($m).";$js;</script>";
	return true;
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
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}

	// Liste fermee des cles de resolv.conf, validees avant toute ecriture :
	// toute cle postee y partait telle quelle.
	$main=array();
	foreach(array("DNS1","DNS2","DNS3") as $k){
		if(!isset($_POST[$k])){continue;}
		if(!dnsfw_ip_ok($_POST[$k])){ echo $tpl->post_error("$k: {wrong_value}");return false;}
		$main[$k]=trim((string)$_POST[$k]);
	}
	foreach(array("DOMAINS1","DOMAINS2","DOMAINS3") as $k){
		if(!isset($_POST[$k])){continue;}
		if(!dnsfw_search_domain_ok($_POST[$k])){ echo $tpl->post_error("{InternalDomain}: {wrong_value}");return false;}
		$main[$k]=trim((string)$_POST[$k]);
	}
	foreach(array("TIMEOUT"=>300,"ATTEMPTS"=>20) as $k=>$max){
		if(!isset($_POST[$k]) || trim((string)$_POST[$k])===""){continue;}
		$v=intval($_POST[$k]);
		if($v<1 || $v>$max){ echo $tpl->post_error("$k: {wrong_value}");return false;}
		$main[$k]=$v;
	}
	foreach(array("USEROTATION","DontUseLocalDns") as $k){
		if(isset($_POST[$k])){$main[$k]=dnsfw_bool($_POST[$k]);}
	}
	$MB=null;
	if(isset($_POST["DnsProxyCache"])){
		$MB=intval($_POST["DnsProxyCache"]);
		if($MB<1 || $MB>65536){ echo $tpl->post_error("{ipcache_size}: {wrong_value}");return false;}
	}

	if(isset($_POST["ResolvConfUseTCP"])){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ResolvConfUseTCP",dnsfw_bool($_POST["ResolvConfUseTCP"]));
	}
	if(isset($_POST["UseEuropeenDNSBackends"])){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseEuropeenDNSBackends",dnsfw_bool($_POST["UseEuropeenDNSBackends"]));
	}
	if($MB!==null){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsProxyCache",$MB*1024*1024);
	}
	foreach ($main as $key=>$value){
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


	return admin_tracks("Saving DNS parameters: ".http_build_query($main,"",", "));
}

function DNS_PROXY_SAVE():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!dnsfw_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$MB=null;
	if(isset($_POST["DnsProxyCache"])){
		$MB=intval($_POST["DnsProxyCache"]);
		if($MB<1 || $MB>65536){ echo $tpl->post_error("{ipcache_size}: {wrong_value}");return false;}
	}
	if(isset($_POST["ResolvConfUseTCP"])){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ResolvConfUseTCP",dnsfw_bool($_POST["ResolvConfUseTCP"]));
	}
	if($MB!==null){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DnsProxyCache",$MB*1024*1024);
	}
	// La case affichee est « utiliser le systeme » : la cle stockee est son inverse.
	// Seule cette cle est ecrite (SAVE_POSTs ecrivait toute cle postee).
	$SquidDNSUseSystem=(intval($_POST["SquidDNSUseSystem"])==1)?0:1;
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDNSUseSystem",$SquidDNSUseSystem);
	$libmem=new lib_memcached();
	$libmem->Delkey("GOOD_DNS_SERVERS");
	return admin_tracks("Saving DNS parameters: SquidDNSUseSystem=$SquidDNSUseSystem");
}

?>	
	
	

