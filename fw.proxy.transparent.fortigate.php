<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["audit-js"])){audit_js();exit;}
if(isset($_GET["audit-popup"])){audit_popup();exit;}
if(isset($_GET["parameters-flat"])){parameters_flat();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["mode-js"])){mode_js();exit;}
if(isset($_POST["fgw-mode-set"])){mode_set();exit;}


page();

// fw.proxy.transparent.fortigate.php / fw.fortigate.tproxy.php
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $title="Fortigate &raquo; {APP_SQUID}";
    $addon="";
    if(isset($_GET["fortiget"])){
        $addon="&fortiget=yes";
    }
    $html=$tpl->page_header($title,ico_dashboard,"{fortigate_use_tproxy}","$page?tabs=yes$addon",
        "fortiports",
        "progress-fortiports-restart",false,
        "table-fortiports"
    );

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Fortigate > {APP_SQUID}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $addon="";
    if(isset($_GET["fortiget"])){
        $addon="&fortiget=yes";
    }
    $array["{status}"]="$page?start=yes$addon";
    $st=fgt_status();
    if($st["mode"]=="wccp"){
        // Mode WCCP : pas de compteurs iptables TPROXY, mais l'assistant et les regles.
        $wccp="fw.proxy.transparent.fortigate.wccp.php";
        $array["{fgw_tab_wizard}"]="$wccp?wizard=yes";
        $array["{fgw_tab_rules}"]="$wccp?rules=yes";
    }
    // Les compteurs viennent des regles TPROXY de l'interface d'ecoute : le trafic que le
    // FortiGate redirige en WCCP L2 y passe aussi, l'onglet vaut donc pour les deux modes.
    $array["{statistics}"]="fw.fortigate.tproxy.php?start=yes$addon";


    echo $tpl->tabs_default($array);
    return true;
}
function Start():bool{

        $page=CurrentPageName();
        $tpl=new template_admin();
        $html[]="<table style='width:100%;margin-top:10px'>";
        $html[]="<tr>";
        $html[]="<td style='width:240px;vertical-align: top'><div id='tproxy-status'></div></td>";
        $html[]="<td style='width:99%;vertical-align: top;padding-left:10px'><div id='tproxy-params'></div></td>";
        $html[]="</tr>";
        $html[]="<script>";
        $js=$tpl->RefreshInterval_js("tproxy-status",$page,"status=yes");

        $html[]="$js";
        // Le panneau de droite n'etait charge QU'UNE fois, alors qu'il affiche
        // exactement les memes faits vivants que le widget de gauche : etat de la
        // fonctionnalite, ports qui repondent, liaison rapportee par le daemon.
        // Seul le callback d'installation le rafraichissait, si bien qu'une
        // activation faite ailleurs (menu, autre page, CLI) laissait les deux
        // moities de l'ecran se contredire indefiniment -- capture du 2026-09-15
        // a 17:10 : le panneau disait INACTIF pendant que le widget decrivait une
        // liaison active mais incomplete, activee sept minutes plus tot.
        // Cadence volontairement plus lente que celle du widget : les appels REST
        // (status + audit) sont refaits a chaque tick, et l'etat ne bouge que sur
        // action d'un exploitant.
        // Le panneau ne rejoue le TINY que si sa signature change : on la remet a
        // zero ici pour que le premier rendu de l'onglet le rejoue toujours.
        $html[]="window.FgtpTinySig='';";
        $html[]=$tpl->RefreshInterval_js("tproxy-params",$page,"parameters-flat=yes&refresh=yes",10);
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
}


/**
 * Etat de la liaison Fortigate tel que le daemon le rapporte.
 * Meme source que la page complete fw.fortigate.tproxy.php, pour que les deux ne
 * puissent pas diverger.
 */
