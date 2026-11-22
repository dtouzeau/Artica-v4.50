<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.firewall.confirm.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Verrou de la politique Artica Meta "firewall" : NAT tenu = liste en lecture seule.
include_once(dirname(__FILE__)."/ressources/class.firewall.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(firewall_meta_holds("nat")){
    if(isset($_POST["nat-enable"]) || isset($_POST["nat-delete"]) || isset($_POST["ID"])){echo firewall_meta_refuse_post();exit;}
    if(isset($_GET["delete-rule-js"])){firewall_meta_refuse_js();exit;}
}
if(isset($_POST["nat-enable"])){enable_save();exit;}
if(isset($_POST["nat-delete"])){delete_save();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
page();

// Maquette stitch_firewall_rules_nat (2026-11-11). Une entrée NAT n'est qu'une
// CIBLE : elle ne sert qu'aux règles du pare-feu dont l'« interface » vaut
// <DNAT|SNAT|RNAT|XNAT>:<id>. La page montre donc, pour chaque entrée, les
// règles qui l'utilisent ; seul le Source NAT s'applique sans règle (saut
// POSTROUTING par défaut, afirewall natExtraCommands).

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{natp_title}","fa fa-arrow-right-arrow-left","{natp_explain}","$page?table-start=yes","/nat","progress-firehol-restart",
        false,"table-loader");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ---------------------------------------------------------------- modèle

// Codes des pseudo-interfaces, index = nat_type.
function natp_codes():array{ return fwapi_nat_codes(); }

function natp_type_meta():array{
    return array(
        0=>array("name"=>"{natp_t_dnat}","short"=>"{natp_s_dnat}","desc"=>"{natp_d_dnat}","ico"=>"fa-solid fa-right-to-bracket","tone"=>"ak-ok"),
        1=>array("name"=>"{natp_t_snat}","short"=>"{natp_s_snat}","desc"=>"{natp_d_snat}","ico"=>"fa-solid fa-right-from-bracket","tone"=>"ak-info"),
        2=>array("name"=>"{natp_t_rnat}","short"=>"{natp_s_rnat}","desc"=>"{natp_d_rnat}","ico"=>"fa-solid fa-arrow-rotate-left","tone"=>"ak-violet"),
        3=>array("name"=>"{natp_t_xnat}","short"=>"{natp_s_xnat}","desc"=>"{natp_d_xnat}","ico"=>"fa-solid fa-route","tone"=>"ak-off"),
    );
}

function natp_nics():array{
    $out=array();
    $nic=new networking();
    $nicZ=$nic->Local_interfaces();
    if(!is_array($nicZ)){return $out;}
    foreach ($nicZ as $yinter=>$line){
        $znic=new system_nic($yinter);
        if($znic->Bridged==1){continue;}
        if($znic->enabled==0){continue;}
        $out[$yinter]=trim((string)$znic->NICNAME);
    }
    return $out;
}

// natp_model : les trois sources lues UNE fois (entrées NAT, règles du pare-feu,
// compteurs iptables), puis une ligne prête à afficher par entrée.
function natp_model():array{
    $m=array("ok"=>true,"error"=>"","rows"=>array(),"fw_enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"))==1,
        "by_type"=>array(0=>0,1=>0,2=>0,3=>0),"on"=>0,"off"=>0);
    $resp=fwapi_get("nat");
    $list=fwapi_data($resp);
    if(!is_array($list)){
        $m["ok"]=false;
        $m["error"]=fwapi_error($resp);
        return $m;
    }
    // Règles rattachées : eth = « DNAT:5 ».
    $attached=array();
    $rules=fwapi_data(fwapi_get("rules"));
    $m["rules_known"]=is_array($rules);
    if(is_array($rules)){
        foreach($rules as $r){
            if(!is_array($r)){continue;}
            $eth=(string)($r["eth"] ?? "");
            if(!preg_match('#^[A-Z]NAT:[0-9]+$#',$eth)){continue;}
            $name=trim(strip_tags(preg_replace('#<br>.*$#s','',(string)($r["rulename"] ?? ""))));
            $attached[$eth][]=array("id"=>intval($r["id"] ?? 0),"name"=>$name,"enabled"=>intval($r["enabled"] ?? 0)==1);
        }
    }
    // Compteurs cumulés depuis le dernier chargement des règles.
    $counters=array();
    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/rules/status"),true);
    if(is_array($st) && !empty($st["Status"]) && is_array($st["Data"]["nat"] ?? null)){
        foreach($st["Data"]["nat"] as $k=>$c){ if(is_array($c)){$counters[intval($k)]=$c;} }
    }
    $nics=natp_nics();
    $codes=natp_codes();
    foreach($list as $n){
        if(!is_array($n)){continue;}
        $id=intval($n["id"] ?? 0);
        $type=intval($n["nat_type"] ?? 0);
        if($type<0 || $type>3){$type=0;}
        $eth=$codes[$type].":".$id;
        $on=intval($n["enabled"] ?? 0)==1;
        $used=$attached[$eth] ?? array();
        $active=0;
        foreach($used as $u){ if($u["enabled"]){$active++;} }
        $problems=array();
        if(is_array($n["problems"] ?? null)){
            foreach($n["problems"] as $p){ $problems[]=trim(($p["error"] ?? "")." ".htmlspecialchars((string)($p["detail"] ?? ""))); }
        }
        $nic=(string)($n["nic"] ?? "");
        $m["rows"][]=array(
            "id"=>$id,"type"=>$type,"eth"=>$eth,"on"=>$on,
            "name"=>trim((string)($n["rulename"] ?? "")),
            "nic"=>$nic,"nicname"=>$nics[$nic] ?? "",
            "dst"=>trim((string)($n["dstaddr"] ?? "")),"dstname"=>trim((string)($n["dstaddr_name"] ?? "")),
            "port"=>intval($n["dstaddrport"] ?? 0),
            "jlog"=>intval($n["jlog"] ?? 0)==1,
            "reflection"=>intval($n["reflection"] ?? 1)==1,"reflection_addr"=>trim((string)($n["reflection_addr"] ?? "")),
            "problems"=>$problems,"used"=>$used,"active"=>$active,
            "counter"=>$counters[$id] ?? null,
        );
        $m["by_type"][$type]++;
        if($on){$m["on"]++;}else{$m["off"]++;}
    }
    usort($m["rows"],function($a,$b){ return $b["id"]-$a["id"]; });
    return $m;
}

