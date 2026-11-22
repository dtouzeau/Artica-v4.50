<?php
// fw.bridges.php — "Interfaces connectors": the paths that let traffic cross from one
// network card of the appliance to another (pnic_bridges, read and written ONLY through
// articarest /firewall/config/bridges*, class.firewall.api.inc).
//
// Stitch redesign 2026-11-19 (stitch_firewall_interfaces_connectors_console): value banner,
// "how it works" explainer, transit policy card, summary line, one card per connector.
// REAL DATA ONLY: traffic figures are the cumulative iptables counters of the ROUTER family
// (/system/network/iptables/stats), reset whenever the firewall is reloaded — no rate, no
// history. One model (brg_model) feeds every widget; one widget = one function.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["start"])){start();exit;}
if(isset($_POST["masqid"])){masq_save();exit;}
if(isset($_GET["masq-id"])){masq_id_js();exit;}
if(isset($_GET["masq-tab"])){masq_tab();exit;}
if(isset($_GET["masq-popup"])){masq_popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_POST["brg-enable"])){enable_save();exit;}
if(isset($_GET["counters-js"])){Counter_js();exit;}
page();

// $function travels into OnClick / $jsafter: whitelist at the read. Without one (external
// callers of ruleid-js), refresh this page's cards if they are on screen.
function brg_function(){
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}
function brg_after($function){
    if($function!=""){return "if(typeof $function==='function'){ $function(); }";}
    return "if(typeof BrgReload==='function'){ BrgReload(); }";
}

// One bridge through the API (null when unknown).
function brg_get($ID){
    $d=fwapi_data(fwapi_get("bridges/".intval($ID)));
    return is_array($d)?$d:null;
}

// ── Model ─────────────────────────────────────────────────────────────────

function brg_model(){
    $m=array("error"=>"","bridges"=>array(),"stats"=>array(),"transit"=>null,"firehol"=>0,"nics"=>array(),"sources"=>array());
    $resp=fwapi_get("bridges");
    $list=fwapi_data($resp);
    if(!is_array($list)){
        $m["error"]=fwapi_error($resp);
        if($m["error"]==""){$m["error"]="{fw_api_no_answer}";}
        return $m;
    }
    usort($list,function($a,$b){ return intval($b["id"] ?? 0)-intval($a["id"] ?? 0); });
    $m["bridges"]=$list;
    $m["firehol"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    $m["transit"]=fwapi_data(fwapi_get("transit"));

    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/iptables/stats"));
    if(is_object($json) && isset($json->Data->Family->ROUTER) && is_object($json->Data->Family->ROUTER)){
        foreach($json->Data->Family->ROUTER as $id=>$s){
            $m["stats"][intval($id)]=array("exists"=>!empty($s->exists),"pkts"=>intval($s->pkts ?? 0),"bytes"=>intval($s->bytes ?? 0));
        }
    }
    foreach($list as $b){
        foreach(array((string)($b["nic_from"] ?? ""),(string)($b["nic_to"] ?? "")) as $nic){
            if($nic!="" && !isset($m["nics"][$nic])){$m["nics"][$nic]=brg_nic_info($nic);}
        }
        if(intval($b["OnlyMASQ"] ?? 0)==1){
            $src=fwapi_data(fwapi_get("bridges/".intval($b["id"])."/sources"));
            $m["sources"][intval($b["id"])]=is_array($src)?count($src):0;
        }
    }
    return $m;
}

function brg_nic_info($nic){
    $n=new system_nic($nic);
    $label=trim(str_replace("Interface $nic","",(string)$n->NICNAME));
    $ip=trim((string)$n->IPADDR);
    $mask=trim((string)$n->NETMASK);
    if($mask=="0.0.0.0"){$mask="";}
    $cidr="";
    if($ip!="" && $mask!="" && filter_var($mask,FILTER_VALIDATE_IP)){
        $long=ip2long($mask);
        $cidr="/".strlen(str_replace("0","",decbin($long & 0xFFFFFFFF)));
    }
    return array("label"=>$label,"ip"=>($ip!=""?$ip.$cidr:""));
}

// "12,450 packets" — the unit travels with the number, so a card not loaded shows a bare dash.
function brg_packets($n){
    $tpl=new template_admin();
    return number_format(intval($n))." ".$tpl->_ENGINE_parse_body("{brg_packets_unit}");
}

function brg_bytes($bytes){
    if($bytes<1024){return intval($bytes)." B";}
    return FormatBytes($bytes/1024,true);
}

// ── Page ──────────────────────────────────────────────────────────────────

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{interfaces_connectors}",
        "fas fa-bezier-curve",
        "{brg_header_explain}",
        "$page?start=yes",
        "interfaces-connector",
        "progress-firehol-restart",false,
        "table-routers-loader"
    );
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {interfaces_connectors}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// start: banner + explainer (static), the cards host, and the live counters timer.
// The counter span is RefreshInterval's sentinel: it must stay in the DOM.
function start(){
    $page=CurrentPageName();
    $t=time();
    $tpl=new template_admin();
    $html=array();
    $html[]="<div class='brg'>";
    $html[]="<span id='bridges-counter-$t'></span>";
    $html[]=brg_hero();
    $html[]=brg_explainer();
    $html[]="<div id='brg-host'></div>";
    $html[]="</div>";
    $js="window.BrgReload=function(){LoadAjaxSilent('brg-host','$page?table=yes&function=BrgReload');};\n".
        "BrgReload();\n".$tpl->RefreshInterval_Loadjs("bridges-counter-$t",$page,"counters-js=yes");
    echo brg_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>$js\n".brg_explainer_js()."</script>";
}

