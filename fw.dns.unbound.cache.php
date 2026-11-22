<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["forget-js"])){forget_js();exit;}
if(isset($_POST["dnsc-forget"])){forget_save();exit;}
if(isset($_GET["flush-all-js"])){flush_all_js();exit;}
if(isset($_POST["dnsc-flush-all"])){flush_all_save();exit;}

page();

// Onglet "Cache" de fw.dns.unbound.php / fw.dns.dnsdist.php — maquette
// stitch_dns_resolver_cache_redesign (2026-11-21). /unbound/cache/dump rend un
// instantane du cache du resolveur : les compteurs portent sur l'instantane entier,
// le tableau sur le filtre choisi, pagine COTE SERVEUR (seule la page affichee voyage).

function ifisright(){
	$users=new usersMenus();
	if($users->AsDnsAdministrator){return true;}
	return false;
}

/**
 * Un nom que l'on accepte de purger du cache : la classe d'un nom DNS, sans
 * espace ni metacaractere (articarest revalide le nom avant unbound-control).
 */
function dnscache_valid_host($h):bool{
	$h=(string)$h;
	if($h===""||strlen($h)>253){return false;}
	return preg_match('#^[A-Za-z0-9._-]+$#',$h)==1;
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=isset($_SESSION["DNSC_Q"])?(string)$_SESSION["DNSC_Q"]:"";
	$f=dnsc_filter_param(isset($_SESSION["DNSC_F"])?$_SESSION["DNSC_F"]:"all");
	$max=dnsc_max_param(isset($_SESSION["DNSC_MAX"])?$_SESSION["DNSC_MAX"]:50);
	$pg=max(1,intval(isset($_SESSION["DNSC_P"])?$_SESSION["DNSC_P"]:1));

	$top=array();
	$top[]=array("DnscReload();",ico_refresh,"{refresh}");
	$TINY_ARRAY["TITLE"]="DNS {cache}";
	$TINY_ARRAY["ICO"]="fa fas fa-database";
	$TINY_ARRAY["EXPL"]="{dnsc_tiny_explain}";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($top);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$url="$page?search=".urlencode($q)."&f=$f&max=$max&p=$pg";
	echo "<div id='dnsc-host'></div>
	<script>
	window.DnscReload=function(){var p=document.getElementById('dnsc-panel');
	  if(!p){LoadAjax('dnsc-host','$url');return;}
	  LoadAjax('dnsc-host','$page?search='+encodeURIComponent(p.getAttribute('data-q')||'')+'&f='+(p.getAttribute('data-f')||'all')+'&max='+(p.getAttribute('data-max')||50)+'&p='+(p.getAttribute('data-p')||1));};
	LoadAjax('dnsc-host','$url');
	$jstiny
	</script>";
}

function dnsc_filter_param($v):string{
	$v=(string)$v;
	if(!in_array($v,array("all","found","nx","fail","dnssec","exp"))){$v="all";}
	return $v;
}
function dnsc_max_param($v):int{
	$v=intval($v);
	if(!in_array($v,array(50,100,200))){$v=50;}
	return $v;
}

function search(){
	$q=trim((string)$_GET["search"]);
	if(strlen($q)>200){$q=substr($q,0,200);}
	$f=dnsc_filter_param(isset($_GET["f"])?$_GET["f"]:"all");
	$max=dnsc_max_param(isset($_GET["max"])?$_GET["max"]:50);
	$pg=max(1,intval(isset($_GET["p"])?$_GET["p"]:1));
	$_SESSION["DNSC_Q"]=$q;$_SESSION["DNSC_F"]=$f;$_SESSION["DNSC_MAX"]=$max;$_SESSION["DNSC_P"]=$pg;
	echo dnsc_content(dnsc_model($q,$f,$max,$pg));
}

// ---------------------------------------------------------------------------
// Modele

/** Code de reponse DNS -> famille affichee. */
function dnsc_result(string $rcode):string{
	$r=strtoupper($rcode);
	if($r=="NOERROR"){return "found";}
	if($r=="NXDOMAIN"){return "nx";}
	if($r=="REFUSED"){return "refused";}
	return "failed";
}

