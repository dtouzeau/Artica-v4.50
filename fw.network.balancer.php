<?php
// Link balancer : répartition et bascule entre plusieurs liens WAN.
// Toutes les écritures passent par l'API du daemon (/firewall/config/linkbalance),
// qui applique sans bouton (moteur afirewall/linkbalancer).
// Migré sur le groupe de passerelles (GET /system/routing/api/lb, lot 5) : les liens se règlent dans l'onglet
// Groupes de passerelles du routage, la page les montre en lecture seule ; link_balance n'est plus lu par le
// répartiteur, ses modifications sont refusées. Revert / resume / migrate par POST /system/routing/api/lb/<action>.
// Daemon sans la route lb (mode unknown) : page historique, sans boutons de migration.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
include_once(dirname(__FILE__)."/ressources/class.routing.api.inc");
include_once(dirname(__FILE__)."/ressources/class.routing.lb.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["edit"])){edit_js();exit;}
if(isset($_GET["popup"])){edit_popup();exit;}
if(isset($_POST["ID"])){edit_save();exit;}
if(isset($_GET["enable"])){link_enable();exit;}
if(isset($_GET["switch"])){balancer_switch();exit;}
if(isset($_GET["lb-switch"])){lb_switch_js();exit;}
if(isset($_POST["lb-switch-confirm"])){lb_switch_confirm();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_LINK_BALANCER}","fas fa-bezier-curve","{APP_LINK_BALANCER_EXPLAIN}",
        "$page?table=yes","link-balancer","progress-lb-restart",false,"table-loader");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_LINK_BALANCER}",$html);
        echo $tpl->build_firewall();return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function lb_status():array{
    $st=fwapi_data(fwapi_get("linkbalance/status"));
    $res=array("active"=>false,"balancing"=>false,"links"=>array());
    if(!is_array($st)){return $res;}
    $res["active"]=!empty($st["active"]);
    $res["balancing"]=!empty($st["balancing"]);
    if(isset($st["links"]) && is_array($st["links"])){
        foreach($st["links"] as $l){$res["links"][intval($l["id"])]=$l;}
    }
    return $res;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $r=rtlb_get();
    $mode=rtlb_mode($r);
    $lb=($mode==="unknown")?array():$r["data"];
    $st=lb_status();
    $html=array();
    if(!$st["active"]){
        $html[]=$tpl->div_explain("{lb_switched_off}");
    }elseif(!$st["balancing"] && $mode!=="group"){
        $html[]=$tpl->div_warning("{lb_needs_two_links}");
    }
    $notices=rtlb_notices($lb);
    foreach($notices["warning"] as $w){$html[]=$tpl->div_warning($w);}
    foreach($notices["info"] as $i){$html[]=$tpl->div_explain($i);}
    if($mode==="group"){
        // liens réglés dans le groupe : lecture seule, aucune modification de link_balance
        $html[]=rtlb_members_html($lb);
    }else{
        $html[]=legacy_links_html($tpl,$page,$st);
    }

    $topbuttons=rtlb_buttons($lb,$page,$st["active"]);
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_LINK_BALANCER}";
    $TINY_ARRAY["ICO"]="fas fa-bezier-curve";
    $TINY_ARRAY["EXPL"]="{APP_LINK_BALANCER_EXPLAIN}";
    $TINY_ARRAY["URL"]="link-balancer";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $html[]="<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

/** Tableau historique des liens (link_balance), modifiable : modes legacy, reverted et unknown. */
function legacy_links_html($tpl,string $page,array $st):string{
    $list=fwapi_data(fwapi_get("linkbalance"));
    if(!is_array($list)){
        return $tpl->div_error(fwapi_error(fwapi_get("linkbalance")));
    }
    $html=array();
    $html[]="<table class='table table-stripped'>";
    $html[]="<thead><tr><th>{interface}</th><th>{status}</th><th>{weight}</th><th>{check}</th><th>{enabled}</th></tr></thead><tbody>";
    foreach($list as $e){
        $ID=intval($e["id"]);
        $iface=htmlspecialchars((string)$e["interface"]);
        $state="<span class='label'>-</span>";
        if(isset($st["links"][$ID]) && intval($e["enabled"])==1){
            $l=$st["links"][$ID];
            $since=htmlspecialchars(substr((string)($l["since"] ?? ""),0,19));
            $state=!empty($l["up"])
                ? "<span class='label label-primary'>{lb_state_up}</span> <small>".intval($l["rtt_ms"])." ms</small>"
                : "<span class='label label-danger'>{lb_state_down}</span> <small>{since} $since</small>";
        }
        $probs="";
        if(isset($e["problems"]) && is_array($e["problems"])){
            foreach($e["problems"] as $p){$probs.="<br><small class='text-danger'>{$p["error"]}</small>";}
        }
        $check=$e["checkytype"]=="alwayson"?"{lb_always_on}":"ping ".htmlspecialchars((string)$e["checkaddr"]);
        $edit="Loadjs('$page?edit=$ID');";
        $enable=$tpl->icon_check(intval($e["enabled"]),"Loadjs('$page?enable=$ID')",null,"AsFirewallManager");
        $html[]="<tr><td><a href=\"javascript:blur();\" OnClick=\"$edit\"><strong>$iface</strong></a>$probs</td>";
        $html[]="<td>$state</td><td>".intval($e["weight"])."</td><td>$check</td><td style='width:1%'>$enable</td></tr>";
    }
    $html[]="</tbody></table>";
    return implode("\n",$html);
}

