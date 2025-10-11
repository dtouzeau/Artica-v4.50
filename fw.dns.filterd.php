<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["UfdbListenInterface"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}


page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_DNSFILTERD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSFILTERD_VERSION");


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_DNSFILTERD} $APP_DNSFILTERD_VERSION </h1>
			<p>{APP_DNSFILTERD_EXPLAIN}</p>
		</div>
	</div>
	<div class='row'>
		<div id='progress-dnsfilter-restart'></div>
		<div class='ibox-content' style='min-height:600px'>
			<div id='table-dnsfilterd'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-dnsfilterd','$page?tabs=yes');
	$.address.state('/');
	$.address.value('/dnsfilter');
	$.address.title('Artica: DNS Filter Parameters');
	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: DNS Filter Parameters",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function tabs(){
	$HideCorporateFeatures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideCorporateFeatures"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$array["{service_status}"]="$page?table=yes";
	$array["{rules}"]="fw.dns.filterd.rules.php";
	$array["{whitelists}"]="fw.dns.filterd.whitelists.php";
	$array["{blacklists}"]="fw.dns.filterd.blacklists.php";
	$array["{events}"]="fw.dns.filterd.events.php";
	echo $tpl->tabs_default($array);
}


function table(){
	
	$f=explode("\n",@file_get_contents("/etc/dnsfilterd/dnsfilterd.conf"));
	$CountOfPattern=0;
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#domainlist\s+\"#", $line)){$CountOfPattern++;continue;}
	}
	
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/dnsfilterd.restart.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/dnsfilterd.restart.log";
	$ARRAY["CMD"]="dnsfilterd.php?restart=yes";
	$ARRAY["TITLE"]="{restarting}";
	$ARRAY["AFTER"]="LoadAjax('table-dnsfilterd','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$dnsfilterd_restart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-dnsfilter-restart')";
	
	
	$mem=new lib_memcached();
	$UFDB_CONNECTS=intval($mem->getKey("UFDB_CONNECTS"));
	$DNSFILTER_HITS=intval($mem->getKey("DNSFILTER_HITS"));
	$UFDB_CONNECTS=$UFDB_CONNECTS+$DNSFILTER_HITS;
	
	$UFDB_CONNECTS=FormatNumber($UFDB_CONNECTS);
	$DNSFILTER_HITS=FormatNumber($DNSFILTER_HITS);
	
	
	$EnableDNSFilterdRest=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterdRest"));


	$dnsfiltersocks=new dnsfiltersocks();
	$UfdbListenPort=intval($dnsfiltersocks->GET_INFO("UfdbListenPort"));
	$UfdbListenInterface=trim($dnsfiltersocks->GET_INFO("UfdbListenInterface"));
	$DefaultIpRedirection=$dnsfiltersocks->GET_INFO("DefaultIpRedirection");
	$dns_neg_ttl=intval($dnsfiltersocks->GET_INFO("dns_neg_ttl"));
	if($dns_neg_ttl==0){$dns_neg_ttl=3600;}
	if($DefaultIpRedirection==null){$DefaultIpRedirection="127.0.0.1";}
	$UfdbgclientSockTimeOut=intval($dnsfiltersocks->GET_INFO("UfdbgclientSockTimeOut"));
	if($UfdbgclientSockTimeOut==0){$UfdbgclientSockTimeOut=2;}
	$SquidGuardClientEnableMemory=intval($dnsfiltersocks->GET_INFO("SquidGuardClientEnableMemory"));
	$SquidGuardClientMaxMemorySeconds=intval($dnsfiltersocks->GET_INFO("SquidGuardClientMaxMemorySeconds"));
	$DebugFilter=intval($dnsfiltersocks->GET_INFO("DebugFilter"));
	$Threads=intval($dnsfiltersocks->GET_INFO("UfdbGuardThreads"));
	if($Threads==0){$Threads=64;}
	if($Threads>140){$Threads=140;}
	if($SquidGuardClientMaxMemorySeconds==0){$SquidGuardClientMaxMemorySeconds=300;}
	
	if($UfdbListenPort==0){$UfdbListenPort=3979;}
	if($UfdbListenInterface==null){$UfdbListenInterface="lo";}
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$sock->getFrameWork("dnsfilterd.php?status=yes");
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/dnsfilterd.status");
	
	if($CountOfPattern>0){
		$CountOfPattern_text=$tpl->widget_h("green","fas fa-database",$CountOfPattern,"{loaded_databases}");
	}
	if($CountOfPattern==0){
		$CountOfPattern_text=$tpl->widget_h("green","fas fa-engine-warning",0,"{no_database_selected}");
	}
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align:top'>";
	$html[]="	<table style='width:100%'>";
	$html[]="		<tr>
						<td>
							<div class=\"ibox\" style='border-top:0px'>
    							<div class=\"ibox-content\" style='border-top:0px'>
								$CountOfPattern_text
								". $tpl->SERVICE_STATUS($ini, "APP_DNSFILTERD",$dnsfilterd_restart).
								$tpl->SERVICE_STATUS($ini, "APP_DNSFILTERD_TAIL",null).
								
								
								$tpl->widget_style1("navy-bg","fas fa-filter","{requests}",$UFDB_CONNECTS).
								$tpl->widget_style1("lazur-bg","fas fa-filter","{cached_requests}",$DNSFILTER_HITS).
								
								"</div>
							</div>
    					</td>
    			  </tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="<td style='vertical-align:top'>";


	$sock=new sockets();
	if($sock->CORP_LICENSE()){
	    if($EnableDNSFilterdRest==1){
            $DNSFilterRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFilterRESTFulAPIKey"));
            $form[]=$tpl->field_text("DNSFilterRESTFulAPIKey", "REST {API_KEY}", $DNSFilterRESTFulAPIKey);

        }
    }

	$form[]=$tpl->field_interfaces("UfdbListenInterface","{interface}",$UfdbListenInterface);
	$form[]=$tpl->field_numeric("UfdbListenPort","{listen_port}",$UfdbListenPort);
	$form[]=$tpl->field_numeric("UfdbGuardThreads","Threads",$Threads);
	
	$form[]=$tpl->field_checkbox("DebugFilter","{debug_mode}",$DebugFilter);
	$form[]=$tpl->field_checkbox("SquidGuardClientEnableMemory","{cache_results}",$SquidGuardClientEnableMemory,false,"");
	$form[]=$tpl->field_numeric("SquidGuardClientMaxMemorySeconds","{cache_time} ({seconds})",$SquidGuardClientMaxMemorySeconds);
	
	
	$form[]=$tpl->field_numeric("UfdbgclientSockTimeOut","{socket_timeout} {seconds}",$UfdbgclientSockTimeOut);
	$form[]=$tpl->field_ipaddr("DefaultIpRedirection","{ipaddr_on_blocked_domains}",$DefaultIpRedirection);
	$form[]=$tpl->field_numeric("dns_neg_ttl", "{negative_dns_ttl} ({seconds})", $dns_neg_ttl);
	
	
	
	$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$dnsfilterd_restart,"AsDnsAdministrator");
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	if(isset($_POST["DNSFilterRESTFulAPIKey"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSFilterRESTFulAPIKey",$_POST["DNSFilterRESTFulAPIKey"]);
        unset($_POST["DNSFilterRESTFulAPIKey"]);
    }
	
	$dnsfiltersocks=new dnsfiltersocks();
	foreach ($_POST as $key=>$value){
		if(!$dnsfiltersocks->SET_INFO($key, $value)){return;}
		
	}
	
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
