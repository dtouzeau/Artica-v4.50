<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Client des routes /unbound/api/* (spec 019 lot 4c) : validation, application et relecture par articarest.
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();
// Verrou d'une politique Meta dnscache : collection records (lecture seule, refus cote serveur).
include_once(dirname(__FILE__)."/ressources/class.unbound.meta.inc");
// Avant l'aiguillage : main() -> unbrec_model() s'en sert avant d'atteindre la fin du fichier (fatal en PHP 8).
define("UNBREC_PAGE_SIZE",50);
if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["record-js"])){record_js();exit;}
if(isset($_GET["record-info-js"])){record_info_js();exit;}
if(isset($_GET["record-info"])){record_info();exit;}
if(isset($_GET["record-popup"])){record_popup();exit;}
// Activation/desactivation : *-js (GET, rend du JavaScript seulement) puis POST.
if(isset($_GET["enable-js"])){record_enable_js();exit;}
if(isset($_POST["record-toggle"])){record_toggle_save();exit;}
// Constats non bloquants (avertissements, notes) d'une ecriture reussie.
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["td-column"])){td_column();exit;}

if(isset($_POST["id"])){record_save();exit;}
if(isset($_GET["id-deleted"])){deleted_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){record_delete();exit;}
if(isset($_GET["deletemem-js"])){record_deletem();exit;}
if(isset($_POST["deletemem"])){record_deletem_save();exit;}

if(isset($_GET["synchronize-js"])){synchronize_js();exit;}
if(isset($_GET["synchronize-popup"])){synchronize_popup();exit;}

if(isset($_GET["activedirectory-js"])){activedirectory_js();exit;}
if(isset($_GET["activedirectory-popup"])){activedirectory_popup();exit;}
if(isset($_POST["CNX"])){activedirectory_save();exit;}
page();

/**
 * Le nom de la fonction de rafraichissement transmis dans l'URL.
 *
 * Il finit dans des URL, dans des attributs OnClick et dans des corps de
 * fonction JavaScript, partout SANS echappement: un "?function=..." forge
 * s'executait donc dans la session de l'administrateur. search_block n'engendre
 * jamais autre chose que "ss<time>", la liste blanche ne peut rien casser de
 * legitime.
 *
 * Renvoie "" quand il n'y a rien a appeler -- les appelants doivent tester,
 * car "$function();" avec un nom vide produit le litteral "();", une
 * SyntaxError qui avorte TOUT le bloc <script> ou elle se trouve.
 */
function unbound_rec_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}

/** L'appel de rafraichissement, ou une chaine vide s'il n'y a rien a appeler. */
function unbound_rec_call():string{
    $fname=unbound_rec_function();
    return ($fname==="")?"":"$fname();";
}

/**
 * Controle cote serveur de chaque handler qui ecrit : form_outside et
 * icon_delete ne font que MASQUER le bouton. Le refus d'un esclave du cluster
 * vient d'articarest (409, adresse et console du maitre) ou, pour les chemins
 * historiques, de unbound_api::legacy_write_refusal().
 */
function unbound_rec_can_write():bool{
    $tpl=new template_admin();
    return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}

/**
 * Refus d'une ecriture d'enregistrement tenue par une politique Meta, au format
 * post_error() ("" si libre). Le chemin historique (/dnscache/record, INSERT AD)
 * et l'API repondent aussi 423, mais la page refuse avant d'ecrire quoi que ce soit.
 */
function unbound_rec_meta_refusal():string{
    return unbound_meta_refuse_collection("records");
}

/** Le JavaScript qui montre, sans bloquer, les constats gardes en session. */
function unbound_rec_notices_call():string{
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
 * Les PTR exacts d'une adresse, lus par l'API (le filtre « name » de la route
 * est un « contient » : on ne garde que le nom identique).
 */
function unbound_rec_ptr_rows($api,$ip,&$notes):array{
    $arpa=unbound_api::ptr_name($ip);
    if($arpa===""){return array();}
    $r=$api->get("/unbound/api/records",array("name"=>$arpa,"type"=>"PTR"));
    if(!$r["ok"]){
        $notes[]="{unbound_ptr_not_synced}: ".$r["error"];
        return array();
    }
    $out=array();
    $rows=(is_array($r["data"]) && isset($r["data"]["records"]) && is_array($r["data"]["records"]))?$r["data"]["records"]:array();
    foreach($rows as $p){
        if(!is_array($p)){continue;}
        if(strcasecmp(rtrim((string)$p["name"],"."),$arpa)!=0){continue;}
        $out[]=$p;
    }
    return $out;
}

/**
 * Le PTR d'un A/AAAA, comme l'ancienne route /dnscache/record (CreatePTR) mais
 * par l'API : cree ou repointe le PTR de l'adresse vers l'hote quand $want,
 * retire celui qui pointe vers l'hote sinon, et retire le PTR de l'ANCIENNE
 * adresse qui designait l'hote quand l'adresse ou le nom a change. Rend les
 * constats (jamais bloquants : l'enregistrement principal est deja en place).
 */
function unbound_rec_sync_ptr($api,$host,$ip,$ttl,$want,$oldhost,$oldip):array{
    $notes=array();
    $host=rtrim(strtolower((string)$host),".");
    $oldhost=rtrim(strtolower((string)$oldhost),".");
    if($oldip!=="" && $oldhost!=="" && ($oldip!==$ip || $oldhost!==$host)){
        foreach(unbound_rec_ptr_rows($api,$oldip,$notes) as $p){
            if(strcasecmp(rtrim((string)$p["content"],"."),$oldhost)!=0){continue;}
            $d=$api->delete("/unbound/api/records/".intval($p["id"]));
            if(!$d["ok"]){$notes[]="{unbound_ptr_not_synced}: ".$d["error"];continue;}
            $notes=array_merge($notes,unbound_api::notices($d));
        }
    }
    if(unbound_api::ptr_name($ip)===""){return $notes;}
    $mine=null;$other=null;
    foreach(unbound_rec_ptr_rows($api,$ip,$notes) as $p){
        if(strcasecmp(rtrim((string)$p["content"],"."),$host)==0){$mine=$p;continue;}
        if($other===null){$other=$p;}
    }
    $r=null;
    if($want){
        if($mine!==null){return $notes;}
        if($other!==null){
            $r=$api->put("/unbound/api/records/".intval($other["id"]),array("name"=>(string)$other["name"],
                "type"=>"PTR","content"=>$host,"ttl"=>intval($ttl),"prio"=>intval($other["prio"])));
        }else{
            $r=$api->post("/unbound/api/records",array("name"=>$ip,"type"=>"PTR","content"=>$host,"ttl"=>intval($ttl),"prio"=>0));
        }
    }elseif($mine!==null){
        $r=$api->delete("/unbound/api/records/".intval($mine["id"]));
    }
    if($r!==null){
        if(!$r["ok"]){$notes[]="{unbound_ptr_not_synced}: ".$r["error"];}
        else{$notes=array_merge($notes,unbound_api::notices($r));}
    }
    return $notes;
}

/**
 * Saisie d'un chemin historique (AD, SOA, types exotiques, noms joker) :
 * l'API ne la valide pas, la console refuse au moins ce qui casserait la
 * configuration d'unbound (caracteres de controle, nom hors alphabet DNS).
 */
function unbound_rec_legacy_input_ok($name,$content):bool{
    $name=(string)$name;$content=(string)$content;
    if($name==="" || strlen($name)>253 || !preg_match('/^[A-Za-z0-9_.:*\-]+$/',$name)){return false;}
    if($content==="" || strlen($content)>4096 || preg_match('/[\x00-\x1f\x7f]/',$content)){return false;}
    return true;
}

/**
 * SRV saisi sous la forme standard « priorite poids port cible » : la priorite
 * passe dans prio, le reste dans le contenu (l'API n'accepte que trois champs).
 * Toute autre forme est rendue telle quelle (l'API dira ce qui ne va pas).
 */
function unbound_rec_split_srv($content,$prio):array{
    $f=preg_split('/\s+/',trim((string)$content));
    if(count($f)==4 && ctype_digit($f[0]) && ctype_digit($f[1]) && ctype_digit($f[2])){
        return array($f[1]." ".$f[2]." ".$f[3],intval($f[0]));
    }
    return array((string)$content,intval($prio));
}

/** Activation/desactivation : *-js (rend du JavaScript, n'ecrit rien) -> POST record-toggle. */
function record_enable_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["enable-js"]);
    header("content-type: application/x-javascript");
    if(!unbound_rec_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS2}");return; }
    if(unbound_meta_collection_locked("records")){ $tpl->js_error("{unbound_meta_managed_locked}");return; }
    $refresh=unbound_rec_call();
    if($refresh===""){$refresh="if(document.getElementById('table-dns-records-loader')){LoadAjax('table-dns-records-loader','$page?main=yes');}";}
    $notices=unbound_rec_notices_call();
    $t=time().$id;
    $js=array();
    $js[]="var xRecToggle$t=function(obj){";
    $js[]="\tvar res=obj.responseText;";
    $js[]="\tif(res.length>3){";
    // post_error() rend du HTML deja echappe (seuls br/strong... restent) ;
    // tout autre corps (page 504 de nginx, notice PHP) est affiche en texte.
    $js[]="\t\t".unbound_api::swal_js("res");
    $js[]="\t}";
    $js[]="\t$refresh";
    $js[]="\t$notices";
    $js[]="};";
    $js[]="var XHR=new XHRConnection();";
    $js[]="XHR.appendData('record-toggle','$id');";
    $js[]="XHR.sendAndLoad('$page','POST',xRecToggle$t);";
    echo implode("\n",$js);
}

