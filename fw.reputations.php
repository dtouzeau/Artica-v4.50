<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["quick-test"])){rpl_quick_test();exit;}
if(isset($_GET["check-rule-perform"])){checkrule_perform();exit;}
if(isset($_GET["check-rule-popup"])){checkrule_popup();exit;}
if(isset($_GET["check-rule"])){checkrule_js();exit;}
if(isset($_POST["reset"])){reset_perform();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_GET["group-move-js"])){group_move_js();exit;}
if(isset($_POST["delete-rule"])){delete_rule_perform();exit;}
if(isset($_GET["delete-rule"])){delete_rule();exit;}
if(isset($_GET["enable-rule"])){enable_rule();exit;}
if(isset($_POST["delete-group"])){delete_group_perform();exit;}
if(isset($_GET["delete-group"])){delete_group();exit;}
if(isset($_GET["link-group"])){group_link();exit;}
if(isset($_GET["group-link-popup"])){group_link_popup();exit;}
if(isset($_GET["group-link"])){group_link_js();exit;}
if(isset($_POST["unlink-group"])){unlink_group_perform();exit;}
if(isset($_GET["unlink-group"])){unlink_group();exit;}
if(isset($_GET["enable-group"])){enable_group();exit;}
if(isset($_GET["enable-service"])){enable_service();exit;}
if(isset($_GET["delete-service"])){delete_service();exit;}
if(isset($_POST["delete-service"])){delete_service_perform();exit;}
if(isset($_POST["serviceid"])){service_save();exit;}
if(isset($_GET["service-popup"])){service_popup();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["services-search"])){services_list();exit;}
if(isset($_GET["popup-search"])){popup_search();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-tab"])){rule_tab();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_POST["groupid"])){group_save();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}
if(isset($_GET["groups-popup"])){groups_popup();exit;}
if(isset($_GET["groups-list"])){groups_list();exit;}
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-popup"])){group_popup();exit;}
if(isset($_GET["group-tab"])){group_tab();exit;}
if(isset($_GET["services-popup"])){services_popup();exit;}
if(isset($_GET["services-button"])){services_button();exit;}

page();

/* =====================================================================
 * Socle des tableaux d'entites de cette page.
 * Le cadre, la carte et le pager viennent de awc_table_style() /
 * awc_pager_js() (class.statistics_appliance.inc, partages).
 * ===================================================================== */

/** En-tete + ouverture du tableau d'entites. */
function rep_table_open($columns,$rowsId):string{
    $h=array();
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    foreach($columns as $col){ $h[]=$col; }
    $h[]="</tr></thead><tbody id='$rowsId'>";
    return @implode("\n",$h);
}

/** Pied du tableau: compteur a gauche, pager a droite. */
function rep_table_close($shown,$total,$unit,$pagerId):string{
    $h=array();
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'>";
    $h[]="<span>{showing} $shown / $total $unit</span>";
    $h[]="<span class='awc-pg' id='$pagerId'></span>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

/** Ligne vide normalisee (jamais un <tbody> vide). */
function rep_table_empty($colspan):string{
    return "<tr><td colspan='$colspan'><div class='awc-empty'>{no_data}</div></td></tr>";
}

/**
 * Bloc d'identite: pastille d'icone, nom (deja rendu, il porte souvent son propre
 * lien via td_href), metadonnees.
 */
function rep_ident($ico,$name,$meta=array(),$off=false):string{
    $icocls=$off?"awc-ico awc-ico-off":"awc-ico";
    $namecls=$off?"awc-name awc-name-off":"awc-name";
    $h ="<div class='awc-ident'>";
    $h.="<span class='$icocls'><i class='$ico'></i></span>";
    $h.="<span><span class='$namecls'>$name</span>";
    if(count($meta)>0){
        $h.="<div class='awc-meta'>".@implode(" &middot; ",$meta)."</div>";
    }
    $h.="</span></div>";
    return $h;
}

/** Pastille d'etat activee / desactivee. */
function rep_state_badge($enabled):string{
    if(intval($enabled)==1){return "<span class='awc-badge awc-badge-on'>{enabled}</span>";}
    return "<span class='awc-badge awc-badge-off'>{disabled}</span>";
}

/** Cellule d'actions alignee a droite. */
function rep_acts($items):string{
    if(!is_array($items)){$items=array($items);}
    $out=array();
    foreach($items as $i){
        if(is_null($i)){continue;}
        if(strlen(trim($i))==0){continue;}
        $out[]=$i;
    }
    return "<div class='awc-acts'>".@implode("",$out)."</div>";
}

/* =====================================================================
 * Client de l'API d'administration des reputations (articarest,
 * /reputations/admin/*). Aucun acces SQLite direct depuis cette page :
 * le daemon recharge ses caches et previent reverse-proxy lui-meme apres
 * chaque ecriture.
 * ===================================================================== */

/**
 * Decode une reponse {"Status":bool,"Info":...,"Error":"..."}.
 * Rend array($info,$err) ; $err n'est JAMAIS vide quand l'appel a echoue :
 * une reponse non JSON (« Not Found » d'un daemon plus ancien que la route,
 * page HTML, erreur curl) est rendue telle quelle, tronquee.
 */
function rep_api_decode($resp):array{
    $raw=(string)$resp;
    $r=json_decode($raw,true);
    if(!is_array($r)){
        $txt=trim(str_replace(array("'","\\"),array(" "," "),strip_tags($raw)));
        if($txt==""){return array(null,"{empty_response}");}
        return array(null,substr($txt,0,300));
    }
    if(!array_key_exists("Status",$r)){
        // Enveloppe d'erreur curl de REST_API_POST_JSON: {"error":"..."}
        if(isset($r["error"]) && trim((string)$r["error"])!=""){return array(null,(string)$r["error"]);}
        if(isset($r["Error"]) && trim((string)$r["Error"])!=""){return array(null,(string)$r["Error"]);}
        return array(null,"{unknown_error}");
    }
    if(!$r["Status"]){
        // 423 du verrou Artica Meta (repAdminLocked) : refus traduit, jamais le texte du daemon.
        if(!empty($r["meta_locked"])){return array(null,"{dnsfw_meta_locked}");}
        $e=isset($r["Error"])?trim((string)$r["Error"]):"";
        if($e==""){$e="{unknown_error}";}
        return array(null,$e);
    }
    return array(isset($r["Info"])?$r["Info"]:null,"");
}
function rep_api_get($path):array{
    return rep_api_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path));
}
function rep_api_post($path,$data=array()):array{
    return rep_api_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($path,$data));
}
/** Echappement HTML des valeurs venues de l'API (noms, descriptions, URI). */
function rep_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}
/** Identifiant de ligne (md5) injecte dans $('#...') : hexadecimal uniquement. */
function rep_md():string{
    return preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
}
/** Liste renvoyee par l'API : un tableau nil cote Go arrive en null. */
function rep_list($info):array{
    return is_array($info)?$info:array();
}

/* =====================================================================
 * Verrou Artica Meta (politique rbl, 2026-11-16).
 * Les regles, groupes et serveurs ecrits par Meta portent "metaid" dans les
 * reponses de /reputations/admin/* ; le daemon refuse (423, meta_locked) toute
 * ecriture qui les vise (reputations.IsMetaLocked), ainsi que la remise a zero.
 * La page les montre en lecture seule et ne propose aucune action d'ecriture.
 * Une regle redevenue locale (former_metaid, metaid vide) reste modifiable.
 * ===================================================================== */
function rep_meta($row):bool{
    if(!is_array($row)){return false;}
    return trim((string)($row["metaid"] ?? ""))!=="";
}
function rep_former_meta($row):bool{
    if(!is_array($row) || rep_meta($row)){return false;}
    return trim((string)($row["former_metaid"] ?? ""))!=="";
}
/** Lien regle-groupe tenu : regle Meta, ou lien ecrit par Meta (GuardLink). */
function rep_meta_link($rule,$group):bool{
    if(rep_meta($rule)){return true;}
    return is_array($group) && trim((string)($group["link_metaid"] ?? ""))!=="";
}
/** Regle ou groupe lu une fois par requete ; lecture en echec = non tenu (le daemon refuse de toute facon, 423). */
function rep_meta_cached($kind,$id):bool{
    $id=intval($id);
    if($id<=0){return false;}
    $k="REP_META_{$kind}_$id";
    if(!array_key_exists($k,$GLOBALS)){
        list($row,$err)=rep_api_get("/reputations/admin/$kind/$id");
        $GLOBALS[$k]=($err==="" && rep_meta($row));
    }
    return $GLOBALS[$k];
}
function rep_rule_locked($ruleid):bool{ return rep_meta_cached("rule",$ruleid); }
function rep_group_locked($groupid):bool{ return rep_meta_cached("group",$groupid); }
/** Serveur tenu : serveur Meta, ou serveur d'un groupe Meta (GuardService). */
function rep_service_locked($service):bool{
    if(rep_meta($service)){return true;}
    return is_array($service) && rep_group_locked(intval($service["groupid"] ?? 0));
}
/** Pastille d'une ligne Meta ou redevenue locale ; "" pour une ligne locale. A passer dans _ENGINE_parse_body. */
function rep_meta_badge($row,$locked="{dnsfw_meta_row_locked}"):string{
    if(rep_meta($row)){
        return "<span class='label label-info rep-meta-badge' style='margin-left:6px;white-space:nowrap' title=\"$locked\"><i class='fa-solid fa-lock' aria-hidden='true'></i>&nbsp;Artica Meta</span>";
    }
    if(rep_former_meta($row)){
        return "<span class='label label-default rep-meta-badge' style='margin-left:6px;white-space:nowrap' title=\"{rblp_node_former_meta_explain}\">{rblp_node_former_meta}</span>";
    }
    return "";
}
/** Bandeau "gere par Artica Meta". A passer dans _ENGINE_parse_body. */
function rep_meta_banner($explain="{rblp_node_managed_explain}"):string{
    return "<div class='alert alert-info rep-meta-banner' role='status' style='margin:10px 0'><i class='fa-solid fa-lock' aria-hidden='true'></i>&nbsp;<strong>{rblp_node_managed}</strong><div style='margin-top:5px'>$explain</div></div>";
}
/** Formulaire d'une ligne tenue : bouton grise, enregistrement neutralise. */
function rep_meta_lock_form($tpl):bool{
    $tpl->FORM_LOCKED=true;
    $tpl->this_form_locked_explain="<i class='fa-solid fa-lock'></i>&nbsp;{dnsfw_meta_locked}";
    return true;
}
/** Refus d'un handler Loadjs. */
function rep_meta_refuse_js():bool{
    (new template_admin())->js_error("{dnsfw_meta_locked}");
    return false;
}
/** Refus d'un POST de form_outside (jserror:). */
function rep_meta_refuse_post():bool{
    echo (new template_admin())->post_error("{dnsfw_meta_locked}");
    return false;
}

