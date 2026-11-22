<?php
// POST SP10 20261023-12
// Backends of a Load-Balancer service (every type but Reverse Proxy, which has
// fw.haproxy.webservers.php). Reads and writes go through the daemon
// (/haproxy/service, /haproxy/backends, /haproxy/backend/{save,delete}): the
// page used to drive haproxy_multi/haproxy_backends from class.haproxy.inc,
// which appliances no longer ship, and to run its own SQL.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["backend-js"])){backend_js();exit;}
if(isset($_GET["backend-popup"])){backend_popup();exit;}
if(isset($_POST["backendname"])){backends_save();exit;}
if(isset($_GET["backend-enable-js"])){backends_enable();exit;}
if(isset($_GET["backend-delete-js"])){backend_delete_js();exit;}
if(isset($_POST["backend-delete"])){backend_delete();exit;}
if(isset($_GET["stats-js"])){stats_js();exit;}
if(isset($_GET["stats-render"])){stats_render();exit;}
page();

// ---------------------------------------------------------------- helpers

function hbk_get($route){
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($route));
	if(!is_object($json)){$e=$GLOBALS["CLASS_SOCKETS"]->mysql_error;return array(null,$e!=""?$e:"{error}");}
	if(!$json->Status){return array(null,(string)$json->Error);}
	return array($json,"");
}

// No write rebuilds the Load-Balancer, enabling and deleting included: only the
// {apply_configuration} button of the services page applies the configuration.
function hbk_post($route,$data,$reload):string{
	$data["reload"]=(bool)$reload;
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($route,$data));
	if(!is_object($json)){$e=$GLOBALS["CLASS_SOCKETS"]->mysql_error;return $e!=""?$e:"{error}";}
	if(!$json->Status){return (string)$json->Error;}
	return "";
}

function hbk_config($b64):array{
	$a=@unserialize(base64_decode((string)$b64),array("allowed_classes"=>false));
	return is_array($a)?$a:array();
}

function hbk_service($name){
	list($json,$err)=hbk_get("/haproxy/services");
	if($json===null || !is_array($json->services)){return null;}
	$found=false;
	foreach($json->services as $s){if((string)$s->servicename===(string)$name){$found=true;break;}}
	if(!$found){return null;}
	list($json,$err)=hbk_get("/haproxy/service?servicename=".urlencode((string)$name));
	return $json===null?null:$json->service;
}

function hbk_backends($servicename):array{
	list($json,$err)=hbk_get("/haproxy/backends?servicename=".urlencode((string)$servicename));
	if($json===null || !is_array($json->backends)){return array();}
	return $json->backends;
}

function hbk_backend($servicename,$backendname){
	foreach(hbk_backends($servicename) as $b){
		if((string)$b->backendname===(string)$backendname){return $b;}
	}
	return null;
}

// Every column of the row: /haproxy/backend/save writes them all.
function hbk_payload($servicename,$b):array{
	return array("servicename"=>(string)$servicename,"backendname"=>(string)$b->backendname,
		"sitename"=>(string)$b->sitename,"listen_ip"=>(string)$b->listen_ip,"listen_port"=>(string)$b->listen_port,
		"bweight"=>(string)intval($b->bweight),"enabled"=>$b->enabled?1:0,"localInterface"=>(string)$b->localInterface,
		"MainConfig"=>(string)$b->MainConfig);
}

function hbk_reload_js($servicename):string{
	$page=CurrentPageName();
	$enc=urlencode((string)$servicename);
	return "LoadAjaxSilent('backend-list','$page?table=$enc');".
		"if(document.getElementById('table-haproxy-services')){LoadAjaxSilent('table-haproxy-services','fw.haproxy.services.php?table=yes');}".
		"if(document.getElementById('table-haproxy-bckstatus')){LoadAjaxSilent('table-haproxy-bckstatus','fw.haproxy.monitor.php?table=yes');}";
}

// ---------------------------------------------------------------- page

