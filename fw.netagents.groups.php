<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Network Agents Groups Management Page
// CRUD groups, manage group membership, group-based actions (upgrade, certs, packages)
//
// Entry points:
//   default (no params)                → page shell with groups table
//   ?gpid={id}                         → group detail dialog (called from fw.netagents.list.php)
//   ?groups-table=yes                  → groups list (AJAX)
//   ?group-agents-table={id}           → agents in group table (AJAX inside dialog)
//   ?group-actions={id}                → action results panel (AJAX)
//
// REST API:
//   GET  /netagents/groups             → list all groups
//   POST /netagents/groups             → create group (name, description)
//   GET  /netagents/groups/{id}        → get group with agents
//   POST /netagents/groups/{id}        → update group
//   GET  /netagents/groups/delete/{id} → delete group
//   POST /netagents/groups/{id}/agents/{agent_id}         → add agent
//   GET  /netagents/groups/{id}/agents/delete/{agent_id}  → remove agent

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");
// Liste noire d'un groupe ("Deny from updates") : infogerant seulement (routes global-only).
natn_provider_gate(array("nagr-deny-js","nagr-deny-on","nagr-deny-off"));

// ── POST handlers ── (droit vérifié côté serveur : AsArticaMetaAdmin)
if(isset($_POST["group_save"])){if(nagr_refuse("post")){exit;}group_save();exit;}
if(isset($_POST["group-edit-save"])){if(nagr_refuse("post")){exit;}group_edit_save();exit;}
if(isset($_POST["group-delete"])){if(nagr_refuse("post")){exit;}group_delete();exit;}
if(isset($_POST["nagr-deny-on"])){nagr_deny_save(intval($_POST["nagr-deny-on"]),true);exit;}
if(isset($_POST["nagr-unlink"])){nagr_unlink_save();exit;}
if(isset($_POST["nagm-add"])){nagm_add_save();exit;}
if(isset($_POST["nagr-deny-off"])){nagr_deny_save(intval($_POST["nagr-deny-off"]),false);exit;}

// ── GET handlers ──
if(isset($_GET["nagr-deny-js"])){nagr_deny_js();exit;}
if(isset($_GET["groups-start"])){group_start();exit;}
if(isset($_GET["nagr-unlink-js"])){nagr_unlink_js();exit;}
if(isset($_GET["group-delete-js"])){group_delete_js();exit;}
if(isset($_GET["group-add-js"])){group_add_js();exit;}
if(isset($_GET["group-add-popup"])){group_add_popup();exit;}
if(isset($_GET["group-edit-js"])){group_edit_js();exit;}
if(isset($_GET["group-edit-popup"])){group_edit_popup();exit;}
if(isset($_GET["group-add-agent-js"])){group_add_agent_js();exit;}
if(isset($_GET["group-add-agent-popup"])){group_add_agent_popup();exit;}
if(isset($_GET["group-renew-certs"])){if(nagr_refuse("html")){exit;}group_renew_certs();exit;}
if(isset($_GET["group-upgrade-js"])){group_upgrade_js();exit;}
if(isset($_GET["group-upgrade-popup"])){group_upgrade_popup();exit;}
if(isset($_GET["group-upgrade-api"])){if(nagr_refuse("html")){exit;}group_upgrade_api();exit;}
if(isset($_GET["group-upgrade-result"])){group_upgrade_result();exit;}
if(isset($_GET["group-packages"])){if(nagr_refuse("html")){exit;}group_packages();exit;}
if(isset($_GET["group-agents-search"])){group_agents_search();exit;}
if(isset($_GET["group-agents-table"])){group_agents_table();exit;}
if(isset($_GET["group-action-buttons"])){group_agents_buttons();exit;}
if(isset($_GET["gpid"])){group_detail();exit;}
if(isset($_GET["groups-table"])){groups_table();exit;}

page();

// ════════════════════════════════════════════════════════════════
// DEFAULT PAGE SHELL
// ════════════════════════════════════════════════════════════════

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{agent_groups}",
        "fas fa-layer-group","{agent_groups_explain}","$page?groups-start=yes",
        "netagents-groups","progress-netagents-groups",false,"table-netagents-groups");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{agent_groups}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ════════════════════════════════════════════════════════════════
// GROUPS TABLE (main content)
// ════════════════════════════════════════════════════════════════
// Vue des groupes : "tiles" (tuiles, par défaut) ou "table". Le choix est
// mémorisé en session pour survivre aux rafraîchissements et à la navigation.
function groups_view():string{
    if(isset($_GET["view"])){
        $v=$_GET["view"]=="table"?"table":"tiles";
        $_SESSION["NETAGENTS_GROUPS_VIEW"]=$v;
        return $v;
    }
    if(isset($_SESSION["NETAGENTS_GROUPS_VIEW"]) && $_SESSION["NETAGENTS_GROUPS_VIEW"]=="table"){
        return "table";
    }
    return "tiles";
}
// ── Liste des groupes d'agents (maquette stitch_agent_groups, 2026-11-07) ──
// Un seul modèle (nagr_model) alimente le bandeau, les tuiles et la table :
// /netagents/groups pour les compteurs du groupe, puis /netagents/groups/{id}
// une fois par groupe pour l'état de ses agents. L'ancienne tuile appelait ce
// détail 4 fois toutes les 5 s, cellule par cellule.

// Droit d'écriture sur la page : le drapeau ne faisait que masquer les boutons.
function nagr_can_write():bool{
    $users=new usersMenus();
    return meta_can_view($users);
}
// Refus servi au format attendu par l'appelant : texte pour un POST de
// formulaire, JS pour un Loadjs, HTML pour un LoadAjax.
function nagr_refuse(string $kind):bool{
    if(nagr_can_write()){return false;}
    $tpl=new template_admin();
    if($kind=="js"){$tpl->js_error("{ERROR_NO_PRIVS}");return true;}
    if($kind=="html"){echo $tpl->div_error("{ERROR_NO_PRIVS}");return true;}
    echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
    return true;
}

// Traduit un token, retire les [br] du moteur et remplace les %s dans l'ordre.
function nagr_tr(string $token,...$args):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    $s=trim(str_replace(array("[br]","<br>","<br/>","<br />"),array(" "," "," "," "),$s));
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}
// Texte venu du démon : échappé, accolades neutralisées pour le moteur de tokens.
function nagr_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}
function nagr_count(int $n,string $one,string $many):string{
    return $n==1?nagr_tr($one):nagr_tr($many,$n);
}
function nagr_accents():array{
    $a=array("accent"=>"#10b981","accent_soft"=>"rgba(16,185,129,.12)","accent_dark"=>"#0d9488");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $a["accent"]=$b["accent"];$a["accent_soft"]=$b["accent_soft"];$a["accent_dark"]=$b["accent_dark"];
    }
    return $a;
}
function nagr_date($ts):string{
    $ts=(string)$ts;
    if($ts=="" || strpos($ts,"0001-01-01")===0){return "";}
    try{ $d=new DateTime($ts); }catch(Exception $e){ return ""; }
    return $d->format("Y-m-d");
}
function nagr_agent_state(array $a):string{
    $st=strtolower((string)($a["status"]??""));
    if($st=="online"){return "online";}
    if($st=="pending"){return "pending";}
    return "offline";
}

// ── Modèle ──
function nagr_model():array{
    $m=array("ok"=>false,"error"=>"","groups"=>array(),
        "groups_n"=>0,"cluster_n"=>0,"agents_n"=>0,"online"=>0,"offline"=>0,"pending"=>0,
        "to_update"=>0,"packages"=>0,"denied_n"=>0);
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups");
    $list=json_decode((string)$raw,true);
    if(!is_array($list)){
        $m["error"]=json_last_error()>JSON_ERROR_NONE?json_last_error_msg():"no answer";
        return $m;
    }
    if(isset($list["Status"]) && !$list["Status"]){
        $m["error"]=(string)($list["Error"]??"error");
        return $m;
    }
    $m["ok"]=true;
    $seen=array();
    foreach($list as $g){
        if(!is_array($g)){continue;}
        $id=intval($g["id"]??0);
        if($id<1){continue;}
        $det=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);
        $row=nagr_group_row($g,$det);
        foreach($row["agents"] as $a){
            // Un agent compté une seule fois dans le bandeau, même s'il
            // apparaissait dans deux groupes.
            $aid=intval($a["id"]??0);
            if($aid>0 && !isset($seen[$aid])){$seen[$aid]=nagr_agent_state($a);}
        }
        $m["groups"][]=$row;
        $m["groups_n"]++;
        if($row["cluster"]){$m["cluster_n"]++;}
        $m["to_update"]+=$row["to_update"];
        if($row["denied"]){$m["denied_n"]++;}else{$m["packages"]+=$row["packages"];}
    }
    foreach($seen as $s){$m[$s]++;}
    $m["agents_n"]=count($seen);
    return $m;
}
// Une ligne de groupe : compteurs de /netagents/groups, état des agents du détail.
function nagr_group_row(array $g,$det):array{
    $detail_ok=is_array($det) && isset($det["name"]);
    $agents=array();
    if($detail_ok && isset($det["agents"]) && is_array($det["agents"])){
        foreach($det["agents"] as $a){ if(is_array($a)){$agents[]=$a;} }
    }
    $row=array(
        "id"=>intval($g["id"]??0),
        "name"=>(string)($g["name"]??""),
        "description"=>(string)($g["description"]??""),
        "created"=>nagr_date($g["created_at"]??""),
        "count"=>$detail_ok?count($agents):intval($g["agent_count"]??0),
        "dhcp"=>intval($g["as_dhcp"]??0),
        "cluster"=>intval($g["cluster_mode"]??0)==1,
        "denied"=>!empty($g["blacklisted"]),
        "tenant_id"=>intval($g["tenant_id"]??0),
        "to_update"=>intval($g["upgrade_agents"]??0),
        "packages"=>intval($g["upgradable_packages"]??0),
        "detail_ok"=>$detail_ok,
        "agents"=>$agents,
        "online"=>0,"offline"=>0,"pending"=>0,"alerts"=>0,
    );
    foreach($agents as $a){
        $row[nagr_agent_state($a)]++;
        $row["alerts"]+=intval($a["artica_bell"]??0);
    }
    return $row;
}
function nagr_match(array $g,string $search):bool{
    $search=trim(str_replace("*","",$search));
    if(strlen($search)<2){return true;}
    $hay=$g["name"]." ".$g["description"];
    foreach($g["agents"] as $a){
        if(is_array($a)){$hay.=" ".($a["hostname"]??"")." ".($a["ip_address"]??"");}
    }
    return stripos($hay,$search)!==false;
}

