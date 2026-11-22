<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
// acls_api_get()/acls_api_send(): enveloppe {success,data,error} d'articarest.
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["statistics-domain"])){statistics_domain();exit;}
if(isset($_GET["statistics-domain-table"])){statistics_domain_table();exit;}
if(isset($_GET["statistics-urls"])){statistics_urls();exit;}
if(isset($_GET["statistics-urls-table"])){statistics_urls_table();exit;}
if(isset($_GET["statistics-popup"])){statistics_settings();exit;}
if(isset($_GET["statistics-table-disabled"])){statistics_table_disabled();exit;}
// Ces deux lignes n'avaient PAS de "exit;": l'execution retombait sur page(),
// qui ajoutait toute la page a la reponse. js_confirm_delete() traite une
// reponse non vide comme une ERREUR: la boite de confirmation affichait donc la
// page entiere et ne se fermait jamais.
if(isset($_GET["delete-familysite"])){delete_familysite_js();exit;}
if(isset($_POST["delete-familysite"])){delete_familysite_perform();exit;}
if(isset($_GET["delete-uri"])){delete_uri();exit;}
if(isset($_GET["delete-sitename"])){delete_sitename();exit;}
if(isset($_POST["delete-sitename"])){delete_sitename_perform();exit;}
page();


/** Un jeton d'identifiant de ligne: c'est toujours un md5. */
function pxcd_md($v):string{
    return preg_replace('/[^a-f0-9]/','',(string)$v);
}

/**
 * Lecture d'un des trois endpoints /proxy/cache/purge/* d'articarest.
 *
 * La table PostgreSQL "squidpurge" n'est creee que par le scan des caches
 * (articarest StoredObjectsScan). Tant qu'il n'a jamais tourne, elle N'EXISTE PAS, et cette
 * page l'interrogeait en direct: PostgreSQL repondait
 * `relation "squidpurge" does not exist` et l'erreur SQL brute s'affichait a
 * l'ecran. Le demon rend maintenant cet etat comme une DONNEE
 * ("tableExists":false), ce qui permet de distinguer "jamais analyse" de
 * "analyse, cache vide" -- deux reponses differentes dont une seule se corrige
 * en cliquant sur {analyze_caches}.
 *
 * Rend array($tableExists, $rows). $rows est toujours un tableau.
 */
function pxcd_api_rows($path):array{
    $data=acls_api_get($path);
    if(!is_array($data)){
        // Appel en echec (demon injoignable, route absente d'un binaire trop
        // ancien): on ne peut PAS affirmer que le scan n'a pas tourne.
        return array(true,array());
    }
    $exists=!empty($data["tableExists"]);
    $rows=(isset($data["rows"]) && is_array($data["rows"]))?$data["rows"]:array();
    return array($exists,$rows);
}

/** Le corps de tableau affiche quand il n'y a rien a montrer. */
function pxcd_empty_row($colspan,$tableExists):string{
    $text=$tableExists?"{no_data}":"{cache_not_analyzed}";
    return "<tr><td colspan='$colspan'><div class='awc-empty'>$text</div></td></tr>";
}

function delete_familysite_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Le nom partait BRUT dans le titre de la boite de confirmation, qui est une
    // chaine JavaScript, et $md brut dans le selecteur jQuery.
    $familysite=$_GET["delete-familysite"];
    $md=pxcd_md($_GET["md"] ?? "");

    $jsafter="$('#$md').remove();";

    $tpl->js_confirm_delete(htmlspecialchars($familysite),"delete-familysite",$familysite,$jsafter);

}

