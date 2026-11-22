<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Meme droit que l'onglet voisin (fw.system.ntpd.servers.status.php) ; AsFirewallManager n'avait rien a faire ici.
$users=new usersMenus();if(!$users->AsDebianSystem){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();

// Onglet "Events" de fw.system.ntpd.php — maquette stitch_ntp_events_log_viewer_redesign
// (2026-11-21). Le journal /var/log/chronyd.log est lu par /chrony/events ; les cartes
// "en un coup d'oeil" et les filtres sont DERIVES du texte des lignes affichees : il
// n'existe ni compteur ni niveau de gravite structure cote daemon.

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_SESSION["NTPD_SEARCH"])){$_SESSION["NTPD_SEARCH"]="";}
	$q=urlencode((string)$_SESSION["NTPD_SEARCH"]);
	$lines=ntpev_lines_param(isset($_SESSION["NTPD_LINES"])?$_SESSION["NTPD_LINES"]:50);
	echo "<div id='ntpev-host'></div>
	<script>LoadAjax('ntpev-host','$page?search=$q&lines=$lines');</script>";
}

/** 50, 200 ou 500 : les seules profondeurs proposees. */
function ntpev_lines_param($v):int{
	$v=intval($v);
	if(!in_array($v,array(50,200,500))){$v=50;}
	return $v;
}

function search(){
	$term=trim((string)$_GET["search"]);
	$lines=ntpev_lines_param(isset($_GET["lines"])?$_GET["lines"]:50);
	$_SESSION["NTPD_SEARCH"]=$term;
	$_SESSION["NTPD_LINES"]=$lines;
	echo ntpev_content(ntpev_model($term,$lines));
}

// ---------------------------------------------------------------------------
// Modele : lecture du journal, decoupage et classement de chaque ligne.

function ntpev_model(string $term,int $lines):array{
	$tpl=new template_admin();
	$m=array("term"=>$term,"lines"=>$lines,"error"=>"","rows"=>array(),"filtered"=>false);
	$MAIN=$tpl->format_search_protocol($term);
	$search=trim((string)$MAIN["TERM"]);
	if(strlen($search)<3){$search="NONE";}else{$m["filtered"]=true;}
	$data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/events/".urlencode(base64_encode($search))."/$lines");
	$json=json_decode($data);
	if(!is_object($json)){
		$m["error"]="{ntpev_err_unreadable}";
		return $m;
	}
	if(empty($json->Status)){
		$m["error"]=trim((string)($json->Error ?? ""));
		if($m["error"]==""){$m["error"]="{ntpev_err_unreadable}";}
		return $m;
	}
	$logs=(isset($json->Logs) && is_array($json->Logs))?$json->Logs:array();
	foreach($logs as $line){
		$line=trim((string)$line);
		if($line==""){continue;}
		$m["rows"][]=ntpev_parse($line);
	}
	return $m;
}

/** Une ligne syslog (format classique ou ISO) -> date, pid, message, nature. */
function ntpev_parse(string $line):array{
	$r=array("ts"=>0,"pid"=>"","msg"=>$line,"raw"=>$line);
	if(preg_match('#^([A-Z][a-z]{2})\s+([0-9]{1,2})\s+([0-9:]{8})\s+\S+\s+chronyd\[([0-9]+)\]:\s+(.+)$#',$line,$re)){
		$ts=strtotime("{$re[1]} {$re[2]} ".date("Y")." {$re[3]}");
		// Pas d'annee dans syslog : une date "dans le futur" appartient a l'annee precedente.
		if($ts!==false && $ts>time()+86400){$ts=strtotime("{$re[1]} {$re[2]} ".(intval(date("Y"))-1)." {$re[3]}");}
		$r["ts"]=($ts===false)?0:$ts;
		$r["pid"]=$re[4];
		$r["msg"]=$re[5];
	}elseif(preg_match('#^([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9:.]+\S*)\s+\S+\s+chronyd\[([0-9]+)\]:\s+(.+)$#',$line,$re)){
		$ts=strtotime($re[1]);
		$r["ts"]=($ts===false)?0:$ts;
		$r["pid"]=$re[2];
		$r["msg"]=$re[3];
	}
	return array_merge($r,ntpev_classify($r["msg"]));
}

/**
 * Nature d'un message, sur le vocabulaire reel de chronyd. groups sert aux filtres :
 * problems, clock, servers, lifecycle.
 */
