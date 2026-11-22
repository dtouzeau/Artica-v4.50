<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des
// tableaux d'entites. Include INCONDITIONNEL: place dans le bootstrap
// if(!class_exists("sockets")) il ne s'executerait jamais et la page mourrait
// sur "Call to undefined function awc_table_style()".
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Spec 019 lot 4c : les politiques passent par /unbound/api/rpz* (validation,
// telechargement borne, unbound-checkconf complet, preuve, retour arriere).
// Les politiques de transfert de zone (axfr) existantes restent lisibles, et ne
// sont modifiables que par l'ancien chemin, sous privilege. Chaque action
// passe par un *-js (GET, rend du JavaScript seulement) puis un POST.
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
// unbound_page : delai d'ecriture (sous les 300 s de nginx), message de delai depasse, masquage des URL.
include_once(dirname(__FILE__)."/ressources/class.unbound.settings.inc");
if(isset($_GET["enable"])){enable_js();exit;}
if(isset($_POST["rpz-toggle"])){policy_toggle_save();exit;}
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["policy-js"])){policy_js();exit;}
// Creation : le meme formulaire que la modification (identifiant 0).
if(isset($_GET["newpolicy-js"])){$_GET["policy-js"]=0;policy_js();exit;}
if(isset($_POST["rpz-save"])){policy_api_save();exit;}

if(isset($_GET["rpz-service"])){rpz_service_js();exit;}
if(isset($_GET["rpz-service-popup"])){rpz_service_popup();exit;}
if(isset($_POST["RPZServiceEnabled"])){rpz_service_save();exit;}

if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){policy_delete();exit;}
// Ancien formulaire : politiques axfr seulement.
if(isset($_POST["ID"])){policy_save();exit;}
if(isset($_GET["policy-popup"])){policy_popup();exit;}
if(isset($_GET["policy-move"])){policy_move_js();exit;}
if(isset($_POST["rpz-move"])){policy_move_save();exit;}
if(isset($_GET["defaults"])){defaults_js();exit;}
if(isset($_POST["rpz-defaults"])){defaults_save();exit;}
if(isset($_GET["td"])){td_row();exit;}
if(isset($_GET["run-js"])){run_js();exit;}
if(isset($_POST["rpz-refresh"])){policy_refresh_save();exit;}


if(isset($_POST["run"])){exit;}
page();



/**
 * Le nom de la fonction de rafraichissement transmis dans l'URL.
 * Il repart dans des OnClick et des $jsafter sans echappement; search_block
 * n'engendre jamais autre chose que "ss<time>".
 */
function rpz_function():string{
	return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}

function rpz_service_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=rpz_function();
    return $tpl->js_dialog("RPZ Service", "$page?rpz-service-popup=yes&function=$function");
}
function rpz_service_popup():bool{
    $tpl=new template_admin();
    $function=rpz_function();

    $RPZServiceUseSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceUseSSL"));
    $RPZServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServicePort"));
    $RPZServiceHostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceHostname");
    $RPZServiceWriteLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceWriteLogs"));
    $RPZServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceEnabled"));
    $RPZServiceClientError=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceClientError");

    if($RPZServicePort==0){
        $RPZServicePort=9905;
    }
    if($RPZServiceEnabled==1){
        if(strlen($RPZServiceClientError)>2){
            echo $tpl->div_error($RPZServiceClientError);
        }
    }

    $form[] = $tpl->field_checkbox("RPZServiceEnabled","{enabled}",$RPZServiceEnabled,true);
    $form[] = $tpl->field_text("RPZServiceHostname","{remote_server_address}",$RPZServiceHostname);
    $form[] = $tpl->field_numeric("RPZServicePort","{remote_server_port}",$RPZServicePort);
    $form[] = $tpl->field_checkbox("RPZServiceUseSSL","{use_ssl}",$RPZServiceUseSSL);
    $form[] = $tpl->field_checkbox("RPZServiceWriteLogs","{write_logs}",$RPZServiceWriteLogs);
    $supported=rpz_transfer_limits_supported();
    $cur=array();
    if($supported){
        $g=(new unbound_api())->get("/unbound/api/settings/rpzclient");
        if(!empty($g["ok"]) && is_array($g["data"]) && is_array($g["data"]["values"] ?? null)){$cur=$g["data"]["values"];}
    }
    foreach(rpz_limit_fields($tpl,"rpzc-",intval($cur["max_transfer_size"] ?? 0),intval($cur["max_transfer_time"] ?? 0),!$supported) as $x){$form[]=$x;}
    if(!$supported){
        echo $tpl->_ENGINE_parse_body($tpl->div_explain("{unbound_version_too_old_transfer_limits}"));
    }
    echo $tpl->form_outside("", $form,"","{apply}","BootstrapDialog1.close();$function();","AsDnsAdministrator");
    return true;
}
/**
 * Client du service RPZ distant. SAVE_POSTs() envoyait a /setinfo TOUTE cle
 * postee (n'importe quel reglage Valkey de l'appliance), sans controle de
 * privilege. Seules les cinq cles RPZService* du formulaire sont ecrites, et
 * le nom d'hote qui finit dans l'URL du depot est verifie.
 */
