<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// squid_whitelist_regex_errors(): validation des motifs a la SAISIE, deleguee
// au daemon (POST /proxy/whitelist/regex/validate). Une entree que regcomp(3)
// refuse n'invalide pas l'ACL, elle EMPECHE Squid de demarrer.
include_once(dirname(__FILE__)."/ressources/class.squid.regexvalidate.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


// Vue 1 : les deux tuiles.
if(isset($_GET["table"])){table();exit;}

// Vue 2 : le CRUD d'une liste. ?search= est le repondeur de search_block --
// il porte le tableau ET le TINY (sans quoi l'en-tete perd ses boutons des la
// premiere frappe dans la zone de recherche).
if(isset($_GET["items"])){adw_items();exit;}
if(isset($_GET["search"])){adw_items_table();exit;}
if(isset($_GET["item-js"])){adw_item_js();exit;}
if(isset($_GET["item-popup"])){adw_item_popup();exit;}
if(isset($_POST["adw-item-save"])){adw_item_save();exit;}
if(isset($_GET["item-delete-js"])){adw_item_delete_js();exit;}
if(isset($_POST["adw-item-delete"])){adw_item_delete();exit;}

// Import en masse : le textarea historique, conserve tel quel. C'est le seul
// moyen de remplacer une liste de parc d'un coup, et son enregistrement garde
// sa semantique d'origine (voir adw_item_save() pour le regime strict unitaire).
if(isset($_GET["SquidWhitelistAuthFrom-js"])){SquidWhitelistAuthFrom_js();exit;}
if(isset($_GET["SquidWhitelistAuthFrom-popup"])){SquidWhitelistAuthFrom_popup();exit;}
if(isset($_POST["SquidWhitelistAuthFrom"])){SquidWhitelistAuthFrom_save();exit;}

if(isset($_GET["SquidWhitelistAuthTo-js"])){SquidWhitelistAuthTo_js();exit;}
if(isset($_GET["SquidWhitelistAuthTo-popup"])){SquidWhitelistAuthTo_popup();exit;}
if(isset($_POST["SquidWhitelistAuthTo"])){SquidWhitelistAuthTo_save();exit;}

// Repartiteurs morts RETIRES (2026-09-14) : rule_js(), rule_tabs() et
// rule_move() n'etaient definies NI ici NI ailleurs dans le depot. Toute URL
// portant ?rule-id-js=, ?rule-tabs= ou ?acl-rule-move= rendait un fatal
// « Call to undefined function » -- c'est-a-dire, cote navigateur, un spinner
// qui ne se vide jamais. Meme vestige de squelette recopie que celui deja
// documente en tete de fichier ; le nettoyage precedent en avait laisse trois.

// ATTENTION : rule_save() se declenche sur $_POST["ID"], une cle tres generique
// qui pilote des regles SSL SANS RAPPORT avec cette page. Aucun formulaire de
// cette page ne doit nommer un champ "ID", sous peine de detourner son
// enregistrement vers /proxy/ssl-rules.
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["enable-js"])){rule_enable();exit;}

page();

// Code mort RETIRE (2026-09-14) : rule_delete_js(), new_rule_js(), new_rule_popup(),
// new_rule_save() n'etaient dispatchees par AUCUNE URL, et rule_delete() n'etait
// appelee que par la premiere. Elles pilotaient des regles SSL (/proxy/ssl-rules),
// sans rapport avec les whitelists d'authentification Active Directory de cette page.


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header(
        "{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}",
        "fa fa-align-justify",
        "{AUTHWHITELIST_EXPLAIN}",
        "$page?table=yes",
        "ad-white",
        "progress-ntlmwhite-restart",false,
        "table-ntlmwhite-rules"

    );


	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{ActiveDirectory} {authentication} {whitelists}",$html);
		echo $tpl->build_firewall();
		return;
	}
	
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_enable(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["enable-js"]);
	$rule=acls_api_get("/proxy/acls/sqlite/rule/$ID");
	if(!is_array($rule)){echo "alert('rule $ID not found');";return;}
	$current=intval($rule["enabled"] ?? 0);
	$new=($current==0) ? 1 : 0;
	$js=($current==0)
		? "$( \"#acl-$ID\" ).removeClass( \"text-muted\" );"
		: "$( \"#acl-$ID\" ).addClass( \"text-muted\" );";
	list($ok,$err)=acls_api_send("PUT","/proxy/acls/sqlite/rule/$ID/enable",["enabled"=>$new]);
	if(!$ok){echo "alert('".$tpl->javascript_parse_text($err)."')";return;}
	echo $js;
}

