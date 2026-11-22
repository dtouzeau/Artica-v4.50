<?php
// HaCluster » Service events: the journal of the load-balancer, read only.
// Source: GET /hacluster/server/events/{max}/{search} (articarest hacluster/HaClusterServer eventsSearch)
//   200 {Status, Error, Info, Logs:[raw syslog lines of /var/log/hacluster.log, newest first]}
//   {search} "NONE" or < 3 characters = no filter; otherwise an rg regex, case-insensitive.
// Server ids in the HAProxy lines are "proxys/proxy<backend ID>": hacluster_db_list_backends() names them.
// Redesign 2026-11-18 (stitch_hacluster_logs): value banner, summary of the loaded events, plain
// sentence per event. One classifier (hae_classify) feeds the tiles, the filter and the table.
// Widgets prefixed hae_, CSS classes hae-.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.hacluster-backends.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["results"])){results();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header(
		"{service_events}",
		ico_eye,
		"{hacluster_events_explain}",
		"$page?start=yes",
		"hacluster-events",
		"progress-firehol-restart",false,
		"table-hacluster-events"
	);

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_HACLUSTER} {service_events}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

// Whitelisted filters, shared by the toolbar and the responder.
function hae_max():int{
	$m=intval($_GET["max"] ?? 250);
	return in_array($m,array(100,250,500,1000))?$m:250;
}
function hae_kind():string{
	$k=(string)($_GET["kind"] ?? "all");
	return in_array($k,array("all","problems","servers","config","cluster"))?$k:"all";
}
function hae_term():string{
	$s=trim((string)($_GET["search"] ?? ""));
	return substr(str_replace(array("\r","\n","\t"),"",$s),0,120);
}

// Content of the page: toolbar (kept across reloads, so typing is never lost) + results host.
function start():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$opts="";
	foreach(array(100,250,500,1000) as $n){
		$opts.="<option value='$n'".($n==250?" selected":"").">".str_replace("%N%",(string)$n,hae_txt("hae_load_n"))."</option>";
	}
	$h=array();
	$h[]="<div class='hae'>";
	$h[]=hae_banner();
	$h[]="<div class='hae-card hae-tools'>";
	$h[]="<div class='hae-search'><label for='hae-q' class='sr-only'>{hae_search_ph}</label>"
		."<i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
		."<input type='search' id='hae-q' placeholder=\"".hae_attr(hae_txt("hae_search_ph"))."\" autocomplete='off'>"
		."<div class='hae-help'>{hae_search_help}</div></div>";
	$h[]="<div class='hae-load'><label for='hae-max'>{hae_load}</label><select id='hae-max'>$opts</select>"
		."<div class='hae-help'>{hae_load_help}</div></div>";
	$h[]="</div>";
	$h[]="<div id='hae-results'></div>";
	$h[]="</div>";

	$topbuttons=array();
	$topbuttons[]=array("HaeGo()",ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{APP_HACLUSTER}&nbsp;&raquo;&nbsp;{service_events}";
	$TINY["ICO"]=ico_eye;
	$TINY["EXPL"]="{hacluster_events_explain}";
	$TINY["URL"]="hacluster-events";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

	echo hae_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).hae_js($page)."<script>NoSpinner();HaeGo();$tiny</script>";
	return true;
}

function hae_js(string $page):string{
	return "<script>
window.HaeK='all';
window.HaeGo=function(){
  var q=document.getElementById('hae-q'),m=document.getElementById('hae-max');
  var s=q?q.value:'',n=m?m.value:'250';
  LoadAjaxSilent('hae-results','$page?results=yes&max='+encodeURIComponent(n)+'&kind='+encodeURIComponent(window.HaeK)+'&search='+encodeURIComponent(s));
};
window.HaeKind=function(k){window.HaeK=k;HaeGo();};
window.HaeClear=function(){var q=document.getElementById('hae-q');if(q){q.value='';}window.HaeK='all';HaeGo();};
window.HaeLoad=function(n){var m=document.getElementById('hae-max');if(m){m.value=String(n);}HaeGo();};
(function(){
  var q=document.getElementById('hae-q'),m=document.getElementById('hae-max'),t=0;
  if(q){
    q.onkeydown=function(e){if((e.keyCode||e.which)==13){e.preventDefault();clearTimeout(t);HaeGo();}};
    q.oninput=function(){clearTimeout(t);var l=q.value.trim().length;if(l==0||l>=3){t=setTimeout(HaeGo,700);}};
  }
  if(m){m.onchange=function(){HaeGo();};}
})();
</script>";
}