// ── Bandeau ──
function nagr_strip_cell(string $label,string $value,string $sub,string $cls=""):string{
    return "<div class='nagr-cell $cls'><div class='nagr-cell-l'>$label</div>"
        ."<div class='nagr-cell-v'>$value</div><div class='nagr-cell-s'>$sub</div></div>";
}
function nagr_cell_groups(array $m):string{
    return nagr_strip_cell("{nagr_groups}",(string)$m["groups_n"],nagr_tr("nagr_in_cluster",$m["cluster_n"]));
}
function nagr_cell_agents(array $m):string{
    $parts=array();
    $parts[]="<span>".nagr_tr("nagr_n_online",$m["online"])."</span>";
    if($m["offline"]>0){
        $parts[]="<span class='nagr-bad'><i class='fas fa-circle-xmark' aria-hidden='true'></i> ".nagr_tr("nagr_n_offline",$m["offline"])."</span>";
    }else{
        $parts[]="<span>".nagr_tr("nagr_n_offline",0)."</span>";
    }
    if($m["pending"]>0){$parts[]="<span>".nagr_tr("nagr_n_pending",$m["pending"])."</span>";}
    return nagr_strip_cell("{agents}",(string)$m["agents_n"],implode("<span class='nagr-sep'>·</span>",$parts));
}
function nagr_cell_update(array $m):string{
    if($m["to_update"]>0){
        $sub="<span class='nagr-warn'><i class='fas fa-arrow-up' aria-hidden='true'></i> ".nagr_count($m["to_update"],"nagr_older_one","nagr_older_many")."</span>";
        return nagr_strip_cell("{nagr_to_update}",(string)$m["to_update"],$sub,"nagr-cell-warn");
    }
    $sub="<span class='nagr-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {nagr_up_to_date}</span>";
    return nagr_strip_cell("{nagr_to_update}","0",$sub);
}
function nagr_cell_packages(array $m):string{
    $sub="<span><i class='fas fa-ban' aria-hidden='true'></i> ".nagr_tr("nagr_denied_groups",$m["denied_n"])."</span>";
    return nagr_strip_cell("{nagr_debian_packages}",(string)$m["packages"],$sub);
}
function nagr_strip(array $m):string{
    return "<section class='nagr-strip' aria-label=\"{agent_groups}\">".nagr_cell_groups($m).nagr_cell_agents($m)
        .nagr_cell_update($m).nagr_cell_packages($m)."</section>";
}

// ── Morceaux partagés par la tuile et la ligne ──
function nagr_alerted(array $g):bool{
    return $g["offline"]>0 || $g["alerts"]>0;
}
function nagr_services(array $g,bool $short=false):string{
    if($g["dhcp"]<1){return "";}
    $t=$short?"DHCP":"{APP_DHCP}";
    if($g["cluster"]){$t.=$short?" · Cluster":" · {cluster_mode}";}
    return "<a class='ak-pill' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.group.dhcp.php?dhcp-js={$g["id"]}')\" title=\"{dhcp_parameters}\"><i class='fas fa-network-wired' aria-hidden='true'></i> $t</a>";
}
function nagr_donut(array $g):string{
    if($g["count"]<1){return "";}
    $label=nagr_tr("nagr_donut_label",$g["online"],$g["count"]);
    return "<span class='nagr-donut-wrap' role='img' aria-label=\"$label\" title=\"$label\"><span class='nagr-donut'>{$g["online"]}/{$g["count"]}</span></span>";
}
function nagr_status_line(array $g):string{
    $id=$g["id"];
    if($g["count"]<1){
        return "<div class='nagr-status nagr-status-empty'><span>{nag_no_agent_in_group}</span>"
            ."<a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.groups.php?group-add-agent-js=$id&function=NagrReload')\"><i class='fas fa-plus' aria-hidden='true'></i> {link_agent}</a></div>";
    }
    if(!$g["detail_ok"]){
        return "<div class='nagr-status'><span class='nagr-mute'>".nagr_count($g["count"],"nagr_agent_one","nagr_agent_many")."</span></div>";
    }
    $p=array();
    $p[]="<span class='".($g["online"]>0?"nagr-ok":"nagr-mute")."'><i class='fas fa-circle-check' aria-hidden='true'></i> ".nagr_tr("nagr_n_online",$g["online"])."</span>";
    $p[]="<span class='".($g["offline"]>0?"nagr-bad":"nagr-mute")."'><i class='fas fa-circle-xmark' aria-hidden='true'></i> ".nagr_tr("nagr_n_offline",$g["offline"])."</span>";
    if($g["pending"]>0){$p[]="<span class='nagr-warn'><i class='fas fa-hourglass-half' aria-hidden='true'></i> ".nagr_tr("nagr_n_pending",$g["pending"])."</span>";}
    $p[]="<span class='".($g["alerts"]>0?"nagr-warn":"nagr-mute")."'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".nagr_count($g["alerts"],"nagr_alert_one","nagr_alert_many")."</span>";
    return "<div class='nagr-status'>".nagr_donut($g).implode("",$p)."</div>";
}
function nagr_agent_row(array $a):string{
    $aid=intval($a["id"]??0);
    $host=nagr_h($a["hostname"]??"#$aid");
    $s=nagr_agent_state($a);
    $bell=intval($a["artica_bell"]??0);
    if($s=="offline"){
        $dot="ak-err";$right="<span class='nagr-bad'>{offline}</span>";
    }elseif($s=="pending"){
        $dot="ak-warn";$right="<span class='nagr-warn'>{pending}</span>";
    }elseif($bell>0){
        $dot="ak-warn";$right="<a href=\"javascript:blur();\" class='nagr-warn' OnClick=\"Loadjs('fw.netagents.bell.php?id=$aid')\">".nagr_count($bell,"nagr_alert_one","nagr_alert_many")."</a>";
    }else{
        $dot="ak-ok";$v=(string)($a["version"]??"");
        $right="<span class='nagr-mute'>".($v!==""?"v".nagr_h($v):"{online}")."</span>";
    }
    return "<div class='nagr-agent'><span class='ak-dot $dot' aria-hidden='true'></span>"
        ."<a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.list.php?agent-info-js=$aid')\" title=\"$host\">$host</a>$right</div>";
}
function nagr_preview(array $g):string{
    if($g["count"]<1 || !$g["detail_ok"]){return "";}
    $h=array();
    $h[]="<div class='nagr-prev'><div class='nagr-label'>{nag_first_agents}</div>";
    foreach(array_slice($g["agents"],0,3) as $a){
        if(is_array($a)){$h[]=nagr_agent_row($a);}
    }
    if($g["count"]>3){
        $more=str_replace("%n",(string)($g["count"]-3),nagr_tr("nag_other_agents"));
        $h[]="<a class='nagr-more' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.groups.php?gpid={$g["id"]}&function=NagrReload')\">".nagr_h($more)."</a>";
    }
    $h[]="</div>";
    return implode("",$h);
}
function nagr_update_badge(array $g,bool $short):string{
    $txt=$short?(string)$g["to_update"]:nagr_tr("nagr_to_update_n",$g["to_update"]);
    if($g["to_update"]<1){
        return $short?"<span class='nagr-mute'>0</span>":"<span class='ak-tag'>$txt</span>";
    }
    return "<a class='ak-tag ak-warn' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.list.php?agents-upgrade-js=0&gpid={$g["id"]}')\" title=\"{nb_agents_to_upgrade}\"><i class='fas fa-arrow-up' aria-hidden='true'></i> $txt</a>";
}
function nagr_packages_link(array $g,bool $short):string{
    if($g["denied"]){
        return "<span class='nagr-mute nagr-denied'><i class='fas fa-ban' aria-hidden='true'></i> {nagr_pkgs_denied}</span>";
    }
    $txt=$short?(string)$g["packages"]:nagr_tr("nagr_packages_n",$g["packages"]);
    if($g["packages"]<1){
        return "<span class='nagr-mute'><i class='fas fa-box' aria-hidden='true'></i> $txt</span>";
    }
    return "<a class='nagr-link' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.groups.aptget.php?gpid={$g["id"]}')\" title=\"{nb_deb_pkgs}\"><i class='fas fa-box' aria-hidden='true'></i> $txt</a>";
}
function nagr_deny_switch(array $g,bool $with_label):string{
    if(!meta_provider_or_sysadmin()){return "";}
    $on=$g["denied"];
    $label=nagr_tr("deny_from_updates");
    $aria=$with_label?$label:$label." — ".nagr_h($g["name"]);
    $h="<button type='button' class='ak-sw".($on?" on":"")."' role='switch' aria-checked='".($on?"true":"false")."' aria-label=\"$aria\" OnClick=\"Loadjs('fw.netagents.groups.php?nagr-deny-js={$g["id"]}')\"></button>";
    if(!$with_label){return $h;}
    return "<label class='nagr-switch-l'>$h<span>$label</span></label>";
}
function nagr_btn(string $js,string $ico,string $label,bool $icon_only,string $cls=""):string{
    if($icon_only){
        return "<button type='button' class='nagr-ibtn $cls' aria-label=\"$label\" title=\"$label\" OnClick=\"$js\"><i class='$ico' aria-hidden='true'></i></button>";
    }
    return "<button type='button' class='nagr-btn $cls' OnClick=\"$js\"><i class='$ico' aria-hidden='true'></i> $label</button>";
}
function nagr_actions_main(array $g,bool $compact):string{
    $id=$g["id"];
    $p="fw.netagents.groups.php";
    $h=array();
    $h[]=nagr_btn("Loadjs('$p?group-add-agent-js=$id&function=NagrReload')","fas fa-plus","{link_agent}",$compact);
    $h[]=nagr_btn("Loadjs('fw.netagents.groups.network.php?id=$id')","fas fa-network-wired","{network_settings}",$compact);
    $h[]=nagr_btn("Loadjs('fw.netagents.groups.software.php?id=$id')","fas fa-compact-disc","{nagr_deploy}",$compact);
    $h[]=nagr_btn("Loadjs('fw.netagents.group.features.php?features-js=$id')","fas fa-puzzle-piece","{features}",$compact);
    return "<div class='nagr-acts-main'>".implode("",$h)."</div>";
}
function nagr_actions_side(array $g):string{
    $id=$g["id"];
    $p="fw.netagents.groups.php";
    return "<div class='nagr-acts-side'>"
        .nagr_btn("Loadjs('$p?group-edit-js=$id')","fas fa-pen-to-square","{parameters}",true)
        .nagr_btn("Loadjs('fw.netagents.groups.enroll.php?group-js=$id')","fas fa-user-lock","{na_enroll_accounts}",true)
        .nagr_btn("Loadjs('$p?group-delete-js=$id')","fas fa-trash-can","{nagr_delete}",true,"nagr-danger")
        ."</div>";
}
function nagr_name(array $g):string{
    $id=$g["id"];
    $desc=$g["description"]!==""?"<div class='nagr-desc' title=\"".nagr_h($g["description"])."\">".nagr_h($g["description"])."</div>":"";
    return "<div class='nagr-title'><a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.groups.php?gpid=$id&function=NagrReload')\">".nagr_h($g["name"])."</a>".natn_tenant_chip($g["tenant_id"] ?? 0)."$desc</div>";
}
function nagr_ico(array $g):string{
    return "<span class='nagr-ico".($g["count"]<1?" nagr-ico-empty":"")."' aria-hidden='true'><i class='fas fa-layer-group'></i></span>";
}

// ── Vue tuiles ──
function nagr_card(array $g):string{
    $cls="nagr-card";
    if(nagr_alerted($g)){$cls.=" nagr-alert";}
    $h=array();
    $h[]="<article class='$cls' id='tr-group-{$g["id"]}'>";
    $h[]="<div class='nagr-head'>".nagr_ico($g).nagr_name($g)."</div>";
    $pills=nagr_services($g);
    if($g["denied"]){$pills.="<span class='ak-pill ak-off'><i class='fas fa-ban' aria-hidden='true'></i> {nagr_updates_denied}</span>";}
    if($pills!==""){$h[]="<div class='ak-chips'>$pills</div>";}
    $h[]=nagr_status_line($g);
    $h[]=nagr_preview($g);
    $h[]="<div class='nagr-facts'>".nagr_update_badge($g,false).nagr_packages_link($g,false);
    if($g["created"]!==""){$h[]="<span class='nagr-mono nagr-mute'>".nagr_tr("nagr_created",$g["created"])."</span>";}
    $h[]="</div>";
    $h[]="<div class='nagr-foot'><div class='nagr-foot-top'>".nagr_deny_switch($g,true).nagr_actions_side($g)."</div>".nagr_actions_main($g,false)."</div>";
    $h[]="</article>";
    return implode("",$h);
}
function nagr_tiles(array $rows):string{
    $h=array();
    foreach($rows as $g){$h[]=nagr_card($g);}
    return "<div class='nagr-grid'>".implode("",$h)."</div>";
}