function fgt_status():array{
    $out=array("reached"=>false,"enabled"=>false,"interface"=>"","source_ip"=>"",
               "mode"=>"transparent","router"=>"","https_service"=>0,"weight"=>0,"password_set"=>false,
               "squid_patched"=>true,"wccp_log_state"=>"unknown","legacy_wccp_conflict"=>false,
               "iface_mismatch"=>false,"meta_managed"=>false);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/status"));
    if(!is_object($json)){return $out;}
    if(empty($json->success)){return $out;}
    if(!is_object($json->data)){return $out;}
    $d=$json->data;
    $out["reached"]=true;
    $out["enabled"]=!empty($d->enabled);
    $out["interface"]=strval($d->interface ?? "");
    $out["source_ip"]=strval($d->source_ip ?? "");
    // Champs du mode WCCP (absents tant que le daemon ne les rapporte pas : mode transparent).
    $mode=strtolower(strval($d->mode ?? ""));
    $out["mode"]=($mode=="wccp")?"wccp":"transparent";
    $out["router"]=strval($d->router ?? "");
    $out["https_service"]=intval($d->https_service ?? 0);
    $out["weight"]=intval($d->weight ?? 0);
    $out["password_set"]=!empty($d->password_set);
    $out["squid_patched"]=!isset($d->squid_patched) || !empty($d->squid_patched);
    $log=strval($d->wccp_log_state ?? "unknown");
    $out["wccp_log_state"]=in_array($log,array("accepting","unpatched","unknown"),true)?$log:"unknown";
    $out["legacy_wccp_conflict"]=!empty($d->legacy_wccp_conflict);
    $out["iface_mismatch"]=!empty($d->iface_mismatch);
    $out["meta_managed"]=!empty($d->meta_managed);
    return $out;
}

/* ------------------------------------------------------------------ */
/* Panneau de droite de l'onglet Etat : un modele, un widget par zone   */
/* ------------------------------------------------------------------ */

/**
 * Lit /tproxyforti/status et /tproxyforti/audit UNE fois par rendu (plus
 * /tproxyforti/wccp/settings, seulement sous verrou Meta, pour le numero de politique).
 * Une route illisible laisse sa zone en gris avec un tiret, jamais un faux vert.
 * enabled vaut null quand aucune des deux routes ne permet de le savoir.
 */
function fgtp_model():array{
    $st=fgt_status();
    list($a,$aerr)=fgt_audit_read();
    $m=array("st"=>$st,"audit_ok"=>($a!==null),"audit_err"=>$aerr,"checks"=>array(),
        "enabled"=>null,"mode"=>"","meta_id"=>0);
    if($a!==null && isset($a->checks) && is_array($a->checks)){
        foreach($a->checks as $c){
            if(!is_object($c)){continue;}
            $id=strval($c->id ?? "");
            if($id===""){continue;}
            $state=strval($c->state ?? "");
            if(!in_array($state,array("ok","warn","fail","skip"),true)){ $state="skip"; }
            $m["checks"][$id]=array("state"=>$state,"detail"=>strval($c->detail ?? ""));
        }
    }
    if($st["reached"]){
        $m["enabled"]=$st["enabled"];
        $m["mode"]=$st["mode"];
    }elseif($a!==null){
        $m["enabled"]=!empty($a->enabled);
        $m["mode"]=(strtolower(strval($a->mode ?? ""))=="wccp")?"wccp":"transparent";
    }
    if($st["reached"] && $st["meta_managed"]){
        $j=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/wccp/settings")));
        if(is_object($j) && !empty($j->success) && isset($j->data) && is_object($j->data)){
            $m["meta_id"]=intval($j->data->meta_managed ?? 0);
        }
    }
    return $m;
}

/** Valeur technique en monospace, ou un tiret si absente. */
function fgtp_val($v):string{
    $v=trim(strval($v));
    if($v===""){ return "<span class='ak-muted'>&mdash;</span>"; }
    return "<span class='ak-mono fgtp-v'>".htmlspecialchars($v)."</span>";
}

