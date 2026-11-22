<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Client unique des routes /unbound/api/* d'articarest (spec 019 lot 4c).
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
// Verrou d'une politique Meta dnscache : collection domains (DNS dynamique et TSIG restent libres).
include_once(dirname(__FILE__)."/ressources/class.unbound.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){main();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
if(isset($_GET["newdomain-js"])){newdomain_js();exit;}
if(isset($_GET["new-domain-popup"])){newdomain_popup();exit;}
if(isset($_POST["newdomain"])){newdomain_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){domain_delete();exit;}
// Bascules : *-js (GET) ne rend que du JavaScript, l'ecriture est un POST.
// « enable-ddns » est l'ancien nom de la bascule DDNS : il ne fait plus
// qu'emettre ce JavaScript, plus aucune ecriture sur un GET.
if(isset($_GET["enable-ddns"])){ddns_js(intval($_GET["enable-ddns"]));exit;}
if(isset($_GET["ddns-js"])){ddns_js(intval($_GET["ddns-js"]));exit;}
if(isset($_POST["ddns-toggle"])){ddns_toggle_save();exit;}
if(isset($_GET["enable-js"])){domain_enable_js();exit;}
if(isset($_POST["domain-toggle"])){domain_toggle_save();exit;}
if(isset($_GET["domain-js"])){domain_js();exit;}
if(isset($_GET["domain-popup"])){domain_popup();exit;}
if(isset($_POST["domainid"])){domain_save();exit;}
if(isset($_GET["ddns-agent-status"])){ddns_status();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["service-popup"])){service_popup();exit;}
if(isset($_POST["DDNSInterface"])){service_save();exit;}
// secretkey-js/-start/-popup retires : la fenetre « generer » lisait
// /crypt/tsigkeys et AFFICHAIT des secrets dans le navigateur. La cle est
// desormais generee par articarest (/unbound/api/domains/{id}/tsig/rotate) et
// ne quitte jamais le demon : la console n'en montre que l'empreinte.
page();

/** Algorithmes TSIG acceptes (= tsigKeyBytes d'articarest). */
function uzone_algorithms():array{
    return array("HMAC-MD5","HMAC-SHA1","HMAC-SHA224","HMAC-SHA256","HMAC-SHA384","HMAC-SHA512");
}

/** « hmac-sha256. » -> « HMAC-SHA256 » ; "" si inconnu (= normAlgo d'articarest). */
function uzone_norm_algo($a):string{
    $a=strtoupper(rtrim(trim((string)$a),"."));
    if(substr($a,-16)===".SIG-ALG.REG.INT"){$a=substr($a,0,-16);}
    return in_array($a,uzone_algorithms(),true)?$a:"";
}

/** Fonction de rafraichissement de search_block, filtree (part dans du JavaScript). */
function uzone_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}
function uzone_reload_call($function):string{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);
    return ($function!=="")?"$function();":"if(typeof UzoneReload==='function'){UzoneReload();}";
}
/** Le JavaScript qui montre, sans bloquer, les constats gardes en session. */
function uzone_notices_call():string{
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
 * Le domaine $id tel que le publie GET /unbound/api/domains : nom, cle (nom,
 * algorithme, empreinte), accord avec le DHCP, agent DDNS — jamais le secret.
 * null si absent ; $err recoit le message d'une lecture en echec.
 */
function uzone_api_domain($api,$id,&$err){
    $err="";
    $r=$api->get("/unbound/api/domains");
    if(!$r["ok"]){$err=unbound_api::error_message($r);return null;}
    foreach(uzone_api_list($r) as $d){
        if(intval($d["id"])==intval($id)){return $d;}
    }
    $err="{error}: id";
    return null;
}
function uzone_api_list(array $r):array{
    $out=array();
    $rows=(is_array($r["data"]) && isset($r["data"]["domains"]) && is_array($r["data"]["domains"]))?$r["data"]["domains"]:array();
    foreach($rows as $d){ if(is_array($d) && isset($d["id"])){$out[]=$d;} }
    return $out;
}

/**
 * JavaScript d'une action de ligne : POST $field=$id, popup en cas d'erreur
 * (texte deja echappe par post_error), puis rafraichissement et constats.
 */
function uzone_post_js($field,$id,$function):string{
    $page=CurrentPageName();
    $field=preg_replace('/[^a-z-]/','',(string)$field);
    $id=intval($id);
    $refresh=uzone_reload_call($function);
    $notices=uzone_notices_call();
    $t=time().$id;
    $js=array();
    $js[]="var xUzone$t=function(obj){";
    $js[]="\tvar res=obj.responseText;";
    $js[]="\tif(res.length>3){";
    // post_error() : HTML deja echappe ; tout autre corps (page 504, notice PHP) en texte.
    $js[]="\t\t".unbound_api::swal_js("res");
    $js[]="\t}";
    $js[]="\t$refresh";
    $js[]="\t$notices";
    $js[]="};";
    $js[]="var XHR=new XHRConnection();";
    $js[]="XHR.appendData('$field','$id');";
    $js[]="XHR.sendAndLoad('$page','POST',xUzone$t);";
    return implode("\n",$js);
}

function newdomain_js(){
	if(unbound_meta_refuse_js_collection("domains")){return;}
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=uzone_function();
	$title=$tpl->javascript_parse_text("{new_domain}");
	$tpl->js_dialog($title, "$page?new-domain-popup=yes&function=$function");
}
function service_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=uzone_function();
    // Le token porte un souligne, pas un tiret: "{service-parameters}" n'existe
    // pas et s'affichait tel quel dans le titre de la fenetre.
    $title=$tpl->javascript_parse_text("{service_parameters}");
    $tpl->js_dialog($title, "$page?service-popup=yes&function=$function");
}
/**
 * Ecoute de l'agent DDNS : aucune route /unbound/api/* ne la couvre. Ancien
 * chemin, sous privilege et refus esclave ; seules les deux cles du formulaire
 * sont ecrites (SAVE_POSTs ecrivait toute cle postee dans la configuration).
 */
