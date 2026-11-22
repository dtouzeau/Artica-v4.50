<?php
// fw.ufdbweb.requests.php - Web filtering > Error page > "Requests list" tab.
// Maquette stitch_access_requests_redesign (2026-11-19).
// A request (ticket) is written by artica-error-page (Tickets.Insert) into webfilter.db
// webfilters_usersasks when a user clicks "request access" on the blocked page:
// zmd5, zDate, ipaddr, sitename, uid and info = base64 of the "||" history
// (BuildHist.Parse: 0 URL, 1 category code, 2 client address, 3 client name, 4 user,
// 5 group, 6 ACL, 7 domain, 8 real IP, 9 rule id, 10 web-filtering rule name,
// 11 category name, 12 error-page rule zmd5). Older rows hold a PHP serialize() instead.
// Allowing a request does what the e-mail link does (Tickets.TicketUnlock): one row in
// ufdbunlock for the error-page rule duration (maxtime minutes, 0 = until removed, i.e.
// 5184000 minutes), the ticket deleted, the whitelist recompiled.
// Left out of the mock-up because invented: nothing beyond the data above; the console
// shell, the tab counter, the "Pagination is enabled…" caption.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["accept-js"])){accept_js();exit;}
if(isset($_POST["accept-request"])){accept_request();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}

page();

function page(){
	$page=CurrentPageName();
	echo "<div style='margin-top:10px' id='ufdbweb-requests-div'></div>
	<script>LoadAjax('ufdbweb-requests-div','$page?table=yes');</script>";
}

// Who may act on a request (the tab is also shown, read-only, to AsProxyMonitor).
function areq_can_act():bool{
	$users=new usersMenus();
	return (bool)($users->AsDansGuardianAdministrator || $users->AsSquidAdministrator);
}

function areq_tr($token):string{
	$tpl=new template_admin();
	$t=$tpl->_ENGINE_parse_body("{".$token."}");
	$args=func_get_args(); array_shift($args);
	foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
	return $t;
}