function record_toggle_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!unbound_rec_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $meta=unbound_rec_meta_refusal();
    if($meta!==""){ echo $meta;return false; }
    $id=intval($_POST["record-toggle"]);
    if($id<=0){ echo $tpl->post_error("ID: {wrong_value}");return false; }
    $api=new unbound_api();
    $g=$api->get("/unbound/api/records/$id");
    if(!$g["ok"]){ echo $tpl->post_error(unbound_api::error_message($g));return false; }
    $rec=is_array($g["data"])?$g["data"]:array();
    if(!unbound_api::record_covered(isset($rec["type"])?$rec["type"]:"",isset($rec["name"])?$rec["name"]:"")){
        echo $tpl->post_error("{unbound_record_legacy_toggle}");
        return false;
    }
    $action=empty($rec["enabled"])?"enable":"disable";
    $r=$api->post("/unbound/api/records/$id/$action");
    if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false; }
    unbound_api::queue($r);
    return admin_tracks("DNS record $id {$rec["type"]} {$rec["name"]}: $action");
}

function activedirectory_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(unbound_meta_collection_locked("records")){
        header("content-type: application/x-javascript");
        $tpl->js_error("{unbound_meta_managed_locked}");
        return;
    }
    $title=$tpl->javascript_parse_text("Active Directory: {caches_rules}");
    $tpl->js_dialog7($title, "$page?activedirectory-popup=yes&function=".unbound_rec_function(),810);
}
function record_deletem():bool{
    $tpl=new template_admin();
    // Le nom vient du cache, donc du trafic des clients, et part dans du HTML
    // (texte de confirmation) ET dans une chaine JavaScript (valeur postee) :
    // seul un nom DNS passe ; md n'est qu'une empreinte md5 (identifiant de ligne).
    $Name=unbound_rec_cache_name(isset($_GET["deletemem-js"])?$_GET["deletemem-js"]:"");
    $md=strtolower((string)(isset($_GET["md"])?$_GET["md"]:""));
    if($Name==="" || !preg_match('/^[a-f0-9]{32}$/',$md)){
        header("content-type: application/x-javascript");
        return $tpl->js_error("{wrong_value}");
    }
    return $tpl->js_confirm_delete(htmlspecialchars($Name,ENT_QUOTES),"deletemem",$Name,"$('#$md').remove();");
}
/** Nom d'une entree du cache acceptable dans du HTML et du JavaScript ("" sinon). */
function unbound_rec_cache_name($name):string{
    $name=(string)$name;
    if($name==="."){return $name;}
    if(strlen($name)>253 || !preg_match('/^[A-Za-z0-9_*.\-]+$/',$name)){return "";}
    return $name;
}
/**
 * Type d'enregistrement d'une URL (record-info) : il part dans le titre du
 * formulaire, un champ cache et le titre du dialogue. Seuls les types connus
 * de la page passent ("" sinon).
 */
function unbound_rec_type_ok($type):string{
    $type=trim((string)$type);
    if(!preg_match('/^[A-Za-z0-9]{1,16}$/',$type)){return "";}
    $known=array_merge(GetRtypes(),array("AD","TKEY"));
    if(!in_array(strtoupper($type),$known,true)){return "";}
    return $type;
}
function record_deletem_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!unbound_rec_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    // /dnscache/delete/memrecord est tenu avec records (ruling D8 du daemon).
    $meta=unbound_rec_meta_refusal();
    if($meta!==""){ echo $meta;return false; }
    $Name=(string)$_POST["deletemem"];
    if($Name=="."){
        $Name="POINT";
    }
    $sock=new sockets();
    // Le nom vient du trafic DNS : encode comme segment de chemin.
    $data=$sock->REST_API("/dnscache/delete/memrecord/".rawurlencode($Name));

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error("API ERROR $sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Delete DNS record $Name");
}


function activedirectory_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ACTIVE_DIRECTORY_LDAP_CONNECTIONS=$tpl->ACTIVE_DIRECTORY_LDAP_CONNECTIONS();
    $CNS=array();
    // Sans &function= dans l'URL, l'ancienne concatenation produisait
    // "();dialogInstance7.close();" -- une SyntaxError qui emportait tout le
    // bloc, y compris la fermeture de la modale.
    $function=unbound_rec_call()."dialogInstance7.close();";
    foreach ($ACTIVE_DIRECTORY_LDAP_CONNECTIONS as $md5=>$array){
        $port=$array["LDAP_PORT"];
        $server=$array["LDAP_SERVER"];
        $dn=$array["LDAP_DN"];
        $CNS[$md5]="$server:$port ($dn)";

    }
    $server_ip=$GLOBALS["CLASS_SOCKETS"]->gethostbyname($server);
    $form[]=$tpl->field_array_hash($CNS,"CNX","#nonull:{activedirectory_connection}");
    $form[]=$tpl->field_ipv4("ADIP","{activedirectory_addr}",$server_ip,true);
    $form[]=$tpl->field_numeric("ttl","TTL ({seconds})",3600,"{DNS_TTL_EXPLAIN}");

    // form_outside() ignore son 3e argument (explication) : elle est montree par div_explain (skill §11).
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("{unbound_ad_explain}<br>{unbound_record_legacy_path}")
        .$tpl->form_outside("Active Directory: {caches_rules}", @implode("\n", $form),"{unbound_ad_explain}<br>{unbound_record_legacy_path}","{create}","$function","AsDnsAdministrator"));


}

function record_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_record}");
	$id=intval($_GET["record-js"]);
	if($id==0 && unbound_meta_collection_locked("records")){
		header("content-type: application/x-javascript");
		$tpl->js_error("{unbound_meta_managed_locked}");
		return;
	}
	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
		$sql="SELECT name,`type`,content FROM records WHERE id=$id";
		$ligne=$q->mysqli_fetch_array($sql);
		$ligne["content"]=substr($ligne["content"],0,20);
		$title="{$ligne["name"]}::{$ligne["type"]}::{$ligne["content"]}...";
		
	}
	$tpl->js_dialog7($title, "$page?record-popup=$id&function=".unbound_rec_function(),810);

	
}
function synchronize_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{synchronize}");
    $tpl->js_dialog7($title, "$page?synchronize-popup=yes&function=".unbound_rec_function(),810);
}

function synchronize_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $CacheDNSRemoteSynchronize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteSynchronize"));
    $CacheDNSRemoteServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteServer");
    $CacheDNSRemoteUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteUsername");
    $CacheDNSRemoteServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteServerPort"));
    $CacheDNSRemoteServerPass=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CacheDNSRemoteServerPass"));
    if($CacheDNSRemoteServerPort==0){$CacheDNSRemoteServerPort=9000;}



    $html[]="<div id='powerdns-synchronize' style='margin-bottom:20px'></div>";
    $form[]=$tpl->field_checkbox("CacheDNSRemoteSynchronize","{enable_feature}",$CacheDNSRemoteSynchronize,true);
    $form[]=$tpl->field_text("CacheDNSRemoteServer","{REMOTE_ARTICA_SERVER}",$CacheDNSRemoteServer,true);
    $form[]=$tpl->field_numeric("CacheDNSRemoteServerPort","{REMOTE_ARTICA_SERVER_PORT}",$CacheDNSRemoteServerPort);
    $form[]=$tpl->field_text("CacheDNSRemoteUsername","{REMOTE_ARTICA_USERNAME}",$CacheDNSRemoteUsername);
    $form[]=$tpl->field_password2("CacheDNSRemoteServerPass","{REMOTE_ARTICA_PASSWORD}",$CacheDNSRemoteServerPass);
    $jsafter="";
    $html[]=$tpl->form_outside("{synchronize}", @implode("\n", $form),"{CacheDNSSynchronizeExplain}","{apply}","$jsafter","AsDnsAdministrator");
    $html[]="";
    echo $tpl->_ENGINE_parse_body($html);
}


function record_info_js(){
	$tpl=new template_admin();
	$type=unbound_rec_type_ok(isset($_GET["type"])?$_GET["type"]:"");
	$id=intval(isset($_GET["id"])?$_GET["id"]:0);
	if($type===""){
		header("content-type: application/x-javascript");
		$tpl->js_error("{wrong_value}");
		return;
	}
	$prefix="{new_record} ";
	$title="{$prefix}Type: - $type -";

	$page=CurrentPageName();
	// $type et $id venaient aussi de l'URL sans encodage.
	$tpl->js_dialog7($title, "$page?record-info=yes&type=".urlencode((string)$type)
		."&id=".urlencode((string)$id)."&function=".unbound_rec_function(),810);
	
}
function record_delete():bool{
    $tpl            = new template_admin();
    $tpl->CLEAN_POST();
    if(!unbound_rec_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $meta=unbound_rec_meta_refusal();
    if($meta!==""){ echo $meta;return false; }
	$id=intval($_POST["delete"]);
    if($id<=0){ echo $tpl->post_error("ID: {wrong_value}");return false; }
    $api=new unbound_api();
    $g=$api->get("/unbound/api/records/$id");
    if(!$g["ok"]){ echo $tpl->post_error(unbound_api::error_message($g));return false; }
    $rec=is_array($g["data"])?$g["data"]:array();
    $rtype=isset($rec["type"])?(string)$rec["type"]:"";
    $rname=isset($rec["name"])?(string)$rec["name"]:"";
    if(unbound_api::record_covered($rtype,$rname)){
        $r=$api->delete("/unbound/api/records/$id");
        if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false; }
        unbound_api::queue($r);
        return admin_tracks("Delete DNS record $id $rtype $rname");
    }
    // Chemin historique (AD, SOA, types exotiques, noms joker) : l'API refuse ces
    // lignes. Meme refus que l'API sur un esclave du cluster.
    $refusal=$api->legacy_write_refusal();
    if($refusal!==""){ echo $tpl->post_error($refusal);return false; }
    $sock=new sockets();
    $data=$sock->REST_API("/dnscache/delete/record/$id");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error("API ERROR $sock->mysql_error");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
   return admin_tracks("Delete DNS record $id");
}