function ntpev_classify(string $msg):array{
	$k=array("kind"=>"other","groups"=>array(),"server"=>"","name"=>"","offset"=>null);
	if(preg_match('#Selected source\s+([^\s(]+)(?:\s*\(([^)]+)\))?#i',$msg,$re)){
		$k["kind"]="chosen";$k["groups"]=array("servers");$k["server"]=$re[1];
		$k["name"]=isset($re[2])?trim($re[2]):"";
		return $k;
	}
	if(preg_match('#no selectable sources|Can.t synchronise#i',$msg)){
		$k["kind"]="nosource";$k["groups"]=array("servers","problems");
		return $k;
	}
	if(preg_match('#System clock (?:was stepped by|wrong by)\s+(-?[0-9]+(?:\.[0-9]+)?)\s+seconds#i',$msg,$re)){
		$k["kind"]="clock";$k["groups"]=array("clock");$k["offset"]=floatval($re[1]);
		return $k;
	}
	if(preg_match('#Source\s+(\S+)\s+online#i',$msg,$re)){
		$k["kind"]="online";$k["groups"]=array("servers");$k["server"]=$re[1];
		return $k;
	}
	if(preg_match('#Source\s+(\S+)\s+offline#i',$msg,$re)){
		$k["kind"]="offline";$k["groups"]=array("servers");$k["server"]=$re[1];
		return $k;
	}
	if(preg_match('#chronyd version\s+\S+\s+starting|chronyd exiting#i',$msg)){
		$k["kind"]="lifecycle";$k["groups"]=array("lifecycle");
		return $k;
	}
	if(preg_match('#(Could not|unable to|Fatal|error|failed|Cannot)#i',$msg)){
		$k["kind"]="error";$k["groups"]=array("problems");
		return $k;
	}
	return $k;
}

/** Jeton traduit puis %s remplace. */
function ntpev_tr(string $token,$value):string{
	$tpl=new template_admin();
	return str_replace("%s",(string)$value,$tpl->_ENGINE_parse_body("{".$token."}"));
}

/** 12 min ago / 3 h ago / 2 d ago, et la date exacte du journal en info-bulle. */
function ntpev_when(int $ts):string{
	if($ts<=0){return "<span class='ntpev-muted'>&mdash;</span>";}
	$exact=htmlspecialchars(date("M j H:i:s",$ts));
	$age=time()-$ts;
	if($age<60){$txt="{ntpev_just_now}";}
	elseif($age<3600){$txt=ntpev_tr("ntpev_ago",intval($age/60)." min");}
	elseif($age<86400){$txt=ntpev_tr("ntpev_ago",intval($age/3600)." h");}
	else{$txt=ntpev_tr("ntpev_ago",intval($age/86400)." d");}
	return "<span title=\"$exact\">$txt</span>";
}

/** Message echappe, adresses IPv4 et durees en secondes en chasse fixe. */
function ntpev_message(string $msg):string{
	$h=htmlspecialchars($msg);
	$h=preg_replace('#\b([0-9]{1,3}(?:\.[0-9]{1,3}){3})\b#','<span class=\'ntpev-mono\'>$1</span>',$h);
	$h=preg_replace('#(?<![0-9.>])(-?[0-9]+\.[0-9]+ seconds)#','<span class=\'ntpev-mono\'>$1</span>',$h);
	return $h;
}

// ---------------------------------------------------------------------------
// Widgets

function ntpev_style():string{
	if(isset($GLOBALS["NTPEV_CSS"])){return "";}
	$GLOBALS["NTPEV_CSS"]=true;
	$accent="#005447";$dark="#003d33";$soft="#e6eeed";$line="#bfd4d1";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent"])){$accent=$a["accent"];}
		if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
		if(!empty($a["accent_line"])){$line=$a["accent_line"];}
	}
	return "<style>
