<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style(): feuille de style partagee des tableaux d'entites (tableaux de regles)
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

// Fenetre "Caching - Expert Mode" d'un site du reverse-proxy (maquette Stitch
// stitch_cache_expert_mode_modal, 2026-11-17). Ouverte depuis la carte "Advanced
// caching" de fw.nginx.sites.optimize.php. Trois onglets : reglages, regles
// "Never cache" (exclude), regles "Cache longer" (include).
// Comportement reel du demon : reverse-proxy ConfiguratorCaches.go (PerSiteExpertCache,
// perSiteCacheDirectives, cacheKeyBody, OvercacheGroups) et CacheRules.

// --- dispatch : TOUS les endpoints AJAX en haut, avant tout rendu, chacun exit; ------------------
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["flat"])){flat();exit;}
if(isset($_GET["flat-config"])){flat_config();exit;}
if(isset($_GET["flat-switch"])){flat_switch();exit;}
if(isset($_GET["ac-off-js"])){ac_off_js();exit;}
if(isset($_POST["ac-disable"])){ac_disable();exit;}
if(isset($_GET["ac-ttl-js"])){ac_ttl_js();exit;}
if(isset($_GET["ac-ttl-popup"])){ac_ttl_popup();exit;}
if(isset($_POST["ac-ttl-serviceid"])){ac_ttl_save();exit;}
if(isset($_GET["ac-maxsize-js"])){ac_maxsize_js();exit;}
if(isset($_GET["ac-maxsize-popup"])){ac_maxsize_popup();exit;}
if(isset($_GET["ac-inactive-js"])){ac_inactive_js();exit;}
if(isset($_GET["ac-inactive-popup"])){ac_inactive_popup();exit;}
if(isset($_POST["ac-simple-field"])){ac_simple_save();exit;}
if(isset($_GET["ac-stale-switch"])){ac_stale_switch();exit;}
if(isset($_GET["ac-forcettl-switch"])){ac_forcettl_switch();exit;}
if(isset($_GET["excl-tab"])){excl_tab();exit;}
if(isset($_GET["over-tab"])){over_tab();exit;}
if(isset($_GET["search"])){ac_rules_table();exit;}
if(isset($_GET["rule-js"])){ac_rule_js();exit;}
if(isset($_GET["rule-popup"])){ac_rule_popup();exit;}
if(isset($_POST["ac-rule-serviceid"])){ac_rule_save();exit;}
if(isset($_GET["rule-delete-js"])){ac_rule_delete_js();exit;}
if(isset($_POST["ac-rule-delete-id"])){ac_rule_delete();exit;}
if(isset($_GET["rule-enable-toggle"])){ac_rule_enable_toggle();exit;}
js();


function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"]);
    $srv=get_servicename($serviceid);
    return $tpl->js_dialog5("$srv: {cache_expert}","$page?tabs=$serviceid");
}

// =====================================================================================
// Modele unique : lu une fois, rend tout ce que les widgets affichent
// =====================================================================================

// Droit le plus etroit qui couvre la configuration d'un site web (meme drapeau que la
// fenetre Optimization, nopt_can_write()).
function acx_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemWebMaster;
}

function acx_model($serviceid):array{
    $serviceid=intval($serviceid);
    $s=new socksngix($serviceid);
    $p=ac_load_params($s);
    $m=array("id"=>$serviceid,"on"=>intval($s->GET_INFO("AdvancedCaching"))==1,"p"=>$p,
        "type"=>intval($s->GetType()),"can"=>acx_can_write());
    // Le demon range toujours le cache dans Directory/site<ID> (Configs.go) ; la valeur
    // WorkDir stockee est souvent vide et ne fait pas foi.
    $m["workdir"]=rtrim($p["Directory"],"/")."/site".$serviceid;
    // Seuls les types 2, 4 et 19 emettent les emplacements de forcage (OvercacheGroups).
    $m["force_type"]=in_array($m["type"],array(2,4,19));
    $m["forced"]=$m["on"] && intval($p["ForceServerTTL"])==1 && $m["force_type"];

    $r=array("ok"=>false,"exclude"=>0,"include"=>0,"groups"=>0,"broad"=>array());
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/cache-rules/$serviceid"),true);
    if(is_array($data) && !empty($data["Status"])){
        $r["ok"]=true;
        $ttls=array();
        $rows=(isset($data["rules"]) && is_array($data["rules"]))?$data["rules"]:array();
        foreach($rows as $row){
            if(!is_array($row)){continue;}
            $type=strval($row["ruletype"] ?? "");
            if($type=="exclude"){$r["exclude"]++;continue;}
            if($type!="include"){continue;}
            $r["include"]++;
            if(intval($row["enabled"] ?? 0)!=1){continue;}
            $t=intval($row["ttl_minutes"] ?? 0);
            if($t>0){$ttls[$t]=true;}
            $pat=strval($row["pattern"] ?? "");
            if(strval($row["matchmode"] ?? "")=="path" && strlen($pat)<=2){$r["broad"][]=$pat;}
        }
        $r["groups"]=count($ttls);
    }
    $m["rules"]=$r;
    return $m;
}

// --- formatage ---------------------------------------------------------------------------------
function acx_dur_seconds($sec):string{
    $t=ac_ttl_split($sec);
    $n=$t["value"];
    $u=array(86400=>array("{nopt_day}","{nopt_days}"),3600=>array("{nopt_hour}","{nopt_hours}"),
        60=>array("{nopt_minute}","{nopt_minutes}"),1=>array("{nopt_second}","{nopt_seconds}"));
    $l=isset($u[$t["unit"]])?$u[$t["unit"]]:$u[1];
    return $n." ".($n==1?$l[0]:$l[1]);
}
function acx_dur_hours($h):string{return acx_dur_seconds(intval($h)*3600);}
function acx_dur_minutes($m):string{return acx_dur_seconds(intval($m)*60);}