/** Bandeau mode actif (lecture seule) et cible : interface, IP source, FortiGate en WCCP. */
function fgtp_mode_strip($m):string{
    $st=$m["st"];
    $modes=array("transparent"=>array("fa-route","{fgtp_mode_transparent}"),"wccp"=>array("fa-tower-broadcast","{fgtp_mode_wccp}"));
    $h=array();
    $h[]="<div class='ak-card fgtp-strip'>";
    $h[]="<div class='fgtp-modes' role='group' aria-label='{fgtp_mode}'>";
    foreach($modes as $k=>$x){
        $on=($m["mode"]==$k);
        $h[]="<span class='fgtp-mode".($on?" on":"")."' data-mode='$k'".($on?" aria-current='true'":"").">"
            ."<i class='fas {$x[0]}' aria-hidden='true'></i>{$x[1]}"
            .($on?"<i class='fas fa-check fgtp-mode-ck' aria-hidden='true'></i>":"")."</span>";
    }
    $h[]="</div>";
    $iface="";
    if($st["reached"]){
        $iface=fgtp_val($st["interface"]);
        if(trim($st["source_ip"])!==""){ $iface.="<span class='ak-muted'>&middot;</span>".fgtp_val($st["source_ip"]); }
    }else{
        $iface=fgtp_val("");
    }
    $h[]="<div class='fgtp-target'>";
    $h[]="<span class='fgtp-kv' data-k='interface'><span class='ak-kicker'><i class='fas fa-network-wired' aria-hidden='true'></i>{fgtp_interface}</span>$iface</span>";
    if($m["mode"]=="wccp"){
        $h[]="<span class='fgtp-kv' data-k='router'><span class='ak-kicker'><i class='fas fa-shield-halved' aria-hidden='true'></i>{fgtp_fortigate}</span>".fgtp_val($st["reached"]?$st["router"]:"")."</span>";
    }
    $h[]="</div></div>";
    return implode("",$h);
}

/** Pile d'alertes vraies, par ordre de gravite. */
function fgtp_alerts($m,$tpl):string{
    $st=$m["st"];
    $w=array();
    $line=function($tone,$ico,$key,$msg,$side=""){
        return "<div class='ak-banner $tone fgtp-alert' data-a='$key' role='status'><i class='fas $ico' aria-hidden='true'></i><span class='ak-banner-m'>$msg</span>$side</div>";
    };
    if($st["reached"]){
        if($m["mode"]=="wccp" && !$st["squid_patched"]){ $w[]=$line("ak-err","fa-circle-xmark","unpatched","{fgw_banner_unpatched}"); }
        if($st["legacy_wccp_conflict"]){ $w[]=$line("ak-err","fa-triangle-exclamation","legacy","{fgw_banner_legacy}"); }
        if($st["iface_mismatch"]){ $w[]=$line("ak-warn","fa-circle-exclamation","iface","{fgw_banner_iface}"); }
        if($st["meta_managed"]){
            $msg="{fgw_banner_meta}";
            if($m["meta_id"]>0){ $msg=str_replace("%s",strval($m["meta_id"]),$tpl->_ENGINE_parse_body("{fgww_meta_banner}")); }
            $w[]=$line("ak-info","fa-sitemap","meta",$msg,"<span class='ak-pill ak-info'><i class='fas fa-lock' aria-hidden='true'></i>{fgtp_meta_lock}</span>");
        }
    }else{
        $w[]=$line("ak-off","fa-circle-question","status","{fortigate_status_unavailable}");
    }
    if(!$m["audit_ok"]){
        $w[]=$line("ak-off","fa-circle-question","audit","{fgaud_unavailable}<span class='fgtp-err'>".htmlspecialchars($m["audit_err"])."</span>");
    }
    if(count($w)==0){ return ""; }
    return "<div class='fgtp-alerts'>".implode("",$w)."</div>";
}

/** Pastille de compte d'une carte, sur les controles non ignores. */
function fgtp_count($m,$ids,$tpl):string{
    $ok=0;$warn=0;$fail=0;$n=0;
    foreach($ids as $id){
        if(!isset($m["checks"][$id])){continue;}
        $s=$m["checks"][$id]["state"];
        if($s=="skip"){continue;}
        $n++;
        if($s=="ok"){$ok++;}elseif($s=="warn"){$warn++;}else{$fail++;}
    }
    if($n==0){ return "<span class='ak-pill ak-off fgtp-count' data-c='none'>&mdash;</span>"; }
    if($fail>0){
        $t=str_replace("%s",strval($fail),$tpl->_ENGINE_parse_body("{fgtp_n_failed}"));
        return "<span class='ak-pill ak-err fgtp-count' data-c='fail'><i class='fas fa-circle-xmark' aria-hidden='true'></i>$t</span>";
    }
    if($warn>0){
        $t=str_replace("%s",strval($warn),$tpl->_ENGINE_parse_body($warn==1?"{fgtp_n_warning}":"{fgtp_n_warnings}"));
        return "<span class='ak-pill ak-warn fgtp-count' data-c='warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>$t</span>";
    }
    return "<span class='ak-pill ak-ok fgtp-count' data-c='ok'><i class='fas fa-check' aria-hidden='true'></i>$ok / $n OK</span>";
}