// Translated plain text of a token, [br] turned into spaces, ready for str_replace of placeholders.
function hae_txt(string $token):string{
	static $cache=array();
	if(isset($cache[$token])){return $cache[$token];}
	$tpl=new template_admin();
	$t=(string)$tpl->_ENGINE_parse_body("{".$token."}");
	$t=preg_replace('#\s*<br\s*/?>\s*#i'," ",$t);
	$cache[$token]=trim($t);
	return $cache[$token];
}

function hae_attr(string $s):string{
	return htmlspecialchars($s,ENT_QUOTES,"UTF-8",false);
}

// Data coming from the log: escaped, braces neutralised for the token engine.
function hae_e($s):string{
	return str_replace(array("{","}"),array("&#123;","&#125;"),req_html((string)$s));
}

// Backend ID => display name (part before the first dot, as before).
function hae_backends():array{
	$out=array();
	$results=hacluster_db_list_backends("ID","ASC","ID,backendname");
	if(!is_array($results)){return $out;}
	foreach($results as $ligne){
		$id=intval($ligne["ID"] ?? 0);
		$name=(string)($ligne["backendname"] ?? "");
		if($id<=0 || $name===""){continue;}
		if(strpos($name,".")>0){$tb=explode(".",$name);$name=$tb[0];}
		$out[$id]=$name;
	}
	return $out;
}

// rg pattern sent to the daemon. A typed server name also matches its HAProxy id (proxys/proxyN),
// which is what the HAProxy lines carry. "/" would split the route: it becomes "." (any character).
function hae_pattern(string $term,array $backends):string{
	if(strlen($term)<3){return "NONE";}
	$p=str_replace("/",".",$term);
	$ids=array();
	foreach($backends as $id=>$name){
		if(stripos($name,$term)!==false){$ids[]=intval($id);}
	}
	if(count($ids)>0 && count($ids)<=20){
		$p="(".$p.")|proxy(".implode("|",$ids).")\\b";
	}
	return $p;
}

// Syslog time without year: the current year, or the previous one for a date in the future.
function hae_time(string $mon,string $day,string $hms):int{
	$t=strtotime("$mon $day ".date("Y")." $hms");
	if($t===false){return 0;}
	if($t>time()+86400){$t=strtotime("$mon $day ".(intval(date("Y"))-1)." $hms");}
	return $t===false?0:intval($t);
}

// Message without the Go source references and the bracketed tags, for display as a sentence.
function hae_clean(string $msg):string{
	$m=preg_replace('#[A-Za-z0-9_]+\.go\[[^\]]*\]:?#',"",$msg);
	$m=preg_replace('#[A-Za-z0-9_]+\.go\b:?#',"",$m);
	$m=preg_replace('#\[(STANDARD|STARTING|STOPPING|RECONFIGURE|RESTART|MICRONODE|WATCHDOG|CERTIFICATE)\]:?#',"",$m);
	$m=preg_replace('#\s{2,}#'," ",$m);
	return trim($m," :,");
}

