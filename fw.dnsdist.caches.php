<?php
// Post SP10 - 13/10/2026
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
// awc_table_style()/awc_pager_js()/awc_sort_js(): feuille de style, pagination et
// tri partages des tableaux d'entites.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["remove"])){remove();exit;}
js();

// Le nom du pool repart dans un segment d'URL REST et, cote daemon, dans une
// expression Lua remise a `dnsdist -e`: il ne peut porter qu'un identifiant.
function dnsc_pool($key="pool"):string{
    $p=isset($_GET[$key])?(string)$_GET[$key]:"";
    return preg_replace('/[^A-Za-z0-9_.-]/','',$p);
}
// $_GET["md"] etait reinjecte tel quel dans "$('#$md').remove()": c'est un md5
// calcule par table(), donc de l'hexadecimal, et rien d'autre.
function dnsc_rowid():string{
    $m=isset($_GET["md"])?(string)$_GET["md"]:"";
    return preg_replace('/[^a-f0-9]/','',strtolower($m));
}
// Le domaine part dans un segment d'URL puis, cote daemon, dans
// expungeByName("<domaine>") execute sans shell mais decoupe sur les espaces.
function dnsc_domain():string{
    $d=isset($_GET["remove"])?(string)$_GET["remove"]:"";
    return preg_replace('/[^A-Za-z0-9._*-]/','',$d);
}

function js():bool{
    $tpl                        = new template_admin();
    $page                       = CurrentPageName();
    $pool                       = dnsc_pool();

    return $tpl->js_dialog4("{cache}: $pool","$page?table-start=$pool");
}

function remove(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $users=new usersMenus();
    // Masquer l'icone de suppression ne protege rien: le repartiteur en tete de
    // fichier repond a toute session authentifiee.
    if(!$users->AsDnsAdministrator){$tpl->js_no_privileges();return;}

    $domain = dnsc_domain();
    $md     = dnsc_rowid();
    $pool   = dnsc_pool();
    if($domain==""||$md==""){
        // js_error()/js_no_privileges() EMETTENT le JS et renvoient un bool: les
        // echo aurait ajoute un "1" au flux evalue par Loadjs().
        $tpl->js_error("{failed}");
        return;
    }

    // getFrameWork() n'aiguille vers le REST que les URI prefixees "API:";
    // "/dnsfw/cache/remove/..." partait donc vers framework/, ou ce chemin
    // n'existe pas. La ligne disparaissait quand meme du DOM: l'entree de cache
    // etait intacte et revenait a la recherche suivante.
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/cache/remove/$pool/".rawurlencode($domain)));
    if(!is_object($json)||!isset($json->Status)||!$json->Status){
        $err="{failed}";
        if(is_object($json) && isset($json->Error) && strlen((string)$json->Error)>0){$err=(string)$json->Error;}
        $tpl->js_error($err);
        return;
    }
    echo "$('#$md').remove();\n";
}

function table_start(){
    $pool                       = dnsc_pool("table-start");
    $page=CurrentPageName();
    $tpl                        = new template_admin();
    $searchBlock=$tpl->search_block($page,null,null,null,"&table=yes&pool=$pool");
    echo $tpl->_ENGINE_parse_body($searchBlock);
}

// --- Cellules du tableau: une cellule = une fonction ------------------------
// Le nom de domaine sort du vidage de cache de dnsdist: il a ete choisi par le
// client qui a emis la requete DNS, il est echappe sans exception.
function dnsc_cell_domain($domain):string{
    return "<td style='width:99%' nowrap><span class='awc-ident'>"
        ."<span class='awc-ico'><i class='fa-solid fa-floppy-disk'></i></span>"
        ."<span class='awc-name'>".htmlspecialchars((string)$domain)."</span></span></td>";
}
function dnsc_cell_qtype($qtype):string{
    return "<td class='center' style='width:1%' nowrap><span class='awc-tag'>".htmlspecialchars((string)$qtype)."</span></td>";
}
function dnsc_cell_saved($saved):string{
    return "<td style='width:1%' nowrap><span class='awc-meta awc-mono'>".htmlspecialchars((string)$saved)."</span></td>";
}
function dnsc_cell_actions($remove):string{
    return "<td class='center' style='width:1%' nowrap><span class='awc-acts'>$remove</span></td>";
}

function table(){
    $pool                       = dnsc_pool();
    $search                     = isset($_GET["search"])?trim((string)$_GET["search"]):"";
    // "strlen($search)==\"\"" comparait un ENTIER a une chaine vide: vrai sur
    // PHP 7 (""->0), FAUX sur PHP 8 (0->"0"). Sur un socle PHP 8 la recherche
    // vide partait donc en segment d'URL vide, la route ne repondait plus et le
    // fichier de resultat n'etait jamais regenere -- table figee ou vide.
    if(strlen($search)==0){
        $search="NONE";
    }
    // rawurlencode() et non urlencode(): dans un CHEMIN, le "+" produit par
    // urlencode() reste un "+" litteral et n'est pas relu comme une espace.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/cache/dump/$pool/".rawurlencode($search));
    $resultfile=PROGRESS_DIR."/dnsdist.$pool.results";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',isset($_GET["function"])?(string)$_GET["function"]:"");
    $data=explode("\n",(string)@file_get_contents( $resultfile ));

    $rows=array();
    foreach ($data as $line){

        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([A-Z]+).*?added\s+([0-9]+)#",$line,$re)){
            continue;
        }
        $domain=$re[1];
        $qtype=$re[3];
        $added=intval($re[4]);
        $time_saved=$tpl->time_to_date($added,true);
        $id=md5($line);
        $remove=$tpl->icon_delete("Loadjs('$page?remove=".rawurlencode($domain)."&func=$function&md=$id&pool=$pool')");

        $rows[]="<tr id='$id' data-sort='$added'>"
            .dnsc_cell_domain($domain)
            .dnsc_cell_qtype($qtype)
            .dnsc_cell_saved($time_saved)
            .dnsc_cell_actions($remove)
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th nowrap>{domain}</th>";
    $html[]="<th class='center' nowrap>{query}</th>";
    // Seule colonne triable: l'horodatage. awc_sort_js() trie numeriquement sur
    // l'attribut data-sort de la ligne, qui porte l'epoch brut.
    $html[]="<th nowrap id='dnsc-th-saved'>{saved} <span id='dnsc-th-saved-arrow'></span></th>";
    $html[]="<th class='center' nowrap>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='dnsc-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // Le <tfoot> ne portait qu'un <ul class='pagination'> jamais alimente:
    // l'init footable ciblait $('#table-<time>'), un identifiant qu'AUCUN
    // element de la page ne portait -- ni pagination, ni tri, malgre les
    // data-sortable poses sur chaque <th>.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='dnsc-pager'></span>";
    $html[]="</div></div></div>";

    // Le daemon coupe le vidage a 500 lignes (head -n 500, dans les deux
    // branches de handleDumpCache): le dire, plutot que de laisser croire que
    // le cache tient en 500 entrees.
    if($c>=500){
        $html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>{cache_dump_truncated}</div>";
    }

    // Le script restait DANS _ENGINE_parse_body(), dont la regex #{(.+?)}# lit
    // chaque bloc d'accolades JS comme un {token}. ICON_SCRIPTS_COMPILE() vide
    // les deux piles, celle de l'instance et celle des globales.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    // awc_sort_js() APRES awc_pager_js(): il rend la main par tb.__awcRedraw.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"dnsc-rows","dnsc-pager")
        .awc_sort_js("dnsc-th-saved","dnsc-rows","desc");
    return true;
}
