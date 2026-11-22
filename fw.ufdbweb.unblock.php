<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");

// Acces temporaires aux sites bloques (webfilter.db ufdbunlock), lus et retires
// par articarest /webfilter/unblock : plus aucun acces SQL direct depuis la page.
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}
page();

function page(){
    $page=CurrentPageName();
    echo "<div style='margin-top:10px' id='ufdbweb-unblock-div'></div>
	<script>LoadAjax('ufdbweb-unblock-div','$page?table=yes');</script>";
}

// Retirer un acces rebloque un site : le filtrage web, pas la lecture seule.
// Avant : aucun controle serveur, et la corbeille s'affichait pour AsProxyMonitor.
function unb_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsDansGuardianAdministrator;
}

function unb_t($token,$a="",$b=""):string{
    if(!isset($GLOBALS["UNB_T"])){$GLOBALS["UNB_T"]=array();}
    if(!isset($GLOBALS["UNB_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["UNB_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2"),array($a,$b),$GLOBALS["UNB_T"][$token]);
}

function unb_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

// Un acces qui se termine dans 5 ans ou plus est « jusqu'a retrait » (10 ans
// pour une regle sans duree).
function unb_forever_secs():int{
    return 5*365*86400;
}

// ── Modele ──────────────────────────────────────────────────────────────────

function unb_model():array{
    $m=array("rows"=>array(),"known"=>true,"active"=>0,"soon"=>0,"forever"=>0,"expired"=>0,"max"=>500);
    $data=acls_api_get("/webfilter/unblock");
    if(!is_array($data) || !is_array($data["accesses"] ?? null)){
        $m["known"]=false;
        return $m;
    }
    $now=intval($data["now"] ?? 0);
    if($now<=0){$now=time();}
    $m["now"]=$now;
    $m["max"]=intval($data["max"] ?? 500);
    foreach($data["accesses"] as $a){
        if(!is_array($a)){continue;}
        $r=array(
            "id"=>intval($a["id"] ?? 0),
            "site"=>(string)($a["site"] ?? ""),
            "user"=>(string)($a["user"] ?? ""),
            "ip"=>(string)($a["ipaddr"] ?? ""),
            "start"=>intval($a["start_time"] ?? 0),
            "end"=>intval($a["end_time"] ?? 0),
        );
        if($r["id"]<1){continue;}
        $left=$r["end"]-$now;
        $r["left"]=$left;
        $r["state"]="normal";
        if($left<=0){$r["state"]="expired";$m["expired"]++;}
        elseif($left>=unb_forever_secs()){$r["state"]="forever";$m["forever"]++;}
        elseif($left<=3600){$r["state"]="soon";$m["soon"]++;}
        if($left>0){$m["active"]++;}
        $m["rows"][]=$r;
    }
    return $m;
}

// « il y a 2 h », « dans 45 min » : la date exacte part en info-bulle.
function unb_span($secs):string{
    $secs=abs(intval($secs));
    if($secs<60){return "";}
    $min=intdiv($secs,60);
    if($min<60){return unb_t("unb_d_min",(string)$min);}
    $h=intdiv($min,60);$mm=$min%60;
    if($h<24){return $mm>0?unb_t("unb_d_hmin",(string)$h,(string)$mm):unb_t("unb_d_h",(string)$h);}
    $d=intdiv($h,24);
    if($d<60){return unb_t("unb_d_days",(string)$d);}
    return unb_t("unb_d_months",(string)intdiv($d,30));
}
function unb_ago($ts,$now):string{
    if($ts<=0){return "&mdash;";}
    $s=unb_span($now-$ts);
    return $s===""?unb_t("unb_just_now"):unb_t("unb_ago",$s);
}
function unb_in($left):string{
    $s=unb_span($left);
    return $s===""?unb_t("unb_less_min"):unb_t("unb_in",$s);
}
function unb_date($ts):string{
    return $ts>0?date("Y-m-d H:i",$ts):"";
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=unb_model();
    $h=array();
    $h[]="<div class='unb'>";
    $h[]=unb_hero();
    if(!$m["known"]){
        $h[]="<div class='unb-alert'><i class='fas fa-circle-exclamation'></i><div>{unb_api_error}</div></div>";
    }else{
        $h[]=unb_tiles($m);
        $h[]=unb_expired_note($m);
    }
    $h[]=unb_card($m);
    $h[]="</div>";

    $btns=array();
    $btns[]=array("LoadAjax('ufdbweb-unblock-div','$page?table=yes');",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{unb_title}";
    $TINY["ICO"]="fa-solid fa-unlock-keyhole";
    $TINY["EXPL"]="{unb_hero_text}";
    $TINY["BUTTONS"]=$tpl->table_buttons($btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    echo unb_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).unb_js()."<script>NoSpinner();$jstiny</script>";
}

function unb_hero():string{
    return "<div class='unb-hero'><div class='unb-hero-ico'><i class='fas fa-unlock-keyhole'></i></div><div class='unb-hero-body'>"
        ."<h3>{unb_hero_title} <span class='unb-hero-sub'>{unblock_list}</span></h3><p>{unb_hero_text}</p><div class='unb-chips'>"
        ."<span class='unb-chip'><i class='fas fa-hourglass-half'></i> {unb_chip_time}</span>"
        ."<span class='unb-chip'><i class='fas fa-user-lock'></i> {unb_chip_person}</span>"
        ."<span class='unb-chip'><i class='fas fa-clock-rotate-left'></i> {unb_chip_close}</span>"
        ."</div></div></div>";
}

function unb_tile($ico,$label,$value,$hint,$cls=""):string{
    return "<div class='unb-tile $cls'><span class='unb-tile-ico'><i class='$ico'></i></span><div class='unb-tile-b'>"
        ."<div class='unb-tile-l'>$label</div><div class='unb-tile-v'>$value</div></div><div class='unb-tile-h'>$hint</div></div>";
}

function unb_tiles(array $m):string{
    return "<div class='unb-tiles'>"
        .unb_tile("fas fa-unlock","{unb_k_active}",number_format($m["active"]),"{unb_k_active_h}")
        .unb_tile("fas fa-hourglass-end","{unb_k_soon}",number_format($m["soon"]),"{unb_k_soon_h}",$m["soon"]>0?"unb-tile-warn":"")
        .unb_tile("fas fa-infinity","{unb_k_forever}",number_format($m["forever"]),"{unb_k_forever_h}")
        ."</div>";
}

function unb_expired_note(array $m):string{
    if($m["expired"]==0){return "";}
    $t=$m["expired"]==1?unb_t("unb_expired_note1"):unb_t("unb_expired_note",number_format($m["expired"]));
    return "<div class='unb-note'><i class='fas fa-circle-info'></i> $t</div>";
}

function unb_until_cell(array $r):string{
    $title=unb_h(unb_date($r["end"]));
    if($r["state"]=="expired"){
        return "<span class='unb-pill unb-pill-off' title='$title'><i class='fas fa-circle-xmark'></i> {unb_expired}</span>";
    }
    if($r["state"]=="forever"){
        return "<span class='unb-pill' title='$title'><i class='fas fa-infinity'></i> {unb_forever}</span> <span class='unb-year'>(".date("Y",$r["end"]).")</span>";
    }
    $total=max(1,$r["end"]-$r["start"]);
    $pct=$r["start"]>0?max(2,min(100,round($r["left"]*100/$total))):100;
    $soon=$r["state"]=="soon";
    $pill=$soon?" <span class='unb-pill unb-pill-warn'><i class='fas fa-hourglass-end'></i> {unb_ends_soon}</span>":"";
    $aria=unb_h(strip_tags(unb_t("unb_bar_aria",unb_span($r["left"])?:"1 min",unb_span($total)?:"1 min")));
    return "<div class='unb-until'><div><span class='unb-in' title='$title'>".unb_in($r["left"])."</span>$pill</div>"
        ."<div class='unb-bar' role='img' aria-label=\"$aria\"><span class='".($soon?"unb-bar-warn":"")."' style='width:$pct%'></span></div></div>";
}

function unb_row(array $r,array $m,bool $can):string{
    $page=CurrentPageName();
    $site=$r["site"]!==""?$r["site"]:"?";
    $letter=strtoupper(substr(preg_replace('/^www\./','',$site),0,1));
    $who=$r["user"]!==""?"<div class='unb-user'>".unb_h($r["user"])."</div>":"<div class='unb-user unb-unknown'>{unb_unknown_user}</div>";
    $act="";
    if($can){
        $aria=unb_h(strip_tags(unb_t("unb_remove_aria",$site,$r["user"]!==""?$r["user"]:$r["ip"])));
        $act="<button type='button' class='unb-ibtn' aria-label=\"$aria\" title=\"$aria\" OnClick=\"Loadjs('$page?delete-js=".intval($r["id"])."');\"><i class='fas fa-trash'></i></button>";
    }
    $search=unb_h(strtolower($site." ".$r["user"]." ".$r["ip"]));
    $off=$r["state"]=="expired"?" class='unb-row-off'":"";
    return "<tr$off data-s=\"$search\"><td><div class='unb-site'><span class='unb-letter'>".unb_h($letter)."</span><div><div class='unb-sname'>".unb_h($site)."</div><div class='unb-sub'>{unb_and_sub}</div></div></div></td>"
        ."<td>$who<div class='unb-ip'>".unb_h($r["ip"])."</div></td>"
        ."<td><span class='unb-ago' title='".unb_h(unb_date($r["start"]))."'>".unb_ago($r["start"],$m["now"])."</span></td>"
        ."<td>".unb_until_cell($r)."</td><td class='unb-act'>$act</td></tr>";
}

function unb_empty(bool $known):string{
    if(!$known){return "<div class='unb-empty'><p>&mdash;</p></div>";}
    return "<div class='unb-empty'><div class='unb-empty-ico'><i class='fas fa-unlock-keyhole'></i></div><h4>{unb_empty_title}</h4><p>{unb_empty_text}</p></div>";
}

function unb_card(array $m):string{
    $page=CurrentPageName();
    $can=unb_can_write();
    $n=count($m["rows"]);
    $count=$n>0?"<span class='unb-count'>".($n==1?unb_t("unb_count1"):unb_t("unb_count",number_format($n)))."</span>":"";
    $btns="";
    if($n>0){
        $btns.="<div class='unb-search'><i class='fas fa-magnifying-glass'></i><input type='search' id='unb-q' placeholder=\"".unb_h(unb_t("unb_search"))."\" aria-label=\"".unb_h(unb_t("unb_search"))."\" oninput=\"UnbFilter(this.value)\"></div>";
    }
    $btns.="<button type='button' class='unb-btn' OnClick=\"LoadAjax('ufdbweb-unblock-div','$page?table=yes');\"><i class='fas fa-arrows-rotate'></i> {refresh}</button>";
    if($can){
        $dis=$n==0?" disabled":"";
        $btns.="<button type='button' class='unb-btn unb-btn-danger'$dis OnClick=\"Loadjs('$page?empty-js=yes');\"><i class='fas fa-trash-can'></i> {unb_remove_all}</button>";
    }
    $head="<div class='unb-card-h'><h3>{unb_title} $count</h3><div class='unb-card-b'>$btns</div></div>";
    if($n==0){return "<div class='unb-card'>$head".unb_empty($m["known"])."</div>";}
    $rows=array();
    foreach($m["rows"] as $r){$rows[]=unb_row($r,$m,$can);}
    $tbl="<div class='unb-scroll'><table class='unb-tbl'><thead><tr><th>{unb_c_site}</th><th>{unb_c_who}</th><th>{unb_c_start}</th><th>{unb_c_until} <i class='fas fa-arrow-up-short-wide' aria-hidden='true'></i></th><th class='unb-act'></th></tr></thead>"
        ."<tbody id='unb-rows'>".implode("",$rows)."</tbody></table></div>";
    $foot="<div class='unb-foot'><span id='unb-shown'>".unb_t("unb_showing",number_format($n),number_format($n))."</span><span>".unb_t("unb_max",number_format($m["max"]))."</span></div>";
    return "<div class='unb-card'>$head$tbl$foot</div>";
}

function unb_js():string{
    $tpl=new template_admin();
    $showing=json_encode(strip_tags($tpl->_ENGINE_parse_body("{unb_showing}")));
    return "<script>
function UnbFilter(q){
  q=(q||'').toLowerCase().trim();
  var tb=document.getElementById('unb-rows'); if(!tb){return;}
  var n=0,t=tb.rows.length;
  for(var i=0;i<t;i++){var r=tb.rows[i];var ok=q===''||(r.getAttribute('data-s')||'').indexOf(q)>-1;r.style.display=ok?'':'none';if(ok){n++;}}
  var s=document.getElementById('unb-shown'); if(s){s.textContent=$showing.replace('%1',n).replace('%2',t);}
}
</script>";
}

// ── Ecritures ───────────────────────────────────────────────────────────────

function delete_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!unb_can_write()){echo $tpl->js_error($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return;}
    $id=intval($_GET["delete-js"]);
    $r=array();
    $data=acls_api_get("/webfilter/unblock");
    if(is_array($data) && is_array($data["accesses"] ?? null)){
        foreach($data["accesses"] as $a){if(intval($a["id"] ?? 0)==$id){$r=$a;break;}}
    }
    $site=(string)($r["site"] ?? "");
    $who=(string)($r["user"] ?? "");
    $ip=(string)($r["ipaddr"] ?? "");
    if($who===""){$who=$ip;}elseif($ip!==""){$who.=" ($ip)";}
    $text="<strong style='font-size:16px'>".unb_h(strip_tags(unb_t("unb_del_title",$site!==""?$site:"#$id",$who)))."</strong><br><br>".unb_t("unb_del_text");
    $tpl->js_confirm_delete($text,"delete",$id,"LoadAjaxSilent('ufdbweb-unblock-div','$page?table=yes');",true);
}

function delete(){
    $tpl=new template_admin();
    if(!unb_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $id=intval($_POST["delete"]);
    if($id<1){echo "invalid access id";return;}
    list($ok,$err)=acls_api_send("DELETE","/webfilter/unblock/$id");
    // Deja expire et purge entre l'affichage et le clic : le resultat voulu est atteint.
    if(!$ok && $err!=="access not found"){echo unb_h(is_string($err)?$err:"API error");return;}
    admin_tracks("Removed temporary access to a blocked site (#$id)");
}

function empty_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!unb_can_write()){echo $tpl->js_error($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return;}
    $data=acls_api_get("/webfilter/unblock");
    $n=(is_array($data) && is_array($data["accesses"] ?? null))?count($data["accesses"]):0;
    $text="<strong style='font-size:16px'>".unb_h(strip_tags(unb_t("unb_empty_q",number_format($n))))."</strong><br><br>".unb_t("unb_empty_conseq");
    $tpl->js_confirm_delete($text,"empty","yes","LoadAjaxSilent('ufdbweb-unblock-div','$page?table=yes');",true);
}

function empty_table(){
    $tpl=new template_admin();
    if(!unb_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    list($ok,$data)=acls_api_send("DELETE","/webfilter/unblock");
    if(!$ok){echo unb_h(is_string($data)?$data:"API error");return;}
    admin_tracks("Removed all temporary accesses to blocked sites (".intval($data["removed"] ?? 0).")");
}

function unb_style():string{
    if(isset($GLOBALS["UNB_STYLE"])){return "";}
    $GLOBALS["UNB_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.unb{display:flex;flex-direction:column;gap:18px;color:#0f172a;font-size:14px}
.unb-hero{position:relative;display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px 24px 20px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.unb-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.unb-hero-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.unb-hero-body{flex:1;min-width:0}
.unb-hero h3{margin:0 0 6px 0;font-size:18px;font-weight:600}
.unb-hero-sub{font-size:13px;font-weight:400;color:#64748b;margin-left:6px}
.unb-hero p{margin:0;line-height:1.6;color:#475569;max-width:960px}
.unb-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.unb-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155;white-space:nowrap}
.unb-chip i,.unb-chip svg{color:$accent}
.unb-alert{display:flex;gap:12px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#b91c1c}
.unb-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.unb-tile{position:relative;display:flex;align-items:center;gap:14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;min-height:92px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.unb-tile-ico{flex:0 0 44px;height:44px;border-radius:10px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:18px}
.unb-tile-b{flex:1;min-width:0}
.unb-tile-l{font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#475569}
.unb-tile-v{font-size:26px;font-weight:700;line-height:1.2}
.unb-tile-h{position:absolute;right:18px;bottom:12px;font-size:12px;color:#64748b}
.unb-tile-warn{background:#fffbeb;border-color:#fde68a}
.unb-tile-warn .unb-tile-ico{background:#fef3c7;color:#b45309}
.unb-tile-warn .unb-tile-l,.unb-tile-warn .unb-tile-v,.unb-tile-warn .unb-tile-h{color:#b45309}
.unb-note{display:flex;align-items:center;gap:8px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:9px 14px;font-size:13px;color:#475569}
.unb-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.unb-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 20px;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}
.unb-card-h h3{margin:0;font-size:17px;font-weight:600;display:flex;align-items:center;gap:10px}
.unb-card-b{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.unb-count{border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.unb-search{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;background:#fff;color:#64748b}
.unb-search input{border:0;outline:0;height:34px;width:240px;font-size:14px;color:#0f172a;background:transparent}
.unb-search:focus-within{outline:2px solid $accent;outline-offset:1px}
.unb-btn{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:7px 14px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap}
.unb-btn:hover:not([disabled]){background:#f8fafc}
.unb-btn[disabled]{opacity:.5;cursor:default}
.unb-btn-danger{color:#b91c1c;border-color:#fecaca;background:#fef2f2}
.unb-btn-danger:hover:not([disabled]){background:#fee2e2}
.unb-btn:focus-visible,.unb-ibtn:focus-visible{outline:2px solid $accent;outline-offset:2px}
.unb-scroll{overflow-x:auto}
.unb-tbl{width:100%;border-collapse:collapse}
.unb-tbl th{text-align:left;font-size:13px;font-weight:600;color:#334155;background:#f8fafc;padding:10px 20px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.unb-tbl td{padding:12px 20px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#0f172a}
.unb-tbl tbody tr:hover td{background:#f8fafc}
.unb-row-off td{opacity:.6}
.unb-site{display:flex;align-items:center;gap:12px;min-width:0}
.unb-letter{flex:0 0 28px;height:28px;border-radius:50%;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700}
.unb-sname{font-weight:600;overflow-wrap:anywhere}
.unb-sub{font-size:12px;color:#64748b}
.unb-user{overflow-wrap:anywhere}
.unb-unknown{font-style:italic;color:#94a3b8}
.unb-ip{font-family:monospace;font-size:13px;color:#475569}
.unb-ago,.unb-in{border-bottom:1px dotted #cbd5e1;white-space:nowrap}
.unb-in{font-weight:600}
.unb-until{display:flex;flex-direction:column;gap:6px;min-width:200px}
.unb-bar{height:4px;border-radius:999px;background:#e2e8f0;overflow:hidden;max-width:240px}
.unb-bar span{display:block;height:100%;background:$accent}
.unb-bar span.unb-bar-warn{background:#f59e0b}
.unb-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:1px 9px;font-size:12px;font-weight:600;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;white-space:nowrap;margin-left:6px}
.unb-until .unb-pill,.unb-tbl td>.unb-pill:first-child{margin-left:0}
.unb-pill-warn{background:#fffbeb;border-color:#fde68a;color:#b45309;margin-left:8px}
.unb-pill-off{color:#64748b}
.unb-year{font-size:12px;color:#94a3b8}
.unb-act{width:1%;text-align:right;white-space:nowrap}
.unb-ibtn{border:0;background:transparent;color:#64748b;width:32px;height:30px;border-radius:8px;cursor:pointer}
.unb-ibtn:hover{background:#fef2f2;color:#b91c1c}
.unb-foot{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:12px 20px;font-size:12px;color:#64748b}
.unb-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:36px 24px;gap:10px}
.unb-empty-ico{width:68px;height:68px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:26px}
.unb-empty h4{margin:4px 0 0 0;font-size:17px;font-weight:600}
.unb-empty p{margin:0;color:#475569;max-width:600px;line-height:1.6}
@media (max-width:1200px){.unb-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.unb-tbl th:nth-child(3),.unb-tbl td:nth-child(3){display:none}}
@media (prefers-reduced-motion:reduce){.unb *{transition:none!important}}
</style>";
}