function rpz_service_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!rpz_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $host=trim((string)($_POST["RPZServiceHostname"] ?? ""));
    if($host!=="" && !preg_match('/^[A-Za-z0-9._:-]{1,253}$/',$host)){
        echo $tpl->post_error("{remote_server_address}: {wrong_value}");
        return false;
    }
    $port=intval($_POST["RPZServicePort"] ?? 0);
    if($port<1 || $port>65535){
        echo $tpl->post_error("{remote_server_port}: {wrong_value}");
        return false;
    }
    $values=array(
        "RPZServiceEnabled"=>intval($_POST["RPZServiceEnabled"] ?? 0)==1?1:0,
        "RPZServiceHostname"=>$host,
        "RPZServicePort"=>$port,
        "RPZServiceUseSSL"=>intval($_POST["RPZServiceUseSSL"] ?? 0)==1?1:0,
        "RPZServiceWriteLogs"=>intval($_POST["RPZServiceWriteLogs"] ?? 0)==1?1:0,
    );
    // Limites AVANT /setinfo : un refus (cache arrete, esclave) ne laisse
    // rien a moitie enregistre. Et seulement si elles changent : le PUT
    // regenere toute la configuration et recharge unbound.
    $lim=rpz_posted_limits("rpzc-");
    if($lim!==null){
        if(isset($lim["error"])){ echo $tpl->post_error($lim["error"]);return false;}
        $api=new unbound_api();
        $api->timeout=unbound_page::WRITE_TIMEOUT;
        $g=$api->get("/unbound/api/settings/rpzclient");
        $cur=(!empty($g["ok"]) && is_array($g["data"]) && is_array($g["data"]["values"] ?? null))?$g["data"]["values"]:null;
        if($cur===null || intval($cur["max_transfer_size"] ?? 0)!=$lim["size"] || intval($cur["max_transfer_time"] ?? 0)!=$lim["time"]){
            $r=$api->put("/unbound/api/settings/rpzclient",array("values"=>array(
                "max_transfer_size"=>$lim["size"],"max_transfer_time"=>$lim["time"])));
            if(empty($r["ok"])){ echo $tpl->post_error(rpz_error_text($r));return false;}
        }
    }
    $keys=array();
    foreach ($values as $key=>$val){
        $keys["Keys"][]=array("key"=>$key,"value"=>(string)$val);
    }
    $e=rpz_rest_err($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/setinfo",$keys));
    if($e!==""){ echo $tpl->post_error($e);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/reconfigure");
    $_POST=$values;
    return admin_tracks_post("Saving RPZ Service client");
}

/** GET /unbound/api/state : unbound sait-il borner les transferts (max-transfer-*) ? Faux si l'etat est illisible. */
function rpz_transfer_limits_supported():bool{
    $r=(new unbound_api())->get("/unbound/api/state");
    return !empty($r["ok"]) && is_array($r["data"]) && !empty($r["data"]["transfer_limits_supported"]);
}

/** Octets -> MiB entiers pour un champ de formulaire (0 = defaut). */
function rpz_bytes_to_mib($bytes):int{
    return intval(round(intval($bytes)/1048576));
}

/**
 * Champs MiB / secondes du formulaire ; $prefix distingue les trois formulaires.
 * Le champ cache porte la valeur exacte en octets : l'affichage en MiB arrondit,
 * et une limite posee par l'API (1536 KiB) ne doit pas devenir 2 MiB parce que
 * l'operateur a modifie un autre champ.
 */
function rpz_limit_fields($tpl,string $prefix,$bytes,$seconds,bool $disabled=false):array{
    $f=array();
    $f[]=$tpl->field_hidden($prefix."maxsize-bytes",(string)intval($bytes));
    $f[]=$tpl->field_numeric($prefix."maxsize","{max_download_size} (MiB)",rpz_bytes_to_mib($bytes),null,null,$disabled);
    $f[]=$tpl->field_numeric($prefix."maxtime","{max_download_time} ({seconds})",intval($seconds),null,null,$disabled);
    return $f;
}

/** Valeurs postees -> octets / secondes ; null si le champ n'a pas ete envoye (champ desactive). */
function rpz_posted_limits(string $prefix):?array{
    if(!isset($_POST[$prefix."maxsize"]) || !isset($_POST[$prefix."maxtime"])){return null;}
    $mib=trim((string)$_POST[$prefix."maxsize"]);
    $sec=trim((string)$_POST[$prefix."maxtime"]);
    if(!ctype_digit($mib==""?"0":$mib) || !ctype_digit($sec==""?"0":$sec)){return array("error"=>"{max_download_size} / {max_download_time}: {wrong_value}");}
    $size=intval($mib)*1048576;
    // MiB affiche inchange : la valeur exacte d'origine (pas l'arrondi)
    $orig=trim((string)($_POST[$prefix."maxsize-bytes"] ?? ""));
    if($orig!=="" && ctype_digit($orig) && rpz_bytes_to_mib(intval($orig))==intval($mib)){$size=intval($orig);}
    return array("size"=>$size,"time"=>intval($sec));
}

/** « 64 MiB / 120 s » : valeurs posees, ou le defaut du moteur (URL : 64 MiB / 120 s). */
function rpz_limits_label(array $p):string{
    $kind=(string)($p["kind"] ?? "url");
    $size=intval($p["max_size"] ?? 0);
    $time=intval($p["max_time"] ?? 0);
    if($kind==="url"){
        if($size==0){$size=64*1048576;}
        if($time==0){$time=120;}
    }elseif($size==0 && $time==0){
        return "";
    }
    $a=$size>0?rpz_bytes_to_mib($size)." MiB":"&mdash;";
    $b=$time>0?$time." s":"&mdash;";
    return "$a / $b";
}

/** Erreur d'une reponse REST; un corps non JSON non vide est une erreur. */
function rpz_rest_err($resp):string{
    $r=json_decode((string)$resp,true);
    if(is_array($r)){
        if(!empty($r["error"])){return (string)$r["error"];}
        if(isset($r["success"]) && !$r["success"]){return (string)(isset($r["message"])?$r["message"]:"{error}");}
        if(isset($r["Status"]) && !$r["Status"]){return (string)(isset($r["Error"])?$r["Error"]:"{error}");}
        return "";
    }
    $raw=trim(str_replace(array("'","\\"),array(" "," "),(string)$resp));
    return $raw==""?"":substr($raw,0,300);
}



/** Erreur d'une ecriture RPZ pour post_error() : delai depasse explique, URL masquees. */
function rpz_error_text(array $r):string{
    return unbound_page::redact_urls(unbound_page::error_text($r));
}

/** Privilege des ecritures par l'API : le refus d'un esclave vient d'articarest (409, maitre et console). */
function rpz_is_admin():bool{
    $tpl=new template_admin();
    return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}

/** Le rafraichissement du tableau ; jamais "();" quand la fonction manque (SyntaxError). */
function rpz_refresh_call():string{
    $f=rpz_function();
    if($f!==""){return "$f();";}
    return "LoadAjax('rpz-local-domains','".CurrentPageName()."?main=yes');";
}

function rpz_notices_call():string{
    return "Loadjs('".CurrentPageName()."?api-notices-js=yes');";
}

function api_notices_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    echo unbound_api::notices_js($tpl->javascript_parse_text("{warning}",0,true),function($s) use ($tpl){
        return $tpl->javascript_parse_text($s,0,true);
    });
}

/**
 * Constats d'une ecriture reussie : avertissements et notes de l'enveloppe,
 * notes de la preuve (ce qui n'a pas pu etre verifie) et redemarrage
 * d'unbound (proof.restarted : rechargement remplace par un redemarrage).
 */
function rpz_api_notices(array $r):array{
    $out=unbound_api::notices($r);
    $data=isset($r["data"])?$r["data"]:null;
    $p=(is_array($data) && isset($data["proof"]) && is_array($data["proof"]))?$data["proof"]:array();
    if(isset($p["notes"]) && is_array($p["notes"])){
        foreach($p["notes"] as $n){ if(is_scalar($n) && trim((string)$n)!==""){$out[]=trim((string)$n);} }
    }
    if(!empty($p["restarted"])){$out[]="{unbound_api_restarted}";}
    // Une URL de liste peut porter une cle (?key=...) : masquee dans les toasts.
    foreach($out as $i=>$t){$out[$i]=unbound_page::redact_urls($t);}
    return $out;
}

function rpz_queue(array $r){
    $n=rpz_api_notices($r);
    if(count($n)>0){unbound_api::queue_texts($n);}
}

/** GET /unbound/api/rpz : data, ou null avec $err. */
function rpz_api_list($api,&$err){
    $r=$api->get("/unbound/api/rpz");
    if(!$r["ok"]){$err=unbound_page::error_text($r);return null;}
    if(!is_array($r["data"])){$err="articarest: no data";return null;}
    if(!isset($r["data"]["policies"]) || !is_array($r["data"]["policies"])){$r["data"]["policies"]=array();}
    return $r["data"];
}

/** Une politique par son identifiant (la route n'a pas de lecture unitaire). */
function rpz_api_policy($api,$id,&$err){
    $data=rpz_api_list($api,$err);
    if($data===null){return null;}
    foreach($data["policies"] as $p){
        if(is_array($p) && intval($p["id"] ?? 0)==intval($id)){return $p;}
    }
    $err="{policy} $id: {not_found}";
    return null;
}

/** Les politiques ordinaires (hors liste blanche reservee), dans l'ordre servi. */
function rpz_api_ordinary(array $data):array{
    $out=array();
    foreach($data["policies"] as $p){
        if(!is_array($p) || !empty($p["reserved"])){continue;}
        $out[]=$p;
    }
    return $out;
}

/**
 * JavaScript d'une action : un POST (XHRConnection), l'erreur en swal (texte
 * deja echappe par post_error), puis rafraichissement et constats.
 */
