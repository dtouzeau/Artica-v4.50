<?php
//Artica Service Pack 8 - Sep 2026
// Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
// INCONDITIONNEL: awc_table_style(), awc_pager_js() et siem_tiny_filters() viennent
// de la. Place dans le bootstrap conditionnel ci-dessus, l'include ne s'executerait
// jamais (class.sockets.inc est deja chargee par class.template-admin.inc) et la page
// mourrait sur "Call to undefined function awc_table_style()": une roue sans fin.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["recategorize"])){exit();}
if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["tab-host"])){tab_host();exit;}
if(isset($_GET["query"])){query();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-popup"])){query_popup();exit;}
if(isset($_POST["FROM"])){query_save();exit;}
if(isset($_GET["categories-list-js"])){category_list_js();exit;}
if(isset($_GET["categories-list"])){category_list();exit;}
if(isset($_GET["users-from-cat-js"])){users_category_js();exit;}
if(isset($_GET["users-from-cat-list"])){users_category_list();exit;}
if(isset($_GET["members-from-cat-js"])){members_category_js();exit;}
if(isset($_GET["members-from-cat-list"])){members_category_list();exit;}
if(isset($_GET["ipaddr-from-cat-js"])){ipaddr_category_js();exit;}
if(isset($_GET["ipaddr-from-cat-list"])){ipaddr_category_list();exit;}
if(isset($_GET["recategorize-js"])){recategorize_js();exit;}
page();

/* ------------------------------------------------------------------------- *
 *  Filtres d'en-tete (TINY)                                                   *
 * ------------------------------------------------------------------------- */

/**
 * Critere de tri courant.
 *
 * Liste blanche et non echappement: la valeur part directement dans un ORDER BY,
 * ou aucune preparation n'est possible (un nom de colonne ne se lie pas).
 */
function stsh_sort():string{
    $v=isset($_GET["sort"])?(string)$_GET["sort"]:"";
    return ($v=="hits")?"hits":"size";
}

/** Colonne de tri, sous sa forme SQL. */
function stsh_order_sql():string{
    return (stsh_sort()=="hits")?"hits DESC":"size DESC";
}

/**
 * Nombre maximal de lignes ramenees.
 *
 * Borne par une liste fermee: la pagination de ce tableau est cote navigateur,
 * donc chaque ligne demandee est une ligne reellement presente dans le DOM.
 */
function stsh_rows():int{
    $v=intval(isset($_GET["rows"])?$_GET["rows"]:0);
    if($v==100 || $v==1000){ return $v; }
    return 500;
}

/** Onglet courant, ramene aux trois valeurs connues. */
function stsh_kind($v):string{
    $v=(string)$v;
    if($v=="members"){return "members";}
    if($v=="sites"){return "sites";}
    return "categories";
}

/** Les filtres, tels qu'ils voyagent d'une requete a l'autre. */
function stsh_filter_qs():string{
    return "&sort=".stsh_sort()."&rows=".stsh_rows();
}

/** Declaration des champs du TINY. Le rendu appartient a siem_tiny_filters(). */
function stsh_tiny_fields():array{
    $fields=array();
    $fields[]=array("TYPE"=>"select","ID"=>"stsh-sort","LABEL"=>"{sort_by}",
        "VALUE"=>stsh_sort(),"JS"=>"StshFilter",
        "OPTIONS"=>array("size"=>"{size}","hits"=>"{hits}"));
    $fields[]=array("TYPE"=>"select","ID"=>"stsh-rows","LABEL"=>"{rows}",
        "VALUE"=>(string)stsh_rows(),"JS"=>"StshFilter","NARROW"=>true,
        "OPTIONS"=>array("100"=>"100","500"=>"500","1000"=>"1000"));
    return $fields;
}

/* ------------------------------------------------------------------------- *
 *  Recherche                                                                  *
 * ------------------------------------------------------------------------- */

/**
 * Terme de recherche, ramene aux caracteres qu'un nom de site, un identifiant,
 * une adresse IP ou une MAC peuvent contenir.
 *
 * LISTE BLANCHE, pas echappement. La valeur est concatenee dans un LIKE, et
 * pg_escape_string2() double les antislashs a la mode MySQL: sous PostgreSQL en
 * standard_conforming_strings (le defaut), un antislash n'echappe RIEN, si bien
 * qu'une apostrophe rendue "\'" ferme quand meme le litteral. Retirer les
 * caracteres qui n'ont aucun sens ici supprime la question.
 */
function stsh_search_term():string{
    $v=isset($_GET["search"])?(string)$_GET["search"]:"";
    $v=preg_replace('/[^A-Za-z0-9 ._:@\/*-]/','',$v);
    return trim($v);
}

/**
 * Motif LIKE issu du terme, ou "" si la recherche est vide.
 *
 * L'etoile de l'utilisateur devient le joker SQL, et le terme est encadre: sans
 * cela "google" ne repondait que sur un familysite valant EXACTEMENT google, et
 * il fallait savoir taper "*google*" pour obtenir une reponse.
 */
function stsh_like():string{
    $term=stsh_search_term();
    if($term==""){return "";}
    $term=str_replace("*","%",$term);
    if(strpos($term,"%")===false){ $term="%".$term."%"; }
    return $term;
}

/**
 * Alias de rafraichissement du tableau affiche.
 *
 * search_block() fabrique une fonction ss<t>() par bloc et la passe au repondeur
 * dans &function. Les filtres d'en-tete doivent rappeler CELLE de l'onglet
 * courant: l'alias est donc pose par le tableau lui-meme, a chaque rendu, et
 * suit donc le changement d'onglet sans que l'en-tete ait a le savoir.
 */
function stsh_refresh_alias():string{
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    if($function==""){return "";}
    return "window.StshRefresh=$function;";
}

/* ------------------------------------------------------------------------- *
 *  Briques de tableau (design awc-*)                                          *
 * ------------------------------------------------------------------------- */

/**
 * Separe le <style> de tete d'un fragment.
 *
 * search_block() renvoie sa feuille collee a son markup, et un <style> ne doit
 * jamais traverser _ENGINE_parse_body(): la regex #{(.+?)}# du moteur lit chaque
 * corps de regle CSS comme un {jeton} et declenche une recherche memcached par
 * regle.
 */
function stsh_split_style($html):array{
    if(preg_match('#^(\s*<style>.*?</style>)(.*)$#s',(string)$html,$m)){
        return array($m[1],$m[2]);
    }
    return array("",(string)$html);
}

/** Cellule d'identite: pastille d'icone, nom, ligne secondaire facultative. */
function stsh_ident_cell(string $icon,string $nameHtml,string $meta=""):string{
    $h ="<div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='$icon'></i></span>";
    $h.="<span><span class='awc-name'>$nameHtml</span>";
    if($meta<>""){ $h.="<div class='awc-meta'>$meta</div>"; }
    $h.="</span></div>";
    return $h;
}

/**
 * Nom affichable d'une categorie -- TOUJOURS une chaine.
 *
 * mysql_catz::CategoryIntToStr() n'a pas de return final: un identifiant absent
 * de sa table en dur, de memcached et (sous 250) des categories personnelles
 * tombe au bout de la fonction et renvoie NULL; une categorie personnelle sans
 * nom renvoie "". Passe a stsh_link(string ...), le NULL levait un TypeError qui
 * emportait tout l'onglet des categories. L'identifiant reste affiche pour que la
 * ligne soit reconnaissable -- et cherchable.
 */
function stsh_category_name($catz,$category_id):string{
    $name=trim((string)$catz->CategoryIntToStr($category_id));
    if($name==""){ $name="{unknown} #".intval($category_id); }
    return $name;
}

/** Lien d'identite ouvrant une fenetre. */
function stsh_link(string $label,string $js):string{
    return "<a href=\"javascript:blur();\" OnClick=\"$js\">".htmlspecialchars($label)."</a>";
}

/**
 * Tableau awc-* complet: carte, entete, lignes, etat vide et pied.
 *
 * $heads: une entree par colonne, array("LABEL"=>"{x}","RIGHT"=>true).
 * $rows: les <tr> deja construits, dans l'ordre d'affichage.
 */
function stsh_table(array $heads,array $rows,string $rowsId,string $pagerId,string $footLeft):string{
    $h=array();
    $h[]="<div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    foreach($heads as $head){
        $cls=(!empty($head["RIGHT"]))?" class='awc-right'":"";
        $h[]="<th$cls>".$head["LABEL"]."</th>";
    }
    $h[]="</tr></thead><tbody id='$rowsId'>";
    if(count($rows)==0){
        $h[]="<tr><td colspan='".count($heads)."'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $h[]=@implode("\n",$rows);
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>$footLeft</span><span class='awc-pg' id='$pagerId'></span></div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/**
 * Emission d'un fragment-tableau.
 *
 * La feuille de style et le JavaScript sont concatenes AUTOUR de
 * _ENGINE_parse_body(), et le pagineur vient APRES le tableau: il lit les <tr>,
 * qui doivent donc exister quand il s'execute.
 */
function stsh_echo_table(string $html,string $rowsId,string $pagerId,int $pagesize=50){
    $tpl=new template_admin();
    $js="<script>NoSpinner();\n".stsh_refresh_alias()."\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .$js
        .awc_pager_js($pagesize,$rowsId,$pagerId);
}

/* ------------------------------------------------------------------------- *
 *  Squelette de page                                                          *
 * ------------------------------------------------------------------------- */

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{in_short}","fas fa-chart-pie","{statscom_short_explain}",
        "$page?build-page=yes","statscom-short","progress-statscom-short",false,"table-statscom-short");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{in_short}",$html);
        echo $tpl->build_firewall();return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

/**
 * Corps de la page: les trois onglets, puis le TINY.
 *
 * Le TINY part APRES tabs_default(): celui-ci rend du markup ET un <script>, et
 * emettre le TINY avant reviendrait a cabler les boutons d'en-tete sur un DOM
 * que le gestionnaire d'onglets n'a pas encore construit.
 */
function build_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $array=array();
    $array["{categories}"]="$page?tab-host=categories";
    $array["{members}"]="$page?tab-host=members";
    $array["{familysite}"]="$page?tab-host=sites";

    $topbuttons=array();
    $topbuttons[]=array("StshFilter()",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{in_short}";
    $TINY["ICO"]="fas fa-chart-pie";
    $TINY["EXPL"]="{statscom_short_explain}";
    $TINY["URL"]="statscom-short";
    $TINY["BUTTONS"]=siem_tiny_filters(stsh_tiny_fields(),$tpl->table_buttons($topbuttons));
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

    echo siem_tiny_filters_style();
    echo "<div style='margin-top:10px'>";
    echo $tpl->tabs_default($array);
    echo "</div>";
    // Declare AVANT le premier onglet: les listes deroulantes du TINY sont
    // utilisables des que l'en-tete est pose, c'est-a-dire avant que le premier
    // tableau ait repondu. StshQS() est relue A CHAQUE recherche (voir
    // tab_host()), si bien qu'un changement de filtre n'a rien a reconstruire.
    echo "<script>".
        "function StshQS(){".
        "var s=document.getElementById('stsh-sort');var r=document.getElementById('stsh-rows');".
        "return '&sort='+(s?encodeURIComponent(s.value):'size')+'&rows='+(r?encodeURIComponent(r.value):'500');}".
        "function StshFilter(){if(typeof window.StshRefresh==='function'){window.StshRefresh();}}".
        "NoSpinner();$tiny</script>";
}

/**
 * Corps d'un onglet: uniquement sa barre de recherche.
 *
 * Les filtres d'en-tete sont injectes dans l'URL que search_block() fabrique,
 * sous forme d'APPEL et non de valeur: le bloc fige son urlToadd au rendu, donc
 * une valeur y serait celle du moment ou l'onglet a ete ouvert. En refermant la
 * chaine JavaScript autour de StshQS(), chaque recherche relit les listes
 * deroulantes -- et le terme deja tape survit au changement de filtre, ce qu'un
 * rechargement du bloc lui-meme ne permettrait pas (search_block() ne sait pas
 * reafficher une valeur initiale).
 */
function tab_host(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $kind=stsh_kind($_GET["tab-host"]);
    $live="'+(window.StshQS?StshQS():'')+'";
    list($css,$block)=stsh_split_style($tpl->search_block($page,"","","","&kind=$kind".$live));
    echo $css.$tpl->_ENGINE_parse_body("<div style='margin-top:10px'>".$block."</div>");
}

/* ------------------------------------------------------------------------- *
 *  Les trois tableaux d'onglet                                                *
 * ------------------------------------------------------------------------- */

function search(){
    // La fenetre « sites d'une categorie » a son propre search_block: elle n'est
    // pas un onglet, donc pas dans stsh_kind() (tab-host=catsites n'a aucun sens).
    if((string)(isset($_GET["kind"])?$_GET["kind"]:"")=="catsites"){ category_sites_search(); return; }
    $kind=stsh_kind(isset($_GET["kind"])?$_GET["kind"]:"");
    if($kind=="members"){ search_members(); return; }
    if($kind=="sites"){ search_sites(); return; }
    search_categories();
}

/**
 * Categories: volumetrie par categorie, avec les trois vues de detail.
 *
 * Le filtre porte sur le NOM de la categorie, qui n'existe pas en base -- la
 * table ne stocke qu'un entier, traduit par mysql_catz. Le tri et la coupe se
 * font donc en PHP, apres traduction; le nombre de categories se compte en
 * dizaines, la lecture complete ne coute rien.
 */
function search_categories(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $catz=new mysql_catz();
    $term=strtolower(stsh_search_term());
    $max=stsh_rows();
    $fq=stsh_filter_qs();

    $sql="SELECT category, SUM(hitstot) as hits, SUM(sizetot) as size FROM statscom_websites GROUP by category";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){ echo $tpl->_ENGINE_parse_body($tpl->div_error($q->mysql_error)); return; }

    $items=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $category_id=$ligne["category"];
        $cname=stsh_category_name($catz,$category_id);
        if($term!="" && strpos(strtolower($cname),$term)===false){ continue; }
        $items[]=array("id"=>$category_id,"name"=>$cname,
            "hits"=>floatval($ligne["hits"]),"size"=>floatval($ligne["size"]));
    }
    $total=count($items);
    $key=stsh_sort();
    usort($items,function($a,$b)use($key){
        if($a[$key]==$b[$key]){return 0;}
        return ($a[$key]<$b[$key])?1:-1;
    });
    $items=array_slice($items,0,$max);

    $rows=array();
    foreach($items as $it){
        $id=urlencode($it["id"]);
        $member=$tpl->icon_user("Loadjs('$page?members-from-cat-js=$id$fq')","AsWebStatisticsAdministrator");
        $ipaddr=$tpl->icon_nic("Loadjs('$page?ipaddr-from-cat-js=$id$fq')","AsWebStatisticsAdministrator");
        $users=$tpl->icon_group("Loadjs('$page?users-from-cat-js=$id$fq')","AsWebStatisticsAdministrator");
        $link=stsh_link($it["name"],"Loadjs('$page?categories-list-js=$id$fq')");

        $h ="<tr>";
        $h.="<td>".stsh_ident_cell("fas fa-folder-tree",$link)."</td>";
        $h.="<td class='center'><div class='awc-acts' style='opacity:1;justify-content:center'>$member</div></td>";
        $h.="<td class='center'><div class='awc-acts' style='opacity:1;justify-content:center'>$ipaddr</div></td>";
        $h.="<td class='center'><div class='awc-acts' style='opacity:1;justify-content:center'>$users</div></td>";
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($it["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($it["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $heads=array(
        array("LABEL"=>"{category}"),
        array("LABEL"=>"{members}"),
        array("LABEL"=>"{ipaddr}"),
        array("LABEL"=>"{details}"),
        array("LABEL"=>"{size}","RIGHT"=>true),
        array("LABEL"=>"{hits}","RIGHT"=>true));
    $foot="{showing} ".count($rows)." / $total";
    stsh_echo_table(stsh_table($heads,$rows,"stsh-cat-rows","stsh-cat-pager",$foot),
        "stsh-cat-rows","stsh-cat-pager");
}

/** Membres: volumetrie par utilisateur, adresse et MAC. */
function search_members(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideMacs'));
    $hideUnkownMembers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideUnkownMembers'));
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    $max=stsh_rows();

    // Les trois colonnes n'ont pas le meme type: mac et ipaddr sont des types
    // reseau PostgreSQL, qui ne se comparent pas a un motif texte sans transtypage
    // explicite. L'ancienne ecriture "mac::text LIKE::text '...'" n'etait pas du
    // SQL valide et faisait echouer TOUTE recherche sur cet onglet.
    $where="";
    $like=stsh_like();
    if($like<>""){
        $where=" WHERE (username LIKE '$like') OR (mac::text LIKE '$like') OR (ipaddr::text LIKE '$like')";
    }
    $order=stsh_order_sql();
    $sql="SELECT username,ipaddr,mac, SUM(hits) as hits, SUM(size) as size FROM statscom_husers$where"
        ." GROUP by username,ipaddr,mac ORDER by $order LIMIT $max";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){ echo $tpl->_ENGINE_parse_body($tpl->div_error($q->mysql_error)); return; }

    $rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $username=trim((string)$ligne["username"]);
        $ipaddr=trim((string)$ligne["ipaddr"]);
        $mac=trim((string)$ligne["mac"]);
        $shownip=$ipaddr;
        if($resolveIP2HOST==1 && $ipaddr<>""){
            $host=new ip2host($ipaddr);
            $shownip=$host->output;
        }
        if($username==""){
            // Pentest 2026-11-04 : ipaddr (donnee externe) affiche sans encodage (XSS stockee).
            $username=($hideUnkownMembers==1)?req_html($ipaddr):"{unknown}";
            $label=$username;
        }else{
            $label=htmlspecialchars($username);
        }

        $h ="<tr>";
        $h.="<td>".stsh_ident_cell("fas fa-user",$label)."</td>";
        $h.="<td class='awc-mono awc-nowrap'>".htmlspecialchars($shownip)."</td>";
        if($hideMacs==0){ $h.="<td class='awc-mono awc-nowrap'>".htmlspecialchars($mac)."</td>"; }
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($ligne["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($ligne["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $heads=array(array("LABEL"=>"{username}"),array("LABEL"=>"{ipaddr}"));
    if($hideMacs==0){ $heads[]=array("LABEL"=>"{MAC}"); }
    $heads[]=array("LABEL"=>"{size}","RIGHT"=>true);
    $heads[]=array("LABEL"=>"{hits}","RIGHT"=>true);
    $foot="{showing} ".count($rows)." ({limit} $max)";
    stsh_echo_table(stsh_table($heads,$rows,"stsh-mbr-rows","stsh-mbr-pager",$foot),
        "stsh-mbr-rows","stsh-mbr-pager");
}

/** Sites globaux: volumetrie par familysite. */
function search_sites(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $max=stsh_rows();

    $where="";
    $like=stsh_like();
    if($like<>""){ $where=" WHERE (familysite LIKE '$like')"; }
    $order=stsh_order_sql();
    $sql="SELECT familysite, SUM(hitstot) as hits, SUM(sizetot) as size FROM statscom_websites$where"
        ." GROUP by familysite ORDER by $order LIMIT $max";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){ echo $tpl->_ENGINE_parse_body($tpl->div_error($q->mysql_error)); return; }

    $rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $familysite=trim((string)$ligne["familysite"]);
        $h ="<tr>";
        $h.="<td>".stsh_ident_cell("fas fa-globe",htmlspecialchars($familysite))."</td>";
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($ligne["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($ligne["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $heads=array(array("LABEL"=>"{familysite}"),
        array("LABEL"=>"{size}","RIGHT"=>true),
        array("LABEL"=>"{hits}","RIGHT"=>true));
    $foot="{showing} ".count($rows)." ({limit} $max)";
    stsh_echo_table(stsh_table($heads,$rows,"stsh-sit-rows","stsh-sit-pager",$foot),
        "stsh-sit-rows","stsh-sit-pager");
}

/* ------------------------------------------------------------------------- *
 *  Fenetres de detail d'une categorie                                         *
 * ------------------------------------------------------------------------- */

function category_list_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $catz=new mysql_catz();
    $category_id=$_GET["categories-list-js"];
    $tpl->js_dialog2(stsh_category_name($catz,$category_id),
        "$page?categories-list=".urlencode($category_id).stsh_filter_qs(),1050);
}
function users_category_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $catz=new mysql_catz();
    $category_id=$_GET["users-from-cat-js"];
    $tpl->js_dialog2(stsh_category_name($catz,$category_id).": {members}/{MAC}/{ipaddr}",
        "$page?users-from-cat-list=".urlencode($category_id).stsh_filter_qs(),1050);
}
function members_category_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $catz=new mysql_catz();
    $category_id=$_GET["members-from-cat-js"];
    $tpl->js_dialog2(stsh_category_name($catz,$category_id).": {members}",
        "$page?members-from-cat-list=".urlencode($category_id).stsh_filter_qs(),1050);
}
function ipaddr_category_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $catz=new mysql_catz();
    $category_id=$_GET["ipaddr-from-cat-js"];
    $tpl->js_dialog2(stsh_category_name($catz,$category_id).": {ipaddr}",
        "$page?ipaddr-from-cat-list=".urlencode($category_id).stsh_filter_qs(),1050);
}