function dnsc_model(string $q,string $f,int $max,int $pg=1):array{
	$m=array("q"=>$q,"f"=>$f,"max"=>$max,"p"=>$pg,"pages"=>1,"offset"=>0,"error"=>"","entries"=>array(),
		"total"=>0,"found"=>0,"nx"=>0,"fail"=>0,"dnssec"=>0,"exp"=>0,
		"v4"=>0,"v6"=>0,"other"=>0,"matched"=>0);
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/cache/dump"));
	if(!is_object($json)){$m["error"]="{dnsc_err_unreadable}";return $m;}
	if(empty($json->Status)){
		$m["error"]=trim((string)($json->Error ?? ""));
		if($m["error"]==""){$m["error"]="{dnsc_err_unreadable}";}
		return $m;
	}
	$cache=(isset($json->Cache) && is_object($json->Cache))?$json->Cache:new stdClass();

	// Duree restante et donnee, indexees par nom + classe + type depuis le cache des RRset.
	$HASH=array();
	foreach((isset($cache->RRSetCache) && is_array($cache->RRSetCache))?$cache->RRSetCache:array() as $d){
		if(!isset($d->RRs) || !is_array($d->RRs)){continue;}
		foreach($d->RRs as $rr){
			$k=strtolower((string)$rr->Name)."|".$rr->Class."|".$rr->Type;
			if(!isset($HASH[$k])){$HASH[$k]=array("ttl"=>intval($rr->TTL),"data"=>array());}
			$HASH[$k]["data"][]=(string)$rr->RData;
		}
	}

	$needle=strtolower($q);
	$keep=array();
	foreach((isset($cache->MsgCache) && is_array($cache->MsgCache))?$cache->MsgCache:array() as $d){
		if(!is_object($d) || strlen((string)$d->QName)<2){continue;}
		$name=(string)$d->QName;
		$type=strtoupper((string)$d->QType);
		$flags=(isset($d->Flags) && is_object($d->Flags))?$d->Flags:new stdClass();
		$rcode=(string)($flags->RCODEName ?? "");
		$res=dnsc_result($rcode);
		$ad=!empty($flags->AD);
		$k=strtolower($name)."|".$d->QClass."|".$d->QType;
		$ttl=isset($HASH[$k])?$HASH[$k]["ttl"]:null;
		$data=isset($HASH[$k])?@implode(", ",$HASH[$k]["data"]):"";
		$host=rtrim($name,".");

		// Compteurs sur l'instantane entier, avant tout filtre.
		$m["total"]++;
		if($res=="found"){$m["found"]++;}elseif($res=="nx"){$m["nx"]++;}else{$m["fail"]++;}
		if($ad){$m["dnssec"]++;}
		$exp=($ttl!==null && $ttl<60);
		if($exp){$m["exp"]++;}
		if($type=="A"){$m["v4"]++;}elseif($type=="AAAA"){$m["v6"]++;}else{$m["other"]++;}

		if($needle!=="" && strpos(strtolower("$host $data"),$needle)===false){continue;}
		if($f=="found" && $res!="found"){continue;}
		if($f=="nx" && $res!="nx"){continue;}
		if($f=="fail" && ($res=="found" || $res=="nx")){continue;}
		if($f=="dnssec" && !$ad){continue;}
		if($f=="exp" && !$exp){continue;}
		$m["matched"]++;
		$keep[]=array("host"=>$host,"type"=>$type,"rcode"=>$rcode,"res"=>$res,"ad"=>$ad,"ttl"=>$ttl,"data"=>$data);
	}
	// Pagination cote serveur : une page hors limites (cache qui a rapetisse) revient a la derniere.
	$m["pages"]=max(1,(int)ceil($m["matched"]/$max));
	if($m["p"]>$m["pages"]){$m["p"]=$m["pages"];}
	$m["offset"]=($m["p"]-1)*$max;
	$m["entries"]=array_slice($keep,$m["offset"],$max);
	return $m;
}

/** Jeton traduit puis %s remplace. */
function dnsc_tr(string $token,$value):string{
	$tpl=new template_admin();
	return str_replace("%s",(string)$value,$tpl->_ENGINE_parse_body("{".$token."}"));
}

/** Texte venu du resolveur : echappe, accolades neutralisees (sinon lues comme {jeton} par le moteur). */
function dnsc_esc($v):string{
	return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES));
}

function dnsc_num(int $n):string{
	return number_format($n,0,".",",");
}

/** 4 min 12 s, 2 h 5 min, 3 d 2 h. */
function dnsc_duration(int $s):string{
	// Unites traduites (elles etaient ecrites en anglais dans toutes les langues).
	$tpl=new template_admin();
	$f=function($tok,$a,$b) use($tpl){return str_replace(array("%a","%b"),array($a,$b),$tpl->_ENGINE_parse_body("{".$tok."}"));};
	if($s<60){return $f("dnsc_dur_s",$s,"");}
	if($s<3600){return $f("dnsc_dur_ms",intval($s/60),$s%60);}
	if($s<86400){return $f("dnsc_dur_hm",intval($s/3600),intval(($s%3600)/60));}
	return $f("dnsc_dur_dh",intval($s/86400),intval(($s%86400)/3600));
}