function SquidWhitelistAuthFrom_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$tpl->js_dialog("{do_not_authenticate_from_network_elements}","$page?SquidWhitelistAuthFrom-popup=yes&function=$function");
}
function SquidWhitelistAuthTo_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$tpl->js_dialog("{do_not_authenticate_to_network_elements}","$page?SquidWhitelistAuthTo-popup=yes&function=$function");	
}

function SquidWhitelistAuthTo_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $values=array();
	$SquidWhitelistAuthTo=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthTo"));
    foreach ($SquidWhitelistAuthTo as $index=>$ligne){
        $values[]=$index;
    }
	$form=$tpl->field_textareacode("SquidWhitelistAuthTo", null, @implode("\n",$values));
	// Depuis la vue de detail on rafraichit le TABLEAU (la fonction generee par
	// search_block) ; depuis les tuiles, la vue des tuiles. Et on FERME la
	// modale : sans ce close, elle restait ouverte au-dessus d'un contenu deja
	// recharge, ce qui se lit comme un enregistrement qui n'a pas abouti.
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$js="BootstrapDialog1.close();";
	if($function!=""){$js.="$function();";}
	else{$js.="LoadAjax('table-ntlmwhite-rules','$page?table=yes');";}
	echo $tpl->form_outside("{items}", $form,"{SquidWhitelistAuthTo_explain}","{apply}",$js,"AsProxyMonitor");
	
}

function SquidWhitelistAuthFrom_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $values=array();
	$SquidWhitelistAuthFrom=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWhitelistAuthFrom"));
    foreach ($SquidWhitelistAuthFrom as $index=>$ligne){
	    $values[]=$index;
    }
	$form=$tpl->field_textareacode("SquidWhitelistAuthFrom", null, @implode("\n",$values));
	// Depuis la vue de detail on rafraichit le TABLEAU (la fonction generee par
	// search_block) ; depuis les tuiles, la vue des tuiles. Et on FERME la
	// modale : sans ce close, elle restait ouverte au-dessus d'un contenu deja
	// recharge, ce qui se lit comme un enregistrement qui n'a pas abouti.
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$js="BootstrapDialog1.close();";
	if($function!=""){$js.="$function();";}
	else{$js.="LoadAjax('table-ntlmwhite-rules','$page?table=yes');";}
	echo $tpl->form_outside("{items}", $form,"{SquidWhitelistAuthFrom_explain}","{apply}",$js,"AsProxyMonitor");
}

function SquidWhitelistAuthTo_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$values=explode("\n",$_POST["SquidWhitelistAuthTo"]);	
	$t=array();
	$IP=new IP();
	$domains=array();
	foreach ($values as $line){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		if($IP->isIPAddressOrRange($line)){$t[$line]=$line;continue;}
		// Seules les entrees NON-IP finissent dans SquidWhitelistAuthToDoms.db,
		// declare « dstdom_regex ». Les adresses alimentent un fichier « dst »
		// et n'ont donc rien a valider ici.
		$domains[]=$line;
        $t[$line]=$line;
    }
	$err=squid_whitelist_regex_errors($domains,"{do_not_authenticate_to_network_elements}");
	// Refus AVANT l'enregistrement : sinon la cle est posee, la prochaine
	// generation ecarte l'entree en silence, et l'exploitant croit sa whitelist
	// active. echo => la modale reste ouverte et affiche la raison (regle §5).
	if($err!=""){ echo $tpl->post_error($err); return; }
	$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($t), "SquidWhitelistAuthTo");
}

