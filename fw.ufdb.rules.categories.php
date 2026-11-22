<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.categories.mem.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.categories.inc');
include_once("ressources/class.ldap-extern.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbrules.meta.inc");

if(isset($_GET["js-ID"])){js_id();exit;}
// table_start() n'existe pas: cette route etait un Fatal "Call to undefined
// function", et ?start-list= n'etait appele de nulle part. Aiguillage retire.
if(isset($_GET["list"])){table();exit;}
if(isset($_GET["category-add-js"])){categoy_add_js();exit;}
if(isset($_GET["category-add"])){categoy_add();exit;}
if(isset($_GET["category-add-body"])){categoy_add_body();exit;}
if(isset($_GET["category-post-js"])){categoy_post();exit;}
if(isset($_GET["category-del-js"])){category_del();exit;}
if(isset($_GET["category-add-dangerous-js"])){category_dangerous_js();exit;}
if(isset($_POST["category-add-dangerous"])){category_dangerous_perform();exit;}
if(isset($_GET["category-add-polluate-js"])){category_polluate_js();exit;}
if(isset($_POST["category-add-polluate"])){category_polluate_perform();exit;}
if(isset($_GET["category-add-nonproductive-js"])){category_nonproductive_js();exit;}
if(isset($_POST["category-add-nonproductive"])){category_nonproductive_perform();exit;}
if(isset($_GET["category-clean"])){category_clean_js();exit;}
if(isset($_POST["category-clean"])){category_clean_perform();exit;}
if(isset($_GET["category-add-all-js"])){category_all_js();exit;}
if(isset($_POST["category-add-all"])){category_all_perform();exit;}


//category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md

page();

function categoy_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $add=array();

    // Pentest 2026-11-04 : ID, modeblk et groupid etaient recopies sans encodage dans la page (XSS).
    if(isset($_GET["ID"])){$add[]="ID=".intval($_GET["ID"]);}
    if(isset($_GET["modeblk"])){$add[]="modeblk=".intval($_GET["modeblk"]);}
    if(isset($_GET["groupid"])){$add[]="groupid=".intval($_GET["groupid"]);}
    $options=@implode("&",$add);
    $tpl->js_dialog5("{categories}", "$page?category-add=yes&$options");
    return true;
}

function js_id(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["js-ID"]);
    // modeblk indexe un tableau de deux entrees: une valeur libre y produisait un
    // index indefini, donc un titre de modale ampute de son suffixe.
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $rulename="{default}";

    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_rules WHERE ID=?",array($ID));
        $rulename=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID";
        // buildADialog() pose le titre dans « title: '$title' » SANS echappement, et
        // javascript_parse_text() ne neutralise les apostrophes que des jetons resolus,
        // pas d'un nom relu en base. htmlspecialchars(ENT_QUOTES) traite le balisage ET
        // l'apostrophe; l'antislash, qu'il laisse passer, ouvrirait une sequence dans
        // ce meme litteral et la modale ne s'ouvrirait jamais, sans un mot a l'ecran.
        $rulename=str_replace("\\","",htmlspecialchars($rulename,ENT_QUOTES));
    }

    $blks=($modeblk==1)?"{whitelists}":"{blacklists}";
    $tpl->js_dialog2("$rulename: {categories} $blks","$page?ID=$ID&modeblk=$modeblk",755);
}

/** ufdbc_require_admin — le garde SERVEUR des trois ecritures de cette page. */
function ufdbc_require_admin(){
    $users=new usersMenus();
    return $users->AsDansGuardianAdministrator?true:false;
}

/** ufdbc_reload — le rechargement renvoye a Loadjs apres une mutation. */
function ufdbc_reload($ID,$modeblk){
    $page=CurrentPageName();
    $ID=intval($ID); $modeblk=intval($modeblk);
    // Rechargement du fragment plutot que « $('#<md5 venu de l URL>').remove() »: cet
    // identifiant arrivait brut du navigateur dans du JavaScript emis, et retirer la
    // ligne a la main laissait le compteur du pied sur l'ancien total.
    return "LoadAjax('ufdb-categories-list-$ID-$modeblk','$page?list=yes&ID=$ID&modeblk=$modeblk');\n"
        ."if(document.getElementById('table-loader-ufdbrules-service')){"
        ."LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');}\n";
}