function reset_js(){
    if(!rpr_require_admin(true)){return;}
    // La remise a zero supprime toutes les regles : refusee tant qu'une regle Meta existe.
    list($rules,$err)=rep_api_get("/reputations/admin/rules");
    if($err!==""){(new template_admin())->js_error($err);return;}
    foreach(rep_list($rules) as $rule){if(rep_meta($rule)){rep_meta_refuse_js();return;}}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl->js_confirm_delete("<strong style='font-size:16px'>".rpl_t("rpl_restore")."</strong><br><br>".rpl_t("rpl_restore_text"),"reset","yes",rpr_reload($function),true);
}
function reset_perform(){
    if(!rpr_require_admin(false)){return;}
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/reputations/reset");
    $reply=json_decode($raw,true);
    // Legacy reset leaves Status=false even on success; Error is authoritative here.
    if(!is_array($reply)){echo rpr_delete_error(trim($raw)!==""?$raw:"{empty_response}");return;}
    if(!empty($reply["meta_locked"])){echo rpr_delete_error("{dnsfw_meta_locked}");return;}
    if(!empty($reply["Error"])){echo rpr_delete_error($reply["Error"]);return;}
    admin_tracks("Reset reputation rules and cache");
}
function checkrule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["check-rule"]);
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    $title=rep_h($rule["rulename"]);
    $tpl->js_dialog2($title, "$page?check-rule-popup=$ID&");
    return true;
}
function checkrule_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["check-rule-popup"]);
    echo $tpl->search_block($page,"","","","&check-rule-perform=$ID");
}
function checkrule_perform():bool{
    $tpl=new template_admin();
    $ipaddr=trim($_GET["search"]);
    if(strlen($ipaddr)<8){
        return false;
    }
    $ID=intval($_GET["check-rule-perform"]);
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
    if($err!=""){
        echo $tpl->div_error($err);
        return false;
    }
    $title=rep_h($rule["rulename"]);

    $modtools=new modesctools();
    $modtools->hostinfo($ipaddr,true);
    $flag="";
    if(strlen($modtools->flag)>3){
        $flag="&nbsp;<img src='img/".$modtools->flag."'>&nbsp;&nbsp;";
    }
    $tpl->table_form_field_text("{hostname}",$flag.$modtools->hostname,ico_computer);
    if(strlen($modtools->city)>1) {
        $tpl->table_form_field_text("{city}", $modtools->city, ico_city);
    }
    $tpl->table_form_field_text("{country}",$modtools->country_name,ico_location);
    $tpl->table_form_field_text("{continent}",$modtools->continent,ico_earth);
    $tpl->table_form_field_text("{isp}",$modtools->isp." AS:$modtools->asn_number",ico_networks);
    $infos=$tpl->_ENGINE_parse_body($tpl->table_form_compile());

    $sock=new sockets();
    $ipaddrH=rep_h($ipaddr);
    $json=json_decode($sock->REST_API("/reputations/check/$ID/".rawurlencode($ipaddr)));
    if(is_object($json) && property_exists($json,"Found") && $json->Found){
        echo $tpl->div_error("{iplisted_in_reputation}||<H1>$title</h1><H3>{found} $ipaddrH</H3>")."<hr>$infos";
        return true;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("notitle:<H1>$title</H1><h3>$ipaddrH {host_not_found}</H1>"))."<hr>$infos";
    return false;
}
function rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ID=intval($_GET["rule-js"]);
    $title="{new_item}";

    if($ID>0){
        list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
        if($err!=""){
            $tpl->js_error($err);
            return false;
        }
        $title=rep_h($rule["rulename"]);
        $tpl->js_dialog2($title, "$page?rule-tab=$ID&function=$function");
        return true;
    }
    return $tpl->js_dialog2($title, "$page?rule-popup=$ID&function=$function");
}
function delete_group_perform():bool{
    if(!rpr_require_admin(false)){return false;}
    $tpl=new template_admin();
    $ID=intval($_POST["delete-group"]);
    list($group,$err)=rep_api_get("/reputations/admin/group/$ID");
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    if(rep_meta($group)){return rep_meta_refuse_post();}
    $GroupName=$group["groupname"];
    list(,$err)=rep_api_post("/reputations/admin/group/$ID/delete");
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    return admin_tracks("Removing reputation group $GroupName");
}
function unlink_group_perform():bool{
    if(!rpr_require_admin(false)){return false;}
    $tpl=new template_admin();
    $ID=intval($_POST["unlink-group"]);
    list($link,$err)=rep_api_get("/reputations/admin/link/$ID");
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    if(rep_rule_locked(intval($link["ruleid"]))){return rep_meta_refuse_post();}
    $rulename=$link["rulename"];
    $GroupName=$link["groupname"];
    list(,$err)=rep_api_post("/reputations/admin/link/$ID/delete");
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    return admin_tracks("Unlink reputation group $GroupName from rule $rulename");
}
function delete_rule():bool{
    if(!rpr_require_admin(true)){return false;}
    $tpl=new template_admin();
    $ID=intval($_GET["delete-rule"]);
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    if(rep_meta($rule)){return rep_meta_refuse_js();}
    $rulename=rep_h($rule["rulename"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $text="<strong style='font-size:16px'>".rep_h(strip_tags(rpl_t("rpl_del_title",(string)$rule["rulename"])))."</strong><br><br>".rpl_t("rpl_del_text");
    return $tpl->js_confirm_delete($text,"delete-rule",$ID,rpr_reload($function),true);
}
function delete_rule_perform():bool{
    if(!rpr_require_admin(false)){return false;}
    $tpl=new template_admin();
    $ID=intval($_POST["delete-rule"]);
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
    if($err!=""){
        echo rpr_delete_error($err);
        return false;
    }
    if(rep_meta($rule)){echo rpr_delete_error("{dnsfw_meta_locked}");return false;}
    $rulename=$rule["rulename"];
    list(,$err)=rep_api_post("/reputations/admin/rule/$ID/delete");
    if($err!=""){
        echo rpr_delete_error($err);
        return false;
    }
    return admin_tracks("Remove reputation rule $rulename");
}


function delete_group():bool{
    if(!rpr_require_admin(true)){return false;}
    $tpl=new template_admin();
    $ID=intval($_GET["delete-group"]);
    list($group,$err)=rep_api_get("/reputations/admin/group/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    if(rep_meta($group)){return rep_meta_refuse_js();}
    $GroupName=rep_h($group["groupname"]);
    $md=rep_md();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    return $tpl->js_confirm_delete("$GroupName","delete-group",$ID,"$('#$md').remove();$function();");
}
function unlink_group():bool{
    if(!rpr_require_admin(true)){return false;}
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["unlink-group"]);
    list($link,$err)=rep_api_get("/reputations/admin/link/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    $ruleid=intval($link["ruleid"]);
    if(rep_rule_locked($ruleid)){return rep_meta_refuse_js();}
    $rulename=rep_h($link["rulename"]);
    $GroupName=rep_h($link["groupname"]);

    $md=rep_md();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));

    return rsg_detach_confirm_js($ID,$link,($function!==""?"$function();":"").rsg_tab_reload($ruleid,$function));
}
function delete_service():bool{
    if(!rpr_require_admin(true)){return false;}
    $tpl=new template_admin();
    $ID=intval($_GET["delete-service"]);
    $refresh=rps_refresh(rps_callback("function")).rps_refresh(rps_callback("function1"));
    list($service,$err)=rep_api_get("/reputations/admin/service/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    if(rep_service_locked($service)){return rep_meta_refuse_js();}
    $uri=rep_h($service["uri"]);
    return $tpl->js_confirm_delete($uri,"delete-service",$ID,$refresh);
}
function enable_group():bool{
    if(!rpr_require_admin(true)){return false;}
    $tpl=new template_admin();
    $ID=intval($_GET["enable-group"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    list($group,$err)=rep_api_get("/reputations/admin/group/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    if(rep_meta($group)){return rep_meta_refuse_js();}
    $GroupName=$group["groupname"];
    list($info,$err)=rep_api_post("/reputations/admin/group/$ID/toggle");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    header("content-type: application/x-javascript");
    if($function!=""){echo "$function();";}
    // L'onglet « Source groups » de la fenetre d'une liste se recharge aussi (avant : badge fige).
    echo rsg_tab_reload(intval($_GET["ruleid"] ?? 0),$function);
    if(intval($info["enabled"])==1){
        return admin_tracks("Enable reputation group $GroupName");
    }
    return admin_tracks("Disable reputation group $GroupName");
}

function enable_rule():bool{
    if(!rpr_require_admin(true)){return false;}
    $tpl=new template_admin();
    $ID=intval($_GET["enable-rule"]);
    if($ID==0){
        $tpl->js_error("ID is zero!");
        return false;
    }
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    if(rep_meta($rule)){return rep_meta_refuse_js();}
    $rulename=$rule["rulename"];
    list($info,$err)=rep_api_post("/reputations/admin/rule/$ID/toggle");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    echo rpr_reload($function);
    if(intval($info["enabled"])==1){
        return admin_tracks("Enable reputation rule $rulename");
    }
    return admin_tracks("Disable reputation rule $rulename");
}
function enable_service():bool{
    if(!rpr_require_admin(true)){return false;}
    $tpl=new template_admin();
    $ID=intval($_GET["enable-service"]);
    list($service,$err)=rep_api_get("/reputations/admin/service/$ID");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    if(rep_service_locked($service)){return rep_meta_refuse_js();}
    $uri=$service["uri"];
    list($info,$err)=rep_api_post("/reputations/admin/service/$ID/toggle");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    header("content-type: application/x-javascript");
    echo rps_refresh(rps_callback("function"));
    if(intval($info["enabled"])==1){
        return admin_tracks("Enable reputation service $uri");
    }
    return admin_tracks("Disable reputation service $uri");
}
function delete_service_perform():bool{
    if(!rpr_require_admin()){return false;}
    $tpl=new template_admin();
    $ID=intval($_POST["delete-service"]);
    list($service,$err)=rep_api_get("/reputations/admin/service/$ID");
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    if(rep_service_locked($service)){return rep_meta_refuse_post();}
    $uri=$service["uri"];
    list(,$err)=rep_api_post("/reputations/admin/service/$ID/delete");
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    return admin_tracks("Deleted reputation service $uri");
}
function service_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $function1=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function1"] ?? ""));
    $ID=intval($_GET["service-js"]);
    $ruleid=intval($_GET["ruleid"]);
    $groupid=intval($_GET["groupid"]);

    list($group,$err)=rep_api_get("/reputations/admin/group/$groupid");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    $GroupName=rep_h($group["groupname"]);
    if($ID==0){
        $title="$GroupName: {new_service}";
    }else{
        $title="$GroupName: {service} #$ID";
    }
    return $tpl->js_dialog4($title, "$page?service-popup=$ID&groupid=$groupid&function=$function&ruleid=$ruleid&function1=$function1");

}
function group_link_js():bool{
    if(!rpr_require_admin(true)){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ruleid=intval($_GET["group-link"]);
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ruleid");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    if(rep_meta($rule)){return rep_meta_refuse_js();}
    $rulename=rep_h($rule["rulename"]);
    return $tpl->js_dialog3("$rulename: {link_group}", "$page?group-link-popup=$ruleid&function=$function");
}
function group_link():bool{
    if(!rpr_require_admin(true)){return false;}

    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $groupid=intval($_GET["link-group"]);
    $ruleid=intval($_GET["ruleid"]);
    if($ruleid==0){
        $tpl->js_error("Rule ID is zero!");
        return false;
    }
    if($groupid==0){
        $tpl->js_error("Group ID is zero!");
        return false;
    }
    $md=rep_md();

    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ruleid");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    list($group,$err)=rep_api_get("/reputations/admin/group/$groupid");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    // Regle Meta, ou groupe Meta lie a une regle locale (GuardLinkCreate).
    if(rep_meta($rule) || rep_meta($group)){return rep_meta_refuse_js();}
    $rulename=$rule["rulename"];
    $GroupName=$group["groupname"];

    // linked=false : le groupe etait deja lie a la regle, ce n'est pas une erreur.
    list($info,$err)=rep_api_post("/reputations/admin/rule/$ruleid/link/$groupid");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    if($function!=""){echo "$function();\n";}
    echo "LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function')";

    if(is_array($info) && !empty($info["linked"])){
        return admin_tracks("Link reputation group $GroupName to the $rulename rule");
    }
    return true;
}

function group_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $title="{new_group}";
    $groupid=intval($_GET["group-js"]);
    $ruleid=intval($_GET["ruleid"]);
    if($groupid>0){
        list($group,$err)=rep_api_get("/reputations/admin/group/$groupid");
        if($err!=""){
            $tpl->js_error($err);
            return false;
        }
        $title=rep_h($group["groupname"]);
        return $tpl->js_dialog3($title, "$page?group-tab=$groupid&function=$function&ruleid=$ruleid");
    }
    return $tpl->js_dialog3($title, "$page?group-popup=$groupid&function=$function&ruleid=$ruleid");
}
function services_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ruleid=intval($_GET["ruleid"]);
    $groupid=intval($_GET["services-popup"]);
    echo rps_style()."<div class='ak rps'><div class='rps-new' id='ButtonServicesFor$groupid'></div>";
    echo $tpl->search_block($page,"","","","&services-search=$groupid&ruleid=$ruleid&function1=$function","");
    echo "</div>";
    return true;
}
function service_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $function1=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function1"] ?? ""));
    $ID=intval($_GET["service-popup"]);
    $ruleid=intval($_GET["ruleid"]);
    $groupid=intval($_GET["groupid"]);
    $BootstrapDialog="dialogInstance4.close();";
    $button="{add}";

    // Valeurs par defaut d'un nouveau service
    $ligne=array(
        "uri"=>"rbl://zen.spamhaus.org",
        "timeout"=>3,
        "noresponse"=>0,
        "responsecode"=>"127.0.0.2",
        "forcedns"=>"0.0.0.0",
    );
    if($ID>0) {
        list($service,$err)=rep_api_get("/reputations/admin/service/$ID");
        if($err!=""){
            echo $tpl->div_error($err);
            return false;
        }
        $ligne=$service;
        $groupid=intval($service["groupid"]);
        $button="{apply}";
    }
    // Serveur Meta, ou serveur (nouveau ou existant) d'un groupe Meta : lecture seule.
    $locked=($ID>0)?rep_service_locked($ligne):rep_group_locked($groupid);
    if($locked){rep_meta_lock_form($tpl);}

    $form=array();
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $form[]=$tpl->field_hidden("groupid",$groupid);
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_text("uri", "{service}", $ligne["uri"]);
    $form[]=$tpl->field_ipv4("responsecode", "{answer}", $ligne["responsecode"]);
    $form[]=$tpl->field_ipv4("forcedns", "{DNSServer}", $ligne["forcedns"]);

    $form[]=$tpl->field_checkbox("noresponse", "{noresponse}", intval($ligne["noresponse"]));
    $form[]=$tpl->field_numeric("timeout", "{timeout} ({seconds})", intval($ligne["timeout"]));
    $refresh=rps_refresh($function).rps_refresh($function1);
    $html=$tpl->form_outside("",$form,"",$button,"$BootstrapDialog$refresh LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function1')","AsDnsAdministrator");
    $banner=$locked?$tpl->_ENGINE_parse_body(rep_meta_banner("{rblp_node_group_locked}")):"";
    echo rps_style()."<div class='rps-form'>".$banner.$tpl->_ENGINE_parse_body($html)."</div>";
    return true;
}
function service_save():bool{
    if(!rpr_require_admin(false,true)){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["serviceid"]);
    if($ID>0){
        list($service,$err)=rep_api_get("/reputations/admin/service/$ID");
        if($err==="" && rep_service_locked($service)){return rep_meta_refuse_post();}
    }elseif(rep_group_locked(intval($_POST["groupid"]))){
        return rep_meta_refuse_post();
    }
    $url=trim((string)$_POST["uri"]);
    $data=array(
        "id"=>$ID,
        "groupid"=>intval($_POST["groupid"]),
        "uri"=>$url,
        "responsecode"=>trim((string)$_POST["responsecode"]),
        "noresponse"=>intval($_POST["noresponse"]),
        "forcedns"=>trim((string)$_POST["forcedns"]),
        "timeout"=>intval($_POST["timeout"]),
    );
    list(,$err)=rep_api_post("/reputations/admin/service",$data);
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    if($ID==0){
        return admin_tracks("Create new reputation element $url");
    }
    return admin_tracks("Update reputation rule $url");
}

function group_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ID=intval($_GET["group-popup"]);
    $ruleid=intval($_GET["ruleid"]);
    $BootstrapDialog="dialogInstance3.close();";
    $button="{add}";
    $ligne=array("groupname"=>"","description"=>"");
    if($ID>0){
        $button="{apply}";
        list($group,$err)=rep_api_get("/reputations/admin/group/$ID");
        if($err!=""){
            echo $tpl->div_error($err);
            return false;
        }
        $ligne=$group;
    }
    // Groupe Meta, ou nouveau groupe sous une regle Meta (GuardGroupCreate) : lecture seule.
    $locked=($ID>0)?rep_meta($ligne):rep_rule_locked($ruleid);
    if($locked){rep_meta_lock_form($tpl);}

    $form[]=$tpl->field_hidden("groupid",$ID);
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_text("GroupName", "{group name}", $ligne["groupname"]);
    $form[]=$tpl->field_text("description", "{description}", $ligne["description"]);
    $html=$tpl->form_outside("",$form,"",$button,"$function();$BootstrapDialog;LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function')","AsDnsAdministrator");
    $banner=$locked?rep_meta_banner(($ID>0)?"{rblp_node_group_locked}":"{dnsfw_meta_row_locked}"):"";
    echo $tpl->_ENGINE_parse_body($banner).$tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_popup():bool{
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ID=intval($_GET["rule-popup"]);
    $BootstrapDialog="dialogInstance2.close();";
    $button="{add}";
    $ligne=array("rulename"=>"","firewall"=>0,"portslist"=>"","maxtime"=>0);
    if($ID>0){
        $button="{apply}";
        list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
        if($err!=""){
            echo $tpl->div_error($err);
            return false;
        }
        $ligne=$rule;
    }
    $locked=rep_meta($ligne);
    if($locked){rep_meta_lock_form($tpl);}
    if(intval($ligne["maxtime"])<5){
        $ligne["maxtime"]=14400;
    }

    $form[]=$tpl->field_hidden("ruleid",$ID);
    $form[]=$tpl->field_text("rulename", "{rpl_f_name}", $ligne["rulename"],true,"{rpl_f_name_help}");
    // Mode de decision lu par le moteur (ruleMatches) ; avant, il n'etait ni affiche ni enregistrable.
    $form[]=$tpl->field_array_hash(rpl_modes(),"reptype","{rpl_f_mode}",rpl_mode_of($ligne),true,"{rpl_f_mode_help}",null,false);
    // Enregistres et exportes vers Meta, mais lus par aucun code d'execution : on le dit.
    $form[]=$tpl->field_section("{rpl_s_firewall}","{rpl_s_firewall_help}",true);
    $form[]=$tpl->field_checkbox("firewall", "{firewall_protection}", intval($ligne["firewall"]),"portslist,maxtime");
    $form[]=$tpl->field_text("portslist", "{ports}", $ligne["portslist"]);
    $form[]=$tpl->field_numeric("maxtime","{Fail2bantime}",intval($ligne["maxtime"]));


    $html=$tpl->form_outside("",$form,"",$button,"$function();$BootstrapDialog","AsDnsAdministrator");
    $banner=$locked?rep_meta_banner("{dnsfw_meta_row_locked}"):"";
    if(rep_former_meta($ligne)){
        $banner="<div class='alert alert-info' role='status' style='margin:10px 0'>{rblp_node_former_meta_explain}</div>";
    }
    echo $tpl->_ENGINE_parse_body($banner).$tpl->_ENGINE_parse_body($html);
    return true;
}
function group_save():bool{
    if(!rpr_require_admin(false,true)){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ruleid=intval($_POST["ruleid"]);
    $groupid=intval($_POST["groupid"]);
    $GroupName=trim((string)$_POST["GroupName"]);
    if($groupid==0 && $ruleid==0){
        echo $tpl->post_error("ruleid = 0");
        return false;
    }
    if($groupid>0?rep_group_locked($groupid):rep_rule_locked($ruleid)){return rep_meta_refuse_post();}
    $data=array(
        "id"=>$groupid,
        "groupname"=>$GroupName,
        "description"=>(string)$_POST["description"],
        "ruleid"=>$ruleid,
    );
    list(,$err)=rep_api_post("/reputations/admin/group",$data);
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    if($groupid==0){
        return admin_tracks("Create new reputation Group $GroupName");
    }
    return admin_tracks("Update reputation Group $GroupName");
}
function rule_save():bool{
    if(!rpr_require_admin(false,true)){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ruleid"]);
    if(rep_rule_locked($ID)){return rep_meta_refuse_post();}
    $rulename=trim((string)$_POST["rulename"]);
    $data=array(
        "id"=>$ID,
        "rulename"=>$rulename,
        "firewall"=>intval($_POST["firewall"]),
        "portslist"=>trim((string)$_POST["portslist"]),
        "maxtime"=>intval($_POST["maxtime"]),
    );
    $modes=rpl_modes();
    $reptype=(string)($_POST["reptype"] ?? "");
    if(isset($modes[$reptype])){$data["reptype"]=$reptype;}
    list(,$err)=rep_api_post("/reputations/admin/rule",$data);
    if($err!=""){
        echo $tpl->post_error($err);
        return false;
    }
    if($ID==0){
        return admin_tracks("Create new reputation rule $rulename");
    }
    return admin_tracks("Update reputation rule $rulename");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{reputation}",ico_clouds,"{APP_REPPUTATION_EXPLAIN}",
        "$page?tabs=yes","reputation",
        "progress-rblclients-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{reputation}",$html);
        echo $tpl->build_firewall();return true;}
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rule_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["rule-tab"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
    if($err!=""){
        echo $tpl->div_error($err);
        return false;
    }
    $array=array();
    $array[rep_h($rule["rulename"])] = "$page?rule-popup=$ID&function=$function";
    $array["{reputation_services}"] = "$page?groups-popup=$ID&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}
function group_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ID=intval($_GET["group-tab"]);
    $ruleid=intval($_GET["ruleid"]);
    list($group,$err)=rep_api_get("/reputations/admin/group/$ID");
    if($err!=""){
        echo $tpl->div_error($err);
        return false;
    }
    $array=array();
    $array[rep_h($group["groupname"])] = "$page?group-popup=$ID&ruleid=$ruleid&function=$function";
    $array["{remote_services}"]="$page?services-popup=$ID&ruleid=$ruleid&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{rules}"] = "$page?popup-search=yes";
    $array["{events}"] = "fw.reputations.requests.php";
    echo $tpl->tabs_default($array);
    return true;
}
function groups_popup():bool{
    $page=CurrentPageName();
    $ruleid=intval($_GET["groups-popup"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    echo "<div id='groupsOf$ruleid' style='margin-top:10px'></div>";
    echo "<script type='text/javascript'>LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function')";
    echo "</script>";
    return true;
}
function services_button():bool{
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsDnsAdministrator")){return true;}
    $page=CurrentPageName();
    $ruleid=intval($_GET["ruleid"]);
    $groupid=intval($_GET["services-button"]);
    if(rep_group_locked($groupid)){return true;}
    $function=rps_callback("function");
    $function1=rps_callback("function1");
    $url="$page?service-js=0&groupid=$groupid&ruleid=$ruleid&function=$function&function1=$function1";
    echo $tpl->_ENGINE_parse_body("<button type='button' class='ak-btn ak-btn-primary' onclick=\"Loadjs('$url')\"><i class='fas fa-plus' aria-hidden='true'></i>{new_service}</button>");
    return true;
}

function rps_callback($key):string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET[$key] ?? ""));
}

function rps_refresh($callback):string{
    return $callback===""?"":"if(typeof window['$callback']==='function'){window['$callback']();}";
}

function rps_text($value):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),rep_h($value));
}

