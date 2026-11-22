<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
include_once(dirname(__FILE__)."/ressources/class.dnswhitelist.inc");
// Spec 019 lot 4c : avec le cache DNS unbound, la liste blanche est la
// politique RPZ reservee « whitelist » (passthru, en tete), ecrite par
// /unbound/api/rpz-whitelist. L'ancienne table webfilter_whitelists n'est lue
// que par dnsfilterd, desinstalle par unbound : elle reste geree ici seulement
// tant qu'unbound n'est pas le resolveur.
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
// unbound_page : delai d'ecriture sous les 300 s de nginx, message de delai depasse.
include_once(dirname(__FILE__)."/ressources/class.unbound.settings.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
// Liste blanche RPZ (cache DNS unbound) : *-js (GET, rend du JavaScript) puis POST.
if(isset($_GET["rpzwl-add-js"])){rpzwl_add_js();exit;}
if(isset($_GET["rpzwl-add-popup"])){rpzwl_add_popup();exit;}
if(isset($_POST["rpzwl-add"])){rpzwl_add_save();exit;}
if(isset($_GET["rpzwl-delete-js"])){rpzwl_delete_js();exit;}
if(isset($_POST["rpzwl-delete"])){rpzwl_delete_save();exit;}
if(isset($_GET["rpzwl-migrate-js"])){rpzwl_migrate_js();exit;}
if(isset($_POST["rpzwl-migrate"])){rpzwl_migrate_save();exit;}
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
// Ancienne liste (dnsfilterd).
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-id"])){item_id();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_POST["item-enable-id"])){item_enable_save();exit;}
if(isset($_GET["item-delete"])){item_delete();exit;}
if(isset($_POST["item-delete-id"])){item_delete_save();exit;}
if(isset($_POST["ID"])){item_save();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	echo "<div id='dnsfilter-white-div' style='margin-top:10px'></div><script>LoadAjax('dnsfilter-white-div','$page?table=yes');</script>";
}

/**
 * Controle cote serveur des handlers qui ecrivent (form_outside et icon_*
 * ne font que masquer le bouton), puis refus d'un esclave du cluster (dns.db
 * est repliquee : un changement local serait ecrase). Rend "" ou le message.
 */
function dnswl_write_refusal():string{
	$tpl=new template_admin();
	if(!$tpl->IsSecurity("AsDnsAdministrator")){return "{ERROR_NO_PRIVS2}";}
	return (new unbound_api())->legacy_write_refusal();
}

/** Bascule : *-js (rend du JavaScript, n'ecrit rien) -> POST item-enable-id. */
function item_enable(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-enable"]);
	header("content-type: application/x-javascript");
	if(!$tpl->IsSecurity("AsDnsAdministrator")){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	echo dnswl_xhr_js("item-enable-id",$ID,"LoadAjax('dnsfilter-white-div','$page?table=yes');");
}

function item_enable_save(){
	$tpl=new template_admin();
	$e=dnswl_write_refusal();
	if($e!==""){echo $tpl->post_error($e);return;}
	// Le daemon bascule et rend la nouvelle valeur : plus de lire-puis-ecrire.
	$e=artrest_error(dnswhitelist_enable(intval($_POST["item-enable-id"])));
	if($e!=""){echo $tpl->post_error($e);}
}

/** Suppression : *-js (confirmation) -> POST item-delete-id. */
function item_delete(){
	$tpl=new template_admin();
	$ID=intval($_GET["item-delete"]);
	// $md est reinjecte tel quel dans un selecteur jQuery: c'est un md5, donc
	// restreint aux caracteres hexadecimaux.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	header("content-type: application/x-javascript");
	if(!$tpl->IsSecurity("AsDnsAdministrator")){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	$ligne=dnswhitelist_get($ID);
	$name=htmlspecialchars((string)($ligne["pattern"] ?? $ID));
	$after=($md==="")?"":"$('#{$md}').remove();";
	$tpl->js_confirm_delete($name,"item-delete-id",$ID,$after);
}

function item_delete_save(){
	$tpl=new template_admin();
	$e=dnswl_write_refusal();
	if($e!==""){echo $tpl->post_error($e);return;}
	$e=artrest_error(dnswhitelist_delete(intval($_POST["item-delete-id"])));
	if($e!=""){echo $tpl->post_error($e);}
}

/** POST d'une action (XHRConnection), erreur en swal, puis $after et constats. */
function dnswl_xhr_js($field,$value,$after):string{
	$page=CurrentPageName();
	$t=time().rand(100,999);
	$js=array();
	$js[]="var xDnsWl$t=function(obj){";
	$js[]="\tvar res=obj.responseText;";
	$js[]="\tif(res.length>3){";
	$js[]="\t\t".unbound_page::swal_js("res");
	$js[]="\t}";
	$js[]="\t$after";
	$js[]="\tLoadjs('$page?api-notices-js=yes');";
	$js[]="};";
	$js[]="var XHR=new XHRConnection();";
	$js[]="XHR.appendData(".json_encode((string)$field).",".json_encode((string)$value,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT).");";
	$js[]="XHR.sendAndLoad('$page','POST',xDnsWl$t);";
	return implode("\n",$js);
}

function item_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-js"]);
	if($ID==0){$title="{new_item}";}else{
		$ligne=dnswhitelist_get($ID);
		if(!is_array($ligne)){$ligne=array();}
		$title="{item} ".htmlspecialchars((string)($ligne["pattern"] ?? ""))." - $ID";}
	$tpl->js_dialog1($title, "$page?item-id=$ID");
}

function item_id(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-id"]);	
	$title="{new_item}";
	$bt="{add}";
	$jsafter="LoadAjax('dnsfilter-white-div','$page?table=yes');dialogInstance1.close();";
	$ligne=dnswhitelist_get($ID);
	if(!is_array($ligne)){$ligne=array();}
	// Une entree neuve n'a pas de ligne : sans ces cles, PHP 8 emet une notice
	// par champ et field_array_hash ne selectionne rien.
	if(!isset($ligne["type"])){$ligne["type"]=1;}
	if(!isset($ligne["pattern"])){$ligne["pattern"]="";}
	// Le motif vient de la base et partait BRUT dans le titre de la modale.
	if($ID>0){$title="{item}: ".htmlspecialchars((string)$ligne["pattern"]);$bt="{apply}";}
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_array_hash($ztype, "ztype", "{type}", $ligne["type"]);
	$form[]=$tpl->field_text("pattern", "{item}", $ligne["pattern"],true);
	// form_outside() n'analyse QUE le libelle de son bouton: sans cet appel, le
	// titre et les libelles de champ s'affichaient en {tokens} litteraux.
	echo $tpl->_ENGINE_parse_body(
		$tpl->form_outside($title, $form,"{dnsfilterd_white_explain}",$bt,$jsafter,"AsDnsAdministrator")
	);
	
	
}

function item_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$e=dnswl_write_refusal();
	if($e!==""){echo $tpl->post_error($e);return;}
	$ID=intval($_POST["ID"]);
	// L'ancien code enchainait l'INSERT puis un UPDATE ... WHERE ID=0 faute de
	// return : sans effet, mais le daemon fait desormais l'un OU l'autre.
	// `enabled` n'est pas touche par une mise a jour, le formulaire ne le porte
	// pas et l'ecrire reactiverait une entree volontairement desactivee.
	$e=artrest_error(dnswhitelist_save($ID,$_POST["pattern"],intval($_POST["ztype"])));
	if($e!=""){echo $tpl->post_error($e);return;}
}


/**
 * Une entree de liste blanche.
 *
 * L'id md5 de la <tr> est CONSERVE: item_delete() fait $('#<md>').remove() pour
 * retirer la ligne sans recharger le tableau.
 */
function dnswl_row($ligne,$ztype,$tpl,$page):string{
	$ID=intval($ligne["ID"]);
	$md=md5(serialize($ligne));
	$pattern=(string)($ligne["pattern"] ?? "");
	$type=intval($ligne["type"] ?? 0);
	$enabled=intval($ligne["enabled"] ?? 0);

	// $ztype[$ligne["type"]] etait indexe sans garde: un type hors de {0,1}
	// -- une valeur posee par un autre chemin -- produisait un avertissement
	// PHP et une cellule vide.
	$typeText=isset($ztype[$type])?$ztype[$type]:"<span class='awc-none'>&mdash;</span>";
	$ico=($type==0)?"fas fa-network-wired":"fas fa-globe";

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='".($enabled==1?"awc-ico":"awc-ico awc-ico-off")."'><i class='$ico'></i></span>";
	// Le motif vient de la base et partait BRUT dans le HTML.
	$h.="<span><span class='awc-name'>"
	   .$tpl->td_href(htmlspecialchars($pattern),"{click_to_edit}","Loadjs('$page?item-js=$ID');")."</span>";
	$h.="<div class='awc-meta awc-mono'>ID: $ID</div>";
	$h.="</span></div></td>";
	$h.="<td>$typeText</td>";
	$h.="<td class='center'>".$tpl->icon_check($enabled,"Loadjs('$page?item-enable=$ID')",null,"AsDnsAdministrator")."</td>";
	$h.="<td><div class='awc-acts'>"
	   .$tpl->icon_delete("Loadjs('$page?item-delete=$ID&md=$md')","AsDnsAdministrator")
	   ."</div></td>";
	$h.="</tr>";
	return $h;
}

/** Unbound est le resolveur : la liste blanche est celle des politiques RPZ. */
function dnswl_rpz_mode():bool{
	return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"))==1;
}

function table(){
	if(dnswl_rpz_mode()){rpzwl_table();return;}

	$page=CurrentPageName();
	$tpl=new template_admin();
	$add="Loadjs('$page?item-js=0');";

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
	$ARRAY["CMD"]="dnsfilterd.php?compile-rules=yes";
	$ARRAY["TITLE"]="{apply_webiltering_rules}";
	// Tache signee : fw.progress.php revérifie le privilege.
	$ARRAY["PRIV"]="AsDnsAdministrator";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-dnsfilter-restart')";

	$ztype=array();
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";

	// foreach() sur autre chose qu'un tableau est FATAL sous PHP 8: le daemon
	// injoignable tuait l'onglet au lieu d'afficher une liste vide.
	$results=dnswhitelist_list();
	if(!is_array($results)){$results=array();}

	$rows=array();
	foreach ($results as $ligne){
		$rows[]=dnswl_row($ligne,$ztype,$tpl,$page);
	}
	$c=count($rows);

	$html=array();
	if($tpl->IsSecurity("AsDnsAdministrator")){
	$html[]="
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_dnsfiltering_rules} </label>
			</div>";
	}

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th>{items}</th>";
	$html[]="<th>{type}</th>";
	$html[]="<th class='center'>{enable}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='dnswl-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> portait un pager footable jamais alimente: l'init ciblait
	// #table-<time>, un id qu'AUCUN <table> de cette page ne porte.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='dnswl-pager'></span>";
	$html[]="</div></div></div>";

	// Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body lisait la configuration JSON de footable comme des
	// {tokens}. ICON_SCRIPTS_COMPILE() est OBLIGATOIRE ici: ce tableau porte
	// des icon_check(), qui alimentent aussi $GLOBALS["TPL_ICON_SCRIPTS"] --
	// seule la version COMPILE draine les deux piles.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(50,"dnswl-rows","dnswl-pager");
}

