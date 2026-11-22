<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


// Pentest 2026-11-04 : pinger-ids/ufdb/js-export/js-export2/filltable/gpid/external-sources-*/jrows/field etait recopie sans encodage dans la page (XSS).
// Identifiants entiers, listes d'entiers separes par "-", md5 d'URL, base64, nom de colonne connu.
foreach(array("js-export","js-export2","filltable","gpid") as $k){if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}}
foreach(array("pinger-ids","ufdb") as $k){if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^0-9-]/','',(string)$_GET[$k]);}}
foreach(array("external-sources-edit","external-sources-popup","external-sources-enable","external-sources-remove") as $k){if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET[$k]);}}
if(isset($_GET["jrows"])){$_GET["jrows"]=preg_replace('#[^A-Za-z0-9+/= ]#','',(string)$_GET["jrows"]);}
if(isset($_GET["field"]) && !in_array($_GET["field"],array("categoryname","category_description","items"),true)){$_GET["field"]="categoryname";}
if(isset($_GET["remote-categories-service-tabs"])){remote_categories_service_tabs();exit;}
if(isset($_GET["remote-categories-service"])){remote_categories_service_js();exit;}
if(isset($_GET["remote-categories-service-popup"])){remote_categories_service_popup();exit;}
if(isset($_GET["remote-categories-service-queries"])){remote_categories_service_queries();exit;}
if(isset($_POST["UseRemoteCategoriesService"])){remote_categories_service_save();exit;}
if(isset($_POST["EnableRemoteCategoriesServices"])){remote_categories_service_save();exit;}
if(isset($_GET["synchronize-remote-categories"])){remote_categories_service_fetch();exit;}
if(isset($_GET["category-dns-js"])){remote_category_js();exit;}
if(isset($_GET["category-http-js"])){remote_http_category_js();exit;}

if(isset($_GET["remote-http-category-tabs"])){remote_http_category_tabs();exit;}
if(isset($_GET["remote-http-category-popup"])){remote_http_category_popup();exit;}
if(isset($_GET["remote-category-popup"])){remote_category_popup();exit;}

if(isset($_GET["all-categories-js"])){all_categories_js();exit;}
if(isset($_GET["all-categories-tabs"])){all_categories_tabs();exit;}
if(isset($_GET["all-categories-perso"])){all_categories_perso();exit;}
if(isset($_GET["all-categories-officials"])){all_categories_officials();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["compile-dns-api"])){compile_dns_api();exit;}
if(isset($_GET["synchronize-dns-api"])){synchronize_dns_api();exit;}
if(isset($_POST["category-orders"])){category_orders_save();exit;}
if(isset($_POST["rpz_id"])){category_edit_rpz_save();exit;}
if(isset($_POST["none"])){die();}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["category-js"])){category_js();exit;}
if(isset($_GET["category-step1"])){category_step1();exit;}
if(isset($_GET["category-popup"])){category_popup();exit;}
if(isset($_GET["category-edit"])){category_edit();exit;}
if(isset($_GET["category-flat"])){category_edit_flat();exit;}
if(isset($_GET["category-edit-js"])){category_edit_js();exit;}
if(isset($_GET["category-edit-popup"])){category_edit_general_popup();exit;}
if(isset($_GET["category-edit-rpz"])){category_edit_rpz_js();exit;}
if(isset($_GET["category-edit-rpz-popup"])){category_edit_rpz_popup();exit;}
if(isset($_GET["dnscatz-compile-single"])){dnscatz_compile_single();exit;}
if(isset($_GET["pinger"])){pinger();exit;}
if(isset($_GET["pinger-ids"])){pinger2();exit;}

if(isset($_POST["newcat"])){category_new_save();exit;}
if(isset($_POST["categorykey"])){category_save();exit;}
if(isset($_GET["category-items"])){category_items();exit;}
if(isset($_GET["category-items-table"])){category_items_table();exit;}
if(isset($_GET["category-items-addjs"])){category_items_add_js();exit;}
if(isset($_GET["category-items-addpopup"])){category_items_add_popup();exit;}
if(isset($_GET["compile-category-buttons"])){category_step_buttons();exit;}
if(isset($_POST["websites"])){category_items_add_save();exit;}
if(isset($_GET["delete-pattern"])){delete_site();exit;}
if(isset($_GET["category-delete"])){category_delete();exit;}
if(isset($_POST["category-delete"])){category_remove();exit;}
if(isset($_GET["category-security"])){category_security();exit;}
if(isset($_GET["remove-all"])){category_remove_all();exit;}
if(isset($_GET["sql-delete"])){category_items_delete_sql();exit;}
if(isset($_POST["DELETE_SQL"])){category_items_delete_sql_perform();exit;}
if(isset($_POST["none"])){exit();}
if(isset($_GET["jrows"])){jrows();exit;}
if(isset($_GET["jrows-data"])){jrows_data();exit;}
if(isset($_GET["filltable"])){filltable();exit;}
if(isset($_GET["td-content"])){td_content();exit;}
if(isset($_GET["delete-priv"])){delete_privs();exit;}
if(isset($_GET["js-export"])){js_export();exit;}
if(isset($_GET["js-export2"])){js_export_confirm();exit;}
if(isset($_GET["js-export-perform"])){js_export_perform();exit;}
if(isset($_GET["js-export-download"])){js_export_download();exit;}
if(isset($_GET["category-orders"])){category_orders();exit;}
if(isset($_GET["external-sources"])){external_sources_start();exit;}
if(isset($_GET["external-sources-table"])){external_sources_table();exit;}
if(isset($_GET["external-sources-edit"])){external_sources_edit_js();exit;}
if(isset($_GET["external-sources-popup"])){external_sources_popup();exit;}
if(isset($_GET["external-sources-enable"])){external_sources_enable();exit;}
if(isset($_GET["external-sources-remove"])){external_sources_remove();exit;}
if(isset($_POST["md5url"])){external_sources_save();exit;}
page();

function synchronize_dns_api():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $function=$_GET["function"];
    $data=$sock->REST_API("/categories/remote/sync");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->js_error($tpl->_ENGINE_parse_body(json_last_error_msg()));
        return false;
    }


    if (!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    $tpl->js_ok($tpl->_ENGINE_parse_body("{success}"));
    echo "$function();";
    return true;
}
function pinger():bool{
    $page=CurrentPageName();
    $js[]="function getDnsCatzIdsString() {
        var ids = [];
        var idsUFDB = [];
        $('span[id^=\"dnscatz-status-\"]').each(function () {
            var match = this.id.match(/dnscatz-status-(\d+)/);
            if (match) {
                ids.push(match[1]); // keep as string
            }
        });
        $('span[id^=\"dnscatz-ufdb-\"]').each(function () {
            var match = this.id.match(/dnscatz-ufdb-(\d+)/);
            if (match) {
                idsUFDB.push(match[1]); // keep as string
            }
        });
        var idsStr=ids.join('-');
        var UfdbStr=idsUFDB.join('-');
        Loadjs('$page?pinger-ids='+encodeURIComponent(idsStr)+'&ufdb='+encodeURIComponent(UfdbStr)  );
    }";
    $js[]="getDnsCatzIdsString()";
    header("content-type: application/x-javascript");
    echo @implode("\n", $js);
    return true;
}

// Bande de compilation au-dessus du tableau : progression dnscatz, ou erreur du serveur distant.
// Une chaine vide efface la bande.
function pinger2_global_status():string{
    $tpl=new template_admin();
    $html="";
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if($EnableLocalUfdbCatService==0){
        $EnableRemoteCategoriesServices=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));
        if($EnableRemoteCategoriesServices==1){
            $err=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceError"));
            if(strlen($err)>2){$html=ufct_strip_remote($err);}
        }
    }else{
        $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_DNSCATZ("/compile-progress"),true);
        if(is_array($json)){$html=ufct_strip($json);}
    }
    $t="";
    if($html<>""){$t=base64_encode($tpl->_ENGINE_parse_body($html));}
    return "if(document.getElementById('ufct-strip')){ $('#ufct-strip').html('$t'.length?base64_decode('$t'):''); }";
}

function pinger2_set(string $id,string $html):string{
    $tpl=new template_admin();
    $t=base64_encode($tpl->_ENGINE_parse_body($html));
    return "$('#$id').html(base64_decode('$t'));\n";
}

function pinger2():bool{
    header("content-type: application/x-javascript");
    echo pinger2_global_status()."\n";
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $DNSCATZREMOTE=array();
    $DNSCATZSTATUS=array();
    $LOCAL_CATEGORIES=array();
    $active=ufct_pill("ok","{active2}");
    $notCompiled=ufct_pill("warn","{not_compiled}");

    $DumpRemoteCategories=json_decode((string)$sock->GET_INFO("DumpRemoteCategories"),true);
    if(is_array($DumpRemoteCategories)){
        foreach ($DumpRemoteCategories as $ligne){
            if(isset($ligne["category_id"])){$DNSCATZREMOTE[intval($ligne["category_id"])]=true;}
        }
    }

    $ufdbs=array();
    if(isset($_GET["ufdb"]) && $_GET["ufdb"]<>""){$ufdbs=explode("-",$_GET["ufdb"]);}
    if(count($ufdbs)>0){
        $LocalCatz=json_decode((string)$sock->REST_API("/categories/locales"),true);
        if(isset($LocalCatz["Categories"]) && is_array($LocalCatz["Categories"])){
            foreach ($LocalCatz["Categories"] as $cat){
                $LOCAL_CATEGORIES[intval($cat["category_id"])]=intval($cat["compiledate"]);
            }
        }
        foreach ($ufdbs as $category_id){
            $category_id=intval($category_id);
            if($category_id==0){continue;}
            if(isset($LOCAL_CATEGORIES[$category_id])){
                echo pinger2_set("dnscatz-ufdb-$category_id",$active);
                $sub="";
                if($LOCAL_CATEGORIES[$category_id]>0){
                    $sub="{updated2}: ".distanceOfTimeInWords($LOCAL_CATEGORIES[$category_id],time(),true);
                }
                echo pinger2_set("dnscatz-updated-$category_id",$sub);
                continue;
            }
            echo pinger2_set("dnscatz-updated-$category_id","");
            if(isset($DNSCATZREMOTE[$category_id])){
                echo pinger2_set("dnscatz-ufdb-$category_id",$active);
                continue;
            }
            echo pinger2_set("dnscatz-ufdb-$category_id",$notCompiled);
        }
    }

    if(intval($sock->GET_INFO("EnableLocalUfdbCatService"))==1){
        $data=json_decode((string)$sock->REST_API_DNSCATZ("/export/status"),true);
        if(isset($data["categories"]) && is_array($data["categories"])){
            foreach ($data["categories"] as $cat){
                $DNSCATZSTATUS[intval($cat["category_id"])]=intval($cat["domain_count"] ?? 0);
            }
        }
    }

    $tb=array();
    if(isset($_GET["pinger-ids"]) && $_GET["pinger-ids"]<>""){$tb=explode("-",$_GET["pinger-ids"]);}
    foreach ($tb as $category_id){
        $category_id=intval($category_id);
        if($category_id==0){continue;}
        if(isset($DNSCATZREMOTE[$category_id])){
            echo pinger2_set("dnscatz-status-$category_id",$active);
            echo pinger2_set("dnscatz-dnssub-$category_id","");
            continue;
        }
        if(!isset($DNSCATZSTATUS[$category_id])){
            echo pinger2_set("dnscatz-status-$category_id",$notCompiled);
            echo pinger2_set("dnscatz-dnssub-$category_id","");
            continue;
        }
        echo pinger2_set("dnscatz-status-$category_id",$active);
        echo pinger2_set("dnscatz-dnssub-$category_id",str_replace(" ","&thinsp;",numberFormat($DNSCATZSTATUS[$category_id],0,""," "))." {domains}");
    }
    return true;
}