function delete_sitename(){
    $sitename=$_GET["delete-sitename"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=pxcd_md($_GET["md"] ?? "");
    $jsafter="$('#$md').remove();";
    $tpl->js_confirm_delete(htmlspecialchars($sitename),"delete-sitename",$sitename,$jsafter);
}
function delete_sitename_perform(){
    // Real purge of the objects: articarest POST /proxy/cache/purge/object (purge
    // run without a shell; squid2.php?purge-delete pasted the name into one).
    $GLOBALS["CLASS_SOCKETS"]->REST_API_TIMEOUT=130;
    list($ok,$err)=acls_api_send("POST","/proxy/cache/purge/object",array("site"=>(string)$_POST["delete-sitename"]));
    if(!$ok){echo $err;return;}
    // Contrat js_confirm_delete: on n'echo RIEN quand la suppression a reussi.
    acls_api_send("DELETE","/proxy/cache/purge/site?sitename="
        .urlencode((string)$_POST["delete-sitename"]));
}
function delete_uri(){
    $md=pxcd_md($_GET["md"] ?? "");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_TIMEOUT=130;
    list($ok,$err)=acls_api_send("POST","/proxy/cache/purge/object",
        array("site"=>(string)$_GET["domain"],"path"=>(string)$_GET["delete-uri"]));
    if(!$ok){
        header("content-type: application/x-javascript");
        echo "alert(".json_encode((string)$err).");";
        return;
    }
    acls_api_send("DELETE","/proxy/cache/purge/url?sitename="
        .urlencode((string)$_GET["domain"])."&path=".urlencode((string)$_GET["delete-uri"]));
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";

}
function delete_familysite_perform(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API_TIMEOUT=130;
    list($ok,$err)=acls_api_send("POST","/proxy/cache/purge/object",array("site"=>(string)$_POST["delete-familysite"]));
    if(!$ok){echo $err;return;}
    // Le nom partait BRUT dans le DELETE, sans meme un echappement.
    acls_api_send("DELETE","/proxy/cache/purge/family?familysite="
        .urlencode((string)$_POST["delete-familysite"]));
}


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html="
<div class=\"row border-bottom white-bg dashboard-header\">
<div class=\"col-sm-12\">
<h1 class=ng-binding>{stored_objects}</h1>
<p>{PROXY_CACHE_STORED_OBJECTS_EXPLAIN}</p>
</div>
</div>



<div class='row'><div id='progress-squidcaching-items'></div>
<div class='ibox-content'>
<div id='table-loader-stored-objects'></div>
</div>
</div>

<script>
LoadAjax('table-loader-stored-objects','$page?table-start=yes');
</script>";


    echo $tpl->_ENGINE_parse_body($html);
}

function table_start(){
    $page=CurrentPageName();
    echo "<div id='squid-purge-table'></div><script>LoadAjaxSilent('squid-purge-table','$page?table=yes');</script>";
}

