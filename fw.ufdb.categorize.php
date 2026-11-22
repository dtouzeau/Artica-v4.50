<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["nocache"])){$GLOBALS["NOCACHE"]=true;}

// Pentest 2026-11-04 : results/results-popup/js-import/category_requested etaient recopies sans encodage dans la page (XSS).
foreach(array("results","results-popup") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET[$xssk]);}}
foreach(array("js-import","category_requested") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=intval($_GET[$xssk]);}}
if(isset($_GET["bulk-uploaded"])){test_bulk_uploaded();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["popup2"])){popup2();exit;}
if(isset($_GET["import-popup"])){import_popup();exit;}
if(isset($_GET["import-form"])){import_form();exit;}
if(isset($_GET["import-button"])){import_button();exit;}
if(isset($_GET["results"])){results_js();exit;}
if(isset($_POST["import-category"])){$_SESSION["IMPORT_CATEGORY"]=$_POST;exit;}
if(isset($_POST["category_id"])){save();exit;}
if(isset($_POST["bulk"])){test_bulk_categories_perform();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
if(isset($_GET["test"])){test_categories();exit;}
if(isset($_GET["test-bulk"])){test_bulk_categories();exit;}
if(isset($_GET["test-bulk-results"])){test_bulk_categories_results();exit;}
if(isset($_GET["test-bulk-results-popup"])){test_bulk_categories_results_popup();exit;}
if(isset($_GET["sitenametest"])){test_categories_perform();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["js-simple"])){js_simple();exit;}
if(isset($_GET["js-import"])){js_import();exit;}
if(isset($_GET["browser-plugin"])){browser_plugin();exit;}
if(isset($_POST["BBPlugin"])){browser_plugin_save();exit;}
js();

//fw.ufdb.categorize.php?js-import=
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $array["{categorize}"] = "$page?popup=yes&function=$function";
    $array["{categorize} ({bulk})"] = "$page?import-popup=yes&function=$function";
    $array["{search}"] = "fw.ufdb.categories.php?category-items=0&EnableMenu=1&function=$function";
    $array["{test_categories}"]="$page?test=yes&function=$function";
    $array["{browser_plugin}"]="$page?browser-plugin=yes&function=$function";
    echo $tpl->tabs_default($array);

}

function js_simple(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog5("{test_categories}", "$page?test=yes");

}
function js_import(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $function=$_GET["function"];

    $_SESSION["IMPORT_CATEGORY"]["ForceExt"]=1;
    $_SESSION["IMPORT_CATEGORY"]["category_id"]=$_GET["js-import"];
    $title=$catz->CategoryIntToStr($_SESSION["IMPORT_CATEGORY"]["category_id"]);
    $tpl->js_dialog5("$title: {categorize} ({bulk})", "$page?import-button=yes&import=yes&function=$function");

}

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}


    if(isset($_GET["category_requested"])){
        $cname=urlencode($_GET["cname"]);
        // Pentest 2026-11-04 : cname etait recopie sans encodage dans la page (XSS).
        $tpl->js_dialog5("{categorize}::".($_GET["cname"] ?? ""), "$page?popup=yes&category_requested={$_GET["category_requested"]}&cname=$cname&function=$function");
        return;
    }
    $tpl->js_dialog5("{categorize}", "$page?tabs=yes&function=$function");
}

function test_bulk_categories_results(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{results}", "$page?test-bulk-results-popup=yes",1024);
}
function test_bulk_categories_results_popup(){
    $tpl=new template_admin();
    // JSON written by articarest categories.BulkTest() (base64): categorized, unresolved
    // (the family domain does not exist in the DNS) and unknown sites.
    $res=json_decode((string)base64_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCAT_TEST_BULK_CATEGORIES_RESULTS")),true);
    if(!is_array($res)){
        echo $tpl->div_warning("{no_data}");
        return;
    }
    $html=array();
    if(!empty($res["truncated"])){
        $html[]=$tpl->div_warning(intval($res["total"])." / {websites}");
    }
    $html[]=test_bulk_results_section("{categorized} (".bulk_count($res,"categorized").")",bulk_list($res,"categorized"),"category");
    $html[]=test_bulk_results_section("{unknown} DNS (".bulk_count($res,"unresolved").")",bulk_list($res,"unresolved"),"family");
    $html[]=test_bulk_results_section("{unknown} (".bulk_count($res,"unknown").")",bulk_list($res,"unknown"),"");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
function bulk_list($res,$key):array{
    if(!isset($res[$key]) || !is_array($res[$key])){return array();}
    return $res[$key];
}
function bulk_count($res,$key):int{
    return count(bulk_list($res,$key));
}
// One block of the bulk-test result. Site names and category names come from the lists
// and the categorization services: escaped, and "{" "}" neutralised so that the
// translation engine never reads them as tokens.
function test_bulk_results_section($title,$rows,$field):string{
    if(count($rows)==0){return "";}
    $html=array();
    $html[]="<H2>$title</H2>";
    $html[]="<table class='table table-hover' style='width:100%'><tbody>";
    foreach($rows as $row){
        $site=bulk_safe(isset($row["site"])?$row["site"]:"");
        $value="";
        if($field<>"" && isset($row[$field])){$value=bulk_safe($row[$field]);}
        $html[]="<tr><td style='width:1%' nowrap>$site</td><td><strong>$value</strong></td></tr>";
    }
    $html[]="</tbody></table>";
    return @implode("\n",$html);
}
function bulk_safe($value):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$value,ENT_QUOTES));
}



function test_bulk_categories(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/categorize.$t.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/categorize.$t.logs.txt";
    $ARRAY["CMD"]="/categories/bulktest/$t";
    $ARRAY["TITLE"]="{test_categories} ({bulk})";
    $ARRAY["AFTER"]="Loadjs('$page?test-bulk-results=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-bulk-categorize')";



    $form[]=$tpl->field_textarea("bulk", "{websites}", $_SESSION["UFDBCAT_TEST_BULK_CATEGORIES"]);
    $form[]=$tpl->field_button_upload("{import}","bulk-uploaded","{upload}");
    echo
        "<div id='progress-bulk-categorize'></div>".
        $tpl->form_outside("{test_categories} ({bulk})", @implode("\n",$form),null,"{search_categories}",$jsafter);
}