// ---- Liste blanche RPZ du cache DNS (unbound) ----------------------------------

function api_notices_js(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	echo unbound_api::notices_js($tpl->javascript_parse_text("{warning}",0,true),function($s) use ($tpl){
		return $tpl->javascript_parse_text($s,0,true);
	});
}

function rpzwl_refresh_call():string{
	return "LoadAjax('dnsfilter-white-div','".CurrentPageName()."?table=yes');";
}

function rpzwl_notices_call():string{
	return "Loadjs('".CurrentPageName()."?api-notices-js=yes');";
}

function rpzwl_can_write():bool{
	$tpl=new template_admin();
	return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}

/** Un nom de la liste blanche tel qu'articarest le rend (nom DNS, « *. » permis). */
function rpzwl_name_ok($n):bool{
	return preg_match('/^[A-Za-z0-9*_][A-Za-z0-9*._-]{0,252}$/',(string)$n)==1;
}

/** Saisie : un nom par ligne (ou separes par espaces, virgules) ; 200 au plus par appel (articarest). */
function rpzwl_parse_names($raw):array{
	$out=array();
	foreach(preg_split('/[\s,;]+/',(string)$raw) as $n){
		$n=trim($n);
		if($n===""||in_array($n,$out,true)){continue;}
		$out[]=$n;
	}
	return $out;
}

