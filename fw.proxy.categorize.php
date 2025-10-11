<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["CategorizePayit"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["CategoriesResolvedCache"])){save();exit;}
if(isset($_POST["PerformanceSave"])){PerformanceSave();exit;}
if(isset($_GET["SafePorts"])){SafePorts();exit;}
if(isset($_GET["SafePortTable"])){SafePorts_table();exit;}
if(isset($_GET["safeport-add"])){SafePorts_add();exit;}
if(isset($_POST["SafePort"])){SafePorts_save();exit;}
if(isset($_GET["safeport-http"])){SafePorts_switch();exit;}
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
if(isset($_GET["clean-cache"])){clean_cache();exit;}
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
	$familysite=$_GET["not-categorized-delete"];
	$md=$_GET["num"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain ~'$familysite'");
	echo "$('#$md').remove();";
    echo "$('#des_$md').remove();";
    return true;
}
function not_categorized_catz():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $familysite=$_GET["not-categorized-catz"];
    $domain=$_GET["domain"];
    $md=$_GET["md"];
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



    if(!$qlproxy->categorize($familysite, $_GET["category"],$FORCE)){
        echo $tpl->js_error("{failed} $qlproxy->mysql_error");
        return false;
    }
    $uid=$_SESSION["uid"];
    if($uid=="-100"){
        $uid="Manager";
    }

    $category_id=intval($_GET["category"]);
    $mem=new lib_memcached();
    $mem->saveKey("CATEGORY:$qlproxy->finaldomain", $category_id,86400);
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain = '$domain'");
    $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain ~ '$domain'");
    $catz=new mysql_catz();
    $categoryname=$catz->CategoryIntToStr($category_id);
    echo "document.getElementById('fam-$md').innerHTML='<span style=\"color:red\">$familysite OK ADDED TO $categoryname</span>';\n";

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
    $familysite=$_GET["not-categorized-scan"];
    $md=$_GET["md"];
    $catz=new mysql_catz();
    $category=$catz->GET_CATEGORIES($familysite);
    if($category>0){
        $q=new postgres_sql();
        $q->QUERY_SQL("DELETE FROM not_categorized WHERE familysite='$familysite'");
        $categoryname=$catz->CategoryIntToStr($category);
        echo "document.getElementById('fam-$md').innerHTML='$familysite <span style=\"color:red\">$categoryname</span>';\n";
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
            echo "document.getElementById('fam-$md').innerHTML='$familysite <span style=\"color:red\">No DNS</span>';\n";

        }
        return;
    }
    echo "document.getElementById('fam-$md').innerHTML='$familysite {$result->answer[0]->address}';";



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
	$html[]="<table style='width:100%;margin-top:20px'>";
	$html[]="<tr>";
	$html[]="<td style='width:355px'>";
    $html[]=$widget1;
	$html[]="</td>";
	$html[]="<td style='width:355px;vertical-align:top;padding-top:7px;padding-left:15px'>";
    $html[]="<div style='border-top:0;width:355px'>";
	$html[]="<div id='cached-websites-catz'></div>";
	$html[]="";
    $html[]="</div>";
	$html[]="</td>";
    $html[]="<td style='width:99%'>&nbsp;</td>";
	$html[]="</tr>";
    $html[]="</table>";
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


	$html[]="<table id='table-notcategorized-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
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
           $TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domains}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{action}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{scan}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $CategorizeRandom=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategorizeRandom");

    $ORDER="created ASC";
    if($CategorizeRandom==1){
        $ORDER="random()";
    }
    $search="*".$_GET["search"]."*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    $sql="SELECT * FROM cloud_categorize and rscan=1 ORDER BY created ASC LIMIT 250";
    if($search<>null){
        $sql="SELECT * FROM cloud_categorize WHERE (domain LIKE '$search' OR title LIKE '$search' OR description LIKE '$search') and rscan=1 ORDER BY $ORDER LIMIT 250";
    }

	$results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        echo "<script>Loadjs('$page?not-categorized-head=yes');</script>";
        return false;
    }
	
    $style='font-size:14px;fonct-weight:bold';
	$fam=new familysite();
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $domain=$ligne["domain"];
		$md=md5(serialize($ligne));
        if(preg_match("#^(www|ww|mx|admin|magento|hostmaster|ns|ns1|webmail|mail|bbs|cpanel|webdisk|autodiscover|smtp|cpcalendars|cpcontacts)\.(.+)#",$domain,$re)){
            $domain=$re[2];
        }
        $numenc=urlencode($domain);
        $tooldoms=$numenc;
		$xfam=$fam->GetFamilySites($domain);
        $domain_url=$tpl->td_href("<span class='label label-default' style='$style'>{$domain}</span>","{categorize}","Loadjs('$page?categorize-this-js=$numenc&md=$md&domain=$domain');");

        if($xfam<>$domain){
            $tooldoms=$xfam;
            $domain_url=$domain_url."&nbsp;|&nbsp;".$tpl->td_href("<span class='label label-default' style='$style'>$xfam</span>","{categorize}","Loadjs('$page?categorize-this-js=$numenc&md=$md&domain=$xfam');");
        }
        $www=null;
        if($domain==$xfam){
            if(!preg_match("#^www\.#",$domain)){
                $www="www.";
            }
        }
        $link="http://$www$domain";
        $visits="<i class='fa fa-link'></i>&nbsp;".$tpl->td_href("{view}",null,"s_PopUpFull('$link','1024','900')");

        $sezarch="https://www.google.com/search?channel=fs&client=ubuntu&q=%22$tooldoms%22";
        $URL="s_PopUpFull('$sezarch','1024','900')";

        $google="&nbsp;<i class='fa fa-link'></i>&nbsp;".$tpl->td_href("Google",null,$URL);
		
		$delete=$tpl->icon_delete("Loadjs('$page?not-categorized-delete=$numenc&num=$md')","AsSquidPersonalCategories");
		$scan=$tpl->icon_recycle("Loadjs('$page?not-categorized-scan=$numenc&md=$md')","AsDansGuardianAdministrator");


		$phishing=$tpl->td_href("<span class='label label-warning'>Phishing</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=105&domain=$tooldoms')");
        $Governments=$tpl->td_href("<span class='label label-primary'>Governments</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=62&domain=$tooldoms')");

        $Industry=$tpl->td_href("<span class='label label-warning'>Industry</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=81&domain=$tooldoms')");

        $Suscpicous=$tpl->td_href("<span class='label label-danger'>Suspicious</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=140&domain=$tooldoms')");

        $jobtraining=$tpl->td_href("<span class='label label-info'>Job Training</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=86&domain=$tooldoms')");

        $music=$tpl->td_href("<span class='label label-success'>Music</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=101&domain=$tooldoms')");

        $movie=$tpl->td_href("<span class='label label-default'>Movies</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=100&domain=$tooldoms')");

        $travel=$tpl->td_href("<span class='label label-success'>Travel</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=119&domain=$tooldoms')");

        $reaf=$tpl->td_href("<span class='label label-danger'>Reaffected</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=112&domain=$tooldoms')");

        $tracker=$tpl->td_href("<span class='label label-warning'>Tracker</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=143&domain=$tooldoms')");

        $computing=$tpl->td_href("<span class='label  label-info'>Computing</span>",null,
            "Loadjs('$page?not-categorized-catz=$numenc&md=$md&category=126&domain=$tooldoms')");

        $title=$ligne["title"];
        $description=$ligne["description"];
        $rscan=$ligne["rscan"];
        if($rscan==1){$ligne["created"]="<strong>{$ligne["created"]}</strong>";}

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>{$ligne["created"]}</td>";
		$html[]="<td><strong id='fam-$md'>$visits&nbsp;$domain_url</span>&nbsp;$google</strong></td>";
        $html[]="<td style='width:1%' nowrap>$reaf&nbsp;&nbsp;$tracker&nbsp;&nbsp;$computing&nbsp;&nbsp;$travel&nbsp;&nbsp;$movie&nbsp;&nbsp;$music&nbsp;&nbsp;$Industry&nbsp;&nbsp;$Governments&nbsp;&nbsp;$jobtraining&nbsp;&nbsp;$phishing&nbsp;&nbsp;$Suscpicous</td>";
        $html[]="<td style='width:1%'>$scan</td>";
		$html[]="<td style='width:1%'>$delete</td>";
		$html[]="</tr>";

        if(strlen($title)>0){
            $html[]="<tr class='$TRCLASS' id='des_$md'>";
            $html[]="<td style='width:1%' nowrap></td>";
            $html[]="<td colspan=4 ><div style='padding-left:5px;margin-left: 30px;border-left: 2px solid #CCCCCC'>Title:$title";
            if(strlen($description)>2){
                $html[]="<div><small>Description:$description</small></div>";
            }
            $html[]="</td>";
            $html[]="</tr>";
        }

	}	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	Loadjs('$page?not-categorized-head=yes');
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
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
        $sql="SELECT * FROM personal_categories WHERE official_category=1 order by category_id";
    }

    $TRCLASS=null;
    $html[]="<table id='table-notcategorized-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $categoryname=$ligne["categoryname"];
        if(preg_match("#^reserved#",$categoryname)){continue;}
        $category_id=$ligne["category_id"];
        $category_description=$tpl->_ENGINE_parse_body($ligne['category_description']);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $md=md5(serialize($ligne));

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$category_id</td>";
        $html[]="<td style='width:1%' nowrap><strong>$categoryname</strong></td>";
        $html[]="<td>$category_description</td>";
        $html[]="</tr>";
}

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function not_categorized_categorize_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $zdomain=$_GET["domain"];
	$tpl->field_hidden("domain",$zdomain);
	$form[]=$tpl->field_categories_list("categorize", "{category}", null);
	$md=$_GET["md"];
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


    $uid=$_SESSION["uid"];
    if($uid=="-100"){
        $uid="Manager";
    }
    $q=new postgres_sql();
    $category=intval($_POST["categorize"]);
    $FORCE=false;
    $oldcatz=$qlproxy->GET_CATEGORIES($domain);
    if($oldcatz==5){
        $q->QUERY_SQL("DELETE FROM category_advertising WHERE sitename='$domain'");
        $FORCE=true;
    }

    if($oldcatz==82){
        $q->QUERY_SQL("DELETE FROM category_internal WHERE sitename='$domain'");
        $FORCE=true;
    }


	if(!$qlproxy->categorize($domain, $category,$FORCE)){
		echo $tpl->post_error("{failed} $qlproxy->mysql_error");
		return false;
	}
	
	$mem=new lib_memcached();
	$mem->saveKey("CATEGORY:$qlproxy->finaldomain", $domain,86400);
	

    if(strlen($domain)>3) {
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain = '$domain'");
        $q->QUERY_SQL("DELETE FROM cloud_categorize WHERE domain ~ '$domain'");
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
   VALUES('$domain',$category,'$date','$uid')";
    $q->QUERY_SQL($sql);
	return true;
}

