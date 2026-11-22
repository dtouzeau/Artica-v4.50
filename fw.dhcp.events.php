<?php
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
// fw.dhcp.events.php — DHCP events (articarest GET /dhcpd/metrics/events, paged on the server)
// with the period summary (GET /dhcpd/metrics/summary).
//
// Stitch redesign 2026-11-19 (stitch_dhcp_events): value banner, filter bar, 4 summary tiles,
// "pool full" alert, events in plain words, device history link, guided empty states.
// Real data only. The period is a page-level control: it lives in the TINY (skill §2b) and
// reloads build-page, which owns the summary (the summary route takes no other filter). The
// search/type/source/refusals filters only reload the results host. One function per widget.
// Left out of the mock-up because invented or misleading: "Displaying real-time DHCP events"
// (the list is a query, refreshed on demand), "Address conflict suspected by device" under a
// DECLINE (the daemon gives no reason for a DECLINE), the "37" counter inside "Refusals only".
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["raw-js"])){raw_js();exit;}
if(isset($_GET["raw-popup"])){raw_popup();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header("{DHCP_EVENTS}","fas fa-list","{dhev_header_explain}",
		"$page?build-page=yes","dhcp-events","progress-dhcp-events",false,"table-dhcp-events");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{DHCP_EVENTS}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

// ── Vocabulary ────────────────────────────────────────────────────────────

// action => (icon, tone class, plain-name token)
function dhev_actions(){
	return array(
		"ACK"=>array("fa-circle-check","dhev-t-ok","{dhev_a_ack}"),
		"NAK"=>array("fa-ban","dhev-t-err","{dhev_a_nak}"),
		"OFFER"=>array("fa-hand-holding","dhev-t-info","{dhev_a_offer}"),
		"DISCOVER"=>array("fa-satellite-dish","dhev-t-slate","{dhev_a_discover}"),
		"REQUEST"=>array("fa-hand","dhev-t-slate","{dhev_a_request}"),
		"RELEASE"=>array("fa-rotate-left","dhev-t-warn","{dhev_a_release}"),
		"DECLINE"=>array("fa-circle-xmark","dhev-t-warn","{dhev_a_decline}"),
		"INFORM"=>array("fa-circle-info","dhev-t-slate","{dhev_a_inform}"),
		"BOOTREQUEST"=>array("fa-power-off","dhev-t-slate","{dhev_a_bootrequest}"),
		"BOOTREPLY"=>array("fa-power-off","dhev-t-slate","{dhev_a_bootreply}"),
	);
}

function dhev_reasons(){
	return array("no_free_leases"=>"{dhev_r_no_free_leases}","dhcp_nak"=>"{dhev_r_dhcp_nak}","unknown_client"=>"{dhev_r_unknown_client}",
		"not_authorized"=>"{dhev_r_not_authorized}","wrong_network"=>"{dhev_r_wrong_network}","lease_conflict"=>"{dhev_r_lease_conflict}","other"=>"{dhev_r_other}");
}

// ── Filters: one whitelist, read by build-page AND the results responder ──

function dhev_filters(){
	$a=strtoupper((string)($_GET["type"] ?? ""));
	$acts=dhev_actions();
	$src=(string)($_GET["source"] ?? "");
	$rs=(string)($_GET["reason"] ?? "");
	return array(
		"range"=>dhcpm_range(),
		"q"=>substr(trim((string)($_GET["search"] ?? "")),0,128),
		"type"=>isset($acts[$a])?$a:"",
		"source"=>in_array($src,array("dhcpd","dhcrelay"),true)?$src:"",
		"rej"=>intval($_GET["rej"] ?? 0)==1?1:0,
		"reason"=>array_key_exists($rs,dhev_reasons())?$rs:"",
		"p"=>max(1,intval($_GET["p"] ?? 1)),
	);
}

// Free text keeps the old syntax (a period, a MAC, an IP, a type or a status typed in the box
// still works); the explicit filters of the bar override it.
function search_params($q){
	$p=array("range"=>dhcpm_range(),"limit"=>250);
	$periods=array("15m","1h","6h","12h","24h","7d","30d","90d");
	foreach(preg_split('/\s+/',trim($q)) as $tok){
		if($tok===""){continue;}
		if(in_array($tok,$periods,true)){$p["range"]=$tok;continue;}
		if(in_array(strtolower($tok),array("dhcpd","dhcrelay"),true)){$p["source"]=strtolower($tok);continue;}
		if(preg_match('/^([0-9a-f]{1,2}:){5}[0-9a-f]{1,2}$/i',$tok)){$p["mac"]=$tok;continue;}
		if(filter_var($tok,FILTER_VALIDATE_IP)){$p["ip"]=$tok;continue;}
		if(in_array(strtoupper($tok),array("DISCOVER","OFFER","REQUEST","ACK","NAK","DECLINE","RELEASE","INFORM","BOOTREQUEST","BOOTREPLY"),true)){$p["action"]=strtoupper($tok);continue;}
		if(in_array($tok,array("rejected","assigned","inform_ack","forwarded"),true)){$p["status"]=$tok;continue;}
		$p["search"]=isset($p["search"])?$p["search"]." ".$tok:$tok;
	}
	return $p;
}

function dhev_query_params($f){
	$p=search_params($f["q"]);
	$p["range"]=$f["range"];
	if($f["type"]!==""){$p["action"]=$f["type"];}
	if($f["source"]!==""){$p["source"]=$f["source"];}
	if($f["rej"]==1){$p["status"]="rejected";}
	if($f["reason"]!==""){$p["reason"]=$f["reason"]; $p["status"]="rejected";}
	return $p;
}

function dhev_qs($f,$withPage=false){
	$qs="&range=".urlencode($f["range"])."&search=".urlencode($f["q"])."&type=".urlencode($f["type"]).
		"&source=".urlencode($f["source"])."&rej=".$f["rej"]."&reason=".urlencode($f["reason"]);
	return $withPage?$qs."&p=".$f["p"]:$qs;
}

// Text that comes FROM the network (device names, MACs, log lines): escaped, and its braces
// turned into entities so _ENGINE_parse_body() never reads "{error}" in a hostname as a token.
function dhev_txt($v){
	return str_replace(array("{","}"),array("&#123;","&#125;"),dhcpm_h($v));
}

function dhev_ago($ts){
	$tpl=new template_admin();
	$d=time()-$ts; if($d<0){$d=0;}
	if($d<60){return $tpl->_ENGINE_parse_body("{dhev_just_now}");}
	if($d<3600){return str_replace("%s",strval(intval($d/60)),$tpl->_ENGINE_parse_body("{dhev_ago_min}"));}
	if($d<86400){return str_replace("%s",strval(intval($d/3600)),$tpl->_ENGINE_parse_body("{dhev_ago_hours}"));}
	return str_replace("%s",strval(intval($d/86400)),$tpl->_ENGINE_parse_body("{dhev_ago_days}"));
}

function dhev_period_label($r){
	$tpl=new template_admin();
	$map=array("15m"=>"{dhev_p_15m}","1h"=>"{dhev_p_1h}","6h"=>"{dhev_p_6h}","12h"=>"{dhev_p_12h}","24h"=>"{dhev_p_24h}","7d"=>"{dhev_p_7d}","30d"=>"{dhev_p_30d}","90d"=>"{dhev_p_90d}");
	return $tpl->_ENGINE_parse_body(isset($map[$r])?$map[$r]:"{dhev_p_24h}");
}

// ── Page body: banner, filters, summary, results host, TINY ───────────────

function build_page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$f=dhev_filters();
	$sum=dhcpm_api("/dhcpd/metrics/summary",array("range"=>$f["range"]));
	$h=array();
	$h[]="<div class='dhev' id='dhev-root'>";
	$h[]=dhev_hero();
	if($sum===null || empty($sum->success)){
		$h[]=dhev_stopped();
		$h[]="</div>";
		echo dhev_style().siem_tiny_filters_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();".dhev_tiny($f)."</script>";
		return;
	}
	$s=isset($sum->data) && is_object($sum->data)?$sum->data:new stdClass();
	$h[]=dhev_filter_bar($f);
	$h[]=dhev_tiles($s,$f);
	$h[]=dhev_pool_alert($s,$f);
	$h[]="<div class='ak-card dhev-card'>";
	$h[]=dhev_top_strip($s);
	$h[]="<div id='dhev-results'></div></div>";
	$h[]="</div>";
	$script="<script>NoSpinner();\n".dhev_js($f)."\n".dhev_tiny($f)."</script>";
	echo dhev_style().siem_tiny_filters_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).$script;
}