/**
 * Constats d'une ecriture reussie : avertissements, notes, notes de la preuve,
 * redemarrage d'unbound, noms deja presents ou absents.
 */
function rpzwl_notices(array $r):array{
	$out=unbound_api::notices($r);
	$data=is_array($r["data"])?$r["data"]:array();
	$p=(isset($data["proof"]) && is_array($data["proof"]))?$data["proof"]:array();
	if(isset($p["notes"]) && is_array($p["notes"])){
		foreach($p["notes"] as $n){ if(is_scalar($n) && trim((string)$n)!==""){$out[]=trim((string)$n);} }
	}
	if(!empty($p["restarted"])){$out[]="{unbound_api_restarted}";}
	foreach(array("already"=>"{rpz_whitelist_already}","absent"=>"{rpz_whitelist_absent}") as $k=>$label){
		if(isset($data[$k]) && is_array($data[$k]) && count($data[$k])>0){
			$out[]="$label: ".implode(", ",array_slice(array_map("strval",$data[$k]),0,20));
		}
	}
	if(isset($data["migrated"])){
		$out[]="{rpz_whitelist_migrated}: ".intval($data["migrated"]);
	}
	if(isset($data["already_present"]) && intval($data["already_present"])>0){
		$out[]="{rpz_whitelist_already}: ".intval($data["already_present"]);
	}
	if(isset($data["skipped"]) && is_array($data["skipped"])){
		foreach(array_slice($data["skipped"],0,10) as $sk){
			if(!is_array($sk)){continue;}
			$out[]=(string)($sk["pattern"] ?? "")." : ".(string)($sk["reason"] ?? "");
		}
	}
	return $out;
}