/** Une ligne de controle : icone + mot d'etat + libelle + detail reel du daemon. */
function fgtp_check_row($m,$id):string{
    $icons=array("ok"=>"fa-circle-check","warn"=>"fa-triangle-exclamation","fail"=>"fa-circle-xmark","skip"=>"fa-circle-minus");
    $tones=array("ok"=>"ak-ok","warn"=>"ak-warn","fail"=>"ak-err","skip"=>"ak-off");
    $words=array("ok"=>"{fgaud_s_ok}","warn"=>"{fgaud_s_warn}","fail"=>"{fgaud_s_fail}","skip"=>"{fgtp_s_na}");
    if(!isset($m["checks"][$id])){
        return "<li class='fgtp-chk ak-off' data-id='$id' data-state='none'><i class='fas fa-minus fgtp-chk-i' aria-hidden='true'></i>"
            ."<div class='fgtp-chk-m'><div class='fgtp-chk-h'><span class='ak-state'>&mdash;</span><span class='fgtp-chk-t'>".fgt_audit_label($id)."</span></div>"
            ."<div class='fgtp-chk-d'>&mdash;</div></div></li>";
    }
    $s=$m["checks"][$id]["state"];
    $d=htmlspecialchars($m["checks"][$id]["detail"]);
    if($d===""){ $d="&mdash;"; }
    return "<li class='fgtp-chk {$tones[$s]}' data-id='$id' data-state='$s'><i class='fas {$icons[$s]} fgtp-chk-i' aria-hidden='true'></i>"
        ."<div class='fgtp-chk-m'><div class='fgtp-chk-h'><span class='ak-state'>{$words[$s]}</span><span class='fgtp-chk-t'>".fgt_audit_label($id)."</span></div>"
        ."<div class='fgtp-chk-d' title=\"$d\">$d</div></div></li>";
}

/** Carte de controles : titre + tuile icone + pastille de compte, puis une ligne par controle. */
function fgtp_card($m,$key,$title,$ico,$ids,$tpl):string{
    $h=array();
    $h[]="<div class='ak-card fgtp-card' data-card='$key'>";
    $notused=($key=="wccp" && $m["mode"]=="transparent");
    $pill=$notused?"<span class='ak-pill ak-off fgtp-count' data-c='na'>{fgtp_s_na}</span>":fgtp_count($m,$ids,$tpl);
    $h[]="<div class='fgtp-card-h'><span class='ak-ico ak-ico-sm ak-ico-sq ak-accent'><i class='fas $ico' aria-hidden='true'></i></span><h3>$title</h3>$pill</div>";
    if($notused){
        $h[]="<div class='fgtp-notused'><i class='fas fa-circle-minus' aria-hidden='true'></i>{fgtp_not_used}</div>";
    }else{
        $h[]="<ul class='fgtp-chks'>";
        foreach($ids as $id){ $h[]=fgtp_check_row($m,$id); }
        $h[]="</ul>";
    }
    $h[]="</div>";
    return implode("",$h);
}

function fgtp_cards($m,$tpl):string{
    $h=array();
    $h[]="<div class='fgtp-grid'>";
    $h[]=fgtp_card($m,"proxy","{fgtp_card_proxy}","fa-server",array("squid_running","legacy_wccp","squid_patched"),$tpl);
    $h[]=fgtp_card($m,"ports","{fgtp_card_ports}","fa-plug",array("port_http","port_https"),$tpl);
    $h[]=fgtp_card($m,"network","{fgtp_card_network}","fa-network-wired",array("interface","kernel_modules","ip_forward","routing","iptables"),$tpl);
    $h[]=fgtp_card($m,"wccp","{fgtp_card_wccp}","fa-tower-broadcast",array("wccp_conf","wccp_udp","router_l2","wccp_log","iface_mismatch"),$tpl);
    $h[]="</div>";
    return implode("",$h);
}