// table: transit card + summary + cards (or the empty invitation) + TINY.
function table(){
    $tpl=new template_admin();
    $function=brg_function();
    if($function==""){$function="BrgReload";}
    $m=brg_model();
    $html=array();
    if($m["error"]!=""){
        $html[]="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation'></i><div class='ak-banner-m'><strong>{brg_error_title}</strong> ".htmlspecialchars($tpl->_ENGINE_parse_body($m["error"]))."</div></div>";
    }else{
        $html[]=brg_transit($m);
        if(count($m["bridges"])==0){
            $html[]=brg_empty();
        }else{
            $html[]=brg_summary($m);
            if($m["firehol"]==0){
                $html[]="<div class='ak-banner ak-info brg-banner'><i class='fas fa-circle-info'></i><div class='ak-banner-m'>{brg_firehol_off}</div></div>";
            }
            $html[]="<div class='brg-grid' id='brg-cards'>";
            foreach($m["bridges"] as $b){$html[]=brg_card($b,$m,$function);}
            $html[]="</div>";
        }
    }
    echo brg_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>NoSpinner();\n".brg_cards_js()."</script>".brg_tiny($function);
}

function brg_tiny($function){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?ruleid-js=0&function=$function',true);",ico_plus,"{new_connector}");
    $topbuttons[]=array("Loadjs('$page?masq-id=0&function=$function')",ico_plus,"{brg_new_masq}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{interfaces_connectors}";
    $TINY_ARRAY["ICO"]="fas fa-bezier-curve";
    $TINY_ARRAY["EXPL"]="{brg_header_explain}";
    $TINY_ARRAY["URL"]="interfaces-connector";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

// ── Widgets ───────────────────────────────────────────────────────────────

function brg_hero(){
    return "<section class='ak-card brg-hero'><div class='brg-hero-txt'>".
        "<h2 class='brg-hero-t'>{brg_hero_title}</h2><p class='brg-hero-p'>{brg_hero_text}</p>".
        "<div class='ak-chips brg-chips'>".
        "<span class='ak-tag'><i class='fas fa-route'></i>{brg_chip_path}</span>".
        "<span class='ak-tag'><i class='fas fa-mask'></i>{brg_chip_masq}</span>".
        "<span class='ak-tag'><i class='fas fa-shield-halved'></i>{brg_chip_rules}</span>".
        "<span class='ak-tag'><i class='fas fa-toggle-on'></i>{brg_chip_toggle}</span>".
        "</div></div></section>";
}

// "How it works": an EXAMPLE diagram + the three concepts. Open by default; the reader's
// choice to hide it is a per-viewer convenience kept in localStorage.
function brg_explainer(){
    return "<details class='ak-card brg-how' id='brg-how' open>".
        "<summary class='brg-how-h'><span class='ak-ico ak-ico-xs ak-accent'><i class='fas fa-circle-info'></i></span><b>{brg_how_title}</b>".
        "<span class='brg-how-tg'><span class='brg-how-hide'>{brg_hide_explanations}</span><span class='brg-how-show'>{brg_show_explanations}</span><i class='fas fa-chevron-down ak-chev'></i></span></summary>".
        "<div class='brg-how-b'>".
        brg_diagram().
        "<div class='brg-concepts'>".
        brg_concept("fa-route","{connector}","{brg_concept_connector}").
        brg_concept("fa-mask","{brg_masq_nat}","{brg_concept_masq}").
        brg_concept("fa-scale-balanced","{brg_policy}","{brg_concept_policy}").
        "</div></div></details>";
}

function brg_diagram(){
    return "<div class='brg-diag' role='img' aria-label='{brg_example}'>".
        "<span class='brg-diag-ex'>{brg_example}</span>".
        "<div class='brg-node'><span class='ak-ico ak-off'><i class='fas fa-users'></i></span><b>{brg_ex_office}</b><span class='ak-mono'>eth1 · 192.168.1.0/24</span><small>{brg_ex_source_card}</small></div>".
        "<div class='brg-wire'><span class='brg-wire-l'></span><span class='brg-wire-mid'><i class='fas fa-shield-halved'></i>Artica <span class='ak-pill ak-ok'>{connector}</span></span><span class='brg-wire-l brg-wire-arrow'></span>".
        "<span class='ak-tag ak-warn brg-wire-masq'><i class='fas fa-mask'></i>{masquerading}</span></div>".
        "<div class='brg-node'><span class='ak-ico ak-info'><i class='fas fa-globe'></i></span><b>{brg_ex_internet}</b><span class='ak-mono'>eth0</span><small>{brg_ex_dest_card}</small></div>".
        "</div>";
}

function brg_concept($ico,$title,$text){
    return "<div class='brg-concept'><div class='brg-concept-t'><span class='ak-ico ak-ico-xs ak-accent'><i class='fas $ico'></i></span>$title</div><p>$text</p></div>";
}

function brg_transit($m){
    $users=new usersMenus();
    $t=$m["transit"];
    if(!is_array($t)){return "";}
    $link="";
    if($users->AsSystemAdministrator){
        $link="<button type='button' class='ak-link' onclick=\"Loadjs('fw.settings.php?fw-transit-js=yes&after=BrgReload')\">{brg_change_transit}</button>";
    }
    if(($t["policy"] ?? "")=="reject"){
        return "<div class='ak-card brg-transit'><span class='ak-ico ak-ok'><i class='fas fa-lock'></i></span>".
            "<div class='brg-transit-b'><span class='brg-lbl'>{brg_transit}</span><b>{fw_transit_reject_banner}</b></div>$link</div>";
    }
    return "<div class='ak-card brg-transit brg-transit-warn'><span class='ak-ico ak-warn'><i class='fas fa-triangle-exclamation'></i></span>".
        "<div class='brg-transit-b'><span class='brg-lbl'>{brg_transit}</span><b>{fw_transit_accept_banner}</b><span class='brg-help'>{brg_transit_accept_help}</span></div>$link</div>";
}

function brg_summary($m){
    $tpl=new template_admin();
    $n=count($m["bridges"]);$on=0;$masq=0;$prob=0;
    foreach($m["bridges"] as $b){
        if(intval($b["enabled"] ?? 0)==1){$on++;}
        if(intval($b["OnlyMASQ"] ?? 0)==1){$masq++;}
        if(!empty($b["problems"])){$prob++;}
    }
    $parts=array();
    $parts[]="<b>".str_replace("%n",strval($n),$tpl->_ENGINE_parse_body("{brg_sum_total}"))."</b>";
    $parts[]=str_replace("%n",strval($on),$tpl->_ENGINE_parse_body("{brg_sum_enabled}"));
    $parts[]=str_replace("%n",strval($n-$on),$tpl->_ENGINE_parse_body("{brg_sum_disabled}"));
    if($masq>0){$parts[]=str_replace("%n",strval($masq),$tpl->_ENGINE_parse_body("{brg_sum_masq}"));}
    $h="<div class='brg-sum'><span>".implode(" &middot; ",$parts)."</span>";
    if($prob>0){
        $h.="<button type='button' class='brg-prob-link' aria-pressed='false' onclick=\"BrgOnlyProblems(this)\"><i class='fas fa-triangle-exclamation'></i>".
            str_replace("%n",strval($prob),$tpl->_ENGINE_parse_body("{brg_sum_problems}"))."</button>";
    }
    return $h."</div>";
}

function brg_card($b,$m,$function){
    $page=CurrentPageName();
    $ID=intval($b["id"] ?? 0);
    $masqOnly=(intval($b["OnlyMASQ"] ?? 0)==1);
    $enabled=(intval($b["enabled"] ?? 0)==1);
    $problems=(isset($b["problems"]) && is_array($b["problems"]))?$b["problems"]:array();
    $edit=$masqOnly?"Loadjs('$page?masq-id=$ID&function=$function')":"Loadjs('$page?ruleid-js=$ID&function=$function',true)";
    $cls="ak-card brg-card".(count($problems)>0?" brg-card-bad":"").($enabled?"":" brg-card-off");
    $h=array();
    $h[]="<article class='$cls' data-problem='".(count($problems)>0?"1":"0")."'>";
    $h[]=brg_card_head($b,$ID,$masqOnly,$enabled,$function);
    $h[]=brg_card_route($b,$m,$problems,$edit);
    if(count($problems)>0){$h[]=brg_card_problems($problems);}
    $h[]=brg_card_status($ID,$enabled,$m);
    $h[]=brg_card_chips($b,$m,$masqOnly,$ID);
    $h[]="<div class='brg-card-f'><span class='brg-id'>N.$ID</span><span class='ak-grow'></span>".
        "<button type='button' class='ak-btn ak-btn-sm ak-btn-ghost' onclick=\"$edit\"><i class='fas fa-pen'></i>".(count($problems)>0?"{brg_fix_edit}":"{brg_edit}")."</button>".
        "<button type='button' class='ak-btn ak-btn-sm ak-btn-danger' onclick=\"Loadjs('$page?delete-rule-js=$ID&function=$function')\"><i class='fas fa-trash-can'></i>{brg_delete}</button></div>";
    $h[]="</article>";
    return implode("\n",$h);
}

function brg_card_head($b,$ID,$masqOnly,$enabled,$function){
    $page=CurrentPageName();
    $kind=$masqOnly?"<span class='ak-pill ak-warn'><i class='fas fa-mask'></i>{brg_kind_masq}</span>":"<span class='ak-pill ak-ok'><i class='fas fa-route'></i>{connector}</span>";
    $name=trim((string)($b["rulename"] ?? ""));
    $nameHtml=($name!="")?"<span class='brg-name'>".htmlspecialchars($name)."</span>":"";
    $js="Loadjs('$page?enable-js=$ID&value=".($enabled?0:1)."&function=$function')";
    $lbl="brg-sw-$ID";
    return "<div class='brg-card-h'>$kind$nameHtml<span class='ak-grow'></span>".
        "<span class='brg-sw'><span id='$lbl' class='brg-sw-l'>".($enabled?"{brg_enabled}":"{brg_disabled}")."</span>".
        "<button type='button' role='switch' class='ak-sw' aria-checked='".($enabled?"true":"false")."' aria-labelledby='$lbl' title='{brg_switch_help}' onclick=\"$js\"></button></span></div>";
}

function brg_card_route($b,$m,$problems,$edit){
    $from=(string)($b["nic_from"] ?? "");$to=(string)($b["nic_to"] ?? "");
    $bad=array();
    foreach($problems as $p){ if(($p["error"] ?? "")=="{fw_iface_unknown}"){$bad[(string)($p["detail"] ?? "")]=1;} }
    $side=function($nic) use ($m,$bad){
        $i=isset($m["nics"][$nic])?$m["nics"][$nic]:array("label"=>"","ip"=>"");
        if(isset($bad[$nic])){return "<span class='brg-unknown'>{brg_unknown_card}</span>";}
        $p=array();
        if($i["label"]!=""){$p[]=htmlspecialchars($i["label"]);}
        if($i["ip"]!=""){$p[]="<span class='ak-mono'>".htmlspecialchars($i["ip"])."</span>";}
        return count($p)>0?implode(" &middot; ",$p):"<span class='ak-muted'>{brg_no_address}</span>";
    };
    $cf=isset($bad[$from])?" brg-bad":"";$ct=isset($bad[$to])?" brg-bad":"";
    return "<a class='brg-route' href='javascript:blur();' onclick=\"$edit\"><span class='ak-mono$cf'>".htmlspecialchars($from)."</span><i class='fas fa-arrow-right-long'></i><span class='ak-mono$ct'>".htmlspecialchars($to)."</span></a>".
        "<div class='brg-sides'>".$side($from)." <i class='fas fa-arrow-right brg-sides-a'></i> ".$side($to)."</div>";
}

function brg_card_problems($problems){
    $li=array();
    foreach($problems as $p){
        $li[]="<li>".($p["error"] ?? "")." <b class='ak-mono'>".htmlspecialchars((string)($p["detail"] ?? ""))."</b></li>";
    }
    return "<div class='ak-banner ak-err brg-probs'><i class='fas fa-triangle-exclamation'></i><div class='ak-banner-m'><strong>{brg_problem_title}</strong><ul>".implode("",$li)."</ul></div></div>";
}

// Status + traffic. The ids are the ones Counter_js() refreshes every few seconds.
function brg_card_status($ID,$enabled,$m){
    $s=isset($m["stats"][$ID])?$m["stats"][$ID]:null;
    $loaded=($enabled && $s!==null && $s["exists"]);
    $pk=$loaded?brg_packets($s["pkts"]):"—";
    $sz=$loaded?brg_bytes($s["bytes"]):"";
    return "<div class='brg-status'><span id='bridge-status-$ID'>".brg_status_pill($loaded,$enabled)."</span>".
        "<span class='brg-traffic'>{brg_traffic} <b id='bridge-packets-$ID'>$pk</b> <b id='bridge-size-$ID'>$sz</b>".
        "<span class='brg-help'>{brg_since_loaded}</span></span></div>";
}

function brg_status_pill($loaded,$enabled=true){
    if(!$enabled){return "<span class='ak-state ak-off'><i class='fas fa-circle-pause'></i>{brg_state_disabled}</span>";}
    if($loaded){return "<span class='ak-state ak-ok'><i class='fas fa-circle-check'></i>{brg_state_loaded}</span>";}
    return "<span class='ak-state ak-off'><i class='far fa-circle'></i>{brg_state_not_loaded}</span>";
}

function brg_card_chips($b,$m,$masqOnly,$ID){
    $tpl=new template_admin();
    $c=array();
    if($masqOnly){
        $n=isset($m["sources"][$ID])?$m["sources"][$ID]:0;
        $c[]=($n>0)?"<span class='ak-tag'><i class='fas fa-sitemap'></i>".str_replace("%n",strval($n),$tpl->_ENGINE_parse_body("{brg_n_sources}"))."</span>"
                   :"<span class='ak-tag'><i class='fas fa-sitemap'></i>{brg_all_sources}</span>";
    }else{
        if(intval($b["masquerading"] ?? 0)==1){$c[]="<span class='ak-tag'><i class='fas fa-mask'></i>{masquerading}</span>";}
        if($m["firehol"]==1){
            if(intval($b["NoFirewall"] ?? 0)==1){
                $c[]="<span class='ak-tag ak-warn'><i class='fas fa-link'></i>{brg_join_only}</span>";
            }else{
                $c[]=(intval($b["policy"] ?? 0)==1)?"<span class='ak-tag ak-err'><i class='fas fa-ban'></i>{brg_deny_end}</span>"
                                                   :"<span class='ak-tag'><i class='fas fa-check'></i>{brg_allow_end}</span>";
            }
            if(intval($b["DenyDHCP"] ?? 0)==1){$c[]="<span class='ak-tag'><i class='fas fa-ban'></i>{brg_dhcp_blocked}</span>";}
            if(intval($b["jlog"] ?? 0)==1){$c[]="<span class='ak-tag'><i class='fas fa-file-lines'></i>{brg_logged}</span>";}
        }
    }
    if(count($c)==0){return "";}
    return "<div class='ak-chips brg-card-chips'>".implode("",$c)."</div>";
}

function brg_empty(){
    $page=CurrentPageName();
    return "<div class='ak-card ak-empty-c brg-empty'><i class='fas fa-route'></i><b class='brg-empty-t'>{brg_empty_title}</b>".
        "<p class='brg-empty-p'>{brg_empty_text}</p>".
        "<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' onclick=\"Loadjs('$page?ruleid-js=0&function=BrgReload',true);\"><i class='fas fa-plus'></i>{new_connector}</button>".
        "<p class='ak-note'>{brg_empty_masq}</p></div>";
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

function brg_explainer_js(){
    return '(function(){var d=document.getElementById("brg-how"); if(!d){return;}
 try{ if(localStorage.getItem("brgHowClosed")==="1"){ d.removeAttribute("open"); } }catch(e){}
 d.addEventListener("toggle",function(){ try{ localStorage.setItem("brgHowClosed",d.open?"0":"1"); }catch(e){} });
})();';
}

function brg_cards_js(){
    return 'window.BrgOnlyProblems=function(btn){
 var on=btn.getAttribute("aria-pressed")!=="true"; btn.setAttribute("aria-pressed",on?"true":"false"); btn.classList.toggle("on",on);
 var cs=document.querySelectorAll("#brg-cards .brg-card");
 for(var i=0;i<cs.length;i++){ cs[i].style.display=(on && cs[i].getAttribute("data-problem")!=="1")?"none":""; }
};';
}