function rpzwl_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$r=(new unbound_api())->get("/unbound/api/rpz-whitelist");
	if(!$r["ok"]){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{whitelists}||".htmlspecialchars($tpl->javascript_parse_text(unbound_api::error_message($r),0,true))));
		return;
	}
	$data=is_array($r["data"])?$r["data"]:array();
	$names=(isset($data["names"]) && is_array($data["names"]))?$data["names"]:array();

	$html=array();
	// Ce que la liste blanche ne couvre pas : les donnees DNS locales (et le
	// pare-feu DNS), le client RPZ distant tant que le cache DNS n'est pas
	// reconfigure.
	$html[]=$tpl->div_explain("{rpz_whitelist_limits}");
	if(isset($data["effective"]) && empty($data["effective"])){
		$why=isset($data["effective_reason"])?(string)$data["effective_reason"]:"";
		$html[]=$tpl->div_warning("{rpz_not_effective}||".htmlspecialchars($why));
	}
	if(rpzwl_can_write()){
		$add="Loadjs('$page?rpzwl-add-js=yes');";
		$migrate="Loadjs('$page?rpzwl-migrate-js=yes');";
		$html[]="
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$migrate\"><i class='fa fa-file-import'></i> {rpz_whitelist_migrate} </label>
			</div>";
	}

	$rows=array();
	foreach($names as $n){
		$n=(string)$n;
		if(!rpzwl_name_ok($n)){continue;}
		$h ="<tr>";
		$h.="<td><div class='awc-ident'><span class='awc-ico'><i class='fas fa-globe'></i></span>";
		$h.="<span><span class='awc-name'>".htmlspecialchars($n)."</span></span></div></td>";
		$h.="<td><div class='awc-acts'>";
		if(rpzwl_can_write()){
			$h.=$tpl->icon_delete("Loadjs('$page?rpzwl-delete-js=".urlencode($n)."')","AsDnsAdministrator");
		}
		$h.="</div></td></tr>";
		$rows[]=$h;
	}
	$c=count($rows);
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr><th>{domains}</th><th style='text-align:right'>{actions}</th></tr></thead><tbody id='rpzwl-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} $c {items}</span><span class='awc-pg' id='rpzwl-pager'></span></div>";
	$html[]="</div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(50,"rpzwl-rows","rpzwl-pager");
}

