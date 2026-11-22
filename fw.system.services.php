<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");


include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main-page"])){main_page();exit;}
if(isset($_GET["table"])){features();exit;}
if(isset($_GET["job-js"])){svcx_job_js();exit;}
if(isset($_GET["job-popup"])){svcx_job_popup();exit;}
if(isset($_GET["job-status"])){svcx_job_status();exit;}
if(isset($_POST["feature-action"])){svcx_action_post();exit;}
if(isset($_POST["lang"])){lang();exit;}
// Pentest 2026-11-04 M-01 : seuls les boutons signes par la page sont renvoyes.
if(isset($_GET["function"])){$_GET["function"]=preg_replace('/[^A-Za-z0-9_]/','',strval($_GET["function"]));}
page();


function main_page(){
    $page=CurrentPageName();
    $html="<div id='main-service-page'></div>
	<script>LoadAjax('main-service-page','$page');</script>";
    $tpl=new template_admin(null,$html);
    echo $tpl->build_firewall();
}

function page(){
    $page=CurrentPageName();
    $network_services=1;
    $members_services=1;
    $network_monitoring=1;
    $proxy_features=1;
    $statistics=1;
    $network_security=1;
    $data_transfer=1;
    $messaging_services=1;
    $expan=0;
    if(isset($_COOKIE["network_services"])){$network_services=$_COOKIE["network_services"];}
    if(isset($_COOKIE["members_services"])){$members_services=$_COOKIE["members_services"];}
    if(isset($_COOKIE["network_monitoring"])){$network_monitoring=$_COOKIE["network_monitoring"];}
    if(isset($_COOKIE["proxy_features"])){$proxy_features=$_COOKIE["proxy_features"];}
    if(isset($_COOKIE["statistics"])){$statistics=$_COOKIE["statistics"];}
    if(isset($_COOKIE["network_security"])){$network_security=$_COOKIE["network_security"];}
    if(isset($_COOKIE["data_transfer"])){$data_transfer=$_COOKIE["data_transfer"];}
    if(isset($_COOKIE["messaging_services"])){$messaging_services=$_COOKIE["messaging_services"];}
    if(isset($_COOKIE["expand"])){$expan=$_COOKIE["expand"];}
    $tpl=new template_admin();
    $html=$tpl->page_header("{activate_disable_features}",
        ico_cd,"{activate_disable_features_section}",
        "$page?table=yes&network_services=$network_services&members_services=$members_services&network_monitoring=$network_monitoring&proxy_features=$proxy_features&statistics=$statistics&network_security=$network_security&data_transfer=$data_transfer&messaging_services=$messaging_services&expand=$expan",
        "features","progress-firehol-restart",false,"table-loader"
    );


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function features():bool{
    $tpl=new template_admin();
    if(!isset($_GET["onlyactive"])){$_GET["onlyactive"]="no";}
    // Pentest 2026-11-04 : onlyactive etait recopie sans encodage dans la page (XSS).
    $_GET["onlyactive"]=$_GET["onlyactive"]=="yes"?"yes":"no";
    // "Only Active" est desormais un filtre du navigateur (svcx_js).

    // Le catalogue (sections, conditions de rendu, etats, motifs) est calcule par
    // articarest (module SystemFeatures, GET /system/features) : une seule source pour
    // cette page et pour Artica Meta. Le privilege de chaque fonctionnalite est juge ici.
    $c=svcx_catalog();
    if($c["missing"]){
        echo $tpl->div_error("{svcx_catalog_unavailable}||{svcx_route_missing}");
        return true;
    }
    if(!$c["ok"]){
        echo $tpl->div_error("{svcx_catalog_unavailable}||".htmlspecialchars($c["error"],ENT_QUOTES));
        return true;
    }
    if(count($c["features"])==0 && $c["refreshing"]){
        // Catalogue jamais calcule ou en cours : jamais "aucune fonctionnalite".
        $h="<div class='ak'><div class='ak-card ak-empty-c' id='svcx-computing'><i class='fas fa-spinner ak-spin' aria-hidden='true'></i>"
            ."<span>{svcx_computing}</span></div></div>";
        $js="<script>setTimeout(function(){if(document.getElementById('svcx-computing')&&typeof LoadAjaxSilent==='function'){LoadAjaxSilent('table-loader','fw.system.services.php?table=yes');}},5000);"
            ."if(typeof NoSpinner==='function'){NoSpinner();}</script>";
        echo $tpl->_ENGINE_parse_body($h).$js;
        return true;
    }
    echo svcx_render(svcx_model($c["features"]));
    return true;
}

// -- Catalogue Go (articarest SystemFeatures) --
// GET /system/features : dernier catalogue evalue par le daemon local, servi sans attendre.
function svcx_catalog():array{
    $out=array("ok"=>false,"missing"=>false,"error"=>"","features"=>array(),"refreshing"=>false);
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/features");
    $j=json_decode($raw,true);
    if(!is_array($j)){$out["error"]=substr(trim(strip_tags($raw)),0,300);return $out;}
    if(empty($j["Status"])){
        $err=isset($j["Error"])?(string)$j["Error"]:"";
        // REST_API rend JsonFailed + "404 Not found" quand la route manque (articarest ancien).
        if(!empty($j["JsonFailed"]) && strpos($err,"404")!==false){$out["missing"]=true;}
        $out["error"]=substr(trim(strip_tags(str_replace("<br>"," ",$err))),0,300);
        return $out;
    }
    $out["ok"]=true;
    $out["refreshing"]=!empty($j["refreshing"]);
    if(isset($j["features"]) && is_array($j["features"])){$out["features"]=$j["features"];}
    return $out;
}

// Une fonctionnalite du catalogue par sa cle ; null si absente.
function svcx_find(array $features,string $key){
    foreach($features as $it){
        if(is_array($it) && isset($it["key"]) && (string)$it["key"]===$key){return $it;}
    }
    return null;
}

// Privilege d'une fonctionnalite, porte de features::isSecurity() (class.features.inc) :
// vide = tout administrateur de la console ; privilege inconnu = refuse.
function svcx_allowed($privileges):bool{
    $privileges=(string)$privileges;
    if($privileges===""){return true;}
    $known=array("AsSystemAdministrator","AsSquidAdministrator","AsDansGuardianAdministrator","AsDnsAdministrator",
        "AsPostfixAdministrator","AsFirewallManager","AsSystemWebMaster","AsMailBoxAdministrator");
    if(!in_array($privileges,$known,true)){return false;}
    if(!isset($GLOBALS["SVCX_USERS"])){$GLOBALS["SVCX_USERS"]=new usersMenus();}
    return (bool)$GLOBALS["SVCX_USERS"]->$privileges;
}

// Famille du motif de blocage, pour l'icone de l'etiquette.
function svcx_reason_kind(string $reason):string{
    if(strpos($reason,"{not_installed}")!==false){return "absent";}
    if(strpos($reason,"{memory}")!==false){return "memory";}
    if(strpos($reason,"{require_installed}")!==false || strpos($reason,"{require_enabled}")!==false){return "link";}
    if(stripos($reason,"License")!==false){return "license";}
    if(strpos($reason,"{wrong_privileges}")!==false){return "privileges";}
    return "warn";
}

// URL d'un lien (wiki, plus d'infos) : http(s) sans caractere qui sortirait de la chaine JS.
function svcx_url($u):string{
    $u=trim((string)$u);
    // Meme jeu de caracteres strict que nafeat_url (console Meta) : rien qui sorte d'un attribut ou d'une chaine JS.
    if(strlen($u)>1024 || !preg_match('#^https?://[A-Za-z0-9._~:/?=%+-]+$#i',$u)){return "";}
    return $u;
}

function svcx_plain(template_admin $tpl,string $text):string{
    $t=$tpl->_ENGINE_parse_body($text);
    $t=preg_replace('#<br\s*/?>#i'," ",$t);
    return trim(html_entity_decode(strip_tags($t),ENT_QUOTES,"UTF-8"));
}

// Action d'une fonctionnalite : installable = POST /system/features/action et suivi du job ;
// sinon le flux framework d'origine (fw.progress.modal.lock.php) avec local_action.
function svcx_item_js(template_admin $tpl,array $it,string $state):string{
    $key=(string)$it["key"];
    $on=$state!="enabled";
    $ARRAY=array();
    $ARRAY["TITLE"]=htmlspecialchars((string)$it["title"],ENT_QUOTES).($on?":{enable_feature}":":{disable_feature}");
    $confirmText=$tpl->_ENGINE_parse_body($ARRAY["TITLE"]);
    // Avertissement du catalogue (Item.confirm, un jeton de langue) : montre dans la boite de
    // confirmation avant une installation. Valeur du noeud : seul un {jeton} simple est accepte.
    $warn=trim((string)($it["confirm"] ?? ""));
    if($on && preg_match('/^\{[A-Za-z0-9_]{1,64}\}$/',$warn)){
        $w=preg_replace('#<br\s*/?>#i'," ",$tpl->_ENGINE_parse_body($warn));
        // js_confirm_execute termine deja la phrase par " ?".
        $confirmText.="\n\n".htmlspecialchars(rtrim(trim(html_entity_decode(strip_tags($w),ENT_QUOTES,"UTF-8"))," ?"),ENT_QUOTES);
    }
    if(!empty($it["installable"])){
        $act=$on?"on":"off";
        $run="SvcxRun('$key','$act')";
        return "Loadjs('fw.progress.php?".fw_progress_confirm_args($confirmText,$run)."')";
    }
    $cmd=(string)($it["local_action"] ?? "");
    if($cmd===""){return "";}
    // Comme class.features.inc : toute la console est rechargee (menus de gauche compris,
    // un service installe y ajoute ses entrees).
    $after="location.reload(true);";
    if(!empty($it["reboot"])){$after.="Loadjs('fw.system.restart.php');";}
    $ARRAY["CMD"]=$cmd;
    $ARRAY["PROGRESS_FILE"]=(string)($it["progress_file"] ?? "");
    $ARRAY["LOG_FILE"]=(string)($it["log_file"] ?? "");
    $ARRAY["AFTER"]=$after;
    $prgress=base64_encode(serialize($ARRAY));
    $js="Loadjs('fw.progress.modal.lock.php?".fw_progress_args_b64($prgress)."&mainid=progress-$key-restart')";
    return "Loadjs('fw.progress.php?".fw_progress_confirm_args($confirmText,$js)."')";
}

// Un Item du catalogue Go en donnees de la page ; null = ignore (forme inattendue).
function svcx_item(template_admin $tpl,$it){
    if(!is_array($it)){return null;}
    $key=(string)($it["key"] ?? "");
    $state=(string)($it["state"] ?? "");
    if(!preg_match('/^[A-Za-z0-9_]{1,64}$/',$key)){return null;}
    if($state!="enabled" && $state!="available" && $state!="blocked"){return null;}
    $reason=(string)($it["reason"] ?? "");
    if(!svcx_allowed($it["security"] ?? "")){$state="blocked";$reason="{wrong_privileges}";}
    $m=array(
        "state"   => $state,
        "title"   => htmlspecialchars((string)($it["title"] ?? ""),ENT_QUOTES),
        // <br> est la seule balise du catalogue Go (APP_DSC) ; plainText() la retire deja cote Go.
        "explain" => str_replace("&lt;br&gt;","<br>",htmlspecialchars((string)($it["explain"] ?? ""),ENT_QUOTES)),
        "wiki"    => svcx_url($it["wiki"] ?? ""),
        "more"    => svcx_url($it["more"] ?? ""),
        "reboot"  => !empty($it["reboot"]),
        "reason"  => htmlspecialchars($reason,ENT_QUOTES),
        "kind"    => $state=="blocked"?svcx_reason_kind($reason):"",
        "js"      => "",
    );
    if($state!="blocked"){
        $m["js"]=svcx_item_js($tpl,$it,$state);
        if($m["js"]===""){$m["state"]="blocked";$m["reason"]="{svcx_unknown_feature}";$m["kind"]="warn";}
    }
    return $m;
}

function svcx_model(array $features):array{
    $tpl=new template_admin();
    $m=array("sections"=>array(),"n"=>array("all"=>0,"enabled"=>0,"available"=>0,"blocked"=>0));
    $order=array("enabled"=>0,"available"=>1,"blocked"=>2);
    $bySection=array();
    foreach($features as $raw){
        $it=svcx_item($tpl,$raw);
        if($it===null){continue;}
        $title=(string)($raw["section"] ?? "");
        if(!isset($bySection[$title])){
            $i=count($bySection);
            $bySection[$title]=array("id"=>"svcx-sec-$i","title"=>htmlspecialchars($title,ENT_QUOTES),"items"=>array(),"n"=>array("enabled"=>0,"available"=>0,"blocked"=>0));
        }
        $bySection[$title]["items"][]=$it;
        $bySection[$title]["n"][$it["state"]]++;
        $m["n"][$it["state"]]++;
        $m["n"]["all"]++;
    }
    foreach($bySection as $sec){
        // Ce qu'on peut activer d'abord, les lignes bloquees ensuite (tri stable).
        $idx=array();
        foreach($sec["items"] as $k=>$it){$idx[$k]=$order[$it["state"]]*10000+$k;}
        array_multisort($idx,$sec["items"]);
        $m["sections"][]=$sec;
    }
    return $m;
}

function svcx_json_out(array $a){
    header("content-type: application/json");
    echo json_encode($a,JSON_INVALID_UTF8_SUBSTITUTE);
}

// POST feature-action=<cle>&do=on|off : action d'une fonctionnalite installable par articarest.
// Reponse JSON lue par SvcxRun : {ok, job} | {ok, already, message} | {ok:false, title, error}.
function svcx_action_post(){
    $tpl=new template_admin();
    $title=svcx_plain($tpl,"{svcx_job_refused}");
    $key=(string)$_POST["feature-action"];
    $do=(string)($_POST["do"] ?? "");
    if(!preg_match('/^[A-Za-z0-9_]{1,64}$/',$key) || ($do!=="on" && $do!=="off")){
        svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>svcx_plain($tpl,"{svcx_unknown_feature}")));return;
    }
    $c=svcx_catalog();
    if($c["missing"]){svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>svcx_plain($tpl,"{svcx_route_missing}")));return;}
    if(!$c["ok"]){svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>$c["error"]));return;}
    $it=svcx_find($c["features"],$key);
    if($it===null || empty($it["installable"])){
        svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>svcx_plain($tpl,"{svcx_unknown_feature}")));return;
    }
    if(!svcx_allowed($it["security"] ?? "")){
        svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>svcx_plain($tpl,"{wrong_privileges}")));return;
    }
    admin_tracks("Feature $key: action $do requested");
    // X-Artica-Actor (compte de la console) est pose par REST_API_POST_JSON (audit_headers).
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/features/action",array("key"=>$key,"action"=>$do));
    $r=json_decode($raw,true);
    if(!is_array($r)){
        // Corps non JSON : "Not Found" du routeur = route absente (articarest ancien).
        $err=trim(strip_tags($raw));
        if($err=="" || stripos($err,"not found")!==false){$err=svcx_plain($tpl,"{svcx_route_missing}");}
        svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>substr($err,0,300)));return;
    }
    if(!empty($r["error"])){svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>substr((string)$r["error"],0,300)));return;}
    if(empty($r["Status"])){
        $err=isset($r["Error"])?(string)$r["Error"]:svcx_plain($tpl,"{svcx_job_refused}");
        svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>substr($err,0,300)));return;
    }
    if(($r["result"] ?? "")==="already"){
        svcx_json_out(array("ok"=>true,"already"=>true,"message"=>svcx_plain($tpl,"{svcx_job_already}")));return;
    }
    $job=(string)($r["job"] ?? "");
    if(!preg_match('/^[0-9a-f]{1,32}$/',$job)){
        svcx_json_out(array("ok"=>false,"title"=>$title,"error"=>svcx_plain($tpl,"{svcx_job_none}")));return;
    }
    svcx_json_out(array("ok"=>true,"job"=>$job));
}