function page(){
	$page=CurrentPageName();
	$enc=urlencode((string)($_GET["servicename"] ?? ""));
	echo "<div id='backend-list'></div><script>LoadAjax('backend-list','$page?table=$enc')</script>";
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$servicename=(string)$_GET["table"];
	$enc=urlencode($servicename);
	$backends=hbk_backends($servicename);
	usort($backends,function($a,$b){return intval($a->bweight)-intval($b->bweight);});
	$PERF=hbk_perf($servicename);
	// The scheme of the address column follows the service type, not only the
	// SSL option: an HTTP load-balancer backend would otherwise read "tcp://".
	$svc=hbk_service($servicename);
	$type=$svc!==null?intval($svc->servicetype):3;

	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin:10px 0'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?backend-js=&servicename=$enc');\"><i class='fa fa-plus'></i> {new_backend} </label>";
	$html[]="</div>";
	$html[]="<div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead><tr><th>{backends}</th><th>{address}</th><th class='center'>{statistics}</th>";
	$html[]="<th class='center'>{active2}</th><th class='center'>{delete}</th></tr></thead><tbody>";
	foreach($backends as $b){
		$name=(string)$b->backendname;
		$benc=urlencode($name);
		$on=(bool)$b->enabled;
		$m=hbk_config($b->MainConfig);
		$ip=(string)$b->listen_ip;
		$logip=$ip;
		$warn="";
		if($ip==""){
			// No address: haproxy resolves the backend name itself.
			$ip=$name;
			$resolved=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($ip);
			$logip=(string)$resolved;
			if(!filter_var($resolved,FILTER_VALIDATE_IP)){$warn=" <span class='awc-badge' style='background:#ba1a1a'><i class='fas fa-triangle-exclamation'></i> {CURLE_COULDNT_RESOLVE_HOST}</span>";}
		}
		$tags=array();
		if(intval($m["UseSSL"] ?? 0)==1){$tags[]="<span class='awc-tag'>SSL</span>";}
		if(intval($m["proxy_protocol"] ?? 0)==1){$tags[]="<span class='awc-tag'>PROXY</span>";}
		if(intval($m["FailOverOnly"] ?? 0)==1){$tags[]="<span class='awc-tag'>{failover_only}</span>";}
		if(intval($m["DrGroup"] ?? 0)==1){$tags[]="<span class='awc-tag'>{disaster_recover_group}</span>";}
		if(intval($m["asSquidArtica"] ?? 0)==1){$tags[]="<span class='awc-tag' title=\"{artica_proxy}\">ARTICA</span>";}
		if(intval($m["postfix-send-proxy"] ?? 0)==1){$tags[]="<span class='awc-tag' title=\"{postfix_send_proxy}\">SMTP PROXY</span>";}
		$edit="Loadjs('$page?backend-js=$benc&backendname=$benc&servicename=$enc')";
		$enable=$tpl->icon_check($on?1:0,"Loadjs('$page?backend-enable-js=$benc&servicename=$enc&state=".($on?0:1)."')",null,"AsSquidAdministrator");
		$del=$tpl->icon_delete("Loadjs('$page?backend-delete-js=$benc&servicename=$enc')","AsSquidAdministrator");
		$html[]="<tr>";
		$html[]="<td style='width:99%'><div class='awc-ident'><span class='awc-ico".($on?"":" awc-ico-off")."'><i class='fas fa-server'></i></span>".
			"<span><span class='awc-name".($on?"":" awc-name-off")."'><a href=\"javascript:blur();\" OnClick=\"$edit\">".htmlspecialchars($name)."</a></span>".
			"<div class='awc-meta'>".@implode(" ",$tags)."$warn</div></span></div></td>";
		$url=hbk_url($type,intval($m["UseSSL"] ?? 0)==1,$ip,$b->listen_port);
		$html[]="<td><a class='awc-mono' href=\"javascript:blur();\" OnClick=\"$edit\"><strong>".htmlspecialchars($url)."</strong></a>".
			hbk_options_line($b->bweight,$m,$b->localInterface)."</td>";
		$section=hbk_section($servicename,$m);
		$html[]="<td class='center'>".hbk_stats_cell($PERF,"$section|$logip","Loadjs('$page?stats-js=$benc&servicename=$enc&section=".urlencode($section)."')")."</td>";
		$html[]="<td class='center'>$enable</td><td class='center'>$del</td>";
		$html[]="</tr>";
	}
	if(count($backends)==0){
		$html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
	}
	$html[]="</tbody></table></div></div>";
	// peity is loaded on demand.
	$peity="(function(){
	function bootPeity(cb){
		if(typeof jQuery!=='undefined' && jQuery.fn && jQuery.fn.peity){cb();return;}
		if(window.__peity_loading){setTimeout(function(){bootPeity(cb);},200);return;}
		window.__peity_loading=true;
		var sc=document.createElement('script');sc.src='js/jquery.peity.min.js';
		sc.onload=function(){cb();};document.head.appendChild(sc);
	}
	bootPeity(function(){
		jQuery('.hapspark').peity('line',{width:150,height:30,fill:'rgba(46,204,113,0.15)',stroke:'#2ecc71',strokeWidth:2});
	});
})();";
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$peity</script>";
}

// The haproxy section a backend lives in: a DrGroup backend goes ONLY to
// "backend dr_<service>", every other one to "backend default_<service>".
function hbk_section($servicename,$MainConfig):string{
	$svc=preg_replace('/[^A-Za-z0-9._-]/','',(string)$servicename);
	return (intval($MainConfig["DrGroup"]??0)==1?"dr_":"default_").$svc;
}

