<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["CategorizePayit"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["CategoriesResolvedCache"])){save();exit;}
if(isset($_GET["not-categorized"])){not_categorized_table();exit;}
if(isset($_GET["users-requests-delete"])){users_requests_delete();exit;}
if(isset($_GET["categories-list-js"])){categories_list_js();exit;}
if(isset($_GET["categories-list-popup"])){categories_list_popup();exit;}

if(isset($_GET["users-requests-table"])){users_requests_table();exit;}
if(isset($_GET["not-categorized-head"])){not_categorized_head();exit;}
if(isset($_GET["not-categorized-table"])){not_categorized_table();exit;}
if(isset($_GET["categorize-this-js"])){not_categorized_categorize_js();exit;}
if(isset($_GET["categorize-this"])){not_categorized_categorize_popup();exit;}
if(isset($_POST["categorize"])){not_categorized_categorize_save();exit;}
if(isset($_GET["search"])){not_categorized_search();exit;}
if(isset($_GET["not-categorized-delete"])){not_categorized_categorize_delete();exit;}
if(isset($_GET["not-categorized-scan"])){not_categorized_scan();exit;}
if(isset($_GET["not-categorized-catz"])){not_categorized_catz();exit;}
if(isset($_GET["import-list-js"])){import_list_js();exit;}
if(isset($_GET["import-list-popup"])){import_list_popup();exit;}
if(isset($_POST["import-list"])){import_list_save();exit;}
if(isset($_GET["groupby"])){status_groupby();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{categorisation}",
        "fas fa-book","{use_categories_for}","$page?tabs=yes","categorisation","progress-catz-restart",false,"table-loader-catz-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function not_categorized_categorize_delete():bool{
    header("content-type: application/x-javascript");
	// Le domaine partait BRUT dans une expression reguliere PostgreSQL et le
	// jeton $md BRUT dans le JavaScript renvoye. $md est toujours un md5.
	$familysite=pg_escape_string2((string)$_GET["not-categorized-delete"]);
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["num"] ?? ""));
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain ~'$familysite'");
	echo "$('#$md').remove();";
    echo "$('#des_$md').remove();";
    return true;
}
function not_categorized_catz():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    // Le domaine cible partait BRUT dans quatre requetes (deux DELETE, un
    // DELETE par expression reguliere, un INSERT) et $md BRUT dans le
    // JavaScript renvoye.
    $familysite=$_GET["not-categorized-catz"];
    $domain=pg_escape_string2((string)$_GET["domain"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $tpl=new template_admin();
    $qlproxy=new mysql_squid_builder();
    if(preg_match("#^(www|mail)\.(.+)#",$familysite,$re)){
        $familysite=$re[2];
    }
    $FORCE=false;
    $q=new postgres_sql();
    $oldcatz=$qlproxy->GET_CATEGORIES($familysite);
    if($oldcatz==5){
        $q->QUERY_SQL("DELETE FROM category_advertising WHERE sitename='$domain'");
        $FORCE=true;
    }

    if($oldcatz==82){
        $q->QUERY_SQL("DELETE FROM category_internal WHERE sitename='$domain'");
        $FORCE=true;
    }



    // L'identifiant de categorie etait transmis brut a categorize() alors qu'il
    // etait deja converti en entier dix lignes plus bas: une seule conversion,
    // au plus tot.
    $category_id=intval($_GET["category"]);
    if(!$qlproxy->categorize($familysite, $category_id,$FORCE)){
        echo $tpl->js_error("{failed} $qlproxy->mysql_error");
        return false;
    }
    $uid=pg_escape_string2((string)$_SESSION["uid"]);
    if($uid=="-100"){
        $uid="Manager";
    }

    $mem=new lib_memcached();
    $mem->saveKey("CATEGORY:$qlproxy->finaldomain", $category_id,86400);
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain = '$domain'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain ~ '$domain'");
    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category_id);
    // Le nom de domaine est ecrit dans une chaine JavaScript simple-quote: une
    // apostrophe suffisait a rompre l'instruction renvoyee.
    $famjs=addslashes(htmlspecialchars($familysite));
    echo "document.getElementById('fam-$md').innerHTML='<span style=\"color:red\">$famjs OK ADDED TO $categoryname</span>';\n";

    $sql="CREATE TABLE IF NOT EXISTS categorize_logs (
            domain varchar(255) PRIMARY KEY,
            category_id INT,
            created TIMESTAMP,
            uid varchar(128)
    )";

    $q->QUERY_SQL($sql);
    $date=date("Y-m-d H:i:s");
    $sql="INSERT INTO categorize_logs (domain,category_id,created,uid) 
   VALUES('$domain',$category_id,'$date','$uid')";
    $q->QUERY_SQL($sql);

    echo "Loadjs('$page?not-categorized-head=yes');\n";
    echo "setTimeout(\"$('#$md').remove()\",1000);";
    echo "setTimeout(\"$('#des_$md').remove()\",1000);";
    return true;
}
function import_list_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{import}: {domains}", "$page?import-list-popup=yes");
    return true;
}
function import_list_popup():bool{
    $tpl=new template_admin();
    $form[]=$tpl->field_textareacode("import-list",null,"");
    echo $tpl->form_outside(null,$form,null,"{import}","dialogInstance1.close();");
    return true;

}
function import_list_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("\n",$_POST["import-list"]);
    $c=0;
    $q=new postgres_sql();
    foreach ($tb as $domain){
        if(strpos("   $domain", '"')>0){continue;}
        if(strpos("   $domain", "{")>0){continue;}
        if(strpos("   $domain", "<")>0){continue;}
        if(strpos("   $domain", ">")>0){continue;}
        if(strpos("   $domain", "!")>0){continue;}
        if(strpos("   $domain", "}")>0){continue;}
        if(strpos("   $domain", ",")>0){continue;}
        if(strpos("   $domain", ";")>0){continue;}
        if(strpos("   $domain", ":")>0){continue;}
        if(strpos("   $domain", "#")>0){continue;}
        if(preg_match("#^[0-9\.]+$#",$domain)){continue;}
        $zdate=date("Y-m-d H:i:s");
        // La liste de caracteres refuses juste au-dessus ne contenait ni
        // l'apostrophe ni l'antislash: une ligne collee dans le champ d'import
        // suffisait a detourner l'INSERT.
        $domain=pg_escape_string2(trim((string)$domain));
        if($domain===""){continue;}
        $c++;
        $q->QUERY_SQL("INSERT INTO cloud_categorize(domain,created) VALUES ('$domain','$zdate') ON CONFLICT DO NOTHING");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
    }
    if($c==0){
        echo $tpl->post_error("Nothing imported");
    }

    return true;

}
function not_categorized_scan(){
    // Le domaine partait BRUT dans le DELETE et dans les trois chaines
    // JavaScript renvoyees; $md est toujours un md5.
    $familysite=$_GET["not-categorized-scan"];
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $famjs=addslashes(htmlspecialchars($familysite));
    $catz=new mysql_catz();
    $category=$catz->GET_CATEGORIES($familysite);
    if($category>0){
        $q=new postgres_sql();
        $famsql=pg_escape_string2((string)$familysite);
        $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$famsql'");
        $categoryname=$catz->CategoryIntToStr($category);
        echo "document.getElementById('fam-$md').innerHTML='$famjs <span style=\"color:red\">$categoryname</span>';\n";
        echo "setTimeout(\"$('#$md').remove()\",1000);\n";
        echo "setTimeout(\"$('#des_$md').remove()\",1000);";
        return;
    }

    $r = new Net_DNS2_Resolver(array('nameservers' => array("8.8.8.8","1.1.1.1"), "timeout" => 1));


    try {
        $result = $r->query("www.$familysite");

    } catch(Net_DNS2_Exception $e) {
        $message=$e->getMessage();
        if(stripos("  $message","referenced in the query does not exist")>0){
            echo "document.getElementById('fam-$md').innerHTML='$famjs <span style=\"color:red\">No DNS</span>';\n";

        }
        return;
    }
    echo "document.getElementById('fam-$md').innerHTML='$famjs {$result->answer[0]->address}';";



}

