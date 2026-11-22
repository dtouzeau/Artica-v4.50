<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Client unique des routes /unbound/api/* d'articarest (spec 019 lot 4c).
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
// Verrou d'une politique Meta dnscache : collection forward_zones, cles des domaines inconnus.
include_once(dirname(__FILE__)."/ressources/class.unbound.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
if(isset($_GET["unbound-unknown-domains-js"])){unbound_unknown_domains_js();exit;}
if(isset($_GET["unbound-unknown-domains-popup"])){unbound_unknown_domains_popup();exit;}
if(isset($_GET["unbound-unknown-domains-start"])){unbound_unknown_domains_start();exit;}
if(isset($_POST["UnboundUseSystemDNS"])){unbound_unknown_domains_save();exit;}
if(isset($_POST["DNS1"]) && !isset($_POST["multidomains"])){unbound_unknown_domains_save();exit;}
if(isset($_POST["DNS2"]) && !isset($_POST["multidomains"])){unbound_unknown_domains_save();exit;}
if(isset($_POST["DNS3"])){unbound_unknown_domains_save();exit;}
if(isset($_POST["DNS4"])){unbound_unknown_domains_save();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main2"])){main_table();exit;}
if(isset($_GET["fz-context"])){fz_context();exit;}
if(isset($_GET["add-rule-js"])){add_rule_js();exit;}
if(isset($_GET["add-rule-popup"])){add_rule_popup();exit;}
if(isset($_GET["dnssec-js"])){dnssec_js();exit;}
if(isset($_GET["dnssec-popup"])){dnssec_popup();exit;}

if(isset($_GET["multidomains-js"])){multidomains_js();exit;}
if(isset($_POST["multidomains"])){multidomains_save();exit;}
if(isset($_GET["multidomains-popup"])){multidomains_popup();exit;}



if(isset($_GET["transparent-js"])){transparent_js();exit;}
if(isset($_GET["transparent-popup"])){transparent_popup();exit;}

if(isset($_POST["DNSSEC"])){dnssec_save();exit;}
if(isset($_POST["TRANSPARENT"])){transparent_save();exit;}



if(isset($_GET["zone-id-js"])){zone_js();exit;}
if(isset($_GET["zone-id"])){zone();exit;}
if(isset($_POST["ID"])){zone_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete-zone"])){delete_zone();exit;}
// ?dns-forward-bts= renvoyait TEL QUEL le base64 fourni dans l URL: nimporte
// quel HTML/JavaScript etait ainsi servi par la console, sous son origine.
// Aucun appelant dans le depot — point d entree retire.
page();

/*
 * Ecritures (spec 019 lot 4c) :
 *  - une zone de transfert SIMPLE (kind « forward ») sur un cache DNS unbound
 *    passe par /unbound/api/forward-zones* : validation, unbound-checkconf
 *    complet, application et preuve, refus 409 enrichi sur un esclave ;
 *  - les groupes de domaines (« multi »), les zones transparentes et DNSSEC, la
 *    zone de tout le DNS (« * » ou « . ») et les zones a une seule etiquette, que
 *    l'API ne couvre pas, gardent l'ancien chemin : requetes liees, saisies
 *    filtrees, privilege et refus esclave, puis regeneration ;
 *  - sans unbound (resolveur PowerDNS, dnsdist, cache local), la table sert aux
 *    autres services : ancien chemin, sans regeneration d'unbound.
 * Chaque handler qui ecrit verifie AsDnsAdministrator cote serveur : le bouton
 * masque ne suffit pas.
 */

function fz_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}
function fz_reload_call($function):string{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);
    // Le bandeau et le parcours en deux etapes portent les compteurs : ils suivent chaque ecriture.
    $ctx="if(typeof FzContextReload==='function'){FzContextReload();}";
    return (($function!=="")?"$function();":"if(typeof FzoneReload==='function'){FzoneReload();}").$ctx;
}
/** Le JavaScript qui montre, sans bloquer, les constats gardes en session. */
function fz_notices_call():string{
    return "Loadjs('".CurrentPageName()."?api-notices-js=yes');";
}
function api_notices_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    echo unbound_api::notices_js($tpl->javascript_parse_text("{warning}",0,true),function($s) use ($tpl){
        return $tpl->javascript_parse_text($s,0,true);
    });
}
function fz_can_edit():bool{
    $tpl=new template_admin();
    return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}
function fz_unbound():bool{
    return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"))==1;
}
/** Nature d'une ligne pdns_fwzones (= zoneKind d'articarest). */
function fz_kind($ligne):string{
    if(!is_array($ligne)){return "";}
    if(intval($ligne["bulk"] ?? 0)==1){return "multi";}
    if(intval($ligne["transparent"] ?? 0)==1){return "transparent";}
    if(intval($ligne["onlyinsecure"] ?? 0)==1){return "dnssec";}
    return "forward";
}
/** Une zone que l'API accepte (= validateDomain : deux etiquettes au moins, alphabet DNS). */
function fz_api_zone_ok($zone):bool{
    $z=strtolower(rtrim(trim((string)$zone),"."));
    return (bool)preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/',$z);
}
/**
 * Une zone du chemin historique : « * », « . » ou un nom DNS (une etiquette
 * permise). Le nom est recopie tel quel dans la configuration d'unbound : ni
 * guillemet, ni espace, ni retour a la ligne.
 */
function fz_legacy_zone_ok($zone):bool{
    $z=trim((string)$zone);
    if($z==="*" || $z==="."){return true;}
    if(strlen($z)>253 || strpos($z,"..")!==false){return false;}
    return (bool)preg_match('/^[A-Za-z0-9_]([A-Za-z0-9_-]{0,62})(\.[A-Za-z0-9_]([A-Za-z0-9_-]{0,62}))*\.?$/',$z);
}
/** Une adresse de serveur de groupe : IP, avec @port et #nom TLS facultatifs (forward-addr d'unbound). */
function fz_forward_addr_ok($v):bool{
    $v=trim((string)$v);
    if(!preg_match('/^([0-9A-Fa-f:.]+)(@([0-9]{1,5}))?(#[A-Za-z0-9.-]{1,253})?$/',$v,$m)){return false;}
    if(filter_var($m[1],FILTER_VALIDATE_IP)===false){return false;}
    if(isset($m[3]) && $m[3]!=="" && (intval($m[3])<1 || intval($m[3])>65535)){return false;}
    return true;
}
/** Regeneration d'unbound apres une ecriture du chemin historique, comme l'ancienne page. */
function fz_regenerate($kind){
    if(!fz_unbound()){return;}
    if($kind==="transparent" || $kind==="dnssec"){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
        return;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/build/zones");
}
/** Refus d'une ecriture du chemin historique : privilege, puis esclave du cluster (texte d'articarest). */
function fz_legacy_refusal($tpl):string{
    if(!fz_can_edit()){return "{ERROR_NO_PRIVS2}";}
    return (new unbound_api())->legacy_write_refusal();
}
function fz_row($id){
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pdns_fwzones WHERE ID=".intval($id));
    return (is_array($ligne) && isset($ligne["ID"]))?$ligne:null;
}

/** Cles ecrites par la fenetre des domaines inconnus (UnboundUseSystemDNS -> UnboundEnforceUnknownDomains). */
function fz_unknown_keys():array{
    return array("UnboundEnforceUnknownDomains","UnboundEnforcedDomains");
}
function dnssec_js():bool{
    if(unbound_meta_refuse_js_collection("forward_zones")){return false;}
    $function=fz_function();
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{fz_kind_dnssec}", "$page?dnssec-popup=yes&function=$function",600);
}
function transparent_js():bool{
    if(unbound_meta_refuse_js_collection("forward_zones")){return false;}
    $function=fz_function();
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{fz_kind_transparent}", "$page?transparent-popup=yes&function=$function",600);
}
function multidomains_js():bool{
    if(unbound_meta_refuse_js_collection("forward_zones")){return false;}
    $function=fz_function();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["multidomains-js"]);
    return $tpl->js_dialog2("{fz_kind_multi}", "$page?multidomains-popup=$ID&function=$function",600);
}
function multidomainsToArray($ligne):array{
    $hostnames_decoded = json_decode($ligne["hostname"], true); // Always decode as an associative array
    if (!is_array($hostnames_decoded) || empty($hostnames_decoded)) {
        $hostnames = [
            "DNS1" => "8.8.8.8",
            "DNS2" => "8.8.4.4"
        ];
        return $hostnames;
    }
    $hostnames = $hostnames_decoded;


    if (!array_key_exists("DNS1", $hostnames)) {
        $hostnames["DNS1"]="8.8.8.8";
    }
    if (!array_key_exists("DNS2", $hostnames)) {
        $hostnames["DNS2"]="8.8.4.4";
    }
    return $hostnames;
}
function multidomainsCleanBulk($bulk):string{
    $tb=explode("\n",(string)$bulk);
    $z=array();
    foreach ($tb as $t) {
        $t=trim($t);
        if($t===""){
            continue;
        }
        $z[]=$t;
    }
    return @implode("\n",$z);
}
function multidomains_popup():bool{
    $function=fz_function();
    $tpl=new template_admin();
    $ID=intval($_GET["multidomains-popup"]);
    $btn="{add}";
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM pdns_fwzones WHERE ID=$ID");
    if(!is_array($ligne)){$ligne=array();}
    if(!isset($ligne["zone"])){
        $ligne["zone"]="New zone group";
    }
    if($ID>0){
        $btn="{apply}";
    }
    $useTLS=intval($ligne["useTLS"] ?? 0);
    $hostnames=multidomainsToArray(array("hostname"=>$ligne["hostname"] ?? ""));
    $form[]=$tpl->field_hidden("multidomains",$ID);
    $form[]=$tpl->field_text("zone","{rulename}",$ligne["zone"],false,"{fz_f_rulename_help}");
    $form[]=$tpl->field_checkbox("useTLS","{fz_tls_plain}",$useTLS,false,"{fz_f_tls_help}");
    $form[]=$tpl->field_text("DNS1","{primary_dns}",$hostnames["DNS1"]);
    $form[]=$tpl->field_text("DNS2","{secondary_dns}", $hostnames["DNS2"],false,"{fz_f_secondary_help}");
    $form[]=$tpl->field_textareacode("dnsbulk","{domains}",$ligne["dnsbulk"] ?? "","{fz_f_domains_help}");
    // form_outside() ignore son 3e argument (explication) : elle est montree par div_explain (skill §11).
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("{fz_multi_form_explain}"));
    echo $tpl->form_outside("",$form,"{unbound_zone_legacy_path}",$btn,"dialogInstance2.close();".fz_reload_call($function),"AsDnsAdministrator");
    return true;
}
/**
 * Groupe de domaines : chemin historique. L'INSERT etait mal forme (« ;) » en
 * trop) et la zone, les serveurs et les domaines partaient non echappes dans le
 * SQL, puis tels quels dans la configuration d'unbound (forward-addr).
 */
