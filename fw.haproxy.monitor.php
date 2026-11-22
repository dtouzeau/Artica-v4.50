<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!class_exists("postgres_sql")){include_once(dirname(__FILE__)."/ressources/class.postgres.inc");}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["list"])){lbmon_list();exit;}
if(isset($_GET["chart"])){lbmon_chart();exit;}
if(isset($_GET["server-pause-js"])){server_state_js(false);exit;}
if(isset($_GET["server-resume-js"])){server_state_js(true);exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header("{load_balancing} &raquo; {backends_status}","fas fa-network-wired","{APP_HAPROXY_BCKSTATS}",
		"$page?table=yes","lb-status","progress-haproxy-restart",false,"table-haproxy-bckstatus");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{backends_status}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

// Takes one server of a backend out of (or back into) the rotation through the
// daemon (/haproxy/runtime/server/*), which validates both names before writing
// to the stats socket.
function server_state_js($enable){
	$page=CurrentPageName();
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	if(!$users->AsProxyMonitor){
		$tpl->js_error("{ERROR_NO_PRIVS2}");
		return;
	}
	$backend=(string)($_GET["backend"] ?? "");
	$server=(string)($_GET["server"] ?? "");
	$route=$enable?"/haproxy/runtime/server/enable":"/haproxy/runtime/server/disable";
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("$route?backend=".urlencode($backend)."&server=".urlencode($server)));
	if(!is_object($json) || !$json->Status){
		$tpl->js_error(is_object($json)?$json->Error:$GLOBALS["CLASS_SOCKETS"]->mysql_error);
		return;
	}
	echo "if(document.getElementById('lbm-list')){LoadAjaxSilent('lbm-list','$page?list=yes');}else{LoadAjaxSilent('table-haproxy-bckstatus','$page?table=yes');}";
}

function lbmon_bytes($bytes):string{
	$bytes=intval($bytes);
	if($bytes<=0){return "<span class='awc-muted'>0</span>";}
	if($bytes<1024){return "$bytes B";}
	return FormatBytes($bytes/1024);
}

function lbmon_number($n):string{
	return number_format(intval($n),0,","," ");
}

// Sliding window on haproxy_log: zdate is a timestamptz, so it compares to now() directly.
function haproxy_log_since($hours):string{
	$hours=intval($hours);
	return "zdate > now() - interval '$hours hours'";
}

// Chart ranges: array(validated hours, bucket width in seconds, axis date format).
// Bucket sizes keep each range between 60 and 170 points.
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

// haproxy_log names a server by its IP: strip the port from "ip:port" / "[ipv6]:port".
function lbmon_address_ip($address):string{
	$a=trim((string)$address);
	if(preg_match('#^\[([^\]]+)\]#',$a,$re)){$a=$re[1];}
	elseif(substr_count($a,":")==1){$a=explode(":",$a)[0];}
	return filter_var($a,FILTER_VALIDATE_IP)?$a:"";
}