function dhev_tiny($f){
	$tpl=new template_admin();
	$opts=array("15m"=>"15m","1h"=>"1h","6h"=>"6h","12h"=>"12h","24h"=>"24h","7d"=>"7d","30d"=>"30d","90d"=>"90d");
	$fields=array();
	$fields[]=array("TYPE"=>"segment","ID"=>"dhev-period","LABEL"=>"{dhev_period}","VALUE"=>$f["range"],"OPTIONS"=>$opts,"JS"=>"DhevPeriod");
	$topbuttons=array();
	$topbuttons[]=array("DhevRefresh()",ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{DHCP_EVENTS}";
	$TINY["ICO"]="fas fa-list";
	$TINY["EXPL"]="{dhev_header_explain}";
	$TINY["URL"]="dhcp-events";
	$TINY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
	return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}

function dhev_hero(){
	return "<section class='ak-card dhev-hero'><h2>{dhev_hero_title}</h2><p>{dhev_hero_text}</p>".
		"<div class='ak-chips dhev-chips'>".
		"<span class='ak-tag'><i class='fas fa-magnifying-glass'></i>{dhev_chip_search}</span>".
		"<span class='ak-tag'><i class='fas fa-ban'></i>{dhev_chip_refusals}</span>".
		"<span class='ak-tag'><i class='fas fa-clock'></i>{dhev_chip_history}</span>".
		"</div></section>";
}

function dhev_stopped(){
	$tpl=new template_admin();
	if(!empty($GLOBALS["DHCPM_API_UNREACHABLE"])){
		return "<div class='ak-banner ak-warn'><i class='fas fa-plug-circle-xmark'></i><div class='ak-banner-m'>{dhcp_metrics_api_unreachable}</div></div>";
	}
	return "<div class='ak-card dhev-stopped'><span class='ak-ico ak-warn'><i class='fas fa-circle-pause'></i></span>".
		"<div class='dhev-stopped-b'><b>{dhev_stopped_title}</b><p>{dhev_stopped_text}</p></div>".
		"<button type='button' class='ak-btn' OnClick=\"LoadAjax('MainContent','fw.dhcp.metrics.settings.php');\"><i class='fas fa-sliders'></i>{dhev_open_settings}</button></div>";
}

function dhev_filter_bar($f){
	$tpl=new template_admin();
	$ph=htmlspecialchars(html_entity_decode($tpl->javascript_parse_text("{dhev_search_placeholder}"),ENT_QUOTES,"UTF-8"),ENT_QUOTES,"UTF-8");
	$types="<option value=''>{dhev_all_events}</option>";
	foreach(dhev_actions() as $k=>$a){ $types.="<option value='$k'".($f["type"]===$k?" selected":"").">{$a[2]} ($k)</option>"; }
	$src="<option value=''>{dhev_all_sources}</option>".
		"<option value='dhcpd'".($f["source"]==="dhcpd"?" selected":"").">{dhev_src_server}</option>".
		"<option value='dhcrelay'".($f["source"]==="dhcrelay"?" selected":"").">{dhev_src_relay}</option>";
	$rejOn=($f["rej"]==1 || $f["reason"]!=="");
	return "<section class='ak-card dhev-filters' role='search'>".
		"<div class='dhev-f dhev-f-q'><label class='ak-search'><i class='fas fa-magnifying-glass'></i>".
		"<input type='search' id='dhev-q' value=\"".dhev_txt($f["q"])."\" placeholder=\"$ph\" aria-label=\"$ph\" OnKeyDown=\"if(event.key==='Enter'){DhevApply();}\"></label>".
		"<p class='dhev-help'>{dhev_search_help}</p></div>".
		"<div class='dhev-f'><select class='ak-sel' id='dhev-type' aria-label='{dhev_what_happened}' OnChange=\"DhevApply();\">$types</select></div>".
		"<div class='dhev-f'><select class='ak-sel' id='dhev-source' aria-label='{dhev_source}' OnChange=\"DhevApply();\">$src</select></div>".
		"<div class='dhev-f'><button type='button' id='dhev-rej' class='dhev-toggle".($rejOn?" on":"")."' aria-pressed='".($rejOn?"true":"false")."' OnClick=\"DhevToggleRej();\"><i class='fas fa-ban'></i>{dhev_refusals_only}</button></div>".
		"<input type='hidden' id='dhev-reason' value=\"".dhcpm_h($f["reason"])."\">".
		"</section>";
}

function dhev_tiles($s,$f){
	$tpl=new template_admin();
	$req=intval($s->requests ?? 0); $acks=intval($s->acks ?? 0); $rej=intval($s->rejects ?? 0); $nfl=intval($s->no_free_leases ?? 0);
	$macs=intval($s->unique_macs ?? 0); $ips=intval($s->unique_ips ?? 0);
	$h=array();
	$h[]="<div class='dhev-tiles'>";
	$h[]=dhev_tile("","fa-arrows-rotate","{dhev_t_requests}",number_format($req),str_replace("%s",number_format($macs),$tpl->_ENGINE_parse_body("{dhev_t_requests_sub}")),"");
	$h[]=dhev_tile("dhev-tile-ok","fa-circle-check","{dhev_t_assigned}",number_format($acks),str_replace("%s",number_format($ips),$tpl->_ENGINE_parse_body("{dhev_t_assigned_sub}")),"");
	if($rej>0){
		$h[]=dhev_tile("dhev-tile-err","fa-ban","{dhev_t_refused}",number_format($rej),"{dhev_t_refused_sub}","DhevOnlyRefusals('');");
	}else{
		$h[]=dhev_tile("dhev-tile-off","fa-ban","{dhev_t_refused}","{dhev_none}","{dhev_t_refused_none}","");
	}
	if($nfl>0){
		$h[]=dhev_tile("dhev-tile-warn","fa-triangle-exclamation","{dhev_t_pool}",str_replace("%s",number_format($nfl),$tpl->_ENGINE_parse_body("{dhev_t_pool_n}")),"{dhev_r_no_free_leases}","DhevOnlyRefusals('no_free_leases');");
	}else{
		$h[]=dhev_tile("dhev-tile-off","fa-triangle-exclamation","{dhev_t_pool}","{dhev_never}","{dhev_t_pool_none}","");
	}
	$h[]="</div>";
	return implode("\n",$h);
}

function dhev_tile($cls,$ico,$label,$value,$sub,$js){
	$tag=$js!==""?"button type='button' OnClick=\"$js\"":"div";
	$end=$js!==""?"button":"div";
	return "<$tag class='ak-card dhev-tile $cls'><div class='dhev-tile-l'>$label<i class='fas $ico'></i></div>".
		"<div class='dhev-tile-v'>$value</div><div class='dhev-tile-s'>$sub".($js!==""?" <i class='fas fa-arrow-right'></i>":"")."</div></$end>";
}

function dhev_pool_alert($s,$f){
	if(intval($s->no_free_leases ?? 0)<=0){return "";}
	return "<div class='ak-banner ak-warn dhev-banner'><i class='fas fa-triangle-exclamation'></i><div class='ak-banner-m'>{dhev_pool_alert} ".
		"<button type='button' class='ak-link' OnClick=\"DhevOnlyRefusals('no_free_leases');\">{dhev_pool_show}</button></div></div>";
}

function dhev_top_strip($s){
	$parts=array();
	if(isset($s->top_mac) && is_object($s->top_mac) && (string)$s->top_mac->key!==""){
		$parts[]="{dhev_top_device} <b class='ak-mono'>".dhev_txt($s->top_mac->key)."</b>";
	}
	if(isset($s->top_ip) && is_object($s->top_ip) && (string)$s->top_ip->key!==""){
		$parts[]="{dhev_top_ip} <b class='ak-mono'>".dhev_txt($s->top_ip->key)."</b>";
	}
	return "<div class='dhev-strip'><span>{dhev_newest_first}</span><span class='dhev-strip-r'>".implode("<span class='dhev-sep'></span>",$parts)."</span></div>";
}

function dhev_js($f){
	$page=CurrentPageName();
	$js=<<<'JS'
window.DhevState={range:__RANGE__,p:1};
window.DhevQS=function(){
 function v(id){ var e=document.getElementById(id); return e?e.value:''; }
 var rej=document.getElementById('dhev-rej');
 return '&range='+encodeURIComponent(DhevState.range)+'&search='+encodeURIComponent(v('dhev-q'))+'&type='+encodeURIComponent(v('dhev-type'))
  +'&source='+encodeURIComponent(v('dhev-source'))+'&rej='+(rej&&rej.getAttribute('aria-pressed')==='true'?1:0)+'&reason='+encodeURIComponent(v('dhev-reason'));
};
window.DhevLoad=function(p){ DhevState.p=p||1; if(!document.getElementById('dhev-results')){return;} LoadAjaxTiny('dhev-results','__PAGE__?search=yes'+DhevQS()+'&p='+DhevState.p); };
window.DhevApply=function(){ DhevLoad(1); };
window.DhevToggleRej=function(){
 var b=document.getElementById('dhev-rej'); var on=b.getAttribute('aria-pressed')!=='true';
 b.setAttribute('aria-pressed',on?'true':'false'); b.classList.toggle('on',on);
 if(!on){ var r=document.getElementById('dhev-reason'); if(r){r.value='';} }
 DhevApply();
};
window.DhevOnlyRefusals=function(reason){
 var b=document.getElementById('dhev-rej'); if(b){ b.setAttribute('aria-pressed','true'); b.classList.add('on'); }
 var r=document.getElementById('dhev-reason'); if(r){ r.value=reason||''; }
 DhevApply();
 var res=document.getElementById('dhev-results'); if(res && res.scrollIntoView){ try{ res.scrollIntoView({block:'start'}); }catch(e){} }
};
window.DhevClear=function(){
 var q=document.getElementById('dhev-q'); if(q){q.value='';}
 var t=document.getElementById('dhev-type'); if(t){t.value='';}
 var s=document.getElementById('dhev-source'); if(s){s.value='';}
 var r=document.getElementById('dhev-reason'); if(r){r.value='';}
 var b=document.getElementById('dhev-rej'); if(b){ b.setAttribute('aria-pressed','false'); b.classList.remove('on'); }
 DhevApply();
};
// The period changes the summary too: reload build-page, keeping the other filters.
window.DhevPeriod=function(r){ DhevState.range=r; LoadAjax('table-dhcp-events','__PAGE__?build-page=yes'+DhevQS()); };
window.DhevRefresh=function(){ LoadAjax('table-dhcp-events','__PAGE__?build-page=yes'+DhevQS()); };
DhevLoad(__P__);
JS;
	return str_replace(array("__RANGE__","__PAGE__","__P__"),array(json_encode($f["range"]),$page,intval($f["p"])),$js);
}

// ── Results responder (?search=): one page of events from the server ──────

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$f=dhev_filters();
	$psize=50;
	$params=dhev_query_params($f);
	$params["limit"]=$psize;
	$params["offset"]=($f["p"]-1)*$psize;
	$json=dhcpm_api("/dhcpd/metrics/events",$params);
	if($json===null || empty($json->success)){
		$err=($json!==null && isset($json->error))?(string)$json->error:"";
		if($err!=="" && stripos($err,"invalid")===0){
			echo $tpl->_ENGINE_parse_body("<div class='ak-banner ak-warn'><i class='fas fa-circle-info'></i><div class='ak-banner-m'>{dhev_bad_filter} <span class='ak-mono'>".dhev_txt($err)."</span></div></div>")."<script>NoSpinner();</script>";
			return;
		}
		echo $tpl->_ENGINE_parse_body(dhev_stopped())."<script>NoSpinner();</script>";
		return;
	}
	$total=intval($json->total ?? 0);
	$pages=max(1,intval(ceil($total/$psize)));
	$pageNo=min($f["p"],$pages);
	$data=(isset($json->data) && is_array($json->data))?$json->data:array();
	$filtered=($f["q"]!=="" || $f["type"]!=="" || $f["source"]!=="" || $f["rej"]==1 || $f["reason"]!=="");

	$h=array();
	if(count($data)==0){
		$h[]=$filtered?dhev_empty_filtered($f):dhev_empty($f);
		echo dhev_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();</script>";
		return;
	}
	$h[]="<div class='dhev-scroll'><table class='dhev-tbl'><thead><tr>";
	$h[]="<th>{dhev_when}</th><th>{dhev_what_happened}</th><th>{dhev_device}</th><th>{dhev_address}</th><th>{dhev_network}</th><th>{dhev_result}</th><th><span class='sr-only'>{devt_raw_message}</span></th>";
	$h[]="</tr></thead><tbody>";
	foreach($data as $e){ $h[]=dhev_row($e,$f); }
	$h[]="</tbody></table></div>";
	$first=($pageNo-1)*$psize+1;
	$last=($pageNo-1)*$psize+count($data);
	$showing=str_replace(array("%a","%b","%t"),array(number_format($first),number_format($last),number_format($total)),$tpl->_ENGINE_parse_body("{dhev_showing}"));
	$h[]="<div class='dhev-foot'><div><b>$showing</b>";
	if(!empty($json->truncated)){ $h[]="<p class='dhev-help'>{dhev_truncated}</p>"; }
	$h[]="</div><span class='ak-pg'>".str_replace(array("awc-pg-off","awc-pg-cur"),array("off","cur"),awc_pager_server($pageNo,$pages,"dhev-pg-a"))."</span></div>";
	$nav="<script>NoSpinner();(function(){var as=document.querySelectorAll('#dhev-results .dhev-pg-a');for(var i=0;i<as.length;i++){as[i].onclick=function(){ if(/\b(off|cur)\b/.test(this.className)){return false;} DhevLoad(parseInt(this.getAttribute('data-p'),10)); var r=document.getElementById('dhev-results'); if(r&&r.scrollIntoView){try{r.scrollIntoView({block:'start'});}catch(e){}} return false; };}})();</script>";
	echo dhev_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).$nav;
}