function service_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!uzone_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $refusal=(new unbound_api())->legacy_write_refusal();
    if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
    $iface=trim((string)($_POST["DDNSInterface"] ?? ""));
    if($iface!=="" && !preg_match('/^[A-Za-z0-9._:@-]{1,32}$/',$iface)){
        echo $tpl->post_error("{listen_interface}: {wrong_value}");
        return false;
    }
    $port=intval($_POST["DDNSPort"] ?? 8053);
    if($port<1 || $port>65535){
        echo $tpl->post_error("{listen_port}: {wrong_value}");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DDNSInterface",$iface);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DDNSPort",$port);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ddns/agent/restart");
    return admin_tracks("DDNS agent listens on ".($iface===""?"all interfaces":$iface).":$port");
}
function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Table de mise en page remplacee par une grille flex: la tuile de service
    // (SERVICE_STATUS) fait 335px et la colonne en reservait 350 en dur; la
    // mise en page passe desormais a la ligne sur un ecran etroit.
    $html=array();
    $html[]="<div class='uzn-top'>";
    $html[]="<div class='uzn-side'><div id='ddns-agent-status'></div></div>";
    $html[]="<div class='uzn-main'>".$tpl->search_block($page)."</div>";
    $html[]="</div>";
    // La feuille de style est concatenee APRES le parsing: _ENGINE_parse_body
    // lirait chaque corps de regle CSS comme un {token}.
    echo uzone_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

/**
 * CSS propre a la page: colonnes de tete et teinte de l'icone d'identite selon
 * l'etat DDNS. Le reste vient de awc_table_style(), partage.
 */