function areq_esc($v):string{
	return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

function areq_md5($v):string{
	return preg_replace('#[^A-Za-z0-9]#','',(string)$v);
}

// Error-page rules, zmd5-keyed: name and unblock duration (minutes, 0 = until removed).
function areq_page_rules():array{
	if(isset($GLOBALS["AREQ_RULES"])){return $GLOBALS["AREQ_RULES"];}
	$out=array();
	$list=acls_api_get("/webfilter/page-rules");
	if(is_array($list)){
		foreach($list as $a){
			if(!is_array($a)){continue;}
			$id=areq_md5($a["zmd5"] ?? "");
			if($id===""){continue;}
			$out[$id]=array("name"=>(string)($a["ruleName"] ?? $a["rulename"] ?? ""),"maxtime"=>intval($a["maxtime"] ?? 0));
		}
	}
	$GLOBALS["AREQ_RULES"]=$out;
	return $out;
}

// One ticket row decoded into a plain array (never trusted: it comes from the blocked page).
function areq_decode(array $ligne):array{
	$R=array("md5"=>areq_md5($ligne["zmd5"] ?? ""),"time"=>intval($ligne["zDate"] ?? 0),
		"site"=>trim((string)($ligne["sitename"] ?? "")),"ip"=>trim((string)($ligne["ipaddr"] ?? "")),
		"uid"=>trim((string)($ligne["uid"] ?? "")),"url"=>"","host"=>"","category"=>"","wfrule"=>"","pagerule"=>"",
		"maxtime"=>-1,"info"=>(string)($ligne["info"] ?? ""));
	$decoded=base64_decode($R["info"],true);
	if(is_string($decoded) && strpos($decoded,"||")!==false){
		$x=explode("||",$decoded);
		$R["url"]=trim((string)($x[0] ?? ""));
		$R["host"]=trim((string)($x[3] ?? ""));
		if($R["uid"]===""){ $R["uid"]=trim((string)($x[4] ?? "")); }
		if($R["site"]===""){ $R["site"]=trim((string)($x[7] ?? "")); }
		if($R["ip"]===""){ $R["ip"]=trim((string)($x[8] ?? $x[2] ?? "")); }
		$R["wfrule"]=trim((string)($x[10] ?? ""));
		$R["category"]=trim((string)($x[11] ?? ""));
		$R["pagerule"]=areq_md5($x[12] ?? "");
	}elseif(is_string($decoded) && $decoded!==""){
		$M=@unserialize($decoded,array("allowed_classes"=>false));
		if(is_array($M)){
			$R["url"]=trim((string)($M["URL"] ?? ""));
			$R["host"]=trim((string)($M["CLIENT_HOSTNAME"] ?? ""));
			$R["category"]=trim((string)($M["CATEGORY_NAME"] ?? ""));
			if($R["site"]===""){ $R["site"]=trim((string)($M["SITENAME"] ?? "")); }
			if(isset($M["maxtime"])){ $R["maxtime"]=intval($M["maxtime"]); }
		}
	}
	if(strtolower($R["uid"])==="unknown" || $R["uid"]==="-"){ $R["uid"]=""; }
	if($R["host"]===$R["ip"]){ $R["host"]=""; }
	if($R["maxtime"]<0){
		$rules=areq_page_rules();
		$R["maxtime"]=isset($rules[$R["pagerule"]])?$rules[$R["pagerule"]]["maxtime"]:0;
	}
	if($R["maxtime"]<0){ $R["maxtime"]=0; }
	return $R;
}

function areq_model():array{
	$M=array("error"=>"","rows"=>array(),"oldest"=>0);
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$results=$q->QUERY_SQL("SELECT * FROM `webfilters_usersasks` ORDER BY zDate LIMIT 500");
	if(!$q->ok){ $M["error"]=(string)$q->mysql_error; return $M; }
	if(!is_array($results)){ $results=array(); }
	foreach($results as $ligne){
		if(!is_array($ligne)){continue;}
		$r=areq_decode($ligne);
		if($r["md5"]===""){continue;}
		$M["rows"][]=$r;
		if($r["time"]>0 && ($M["oldest"]==0 || $r["time"]<$M["oldest"])){ $M["oldest"]=$r["time"]; }
	}
	return $M;
}

// "2 h ago", "3 days ago": relative age of a timestamp.
function areq_age(int $t):string{
	if($t<=0){ return "—"; }
	$d=max(0,time()-$t);
	if($d<3600){ return areq_tr("areq_ago_min",max(1,intval($d/60))); }
	if($d<86400){ return areq_tr("areq_ago_h",intval($d/3600)); }
	$n=intval($d/86400);
	return $n==1?areq_tr("areq_ago_1d"):areq_tr("areq_ago_d",$n);
}

function areq_duration(int $maxtime):string{
	if($maxtime<=0){ return areq_tr("areq_dur_forever"); }
	if($maxtime<60){ return areq_tr("areq_dur_min",$maxtime); }
	if($maxtime<1440 && $maxtime%60==0){ return areq_tr("areq_dur_h",intval($maxtime/60)); }
	if($maxtime<1440){ return areq_tr("areq_dur_min",$maxtime); }
	$d=round($maxtime/1440,1);
	return $d==1?areq_tr("areq_dur_1d"):areq_tr("areq_dur_d",$d);
}

// The site as it will be opened: its family (the whole site and its sub-domains).
function areq_family($site):string{
	$site=strtolower(trim((string)$site));
	if(preg_match("#^(www|ww1|ww2|ww3)\.(.+)#",$site,$re)){ $site=$re[2]; }
	if($site===""){ return ""; }
	$fam=new familysite();
	$f=trim((string)$fam->GetFamilySites($site));
	return $f===""?$site:$f;
}

// Who will get access, in words (IP always, user name when known).
function areq_who(array $r):string{
	if($r["uid"]!=="" && $r["ip"]!==""){ return areq_esc($r["uid"])." (".areq_esc($r["ip"]).")"; }
	if($r["ip"]!==""){ return areq_esc($r["ip"]); }
	if($r["uid"]!==""){ return areq_esc($r["uid"]); }
	return "";
}

function areq_consequence(array $r):string{
	$fam="*.".areq_esc(areq_family($r["site"]));
	$who=areq_who($r);
	if($who===""){ return $r["maxtime"]>0?areq_tr("areq_opens_all_for",$fam,areq_esc(areq_duration($r["maxtime"]))):areq_tr("areq_opens_all_forever",$fam); }
	return $r["maxtime"]>0?areq_tr("areq_opens_for",$fam,$who,areq_esc(areq_duration($r["maxtime"]))):areq_tr("areq_opens_forever",$fam,$who);
}

// ---- the tab ----------------------------------------------------------------------------------
function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$M=areq_model();
	$h=array();
	$h[]="<div class='areq'>";
	if($M["error"]!==""){
		$h[]=$tpl->div_error(areq_esc($M["error"]));
	}else{
		$h[]=areq_banner($M);
		if(count($M["rows"])==0){
			$h[]=areq_empty();
		}else{
			$h[]=areq_toolbar($M);
			$h[]=areq_list($M);
		}
	}
	$h[]="</div>";
	$topbuttons=array();
	$topbuttons[]=array("LoadAjax('ufdbweb-requests-div','$page?table=yes');",ico_refresh,"{refresh}");
	$TINY_ARRAY=array();
	$TINY_ARRAY["TITLE"]="{requests_list}";
	$TINY_ARRAY["ICO"]="fa-solid fa-user-tag";
	$TINY_ARRAY["EXPL"]="{areq_tiny_explain}";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	echo areq_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>NoSpinner();".areq_js()."\n$jstiny</script>";
}