function compile_dns_api():bool{
    $tpl=new template_admin();


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscatz/permissions"),true);

    if(!isset($json["Status"])){
        return $tpl->js_error("Protocol error");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }



    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_DNSCATZ("/compile");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->js_error($tpl->_ENGINE_parse_body(json_last_error_msg()));
        return false;
    }

    if ($json->status=="error"){
        $tpl->js_error($json->message);
        return false;
    }



    $tpl->js_ok($tpl->_ENGINE_parse_body("{success}"));

    return true;

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{your_categories}",
        "fad fa-books","{personal_categories_explain}<div id='categories-text-infos'></div>","$page?tabs=yes",
        "personal-categories","progress-ppcategories-restart",false,"table-perso-category-start");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{your_categories}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Hote de la bande de compilation, rempli par le pinger du tableau.
    echo "<div id='ufct-strip' role='status' aria-live='polite' style='margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&table=yes");
    return true;

}
function external_sources_start():bool{
    $ID=intval($_GET["external-sources"]);
    $page=CurrentPageName();
    echo "<div id='table-external-sources-$ID' style='margin-top:15px'></div>";
    echo "<script>";
    echo "LoadAjax('table-external-sources-$ID','$page?external-sources-table=$ID');";
    echo "</script>";
    return true;
}
function all_categories_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{your_categories}"]="$page?all-categories-perso=yes";
    $array["{official_categories}"]="$page?all-categories-officials=yes";
    echo "<div style='margin-top:10px'>".$tpl->tabs_default($array)."</div>";
    return true;
}
function all_categories_perso():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 order by categoryname";
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    // Ces deux fonctions fermaient un <thead> et un <tr> qu'elles n'ouvraient
    // JAMAIS: le lexer comptait 4 <thead> pour 6 </thead> dans ce fichier.
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{categories}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ufcp-rows'>";
    // Position de depart des lignes, pour le comptage du pied.
    $__rows=count($html);

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error<br>$sql</div>";
        return false;
    }

    $TRCLASS=null;


    while ($ligne = pg_fetch_assoc($results)) {
        $TRCLASS=null;
        $category_id = $ligne["category_id"];
        $categoryname = $ligne["categoryname"];
        $text_category = $tpl->_ENGINE_parse_body($ligne["category_description"]);
        $category_icon = $ligne["category_icon"];
        $html[]="<td style='width:1%;vertical-align: top' nowrap>$category_id</td>";
        $html[]="<td style='width:99%'><strong style='font-size:18px'>$categoryname</strong><br><i>$text_category</i></td>";
        $html[]="</tr>";

    }

    // Le <tfoot> portait un pager footable qu'AUCUN init n'alimente sur cette
    // page: un pied de tableau vide. Il est remplace par le pied awc et son
    // pager cote client. Le nombre de lignes est compte sur les fragments
    // ajoutes depuis l'ouverture du <tbody>, ce qui evite de toucher a la
    // boucle de chaque tableau.
    $__c=0; foreach(array_slice($html,$__rows) as $__f){$__c+=substr_count($__f,"<tr");}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $__c {items}</span>";
    $html[]="<span class='awc-pg' id='ufcp-pager'></span>";
    $html[]="</div></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body($html).awc_pager_js(50,"ufcp-rows","ufcp-pager");
    return true;
}
function all_categories_officials():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE official_category=1 AND free_category=0 order by categoryname";
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    // Ces deux fonctions fermaient un <thead> et un <tr> qu'elles n'ouvraient
    // JAMAIS: le lexer comptait 4 <thead> pour 6 </thead> dans ce fichier.
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{categories}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ufco-rows'>";
    // Position de depart des lignes, pour le comptage du pied.
    $__rows=count($html);

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error<br>$sql</div>";
        return false;
    }

    $TRCLASS=null;


    while ($ligne = pg_fetch_assoc($results)) {
        $TRCLASS=null;
        $category_id = $ligne["category_id"];
        $categoryname = $ligne["categoryname"];
        if(preg_match("#^reserved#i", $categoryname)){
            continue;
        }
        $text_category = $tpl->_ENGINE_parse_body($ligne["category_description"]);
        $category_icon = $ligne["category_icon"];
        $html[]="<td style='width:1%;vertical-align: top' nowrap>$category_id</td>";
        $html[]="<td style='width:99%'><strong style='font-size:18px'>$categoryname</strong><br><i>$text_category</i></td>";
        $html[]="</tr>";

    }

    // Le <tfoot> portait un pager footable qu'AUCUN init n'alimente sur cette
    // page: un pied de tableau vide. Il est remplace par le pied awc et son
    // pager cote client. Le nombre de lignes est compte sur les fragments
    // ajoutes depuis l'ouverture du <tbody>, ce qui evite de toucher a la
    // boucle de chaque tableau.
    $__c=0; foreach(array_slice($html,$__rows) as $__f){$__c+=substr_count($__f,"<tr");}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $__c {items}</span>";
    $html[]="<span class='awc-pg' id='ufco-pager'></span>";
    $html[]="</div></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body($html).awc_pager_js(50,"ufco-rows","ufco-pager");
    return true;

}
function remote_http_category_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }

    $category_id        = intval($_GET["remote-http-category-tabs"]);

    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $array[$ligne["categoryname"]]="$page?remote-http-category-popup=$category_id&function=$function";
    $array["{items}"]="$page?category-items=$category_id&function={$_GET["function"]}";
    echo $tpl->tabs_default($array);
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $EnableRemoteCategoriesServices=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));
    $array["{your_categories}"]="$page?table-start=yes&function=$function";
    if($EnableRemoteCategoriesServices==0) {
        $array["{main_parameters}"] = "fw.ufdb.categories.settings.php?parameters=yes&function=$function";
        $array["{schedule}"] = "fw.proxy.tasks.php?microstart=yes&ForceTaskType=3&function=$function";

        if ($users->AsProxyMonitor) {
            $array["{events}"] = "fw.ufdb.categories.events.php";
        }
    }
    echo $tpl->tabs_default($array);
    return true;
}
function jrows():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{data}", "$page?jrows-data={$_GET["jrows"]}");
}
function js_export():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $categoryid=$_GET["js-export"];
    $catname=$catz->CategoryIntToStr($categoryid);
    return $tpl->js_confirm_execute("{export} $catname","none","none","Loadjs('$page?js-export2=$categoryid')");
}
function js_export_confirm():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $categoryid=$_GET["js-export2"];
    $catname=$catz->CategoryIntToStr($categoryid);
   return $tpl->js_dialog6("{export}:$catname", "$page?js-export-perform=$categoryid",650);
}
function dnscatz_compile_single():bool{
    $tpl=new template_admin();
    $id=intval($_GET["dnscatz-compile-single"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscatz/permissions"),true);

    if(!isset($json["Status"])){
        return $tpl->js_error("Protocol error");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_DNSCATZ("/compile/$id"),true);

    if(!isset($json["category_id"])){
        return $tpl->js_error("Protocol error");
    }
    // dnscatz ne compile que la zone DNS. La base de filtrage web (domains.ufdb),
    // la seule que les proxys telechargent, n etait jamais reconstruite : les
    // sites ajoutes n arrivaient jamais sur les proxys.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/category/compile/$id");
    if(isset($json["message"])){
        admin_tracks("Category service: Compile category id #$id");
        return $tpl->js_ok($json["message"]);
    }
    return admin_tracks("Category service: Compile category id #$id");

}

function js_export_perform(){
    if(!fw_progress_page_priv("AsDansGuardianAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $categoryid=intval($_GET["js-export-perform"]);
    $catname=$catz->CategoryIntToStr($categoryid);


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/categorize.$categoryid.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/categorize.$categoryid.logs.txt";
    $ARRAY["CMD"]="/categories/export/$categoryid";
    $ARRAY["TITLE"]="{export} ".$catz->CategoryIntToStr($categoryid);
    $ARRAY["AFTER"]="LoadAjax('download-js-export-$categoryid','$page?js-export-download=$categoryid');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-js-export-$categoryid')";


    $html[]="<H2>{export}: $catname</H2>";
    $html[]="<div id='progress-js-export-$categoryid'></div>";
    $html[]="<div id='download-js-export-$categoryid'></div>";
    // $jsafter vient de framework_buildjs(): il porte des accolades, donc il ne
    // doit pas traverser _ENGINE_parse_body().
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>$jsafter</script>";
}
function js_export_download(){
    $tpl=new template_admin();
    $category_id=intval($_GET["js-export-download"]);
    $catz=new mysql_catz();
    $catname=$catz->CategoryIntToStr($category_id);
    $file_path=PROGRESS_DIR."/category-$category_id.gz";

    if(!is_file($file_path)){
        $html[]="
                ".$tpl->widget_style1("red-bg","fas fa-exclamation","{failed}","$catname Error!")."
            ";

        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    //<i class="fas fa-exclamation"></i>
    // <i class="fas fa-file-archive"></i>
    $filesize=FormatBytes(@filesize($file_path)/1024);

    $html[]="<a href='ressources/logs/web/category-$category_id.gz'>
                ".$tpl->widget_style1("navy-bg","fas fa-file-archive","$catname","$category_id.gz <small>($filesize)</small>")."</a>
            ";

    echo $tpl->_ENGINE_parse_body($html);
    return false;

}
function jrows_data(){
    $data= unserialize(base64_decode($_GET["jrows-data"]),array("allowed_classes"=>false));
    // Pentest 2026-11-04 : jrows-data etait recopie sans encodage dans la page (XSS).
    if(!is_array($data)){$data=array();}
    echo "<textarea style='width:100%;min-height:512px'>".req_html(@implode("\n",$data))."</textarea>";
}
function delete_site(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $q=new postgres_sql();
    $md5=$_GET["md"];


    $category_id=intval($_GET["category_id"]);
    $sitename=$_GET["delete-pattern"];
    $function=null;
    if(isset($_GET["function"])){
        $function=$_GET["function"]."()";
    }

    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categorytable FROM personal_categories WHERE category_id='$category_id'"));
    // Pentest 2026-11-04 : delete-pattern etait concatene dans la requete.
    $table=preg_replace('/[^A-Za-z0-9_]/','',strval($ligne["categorytable"]));


    $q->QUERY_SQL_PARAMS("DELETE FROM $table WHERE sitename=$1",array($sitename));
    admin_tracks("Remove $sitename FROM $table category $category_id");
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/category/flush/$category_id");
    echo "$('#$md5').remove();$function;";

}
function category_remove_all():bool{
    $tpl=new template_admin();
    $function=null;
    $users=new usersMenus();
    if(!$users->AsDansGuardianAdministrator){
        return $tpl->js_no_privileges();
    }
    if(isset($_GET["function"])){
        $function=$_GET["function"]."()";
    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ufdbcat.compile.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ufdbcat.compile.log";
    $ARRAY["CMD"]="/categories/remove-all";
    $ARRAY["TITLE"]="{remove_all_categories}";
    $ARRAY["AFTER"]="$function";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-ppcategories-restart')";
    return $tpl->js_confirm_delete("{remove_all_categories}", "none", "yes",$jsrestart);
}
function category_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["category-js"]);

    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }

    if($category_id>0){
        $q=new postgres_sql();
        $sql="SELECT categoryname FROM personal_categories WHERE category_id='$category_id'";
        $ligne=$q->mysqli_fetch_array($sql);
        $title="{category}: {$ligne["categoryname"]}";
    }else{
        $title="{new_category}";
    }

    $tpl->js_dialog1($title, "$page?category-popup=$category_id$function");
}
function category_items_add_js(){
    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["category-items-addjs"]);
    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categoryname FROM personal_categories WHERE category_id='$category_id'"));
    $title="{$ligne["categoryname"]}: {add_websites}";
    $tpl->js_dialog2($title, "$page?category-items-addpopup=$category_id$function");
}
function category_delete():bool{
    $tpl=new template_admin();
    // Pentest 2026-11-04 : category-delete etait recopie sans encodage dans la page (XSS).
    $categorykey=intval($_GET["category-delete"]);
    $function=null;
    if(isset($_GET["function"])){
        $function=$_GET["function"]."()";
    }

    return $tpl->js_confirm_delete("{category} $categorykey", "category-delete", $categorykey,"$function");
}
function category_remove(){
    $category=intval($_POST["category-delete"]);

    if($category==0){
        echo "Unknown category...\n";
        return;
    }

    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categoryname FROM personal_categories WHERE category_id='$category'"));
    $categoryname=$ligne["categoryname"];


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/category/delete/$category"),true);
    if(!isset($json["Status"])){
        echo "Bad Protocol";
        return;
    }
    if(!$json["Status"]){
        echo "($categoryname) ".$json["Error"];
        return;
    }
    admin_tracks("Removed category $categoryname ID.$category");


}
function category_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["category-popup"]);
    $users=new usersMenus();
    $function=null;$OFF=false;
    if(isset($_GET["function"])){
        $function=$_GET["function"]."()";
    }



    if($category_id==0){
        $jsafter="$function;dialogInstance1.close();";
        $form[]=$tpl->field_hidden("category_id", 0);
        $form[]=$tpl->field_text("newcat", "{category_name}", null,true);
        $form[]=$tpl->field_text("description", "{description}", null);
        $form[]=$tpl->field_checkbox("PublicMode","{shared_category}",0,false,"{shared_category_explain}");
        echo $tpl->form_outside("", @implode("\n", $form),null,"{add}",$jsafter,"AsDansGuardianAdministrator");
        return true;
    }

    $q=new postgres_sql();
    $sql="SELECT categoryname,official_category,free_category,meta FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    if($ligne["official_category"]==1){$OFF=true;}
    if($ligne["free_category"]==1){$OFF=true;}
    if($ligne["meta"]==1){$OFF=true;}

    $array=array();
    $functionjs=str_replace("()","",$function);
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    if($users->AsDansGuardianAdministrator) {
        $array[$ligne["categoryname"]] = "$page?category-edit=$category_id&function=$functionjs";
    }
    if($OFF){
        if($ManageOfficialsCategories==0){
            echo $tpl->tabs_default($array);
            return true;
        }
    }

    $UseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteCategoriesService"));
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    VERBOSE("UseRemoteCategoriesService=$UseRemoteCategoriesService, EnableLocalUfdbCatService=$EnableLocalUfdbCatService",__LINE__);

    if($UseRemoteCategoriesService==0) {
        $array["{items}"]="$page?category-items=$category_id&function={$_GET["function"]}";
        $array["{external_sources}"] = "$page?external-sources=$category_id&function={$_GET["function"]}";

        if ($EnableLocalUfdbCatService == 1) {
            $array["{orders}"] = "$page?category-orders=$category_id&function={$_GET["function"]}";
        }

        if ($users->AsDansGuardianAdministrator) {
            $array["{security}"] = "$page?category-security=$category_id&function={$_GET["function"]}";
        }
    }

    echo $tpl->tabs_default($array);
    return true;
}
function category_orders_save(){
    $tpl=new template_admin();
    // Pentest 2026-11-04 : category-orders/blacklist/whitelist/nocache/parent etaient concatenes dans la requete.
    $category_id=intval($_POST["category-orders"]);
    $q=new postgres_sql();

    $q->QUERY_SQL_PARAMS("UPDATE personal_categories SET 
    blacklist=$1,
    whitelist=$2,
    nocache=$3,
    parent=$4 WHERE category_id=$category_id",array(strval($_POST["blacklist"]),strval($_POST["whitelist"]),strval($_POST["nocache"]),strval($_POST["parent"])));

    if(!$q->ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($q->mysql_error));return;}
    admin_tracks("Updated category ACLs $category_id");
}
function category_orders():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $category_id=intval($_GET["category-orders"]);

    $q=new postgres_sql();

    if(!$q->FIELD_EXISTS("personal_categories","blacklist")) {
        $q->QUERY_SQL("ALTER TABLE personal_categories ADD blacklist smallint NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("personal_categories","whitelist")) {
        $q->QUERY_SQL("ALTER TABLE personal_categories ADD whitelist smallint NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("personal_categories","nocache")) {
        $q->QUERY_SQL("ALTER TABLE personal_categories ADD nocache smallint NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("personal_categories","parent")) {
        $q->QUERY_SQL("ALTER TABLE personal_categories ADD parent VARCHAR(90) NULL");
    }

    $ligne=$q->mysqli_fetch_array("SELECT * FROM personal_categories WHERE category_id=$category_id");

    $tpl->field_hidden("category-orders",$category_id);

    $form[]=$tpl->field_checkbox("blacklist","{blacklist}",$ligne["blacklist"]);
    $form[]=$tpl->field_checkbox("whitelist","{whitelist}",$ligne["whitelist"]);
    $form[]=$tpl->field_checkbox("nocache","{no_cache}",$ligne["nocache"]);
    $html[]=$tpl->form_outside("{orders}", $form,"{categoy_service_orders_explain}","{apply}",null,"AsDansGuardianAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function category_items_add_popup():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $category_id=intval($_GET["category-items-addpopup"]);
    $function=null;
    if(isset($_GET["function"])){
        $function=$_GET["function"]."()";
    }

    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categoryname,category_description FROM personal_categories WHERE category_id='$category_id'"));
    $categoryname=$ligne["categoryname"];
    $category_description=$ligne["category_description"];

    $form[]=$tpl->field_hidden("websites-categorykey", $category_id);
    $form[]=$tpl->field_checkbox("ForceCat","{force}",0,false,"{category_inject_force_explain}");
    $form[]=$tpl->field_checkbox("ForceExt","{no_extension_check}",0,false,"{free_cat_no_extension_check_explain}");
    $form[]=$tpl->field_textareacode("websites", "{websites}", null);

    $jsrestart=$tpl->framework_buildjs("/categories/memory/run/$category_id","$category_id.progress","categorize.manu.$category_id.log","progress-items-$category_id","dialogInstance2.close();$function;");

    $explain="$category_description<br>{perso_add_websites_categories_explain}";
    $html[]="<div id='progress-items-$category_id'></div>";
    $html[]=$tpl->form_outside("$categoryname &raquo;&raquo; {add_websites}", @implode("\n", $form),$explain,"{add_websites}",$jsrestart);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function category_items_add_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $websites=$_POST["websites"];
    $category_id=$_POST["websites-categorykey"];
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

    return true;

}
function category_edit():bool{
    $page               = CurrentPageName();
    $category_id        = intval($_GET["category-edit"]);
    $function=$_GET["function"];
    echo "<div id='category-edit-$category_id'></div>";
    echo "<script>LoadAjax('category-edit-$category_id','$page?category-flat=$category_id&function=$function');</script>";
    return true;
}
function category_edit_flat():bool{
    $tpl                = new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page               = CurrentPageName();
    $security           = "AsDansGuardianAdministrator";
    $function=$_GET["function"];
    $category_id        = intval($_GET["category-flat"]);
    $OFF                = false;
    $jsafter            = "";
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $category_description=$ligne["category_description"];
    if($ligne["official_category"]==1){
        $OFF=true;
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $security="license";
        }
    }
    if($ligne["free_category"]==1){$OFF=true;}
    $items=numberFormat($ligne["items"],0,""," ");
    if($OFF){
        $CURRENT=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
        $ROWS=$CURRENT[$category_id]["items"];
        $items=numberFormat($ROWS,0,""," ");
    }
    $meta=intval($ligne["meta"]);

    if($meta){
        return category_edit_remote();
    }
    $tpl->CLUSTER_CLI=true;
    $tpl->table_form_field_js("Loadjs('$page?category-edit-js=$category_id&function=$function');",$security);
    $tpl->table_form_field_text("{ID}"  , "$category_id &nbsp;|&nbsp; <small style='text-transform: none'>Table:&nbsp;{$ligne["categorytable"]}</small>",ico_params);
    $tpl->table_form_field_text("{category}", "{$ligne["categoryname"]}", ico_books);
    if(strlen($category_description)>2){
        $tpl->table_form_field_text("{description}", "<small>$category_description</small>", ico_books);
    }
    $tpl->table_form_field_text("{items}",$items,ico_list);

    $compiledate=$tpl->time_to_date($ligne["compiledate"],true);
    $tpl->table_form_field_text("{export}",$compiledate,ico_clock);

    $CategoryServiceRPZEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZEnabled"));
    if($CategoryServiceRPZEnabled==0){
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{POLICIES_ZONES}",0,ico_database);
    }else{
        $tpl->table_form_field_js("Loadjs('$page?category-edit-rpz=$category_id&function=$function');",$security);
        $tpl->table_form_field_bool("{POLICIES_ZONES}",$ligne["rpz"],ico_database);
    }
    $tpl->table_form_field_js("Loadjs('$page?category-edit-js=$category_id&function=$function');",$security);
    $tpl->table_form_field_bool("{shared_category}",$OFF,ico_user);

    if(strlen($function)>4){
        $jsafter="LoadAjax('category-edit-$category_id','$page?category-flat=$category_id&function=$function');$function()";
    }

    $compile=$tpl->framework_buildjs("/category/compile/$category_id",
    "ufdbcat.compile.progress","ufdbcat.compile.log","compile-progress-$category_id",$jsafter);

    $UseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteCategoriesService"));
    if($UseRemoteCategoriesService==0) {
        $tpl->table_form_button("{compile_this_category}", $compile, $security, ico_save);
    }

    $html[]="<div id='compile-progress-$category_id'></div>";
    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body($html);

    return true;
}
function category_edit_js():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page=CurrentPageName();
    $category_id=intval($_GET["category-edit-js"]);

    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }

    if($category_id>0){
        $q=new postgres_sql();
        $sql="SELECT categoryname FROM personal_categories WHERE category_id='$category_id'";
        $ligne=$q->mysqli_fetch_array($sql);
        $title="{category}: {$ligne["categoryname"]}";
    }else{
        $title="{new_category}";
    }

    return $tpl->js_dialog2($title, "$page?category-edit-popup=$category_id$function");
}
function remote_categories_service_js():bool{
    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page=CurrentPageName();
    return $tpl->js_dialog2("{use_remote_categories_services}",
        "$page?remote-categories-service-tabs=yes$function",650);
}
function remote_category_js():bool{
    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page=CurrentPageName();
    $q=new postgres_sql();
    $category_id=intval($_GET["category-dns-js"]);
    $sql="SELECT categoryname FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $title="{category}: {$ligne["categoryname"]}";
    return $tpl->js_dialog2($title,
        "$page?remote-category-popup=$category_id$function",550);
}
function remote_http_category_js():bool{
    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page=CurrentPageName();
    $q=new postgres_sql();
    $category_id=intval($_GET["category-http-js"]);
    $sql="SELECT categoryname FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $title="{category}: {$ligne["categoryname"]}";
    return $tpl->js_dialog2($title,
        "$page?remote-http-category-tabs=$category_id$function",550);

}
function remote_http_category_popup(){
    $tpl=new template_admin();
    $category_id        = intval($_GET["remote-http-category-popup"]);
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $category_description=$ligne["category_description"];
    $items=numberFormat($ligne["items"],0,""," ");
    $item_text=$items;
    $title="{$ligne["categoryname"]}";

    $RemoteCategoriesServicesPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicePort"));
    $RemoteCategoriesServicesAddress=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceAddr");
    $Addr="http://$RemoteCategoriesServicesAddress:$RemoteCategoriesServicesPort";

    $category_dns_explain=$tpl->_ENGINE_parse_body("{category_http_explain}");
    $category_dns_explain=str_replace("%s",$Addr,$category_dns_explain);
    $category_dns_explain=$tpl->_ENGINE_parse_body("{category_dns_explain}");
    $category_dns_explain=str_replace("%s",$Addr,$category_dns_explain);
    $tpl->table_form_section($title,$category_dns_explain);
    $tpl->table_form_field_text("{ID}",$category_id,ico_params);
    $tpl->table_form_field_text("{key}",$ligne["categorykey"],ico_key);
    $tpl->table_form_field_text("{description}",$category_description,ico_infoi);
    $tpl->table_form_field_text("{items}",$item_text,ico_list_opt);
    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    return true;
}
function remote_category_popup(){
    $tpl=new template_admin();
    $category_id        = intval($_GET["remote-category-popup"]);
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $category_description=$ligne["category_description"];
    $items=numberFormat($ligne["items"],0,""," ");
    $item_text=$items;
    $title="{$ligne["categoryname"]}";


    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesAddress"));
    $RemoteCategoriesServicesPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesPort"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));
    $Addr="{DNS}: $RemoteCategoriesServicesDomain";
    if ($RemoteCategoriesServicesPort == 0) {
        $RemoteCategoriesServicesPort = 3477;
    }
    if ($RemoteCategoriesServicesDomain == null) {
        $RemoteCategoriesServicesDomain = "categories.tld";
    }
    if($RemoteCategoriesServicesRemote==1){
        $Addr="$RemoteCategoriesServicesAddress:$RemoteCategoriesServicesPort@$RemoteCategoriesServicesDomain";
    }


    $category_dns_explain=$tpl->_ENGINE_parse_body("{category_dns_explain}");
    $category_dns_explain=str_replace("%s",$Addr,$category_dns_explain);
    $tpl->table_form_section($title,$category_dns_explain);
    $tpl->table_form_field_text("{ID}",$category_id,ico_params);
    $tpl->table_form_field_text("{key}",$ligne["categorykey"],ico_key);
    $tpl->table_form_field_text("{description}",$category_description,ico_infoi);
    $tpl->table_form_field_text("{items}",$item_text,ico_list_opt);
    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    return true;



}