// ── Vue table ──
function nagr_row(array $g):string{
    $id=$g["id"];
    $agents="<span class='nagr-mute nagr-it'>".nagr_count(0,"nagr_agent_one","nagr_agent_many")."</span>";
    if($g["count"]>0 && $g["detail_ok"]){
        $agents="<span class='nagr-ok' title=\"{online}\"><i class='fas fa-circle-check' aria-hidden='true'></i> {$g["online"]}</span>"
            ."<span class='".($g["offline"]>0?"nagr-bad":"nagr-mute")."' title=\"{offline}\"><i class='fas fa-circle-xmark' aria-hidden='true'></i> {$g["offline"]}</span>";
        if($g["pending"]>0){$agents.="<span class='nagr-warn' title=\"{pending}\"><i class='fas fa-hourglass-half' aria-hidden='true'></i> {$g["pending"]}</span>";}
    }elseif($g["count"]>0){
        $agents="<span>{$g["count"]}</span>";
    }
    $alerts=$g["alerts"]>0
        ?"<span class='ak-tag ak-warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {$g["alerts"]}</span>"
        :"<span class='nagr-mute'>0</span>";
    $svc=nagr_services($g,true);
    if($svc===""){$svc="<span class='nagr-mute' aria-label='none'>—</span>";}
    $h=array();
    $h[]="<tr id='tr-group-$id'".(nagr_alerted($g)?" class='nagr-alert'":"").">";
    $h[]="<td><div class='nagr-cellname'><span class='nagr-bar' aria-hidden='true'></span>".nagr_ico($g).nagr_name($g)."</div></td>";
    $h[]="<td><div class='nagr-inline'>$agents</div></td>";
    $h[]="<td>$alerts</td>";
    $h[]="<td>".nagr_update_badge($g,true)."</td>";
    $h[]="<td class='nagr-mono'>".nagr_packages_link($g,true)."</td>";
    $h[]="<td>$svc</td>";
    $h[]="<td>".nagr_deny_switch($g,false)."</td>";
    $h[]="<td class='nagr-mono nagr-mute'>".($g["created"]!==""?$g["created"]:"—")."</td>";
    $h[]="<td class='nagr-right'><div class='nagr-acts'>".nagr_actions_main($g,true).nagr_actions_side($g)."</div></td>";
    $h[]="</tr>";
    return implode("",$h);
}
function nagr_table(array $rows):string{
    $h=array();
    $h[]="<div class='nagr-tcard'><table class='nagr-tbl'><thead><tr>";
    $h[]="<th scope='col'>{name}</th><th scope='col'>{agents}</th><th scope='col'>{alerts}</th>";
    $h[]="<th scope='col'>{nagr_col_update}</th><th scope='col'>{nagr_debian_packages}</th><th scope='col'>{services}</th>";
    $h[]="<th scope='col'>{nagr_col_deny}</th><th scope='col'>{nagr_col_created}</th><th scope='col' class='nagr-right'>{nagr_col_actions}</th>";
    $h[]="</tr></thead><tbody>";
    foreach($rows as $g){$h[]=nagr_row($g);}
    $h[]="</tbody></table></div>";
    return implode("",$h);
}

// ── États vides et erreur ──
function nagr_empty():string{
    return "<div class='nagr-empty'><i class='fas fa-layer-group' aria-hidden='true'></i><p>{nagr_no_group}</p>"
        ."<button type='button' class='nagr-btn nagr-primary' OnClick=\"Loadjs('fw.netagents.groups.php?group-add-js=yes&function=NagrReload')\"><i class='fas fa-plus' aria-hidden='true'></i> {new_group}</button></div>";
}
function nagr_no_match():string{
    return "<div class='nagr-empty nagr-empty-s'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><p>{nagr_no_match}</p></div>";
}
function nagr_error_banner(string $err):string{
    return "<div class='nagr-error' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><div><strong>{nagr_unreachable}</strong><div class='nagr-mono'>".nagr_h($err)."</div></div></div>";
}