// ---------------------------------------------------------------- page

function table_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Modification du pare-feu en attente de confirmation (fwhistory) : bandeau partagé.
    echo firewall_confirm_banner();
    echo firewall_meta_banner("nat",$tpl);
    echo natp_style()."<div class='ak natp' id='natp-host'></div>".natp_js();

    $topbuttons=array();
    if(!firewall_meta_holds("nat")){$topbuttons[]=array("Loadjs('$page?ruleid-js=0',true);", ico_plus, "{new_rule}");}
    $topbuttons[]=array("NatReload();", ico_refresh, "{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{natp_title}";
    $TINY_ARRAY["ICO"]="fa fa-arrow-right-arrow-left";
    $TINY_ARRAY["EXPL"]="{natp_explain}";
    $TINY_ARRAY["URL"]="nat";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    echo "<script>NatReload();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

// Contenu rechargeable : bandeaux, tuiles, barre d'outils, tableau, guide.
function table(){
    $tpl=new template_admin();
    $m=natp_model();
    $html=array();
    $html[]=natp_banner_disabled($m);
    if(!$m["ok"]){
        $html[]="<div class='ak-banner ak-err'><i class='fa-solid fa-circle-exclamation'></i><span class='ak-banner-m'>".htmlspecialchars($m["error"])."</span></div>";
        echo $tpl->_ENGINE_parse_body($html)."<script>NoSpinner();</script>";
        return;
    }
    $html[]=natp_notice();
    $html[]=natp_tiles($m);
    if(count($m["rows"])==0){
        $html[]=natp_empty();
    }else{
        $html[]=natp_toolbar($m);
        $html[]=natp_table($m);
    }
    $html[]=natp_guide();
    echo $tpl->_ENGINE_parse_body($html)."<script>NoSpinner();NatApply();</script>";
}

function natp_banner_disabled(array $m):string{
    if($m["fw_enabled"]){return "";}
    return "<div class='ak-banner ak-warn natp-gap'><i class='fa-solid fa-triangle-exclamation'></i><span class='ak-banner-m'>{natp_fw_disabled}</span></div>";
}

function natp_notice():string{
    return "<div class='ak-banner ak-info natp-gap natp-notice'><i class='fa-solid fa-circle-info'></i>"
        ."<span class='ak-banner-m'><strong>{natp_how_t}</strong> {natp_how}</span></div>";
}

function natp_tiles(array $m):string{
    $h=array("<div class='natp-tiles'>");
    foreach(natp_type_meta() as $t=>$meta){
        $h[]=natp_tile($t,$meta,intval($m["by_type"][$t]));
    }
    $h[]="</div>";
    return implode("",$h);
}

function natp_tile(int $t,array $meta,int $count):string{
    return "<button type='button' class='natp-tile' id='natp-tile-$t' aria-pressed='false' OnClick=\"NatType($t)\">"
        ."<span class='natp-tile-h'><span class='ak-ico ak-ico-xs ak-ico-sq {$meta["tone"]}'><i class='{$meta["ico"]}'></i></span>"
        ."<span class='natp-tile-t'>{$meta["name"]}</span><span class='ak-count'>$count</span></span>"
        ."<span class='natp-tile-d'>{$meta["desc"]}</span></button>";
}

function natp_toolbar(array $m):string{
    $all=$m["on"]+$m["off"];
    return "<div class='ak-tools natp-tools'>"
        ."<label class='ak-search natp-search'><i class='fa-solid fa-magnifying-glass'></i>"
        ."<input type='search' id='natp-q' placeholder='{natp_search}' aria-label='{natp_search}' OnInput=\"NatQuery(this.value)\"></label>"
        ."<div class='ak-seg' role='radiogroup'>"
        ."<button type='button' class='ak-seg-b' id='natp-st-all' role='radio' OnClick=\"NatStatus('all')\">{natp_all} ($all)</button>"
        ."<button type='button' class='ak-seg-b' id='natp-st-on' role='radio' OnClick=\"NatStatus('on')\">{natp_enabled} ({$m["on"]})</button>"
        ."<button type='button' class='ak-seg-b' id='natp-st-off' role='radio' OnClick=\"NatStatus('off')\">{natp_disabled} ({$m["off"]})</button>"
        ."</div><span class='ak-grow'></span>"
        ."<span class='ak-note'><i class='fa-solid fa-circle-info'></i> {natp_counters_note}</span></div>";
}

function natp_table(array $m):string{
    $h=array();
    $h[]="<div class='ak-card natp-card'><table class='ak-tbl natp-tbl'><thead><tr>";
    $h[]="<th class='natp-c-sw'><span class='sr-only'>{natp_enabled}</span></th><th>{natp_th_name}</th><th>{natp_th_type}</th>";
    $h[]="<th>{natp_th_iface}</th><th>{natp_th_target}</th><th>{natp_th_used}</th><th>{natp_th_log}</th><th class='ak-end'>{natp_th_actions}</th>";
    $h[]="</tr></thead><tbody id='natp-rows'>";
    foreach($m["rows"] as $r){ $h[]=natp_row($r); }
    $h[]="<tr id='natp-nomatch' hidden><td colspan='8'><div class='ak-empty-c'><i class='fa-solid fa-filter-circle-xmark'></i>{natp_nomatch}</div></td></tr>";
    $h[]="</tbody></table>";
    $h[]=natp_footer($m);
    $h[]="</div>";
    return implode("\n",$h);
}

function natp_footer(array $m):string{
    $meta=natp_type_meta();
    $legend=array();
    foreach($m["by_type"] as $t=>$c){
        if($c==0){continue;}
        $legend[]="<span class='natp-leg'><span class='ak-dot {$meta[$t]["tone"]}'></span>$c {$meta[$t]["short"]}</span>";
    }
    $total=count($m["rows"]);
    return "<div class='natp-foot'><span>{natp_showing} <strong id='natp-vis'>$total</strong> / <strong>$total</strong></span>"
        ."<span class='natp-legs'>".implode("",$legend)."</span></div>";
}

function natp_row(array $r):string{
    $page=CurrentPageName();
    $id=$r["id"];
    $q=strtolower(implode(" ",array($id,"#$id",$r["name"],$r["dst"],$r["dstname"],$r["port"]>0?$r["port"]:"",$r["nic"],$r["nicname"])));
    $cls=$r["on"]?"":" natp-off";
    if($r["on"] && natp_unapplied($r)){$cls.=" natp-warnrow";}
    return "<tr class='natp-r$cls' data-type='{$r["type"]}' data-on='".($r["on"]?1:0)."' data-q=\"".htmlspecialchars($q)."\">"
        ."<td class='natp-c-sw'>".natp_switch($r)."</td>"
        ."<td>".natp_cell_name($r)."</td>"
        ."<td class='ak-nowrap'>".natp_pill($r["type"])."</td>"
        ."<td class='ak-nowrap'>".natp_cell_iface($r)."</td>"
        ."<td>".natp_cell_target($r)."</td>"
        ."<td>".natp_cell_used($r)."</td>"
        ."<td class='ak-nowrap'>".natp_cell_log($r)."</td>"
        ."<td class='ak-end'><div class='ak-acts'>"
        ."<button type='button' class='ak-act' aria-label='{natp_edit}' title='{natp_edit}' OnClick=\"Loadjs('$page?ruleid-js=$id',true)\"><i class='fa-solid fa-".(firewall_meta_holds("nat")?"eye":"pen")."'></i></button>"
        .(firewall_meta_holds("nat")?"":"<button type='button' class='ak-act ak-danger' aria-label='{natp_delete}' title='{natp_delete}' OnClick=\"Loadjs('$page?delete-rule-js=$id')\"><i class='fa-solid fa-trash-can'></i></button>")
        ."</div></td></tr>";
}

// Une entrée qui n'a aucune règle ACTIVE ne pose rien (sauf le Source NAT).
function natp_unapplied(array $r):bool{
    if($r["type"]==1 && count($r["used"])==0){return false;}
    return $r["active"]==0;
}

function natp_switch(array $r):string{
    $on=$r["on"]?"true":"false";
    $lbl=$r["on"]?"{natp_enabled}":"{natp_disabled}";
    // NAT tenu par Artica Meta : interrupteur affiche, sans action.
    if(firewall_meta_holds("nat")){return "<button type='button' class='ak-sw' role='switch' aria-checked='$on' aria-label='$lbl' title='$lbl' disabled></button>";}
    return "<button type='button' class='ak-sw' role='switch' aria-checked='$on' aria-label='$lbl' title='$lbl' OnClick=\"NatToggle({$r["id"]},this)\"></button>";
}

function natp_cell_name(array $r):string{
    $page=CurrentPageName();
    $name=$r["name"]!=""?htmlspecialchars($r["name"]):"<span class='natp-noname'>".natp_target_text($r)."</span>";
    $h="<span class='natp-name'><span class='natp-id'>#{$r["id"]}</span>"
        ."<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?ruleid-js={$r["id"]}',true)\">$name</a></span>";
    foreach($r["problems"] as $p){
        $h.="<span class='natp-prob'><i class='fa-solid fa-triangle-exclamation'></i> $p</span>";
    }
    return $h;
}

function natp_pill(int $type):string{
    $meta=natp_type_meta();
    $x=$meta[$type];
    // Nom court dans le tableau (les tuiles donnent le nom complet), nom complet en infobulle.
    return "<span class='ak-pill {$x["tone"]} natp-pill' title=\"{$x["name"]}\"><i class='{$x["ico"]}'></i> {$x["short"]}</span>";
}

function natp_cell_iface(array $r):string{
    if($r["nic"]==""){return "<span class='ak-tag ak-off'>{all_interfaces}</span>";}
    $nic=htmlspecialchars($r["nic"]);
    if($r["nicname"]=="" || $r["nicname"]==$r["nic"]){return "<span class='ak-chip'>$nic</span>";}
    return "<span class='natp-iface'>".htmlspecialchars($r["nicname"])." <span class='ak-mono ak-muted'>($nic)</span></span>";
}

// Adresse visée : nom saisi (résolu en IP par le daemon) puis l'IP.
function natp_addr(array $r):string{
    $ip=htmlspecialchars($r["dst"]);
    if($r["dstname"]!="" && $r["dstname"]!=$r["dst"]){
        return "<strong>".htmlspecialchars($r["dstname"])."</strong> <span class='ak-muted natp-nw'>($ip)</span>";
    }
    return $ip==""?"<span class='ak-muted'>—</span>":"<strong class='natp-nw'>$ip</strong>";
}

// Traduction lisible, différente selon le type.
function natp_target_text(array $r):string{
    $port=$r["port"]>0?"<span class='natp-port natp-nw'>:{$r["port"]}</span>":"";
    switch($r["type"]){
        case 1: return "{natp_src_becomes} ".natp_addr($r).$port;
        case 2: return "&rarr; {natp_local_port} ".($r["port"]>0?"<strong>{$r["port"]}</strong>":"<span class='ak-muted'>{natp_keep_port}</span>");
        case 3: return "{natp_via_gw} ".natp_addr($r);
    }
    return "&rarr; ".natp_addr($r).$port;
}

function natp_cell_target(array $r):string{
    $h="<span class='natp-target ak-mono'>".natp_target_text($r)."</span>";
    if($r["type"]==0 && $r["reflection"]){
        $addr=$r["reflection_addr"]!=""?" &middot; <span class='ak-mono'>".htmlspecialchars($r["reflection_addr"])."</span>":"";
        $h.="<span class='ak-tag ak-ok natp-refl'><i class='fa-solid fa-arrows-rotate'></i> {fw_nat_reflection}$addr</span>";
    }
    return $h;
}

function natp_cell_used(array $r):string{
    $n=count($r["used"]);
    $h="";
    if($n==0){
        if($r["type"]==1){
            $h="<span class='ak-tag ak-off'><i class='fa-solid fa-globe'></i> {natp_snat_all}</span>";
        }else{
            $h="<span class='ak-tag ak-warn'><i class='fa-solid fa-link-slash'></i> {natp_unused}</span>";
        }
    }else{
        // Traduire avant de remplacer %s : sinon le token ne correspond plus.
        $label=$n==1?"{natp_rule_1}":str_replace("%s",$n,(new template_admin())->_ENGINE_parse_body("{natp_rules_n}"));
        $h="<button type='button' class='ak-link natp-usedby' aria-expanded='false' aria-controls='natp-pop-{$r["id"]}' OnClick=\"NatPop({$r["id"]},this,event)\">$label <i class='fa-solid fa-chevron-down'></i></button>";
        if($r["active"]==0){
            $h.="<span class='ak-tag ak-warn natp-sub'><i class='fa-solid fa-link-slash'></i> {natp_rules_off}</span>";
        }
        $h.=natp_popover($r);
    }
    $h.=natp_traffic($r);
    return "<div class='natp-used'>$h</div>";
}

function natp_popover(array $r):string{
    $li=array();
    foreach($r["used"] as $u){
        $name=$u["name"]!=""?htmlspecialchars($u["name"]):"#{$u["id"]}";
        $off=$u["enabled"]?"":" <span class='ak-tag ak-off'>{natp_rule_disabled}</span>";
        $li[]="<li><a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.rules.php?ruleid-js={$u["id"]}')\"><span class='natp-id'>#{$u["id"]}</span> $name</a>$off</li>";
    }
    return "<div class='natp-pop' id='natp-pop-{$r["id"]}' hidden><div class='natp-pop-t'>{natp_popover_t}</div>"
        ."<ul>".implode("",$li)."</ul>"
        ."<a class='ak-link' href='/firewall'>{natp_open_rules} <i class='fa-solid fa-arrow-right'></i></a></div>";
}

// Compteurs cumulés : affichés seulement quand le daemon en a (sinon rien,
// jamais un 0 qui prétendrait une mesure).
function natp_traffic(array $r):string{
    if(!$r["on"] || !is_array($r["counter"])){return "";}
    $p=floatval($r["counter"]["pkts"] ?? 0);
    $b=floatval($r["counter"]["bytes"] ?? 0);
    if($p<1000){$pk=number_format($p,0);}elseif($p<1000000){$pk=sprintf("%.1f k",$p/1000);}else{$pk=sprintf("%.1f M",$p/1000000);}
    return "<span class='ak-note natp-traffic'>$pk {natp_pkts} &middot; ".FormatBytes($b/1024,true)."</span>";
}

function natp_cell_log(array $r):string{
    if(!$r["jlog"]){return "<span class='ak-muted' aria-label='{natp_not_logged}'>—</span>";}
    return "<span class='ak-tag ak-off'><i class='fa-solid fa-file-lines'></i> {natp_logged}</span>";
}

function natp_empty():string{
    $page=CurrentPageName();
    return "<div class='ak-card'><div class='ak-empty-c natp-empty'><i class='fa-solid fa-arrow-right-arrow-left'></i>"
        ."<strong>{natp_empty_t}</strong><span>{natp_empty}</span>"
        .(firewall_meta_holds("nat")?"":"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?ruleid-js=0',true)\"><i class='fa-solid fa-plus'></i> {new_rule}</button>")
        ."</div></div>";
}

function natp_guide():string{
    $steps=array(
        array("{natp_step1_t}","{natp_step1}"),
        array("{natp_step2_t}","{natp_step2}"),
        array("{natp_step3_t}","{natp_step3}"),
    );
    $h=array();
    $h[]="<details class='ak-card ak-details natp-guide' id='natp-guide' OnToggle=\"NatGuide(this)\"><summary>";
    $h[]="<span class='ak-ico ak-ico-xs ak-ico-sq ak-ok'><i class='fa-solid fa-graduation-cap'></i></span><span>{natp_guide_t}</span>";
    $h[]="<i class='fa-solid fa-chevron-down ak-chev'></i></summary><div class='natp-steps'>";
    foreach($steps as $i=>$s){
        $n=$i+1;
        $h[]="<div class='natp-step'><div class='natp-step-h'><span class='natp-step-n'>$n</span><strong>{$s[0]}</strong></div><p>{$s[1]}</p></div>";
    }
    $h[]="</div><div class='natp-guide-f'><a class='ak-link' href='/firewall'>{natp_open_rules} <i class='fa-solid fa-arrow-right'></i></a></div></details>";
    return implode("",$h);
}

// ---------------------------------------------------------------- CSS / JS

function natp_style():string{
    if(isset($GLOBALS["NATP_STYLE"])){return "";}
    $GLOBALS["NATP_STYLE"]=true;
    $c=array();
    $c[]=".natp{margin-top:10px}.natp-gap{margin-bottom:16px}";
    $c[]=".natp-notice{align-items:flex-start;line-height:1.5}.natp-notice>svg,.natp-notice>i{margin-top:3px}";
    $c[]=".natp-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}";
    $c[]=".natp-tile{display:flex;flex-direction:column;gap:8px;text-align:left;padding:14px 16px;border:1px solid var(--ak-border);border-radius:12px;background:#fff;cursor:pointer;font:inherit;color:inherit;transition:border-color .15s,box-shadow .15s}";
    $c[]=".natp-tile:hover{border-color:var(--ak-line)}.natp-tile[aria-pressed=true]{border-color:var(--ak-fill);box-shadow:0 0 0 1px var(--ak-fill)}";
    $c[]=".natp-tile:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}";
    $c[]=".natp-tile-h{display:flex;align-items:center;gap:10px}.natp-tile-t{flex:1 1 auto;font-size:15px;font-weight:600}";
    $c[]=".natp-tile-d{font-size:13px;color:var(--ak-muted);line-height:1.45}";
    $c[]=".natp-tools .ak-search{width:360px;max-width:100%}.natp-tools .ak-note{display:inline-flex;align-items:center;gap:6px}";
    $c[]=".natp-card{overflow:visible}.natp-tbl th,.natp-tbl td{padding-left:12px;padding-right:12px}.natp-tbl td{vertical-align:top;padding-top:14px;padding-bottom:14px}";
    $c[]=".natp-tbl .ak-tag,.natp-tbl .ak-pill{white-space:normal}.natp-tbl td:first-child,.natp-tbl th:first-child{padding-left:18px}.natp-tbl td:last-child,.natp-tbl th:last-child{padding-right:18px}";
    $c[]=".natp-c-sw{width:40px}.natp-tbl .ak-acts{padding-top:0}";
    $c[]=".natp-name{display:flex;align-items:baseline;gap:8px;font-size:14px;font-weight:600}.natp-name a{color:var(--ak-text);text-decoration:none}.natp-name a:hover{color:var(--ak-fill);text-decoration:underline}";
    $c[]=".natp-id{font-size:13px;font-weight:500;color:var(--ak-muted)}.natp-noname{font-weight:500}";
    $c[]=".natp-prob{display:flex;align-items:flex-start;gap:6px;margin-top:6px;font-size:13px;color:#865300;line-height:1.4}";
    $c[]=".natp-target{display:block}.natp-nw{white-space:nowrap}.natp-tbl .natp-pill{white-space:nowrap}.natp-port{color:var(--ak-muted)}.natp-refl{margin-top:6px}";
    $c[]=".natp-used{position:relative;display:flex;flex-direction:column;align-items:flex-start;gap:4px}.natp-usedby{padding-left:0}.natp-sub{margin-top:2px}";
    $c[]=".natp-traffic{white-space:nowrap}";
    $c[]=".natp-pop{position:absolute;z-index:25;top:30px;left:0;width:300px;padding:12px;border:1px solid var(--ak-border);border-radius:10px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.14)}";
    $c[]=".natp-pop-t{font-size:13px;font-weight:600;color:var(--ak-text2);margin-bottom:6px}.natp-pop ul{list-style:none;margin:0 0 8px;padding:0}";
    $c[]=".natp-pop li{display:flex;align-items:center;gap:6px;padding:5px 0;border-bottom:1px solid var(--ak-border2);font-size:13px}.natp-pop li a{color:var(--ak-text);text-decoration:none}.natp-pop li a:hover{color:var(--ak-fill);text-decoration:underline}";
    $c[]=".natp-off td{color:var(--ak-muted)}.natp-off .natp-name a{color:var(--ak-muted)}.natp-off .ak-pill,.natp-off .natp-refl{filter:grayscale(1);opacity:.75}";
    $c[]=".natp-warnrow td{background:#fffbf3}";
    $c[]=".natp-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:12px 18px;border-top:1px solid var(--ak-border);color:var(--ak-text2);font-size:13px}";
    $c[]=".natp-legs{display:flex;gap:16px;flex-wrap:wrap}.natp-leg{display:inline-flex;align-items:center;gap:6px}";
    $c[]=".natp-empty strong{font-size:15px;color:var(--ak-text)}";
    $c[]=".natp-guide{margin-top:16px}.natp-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;padding:16px 18px 4px}";
    $c[]=".natp-step-h{display:flex;align-items:center;gap:10px;margin-bottom:6px}.natp-step p{margin:0;font-size:13px;color:var(--ak-text2);line-height:1.5}";
    $c[]=".natp-step-n{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:var(--ak-soft);color:var(--ak-fill);font-weight:700;font-size:13px}";
    $c[]=".natp-guide-f{padding:8px 12px 14px}";
    $c[]="@media(max-width:1200px){.natp-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.natp-steps{grid-template-columns:1fr}}";
    $c[]="@media(prefers-reduced-motion:reduce){.natp-tile{transition:none}}";
    return "<style>".implode("\n",$c)."</style>";
}

// État des filtres dans window : il survit au rechargement du contenu.
function natp_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $err=$tpl->javascript_parse_text("{error}");
    return "<script>
window.NatF=window.NatF||{q:'',st:'all',type:-1};
function NatReload(){ LoadAjaxSilent('natp-host','$page?table=yes'); }
function NatReloadSafe(){ NatReload(); }
function NatQuery(v){ NatF.q=String(v||'').toLowerCase().trim(); NatApply(); }
function NatStatus(s){ NatF.st=s; NatApply(); }
function NatType(t){ NatF.type=(NatF.type===t)?-1:t; NatApply(); }
function NatApply(){
  var q=document.getElementById('natp-q'); if(q && q.value!==NatF.q && document.activeElement!==q){ q.value=NatF.q; }
  var st={all:'natp-st-all',on:'natp-st-on',off:'natp-st-off'};
  for(var k in st){ var b=document.getElementById(st[k]); if(b){ var on=(k===NatF.st); b.className='ak-seg-b'+(on?' on':''); b.setAttribute('aria-checked',on?'true':'false'); } }
  for(var t=0;t<4;t++){ var e=document.getElementById('natp-tile-'+t); if(e){ e.setAttribute('aria-pressed',NatF.type===t?'true':'false'); } }
  var tb=document.getElementById('natp-rows'); if(!tb){return;}
  var rows=tb.rows, vis=0;
  for(var i=0;i<rows.length;i++){
    var r=rows[i]; if(!r.hasAttribute('data-type')){continue;}
    var ok=true;
    if(NatF.type>=0 && parseInt(r.getAttribute('data-type'),10)!==NatF.type){ok=false;}
    if(NatF.st==='on' && r.getAttribute('data-on')!=='1'){ok=false;}
    if(NatF.st==='off' && r.getAttribute('data-on')!=='0'){ok=false;}
    if(NatF.q!=='' && r.getAttribute('data-q').indexOf(NatF.q)<0){ok=false;}
    r.style.display=ok?'':'none'; if(ok){vis++;}
  }
  var nm=document.getElementById('natp-nomatch'); if(nm){ nm.hidden=(vis>0); }
  var vc=document.getElementById('natp-vis'); if(vc){ vc.textContent=vis; }
  var g=document.getElementById('natp-guide'); if(g && NatF.guide){ g.open=true; }
}
function NatGuide(d){ NatF.guide=d.open; }
function NatPop(id,btn,ev){
  if(ev){ev.stopPropagation();}
  var p=document.getElementById('natp-pop-'+id); if(!p){return;}
  var open=p.hidden;
  NatPopClose();
  if(open){ p.hidden=false; btn.setAttribute('aria-expanded','true'); }
}
function NatPopClose(){
  var ps=document.querySelectorAll('.natp-pop'); for(var i=0;i<ps.length;i++){ ps[i].hidden=true; }
  var bs=document.querySelectorAll('.natp-usedby'); for(var j=0;j<bs.length;j++){ bs[j].setAttribute('aria-expanded','false'); }
}
if(!window.NatPopBound){
  window.NatPopBound=true;
  document.addEventListener('click',function(e){ if(!e.target.closest || !e.target.closest('.natp-pop')){ NatPopClose(); } });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ NatPopClose(); } });
}
function NatToggle(id,btn){
  var v=btn.getAttribute('aria-checked')==='true'?0:1;
  btn.setAttribute('aria-busy','true');
  $.post('$page',{'nat-enable':id,'value':v}).done(function(resp){
    var t=String(resp||'').trim();
    if(t.length>3){ swal({title:'$err',text:t.indexOf('jserror:')===0?t.substring(8):t,html:true,type:'error'}); }
    NatReload();
  }).fail(function(){ NatReload(); });
}
</script>";
}