function dhev_row($e,$f){
	$page=CurrentPageName();
	$acts=dhev_actions();
	$reasons=dhev_reasons();
	$ts=strtotime((string)($e->timestamp ?? ""));
	$a=strtoupper((string)($e->action ?? ""));
	$A=isset($acts[$a])?$acts[$a]:array("fa-circle","dhev-t-slate",$a!==""?dhev_txt($a):"—");
	$status=trim((string)($e->status ?? ""));
	$rejected=($status==="rejected");
	$mac=trim((string)($e->mac_address ?? ""));
	$host=trim((string)($e->client_hostname ?? ""));
	$ip=trim((string)($e->ip_address ?? ""));
	$req=trim((string)($e->requested_ip ?? ""));
	$iface=trim((string)($e->interface ?? ""));
	$net=trim((string)($e->network ?? ""));
	$src=trim((string)($e->source ?? ""));
	$raw=urlencode(base64_encode((string)($e->raw_message ?? "")));

	$when=$ts>0?"<b>".date("H:i:s",$ts)."</b><span title='".date("Y-m-d H:i:s",$ts)."'>".dhev_txt(dhev_ago($ts))."</span>":"<b>—</b>";
	$what="<span class='dhev-what {$A[1]}'><i class='fas {$A[0]}'></i><span><span class='dhev-what-n'>{$A[2]}</span>".($a!==""?"<span class='dhev-tag'>".dhev_txt($a)."</span>":"")."</span></span>";

	$name=$host!==""?"<b>".dhev_txt($host)."</b>":"<em class='dhev-unnamed'>{dhev_unnamed}</em>";
	if($mac!==""){
		$js="Loadjs('fw.dhcp.metrics.macs.php?mac-js=yes&mac=".urlencode($mac)."&range=".urlencode($f["range"])."')";
		$dev="<a class='dhev-dev' href='javascript:blur();' title='{dhev_device_history}' OnClick=\"$js\">$name <i class='fas fa-up-right-from-square'></i></a><span class='ak-mono dhev-sub'>".dhev_txt($mac)."</span>";
	}else{
		$dev="<span class='dhev-dev'>$name</span>";
	}
	$addr=$ip!==""?"<b class='ak-mono'>".dhev_txt($ip)."</b>":"<span class='ak-muted'>—</span>";
	if($req!=="" && $req!==$ip){ $addr.="<span class='dhev-sub'>".str_replace("%s","<span class='ak-mono'>".dhev_txt($req)."</span>",(new template_admin())->_ENGINE_parse_body("{dhev_asked_for}"))."</span>"; }
	$netw=$iface!==""?"<span class='ak-mono'>".dhev_txt($iface)."</span>":"<span class='ak-muted'>—</span>";
	if($src==="dhcrelay"){ $netw.=" <span class='dhev-tag dhev-tag-info'>{dhev_relay}</span>"; }
	if($net!==""){ $netw.="<span class='ak-mono dhev-sub'>".dhev_txt($net)."</span>"; }

	if($rejected){
		$rr=trim((string)($e->reject_reason ?? ""));
		$res="<span class='ak-pill ak-err'><i class='fas fa-ban'></i>{dhev_refused}</span>";
		if($rr!==""){ $res.="<span class='dhev-reason'>".(isset($reasons[$rr])?$reasons[$rr]:dhev_txt($rr))."</span>"; }
	}elseif($status==="assigned"){ $res="<span class='ak-pill ak-ok'><i class='fas fa-check'></i>{dhev_assigned}</span>"; }
	elseif($status==="inform_ack"){ $res="<span class='ak-pill ak-off'><i class='fas fa-circle-info'></i>{dhev_settings_sent}</span>"; }
	elseif($status==="forwarded"){ $res="<span class='ak-pill ak-info'><i class='fas fa-share'></i>{dhev_forwarded}</span>"; }
	else{ $res="<span class='ak-muted'>—</span>"; }

	$rawBtn="<button type='button' class='ak-btn ak-btn-icon ak-btn-ghost' aria-label='{devt_raw_message}' title='{devt_raw_message}' OnClick=\"Loadjs('$page?raw-js=yes&raw=$raw')\"><i class='fas fa-file-lines'></i></button>";
	return "<tr class='".($rejected?"dhev-row-rej":"")."'><td class='dhev-when'>$when</td><td>$what</td><td>$dev</td><td>$addr</td><td>$netw</td><td class='dhev-res'>$res</td><td class='dhev-act'>$rawBtn</td></tr>";
}