function svcx_job_id():string{
    $id=(string)($_GET["job-js"] ?? ($_GET["job-popup"] ?? ($_GET["job-status"] ?? "")));
    return preg_match('/^[0-9a-f]{1,32}$/',$id)?$id:"";
}

function svcx_job_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=svcx_job_id();
    if($id===""){return false;}
    return $tpl->js_dialog2("{svcx_job_title}","$page?job-popup=$id",650);
}

// Corps du dialogue : suivi de GET /system/features/jobs toutes les 2 s jusqu'a la fin du job.
function svcx_job_popup(){
    $tpl=new template_admin();
    $id=svcx_job_id();
    if($id===""){echo $tpl->div_error("{svcx_job_none}");return;}
    $h="<div class='ak' id='svcx-job-$id'><div class='ak-banner ak-info'><i class='fas fa-spinner ak-spin' aria-hidden='true'></i>"
        ."<span class='ak-banner-m'>{svcx_job_running}</span></div></div>";
    $js="<script>(function(){var id='$id';"
        ."function tick(){var el=document.getElementById('svcx-job-'+id);if(!el){return;}"
        ."$.get('fw.system.services.php?job-status='+id,function(h){el=document.getElementById('svcx-job-'+id);if(!el){return;}"
        ."el.innerHTML=h;if(el.querySelector('[data-done]')){if(typeof SvcxReload==='function'){SvcxReload();}"
        // Job termine : la console entiere est rechargee a la fermeture du dialogue (menus de gauche).
        ."if(typeof dialogInstance2==='object'&&dialogInstance2&&typeof dialogInstance2.getModal==='function'){dialogInstance2.getModal().one('hidden.bs.modal',function(){location.reload(true);});}"
        ."return;}"
        ."setTimeout(tick,2000);},'html').fail(function(){setTimeout(tick,4000);});}"
        ."tick();})();</script>";
    echo $tpl->_ENGINE_parse_body($h).$js;
}

