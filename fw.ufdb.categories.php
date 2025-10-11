<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["remote-categories-service-tabs"])){remote_categories_service_tabs();exit;}
if(isset($_GET["remote-categories-service"])){remote_categories_service_js();exit;}
if(isset($_GET["remote-categories-service-popup"])){remote_categories_service_popup();exit;}
if(isset($_GET["remote-categories-service-queries"])){remote_categories_service_queries();exit;}
if(isset($_POST["UseRemoteCategoriesService"])){remote_categories_service_save();exit;}
if(isset($_POST["EnableRemoteCategoriesServices"])){remote_categories_service_save();exit;}
if(isset($_GET["synchronize-remote-categories"])){remote_categories_service_fetch();exit;}

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
function compile_dns_api():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $function=$_GET["function"];
    $data=$sock->REST_API("/categories/dns/compile");
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

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{your_categories}",
        "fad fa-books","{personal_categories_explain}","$page?tabs=yes",
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
    echo "<div style='margin-top:10px'>&nbsp;</div>";
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
    $html[]="<table style='margin-top:10px' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<th data-sortable=true class='text-capitalize'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{categories}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error<br>$sql</div>";
        return false;
    }

    $TRCLASS=null;


    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $category_id = $ligne["category_id"];
        $categoryname = $ligne["categoryname"];
        $text_category = $tpl->_ENGINE_parse_body($ligne["category_description"]);
        $category_icon = $ligne["category_icon"];
        $html[]="<td style='width:1%;vertical-align: top' nowrap>$category_id</td>";
        $html[]="<td style='width:99%'><strong style='font-size:18px'>$categoryname</strong><br><i>$text_category</i></td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function all_categories_officials():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $sql="SELECT * FROM personal_categories WHERE official_category=1 AND free_category=0 order by categoryname";
    $html[]="<table style='margin-top:10px' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<th data-sortable=true class='text-capitalize'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize'>{categories}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error<br>$sql</div>";
        return false;
    }

    $TRCLASS=null;


    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
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

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $function=$_GET["function"];
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
function js_export_perform(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $categoryid=$_GET["js-export-perform"];
    $catname=$catz->CategoryIntToStr($categoryid);


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$categoryid.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/categorize.$categoryid.logs.txt";
    $ARRAY["CMD"]="categories.php?categorize-export=$categoryid";
    $ARRAY["TITLE"]="{export} ".$catz->CategoryIntToStr($categoryid);
    $ARRAY["AFTER"]="LoadAjax('download-js-export-$categoryid','$page?js-export-download=$categoryid');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=progress-js-export-$categoryid')";


    $html[]="<H2>{export}: $catname</H2>";
    $html[]="<div id='progress-js-export-$categoryid'></div>";
    $html[]="<div id='download-js-export-$categoryid'></div>";
    $html[]="<script>";
    $html[]=$jsafter;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function js_export_download(){
    $tpl=new template_admin();
    $category_id=$_GET["js-export-download"];
    $catz=new mysql_catz();
    $catname=$catz->CategoryIntToStr($category_id);
    $file_path="/usr/share/artica-postfix/ressources/logs/$category_id.gz";

    if(!is_file($file_path)){
        $html[]="
                <div class=\"widget style1 red-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-exclamation fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {failed} </span>
                            <h2 class=\"font-bold\">$catname Error!</h2>
                        </div>
                    </div>
                </div>
            ";

        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    //<i class="fas fa-exclamation"></i>
    // <i class="fas fa-file-archive"></i>
    $filesize=FormatBytes(@filesize($file_path)/1024);

    $html[]="<a href='ressources/logs/$category_id.gz'>
                <div class=\"widget style1 navy-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-file-archive fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> $catname </span>
                            <h2 class=\"font-bold\">$category_id.gz <small style='color:white'>($filesize)</small></h2>
                        </div>
                    </div>
                </div></a>
            ";

    echo $tpl->_ENGINE_parse_body($html);
    return false;

}
function jrows_data(){
    $data= unserialize(base64_decode($_GET["jrows-data"]));
    echo "<textarea style='width:100%;min-height:512px'>".@implode("\n",$data)."</textarea>";
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
    $table=$ligne["categorytable"];


    $q->QUERY_SQL("DELETE FROM $table WHERE sitename='$sitename'");
    admin_tracks("Remove $sitename FROM $table category $category_id");
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
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
    $ARRAY["CMD"]="ufdbguard.php?remove-all-categories=yes";
    $ARRAY["TITLE"]="{remove_all_categories}";
    $ARRAY["AFTER"]="$function";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-ppcategories-restart')";
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
    $categorykey=$_GET["category-delete"];
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

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/$category/remove"));
    if(!$json->Status){
        echo "($categoryname) ".$json->Error;
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
    $category_id=$_POST["category-orders"];
    $q=new postgres_sql();

    $q->QUERY_SQL("UPDATE personal_categories SET 
    blacklist='{$_POST["blacklist"]}',
    whitelist='{$_POST["whitelist"]}',
    nocache='{$_POST["nocache"]}',
    parent='{$_POST["parent"]}' WHERE category_id=$category_id");

    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return;}
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
        "$page?remote-categories-service-tabs=yes$function",550);
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


    $form[]=$tpl->field_checkbox("UseRemoteCategoriesService","{enabled}",$UseRemoteCategoriesService);
    $form[]=$tpl->field_text("RemoteCategoriesServiceAddr", "{remote_server_address}", $RemoteCategoriesServiceAddr);

    if($RemoteCategoriesServicePort==0){
        $RemoteCategoriesServicePort=9905;
    }
    $form[] = $tpl->field_numeric("RemoteCategoriesServicePort","{remote_server_port} (HTTP)",$RemoteCategoriesServicePort);
    $form[] = $tpl->field_checkbox("RemoteCategoriesServiceSSL","{use_ssl}",$RemoteCategoriesServiceSSL);
    echo $tpl->form_outside("", $form,"{CategoryDatabaseReplication_explain}","{apply}","$function","AsDansGuardianAdministrator");
    return true;
}
function remote_categories_service_save():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI   = true;
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/remote/service/check");
    return admin_tracks_post("Saving the use a remote categories service");

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

    $sql="UPDATE personal_categories SET categoryname='{$_POST["categorykey"]}', category_description='{$_POST["category_description"]}', PublicMode='{$_POST["PublicMode"]}' WHERE category_id='{$_POST["category_id"]}'";
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
    $table=$ligne["categorytable"];
    $sql=null;

    if(preg_match("#(limit)(\s+|=|)([0-9]+)#i", $search,$re)){
        $search=trim(str_replace("$re[1]$re[2]$re[3]", "", $search));

    }


    if(preg_match("#regex:(.+)#", $search,$re)){
        $search=trim($re[1]);
        $sql="DELETE from $table WHERE sitename ~* '$search'";
    }

    if($sql==null){
        if($search==null){$search="*";}
        if(strpos(" $search ", "*")>0){
            $search=str_replace("*", "%", $search);
            $sql="DELETE from $table WHERE sitename LIKE '$search'";
        }
    }

    if($sql==null){
        echo "Bad search pattern";
        return false;
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
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

    $topbuttons=array();


    $jsrestart=$tpl->framework_buildjs("/category/compile/$categorykey",
    "ufdbcat.compile.progress","ufdbcat.compile.txt",
        "compile-category-single","document.getElementById('compile-category-single').innerHTML=''");


    $jsDelete="Loadjs('$page?sql-delete=yes&id=$categorykey&function=$function');";
    $PowerDNSEnableClusterSlave = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    //$html[]=$tpl->_ENGINE_parse_body("<div class=\"btn-group\" data-toggle=\"buttons\">$DropDownButton");
    if($PowerDNSEnableClusterSlave==0) {
        if($categorykey>0) {
            $add="Loadjs('$page?category-items-addjs=$categorykey&function=$function')";
            $topbuttons[] = array("$add", ico_plus, "{add_websites}");
        }
    }

    if($categorykey>0) {
        $topbuttons[] = array("$jsrestart", "fa fa-download", "{compile_category}");
    }


    if($PowerDNSEnableClusterSlave==0) {
        if($categorykey>0) {
            $topbuttons[] = array("$jsDelete", ico_trash, "{remove_all_items}");
        }
    }

    
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function category_security(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["category-security"]);
    $q=new postgres_sql();
    $t=time();
    $results=$q->QUERY_SQL("SELECT * FROM personal_categories_privs WHERE category_id='$category_id'");


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize'>{privileges}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $dngroup=$ligne["dngroup"];
        $id=$ligne["id"];



        $delete=$tpl->icon_delete("Loadjs('$page?delete-priv=$id&md=$md')");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><strong>$dngroup</strong></td>";
        $html[]="<td class='center' style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
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
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $category_id=$ligne["category_id"];
            $categoryname=$ligne["categoryname"];
            if(preg_match("#reserved#", $categoryname)){continue;}
            $array[$categoryname]="LoadAjaxSilent('category-div-items-main','$page?category-step1=$category_id&EnableMenu=$EnableMenu&function={$_GET["function"]}');";
        }
    }


    $t=time();
    $html[]="<div id='compile-category-single' style='margin-top:10px'></div>";
    $html[]="<div id='compile-category-buttons' style='margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&category-items-table=yes&t=$t&categorykey=$categorykey");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function category_items_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category_id=intval($_GET["categorykey"]);
    if($category_id==0){return;}
    $q=new postgres_sql();
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT categorytable FROM personal_categories WHERE category_id='$category_id'"));
    $table=$ligne["categorytable"];
    $search=trim($_GET["search"]);
    $search=$q->SearchAntiXSS($search);
    $jsbuttons="LoadAjaxSilent('compile-category-buttons','$page?compile-category-buttons=yes&function={$_GET["function"]}&categorykey=$category_id');";
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
        $LIMIT=$re[3];
    }
    $html[]="<table id='table-persocats-items' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    if(!$q->TABLE_EXISTS($table)){$q->CREATE_CATEGORY_TABLE($table);}


    $sql="SELECT sitename from $table$ORDER LIMIT $LIMIT";

    if($search<>null){
        $sql=null;
        if(preg_match("#regex:(.+)#i", $search,$re)){
            $search=trim($re[1]);
            $sql="SELECT sitename from $table WHERE sitename ~* '$search'$ORDER FETCH FIRST $LIMIT ROWS ONLY;";
        }

        if($sql==null){
            if(strpos(" $search ", "*")>0){
                $search=trim(str_replace("*", "%", $search));
                $sql="SELECT sitename from $table WHERE sitename LIKE '$search'$ORDER FETCH FIRST $LIMIT ROWS ONLY;";
            }
        }

        if($sql==null){
            $search=trim($search);
            $sql="SELECT sitename from $table WHERE sitename='$search'$ORDER FETCH FIRST $LIMIT ROWS ONLY;";

        }
    }

    $results=$q->QUERY_SQL($sql);
    $zrows=array();
    $ROWS=array();

    if(!$q->ok){
        echo "<div class='alert alert-danger' style='margin-top:10px'>$q->mysql_error<br><strong>$sql</strong></div>
        <script>$jsbuttons</script>";
        return;
    }

    if(pg_num_rows($results)==0){
        echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'><H2>{no_data}</H2><strong>$sql</strong></div> <script>$jsbuttons</script>");
        return;
    }
    $num=0;
    $TRCLASS=null;
    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $num++;
        $pattern=$ligne["sitename"];
        $text_class=null;
        $patternenc=urlencode($pattern);

        $zmd5=md5($pattern);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-pattern=$patternenc&category_id=$category_id&md=$zmd5&function={$_GET["function"]}')");
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
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites} ".$tpl->td_href("{search}:&nbsp;&nbsp;&laquo;&nbsp;$search&nbsp;&raquo;","{raw_format}",$jrows)."</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $html[]=@implode("\n", $ROWS);
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<small></small>";
	$html[]="<script>";
    $html[]="LoadAjaxSilent('compile-category-buttons','$page?compile-category-buttons=yes&function={$_GET["function"]}&categorykey=$category_id');";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-persocats-items').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}
