<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["prefer_direct"])){Save();exit;}
if(isset($_GET["flat"])){flat();exit;}
if(isset($_GET["table-flat"])){table_flat();exit;}
page();

/* ===========================================================================
 * Parent proxies » Parameters (maquette Stitch « Parent proxy parameters », 2026-11-21).
 *
 * Trois reglages appliques par articarest :
 *  - prefer_direct : 0 -> « prefer_direct off » ET « always_direct deny all » (le proxy ne
 *    sort jamais en direct), 1 -> « prefer_direct on » (direct d'abord) — SquidParents.Reconfigure() ;
 *  - nonhierarchical_direct : on/off — meme fonction ;
 *  - forwarded_for : ecrit par le seul squid.Anonymous() (anonymous.conf, defaut « on ») ;
 *    GET /proxy/parents/compile l'appelle avant SquidParents.Reconfigure(), et
 *    GET /proxy/acls/anonymous le reecrit seul. SquidAnonymousBrowsing=1 force « delete ».
 * Le formulaire est sur la page (plus de fenetre) : Apply enregistre les trois valeurs,
 * puis relance seulement ce qui a change (compilation des parents et/ou en-tete).
 * ========================================================================= */

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{parent_proxies} {parameters}",
        "fas fa-cogs","{parent_proxies_status_explain}","$page?flat=yes",
        "proxy-parents-status","progress-squid-parent-restart",false,"table-loader-progress-squid-parent-restart");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall("{parent_proxies} {parameters}");
		return;
	}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function flat():bool{
    $page=CurrentPageName();
    echo "<div id='proxy-parents-param-flat'></div>";
    echo "<script>LoadAjaxSilent('proxy-parents-param-flat','$page?table-flat=yes');</script>";
    return true;
}

// Valeurs acceptees pour forwarded_for, dans l'ordre d'affichage.
function ppp_forwarded_values():array{return array("on","off","transparent","delete","truncate");}

// Droit d'ecriture : le plus etroit pour le proxy.
function ppp_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSquidAdministrator;
}

// Le modele : les trois reglages, le forcage par la navigation anonyme et le droit.
function ppp_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $fw=trim((string)$s->GET_INFO("forwarded_for"));
    if(!in_array($fw,ppp_forwarded_values())){$fw="on";}
    return array(
        "prefer_direct"=>intval($s->GET_INFO("prefer_direct"))==1?1:0,
        "nonhierarchical_direct"=>intval($s->GET_INFO("nonhierarchical_direct"))==1?1:0,
        "forwarded_for"=>$fw,
        "anonymous"=>intval($s->GET_INFO("SquidAnonymousBrowsing"))==1,
        "write"=>ppp_can_write(),
    );
}

// 1. Bandeau d'accroche.
function ppp_banner():string{
    return "<section class='ppp-card ppp-banner'><div class='ppp-bico' aria-hidden='true'><i class='fas fa-route'></i></div>"
        ."<div class='ppp-btxt'><h2>{ppp_title}</h2><p>{ppp_text}</p><div class='ppp-chips'>"
        ."<span class='ppp-chip'><i class='fas fa-layer-group' aria-hidden='true'></i>{ppp_chip_all}</span>"
        ."<span class='ppp-chip'><i class='fas fa-rotate' aria-hidden='true'></i>{ppp_chip_reload}</span>"
        ."</div></div></section>";
}

function ppp_card_head(string $title,string $tech,string $side=""):string{
    return "<div class='ppp-head'><div><h3>$title</h3><div class='ppp-tech'>$tech</div></div>$side</div>";
}

// Une grande carte de choix (radio).
function ppp_choice(string $group,string $value,bool $on,string $icon,string $title,string $sub,string $text,string $note,bool $disabled):string{
    $dis=$disabled?" aria-disabled='true'":"";
    return "<div class='ppp-choice".($on?" on":"")."' role='radio' tabindex='".($on?"0":"-1")."' aria-checked='".($on?"true":"false")."' data-group='$group' data-value='$value'$dis"
        ." OnClick=\"PppPick(this);\" OnKeyDown=\"PppKey(event,this);\">"
        ."<div class='top'><span class='ic' aria-hidden='true'><i class='fas $icon'></i></span><div class='tt'><div class='t'>$title</div><div class='s'>$sub</div></div>"
        ."<span class='ck' aria-hidden='true'><i class='fas fa-circle-check'></i></span></div><p>$text</p>$note</div>";
}