function activedirectory_save(){
    $tpl            = new template_admin();
    $tpl->CLEAN_POST();
    // Chemin historique (le type AD n'est pas couvert par /unbound/api/records) :
    // privilege et refus esclave verifies ici, SQL parametre plus bas.
    if(!unbound_rec_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
    $meta=unbound_rec_meta_refusal();
    if($meta!==""){ echo $meta;return false; }
    $refusal=(new unbound_api())->legacy_write_refusal();
    if($refusal!==""){ echo $tpl->post_error($refusal);return false; }

    $CONNECTIONS    = $tpl->ACTIVE_DIRECTORY_LDAP_CONNECTIONS();
    $CNX            = (string)$_POST["CNX"];
    if(!isset($CONNECTIONS[$CNX]) || !is_array($CONNECTIONS[$CNX])){
        echo $tpl->post_error("{activedirectory_connection}: {wrong_value}");
        return false;
    }
    $settings       = $CONNECTIONS[$CNX];
    $ADIP           = trim((string)$_POST["ADIP"]);
    $TTL            = intval($_POST["ttl"]);
    if(filter_var($ADIP,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)===false){
        echo $tpl->post_error("{activedirectory_addr}: {wrong_value}");
        return false;
    }
    if($TTL<60 || $TTL>604800){$TTL=3600;}


    if(!isset($settings["LDAP_SUFFIX"])){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("{ldap_suffix} {missing}"));
        return false;
    }

    $settings["netbios"]=null;
    if(!isset($settings["fullhosname"])){$settings["fullhosname"]=$settings["LDAP_SERVER"];}
    if(!isset($settings["WINDOWS_DNS_SUFFIX"])){
        if(preg_match("#^(.+?)\.(.+)$#",$settings["fullhosname"],$re)){
            $settings["WINDOWS_DNS_SUFFIX"]=$re[2];
            $settings["netbios"]=$re[1];
        }
    }

    if($settings["netbios"]==null){
        if(preg_match("#^(.+?)\.(.+)$#",$settings["fullhosname"],$re)){
            $settings["netbios"]=$re[1];
        }
    }
    if($settings["netbios"]==null){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("Netbios name: {missing} {$settings["fullhosname"]}"));
        return false;
    }
    $LDAP_SUFFIX    = $settings["LDAP_SUFFIX"];
    $NETBIOS        = strtoupper($settings["netbios"]);





    $LDAP_SUFFIX_DNS=strtolower($LDAP_SUFFIX);
    $LDAP_SUFFIX_DNS=str_replace("dc=","",$LDAP_SUFFIX_DNS);
    $LDAP_SUFFIX_DNS=str_replace(",",".",$LDAP_SUFFIX_DNS);
    $nTDSDSA=null;



    $ad=new ActiveDirectory(0,$settings);
    $tty=$ad->ObjectProperty($LDAP_SUFFIX);

    if($ad->ldap_failed){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body($ad->ldap_last_error));
        return false;
    }

    if(!isset($tty["SOURCE"])){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("$LDAP_SUFFIX (objectguid) Array {missing} "));
        return false;
    }

    $SOURCE=$tty["SOURCE"];


    if(!isset($SOURCE["objectguid"])){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("$LDAP_SUFFIX objectguid {missing}"));
        return false;
    }

    $objectguid=_to_p_guid($SOURCE["objectguid"][0]);
    if(!preg_match("#^[0-9a-z\-]+$#",$objectguid)){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("$LDAP_SUFFIX objectguid $objectguid {incorrect}"));
        return false;
    }



    if(!isset($tty["SOURCE"])){
        $nTDSDSA_dn="CN=DNS Settings,CN=$NETBIOS,CN=Servers,CN=Premier-Site-par-defaut,CN=Sites,CN=Configuration,$LDAP_SUFFIX";
        $tty=$ad->ObjectProperty($nTDSDSA_dn);

    }

    if(!isset($tty["SOURCE"])){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("nTDSDSA SOURCE {missing}"));
        return false;
    }

    $SOURCE=$tty["SOURCE"];

    if(!isset($SOURCE["objectguid"])){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("nTDSDSA: objectguid {missing}"));
        return false;
    }

    $nTDSDSA=_to_p_guid($SOURCE["objectguid"][0]);
    if(!preg_match("#^[0-9a-z\-]+$#",$nTDSDSA)){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("nTDSDSA: objectguid $nTDSDSA {incorrect}"));
        return false;
    }
    $NETBIOS=strtolower($NETBIOS);
    // Le suffixe et le nom NetBIOS viennent de l'annuaire : ils partaient
    // concatenes dans l'INSERT et finissent dans la configuration d'unbound.
    if(!unbound_rec_legacy_input_ok($LDAP_SUFFIX_DNS,"$ADIP:$NETBIOS") || !preg_match('/^[a-z0-9_\-]+$/',$NETBIOS)
        || !preg_match('/^[a-z0-9_.\-]+$/',$LDAP_SUFFIX_DNS)){
        echo $tpl->post_error("{ldap_suffix}: {wrong_value}");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $q->QUERY_SQL_PARAMS("INSERT INTO records (name,type,content,ttl,prio) VALUES (?,?,?,?,?)",
        array($LDAP_SUFFIX_DNS,"AD","$ADIP:$NETBIOS:$objectguid:$nTDSDSA",$TTL,0));

    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks("Create a new Active directory DNS record for $LDAP_SUFFIX_DNS with $ADIP:$NETBIOS:$objectguid:$nTDSDSA");

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/build/zones");
    return true;
}

function _to_p_guid( $guid )
{
    $hex_guid = unpack( "H*hex", $guid );
    $hex    = $hex_guid["hex"];

    $hex1   = substr( $hex, -26, 2 ) . substr( $hex, -28, 2 ) . substr( $hex, -30, 2 ) . substr( $hex, -32, 2 );
    $hex2   = substr( $hex, -22, 2 ) . substr( $hex, -24, 2 );
    $hex3   = substr( $hex, -18, 2 ) . substr( $hex, -20, 2 );
    $hex4   = substr( $hex, -16, 4 );
    $hex5   = substr( $hex, -12, 12 );

    $guid = $hex1 . "-" . $hex2 . "-" . $hex3 . "-" . $hex4 . "-" . $hex5;

    return $guid;
}


function record_wizard(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();
	$function=unbound_rec_function();

	// Types courants en tete, le reste dans l'ordre alphabetique de GetRtypes().
	$common=array("A","AAAA","CNAME","MX","PTR","TXT","SRV","NS");
	$types=$common;
	foreach (GetRtypes() as $record_type){
		if(!in_array($record_type,$types,true)){$types[]=$record_type;}
	}

	$tiles=array();
	foreach ($types as $record_type){
		$key="dns_rr_explain_".strtolower($record_type);
		$js="Loadjs('$page?record-info-js=yes&type=".urlencode($record_type)."&id=0&function=$function');";
		$tiles[]="<a class='rrw-tile' href=\"javascript:blur();\" OnClick=\"$js\">"
			."<span class='rrw-type'>$record_type</span>"
			."<span class='rrw-desc'>{{$key}}</span></a>";
	}

	$html[]=$tpl->div_explain("{new_record}||{ADD_DNS_ENTRY_TEXT}");
	$html[]="<div class='rrw-search'><input type='text' class='ak-in' id='rrw-q-$t' placeholder='{search}' autocomplete='off'></div>";
	$html[]="<div class='rrw-grid' id='rrw-grid-$t'>".@implode("",$tiles)."</div>";
	$html[]="<div class='rrw-empty' id='rrw-empty-$t' style='display:none'>{no_data}</div>";

	// CSS et JS hors de _ENGINE_parse_body : ses accolades seraient lues comme des {jetons}.
	echo record_wizard_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).record_wizard_js($t);
}

function record_wizard_css():string{
	$accent="#10b981";$soft="rgba(16,185,129,.12)";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$ac=brand_ui_accents(brand_palette());
		$accent=$ac["accent"];$soft=$ac["accent_soft"];
	}
	// Champ de recherche : ak-in (kit ak-, angular.css.php).
	return "<style>