function multidomains_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $meta=unbound_meta_refuse_collection("forward_zones");
    if($meta!==""){ echo $meta;return false;}
    $refusal=fz_legacy_refusal($tpl);
    if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
    $ID=intval($_POST["multidomains"]);
    if($ID>0){
        // Seul un groupe de domaines passe par ce chemin historique : une zone
        // de transfert (geree par l'API, validee et prouvee), transparente ou
        // DNSSEC ne doit jamais etre convertie ici sans validation ni preuve.
        $row=fz_row($ID);
        if($row===null){ echo $tpl->post_error("{rulename}: {wrong_value}");return false;}
        if(fz_kind($row)!=="multi"){ echo $tpl->post_error("{unbound_zone_not_a_group}");return false;}
    }
    $zone=trim((string)($_POST["zone"] ?? ""));
    if($zone==="" || strlen($zone)>255 || preg_match('/[\x00-\x1f\x7f]/',$zone)){
        echo $tpl->post_error("{rulename}: {wrong_value}");return false;
    }
    $dns1=trim((string)($_POST["DNS1"] ?? ""));
    $dns2=trim((string)($_POST["DNS2"] ?? ""));
    if(!fz_forward_addr_ok($dns1)){ echo $tpl->post_error("{primary_dns}: {wrong_value}");return false;}
    if($dns2!=="" && !fz_forward_addr_ok($dns2)){ echo $tpl->post_error("{secondary_dns}: {wrong_value}");return false;}
    $useTLS=intval($_POST["useTLS"] ?? 0)==1?1:0;
    $dnsbulk=multidomainsCleanBulk($_POST["dnsbulk"] ?? "");
    foreach(explode("\n",$dnsbulk) as $d){
        if($d==="" ){continue;}
        // « * » et « . » : le generateur saute toute entree de moins de 3
        // caracteres (Configurator.go) ; acceptes, ils ne seraient jamais servis.
        if($d==="*" || $d==="." || !fz_legacy_zone_ok($d)){
            echo $tpl->post_error("{domains}: {wrong_value} (".htmlspecialchars($d).")");
            return false;
        }
    }
    $jsonhostnames=json_encode(array("DNS1"=>$dns1,"DNS2"=>$dns2));
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    if($ID==0){
        $q->QUERY_SQL_PARAMS("INSERT INTO pdns_fwzones (zone,useTLS,hostname,dnsbulk,bulk) VALUES (?,?,?,?,1)",
            array($zone,$useTLS,$jsonhostnames,$dnsbulk));
    }else{
        $q->QUERY_SQL_PARAMS("UPDATE pdns_fwzones SET zone=?,useTLS=?,hostname=?,dnsbulk=?,bulk=1 WHERE ID=? AND bulk=1",
            array($zone,$useTLS,$jsonhostnames,$dnsbulk,$ID));
    }
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    fz_regenerate("multi");
    return admin_tracks("Save DNS Cache bulk domains redirection $zone to $dns1 $dns2");

}