function rps_model($groupid):array{
    $model=array("rows"=>array(),"error"=>"");
    if($groupid<=0){return $model;}
    list($services,$model["error"])=rep_api_get("/reputations/admin/group/$groupid/services");
    if($model["error"]!==""){return $model;}

    foreach(rep_list($services) as $service){
        $row=array(
            "id"=>intval($service["id"]),
            "uri"=>rps_text($service["uri"]),
            "enabled"=>intval($service["enabled"])===1,
            "response"=>intval($service["noresponse"])===1?"{no_answer}":rps_text($service["responsecode"]),
            "dns"=>rps_text(trim((string)$service["forcedns"])),
            "stamp"=>false,
            "invalid"=>false,
            "endpoint"=>"",
            "detail"=>"",
            "meta"=>rep_meta($service),
        );
        if($row["dns"]===""){$row["dns"]="{default}";}
        if(strpos((string)$service["uri"],"sdns://")===0){
            $row["stamp"]=true;
            $stamp=base64_encode($service["uri"]);
            $decoded=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnstamp/decode/$stamp"));
            if(empty($decoded->Status) || !is_object($decoded->Info ?? null)){
                $row["invalid"]=true;
                $row["detail"]=rps_text($decoded->Error ?? "");
            }else{
                $row["endpoint"]=rps_text("https://".$decoded->Info->ProviderName.$decoded->Info->Path);
                $row["dns"]=rps_text($decoded->Info->ServerAddrStr);
            }
        }
        $model["rows"][]=$row;
    }
    return $model;
}

function rps_identity($row,$edit):string{
    $uri=$row["uri"];
    $icon=$row["invalid"]?"triangle-exclamation":($row["stamp"]?"lock":"database");
    $tone=$row["invalid"]?"err":($row["enabled"]?"accent":"off");
    $name=$edit===""?"<span class='rps-uri'>$uri</span>":
        "<button type='button' class='ak-link rps-uri' onclick=\"Loadjs('$edit')\">$uri</button>";
    $meta="{DNS_SERVER}: <span class='rps-dns'>".$row["dns"]."</span>";
    if($row["endpoint"]!==""){
        $meta="<strong>DOH</strong> ".$row["endpoint"]." <span aria-hidden='true'>&middot;</span> ".$meta;
    }
    $error="";
    if($row["invalid"]){
        $error="<div class='rps-stamp-error'><span class='ak-tag ak-err'><i class='fas fa-circle-exclamation' aria-hidden='true'></i>DNS Stamp Error</span>"
            ."<div>".$row["detail"]."</div></div>";
    }
    $badge=!empty($row["meta"])?rep_meta_badge(array("metaid"=>"1"),"{rblp_node_group_locked}"):"";
    return "<div class='rps-ident'><span class='ak-ico ak-ico-sm ak-ico-sq ak-$tone'><i class='fas fa-$icon' aria-hidden='true'></i></span>"
        ."<div>$name$badge<div class='rps-meta'>$meta</div>$error</div></div>";
}

function rps_actions($row,$context):string{
    if(!$context["admin"]){return "";}
    if($context["locked"] || !empty($row["meta"])){return "";}
    $page=$context["page"];
    $function=$context["function"];
    $function1=$context["function1"];
    $id=$row["id"];
    $name=$row["uri"];
    $checked=$row["enabled"]?"true":"false";
    $disabled=$context["canToggle"]?"":" disabled";
    return "<div class='rps-actions'>"
        ."<button type='button' class='ak-sw' role='switch' aria-checked='$checked' aria-label='{enabled}: $name'$disabled onclick=\"Loadjs('$page?enable-service=$id&function=$function&function1=$function1')\"></button>"
        ."<button type='button' class='ak-btn ak-btn-icon ak-btn-danger' aria-label='{delete}: $name' onclick=\"Loadjs('$page?delete-service=$id&function=$function&function1=$function1')\"><i class='fas fa-trash-can' aria-hidden='true'></i></button></div>";
}