function areq_banner(array $M):string{
	$rules=areq_page_rules();
	$timed=false; $forever=false;
	foreach($M["rows"] as $r){ if($r["maxtime"]>0){$timed=true;}else{$forever=true;} }
	if(count($M["rows"])==0){ foreach($rules as $ru){ if($ru["maxtime"]>0){$timed=true;}else{$forever=true;} } }
	$chips="<span class='ak-tag ak-info'><i class='fas fa-user-check'></i>{areq_chip_person}</span>";
	if($timed){ $chips.="<span class='ak-tag ak-info'><i class='fas fa-hourglass-half'></i>{areq_chip_ends}</span>"; }
	if($forever){ $chips.="<span class='ak-tag ak-info'><i class='fas fa-infinity'></i>{areq_chip_forever}</span>"; }
	$chips.="<span class='ak-tag ak-info'><i class='fas fa-clipboard-check'></i>{areq_chip_logged}</span>";
	return "<section class='areq-banner'><span class='areq-banner-ico'><i class='fas fa-hand'></i></span><div>"
		."<h3>{areq_banner_title}</h3><p>{areq_banner_text}</p><div class='ak-chips'>$chips</div></div></section>";
}

function areq_toolbar(array $M):string{
	$page=CurrentPageName();
	$n=count($M["rows"]);
	$sum=areq_tr($n==1?"areq_waiting_1":"areq_waiting_n","<b>$n</b>");
	if($M["oldest"]>0){ $sum.=" <span class='areq-dot'>·</span> ".areq_tr("areq_oldest","<b>".areq_esc(areq_age($M["oldest"]))."</b>"); }
	$decline="";
	if(areq_can_act()){
		$decline="<button type='button' class='ak-btn areq-btn-danger' title=\"{areq_decline_all_help}\" OnClick=\"Loadjs('$page?empty-js=yes');\"><i class='fas fa-trash-can'></i>{areq_decline_all}</button>";
	}
	if(!areq_can_act()){ $sum.="<div class='areq-ro'><i class='fas fa-eye'></i>{areq_readonly}</div>"; }
	return "<div class='areq-bar'><div class='areq-sum'>$sum</div><div class='areq-tools'>"
		."<label class='ak-search areq-search'><i class='fas fa-magnifying-glass'></i><input type='search' id='areq-q' placeholder=\"{areq_search}\" aria-label=\"{areq_search}\" oninput='AreqApply()'></label>"
		."$decline</div></div>";
}