function svcx_job_status(){
    $tpl=new template_admin();
    $id=svcx_job_id();
    $done="<span data-done='1' hidden></span>";
    if($id===""){echo $tpl->_ENGINE_parse_body("<div class='ak-banner ak-err'><span class='ak-banner-m'>{svcx_job_none}</span></div>$done");return;}
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/features/jobs");
    $j=json_decode($raw,true);
    if(!is_array($j) || empty($j["Status"])){
        $err=is_array($j)&&isset($j["Error"])?(string)$j["Error"]:trim(strip_tags($raw));
        $err=htmlspecialchars(substr(trim(strip_tags(str_replace("<br>"," ",$err))),0,300),ENT_QUOTES);
        // Lecture en echec (daemon qui redemarre...) : le suivi continue, pas de marqueur de fin.
        echo $tpl->_ENGINE_parse_body("<div class='ak-banner ak-err'><span class='ak-banner-m'>{svcx_catalog_unavailable}<br>$err</span></div>");
        return;
    }
    $job=isset($j["job"]) && is_array($j["job"])?$j["job"]:null;
    if($job===null){echo $tpl->_ENGINE_parse_body("<div class='ak-banner ak-off'><span class='ak-banner-m'>{svcx_job_none}</span></div>$done");return;}
    if((string)($job["id"] ?? "")!==$id){
        echo $tpl->_ENGINE_parse_body("<div class='ak-banner ak-warn'><span class='ak-banner-m'>{svcx_job_replaced}</span></div>$done");
        return;
    }
    $key=(string)($job["key"] ?? "");
    $name=htmlspecialchars($key,ENT_QUOTES);
    $c=svcx_catalog();
    if($c["ok"]){
        $it=svcx_find($c["features"],$key);
        if($it!==null){$name=htmlspecialchars((string)($it["title"] ?? $key),ENT_QUOTES);}
    }
    $state=(string)($job["state"] ?? "");
    $pc=max(0,min(100,intval($job["progress"] ?? 0)));
    $msg=htmlspecialchars((string)($job["message"] ?? ""),ENT_QUOTES);
    $h=array();
    $h[]="<div class='ak-sec-t' style='margin-bottom:10px'>$name</div>";
    if($state=="running"){
        $h[]="<div class='ak-banner ak-info'><i class='fas fa-spinner ak-spin' aria-hidden='true'></i><span class='ak-banner-m'>{svcx_job_running} ($pc%)</span></div>";
        $h[]="<div class='ak-bar' style='margin-top:10px'><span style='width:$pc%'></span></div>";
        if($msg!==""){$h[]="<div class='ak-note' style='margin-top:6px'>$msg</div>";}
        echo $tpl->_ENGINE_parse_body(implode("",$h));
        return;
    }
    $map=array("ok"=>array("ak-ok","fa-circle-check","{svcx_job_ok}"),
        "already"=>array("ak-ok","fa-circle-check","{svcx_job_already}"),
        "failed"=>array("ak-err","fa-triangle-exclamation","{svcx_job_failed}"),
        "refused"=>array("ak-err","fa-ban","{svcx_job_refused}"));
    $v=isset($map[$state])?$map[$state]:array("ak-err","fa-triangle-exclamation","{svcx_job_failed}");
    $h[]="<div class='ak-banner {$v[0]}'><i class='fas {$v[1]}' aria-hidden='true'></i><span class='ak-banner-m'>{$v[2]}</span></div>";
    if($msg!==""){$h[]="<div class='ak-note' style='margin-top:6px'>$msg</div>";}
    if($state=="ok" && !empty($job["reboot_required"])){
        // Jamais de redemarrage automatique : l'administrateur decide.
        $h[]="<div class='ak-banner ak-warn' style='margin-top:10px'><i class='fas fa-power-off' aria-hidden='true'></i>"
            ."<span class='ak-banner-m'>{svcx_reboot_required}</span>"
            ."<button type='button' class='ak-btn ak-btn-sm' OnClick=\"Loadjs('fw.system.restart.php')\">{svcx_restart_server}</button></div>";
    }
    $h[]=$done;
    echo $tpl->_ENGINE_parse_body(implode("",$h));
}