function rps_table($model,$context):string{
    if($model["error"]!==""){
        return "<div class='ak-banner ak-err' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><div class='ak-banner-m'>".rep_h($model["error"])."</div></div>";
    }
    $page=$context["page"];
    $groupid=$context["groupid"];
    $ruleid=$context["ruleid"];
    $function=$context["function"];
    $function1=$context["function1"];
    $body="rps-rows-$groupid";
    $pager="rps-pager-$groupid";
    $html=$context["locked"]?rep_meta_banner("{rblp_node_group_locked}"):"";
    $html.="<div class='ak-card rps-table'><table class='ak-tbl'><thead><tr>"
        ."<th scope='col'>{services}</th><th scope='col'>{response}</th>"
        ."<th scope='col'>{enabled}</th><th scope='col' class='ak-end'>{actions}</th></tr></thead><tbody id='$body'>";
    foreach($model["rows"] as $row){
        $id=$row["id"];
        $edit=$context["admin"]?"$page?service-js=$id&groupid=$groupid&ruleid=$ruleid&function=$function&function1=$function1":"";
        $state=$row["enabled"]?"{enabled}":"{disabled}";
        $tone=$row["enabled"]?"ok":"off";
        $icon=$row["enabled"]?"circle-check":"circle-minus";
        $class=$row["invalid"]?" class='rps-invalid'":"";
        $html.="<tr$class><td>".rps_identity($row,$edit)."</td><td><span class='ak-chip ak-mono'>".$row["response"]."</span></td>"
            ."<td><span class='ak-pill ak-$tone'><i class='fas fa-$icon' aria-hidden='true'></i>$state</span></td>"
            ."<td>".rps_actions($row,$context)."</td></tr>";
    }
    if(count($model["rows"])===0){$html.=rep_table_empty(4);}
    $total=count($model["rows"]);
    return $html."</tbody></table><div class='rps-foot'><span>{showing} ".min(25,$total)." / $total {services}</span><span class='awc-pg' id='$pager'></span></div></div>";
}

function rps_js($context,$hasRows):string{
    $page=$context["page"];
    $groupid=$context["groupid"];
    $ruleid=$context["ruleid"];
    $function=$context["function"];
    $function1=$context["function1"];
    $url="$page?services-button=$groupid&function=$function&function1=$function1&ruleid=$ruleid";
    $pager=$hasRows?awc_pager_js(25,"rps-rows-$groupid","rps-pager-$groupid"):"";
    return "<script>LoadAjaxSilent('ButtonServicesFor$groupid','$url');NoSpinner();</script>".$pager;
}

function services_list():bool{
    $tpl=new template_admin();
    $context=array(
        "page"=>CurrentPageName(),
        "groupid"=>intval($_GET["services-search"]),
        "ruleid"=>intval($_GET["ruleid"] ?? 0),
        "function"=>rps_callback("function"),
        "function1"=>rps_callback("function1"),
        "admin"=>$tpl->IsSecurity("AsDnsAdministrator"),
    );
    $context["locked"]=rep_group_locked($context["groupid"]);
    $context["canToggle"]=!$tpl->CLUSTER_CLI || intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))!==1;
    $model=rps_model($context["groupid"]);
    echo awc_table_style().$tpl->_ENGINE_parse_body(rps_table($model,$context))
        .rps_js($context,count($model["rows"])>0);
    return true;
}