function rpz_xhr_js($field,$value,$extra=array(),$info=""):string{
    $page=CurrentPageName();
    $refresh=rpz_refresh_call();
    $notices=rpz_notices_call();
    $t=time().rand(100,999);
    $js=array();
    if($info!==""){
        $tpl=new template_admin();
        $enc=json_encode(htmlspecialchars($tpl->javascript_parse_text($info,0,true),ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        if($enc!==false){$js[]="if(typeof toastr!=='undefined'){toastr.info($enc);}";}
    }
    $js[]="var xRpz$t=function(obj){";
    $js[]="\tvar res=obj.responseText;";
    $js[]="\tif(res.length>3){";
    $js[]="\t\t".unbound_page::swal_js("res");
    $js[]="\t}";
    $js[]="\t$refresh";
    $js[]="\t$notices";
    $js[]="};";
    $js[]="var XHR=new XHRConnection();";
    $js[]="XHR.appendData(".json_encode((string)$field).",".json_encode((string)$value,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT).");";
    foreach($extra as $k=>$v){
        $js[]="XHR.appendData(".json_encode((string)$k).",".json_encode((string)$v,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT).");";
    }
    $js[]="XHR.sendAndLoad('$page','POST',xRpz$t);";
    return implode("\n",$js);
}

function policy_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=rpz_function();
    $ID=intval($_GET["policy-js"]);
    $title="{new_policy}";
    if($ID>0){
        $err="";
        $p=rpz_api_policy(new unbound_api(),$ID,$err);
        $title=($p===null)?"{policy} $ID":preg_replace('/[^A-Za-z0-9._ -]/','',(string)$p["name"]);
    }
    $tpl->js_dialog($tpl->javascript_parse_text($title), "$page?policy-popup=$ID&function=$function");
}

/** Nouveau telechargement de la liste : *-js -> POST rpz-refresh (axfr : ancien chemin). */
function run_js():bool{
    $tpl=new template_admin();
    $function=rpz_function();
    $ID=intval($_GET["run-js"]);
    if($ID==0){
        return $tpl->js_error("ID ==0");
    }
    if(!rpz_is_admin()){ return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    $err="";
    $p=rpz_api_policy(new unbound_api(),$ID,$err);
    if($p===null){ return $tpl->js_error($err);}
    if(($p["kind"] ?? "")==="axfr"){
        if(!rpz_can_edit()){ return $tpl->js_error("{ERROR_NO_PRIVS2}");}
        $js=$tpl->framework_buildjs("/dnscache/rpz/download/$ID",
            "rpz.$ID.progress","rpz.$ID.log","rpz-progress-restart",rpz_refresh_call(),null,null,"AsDnsAdministrator");
        return $tpl->js_confirm_execute("{download} ".htmlspecialchars((string)$p["name"])." {database}","run",$ID,$js);
    }
    header("content-type: application/x-javascript");
    echo rpz_xhr_js("rpz-refresh",$ID,array(),"{rpz_applying}");
    return true;
}

function policy_refresh_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!rpz_is_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["rpz-refresh"]);
    if($ID<=0){ echo $tpl->post_error("ID: {wrong_value}");return false;}
    $api=new unbound_api();
    // Telechargement synchrone, borne par articarest a 120 s et 64 Mio.
    $api->timeout=unbound_page::WRITE_TIMEOUT;
    $r=$api->post("/unbound/api/rpz/$ID/refresh");
    if(!$r["ok"]){ echo $tpl->post_error(rpz_error_text($r));return false;}
    rpz_queue($r);
    $name=(is_array($r["data"]) && isset($r["data"]["policy"]["name"]))?$r["data"]["policy"]["name"]:$ID;
    return admin_tracks("Refresh DNS policy zone (RPZ) $ID $name");
}

/** Activation/desactivation : *-js (rend du JavaScript, n'ecrit rien) -> POST rpz-toggle. */
function enable_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["enable"]);
    // L'etat VOULU, d'apres l'icone affichee : un re-clic apres une coupure
    // ne doit jamais inverser une seconde fois (ronde 1, I1).
    $want=((string)($_GET["want"] ?? "")==="disable")?"disable":"enable";
    header("content-type: application/x-javascript");
    if(!rpz_is_admin()){ return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    echo rpz_xhr_js("rpz-toggle",$ID,array("want"=>$want),"{rpz_applying}");
    return true;
}

function policy_toggle_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!rpz_is_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["rpz-toggle"]);
    if($ID<=0){ echo $tpl->post_error("ID: {wrong_value}");return false;}
    $api=new unbound_api();
    $api->timeout=unbound_page::WRITE_TIMEOUT;
    $err="";
    $p=rpz_api_policy($api,$ID,$err);
    if($p===null){ echo $tpl->post_error($err);return false;}
    if(!empty($p["reserved"])){ echo $tpl->post_error("{rpz_whitelist_reserved}");return false;}
    $action=(string)($_POST["want"] ?? "");
    if($action!=="enable" && $action!=="disable"){ echo $tpl->post_error("want: {wrong_value}");return false;}
    // Deja dans l'etat voulu (operation precedente terminee apres une coupure) : rien a faire.
    if(empty($p["enabled"])===($action==="disable")){ return true;}
    if(($p["kind"] ?? "")==="axfr"){ return rpz_legacy_toggle($api,$ID,$p,$action);}
    // L'activation telecharge la liste et la verifie avant de l'installer.
    $r=$api->post("/unbound/api/rpz/$ID/$action");
    if(!$r["ok"]){ echo $tpl->post_error(rpz_error_text($r));return false;}
    rpz_queue($r);
    return admin_tracks("Set DNS policy zone (RPZ) $ID {$p["name"]} to $action");
}

/** Politique axfr : ancien chemin (SQL parametre + compilation), sous privilege et refus esclave. */
function rpz_legacy_toggle($api,$ID,array $p,$action):bool{
    $tpl=new template_admin();
    if(!rpz_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $refusal=$api->legacy_write_refusal();
    if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
    $enabled=($action==="enable")?1:0;
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $q->QUERY_SQL_PARAMS("UPDATE policies SET enabled=? WHERE ID=? AND rpztype=2",array($enabled,intval($ID)));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/rpz/build");
    unbound_api::queue_texts(array("{rpz_axfr_unproven}"));
    return admin_tracks("Set RBL Policy $ID to ".($enabled==1?"enable":"disable"));
}

/** Deplacement : *-js -> POST rpz-move (articarest : position parmi les politiques ordinaires). */
function policy_move_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!rpz_is_admin()){ $tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_GET["policy-move"]);
    // Positions ABSOLUES (depart affiche, arrivee voulue) : idempotent (ronde 1, I1).
    $from=intval($_GET["from"] ?? 0);
    $to=intval($_GET["to"] ?? 0);
    echo rpz_xhr_js("rpz-move",$ID,array("from"=>$from,"to"=>$to));
}

function policy_move_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!rpz_is_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["rpz-move"]);
    $from=intval($_POST["from"] ?? 0);
    $pos=intval($_POST["to"] ?? 0);
    if($from<1 || $pos<1){ echo $tpl->post_error("{position}: {wrong_value}");return false;}
    $api=new unbound_api();
    $err="";
    $data=rpz_api_list($api,$err);
    if($data===null){ echo $tpl->post_error($err);return false;}
    $cur=rpz_position_of(rpz_api_ordinary($data),$ID);
    if($cur<=0){ echo $tpl->post_error("{policy} $ID: {not_found}");return false;}
    // Deja a la position voulue (deplacement precedent termine) : rien a faire.
    if($cur==$pos){ return true;}
    // L'ordre a change depuis l'affichage : ne rien deviner.
    if($cur!=$from){ echo $tpl->post_error("{rpz_order_changed}");return false;}
    $r=$api->post("/unbound/api/rpz/$ID/move",array("position"=>$pos));
    if(!$r["ok"]){ echo $tpl->post_error(rpz_error_text($r));return false;}
    rpz_queue($r);
    return admin_tracks("Move DNS policy zone (RPZ) $ID to position $pos");
}

/** Position actuelle (1 = premiere apres la liste blanche) ; 0 si la politique est absente. */
function rpz_position_of(array $ordinary,$id):int{
    foreach(array_values($ordinary) as $i=>$p){
        if(intval($p["id"] ?? 0)==intval($id)){return $i+1;}
    }
    return 0;
}