// 2. Passer par un parent, ou aller en direct.
function ppp_card_direct(array $m):string{
    $dis=!$m["write"];
    $h=array();
    $h[]="<section class='ppp-card'>".ppp_card_head("{ppp_direct_t}","Prefer direct","<span class='ppp-def'>{ppp_default_off}</span>");
    $h[]="<div class='ppp-choices two' role='radiogroup' aria-label=\"{ppp_direct_t}\">";
    $h[]=ppp_choice("prefer_direct","0",$m["prefer_direct"]==0,"fa-shield-halved","{ppp_direct_off_t}","{ppp_off}","{ppp_direct_off_text}",
        "<div class='ppp-note warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>{ppp_direct_off_warn}</div>",$dis);
    $h[]=ppp_choice("prefer_direct","1",$m["prefer_direct"]==1,"fa-arrow-right-arrow-left","{ppp_direct_on_t}","{ppp_on}","{ppp_direct_on_text}",
        "<div class='ppp-note'><i class='fas fa-circle-info' aria-hidden='true'></i>{ppp_direct_on_note}</div>",$dis);
    $h[]="</div></section>";
    return @implode("",$h);
}

// 3. Requetes impossibles a mettre en cache.
function ppp_card_nonhier(array $m):string{
    $on=$m["nonhierarchical_direct"]==1;
    $dis=!$m["write"]?" aria-disabled='true'":"";
    $sw="<button type='button' class='ppp-switch".($on?" on":"")."' role='switch' id='ppp-nonhier' aria-checked='".($on?"true":"false")."' aria-labelledby='ppp-nonhier-l'$dis OnClick=\"PppToggle(this);\"><span></span></button>";
    $blocked="<div class='ppp-note info' id='ppp-nonhier-blocked'".($m["prefer_direct"]==0?"":" hidden")."><i class='fas fa-circle-info' aria-hidden='true'></i><span>{ppp_nonhier_blocked}</span></div>";
    return "<section class='ppp-card'>".ppp_card_head("{ppp_nonhier_t}","Direct non-hierarchical requests",$sw)
        ."<div class='ppp-lbl' id='ppp-nonhier-l'>{ppp_nonhier_label}</div><p class='ppp-help'>{ppp_nonhier_help}</p>$blocked</section>";
}

// Une ligne du choix X-Forwarded-For.
function ppp_fwd_row(string $value,bool $on,string $icon,string $title,string $sub,string $text,string $example,bool $disabled):string{
    $dis=$disabled?" aria-disabled='true'":"";
    return "<div class='ppp-row".($on?" on":"")."' role='radio' tabindex='".($on?"0":"-1")."' aria-checked='".($on?"true":"false")."' data-group='forwarded_for' data-value='$value'$dis"
        ." OnClick=\"PppPick(this);\" OnKeyDown=\"PppKey(event,this);\">"
        ."<span class='ic' aria-hidden='true'><i class='fas $icon'></i></span>"
        ."<div class='tx'><div class='t'>$title <span class='ppp-tech'>$sub</span></div><p>$text</p></div>"
        ."<span class='ex'>$example</span><span class='rd' aria-hidden='true'></span></div>";
}

