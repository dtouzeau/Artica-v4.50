<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
// Verrou de la politique Artica Meta "firewall" : regles tenues = horaires en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["rule-save"])){save();exit;}
page();

// Onglet « Time restriction » d'une regle (maquette stitch_firewall_rules_time).
// Un modele lu une fois, un widget par fonction, CSS hors de _ENGINE_parse_body().

function fwt_clock($v,string $def):string{
	$v=trim((string)$v);
	if(!preg_match('/^([0-9]{1,2}):([0-9]{2})/',$v,$re)){return $def;}
	$h=intval($re[1]);$mi=intval($re[2]);
	if($h>23 || $mi>59){return $def;}
	return sprintf("%02d:%02d",$h,$mi);
}

function fwt_model(int $ID):array{
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	// intval : l'identifiant etait interpole tel quel dans la requete (injection SQL).
	$ligne=$q->mysqli_fetch_array("SELECT rulename,enablet,time_restriction FROM iptables_main WHERE ID='$ID'");
	if(!is_array($ligne)){$ligne=array();}
	// Regle sans horaire : time_restriction vide, unserialize() rendait false.
	$T=@unserialize((string)($ligne["time_restriction"] ?? ""),array("allowed_classes"=>false));
	if(!is_array($T)){$T=array();}
	$m=array("id"=>$ID,"name"=>(string)($ligne["rulename"] ?? ""),"enabled"=>intval($ligne["enablet"] ?? 0)==1);
	$m["from"]=fwt_clock($T["ftime"] ?? "","20:00");
	$m["to"]=fwt_clock($T["ttime"] ?? "","23:59");
	$m["days"]=array();
	for($i=1;$i<=7;$i++){$m["days"][$i]=intval($T["D$i"] ?? 0)==1;}

	// Heures locales : articarest les convertit en UTC a la pose
	// (Rules.ExtractFirewallRuleTimes) et reconstruit au changement d'heure.
	$tz=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("timezones"));
	if($tz=="" || !in_array($tz,timezone_identifiers_list(),true)){$tz=date_default_timezone_get();}
	$m["tz"]=$tz;
	$m["utc_now"]=gmdate("H:i");
	try{
		$d=new DateTime("now",new DateTimeZone($tz));
		$m["local_now"]=$d->format("H:i");
	}catch(Exception $e){$m["local_now"]=$m["utc_now"];}
	return $m;
}

function fwt_summary(array $m):string{
	$cls=$m["enabled"]?"is-on":"is-off";
	// Trois icones rendues, la CSS montre celle de l'etat : une icone FA en SVG ne se pilote pas par className.
	return "<section class='fwt-sum $cls' id='fwt-sum' aria-live='polite'>"
		."<span class='fwt-sum-i' aria-hidden='true'><span class='i-on'><i class='fa-solid fa-clock'></i></span><span class='i-off'><i class='fa-solid fa-infinity'></i></span><span class='i-bad'><i class='fa-solid fa-triangle-exclamation'></i></span></span>"
		."<div class='fwt-sum-t'><b id='fwt-sum-text'></b>"
		."<small><i class='fa-solid fa-globe' aria-hidden='true'></i> {fwt_tz_local} <span class='fwt-tz'>".htmlspecialchars($m["tz"])."</span> &middot; {fwt_now} <span class='fwt-mono'>{$m["local_now"]}</span></small></div></section>";
}

function fwt_switch(array $m):string{
	$v=$m["enabled"]?"true":"false";
	return "<section class='fwt-row'><div><b id='fwt-sw-l'>{fwt_enable}</b><small>{fwt_enable_help}</small></div>"
		."<div class='fwt-row-r'><span class='fwt-state' id='fwt-state'></span>"
		."<button type='button' class='fwt-sw' id='fwt-sw' role='switch' aria-checked='$v' aria-labelledby='fwt-sw-l'><span aria-hidden='true'></span></button></div></section>";
}

