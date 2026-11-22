<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once("ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbrules.meta.inc");

if(isset($_GET["list"])){table();exit;}
if(isset($_GET["period-add-js"])){period_add_js();exit;}
if(isset($_GET["period-add"])){period_add();exit;}
if(isset($_GET["period-del-js"])){period_del_js();exit;}
if(isset($_GET["category-post-js"])){categoy_post();exit;}
if(isset($_GET["category-del-js"])){category_del();exit;}	
if(isset($_POST["RuleMatchTime"])){rule_time_main_save();exit;}
if(isset($_POST["TIMEID"])){period_save();exit;}
if(isset($_POST["quick-days"])){period_quick_add();exit;}



	//category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md

page();

function period_add_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=isset($_GET["ID"])?intval($_GET["ID"]):0;
	$TIMEID=isset($_GET["TIMEID"])?intval($_GET["TIMEID"]):-1;
	// Le titre est reconstruit ICI, a partir de la plage lue en base. L'ancienne version
	// le recevait dans l'URL ($_GET["title"]) et l'injectait dans js_dialog2(), qui le
	// pose dans un litteral JavaScript: une apostrophe decapitait la modale, le reste
	// s'y executait. Rien de ce qui vient du navigateur n'a besoin de traverser cet appel.
	$title="{set_the_period}";
	if($TIMEID>=0){
		$load=ufdbtime_load($ID);
		if(isset($load["TimeSpace"]["TIMES"][$TIMEID])){
			$a=$load["TimeSpace"]["TIMES"][$TIMEID];
			$title="{period} ".ufdbtime_hhmm($a,"BEGIN")." - ".ufdbtime_hhmm($a,"END");
		}
	}
	$tpl->js_dialog2($title,"$page?period-add=yes&ID=$ID&TIMEID=$TIMEID");
}
function period_del_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!ufdbtime_require_admin()){ echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return; }
	$ID=isset($_GET["ID"])?intval($_GET["ID"]):0;
	$TIMEID=isset($_GET["TIMEID"])?intval($_GET["TIMEID"]):-1;

	$load=ufdbtime_load($ID);
	$TimeSpace=$load["TimeSpace"];
	if(!isset($TimeSpace["TIMES"][$TIMEID])){ echo "UfdbTimeReload();"; return; }
	unset($TimeSpace["TIMES"][$TIMEID]);

	$err=ufdbtime_store($ID,$load["ligne"],$TimeSpace);
	if($err!=""){ echo "alert(".json_encode($tpl->javascript_parse_text($err)).");"; return; }
	admin_tracks("Web filter rule $ID: removed time range $TIMEID");
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/compile");
	// Rechargement du fragment plutot que « $('#<md5 venu de l URL>').remove() »:
	// cet identifiant arrivait brut du navigateur dans du JavaScript emis, et retirer
	// la ligne a la main laissait le compteur du pied de tableau sur l'ancien total.
	echo "UfdbTimeReload();";
}


/**
 * category_del / categoy_post — retrait et ajout d'une categorie sur une regle.
 *
 * Trois valeurs arrivaient du navigateur et etaient CONCATENEES dans le SQL
 * ($category, $modeblk, $ID) sur un DELETE et un INSERT, et une quatrieme ($_GET["md"])
 * etait ecrite telle quelle dans le JavaScript renvoye. Elles sont desormais liees
 * (QUERY_SQL_PARAMS) ou converties, et le fragment se recharge au lieu de retirer une
 * ligne par un identifiant venu de l'URL.
 */
function category_del(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!ufdbtime_require_admin()){ echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return; }
	$category=(string)$_GET["category-del-js"];
	$ID=isset($_GET["ID"])?intval($_GET["ID"]):0;
	$modeblk=isset($_GET["modeblk"])?(string)$_GET["modeblk"]:"";
	if(ufdbrules_meta_locked_rule($ID)){ echo "alert(".json_encode(ufdbrules_meta_text()).");"; return; }
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$q->QUERY_SQL_PARAMS("DELETE FROM webfilter_blks WHERE category=? AND modeblk=? AND webfilter_id=?",
		array($category,$modeblk,$ID));
	if(!$q->ok){
		writelogs("webfilter_blks: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
	}
	admin_tracks("Web filter rule $ID: removed category $category ($modeblk)");
	echo "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
}