function svcx_render(array $m):string{
    $tpl=new template_admin();
    $h=array();
    $h[]="<div class='ak svcx' id='svcx'>";
    $h[]=svcx_tiles($m);
    $h[]=svcx_toolbar($m);
    $h[]="<div id='features-templates'>";
    foreach($m["sections"] as $sec){$h[]=svcx_section($sec);}
    $h[]=svcx_empty();
    $h[]="</div></div>";
    return svcx_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).svcx_js();
}

function svcx_tile(string $f,string $ico,string $label,int $n,string $sub,bool $on):string{
    $p=$on?"true":"false";
    return "<button type='button' class='ak-fig svcx-tile' data-f='$f' aria-pressed='$p' OnClick=\"SvcxState('$f')\">"
        ."<span class='ak-fig-txt'><span class='ak-fig-l'>$label</span><span class='ak-fig-v'>$n</span><span class='ak-fig-s'>$sub</span></span>"
        ."<span class='ak-ico ak-ico-sq'><i class='fas $ico' aria-hidden='true'></i></span></button>";
}

function svcx_tiles(array $m):string{
    $n=$m["n"];
    return "<div class='ak-grid-4 svcx-tiles'>"
        .svcx_tile("all","fa-list-check","{svcx_all_features}",$n["all"],"{svcx_all_sub}",true)
        ."<span class='ak-ok svcx-tw'>".svcx_tile("enabled","fa-circle-check","{svcx_enabled}",$n["enabled"],"{svcx_enabled_sub}",false)."</span>"
        ."<span class='ak-accent svcx-tw'>".svcx_tile("available","fa-download","{svcx_available}",$n["available"],"{svcx_available_sub}",false)."</span>"
        ."<span class='ak-off svcx-tw'>".svcx_tile("blocked","fa-ban","{svcx_blocked}",$n["blocked"],"{svcx_blocked_sub}",false)."</span>"
        ."</div>";
}