function remote_categories_service_tabs():bool{
    $tpl=new template_admin();
    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    $page=CurrentPageName();
    $array["{CategoryDatabaseReplication}"]="$page?remote-categories-service-popup=yes$function";
    $array["{RemoteCategoryQueries}"]="$page?remote-categories-service-queries=yes$function";
    echo $tpl->tabs_default($array);
    return true;
}
function remote_categories_service_fetch():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/remote/service/check"));
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    return $tpl->js_ok("{success}");
}

function remote_categories_service_queries():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $function="";
    if(isset($_GET["function"])){
        $function="{$_GET["function"]}();";
    }

    $EnableRemoteCategoriesServices = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));
    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesAddress"));
    $RemoteCategoriesServicesPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesPort"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));




    if ($RemoteCategoriesServicesPort == 0) {
        $RemoteCategoriesServicesPort = 3477;
    }
    if ($RemoteCategoriesServicesDomain == null) {
        $RemoteCategoriesServicesDomain = "categories.tld";
    }

        $form[] = $tpl->field_checkbox("EnableRemoteCategoriesServices", "{use_remote_categories_services}", $EnableRemoteCategoriesServices, "RemoteCategoriesServicesDomain,RemoteCategoriesServicesRemote,RemoteCategoriesServicesAddress,RemoteCategoriesServicesPort,RemoteCategoriesServicesDomain");
    $RemoteCategoriesServiceUseArticaTech=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceUseArticaTech");

    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if($EnableLocalUfdbCatService==1){
        $form[]=$tpl->field_checkbox("RemoteCategoriesServiceUseArticaTech","{query_artica_db_unknown}",$RemoteCategoriesServiceUseArticaTech);
    }

        $form[] = $tpl->field_checkbox("RemoteCategoriesServicesRemote", "{direct_connection}", $RemoteCategoriesServicesRemote, "RemoteCategoriesServicesAddress,RemoteCategoriesServicesPort");
        $form[] = $tpl->field_text("RemoteCategoriesServicesAddress", "{remote_server_address}", $RemoteCategoriesServicesAddress);
        $form[] = $tpl->field_text("RemoteCategoriesServicesPort", "{remote_server_port}", $RemoteCategoriesServicesPort);
        $form[] = $tpl->field_text("RemoteCategoriesServicesDomain", "{SiteDomain}", $RemoteCategoriesServicesDomain);

    $myform         = $tpl->form_outside(null, $form,"{RemoteCategoryQueries_explain}","{apply}","$function","AsDansGuardianAdministrator");
    $html[]=$tpl->_ENGINE_parse_body($myform);
    echo @implode("\n",$html);
    return true;

}