.rrw-search{margin:10px 0 14px}
.rrw-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;max-height:460px;overflow-y:auto;padding:2px}
.rrw-tile{display:flex;flex-direction:column;gap:4px;padding:12px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;text-decoration:none!important;color:#1e293b;transition:border-color .15s,box-shadow .15s}
.rrw-tile:hover{border-color:$accent;box-shadow:0 2px 8px rgba(15,23,42,.08);background:$soft}
.rrw-type{font-size:16px;font-weight:700;color:#0f172a}
.rrw-desc{font-size:12px;line-height:16px;color:#64748b}
.rrw-empty{padding:24px;text-align:center;color:#64748b}
</style>";
}

function record_wizard_js($t):string{
	$t=intval($t);
	return "<script>
(function(){
	var q=document.getElementById('rrw-q-$t');
	var grid=document.getElementById('rrw-grid-$t');
	var empty=document.getElementById('rrw-empty-$t');
	if(!q || !grid){return;}
	var tiles=grid.getElementsByTagName('a');
	q.onkeyup=function(){
		var v=q.value.toLowerCase().trim(), shown=0;
		for(var i=0;i<tiles.length;i++){
			var ok=(v==='' || tiles[i].textContent.toLowerCase().indexOf(v)>-1);
			tiles[i].style.display=ok?'':'none';
			if(ok){shown++;}
		}
		empty.style.display=shown?'none':'';
	};
	setTimeout(function(){q.focus();},300);
})();
</script>";
}

function record_info(){
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $tpl = new template_admin();
    // Reflete dans le titre du formulaire et un champ cache : types connus seulement.
    $record_type = unbound_rec_type_ok(isset($_GET["type"])?$_GET["type"]:"");
    if($record_type===""){
        echo $tpl->div_error("{wrong_value}");
        return false;
    }
    $page = CurrentPageName();
    $function=unbound_rec_function();
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    VERBOSE("PowerDNSEnableClusterSlave=$PowerDNSEnableClusterSlave", __LINE__);

    $explain_form = null;
    $LABEL_NAME = "{name}";

    $record_type_explain = $record_type;
    $RRCODE_LABEL["SOA"] = "{zone}";
    $RRCODE["NS"] = "Name Server Record";
    $RRCODE["SOA"] = "Start of Authority Record";


    $RRCODE_EXPLAIN["SOA"] = "{SOA_RECORD_EXPLAIN}";
    $RRCODE_EXPLAIN["NS"] = "{NS_RECORD_EXPLAIN}";

    if (isset($RRCODE[$record_type])) {
        $record_type_explain = $RRCODE[$record_type];
    }
    if (isset($RRCODE_EXPLAIN[$record_type])) {
        $explain_form = $RRCODE_EXPLAIN[$record_type];
    }
    if (isset($RRCODE_LABEL[$record_type])) {
        $LABEL_NAME = $RRCODE_LABEL[$record_type];
    }
    // $functionToLoad est interpole dans $jsafter entre deux points-virgules:
    // il doit rester VIDE quand aucune fonction n'est fournie, sinon "();"
    // avorte tout le bloc.
    $functionToLoad = unbound_rec_call();

    $title = "$record_type_explain";
    $SHOW_TTL = true;
    $id = intval($_GET["id"]);
    $btname = "{add}";
    $jsafter = "dialogInstance7.close();{$functionToLoad}Loadjs('$page?filltable=$id&function=$function');".unbound_rec_notices_call();


    if ( (strtoupper($record_type) == "TXT") or (strtoupper($record_type) == "SPF") ){
        $SHOW_TTL = false;
    }



    if ($id > 0) {
        $sql = "SELECT * FROM records WHERE id=$id";
        $ligne = $q->mysqli_fetch_array($sql);
        $btname = "{apply}";
        $tpl->form_add_button("{delete}", "DeleteDNSR$id()");
    }

    if (!is_numeric($ligne["ttl"])) {
        $ligne["ttl"] = 3600;
    }

    $form[] = $tpl->field_hidden("id", $id);
    $form[] = $tpl->field_hidden("type", $record_type);
    $form[] = $tpl->field_text("name", "$LABEL_NAME", $ligne["name"], true, "");


	
	switch (strtoupper($record_type)) {
		case "A":
			$ADDPTR=0;
			if($id>0){
				$content_array = preg_split("/\./", $ligne["content"]);
				$content_rev = sprintf("%d.%d.%d.%d.in-addr.arpa", $content_array[3], $content_array[2], $content_array[1], $content_array[0]);
				$sql="SELECT id FROM records WHERE name='$content_rev'";
				$ligne2=$q->mysqli_fetch_array($sql);
				$id_reverse=intval($ligne2["id"]);
				
				if($id_reverse>0){
					$ADDPTR=1;
					$form[]=$tpl->field_hidden("ptr_id",$id_reverse);

				}
			}
		
			$form[]=$tpl->field_ipaddr("content", "{ipaddr}", $ligne["content"]);
			$form[]=$tpl->field_checkbox("ADDPTR","{dns_ptr_text}",$ADDPTR);
			break;
			
			
		case "TXT":
			$form[]=$tpl->field_textarea("content", "{content}", $ligne["content"],"636px","110px");
			break;
			
		case "CERT":
			$form[]=$tpl->field_textarea("content", "{certificate}", $ligne["content"],"636px","110px");
			break;		

		case "SSHFP":
			$form[]=$tpl->field_textarea("content", "SSH KEY", $ligne["content"],"636px","110px");
			break;

		case "IPSECKEY":
			$form[]=$tpl->field_textarea("content", "IP SECKEY", $ligne["content"],"636px","110px");
			break;	

			
		case "TKEY":
			$form[]=$tpl->field_textarea("content", "Secret Key Establishment for DNS", $ligne["content"],"636px","110px");
			break;			
			
		case "SIG":
			$form[]=$tpl->field_textarea("content", "Domain Name System Security Extensions", $ligne["content"],"636px","110px");
			break;			
			
		case "SPF":
			$form[]=$tpl->field_textarea("content", "SPF", $ligne["content"],"636px","50px");
			break;

        case "MX":
            $SHOW_TTL=false;
            $form[]=$tpl->field_text("content", "{smtp_server_address}", $ligne["content"],true);
            if(intval($ligne["prio"])==0){$ligne["prio"]=5;}
            if($id==0){$form[]=$tpl->field_numeric("prio","PRIO",$ligne["prio"]);}
            break;

		case "SOA":
			$SHOW_TTL=false;
			$TR=preg_split('/\s+/', $ligne["content"]);
			$form[]=$tpl->field_text("SOA_MNAME", "MNAME", $TR[0],true,"{SOA_COMPUTER_EXPLAIN}");
			$form[]=$tpl->field_text("SOA_EMAIL", "RNAME", $TR[1],true,"{SOA_EMAIL_EXPLAIN}");
			if(intval($TR[2])==0){$TR[2]=date("YmdHi");}
			if(intval($TR[3])==0){$TR[3]=86400;}
			if(intval($TR[4])==0){$TR[4]=7200;}
			if(intval($TR[5])==0){$TR[5]=2419200;}
			if(intval($TR[6])==0){$TR[6]=240;}
			
			$form[]=$tpl->field_numeric("SOA_SERIAL","{serial}",$TR[2],"{DNS_SOA_SERIAL_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_REFRESH","{refresh}",$TR[3],"{SOA_REFRESH_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_RETRY","{retry}",$TR[4],"{SOA_RETRY_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_EXPIRE","{expire}",$TR[5],"{SOA_EXPIRE_EXPLAIN}");
			$form[]=$tpl->field_numeric("SOA_NEG","{negative_cache}",$TR[6],"{SOA_NEG_EXPLAIN}");
			break;

        case "PTR":
            $form[] = $tpl->field_text("content", "{hostname}",$ligne["content"]);

            if($ligne["name"]==null) {
                $form[] = $tpl->field_ipaddr("name", "{ipaddr}", $ligne["name"], true, "");
            }else{
                $form[] = $tpl->field_text("name", "PTR {record}", $ligne["name"], true, "");
            }

            break;

        case "NS":
            $form[]=$tpl->field_text("content", "{hostname}", $ligne["content"]);
            break;

        case "SRV":
            // L'API veut « poids port cible » et la priorite a part ; la forme a
            // quatre champs (priorite en tete) est aussi acceptee a l'enregistrement.
            $form[]=$tpl->field_text("content", "{value}", $ligne["content"],true,"{unbound_srv_content_help}");
            if($id==0){$form[]=$tpl->field_numeric("prio","PRIO",intval($ligne["prio"]));}
            break;

			
		default:
			$form[]=$tpl->field_text("content", "{value}", $ligne["content"]);
	}
	
	if($id>0){$form[]=$tpl->field_numeric("prio","PRIO",$ligne["prio"]);}
	if($SHOW_TTL){$form[]=$tpl->field_numeric("ttl","TTL ({seconds})",$ligne["ttl"],"{DNS_TTL_EXPLAIN}");}

	// Le bouton le dit : ce type (ou ce nom joker) garde le chemin historique,
	// sans la validation ni la relecture du cache DNS.
	if(!unbound_api::record_covered($record_type,isset($ligne["name"])?$ligne["name"]:"")){
		$explain_form=($explain_form==null)?"{unbound_record_legacy_path}":"$explain_form<br>{unbound_record_legacy_path}";
	}


	if($PowerDNSEnableClusterSlave==1){
		$tpl->FORM_LOCKED=true;
		$tpl->this_form_locked_explain="{form_locked_cluster_client}";
	}
	if(unbound_meta_collection_locked("records")){unbound_meta_lock_form($tpl);}

    if(is_null($explain_form)){
        $explain_form="{record} $record_type||{dns_rr_explain_".strtolower($record_type)."}";
    }
	
	// form_outside() ignore son 3e argument (explication) : elle est montree par div_explain (skill §11).
	$html[]=$tpl->div_explain($explain_form);
    if(strlen($title)<4){$title="";}
	$html[]=$tpl->form_outside($title, @implode("\n", $form),"",$btname,$jsafter,"AsDnsAdministrator");
	$html[]="<script>";
	$html[]="function DeleteDNSR$id(){";
	$html[]="Loadjs('$page?delete-js=$id&byPOP=yes&function=".unbound_rec_function()."');";
	$html[]="}";
	$html[]="</script>";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}


function record_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!unbound_rec_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }
	$meta=unbound_rec_meta_refusal();
	if($meta!==""){ echo $meta;return false; }
	$id=intval($_POST["id"]);
	$type=strtoupper(trim((string)(isset($_POST["type"])?$_POST["type"]:"")));
	$name=trim((string)(isset($_POST["name"])?$_POST["name"]:""));
	$content=trim((string)(isset($_POST["content"])?$_POST["content"]:""));
	$addptr=isset($_POST["ADDPTR"])?intval($_POST["ADDPTR"]):1;
	$api=new unbound_api();

	// La ligne actuelle (modification) : son type et son nom decident aussi du chemin.
	$old=null;
	if($id>0){
		$g=$api->get("/unbound/api/records/$id");
		if(!$g["ok"]){ echo $tpl->post_error(unbound_api::error_message($g));return false; }
		$old=is_array($g["data"])?$g["data"]:array();
	}
	$ttl=isset($_POST["ttl"])?intval($_POST["ttl"]):(is_array($old)?intval($old["ttl"]):3600);
	$prio=isset($_POST["prio"])?intval($_POST["prio"]):(is_array($old)?intval($old["prio"]):1);

	if($type=="SRV"){
		list($content,$prio)=unbound_rec_split_srv($content,$prio);
	}

	$covered=unbound_api::record_covered($type,$name);
	if(is_array($old) && !unbound_api::record_covered(isset($old["type"])?$old["type"]:"",isset($old["name"])?$old["name"]:"")){
		$covered=false;
	}
	if(!$covered){
		return record_save_legacy($tpl,$api,$id,$type,$name,$content,$ttl,$prio,$addptr);
	}

	// TTL non affiche (TXT, MX...) herite d'une ligne historique hors des bornes de
	// l'API : l'operateur ne pourrait pas le corriger, on revient au defaut.
	if(!isset($_POST["ttl"]) && ($ttl<60 || $ttl>604800)){$ttl=3600;}

	// Chemin de l'API : validation (ValidateRecord/RenderRR), application en
	// direct, relecture de ce qu'unbound sert, refus enrichi sur un esclave.
	$body=array("name"=>$name,"type"=>$type,"content"=>$content,"ttl"=>$ttl,"prio"=>$prio);
	if($id>0){
		$r=$api->put("/unbound/api/records/$id",$body);
	}else{
		$r=$api->post("/unbound/api/records",$body);
	}
	if(!$r["ok"]){ echo $tpl->post_error(unbound_api::error_message($r));return false; }
	unbound_api::queue($r);
	$rec=is_array($r["data"])?$r["data"]:array();
	$saved_id=isset($rec["id"])?intval($rec["id"]):$id;
	admin_tracks(($id>0?"Update":"Create")." DNS record $saved_id $type $name $content");

	// L'ancienne route creait aussi le PTR d'un A/AAAA (CreatePTR) : meme geste,
	// par l'API. Ses echecs ne bloquent pas : l'enregistrement est en place.
	if($type=="A" || $type=="AAAA"){
		$oldip="";$oldhost="";
		if(is_array($old) && isset($old["type"]) && strtoupper((string)$old["type"])==$type){
			$oldip=(string)$old["content"];$oldhost=(string)$old["name"];
		}
		$host=isset($rec["name"])?(string)$rec["name"]:$name;
		$ip=isset($rec["content"])?(string)$rec["content"]:$content;
		$notes=unbound_rec_sync_ptr($api,$host,$ip,isset($rec["ttl"])?intval($rec["ttl"]):$ttl,$addptr==1,$oldhost,$oldip);
		if(count($notes)>0){unbound_api::queue_texts($notes);}
	}
	return true;
}

/**
 * Chemin historique : types que l'API refuse (AD, SOA, types exotiques) et
 * noms joker. Sous privilege (verifie par l'appelant), refuse sur un esclave
 * du cluster avec le meme message que l'API, saisie minimale controlee ; la
 * route /dnscache/record ecrit en SQL parametre.
 */
function record_save_legacy($tpl,$api,$id,$type,$name,$content,$ttl,$prio,$addptr):bool{
	$refusal=$api->legacy_write_refusal();
	if($refusal!==""){ echo $tpl->post_error($refusal);return false; }

	if($type=="SOA"){
		$fields=array();
		foreach(array("SOA_MNAME","SOA_EMAIL") as $k){
			$v=trim((string)(isset($_POST[$k])?$_POST[$k]:""));
			if(!preg_match('/^[A-Za-z0-9_.\-]+$/',$v)){ echo $tpl->post_error("$k: {wrong_value}");return false; }
			$fields[]=$v;
		}
		foreach(array("SOA_SERIAL","SOA_REFRESH","SOA_RETRY","SOA_EXPIRE","SOA_NEG") as $k){
			$v=trim((string)(isset($_POST[$k])?$_POST[$k]:""));
			if(!preg_match('/^[0-9]{1,10}$/',$v)){ echo $tpl->post_error("$k: {wrong_value}");return false; }
			$fields[]=$v;
		}
		$ttl=intval($fields[6]);
		$content=implode(" ",$fields);
	}
	if(!preg_match('/^[A-Z0-9]{1,16}$/',$type) || !unbound_rec_legacy_input_ok($name,$content)){
		echo $tpl->post_error("{name} / {content}: {wrong_value}");
		return false;
	}

	$MAIN=array();
	$MAIN["name"] = $name;
	$MAIN["type"] = $type;
	$MAIN["content"] = $content;
	$MAIN["ttl"] = $ttl;
	$MAIN["prio"] = $prio;
	$MAIN["id"] = $id;
	$MAIN["CreatePTR"]=$addptr;

	$data = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/dnscache/record", $MAIN);
	$json = json_decode($data);
	if (json_last_error() > JSON_ERROR_NONE || !is_object($json)) {
		echo $tpl->post_error("API ERROR {$GLOBALS["CLASS_SOCKETS"]->mysql_error}");
		return false;
	}
	if(!$json->Status){
		echo $tpl->post_error($json->Error);
		return false;
	}
	return admin_tracks("Save DNS record (legacy path) $id $type $name $content");
}


function record_popup(){
	$id=intval($_GET["record-popup"]);
	if($id==0){record_wizard();return;}
	$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
	$tpl=new template_admin();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $t=time();
	$bname="{add}";
	$title="{new_record}";
    $jsafter=null;

	VERBOSE("PowerDNSEnableClusterSlave=$PowerDNSEnableClusterSlave", __LINE__);

	if($id>0){
		$bname="{apply}";
		$sql="SELECT * FROM records WHERE id=$id";
		$ligne=$q->mysqli_fetch_array($sql);
		$title="{type} {$ligne["type"]}&nbsp;&laquo;&nbsp;{$ligne["name"]}&nbsp;&laquo;&nbsp{$ligne["content"]}";

	}

	foreach (GetRtypes() as $record_type) {$TYPES[$record_type]=$record_type;}

    $form[]=$tpl->field_text("name","{record}",$ligne["name"],true);
	$form[]=$tpl->field_array_hash_simple($TYPES, "type", "{type} (IN)", $ligne["type"],true,"FormRecordType$t()");
	$html[]=$tpl->form_outside("$title", @implode("\n", $form),"{ADD_DNS_ENTRY_TEXT}",$bname,"$jsafter","AsDnsAdministrator");
	$html[]="";
}

function deleted_js()
{

    $page=CurrentPageName();
    // $id est reinjecte tel quel dans un selecteur jQuery: c'est un md5, donc
    // restreint aux caracteres hexadecimaux.
    $id=preg_replace('/[^a-f0-9]/','',(string)(isset($_GET["id-deleted"])?$_GET["id-deleted"]:""));

    $js=array();
    if($id!==""){$js[]="$('#{$id}').remove();";}
    $call=unbound_rec_call();
    if($call!==""){$js[]=$call;}

    if(isset($_GET["byPOP"])) {
        $js[] = "dialogInstance7.close();";
        $js[] = "if(document.getElementById('table-dns-records-loader')){";
        $js[]="\tLoadAjax('table-dns-records-loader','$page?main=yes');";
        $js[]="\t}";
    }

    $js[]="if(document.getElementById('TABLEAU-TOP-RECHERCHE')){";
    $js[]="\tvar Search=encodeURIComponent(document.getElementById('TABLEAU-TOP-RECHERCHE').getAttribute('data-search')||'');";
    $js[]="\tLoadAjax('MainContent','fw.top.search.php?search='+Search);";
    $js[]="\t}";
    // Constats non bloquants de la suppression (avertissements, notes d'articarest).
    $js[]=unbound_rec_notices_call();
    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
    // La piste d'audit est ecrite par record_delete() (POST) : plus d'admin_tracks
    // ici, a partir d'un texte fourni par l'URL.
}

function delete_js(){
    $page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	// Meme raison: $id part dans un SELECT puis dans la suppression.
	$id=intval($_GET["delete-js"]);


	$q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
	$sql="SELECT content,`type`,`name` FROM records WHERE id=$id";
	$ligne=$q->mysqli_fetch_array($sql);
	$value=$ligne["content"];
	$type=$ligne["type"];
	$name=$ligne["name"];
	$delete=$tpl->javascript_parse_text("{delete}");


    // Cet id est celui de la <tr> a retirer du DOM: un md5, encode pour l'URL.
    $final=array();
    $final[]="id-deleted=".urlencode((string)(isset($_GET["id"])?$_GET["id"]:""));

	$fname=unbound_rec_function();
	if($fname!==""){
	    $final[]="&function=$fname";
	}
    if(strlen( (string) $value)>30){
        $value=substr($value,0,27)."...";
    }
	if(isset($_GET["byPOP"])) {
        $final[] = "&byPOP=yes";
    }

	$jsfinal="Loadjs('$page?".@implode("",$final)."')";
	$tpl->js_confirm_delete("$delete:<br>$name<br>$type<br>$value<br>ID: ($id) ?","delete",$id,$jsfinal);

	
}



function GetRtypes(){
	$rtypes = array(
			'A',
			'AAAA',
			'AFSDB',
			'CERT',
			'CNAME',
			'DHCID',
			'DLV',
			'DNSKEY',
			'DS',
			'EUI48',
			'EUI64',
			'HINFO',
			'IPSECKEY',
			'KEY',
			'KX',
			'LOC',
			'MINFO',
			'MR',
			'MX',
			'NAPTR',
			'NS',
			'NSEC',
			'NSEC3',
			'NSEC3PARAM',
			'OPT',
			'PTR',
			'RKEY',
			'RP',
			'RRSIG',
			'SIG',
			'SOA',
			'SPF',
			'SRV',
			'SSHFP',
			'TLSA',
			'TSIG',
			
			'TXT',
			'WKS',
	);

	return $rtypes;


}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	// A fresh visit starts on "All", page 1 (the filter is only kept across reloads of the list).
	unset($_SESSION["UNBREC"]);
    unbound_databases();
    $html=$tpl->page_header("{DNS_RECORDS}","fas fa-list-ol","{unbound_dns_records_explain}","$page?tabs=yes","dns-cache-records","progress-firehol-restart","main=yes","table-dns-records-loader");

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}