// The classifier: raw message => kind, sentence (HTML, already escaped), server name, server id.
// kinds: outage down up config restart repair sync cert dns error info
function hae_classify(string $msg,array $backends):array{
	$ev=array("kind"=>"info","sentence"=>"","server"=>"","sid"=>"");
	$srv=function($id)use($backends,&$ev){
		$id=intval($id);
		$ev["sid"]="proxy$id";
		$ev["server"]=isset($backends[$id])?$backends[$id]:"proxy$id";
	};
	if(stripos($msg,"no server available")!==false){
		$ev["kind"]="outage";$ev["sentence"]=hae_txt("hae_s_outage");return $ev;
	}
	if(preg_match('#backend (.+?) \(([^)]*)\) agent transition: "([^"]*)" -> "([^"]*)"#i',$msg,$re)){
		$ev["server"]=$re[1];$ev["sid"]=$re[2];
		$to=strtolower($re[4]);
		if(strpos($to,"up")!==false || $to==="ready"){$ev["kind"]="up";$ev["sentence"]=hae_txt("hae_s_agent_up");return $ev;}
		if(strpos($to,"down")!==false || strpos($to,"fail")!==false){$ev["kind"]="down";$ev["sentence"]=hae_txt("hae_s_agent_down");return $ev;}
		$ev["sentence"]=str_replace("%S%",hae_e($re[4]),hae_txt("hae_s_agent_other"));
		return $ev;
	}
	$down=false;
	if(preg_match('#server [A-Za-z]+/[A-Za-z]+?([0-9]+) is DOWN#i',$msg,$re)){$srv($re[1]);$down=true;}
	elseif(preg_match('#for server [A-Za-z]+/[A-Za-z]+?([0-9]+) failed#i',$msg,$re)){$srv($re[1]);$down=true;}
	if($down){
		$ev["kind"]="down";
		if(stripos($msg,"Connection refused")!==false){$ev["sentence"]=hae_txt("hae_s_refused");}
		elseif(preg_match('#wrong status, code: ([0-9]{3})#i',$msg,$c)){$ev["sentence"]=str_replace("%CODE%",$c[1],hae_txt("hae_s_http"));}
		elseif(stripos($msg,"timeout")!==false || stripos($msg,"timed out")!==false){$ev["sentence"]=hae_txt("hae_s_timeout");}
		else{$ev["sentence"]=hae_txt("hae_s_down");}
		return $ev;
	}
	if(preg_match('#server [A-Za-z]+/[A-Za-z]+?([0-9]+) is UP#i',$msg,$re) || preg_match('#for server [A-Za-z]+/[A-Za-z]+?([0-9]+) succeeded#i',$msg,$re)){
		$srv($re[1]);$ev["kind"]="up";$ev["sentence"]=hae_txt("hae_s_up");return $ev;
	}
	if(strpos($msg,"[WATCHDOG]")!==false){
		$ev["kind"]="repair";$ev["sentence"]=hae_txt("hae_s_repair");return $ev;
	}
	if(strpos($msg,"[CERTIFICATE]")!==false){
		$ev["kind"]="cert";$ev["sentence"]=hae_e(hae_clean($msg));return $ev;
	}
	if(strpos($msg,"[RESTART]")!==false){
		$ev["kind"]="restart";$ev["sentence"]=hae_txt("hae_s_restarted");return $ev;
	}
	if(preg_match('#SIGHUP|\[RECONFIGURE\]|reloading|Re-reading configuration#i',$msg)){
		$ev["kind"]="config";$ev["sentence"]=hae_txt("hae_s_config");return $ev;
	}
	if(strpos($msg,"[STOPPING]")!==false || preg_match('#\bstopp(ed|ing)\b#i',$msg)){
		$ev["kind"]="config";$ev["sentence"]=hae_txt("hae_s_stopped");return $ev;
	}
	if(strpos($msg,"[STARTING]")!==false || preg_match('#\bstarted\b#i',$msg)){
		$ev["kind"]="config";$ev["sentence"]=hae_txt("hae_s_started");return $ev;
	}
	if(strpos($msg,"ReplicMaster")!==false){$ev["kind"]="sync";$ev["sentence"]=hae_txt("hae_s_sync");return $ev;}
	if(strpos($msg,"PushCommands")!==false){$ev["kind"]="sync";$ev["sentence"]=hae_txt("hae_s_push");return $ev;}
	if(strpos($msg,"Cluster package")!==false){$ev["kind"]="sync";$ev["sentence"]=hae_txt("hae_s_package");return $ev;}
	if(strpos($msg,"HaClusterDNS")!==false){$ev["kind"]="dns";$ev["sentence"]=hae_e(hae_clean($msg));return $ev;}
	if(preg_match('#error|unable to post#i',$msg)){$ev["kind"]="error";$ev["sentence"]=hae_e(hae_clean($msg));return $ev;}
	$ev["sentence"]=hae_e(hae_clean($msg));
	if($ev["sentence"]===""){$ev["sentence"]=hae_e($msg);}
	return $ev;
}

// Filter groups of the segmented control.
function hae_groups():array{
	return array(
		"problems"=>array("outage","down","error","repair"),
		"servers"=>array("down","up"),
		"config"=>array("config","restart"),
		"cluster"=>array("sync","cert","dns"),
	);
}

// The model: one call to the daemon, every line classified once.
function hae_model(int $max,string $term):array{
	$m=array("error"=>"","events"=>array(),"counts"=>array(),"max"=>$max,"term"=>$term,"raw_lines"=>0);
	$backends=hae_backends();
	$pattern=hae_pattern($term,$backends);
	$raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/events/$max/".rawurlencode($pattern));
	$json=json_decode($raw,true);
	if(!is_array($json)){$m["error"]=substr(trim(strip_tags($raw)),0,300);return $m;}
	if(empty($json["Status"])){$m["error"]=substr((string)($json["Error"] ?? ""),0,300);return $m;}
	if(!isset($json["Logs"]) || !is_array($json["Logs"])){$json["Logs"]=array();}
	foreach(array("outage","down","up","config","restart","repair","sync","cert","dns","error","info") as $k){$m["counts"][$k]=0;}
	foreach($json["Logs"] as $line){
		$line=trim((string)$line);
		if($line===""){continue;}
		if(!preg_match('#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+(.+?)\s+.*?\[([0-9]+)\]:(.+)#',$line,$re)){continue;}
		$m["raw_lines"]++;
		$msg=trim($re[6]);
		$ev=hae_classify($msg,$backends);
		$ev["ts"]=hae_time($re[1],$re[2],$re[3]);
		$ev["pid"]=$re[5];
		$ev["raw"]=$msg;
		$m["events"][]=$ev;
		$m["counts"][$ev["kind"]]++;
	}
	return $m;
}