/**
 * Bouton « Reanalyser »: confirmation, puis GET /statscom/recategorize/<id> sur
 * articarest, suivi par la barre de progression dans la fenetre de la categorie.
 *
 * Le demon ecrit recategorize-<id>.progress et .log (squidstats/StatsCom,
 * RecategorizeCategory.go). L'ancien chemin -- framework/statscom.php puis
 * exec.re-categorize.php --recategorize-category -- ne deplacait aucun site: la
 * categorie relue par mysql_catz::GET_CATEGORIES() valait toujours 0.
 *
 * Le droit est verifie ICI: framework_buildjs() ne connait pas
 * AsWebStatisticsAdministrator (IsSecurity() repond true a tout nom qu'il ne
 * traite pas), et le bouton n'exige que le droit de consultation.
 */
function recategorize_js(){
    $catz=new mysql_catz();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["recategorize-js"]);
    $users=new usersMenus();
    if(!$users->AsWebStatisticsAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return;
    }
    // A la fin: la fenetre est rouverte sur les sites restants, et l'onglet des
    // categories derriere elle est rafraichi (ses totaux ont bouge).
    $after="dialogInstance2.close();Loadjs('$page?categories-list-js=$category_id');"
        ."if(typeof window.StshRefresh==='function'){window.StshRefresh();}";
    $jsafter=$tpl->framework_buildjs("/statscom/recategorize/$category_id",
        "recategorize-$category_id.progress","recategorize-$category_id.log",
        "recategorize-$category_id",$after);
    $category_name=stsh_category_name($catz,$category_id);
    $tpl->js_confirm_execute("{rescan_database}: $category_name<hr>{recategorize_category_ask}",
        "recategorize",$category_id,$jsafter);
}