function test_bulk_categories_perform(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["UFDBCAT_TEST_BULK_CATEGORIES"]=$_POST["bulk"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_TEST_BULK_CATEGORIES",base64_encode($_POST["bulk"]));

}


/**
 * Onglet "Tester les categories" (maquette stitch_testeur_de_cat_gories_artica).
 * La barre de test est rendue une fois ; le resultat (?sitenametest=) vient de
 * /category/test/{domain}, qui interroge TOUTES les sources : la page montre la
 * categorie retenue par le proxy ET toutes les categories trouvees.
 */
function test_categories(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($_SESSION["UFDBCAT_TEST_CATEGORIES"])){$_SESSION["UFDBCAT_TEST_CATEGORIES"]="";}
    $last=htmlspecialchars((string)$_SESSION["UFDBCAT_TEST_CATEGORIES"],ENT_QUOTES);
    $placeholder=htmlspecialchars($tpl->javascript_parse_text("{ctst_placeholder}"),ENT_QUOTES);

    $html=array();
    $html[]="<div class='ak ctst-wrap'>";
    $html[]="<section class='ak-card ctst-card'>";
    $html[]="<div class='ctst-head'><label class='ctst-label' for='ctst-input'>{ctst_site_to_test}</label>";
    $html[]="<span class='ctst-hint'>{ctst_enter_hint}</span></div>";
    $html[]="<p class='ctst-help'>{ctst_help}</p>";
    $html[]="<div class='ctst-bar'><span class='ctst-bar-ico'><i class='fas fa-globe'></i></span>";
    $html[]="<input type='text' id='ctst-input' class='ctst-input' value=\"$last\" placeholder=\"$placeholder\" autocomplete='off' onkeydown='CtstKey(event)'>";
    $html[]="<button type='button' id='ctst-run' class='ctst-btn' onclick='CtstRun()'><span class='ctst-btn-ico'><i class='fas fa-magnifying-glass'></i></span><span id='ctst-run-txt'>{ctst_run}</span></button>";
    $html[]="</div></section>";
    $html[]="<div id='sitenametest-results' class='ctst-results' aria-live='polite'></div>";
    $html[]="</div>";

    $running=addslashes($tpl->javascript_parse_text("{ctst_running}"));
    $run=addslashes($tpl->javascript_parse_text("{ctst_run}"));
    $js="<script>
function CtstKey(e){ if(e.keyCode==13){ e.preventDefault(); CtstRun(); } }
function CtstRun(){
  var v=document.getElementById('ctst-input').value.trim();
  if(v===''){ return; }
  var b=document.getElementById('ctst-run'); b.disabled=true;
  document.getElementById('ctst-run-txt').textContent='$running';
  var r=document.getElementById('sitenametest-results'); if(r){ r.style.opacity='.5'; }
  LoadAjaxSilent('sitenametest-results','$page?sitenametest='+encodeURIComponent(v));
}
function CtstDone(){
  var b=document.getElementById('ctst-run'); if(b){ b.disabled=false; }
  var t=document.getElementById('ctst-run-txt'); if(t){ t.textContent='$run'; }
  var r=document.getElementById('sitenametest-results'); if(r){ r.style.opacity='1'; }
}
function CtstToggle(id){
  var d=document.getElementById(id); if(!d){ return; }
  var open=d.style.display!=='none';
  d.style.display=open?'none':'block';
  var c=document.getElementById(id+'-chev'); if(c){ c.style.transform=open?'':'rotate(90deg)'; }
}
function CtstGoCategorize(){
  var a=document.querySelector('a[data-url^=\"$page?popup=yes\"]');
  if(a){ a.click(); }
}
NoSpinner();
if(document.getElementById('ctst-input').value.trim()!==''){ CtstRun(); }
</script>";
    echo ctst_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
}

/** Reponse du diagnostic ; repli sur /category/get quand le demon ne connait pas encore /category/test. */
function ctst_query($site):array{
    $enc=rawurlencode($site);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/category/test/$enc"),true);
    if(is_array($json) && isset($json["effective"]) && is_array($json["effective"])){
        if(!isset($json["hits"]) || !is_array($json["hits"])){$json["hits"]=array();}
        if(!isset($json["sources"]) || !is_array($json["sources"])){$json["sources"]=array();}
        return $json;
    }
    // Demon plus ancien : une seule source, celle de la chaine.
    $eff=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/category/get/$enc"),true);
    if(!is_array($eff)){$eff=array();}
    $hits=array();
    if(intval($eff["category_id"] ?? 0)>0){
        $hits[]=array("source"=>(string)($eff["Engine"] ?? ""),"category_id"=>intval($eff["category_id"]),"categoryname"=>(string)($eff["categoryname"] ?? ""));
    }
    return array("Status"=>true,"domain"=>$site,"effective"=>$eff,"hits"=>$hits,"sources"=>array(),"legacy"=>true);
}

/** Nom, description et icone d'une categorie (descriptions de la console, repli sur le demon). */
function ctst_category_info($id,$fallbackName,$descriptions):array{
    $id=intval($id);
    $info=array("name"=>trim((string)$fallbackName),"desc"=>"","icon"=>"");
    if(isset($descriptions[$id]) && is_array($descriptions[$id])){
        $d=$descriptions[$id];
        if(!empty($d["categoryname"])){$info["name"]=(string)$d["categoryname"];}
        $info["desc"]=(string)($d["category_description"] ?? "");
        $info["icon"]=(string)($d["category_icon"] ?? "");
    }elseif($id>=5001 && $id<=5128){
        $info["desc"]="Lemnia ".$info["name"]." database";
    }
    if($info["name"]===""){$info["name"]="#$id";}
    return $info;
}

function test_categories_perform():bool{
    $tpl=new template_admin();
    $site=url_decode_special_tool(trim(strtolower((string)$_GET["sitenametest"])));
    $site=trim(str_replace(array(";","?"),array(".",""),$site));
    // Une adresse complete est acceptee : on garde l'hote.
    if(preg_match('#^[a-z][a-z0-9+.-]*://([^/:]+)#',$site,$re)){$site=$re[1];}
    $site=trim(preg_replace('#[/:].*$#','',$site));
    if($site===""){echo "<script>CtstDone();</script>";return false;}
    $_SESSION["UFDBCAT_TEST_CATEGORIES"]=$site;

    $rep=ctst_query($site);
    $eff=$rep["effective"];
    $catz=new mysql_catz();
    $descriptions=$catz->categories_descriptions();
    if(!is_array($descriptions)){$descriptions=array();}

    // Regroupe les correspondances par categorie : chaque categorie une seule fois,
    // avec la liste des sources qui l'ont trouvee.
    $groups=array();
    foreach($rep["hits"] as $h){
        if(!is_array($h)){continue;}
        $id=intval($h["category_id"] ?? 0);
        if($id<=0){continue;}
        if(!isset($groups[$id])){$groups[$id]=array("name"=>(string)($h["categoryname"] ?? ""),"sources"=>array());}
        $groups[$id]["sources"][]=array((string)($h["source"] ?? ""),(string)($h["match"] ?? ""));
    }
    $effId=intval($eff["category_id"] ?? 0);
    if($effId>0 && !isset($groups[$effId])){
        $groups[$effId]=array("name"=>(string)($eff["categoryname"] ?? ""),"sources"=>array(array((string)($eff["Engine"] ?? ""),"")));
    }

    $html=array();
    if(count($groups)==0){
        $html[]=ctst_card_not_found($site);
    }else{
        if($effId>0){
            $html[]=ctst_card_effective($site,$effId,$groups[$effId],$descriptions);
        }
        $html[]=ctst_all_found($groups,$effId,$descriptions);
    }
    $html[]=ctst_meta($eff,$effId);
    $html[]=ctst_details($rep);
    echo ctst_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>CtstDone();</script>";
    return true;
}

/** Pastille d'icone d'une categorie (image de la base, sinon icone generique). */
function ctst_icon($icon,$big=false):string{
    $cls=$big?"ak-ico ak-ico-sq ctst-ico ctst-ico-big":"ak-ico ak-ico-sm ak-ico-sq ctst-ico";
    if($icon!==""){return "<span class='$cls'><img src=\"".htmlspecialchars($icon)."\" alt=''></span>";}
    return "<span class='$cls'><i class='fas fa-tag'></i></span>";
}

/** Lien vers la fiche d'une categorie. */
function ctst_category_js($id):string{
    return "Loadjs('fw.ufdb.categories.php?category-js=".intval($id)."')";
}

/** Carte principale : la categorie que le proxy utilise pour ce site. */
function ctst_card_effective($site,$id,$group,$descriptions):string{
    $info=ctst_category_info($id,$group["name"],$descriptions);
    $h="<section class='ak-card ctst-card ctst-result'>";
    $h.=ctst_icon($info["icon"],true);
    $h.="<div class='ctst-result-body'><span class='ctst-site'>".htmlspecialchars($site)."</span>";
    $h.="<a class='ctst-catname' href='javascript:blur();' onclick=\"".ctst_category_js($id)."\">".htmlspecialchars($info["name"])." <i class='fas fa-arrow-up-right-from-square'></i></a>";
    if($info["desc"]!==""){$h.="<p class='ctst-desc'>".$info["desc"]."</p>";}
    $h.="</div><span class='ak-pill ak-ok'><i class='fas fa-check'></i> {ctst_retained}</span></section>";
    return $h;
}