function hae_style():string{
	if(isset($GLOBALS["HAE_STYLE_DONE"])){return "";}
	$GLOBALS["HAE_STYLE_DONE"]=true;
	$accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		$accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
	}
	$c=array();
	$c[]=".hae{display:flex;flex-direction:column;gap:20px;padding:6px 2px 20px;color:#0f172a;font-size:14px}";
	$c[]=".hae .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}";
	$c[]=".hae-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
	$c[]=".hae-ban{display:flex;gap:20px;padding:24px 28px;border-left:4px solid $accent}";
	$c[]=".hae-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:22px}";
	$c[]=".hae-ban h2{margin:0 0 6px;font-size:20px;font-weight:600;color:#0f172a}";
	$c[]=".hae-ban p{margin:0;line-height:1.6;color:#475569;max-width:1100px}";
	$c[]=".hae-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
	$c[]=".hae-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155;font-size:13px}";
	$c[]=".hae-tools{display:flex;flex-wrap:wrap;align-items:flex-start;gap:16px 32px;padding:16px 20px}";
	$c[]=".hae-search{position:relative;flex:1 1 420px;max-width:620px}";
	$c[]=".hae-search>svg,.hae-search>i{position:absolute;left:14px;top:13px;color:#94a3b8}";
	$c[]=".hae-search input{width:100%;height:42px;padding:0 14px 0 40px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;color:#0f172a;background:#fff}";
	$c[]=".hae-search input:focus,.hae-load select:focus{outline:2px solid $accent;outline-offset:1px;border-color:$accent}";
	$c[]=".hae-help{margin-top:6px;font-size:13px;color:#64748b}";
	$c[]=".hae-load{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px}";
	$c[]=".hae-load label{margin:0;font-weight:600;font-size:13px;color:#334155}";
	$c[]=".hae-load select{height:42px;padding:0 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#fff;color:#0f172a}";
	$c[]=".hae-load .hae-help{flex-basis:100%;margin-top:0}";
	$c[]=".hae-alert{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 20px;border:1px solid #fecaca;background:#fef2f2;border-radius:12px;color:#991b1b;font-weight:600}";
	$c[]=".hae-alert .i{display:inline-flex;width:32px;height:32px;border-radius:8px;background:#fee2e2;align-items:center;justify-content:center;margin-right:12px;color:#dc2626}";
	$c[]=".hae-btn-o{display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;border:1px solid #fca5a5;background:#fff;color:#b91c1c;font-weight:600;font-size:13px;cursor:pointer;white-space:nowrap}";
	$c[]=".hae-span{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;color:#475569;font-size:13px}";
	$c[]=".hae-span b{color:#0f172a}";
	$c[]=".hae-bars{display:inline-flex;align-items:center;gap:10px;padding:6px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;font-size:13px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.04em}";
	$c[]=".hae-bars svg{display:block}";
	$c[]=".hae-tiles{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}";
	$c[]=".hae-tile{display:flex;flex-direction:column;gap:6px;padding:16px 18px;text-align:left;cursor:pointer;font:inherit;color:inherit}";
	$c[]=".hae-tile:hover{border-color:#cbd5e1}.hae-tile:focus-visible{outline:2px solid $accent;outline-offset:2px}";
	$c[]=".hae-tile-h{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.04em}";
	$c[]=".hae-tile b{font-size:26px;font-weight:600;line-height:1.1;color:#0f172a}";
	$c[]=".hae-tile b.zero{color:#94a3b8}";
	$c[]=".hae-tile p{margin:0;font-size:13px;color:#64748b;line-height:1.4}";
	$c[]=".hae-tile.red{border-color:#fecaca;background:#fffafa}.hae-tile.red b,.hae-tile.red .hae-tile-h svg{color:#b91c1c}";
	$c[]=".hae-tile.green b,.hae-tile.green .hae-tile-h svg{color:#047857}";
	$c[]=".hae-tile.amber b,.hae-tile.amber .hae-tile-h svg{color:#b45309}";
	$c[]=".hae-seg{display:inline-flex;flex-wrap:wrap;gap:4px;padding:4px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc}";
	$c[]=".hae-seg button{border:0;background:none;padding:6px 12px;border-radius:7px;font-size:13px;font-weight:600;color:#475569;cursor:pointer}";
	$c[]=".hae-seg button[aria-pressed=true]{background:#fff;color:#0f172a;box-shadow:0 1px 2px rgba(15,23,42,.12)}";
	$c[]=".hae-seg button:focus-visible{outline:2px solid $accent}";
	$c[]=".hae-tblh{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;border-bottom:1px solid #e2e8f0}";
	$c[]=".hae-tblh span{font-size:13px;color:#475569}";
	$c[]=".hae-tblw{overflow-x:auto}";
	$c[]=".hae-tbl{width:100%;border-collapse:collapse;table-layout:fixed}";
	$c[]=".hae-tbl th{text-align:left;padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;color:#475569;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}";
	$c[]=".hae-tbl th .q{margin-left:4px;color:#94a3b8;cursor:help}";
	$c[]=".hae-tbl td{padding:14px 18px;border-top:1px solid #f1f5f9;vertical-align:top}";
	$c[]=".hae-tbl tr.bad td:first-child{box-shadow:inset 3px 0 0 #dc2626}";
	$c[]=".hae-tbl tr.out td{background:#fff5f5}";
	$c[]=".hae-tbl tr.day td{background:#f8fafc;padding:8px 18px;font-size:13px;font-weight:600;color:#334155}";
	$c[]=".hae-when b{display:block;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#0f172a}";
	$c[]=".hae-when span{font-size:13px;color:#64748b}";
	$c[]=".hae-what-h{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-bottom:6px}";
	$c[]=".hae-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;border:1px solid;font-size:13px;font-weight:600;white-space:nowrap}";
	$c[]=".hae-pill.outage{background:#b91c1c;border-color:#b91c1c;color:#fff}";
	$c[]=".hae-pill.down,.hae-pill.error{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
	$c[]=".hae-pill.up{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
	$c[]=".hae-pill.config,.hae-pill.dns,.hae-pill.info{background:#f1f5f9;border-color:#e2e8f0;color:#334155}";
	$c[]=".hae-pill.restart,.hae-pill.repair,.hae-pill.cert{background:#fffbeb;border-color:#fde68a;color:#b45309}";
	$c[]=".hae-pill.sync{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}";
	$c[]=".hae-srv{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#0f172a}";
	$c[]=".hae-srv small{font-size:13px;color:#94a3b8}";
	$c[]=".hae-sent{color:#0f172a;line-height:1.5;overflow-wrap:anywhere}";
	$c[]=".hae-raw summary{cursor:pointer;color:$dark;font-size:13px;font-weight:600;list-style:none}";
	$c[]=".hae-raw summary::-webkit-details-marker{display:none}";
	$c[]=".hae-raw summary:focus-visible{outline:2px solid $accent;outline-offset:2px}";
	$c[]=".hae-raw-t{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;font-weight:400;color:#64748b;margin-bottom:4px}";
	$c[]=".hae-raw[open] .hae-raw-t{white-space:pre-wrap;overflow-wrap:anywhere}";
	$c[]=".hae-raw-p{margin-top:4px;font-size:13px;color:#64748b}";
	$c[]=".hae-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 18px;border-top:1px solid #e2e8f0;font-size:13px;color:#475569}";
	$c[]="#hae-pager a{display:inline-block;min-width:32px;margin-left:4px;padding:5px 10px;border:1px solid #e2e8f0;border-radius:6px;text-align:center;color:#334155;text-decoration:none}";
	$c[]="#hae-pager a.awc-pg-cur{background:$soft;border-color:$accent;color:#0f172a;font-weight:600}";
	$c[]="#hae-pager a.awc-pg-off{opacity:.45;pointer-events:none}";
	$c[]=".hae-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:44px 24px}";
	$c[]=".hae-empty-i{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;background:#f1f5f9;color:#64748b;margin-bottom:6px}";
	$c[]=".hae-empty-i.ok{background:#ecfdf5;color:#059669}";
	$c[]=".hae-empty h3{margin:0;font-size:18px;font-weight:600}.hae-empty p{margin:0;max-width:620px;color:#475569;line-height:1.6}";
	$c[]=".hae-acts{display:flex;gap:10px;margin-top:10px}";
	$c[]=".hae-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:8px;border:1px solid $accent;background:$accent;color:#fff;font-weight:600;font-size:14px;cursor:pointer}";
	$c[]=".hae-btn.ghost{background:#fff;color:#0f172a;border-color:#cbd5e1}";
	$c[]=".hae-btn:focus-visible,.hae-btn-o:focus-visible{outline:2px solid $accent;outline-offset:2px}";
	$c[]="@media (max-width:1200px){.hae-tiles{grid-template-columns:repeat(3,minmax(0,1fr))}}";
	return "<style>".implode("\n",$c)."</style>";
}