/**
 * La cle de dedoublonnage entre un enregistrement de la base et une entree du
 * cache du resolveur.
 *
 * Les deux cotes la fabriquaient chacun a sa facon: "$name.$type" a l'ecriture,
 * "$name$type" a la lecture. Elles ne pouvaient donc JAMAIS correspondre, et
 * toute entree du cache doublant un enregistrement declare s'affichait deux
 * fois. Une seule fonction pour les deux cotes rend la divergence impossible.
 */
function unbound_rec_key($name,$type):string{
    return strtolower(trim((string)$name))."|".strtolower(trim((string)$type));
}

// =====================================================================
// Records list (Stitch "unbound dns manager console").
// The search box is the search_block of page_header (?main=yes&search=…);
// type chips and pages reload the same div with &type=&p=, remembered in the
// session so that the refresh function a dialog calls after a save (which only
// knows the search) lands back on the same type and page.
// =====================================================================


// Current type filter, page and search; remembered across reloads.
function unbrec_state():array{
	$search=trim((string)(isset($_GET["search"])?$_GET["search"]:""));
	$prev=isset($_SESSION["UNBREC"]) && is_array($_SESSION["UNBREC"])?$_SESSION["UNBREC"]:array("type"=>"","p"=>1,"search"=>"");
	$type=isset($_GET["type"])?strtoupper(trim((string)$_GET["type"])):(string)$prev["type"];
	if($type!=="" && !in_array($type,GetRtypes(),true)){$type="";}
	if(isset($_GET["p"])){$p=intval($_GET["p"]);}
	else{$p=($search===(string)$prev["search"] && $type===(string)$prev["type"])?intval($prev["p"]):1;}
	if(isset($_GET["type"]) && !isset($_GET["p"])){$p=1;}
	if($p<1){$p=1;}
	$_SESSION["UNBREC"]=array("type"=>$type,"p"=>$p,"search"=>$search);
	return array("type"=>$type,"p"=>$p,"search"=>$search);
}