function dhev_empty($f){
	$tpl=new template_admin();
	return "<div class='ak-empty-c dhev-empty'><i class='fas fa-network-wired'></i><b>".str_replace("%s",dhev_period_label($f["range"]),$tpl->_ENGINE_parse_body("{dhev_empty_title}"))."</b>".
		"<p>{dhev_empty_text}</p><div class='ak-actions dhev-empty-a'>".
		($f["range"]!=="7d"&&$f["range"]!=="30d"&&$f["range"]!=="90d"?"<button type='button' class='ak-btn' OnClick=\"DhevPeriod('7d');\"><i class='fas fa-clock-rotate-left'></i>{dhev_last_7d}</button>":"").
		"<button type='button' class='ak-btn' OnClick=\"LoadAjax('MainContent','fw.dhcp.status.php');\"><i class='fas fa-server'></i>{dhev_open_status}</button></div></div>";
}

function dhev_empty_filtered($f){
	$tpl=new template_admin();
	$title=$f["q"]!==""?str_replace("%s","“".dhev_txt($f["q"])."”",$tpl->_ENGINE_parse_body("{dhev_nomatch_title}")):$tpl->_ENGINE_parse_body("{dhev_nomatch_filters}");
	return "<div class='ak-empty-c dhev-empty'><i class='fas fa-magnifying-glass'></i><b>$title</b><p>".str_replace("%s",dhev_period_label($f["range"]),$tpl->_ENGINE_parse_body("{dhev_nomatch_text}"))."</p>".
		"<button type='button' class='ak-link' OnClick=\"DhevClear();\">{dhev_clear}</button></div>";
}

