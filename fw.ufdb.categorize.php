<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["nocache"])){$GLOBALS["NOCACHE"]=true;}

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
    $array["{test_categories} ({bulk})"]="$page?test-bulk=yes&function=$function";
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
        $tpl->js_dialog5("{categorize}::{$_GET["cname"]}", "$page?popup=yes&category_requested={$_GET["category_requested"]}&cname=$cname&function=$function");
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
    $UFDBCAT_TEST_BULK_CATEGORIES_RESULTS=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBCAT_TEST_BULK_CATEGORIES_RESULTS"));
    echo $tpl->_ENGINE_parse_body($UFDBCAT_TEST_BULK_CATEGORIES_RESULTS);
}



function test_bulk_categories(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$t.logs.txt";
    $ARRAY["CMD"]="categories.php?categorize-bulk=$t";
    $ARRAY["TITLE"]="{test_categories} ({bulk})";
    $ARRAY["AFTER"]="Loadjs('$page?test-bulk-results=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=progress-bulk-categorize')";



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


function test_categories(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();

    if(!isset($_SESSION["UFDBCAT_TEST_CATEGORIES"])){
        $_SESSION["UFDBCAT_TEST_CATEGORIES"]="";
    }
    $html[]="
    <div class=\"row\">
		<div class='ibox-content'>
			<div class=\"input-group\">
				<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["UFDBCAT_TEST_CATEGORIES"]}\" placeholder=\"{search_category}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
				<span class=\"input-group-btn\">
					<button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">{search_category}</button>
				</span>
			</div>
	</div>
		<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('sitenametest-results','$page?sitenametest='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>
	<div id='sitenametest-results' style='margin-bottom:10px;width:98%'>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));



}