function SquidWhitelistAuthFrom_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$values=explode("\n",$_POST["SquidWhitelistAuthFrom"]);
    $t=array();
	$IP=new IP();
	foreach ($values as $line){
		$line=trim(strtolower($line));
		if($line==null){continue;}
		if(!$IP->isIPAddressOrRange($line)){
			if(!$IP->IsvalidMAC($line)){
				continue;
			}
		}
		
		$t[$line]=$line;
		
	}
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($t), "SquidWhitelistAuthFrom");
}






function rule_save(){
	$ID=intval($_POST["ID"]);
	$body=[];
	$body["description"]=url_decode_special_tool($_POST["description"] ?? "");
	$body["enabled"]=intval($_POST["enabled"] ?? 0);
	$body["crypt"]=intval($_POST["crypt"] ?? 0);
	$body["trust"]=intval($_POST["trust"] ?? 0);
	list($ok,$err)=acls_api_send("PUT","/proxy/ssl-rules/$ID",$body);
	if(!$ok){echo htmlspecialchars($err);}
}







// ---------------------------------------------------------------------------
// Les deux listes de la page
// ---------------------------------------------------------------------------

/**
 * Tout ce qui distingue les deux whitelists, en un seul endroit : la cle Valkey,
 * les jetons, la teinte du badge et l'icone. Sans $list, rend la table complete.
 */
function adw_list_meta($list=null){
	$m=array();
	$m["From"]=array(
		"key"     => "SquidWhitelistAuthFrom",
		"title"   => "{do_not_authenticate_from_network_elements}",
		"explain" => "{SquidWhitelistAuthFrom_explain}",
		"badge"   => "{source}",
		"tone"    => "src",
		"ico"     => "fas fa-desktop",
	);
	$m["To"]=array(
		"key"     => "SquidWhitelistAuthTo",
		"title"   => "{do_not_authenticate_to_network_elements}",
		"explain" => "{SquidWhitelistAuthTo_explain}",
		"badge"   => "{destination}",
		"tone"    => "dst",
		"ico"     => "fas fa-globe",
	);
	if($list===null){return $m;}
	return isset($m[$list])?$m[$list]:null;
}

/**
 * Liste demandee, en liste blanche STRICTE.
 *
 * Ce parametre choisit une CLE VALKEY : accepter autre chose que les deux
 * valeurs prevues laisserait une URL forgee designer n'importe quel reglage de
 * l'appliance, en lecture comme en ECRITURE. Rend "" si la valeur n'est pas
 * reconnue, et chaque appelant traite "" comme une erreur.
 */
function adw_list_param(){
	$l="";
	if(isset($_GET["list"])){$l=(string)$_GET["list"];}
	if(isset($_POST["adw-list"])){$l=(string)$_POST["adw-list"];}
	if($l!=="From" && $l!=="To"){return "";}
	return $l;
}

/** Les entrees d'une liste, dedoublonnees et triees. */
function adw_list_load($list){
	$meta=adw_list_meta($list);
	if($meta===null){return array();}
	$a=@unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO($meta["key"]));
	// unserialize() rend false sur une cle absente ou corrompue. Sans ce garde,
	// le foreach fatalise en PHP 8 -- et le parc va de 7.3 a 8.x.
	if(!is_array($a)){return array();}
	$out=array();
	foreach($a as $k=>$v){
		$k=trim((string)$k);
		if($k===""){continue;}
		$out[$k]=$k;
	}
	ksort($out);
	return $out;
}

/** Ecrit la liste. Le tableau est indexe PAR LA VALEUR, comme a l'origine. */
function adw_list_save($list,$values){
	$meta=adw_list_meta($list);
	if($meta===null){return false;}
	$t=array();
	foreach($values as $v){
		$v=trim((string)$v);
		if($v===""){continue;}
		$t[$v]=$v;
	}
	$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($t),$meta["key"]);
	return true;
}

/**
 * Validation du CRUD unitaire -- regime STRICT : une entree refusee est DITE.
 *
 * L'import en masse garde au contraire la tolerance silencieuse historique
 * (SquidWhitelistAuthFrom_save() fait « continue; » sur une ligne invalide) :
 * on ne change pas la semantique d'un chemin qui existait deja et dont des
 * exploitants dependent. Rend "" si l'entree est acceptable.
 */