function remote_categories_service_popup():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $function="";
    if(isset($_GET["function"])){
        $function="{$_GET["function"]}();";
    }

    $UseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteCategoriesService"));
    $RemoteCategoriesServiceSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceSSL"));
    $RemoteCategoriesServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicePort"));
    $RemoteCategoriesServiceAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceAddr");
    $RemoteCategoriesServiceMetaDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceMetaDebug"));
    $RemoteCategoriesServiceIface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServiceIface"));
    if($RemoteCategoriesServicePort==0){
        $RemoteCategoriesServicePort=9905;
    }

    $form[] = $tpl->field_checkbox("UseRemoteCategoriesService","{enabled}",$UseRemoteCategoriesService);
    $form[] = $tpl->field_interfaces("RemoteCategoriesServiceIface","{outgoing_interface}",$RemoteCategoriesServiceIface);
    $form[] = $tpl->field_text("RemoteCategoriesServiceAddr", "{remote_server_address}", $RemoteCategoriesServiceAddr);
    $form[] = $tpl->field_numeric("RemoteCategoriesServicePort","{remote_server_port} (HTTP)",$RemoteCategoriesServicePort);
    $form[] = $tpl->field_checkbox("RemoteCategoriesServiceSSL","{use_ssl}",$RemoteCategoriesServiceSSL);
    $form[] = $tpl->field_checkbox("RemoteCategoriesServiceMetaDebug","{debug}",$RemoteCategoriesServiceMetaDebug);
    echo $tpl->form_outside("", $form,"{CategoryDatabaseReplication_explain}","{apply}","$function","AsDansGuardianAdministrator");
    return true;
}
function remote_categories_service_save():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    if($_POST["RemoteCategoriesServiceUseArticaTech"]==1){
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RemoteCategoriesServiceUseArticaTech",0);
            echo $tpl->post_error("{articadb_error_license}");
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/remote/service/check");
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if($EnableLocalUfdbCatService==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnscatz/reconfigure");
    }

    return admin_tracks_post("Saving the use of a remote categories service");

}
function category_edit_rpz_js():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page=CurrentPageName();
    $category_id=intval($_GET["category-edit-rpz"]);

    $function=null;
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    $q=new postgres_sql();
    $sql="SELECT categoryname FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $title="{category}: {$ligne["categoryname"]}";
    return $tpl->js_dialog2($title, "$page?category-edit-rpz-popup=$category_id$function",550);
}
function category_edit_rpz_popup():bool{
    $tpl                = new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page               = CurrentPageName();
    $security           = "AsDansGuardianAdministrator";
    $function=$_GET["function"];
    $category_id        = intval($_GET["category-edit-rpz-popup"]);
    if($category_id==0){
        echo $tpl->div_error("ID == 0!?");
        return false;
    }
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);

    $form[]=$tpl->field_hidden("rpz_id", $category_id);
    $form[]=$tpl->field_checkbox("rpz", "{publish_as_rpz}", $ligne["rpz"]);

    $jsafter="Loadjs('fw.ufdb.categories.php?filltable=$category_id');LoadAjax('category-edit-$category_id','$page?category-flat=$category_id&function=$function');dialogInstance2.close();";
    if(strlen($function)>4){
        $jsafter="$jsafter;$function()";
    }

    $RPZActions[0]="{default}";
    $RPZActions[1]="{drop}";
    $RPZActions[2]="NXDOMAIN";
    $RPZActions[3]="{whitelist}";
    $RPZActions[4]="{redirect_ip_address}";



    $form[]=$tpl->field_array_hash($RPZActions,"rpzaction","{action}",$ligne["rpzaction"]);
    $form[]=$tpl->field_ipaddr("rpzredirect","{redirect_ip_address}",$ligne["rpzredirect"]);


    echo $tpl->form_outside("",$form,"","{apply}",$jsafter,$security);
    return true;
}
function category_edit_rpz_save():bool{
    $tpl = new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $q=new postgres_sql();
    $rpzaction=intval($_POST["rpzaction"]);
    $category_id=intval($_POST["rpz_id"]);
    $rpz=intval($_POST["rpz"]);
    $rpzredirect=trim($_POST["rpzredirect"]);
    $q->QUERY_SQL("UPDATE personal_categories SET 
                        rpzaction=$rpzaction,
                        rpzredirect='$rpzredirect',
                        rpz=$rpz WHERE category_id=$category_id");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/server/rpz/flush");
    return admin_tracks_post("Update RPZ category feature for category #$category_id");
}
function category_edit_general_popup():bool{
    $tpl                = new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $page               = CurrentPageName();
    $security           = "AsDansGuardianAdministrator";
    $function=$_GET["function"];
    $category_id        = intval($_GET["category-edit-popup"]);
    $OFF                = false;
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE category_id='$category_id'";
    $ligne=$q->mysqli_fetch_array($sql);
    $error=null;
    $category_description=$ligne["category_description"];
    if($ligne["official_category"]==1){
        $OFF=true;
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $security="license";

        }

    }
    if($ligne["free_category"]==1){$OFF=true;}
    $items=numberFormat($ligne["items"],0,""," ");
    $item_text="$items {items}";
    if($OFF){
        $CURRENT=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
        $ROWS=$CURRENT[$category_id]["items"];
        $item_text=numberFormat($ROWS,0,""," ")." {items}";
    }
    $meta=intval($ligne["meta"]);

    if($meta){
        return category_edit_remote();
    }

    $jsafter="Loadjs('fw.ufdb.categories.php?filltable=$category_id');LoadAjax('category-edit-$category_id','$page?category-flat=$category_id&function=$function');dialogInstance2.close();";
    if(strlen($function)>4){
        $jsafter="$jsafter;$function()";
    }
    $form[]=$tpl->field_hidden("category_id", $category_id);
    $form[]=$tpl->field_text("categorykey", "{category_name}", $ligne["categoryname"]);
    $form[]=$tpl->field_text("category_description", "{description}", $ligne["category_description"]);
    if($OFF){
        $form[]=$tpl->field_hidden("PublicMode", 0);
    }else{
        $form[]=$tpl->field_checkbox("PublicMode","{shared_category}",$ligne["publicmode"],false,"{shared_category_explain}");
    }

    echo $tpl->_ENGINE_parse_body($error).$tpl->form_outside($ligne["categoryname"]."&nbsp;&nbsp;-&nbsp;&nbsp;<strong style='font-size:11px;'>$item_text</strong>&nbsp;<small>(ID:$category_id)</small><p style='font-size:12px;border-top: 1px solid #5C5C5C;margin-bottom:25px'>$category_description</p>",$form,"","{apply}",$jsafter,$security);
