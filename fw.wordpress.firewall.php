<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["wpfw-summary"])){wpfw_summary();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["wpfw-delete"])){delete();exit;}
if(isset($_POST["ID"])){save();exit;}
page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{outgoing_wbl}",
        "fa-solid fa-block-brick-fire","{wpfw_explain}","$page?table=yes","nginx-outfw","reconfigure-wordpress-firewall",false,"wordpress-secure-firewall");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{outgoing_wbl}",$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Liste blanche sortante du pare-feu des services web (maquette stitch_outgoing_allow_list, 2026-11-06) ──
// Une ligne de wp_firewall (wordpress.db) = une destination TCP (adresse, port) que les
// processus nginx peuvent joindre. Le démon reverse-proxy la relit dans
// NginxFW.FirewallBuild() et pousse « add nginxfww <adresse>,tcp:<port> » dans un ipset
// hash:ip,port family inet (maxelem 1 000 000) : UN seul port par règle, IPv4 seulement,
// nom d'hôte résolu au build (première adresse), réseaux dont le masque finit par /8 ou
// /12 ignorés. Il y ajoute d'office les backends actifs, les réseaux de confiance, quatre
// hôtes de mise à jour WordPress et 216.58.204.0/23 (carte « Always allowed »).
// Aucun compteur par règle, aucun historique : la page ne montre que la configuration,
// l'état de l'ipset (/reverse-proxy/firewall/status) et la date du dernier build.

function wpfw_db():string{ return "/home/artica/SQLITE/wordpress.db"; }
/** Capacité de l'ipset nginxfww si l'en-tête ne la donne pas (createIPSet, maxelem). */
function wpfw_capacity_default():int{ return 1000000; }

function wpfw_can():bool{
    $users=new usersMenus();
    return $users->AsWebSecurity || $users->AsSystemAdministrator;
}

/** Texte libre venu de la base : échappé, et les accolades/crochets neutralisés pour le moteur de jetons. */
function wpfw_txt($s):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

/** Jeton traduit, %s remplacés dans l'ordre, sans le <br> final d'une phrase seule. */
function wpfw_tr(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return preg_replace('#(\s*<br\s*/?>\s*)+$#i',"",$s);
}

function wpfw_ago(int $ts,int $now):string{
    $d=max(0,$now-$ts);
    if($d<60){return wpfw_tr("minc_just_now");}
    if($d<3600){return wpfw_tr("minc_ago_min",array(intdiv($d,60)));}
    if($d<86400){return wpfw_tr("minc_ago_h",array(intdiv($d,3600)));}
    if($d<172800){return wpfw_tr("minc_yesterday");}
    return wpfw_tr("minc_ago_d",array(intdiv($d,86400)));
}