function uzone_style():string{
    return "<style>
.uzn-top{display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap}
.uzn-side{width:350px;flex:0 0 auto}
.uzn-main{flex:1 1 420px;min-width:0}
.uzn-ok{color:#006a62;font-weight:600}
.uzn-bad{color:#a01c1c;font-weight:600}
</style>";
}
function service_popup(){
    $tpl=new template_admin();
    $function=uzone_function();
    $DDNSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DDNSPort"));
    $DDNSInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DDNSInterface"));
    $form[]=$tpl->field_interfaces("DDNSInterface","{listen_interface}",$DDNSInterface);
    if($DDNSPort==0){
        $DDNSPort=8053;
    }
    $form[]=$tpl->field_numeric("DDNSPort","{listen_port}",$DDNSPort);
    $html[]=$tpl->form_outside("",$form,null,"{apply}",
        "$function();BootstrapDialog1.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}

/** Texte de l'accord avec le DHCP : kea_key_match absent = aucune regle DDNS du DHCP ne couvre le domaine. */
function uzone_kea_label(array $d):string{
    if(!array_key_exists("kea_key_match",$d) || $d["kea_key_match"]===null){return "{unbound_tsig_kea_none}";}
    return ($d["kea_key_match"]===true)?'<span class="uzn-ok">{unbound_tsig_kea_match}</span>'
        :'<span class="uzn-bad">{unbound_tsig_kea_mismatch}</span>';
}
function uzone_agent_label(array $d):string{
    if(!array_key_exists("ddns_agent_running",$d) || $d["ddns_agent_running"]===null){return "";}
    return ($d["ddns_agent_running"]===true)?'<span class="uzn-ok">{running}</span>':'<span class="uzn-bad">{stopped}</span>';
}
function uzone_fp_label(array $d):string{
    if(empty($d["secret_set"])){return "{not_defined}";}
    $fp=preg_replace('/[^a-f0-9]/','',(string)($d["secret_fingerprint"] ?? ""));
    return '<span style="font-family:monospace">'.$fp.'</span>';
}

function domain_popup(){
    $tpl=new template_admin();
    $function=uzone_function();
    $id=intval($_GET["domain-popup"]);
    $d=uzone_api_domain(new unbound_api(),$id,$err);
    if($d===null){
        echo $tpl->div_error($tpl->_ENGINE_parse_body(htmlspecialchars($err)));
        return false;
    }
    // Rien de ce formulaire ne lit ni ne montre le secret : seulement son
    // empreinte, l'accord avec la cle du DHCP et l'etat de l'agent DDNS.
    $form[]=$tpl->field_hidden("domainid",$id);
    // Domaines tenus par une politique Meta : la description l'est aussi (MetaDomain.description) ;
    // DNS dynamique, nom et cle TSIG restent libres.
    $form[]=unbound_meta_collection_locked("domains")?unbound_meta_field_ro($tpl,"explainthis","{description}",(string)$d["explain"]):$tpl->field_text("explainthis","{description}",(string)$d["explain"]);
    $form[]=$tpl->field_checkbox("allowddnsupdate","DDNS",empty($d["allow_ddns_update"])?0:1);
    $form[]=$tpl->field_text("tsigkeyname","{key_name}",(string)$d["key_name"]);
    $form[]=$tpl->field_info("tsig_fp","{fingerprint}",uzone_fp_label($d));
    $form[]=$tpl->field_info("tsig_kea","{unbound_tsig_dhcp_key}",uzone_kea_label($d));
    $agent=uzone_agent_label($d);
    if($agent!==""){$form[]=$tpl->field_info("tsig_agent","{APP_DDNS_AGENT}",$agent);}
    $algo=uzone_norm_algo($d["algorithm"]);
    if($algo===""){$algo="HMAC-SHA256";}
    $HMAC=array();
    foreach(uzone_algorithms() as $a){$HMAC[$a]=$a;}
    $form[]=$tpl->field_array_hash($HMAC,"algorithm","{algorithm}",$algo);
    $form[]=$tpl->field_checkbox("generate_key","{generate_key}",0,false,"{unbound_tsig_generate_explain}");
    $form[]=$tpl->field_password("secretkey","{tsig_secret}","",false,"{unbound_tsig_external_key_explain}");
    $html[]=$tpl->form_outside("",$form,null,"{apply}",
        uzone_reload_call($function)."BootstrapDialog1.close();".uzone_notices_call(),"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

/**
 * Enregistrement du formulaire d'un domaine, en trois etapes dans cet ordre :
 *  1. description : aucune route de l'API -> ancien chemin, requete liee ;
 *  2. cle : « generer » = POST /tsig/rotate (articarest genere) ; une cle
 *     externe saisie = POST /tsig {secret, algorithm, key_name?}. Dans les deux
 *     cas articarest aligne la cle du DHCP quand le lien est univoque, relance
 *     l'agent, prouve, et ne rend que l'empreinte. Le secret ne passe plus par
 *     SQLite cote PHP : une erreur SQL en ecrivait la trace (arguments compris)
 *     sous ressources/logs/sql_errors ;
 *  3. DNS dynamique et nom de cle : POST /ddns, seulement s'ils changent
 *     (articarest refuse un changement nul).
 * La cle passe avant le DNS dynamique : l'activer exige une cle.
 */
function domain_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!uzone_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $id=intval($_POST["domainid"] ?? 0);
    if($id<=0){ echo $tpl->post_error("{error}: id");return false;}
    $api=new unbound_api();
    $cur=uzone_api_domain($api,$id,$err);
    if($cur===null){ echo $tpl->post_error($err);return false;}
    $domain=(string)$cur["domain"];

    $want=intval($_POST["allowddnsupdate"] ?? 0)==1;
    // Champ absent (description en lecture seule sous verrou Meta) : inchangee.
    $explain=isset($_POST["explainthis"])?trim((string)$_POST["explainthis"]):(string)$cur["explain"];
    $keyname=trim((string)($_POST["tsigkeyname"] ?? ""));
    $generate=intval($_POST["generate_key"] ?? 0)==1;
    $secret=trim((string)($_POST["secretkey"] ?? ""));
    $algorithm=uzone_norm_algo($_POST["algorithm"] ?? "");

    if(strlen($explain)>255 || preg_match('/[\x00-\x1f\x7f]/',$explain)){
        echo $tpl->post_error("{description}: {wrong_value}");return false;
    }
    if($keyname!=="" && !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,62}$/',$keyname)){
        echo $tpl->post_error("{key_name}: {wrong_value}");return false;
    }
    if($algorithm===""){ echo $tpl->post_error("{algorithm}: {wrong_value}");return false;}
    // Le secret est recopie entre guillemets dans la configuration de l'agent
    // DDNS (key "x" { secret "y"; }) : base64 strict.
    if($secret!=="" && !preg_match('#^[A-Za-z0-9+/]+={0,2}$#',$secret)){
        echo $tpl->post_error("{tsig_secret}: {wrong_value}");return false;
    }
    if($generate && $secret!==""){ echo $tpl->post_error("{unbound_tsig_generate_or_external}");return false;}
    $curAlgo=uzone_norm_algo($cur["algorithm"]);
    if(!$generate && $secret==="" && !empty($cur["secret_set"]) && $curAlgo!=="" && $curAlgo!==$algorithm){
        echo $tpl->post_error("{unbound_tsig_algo_needs_key}");return false;
    }

    $explainChanged=($explain!==(string)$cur["explain"]);
    $keyChanged=($keyname!=="" && $keyname!==(string)$cur["key_name"]);
    if($explainChanged){
        $meta=unbound_meta_refuse_collection("domains");
        if($meta!==""){ echo $meta;return false;}
        $refusal=$api->legacy_write_refusal();
        if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
    }
    $done=false;
    $fail=function($msg) use ($tpl,&$done){
        echo $tpl->post_error(($done?"{unbound_domain_partly_saved}\n":"").$msg);
        return false;
    };

    // 1. description
    if($explainChanged){
        $q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
        $q->QUERY_SQL_PARAMS("UPDATE localdomains SET explainthis=? WHERE id=?",array($explain,$id));
        if(!$q->ok){ return $fail($q->mysql_error);}
        admin_tracks("Edit the description of local DNS domain $domain");
        $done=true;
    }

    // 2. cle TSIG
    if($generate){
        $r=$api->post("/unbound/api/domains/$id/tsig/rotate",array("algorithm"=>$algorithm));
        if(!$r["ok"]){ return $fail(unbound_api::error_message($r));}
        unbound_api::queue($r);
        $fp=preg_replace('/[^a-f0-9]/','',(string)(is_array($r["data"]) && isset($r["data"]["secret_fingerprint"])?$r["data"]["secret_fingerprint"]:""));
        unbound_api::queue_texts(array("{unbound_tsig_new_fingerprint}: $fp"));
        admin_tracks("Generate a new TSIG key ($algorithm, fingerprint $fp) for local DNS domain $domain");
        $done=true;
    }elseif($secret!==""){
        // Seul le corps de la requete porte le secret : unbound_api ne journalise
        // jamais un corps, et articarest expurge ses messages (envScrub). Le
        // nom de cle change voyage avec la cle (une seule preuve).
        $body=array("secret"=>$secret,"algorithm"=>$algorithm);
        if($keyChanged){$body["key_name"]=$keyname;}
        $r=$api->post("/unbound/api/domains/$id/tsig",$body);
        if(!$r["ok"]){ return $fail(unbound_api::error_message($r));}
        if($keyChanged){$keyChanged=false;}
        unbound_api::queue($r);
        $fp=preg_replace('/[^a-f0-9]/','',(string)(is_array($r["data"]) && isset($r["data"]["secret_fingerprint"])?$r["data"]["secret_fingerprint"]:""));
        unbound_api::queue_texts(array("{unbound_tsig_external_applied}: $fp"));
        admin_tracks("Set an external TSIG key ($algorithm, fingerprint $fp) for local DNS domain $domain");
        $done=true;
    }

    // 3. DNS dynamique et nom de cle
    if($want!==!empty($cur["allow_ddns_update"]) || $keyChanged){
        $body=array("enabled"=>$want);
        if($keyChanged){$body["key_name"]=$keyname;}
        $r=$api->post("/unbound/api/domains/$id/ddns",$body);
        if(!$r["ok"]){ return $fail(unbound_api::error_message($r));}
        unbound_api::queue($r);
        admin_tracks(($want?"Enable":"Disable")." dynamic DNS updates of local DNS domain $domain".($keyChanged?" (key name $keyname)":""));
    }
    return true;
}

/**
 * Ecritures de la page: administrateur DNS seulement (le bouton masque ne
 * suffit pas). Le refus d'un esclave du cluster vient d'articarest (409, avec
 * l'adresse et la console du maitre) ou, pour les chemins historiques, de
 * unbound_api::legacy_write_refusal().
 */
function uzone_can_edit():bool{
    $tpl=new template_admin();
    return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}

/** Bascule DDNS d'un domaine : *-js (GET) n'emet que du JavaScript -> POST ddns-toggle. */
function ddns_js($id){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!uzone_can_edit()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	echo uzone_post_js("ddns-toggle",$id,uzone_function());
}

/**
 * L'etat est relu par l'API plutot que transmis : la ligne affichee peut dater.
 * articarest refuse d'activer sans cle reelle, sur un domaine desactive, sans
 * agent DDNS, ou quand le nom de cle est pris par un autre domaine.
 */
function ddns_toggle_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!uzone_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $id=intval($_POST["ddns-toggle"]);
    if($id<=0){ echo $tpl->post_error("{error}: id");return false;}
    $api=new unbound_api();
    $d=uzone_api_domain($api,$id,$err);
    if($d===null){ echo $tpl->post_error($err);return false;}
    $want=empty($d["allow_ddns_update"]);
    $r=$api->post("/unbound/api/domains/$id/ddns",array("enabled"=>$want));
    if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
    unbound_api::queue($r);
    return admin_tracks(($want?"Enable":"Disable")." dynamic DNS updates of local DNS domain ".$d["domain"]);
}

/** Activation d'un domaine : *-js (GET) n'emet que du JavaScript -> POST domain-toggle. */
function domain_enable_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!uzone_can_edit()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	if(unbound_meta_collection_locked("domains")){$tpl->js_error("{unbound_meta_managed_locked}");return;}
	echo uzone_post_js("domain-toggle",intval($_GET["enable-js"]),uzone_function());
}
function domain_toggle_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!uzone_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $meta=unbound_meta_refuse_collection("domains");
    if($meta!==""){ echo $meta;return false;}
    $id=intval($_POST["domain-toggle"]);
    if($id<=0){ echo $tpl->post_error("{error}: id");return false;}
    $api=new unbound_api();
    $d=uzone_api_domain($api,$id,$err);
    if($d===null){ echo $tpl->post_error($err);return false;}
    $action=empty($d["enabled"])?"enable":"disable";
    $r=$api->post("/unbound/api/domains/$id/$action");
    if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
    unbound_api::queue($r);
    return admin_tracks("Local DNS domain ".$d["domain"].": $action");
}

/**
 * Confirmation de suppression d'un domaine local.
 *
 * articarest refuse (409) de supprimer un domaine qui porte encore des
 * enregistrements : auparavant la console les supprimait en silence. La page le
 * dit des la confirmation, avec le nombre que compte articarest lui-meme.
 */
function delete_js(){
	$tpl=new template_admin();
	$id=intval($_GET["delete-js"]);
	$function=uzone_function();
	header("content-type: application/x-javascript");
	if(!uzone_can_edit()){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
	if(unbound_meta_collection_locked("domains")){return $tpl->js_error("{unbound_meta_managed_locked}");}
	$d=uzone_api_domain(new unbound_api(),$id,$err);
	if($d===null){return $tpl->js_error($err);}
	$domain=htmlspecialchars((string)$d["domain"]);
	$records=intval($d["records"] ?? 0);
	if($records>0){
		$msg=$tpl->_ENGINE_parse_body("{unbound_domain_has_records}");
		return $tpl->js_error(str_replace(array("%d","%c"),array($domain,strval($records)),$msg));
	}
	// js_confirm_delete n'execute $jsafter que si la reponse du POST est VIDE.
	$tpl->js_confirm_delete($domain,"delete",$id,uzone_reload_call($function).uzone_notices_call());
	return true;
}

function domain_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=uzone_function();
    $id=intval($_GET["domain-js"]);
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $ligne=$q->mysqli_fetch_array("SELECT domain FROM localdomains WHERE id=$id");
    $title=is_array($ligne)?htmlspecialchars((string)$ligne["domain"]):"#$id";
    return $tpl->js_dialog($title, "$page?domain-popup=$id&function=$function");
}

function newdomain_popup():bool{
		$tpl=new template_admin();
        $function=uzone_function();
		$form[]=$tpl->field_text("newdomain", "{domain}", null);
        $form[]=$tpl->field_text("explainthis", "{description}", null);
		// form_outside() ignore son 3e argument (explication) : elle est montree par div_explain (skill §11).
		$html[]=$tpl->div_explain("{unbound_domain_legacy_path}");
		$html[]=$tpl->form_outside("{new_domain}", @implode("\n", $form),"{unbound_domain_legacy_path}","{add}",
				"BootstrapDialog1.close();".uzone_reload_call($function).uzone_notices_call(),"AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body($html);
        return true;
}
/** Un nom a une seule etiquette (« lan », « home ») : l'API exige deux etiquettes. */
function uzone_single_label($domain):bool{
    // Au moins une lettre : un nom tout en chiffres (« 10 ») n'est pas un nom DNS de premier niveau.
    $d=rtrim((string)$domain,".");
    return (bool)preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/',$d) && (bool)preg_match('/[a-z]/',$d);
}
/**
 * Creation par POST /unbound/api/domains : articarest valide le nom (deux
 * etiquettes au moins), refuse un doublon, applique sous unbound-checkconf
 * complet et prouve. L'ancienne version inserait en base sans rien appliquer.
 * Un nom a une seule etiquette, que l'API refuse, garde l'ancien chemin : refus
 * esclave, doublon refuse, INSERT lie, puis reconstruction des zones. Ensuite
 * activation, DNS dynamique, cle et suppression de ce domaine passent par
 * l'API, qui ne revalide pas le nom.
 */
function newdomain_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!uzone_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // Les deux chemins (API et INSERT historique) : le second, le daemon ne le voit pas.
    $meta=unbound_meta_refuse_collection("domains");
    if($meta!==""){ echo $meta;return false;}
    $domain=trim(strtolower(url_decode_special_tool((string)($_POST["newdomain"] ?? ""))));
    $explain=trim((string)($_POST["explainthis"] ?? ""));
    if($domain===""){ echo $tpl->post_error("{no_data}");return false;}
    if(uzone_single_label($domain)){
        return newdomain_save_legacy($tpl,rtrim($domain,"."),$explain);
    }
    $r=(new unbound_api())->post("/unbound/api/domains",array("domain"=>$domain,"explain"=>$explain));
    if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
    unbound_api::queue($r);
    return admin_tracks("Create a new DNS Cache local domain $domain");
}
function newdomain_save_legacy($tpl,$domain,$explain):bool{
    if(strlen($explain)>255 || preg_match('/[\x00-\x1f\x7f]/',$explain)){
        echo $tpl->post_error("{description}: {wrong_value}");return false;
    }
    $refusal=(new unbound_api())->legacy_write_refusal();
    if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $ex=$q->QUERY_SQL_PARAMS("SELECT id FROM localdomains WHERE domain=?",array($domain));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
    if(is_array($ex) && count($ex)>0){ echo $tpl->post_error("$domain: {already_exists}");return false;}
    $q->QUERY_SQL_PARAMS("INSERT INTO localdomains (domain,explainthis,enabled) VALUES (?,?,1)",array($domain,$explain));
    if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/build/zones");
    return admin_tracks("Create a new DNS Cache local domain $domain (single label, former path)");
}
/**
 * Suppression par DELETE /unbound/api/domains/{id} : refusee (409) tant que le
 * domaine porte des enregistrements, ce que delete_js() dit deja. articarest
 * garde un jeton de restauration (ligne, cle et zone dynamique) ; la console
 * n'en fait rien.
 *
 * Convention: on n'echo QUE l'erreur — une reponse vide vaut succes pour
 * js_confirm_delete, qui declenche alors le rafraichissement.
 */
function domain_delete(){
	$tpl=new template_admin();
	if(!uzone_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$meta=unbound_meta_refuse_collection("domains");
	if($meta!==""){ echo $meta;return false;}
	$id=intval($_POST["delete"]);
	if($id<=0){ echo $tpl->post_error("{error}: id");return false;}
	$r=(new unbound_api())->delete("/unbound/api/domains/$id");
	if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
	unbound_api::queue($r);
	$domain=(is_array($r["data"]) && isset($r["data"]["domain"]))?(string)$r["data"]["domain"]:"#$id";
	return admin_tracks("Removed local DNS domain $domain");
}

function title_extention():string{
    $Key="PowerDNSEnableClusterSlave";
    $keydate="PowerDNSClusterClientDate";
    $ClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Key));
    if($ClusterSlave==1){return "";}
    $ClusterClientDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($keydate));
    if($ClusterClientDate==0){return "";}
    $tpl=new template_admin();
    $text_date=$tpl->time_to_date($ClusterClientDate,true);
    return "&nbsp;<small>{last_sync}: $text_date</small>";

}