// 4. Ce que les serveurs en amont apprennent des utilisateurs.
function ppp_card_forwarded(array $m):string{
    $dis=!$m["write"] || $m["anonymous"];
    $rows=array(
        array("on","fa-eye","{ppp_fwd_on_t}","Enabled (on)","{ppp_fwd_on_text}","X-Forwarded-For: 192.168.1.25"),
        array("off","fa-circle-question","{ppp_fwd_off_t}","Disabled (off)","{ppp_fwd_off_text}","X-Forwarded-For: unknown"),
        array("transparent","fa-right-long","{ppp_fwd_tr_t}","Transparent","{ppp_fwd_tr_text}","{ppp_fwd_tr_ex}"),
        array("delete","fa-user-secret","{ppp_fwd_del_t}","Anonymous (delete)","{ppp_fwd_del_text}","{ppp_fwd_del_ex}"),
        array("truncate","fa-scissors","{ppp_fwd_tr2_t}","Hide (truncate)","{ppp_fwd_tr2_text}","X-Forwarded-For: 10.0.0.4"),
    );
    $h=array();
    $h[]="<section class='ppp-card'>".ppp_card_head("{ppp_fwd_t}","X-Forwarded-For");
    $h[]="<p class='ppp-help top'>{ppp_fwd_help}</p>";
    if($m["anonymous"]){
        $h[]="<div class='ppp-band warn'><i class='fas fa-user-secret' aria-hidden='true'></i><span>{ppp_fwd_forced}</span></div>";
    }
    $h[]="<div class='ppp-rows' role='radiogroup' aria-label=\"{ppp_fwd_t}\">";
    foreach($rows as $r){
        $h[]=ppp_fwd_row($r[0],$m["forwarded_for"]==$r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$dis);
    }
    $h[]="</div></section>";
    return @implode("",$h);
}

// 5. Barre d'enregistrement : visible seulement quand quelque chose a change.
function ppp_footer(array $m):string{
    if(!$m["write"]){
        return "<div class='ppp-foot'><span class='st ro'><i class='fas fa-lock' aria-hidden='true'></i>{ppp_readonly}</span></div>";
    }
    return "<div class='ppp-foot' id='ppp-foot'>"
        ."<span class='st' id='ppp-saved'><i class='fas fa-circle-check' aria-hidden='true'></i>{ppp_saved}</span>"
        ."<span class='st dirty' id='ppp-dirty' hidden><i class='fas fa-pen' aria-hidden='true'></i>{ppp_dirty}</span>"
        ."<span class='ppp-err' id='ppp-err' role='alert' hidden></span>"
        ."<span class='bt'><button type='button' class='ppp-btn' id='ppp-cancel' OnClick=\"PppCancel();\" hidden>{ppp_cancel}</button>"
        ."<button type='button' class='ppp-btn primary' id='ppp-apply' OnClick=\"PppApply();\" hidden><i class='fas fa-check' aria-hidden='true'></i>{ppp_apply}</button></span></div>";
}

function table_flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=ppp_model();
    $html="<div class='ppp' id='ppp-root'>".ppp_banner().ppp_card_direct($m).ppp_card_nonhier($m).ppp_card_forwarded($m).ppp_footer($m)."</div>";

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{parent_proxies} {parameters}";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{parent_proxies_status_explain}";
    $TINY_ARRAY["URL"]="proxy-parents-status";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // La compilation des parents (prefer_direct, nonhierarchical_direct) recharge le proxy
    // avec suivi de progression ; l'en-tete est relance cote serveur par Save().
    $compile=$tpl->framework_buildjs("/proxy/parents/compile",
        "squid.access.center.progress","squid.access.center.progress.log","progress-squid-parent-restart",
        "LoadAjaxSilent('proxy-parents-param-flat','$page?table-flat=yes');");
    $saved=json_encode(array("prefer_direct"=>strval($m["prefer_direct"]),"nonhierarchical_direct"=>strval($m["nonhierarchical_direct"]),"forwarded_for"=>$m["forwarded_for"]));
    echo ppp_style().$tpl->_ENGINE_parse_body($html).ppp_js($page,$saved,$compile)."<script>$jstiny</script>";
    return true;
}