// ---------------------------------------------------------------- actions

function enable_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["nat-enable"]);
    $v=intval($_POST["value"] ?? 0)==1?1:0;
    $e=fwapi_error(fwapi_send("PUT","nat/$ID",array("enabled"=>$v)));
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall NAT rule #$ID ".($v==1?"enabled":"disabled"));
}

function rule_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ruleid=intval($_GET["ruleid-js"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $title="{new_rule}";
    if($ruleid>0){
        $n=fwapi_data(fwapi_get("nat/$ruleid"));
        $meta=natp_type_meta();
        $type=is_array($n)?intval($n["nat_type"] ?? 0):0;
        if($type<0 || $type>3){$type=0;}
        $name=is_array($n)?trim((string)($n["rulename"] ?? "")):"";
        $title="#$ruleid ".($name!=""?$tpl->javascript_parse_text($name)." - ":"").$meta[$type]["name"];
    }
    $tpl->js_dialog2($title,"$page?rule-popup=$ruleid&function=$function",700);
}

function delete_js(){
    $ID=intval($_GET["delete-rule-js"]);
    $tpl=new template_admin();
    $n=fwapi_data(fwapi_get("nat/$ID"));
    if(!is_array($n)){$tpl->js_error("{fw_rule_not_found}");return false;}
    $codes=natp_codes();
    $type=intval($n["nat_type"] ?? 0);
    if($type<0 || $type>3){$type=0;}
    $eth=$codes[$type].":$ID";
    // Le daemon refuserait (409) : on le dit avant de demander confirmation.
    $names=array();
    $rules=fwapi_data(fwapi_get("rules"));
    if(is_array($rules)){
        foreach($rules as $r){
            if(is_array($r) && (string)($r["eth"] ?? "")===$eth){
                $names[]="#".intval($r["id"] ?? 0)." ".trim(strip_tags(preg_replace('#<br>.*$#s','',(string)($r["rulename"] ?? ""))));
            }
        }
    }
    if(count($names)>0){
        $tpl->js_error("{natp_in_use}\n".implode("\n",$names));
        return false;
    }
    $name=trim((string)($n["rulename"] ?? ""));
    if($name==""){$name="#$ID";}
    $tpl->js_confirm_delete(htmlspecialchars($name),"nat-delete",$ID,"NatReloadSafe();");
    return true;
}

function delete_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["nat-delete"]);
    // Le daemon refuse (409) tant que des règles sont rattachées, et les nomme.
    $e=fwapi_error(fwapi_send("DELETE","nat/$ID"));
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall NAT rule #$ID deleted");
}