function test_categories_perform(){
    $sitenametest   = url_decode_special_tool(trim(strtolower($_GET["sitenametest"])));
    $sitenametest   = trim($sitenametest);
    $action         = null;
    if($sitenametest==null){return;}
    $category       = 0;
    $sitenametest   = str_replace(";",".",$sitenametest);
    $sitenametest   = str_replace("?","",$sitenametest);
    $srnprovider    = null;
    if(preg_match("#(.+?)VERBOSE#i", $sitenametest,$re)){
        $GLOBALS["VERBOSE"]=true;
        $GLOBALS["NOCACHE"]=true;
        $sitenametest=trim($re[1]);
    }
    $_SESSION["UFDBCAT_TEST_CATEGORIES"]=$sitenametest;
    $class="alert-success";

    $catz=new mysql_catz();
    $categories_descriptions=$catz->categories_descriptions();
    unset($_SESSION["TEST_CATEGORIES"]);
    $time_start = $catz->microtime_float();
    $category = $catz->GET_CATEGORIES($sitenametest);

    if(!is_array($catz->THESHIELD_MAIN)){
        $error_text="&nbsp;-&nbsp;<strong>{connection_error}</strong>";
    }
    $data = json_decode($catz->THESHIELD_MAIN,true);
    //print_r($data);

    $error_text=null;$cached=null;
    VERBOSE("catz->GET_CATEGORIES($sitenametest)=$category",__LINE__);



    if($data["engine"]=="HIT"){
        $action="&nbsp;<small><strong>{cached}</strong></small>";
    }
    $srnprovider=$data["engine"];

    if(count($catz->CategoriesList)>0){
        $action=null;

        if(intval($catz->CategoriesList[1])==1){
            $catz->CategoriesList[2]=0;
            $catz->CategoriesList[3]=0;
            $class="alert-danger";
            $action="&nbsp;&raquo;&raquo;&raquo;&nbsp;{block}!";
        }

        if(intval($catz->CategoriesList[2])==1){
            $action="&nbsp;&raquo;&raquo;&raquo;&nbsp;{allow}";
        }
        if(intval($catz->CategoriesList[3])==1){
            $action="$action&nbsp;&raquo;&raquo;&raquo;&nbsp;{no_cache}";
        }
    }

    $time_end = $catz->microtime_float();
    $TimeExec = round($time_end - $time_start,3);

    $catz->events[]="Final ($category) ({$TimeExec}ms) Query ({$catz->TimeExec}ms)";
    $_SESSION["TEST_CATEGORIES"]["SITENAME"]=$sitenametest;
    $_SESSION["TEST_CATEGORIES"]["category_id"]=intval($category);

    VERBOSE("Category: $category",__LINE__);
    $lemCatz[5001] = "lem.adult.porn";
    $lemCatz[5002] = "lem.notlegal.appz";
    $lemCatz[5003] = "lem.notlegal.hardware";
    $lemCatz[5004] = "lem.games.online";
    $lemCatz[5005] = "lem.notlegal.generic";
    $lemCatz[5006] = "lem.health.prevention.drugs";
    $lemCatz[5007] = "lem.gov.justice";
    $lemCatz[5008] = "lem.gov.health";
    $lemCatz[5009] = "lem.dating";
    $lemCatz[5010] = "lem.scam";
    $lemCatz[5011] = "x.cd06.wl";
    $lemCatz[5012] = "lem.games.app.apple";
    $lemCatz[5013] = "lem.games.news";
    $lemCatz[5014] = "lem.health.prevention.accident";
    $lemCatz[5015] = "lem.finance.banks";
    $lemCatz[5016] = "lem.hobby.manga";
    $lemCatz[5017] = "lem.gamble";
    $lemCatz[5018] = "lem.games.app.pc";
    $lemCatz[5019] = "lem.health.risk.drugs";
    $lemCatz[5020] = "lem.companies.insurance";
    $lemCatz[5021] = "lem.games.app.android";
    $lemCatz[5022] = "lem.health.prevention.riskypractice";
    $lemCatz[5023] = "lem.gov.country.fr";
    $lemCatz[5024] = "lem.infected";
    $lemCatz[5025] = "lem.chat";
    $lemCatz[5026] = "lem.tracker";
    $lemCatz[5027] = "lem.malware";
    $lemCatz[5028] = "lem.gov.edu";
    $lemCatz[5029] = "lem.notlegal.moviez";
    $lemCatz[5030] = "lem.notlegal.p2p";
    $lemCatz[5031] = "lem.certificate";
    $lemCatz[5032] = "lem.search.safe-engines";
    $lemCatz[5033] = "lem.fake.news";
    $lemCatz[5034] = "lem.fake.joke";
    $lemCatz[5035] = "lem.fake.health";
    $lemCatz[5036] = "lem.notlegal.downloadz";
    $lemCatz[5037] = "lem.gov.rescue";
    $lemCatz[5038] = "lem.gov.towns";
    $lemCatz[5039] = "lem.gov.regions";
    $lemCatz[5040] = "lem.gov.country";
    $lemCatz[5041] = "lem.health.prevention.sects";
    $lemCatz[5042] = "lem.notlegal.extractor";
    $lemCatz[5043] = "lem.health.risk.sects";
    $lemCatz[5044] = "lem.gov.police";
    $lemCatz[5045] = "lem.finance.fr.notlegal";
    $lemCatz[5046] = "lem.gov.generic";
    $lemCatz[5047] = "lem.companies.industries";
    $lemCatz[5048] = "lem.adult.underwears";
    $lemCatz[5049] = "lem.health.products";
    $lemCatz[5050] = "lem.network.monitoring";
    $lemCatz[5051] = "lem.hobby.sport";
    $lemCatz[5052] = "x.cd06.bl";
    $lemCatz[5053] = "lem.computing";
    $lemCatz[5054] = "lem.shop";
    $lemCatz[5055] = "lem.companies.itservices";
    $lemCatz[5056] = "lem.computing.pentesting";
    $lemCatz[5057] = "lem.computing.freedns";
    $lemCatz[5058] = "lem.adult.sexshop";
    $lemCatz[5059] = "lem.learning";
    $lemCatz[5060] = "lem.computing.update";
    $lemCatz[5061] = "lem.hobby.art";
    $lemCatz[5062] = "lem.cooking";
    $lemCatz[5063] = "lem.companies.buildings";
    $lemCatz[5064] = "lem.hobby.genealogy";
    $lemCatz[5065] = "lem.hobby.astrology";
    $lemCatz[5066] = "lem.ads";
    $lemCatz[5067] = "lem.news.tv";
    $lemCatz[5068] = "lem.news.mag";
    $lemCatz[5069] = "lem.computing.webdesign";
    $lemCatz[5070] = "lem.learning.schools";
    $lemCatz[5071] = "lem.health.hospitals";
    $lemCatz[5072] = "lem.hobby.travel";
    $lemCatz[5073] = "lem.learning.manuals";
    $lemCatz[5074] = "lem.learning.languages";
    $lemCatz[5075] = "lem.learning.tools";
    $lemCatz[5076] = "lem.hobby.animals";
    $lemCatz[5077] = "lem.hobby.music";
    $lemCatz[5078] = "lem.hobby.vehicles";
    $lemCatz[5079] = "lem.health.disease";
    $lemCatz[5080] = "lem.app.productivity";
    $lemCatz[5081] = "lem.hobby.books";
    $lemCatz[5082] = "lem.hobby.photo";
    $lemCatz[5083] = "lem.sciences";
    $lemCatz[5084] = "lem.companies.realestate";
    $lemCatz[5085] = "lem.blogs.design";
    $lemCatz[5086] = "lem.blogs.geek";
    $lemCatz[5087] = "lem.socialnet";
    $lemCatz[5088] = "lem.blogs.persdev";
    $lemCatz[5089] = "lem.companies.models";
    $lemCatz[5090] = "lem.politics";
    $lemCatz[5091] = "lem.blogs";
    $lemCatz[5092] = "lem.ecology";
    $lemCatz[5093] = "lem.proxy";
    $lemCatz[5094] = "lem.phishing";
    $lemCatz[5095] = "lem.finance.cryptocoins";
    $lemCatz[5096] = "lem.health.risk";
    $lemCatz[5097] = "lem.pictures";
    $lemCatz[5098] = "lem.blogs.celebrity";
    $lemCatz[5099] = "lem.blogs.fashion";
    $lemCatz[5100] = "lem.blogs.diy";
    $lemCatz[5101] = "lem.violence";
    $lemCatz[5102] = "lem.blogs.history";
    $lemCatz[5103] = "lem.hobby.nature";
    $lemCatz[5104] = "lem.notmoderated";
    $lemCatz[5105] = "lem.blogs.lifestyle";
    $lemCatz[5106] = "lem.videos";
    $lemCatz[5107] = "lem.pwned";
    $lemCatz[5108] = "lem.religious";
    $lemCatz[5109] = "lem.tattouing";
    $lemCatz[5110] = "lem.companies.events";
    $lemCatz[5111] = "lem.anonymous";
    $lemCatz[5112] = "lem.visio";
    $lemCatz[5113] = "lem.adult.generic";
    $lemCatz[5114] = "lem.weapons";
    $lemCatz[5115] = "lem.job";
    $lemCatz[5116] = "lem.blogs.lgbt";
    $lemCatz[5117] = "lem.apple";
    $lemCatz[5118] = "lem.timeloose";
    $lemCatz[5119] = "lem.learning.cheat";
    $lemCatz[5120] = "lem.health.prevention";
    $lemCatz[5121] = "lem.computing.antivirus";
    $lemCatz[5122] = "lem.videos.youtube";
    $lemCatz[5123] = "lem.videos.tv";
    $lemCatz[5124] = "lem.notused";
    $lemCatz[5125] = "lem.search.nosafe-engines";
    $lemCatz[5126] = "lem.meteo";
    $lemCatz[5127] = "lem.finance.loan";
    $lemCatz[5128] = "lem.companies.printing";

    if(isset($categories_descriptions[$category])){
        VERBOSE("Category: $category (in array) OK",__LINE__);
        $_SESSION["TEST_CATEGORIES"]["RESULTS"]=$categories_descriptions[$category];
    }
    elseif (isset($lemCatz[$category])){
        $_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"]=$data["category_name"];
        $_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"]="Lemnia {$data["category_name"]} database";

        $_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_icon"]="/img/20-categories-personnal.png";
        $_SESSION["TEST_CATEGORIES"]["category_id"]=$category;
    }

    else{
        VERBOSE("Category: $category (not in array) -- rebuild",__LINE__);
        $libmem=new lib_memcached();
        $GLOBALS["categories_descriptions"]=array();
        $libmem->saveKey("categories_descriptions", serialize(array()),1600);
        $categories_descriptions=$catz->categories_descriptions();
        $_SESSION["TEST_CATEGORIES"]["RESULTS"]=$categories_descriptions[$category];

    }
    $_SESSION["TEST_CATEGORIES"]["EVENTS"]=$catz->events;
    $_SESSION["TEST_CATEGORIES"]["PROVIDER"]=$srnprovider;

    $provider_text=null;
    if(isset($_SESSION["TEST_CATEGORIES"]["PROVIDER"])){
        $provider=$_SESSION["TEST_CATEGORIES"]["PROVIDER"];
        $took = (intval($data["time"]))/1000;
        if($provider<>null){
            $provider_text="&nbsp;<small style='color:rgb(60, 118, 61)'>( The Shields: $provider -> $took ms)</small>";
        }
    }

    if(!isset($_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_icon"])){
        $_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_icon"]=null;
    }
    if(!isset($_SESSION["TEST_CATEGORIES"]["RESULTS"])){$class="alert-danger";}
    if($_SESSION["TEST_CATEGORIES"]["category_id"]==0){$class="alert-danger";}
    if(!isset($_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"])){$_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"]="{unknown}";}
    $tpl=new template_admin();
    $html[]="<div class='alert $class'><H1>";
    $html[]="<img src='{$_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_icon"]}' align=left>&nbsp;{$_SESSION["TEST_CATEGORIES"]["SITENAME"]}$action</h1>";

    if(!isset($_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"])){$_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"]=null;}
    $js="Loadjs('fw.ufdb.categories.php?category-js={$_SESSION["TEST_CATEGORIES"]["category_id"]}')";
    $category_description=$tpl->_ENGINE_parse_body($_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"]);

    $category_text=$tpl->td_href($_SESSION["TEST_CATEGORIES"]["RESULTS"]["categoryname"],$category_description,$js);

    if(isset($_SESSION["TEST_CATEGORIES"]["RESULTS"])){
        $html[]="<H2>{category}:$category_text{$provider_text}</H2>";
        $html[]="<div><i>{$_SESSION["TEST_CATEGORIES"]["RESULTS"]["category_description"]}</i></div><hr>";
    }
    foreach ($_SESSION["TEST_CATEGORIES"]["EVENTS"] as $line){
        $html[]="<div><small>$line</small></div>";

    }
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html)."</div><script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>");

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

    echo "<table class='table table-striped'>
                            <thead>
                            <tr>
                                <th>Events</th>
                            </tr>
                            </thead>
                            <tbody>";

    foreach ($f as $line){

        echo "<tr><td>$line</td></tr>";

    }
    echo "</tbody></table>";
}
function import_popup(){
    $page=CurrentPageName();
    echo "<div id='category-import-form'></div><script>LoadAjax('category-import-form','$page?import-form=yes');</script>";

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

    echo $tpl->form_outside("{import}&nbsp;{$_GET["cname"]}", @implode("\n", $form),$explain,"{next}",$jsafter);

}