/** Reglages FortiGate du mode WCCP, en lecture seule ; ils se modifient dans l'assistant. */
function fgtp_fortigate($m):string{
    if($m["mode"]!="wccp"){ return ""; }
    $st=$m["st"];$r=$st["reached"];
    $dash="<span class='ak-muted'>&mdash;</span>";
    $pw=$dash;
    if($r){
        $pw=$st["password_set"]
            ?"<span class='ak-state ak-ok' data-pw='set'><i class='fas fa-lock' aria-hidden='true'></i>{fgtp_pw_set}</span>"
            :"<span class='ak-state ak-warn' data-pw='unset'><i class='fas fa-lock-open' aria-hidden='true'></i>{fgtp_pw_not_set}</span>";
    }
    $tiles=array(
        array("{fgw_router}",$r?fgtp_val($st["router"]):$dash),
        array("{fgw_https_service}",($r && $st["https_service"]>0)?fgtp_val($st["https_service"]):$dash),
        array("{fgw_weight}",$r?fgtp_val($st["weight"]):$dash),
        array("{fgw_password}",$pw),
    );
    $h=array();
    $h[]="<div class='ak-card fgtp-card' data-card='fortigate'>";
    $h[]="<div class='fgtp-card-h'><span class='ak-ico ak-ico-sm ak-ico-sq ak-accent'><i class='fas fa-sliders' aria-hidden='true'></i></span><h3>{fgtp_fortigate}</h3>";
    $h[]="<button type='button' class='ak-link fgtp-edit' OnClick=\"FgtpWizard();\">{fgtp_edit_wizard}<i class='fas fa-arrow-right' aria-hidden='true'></i></button></div>";
    $h[]="<div class='fgtp-tiles'>";
    foreach($tiles as $t){
        $h[]="<div class='fgtp-tile'><span class='ak-kicker'>{$t[0]}</span><div class='fgtp-tile-v'>{$t[1]}</div></div>";
    }
    $h[]="</div></div>";
    return implode("",$h);
}

function fgtp_footer():string{
    return "<div class='fgtp-foot'>"
        ."<span><i class='fas fa-shield-halved' aria-hidden='true'></i>{fgtp_local_only}</span>"
        ."<span class='fgtp-foot-r'><i class='fas fa-rotate' aria-hidden='true'></i>{fgtp_refreshed}</span>"
        ."</div>";
}

function fgtp_empty():string{
    return "<div class='ak-card' data-card='empty'><div class='ak-empty-c'><i class='fas fa-plug-circle-xmark' aria-hidden='true'></i><span>{fgtp_not_installed}</span></div></div>";
}

/** CSS propre au panneau ; le reste vient du kit ak-*. */
function fgtp_style():string{
    if(isset($GLOBALS["FGTP_STYLE"])){ return ""; }
    $GLOBALS["FGTP_STYLE"]=true;
    $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    $c=array();
    $c[]=".fgtp{display:flex;flex-direction:column;gap:14px}";
    $c[]=".fgtp-strip{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px 20px;padding:14px 18px}";
    $c[]=".fgtp-modes{display:inline-flex;gap:4px;padding:4px;border-radius:10px;background:#f1f5f9}";
    $c[]=".fgtp-mode{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:8px;font-size:13px;font-weight:600;color:#6d7a77;white-space:nowrap}";
    $c[]=".fgtp-mode.on{background:$soft;color:#00504a}";
    $c[]=".fgtp-target{display:flex;flex-wrap:wrap;gap:10px 24px;align-items:center}";
    $c[]=".fgtp-kv{display:inline-flex;align-items:center;gap:10px;flex-wrap:wrap}";
    $c[]=".fgtp-kv .ak-kicker{display:inline-flex;align-items:center;gap:6px}";
    $c[]=".fgtp-v{font-size:14px;font-weight:600;color:#111d23}";
    $c[]=".fgtp-alerts{display:flex;flex-direction:column;gap:8px}";
    $c[]=".fgtp-err{display:block;margin-top:2px;font-size:13px;opacity:.85;overflow-wrap:anywhere}";
    $c[]=".fgtp-alert{border-left:4px solid var(--ak-tsolid,#94a3b8);padding:8px 12px;font-size:13px}";
    $c[]=".fgtp-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}";
    $c[]=".fgtp-card{padding:16px 18px}";
    $c[]=".fgtp-card-h{display:flex;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid var(--ak-border2)}";
    $c[]=".fgtp-card-h h3{flex:1 1 auto;margin:0;font-size:16px;font-weight:600;color:var(--ak-text)}";
    $c[]=".fgtp-chks{list-style:none;margin:0;padding:0}";
    $c[]=".fgtp-chk{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-top:1px solid var(--ak-border2)}";
    $c[]=".fgtp-chk:first-child{border-top:0}";
    $c[]=".fgtp-chk-i{flex:0 0 auto;margin-top:2px;font-size:16px;color:var(--ak-tsolid,#94a3b8)}";
    $c[]=".fgtp-chk-m{flex:1 1 auto;min-width:0}";
    $c[]=".fgtp-chk-h{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}";
    $c[]=".fgtp-chk-t{font-size:14px;font-weight:500;color:var(--ak-text)}";
    $c[]=".fgtp-chk-d{margin-top:2px;font-size:13px;color:var(--ak-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".fgtp-notused{display:flex;align-items:center;gap:8px;padding:12px 0 2px;font-size:13px;color:var(--ak-muted)}";
    $c[]=".fgtp-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}";
    $c[]=".fgtp-tile{display:flex;flex-direction:column;gap:8px;padding:12px 14px;border-radius:10px;background:var(--ak-surface,#f8fafc);border:1px solid var(--ak-border2);min-width:0}";
    $c[]=".fgtp-tile-v{font-size:15px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".fgtp-tile-v .fgtp-v{font-size:16px}";
    $c[]=".fgtp-foot{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px 20px;font-size:13px;color:var(--ak-muted)}";
    $c[]=".fgtp-foot span{display:inline-flex;align-items:center;gap:8px}";
    $c[]="@media (max-width:1200px){.fgtp-grid{grid-template-columns:minmax(0,1fr)}.fgtp-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    return "<style>".implode("\n",$c)."</style>";
}