// Last 6 hours of every backend of the service from haproxy_log, in two queries for the
// whole table: 3-minute series on a shared time axis (two curves of different lengths
// do not compare), plus requests, distinct clients, size and p95. Both sections are
// read and keyed "<section>|<ip>", so a DR backend is not looked up in default_.
function hbk_perf($servicename):array{
	if(!class_exists("postgres_sql")){include_once(dirname(__FILE__)."/ressources/class.postgres.inc");}
	$OUT=array("spark"=>array(),"perf"=>array(),"p95"=>array(),"slots"=>2);
	$q=new postgres_sql();
	$svc=preg_replace('/[^A-Za-z0-9._-]/','',(string)$servicename);
	$IN="servicename IN ('default_$svc','dr_$svc')";
	list($LATSEL,$LATALIAS)=haproxy_latency_select($q);

	$slots=array();$raw=array();
	$r1=$q->QUERY_SQL("SELECT servicename||'|'||host(backend) AS bk, floor(extract(epoch from zdate)/180)*180 AS b, SUM(rqs) AS n
		FROM haproxy_log WHERE $IN AND ".haproxy_log_since(6)."
		GROUP BY 1,2 ORDER BY 2");
	if($r1){
		while($l=@pg_fetch_assoc($r1)){
			$slots[intval($l["b"])]=true;
			$raw[$l["bk"]][intval($l["b"])]=intval($l["n"]);
		}
	}
	$slots=array_keys($slots);
	sort($slots);
	// A polyline needs two points: pad the axis backwards with empty buckets.
	while(count($slots)<2){array_unshift($slots,($slots[0]??0)-180);}
	$OUT["slots"]=count($slots);
	foreach($raw as $bk=>$byslot){
		$serie=array();
		foreach($slots as $sl){$serie[]=intval($byslot[$sl]??0);}
		$OUT["spark"][$bk]=$serie;
	}

	$r2=$q->QUERY_SQL("SELECT servicename||'|'||host(backend) AS bk, SUM(rqs) AS rqs, COUNT(DISTINCT ipsrc) AS clients, SUM(size) AS sz$LATSEL
		FROM haproxy_log WHERE $IN AND ".haproxy_log_since(6)."
		GROUP BY 1");
	if($r2){
		while($l=@pg_fetch_assoc($r2)){
			$OUT["perf"][$l["bk"]]=array("rqs"=>intval($l["rqs"]),"clients"=>intval($l["clients"]),"sz"=>floatval($l["sz"]));
			if(count($LATALIAS)==0){continue;}
			$B=array();$top=0;
			foreach($LATALIAS as $bound=>$a){
				$B[$bound]=intval($l[$a]);
				$top=max($top,$B[$bound]);
			}
			$cap=false;
			$v=haproxy_histogram_percentile($B,$top+intval($l["linf"]),0.95,$cap);
			if($v!==null){$OUT["p95"][$l["bk"]]=($cap?"&ge; ":"").$v;}
		}
	}
	return $OUT;
}

// 1234 -> 1.2k, 1234567 -> 1.2M.
function hbk_short_num($n):string{
	$n=intval($n);
	if($n<1000){return (string)$n;}
	if($n<1000000){return number_format($n/1000,1)."k";}
	return number_format($n/1000000,1)."M";
}

// Counter and clients on top, sparkline in the middle, p95 and volume below; the whole
// cell opens the statistics window. No traffic draws a flat line rather than a dash.
function hbk_stats_cell($PERF,$key,$openjs):string{
	$serie=$PERF["spark"][$key]??array();
	if(count($serie)<2){$serie=array_fill(0,max(2,$PERF["slots"]),0);}
	$P=$PERF["perf"][$key]??null;
	$reqtxt=$P?"REQ ".hbk_short_num($P["rqs"]):"REQ &mdash;";
	$cli="";
	if($P && $P["clients"]>0){
		$cli="<span style='background:#e3f0f8;color:#006397;border-radius:999px;padding:1px 7px;font-size:10px;font-weight:700'>".
			"<i class='fas fa-user'></i>&nbsp;".$P["clients"]."</span>";
	}
	$foot=array();
	if(isset($PERF["p95"][$key])){$foot[]="p95 {$PERF["p95"][$key]} ms";}
	if($P && $P["sz"]>0){$foot[]=FormatBytes($P["sz"]/1024);}
	$foottxt=count($foot)>0?"<div style='font-size:11px;color:#6c7b6d;margin-top:2px'>".implode(" &bull; ",$foot)."</div>":"";
	return "<div style='cursor:pointer;min-width:150px;text-align:left' title='{statistics}' OnClick=\"$openjs\">".
		"<div style='display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;letter-spacing:.05em;color:#6c7b6d;text-transform:uppercase'>".
		"<span>$reqtxt</span><span style='margin-left:auto'>$cli</span></div>".
		"<span class='hapspark'>".implode(",",$serie)."</span>$foottxt</div>";
}

// ---------------------------------------------------------------- edit

function backend_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$servicename=(string)($_GET["servicename"] ?? "");
	$backend=(string)($_GET["backend-js"] ?? "");
	$title=$backend==""?"$servicename &raquo; ".$tpl->javascript_parse_text("{new_backend}"):"$servicename &raquo; $backend";
	$tpl->js_dialog2($title,"$page?backend-popup=".urlencode($backend)."&servicename=".urlencode($servicename));
}

function backend_popup(){
	$tpl=new template_admin();
	$servicename=(string)$_GET["servicename"];
	$backendname=(string)$_GET["backend-popup"];
	$svc=hbk_service($servicename);
	if($svc===null){echo $tpl->_ENGINE_parse_body($tpl->div_error("{servicename}: ".htmlspecialchars($servicename)." {no_data}"));return;}
	$type=intval($svc->servicetype);
	$isHTTP=($type==0 || $type==1);
	$b=$backendname!=""?hbk_backend($servicename,$backendname):null;
	$m=$b!==null?hbk_config($b->MainConfig):array();
	$defaults=array("inter"=>60000,"fall"=>3,"rise"=>2,"maxconn"=>10000,"asSquidArtica"=>0,"UseSSL"=>0,"proxy_protocol"=>0,"slowstart"=>0);
	foreach($defaults as $k=>$v){if(!isset($m[$k]) || !is_numeric($m[$k])){$m[$k]=$v;}}
	$port=$b!==null?intval($b->listen_port):0;
	if($port<2){$port=$type==4?25:8080;}

	$ip=new networking();
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	$interfaces=array(""=>"{default}")+$interfaces;
	$curIface=$b!==null?(string)$b->localInterface:"";
	if($curIface!="" && !isset($interfaces[$curIface])){$interfaces[$curIface]=$curIface;}

	$jsafter=hbk_reload_js($servicename);
	$typetext=hbk_type_label($type);
	$form[]=$tpl->field_hidden("servicename",$servicename);
	if($b!==null){
		$form[]=$tpl->field_hidden("backendname",$backendname);
		$form[]=$tpl->field_info("backendname_ro","{backendname}",$backendname); // field_info filtre la valeur (pentest 2026-11-04)
		$title=htmlspecialchars("$servicename/$backendname")." ($typetext)";
	}else{
		$form[]=$tpl->field_text("backendname","{backendname}","",true);
		$jsafter.="dialogInstance2.close();";
		$title=htmlspecialchars($servicename)."/{new_backend} ($typetext)";
	}
	$form[]=$tpl->field_section("{lbsvc_sec_listen}");
	$form[]=$tpl->field_array_hash($interfaces,"localInterface","{outgoing_address}",$curIface,false,"{haproxy_local_interface_help}");
	$form[]=$tpl->field_ipaddr("listen_ip","{destination_address}",$b!==null?(string)$b->listen_ip:"");
	$form[]=$tpl->field_numeric("listen_port","{destination_port}",$port);
	$form[]=$tpl->field_checkbox("proxy_protocol","{proxy_protocol}",intval($m["proxy_protocol"]),false,"{proxy_protocol_explain}");
	$form[]=$tpl->field_checkbox("FailOverOnly","{failover_only}",intval($m["FailOverOnly"] ?? 0));
	$form[]=$tpl->field_checkbox("DrGroup","{disaster_recover_group}",intval($m["DrGroup"] ?? 0));
	// Encryption to the server does not depend on the mode: haproxy takes
	// "server <address> ssl verify none" in TCP as in HTTP, and the configuration
	// writes it whatever the service type. Kept in the HTTP block, a TCP or SMTP
	// backend stored with SSL could neither show it nor switch it off.
	$form[]=$tpl->field_section("{ssl_protocol}");
	$form[]=$tpl->field_checkbox("UseSSL","{remote_server_use_ssl}",intval($m["UseSSL"]));
	if($isHTTP){
		if(intval($svc->loadbalancetype)==2){
			$form[]=$tpl->field_section("{HTTP_PROXY_MODE}","{WARN_HTTP_PROXY_MODE2}");
		}else{
			$form[]=$tpl->field_section("{HTTP_PROXY_MODE}");
		}
		// asSquidArtica forces the PROXY protocol and an httpchk on a Squid URL:
		// HTTP only.
		$form[]=$tpl->field_checkbox("asSquidArtica","{artica_proxy}",intval($m["asSquidArtica"]));
	}
	if($type==4){
		$form[]=$tpl->field_section("{SMTP_MODE}");
		$form[]=$tpl->field_checkbox("postfix-send-proxy","{postfix_send_proxy}",intval($m["postfix-send-proxy"] ?? 0));
	}
	$form[]=$tpl->field_section("{lbsvc_sec_timeouts}");
	$form[]=$tpl->field_numeric("bweight","{weight}",$b!==null?intval($b->bweight):1);
	$form[]=$tpl->field_numeric("maxconn","{max_connections}",intval($m["maxconn"]));
	$form[]=$tpl->field_numeric("inter","{check_interval} ({milliseconds})",intval($m["inter"]));
	$form[]=$tpl->field_numeric("fall","{failed_number} ({attempts})",intval($m["fall"]));
	$form[]=$tpl->field_numeric("rise","{success_number} ({attempts})",intval($m["rise"]));
	$form[]=$tpl->field_numeric("slowstart","{lbbk_slowstart}",intval($m["slowstart"]));
	echo $tpl->_ENGINE_parse_body($tpl->form_outside($title,@implode("\n",$form),"",$b===null?"{add}":"{apply}",$jsafter,"AsSquidAdministrator"));
}

// Service type label, as the services page names it (HaProxy.ServiceTypeNames).
function hbk_type_label($type):string{
	$T=array(0=>"{lbsvc_type0}",1=>"{lbsvc_type1}",2=>"{lbsvc_type2}",3=>"{lbsvc_type3}",4=>"{lbsvc_type4}");
	return $T[intval($type)] ?? "#".intval($type);
}

// The backend as the service reaches it: the scheme follows the service type, and
// the port is left out when it is the scheme's own.
function hbk_url($type,$ssl,$host,$port):string{
	$type=intval($type);
	$proto="tcp";
	if($type==0 || $type==1){$proto=$ssl?"https":"http";}
	if($type==4){$proto=$ssl?"smtps":"smtp";}
	$port=intval($port);
	$default=array("http"=>80,"https"=>443,"smtp"=>25);
	$p=(isset($default[$proto]) && $default[$proto]==$port)?"":":$port";
	return "$proto://$host$p/";
}

// The "server" directive keywords of haproxy.cfg, left untranslated, one label
// per "name:value". The labels wrap between each other, never inside one.
function hbk_options_line($weight,$m,$localInterface):string{
	$w=intval($weight)>0?intval($weight):1;
	$opts=array();
	$opts[]="Weight:$w";
	$opts[]="inter:".(intval($m["inter"] ?? 0)>0?intval($m["inter"]):60000)."ms";
	$opts[]="fall:".(intval($m["fall"] ?? 0)>0?intval($m["fall"]):3);
	$opts[]="rise:".(intval($m["rise"] ?? 0)>0?intval($m["rise"]):2);
	$opts[]="maxconn:".(intval($m["maxconn"] ?? 0)>0?intval($m["maxconn"]):10000);
	if(intval($m["slowstart"] ?? 0)>0){$opts[]="slowstart:".intval($m["slowstart"]);}
	$localInterface=trim((string)$localInterface);
	if(strlen($localInterface)>1){$opts[]="source:".htmlspecialchars($localInterface);}
	$h="";
	foreach($opts as $o){$h.="<span class='awc-tag' style='white-space:nowrap'>$o</span>";}
	return "<div style='display:flex;flex-wrap:wrap;gap:4px;margin-top:4px'>$h</div>";
}

function backends_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$servicename=(string)$_POST["servicename"];
	$name=trim((string)$_POST["backendname"]);
	if($name==""){echo $tpl->post_error("{backendname}: {no_data}");return;}
	if(hbk_service($servicename)===null){echo $tpl->post_error("{servicename}: ".htmlspecialchars($servicename)." {no_data}");return;}
	$b=hbk_backend($servicename,$name);
	if($b===null){
		$data=array("servicename"=>$servicename,"backendname"=>$name,"sitename"=>"","listen_ip"=>"","listen_port"=>"",
			"bweight"=>"1","enabled"=>1,"localInterface"=>"","MainConfig"=>"");
		$m=array();
	}else{
		$data=hbk_payload($servicename,$b);
		$m=hbk_config($b->MainConfig);
	}
	$data["listen_ip"]=(string)$_POST["listen_ip"];
	$data["listen_port"]=(string)intval($_POST["listen_port"]);
	$data["bweight"]=(string)intval($_POST["bweight"]);
	$data["localInterface"]=(string)$_POST["localInterface"];
	foreach(array("inter","fall","rise","slowstart","maxconn","FailOverOnly","DrGroup","asSquidArtica","UseSSL","proxy_protocol","postfix-send-proxy") as $k){
		if(isset($_POST[$k])){$m[$k]=intval($_POST[$k]);}
	}
	$data["MainConfig"]=base64_encode(serialize($m));
	$e=hbk_post("/haproxy/backend/save",$data,false);
	if($e!=""){echo $tpl->post_error($e);}
}