/**
 * Requete commune aux trois vues « par categorie ».
 *
 * Les trois faisaient la meme double agregation a un GROUP BY pres. Le sous-select
 * agrege par jour puis le select englobant cumule, ce qui evite de compter deux
 * fois un couple site/jour.
 */
function stsh_category_rows(string $groupby,int $category_id,&$error){
    $error="";
    $q=new postgres_sql();
    $order=stsh_order_sql();
    $max=stsh_rows();
    $cols="statscom_days.".str_replace(",",",statscom_days.",$groupby);
    $sqlA="SELECT $cols,SUM(statscom_days.hits) as hits,SUM(statscom_days.size) as size"
        ." FROM statscom_days,statscom_websites"
        ." WHERE statscom_days.siteid=statscom_websites.siteid AND statscom_websites.category=$category_id"
        ." GROUP BY $cols";
    $sql="SELECT $groupby,SUM(hits) as hits,SUM(size) as size FROM ($sqlA) as t"
        ." GROUP BY $groupby ORDER BY $order LIMIT $max";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){ $error=$q->mysql_error; return array(); }
    $out=array();
    while ($ligne = pg_fetch_assoc($results)) { $out[]=$ligne; }
    return $out;
}

/** En-tete de fenetre: le bouton de reanalyse de la categorie. */
function stsh_category_head(int $category_id):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if($category_id<=0){ return ""; }
    $h=array();
    $h[]="<div id='recategorize-$category_id'></div>";
    $h[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom:8px'>";
    $h[]=$tpl->button_label_table("{rescan_database}",
        "Loadjs('$page?recategorize-js=$category_id')","fa fa-repeat","AllowViewStatistics");
    $h[]="</div>";
    return @implode("\n",$h);
}