function rps_style():string{
    return <<<'HTML'
<style>
.rps{position:relative;padding:24px;font-size:14px}
.rps,.rps *,.rps-form,.rps-form *{box-sizing:border-box}
.rps .rps-new{position:absolute;top:24px;right:24px;z-index:1}
.rps .rps-new .ak-btn{height:40px;white-space:nowrap}
.rps .sb-stitch{margin:0}
.rps .sb-stitch .ibox-content{margin:0;padding:0;background:transparent}
.rps .sb-stitch .input-group{margin-left:0;width:450px;max-width:calc(100% - 200px);height:40px}
.rps .sb-stitch .form-control,.rps .sb-stitch .sb-go{height:40px;font-size:14px}
.rps .sb-stitch .sb-go{white-space:nowrap;text-transform:none;height:34px;line-height:34px}
.rps .sb-stitch .input-group:focus-within{outline:2px solid var(--ak-fill);outline-offset:2px}
.rps .rps-table{margin-top:16px;overflow-x:auto}
.rps .rps-table table{table-layout:auto;margin:0;width:100%}
.rps .rps-table th{background:#f1f5f9;color:#334155}
.rps .rps-table th:not(:first-child){width:1%;white-space:nowrap}
.rps .rps-table td{height:60px;vertical-align:middle;overflow-wrap:anywhere}
.rps .rps-ident{display:flex;align-items:flex-start;gap:12px}
.rps .rps-ident>div{min-width:0}
.rps .rps-uri{font-size:14px;font-weight:600;line-height:1.4;text-align:left;white-space:normal;overflow-wrap:anywhere;padding:0}
.rps .rps-meta{color:#475569;font-size:13px;line-height:1.45;margin-top:3px}
.rps .rps-invalid td{background:#fffafa}
.rps .rps-stamp-error{color:#991b1b;font-size:13px;margin-top:4px}
.rps .rps-dns,.rps .rps-table .ak-chip,.rps .rps-table .ak-pill{white-space:nowrap}
.rps .rps-actions{display:flex;justify-content:flex-end;align-items:center;gap:8px}
.rps .rps-actions .ak-btn{width:36px;height:36px;border:1px solid #fecaca;border-radius:8px}
.rps .rps-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;font-size:13px;border-top:1px solid #e2e8f0}
.rps button:focus-visible,.rps input:focus-visible,.rps a:focus-visible,.rps-form input:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}
.rps-form form>table>tbody{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.rps-form form>table>tbody>tr{display:flex;flex-direction:column;gap:4px;min-width:0}
.rps-form form>table>tbody>tr:first-child,.rps-form form>table>tbody>tr:last-child{grid-column:1/-1}
.rps-form form>table>tbody>tr>td{display:block;width:100%!important;text-align:left!important;white-space:normal;padding:0!important}
.rps-form .form-control{min-height:40px;font-size:14px;border-radius:8px}
.rps-form .input-group .input-number{height:40px;border-radius:0}
.rps-form .input-group-btn>.btn[data-toggle="spinner"]{height:40px;margin-top:0;margin-bottom:0;vertical-align:middle}
.rps-form .labelform{font-size:13px;font-weight:600;color:#334155}
@media(max-width:1200px){.rps-form form>table>tbody{grid-template-columns:minmax(0,1fr)}.rps .rps-new{position:static;display:flex;justify-content:flex-end;margin-bottom:12px}.rps .sb-stitch .input-group{max-width:100%}.rps .rps-table th,.rps .rps-table td{padding-left:8px;padding-right:8px}}
@media(prefers-reduced-motion:reduce){.rps *,.rps-form *{transition:none!important;animation:none!important}}
</style>
HTML;
}
function group_move_js():bool{
    if(!rpr_require_admin(true)){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["group-move-js"]);
    $ruleid=intval($_GET["ruleid"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $dir=(string)($_GET["dir"] ?? "");
    if($dir!="up" && $dir!="down"){
        $tpl->js_error("dir: up|down");
        return false;
    }
    // Lien d'une regle Meta : refus avant l'appel (le daemon refuse aussi, 423).
    list($link,$err)=rep_api_get("/reputations/admin/link/$ID");
    if($err==="" && rep_rule_locked(intval($link["ruleid"]))){return rep_meta_refuse_js();}
    // Le daemon deplace le lien et renumerote tous les liens de la regle.
    list(,$err)=rep_api_post("/reputations/admin/link/$ID/move/$dir");
    if($err!=""){
        $tpl->js_error($err);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function');";
    return true;
}
// ── Sources d'une liste (onglet « Source groups », refonte Stitch 2026-11-20) : un modele, un widget par fonction ──
// Le moteur (reputations.VerifRule) interroge en parallele chaque serveur actif d'un groupe actif :
// l'ordre des groupes n'est qu'un ordre d'affichage, et « every source » porte sur chaque serveur.

function rsg_callback():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}
// Rechargement de l'onglet (hote cree par groups_popup()).
function rsg_tab_reload($ruleid,$function):string{
    $page=CurrentPageName();
    $ruleid=intval($ruleid);
    if($ruleid<=0){return "";}
    return "if(document.getElementById('groupsOf$ruleid')){LoadAjaxSilent('groupsOf$ruleid','$page?groups-list=$ruleid&function=$function');}";
}
// Serveurs d'un groupe, lus une seule fois par requete.
function rsg_group_servers($groupid):array{
    if(!isset($GLOBALS["RSG_SRV"])){$GLOBALS["RSG_SRV"]=array();}
    if(isset($GLOBALS["RSG_SRV"][$groupid])){return $GLOBALS["RSG_SRV"][$groupid];}
    list($services,$err)=rep_api_get("/reputations/admin/group/$groupid/services");
    $out=array("ok"=>$err==="","rows"=>array());
    foreach(rep_list($services) as $s){
        $out["rows"][]=array(
            "uri"=>trim((string)$s["uri"]),
            "enabled"=>intval($s["enabled"])===1,
            "code"=>trim((string)$s["responsecode"]),
            "noresponse"=>intval($s["noresponse"])===1,
        );
    }
    $GLOBALS["RSG_SRV"][$groupid]=$out;
    return $out;
}
// groupid => nombre de listes qui l'utilisent (un appel par liste, elles sont peu nombreuses).
function rsg_usage():array{
    if(isset($GLOBALS["RSG_USAGE"])){return $GLOBALS["RSG_USAGE"];}
    $usage=array();
    list($rules,$err)=rep_api_get("/reputations/admin/rules");
    if($err!==""){$GLOBALS["RSG_USAGE"]=null;return array();}
    foreach(rep_list($rules) as $r){
        list($groups,$gerr)=rep_api_get("/reputations/admin/rule/".intval($r["id"])."/groups");
        if($gerr!==""){$GLOBALS["RSG_USAGE"]=null;return array();}
        foreach(rep_list($groups) as $g){
            $gid=intval($g["id"]);
            $usage[$gid]=($usage[$gid] ?? 0)+1;
        }
    }
    $GLOBALS["RSG_USAGE"]=$usage;
    return $usage;
}
function rsg_usage_known():bool{
    rsg_usage();
    return is_array($GLOBALS["RSG_USAGE"]);
}
// Texte d'un serveur pour une puce : domaine DNSBL, ou « DoH · fournisseur » d'un tampon sdns://.
function rsg_server_label($uri):string{
    if(strpos($uri,"sdns://")!==0){return rps_text($uri);}
    $decoded=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnstamp/decode/".base64_encode($uri)));
    if(!empty($decoded->Status) && is_object($decoded->Info ?? null) && trim((string)$decoded->Info->ProviderName)!==""){
        return "DoH &middot; ".rps_text($decoded->Info->ProviderName);
    }
    return "DoH &middot; {rsg_stamp_invalid}";
}
// Regle de verdict des serveurs actifs : « repond 127.0.0.2 », « ne repond pas », ou les deux.
function rsg_answer_rule($servers):string{
    $codes=array();$silent=false;
    foreach($servers as $s){
        if(!$s["enabled"]){continue;}
        if($s["noresponse"]){$silent=true;continue;}
        $code=$s["code"]===""?"127.0.0.2":$s["code"];
        $codes[$code]=true;
    }
    $parts=array();
    if(count($codes)>0){$parts[]=rpl_t("rsg_answer_code","<span class='rsg-mono'>".rps_text(implode(" / ",array_keys($codes)))."</span>");}
    if($silent){$parts[]=rpl_t("rsg_answer_silent");}
    return implode(" &middot; ",$parts);
}

// Confirmation du retrait : js_confirm_delete imposerait « Yes, delete it » alors que rien n'est supprime.
function rsg_detach_confirm_js($linkid,$link,$jsafter):bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $dec=function($html){ return html_entity_decode($html,ENT_QUOTES,"UTF-8"); };
    // Le texte traduit est decode, les noms venus de l'API restent echappes (ils partent en HTML).
    $title=str_replace(array("%1","%2"),array("« ".rep_h($link["groupname"])." »","« ".rep_h($link["rulename"])." »"),$dec(rpl_t("rsg_detach_title","%1","%2")));
    $content=$dec($tpl->_ENGINE_parse_body("{rsg_detach_text}"));
    $btn=$dec($tpl->_ENGINE_parse_body("{rsg_detach_btn}"));
    $cancel=$dec($tpl->_ENGINE_parse_body("{rsg_cancel}"));
    $err=$dec($tpl->_ENGINE_parse_body("{rsg_error}"));
    $linkid=intval($linkid);
    header("content-type: application/x-javascript");
    echo "$.confirm({theme:'material',containerFluid:true,icon:'fas fa-link-slash',title:".json_encode($title).",content:".json_encode($content).",
buttons:{confirm:{text:".json_encode($btn).",btnClass:'btn-red',action:function(){
 var XHR=new XHRConnection();XHR.appendData('unlink-group','$linkid');
 XHR.sendAndLoad('$page','POST',function(obj){var r=(obj.responseText||'').trim();
  if(r.length>3){\$.alert({theme:'material',title:".json_encode($err).",content:r});return;}
  $jsafter});}},
cancel:{text:".json_encode($cancel)."}}});";
    return true;
}

function rsg_model($ruleid):array{
    $tpl=new template_admin();
    $m=array("ruleid"=>$ruleid,"rule"=>null,"error"=>"","groups"=>array(),"admin"=>$tpl->IsSecurity("AsDnsAdministrator"),
        "locked"=>false,"mode"=>"match-one","servers"=>0,"active"=>0,"usage_known"=>false,"function"=>rsg_callback());
    list($rule,$rerr)=rep_api_get("/reputations/admin/rule/$ruleid");
    if($rerr===""){
        $m["rule"]=$rule;
        $m["locked"]=rep_meta($rule);
        $m["mode"]=rpl_mode_of($rule);
    }
    list($groups,$err)=rep_api_get("/reputations/admin/rule/$ruleid/groups");
    if($err!==""){$m["error"]=$err;return $m;}
    $usage=rsg_usage();
    $m["usage_known"]=rsg_usage_known();
    foreach(rep_list($groups) as $g){
        $gid=intval($g["id"]);
        $srv=rsg_group_servers($gid);
        $on=0;
        foreach($srv["rows"] as $s){if($s["enabled"]){$on++;}}
        $enabled=intval($g["enabled"])===1;
        $row=array(
            "id"=>$gid,
            "linkid"=>intval($g["linkid"]),
            "name"=>rps_text($g["groupname"]),
            "description"=>rps_text(trim((string)$g["description"])),
            "enabled"=>$enabled,
            "meta"=>rep_meta($g),
            "former"=>rep_former_meta($g),
            "linkmeta"=>rep_meta_link($rule,$g),
            "servers_ok"=>$srv["ok"],
            "servers"=>$srv["rows"],
            "total"=>count($srv["rows"]),
            "on"=>$on,
            "others"=>max(0,intval($usage[$gid] ?? 1)-1),
        );
        $m["servers"]+=$row["total"];
        if($enabled){$m["active"]+=$on;}
        $m["groups"][]=$row;
    }
    return $m;
}

function rsg_meta_banner($m):string{
    if(!$m["locked"]){return "";}
    return "<div class='rsg-banner rsg-banner-meta' role='status'><i class='fas fa-lock' aria-hidden='true'></i><div>{dnsfw_meta_row_locked}</div></div>";
}

function rsg_context($m):string{
    $n=count($m["groups"]);
    $all=$m["mode"]==="match-all";
    $chip=$all?"<i class='fas fa-circle-check' aria-hidden='true'></i>{rpl_mode_all}":"<i class='fas fa-circle-dot' aria-hidden='true'></i>{rpl_mode_any}";
    $help=$all?"{rsg_mode_all_help}":"{rsg_mode_any_help}";
    $active=$m["active"]>0?"<span class='rsg-count rsg-ok'><i class='fas fa-circle-check' aria-hidden='true'></i>".rpl_t("rsg_n_active",$m["active"])."</span>"
        :"<span class='rsg-count rsg-warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>".rpl_t("rsg_n_active",0)."</span>";
    return "<section class='rsg-context'>"
        ."<div class='rsg-context-ico' aria-hidden='true'><i class='fas fa-list-check'></i></div>"
        ."<div class='rsg-context-body'><h3>{rsg_title}<span class='rsg-sub'>{rsg_title_sub}</span></h3>"
        ."<p>{rsg_explain}</p>"
        ."<div class='rsg-counts'><span>".rpl_t($n===1?"rsg_n_group":"rsg_n_groups",$n)."</span><span class='rsg-sep' aria-hidden='true'>&bull;</span>"
        ."<span>".rpl_t($m["servers"]===1?"rsg_n_server":"rsg_n_servers",$m["servers"])."</span><span class='rsg-sep' aria-hidden='true'>&bull;</span>$active</div></div>"
        ."<div class='rsg-mode'><span class='rsg-mode-chip'>$chip</span><span class='rsg-mode-help'>$help</span></div>"
        ."</section>";
}

// Alerte seulement quand la liste ne peut rien detecter.
function rsg_alert($m):string{
    if(count($m["groups"])===0 || $m["active"]>0){return "";}
    return "<div class='rsg-banner rsg-banner-warn' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><div><strong>{rsg_list_blind}</strong> {rsg_list_blind_help}</div></div>";
}

function rsg_toolbar($m):string{
    // Liste vide : l'etat vide porte deja les deux boutons.
    if($m["locked"] || !$m["admin"] || count($m["groups"])===0){return "";}
    $page=CurrentPageName();
    $rid=$m["ruleid"];$f=$m["function"];
    $search="";
    if(count($m["groups"])>6){
        $search="<label class='rsg-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' placeholder='{rsg_filter}' aria-label='{rsg_filter}' oninput=\"RsgFilter($rid,this.value)\"></label>";
    }
    return "<div class='rsg-toolbar'><div class='rsg-toolbar-l'>"
        ."<button type='button' class='rsg-btn rsg-btn-primary' onclick=\"Loadjs('$page?group-link=$rid&function=$f')\"><i class='fas fa-link' aria-hidden='true'></i>{rsg_add}</button>"
        ."<button type='button' class='rsg-btn' onclick=\"Loadjs('$page?group-js=0&ruleid=$rid&function=$f')\"><i class='fas fa-plus' aria-hidden='true'></i>{rsg_create}</button>"
        ."</div>$search</div>";
}

function rsg_badges($g):string{
    $b=$g["enabled"]?"<span class='rsg-pill rsg-ok'><i class='fas fa-circle-check' aria-hidden='true'></i>{rpl_on}</span>"
        :"<span class='rsg-pill rsg-off'><i class='fas fa-circle-minus' aria-hidden='true'></i>{rpl_off}</span>";
    if($g["meta"]){$b.=rsg_meta_pill();}
    elseif($g["former"]){$b.=rep_meta_badge(array("former_metaid"=>"1"));}
    return $b;
}

function rsg_meta_pill():string{
    return "<span class='rsg-pill rsg-meta' title=\"{rblp_node_group_locked}\"><i class='fas fa-lock' aria-hidden='true'></i>{rpl_meta}</span>";
}

function rsg_server_line($g):string{
    if(!$g["servers_ok"]){return "<div class='rsg-servers'><span class='rsg-srv-n'>&mdash;</span></div>";}
    $warn=$g["on"]===0?" rsg-warn-t":"";
    $count=rpl_t($g["total"]===1?"rsg_n_server":"rsg_n_servers",$g["total"])." &bull; ".rpl_t("rsg_n_on",$g["on"]);
    $chips=array();$shown=0;
    foreach($g["servers"] as $s){
        if($shown>=3){break;}
        $off=$s["enabled"]?"":" rsg-srv-off";
        $title=$s["enabled"]?"":" title='{rsg_server_off}'";
        $chips[]="<span class='rsg-srv$off'$title>".rsg_server_label($s["uri"])."</span>";
        $shown++;
    }
    $more=$g["total"]-$shown;
    if($more>0){$chips[]="<span class='rsg-srv rsg-srv-more'>".rpl_t("rsg_more",$more)."</span>";}
    return "<div class='rsg-servers'><span class='rsg-srv-n$warn'>$count</span>"
        .(count($chips)>0?"<span class='rsg-vsep' aria-hidden='true'></span>".implode("",$chips):"")."</div>";
}

function rsg_group_warning($g,$m):string{
    if(!$g["servers_ok"] || $g["on"]>0){return "";}
    $page=CurrentPageName();
    $fix="";
    if($m["admin"] && !$g["meta"]){
        $fix="<button type='button' class='rsg-link' onclick=\"Loadjs('$page?group-js=".$g["id"]."&ruleid=".$m["ruleid"]."&function=".$m["function"]."')\">{rsg_add_server}<i class='fas fa-arrow-right' aria-hidden='true'></i></button>";
    }
    $text=$g["total"]===0?"{rsg_group_empty}":"{rsg_group_all_off}";
    return "<div class='rsg-gwarn'><span><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><strong>{rsg_no_active}</strong> $text</span>$fix</div>";
}

function rsg_group_actions($g,$m,$first,$last):string{
    if(!$m["admin"] || $m["locked"]){return "";}
    if($g["meta"]){
        return "<div class='rsg-acts'><span class='rsg-locked'><i class='fas fa-lock' aria-hidden='true'></i>{rpl_locked}</span></div>";
    }
    $page=CurrentPageName();
    $rid=$m["ruleid"];$f=$m["function"];$id=$g["id"];$lid=$g["linkid"];$name=$g["name"];
    $checked=$g["enabled"]?"true":"false";
    $h="<div class='rsg-acts'>"
        ."<button type='button' class='rsg-sw' role='switch' aria-checked='$checked' aria-label='".rpl_t("rsg_aria_toggle",$name)."' title='{rsg_switch_help}' onclick=\"Loadjs('$page?enable-group=$id&ruleid=$rid&function=$f')\"></button>";
    if(!$g["linkmeta"]){
        $h.="<button type='button' class='rsg-ibtn rsg-ibtn-danger' aria-label='".rpl_t("rsg_aria_detach",$name)."' title='{rsg_detach}' onclick=\"Loadjs('$page?unlink-group=$lid&function=$f&ruleid=$rid')\"><i class='fas fa-link-slash' aria-hidden='true'></i></button>";
        $up=$first?" disabled":"";$down=$last?" disabled":"";
        $h.="<span class='rsg-move' title='{rsg_order_help}'>"
            ."<button type='button' class='rsg-ibtn' aria-label='".rpl_t("rsg_aria_up",$name)."'$up onclick=\"Loadjs('$page?group-move-js=$lid&ruleid=$rid&dir=up&function=$f')\"><i class='fas fa-arrow-up' aria-hidden='true'></i></button>"
            ."<button type='button' class='rsg-ibtn' aria-label='".rpl_t("rsg_aria_down",$name)."'$down onclick=\"Loadjs('$page?group-move-js=$lid&ruleid=$rid&dir=down&function=$f')\"><i class='fas fa-arrow-down' aria-hidden='true'></i></button></span>";
    }
    return $h."</div>";
}

function rsg_group_card($g,$m,$first,$last):string{
    $page=CurrentPageName();
    $warn=$g["servers_ok"] && $g["on"]===0;
    $tone=$warn?"warn":($g["enabled"]?"accent":"off");
    $icon=$warn?"triangle-exclamation":"folder-open";
    $open="Loadjs('$page?group-js=".$g["id"]."&ruleid=".$m["ruleid"]."&function=".$m["function"]."')";
    $desc=$g["description"]!==""?"<p class='rsg-desc'>".$g["description"]."</p>":"";
    $answer=rsg_answer_rule($g["servers"]);
    $answer=$answer!==""?"<p class='rsg-help'>$answer</p>":"";
    $shared="";
    if($m["usage_known"] && $g["others"]>0){
        $shared="<p class='rsg-shared'><i class='fas fa-share-nodes' aria-hidden='true'></i>".rpl_t($g["others"]===1?"rsg_shared_1":"rsg_shared_n",$g["others"])."</p>";
    }
    $off=$g["enabled"]?"":"<p class='rsg-help'>{rsg_off_help}</p>";
    $search=strtolower(strip_tags(html_entity_decode($g["name"]." ".$g["description"],ENT_QUOTES,"UTF-8")));
    return "<article class='rsg-card".($g["enabled"]?"":" rsg-card-off")."' data-s='".rep_h($search)."'>"
        ."<div class='rsg-gico rsg-$tone' aria-hidden='true'><i class='fas fa-$icon'></i></div>"
        ."<div class='rsg-main'><div class='rsg-head'><button type='button' class='rsg-name' onclick=\"$open\">".$g["name"]."</button>".rsg_badges($g)."</div>"
        .$desc.rsg_server_line($g).rsg_group_warning($g,$m).$answer.$shared.$off."</div>"
        .rsg_group_actions($g,$m,$first,$last)
        ."</article>";
}

function rsg_cards($m):string{
    $n=count($m["groups"]);
    if($n===0){return rsg_empty($m);}
    $h=array();
    foreach($m["groups"] as $i=>$g){$h[]=rsg_group_card($g,$m,$i===0,$i===$n-1);}
    return "<div class='rsg-cards' id='rsg-cards-".$m["ruleid"]."'>".implode("",$h)."</div>";
}

function rsg_empty($m):string{
    $page=CurrentPageName();
    $rid=$m["ruleid"];$f=$m["function"];
    $btn="";
    if($m["admin"] && !$m["locked"]){
        $btn="<div class='rsg-empty-b'><button type='button' class='rsg-btn rsg-btn-primary' onclick=\"Loadjs('$page?group-link=$rid&function=$f')\"><i class='fas fa-link' aria-hidden='true'></i>{rsg_add}</button>"
            ."<button type='button' class='rsg-btn' onclick=\"Loadjs('$page?group-js=0&ruleid=$rid&function=$f')\"><i class='fas fa-plus' aria-hidden='true'></i>{rsg_create}</button></div>";
    }
    return "<div class='rsg-empty'><div class='rsg-empty-ico' aria-hidden='true'><i class='fas fa-shield-halved'></i></div>"
        ."<h4>{rsg_empty_title}</h4><p>{rsg_empty_text}</p>$btn</div>";
}

function rsg_footer($m):string{
    $n=count($m["groups"]);
    if($n===0){return "";}
    return "<div class='rsg-foot'><span id='rsg-shown-".$m["ruleid"]."'>".rpl_t("rsg_showing",$n,$n)."</span><span>{rsg_order_help}</span></div>";
}

function rsg_error($m):string{
    return "<div class='rsg-banner rsg-banner-err' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><div>".rep_h($m["error"])."</div></div>";
}

function rsg_js():string{
    $tpl=new template_admin();
    $showing=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{rsg_showing}"),ENT_QUOTES,"UTF-8"));
    return "<script>
function RsgFilter(rid,q){q=(q||'').toLowerCase().trim();var l=document.getElementById('rsg-cards-'+rid);if(!l){return;}var n=0,t=0;
 for(var i=0;i<l.children.length;i++){var c=l.children[i];t++;var ok=(q===''||(c.getAttribute('data-s')||'').indexOf(q)>-1);c.style.display=ok?'':'none';if(ok){n++;}}
 var s=document.getElementById('rsg-shown-'+rid);if(s){s.textContent=$showing.replace('%1',n).replace('%2',t);}}
NoSpinner();
</script>";
}

function groups_list():bool{
    $tpl=new template_admin();
    $m=rsg_model(intval($_GET["groups-list"]));
    $html="<div class='rsg'>".rsg_meta_banner($m);
    if($m["error"]!==""){
        $html.=rsg_error($m);
    }else{
        $html.=rsg_context($m).rsg_alert($m).rsg_toolbar($m).rsg_cards($m).rsg_footer($m);
    }
    $html.="</div>";
    echo rsg_style().$tpl->_ENGINE_parse_body($html).rsg_js();
    return true;
}

// Fenetre « Add a source group » : tous les groupes, partages entre listes.
function rsg_picker_row($g,$linked,$usage,$usageKnown,$ruleid,$function):string{
    $page=CurrentPageName();
    $id=intval($g["id"]);
    $name=rps_text($g["groupname"]);
    $desc=trim((string)$g["description"]);
    $srv=rsg_group_servers($id);
    $first="";
    foreach($srv["rows"] as $s){if($s["enabled"]){$first=rsg_server_label($s["uri"]);break;}}
    $meta=array();
    if($srv["ok"]){$meta[]=rpl_t(count($srv["rows"])===1?"rsg_n_server":"rsg_n_servers",count($srv["rows"]));}
    if($usageKnown){
        $u=intval($usage[$id] ?? 0);
        $meta[]=$u===0?rpl_t("rsg_used_none"):rpl_t($u===1?"rsg_used_1":"rsg_used_n",$u);
    }
    $md=md5(serialize($g));
    if(rep_meta($g)){
        $act="<span class='rsg-locked'><i class='fas fa-lock' aria-hidden='true'></i>{rpl_locked}</span>";
    }elseif(isset($linked[$id])){
        $act="<button type='button' class='rsg-btn' disabled><i class='fas fa-check' aria-hidden='true'></i>{rsg_already}</button>";
    }else{
        $act="<button type='button' class='rsg-btn rsg-btn-primary' onclick=\"Loadjs('$page?link-group=$id&ruleid=$ruleid&function=$function&md=$md')\"><i class='fas fa-plus' aria-hidden='true'></i>{rsg_add_to_list}</button>";
    }
    $badge=rep_meta($g)?rsg_meta_pill():"";
    return "<div class='rsg-prow' id='$md'><div class='rsg-gico rsg-accent' aria-hidden='true'><i class='fas fa-folder-open'></i></div>"
        ."<div class='rsg-main'><div class='rsg-head'><span class='rsg-pname'>$name</span>".($first!==""?"<span class='rsg-srv'>$first</span>":"").$badge."</div>"
        .($desc!==""?"<p class='rsg-desc'>".rps_text($desc)."</p>":"")
        .(count($meta)>0?"<p class='rsg-help'><i class='fas fa-server' aria-hidden='true'></i> ".implode(" &bull; ",$meta)."</p>":"")
        ."</div><div class='rsg-acts'>$act</div></div>";
}

function group_link_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=rsg_callback();
    $ruleid=intval($_GET["group-link-popup"]);
    $html="<div class='rsg rsg-picker'>";
    list($groups,$err)=rep_api_get("/reputations/admin/groups");
    if($err!==""){
        echo rsg_style().$tpl->_ENGINE_parse_body($html.rsg_error(array("error"=>$err))."</div>");
        return true;
    }
    list($mine,)=rep_api_get("/reputations/admin/rule/$ruleid/groups");
    $linked=array();
    foreach(rep_list($mine) as $g){$linked[intval($g["id"])]=true;}
    $usage=rsg_usage();
    $known=rsg_usage_known();
    $create="<button type='button' class='rsg-btn' onclick=\"Loadjs('$page?group-js=0&ruleid=$ruleid&function=$function')\"><i class='fas fa-plus' aria-hidden='true'></i>{rsg_create}</button>";
    $html.="<div class='rsg-pintro'><p><i class='fas fa-circle-info' aria-hidden='true'></i>{rsg_picker_explain}</p>$create</div>";
    $rows=array();
    foreach(rep_list($groups) as $g){$rows[]=rsg_picker_row($g,$linked,$usage,$known,$ruleid,$function);}
    if(count($rows)===0){
        $html.="<div class='rsg-empty'><div class='rsg-empty-ico' aria-hidden='true'><i class='fas fa-folder-open'></i></div><h4>{rsg_picker_empty}</h4><p>{rsg_picker_empty_text}</p></div>";
    }else{
        $html.="<div class='rsg-plist'>".implode("",$rows)."</div>";
    }
    $html.="</div>";
    echo rsg_style().$tpl->_ENGINE_parse_body($html)."<script>NoSpinner();</script>";
    return true;
}

function rsg_style():string{
    if(isset($GLOBALS["RSG_STYLE"])){return "";}
    $GLOBALS["RSG_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.rsg{display:flex;flex-direction:column;gap:16px;padding:16px 4px 8px;color:#0f172a;font-size:14px}
.rsg *{box-sizing:border-box}
.rsg p{margin:0}
.rsg-banner{display:flex;gap:10px;align-items:flex-start;border-radius:10px;padding:12px 16px;font-size:13.5px;line-height:1.5}
.rsg-banner-meta{background:#e6eeed;border:1px solid #bfd4d1;color:#005447}
.rsg-banner-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.rsg-banner-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.rsg-context{display:flex;gap:16px;align-items:flex-start;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px}
.rsg-context-ico{flex:0 0 40px;height:40px;border-radius:10px;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px}
.rsg-context-body{flex:1;min-width:0}
.rsg-context h3{margin:0 0 4px;font-size:16px;font-weight:600}
.rsg-sub{margin-left:8px;font-size:12.5px;font-weight:400;color:#64748b}
.rsg-context p{color:#475569;line-height:1.55}
.rsg-counts{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:10px;font-size:13.5px;font-weight:600;color:#334155}
.rsg-sep{color:#cbd5e1}
.rsg-count{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:2px 10px;font-size:12.5px}
.rsg-ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.rsg-warn{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.rsg-off{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}
.rsg-meta{background:#e6eeed;color:#005447;border:1px solid #bfd4d1}
.rsg-accent{background:$soft;color:$dark;border:1px solid $line}
.rsg-mode{flex:0 0 auto;max-width:300px;display:flex;flex-direction:column;align-items:flex-end;gap:6px;text-align:right}
.rsg-mode-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #e2e8f0;border-radius:999px;padding:6px 14px;font-weight:600;font-size:13.5px;white-space:nowrap}
.rsg-mode-chip i,.rsg-mode-chip svg{color:$accent}
.rsg-mode-help{font-size:12.5px;color:#64748b;line-height:1.45}
.rsg-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.rsg-toolbar-l{display:flex;gap:10px;flex-wrap:wrap}
.rsg-btn{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;font-size:13.5px;font-weight:600;cursor:pointer;white-space:nowrap}
.rsg-btn:hover{background:#f8fafc}
.rsg-btn[disabled]{opacity:.6;cursor:not-allowed}
.rsg-btn-primary{background:$accent;border-color:$accent;color:#fff}
.rsg-btn-primary:hover{background:$dark;border-color:$dark}
.rsg-search{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;height:38px;color:#64748b;margin:0;font-weight:400;width:280px;max-width:100%}
.rsg-search input{border:0;outline:0;flex:1;min-width:0;font-size:13.5px;background:transparent;color:#0f172a}
.rsg-search:focus-within{outline:2px solid $accent;outline-offset:1px}
.rsg-cards,.rsg-plist{display:flex;flex-direction:column;gap:12px}
.rsg-card,.rsg-prow{display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(15,23,42,.06);transition:border-color .15s}
.rsg-card:hover,.rsg-prow:hover{border-color:#cbd5e1}
.rsg-card-off{background:#fcfcfd}
.rsg-gico{flex:0 0 40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
.rsg-main{flex:1;min-width:0;display:flex;flex-direction:column;gap:6px}
.rsg-head{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.rsg-name{border:0;background:none;padding:0;font-size:15px;font-weight:600;color:#0f172a;text-align:left;cursor:pointer;overflow-wrap:anywhere}
.rsg-name:hover{color:$dark;text-decoration:underline}
.rsg-pname{font-size:15px;font-weight:600;overflow-wrap:anywhere}
.rsg-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;white-space:nowrap}
.rsg-desc{color:#475569;font-size:13.5px;line-height:1.5;overflow-wrap:anywhere}
.rsg-servers{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.rsg-srv-n{font-size:13px;font-weight:600;color:#334155;white-space:nowrap}
.rsg-warn-t{color:#b45309}
.rsg-vsep{width:1px;height:18px;background:#e2e8f0}
.rsg-srv{display:inline-block;font-family:monospace;font-size:12.5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px;color:#0f172a;max-width:100%;overflow-wrap:anywhere}
.rsg-srv-off{text-decoration:line-through;color:#94a3b8}
.rsg-srv-more{font-family:inherit;color:#475569}
.rsg-mono{font-family:monospace}
.rsg-help{font-size:12.5px;color:#64748b;line-height:1.45}
.rsg-shared{font-size:12.5px;color:#475569;display:flex;align-items:center;gap:6px}
.rsg-gwarn{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;font-size:13px;color:#92400e}
.rsg-gwarn i,.rsg-gwarn svg{margin-right:6px}
.rsg-link{border:0;background:none;padding:0;color:#92400e;font-weight:600;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.rsg-link:hover{text-decoration:underline}
.rsg-acts{flex:0 0 auto;display:flex;align-items:center;gap:10px}
.rsg-sw{position:relative;width:44px;height:24px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;transition:background .15s;padding:0}
.rsg-sw:after{content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.rsg-sw[aria-checked=true]{background:$accent}
.rsg-sw[aria-checked=true]:after{left:23px}
.rsg-ibtn{width:34px;height:34px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:0}
.rsg-ibtn:hover{background:#f8fafc}
.rsg-ibtn[disabled]{opacity:.35;cursor:default}
.rsg-ibtn-danger{color:#b91c1c;border-color:#fecaca}
.rsg-ibtn-danger:hover{background:#fef2f2}
.rsg-move{display:inline-flex;gap:4px}
.rsg-locked{display:inline-flex;align-items:center;gap:6px;background:#e6eeed;color:#005447;border:1px solid #bfd4d1;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;white-space:nowrap}
.rsg-empty{border:2px dashed #cbd5e1;border-radius:12px;padding:32px 24px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:10px}
.rsg-empty-ico{width:52px;height:52px;border-radius:14px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:22px}
.rsg-empty h4{margin:0;font-size:16px;font-weight:600}
.rsg-empty p{color:#475569;max-width:560px;line-height:1.55}
.rsg-empty-b{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:6px}
.rsg-foot{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;border-top:1px solid #e2e8f0;padding-top:12px;font-size:12.5px;color:#64748b}
.rsg-foot span:first-child{font-weight:600;color:#334155}
.rsg-pintro{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px}
.rsg-pintro p{color:#475569;font-size:13.5px;display:flex;gap:8px;align-items:center}
.rsg button:focus-visible,.rsg input:focus-visible{outline:2px solid $accent;outline-offset:2px}
@media(max-width:1000px){.rsg-card,.rsg-prow{flex-wrap:wrap}.rsg-acts{width:100%;justify-content:flex-end}.rsg-context{flex-wrap:wrap}.rsg-mode{align-items:flex-start;text-align:left;max-width:none}}
@media(prefers-reduced-motion:reduce){.rsg *{transition:none!important;animation:none!important}}
</style>";
}

// Reputation rules redesign: one API snapshot, independently rendered widgets.
function rpr_require_admin($js=false,$form=false):bool{
    $users=new usersMenus();
    if($users->AsDnsAdministrator){return true;}
    $tpl=new template_admin();
    if($js){$tpl->js_error("{no_privileges}");}
    elseif($form){echo $tpl->post_error("{no_privileges}");}
    else{echo rpr_delete_error("{no_privileges}");}
    return false;
}
function rpr_delete_error($err):string{
    $tpl=new template_admin();
    $text=$tpl->_ENGINE_parse_body(rep_h($err));
    return strlen($text)>3?$text:$tpl->_ENGINE_parse_body("{error}").": ".$text;
}
function rpr_reload($function):string{
    return $function!==""?"if(typeof $function==='function'){ $function(); }":"LoadAjax('MainContent','fw.reputations.php');";
}
// ── Listes de reputation (refonte Stitch 2026-11-19) : un modele, un widget par fonction ──

function rpl_t($token,$a="",$b=""):string{
    if(!isset($GLOBALS["RPL_T"])){$GLOBALS["RPL_T"]=array();}
    if(!isset($GLOBALS["RPL_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["RPL_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2"),array($a,$b),$GLOBALS["RPL_T"][$token]);
}

// Modes de decision lus par le moteur (reputations.ruleMatches) : match-one et
// match-any donnent le meme verdict.
function rpl_modes():array{
    return array("match-one"=>"{rpl_mode_any}","match-all"=>"{rpl_mode_all}");
}
function rpl_mode_of($rule):string{
    return ((string)($rule["reptype"] ?? ""))==="match-all"?"match-all":"match-one";
}

function rpl_model():array{
    list($rules,$err)=rep_api_get("/reputations/admin/rules");
    $users=new usersMenus();
    $m=array("error"=>$err,"rows"=>array(),"on"=>0,"meta"=>false,"admin"=>(bool)$users->AsDnsAdministrator);
    foreach(rep_list($rules) as $rule){
        if(!is_array($rule)){continue;}
        if(rep_meta($rule)){$m["meta"]=true;}
        if(intval($rule["enabled"] ?? 0)===1){$m["on"]++;}
        $m["rows"][]=$rule;
    }
    return $m;
}

function popup_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=rpl_model();
    $h=array();
    $h[]="<div class='rpl'>";
    $h[]=rpl_hero();
    $h[]=rpl_test_strip($m);
    $h[]="<div id='rpl-lists'></div>";
    $h[]="</div>";
    echo rpl_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).rpl_js()
        ."<script>LoadAjaxSilent('rpl-lists','$page?search=');</script>";
    return true;
}

function table(){
    $tpl=new template_admin();
    $m=rpl_model();
    echo rpl_style().$tpl->_ENGINE_parse_body(rpl_lists_card($m)).rpl_tiny($m);
    return true;
}

function rpl_hero():string{
    return "<div class='rpl-hero'><div class='rpl-hero-ico'><i class='fas fa-shield-virus'></i></div><div class='rpl-hero-body'>"
        ."<h3>{rpl_hero_title} <span class='rpl-hero-sub'>{rpl_hero_sub}</span></h3><p>{rpl_hero_text}</p><div class='rpl-chips'>"
        ."<span class='rpl-chip'><i class='fas fa-bolt'></i> {rpl_chip_cache}</span>"
        ."<span class='rpl-chip'><i class='fas fa-plug'></i> {rpl_chip_many}</span>"
        ."<span class='rpl-chip'><i class='fas fa-server'></i> {rpl_chip_servers}</span>"
        ."<span class='rpl-chip'><i class='fas fa-magnifying-glass'></i> {rpl_chip_test}</span>"
        ."</div></div></div>";
}

function rpl_test_strip(array $m):string{
    if(count($m["rows"])==0){return "";}
    $opts=array();
    foreach($m["rows"] as $r){
        $opts[]="<option value='".intval($r["id"])."'>".rep_h($r["rulename"])."</option>";
    }
    $ph=rep_h(rpl_t("rpl_test_ph"));
    return "<div class='rpl-test'><form class='rpl-test-f' onsubmit=\"RplTest();return false;\">"
        ."<label class='rpl-test-l' for='rpl-ip'><span class='rpl-dot'></span>{rpl_test_q}</label>"
        ."<div class='rpl-in'><i class='fas fa-network-wired'></i><input id='rpl-ip' type='text' autocomplete='off' spellcheck='false' placeholder='$ph' aria-describedby='rpl-test-help'></div>"
        ."<span class='rpl-against'>{rpl_against}</span>"
        ."<select id='rpl-rule' class='rpl-sel' aria-label=\"".rep_h(rpl_t("rpl_against"))."\">".implode("",$opts)."</select>"
        ."<button type='submit' class='rpl-btn rpl-btn-dark'><i class='fas fa-magnifying-glass'></i> {rpl_test_btn}</button>"
        ."<span class='rpl-test-h' id='rpl-test-help'>{rpl_test_help}</span></form>"
        ."<div id='rpl-test-res' role='status' aria-live='polite'></div></div>";
}

function rpl_sources(array $rule,bool $canEdit):string{
    $groups=rep_list($rule["groups"] ?? null);
    $id=intval($rule["id"]);
    if(count($groups)==0){
        $add=$canEdit?" <a href='javascript:void(0)' class='rpl-link' onclick=\"Loadjs('fw.reputations.php?rule-js=$id&function=RplRefresh')\">{rpl_add_first}</a>":"";
        return "<div class='rpl-src'><span class='rpl-src-l'>{rpl_sources}</span><span class='rpl-tag rpl-tag-warn'><i class='fas fa-triangle-exclamation'></i><span class='rpl-tag-t'>{rpl_no_source}</span></span>$add</div>";
    }
    $chips=array();
    $n=0;
    foreach($groups as $g){
        $n++;
        $off=intval($g["enabled"] ?? 1)!==1;
        $title=trim((string)($g["description"] ?? ""));
        $t=$title!==""?" title=\"".rep_h($title)."\"":"";
        $gid=intval($g["id"]);
        $chips[]="<a href='javascript:void(0)' class='rpl-step".($off?" rpl-step-off":"")."'$t onclick=\"Loadjs('fw.reputations.php?group-js=$gid&ruleid=$id&function=RplRefresh')\">"
            ."<span class='rpl-step-n'>$n</span><i class='fas fa-layer-group'></i><span class='rpl-tag-t'>".rep_h($g["groupname"])."</span>".($off?" <span class='rpl-off-w'>{rpl_off}</span>":"")."</a>";
    }
    return "<div class='rpl-src'><span class='rpl-src-l'>{rpl_sources}</span><div class='rpl-steps'>".implode("<span class='rpl-join' aria-hidden='true'></span>",$chips)."</div></div>";
}

function rpl_row(array $rule,array $m):string{
    $id=intval($rule["id"]);
    $name=rep_h($rule["rulename"]);
    $on=intval($rule["enabled"] ?? 0)===1;
    $meta=rep_meta($rule);
    $canEdit=$m["admin"] && !$meta;
    $empty=count(rep_list($rule["groups"] ?? null))==0;
    $ico=$empty?"<span class='rpl-ico rpl-ico-warn'><i class='fas fa-triangle-exclamation'></i></span>":"<span class='rpl-ico'><i class='fas fa-shield-halved'></i></span>";
    $nameH=$m["admin"]?"<a href='javascript:void(0)' class='rpl-name' onclick=\"Loadjs('fw.reputations.php?rule-js=$id&function=RplRefresh')\">$name</a>":"<span class='rpl-name'>$name</span>";
    $state=$on?"<span class='rpl-badge rpl-badge-on'>{rpl_active}</span>":"<span class='rpl-badge'>{rpl_off}</span>";
    if($meta){$state.="<span class='rpl-badge rpl-badge-meta'><i class='fas fa-lock'></i> {rpl_meta}</span>";}
    $all=rpl_mode_of($rule)==="match-all";
    $mode="<span class='rpl-mode'><i class='fas fa-".($all?"link":"circle-nodes")."'></i> ".($all?"{rpl_mode_all}":"{rpl_mode_any}")."</span>";
    $side=array();
    if($canEdit){
        $sw=$on?"true":"false";
        $side[]="<div class='rpl-toggle'><span class='rpl-swl'>".($on?"{rpl_on}":"{rpl_off}")."</span><button type='button' class='rpl-switch' role='switch' aria-checked='$sw' aria-label=\"".rep_h(rpl_t("rpl_toggle_x",(string)$rule["rulename"]))."\" onclick=\"Loadjs('fw.reputations.php?enable-rule=$id&function=RplRefresh')\"><span class='rpl-knob'></span></button></div>";
    }elseif($meta){
        $side[]="<span class='rpl-locked'>{rpl_locked}</span>";
    }
    $side[]="<button type='button' class='rpl-btn' onclick=\"RplPick($id)\"><i class='fas fa-magnifying-glass'></i> {rpl_test}</button>";
    if($canEdit){
        $side[]="<button type='button' class='rpl-ibtn' aria-label=\"".rep_h(rpl_t("rpl_edit_x",(string)$rule["rulename"]))."\" title=\"".rep_h(rpl_t("rpl_edit"))."\" onclick=\"Loadjs('fw.reputations.php?rule-js=$id&function=RplRefresh')\"><i class='fas fa-pen'></i></button>";
        $side[]="<button type='button' class='rpl-ibtn rpl-ibtn-del' aria-label=\"".rep_h(rpl_t("rpl_delete_x",(string)$rule["rulename"]))."\" title=\"".rep_h(rpl_t("rpl_delete"))."\" onclick=\"Loadjs('fw.reputations.php?delete-rule=$id&function=RplRefresh')\"><i class='fas fa-trash'></i></button>";
    }
    $search=rep_h(strtolower((string)$rule["rulename"]." ".implode(" ",array_map(function($g){return (string)($g["groupname"] ?? "");},rep_list($rule["groups"] ?? null)))));
    return "<div class='rpl-row".($on?"":" rpl-row-off")."' data-s=\"$search\">$ico"
        ."<div class='rpl-id'><div class='rpl-head'>$nameH$state</div>$mode</div>"
        ."<div class='rpl-main'>".rpl_sources($rule,$canEdit)."</div>"
        ."<div class='rpl-side'>".implode("",$side)."</div></div>";
}

function rpl_empty(array $m):string{
    $btns="";
    if($m["admin"]){
        $btns="<div class='rpl-empty-b'><button type='button' class='rpl-btn rpl-btn-primary' onclick=\"Loadjs('fw.reputations.php?rule-js=0&function=RplRefresh')\"><i class='fas fa-plus'></i> {rpl_new}</button>"
            ."<button type='button' class='rpl-btn' onclick=\"Loadjs('fw.reputations.php?reset-js=yes&function=RplRefresh')\"><i class='fas fa-rotate-left'></i> {rpl_restore}</button></div>";
    }
    return "<div class='rpl-empty'><div class='rpl-empty-ico'><i class='fas fa-shield-virus'></i></div><h4>{rpl_empty_title}</h4><p>{rpl_empty_text}</p>$btns</div>";
}

function rpl_lists_card(array $m):string{
    if($m["error"]!==""){
        return "<div class='rpl-alert' role='alert'><i class='fas fa-circle-exclamation'></i><div>".rep_h($m["error"])."</div></div>";
    }
    $n=count($m["rows"]);
    $count=$n>0?"<span class='rpl-count'>".($n==1?rpl_t("rpl_count1",number_format($m["on"])):rpl_t("rpl_count",number_format($n),number_format($m["on"])))."</span>":"";
    $tools="";
    if($n>0){
        $tools.="<div class='rpl-search'><i class='fas fa-magnifying-glass'></i><input type='search' placeholder=\"".rep_h(rpl_t("rpl_search"))."\" aria-label=\"".rep_h(rpl_t("rpl_search"))."\" oninput=\"RplFilter(this.value)\"></div>";
    }
    if($m["admin"]){
        $tools.="<button type='button' class='rpl-btn rpl-btn-primary' onclick=\"Loadjs('fw.reputations.php?rule-js=0&function=RplRefresh')\"><i class='fas fa-plus'></i> {rpl_new}</button>";
    }
    $banner=$m["meta"]?"<div class='rpl-pad'>".rep_meta_banner()."</div>":"";
    $rows=array();
    foreach($m["rows"] as $r){$rows[]=rpl_row($r,$m);}
    $body=$n==0?rpl_empty($m):"<div class='rpl-list' id='rpl-rows'>".implode("",$rows)."</div>";
    return "<div class='rpl-card'><div class='rpl-card-h'><h3>{rpl_your_lists} $count</h3><div class='rpl-tools'>$tools</div></div>$banner$body</div>";
}

function rpl_tiny(array $m):string{
    $tpl=new template_admin();
    $buttons=array();
    if($m["admin"]){
        $buttons[]=array("Loadjs('fw.reputations.php?rule-js=0&function=RplRefresh');",ico_plus,"{rpl_new}");
        // La remise a zero effacerait les regles Meta (refusee par le daemon, 423) : bouton masque.
        if(!$m["meta"]){$buttons[]=array("Loadjs('fw.reputations.php?reset-js=yes&function=RplRefresh');","fas fa-rotate-left","{rpl_restore}");}
    }
    $buttons[]=array("RplRefresh();",ico_refresh,"{refresh}");
    $tiny=array("TITLE"=>"{rpl_hero_sub}","ICO"=>"fas fa-shield-virus","EXPL"=>"{rpl_tiny_expl}","URL"=>"reputation","BUTTONS"=>$tpl->table_buttons($buttons));
    $sig=md5(serialize($tiny));
    return "<script>NoSpinner();if(window.RplTinySig!==".json_encode($sig)."){window.RplTinySig=".json_encode($sig).";Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($tiny)))."');}</script>";
}

function rpl_js():string{
    $page=CurrentPageName();
    return "<script>
function RplRefresh(){LoadAjaxSilent('rpl-lists','$page?search=');}
function RplFilter(q){q=(q||'').toLowerCase().trim();var l=document.getElementById('rpl-rows');if(!l){return;}
 for(var i=0;i<l.children.length;i++){var r=l.children[i];r.style.display=(q===''||(r.getAttribute('data-s')||'').indexOf(q)>-1)?'':'none';}}
function RplPick(id){var s=document.getElementById('rpl-rule');if(s){s.value=id;}var i=document.getElementById('rpl-ip');if(i){i.focus();if(i.value.trim()!==''){RplTest();}}}
function RplTest(){var i=document.getElementById('rpl-ip'),s=document.getElementById('rpl-rule');if(!i||!s){return;}var ip=i.value.trim();if(ip===''){i.focus();return;}
 LoadAjaxSilent('rpl-test-res','$page?quick-test='+encodeURIComponent(s.value)+'&ip='+encodeURIComponent(ip));}
</script>";
}

// Test d'une adresse : resultat en ligne, ambre si listee, vert sinon (plus de
// div_error rouge pour une adresse blanche recherchee expres).
function rpl_quick_test():bool{
    $ID=intval($_GET["quick-test"]);
    $ip=trim((string)($_GET["ip"] ?? ""));
    $tpl=new template_admin();
    if(!filter_var($ip,FILTER_VALIDATE_IP)){
        echo "<div class='rpl-res rpl-res-err'><i class='fas fa-circle-exclamation'></i><div>".rep_h(strip_tags(rpl_t("rpl_bad_ip",$ip)))."</div></div>";
        return false;
    }
    list($rule,$err)=rep_api_get("/reputations/admin/rule/$ID");
    if($err!==""){echo "<div class='rpl-res rpl-res-err'><i class='fas fa-circle-exclamation'></i><div>".rep_h($err)."</div></div>";return false;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/reputations/check/$ID/".rawurlencode($ip)));
    if(!is_object($json) || !property_exists($json,"Found")){
        echo "<div class='rpl-res rpl-res-err'><i class='fas fa-circle-exclamation'></i><div>".$tpl->_ENGINE_parse_body("{rpl_check_failed}")."</div></div>";
        return false;
    }
    $mt=new modesctools();
    $mt->hostinfo($ip,true);
    $info=array();
    if(trim((string)$mt->hostname)!=="" && $mt->hostname!==$ip){$info[]="<span class='rpl-mono'>".rep_h($mt->hostname)."</span>";}
    $where=trim((string)$mt->country_name);
    if(strlen((string)$mt->city)>1){$where.=($where!==""?" (":"").$mt->city.($where!==""?")":"");}
    if($where!==""){
        $flag=strlen((string)$mt->flag)>3?"<img src='img/".rep_h($mt->flag)."' alt='' style='vertical-align:-2px'> ":"";
        $info[]=$flag.rep_h($where);
    }
    if(trim((string)$mt->isp)!==""){$info[]=rep_h(trim(((intval($mt->asn_number)>0)?"AS".intval($mt->asn_number)." ":"").$mt->isp));}
    $sub=count($info)>0?"<div class='rpl-res-sub'>".implode("<span class='rpl-sep'>&bull;</span>",$info)."</div>":"";
    $ipH="<span class='rpl-mono'>".rep_h($ip)."</span>";
    $name="<strong>".rep_h($rule["rulename"])."</strong>";
    if($json->Found){
        echo "<div class='rpl-res rpl-res-warn'><span class='rpl-res-ico'><i class='fas fa-triangle-exclamation'></i></span><div><div class='rpl-res-t'>".rpl_t("rpl_listed",$ipH,$name)."</div>$sub</div></div>";
        return true;
    }
    echo "<div class='rpl-res rpl-res-ok'><span class='rpl-res-ico'><i class='fas fa-circle-check'></i></span><div><div class='rpl-res-t'>".rpl_t("rpl_not_listed",$ipH,$name)."</div>$sub</div></div>";
    return true;
}

function rpl_style():string{
    if(isset($GLOBALS["RPL_STYLE"])){return "";}
    $GLOBALS["RPL_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.rpl{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:10px;max-width:1320px}
.rpl *,.rpl-card *{box-sizing:border-box}
.rpl-hero{position:relative;display:flex;gap:20px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px 28px 22px 32px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.rpl-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.rpl-hero-ico{flex:0 0 52px;height:52px;border-radius:14px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:22px}
.rpl-hero-body{flex:1;min-width:0}
.rpl-hero h3{margin:0 0 6px 0;font-size:20px;font-weight:600;letter-spacing:-.01em}
.rpl-hero-sub{display:inline-block;margin-left:8px;font-size:12px;font-weight:500;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;vertical-align:middle;white-space:nowrap}
.rpl-hero p{margin:0;line-height:1.65;color:#475569;max-width:900px}
.rpl-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.rpl-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155;white-space:nowrap}
.rpl-chip i,.rpl-chip svg{color:$accent}
.rpl-test{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px 24px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.rpl-test-f{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:0}
.rpl-test-l{display:flex;align-items:center;gap:8px;font-size:15px;font-weight:600;margin:0;white-space:nowrap}
.rpl-dot{width:8px;height:8px;border-radius:50%;background:$accent}
.rpl-in{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;background:#fff;color:#64748b}
.rpl-in input{border:0;outline:0;height:38px;width:200px;font-family:monospace;font-size:14px;color:#0f172a;background:transparent}
.rpl-in:focus-within,.rpl-search:focus-within{outline:2px solid $accent;outline-offset:1px}
.rpl-against{font-size:13px;color:#64748b}
.rpl-sel{height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:14px;color:#0f172a;background:#fff;min-width:220px;max-width:360px}
.rpl-test-h{flex-basis:100%;font-size:12px;color:#64748b}
#rpl-test-res:empty{display:none}
#rpl-test-res{margin-top:14px}
.rpl-res{display:flex;gap:14px;align-items:flex-start;border-radius:12px;padding:14px 18px;border:1px solid}
.rpl-res-ico{flex:0 0 36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
.rpl-res-t{font-size:14px;line-height:1.5}
.rpl-res-sub{margin-top:4px;font-size:13px}
.rpl-sep{margin:0 8px;opacity:.6}
.rpl-res-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.rpl-res-warn .rpl-res-ico{background:#fef3c7;color:#b45309}
.rpl-res-ok{background:#ecfdf5;border-color:#a7f3d0;color:#065f46}
.rpl-res-ok .rpl-res-ico{background:#d1fae5;color:#047857}
.rpl-res-err{background:#fef2f2;border-color:#fecaca;color:#b91c1c;align-items:center}
.rpl-mono{font-family:monospace;font-size:13px}
.rpl-alert{display:flex;gap:12px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;color:#b91c1c}
.rpl-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 1px 2px rgba(15,23,42,.06),0 8px 24px rgba(15,23,42,.04);overflow:hidden;color:#0f172a;font-size:14px}
.rpl-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 24px;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}
.rpl-card-h h3{margin:0;font-size:18px;font-weight:600;display:flex;align-items:center;gap:10px}
.rpl-count{border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.rpl-tools{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.rpl-search{display:flex;align-items:center;gap:8px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;color:#64748b}
.rpl-search input{border:0;outline:0;height:36px;width:240px;font-size:14px;background:transparent;color:#0f172a}
.rpl-pad{padding:16px 24px 0 24px}
.rpl-list{display:flex;flex-direction:column}
.rpl-row{display:flex;align-items:center;gap:18px;padding:18px 24px;border-bottom:1px solid #f1f5f9;transition:background .15s}
.rpl-row:last-child{border-bottom:0}
.rpl-row:hover{background:#f8fafc}
.rpl-row-off .rpl-ico,.rpl-row-off .rpl-id,.rpl-row-off .rpl-main{opacity:.65}
.rpl-ico{flex:0 0 42px;height:42px;border-radius:12px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:17px}
.rpl-ico-warn{background:#fef3c7;color:#b45309}
.rpl-id{flex:0 0 300px;min-width:0}
.rpl-head{display:flex;align-items:center;flex-wrap:wrap;gap:6px 8px;margin-bottom:6px}
.rpl-name{font-size:16px;font-weight:600;color:#0f172a;text-decoration:none;overflow-wrap:anywhere}
a.rpl-name:hover{color:$dark}
.rpl-badge{display:inline-flex;align-items:center;gap:5px;border:1px solid #e2e8f0;background:#f1f5f9;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:600;color:#64748b;white-space:nowrap}
.rpl-badge-on{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
.rpl-badge-meta{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.rpl-mode{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border-radius:8px;padding:3px 10px;font-size:13px;color:#334155}
.rpl-mode i,.rpl-mode svg{color:#64748b}
.rpl-main{flex:1;min-width:0}
.rpl-src{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.rpl-src-l{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8}
.rpl-steps{display:flex;align-items:center;flex-wrap:wrap;gap:6px 0;min-width:0}
.rpl-join{display:inline-block;width:14px;height:1px;background:#cbd5e1;margin:0 4px}
.rpl-step{display:inline-flex;align-items:center;gap:7px;max-width:260px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;padding:4px 10px 4px 5px;font-size:13px;color:#0f172a;text-decoration:none;white-space:nowrap}
.rpl-step:hover{border-color:$line;background:$soft;text-decoration:none;color:#0f172a}
.rpl-step i,.rpl-step svg{color:$accent;flex:0 0 auto}
.rpl-step-n{flex:0 0 20px;height:20px;border-radius:6px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center}
.rpl-step-off{color:#94a3b8;text-decoration:line-through}
.rpl-step-off i,.rpl-step-off svg{color:#94a3b8}
.rpl-off-w{font-size:11px;text-decoration:none}
.rpl-tag{display:inline-flex;align-items:center;gap:6px;max-width:380px;border-radius:8px;padding:4px 10px;font-size:13px;font-weight:500;white-space:nowrap;border:1px solid}
.rpl-tag-warn{background:#fffbeb;border-color:#fde68a;color:#b45309}
.rpl-tag-t{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.rpl-link{font-size:13px;font-weight:600;color:$dark;text-decoration:underline;white-space:nowrap}
.rpl-side{display:flex;align-items:center;gap:10px;flex:0 0 auto}
.rpl-toggle{display:flex;align-items:center;gap:8px;min-width:86px;justify-content:flex-end}
.rpl-swl{font-size:13px;font-weight:600;color:#475569}
.rpl-switch{position:relative;width:46px;height:26px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
.rpl-switch[aria-checked=true]{background:$accent}
.rpl-knob{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.rpl-switch[aria-checked=true] .rpl-knob{left:23px}
.rpl-locked{font-size:13px;font-style:italic;color:#64748b;white-space:nowrap}
.rpl-btn{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:7px 14px;font-size:14px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap}
.rpl-btn:hover{background:#f8fafc}
.rpl-btn-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600}
.rpl-btn-primary:hover{background:$dark;border-color:$dark}
.rpl-btn-dark{background:#0f172a;border-color:#0f172a;color:#fff;font-weight:600;height:40px}
.rpl-btn-dark:hover{background:#1e293b}
.rpl-ibtn{border:0;background:transparent;color:#64748b;width:34px;height:32px;border-radius:8px;cursor:pointer}
.rpl-ibtn:hover{background:#f1f5f9;color:#0f172a}
.rpl-ibtn-del:hover{background:#fef2f2;color:#b91c1c}
.rpl-switch:focus-visible,.rpl-btn:focus-visible,.rpl-ibtn:focus-visible,.rpl-step:focus-visible,.rpl-name:focus-visible,.rpl-sel:focus-visible,.rpl-link:focus-visible{outline:2px solid $accent;outline-offset:2px}
.rpl-empty{display:flex;flex-direction:column;align-items:center;text-align:center;padding:40px 24px;gap:10px}
.rpl-empty-ico{width:72px;height:72px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:28px}
.rpl-empty h4{margin:4px 0 0 0;font-size:17px;font-weight:600}
.rpl-empty p{margin:0;color:#475569;max-width:580px;line-height:1.6}
.rpl-empty-b{display:flex;gap:10px;margin-top:6px}
.rpl-form-note{margin:0 0 12px 0;padding:10px 14px;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:13px}
@media (max-width:1200px){.rpl-row{flex-wrap:wrap}.rpl-id{flex:1 1 260px}.rpl-main{flex-basis:100%;order:3}.rpl-side{margin-left:auto}}
@media (prefers-reduced-motion:reduce){.rpl *,.rpl-card *{transition:none!important}}
</style>";
}