// ---------------------------------------------------------------------------
// Widgets

function dnsc_style():string{
	if(isset($GLOBALS["DNSC_CSS"])){return "";}
	$GLOBALS["DNSC_CSS"]=true;
	$accent="#005447";$dark="#003d33";$soft="#e6eeed";$line="#bfd4d1";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent"])){$accent=$a["accent"];}
		if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
		if(!empty($a["accent_line"])){$line=$a["accent_line"];}
	}
	return "<style>
.dnsc{display:flex;flex-direction:column;gap:20px;margin-top:16px;color:#0f172a;font-size:14px}
.dnsc *{box-sizing:border-box}
.dnsc-hero{display:flex;gap:20px;align-items:flex-start;background:#f2f6f6;border:1px solid $line;border-radius:12px;padding:24px}
.dnsc-hero-ico{flex:0 0 auto;width:48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px}
.dnsc-hero h2{font-size:20px;font-weight:600;margin:0;color:#0f172a}
.dnsc-hero p{font-size:14px;line-height:1.6;color:#334155;margin:8px 0 0}
.dnsc-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.dnsc-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:500;border:1px solid #e2e8f0;background:#fff;color:#334155}
.dnsc-tiles{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
@media (max-width:1200px){.dnsc-tiles{grid-template-columns:repeat(2,1fr)}}
.dnsc-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:6px;text-align:left;box-shadow:0 1px 2px rgba(15,23,42,.05);font:inherit;color:inherit}
button.dnsc-tile{cursor:pointer}
button.dnsc-tile:hover{border-color:$line}
.dnsc-tile-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.dnsc-tile-l{font-size:13px;color:#64748b}
.dnsc-tile-v{font-size:28px;font-weight:600;color:#0f172a;line-height:1.2}
.dnsc-tile-v.dnsc-warn-t{color:#b45309}
.dnsc-tile-ico{flex:0 0 auto;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;background:$soft;color:$accent}
.dnsc-tile-ico.dnsc-ok{background:#ecfdf5;color:#047857}
.dnsc-tile-ico.dnsc-off{background:#f1f5f9;color:#475569}
.dnsc-tile-ico.dnsc-warn{background:#fffbeb;color:#b45309}
.dnsc-tile-s{font-size:13px;color:#64748b;border-top:1px solid #f1f5f9;padding-top:10px;margin-top:auto}
.dnsc-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.05);overflow:hidden}
.dnsc-tools{padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;flex-direction:column;gap:14px}
.dnsc-tools-r{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
.dnsc-search{display:flex;align-items:center;gap:8px;width:460px;max-width:100%;height:40px;padding:0 12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#64748b}
.dnsc-search input{border:0;outline:0;flex:1 1 auto;font-size:14px;color:#0f172a;background:transparent;min-width:0}
.dnsc-search:focus-within{border-color:$accent;box-shadow:0 0 0 2px $soft}
.dnsc-help{font-size:13px;color:#64748b;margin-top:6px}
.dnsc-show{display:flex;gap:20px;align-items:flex-start}
.dnsc-show label{font-size:13px;color:#475569;font-weight:500;margin:0;display:flex;align-items:center;gap:8px}
.dnsc-show select{height:38px;border:1px solid #e2e8f0;border-radius:8px;padding:0 10px;font-size:14px;background:#fff;color:#0f172a}
.dnsc-btn{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#0f172a;font-size:14px;font-weight:500;cursor:pointer}
.dnsc-btn:hover{border-color:$line;background:$soft;color:$dark}
.dnsc-btn-s{height:32px;padding:0 12px;font-size:13px}
.dnsc-btn-warn{color:#92400e;border-color:#fde68a;background:#fffbeb}
.dnsc-btn-warn:hover{color:#78350f;border-color:#fcd34d;background:#fef3c7}
.dnsc-gap{display:inline-flex;align-items:center;padding:0 4px;color:#94a3b8}
.dnsc-fl{font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
.dnsc-fs{display:flex;flex-wrap:wrap;gap:8px}
.dnsc-f{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 14px;border-radius:999px;border:1px solid #e2e8f0;background:#fff;color:#334155;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap}
.dnsc-f span{color:#64748b;font-weight:400}
.dnsc-f[aria-checked=true]{background:$accent;border-color:$accent;color:#fff}
.dnsc-f[aria-checked=true] span{color:rgba(255,255,255,.85)}
.dnsc-f:focus-visible,.dnsc-btn:focus-visible,.dnsc-link:focus-visible,.dnsc-show select:focus-visible,button.dnsc-tile:focus-visible,.dnsc-pg button:focus-visible{outline:2px solid $accent;outline-offset:2px}
.dnsc-tbl{width:100%;border-collapse:collapse;table-layout:fixed}
.dnsc-tbl th{text-align:left;font-size:13px;font-weight:600;color:#64748b;padding:12px 24px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.dnsc-tbl td{padding:12px 24px;border-top:1px solid #f1f5f9;font-size:14px;color:#334155;vertical-align:middle}
.dnsc-tbl tbody tr:hover{background:#f8fafc}
.dnsc-name{display:flex;align-items:center;gap:12px;min-width:0}
.dnsc-nico{flex:0 0 auto;width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px}
.dnsc-nico.dnsc-r-found{background:#ecfdf5;color:#047857}
.dnsc-nico.dnsc-r-nx{background:#f1f5f9;color:#64748b}
.dnsc-nico.dnsc-r-failed,.dnsc-nico.dnsc-r-refused{background:#fffbeb;color:#b45309}
.dnsc-nt{font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dnsc-ns{font-size:13px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dnsc-ellip{min-width:0;overflow:hidden}
.dnsc-badge{display:inline-flex;align-items:center;gap:6px;padding:2px 9px;border-radius:999px;font-size:13px;font-weight:500;white-space:nowrap}
.dnsc-b-found{background:#ecfdf5;color:#047857}
.dnsc-b-nx{background:#f1f5f9;color:#475569}
.dnsc-b-failed{background:#fffbeb;color:#b45309}
.dnsc-b-refused{background:#fef2f2;color:#b91c1c}
.dnsc-b-ad{background:$soft;color:$accent;border:1px solid $line}
.dnsc-sub{font-size:13px;color:#64748b;margin-top:3px}
.dnsc-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px}
.dnsc-ans{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#0f172a}
.dnsc-soon{color:#b45309;font-weight:600}
.dnsc-muted{color:#94a3b8}
.dnsc-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 24px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b}
.dnsc-foot b{color:#0f172a}
.dnsc-pg{display:flex;gap:6px;flex-wrap:wrap}
.dnsc-pg button{min-width:34px;height:32px;padding:0 10px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#0f172a;font-size:13px;cursor:pointer}
.dnsc-pg button.cur{background:$accent;border-color:$accent;color:#fff}
.dnsc-pg button[disabled]{color:#94a3b8;cursor:default}
.dnsc-empty{padding:40px 24px;text-align:center}
.dnsc-empty-ico{font-size:32px;color:#94a3b8;margin-bottom:12px}
.dnsc-empty-t{font-size:16px;font-weight:600;color:#0f172a}
.dnsc-empty-x{font-size:14px;color:#64748b;margin:8px auto 14px;max-width:620px;line-height:1.6}
.dnsc-link{background:none;border:0;padding:0;color:$accent;font-weight:600;font-size:13px;cursor:pointer;text-decoration:underline}
.dnsc-banner{display:flex;align-items:center;gap:14px;border-radius:12px;padding:14px 20px;font-size:14px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.dnsc-banner i{font-size:18px}
@media (prefers-reduced-motion:no-preference){.dnsc-f,.dnsc-btn,.dnsc-tile,.dnsc-tbl tbody tr{transition:background .15s,color .15s,border-color .15s}}
</style>";
}

function dnsc_hero():string{
	return "<div class='dnsc-hero'><div class='dnsc-hero-ico'><i class='fa-solid fa-bolt' aria-hidden='true'></i></div>
	<div><h2>{dnsc_hero_title}</h2><p>{dnsc_hero_text}</p>
	<div class='dnsc-chips'>
	<span class='dnsc-chip'><i class='fa-solid fa-shield-halved' aria-hidden='true'></i>{dnsc_chip_safe}</span>
	<span class='dnsc-chip'><i class='fa-solid fa-camera' aria-hidden='true'></i>{dnsc_chip_snapshot}</span>
	<span class='dnsc-chip'><i class='fa-solid fa-hourglass-half' aria-hidden='true'></i>{dnsc_chip_expire}</span>
	</div></div></div>";
}

function dnsc_tile(string $label,string $value,string $ico,string $icocls,string $sub,string $filter="",string $vcls=""):string{
	$tag=$filter!==""?"button type='button' OnClick=\"DnscFilter('$filter');\"":"div";
	$end=$filter!==""?"button":"div";
	return "<$tag class='dnsc-tile'><div class='dnsc-tile-h'><div><div class='dnsc-tile-l'>$label</div><div class='dnsc-tile-v$vcls'>$value</div></div>
	<div class='dnsc-tile-ico $icocls'><i class='$ico' aria-hidden='true'></i></div></div><div class='dnsc-tile-s'>$sub</div></$end>";
}

function dnsc_tile_total(array $m):string{
	$sub=str_replace(array("%a","%b","%c"),array(dnsc_num($m["v4"]),dnsc_num($m["v6"]),dnsc_num($m["other"])),(new template_admin())->_ENGINE_parse_body("{dnsc_tile_total_sub}"));
	return dnsc_tile("{dnsc_tile_total}",dnsc_num($m["total"]),"fa-solid fa-database","",$sub);
}
function dnsc_tile_found(array $m):string{
	return dnsc_tile("{dnsc_tile_found}",dnsc_num($m["found"]),"fa-solid fa-circle-check","dnsc-ok",dnsc_tr("dnsc_tile_found_sub",dnsc_num($m["dnssec"])),"found");
}
function dnsc_tile_nx(array $m):string{
	return dnsc_tile("{dnsc_tile_nx}",dnsc_num($m["nx"]),"fa-solid fa-circle-question","dnsc-off","{dnsc_tile_nx_sub}","nx");
}
function dnsc_tile_fail(array $m):string{
	$warn=$m["fail"]>0;
	return dnsc_tile("{dnsc_tile_fail}",dnsc_num($m["fail"]),"fa-solid fa-triangle-exclamation",$warn?"dnsc-warn":"dnsc-off","{dnsc_tile_fail_sub}","fail",$warn?" dnsc-warn-t":"");
}
function dnsc_tiles(array $m):string{
	return "<div class='dnsc-tiles'>".dnsc_tile_total($m).dnsc_tile_found($m).dnsc_tile_nx($m).dnsc_tile_fail($m)."</div>";
}

function dnsc_toolbar(array $m):string{
	$opt=array();
	foreach(array(50,100,200) as $v){$opt[]="<option value='$v'".($v==$m["max"]?" selected":"").">".dnsc_tr("dnsc_entries",$v)."</option>";}
	$fs=array(array("all","{dnsc_f_all}",$m["total"],""),array("found","{dnsc_f_found}",$m["found"],"fa-solid fa-circle-check"),
		array("nx","{dnsc_f_nx}",$m["nx"],"fa-solid fa-circle-question"),array("fail","{dnsc_f_fail}",$m["fail"],"fa-solid fa-triangle-exclamation"),
		array("dnssec","{dnsc_f_dnssec}",$m["dnssec"],"fa-solid fa-shield-halved"),array("exp","{dnsc_f_exp}",$m["exp"],"fa-solid fa-hourglass-half"));
	$c=array();
	foreach($fs as $x){
		$i=$x[3]!==""?"<i class='{$x[3]}' aria-hidden='true'></i>":"";
		$c[]="<button type='button' class='dnsc-f' role='radio' aria-checked='".($x[0]==$m["f"]?"true":"false")."' OnClick=\"DnscFilter('{$x[0]}');\">$i{$x[1]} <span>(".dnsc_num($x[2]).")</span></button>";
	}
	return "<div class='dnsc-tools'><div class='dnsc-tools-r'>
	<div><label class='dnsc-search'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i>
	<input type='text' id='dnsc-q' value=\"".dnsc_esc($m["q"])."\" placeholder=\"{dnsc_search_ph}\" aria-label=\"{dnsc_search_ph}\" autocomplete='off'></label>
	<div class='dnsc-help'>{dnsc_search_help}</div></div>
	<div class='dnsc-show'><div><label for='dnsc-max'>{dnsc_show}<select id='dnsc-max' OnChange=\"DnscGo();\">".@implode("",$opt)."</select></label>
	<div class='dnsc-help'>{dnsc_show_help}</div></div>
	<div><button type='button' class='dnsc-btn' OnClick=\"DnscReload();\"><i class='fa-solid fa-rotate' aria-hidden='true'></i>{dnsc_refresh}</button>
	<div class='dnsc-help'>{dnsc_refresh_help}</div></div>".dnsc_flush_all_button($m)."</div></div>
	<div class='dnsc-fl'>{dnsc_quick_filters}</div>
	<div class='dnsc-fs' role='radiogroup' aria-label=\"{dnsc_filter_label}\">".@implode("",$c)."</div></div>";
}

function dnsc_type_word(string $t):string{
	$map=array("A"=>"{dnsc_t_a}","AAAA"=>"{dnsc_t_aaaa}","CNAME"=>"{dnsc_t_cname}","MX"=>"{dnsc_t_mx}",
		"TXT"=>"{dnsc_t_txt}","PTR"=>"{dnsc_t_ptr}","NS"=>"{dnsc_t_ns}","SOA"=>"{dnsc_t_soa}","SRV"=>"{dnsc_t_srv}","HTTPS"=>"{dnsc_t_https}");
	return isset($map[$t])?$map[$t]:"{dnsc_t_other}";
}

function dnsc_result_badge(string $res):string{
	$map=array("found"=>array("fa-solid fa-circle-check","{dnsc_r_found}"),"nx"=>array("fa-solid fa-circle-question","{dnsc_r_nx}"),
		"failed"=>array("fa-solid fa-triangle-exclamation","{dnsc_r_failed}"),"refused"=>array("fa-solid fa-ban","{dnsc_r_refused}"));
	$x=$map[$res];
	return "<span class='dnsc-badge dnsc-b-$res'><i class='{$x[0]}' aria-hidden='true'></i>{$x[1]}</span>";
}

/** Domaine principal du nom (google.com pour www.google.com), seulement s'il differe. */
function dnsc_family(string $host):string{
	if(!isset($GLOBALS["DNSC_ZCAT"])){$GLOBALS["DNSC_ZCAT"]=new squid_familysite();}
	$fam=trim(strip_tags((string)$GLOBALS["DNSC_ZCAT"]->GetFamilySites($host)));
	if($fam==="" || strtolower($fam)===strtolower($host)){return "";}
	return $fam;
}

function dnsc_row(array $e):string{
	$page=CurrentPageName();
	$host=$e["host"];
	$h=dnsc_esc($host);
	$fam=dnsc_family($host);
	$sub=$fam!==""?"<div class='dnsc-ns'>".dnsc_tr("dnsc_part_of",htmlspecialchars($fam))."</div>":"";
	$ad=$e["ad"]?" <span class='dnsc-badge dnsc-b-ad' title=\"{dnsc_ad_title}\"><i class='fa-solid fa-shield-halved' aria-hidden='true'></i>{dnsc_verified}</span>":"";
	$rc=$e["rcode"]!==""?htmlspecialchars($e["rcode"]):"&mdash;";
	if($e["data"]===""){$ans="<span class='dnsc-muted'>&mdash;</span>";}
	else{$d=dnsc_esc($e["data"]);$ans="<div class='dnsc-ans dnsc-mono' title=\"$d\">$d</div>";}
	if($e["ttl"]===null){$exp="<span class='dnsc-muted'>&mdash;</span>";}
	else{
		$t=intval($e["ttl"]);
		$cls=$t<60?" class='dnsc-soon'":"";
		$exp="<div$cls>".dnsc_duration($t)."</div><div class='dnsc-sub dnsc-mono'>TTL $t s</div>";
	}
	$forget="";
	if(dnscache_valid_host($host)){
		$aria=htmlspecialchars(dnsc_tr("dnsc_forget_aria",$host),ENT_QUOTES);
		$forget="<button type='button' class='dnsc-btn dnsc-btn-s' aria-label=\"$aria\" OnClick=\"Loadjs('$page?forget-js=".urlencode($host)."');\"><i class='fa-solid fa-eraser' aria-hidden='true'></i>{dnsc_forget}</button>";
	}
	return "<tr><td><div class='dnsc-name'><span class='dnsc-nico dnsc-r-{$e["res"]}'><i class='fa-solid fa-globe' aria-hidden='true'></i></span>
	<div class='dnsc-ellip'><div class='dnsc-nt' title=\"$h\">$h</div>$sub</div></div></td>
	<td>".dnsc_result_badge($e["res"])."$ad<div class='dnsc-sub dnsc-mono'>$rc</div></td>
	<td><div>".dnsc_type_word($e["type"])."</div><div class='dnsc-sub dnsc-mono'>".htmlspecialchars($e["type"])."</div></td>
	<td>$ans</td><td>$exp</td><td style='text-align:right'>$forget</td></tr>";
}

function dnsc_empty(array $m):string{
	if($m["total"]==0){
		return "<div class='dnsc-empty'><div class='dnsc-empty-ico'><i class='fa-solid fa-bolt' aria-hidden='true'></i></div>
		<div class='dnsc-empty-t'>{dnsc_empty_t}</div><div class='dnsc-empty-x'>{dnsc_empty_x}</div></div>";
	}
	$t=$m["q"]!==""?dnsc_tr("dnsc_nomatch_q",dnsc_esc($m["q"])):"{dnsc_nomatch_f}";
	return "<div class='dnsc-empty'><div class='dnsc-empty-ico'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i></div>
	<div class='dnsc-empty-t'>$t</div><div class='dnsc-empty-x'>{dnsc_nomatch_x}</div>
	<button type='button' class='dnsc-link' OnClick=\"DnscClear();\">{dnsc_clear}</button></div>";
}

function dnsc_table(array $m):string{
	$h=dnsc_toolbar($m);
	if(count($m["entries"])==0){return "<div class='dnsc-card'>$h".dnsc_empty($m)."</div>";}
	$rows=array();
	foreach($m["entries"] as $e){$rows[]=dnsc_row($e);}
	$from=$m["offset"]+1;$to=$m["offset"]+count($m["entries"]);
	$foot=str_replace(array("%a","%b"),array("<b>".dnsc_num($from)."&ndash;".dnsc_num($to)."</b>","<b>".dnsc_num($m["matched"])."</b>"),(new template_admin())->_ENGINE_parse_body("{dnsc_showing}"));
	return "<div class='dnsc-card'>$h
	<table class='dnsc-tbl'><colgroup><col style='width:27%'><col style='width:17%'><col style='width:13%'><col style='width:20%'><col style='width:12%'><col style='width:11%'></colgroup>
	<thead><tr><th>{dnsc_c_name}</th><th>{dnsc_c_result}</th><th>{dnsc_c_type}</th><th>{dnsc_c_answer}</th><th>{dnsc_c_expires}</th><th style='text-align:right'>{dnsc_c_action}</th></tr></thead>
	<tbody id='dnsc-rows'>".@implode("",$rows)."</tbody></table>
	<div class='dnsc-foot'><span>$foot</span>".dnsc_pager($m)."</div></div>";
}

/** Recherche, filtre, taille et numero de page relisent l'instantane cote serveur. */
function dnsc_js():string{
	$page=CurrentPageName();
	$js=<<<'JS'
<script>
(function(){
var P=document.getElementById('dnsc-panel');
function go(q,f,max,p){LoadAjax('dnsc-host','__PAGE__?search='+encodeURIComponent(q)+'&f='+f+'&max='+max+'&p='+(p||1));}
function cur(k,d){return P?(P.getAttribute('data-'+k)||d):d;}
window.DnscGo=function(){var q=document.getElementById('dnsc-q'),m=document.getElementById('dnsc-max');go(q?q.value:cur('q',''),cur('f','all'),m?m.value:cur('max',50));};
window.DnscFilter=function(f){var q=document.getElementById('dnsc-q');go(q?q.value:cur('q',''),f,cur('max',50));};
window.DnscClear=function(){go('','all',cur('max',50));};
var q=document.getElementById('dnsc-q');
if(q){q.onkeydown=function(e){if((e.key||'')=='Enter'||e.keyCode==13){DnscGo();}};}
window.DnscPage=function(n){var q=document.getElementById('dnsc-q');go(q?q.value:cur('q',''),cur('f','all'),cur('max',50),n);};
})();
</script>
JS;
	return str_replace("__PAGE__",$page,$js);
}

function dnsc_content(array $m):string{
	$tpl=new template_admin();
	$w=array();
	$w[]=dnsc_hero();
	if($m["error"]!=""){
		$w[]="<div class='dnsc-banner' role='alert'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><div><b>{dnsc_err_title}</b>
		<div style='font-size:13px;margin-top:2px'>".htmlspecialchars(strip_tags($tpl->_ENGINE_parse_body($m["error"])))."</div></div></div>";
	}else{
		// Cache vide : pas de compteurs a zero, l'etat vide du tableau explique la situation.
		if($m["total"]>0){$w[]=dnsc_tiles($m);}
		$w[]=dnsc_table($m);
	}
	$attrs=" data-q=\"".dnsc_esc($m["q"])."\" data-f='{$m["f"]}' data-max='".intval($m["max"])."' data-p='".intval($m["p"])."'";
	return dnsc_style().$tpl->_ENGINE_parse_body("<div class='dnsc' id='dnsc-panel'$attrs>".@implode("\n",$w)."</div>").dnsc_js()."<script>NoSpinner();</script>";
}

/** Pager serveur : precedent, fenetre de pages autour de la courante, suivant. */
function dnsc_pager(array $m):string{
	if($m["pages"]<2){return "";}
	$p=$m["p"];$n=$m["pages"];
	$b=array();
	$b[]="<button type='button'".($p<=1?" disabled":" OnClick=\"DnscPage(".($p-1).");\"").">{dnsc_prev}</button>";
	$start=max(1,$p-2);$end=min($n,$start+4);$start=max(1,$end-4);
	if($start>1){$b[]="<button type='button' OnClick=\"DnscPage(1);\">1</button>";if($start>2){$b[]="<span class='dnsc-gap'>&hellip;</span>";}}
	for($i=$start;$i<=$end;$i++){
		$b[]=($i==$p)?"<button type='button' class='cur' aria-current='page'>$i</button>":"<button type='button' OnClick=\"DnscPage($i);\">$i</button>";
	}
	if($end<$n){if($end<$n-1){$b[]="<span class='dnsc-gap'>&hellip;</span>";}$b[]="<button type='button' OnClick=\"DnscPage($n);\">$n</button>";}
	$b[]="<button type='button'".($p>=$n?" disabled":" OnClick=\"DnscPage(".($p+1).");\"").">{dnsc_next}</button>";
	return "<nav class='dnsc-pg' aria-label=\"{dnsc_pages_label}\">".@implode("",$b)."</nav>";
}

/** Vider tout le cache : seulement quand il y a quelque chose a vider. */
function dnsc_flush_all_button(array $m):string{
	if($m["total"]==0){return "";}
	$page=CurrentPageName();
	return "<div><button type='button' class='dnsc-btn dnsc-btn-warn' OnClick=\"Loadjs('$page?flush-all-js=yes');\"><i class='fa-solid fa-broom' aria-hidden='true'></i>{dnsc_flush_all}</button>
	<div class='dnsc-help'>{dnsc_flush_all_help}</div></div>";
}

// ---------------------------------------------------------------------------
// "Forget this answer" : confirmation, puis POST.

function forget_js(){
	$host=(string)($_GET["forget-js"] ?? "");
	header("content-type: application/x-javascript");
	if(!dnscache_valid_host($host)){return;}
	$tpl=new template_admin();
	// js_dialog_confirm_action() coupe la ligne apres chaque "." du texte : les points
	// du nom passent en entite &#46; (le nom est deja restreint a [A-Za-z0-9._-]).
	$name="<b>".str_replace(".","&#46;",$host)."</b>";
	$text=str_replace("%s",$name,$tpl->_ENGINE_parse_body("{dnsc_forget_confirm}"));
	$tpl->js_dialog_confirm_action($text,"dnsc-forget",$host,"DnscReload();");
}

function forget_save(){
	$users=new usersMenus();
	if(!$users->AsDnsAdministrator){
		admin_tracks("Refused a DNS cache flush: insufficient privileges");
		echo (new template_admin())->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		return;
	}
	$host=trim((string)$_POST["dnsc-forget"]);
	if(!dnscache_valid_host($host)){echo (new template_admin())->_ENGINE_parse_body("{dnsc_bad_name}");return;}
	// POST /unbound/cache/flush : articarest revalide le nom et appelle
	// unbound-control flush_zone sans shell (le nom et tout ce qui est en dessous).
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/unbound/cache/flush",array("name"=>$host)));
	if(!is_object($json) || empty($json->Status)){
		$err="articarest: no answer";
		if(is_object($json) && isset($json->Error)){$err=(string)$json->Error;}
		elseif(is_object($json) && isset($json->error)){$err=(string)$json->error;}
		echo htmlspecialchars($err);
		return;
	}
	admin_tracks("Flushed DNS cache entry $host (and the names under it)");
}

// ---------------------------------------------------------------------------
// "Empty the whole cache" : confirmation, puis POST /unbound/cache/flush/all.

function flush_all_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	// "none" : js_dialog_confirm_action() n'ajoute alors aucune valeur au message.
	$tpl->js_dialog_confirm_action("{dnsc_flush_all_confirm}","dnsc-flush-all","none","DnscReload();");
}

function flush_all_save(){
	$users=new usersMenus();
	if(!$users->AsDnsAdministrator){
		admin_tracks("Refused a DNS cache flush: insufficient privileges");
		echo (new template_admin())->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		return;
	}
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/unbound/cache/flush/all",array()));
	if(!is_object($json) || empty($json->Status)){
		$err="articarest: no answer";
		if(is_object($json) && isset($json->Error)){$err=(string)$json->Error;}
		echo htmlspecialchars($err);
		return;
	}
	admin_tracks("Emptied the whole DNS resolver cache");
}