function table():bool{
	$tpl=new template_admin();
	$security="AsSquidAdministrator";


    $CategorizePayit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizePayit"));
    $CategorizePayBySite=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizePayBySite");
    $CategorizeCurrency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizeCurrency");
    $CategorizeRandom=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizeRandom");

    if($CategorizePayBySite==null){$CategorizePayBySite="0.5";}
    if($CategorizeCurrency==null){$CategorizeCurrency="Euro";}


    $form[]=$tpl->field_checkbox("CategorizeRandom","{randomized_list}",$CategorizeRandom);

	$form[]=$tpl->field_checkbox("CategorizePayit","{calculate_compensation}",$CategorizePayit,"CategorizePayBySite,CategorizeCurrency");
    $form[]=$tpl->field_text("CategorizePayBySite","{CategorizePayBySite}",$CategorizePayBySite);
    $form[]=$tpl->field_text("CategorizeCurrency","{currency}",$CategorizeCurrency);
	$html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",null,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function Save():bool{
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
    return true;
}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $users=new usersMenus();
    if($users->AsDansGuardianAdministrator OR $users->AsSquidAdministrator) {
        $array["{status}"] = "$page?status=yes";
    }

    $array["{not_categorized}"]="$page?not-categorized=yes";


    if($users->AsDansGuardianAdministrator OR $users->AsSquidAdministrator) {
        $array["{parameters}"] = "$page?table=yes";
    }


	echo $tpl->tabs_default($array);
    return true;
}