function table(){

    $page=CurrentPageName();
    $tpl=new template_admin();


    // articarest squid/proxy_caches_jobs.go StoredObjectsScan (port of exec.purge.php).
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.purge.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.purge.progress.log";
    $ARRAY["CMD"]="/proxy/caches/objects/scan";
    $ARRAY["TITLE"]="{analyze_caches}";
    $ARRAY["AFTER"]="LoadAjaxSilent('squid-purge-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-squidcaching-items')";
    $t=time();

    $html[]=$tpl->_ENGINE_parse_body("
	
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fa fa-bolt'></i> {analyze_caches} </label>
			<label class=\"btn btn btn-info\" OnClick=\"Loadjs('fw.proxy.cache.status.php?statistics=yes')\"><i class='fas fa-cogs'></i> {settings} </label>
			</div>
			<div class=\"btn-group\" data-toggle=\"buttons\">
			</div>");

    list($tableExists,$apiRows)=pxcd_api_rows("/proxy/cache/purge/families");

    $rows=array();
    $totalSize=0;
    foreach($apiRows as $r){
        if(!is_array($r)){continue;}
        // Les constructeurs de lignes attendent les noms de colonnes SQL
        // historiques; on normalise ici plutot que de les reecrire.
        $ligne=array(
            "familysite" => (string)($r["familySite"] ?? ""),
            "hits"       => intval($r["hits"] ?? 0),
            "size"       => floatval($r["size"] ?? 0),
        );
        $totalSize=$totalSize+$ligne["size"];
        $rows[]=pxcd_family_row($ligne,$page,$tpl);
    }
    $shown=count($rows);

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{websites}</th>";
    $html[]="<th class='center'>{objects}</th>";
    $html[]="<th class='center'>{size}</th>";
    $html[]="<th class='center'>{delete}</th>";
    $html[]="</tr></thead><tbody id='pxcd-fam-rows'>";
    if($shown==0){
        $html[]=pxcd_empty_row(4,$tableExists);
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown &middot; {size}: <b>".FormatBytes($totalSize/1024)."</b></span>";
    $html[]="<span class='awc-pg' id='pxcd-fam-pager'></span>";
    $html[]="</div></div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"pxcd-fam-rows","pxcd-fam-pager");

}

// Pentest 2026-11-04 : noms de sites / chemins du cache (donnee externe) : htmlspecialchars UTF-8 rendait "" sur un octet Latin-1 (XSS stockee).
/** Un domaine de rattachement et le volume qu'il occupe dans le cache. */
function pxcd_family_row($ligne,$page,$tpl):string{
    $familysite=(string)$ligne["familysite"];
    $zmd5=md5(serialize($ligne));
    $enc=urlencode($familysite);
    // Le nom vient de la base et partait BRUT dans le HTML.
    $link=$tpl->td_href("<span class='awc-name'>".req_html($familysite)."</span>",
        "{stored_objects}","Loadjs('$page?statistics-domain=$enc')");

    $h ="<tr id='$zmd5'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
    $h.="<span>$link</span>";
    $h.="</div></td>";
    $h.="<td class='center'>".FormatNumber(intval($ligne["hits"]))."</td>";
    $h.="<td class='center'><b>".FormatBytes($ligne["size"]/1024)."</b></td>";
    $h.="<td class='center'><div class='awc-acts'>"
        .$tpl->icon_delete("Loadjs('$page?delete-familysite=$enc&md=$zmd5')","AsProxyMonitor")
        ."</div></td>";
    $h.="</tr>";
    return $h;
}

function statistics_urls(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Le domaine est ecrit dans le JavaScript d'ouverture de la modale.
    $domain=$_GET["statistics-urls"];
    $domainenc=urlencode($domain);
    $title=addslashes(htmlspecialchars($domain));
    $tpl->js_dialog3("{stored_objects}: $title", "$page?statistics-urls-table=$domainenc",990);

}

function statistics_urls_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Le nom de site partait BRUT dans le WHERE.
    $domain=(string)$_GET["statistics-urls-table"];
    list($tableExists,$apiRows)=pxcd_api_rows(
        "/proxy/cache/purge/urls?sitename=".urlencode($domain));

    $rows=array();
    $totalSize=0;
    foreach($apiRows as $r){
        if(!is_array($r)){continue;}
        $ligne=array(
            "path" => (string)($r["path"] ?? ""),
            "size" => floatval($r["size"] ?? 0),
        );
        $totalSize=$totalSize+$ligne["size"];
        $rows[]=pxcd_uri_row($ligne,$domain,$page,$tpl);
    }
    $shown=count($rows);

    $html=array();
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{urls}</th>";
    $html[]="<th class='center'>{size}</th>";
    $html[]="<th class='center'>{delete}</th>";
    $html[]="</tr></thead><tbody id='pxcd-uri-rows'>";
    if($shown==0){
        $html[]=pxcd_empty_row(3,$tableExists);
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown &middot; {size}: <b>".FormatBytes($totalSize/1024)."</b></span>";
    $html[]="<span class='awc-pg' id='pxcd-uri-pager'></span>";
    $html[]="</div></div>";

    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"pxcd-uri-rows","pxcd-uri-pager");
}

/**
 * Un objet stocke dans le cache.
 *
 * Le chemin etait tronque a 90 caracteres et enveloppe dans td_href() SANS
 * troisieme argument: le lien n'avait donc aucun OnClick, il etait mort. Le
 * chemin complet est desormais rendu, le conteneur .awc-scroll gerant le
 * debordement.
 */
function pxcd_uri_row($ligne,$domain,$page,$tpl):string{
    $zmd5=md5(serialize($ligne));
    $path=(string)$ligne["path"];
    $pathenc=urlencode($path);
    $del=$tpl->icon_delete("Loadjs('$page?delete-uri=$pathenc&domain=".urlencode($domain)."&md=$zmd5')","AsProxyMonitor");

    $h ="<tr id='$zmd5'>";
    // Le chemin vient de la base et partait BRUT dans le HTML.
    $h.="<td class='awc-mono' style='word-break:break-all'>".req_html($path)."</td>";
    $h.="<td class='center'><b>".FormatBytes($ligne["size"]/1024)."</b></td>";
    $h.="<td class='center'><div class='awc-acts'>$del</div></td>";
    $h.="</tr>";
    return $h;
}

function statistics_domain_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Le domaine de rattachement partait BRUT dans le WHERE.
    $domain=(string)$_GET["statistics-domain-table"];
    list($tableExists,$apiRows)=pxcd_api_rows(
        "/proxy/cache/purge/sites?familysite=".urlencode($domain));

    $rows=array();
    $totalSize=0;
    foreach($apiRows as $r){
        if(!is_array($r)){continue;}
        $ligne=array(
            "sitename" => (string)($r["siteName"] ?? ""),
            "hits"     => intval($r["hits"] ?? 0),
            "size"     => floatval($r["size"] ?? 0),
        );
        $totalSize=$totalSize+$ligne["size"];
        $rows[]=pxcd_site_row($ligne,$page,$tpl);
    }
    $shown=count($rows);

    $html=array();
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{websites}</th>";
    $html[]="<th class='center'>{objects}</th>";
    $html[]="<th class='center'>{size}</th>";
    $html[]="<th class='center'>{delete}</th>";
    $html[]="</tr></thead><tbody id='pxcd-site-rows'>";
    if($shown==0){
        $html[]=pxcd_empty_row(4,$tableExists);
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown &middot; {size}: <b>".FormatBytes($totalSize/1024)."</b></span>";
    $html[]="<span class='awc-pg' id='pxcd-site-pager'></span>";
    $html[]="</div></div>";

    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"pxcd-site-rows","pxcd-site-pager");
}