// One pass over the API and the cache; every widget reads this model.
function unbrec_model(array $st):array{
	$m=array("err"=>"","rows"=>array(),"total"=>0,"all"=>0,"types"=>array(),"cache"=>array(),"cache_err"=>false);
	$api=new unbound_api();
	$q=array("limit"=>UNBREC_PAGE_SIZE,"offset"=>($st["p"]-1)*UNBREC_PAGE_SIZE);
	if($st["search"]!==""){$q["q"]=$st["search"];}
	if($st["type"]!==""){$q["type"]=$st["type"];}
	$r=$api->get("/unbound/api/records",$q);
	if(!$r["ok"]){$m["err"]=unbound_api::error_message($r);return $m;}
	$data=is_array($r["data"])?$r["data"]:array();
	$m["rows"]=(isset($data["records"]) && is_array($data["records"]))?$data["records"]:array();
	$m["total"]=intval(isset($data["total"])?$data["total"]:count($m["rows"]));

	// Real counts: the same search, one type at a time, limit=1 (only "total" is read).
	$base=array("limit"=>1);
	if($st["search"]!==""){$base["q"]=$st["search"];}
	$a=$api->get("/unbound/api/records",$base);
	$m["all"]=($a["ok"] && is_array($a["data"]))?intval($a["data"]["total"] ?? 0):$m["total"];
	foreach(unbound_api::record_types() as $t){
		$c=$api->get("/unbound/api/records",array_merge($base,array("type"=>$t)));
		$n=($c["ok"] && is_array($c["data"]))?intval($c["data"]["total"] ?? 0):0;
		if($n>0 || $t===$st["type"]){$m["types"][$t]=$n;}
	}
	if($st["type"]!=="" && !isset($m["types"][$st["type"]])){$m["types"][$st["type"]]=$m["total"];}

	// Resolver data not declared in the database: compare with EVERY declared
	// name/type (one read), not with the page on screen.
	$declared=array();
	$db=new lib_sqlite("/home/artica/SQLITE/unbound.db");
	$all=$db->QUERY_SQL("SELECT name,type FROM records");
	if(is_array($all)){foreach($all as $row){$declared[unbound_rec_key($row["name"] ?? "",$row["type"] ?? "")]=true;}}
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscache/records"));
	if(!is_object($json) || !isset($json->Records)){$m["cache_err"]=true;return $m;}
	$records=is_array($json->Records)?$json->Records:array();
	foreach($records as $e){
		if(!is_object($e)){continue;}
		$name=(string)($e->name ?? ""); $type=(string)($e->type ?? "");
		if(isset($declared[unbound_rec_key($name,$type)])){continue;}
		if($st["type"]!=="" && strtoupper($type)!==$st["type"]){continue;}
		if($st["search"]!=="" && stripos($name.(string)($e->content ?? ""),str_replace("*","",$st["search"]))===false){continue;}
		$m["cache"][]=$e;
	}
	return $m;
}

// 3600 -> "1 h", 300 -> "5 min", 86400 -> "1 d".
function unbrec_ttl($s):string{
	$s=intval($s);
	if($s<=0){return "-";}
	if($s%86400==0){return ($s/86400)." d";}
	if($s%3600==0){return ($s/3600)." h";}
	if($s%60==0){return ($s/60)." min";}
	return "$s s";
}

function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$readonly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"))==1;
	// Enregistrements tenus par une politique Meta : meme lecture seule qu'un esclave.
	$metaLocked=unbound_meta_collection_locked("records");
	$slave=$readonly;
	if($metaLocked){$readonly=true;}
	$function=unbound_rec_function();
	$divid=preg_replace('/[^A-Za-z0-9_-]/','',(string)(isset($_GET["div-id"])?$_GET["div-id"]:""));
	$st=unbrec_state();
	$m=unbrec_model($st);
	$base="$page?main=yes&search=".urlencode($st["search"])."&function=$function&div-id=$divid";

	$h=array();
	$h[]="<div class='unbrec ak'>";
	if($slave){$h[]=unbrec_readonly();}
	if($metaLocked){$h[]=unbound_meta_banner($tpl,"records");}
	if($m["err"]!==""){
		$h[]=$tpl->div_error(htmlspecialchars($m["err"]));
	}else{
		$h[]=unbrec_toolbar($m,$st,$base,$divid);
		$h[]=unbrec_table($m,$st,$base,$divid,$readonly,$page,$function);
		$h[]=unbrec_cache($m,$page,$metaLocked);
	}
	$h[]="</div>";

	$topbuttons=array();
	if(!$readonly){
		$topbuttons[]=array("Loadjs('$page?record-js=0&function=$function');",ico_plus,"{new_record}");
		$topbuttons[]=array("Loadjs('$page?activedirectory-js=yes&function=$function');","fab fa-windows","Active Directory");
	}
	$topbuttons[]=array("LoadAjax('MainContent','fw.dns.unbound.blockaaaa.php?main-page=yes');",ico_filter,"{filter_aaaa}");
	$TINY_ARRAY=array();
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$TINY_ARRAY["TITLE"]="{DNS_RECORDS}";
	$TINY_ARRAY["ICO"]="fas fa-list-ol";
	$TINY_ARRAY["EXPL"]="{unbound_dns_records_explain}";
	$js=array();
	$js[]="function CreateDNSRecord(){ Loadjs('$page?record-js=0&function=$function'); }";
	$js[]="function ActiveDirectoryRecord(){ Loadjs('$page?activedirectory-js=yes&function=$function'); }";
	$js[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	$js[]="NoSpinner();";
	echo unbrec_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>".@implode("\n",$js)."</script>";
}