function transparent_popup():bool{
    $function=fz_function();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_text("TRANSPARENT","{domain}","",true,"{fz_f_transparent_help}");
    $html[]=$tpl->div_explain("{fz_transparent_form_explain}");   // form_outside() ignore son explication (skill §11)
    $html[]=$tpl->form_outside("", @implode("\n", $form),"{unbound_zone_legacy_path}","{add}",
        fz_reload_call($function)."LoadAjax('table-loader','$page?main=yes');dialogInstance2.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
/** Zone transparente : chemin historique, requete liee (le domaine partait non echappe). */
function transparent_save():bool{
    return fz_legacy_flag_save("TRANSPARENT","transparent",
        "INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS,transparent) VALUES (?,'53','','0','0',1)",
        "Adding new %s in transparent mode for the DNS Cache service");
}
/** Domaine DNSSEC non securise : chemin historique, requete liee. */
function dnssec_save():bool{
    return fz_legacy_flag_save("DNSSEC","dnssec",
        "INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS,onlyinsecure,insecure) VALUES (?,'53','','0','0',1,1)",
        "Adding new %s inside DNSSEC whitelist forward zone");
}
function fz_legacy_flag_save($field,$kind,$sql,$track):bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $meta=unbound_meta_refuse_collection("forward_zones");
    if($meta!==""){ echo $meta;return false;}
    $refusal=fz_legacy_refusal($tpl);
    if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
    $domain=trim((string)($_POST[$field] ?? ""));
    if($domain===""){
        echo $tpl->post_error("{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}");
        return false;
    }
    if($domain==="*" || $domain==="." || !fz_legacy_zone_ok($domain)){
        echo $tpl->post_error("{domain}: {wrong_value}");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $q->QUERY_SQL_PARAMS($sql,array($domain));
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    fz_regenerate($kind);
    return admin_tracks(sprintf($track,$domain));
}
function dnssec_popup():bool{
    $function=fz_function();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_text("DNSSEC","{domain}","",true,"{fz_f_dnssec_help}");
    $html[]=$tpl->div_explain("{fz_dnssec_form_explain}");   // form_outside() ignore son explication (skill §11)
    $html[]=$tpl->form_outside("", @implode("\n", $form),"{unbound_zone_legacy_path}","{add}",
        fz_reload_call($function)."LoadAjax('table-loader','$page?main=yes');dialogInstance2.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function zone_js():bool{
	if(unbound_meta_refuse_js_collection("forward_zones")){return false;}
	$function=fz_function();
	$id=intval($_GET["zone-id-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_forward_zone}");

	if($id>0){
		$ligne=fz_row($id);
		if(is_array($ligne)){
			$title=htmlspecialchars($ligne["zone"]." [".$ligne["hostname"].":".$ligne["port"]."]");
		}
	}
	// ?domain= pre-remplit la zone (lien depuis d'autres pages) : encode, il
	// partait brut dans l'URL de la fenetre.
	$domain=urlencode((string)($_GET["domain"] ?? ""));
	return $tpl->js_dialog($title, "$page?zone-id=$id&function=$function&domain=$domain");

}

function unbound_unknown_domains_js():bool{
    if(count(unbound_meta_locked_keys(fz_unknown_keys()))>0){
        header("content-type: application/x-javascript");
        (new template_admin())->js_error("{unbound_meta_managed_locked}");
        return false;
    }
    $page=CurrentPageName();
    $function=fz_function();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{unknown_domains}","$page?unbound-unknown-domains-start=yes&function=$function",550);
}

/**
 * Confirmation de suppression d'une zone.
 * js_confirm_delete (jquery-confirm) n'execute son $jsafter que si la reponse
 * du POST est vide — c'est-a-dire uniquement en cas de succes reel.
 */
function delete_js():bool{
	if(unbound_meta_refuse_js_collection("forward_zones")){return false;}
	$tpl=new template_admin();
	$id=intval($_GET["delete-js"]);
	$function=fz_function();
	$ligne=fz_row($id);
	$label=is_array($ligne)?trim((string)$ligne["zone"]):"#$id";
	if($label===""){$label="#$id";}
	return $tpl->js_confirm_delete(htmlspecialchars($label),"delete-zone",$id,fz_reload_call($function).fz_notices_call());
}

/**
 * Suppression : une zone simple d'un cache unbound passe par DELETE
 * /unbound/api/forward-zones/{id} (application et preuve) ; les autres natures,
 * et toute zone sans unbound, par l'ancien chemin suivi d'une regeneration —
 * l'ancienne version supprimait la ligne sans rien regenerer.
 */
function delete_zone():bool{
	$tpl=new template_admin();
	if(!fz_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	// Tenue par une politique Meta : refus avant l'API (423) comme avant l'ancien chemin SQL.
	$meta=unbound_meta_refuse_collection("forward_zones");
	if($meta!==""){ echo $meta;return false;}
	$id=intval($_POST["delete-zone"]);
	if($id<=0){ echo $tpl->post_error("{error}: id");return false;}
	$ligne=fz_row($id);
	if(!is_array($ligne)){ echo $tpl->post_error("{error}: id");return false;}
	$kind=fz_kind($ligne);
	$zone=(string)$ligne["zone"];
	if(fz_unbound() && $kind==="forward"){
		$r=(new unbound_api())->delete("/unbound/api/forward-zones/$id");
		if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
		unbound_api::queue($r);
		return admin_tracks("Removed DNS forward zone $zone");
	}
	$refusal=fz_legacy_refusal($tpl);
	if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL_PARAMS("DELETE FROM pdns_fwzones WHERE ID=?",array($id));
	if(!$q->ok){ echo $tpl->post_error($q->mysql_error);return false;}
	fz_regenerate($kind);
	return admin_tracks("Removed DNS forward zone $zone ($kind)");
}

function zone():bool{
		$tpl=new template_admin();
        $function=fz_function();

        $useTLS=0;
        $hostname=null;
		$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
        $zone=null;
		$id=intval($_GET["zone-id"]);
        $recursive=0;
		$bname="{add}";
        $port="";
		$page=CurrentPageName();
		$title=$tpl->javascript_parse_text("{new_forward_zone}");

		if($id>0){
			$bname="{apply}";
			$ligne=fz_row($id);
			if(is_array($ligne)){
				$hostname=$ligne["hostname"];
				$zone=$ligne["zone"];
				$port=$ligne["port"];
				$recursive=$ligne["recursive"];
				$title=htmlspecialchars("$zone » $hostname:$port");
				$useTLS=intval($ligne["useTLS"]);
			}

		}
	    if($zone==null){
            if(isset($_GET["domain"])){
                $zone=(string)$_GET["domain"];
            }
        }

		if(!is_numeric($port)){$port=53;}

		$form[]=$tpl->field_hidden("ID", $id);
		$form[]=$tpl->field_text("zone", "{domain}", $zone,false,"{fz_f_domain_help}");
		if($UnboundEnabled==0){$form[]=$tpl->field_checkbox("recursive","{fz_recursive_plain}",$recursive,false,"{fz_f_recursive_help}");}
		$form[]=$tpl->field_ipaddr("hostname", "{fz_f_server}", $hostname,false,"{fz_f_server_help}");
		$form[]=$tpl->field_numeric("port","{fz_f_port}",$port,"{fz_f_port_help}");

		// Le chemin historique (« * », nom sans domaine) n'est plus annonce ici : la ligne
		// enregistree ainsi porte « Not verified » dans le tableau, avec son explication.
		$explain="{fz_zone_form_explain}";
		if($UnboundEnabled==1){
			$form[]=$tpl->field_checkbox("useTLS","{fz_tls_plain}",$useTLS,false,"{fz_f_tls_help}");
		}


		// form_outside() ignore son 3e argument (explication) : elle est montree par div_explain (skill §11).
		$html[]=$tpl->div_explain($explain);
		$html[]=$tpl->form_outside("$title", @implode("\n", $form),$explain,$bname,
				fz_reload_call($function)."LoadAjax('table-loader','$page?main=yes');BootstrapDialog1.close();".fz_notices_call(),"AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

	return true;
}

/**
 * Zone simple. Avec unbound et un nom DNS a deux etiquettes au moins : POST ou
 * PUT /unbound/api/forward-zones (articarest valide, applique et prouve ; un
 * 409/400/422 s'affiche avec son texte). Sinon (« * », « . », une seule
 * etiquette, ou pas d'unbound) : ancien chemin, requete liee — tous les champs
 * partaient non echappes dans le SQL.
 */
function zone_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!fz_can_edit()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$meta=unbound_meta_refuse_collection("forward_zones");
	if($meta!==""){ echo $meta;return false;}
	$id=intval($_POST["ID"] ?? 0);
	$zone=trim((string)($_POST["zone"] ?? ""));
	$server=trim((string)($_POST["hostname"] ?? ""));
	if($zone==="" || $server===""){
        echo $tpl->post_error("{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}");
        return false;
    }
	if(filter_var($server,FILTER_VALIDATE_IP)===false){
        echo $tpl->post_error("{ipaddr}: {wrong_value}");
        return false;
	}
	$port=intval($_POST["port"] ?? 0);
	if($port==0){$port=53;}
	if($port<1 || $port>65535){
        echo $tpl->post_error("{listen_port}: {wrong_value}");
        return false;
	}
	$useTLS=intval($_POST["useTLS"] ?? 0)==1?1:0;
	$recursive=intval($_POST["recursive"] ?? 0)==1?1:0;
	$old=null;
	if($id>0){
		$old=fz_row($id);
		if(!is_array($old)){ echo $tpl->post_error("{error}: id");return false;}
		if(fz_kind($old)!=="forward"){ echo $tpl->post_error("{unbound_zone_legacy_path}");return false;}
	}

	if(fz_unbound() && fz_api_zone_ok($zone)){
		$body=array("zone"=>$zone,"server"=>$server,"port"=>$port,"use_tls"=>$useTLS==1);
		$api=new unbound_api();
		$r=($id>0)?$api->put("/unbound/api/forward-zones/$id",$body):$api->post("/unbound/api/forward-zones",$body);
		if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false;}
		unbound_api::queue($r);
		return admin_tracks(($id>0?"Edit":"Create")." DNS forward zone $zone -> $server:$port");
	}

	if(!fz_legacy_zone_ok($zone)){
        echo $tpl->post_error("{domain}: {wrong_value}");
        return false;
	}
	$refusal=fz_legacy_refusal($tpl);
	if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	if($id>0){
		$q->QUERY_SQL_PARAMS("UPDATE pdns_fwzones SET port=?,zone=?,recursive=?,useTLS=?,hostname=? WHERE ID=?",
			array($port,$zone,$recursive,$useTLS,$server,$id));
	}else{
		$q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS) VALUES (?,?,?,?,?)",
			array($zone,$port,$server,$recursive,$useTLS));
	}
	if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
	if(fz_unbound()){
		$GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
	}
    return admin_tracks(($id>0?"Edit":"Create")." DNS forward zone $zone -> $server:$port");

}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{forward_zones}","far fa-arrows","{forward_zones_explain}",
        "$page?main=yes","dns-forward","progress-firehol-restart",false,"table-loader");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}

	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function main():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Barre de progression de « Reload DNS service » : hors des deux hotes recharges,
    // sinon un enregistrement pendant la reconfiguration effacerait la barre.
    echo "<div id='dnsfw-zone-restart' style='margin-top:10px'></div>";
    // Bandeau d'accroche + parcours en deux etapes : fragment a part, recharge apres
    // chaque ecriture (fz_reload_call) et apres la fenetre des domaines inconnus.
    echo "<div id='fz-context'></div>";
    echo "<div style='margin-top:10px'>".$tpl->search_block($page,null,null,null,"&main2=yes")."</div>";
    echo "<script>window.FzContextReload=function(){if(!document.getElementById('fz-context')){return;}LoadAjaxSilent('fz-context','$page?fz-context=yes');};FzContextReload();</script>";
    return true;
}

/** Reglages lus une fois, partages par tous les widgets de la page. */
function fz_model():array{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["unbound"]=intval($S->GET_INFO("UnboundEnabled"))==1;
    $m["dnssec"]=$m["unbound"] && intval($S->GET_INFO("UnBoundDNSSEC"))==1;
    $m["roothints"]=$m["unbound"] && intval($S->GET_INFO("EnableDNSRootInts"))==1;
    $m["can_edit"]=fz_can_edit();
    // Esclave du cluster ou appliance geree : chaque ecriture serait refusee (409) ; le
    // bandeau le dit, et les boutons d'ajout, de modification et de suppression disparaissent.
    $st=fz_state();
    $m["readonly"]=(isset($st["role"]) && $st["role"]==="slave") || !empty($st["managed"]);
    // Politique Meta : zones tenues (lecture seule), domaines inconnus tenus.
    $m["meta_zones"]=unbound_meta_collection_locked("forward_zones");
    $m["meta_unknown"]=count(unbound_meta_locked_keys(fz_unknown_keys()))>0;
    return $m;
}

/** Profils DNS4EU (UseDNSForEUBackends), libelles existants. */
function fz_dns4eu_profiles():array{
    return array(1=>"{protective_resolution}",2=>"{child_protection}",3=>"{ads_protection}",
        4=>"{child_protection} & {ads_protection}",5=>"{unfiltered_resolution}");
}