function status():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();


    /*
     * A voir, ancienne table dans PostGreSQL : not_categorized, visits
     */

    $q=new postgres_sql();
    $not_categorized=FormatNumber($q->COUNT_ROWS("cloud_categorize"));

    $sql="CREATE TABLE IF NOT EXISTS categorize_logs (
            domain varchar(255) PRIMARY KEY,
            category_id INT,
            created TIMESTAMP,
            uid varchar(128)
    )";
    $q->QUERY_SQL($sql);


	$widget1=$tpl->widget_jaune("{not_categorized}",$not_categorized);
	// Mise en page en flex. Les cellules etaient figees a 355px alors qu'une
	// tuile de service en mesure 335: la colonne de droite, qui recoit le
	// classement par contributeur, etait rognee des que le tableau depassait
	// trois colonnes.
	$html[]="<div style='display:flex;flex-wrap:wrap;gap:15px;align-items:flex-start;margin-top:20px'>";
	$html[]="<div style='flex:0 0 auto'>$widget1</div>";
	$html[]="<div style='flex:1 1 420px;min-width:0' id='cached-websites-catz'></div>";
	$html[]="</div>";
    $html[]="<script>";
    $html[]="LoadAjaxTiny('cached-websites-catz','$page?groupby=yes')";
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}

function not_categorized_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null);
    return true;
}
function not_categorized_search():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Artica v4.x Infrastructure Appliances%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE 'Buy and Sell Domain Names%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%This domain was registered with%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%Ce domaine est peut%vendre%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%The domain name % is for sale%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%This domain name has been registered%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%is available for sale or other proposals%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Hosted By One.com%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%domain name % is available for sale%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '% is a premium domain name for sale%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%This domain has expired%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%Domeinnaam registreren %'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE description LIKE '%OVHcloud accompagne votre % au meilleur des infrastructures web%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Webmail Login%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Coming Soon%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%cPanel Login%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Parked Domain name%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%This site is under development%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Under Construction%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%under construction%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Site en construction%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Sitio web en construcc%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Domain parking page%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%This domain was registered with %')");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Dominio registrado en %')");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '% Domain registered at %'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%- Registered at Namecheap%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%.% - Now for sale%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%This Domain % For Sale%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%.% has been registered%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Ressources et information concernant % Resources and Information%'");

    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Under construction%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Dit domein is gereserveerd%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '%Your Internet Address For Life%'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE title LIKE '% is for sale%'");

    // La saisie partait BRUTE dans trois LIKE PostgreSQL: une apostrophe
    // suffisait a detourner la requete.
    $CategorizeRandom=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizeRandom");
    $ORDER="created ASC";
    if($CategorizeRandom==1){
        $ORDER="random()";
    }
    $search=pg_escape_string2("*".trim((string)($_GET["search"] ?? ""))."*");
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);

    // La branche sans filtre etait syntaxiquement fausse ("FROM
    // cloud_categorize and rscan=1", sans WHERE). Elle n'etait jamais atteinte
    // -- $search vaut au minimum "%" -- mais elle aurait echoue.
    $sql="SELECT * FROM cloud_categorize WHERE rscan=1 ORDER BY $ORDER LIMIT 250";
    if($search<>null){
        $sql="SELECT * FROM cloud_categorize WHERE (domain LIKE '$search' OR title LIKE '$search' OR description LIKE '$search') and rscan=1 ORDER BY $ORDER LIMIT 250";
    }

	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        echo "<script>Loadjs('$page?not-categorized-head=yes');</script>";
        return false;
    }

	$fam=new familysite();
	$rows=array();
	while ($ligne = pg_fetch_assoc($results)) {
		$rows[]=catz_notcat_row($ligne,$fam,$page,$tpl);
	}
	$shown=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{domains}</th>";
	$html[]="<th>{categories}</th>";
	$html[]="<th>{date}</th>";
	$html[]="<th class='center'>{actions}</th>";
	$html[]="</tr></thead><tbody id='catz-nc-rows'>";
	if($shown==0){
		$html[]="<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown</span>";
	$html[]="<span class='awc-pg' id='catz-nc-pager'></span>";
	$html[]="</div></div></div>";

	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	Loadjs('$page?not-categorized-head=yes');
	</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"catz-nc-rows","catz-nc-pager");
    return true;
}