// ---------------------------------------------------------------- row actions

function backends_enable(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$servicename=(string)$_GET["servicename"];
	$b=hbk_backend($servicename,(string)$_GET["backend-enable-js"]);
	if($b===null){$tpl->js_error("{backendname}: {no_data}");return;}
	$data=hbk_payload($servicename,$b);
	$data["enabled"]=intval($_GET["state"] ?? 0)==1?1:0;
	$e=hbk_post("/haproxy/backend/save",$data,false);
	if($e!=""){$tpl->js_error($e);return;}
	echo hbk_reload_js($servicename);
}

function backend_delete_js(){
	$tpl=new template_admin();
	$servicename=(string)$_GET["servicename"];
	$backend=(string)$_GET["backend-delete-js"];
	$tpl->js_confirm_delete(htmlspecialchars("$servicename/$backend"),"backend-delete","$servicename|$backend",hbk_reload_js($servicename));
}

function backend_delete(){
	$tpl=new template_admin();
	$f=explode("|",(string)$_POST["backend-delete"],2);
	if(count($f)<2){echo $tpl->post_error("{backendname}: {no_data}");return;}
	$e=hbk_post("/haproxy/backend/delete",array("servicename"=>$f[0],"backendname"=>$f[1]),false);
	if($e!=""){echo $tpl->post_error($e);}
}