/**
 * Ou partent les domaines sans regle, comme le construit articarest
 * (unbound/Configurator.go) : serveurs racine, sinon serveurs imposes
 * (UnboundEnforceUnknownDomains) ou DNS du systeme ; DNS4EU s'ajoute dans
 * les deux cas des qu'un profil est choisi.
 */
function fz_unknown_model():array{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $mo=array("mode"=>"system","servers"=>array(),"dns4eu"=>"");
    if(intval($S->GET_INFO("EnableDNSRootInts"))==1){$mo["mode"]="root";return $mo;}
    $eu=intval($S->GET_INFO("UseDNSForEUBackends"));
    $profiles=fz_dns4eu_profiles();
    if(isset($profiles[$eu])){$mo["dns4eu"]=$profiles[$eu];}
    if(intval($S->GET_INFO("UnboundEnforceUnknownDomains"))==1){
        $mo["mode"]="specific";
        $vals=explode(";",(string)base64_decode((string)$S->GET_INFO("UnboundEnforcedDomains")));
        foreach($vals as $i=>$v){
            $v=trim((string)$v);
            if($v==="" || $i>3){continue;}
            $mo["servers"][]=$v;
        }
        return $mo;
    }
    $IP=new IP();
    $resolv=new resolv_conf();
    foreach(array("DNS1","DNS2","DNS3") as $k){
        if(!isset($resolv->MainArray[$k])){continue;}
        $v=trim((string)$resolv->MainArray[$k]);
        if($v!=="" && $IP->isValid($v)){$mo["servers"][]=$v;}
    }
    return $mo;
}

/**
 * Compteurs reels par nature, sur TOUTE la table (pas sur la page ni la recherche),
 * avec les memes regles de visibilite que le tableau (fz_row_model).
 */
function fz_counts($m):array{
    $out=array("ok"=>true,"total"=>0,"counts"=>array("forward"=>0,"multi"=>0,"transparent"=>0,"dnssec"=>0));
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $results=$q->QUERY_SQL("SELECT * FROM pdns_fwzones ORDER by zone");
    if(!$q->ok){$out["ok"]=false;return $out;}
    if(!is_array($results)){$results=array();}
    foreach($results as $ligne){
        $r=fz_row_model($ligne,$m,"","");
        if($r===null){continue;}
        $out["counts"][$r["kind"]]++;
        $out["total"]++;
    }
    return $out;
}

/** ?fz-context=yes : lecture seule, bandeau d'accroche, puis « Your rules » -> « Everything else ». */
function fz_context():bool{
    $tpl=new template_admin();
    $m=fz_model();
    $c=fz_counts($m);
    $html=array();
    if($m["unbound"]){$html[]=fz_readonly_banner();}
    $html[]=fz_hero($m);
    $html[]="<div class='fz-flow".($m["unbound"]?"":" fz-flow-one")."'>";
    $html[]=fz_rules_step($c);
    if($m["unbound"]){
        $html[]="<div class='fz-flow-arrow' aria-hidden='true'><span><i class='fas fa-arrow-right'></i></span></div>";
        $html[]=fz_default_route_card($m,fz_unknown_model());
    }
    $html[]="</div>";
    echo fzone_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

/** Le JavaScript du bouton « Add a rule » : la fenetre de choix avec unbound, la zone simple sinon. */
function fz_add_rule_js($m,$function):string{
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$function);
    if(!$m["unbound"]){return "Loadjs('$page?zone-id-js=0&function=$function')";}
    return "Loadjs('$page?add-rule-js=yes&function=$function')";
}

/** Bandeau d'accroche : le benefice, le fonctionnement, des puces vraies pour le moteur actif. */
function fz_hero($m):string{
    $tpl=new template_admin();
    $chips=array();
    if($m["unbound"]){$chips[]=array("fas fa-list-check","{fz_chip_order}");}
    $chips[]=array("fas fa-users","{fz_chip_devices}");
    // Sans unbound, l'enregistrement n'applique rien lui-meme : la puce serait fausse.
    if($m["unbound"] && !$m["readonly"]){$chips[]=array("fas fa-floppy-disk","{fz_chip_applied}");}
    if($m["unbound"]){$chips[]=array("fas fa-lock","{fz_chip_tls}");}
    $c=array();
    foreach($chips as $ch){$c[]="<span class='fz-hero-chip'><i class='{$ch[0]}'></i>{$ch[1]}</span>";}

    $acts=array();
    if($m["can_edit"] && !$m["meta_zones"] && !$m["readonly"]){
        $acts[]="<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' OnClick=\"".fz_add_rule_js($m,"")."\"><i class='fas fa-plus'></i>{fz_add_rule}</button>";
    }
    if($m["can_edit"]){
        $acts[]="<button type='button' class='ak-btn ak-btn-lg' title='{fz_reload_hint}' OnClick=\"".fz_restart_js($tpl)."\"><i class='fas fa-rotate'></i>{fz_reload_dns}</button>";
    }
    $text=$m["unbound"]?"{fz_hero_text}":"{fz_hero_text_plain}";
    $h ="<section class='ak fz-hero' aria-labelledby='fz-hero-t'>";
    $h.="<span class='fz-hero-ico' aria-hidden='true'><i class='fas fa-signs-post'></i></span>";
    $h.="<div class='fz-hero-main'><h2 class='fz-hero-t' id='fz-hero-t'>{fz_hero_title}</h2>";
    $h.="<p class='fz-hero-p'>$text</p>";
    $h.="<div class='fz-hero-chips'>".@implode("",$c)."</div></div>";
    if(count($acts)>0){$h.="<div class='fz-hero-acts'>".@implode("",$acts)."</div>";}
    $h.="</section>";
    return $h;
}

/** Etape 1 « Your rules » : total reel et detail par nature (les natures a 0 sont tues). */
function fz_rules_step($c):string{
    $kinds=fz_kinds();
    $total="&mdash;";
    $word="{fz_rules_word}";
    $parts=array();
    if($c["ok"]){
        $total=intval($c["total"]);
        if($total==1){$word="{fz_rule_word}";}
        foreach($kinds as $kind=>$def){
            $n=intval($c["counts"][$kind]);
            if($n==0){continue;}
            $parts[]="<span class='fz-bd'><i class='{$def[0]}'></i>$n {$def[3]}</span>";
        }
    }
    $h ="<section class='ak-card fz-step' aria-labelledby='fz-step1'>";
    $h.="<div class='fz-kick' id='fz-step1'><span class='fz-num'>1</span>{fz_step_rules}</div>";
    $h.="<div class='fz-big'><span class='fz-big-n'>$total</span><span class='fz-big-w'>$word</span></div>";
    if(count($parts)>0){$h.="<div class='fz-bds'>".@implode("",$parts)."</div>";}
    $h.="<p class='fz-step-p'>{fz_step_rules_explain}</p>";
    $h.="</section>";
    return $h;
}

/** Esclave du cluster ou appliance geree : toute ecriture sera refusee, on le dit avant. */
/** /unbound/api/state lu une fois par requete (bandeau lecture seule + modele). */
function fz_state():array{
    if(isset($GLOBALS["FZ_STATE"])){return $GLOBALS["FZ_STATE"];}
    $GLOBALS["FZ_STATE"]=array();
    if(!fz_unbound()){return $GLOBALS["FZ_STATE"];}
    $r=(new unbound_api())->get(unbound_api::PREFIX."state");
    if($r["ok"] && is_array($r["data"])){$GLOBALS["FZ_STATE"]=$r["data"];}
    return $GLOBALS["FZ_STATE"];
}

function fz_readonly_banner():string{
    $d=fz_state();
    if(count($d)==0){return "";}
    $text="";
    $link="";
    if(isset($d["role"]) && $d["role"]==="slave"){
        $text="{fz_slave_readonly}";
        $master=trim((string)($d["master"] ?? ""));
        $addr=($master!=="")?" <span class='fz-mono'>(".htmlspecialchars($master).")</span>":"";
        $url=trim((string)($d["master_console"] ?? ""));
        if(preg_match('#^https?://[^\s"\'<>]+$#',$url)){
            $link="<a class='fz-ro-link' href=\"".htmlspecialchars($url)."\" target='_blank' rel='noopener'>{fz_open_master}$addr <i class='fas fa-arrow-up-right-from-square'></i></a>";
        }elseif($addr!==""){
            $link="<span class='fz-ro-link'>$addr</span>";
        }
    }elseif(!empty($d["managed"])){
        $text="{fz_managed_readonly}";
    }
    if($text===""){return "";}
    return "<div class='ak ak-banner ak-warn fz-ro' role='status'><span><i class='fas fa-lock'></i></span><div class='ak-banner-m'>$text</div>$link</div>";
}