/** Une pastille de categorisation rapide: un clic classe le domaine. */
function catz_notcat_chip($label,$bg,$js):string{
	return "<a href=\"javascript:blur();\" style='text-decoration:none' OnClick=\"$js\">"
		."<span class='awc-badge' style='background:$bg'>$label</span></a>";
}

/**
 * Un domaine en attente de categorisation. Le titre et la description de la
 * page occupaient jusqu'ici une SECONDE ligne de tableau (<tr id="des_...">)
 * qui doublait la hauteur du tableau et cassait l'alternance des couleurs; ils
 * passent en sous-titre de l'identite. Les deux lignes gardent leur identifiant
 * -- les gestionnaires de suppression retirent toujours "#$md" ET "#des_$md" --
 * mais la seconde n'existe plus: retirer un identifiant absent est sans effet
 * en jQuery.
 */
function catz_notcat_row($ligne,$fam,$page,$tpl):string{
	$style='font-size:14px';
	$domain=$ligne["domain"];
	$md=md5(serialize($ligne));
	if(preg_match("#^(www|ww|mx|admin|magento|hostmaster|ns|ns1|webmail|mail|bbs|cpanel|webdisk|autodiscover|smtp|cpcalendars|cpcontacts)\.(.+)#",$domain,$re)){
		$domain=$re[2];
	}
	$numenc=urlencode($domain);
	$tooldoms=$numenc;
	$xfam=$fam->GetFamilySites($domain);
	$domain_url=$tpl->td_href("<span class='awc-name'>".htmlspecialchars($domain)."</span>","{categorize}",
		"Loadjs('$page?categorize-this-js=$numenc&md=$md&domain=".urlencode($domain)."');");

	if($xfam<>$domain){
		$tooldoms=urlencode($xfam);
		$domain_url=$domain_url."&nbsp;|&nbsp;".$tpl->td_href(
			"<span class='awc-tag'>".htmlspecialchars($xfam)."</span>","{categorize}",
			"Loadjs('$page?categorize-this-js=$numenc&md=$md&domain=".urlencode($xfam)."');");
	}

	$www=null;
	if($domain==$xfam){
		if(!preg_match("#^www\.#",$domain)){$www="www.";}
	}
	// L'URL est ecrite dans une chaine JavaScript simple-quote: un domaine
	// contenant une apostrophe (ils existent dans cloud_categorize, la table est
	// alimentee par les journaux du proxy) rompait l'appel a s_PopUpFull.
	$link=addslashes("http://$www$domain");
	$visits=$tpl->td_href("<i class='fa fa-link'></i>&nbsp;{view}",null,"s_PopUpFull('$link','1024','900')");
	$sezarch="https://www.google.com/search?channel=fs&client=ubuntu&q=%22$tooldoms%22";
	$google=$tpl->td_href("<i class='fa fa-link'></i>&nbsp;Google",null,"s_PopUpFull('$sezarch','1024','900')");

	// Les onze raccourcis de categorisation. Chaque entree: libelle, fond,
	// identifiant de categorie.
	$quick=array(
		array("Reaffected",   "#ba1a1a", 112),
		array("Tracker",      "#865300", 143),
		array("Computing",    "#006a62", 126),
		array("Travel",       "#26a69a", 119),
		array("Movies",       "#263238", 100),
		array("Music",        "#26a69a", 101),
		array("Industry",     "#865300",  81),
		array("Governments",  "#1c84c6",  62),
		array("Job Training", "#006a62",  86),
		array("Phishing",     "#865300", 105),
		array("Suspicious",   "#7d1218", 140),
	);
	$chips=array();
	foreach($quick as $c){
		$chips[]=catz_notcat_chip($c[0],$c[1],
			"Loadjs('$page?not-categorized-catz=$numenc&md=$md&category={$c[2]}&domain=$tooldoms')");
	}

	$delete=$tpl->icon_delete("Loadjs('$page?not-categorized-delete=$numenc&num=$md')","AsSquidPersonalCategories");
	$scan=$tpl->icon_recycle("Loadjs('$page?not-categorized-scan=$numenc&md=$md')","AsDansGuardianAdministrator");

	$title=trim((string)$ligne["title"]);
	$description=trim((string)$ligne["description"]);
	$created=(string)$ligne["created"];

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
	$h.="<span id='fam-$md'>$domain_url";
	$h.="<div class='awc-meta'>$visits &middot; $google</div>";
	if($title!==""){
		$h.="<div class='awc-meta'>".htmlspecialchars($title)."</div>";
	}
	if(strlen($description)>2){
		$h.="<div class='awc-meta awc-muted'>".htmlspecialchars($description)."</div>";
	}
	$h.="</span></div></td>";
	$h.="<td><div class='awc-chips'>".@implode("",$chips)."</div></td>";
	$h.="<td class='awc-mono'>".htmlspecialchars($created)."</td>";
	$h.="<td class='center'><div class='awc-acts'>$scan$delete</div></td>";
	$h.="</tr>";
	return $h;
}