function page(){
    $page       = CurrentPageName();
	$tpl        = new template_admin();
	$title_add  = title_extention();


    $html=$tpl->page_header("{local_domains}$title_add","fab fab fa-soundcloud",
    "{local_domains_dns_explain}","$page?start=yes","dnscache-local-domains","progress-dnslocaldomains-restart",false,"table-unbound-domains");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function ddns_status():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();

    $data = $sock->REST_API("/ddns/agent/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $APP_DDNS_AGENT=$tpl->framework_buildjs(
        "/ddns/agent/restart","ddns-agent.service.progress",
        "ddns-agent.service.log","progress-dnslocaldomains-restart",null,null,null,"AsDnsAdministrator");

    echo $tpl->SERVICE_STATUS($ini, "APP_DDNS_AGENT",$APP_DDNS_AGENT);
    return true;
}

// $function est fourni par l'appelant (main(), qui l'a deja filtre): il file
// ensuite dans des OnClick sans echappement.
function btns($function=""){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $new_entry=$tpl->_ENGINE_parse_body("{new_domain}");
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);

    $jsrestart=$tpl->framework_buildjs("/unbound/build/zones",
            "unbound.zones.progress","unbound.zones.progress.log",
            "progress-dnslocaldomains-restart",null,null,null,"AsDnsAdministrator");

    $topbuttons=array();
    if($PowerDNSEnableClusterSlave==0){
        if(!unbound_meta_collection_locked("domains")){$topbuttons[] = array("Loadjs('$page?newdomain-js=yes&function=$function')",ico_plus,$new_entry);}
        $topbuttons[] = array("Loadjs('$page?service-js=yes&function=$function')",ico_params,"{service_parameters}");
    }
    $topbuttons[] = array($jsrestart,ico_save,"{reconfigure_service}");
    return  $tpl->table_buttons($topbuttons);

}