// ── Style ──
function nagr_style():string{
    if(isset($GLOBALS["NAGR_STYLE_SENT"])){return "";}
    $GLOBALS["NAGR_STYLE_SENT"]=true;
    $a=nagr_accents();
    $acc=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    return "<style>
.nagr-root{color:#0f172a;font-size:14px;margin-top:16px}
.nagr-root *{box-sizing:border-box}
.nagr-strip{display:grid;grid-template-columns:repeat(4,1fr);background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04);margin-bottom:24px}
.nagr-cell{padding:18px 20px;border-left:1px solid #e2e8f0;min-width:0}
.nagr-cell:first-child{border-left:0}
.nagr-cell-l{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.nagr-cell-v{font-size:28px;font-weight:600;line-height:1.15;margin:4px 0}
.nagr-cell-s{font-size:13px;color:#64748b;display:flex;flex-wrap:wrap;align-items:center;gap:4px 6px}
.nagr-sep{color:#94a3b8}
.nagr-ok{color:#047857}.nagr-bad{color:#b91c1c;font-weight:600}.nagr-warn{color:#b45309;font-weight:600}.nagr-mute{color:#64748b}
.nagr-it{font-style:italic}
.nagr-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px}
.nagr-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
.nagr-card{position:relative;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:14px;box-shadow:0 1px 2px rgba(15,23,42,.04);transition:box-shadow .15s,border-color .15s;overflow:hidden;min-width:0}
.nagr-card:hover{box-shadow:0 4px 14px rgba(15,23,42,.08)}
.nagr-card.nagr-alert{border-left:4px solid #f59e0b;padding-left:17px}
.nagr-head{display:flex;align-items:flex-start;gap:12px;min-width:0}
.nagr-ico{width:40px;height:40px;border-radius:10px;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 auto}
.nagr-ico-empty{background:#f1f5f9;color:#64748b}
.nagr-title{flex:1 1 auto;min-width:0}
.nagr-title a{font-size:18px;font-weight:600;color:#0f172a;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nagr-title a:hover{color:$dark;text-decoration:underline}
.nagr-desc{font-size:13px;color:#64748b;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nagr-status{display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;background:#f8fafc;border-radius:8px;padding:9px 12px;font-size:13px;font-weight:600}
.nagr-status .nagr-mute{font-weight:500}
.nagr-status-empty{justify-content:space-between;font-weight:500}
.nagr-status-empty span{font-style:italic;color:#64748b}
.nagr-status-empty a{color:$dark;text-decoration:none;font-weight:600}
.nagr-donut-wrap{display:inline-flex;width:18px;height:18px}
.nagr-label{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b;margin-bottom:6px}
.nagr-agent{display:flex;align-items:center;gap:10px;height:36px;padding:0 10px;background:#f8fafc;border-radius:6px;margin-top:4px;font-size:13px}
.nagr-agent a{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#0f172a;text-decoration:none;font-family:'JetBrains Mono',Menlo,Consolas,monospace}
.nagr-agent a:hover{color:$dark;text-decoration:underline}
.nagr-agent>span:last-child{white-space:nowrap}
.nagr-more{display:inline-block;margin-top:6px;font-size:13px;font-weight:600;color:$dark;text-decoration:none}
.nagr-more:hover{text-decoration:underline}
.nagr-facts{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px;font-size:13px}
.nagr-facts .nagr-mono{margin-left:auto}
.nagr-link{display:inline-flex;align-items:center;gap:5px;color:#334155;text-decoration:none;white-space:nowrap}
.nagr-link:hover{color:$dark;text-decoration:underline}
.nagr-denied{font-style:italic;white-space:nowrap}
.nagr-foot-top{display:flex;align-items:center;justify-content:space-between;gap:8px}
.nagr-foot{display:flex;flex-direction:column;gap:12px;border-top:1px solid #eef2f6;padding-top:14px;margin-top:auto}
.nagr-switch-l{display:inline-flex;align-items:center;gap:10px;font-size:13px;font-weight:500;margin:0;cursor:pointer}
.nagr-acts{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap}
.nagr-acts-main{display:flex;flex-wrap:wrap;gap:6px}
.nagr-acts-side{display:flex;gap:4px;margin-left:auto}
.nagr-btn{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 10px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#0f172a;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap}
.nagr-btn:hover{background:$soft;border-color:$acc}
.nagr-primary{background:$acc;border-color:$acc;color:#fff;height:38px;padding:0 16px;font-weight:600}
.nagr-primary:hover{background:$dark;border-color:$dark;color:#fff}
.nagr-ibtn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:0;border-radius:8px;background:transparent;color:#475569;cursor:pointer;font-size:15px}
.nagr-ibtn:hover{background:#f1f5f9;color:#0f172a}
.nagr-danger{color:#b91c1c;margin-left:12px}
.nagr-danger:hover{background:#fee2e2;color:#b91c1c}
.nagr-root button:focus-visible,.nagr-root a:focus-visible{outline:2px solid $acc;outline-offset:2px}
.nagr-tcard{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04);overflow-x:auto}
.nagr-tbl{width:100%;border-collapse:collapse;font-size:14px;color:#0f172a}
.nagr-tbl th{background:#f8fafc;color:#64748b;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;text-align:left;padding:12px;border-bottom:1px solid #e2e8f0;line-height:1.3;vertical-align:bottom}
.nagr-tbl td{padding:10px 12px;border-bottom:1px solid #eef2f6;vertical-align:middle;color:#0f172a}
.nagr-tbl tr:last-child td{border-bottom:0}
.nagr-tbl tbody tr:hover td{background:$soft}
.nagr-tbl td{padding:10px}
.nagr-tbl th{padding:12px 10px}
.nagr-tbl td:first-child{width:99%}
.nagr-tbl td:not(:first-child){white-space:nowrap}
.nagr-cellname{display:flex;align-items:center;gap:10px;min-width:200px;max-width:320px}
.nagr-cellname .nagr-ico{width:32px;height:32px;font-size:15px}
.nagr-cellname .nagr-title a{font-size:14px}
.nagr-bar{width:4px;height:32px;border-radius:2px;background:transparent;flex:0 0 auto}
.nagr-tbl tr.nagr-alert .nagr-bar{background:#f59e0b}
.nagr-inline{display:flex;gap:10px;font-size:13px;font-weight:600}
.nagr-right{text-align:right}
.nagr-tbl .nagr-acts{flex-wrap:nowrap;justify-content:flex-end}
.nagr-tbl .nagr-acts-main,.nagr-tbl .nagr-acts-side{flex-wrap:nowrap;gap:2px;margin-left:0}
.nagr-tbl .nagr-danger{margin-left:8px}
.nagr-tbl .nagr-ibtn{width:28px;height:28px;font-size:14px}
.nagr-empty{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:40px;text-align:center;color:#64748b}
.nagr-empty>i{font-size:40px;color:#94a3b8}
.nagr-empty p{margin:14px 0 18px;font-size:14px}
.nagr-empty-s{grid-column:1/-1;padding:28px}
.nagr-error{display:flex;gap:14px;align-items:flex-start;background:#fee2e2;border:1px solid #fca5a5;border-radius:12px;padding:16px 18px;color:#7f1d1d}
.nagr-error>i{font-size:20px;margin-top:2px}
@media(max-width:1200px){.nagr-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(prefers-reduced-motion:reduce){.nagr-card{transition:none}}
</style>";
}

// ── JS : rechargement silencieux du contenu, donut peity ──
function nagr_js(string $url):string{
    $a=nagr_accents();
    $acc=$a["accent"];
    $u=json_encode($url);
    return "<script src='js/jquery.peity.min.js'></script><script>
window.NagrDonut=function(scope){ if(window.jQuery && jQuery.fn.peity){ jQuery((scope||'#nagr-host')+' .nagr-donut').peity('donut',{fill:['$acc','#e2e8f0'],radius:9,innerRadius:5}); } };
window.NagrReload=function(){
    var h=document.getElementById('nagr-host'); if(!h){return;}
    LoadAjaxSilent('nagr-host',h.getAttribute('data-url')+'&nagr-inner=yes');
};
if(window.NagrTimer){clearInterval(window.NagrTimer);}
window.NagrTimer=setInterval(function(){
    var h=document.getElementById('nagr-host');
    if(!h){clearInterval(window.NagrTimer);window.NagrTimer=null;return;}
    if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.sweet-overlay[style*=\"block\"]')){return;}
    NagrReload();
},30000);
NagrDonut();
</script>";
}

// ── Rendu ──
function nagr_inner(string $view,string $search):string{
    $tpl=new template_admin();
    $m=nagr_model();
    if(!$m["ok"]){
        return $tpl->_ENGINE_parse_body(nagr_error_banner($m["error"]));
    }
    if($m["groups_n"]==0){
        return $tpl->_ENGINE_parse_body(nagr_empty());
    }
    $rows=array();
    foreach($m["groups"] as $g){
        if(nagr_match($g,$search)){$rows[]=$g;}
    }
    $body=nagr_no_match();
    if(count($rows)>0){
        $body=$view=="table"?nagr_table($rows):nagr_tiles($rows);
    }
    // Le donut est redessiné après chaque rechargement silencieux.
    return $tpl->_ENGINE_parse_body(nagr_strip($m).$body)."<script>if(window.NagrDonut){NagrDonut();}</script>";
}
function group_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $view=groups_view();
    // La bascule vit dans l'en-tête (TINY), qui apparaît avant le premier résultat
    // de recherche : la fonction doit donc exister dès ici.
    echo "<script>window.NagGroupsView=function(v){LoadAjax('table-netagents-groups','$page?groups-start=yes&view='+(v=='table'?'table':'tiles'));};</script>";
    echo $tpl->search_block($page,"","","","&groups-table=yes&view=$view");
    return true;
}
function groups_tiny($tpl,$view):string{
    $page=CurrentPageName();
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?group-add-js=yes&function=NagrReload');",ico_plus,"{new_group}");
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"nag-groups-view","LABEL"=>"","VALUE"=>$view,
        "OPTIONS"=>array("tiles"=>"<i class='fas fa-grip'></i> {tiles}","table"=>"<i class='fas fa-list'></i> {nagr_table}"),
        "JS"=>"NagGroupsView");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{agent_groups}";
    $TINY_ARRAY["ICO"]="fas fa-layer-group";
    $TINY_ARRAY["EXPL"]="{agent_groups_explain}";
    $TINY_ARRAY["URL"]="netagents-groups";
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}
// Répondeur du search_block, et du rechargement silencieux (nagr-inner).
function groups_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $view=groups_view();
    $search=trim((string)($_GET["search"] ?? ""));
    if(isset($_GET["nagr-inner"])){
        echo nagr_inner($view,$search);
        return true;
    }
    $url="$page?groups-table=yes&view=$view&search=".urlencode($search);
    $html=array();
    $html[]="<div class='nagr-root' id='nagr-host' data-url=\"".htmlspecialchars($url,ENT_QUOTES)."\">";
    $html[]=nagr_inner($view,$search);
    $html[]="</div>";
    $html[]="<script>NoSpinner();".groups_tiny($tpl,$view)."</script>";
    echo nagr_style().siem_tiny_filters_style().implode("",$html).nagr_js($url);
    return true;
}

// ── Deny from updates : interrupteur, confirmation à l'activation ──
function nagr_deny_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(nagr_refuse("js")){return false;}
    $id=intval($_GET["nagr-deny-js"]);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);
    if(!is_array($json) || !isset($json["name"])){
        return $tpl->js_error("{nagr_unreachable}");
    }
    if(empty($json["blacklisted"])){
        return $tpl->js_dialog_confirm_action("{nagr_deny_confirm}","nagr-deny-on",$id,"NagrReload();");
    }
    // Rouvrir les mises à jour ne demande pas de confirmation.
    header("content-type: application/x-javascript");
    echo "$.post('$page',{'nagr-deny-off':'$id'},function(r){r=String(r||'').trim();if(r.length>0){swal({title:'Oops...',text:r,type:'warning'});}NagrReload();});";
    return true;
}
// Un champ par sens (nagr-deny-on / nagr-deny-off) : rejouer la requête ne bascule pas l'état.
function nagr_deny_save(int $id,bool $deny):bool{
    if(nagr_refuse("post")){return false;}
    if($id<1){echo "Invalid group";return false;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);
    $name=is_array($json)?(string)($json["name"]??"#$id"):"#$id";
    if($deny){
        $r=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/blacklist/group/$id",array()),true);
    }else{
        $r=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/netagents/blacklist/group/$id"),true);
    }
    if(is_array($r) && isset($r["Status"]) && !$r["Status"]){
        echo (string)($r["Error"]??"Error");
        return false;
    }
    return admin_tracks("Set agent group $name ".($deny?"denied from updates":"allowed to receive updates"));
}
// ════════════════════════════════════════════════════════════════
// GROUP DETAIL DIALOG (called from fw.netagents.list.php via Loadjs)
// Même kit nagr-* que la liste : en-tête du groupe, barre d'actions, agents.
// ════════════════════════════════════════════════════════════════

function group_detail():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["gpid"]);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);
    if(!is_array($json)||!isset($json["name"])){
        return $tpl->js_error("{nagr_unreachable}");
    }
    $name=nagr_h($json["name"]);
    return $tpl->js_dialog5("<i class='fas fa-layer-group'></i> $name","$page?group-agents-table=$id",950);
}
// Un groupe lu depuis /netagents/groups/{id}, au format des lignes de nagr_model().
function nagrd_group(int $id):array{
    $det=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);
    if(!is_array($det) || !isset($det["name"])){return array();}
    return nagr_group_row($det,$det);
}
function nagr_datetime($ts):string{
    $ts=(string)$ts;
    if($ts=="" || strpos($ts,"0001-01-01")===0){return "";}
    try{ $d=new DateTime($ts); }catch(Exception $e){ return ""; }
    $d->setTimezone(new DateTimeZone(date_default_timezone_get()));
    return $d->format("Y-m-d H:i");
}
// Adresse d'un agent : un agent en connexion inversée appelle le Meta et n'a
// pas d'adresse joignable (le démon enregistre "<uuid>.invalid", port 0).
function nagrd_address(array $a):string{
    $ip=(string)($a["ip_address"]??"");
    if($ip=="" ){return "<span class='nagr-mute'>—</span>";}
    if(substr($ip,-8)==".invalid"){
        return "<span class='nagr-mute' title=\"".nagr_h($ip)."\"><i class='fas fa-right-left' aria-hidden='true'></i> {nagr_reverse_conn}</span>";
    }
    $port=intval($a["port"]??0);
    $prefix=(string)($a["path_prefix"]??"");
    return "<span class='nagr-mono'>".nagr_h($ip).($port>0?":$port":"").nagr_h($prefix)."</span>";
}
function nagrd_state_cell(array $a):string{
    $s=nagr_agent_state($a);
    $map=array("online"=>array("ak-ok","nagr-ok","{online}"),"pending"=>array("ak-warn","nagr-warn","{pending}"),"offline"=>array("ak-err","nagr-bad","{offline}"));
    list($dot,$cls,$label)=$map[$s];
    // Pastille seule ; le libellé reste en infobulle et pour les lecteurs d'écran.
    return "<span class='nagrd-state $cls' title=\"$label\"><span class='ak-dot nagrd-dot $dot' aria-hidden='true'></span><span class='sr-only'>$label</span></span>";
}

// ── En-tête : identité, état, faits, Parameters ──
function nagrd_head(array $g,string $function):string{
    $id=$g["id"];
    $page=CurrentPageName();
    $pills=nagr_services($g);
    if($g["denied"]){$pills.="<span class='ak-pill ak-off'><i class='fas fa-ban' aria-hidden='true'></i> {nagr_updates_denied}</span>";}
    $h=array();
    $h[]="<div class='nagrd-head".(nagr_alerted($g)?" nagr-alert":"")."'>";
    $h[]="<div class='nagr-head'>".nagr_ico($g)."<div class='nagr-title'><div class='nagrd-name'>".nagr_h($g["name"])."</div>";
    if($g["description"]!==""){$h[]="<div class='nagr-desc' title=\"".nagr_h($g["description"])."\">".nagr_h($g["description"])."</div>";}
    $h[]="</div>".nagr_btn("Loadjs('$page?group-edit-js=$id&function=$function')","fas fa-pen-to-square","{parameters}",false)."</div>";
    if($pills!==""){$h[]="<div class='ak-chips'>$pills</div>";}
    $h[]=nagr_status_line($g);
    $h[]="<div class='nagr-facts'>".nagr_update_badge($g,false).nagr_packages_link($g,false);
    if($g["created"]!==""){$h[]="<span class='nagr-mono nagr-mute'>".nagr_tr("nagr_created",$g["created"])."</span>";}
    $h[]="</div></div>";
    return implode("",$h);
}
// ── Actions de groupe ──
function nagrd_actions(array $g,string $function):string{
    $id=$g["id"];
    $page=CurrentPageName();
    $h=array();
    $h[]="<div class='nagrd-acts'>";
    $h[]=nagr_btn("Loadjs('$page?group-add-agent-js=$id&function=$function')","fas fa-plus","{link_agent}",false,"nagr-primary nagrd-primary");
    $h[]=nagr_btn("Loadjs('$page?group-upgrade-js=$id&function=$function')","fas fa-arrow-up","{nagr_update_agents}",false);
    $h[]=nagr_btn("LoadAjax('group-action-result','$page?group-renew-certs=$id&function=$function')","fas fa-lock","{nagr_renew_certs}",false);
    $h[]=nagr_btn("LoadAjax('group-action-result','$page?group-packages=$id&function=$function')","fas fa-box","{nagr_check_packages}",false);
    $h[]=nagr_btn("Loadjs('fw.netagents.group.features.php?features-js=$id')","fas fa-puzzle-piece","{features}",false);
    $h[]=nagr_btn("Loadjs('fw.netagents.groups.enroll.php?group-js=$id')","fas fa-user-lock","{na_enroll_accounts}",false);
    $h[]="</div>";
    return implode("",$h);
}
function group_agents_buttons():bool{
    $tpl=new template_admin();
    $id=intval($_GET["group-action-buttons"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $g=nagrd_group($id);
    if(count($g)==0){
        echo $tpl->_ENGINE_parse_body(nagr_error_banner("#$id"));
        return false;
    }
    echo $tpl->_ENGINE_parse_body(nagrd_head($g,$function).nagrd_actions($g,$function))."<script>if(window.NagrDonut){NagrDonut('#group-action-buttons-$id');}</script>";
    return true;
}

// ── Agents du groupe (répondeur du search_block de la fenêtre) ──
function nagrd_agent_row(int $gid,array $a,string $function):string{
    $aid=intval($a["id"]??0);
    $host=nagr_h($a["hostname"]??"#$aid");
    $bell=intval($a["artica_bell"]??0);
    $ver=(string)($a["version"]??"");
    $seen=nagr_datetime($a["last_seen"]??"");
    $kind=!empty($a["artica"])
        ?"<i class='fas fa-cube nagrd-kind' title='Artica' aria-label='Artica'></i>"
        :"<i class='fas fa-server nagrd-kind nagr-mute' aria-hidden='true'></i>";
    $label=nagr_tr("nagr_unlink");
    $h=array();
    $h[]="<tr>";
    $h[]="<td>".nagrd_state_cell($a)."</td>";
    $h[]="<td><div class='nagrd-host'>$kind<a href=\"javascript:blur();\" OnClick=\"Loadjs('fw.netagents.list.php?agent-info-js=$aid')\" title=\"$host\">$host</a></div></td>";
    $h[]="<td>".nagrd_address($a)."</td>";
    $h[]="<td class='nagr-mono'>".($ver!==""?"v".nagr_h($ver):"<span class='nagr-mute'>—</span>")."</td>";
    $h[]="<td>".($bell>0?"<a href=\"javascript:blur();\" class='ak-tag ak-warn' OnClick=\"Loadjs('fw.netagents.bell.php?id=$aid')\"><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> $bell</a>":"<span class='nagr-mute'>0</span>")."</td>";
    $h[]="<td class='nagr-mono nagr-mute'>".($seen!==""?$seen:"—")."</td>";
    $h[]="<td class='nagr-right'><button type='button' class='nagr-ibtn nagr-danger' aria-label=\"$label — $host\" title=\"$label\" OnClick=\"Loadjs('fw.netagents.groups.php?nagr-unlink-js=$gid&agent=$aid&function=$function')\"><i class='fas fa-link-slash' aria-hidden='true'></i></button></td>";
    $h[]="</tr>";
    return implode("",$h);
}
function group_agents_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $id=intval($_GET["group-agents-search"]);
    $refreshHead="<script>LoadAjaxSilent('group-action-buttons-$id','$page?group-action-buttons=$id&function=$function');</script>";
    $g=nagrd_group($id);
    if(count($g)==0){
        echo $tpl->_ENGINE_parse_body(nagr_error_banner("#$id"));
        return false;
    }
    if(count($g["agents"])==0){
        $html="<div class='nagr-empty nagr-empty-s'><i class='fas fa-server' aria-hidden='true'></i><p>{nag_no_agent_in_group}</p>"
            ."<button type='button' class='nagr-btn nagr-primary' OnClick=\"Loadjs('$page?group-add-agent-js=$id&function=$function')\"><i class='fas fa-plus' aria-hidden='true'></i> {link_agent}</button></div>";
        echo $tpl->_ENGINE_parse_body($html).$refreshHead;
        return true;
    }
    // La saisie est cherchée telle quelle : elle partait dans une expression
    // régulière, et un « ( » cassait la liste.
    $search=trim(str_replace("*","",(string)($_GET["search"] ?? "")));
    $rows=array();
    foreach($g["agents"] as $a){
        if(!is_array($a)){continue;}
        if(strlen($search)>1){
            $hay=($a["hostname"]??"")." ".($a["ip_address"]??"")." ".($a["version"]??"")." ".($a["status"]??"");
            if(stripos($hay,$search)===false){continue;}
        }
        $rows[]=nagrd_agent_row($id,$a,$function);
    }
    if(count($rows)==0){
        echo $tpl->_ENGINE_parse_body(nagr_no_match()).$refreshHead;
        return true;
    }
    $h=array();
    $h[]="<div class='nagr-tcard'><table class='nagr-tbl nagrd-tbl'><thead><tr>";
    $h[]="<th scope='col'>{status}</th><th scope='col'>{nagr_col_hostname}</th><th scope='col'>{address}</th><th scope='col'>{nagr_col_version}</th>";
    $h[]="<th scope='col'>{alerts}</th><th scope='col'>{nagr_col_last_seen}</th><th scope='col' class='nagr-right'><span class='sr-only'>{nagr_col_actions}</span></th>";
    $h[]="</tr></thead><tbody>".implode("",$rows)."</tbody></table></div>";
    echo $tpl->_ENGINE_parse_body(implode("",$h)).$refreshHead;
    return true;
}
function group_agents_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["group-agents-table"]);
    $f=array();
    $f[]="<div class='nagr-root nagrd'>";
    $f[]="<div id='group-action-buttons-$id'></div>";
    $f[]="<div id='group-action-result'></div>";
    $f[]=$tpl->search_block($page,"","","","&group-agents-search=$id");
    $f[]="</div>";
    // La fenêtre s'ouvre aussi depuis la liste des agents : elle apporte son style et son donut.
    echo nagr_style().nagrd_style().$tpl->_ENGINE_parse_body(implode("\n",$f)).nagrd_js();
    return true;
}
function nagrd_style():string{
    return "<style>
.nagrd{margin-top:0}
.nagrd-head{display:flex;flex-direction:column;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px}
.nagrd-head.nagr-alert{border-left:4px solid #f59e0b;padding-left:17px}
.nagrd-head .nagr-head{align-items:center}
.nagrd-name{font-size:18px;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.nagrd-acts{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 4px}
.nagrd-primary{height:32px;padding:0 12px}
.nagrd-tbl td:first-child{width:auto}
.nagrd-tbl td:nth-child(2){width:99%;max-width:0}
.nagrd-host{display:flex;align-items:center;gap:8px;min-width:0}
.nagrd-host a{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#0f172a;font-weight:600;text-decoration:none;font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px}
.nagrd-host a:hover{text-decoration:underline}
.nagrd-kind{flex:0 0 auto;color:#0d9488}
.nagrd-state{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:4px 0}
.nagrd-dot{width:10px;height:10px}
.nagrd-tbl td:first-child,.nagrd-tbl th:first-child{text-align:center}
.nagrd .nagr-tbl .nagr-danger{margin-left:0}
.nagrd .nagr-tcard{margin-top:4px}
.nagrd-tbl th,.nagrd-tbl td:not(:nth-child(2)){white-space:nowrap}
</style>";
}
function nagrd_js():string{
    $a=nagr_accents();
    $acc=$a["accent"];
    return "<script src='js/jquery.peity.min.js'></script><script>
if(!window.NagrDonut){window.NagrDonut=function(scope){ if(window.jQuery && jQuery.fn.peity){ jQuery((scope||'#nagr-host')+' .nagr-donut').peity('donut',{fill:['$acc','#e2e8f0'],radius:9,innerRadius:5}); } };}
</script>";
}

// ── Retirer un agent du groupe, après confirmation ──
function nagr_unlink_js():bool{
    $tpl=new template_admin();
    if(nagr_refuse("js")){return false;}
    $gid=intval($_GET["nagr-unlink-js"]);
    $aid=intval($_GET["agent"]??0);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $g=nagrd_group($gid);
    $host="#$aid";
    if(count($g)>0){
        foreach($g["agents"] as $a){
            if(intval($a["id"]??0)==$aid){$host=(string)($a["hostname"]??$host);}
        }
    }
    $text=nagr_tr("nagr_unlink_confirm",nagr_h($host),nagr_h($g["name"]??"#$gid"));
    $after=($function!==""?"$function();":"")."if(window.NagrReload){NagrReload();}";
    return $tpl->js_confirm_delete($text,"nagr-unlink","$gid:$aid",$after,true);
}
function nagr_unlink_save():bool{
    if(nagr_refuse("post")){return false;}
    $parts=explode(":",(string)$_POST["nagr-unlink"]);
    $gid=intval($parts[0]);
    $aid=intval($parts[1]??0);
    if($gid<1 || $aid<1){echo "Invalid request";return false;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$gid/agents/delete/$aid"),true);
    if(!is_array($json) || empty($json["Status"])){
        echo is_array($json)?(string)($json["Error"]??"Error"):"Error";
        return false;
    }
    return admin_tracks("Removed agent #$aid from agent group #$gid");
}

// ════════════════════════════════════════════════════════════════
// CREATE GROUP
// ════════════════════════════════════════════════════════════════

function group_add_js():bool{
    $function=$_GET['function'];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog10("<i class='".ico_plus."'></i> {new_group}","$page?group-add-popup=yes&function=$function",550);
}

function group_add_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET['function'];
    $form=array();
    $form[]=$tpl->field_text("group_name","{name}","",true);
    $form[]=$tpl->field_text("group_description","{description}","");
    $form[]=$tpl->field_checkbox("group_cluster_mode","{dhcp_cluster_mode}",0,false,"{dhcp_cluster_mode_explain}");
    $tf=natn_tenant_field($tpl,"group_tenant_id",0);   // infogerant : groupe global ou d'un tenant
    if($tf!==""){$form[]=$tf;}
    $form[]=$tpl->field_hidden("group_save","1");
    echo $tpl->form_outside("",
        $form,"","{add}",
        "dialogInstance10.close();$function();",
        "AsMetaAdmin");
    return true;
}

function group_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $name=trim($_POST["group_name"]??"");
    $desc=trim($_POST["group_description"]??"");
    if(strlen($name)<1){
        echo $tpl->post_error("{name}: {required}");
        return false;
    }
    $data=array("name"=>$name,"description"=>$desc,"cluster_mode"=>intval($_POST["group_cluster_mode"]??0));
    $tenant=natn_posted_tenant("group_tenant_id");   // 0 pour un tenant : le daemon impose le sien
    if($tenant>0){$data["tenant_id"]=$tenant;}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/groups",$data));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->post_error(__LINE__.") ".$json->Error??"Error");
        return false;
    }
    admin_tracks("Created agent group: $name");
    return true;
}

// ════════════════════════════════════════════════════════════════
// EDIT GROUP
// ════════════════════════════════════════════════════════════════

function group_edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-edit-js"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $name="";
    if(is_object($json)&&property_exists($json,"name")){
        $name=htmlspecialchars($json->name);
    }
    return $tpl->js_dialog8("<i class='".ico_edit."'></i> $name","$page?group-edit-popup=$id",550);
}

function group_edit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-edit-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        echo $tpl->div_error("{error}");
        return false;
    }
    $form=array();
    $form[]=$tpl->field_text("group-name","{name}",$json->name,true);
    $form[]=$tpl->field_text("group-description","{description}",$json->description??"");
    $clusterMode=intval($json->cluster_mode??0);
    $form[]=$tpl->field_checkbox("group-cluster-mode","{dhcp_cluster_mode}",$clusterMode,false,"{dhcp_cluster_mode_explain}");
    $form[]=$tpl->field_hidden("group-edit-save","$id");
    echo $tpl->form_outside("",
        $form,"","{apply}",
        "dialogInstance8.close();if(window.NagrReload){NagrReload();}",
        "AsMetaAdmin");
    return true;
}

function group_edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=intval($_POST["group-edit-save"]);
    $name=trim($_POST["group-name"]);
    $desc=trim($_POST["group-description"]);
    if(strlen($name)<1){
        echo $tpl->post_error("Err.".__LINE__." {name}: {required}");
        return false;
    }
    $data=array("name"=>$name,"description"=>$desc,"cluster_mode"=>intval($_POST["group-cluster-mode"]??0));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/groups/$id",$data));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->post_error("#$id Err.".__LINE__." ".$json->Error??"Error");
        return false;
    }
    admin_tracks("Updated agent group identity #$id: $name ($desc)");
    return true;
}

// ════════════════════════════════════════════════════════════════
// DELETE GROUP
// ════════════════════════════════════════════════════════════════

function group_delete_js():bool{
    $tpl=new template_admin();
    $id=intval($_GET["group-delete-js"]);
    $md=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["md"]??""));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $name="#$id";
    if(is_object($json)&&property_exists($json,"name")){
        $name=$json->name;
    }
    return $tpl->js_confirm_delete($name,"group-delete",$id,"if(window.NagrReload){NagrReload();}else{\$('#$md').remove();}");
}

function group_delete():bool{
    $tpl=new template_admin();
    $id=intval($_POST["group-delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/delete/$id"));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->post_error($json->Error??"Error");
        return false;
    }
    admin_tracks("Deleted agent group #$id");
    return true;
}

// ════════════════════════════════════════════════════════════════
// ADD AGENT TO GROUP
// ════════════════════════════════════════════════════════════════

function group_add_agent_js():bool{
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl=new template_admin();
    $id=intval($_GET["group-add-agent-js"]);
    return $tpl->js_dialog3("<i class='".ico_plus."'></i> {link_agent}","$page?group-add-agent-popup=$id&function=$function",880);
}

// ════════════════════════════════════════════════════════════════
// Fenêtre « Add machines to <groupe> » (maquette stitch_artica_meta_group_agent_modal).
// « Also in » : champ groups de /netagents/list.
// Écarté de la maquette : l'heure exacte en UTC et « awaiting initial handshake »
// (la console affiche l'heure locale), les puces de réassurance vides du rendu.
// ════════════════════════════════════════════════════════════════
function nagm_model(int $id):array{
    $m=array("id"=>$id,"ok"=>false,"group"=>"","group_n"=>0,"registered"=>0,"agents"=>array(),
        "online"=>0,"pending"=>0,"offline"=>0,"edit"=>nagr_can_write());
    $grp=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);
    $all=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list"),true);
    if(!is_array($grp) || !is_array($all) || !isset($all["agents"]) || !is_array($all["agents"])){return $m;}
    $m["ok"]=true;
    $m["group"]=(string)($grp["name"] ?? "#$id");
    $existing=array();
    if(isset($grp["agents"]) && is_array($grp["agents"])){
        foreach($grp["agents"] as $a){$existing[intval($a["id"] ?? 0)]=true;}
    }
    $m["group_n"]=count($existing);
    $m["registered"]=count($all["agents"]);
    foreach($all["agents"] as $a){
        $aid=intval($a["id"] ?? 0);
        if($aid<=0 || isset($existing[$aid])){continue;}
        if(array_key_exists("enabled",$a) && !$a["enabled"]){continue;}
        $st=nagr_agent_state($a);
        $m[$st]++;
        $a["_state"]=$st;
        // /netagents/list donne deja les groupes de l'agent (nom => id), limites au tenant.
        $a["_groups"]=array();
        if(isset($a["groups"]) && is_array($a["groups"])){
            foreach($a["groups"] as $gname=>$gid){if(intval($gid)!=$id){$a["_groups"][]=(string)$gname;}}
            sort($a["_groups"],SORT_NATURAL|SORT_FLAG_CASE);
        }
        $m["agents"][]=$a;
    }
    usort($m["agents"],function($x,$y){
        $o=array("online"=>0,"pending"=>1,"offline"=>2);
        $c=$o[$x["_state"]]<=>$o[$y["_state"]];
        return $c!=0?$c:strcasecmp((string)($x["hostname"] ?? ""),(string)($y["hostname"] ?? ""));
    });
    return $m;
}
function nagm_ago(int $ts):string{
    $s=max(0,time()-$ts);
    if($s<90){return nagr_tr("nagm_ago_now");}
    if($s<5400){return nagr_tr("nagm_ago_min",strval(intval(round($s/60))));}
    if($s<172800){return nagr_tr("nagm_ago_h",strval(intval(round($s/3600))));}
    return nagr_tr("nagm_ago_d",strval(intval(round($s/86400))));
}
function nagm_ts($v):int{
    $v=(string)$v;
    if($v=="" || strpos($v,"0001-01-01")===0){return 0;}
    try{$d=new DateTime($v);return $d->getTimestamp();}catch(Exception $e){return 0;}
}
function nagm_header(array $m):string{
    $g=nagr_h($m["group"]);
    $sub=$m["group_n"]==1?"{nagm_group_n1}":nagr_h(nagr_tr("nagm_group_n",strval($m["group_n"])));
    return "<div class='nagm-head'><span class='nagm-hico' aria-hidden='true'><i class='fas fa-object-group'></i></span>"
        ."<div><h2>".str_replace("\x01","<span class='nagm-gname'>$g</span>",nagr_h(nagr_tr("nagm_title","\x01")))."</h2><p id='nagm-count' data-n='{$m["group_n"]}'>$sub</p></div></div>";
}
function nagm_explain():string{
    return "<div class='nagm-explain'><i class='fas fa-circle-info' aria-hidden='true'></i><div><p>{nagm_explain}</p>"
        ."<div class='nagm-chips'><span><i class='fas fa-rotate-left' aria-hidden='true'></i>{nagm_chip_reversible}</span>"
        
        ."<span><i class='fas fa-layer-group' aria-hidden='true'></i>{nagm_chip_several}</span></div></div></div>";
}
function nagm_toolbar(array $m):string{
    $n=count($m["agents"]);
    $seg=array(array("all","{nagm_all}",$n),array("online","{online}",$m["online"]),array("pending","{pending}",$m["pending"]),array("offline","{offline}",$m["offline"]));
    $h=array("<div class='nagm-tools'><label class='nagm-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='nagm-q' placeholder='{nagm_search}' aria-label='{nagm_search}' oninput='NagmFilter()'></label>");
    $h[]="<div class='nagm-seg' role='group' aria-label='{nagm_filter}'>";
    foreach($seg as $s){
        $on=$s[0]=="all"?" on' aria-pressed='true":"' aria-pressed='false";
        $h[]="<button type='button' class='nagm-sb$on' data-st='{$s[0]}' OnClick=\"NagmState(this)\">{$s[1]} <b>{$s[2]}</b></button>";
    }
    $h[]="</div>";
    if($m["edit"]){$h[]="<label class='nagm-all'><input type='checkbox' id='nagm-all' onchange='NagmAll(this)'> {nagm_select_all}</label>";}
    $h[]="</div>";
    return implode("",$h);
}
function nagm_state_pill(array $a):string{
    $map=array("online"=>array("ok","fas fa-circle-check","{online}"),"pending"=>array("warn","fas fa-hourglass-half","{pending}"),"offline"=>array("off","fas fa-circle-xmark","{offline}"));
    list($cls,$ico,$label)=$map[$a["_state"]];
    $seen=nagm_ts($a["last_seen"] ?? "");
    $reg=nagm_ts($a["registered_at"] ?? "");
    if($a["_state"]=="pending" || $seen==0){
        $sub="{nagm_waiting}";
        $tip=$reg>0?nagr_h(nagr_tr("nagm_registered_on",date("Y-m-d H:i",$reg))):"";
    }else{
        $sub=nagr_h(nagr_tr("nagm_last_contact",nagm_ago($seen)));
        $tip=nagr_h(date("Y-m-d H:i:s",$seen).($reg>0?" · ".nagr_tr("nagm_registered_on",date("Y-m-d",$reg)):""));
    }
    return "<div class='nagm-st'><span class='nagm-pill $cls'><i class='$ico' aria-hidden='true'></i>$label</span><span class='nagm-seen' title=\"$tip\">$sub</span></div>";
}
function nagm_kind(array $a):string{
    $p=array();
    if(!empty($a["artica"]) || !empty($a["artica_node"])){
        $v=preg_replace('/(\.0+)+$/',"",trim((string)($a["artica_version"] ?? "")));
        $sp=intval($a["artica_service_pack"] ?? 0);
        $hf=trim((string)($a["artica_hotfix"] ?? ""));
        $s="{nagm_artica}".($v!=""?" ".nagr_h($v):"").($sp>0?" SP$sp":"");
        if($hf!=""){$s.=" · hotfix ".nagr_h($hf);}
        $p[]=$s;
    }else{
        $d=intval($a["debian_version"] ?? 0);
        $p[]=$d>0?nagr_h(nagr_tr("nagm_debian_n",strval($d))):"{nagm_debian}";
    }
    $ver=trim((string)($a["version"] ?? ""));
    if($ver!=""){$p[]="<span class='nagm-mute'>".nagr_h(nagr_tr("nagm_agent_v",$ver))."</span>";}
    return "<span class='nagm-kind'>".implode(" · ",$p)."</span>";
}
function nagm_groups(array $a):string{
    $g=$a["_groups"];
    if(count($g)==0){return "";}
    $h=array("<span class='nagm-also'>{nagm_also_in}");
    foreach(array_slice($g,0,2) as $n){$h[]="<span class='nagm-gchip'>".nagr_h($n)."</span>";}
    if(count($g)>2){$h[]="<span class='nagm-gchip' title=\"".nagr_h(implode(", ",array_slice($g,2)))."\">+".(count($g)-2)."</span>";}
    $h[]="</span>";
    return implode("",$h);
}
function nagm_row(array $m,array $a):string{
    $aid=intval($a["id"]);
    $host=trim((string)($a["hostname"] ?? ""));
    if($host==""){$host="#$aid";}
    $hh=nagr_h($host);
    $desc=trim((string)($a["description"] ?? ""));
    $addr=(string)($a["ip_address"] ?? "");
    $search=strtolower($host." ".$desc." ".$addr);
    $bell=intval($a["artica_bell"] ?? 0);
    $h=array("<article class='nagm-row' data-id='$aid' data-st='{$a["_state"]}' data-q=\"".nagr_h($search)."\">");
    $h[]=$m["edit"]?"<input type='checkbox' class='nagm-ck' value='$aid' aria-label=\"".nagr_h(nagr_tr("nagm_select_one",$host))."\" onchange='NagmCount()'>":"<span></span>";
    $h[]=nagm_state_pill($a);
    $h[]="<div class='nagm-id'><div class='nagm-l1'><b title=\"$hh\">$hh</b>".($desc!=""?"<span class='nagm-desc' title=\"".nagr_h($desc)."\">".nagr_h($desc)."</span>":"")."</div>";
    $h[]="<div class='nagm-l2'>".nagrd_address($a).nagm_kind($a)."</div>";
    $extra=nagm_groups($a).($bell>0?"<span class='nagm-bell'><i class='fas fa-bell' aria-hidden='true'></i>".nagr_h(nagr_tr($bell==1?"nagm_alert1":"nagm_alerts",strval($bell)))."</span>":"");
    if($extra!=""){$h[]="<div class='nagm-l3'>$extra</div>";}
    $h[]="</div><div class='nagm-act'>";
    if($m["edit"]){
        $h[]="<button type='button' class='nagm-add' OnClick=\"NagmAdd([$aid])\" aria-label=\"".nagr_h(nagr_tr("nagm_add_one",$host))."\"><i class='fas fa-plus' aria-hidden='true'></i> {nagm_add}</button>";
    }
    if($a["_state"]!="online"){$h[]="<span class='nagm-next'>{nagm_next_contact}</span>";}
    $h[]="</div></article>";
    return implode("",$h);
}
function nagm_empty(array $m):string{
    if($m["registered"]==0){
        return "<div class='nagm-empty'><span aria-hidden='true'><i class='fas fa-server'></i></span><b>{nagm_none_title}</b><p>{nagm_none_text}</p>"
            .(meta_is_provider()?"<a class='nagm-btn' href='javascript:blur();' OnClick=\"if(window.dialogInstance3){dialogInstance3.close();}Loadjs('fw.netagents.list.php?agent-add-js=yes');\"><i class='fas fa-plus' aria-hidden='true'></i>{nagm_register}</a>":"")."</div>";
    }
    return "<div class='nagm-empty'><span aria-hidden='true'><i class='fas fa-circle-check'></i></span><b>{nagm_all_in_title}</b><p>{nagm_all_in_text}</p></div>";
}
function nagm_footer(array $m):string{
    $n=count($m["agents"]);
    $can=$n==1?"{nagm_can_add1}":nagr_h(nagr_tr("nagm_can_add",strval($n)));
    $h="<div class='nagm-foot'><span><b id='nagm-sel'>".nagr_h(nagr_tr("nagm_selected",strval(0)))."</b> · <span id='nagm-left' data-n='$n'>$can</span></span><span class='nagm-fb'>"
        ."<button type='button' class='nagm-btn sec' OnClick=\"if(window.dialogInstance3){dialogInstance3.close();}\">{nagm_close}</button>";
    if($m["edit"]){
        $h.="<button type='button' class='nagm-btn' id='nagm-bulk' disabled title='{nagm_pick_first}' OnClick='NagmBulk()'><i class='fas fa-plus' aria-hidden='true'></i> <span id='nagm-bulk-l'>".nagr_h(nagr_tr("nagm_add_sel",strval(0)))."</span></button>";
    }
    return $h."</span></div>";
}
function nagm_js(array $m,string $function):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $T=array("sel"=>nagr_tr("nagm_selected","%n"),"bulk"=>nagr_tr("nagm_add_sel","%n"),"added"=>nagr_tr("nagm_added"),
        "gn"=>nagr_tr("nagm_group_n","%n"),"gn1"=>nagr_tr("nagm_group_n1"),"left"=>nagr_tr("nagm_can_add","%n"),"left1"=>nagr_tr("nagm_can_add1"),
        "nomatch"=>nagr_tr("nagm_nomatch","%q"),"err"=>nagr_tr("nagm_add_err"));
    return "<script>NoSpinner();(function(){var T=".json_encode($T,$J).",G={$m["id"]},F=".json_encode($function).",ST='all';"
        ."function rows(){return document.querySelectorAll('#nagm-list .nagm-row');}"
        ."window.NagmFilter=function(){var q=(document.getElementById('nagm-q')||{value:''}).value.toLowerCase().trim(),n=0;"
        ."rows().forEach(function(r){var ok=(ST=='all'||r.getAttribute('data-st')==ST)&&(q==''||r.getAttribute('data-q').indexOf(q)>=0);r.hidden=!ok;if(ok){n++;}});"
        ."var e=document.getElementById('nagm-nomatch');if(e){e.hidden=!(n==0&&rows().length>0);var t=document.getElementById('nagm-nomatch-t');if(t){t.textContent=T.nomatch.replace('%q',q);}}NagmCount();};"
        ."window.NagmState=function(b){ST=b.getAttribute('data-st');document.querySelectorAll('.nagm-sb').forEach(function(x){var on=x===b;x.classList.toggle('on',on);x.setAttribute('aria-pressed',on?'true':'false');});NagmFilter();};"
        ."window.NagmClear=function(){var q=document.getElementById('nagm-q');if(q){q.value='';}NagmFilter();};"
        ."window.NagmAll=function(c){rows().forEach(function(r){if(!r.hidden){var k=r.querySelector('.nagm-ck');if(k){k.checked=c.checked;}}});NagmCount();};"
        ."function picked(){var a=[];document.querySelectorAll('#nagm-list .nagm-ck:checked').forEach(function(k){if(!k.closest('.nagm-row').hidden){a.push(parseInt(k.value,10));}});return a;}"
        ."window.NagmCount=function(){var n=picked().length,s=document.getElementById('nagm-sel'),b=document.getElementById('nagm-bulk'),l=document.getElementById('nagm-bulk-l');"
        ."if(s){s.textContent=T.sel.replace('%n',n);}if(l){l.textContent=T.bulk.replace('%n',n);}if(b){b.disabled=(n==0);}};"
        ."window.NagmBulk=function(){var a=picked();if(a.length){NagmAdd(a);}};"
        ."function bump(k){var c=document.getElementById('nagm-count');if(c){var n=parseInt(c.getAttribute('data-n'),10)+k;c.setAttribute('data-n',n);c.textContent=n==1?T.gn1:T.gn.replace('%n',n);}"
        ."var l=document.getElementById('nagm-left');if(l){var m=parseInt(l.getAttribute('data-n'),10)-k;l.setAttribute('data-n',m);l.textContent=m==1?T.left1:T.left.replace('%n',m);}}"
        ."window.NagmAdd=function(ids){ids.forEach(function(i){var r=document.querySelector('.nagm-row[data-id=\"'+i+'\"]');if(r){r.classList.add('busy');}});"
        ."$.post('$page',{'nagm-add':G,'agents':ids.join(',')},function(d){var j=null;try{j=JSON.parse(d);}catch(e){}"
        ."if(!j){alert(String(d||T.err).trim());document.querySelectorAll('.nagm-row.busy').forEach(function(r){r.classList.remove('busy');});return;}"
        ."(j.added||[]).forEach(function(i){var r=document.querySelector('.nagm-row[data-id=\"'+i+'\"]');if(!r){return;}r.classList.remove('busy');r.classList.add('done');"
        ."var a=r.querySelector('.nagm-act');if(a){a.innerHTML='<span class=\"nagm-ok\"><i class=\"fas fa-check\" aria-hidden=\"true\"></i> '+T.added+'</span>';}"
        ."var k=r.querySelector('.nagm-ck');if(k){k.checked=false;k.disabled=true;}setTimeout(function(){r.parentNode&&r.parentNode.removeChild(r);NagmFilter();},2000);});"
        ."bump((j.added||[]).length);document.querySelectorAll('.nagm-row.busy').forEach(function(r){r.classList.remove('busy');});"
        ."if(j.errors&&j.errors.length){alert(j.errors.join('\\n'));}if((j.added||[]).length&&F&&typeof window[F]==='function'){window[F]();}NagmCount();});};"
        ."})();</script>";
}
function nagm_style():string{
    return "<style>
.nagm{color:#1f2937;font-size:14px}
.nagm-head{display:flex;gap:14px;align-items:center;padding:4px 0 14px 0;border-bottom:1px solid #eef0f3}
.nagm-hico{flex:0 0 40px;height:40px;border-radius:10px;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:17px}
.nagm-head h2{margin:0;font-size:18px;font-weight:600;color:#111827}
.nagm-gname{color:#005447}
.nagm-head p{margin:2px 0 0 0;font-size:13px;color:#6b7280}
.nagm-explain{display:flex;gap:12px;margin:14px 0;padding:12px 16px;border-radius:8px;background:#f2f6f6;border:1px solid #e6eeed}
.nagm-explain>i,.nagm-explain>svg{color:#005447;margin-top:3px;flex:0 0 auto}
.nagm-explain p{margin:0;line-height:1.5}
.nagm-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.nagm-chips span{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:#fff;border:1px solid #bfd4d1;color:#005447;font-size:13px}
.nagm-tools{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px}
.nagm-search{flex:1 1 240px;display:flex;align-items:center;gap:8px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;height:36px;margin:0;font-weight:400;min-width:0;box-sizing:border-box}
.nagm-search i,.nagm-search svg{color:#9ca3af}
.nagm-search input{border:0;outline:0;flex:1 1 auto;min-width:0;font-size:14px;background:transparent;height:34px}
.nagm-search:focus-within{border-color:#005447;box-shadow:0 0 0 2px #bfd4d1}
.nagm-seg{display:inline-flex;background:#f3f4f6;border-radius:8px;padding:3px;gap:2px}
.nagm-sb{border:0;background:transparent;border-radius:6px;padding:5px 10px;font-size:13px;color:#374151;cursor:pointer;white-space:nowrap}
.nagm-sb b{font-weight:600;margin-left:2px}
.nagm-sb.on{background:#fff;color:#005447;box-shadow:0 1px 2px rgba(0,0,0,.08)}
.nagm-all{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:400;color:#374151;margin:0;white-space:nowrap}
.nagm-list{border-top:1px solid #eef0f3;max-height:52vh;overflow-y:auto}
.nagm-row{display:grid;grid-template-columns:24px 150px minmax(0,1fr) 170px;gap:12px;align-items:center;padding:12px 4px;border-bottom:1px solid #f1f3f5;min-height:72px;box-sizing:border-box}
.nagm-row[hidden]{display:none}
.nagm-row.busy{opacity:.55}
.nagm-row.done{background:#f0fdf4}
.nagm-ck{width:16px;height:16px;margin:0;accent-color:#005447}
.nagm-st{display:flex;flex-direction:column;gap:4px;align-items:flex-start}
.nagm-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600}
.nagm-pill.ok{background:#dcfce7;color:#166534}
.nagm-pill.warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.nagm-pill.off{background:#f3f4f6;color:#4b5563}
.nagm-seen{font-size:13px;color:#6b7280;line-height:1.3}
.nagm-id{min-width:0;display:flex;flex-direction:column;gap:5px}
.nagm-l1{display:flex;align-items:baseline;gap:10px;min-width:0}
.nagm-l1 b{font-size:15px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:0 1 auto;min-width:0}
.nagm-desc{font-size:13px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0;flex:1 1 0}
.nagm-l2{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px;color:#374151}
.nagm-l2 .nagr-mono{font-family:monospace;font-size:13px;background:#f3f4f6;border-radius:4px;padding:1px 6px}
.nagm-mute{color:#6b7280}
.nagm-l3{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.nagm-also{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;flex-wrap:wrap}
.nagm-gchip{font-size:13px;color:#374151;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:0 7px}
.nagm-bell{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:999px;padding:0 8px}
.nagm-act{display:flex;flex-direction:column;align-items:flex-end;gap:4px;text-align:right}
.nagm-add{display:inline-flex;align-items:center;gap:6px;border:1px solid #bfd4d1;background:#fff;color:#005447;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.nagm-add:hover{background:#e6eeed;color:#003d33}
.nagm-next{font-size:13px;color:#6b7280;line-height:1.35}
.nagm-ok{color:#166534;font-weight:600;font-size:13px}
.nagm-empty{text-align:center;padding:36px 16px;display:flex;flex-direction:column;align-items:center;gap:6px}
.nagm-empty>span{width:48px;height:48px;border-radius:50%;background:#f2f6f6;color:#005447;display:flex;align-items:center;justify-content:center;font-size:19px}
.nagm-empty b{font-size:16px;color:#111827}
.nagm-empty p{margin:0 0 6px 0;color:#6b7280;font-size:13px;max-width:440px;line-height:1.55}
.nagm-link{color:#005447;font-weight:600;text-decoration:underline;cursor:pointer;font-size:13px}
.nagm-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-top:14px;border-top:1px solid #eef0f3;margin-top:4px;font-size:13px;color:#6b7280}
.nagm-foot b{color:#111827}
.nagm-fb{display:flex;gap:10px}
.nagm-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #005447;background:#005447;color:#fff;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none}
.nagm-btn:hover{background:#003d33;color:#fff;text-decoration:none}
.nagm-btn.sec{background:#fff;color:#374151;border-color:#d1d5db}
.nagm-btn.sec:hover{background:#f3f4f6;color:#111827}
.nagm-btn[disabled]{opacity:.5;cursor:not-allowed}
.nagm-err{display:flex;gap:10px;align-items:center;padding:14px 16px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-top:14px}
.nagm button:focus-visible,.nagm input:focus-visible,.nagm a:focus-visible{outline:2px solid #005447;outline-offset:2px}
@media(max-width:1000px){.nagm-row{grid-template-columns:24px 130px minmax(0,1fr) 150px}}
@media(prefers-reduced-motion:no-preference){.nagm-row{transition:opacity .15s,background .15s}}
</style>";
}
function group_add_agent_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $id=intval($_GET["group-add-agent-popup"]);
    $m=nagm_model($id);
    $h=array("<div class='nagm'>");
    if(!$m["ok"]){
        $h[]="<div class='nagm-err' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>{nagm_load_err}</span>"
            ."<a class='nagm-link' href='javascript:blur();' OnClick=\"LoadAjax(this.closest('.nagm').parentNode.id,'$page?group-add-agent-popup=$id&function=$function');\">{nagm_retry}</a></div></div>";
        echo nagm_style().$tpl->_ENGINE_parse_body(implode("",$h))."<script>NoSpinner();</script>";
        return true;
    }
    $h[]=nagm_header($m);
    $h[]=nagm_explain();
    if(count($m["agents"])==0){
        $h[]=nagm_empty($m);
    }else{
        $h[]=nagm_toolbar($m);
        $h[]="<div class='nagm-list' id='nagm-list'>";
        foreach($m["agents"] as $a){$h[]=nagm_row($m,$a);}
        $h[]="<div class='nagm-empty' id='nagm-nomatch' hidden><span aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span><b id='nagm-nomatch-t'></b><p>{nagm_nomatch_text}</p>"
            ."<a class='nagm-link' href='javascript:blur();' OnClick='NagmClear()'>{nagm_clear}</a></div>";
        $h[]="</div>";
    }
    $h[]=nagm_footer($m);
    $h[]="</div>";
    echo nagm_style().$tpl->_ENGINE_parse_body(implode("",$h)).nagm_js($m,$function);
    return true;
}
// POST nagm-add=<groupe>&agents=1,2,3 : ajoute les agents un par un, répond {added:[…],errors:[…]}.
function nagm_add_save():bool{
    if(!nagr_can_write()){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
        return false;
    }
    $gid=intval($_POST["nagm-add"]);
    $ids=array();
    foreach(explode(",",(string)($_POST["agents"] ?? "")) as $v){$v=intval($v);if($v>0){$ids[$v]=true;}}
    $out=array("added"=>array(),"errors"=>array());
    if($gid<=0 || count($ids)==0){echo json_encode($out);return false;}
    foreach(array_keys(array_slice($ids,0,500,true)) as $aid){
        $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/groups/$gid/agents/$aid",array()));
        if(is_object($json) && property_exists($json,"Status") && !$json->Status){
            $out["errors"][]="#$aid: ".(property_exists($json,"Error")?(string)$json->Error:"error");
            continue;
        }
        if($json===null){$out["errors"][]="#$aid: API unreachable";continue;}
        $out["added"][]=$aid;
    }
    if(count($out["added"])>0){admin_tracks("Added agent(s) #".implode(",#",$out["added"])." to group #$gid");}
    header("content-type: application/json");
    echo json_encode($out);
    return true;
}


// ════════════════════════════════════════════════════════════════
// GROUP UPGRADE
// ════════════════════════════════════════════════════════════════

function group_upgrade_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-upgrade-js"]);
    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    return $tpl->js_dialog5("<i class='".ico_arrow_up."'></i> {update_agent} v$CurVer","$page?group-upgrade-popup=$id",700);
}

function group_upgrade_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-upgrade-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        echo $tpl->div_error("{error}");
        return false;
    }
    $name=htmlspecialchars($json->name);
    $count=intval($json->agent_count??0);

    $f=array();
    $f[]="<div style='background:#f9f9f9;border:1px solid #e7eaec;border-radius:3px;padding:12px 15px;margin-bottom:15px'>";
    $f[]="  <i class='".ico_group."' style='color:#1ab394'></i>&nbsp;&nbsp;<strong>$name</strong>";
    $f[]="  &nbsp;&nbsp;<span class='badge' style='background:#1ab394;color:#fff'>$count {agents_list}</span>";
    $f[]="</div>";

    $f[]="<div style='text-align:center;margin:25px 0'>";
    $f[]="  <a href='javascript:void(0)' onclick=\"LoadAjax('group-upgrade-result','$page?group-upgrade-api=$id')\" class='btn btn-lg btn-primary'>";
    $f[]="    <i class='".ico_arrow_up."'></i>&nbsp;&nbsp;{update_agent}";
    $f[]="  </a>";
    $f[]="</div>";
    $f[]="<div id='group-upgrade-result'></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function group_upgrade_api():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["group-upgrade-api"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/upgrade/group/$id",array()));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->div_error($json->Error??"Error");
        return false;
    }

    $upgrade_id="";
    if(is_object($json)&&property_exists($json,"upgrade_id")){
        $upgrade_id=$json->upgrade_id;
    }

    $f=array();
    $f[]=$tpl->div_success("{success} — Upgrade started");
    if(!empty($upgrade_id)){
        $f[]="<div id='group-upgrade-progress'></div>";
        $enc_uid=urlencode($upgrade_id);
        $f[]="<script>";
        $f[]="function grpUpgPoll(){LoadAjax('group-upgrade-progress','$page?group-upgrade-result=$enc_uid');}";
        $f[]="grpUpgPoll();";
        $f[]="setInterval(grpUpgPoll,5000);";
        $f[]="</script>";
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    admin_tracks("Started group upgrade for group #$id");
    return true;
}

function group_upgrade_result():bool{
    $tpl=new template_admin();
    $uid=urldecode($_GET["group-upgrade-result"]);
    $enc=urlencode($uid);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/upgrade/group/status/$enc"));
    if(!is_object($json)){
        echo $tpl->div_warning("{no_data}");
        return true;
    }

    $state=$json->state??"unknown";
    $total=intval($json->total_agents??0);
    $success=intval($json->success_count??0);
    $fail=intval($json->fail_count??0);
    $pending=intval($json->pending_count??0);

    $state_class=($state==="completed")?"success":(($state==="running")?"info":"warning");

    $f=array();
    $f[]="<div class='row' style='margin-bottom:10px'>";
    $f[]="  <div class='col-md-3'><span class='label label-$state_class'>".strtoupper($state)."</span></div>";
    $f[]="  <div class='col-md-3'><span class='badge' style='background:#1ab394;color:#fff'>$success</span> {success}</div>";
    $f[]="  <div class='col-md-3'><span class='badge' style='background:#ed5565;color:#fff'>$fail</span> {failed}</div>";
    $f[]="  <div class='col-md-3'><span class='badge' style='background:#f8ac59;color:#fff'>$pending</span> {pending}</div>";
    $f[]="</div>";

    // Per-agent results
    if(isset($json->agents)&&is_array($json->agents)&&count($json->agents)>0){
        $f[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
        $f[]="<thead><tr><th>{hostname}</th><th>{status}</th><th>{version}</th></tr></thead><tbody>";
        foreach($json->agents as $a){
            $h=htmlspecialchars($a->hostname??"-");
            $s=$a->state??"pending";
            $sc=($s==="completed"||$s==="success")?"primary":(($s==="failed"||$s==="error")?"danger":"warning");
            $v=htmlspecialchars($a->version??"");
            $f[]="<tr><td>$h</td><td><span class='label label-$sc'>".strtoupper($s)."</span></td><td>$v</td></tr>";
        }
        $f[]="</tbody></table></div></div></div>";
    }

    // If still running, show spinner
    if($state==="running"||$state==="pending"){
        $f[]="<div class='text-center' style='padding:10px;color:#999'>";
        $f[]="  <i class='fa fa-spinner fa-spin'></i>&nbsp;&nbsp;{please_wait}...";
        $f[]="</div>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ════════════════════════════════════════════════════════════════
// RENEW CERTIFICATES FOR GROUP
// ════════════════════════════════════════════════════════════════

function group_renew_certs():bool{
    $tpl=new template_admin();
    $id=intval($_GET["group-renew-certs"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/certs/renew/group/$id",array()));

    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->div_error($json->Error??"Error");
        return false;
    }

    $total=intval($json->total??0);
    $success=intval($json->success_count??0);
    $fail=intval($json->fail_count??0);

    $f=array();
    $f[]="<div class='ibox' style='margin-top:10px'>";
    $f[]="  <div class='ibox-title'><h5><i class='".ico_lock."' style='color:#f8ac59'></i>&nbsp;&nbsp;{certificate} {refresh}</h5></div>";
    $f[]="  <div class='ibox-content'>";

    // Summary
    $f[]="<div class='row' style='margin-bottom:15px'>";
    $f[]="  <div class='col-md-4 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700'>$total</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{total}</div>";
    $f[]="  </div>";
    $f[]="  <div class='col-md-4 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700;color:#1ab394'>$success</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{success}</div>";
    $f[]="  </div>";
    $f[]="  <div class='col-md-4 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700;color:#ed5565'>$fail</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{failed}</div>";
    $f[]="  </div>";
    $f[]="</div>";

    // Per-agent results
    if(isset($json->results)&&is_array($json->results)&&count($json->results)>0){
        $f[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
        $f[]="<thead><tr><th>{hostname}</th><th>{status}</th><th>{message}</th></tr></thead><tbody>";
        foreach($json->results as $r){
            $h=htmlspecialchars($r->hostname??"-");
            $ok=$r->success??false;
            $msg=htmlspecialchars($r->message??"");
            $sc=$ok?"primary":"danger";
            $sl=$ok?"{success}":"{failed}";
            $f[]="<tr><td>$h</td><td><span class='label label-$sc'>$sl</span></td><td class='text-muted'>$msg</td></tr>";
        }
        $f[]="</tbody></table></div></div></div>";
    }

    $f[]="  </div>";
    $f[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    admin_tracks("Renewed certificates for group #$id: $success/$total succeeded");
    return true;
}

// ════════════════════════════════════════════════════════════════
// GROUP PACKAGES STATS
// ════════════════════════════════════════════════════════════════

function group_packages():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["group-packages"]);

    // Trigger fetch first
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/packages/fetch/group/$id",array());

    // Get stats
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/group/$id?details=1"));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->div_error($json->Error??"Error");
        return false;
    }

    $total_agents=intval($json->total_agents??0);
    $total_upgradable=intval($json->total_upgradable??0);

    $f=array();
    $f[]="<div class='ibox' style='margin-top:10px'>";
    $f[]="  <div class='ibox-title'><h5><i class='".ico_box."' style='color:#1c84c6'></i>&nbsp;&nbsp;{packages}</h5></div>";
    $f[]="  <div class='ibox-content'>";

    // Summary
    $f[]="<div class='row' style='margin-bottom:15px'>";
    $f[]="  <div class='col-md-6 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700'>$total_agents</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{agents_list}</div>";
    $f[]="  </div>";
    $f[]="  <div class='col-md-6 text-center'>";
    $upgColor=$total_upgradable>0?"#f8ac59":"#1ab394";
    $f[]="    <div style='font-size:28px;font-weight:700;color:$upgColor'>$total_upgradable</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{packages}</div>";
    $f[]="  </div>";
    $f[]="</div>";

    // Per-agent breakdown
    if(isset($json->agent_summaries)&&is_array($json->agent_summaries)&&count($json->agent_summaries)>0){
        $f[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
        $f[]="<thead><tr><th>{hostname}</th><th>{packages}</th><th>{status}</th></tr></thead><tbody>";
        foreach($json->agent_summaries as $s){
            $h=htmlspecialchars($s->hostname??"-");
            $cnt=intval($s->upgradable_count??0);
            $has_err=$s->has_error??false;
            if($has_err){
                $badge="<span class='label label-danger'>{error}</span>";
            }elseif($cnt>0){
                $badge="<span class='badge' style='background:#f8ac59;color:#fff'>$cnt</span>";
            }else{
                $badge="<span class='badge' style='background:#1ab394;color:#fff'>0</span>";
            }
            $st=$has_err?htmlspecialchars($s->error_message??""):"<span class='text-muted'>OK</span>";
            $f[]="<tr><td>$h</td><td>$badge</td><td>$st</td></tr>";
        }
        $f[]="</tbody></table></div></div></div>";
    }

    $f[]="  </div>";
    $f[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}