/**
 * Scripts du panneau : lien vers l'onglet de l'assistant WCCP, et TINY rejoue
 * seulement si sa signature a change (Start() remet la signature a zero).
 */
function fgtp_js($jstiny):string{
    $wccp="fw.proxy.transparent.fortigate.wccp.php";
    $sig=md5($jstiny);
    return "<script>
function FgtpWizard(){
  var a=document.querySelector('a[data-url*=\"$wccp?wizard=yes\"]');
  if(a){ a.click(); return; }
  Loadjs('$wccp?settings-js=yes');
}
(function(){ if(window.FgtpTinySig!=='$sig'){ window.FgtpTinySig='$sig'; $jstiny } })();
</script>";
}

/** TINY : boutons propres a chaque mode. */
function fgtp_tiny($m,$tpl):string{
    $page=CurrentPageName();
    $st=$m["st"];
    $enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TProxyFortiEnable"));
    $install=$tpl->framework_buildjs("/proxy/fortigate/install",
        "squid.fortigate.progress",
        "squid.fortigate.log",
        "progress-fortiports-restart",
        "LoadAjaxSilent('tproxy-params','$page?parameters-flat=yes');"
    );
    $topbuttons=array();
    $TINY_ARRAY=array();
    if($st["mode"]=="wccp"){
        $topbuttons[] = array("LoadAjaxSilent('tproxy-params','$page?parameters-flat=yes')", ico_refresh, "{refresh}");
        if($enabled==1){
            $topbuttons[] = array("Loadjs('fw.fortigate.tproxy.php?fortigate-uninstall-js')", ico_trash, "{uninstall}");
        }else{
            // Sans ce bouton, une integration desinstallee en mode WCCP ne pouvait plus etre reinstallee d'ici.
            $topbuttons[] = array($install, ico_cd, "{install}");
        }
        if($st["reached"] && !$st["meta_managed"]){
            $topbuttons[] = array("Loadjs('$page?mode-js=transparent')", ico_firewall, "{fgw_switch_to_transparent}");
        }
        $TINY_ARRAY["TITLE"]="{fgw_title_wccp}";
        $TINY_ARRAY["EXPL"]="{fgw_wccp_explain}";
    }else{
        $topbuttons[] = array("Loadjs('fw.fortigate.tproxy.php?fortigate-rules-js')", ico_support, "{fortigate_rules}");
        $topbuttons[] = array("Loadjs('fw.fortigate.tproxy.php?fortigate-params-js')", ico_params, "{parameters}");
        if($enabled==1) {
            $topbuttons[] = array("Loadjs('fw.fortigate.tproxy.php?fortigate-uninstall-js')", ico_trash, "{uninstall}");
        }else{
            $topbuttons[] = array($install, ico_cd, "{install}");
        }
        if($st["reached"] && !$st["meta_managed"]){
            $topbuttons[] = array("Loadjs('$page?mode-js=wccp')", ico_firewall, "{fgw_switch_to_wccp}");
        }
        $TINY_ARRAY["TITLE"]="{fortigate_tproxy}";
        $TINY_ARRAY["EXPL"]="{fortigate_tproxy_explain}";
    }
    $TINY_ARRAY["ICO"]=ico_firewall;
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

/** Panneau de droite (les deux modes), recharge toutes les 10 s par Start(). */
function parameters_flat(){
    $tpl=new template_admin();
    $m=fgtp_model();
    $jstiny=fgtp_tiny($m,$tpl);
    $h=array();
    $h[]="<div class='ak fgtp' id='fgtp-root' data-mode='".($m["mode"]==""?"unknown":$m["mode"])."'>";
    $h[]=fgtp_mode_strip($m);
    $h[]=fgtp_alerts($m,$tpl);
    if($m["enabled"]===false){
        $h[]=fgtp_empty();
    }else{
        $h[]=fgtp_cards($m,$tpl);
        $h[]=fgtp_fortigate($m);
        $h[]=fgtp_footer();
    }
    $h[]="</div>";
    echo fgtp_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).fgtp_js($jstiny);
}
// Fin du panneau de droite