// Per-server request series over the last 6 hours, 3-minute buckets (same scale as the
// global chart). One query for the whole page and a shared time axis, keyed by
// "<backend section>|<ip>" so two services pointing at the same server stay apart.
function lbmon_sparklines():array{
	$q=new postgres_sql();
	$slots=array();$raw=array();
	$rs=$q->QUERY_SQL("SELECT servicename, host(backend) AS bk,
		floor(extract(epoch from zdate)/180)*180 AS b, SUM(rqs) AS n
		FROM haproxy_log WHERE ".haproxy_log_since(6)."
		GROUP BY 1,2,3 ORDER BY 3");
	if($rs){
		while($l=@pg_fetch_assoc($rs)){
			$slots[intval($l["b"])]=true;
			$raw[$l["servicename"]."|".$l["bk"]][intval($l["b"])]=intval($l["n"]);
		}
	}
	$slots=array_keys($slots);
	sort($slots);
	// A polyline needs two points: pad the axis backwards with empty buckets.
	while(count($slots)<2){array_unshift($slots,($slots[0]??0)-180);}
	$SPARK=array();
	foreach($raw as $k=>$byslot){
		$serie=array();
		foreach($slots as $sl){$serie[]=intval($byslot[$sl]??0);}
		$SPARK[$k]=$serie;
	}
	return array($SPARK,count($slots));
}

// The sparkline opens the backend statistics window (fw.haproxy.backends.php?stats-js).
function lbmon_sparkline($SPARK,$nslots,$backend,$address,$servicename,$servername):string{
	$ip=lbmon_address_ip($address);
	$serie=$SPARK["$backend|$ip"]??array();
	// No traffic draws a flat line: "no request" is not the same as "no chart".
	if(count($serie)<2){$serie=array_fill(0,max(2,$nslots),0);}
	$openjs="Loadjs('fw.haproxy.backends.php?stats-js=".urlencode($servername)."&servicename=".urlencode($servicename)."&section=".urlencode($backend)."')";
	return "<div class='lbm-spark' title='{statistics}' OnClick=\"$openjs\"><span class='hapspark'>".implode(",",$serie)."</span></div>";
}

// Global traffic chart, all services, read from haproxy_log. Everything goes out in one
// response, script included, because the fragment is injected by LoadAjaxSilent.
function lbmon_chart(){
	$tpl=new template_admin();
	list($hours,$width,$fmt)=haproxy_chart_range($_GET["hours"]??6);
	$bucket="floor(extract(epoch from zdate)/$width)*$width";

	$q=new postgres_sql();
	$res=$q->QUERY_SQL("SELECT $bucket AS b, SUM(rqs) AS rqs,
		SUM(CASE WHEN http_code>=400 AND http_code NOT IN (401,407) THEN rqs ELSE 0 END) AS err
		FROM haproxy_log WHERE ".haproxy_log_since($hours)."
		GROUP BY 1 ORDER BY 1");

	$labels=array();$full=array();$vreq=array();$verr=array();$keys=array();$nreq=array();
	if($res){
		while($ligne=@pg_fetch_assoc($res)){
			$t=intval($ligne["b"]);
			$labels[]=date($fmt,$t);
			$full[]=haproxy_chart_interval($t,$width);
			$keys[]=$t;
			$nreq[]=intval($ligne["rqs"]);
			$vreq[]=round(intval($ligne["rqs"])/$width,2);
			$verr[]=round(intval($ligne["err"])/$width,2);
		}
	}

	// Error breakdown per code for the tooltip; the share is over ALL requests of the bucket.
	$bycode=array();
	$res2=$q->QUERY_SQL("SELECT $bucket AS b, http_code, SUM(rqs) AS n
		FROM haproxy_log
		WHERE http_code>=400 AND http_code NOT IN (401,407) AND ".haproxy_log_since($hours)."
		GROUP BY 1,2 ORDER BY 1, 3 DESC");
	if($res2){
		while($ligne=@pg_fetch_assoc($res2)){
			$bycode[intval($ligne["b"])][]=array(intval($ligne["http_code"]),intval($ligne["n"]));
		}
	}
	$tips=array();
	foreach($keys as $i=>$k){
		$line=array();
		foreach(($bycode[$k]??array()) as $cc){
			$pct=$nreq[$i]>0?round(($cc[1]/$nreq[$i])*100):0;
			$line[]=$cc[0]."  $pct%  (".$cc[1].")";
		}
		$tips[]=$line;
	}

	$btns=array();
	foreach(haproxy_chart_ranges() as $hh=>$lbl){
		$cls=($hh==$hours)?"btn-primary btn-xs":"btn-white btn-xs";
		$btns[]="<label class=\"btn $cls\" OnClick=\"LbmChartLoad($hh);\">$lbl</label>";
	}
	$head="<div class='lbm-chart-head'><div class='lbm-tile-label'>{requests}</div>".
		"<div class=\"btn-group\" data-toggle=\"buttons\">".implode("",$btns)."</div></div>";

	if(count($labels)==0){
		echo $tpl->_ENGINE_parse_body($head."<div class='lbm-chart-empty'>{no_data}</div>");
		return;
	}

	$jl=json_encode($labels);$jf=json_encode($full);$jr=json_encode($vreq);
	$je=json_encode($verr);$jt=json_encode($tips);
	$req_t=$tpl->javascript_parse_text("{requests}")."/s";
	$err_t=$tpl->javascript_parse_text("{errors}")."/s";

	// Height lives on the box: maintainAspectRatio:false ignores the canvas height.
	$h=array();
	$h[]=$head;
	$h[]="<div style='position:relative;height:250px;width:100%'><canvas id='lbm-chart-canvas'></canvas></div>";
	$h[]="<script>
(function(){
	function boot(cb){
		if(typeof Chart!=='undefined'){cb();return;}
		if(window.__lbm_chart_loading){setTimeout(function(){boot(cb);},200);return;}
		window.__lbm_chart_loading=true;
		var sc=document.createElement('script');sc.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';
		sc.onload=function(){cb();};document.head.appendChild(sc);
	}
	boot(function(){
		var el=document.getElementById('lbm-chart-canvas');
		if(!el){return;}
		if(window.__lbm_chart){try{window.__lbm_chart.destroy();}catch(e){}}
		var full=$jf,tips=$jt;
		// A single bucket with zero-radius points would draw nothing.
		var pr=$jl.length<2?5:($jl.length<=40?3:0);
		window.__lbm_chart=new Chart(el,{type:'line',
			data:{labels:$jl,datasets:[
				{label:'$req_t',data:$jr,borderColor:'#006a62',backgroundColor:'rgba(0,106,98,0.15)',
					fill:true,borderWidth:2,pointRadius:pr,pointHoverRadius:Math.max(pr,4),tension:0.3},
				{label:'$err_t',data:$je,borderColor:'#ba1a1a',backgroundColor:'rgba(186,26,26,0.12)',
					fill:true,borderWidth:2,pointRadius:pr,pointHoverRadius:Math.max(pr,4),tension:0.3}
			]},
			options:{responsive:true,maintainAspectRatio:false,animation:false,
				interaction:{mode:'index',intersect:false},
				plugins:{legend:{display:true,position:'bottom'},
					tooltip:{mode:'index',intersect:false,callbacks:{
						title:function(items){return full[items[0].dataIndex]||'';},
						afterBody:function(items){return tips[items[0].dataIndex]||[];}}}},
				scales:{y:{beginAtZero:true,suggestedMax:1},x:{ticks:{maxTicksLimit:12,autoSkip:true}}}}
		});
	});
})();
</script>";
	echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function lbmon_style():string{
	return "<style>
.lbm-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin:10px 0 0 0}
.lbm-tile{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:16px 18px}
.lbm-tile-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.lbm-tile-value{font-size:30px;font-weight:800;line-height:38px;color:#111d23;margin-top:4px}
.lbm-tile-sub{font-size:12px;color:#3d4947;margin-top:4px;display:flex;align-items:center;gap:6px}
.lbm-red .lbm-tile-value,.lbm-red .lbm-tile-sub{color:#ba1a1a}
.lbm-dot{width:8px;height:8px;border-radius:50%;background:#26a69a;display:inline-block}
.lbm-dot-off{background:#ba1a1a}
.lbm-st{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:700;text-transform:uppercase;padding:3px 8px;border-radius:4px;white-space:nowrap}
.lbm-st-on{background:#e0f2f1;color:#006a62}
.lbm-st-maint{background:#fef3c7;color:#865300}
.lbm-st-err{background:#fee2e2;color:#ba1a1a;cursor:help}
.lbm-st-dot{width:6px;height:6px;border-radius:50%;background:#006a62}
.lbm-svc td{background:#f9fafa}
.lbm-svc-meta{font-size:12px;color:#3d4947;margin-top:2px}
.lbm-srv-cell{position:relative;padding-left:56px!important}
.lbm-tree{position:absolute;left:38px;top:0;height:50%;width:12px;border-left:2px solid #bcc9c6;border-bottom:2px solid #bcc9c6;border-bottom-left-radius:3px}
.lbm-srv-ico{width:32px;height:32px;border-radius:6px;background:#f1f3f3;color:#5b6765;display:inline-flex;align-items:center;justify-content:center;font-size:14px;flex:0 0 auto}
.lbm-srv-ico-maint{background:#fffbeb;color:#865300}
.lbm-srv-ico-err{background:#fee2e2;color:#ba1a1a}
.lbm-srv-name a{color:#006a62;font-weight:600;text-decoration:none}
.lbm-srv-name a:hover{text-decoration:underline}
.lbm-srv-maint .lbm-srv-name a{color:#111d23}
.lbm-srv-err .lbm-srv-name a,.lbm-srv-err .lbm-addr{color:#ba1a1a}
.lbm-srv-err td{background:#fffafa}
.lbm-addr{font-size:12px;color:#3d4947}
.lbm-type{font-size:12px;color:#3d4947;background:#f1f3f3;padding:2px 8px;border-radius:4px}
.lbm-num{text-align:right;white-space:nowrap}
.lbm-svc .lbm-num{font-weight:600;color:#111d23}
.lbm-aggr{font-size:12px;color:#3d4947;font-style:italic;white-space:nowrap}
.lbm-btn{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:5px 12px;border-radius:4px;text-decoration:none!important;white-space:nowrap;cursor:pointer;opacity:.45;transition:opacity .15s}
.awc-tbl tbody tr:hover .lbm-btn{opacity:1}
.lbm-btn-pause{border:1px solid #865300;color:#865300!important;background:#fff}
.lbm-btn-pause:hover{background:#fef3c7}
.lbm-btn-resume{border:1px solid #006a62;background:#006a62;color:#fff!important}
.lbm-btn-resume:hover{background:#00534c}
.lbm-chart{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:16px 18px;margin-top:16px}
.lbm-chart-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px}
.lbm-chart-empty{padding:44px;text-align:center;color:#3d4947;font-size:13px}
.lbm-spark{margin-top:4px;line-height:0;cursor:pointer}
.lbm-btn-off{border:1px solid #bcc9c6;color:#8a968b!important;background:#f1f3f3;cursor:not-allowed}
.lbm-chk{font-size:12px;color:#3d4947;display:inline-flex;flex-direction:column;max-width:260px}
.lbm-chk-x{font-size:11px;color:#6c7b6d;white-space:normal}
.lbm-chk-err,.lbm-chk-err .lbm-chk-x{color:#ba1a1a}
.lbm-dr{font-size:10px;font-weight:700;color:#865300;background:#fef3c7;border-radius:4px;padding:1px 6px;margin-left:6px}
.lbm-svc-down td{background:#fff5f5}
.lbm-svc-down td:first-child{box-shadow:inset 3px 0 0 #ba1a1a}
.lbm-empty{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:48px 20px;text-align:center;margin-top:16px}
.lbm-empty-ico{font-size:40px;color:#bcc9c6}
.lbm-empty-txt{font-size:15px;color:#3d4947;margin:14px 0 18px 0}
@media(max-width:1100px){.lbm-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.lbm-tiles{grid-template-columns:1fr}}
</style>";
}

// A failed health check code, with its explanation as a tooltip.
function lbmon_check_label($code):string{
	$code=strtoupper(preg_replace('/[^A-Za-z0-9]/','',(string)$code));
	if($code==""){return "{stopped}";}
	return $code;
}

function lbmon_server_badge($srv):string{
	switch($srv->state){
		case "maintenance": return "<span class='lbm-st lbm-st-maint'>{maintenance}</span>";
		case "error":
		case "stopped":
			$label=lbmon_check_label($srv->check);
			$tip=$srv->check!=""?"{lbmon_chk_".lbmon_check_label($srv->check)."}":"{stopped}";
			return "<span class='lbm-st lbm-st-err' title=\"$tip\">$label</span>";
	}
	return "<span class='lbm-st lbm-st-on'>{online}</span>";
}

// Health check codes that report a failure (the others passed or are pending).
function lbmon_check_failed($code):bool{
	return in_array(lbmon_check_label($code),array("SOCKERR","L4TMOUT","L4TOUT","L4CON","L6TOUT","L6RSP","L7TOUT","L7RSP","L7STS"),true);
}

// Last health check of a server: code, then what it means, red on a failure.
function lbmon_check_text($code):string{
	if(trim((string)$code)==""){return "<span class='awc-muted'>&mdash;</span>";}
	$c=lbmon_check_label($code);
	$cls=lbmon_check_failed($code)?" lbm-chk-err":"";
	return "<span class='lbm-chk$cls'><strong>$c</strong><span class='lbm-chk-x'>{lbmon_chk_$c}</span></span>";
}

function lbmon_monitor_js():string{
	$page=CurrentPageName();
	return "LoadAjaxSilent('table-haproxy-bckstatus','$page?table=yes');";
}

// The page shell: tiles, chart and search stay put; only the list below them is
// reloaded every 15 s (?list=yes), so the chart is not redrawn on each pass.
function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/haproxy/monitor"));

	$jsstart=$tpl->framework_buildjs("/haproxy/start","haproxy.progress","haproxy.progress.log","progress-haproxy-restart",lbmon_monitor_js());
	$topbuttons=array();
	$topbuttons[]=array(lbmon_monitor_js(),ico_refresh,"{refresh}");
	$topbuttons[]=array($tpl->framework_buildjs("/haproxy/reload","haproxy.progress","haproxy.progress","progress-haproxy-restart",lbmon_monitor_js()),
		ico_save,"{reload} {APP_HAPROXY}","AsProxyMonitor");
	$topbuttons[]=array("LoadAjaxSilent('MainContent','fw.haproxy.services.php');","fas fa-random","{tcp_services}");
	$TINY=array();
	$TINY["TITLE"]="{load_balancing} &raquo; {backends_status}";
	$TINY["ICO"]="fas fa-network-wired";
	$TINY["EXPL"]="{APP_HAPROXY_BCKSTATS}";
	$TINY["URL"]="lb-status";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$tiny="<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";

	if(!is_object($json)){
		echo $tpl->_ENGINE_parse_body($tpl->div_error($GLOBALS["CLASS_SOCKETS"]->mysql_error)).$tiny;
		return;
	}
	if(!$json->Running){
		$html=array();
		$html[]="<div class='lbm-empty'>";
		$html[]="<div class='lbm-empty-ico'><i class='fas fa-network-wired'></i></div>";
		$html[]="<div class='lbm-empty-txt'>{lbmon_not_started}</div>";
		$html[]="<a class='lbm-btn lbm-btn-resume' style='opacity:1;font-size:13px;padding:8px 16px' href=\"javascript:blur();\" OnClick=\"$jsstart\"><i class='".ico_play."'></i> {start_service}</a>";
		$html[]="</div>";
		echo lbmon_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$tiny;
		return;
	}
	if(!$json->Status){
		echo $tpl->_ENGINE_parse_body($tpl->div_error((string)$json->Error)).$tiny;
		return;
	}

	$html=array();
	$html[]="<div id='lbm-tiles'>".lbmon_tiles_html($json->Monitor)."</div>";
	$html[]="<div class='lbm-chart' id='lbm-traffic-chart'></div>";
	$html[]="<div style='margin-top:16px'>".awc_search_bar("lbm-search","{search}")."</div>";
	$html[]="<div id='lbm-list'>".lbmon_list_html($json->Monitor)."</div>";

	// The chart reads the database (one bucket per minute): it keeps its own 60 s
	// pace and the selected range survives a reload. The list follows the stats
	// socket, every 15 s.
	$html[]="<script>
(function(){
	window.LbmChartLoad=function(hours){
		window.__lbm_hours=hours||window.__lbm_hours||6;
		LoadAjaxSilent('lbm-traffic-chart','$page?chart=yes&hours='+window.__lbm_hours);
	};
	if(window.__lbm_ctick){clearInterval(window.__lbm_ctick);}
	window.__lbm_ctick=setInterval(function(){
		if(document.getElementById('lbm-traffic-chart')){LbmChartLoad();}
		else{clearInterval(window.__lbm_ctick);}
	},60000);
	if(window.__lbm_ltick){clearInterval(window.__lbm_ltick);}
	window.__lbm_ltick=setInterval(function(){
		if(document.getElementById('lbm-list')){LoadAjaxSilent('lbm-list','$page?list=yes');}
		else{clearInterval(window.__lbm_ltick);}
	},15000);
	LbmChartLoad();
})();
</script>";
	echo awc_table_style().lbmon_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$tiny;
}

// The list alone, for the 15 s refresh. It also redraws the tiles: same dump.
function lbmon_list(){
	$tpl=new template_admin();
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/haproxy/monitor"));
	if(!is_object($json)){
		echo $tpl->_ENGINE_parse_body($tpl->div_error($GLOBALS["CLASS_SOCKETS"]->mysql_error));
		return;
	}
	if(!$json->Running){
		// Stopped in between: the full page shows the start button.
		echo "<script>".lbmon_monitor_js()."</script>";
		return;
	}
	if(!$json->Status){
		echo $tpl->_ENGINE_parse_body($tpl->div_error((string)$json->Error));
		return;
	}
	$tiles=$tpl->_ENGINE_parse_body(lbmon_tiles_html($json->Monitor));
	echo awc_table_style().lbmon_style().lbmon_list_html($json->Monitor).
		"<script>(function(){var t=document.getElementById('lbm-tiles');if(t){t.innerHTML=".json_encode($tiles).";}})();</script>";
}

function lbmon_tiles_html($m):string{
	$services=is_array($m->services)?$m->services:array();
	$bin=0;$bout=0;
	foreach($services as $svc){$bin+=intval($svc->bytes_in);$bout+=intval($svc->bytes_out);}
	$html=array();
	$html[]="<div class='lbm-tiles'>";
	$allOpen=intval($m->services_active)==count($services);
	$html[]="<div class='lbm-tile'><div class='lbm-tile-label'>{lbmon_active_services}</div>".
		"<div class='lbm-tile-value'>".intval($m->services_active)."</div>".
		"<div class='lbm-tile-sub'><span class='lbm-dot".($allOpen?"":" lbm-dot-off")."'></span>".
		($allOpen?"{lbmon_all_frontends_ok}":"{lbmon_some_frontends_down}")."</div></div>";
	$html[]="<div class='lbm-tile'><div class='lbm-tile-label'>{lbmon_servers_online}</div>".
		"<div class='lbm-tile-value'>".intval($m->servers_online)."</div>".
		"<div class='lbm-tile-sub'>{lbmon_configured_servers}: ".intval($m->servers_total)."</div></div>";
	$failing=is_array($m->failing)?$m->failing:array();
	$errSub="{lbmon_no_server_error}";
	if(count($failing)>0){
		$errSub="<i class='fas fa-triangle-exclamation'></i> ".htmlspecialchars(implode(", ",array_slice($failing,0,3))).(count($failing)>3?"…":"")." {lbmon_unreachable}";
	}
	$html[]="<div class='lbm-tile".(count($failing)>0?" lbm-red":"")."'><div class='lbm-tile-label'>{lbmon_servers_error}</div>".
		"<div class='lbm-tile-value'>".intval($m->servers_error)."</div>".
		"<div class='lbm-tile-sub'>$errSub</div></div>";
	// Traffic of every frontend, as the old headline gave it.
	$html[]="<div class='lbm-tile'><div class='lbm-tile-label'>{lbmon_total_requests}</div>".
		"<div class='lbm-tile-value'>".lbmon_number($m->requests)."</div>".
		"<div class='lbm-tile-sub'>{lbmon_since_start} &middot; IN ".strip_tags(lbmon_bytes($bin))." / OUT ".strip_tags(lbmon_bytes($bout))."</div></div>";
	$html[]="</div>";
	return implode("\n",$html);
}

function lbmon_list_html($m):string{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$t=time();
	$services=is_array($m->services)?$m->services:array();
	list($SPARK,$nslots)=lbmon_sparklines();

	$html=array();
	$html[]="<div class='awc-wrap' style='padding:16px 0'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th style='width:140px'>{status}</th><th>{lbmon_service_server}</th><th style='width:110px'>{type}</th><th>{health}</th>";
	$html[]="<th class='lbm-num' style='width:120px'>{lbmon_incoming}</th><th class='lbm-num' style='width:120px'>{lbmon_outgoing}</th>";
	$html[]="<th class='lbm-num' style='width:120px'>{requests}</th><th style='width:170px;text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='awc-rows-$t'>";

	$nservers=0;
	foreach($services as $gi=>$svc){
		$name=htmlspecialchars((string)$svc->name);
		$servers=is_array($svc->servers)?$svc->servers:array();
		$nservers+=count($servers);
		$up=0;
		foreach($servers as $srv){if($srv->state=="online"){$up++;}}
		// A frontend still open with no server left to answer is not "active".
		$degraded=count($servers)>0 && $up==0;
		$badge=$svc->status=="OPEN"?"<span class='lbm-st lbm-st-on'><span class='lbm-st-dot'></span>{active2}</span>":
			"<span class='lbm-st lbm-st-err'>{stopped}</span>";
		if($degraded){$badge.="<div style='margin-top:4px'><span class='lbm-st lbm-st-err'>0 / ".count($servers)." {servers}</span></div>";}
		$html[]="<tr class='lbm-svc".($degraded?" lbm-svc-down":"")."' data-grp='$gi'>";
		$html[]="<td>$badge</td>";
		$html[]="<td><div class='awc-ident'><span class='awc-ico'><i class='fas fa-network-wired'></i></span>".
			"<span><span class='awc-name'>$name</span><div class='lbm-svc-meta'>frontend &middot; ".count($servers)." {lbmon_associated_servers}</div></span></div></td>";
		$html[]="<td><span class='lbm-type'>frontend</span></td>";
		$html[]="<td><span class='awc-muted'>&mdash;</span></td>";
		$html[]="<td class='lbm-num'>".lbmon_bytes($svc->bytes_in)."</td>";
		$html[]="<td class='lbm-num'>".lbmon_bytes($svc->bytes_out)."</td>";
		$html[]="<td class='lbm-num'>".lbmon_number($svc->requests)."</td>";
		$html[]="<td style='text-align:right'><span class='lbm-aggr'>{lbmon_global_aggregation}</span></td>";
		$html[]="</tr>";

		foreach($servers as $srv){
			$rowClass="";$icoClass="";$ico=ico_server;
			if($srv->state=="maintenance"){$rowClass="lbm-srv-maint";$icoClass=" lbm-srv-ico-maint";$ico="fas fa-triangle-exclamation";}
			if($srv->state=="error" || $srv->state=="stopped"){$rowClass="lbm-srv-err";$icoClass=" lbm-srv-ico-err";$ico="fas fa-xmark";}
			$qs="backend=".urlencode((string)$srv->backend)."&server=".urlencode((string)$srv->name);
			$edit="Loadjs('fw.haproxy.backends.php?backend-js=".urlencode((string)$srv->name)."&backendname=".urlencode((string)$srv->name)."&servicename=".urlencode((string)$svc->name)."')";
			$online=$srv->state=="online";
			if(!$users->AsProxyMonitor){
				// Without the privilege the button stays, greyed out and inert.
				$action="<span class='lbm-btn lbm-btn-off'><i class='fas ".($online?"fa-circle-pause":"fa-circle-play")."'></i> ".($online?"{lbmon_put_in_pause}":"{resume}")."</span>";
			}elseif($online){
				$action="<a class='lbm-btn lbm-btn-pause' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?server-pause-js=yes&$qs')\"><i class='fas fa-circle-pause'></i> {lbmon_put_in_pause}</a>";
			}else{
				$action="<a class='lbm-btn lbm-btn-resume' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?server-resume-js=yes&$qs')\"><i class='fas fa-circle-play'></i> {resume}</a>";
			}
			// A server of the "dr_<service>" section only takes over on a disaster.
			$dr=preg_match('/^dr_/',(string)$srv->backend)?" <span class='lbm-dr'>DR</span>":"";
			$html[]="<tr class='$rowClass' data-grp='$gi'>";
			$html[]="<td>".lbmon_server_badge($srv)."</td>";
			$html[]="<td class='lbm-srv-cell'><span class='lbm-tree'></span><div class='awc-ident' style='gap:12px'>".
				"<span class='lbm-srv-ico$icoClass'><i class='$ico'></i></span>".
				"<span><span class='lbm-srv-name'><a href=\"javascript:blur();\" OnClick=\"$edit\">".htmlspecialchars((string)$srv->name)."</a></span>$dr".
				"<div class='lbm-addr'>".htmlspecialchars((string)$srv->address)."</div></span></div></td>";
			$html[]="<td><span class='awc-mono'>server</span></td>";
			$html[]="<td>".lbmon_check_text($srv->check)."</td>";
			$html[]="<td class='lbm-num awc-mono'>".lbmon_bytes($srv->bytes_in)."</td>";
			$html[]="<td class='lbm-num awc-mono'>".lbmon_bytes($srv->bytes_out)."</td>";
			$html[]="<td class='lbm-num awc-mono'>".lbmon_number($srv->requests).
				lbmon_sparkline($SPARK,$nslots,(string)$srv->backend,(string)$srv->address,(string)$svc->name,(string)$srv->name)."</td>";
			$html[]="<td style='text-align:right'>$action</td>";
			$html[]="</tr>";
		}
	}
	if(count($services)==0){
		$html[]="<tr><td colspan='8'><div class='awc-empty'>{no_data}</div></td></tr>";
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>".(count($services)+$nservers)." {items} (".count($services)." {services}, $nservers {servers})</span>";
	$html[]="<span class='awc-pg' id='awc-pager-$t'></span>";
	$html[]="</div></div></div>";

	// Search over a service AND its servers: a service that matches shows all its
	// servers, a server that matches shows with its service. "*" is a wildcard.
	// The field lives outside the list, so the term survives the 15 s reload and
	// is applied again to the new rows.
	$html[]="<script>
(function(){
	var inp=document.getElementById('lbm-search');
	var tb=document.getElementById('awc-rows-$t');
	if(!inp||!tb){return;}
	var box=document.getElementById('awc-pager-$t');
	function apply(){
		var q=inp.value.replace(/^\s+|\s+$/g,'').toLowerCase();
		var rows=tb.rows;
		if(q==''){
			if(box){box.style.display='';}
			if(typeof tb.__awcRedraw=='function'){tb.__awcRedraw();}
			else{for(var i=0;i<rows.length;i++){rows[i].style.display='';}}
			return;
		}
		if(box){box.style.display='none';}
		var rx=q.indexOf('*')>=0?new RegExp(q.replace(/[.+?^\${}()|[\]\\\\]/g,'\\\\$&').replace(/\*/g,'.*')):null;
		function hit(tr){var s=(tr.textContent||'').toLowerCase();return rx?rx.test(s):s.indexOf(q)>=0;}
		var grp={},svc={};
		for(var i=0;i<rows.length;i++){
			var g=rows[i].getAttribute('data-grp');if(g===null){continue;}
			if(rows[i].className.indexOf('lbm-svc')>=0){svc[g]=hit(rows[i]);}
			else if(hit(rows[i])){grp[g]=true;}
		}
		for(var i=0;i<rows.length;i++){
			var g=rows[i].getAttribute('data-grp');if(g===null){continue;}
			var isSvc=rows[i].className.indexOf('lbm-svc')>=0;
			var show=svc[g]||(isSvc?grp[g]:hit(rows[i]));
			rows[i].style.display=show?'':'none';
		}
	}
	inp.onkeyup=apply;inp.onchange=apply;
	window.__lbm_search_apply=apply;
})();
</script>";
	// peity is loaded on demand.
	$html[]="<script>
(function(){
	function bootPeity(cb){
		if(typeof jQuery!=='undefined' && jQuery.fn && jQuery.fn.peity){cb();return;}
		if(window.__peity_loading){setTimeout(function(){bootPeity(cb);},200);return;}
		window.__peity_loading=true;
		var sc=document.createElement('script');sc.src='js/jquery.peity.min.js';
		sc.onload=function(){cb();};document.head.appendChild(sc);
	}
	bootPeity(function(){
		jQuery('.hapspark').peity('line',{width:96,height:22,fill:'rgba(0,106,98,0.15)',stroke:'#006a62',strokeWidth:2});
	});
})();
</script>";
	// The pager first, then the search re-applied: it hides the pager while a term is typed.
	return $tpl->_ENGINE_parse_body(@implode("\n",$html)).
		awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"awc-rows-$t","awc-pager-$t").
		"<script>if(typeof window.__lbm_search_apply==='function'){window.__lbm_search_apply();}</script>".
		$tpl->search_block_css();
}