function popup():bool{
    $page=CurrentPageName();
    $function="";if(isset($_GET["function"])){$function=$_GET["function"];}
    $category_requested=0;if(isset($_GET["category_requested"])){ $category_requested=intval($_GET["category_requested"]);}
    $cname="";
    if(isset($_GET["cname"])){ $cname=$_GET["cname"];}

    $url="$page?popup2=yes&cname=$cname&category_requested=$category_requested&function=$function";

    echo "<div id='popup-categorize'></div>\n";
    echo "<script>";
    echo "LoadAjax('popup-categorize','$url');";
    echo "</script>";
    return true;

}

function popup2():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $cname=$_GET["cname"];
    $function=$_GET["function"];
    $category_requested=intval($_GET["category_requested"]);
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0  AND meta=0 order by categoryname ";
    if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";}
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error</div>";
        return false;
    }
    $HASH=array();
    if($category_requested==0) {
        while ($ligne = pg_fetch_assoc($results)) {
            $category_id = $ligne["category_id"];
            if($category_id=="234"){$ligne["categoryname"]="CloudFlare";}

            $categoryname = $ligne["categoryname"];
            if (preg_match("#^reserved#", $categoryname)) {
                continue;
            }
            $HASH[$category_id] = $categoryname;
        }
    }
    $t=time();
    $form[]=$tpl->field_hidden("logid", "$t");
    if($category_requested==0) {$form[]=$tpl->field_array_hash($HASH, "category_id", "{category}", 0);}
    if($category_requested>0) {$tpl->field_hidden("category_id",$category_requested);}
    $form[]=$tpl->field_checkbox("ForceCat","{force}",0,false,"{category_inject_force_explain}");
    $form[]=$tpl->field_checkbox("ForceExt","{no_extension_check}",0,false,"{free_cat_no_extension_check_explain}");
    $form[]=$tpl->field_textareacode("websites", "{websites}", null);

    $url="$page?popup2=yes&cname=$cname&category_requested=$category_requested&function=$function";

    $functionafter=null;
    if(strlen($function)>4){
        $functionafter=";".$function."();";
    }

    $jsafter=$tpl->framework_buildjs("/categories/memory/run/all",
        "categorize.progress","categorize.progress.log","progress-$t",
        "LoadAjax('popup-categorize','$url');$functionafter");

    echo "<div id='progress-$t' style='margin-top:5px'></div>";

    $explain="{perso_add_websites_categories_explain}";
    echo $tpl->form_outside("{add_websites}&nbsp;$cname", @implode("\n", $form),$explain,"{add_websites}",$jsafter);
    return true;

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
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=$_GET["bulk-uploaded"];
    $file_encoded=urlencode($file);
    $md5=md5($file);
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$md5.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$md5.logs.txt";
    $ARRAY["CMD"]="categories.php?categorize-bulk=$file_encoded";
    $ARRAY["TITLE"]="{test_categories} ({bulk})";
    $ARRAY["AFTER"]="Loadjs('$page?test-bulk-results=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=progress-bulk-categorize')";
    echo $jsafter;
}

function file_uploaded():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $file=$_GET["file-uploaded"];
    $category_id=intval($_GET["category_id"]);
    $forcecat=intval($_GET["ForceCat"]);
    $ForceExt=intval($_GET["ForceExt"]);
    $function=$_GET["function"];

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
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo $tpl->framework_buildjs("/categories/memory/run/$category_id",
        "$category_id.progress","categorize.manu.$category_id.log","import-categories-div-progress", $function);


    return admin_tracks("Launching categorize after uploaded $file for category $category_id");


}