// =====================================================================================
// Onglets
// =====================================================================================

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["tabs"]);
    $m=acx_model($serviceid);
    $off="";
    if(!$m["on"]){$off="<span class='acx-tab-off acx-offnote-$serviceid'>{nopt_off}</span>";}
    $cntE=$m["rules"]["ok"]?"<span class='acx-cnt'>{$m["rules"]["exclude"]}</span>":"";
    $cntI=$m["rules"]["ok"]?"<span class='acx-cnt'>{$m["rules"]["include"]}</span>":"";
    $array=array();
    $array["<span class='acx-tab'>{acx_tab_settings}<small>{global_parameters}</small></span>"]="$page?flat=$serviceid";
    $array["<span class='acx-tab'>{acx_tab_never}<small>{acx_tab_never_sub}</small>$cntE$off</span>"]="$page?excl-tab=$serviceid";
    $array["<span class='acx-tab'>{acx_tab_longer}<small>{acx_tab_longer_sub}</small>$cntI$off</span>"]="$page?over-tab=$serviceid";
    echo acx_style().acx_js().$tpl->tabs_default($array);
    return true;
}

// =====================================================================================
// Onglet 1 : reglages
// =====================================================================================

function flat():bool{
    $page=CurrentPageName();
    $serviceid=intval($_GET["flat"]);
    echo "<div id='flat-$serviceid'></div>";
    echo "<script>LoadAjax('flat-$serviceid','$page?flat-config=$serviceid');</script>";
    return true;
}

function flat_config():bool{
    $tpl=new template_admin();
    $m=acx_model(intval($_GET["flat-config"]));
    $h=array();
    $h[]="<div class='ak acx'>";
    $h[]=acx_banner($m);
    $h[]=acx_main_switch($m);
    $h[]=acx_tiles($m);
    $h[]=acx_behave($m);
    $h[]=acx_storage($m);
    $h[]=acx_tip();
    $h[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
    return true;
}

// Bandeau d'accroche. Chaque puce est une verite du demon (cacheKeyBody) ; celle du
// contenu perime depend du reglage reel UseStale.
function acx_banner($m):string{
    $chips=array();
    $chips[]=array("fas fa-lock","{acx_chip_auth}");
    $chips[]=array("fas fa-cookie-bite","{acx_chip_cookie}");
    $chips[]=array("fas fa-arrow-right-arrow-left","{acx_chip_get}");
    if(intval($m["p"]["UseStale"])==1){$chips[]=array("fas fa-life-ring","{acx_chip_stale}");}
    $h="<section class='acx-hero'><span class='acx-hero-ico'><i class='fas fa-bolt'></i></span><div class='acx-hero-b'>";
    $h.="<h2 class='acx-hero-t'>{acx_hero_title}</h2><p class='acx-hero-p'>{acx_hero_text}</p><div class='ak-chips'>";
    foreach($chips as $c){$h.="<span class='acx-chip'><i class='{$c[0]}'></i> {$c[1]}</span>";}
    return $h."</div></div></section>";
}

// Interrupteur generique : role=switch, etat ecrit a cote, desactive sans le droit.
function acx_switch($m,$on,$label,$js):string{
    $checked=$on?"true":"false";
    $state=$on?"<span class='ak-state ak-ok'><span class='ak-dot'></span> {nopt_on}</span>":"<span class='ak-state ak-off'><span class='acx-hollow'></span> {nopt_off}</span>";
    if(!$m["can"]){
        return "$state<button type='button' role='switch' class='ak-sw' aria-checked='$checked' aria-label=\"$label\" title=\"{acx_no_priv}\" disabled></button>";
    }
    return "$state<button type='button' role='switch' class='ak-sw' aria-checked='$checked' aria-label=\"$label\" OnClick=\"this.disabled=true;$js\"></button>";
}

function acx_main_switch($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    // Couper efface le cache du site sur le disque : confirmation avant ; allumer est direct.
    $js=$m["on"]?"Loadjs('$page?ac-off-js=$ID')":"Loadjs('$page?flat-switch=$ID&value=1')";
    $h="<section class='ak-card acx-row acx-main'><div class='acx-row-b'>";
    $h.="<div class='acx-row-t'>{acx_main_title} <small>{enable_feature}</small></div>";
    $h.="<div class='ak-help'>{acx_main_help}</div>";
    if($m["on"]){$h.="<div class='ak-help'>{acx_main_off_warn}</div>";}
    $h.="</div><div class='acx-row-sw'>".acx_switch($m,$m["on"],"{acx_main_title}",$js)."</div></section>";
    return $h;
}

function acx_tile($m,$ico,$title,$sub,$value,$help,$js):string{
    $btn="";
    if($m["can"]){$btn="<button type='button' class='ak-link acx-change' OnClick=\"$js\">{acx_change} <i class='fas fa-pen'></i></button>";}
    $h="<div class='ak-card acx-tile'><div class='acx-tile-h'><span class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='$ico'></i></span>$btn</div>";
    $h.="<div class='acx-tile-t'>$title</div><div class='acx-sub'>$sub</div>";
    $h.="<div class='acx-tile-v'>$value</div><div class='acx-tile-help'>$help</div></div>";
    return $h;
}

function acx_tiles($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    $p=$m["p"];
    $h="<section><h3 class='ak-sec-t'>{acx_kept_title}</h3><div class='acx-tiles'>";
    $h.=acx_tile($m,"fas fa-clock","{acx_ttl_title}","{ttl}",acx_dur_seconds($p["TTLCache"]),"{acx_ttl_help}","Loadjs('$page?ac-ttl-js=$ID')");
    $h.=acx_tile($m,"fas fa-hard-drive","{acx_size_title}","{cache_max_size_gb}",intval($p["MaxSizeGigabytes"])." {acx_gb}","{acx_size_help}","Loadjs('$page?ac-maxsize-js=$ID')");
    $h.=acx_tile($m,"fas fa-hourglass-half","{acx_inactive_title}","{cache_inactive_hours}",acx_dur_hours($p["InactiveTime"]),"{acx_inactive_help}","Loadjs('$page?ac-inactive-js=$ID')");
    return $h."</div></section>";
}

function acx_behave($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    $p=$m["p"];
    $stale=intval($p["UseStale"])==1;
    $force=intval($p["ForceServerTTL"])==1;
    $h="<section><h3 class='ak-sec-t'>{acx_behave_title}</h3><div class='acx-stack'>";

    $h.="<div class='ak-card acx-row'><span class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='fas fa-life-ring'></i></span><div class='acx-row-b'>";
    $h.="<div class='acx-row-t'>{acx_stale_title} <small>{cache_use_stale}</small></div><div class='ak-help'>{acx_stale_help}</div></div>";
    $h.="<div class='acx-row-sw'>".acx_switch($m,$stale,"{acx_stale_title}","Loadjs('$page?ac-stale-switch=$ID')")."</div></div>";

    $h.="<div class='ak-card acx-row acx-row-wrap'><span class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='fas fa-shield-halved'></i></span><div class='acx-row-b'>";
    $h.="<div class='acx-row-t'>{acx_force_title} <small>{acx_force_sub}</small></div><div class='ak-help'>{acx_force_help}</div>";
    if(!$m["force_type"]){$h.="<div class='ak-help'>{acx_force_type}</div>";}
    $h.="</div><div class='acx-row-sw'>".acx_switch($m,$force,"{acx_force_title}","Loadjs('$page?ac-forcettl-switch=$ID')")."</div>";
    // Le risque est dit AVANT d'activer, et reste visible tant que c'est actif.
    $n=$m["rules"]["ok"]?" ({$m["rules"]["include"]})":"";
    $h.="<div class='ak-banner ak-warn acx-note' role='note'><i class='fas fa-triangle-exclamation'></i><span class='ak-banner-m'>{acx_force_warn}";
    if($m["forced"] && count($m["rules"]["broad"])>0){
        $list=array();
        foreach($m["rules"]["broad"] as $b){$list[]="<span class='ak-mono'>".htmlspecialchars($b)."</span>";}
        $h.="<br><strong>{acx_force_broad}</strong> ".@implode(", ",$list);
    }
    $h.="</span><button type='button' class='ak-link' OnClick=\"AcxTab(this,2)\">{acx_see_longer}$n <i class='fas fa-arrow-right'></i></button></div>";
    $h.="</div>";
    return $h."</div></section>";
}

function acx_storage($m):string{
    $h="<section><h3 class='ak-sec-t'>{acx_where_title}</h3><div class='ak-card acx-row'>";
    $h.="<span class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='fas fa-folder-open'></i></span><div class='acx-row-b'>";
    $h.="<div class='acx-row-t ak-mono'>".htmlspecialchars($m["workdir"])."</div>";
    $h.="<div class='acx-sub'>{cache_directory} &middot; {acx_workdir_sub}</div></div><span class='ak-muted acx-ro'>{acx_where_ro}</span></div></section>";
    return $h;
}

function acx_tip():string{
    return "<div class='acx-tip'><i class='fas fa-circle-info'></i> <span>{acx_tip_purge}</span></div>";
}

// --- ecritures de l'onglet 1 : droit verifie cote serveur, bornes, journal --------------------
function acx_deny_js($serviceid):bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $tpl->js_error("{acx_no_priv}");
    echo refresh_global_no_close($serviceid);
    return false;
}