function areq_empty():string{
	return "<div class='ak-card ak-empty-c areq-empty'><i class='fas fa-inbox'></i><b>{areq_empty_title}</b><span>{areq_empty_text}</span>"
		."<button type='button' class='ak-btn' OnClick=\"var t=document.querySelector('a[data-toggle=tab][data-url^=\\'fw.ufdbweb.rules.php\\']');if(t){t.click();}else{LoadAjax('ufdbweb-requests-div','fw.ufdbweb.rules.php?table2=yes');}\"><i class='fas fa-arrow-right'></i>{areq_open_rules}</button></div>";
}

function areq_list(array $M):string{
	$h=array("<ul class='areq-list' id='areq-list'>");
	foreach($M["rows"] as $r){ $h[]=areq_card($r); }
	$h[]="</ul>";
	$h[]="<div class='areq-nomatch' id='areq-nomatch' hidden>{areq_nomatch}</div>";
	$h[]="<div class='areq-foot'><span id='areq-count'></span><span class='areq-pg' id='areq-pager'></span></div>";
	return @implode("",$h);
}

function areq_card(array $r):string{
	$page=CurrentPageName();
	$site=$r["site"]!==""?$r["site"]:"—";
	$waiting=($r["time"]>0 && time()-$r["time"]>86400)
		?"<span class='ak-pill ak-warn'><i class='fas fa-clock'></i>".areq_tr("areq_waiting_since",areq_esc(areq_age_short($r["time"])))."</span>":"";
	$url=$r["url"]!==""?"<div class='areq-url' title=\"".areq_esc($r["url"])."\">".areq_esc($r["url"])."</div>":"";
	$who=$r["uid"]!==""?"<b>".areq_esc($r["uid"])."</b>":"<span class='areq-muted'>{areq_unidentified}</span>";
	$where=array();
	if($r["ip"]!==""){ $where[]=areq_esc($r["ip"]); }
	if($r["host"]!==""){ $where[]=areq_esc($r["host"]); }
	$cat=$r["category"]!==""?"<span class='ak-tag ak-off'><i class='fas fa-ban'></i>".areq_esc($r["category"])."</span>":"<span class='areq-muted'>—</span>";
	$rule=$r["wfrule"]!==""?"<div class='areq-sub'>".areq_tr("areq_rule",areq_esc($r["wfrule"]))."</div>":"";
	$date=$r["time"]>0?date("Y-m-d H:i",$r["time"]):"";
	$acts="";
	if(areq_can_act()){
		$acts="<div class='areq-acts'><button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?accept-js=".$r["md5"]."');\"><i class='fas fa-unlock'></i>{areq_allow}</button>"
			."<button type='button' class='ak-btn ak-btn-ghost' OnClick=\"Loadjs('$page?delete-js=".$r["md5"]."');\"><i class='fas fa-xmark'></i>{areq_decline}</button></div>"
			."<p class='areq-cons'>".areq_consequence($r)."</p>";
	}else{
		$acts="";
	}
	$search=strtolower($site." ".$r["url"]." ".$r["uid"]." ".$r["ip"]." ".$r["host"]." ".$r["category"]);
	$aria=areq_tr("areq_card_aria",areq_esc($r["uid"]!==""?$r["uid"]:$r["ip"]),areq_esc($site));
	return "<li class='ak-card areq-card' id='".$r["md5"]."' data-s=\"".areq_esc($search)."\" aria-label=\"$aria\">"
		."<div class='areq-site'><span class='ak-ico ak-ico-sm ak-off ak-ico-sq'><i class='fas fa-globe'></i></span><div class='areq-site-t'>"
		."<div class='areq-dom'><b>".areq_esc($site)."</b>$waiting</div>$url"
		."<div class='areq-ref' title=\"".areq_esc($r["md5"])."\">".areq_tr("areq_ref",substr($r["md5"],0,6))."…</div></div></div>"
		."<div class='areq-facts'>"
		."<div><div class='areq-k'><i class='fas fa-user'></i>{areq_who_k}</div><div class='areq-v'>$who</div><div class='areq-sub areq-mono'>".@implode(" · ",$where)."</div></div>"
		."<div><div class='areq-k'><i class='fas fa-ban'></i>{areq_why}</div><div class='areq-v'>$cat</div>$rule</div>"
		."<div><div class='areq-k'><i class='fas fa-clock'></i>{areq_asked}</div><div class='areq-v' title=\"$date\">".areq_esc(areq_age($r["time"]))."</div>"
		."<div class='areq-sub'>".areq_tr("areq_dur_line",areq_esc(areq_duration($r["maxtime"])))."</div></div>"
		."</div><div class='areq-side'>$acts</div></li>";
}