/** Les actions d'une politique (valeurs d'API) et leurs libelles. */
function rpz_actions():array{
    return array(
        "nxdomain"=>"{HEADERS_FROM_OR_TO_NO_DOMAIN}",
        "nodata"=>"{no_data}",
        "drop"=>"{drop}",
        "cname"=>"{SpoofCNAMEAction}",
        "passthru"=>"{do_nothing}");
}

function policy_popup(){
    $tpl=new template_admin();
    $ID=intval($_GET["policy-popup"]);
    $function=rpz_function();
    $p=array("name"=>"","source"=>"","action"=>"nxdomain","cname"=>"","description"=>"");
    if($ID>0){
        $err="";
        $cur=rpz_api_policy(new unbound_api(),$ID,$err);
        if($cur===null){ echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($tpl->javascript_parse_text($err,0,true))));return;}
        if(!empty($cur["reserved"])){ echo $tpl->_ENGINE_parse_body($tpl->div_explain("{rpz_whitelist_reserved}"));return;}
        if(($cur["kind"] ?? "")==="axfr"){ policy_popup_legacy($ID);return;}
        $p=array_merge($p,$cur);
        if(!rpz_is_admin()){$p["source"]=unbound_page::redact_url($p["source"]);}
    }
    $actions=rpz_actions();
    $action=(string)$p["action"];
    // Valeur vivante absente du catalogue (action inconnue) : rien n'est
    // selectionne, articarest refusera la saisie vide et le dira.
    if($action!=="" && !isset($actions[$action])){$actions[$action]=$action;}

    $form[]=$tpl->field_hidden("rpz-save",$ID);
    $form[]=$tpl->field_text("rpz-name","{rulename}",$p["name"],true);
    $form[]=$tpl->field_text("rpz-source","{url}",$p["source"],true);
    $form[]=$tpl->field_array_hash($actions,"rpz-action","{policy}",$action);
    $form[]=$tpl->field_text("rpz-cname","CNAME",isset($p["cname"])?$p["cname"]:"");
    $form[]=$tpl->field_text("rpz-description","{description}",isset($p["description"])?$p["description"]:"");
    foreach(rpz_limit_fields($tpl,"rpz-",intval($p["max_size"] ?? 0),intval($p["max_time"] ?? 0)) as $x){$form[]=$x;}
    $button="{apply}";
    $title=$ID>0?htmlspecialchars((string)$p["name"]):"{new_policy}";
    if($ID==0){
        $form[]=$tpl->field_checkbox("rpz-enabled","{enabled}",1);
        $button="{create}";
    }
    // form_outside() ignore son 3e argument (explication) : elle est montree par div_explain (skill §11).
    // Creation : seules les listes URL passent par l'API ; une politique de transfert
    // de zone (AXFR) ne peut plus etre creee, et l'administrateur doit savoir pourquoi.
    $explain="{rpz_api_form_explain}";
    if($ID==0){$explain.="<br>{rpz_axfr_no_create}";}
    echo $tpl->_ENGINE_parse_body($tpl->div_explain($explain));
    echo $tpl->form_outside($title,$form,"{rpz_api_form_explain}",$button,
        "BootstrapDialog1.close();".rpz_refresh_call().rpz_notices_call(),"AsDnsAdministrator");
}

/**
 * Creation et modification d'une politique de type URL par l'API : articarest
 * valide, telecharge (120 s, 64 Mio), refuse une liste qui couperait
 * l'appliance, verifie par unbound-checkconf, recharge et prouve.
 */
function policy_api_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!rpz_is_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["rpz-save"]);
    $body=array(
        "name"=>trim((string)($_POST["rpz-name"] ?? "")),
        "kind"=>"url",
        "source"=>trim((string)($_POST["rpz-source"] ?? "")),
        "action"=>trim((string)($_POST["rpz-action"] ?? "")),
        "cname"=>trim((string)($_POST["rpz-cname"] ?? "")),
        "description"=>trim((string)($_POST["rpz-description"] ?? "")),
    );
    $lim=rpz_posted_limits("rpz-");
    if($lim!==null){
        if(isset($lim["error"])){ echo $tpl->post_error($lim["error"]);return false;}
        $body["max_size"]=$lim["size"];
        $body["max_time"]=$lim["time"];
    }
    $api=new unbound_api();
    $api->timeout=unbound_page::WRITE_TIMEOUT;
    if($ID>0){
        $err="";
        $cur=rpz_api_policy($api,$ID,$err);
        if($cur===null){ echo $tpl->post_error($err);return false;}
        if(!empty($cur["reserved"])){ echo $tpl->post_error("{rpz_whitelist_reserved}");return false;}
        if(($cur["kind"] ?? "")==="axfr"){ echo $tpl->post_error("{rpz_axfr_unproven}");return false;}
        $r=$api->put("/unbound/api/rpz/$ID",$body);
    }else{
        $body["enabled"]=(intval($_POST["rpz-enabled"] ?? 1)==1);
        $r=$api->post("/unbound/api/rpz",$body);
    }
    if(!$r["ok"]){ echo $tpl->post_error(rpz_error_text($r));return false;}
    rpz_queue($r);
    return admin_tracks(($ID>0?"Update":"Create")." DNS policy zone (RPZ) {$body["name"]} ({$body["action"]}) ".unbound_page::redact_url($body["source"]));
}