// ── Raw log line ──────────────────────────────────────────────────────────

function raw_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$raw=urlencode((string)$_GET["raw"]);
	return $tpl->js_dialog2("{devt_raw_message}","$page?raw-popup=yes&raw=$raw",650);
}

function raw_popup(){
	$tpl=new template_admin();
	$msg=(string)base64_decode((string)$_GET["raw"]);
	$k=md5($msg.microtime());
	$h="<div class='dhev-raw'><pre id='dhev-raw-$k'>".dhev_txt($msg)."</pre>".
		"<button type='button' class='ak-btn ak-btn-sm' OnClick=\"DhevCopy('$k',this);\"><i class='fas fa-copy'></i>{dhev_copy}</button></div>";
	$copied=json_encode(html_entity_decode($tpl->javascript_parse_text("{dhev_copied}"),ENT_QUOTES,"UTF-8"));
	$js="<script>window.DhevCopy=function(k,b){ var t=document.getElementById('dhev-raw-'+k).textContent;
 function ok(){ b.lastChild.nodeValue=$copied; }
 try{ if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(ok); return; } }catch(e){}
 var a=document.createElement('textarea'); a.value=t; a.style.position='fixed'; a.style.opacity='0'; document.body.appendChild(a); a.select();
 try{ document.execCommand('copy'); ok(); }catch(e){} document.body.removeChild(a); };</script>";
	echo dhev_style().$tpl->_ENGINE_parse_body($h).$js;
}