function areq_age_short(int $t):string{
	$d=max(0,time()-$t);
	$n=intval($d/86400);
	return $n<=1?areq_tr("areq_1day"):areq_tr("areq_ndays",$n);
}

// ---- actions -----------------------------------------------------------------------------------
function areq_row($md5):array{
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	// Pentest 2026-11-04 : the ticket id was concatenated into the query.
	$r=$q->QUERY_SQL_PARAMS("SELECT * FROM webfilters_usersasks WHERE `zmd5`=?",array($md5));
	return (is_array($r) && isset($r[0]) && is_array($r[0]))?$r[0]:array();
}

function accept_js(){
	$tpl=new template_admin();
	if(!areq_can_act()){$tpl->js_no_privileges();return;}
	$page=CurrentPageName();
	$md5=areq_md5($_GET["accept-js"] ?? "");
	$ligne=areq_row($md5);
	if(count($ligne)==0){$tpl->js_error("{areq_gone}");return;}
	$r=areq_decode($ligne);
	$okText=$r["maxtime"]>0?areq_tr("areq_allowed_text",areq_esc(areq_duration($r["maxtime"]))):areq_tr("areq_allowed_forever");
	echo areq_confirm_js(areq_tr("areq_allow_confirm",areq_esc($r["site"])),areq_consequence($r)."<br>".$tpl->_ENGINE_parse_body("{areq_allow_confirm_note}"),
		"{areq_allow}",false,"accept-request",$md5,"{areq_allowed_title}",$okText);
}

// POST: does what the e-mail link does (Tickets.TicketUnlock). Echoes only an error.
function accept_request(){
	$tpl=new template_admin();
	if(!areq_can_act()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
	$md5=areq_md5($_POST["accept-request"] ?? "");
	$ligne=areq_row($md5);
	if(count($ligne)==0){echo $tpl->post_error("{areq_gone}");return;}
	$r=areq_decode($ligne);
	$family=areq_family($r["site"]);
	if($family===""){echo $tpl->post_error("{areq_no_site}");return;}
	$minutes=$r["maxtime"]>0?$r["maxtime"]:5184000;
	$end=time()+$minutes*60;
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO ufdbunlock (`md5`,`logintime`,`finaltime`,`uid`,`www`,`ipaddr`,`details`) VALUES (?,?,?,?,?,?,?)",
		array($md5,time(),$end,$r["uid"],$family,$r["ip"],$r["info"]));
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return;}
	$q->QUERY_SQL_PARAMS("DELETE FROM webfilters_usersasks WHERE `zmd5`=?",array($md5));
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/whitelists/nohupcompile");
	admin_tracks("Accept unblock ticket $md5: *.$family for ".($r["uid"]!==""?$r["uid"]." ":"").$r["ip"]." until ".date("Y-m-d H:i",$end));
}

function delete_js(){
	$tpl=new template_admin();
	if(!areq_can_act()){$tpl->js_no_privileges();return;}
	$page=CurrentPageName();
	$md5=areq_md5($_GET["delete-js"] ?? "");
	$ligne=areq_row($md5);
	if(count($ligne)==0){$tpl->js_error("{areq_gone}");return;}
	$r=areq_decode($ligne);
	$who=areq_who($r);
	$tpl->js_confirm_delete(trim(($who!==""?"$who · ":"").areq_esc($r["site"])),"delete",$md5,"LoadAjaxSilent('ufdbweb-requests-div','$page?table=yes');");
}