// ---------------------------------------------------------------- statistics
// Statistics of one backend, read from haproxy_log (PostgreSQL). The collector writes
// one bucket per minute and per (source, service, backend, code, termination):
//   haproxy_log.servicename = "default_<service>" or "dr_<service>" (haproxy section)
//   haproxy_log.backend     = server IP (INET)
//   haproxy_log.ts          = termination state (%ts), not a timestamp

function haproxy_log_since($hours):string{
	$hours=intval($hours);
	return "zdate > now() - interval '$hours hours'";
}

// array(validated hours, bucket width in seconds, axis date format).
function haproxy_chart_range($hours):array{
	$R=array(
		1   => array(60,    "H:i"),
		6   => array(180,   "H:i"),
		24  => array(900,   "H:i"),
		168 => array(3600,  "d/m H\h"),
		720 => array(21600, "d/m"),
	);
	$hours=intval($hours);
	if(!isset($R[$hours])){$hours=6;}
	return array($hours,$R[$hours][0],$R[$hours][1]);
}

function haproxy_chart_ranges():array{
	return array(1=>"1 {hour}",6=>"6 {hours}",24=>"1 {day}",168=>"7 {days}",720=>"30 {days}");
}

// The axis carries the START of a bucket; the tooltip gives both bounds.
function haproxy_chart_interval($start,$width):string{
	$start=intval($start);
	$end=$start+intval($width);
	$f=(date("Y-m-d",$start)==date("Y-m-d",$end))?"H:i":"Y-m-d H:i";
	return date("Y-m-d H:i",$start)." → ".date($f,$end);
}

// Plain-text label of a HAProxy termination state (%ts), "" when unreadable.
function haproxy_termination_label($flags):string{
	$flags=(string)$flags;
	if(strlen($flags)<2){return "";}
	$pair=substr($flags,0,2);
	$COMMON=array(
		"--" => "Normal termination",
		"CC" => "Client aborted before the connection was established",
		"CD" => "Client aborted during data transfer",
		"cD" => "Client timeout, no data exchanged",
		"CH" => "Client aborted while waiting for the server response",
		"cH" => "Client timeout while sending the request body",
		"CQ" => "Client aborted while queued",
		"CR" => "Client aborted before sending a complete request",
		"cR" => "Client timeout before a complete request",
		"CT" => "Client aborted while tarpitted",
		"LR" => "Request handled locally by HAProxy",
		"SC" => "Server refused the TCP connection",
		"sC" => "Connection timeout to the server",
		"SD" => "Server connection died during data transfer",
		"sD" => "Server timeout, no data exchanged",
		"SH" => "Server aborted before sending response headers",
		"sH" => "Server timeout waiting for response headers",
		"PC" => "Proxy refused the connection, limit reached",
		"PD" => "Proxy blocked a malformed chunked message",
		"PH" => "Proxy blocked an invalid server response",
		"PR" => "Proxy blocked the request",
		"PT" => "Proxy tarpitted the request",
		"RC" => "A proxy resource was exhausted",
		"DC" => "Session killed, server detected down",
		"IC" => "Internal proxy error",
	);
	if(isset($COMMON[$pair])){return $COMMON[$pair];}
	$CAUSE=array("C"=>"Client aborted","S"=>"Server aborted or refused","P"=>"Blocked by the proxy",
		"L"=>"Handled locally by HAProxy","R"=>"Proxy resource exhausted","I"=>"Internal proxy error",
		"D"=>"Killed, server down","U"=>"Killed, backup released","K"=>"Killed by an administrator",
		"c"=>"Client timeout","s"=>"Server timeout","-"=>"Normal");
	$PHASE=array("R"=>"waiting for the request","Q"=>"queued","C"=>"connecting to the server",
		"H"=>"waiting for response headers","D"=>"data transfer","L"=>"sending the last data",
		"T"=>"tarpitted","-"=>"completed");
	$c1=substr($pair,0,1);$c2=substr($pair,1,1);
	return ($CAUSE[$c1]??"State $c1")." (".($PHASE[$c2]??"phase $c2").")";
}