function status(){
    $tpl=new template_admin();

    // Tuile de service standard, identique a celle de la page du proxy
    // (fw.proxy.status.php squid_service_status) : meme source, meme rendu.
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/service/status"));
    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error(),"{error}"));
    }elseif(!is_object($json) || empty($json->Status)){
        $err=(is_object($json) && isset($json->Error))?strval($json->Error):"";
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>".htmlspecialchars($err),"{error}"));
    }elseif(!property_exists($json,"Info")){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Missing property Info","{error}"));
    }else{
        $ini=new Bs_IniHandler();
        $ini->loadString($json->Info);
        echo $tpl->SERVICE_STATUS($ini,"APP_SQUID");
    }
    // Verdict de l'API d'audit sous la tuile.
    echo fgt_audit_banner($tpl);
    return true;
}

/** Libelles des controles d'audit (jetons fgaud_<id>). Un ID inconnu est affiche tel quel. */
function fgt_audit_labels():array{
    return array("enabled","legacy_wccp","squid_running","interface","kernel_modules","ip_forward","routing",
        "iptables","port_http","port_https","squid_patched","wccp_conf","wccp_udp","router_l2","wccp_log","iface_mismatch");
}

function fgt_audit_label($id):string{
    if(in_array($id,fgt_audit_labels(),true)){ return "{fgaud_".$id."}"; }
    return htmlspecialchars(strval($id));
}

/** Lit /tproxyforti/audit. Retourne array(data|null, erreur). */
function fgt_audit_read():array{
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/tproxyforti/audit");
    $j=json_decode(strval($raw));
    if(!is_object($j)){
        $e=trim(strval($raw));
        return array(null,($e=="")?"empty answer":substr($e,0,300));
    }
    if(empty($j->success) || !isset($j->data) || !is_object($j->data)){
        return array(null,isset($j->error)?strval($j->error):"unexpected answer");
    }
    return array($j->data,"");
}

function fgt_audit_banner($tpl):string{
    $page=CurrentPageName();
    list($d,$err)=fgt_audit_read();
    $link="<a class='ak-link' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?audit-js=yes');\">{fgaud_details}</a>";
    if($d===null){
        $h="<div class='ak-banner ak-off'><i class='fas fa-circle-question'></i> <span class='ak-banner-m'>{fgaud_unavailable}<br><small>".htmlspecialchars($err)."</small></span></div>";
        return $tpl->_ENGINE_parse_body("<div class='ak' style='margin-top:10px'>$h</div>");
    }
    if(empty($d->enabled)){
        $h="<div class='ak-banner ak-off'><i class='fas fa-power-off'></i> <span class='ak-banner-m'>{fgaud_disabled}</span></div>";
        return $tpl->_ENGINE_parse_body("<div class='ak' style='margin-top:10px'>$h</div>");
    }
    if(!empty($d->ready)){
        $h="<div class='ak-banner ak-ok'><i class='fas fa-circle-check'></i> <span class='ak-banner-m'>{fgaud_ready}</span></div>";
    }else{
        $first="";
        if(isset($d->checks) && is_array($d->checks)){
            foreach($d->checks as $c){
                if(is_object($c) && strval($c->state ?? "")=="fail"){ $first=strval($c->id ?? ""); break; }
            }
        }
        if($first=="" && isset($d->failed) && is_array($d->failed) && count($d->failed)>0){ $first=strval($d->failed[0]); }
        $why=($first=="")?"":"<br><small>".fgt_audit_label($first)."</small>";
        $h="<div class='ak-banner ak-err'><i class='fas fa-circle-xmark'></i> <span class='ak-banner-m'>{fgaud_not_ready}$why</span></div>";
    }
    return $tpl->_ENGINE_parse_body("<div class='ak' style='margin-top:10px'>$h<div style='margin-top:6px'>$link</div></div>");
}

