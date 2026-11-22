<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["flat"])){redis_flat();exit;}
if(isset($_GET["settings-js"])){redis_settings_js();exit;}
if(isset($_GET["settings-popup"])){redis_settings_popup();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["metrics"])){redisx_metrics();exit;}
if(isset($_POST["RedisBindInterface"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){search();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");


    $html=$tpl->page_header("{APP_REDIS_SERVER} v{$APP_REDIS_SERVER_VERSION}",
        ico_database
        ,"{APP_REDIS_SERVER_TEXT}"
        ,"$page?tabs=yes"
        ,"redis-database",
        "progress-redis-restart",
        false,
        "table-loader-redis-service"
    );
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_REDIS_SERVER}",$html);
		echo $tpl->build_firewall();
		return;
	}
	

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]="<table style='width:100%;margin-top:20px'>";
	$html[]="<tr>";
	$html[]="<td style='width:350px;vertical-align:top'>";
	$html[]="<div id='redis-status'></div>";
	$html[]="</td>";
	$html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";
	$html[]="<div id='redis-metrics' class='ak'></div>";
	$html[]="<div id='redis-flat' style='margin-top:24px'></div>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>";
	$html[]="LoadAjaxTiny('redis-status','$page?status=yes');";
	$html[]=$tpl->RefreshInterval_js("redis-status",$page,"status=yes",5);
	$html[]="LoadAjaxSilent('redis-flat','$page?flat=yes');";
	$html[]=redis_tiny();
	$html[]="</script>";
	echo redisx_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).redisx_js();
}