return true;
}
function category_edit_remote():bool{
    $tpl                = new template_admin();
    echo $tpl->div_explain("{this_category_is_from_category_service}");
    return true;
}
function category_save():bool{

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_POST["category_description"]=str_replace("'","`",$_POST["category_description"]);

    // Pentest 2026-11-04 : categorykey, PublicMode et category_id etaient concatenes dans la requete.
    // PostgreSQL (standard_conforming_strings) : doubler la quote suffit dans un litteral '...'.
    $categorykey=str_replace("'","''",strval($_POST["categorykey"]));
    $category_description=str_replace("'","''",strval($_POST["category_description"]));
    $PublicMode=intval($_POST["PublicMode"]);
    $category_id=intval($_POST["category_id"]);
    $sql="UPDATE personal_categories SET categoryname='$categorykey', category_description='$category_description', PublicMode='$PublicMode' WHERE category_id='$category_id'";
    $q=new postgres_sql();
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks_post("Save category #{$_POST["category_id"]}");
}
function category_items_delete_sql(){
    $tpl=new template_admin();
    $SQL= $_SESSION["CATEGORY_SEARCH"];
    $id=intval($_GET["id"]);
    $function=$_GET["function"];
    if(strlen($SQL)==0){
        $SQL="{all} {records}";
    }
    $tpl->js_confirm_delete($SQL, "DELETE_SQL",$id,"$function()");
}
function category_items_delete_sql_perform():bool{

    $search= $_SESSION["CATEGORY_SEARCH"];
    $category_id=intval($_POST["DELETE_SQL"]);

    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categorytable FROM personal_categories WHERE category_id='$category_id'"));
    // Pentest 2026-11-04 : la recherche (CATEGORY_SEARCH) etait concatenee dans la requete.
    $table=preg_replace('/[^A-Za-z0-9_]/','',strval($ligne["categorytable"]));
    $sql=null;

    if(preg_match("#(limit)(\s+|=|)([0-9]+)#i", $search,$re)){
        $search=trim(str_replace("$re[1]$re[2]$re[3]", "", $search));

    }


    if(preg_match("#regex:(.+)#", $search,$re)){
        $search=trim($re[1]);
        $sql="DELETE from $table WHERE sitename ~* $1";
    }

    if($sql==null){
        if($search==null){$search="*";}
        if(strpos(" $search ", "*")>0){
            $search=str_replace("*", "%", $search);
            $sql="DELETE from $table WHERE sitename LIKE $1";
        }
    }

    if($sql==null){
        echo "Bad search pattern";
        return false;
    }

    $q->QUERY_SQL_PARAMS($sql,array($search));
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/category/flush/$category_id");
    return true;
}
function category_items():bool{
    $page=CurrentPageName();
    $categorykey=intval($_GET["category-items"]);
    $EnableMenu=intval($_GET["EnableMenu"]);
    $function=$_GET["function"];
    echo "<div id='category-div-items-main'></div>
	<script>LoadAjaxSilent('category-div-items-main','$page?category-step1=$categorykey&EnableMenu=$EnableMenu&function=$function');</script>	
			
	";
return true;

}
function category_step_buttons():bool{
    $categorykey=intval($_GET["categorykey"]);
    $function=$_GET["function"];
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $meta       = intval($_GET["meta"]);

    $topbuttons=array();

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/category/cacheinfo/$categorykey"),true);

    $jsrestart=$tpl->framework_buildjs("/category/compile/$categorykey",
    "ufdbcat.compile.progress","ufdbcat.compile.txt",
        "compile-category-single","$function();document.getElementById('compile-category-single').innerHTML=''");





    $jsDelete="Loadjs('$page?sql-delete=yes&id=$categorykey&function=$function');";
    $PowerDNSEnableClusterSlave = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    //$html[]=$tpl->_ENGINE_parse_body("<div class=\"btn-group\" data-toggle=\"buttons\">$DropDownButton");
    if($PowerDNSEnableClusterSlave==0) {
        if($categorykey>0) {
            if($meta==0) {
                $add = "Loadjs('$page?category-items-addjs=$categorykey&function=$function')";
                $topbuttons[] = array("$add", ico_plus, "{add_websites}");
            }
        }
    }
    $topbuttons[] = array("$jsrestart", "fa fa-download", "{compile_category}");



    if($PowerDNSEnableClusterSlave==0) {
        if($categorykey>0) {
            if($meta==0) {
                $topbuttons[] = array("$jsDelete", ico_trash, "{remove_all_items}");
            }
        }
    }

    
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));

    if(isset($data["TimeStamp"])){
        if($data["TimeStamp"]>0){
            $distantce=distanceOfTimeInWords($data["TimeStamp"],time());
            $f[]="<div style='margin-top:5px;margin-bottom:5px;padding-right:8px;text-align:right'>";
            $f[]="{compile_time}: ".$tpl->time_to_date($data["TimeStamp"],true)." - $distantce";

            if($data["RepoEnabled"]==1){
                $class="text-muted";
                if($data["RepoTimeStamp"]>0) {
                    if($data["RepoFileMint"]>$data["FileMin"]+5){
                        $class="text-warning font-bold";
                    }

                    $distantce = distanceOfTimeInWords($data["RepoTimeStamp"], time());
                    $f[] = "<span class='$class'>{published}: " . $tpl->time_to_date($data["RepoTimeStamp"], true) . " - $distantce</span>";
                }
            }

            $f[]="</div>";
            echo $tpl->_ENGINE_parse_body($f);
        }
    }

    return true;
}
function category_security(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["category-security"]);
    $q=new postgres_sql();
    $t=time();
    $results=$q->QUERY_SQL("SELECT * FROM personal_categories_privs WHERE category_id='$category_id'");


    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{privileges}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ufcs-rows'>";
    // Position de depart des lignes, pour le comptage du pied.
    $__rows=count($html);
    $TRCLASS=null;

    while ($ligne = pg_fetch_assoc($results)) {
        $TRCLASS=null;
        $md=md5(serialize($ligne));
        $dngroup=$ligne["dngroup"];
        $id=$ligne["id"];



        $delete=$tpl->icon_delete("Loadjs('$page?delete-priv=$id&md=$md')");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><strong>$dngroup</strong></td>";
        $html[]="<td class='center' style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";

    }
    // Le <tfoot> portait un pager footable qu'AUCUN init n'alimente sur cette
    // page. Le nombre de lignes est compte sur les fragments ajoutes depuis
    // l'ouverture du <tbody>, ce qui evite de toucher a la boucle.
    $__c=0; foreach(array_slice($html,$__rows) as $__f){$__c+=substr_count($__f,"<tr");}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $__c {items}</span>";
    $html[]="<span class='awc-pg' id='ufcs-pager'></span>";
    $html[]="</div></div></div>";

    // Le <script> partait dans $html, donc dans _ENGINE_parse_body(), dont la
    // regex #{(.+?)}# lit toute accolade JavaScript comme un {token}.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"ufcs-rows","ufcs-pager");
}
function delete_privs(){
    $md=$_GET["md"];

    $id=intval($_GET["delete-priv"]);
    $tpl=new template_admin();
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM personal_categories_privs WHERE id=$id");
    if(!$q->ok){
        echo $tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();";

}
function category_step1(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $categorykey=intval($_GET["category-step1"]);
    $EnableMenu=intval($_GET["EnableMenu"]);
    $TRCLASS=null;
    if($EnableMenu==1){
        $array=array();
        $q=new postgres_sql();
        $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
        $sql="SELECT * FROM personal_categories WHERE official_category=0 AND meta=0 order by categoryname ";
        if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories order by categoryname";}
        $results=$q->QUERY_SQL($sql);
        while ($ligne = pg_fetch_assoc($results)) {
            $TRCLASS=null;
            $category_id=$ligne["category_id"];
            $categoryname=$ligne["categoryname"];
            if(preg_match("#reserved#", $categoryname)){continue;}
            $array[$categoryname]="LoadAjaxSilent('category-div-items-main','$page?category-step1=$category_id&EnableMenu=$EnableMenu&function={$_GET["function"]}');";
        }
    }


    $t=time();
    $html[]="<div id='compile-category-single' style='margin-top:10px'></div>";
    $html[]="<div id='compile-category-buttons-$categorykey' style='margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&category-items-table=yes&t=$t&categorykey=$categorykey");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function category_items_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["categorykey"]);
    if($category_id==0){return;}
    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categorytable,meta FROM personal_categories WHERE category_id='$category_id'"));
    // Pentest 2026-11-04 : search etait concatene dans la requete.
    $table=preg_replace('/[^A-Za-z0-9_]/','',strval($ligne["categorytable"]));
    $meta=intval($ligne["meta"]);
    $search=trim($_GET["search"]);
    $search=$q->SearchAntiXSS($search);

    $jsbuttons=$tpl->RefreshInterval_js("compile-category-buttons-$category_id",$page,"compile-category-buttons=yes&function={$_GET["function"]}&categorykey=$category_id&meta=$meta");

    $_SESSION["CATEGORY_SEARCH"]=$search;
    $LIMIT=250;
    $ORDER=null;
    if(preg_match("#ASC#", $search,$re)){
        $ORDER=" ORDER BY sitename ASC";
        $search=str_replace("ASC", "", $search);
    }
    if(preg_match("#DESC#", $search,$re)){
        $search=str_replace("DESC", "", $search);
        $ORDER=" ORDER BY sitename DESC";
    }
    if(preg_match("#(limit)(\s+|=|)([0-9]+)#i", $search,$re)){
        $search=str_replace("$re[1]$re[2]$re[3]", "", $search);
        $LIMIT=intval($re[3]);
    }
    $sqlparams=array();
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";

    if(!$q->TABLE_EXISTS($table)){$q->CREATE_CATEGORY_TABLE($table);}


    $sql="SELECT sitename from $table$ORDER LIMIT $LIMIT";

    if($search<>null){
        $sql=null;
        if(preg_match("#regex:(.+)#i", $search,$re)){
            $search=trim($re[1]);
            $sql="SELECT sitename from $table WHERE sitename ~* $1$ORDER FETCH FIRST $LIMIT ROWS ONLY;";
            $sqlparams=array($search);
        }

        if($sql==null){
            if(strpos(" $search ", "*")>0){
                $search=trim(str_replace("*", "%", $search));
                $sql="SELECT sitename from $table WHERE sitename LIKE $1$ORDER FETCH FIRST $LIMIT ROWS ONLY;";
                $sqlparams=array($search);
            }
        }

        if($sql==null){
            $search=trim($search);
            $sql="SELECT sitename from $table WHERE sitename=$1$ORDER FETCH FIRST $LIMIT ROWS ONLY;";
            $sqlparams=array($search);

        }
    }

    $results=count($sqlparams)>0?$q->QUERY_SQL_PARAMS($sql,$sqlparams):$q->QUERY_SQL($sql);
    $zrows=array();
    $ROWS=array();

    if(!$q->ok){
        // Pentest 2026-11-04 : la requete (avec la recherche saisie) n'est plus renvoyee au navigateur.
        $sqlErr=htmlspecialchars($q->mysql_error,ENT_QUOTES);
        echo "<div class='alert alert-danger' style='margin-top:10px'>$sqlErr</div>
        <script>$jsbuttons</script>";
        return;
    }

    if(pg_num_rows($results)==0){
        echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'><H2>{no_data}</H2><strong>".htmlspecialchars($sql,ENT_QUOTES)."</strong></div> <script>$jsbuttons</script>");
        return;
    }
    $num=0;
    $TRCLASS=null;
    while ($ligne = pg_fetch_assoc($results)) {
        $TRCLASS=null;
        $num++;
        $pattern=$ligne["sitename"];
        $text_class=null;
        $patternenc=urlencode($pattern);

        $zmd5=md5($pattern);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-pattern=$patternenc&category_id=$category_id&md=$zmd5&function={$_GET["function"]}')");
        if($meta==1){
            $delete="&nbsp;";
        }
        $ROWS[]="<tr class='$TRCLASS' id='$zmd5'>";
        $ROWS[]="<td class=\"$text_class\" style='width: 1%'>$num</td>";
        $ROWS[]="<td class=\"$text_class\" style='width: 99%'>$pattern</td>";
        $ROWS[]="<td style='width:1%' class='center' nowrap>$delete</td>";
        $ROWS[]="</tr>";
        $zrows[]=$pattern;

    }
    if(strlen($search)==0){
        $search="*";
    }
    if($search=="%"){
        $search="*";
    }
    $jrows="Loadjs('$page?jrows=".base64_encode(serialize($zrows))."')";

    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites} ".$tpl->td_href("{search}:&nbsp;&nbsp;&laquo;&nbsp;".req_html($search)."&nbsp;&raquo;","{raw_format}",$jrows)."</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ufci-rows'>";
    // Position de depart des lignes, pour le comptage du pied.
    $__rows=count($html);
    $html[]=@implode("\n", $ROWS);
    // Le <tfoot> portait un pager footable qu'AUCUN init n'alimente sur cette
    // page. Le nombre de lignes est compte sur les fragments ajoutes depuis
    // l'ouverture du <tbody>, ce qui evite de toucher a la boucle.
    $__c=0; foreach(array_slice($html,$__rows) as $__f){$__c+=substr_count($__f,"<tr");}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $__c {items}</span>";
    $html[]="<span class='awc-pg' id='ufci-pager'></span>";
    $html[]="</div></div></div>";

    // $jsbuttons vient de RefreshInterval_js(): il porte des accolades, donc il
    // ne doit pas traverser l'analyse.
    $script="<script>\n".$jsbuttons."\nNoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"ufci-rows","ufci-pager");


}
function category_new_save():bool{

    $_POST["personal_database"]=url_decode_special_tool($_POST["newcat"]);
    $_POST["category_text"]=url_decode_special_tool($_POST["description"]);
    $org=$_POST["personal_database"];
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    if($_POST["personal_database"]==null){
        $tpl->post_error("No category set or wrong category name $org");
        return false;
    }

    if($_POST["personal_database"]=="security"){$_POST["personal_database"]="security2";}

    $array=array(
        "categoryname"=>$_POST["personal_database"],
        "description"=>$_POST["category_text"],
        "public_mode"=>$_POST["PublicMode"],
        "id"=>0
    );


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/category/create",$array),true);
    if(!$json["Status"]){
        echo $tpl->post_error($json["Error"]);
        return false;
    }
    return admin_tracks("Create a new personal category {$_POST["personal_database"]} ({$_POST["category_text"]})");
}
function filltable():bool{
    $sid=$_GET["filltable"];
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="LoadAjaxSilent('category_name_$sid','$page?td-content=$sid&field=categoryname');";
    $f[]="LoadAjaxSilent('category_text_$sid','$page?td-content=$sid&field=category_description');";
    $f[]="LoadAjaxSilent('category_items_$sid','$page?td-content=$sid&field=items');";
    echo @implode("\n",$f);
    return true;
}
function td_content():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $category_id=intval($_GET["td-content"]);
    // Pentest 2026-11-04 : field (nom de colonne) etait concatene dans la requete.
    $field=strval($_GET["field"]);
    if(!in_array($field,array("items","categoryname","category_description"),true)){return false;}
    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT categorytable,$field FROM personal_categories WHERE category_id=$category_id");
    if(!$q->ok){echo "ERROR!";return false;}

    if($field=="items"){
        $ligne["items"]=$q->COUNT_ROWS_LOW($ligne["categorytable"]);
        $itemsEncTxt=numberFormat( $ligne["items"],0,""," ");
        echo $itemsEncTxt;
        return false;
    }

    if($field=="categoryname"){
        echo $tpl->td_href($ligne["categoryname"],"{click_to_edit}","Loadjs('$page?category-js=$category_id')");
        return true;
    }

    echo $tpl->_ENGINE_parse_body($ligne[$field]);
    return true;
}
function external_sources_edit_js():bool{
    $md5url                     = trim($_GET["external-sources-edit"]);
    $category_id                = intval($_GET["category-id"]);
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();

    if($md5url<>null){
        $title="{edit}:$md5url";
    }else{
        $title="{new_source}";
    }
   return $tpl->js_dialog4($title,"$page?external-sources-popup=$md5url&category-id=$category_id");

}
function all_categories_js():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    return $tpl->js_dialog4("{all_categories}","$page?all-categories-tabs=yes");
}
function external_sources_popup(){
    $md5url                     = trim($_GET["external-sources-popup"]);
    $category_id                = intval($_GET["category-id"]);
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();
    $tpl->CLUSTER_CLI           = True;
    $title                      = null;
    $btname                     = "{apply}";

    if($category_id==0){
        echo $tpl->div_error("No Category ID !!!");
        return false;
    }


    $SOURCES=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesExternalSources"));
    $SOURCES_TABLE=unserialize($SOURCES);
    if(!isset($SOURCES_TABLE[$category_id])){
        $SOURCES_TABLE[$category_id]=array();
    }
    if(!isset($SOURCES_TABLE[$category_id][$md5url])){
        $SOURCES_TABLE[$category_id][$md5url]["URL"]=null;
        $SOURCES_TABLE[$category_id][$md5url]["ENABLED"]=1;
        $SOURCES_TABLE[$category_id][$md5url]["MD5"]=null;
        $SOURCES_TABLE[$category_id][$md5url]["DATE"]=time();
        $SOURCES_TABLE[$category_id][$md5url]["FORCE"]=0;
        $SOURCES_TABLE[$category_id][$md5url]["EMPTY"]=0;
    }
    $ligne=$SOURCES_TABLE[$category_id][$md5url];
    if($md5url==null){$title="{new_source}";$btname="{add}";}
    $tpl->field_hidden("category_id",$category_id);
    $tpl->field_hidden("md5url",$md5url);
    $form[]=$tpl->field_checkbox("ENABLED","{enabled}",$ligne["ENABLED"]);
    $form[]=$tpl->field_text("URL","{url_source}",$ligne["URL"]);
    $form[]=$tpl->field_checkbox("FORCE","{force}",$ligne["FORCE"],false,"{category_inject_force_explain}");
    $form[]=$tpl->field_checkbox("EMPTY","{empty_database_before_import}",$ligne["EMPTY"]);

    echo $tpl->form_outside($title,$form,null,$btname,
        "dialogInstance4.close();LoadAjax('table-external-sources-$category_id','$page?external-sources-table=$category_id');","AsDansGuardianAdministrator");
    return true;
}
function external_sources_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SOURCES=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesExternalSources"));
    $SOURCES_TABLE=unserialize($SOURCES);
    $title="Edit source url";
    $category_id=$_POST["category_id"];
    $md5url=$_POST["md5url"];
    if($md5url==null){
        $title="Add new source url";
        $md5url=md5($_POST["URL"]);

    }
    $SOURCES_TABLE[$category_id][$md5url]["FILECRC"]=null;
    $SOURCES_TABLE[$category_id][$md5url]["FILETIME"]=0;
    $SOURCES_TABLE[$category_id][$md5url]["TIMESTAMP"]=0;
    foreach ($_POST as $key=>$val){
        $SOURCES_TABLE[$category_id][$md5url][$key]=$val;

    }
    $SOURCES_TABLE2=serialize($SOURCES_TABLE);
    $SOURCES=base64_encode($SOURCES_TABLE2);
    admin_tracks_post($title);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesExternalSources",$SOURCES);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/external/run");

}
function external_sources_table(){
    $tpl                        = new template_admin();
    $tpl->CLUSTER_CLI           = True;
    $page                       = CurrentPageName();
    $users                      = new usersMenus();
    $t                          = time();
    $category_id                = intval($_GET["external-sources-table"]);
    $topbuttons                 = array();



    if($users->AsDansGuardianAdministrator){
           $topbuttons[] = array("Loadjs('$page?external-sources-edit=&category-id=$category_id');", "fa-cloud-plus", "{new_source}", "green");
    }

    $bbtn = $tpl->th_buttons($topbuttons);

    $SOURCES_TABLE=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesExternalSources"));
    $html[]=$bbtn;
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{source}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{updated}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ufce-rows'>";
    // Position de depart des lignes, pour le comptage du pied.
    $__rows=count($html);

    $DivsRun=array();
    $TRCLASS=null;
    $DATAS=$SOURCES_TABLE[$category_id];
    $c=0;
   foreach ($DATAS as $md5url=>$ligne){
       $c++;
        if($md5url==null){$md5url=time()+$c;}
        $TRCLASS=null;
        $URL=$ligne["URL"];
        $TIME=intval($ligne["TIMESTAMP"]);
        if(!isset($ligne["ERROR"])){$ligne["ERROR"]=null;}
       if(!isset($ligne["IMPORTED"])){$ligne["IMPORTED"]=0;}
       if(!isset($ligne["LINES"])){$ligne["LINES"]=0;}
        $FORCE=$ligne["FORCE"];
        $FORCE_TEXT=null;
        $LINES_TEXT=null;
        $TIME_TEXT=$tpl->icon_nothing();
        $ERROR_TEXT=null;
        if($TIME>0){
            $TIME_TEXT=distanceOfTimeInWords($TIME,time());
        }

        if($ligne["IMPORTED"]>0){
            $LINES_TEXT="&nbsp;<small><strong>({$ligne["LINES"]}/{$ligne["IMPORTED"]})</small></strong>";
        }

        $link="Loadjs('$page?external-sources-edit=$md5url&category-id=$category_id')";
        $link_disale="Loadjs('$page?external-sources-enable=$md5url&category-id=$category_id')";
        $link_remove="Loadjs('$page?external-sources-remove=$md5url&category-id=$category_id')";
        $md5Full=md5("$category_id$md5url");
       $DivsRun[]="<div id='extern$md5Full'></div>";
        $linkRun=$tpl->framework_buildjs("/categories/external/single/$category_id/$md5url",
        "extern.$category_id.$md5url.progress",
       "extern.$category_id.$md5url.log","extern$md5Full"
        );

       $ENABLED=$ligne["ENABLED"];
       if($ligne["ERROR"]<>null){
           $ERROR_TEXT="<br><span class='text-danger'>{$ligne["ERROR"]}</span>";
       }
       $disable=$tpl->icon_check($ENABLED,$link_disale,null,"AsDansGuardianAdministrator");
       $delete=$tpl->icon_delete($link_remove,"AsDansGuardianAdministrator");
       $URL=$tpl->td_href($URL,null, $link);
        if($FORCE==1){
            $FORCE_TEXT=" ({force})";
        }


        $Run=$tpl->icon_run($linkRun,"AsDansGuardianAdministrator");
        $html[]="<tr class='$TRCLASS' id='$md5url'>";
        $html[]="<td style='width:99%'><i class='fa-solid fa-cloud'></i>&nbsp;&nbsp;$URL$FORCE_TEXT$LINES_TEXT$ERROR_TEXT</td>";
        $html[]="<td style='width:1%' nowrap>$TIME_TEXT</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>$Run</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>$disable</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>$delete</td>";
        $html[]="</tr>";

    }

    // Le <tfoot> portait un pager footable qu'AUCUN init n'alimente sur cette
    // page. Le nombre de lignes est compte sur les fragments ajoutes depuis
    // l'ouverture du <tbody>, ce qui evite de toucher a la boucle.
    $__c=0; foreach(array_slice($html,$__rows) as $__f){$__c+=substr_count($__f,"<tr");}
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $__c {items}</span>";
    $html[]="<span class='awc-pg' id='ufce-pager'></span>";
    $html[]="</div></div></div>";

    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$DivsRun).@implode("\n", $html))
        .$script
        .awc_pager_js(50,"ufce-rows","ufce-pager");

}
function external_sources_enable():bool{
    $md5url                     = trim($_GET["external-sources-enable"]);
    $category_id                = intval($_GET["category-id"]);
    $SOURCES                    = base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesExternalSources"));
    $SOURCES_TABLE              = unserialize($SOURCES);
    if($SOURCES_TABLE[$category_id][$md5url]["ENABLED"]==0){
        $SOURCES_TABLE[$category_id][$md5url]["ENABLED"]=1;
        admin_tracks("Enable external source $md5url from category id $category_id");
        $SOURCES_TABLE2=serialize($SOURCES_TABLE);
        $SOURCES=base64_encode($SOURCES_TABLE2);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesExternalSources",$SOURCES);
        return true;
    }
    $SOURCES_TABLE[$category_id][$md5url]["ENABLED"]=0;
    admin_tracks("Disable external source $md5url from category id $category_id");
    $SOURCES_TABLE2=serialize($SOURCES_TABLE);
    $SOURCES=base64_encode($SOURCES_TABLE2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesExternalSources",$SOURCES);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/external/run");
    return true;
}
function external_sources_remove(){
    $md5url                     = trim($_GET["external-sources-remove"]);
    $category_id                = intval($_GET["category-id"]);
    $SOURCES                    = base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesExternalSources"));
    $SOURCES_TABLE              = unserialize($SOURCES);
    unset($SOURCES_TABLE[$category_id][$md5url]);
    admin_tracks("Remove external source $md5url from category id $category_id ");
    $SOURCES_TABLE2=serialize($SOURCES_TABLE);
    $SOURCES=base64_encode($SOURCES_TABLE2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesExternalSources",$SOURCES);
    header("content-type: application/x-javascript");
    echo "$('#$md5url').remove();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/external/run");
    return true;
}