function delete(){
	$tpl=new template_admin();
	if(!areq_can_act()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
	$md5=areq_md5($_POST["delete"] ?? "");
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL_PARAMS("DELETE FROM webfilters_usersasks WHERE `zmd5`=?",array($md5));
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return;}
	admin_tracks("Decline unblock ticket $md5");
}

function empty_js(){
	$tpl=new template_admin();
	if(!areq_can_act()){$tpl->js_no_privileges();return;}
	echo areq_confirm_js("{areq_decline_all_title}","{areq_decline_all_text}","{areq_decline_all}",true,"empty","yes","{areq_declined_title}","{areq_declined_text}");
}

function empty_table(){
	$tpl=new template_admin();
	if(!areq_can_act()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL("DELETE FROM webfilters_usersasks");
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return;}
	admin_tracks("Decline every waiting unblock ticket");
}

// Styled confirmation, then POST $field=$value; an empty answer is a success (skill §5).
// Not js_dialog_confirm_action(): it turns every "." into a line break (it would cut
// "youtube.com") and appends the raw value to its success text; js_confirm_empty() does too.
function areq_confirm_js($title,$body,$yes,$danger,$field,$value,$okTitle,$okText):string{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$plain=function($t) use ($tpl){ return html_entity_decode($tpl->_ENGINE_parse_body((string)$t),ENT_QUOTES,"UTF-8"); };
	$html=function($t) use ($tpl){ return str_replace("[br]","<br>",$tpl->_ENGINE_parse_body((string)$t)); };
	header("content-type: application/x-javascript");
	return "swal({title:".json_encode($plain($title)).",text:".json_encode($html($body)).",html:true,type:'".($danger?"warning":"info")."',showCancelButton:true,"
		."confirmButtonColor:'".($danger?"#ba1a1a":"#0f766e")."',confirmButtonText:".json_encode($plain($yes)).",cancelButtonText:".json_encode($plain("{areq_cancel}")).",closeOnConfirm:false},function(){
 var x=new XHRConnection();x.appendData(".json_encode((string)$field).",".json_encode((string)$value).");
 x.sendAndLoad('$page','POST',function(o){var t=String(o.responseText||'').trim();
  if(t.length>3){ if(t.indexOf('jserror:')===0){t=t.substring(8);} swal({title:'Oops...',text:t,html:true,type:'error'}); return; }
  swal({title:".json_encode($plain($okTitle)).",text:".json_encode($html($okText)).",html:true,type:'success'});
  LoadAjaxSilent('ufdbweb-requests-div','$page?table=yes');
 });
});";
}

// ---- assets ------------------------------------------------------------------------------------
// Search and pagination in one pass: the filter decides which cards exist, the pager slices them.
function areq_js():string{
	$tpl=new template_admin();
	$shown=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{areq_showing}"),ENT_QUOTES,"UTF-8"));
	return "window.AreqShown=$shown;window.AreqPage=1;
window.AreqApply=function(p){
 var L=document.getElementById('areq-list');if(!L){return;}
 if(typeof p==='number'){window.AreqPage=p;}else{window.AreqPage=1;}
 var q=(document.getElementById('areq-q')||{value:''}).value.trim().toLowerCase(),size=25,m=[],i;
 for(i=0;i<L.children.length;i++){var c=L.children[i];if(q===''||(c.getAttribute('data-s')||'').indexOf(q)>=0){m.push(c);}c.style.display='none';}
 var pages=Math.max(1,Math.ceil(m.length/size));if(window.AreqPage>pages){window.AreqPage=pages;}
 var a=(window.AreqPage-1)*size,b=Math.min(m.length,a+size);
 for(i=a;i<b;i++){m[i].style.display='';}
 var cnt=document.getElementById('areq-count');if(cnt){cnt.textContent=m.length?window.AreqShown.replace('%s',a+1).replace('%s',b).replace('%s',m.length):'';}
 var nm=document.getElementById('areq-nomatch');if(nm){nm.hidden=m.length>0;}
 var pg=document.getElementById('areq-pager');if(!pg){return;}
 if(pages<2){pg.innerHTML='';return;}
 var h='';for(i=1;i<=pages;i++){h+='<button type=\"button\" class=\"'+(i===window.AreqPage?'cur':'')+'\" onclick=\"AreqApply('+i+')\">'+i+'</button>';}
 pg.innerHTML=h;
};
AreqApply();";
}