function ppp_js(string $page,string $saved,string $compile):string{
    $compile=str_replace("</script","<\\/script",$compile);
    return "<script>
window.PppSaved=$saved;
window.PppCur=JSON.parse(JSON.stringify(window.PppSaved));
function PppCompile(){ $compile }
function PppSync(){var root=document.getElementById('ppp-root');if(!root){return;}
 var c=root.querySelectorAll('[data-group]');
 for(var i=0;i<c.length;i++){var g=c[i].getAttribute('data-group'),on=PppCur[g]===c[i].getAttribute('data-value');
  c[i].className=c[i].className.replace(/ on\\b/g,'')+(on?' on':'');c[i].setAttribute('aria-checked',on?'true':'false');c[i].setAttribute('tabindex',on?'0':'-1');}
 var s=document.getElementById('ppp-nonhier');if(s){var o=PppCur.nonhierarchical_direct==='1';s.className='ppp-switch'+(o?' on':'');s.setAttribute('aria-checked',o?'true':'false');}
 var b=document.getElementById('ppp-nonhier-blocked');if(b){b.hidden=PppCur.prefer_direct!=='0';}
 var dirty=false;for(var k in PppSaved){if(PppSaved[k]!==PppCur[k]){dirty=true;}}
 var ids={'ppp-saved':!dirty,'ppp-dirty':dirty,'ppp-cancel':dirty,'ppp-apply':dirty};
 for(var id in ids){var e=document.getElementById(id);if(e){e.hidden=!ids[id];}}
 var f=document.getElementById('ppp-foot');if(f){f.className='ppp-foot'+(dirty?' dirty':'');}}
function PppPick(el){if(el.getAttribute('aria-disabled')==='true'){return;}PppCur[el.getAttribute('data-group')]=el.getAttribute('data-value');PppSync();}
function PppKey(e,el){var g=el.getAttribute('data-group');var list=[].slice.call(document.querySelectorAll(\"[data-group='\"+g+\"']\"));var i=list.indexOf(el);
 if(e.keyCode==13||e.keyCode==32){e.preventDefault();PppPick(el);return;}
 var n=-1;if(e.keyCode==39||e.keyCode==40){n=Math.min(list.length-1,i+1);}if(e.keyCode==37||e.keyCode==38){n=Math.max(0,i-1);}
 if(n>=0&&n!==i){e.preventDefault();PppPick(list[n]);list[n].focus();}}
function PppToggle(el){if(el.getAttribute('aria-disabled')==='true'){return;}PppCur.nonhierarchical_direct=PppCur.nonhierarchical_direct==='1'?'0':'1';PppSync();}
function PppCancel(){PppCur=JSON.parse(JSON.stringify(PppSaved));var e=document.getElementById('ppp-err');if(e){e.hidden=true;}PppSync();}
function PppApply(){var btn=document.getElementById('ppp-apply');if(btn){btn.disabled=true;}
 var parents=(PppCur.prefer_direct!==PppSaved.prefer_direct)||(PppCur.nonhierarchical_direct!==PppSaved.nonhierarchical_direct);
 var post={prefer_direct:PppCur.prefer_direct,nonhierarchical_direct:PppCur.nonhierarchical_direct,forwarded_for:PppCur.forwarded_for};
 $.post('$page',post,function(data){if(btn){btn.disabled=false;}var t=String(data||'').trim();var e=document.getElementById('ppp-err');
  if(t.length>0){if(e){e.textContent=t;e.hidden=false;}return;}
  if(e){e.hidden=true;}PppSaved=JSON.parse(JSON.stringify(PppCur));PppSync();
  if(parents){PppCompile();}});}
PppSync();
</script>";
}

/**
 * Enregistre les trois reglages (et eux seuls : SAVE_POSTs ecrivait n'importe quelle cle
 * postee, avec le seul droit de lecture AsProxyMonitor). Ne renvoie que l'erreur.
 * Si seul l'en-tete change, /proxy/acls/anonymous le reecrit et recharge le proxy ; si un
 * reglage des parents change aussi, /proxy/parents/compile (lance par la page) reecrit les
 * deux fichiers et ne recharge qu'une fois.
 */
function Save(){
    $tpl=new template_admin();
    if(!ppp_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $s=$GLOBALS["CLASS_SOCKETS"];
    $prefer=intval($_POST["prefer_direct"] ?? 0)==1?1:0;
    $nonhier=intval($_POST["nonhierarchical_direct"] ?? 0)==1?1:0;
    $fw=trim(strval($_POST["forwarded_for"] ?? ""));
    if(!in_array($fw,ppp_forwarded_values())){echo $tpl->_ENGINE_parse_body("{ppp_bad_value}");return;}
    $old=trim((string)$s->GET_INFO("forwarded_for"));
    if(!in_array($old,ppp_forwarded_values())){$old="on";}
    $parents=intval($s->GET_INFO("prefer_direct"))!=$prefer || intval($s->GET_INFO("nonhierarchical_direct"))!=$nonhier;
    $s->SET_INFO("prefer_direct",$prefer);
    $s->SET_INFO("nonhierarchical_direct",$nonhier);
    $s->SET_INFO("forwarded_for",$fw);
    // La compilation des parents, lancee ensuite par la page, reecrit aussi
    // anonymous.conf : l'en-tete seul passe par sa propre route (un seul rechargement).
    if($fw!==$old && !$parents){$s->REST_API("/proxy/acls/anonymous");}
    admin_tracks("Parent proxies parameters: prefer_direct=$prefer, nonhierarchical_direct=$nonhier, forwarded_for=$fw");
}

function ppp_style():string{
    if(!empty($GLOBALS["PPP_CSS"])){return "";}
    $GLOBALS["PPP_CSS"]=true;
    $ac=array("accent"=>"#005447","accent_dark"=>"#003d33","accent_soft"=>"rgba(0,84,71,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return "<style>
.ppp{--ppp-accent:{$ac["accent"]};--ppp-dark:{$ac["accent_dark"]};--ppp-soft:{$ac["accent_soft"]};display:flex;flex-direction:column;gap:16px;max-width:1100px;margin:12px 0 0 0;color:#0f172a;font-size:14px}
.ppp *{box-sizing:border-box}
.ppp [hidden]{display:none!important}
.ppp-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.04);padding:24px}
.ppp-banner{display:flex;gap:16px;align-items:flex-start}
.ppp-bico{flex:0 0 44px;width:44px;height:44px;border-radius:10px;background:var(--ppp-soft);color:var(--ppp-accent);display:flex;align-items:center;justify-content:center;font-size:20px}
.ppp-btxt h2{margin:0 0 6px 0;font-size:19px;font-weight:600}
.ppp-btxt p{margin:0;color:#475569;line-height:1.55}
.ppp-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.ppp-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;background:#f2f6f6;border:1px solid #e1ebe9;font-size:13px;color:#1e293b}
.ppp-chip i,.ppp-chip svg{color:var(--ppp-accent)}
.ppp-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px}
.ppp-head h3{margin:0;font-size:18px;font-weight:600}
.ppp-tech{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;font-weight:400;color:#64748b;margin-top:2px}
.ppp-def{font-size:13px;color:#64748b;white-space:nowrap}
.ppp-choices.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.ppp-choice{position:relative;border:1px solid #e5e7eb;border-radius:12px;padding:18px;background:#fff;cursor:pointer}
.ppp-choice:hover{border-color:#bfd4d1}
.ppp-choice.on{border:2px solid var(--ppp-accent);padding:17px;background:#f7faf9}
.ppp-choice .top{display:flex;gap:12px;align-items:flex-start}
.ppp-choice .ic{flex:0 0 36px;width:36px;height:36px;border-radius:50%;border:1px solid #e5e7eb;background:#fff;color:#475569;display:flex;align-items:center;justify-content:center}
.ppp-choice.on .ic{color:var(--ppp-accent);border-color:#bfd4d1}
.ppp-choice .tt{flex:1}
.ppp-choice .t{font-weight:600;font-size:15px}
.ppp-choice .s{font-size:13px;color:#64748b}
.ppp-choice .ck{color:var(--ppp-accent);font-size:20px;visibility:hidden}
.ppp-choice.on .ck{visibility:visible}
.ppp-choice p{margin:12px 0;color:#334155;line-height:1.5}
.ppp-note{display:flex;gap:8px;align-items:flex-start;padding:8px 12px;border-radius:8px;background:#f8fafc;border:1px solid #e5e7eb;font-size:13px;color:#475569}
.ppp-note>i,.ppp-note>svg{margin-top:2px;color:#64748b}
.ppp-note.warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.ppp-note.warn>i,.ppp-note.warn>svg{color:#d97706}
.ppp-note.info{margin-top:14px}
.ppp-lbl{font-weight:600;font-size:15px}
.ppp-help{margin:4px 0 0 0;font-size:13px;color:#64748b;line-height:1.5}
.ppp-help.top{margin:-6px 0 14px 0}
.ppp-switch{position:relative;flex:0 0 52px;width:52px;height:30px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0}
.ppp-switch span{position:absolute;top:3px;left:3px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2)}
.ppp-switch.on{background:var(--ppp-accent)}
.ppp-switch.on span{left:25px}
.ppp-band{display:flex;gap:10px;align-items:center;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px}
.ppp-band.warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.ppp-rows{display:flex;flex-direction:column;gap:8px}
.ppp-row{display:flex;align-items:center;gap:14px;padding:12px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;cursor:pointer;min-height:64px}
.ppp-row:hover{border-color:#bfd4d1}
.ppp-row.on{border:2px solid var(--ppp-accent);padding:11px 15px;background:#f7faf9}
.ppp-row .ic{flex:0 0 36px;width:36px;height:36px;border-radius:8px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}
.ppp-row.on .ic{background:#fff;color:var(--ppp-accent);border:1px solid #bfd4d1}
.ppp-row .tx{flex:1;min-width:0}
.ppp-row .t{font-weight:600;font-size:15px;display:flex;flex-wrap:wrap;gap:8px;align-items:baseline}
.ppp-row .t .ppp-tech{margin:0}
.ppp-row p{margin:2px 0 0 0;font-size:13px;color:#475569}
.ppp-row .ex{flex:0 0 auto;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#334155;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;white-space:nowrap}
.ppp-row .rd{flex:0 0 18px;width:18px;height:18px;border-radius:50%;border:2px solid #cbd5e1;background:#fff}
.ppp-row.on .rd{border-color:var(--ppp-accent);background:var(--ppp-accent);box-shadow:inset 0 0 0 3px #fff}
.ppp [aria-disabled=true]{cursor:not-allowed;opacity:.6}
.ppp-choice:focus-visible,.ppp-row:focus-visible,.ppp-switch:focus-visible,.ppp-btn:focus-visible{outline:2px solid var(--ppp-accent);outline-offset:2px}
.ppp-foot{position:static;z-index:5;display:flex;align-items:center;gap:16px;padding:14px 20px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 -2px 8px rgba(15,23,42,.06)}
.ppp-foot.dirty{position:sticky;bottom:0;border-color:#bfd4d1;background:#f7faf9}
.ppp-foot .st{display:inline-flex;gap:8px;align-items:center;font-size:14px;color:#334155}
.ppp-foot .st>i,.ppp-foot .st>svg{color:var(--ppp-accent)}
.ppp-foot .st.ro>i,.ppp-foot .st.ro>svg{color:#64748b}
.ppp-foot .bt{margin-left:auto;display:flex;gap:10px}
.ppp-err{color:#b91c1c;font-size:13px;font-weight:600}
.ppp-btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 18px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#1e293b;font-size:14px;font-weight:600;cursor:pointer}
.ppp-btn.primary{background:var(--ppp-accent);border-color:var(--ppp-accent);color:#fff}
.ppp-btn.primary:hover{background:var(--ppp-dark)}
.ppp-btn[disabled]{opacity:.6;cursor:wait}
@media (max-width:1200px){.ppp-choices.two{grid-template-columns:1fr}.ppp-row{flex-wrap:wrap}.ppp-row .ex{order:5;margin-left:50px}}
@media (prefers-reduced-motion:no-preference){.ppp-choice,.ppp-row,.ppp-switch,.ppp-switch span{transition:all .15s ease}}
</style>";
}