function unbrec_readonly():string{
	return "<div class='ak-banner ak-off unbrec-ro' role='note'><span><i class='fas fa-lock' aria-hidden='true'></i> {unbrec_readonly}</span><span class='unbrec-ro-tag'>{unbrec_readonly_tag}</span></div>";
}

// Search lives in the page header; the toolbar holds the type chips and the count.
function unbrec_toolbar(array $m,array $st,string $base,string $divid):string{
	$chips=array();
	$chips[]=unbrec_chip("","{unbrec_all}",$m["all"],$st,$base,$divid);
	foreach($m["types"] as $t=>$n){$chips[]=unbrec_chip($t,htmlspecialchars($t),$n,$st,$base,$divid);}
	return "<div class='ak-tools unbrec-bar'><div class='ak-seg unbrec-chips' role='group' aria-label='{type}'>".implode("",$chips)."</div>"
		."<div class='unbrec-count'><b>".number_format($m["total"],0,"."," ")."</b> {unbrec_records}</div></div>";
}

function unbrec_chip(string $type,string $label,int $n,array $st,string $base,string $divid):string{
	$on=($type===$st["type"]);
	$js=$divid!==""?"LoadAjaxSilent('$divid','$base&type=".urlencode($type)."');":"";
	return "<button type='button' class='ak-seg-b unbrec-chip".($on?" on":"")."' aria-pressed='".($on?"true":"false")."' OnClick=\"$js\">$label <small>".number_format($n,0,"."," ")."</small></button>";
}

function unbrec_table(array $m,array $st,string $base,string $divid,bool $readonly,string $page,string $function):string{
	$h=array();
	$h[]="<div class='ak-card unbrec-card'><div class='unbrec-scroll'><table class='ak-tbl unbrec-tbl'>";
	$h[]="<thead><tr><th>{unbrec_name}</th><th>{unbrec_type}</th><th>{unbrec_value}</th><th>{unbrec_ttl}</th><th>{status}</th><th class='unbrec-c'>{enabled}</th><th class='ak-end'>{unbrec_actions}</th></tr></thead><tbody>";
	if(count($m["rows"])==0){
		$h[]="<tr><td colspan='7'>".unbrec_empty($st,$readonly,$page,$function)."</td></tr>";
	}else{
		foreach($m["rows"] as $rec){if(is_array($rec)){$h[]=unbrec_row($rec,$readonly,$page,$function);}}
	}
	$h[]="</tbody></table></div>";
	$h[]=unbrec_pager($m,$st,$base,$divid);
	$h[]="</div>";
	return implode("\n",$h);
}

function unbrec_empty(array $st,bool $readonly,string $page,string $function):string{
	if($st["search"]!=="" || $st["type"]!==""){
		return "<div class='ak-empty-c'>{unbrec_no_match}</div>";
	}
	$btn=(!$readonly && unbound_rec_can_write())?"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?record-js=0&function=$function');\"><i class='fas fa-plus' aria-hidden='true'></i> {new_record}</button>":"";
	return "<div class='ak-empty-c'><span class='unbrec-empty-ico' aria-hidden='true'><i class='fas fa-list-ol'></i></span><div>{unbrec_no_record}</div>$btn</div>";
}

// One declared record. The dns-name/-type/-content spans are kept: filltable()
// rewrites them after an edit.
function unbrec_row(array $rec,bool $readonly,string $page,string $function):string{
	$id=intval($rec["id"] ?? 0);
	$name=(string)($rec["name"] ?? "");
	$type=strtoupper((string)($rec["type"] ?? ""));
	$content=(string)($rec["content"] ?? "");
	$zone=(string)($rec["zone"] ?? "");
	$ttl=intval($rec["ttl"] ?? 0);
	$prio=intval($rec["prio"] ?? 0);
	$enabled=!(isset($rec["enabled"]) && $rec["enabled"]===false);
	$served=array_key_exists("served",$rec)?$rec["served"]:null;
	$applyErr=(string)($rec["apply_error"] ?? "");
	$md=md5($id.$name.$type);
	$jshost="Loadjs('$page?record-info-js=yes&type=".urlencode($type)."&id=$id');";
	$hn=htmlspecialchars($name,ENT_QUOTES);
	$canWrite=!$readonly && unbound_rec_can_write();
	$covered=unbound_api::record_covered($type,$name);

	$h="<tr id='$md' class='".($enabled?"":"is-off")."'>";
	$h.="<td><a class='unbrec-name' href=\"javascript:blur();\" OnClick=\"$jshost\"><span id='dns-name-$id'>$hn</span></a>";
	if($zone!==""){$h.="<div class='unbrec-zone'>".htmlspecialchars($zone)."</div>";}
	$h.="</td>";
	$h.="<td><span id='dns-type-$id'><span class='ak-tag unbrec-type ".unbrec_type_class($type)."'>".htmlspecialchars($type)."</span></span></td>";
	$pchip=($prio>0 && ($type=="MX" || $type=="SRV"))?"<span class='ak-tag ak-warn unbrec-prio'>{unbrec_prio} $prio</span>":"";
	$h.="<td class='unbrec-val'>$pchip<span id='dns-content-$id' title='".htmlspecialchars($content,ENT_QUOTES)."'>".htmlspecialchars($content)."</span></td>";
	$h.="<td><span title='$ttl s'>".unbrec_ttl($ttl)."</span></td>";
	$h.="<td>".unbrec_status($enabled,$served,$applyErr)."</td>";
	$h.="<td class='unbrec-c'>".unbrec_switch($id,$enabled,$covered,$canWrite,$hn,$page)."</td>";
	$acts="";
	if($canWrite){
		$acts.="<button type='button' class='ak-act' aria-label='{unbrec_edit} $hn' title='{unbrec_edit}' OnClick=\"$jshost\"><i class='fas fa-pen' aria-hidden='true'></i></button>";
		$acts.="<button type='button' class='ak-act ak-danger' aria-label='{unbrec_delete} $hn' title='{unbrec_delete}' OnClick=\"Loadjs('$page?delete-js=$id&id=$md&function=$function')\"><i class='fas fa-trash' aria-hidden='true'></i></button>";
	}
	$h.="<td class='ak-end'><div class='ak-acts unbrec-acts'>$acts</div></td>";
	return $h."</tr>";
}

function unbrec_type_class(string $type):string{
	// Ton du kit ak- de l'etiquette de type.
	if($type=="A" || $type=="AAAA"){return "ak-ok";}
	if($type=="CNAME" || $type=="PTR"){return "ak-off";}
	if($type=="MX" || $type=="SRV"){return "ak-warn";}
	return "unbrec-t-other";
}

// Icon + word, never color alone. served: true / false / null (resolver unreachable).
function unbrec_status(bool $enabled,$served,string $applyErr):string{
	if(!$enabled){return "<span class='ak-state ak-off'><i class='fas fa-power-off' aria-hidden='true'></i>{disabled}</span>";}
	if($served===true){return "<span class='ak-state ak-ok'><i class='fas fa-circle-check' aria-hidden='true'></i>{served}</span>";}
	if($served===false){
		$t=$applyErr!==""?" title='".htmlspecialchars($applyErr,ENT_QUOTES)."'":"";
		return "<span class='ak-state ak-err'$t><i class='fas fa-circle-exclamation' aria-hidden='true'></i>{dres_not_served}</span>";
	}
	return "<span class='ak-state ak-off' title='{dns_cache_not_answering}'><i class='fas fa-circle-question' aria-hidden='true'></i>{unbrec_unknown}</span>";
}

// A real switch for API rows; legacy rows (AD, SOA, wildcards) cannot be toggled here.
function unbrec_switch(int $id,bool $enabled,bool $covered,bool $canWrite,string $hn,string $page):string{
	if(!$covered){return "<span class='unbrec-legacy' title='{unbound_record_legacy_toggle}'>{unbrec_legacy}</span>";}
	$fname=unbound_rec_function();
	$js=$canWrite?" OnClick=\"Loadjs('$page?enable-js=$id&function=$fname');\"":" disabled";
	return "<button type='button' role='switch' aria-checked='".($enabled?"true":"false")."' aria-label='{enabled} $hn' class='ak-sw'$js></button>";
}

function unbrec_pager(array $m,array $st,string $base,string $divid):string{
	$size=UNBREC_PAGE_SIZE;
	$total=$m["total"];
	$pages=max(1,intval(ceil($total/$size)));
	$cur=min($st["p"],$pages);
	$from=$total>0?($cur-1)*$size+1:0;
	$to=min($total,$cur*$size);
	$left="{showing} <b>$from&ndash;$to</b> / <b>".number_format($total,0,"."," ")."</b> {unbrec_records}";
	$btns="";
	if($pages>1 && $divid!==""){
		$go=function($p) use($base,$st,$divid){ return "LoadAjaxSilent('$divid','$base&type=".urlencode($st["type"])."&p=$p');"; };
		$btns.="<button type='button' aria-label='{unbrec_prev}'".($cur<=1?" disabled":" OnClick=\"".$go($cur-1)."\"").">&lsaquo;</button>";
		$set=array(1,$pages);
		for($p=max(1,$cur-1);$p<=min($pages,$cur+1);$p++){$set[]=$p;}
		$set=array_unique($set); sort($set);
		$prev=0;
		foreach($set as $p){
			if($prev && $p>$prev+1){$btns.="<span class='unbrec-gap'>&hellip;</span>";}
			$btns.="<button type='button'".($p==$cur?" class='cur' aria-current='page'":" OnClick=\"".$go($p)."\"").">$p</button>";
			$prev=$p;
		}
		$btns.="<button type='button' aria-label='{unbrec_next}'".($cur>=$pages?" disabled":" OnClick=\"".$go($cur+1)."\"").">&rsaquo;</button>";
	}
	return "<div class='ak-card-f'><span>$left</span><span class='ak-pg unbrec-pg'>$btns</span></div>";
}