function rule_settings(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-popup"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $n=array("enabled"=>1,"rulename"=>"","nat_type"=>0,"nic"=>"","dstaddr"=>"","dstaddr_name"=>"","dstaddrport"=>0,"jlog"=>0,"reflection"=>1,"reflection_addr"=>"");
    $btname="{add}";
    // NAT tenu par Artica Meta : fiche en lecture seule, pas de creation.
    $metaBanner=firewall_meta_banner("nat",$tpl);
    if($metaBanner!==""){
        if($ID==0){echo $metaBanner;return;}
        firewall_meta_lock_form($tpl);
    }
    if($ID>0){
        $d=fwapi_data(fwapi_get("nat/$ID"));
        if(!is_array($d)){echo $tpl->div_error("{fw_rule_not_found}");return;}
        $n=array_merge($n,$d);
        $btname="{apply}";
    }
    $types=array();
    foreach(natp_type_meta() as $t=>$meta){$types[$t]=$tpl->javascript_parse_text($meta["name"]);}
    $NICS=array();
    foreach(natp_nics() as $eth=>$name){$NICS[$eth]=$name!=""?"$name ($eth)":$eth;}
    $nic=(string)$n["nic"];
    if($nic!="" && !isset($NICS[$nic])){$NICS[$nic]=$nic;}

    $tpl->field_hidden("ID", $ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",intval($n["enabled"]),true);
    $form[]=$tpl->field_text("rulename","{rulename}",(string)$n["rulename"],false);
    $form[]=$tpl->field_array_hash($types,"NAT_TYPE","{natp_th_type}",intval($n["nat_type"]),true);
    $form[]=$tpl->field_array_hash($NICS,"nic","{interface}",$nic,false);
    // Le nom d'hôte saisi est réaffiché plutôt que l'IP résolue : réenregistrer
    // la règle le fait résoudre de nouveau par le daemon.
    $dst=!empty($n["dstaddr_name"])?$n["dstaddr_name"]:$n["dstaddr"];
    $form[]=$tpl->field_text("dstaddr","{destination_address}",(string)$dst,false,"{natp_host_help}");
    $form[]=$tpl->field_numeric("dstaddrport","{destination_port}",intval($n["dstaddrport"]));
    $form[]=$tpl->field_checkbox("jlog","{log_all_events}",intval($n["jlog"]));
    $form[]=$tpl->field_checkbox("reflection","{fw_nat_reflection}",intval($n["reflection"]),false,"{fw_nat_reflection_explain}");
    $form[]=$tpl->field_text("reflection_addr","{fw_nat_reflection_addr}",(string)$n["reflection_addr"]);

    $after="dialogInstance2.close();if(typeof NatReloadSafe==='function'){NatReloadSafe();}";
    if($function!=""){$after.="if(typeof $function==='function'){{$function}();}";}
    $html[]=$metaBanner;
    $html[]=$tpl->div_explain("{natp_form_t}||{natp_form_explain}");
    $html[]="<div id='natp-form'>".$tpl->form_outside("",@implode("\n",$form),null,$btname,$after,"AsFirewallManager")."</div>";
    echo $tpl->_ENGINE_parse_body($html).natp_form_js($tpl);
}

// Libellés et champs qui changent avec le type (adresse traduite, passerelle,
// port local ; réflexion pour le Destination NAT seulement).
function natp_form_js($tpl):string{
    $sfx=$tpl->suffixid;
    $ids=array();
    foreach(array("NAT_TYPE","dstaddr","dstaddrport","reflection","reflection_addr") as $f){$ids[$f]=md5("$f$sfx");}
    $L=array(
        "dst"=>$tpl->javascript_parse_text("{destination_address}"),
        "snat"=>$tpl->javascript_parse_text("{natp_lbl_snat}"),
        "gw"=>$tpl->javascript_parse_text("{natp_lbl_gw}"),
        "port"=>$tpl->javascript_parse_text("{destination_port}"),
        "lport"=>$tpl->javascript_parse_text("{natp_lbl_lport}"),
        "sport"=>$tpl->javascript_parse_text("{natp_lbl_sport}"),
    );
    $L=json_encode($L);
    return "<script>
(function(){
 var L=$L;
 function row(id){ var e=document.getElementById(id); if(!e){ return null; } return e.closest?e.closest('tr'):null; }
 function show(id,on){ var r=row(id); if(!r){return;} r.style.display=on?'':'none';
   var nx=r.nextElementSibling; if(nx && nx.className.indexOf('afrm-hrow')>=0){ nx.style.display=on?'':'none'; } }
 function lbl(id,txt){ var r=row(id); if(!r){return;} var l=r.querySelector('.afrm-lbl')||r.querySelector('label'); if(!l){return;}
   var w=document.createTreeWalker(l,4,null,false),n; while((n=w.nextNode())){ if(n.nodeValue.trim()!==''){ n.nodeValue=txt; return; } } }
 function apply(v){
   v=parseInt(v,10)||0;
   lbl('{$ids["dstaddr"]}', v===1?L.snat:(v===3?L.gw:L.dst));
   show('{$ids["dstaddr"]}', v!==2);
   show('{$ids["dstaddrport"]}', v!==3);
   lbl('{$ids["dstaddrport"]}', v===2?L.lport:(v===1?L.sport:L.port));
   show('{$ids["reflection"]}', v===0);
   show('{$ids["reflection_addr"]}', v===0);
 }
 var s=document.getElementById('{$ids["NAT_TYPE"]}');
 if(!s){return;}
 $(s).on('change',function(){ apply(this.value); });
 apply(s.value);
})();
</script>";
}

function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    // Champs présents seulement : en mise à jour, le daemon garde les autres.
    $data=array();
    foreach(array("rulename","nic","dstaddr","srcaddr") as $k){
        if(isset($_POST[$k])){$data[$k]=trim((string)$_POST[$k]);}
    }
    foreach(array("dstaddrport","dstport","jlog","enabled","reflection") as $k){
        if(isset($_POST[$k])){$data[$k]=intval($_POST[$k]);}
    }
    if(isset($_POST["reflection_addr"])){$data["reflection_addr"]=trim((string)$_POST["reflection_addr"]);}
    if(isset($_POST["NAT_TYPE"])){$data["nat_type"]=intval($_POST["NAT_TYPE"]);}
    $resp=$ID==0?fwapi_send("POST","nat",$data):fwapi_send("PUT","nat/$ID",$data);
    $e=fwapi_error($resp);
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Firewall NAT rule ".($ID==0?"created":"#$ID updated")." ".($data["nic"] ?? "")." ".($data["dstaddr"] ?? ""));
}