function rpzwl_add_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{new_item}","$page?rpzwl-add-popup=yes");
}

function rpzwl_add_popup(){
	$tpl=new template_admin();
	$form[]=$tpl->field_hidden("rpzwl-add","yes");
	$form[]=$tpl->field_textarea("rpzwl-names","{domains}","","100%","200px");
	$jsafter="dialogInstance1.close();".rpzwl_refresh_call().rpzwl_notices_call();
	// form_outside() ignore son 3e argument (explication) : elle est montree par div_explain (skill §11).
	echo $tpl->_ENGINE_parse_body(
		$tpl->div_explain("{rpz_whitelist_add_explain}")
		.$tpl->form_outside("{new_item}",$form,"{rpz_whitelist_add_explain}","{add}",$jsafter,"AsDnsAdministrator")
	);
}

/**
 * POST /unbound/api/rpz-whitelist {add} : articarest valide les noms, refuse
 * « * » et les domaines de premier niveau (409), ecrit, verifie par
 * unbound-checkconf, recharge et prouve qu'un nom ajoute resout. Un nom encore
 * bloque (donnees locales, client RPZ distant) : liste ecrite, erreur
 * written:true avec la raison.
 */
function rpzwl_add_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!rpzwl_can_write()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$names=rpzwl_parse_names($_POST["rpzwl-names"] ?? "");
	if(count($names)==0){echo $tpl->post_error("{domains}: {wrong_value}");return false;}
	if(count($names)>200){echo $tpl->post_error("{domains}: 200 max");return false;}
	$api=new unbound_api();
	// Preuve par resolution reelle de chaque nom ajoute (10 s par temoin), parfois un redemarrage.
	$api->timeout=unbound_page::WRITE_TIMEOUT;
	$r=$api->post("/unbound/api/rpz-whitelist",array("add"=>$names));
	if(!$r["ok"]){echo $tpl->post_error(unbound_page::error_text($r));return false;}
	$n=rpzwl_notices($r);
	if(count($n)>0){unbound_api::queue_texts($n);}
	return admin_tracks("DNS whitelist: add ".implode(", ",array_slice($names,0,20)));
}

function rpzwl_delete_js(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	if(!rpzwl_can_write()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	$name=(string)($_GET["rpzwl-delete-js"] ?? "");
	if(!rpzwl_name_ok($name)){$tpl->js_error("{domains}: {wrong_value}");return;}
	$tpl->js_confirm_delete(htmlspecialchars($name),"rpzwl-delete",$name,rpzwl_refresh_call().rpzwl_notices_call());
}

function rpzwl_delete_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	if(!rpzwl_can_write()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$name=trim((string)($_POST["rpzwl-delete"] ?? ""));
	if(!rpzwl_name_ok($name)){echo $tpl->post_error("{domains}: {wrong_value}");return false;}
	$api=new unbound_api();
	$api->timeout=unbound_page::WRITE_TIMEOUT;
	$r=$api->post("/unbound/api/rpz-whitelist",array("remove"=>array($name)));
	if(!$r["ok"]){echo $tpl->post_error(unbound_page::error_text($r));return false;}
	$n=rpzwl_notices($r);
	if(count($n)>0){unbound_api::queue_texts($n);}
	return admin_tracks("DNS whitelist: remove $name");
}

/** Reprise des anciennes entrees (domaines) : *-js -> POST rpzwl-migrate. */
function rpzwl_migrate_js(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	if(!rpzwl_can_write()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	echo dnswl_xhr_js("rpzwl-migrate","yes",rpzwl_refresh_call());
}

function rpzwl_migrate_save(){
	$tpl=new template_admin();
	if(!rpzwl_can_write()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$api=new unbound_api();
	$api->timeout=unbound_page::WRITE_TIMEOUT;
	$r=$api->post("/unbound/api/rpz-whitelist/migrate");
	if(!$r["ok"]){echo $tpl->post_error(unbound_page::error_text($r));return false;}
	$n=rpzwl_notices($r);
	if(count($n)>0){unbound_api::queue_texts($n);}
	return admin_tracks("DNS whitelist: former entries migrated to the DNS cache");
}