function isUfdb():bool{
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    if($EnableUfdbGuard==1){
        return true;
    }
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if($EnableLocalUfdbCatService==0){
        return false;
    }
    $CategoryServiceWebFilteringEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceWebFilteringEnabled"));

    $UseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteCategoriesService"));

    if($CategoryServiceWebFilteringEnabled==1){
        return true;
    }
    if($UseRemoteCategoriesService==1){
        return true;
    }
    return false;
}
function isRPZ(){
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    if($EnableLocalUfdbCatService==0){
        return false;
    }
    $CategoryServiceRPZEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZEnabled"));
    if($CategoryServiceRPZEnabled==1){
        return true;
    }
    return false;
}

// Hote du tableau des categories (maquette stitch_ufdb_categories).
// Un widget = une fonction : contexte, requete, en-tete, ligne, cellules, vide, bande, TINY, style, JS.
function ufct_context():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $users=new usersMenus();
    $c=array();
    $c["page"]=CurrentPageName();
    $c["function"]=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $c["admin"]=(bool)$users->AsDansGuardianAdministrator;
    $c["manage"]=intval($sock->GET_INFO("ManageOfficialsCategories"));
    $c["hide"]=intval($sock->GET_INFO("HideOfficialsCategory"));
    if($c["manage"]==1){$c["hide"]=0;}
    $c["slave"]=intval($sock->GET_INFO("PowerDNSEnableClusterSlave"));
    $c["local"]=intval($sock->GET_INFO("EnableLocalUfdbCatService"));
    $c["useremote"]=intval($sock->GET_INFO("UseRemoteCategoriesService"));
    $c["remoteenabled"]=intval($sock->GET_INFO("EnableRemoteCategoriesServices"));
    $c["rpzcol"]=isRPZ();
    $c["ufdbcol"]=isUfdb();
    $c["dnscol"]=$c["local"]==1;
    $sources=@unserialize(base64_decode((string)$sock->GET_INFO("CategoriesExternalSources")));
    $c["sources"]=is_array($sources)?$sources:array();
    $current=@unserialize((string)$sock->GET_INFO("UfdbMasterCache"));
    $c["current"]=is_array($current)?$current:array();
    return $c;
}

// La clause WHERE etait concatenee APRES le ORDER BY dans deux variantes sur trois :
// toute recherche finissait en erreur SQL, sauf avec les categories officielles masquees.
function ufct_query(array $c,string $search,string &$error){
    $error="";
    $q=new postgres_sql();
    $where=array();
    if($c["hide"]==1){$where[]="official_category=0 AND free_category=0";}
    if($search<>""){
        if(is_numeric($search)){
            $where[]="category_id=".intval($search);
        }else{
            $s="%".trim(str_replace("*","%",$search),"%")."%";
            $s=pg_escape_string2($q->SearchAntiXSS($s));
            $where[]="categoryname ILIKE '$s'";
        }
    }
    $sql="SELECT * FROM personal_categories";
    if(count($where)>0){$sql.=" WHERE ".implode(" AND ",$where);}
    $sql.=" ORDER BY categoryname";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$error=$q->mysql_error;return false;}
    return $results;
}

function ufct_colcount(array $c):int{
    $n=4;
    if($c["ufdbcol"]){$n++;}
    if($c["dnscol"]){$n++;}
    if($c["rpzcol"]){$n++;}
    return $n;
}

function ufct_thead(array $c):string{
    $h=array();
    $h[]="<thead><tr>";
    $h[]="<th class='ufct-c-cat' scope='col'>{category}</th>";
    $h[]="<th class='ufct-c-num' scope='col'>{items}</th>";
    if($c["ufdbcol"]){$h[]="<th class='ufct-c-st' scope='col'>Proxy</th>";}
    if($c["dnscol"]){$h[]="<th class='ufct-c-st' scope='col'>DNS</th>";}
    if($c["rpzcol"]){$h[]="<th class='ufct-c-rpz' scope='col'>RPZ</th>";}
    $h[]="<th class='ufct-c-created' scope='col'>{created}</th>";
    $h[]="<th class='ufct-c-act' scope='col'>{ufct_col_actions}</th>";
    $h[]="</tr></thead>";
    return implode("",$h);
}

function ufct_pill(string $state,string $label):string{
    $ico=array("ok"=>"fa-solid fa-circle-check","warn"=>"fa-solid fa-triangle-exclamation","off"=>"fa-solid fa-circle-minus");
    if(!isset($ico[$state])){$state="off";}
    return "<span class='ufct-pill ufct-pill-$state'><i class='{$ico[$state]}' aria-hidden='true'></i>$label</span>";
}

// Etat inconnu tant que le pinger n'est pas passe : une barre neutre, pas un spinner.
function ufct_skeleton():string{
    return "<span class='ufct-skel' aria-hidden='true'></span>";
}

function ufct_icon(array $l,bool $remote,bool $meta):string{
    if($meta){return "<span class='ufct-ico ufct-ico-meta' aria-hidden='true'><i class='fa-solid fa-download'></i></span>";}
    if($remote){return "<span class='ufct-ico ufct-ico-remote' aria-hidden='true'><i class='fa-solid fa-cloud'></i></span>";}
    $src=trim((string)($l["category_icon"] ?? ""));
    if($src==""){return "<span class='ufct-ico' aria-hidden='true'><i class='fa-solid fa-folder'></i></span>";}
    return "<span class='ufct-ico' aria-hidden='true'><img src='".htmlspecialchars($src,ENT_QUOTES)."' alt=''></span>";
}

function ufct_chip(bool $official,bool $free,bool $remote,bool $meta):string{
    $def=array();
    if($meta){$def=array("meta","fa-solid fa-download","{ufct_type_meta}");}
    elseif($remote){$def=array("remote","fa-solid fa-cloud","{ufct_type_remote}");}
    elseif($official){$def=array("official","fa-solid fa-shield-halved","{ufct_type_official}");}
    elseif($free){$def=array("free","fa-solid fa-gift","{ufct_type_free}");}
    if(count($def)==0){return "";}
    return "<span class='ufct-chip ufct-chip-{$def[0]}'><i class='{$def[1]}' aria-hidden='true'></i>{$def[2]}</span>";
}