// 1. Value banner.
function hae_banner():string{
	$chips=array(
		array("fa-eye","{hae_chip_ro}"),
		array("fa-arrow-down-wide-short","{hae_chip_newest}"),
		array("fa-shield-heart","{hae_chip_aside}"),
		array("fa-magnifying-glass","{hae_chip_search}"),
	);
	$h="";
	foreach($chips as $c){$h.="<span class='hae-chip'><i class='fas {$c[0]}' aria-hidden='true'></i>{$c[1]}</span>";}
	return "<section class='hae-card hae-ban'><div class='hae-ban-ico'><i class='fas fa-server' aria-hidden='true'></i></div>"
		."<div><h2>{hae_banner_title}</h2><p>{hae_banner_text}</p><div class='hae-chips'>$h</div></div></section>";
}

// 2. Alert, only when a full outage is among the loaded events (the most recent one).
function hae_alert(array $m):string{
	foreach($m["events"] as $ev){
		if($ev["kind"]!=="outage"){continue;}
		$when=$ev["ts"]>0?hae_clock($ev["ts"]):"";
		$t=str_replace("%T%",$when,hae_txt("hae_alert_outage"));
		return "<div class='hae-alert' role='alert'><span><span class='i'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></span>$t</span>"
			."<button type='button' class='hae-btn-o' OnClick=\"HaeKind('problems')\">{hae_show_problems} <span aria-hidden='true'>&rarr;</span></button></div>";
	}
	return "";
}