// Percentile from a CUMULATIVE latency histogram (bound ms => requests served in that
// time or less, the shape of the lat_le* columns), interpolated linearly inside the
// bucket like Prometheus histogram_quantile. null on an empty sample; past the last
// bound it returns that bound and sets $capped (the value is then a lower bound).
function haproxy_histogram_percentile($buckets,$total,$p,&$capped=null){
	$capped=false;
	$total=intval($total);
	if($total<=0){return null;}
	// Empty last bucket: the columns exist but nobody fills them.
	$top=0;
	foreach($buckets as $count){$top=max($top,intval($count));}
	if($top<=0){return null;}
	$target=$p*$total;
	$prevBound=0;$prevCount=0;$lastBound=0;
	foreach($buckets as $bound=>$count){
		$bound=intval($bound);$count=intval($count);
		$lastBound=$bound;
		if($count>=$target){
			if($count<=$prevCount){return $bound;}
			$frac=($target-$prevCount)/($count-$prevCount);
			return round($prevBound+($bound-$prevBound)*$frac,1);
		}
		$prevBound=$bound;$prevCount=$count;
	}
	$capped=true;
	return $lastBound;
}

// Latency buckets actually present in haproxy_log (the collector owns the bounds):
// array(bound ms => column name), sorted by bound.
function haproxy_latency_buckets($q):array{
	static $CACHE=null;
	if($CACHE!==null){return $CACHE;}
	$CACHE=array();
	$res=$q->QUERY_SQL("SELECT column_name FROM information_schema.columns
		WHERE table_name='haproxy_log' AND column_name ~ '^lat_le[0-9]+$'");
	if($res){
		while($l=@pg_fetch_assoc($res)){$CACHE[intval(substr($l["column_name"],6))]=$l["column_name"];}
	}
	ksort($CACHE);
	return $CACHE;
}

// SUM() list for a SELECT and the aliases to read it back: array(sql, array(bound => alias)).
function haproxy_latency_select($q):array{
	$B=haproxy_latency_buckets($q);
	if(count($B)==0){return array("",array());}
	$sel=array();$alias=array();
	foreach($B as $bound=>$col){
		$alias[$bound]="b$bound";
		$sel[]="SUM($col) AS b$bound";
	}
	// lat_inf is optional: without it the overflow of the last bucket is unknown.
	$sel[]=$q->FIELD_EXISTS("haproxy_log","lat_inf")?"SUM(COALESCE(lat_inf,0)) AS linf":"0 AS linf";
	return array(", ".implode(", ",$sel),$alias);
}

function stats_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$servicename=(string)($_GET["servicename"]??"");
	$backendname=(string)$_GET["stats-js"];
	$section=urlencode((string)($_GET["section"]??""));
	$tpl->js_dialog2(htmlspecialchars("$servicename / $backendname"),
		"$page?stats-render=".urlencode($backendname)."&servicename=".urlencode($servicename)."&section=$section&hours=24",1100);
}

// The two values identifying the backend in haproxy_log. Nothing from the URL enters
// the SQL as-is: the section is filtered on a character list and the address must be
// a valid IP (the column is INET, a bad value would fail the whole query).
function stats_keys($servicename,$backendname):array{
	$b=hbk_backend($servicename,$backendname);
	$section=(string)($_GET["section"]??"");
	if(!preg_match('/^(default|dr)_[A-Za-z0-9._-]+$/',$section)){
		$section=hbk_section($servicename,$b===null?array():hbk_config($b->MainConfig));
	}
	$ip=$b===null?"":trim((string)$b->listen_ip);
	// A backend without address is resolved by haproxy, which logs the resolved IP.
	if($ip==""){$ip=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($backendname);}
	if(filter_var($ip,FILTER_VALIDATE_IP)===false){$ip=null;}
	return array("svc"=>$section,"ip"=>$ip);
}