function not_categorized_categorize_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$domain=$_GET["categorize-this-js"];
	$domanenc=urlencode($domain);
    $zdomain=urlencode($_GET["domain"]);
	$md=$_GET["md"];
	$tpl->js_dialog1("{categorize}: $domain", "$page?categorize-this=$domanenc&md=$md&domain=$zdomain");
    return true;
}
function categories_list_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog5("{your_categories}", "$page?categories-list-popup=yes");
    return true;

}

function categories_list_popup():bool{
    $tpl=new template_admin();
    $q=new postgres_sql();
    $ManageOfficialsCategories = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by categoryname";
    if($ManageOfficialsCategories==1){
        $sql="SELECT * FROM personal_categories WHERE official_category=1 order by categoryname";
    }

    $results=$q->QUERY_SQL($sql);
    $rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        if(preg_match("#^reserved#",$ligne["categoryname"])){continue;}
        $rows[]=catz_category_row($ligne,$tpl);
    }
    $shown=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{name}</th>";
    $html[]="<th>{description}</th>";
    $html[]="</tr></thead><tbody id='catz-cat-rows'>";
    if($shown==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown</span>";
    $html[]="<span class='awc-pg' id='catz-cat-pager'></span>";
    $html[]="</div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(50,"catz-cat-rows","catz-cat-pager");
    return true;
}

/**
 * Une categorie personnelle. L'identifiant numerique occupait une colonne
 * entiere sans en-tete: il devient le sous-titre du nom.
 */