/** Ancien formulaire, pour une politique de transfert de zone (axfr) existante. */
function policy_popup_legacy($ID){
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM policies WHERE ID=".intval($ID));
    if(!is_array($ligne)){$ligne=array();}
    $rpztype=intval($ligne["rpztype"] ?? 0);
    if($rpztype!=2){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{policy}: {wrong_value}"));return;}

    $tpl->field_hidden("ID",$ID);
    $form[]=$tpl->field_text("rpzurl","{DNS_SERVER}",$ligne["rpzurl"],true);

    $defpol_array["Policy.Custom"]="{SpoofCNAMEAction}";
    $defpol_array["Policy.Drop"]="{drop}";
    $defpol_array["Policy.NoAction"]="{do_nothing}";
    $defpol_array["Policy.NODATA"]="{no_data}";
    $defpol_array["Policy.NXDOMAIN"]="{HEADERS_FROM_OR_TO_NO_DOMAIN}";
    $form[] = $tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    $form[] = $tpl->field_text("zone","{zone}",$ligne["zone"]);
    $form[] = $tpl->field_text("rpzname","{rulename}",$ligne["rpzname"]);
    $form[] = $tpl->field_array_hash($defpol_array,"defpol","{policy}",$ligne["defpol"]);
    $form[] = $tpl->field_text("defcontent","CNAME",$ligne["defcontent"]);
    $supported=rpz_transfer_limits_supported();
    $curp=rpz_api_policy(new unbound_api(),$ID,$errp);
    if(!is_array($curp)){$curp=array();}
    foreach(rpz_limit_fields($tpl,"rpza-",intval($curp["max_size"] ?? 0),intval($curp["max_time"] ?? 0),!$supported) as $x){$form[]=$x;}
    if(!$supported){
        echo $tpl->_ENGINE_parse_body($tpl->div_explain("{unbound_version_too_old_transfer_limits}"));
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("{rpz_axfr_legacy}"));   // form_outside() ignore son explication (skill §11)
    echo $tpl->form_outside(htmlspecialchars((string)$ligne["rpzname"])." {type}:$rpztype", $form,"{rpz_axfr_legacy}","{apply}",
        "BootstrapDialog1.close();".rpz_refresh_call().rpz_notices_call(),"AsDnsAdministrator");
}

/**
 * Ancien enregistrement : SEULEMENT une politique axfr existante (l'API
 * refuse de les ecrire : unbound chargerait les transferts suivants sans
 * controle de couverture). Privilege, refus esclave, SQL parametre.
 */
function policy_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!rpz_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["ID"]);
    if($ID==0){ echo $tpl->post_error("ID ==0");return false;}
    $refusal=(new unbound_api())->legacy_write_refusal();
    if($refusal!==""){ echo $tpl->post_error($refusal);return false;}

    // CLEAN_POST() DECODE les valeurs sans rien echapper: elles partaient
    // telles quelles dans l'UPDATE, une quote dans le nom suffisait a reecrire
    // toutes les politiques. Les valeurs sont desormais liees.
    $defpol=trim((string)($_POST["defpol"] ?? ""));
    $allowed=array("Policy.Custom","Policy.Drop","Policy.NoAction","Policy.NODATA","Policy.NXDOMAIN");
    if(!in_array($defpol,$allowed,true)){ echo $tpl->post_error("{policy}: {error}");return false;}

    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $cur=$q->mysqli_fetch_array("SELECT rpztype FROM policies WHERE ID=$ID");
    if(!is_array($cur)){ echo $tpl->post_error("{policy}: {error}");return false;}
    // Les politiques URL passent par l'API (policy_api_save).
    if(intval($cur["rpztype"])!=2){ echo $tpl->post_error("{policy}: {wrong_value}");return false;}
    $rpzurl=trim((string)($_POST["rpzurl"] ?? ""));
    $rpzname=trim((string)($_POST["rpzname"] ?? ""));
    $zone=trim((string)($_POST["zone"] ?? ""));
    $defcontent=trim((string)($_POST["defcontent"] ?? ""));
    // Ces valeurs sont recopiees ligne a ligne dans /etc/unbound/rpz.conf et
    // zone devient un chemin de fichier ecrit en root: memes regles que
    // ValidPolicyRow cote articarest.
    $e=rpz_policy_row_error(intval($cur["rpztype"]),$rpzname,$zone,$defpol,$defcontent,$rpzurl,true);
    if($e!==""){ echo $tpl->post_error($e);return false;}

    $q->QUERY_SQL_PARAMS("UPDATE policies SET rpzurl=?, enabled=?, rpzname=?, zone=?, defpol=?, lastsaved=?, defcontent=? WHERE ID=? AND rpztype=2",
        array(
            $rpzurl,
            intval($_POST["enabled"] ?? 0),
            $rpzname,
            $zone,
            $defpol,
            time(),
            $defcontent,
            $ID));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks_post("Saving RPZ Policy {$_POST["rpzname"]}");
    $sock=new sockets();
    $sock->REST_API("/dnscache/rpz/build");
    unbound_api::queue_texts(array("{rpz_axfr_unproven}"));
    // Les limites d'une politique AXFR ne passent que par la route /limits ; elle refuse (409)
    // des valeurs inchangees, donc seulement si elles different de celles de l'API.
    $lim=rpz_posted_limits("rpza-");
    if($lim!==null){
        if(isset($lim["error"])){ echo $tpl->post_error($lim["error"]);return false;}
        $errp="";
        $curp=rpz_api_policy(new unbound_api(),$ID,$errp);
        if(!is_array($curp)){ echo $tpl->post_error($errp);return false;}
        if(intval($curp["max_size"] ?? 0)!=$lim["size"] || intval($curp["max_time"] ?? 0)!=$lim["time"]){
            $api=new unbound_api();
            $api->timeout=unbound_page::WRITE_TIMEOUT;
            $r=$api->post("/unbound/api/rpz/$ID/limits",array("max_size"=>$lim["size"],"max_time"=>$lim["time"]));
            if(empty($r["ok"])){ echo $tpl->post_error(rpz_error_text($r));return false;}
        }
    }
    return true;
}

function delete_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!rpz_is_admin()){ $tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_GET["delete-js"]);
    $err="";
    $p=rpz_api_policy(new unbound_api(),$ID,$err);
    if($p===null){ $tpl->js_error($err);return;}
    if(!empty($p["reserved"])){ $tpl->js_error("{rpz_whitelist_reserved}");return;}
    $tpl->js_confirm_delete(htmlspecialchars((string)$p["name"]),"delete",$ID,rpz_refresh_call().rpz_notices_call());

}
function policy_delete(){
    $tpl=new template_admin();
    if(!rpz_is_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["delete"]);
    if($ID<=0){ echo $tpl->post_error("ID: {wrong_value}");return false;}
    $api=new unbound_api();
    $err="";
    $p=rpz_api_policy($api,$ID,$err);
    if($p===null){ echo $tpl->post_error($err);return false;}
    if(!empty($p["reserved"])){ echo $tpl->post_error("{rpz_whitelist_reserved}");return false;}
    if(($p["kind"] ?? "")==="axfr"){
        // Ancien chemin, sous privilege et refus esclave.
        if(!rpz_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
        $refusal=$api->legacy_write_refusal();
        if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
        $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
        $q->QUERY_SQL_PARAMS("DELETE FROM policies WHERE ID=? AND rpztype=2",array($ID));
        if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/rpz/build");
        unbound_api::queue_texts(array("{rpz_axfr_unproven}"));
        return admin_tracks("Remove RPZ Policy {$p["name"]} $ID");
    }
    $r=$api->delete("/unbound/api/rpz/$ID");
    if(!$r["ok"]){ echo $tpl->post_error(rpz_error_text($r));return false;}
    rpz_queue($r);
    return admin_tracks("Remove DNS policy zone (RPZ) {$p["name"]} $ID");
}
function page(){
    $page       = CurrentPageName();
	$tpl        = new template_admin();



    $html=$tpl->page_header("{POLICIES_ZONES}","fa-solid fa-shield",
    "{RPZ_EXPLAIN}","$page?main=yes","rpz-local-domains","rpz-progress-restart",true,"rpz-local-domains");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function btns():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $topbuttons=array();

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('$page?policy-js=0&function=".rpz_function()."');",
            ico_plus, "{new_policy}");
    }

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('$page?rpz-service=yes&function=".rpz_function()."');",
            ico_clouds, "RPZ Service");
    }

    if($PowerDNSEnableClusterSlave==0) {
        $topbuttons[] = array("Loadjs('$page?defaults=yes&function=".rpz_function()."');",
            ico_import, "{default_rules}");
    }

    return $tpl->table_buttons($topbuttons);


}

/** Les politiques par defaut (listes publiques), creees DESACTIVEES : aucune liste n'est telechargee sans un geste. */
function rpz_default_policies():array{
    $d=array();
    $d[]=array("urlhaus","https://urlhaus.abuse.ch/downloads/rpz","URLhaus is a project from abuse.ch with the goal of sharing malicious URLs that are being used for malware distribution.");
    $d[]=array("curben","https://curbengh.github.io/malware-filter/phishing-filter-rpz.conf","phishtank.com, openphish.com, phishunt.io Compilation");
    $d[]=array("hblock","https://hblock.molinero.dev/hosts_rpz.txt","hBlock is a POSIX-compliant shell script that gets a list of domains that serve ads, tracking scripts and malware from multiple sources");
    $d[]=array("yoyo","https://pgl.yoyo.org/adservers/serverlist.php?hostformat=rpz&showintro=1&mimetype=plaintext","Advertising Servers");
    $d[]=array("stevenblack.porn","https://raw.githubusercontent.com/StevenBlack/hosts/master/alternates/porn/hosts","StevenBlack/hosts with the porn extension");
    return $d;
}

/** {default_rules} : *-js (rend du JavaScript, n'ecrit rien) -> POST rpz-defaults. */
function defaults_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!rpz_is_admin()){ $tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    echo rpz_xhr_js("rpz-defaults","yes");
}

/**
 * Cree par l'API les politiques par defaut absentes (meme nom, sans
 * distinction de casse). Elles le sont desactivees : la creation active
 * telechargerait cinq listes en une requete ; l'activation de chacune les
 * telecharge et les verifie une a une.
 */