function category_new_save():bool{

    $_POST["personal_database"]=url_decode_special_tool($_POST["newcat"]);
    $_POST["category_text"]=url_decode_special_tool($_POST["description"]);
    $org=$_POST["personal_database"];
    $tpl=new template_admin();
    include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
    $dans=new dansguardian_rules();

    if($_POST["personal_database"]==null){
        $tpl->post_error("No category set or wrong category name $org");
        return false;
    }

    if($_POST["personal_database"]=="security"){$_POST["personal_database"]="security2";}
    $_POST["category_text"]=mysql_escape_string2($_POST["category_text"]);

    if(isset($dans->array_blacksites[$_POST["personal_database"]])){
        echo $tpl->post_error("{$_POST["personal_database"]} :{category_already_exists}<br>{category_already_exists_explain}");
        return false;
    }

    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT categorykey FROM personal_categories WHERE categoryname='{$_POST["personal_database"]}'");
    if(isset($ligne["categorykey"])){
        if(strlen($ligne["categorykey"])>1){
            echo $tpl->post_error("{$_POST["personal_database"]}:{category_already_exists}<br>{category_already_exists_explain}");
            return false;
        }
    }


    $category=new categories();
    if(!$category->create_category($_POST["personal_database"], $_POST["category_text"], $_POST["PublicMode"])){
        echo $tpl->post_error($category->mysql_error);
    }

    return admin_tracks("Adding a new personal category {$_POST["personal_database"]}");


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
    $field=$_GET["field"];
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
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{source}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{updated}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $DivsRun=array();
    $TRCLASS=null;
    $DATAS=$SOURCES_TABLE[$category_id];
    $c=0;
   foreach ($DATAS as $md5url=>$ligne){
       $c++;
        if($md5url==null){$md5url=time()+$c;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
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

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$DivsRun).@implode("\n", $html));

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

function table(){
    $tpl                        = new template_admin();
    $tpl->CLUSTER_CLI           = True;
    $page                       = CurrentPageName();
    $users                      = new usersMenus();
    $function                   = $_GET["function"];
    $topbuttons                 = array();
    $DNSCATZSTATUS=array();
    $RPZSTATUS=array();
    $ManageOfficialsCategories  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $HideOfficialsCategory      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideOfficialsCategory"));
    $PowerDNSEnableClusterSlave = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    $UseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteCategoriesService"));



    $LabelRCat="";

    if ($UseRemoteCategoriesService == 1) {
        $LabelRCat=" (On)";
    }

    $COLUMNUFDB=isUfdb();
    $RPZCOLUMN=isRPZ();


        $sock=new sockets();
        $data=$sock->REST_API("/categories/dns/status");
        $jsonCatz=json_decode($data);

        if (json_last_error() > JSON_ERROR_NONE) {
            echo $tpl->div_error( "Decoding: " . strlen($data) . " bytes " . json_last_error_msg());
        }

        if (json_last_error() == JSON_ERROR_NONE) {
            if ($jsonCatz->Status){
                VERBOSE($jsonCatz->Cats,__LINE__);
                $tb=explode("|",$jsonCatz->Cats);
                foreach ($tb as $zl){
                    VERBOSE($zl,__LINE__);
                    $tb2=explode(":",$zl);
                    $DNSCATZSTATUS[$tb2[0]]=$tb2[1];
                }
                $tb=explode("|",$jsonCatz->Rpz);
                foreach ($tb as $rpzid){
                    $RPZSTATUS[$rpzid]=true;
                }

            }
        }


    $search=trim($_GET["search"]);
    if($search<>null) {
        if(!is_numeric($search)) {
            $search="*$search*";
            $search = str_replace("**", "*", $search);
            $search = str_replace("**", "*", $search);
            $search = str_replace("*", "%", $search);
        }
    }

    if($ManageOfficialsCategories==1){$HideOfficialsCategory=0;}
    $q=new postgres_sql();
    $q->QUERY_SQL("UPDATE personal_categories SET remotecatz=0 WHERE serviceid=0");
    $pp=new categories();
    $pp->initialize();
    $add="Loadjs('$page?category-js=&function=$function',true);";

    $jsCompile=$tpl->framework_buildjs(
        "/category/all/compile",
        "ufdbcat.compile.all.progress","ufdbcat.compile.all.log",
        "progress-ppcategories-restart"
    );

    $jsRemoveAll="Loadjs('$page?remove-all=yes&function=$function')";

    if($EnableLocalUfdbCatService==1){
        $jsCompile="Loadjs('$page?compile-dns-api=yes&function=$function')";
    }



    if($users->AsDansGuardianAdministrator){
        if($PowerDNSEnableClusterSlave==0){
            if($UseRemoteCategoriesService==0) {
                $topbuttons[] = array($add, "fad fa-books-medical", "{new_category}");
            }
        }

        if($UseRemoteCategoriesService==0){
            $topbuttons[] = array($jsCompile, ico_save, "{compile_all_categories}");
        }

        $topbuttons[] = array("Loadjs('$page?remote-categories-service=yes&function=$function')", ico_clouds, "{use_remote_categories_services}$LabelRCat");
        $UseRemoteCategoriesService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteCategoriesService"));
        $EnableRemoteCategoriesServices=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));




        if($UseRemoteCategoriesService==0) {
            if ($EnableRemoteCategoriesServices == 1) {
                $jsSync = "Loadjs('$page?synchronize-dns-api=yes&function=$function')";
                $topbuttons[] = array($jsSync, ico_refresh, "{fetch_categories}");
            }
        }else{
            $jsSync = "Loadjs('$page?synchronize-remote-categories=yes&function=$function')";
            $topbuttons[] = array($jsSync, ico_refresh, "{synchronize}");
        }
        $topbuttons[] = array("Loadjs('$page?all-categories-js=yes')", ico_list, "{all_categories}");


        if($UseRemoteCategoriesService==0) {
            $topbuttons[] = array($jsRemoveAll, ico_trash, "{remove_all_categories}");
        }
    }
    $html[]="<table id='table-persocats-list' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
     if($RPZCOLUMN) {
        $html[] = "<th data-sortable=false>RPZ</th>";
     }
    if($EnableLocalUfdbCatService==1) {
        $html[] = "<th data-sortable=false>DNS</th>";
    }
    if($COLUMNUFDB) {
        $html[] = "<th data-sortable=false>PROXY</th>";
    }
    $html[]="<th data-sortable=true class='text-capitalize'>{categories}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{add}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    if(!isset($_GET["gpid"])){$_GET["gpid"]=0;}
    $jsAfter="LoadAjax('table-loader','$page?table=yes&gpid={$_GET["gpid"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $q=new postgres_sql();
    $WHERE=null;
    $WHERE2=null;
    if($search<>null){
        $search=$q->SearchAntiXSS($search);
        $WHEREA="(categoryname LIKE '$search')";
        if(is_numeric($search)){
            $WHEREA="category_id='$search'";
        }
        $WHERE=" WHERE $WHEREA";
        $WHERE2=" AND $WHEREA";
    }

    $sql="SELECT * FROM personal_categories order by categoryname $WHERE";

    if($HideOfficialsCategory==1){
        $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 $WHERE2 order by categoryname";
    }
    if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories $WHERE order by categoryname";}
    VERBOSE("$sql",__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "<div class='alert alert-danger'>$q->mysql_error<br>$sql</div>";
        return;
    }

    $TRCLASS=null;
    $CategoryRPZ=0;
    $SOURCES=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesExternalSources"));
    $SOURCES_TABLE=unserialize($SOURCES);
    $CURRENT=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    $CategoryServiceRPZEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoryServiceRPZEnabled"));
    if($EnableLocalUfdbCatService==1){
        if($CategoryServiceRPZEnabled==1){
            $CategoryRPZ=1;
        }
    }

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $category_id=$ligne["category_id"];
        if($ManageOfficialsCategories==0){if($category_id>5000){continue;}}
        $categoryname=$ligne["categoryname"];
        $categoryname_enc=urlencode($categoryname);
        $items=$ligne["items"];
        $text_category=$tpl->_ENGINE_parse_body($ligne["category_description"]);
        $itemsEncTxt=numberFormat($items,0,""," ");
        $delete=$tpl->icon_delete("Loadjs('$page?category-delete=$category_id&function=$function')");
        $category_icon=$ligne["category_icon"];
        $official_category=$ligne["official_category"];
        $free_category=$ligne["free_category"];
        $remotecatz=intval($ligne["remotecatz"]);
        $serviceid=intval($ligne["serviceid"]);
        $created=intval($ligne["created"]);
        $meta=intval($ligne["meta"]);
        $icolabel="";
        $icolabel2="";
        $icoRPZ="<span class='label label-default'>{inactive2}</span>";
        $icoDNS="<span class='label label-warning'>{not_compiled}</span>";
        $icoProxy="<span class='label label-warning'>{not_compiled}</span>";

        $source_ico=null;
        if(!isset($SOURCES_TABLE[$category_id])){
            $SOURCES_TABLE[$category_id]=array();
        }

        if(count($SOURCES_TABLE[$category_id])>0){
            $source_ico="<i class='fa-solid fa-file-import' style='color:#1AB394'></i>&nbsp;";
        }

        $button=$tpl->button_autnonome("{add}",
            "Loadjs('fw.ufdb.categorize.php?category_requested=$category_id&cname=$categoryname_enc&function=$function')",
            "fas fa-plus","AsDansGuardianAdministrator",0,"btn-primary","small");

        $button_import=$tpl->button_autnonome("{import}",
            "Loadjs('fw.ufdb.categorize.php?js-import=$category_id&function=$function')",
            "fas fa-file-import","AsDansGuardianAdministrator",0,"btn-info","small");

        $button_export=$tpl->button_autnonome("{export}",
            "Loadjs('$page?js-export=$category_id&function=$function')",
            "fas fa-file-export","AsDansGuardianAdministrator",0,"btn-warning","small");

        $jsCompile=$tpl->framework_buildjs("/category/compile/$category_id",
            "ufdbcat.compile.progress","ufdbcat.compile.txt",
            "progress-ppcategories-restart","$function()");

        $button_compile=$tpl->button_autnonome("{compile2}",$jsCompile,
            "fas fa-download","AsDansGuardianAdministrator",0,"btn-success","small");


        if($official_category==1){
            $delete=$tpl->icon_nothing();
            if($ManageOfficialsCategories==0){
                $button="&nbsp;";
                $button_import="&nbsp;";
                $button_compile="&nbsp;";
            }
        }
        if($free_category==1){
            $items=$CURRENT[$category_id]["items"];
            $itemsEncTxt=numberFormat($items,0,""," ");
            $delete=$tpl->icon_nothing();
            $button="&nbsp;";
            $button_import="&nbsp;";
            $button_compile="&nbsp;";
        }

        if(!$users->AsDansGuardianAdministrator) {
            $delete=$tpl->icon_nothing();
            if(!isset($_SESSION["MANAGE_CATEGORIES"][$category_id])){continue;}

        }

        if($PowerDNSEnableClusterSlave==1){
            $button=$tpl->icon_nothing();
            $button_import=$tpl->icon_nothing();
        }

        $category_link=$tpl->td_href($categoryname,"{click_to_edit}","Loadjs('$page?category-js=$category_id&function=$function')");

        if($remotecatz==1){
            $button="&nbsp;";
            $button_import="&nbsp;";
            $button_export="&nbsp;";
            $button_compile="&nbsp;";
            $delete=$tpl->icon_nothing();
            $category_link=$categoryname;
            $q2=new lib_sqlite("/home/artica/SQLITE/proxy.db");
            $sline=$q2->mysqli_fetch_array("SELECT * FROM categories_services WHERE ID=$serviceid");
            $port=$sline["port"];
            $hostname=$sline["hostname"];
            $category_icon="img/20-import.png";
            $items=$ligne["items"];
            $text_category=$tpl->_ENGINE_parse_body($ligne["category_description"]);
            $itemsEncTxt=numberFormat($items,0,""," ");
            if($hostname<>null){$text_category=$text_category." ($hostname:$port)";}
        }

        if($meta==1){
            $button="&nbsp;";
            $button_import="&nbsp;";
            $button_export="&nbsp;";
            $button_compile="&nbsp;";
            $category_link=$categoryname;
            $items=intval($ligne["items"]);
            $text_category=$tpl->_ENGINE_parse_body($ligne["category_description"]);
            $itemsEncTxt=numberFormat($items,0,""," ");
        }

        if($EnableLocalUfdbCatService==1){
            if($remotecatz==0) {
                $button_compile = "&nbsp;";
                if (isset($DNSCATZSTATUS[$category_id])) {
                    $icoDNS = "<span class='label label-primary'>{active2}</span>";
                }
            }else{
                $icoDNS="&nbsp;";
            }
        }
        if($COLUMNUFDB){
           if(!is_null($jsonCatz)) {
                if (property_exists($jsonCatz, "Ufdb")) {
                    if ($jsonCatz->Ufdb->{$category_id}) {
                        $icoProxy = "<span class='label label-primary'>{active2}</span>";
                    }
                }
            }
        }



        $created_text=$tpl->icon_nothing();
        if($created>0){
            $created_text=$tpl->time_to_date($created);
        }
        if($CategoryRPZ==1){
            if($ligne["rpz"]==1){
                if(!isset($RPZSTATUS[$category_id])){
                $icoRPZ="<span class='label label-warning'>{not_compiled}</span>";
                }else{
                    $icoRPZ="<span class='label label-primary'>{active2}</span>";
                }
            }
        }


        $sid=$category_id;
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%'><img src='$category_icon' alt=''></td>";
        if($RPZCOLUMN) {
              $html[] = "<td style='width:1%'>$icoRPZ</td>";
        }
        if($EnableLocalUfdbCatService==1) {
            $html[] = "<td style='width:1%'>$icoDNS</td>";
        }
        if($COLUMNUFDB) {
            $html[] = "<td style='width:1%'>$icoProxy</td>";
        }
        if($UseRemoteCategoriesService==1) {
            $button="&nbsp;";
            $delete="&nbsp;";
            $button_compile="&nbsp;";
            $button_import="&nbsp;";
            $button_export="&nbsp;";
        }


        $html[]="<td nowrap style='width:20%'>$source_ico<strong id='category_name_$sid'>$category_link</strong></td>";
        $html[]="<td style='width:80%'><span id='category_text_$sid'>$icolabel$icolabel2$text_category</span></td>";
        $html[]="<td style='vertical-align:middle;width=1%' nowrap>$created_text</td>";
        $html[]="<td style='vertical-align:middle;width=1%;text-align:right' nowrap><span id='category_items_$sid'>$itemsEncTxt</span></td>";
        $html[]="<td style='vertical-align:middle;width=1%' class='center'>$button_compile</td>";
        $html[]="<td style='vertical-align:middle;width=1%' class='center'>$button_import</td>";
        $html[]="<td style='vertical-align:middle;width=1%' class='center'>$button_export</td>";
        $html[]="<td style='vertical-align:middle;width=1%' class='center'>$button</td>";
        $html[]="<td style='vertical-align:middle;width=1%' class='center'>$delete</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";


    $TINY_ARRAY["TITLE"]="{your_categories}";
    $TINY_ARRAY["ICO"]="fad fa-books";
    $TINY_ARRAY["EXPL"]="{personal_categories_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->th_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}