/** Etape 2 « Everything else » (Unknown domains) : le mode et les serveurs reels de la route par defaut. */
function fz_default_route_card($m,$mo):string{
    $page=CurrentPageName();
    $modes=array(
        "root"=>array("fas fa-globe","{EnableDNSRootInts}"),
        "system"=>array("fas fa-server","{fz_mode_system}"),
        "specific"=>array("fas fa-sliders","{fz_mode_specific}"),
    );
    list($mico,$mlabel)=$modes[$mo["mode"]];
    $labels=array("{primary_dns}","{secondary_dns}","{DNSServer} 3","{DNSServer} 4");

    $chips=array();
    $chips[]="<span class='ak-tag ak-info fz-type'><i class='$mico'></i>$mlabel</span>";
    foreach($mo["servers"] as $i=>$srv){
        $title=($mo["mode"]==="specific" && isset($labels[$i]))?$labels[$i]:"{DNSServer}";
        $chips[]="<span class='ak-chip' title='$title'>".htmlspecialchars($srv)."</span>";
    }
    if($mo["dns4eu"]!==""){
        $chips[]="<span class='ak-tag ak-ok fz-type'><i class='fas fa-shield-halved'></i>{UseDNSForEUBackends} &middot; ".$mo["dns4eu"]."</span>";
    }
    $warn="";
    if($mo["mode"]!=="root" && count($mo["servers"])==0){
        $warn="<div class='ak-banner ak-warn fz-step-warn' role='status'><span><i class='fas fa-triangle-exclamation'></i></span><div class='ak-banner-m'><strong>{fz_no_upstream}</strong><br>{fz_no_upstream_explain}</div></div>";
    }

    $btn="";
    if($m["can_edit"] && !$m["meta_unknown"] && !$m["readonly"]){
        $btn="<button type='button' class='ak-btn fz-step-btn' OnClick=\"Loadjs('$page?unbound-unknown-domains-js=yes');\"><i class='fas fa-pen'></i>{change}</button>";
    }
    $h ="<section class='ak-card fz-step' aria-labelledby='fz-step2'>";
    $h.="<div class='fz-step-head'><div class='fz-kick' id='fz-step2'><span class='fz-num'>2</span>{fz_step_else}<span class='fz-kick-sub'>({unknown_domains})</span></div>$btn</div>";
    $h.="<div class='fz-route-vals'>".@implode("",$chips)."</div>";
    $h.=$warn;
    $h.="<p class='fz-step-p'>{fz_step_else_explain}</p>";
    $h.="</section>";
    return $h;
}

/** Une ligne de pdns_fwzones mise en forme pour le tableau (null = ligne masquee). */
function fz_row_model($ligne,$m,$page,$function){
    $kind=fz_kind($ligne);
    $id=intval($ligne["ID"]);
    $zone=trim((string)$ligne["zone"]);
    if($kind!=="forward" && !$m["unbound"]){return null;}
    if($kind==="dnssec" && !$m["dnssec"]){return null;}
    if($m["roothints"] && ($zone==="*" || $zone===".")){return null;}

    $r=array("id"=>$id,"kind"=>$kind,"zone"=>$zone,"servers"=>array(),"automatic"=>false,
        "tls"=>intval($ligne["useTLS"])==1,"recursive"=>false,"insecure"=>false,"domains"=>array(),
        "edit"=>"","delete"=>"Loadjs('$page?delete-js=$id&function=$function')","legacy"=>false);
    if($kind==="forward"){
        $r["servers"][]=$ligne["hostname"].":".$ligne["port"];
        $r["recursive"]=(!$m["unbound"] && intval($ligne["recursive"])==1);
        $r["edit"]="Loadjs('$page?zone-id-js=$id&function=$function')";
    }
    if($kind==="multi"){
        foreach(multidomainsToArray($ligne) as $dns){
            if(trim((string)$dns)!==""){$r["servers"][]=$dns;}
        }
        $bulk=multidomainsCleanBulk($ligne["dnsbulk"]);
        foreach(explode("\n",$bulk) as $d){
            if(strlen($d)>2){$r["domains"][]=$d;}
        }
        $r["edit"]="Loadjs('$page?multidomains-js=$id&function=$function')";
    }
    if($kind==="transparent" || $kind==="dnssec"){$r["automatic"]=true;}
    if($m["dnssec"] && intval($ligne["insecure"] ?? 0)==1){$r["insecure"]=true;}
    // Le chemin historique n'a de sens qu'avec unbound : sans lui, tout passe par la table.
    if($m["unbound"] && ($kind!=="forward" || !fz_api_zone_ok($zone))){$r["legacy"]=true;}
    // Sans le droit d'ecriture, le nom n'ouvre pas un editeur dont l'enregistrement serait refuse.
    if(!$m["can_edit"] || $m["meta_zones"] || $m["readonly"]){$r["edit"]="";}
    return $r;
}

/**
 * Chaque nature de regle : icone, libelle en mots courants, terme technique (sous-titre
 * gris), libelle court (filtres et detail des compteurs), ton de l'etiquette.
 */
function fz_kinds():array{
    return array(
        "forward"=>array("fas fa-arrow-right","{fz_kind_forward}","{fz_type_forward}","{fz_kind_forward}","ak-info"),
        "multi"=>array("fas fa-layer-group","{fz_kind_multi}","{multidomains}","{fz_kind_multi_short}","ak-ok"),
        "transparent"=>array("fas fa-arrow-right-arrow-left","{fz_kind_transparent}","{transparent}","{fz_kind_transparent}","ak-off"),
        "dnssec"=>array("fas fa-shield-halved","{fz_kind_dnssec}","{fz_dnssec_whitelist}","{fz_kind_dnssec}","ak-warn"),
    );
}

/** « What it does » : etiquette icone + mots courants, terme technique dessous. */
function fz_type_badge($kind):string{
    $k=fz_kinds();
    if(!isset($k[$kind])){return "";}
    $d=$k[$kind];
    return "<span class='ak-tag {$d[4]} fz-type'><i class='{$d[0]}'></i>{$d[1]}</span><div class='fz-tech'>{$d[2]}</div>";
}

/** Cellule « Domain » : nom (lien d'edition), bascule des domaines d'un groupe. */
function fz_zone_cell($r):string{
    $label=htmlspecialchars($r["zone"]);
    if($r["zone"]==="*" || $r["zone"]==="."){$label="{fz_all_domains}";}
    if($r["edit"]!==""){
        $name="<a href=\"javascript:blur();\" OnClick=\"{$r["edit"]}\">$label</a>";
    }elseif($r["automatic"]){
        $name="<span title='{fz_no_server_to_edit}'>$label</span>";
    }else{
        $name="<span>$label</span>";
    }
    $h="<div class='fz-zone'><span class='fz-zone-name'>$name</span>";
    if($r["kind"]==="multi"){
        $n=count($r["domains"]);
        $h.="<button type='button' class='fz-dcount' aria-expanded='false' aria-controls='fz-sub-{$r["id"]}' OnClick=\"FzToggle({$r["id"]},this)\">$n {domains}<i class='fas fa-chevron-down'></i></button>";
    }
    return $h."</div>";
}

/** Cellule « Answered by » : serveurs reels, puis chiffrement, recursion, DNSSEC, verification. */
function fz_upstream_cell($r):string{
    $h=array();
    if($r["automatic"]){
        $h[]="<span class='fz-auto'>{fz_automatic_plain}</span>";
    }elseif(count($r["servers"])>0){
        $s=array("<i class='fas fa-arrow-right-long fz-arrow' aria-hidden='true'></i>");
        foreach($r["servers"] as $srv){$s[]="<span class='ak-chip'>".htmlspecialchars($srv)."</span>";}
        $h[]="<div class='fz-ups-l'>".@implode("",$s)."</div>";
    }
    $t=array();
    if($r["tls"] && !$r["automatic"]){$t[]="<span class='ak-tag ak-ok fz-tag'><i class='fas fa-lock'></i>{fz_tls_plain}</span>";}
    if($r["recursive"]){$t[]="<span class='ak-tag ak-off fz-tag'><i class='fas fa-magnifying-glass'></i>{fz_recursive_plain}</span>";}
    if($r["insecure"]){$t[]="<span class='ak-tag ak-warn fz-tag'><i class='fas fa-triangle-exclamation'></i>{fz_dnssec_insecure}</span>";}
    // Seule une zone simple a une version « verifiee » (l'API) : sur les autres natures, le
    // marqueur serait present sur chaque ligne et ne dirait rien.
    if($r["legacy"] && $r["kind"]==="forward"){
        $t[]="<span class='ak-tag ak-off fz-tag' title='{fz_not_verified_hint}'><i class='fas fa-circle-info'></i>{fz_not_verified}</span>";
    }
    if(count($t)>0){$h[]="<div class='fz-ups-l'>".@implode("",$t)."</div>";}
    return "<div class='fz-ups'>".@implode("",$h)."</div>";
}

/** Boutons d'action : modifier (si la nature le permet) et supprimer, avec aria-label. */
function fz_actions_cell($r,$m):string{
    if(!$m["can_edit"] || $m["meta_zones"] || $m["readonly"]){return "";}
    $name=htmlspecialchars($r["zone"]);
    $h="";
    if($r["edit"]!==""){
        $h.="<button type='button' class='awc-act fz-act' aria-label='{fz_edit} $name' title='{fz_edit}' OnClick=\"{$r["edit"]}\"><i class='fas fa-pen'></i></button>";
    }
    $h.="<button type='button' class='awc-act awc-act-danger fz-act' aria-label='{fz_delete} $name' title='{fz_delete}' OnClick=\"{$r["delete"]}\"><i class='fas fa-trash-can'></i></button>";
    return "<div class='awc-acts'>$h</div>";
}