function defaults_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!rpz_is_admin()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $api=new unbound_api();
    $err="";
    $data=rpz_api_list($api,$err);
    if($data===null){ echo $tpl->post_error($err);return false;}
    $have=array();
    foreach($data["policies"] as $p){ if(is_array($p)){$have[strtolower((string)$p["name"])]=true;} }
    $created=array();$errors=array();
    foreach(rpz_default_policies() as $d){
        if(isset($have[strtolower($d[0])])){continue;}
        $r=$api->post("/unbound/api/rpz",array("name"=>$d[0],"kind"=>"url","source"=>$d[1],
            "action"=>"nxdomain","description"=>$d[2],"enabled"=>false));
        if(!$r["ok"]){$errors[]=$d[0].": ".rpz_error_text($r);continue;}
        rpz_queue($r);
        $created[]=$d[0];
    }
    if(count($created)>0){
        unbound_api::queue_texts(array("{rpz_defaults_disabled}"));
        admin_tracks("Create default DNS policy zones (RPZ): ".implode(", ",$created));
    }
    if(count($errors)>0){ echo $tpl->post_error(implode("\n",$errors));return false;}
    return true;
}
/**
 * Badge d'etat d'une politique. Partage par la ligne du tableau et par
 * td_row(), qui le reecrit apres une bascule: td_row() ne lisait que la
 * colonne enabled et affichait « Active » en reactivant une politique en erreur.
 */
function rpz_status_badge($ligne):string{
    if(intval($ligne["enabled"] ?? 0)==0){
        return "<span class='awc-badge awc-badge-off'>{disabled}</span>";
    }
    if(intval($ligne["rpztype"] ?? 0)==1){
        if(strlen(trim((string)($ligne["rpzurl"] ?? "")))<3){
            return "<span class='awc-badge awc-badge-alert'>{url} {error}</span>";
        }
        $status=intval($ligne["status"] ?? 0);
        if($status>1){return "<span class='awc-badge awc-badge-alert'>{error}</span>";}
        if($status==0){return "<span class='awc-badge awc-badge-off'>{inactive}</span>";}
    }
    return "<span class='awc-badge awc-badge-on'>{active2}</span>";
}

/**
 * Validation d'une politique avant ecriture, double en PHP de
 * UnboundRPZ.ValidPolicyRow (articarest unbound/UnboundRPZ/validate.go): les
 * deux se changent ensemble.
 *
 * rpzname, zone, defcontent et rpzurl sont recopies ligne a ligne dans
 * /etc/unbound/rpz.conf, et zone devient le chemin d'un fichier qu'articarest
 * cree ou remplace en root: un saut de ligne ajoutait une directive a la
 * configuration d'unbound, un "../" ecrivait hors de /etc/unbound.
 *
 * $full=false ne verifie que le nom et le type (creation d'une politique
 * vierge, sans zone ni source: articarest l'ignore tant qu'elle n'a pas
 * d'URL). Rend "" ou le message d'erreur a passer a post_error().
 */
function rpz_policy_row_error(int $rpztype,string $rpzname,string $zone,string $defpol,string $defcontent,string $rpzurl,bool $full):string{
    $fields=array("{rulename}"=>$rpzname,"{zone}"=>$zone,"{policy}"=>$defpol,"CNAME"=>$defcontent,"{url}"=>$rpzurl);
    foreach ($fields as $label=>$v){
        if(rpz_has_control_or_quote($v)){return "$label: {wrong_value}";}
    }
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,62}$/',$rpzname)){return "{rulename}: {wrong_value}";}
    if($rpztype!=1 && $rpztype!=2){return "{type}: {wrong_value}";}
    if(!$full){return "";}
    if(!rpz_zone_is_safe($zone)){return "{zone}: {wrong_value}";}
    $actions=array("Policy.Custom","Policy.Drop","Policy.NXDOMAIN","Policy.NODATA","Policy.NoAction");
    if(!in_array($defpol,$actions,true)){return "{policy}: {wrong_value}";}
    if($defpol=="Policy.Custom" && !rpz_is_dns_name($defcontent)){return "CNAME: {wrong_value}";}
    if($rpzurl===""){return "";}
    if($rpztype==2){
        // Serveur(s) DNS primaire(s) "hote[@port]", une ligne "master:" chacun.
        foreach (explode(",",$rpzurl) as $master){
            if(!rpz_is_master(trim($master))){return "{DNS_SERVER}: {wrong_value}";}
        }
        return "";
    }
    if(!rpz_is_safe_url($rpzurl)){return "{url}: {wrong_value}";}
    return "";
}

/** hasControlOrQuote: controles C0/C1, DEL, ' " ` \ et separateurs U+2028/U+2029. */
function rpz_has_control_or_quote(string $v):bool{
    if(preg_match('/[\x00-\x1F\x7F"\'`\\\\]/',$v)){return true;}
    // C1 (U+0080..U+009F), puis U+2028 et U+2029, en UTF-8.
    if(preg_match('/\xC2[\x80-\x9F]/',$v)){return true;}
    if(preg_match('/\xE2\x80[\xA8\xA9]/',$v)){return true;}
    return false;
}

/**
 * validZone: chemin relatif sous /etc/unbound, deja canonique (filepath.Clean
 * ne le change pas), sans "..", caracteres [A-Za-z0-9._/-], 255 au plus, et
 * jamais le nom d'un fichier de configuration d'unbound.
 */
function rpz_zone_is_safe(string $zone):bool{
    if($zone==="" || strlen($zone)>255){return false;}
    if(!preg_match('#^[A-Za-z0-9._/-]+$#',$zone)){return false;}
    if(substr($zone,0,1)=="/"){return false;}
    if(strpos($zone,"..")!==false){return false;}
    $segments=explode("/",$zone);
    foreach ($segments as $segment){
        // "a//b", "./a", "a/" ou "." ne sont pas canoniques.
        if($segment==="" || $segment==="."){return false;}
    }
    $top=strtolower($segments[0]);
    if(in_array($top,array("dnssec","root.key","root.hints"),true)){return false;}
    $last=$segments[count($segments)-1];
    $dot=strrpos($last,".");
    if($dot!==false){
        $ext=strtolower(substr($last,$dot));
        $forbidden=array(".conf",".key",".pem",".crt",".hints",".local",".db",".jnl",".pid",".sock",
            ".bak",".failed",".anchor",".tar",".gz",".progress");
        if(in_array($ext,$forbidden,true)){return false;}
    }
    return true;
}

/** validDNSName: nom DNS (un point final permis) fait d'etiquettes LDH et "_". */
function rpz_is_dns_name(string $name):bool{
    if(substr($name,-1)=="."){$name=substr($name,0,-1);}
    if($name==="" || strlen($name)>253){return false;}
    foreach (explode(".",$name) as $label){
        if(!preg_match('/^[A-Za-z0-9_]([A-Za-z0-9_-]{0,61}[A-Za-z0-9_])?$/',$label)){return false;}
    }
    return true;
}

/** masterList: un element "hote[@port]", hote = adresse IP ou nom DNS. */
function rpz_is_master(string $item):bool{
    if($item===""){return false;}
    $host=$item;
    $at=strrpos($item,"@");
    if($at!==false){
        $host=substr($item,0,$at);
        $port=substr($item,$at+1);
        if(!preg_match('/^[0-9]{1,5}$/',$port) || intval($port)<1 || intval($port)>65535){return false;}
    }
    if(filter_var($host,FILTER_VALIDATE_IP)!==false){return true;}
    return rpz_is_dns_name($host);
}