function wpfw_accents():array{
    $ac=array("accent"=>"#0f766e","accent_dark"=>"#115e59","accent_soft"=>"rgba(15,118,110,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

/**
 * Nature d'une adresse telle que le build la traitera : ip, net (CIDR IPv4),
 * host (nom résolu au build) ou bad (refusée par l'ipset family inet).
 */
function wpfw_kind(string $addr):string{
    $addr=trim($addr);
    if(filter_var($addr,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){return "ip";}
    if(preg_match('#^([0-9.]+)/([0-9]{1,2})$#',$addr,$m)){
        if(filter_var($m[1],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) && intval($m[2])<=32){return "net";}
        return "bad";
    }
    if(wpfw_is_hostname($addr)){return "host";}
    return "bad";
}
function wpfw_is_hostname(string $s):bool{
    if($s==="" || strlen($s)>253){return false;}
    // Que des chiffres et des points : une IPv4 mal formée, pas un nom.
    if(preg_match('/^[0-9.]+$/',$s)){return false;}
    // Le démon résout par LookupHost : un nom court (localhost, intranet) est valable.
    return preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i',$s)===1;
}
/** Même test que FirewallBuild() : un suffixe /8 ou /12 est sauté sans rien dire. */
function wpfw_skipped(string $addr):bool{
    $addr=trim($addr);
    return substr($addr,-2)=="/8" || substr($addr,-3)=="/12";
}

function wpfw_ensure_table($q){
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `wp_firewall` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`address` TEXT,
	`description` text,
	`enabled` INTEGER NOT NULL DEFAULT 1,
	`port` INTEGER NOT NULL DEFAULT 80)");
}

/** Marque une modification : le bandeau « à appliquer » la compare au dernier build. */
function wpfw_mark_changed(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NginxFWRulesChanged",time());
}

/** État de l'ipset nginxfww vu par le démon (null si le démon ne répond pas). */
function wpfw_status():?array{
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/firewall/status");
    $json=json_decode((string)$raw);
    if(!is_object($json) || !property_exists($json,"Info")){return null;}
    $info=@unserialize((string)$json->Info,array("allowed_classes"=>false));
    if(!is_array($info)){return null;}
    $cap=wpfw_capacity_default();
    if(isset($info["HEADER"]) && preg_match('#maxelem\s+([0-9]+)#',(string)$info["HEADER"],$m)){$cap=intval($m[1]);}
    return array(
        "active"=>strtolower(trim((string)($info["STATUS"] ?? "")))=="true",
        "entries"=>isset($info["NUMBER OF ENTRIES"])?intval($info["NUMBER OF ENTRIES"]):-1,
        "capacity"=>$cap,
    );
}

function wpfw_model():array{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $m=array(
        "enabled"=>intval($S->GET_INFO("EnableNginxFW"))==1,
        "stopped"=>intval($S->GET_INFO("NGINX_FW_STOPPED"))==1,
        "built"=>intval($S->GET_INFO("NGINX_FW_TIMESTAMP")),
        "changed"=>intval($S->GET_INFO("NginxFWRulesChanged")),
        "rules_on"=>0,"rules_off"=>0,"db_error"=>"",
        "status"=>null,"now"=>time(),
    );
    if(!$m["enabled"]){return $m;}
    $q=new lib_sqlite(wpfw_db());
    wpfw_ensure_table($q);
    $r=$q->QUERY_SQL("SELECT enabled,COUNT(*) AS n FROM wp_firewall GROUP BY enabled");
    if(!$q->ok){$m["db_error"]=$q->mysql_error;}
    foreach((is_array($r)?$r:array()) as $l){
        if(intval($l["enabled"])==1){$m["rules_on"]+=intval($l["n"]);}else{$m["rules_off"]+=intval($l["n"]);}
    }
    $m["status"]=wpfw_status();
    return $m;
}

// ── Widgets ──

function wpfw_style():string{
    if(!empty($GLOBALS["WPFW_CSS_DONE"])){return "";}
    $GLOBALS["WPFW_CSS_DONE"]=true;
    $ac=wpfw_accents(); $a=$ac["accent"];
    return "<style>
.wpfw{margin-top:12px;color:#0f172a;font-size:13px}
.wpfw-strip{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:16px}
.wpfw-cell{padding:16px 20px;min-height:88px;min-width:0;border-left:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;gap:12px}
.wpfw-cell:first-child{border-left:0}
.wpfw-lbl{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;line-height:16px}
.wpfw-big{font-size:22px;font-weight:600;line-height:28px;margin-top:4px;white-space:nowrap}
.wpfw-sub{font-size:13px;color:#64748b;font-weight:400}
.wpfw-cell .ak-pill{margin-top:6px}
.wpfw-dot{margin-right:6px}
.wpfw-rules{margin-top:6px;font-size:15px;font-weight:600;white-space:nowrap}
.wpfw-rules .sep{color:#cbd5e1;margin:0 8px}
.wpfw-ring{position:relative;width:52px;height:52px;flex:0 0 52px}
.wpfw-ring span{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#334155}
.wpfw-banner{justify-content:space-between;gap:16px;padding:12px 16px;margin-bottom:16px;font-weight:600}
.wpfw-banner .ic{width:28px;height:28px;flex:0 0 28px;border-radius:50%;background:rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;font-size:13px}
.wpfw-banner .msg{display:flex;align-items:center;gap:12px}
.wpfw-aa-h:focus-visible{outline:2px solid $a;outline-offset:2px}
.wpfw-tcard{padding:16px}
.wpfw-tbar{display:flex;align-items:center;justify-content:flex-end;margin-bottom:10px}
.wpfw-table th{white-space:nowrap}.wpfw-table td{font-size:13px}
.wpfw-table tbody tr{transition:background .15s}
.wpfw-table tbody tr:hover{background:#f8fafc}
.wpfw-table tr.off td.dim{opacity:.6}
.wpfw-dest{display:flex;align-items:center;gap:10px;min-width:0}
.wpfw-addr{font-size:14px;font-weight:600;cursor:pointer;word-break:break-all}
.wpfw-addr:hover{text-decoration:underline}
.wpfw-chip{display:inline-flex;align-items:center;gap:5px}
.wpfw-note{font-size:12px;color:#64748b;margin-top:3px}
.wpfw-desc{display:block;max-width:520px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#4b5563}
.wpfw-acts{white-space:nowrap;text-align:right}
.wpfw-empty{padding:40px 16px}
.wpfw-empty h3{margin:0;font-size:16px;font-weight:600;color:#0f172a}
.wpfw-empty p{margin:0 0 8px}
.wpfw-aa{margin-top:16px}
.wpfw-aa-h{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%;padding:14px 16px;border:0;background:transparent;text-align:left;cursor:pointer;border-radius:10px}
.wpfw-aa-h .ttl{display:flex;align-items:center;gap:12px}
.wpfw-aa-h h4{margin:0;font-size:15px;font-weight:600;color:#0f172a}
.wpfw-aa-h p{margin:2px 0 0;font-size:13px;color:#64748b}
.wpfw-chev{display:inline-block;transition:transform .15s;color:#64748b;font-size:13px}
.wpfw-aa-h[aria-expanded=true] .wpfw-chev{transform:rotate(180deg)}
.wpfw-aa-b{border-top:1px solid #f1f5f9;padding:4px 16px 8px}
.wpfw-aa-b[hidden]{display:none}
.wpfw-aa-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.wpfw-aa-row:last-child{border-bottom:0}
.wpfw-aa-row .k{display:flex;align-items:center;gap:10px;font-weight:600;color:#1e293b;white-space:nowrap}
.wpfw-aa-row .k .i{width:18px;text-align:center;color:#64748b;font-size:14px}
.wpfw-aa-row .v{display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:6px;color:#475569;text-align:right}
.wpfw-off{max-width:640px;margin:24px auto;padding:32px;text-align:center}
.wpfw-off .big{font-size:30px;color:#94a3b8;margin-bottom:12px}
.wpfw-off h3{margin:0 0 6px;font-size:17px;font-weight:600}
.wpfw-off p{margin:0 0 18px;color:#475569}
@media (max-width:1200px){.wpfw-strip{grid-template-columns:repeat(2,minmax(0,1fr))}.wpfw-cell:nth-child(3){border-left:0}.wpfw-cell:nth-child(n+3){border-top:1px solid #e5e7eb}}
@media (prefers-reduced-motion:reduce){.wpfw *{transition:none!important;animation:none!important}}
</style>";
}

function wpfw_cell_state(array $m):string{
    $st=$m["status"];
    if($st===null){
        $pill="<span class='ak-pill ak-off'><span style='font-size:12px'><i class='fa-solid fa-circle-question'></i></span>{wpfw_unknown}</span>";
    }elseif($st["active"] && !$m["stopped"]){
        $pill="<span class='ak-pill ak-ok'><span style='font-size:12px'><i class='fa-solid fa-shield-check'></i></span>{wpfw_active}</span>";
    }else{
        $pill="<span class='ak-pill ak-warn'><span style='font-size:12px'><i class='fa-solid fa-pause'></i></span>{wpfw_stopped}</span>";
    }
    return "<div class='wpfw-cell' data-cell='state'><div><div class='wpfw-lbl'>{wpfw_state}</div>$pill</div></div>";
}

function wpfw_cell_applied(array $m):string{
    if($m["built"]<=0){
        $v="<div class='wpfw-big'>{wpfw_never}</div>";
    }else{
        $abs=date("Y-m-d H:i:s",$m["built"]);
        $v="<div class='wpfw-big' title='$abs'>".wpfw_ago($m["built"],$m["now"])." <span class='wpfw-sub ak-mono' style='font-size:12px'>($abs)</span></div>";
    }
    return "<div class='wpfw-cell' data-cell='applied'><div style='min-width:0'><div class='wpfw-lbl'>{wpfw_last_applied}</div>$v</div></div>";
}

/** Anneau SVG : entrées chargées contre la capacité réelle de l'ipset (arc minimal visible dès 1 entrée). */
function wpfw_ring(int $n,int $cap):string{
    $ac=wpfw_accents();
    $pct=$cap>0?($n*100/$cap):0;
    $dash=$pct;
    if($n>0 && $dash<2){$dash=2;}
    if($dash>100){$dash=100;}
    $color=$pct>=90?"#b91c1c":($pct>=75?"#b45309":$ac["accent"]);
    $lbl=$pct<1&&$n>0?"&lt;1%":sprintf("%d%%",round($pct));
    if($pct>0 && $pct<10 && $pct>=1){$lbl=sprintf("%.1f%%",$pct);}
    return "<div class='wpfw-ring' role='img' aria-label='$lbl'><svg viewBox='0 0 36 36' width='52' height='52'>
<circle cx='18' cy='18' r='15.9155' fill='none' stroke='#e5e7eb' stroke-width='3.5'/>
<circle cx='18' cy='18' r='15.9155' fill='none' stroke='$color' stroke-width='3.5' stroke-linecap='round' stroke-dasharray='".sprintf("%.2f",$dash).",100' transform='rotate(-90 18 18)'/>
</svg><span>$lbl</span></div>";
}

function wpfw_cell_entries(array $m):string{
    $st=$m["status"];
    if($st===null || $st["entries"]<0){
        return "<div class='wpfw-cell' data-cell='entries'><div><div class='wpfw-lbl'>{wpfw_entries}</div><div class='wpfw-big'>&mdash;</div></div></div>";
    }
    $n=$st["entries"]; $cap=$st["capacity"];
    return "<div class='wpfw-cell' data-cell='entries'><div style='min-width:0'><div class='wpfw-lbl'>{wpfw_entries}</div>
<div class='wpfw-big'>".number_format($n)." <span class='wpfw-sub'>{wpfw_of} ".number_format($cap)."</span></div></div>".wpfw_ring($n,$cap)."</div>";
}

function wpfw_cell_rules(array $m):string{
    $ac=wpfw_accents();
    if($m["db_error"]!==""){
        return "<div class='wpfw-cell' data-cell='rules'><div><div class='wpfw-lbl'>{wpfw_your_rules}</div><div class='wpfw-big'>&mdash;</div></div></div>";
    }
    return "<div class='wpfw-cell' data-cell='rules'><div><div class='wpfw-lbl'>{wpfw_your_rules}</div>
<div class='wpfw-rules'><span class='ak-dot wpfw-dot' style='background:{$ac["accent"]}'></span>{$m["rules_on"]} {wpfw_enabled_n}<span class='sep'>&middot;</span><span class='ak-dot wpfw-dot' style='background:#9ca3af'></span>{$m["rules_off"]} {wpfw_disabled_n}</div></div></div>";
}

function wpfw_strip(array $m):string{
    return "<div class='ak-card wpfw-strip'>".wpfw_cell_state($m).wpfw_cell_applied($m).wpfw_cell_entries($m).wpfw_cell_rules($m)."</div>";
}

/** Bandeau « à appliquer » : une modification faite ici est plus récente que le dernier build réussi. */
function wpfw_pending_banner(array $m):string{
    if($m["changed"]<=0 || $m["changed"]<=$m["built"]){return "";}
    return "<div class='ak-banner ak-warn wpfw-banner' role='status' id='wpfw-pending'><div class='msg'><span class='ic'><i class='fa-solid fa-triangle-exclamation'></i></span>".wpfw_tr("wpfw_pending")."</div>
<button type='button' class='ak-btn' OnClick=\"WpfwApply();\"><span><i class='fa-solid fa-arrows-rotate'></i></span>{apply_configuration}</button></div>";
}

function wpfw_disabled_card():string{
    return "<div class='ak-card wpfw-off' data-state='off'><div class='big'><i class='fa-solid fa-shield-slash'></i></div>
<h3>{wpfw_off_title}</h3><p>".wpfw_tr("wpfw_off_text")."</p>
<button type='button' class='ak-btn ak-btn-primary' OnClick=\"document.location.href='/nginx-status';\"><span><i class='fa-solid fa-gauge'></i></span>{wpfw_off_btn}</button></div>";
}

/** Contenu de l'hôte rafraîchi : bandeau d'état + bandeau « à appliquer ». */
function wpfw_summary(){
    $tpl=new template_admin();
    $m=wpfw_model();
    if(!$m["enabled"]){echo wpfw_style().$tpl->_ENGINE_parse_body(wpfw_disabled_card());return;}
    echo wpfw_style().$tpl->_ENGINE_parse_body(wpfw_strip($m).wpfw_pending_banner($m));
}

function wpfw_kind_chip(string $kind):string{
    $map=array(
        "ip"=>array("fa-solid fa-location-dot","{wpfw_kind_ip}"),
        "net"=>array("fa-solid fa-network-wired","{wpfw_kind_net}"),
        "host"=>array("fa-solid fa-globe","{wpfw_kind_host}"),
        "bad"=>array("fa-solid fa-circle-exclamation","{wpfw_kind_bad}"),
    );
    $x=$map[$kind] ?? $map["bad"];
    return "<span class='ak-tag wpfw-chip ".(array("ip"=>"ak-info","host"=>"ak-ok","net"=>"ak-violet")[$kind] ?? "ak-err")."'><span style='font-size:11px'><i class='{$x[0]}'></i></span>{$x[1]}</span>";
}

function wpfw_row(array $l,bool $can):string{
    $page=CurrentPageName();
    $ID=intval($l["ID"]);
    $addr=trim((string)$l["address"]);
    $port=intval($l["port"]);
    $on=intval($l["enabled"])==1;
    $kind=wpfw_kind($addr);
    $desc=trim(html_entity_decode((string)$l["description"],ENT_QUOTES,"UTF-8"));

    $note="";
    if($kind=="host"){$note="<div class='wpfw-note'>{wpfw_resolved}</div>";}
    if($kind=="bad"){$note="<div class='wpfw-note' style='color:#b91c1c'>".wpfw_tr("wpfw_bad_note")."</div>";}
    $skip="";
    if($kind=="net" && wpfw_skipped($addr)){
        $skip=" <span class='ak-tag ak-warn wpfw-chip' title=\"".wpfw_tr("wpfw_skip_tip")."\"><span style='font-size:11px'><i class='fa-solid fa-ban'></i></span>{wpfw_skipped}</span>";
    }
    $dis=$can?"":" disabled";
    $sw="<button type='button' class='ak-sw' role='switch' id='wpfw-sw-$ID' aria-checked='".($on?"true":"false")."' aria-label='{enabled}: ".wpfw_txt($addr).":$port'$dis OnClick=\"Loadjs('$page?enable=$ID');\"></button>";
    $edit="Loadjs('$page?js=$ID');";
    $acts="<button type='button' class='ak-act' aria-label='{wpfw_edit}' title='{wpfw_edit}' OnClick=\"$edit\"><i class='fa-solid fa-pen'></i></button>";
    if($can){
        $acts.="<button type='button' class='ak-act ak-danger' aria-label='{wpfw_delete}' title='{wpfw_delete}' OnClick=\"Loadjs('$page?delete-js=$ID');\"><i class='fa-solid fa-trash-can'></i></button>";
    }
    $descHtml=$desc===""?"<span class='wpfw-desc' style='color:#94a3b8'>&mdash;</span>":"<span class='wpfw-desc' title=\"".wpfw_txt($desc)."\">".wpfw_txt($desc)."</span>";

    return "<tr id='wpfw-r-$ID' class='".($on?"on":"off")."'>
<td style='width:1%'>$sw</td>
<td class='dim'><div class='wpfw-dest'>".wpfw_kind_chip($kind)."<span class='wpfw-addr ak-mono' OnClick=\"$edit\">".wpfw_txt($addr)."</span>$skip</div>$note</td>
<td class='dim' style='width:1%'><span class='ak-chip'>TCP $port</span></td>
<td class='dim'>$descHtml</td>
<td class='wpfw-acts' style='width:1%'>$acts</td></tr>";
}

function wpfw_empty_state(bool $searching,bool $can):string{
    if($searching){
        return "<div class='ak-empty-c wpfw-empty'><i class='fa-solid fa-filter-circle-xmark'></i><h3>{wpfw_no_match}</h3></div>";
    }
    $btn=$can?"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('".CurrentPageName()."?js=0');\"><span><i class='fa-solid fa-plus'></i></span>{new_rule}</button>":"";
    return "<div class='ak-empty-c wpfw-empty' data-state='empty'><i class='fa-solid fa-block-brick-fire'></i><h3>{wpfw_empty_title}</h3><p>".wpfw_tr("wpfw_empty_text")."</p>$btn</div>";
}

function wpfw_rules_card(array $rows,bool $searching,bool $can):string{
    $n=count($rows);
    $count=$n." ".($n==1?"{wpfw_rule_one}":"{wpfw_rules_n}");
    $h=array();
    $h[]="<div class='ak-card wpfw-tcard'><div class='wpfw-tbar'><span class='ak-count' id='wpfw-count'>$count</span></div>";
    if($n==0){
        $h[]=wpfw_empty_state($searching,$can)."</div>";
        return @implode("\n",$h);
    }
    $h[]="<table class='ak-tbl wpfw-table' id='wpfw-table'><thead><tr><th>{enabled}</th><th>{wpfw_destination}</th><th>{wpfw_port}</th><th>{description}</th><th style='text-align:right'>{wpfw_actions}</th></tr></thead><tbody>";
    foreach($rows as $l){$h[]=wpfw_row($l,$can);}
    $h[]="</tbody></table></div>";
    return @implode("\n",$h);
}

function wpfw_always_row(string $ico,string $label,string $value):string{
    return "<div class='wpfw-aa-row'><div class='k'><span class='i'><i class='$ico'></i></span>$label</div><div class='v'>$value</div></div>";
}

/** Ce que FirewallBuild() autorise d'office, hors de wp_firewall (lecture seule). */
function wpfw_always_card():string{
    $p8="<span class='ak-chip'>TCP 80</span><span class='ak-chip'>TCP 443</span>";
    $hosts="";
    foreach(array("api.wordpress.org","updates.theme-fusion.com","avada.theme-fusion.com","ip-info.ff.avast.com") as $hn){
        $hosts.="<span class='ak-tag ak-ok ak-mono'>$hn</span>";
    }
    $rows=array();
    $rows[]=wpfw_always_row("fa-solid fa-server","{wpfw_aa_backends}","{wpfw_aa_backends_v}");
    $rows[]=wpfw_always_row("fa-solid fa-shield-halved","{wpfw_aa_trusted}","{wpfw_aa_trusted_v} $p8");
    $rows[]=wpfw_always_row("fa-brands fa-wordpress","{wpfw_aa_wp}",$hosts.$p8);
    $rows[]=wpfw_always_row("fa-brands fa-google","{wpfw_aa_google}","<span class='ak-tag ak-violet ak-mono'>216.58.204.0/23</span>$p8");
    return "<div class='ak-card wpfw-aa'><button type='button' class='wpfw-aa-h' id='wpfw-aa-h' aria-expanded='false' aria-controls='wpfw-aa-b' OnClick=\"WpfwToggleAA(this);\">
<span class='ttl'><span class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='fa-solid fa-lock'></i></span><span><h4>{wpfw_always}</h4><p>{wpfw_always_sub}</p></span></span>
<span class='wpfw-chev'><i class='fa-solid fa-chevron-down'></i></span></button>
<div class='wpfw-aa-b' id='wpfw-aa-b' hidden>".@implode("",$rows)."</div></div>";
}

function wpfw_apply_js():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("nginx:/reverse-proxy/firewall/build",
        "wordpress.firewall.progress","wordpress.firewall.progress.txt",
        "reconfigure-wordpress-firewall","WpfwSummary();");
}

function wpfw_js():string{
    $page=CurrentPageName();
    $apply=wpfw_apply_js();
    return "<script>
(function(){
window.WpfwSummary=function(){ if(document.getElementById('wpfw-summary')){LoadAjaxSilent('wpfw-summary','$page?wpfw-summary=yes');} };
window.WpfwReload=function(){ WpfwSummary(); if(typeof window.WpfwSearchFn==='function'){window.WpfwSearchFn();} };
window.WpfwApply=function(){ $apply };
window.WpfwSwitched=function(id,on){
  var b=document.getElementById('wpfw-sw-'+id),r=document.getElementById('wpfw-r-'+id);
  if(b){b.setAttribute('aria-checked',on?'true':'false');}
  if(r){r.className=on?'on':'off';}
  WpfwSummary();
};
window.WpfwToggleAA=function(btn){
  var b=document.getElementById('wpfw-aa-b'); if(!b){return;}
  var open=btn.getAttribute('aria-expanded')==='true';
  btn.setAttribute('aria-expanded',open?'false':'true');
  if(open){b.setAttribute('hidden','hidden');}else{b.removeAttribute('hidden');}
};
if(window.WpfwTimer){clearInterval(window.WpfwTimer);}
window.WpfwTimer=setInterval(function(){
  if(!document.getElementById('wpfw-summary')){clearInterval(window.WpfwTimer);window.WpfwTimer=null;return;}
  if(document.querySelector('.modal.in,.modal.show,.jconfirm,.sweet-alert.visible')){return;}
  WpfwSummary();
},60000);
})();
</script>";
}

// ── Gestionnaires ──

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=wpfw_model();
    if(!$m["enabled"]){
        $TINY=array("TITLE"=>"{outgoing_wbl}","ICO"=>"fa-solid fa-block-brick-fire","EXPL"=>"{wpfw_explain}","URL"=>"nginx-outfw","BUTTONS"=>"");
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
        echo wpfw_style().$tpl->_ENGINE_parse_body("<div class='wpfw ak'>".wpfw_disabled_card()."</div>")."<script>NoSpinner();$jstiny</script>";
        return;
    }
    $html=array();
    $html[]="<div class='wpfw ak'>";
    $html[]="<div id='wpfw-summary'>".wpfw_strip($m).wpfw_pending_banner($m)."</div>";
    $html[]=$tpl->search_block($page,"","");
    $html[]=wpfw_always_card();
    $html[]="</div>";
    echo wpfw_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).wpfw_js();
}

function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $can=wpfw_can();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"))==0){
        echo wpfw_style().$tpl->_ENGINE_parse_body(wpfw_disabled_card());
        return;
    }
    $raw=trim((string)($_GET["search"] ?? ""));
    $searching=$raw!=="";
    $search=str_replace("*","%","*$raw*");
    $search=str_replace("%%","%",$search);

    $q=new lib_sqlite(wpfw_db());
    wpfw_ensure_table($q);
    // Pentest 2026-11-04 : search etait concatene dans la requete.
    $results=$q->QUERY_SQL_PARAMS("SELECT * FROM wp_firewall WHERE (address LIKE ? OR description LIKE ?) ORDER BY address,port",array($search,$search));
    $html=array();
    if(!$q->ok){
        $html[]=$tpl->div_error("SQL ERROR||".wpfw_txt($q->mysql_error));
        $results=array();
    }
    if(!is_array($results)){$results=array();}
    $html[]=wpfw_rules_card($results,$searching,$can);

    $topbuttons=array();
    if($can){
        $topbuttons[]=array("Loadjs('$page?js=0');",ico_plus,"{new_rule}");
        $topbuttons[]=array("WpfwApply();","fa-solid fa-arrows-rotate","{apply_configuration}");
    }
    $TINY=array();
    $TINY["TITLE"]="{outgoing_wbl}";
    $TINY["ICO"]="fa-solid fa-block-brick-fire";
    $TINY["EXPL"]="{wpfw_explain}";
    $TINY["URL"]="nginx-outfw";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    $setfn=$function!==""?"window.WpfwSearchFn=function(){ if(typeof window['$function']==='function'){window['$function']();} };":"";
    echo wpfw_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>NoSpinner();$setfn\n$jstiny</script>";
}

function js(){
    $page=CurrentPageName();
    $ID=intval($_GET["js"]);
    $tpl=new template_admin();
    if($ID==0){$title="{new_rule}";}else{
        $q=new lib_sqlite(wpfw_db());
        $ligne=$q->mysqli_fetch_array("SELECT address,port FROM wp_firewall WHERE ID=$ID");
        $addr=preg_replace('/[^A-Za-z0-9.:\/_-]/','',(string)($ligne["address"] ?? ""));
        $title=$addr.":".intval($ligne["port"] ?? 0);
    }
    $tpl->js_dialog1($title,"$page?popup=$ID");
}

function popup(){
    $ID=intval($_GET["popup"]);
    $tpl=new template_admin();
    $ligne=array("address"=>"","port"=>"443","description"=>"");
    $btname="{create}";
    $title="{new_rule}";
    if($ID>0){
        $q=new lib_sqlite(wpfw_db());
        $l=$q->mysqli_fetch_array("SELECT * FROM wp_firewall WHERE ID=$ID");
        if(is_array($l)){$ligne=$l;}
        $title=wpfw_txt($ligne["address"]);
        $btname="{apply}";
    }
    $jsafter="dialogInstance1.close();WpfwReload();";
    $form=array();
    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_text("address","{address}",$ligne["address"],true,"{wpfw_address_help}");
    $form[]=$tpl->field_numeric("port","{remote_port}",$ligne["port"],"{wpfw_port_help}");
    $form[]=$tpl->field_text("description","{description}",$ligne["description"]);
    echo $tpl->form_outside($title,$form,"{wpfw_form_explain}",$btname,$jsafter,"AsWebSecurity",true);
}

function save(){
    $tpl=new template_admin();
    if(!wpfw_can()){echo $tpl->post_error("{ERROR_NO_PRIVS}");return;}
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $port=intval($_POST["port"]);
    $address=strtolower(trim((string)$_POST["address"]));
    if(wpfw_kind($address)=="bad"){echo $tpl->post_error(wpfw_tr("wpfw_err_address"));return;}
    if($port<1 || $port>65535){echo $tpl->post_error(wpfw_tr("wpfw_err_port"));return;}
    $description=trim((string)$_POST["description"]);

    $q=new lib_sqlite(wpfw_db());
    wpfw_ensure_table($q);
    if($ID==0){
        $q->QUERY_SQL_PARAMS("INSERT INTO wp_firewall (address,port,description) VALUES(?,?,?)",array($address,$port,$description));
        if(!$q->ok){echo $tpl->post_error($tpl->javascript_parse_text($q->mysql_error));return;}
        wpfw_mark_changed();
        admin_tracks("Add a new whitelisted destination for Web Firewall $address:$port");
        return;
    }
    $q->QUERY_SQL_PARAMS("UPDATE wp_firewall SET address=?,port=?,description=? WHERE ID=?",array($address,$port,$description,$ID));
    if(!$q->ok){echo $tpl->post_error($tpl->javascript_parse_text($q->mysql_error));return;}
    wpfw_mark_changed();
    admin_tracks("Update whitelisted destination for Web Firewall $address:$port");
}

function enable(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!wpfw_can()){echo $tpl->js_mysql_alert($tpl->javascript_parse_text("{ERROR_NO_PRIVS}"));return;}
    $ID=intval($_GET['enable']);
    $q=new lib_sqlite(wpfw_db());
    $ligne=$q->mysqli_fetch_array("SELECT address,port,enabled FROM wp_firewall WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
    if(!is_array($ligne) || !isset($ligne["enabled"])){return;}
    $ena=intval($ligne["enabled"])==0?1:0;
    $q->QUERY_SQL("UPDATE wp_firewall SET enabled=$ena WHERE ID=$ID");
    if(!$q->ok){echo $tpl->js_mysql_alert($q->mysql_error);return;}
    wpfw_mark_changed();
    admin_tracks(($ena==1?"Enable":"Disable")." whitelisted destination for Web Firewall {$ligne["address"]}:{$ligne["port"]}");
    echo "WpfwSwitched($ID,".($ena==1?"true":"false").");";
}

function delete_js(){
    $tpl=new template_admin();
    $ID=intval($_GET["delete-js"]);
    $q=new lib_sqlite(wpfw_db());
    $l=$q->mysqli_fetch_array("SELECT address,port FROM wp_firewall WHERE ID=$ID");
    $addr=preg_replace('/[^A-Za-z0-9.:\/_-]/','',(string)($l["address"] ?? ""));
    $tpl->js_confirm_delete("$addr:".intval($l["port"] ?? 0),"wpfw-delete",$ID,"WpfwReload()");
}

function delete(){
    $tpl=new template_admin();
    if(!wpfw_can()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return;}
    $ID=intval($_POST['wpfw-delete']);
    $q=new lib_sqlite(wpfw_db());
    $l=$q->mysqli_fetch_array("SELECT address,port FROM wp_firewall WHERE ID=$ID");
    $q->QUERY_SQL("DELETE FROM wp_firewall WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return;}
    wpfw_mark_changed();
    admin_tracks("Delete whitelisted destination for Web Firewall ".($l["address"] ?? "")." :".($l["port"] ?? ""));
}