function adw_validate_entry($list,$value){
	$value=trim(strtolower($value));
	if($value===""){return "{cannot_be_a_null_value}";}
	$IP=new IP();
	if($IP->isIPAddressOrRange($value)){return "";}
	if($list=="From"){
		if($IP->IsvalidMAC($value)){return "";}
		return "{invalid_ip_address} $value";
	}
	// Cote « To », une entree non-IP part en « dstdom_regex » : elle doit etre
	// compilable. Une regex que regcomp(3) refuse n'invalide pas l'ACL, elle
	// EMPECHE Squid de demarrer.
	return squid_whitelist_regex_errors(array($value),"{do_not_authenticate_to_network_elements}");
}

// ---------------------------------------------------------------------------
// Vue 1 : les tuiles  (maquette stitch_whitelist_tile_view_system)
// ---------------------------------------------------------------------------

/**
 * Referme les <b> restes ouverts dans un libelle traduit.
 *
 * Un jeton portant un [b] sans [/b] ne se contente pas de mettre SA phrase en
 * gras : l'algorithme d'adoption de HTML5 ROUVRE l'element sur les noeuds
 * freres suivants. Mesure faite sur SquidWhitelistAuthTo_explain, malforme
 * dans 7 des 8 langues -- l'en-tete d'apercu de la tuile se retrouvait
 * enveloppe dans un <b> unique, devenait un seul element flex, et
 * justify-content:space-between n'avait plus rien a separer : « CURRENT
 * RULESEnabled » colles. La donnee est corrigee par ailleurs ; ce garde
 * empeche la classe entiere de defaut de casser une mise en page.
 */
function adw_close_tags($html){
	$open =preg_match_all('#<b\b[^>]*>#i',$html);
	$close=preg_match_all('#</b>#i',$html);
	if($open>$close){$html.=str_repeat("</b>",$open-$close);}
	return $html;
}

/**
 * Feuille des tuiles. Emise HORS de _ENGINE_parse_body() : sa regex #{(.+?)}#
 * lit chaque corps de regle CSS comme un {token} et declenche une recherche
 * memcached par regle. Verts maison (#1ab394 / #ecfdf5), pas le teal Tailwind
 * de la maquette : la console a deja sa palette.
 */