/** Aucune source ne connait le site : carte neutre (le rouge est reserve au blocage). */
function ctst_card_not_found($site):string{
    $h="<section class='ak-card ctst-card ctst-result ctst-result-warn'>";
    $h.="<span class='ak-ico ak-ico-sq ak-warn ctst-ico ctst-ico-big'><i class='fas fa-question'></i></span>";
    $h.="<div class='ctst-result-body'><span class='ctst-site'>".htmlspecialchars($site)."</span>";
    $h.="<span class='ctst-catname ctst-catname-plain'>{ctst_not_found_title}</span>";
    $h.="<p class='ctst-desc'>{ctst_not_found_text}</p>";
    $h.="<a class='ctst-link' href='javascript:blur();' onclick='CtstGoCategorize()'>{ctst_categorize_it} <span aria-hidden='true'>&rarr;</span></a>";
    $h.="</div></section>";
    return $h;
}

/** Toutes les categories trouvees, chacune avec les sources qui l'ont trouvee. */
function ctst_all_found($groups,$effId,$descriptions):string{
    $n=count($groups);
    $h="<section class='ak-card ctst-card'>";
    $h.="<div class='ctst-head'><span class='ctst-label'>{ctst_all_found} <span class='ak-count'>$n</span></span></div>";
    $h.="<p class='ctst-help'>{ctst_all_found_explain}</p><ul class='ctst-list'>";
    foreach($groups as $id=>$g){
        $info=ctst_category_info($id,$g["name"],$descriptions);
        $h.="<li class='ctst-item".($id==$effId?" ctst-item-on":"")."'>".ctst_icon($info["icon"]);
        $h.="<div class='ctst-item-body'><a class='ctst-item-name' href='javascript:blur();' onclick=\"".ctst_category_js($id)."\">".htmlspecialchars($info["name"])."</a>";
        if($info["desc"]!==""){$h.="<span class='ctst-item-desc'>".$info["desc"]."</span>";}
        $h.="<span class='ctst-sources'>";
        foreach($g["sources"] as $s){
            $label=$s[0]===""?"?":$s[0];
            $h.="<span class='ctst-src'>".htmlspecialchars($label);
            if($s[1]!==""){$h.=" <span class='ctst-src-match'>(".htmlspecialchars($s[1]).")</span>";}
            $h.="</span>";
        }
        $h.="</span></div>";
        if($id==$effId){$h.="<span class='ak-pill ak-ok'>{ctst_retained_short}</span>";}
        $h.="</li>";
    }
    $h.="</ul></section>";
    return $h;
}

/** Ligne de metadonnees : moteur retenu, temps de reponse, fiche de la categorie. */
function ctst_meta($eff,$effId):string{
    $tpl=new template_admin();
    $engine=trim((string)($eff["Engine"] ?? ""));
    $ms=intval($eff["time"] ?? 0);
    $resp=str_replace("%s","<strong>$ms</strong>",$tpl->_ENGINE_parse_body("{ctst_response_ms}"));
    $h="<div class='ctst-meta'><span>{engine} : <strong>".($engine===""?"—":htmlspecialchars($engine))."</strong></span>";
    $h.="<span class='ctst-dot'>·</span><span>$resp</span>";
    if($effId>0){
        $h.="<a class='ctst-link ctst-meta-right' href='javascript:blur();' onclick=\"".ctst_category_js($effId)."\">{ctst_category_sheet} <i class='fas fa-chevron-right'></i></a>";
    }
    $h.="</div>";
    return $h;
}

/** Details repliables : ce que chaque source a fait, puis les etapes du moteur. */
function ctst_details($rep):string{
    $eff=$rep["effective"];
    $events=(isset($eff["Events"]) && is_array($eff["Events"]))?$eff["Events"]:array();
    if(!empty($eff["Error"])){$events[]=(string)$eff["Error"];}
    $sources=$rep["sources"];
    $count=count($sources)+count($events);
    if($count==0){return "";}
    $id="ctst-details-".md5(microtime());
    $h="<button type='button' class='ctst-toggle' onclick=\"CtstToggle('$id')\"><span id='$id-chev' class='ctst-chev'><i class='fas fa-chevron-right'></i></span>{ctst_details} ($count)</button>";
    $h.="<div id='$id' class='ctst-details' style='display:none'>";
    if(count($sources)>0){
        $h.="<table class='ctst-src-table'><tbody>";
        foreach($sources as $s){
            if(!is_array($s)){continue;}
            $name=htmlspecialchars((string)($s["source"] ?? ""));
            if(empty($s["enabled"])){
                $state="<span class='ctst-st ctst-st-off'>{disabled}</span>";
            }elseif(!empty($s["error"])){
                $state="<span class='ctst-st ctst-st-err'>{error} : ".htmlspecialchars((string)$s["error"])."</span>";
            }elseif(intval($s["found"] ?? 0)>0){
                $state="<span class='ctst-st ctst-st-on'>".str_replace("%s",intval($s["found"]),(new template_admin())->_ENGINE_parse_body("{ctst_found_n}"))."</span>";
            }else{
                $state="<span class='ctst-st'>{ctst_nothing_found}</span>";
            }
            $time=empty($s["enabled"])?"":intval($s["time"] ?? 0)." ms";
            $h.="<tr><td>$name</td><td>$state</td><td class='ctst-num'>$time</td></tr>";
        }
        $h.="</tbody></table>";
    }
    if(count($events)>0){
        $h.="<ol class='ctst-events'>";
        // Les etapes du demon disent "{none}" pour "pas de reponse" : ce jeton vaut
        // "Ne pas utiliser" (libelle d'option), d'ou un jeton dedie ici.
        foreach($events as $e){$h.="<li>".htmlspecialchars(str_replace("{none}","{ctst_no_answer}",(string)$e))."</li>";}
        $h.="</ol>";
    }
    $h.="</div>";
    return $h;
}