function categoy_post(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	if(!ufdbtime_require_admin()){ echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return; }
	$category=(string)$_GET["category-post-js"];
	$ID=isset($_GET["ID"])?intval($_GET["ID"]):0;
	$modeblk=isset($_GET["modeblk"])?(string)$_GET["modeblk"]:"";
	if(ufdbrules_meta_locked_rule($ID)){ echo "alert(".json_encode(ufdbrules_meta_text()).");"; return; }
	$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$rows=$q->QUERY_SQL_PARAMS("SELECT ID FROM webfilter_blks WHERE category=? AND modeblk=? AND webfilter_id=?",
		array($category,$modeblk,$ID));
	if(isset($rows[0]["ID"]) && intval($rows[0]["ID"])>0){ return; }

	$q->QUERY_SQL_PARAMS("INSERT INTO webfilter_blks (webfilter_id,category,modeblk) VALUES (?,?,?)",
		array($ID,$category,$modeblk));
	if(!$q->ok){
		writelogs("webfilter_blks: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
	}
	admin_tracks("Web filter rule $ID: added category $category ($modeblk)");
	echo "RefreshUfdbCategoriesList();\n";
	echo "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
}

/**
 * ufdbtime_require_admin — le garde SERVEUR.
 *
 * Cacher un bouton n'est pas un controle de privilege: le bloc d'aiguillage en tete
 * de page repond a n'importe quelle session authentifiee, quelle que soit l'interface
 * qu'on lui a montree. Les trois ecritures de cette page (comportement, ajout, retrait)
 * passent donc par ici, avant de toucher quoi que ce soit.
 */
function ufdbtime_require_admin(){
    $users=new usersMenus();
    if(!$users->AsDansGuardianAdministrator){ return false; }
    return true;
}

/**
 * ufdbtime_load — lit la configuration temporelle d'une regle.
 *
 * ID=0 designe la regle principale par defaut, rangee dans un reglage; ID>0 une ligne
 * de webfilter_rules. Les cinq fonctions de cette page recopiaient ces huit lignes,
 * et donc aussi ses deux defauts:
 *   - $ID venait de $_GET/$_POST et etait CONCATENE dans le SQL. « 0 OR 1=1 » sur la
 *     mise a jour reecrivait la plage horaire de TOUTES les regles du filtre web.
 *   - unserialize() renvoie false sur une chaine vide -- ce qui est l'etat NORMAL
 *     d'une regle neuve. $TimeSpace["TIMES"] sur false leve un avertissement PHP 8 et
 *     le foreach qui suit s'arretait la.
 * Retourne toujours un tableau exploitable, jamais false.
 */
function ufdbtime_load($ID){
    $ID=intval($ID);
    $ligne=array();
    if($ID==0){
        $raw=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule");
        $ligne=@unserialize(base64_decode($raw));
        if(!is_array($ligne)){ $ligne=array(); }
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $rows=$q->QUERY_SQL_PARAMS("SELECT TimeSpace FROM webfilter_rules WHERE ID=?",array($ID));
        $ligne=isset($rows[0])?$rows[0]:array();
    }
    $TimeSpace=@unserialize(base64_decode(isset($ligne["TimeSpace"])?$ligne["TimeSpace"]:""));
    if(!is_array($TimeSpace)){ $TimeSpace=array(); }
    if(!isset($TimeSpace["TIMES"]) || !is_array($TimeSpace["TIMES"])){ $TimeSpace["TIMES"]=array(); }
    if(!isset($TimeSpace["RuleMatchTime"]) || $TimeSpace["RuleMatchTime"]===null){ $TimeSpace["RuleMatchTime"]="none"; }
    if(!isset($TimeSpace["RuleAlternate"]) || $TimeSpace["RuleAlternate"]===null){ $TimeSpace["RuleAlternate"]="none"; }
    return array("ligne"=>$ligne,"TimeSpace"=>$TimeSpace);
}

/** ufdbtime_store — ecrit la configuration temporelle. Retourne "" ou le message d'erreur. */
function ufdbtime_store($ID,$ligne,$TimeSpace){
    $ID=intval($ID);
    // Verrou Artica Meta : SEUL ecrivain de la page (periode, ajout rapide, comportement,
    // suppression) -- regle Meta, ou regle par defaut tenue, relue ici a chaque ecriture.
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_text(); }
    $encoded=base64_encode(serialize($TimeSpace));
    if($ID==0){
        $ligne["TimeSpace"]=$encoded;
        $sock=new sockets();
        $sock->SaveConfigFile(base64_encode(serialize($ligne)),"DansGuardianDefaultMainRule");
        return "";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    // Parametre lie, pas concatene: $encoded contient du base64 (inoffensif) mais $ID
    // vient du navigateur. QUERY_SQL_PARAMS est une METHODE distincte, pas un second
    // argument de QUERY_SQL -- celui-ci est deja pris par le nom de base historique.
    $q->QUERY_SQL_PARAMS("UPDATE webfilter_rules SET TimeSpace=? WHERE ID=?",array($encoded,$ID));
    if(!$q->ok){
        // Le texte SQLite nomme tables et colonnes: journal, pas ecran.
        writelogs("ufdbtime_store: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        return "{database_error}";
    }
    return "";
}

/** Les sept jours, dans l'ordre de la semaine, avec leur cle de stockage. */
function ufdbtime_days(){
    return array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday",
                 "f"=>"Friday","a"=>"Saturday","s"=>"Sunday");
}

/**
 * ufdbtime_lower — minuscules, mbstring present ou non.
 *
 * mbstring n'est pas garanti sur toute la flotte, et un appel non garde a
 * mb_strtolower() est une fatale a l'affichage -- un spinner qui ne s'arrete jamais,
 * sans un mot dans le navigateur. Meme precaution que pour mb_substr() ci-dessous.
 */
function ufdbtime_lower($s){
    if(function_exists("mb_strtolower")){ return mb_strtolower($s,"UTF-8"); }
    return strtolower($s);
}

/**
 * ufdbtime_day_short — l'abrege d'un jour, derive de sa TRADUCTION.
 *
 * Trois lettres du nom traduit: « Monday » donne Mon, « Lundi » Lun, « Montag » Mon.
 * Aucun jeton supplementaire a creer et a maintenir dans neuf langues pour une
 * information que le nom complet porte deja.
 */
function ufdbtime_day_short($fullTranslated){
    // DECODER d'abord. La base de traduction range les accents en entites HTML, et
    // couper « S&aacute;bado » au troisieme caractere donnerait « S&a » -- une entite
    // tronquee, que le navigateur affiche telle quelle. On revient au texte, on coupe,
    // et l'appelant re-echappe pour l'attribut ou la cellule.
    $s=trim(strip_tags(html_entity_decode($fullTranslated,ENT_QUOTES,"UTF-8")));
    if(function_exists("mb_substr")){ return mb_substr($s,0,3,"UTF-8"); }
    return substr($s,0,3);
}

/**
 * ufdbtime_css — la feuille du planificateur, d'apres « Vigil Sentinel »
 * (/home/claude/images/stitch_time_based_filter_scheduler.zip).
 *
 * Une seule fois par requete: la page appelle plusieurs widgets qui en dependent.
 * A CONCATENER APRES _ENGINE_parse_body() -- son motif #{(.+?)}# lirait chaque corps
 * de regle CSS comme un {token} et declencherait une recherche memcached par regle.
 *
 * Le vert (#059669 emerald / #0d9488 teal) porte ici l'action, pas la severite: cette
 * page ne diagnostique rien, elle configure. Le rouge reste reserve au retrait.
 */
function ufdbtime_css(){
    if(!empty($GLOBALS["UFDBTIME_CSS_DONE"])){return "";}
    $GLOBALS["UFDBTIME_CSS_DONE"]=true;
    return "<style>
.ufdbt{color:#0f172a;font-size:13px}
.ufdbt *{box-sizing:border-box}
.ufdbt-stack>*+*{margin-top:20px}
.ufdbt-note{display:flex;gap:12px;align-items:flex-start;background:linear-gradient(90deg,#f0f9ff,rgba(239,246,255,.6));border:1px solid rgba(186,230,253,.8);border-radius:12px;padding:16px;color:#0c4a6e}
.ufdbt-note-i{color:#0284c7;font-size:17px;line-height:20px;flex:0 0 auto}
.ufdbt-note-t{font-weight:600;color:#082f49;margin:0 0 3px}
.ufdbt-note-b{color:rgba(7,89,133,.9);line-height:1.55;margin:0}
.ufdbt-note-m{font-family:'JetBrains Mono',ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#0369a1;margin:5px 0 0}
.ufdbt-card{background:rgba(248,250,252,.7);border:1px solid rgba(226,232,240,.9);border-radius:12px;padding:20px}
.ufdbt-h{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin:0 0 16px}
.ufdbt-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.ufdbt-lab{display:block;font-size:13px;font-weight:500;color:#334155;margin:0 0 6px}
.ufdbt-hint{font-size:12px;color:#64748b;margin:4px 0 0}
.ufdbt-sel{width:100%;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:13px;color:#1e293b;box-shadow:0 1px 2px rgba(15,23,42,.04);outline:0}
.ufdbt-sel:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.2)}
.ufdbt-right{display:flex;justify-content:flex-end;margin-top:16px}
.ufdbt-bt{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:8px;font-size:13px;font-weight:600;padding:9px 18px;color:#fff!important;text-decoration:none!important;cursor:pointer;box-shadow:0 1px 3px rgba(15,23,42,.1);transition:background .15s}
.ufdbt-bt-ok{background:#059669}
.ufdbt-bt-ok:hover{background:#047857}
.ufdbt-bt-add{background:#0d9488;padding:10px 16px;font-weight:500}
.ufdbt-bt-add:hover{background:#0f766e}
.ufdbt-bt-add:active{background:#115e59}
.ufdbt-tools{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
.ufdbt-find{display:flex;align-items:center;max-width:360px;flex:1 1 240px;min-width:0}
.ufdbt-find input{width:100%;min-width:0;background:#fff;border:1px solid #cbd5e1;border-right:0;border-radius:8px 0 0 8px;padding:8px 12px;font-size:13px;color:#334155;outline:0}
.ufdbt-find input::placeholder{color:#94a3b8}
.ufdbt-find input:focus{border-color:#10b981}
.ufdbt-find button{border:1px solid #0d9488;background:#0d9488;color:#fff;border-radius:0 8px 8px 0;padding:8px 13px;cursor:pointer}
.ufdbt-find button:hover{background:#0f766e;border-color:#0f766e}
.ufdbt-new{padding:16px;background:#f8fafc;border:1px dashed rgba(110,231,183,.8);border-radius:12px}
.ufdbt-new-h{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:12px;font-weight:600;color:#065f46;margin-bottom:12px}
.ufdbt-dot{display:inline-block;width:8px;height:8px;border-radius:9999px;background:#10b981;margin-right:6px;vertical-align:middle}
.ufdbt-new-h span.ufdbt-mut{color:#94a3b8;font-weight:400}
.ufdbt-new-g{display:flex;flex-wrap:wrap;align-items:flex-end;gap:16px}
.ufdbt-f-days{flex:1 1 300px;min-width:0}
.ufdbt-f-time{flex:0 0 auto;display:flex;align-items:flex-end;gap:8px}
.ufdbt-f-go{flex:0 0 auto;margin-left:auto}
.ufdbt-lab2{display:block;font-size:12px;font-weight:500;color:#475569;margin:0 0 6px}
.ufdbt-days{display:flex;flex-wrap:wrap;gap:4px}
.ufdbt-day{padding:5px 10px;font-size:12px;font-weight:500;border-radius:4px;background:#fff;color:#475569;border:1px solid #cbd5e1;cursor:pointer;line-height:16px}
.ufdbt-day:hover{background:#f1f5f9}
.ufdbt-day.on{background:#059669;color:#fff;border-color:#059669;font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.ufdbt-time{width:110px;font-size:12px;font-weight:500;padding:6px 8px;background:#fff;border:1px solid #cbd5e1;border-radius:4px;outline:0}
.ufdbt-time:focus{border-color:#10b981}
.ufdbt-at{color:#94a3b8;padding-bottom:7px}
.ufdbt-bt-sm{padding:7px 14px;font-size:12px;font-weight:600;border-radius:4px}
.ufdbt-wrap{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.ufdbt-scroll{overflow-x:auto}
.ufdbt-tbl{width:100%;text-align:left;border-collapse:collapse}
.ufdbt-tbl thead tr{background:rgba(248,250,252,.8);border-bottom:1px solid #e2e8f0}
.ufdbt-tbl th{padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;white-space:nowrap}
.ufdbt-tbl td{padding:14px 16px;vertical-align:middle;border-top:1px solid #f1f5f9}
.ufdbt-tbl tbody tr:first-child td{border-top:0}
.ufdbt-tbl tbody tr:hover{background:rgba(248,250,252,.7)}
.ufdbt-id{display:inline-flex;align-items:center;gap:5px;color:#94a3b8;font-family:'JetBrains Mono',ui-monospace,Menlo,Consolas,monospace;font-size:12px}
.ufdbt-id b{color:#475569}
.ufdbt-desc{display:flex;align-items:center;gap:8px}
.ufdbt-desc a{font-weight:500;color:#1e293b!important;text-decoration:underline;text-underline-offset:4px;text-decoration-color:#cbd5e1;cursor:pointer}
.ufdbt-desc a:hover{color:#047857!important;text-decoration-color:#10b981}
.ufdbt-chips{display:flex;flex-wrap:wrap;gap:4px}
.ufdbt-chip{padding:2px 7px;border-radius:4px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:600}
.ufdbt-range{font-family:'JetBrains Mono',ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#475569;white-space:nowrap}
.ufdbt-acts{display:flex;justify-content:flex-end;gap:4px}
.ufdbt-act{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1px solid transparent;color:#94a3b8;text-decoration:none!important;cursor:pointer}
.ufdbt-act:hover{background:#f1f5f9;color:#475569}
.ufdbt-act-del:hover{background:#fff1f2;color:#be123c;border-color:#fecdd3}
.ufdbt-empty{padding:34px 20px;text-align:center;color:#64748b}
.ufdbt-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;border-top:1px solid #e2e8f0;padding:14px 20px;font-size:13px;color:#475569}
.ufdbt-live{display:inline-flex;align-items:center;gap:7px;color:#047857;font-size:12px;font-weight:500}
.ufdbt-off{color:#94a3b8}
.ufdbt-off .ufdbt-dot{background:#cbd5e1}
@media(max-width:920px){
.ufdbt-grid{grid-template-columns:1fr}
.ufdbt-hide-sm{display:none}
.ufdbt-f-go{margin-left:0;width:100%}
.ufdbt-f-go .ufdbt-bt{width:100%;justify-content:center}}
</style>";
}

/** ufdbtime_banner — l'encart d'instructions, en tete de l'onglet. */
function ufdbtime_banner(){
    return "<section class='ufdbt-note'>"
        ."<div class='ufdbt-note-i'><i class='fas fa-circle-info'></i></div>"
        ."<div><p class='ufdbt-note-t'>{time_rule_configuration}</p>"
        ."<p class='ufdbt-note-b'>{ufdbguardTimeSpaceExplain}</p></div></section>";
}

/**
 * ufdbtime_behavior_card — quand la regle s'applique, et par quoi on la remplace sinon.
 *
 * Ecrit a la main plutot que par form_outside(): la maquette place ici deux listes
 * legendees et un bouton vert aligne a droite, ce que form_outside ne rend pas. Le
 * CONTRAT de sauvegarde reste celui de la console -- une reponse vide vaut succes,
 * tout le reste est l'erreur -- et le controle de privilege est fait cote SERVEUR
 * dans rule_time_main_save(), pas par la simple absence du bouton.
 */
function ufdbtime_behavior_card($TimeSpace,$RULESS,$ID){
    $page=CurrentPageName();
    $ID=intval($ID);
    $RuleBH=array("inside"=>"{inside_time}","outside"=>"{outside_time}","none"=>"{disabled}");

    $h ="<section class='ufdbt-card'>";
    $h.="<h2 class='ufdbt-h'><i class='fas fa-gear'></i> {scheduler_behavior}</h2>";
    $h.="<div class='ufdbt-grid'>";
    $h.="<div><label class='ufdbt-lab' for='ufdbt-match'>{activation_condition}</label>"
       .ufdbtime_select("ufdbt-match",$RuleBH,$TimeSpace["RuleMatchTime"])
       ."<p class='ufdbt-hint'>{activation_condition_explain}</p></div>";
    $h.="<div><label class='ufdbt-lab' for='ufdbt-alt'>{alternate_rule}</label>"
       .ufdbtime_select("ufdbt-alt",$RULESS,$TimeSpace["RuleAlternate"])
       ."<p class='ufdbt-hint'>{alternate_rule_explain}</p></div>";
    $h.="</div>";
    $h.="<div class='ufdbt-right'><a class='ufdbt-bt ufdbt-bt-ok' href=\"javascript:blur();\" "
       ."OnClick=\"UfdbTimeBehavior()\"><i class='fas fa-check'></i> {apply_changes}</a></div>";
    $h.="</section>";
    return $h;
}

/**
 * ufdbtime_select — une liste deroulante du planificateur.
 *
 * La comparaison est faite en CHAINE: les identifiants de regle sont numeriques et
 * la cle "none" ne l'est pas. En comparaison lache, PHP 7 rend « "none" == 0 » vrai,
 * et la regle 0 (« defaut ») ressortait selectionnee a la place de « aucune ».
 */
function ufdbtime_select($id,$options,$selected){
    $h="<select class='ufdbt-sel' id='".htmlspecialchars($id)."'>";
    foreach($options as $v=>$label){
        $sel=((string)$v===(string)$selected)?" selected":"";
        $h.="<option value='".htmlspecialchars((string)$v)."'$sel>".$label."</option>";
    }
    return $h."</select>";
}

/** ufdbtime_toolbar — le bouton d'ajout et le filtre du tableau. */
function ufdbtime_toolbar($canWrite){
    $h ="<div class='ufdbt-tools'>";
    if($canWrite){
        $h.="<a class='ufdbt-bt ufdbt-bt-add' href=\"javascript:blur();\" OnClick=\"UfdbTimeToggleNew()\">"
           ."<i class='fas fa-plus'></i> {add_a_period}</a>";
    }else{ $h.="<span></span>"; }
    $h.="<div class='ufdbt-find'>"
       ."<input type='text' id='ufdbt-q' placeholder=\"{search_a_period}\" oninput=\"UfdbTimeFilter()\">"
       ."<button type='button' OnClick=\"UfdbTimeFilter()\"><i class='fas fa-magnifying-glass'></i></button>"
       ."</div></div>";
    return $h;
}

/**
 * ufdbtime_composer — la creation rapide d'une plage recurrente.
 *
 * Replie par defaut, deplie d'office quand aucune plage n'existe: toujours ouvert, il
 * occuperait a chaque visite la bande que l'operateur franchit pour atteindre son
 * tableau -- et c'est justement sur une regle vide qu'on vient le chercher.
 * La modale d'edition reste la voie pour MODIFIER une plage existante.
 */
function ufdbtime_composer($ID,$open){
    $ID=intval($ID);
    $days=ufdbtime_days();
    $tpl=new template_admin();
    $h ="<div class='ufdbt-new' id='ufdbt-new'".($open?"":" style='display:none'").">";
    $h.="<div class='ufdbt-new-h'><span><span class='ufdbt-dot'></span>{new_recurring_period}</span>"
       ."<span class='ufdbt-mut'>{select_days_and_range}</span></div>";
    $h.="<div class='ufdbt-new-g'>";
    $h.="<div class='ufdbt-f-days'><label class='ufdbt-lab2'>{days_of_application}</label><div class='ufdbt-days'>";
    foreach($days as $key=>$full){
        $short=ufdbtime_day_short($tpl->_ENGINE_parse_body("{".$full."}"));
        $h.="<button type='button' class='ufdbt-day' data-d='".htmlspecialchars($key)."' "
           ."title=\"{".$full."}\" OnClick=\"UfdbTimeDay(this)\">".htmlspecialchars($short)."</button>";
    }
    $h.="</div></div>";
    $h.="<div class='ufdbt-f-time'>";
    $h.="<div><label class='ufdbt-lab2' for='ufdbt-b'>{hourBegin}</label>"
       ."<input class='ufdbt-time' type='time' id='ufdbt-b' value='08:00'></div>";
    $h.="<span class='ufdbt-at'>{to}</span>";
    $h.="<div><label class='ufdbt-lab2' for='ufdbt-e'>{hourEnd}</label>"
       ."<input class='ufdbt-time' type='time' id='ufdbt-e' value='18:00'></div>";
    $h.="</div>";
    $h.="<div class='ufdbt-f-go'><a class='ufdbt-bt ufdbt-bt-ok ufdbt-bt-sm' href=\"javascript:blur();\" "
       ."OnClick=\"UfdbTimeAdd()\">{save}</a></div>";
    $h.="</div></div>";
    return $h;
}

/**
 * ufdbtime_row — une plage horaire.
 *
 * $active dit si le planificateur est en service: une pastille verte sur chaque ligne
 * d'une regle dont la condition d'activation vaut « desactive » affirmerait que ces
 * plages agissent, alors qu'aucune n'est lue.
 */
function ufdbtime_row($TIMEID,$array,$ID,$active,$canWrite){
    $page=CurrentPageName();
    $ID=intval($ID); $TIMEID=intval($TIMEID);
    $tpl=new template_admin();
    $days=ufdbtime_days();

    $chips=array(); $names=array();
    foreach($days as $key=>$full){
        if(!isset($array["DAYS"][$key]) || intval($array["DAYS"][$key])!=1){continue;}
        $lbl=$tpl->_ENGINE_parse_body("{".$full."}");
        $names[]=$lbl;
        $chips[]="<span class='ufdbt-chip'>".htmlspecialchars(ufdbtime_day_short($lbl))."</span>";
    }
    $b=ufdbtime_hhmm($array,"BEGIN");
    $e=ufdbtime_hhmm($array,"END");
    // Phrase reconstruite a CHAQUE ligne. L'ancienne version n'affectait $daysText que
    // dans un if(is_array(DAYS)): une plage sans jour repris heritait en silence du
    // libelle de la ligne PRECEDENTE, et affichait donc les jours d'une autre plage.
    $sentence=count($names)
        ? $tpl->_ENGINE_parse_body("{each} ".implode(", ",$names)." {from} $b {to} $e")
        : $tpl->_ENGINE_parse_body("{no_day_selected}");
    // Une plage dont aucun jour n'est retenu ne se declenchera jamais, quel que soit
    // l'etat du planificateur: sa pastille doit le dire, sinon la ligne se lit comme
    // une regle en vigueur alors qu'elle est inerte.
    $live=($active && count($names)>0);
    $jsEdit="Loadjs('$page?period-add-js=yes&ID=$ID&TIMEID=$TIMEID')";
    $jsDel ="UfdbTimeDel($TIMEID)";

    // Cle de filtre en TEXTE BRUT: l'utilisateur tape « samedi », pas « s&aacute;bado ».
    // On decode donc les entites avant de comparer, puis on echappe pour l'attribut.
    $key=ufdbtime_lower(html_entity_decode($sentence." ".implode(" ",$names),ENT_QUOTES,"UTF-8"));
    $h ="<tr data-f=\"".htmlspecialchars($key,ENT_QUOTES)."\">";
    $h.="<td style='width:1%;text-align:center'><span class='ufdbt-id'>"
       ."<i class='fas fa-clock'></i><b>$TIMEID</b></span></td>";
    $h.="<td><div class='ufdbt-desc'><span class='ufdbt-dot'".($live?"":" style='background:#cbd5e1'")."></span>"
       ."<a href=\"javascript:blur();\" OnClick=\"$jsEdit\">".$sentence."</a></div></td>";
    $h.="<td class='ufdbt-hide-sm'><div class='ufdbt-chips'>".implode("",$chips)."</div></td>";
    $h.="<td class='ufdbt-hide-sm'><span class='ufdbt-range'>$b:00 - $e:00</span></td>";
    $h.="<td style='text-align:right'><div class='ufdbt-acts'>";
    $h.="<a class='ufdbt-act' title=\"{edit}\" href=\"javascript:blur();\" OnClick=\"$jsEdit\"><i class='fas fa-pen'></i></a>";
    if($canWrite){
        $h.="<a class='ufdbt-act ufdbt-act-del' title=\"{delete}\" href=\"javascript:blur();\" OnClick=\"$jsDel\"><i class='fas fa-trash'></i></a>";
    }
    $h.="</div></td></tr>";
    return $h;
}

/** ufdbtime_hhmm — « 8 » et « 5 » donnent « 08:05 ». */
function ufdbtime_hhmm($array,$prefix){
    $H=isset($array[$prefix."H"])?intval($array[$prefix."H"]):0;
    $M=isset($array[$prefix."M"])?intval($array[$prefix."M"]):0;
    if($H<0||$H>23){$H=0;}
    if($M<0||$M>59){$M=0;}
    return sprintf("%02d:%02d",$H,$M);
}

function page(){
	$page=CurrentPageName();
	// Pentest 2026-11-04 : ID etait recopie sans encodage dans la page (XSS).
    echo "<div style='margin-top:10px'><div id='ufdb-time-list' style='margin-top:20px'></div></div>
	<script>
		function RefreshUfdbTimeList(){
			LoadAjax('ufdb-time-list','$page?list=yes&ID=".intval($_GET["ID"] ?? 0)."');
			
		}
		RefreshUfdbTimeList();
	</script>	
	";
	
	
}

function period_add(){
	$ID=isset($_GET["ID"])?intval($_GET["ID"]):0;
	$TIMEID=isset($_GET["TIMEID"])?intval($_GET["TIMEID"]):-1;
	$tpl=new template_admin();
	$page=CurrentPageName();

	$load=ufdbtime_load($ID);
	$Config=isset($load["TimeSpace"]["TIMES"][$TIMEID])?$load["TimeSpace"]["TIMES"][$TIMEID]:array();

	$buttonname="{apply}";
	$close=null;
	if($TIMEID<0){
		$close="dialogInstance2.close();";
		$buttonname="{add}";
	}

	$form=array();
	foreach(ufdbtime_days() as $key=>$val){
		$checked=isset($Config["DAYS"][$key])?intval($Config["DAYS"][$key]):0;
		$form[]=$tpl->field_checkbox("day_{$key}","{{$val}}",$checked);
	}
	$form[]=$tpl->field_hidden("ID",$ID);
	$form[]=$tpl->field_hidden("TIMEID",$TIMEID);
	// HH:MM zero-padde, obligatoirement. field_clock rend un <input type='time'>, dont
	// la valeur DOIT respecter ce format: l'ancien « intval(8).":".intval(5) » produisait
	// « 8:5 », que le navigateur refuse -- le champ s'affichait vide a chaque ouverture
	// d'une plage existante, et l'enregistrement effacait donc l'horaire qu'on venait
	// seulement de vouloir consulter.
	$form[]=$tpl->field_clock("BEGINH","{hourBegin}",ufdbtime_hhmm($Config,"BEGIN"));
	$form[]=$tpl->field_clock("ENDH","{hourEnd}",ufdbtime_hhmm($Config,"END"));

	echo $tpl->form_outside("{set_the_period}",@implode("\n",$form),null,$buttonname,
		"UfdbTimeReload();$close","AsDansGuardianAdministrator");
	return;
}
/**
 * ufdbtime_js — le comportement du planificateur.
 *
 * A CONCATENER APRES _ENGINE_parse_body(): les accolades des blocs JavaScript seraient
 * lues comme des {tokens}. Les libelles qui doivent etre traduits sont donc resolus
 * cote PHP et injectes deja encodes en JSON.
 */
function ufdbtime_js($ID){
    $page=CurrentPageName();
    $ID=intval($ID);
    $tpl=new template_admin();
    $t=function($k)use($tpl){ return json_encode($tpl->javascript_parse_text($k)); };

    $js ="<script>\n";
    $js.="if(typeof NoSpinner==='function'){NoSpinner();}\n";
    $js.="function UfdbTimeReload(){\n";
    $js.="  if(typeof RefreshUfdbTimeList==='function'){RefreshUfdbTimeList();}\n";
    $js.="  else{LoadAjax('ufdb-time-list','$page?list=yes&ID=$ID');}\n";
    $js.="  if(document.getElementById('table-loader-ufdbrules-service')){\n";
    $js.="    LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');}\n";
    $js.="}\n";
    // Contrat de sauvegarde de la console: <=3 caracteres = succes, tout le reste est
    // le message d'erreur. Le meme partout, pour que les trois ecritures de la page se
    // comportent comme le reste de l'interface.
    $js.="function UfdbTimeSaved(r){\n";
    $js.="  if(r && r.length>3){ swal({title:".$t("{error}").",text:r,type:'error',".
         "confirmButtonColor:'#ed5565',confirmButtonText:".$t("{ok}")."}); return false; }\n";
    $js.="  UfdbTimeReload(); return true;\n";
    $js.="}\n";
    $js.="function UfdbTimeBehavior(){\n";
    $js.="  var m=document.getElementById('ufdbt-match'),a=document.getElementById('ufdbt-alt');\n";
    $js.="  if(!m||!a){return;}\n";
    $js.="  $.post('$page',{'ID':'$ID','RuleMatchTime':m.value,'RuleAlternate':a.value},UfdbTimeSaved);\n";
    $js.="}\n";
    $js.="function UfdbTimeToggleNew(){\n";
    $js.="  var d=document.getElementById('ufdbt-new'); if(!d){return;}\n";
    $js.="  var show=(d.style.display==='none');\n";
    $js.="  d.style.display=show?'':'none';\n";
    $js.="  if(show){var b=document.getElementById('ufdbt-b'); if(b){b.focus();}}\n";
    $js.="}\n";
    $js.="function UfdbTimeDay(el){ $(el).toggleClass('on'); }\n";
    $js.="function UfdbTimeAdd(){\n";
    $js.="  var d=[]; $('#ufdbt-new .ufdbt-day.on').each(function(){d.push($(this).attr('data-d'));});\n";
    $js.="  if(d.length===0){ swal({title:".$t("{select_at_least_one_day}").",type:'warning',".
         "confirmButtonColor:'#1c84c6',confirmButtonText:".$t("{ok}")."}); return; }\n";
    $js.="  var b=document.getElementById('ufdbt-b').value,e=document.getElementById('ufdbt-e').value;\n";
    $js.="  if(!b||!e){return;}\n";
    // La comparaison en chaine suffit sur « HH:MM » zero-padde, et c'est le format que
    // <input type=time> impose. Le serveur revalide: cette garde est un confort, pas
    // une securite -- elle evite l'aller-retour, pas la mauvaise donnee.
    $js.="  if(e<=b){ swal({title:".$t("{end_before_begin}").",type:'warning',".
         "confirmButtonColor:'#1c84c6',confirmButtonText:".$t("{ok}")."}); return; }\n";
    $js.="  $.post('$page',{'ID':'$ID','quick-days':d.join(''),'quick-begin':b,'quick-end':e},function(r){\n";
    $js.="    if(UfdbTimeSaved(r)){ $('#ufdbt-new .ufdbt-day').removeClass('on'); }\n";
    $js.="  });\n";
    $js.="}\n";
    $js.="function UfdbTimeDel(tid){\n";
    $js.="  swal({title:".$t("{are_you_sure}").",type:'warning',showCancelButton:true,\n";
    $js.="    confirmButtonColor:'#ed5565',confirmButtonText:".$t("{yes}").",cancelButtonText:".$t("{cancel}")."},\n";
    $js.="    function(ok){ if(!ok){return;} Loadjs('$page?period-del-js=yes&ID=$ID&TIMEID='+tid); });\n";
    $js.="}\n";
    // Filtre cote navigateur: toutes les lignes sont deja dans le DOM (une regle porte
    // quelques plages, jamais des milliers), donc filtrer = montrer ou cacher.
    $js.="function UfdbTimeFilter(){\n";
    $js.="  var q=document.getElementById('ufdbt-q'); if(!q){return;}\n";
    $js.="  var s=q.value.toLowerCase().replace(/^\\s+|\\s+$/g,''),n=0;\n";
    $js.="  $('#ufdbt-rows tr[data-f]').each(function(){\n";
    $js.="    var ok=(s===''||$(this).attr('data-f').indexOf(s)>=0);\n";
    $js.="    this.style.display=ok?'':'none'; if(ok){n++;}\n";
    $js.="  });\n";
    $js.="  $('#ufdbt-count').text(n);\n";
    $js.="  $('#ufdbt-none').css('display',n===0?'':'none');\n";
    $js.="}\n";
    $js.="</script>";
    return $js;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=isset($_GET["ID"])?intval($_GET["ID"]):0;

    $load=ufdbtime_load($ID);
    $TimeSpace=$load["TimeSpace"];

    // Les regles candidates au repli. Une regle ne peut pas se replier sur elle-meme.
    $RULESS=array("none"=>"{none}","0"=>"{default}");
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT ID,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY groupname");
    if(!$q->ok){
        // Le message SQLite nomme tables et colonnes: journal, pas <H2> a l'ecran.
        writelogs("webfilter_rules: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        $results=array();
    }
    if(!is_array($results)){ $results=array(); }
    foreach($results as $ligne){
        if(intval($ligne["ID"])==$ID){continue;}
        $RULESS[strval($ligne["ID"])]=htmlspecialchars((string)$ligne["groupname"]);
    }

    // Une console esclave de cluster ne fabrique pas sa propre configuration.
    $slave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $users=new usersMenus();
    $canWrite=($users->AsDansGuardianAdministrator && $slave!=1);
    // Regle Artica Meta (ou regle par defaut tenue) : plages affichees, non modifiables.
    $metaLocked=ufdbrules_meta_locked_rule($ID);
    if($metaLocked){ $canWrite=false; }

    $times=$TimeSpace["TIMES"];
    $active=($TimeSpace["RuleMatchTime"]!="none");
    $n=count($times);

    $html=array();
    $html[]="<div class='ufdbt'><div class='ufdbt-stack'>";
    if($metaLocked){ $html[]=ufdbrules_meta_banner(new template_admin(),($ID==0)?"default":$ID); }
    $html[]=ufdbtime_banner();
    $html[]=ufdbtime_behavior_card($TimeSpace,$RULESS,$ID);
    $html[]=ufdbtime_toolbar($canWrite);
    if($canWrite){ $html[]=ufdbtime_composer($ID,$n==0); }

    $html[]="<div class='ufdbt-wrap'><div class='ufdbt-scroll'>";
    $html[]="<table class='ufdbt-tbl'><thead><tr>";
    $html[]="<th style='width:70px;text-align:center'>ID</th>";
    $html[]="<th>{time_rule_configuration}</th>";
    $html[]="<th class='ufdbt-hide-sm' style='width:150px'>{days_concerned}</th>";
    $html[]="<th class='ufdbt-hide-sm' style='width:150px'>{time_range}</th>";
    $html[]="<th style='width:100px;text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ufdbt-rows'>";
    foreach($times as $TIMEID=>$array){
        if(!is_array($array)){continue;}
        $html[]=ufdbtime_row($TIMEID,$array,$ID,$active,$canWrite);
    }
    // La ligne « aucune plage » vit DANS le tableau: rendue a sa place, l'en-tete des
    // colonnes disparaitrait et le bloc changerait de forme selon qu'il est vide ou non.
    $html[]="<tr id='ufdbt-none'".($n>0?" style='display:none'":"")."><td colspan='5'>"
        ."<div class='ufdbt-empty'>{no_period_defined}</div></td></tr>";
    $html[]="</tbody></table></div>";
    $html[]="<div class='ufdbt-foot'>";
    $html[]="<span>{showing} <b id='ufdbt-count'>$n</b> / $n {periods_configured}</span>";
    $html[]=$active
        ? "<span class='ufdbt-live'><span class='ufdbt-dot'></span>{scheduler_active}</span>"
        : "<span class='ufdbt-live ufdbt-off'><span class='ufdbt-dot'></span>{scheduler_disabled}</span>";
    $html[]="</div></div>";
    $html[]="</div></div>";

    echo ufdbtime_css()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .ufdbtime_js($ID);
}

/**
 * period_quick_add — la creation depuis le composeur en ligne.
 *
 * N'ecrit RIEN en cas de succes: form_outside comme le $.post de cette page traitent
 * toute reponse non vide comme une erreur.
 */
function period_quick_add(){
    if(!ufdbtime_require_admin()){ echo "{access_denied}"; return; }
    $ID=isset($_POST["ID"])?intval($_POST["ID"]):0;

    // Liste FERMEE de lettres. Les cles de jour deviennent des index de tableau qui
    // seront serialises puis relus: on n'accepte que les sept que le format connait.
    $valid=array_keys(ufdbtime_days());
    $days=array();
    $raw=isset($_POST["quick-days"])?(string)$_POST["quick-days"]:"";
    for($i=0;$i<strlen($raw);$i++){
        $c=$raw[$i];
        if(in_array($c,$valid,true)){ $days[$c]=1; }
    }
    if(count($days)==0){ echo "{select_at_least_one_day}"; return; }

    $b=ufdbtime_parse_time(isset($_POST["quick-begin"])?$_POST["quick-begin"]:"");
    $e=ufdbtime_parse_time(isset($_POST["quick-end"])?$_POST["quick-end"]:"");
    if($b===false || $e===false){ echo "{error}"; return; }
    // Revalidation SERVEUR: la garde du navigateur evite un aller-retour, elle ne
    // protege rien -- ce POST est atteignable sans passer par la page.
    if(($e[0]*60+$e[1]) <= ($b[0]*60+$b[1])){ echo "{end_before_begin}"; return; }

    $load=ufdbtime_load($ID);
    $TimeSpace=$load["TimeSpace"];
    $TimeSpace["TIMES"][]=array("BEGINH"=>$b[0],"BEGINM"=>$b[1],"ENDH"=>$e[0],"ENDM"=>$e[1],"DAYS"=>$days);

    $err=ufdbtime_store($ID,$load["ligne"],$TimeSpace);
    if($err!=""){ echo $err; return; }
    admin_tracks("Web filter rule $ID: added a time range");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/compile");
}

/** ufdbtime_parse_time — « HH:MM » vers array(H,M), ou false. */
function ufdbtime_parse_time($s){
    if(!preg_match("#^([0-9]{1,2}):([0-9]{1,2})$#",trim((string)$s),$re)){ return false; }
    $H=intval($re[1]); $M=intval($re[2]);
    if($H<0||$H>23||$M<0||$M>59){ return false; }
    return array($H,$M);
}


function rule_time_main_save(){
	if(!ufdbtime_require_admin()){ echo "{access_denied}"; return; }
	$ID=isset($_POST["ID"])?intval($_POST["ID"]):0;

	// Listes FERMEES. Ces deux valeurs sont relues par le compilateur de regles ufdbGuard;
	// une chaine inattendue s'y serialise sans bruit et ne se voit qu'a la compilation.
	$match=isset($_POST["RuleMatchTime"])?(string)$_POST["RuleMatchTime"]:"none";
	if(!in_array($match,array("inside","outside","none"),true)){ $match="none"; }
	$alt=isset($_POST["RuleAlternate"])?(string)$_POST["RuleAlternate"]:"none";
	if($alt!=="none" && !preg_match("#^[0-9]+$#",$alt)){ $alt="none"; }

	$load=ufdbtime_load($ID);
	$TimeSpace=$load["TimeSpace"];
	$TimeSpace["RuleMatchTime"]=$match;
	$TimeSpace["RuleAlternate"]=$alt;

	$err=ufdbtime_store($ID,$load["ligne"],$TimeSpace);
	if($err!=""){ echo $err; return; }
	admin_tracks("Web filter rule $ID: scheduler behavior set to $match (fallback $alt)");
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/compile");
}
function period_save(){
	if(!ufdbtime_require_admin()){ echo "{access_denied}"; return; }
	$ID=isset($_POST["ID"])?intval($_POST["ID"]):0;
	$TIMEID=isset($_POST["TIMEID"])?intval($_POST["TIMEID"]):-1;

	$b=ufdbtime_parse_time(isset($_POST["BEGINH"])?$_POST["BEGINH"]:"");
	$e=ufdbtime_parse_time(isset($_POST["ENDH"])?$_POST["ENDH"]:"");
	if($b===false || $e===false){ echo "{error}"; return; }
	if(($e[0]*60+$e[1]) <= ($b[0]*60+$b[1])){ echo "{end_before_begin}"; return; }

	// Les jours arrivent en day_<lettre>. La lettre devient un index serialise: on
	// n'accepte que les sept que le format connait, jamais ce que le POST propose.
	$valid=array_keys(ufdbtime_days());
	$days=array();
	foreach($_POST as $index=>$value){
		if(!preg_match("#^day_([a-z])$#",$index,$re)){ continue; }
		if(!in_array($re[1],$valid,true)){ continue; }
		$days[$re[1]]=(intval($value)==1)?1:0;
	}
	if(count(array_filter($days))==0){ echo "{select_at_least_one_day}"; return; }

	$Config=array("BEGINH"=>$b[0],"BEGINM"=>$b[1],"ENDH"=>$e[0],"ENDM"=>$e[1],"DAYS"=>$days);
	$load=ufdbtime_load($ID);
	$TimeSpace=$load["TimeSpace"];
	if($TIMEID<0 || !isset($TimeSpace["TIMES"][$TIMEID])){
		$TimeSpace["TIMES"][]=$Config;
	}else{
		$TimeSpace["TIMES"][$TIMEID]=$Config;
	}

	$err=ufdbtime_store($ID,$load["ligne"],$TimeSpace);
	if($err!=""){ echo $err; return; }
	admin_tracks("Web filter rule $ID: saved time range $TIMEID");
	// L'ancienne version ne recompilait PAS apres l'enregistrement d'une plage (seul le
	// comportement le faisait): la plage etait bien rangee et n'entrait en vigueur qu'a
	// la compilation suivante, declenchee par autre chose.
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/compile");
}