function ufct_remote_host(int $serviceid):string{
    static $cache=array();
    if($serviceid==0){return "";}
    if(isset($cache[$serviceid])){return $cache[$serviceid];}
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $s=$q->mysqli_fetch_array("SELECT hostname,port FROM categories_services WHERE ID=$serviceid");
    $host="";
    if(is_array($s) && trim((string)($s["hostname"] ?? ""))<>""){
        $host=trim($s["hostname"]).":".intval($s["port"] ?? 0);
    }
    $cache[$serviceid]=$host;
    return $host;
}

function ufct_cell_category(array $l,array $c,array $t):string{
    $id=intval($l["category_id"]);
    $name=htmlspecialchars((string)$l["categoryname"],ENT_QUOTES,"UTF-8");
    // Les descriptions officielles sont des {tokens} : les traduire AVANT d'echapper, sinon
    // une apostrophe de la traduction casserait l'attribut title.
    $tpl=new template_admin();
    $desc=htmlspecialchars(strip_tags($tpl->_ENGINE_parse_body((string)$l["category_description"])),ENT_QUOTES,"UTF-8",false);
    $descLine=$desc;
    if($t["remote"]){
        $host=ufct_remote_host(intval($l["serviceid"] ?? 0));
        if($host<>""){
            $descLine="<span class='ufct-mono'>".htmlspecialchars($host,ENT_QUOTES)."</span> &middot; $desc";
        }
    }
    $src="";
    if(isset($c["sources"][$id]) && is_array($c["sources"][$id]) && count($c["sources"][$id])>0){
        $src="<span class='ufct-src' title=\"{ufct_external_source}\" aria-label=\"{ufct_external_source}\"><i class='fa-solid fa-file-import' aria-hidden='true'></i></span>";
    }
    $chip=ufct_chip($t["official"],$t["free"],$t["remote"],$t["meta"]);
    $h=array();
    $h[]="<td class='ufct-c-cat'><div class='ufct-cat'>".ufct_icon($l,$t["remote"],$t["meta"]);
    $h[]="<div class='ufct-cat-txt'><div class='ufct-cat-l1'>$chip$src";
    $h[]="<a href='javascript:void(0)' class='ufct-name' id='category_name_$id' title=\"{click_to_edit}\" onclick=\"{$t["edit"]}\">$name</a></div>";
    $h[]="<div class='ufct-desc' id='category_text_$id' title='$desc'>$descLine</div></div></div></td>";
    return implode("",$h);
}

function ufct_cell_proxy(int $id):string{
    return "<td class='ufct-c-st'><span id='dnscatz-ufdb-$id'>".ufct_skeleton()."</span><div class='ufct-sub' id='dnscatz-updated-$id'></div></td>";
}

function ufct_cell_dns(int $id):string{
    return "<td class='ufct-c-st'><span id='dnscatz-status-$id'>".ufct_skeleton()."</span><div class='ufct-sub' id='dnscatz-dnssub-$id'></div></td>";
}

// L'ancienne colonne affichait « Not compiled » pour toute categorie RPZ : aucun etat
// de compilation RPZ n'est lu. On montre ce qui est reel, le drapeau rpz de la categorie.
function ufct_cell_rpz(array $l):string{
    if(intval($l["rpz"] ?? 0)==1){return "<td class='ufct-c-rpz'>".ufct_pill("ok","{enabled}")."</td>";}
    return "<td class='ufct-c-rpz'>".ufct_pill("off","{inactive2}")."</td>";
}

function ufct_cell_actions(int $id,string $name,array $f):string{
    $label=htmlspecialchars($name,ENT_QUOTES,"UTF-8");
    $out=array();
    if($f["add"]<>""){
        $out[]="<button type='button' class='ufct-add' title=\"{ufct_add_websites}\" onclick=\"{$f["add"]}\"><i class='fa-solid fa-plus' aria-hidden='true'></i>{add}</button>";
    }
    if($f["compile"]<>""){
        $out[]="<button type='button' class='ufct-ib' title=\"{ufct_compile_category}\" aria-label=\"{ufct_compile_category} $label\" onclick=\"{$f["compile"]}\"><i class='fa-solid fa-download' aria-hidden='true'></i></button>";
    }
    $items=array();
    if($f["import"]<>""){
        $items[]="<button type='button' role='menuitem' onclick=\"UfctMenuClose();{$f["import"]}\"><i class='fa-solid fa-file-import' aria-hidden='true'></i>{ufct_import}</button>";
    }
    if($f["export"]<>""){
        $items[]="<button type='button' role='menuitem' onclick=\"UfctMenuClose();{$f["export"]}\"><i class='fa-solid fa-file-export' aria-hidden='true'></i>{export}</button>";
    }
    if($f["delete"]<>""){
        if(count($items)>0){$items[]="<hr>";}
        $items[]="<button type='button' role='menuitem' class='ufct-danger' onclick=\"UfctMenuClose();{$f["delete"]}\"><i class='fa-solid fa-trash' aria-hidden='true'></i>{ufct_delete}</button>";
    }
    if(count($items)==0){
        $out[]="<button type='button' class='ufct-ib ufct-kebab' disabled title=\"{ufct_readonly}\" aria-label=\"{ufct_readonly}\"><i class='fa-solid fa-ellipsis-vertical' aria-hidden='true'></i></button>";
    }else{
        $out[]="<button type='button' class='ufct-ib ufct-kebab' aria-haspopup='menu' aria-expanded='false' aria-label=\"{ufct_more_actions} $label\" onclick=\"UfctMenu(this,'ufct-m-$id')\"><i class='fa-solid fa-ellipsis-vertical' aria-hidden='true'></i></button>";
        $out[]="<div class='ufct-menu' id='ufct-m-$id' role='menu' hidden>".implode("",$items)."</div>";
    }
    return "<td class='ufct-c-act'><div class='ufct-act'>".implode("",$out)."</div></td>";
}

// Ce que chaque ligne autorise : reprend a l'identique les regles de l'ancien tableau.
function ufct_row_actions(array $l,array $c,array $t):array{
    $id=intval($l["category_id"]);
    $page=$c["page"];
    $fn=$c["function"];
    $tpl=new template_admin();
    $add=$c["admin"];$import=$c["admin"];$compile=$c["admin"];$export=$c["admin"];$delete=$c["admin"];
    if($t["official"]){
        $delete=false;
        if($c["manage"]==0){$add=false;$import=false;$compile=false;}
    }
    if($t["free"]){$delete=false;$add=false;$import=false;$compile=false;}
    if($c["slave"]==1){$add=false;$import=false;}
    if($t["remote"]){$add=false;$import=false;$compile=false;$export=false;$delete=false;}
    if($t["meta"]){$add=false;$import=false;$compile=false;$export=false;}
    if($c["useremote"]==1){$add=false;$import=false;$compile=false;$export=false;$delete=false;}

    $f=array("add"=>"","import"=>"","compile"=>"","export"=>"","delete"=>"");
    if($add){
        $cname=urlencode((string)$l["categoryname"]);
        $f["add"]="Loadjs('fw.ufdb.categorize.php?category_requested=$id&cname=$cname&function=$fn')";
    }
    if($import){$f["import"]="Loadjs('fw.ufdb.categorize.php?js-import=$id&function=$fn')";}
    if($export){$f["export"]="Loadjs('$page?js-export=$id&function=$fn')";}
    if($delete){$f["delete"]="Loadjs('$page?category-delete=$id&function=$fn')";}
    if($compile){
        if($c["local"]==1){
            $f["compile"]="Loadjs('$page?dnscatz-compile-single=$id')";
        }else{
            $after="";
            if($fn<>""){$after="$fn()";}
            $f["compile"]=$tpl->framework_buildjs("/category/compile/$id",
                "ufdbcat.compile.progress","ufdbcat.compile.txt","progress-ppcategories-restart",$after);
        }
    }
    return $f;
}

function ufct_row(array $l,array $c):string{
    $id=intval($l["category_id"]);
    $page=$c["page"];
    $fn=$c["function"];
    $t=array();
    $t["official"]=intval($l["official_category"] ?? 0)==1;
    $t["free"]=intval($l["free_category"] ?? 0)==1;
    // Une ligne « distante » sans service d'origine est locale (meme regle que la normalisation Go).
    $t["remote"]=intval($l["remotecatz"] ?? 0)==1 && intval($l["serviceid"] ?? 0)>0;
    $t["meta"]=intval($l["meta"] ?? 0)==1;
    $t["edit"]="Loadjs('$page?category-js=$id&function=$fn')";
    if($t["remote"]){$t["edit"]="Loadjs('$page?category-dns-js=$id&function=$fn')";}
    if($t["meta"]){$t["edit"]="Loadjs('$page?category-http-js=$id&function=$fn')";}

    $items=intval($l["items"] ?? 0);
    if($t["free"] && isset($c["current"][$id]["items"])){$items=intval($c["current"][$id]["items"]);}
    $created=intval($l["created"] ?? 0);
    $createdText="<span class='ufct-muted'>&mdash;</span>";
    if($created>0){$createdText=date("Y-m-d",$created);}

    $h=array();
    $h[]="<tr>";
    $h[]=ufct_cell_category($l,$c,$t);
    $h[]="<td class='ufct-c-num'><span id='category_items_$id'>".str_replace(" ","&thinsp;",numberFormat($items,0,""," "))."</span></td>";
    if($c["ufdbcol"]){$h[]=ufct_cell_proxy($id);}
    if($c["dnscol"]){$h[]=ufct_cell_dns($id);}
    if($c["rpzcol"]){$h[]=ufct_cell_rpz($l);}
    $h[]="<td class='ufct-c-created'>$createdText</td>";
    $h[]=ufct_cell_actions($id,(string)$l["categoryname"],ufct_row_actions($l,$c,$t));
    $h[]="</tr>";
    return implode("",$h);
}

function ufct_empty(array $c,string $search):string{
    $cols=ufct_colcount($c);
    if($search<>""){
        $s=htmlspecialchars($search,ENT_QUOTES,"UTF-8");
        return "<tr class='ufct-empty-row'><td colspan='$cols'><div class='ufct-empty'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><div>{ufct_empty_search} <strong>$s</strong></div></div></td></tr>";
    }
    $btn="";
    if($c["admin"] && $c["slave"]==0){
        $btn="<button type='button' class='ufct-primary' onclick=\"Loadjs('{$c["page"]}?category-js=&function={$c["function"]}',true);\"><i class='fa-solid fa-plus' aria-hidden='true'></i>{new_category}</button>";
    }
    return "<tr class='ufct-empty-row'><td colspan='$cols'><div class='ufct-empty'><i class='fa-solid fa-books' aria-hidden='true'></i><div>{ufct_empty_title}</div>$btn</div></td></tr>";
}

// Bande de compilation (dnscatz /compile-progress) : vide tant qu'aucune compilation n'a tourne.
function ufct_strip(array $json):string{
    $tpl=new template_admin();
    $progress=intval($json["progress"] ?? 0);
    if($progress<=0){return "";}
    $when="";
    if(!empty($json["completed_at"])){$when=$tpl->time_to_date($tpl->GoToTimestamp($json["completed_at"]),true);}
    $err=trim((string)($json["error"] ?? ""));
    if(strlen($err)>3){
        $sub=htmlspecialchars($err,ENT_QUOTES,"UTF-8");
        if($when<>""){$sub="$when &middot; $sub";}
        return "<div class='ufct-strip ufct-strip-err'><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i><b>{ufct_error}</b><span class='ufct-strip-sub'>$sub</span></div>";
    }
    if($progress>=100){
        $sec=round(intval($json["duration_ms"] ?? 0)/1000,1);
        return "<div class='ufct-strip ufct-strip-ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i><b>{compilation_done}</b><span class='ufct-strip-sub'>&middot; {completed_at}: $when ({$sec}s)</span></div>";
    }
    return "<div class='ufct-strip ufct-strip-run'><i class='fa-solid fa-rotate fa-spin' aria-hidden='true'></i><b>{compiling}&hellip; $progress%</b><span class='ufct-bar' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='$progress'><span style='width:$progress%'></span></span></div>";
}

// Erreur du serveur de categories distant (RemoteCategoriesServiceError).
function ufct_strip_remote(string $err):string{
    $sub=htmlspecialchars($err,ENT_QUOTES,"UTF-8");
    return "<div class='ufct-strip ufct-strip-err'><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i><b>{category_server}: {ufct_error}</b><span class='ufct-strip-sub'>$sub</span></div>";
}