function ipaddr_category_list(){
    $tpl=new template_admin();
    $category_id=intval($_GET["ipaddr-from-cat-list"]);
    $error="";
    $lines=stsh_category_rows("ipaddr",$category_id,$error);
    if($error<>""){ echo $tpl->_ENGINE_parse_body($tpl->div_error($error)); return; }

    $rows=array();
    foreach($lines as $ligne){
        $value=trim((string)$ligne["ipaddr"]);
        if($value==""){$value="{unknown}";}else{$value=htmlspecialchars($value);}
        $h ="<tr>";
        $h.="<td>".stsh_ident_cell("fas fa-desktop",$value)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($ligne["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($ligne["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }
    $heads=array(array("LABEL"=>"{ipaddr}"),
        array("LABEL"=>"{size}","RIGHT"=>true),array("LABEL"=>"{hits}","RIGHT"=>true));
    stsh_echo_table(stsh_table($heads,$rows,"stsh-cip-rows","stsh-cip-pager","{showing} ".count($rows)),
        "stsh-cip-rows","stsh-cip-pager",25);
}

function members_category_list(){
    $tpl=new template_admin();
    $category_id=intval($_GET["members-from-cat-list"]);
    $error="";
    $lines=stsh_category_rows("username",$category_id,$error);
    if($error<>""){ echo $tpl->_ENGINE_parse_body($tpl->div_error($error)); return; }

    $rows=array();
    foreach($lines as $ligne){
        $value=trim((string)$ligne["username"]);
        if($value==""){$value="{unknown}";}else{$value=htmlspecialchars($value);}
        $h ="<tr>";
        $h.="<td>".stsh_ident_cell("fas fa-user",$value)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($ligne["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($ligne["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }
    $heads=array(array("LABEL"=>"{members}"),
        array("LABEL"=>"{size}","RIGHT"=>true),array("LABEL"=>"{hits}","RIGHT"=>true));
    stsh_echo_table(stsh_table($heads,$rows,"stsh-cmb-rows","stsh-cmb-pager","{showing} ".count($rows)),
        "stsh-cmb-rows","stsh-cmb-pager",25);
}

function users_category_list(){
    $tpl=new template_admin();
    $category_id=intval($_GET["users-from-cat-list"]);
    $error="";
    $lines=stsh_category_rows("username,ipaddr,mac",$category_id,$error);
    if($error<>""){ echo $tpl->_ENGINE_parse_body($tpl->div_error($error)); return; }

    $hideMacs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO('statscomHideMacs'));
    $rows=array();
    foreach($lines as $ligne){
        $username=trim((string)$ligne["username"]);
        $ipaddr=trim((string)$ligne["ipaddr"]);
        $mac=trim((string)$ligne["mac"]);
        $label=($username=="")?"{unknown}":htmlspecialchars($username);
        $h ="<tr>";
        $h.="<td>".stsh_ident_cell("fas fa-user",$label)."</td>";
        $h.="<td class='awc-mono awc-nowrap'>".htmlspecialchars($ipaddr)."</td>";
        if($hideMacs==0){ $h.="<td class='awc-mono awc-nowrap'>".htmlspecialchars($mac)."</td>"; }
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($ligne["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($ligne["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }
    $heads=array(array("LABEL"=>"{members}"),array("LABEL"=>"{ipaddr}"));
    if($hideMacs==0){ $heads[]=array("LABEL"=>"{MAC}"); }
    $heads[]=array("LABEL"=>"{size}","RIGHT"=>true);
    $heads[]=array("LABEL"=>"{hits}","RIGHT"=>true);
    stsh_echo_table(stsh_table($heads,$rows,"stsh-cus-rows","stsh-cus-pager","{showing} ".count($rows)),
        "stsh-cus-rows","stsh-cus-pager",25);
}

/* ------------------------------------------------------------------------- *
 *  Sites d'une categorie (?categories-list=): recherche joker / regex,        *
 *  tri et pagination COTE SERVEUR                                             *
 * ------------------------------------------------------------------------- */

/**
 * Lignes par page de la fenetre.
 *
 * Une fonction et non un define(): le define() serait execute a son rang dans le
 * fichier, c'est-a-dire jamais -- l'aiguillage en tete fait exit avant. Seules
 * les declarations de fonctions sont remontees.
 */
function stsh_csi_psize():int{ return 25; }

/** Mode de recherche: "re" (expression reguliere) ou "glob" (joker, par defaut). */
function stsh_csi_mode():string{
    return ((string)(isset($_GET["mode"])?$_GET["mode"]:"")=="re")?"re":"glob";
}

/** Colonne de tri, liste blanche: elle part dans un ORDER BY. */
function stsh_csi_order():string{
    return ((string)(isset($_GET["order"])?$_GET["order"]:"")=="hits")?"hits":"size";
}

/** Sens du tri, liste blanche. Decroissant par defaut: les plus gros d'abord. */
function stsh_csi_dir():string{
    return (strtolower((string)(isset($_GET["dir"])?$_GET["dir"]:""))=="asc")?"asc":"desc";
}

/**
 * Terme saisi, BRUT.
 *
 * Pas la liste blanche de stsh_search_term(): une expression reguliere a besoin
 * de \ ( ) [ ] { } | ^ $ + ?, soit exactement ce que celle-ci retire. La surete
 * vient de stsh_pg_literal(), pas d'un filtrage du contenu. Seuls le NUL (refuse
 * par PostgreSQL dans un litteral) et la longueur sont bornes.
 */
function stsh_csi_term():string{
    $v=(string)(isset($_GET["search"])?$_GET["search"]:"");
    $v=str_replace("\0","",$v);
    return substr(trim($v),0,256);
}

/**
 * Litteral de chaine PostgreSQL, sur quel que soit standard_conforming_strings.
 *
 * La forme E'...' traite TOUJOURS l'antislash comme un echappement, donc doubler
 * \ puis ' suffit et ne depend d'aucun reglage serveur. C'est precisement ce qui
 * manque a pg_escape_string2() (voir stsh_search_term()): un '...' ordinaire lit
 * l'antislash litteralement en standard_conforming_strings=on, et "\'" y ferme
 * la chaine. postgres_sql n'offre pas de requete preparee.
 */
function stsh_pg_literal(string $s):string{
    $s=str_replace("\0","",$s);
    return "E'".str_replace(array("\\","'"),array("\\\\","''"),$s)."'";
}

/**
 * Joker -> motif LIKE.
 *
 * * = n'importe quelle suite, ? = un caractere; % _ et \ saisis sont pris
 * litteralement. Sans joker, le terme est cherche PARTOUT dans le nom (comme les
 * onglets, stsh_like()); avec un joker, le motif est ancre tel que tape:
 * "*.fr" = se termine par .fr, "mail.*" = commence par mail.
 */
function stsh_glob_to_like(string $term):string{
    $esc=str_replace(array("\\","%","_"),array("\\\\","\\%","\\_"),$term);
    $like=str_replace(array("*","?"),array("%","_"),$esc);
    if(strpos($term,"*")===false && strpos($term,"?")===false){ $like="%".$like."%"; }
    return $like;
}

/** Condition SQL sur sitename, ou "" sans terme. Insensible a la casse. */
function stsh_csi_filter_sql(string $term,string $mode):string{
    if($term==""){ return ""; }
    if($mode=="re"){ return "sitename ~* ".stsh_pg_literal($term); }
    return "sitename ILIKE ".stsh_pg_literal(stsh_glob_to_like($term));
}

/** Nombre de sites repondant a $where (une ligne par site, comme la page). */
function stsh_csi_count_sql(string $where):string{
    return "SELECT COUNT(*) AS n FROM"
        ." (SELECT 1 FROM statscom_websites WHERE $where GROUP BY sitename,siteid) AS t";
}

/**
 * Une page de sites, triee sur l'ensemble. $order et $dir sont re-filtres ici
 * parce qu'ils partent dans l'ORDER BY, ou rien ne se lie. hits et size y
 * designent les ALIAS agreges, pas les colonnes homonymes de la table: pour un
 * nom nu, PostgreSQL resout l'ORDER BY sur la liste de sortie d'abord.
 */
function stsh_csi_page_sql(string $where,string $order,string $dir,int $psize,int $offset):string{
    $col=($order=="hits")?"hits":"size";
    $sens=($dir=="asc")?"ASC":"DESC";
    return "SELECT siteid,sitename,COALESCE(SUM(hitstot),0) AS hits,COALESCE(SUM(sizetot),0) AS size"
        ." FROM statscom_websites WHERE $where GROUP BY sitename,siteid"
        ." ORDER BY $col $sens, sitename ASC, siteid ASC LIMIT ".intval($psize)." OFFSET ".intval($offset);
}

/**
 * Etat de la fenetre, cote navigateur.
 *
 * search_block() fige son urlToadd au rendu; le mode, la colonne, le sens et la
 * page y sont donc injectes sous forme d'APPEL a StshCsiQS() (meme principe que
 * tab_host()). La page est a usage unique: StshCsiQS() la remet a 1 en la lisant,
 * si bien qu'un nouveau terme (Entree, Go!) repart toujours de la premiere page,
 * et seul un clic du pager en demande une autre.
 */
function stsh_csi_state_js(string $order):string{
    $order=($order=="hits")?"hits":"size";
    return "<script>"
        ."window.StshCsi={mode:'glob',order:'$order',dir:'desc',page:1};\n"
        ."function StshCsiQS(){var S=window.StshCsi||{};var p=parseInt(S.page,10)||1;S.page=1;"
        ."return '&mode='+(S.mode=='re'?'re':'glob')+'&order='+(S.order=='hits'?'hits':'size')"
        ."+'&dir='+(S.dir=='asc'?'asc':'desc')+'&page='+p;}\n"
        ."function StshCsiMode(m,a){var S=window.StshCsi;if(!S){return;}S.mode=(m=='re')?'re':'glob';S.page=1;"
        ."var t=document.getElementById('SearchBlockDropDownTitle');if(t&&a){t.innerHTML=a.innerHTML;}}\n"
        ."</script>";
}

/**
 * Cablage des en-tetes triables et du pager d'un rendu.
 *
 * Tout passe par la fonction de recherche de search_block ($function, deja
 * filtree [A-Za-z0-9_]): elle relit le terme ET StshCsiQS(), donc le tri garde
 * le filtre et la page garde le tri. Premier clic sur une colonne = decroissant
 * (les plus gros / les plus demandes d'abord), clic suivant = inversion.
 * L'etat est resynchronise sur ce que le serveur a REELLEMENT applique.
 */
function stsh_csi_nav_js(string $function,string $order,string $dir):string{
    if($function==""){ return ""; }
    return "<script>(function(){\n"
        ."var S=window.StshCsi; if(!S){return;}\n"
        ."S.order='".(($order=="hits")?"hits":"size")."'; S.dir='".(($dir=="asc")?"asc":"desc")."';\n"
        ."function go(){ if(typeof window.$function==='function'){ window.$function(); } }\n"
        ."var ths=document.querySelectorAll('.stsh-csi-th');\n"
        ."for(var i=0;i<ths.length;i++){ ths[i].onclick=function(){\n"
        ."  var k=this.getAttribute('data-order');\n"
        ."  if(S.order==k){ S.dir=(S.dir=='asc')?'desc':'asc'; }else{ S.order=k; S.dir='desc'; }\n"
        ."  S.page=1; go(); return false; }; }\n"
        ."var as=document.querySelectorAll('.stsh-csi-pg');\n"
        ."for(var j=0;j<as.length;j++){ as[j].onclick=function(){\n"
        ."  if(this.className.indexOf('awc-pg-off')>=0 || this.className.indexOf('awc-pg-cur')>=0){ return false; }\n"
        ."  S.page=parseInt(this.getAttribute('data-p'),10)||1; go(); return false; }; }\n"
        ."})();</script>";
}

/**
 * En-tete triable. La fleche est un <span> et non un <i class='fa ...'>: Font
 * Awesome remplacerait ce dernier par un <svg> (cf. awc_sort_js()).
 */
function stsh_csi_th(string $key,string $label,string $order,string $dir):string{
    $arrow="";
    if($key==$order){
        $arrow="<span style='margin-left:6px;font-size:10px'>".(($dir=="asc")?"&#9650;":"&#9660;")."</span>";
    }
    return "<th class='awc-right awc-nowrap'><a class='stsh-csi-th' data-order='$key' href=\"javascript:blur();\""
        ." title=\"{click_to_sort}\" style='color:inherit;text-decoration:none'>$label$arrow</a></th>";
}

/** Fenetre « sites d'une categorie »: bouton de reanalyse, recherche, resultats. */
function category_list(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $category_id=intval($_GET["categories-list"]);

    // Le menu du search_block choisit le mode. %s devient le nom de sa fonction
    // de recherche; "return false" empeche le href="#" de toucher a l'URL.
    $options=array("DROPDOWN"=>array(
        "TITLE"=>"{wildcard}",
        "CONTENT"=>array(
            "{wildcard}"=>"StshCsiMode('glob',this);%s();return false;",
            "{regex}"=>"StshCsiMode('re',this);%s();return false;")));
    $live="'+(window.StshCsiQS?StshCsiQS():'')+'";
    list($css,$block)=stsh_split_style($tpl->search_block($page,"","","",
        "&kind=catsites&catid=$category_id".$live,$options));

    // L'etat est pose AVANT le search_block, dont la recherche automatique part a
    // 800 ms; le tri initial reprend celui du filtre d'en-tete de la page.
    echo $css.stsh_csi_state_js(stsh_sort())
        .$tpl->_ENGINE_parse_body(stsh_category_head($category_id)
            ."<div style='margin-top:10px'>".$block."</div>");
}

/**
 * Repondeur du search_block de la fenetre (&kind=catsites).
 *
 * Tri et pagination dans PostgreSQL: le tri porte sur TOUS les sites de la
 * categorie, pas sur les lignes deja chargees -- l'ancien tableau FooTable
 * comparait "1.2 GB" et "300 MB" comme du texte. Le sitename puis le siteid
 * departagent les ex aequo, sans quoi une ligne pourrait changer de page d'un
 * clic a l'autre.
 */
function category_sites_search(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $category_id=intval(isset($_GET["catid"])?$_GET["catid"]:0);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $mode=stsh_csi_mode();
    $order=stsh_csi_order();
    $dir=stsh_csi_dir();
    $term=stsh_csi_term();
    $psize=stsh_csi_psize();

    $filter=stsh_csi_filter_sql($term,$mode);
    $where="category=$category_id".(($filter<>"")?" AND $filter":"");

    $results=$q->QUERY_SQL(stsh_csi_count_sql($where));
    if(!$q->ok){ stsh_csi_error((string)$q->mysql_error,$mode); return; }
    $ligne=pg_fetch_assoc($results);
    $found=intval(is_array($ligne)?$ligne["n"]:0);

    $total=$found;
    if($filter<>""){
        $results=$q->QUERY_SQL(stsh_csi_count_sql("category=$category_id"));
        if($q->ok){ $ligne=pg_fetch_assoc($results); $total=intval(is_array($ligne)?$ligne["n"]:0); }
    }

    $pages=intval(ceil($found/$psize)); if($pages<1){ $pages=1; }
    $pageNo=intval(isset($_GET["page"])?$_GET["page"]:1);
    if($pageNo<1){ $pageNo=1; } if($pageNo>$pages){ $pageNo=$pages; }
    $offset=($pageNo-1)*$psize;

    $results=$q->QUERY_SQL(stsh_csi_page_sql($where,$order,$dir,$psize,$offset));
    if(!$q->ok){ stsh_csi_error((string)$q->mysql_error,$mode); return; }

    $rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $siteid=intval($ligne["siteid"]);
        $sitename=trim((string)$ligne["sitename"]);
        $link=stsh_link($sitename,"Loadjs('fw.statscom.browse.website.php?siteid=$siteid&total=yes')");
        $h ="<tr>";
        $h.="<td>".stsh_ident_cell("fas fa-globe",$link)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($ligne["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($ligne["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $foot="{showing} 0 / 0";
    if($found>0){ $foot="{showing} ".($offset+1)."-".($offset+count($rows))." / ".$tpl->FormatNumber($found); }
    if($total!=$found){ $foot.=" (".$tpl->FormatNumber($total).")"; }

    $h=array();
    $h[]="<div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr><th>{website}</th>";
    $h[]=stsh_csi_th("size","{size}",$order,$dir);
    $h[]=stsh_csi_th("hits","{hits}",$order,$dir);
    $h[]="</tr></thead><tbody id='stsh-csi-rows'>";
    if(count($rows)==0){
        $h[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $h[]=@implode("\n",$rows);
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>$foot</span>"
        ."<span class='awc-pg'>".awc_pager_server($pageNo,$pages,"stsh-csi-pg")."</span></div>";
    $h[]="</div>";

    // CSS et JS AUTOUR de l'analyse: _ENGINE_parse_body() lirait leurs {...}
    // comme des jetons. Pas de stsh_refresh_alias() ici: le {refresh} de l'en-tete
    // doit continuer a recharger l'onglet, pas cette fenetre.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$h))
        ."<script>NoSpinner();</script>"
        .stsh_csi_nav_js($function,$order,$dir);
}

/**
 * Erreur de requete. En mode regex, c'est presque toujours le motif que
 * PostgreSQL refuse ("invalid regular expression: ..."): le dire en ces termes
 * plutot que d'afficher une erreur SQL brute.
 */
function stsh_csi_error(string $error,string $mode){
    $tpl=new template_admin();
    $error=trim(preg_replace('/^\s*ERROR:\s*/i','',$error));
    if($mode=="re" && stripos($error,"regular expression")!==false){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{invalid_pattern}"))
            ."<div class='awc-muted' style='margin:-6px 0 10px'>".htmlspecialchars($error)."</div>"
            ."<script>NoSpinner();</script>";
        return;
    }
    echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($error)))."<script>NoSpinner();</script>";
}

/* ------------------------------------------------------------------------- *
 *  Constructeur de requete detaillee (?query=yes)                             *
 * ------------------------------------------------------------------------- */

function query_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{build_query}","$page?query-popup=yes",650);
}

function query_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    if(!$q->CREATE_STATSCOM()) {
        echo $tpl->div_error($q->mysql_error);
    }
    $results=$q->QUERY_SQL("SELECT zdate FROM statscom_days GROUP BY zdate ORDER BY zdate DESC");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $DATES=array();
    $DATES[date("Y-m-d")]=$tpl->time_to_date(time());
    $DATES[date("Y-m-d",strtotime( '-1 days' ))]=$tpl->time_to_date(strtotime( '-1 days' ));

    while ($ligne = pg_fetch_assoc($results)) {
        $time=strtotime($ligne["zdate"]);
        $DATES[$ligne["zdate"]]=$tpl->time_to_date($time);
    }

    $TIMES=array();$MINS=array();
    for($i=0;$i<24;$i++){ $h=($i<10)?"0$i":"$i"; $TIMES[$h]=$h; }
    for($i=0;$i<60;$i++){ $h=($i<10)?"0$i":"$i"; $MINS[$h]=$h; }

    query_defaults();
    $form=array();
    $form[]=$tpl->field_section("{from_date}");
    $form[]=$tpl->field_array_hash($DATES,"FROM","nonull:{from_date}",$_SESSION["STATSCOM_DAY"]["FROM"]);
    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $form[]=$tpl->field_array_hash($TIMES,"FROM_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"FROM_MIN","nonull:{minutes}",$tb[1]);
    $form[]=$tpl->field_section("{to_date}");
    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["TOH"]);
    $form[]=$tpl->field_array_hash($DATES,"TO","nonull:{to_date}",$_SESSION["STATSCOM_DAY"]["TO"]);
    $form[]=$tpl->field_array_hash($TIMES,"TO_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"TO_MIN","nonull:{minutes}",$tb[1]);

    echo $tpl->form_outside("{build_query}",$form,null,"{launch}",
        "dialogInstance1.close();","AsWebStatisticsAdministrator",true);
}

function query_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // Seules les dates sont postees par ce formulaire. Lire les autres cles sans
    // garde emet un warning PHP 8 et ecrase par null une valeur deja en session.
    $_SESSION["STATSCOM_DAY"]["FROM"]=$_POST["FROM"];
    $_SESSION["STATSCOM_DAY"]["TO"]=$_POST["TO"];
    $_SESSION["STATSCOM_DAY"]["FROMH"]=$_POST["FROM_HOUR"].":".$_POST["FROM_MIN"];
    $_SESSION["STATSCOM_DAY"]["TOH"]=$_POST["TO_HOUR"].":".$_POST["TO_MIN"];
    foreach(array("USER","IP","MAC","SIZE","LIMIT","CATEGORY") as $k){
        if(isset($_POST[$k])){ $_SESSION["STATSCOM_DAY"][$k]=$_POST[$k]; }
    }
}

/** Valeurs par defaut de la fenetre de requete. */
function query_defaults(){
    if(!isset($_SESSION["STATSCOM_DAY"]["FROM"])){ $_SESSION["STATSCOM_DAY"]["FROM"]=date("Y-m-d"); }
    if(!isset($_SESSION["STATSCOM_DAY"]["TO"])){ $_SESSION["STATSCOM_DAY"]["TO"]=date("Y-m-d"); }
    if(!isset($_SESSION["STATSCOM_DAY"]["FROMH"])){ $_SESSION["STATSCOM_DAY"]["FROMH"]="00:00"; }
    if(!isset($_SESSION["STATSCOM_DAY"]["TOH"])){ $_SESSION["STATSCOM_DAY"]["TOH"]="23:59"; }
    if(intval(isset($_SESSION["STATSCOM_DAY"]["LIMIT"])?$_SESSION["STATSCOM_DAY"]["LIMIT"]:0)==0){
        $_SESSION["STATSCOM_DAY"]["LIMIT"]=250;
    }
}

/** Enregistrements detailles de la periode choisie. */
function query(){
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $q=new postgres_sql();
    $WHEREU="";$WHEREM="";$WHEREI="";$WHERES="";$WEREC="";
    query_defaults();

    $USER=isset($_SESSION["STATSCOM_DAY"]["USER"])?$_SESSION["STATSCOM_DAY"]["USER"]:null;
    $IP=isset($_SESSION["STATSCOM_DAY"]["IP"])?$_SESSION["STATSCOM_DAY"]["IP"]:null;
    $MAC=isset($_SESSION["STATSCOM_DAY"]["MAC"])?$_SESSION["STATSCOM_DAY"]["MAC"]:null;
    $SIZE=isset($_SESSION["STATSCOM_DAY"]["SIZE"])?$_SESSION["STATSCOM_DAY"]["SIZE"]:0;
    $CATEGORY=isset($_SESSION["STATSCOM_DAY"]["CATEGORY"])?$_SESSION["STATSCOM_DAY"]["CATEGORY"]:null;

    $time1=strtotime($_SESSION["STATSCOM_DAY"]["FROM"]." ".$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $time2=strtotime($_SESSION["STATSCOM_DAY"]["TO"]." ".$_SESSION["STATSCOM_DAY"]["TOH"]);
    $seconds=$time2-$time1;
    if($seconds<0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{please_define_dates_in_correct_order}"));
        return;
    }

    $TITLES=array();
    if($seconds<259150){
        $TABLE_SELECTED="statscom";
        $date1=$_SESSION["STATSCOM_DAY"]["FROM"]." ".$_SESSION["STATSCOM_DAY"]["FROMH"];
        $date2=$_SESSION["STATSCOM_DAY"]["TO"]." ".$_SESSION["STATSCOM_DAY"]["TOH"];
        $withtime=true;
    }else{
        $TABLE_SELECTED="statscom_days";
        $date1=$_SESSION["STATSCOM_DAY"]["FROM"];
        $date2=$_SESSION["STATSCOM_DAY"]["TO"];
        $withtime=false;
    }
    $tt2=date("Y-m-d",strtotime($date2));
    $TITLES[]="{from} ".$tpl->time_to_date(strtotime($date1),$withtime);
    if($tt2==date("Y-m-d")){
        $TITLES[]="{to} {today} ".$tpl->time_to_date(strtotime($date2),$withtime);
    }else{
        $TITLES[]="{to} ".$tpl->time_to_date(strtotime($date2),$withtime);
    }
    $TITLES[]="(".distanceOfTimeInWords(strtotime($date1),strtotime($date2)).")";

    // Pentest 2026-11-04 : USER, IP, MAC et les dates (session alimentee par le POST) etaient concatenes dans la requete.
    if($USER<>null){ $TITLES[]="{and} {member} $USER"; $WHEREU=" AND username='".pg_escape_string2($USER)."'"; }
    if($IP<>null){ $TITLES[]="{and} {ipaddr} $IP"; $WHEREI=" AND ipaddr='".pg_escape_string2($IP)."'"; }
    if($MAC<>null){ $TITLES[]="{and} {MAC} $MAC"; $WHEREM=" AND mac='".pg_escape_string2($MAC)."'"; }
    if(intval($SIZE)>0){
        $size_q=round(intval($SIZE)*1024);
        $WHERES=" $TABLE_SELECTED.size > $size_q AND";
    }
    if(intval($CATEGORY)>0){
        $TITLES[]="{and} {category} ".stsh_category_name($catz,$CATEGORY);
        $WEREC=" AND statscom_websites.category=".intval($CATEGORY);
    }

    $limit=intval($_SESSION["STATSCOM_DAY"]["LIMIT"]);
    $sql="SELECT SUM($TABLE_SELECTED.hits) as hits, SUM($TABLE_SELECTED.size) as size,"
        ." $TABLE_SELECTED.username,$TABLE_SELECTED.ipaddr,$TABLE_SELECTED.mac,$TABLE_SELECTED.zdate,"
        ." statscom_websites.sitename,statscom_websites.category"
        ." FROM $TABLE_SELECTED,statscom_websites WHERE{$WHERES}"
        ." statscom_websites.siteid=$TABLE_SELECTED.siteid AND"
        ." $TABLE_SELECTED.zdate >='".pg_escape_string2($date1)."' AND $TABLE_SELECTED.zdate <= '".pg_escape_string2($date2)."'{$WHEREU}{$WHEREI}{$WHEREM}{$WEREC}"
        ." GROUP BY $TABLE_SELECTED.zdate,$TABLE_SELECTED.username,$TABLE_SELECTED.ipaddr,"
        ."$TABLE_SELECTED.mac,statscom_websites.sitename,statscom_websites.category"
        ." ORDER BY $TABLE_SELECTED.zdate LIMIT $limit";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($q->mysql_error));
        return;
    }

    $rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $category_id=$ligne["category"];
        $category=stsh_category_name($catz,$category_id);
        // fw.statscom.category.php n'existe pas: le lien tombait sur un 404 que
        // Loadjs avale sans rien dire. La fiche d'une categorie, c'est
        // fw.statscom.browse.categories.php.
        $cat=stsh_link($category,
            "Loadjs('fw.statscom.browse.categories.php?category=".urlencode($category_id)."')");
        $h ="<tr>";
        $h.="<td class='awc-nowrap'>".htmlspecialchars($ligne["zdate"])."</td>";
        $h.="<td>".htmlspecialchars($ligne["sitename"])."</td>";
        $h.="<td class='awc-nowrap'>$cat</td>";
        $h.="<td class='awc-nowrap'>".htmlspecialchars($ligne["username"])."</td>";
        $h.="<td class='awc-mono awc-nowrap'>".htmlspecialchars($ligne["mac"])."</td>";
        $h.="<td class='awc-mono awc-nowrap'>".htmlspecialchars($ligne["ipaddr"])."</td>";
        $h.="<td class='awc-right awc-nowrap'>".FormatBytes($ligne["size"]/1024)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($ligne["hits"])."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $heads=array(array("LABEL"=>"{date}"),array("LABEL"=>"{sitename}"),array("LABEL"=>"{category}"),
        array("LABEL"=>"{member}"),array("LABEL"=>"{MAC}"),array("LABEL"=>"{ipaddr}"),
        array("LABEL"=>"{size}","RIGHT"=>true),array("LABEL"=>"{hits}","RIGHT"=>true));
    $table="<h2 style='margin-top:10px'>".@implode(" ",$TITLES)."</h2>"
        .stsh_table($heads,$rows,"stsh-qry-rows","stsh-qry-pager","{showing} ".count($rows));
    stsh_echo_table($table,"stsh-qry-rows","stsh-qry-pager");
}