/** Une regle = une ligne, plus la sous-ligne repliee des domaines d'un groupe. */
function fzone_row($r,$m):string{
    $id=$r["id"];
    $h ="<tr class='fz-row' data-id='$id' data-kind='{$r["kind"]}' data-open='0'>";
    $h.="<td>".fz_zone_cell($r)."</td>";
    $h.="<td>".fz_type_badge($r["kind"])."</td>";
    $h.="<td>".fz_upstream_cell($r)."</td>";
    $h.="<td>".fz_actions_cell($r,$m)."</td>";
    $h.="</tr>";
    if($r["kind"]==="multi"){
        $chips=array();
        foreach($r["domains"] as $d){$chips[]="<span class='ak-chip'>".htmlspecialchars($d)."</span>";}
        if(count($chips)==0){$chips[]="<span class='awc-none'>{fz_group_empty}</span>";}
        $h.="<tr class='fz-sub' id='fz-sub-$id' style='display:none'><td colspan='4'><div class='fz-sub-in'>";
        $h.="<i class='fas fa-arrow-turn-down-right fz-sub-ico' aria-hidden='true'></i><span class='fz-sub-label'>{fz_group_domains}</span>".@implode("",$chips)."</div></td></tr>";
    }
    return $h;
}

/** Filtres par nature, avec leurs compteurs reels (sur le resultat de la recherche). */
function fz_filter_chips($counts):string{
    $total=array_sum($counts);
    $h="<div class='ak-seg fz-chips' role='group' aria-label='{fz_col_what}'>";
    $h.="<button type='button' class='ak-seg-b fz-fchip' data-kind='all' aria-pressed='true' OnClick=\"FzFilter(this)\">{all} ($total)</button>";
    foreach(fz_kinds() as $kind=>$def){
        if(intval($counts[$kind] ?? 0)==0){continue;}
        $h.="<button type='button' class='ak-seg-b fz-fchip' data-kind='$kind' aria-pressed='false' OnClick=\"FzFilter(this)\"><i class='{$def[0]}'></i>{$def[3]} ({$counts[$kind]})</button>";
    }
    return $h."</div>";
}

/** Etat vide : une invitation (aucune regle), ou aucune regle pour la recherche saisie. */
function fz_empty_state($m,$search,$page,$function):string{
    if($search!==""){
        return "<div class='ak-empty-c fz-empty'><i class='fas fa-magnifying-glass'></i><div class='fz-empty-title'>{fz_no_match} &laquo;&nbsp;".htmlspecialchars($search)."&nbsp;&raquo;</div></div>";
    }
    $h="<div class='ak-empty-c fz-empty'><i class='fas fa-signs-post'></i>";
    $h.="<div class='fz-empty-title'>".($m["unbound"]?"{fz_empty_title2}":"{fz_empty_title}")."</div>";
    $h.="<div class='fz-empty-sub'>{fz_empty_invite}</div>";
    if($m["can_edit"] && !$m["meta_zones"] && !$m["readonly"]){
        $h.="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"".fz_add_rule_js($m,$function)."\"><i class='fas fa-plus'></i>{fz_add_rule}</button>";
    }
    return $h."</div>";
}

/** En-tete : titre et explication ; les actions sont dans le bandeau d'accroche (fz_hero). */
function fz_tiny($tpl):string{
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{forward_zones}";
    $TINY_ARRAY["ICO"]="far fa-arrows";
    $TINY_ARRAY["EXPL"]="{forward_zones_explain}";
    $TINY_ARRAY["URL"]="dns-forward";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons(array());
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function main_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Whitelist: $function part ensuite dans des OnClick et des $jsafter sans echappement.
    $function=fz_function();
    $m=fz_model();

    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $QUS="1";
    $search=trim((string)($_GET["search"] ?? ""));
    if($search!==""){
        $like=$q->sqlite_escape_string2(str_replace("*","%",$search));
        $QUS="((zone LIKE '%$like%') OR (hostname LIKE '%$like%') OR (dnsbulk LIKE '%$like%'))";
    }
    $results=$q->QUERY_SQL("SELECT * FROM pdns_fwzones WHERE $QUS ORDER by zone");
    $err="";
    if(!$q->ok){$err=$tpl->div_error(htmlspecialchars($q->mysql_error));}
    if(!is_array($results)){$results=array();}

    $rows=array();
    $counts=array("forward"=>0,"multi"=>0,"transparent"=>0,"dnssec"=>0);
    foreach($results as $ligne){
        $r=fz_row_model($ligne,$m,$page,$function);
        if($r===null){continue;}
        $counts[$r["kind"]]++;
        $rows[]=fzone_row($r,$m);
    }

    $html=array();
    if($m["meta_zones"] || $m["meta_unknown"]){$html[]=unbound_meta_banner($tpl);}
    $html[]=$err;
    $html[]="<div class='awc-wrap fz-scope ak'><div class='awc-card'>";
    if(count($rows)>0){
        $html[]="<div class='fz-toolbar'>".fz_filter_chips($counts)."</div>";
    }
    $html[]="<div class='awc-scroll'><table class='awc-tbl fz-tbl'><thead><tr>";
    $html[]="<th style='width:34%'>{domain}</th>";
    $html[]="<th style='width:22%'>{fz_col_what}</th>";
    $html[]="<th>{fz_col_answered}</th>";
    $html[]="<th style='text-align:right;width:1%'>{fz_col_actions}</th>";
    $html[]="</tr></thead><tbody id='fz-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='4'>".fz_empty_state($m,$search,$page,$function)."</td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} <strong id='fz-shown'>".count($rows)."</strong> {fz_rules_word}</span>";
    $html[]="<span class='awc-pg' id='fz-pager'></span>";
    $html[]="</div></div></div>";

    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=$tpl->ICON_SCRIPTS_COMPILE();
    // La fonction de rafraichissement de search_block est aliasee sur un nom
    // fixe: les mutations rechargent le tableau en conservant le filtre.
    if($function!=""){$html[]="if(typeof $function==='function'){window.FzoneReload=$function;}";}
    $html[]="window.FzoneReload=window.FzoneReload||function(){};";
    $html[]=fz_tiny($tpl);
    $html[]="</script>";

    echo awc_table_style().fzone_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .fz_table_js(50);
    return true;
}

/** « Add a rule » : la fenetre de choix (unbound seulement ; sans lui, une seule nature existe). */
function add_rule_js():bool{
    if(unbound_meta_refuse_js_collection("forward_zones")){return false;}
    $tpl=new template_admin();
    if(!fz_can_edit()){
        header("content-type: application/x-javascript");
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $function=fz_function();
    $page=CurrentPageName();
    return $tpl->js_dialog3("{fz_choose_title}","$page?add-rule-popup=yes&function=$function",720);
}

/** Quatre tuiles : chacune ferme la fenetre de choix et ouvre la fenetre existante de sa nature. */
function add_rule_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=fz_function();
    $m=fz_model();
    $tiles=array(
        array("forward","Loadjs('$page?zone-id-js=0&function=$function')","{fz_tile_forward}","corp.local &rarr; 10.0.0.10",false),
        array("multi","Loadjs('$page?multidomains-js=0&function=$function')","{fz_tile_multi}","intranet.lan, erp.lan &rarr; 10.0.0.10, 10.0.0.11",false),
        array("transparent","Loadjs('$page?transparent-js=yes&function=$function')","{fz_tile_transparent}","168.192.in-addr.arpa",true),
    );
    if($m["dnssec"]){
        $tiles[]=array("dnssec","Loadjs('$page?dnssec-js=yes&function=$function')","{fz_tile_dnssec}","legacy-vendor.org",true);
    }
    $k=fz_kinds();
    $h=array();
    $h[]="<div class='ak fz-chooser'>";
    $h[]="<p class='fz-chooser-p'>{fz_choose_explain}</p>";
    $h[]="<div class='fz-tiles'>";
    foreach($tiles as $t){
        $d=$k[$t[0]];
        $rare=$t[4]?"<span class='ak-tag ".($t[0]==="dnssec"?"ak-warn":"ak-off")." fz-rare'>{fz_rarely_needed}</span>":"";
        $h[]="<button type='button' class='fz-tile' OnClick=\"dialogInstance3.close();{$t[1]};\">";
        $h[]="<span class='fz-tile-top'><span class='ak-ico ak-ico-sm {$d[4]}'><i class='{$d[0]}'></i></span>$rare</span>";
        $h[]="<span class='fz-tile-t'>{$d[1]}</span><span class='fz-tech'>{$d[2]}</span>";
        $h[]="<span class='fz-tile-p'>{$t[2]}</span>";
        $h[]="<span class='fz-tile-ex'><span class='fz-tile-exl'>{fz_example}</span><span class='ak-mono'>{$t[3]}</span></span>";
        $h[]="</button>";
    }
    $h[]="</div></div>";
    echo fzone_style().$tpl->_ENGINE_parse_body(@implode("\n",$h));
    return true;
}