/** Un site du domaine et le volume qu'il occupe dans le cache. */
function pxcd_site_row($ligne,$page,$tpl):string{
    $sitename=(string)$ligne["sitename"];
    $zmd5=md5(serialize($ligne));
    // Le nom partait NON ENCODE dans l'URL alors que la version encodee etait
    // calculee juste a cote et servait uniquement au lien de suppression.
    $enc=urlencode($sitename);
    $link=$tpl->td_href("<span class='awc-name'>".req_html($sitename)."</span>",
        "{stored_objects}","Loadjs('$page?statistics-urls=$enc')");
    $del=$tpl->icon_delete("Loadjs('$page?delete-sitename=$enc&md=$zmd5')","AsProxyMonitor");

    $h ="<tr id='$zmd5'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
    $h.="<span>$link</span>";
    $h.="</div></td>";
    $h.="<td class='center'>".FormatNumber(intval($ligne["hits"]))."</td>";
    $h.="<td class='center'><b>".FormatBytes($ligne["size"]/1024)."</b></td>";
    $h.="<td class='center'><div class='awc-acts'>$del</div></td>";
    $h.="</tr>";
    return $h;
}

function statistics_domain(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $domain=$_GET["statistics-domain"];
    $domainenc=urlencode($domain);
    $title=addslashes(htmlspecialchars($domain));
    $tpl->js_dialog1("{stored_objects}: $title", "$page?statistics-domain-table=$domainenc");
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}