function edit_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["edit"]);
    $tpl->js_dialog2("{APP_LINK_BALANCER}","$page?popup=$ID");
}

function edit_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["popup"]);
    $e=fwapi_data(fwapi_get("linkbalance/$ID"));
    if(!is_array($e)){echo $tpl->div_error("{fw_rule_not_found}");return;}
    $types=array("ping"=>"Ping","alwayson"=>"{lb_always_on}");
    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_array_hash($types,"checkytype","{check}",$e["checkytype"]);
    $form[]=$tpl->field_ipv4("checkaddr","{lb_check_address}",$e["checkaddr"]);
    $form[]=$tpl->field_numeric("weight","{weight}",intval($e["weight"]));
    echo $tpl->form_outside(htmlspecialchars((string)$e["interface"]),@implode("\n",$form),"{lb_weight_explain}","{apply}",
        "dialogInstance2.close();LoadAjax('table-loader','$page?table=yes');","AsFirewallManager");
}

function edit_save(){
    $tpl=new template_admin();
    if(rtlb_mode(rtlb_get())==="group"){echo $tpl->post_error("{lb_grp_edit_in_group}");return;}
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $data=array(
        "checkytype"=>(string)($_POST["checkytype"] ?? "ping"),
        "checkaddr"=>trim((string)($_POST["checkaddr"] ?? "")),
        "weight"=>intval($_POST["weight"] ?? 1),
    );
    $e=fwapi_error(fwapi_send("PUT","linkbalance/$ID",$data));
    if($e!=""){echo $tpl->post_error($e);return;}
    admin_tracks("Link balancer: link #$ID saved (weight {$data["weight"]}, {$data["checkytype"]} {$data["checkaddr"]})");
}

function link_enable(){
    $tpl=new template_admin();
    if(rtlb_mode(rtlb_get())==="group"){$tpl->js_error("{lb_grp_edit_in_group}");return;}
    $page=CurrentPageName();
    $ID=intval($_GET["enable"]);
    $cur=fwapi_data(fwapi_get("linkbalance/$ID"));
    if(!is_array($cur)){$tpl->js_error("{fw_rule_not_found}");return;}
    $new=intval($cur["enabled"])==1?0:1;
    $e=fwapi_error(fwapi_send("POST","linkbalance/$ID/enable",array("enabled"=>$new)));
    if($e!=""){$tpl->js_error($e);return;}
    admin_tracks("Link balancer: link #$ID enabled=$new");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader','$page?table=yes');\n";
}

function balancer_switch(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $v=intval($_GET["switch"])==1?1:0;
    $e=fwapi_error(fwapi_send("POST","linkbalance/enable",array("enabled"=>$v)));
    if($e!=""){$tpl->js_error($e);return;}
    admin_tracks("Link balancer: switched ".($v==1?"on":"off"));
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader','$page?table=yes');\n";
}

/** Revert (confirmé), resume, migrate du répartiteur sur le groupe : liste blanche, puis rechargement. */
function lb_switch_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $action=(string)$_GET["lb-switch"];
    if(!in_array($action,rtlb_actions(),true)){$tpl->js_error("{routing_err_invalid}");return;}
    if($action==="revert"){
        // l'ancien répartiteur relit link_balance tel que la migration l'a laissé
        $tpl->js_confirm_execute("{lb_grp_revert_confirm}","lb-switch-confirm","revert","LoadAjax('table-loader','$page?table=yes');");
        return;
    }
    $data=array();
    $err=lb_switch_apply($action,$data);
    if($err!==""){$tpl->js_error($err);return;}
    // migrate : le daemon arme la migration, faite au prochain passage du répartiteur (la page reste historique d'ici là)
    if($action==="migrate" && ($data["migration_requested"] ?? false)===true){
        $tpl->js_ok("{lb_grp_migration_requested}","LoadAjax('table-loader','$page?table=yes');");
        return;
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader','$page?table=yes');\n";
}

/** POST de la confirmation du revert : rien en cas de succès, le texte de l'erreur sinon. */
function lb_switch_confirm(){
    $tpl=new template_admin();
    if((string)$_POST["lb-switch-confirm"]!=="revert"){echo $tpl->_ENGINE_parse_body("{routing_err_invalid}");return;}
    $err=lb_switch_apply("revert");
    if($err!==""){echo $tpl->_ENGINE_parse_body($err);}
}

/**
 * Appel du daemon ; "" en cas de succès, sinon le texte de l'erreur (HTML-safe, jetons à traduire).
 * $data reçoit les données de la réponse (GET lb, plus migration_requested pour migrate).
 */
function lb_switch_apply(string $action,array &$data=array()):string{
    $data=array();
    if(!in_array($action,rtlb_actions(),true)){return "{routing_err_invalid}";}
    $r=rtapi_post("/system/routing/api/lb/$action",array());
    if(empty($r["ok"])){return rtapi_h(rtapi_error_text($r));}
    $data=is_array($r["data"] ?? null)?$r["data"]:array();
    admin_tracks("Link balancer: $action");
    return "";
}