/** Bouton « Reconfigure service » : la cible suit le moteur DNS actif. */
function fz_restart_js($tpl):string{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $clean="document.getElementById('dnsfw-zone-restart').innerHTML=''";
    $js="blur();";
    if(intval($S->GET_INFO("PowerDNSEnableRecursor"))==1){
        $js=$tpl->framework_buildjs("pdns.php?reload-recusor=yes","recusor.restart.progress","recusor.restart.log",
            "dnsfw-zone-restart",$clean,null,null,"AsDnsAdministrator");
    }
    if(intval($S->GET_INFO("DoNotUseLocalDNSCache"))==0){
        $js="Loadjs('fw.dns.servers.php?restart-localdnscache');$clean";
    }
    if(intval($S->GET_INFO("UnboundEnabled"))==1){
        $js=$tpl->framework_buildjs("/unbound/control/reconfigure","unbound.reconfigure.progress","unbound.reconfigure.log",
            "dnsfw-zone-restart",$clean,null,null,"AsDnsAdministrator");
    }
    if(intval($S->GET_INFO("EnableDNSDist"))==1){
        $js=$tpl->framework_buildjs("/dnsfw/service/php/restart","dnsdist.restart","dnsdist.restart.log",
            "dnsfw-zone-restart",$clean,null,null,"AsDnsAdministrator");
    }
    return $js;
}



/**
 * Filtre par nature + pagination + depli des groupes, dans un seul script :
 * awc_pager_js() reafficherait les lignes masquees par le filtre et les
 * sous-lignes repliees. Le filtre choisi survit au rechargement (window.FzKind).
 */
function fz_table_js($size):string{
    $js='<script>
(function(){
 var tb=document.getElementById("fz-rows"); if(!tb){return;}
 var box=document.getElementById("fz-pager"), shown=document.getElementById("fz-shown");
 var size=__SIZE__, cur=1;
 var chips=document.querySelectorAll(".fz-fchip");
 var kind=window.FzKind||"all", found=false;
 for(var c=0;c<chips.length;c++){ if(chips[c].getAttribute("data-kind")===kind){found=true;} }
 if(!found){kind="all";}
 function mains(){ var o=[]; for(var i=0;i<tb.rows.length;i++){ if((" "+tb.rows[i].className+" ").indexOf(" fz-row ")>=0){o.push(tb.rows[i]);} } return o; }
 function sub(r){ return document.getElementById("fz-sub-"+r.getAttribute("data-id")); }
 function pressChips(){ for(var c=0;c<chips.length;c++){ var on=chips[c].getAttribute("data-kind")===kind; chips[c].setAttribute("aria-pressed",on?"true":"false"); chips[c].className=chips[c].className.replace(/\s*\bon\b/g,"")+(on?" on":""); } }
 function draw(){
   var all=mains(), vis=[], i, s;
   if(all.length===0){ if(box){box.innerHTML="";} return; }
   for(i=0;i<all.length;i++){ if(kind==="all"||all[i].getAttribute("data-kind")===kind){vis.push(all[i]);} }
   var pages=Math.max(1,Math.ceil(vis.length/size)); if(cur>pages){cur=pages;}
   for(i=0;i<all.length;i++){ all[i].style.display="none"; s=sub(all[i]); if(s){s.style.display="none";} }
   for(i=0;i<vis.length;i++){
     if(i<(cur-1)*size || i>=cur*size){continue;}
     vis[i].style.display=""; s=sub(vis[i]);
     if(s && vis[i].getAttribute("data-open")==="1"){s.style.display="";}
   }
   if(shown){shown.innerHTML=String(vis.length);}
   if(!box){return;}
   if(pages<=1){box.innerHTML="";return;}
   var h="";
   h+="<a href=\"javascript:blur();\" class=\""+(cur==1?"awc-pg-off":"")+"\" data-p=\""+(cur-1)+"\" aria-label=\"&lsaquo;\">&lsaquo;</a>";
   var end=Math.min(pages,cur+2), start=Math.max(1,end-4); end=Math.min(pages,start+4);
   for(var p=start;p<=end;p++){ h+="<a href=\"javascript:blur();\" class=\""+(p==cur?"awc-pg-cur":"")+"\" data-p=\""+p+"\">"+p+"</a>"; }
   h+="<a href=\"javascript:blur();\" class=\""+(cur==pages?"awc-pg-off":"")+"\" data-p=\""+(cur+1)+"\" aria-label=\"&rsaquo;\">&rsaquo;</a>";
   box.innerHTML=h;
   var as=box.getElementsByTagName("a");
   for(var k=0;k<as.length;k++){ as[k].onclick=function(){ var n=parseInt(this.getAttribute("data-p"),10); if(n>=1&&n<=pages&&n!=cur){cur=n;draw();} return false; }; }
 }
 window.FzFilter=function(btn){ kind=btn.getAttribute("data-kind")||"all"; window.FzKind=kind; cur=1; pressChips(); draw(); };
 window.FzToggle=function(id,btn){
   var r=null, all=mains();
   for(var i=0;i<all.length;i++){ if(all[i].getAttribute("data-id")===String(id)){r=all[i];} }
   if(!r){return;}
   var open=r.getAttribute("data-open")==="1"?"0":"1";
   r.setAttribute("data-open",open); btn.setAttribute("aria-expanded",open==="1"?"true":"false");
   draw();
 };
 pressChips(); draw();
})();
</script>';
    return str_replace("__SIZE__",intval($size),$js);
}

/**
 * CSS propre a la page (prefixe fz-). Le socle du tableau vient de awc_table_style(),
 * les etiquettes, puces, boutons et bandeaux du kit ak- (angular.css.php).
 * A concatener AUTOUR du parsing, jamais dedans.
 */