// value=1 allume, value=0 eteint (le chemin normal pour eteindre passe par ac_disable,
// apres confirmation).
function flat_switch():bool{
    $serviceid=intval($_GET["flat-switch"]);
    if(!acx_can_write()){return acx_deny_js($serviceid);}
    $value=intval($_GET["value"] ?? 1)==1?1:0;
    acx_set_enabled($serviceid,$value);
    header("content-type: application/x-javascript");
    echo refresh_global_no_close($serviceid);
    if($value==1){echo "\$('.acx-offnote-$serviceid').remove();\n";}
    return true;
}
function acx_set_enabled($serviceid,$value):bool{
    $socknginx=new socksngix($serviceid);
    $socknginx->SET_INFO("AdvancedCaching",$value);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    $srv=get_servicename($serviceid);
    return admin_tracks("Set Caching - Expert Mode enable=$value for reverse-proxy service $srv");
}
function ac_off_js():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["ac-off-js"]);
    $after=str_replace("\n","",refresh_global_no_close($serviceid));
    return $tpl->js_dialog_confirm_action("{acx_off_confirm}","ac-disable",$serviceid,$after);
}
function ac_disable(){
    $tpl=new template_admin();
    $serviceid=intval($_POST["ac-disable"]);
    if(!acx_can_write()){echo $tpl->post_error("{acx_no_priv}");return;}
    acx_set_enabled($serviceid,0);
}

// Champs scalaires de AdvancedCacheParams. Le blob est souvent PARTIEL : le demon
// applique ses defauts pour ce qui manque (ac_load_params, class.nginx.params.inc).
function ac_save_param($sock,$serviceid,$key,$value):bool{
    $raw=$sock->GET_INFO("AdvancedCacheParams");
    $json=json_decode($raw,true);
    if(!is_array($json)){$json=array();}
    $json[$key]=$value;
    $ok=$sock->SET_INFO("AdvancedCacheParams",json_encode($json));
    if($ok){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
        admin_tracks("Set Caching - Expert Mode $key=$value for reverse-proxy service $serviceid");
    }
    return $ok;
}
function ac_ttl_split($seconds):array{
    $seconds=intval($seconds);
    if($seconds>0 && $seconds%86400==0){return array("value"=>intval($seconds/86400),"unit"=>86400);}
    if($seconds>0 && $seconds%3600==0){return array("value"=>intval($seconds/3600),"unit"=>3600);}
    if($seconds>0 && $seconds%60==0){return array("value"=>intval($seconds/60),"unit"=>60);}
    return array("value"=>$seconds,"unit"=>1);
}
function acx_after_modal($serviceid):string{
    $page=CurrentPageName();
    return "dialogInstance6.close();LoadAjaxSilent('flat-$serviceid','$page?flat-config=$serviceid');";
}