function catz_category_row($ligne,$tpl):string{
    $categoryname=(string)$ligne["categoryname"];
    $category_id=intval($ligne["category_id"]);
    // La description est stockee sous forme de jetons {…}: elle doit passer par
    // le moteur de traduction et ne peut donc pas etre echappee en HTML.
    $category_description=$tpl->_ENGINE_parse_body($ligne["category_description"]);

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-tag'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars($categoryname)."</span>";
    $h.="<div class='awc-meta awc-mono'>#$category_id</div></span>";
    $h.="</div></td>";
    $h.="<td>".($category_description!==""?$category_description:"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="</tr>";
    return $h;
}

function not_categorized_categorize_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $zdomain=$_GET["domain"];
	$tpl->field_hidden("domain",$zdomain);
	$form[]=$tpl->field_categories_list("categorize", "{category}", null);
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$jsafter="$('#$md').remove();$('#des_$md').remove();dialogInstance1.close();Loadjs('$page?not-categorized-head=yes');";
	echo $tpl->form_outside("{categorize} $zdomain", $form,null,"{categorize}",$jsafter,"AsSquidPersonalCategories");
	return true;
}

function not_categorized_categorize_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$qlproxy=new mysql_squid_builder();
    $domain=$_POST["domain"];
    if(strlen($domain)<3){echo "?? $domain ???";return false;}
    if($domain==null){echo "?? Nothing ???";return false;}


    $uid=pg_escape_string2((string)$_SESSION["uid"]);
    if($uid=="-100"){
        $uid="Manager";
    }
    // CLEAN_POST retire les caracteres XSS mais pas l'antislash: le domaine
    // alimente cinq requetes PostgreSQL dont un DELETE par expression
    // reguliere. La variable echappee sert UNIQUEMENT au SQL -- les methodes de
    // mysql_squid_builder continuent de recevoir la valeur d'origine.
    $domainsql=pg_escape_string2($domain);
    $q=new postgres_sql();
    $category=intval($_POST["categorize"]);
    $FORCE=false;
    $oldcatz=$qlproxy->GET_CATEGORIES($domain);
    if($oldcatz==5){
        $q->QUERY_SQL("DELETE FROM category_advertising WHERE sitename='$domainsql'");
        $FORCE=true;
    }

    if($oldcatz==82){
        $q->QUERY_SQL("DELETE FROM category_internal WHERE sitename='$domainsql'");
        $FORCE=true;
    }


	if(!$qlproxy->categorize($domain, $category,$FORCE)){
		echo $tpl->post_error("{failed} $qlproxy->mysql_error");
		return false;
	}
	
	$mem=new lib_memcached();
	$mem->saveKey("CATEGORY:$qlproxy->finaldomain", $domain,86400);
	

    if(strlen($domain)>3) {
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain = '$domainsql'");
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain ~ '$domainsql'");
    }

    $sql="CREATE TABLE IF NOT EXISTS categorize_logs (
            domain varchar(255) PRIMARY KEY,
            category_id INT,
            created TIMESTAMP,
            uid varchar(128)
    )";

    $q->QUERY_SQL($sql);
    $date=date("Y-m-d H:i:s");
    $sql="INSERT INTO categorize_logs (domain,category_id,created,uid) 
   VALUES('$domainsql',$category,'$date','$uid')";
    $q->QUERY_SQL($sql);
	return true;
}

function users_requests_delete():bool{
	$tpl=new template_admin();
	// L'identifiant partait BRUT dans le WHERE (c'est une cle SERIAL) et $md
	// BRUT dans le JavaScript renvoye.
	$id=intval($_GET["users-requests-delete"]);
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM categories_requests WHERE id=$id");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
	echo "$('#$md').remove();\n";
    echo "$('#des_$md').remove();";
	return true;
}