/** CSS du kit de l'onglet (une fois par requete). */
function ctst_style():string{
    if(isset($GLOBALS["CTST_STYLE_DONE"])){return "";}
    $GLOBALS["CTST_STYLE_DONE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.ctst-wrap{margin-top:16px}
.ctst-card{padding:20px 22px;margin-bottom:16px}
.ctst-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.ctst-label{font-size:15px;font-weight:600;color:#111d23;margin:0;display:inline-flex;align-items:center;gap:8px}
.ctst-hint{font-size:12px;color:#6d7a77}
.ctst-help{font-size:14px;color:#3d4947;margin:6px 0 14px 0;line-height:1.5}
.ctst-bar{display:flex;align-items:stretch;border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;background:#fff}
.ctst-bar:focus-within{border-color:#006a62;box-shadow:0 0 0 3px $soft}
.ctst-bar-ico{display:inline-flex;align-items:center;padding:0 14px;color:#6d7a77;font-size:16px}
.ctst-input{flex:1 1 auto;min-width:0;border:0;outline:0;height:48px;font-size:16px;color:#111d23;background:transparent;padding:0}
.ctst-btn{display:inline-flex;align-items:center;gap:10px;border:0;background:#006a62;color:#fff;font-size:15px;font-weight:600;padding:0 22px;cursor:pointer;white-space:nowrap;transition:background .15s}
.ctst-btn:hover{background:#00504a}
.ctst-btn[disabled]{background:#3d8f88;cursor:progress}
.ctst-btn:focus-visible,.ctst-link:focus-visible,.ctst-catname:focus-visible,.ctst-item-name:focus-visible,.ctst-toggle:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.ctst-btn-ico{display:inline-flex}
.ctst-results{min-height:120px;transition:opacity .15s}
.ctst-result{display:flex;align-items:flex-start;gap:18px}
.ctst-result-warn{border-left:4px solid #d98324}
.ctst-ico{overflow:hidden}
.ctst-ico img{max-width:24px;max-height:24px}
.ctst-ico-big{flex-basis:56px;width:56px;height:56px;border-radius:12px;font-size:22px}
.ctst-ico-big img{max-width:32px;max-height:32px}
.ctst-result-body{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:4px}
.ctst-site{font-size:14px;color:#6d7a77;overflow-wrap:anywhere}
.ctst-catname{font-size:24px;font-weight:600;color:#111d23;text-decoration:none;line-height:1.25}
.ctst-catname i{font-size:13px;color:#6d7a77;opacity:0;transition:opacity .15s}
.ctst-catname:hover{color:#006a62;text-decoration:underline}
.ctst-catname:hover i{opacity:1}
.ctst-catname-plain:hover{color:#111d23;text-decoration:none}
.ctst-desc{font-size:14px;color:#3d4947;margin:2px 0 0 0;line-height:1.5}
.ctst-link{color:#006a62;font-weight:600;font-size:14px;text-decoration:none;margin-top:6px;display:inline-flex;align-items:center;gap:6px}
.ctst-link:hover{color:#00504a;text-decoration:underline}
.ctst-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
.ctst-item{display:flex;align-items:flex-start;gap:14px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc}
.ctst-item-on{border-color:$accent;background:#fff}
.ctst-item-body{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:3px}
.ctst-item-name{font-size:15px;font-weight:600;color:#111d23;text-decoration:none}
.ctst-item-name:hover{color:#006a62;text-decoration:underline}
.ctst-item-desc{font-size:13px;color:#3d4947;line-height:1.45}
.ctst-sources{display:flex;flex-wrap:wrap;gap:6px;margin-top:4px}
.ctst-src{display:inline-block;padding:2px 8px;border-radius:6px;border:1px solid #bcc9c6;background:#fff;font-size:12px;color:#3d4947}
.ctst-src-match{color:#6d7a77}
.ctst-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13px;color:#3d4947;padding:0 4px 10px 4px}
.ctst-meta strong{color:#111d23;font-variant-numeric:tabular-nums}
.ctst-dot{color:#bcc9c6}
.ctst-meta-right{margin:0 0 0 auto}
.ctst-toggle{display:inline-flex;align-items:center;gap:8px;background:none;border:0;padding:6px 4px;color:#006a62;font-size:14px;font-weight:600;cursor:pointer}
.ctst-chev{display:inline-flex;font-size:11px;transition:transform .15s}
.ctst-details{margin-top:8px;padding:14px 16px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;max-height:320px;overflow:auto}
.ctst-src-table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:10px}
.ctst-src-table td{padding:6px 8px;border-bottom:1px solid #e2e8f0;color:#111d23}
.ctst-num{text-align:right;color:#6d7a77;white-space:nowrap;font-variant-numeric:tabular-nums}
.ctst-st{color:#3d4947}
.ctst-st-on{color:#006a62;font-weight:600}
.ctst-st-off{color:#6d7a77}
.ctst-st-err{color:#ba1a1a}
.ctst-events{margin:0;padding-left:22px;font-size:12px;color:#3d4947;line-height:1.7}
</style>";
}

function results_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $category_requested=intval($_GET["category_requested"]);
    if($category_requested>0) {
        echo "Loadjs('fw.ufdb.categories.php?filltable=$category_requested');\n";
    }

    $tpl->js_dialog5("{categorize}", "$page?results-popup={$_GET["results"]}");

}

function results_popup(){
    $logid=$_GET["results-popup"];
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/$logid.log"));
    @unlink("/usr/share/artica-postfix/ressources/logs/web/$logid.log");

    echo awc_table_style()."<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>
                            <thead>
                            <tr>
                                <th>Events</th>
                            </tr>
                            </thead>
                            <tbody>";

    foreach ($f as $line){

        echo "<tr><td>$line</td></tr>";

    }
    echo "</tbody></table></div></div></div>";
}
/**
 * Onglet "Categoriser (Masse)" (maquette stitch_import_masse_artica) : un seul
 * ecran au lieu de l'ancien assistant en deux etapes. Le fichier part vers
 * /fw.upload.php, puis ?file-uploaded= recoit la categorie et le mode choisis
 * a cet instant : plus besoin de les ranger en session au prealable.
 * La colonne de droite est celle de l'onglet Categoriser (cz_options_column).
 */
function import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $err="";
    list($mine,$official)=cz_categories($err);
    if($err!==""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err)));
        return;
    }
    $html=array();
    $html[]="<div class='ak cz-wrap'>";
    $html[]="<div class='cz-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-file-import'></i></span><div>";
    $html[]="<h2>{imp_title}</h2><p>{imp_subtitle}</p></div></div>";
    $html[]="<div class='cz-cols'>";
    $html[]=imp_file_column();
    $html[]=cz_options_column($mine,$official,0);
    $html[]="</div>";
    $html[]="<div class='cz-foot' id='imp-foot'><span id='imp-summary' class='cz-summary'></span>";
    $html[]="<button type='button' id='imp-run' class='ak-btn ak-btn-primary ak-btn-lg' onclick='ImpRun()' disabled><i class='fas fa-file-import'></i> <span id='imp-run-txt'>{imp_run}</span></button></div>";
    $html[]="<div id='imp-error' class='ak-banner ak-err cz-error' role='alert' style='display:none'></div>";
    $html[]="<div id='import-categories-div-progress' class='cz-progress' aria-live='polite'></div>";
    $html[]="</div>";
    echo cz_style().imp_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).cz_shared_js().imp_js($page,$function);
}

/** Colonne gauche : zone de depot, fichier choisi et regles du fichier. */
function imp_file_column():string{
    $h="<div class='cz-col'>";
    $h.="<div class='cz-label-row'><span class='cz-label'>{imp_file} <span class='cz-req'>*</span></span><span class='cz-hint'>{imp_formats}</span></div>";
    $h.="<input type='file' id='imp-input' accept='.txt,.gz,text/plain,application/gzip' style='display:none' onchange='ImpPick(this.files[0])'>";
    $h.="<div id='imp-drop' class='imp-drop' role='button' tabindex='0' aria-label=\"{imp_choose}\" onclick=\"document.getElementById('imp-input').click()\" onkeydown='ImpKey(event)'>";
    $h.="<span class='imp-drop-ico'><i class='fas fa-cloud-arrow-up'></i></span>";
    $h.="<span class='imp-drop-title'>{imp_drop}</span>";
    $h.="<span class='imp-drop-or'>{imp_or} <span class='imp-choose'>{imp_choose}</span></span></div>";
    $h.="<div id='imp-picked' class='imp-picked' style='display:none'>";
    $h.="<span class='ak-ico ak-ico-sm ak-ico-sq'><i class='far fa-file-lines'></i></span>";
    $h.="<div class='imp-picked-body'><span id='imp-name' class='imp-name'></span><span id='imp-size' class='imp-size'></span></div>";
    $h.="<a href='javascript:blur();' class='imp-change' onclick=\"document.getElementById('imp-input').click()\">{imp_change}</a></div>";
    $h.="<div class='cz-help'><span class='cz-help-ico'><i class='fas fa-circle-info'></i></span><ul>";
    $h.="<li>{imp_help_1}</li><li>{imp_help_2}</li><li>{imp_help_3}</li></ul></div>";
    $h.="</div>";
    return $h;
}

/** JS de l'onglet : choix du fichier, resume, envoi puis import. */
function imp_js($page,$function):string{
    $tpl=new template_admin();
    $tx=array();
    foreach(array("imp_summary","imp_summary_empty","imp_uploading","imp_run","imp_running","imp_upload_failed") as $k){
        $tx[$k]=json_encode($tpl->javascript_parse_text("{".$k."}"));
    }
    return "<script>
var ImpFile=null, ImpAfter='$function';
function CzOptChanged(){ ImpUpdate(); }
function ImpKey(e){ if(e.keyCode==13||e.keyCode==32){ e.preventDefault(); document.getElementById('imp-input').click(); } }
function ImpSize(n){ if(n<1024){return n+' B';} if(n<1048576){return Math.round(n/1024)+' KB';} return (n/1048576).toFixed(1)+' MB'; }
function ImpPick(f){
  if(!f){return;}
  ImpFile=f;
  document.getElementById('imp-drop').style.display='none';
  document.getElementById('imp-picked').style.display='';
  document.getElementById('imp-name').textContent=f.name;
  document.getElementById('imp-size').textContent=ImpSize(f.size);
  document.getElementById('imp-error').style.display='none';
  ImpUpdate();
}
function ImpUpdate(){
  var cat=CzCatName(), ready=(ImpFile!==null && cat!=='');
  document.getElementById('imp-summary').textContent= ready ? {$tx["imp_summary"]}.replace('%f',ImpFile.name).replace('%c',cat) : {$tx["imp_summary_empty"]};
  document.getElementById('imp-run').disabled=!ready;
}
function ImpBusy(on,label){
  document.getElementById('imp-run').disabled=on;
  document.getElementById('imp-run-txt').textContent=label;
  var c=document.querySelectorAll('.cz-col');
  for(var i=0;i<c.length;i++){ c[i].style.opacity=on?'.6':''; c[i].style.pointerEvents=on?'none':''; }
}
function ImpFail(msg){
  var e=document.getElementById('imp-error'); e.textContent=msg; e.style.display='';
  ImpBusy(false,{$tx["imp_run"]}); ImpUpdate();
}
function ImpDone(){
  ImpBusy(false,{$tx["imp_run"]}); ImpUpdate();
  if(ImpAfter!=='' && typeof window[ImpAfter]==='function'){ window[ImpAfter](); }
}
function ImpRun(){
  if(ImpFile===null){return;}
  var cat=document.getElementById('cz-category').value; if(cat===''){return;}
  var force=document.querySelector('input[name=cz-force]:checked');
  var forceCat=force?force.value:'0', forceExt=document.getElementById('cz-noext').checked?'1':'0';
  document.getElementById('imp-error').style.display='none';
  ImpBusy(true,{$tx["imp_uploading"]}.replace('%s','0'));
  var fd=new FormData(); fd.append('files',ImpFile);
  var x=new XMLHttpRequest(); x.open('POST','/fw.upload.php');
  x.upload.onprogress=function(e){ if(e.lengthComputable){ document.getElementById('imp-run-txt').textContent={$tx["imp_uploading"]}.replace('%s',Math.round(e.loaded/e.total*100)); } };
  x.onerror=function(){ ImpFail({$tx["imp_upload_failed"]}); };
  x.onload=function(){
    var up=null; try{ var j=JSON.parse(x.responseText); up=(j && j.files)?j.files[0]:null; }catch(err){}
    if(x.status!==200 || up===null || up.error){ ImpFail(up&&up.error?up.error:{$tx["imp_upload_failed"]}); return; }
    ImpBusy(true,{$tx["imp_running"]});
    Loadjs('$page?file-uploaded='+encodeURIComponent(up.name)+'&category_id='+encodeURIComponent(cat)+'&ForceCat='+forceCat+'&ForceExt='+forceExt+'&function=ImpDone');
  };
  x.send(fd);
}
(function(){
  var d=document.getElementById('imp-drop');
  ['dragenter','dragover'].forEach(function(t){ d.addEventListener(t,function(e){ e.preventDefault(); d.className='imp-drop imp-drop-over'; }); });
  ['dragleave','drop'].forEach(function(t){ d.addEventListener(t,function(e){ e.preventDefault(); d.className='imp-drop'; }); });
  d.addEventListener('drop',function(e){ if(e.dataTransfer && e.dataTransfer.files.length){ ImpPick(e.dataTransfer.files[0]); } });
})();
NoSpinner();
ImpUpdate();
</script>";
}

/** CSS propre a l'import (le reste vient de cz_style). */
function imp_style():string{
    if(isset($GLOBALS["IMP_STYLE_DONE"])){return "";}
    $GLOBALS["IMP_STYLE_DONE"]=true;
    $soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.imp-drop{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;height:220px;margin-top:6px;border:2px dashed #cbd5e1;border-radius:12px;background:#f8fafc;cursor:pointer;text-align:center;transition:border-color .15s,background .15s}
.imp-drop:hover,.imp-drop-over{border-style:solid;border-color:#006a62;background:$soft}
.imp-drop:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.imp-drop-ico{width:56px;height:56px;border-radius:50%;background:#fff;color:#006a62;display:inline-flex;align-items:center;justify-content:center;font-size:24px;box-shadow:0 1px 2px rgba(15,23,42,.08)}
.imp-drop-title{font-size:16px;font-weight:600;color:#111d23}
.imp-drop-or{font-size:13px;color:#3d4947}
.imp-choose{display:inline-block;margin-left:4px;padding:5px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#111d23;font-weight:600}
.imp-picked{display:flex;align-items:center;gap:14px;margin-top:6px;padding:14px 16px;border:1px solid $line;border-radius:12px;background:#fff}
.imp-picked-body{flex:1 1 auto;min-width:0;display:flex;flex-direction:column;gap:2px}
.imp-name{font-size:15px;font-weight:600;color:#111d23;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.imp-size{font-size:13px;color:#3d4947}
.imp-change{color:#006a62;font-weight:600;font-size:14px;text-decoration:none;white-space:nowrap}
.imp-change:hover{text-decoration:underline;color:#00504a}
</style>";
}

function import_button():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $function=$_GET["function"];
    if(isset($_GET["import"])){
        $_SESSION["IMPORT_CATEGORY"]["ForceCat"]=1;
        $_SESSION["IMPORT_CATEGORY"]["ForceExt"]=1;
    }

    $ForceExt=$_SESSION["IMPORT_CATEGORY"]["ForceExt"];
    $addon="&category_id={$_SESSION["IMPORT_CATEGORY"]["category_id"]}&ForceCat={$_SESSION["IMPORT_CATEGORY"]["ForceCat"]}&ForceExt=$ForceExt&function=$function";


    $td1="style='width:1%' nowrap";
    $html[]="";
    $html[]="<div id='import-categories-div-progress'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan=2 style='padding-top:10px;text-align:left'><H2>{import} {websites}</H2><hr></td>";
    $html[]="<tr>";
    $html[]="<td $td1>{category}:</td>";
    $html[]="<td><strong>".$catz->CategoryIntToStr($_SESSION["IMPORT_CATEGORY"]["category_id"])."</strong></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td $td1>{force}:</td>";
    $html[]="<td><strong>{$_SESSION["IMPORT_CATEGORY"]["ForceCat"]}</strong></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td colspan=2 style='padding-top:10px;text-align:right'>".$tpl->button_upload("{upload_a_file}",$page,null,$addon)."</td>";
    $html[]="</tr>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function import_form(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];

    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0  AND meta=0 order by categoryname ";
    if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";}
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error</div>";
        return;
    }


    while ($ligne = pg_fetch_assoc($results)) {
        $category_id = $ligne["category_id"];
        $categoryname = $ligne["categoryname"];
        if (preg_match("#^reserved#", $categoryname)) {
            continue;
        }
        $text_category = null;
        $HASH[$category_id] = $categoryname;
    }

    $tpl->field_hidden("import-category","yes");
    $form[]=$tpl->field_array_hash($HASH, "category_id", "{category}", 0);
    $form[]=$tpl->field_checkbox("ForceCat","{force}",0,false,"{category_inject_force_explain}");
    $form[]=$tpl->field_checkbox("ForceExt","{no_extension_check}",0,false,"{free_cat_no_extension_check_explain}");
    $explain="{import_websites_categories_explain}";

    $functionafter=null;
    if(strlen($function)>4){
        $functionafter=";".$function."();";
    }

    $jsafter="LoadAjax('category-import-form','$page?import-button=yes');$functionafter";

    // Pentest 2026-11-04 : cname etait recopie sans encodage dans la page (XSS).
    echo $tpl->form_outside("{import}&nbsp;".req_html($_GET["cname"] ?? ""), @implode("\n", $form),$explain,"{next}",$jsafter);

}


function popup():bool{
    $page=CurrentPageName();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $category_requested=0;if(isset($_GET["category_requested"])){ $category_requested=intval($_GET["category_requested"]);}
    $cname="";
    if(isset($_GET["cname"])){ $cname=$_GET["cname"];}

    // Pentest 2026-11-04 : cname etait recopie sans encodage dans la page (XSS).
    $url="$page?popup2=yes&cname=".req_url($cname)."&category_requested=$category_requested&function=$function";

    echo "<div id='popup-categorize'></div>\n";
    echo "<script>";
    echo "LoadAjax('popup-categorize','$url');";
    echo "</script>";
    return true;

}

/**
 * Onglet "Categoriser" (maquette stitch_testeur_de_cat_gories_artica0) : ajout
 * manuel de sites a une categorie. Formulaire propre a la page (deux colonnes),
 * poste vers save() comme l'ancien form_outside ; succes = reponse vide, puis
 * recompilation des categories avec sa barre de progression.
 */
function popup2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $cname=(string)($_GET["cname"] ?? "");
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $category_requested=intval($_GET["category_requested"] ?? 0);
    $err="";
    list($mine,$official)=cz_categories($err);
    if($err!==""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err)));
        return false;
    }

    $t=time();
    $url="$page?popup2=yes&cname=".urlencode($cname)."&category_requested=$category_requested&function=$function";
    $functionafter=strlen($function)>4?";".$function."();":"";
    $jsafter=$tpl->framework_buildjs("/categories/memory/run/all",
        "categorize.progress","categorize.progress.log","progress-$t",
        "LoadAjax('popup-categorize','$url');$functionafter");

    $html=array();
    $html[]="<div class='ak cz-wrap'>";
    $html[]="<div class='cz-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-tag'></i></span><div>";
    $html[]="<h2>{add_websites}".($cname!==""?" <span class='cz-cname'>".htmlspecialchars($cname)."</span>":"")."</h2>";
    $html[]="<p>{cz_subtitle}</p></div></div>";
    $html[]="<div class='cz-cols'>";
    $html[]=cz_sites_column();
    $html[]=cz_options_column($mine,$official,$category_requested);
    $html[]="</div>";
    $html[]=cz_footer($t);
    $html[]="</div>";

    echo cz_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).cz_js($page,$t,$jsafter);
    return true;
}

/**
 * Categories proposees comme destination : les personnelles, plus les officielles
 * quand leur gestion est activee. Partage par les onglets Categoriser et Masse.
 */
function cz_categories(&$err):array{
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0  AND meta=0 order by categoryname ";
    if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";}
    $results=$q->QUERY_SQL($sql);
    $mine=array();$official=array();
    if(!$q->ok){$err=(string)$q->mysql_error;return array($mine,$official);}
    while($ligne=pg_fetch_assoc($results)){
        $id=intval($ligne["category_id"]);
        $name=(string)$ligne["categoryname"];
        if($id==234){$name="CloudFlare";}
        if(preg_match("#^reserved#",$name)){continue;}
        if(intval($ligne["official_category"] ?? 0)==1){$official[$id]=$name;}else{$mine[$id]=$name;}
    }
    return array($mine,$official);
}

/** Colonne gauche : la liste des sites, son compteur et l'aide de saisie. */
function cz_sites_column():string{
    $tpl=new template_admin();
    $ph=htmlspecialchars($tpl->javascript_parse_text("{cz_sites_placeholder}"),ENT_QUOTES);
    $h="<div class='cz-col'>";
    $h.="<div class='cz-label-row'><label class='cz-label' for='cz-sites'>{websites} <span class='cz-req'>*</span></label><span class='cz-hint'>{cz_shortcut}</span></div>";
    $h.="<p class='cz-sub'>{cz_sites_help}</p>";
    $h.="<div class='cz-editor'><textarea id='cz-sites' class='cz-sites' spellcheck='false' placeholder=\"$ph\" oninput='CzCount()' onkeydown='CzKey(event)'></textarea>";
    $h.="<div class='cz-status'><span><i class='fas fa-globe'></i> <span id='cz-count'></span></span><span id='cz-warn' class='ak-pill ak-warn' style='display:none'></span></div></div>";
    $h.="<div class='cz-help'><span class='cz-help-ico'><i class='fas fa-circle-info'></i></span><ul>";
    $h.="<li>{cz_help_1}</li><li>{cz_help_2}</li><li>{cz_help_3}</li></ul></div>";
    $h.="</div>";
    return $h;
}

/** Colonne droite : categorie de destination, mode d'ajout, domaines sans extension. */
function cz_options_column($mine,$official,$requested):string{
    $tpl=new template_admin();
    $h="<div class='cz-col'>";
    $h.="<label class='cz-label' for='cz-category'>{cz_category_dest} <span class='cz-req'>*</span></label>";
    $all=$mine+$official;
    if($requested>0){
        $name=isset($all[$requested])?$all[$requested]:"#$requested";
        $h.="<div class='cz-fixedcat'><span class='ak-ico ak-ico-sq cz-ico-sm'><i class='fas fa-tag'></i></span><span>".htmlspecialchars($name)."</span></div>";
        $h.="<input type='hidden' id='cz-category' value='".intval($requested)."'>";
    }else{
        $ph=htmlspecialchars($tpl->javascript_parse_text("{cz_filter_placeholder}"),ENT_QUOTES);
        $h.="<input type='text' class='cz-filter' placeholder=\"$ph\" oninput='CzFilter(this.value)' aria-label=\"$ph\">";
        $h.="<select id='cz-category' class='cz-select' size='7' onchange='CzOptChanged()'>";
        $h.=cz_optgroup("{cz_my_categories}",$mine);
        $h.=cz_optgroup("{cz_official_categories}",$official);
        $h.="</select>";
    }

    $h.="<fieldset class='cz-mode'><legend class='cz-label'>{cz_mode}</legend>";
    $h.="<label class='cz-radio cz-radio-on'><input type='radio' name='cz-force' value='0' checked onchange='CzMode()'>";
    $h.="<span class='cz-radio-body'><span class='cz-radio-title'><i class='fas fa-shield-halved'></i> {cz_mode_check} <span class='ak-tag ak-ok'>{cz_recommended}</span></span>";
    $h.="<span class='cz-radio-text'>{cz_mode_check_explain}</span></span></label>";
    $h.="<label class='cz-radio'><input type='radio' name='cz-force' value='1' onchange='CzMode()'>";
    $h.="<span class='cz-radio-body'><span class='cz-radio-title'><i class='fas fa-bolt'></i> {cz_mode_force}</span>";
    $h.="<span class='cz-radio-text'>{cz_mode_force_explain}</span></span></label>";
    $h.="</fieldset>";

    $h.="<label class='cz-check'><input type='checkbox' id='cz-noext'>";
    $h.="<span><span class='cz-radio-title'>{cz_noext}</span><span class='cz-radio-text'>{cz_noext_explain}</span></span></label>";
    $h.="</div>";
    return $h;
}

/** Un groupe d'options de la liste des categories (omis s'il est vide). */
function cz_optgroup($label,$items):string{
    if(count($items)==0){return "";}
    $h="<optgroup label=\"$label\">";
    foreach($items as $id=>$name){$h.="<option value='".intval($id)."'>".htmlspecialchars($name)."</option>";}
    return $h."</optgroup>";
}

/** Pied : resume dynamique, bouton, progression et erreur. */
function cz_footer($t):string{
    $h="<div class='cz-foot'><span id='cz-summary' class='cz-summary'></span>";
    $h.="<button type='button' id='cz-submit' class='ak-btn ak-btn-primary ak-btn-lg' onclick='CzSubmit()' disabled><i class='fas fa-plus'></i> {add_websites}</button></div>";
    $h.="<div id='cz-error' class='ak-banner ak-err cz-error' role='alert' style='display:none'></div>";
    $h.="<div id='progress-$t' class='cz-progress'></div>";
    return $h;
}

/** JS du formulaire : compteur, filtre de categories, envoi vers save(). */
function cz_js($page,$t,$jsafter):string{
    $tpl=new template_admin();
    $tx=array();
    foreach(array("cz_count","cz_suspicious","cz_summary","cz_summary_empty") as $k){
        $tx[$k]=json_encode($tpl->javascript_parse_text("{".$k."}"));
    }
    return cz_shared_js()."<script>
function CzLines(){
  var v=document.getElementById('cz-sites').value.replace(/,/g,'\\n').split(/\\r?\\n/);
  var ok=0, bad=0;
  for(var i=0;i<v.length;i++){
    var l=v[i].trim(); if(l===''){continue;}
    if(/[*+\\s]/.test(l)){bad++;}else{ok++;}
  }
  return {ok:ok,bad:bad};
}
function CzCount(){
  var n=CzLines(), cat=CzCatName();
  document.getElementById('cz-count').textContent={$tx["cz_count"]}.replace('%s',n.ok);
  var w=document.getElementById('cz-warn');
  if(n.bad>0){ w.style.display=''; w.textContent={$tx["cz_suspicious"]}.replace('%s',n.bad); }else{ w.style.display='none'; }
  var ready=(n.ok>0 && cat!=='');
  document.getElementById('cz-summary').textContent= ready ? {$tx["cz_summary"]}.replace('%s',n.ok).replace('%c',cat) : {$tx["cz_summary_empty"]};
  document.getElementById('cz-submit').disabled=!ready;
}
function CzOptChanged(){ CzCount(); }
function CzKey(e){ if(e.ctrlKey && e.keyCode==13){ e.preventDefault(); CzSubmit(); } }
function CzSubmit(){
  var b=document.getElementById('cz-submit'); if(b.disabled){return;}
  var err=document.getElementById('cz-error'); err.style.display='none';
  var force=document.querySelector('input[name=cz-force]:checked');
  b.disabled=true;
  $.post('$page',{
    logid:'$t',
    category_id:encodeURIComponent(document.getElementById('cz-category').value),
    ForceCat:force?force.value:'0',
    ForceExt:document.getElementById('cz-noext').checked?'1':'0',
    websites:encodeURIComponent(document.getElementById('cz-sites').value)
  }).done(function(r){
    r=String(r).trim();
    if(r.length>3){
      if(r.indexOf('jserror:')===0){r=r.substring(8);}
      err.textContent=r; err.style.display=''; b.disabled=false; return;
    }
    $jsafter
  }).fail(function(x){ err.textContent='HTTP '+x.status; err.style.display=''; b.disabled=false; });
}
NoSpinner();
CzCount();
</script>";
}

/** JS commun a la colonne Destination et options (nom de categorie, filtre, cartes radio). */
function cz_shared_js():string{
    return "<script>
function CzCatName(){
  var s=document.getElementById('cz-category'); if(!s){ return ''; }
  if(s.tagName==='SELECT'){ return s.selectedIndex>=0 ? s.options[s.selectedIndex].text : ''; }
  var f=document.querySelector('.cz-fixedcat span:last-child'); return f?f.textContent:'';
}
function CzFilter(q){
  q=q.toLowerCase(); var s=document.getElementById('cz-category');
  for(var i=0;i<s.options.length;i++){ var o=s.options[i]; o.style.display=(o.text.toLowerCase().indexOf(q)>=0)?'':'none'; }
}
function CzMode(){
  var r=document.querySelectorAll('.cz-radio');
  for(var i=0;i<r.length;i++){ r[i].className='cz-radio'+(r[i].querySelector('input').checked?' cz-radio-on':''); }
}
</script>";
}

/** CSS du kit de l'onglet (une fois par requete). */
function cz_style():string{
    if(isset($GLOBALS["CZ_STYLE_DONE"])){return "";}
    $GLOBALS["CZ_STYLE_DONE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.cz-wrap{margin-top:16px}
.cz-head{display:flex;align-items:flex-start;gap:14px;padding-bottom:16px;margin-bottom:20px;border-bottom:1px solid #f1f5f9}
.cz-head h2{font-size:20px;font-weight:600;margin:0;color:#111d23}
.cz-head p{font-size:14px;color:#3d4947;margin:4px 0 0 0}
.cz-cname{color:#006a62}
.cz-ico-sm{flex-basis:28px;width:28px;height:28px;font-size:13px}
.cz-cols{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:28px}
.cz-col{display:flex;flex-direction:column;min-width:0}
.cz-label-row{display:flex;align-items:baseline;justify-content:space-between;gap:12px}
.cz-label{font-size:15px;font-weight:600;color:#111d23;margin:0 0 4px 0;display:block;padding:0}
.cz-req{color:#ba1a1a}
.cz-hint{font-size:12px;color:#6d7a77}
.cz-sub{font-size:13px;color:#3d4947;margin:0 0 10px 0}
.cz-editor{border:1px solid #cbd5e1;border-radius:10px;overflow:hidden;background:#fff}
.cz-editor:focus-within{border-color:#006a62;box-shadow:0 0 0 3px $soft}
.cz-sites{display:block;width:100%;height:280px;border:0;outline:0;resize:vertical;padding:12px 14px;font-family:monospace;font-size:14px;line-height:1.7;color:#111d23;background:#fff}
.cz-status{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:13px;color:#3d4947;font-weight:600}
.cz-status i{color:#006a62}
.cz-help{display:flex;gap:12px;margin-top:14px;padding:14px 16px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc}
.cz-help-ico{color:#006a62;font-size:15px;margin-top:1px}
.cz-help ul{margin:0;padding-left:16px;font-size:13px;color:#3d4947;line-height:1.7}
.cz-filter{width:100%;height:36px;border:1px solid #cbd5e1;border-bottom:0;border-radius:8px 8px 0 0;padding:0 12px;font-size:13px;color:#111d23;outline:0}
.cz-filter:focus{border-color:#006a62}
.cz-select{width:100%;border:1px solid #cbd5e1;border-radius:0 0 8px 8px;padding:4px;font-size:14px;color:#111d23;outline:0;background:#fff}
.cz-select:focus{border-color:#006a62;box-shadow:0 0 0 3px $soft}
.cz-select optgroup{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6d7a77}
.cz-select option{font-size:14px;font-weight:400;text-transform:none;letter-spacing:0;color:#111d23;padding:4px 8px}
.cz-fixedcat{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:15px;font-weight:600}
.cz-mode{border:0;margin:20px 0 0 0;padding:0;min-width:0}
.cz-mode legend{border:0;width:auto;margin-bottom:8px}
.cz-radio{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1px solid #e2e8f0;border-radius:10px;margin:0 0 10px 0;cursor:pointer;font-weight:400;transition:border-color .15s,background .15s}
.cz-radio:not(.cz-radio-on):hover{border-color:#cbd5e1}
.cz-radio-on{border:2px solid #006a62;background:#fbfefd;padding:13px 15px}
.cz-radio input,.cz-check input{margin-top:3px;accent-color:#006a62;width:16px;height:16px;flex:0 0 auto}
.cz-radio-body,.cz-check>span{display:flex;flex-direction:column;gap:3px}
.cz-radio-title{font-size:14px;font-weight:600;color:#111d23;display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap}
.cz-radio-title i{color:#006a62}
.cz-radio-text{font-size:13px;color:#3d4947;line-height:1.5}
.cz-check{display:flex;align-items:flex-start;gap:12px;margin:8px 0 0 0;cursor:pointer;font-weight:400}
.cz-radio input:focus-visible,.cz-check input:focus-visible,.cz-sites:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.cz-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:24px;padding-top:16px;border-top:1px solid #f1f5f9}
.cz-summary{font-size:14px;color:#3d4947}
.cz-foot .ak-btn[disabled]{opacity:1;border-color:#cbd5e1;background:#cbd5e1;color:#6d7a77;cursor:not-allowed}
.cz-error{margin-top:12px;font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere}
.cz-progress{margin-top:10px}
</style>";
}

function save(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $websites=$_POST["websites"];
    $category_id=$_POST["category_id"];
    $forcecat=$_POST["ForceCat"];
    $forceext=$_POST["ForceExt"];


    $ARRAY["websites"]=$websites;
    $ARRAY["category_id"]=$category_id;
    $ARRAY["ForceCat"]=$forcecat;
    $ARRAY["ForceExt"]=$forceext;
    $ARRAY["SESSIONID"]=$_SESSION["uid"];

    $sock=new sockets();
    $json=json_decode($sock->REST_API_POST("/categories/memory/prepare",$ARRAY));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
}

function test_bulk_uploaded(){
    if(!fw_progress_page_priv("AsDansGuardianAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=(string)($_GET["bulk-uploaded"] ?? "");
    // A file the upload handler just wrote in conf/upload: its plain name, nothing else.
    if($file==="" || $file!==basename($file) || !is_file(dirname(__FILE__)."/ressources/conf/upload/$file")){
        return fw_progress_bad_value("bulk-uploaded");
    }
    $md5=md5($file);
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/categorize.$md5.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/categorize.$md5.logs.txt";
    $ARRAY["CMD"]="/categories/bulktest/$md5?file=".rawurlencode($file);
    $ARRAY["TITLE"]="{test_categories} ({bulk})";
    $ARRAY["AFTER"]="Loadjs('$page?test-bulk-results=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-bulk-categorize')";
    echo $jsafter;
}

function file_uploaded():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    // basename() : le nom vient de l'URL et le demon LIT le fichier designe ; un
    // "../../../etc/shadow" aurait ete charge dans une categorie.
    $file=basename(str_replace("\\","/",(string)$_GET["file-uploaded"]));
    $category_id=intval($_GET["category_id"]);
    $forcecat=intval($_GET["ForceCat"]);
    $ForceExt=intval($_GET["ForceExt"]);
    // Finit dans du JavaScript : nom de fonction seulement.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if($file==="" || $file==="." || $file===".."){
        echo $tpl->js_error("{imp_upload_failed}");
        return false;
    }
    if(strlen($function)>2){
        $function="$function()";
    }

    $filepath = dirname(__FILE__) . "/ressources/conf/upload/$file";
    $ARRAY["websites"]=$filepath;
    $ARRAY["category_id"]=$category_id;
    $ARRAY["ForceCat"]=$forcecat;
    $ARRAY["ForceExt"]=$ForceExt;
    $ARRAY["SESSIONID"]=$_SESSION["uid"];

    $sock=new sockets();
    $json=json_decode($sock->REST_API_POST("/categories/memory/prepare",$ARRAY));
    if(!is_object($json) || !$json->Status){
        // L'ecran d'import (ImpDone) doit sortir de son etat "en cours".
        if($function!==""){echo "if(typeof ImpDone==='function'){ImpDone();}\n";}
        echo $tpl->js_error(is_object($json)?$json->Error:"{error}");
        return false;
    }
    header("content-type: application/x-javascript");
    echo $tpl->framework_buildjs("/categories/memory/run/$category_id",
        "$category_id.progress","categorize.manu.$category_id.log","import-categories-div-progress", $function, $function===""?null:$function);


    return admin_tracks("Launching categorize after uploaded $file for category $category_id");
}
function browser_plugin():bool{

    $tpl=new template_admin();
    $EnableBrowserPlugin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBrowserPlugin"));
    $BrowserPluginPortNum=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BrowserPluginPortNum"));
    $BrowserPluginApiKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BrowserPluginApiKey");
    $BrowserPluginUseSSL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BrowserPluginUseSSL");
    $html[]="<p>";
    // Le titre d'un BigCircleCheckbox est rendu en texte brut : le long paragraphe
    // {enable_browser_plugin_port} y affichait ses <br>. Il passe en explication (HTML),
    // sous un titre court ; {enable_browser_plugin_explain} n'avait jamais ete declare.
    $html[]=$tpl->BigCircleCheckbox("BBPlugin:1|EnableBrowserPlugin","{enable_browser_plugin}","{enable_browser_plugin_port}",$EnableBrowserPlugin);

    $html[]=$tpl->BigCircleCheckbox("BBPlugin:1|BrowserPluginUseSSL","{useSSL}","{browser_plugin_ssl_explain}",$BrowserPluginUseSSL);

    if(strlen($BrowserPluginApiKey)<3){
        $BrowserPluginApiKey=generateApiKey();
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BrowserPluginApiKey",$BrowserPluginApiKey);
    }

    if($BrowserPluginPortNum==0){
        $BrowserPluginPortNum=9203;
    }

    $html[]=$tpl->BigIntegerField("BBPlugin:1|BrowserPluginPort","{listen_port}","{listen_port_explain}",$BrowserPluginPortNum,null,1,65535);



    $html[]=$tpl->BigTextField("BBPlugin:1|BrowserPluginAPI","{API_KEY}","{browser_plugin_api_key}",$BrowserPluginApiKey);


    $html[]="</p>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;

}
function browser_plugin_save():bool{
    unset($_POST["BBPlugin"]);
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/browsercat/reload");
    return admin_tracks_post("Saving BrowserPlugin feature settings");

}
function generateApiKey(): string
{
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $groupLength = 3;
    $groups = 4;
    $key = [];

    for ($g = 0; $g < $groups; $g++) {
        $group = '';
        for ($i = 0; $i < $groupLength; $i++) {
            $group .= $letters[random_int(0, strlen($letters) - 1)];
        }
        $key[] = $group;
    }

    return implode('-', $key);
}