function svcx_switch(string $id,string $label,bool $on,string $js):string{
    $a=$on?"true":"false";
    return "<span class='svcx-swl'><button type='button' role='switch' class='ak-sw' id='$id' aria-checked='$a' aria-labelledby='$id-l' OnClick=\"$js\"></button>"
        ."<span id='$id-l'>$label</span></span>";
}

function svcx_toolbar(array $m):string{
    $pills=array();
    $pills[]="<button type='button' class='ak-seg-b on' data-s='' OnClick=\"SvcxSection(this,'')\">{svcx_all_sections}</button>";
    foreach($m["sections"] as $sec){
        $n=count($sec["items"]);
        $pills[]="<button type='button' class='ak-seg-b' data-s='{$sec["id"]}' OnClick=\"SvcxSection(this,'{$sec["id"]}')\">{$sec["title"]} <span class='ak-count'>$n</span></button>";
    }
    return "<div class='ak-tools svcx-tools'>"
        ."<div class='svcx-tools-l'><label class='ak-search svcx-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='svcx-q' placeholder='{svcx_search}' aria-label='{svcx_search}' oninput='SvcxApply()'></label>"
        ."<span class='ak-grow'></span>"
        .svcx_switch("svcx-desc","{svcx_show_desc}",true,"SvcxToggle(this)")
        .svcx_switch("svcx-only","{OnlyActive}",false,"SvcxToggle(this)")
        ."<button type='button' class='ak-btn' OnClick=\"LoadAjax('features-templates','fw.system.wizards.php?popup=yes')\"><i class='fas fa-hat-wizard' aria-hidden='true'></i> {wizards}</button>"
        ."</div><div class='ak-seg svcx-pills' role='group' aria-label='{svcx_sections}'>".implode("",$pills)."</div></div>";
}