function users_requests_table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

	// Le bouton "{analyze}" qui coiffait ce tableau appelait
	// OnClick="$jsrestart" -- une variable qui n'a JAMAIS ete definie dans ce
	// fichier: l'attribut sortait vide et le bouton ne faisait rien. Il est
	// retire plutot que recable: cette vue (?users-requests-table=yes) n'est
	// referencee ni par tabs() ni par le menu de gauche, elle n'a donc pas de
	// conteneur connu a rafraichir.
	$html=array();

	$q=new postgres_sql();
	$catz=new mysql_catz();

	$sql="CREATE TABLE IF NOT EXISTS categories_requests(
		id SERIAL NOT NULL PRIMARY KEY,
		zDate timestamp,
		uuid VARCHAR(90),
		detectedas INT,
		sitename VARCHAR(255),
		category VARCHAR(128),
		company VARCHAR(128),
		email VARCHAR(128) )";

	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);
		return false;
	}

	$results=$q->QUERY_SQL("SELECT * FROM categories_requests ORDER BY zDate DESC");
	if(!$q->ok){
		echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);
		return false;
	}

	if(pg_num_rows($results)==0){
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
		return false;
	}

	$rows=array();
	while ($ligne = pg_fetch_assoc($results)) {
		$rows[]=catz_request_row($ligne,$catz,$page,$tpl);
	}
	$shown=count($rows);

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{domains}</th>";
	$html[]="<th>{category}</th>";
	$html[]="<th>{detected}</th>";
	$html[]="<th>{date}</th>";
	$html[]="<th class='center'>{delete}</th>";
	$html[]="</tr></thead><tbody id='catz-req-rows'>";
	$html[]=@implode("\n",$rows);
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown</span>";
	$html[]="<span class='awc-pg' id='catz-req-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"catz-req-rows","catz-req-pager");
    return true;
}

/**
 * Une demande de categorisation envoyee par un utilisateur. Societe et adresse
 * de contact tenaient chacune une colonne: elles decrivent le demandeur, pas la
 * demande, et passent en sous-titre du site concerne.
 */
function catz_request_row($ligne,$catz,$page,$tpl):string{
	$md=md5(serialize($ligne));
	$delete=$tpl->icon_delete("Loadjs('$page?users-requests-delete=".intval($ligne["id"])."&md=$md')");
	$detectedascat=$catz->CategoryIntToStr(intval($ligne["detectedas"]));

	$meta=array();
	if(trim((string)$ligne["company"])!==""){$meta[]=htmlspecialchars((string)$ligne["company"]);}
	if(trim((string)$ligne["email"])!==""){$meta[]=htmlspecialchars((string)$ligne["email"]);}

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-comment-dots'></i></span>";
	$h.="<span><span class='awc-name'>".htmlspecialchars((string)$ligne["sitename"])."</span>";
	if(count($meta)>0){$h.="<div class='awc-meta'>".@implode(" &middot; ",$meta)."</div>";}
	$h.="</span></div></td>";
	$h.="<td><span class='awc-tag'>".htmlspecialchars((string)$ligne["category"])."</span></td>";
	$h.="<td>".($detectedascat!==""
		?"<span class='awc-tag'>".htmlspecialchars((string)$detectedascat)."</span>"
		:"<span class='awc-none'>&mdash;</span>")."</td>";
	$h.="<td class='awc-mono'>".htmlspecialchars((string)$ligne["zdate"])."</td>";
	$h.="<td class='center'><div class='awc-acts'>$delete</div></td>";
	$h.="</tr>";
	return $h;
}

function not_categorized_head():bool{
    $tpl=new template_admin();
    $uid=pg_escape_string2((string)$_SESSION["uid"]);
    if($uid=="-100"){
        $uid="Manager";
    }
    $page=CurrentPageName();
    $q=new postgres_sql();
    $not_categorized=FormatNumber($q->COUNT_ROWS_LOW("cloud_categorize"));

    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM cloud_categorize WHERE rscan=0");
    $title_scanned=FormatNumber($ligne["tcount"]);

    $sql="CREATE TABLE IF NOT EXISTS categorize_logs (
            domain varchar(255) PRIMARY KEY,
            category_id INT,
            created TIMESTAMP,
            uid varchar(128)
    )";
    $q->QUERY_SQL($sql);
    $title="{categorisation}: $not_categorized";
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM categorize_logs WHERE uid='$uid'");
    $CategorizedSites=intval($ligne["tcount"]);
    $CategorizedSitesText=FormatNumber($CategorizedSites);
    $CategorizePayit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizePayit"));
    $CategorizePayBySite=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizePayBySite");
    $CategorizeCurrency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizeCurrency");
    $pay=null;
    if($CategorizedSites>0){
        if($CategorizePayit==1){
            $cur=floatval($CategorizePayBySite);
            $Sum=($CategorizedSites*$cur);
            $pay="&nbsp;($Sum&nbsp;$CategorizeCurrency)";
        }

        $title="$CategorizedSitesText/$not_categorized$pay - Scanned $title_scanned";
    }
    header("content-type: application/x-javascript");
    $topbuttons[] = array("Loadjs('$page?categories-list-js=yes')","fa fa-list-ul","{your_categories}: {description}");

    $topbuttons[] = array("Loadjs('$page?import-list-js=yes')","fas fa-cloud-download","{import}");


    $TINY_ARRAY["TITLE"]="$title";
    $TINY_ARRAY["ICO"]="fas fa-book";
    $TINY_ARRAY["EXPL"]="{use_categories_for}";
    $TINY_ARRAY["URL"]="categorisation";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    return true;
}