// Header: emitted once by the tab, never by the refreshed status widget.
function redis_tiny():string{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
	$refresh="LoadAjaxTiny('redis-status','$page?status=yes');LoadAjaxSilent('redis-flat','$page?flat=yes');if(window.RedisxReload){RedisxReload();}";
	$topbuttons=array();
	$topbuttons[]=array("Loadjs('$page?settings-js=yes')",ico_params,"{redis_general_settings}");
	$topbuttons[]=array($refresh,ico_refresh,"{refresh}");
	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{APP_REDIS_SERVER} v{$APP_REDIS_SERVER_VERSION}";
	$TINY_ARRAY["ICO"]=ico_database;
	$TINY_ARRAY["EXPL"]="{APP_REDIS_SERVER_TEXT}";
	$TINY_ARRAY["URL"]="redis-database";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

// Current settings with their defaults, read in one place for the card and the dialog.
function redis_settings():array{
	$s=array();
	$s["RedisBindInterface"]=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindInterface"));
	$s["RedisBindPort"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindPort"));
	$s["RedisMaxDB"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisMaxDB"));
	$s["RedisMaxmemory"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisMaxmemory"));
	$s["RedisPassword"]=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisPassword"));
	if($s["RedisBindInterface"]==""){$s["RedisBindInterface"]="lo";}
	if($s["RedisBindPort"]==0){$s["RedisBindPort"]=6379;}
	if($s["RedisMaxDB"]==0){$s["RedisMaxDB"]=16;}
	if($s["RedisMaxmemory"]==0){$s["RedisMaxmemory"]=500;}
	return $s;
}

// Restart with progress bar, then refresh the status widget and the card.
function redis_restart_js():string{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$after="LoadAjaxTiny('redis-status','$page?status=yes');LoadAjaxSilent('redis-flat','$page?flat=yes');";
	return $tpl->framework_buildjs("/redis/restart","redis.restart.progress","redis.restart.progress.logs",
		"progress-redis-restart",$after,$after);
}

// The parameters card (flat convention); every row opens the same dialog.
function redis_flat(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$s=redis_settings();
	$iface=$s["RedisBindInterface"];
	if($iface=="lo"){$iface="Loopback (127.0.0.1)";}
	$js="Loadjs('$page?settings-js=yes')";
	$tpl->table_form_section("{redis_general_settings}","{redisx_settings_hint}");
	$tpl->table_form_field_js($js,"AsSystemAdministrator");
	$tpl->table_form_field_text("{listen_interface}",htmlspecialchars($iface),ico_nic);
	$tpl->table_form_field_text("{redis_listen_port}",$s["RedisBindPort"],ico_plug);
	$tpl->table_form_field_text("{max_databases}",$s["RedisMaxDB"],ico_database);
	$tpl->table_form_field_text("{redisx_max_memory}",$s["RedisMaxmemory"]." MB",ico_mem);
	$warn=redis_running_limit_warning($s);
	if($warn<>""){$tpl->table_form_field_warn($warn);}
	// Never displayed: only whether one is set.
	$tpl->table_form_field_text("{password}",strlen($s["RedisPassword"])>0?"{redisx_pwd_set}":"{redisx_pwd_not_set}",ico_lock);
	echo $tpl->table_form_compile(2);
}

// The limit the RUNNING server applies (INFO maxmemory) versus the saved setting:
// they differ until the service is restarted. Empty when they match or when the
// server cannot be read (no guess).
function redis_running_limit_warning(array $s):string{
	$m=redisx_model();
	if($m["state"]<>"ok" || !isset($m["info"]["maxmemory"])){return "";}
	$running=floatval($m["info"]["maxmemory"]);
	$wanted=floatval($s["RedisMaxmemory"])*1048576;
	if(abs($running-$wanted)<1048576){return "";}
	$txt=$running>0?redisx_bytes($running):redisx_tr("redisx_no_limit",array());
	return redisx_tr("redisx_running_limit",array("%s"=>$txt));
}

function redis_settings_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{redis_general_settings}","$page?settings-popup=yes",650);
}

function redis_settings_popup(){
	$tpl=new template_admin();
	$s=redis_settings();
	$form[]=$tpl->field_interfaces("RedisBindInterface","{listen_interface}",$s["RedisBindInterface"]);
	$form[]=$tpl->field_numeric("RedisBindPort","{redis_listen_port}",$s["RedisBindPort"]);
	$form[]=$tpl->field_numeric("RedisMaxDB","{max_databases}",$s["RedisMaxDB"]);
	$form[]=$tpl->field_numeric("RedisMaxmemory","{redis_max_memory}",$s["RedisMaxmemory"]);
	// The stored password is never sent to the browser: empty = unchanged.
	$form[]=$tpl->field_password2("RedisPassword","{password}","",false,"{redis_password_keep}");
	if(strlen($s["RedisPassword"])>0){
		$form[]=$tpl->field_checkbox("RedisPasswordClear","{redis_password_clear}",0);
	}
	$html[]=$tpl->div_explain("{redis_general_settings}||{redis_settings_explain}");
	$html[]=$tpl->form_outside("",@implode("\n",$form),"","{apply}","dialogInstance1.close();".redis_restart_js(),"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

// Saves the dialog. Echoes nothing on success (form_outside then closes and restarts).
function save(){
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
	$tpl->CLEAN_POST();
	$iface=trim((string)$_POST["RedisBindInterface"]);
	$port=intval($_POST["RedisBindPort"]);
	$maxdb=intval($_POST["RedisMaxDB"]);
	$maxmem=intval($_POST["RedisMaxmemory"]);
	if($iface!="" && !preg_match('/^[A-Za-z0-9._:-]{1,32}$/',$iface)){echo $tpl->post_error("{redis_err_iface}");return;}
	if($port<1 || $port>65535){echo $tpl->post_error("{redis_err_port}");return;}
	if($maxdb<1 || $maxdb>1024){echo $tpl->post_error("{redis_err_maxdb}");return;}
	if($maxmem<16){echo $tpl->post_error("{redis_err_maxmem}");return;}

	$sock=$GLOBALS["CLASS_SOCKETS"];
	$sock->SET_INFO("RedisBindInterface",$iface);
	$sock->SET_INFO("RedisBindPort",$port);
	$sock->SET_INFO("RedisMaxDB",$maxdb);
	$sock->SET_INFO("RedisMaxmemory",$maxmem);
	$pass=isset($_POST["RedisPassword"])?trim((string)$_POST["RedisPassword"]):"";
	$what="";
	if(intval(isset($_POST["RedisPasswordClear"])?$_POST["RedisPasswordClear"]:0)==1){
		$sock->SET_INFO("RedisPassword","");
		$what=", password removed";
	}elseif($pass!=""){
		$sock->SET_INFO("RedisPassword",$pass);
		$what=", password changed";
	}
	admin_tracks("Redis server settings: interface=$iface port=$port databases=$maxdb max memory={$maxmem}MB$what");
}

function status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$json=json_decode($sock->REST_API("/redis/status"));
	if(!is_object($json) || !property_exists($json,"Info")){
		echo $tpl->div_error("{redisx_status_unavailable}");
		return false;
	}
	$ini=new Bs_IniHandler();
	$ini->loadString($json->Info);
	$jsrestart=$tpl->framework_buildjs(
		"/redis/restart","redis.restart.progress","redis.restart.progress.logs",
		"progress-redis-restart","LoadAjaxTiny('redis-status','$page?status=yes');LoadAjaxSilent('redis-flat','$page?flat=yes');",
		"LoadAjaxTiny('redis-status','$page?status=yes');"
	);
	echo $tpl->SERVICE_STATUS($ini, "APP_REDIS_SERVER",$jsrestart);
	return true;
}

function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $TINY_ARRAY["TITLE"]="{APP_REDIS_SERVER} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_REDIS_SERVER_TEXT}";
    $TINY_ARRAY["URL"]="redis-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page);
    echo "<script>$jstiny</script>";
}
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $data=$tpl->logfile_search("redis",$MAIN,false);

    $html[]="

<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
			<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";


    $pattern="^([0-9]+):([A-Z]+)\s+(.+?)\.[0-9]+\s+(.*?)\s+(.+)";
    foreach ($data as $line) {
        if (!preg_match("#$pattern#", $line, $re)) {
            continue;
        }
        $md = md5($line);
        $color="black";
        $pid=$re[1];
        $date=$re[3];
        $event=trim($re[5]);
        $html[]="<tr id='$md'>
				<td style='color:$color;width:1%' nowrap>$date</td>
				<td style='color:$color;width:1%' nowrap>$pid</td>
				<td style='color:$color;width:99%'>$event</span></td>
                </tr>";

    }

    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body($html);

}


// =====================================================================
// Health metrics (Stitch "redis service health dashboard"): one INFO read
// per refresh, every widget reads the same model. INFO is a snapshot plus
// counters since start: no history, so no curve.
// =====================================================================

// redisx_model: INFO of the local server through its Unix socket.
// state: ok | noext (PHP extension missing) | down (no answer).
function redisx_model():array{
	$m=array("state"=>"ok","info"=>array(),"db0"=>array("keys"=>0,"expires"=>0,"avg_ttl"=>0));
	if(!class_exists("Redis")){$m["state"]="noext";return $m;}
	$pass=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisPassword"));
	try{
		$r=new Redis();
		if(!$r->connect('/var/run/redis/redis.sock',0,2.0)){$m["state"]="down";return $m;}
		if($pass<>""){$r->auth($pass);}
		$info=$r->info();
		$r->close();
	}catch(Exception $e){
		$m["state"]="down";
		return $m;
	}
	if(!is_array($info) || count($info)==0){$m["state"]="down";return $m;}
	$m["info"]=$info;
	// db0 arrives as "keys=173299,expires=173298,avg_ttl=73730278,…"
	if(isset($info["db0"])){
		$raw=is_array($info["db0"])?$info["db0"]:array();
		if(!is_array($info["db0"])){
			foreach(explode(",",(string)$info["db0"]) as $kv){
				$t=explode("=",$kv,2);
				if(count($t)==2){$raw[$t[0]]=$t[1];}
			}
		}
		foreach(array("keys","expires","avg_ttl") as $k){
			if(isset($raw[$k])){$m["db0"][$k]=intval($raw[$k]);}
		}
	}
	return $m;
}
// Translate a token THEN fill its placeholders (the placeholders only exist
// once the token is resolved).
function redisx_tr(string $tok,array $rep):string{
	if(!isset($GLOBALS["REDISX_TPL"])){$GLOBALS["REDISX_TPL"]=new template_admin();}
	$txt=$GLOBALS["REDISX_TPL"]->_ENGINE_parse_body("{".$tok."}");
	return str_replace(array_keys($rep),array_values($rep),$txt);
}
function redisx_i(array $m,string $k):float{
	return isset($m["info"][$k])?floatval($m["info"][$k]):0.0;
}
function redisx_bytes($b):string{
	$b=floatval($b);
	if($b>=1073741824){return sprintf("%.2f GB",$b/1073741824);}
	if($b>=1048576){return str_replace(".0 MB"," MB",sprintf("%.1f MB",$b/1048576));}
	if($b>=1024){return sprintf("%.0f KB",$b/1024);}
	return sprintf("%.0f B",$b);
}
// 4927667 -> "4.9 M", 1200000000 -> "1.2 B"
function redisx_short($n):string{
	$n=floatval($n);
	if($n>=1000000000){return sprintf("%.1f B",$n/1000000000);}
	if($n>=1000000){return sprintf("%.1f M",$n/1000000);}
	if($n>=10000){return sprintf("%.1f k",$n/1000);}
	return number_format($n,0,"."," ");
}
function redisx_num($n):string{
	return number_format(floatval($n),0,"."," ");
}
function redisx_pct($a,$b):float{
	return $b>0?round($a*100/$b,1):0.0;
}
// 73730278 ms -> "20 h 28 min"
function redisx_ttl($ms):string{
	$s=intval($ms/1000);
	if($s<=0){return "-";}
	$d=intval($s/86400); $h=intval(($s%86400)/3600); $mn=intval(($s%3600)/60);
	if($d>0){return "$d d $h h";}
	if($h>0){return "$h h $mn min";}
	if($mn>0){return "$mn min";}
	return "$s s";
}
// Relative age of a save: "12 min ago" (tokens carry the %s).
function redisx_ago($ts):string{
	$ts=intval($ts);
	if($ts<=0){return "{redisx_never_saved}";}
	$age=max(0,time()-$ts);
	if($age<60){return "{redisx_just_now}";}
	if($age<3600){return redisx_tr("redisx_min_ago",array("%s"=>(string)intval($age/60)));}
	if($age<86400){return redisx_tr("redisx_h_ago",array("%s"=>(string)intval($age/3600)));}
	return redisx_tr("redisx_d_ago",array("%s"=>(string)intval($age/86400)));
}

// ?metrics=yes — the refreshed host content.
function redisx_metrics(){
	$tpl=new template_admin();
	$m=redisx_model();
	$h=array();
	if($m["state"]=="noext"){
		$h[]="<div class='ak-banner ak-warn redisx-banner'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {REDIS_PHP_EXTENSION_NOT_LOADED}</div>";
	}elseif($m["state"]=="down"){
		$h[]=redisx_down();
	}else{
		$h[]="<div class='ak-grid-4 redisx-kpis'>".redisx_kpi_memory($m).redisx_kpi_hits($m).redisx_kpi_clients($m).redisx_kpi_ops($m)."</div>";
		$h[]="<div class='ak-grid-2 redisx-cards'>".redisx_card_keys($m).redisx_card_persistence($m)."</div>";
		$h[]=redisx_footer($m);
	}
	echo $tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();if(window.RedisxDonuts){RedisxDonuts();}</script>";
}

function redisx_down():string{
	return "<div class='ak-empty redisx-down'><i class='fas fa-power-off' aria-hidden='true'></i><span>{redisx_stopped}</span></div>";
}

// Donut measured against a real ceiling; text = "value/max" for peity.
// The figure in the centre repeats the aria-label (peity hides the source span).
function redisx_donut($val,$max,$cls,$aria,$center=null):string{
	$val=max(0,min(floatval($val),floatval($max)));
	if($center===null){$center=$aria;}
	return "<span class='redisx-ring' role='img' aria-label='".htmlspecialchars($aria,ENT_QUOTES)."'>"
		."<span class='redisx-donut $cls' aria-hidden='true'>".round($val,2)."/".floatval($max)."</span>"
		."<b aria-hidden='true'>".htmlspecialchars($center)."</b></span>";
}

function redisx_kpi(string $ico,string $label,string $value,string $sub,string $donut,string $state=""):string{
	return "<div class='ak-fig redisx-kpi $state'><div class='ak-fig-txt'><div class='ak-fig-l'><i class='fas $ico' aria-hidden='true'></i> $label</div>"
		."<div class='ak-fig-v'>$value</div><div class='ak-fig-s'>$sub</div></div>$donut</div>";
}

function redisx_kpi_memory(array $m):string{
	$used=redisx_i($m,"used_memory");
	$max=redisx_i($m,"maxmemory");
	$val=redisx_bytes($used);
	$donut=""; $state="";
	if($max>0){
		$pct=redisx_pct($used,$max);
		$val.=" <small>/ ".redisx_bytes($max)."</small>";
		$cls="redisx-d-ok";
		if($pct>80){$cls="redisx-d-warn";$state="redisx-warn";}
		if($pct>95){$cls="redisx-d-err";$state="redisx-crit";}
		$donut=redisx_donut($used,$max,$cls,"$pct %",round($pct)."%");
	}
	if($state=="redisx-crit"){
		return redisx_kpi("fa-triangle-exclamation","{redisx_critical_limit}",$val,"{peak} ".redisx_bytes(redisx_i($m,"used_memory_peak")),$donut,$state);
	}
	return redisx_kpi("fa-memory","{redisx_memory}",$val,"{peak} ".redisx_bytes(redisx_i($m,"used_memory_peak")),$donut,$state);
}

function redisx_kpi_hits(array $m):string{
	$hits=redisx_i($m,"keyspace_hits");
	$miss=redisx_i($m,"keyspace_misses");
	$tot=$hits+$miss;
	if($tot<=0){
		return redisx_kpi("fa-bullseye","{redisx_hit_ratio}","-","{redisx_no_lookup}","");
	}
	$pct=redisx_pct($hits,$tot);
	// Neutral ring: a low ratio is normal for some workloads.
	return redisx_kpi("fa-bullseye","{redisx_hit_ratio}","$pct %",
		redisx_short($hits)." {redisx_hits} &middot; ".redisx_short($miss)." {redisx_misses}",
		redisx_donut($hits,$tot,"redisx-d-neutral","$pct %",round($pct)."%"));
}

function redisx_kpi_clients(array $m):string{
	$c=redisx_i($m,"connected_clients");
	$max=redisx_i($m,"maxclients");
	$rej=redisx_i($m,"rejected_connections");
	$val=redisx_num($c);
	$donut="";
	if($max>0){
		$val.=" <small>/ ".redisx_num($max)."</small>";
		$donut=redisx_donut($c,$max,"redisx-d-ok",redisx_pct($c,$max)." %",redisx_short($c));
	}
	$sub=redisx_num(redisx_i($m,"blocked_clients"))." {redisx_blocked} &middot; ";
	$sub.=$rej>0?"<span class='ak-tag ak-err'>".redisx_num($rej)." {redisx_rejected}</span>":"0 {redisx_rejected}";
	return redisx_kpi("fa-users","{redisx_clients}",$val,$sub,$donut,$rej>0?"redisx-err":"");
}

function redisx_kpi_ops(array $m):string{
	$ops=redisx_i($m,"instantaneous_ops_per_sec");
	$tot=redisx_i($m,"total_commands_processed");
	return redisx_kpi("fa-gauge-high","{redisx_operations}",redisx_num($ops)." <small>ops/s</small>",
		redisx_tr("redisx_total_commands",array("%s"=>redisx_short($tot))),
		"<span class='ak-ico ak-ok' aria-hidden='true'><i class='fas fa-bolt'></i></span>");
}

function redisx_row(string $ico,string $label,string $value):string{
	return "<div class='redisx-row'><span><i class='fas $ico' aria-hidden='true'></i>$label</span><b>$value</b></div>";
}

function redisx_card_keys(array $m):string{
	$ev=redisx_i($m,"evicted_keys");
	$evTxt=redisx_short($ev);
	if($ev>0){$evTxt="<span class='ak-tag ak-warn' title='{redisx_evicted_help}'>$evTxt</span>";}
	$policy=isset($m["info"]["maxmemory_policy"])?htmlspecialchars((string)$m["info"]["maxmemory_policy"]):"";
	$h="<section class='ak-card ak-card-pad redisx-card'><div class='redisx-card-h'><h3>{redisx_keys} <small>({redisx_db0})</small></h3>";
	if($policy<>""){$h.="<span class='ak-tag ak-off' title='{redisx_policy_help}'>$policy</span>";}
	$h.="</div><div class='ak-fig-l'>{redisx_total_keys}</div><div class='redisx-big'>".redisx_num($m["db0"]["keys"])."</div>";
	$h.="<div class='redisx-rows'>";
	$h.=redisx_row("fa-hourglass-half","{redisx_with_expiry}",redisx_num($m["db0"]["expires"]));
	$h.=redisx_row("fa-clock","{redisx_avg_ttl}",redisx_ttl($m["db0"]["avg_ttl"]));
	$h.=redisx_row("fa-broom","{redisx_expired_since}",redisx_short(redisx_i($m,"expired_keys")));
	$h.="<div class='redisx-row'><span><i class='fas fa-arrow-right-from-bracket' aria-hidden='true'></i>{redisx_evicted_since} <i class='fas fa-circle-info redisx-help' title='{redisx_evicted_help}' aria-label='{redisx_evicted_help}'></i></span><b>$evTxt</b></div>";
	return $h."</div></section>";
}

function redisx_card_persistence(array $m):string{
	$tpl=new template_admin();
	$ok=(isset($m["info"]["rdb_last_bgsave_status"])?(string)$m["info"]["rdb_last_bgsave_status"]:"ok")=="ok";
	$last=intval(redisx_i($m,"rdb_last_save_time"));
	$aof=intval(redisx_i($m,"aof_enabled"))==1;
	$frag=redisx_i($m,"mem_fragmentation_ratio");
	$h="<section class='ak-card ak-card-pad redisx-card'><div class='redisx-card-h'><h3>{redisx_persistence}</h3>";
	$h.="<span class='ak-tag ak-off'>{redisx_aof}: ".($aof?"{enabled}":"{disabled}")."</span></div>";
	$h.="<div class='ak-fig-l'>{redisx_snapshot_status}</div>";
	if($ok){
		$h.="<div class='redisx-save redisx-save-ok'><span class='redisx-save-ico'><i class='fas fa-check' aria-hidden='true'></i></span> {redisx_last_save_ok}</div>";
	}else{
		$h.="<div class='redisx-save redisx-save-err'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> {redisx_last_save_failed}</div>";
	}
	$h.="<div class='redisx-rows'>";
	$when=$last>0?"<span title='".htmlspecialchars($tpl->time_to_date($last,true),ENT_QUOTES)."'>".redisx_ago($last)."</span>":redisx_ago(0);
	$h.=redisx_row("fa-floppy-disk","{redisx_last_snapshot}",$when);
	$h.=redisx_row("fa-pen-to-square","{redisx_changes_since}",redisx_num(redisx_i($m,"rdb_changes_since_last_save")));
	$fragTxt=sprintf("%.2f",$frag);
	if($frag>1.5){$fragTxt.=" <span class='ak-tag ak-warn'>{redisx_high}</span>";}
	$h.="<div class='redisx-row'><span><i class='fas fa-puzzle-piece' aria-hidden='true'></i>{redisx_fragmentation} <i class='fas fa-circle-info redisx-help' title='{redisx_frag_help}' aria-label='{redisx_frag_help}'></i></span><b>$fragTxt</b></div>";
	return $h."</div></section>";
}

function redisx_footer(array $m):string{
	$i=$m["info"];
	$ver="";
	if(isset($i["valkey_version"])){
		$ver="Valkey ".htmlspecialchars((string)$i["valkey_version"]);
		if(isset($i["redis_version"])){$ver.=" (".redisx_tr("redisx_compatible",array("%s"=>htmlspecialchars((string)$i["redis_version"]))).")";}
	}elseif(isset($i["redis_version"])){
		$ver="Redis ".htmlspecialchars((string)$i["redis_version"]);
	}
	$parts=array();
	if($ver<>""){$parts[]=$ver;}
	if(isset($i["role"])){$parts[]=redisx_tr("redisx_role",array("%s"=>htmlspecialchars((string)$i["role"])));}
	$parts[]=redisx_tr("redisx_up_days",array("%s"=>(string)intval(redisx_i($m,"uptime_in_days"))));
	$parts[]=redisx_tr("redisx_network",array("%in"=>redisx_bytes(redisx_i($m,"total_net_input_bytes")),"%out"=>redisx_bytes(redisx_i($m,"total_net_output_bytes"))));
	return "<div class='redisx-foot'><span><span class='ak-dot ak-ok redisx-dot'></span>".implode(" &middot; ",$parts)."</span>"
		."<span><i class='fas fa-rotate' aria-hidden='true'></i> {redisx_refreshed}</span></div>";
}

// Timer (10 s, skipped while a dialog is open) + peity donuts.
function redisx_js():string{
	$page=CurrentPageName();
	$c=redisx_colors();
	$js=<<<'JS'
<script>
(function(){
 window.RedisxDonuts=function(){
  var go=function(){
   jQuery('.redisx-donut').each(function(){
    var e=jQuery(this), f=['__ACC__','#e3f0f8'];
    if(e.hasClass('redisx-d-warn')){f=['#d98324','#ffddb9'];}
    if(e.hasClass('redisx-d-err')){f=['#ba1a1a','#ffdad6'];}
    if(e.hasClass('redisx-d-neutral')){f=['#3d4947','#e3f0f8'];}
    e.peity('donut',{fill:f,radius:22,innerRadius:16});
   });
  };
  if(typeof jQuery==='undefined'){return;}
  if(jQuery.fn.peity){go();return;}
  jQuery.ajax({url:'js/jquery.peity.min.js',dataType:'script',cache:true}).done(go);
 };
 window.RedisxReload=function(){ if(document.getElementById('redis-metrics')){ LoadAjaxSilent('redis-metrics','__PAGE__?metrics=yes'); } };
 if(window.RedisxTimer){clearInterval(window.RedisxTimer);}
 window.RedisxTimer=setInterval(function(){
  if(!document.getElementById('redis-metrics')){clearInterval(window.RedisxTimer);window.RedisxTimer=null;return;}
  if(document.hidden || document.querySelector('.modal.in, .modal.show, .sweet-alert.visible')){return;}
  RedisxReload();
 },10000);
 RedisxReload();
})();
</script>
JS;
	return str_replace(array("__PAGE__","__ACC__"),array($page,$c["accent"]),$js);
}

function redisx_colors():array{
	$c=array("accent"=>"#10b981","soft"=>"rgba(16,185,129,.12)","primary"=>"#006a62");
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$p=brand_palette();
		$a=brand_ui_accents($p);
		if(!empty($a["accent"])){$c["accent"]=$a["accent"];}
		if(!empty($a["accent_soft"])){$c["soft"]=$a["accent_soft"];}
		if(!empty($p["colors_on"]) && !empty($a["accent_dark"])){$c["primary"]=$a["accent_dark"];}
	}
	return $c;
}

function redisx_style():string{
	if(isset($GLOBALS["REDISX_STYLE"])){return "";}
	$GLOBALS["REDISX_STYLE"]=true;
	$c=redisx_colors();
	$P=$c["primary"]; $S=$c["soft"];
	// Seules les regles propres a la page : le reste vient du kit ak- (angular.css.php).
	return "<style>
#redis-metrics{color:#111d23}
.redisx-kpis{margin-bottom:24px}
.redisx-kpi{min-height:112px}
.redisx-kpi.redisx-warn{border-color:#f5c48a}
.redisx-kpi.redisx-err{border-color:#f3b3ad}
.redisx-kpi.redisx-crit{background:#ffdad6;border-color:#f3b3ad}
.redisx-kpi.redisx-crit .ak-fig-l,.redisx-kpi.redisx-crit .ak-fig-v,.redisx-kpi.redisx-crit .ak-fig-v small,.redisx-kpi.redisx-crit .ak-fig-s{color:#93000a}
.redisx-ring{position:relative;display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;flex:0 0 auto}
.redisx-ring b{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#111d23;pointer-events:none}
.redisx-save-ico{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:$S;font-size:14px}
.ak-fig-v small{font-size:14px;font-weight:500;color:#6d7a77;white-space:nowrap}
.redisx-donut{flex:0 0 auto}
.redisx-cards{gap:24px;margin-bottom:16px}
.redisx-card{padding:20px 24px}
.redisx-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.redisx-card-h h3{margin:0;font-size:15px;font-weight:600;color:#111d23}
.redisx-card-h h3 small{font-size:13px;font-weight:400;color:#6d7a77}
.redisx-big{font-size:32px;font-weight:700;margin:4px 0 12px}
.redisx-save{display:flex;align-items:center;gap:8px;font-size:20px;font-weight:700;margin:6px 0 12px}
.redisx-save-ok{color:$P}
.redisx-save-err{color:#ba1a1a}
.redisx-rows{border-top:1px solid #e2e8f0;padding-top:6px}
.redisx-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:8px 0;font-size:14px;color:#3d4947}
.redisx-row>span{display:inline-flex;align-items:center;gap:10px}
.redisx-row>span>svg:first-child,.redisx-row>span>i:first-child{width:16px;color:#6d7a77}
.redisx-row b{color:#111d23;font-weight:600;white-space:nowrap}
.redisx-help{color:#9aa9a6;font-size:13px;cursor:help}
.redisx-foot{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;padding-top:8px;font-size:13px;color:#6d7a77}
.redisx-dot{margin-right:8px}
.redisx-down{justify-content:center;min-height:120px;font-size:15px;font-weight:600}.redisx-down>span{flex:0 0 auto}
.redisx-banner{margin-bottom:16px}
@media(max-width:1200px){.redisx-cards{grid-template-columns:1fr}}
@media(prefers-reduced-motion:reduce){#redis-metrics *{transition:none!important}}
</style>";
}