/** validRPZURL: http(s), 2048 caracteres au plus, sans espace ni identifiants, avec un hote. */
function rpz_is_safe_url(string $url):bool{
    if(strlen($url)>2048 || preg_match('/[ \t]/',$url)){return false;}
    if(!preg_match('#^https?://#i',$url)){return false;}
    $p=@parse_url($url);
    if(!is_array($p)){return false;}
    if(!isset($p["host"]) || $p["host"]===""){return false;}
    if(isset($p["user"]) || isset($p["pass"])){return false;}
    // url.Parse refuse un echappement % invalide et un hote hors de la
    // grammaire: refuses ici a l'enregistrement plutot qu'ignores ensuite.
    if(preg_match('/%(?![0-9A-Fa-f]{2})/',$url)){return false;}
    $host=(string)$p["host"];
    if(!preg_match('/^([A-Za-z0-9_-]+\.)*[A-Za-z0-9_-]+\.?$/',$host) && !preg_match('/^\[[0-9A-Fa-f:.]+\]$/',$host)){return false;}
    // parse_url coupe l'autorite au premier "/" ou "?": un "@" qui y reste
    // serait lu comme des identifiants par un autre analyseur.
    $authority=preg_replace('#^https?://#i','',$url);
    $authority=preg_split('#[/?\#]#',$authority,2)[0];
    if(strpos($authority,"@")!==false){return false;}
    return true;
}

/** Les politiques sont-elles modifiables par cette session sur ce noeud ? */
function rpz_can_edit():bool{
    if(isset($GLOBALS["RPZ_CAN_EDIT"])){return $GLOBALS["RPZ_CAN_EDIT"];}
    $tpl=new template_admin();
    $slave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $GLOBALS["RPZ_CAN_EDIT"]=($slave==0 && $tpl->IsSecurity("AsDnsAdministrator"));
    return $GLOBALS["RPZ_CAN_EDIT"];
}

function td_row():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["td"]);
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM policies WHERE ID=$ID");
    if(!is_array($ligne)){$ligne=array();}
    $off=intval($ligne["enabled"] ?? 0)==0?"true":"false";
    $status_ico=base64_encode($tpl->_ENGINE_parse_body(rpz_status_badge($ligne)));
    header("content-type: application/x-javascript");
    $f[]="if( document.getElementById('status-ico-$ID') ){";
    $f[]="\tdocument.getElementById('status-ico-$ID').innerHTML=base64_decode('$status_ico');";
    $f[]="}";
    $f[]="$('#rpz-ico-$ID').toggleClass('awc-ico-off',$off);";
    $f[]="$('#rpz-name-$ID').toggleClass('awc-name-off',$off);";
    echo @implode("\n",$f);
    return true;
}

/** Une ligne d'etat du client « RPZ Service » (zone recue, ou erreur du client). */
function rpz_service_row($name,$badge):string{
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='".ico_clouds."'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars((string)$name)."</span>";
    $h.="<div class='awc-meta'>RPZ Service</div></span></div></td>";
    $h.="<td><span class='awc-none'>&mdash;</span></td>";
    $h.="<td class='awc-right'><span class='awc-none'>&mdash;</span></td>";
    $h.="<td class='center'>$badge</td>";
    $h.="<td class='center'></td>";
    $h.="<td></td>";
    $h.="</tr>";
    return $h;
}

/** Badge d'etat d'une politique lue par l'API. */
function rpz_api_badge(array $p):string{
    if(empty($p["enabled"])){
        return "<span class='awc-badge awc-badge-off'>{disabled}</span>";
    }
    if(isset($p["invalid"]) && (string)$p["invalid"]!==""){
        // Ligne ignoree a la compilation (ValidPolicyRow) : la raison en infobulle.
        return "<span class='awc-badge awc-badge-alert' title=\"".htmlspecialchars((string)$p["invalid"],ENT_QUOTES)."\">{invalid}</span>";
    }
    $status=(string)($p["status"] ?? "");
    if($status==="error"){return "<span class='awc-badge awc-badge-alert'>{error}</span>";}
    if($status==="pending"){return "<span class='awc-badge awc-badge-off'>{inactive}</span>";}
    return "<span class='awc-badge awc-badge-on'>{active2}</span>";
}

/**
 * Une politique lue par GET /unbound/api/rpz. $pos/$count : position parmi
 * les politiques ordinaires (fleches) ; 0 pour la liste blanche reservee.
 */
function rpz_policy_row(array $p,$function,$pos,$count):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $canEdit=rpz_is_admin() && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==0;
    $ID=intval($p["id"] ?? 0);
    $kind=(string)($p["kind"] ?? "url");
    $action=(string)($p["action"] ?? "");
    $source=trim((string)($p["source"] ?? ""));
    $enabled=empty($p["enabled"])?0:1;
    $reserved=!empty($p["reserved"]);
    // Une URL de liste peut porter une cle : en clair pour un administrateur DNS
    // seulement (il la modifie), masquee pour toute autre session.
    $redact=!rpz_is_admin();
    if($redact){
        $source=unbound_page::redact_url($source);
        foreach(array("last_error","invalid") as $k){ if(isset($p[$k])){$p[$k]=unbound_page::redact_urls($p[$k]);} }
    }

    $Types=array("url"=>"URL / {download}","axfr"=>"{dns_query}","local"=>"{whitelists}");
    $meta=isset($Types[$kind])?$Types[$kind]:"&mdash;";
    if($source!==""){$meta.=" &middot; <span class='awc-mono' style='word-break:break-all'>".htmlspecialchars($source)."</span>";}
    if($kind==="axfr"){$meta.=" &middot; {rpz_axfr_short}";}
    if($reserved){$meta.=" &middot; {rpz_whitelist_reserved}";}
    if(isset($p["last_error"]) && trim((string)$p["last_error"])!==""){
        $meta.="<div class='awc-meta' style='color:#9f1239'>".htmlspecialchars((string)$p["last_error"])."</div>";
    }
    if(isset($p["invalid"]) && (string)$p["invalid"]!==""){
        $meta.="<div class='awc-meta' style='color:#9f1239'>".htmlspecialchars((string)$p["invalid"])."</div>";
    }

    if(!$reserved){
        $lbl=rpz_limits_label($p);
        if($lbl!==""){$meta.="<div class='awc-meta'>$lbl</div>";}
    }

    $actions=rpz_actions();
    $action_text=isset($actions[$action])?$actions[$action]:"<span class='awc-none'>&mdash;</span>";
    if($action==="cname"){
        $action_text.="<div class='awc-meta'>".htmlspecialchars((string)($p["cname"] ?? ""))."</div>";
    }

    $items="<span class='awc-none'>&mdash;</span>";
    if(($p["status"] ?? "")==="ok" || $reserved){
        $items="<strong>".$tpl->FormatNumber(intval($p["items"] ?? 0))."</strong>";
    }

    $off=($enabled==0);
    $name=htmlspecialchars((string)($p["name"] ?? ""));
    $nameHtml=$name;
    $enabled_ico="";
    $acts=array();
    if(!$reserved){
        $nameHtml="<a href=\"javascript:blur();\" onclick=\"Loadjs('$page?policy-js=$ID&function=$function')\">$name</a>";
        $want=($enabled==1)?"disable":"enable";
        $enabled_ico=$tpl->icon_check($enabled,"Loadjs('$page?enable=$ID&want=$want&function=$function');",null,"AsDnsAdministrator");
        if($canEdit){
            if($enabled==1){
                $acts[]="<a class='awc-act awc-act-go' title='{download}' href=\"javascript:blur();\" onclick=\"Loadjs('$page?run-js=$ID&function=$function')\"><i class='fa-solid fa-cloud-arrow-down'></i></a>";
            }
            if($pos>1){
                $acts[]="<a class='awc-act' title='{up}' href=\"javascript:blur();\" onclick=\"Loadjs('$page?policy-move=$ID&from=$pos&to=".($pos-1)."&function=$function')\"><i class='fa-solid fa-arrow-up'></i></a>";
            }
            if($pos<$count){
                $acts[]="<a class='awc-act' title='{down}' href=\"javascript:blur();\" onclick=\"Loadjs('$page?policy-move=$ID&from=$pos&to=".($pos+1)."&function=$function')\"><i class='fa-solid fa-arrow-down'></i></a>";
            }
            $acts[]="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" onclick=\"Loadjs('$page?delete-js=$ID&function=$function')\"><i class='fa-solid fa-trash-can'></i></a>";
        }
    }else{
        $nameHtml="<a href=\"javascript:blur();\" onclick=\"Loadjs('$page?policy-js=$ID&function=$function')\">$name</a>";
    }

    $ico=$reserved?"fa-solid fa-circle-check":"fa-solid fa-shield-halved";
    $h ="<tr id='rpz-row-$ID'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span id='rpz-ico-$ID' class='awc-ico".($off?" awc-ico-off":"")."'><i class='$ico'></i></span>";
    $h.="<span><span id='rpz-name-$ID' class='awc-name".($off?" awc-name-off":"")."'>$nameHtml</span>";
    $h.="<div class='awc-meta'>$meta</div></span></div></td>";
    $h.="<td class='awc-nowrap'>$action_text</td>";
    $h.="<td class='awc-right awc-nowrap'>$items</td>";
    $h.="<td class='center awc-nowrap'><span id='status-ico-$ID'>".rpz_api_badge($p)."</span></td>";
    $h.="<td class='center' style='width:1%'>$enabled_ico</td>";
    $h.="<td><div class='awc-acts'>".@implode("",$acts)."</div></td>";
    $h.="</tr>";
    return $h;
}