function svcx_section(array $sec):string{
    $n=$sec["n"];
    $cnt=str_replace(array("%e","%a","%b"),array($n["enabled"],$n["available"],$n["blocked"]),"<span>%e {svcx_n_enabled}</span> · <span>%a {svcx_n_available}</span> · <span>%b {svcx_n_blocked}</span>");
    $rows=array();
    foreach($sec["items"] as $it){$rows[]=svcx_row($it);}
    return "<section class='ak-card svcx-sec' id='{$sec["id"]}' data-s='{$sec["id"]}' style='margin-top:3px'>"
        ."<button type='button' class='svcx-sec-h' aria-expanded='true' aria-controls='{$sec["id"]}-b' OnClick=\"SvcxFold(this)\">"
        ."<i class='fas fa-chevron-down ak-chev' aria-hidden='true'></i><span class='svcx-sec-t'>{$sec["title"]}</span>"
        ."<span class='ak-grow'></span><span class='ak-muted svcx-cnt'>$cnt</span></button>"
        ."<div class='svcx-sec-b' id='{$sec["id"]}-b'>".implode("",$rows)."</div></section>";
}

function svcx_state(string $state):string{
    if($state=="enabled"){return "<span class='ak-pill ak-ok svcx-st'><i class='fas fa-circle-check' aria-hidden='true'></i> {svcx_enabled}</span>";}
    if($state=="available"){return "<span class='ak-pill ak-accent svcx-st'><i class='fas fa-download' aria-hidden='true'></i> {svcx_available}</span>";}
    return "<span class='ak-pill ak-off svcx-st'><i class='fas fa-ban' aria-hidden='true'></i> {svcx_blocked}</span>";
}

function svcx_reason(array $it):string{
    $ico=array("absent"=>"fa-box-open","memory"=>"fa-memory","link"=>"fa-link","license"=>"fa-certificate","privileges"=>"fa-user-lock","warn"=>"fa-triangle-exclamation");
    $i=isset($ico[$it["kind"]])?$ico[$it["kind"]]:"fa-lock";
    return "<span class='ak-tag ak-off svcx-reason'><i class='fas $i' aria-hidden='true'></i> {$it["reason"]}</span>";
}

function svcx_action(array $it):string{
    if($it["state"]=="blocked"){return svcx_reason($it);}
    $js=$it["js"];
    if($it["state"]=="enabled"){
        return "<button type='button' class='ak-btn ak-btn-sm svcx-un' OnClick=\"$js\"><i class='fas fa-trash-can' aria-hidden='true'></i> {svcx_uninstall}</button>";
    }
    return "<button type='button' class='ak-btn ak-btn-sm ak-btn-primary' OnClick=\"$js\"><i class='fas fa-download' aria-hidden='true'></i> {install2}</button>";
}

function svcx_links(array $it):string{
    $h="";
    // URL deja validee par svcx_url() (jeu de caracteres strict) et echappee dans l'attribut.
    if($it["wiki"]!==""){
        $u=htmlspecialchars($it["wiki"],ENT_QUOTES);
        $h.="<button type='button' class='ak-link svcx-lk' OnClick=\"s_PopUpFull('$u',1024,768,'Support');\"><i class='fas fa-book-open' aria-hidden='true'></i> Wiki</button>";
    }
    if($it["more"]!==""){
        $u=htmlspecialchars($it["more"],ENT_QUOTES);
        $h.="<button type='button' class='ak-link svcx-lk' OnClick=\"s_PopUpFull('$u','1024','900');\"><i class='fas fa-circle-info' aria-hidden='true'></i> {more_infos}</button>";
    }
    return $h;
}