/**
 * Predicat SQL des enregistrements d'une zone, pour le comptage affiche quand
 * l'API est illisible. L'ancien "name LIKE '%domaine'" attrapait aussi
 * "faux-domaine.tld" (pas de point separateur).
 */
function uzone_records_where($q,$domain):string{
    $d=$q->sqlite_escape_string2($domain);
    return "(`name`='$d' OR `name` LIKE '%.$d')";
}

/**
 * Une zone locale: nom en identite (clic = edition, description en meta),
 * nombre d'enregistrements, activation, cle TSIG (nom, algorithme, empreinte,
 * accord avec le DHCP — jamais le secret), bascule DDNS, suppression.
 * $info = la ligne de GET /unbound/api/domains, null si l'API est illisible.
 */
// $metaLocked : domaines tenus par une politique Meta, activation et suppression inertes,
// DNS dynamique libre.
function uzone_row($tpl,$page,$function,$ligne,$items,$readonly,$info=null,$metaLocked=false):string{
    $ID=intval($ligne["id"]);
    $md=md5(serialize($ligne));
    $domain=(string)$ligne["domain"];
    $explain=(string)$ligne["explainthis"];
    $ddns=intval($ligne["allowddnsupdate"]);
    $enabled=isset($ligne["enabled"])?intval($ligne["enabled"]):1;
    $tsigkeyname=(string)$ligne["tsigkeyname"];
    $algorithm=(string)$ligne["algorithm"];
    if(is_array($info)){
        $items=intval($info["records"] ?? $items);
        $ddns=empty($info["allow_ddns_update"])?0:1;
        $enabled=empty($info["enabled"])?0:1;
    }

    $editJs="Loadjs('$page?domain-js=$ID&function=$function')";
    // Teinte pleine quand la zone accepte les mises a jour dynamiques, neutre
    // sinon. (Une classe dediee aurait ete inutile: la teinte "active" est
    // exactement celle de .awc-ico — la distinction doit venir de .awc-ico-off.)
    $icocls=$ddns?"awc-ico":"awc-ico awc-ico-off";

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='$icocls'><i class='fas fa-globe'></i></span>";
    $h.="<span><span class='awc-name'><a href=\"javascript:$editJs\">".htmlspecialchars($domain)."</a></span>";
    if(trim($explain)!==""){$h.="<div class='awc-meta'>".htmlspecialchars($explain)."</div>";}
    $h.="</span></div></td>";
    $h.="<td class='center'><span class='awc-tag'>".FormatNumber($items)."</span></td>";
    $h.="<td class='center'>".$tpl->icon_check($enabled,($readonly||$metaLocked)?null:"Loadjs('$page?enable-js=$ID&function=$function')",null,"AsDnsAdministrator")."</td>";
    $tsig="<span class='awc-none'>&mdash;</span>";
    if($tsigkeyname!==""){
        $tsig="<span style='font-family:monospace'>".htmlspecialchars($tsigkeyname)."</span>"
            ."<div class='awc-meta'>".htmlspecialchars($algorithm);
        if(is_array($info)){
            $tsig.=" &middot; ".uzone_fp_label($info);
            if(array_key_exists("kea_key_match",$info) && $info["kea_key_match"]!==null){$tsig.=" &middot; ".uzone_kea_label($info);}
        }
        $tsig.="</div>";
    }
    $h.="<td class='awc-mono'>$tsig</td>";
    $ddnsCell=$tpl->icon_check($ddns,$readonly?null:"Loadjs('$page?ddns-js=$ID&function=$function')",null,"AsDnsAdministrator");
    if(is_array($info) && $ddns==1){
        $agent=uzone_agent_label($info);
        if($agent!==""){$ddnsCell.="<div class='awc-meta'>$agent</div>";}
    }
    $h.="<td class='center'>$ddnsCell</td>";
    $h.="<td><div class='awc-acts'>";
    if(!$readonly && !$metaLocked){
        $h.=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&function=$function')","AsDnsAdministrator");
    }else{
        $h.=$tpl->icon_nothing();
    }
    $h.="</div></td>";
    $h.="</tr>";
    return $h;
}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
	// Whitelist obligatoire: $function part ensuite dans des OnClick et des
	// $jsafter sans echappement (XSS reflechi via ?function=...).
	$function=uzone_function();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	$metaLocked=unbound_meta_collection_locked("domains");

	// Le terme de recherche etait concatene tel quel dans le LIKE: une simple
	// quote suffisait a detourner la requete.
	$LIKE="";
	$search=trim((string)($_GET["search"] ?? ""));
	if($search!==""){
		$like=$q->sqlite_escape_string2(str_replace("*","%",$search));
		$LIKE="WHERE `domain` LIKE '%$like%'";
	}

	// La liste vient de la base (recherche, colonnes historiques) ; l'etat de
	// la cle (empreinte, accord avec le DHCP), de l'agent et le nombre
	// d'enregistrements viennent de l'API, qui ne publie jamais le secret.
	$sql="SELECT id,domain,explainthis,enabled,allowddnsupdate,tsigkeyname,algorithm FROM localdomains $LIKE ORDER BY domain";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<br>$sql");}
	if(!is_array($results)){$results=array();}

	$api=new unbound_api();
	$r=$api->get("/unbound/api/domains");
	$apiErr=$r["ok"]?"":unbound_api::error_message($r);
	$infos=array();
	foreach(uzone_api_list($r) as $d){$infos[intval($d["id"])]=$d;}

	$rows=array();
	foreach ($results as $ligne){
		$id=intval($ligne["id"]);
		$items=0;
		if(!isset($infos[$id])){
			$ligne2=$q->mysqli_fetch_array("SELECT COUNT(*) AS tcount FROM records WHERE ".uzone_records_where($q,$ligne["domain"]));
			$items=intval($ligne2["tcount"]);
		}
		$rows[]=uzone_row($tpl,$page,$function,$ligne,$items,$PowerDNSEnableClusterSlave==1,isset($infos[$id])?$infos[$id]:null,$metaLocked);
	}

	$html=array();
	$metaBanner=unbound_meta_banner($tpl,"domains");
	if($metaBanner!==""){$html[]=$metaBanner;}
	if($apiErr!==""){
		$html[]=$tpl->div_warning(htmlspecialchars($apiErr));
	}
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{domains}</th>";
	$html[]="<th class='center'>{items}</th>";
	$html[]="<th class='center'>{enabled}</th>";
	$html[]="<th>TSIG</th>";
	$html[]="<th class='center'>DDNS</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='uzn-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} ".count($rows)." {domains}</span>";
	$html[]="<span class='awc-pg' id='uzn-pager'></span>";
	$html[]="</div></div></div>";

	$title_add  = title_extention();
	$TINY_ARRAY["TITLE"]="{local_domains} $title_add";
	$TINY_ARRAY["ICO"]="fab fab fa-soundcloud";
	$TINY_ARRAY["EXPL"]="{local_domains_dns_explain}";
	$TINY_ARRAY["URL"]="dnscache-local-domains";
	$TINY_ARRAY["BUTTONS"]=btns($function);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$jj=$tpl->RefreshInterval_js("ddns-agent-status",$page,"ddns-agent-status=yes",3);
	// La fonction de rafraichissement generee par search_block est aliasee sur
	// un nom fixe: les mutations (bascule DDNS, suppression) rechargent ainsi le
	// tableau EN CONSERVANT le filtre de recherche courant.
	// Le <script> partait DANS $html, donc dans _ENGINE_parse_body(), dont la
	// regex #{(.+?)}# lit "{window.UzoneReload=...}" et "function(){}" comme des
	// {tokens}. Il est desormais concatene apres l'analyse, comme la feuille de
	// style. ICON_SCRIPTS_COMPILE() plutot que la propriete d'instance: elle
	// draine aussi $GLOBALS["TPL_ICON_SCRIPTS"], que icon_check() alimente en
	// parallele depuis uzone_row().
	$js=array();
	$js[]="NoSpinner();";
	$js[]=$tpl->ICON_SCRIPTS_COMPILE();
	if($function!=""){$js[]="if(typeof $function==='function'){window.UzoneReload=$function;}";}
	$js[]="window.UzoneReload=window.UzoneReload||function(){};";
	$js[]=$jj;
	$js[]=$jstiny;
	$script="<script>".@implode("\n",$js)."</script>";

	echo awc_table_style().uzone_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"uzn-rows","uzn-pager");
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function DistanceInMns($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}




?>