function status_groupby():bool{
    $tpl=new template_admin();
    $CategorizePayit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizePayit"));
    $CategorizePayBySite=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizePayBySite");
    $CategorizeCurrency=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizeCurrency");

    $q=new postgres_sql();

    if(!$q->FIELD_EXISTS("cloud_categorize","rscan")){
        $q->QUERY_SQL("ALTER TABLE cloud_categorize ADD rscan smallint NOT NULL DEFAULT 0");
        if(!$q->ok){echo $q->mysql_error."<br>";}
        $q->QUERY_SQL("ALTER TABLE cloud_categorize ADD title VARCHAR(255) NULL");
        $q->QUERY_SQL("ALTER TABLE cloud_categorize ADD description VARCHAR(512) NULL");
    }

    // La meme requete etait executee DEUX fois: une premiere passe uniquement
    // pour totaliser, une seconde pour afficher. Les lignes sont desormais
    // collectees une fois et le total calcule dessus.
    $SQL="SELECT count(domain) as tcount, uid FROM categorize_logs GROUP BY uid ORDER BY tcount DESC";
    $results=$q->QUERY_SQL($SQL);
    $lignes=array();
    $Max=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $Max=$Max + intval($ligne["tcount"]);
        $lignes[]=$ligne;
    }

    $rows=array();
    foreach($lignes as $ligne){
        $rows[]=catz_member_row($ligne,$Max,$CategorizePayit,$CategorizePayBySite,$CategorizeCurrency);
    }

    $colspan=3;
    if($CategorizePayit==1){$colspan=4;}

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{member}</th>";
    $html[]="<th class='center'>{domains}</th>";
    $html[]="<th class='center'>{performance}</th>";
    if($CategorizePayit==1){
        $html[]="<th class='center'>".htmlspecialchars((string)$CategorizeCurrency)."</th>";
    }
    $html[]="</tr></thead><tbody id='catz-mbr-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='$colspan'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{websites_categorized}: <b>".FormatNumber($Max)."</b></span>";
    $html[]="<span class='awc-pg' id='catz-mbr-pager'></span>";
    $html[]="</div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js(50,"catz-mbr-rows","catz-mbr-pager");
    return true;
}

/**
 * La contribution d'un membre. Le total general etait une ligne <tr> en
 * 28 pixels collee sous le tableau: il passe dans le pied de carte, ou il ne
 * peut plus etre emporte par la pagination.
 */
function catz_member_row($ligne,$Max,$CategorizePayit,$CategorizePayBySite,$CategorizeCurrency):string{
    $tcount=intval($ligne["tcount"]);
    $uid=(string)$ligne["uid"];
    // Division par zero si categorize_logs est vide et qu'une ligne existe
    // malgre tout (tcount a 0): le total servait de diviseur sans garde.
    $prc=($Max>0)?round(($tcount/$Max)*100,2):0;

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-user'></i></span>";
    $h.="<span><span class='awc-name'>".htmlspecialchars($uid)."</span></span>";
    $h.="</div></td>";
    $h.="<td class='center'><b>".FormatNumber($tcount)."</b></td>";
    $h.="<td class='center awc-mono'>$prc%</td>";
    if($CategorizePayit==1){
        $pay=FormatNumber(round(floatval($CategorizePayBySite)*$tcount,2))." ".htmlspecialchars((string)$CategorizeCurrency);
        $h.="<td class='center'><b>$pay</b></td>";
    }
    $h.="</tr>";
    return $h;
}