// "10:02" today, "Nov 17, 22:53" otherwise.
function hae_clock(int $ts):string{
	return date("Y-m-d",$ts)===date("Y-m-d")?date("H:i",$ts):date("M j, H:i",$ts);
}

function hae_duration(int $sec):string{
	if($sec<60){return "<1 min";}
	$d=intval($sec/86400);$h=intval(($sec%86400)/3600);$mi=intval(($sec%3600)/60);
	if($d>0){return $h>0?"$d d $h h":"$d d";}
	if($h>0){return "$h h $mi min";}
	return "$mi min";
}

function hae_age(int $ts):string{
	$d=time()-$ts;
	if($d<60){return "<1 min";}
	if($d<3600){return intval($d/60)." min";}
	if($d<86400){return intval($d/3600)." h";}
	return intval($d/86400)." d";
}

// 3a. Caption of the summary: on what the numbers are computed.
function hae_span(array $m):string{
	$n=count($m["events"]);
	$min=0;$max=0;
	foreach($m["events"] as $ev){
		if($ev["ts"]<=0){continue;}
		if($min==0 || $ev["ts"]<$min){$min=$ev["ts"];}
		if($ev["ts"]>$max){$max=$ev["ts"];}
	}
	$t=str_replace("%N%","<b>$n</b>",hae_txt($n==1?"hae_span_1":"hae_span_n"));
	if($min>0){
		$t.=" &mdash; ".str_replace(array("%A%","%B%","%D%"),array(date("M j, H:i",$min),hae_clock($max),hae_duration($max-$min)),hae_txt("hae_span_from"));
	}
	return "<div class='hae-span'><span><i class='fas fa-clock' aria-hidden='true'></i> $t</span>".hae_bars($m["events"],$min,$max)."</div>";
}

// 3b. Problems per hour over the loaded time span (last 24 hours of it at most), inline SVG bars.
function hae_bars(array $events,int $min,int $max):string{
	if($min<=0 || $max<=$min){return "";}
	$problem=array("outage"=>1,"down"=>1,"error"=>1,"repair"=>1);
	$last=intval(floor($max/3600));
	$first=max(intval(floor($min/3600)),$last-23);
	$n=$last-$first+1;
	if($n<2){return "";}
	$b=array_fill(0,$n,0);
	foreach($events as $ev){
		if($ev["ts"]<=0 || !isset($problem[$ev["kind"]])){continue;}
		$i=intval(floor($ev["ts"]/3600))-$first;
		if($i>=0 && $i<$n){$b[$i]++;}
	}
	$top=max(1,max($b));
	$w=6;$gap=3;$H=24;
	$svg="";
	for($i=0;$i<$n;$i++){
		$h=$b[$i]>0?max(3,intval(round($b[$i]*$H/$top))):2;
		$col=$b[$i]>0?"#ef4444":"#e2e8f0";
		$x=$i*($w+$gap);
		$tt=hae_attr(date("H:00",($first+$i)*3600)." — ".$b[$i]);
		$svg.="<rect x='$x' y='".($H-$h)."' width='$w' height='$h' rx='1' fill='$col'><title>$tt</title></rect>";
	}
	$width=$n*($w+$gap)-$gap;
	$label=hae_attr(hae_txt("hae_per_hour"));
	return "<span class='hae-bars'>{hae_per_hour}<svg width='$width' height='$H' viewBox='0 0 $width $H' role='img' aria-label=\"$label\">$svg</svg></span>";
}