function svcx_row(array $it):string{
    $reboot=$it["reboot"]?" <span class='ak-tag ak-warn'><i class='fas fa-power-off' aria-hidden='true'></i> {svcx_reboot}</span>":"";
    $desc="";$more="";
    if($it["explain"]!==""){
        $desc="<div class='svcx-desc'>{$it["explain"]}</div>";
        // Une ligne par defaut ; le chevron deplie le texte complet de CETTE fonctionnalite.
        $more="<button type='button' class='ak-act svcx-more' aria-expanded='false' aria-label='{svcx_details}' title='{svcx_details}' OnClick='SvcxMore(this)'><i class='fas fa-chevron-right' aria-hidden='true'></i></button>";
    }
    return "<div class='svcx-row svcx-{$it["state"]}' data-st='{$it["state"]}'>"
        .svcx_state($it["state"])
        ."<div class='svcx-main'><div class='svcx-title'>{$it["title"]}$reboot$more</div>$desc</div>"
        ."<div class='svcx-side'>".svcx_links($it).svcx_action($it)."</div></div>";
}

function svcx_empty():string{
    return "<div class='ak-card ak-empty-c' id='svcx-empty' hidden><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<span>{svcx_no_match}</span><button type='button' class='ak-btn ak-btn-ghost' OnClick='SvcxReset()'><i class='fas fa-filter-circle-xmark' aria-hidden='true'></i> {svcx_clear}</button></div>";
}

// Regles propres a la page ; le reste vient du kit ak- (angular.css.php).
function svcx_style():string{
    $c=array();
    $c[]=".svcx{display:flex;flex-direction:column;gap:16px;padding:4px 2px 16px}";
    $c[]=".svcx-tw{display:block;min-width:0}.svcx-tile{width:100%;text-align:left;cursor:pointer}.svcx-tile:hover{border-color:#bcc9c6}";
    $c[]=".svcx-tile[aria-pressed=true]{border-color:var(--ak-fill);box-shadow:inset 0 0 0 1px var(--ak-fill)}.svcx-tile:focus-visible{outline:2px solid var(--ak-fill);outline-offset:2px}";
    $c[]=".svcx-tools{flex-direction:column;align-items:stretch;margin:0}.svcx-tools-l{display:flex;align-items:center;gap:16px;flex-wrap:wrap}.svcx-search{width:360px}";
    $c[]=".svcx-swl{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#334155}";
    $c[]=".svcx-pills{flex-wrap:wrap;align-self:flex-start}";
    $c[]=".svcx-sec-h{display:flex;align-items:center;gap:10px;width:100%;min-height:48px;padding:10px 18px;border:0;border-bottom:1px solid #e2e8f0;background:#fff;text-align:left;cursor:pointer}";
    $c[]=".svcx-sec-h:hover{background:#f8fafc}.svcx-sec-h:focus-visible{outline:2px solid var(--ak-fill);outline-offset:-2px}";
    $c[]=".svcx-sec-h[aria-expanded=false]{border-bottom:0}.svcx-sec-h[aria-expanded=false] .ak-chev{transform:rotate(-90deg)}.svcx-sec-h .ak-chev{transition:transform .15s}";
    $c[]=".svcx-sec-t{font-size:15px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}.svcx-cnt{font-size:13px;white-space:nowrap}";
    $c[]=".svcx-row{display:flex;align-items:center;gap:16px;min-height:60px;padding:10px 18px;border-top:1px solid #f1f5f9}.svcx-row:first-child{border-top:0}";
    $c[]=".svcx-enabled{background:rgba(16,185,129,.05)}.svcx-st{flex:0 0 112px;justify-content:center}";
    $c[]=".svcx-main{flex:1 1 auto;min-width:0}.svcx-title{font-size:15px;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:8px;flex-wrap:wrap}";
    $c[]=".svcx-blocked .svcx-title{color:#475569}";
    $c[]=".svcx-desc{margin-top:2px;font-size:13px;color:#64748b;overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical}";
    $c[]=".svcx-open .svcx-desc{display:block}.svcx-nodesc .svcx-desc,.svcx-nodesc .svcx-more{display:none}";
    $c[]=".svcx-more{width:24px;height:24px;font-size:12px}.svcx-more svg,.svcx-more i{transition:transform .15s}.svcx-open .svcx-more svg,.svcx-open .svcx-more i{transform:rotate(90deg)}";
    $c[]=".svcx-side{flex:0 0 auto;display:flex;align-items:center;gap:10px;justify-content:flex-end;max-width:46%}";
    $c[]=".svcx-un{border-color:#865300;color:#865300}.svcx-un:hover{border-color:#865300;color:#865300;background:#ffddb9}";
    $c[]=".svcx-reason{white-space:normal;text-align:left;max-width:300px}.svcx-lk{font-size:13px}";
    $c[]=".svcx [data-hide]{display:none!important}";
    $c[]="@media (prefers-reduced-motion:reduce){.svcx-sec-h .ak-chev,.svcx-more svg,.svcx-more i{transition:none}}";
    return "<style>".implode("\n",$c)."</style>";
}