function fwt_card_range(array $m):string{
	$ticks=array();
	for($h=0;$h<=24;$h+=3){$ticks[]="<span>".sprintf("%02d",$h).":00</span>";}
	return "<section class='fwt-card'><div class='fwt-card-h'><h3>{fwt_range}</h3><span class='fwt-muted'>{fwt_24h}</span></div>"
		."<div class='fwt-times'>"
		."<div class='fwt-f'><label for='fwt-from'>{fwt_start}</label><input type='time' id='fwt-from' class='fwt-mono' step='60' value='{$m["from"]}' required></div>"
		."<i class='fa-solid fa-arrow-right fwt-arrow' aria-hidden='true'></i>"
		."<div class='fwt-f'><label for='fwt-to'>{fwt_end}</label><input type='time' id='fwt-to' class='fwt-mono' step='60' value='{$m["to"]}' required></div>"
		."<span class='fwt-dur'><i class='fa-solid fa-hourglass-half' aria-hidden='true'></i>{fwt_duration} <b class='fwt-mono' id='fwt-dur'></b></span>"
		."</div>"
		."<div class='fwt-bar' aria-hidden='true'><span id='fwt-fill'></span></div>"
		."<div class='fwt-ticks' aria-hidden='true'>".implode("",$ticks)."</div>"
		."<div class='fwt-err' id='fwt-range-err' role='alert' hidden><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i> {fwt_order}</div>"
		."<p class='fwt-help'><i class='fa-solid fa-circle-info' aria-hidden='true'></i> {fwt_midnight}</p></section>";
}

function fwt_card_days(array $m):string{
	$short=array(1=>"{fwt_mon}",2=>"{fwt_tue}",3=>"{fwt_wed}",4=>"{fwt_thu}",5=>"{fwt_fri}",6=>"{fwt_sat}",7=>"{fwt_sun}");
	$full=array(1=>"{monday}",2=>"{tuesday}",3=>"{wednesday}",4=>"{thursday}",5=>"{friday}",6=>"{saturday}",7=>"{sunday}");
	$chips=array();
	foreach($short as $i=>$s){
		$on=$m["days"][$i]?"true":"false";
		$chips[]="<button type='button' class='fwt-chip' data-day='$i' aria-pressed='$on' title=\"{$full[$i]}\"><i class='fa-solid fa-check' aria-hidden='true'></i>$s</button>";
	}
	return "<section class='fwt-card'><div class='fwt-card-h'><h3 id='fwt-days-h'>{fwt_days}</h3>"
		."<span class='fwt-short' role='group' aria-label=\"{fwt_shortcuts}\">"
		."<button type='button' data-set='1,2,3,4,5'>{fwt_weekdays}</button><button type='button' data-set='6,7'>{fwt_weekend}</button>"
		."<button type='button' data-set='1,2,3,4,5,6,7'>{fwt_everyday}</button><button type='button' data-set=''>{fwt_clear}</button></span></div>"
		."<div class='fwt-chips' role='group' aria-labelledby='fwt-days-h'>".implode("",$chips)."</div>"
		."<p class='fwt-help'><i class='fa-solid fa-circle-info' aria-hidden='true'></i> {fwt_noday}</p></section>";
}

function fwt_footer():string{
	return "<div class='fwt-err' id='fwt-save-err' role='alert' hidden><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i> <span id='fwt-save-err-t'></span></div>"
		."<div class='fwt-foot'><button type='button' class='fwt-btn' id='fwt-apply'><i class='fa-solid fa-floppy-disk' aria-hidden='true'></i><span id='fwt-apply-t'>{fwt_apply}</span></button></div>";
}

function fwt_style():string{
	$acc="#10b981";$dark="#047857";$soft="rgba(16,185,129,.12)";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$a=brand_ui_accents(brand_palette());
		if(!empty($a["accent"])){$acc=$a["accent"];}
		if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
		if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
	}
	$css=<<<'CSS'