function category_del():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!ufdbc_require_admin()){
        echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return false;
    }
    $category=(string)$_GET["category-del-js"];
    $ID=intval($_GET["ID"]);
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    if(ufdbrules_meta_locked_rule($ID)){
        echo "alert(".json_encode(ufdbrules_meta_text()).");"; return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

    // Deux formes possibles, toutes deux NUMERIQUES: « GpCatz<n> » designe un lien de
    // groupe, un nombre nu une categorie. Elles partaient concatenees dans le SQL.
    if(preg_match("#^GpCatz([0-9]+)$#",$category,$re)){
        $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_blklnk WHERE ID=?",array(intval($re[1])));
        if(!$q->ok){
            writelogs("webfilter_blklnk: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return false;
        }
        admin_tracks("Removing categories group link #{$re[1]} from Web-filter rule #$ID");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
        echo ufdbc_reload($ID,$modeblk);
        return true;
    }

    $category_id=intval($category);
    $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_blks WHERE category=? AND modeblk=? AND webfilter_id=?",
        array($category_id,$modeblk,$ID));
    if(!$q->ok){
        writelogs("webfilter_blks: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    echo ufdbc_reload($ID,$modeblk);
    return admin_tracks("Removing Web-filter rule category black=$modeblk $category_id from rule #$ID");
}

function categoy_post(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!ufdbc_require_admin()){
        echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $category=(string)$_GET["category-post-js"];
    $ID=isset($_GET["ID"])?intval($_GET["ID"]):0;
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $add="";
    // Verrou Artica Meta : la cible est une regle (ID, ou lien de groupe de categories).
    if((isset($_GET["ID"]) || preg_match("#^GpCatz[0-9]+$#",$category)) && ufdbrules_meta_locked_rule($ID)){
        echo "alert(".json_encode(ufdbrules_meta_text()).");"; return;
    }

    // ---- rattachement d'un GROUPE de categories ----------------------------
    if(preg_match("#^GpCatz([0-9]+)$#",$category,$re)){
        $gid=intval($re[1]);
        $md5=md5("$gid$ID$modeblk");
        $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_blklnk WHERE zmd5=?",array($md5));
        $q->QUERY_SQL_PARAMS(
            "INSERT INTO webfilter_blklnk (zmd5,webfilter_blkid,webfilter_ruleid,blacklist) VALUES (?,?,?,?)",
            array($md5,$gid,$ID,$modeblk));
        if(!$q->ok){
            writelogs("webfilter_blklnk: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
        }
        admin_tracks("Adding categories group #$gid to Web-filter rule #$ID (black=$modeblk)");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
        echo ufdbc_reload($ID,$modeblk);
        return;
    }

    $category_id=intval($category);

    // ---- rattachement a une REGLE ------------------------------------------
    if(isset($_GET["ID"])){
        $rows=$q->QUERY_SQL_PARAMS(
            "SELECT ID FROM webfilter_blks WHERE category=? AND modeblk=? AND webfilter_id=?",
            array($category_id,$modeblk,$ID));
        if(isset($rows[0]["ID"]) && intval($rows[0]["ID"])>0){ return; }
        $q->QUERY_SQL_PARAMS(
            "INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES (?,?,?)",
            array($ID,$category_id,$modeblk));
        if(!$q->ok){
            writelogs("webfilter_blks: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
        }
        admin_tracks("Adding category $category_id to Web-filter rule #$ID (black=$modeblk)");
    }

    // ---- rattachement a un GROUPE de categories -----------------------------
    if(isset($_GET["groupid"])){
        $groupid=intval($_GET["groupid"]);
        $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_blkcnt (webfilter_blkid,category) VALUES(?,?)",
            array($groupid,$category_id));
        if(!$q->ok){
            writelogs("webfilter_blkcnt: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
        }
        admin_tracks("Adding category $category_id to categories group #$groupid");
        $add="LoadAjax('categories-table-$groupid-div','fw.ufdb.categories.groups.php?categories-table=$groupid');\n";
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    echo $add.ufdbc_reload($ID,$modeblk);
}

function page():bool{
    $page=CurrentPageName();
    // Ces deux valeurs entrent dans un identifiant de div, dans un NOM DE FONCTION
    // JavaScript et dans trois URL. Elles arrivaient brutes de l'URL: un « 1;alert(1)// »
    // en modeblk se serait execute a l'ouverture du dialogue.
    $ID=intval($_GET["ID"]);
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $reload="LoadAjax('ufdb-categories-list-$ID-$modeblk','$page?list=yes&ID=$ID&modeblk=$modeblk');"
        // artica-ufdbrules-table n'existe que si le dialogue a ete ouvert depuis la
        // liste des regles: sans la garde, LoadAjax visait un div absent a chaque
        // rafraichissement quand on venait de l'editeur de regle.
        ."if(document.getElementById('artica-ufdbrules-table')){"
        ."LoadAjax('artica-ufdbrules-table','fw.ufdb.rules.php?table=yes');}";
    echo "<div id='ufdb-categories-list-$ID-$modeblk' style='margin-top:20px'></div>
	<script>
		function RefreshUfdbCategoriesList(){ $reload }
		function RefreshUfdbCategoriesList$ID$modeblk(){ $reload }
		RefreshUfdbCategoriesList();
	</script>
	";
    return true;
}
function category_all_list():array{
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    $q=new postgres_sql();
    if(!$q->TABLE_EXISTS("personal_categories")){
        $categories=new categories();
        $categories->initialize();
    }

    $sql="SELECT *  FROM personal_categories WHERE free_category=0 AND meta=0 ORDER BY categoryname";
    $results=$q->QUERY_SQL($sql);
    $f=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $categoryname = $ligne["categoryname"];
        $category_id = $ligne["category_id"];

        $official_category = $ligne["official_category"];
        $free_category = $ligne["free_category"];
        if (preg_match("#^reserved#", $categoryname)) {
            continue;
        }
        $ISOFFICIAL = false;
        if ($official_category == 1) {
            $ISOFFICIAL = true;
        }
        if ($free_category == 1) {
            $ISOFFICIAL = true;
        }
        if($ISOFFICIAL){
            if(!$CORP_LICENSE){continue;}
        }
        $f[]=$category_id;
    }
   return $f;
}
function category_all_perform():bool{
    $MAIN=json_decode(base64_decode($_POST["category-add-all"]),true);
    // Une charge illisible ne doit PAS retomber sur des valeurs par defaut: ID=0 est la
    // regle par defaut, une cible parfaitement legitime -- un payload malforme aurait
    // donc agi sur elle en silence. On refuse.
    if(!is_array($MAIN) || !isset($MAIN["ID"])){ echo "{error}"; return false; }
    $ID=isset($MAIN["ID"])?intval($MAIN["ID"]):0;
    $modeblk=(isset($MAIN["modeblk"]) && intval($MAIN["modeblk"])==1)?1:0;
    // Verrou Artica Meta (regle Meta, ou regle par defaut tenue), relu cote serveur.
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_text(); }
    $category_list=category_all_list();

    if(count($category_list)==0){
        echo "No Category!!!???\n";
        return false;
    }
    writelogs("Inject category_list into $ID",__FUNCTION__,__FILE__,__LINE__);
    if(!category_inject($category_list,$ID,$modeblk)){
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    return admin_tracks("Adding All Web-Filtering categories inside rule $ID");

}

/**
 * ufdbcat_css — la feuille du selecteur de categories.
 *
 * D'apres /home/claude/images/stitch_refonte_ajout_categories.zip. Une seule fois par
 * requete, et A CONCATENER APRES _ENGINE_parse_body(): son motif #{(.+?)}# lirait
 * chaque corps de regle CSS comme un {token} et declencherait une recherche memcached
 * par regle.
 *
 * Les ICONES DE CATEGORIE restent celles d'aujourd'hui -- des images servies par
 * personal_categories.category_icon. La maquette les remplacait par des glyphes
 * colores; on garde les images et on ne reprend d'elle que la pastille qui les loge.
 */
function ufdbcat_css(){
    if(!empty($GLOBALS["UFDBCAT_CSS_DONE"])){return "";}
    $GLOBALS["UFDBCAT_CSS_DONE"]=true;
    return "<style>
.ucat{color:#0f172a;font-size:13px}
.ucat *{box-sizing:border-box}
.ucat>*+*{margin-top:14px}
.ucat-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;
 background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.ucat-find{position:relative;flex:1 1 320px;min-width:0;max-width:440px}
.ucat-find-i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none}
.ucat-find input{width:100%;padding:8px 76px 8px 32px;border:1px solid #cbd5e1;border-radius:8px;
 font-size:13px;color:#334155;background:rgba(248,250,252,.5);outline:0}
.ucat-find input::placeholder{color:#94a3b8}
.ucat-find input:focus{background:#fff;border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.2)}
.ucat-find button{position:absolute;right:5px;top:50%;transform:translateY(-50%);border:0;border-radius:6px;
 background:#059669;color:#fff;font-size:12px;font-weight:600;padding:5px 11px;cursor:pointer}
.ucat-find button:hover{background:#047857}
.ucat-show{display:flex;align-items:center;gap:8px;font-size:12px;color:#64748b}
.ucat-show select{font-size:12px;font-weight:500;color:#334155;background:#fff;border:1px solid #cbd5e1;
 border-radius:8px;padding:6px 9px;outline:0}
.ucat-show select:focus{border-color:#10b981}
.ucat-pre{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.ucat-pre-h{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin:0 0 10px}
.ucat-pre-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}
.ucat-pre-b{display:flex;align-items:flex-start;gap:10px;border:1px solid #e2e8f0;border-radius:10px;
 padding:10px 12px;background:#fff;text-decoration:none!important;cursor:pointer;transition:border-color .15s,background .15s}
.ucat-pre-b:hover{border-color:#10b981;background:rgba(236,253,245,.6)}
.ucat-pre-d{width:8px;height:8px;border-radius:9999px;flex:0 0 auto;margin-top:6px}
.ucat-pre-n{display:block;font-size:13px;font-weight:600;color:#1e293b;line-height:18px}
.ucat-pre-s{display:block;font-size:11px;color:#64748b;line-height:15px;margin-top:1px}
.ucat-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.ucat-scroll{overflow-x:auto}
.ucat-tbl{width:100%;text-align:left;border-collapse:collapse}
.ucat-tbl thead tr{background:rgba(248,250,252,.8);border-bottom:1px solid #e2e8f0}
.ucat-tbl th{padding:12px 16px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#475569}
.ucat-tbl td{padding:14px 16px;vertical-align:top;border-top:1px solid #f1f5f9}
.ucat-tbl tbody tr:first-child td{border-top:0}
.ucat-tbl tbody tr:hover{background:rgba(248,250,252,.7)}
.ucat-id{display:flex;align-items:flex-start;gap:14px}
/* La pastille loge l'IMAGE d'origine: on ne remplace pas les icones existantes. */
.ucat-ic{width:36px;height:36px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;
 display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;margin-top:2px;overflow:hidden;
 color:#64748b;font-size:15px}
.ucat-ic img{max-width:22px;max-height:22px}
.ucat-n{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.ucat-n b{font-size:13px;font-weight:600;color:#1e293b}
.ucat-tag{font-family:'JetBrains Mono',ui-monospace,Menlo,Consolas,monospace;font-size:10px;
 background:#f1f5f9;color:#475569;padding:1px 6px;border-radius:4px;border:1px solid #e2e8f0}
.ucat-tag-warn{background:#fffbeb;color:#b45309;border-color:#fde68a}
.ucat-d{font-size:12px;color:#64748b;line-height:1.5;margin:3px 0 0;max-width:44rem}
.ucat-bt{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:12px;font-weight:600;
 color:#047857!important;background:#ecfdf5;border:1px solid #d1fae5;border-radius:8px;
 text-decoration:none!important;cursor:pointer;white-space:nowrap}
.ucat-bt:hover{background:#d1fae5}
.ucat-bt-off{color:#94a3b8!important;background:#f8fafc;border-color:#e2e8f0;cursor:default}
.ucat-bt-off:hover{background:#f8fafc}
.ucat-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;
 background:#f8fafc;border-top:1px solid #e2e8f0;padding:13px 18px;font-size:13px;color:#475569}
.ucat-empty{padding:34px 20px;text-align:center;color:#64748b}
@media(max-width:700px){.ucat-find{max-width:none}}
</style>";
}
/** ufdbcat_show — la dimension « Afficher »: liste FERMEE, elle choisit un SQL. */
function ufdbcat_show(){
    $v=isset($_GET["show"])?(string)$_GET["show"]:"all";
    $ok=array("all","official","free","personal");
    return in_array($v,$ok,true)?$v:"all";
}

/** ufdbcat_row — une categorie proposee a l'ajout. */
function ufdbcat_row($icon,$name,$tag,$description,$items,$js,$enabled){
    $h ="<tr>";
    $h.="<td><div class='ucat-id'>";
    // L'ICONE reste celle d'aujourd'hui: une image de personal_categories.category_icon.
    $h.="<span class='ucat-ic'><img src='".htmlspecialchars($icon)."' alt=''></span>";
    $h.="<div><div class='ucat-n'><b>$name</b>";
    if($tag!==""){ $h.="<span class='ucat-tag'>#".htmlspecialchars($tag)."</span>"; }
    if($items!==""){ $h.="<span class='ucat-tag'>$items</span>"; }
    if(!$enabled){ $h.="<span class='ucat-tag ucat-tag-warn'>{license_error}</span>"; }
    $h.="</div>";
    if($description!==""){ $h.="<p class='ucat-d'>$description</p>"; }
    $h.="</div></div></td>";
    $h.="<td style='width:1%;text-align:right' nowrap>";
    $h.=$enabled
        ? "<a class='ucat-bt' href=\"javascript:blur();\" OnClick=\"$js\"><i class='fas fa-plus'></i> {add}</a>"
        : "<span class='ucat-bt ucat-bt-off'><i class='fas fa-plus'></i> {add}</span>";
    $h.="</td></tr>";
    return $h;
}

/** ufdbcat_group_row — un GROUPE de categories, propose au meme endroit. */
function ufdbcat_group_row($groupname,$count,$js){
    $h ="<tr>";
    $h.="<td><div class='ucat-id'>";
    $h.="<span class='ucat-ic'><i class='fas fa-folder-tree'></i></span>";
    $h.="<div><div class='ucat-n'><b>".htmlspecialchars($groupname)."</b>"
       ."<span class='ucat-tag'>".intval($count)." {categories}</span></div>";
    $h.="<p class='ucat-d'>{categories_groups}</p>";
    $h.="</div></div></td>";
    $h.="<td style='width:1%;text-align:right' nowrap>"
       ."<a class='ucat-bt' href=\"javascript:blur();\" OnClick=\"$js\"><i class='fas fa-plus'></i> {add}</a></td>";
    $h.="</tr>";
    return $h;
}

/**
 * ufdbcat_presets — les ajouts en un geste.
 *
 * Ces quatre actions EXISTAIENT deja, mais sur la vue de la regle, pas dans le
 * dialogue ou l'on choisit des categories -- c'est-a-dire partout sauf a l'endroit
 * ou on les cherche. La maquette les place ici, repliees dans un menu; on les rend
 * a plat: un menu deroulant demande un clic de plus pour decouvrir ce qu'il cache.
 */
function ufdbcat_presets($page,$url_options,$corp){
    if(!$corp){ return ""; }
    $items=array(
        array("#f59e0b","{dangerous_categories}","{dangerous_categories_short}","category-add-dangerous-js"),
        array("#06b6d4","{pollution_categories}","{pollution_categories_short}","category-add-polluate-js"),
        array("#005447","{nonproductive}","{nonproductive_short}","category-add-nonproductive-js"),
        array("#334155","{all_categories}","{all_categories_short}","category-add-all-js"),
    );
    $h ="<section class='ucat-pre'>";
    $h.="<h3 class='ucat-pre-h'><i class='fas fa-bolt'></i> {quick_add}</h3>";
    $h.="<div class='ucat-pre-g'>";
    foreach($items as $it){
        $h.="<a class='ucat-pre-b' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?".$it[3]."=yes&$url_options')\">"
           ."<span class='ucat-pre-d' style='background:".$it[0]."'></span>"
           ."<span><span class='ucat-pre-n'>".$it[1]."</span>"
           ."<span class='ucat-pre-s'>".$it[2]."</span></span></a>";
    }
    return $h."</div></section>";
}

/**
 * categoy_add — hote du selecteur.
 *
 * Hote + corps, comme partout ailleurs sur cette famille de pages: le selecteur
 * « Afficher » recharge la liste sans reconstruire la modale, et un conteneur ne peut
 * pas se remplacer lui-meme.
 */
function categoy_add(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID_int=isset($_GET["ID"])?intval($_GET["ID"]):null;
    $modeblk_int=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $groupid_int=isset($_GET["groupid"])?intval($_GET["groupid"]):null;
    $add=array();
    if($ID_int!==null){$add[]="ID=$ID_int";}
    if(isset($_GET["modeblk"])){$add[]="modeblk=$modeblk_int";}
    if($groupid_int!==null){$add[]="groupid=$groupid_int";}
    $url=@implode("&",$add);
    echo $tpl->_ENGINE_parse_body(
        "<div id='ufdbcat-host'></div>"
       ."<script>LoadAjaxSilent('ufdbcat-host','$page?category-add-body=yes&$url&show=all');</script>");
}

function categoy_add_body(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $q                  = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $NOGROUP            = false;
    $show               = ufdbcat_show();
    $useCGuardCategories= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCGuardCategories"));
    $CORP_LICENSE       = $GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();

    // unserialize() rend false sur un reglage vide -- l'etat normal d'une appliance qui
    // n'a pas encore telecharge de base. L'indexation qui suit leverait alors un
    // avertissement PHP 8 a chaque ligne.
    $UfdbMasterCache=@unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    if(!is_array($UfdbMasterCache)){ $UfdbMasterCache=array(); }

    $ID_int=isset($_GET["ID"])?intval($_GET["ID"]):null;
    $modeblk_int=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $groupid_int=isset($_GET["groupid"])?intval($_GET["groupid"]):null;
    $add=array();
    if($ID_int!==null){$add[]="ID=$ID_int";}
    if(isset($_GET["modeblk"])){$add[]="modeblk=$modeblk_int";}
    if($groupid_int!==null){$add[]="groupid=$groupid_int";}
    $url_options=@implode("&",$add);

    // Ce qui est DEJA rattache: on ne le propose pas.
    $cats=array();
    if($ID_int!==null) {
        $results=$q->QUERY_SQL_PARAMS("SELECT category FROM webfilter_blks WHERE webfilter_id=? AND modeblk=?",
            array($ID_int,$modeblk_int));
        if(is_array($results)){ foreach($results as $l){ $cats[$l["category"]]=true; } }
        $results=$q->QUERY_SQL_PARAMS("SELECT ID FROM webfilter_blklnk WHERE webfilter_ruleid=? AND blacklist=?",
            array($ID_int,$modeblk_int));
        if(is_array($results)){ foreach($results as $l){ $cats["GpCatz".intval($l["ID"])]=true; } }
    }
    if($groupid_int!==null){
        $NOGROUP=true;
        $results=$q->QUERY_SQL_PARAMS("SELECT category FROM webfilter_blkcnt WHERE webfilter_blkid=?",
            array($groupid_int));
        if(is_array($results)){ foreach($results as $l){ $cats[$l["category"]]=true; } }
    }

    $rows=array();

    // ---- Groupes de categories -------------------------------------------
    if(!$NOGROUP && ($show=="all")){
        $gres=$q->QUERY_SQL("SELECT ID,groupname FROM webfilter_blkgp WHERE enabled=1 ORDER BY groupname");
        if(!$q->ok){
            writelogs("webfilter_blkgp: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            $gres=array();
        }
        if(!is_array($gres)){ $gres=array(); }
        // Une seule requete pour tous les comptes, au lieu d'une par groupe.
        $counts=array();
        $cres=$q->QUERY_SQL("SELECT webfilter_blkid, COUNT(category) AS n FROM webfilter_blkcnt GROUP BY webfilter_blkid");
        if(is_array($cres)){ foreach($cres as $c){ $counts[intval($c["webfilter_blkid"])]=intval($c["n"]); } }
        foreach($gres as $ligne){
            $gid=intval($ligne["ID"]);
            if(isset($cats["GpCatz$gid"])){ continue; }
            $rows[]=ufdbcat_group_row((string)$ligne["groupname"],
                isset($counts[$gid])?$counts[$gid]:0,
                "Loadjs('$page?category-post-js=GpCatz$gid&$url_options')");
        }
    }

    // ---- Catalogue de categories ------------------------------------------
    $pg=new postgres_sql();
    if(!$pg->TABLE_EXISTS("personal_categories")){
        $categories=new categories();
        $categories->initialize();
    }
    // Categorie locale : remotecatz a 0 ou NULL, ou « distante » sans service d'origine.
    // La page ecrivait cette normalisation en base a CHAQUE affichage (deux UPDATE) ;
    // elle est desormais faite par articarest (categories.PatchTable au demarrage, et a
    // l'ecriture de la synchro distante). La condition garde ici le meme resultat sans ecrire.
    $local="(COALESCE(remotecatz,0)=0 OR COALESCE(serviceid,0)=0)";
    $where="free_category=0 AND $local";
    if($useCGuardCategories==1){ $where="($local OR serviceid=999991)"; }
    // La dimension « Afficher » choisit un SQL, pas un filtre de texte: elle porte sur
    // des colonnes, pas sur ce qui est ecrit dans la ligne.
    if($show=="official"){ $where="official_category=1 AND $local"; }
    if($show=="free")    { $where="free_category=1 AND $local"; }
    if($show=="personal"){ $where="official_category=0 AND free_category=0 AND $local"; }
    $results=$pg->QUERY_SQL("SELECT * FROM personal_categories WHERE $where ORDER BY categoryname");
    if(!$pg->ok){
        writelogs("personal_categories: ".$pg->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo ufdbcat_css().$tpl->_ENGINE_parse_body("<div class='alert alert-danger'>{database_error}</div>");
        return true;
    }

    // Categories retirees du catalogue: la liste etait indexee SANS isset(), donc
    // chaque categorie non blacklistee levait un avertissement PHP 8.
    $BlacklistedCatz=array(229=>true,230=>true,231=>true,232=>true,233=>true,
                           234=>true,236=>true,237=>true,238=>true);
    $available=0;
    if($results){ $available=pg_num_rows($results); }

    while($results && ($ligne=pg_fetch_assoc($results))){
        $category_id=intval($ligne["category_id"]);
        if(isset($BlacklistedCatz[$category_id])){ continue; }
        if(isset($cats[$category_id])){ continue; }
        $categoryname=(string)$ligne["categoryname"];
        if(preg_match("#^reserved#",$categoryname)){ continue; }

        $official=intval($ligne["official_category"]);
        $free=intval($ligne["free_category"]);
        if($official==1 && !$CORP_LICENSE){
            // Sans licence, la categorie officielle reste VISIBLE mais non ajoutable:
            // l'ancienne version la retirait de la liste, si bien que rien a l'ecran
            // n'expliquait pourquoi le catalogue etait si court.
            $enabled=false;
        }else{
            $enabled=true;
        }

        $icon=(string)$ligne["category_icon"];
        if($icon==""){ $icon="img/20-categories-personnal.png"; }

        $count=0;
        if($official==1 || $free==1){
            if(isset($UfdbMasterCache[$category_id]["items"])){ $count=intval($UfdbMasterCache[$category_id]["items"]); }
        }else{
            $count=intval($ligne["items"]);
        }
        $itemsTxt=($count>0)?$tpl->FormatNumber($count)." {items}":"";

        // Le nom et la description sortent du CATALOGUE: on les echappe AVANT
        // l'analyse, jamais apres -- echapper une sortie du moteur afficherait
        // « Ao&ucirc;t » en toutes lettres.
        $name=$tpl->_ENGINE_parse_body(htmlspecialchars($categoryname));
        $desc=$tpl->_ENGINE_parse_body(htmlspecialchars($tpl->utf8_encode((string)$ligne["category_description"])));

        // Le slug n'est montre que s'il APPORTE quelque chose: quand aucune traduction
        // n'existe pour lui, le nom affiche EST le slug, et « adult #adult » n'informe
        // personne. La maquette montre les deux parce qu'elle suppose un libelle
        // humain a cote d'un identifiant technique -- ce qui n'est vrai qu'ici.
        $tag=($name===htmlspecialchars($categoryname))?"":$categoryname;
        $rows[]=ufdbcat_row($icon,$name,$tag,$desc,$itemsTxt,
            "Loadjs('$page?category-post-js=$category_id&$url_options')",$enabled);
    }

    $shown=count($rows);
    $showOpts=array("all"=>"{all}","official"=>"{official}","free"=>"{free}","personal"=>"{personal}");

    $html=array();
    $html[]="<div class='ucat'>";

    // Barre de recherche + dimension d'affichage.
    $html[]="<div class='ucat-bar'>";
    $html[]="<div class='ucat-find'><i class='fas fa-magnifying-glass ucat-find-i'></i>"
           ."<input type='text' id='ufdbcat-search' placeholder=\"{search}\" oninput=\"UcatFilter()\">"
           ."<button type='button' OnClick=\"UcatFilter()\">{search}</button></div>";
    $html[]="<div class='ucat-show'><span>{show}</span><select id='ufdbcat-show' OnChange=\"UcatShow(this.value)\">";
    foreach($showOpts as $k=>$lbl){
        $html[]="<option value='".htmlspecialchars($k)."'".($k===$show?" selected":"").">$lbl</option>";
    }
    $html[]="</select></div></div>";

    $html[]=ufdbcat_presets($page,$url_options,$CORP_LICENSE);

    if(!$CORP_LICENSE){
        $html[]="<div class='alert alert-warning' style='margin:0'>{categories_explain_no_license}</div>";
    }

    $html[]="<div class='ucat-wrap'><div class='ucat-scroll'>";
    $html[]="<table class='ucat-tbl'><thead><tr>";
    $html[]="<th>{category}</th><th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ufdbcat-rows'>";
    if($shown==0){
        $html[]="<tr><td colspan='2'><div class='ucat-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='ucat-foot'>";
    $html[]="<span>{showing} <b id='ufdbcat-count'>$shown</b> / $shown {categories}"
           .($available>$shown?" &middot; ".intval($available)." {available}":"")."</span>";
    $html[]="<span class='awc-pg' id='ufdbcat-pager'></span>";
    $html[]="</div></div>";
    $html[]="</div>";

    // JS concatene APRES l'analyse: ses blocs {..} seraient lus comme des jetons de traduction.
    $js="<script>\n"
       ."if(typeof NoSpinner==='function'){NoSpinner();}\n"
       ."function UcatShow(v){ LoadAjaxSilent('ufdbcat-host','$page?category-add-body=yes&$url_options&show='+encodeURIComponent(v)); }\n"
       // Filtre maison plutot qu'awc_filter_js(): ce tableau n'est pas un tableau awc
       // et le compteur du pied doit suivre ce que la recherche laisse voir.
       ."function UcatFilter(){\n"
       ."  var q=document.getElementById('ufdbcat-search'); if(!q){return;}\n"
       ."  var s=q.value.toLowerCase().replace(/^\\s+|\\s+$/g,''),n=0;\n"
       ."  var rows=document.querySelectorAll('#ufdbcat-rows tr');\n"
       ."  for(var i=0;i<rows.length;i++){\n"
       ."    var t=(rows[i].textContent||'').toLowerCase();\n"
       ."    var ok=(s===''||t.indexOf(s)>=0);\n"
       ."    rows[i].style.display=ok?'':'none'; if(ok){n++;}\n"
       ."  }\n"
       ."  var c=document.getElementById('ufdbcat-count'); if(c){c.innerHTML=n;}\n"
       ."}\n"
       ."</script>";

    echo ufdbcat_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
    return true;
}

function category_all_js():bool{
    $ID=intval($_GET["ID"]);
    // modeblk vaut 0 ou 1 et RIEN d'autre: il finit dans un nom de fonction JavaScript
    // et, plus loin, dans deux requetes SQL ou il etait concatene tel quel.
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $tpl=new template_admin();
    // JSON, et seulement les deux entiers dont le handler a besoin. La charge partait
    // en serialize($_GET) COMPLET et revenait dans un unserialize(): une deserialisation
    // PHP d'une donnee qui traverse le navigateur -- la classe de faille qui a deja
    // touche ce produit (CVE-2024-2054). json_decode n'instancie aucun objet.
    $MAIN=base64_encode(json_encode(array("ID"=>$ID,"modeblk"=>$modeblk)));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{add_all_categories_explain}<br>{category_family_add_expl}",
        "category-add-all",$MAIN,
        $js
    );


}
function category_dangerous_js():bool{
    $ID=intval($_GET["ID"]);
    // modeblk vaut 0 ou 1 et RIEN d'autre: il finit dans un nom de fonction JavaScript
    // et, plus loin, dans deux requetes SQL ou il etait concatene tel quel.
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $tpl=new template_admin();
    // JSON, et seulement les deux entiers dont le handler a besoin. La charge partait
    // en serialize($_GET) COMPLET et revenait dans un unserialize(): une deserialisation
    // PHP d'une donnee qui traverse le navigateur -- la classe de faille qui a deja
    // touche ce produit (CVE-2024-2054). json_decode n'instancie aucun objet.
    $MAIN=base64_encode(json_encode(array("ID"=>$ID,"modeblk"=>$modeblk)));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{dangerous_categories_explain}<br>{category_family_add_expl}",
        "category-add-dangerous",$MAIN,
        $js
    );
}
function category_nonproductive_js():bool{
    $ID=intval($_GET["ID"]);
    // modeblk vaut 0 ou 1 et RIEN d'autre: il finit dans un nom de fonction JavaScript
    // et, plus loin, dans deux requetes SQL ou il etait concatene tel quel.
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $tpl=new template_admin();
    // JSON, et seulement les deux entiers dont le handler a besoin. La charge partait
    // en serialize($_GET) COMPLET et revenait dans un unserialize(): une deserialisation
    // PHP d'une donnee qui traverse le navigateur -- la classe de faille qui a deja
    // touche ce produit (CVE-2024-2054). json_decode n'instancie aucun objet.
    $MAIN=base64_encode(json_encode(array("ID"=>$ID,"modeblk"=>$modeblk)));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{nonproductive_cat_explain}<br>{category_family_add_expl}",
        "category-add-nonproductive",$MAIN,
        $js
    );
}

function category_polluate_js():bool{
    $ID=intval($_GET["ID"]);
    // modeblk vaut 0 ou 1 et RIEN d'autre: il finit dans un nom de fonction JavaScript
    // et, plus loin, dans deux requetes SQL ou il etait concatene tel quel.
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $page=CurrentPageName();
    $tpl=new template_admin();
    // JSON, et seulement les deux entiers dont le handler a besoin. La charge partait
    // en serialize($_GET) COMPLET et revenait dans un unserialize(): une deserialisation
    // PHP d'une donnee qui traverse le navigateur -- la classe de faille qui a deja
    // touche ce produit (CVE-2024-2054). json_decode n'instancie aucun objet.
    $MAIN=base64_encode(json_encode(array("ID"=>$ID,"modeblk"=>$modeblk)));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{pollution_categories_explain}<br>{category_family_add_expl}",
        "category-add-polluate",$MAIN,
        $js
    );
}
function category_clean_js():bool{
    $ID=intval($_GET["ID"]);
    // modeblk vaut 0 ou 1 et RIEN d'autre: il finit dans un nom de fonction JavaScript
    // et, plus loin, dans deux requetes SQL ou il etait concatene tel quel.
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;
    $page=CurrentPageName();
    $tpl=new template_admin();
    // JSON, et seulement les deux entiers dont le handler a besoin. La charge partait
    // en serialize($_GET) COMPLET et revenait dans un unserialize(): une deserialisation
    // PHP d'une donnee qui traverse le navigateur -- la classe de faille qui a deja
    // touche ce produit (CVE-2024-2054). json_decode n'instancie aucun objet.
    $MAIN=base64_encode(json_encode(array("ID"=>$ID,"modeblk"=>$modeblk)));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{delete_all_items}",
        "category-clean",$MAIN,
        $js
    );
}



function category_dangerous_list():array{
    $list="92,135,64,140,181,111,105,46,149,238";
    return explode(",",$list);
}
function category_nonproductive_list():array{
    $list="10,14,41,42,45,57,58,132,97,150,148,130,112,109,71,238";
    return explode(",",$list);
}
function category_polluate_list():array{
    $list="143,5,91,238";
    return explode(",",$list);
}
function category_inject($Next,$ID,$modeblk):bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $CurrentCategories=CurrentCategories($ID,$modeblk);
    foreach ($Next as $catid){
        if(isset($CurrentCategories[$catid])){
            continue;
        }

        $q->QUERY_SQL_PARAMS("INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES (?,?,?)",
            array(intval($ID),intval($catid),intval($modeblk)));
        if (!$q->ok) {
            // Le texte SQLite nomme tables et colonnes: journal, pas ecran.
            writelogs("category_inject: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            $tpl->js_mysql_alert("{database_error}");
            return false;
        }

    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    return true;

}
function CurrentCategories($ID,$modeblk):array{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $SAVED=array();
    $results=$q->QUERY_SQL_PARAMS("SELECT category FROM webfilter_blks WHERE webfilter_id=? AND modeblk=?",
        array(intval($ID),intval($modeblk)));
    if(!is_array($results)){ $results=array(); }
    foreach ($results as $ligne){
        $category=$ligne["category"];
        $SAVED[$category]=true;
    }
    return $SAVED;
}
function category_polluate_perform():bool{
    $MAIN=json_decode(base64_decode($_POST["category-add-polluate"]),true);
    // Une charge illisible ne doit PAS retomber sur des valeurs par defaut: ID=0 est la
    // regle par defaut, une cible parfaitement legitime -- un payload malforme aurait
    // donc agi sur elle en silence. On refuse.
    if(!is_array($MAIN) || !isset($MAIN["ID"])){ echo "{error}"; return false; }
    $ID=isset($MAIN["ID"])?intval($MAIN["ID"]):0;
    $modeblk=(isset($MAIN["modeblk"]) && intval($MAIN["modeblk"])==1)?1:0;
    // Verrou Artica Meta (regle Meta, ou regle par defaut tenue), relu cote serveur.
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_text(); }
    $category_dangerous_list=category_polluate_list();
    if(!category_inject($category_dangerous_list,$ID,$modeblk)){
        return false;
    }

    return admin_tracks("Adding Web-Filtering Polluate categories for rule $ID");
}
function category_dangerous_perform():bool{
    $MAIN=json_decode(base64_decode($_POST["category-add-dangerous"]),true);
    // Une charge illisible ne doit PAS retomber sur des valeurs par defaut: ID=0 est la
    // regle par defaut, une cible parfaitement legitime -- un payload malforme aurait
    // donc agi sur elle en silence. On refuse.
    if(!is_array($MAIN) || !isset($MAIN["ID"])){ echo "{error}"; return false; }
    $ID=isset($MAIN["ID"])?intval($MAIN["ID"]):0;
    $modeblk=(isset($MAIN["modeblk"]) && intval($MAIN["modeblk"])==1)?1:0;
    // Verrou Artica Meta (regle Meta, ou regle par defaut tenue), relu cote serveur.
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_text(); }
    $category_dangerous_list=category_dangerous_list();
    if(!category_inject($category_dangerous_list,$ID,$modeblk)){
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    return admin_tracks("Adding Web-Filtering Dangerous categories for rule $ID");

}
function category_clean_perform():bool{
    $MAIN=json_decode(base64_decode($_POST["category-clean"]),true);
    // Une charge illisible ne doit PAS retomber sur des valeurs par defaut: ID=0 est la
    // regle par defaut, une cible parfaitement legitime -- un payload malforme aurait
    // donc agi sur elle en silence. On refuse.
    if(!is_array($MAIN) || !isset($MAIN["ID"])){ echo "{error}"; return false; }
    $ID=isset($MAIN["ID"])?intval($MAIN["ID"]):0;
    $modeblk=(isset($MAIN["modeblk"]) && intval($MAIN["modeblk"])==1)?1:0;
    // Verrou Artica Meta (regle Meta, ou regle par defaut tenue), relu cote serveur.
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_text(); }

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_blks WHERE webfilter_id=? AND modeblk=?",
        array($ID,$modeblk));
    if(!$q->ok){
        // L'ancienne version renvoyait la REQUETE et le message SQLite au navigateur.
        writelogs("category_clean_perform($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}";
        return false;
    }
    return admin_tracks("Removing Web-Filtering categories from rule $ID");
}
function category_nonproductive_perform():bool{
    $MAIN=json_decode(base64_decode($_POST["category-add-nonproductive"]),true);
    // Une charge illisible ne doit PAS retomber sur des valeurs par defaut: ID=0 est la
    // regle par defaut, une cible parfaitement legitime -- un payload malforme aurait
    // donc agi sur elle en silence. On refuse.
    if(!is_array($MAIN) || !isset($MAIN["ID"])){ echo "{error}"; return false; }
    $ID=isset($MAIN["ID"])?intval($MAIN["ID"]):0;
    $modeblk=(isset($MAIN["modeblk"]) && intval($MAIN["modeblk"])==1)?1:0;
    // Verrou Artica Meta (regle Meta, ou regle par defaut tenue), relu cote serveur.
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_text(); }
    $category_dangerous_list=category_nonproductive_list();
    if(!category_inject($category_dangerous_list,$ID,$modeblk)){
        return false;
    }

    return admin_tracks("Adding Web-Filtering Non-productives categories for rule $ID");


}

/**
 * ufdbc_group_row — un GROUPE de categories rattache a la regle.
 *
 * Un groupe et une categorie sont deux choses differentes rangees dans le meme
 * tableau: le groupe en embarque N. La pastille d'icone et le badge de type le disent,
 * plutot que de laisser deviner d'apres le libelle de la colonne description.
 */
function ufdbc_group_row($IDrow,$groupname,$enabled,$count,$jsDel,$canDelete){
    $ico=$enabled?"awc-ico":"awc-ico awc-ico-off";
    $nameCls=$enabled?"awc-name":"awc-name awc-name-off";
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='$ico'><i class='fas fa-folder-tree'></i></span>";
    $h.="<span><span class='$nameCls'>".htmlspecialchars($groupname)."</span>";
    $h.=$enabled?"":" <span class='awc-badge awc-badge-off'>{disabled}</span>";
    $h.="<div class='awc-meta'>{categories_groups}</div>";
    $h.="</span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-tag'>"
       .intval($count)." {categories}</span></td>";
    $h.="<td style='width:1%' nowrap>".ufdbc_acts($jsDel,$canDelete)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * ufdbc_category_row — une categorie de la regle.
 *
 * $description SORT du moteur de traduction (donc du HTML, avec ses entites) et n'est
 * pas re-echappee ici; $name est passe deja echappe par l'appelant.
 */
function ufdbc_category_row($name,$description,$icon,$licensed,$jsDel,$canDelete){
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    // L'icone de categorie est une IMAGE, pas une glyphe: on la loge dans la meme
    // pastille de 40px que les autres tableaux pour que les lignes s'alignent.
    $h.="<span class='awc-ico' style='background:rgba(38,50,56,.06);padding:6px'>"
       ."<img src='".htmlspecialchars($icon)."' style='max-width:100%;max-height:100%'></span>";
    $h.="<span><span class='awc-name'>$name</span>";
    if($description!==""){ $h.="<div class='awc-meta'>$description</div>"; }
    $h.="</span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap>"
       .($licensed?"<span class='awc-tag'>{category}</span>"
                  :"<span class='awc-badge awc-badge-warn'>{no_license}</span>")."</td>";
    $h.="<td style='width:1%' nowrap>".ufdbc_acts($jsDel,$canDelete)."</td>";
    $h.="</tr>";
    return $h;
}

/** ufdbc_acts — la colonne d'actions, revelee au survol. */
function ufdbc_acts($jsDel,$canDelete){
    if(!$canDelete){ return "<div class='awc-acts'>&nbsp;</div>"; }
    return "<div class='awc-acts'><a class='awc-act awc-act-danger' title=\"{delete}\" "
        ."href=\"javascript:blur();\" OnClick=\"$jsDel\"><i class='fas fa-trash'></i></a></div>";
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    FILL_CATEGORIES_MEM();
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();

    $postgres=new postgres_sql();
    $btns=null;

    // modeblk vaut 0 (liste noire) ou 1 (liste blanche) et RIEN d'autre. Il partait
    // brut dans deux requetes SQL et dans une dizaine d'URL.
    $ruleid=intval($_GET["ID"]);
    $modeblk=(isset($_GET["modeblk"]) && intval($_GET["modeblk"])==1)?1:0;

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $users=new usersMenus();
    $canDelete=($PowerDNSEnableClusterSlave==0 && $users->AsDansGuardianAdministrator);

    $html=array();
    // Regle Artica Meta (ou regle par defaut tenue) : categories affichees, rien de modifiable.
    $metaLocked=ufdbrules_meta_locked_rule($ruleid);
    if($metaLocked){ $canDelete=false; }
    if($PowerDNSEnableClusterSlave==1){
        $html[]=$tpl->div_warning("{formlock_clutser_slave}");
    }elseif($metaLocked){
        $html[]=ufdbrules_meta_banner($tpl,($ruleid==0)?"default":$ruleid);
    }else{
        $topbuttons=array();
        $topbuttons[]=array("Loadjs('$page?category-add-js=yes&ID=$ruleid&modeblk=$modeblk')",ico_plus,"{add_categories}");
        $topbuttons[]=array("Loadjs('$page?category-clean=yes&ID=$ruleid&modeblk=$modeblk')",ico_trash,"{delete_all}");
        $btns=$tpl->th_buttons($topbuttons);
    }

    // Identifiants uniques par regle ET par mode: le selecteur (categoy_add(),
    // prefixe ufdbcat-) s'ouvre EN MODALE PAR-DESSUS ce tableau, et les onglets
    // liste noire / liste blanche de fw.ufdb.rules.edit.php rendent deux fois
    // cette meme fonction. Un prefixe partage ferait ramasser le mauvais <tbody>
    // par document.getElementById().
    $uid="ufdbsel-$ruleid-$modeblk";

    $html[]=awc_search_bar("$uid-search","{search}");
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    if($btns!==null){ $html[]="<tr><th colspan='3'>$btns</th></tr>"; }
    // Trois colonnes, pas cinq. Le nom et sa description tiennent dans le bloc
    // d'identite; l'ancien en-tete declarait cinq colonnes (colspan 2 + trois) pour
    // des lignes qui n'en emettaient que quatre -- une colonne fantome de plus.
    $html[]="<tr><th>{category}</th>"
           ."<th class='center' style='width:1%'>{type}</th>"
           ."<th style='width:1%;text-align:right'>{actions}</th></tr>";
    $html[]="</thead>";
    $html[]="<tbody id='$uid-rows'>";
    $ROWS=0;

    // ---- Groupes de categories rattaches a la regle -------------------------
    $results=$q->QUERY_SQL_PARAMS(
        "SELECT ID,webfilter_blkid FROM webfilter_blklnk WHERE webfilter_ruleid=? AND blacklist=?",
        array($ruleid,$modeblk));
    if(!$q->ok){
        // Le message SQLite nomme tables et colonnes: journal, pas ecran -- et
        // surtout pas un FATAL_ERROR_SHOW_128 qui remplace tout le tableau.
        writelogs("webfilter_blklnk: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        $results=array();
    }
    if(!is_array($results)){ $results=array(); }

    foreach($results as $ligne){
        $IDrow=intval($ligne["ID"]);
        $blkid=intval($ligne["webfilter_blkid"]);
        $rows2=$q->QUERY_SQL_PARAMS("SELECT groupname,enabled FROM webfilter_blkgp WHERE ID=?",array($blkid));
        $groupname=isset($rows2[0]["groupname"])?(string)$rows2[0]["groupname"]:"#$blkid";
        $enabled=isset($rows2[0]["enabled"])?(intval($rows2[0]["enabled"])==1):false;
        $rows3=$q->QUERY_SQL_PARAMS("SELECT COUNT(category) AS CountDeCats FROM webfilter_blkcnt WHERE webfilter_blkid=?",array($blkid));
        $Categories=isset($rows3[0]["CountDeCats"])?intval($rows3[0]["CountDeCats"]):0;

        $jsdel="Loadjs('$page?category-del-js=GpCatz$IDrow&ID=$ruleid&modeblk=$modeblk')";
        $html[]=ufdbc_group_row($IDrow,$groupname,$enabled,$Categories,$jsdel,$canDelete);
        $ROWS++;
    }

    // ---- Categories directement rattachees a la regle -----------------------
    $results=$q->QUERY_SQL_PARAMS(
        "SELECT category FROM webfilter_blks WHERE webfilter_id=? AND modeblk=?",
        array($ruleid,$modeblk));
    if(!$q->ok){
        writelogs("webfilter_blks: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        $results=array();
    }
    if(!is_array($results)){ $results=array(); }

    foreach($results as $ligne){
        $category_id=intval($ligne["category"]);
        $ligne2=array();
        if(isset($GLOBALS["categories_descriptions"][$category_id])){
            $ligne2=$GLOBALS["categories_descriptions"][$category_id];
        }else{
            // pg_fetch_array(false) leve une TypeError en PHP 8: la garde qui suivait
            // arrivait UNE LIGNE TROP TARD et ne protegeait rien. On teste le resultat
            // de la requete avant de le lire.
            // postgres_sql n'offre pas de variante parametree; $category_id est deja
            // passe par intval(), donc l'interpolation ne peut porter qu'un entier.
            $res=$postgres->QUERY_SQL("SELECT * FROM personal_categories WHERE category_id='$category_id'");
            if($res!==false && $res!==null){
                $fetched=@pg_fetch_array($res);
                if(is_array($fetched)){ $ligne2=$fetched; }
            }
        }

        $categoryname=isset($ligne2["categoryname"])?$tpl->_ENGINE_parse_body($ligne2["categoryname"]):null;
        $category_description=isset($ligne2["category_description"])?(string)$ligne2["category_description"]:"";
        $category_icon=isset($ligne2["category_icon"])?(string)$ligne2["category_icon"]:"";
        if($category_icon==""){ $category_icon="img/20-categories-personnal.png"; }
        if($categoryname==null || $categoryname===""){
            $categoryname=$tpl->_ENGINE_parse_body("{unknown}: {category} $category_id");
            $category_icon="img/20-check-red.png";
        }
        $licensed=($CORP_LICENSE || $category_id>=250);
        $description=$tpl->utf8_encode($tpl->_ENGINE_parse_body($category_description));

        $jsdel="Loadjs('$page?category-del-js=$category_id&ID=$ruleid&modeblk=$modeblk')";
        // $categoryname et $description sortent du moteur de traduction: ce sont deja
        // du HTML avec leurs entites, les re-echapper afficherait « Ao&ucirc;t ».
        $html[]=ufdbc_category_row($categoryname,trim($description),$category_icon,$licensed,$jsdel,$canDelete);
        $ROWS++;
    }

    if($ROWS==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_category_has_been_added}</div></td></tr>";
    }

    $html[]="</tbody>";
    $html[]="</table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".$tpl->FormatNumber($ROWS)." / "
           .$tpl->FormatNumber($ROWS)." {categories}</span>"
           ."<span class='awc-pg' id='$uid-pager'></span></div>";
    $html[]="</div></div>";
    // ICON_SCRIPTS_COMPILE() draine $GLOBALS["TPL_ICON_SCRIPTS"], alimente en parallele
    // par icon_check(): il reste juste meme si une ligne est batie par sa propre
    // fonction, ce que $tpl->ICON_SCRIPTS (propriete d'INSTANCE) ne garantit pas.
    $html[]="<script>if(typeof NoSpinner==='function'){NoSpinner();}\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    // CSS et JS concatenes AUTOUR de _ENGINE_parse_body(), et le filtre APRES
    // le pager (qui lui passe la main par tb.__awcRedraw).
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"$uid-rows","$uid-pager")
        .awc_filter_js("$uid-search","$uid-rows","$uid-pager");
    return true;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}