function adw_tiles_css(){
	if(isset($GLOBALS["ADW_TILES_CSS"])){return "";}
	$GLOBALS["ADW_TILES_CSS"]=true;
	return "<style>
.adw-sechead{display:flex;align-items:center;gap:10px;margin:4px 0 14px 0;flex-wrap:wrap;}
.adw-sectitle{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;}
.adw-pill{font-size:11px;font-weight:600;color:#334155;background:#e2e8f0;border-radius:999px;padding:3px 10px;white-space:nowrap;}
.adw-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;}
@media (max-width:820px){.adw-grid{grid-template-columns:1fr;}}
.adw-tile{background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 2px rgba(15,23,42,.05);
 display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;transition:box-shadow .2s;}
.adw-tile:hover{box-shadow:0 4px 10px rgba(15,23,42,.08);}
.adw-tile-body{padding:20px;}
.adw-tile-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;}
.adw-badge{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;border-radius:999px;padding:3px 10px;white-space:nowrap;}
.adw-badge-src{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;}
.adw-badge-dst{background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;}
.adw-dot{width:7px;height:7px;border-radius:50%;display:inline-block;}
.adw-badge-src .adw-dot{background:#10b981;}
.adw-badge-dst .adw-dot{background:#0ea5e9;}
.adw-count{font-size:12px;font-weight:600;color:#334155;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:5px;padding:2px 8px;white-space:nowrap;}
.adw-title{font-size:14px;font-weight:600;line-height:1.35;margin:0;}
.adw-title a{color:#1e293b;text-decoration:none;display:inline-flex;align-items:flex-start;gap:6px;}
.adw-title a:hover{color:#18a689;text-decoration:underline;}
.adw-expl{margin-top:10px;font-size:12px;color:#64748b;line-height:1.6;}
.adw-prev{margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;}
.adw-prev-head{display:flex;align-items:center;justify-content:space-between;gap:10px;
 font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;}
.adw-prev-on{color:#047857;font-weight:700;text-transform:none;letter-spacing:0;}
.adw-prev-off{color:#94a3b8;font-weight:700;text-transform:none;letter-spacing:0;}
.adw-prev-line{display:flex;align-items:center;gap:8px;font-size:12px;font-family:monospace;color:#334155;line-height:1.7;}
.adw-prev-more{font-size:11px;color:#94a3b8;margin-top:4px;}
.adw-foot{padding:12px 20px;background:#f8fafc;border-top:1px solid #eef2f6;display:flex;align-items:center;justify-content:space-between;gap:10px;}
.adw-view{font-size:12px;font-weight:600;color:#18a689;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.adw-view:hover{color:#1ab394;text-decoration:underline;}
.adw-add{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:#334155;text-transform:capitalize;
 background:#fff;border:1px solid #cbd5e1;border-radius:5px;padding:4px 10px;text-decoration:none;cursor:pointer;}
.adw-add:hover{background:#f1f5f9;border-color:#94a3b8;color:#1e293b;}
.adw-add .adw-plus{color:#1ab394;font-weight:700;}
.adw-warn{margin-top:20px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:16px 18px;display:flex;gap:12px;align-items:flex-start;}
.adw-warn-ico{color:#f59e0b;font-size:16px;flex:0 0 auto;line-height:1.4;}
.adw-warn-t{font-size:13px;font-weight:600;color:#92400e;margin-bottom:4px;}
.adw-warn-d{font-size:12px;color:#b45309;line-height:1.65;}
</style>";
}

/** Le titre de section et sa pastille de comptage. */
function adw_section_head($count){
	$h=array();
	$h[]="<div class='adw-sechead'>";
	$h[]="<span class='adw-sectitle'>{configured} {whitelists}</span>";
	$h[]="<span class='adw-pill'>".intval($count)." {categories}</span>";
	$h[]="</div>";
	return @implode("\n",$h);
}

/** Une tuile : badge, compteur, titre cliquable, explication, apercu, pied. */
function adw_tile($list){
	$page=CurrentPageName();
	// L'instance manquait: $tpl valait null et l'analyse du libelle plus bas
	// (adw_close_tags) levait « Call to a member function _ENGINE_parse_body()
	// on null » -- fatal, donc la vue en tuiles ne s'affichait pas du tout.
	$tpl=new template_admin();
	$meta=adw_list_meta($list);
	if($meta===null){return "";}
	$values=adw_list_load($list);
	$n=count($values);
	$open="LoadAjax('table-ntlmwhite-rules','$page?items=yes&list=$list');";
	$add ="Loadjs('$page?item-js=yes&list=$list&ret=tiles');";

	// Apercu : les trois premieres entrees, htmlspecialchars() car ce sont des
	// valeurs saisies par l'exploitant et non de la sortie de traduction.
	$prev=array();
	$i=0;
	foreach($values as $v){
		if($i>=3){break;}
		$prev[]="<div class='adw-prev-line'><i class='".$meta["ico"]."'></i>"
		       ."<span>".htmlspecialchars($v)."</span></div>";
		$i++;
	}
	if($n==0){$prev[]="<div class='adw-prev-line'><span style='color:#94a3b8'>{no_item}</span></div>";}
	if($n>3){$prev[]="<div class='adw-prev-more'>+ ".($n-3)." {items}</div>";}

	$state=($n>0)?"<span class='adw-prev-on'>{enabled}</span>":"<span class='adw-prev-off'>{no_item}</span>";

	$h=array();
	$h[]="<article class='adw-tile'>";
	$h[]="<div class='adw-tile-body'>";
	$h[]="<div class='adw-tile-top'>";
	$h[]="<span class='adw-badge adw-badge-".$meta["tone"]."'><span class='adw-dot'></span>".$meta["badge"]."</span>";
	$h[]="<span class='adw-count'>$n {items}</span>";
	$h[]="</div>";
	$h[]="<h3 class='adw-title'><a href=\"javascript:blur();\" OnClick=\"$open\">"
	    .$meta["title"]."<i class='fas fa-chevron-right' style='font-size:11px;color:#94a3b8'></i></a></h3>";
	// Le libelle est parse ICI pour pouvoir refermer ses balises : non parse, il
	// porte encore [b]/[/b] et le desequilibre ne serait visible qu'au rendu.
	$h[]="<div class='adw-expl'>".adw_close_tags($tpl->_ENGINE_parse_body($meta["explain"]))."</div>";
	$h[]="<div class='adw-prev'>";
	$h[]="<div class='adw-prev-head'><span>{current_rules}</span>$state</div>";
	$h[]=@implode("\n",$prev);
	$h[]="</div>";
	$h[]="</div>";
	$h[]="<div class='adw-foot'>";
	$h[]="<a class='adw-view' href=\"javascript:blur();\" OnClick=\"$open\">{adwhite_view_items}"
	    ."<i class='fas fa-arrow-right' style='font-size:10px'></i></a>";
	$h[]="<a class='adw-add' href=\"javascript:blur();\" OnClick=\"$add\"><span class='adw-plus'>+</span>{add}</a>";
	$h[]="</div>";
	$h[]="</article>";
	return @implode("\n",$h);
}

/** La banniere d'avertissement de la maquette. */
function adw_security_banner(){
	$h=array();
	$h[]="<div class='adw-warn'>";
	$h[]="<div class='adw-warn-ico'><i class='fas fa-triangle-exclamation'></i></div>";
	$h[]="<div><div class='adw-warn-t'>{warning}</div>";
	$h[]="<div class='adw-warn-d'>{adwhite_security_warning}</div></div>";
	$h[]="</div>";
	return @implode("\n",$h);
}

/** Le TINY commun aux deux vues : meme titre, memes boutons de base. */
function adw_tiny($tpl,$topbuttons){
	$TINY=array();
	$TINY["TITLE"]="{ActiveDirectory}&nbsp;&raquo;&nbsp;{authentication}&nbsp;&raquo;&nbsp;{whitelists}";
	$TINY["ICO"]="fa fa-align-justify";
	$TINY["EXPL"]="{AUTHWHITELIST_EXPLAIN}";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}

/** Le bouton « appliquer les regles », identique dans les deux vues. */
function adw_apply_js($tpl){
	return $tpl->framework_buildjs(
		"/proxy/acls/httpaccess/final",
		"BuildHTTPAccessFinal.progress",
		"BuildHTTPAccessFinal.log",
		"progress-ntlmwhite-restart",
		"document.getElementById('progress-ntlmwhite-restart').innerHTML='';"
	);
}

// ---------------------------------------------------------------------------
// Vue 2 : le CRUD d'une liste
// ---------------------------------------------------------------------------

/**
 * L'hote de la vue de detail : juste la barre de recherche.
 *
 * search_block declenche lui-meme ?search=… au bout d'environ 800 ms ; c'est ce
 * repondeur qui rend le tableau ET le TINY complet (regle §7d : il est le seul
 * a connaitre $function, le nom de la fonction de rafraichissement generee).
 * On pose tout de meme ici un TINY REDUIT -- {back} et {apply_rules} seulement --
 * pour que le retour soit disponible tout de suite : sans lui l'exploitant reste
 * pres d'une seconde devant une page sans aucun moyen de revenir. On y omet
 * volontairement {add}, dont la modale a besoin de $function pour rafraichir.
 */
function adw_items(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$list=adw_list_param();
	if($list==""){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return;}

	$topbuttons=array();
	$topbuttons[]=array("LoadAjax('table-ntlmwhite-rules','$page?table=yes');","fa fa-arrow-left","{back}");
	$topbuttons[]=array(adw_apply_js($tpl),ico_save,"{apply_rules}");

	$html=array();
	$html[]="<div style='margin-top:10px'>";
	// $urlToadd ne doit PAS contenir « items=yes » : le repartiteur teste
	// ?items= AVANT ?search=, et l'URL de recherche reviendrait donc ici --
	// une boucle qui ne rend jamais le tableau.
	$html[]=$tpl->search_block($page,"","","","&list=$list");
	$html[]="</div>";
	$html[]="<script>".adw_tiny($tpl,$topbuttons)."</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

/** Le repondeur ?search= : il possede le tableau ET le TINY complet. */
function adw_items_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$list=adw_list_param();
	if($list==""){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return;}
	$meta=adw_list_meta($list);

	// Liste blanche OBLIGATOIRE : $function part brut dans des OnClick et dans
	// les $jsafter des modales. Un ?function=0;alert(document.cookie)// forge
	// s'executerait dans la session de l'administrateur connecte.
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$search=trim((string)(isset($_GET["search"])?$_GET["search"]:""));

	$values=adw_list_load($list);
	$total=count($values);
	$rows=array();
	foreach($values as $v){
		if($search!="" && stripos($v,$search)===false){continue;}
		$venc=rawurlencode($v);
		$edit="Loadjs('$page?item-js=yes&list=$list&value=$venc&function=$function');";
		$del ="Loadjs('$page?item-delete-js=yes&list=$list&value=$venc&function=$function');";
		$r ="<tr>";
		$r.="<td><div class='awc-ident'>";
		$r.="<span class='awc-ico'><i class='".$meta["ico"]."'></i></span>";
		$r.="<span><span class='awc-name'><a href=\"javascript:blur();\" OnClick=\"$edit\">"
		   .htmlspecialchars($v)."</a></span></span>";
		$r.="</div></td>";
		$r.="<td><div class='awc-acts'>";
		$r.="<a class='awc-act' title='{edit}' href=\"javascript:blur();\" OnClick=\"$edit\">"
		   ."<i class='fas fa-pen'></i></a>";
		$r.="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" OnClick=\"$del\">"
		   ."<i class='fas fa-trash-can'></i></a>";
		$r.="</div></td></tr>";
		$rows[]=$r;
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>".$meta["title"]."</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='adw-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_item}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} ".count($rows)." / $total {items}</span>";
	$html[]="<span class='awc-pg' id='adw-pager'></span>";
	$html[]="</div></div></div>";

	$topbuttons=array();
	$topbuttons[]=array("Loadjs('$page?item-js=yes&list=$list&function=$function');",ico_plus,"{add}");
	$topbuttons[]=array("Loadjs('$page?".$meta["key"]."-js=yes&list=$list&function=$function');",ico_import,"{bulk_import}");
	$topbuttons[]=array("LoadAjax('table-ntlmwhite-rules','$page?table=yes');","fa fa-arrow-left","{back}");
	$topbuttons[]=array(adw_apply_js($tpl),ico_save,"{apply_rules}");

	// Le TINY part APRES le tableau, dans le meme <script> : les boutons doivent
	// exister dans le DOM avant que l'animation d'icone du TINY ne tourne.
	$js ="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n".adw_tiny($tpl,$topbuttons)."</script>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n",$html))
	    .$js
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"adw-rows","adw-pager");
}

/** Ouvre la modale d'ajout (valeur vide) ou d'edition. */
function adw_item_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$list=adw_list_param();
	if($list==""){return;}
	$value=(string)(isset($_GET["value"])?$_GET["value"]:"");
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$ret=(isset($_GET["ret"]) && $_GET["ret"]=="tiles")?"tiles":"";
	$title=($value==="")?"{add}":"{edit}";
	$tpl->js_dialog2($title,
		"$page?item-popup=yes&list=$list&value=".rawurlencode($value)."&function=$function&ret=$ret",560);
}

/** Le formulaire d'UNE entree. */
function adw_item_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$list=adw_list_param();
	if($list==""){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return;}
	$meta=adw_list_meta($list);
	$value=(string)(isset($_GET["value"])?$_GET["value"]:"");
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$ret=(isset($_GET["ret"]) && $_GET["ret"]=="tiles")?"tiles":"";

	$form=array();
	// « adw-old » porte la valeur d'origine : le tableau Valkey est indexe PAR
	// LA VALEUR, donc editer, c'est retirer l'ancienne cle et poser la nouvelle.
	// Aucun champ ne s'appelle "ID" : cette page dispatche $_POST["ID"] vers
	// rule_save(), qui ecrit des regles SSL sans rapport (voir le repartiteur).
	$form[]=$tpl->field_hidden("adw-item-save","yes");
	$form[]=$tpl->field_hidden("adw-list",$list);
	$form[]=$tpl->field_hidden("adw-old",$value);
	$form[]=$tpl->field_text("adw-value","{value}",$value,true);

	$refresh="";
	if($ret=="tiles"){$refresh="LoadAjax('table-ntlmwhite-rules','$page?table=yes');";}
	elseif($function!=""){$refresh="$function();";}

	$html=array();
	$html[]=$tpl->div_explain($meta["title"]."||".$meta["explain"]);
	$html[]=$tpl->form_outside("",@implode("\n",$form),"","{apply}",
		"dialogInstance2.close();$refresh","AsProxyMonitor");
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

/**
 * Enregistre UNE entree.
 *
 * N'echoue rien en cas de succes : form_outside traite toute reponse non vide
 * comme une erreur et ne lance son $jsafter (fermeture + rafraichissement) que
 * sur une reponse vide. Les refus passent par post_error(), qui produit la
 * fenetre d'erreur -- un echo brut atterrirait dans la banniere en haut de la
 * modale, hors ecran sur un formulaire long.
 */
function adw_item_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$list=adw_list_param();
	if($list==""){echo $tpl->post_error("{error}");return;}
	$value=trim(strtolower((string)(isset($_POST["adw-value"])?$_POST["adw-value"]:"")));
	$old  =trim(strtolower((string)(isset($_POST["adw-old"])?$_POST["adw-old"]:"")));

	$err=adw_validate_entry($list,$value);
	// Refus AVANT l'enregistrement : une entree posee puis ecartee en silence a
	// la generation laisse croire a une whitelist active qui ne l'est pas.
	if($err!=""){echo $tpl->post_error($err);return;}

	$values=adw_list_load($list);
	if($old!=="" && isset($values[$old])){unset($values[$old]);}
	if($old!==$value && isset($values[$value])){echo $tpl->post_error("{already_exists}: $value");return;}
	$values[$value]=$value;
	adw_list_save($list,$values);
	admin_tracks("Proxy AD whitelist ($list): add/update entry $value");
}

/** Demande confirmation avant suppression (icon_delete n'en porte aucune). */
function adw_item_delete_js(){
	$tpl=new template_admin();
	$list=adw_list_param();
	if($list==""){return;}
	$value=(string)(isset($_GET["value"])?$_GET["value"]:"");
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$after=($function!="")?"$function();":"";
	// js_confirm_delete ne POSTe QU'UN champ : la liste voyage donc avec la
	// valeur, encodee en base64 pour supporter n'importe quel caractere.
	$tpl->js_confirm_delete($value,"adw-item-delete",base64_encode($list."\t".$value),$after);
}

/** Supprime UNE entree. Meme contrat de reponse que adw_item_save(). */
function adw_item_delete(){
	$tpl=new template_admin();
	$raw=base64_decode((string)$_POST["adw-item-delete"]);
	$parts=explode("\t",$raw,2);
	if(count($parts)<2){echo $tpl->post_error("{error}");return;}
	$list=$parts[0];
	if($list!=="From" && $list!=="To"){echo $tpl->post_error("{error}");return;}
	$value=$parts[1];
	$values=adw_list_load($list);
	if(!isset($values[$value])){return;}   // deja absent : rien a dire, c'est le resultat voulu
	unset($values[$value]);
	adw_list_save($list,$values);
	admin_tracks("Proxy AD whitelist ($list): delete entry $value");
}

/** Vue 1 : les deux tuiles de la maquette, plus la banniere d'avertissement. */
function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$topbuttons=array();
	$topbuttons[]=array(adw_apply_js($tpl),ico_save,"{apply_rules}");

	$meta=adw_list_meta();
	$html=array();
	$html[]=adw_section_head(count($meta));
	$html[]="<div class='adw-grid'>";
	foreach($meta as $list=>$ignored){$html[]=adw_tile($list);}
	$html[]="</div>";
	$html[]=adw_security_banner();
	$html[]="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n".adw_tiny($tpl,$topbuttons)."</script>";

	// La feuille est concatenee AUTOUR du parse, jamais dedans.
	echo adw_tiles_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}



