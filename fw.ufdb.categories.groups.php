<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["start-table"])){start_table();exit;}
if(isset($_POST["NewGroup"])){group_save();exit;}
if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_GET["new-group-popup"])){new_group_popup();exit;}
if(isset($_GET["group-enable"])){group_enable();exit;}
if(isset($_GET["group-delete"])){group_delete();exit;}
if(isset($_POST["group-delete"])){group_delete_perform();exit;}
if(isset($_GET["ID"])){group_js();exit;}
if(isset($_GET["ID-tab"])){group_tab();exit;}
if(isset($_GET["categories-start"])){categories_start();exit;}
if(isset($_GET["categories-table"])){categories_table();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["unlink-category"])){category_unlink();exit;}
if(isset($_POST["groupname"])){settings_save();exit;}
js();

/** ufdbg_reload — le rechargement renvoye a Loadjs apres une mutation de la liste. */
function ufdbg_reload(){
    $page=CurrentPageName();
    // Rechargement du fragment plutot que « $('#<md5 venu de l URL>').remove() »: cet
    // identifiant arrivait brut du navigateur dans du JavaScript emis, et retirer la
    // ligne a la main laissait le compteur du pied sur l'ancien total.
    return "if(document.getElementById('categories-group-main-div')){"
        ."LoadAjax('categories-group-main-div','$page?table=yes');}\n";
}