function ufct_tiny(array $c):string{
    $tpl=new template_admin();
    $page=$c["page"];
    $function=$c["function"];
    $topbuttons=array();
    $LabelRCat="";
    if($c["useremote"]==1){$LabelRCat=" (On)";}
    $jsCompile=$tpl->framework_buildjs("/category/all/compile",
        "ufdbcat.compile.all.progress","ufdbcat.compile.all.log","progress-ppcategories-restart");
    if($c["local"]==1){$jsCompile="Loadjs('$page?compile-dns-api=yes&function=$function')";}
    if($c["admin"]){
        if($c["slave"]==0){
            $topbuttons[]=array("Loadjs('$page?category-js=&function=$function',true);","fad fa-books-medical","{new_category}");
        }
        $topbuttons[]=array($jsCompile,ico_save,"{compile_all}");
        $topbuttons[]=array("Loadjs('$page?remote-categories-service=yes&function=$function')",ico_clouds,"{category_server}$LabelRCat");
        if($c["useremote"]==0){
            if($c["remoteenabled"]==1){
                $topbuttons[]=array("Loadjs('$page?synchronize-dns-api=yes&function=$function')",ico_refresh,"{fetch_categories}");
            }
        }else{
            $topbuttons[]=array("Loadjs('$page?synchronize-remote-categories=yes&function=$function')",ico_refresh,"{ufct_synchronize}");
        }
        $topbuttons[]=array("Loadjs('$page?all-categories-js=yes')",ico_list,"{all_categories}");
        // Page PLEINE, jamais en dialogue : son TINY reecrirait l'en-tete de cette page.
        $topbuttons[]=array("document.location.href='/categories-console';",ico_microsoft,"{categories_windows_console}");
        if($c["useremote"]==0){
            $topbuttons[]=array("Loadjs('$page?remove-all=yes&function=$function')",ico_trash,"{remove_all_categories}");
        }
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{your_categories}";
    $TINY_ARRAY["ICO"]="fad fa-books";
    $TINY_ARRAY["EXPL"]="{personal_categories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->th_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

function ufct_colors():array{
    $c=array("accent"=>"#10b981","dark"=>"#0d7a6e","soft"=>"rgba(16,185,129,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$c["accent"]=$a["accent"];}
        if(!empty($a["accent_dark"])){$c["dark"]=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$c["soft"]=$a["accent_soft"];}
    }
    return $c;
}

// CSS de la page : concatenee HORS de _ENGINE_parse_body().
function ufct_style():string{
    $c=ufct_colors();
    $css=<<<'CSS'
<style>
.ufct-strip{box-sizing:border-box;display:flex;align-items:center;gap:10px;min-height:48px;padding:10px 16px;border-radius:8px;border:1px solid;font-size:13px;margin:0 0 12px}
.ufct-strip b{font-weight:600}
.ufct-strip-ok{background:#e7f6f0;border-color:#bfe6d4;color:#127a52}
.ufct-strip-run{background:#eef4fb;border-color:#cddff3;color:#1f4f82}
.ufct-strip-err{background:#fdecea;border-color:#f5c6c0;color:#b3261e}
.ufct-strip-sub{color:#4b5563}
.ufct-strip-err .ufct-strip-sub{color:#7a2e26}
.ufct-bar{display:block;width:200px;height:6px;margin-left:auto;background:#fff;border:1px solid #cddff3;border-radius:999px;overflow:hidden}
.ufct-bar>span{display:block;height:100%;background:__ACC__;transition:width .15s}
table.ufct-tbl{width:100%}
table.ufct-tbl>thead>tr>th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#5f6b7a;font-weight:600;white-space:nowrap;padding:10px 12px}
table.ufct-tbl>tbody>tr>td{vertical-align:middle;padding:8px 12px;height:56px;font-size:14px;color:#1f2933}
table.ufct-tbl>tbody>tr:hover>td{background:#f7f9fb}
table.ufct-tbl .ufct-c-cat{width:34%;max-width:0}
table.ufct-tbl .ufct-c-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;width:1%}
table.ufct-tbl .ufct-c-st{white-space:nowrap;width:1%}
table.ufct-tbl .ufct-c-rpz{white-space:nowrap;width:1%}
table.ufct-tbl .ufct-c-created{white-space:nowrap;width:1%}
table.ufct-tbl>tbody>tr>td.ufct-c-created{color:#5f6b7a;font-size:13px;font-variant-numeric:tabular-nums}
table.ufct-tbl .ufct-c-act{text-align:right;white-space:nowrap;width:1%}
.ufct-cat{display:flex;align-items:center;gap:12px;min-width:0}
.ufct-ico{flex:0 0 32px;width:32px;height:32px;border-radius:8px;border:1px solid #e3e8ee;background:#f5f7f9;display:inline-flex;align-items:center;justify-content:center;color:#5f6b7a;font-size:14px;overflow:hidden}
.ufct-ico img{max-width:20px;max-height:20px}
.ufct-ico-remote{background:#eaf2fd;border-color:#cddff3;color:#1d5fb4}
.ufct-ico-meta{background:__SOFT__;border-color:transparent;color:__DARK__}
.ufct-cat-txt{min-width:0;flex:1 1 auto}
.ufct-cat-l1{display:flex;align-items:center;gap:6px;min-width:0}
.ufct-name{font-weight:600;color:#1f2933;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ufct-name:hover,.ufct-name:focus{color:__DARK__;text-decoration:underline}
.ufct-desc{font-size:13px;color:#5f6b7a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
.ufct-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12.5px;color:#374151}
.ufct-muted{color:#9aa4af}
.ufct-src{color:#5f6b7a;font-size:12px;display:inline-flex}
.ufct-chip{display:inline-flex;align-items:center;gap:4px;flex:0 0 auto;padding:1px 6px;border:1px solid #d5dbe2;border-radius:4px;font-size:12px;line-height:16px;color:#374151;background:#fff}
.ufct-chip-remote{border-color:#cddff3;color:#1d5fb4;background:#f3f8fe}
.ufct-chip-meta{border-color:transparent;color:__DARK__;background:__SOFT__}
.ufct-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;line-height:18px;white-space:nowrap}
.ufct-pill-ok{background:#e7f6f0;color:#127a52}
.ufct-pill-warn{background:#fff4dc;color:#8a5a00}
.ufct-pill-off{background:#f1f3f5;color:#4b5563}
.ufct-sub{font-size:12px;color:#5f6b7a;margin-top:3px;white-space:nowrap}
.ufct-sub:empty{display:none}
.ufct-skel{display:inline-block;width:76px;height:20px;border-radius:999px;background:linear-gradient(90deg,#eef1f4 0%,#f7f9fb 50%,#eef1f4 100%);background-size:200% 100%;animation:ufctsk 1.2s ease-in-out infinite;vertical-align:middle}
@keyframes ufctsk{0%{background-position:100% 0}100%{background-position:-100% 0}}
.ufct-act{display:inline-flex;align-items:center;justify-content:flex-end;gap:4px}
.ufct-add{display:inline-flex;align-items:center;gap:5px;border:0;background:transparent;color:__DARK__;font-weight:600;font-size:13px;padding:5px 9px;border-radius:6px;cursor:pointer}
.ufct-add:hover{background:__SOFT__}
.ufct-ib{width:30px;height:30px;border:0;background:transparent;border-radius:6px;color:#5f6b7a;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:13px}
.ufct-ib:hover{background:#eef1f4;color:#1f2933}
.ufct-ib[disabled]{opacity:.35;cursor:not-allowed;background:transparent}
.ufct-add:focus-visible,.ufct-ib:focus-visible,.ufct-name:focus-visible,.ufct-menu button:focus-visible,.ufct-primary:focus-visible{outline:2px solid __ACC__;outline-offset:2px}
.ufct-menu{position:fixed;z-index:3000;min-width:170px;background:#fff;border:1px solid #e3e8ee;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.14);padding:4px 0;text-align:left}
.ufct-menu[hidden]{display:none}
.ufct-menu button{display:flex;width:100%;align-items:center;gap:9px;border:0;background:transparent;padding:7px 14px;font-size:13px;color:#1f2933;cursor:pointer;text-align:left}
.ufct-menu button:hover,.ufct-menu button:focus{background:#f3f5f7}
.ufct-menu .ufct-danger{color:#b3261e;font-weight:600}
.ufct-menu hr{margin:4px 0;border:0;border-top:1px solid #e3e8ee}
.ufct-empty{display:flex;flex-direction:column;align-items:center;gap:10px;padding:40px 16px;color:#5f6b7a;font-size:14px}
.ufct-empty>i,.ufct-empty>svg{font-size:36px;color:#9aa4af}
.ufct-primary{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:6px;background:__ACC__;color:#fff;font-weight:600;font-size:13px;padding:7px 14px;cursor:pointer}
@media (max-width:1200px){.ufct-c-created{display:none}}
@media (max-width:1060px){.ufct-c-rpz{display:none}}
@media (prefers-reduced-motion:reduce){.ufct-skel{animation:none}.ufct-bar>span{transition:none}}
</style>
CSS;
    return str_replace(array("__ACC__","__DARK__","__SOFT__"),array($c["accent"],$c["dark"],$c["soft"]),$css);
}

// Menu d'actions par ligne : position fixe pour sortir du conteneur defilant du tableau.
function ufct_js():string{
    return <<<'JS'
<script>
(function(){
 if(window.UfctMenu){return;}
 window.UfctMenuClose=function(){
  jQuery('.ufct-menu').attr('hidden','hidden');
  jQuery('.ufct-kebab[aria-expanded=true]').attr('aria-expanded','false');
 };
 window.UfctMenu=function(btn,id){
  var m=document.getElementById(id); if(!m){return;}
  var wasOpen=!m.hasAttribute('hidden');
  UfctMenuClose(); if(wasOpen){return;}
  m.removeAttribute('hidden');
  var r=btn.getBoundingClientRect(), w=m.offsetWidth, h=m.offsetHeight;
  var top=r.bottom+4; if(top+h>window.innerHeight-8){top=Math.max(8,r.top-h-4);}
  var left=r.right-w; if(left<8){left=8;}
  m.style.top=top+'px'; m.style.left=left+'px';
  btn.setAttribute('aria-expanded','true');
  var f=m.querySelector('button'); if(f){f.focus();}
 };
 document.addEventListener('click',function(e){
  if(!jQuery(e.target).closest('.ufct-menu,.ufct-kebab').length){UfctMenuClose();}
 },true);
 window.addEventListener('scroll',function(e){
  if(jQuery(e.target).closest && jQuery(e.target).closest('.ufct-menu').length){return;}
  UfctMenuClose();
 },true);
 document.addEventListener('keydown',function(e){
  var m=jQuery('.ufct-menu:not([hidden])')[0]; if(!m){return;}
  if(e.key==='Escape'){
   var b=jQuery('.ufct-kebab[aria-expanded=true]')[0]; UfctMenuClose(); if(b){b.focus();} e.preventDefault(); return;
  }
  if(e.key==='ArrowDown'||e.key==='ArrowUp'){
   var it=jQuery(m).find('button').toArray(); if(!it.length){return;}
   var i=it.indexOf(document.activeElement);
   i=(e.key==='ArrowDown')?(i+1)%it.length:(i<=0?it.length-1:i-1);
   it[i].focus(); e.preventDefault();
  }
 });
})();
</script>
JS;
}

function table(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $c=ufct_context();
    $page=$c["page"];
    $search=trim((string)($_GET["search"] ?? ""));

    // Plus d'ecriture a l'affichage : remotecatz est normalise par articarest
    // (categories.PatchTable au demarrage, et a l'ecriture de la synchro distante).
    $pp=new categories();
    $pp->initialize();
    if(!isset($_GET["gpid"])){$_GET["gpid"]=0;}
    $GLOBALS["jsAfterEnc"]=base64_encode("LoadAjax('table-loader','$page?table=yes&gpid={$_GET["gpid"]}');");

    $error="";
    $results=ufct_query($c,$search,$error);
    if($error<>""){
        echo "<div class='alert alert-danger'>".htmlspecialchars($error,ENT_QUOTES,"UTF-8")."</div>";
        return;
    }

    $rows=array();
    while($l=pg_fetch_assoc($results)){
        $id=intval($l["category_id"]);
        if($c["manage"]==0 && $id>5000){continue;}
        if(!$c["admin"] && !isset($_SESSION["MANAGE_CATEGORIES"][$id])){continue;}
        $rows[]=ufct_row($l,$c);
    }

    $html=array();
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table id='table-persocats-list' class='awc-tbl ufct-tbl'>";
    $html[]=ufct_thead($c);
    $html[]="<tbody id='ufct-rows'>";
    if(count($rows)==0){
        $html[]=ufct_empty($c,$search);
    }else{
        $html[]=implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span><span class='awc-pg' id='ufct-pager'></span></div>";
    $html[]="</div></div>";

    $jstiny=ufct_tiny($c);
    $Pinger=$tpl->RefreshInterval_Loadjs("table-persocats-list",$page,"pinger=yes");
    // $Pinger porte des accolades : le bloc est concatene APRES l'analyse.
    $script="<script>\n$Pinger\nNoSpinner();\n$jstiny\n</script>";

    echo ufct_style().awc_table_style()
        .$tpl->_ENGINE_parse_body(implode("\n",$html))
        .$script
        .awc_pager_js(50,"ufct-rows","ufct-pager")
        .ufct_js();
}