// 3c. One summary tile; a click filters the timeline.
function hae_tile(string $label,string $help,string $ico,int $n,string $tone,string $kind):string{
	$cls=$n>0?" $tone":"";
	$zero=$n==0?" class='zero'":"";
	return "<button type='button' class='hae-card hae-tile$cls' OnClick=\"HaeKind('$kind')\"><span class='hae-tile-h'><span>$label</span><i class='fas $ico' aria-hidden='true'></i></span>"
		."<b$zero>$n</b><p>$help</p></button>";
}

function hae_tiles(array $c):string{
	$h="<div class='hae-tiles'>";
	$h.=hae_tile("{hae_t_outage}","{hae_t_outage_help}","fa-circle-xmark",$c["outage"],"red","problems");
	$h.=hae_tile("{hae_t_down}","{hae_t_down_help}","fa-arrow-down",$c["down"],"red","servers");
	$h.=hae_tile("{hae_t_up}","{hae_t_up_help}","fa-circle-check",$c["up"],"green","servers");
	$h.=hae_tile("{hae_t_config}","{hae_t_config_help}","fa-sliders",$c["config"]+$c["restart"],"","config");
	$h.=hae_tile("{hae_t_repair}","{hae_t_repair_help}","fa-wrench",$c["repair"],"amber","problems");
	return $h."</div>";
}

function hae_group_count(array $c,string $g):int{
	if($g==="all"){return array_sum($c);}
	$n=0;
	$groups=hae_groups();
	foreach($groups[$g] as $k){$n+=intval($c[$k] ?? 0);}
	return $n;
}

// 4. Filter by kind, with the count of each group in the loaded events.
function hae_segment(array $c,string $kind):string{
	$labels=array("all"=>"hae_f_all","problems"=>"hae_f_problems","servers"=>"hae_f_servers","config"=>"hae_f_config","cluster"=>"hae_f_cluster");
	$h="<div class='hae-seg' role='group' aria-label=\"".hae_attr(hae_txt("hae_f_label"))."\">";
	foreach($labels as $k=>$tok){
		$on=$k===$kind?"true":"false";
		$h.="<button type='button' aria-pressed='$on' OnClick=\"HaeKind('$k')\">{".$tok."} (".hae_group_count($c,$k).")</button>";
	}
	return $h."</div>";
}

function hae_kind_pill(string $kind):string{
	$map=array(
		"outage"=>array("fa-circle-xmark","hae_k_outage"),
		"down"=>array("fa-arrow-down","hae_k_down"),
		"up"=>array("fa-arrow-up","hae_k_up"),
		"config"=>array("fa-sliders","hae_k_config"),
		"restart"=>array("fa-rotate","hae_k_restart"),
		"repair"=>array("fa-wrench","hae_k_repair"),
		"sync"=>array("fa-arrows-rotate","hae_k_sync"),
		"cert"=>array("fa-certificate","hae_k_cert"),
		"dns"=>array("fa-globe","hae_k_dns"),
		"error"=>array("fa-bug","hae_k_error"),
		"info"=>array("fa-circle-info","hae_k_info"),
	);
	$x=isset($map[$kind])?$map[$kind]:$map["info"];
	return "<span class='hae-pill $kind'><i class='fas {$x[0]}' aria-hidden='true'></i>{".$x[1]."}</span>";
}

function hae_day_label(int $ts):string{
	$d=date("Y-m-d",$ts);
	if($d===date("Y-m-d")){return hae_txt("hae_today")." &mdash; ".date("l, M j",$ts);}
	if($d===date("Y-m-d",time()-86400)){return hae_txt("hae_yesterday")." &mdash; ".date("l, M j",$ts);}
	return date("l, M j",$ts);
}

function hae_row(array $ev):string{
	$kind=$ev["kind"];
	$cls=$kind==="outage"?"bad out":(($kind==="down" || $kind==="error")?"bad":"");
	$ts=intval($ev["ts"]);
	$when=$ts>0?"<b title='".date("Y-m-d H:i:s",$ts)."'>".date("H:i:s",$ts)."</b><span>".str_replace("%AGE%",hae_age($ts),hae_txt("hae_ago"))."</span>":"<b>&mdash;</b>";
	$head=hae_kind_pill($kind);
	if($ev["server"]!==""){
		$sid=($ev["sid"]!=="" && $ev["sid"]!==$ev["server"])?" <small>".hae_e($ev["sid"])."</small>":"";
		$head.="<span class='hae-srv'><i class='fas fa-server' aria-hidden='true'></i>".hae_e($ev["server"])."$sid</span>";
	}
	$raw=hae_e($ev["raw"]);
	$pid=str_replace("%PID%",hae_e($ev["pid"]),hae_txt("hae_process"));
	return "<tr class='$cls'><td class='hae-when'>$when</td>"
		."<td><div class='hae-what-h'>$head</div><div class='hae-sent'>{$ev["sentence"]}</div></td>"
		."<td><details class='hae-raw'><summary><span class='hae-raw-t'>$raw</span>{hae_show_raw}</summary>"
		."<div class='hae-raw-p'>$pid</div></details></td></tr>";
}