function areq_style():string{
	if(isset($GLOBALS["AREQ_STYLE"])){return "";}
	$GLOBALS["AREQ_STYLE"]=true;
	$A="#0d9488";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){ $a=brand_ui_accents(brand_palette()); if(!empty($a["accent"])){$A=$a["accent"];} }
	return "<style>
.areq{color:#0f172a;font-size:14px}
.areq *{box-sizing:border-box}
.areq p{margin:0}
.areq-banner{display:flex;gap:16px;align-items:flex-start;background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;padding:18px 20px;margin-bottom:18px}
.areq-banner-ico{flex:0 0 44px;height:44px;border-radius:10px;background:#dbeafe;color:#1d4ed8;display:inline-flex;align-items:center;justify-content:center;font-size:20px}
.areq-banner h3{font-size:17px;font-weight:600;color:#1e3a8a;margin:0 0 6px}
.areq-banner p{font-size:14px;color:#334155;line-height:1.55;margin-bottom:12px;max-width:920px}
.areq-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.areq-sum{font-size:15px;color:#475569}
.areq-sum b{color:#0f172a}
.areq-dot{color:#94a3b8;margin:0 4px}
.areq-ro{font-size:13px;color:#64748b;margin-top:4px;display:flex;align-items:center;gap:6px}
.areq-tools{display:flex;align-items:center;gap:10px}
.areq-search{min-width:320px}
.areq .areq-btn-danger{border-color:#fecaca;color:#ba1a1a!important;background:#fff}
.areq .areq-btn-danger:hover{background:#fef2f2}
.areq-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:12px}
.areq-card{display:grid;grid-template-columns:minmax(240px,1.3fr) minmax(420px,2fr) minmax(260px,1fr);gap:20px;align-items:center;padding:16px 20px}
.areq-site{display:flex;gap:12px;align-items:flex-start;min-width:0}
.areq-site-t{min-width:0;flex:1}
.areq-dom{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.areq-dom b{font-size:16px;font-weight:600;word-break:break-all}
.areq-url{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:3px}
.areq-ref{font-size:13px;color:#94a3b8;margin-top:3px}
.areq-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.areq-k{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#64748b;margin-bottom:4px}
.areq-v{font-size:14px;min-width:0}
.areq-v b{font-weight:600}
.areq-sub{font-size:13px;color:#64748b;margin-top:3px;line-height:1.45}
.areq-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.areq-muted{color:#64748b}
.areq-side{display:flex;flex-direction:column;align-items:flex-end;gap:8px;text-align:right}
.areq-acts{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.areq-cons{font-size:13px;color:#475569;line-height:1.45}
.areq-empty{padding:44px 20px}
.areq-empty span{color:#64748b;max-width:520px}
.areq-nomatch{padding:24px;text-align:center;color:#64748b;font-size:14px}
.areq-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;font-size:13px;color:#64748b}
.areq-pg{display:flex;gap:6px}
.areq-pg button{min-width:34px;height:34px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#0f172a;font-size:13px;font-weight:600;cursor:pointer}
.areq-pg button.cur{background:$A;border-color:$A;color:#fff}
.areq .ak-btn:focus-visible,.areq-pg button:focus-visible,.areq-search input:focus-visible{outline:2px solid $A;outline-offset:2px}
@media(max-width:1200px){.areq-card{grid-template-columns:1fr}.areq-side{align-items:flex-start;text-align:left}.areq-acts{justify-content:flex-start}}
@media (prefers-reduced-motion:no-preference){.areq-card{transition:box-shadow .15s}.areq-card:hover{box-shadow:0 2px 6px rgba(15,23,42,.08)}}
</style>";
}