function audit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{fgaud_details}","$page?audit-popup=yes",720);
}

function audit_popup():bool{
    $tpl=new template_admin();
    list($d,$err)=fgt_audit_read();
    if($d===null){
        echo $tpl->_ENGINE_parse_body("<div class='ak'><div class='ak-banner ak-off'><span class='ak-banner-m'>{fgaud_unavailable}<br><small>".htmlspecialchars($err)."</small></span></div></div>");
        return true;
    }
    $icons=array("ok"=>"fa-circle-check","warn"=>"fa-triangle-exclamation","fail"=>"fa-circle-xmark","skip"=>"fa-circle-minus");
    $tones=array("ok"=>"ak-ok","warn"=>"ak-warn","fail"=>"ak-err","skip"=>"ak-off");
    $rows=array();$skips=array();
    if(isset($d->checks) && is_array($d->checks)){
        foreach($d->checks as $c){
            if(!is_object($c)){continue;}
            $st=strval($c->state ?? "skip");
            if(!isset($icons[$st])){$st="skip";}
            $r="<tr><td style='width:1%' nowrap><span class='ak-state {$tones[$st]}'><i class='fas {$icons[$st]}'></i> {fgaud_s_$st}</span></td>"
              ."<td style='width:35%'>".fgt_audit_label(strval($c->id ?? ""))."</td>"
              ."<td class='ak-muted'>".htmlspecialchars(strval($c->detail ?? ""))."</td></tr>";
            if($st=="skip"){$skips[]=$r;}else{$rows[]=$r;}
        }
    }
    $all=array_merge($rows,$skips);
    $h="<div class='ak'><div class='ak-card'><table class='ak-tbl'><tbody>".@implode("",$all)."</tbody></table></div></div>";
    echo $tpl->_ENGINE_parse_body($h);
    return true;
}

/** Modal de confirmation du changement de mode (Transparent / WCCP). */
function mode_js(){
    $tpl=new template_admin();
    $mode=(isset($_GET["mode-js"]) && $_GET["mode-js"]=="wccp")?"wccp":"transparent";
    $page=CurrentPageName();
    $text=($mode=="wccp")?"{fgw_confirm_wccp}":"{fgw_confirm_transparent}";
    $tpl->js_dialog_confirm_action($text,"fgw-mode-set",$mode,
        "LoadAjax('table-fortiports','$page?tabs=yes');");
}

/**
 * POST /tproxyforti/mode. 422 (refus) et 409 (verrou Meta) : le message anglais du
 * daemon est affiche tel quel. 202 : le resultat se lit ensuite via /status.
 * Rien n'est ecrit en cas de succes (le dialogue se ferme et rafraichit).
 */
function mode_set(){
    $tpl=new template_admin();
    $mode=(isset($_POST["fgw-mode-set"]) && $_POST["fgw-mode-set"]=="wccp")?"wccp":"transparent";
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/tproxyforti/mode",array("mode"=>$mode));
    $e=fgt_rest_err($resp);
    if($e!=""){ echo $tpl->post_error($e); }
}

/** Erreur d'un appel REST, "" seulement si l'appel a reussi (reponse non JSON = erreur). */
function fgt_rest_err($resp):string{
    $r=json_decode(strval($resp),true);
    if(is_array($r)){
        if(!empty($r["error"])){ return strval($r["error"]); }
        if(isset($r["success"]) && !$r["success"]){ return strval(isset($r["message"])?$r["message"]:"error"); }
        return "";
    }
    $raw=trim(str_replace(array("'","\\"),array(" "," "),strval($resp)));
    return $raw==""?"empty answer":substr($raw,0,300);
}