function hae_th(string $label,string $help,string $w):string{
	return "<th scope='col' style='width:$w' title=\"".hae_attr(hae_txt($help))."\">{".$label."}<i class='fas fa-circle-question q' aria-hidden='true'></i></th>";
}

// 5. Timeline: rows of the selected kind, grouped by day, 50 rows per page in the browser.
function hae_table(array $events,array $m,string $kind):string{
	$h=array();
	$h[]="<div class='hae-card'><div class='hae-tblh'>".hae_segment($m["counts"],$kind)."</div>";
	$h[]="<div class='hae-tblw'><table class='hae-tbl'><thead><tr>"
		.hae_th("hae_col_when","hae_col_when_help","150px")
		.hae_th("hae_col_what","hae_col_what_help","46%")
		.hae_th("hae_col_details","hae_col_details_help","auto")
		."</tr></thead><tbody id='hae-rows'>";
	$day="";
	foreach($events as $ev){
		$d=$ev["ts"]>0?date("Y-m-d",$ev["ts"]):"";
		if($d!=="" && $d!==$day){
			$day=$d;
			$h[]="<tr class='day'><td colspan='3'><i class='fas fa-calendar' aria-hidden='true'></i> ".hae_day_label($ev["ts"])."</td></tr>";
		}
		$h[]=hae_row($ev);
	}
	$h[]="</tbody></table></div>";
	$shown=str_replace(array("%N%","%T%"),array((string)count($events),(string)count($m["events"])),hae_txt("hae_showing"));
	$h[]="<div class='hae-foot'><span>$shown</span><span id='hae-pager'></span></div></div>";
	return implode("",$h);
}

// 6. Empty states: an invitation with the next action.
function hae_empty(string $icls,string $ico,string $title,string $text,string $acts):string{
	return "<div class='hae-card hae-empty'><div class='hae-empty-i $icls'><i class='fas $ico' aria-hidden='true'></i></div>"
		."<h3>$title</h3>".($text!==""?"<p>$text</p>":"").($acts!==""?"<div class='hae-acts'>$acts</div>":"")."</div>";
}

function hae_empty_state(array $m,string $kind):string{
	if(count($m["events"])>0 && $kind!=="all"){
		$box=hae_empty("ok","fa-circle-check","{hae_empty_kind_title}","",
			"<button type='button' class='hae-btn' OnClick=\"HaeKind('all')\">{hae_show_all}</button>");
		return "<div class='hae-card'><div class='hae-tblh'>".hae_segment($m["counts"],$kind)."</div></div>".$box;
	}
	if($m["term"]!==""){
		$t=str_replace("%S%",hae_e($m["term"]),hae_txt("hae_empty_search_title"));
		$acts="<button type='button' class='hae-btn' OnClick=\"HaeClear()\">{hae_clear_search}</button>";
		if($m["max"]<1000){$acts.="<button type='button' class='hae-btn ghost' OnClick=\"HaeLoad(1000)\">{hae_load_1000}</button>";}
		return hae_empty("","fa-magnifying-glass",$t,"{hae_empty_search_text}",$acts);
	}
	return hae_empty("","fa-scroll","{hae_empty_title}","{hae_empty_text}","");
}

function hae_body(array $m,string $kind):string{
	$events=array();
	if($kind==="all"){$events=$m["events"];}
	else{
		$groups=hae_groups();
		$keep=array_flip($groups[$kind]);
		foreach($m["events"] as $ev){if(isset($keep[$ev["kind"]])){$events[]=$ev;}}
	}
	$h=array();
	$h[]=hae_alert($m);
	if(count($m["events"])>0){
		$h[]=hae_span($m);
		$h[]=hae_tiles($m["counts"]);
	}
	$h[]=count($events)==0?hae_empty_state($m,$kind):hae_table($events,$m,$kind);
	return implode("\n",$h);
}

// Responder of the results host.
function results():bool{
	$tpl=new template_admin();
	$kind=hae_kind();
	$m=hae_model(hae_max(),hae_term());
	if($m["error"]!==""){
		$html="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span class='ak-banner-m'>{hae_err_read}<br>".hae_e($m["error"])."</span></div>";
		echo hae_style().$tpl->_ENGINE_parse_body($html)."<script>NoSpinner();</script>";
		return false;
	}
	echo hae_style()."<div class='hae' style='padding-top:0'>".$tpl->_ENGINE_parse_body(hae_body($m,$kind))."</div>"
		.awc_pager_js(50,"hae-rows","hae-pager")."<script>NoSpinner();</script>";
	return true;
}