// Resolver data not declared above: its own collapsed section, flush only.
// $metaLocked : enregistrements tenus par une politique Meta, la purge d'un nom en cache est refusee.
function unbrec_cache(array $m,string $page,bool $metaLocked=false):string{
	if($m["cache_err"]){
		return "<div class='ak-card unbrec-cache-err'><i class='fas fa-circle-question' aria-hidden='true'></i> {dns_cache_not_answering}</div>";
	}
	$n=count($m["cache"]);
	if($n==0){return "";}
	$max=500;
	$rows=array();
	$c=0;
	foreach($m["cache"] as $e){
		$c++;
		if($c>$max){break;}
		$name=(string)($e->name ?? ""); $type=(string)($e->type ?? ""); $content=(string)($e->content ?? "");
		$md=md5("$name$type$content$c");
		$hn=htmlspecialchars($name,ENT_QUOTES);
		$rows[]="<tr id='$md'><td class='ak-mono'>$hn</td><td><span class='ak-tag unbrec-type ".unbrec_type_class(strtoupper($type))."'>".htmlspecialchars($type)."</span></td>"
			."<td class='unbrec-val'>".htmlspecialchars($content)."</td><td><span title='".intval($e->ttl ?? 0)." s'>".unbrec_ttl($e->ttl ?? 0)."</span></td>"
			."<td class='ak-end'>".($metaLocked?"":"<button type='button' class='ak-act' aria-label='{unbrec_flush} $hn' title='{unbrec_flush}' OnClick=\"Loadjs('$page?deletemem-js=".urlencode($name)."&md=$md')\"><i class='fas fa-broom' aria-hidden='true'></i></button>")."</td></tr>";
	}
	$more=$n>$max?"<div class='unbrec-cache-note'>$max / $n</div>":"";
	return "<details class='ak-card unbrec-cache'><summary><span><i class='fas fa-bolt' aria-hidden='true'></i> {unbrec_cache_title} <span class='ak-count'>$n</span></span><span class='unbrec-cache-hint'>{unbrec_cache_hint}</span></summary>"
		."<div class='unbrec-cache-body'><p>{unbrec_cache_explain}</p><table class='ak-tbl unbrec-tbl'><thead><tr><th>{unbrec_name}</th><th>{unbrec_type}</th><th>{unbrec_value}</th><th>{unbrec_ttl}</th><th></th></tr></thead><tbody>"
		.implode("",$rows)."</tbody></table>$more</div></details>";
}

function unbrec_style():string{
	if(isset($GLOBALS["UNBREC_STYLE"])){return "";}
	$GLOBALS["UNBREC_STYLE"]=true;
	$P="#006a62"; $S="rgba(16,185,129,.12)";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$pal=brand_palette(); $a=brand_ui_accents($pal);
		if(!empty($a["accent_soft"])){$S=$a["accent_soft"];}
		if(!empty($pal["colors_on"]) && !empty($a["accent_dark"])){$P=$a["accent_dark"];}
	}
	// Carte, table, etiquettes, etats, segmente, pagination, actions : kit ak- (angular.css.php).
	return "<style>
.unbrec{margin:10px 15px 0}
.unbrec-ro{justify-content:space-between;gap:12px;padding:12px 16px;border-radius:12px;margin-bottom:16px}
.unbrec-ro-tag{padding:2px 10px;border-radius:999px;background:#fff;font-size:12px;font-weight:700;color:$P}
.unbrec-bar{justify-content:space-between;gap:16px;padding:12px 16px}
.unbrec-chips{flex-wrap:wrap}
.unbrec-chip small{font-size:12px;font-weight:500;color:#6d7a77;margin-left:4px}
.unbrec-count{font-size:13px;color:#3d4947;white-space:nowrap}
.unbrec-card{margin-bottom:16px}
.unbrec-scroll{overflow-x:auto}
.unbrec-tbl th{white-space:nowrap}
.unbrec-tbl td{font-size:13px}
.unbrec-tbl tbody tr:hover{background:#f6faf9}
.unbrec-tbl tr.is-off td{opacity:.55}
.unbrec-tbl tr.is-off td:nth-last-child(-n+2){opacity:1}
.unbrec-c{text-align:center!important}
.unbrec-name{font-size:15px;font-weight:600;color:$P;text-decoration:none;word-break:break-all}
.unbrec-name:hover{text-decoration:underline;color:$P}
.unbrec-zone{font-size:12px;color:#6d7a77;margin-top:2px}
.unbrec-type{min-width:56px;font-weight:700;text-align:center}
.unbrec-t-other{background:#fff;border:1px solid #bcc9c6}
.unbrec-val{font-family:monospace;font-size:13px;max-width:420px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.unbrec-prio{margin-right:8px}
.unbrec-legacy{display:inline-block;padding:2px 8px;border-radius:6px;background:#eef1f1;color:#3d4947;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.unbrec-acts{opacity:.45;transition:opacity .15s}
.unbrec-tbl tbody tr:hover .unbrec-acts,.unbrec-acts:focus-within{opacity:1}
.unbrec-pg{align-items:center}
.unbrec-pg button:disabled{color:#bcc9c6;cursor:default}
.unbrec-gap{color:#6d7a77}
.unbrec-empty-ico{width:64px;height:64px;border-radius:50%;background:$S;color:$P;display:inline-flex;align-items:center;justify-content:center;font-size:26px}
.unbrec-cache{margin-bottom:16px}
.unbrec-cache summary{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;cursor:pointer;font-size:14px;font-weight:600;list-style:none}
.unbrec-cache summary::-webkit-details-marker{display:none}
.unbrec-cache-hint{font-size:13px;font-weight:400;color:#6d7a77}
.unbrec-cache-body{border-top:1px solid #e2e8f0}
.unbrec-cache-body p{margin:0;padding:12px 16px;font-size:13px;color:#3d4947}
.unbrec-cache-note{padding:10px 16px;font-size:12px;color:#6d7a77}
.unbrec-cache-err{padding:14px 16px;color:#6d7a77;font-size:13px;margin-bottom:16px}
.unbrec-name:focus-visible,.unbrec-cache summary:focus-visible{outline:2px solid $P;outline-offset:2px}
@media(prefers-reduced-motion:reduce){.unbrec *{transition:none!important}}
</style>";
}

function filltable(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $id=intval($_GET["filltable"]);
    // Ce nom finit dans quatre URL rechargees en JavaScript: liste blanche.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $f[]="LoadAjaxSilent('dns-name-$id','$page?id=$id&td-column=name&function=$function');";
    $f[]="LoadAjaxSilent('dns-domain-$id','$page?id=$id&td-column=domain&function=$function');";
    $f[]="LoadAjaxSilent('dns-type-$id','$page?id=$id&td-column=type&function=$function');";
    $f[]="LoadAjaxSilent('dns-content-$id','$page?id=$id&td-column=content&function=$function');";

echo @implode("\n",$f);



}

function td_column(){
    $page=CurrentPageName();
    // Ce gestionnaire reecrit une cellule du tableau apres modification: $id
    // partait brut dans le WHERE.
    $id=intval($_GET["id"]);
    $field=(string)($_GET["td-column"] ?? "");
    $tpl=new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/unbound.db");
    $sql = "SELECT * FROM records WHERE id=$id";
    $ligne = $q->mysqli_fetch_array($sql);
    $type=(string)(isset($ligne["type"])?$ligne["type"]:"");
    $jshost="Loadjs('$page?record-info-js=yes&type=".urlencode($type)."&id=$id');";


    // Valeurs de la base : echappees comme dans unbound_rec_row().
    if($field=="content"){
        $content=(string)(isset($ligne["content"])?$ligne["content"]:"");
        if(strlen($content)>90){$content=substr($content, 0,87)."...";}
        echo htmlspecialchars($content);
        return;
    }

    echo $tpl->td_href(htmlspecialchars((string)(isset($ligne[$field])?$ligne[$field]:"")),null,$jshost);

}

function unbound_databases(){
    $q=new lib_sqlite("/home/artica/SQLITE/unbound.db");
    @chmod("/home/artica/SQLITE/unbound.db", 0644);
    @chown("/home/artica/SQLITE/unbound.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS records (
 `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  name                  TEXT DEFAULT NULL,
  type                  TEXT DEFAULT NULL,
  content               TEXT DEFAULT NULL,
  ttl                   INT DEFAULT NULL,
  prio                  INT DEFAULT NULL,
  disabled              INTEGER DEFAULT 0,
  ordername             TEXT DEFAULT NULL,
  auth                  INTEGER DEFAULT 1 )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        $tpl=new template_admin();
        echo $tpl->div_error("Fatal: $q->mysql_error (".__LINE__.")\n");
    }

}