function stats_render(){
	if(!class_exists("postgres_sql")){include_once(dirname(__FILE__)."/ressources/class.postgres.inc");}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$backendname=(string)$_GET["stats-render"];
	$servicename=(string)($_GET["servicename"]??"");
	$sectionenc=urlencode((string)($_GET["section"]??""));

	list($hours,$width,$fmt)=haproxy_chart_range($_GET["hours"]??24);
	// Numeric bucket key: each QUERY_SQL opens its own PostgreSQL session.
	$bucket="floor(extract(epoch from zdate)/$width)*$width";

	$K=stats_keys($servicename,$backendname);
	if($K["ip"]===null){
		echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data}: ".htmlspecialchars($backendname)));
		return;
	}
	// Pentest 2026-11-04 : le nom de section (configuration) etait concatene dans la requete sans echappement.
	$WHERE="servicename='".pg_escape_string2($K["svc"])."' AND backend='".pg_escape_string2($K["ip"])."'::inet AND ".haproxy_log_since($hours);

	$q=new postgres_sql();
	// postgres_sql::QUERY_SQL() returns a pg resource, not an array.
	$rows=function($sql) use ($q){
		$out=array();
		$res=$q->QUERY_SQL($sql);
		if(!$res){return $out;}
		while($ligne=@pg_fetch_assoc($res)){$out[]=$ligne;}
		return $out;
	};

	// Latency columns came later: an unmigrated base must not break the query.
	list($LATCOLS,$LATALIAS)=haproxy_latency_select($q);
	$HAS_LAT=(count($LATALIAS)>0);

	$serie=$rows("SELECT $bucket AS b, SUM(rqs) AS rqs, SUM(size) AS sz,
		SUM(CASE WHEN http_code>=400 AND http_code NOT IN (401,407) THEN rqs ELSE 0 END) AS err$LATCOLS
		FROM haproxy_log WHERE $WHERE GROUP BY 1 ORDER BY 1");

	$md=md5("$servicename$backendname");

	$btns=array();
	foreach(haproxy_chart_ranges() as $hh=>$lbl){
		$cls=($hh==$hours)?"btn-primary":"btn-white";
		$u="$page?stats-render=".urlencode($backendname)."&servicename=".urlencode($servicename)."&section=$sectionenc&hours=$hh";
		$btns[]="<label class=\"btn $cls\" OnClick=\"LoadAjax('haproxy-stats-$md','$u');\">$lbl</label>";
	}

	$h=array();
	$h[]="<div id='haproxy-stats-$md'>";
	$h[]="<div style='display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin:8px 4px'>";
	$h[]="<div><strong style='font-size:16px'>".htmlspecialchars($servicename)." / ".htmlspecialchars($backendname)."</strong>".
		"<div style='font-size:12px;color:#6c7b6d'>".htmlspecialchars($K["ip"])."</div></div>";
	$h[]="<div class=\"btn-group\" data-toggle=\"buttons\">".implode("",$btns)."</div>";
	$h[]="</div>";

	// The range selector comes BEFORE this test, so an empty range can still be left.
	if(count($serie)==0){
		$h[]="<div style='margin:8px 4px'>".$tpl->div_info("{no_data} &mdash; ".htmlspecialchars("$servicename/$backendname"))."</div>";
		$h[]="</div>";
		echo $tpl->_ENGINE_parse_body(implode("\n",$h));
		return;
	}

	$labels=array();$full=array();$keys=array();
	$vreq=array();$verr=array();$vsz=array();
	$p50=array();$p95=array();$p99=array();
	$totreq=0;$toterr=0;$totsz=0;
	$CUM=array();   // histogram cumulated over the whole range
	$latreq=0;      // requests actually measured, denominator of the cumulated percentile
	foreach($serie as $r){
		$t=intval($r["b"]);
		$labels[]=date($fmt,$t);
		$full[]=haproxy_chart_interval($t,$width);
		$keys[]=$t;
		$vreq[]=intval($r["rqs"]);
		$verr[]=intval($r["err"]);
		$vsz[]=round(intval($r["sz"])/1024,1);
		$totreq+=intval($r["rqs"]);
		$toterr+=intval($r["err"]);
		$totsz+=intval($r["sz"]);
		if($HAS_LAT){
			$b=array();$top=0;
			foreach($LATALIAS as $bound=>$a){
				$b[$bound]=intval($r[$a]);
				$top=max($top,$b[$bound]);
			}
			$n=$top+intval($r["linf"]);
			$latreq+=$n;
			$p50[]=haproxy_histogram_percentile($b,$n,0.50);
			$p95[]=haproxy_histogram_percentile($b,$n,0.95);
			$p99[]=haproxy_histogram_percentile($b,$n,0.99);
			// Buckets add up; percentiles do not.
			foreach($b as $k=>$v){$CUM[$k]=($CUM[$k]??0)+intval($v);}
		}
	}

	// Error breakdown per bucket for the tooltip; the share is over ALL requests of the bucket.
	$bycode=array();
	foreach($rows("SELECT $bucket AS b, http_code, SUM(rqs) AS n FROM haproxy_log
		WHERE $WHERE AND http_code>=400 AND http_code NOT IN (401,407)
		GROUP BY 1,2 ORDER BY 1, 3 DESC") as $r){
		$bycode[intval($r["b"])][]=array(intval($r["http_code"]),intval($r["n"]));
	}
	$tips=array();
	foreach($keys as $i=>$k){
		$line=array();
		foreach(($bycode[$k]??array()) as $cc){
			$pct=$vreq[$i]>0?round(($cc[1]/$vreq[$i])*100):0;
			$line[]=$cc[0]."  $pct%  (".$cc[1].")";
		}
		$tips[]=$line;
	}

	$codes=$rows("SELECT http_code, SUM(rqs) AS rqs FROM haproxy_log WHERE $WHERE GROUP BY 1 ORDER BY 2 DESC");
	$terms=$rows("SELECT ts, SUM(rqs) AS rqs FROM haproxy_log WHERE $WHERE GROUP BY 1 ORDER BY 2 DESC");
	$ips=$rows("SELECT ipsrc, SUM(rqs) AS rqs, SUM(size) AS sz FROM haproxy_log WHERE $WHERE GROUP BY 1 ORDER BY 2 DESC LIMIT 10");

	$err_pct=$totreq>0?round(($toterr/$totreq)*100,1):0;

	// The latency tile and chart only show when the histogram really holds measures.
	$capped=false;
	$gp95=$HAS_LAT?haproxy_histogram_percentile($CUM,$latreq,0.95,$capped):null;
	$HAS_LATDATA=($gp95!==null);
	$cols=$HAS_LATDATA?"1fr 1fr 1fr 1fr":"1fr 1fr 1fr";
	$h[]="<div style='display:grid;grid-template-columns:$cols;gap:12px;margin:8px 4px'>";
	$h[]=stats_tile("{requests}",$tpl->FormatNumber($totreq),"#006d37");
	$h[]=stats_tile("{errors}",$tpl->FormatNumber($toterr)." <small>($err_pct%)</small>",$toterr>0?"#ba1a1a":"#6c7b6d");
	$h[]=stats_tile("{size}",FormatBytes($totsz/1024),"#006397");
	if($HAS_LATDATA){
		// Not every request is timed: show the covered share.
		$cov=$totreq>0?round(($latreq/$totreq)*100):0;
		$sub=$cov<95?" <small>($cov% {requests})</small>":"";
		$h[]=stats_tile("{latency} p95",($capped?"&ge; ":"").$gp95." ms".$sub,"#865300");
	}
	$h[]="</div>";

	// Height is set on the box: maintainAspectRatio:false ignores the canvas height.
	$h[]=stats_chart_title("{requests}","{errors}");
	$h[]="<div style='margin:8px 4px;position:relative;height:200px'><canvas id='hst-req-$md'></canvas></div>";
	if($HAS_LATDATA){
		$h[]=stats_chart_title("{latency}","p50 &middot; p95 &middot; p99");
		$h[]="<div style='margin:8px 4px;position:relative;height:180px'><canvas id='hst-lat-$md'></canvas></div>";
	}
	$h[]=stats_chart_title("{size}","KB");
	$h[]="<div style='margin:8px 4px;position:relative;height:150px'><canvas id='hst-sz-$md'></canvas></div>";

	$h[]="<div style='display:grid;grid-template-columns:1fr 1fr 2fr;gap:12px;margin:8px 4px'>";
	$h[]=stats_table("{http_status_code}",array("{code}","{requests}"),array_map(function($r){
		return array(intval($r["http_code"]),number_format($r["rqs"]));
	},$codes));
	$h[]=stats_table("Termination",array("{status}","{requests}"),array_map(function($r){
		return array(htmlspecialchars($r["ts"])." <small>".htmlspecialchars(haproxy_termination_label($r["ts"]))."</small>",
			number_format($r["rqs"]));
	},$terms));
	$h[]=stats_table("{top_ips}",array("{ipaddr}","{requests}","{size}"),array_map(function($r){
		return array("<span style='font-family:monospace'>".htmlspecialchars($r["ipsrc"])."</span>",
			number_format($r["rqs"]),FormatBytes($r["sz"]/1024));
	},$ips));
	$h[]="</div>";
	$h[]="</div>";

	$lab=json_encode($labels);$jreq=json_encode($vreq);$jerr=json_encode($verr);$jsz=json_encode($vsz);
	$j50=json_encode($p50);$j95=json_encode($p95);$j99=json_encode($p99);
	$jt=json_encode($tips);$jf=json_encode($full);
	$req_t=$tpl->javascript_parse_text("{requests}");
	$err_t=$tpl->javascript_parse_text("{errors}");

	// Chart.js on demand, from the SAME file as the rest of the product: the first page
	// visited sets the version for the session, and the options below are v4 syntax.
	$h[]="<script>
(function(){
	function boot(cb){
		if(typeof Chart!=='undefined'){cb();return;}
		if(window.__hst_loading){setTimeout(function(){boot(cb);},200);return;}
		window.__hst_loading=true;
		var sc=document.createElement('script');sc.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';
		sc.onload=function(){cb();};document.head.appendChild(sc);
	}
	boot(function(){
		if(!window.__hst){window.__hst={};}
		['hst-req-$md','hst-lat-$md','hst-sz-$md'].forEach(function(k){
			if(window.__hst[k]){try{window.__hst[k].destroy();}catch(e){}}
		});
		var lab=$lab,tips=$jt,full=$jf;
		function tt(items){return full[items[0].dataIndex]||'';}
		// A single bucket with zero-radius points would draw nothing.
		var pr=lab.length<2?5:(lab.length<=40?3:0);
		var phr=Math.max(pr,4);
		var opt={responsive:true,maintainAspectRatio:false,animation:false,
			interaction:{mode:'index',intersect:false},
			plugins:{legend:{display:true,position:'bottom'}},
			scales:{y:{beginAtZero:true},x:{ticks:{maxTicksLimit:12,autoSkip:true}}}};
		// The JSON clone drops functions: callbacks are set after cloning.
		var optReq=JSON.parse(JSON.stringify(opt));
		optReq.plugins.tooltip={mode:'index',intersect:false,
			callbacks:{title:tt,afterBody:function(items){return tips[items[0].dataIndex]||[];}}};
		opt.plugins.tooltip={mode:'index',intersect:false,callbacks:{title:tt}};
		var c1=document.getElementById('hst-req-$md');
		if(c1){window.__hst['hst-req-$md']=new Chart(c1,{type:'line',data:{labels:lab,datasets:[
			{label:'$req_t',data:$jreq,borderColor:'#2ecc71',backgroundColor:'rgba(46,204,113,0.18)',fill:true,borderWidth:2,pointRadius:pr,pointHoverRadius:phr,tension:0.3},
			{label:'$err_t',data:$jerr,borderColor:'#ba1a1a',backgroundColor:'rgba(186,26,26,0.12)',fill:true,borderWidth:2,pointRadius:pr,pointHoverRadius:phr,tension:0.3}
		]},options:optReq});}
		var cl=document.getElementById('hst-lat-$md');
		if(cl){window.__hst['hst-lat-$md']=new Chart(cl,{type:'line',data:{labels:lab,datasets:[
			{label:'p50',data:$j50,borderColor:'#2ecc71',fill:false,borderWidth:2,pointRadius:pr,pointHoverRadius:phr,tension:0.3},
			{label:'p95',data:$j95,borderColor:'#f39c12',fill:false,borderWidth:2,pointRadius:pr,pointHoverRadius:phr,tension:0.3},
			{label:'p99',data:$j99,borderColor:'#ba1a1a',fill:false,borderWidth:2,pointRadius:pr,pointHoverRadius:phr,tension:0.3}
		]},options:opt});}
		var c2=document.getElementById('hst-sz-$md');
		if(c2){window.__hst['hst-sz-$md']=new Chart(c2,{type:'bar',data:{labels:lab,datasets:[
			{label:'KB',data:$jsz,backgroundColor:'rgba(52,152,219,0.55)'}
		]},options:opt});}
	});
})();
</script>";
	echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function stats_chart_title($label,$hint=""):string{
	if($hint!=""){
		$hint="<span style='font-weight:400;text-transform:none;letter-spacing:0;color:#8a968b;margin-left:8px'>$hint</span>";
	}
	return "<div style='margin:18px 4px 2px;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#3d4947'>$label$hint</div>";
}

function stats_tile($label,$value,$color):string{
	return "<div style='background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px'>".
		"<div style='font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#6c7b6d'>$label</div>".
		"<div style='font-size:24px;font-weight:700;color:$color;line-height:1.2'>$value</div></div>";
}

function stats_table($title,$heads,$rows):string{
	$h="<div style='background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>";
	$h.="<div style='padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#3d4947'>$title</div>";
	$h.="<table class='table table-striped' style='margin:0;font-size:13px'><thead><tr>";
	foreach($heads as $i=>$t){$h.="<th".($i>0?" style='text-align:right'":"").">$t</th>";}
	$h.="</tr></thead><tbody>";
	if(count($rows)==0){$h.="<tr><td colspan='".count($heads)."'>&mdash;</td></tr>";}
	foreach($rows as $r){
		$h.="<tr>";
		foreach($r as $i=>$c){$h.="<td".($i>0?" style='text-align:right'":"").">$c</td>";}
		$h.="</tr>";
	}
	$h.="</tbody></table></div>";
	return $h;
}