function fzone_style():string{
    if(isset($GLOBALS["FZONE_STYLE_DONE"])){return "";}
    $GLOBALS["FZONE_STYLE_DONE"]=true;
    $accent="#10b981";$dark="#006a62";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.fz-hero{position:relative;display:flex;align-items:center;gap:24px;background:#fff;border:1px solid #dfe5ea;border-left:4px solid $accent;border-radius:12px;padding:24px;margin:0 0 16px 0;box-shadow:0 1px 2px rgba(16,24,40,.06);color:#111d23}
.fz-hero-ico{width:56px;height:56px;border-radius:50%;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:24px;flex:0 0 auto}
.fz-hero-main{flex:1 1 auto;min-width:0}
.fz-hero-t{font-size:20px;font-weight:600;line-height:28px;margin:0 0 6px 0;color:#111d23}
.fz-hero-p{font-size:14px;line-height:22px;color:#3d4947;margin:0 0 12px 0;max-width:880px}
.fz-hero-chips{display:flex;flex-wrap:wrap;gap:8px}
.fz-hero-chip{display:inline-flex;align-items:center;gap:7px;background:#eef1f3;color:#3d4947;border-radius:999px;padding:5px 12px;font-size:13px;font-weight:500}
.fz-hero-chip svg,.fz-hero-chip i{color:$dark}
.fz-hero-acts{display:flex;flex-direction:column;gap:10px;flex:0 0 auto;min-width:210px}
.fz-hero-acts .ak-btn{justify-content:center}
.fz-flow{display:grid;grid-template-columns:1fr 48px 1fr;gap:0;align-items:stretch;margin:0 0 6px 0}
.fz-flow-one{grid-template-columns:1fr}
.fz-flow-arrow{display:flex;align-items:center;justify-content:center}
.fz-flow-arrow span{width:36px;height:36px;border-radius:50%;border:1px solid #dfe5ea;background:#fff;color:#6d7a77;display:inline-flex;align-items:center;justify-content:center}
.fz-step{padding:20px 24px;display:flex;flex-direction:column;gap:12px;color:#111d23}
.fz-step-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.fz-step-btn{flex:0 0 auto}
.fz-kick{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.fz-kick-sub{font-weight:400;letter-spacing:0;text-transform:none;color:#6d7a77}
.fz-num{width:22px;height:22px;border-radius:50%;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:13px;letter-spacing:0}
.fz-big{display:flex;align-items:baseline;gap:8px}
.fz-big-n{font-size:32px;font-weight:700;line-height:36px;font-variant-numeric:tabular-nums}
.fz-big-w{font-size:18px;font-weight:600;color:#3d4947}
.fz-bds{display:flex;flex-wrap:wrap;gap:6px 16px}
.fz-bd{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#3d4947}
.fz-bd svg,.fz-bd i{color:#6d7a77}
.fz-step-p{font-size:13px;color:#3d4947;margin:auto 0 0 0}
.fz-step-warn{margin:0;padding:10px 14px;font-size:13px}
.fz-route-vals{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.fz-ro{margin:0 0 12px 0;padding:12px 18px;font-size:13px}
.fz-ro-link{font-weight:700;color:#663e00;text-decoration:underline;white-space:nowrap}
.fz-mono{font-variant-numeric:tabular-nums;font-weight:600}
.fz-toolbar{display:flex;justify-content:flex-end;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #dfe5ea}
.fz-chips{flex-wrap:wrap}
.fz-chips .ak-seg-b{display:inline-flex;align-items:center;gap:6px}
.fz-tbl tbody td{padding:14px 20px;vertical-align:top}
.fz-zone{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.fz-zone-name{font-size:15px;font-weight:600;color:#111d23}
.fz-zone-name a{color:#111d23;text-decoration:none}
.fz-zone-name a:hover{color:$dark;text-decoration:underline}
.fz-dcount{display:inline-flex;align-items:center;gap:6px;border:1px solid #dfe5ea;background:#fff;border-radius:6px;padding:3px 10px;font-size:13px;font-weight:600;color:#3d4947;cursor:pointer}
.fz-dcount:hover{border-color:$dark;color:$dark}
.fz-dcount svg{transition:transform .15s}
.fz-dcount[aria-expanded=true] svg{transform:rotate(180deg)}
.fz-type,.fz-tag{display:inline-flex;align-items:center;gap:6px;font-weight:600}
.fz-tech{font-size:13px;color:#3d4947;margin-top:4px}
.fz-ups{display:flex;flex-direction:column;gap:6px}
.fz-ups-l{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.fz-arrow{color:#6d7a77;margin-right:2px}
.fz-auto{color:#3d4947;font-style:italic;font-size:13px}
.fz-act{border:0;background:transparent;cursor:pointer}
.fz-sub td{background:#f8fafb;padding:10px 20px 14px 20px!important}
.fz-sub-in{display:flex;flex-wrap:wrap;gap:6px;align-items:center;padding-left:14px}
.fz-sub-ico{color:#6d7a77}
.fz-sub-label{font-size:13px;font-weight:600;margin-right:6px;color:#111d23}
.fz-empty-title{font-size:15px;font-weight:600;color:#111d23}
.fz-empty-sub{font-size:13px;max-width:520px}
.fz-empty .ak-btn{margin-top:10px}
.fz-chooser-p{font-size:14px;color:#3d4947;margin:0 0 16px 0}
.fz-tiles{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fz-tile{display:flex;flex-direction:column;align-items:flex-start;gap:4px;text-align:left;background:#fff;border:1px solid #dfe5ea;border-radius:8px;padding:16px;cursor:pointer;color:#111d23;transition:border-color .15s,box-shadow .15s}
.fz-tile:hover{border-color:$dark;box-shadow:0 0 0 3px $soft}
.fz-tile-top{display:flex;justify-content:space-between;align-items:center;width:100%;margin-bottom:6px}
.fz-tile-t{font-size:15px;font-weight:600}
.fz-tile-p{font-size:13px;line-height:19px;color:#3d4947;margin-top:6px}
.fz-tile-ex{display:flex;flex-direction:column;gap:2px;margin-top:auto;padding-top:10px;font-size:13px;color:#111d23}
.fz-tile-exl{font-size:13px;font-weight:600;color:#3d4947;text-transform:uppercase;letter-spacing:.05em}
.fz-scope a:focus-visible,.fz-dcount:focus-visible,.fz-ro a:focus-visible,.fz-tile:focus-visible,.fz-hero .ak-btn:focus-visible,.fz-step-btn:focus-visible{outline:2px solid $dark;outline-offset:2px}
@media(max-width:1200px){.fz-hero{flex-wrap:wrap}.fz-hero-acts{flex-direction:row;min-width:0;width:100%}.fz-flow{grid-template-columns:1fr;gap:12px}.fz-flow-arrow{display:none}.fz-toolbar{justify-content:flex-start}}
@media(prefers-reduced-motion:reduce){.fz-dcount svg,.fz-tile{transition:none}}
</style>";
}

function unbound_unknown_domains_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // Aucune route de l'API : ancien chemin, sous privilege et refus esclave ;
    // chaque serveur est recopie dans la configuration d'unbound (forward-addr).
    $refusal=fz_legacy_refusal($tpl);
    if($refusal!==""){ echo $tpl->post_error($refusal);return false;}
    // /setinfo ne connait pas le verrou Meta : refus si l'une des deux cles est tenue.
    $meta=unbound_meta_refuse_keys(fz_unknown_keys());
    if($meta!==""){ echo $meta;return false;}
    foreach(array("DNS1","DNS2","DNS3","DNS4") as $k){
        if(!isset($_POST[$k])){continue;}
        $_POST[$k]=trim((string)$_POST[$k]);
        if($_POST[$k]!=="" && !fz_forward_addr_ok($_POST[$k])){
            echo $tpl->post_error("$k: {wrong_value}");
            return false;
        }
    }
    $tt=array();
    if(isset($_POST["UnboundUseSystemDNS"])) {
        if ($_POST["UnboundUseSystemDNS"] == 1) {
            $tt[]="Use System DNS";
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEnforceUnknownDomains", 0);
        } else {
            $tt[]="Use specific DNS";
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEnforceUnknownDomains", 1);
        }
    }

    $UnboundEnforcedDomains=explode(";",base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnforcedDomains")));
    if(isset($_POST["DNS1"])) {
        $UnboundEnforcedDomains[0]=$_POST["DNS1"];
        $tt[]="Set DNS 1: $UnboundEnforcedDomains[0]";
    }
    if(isset($_POST["DNS2"])) {
        $UnboundEnforcedDomains[1]=$_POST["DNS2"];
        $tt[]="Set DNS 2: $UnboundEnforcedDomains[1]";
    }
    if(isset($_POST["DNS3"])) {
        $UnboundEnforcedDomains[2]=$_POST["DNS3"];
        $tt[]="Set DNS 3: $UnboundEnforcedDomains[2]";
    }
    if(isset($_POST["DNS4"])) {
        $UnboundEnforcedDomains[3]=$_POST["DNS4"];
        $tt[]="Set DNS 4: $UnboundEnforcedDomains[3]";
    }
    $UnboundEnforcedDomainsNew=base64_encode(@implode(";",$UnboundEnforcedDomains));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEnforcedDomains",$UnboundEnforcedDomainsNew);
    if(count($tt)>0) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/build/zones");
    }
    return admin_tracks("Save DNS unknown domains rule to ".@implode(" ",$tt));
}

function unbound_unknown_domains_start():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist XSS
    $page=CurrentPageName();
    echo "<div id='unbound-unknown-domains'></div>";
    echo "<script>LoadAjax('unbound-unknown-domains','$page?unbound-unknown-domains-popup=yes&function=$function');</script>";
    return true;
}
function unbound_unknown_domains_popup():bool{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? "")); // whitelist XSS
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UnboundEnforceUnknownDomains=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnforceUnknownDomains"));
    $UnboundUseSystemDNS=1;
    if($UnboundEnforceUnknownDomains==1){
        $UnboundUseSystemDNS=0;
    }


    // Apres chaque enregistrement : le refus (esclave, adresse invalide) s'affiche,
    // puis la carte « Unknown domains » de la page se redessine.
    $after="if(String(resp).indexOf('jserror:')===0){".unbound_api::swal_js("resp")."}"
        ."if(typeof FzContextReload==='function'){FzContextReload();}";
    $form[]=$tpl->BigCircleCheckbox("UnboundUseSystemDNS","{SambaDnsProxy}","{UnboundUseSystemDNS}",$UnboundUseSystemDNS,$after."LoadAjax('unbound-unknown-domains','$page?unbound-unknown-domains-popup=yes&function=$function');");
    if($UnboundUseSystemDNS==1){
        echo @implode("\n",$form); // Big* traduit deja ; son JS ne doit pas passer par le moteur de jetons
        return true;
    }

    $UnboundEnforcedDomains=explode(";",base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnforcedDomains")));


    if(!isset($UnboundEnforcedDomains[0])){
        $UnboundEnforcedDomains[0]="";
    }
    if(!isset($UnboundEnforcedDomains[1])){
        $UnboundEnforcedDomains[1]="";
    }
    if(!isset($UnboundEnforcedDomains[2])){
        $UnboundEnforcedDomains[2]="";
    }
    if(!isset($UnboundEnforcedDomains[3])){
        $UnboundEnforcedDomains[3]="";
    }

    $js="dialogInstance1.close();$function();";

    $form[]=$tpl->BigNetworkField("DNS1","{primary_dns}","",$UnboundEnforcedDomains[0],"1.1.1.1",$after);
    $form[]=$tpl->BigNetworkField("DNS2","{secondary_dns}","",$UnboundEnforcedDomains[1],"9.9.9.9",$after);
    $form[]=$tpl->BigNetworkField("DNS3","{DNSServer} 3","",$UnboundEnforcedDomains[2],"8.8.8.8",$after);
    $form[]=$tpl->BigNetworkField("DNS4","{DNSServer} 4","",$UnboundEnforcedDomains[3],"8.8.4.4",$after);
    echo @implode("\n",$form); // Big* traduit deja ; son JS ne doit pas passer par le moteur de jetons
    return true;

}