// --- modale "Keep each page for" (valeur + unite) ---------------------------------------------
function ac_ttl_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["ac-ttl-js"]);
    return $tpl->js_dialog6("{acx_ttl_title}","$page?ac-ttl-popup=$serviceid",560);
}
function ac_ttl_popup(){
    $tpl=new template_admin();
    $serviceid=intval($_GET["ac-ttl-popup"]);
    $sock=new socksngix($serviceid);
    $params=ac_load_params($sock);
    $ttl=ac_ttl_split($params["TTLCache"]);
    $UNITHASH=array(1=>"{nopt_seconds}",60=>"{nopt_minutes}",3600=>"{nopt_hours}",86400=>"{nopt_days}");
    $form=array();
    $form[]=$tpl->field_hidden("ac-ttl-serviceid",$serviceid);
    $form[]=$tpl->field_text("ac-ttl-value","{acx_value}",strval($ttl["value"]),true,"{acx_ttl_help}");
    $form[]=$tpl->field_array_hash_simple($UNITHASH,"ac-ttl-unit","{acx_unit}",strval($ttl["unit"]));
    $html=array();
    $html[]=$tpl->div_explain("{acx_ttl_title}||{cache_ttl_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{apply}",acx_after_modal($serviceid),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
function ac_ttl_save(){
    $tpl=new template_admin();$tpl->CLEAN_POST();
    $serviceid=intval($_POST["ac-ttl-serviceid"]);
    if(!acx_can_write()){echo $tpl->post_error("{acx_no_priv}");return;}
    $value=intval($_POST["ac-ttl-value"]);
    $unit=intval($_POST["ac-ttl-unit"]);
    if(!in_array($unit,array(1,60,3600,86400))){$unit=1;}
    $seconds=$value*$unit;
    // 0 ferait retomber le demon sur son defaut (1 heure) sans le dire : refuse.
    if($value<1 || $seconds>31536000){echo $tpl->post_error("{acx_err_ttl}");return;}
    $sock=new socksngix($serviceid);
    if(!ac_save_param($sock,$serviceid,"TTLCache",$seconds)){
        echo $tpl->post_error("{operation_failed}");
    }
}

// --- modale "Disk space allowed" (Go) ---------------------------------------------------------
function ac_maxsize_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["ac-maxsize-js"]);
    return $tpl->js_dialog6("{acx_size_title}","$page?ac-maxsize-popup=$serviceid",560);
}
function ac_maxsize_popup(){
    $tpl=new template_admin();
    $serviceid=intval($_GET["ac-maxsize-popup"]);
    $sock=new socksngix($serviceid);
    $params=ac_load_params($sock);
    $form=array();
    $form[]=$tpl->field_hidden("ac-simple-serviceid",$serviceid);
    $form[]=$tpl->field_hidden("ac-simple-field","MaxSizeGigabytes");
    $form[]=$tpl->field_text("ac-simple-value","{acx_size_title} ({acx_gb})",strval($params["MaxSizeGigabytes"]),true,"{acx_size_help}");
    $html=array();
    $html[]=$tpl->div_explain("{acx_size_title}||{cache_max_size_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{apply}",acx_after_modal($serviceid),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// --- modale "Forget unused pages after" (heures) ----------------------------------------------
function ac_inactive_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["ac-inactive-js"]);
    return $tpl->js_dialog6("{acx_inactive_title}","$page?ac-inactive-popup=$serviceid",560);
}
function ac_inactive_popup(){
    $tpl=new template_admin();
    $serviceid=intval($_GET["ac-inactive-popup"]);
    $sock=new socksngix($serviceid);
    $params=ac_load_params($sock);
    $form=array();
    $form[]=$tpl->field_hidden("ac-simple-serviceid",$serviceid);
    $form[]=$tpl->field_hidden("ac-simple-field","InactiveTime");
    $form[]=$tpl->field_text("ac-simple-value","{acx_inactive_title} ({nopt_hours})",strval($params["InactiveTime"]),true,"{acx_inactive_help}");
    $html=array();
    $html[]=$tpl->div_explain("{acx_inactive_title}||{cache_inactive_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{apply}",acx_after_modal($serviceid),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// --- sauvegarde des deux champs entiers : liste blanche + bornes ------------------------------
function ac_simple_save(){
    $tpl=new template_admin();$tpl->CLEAN_POST();
    $serviceid=intval($_POST["ac-simple-serviceid"]);
    if(!acx_can_write()){echo $tpl->post_error("{acx_no_priv}");return;}
    $field=strval($_POST["ac-simple-field"]);
    $RANGES=array("MaxSizeGigabytes"=>array(1,10000,"{acx_err_size}"),"InactiveTime"=>array(1,8760,"{acx_err_inactive}"));
    if(!isset($RANGES[$field])){echo $tpl->post_error("{operation_failed}");return;}
    $raw=trim(strval($_POST["ac-simple-value"]));
    $value=intval($raw);
    if(!preg_match('/^[0-9]+$/',$raw) || $value<$RANGES[$field][0] || $value>$RANGES[$field][1]){
        echo $tpl->post_error($RANGES[$field][2]);
        return;
    }
    $sock=new socksngix($serviceid);
    if(!ac_save_param($sock,$serviceid,$field,$value)){
        echo $tpl->post_error("{operation_failed}");
    }
}

function ac_stale_switch():bool{
    $serviceid=intval($_GET["ac-stale-switch"]);
    if(!acx_can_write()){return acx_deny_js($serviceid);}
    $sock=new socksngix($serviceid);
    $params=ac_load_params($sock);
    $new=intval($params["UseStale"])==0?1:0;
    ac_save_param($sock,$serviceid,"UseStale",$new);
    header("content-type: application/x-javascript");
    echo refresh_global_no_close($serviceid);
    return true;
}

function ac_forcettl_switch():bool{
    $serviceid=intval($_GET["ac-forcettl-switch"]);
    if(!acx_can_write()){return acx_deny_js($serviceid);}
    $sock=new socksngix($serviceid);
    $params=ac_load_params($sock);
    $new=intval($params["ForceServerTTL"])==0?1:0;
    ac_save_param($sock,$serviceid,"ForceServerTTL",$new);
    header("content-type: application/x-javascript");
    echo refresh_global_no_close($serviceid);
    return true;
}

// =====================================================================================
// Onglets 2 et 3 : regles, search_block (section 7) sur /cache-rules/{id}
// =====================================================================================

function excl_tab():bool{
    return ac_rule_type_tab(intval($_GET["excl-tab"]),"exclude");
}
function over_tab():bool{
    return ac_rule_type_tab(intval($_GET["over-tab"]),"include");
}
function ac_rule_type_tab($serviceid,$ruletype):bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=acx_model($serviceid);
    $reloadFn=($ruletype=="include")?"CacheOverReload":"CacheExclReload";

    $html=array();
    $html[]="<script>window.$reloadFn=window.$reloadFn||function(){};</script>";
    $html[]="<div class='ak acx'>";
    $html[]="<div class='acx-intro'><i class='fas fa-circle-info'></i><span>".($ruletype=="include"?"{acx_longer_intro}":"{acx_never_intro}")."</span></div>";
    if(!$m["on"]){
        $btn="";
        if($m["can"]){$btn="<button type='button' class='ak-btn ak-btn-sm' OnClick=\"Loadjs('$page?flat-switch=$serviceid&value=1')\">{acx_main_title}</button>";}
        $html[]="<div class='ak-banner ak-warn acx-offnote-$serviceid' role='note'><i class='fas fa-triangle-exclamation'></i><span class='ak-banner-m'>{acx_rules_off}</span>$btn</div>";
    }
    if($ruletype=="include"){
        // Ou la regle agit vraiment : navigateur seul, ou aussi l'appliance.
        $st=$m["forced"]?"{acx_stored_yes}":"{acx_stored_browser}";
        $html[]="<div class='acx-status'><i class='fas fa-server'></i><span>$st</span><button type='button' class='ak-link' OnClick=\"AcxTab(this,0)\">{acx_change}</button></div>";
    }
    $html[]="<div class='acx-tools'>";
    if($m["can"]){
        $html[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?rule-js=0&serviceid=$serviceid&ruletype=$ruletype')\"><i class='fas fa-plus'></i> {new_item}</button>";
    }
    $html[]="</div>";
    $html[]="<div>".$tpl->search_block($page,"","","","&serviceid=$serviceid&ruletype=$ruletype")."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// --- responder search_block partage par les 2 onglets, distingue par $_GET["ruletype"] --------
function ac_rules_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"]);
    $ruletype=(isset($_GET["ruletype"]) && $_GET["ruletype"]==="include")?"include":"exclude";
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $search=strtolower(trim(str_replace(array("*","%"),"",(string)(isset($_GET["search"])?$_GET["search"]:""))));
    $can=acx_can_write();

    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/cache-rules/$serviceid?type=$ruletype");
    $data=json_decode($resp,true);
    $readable=is_array($data) && !empty($data["Status"]);
    $rows=($readable && isset($data["rules"]) && is_array($data["rules"]))?$data["rules"]:array();
    $total=count($rows);

    // Au-dela de 8 durees distinctes, le demon ne force plus le stockage sur l'appliance
    // (maxOvercacheGroups) : les regles en trop restent cote navigateur.
    $groups=array();
    foreach($rows as $r){
        if(is_array($r) && intval($r["enabled"] ?? 0)==1 && intval($r["ttl_minutes"] ?? 0)>0){$groups[intval($r["ttl_minutes"])]=true;}
    }

    if($search!=""){
        $filtered=array();
        foreach($rows as $r){
            $hay=strtolower((isset($r["pattern"])?$r["pattern"]:"")." ".(isset($r["comment"])?$r["comment"]:""));
            if(strpos($hay,$search)!==false){$filtered[]=$r;}
        }
        $rows=$filtered;
    }

    $reloadFn=($ruletype=="include")?"CacheOverReload":"CacheExclReload";
    $html=array();
    if($ruletype=="include" && count($groups)>8){
        $html[]="<div class='ak-banner ak-warn' role='note'><i class='fas fa-triangle-exclamation'></i><span class='ak-banner-m'>{acx_groups_over}</span></div>";
    }
    if(!$readable){
        $html[]="<div class='ak-banner ak-err' role='note'><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m'>{acx_rules_unreadable}</span></div>";
    }elseif($total==0){
        $html[]=acx_empty($serviceid,$ruletype,$function,$can);
    }else{
        $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
        $html[]="<table class='awc-tbl'><thead><tr>";
        $html[]="<th>{acx_col_rule}</th><th>{acx_col_matches}</th>";
        if($ruletype=="include"){$html[]="<th>{acx_col_keep}</th>";}
        $html[]="<th>{acx_col_status}</th><th class='center'>&nbsp;</th>";
        $html[]="</tr></thead><tbody>";
        if(count($rows)==0){
            $colspan=($ruletype=="include")?5:4;
            $html[]="<tr><td colspan='$colspan' class='awc-empty'>{acx_no_match}</td></tr>";
        }
        foreach($rows as $r){
            if(!is_array($r)){continue;}
            $html[]=ac_rule_row($page,$serviceid,$ruletype,$function,$r,$can);
        }
        $html[]="</tbody></table></div></div></div>";
    }
    if($function!=""){
        $html[]="<script>if(typeof $function==='function'){window.$reloadFn=$function;}</script>";
    }
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// Etat vide : une invitation, avec l'exemple concret et l'action.
function acx_empty($serviceid,$ruletype,$function,$can):string{
    $page=CurrentPageName();
    $ico=$ruletype=="include"?"fas fa-forward":"fas fa-ban";
    $t=$ruletype=="include"?"{acx_empty_longer_t}":"{acx_empty_never_t}";
    $p=$ruletype=="include"?"{acx_empty_longer_p}":"{acx_empty_never_p}";
    $btn="";
    if($can){$btn="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?rule-js=0&serviceid=$serviceid&ruletype=$ruletype&function=$function')\"><i class='fas fa-plus'></i> {new_item}</button>";}
    return "<div class='ak-card'><div class='ak-empty-c'><i class='$ico'></i><strong class='acx-empty-t'>$t</strong><span>$p</span>$btn</div></div>";
}

function acx_mode_label($mode):string{
    $MODES=array("path"=>array("fas fa-route","{acx_mode_path}"),"ext"=>array("fas fa-file-code","{acx_mode_ext}"),
        "regex"=>array("fas fa-code","{acx_mode_regex}"));
    if(!isset($MODES[$mode])){return htmlspecialchars($mode);}
    return "<span class='acx-mode'><i class='{$MODES[$mode][0]}'></i> {$MODES[$mode][1]}</span>";
}

/**
 * Une regle de cache. Le nombre de cellules depend du type : cinq pour "Cache longer"
 * (avec la duree), quatre pour "Never cache" ; le colspan de la ligne vide suit.
 */
function ac_rule_row($page,$serviceid,$ruletype,$function,array $r,$can):string{
    $id=intval($r["ID"] ?? 0);
    $pattern=(string)($r["pattern"] ?? "");
    $comment=(string)($r["comment"] ?? "");
    $mode=(string)($r["matchmode"] ?? "path");
    $enabled=intval($r["enabled"] ?? 0)==1;
    $q="serviceid=$serviceid&ruletype=$ruletype&function=$function";
    $editJs="Loadjs('$page?rule-js=$id&$q')";
    $m=array("can"=>$can);
    $sw=acx_switch($m,$enabled,"{acx_col_status}","Loadjs('$page?rule-enable-toggle=$id&$q')");

    $h ="<tr".($enabled?"":" class='acx-dim'").">";
    $h.="<td><div class='acx-pat ak-mono'>".htmlspecialchars($pattern)."</div>";
    if($comment!==""){ $h.="<div class='awc-meta'>".htmlspecialchars($comment)."</div>"; }
    $h.="</td>";
    $h.="<td class='awc-nowrap'>".acx_mode_label($mode)."</td>";
    if($ruletype=="include"){
        $min=intval($r["ttl_minutes"] ?? 0);
        $h.="<td class='awc-nowrap' title='$min min'>".($min>0?acx_dur_minutes($min):"&mdash;")."</td>";
    }
    $h.="<td class='awc-nowrap'><span class='acx-cellsw'>$sw</span></td>";
    $h.="<td><div class='ak-acts'>";
    if($can){
        $h.="<button type='button' class='ak-act' aria-label=\"{acx_edit_rule}\" title=\"{acx_edit_rule}\" OnClick=\"$editJs\"><i class='fas fa-pen'></i></button>";
        $h.="<button type='button' class='ak-act ak-danger' aria-label=\"{acx_delete_rule}\" title=\"{acx_delete_rule}\" OnClick=\"Loadjs('$page?rule-delete-js=$id&$q&pattern=".urlencode($pattern)."')\"><i class='fas fa-trash-can'></i></button>";
    }
    $h.="</div></td></tr>";
    return $h;
}

// --- modale ajout / edition d'une regle ----------------------------------------------------------
function ac_rule_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["rule-js"]);
    $serviceid=intval($_GET["serviceid"]);
    $ruletype=(isset($_GET["ruletype"]) && $_GET["ruletype"]==="include")?"include":"exclude";
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $title=$id>0?"{acx_edit_rule}":"{new_item}";
    return $tpl->js_dialog6($title,"$page?rule-popup=$id&serviceid=$serviceid&ruletype=$ruletype&function=$function",640);
}

// Duree d'une regle en minutes -> valeur + unite (minutes, heures, jours).
function acx_ttl_unit($min):array{
    $min=intval($min);
    if($min>0 && $min%1440==0){return array(intval($min/1440),1440);}
    if($min>0 && $min%60==0){return array(intval($min/60),60);}
    return array($min,1);
}

function ac_rule_popup(){
    $tpl=new template_admin();
    $id=intval($_GET["rule-popup"]);
    $serviceid=intval($_GET["serviceid"]);
    $ruletype=(isset($_GET["ruletype"]) && $_GET["ruletype"]==="include")?"include":"exclude";

    $pattern="";$matchmode="path";$comment="";$enabled=1;$ttlmin=60;
    if($id>0){
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/cache-rule/$serviceid/$id");
        $data=json_decode($resp,true);
        if(is_array($data) && !empty($data["Status"]) && isset($data["rule"]) && is_array($data["rule"])){
            $r=$data["rule"];
            $pattern=isset($r["pattern"])?$r["pattern"]:"";
            $matchmode=isset($r["matchmode"])?$r["matchmode"]:"path";
            $comment=isset($r["comment"])?$r["comment"]:"";
            $enabled=intval(isset($r["enabled"])?$r["enabled"]:1);
            $ttlmin=intval(isset($r["ttl_minutes"])?$r["ttl_minutes"]:60);
        }
    }

    $MODES=array("path"=>"{acx_mode_path}","ext"=>"{acx_mode_ext}","regex"=>"{acx_mode_regex}");
    $reloadFn=($ruletype=="include")?"CacheOverReload":"CacheExclReload";
    $explainToken=($ruletype=="include")?"{acx_longer_intro}":"{acx_never_intro}";
    $titleToken=($ruletype=="include")?"{acx_tab_longer}":"{acx_tab_never}";

    $form=array();
    $form[]=$tpl->field_hidden("ac-rule-serviceid",$serviceid);
    $form[]=$tpl->field_hidden("ac-rule-id",$id);
    $form[]=$tpl->field_hidden("ac-rule-type",$ruletype);
    $form[]=$tpl->field_array_hash_simple($MODES,"ac-rule-matchmode","{acx_col_matches}",$matchmode);
    $form[]=$tpl->field_text("ac-rule-pattern","{acx_pattern}",$pattern,true,"{acx_pattern_help}");
    if($ruletype=="include"){
        list($v,$u)=acx_ttl_unit($ttlmin);
        $form[]=$tpl->field_text("ac-rule-ttl","{acx_col_keep}",strval($v),true,"{acx_keep_help}");
        $form[]=$tpl->field_array_hash_simple(array(1=>"{nopt_minutes}",60=>"{nopt_hours}",1440=>"{nopt_days}"),"ac-rule-ttl-unit","{acx_unit}",strval($u));
    }
    $form[]=$tpl->field_text("ac-rule-comment","{comment}",$comment,false,"{acx_comment_help}");
    $form[]=$tpl->field_checkbox("ac-rule-enabled","{enabled}",$enabled);

    $btn=$id>0?"{apply}":"{add}";
    $html=array();
    $html[]=$tpl->div_explain("$titleToken||$explainToken");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,$btn,
        "dialogInstance6.close();$reloadFn();","AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function ac_rest_error($resp){
    $r=json_decode((string)$resp,true);
    if(is_array($r)){
        if(isset($r["Error"]) && $r["Error"]!=""){return (string)$r["Error"];}
        if(isset($r["error"]) && $r["error"]!=""){return (string)$r["error"];}
        if(isset($r["Status"]) && !$r["Status"]){return "{operation_failed}";}
        return "";
    }
    return "{operation_failed}";
}

function ac_rule_save(){
    $tpl=new template_admin();$tpl->CLEAN_POST();
    $serviceid=intval($_POST["ac-rule-serviceid"]);
    if(!acx_can_write()){echo $tpl->post_error("{acx_no_priv}");return;}
    $id=intval($_POST["ac-rule-id"]);
    $ruletype=(isset($_POST["ac-rule-type"]) && $_POST["ac-rule-type"]==="include")?"include":"exclude";
    $mode=strval($_POST["ac-rule-matchmode"] ?? "");
    if(!in_array($mode,array("path","ext","regex"))){echo $tpl->post_error("{operation_failed}");return;}
    $pattern=trim(strval($_POST["ac-rule-pattern"] ?? ""));
    if($pattern=="" || strlen($pattern)>512){echo $tpl->post_error("{acx_err_pattern}");return;}
    $data=array(
        "ID"=>$id,
        "ruletype"=>$ruletype,
        "matchmode"=>$mode,
        "pattern"=>$pattern,
        "enabled"=>intval($_POST["ac-rule-enabled"] ?? 0)==1?1:0,
        "comment"=>trim(strval($_POST["ac-rule-comment"] ?? "")),
    );
    if($ruletype=="include"){
        $unit=intval($_POST["ac-rule-ttl-unit"] ?? 1);
        if(!in_array($unit,array(1,60,1440))){$unit=1;}
        $v=intval($_POST["ac-rule-ttl"] ?? 0);
        $minutes=$v*$unit;
        if($v<1 || $minutes>525600){echo $tpl->post_error("{acx_err_keep}");return;}
        $data["ttl_minutes"]=$minutes;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/cache-rule/$serviceid",$data);
    $err=ac_rest_error($resp);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks("Save cache rule $ruletype $mode \"$pattern\" for reverse-proxy service $serviceid");
}

// --- suppression avec confirmation ---------------------------------------------------------------
function ac_rule_delete_js(){
    $tpl=new template_admin();
    $id=intval($_GET["rule-delete-js"]);
    $serviceid=intval($_GET["serviceid"]);
    $ruletype=(isset($_GET["ruletype"]) && $_GET["ruletype"]==="include")?"include":"exclude";
    $pattern=htmlspecialchars(isset($_GET["pattern"])?$_GET["pattern"]:"");
    $reloadFn=($ruletype=="include")?"CacheOverReload":"CacheExclReload";
    $tpl->js_confirm_delete($pattern,"ac-rule-delete-id","$serviceid:$id:$ruletype","$reloadFn();");
}
function ac_rule_delete(){
    $tpl=new template_admin();$tpl->CLEAN_POST();
    if(!acx_can_write()){echo $tpl->post_error("{acx_no_priv}");return;}
    $parts=explode(":",(string)$_POST["ac-rule-delete-id"]);
    $serviceid=intval(isset($parts[0])?$parts[0]:0);
    $id=intval(isset($parts[1])?$parts[1]:0);
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/cache-rule/$serviceid/$id/delete",array());
    $err=ac_rest_error($resp);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks("Delete cache rule $id of reverse-proxy service $serviceid");
}

// --- bascule enabled/disabled depuis la ligne ---------------------------------------------------
function ac_rule_enable_toggle():bool{
    $tpl=new template_admin();
    $id=intval($_GET["rule-enable-toggle"]);
    $serviceid=intval($_GET["serviceid"]);
    $ruletype=(isset($_GET["ruletype"]) && $_GET["ruletype"]==="include")?"include":"exclude";
    $reloadFn=($ruletype=="include")?"CacheOverReload":"CacheExclReload";
    header("content-type: application/x-javascript");
    if(!acx_can_write()){
        $tpl->js_error("{acx_no_priv}");
        echo "$reloadFn();\n";
        return false;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/cache-rule/$serviceid/$id");
    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"]) || !isset($data["rule"]["enabled"])){
        $tpl->js_error("{operation_failed}");
        echo "$reloadFn();\n";
        return false;
    }
    $new=intval($data["rule"]["enabled"])==1?0:1;
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/cache-rule/$serviceid/$id/enable",array("enabled"=>$new));
    echo "$reloadFn();\n";
    return admin_tracks("Set cache rule $id enabled=$new for reverse-proxy service $serviceid");
}

// =====================================================================================
// Utilitaires partages
// =====================================================================================

function refresh_global_no_close($serviceid):string{
    $page=CurrentPageName();
    $f=array();
    $f[]="if(document.getElementById('flat-$serviceid')){LoadAjaxSilent('flat-$serviceid','$page?flat-config=$serviceid');}";
    $f[]="if(document.getElementById('www-parameters-$serviceid')){LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');}";
    $f[]="if(document.getElementById('nopt-host-$serviceid')){LoadAjaxSilent('nopt-host-$serviceid','fw.nginx.sites.optimize.php?nopt-cards=$serviceid');}";
    $f[]="if(document.getElementById('nopt-buf-host-$serviceid')){LoadAjaxSilent('nopt-buf-host-$serviceid','fw.nginx.sites.optimize.php?nopt-buffers=$serviceid');}";
    return @implode("",$f)."\n";
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}

// Bascule vers l'onglet n (0 = Settings, 2 = Cache longer) depuis un bouton d'un onglet.
function acx_js():string{
    return "<script>window.AcxTab=function(el,n){var c=$(el).closest('.tabs-container');c.find('ul.nav-tabs>li:eq('+n+')>a').trigger('click');};</script>";
}

function acx_style():string{
    if(isset($GLOBALS["ACX_STYLE"])){return "";}
    $GLOBALS["ACX_STYLE"]=true;
    $soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$fill="#047857";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    $c=array();
    $c[]=".acx{display:flex;flex-direction:column;gap:20px;padding:16px 4px 8px}";
    $c[]=".acx-tab{display:inline-flex;align-items:baseline;gap:6px}.acx-tab small{font-size:13px;color:#64748b;font-weight:400}";
    $c[]=".acx-cnt{display:inline-block;min-width:20px;padding:0 6px;border-radius:999px;background:#e3f0f8;color:#3d4947;font-size:13px;font-weight:600;text-align:center}";
    $c[]=".acx-tab-off{display:inline-block;padding:0 6px;border-radius:999px;background:#e3f0f8;color:#64748b;font-size:13px;font-weight:600}";
    $c[]=".acx-hero{display:flex;gap:16px;padding:20px 22px;border:1px solid $line;border-radius:14px;background:$soft}";
    $c[]=".acx-hero-ico{flex:0 0 44px;width:44px;height:44px;border-radius:50%;background:$fill;color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px}";
    $c[]=".acx-hero-b{min-width:0;display:flex;flex-direction:column;gap:8px}";
    $c[]=".acx-hero-t{margin:0;font-size:17px;font-weight:600;color:#0f172a}";
    $c[]=".acx-hero-p{margin:0;font-size:14px;line-height:1.6;color:#334155}";
    $c[]=".acx-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:#fff;border:1px solid $line;font-size:13px;color:#334155}";
    $c[]=".acx-chip i{color:#00504a}";
    $c[]=".acx-row{display:flex;align-items:center;gap:14px;padding:16px 18px;overflow:visible}";
    $c[]=".acx-row-wrap{flex-wrap:wrap}.acx-row-wrap .acx-note{flex:1 1 100%;margin:4px 0 0}";
    $c[]=".acx-row-b{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:4px}";
    $c[]=".acx-row-t{font-size:14px;font-weight:600;color:#0f172a;overflow-wrap:anywhere}.acx-row-t small{font-size:13px;font-weight:400;color:#64748b;margin-left:6px}";
    $c[]=".acx-row-sw,.acx-cellsw{flex:0 0 auto;display:inline-flex;align-items:center;gap:10px}";
    $c[]=".acx .ak-help{font-size:13px;line-height:1.5;color:#64748b}";
    $c[]=".acx-hollow{display:inline-block;width:8px;height:8px;border-radius:50%;border:2px solid currentColor}";
    $c[]=".acx-stack{display:flex;flex-direction:column;gap:12px}";
    $c[]=".acx-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}";
    $c[]="@media (max-width:1000px){.acx-tiles{grid-template-columns:1fr}}";
    $c[]=".acx-tile{display:flex;flex-direction:column;gap:2px;padding:16px 18px}";
    $c[]=".acx-tile-h{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}";
    $c[]=".acx-tile-t{font-size:14px;color:#0f172a}.acx-sub{font-size:13px;color:#64748b}";
    $c[]=".acx-tile-v{font-size:22px;font-weight:600;color:#0f172a;margin:8px 0 10px}";
    $c[]=".acx-tile-help{margin-top:auto;padding-top:10px;border-top:1px solid #f1f5f9;font-size:13px;line-height:1.5;color:#64748b}";
    $c[]=".acx-change{font-size:13px}";
    $c[]=".acx-ro{font-size:13px;flex:0 0 auto}";
    $c[]=".acx-tip,.acx-intro,.acx-status{display:flex;align-items:flex-start;gap:8px;font-size:13px;line-height:1.6;color:#3d4947}";
    $c[]=".acx-tip i,.acx-intro i,.acx-status i{margin-top:3px;color:#64748b}";
    $c[]=".acx-intro{font-size:14px;color:#334155}";
    $c[]=".acx-status{padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;align-items:center}.acx-status span{flex:1 1 auto}";
    $c[]=".acx-tools{display:flex;justify-content:flex-end}";
    $c[]=".acx-pat{font-size:14px;color:#0f172a;overflow-wrap:anywhere}";
    $c[]=".acx-mode{display:inline-flex;align-items:center;gap:6px;color:#334155}.acx-mode i{color:#64748b}";
    $c[]="tr.acx-dim .acx-pat,tr.acx-dim .acx-mode{color:#94a3b8}";
    $c[]=".acx-empty-t{font-size:15px;color:#0f172a}";
    $c[]="@media (prefers-reduced-motion:reduce){.acx *{transition:none!important;animation:none!important}}";
    return "<style>".@implode("\n",$c)."</style>";
}