function brg_style(){
    if(isset($GLOBALS["BRG_STYLE"])){return "";}
    $GLOBALS["BRG_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.brg{margin-top:10px;display:flex;flex-direction:column;gap:16px}
.brg h2{margin:0}
.brg-hero{padding:22px 28px;border-left:4px solid $accent}
.brg-hero-txt{max-width:880px}
.brg-hero-t{font-size:22px;font-weight:600;color:#0f172a;line-height:1.3}
.brg-hero-p{font-size:14px;color:#475569;line-height:1.6;margin:8px 0 0}
.brg-chips{margin-top:14px;gap:8px}
.brg-how{padding:0}
.brg-how-h{display:flex;align-items:center;gap:10px;padding:14px 20px;cursor:pointer;list-style:none;font-size:15px;color:#0f172a}
.brg-how-h::-webkit-details-marker{display:none}
.brg-how-tg{margin-left:auto;display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#475569}
.brg-how[open] .brg-how-show,.brg-how:not([open]) .brg-how-hide{display:none}
.brg-how:not([open]) .ak-chev{transform:rotate(-90deg)}
.brg-how-b{padding:0 20px 20px;border-top:1px solid #f1f5f9}
.brg-diag{position:relative;display:flex;align-items:center;gap:16px;margin-top:16px;padding:24px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc}
.brg-diag-ex{position:absolute;top:10px;right:12px;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b;background:#e2e8f0;border-radius:6px;padding:2px 8px}
.brg-node{flex:0 0 220px;display:flex;flex-direction:column;align-items:center;gap:4px;padding:16px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;text-align:center}
.brg-node b{font-size:15px;color:#0f172a}
.brg-node .ak-mono{font-size:13px;color:#475569}
.brg-node small{font-size:13px;color:#64748b}
.brg-wire{flex:1 1 auto;position:relative;display:flex;align-items:center;min-width:220px;padding-bottom:30px}
.brg-wire-l{flex:1 1 auto;border-top:2px solid $accent}
.brg-wire-arrow{position:relative}
.brg-wire-arrow::after{content:'';position:absolute;right:-2px;top:-7px;border:6px solid transparent;border-left:9px solid $accent}
.brg-wire-mid{display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border:2px solid $accent;border-radius:10px;background:#fff;font-weight:600;color:#0f172a;white-space:nowrap}
.brg-wire-masq{position:absolute;left:50%;transform:translateX(-50%);bottom:0}
.brg-concepts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:16px}
.brg-concept{border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px}
.brg-concept-t{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:600;color:#0f172a}
.brg-concept p{margin:8px 0 0;font-size:13px;color:#475569;line-height:1.5}
.brg-transit{display:flex;align-items:center;gap:14px;padding:14px 20px}
.brg-transit-b{display:flex;flex-direction:column;gap:2px;flex:1 1 auto}
.brg-transit-b b{font-size:14px;color:#0f172a}
.brg-transit-warn{border-color:#f5c48a}
.brg-lbl{font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.brg-help{display:block;font-size:13px;color:#64748b}
.brg-banner{margin:0}
#brg-host{display:flex;flex-direction:column;gap:16px}
#brg-host:empty{display:none}
.brg-sum{display:flex;align-items:center;gap:16px;font-size:14px;color:#475569;padding:0 4px}
.brg-sum b{color:#0f172a}
.brg-prob-link{display:inline-flex;align-items:center;gap:6px;border:1px solid transparent;background:transparent;color:#b91c1c;font-weight:600;font-size:14px;border-radius:999px;padding:3px 10px;cursor:pointer}
.brg-prob-link:hover,.brg-prob-link.on{border-color:#fca5a5;background:#fef2f2}
.brg-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.brg-card{padding:18px 20px;display:flex;flex-direction:column;gap:10px}
.brg-card-bad{border-color:#fca5a5}
.brg-card-off .brg-route{color:#64748b}
.brg-card-h{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.brg-name{font-size:14px;font-weight:600;color:#0f172a}
.brg-sw{display:inline-flex;align-items:center;gap:8px}
.brg-sw-l{font-size:13px;color:#475569}
.brg-route{display:inline-flex;align-items:center;gap:10px;font-size:20px;font-weight:600;color:#0f172a;text-decoration:none}
.brg-route:hover{color:#0f766e;text-decoration:none}
.brg-route .fa-arrow-right-long,.brg-route svg{font-size:16px;color:#94a3b8}
.brg-bad{color:#b91c1c}
.brg-sides{font-size:13px;color:#475569;line-height:1.5}
.brg-sides-a{font-size:11px;color:#94a3b8;margin:0 4px}
.brg-unknown{color:#b91c1c;font-weight:600}
.brg-probs{margin:0}
.brg-probs ul{margin:4px 0 0;padding-left:18px}
.brg-status{display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap}
.brg-traffic{font-size:13px;color:#475569}
.brg-traffic b{color:#0f172a}
.brg-traffic .brg-help{display:inline;margin-left:6px}
.brg-card-chips{gap:6px}
.brg-card-f{display:flex;align-items:center;gap:8px;margin-top:auto;padding-top:10px;border-top:1px solid #f1f5f9}
.brg-id{font-size:13px;color:#64748b}
.brg-empty{padding:40px 24px}
.brg-empty-t{font-size:17px;color:#0f172a}
.brg-empty-p{max-width:640px;margin:6px auto 14px;color:#475569;font-size:14px;line-height:1.5}
.brg-how-h:focus-visible,.brg-route:focus-visible,.brg-prob-link:focus-visible{outline:2px solid $accent;outline-offset:2px}
@media(max-width:1200px){.brg-grid{grid-template-columns:1fr}.brg-concepts{grid-template-columns:1fr}}
@media(max-width:900px){.brg-diag{flex-direction:column}.brg-node{flex:0 0 auto;width:100%}}
</style>";
}

// ── Live counters (RefreshInterval_Loadjs → JavaScript) ───────────────────

function Counter_js(){
    $tpl=new template_admin();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/iptables/stats"));
    header("content-type: application/x-javascript");
    if(!is_object($json) || !isset($json->Data->Family->ROUTER) || !is_object($json->Data->Family->ROUTER)){return;}
    $html=array();
    foreach($json->Data->Family->ROUTER as $ID=>$s){
        $ID=intval($ID);
        if(empty($s->exists)){continue;}
        $pill=json_encode($tpl->_ENGINE_parse_body(brg_status_pill(true)));
        $pk=json_encode(brg_packets(intval($s->pkts ?? 0)));
        $sz=json_encode(brg_bytes(intval($s->bytes ?? 0)));
        // Only cards that are enabled are touched: a disabled card keeps its "Disabled" state.
        $html[]="(function(){var st=document.getElementById('bridge-status-$ID'); if(!st || st.querySelector('.fa-circle-pause, [data-icon=circle-pause]')){return;}".
            "st.innerHTML=$pill; var p=document.getElementById('bridge-packets-$ID'); if(p){p.textContent=$pk;} var z=document.getElementById('bridge-size-$ID'); if(z){z.textContent=$sz;}})();";
    }
    echo @implode("\n",$html);
}

// ── Enable / disable (POST /firewall/config/bridges/{id}/enable) ──────────

function enable_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["enable-js"]);
    $value=intval($_GET["value"] ?? 0)==1?1:0;
    $after=brg_after(brg_function());
    $oops=json_encode($tpl->javascript_parse_text("{operation_failed}"));
    // One POST, then the cards are redrawn: the switch shows the daemon's state, never a
    // generic "task executed" box. Disabling closes a live path, so it asks first.
    $post="var x=new XHRConnection();x.appendData('brg-enable','$ID:$value');".
        "x.sendAndLoad('$page','POST',function(o){var r=String(o.responseText||'').trim();".
        "if(r.length>3){if(r.indexOf('jserror:')===0){r=r.substring(8);}swal({title:$oops,text:r,html:true,type:'error'});}".
        "$after});";
    header("content-type: application/x-javascript");
    if($value==1){echo "(function(){ $post })();";return;}
    $text=json_encode(str_replace("[br]","<br>",$tpl->_ENGINE_parse_body("{brg_disable_confirm}")));
    $title=json_encode($tpl->javascript_parse_text("{are_you_sure}"));
    $yes=json_encode($tpl->javascript_parse_text("{brg_disable}"));
    $no=json_encode($tpl->javascript_parse_text("{brg_cancel}"));
    echo "swal({title:$title,text:$text,type:'warning',html:true,showCancelButton:true,confirmButtonColor:'#DD6B55',".
        "confirmButtonText:$yes,cancelButtonText:$no,closeOnConfirm:true},function(ok){ if(!ok){ $after return; } $post });";
}

function enable_save(){
    $tpl=new template_admin();
    $p=explode(":",(string)$_POST["brg-enable"]);
    $ID=intval($p[0]);
    $value=(isset($p[1]) && intval($p[1])==1)?1:0;
    if($ID<=0){echo $tpl->post_error("{fw_rule_not_found}");return;}
    $e=fwapi_error(fwapi_send("POST","bridges/$ID/enable",array("enabled"=>$value)));
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks(($value==1?"Enable":"Disable")." network connector id:$ID");
}

// ── Connector modal ───────────────────────────────────────────────────────

function rule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid-js"]);
    $function=brg_function();
    if($ruleid==0){
        $title="{new_connector}";
    }else{
        $b=brg_get($ruleid);
        $title="{connector} N.$ruleid ".htmlspecialchars((string)($b["nic_from"] ?? ""))." &raquo; ".htmlspecialchars((string)($b["nic_to"] ?? ""));
    }
    $tpl->js_dialog($title,"$page?rule-popup=$ruleid&function=$function");
}

function rule_settings(){
    $tpl=new template_admin();
    $function=brg_function();
    $ID=intval($_GET["rule-popup"]);
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    $b=($ID>0)?brg_get($ID):null;
    if(!is_array($b)){$b=array("enabled"=>1,"masquerading"=>0,"policy"=>0,"NoFirewall"=>0,"DenyDHCP"=>0,"jlog"=>0,"rulename"=>"","nic_from"=>"","nic_to"=>"");}

    $BEHA=array(0=>"{finally_allow_all}",1=>"{finally_deny_all}");
    $but=($ID>0)?"{apply}":"{add}";

    $tpl->field_hidden("ID",$ID);
    $form=array();
    $form[]=$tpl->field_checkbox("enabled","{enabled}",intval($b["enabled"] ?? 1),false,"{brg_f_enabled_help}");
    $form[]=$tpl->field_text("rulename","{rulename}",(string)($b["rulename"] ?? ""),false,"{brg_f_rulename_help}");
    $form[]=$tpl->field_interfaces("nic_from","{packets_from}",(string)($b["nic_from"] ?? ""),"{brg_f_from_help}");
    $form[]=$tpl->field_interfaces("nic_to","{should_be_forwarded_to}",(string)($b["nic_to"] ?? ""),"{brg_f_to_help}");
    $form[]=$tpl->field_checkbox("masquerading","{masquerading}",intval($b["masquerading"] ?? 0),false,"{brg_f_masq_help}");
    if($FireHolEnable==1){
        $form[]=$tpl->field_array_hash($BEHA,"policy","{brg_policy}",intval($b["policy"] ?? 0),false,"{brg_f_policy_help}");
        $form[]=$tpl->field_checkbox("NoFirewall","{join_interfaces_only}",intval($b["NoFirewall"] ?? 0),false,"{brg_f_join_help}");
        $form[]=$tpl->field_checkbox("DenyDHCP","{deny_dhcp_requests}",intval($b["DenyDHCP"] ?? 0),false,"{brg_f_dhcp_help}");
        $form[]=$tpl->field_checkbox("jlog","{log_all_events}",intval($b["jlog"] ?? 0),false,"{brg_f_log_help}");
    }else{
        // Firewall service off: the policy options are kept as they are, never reset.
        $tpl->field_hidden("policy",intval($b["policy"] ?? 0));
        $tpl->field_hidden("NoFirewall",intval($b["NoFirewall"] ?? 0));
        $tpl->field_hidden("DenyDHCP",intval($b["DenyDHCP"] ?? 0));
        $tpl->field_hidden("jlog",intval($b["jlog"] ?? 0));
    }
    $close=($ID==0)?"BootstrapDialog1.close();":"";
    $html=array();
    $html[]=$tpl->div_explain("{connector}||{brg_connector_explain}");
    $html[]=$tpl->form_outside("",$form,null,$but,brg_after($function).$close,"AsFirewallManager");
    echo $tpl->_ENGINE_parse_body($html);
}

function rule_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $data=array(
        "nic_from"=>(string)$_POST["nic_from"],
        "nic_to"=>(string)$_POST["nic_to"],
        "policy"=>intval($_POST["policy"] ?? 0),
        "NoFirewall"=>intval($_POST["NoFirewall"] ?? 0),
        "jlog"=>intval($_POST["jlog"] ?? 0),
        "masquerading"=>intval($_POST["masquerading"] ?? 0),
        "enabled"=>intval($_POST["enabled"] ?? 0),
        "DenyDHCP"=>intval($_POST["DenyDHCP"] ?? 0),
        "rulename"=>(string)($_POST["rulename"] ?? ""),
    );
    if($ID==0){
        $e=fwapi_error(fwapi_send("POST","bridges",$data));
    }else{
        $e=fwapi_error(fwapi_send("PUT","bridges/$ID",$data));
    }
    if($e!=""){echo $tpl->post_error($e);return false;}
    return admin_tracks(($ID==0?"Create new":"Update")." Firewall bridge {$data["nic_from"]} to {$data["nic_to"]}");
}

// ── Masquerade-only modal ─────────────────────────────────────────────────

function masq_id_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["masq-id"]);
    $function=brg_function();
    if($ruleid==0){
        $title="{brg_new_masq}";
    }else{
        $b=brg_get($ruleid);
        $title="{brg_kind_masq} N.$ruleid ".htmlspecialchars((string)($b["nic_from"] ?? ""))." &raquo; ".htmlspecialchars((string)($b["nic_to"] ?? ""));
    }
    return $tpl->js_dialog($title,"$page?masq-tab=$ruleid&function=$function");
}

function masq_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["masq-tab"]);
    $function=brg_function();
    $array=array();
    $array["{rule}"]="$page?masq-popup=$id&function=$function";
    if($id>0){
        $array["{source_network}"]="fw.bridges.masq.sources.php?popup-main=$id&function=$function";
    }
    echo $tpl->tabs_default($array);
}

function masq_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["masq-popup"]);
    $function=brg_function();
    $b=($ID>0)?brg_get($ID):null;
    if(!is_array($b)){$b=array("enabled"=>1,"rulename"=>"Masquerade packets","nic_from"=>"","nic_to"=>"");}
    $tpl->field_hidden("masqid",$ID);
    $form=array();
    $form[]=$tpl->field_checkbox("enabled","{enabled}",intval($b["enabled"] ?? 1),false,"{brg_f_enabled_help}");
    $form[]=$tpl->field_text("rulename","{rulename}",(string)($b["rulename"] ?? ""),false,"{brg_f_rulename_help}");
    $form[]=$tpl->field_interfaces("nic_from","{masq_from}",(string)($b["nic_from"] ?? ""),"{brg_f_masq_from_help}");
    $form[]=$tpl->field_interfaces("nic_to","{masq_to}",(string)($b["nic_to"] ?? ""),"{brg_f_masq_to_help}");
    $close=($ID==0)?"BootstrapDialog1.close();":"";
    $html=array();
    $html[]=$tpl->div_explain("{brg_kind_masq}||{brg_masq_explain}");
    $html[]=$tpl->form_outside("",$form,null,($ID>0)?"{apply}":"{add}",brg_after($function).$close,"AsFirewallManager");
    echo $tpl->_ENGINE_parse_body($html);
}

function masq_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["masqid"]);
    $data=array(
        "nic_from"=>(string)$_POST["nic_from"],
        "nic_to"=>(string)$_POST["nic_to"],
        "enabled"=>intval($_POST["enabled"] ?? 0),
        "rulename"=>(string)($_POST["rulename"] ?? ""),
        "OnlyMASQ"=>1,
    );
    if($ID==0){
        $e=fwapi_error(fwapi_send("POST","bridges",$data));
    }else{
        $e=fwapi_error(fwapi_send("PUT","bridges/$ID",$data));
    }
    if($e!=""){echo $tpl->post_error($e);return false;}
    return admin_tracks(($ID==0?"Create new":"Update")." masquerade rule {$data["nic_from"]} to {$data["nic_to"]}");
}

// ── Delete ────────────────────────────────────────────────────────────────

function delete_js():bool{
    $ruleid=intval($_GET["delete-rule-js"]);
    $tpl=new template_admin();
    $b=brg_get($ruleid);
    $route=htmlspecialchars((string)($b["nic_from"] ?? ""))." &rarr; ".htmlspecialchars((string)($b["nic_to"] ?? ""));
    $text=str_replace("%s",$route,$tpl->_ENGINE_parse_body("{brg_delete_confirm}"));
    return $tpl->js_confirm_delete($text,"delete-remove",$ruleid,brg_after(brg_function()),true);
}

function delete_remove():bool{
    $ruleid=intval($_POST["delete-remove"]);
    $resp=fwapi_send("DELETE","bridges/$ruleid");
    $e=fwapi_error($resp);
    if($e!=""){echo (new template_admin())->post_error($e);return false;}
    // The daemon deleted the bridge, its sources and ITS rules — never global rules.
    // The object links of those rules live in acls.db.
    $data=fwapi_data($resp);
    $rules=(is_array($data) && isset($data["rules"]) && is_array($data["rules"]))?$data["rules"]:array();
    foreach($rules as $ID){
        acls_api_send("DELETE","/proxy/acls/sqlite/links/by-aclid?table=firewallfilter_sqacllinks&aclid=".intval($ID));
    }
    return admin_tracks("Delete Network router id:$ruleid and ".count($rules)." rule(s)");
}