<style>
.fwt{color:#111d23;font-size:14px;line-height:1.5;max-width:860px;padding:8px 4px}
.fwt *{box-sizing:border-box}
.fwt-mono{font-family:monospace}
.fwt-muted{font-size:13px;color:#6d7a77}
.fwt-sum{display:flex;align-items:flex-start;gap:14px;border-radius:10px;padding:14px 16px;margin-bottom:16px;border:1px solid transparent}
.fwt-sum.is-on{background:__SOFT__;border-color:__SOFT__}
.fwt-sum.is-off{background:#f1f5f9;border-color:#e2e8f0}
.fwt-sum-i{flex:0 0 32px;width:32px;height:32px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:16px;color:__DARK__;background:#fff}
.fwt-sum.is-off .fwt-sum-i{color:#3d4947}
.fwt-sum.is-bad{background:#fff4e5;border-color:#ffddb9}
.fwt-sum.is-bad .fwt-sum-i{color:#865300}
.fwt-sum-i>span{display:none}
.fwt-sum.is-on .i-on,.fwt-sum.is-off .i-off,.fwt-sum.is-bad .i-bad{display:inline-flex}
.fwt-sum-t b{display:block;font-size:15px;font-weight:600}
.fwt-sum-t small{display:block;font-size:13px;color:#3d4947;margin-top:2px}
.fwt-sum-t small i,.fwt-sum-t small svg{color:#6d7a77;margin-right:4px}
.fwt-tz{font-weight:600;color:#111d23}
.fwt-row{display:flex;align-items:center;justify-content:space-between;gap:16px;background:#f8fafc;border:1px solid #e3e8ee;border-radius:12px;padding:14px 16px;margin-bottom:16px}
.fwt-row b{display:block;font-size:14px;font-weight:600}
.fwt-row small{display:block;font-size:13px;color:#6d7a77}
.fwt-row-r{display:flex;align-items:center;gap:12px}
.fwt-state{font-size:13px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#6d7a77}
.fwt-state.is-on{color:__DARK__}
.fwt-sw{position:relative;width:44px;height:24px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;flex:0 0 44px}
.fwt-sw span{position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:999px;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.25)}
.fwt-sw[aria-checked=true]{background:__ACC__}
.fwt-sw[aria-checked=true] span{left:22px}
.fwt-body.is-off{opacity:.6}
.fwt-card{background:#fff;border:1px solid #e3e8ee;border-radius:12px;padding:20px;margin-bottom:16px;box-shadow:0 1px 2px rgba(16,24,40,.05)}
.fwt-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.fwt-card-h h3{margin:0;font-size:15px;font-weight:600;color:#111d23}
.fwt-times{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;margin-bottom:16px}
.fwt-f{width:160px}
.fwt-f label{display:block;font-size:13px;font-weight:600;color:#3d4947;margin:0 0 4px}
.fwt-f input{width:100%;height:40px;border:1px solid #cbd5e1;border-radius:6px;padding:0 10px;font-size:15px;color:#111d23;background:#fff}
.fwt-f input[aria-invalid=true]{border-color:#ba1a1a}
.fwt-arrow{color:#6d7a77;margin-bottom:12px}
.fwt-dur{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#3d4947;background:#f8fafc;border:1px solid #e3e8ee;border-radius:6px;padding:8px 12px}
.fwt-dur i,.fwt-dur svg{color:__DARK__}
.fwt-bar{position:relative;height:28px;border-radius:6px;background:#eef2f7;border:1px solid #e3e8ee;overflow:hidden;background-image:linear-gradient(to right,rgba(148,163,184,.35) 1px,transparent 1px);background-size:12.5% 100%}
.fwt-bar span{position:absolute;top:0;bottom:0;background:__ACC__}
.fwt-ticks{display:flex;justify-content:space-between;font-family:monospace;font-size:13px;color:#6d7a77;margin-top:4px}
.fwt-err{display:flex;align-items:center;gap:8px;background:#ffdad6;color:#93000a;border-radius:6px;padding:8px 12px;font-size:13px;margin-top:12px}
.fwt-err[hidden]{display:none}
.fwt-help{margin:12px 0 0;font-size:13px;color:#6d7a77}
.fwt-help i,.fwt-help svg{margin-right:4px}
.fwt-short{display:inline-flex;gap:6px;flex-wrap:wrap}
.fwt-short button{border:1px solid #e3e8ee;background:#f8fafc;border-radius:6px;padding:4px 10px;font-size:13px;font-weight:600;color:#3d4947;cursor:pointer}
.fwt-short button:hover{background:#f1f5f9}
.fwt-chips{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:8px}
.fwt-chip{height:40px;border-radius:6px;border:1px solid #d5dbe2;background:#fff;color:#3d4947;font-size:14px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:6px;cursor:pointer}
.fwt-chip i,.fwt-chip svg{display:none}
.fwt-chip[aria-pressed=true]{background:__ACC__;border-color:__ACC__;color:#fff}
.fwt-chip[aria-pressed=true] i,.fwt-chip[aria-pressed=true] svg{display:inline-block}
.fwt-foot{display:flex;justify-content:flex-end;margin-top:4px}
.fwt-btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:6px;background:__ACC__;color:#fff;font-weight:600;font-size:14px;padding:10px 20px;cursor:pointer}
.fwt-btn:hover{background:__DARK__}
.fwt-btn[disabled]{opacity:.6;cursor:not-allowed}
.fwt button:focus-visible,.fwt input:focus-visible{outline:2px solid __ACC__;outline-offset:2px}
@media (max-width:760px){.fwt-times{flex-direction:column;align-items:stretch}.fwt-arrow{display:none}.fwt-chips{grid-template-columns:repeat(4,minmax(0,1fr))}}
@media (prefers-reduced-motion:no-preference){.fwt-sw,.fwt-sw span,.fwt-chip{transition:all .15s}}
</style>
CSS;
	return str_replace(array("__ACC__","__DARK__","__SOFT__"),array($acc,$dark,$soft),$css);
}

// Textes passes au JS : traduits puis debarrasses de [br]/balises (phrase reconstruite en direct).
function fwt_txt(string $tok):string{
	$tpl=new template_admin();
	return trim(html_entity_decode(strip_tags(str_replace("[br]"," ",$tpl->_ENGINE_parse_body("{".$tok."}"))),ENT_QUOTES,"UTF-8"));
}

function fwt_js(array $m):string{
	$days=array();
	foreach(array("monday","tuesday","wednesday","thursday","friday","saturday","sunday") as $k){$days[]=fwt_txt($k);}
	$cfg=array("page"=>CurrentPageName(),"id"=>$m["id"],"days"=>$days,
		"t_off"=>fwt_txt("fwt_sum_off"),"t_every"=>fwt_txt("fwt_sum_every"),"t_some"=>fwt_txt("fwt_sum_some"),
		"t_range"=>fwt_txt("fwt_sum_span"),"t_and"=>fwt_txt("fwt_and"),"t_bad"=>fwt_txt("fwt_sum_bad"),
		"t_on"=>fwt_txt("fwt_on"),"t_offs"=>fwt_txt("fwt_off"),"t_saved"=>fwt_txt("fwt_saved"),"t_apply"=>fwt_txt("fwt_apply"),
		"t_noans"=>fwt_txt("fw_api_no_answer"));
	$json=json_encode($cfg,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
	$js=<<<'JS'
<script>
(function(){
var C=__CFG__,root=document.getElementById('fwt');if(!root){return;}
function q(id){return document.getElementById(id);}
function mins(v){var r=/^(\d{1,2}):(\d{2})/.exec(v||'');if(!r){return -1;}var h=+r[1],m=+r[2];return (h>23||m>59)?-1:h*60+m;}
function on(){return q('fwt-sw').getAttribute('aria-checked')==='true';}
function picked(){var a=[];root.querySelectorAll('.fwt-chip').forEach(function(c){if(c.getAttribute('aria-pressed')==='true'){a.push(+c.getAttribute('data-day'));}});return a;}
function daysText(d){
 if(d.length===0||d.length===7){return null;}
 var contiguous=d.length>2&&d[d.length-1]-d[0]===d.length-1;
 if(contiguous){return C.t_range.replace('%a',C.days[d[0]-1]).replace('%b',C.days[d[d.length-1]-1]);}
 var n=d.map(function(x){return C.days[x-1];});
 if(n.length===1){return n[0];}
 return n.slice(0,-1).join(', ')+' '+C.t_and+' '+n[n.length-1];
}
function render(){
 var f=q('fwt-from').value,t=q('fwt-to').value,a=mins(f),b=mins(t),ok=a>=0&&b>a,en=on();
 var sum=q('fwt-sum'),txt;
 q('fwt-state').textContent=en?C.t_on:C.t_offs;q('fwt-state').classList.toggle('is-on',en);
 root.querySelector('.fwt-body').classList.toggle('is-off',!en);
 root.querySelectorAll('.fwt-body input,.fwt-body button').forEach(function(e){e.disabled=!en;});
 q('fwt-from').setAttribute('aria-invalid',a<0?'true':'false');q('fwt-to').setAttribute('aria-invalid',(b<0||(a>=0&&b<=a))?'true':'false');
 q('fwt-range-err').hidden=!(en&&a>=0&&b>=0&&b<=a);
 var fill=q('fwt-fill');
 if(ok){fill.style.left=(a/14.4)+'%';fill.style.width=((b-a)/14.4)+'%';var d=b-a;q('fwt-dur').textContent=Math.floor(d/60)+' h '+('0'+d%60).slice(-2)+' min';}
 else{fill.style.width='0';q('fwt-dur').textContent='—';}
 if(!en){txt=C.t_off;}
 else if(!ok){txt=C.t_bad;}
 else{var dt=daysText(picked());txt=(dt===null?C.t_every:C.t_some.replace('%d',dt)).replace('%f',f.slice(0,5)).replace('%t',t.slice(0,5));}
 q('fwt-sum-text').textContent=txt;
 sum.className='fwt-sum '+(!en?'is-off':(ok?'is-on':'is-bad'));
 q('fwt-apply').disabled=en&&!ok;
}
q('fwt-sw').addEventListener('click',function(){this.setAttribute('aria-checked',on()?'false':'true');render();});
q('fwt-from').addEventListener('input',render);q('fwt-to').addEventListener('input',render);
root.querySelectorAll('.fwt-chip').forEach(function(c){c.addEventListener('click',function(){c.setAttribute('aria-pressed',c.getAttribute('aria-pressed')==='true'?'false':'true');render();});});
root.querySelectorAll('.fwt-short button').forEach(function(s){s.addEventListener('click',function(){var set=(s.getAttribute('data-set')||'').split(',');root.querySelectorAll('.fwt-chip').forEach(function(c){c.setAttribute('aria-pressed',set.indexOf(c.getAttribute('data-day'))>=0?'true':'false');});render();});});
q('fwt-apply').addEventListener('click',function(){
 var btn=this;if(btn.disabled){return;}
 var data={'rule-save':C.id,'enablet':on()?1:0,'ftime':q('fwt-from').value,'ttime':q('fwt-to').value};
 var p=picked();for(var i=1;i<=7;i++){data['D'+i]=p.indexOf(i)>=0?1:0;}
 btn.disabled=true;q('fwt-save-err').hidden=true;q('fwt-apply-t').textContent=C.t_apply;clearTimeout(window.FwtSavedT);
 $.post(C.page,data).done(function(r){
  r=$.trim(r||'');btn.disabled=false;
  if(r!==''){q('fwt-save-err-t').textContent=r;q('fwt-save-err').hidden=false;return;}
  q('fwt-apply-t').textContent=C.t_saved;window.FwtSavedT=setTimeout(function(){q('fwt-apply-t').textContent=C.t_apply;},2000);
  Loadjs(C.page.replace(/[^\/]*$/,'')+'fw.rules.php?fill='+C.id);
 }).fail(function(){btn.disabled=false;q('fwt-save-err-t').textContent=C.t_noans;q('fwt-save-err').hidden=false;});
});
render();
})();
</script>
JS;
	return str_replace("__CFG__",$json,$js);
}

function page(){
	$tpl=new template_admin();
	$ID=intval($_GET["rule-id"] ?? 0);
	$m=fwt_model($ID);
	$html=array();
	$metaBanner=firewall_meta_banner("rules",$tpl);
	$html[]="<div class='fwt' id='fwt'>";
	$html[]=fwt_summary($m);
	$html[]=fwt_switch($m);
	$html[]="<div class='fwt-body'>".fwt_card_range($m).fwt_card_days($m)."</div>";
	$html[]=fwt_footer();
	$html[]="</div>";
	$ro="";
	// Bouton d'enregistrement cache (le script de la page le cherche toujours).
	if($metaBanner!==""){$ro="<script>(function(){var b=document.getElementById('fwt-apply');if(b){b.hidden=true;}})();</script>";}
	echo fwt_style().$metaBanner.$tpl->_ENGINE_parse_body(implode("",$html)).fwt_js($m).$ro;
}

// Renvoie RIEN en cas de succes, sinon le texte de l'erreur (affiche par fwt_js).
function save(){
	$tpl=new template_admin();
	$ID=intval($_POST["rule-save"]);
	if(firewall_meta_holds("rules")){echo fwt_txt("firewall_meta_locked");return;}
	if($ID<1){echo fwt_txt("fw_time_invalid");return;}
	$days=array();
	for($i=1;$i<=7;$i++){
		if(intval($_POST["D$i"] ?? 0)==1){$days[]=$i;}
	}
	$data=array(
		"enabled"=>intval($_POST["enablet"] ?? 0)==1?1:0,
		"days"=>$days,
		"from"=>trim((string)($_POST["ftime"] ?? "")),
		"to"=>trim((string)($_POST["ttime"] ?? "")),
	);
	$e=fwapi_error(fwapi_send("PUT","rules/$ID/time",$data));
	if($e!=""){
		echo trim(html_entity_decode(strip_tags(str_replace("[br]"," ",$tpl->_ENGINE_parse_body($e))),ENT_QUOTES,"UTF-8"));
		return;
	}
	admin_tracks("Firewall rule $ID: time restriction ".($data["enabled"]?"on, {$data["from"]}-{$data["to"]}, days ".(count($days)?implode(",",$days):"all"):"off"));
}