/** Le terme de recherche (jokers *) sur le nom, la source et la cible CNAME. */
function rpz_matches(array $p,$search):bool{
    $search=trim((string)$search);
    if($search===""||$search==="*"){return true;}
    $re="#".str_replace("\\*",".*",preg_quote($search,"#"))."#i";
    foreach(array("name","source","cname","description") as $k){
        if(isset($p[$k]) && preg_match($re,(string)$p[$k])){return true;}
    }
    return false;
}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=rpz_function();

    $TINY_ARRAY["TITLE"]="{POLICIES_ZONES}";
    $TINY_ARRAY["ICO"]="fa-solid fa-shield";
    $TINY_ARRAY["EXPL"]="{RPZ_EXPLAIN}";
    $TINY_ARRAY["URL"]="rpz-local-domains";
    $TINY_ARRAY["BUTTONS"]=btns();
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Lecture par l'API : etat effectif, liste blanche reservee, lignes
    // ignorees (invalid), politiques axfr en lecture seule.
    $err="";
    $data=rpz_api_list(new unbound_api(),$err);
    if($data===null){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{POLICIES_ZONES}||".htmlspecialchars($tpl->javascript_parse_text($err,0,true))))
            ."<script>$jstiny\nNoSpinner();</script>";
        return;
    }

    $html=array();
    if(empty($data["effective"])){
        $why=isset($data["effective_reason"])?(string)$data["effective_reason"]:"";
        $html[]=$tpl->div_warning("{rpz_not_effective}||".htmlspecialchars($why));
    }

    $rows=array();
    $RPZServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceEnabled"));
    if($RPZServiceEnabled==1){
        $RPZServiceClientError=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RPZServiceClientError");
        if(strlen($RPZServiceClientError)>1){
            // Ce message vient du demon et partait BRUT dans le HTML.
            $rows[]=rpz_service_row($RPZServiceClientError,"<span class='awc-badge awc-badge-alert'>{error}</span>");
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/rpzclient/status"));
        // property_exists(null,...) est une TypeError sous PHP 8: le demon
        // injoignable tuait la page.
        if(is_object($json) && property_exists($json,"Info") && is_array($json->Info)) {
            foreach ($json->Info as $status) {
                $badge="<span class='awc-badge awc-badge-off'>{inactive2}</span>";
                if(intval($status->Active ?? 0)==1){
                    $badge="<span class='awc-badge awc-badge-on'>{active2}</span>";
                }
                $rows[]=rpz_service_row($status->ZoneName ?? "",$badge);
            }
        }
    }

    $search=(string)($_GET["search"] ?? "");
    $ordinary=rpz_api_ordinary($data);
    $count=count($ordinary);
    $pos=0;
    foreach($data["policies"] as $p){
        if(!is_array($p)){continue;}
        $reserved=!empty($p["reserved"]);
        if(!$reserved){$pos++;}
        if(!rpz_matches($p,$search)){continue;}
        $rows[]=rpz_policy_row($p,$function,$reserved?0:$pos,$count);
    }

	// L'id table-rpz-policies est conserve: un id de tableau est une sentinelle
	// dans cette console et le perdre ne produit aucune erreur, seulement du
	// silence.
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table id='table-rpz-policies' class='awc-tbl'>";
	$html[]="<thead><tr>";
	$html[]="<th>{rulename}</th>";
	$html[]="<th>{policy}</th>";
	$html[]="<th class='awc-right'>{items}</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="<th class='center'>{enable}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead>";
	$html[]="<tbody id='rpz-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} ".count($rows)." {items}</span>";
	$html[]="<span class='awc-pg' id='rpz-pager'></span>";
	$html[]="</div></div></div>";

    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body lit toute accolade JavaScript comme un {token}.
    // ICON_SCRIPTS_COMPILE() est requis: ce tableau porte des icon_check().
    $script="<script>\n$jstiny\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"rpz-rows","rpz-pager");
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function DistanceInMns($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}

function isPdnsError($domain_id):string{
    VERBOSE(__FUNCTION__,__LINE__);
    // Pentest 2026-11-04 : domain_id etait concatene dans la requete.
    $domain_id=intval($domain_id);
    $q          = new mysql_pdns();
    $tpl        = new template_admin();

    if($q->TABLE_EXISTS("pdnsutil_chkzones")) {
        $sql = "SELECT COUNT(*) AS tcount FROM pdnsutil_chkzones WHERE domain_id=$domain_id";
        $ligne2 = mysqli_fetch_array($q->QUERY_SQL($sql));


        if (!$q->ok) {
            return "<td style='width:1%' nowrap><i class=\"fas fa-exclamation-circle\"></i><span class='text-danger'>" . $tpl->td_href("MySQL Error", $q->mysql_error) . "</span></td>";
        }

        $zcount = $ligne2["tcount"];
        if ($zcount > 0) {
            return "<td style='width:1%' nowrap><a href=\"javascript:blur();\" 
						OnClick=\"Loadjs('fw.pdns.domains.status.php?domain_id=$domain_id');\"
						><span class='label label-warning'>{$zcount} {errors}</span></a></td>";
        }
    }

    $q      = new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $ligne  = $q->mysqli_fetch_array("SELECT * FROM dnsinfos WHERE domain_id=$domain_id");

    if(!$q->ok){
        return "<td style='width:1%' nowrap><i class=\"fas fa-exclamation-circle\"></i><span class='text-danger'>".
            $tpl->td_href("MySQL Error",$q->mysql_error)."</span></td>";
    }



    if($ligne["renewdate"]>0){
        $renewdate=date("Y-m-d",$ligne["renewdate"]);
    }

    $zinfo=unserialize(base64_decode($ligne["zinfo"]));

    if(count($zinfo)>0) {
        foreach ($zinfo as $line) {
            $line = trim($line);
            if ($line == null) {
                continue;
            }
            VERBOSE($line,__LINE__);

            if (preg_match("#Backend launched with banner#i", $line)) {
                continue;
            }
            if (preg_match("#UeberBackend destructor#i", $line)) {
                continue;
            }
            if (preg_match("#Error:(.+)#", $line, $re)) {
                if (trim($re[1]) == null) {
                    continue;
                }
                return "<td style='width:1%' nowrap><span class='label label-danger'>{error}</span></a></td>";

            }
            if (preg_match("#[0-9]+\s+Error(.+)#i", $line)) {
                if (trim($re[1]) == null) {
                    continue;
                }
                return "<td style='width:1%' nowrap><span class='label label-danger'>{error}</span></a></td>";

            }
        }
    }

    return "<td style='width:1%' nowrap><span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;OK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></a></td>";
}


?>