.ntpev{display:flex;flex-direction:column;gap:20px;margin-top:16px;color:#0f172a;font-size:14px}
.ntpev *{box-sizing:border-box}
.ntpev-hero{display:flex;gap:20px;align-items:flex-start;background:#f2f6f6;border:1px solid $line;border-radius:12px;padding:24px}
.ntpev-hero-ico{flex:0 0 auto;width:48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px}
.ntpev-hero h2{font-size:20px;font-weight:600;margin:0;color:#0f172a}
.ntpev-hero p{font-size:14px;line-height:1.6;color:#334155;margin:8px 0 0}
.ntpev-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.ntpev-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:500;border:1px solid #e2e8f0;background:#fff;color:#334155}
.ntpev-glance{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media (max-width:1200px){.ntpev-glance{grid-template-columns:1fr}}
.ntpev-card{display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.ntpev-card-ico{flex:0 0 auto;width:44px;height:44px;border-radius:10px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:18px}
.ntpev-card-ico.ntpev-warn{background:#fffbeb;color:#b45309}
.ntpev-card-ico.ntpev-off{background:#f1f5f9;color:#94a3b8}
.ntpev-card-l{font-size:13px;color:#64748b}
.ntpev-card-v{font-size:18px;font-weight:600;color:#0f172a;margin-top:2px;word-break:break-word}
.ntpev-card-v.ntpev-warn-t{color:#b45309}
.ntpev-card-s{font-size:13px;color:#64748b;margin-top:4px}
.ntpev-card-x{font-size:13px;color:#64748b;margin-top:2px;line-height:1.5}
.ntpev-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px}
.ntpev-card-v .ntpev-mono{font-size:14px;font-weight:500;color:#334155}
.ntpev-banner{display:flex;align-items:center;gap:14px;border-radius:12px;padding:14px 20px;font-size:14px}
.ntpev-banner i{font-size:18px}
.ntpev-banner .ntpev-grow{flex:1 1 auto;font-weight:600}
.ntpev-banner-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.ntpev-banner-err .ntpev-grow{font-weight:400}
.ntpev-banner-prob{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.ntpev-log{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.05);overflow:hidden}
.ntpev-tools{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;flex-direction:column;gap:14px}
.ntpev-tools-r{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
.ntpev-sbox{display:flex;gap:8px;align-items:center}
.ntpev-search{display:flex;align-items:center;gap:8px;width:460px;max-width:100%;height:40px;padding:0 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#64748b}
.ntpev-search input{border:0;outline:0;flex:1 1 auto;font-size:14px;color:#0f172a;background:transparent;min-width:0}
.ntpev-search:focus-within{border-color:$accent;box-shadow:0 0 0 2px $soft}
.ntpev-help{font-size:13px;color:#64748b;margin-top:6px}
.ntpev-lines{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
.ntpev-lines label{font-size:13px;color:#475569;font-weight:500;margin:0;display:flex;align-items:center;gap:8px}
.ntpev-lines select{height:36px;border:1px solid #e2e8f0;border-radius:8px;padding:0 10px;font-size:14px;background:#fff;color:#0f172a}
.ntpev-chipsf{display:flex;flex-wrap:wrap;gap:8px}
.ntpev-f{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 14px;border-radius:999px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap}
.ntpev-f b{display:inline-block;min-width:22px;padding:1px 6px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:13px;font-weight:600;text-align:center}
.ntpev-f[aria-checked=true]{background:$accent;border-color:$accent;color:#fff}
.ntpev-f[aria-checked=true] b{background:rgba(255,255,255,.2);color:#fff}
.ntpev-f[disabled]{opacity:.5;cursor:default}
.ntpev-btn{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 18px;border-radius:8px;border:1px solid $accent;background:$accent;color:#fff;font-size:14px;font-weight:600;cursor:pointer}
.ntpev-btn:hover{background:$dark;border-color:$dark}
.ntpev-btn-o{background:#fff;color:#991b1b;border-color:#fecaca;height:34px;font-size:13px}
.ntpev-btn-o:hover{background:#fff5f5;border-color:#fca5a5;color:#7f1d1d}
.ntpev-link{background:none;border:0;padding:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer;text-decoration:underline}
.ntpev-f:focus-visible,.ntpev-btn:focus-visible,.ntpev-link:focus-visible,.ntpev-lines select:focus-visible{outline:2px solid $accent;outline-offset:2px}
.ntpev-tbl{width:100%;border-collapse:collapse}
.ntpev-tbl th{text-align:left;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b;padding:12px 24px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.ntpev-tbl td{padding:12px 24px;border-top:1px solid #f1f5f9;font-size:14px;color:#334155;vertical-align:top;line-height:1.5}
.ntpev-tbl td.ntpev-w{white-space:nowrap;width:1%}
.ntpev-tbl td.ntpev-k{white-space:nowrap;width:1%}
.ntpev-tbl tbody tr:hover{background:#f8fafc}
.ntpev-tbl tr.ntpev-r-error{background:#fff7f7}
.ntpev-tbl tr.ntpev-r-nosource{background:#fffcf0}
.ntpev-tbl tr.ntpev-r-error td.ntpev-m{color:#991b1b}
.ntpev-tbl tr.ntpev-r-nosource td.ntpev-m{color:#92400e}
.ntpev-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:500;white-space:nowrap;border:1px solid transparent}
.ntpev-b-chosen,.ntpev-b-online{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.ntpev-b-nosource{background:#fffbeb;color:#b45309;border-color:#fde68a}
.ntpev-b-clock{background:$soft;color:$accent;border-color:$line}
.ntpev-b-offline,.ntpev-b-lifecycle,.ntpev-b-other{background:#f1f5f9;color:#475569}
.ntpev-b-error{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.ntpev-pid{float:right;margin-left:16px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#94a3b8}
.ntpev-muted{color:#94a3b8}
.ntpev-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 24px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b}
.ntpev-empty{padding:40px 24px;text-align:center}
.ntpev-empty-ico{font-size:32px;color:#94a3b8;margin-bottom:12px}
.ntpev-empty-t{font-size:16px;font-weight:600;color:#0f172a}
.ntpev-empty-x{font-size:14px;color:#64748b;margin:8px auto 16px;max-width:620px;line-height:1.6}
.ntpev-empty-a{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
@media (prefers-reduced-motion:no-preference){.ntpev-f,.ntpev-btn,.ntpev-tbl tbody tr{transition:background .15s,color .15s,border-color .15s}}
</style>";
}

function ntpev_hero(array $m):string{
	return "<div class='ntpev-hero'><div class='ntpev-hero-ico'><i class='fa-solid fa-clock' aria-hidden='true'></i></div>
	<div><h2>{ntpev_hero_title}</h2><p>{ntpev_hero_text}</p>
	<div class='ntpev-chips'>
	<span class='ntpev-chip'><i class='fa-solid fa-book-open' aria-hidden='true'></i>{ntpev_chip_readonly}</span>
	<span class='ntpev-chip'><i class='fa-solid fa-arrow-down-wide-short' aria-hidden='true'></i>{ntpev_chip_newest}</span>
	<span class='ntpev-chip'><i class='fa-solid fa-list-ol' aria-hidden='true'></i>".ntpev_tr("ntpev_chip_lines",intval($m["lines"]))."</span>
	</div></div></div>";
}

/** Serveur retenu : la ligne "Selected source" la plus recente de la vue. */
function ntpev_card_server(array $m):string{
	$chosen=null;$lost=null;
	foreach($m["rows"] as $r){
		if($r["kind"]=="chosen" && $chosen===null){$chosen=$r;}
		if($r["kind"]=="nosource" && $lost===null){$lost=$r;}
	}
	$head="<div class='ntpev-card-l'>{ntpev_card_server}</div>";
	// Plus de source utilisable APRES le dernier choix : c'est l'etat actuel du journal.
	if($lost!==null && ($chosen===null || $lost["ts"]>=$chosen["ts"])){
		return "<div class='ntpev-card'><div class='ntpev-card-ico ntpev-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i></div>
		<div>$head<div class='ntpev-card-v ntpev-warn-t'>{ntpev_card_nosource}</div>
		<div class='ntpev-card-s'>".ntpev_when($lost["ts"])."</div>
		<div class='ntpev-card-x'>{ntpev_card_nosource_x}</div></div></div>";
	}
	if($chosen===null){
		return "<div class='ntpev-card'><div class='ntpev-card-ico ntpev-off'><i class='fa-solid fa-server' aria-hidden='true'></i></div>
		<div>$head<div class='ntpev-card-x'>{ntpev_card_server_none}</div></div></div>";
	}
	$v="<span class='ntpev-mono'>".htmlspecialchars($chosen["server"])."</span>";
	if($chosen["name"]!="" && $chosen["name"]!=$chosen["server"]){$v=htmlspecialchars($chosen["name"])." &mdash; $v";}
	return "<div class='ntpev-card'><div class='ntpev-card-ico'><i class='fa-solid fa-server' aria-hidden='true'></i></div>
	<div>$head<div class='ntpev-card-v'>$v</div>
	<div class='ntpev-card-s'>".ntpev_tr("ntpev_chosen",ntpev_when($chosen["ts"]))."</div></div></div>";
}

/** Dernier recalage de l'horloge, valeur reprise du message. */
function ntpev_card_clock(array $m):string{
	$c=null;
	foreach($m["rows"] as $r){ if($r["kind"]=="clock"){$c=$r;break;} }
	$head="<div class='ntpev-card-l'>{ntpev_card_clock}</div>";
	if($c===null){
		return "<div class='ntpev-card'><div class='ntpev-card-ico ntpev-off'><i class='fa-solid fa-clock-rotate-left' aria-hidden='true'></i></div>
		<div>$head<div class='ntpev-card-x'>{ntpev_card_clock_none}</div></div></div>";
	}
	$o=floatval($c["offset"]);
	$v=($o>=0?"+":"&minus;").number_format(abs($o),2,".","")." s";
	return "<div class='ntpev-card'><div class='ntpev-card-ico'><i class='fa-solid fa-clock-rotate-left' aria-hidden='true'></i></div>
	<div>$head<div class='ntpev-card-v'>$v</div><div class='ntpev-card-s'>".ntpev_when($c["ts"])."</div></div></div>";
}

function ntpev_glance(array $m):string{
	return "<div class='ntpev-glance'>".ntpev_card_server($m).ntpev_card_clock($m)."</div>";
}

function ntpev_count(array $m,string $group):int{
	$n=0;
	foreach($m["rows"] as $r){ if(in_array($group,$r["groups"])){$n++;} }
	return $n;
}

/** Seulement quand la vue contient des lignes a probleme. */
function ntpev_problems(array $m):string{
	$n=ntpev_count($m,"problems");
	if($n==0){return "";}
	$t=($n==1)?"{ntpev_prob_one}":ntpev_tr("ntpev_prob_many",$n);
	return "<div class='ntpev-banner ntpev-banner-prob' role='status'><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i>
	<div class='ntpev-grow'>$t</div>
	<button type='button' class='ntpev-btn ntpev-btn-o' OnClick=\"NtpevFilter('problems');\">{ntpev_show_problems}</button></div>";
}

function ntpev_toolbar(array $m):string{
	$sel=array();
	foreach(array(50,200,500) as $v){ $sel[]="<option value='$v'".($v==$m["lines"]?" selected":"").">$v</option>"; }
	$chips=array(array("all","{ntpev_f_all}",count($m["rows"])),
		array("problems","{ntpev_f_problems}",ntpev_count($m,"problems")),
		array("clock","{ntpev_f_clock}",ntpev_count($m,"clock")),
		array("servers","{ntpev_f_servers}",ntpev_count($m,"servers")),
		array("lifecycle","{ntpev_f_lifecycle}",ntpev_count($m,"lifecycle")));
	$c=array();
	foreach($chips as $x){
		$dis=($x[0]!="all" && $x[2]==0)?" disabled":"";
		$c[]="<button type='button' class='ntpev-f' role='radio' aria-checked='".($x[0]=="all"?"true":"false")."' data-f='{$x[0]}'$dis>{$x[1]}<b>{$x[2]}</b></button>";
	}
	return "<div class='ntpev-tools'><div class='ntpev-tools-r'>
	<div><div class='ntpev-sbox'><label class='ntpev-search'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i>
	<input type='text' id='ntpev-q' value=\"".htmlspecialchars($m["term"],ENT_QUOTES)."\" placeholder=\"{ntpev_search_ph}\" aria-label=\"{ntpev_search_ph}\" autocomplete='off'></label>
	<button type='button' class='ntpev-btn' OnClick=\"NtpevSearch();\">{ntpev_search_btn}</button></div>
	<div class='ntpev-help'>{ntpev_search_help}</div></div>
	<div class='ntpev-lines'><label for='ntpev-lines'>{ntpev_lines}<select id='ntpev-lines' OnChange=\"NtpevSearch();\">".@implode("",$sel)."</select></label>
	<div class='ntpev-help' style='margin:0'>{ntpev_lines_help}</div></div></div>
	<div class='ntpev-chipsf' role='radiogroup' aria-label=\"{ntpev_filter_label}\">".@implode("",$c)."</div></div>";
}

function ntpev_badge(string $kind):string{
	$map=array(
		"chosen"=>array("fa-solid fa-circle-check","{ntpev_k_chosen}"),
		"nosource"=>array("fa-solid fa-triangle-exclamation","{ntpev_k_nosource}"),
		"clock"=>array("fa-solid fa-clock-rotate-left","{ntpev_k_clock}"),
		"online"=>array("fa-solid fa-signal","{ntpev_k_online}"),
		"offline"=>array("fa-solid fa-plug-circle-xmark","{ntpev_k_offline}"),
		"lifecycle"=>array("fa-solid fa-power-off","{ntpev_k_lifecycle}"),
		"error"=>array("fa-solid fa-circle-xmark","{ntpev_k_error}"),
		"other"=>array("fa-solid fa-circle-info","{ntpev_k_other}"));
	$x=isset($map[$kind])?$map[$kind]:$map["other"];
	return "<span class='ntpev-badge ntpev-b-$kind'><i class='{$x[0]}' aria-hidden='true'></i>{$x[1]}</span>";
}

function ntpev_row(array $r):string{
	$pid="";
	if($r["pid"]!=""){
		$p=htmlspecialchars($r["pid"]);
		$pid="<span class='ntpev-pid' title=\"".ntpev_tr("ntpev_pid",$p)."\">[PID $p]</span>";
	}
	$g=htmlspecialchars(@implode(" ",$r["groups"]),ENT_QUOTES);
	return "<tr class='ntpev-r-{$r["kind"]}' data-g=\"$g\">
	<td class='ntpev-w'>".ntpev_when($r["ts"])."</td>
	<td class='ntpev-k'>".ntpev_badge($r["kind"])."</td>
	<td class='ntpev-m'>$pid".ntpev_message($r["msg"])."</td></tr>";
}

/** Journal vide, ou recherche sans resultat : deux invitations differentes. */
function ntpev_empty(array $m):string{
	if($m["filtered"]){
		// La recherche parcourt tout le journal (grep) : relire plus de lignes ne trouverait rien de plus.
		return "<div class='ntpev-empty'><div class='ntpev-empty-ico'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i></div>
		<div class='ntpev-empty-t'>".ntpev_tr("ntpev_nomatch",htmlspecialchars($m["term"]))."</div>
		<div class='ntpev-empty-x'>{ntpev_nomatch_x}</div>
		<div class='ntpev-empty-a'><button type='button' class='ntpev-link' OnClick=\"NtpevClear();\">{ntpev_clear}</button></div></div>";
	}
	return "<div class='ntpev-empty'><div class='ntpev-empty-ico'><i class='fa-solid fa-clock' aria-hidden='true'></i></div>
	<div class='ntpev-empty-t'>{ntpev_empty_t}</div><div class='ntpev-empty-x'>{ntpev_empty_x}</div>
	<div class='ntpev-empty-a'><button type='button' class='ntpev-link' OnClick=\"NtpevTab('status=yes');\">{ntpev_open_status}</button></div></div>";
}

function ntpev_log(array $m):string{
	$h=ntpev_toolbar($m);
	if(count($m["rows"])==0){
		return "<div class='ntpev-log'>$h".ntpev_empty($m)."</div>";
	}
	$rows=array();
	foreach($m["rows"] as $r){ $rows[]=ntpev_row($r); }
	$more="";
	if(count($m["rows"])>=$m["lines"] && $m["lines"]<500){
		$next=($m["lines"]<200)?200:500;
		$more="<button type='button' class='ntpev-link' OnClick=\"NtpevLines($next);\">".ntpev_tr("ntpev_show_n",$next)."</button>";
	}
	return "<div class='ntpev-log'>$h
	<table class='ntpev-tbl'><thead><tr><th>{ntpev_c_when}</th><th>{ntpev_c_kind}</th><th>{ntpev_c_message}</th></tr></thead>
	<tbody id='ntpev-rows'>".@implode("",$rows)."</tbody></table>
	<div class='ntpev-empty' id='ntpev-nofilter' hidden><div class='ntpev-empty-t'>{ntpev_nofilter}</div>
	<div class='ntpev-empty-a' style='margin-top:10px'><button type='button' class='ntpev-link' OnClick=\"NtpevFilter('all');\">{ntpev_show_all}</button></div></div>
	<div class='ntpev-foot'><span>".ntpev_tr("ntpev_shown",count($m["rows"]))." &middot; <span title=\"{ntpev_auto_note}\"><i class='fa-solid fa-rotate' aria-hidden='true'></i> {ntpev_auto_short}</span></span>$more</div></div>";
}

/** Les filtres par nature masquent des lignes deja chargees ; recherche et profondeur relisent le journal. */
function ntpev_js():string{
	$page=CurrentPageName();
	$js=<<<'JS'
<script>
(function(){
function rows(){var t=document.getElementById('ntpev-rows');return t?t.rows:[];}
var S=window.NtpevState||(window.NtpevState={f:'all'});
window.NtpevFilter=function(f){
  S.f=f;
  var r=rows(),n=0;
  for(var i=0;i<r.length;i++){var ok=(f=='all')||((' '+r[i].getAttribute('data-g')+' ').indexOf(' '+f+' ')>=0);r[i].style.display=ok?'':'none';if(ok){n++;}}
  var c=document.querySelectorAll('.ntpev-f');
  for(var k=0;k<c.length;k++){c[k].setAttribute('aria-checked',c[k].getAttribute('data-f')==f?'true':'false');}
  var e=document.getElementById('ntpev-nofilter');if(e){e.hidden=(n>0);}
};
var c=document.querySelectorAll('.ntpev-f');
for(var k=0;k<c.length;k++){c[k].onclick=function(){if(!this.disabled){NtpevFilter(this.getAttribute('data-f'));}};}
window.NtpevSearch=function(){
  var q=document.getElementById('ntpev-q'),l=document.getElementById('ntpev-lines');
  LoadAjax('ntpev-host','__PAGE__?search='+encodeURIComponent(q?q.value:'')+'&lines='+(l?l.value:50));
};
window.NtpevLines=function(n){var l=document.getElementById('ntpev-lines');if(l){l.value=n;}NtpevSearch();};
window.NtpevClear=function(){var q=document.getElementById('ntpev-q');if(q){q.value='';}NtpevSearch();};
window.NtpevTab=function(q){var a=jQuery('a[data-url*="'+q+'"]');if(a.length){a.first().trigger('click');}};
var q=document.getElementById('ntpev-q');
if(q){q.onkeydown=function(e){if((e.key||'')=='Enter'||e.keyCode==13){NtpevSearch();}};}
// Filtre en cours retrouve apres un rechargement (s'il a encore des lignes).
var cur=document.querySelector('.ntpev-f[data-f="'+S.f+'"]');
if(S.f!='all' && cur && !cur.disabled){NtpevFilter(S.f);}else{S.f='all';}
// Les nouvelles lignes arrivent en haut : on ne recharge que si le lecteur est en haut
// du journal, ne tape rien, n'a pas de fenetre ouverte et regarde l'onglet.
if(window.NtpevTimer){clearTimeout(window.NtpevTimer);}
window.NtpevTimer=setTimeout(function tick(){
  var p=document.getElementById('ntpev-panel');
  if(!p){window.NtpevTimer=null;return;}
  var log=p.querySelector('.ntpev-log'),qi=document.getElementById('ntpev-q');
  var reading=log && log.getBoundingClientRect().top<0;
  var typing=qi && document.activeElement===qi;
  var busy=jQuery('.modal.in,.modal.show,.sweet-alert.visible,.swal2-container').length>0;
  if(document.hidden||reading||typing||busy){window.NtpevTimer=setTimeout(tick,60000);return;}
  LoadAjaxSilent('ntpev-host','__PAGE__?search='+encodeURIComponent(p.getAttribute('data-q')||'')+'&lines='+(p.getAttribute('data-lines')||50));
},60000);
})();
</script>
JS;
	return str_replace("__PAGE__",$page,$js);
}

function ntpev_content(array $m):string{
	$tpl=new template_admin();
	$w=array();
	$w[]=ntpev_hero($m);
	if($m["error"]!=""){
		$w[]="<div class='ntpev-banner ntpev-banner-err' role='alert'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>
		<div class='ntpev-grow'><b>{ntpev_err_title}</b><div style='font-size:13px;margin-top:2px'>".htmlspecialchars(strip_tags($tpl->_ENGINE_parse_body($m["error"])))."</div></div></div>";
	}else{
		// Sans ligne, les cartes n'auraient rien a resumer : l'etat vide du journal suffit.
		if(count($m["rows"])>0){$w[]=ntpev_glance($m);}
		$w[]=ntpev_problems($m);
		$w[]=ntpev_log($m);
	}
	$attrs=" data-q=\"".htmlspecialchars($m["term"],ENT_QUOTES)."\" data-lines='".intval($m["lines"])."'";
	return ntpev_style().$tpl->_ENGINE_parse_body("<div class='ntpev' id='ntpev-panel'$attrs>".@implode("\n",$w)."</div>").ntpev_js()."<script>NoSpinner();</script>";
}
