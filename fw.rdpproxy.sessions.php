<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["start"])){rdpsx_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_POST["disconnect"])){disconnect_perform();exit;}


page();


// Couper une session (RDP, VNC ou SSH) : AsSquidAdministrator, comme le journal d'acces et
// les enregistrements. Le bouton est masque sans ce droit ; le POST le verifie aussi.
function rdps_allowed():bool{
    if(!isset($GLOBALS["RDPS_ALLOWED"])){
        $users=new usersMenus();
        $GLOBALS["RDPS_ALLOWED"]=(bool)$users->AsSquidAdministrator;
    }
    return $GLOBALS["RDPS_ALLOWED"];
}

function disconnect(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdps_allowed()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_GET["disconnect"]);
    $error="";
    $ligne=rdpproxy_session($ID,$error);
    if($error!=""){echo $tpl->js_error($error);return;}
    $psid=intval($ligne["psid"]);
    $proto=rdps_proto($ligne);
    // La coupure n'est pas immediate : authhook lit la demande au keepalive suivant (<= 30 s),
    // pour RDP, VNC comme SSH. Le dire avant, sinon la ligne « encore la » se lit comme un echec.
    $tpl->js_dialog_confirm_action("{disconnect}: {session} $proto $psid. {rdpproxy_disconnect_delay}","disconnect",$ID,rdpsx_reload_js());

}
// rdp_confirm_error : la boite de confirmation (js_dialog_confirm_action / js_confirm_delete)
// affiche la reponse telle quelle en HTML et ne connait pas le prefixe « jserror: » de
// post_error : texte traduit, sans balise, echappe.
// Les valeurs des jetons sont deja en entites (&egrave;, &#233;) : les decoder avant
// d'echapper, sinon « &amp;egrave; » s'affiche. Non-ASCII reencode en entites numeriques.
function rdp_confirm_error($tpl,$msg):string{
    $t=strip_tags($tpl->_ENGINE_parse_body((string)$msg));
    $t=html_entity_decode($t,ENT_QUOTES|ENT_HTML5,"UTF-8");
    $t=htmlspecialchars($t,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8");
    if(function_exists("mb_encode_numericentity")){$t=mb_encode_numericentity($t,array(0x80,0x10FFFF,0,0x1FFFFF),"UTF-8");}
    return $t;
}

function disconnect_perform(){
    $tpl=new template_admin();
    if(!rdps_allowed()){echo rdp_confirm_error($tpl,"{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["disconnect"]);
    // Lu AVANT la deconnexion, pour nommer la session dans le journal.
    $err=""; $s=rdpproxy_session($ID,$err);
    $who=isset($s["username"])?$s["username"]:"";
    $to=isset($s["target_alias"])&&$s["target_alias"]!=""?$s["target_alias"]:(isset($s["target_host"])?$s["target_host"]:"");
    $proto=is_array($s)&&count($s)>0?rdps_proto($s):"";
    $error=rdpproxy_session_disconnect($ID);
    if($error!=""){echo rdp_confirm_error($tpl,$error);return;}
    admin_tracks("Disconnected RDS Proxy ".($proto!=""?"$proto ":"")."session #$ID".($who!=""?" of $who":"").($to!=""?" to $to":""));
}






// rdps_proto : protocole de la session (RDP, VNC, SSH ou WEB). Un daemon anterieur au champ
// proto_dest ne connaissait que RDP ; avant le 14/11 il rendait aussi WEB en RDP (retour RWE).
function rdps_proto($ligne):string{
    $p=strtoupper(trim(isset($ligne["proto_dest"])?(string)$ligne["proto_dest"]:""));
    if(!in_array($p,array("RDP","VNC","SSH","WEB"),true)){$p="RDP";}
    return $p;
}

function rdps_proto_icon($proto):string{
    if($proto=="SSH"){return "fas fa-terminal";}
    if($proto=="VNC"){return "fas fa-tv";}
    if($proto=="WEB"){return "fas fa-globe";}
    return "fa fa-desktop";
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    $title="{APP_RDPPROXY} v$version &raquo;&raquo; {sessions}";
    $html=$tpl->page_header($title,"fas fa-desktop","{rdpsx_explain}",
        "$page?start=yes","rdpproxy-sessions","progress-rdpproxy-restart",false,"table-rdpproxy-sessions");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin($title,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// Hote du tableau de bord, recharge toutes les 10 s (sauf pendant une saisie dans la recherche).
// La recherche et les filtres vivent dans window.RdpsxQ / RdpsxSt / RdpsxPr : un rechargement
// les reapplique au lieu de les remettre a zero.
function rdpsx_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $reload=rdpsx_reload_js();
    // Sessions Web gardees (RWE 14/11) : sur la meme page que les sessions ouvertes, avec leur etat
    // et leur action Fermer. Tableau et fermeture de fw.rdpproxy.status.php (un seul code) ; la
    // fenetre de confirmation rappelle RwkReload, definie ici.
    $kept="";
    if(rdpproxy_web_keep_state()["supported"]){
        $kept="<div class='rdpsx-kept'><div class='rdpsx-kept-h'><i class='fas fa-clock-rotate-left'></i> {rdpproxy_webkeep_tab}</div>"
            ."<div id='rdpsx-kept'></div></div>";
    }
    $keptH=$tpl->_ENGINE_parse_body($kept)."<style>.rdpsx-kept{margin:18px 0 0 0}.rdpsx-kept-h{font-size:15px;font-weight:700;color:#111d23;margin:0 0 8px 2px}.rdpsx-kept-h i{color:#0f766e;margin-right:6px}</style>";
    echo "<div id='rdpsx-host'></div>$keptH<script>
function RwkReload(){ if(document.getElementById('rdpsx-kept')){LoadAjaxSilent('rdpsx-kept','fw.rdpproxy.status.php?webview-keep-table=yes');} }
window.RdpsxQ=window.RdpsxQ||''; window.RdpsxSt=window.RdpsxSt||''; window.RdpsxPr=window.RdpsxPr||'';
$reload
if(window.RdpsxTimer){clearInterval(window.RdpsxTimer);}
window.RdpsxTimer=setInterval(function(){
  if(!document.getElementById('rdpsx-host')){clearInterval(window.RdpsxTimer);window.RdpsxTimer=null;return;}
  var a=document.activeElement; if(a && a.id==='rdpsx-q'){return;}
  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.jconfirm')){return;}
  $reload
},10000);
</script>";
    $topbuttons=array();
    $topbuttons[]=array($reload,ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{APP_RDPPROXY} &raquo;&raquo; {sessions}";
    $TINY["ICO"]="fas fa-desktop";
    $TINY["EXPL"]="{rdpsx_explain}";
    $TINY["URL"]="rdpproxy-sessions";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    echo "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
}

function rdpsx_reload_js():string{
    $page=CurrentPageName();
    return "LoadAjaxSilent('rdpsx-host','$page?table=yes');if(typeof RwkReload==='function'){RwkReload();}";
}

/** Duree lisible : "6 h 42 min", "48 min", "35 s", "2 j 3 h". */
function rdpsx_dur($s):string{
    $s=max(0,intval($s));
    if($s<60){return "$s s";}
    $d=intdiv($s,86400); $h=intdiv($s%86400,3600); $m=intdiv($s%3600,60);
    if($d>0){return "$d {rdpt_days_short} $h h";}
    if($h>0){return "$h h ".sprintf("%02d",$m)." min";}
    return "$m min";
}

function rdpsx_state($ligne):string{
    $st=(string)($ligne["state"] ?? "");
    return in_array($st,array("connected","closing","unknown"),true)?$st:"connected";
}

/** Tableau de bord : indicateurs, cibles les plus utilisees, filtres, tableau. */
function table(){
    $tpl=new template_admin();
    $error="";
    $rows=rdpproxy_sessions($error);
    if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($error,ENT_QUOTES)))."<script>NoSpinner();</script>";return;}
    if(!is_array($rows)){$rows=array();}
    $list=array();
    foreach($rows as $r){ if(is_array($r)){$list[]=$r;} }
    $now=time();
    // Les plus anciennes d'abord : la session ouverte depuis le plus longtemps est celle qu'on cherche.
    usort($list,function($a,$b){ return intval($a["created"] ?? 0)-intval($b["created"] ?? 0); });

    $html=array();
    $html[]="<div class='rdpsx'>";
    $html[]="<div class='rdpsx-kpis'>";
    $html[]=rdpsx_kpi_open($list);
    $html[]=rdpsx_kpi_protocols($list);
    $html[]=rdpsx_kpi_duration($list,$now);
    $html[]=rdpsx_kpi_watch($list);
    $html[]="</div>";
    if(count($list)>0){$html[]=rdpsx_top_targets($list);}
    $html[]=rdpsx_table($list,$now);
    $html[]="</div>";
    echo rdpsx_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpsx_js();
}

function rdpsx_kpi($label,$value,$sub,$art,$cls=""):string{
    return "<div class='rdpsx-card rdpsx-kpi $cls'><div class='rdpsx-kpi-b'><span class='rdpsx-kpi-l'>$label</span>"
        ."<span class='rdpsx-kpi-v'>$value</span><span class='rdpsx-kpi-s'>$sub</span></div><div class='rdpsx-kpi-art'>$art</div></div>";
}

function rdpsx_kpi_open(array $list):string{
    $users=array();$targets=array();
    foreach($list as $l){
        $users[strtolower((string)($l["username"] ?? ""))]=true;
        $targets[intval($l["target_id"] ?? 0).":".(string)($l["target_alias"] ?? "")]=true;
    }
    $sub=count($list)>0?count($users)." {rdpsx_users} &middot; ".count($targets)." {rdpsx_targets_n}":"{rdpsx_none_open}";
    return rdpsx_kpi("{rdpsx_open}",(string)count($list),$sub,"<span class='rdpsx-kpi-ico'><i class='fas fa-network-wired'></i></span>");
}

function rdpsx_kpi_protocols(array $list):string{
    $c=array("RDP"=>0,"VNC"=>0,"SSH"=>0,"WEB"=>0);
    foreach($list as $l){ $c[rdps_proto($l)]++; }
    $sub="<span class='rdpsx-lg'><span class='rdpsx-dot rdpsx-p-rdp'></span>{$c["RDP"]} RDP</span>"
        ."<span class='rdpsx-lg'><span class='rdpsx-dot rdpsx-p-vnc'></span>{$c["VNC"]} VNC</span>"
        ."<span class='rdpsx-lg'><span class='rdpsx-dot rdpsx-p-ssh'></span>{$c["SSH"]} SSH</span>"
        ."<span class='rdpsx-lg'><span class='rdpsx-dot rdpsx-p-web'></span>{$c["WEB"]} WebView</span>";
    $art="<span class='rdpsx-kpi-ico'><i class='fas fa-plug'></i></span>";
    if(count($list)>0){
        $art="<span class='rdpsx-g-proto' role='img' aria-label=\"{$c["RDP"]} RDP, {$c["VNC"]} VNC, {$c["SSH"]} SSH, {$c["WEB"]} WebView\">{$c["RDP"]},{$c["VNC"]},{$c["SSH"]},{$c["WEB"]}</span>";
    }
    $n=0; foreach($c as $v){ if($v>0){$n++;} }
    return rdpsx_kpi("{rdpsx_protocols}",(string)$n,$sub,$art);
}

/** Duree : la plus longue en grand, la mediane, une barre par session (triees). */
function rdpsx_kpi_duration(array $list,$now):string{
    $d=array();
    foreach($list as $l){ $c=intval($l["created"] ?? 0); if($c>0){$d[]=max(0,$now-$c);} }
    if(count($d)==0){
        return rdpsx_kpi("{rdpsx_duration}","<span class='rdpsx-dim'>&mdash;</span>","","<span class='rdpsx-kpi-ico'><i class='far fa-clock'></i></span>");
    }
    sort($d);
    $n=count($d);
    $med=$n%2?$d[intdiv($n,2)]:intdiv($d[$n/2-1]+$d[$n/2],2);
    $bars=array_slice($d,-30);
    $vals=array(); foreach($bars as $v){ $vals[]=max(1,intdiv($v,60)); }
    $w=max(40,min(120,count($vals)*8));
    $art="<span class='rdpsx-g-dur' data-w='$w' role='img' aria-label=\"{rdpsx_longest} ".rdpsx_dur(end($d))."\">".implode(",",$vals)."</span>";
    return rdpsx_kpi("{rdpsx_duration}","<span class='rdpsx-kpi-dur'>".rdpsx_dur(end($d))."</span>","{rdpsx_median} ".rdpsx_dur($med),$art);
}

function rdpsx_kpi_watch(array $list):string{
    $unk=0;$cls=0;
    foreach($list as $l){ $s=rdpsx_state($l); if($s=="unknown"){$unk++;} if($s=="closing"){$cls++;} }
    if($unk+$cls==0){
        return rdpsx_kpi("{rdpsx_watch}","0","<span class='rdpsx-good'><i class='fas fa-circle-check'></i> {rdpsx_all_active}</span>",
            "<span class='rdpsx-kpi-ico rdpsx-ico-ok'><i class='fas fa-heart-pulse'></i></span>");
    }
    return rdpsx_kpi("{rdpsx_watch}","<span class='rdpsx-warn-v'>".($unk+$cls)."</span>",
        "<span class='rdpsx-warn'>$unk {rdpsx_st_unknown} &middot; $cls {rdpsx_st_closing}</span>",
        "<span class='rdpsx-kpi-ico rdpsx-ico-warn'><i class='fas fa-triangle-exclamation'></i></span>","rdpsx-kpi-warn");
}

/** Cibles les plus utilisees : 5 puces avec barre, clic = fiche de la cible. */
function rdpsx_top_targets(array $list):string{
    $t=array();
    foreach($list as $l){
        $id=intval($l["target_id"] ?? 0);
        $alias=(string)($l["target_alias"] ?? "");
        if($alias==""){$alias=(string)($l["target_host"] ?? "#$id");}
        $k="$id:$alias";
        if(!isset($t[$k])){$t[$k]=array("id"=>$id,"alias"=>$alias,"n"=>0,"proto"=>rdps_proto($l));}
        $t[$k]["n"]++;
    }
    uasort($t,function($a,$b){ return $b["n"]-$a["n"]; });
    $t=array_slice($t,0,5);
    $max=1; foreach($t as $x){ $max=max($max,$x["n"]); }
    $h="<section class='rdpsx-card rdpsx-top' aria-label=\"{rdpsx_top_targets}\"><span class='rdpsx-top-t'><i class='fas fa-server'></i> {rdpsx_top_targets}</span><div class='rdpsx-chips'>";
    foreach($t as $x){
        $pct=round($x["n"]/$max*100);
        $al=htmlspecialchars($x["alias"],ENT_QUOTES);
        $h.="<span class='rdpsx-chip rdpsx-chip-ro'><i class='".rdps_proto_icon($x["proto"])." rdpsx-pc-".strtolower($x["proto"])."' aria-hidden='true'></i>"
            ."<span class='rdpsx-mono'>$al</span><span class='rdpsx-chip-bar' aria-hidden='true'><span style='width:$pct%'></span></span>"
            ."<span class='rdpsx-chip-n'>{$x["n"]} {rdpsx_sessions_n}</span></span>";
    }
    return $h."</div></section>";
}

/** Barre d'outils + tableau + pied. */
function rdpsx_table(array $list,$now):string{
    $sc=array("connected"=>0,"closing"=>0,"unknown"=>0);
    $pc=array("RDP"=>0,"VNC"=>0,"SSH"=>0,"WEB"=>0);
    $max=1;
    foreach($list as $l){
        $sc[rdpsx_state($l)]++; $pc[rdps_proto($l)]++;
        $max=max($max,$now-intval($l["created"] ?? $now));
    }
    $h="<div class='rdpsx-card rdpsx-tbl-card'><div class='rdpsx-tools'>";
    $h.="<label class='rdpsx-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='rdpsx-q' placeholder=\"{rdpsx_search_ph}\" aria-label=\"{rdpsx_search_ph}\" oninput=\"RdpsxApply(true)\"></label>";
    $h.="<div class='rdpsx-filters'><div class='rdpsx-seg' role='group' aria-label=\"{status}\">";
    $h.="<button type='button' class='rdpsx-f' data-k='st' data-v=''>{all} (".count($list).")</button>";
    foreach(array("connected"=>"{rdpsx_st_connected}","closing"=>"{rdpsx_st_closing}","unknown"=>"{rdpsx_st_unknown}") as $k=>$lbl){
        $h.="<button type='button' class='rdpsx-f' data-k='st' data-v='$k'>$lbl ({$sc[$k]})</button>";
    }
    $h.="</div>";
    // filtres par protocole : seulement ceux qui ont des sessions (aucun groupe vide quand la liste l'est)
    if(array_sum($pc)>0){
        $h.="<div class='rdpsx-seg rdpsx-seg-p' role='group' aria-label=\"{rdpsx_protocols}\">";
        foreach($pc as $p=>$n){
            if($n==0){continue;}
            $lbl=$p=="WEB"?"WebView":$p;
            $h.="<button type='button' class='rdpsx-f rdpsx-fp' data-k='pr' data-v='$p'><span class='rdpsx-dot rdpsx-p-".strtolower($p)."'></span>$lbl ($n)</button>";
        }
        $h.="</div>";
    }
    $h.="</div></div>";

    $h.="<div class='rdpsx-scroll'><table class='rdpsx-tbl'><thead><tr>"
        ."<th>{rdpsx_col_user}</th><th>{rdpsx_col_client}</th><th>{rdpsx_col_target}</th><th>{rdpsx_col_rule}</th>"
        ."<th>{rdpsx_col_duration}</th><th>{rdpsx_col_activity}</th><th>{status}</th><th class='rdpsx-r'>&nbsp;</th></tr></thead><tbody id='rdpsx-rows'>";
    if(count($list)==0){
        $h.="<tr><td colspan='8'><div class='rdpsx-empty'><i class='fas fa-desktop'></i><b>{rdpsx_no_session}</b><span>{rdpsx_no_session_sub}</span></div></td></tr>";
    }
    foreach($list as $l){ $h.=session_row($l,$now,$max); }
    $h.="<tr class='rdpsx-nomatch' style='display:none'><td colspan='8'>{rdpsx_no_match}</td></tr>";
    $h.="</tbody></table></div>";
    $h.="<div class='rdpsx-foot'><span><b id='rdpsx-n'>".count($list)."</b> {rdpsx_sessions_n}</span>"
        ."<span class='rdpsx-note'><i class='far fa-clock'></i> {rdpproxy_disconnect_delay}</span>"
        ."<span class='rdpsx-live'><span class='rdpsx-live-dot' aria-hidden='true'></span>{rdpsx_updated_at} ".date("H:i:s")." &middot; {rdpsx_auto}</span>"
        ."<span class='rdpsx-pg' id='rdpsx-pg'></span></div></div>";
    return $h;
}

/** Une ligne par session : qui, d'ou, vers quoi, par quelle regle, depuis quand. */
function session_row($ligne,$now=0,$max=1):string{
    $page=CurrentPageName();
    if($now==0){$now=time();}
    $ID=intval($ligne["ID"] ?? 0);
    $created=intval($ligne["created"] ?? 0);
    $xtime=intval($ligne["xtime"] ?? 0);
    $ruleid=intval($ligne["rule_id"] ?? 0);
    $targetid=intval($ligne["target_id"] ?? 0);
    $userid=intval($ligne["userid"] ?? 0);
    $psid=intval($ligne["psid"] ?? 0);
    $st=rdpsx_state($ligne);
    $proto=rdps_proto($ligne);
    $e=function($v){ return htmlspecialchars((string)$v,ENT_QUOTES); };

    $user=$e($ligne["username"] ?? "");
    // Lecture seule (RWE 14/11) : la page des sessions n'ouvre plus la fiche du membre ni celle de la cible.
    $userH="<span class='rdpsx-user'>$user</span>";
    // Membre de la règle (groupe AD) quand il diffère de l'utilisateur connecté (RWE 2026-11-02) :
    // le nom saisi reste affiché, le groupe vient dessous ; pour un DN, seul le CN est montré.
    $member=(string)($ligne["member"] ?? "");
    if($member!=="" && strcasecmp($member,(string)($ligne["username"] ?? ""))!=0){
        $mshow=preg_match('/^\s*CN=([^,]+)/i',$member,$mm)?$mm[1]:$member;
        $userH.="<div class='rdpsx-it'>{rdpproxy_session_via} ".$e($mshow)."</div>";
    }
    $login=$e($ligne["target_login"] ?? "");
    $loginH=$login!==""?"{rdpsx_target_account} : <span class='rdpsx-mono'>$login</span>"
        :($proto=="SSH"?"<span class='rdpsx-it'>{rdpproxy_session_login_prompt}</span>":"");

    $ip=$e($ligne["ip_client"] ?? "");
    $alias=$e($ligne["target_alias"] ?? "");
    $host=$e($ligne["target_host"] ?? "");
    $dev=$e($ligne["target_device"] ?? "");
    $port=intval($ligne["target_port"] ?? 0);
    $where="$host:$port";
    if($dev!=="" && $dev!==$host){$where.=", $dev:$port";}
    if($alias===""){$alias=$host;}
    $rule=$e($ligne["rule_name"] ?? "");

    $dur=$created>0?max(0,$now-$created):0;
    $pct=max(2,round($dur/max(1,$max)*100));
    $start=$created>0?(date("Y-m-d",$created)==date("Y-m-d",$now)?date("H:i",$created):date("Y-m-d H:i",$created)):"";
    $age=$xtime>0?max(0,$now-$xtime):-1;
    $fresh=$age<0?"off":($age>=300?"bad":($age>=120?"warn":"ok"));
    if($st=="unknown"){$fresh="bad";}

    $states=array(
        "connected"=>"<span class='rdpsx-st rdpsx-st-ok'><span></span>{rdpsx_st_connected}</span>",
        "closing"=>"<span class='rdpsx-st rdpsx-st-cls' title=\"{rdpproxy_disconnect_delay}\"><i class='far fa-hourglass-half'></i> {rdpsx_st_closing}</span>",
        "unknown"=>"<span class='rdpsx-st rdpsx-st-unk' title=\"{rdpsx_unknown_tip}\"><i class='fas fa-triangle-exclamation'></i> {rdpsx_st_unknown}</span>",
    );
    $act="";
    if($st=="closing"){
        $act="<span class='rdpsx-closing'><i class='fas fa-circle-notch fa-spin' aria-hidden='true'></i> {rdpsx_closing_wait}</span>";
    }elseif(rdps_allowed()){
        $act="<button type='button' class='rdpsx-disc' onclick=\"Loadjs('$page?disconnect=$ID')\" aria-label=\"{rdpsx_disconnect} : $user &rarr; $alias\">"
            ."<i class='fas fa-link-slash' aria-hidden='true'></i> {rdpsx_disconnect}</button>";
    }

    $pl=strtolower($proto);
    $h="<tr data-st='$st' data-pr='$proto' class='rdpsx-row-$st'>";
    $h.="<td><div class='rdpsx-c2'>$userH<span class='rdpsx-sub'>$loginH</span></div></td>";
    $h.="<td><div class='rdpsx-c2'><span class='rdpsx-mono rdpsx-strong'>$ip</span><span class='rdpsx-sub rdpsx-mono'>PSID $psid</span></div></td>";
    $h.="<td><div class='rdpsx-tgt'><span class='rdpsx-pico rdpsx-pico-$pl' title='$proto'><i class='".rdps_proto_icon($proto)."' aria-hidden='true'></i><span class='sr-only'>$proto</span></span>"
        ."<div class='rdpsx-c2'><span class='rdpsx-strong'>$alias</span>"
        ."<span class='rdpsx-sub rdpsx-mono'>$where</span></div></div></td>";
    $h.="<td>".($rule!==""?"<a href=\"javascript:blur();\" class='rdpsx-rule' onclick=\"Loadjs('fw.rdpproxy.connections.php?group-js=$ruleid')\">$rule</a>":"<span class='rdpsx-dim'>&mdash;</span>")."</td>";
    $h.="<td><div class='rdpsx-c2'><span class='rdpsx-strong'>".rdpsx_dur($dur)."</span>"
        ."<span class='rdpsx-sub'>{rdpsx_since} $start <span class='rdpsx-dbar' aria-hidden='true'><span style='width:$pct%'></span></span></span></div></td>";
    $h.="<td class='rdpsx-nw'><span class='rdpsx-fresh rdpsx-fresh-$fresh'".($fresh=="bad"?" title=\"{rdpsx_unknown_tip}\"":"")."><span></span>".($age<0?"&mdash;":rdpsx_dur($age))."</span></td>";
    $h.="<td>{$states[$st]}</td>";
    $h.="<td class='rdpsx-r'>$act</td></tr>";
    return $h;
}

function rdpsx_js():string{
    return "<script>
function RdpsxApply(typed){
  var tb=document.getElementById('rdpsx-rows'); if(!tb){return;}
  var inp=document.getElementById('rdpsx-q');
  if(typed){ window.RdpsxQ=inp.value; window.RdpsxPage=1; } else if(inp && inp.value!==window.RdpsxQ){ inp.value=window.RdpsxQ||''; }
  var q=(window.RdpsxQ||'').toLowerCase().replace(/^\s+|\s+$/g,''), st=window.RdpsxSt||'', pr=window.RdpsxPr||'';
  var hits=[], nomatch=null, size=25, all=0;
  for(var i=0;i<tb.rows.length;i++){
    var tr=tb.rows[i];
    if(tr.className.indexOf('rdpsx-nomatch')>=0){nomatch=tr;continue;}
    if(!tr.hasAttribute('data-st')){continue;}
    all++;
    var ok=(st===''||tr.getAttribute('data-st')===st)&&(pr===''||tr.getAttribute('data-pr')===pr)&&(q===''||(tr.textContent||'').toLowerCase().indexOf(q)>=0);
    tr.style.display='none'; if(ok){hits.push(tr);}
  }
  var pages=Math.max(1,Math.ceil(hits.length/size)), cur=Math.min(window.RdpsxPage||1,pages); window.RdpsxPage=cur;
  for(var j=0;j<hits.length;j++){ if(j>=(cur-1)*size&&j<cur*size){hits[j].style.display='';} }
  if(nomatch){nomatch.style.display=(all>0&&hits.length===0)?'':'none';}
  var n=document.getElementById('rdpsx-n'); if(n){n.textContent=hits.length;}
  var f=document.querySelectorAll('.rdpsx-f');
  for(var k=0;k<f.length;k++){
    var on=(f[k].getAttribute('data-k')==='st'?st:pr)===f[k].getAttribute('data-v');
    f[k].className=f[k].className.replace(/ rdpsx-f-on/g,'')+(on?' rdpsx-f-on':''); f[k].setAttribute('aria-pressed',on?'true':'false');
  }
  var pg=document.getElementById('rdpsx-pg'), h='';
  if(pages>1){
    h+='<button type=\"button\" '+(cur==1?'disabled':'')+' onclick=\"window.RdpsxPage='+(cur-1)+';RdpsxApply()\">&lsaquo;</button><span>'+cur+' / '+pages+'</span>';
    h+='<button type=\"button\" '+(cur==pages?'disabled':'')+' onclick=\"window.RdpsxPage='+(cur+1)+';RdpsxApply()\">&rsaquo;</button>';
  }
  if(pg){pg.innerHTML=h;}
}
(function(){
  var f=document.querySelectorAll('.rdpsx-f');
  for(var i=0;i<f.length;i++){ f[i].onclick=function(){
    var k=this.getAttribute('data-k'), v=this.getAttribute('data-v');
    if(k==='st'){window.RdpsxSt=v;}else{window.RdpsxPr=(window.RdpsxPr===v)?'':v;}
    window.RdpsxPage=1; RdpsxApply();
  }; }
  RdpsxApply();
  if(typeof jQuery==='undefined'){return;}
  var run=function(){
    jQuery('.rdpsx-g-proto').peity('donut',{radius:22,innerRadius:15,fill:['#1d4ed8','#005447','#334155','#0f766e']});
    jQuery('.rdpsx-g-dur').each(function(){ jQuery(this).peity('bar',{height:38,width:parseInt(this.getAttribute('data-w')||'80',10),padding:0.25,fill:['#26a69a']}); });
  };
  if(jQuery.fn.peity){run();}else{ var sc=document.createElement('script'); sc.src='js/jquery.peity.min.js'; sc.onload=run; document.head.appendChild(sc); }
})();
NoSpinner();
</script>";
}

function rdpsx_style():string{
    if(isset($GLOBALS["RDPSX_STYLE"])){return "";}
    $GLOBALS["RDPSX_STYLE"]=true;
    return "<style>
.rdpsx{display:flex;flex-direction:column;gap:18px;margin:12px 0 20px;color:#111d23}
.rdpsx-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.rdpsx-mono{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.rdpsx-dim{color:#64748b}.rdpsx-it{font-style:italic}.rdpsx-strong{font-weight:700;color:#111d23}
.rdpsx-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px}
.rdpsx-kpi{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px}
.rdpsx-kpi-b{display:flex;flex-direction:column;gap:5px;min-width:0}
.rdpsx-kpi-l{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#3d4947}
.rdpsx-kpi-v{font-size:30px;font-weight:700;line-height:1;font-variant-numeric:tabular-nums}
.rdpsx-kpi-dur{font-size:24px;white-space:nowrap}
.rdpsx-kpi-s{font-size:12px;color:#3d4947;display:flex;flex-wrap:wrap;gap:4px 10px}
.rdpsx-kpi-ico{width:44px;height:44px;border-radius:12px;background:#f1f5f9;color:#475569;display:inline-flex;align-items:center;justify-content:center;font-size:18px}
.rdpsx-ico-ok{background:#e6f4f2;color:#006a62}
.rdpsx-ico-warn{background:#fef3c7;color:#92400e}
.rdpsx-kpi-warn{background:#fffbeb;border-color:#fcd34d}
.rdpsx-good{color:#065f46;font-weight:600}.rdpsx-warn{color:#92400e;font-weight:600}.rdpsx-warn-v{color:#92400e}
.rdpsx-lg{display:inline-flex;align-items:center;gap:5px;white-space:nowrap}
.rdpsx-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.rdpsx-p-rdp{background:#1d4ed8}.rdpsx-p-vnc{background:#005447}.rdpsx-p-ssh{background:#334155}.rdpsx-p-web{background:#0f766e}
.rdpsx-top{display:flex;align-items:center;gap:14px;padding:12px 18px;flex-wrap:wrap}
.rdpsx-top-t{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#3d4947;white-space:nowrap}
.rdpsx-top-t i{color:#006a62;margin-right:4px}
.rdpsx-chips{display:flex;gap:8px;flex-wrap:wrap}
.rdpsx-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;font-size:13px;color:#111d23;cursor:pointer}
.rdpsx-chip:hover{border-color:#26a69a;background:#fff}
.rdpsx-chip-ro,.rdpsx-chip-ro:hover{cursor:default;border-color:#e2e8f0;background:#f8fafc}
.rdpsx-chip .rdpsx-mono{font-weight:700}
.rdpsx-chip-bar{width:60px;height:5px;border-radius:999px;background:#e2e8f0;overflow:hidden}
.rdpsx-chip-bar span{display:block;height:100%;background:#006a62;border-radius:999px}
.rdpsx-chip-n{font:12px ui-monospace,Menlo,Consolas,monospace;color:#3d4947;white-space:nowrap}
.rdpsx-pc-rdp{color:#1d4ed8}.rdpsx-pc-vnc{color:#005447}.rdpsx-pc-ssh{color:#334155}.rdpsx-pc-web{color:#0f766e}
.rdpsx-tbl-card{overflow:hidden}
.rdpsx-tools{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}
.rdpsx-search{position:relative;flex:0 1 400px;margin:0;font-weight:400}
.rdpsx-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;font-size:13px}
.rdpsx-search input{width:100%;height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px 0 34px;font-size:13px;color:#111d23;background:#fff}
.rdpsx-search input:focus{outline:2px solid #006a62;outline-offset:1px;border-color:#006a62}
.rdpsx-filters{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.rdpsx-seg{display:inline-flex;gap:4px;padding:4px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0}
.rdpsx-f{border:0;background:transparent;border-radius:7px;padding:5px 11px;font-size:12px;font-weight:600;color:#334155;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.rdpsx-f-on{background:#00504a;color:#fff}
.rdpsx-seg-p .rdpsx-f-on{background:#fff;color:#111d23;box-shadow:0 1px 2px rgba(15,23,42,.15);outline:1px solid #94a3b8}
.rdpsx-f:focus-visible,.rdpsx-chip:focus-visible,.rdpsx-disc:focus-visible,.rdpsx-tbl a:focus-visible,.rdpsx-pg button:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.rdpsx-scroll{overflow-x:auto}
.rdpsx-tbl{width:100%;border-collapse:collapse;font-size:13px;color:#111d23}
.rdpsx-tbl thead th{position:sticky;top:0;background:#f8fafc;color:#3d4947;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-align:left;padding:11px 14px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.rdpsx-tbl td{padding:11px 14px;border-top:1px solid #f1f5f9;vertical-align:middle;color:#111d23}
.rdpsx-tbl tbody tr:first-child td{border-top:0}
.rdpsx-tbl tbody tr:hover td{background:#f8fafc}
.rdpsx-row-unknown td{background:#fffbeb}
.rdpsx-row-closing td{color:#64748b}
.rdpsx-r{text-align:right}.rdpsx-nw{white-space:nowrap}
.rdpsx-c2{display:flex;flex-direction:column;gap:2px;min-width:0}
.rdpsx-user{font-size:14px;font-weight:700;color:#00504a}
.rdpsx-sub{font-size:12px;color:#3d4947}
.rdpsx-tgt{display:flex;align-items:center;gap:10px}
.rdpsx-pico{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;flex:0 0 32px}
.rdpsx-pico-rdp{background:#e0ecff;color:#1d4ed8}.rdpsx-pico-vnc{background:#f1e8ff;color:#005447}.rdpsx-pico-ssh{background:#eef2f6;color:#334155}.rdpsx-pico-web{background:#e3f4f1;color:#0f766e}
.rdpsx-tbl a{color:#00504a}
.rdpsx-rule{display:inline-block;padding:3px 10px;border-radius:999px;background:#eef2f6;color:#111d23!important;font-size:12px;font-weight:600}
.rdpsx-dbar{display:inline-block;vertical-align:middle;width:60px;height:5px;margin-left:6px;border-radius:999px;background:#e2e8f0;overflow:hidden}
.rdpsx-dbar span{display:block;height:100%;background:#26a69a;border-radius:999px}
.rdpsx-fresh{display:inline-flex;align-items:center;gap:6px;font:12px ui-monospace,Menlo,Consolas,monospace}
.rdpsx-fresh>span{width:8px;height:8px;border-radius:50%;display:inline-block}
.rdpsx-fresh-ok>span{background:#26a69a}.rdpsx-fresh-warn>span{background:#d97706}.rdpsx-fresh-bad>span{background:#b91c1c}.rdpsx-fresh-off>span{background:#94a3b8}
.rdpsx-fresh-warn{color:#92400e}.rdpsx-fresh-bad{color:#991b1b;font-weight:700}
.rdpsx-st{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap}
.rdpsx-st>span{width:7px;height:7px;border-radius:50%;background:currentColor}
.rdpsx-st-ok{background:#e6f4f2;color:#00695f}
.rdpsx-st-cls{background:#eef2f6;color:#334155}
.rdpsx-st-unk{background:#fef3c7;color:#78350f}
.rdpsx-disc{display:inline-flex;align-items:center;gap:6px;border:1px solid #f1a6a6;background:#fff;color:#b91c1c;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap}
.rdpsx-disc:hover{background:#fef2f2;border-color:#b91c1c}
.rdpsx-closing{font-size:12px;color:#475569;white-space:nowrap}
.rdpsx-nomatch td{text-align:center;color:#3d4947;padding:22px}
.rdpsx-empty{display:flex;flex-direction:column;align-items:center;gap:6px;padding:30px 20px;color:#3d4947;font-size:13px;text-align:center}
.rdpsx-empty>i{font-size:28px;color:#94a3b8}
.rdpsx-empty b{font-size:15px;color:#111d23}
.rdpsx-foot{display:flex;align-items:center;gap:18px;padding:12px 18px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:13px;flex-wrap:wrap}
.rdpsx-note{color:#3d4947}
.rdpsx-live{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#3d4947}
.rdpsx-live-dot{width:8px;height:8px;border-radius:50%;background:#26a69a;animation:rdpsxPulse 2s ease-in-out infinite}
.rdpsx-pg{margin-left:auto;display:flex;align-items:center;gap:8px}
.rdpsx-pg button{width:30px;height:30px;border:1px solid #cbd5e1;background:#fff;border-radius:6px;cursor:pointer;font-size:16px}
.rdpsx-pg button[disabled]{opacity:.45;cursor:default}
@keyframes rdpsxPulse{0%,100%{opacity:1}50%{opacity:.35}}
@media (prefers-reduced-motion:reduce){.rdpsx-live-dot{animation:none}}
</style>";
}