function users_requests_delete():bool{
	$tpl=new template_admin();
	$id=$_GET["users-requests-delete"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM categories_requests WHERE id=$id");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
	echo "$('#{$_GET["md"]}').remove();\n";
    echo "$('#des_{$_GET["md"]}').remove();";
	return true;
}

function users_requests_table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();


	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recategorize.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/recategorize.progress.txt";

	$ARRAY["CMD"]="squidlogger.php?rescan-not-categorized=yes";
	$ARRAY["TITLE"]="{analyze} {not_categorized}";
	$ARRAY["AFTER"]="LoadAjax('table-not-categorized-section','$page?not-categorized-table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-catz-restart')";


	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fas fa-retweet'></i> {analyze} </label>
			</div>");

	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domains}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{detected}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{company}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{email}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

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
	
	
	
	while ($ligne = pg_fetch_assoc($results)) {
		//zDate,uuid,sitename,category,company,email,detectedas
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

		$md=md5(serialize($ligne));
		$delete=$tpl->icon_delete("Loadjs('$page?users-requests-delete={$ligne["id"]}&md=$md')");
		$detectedas=$ligne["detectedas"];
		$detectedascat=$catz->CategoryIntToStr($detectedas);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td>{$ligne["zdate"]}</td>";
		$html[]="<td><strong>{$ligne["sitename"]}</strong></td>";
		$html[]="<td>{$ligne["category"]}</td>";
		$html[]="<td>$detectedascat</td>";
		$html[]="<td>{$ligne["company"]}</td>";
		$html[]="<td>{$ligne["email"]}</td>";
		$html[]="<td style='width:1%' class='center'>$delete</td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true 	}, 	\"sorting\": { 	\"enabled\": true 	} } ); });
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function not_categorized_head():bool{
    $tpl=new template_admin();
    $uid=$_SESSION["uid"];
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
    $results=$q->QUERY_SQL("SELECT count(domain) as tcount, uid FROM categorize_logs GROUP BY uid ORDER BY tcount DESC");

    $TRCLASS=null;
    $html[]="<table id='table-notcategorized-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domains}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{performance}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</center></th>";
    if($CategorizePayit==1) {
        $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>$CategorizeCurrency</center></th>";
    }

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $Max=0;
    while ($ligne = pg_fetch_assoc($results)) {
        $Max=$Max + $ligne["tcount"];

    }

    if(!$q->FIELD_EXISTS("cloud_categorize","rscan")){
        $q->QUERY_SQL("ALTER TABLE cloud_categorize ADD rscan smallint NOT NULL DEFAULT 0");
        if(!$q->ok){echo $q->mysql_error."<br>";}
        $q->QUERY_SQL("ALTER TABLE cloud_categorize ADD title VARCHAR(255) NULL");
        $q->QUERY_SQL("ALTER TABLE cloud_categorize ADD description VARCHAR(512) NULL");
    }

    $results=$q->QUERY_SQL("SELECT count(domain) as tcount, uid FROM categorize_logs GROUP BY uid ORDER BY tcount DESC");
    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }

        $html[]="<tr class='$TRCLASS' id='' style='font-size:18px'>";

        $tcount=$ligne["tcount"];
        $uid=$ligne["uid"];
        $tcountDoms=FormatNumber($tcount);
        $prc=$tcount/$Max;
        $prc=round($prc*100,2);

        $html[]="<td style='width:1%' nowrap style='text-align: right'><strong>$tcountDoms</strong>&nbsp;</td>";
        $html[]="<td style='width:1%' nowrap style='text-align: right'><strong>$prc%</strong></td>";
        $html[]="<td style='width:1%' nowrap><strong>$uid</strong></td>";

        if($CategorizePayit==1) {
            $tcount_pay=round($CategorizePayBySite*$tcount,2);
            $tcount_pay=FormatNumber($tcount_pay)." $CategorizeCurrency";
            $html[]="<td nowrap style='text-align: right;width:99%'><strong>$tcount_pay</strong></td>";
        }

        $html[]="</tr>";
    }
    $colspan=3;
    if($CategorizePayit==1) {$colspan=4;}
    $Max=FormatNumber($Max);
    $html[]="<tr class='$TRCLASS' id='' style='font-size:18px'>";
    $html[]="<td style='font-size:28px;border-top:1px solid #CCCCCC;text-align: right' colspan='$colspan'><span style='font-size:12px'>{websites_categorized}:</span>&nbsp;<strong>$Max</strong>&nbsp;</td>";
    $html[]="</tr>";
    

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan=34'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;


}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'):string{$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