function svcx_js():string{
    return <<<'JS'
<script>
(function(){
  var S={st:'all',sec:'',q:'',only:false};
  function all(sel,r){return Array.prototype.slice.call((r||document).querySelectorAll(sel));}
  function store(k,v){try{localStorage.setItem('svcx-'+k,v);}catch(e){}}
  function read(k){try{return localStorage.getItem('svcx-'+k);}catch(e){return null;}}
  window.SvcxApply=function(){
    var q=document.getElementById('svcx-q');S.q=q?q.value.trim().toLowerCase():'';
    var shown=0;
    all('#svcx .svcx-sec').forEach(function(sec){
      var n=0;
      all('.svcx-row',sec).forEach(function(r){
        var st=r.getAttribute('data-st'),ok=true;
        if(S.st!=='all'&&st!==S.st){ok=false;}
        if(S.only&&st!=='enabled'){ok=false;}
        if(ok&&S.q!==''&&r.textContent.toLowerCase().indexOf(S.q)<0){ok=false;}
        if(ok){r.removeAttribute('data-hide');n++;}else{r.setAttribute('data-hide','1');}
      });
      var vis=n>0&&(S.sec===''||sec.getAttribute('data-s')===S.sec);
      if(vis){sec.removeAttribute('data-hide');shown+=n;}else{sec.setAttribute('data-hide','1');}
    });
    var e=document.getElementById('svcx-empty');if(e){e.hidden=shown>0;}
  };
  window.SvcxState=function(f){
    S.st=f;
    all('#svcx .svcx-tile').forEach(function(b){b.setAttribute('aria-pressed',b.getAttribute('data-f')===f?'true':'false');});
    SvcxApply();
  };
  window.SvcxSection=function(btn,id){
    S.sec=id;
    all('#svcx .svcx-pills .ak-seg-b').forEach(function(b){b.classList.toggle('on',b===btn);});
    SvcxApply();
  };
  window.SvcxToggle=function(sw){
    var on=sw.getAttribute('aria-checked')!=='true';sw.setAttribute('aria-checked',on?'true':'false');
    if(sw.id==='svcx-desc'){document.getElementById('svcx').classList.toggle('svcx-nodesc',!on);store('desc',on?'1':'0');}
    if(sw.id==='svcx-only'){S.only=on;store('only',on?'1':'0');SvcxApply();}
  };
  window.SvcxMore=function(btn){
    var r=btn.closest('.svcx-row');if(!r){return;}
    var open=!r.classList.contains('svcx-open');r.classList.toggle('svcx-open',open);
    btn.setAttribute('aria-expanded',open?'true':'false');
  };
  window.SvcxFold=function(h){
    var open=h.getAttribute('aria-expanded')!=='false';h.setAttribute('aria-expanded',open?'false':'true');
    var b=document.getElementById(h.getAttribute('aria-controls'));if(b){b.hidden=open;}
  };
  window.SvcxReset=function(){
    var q=document.getElementById('svcx-q');if(q){q.value='';}
    var a=document.querySelector('#svcx .svcx-pills .ak-seg-b');S.sec='';
    if(a){SvcxSection(a,'');}
    if(S.only){SvcxToggle(document.getElementById('svcx-only'));}
    SvcxState('all');
  };
  // Recharge la liste (catalogue Go) apres une action.
  window.SvcxReload=function(){
    if(document.getElementById('table-loader')&&typeof LoadAjaxSilent==='function'){LoadAjaxSilent('table-loader','fw.system.services.php?table=yes');}
  };
  // Fonctionnalite installable : POST /system/features/action par la page, puis suivi du job.
  window.SvcxRun=function(key,act){
    $.post('fw.system.services.php',{'feature-action':key,'do':act},null,'text').done(function(r){
      var j=null;try{j=JSON.parse(r);}catch(e){}
      if(!j){swal({title:'',text:String(r).substring(0,300),type:'error'});return;}
      if(!j.ok){swal({title:j.title||'',text:j.error||'',type:'error'});return;}
      if(j.already){swal({title:'',text:j.message||'',type:'success'});SvcxReload();return;}
      if(j.job){Loadjs('fw.system.services.php?job-js='+encodeURIComponent(j.job));}
    }).fail(function(x){swal({title:'',text:'HTTP '+x.status,type:'error'});});
  };
  if(read('desc')==='0'){var d=document.getElementById('svcx-desc');if(d){SvcxToggle(d);}}
  if(read('only')==='1'){var o=document.getElementById('svcx-only');if(o){SvcxToggle(o);}}
  if(typeof NoSpinner==='function'){NoSpinner();}
})();
</script>
JS;
}