// ── CSS ───────────────────────────────────────────────────────────────────

function dhev_style(){
	if(isset($GLOBALS["DHEV_STYLE"])){return "";}
	$GLOBALS["DHEV_STYLE"]=true;
	$accent="#10b981";$soft="rgba(16,185,129,.12)";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent"])){$accent=$a["accent"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
	}
	return "<style>
.dhev{margin-top:10px;display:flex;flex-direction:column;gap:16px}
.dhev h2{margin:0}
.dhev-hero{padding:20px 26px;border-left:4px solid $accent}
.dhev-hero h2{font-size:22px;font-weight:600;color:#0f172a}
.dhev-hero p{margin:8px 0 0;font-size:14px;color:#475569;line-height:1.6;max-width:880px}
.dhev-chips{margin-top:12px;gap:8px}
.dhev-filters{display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;padding:14px 18px}
.dhev-f-q{flex:1 1 360px;min-width:300px}
.dhev-f-q .ak-search{width:100%;box-sizing:border-box}
.dhev-f-q .ak-search input{min-width:0;width:100%}
.dhev-f .ak-sel{min-width:200px}
.dhev-help{font-size:13px;color:#64748b;margin:6px 0 0}
.dhev-toggle{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 14px;border:1px solid #cbd5e1;border-radius:999px;background:#fff;color:#334155;font-size:13px;font-weight:600;cursor:pointer}
.dhev-toggle.on{border-color:#fca5a5;background:#fef2f2;color:#b91c1c}
.dhev-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.dhev-tile{display:block;box-sizing:border-box;width:100%;text-align:left;padding:14px 18px;font:inherit;color:inherit;background:#fff}
button.dhev-tile{cursor:pointer}
button.dhev-tile:hover{box-shadow:0 2px 6px rgba(15,23,42,.08)}
.dhev-tile-l{display:flex;justify-content:space-between;align-items:center;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.dhev-tile-v{font-size:26px;font-weight:700;color:#0f172a;margin-top:6px;line-height:1.2}
.dhev-tile-s{font-size:13px;color:#64748b;margin-top:4px}
.dhev-tile-ok .dhev-tile-v{color:#047857}.dhev-tile-ok .dhev-tile-l svg,.dhev-tile-ok .dhev-tile-l i{color:#059669}
.dhev-tile-err{border-color:#fca5a5}.dhev-tile-err .dhev-tile-v,.dhev-tile-err .dhev-tile-l,.dhev-tile-err .dhev-tile-s{color:#b91c1c}
.dhev-tile-warn{border-color:#fcd34d}.dhev-tile-warn .dhev-tile-v,.dhev-tile-warn .dhev-tile-l,.dhev-tile-warn .dhev-tile-s{color:#92400e}
.dhev-tile-off .dhev-tile-v{color:#64748b;font-size:20px}
.dhev-banner{margin:0}
.dhev-card{overflow:hidden}
.dhev-strip{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 18px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569}
.dhev-strip-r{display:inline-flex;align-items:center;gap:12px}
.dhev-sep{width:1px;height:16px;background:#e2e8f0}
.dhev-scroll{overflow-x:auto}
.dhev-tbl{width:100%;border-collapse:collapse}
.dhev-tbl thead th{padding:12px 16px;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e2e8f0;text-align:left;white-space:nowrap}
.dhev-tbl td{padding:10px 16px;vertical-align:middle;font-size:13px;color:#0f172a;border-top:1px solid #f1f5f9}
.dhev-tbl tbody tr:first-child td{border-top:0}
.dhev-tbl tbody tr:hover{background:#f8fafc}
.dhev-row-rej td:first-child{box-shadow:inset 3px 0 0 #fca5a5}
.dhev-row-rej{background:#fffafa}
.dhev-when{white-space:nowrap}
.dhev-when b{display:block;font-size:14px}
.dhev-when span{display:block;color:#64748b}
.dhev-what{display:inline-flex;align-items:flex-start;gap:8px;color:var(--dhev-fg)}
.dhev-what>svg,.dhev-what>i{margin-top:2px;font-size:15px}
.dhev-what-n{display:block;color:#0f172a;font-size:14px}
.dhev-tag{display:inline-block;margin-top:2px;padding:0 6px;border-radius:4px;background:#f1f5f9;color:#475569;font-size:12px;font-weight:600;letter-spacing:.03em}
.dhev-tag-info{background:#dbeafe;color:#1d4ed8;margin-top:0}
.dhev-t-ok{--dhev-fg:#059669}.dhev-t-err{--dhev-fg:#dc2626}.dhev-t-info{--dhev-fg:#2563eb}.dhev-t-warn{--dhev-fg:#d97706}.dhev-t-slate{--dhev-fg:#475569}
.dhev-dev{display:inline-flex;align-items:center;gap:6px;color:#0f172a;text-decoration:none}
a.dhev-dev:hover{color:#0f766e}
a.dhev-dev>svg,a.dhev-dev>i{font-size:11px;color:#94a3b8}
.dhev-unnamed{color:#64748b}
.dhev-sub{display:block;color:#64748b;font-size:13px;margin-top:2px}
.dhev-res .ak-pill{white-space:nowrap}
.dhev-reason{display:block;color:#b91c1c;font-size:13px;margin-top:4px;max-width:240px}
.dhev-act{width:1%;text-align:right}
.dhev-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 18px;border-top:1px solid #f1f5f9;font-size:13px;color:#334155}
.dhev-empty{padding:36px 20px}
.dhev-empty b{font-size:16px;color:#0f172a}
.dhev-empty p{max-width:600px;margin:6px auto 12px;font-size:14px;color:#475569}
.dhev-empty-a{justify-content:center}
.dhev-stopped{display:flex;align-items:center;gap:14px;padding:16px 20px;border-color:#fcd34d;background:#fffbeb}
.dhev-stopped-b{flex:1 1 auto}
.dhev-stopped-b b{font-size:15px;color:#92400e}
.dhev-stopped-b p{margin:4px 0 0;font-size:13px;color:#92400e}
.dhev-raw pre{white-space:pre-wrap;word-break:break-all;font-size:13px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin:0 0 10px}
.dhev-toggle:focus-visible,.dhev-tile:focus-visible,a.dhev-dev:focus-visible{outline:2px solid $accent;outline-offset:2px}
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
@media(max-width:1200px){.dhev-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>";
}