function group_delete(){
    $tpl=new template_admin();
    if(!ufdbg_require_admin()){ $tpl->js_no_privileges(); return; }
    $ID=intval($_GET["group-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_blkgp WHERE ID=?",array($ID));
    // js_confirm_delete() insere ce texte en HTML (puis l'encode en base64, donc les
    // guillemets ne cassent pas le JavaScript) -- mais un nom porteur de balises y
    // serait rendu comme du balisage. Il vient de la base, pas du moteur de traduction:
    // l'echapper est ici correct.
    $groupname=htmlspecialchars(isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID");
    $tpl->js_confirm_delete($groupname,"group-delete",$ID,ufdbg_reload());
}

function group_delete_perform(){
    if(!ufdbg_require_admin()){ echo "{access_denied}"; return; }
    $ID=intval($_POST["group-delete"]);
    if($ID<=0){ echo "{error}"; return; }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    foreach(array(
        "DELETE FROM webfilter_blklnk WHERE webfilter_blkid=?",
        "DELETE FROM webfilter_blkcnt WHERE webfilter_blkid=?",
        "DELETE FROM webfilter_blkgp WHERE ID=?",
    ) as $sql){
        $q->QUERY_SQL_PARAMS($sql,array($ID));
        if(!$q->ok){
            writelogs("group_delete_perform($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            echo "{database_error}"; return;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Removing web-filter categories group #$ID");
}

function group_enable(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!ufdbg_require_admin()){
        echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return;
    }
    $ID=intval($_GET["group-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    // Bascule en UNE operation. Le couple lecture-puis-ecriture laissait deux clics
    // rapides se croiser et rendre l'etat inverse de ce que la case affichait.
    $q->QUERY_SQL_PARAMS("UPDATE webfilter_blkgp SET enabled=CASE WHEN enabled=1 THEN 0 ELSE 1 END WHERE ID=?",
        array($ID));
    if(!$q->ok){
        writelogs("group_enable($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Toggling web-filter categories group #$ID");
}

function category_unlink(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!ufdbg_require_admin()){
        echo "alert(".json_encode($tpl->javascript_parse_text("{access_denied}")).");"; return;
    }
    $ID=intval($_GET["unlink-category"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT webfilter_blkid FROM webfilter_blkcnt WHERE ID=?",array($ID));
    $gid=isset($rows[0]["webfilter_blkid"])?intval($rows[0]["webfilter_blkid"]):0;
    $q->QUERY_SQL_PARAMS("DELETE FROM webfilter_blkcnt WHERE ID=?",array($ID));
    if(!$q->ok){
        writelogs("category_unlink($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "alert(".json_encode($tpl->javascript_parse_text("{database_error}")).");"; return;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    admin_tracks("Unlinking category row #$ID from its group");
    $page=CurrentPageName();
    echo "if(document.getElementById('categories-table-$gid-div')){"
        ."LoadAjax('categories-table-$gid-div','$page?categories-table=$gid');}\n";
}

function settings_save(){
    if(!ufdbg_require_admin()){ echo (new template_admin())->post_error("{access_denied}"); return; }
    $tpl=new template_admin();
    // groupid n'etait PAS converti et groupname pas echappe: les deux partaient
    // concatenes dans le meme UPDATE.
    $ID=intval($_POST["groupid"]);
    $groupname=trim((string)$_POST["groupname"]);
    if($ID<=0 || $groupname===""){ echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{error}")); return; }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL_PARAMS("UPDATE webfilter_blkgp SET groupname=? WHERE ID=?",array($groupname,$ID));
    if(!$q->ok){
        writelogs("settings_save($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo (new template_admin())->post_error("".$tpl->javascript_parse_text("{database_error}")); return;
    }
    admin_tracks("Renaming web-filter categories group #$ID to $groupname");
}

function group_save(){
    if(!ufdbg_require_admin()){ echo "{access_denied}"; return; }
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // CLEAN_POST() calcule bien les tableaux echappes, mais reecrit dans $_POST la
    // version DECODEE et NON echappee: appelee sans lire son retour, elle ne protege
    // rien. Le nom part donc en parametre lie.
    $name=trim((string)$_POST["NewGroup"]);
    if($name===""){ echo "{error}"; return; }
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL_PARAMS("INSERT INTO webfilter_blkgp (groupname,enabled) VALUES (?,1)",array($name));
    if(!$q->ok){
        writelogs("group_save: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}"; return;
    }
    admin_tracks("Creating web-filter categories group $name");
}

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{categories_groups}","$page?start-table=yes",980);

}
function new_group_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog3("{categories_groups}: {new_group}","$page?new-group-popup=yes",550);
}
function group_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["ID"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_blkgp WHERE ID=?",array($ID));
    $groupname=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID";
    // buildADialog() pose le titre dans « title: '$title' » SANS echappement, et
    // javascript_parse_text() ne neutralise les apostrophes que des jetons resolus,
    // pas d'un nom relu en base. htmlspecialchars(ENT_QUOTES) traite le balisage ET
    // l'apostrophe; l'antislash, qu'il laisse passer, ouvrirait une sequence dans ce
    // meme litteral et la modale ne s'ouvrirait jamais, sans un mot a l'ecran.
    $groupname=str_replace("\\","",htmlspecialchars($groupname,ENT_QUOTES));
    $tpl->js_dialog3("{categories_groups}: $groupname","$page?ID-tab=$ID",650);
}
function group_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID-tab"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_blkgp WHERE ID=?",array($ID));
    $title=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID";

    $array=array();
    if($ID>0){
        $array["{categories}"]="$page?categories-start=$ID";
        // Le nom sert de LIBELLE d'onglet, donc de cle de tableau ET de HTML: un nom
        // porteur de balises y serait rendu comme du balisage, et un nom valant
        // « {categories} » ecraserait purement et simplement le premier onglet.
        $label=htmlspecialchars($title);
        if(isset($array[$label]) || $label===""){ $label="{settings}"; }
        $array[$label]="$page?settings=$ID";
    }
    echo $tpl->tabs_default($array);
}
function categories_start(){
    $page=CurrentPageName();
    // Cette valeur entre dans un identifiant de div et dans une URL: elle arrivait
    // brute de la barre d'adresse.
    $ID=intval($_GET["categories-start"]);
    echo "<div id='categories-table-$ID-div' style='margin-top:10px'></div>"
        ."<script>LoadAjax('categories-table-$ID-div','$page?categories-table=$ID');</script>";
}


function new_group_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $form[]=$tpl->field_text("NewGroup","{group name}",null,true);
    echo $tpl->form_outside("{categories_groups}: {new_group}",$form,null,"{add}","dialogInstance3.close();LoadAjax('categories-group-main-div','$page?table=yes')","AsDansGuardianAdministrator");

}
function settings(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["settings"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_blkgp WHERE ID=?",array($ID));
    $groupname=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"";

    $form=array();
    // field_hidden() empile dans $this->XHRHIDDEN par EFFET DE BORD, et XHR_BUILD()
    // (appele par form_outside) emet cette pile quoi qu'il arrive: l'ancien
    // « $tpl->field_hidden(...); » sans « $form[]= » postait donc bien groupid.
    // On garde la valeur de retour quand meme -- le champ est alors reellement
    // present dans le DOM, ce qui rend le formulaire inspectable et ne repose plus
    // sur un effet de bord pour transmettre son identifiant.
    $form[]=$tpl->field_hidden("groupid",$ID);
    $form[]=$tpl->field_text("groupname","{group name}",$groupname,true);
    echo $tpl->form_outside("{categories_groups}: ".htmlspecialchars($groupname),$form,null,"{apply}",
        "LoadAjax('categories-group-main-div','$page?table=yes')","AsDansGuardianAdministrator");
}

function start_table(){
    $page=CurrentPageName();
    echo "<div id='categories-group-main-div'></div><script>LoadAjax('categories-group-main-div','$page?table=yes');</script>";
}
/** ufdbg_require_admin — le garde SERVEUR des cinq ecritures de cette page. */
function ufdbg_require_admin(){
    $users=new usersMenus();
    return $users->AsDansGuardianAdministrator?true:false;
}

/**
 * ufdbg_group_row — un groupe de categories.
 *
 * $check est produit par icon_check(), qui empile sa fonction BtSwitch<id>() dans
 * $GLOBALS["TPL_ICON_SCRIPTS"] AUTANT que dans la propriete d'instance: le tableau
 * doit fermer sur ICON_SCRIPTS_COMPILE(), seul moyen de drainer les deux piles.
 * Avec @implode($tpl->ICON_SCRIPTS), une ligne batie par sa propre fonction perdrait
 * sa fonction de bascule -- la case s'afficherait et ne repondrait a aucun clic.
 */
function ufdbg_group_row($ID,$groupname,$enabled,$categories,$rules,$check,$canWrite){
    $page=CurrentPageName();
    $ID=intval($ID);
    $ico=$enabled?"awc-ico":"awc-ico awc-ico-off";
    $nameCls=$enabled?"awc-name":"awc-name awc-name-off";

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='$ico'><i class='fas fa-folder-tree'></i></span>";
    $h.="<span><span class='$nameCls'><a href=\"javascript:blur();\" "
       ."OnClick=\"Loadjs('$page?ID=$ID')\">".htmlspecialchars($groupname)."</a></span>";
    $h.="<div class='awc-meta'>#$ID</div></span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-seen'>".intval($categories)."</span>"
       ."<div class='awc-meta'>{categories}</div></td>";
    // La colonne {rules} portait un en-tete depuis toujours et affichait... l'icone de
    // suppression: le corps des lignes etait decale d'une cellule par rapport a
    // l'en-tete, si bien que la colonne {delete}, elle, restait vide. Elle compte
    // desormais vraiment les regles qui referencent ce groupe.
    $h.="<td class='center' style='width:1%' nowrap>"
       .(intval($rules)>0?"<span class='awc-seen'>".intval($rules)."</span>"
                         :"<span class='awc-muted'>0</span>")
       ."<div class='awc-meta'>{rules}</div></td>";
    $h.="<td class='center' style='width:1%' nowrap>$check</td>";
    $h.="<td style='width:1%' nowrap><div class='awc-acts'>";
    if($canWrite){
        $h.="<a class='awc-act awc-act-danger' title=\"{delete}\" href=\"javascript:blur();\" "
           ."OnClick=\"Loadjs('$page?group-delete=$ID')\"><i class='fas fa-trash'></i></a>";
    }else{ $h.="&nbsp;"; }
    $h.="</div></td></tr>";
    return $h;
}

/** ufdbg_category_row — une categorie rattachee au groupe. */
function ufdbg_category_row($rowid,$categoryname,$icon,$items,$canWrite){
    $page=CurrentPageName();
    $rowid=intval($rowid);
    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    // L'icone est une IMAGE, pas une glyphe: on la loge dans la meme pastille de 40px
    // que les autres tableaux pour que les lignes s'alignent.
    $h.="<span class='awc-ico' style='background:rgba(38,50,56,.06);padding:6px'>"
       ."<img src='".htmlspecialchars($icon)."' style='max-width:100%;max-height:100%'></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars($categoryname)."</span>";
    if($items!==""){ $h.="<div class='awc-meta'>$items</div>"; }
    $h.="</span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-mono'>#$rowid</span></td>";
    $h.="<td style='width:1%' nowrap><div class='awc-acts'>";
    if($canWrite){
        $h.="<a class='awc-act awc-act-danger' title=\"{unlink}\" href=\"javascript:blur();\" "
           ."OnClick=\"Loadjs('$page?unlink-category=$rowid')\"><i class='fas fa-link-slash'></i></a>";
    }else{ $h.="&nbsp;"; }
    $h.="</div></td></tr>";
    return $h;
}

/** ufdbg_toolbar — les boutons au-dessus d'un tableau (on est dans une MODALE: pas de TINY). */
function ufdbg_toolbar($buttons){
    $h="<div class='awc-search' style='margin-top:0'><div class='awc-search-actions'>";
    foreach($buttons as $b){
        $h.="<a class='awc-btn' href=\"javascript:blur();\" OnClick=\"".$b[0]."\">"
           ."<i class='".$b[1]."'></i> ".$b[2]."</a>";
    }
    return $h."</div></div>";
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $canWrite=ufdbg_require_admin();

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT ID,groupname,enabled FROM webfilter_blkgp ORDER BY groupname");
    if(!$q->ok){
        // Le message SQLite nomme tables et colonnes: journal, pas ecran.
        writelogs("webfilter_blkgp: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{database_error}"));
        return;
    }
    if(!is_array($results)){ $results=array(); }

    // DEUX requetes groupees au lieu de deux par ligne. L'ancienne version comptait les
    // categories d'un groupe a l'interieur de la boucle: vingt groupes coutaient vingt
    // allers-retours pour une information que le moteur rend en une passe.
    $counts=array(); $used=array();
    $rows=$q->QUERY_SQL("SELECT webfilter_blkid, COUNT(category) AS n FROM webfilter_blkcnt GROUP BY webfilter_blkid");
    if(is_array($rows)){ foreach($rows as $r){ $counts[intval($r["webfilter_blkid"])]=intval($r["n"]); } }
    $rows=$q->QUERY_SQL("SELECT webfilter_blkid, COUNT(*) AS n FROM webfilter_blklnk GROUP BY webfilter_blkid");
    if(is_array($rows)){ foreach($rows as $r){ $used[intval($r["webfilter_blkid"])]=intval($r["n"]); } }

    $buttons=array();
    if($canWrite){ $buttons[]=array("Loadjs('$page?new-group-js=yes');","fas fa-plus","{new_group}"); }
    $buttons[]=array("LoadAjax('categories-group-main-div','$page?table=yes');","fas fa-rotate","{refresh}");

    $html=array();
    $html[]=ufdbg_toolbar($buttons);
    $html[]=awc_search_bar("ufdbg-search","{search}");
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{groups2}</th>";
    $html[]="<th class='center' style='width:1%'>{categories}</th>";
    $html[]="<th class='center' style='width:1%'>{rules}</th>";
    $html[]="<th class='center' style='width:1%'>{enabled}</th>";
    $html[]="<th style='width:1%;text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ufdbg-rows'>";

    $n=0;
    foreach($results as $ligne){
        $ID=intval($ligne["ID"]);
        $enabled=(intval($ligne["enabled"])==1);
        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?group-enable=$ID')",null,"AsDansGuardianAdministrator");
        $html[]=ufdbg_group_row($ID,(string)$ligne["groupname"],$enabled,
            isset($counts[$ID])?$counts[$ID]:0,
            isset($used[$ID])?$used[$ID]:0,
            $check,$canWrite);
        $n++;
    }
    if($n==0){ $html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>"; }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n {groups2}</span>"
           ."<span class='awc-pg' id='ufdbg-pager'></span></div>";
    $html[]="</div></div>";
    // ICON_SCRIPTS_COMPILE() draine $GLOBALS["TPL_ICON_SCRIPTS"] ET la pile d'instance:
    // c'est ce que reclame tout tableau contenant un icon_check().
    $html[]="<script>if(typeof NoSpinner==='function'){NoSpinner();}\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    // Pagination et filtre remplacent le <tfoot><ul class='pagination'> qu'aucun
    // .footable() n'initialisait: il etait rendu et restait vide en toutes circonstances.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ufdbg-rows","ufdbg-pager")
        .awc_filter_js("ufdbg-search","ufdbg-rows","ufdbg-pager");
}

function categories_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["categories-table"]);
    $canWrite=ufdbg_require_admin();

    $catz=new mysql_catz();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL_PARAMS("SELECT ID,category FROM webfilter_blkcnt WHERE webfilter_blkid=?",array($ID));
    if(!$q->ok){
        writelogs("webfilter_blkcnt: ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{database_error}"));
        return;
    }
    if(!is_array($results)){ $results=array(); }

    // unserialize() rend false sur un reglage vide -- l'etat normal d'une appliance qui
    // n'a pas encore telecharge de base. $UfdbMasterCache[...] sur un booleen leve un
    // avertissement PHP 8 a chaque ligne.
    $UfdbMasterCache=@unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    if(!is_array($UfdbMasterCache)){ $UfdbMasterCache=array(); }
    $qp=new postgres_sql();

    $buttons=array();
    if($canWrite){
        $buttons[]=array("Loadjs('fw.ufdb.rules.categories.php?category-add-js=yes&groupid=$ID');",
            "fas fa-plus","{link_categories}");
    }

    $html=array();
    $html[]=ufdbg_toolbar($buttons);
    $html[]=awc_search_bar("ufdbgc-$ID-search","{search}");
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{categories}</th>";
    $html[]="<th class='center' style='width:1%'>ID</th>";
    $html[]="<th style='width:1%;text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ufdbgc-$ID-rows'>";

    $n=0;
    foreach($results as $ligne){
        $rowid=intval($ligne["ID"]);
        $categoryid=intval($ligne["category"]);
        $categoryname=$catz->CategoryIntToStr($categoryid);

        // postgres_sql n'offre pas de variante parametree; $categoryid est deja passe
        // par intval(), donc l'interpolation ne peut porter qu'un entier.
        $ligne2=$qp->mysqli_fetch_array("SELECT category_icon FROM personal_categories WHERE category_id = $categoryid");
        $category_icon=(is_array($ligne2) && isset($ligne2["category_icon"]))?(string)$ligne2["category_icon"]:"";
        if($category_icon==""){ $category_icon="img/20-categories-personnal.png"; }

        // Le nombre d'entrees, une seule fois. L'ancien code composait « 12 340 {items} »
        // puis testait « if($items>0) » sur cette CHAINE: en PHP 8 la comparaison devient
        // lexicographique, elle est vraie, et le libelle sortait double -- « 12 340 Items
        // Records ». Ici on garde le nombre, et le mot est ajoute une fois.
        $items="";
        $official=isset($UfdbMasterCache[$categoryid]["official_category"])?intval($UfdbMasterCache[$categoryid]["official_category"]):0;
        $free=isset($UfdbMasterCache[$categoryid]["free_category"])?intval($UfdbMasterCache[$categoryid]["free_category"]):0;
        if(($official==1 || $free==1) && isset($UfdbMasterCache[$categoryid]["items"])){
            $count=intval($UfdbMasterCache[$categoryid]["items"]);
            if($count>0){ $items=$tpl->FormatNumber($count)." {records}"; }
        }

        $html[]=ufdbg_category_row($rowid,$categoryname,$category_icon,$items,$canWrite);
        $n++;
    }
    if($n==0){ $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>"; }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n / $n {categories}</span>"
           ."<span class='awc-pg' id='ufdbgc-$ID-pager'></span></div>";
    $html[]="</div></div>";
    $html[]="<script>if(typeof NoSpinner==='function'){NoSpinner();}\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ufdbgc-$ID-rows","ufdbgc-$ID-pager")
        .awc_filter_js("ufdbgc-$ID-search","ufdbgc-$ID-rows","ufdbgc-$ID-pager");
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}