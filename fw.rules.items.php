<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["change-groupname-js"])){group_chgpname_js();exit;}
if(isset($_GET["change-groupname-popup"])){group_chgpname_popup();exit;}
if(isset($_GET["items-compiled"])){items_compiled();exit;}
if(isset($_GET["urlsdb-gpid"])){item_url_db_uploaded();}
if(isset($_GET["ndpi-list"])){ndpi_list();exit;}
if(isset($_GET["ndpi-choose"])){ndpi_choose();exit;}
if(isset($_POST["new-item-fingerprint"])){server_cert_fingerprint_save();exit;}
if(isset($_POST["new-item-header-save"])){new_item_header_save();exit;}
if(isset($_POST["new-item-save"])){new_item_save();exit;}
if(isset($_POST["SaveGroupName"])){SaveGroupName();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["item-add"])){item_add_popup();exit;}
if(isset($_POST["pattern"])){item_save();exit;}
if(isset($_POST["SaveGroup"])){group_save();exit;}
if(isset($_POST["SaveTime"])){time_save();exit;}
if(isset($_POST["url_db"])){item_url_db_save();exit;}
if(isset($_POST["weekrange_gpid"])){weekrange_save();exit;}
if(isset($_GET["categories-list"])){categories_list();exit;}
if(isset($_GET["add-categories-js"])){categories_js();exit;}
if(isset($_GET["category-post-all"])){categories_select_all();exit;}
if(isset($_GET["add-categories-popup"])){categories_popup();exit;}
if(isset($_GET["category-post-js"])){category_post_js();exit;}
if(isset($_GET["category-unlink"])){category_unlink();exit;}
if(isset($_GET["item-tabs"])){item_tabs();exit;}
if(isset($_GET["item-start"])){item_start();exit;}
if(isset($_GET["item-search"])){item_search();exit;}
if(isset($_GET["search"])){item_table();exit;}
if(isset($_GET["new-item-js"])){new_item_js();exit;}
if(isset($_GET["new-item-popup"])){new_item_popup();exit;}
if(isset($_GET["item-delete"])){item_delete();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_GET["import-item-js"])){item_import_js();exit;}

if(isset($_GET["item-import-popup"])){item_import_popup();exit;}
if(isset($_GET["file-uploaded"])){item_import_uploaded();exit;}
if(isset($_GET["file-load-functions"])){file_load_functions();exit;}

if(isset($_GET["countries-search"])){countries_search();exit;}
if(isset($_GET["spamc-search"])){spamc_search();exit;}
if(isset($_GET["countries-enable"])){countries_enable();exit;}
if(isset($_GET["spmac-val"])){spamc_val();exit;}
if(isset($_GET["countries-selectall-js"])){countries_selectall();exit;}
if(isset($_GET["countries-deselectall-js"])){countries_dselectall();exit;}


if(isset($_GET["item-description-js"])){items_description_js();exit;}
if(isset($_GET["item-description-popup"])){items_description_popup();exit;}
if(isset($_POST["item-description"])){items_description_save();exit;}
if(isset($_GET["fill-description"])){item_description();exit;}

if(isset($_GET["category-add-all-js"])){categories_all_js();exit;}
if(isset($_POST["category-add-all"])){category_all_perform();exit;}

if(isset($_GET["category-add-dangerous-js"])){category_dangerous_js();exit;}
if(isset($_POST["category-add-dangerous"])){category_dangerous_perform();exit;}
if(isset($_GET["category-add-polluate-js"])){category_polluate_js();exit;}
if(isset($_POST["category-add-polluate"])){category_polluate_perform();exit;}
if(isset($_GET["category-add-nonproductive-js"])){category_nonproductive_js();exit;}
if(isset($_POST["category-add-nonproductive"])){category_nonproductive_perform();exit;}
if(isset($_GET["category-clean"])){category_clean_js();exit;}
if(isset($_POST["category-clean"])){category_clean_perform();exit;}

items_js();
function category_clean_js():bool{
    $page=CurrentPageName();
    $groupid=intval($_GET["category-clean"]);
    $tpl=new template_admin();
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=base64_decode($_GET["js-after"]);
        }
    }
    $js="LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');$js_after";
    return $tpl->js_confirm_execute("{delete_all_items}",
        "category-clean",$groupid,
        $js
    );
}
function category_clean_perform():bool{
    $groupid=$_POST["category-clean"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid");
    return admin_tracks("Removing all catgegories from acls group $groupid");
}
function category_nonproductive_js():bool{
    $groupid=intval($_GET["category-add-nonproductive-js"]);
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=base64_decode($_GET["js-after"]);
        }
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js="LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');$js_after";

    return $tpl->js_confirm_execute("{nonproductive_cat_explain}<br>{category_family_add_expl}",
        "category-add-nonproductive",$groupid,
        $js
    );
}
function category_nonproductive_perform():bool{
    $groupid=$_POST["category-add-nonproductive"];
    $list="10,14,41,42,45,57,58,132,97,150,148,130,112,109,71,238";
    $tbl=explode(",",$list);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    foreach ($tbl as $category) {
        $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid AND pattern='$category'");
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ('$groupid','$category',1)");
    }

    return admin_tracks("Adding Non-productive categories inside acls group $groupid");

}
function category_polluate_js():bool{
    $groupid=intval($_GET["category-add-polluate-js"]);
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=base64_decode($_GET["js-after"]);
        }
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js="LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');$js_after";

    return $tpl->js_confirm_execute("{pollution_categories_explain}<br>{category_family_add_expl}",
        "category-add-polluate",$groupid,
        $js
    );
}
function category_polluate_perform():bool{
    $groupid=$_POST["category-add-polluate"];
    $list="143,5,91,238";
    $tbl=explode(",",$list);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    foreach ($tbl as $category) {
        $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid AND pattern='$category'");
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ('$groupid','$category',1)");
    }

    return admin_tracks("Adding Polluate categories inside acls group $groupid");

}
function category_dangerous_js():bool{
    $groupid=intval($_GET["category-add-dangerous-js"]);
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=base64_decode($_GET["js-after"]);
        }
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js="LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');$js_after";

    return $tpl->js_confirm_execute("{dangerous_categories_explain}<br>{category_family_add_expl}",
        "category-add-dangerous",$groupid,
        $js
    );
}

function categories_all_js():bool{
    $groupid=intval($_GET["category-add-all-js"]);
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=base64_decode($_GET["js-after"]);
        }
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js="LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');$js_after";

    return $tpl->js_confirm_execute("{add_all_categories_explain}<br>{category_family_add_expl}",
        "category-add-all",$groupid,
        $js
    );
}

function category_all_list():array{
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    $q=new postgres_sql();
    if(!$q->TABLE_EXISTS("personal_categories")){
        $categories=new categories();
        $categories->initialize();
    }

    $sql="SELECT *  FROM personal_categories WHERE free_category=0 AND meta=0 ORDER BY categoryname";
    $results=$q->QUERY_SQL($sql);
    $f=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $categoryname = $ligne["categoryname"];
        $category_id = $ligne["category_id"];

        $official_category = $ligne["official_category"];
        $free_category = $ligne["free_category"];
        if (preg_match("#^reserved#", $categoryname)) {
            continue;
        }
        $ISOFFICIAL = false;
        if ($official_category == 1) {
            $ISOFFICIAL = true;
        }
        if ($free_category == 1) {
            $ISOFFICIAL = true;
        }
        if($ISOFFICIAL){
            if(!$CORP_LICENSE){continue;}
        }
        $f[]=$category_id;
    }
    return $f;
}

function category_all_perform():bool{
    $groupid=$_POST["category-add-all"];
    $tbl=category_all_list();

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    foreach ($tbl as $category) {
        $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid AND pattern='$category'");
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ('$groupid','$category',1)");
    }

    return admin_tracks("Adding all categories inside acls group $groupid");

}
function category_dangerous_perform():bool{
    $groupid=$_POST["category-add-dangerous"];
    $list="92,135,64,140,181,111,105,46,149,238";
    $tbl=explode(",",$list);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    foreach ($tbl as $category) {
        $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid AND pattern='$category'");
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ('$groupid','$category',1)");
    }

    return admin_tracks("Adding Dangerous categories inside acls group $groupid");

}
function group_chgpname_js(){
    $groupid=intval($_GET["change-groupname-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'");



    $tpl->js_dialog7("{group_properties}: {$ligne["GroupName"]}",
        "$page?change-groupname-popup=$groupid&js-after={$_GET["js-after"]}",850);

}

function group_chgpname_popup():bool{
    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $groupid=intval($_GET["change-groupname-popup"]);
    $jsafter=base64_decode($_GET["js-after"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $btname="{apply}";
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $backjs="dialogInstance7.close();";

    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $FullExplain="<i>{about2}:$item_explain</i>";
    $form[]=$tpl->field_hidden("SaveGroup", $groupid);
    $form[]=$tpl->field_text("GroupName", "{groupname}",$tpl->utf8_encode($ligne["GroupName"]));
    $html[]=$tpl->form_outside($groupTypeText,$form,
        $FullExplain,$btname,"$backjs$jsafter","AsDansGuardianAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function items_description_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-description-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT pattern FROM webfilters_sqitems WHERE ID=$ID");
    $title=$ligne["pattern"];
    $tpl->js_dialog9($title,"$page?item-description-popup=$ID");
}
function items_description_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-description-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT pattern,description FROM webfilters_sqitems WHERE ID=$ID");
    $title=$ligne["pattern"];

    $form[]=$tpl->field_hidden("item-description",$ID);
    $form[]=$tpl->field_text("description","{description}",$ligne["description"],true);
    $refresh="LoadAjaxSilent('descitem-$ID','$page?fill-description=$ID');";
    echo $tpl->form_outside("",$form,null,"{apply}","dialogInstance9.close();$refresh","AsDansGuardianAdministrator",true);

}

function item_description(){
    $ID=intval($_GET["fill-description"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $page=CurrentPageName();
    $ligne=$q->mysqli_fetch_array("SELECT description FROM webfilters_sqitems WHERE ID=$ID");
    $description=trim($ligne["description"]);
    if($description<>null){
        $description=$tpl->td_href($description,null,"Loadjs('$page?item-description-js=$ID')");
    }else{
        $description=$tpl->icon_nothing("Loadjs('$page?item-description-js=$ID')");
    }
    echo $tpl->_ENGINE_parse_body($description);
}



function items_description_save(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ID=intval($_POST["item-description"]);
    $description=$q->sqlite_escape_string2($_POST["description"]);
    $q->QUERY_SQL("UPDATE webfilters_sqitems SET description='$description' WHERE ID='$ID'");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
}

function URL_ADDONS(){
    if(!isset($_GET["TableLink"])){$_GET["TableLink"]=null;}
    if(!isset($_GET["RefreshTable"])){$_GET["RefreshTable"]=null;}
    if(!isset($_GET["ProxyPac"])){$_GET["ProxyPac"]=0;}
    if(!isset($_GET["firewall"])){$_GET["firewall"]=0;}
    if(!isset($_GET["function"])){$_GET["function"]=null;}
    if(!isset($_GET["js-after"])){$_GET["js-after"]=null;}
    if(!isset($_GET["RefreshFunction"])){$_GET["RefreshFunction"]=null;}
    if(!isset($_GET["items-refresh"])){$_GET["items-refresh"]=null;}



    $TableLink=$_GET["TableLink"];
    $RefreshTable=$_GET["RefreshTable"];
    $ProxyPac=$_GET["ProxyPac"];
    $firewall=$_GET["firewall"];
    $function=$_GET["function"];
    $jsafter=$_GET["js-after"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $items_refresh=$_GET["items-refresh"];


    return "&js-after=$jsafter&function=$function&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&RefreshFunction=$RefreshFunction&items-refresh=$items_refresh";
}

function items_js():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if(!$q->FIELD_EXISTS("webfilters_sqgroups","bulkimport")){
        $q->QUERY_SQL("ALTER TABLE webfilters_sqgroups ADD bulkimport TEXT NULL");
        $q->QUERY_SQL("ALTER TABLE webfilters_sqgroups ADD bulkmd5 TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("webfilters_sqgroups","description")){
        $q->QUERY_SQL("ALTER TABLE webfilters_sqgroups add description TEXT NULL");
    }

    $tpl=new template_admin();
    $groupid=intval($_GET["groupid"]);
    if($groupid==0){
        echo $tpl->js_error("No Group identifier defined");
        return false;
    }
    $GroupType="";
    $URL_ADDONS=URL_ADDONS();
    $function=$_GET["function"];
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $qProxy=new mysql_squid_builder(true);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    if(isset($qProxy->acl_GroupType[$ligne["GroupType"]])) {
        $GroupType = $qProxy->acl_GroupType[$ligne["GroupType"]];
    }
    if($ligne["GroupType"]=="ndpi"){$GroupType="Deep Packet inspection";}
    if($ligne["GroupType"]=="geoip"){$GroupType=$tpl->_ENGINE_parse_body("{geo_location}");}
    return $tpl->js_dialog8("{items} - {group}: {$ligne["GroupName"]} $GroupType","$page?item-tabs=$groupid&function2=$function$URL_ADDONS",850);

}

function new_item_backjs():string{
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $function_search="";
    if(isset($_GET["function_search"])){
        $function_search=$_GET["function_search"];
    }
    $items_refresh=$_GET["items-refresh"];
    return "js-after=$jsafter&function=$function&function_search=$function_search&items-refresh=$items_refresh";
}

function new_item_js(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $groupid=intval($_GET["new-item-js"]);
    $new_item_backjs=new_item_backjs();
    $qProxy=new mysql_squid_builder(true);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $groupname=$qProxy->acl_GroupType[$ligne["GroupType"]];

    if($ligne["GroupType"]=="ndpi"){
        $tpl->js_dialog7("{new_item} - {group}: {$ligne["GroupName"]}","$page?ndpi-list=$groupid&$new_item_backjs");
        return;
    }
    $tpl->js_dialog7("{new_item} - {group}: {$ligne["GroupName"]} $groupname","$page?new-item-popup=$groupid&$new_item_backjs");

}

function item_import_js(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $groupid=intval($_GET["import-item-js"]);
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $qProxy=new mysql_squid_builder(true);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $groupname=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $tpl->js_dialog7("{import} - {group}: $groupname","$page?item-import-popup=$groupid&js-after=$jsafter&function=$function",650);
}

function item_import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $groupid=intval($_GET["item-import-popup"]);
    $_SESSION["ACLS"]["JS_AFTER"]=$_GET["js-after"];
    $_SESSION["ACLS"]["FUNCTION"]=$_GET["function"];
    $_SESSION["ACLS"]["GPID"]=$groupid;
    $_SESSION["ACLS"]["DIV"]="progress-import-$groupid";

    $html[]="<div id='progress-import-$groupid'></div>";
    $html[]="<div class='alert alert-info'>{import_file_standard_explain}</div>";
    $html[]="<div class='center'>";
    $html[]=$tpl->button_upload("{upload_a_file}",$page,null,"&function=$function");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}



function file_uploaded(){
    header("content-type: application/x-javascript");
    $file=$_GET["file-uploaded"];
    $function=$_GET["function"];
    $page=CurrentPageName();
    $basename=basename($file);
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup_categories.log";
    $ARRAY["CMD"]="ufdbguard.php?restore-categories=".urlencode($file)."&uploaded=yes";
    $ARRAY["TITLE"]="{restore_backup} $basename";
    $ARRAY["AFTER"]="Loadjs('$page?file-load-functions=yes&function=$function');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=backup-categories-progress')";
    echo $jsrestart;
}

function file_load_functions():bool{
    header("content-type: application/x-javascript");
    $function=$_GET["function"];
    if($function<>null){
        echo "$function()\n";
    }

    echo "if (document.getElementById('table-perso-category-loader') ){\n";
    echo "LoadAjax('table-perso-category-loader','fw.ufdb.categories.php?table=yes');\n";
    echo "}\n";
    return true;
}



function categories_js(){
    $qProxy=new mysql_squid_builder(true);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["add-categories-js"]);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $groupname=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $tpl->js_dialog8("{add_category} - {group}: {$ligne["GroupName"]} ($groupname)","$page?add-categories-popup=$groupid");

}
function item_tabs(){
    if(!isset($_GET["ProxyPac"])){$_GET["ProxyPac"]=0;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=$_GET["item-tabs"];
    $ProxyPac=intval($_GET["ProxyPac"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType,params,bulkimport FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupName=$ligne["GroupName"];
    $GroupType=$ligne["GroupType"];
    $ONEITEM["the_shields"]=true;
    $ONEITEM["opendns"]=true;
    $ONEITEM["opendnsf"]=true;
    $ONEITEM["proxy_auth_ads"]=true;
    $ONEITEM["proxy_auth_adou"]=true;
    $ONEITEM["time"]=true;
    $ONEITEM["url_db"]=true;
    $ONEITEM["categories"]=true;
    $ONEITEM["facebook"]="FaceBook - {macro}";
    $ONEITEM["teamviewer"]="TeamViewer - {macro}";
    $ONEITEM["whatsapp"]="whatsapp - {macro}";
    $ONEITEM["office365"]="office 365 - {macro}";
    $ONEITEM["skype"]="Skype - {macro}";
    $ONEITEM["youtube"]="YouTube - {macro}";
    $ONEITEM["google"]="Google - {macro}";
    $ONEITEM["localnet"]="{local_network}";
    $ONEITEM["weekrange"]="{local_network}";
    $ONEITEM["ldap_group"]="{dynamic_ldap_group} (local)";
    $ONEITEM["webfilter"]="{webfiltering}";
    $ONEITEM["AclsGroup"]=true;
    $ONEITEM["netbiosname"]=true;
    $ONEITEM["articablackreputation"]=true;
    $ONEITEM["all"]=true;
    $ONEITEM["reputation"]=true;
    $ONEITEM["dmarc"]=true;
    $ONEITEM["spf"]=true;
    $ONEITEM["spamc"]=true;


    $URL_ADDONS=URL_ADDONS();
    $GroupNameEnc=$tpl->utf8_encode($GroupName);

    VERBOSE("$groupid == {$ligne["bulkimport"]}",__LINE__);
    $STR_BULK=false;
    if(strlen($ligne["bulkimport"])>6){$STR_BULK=true;}

    if($GroupType=="spamc"){
        $array["{spamc}"]="$page?item-start=$groupid{$URL_ADDONS}";
    }

    if(!isset($ONEITEM[$GroupType])){
       if(!$STR_BULK){$array["{items}"]="$page?item-start=$groupid$URL_ADDONS";}
        if(is_file("/etc/squid3/acls/container_$groupid.txt")){
            $array["{compiled_items}"]="$page?items-compiled=$groupid$URL_ADDONS";
        }
    }
    if($GroupType=="AclsGroup"){
        $array["{objects}"]="fw.rules.items.objects.php?gpid=$groupid$URL_ADDONS";
    }

    $array[$GroupNameEnc]="$page?item-popup=$groupid$URL_ADDONS";

    if($ProxyPac==1){
        if($GroupType<>"all") {
            $array["{proxy_servers}"] = "fw.rules.items.pac.php?gpid=$groupid$URL_ADDONS";
        }
    }
    echo $tpl->tabs_default($array);
}
function items_compiled():bool{
    $tpl=new template_admin();
    $groupid=intval($_GET["items-compiled"]);
    $srcfile="/etc/squid3/acls/container_$groupid.txt";
    $content=@file_get_contents($srcfile);
    $contentz=explode("\n",$content);
    $html[]=$tpl->field_textareacode("container_$groupid",null,$content);
    echo $tpl->form_outside("{compiled_items}: " . $tpl->FormatNumber(count($contentz))." {items}",$html);
    return true;
}

function item_start(){
    $groupid    = $_GET["item-start"];
    $jsafter    = $_GET["js-after"];
    $function   = $_GET["function"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $page       = CurrentPageName();
    $q          = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne      = $q->mysqli_fetch_array("SELECT GroupType FROM webfilters_sqgroups 
                  WHERE ID='$groupid'");

    $GroupType=$ligne["GroupType"];
    if($GroupType=="spamc"){
        echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','$page?spamc-search=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
        return;
    }
    if($GroupType=="fwgeo" OR $GroupType=="geoip"){
        echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','$page?countries-search=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
        return;
    }
    if($GroupType=="categories"){
        $_GET["item-add"]=$groupid;
        item_category();
        return true;
    }


    echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','$page?item-search=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
}

function item_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=intval($_GET["item-popup"]);
    $URL_ADDONS=URL_ADDONS();

    $html[]=$tpl->_ENGINE_parse_body("
			<div class=row style='margin-top:20px'>
					<div id='fw-items-table' style='margin-top:10px'></div>
			</div>");


    $html[]="
<script>
		LoadAjax('fw-items-table','$page?item-add=$groupid$URL_ADDONS');
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function proxy_reputation():bool{
    $page=CurrentPageName();
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $jsafter=base64_decode($_GET["js-after"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if(!$q->FIELD_EXISTS("webfilters_sqgroups","repblack")){
        $q->QUERY_SQL("ALTER TABLE webfilters_sqgroups ADD COLUMN repblack INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("webfilters_sqgroups","repwhite")){
        $q->QUERY_SQL("ALTER TABLE webfilters_sqgroups ADD COLUMN repwhite INTEGER NOT NULL DEFAULT 0");
    }

    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType,repwhite,repblack FROM webfilters_sqgroups WHERE ID='$groupid'");
    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after={$_GET["js-after"]}');";
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_text("GroupName","{groupname}",$tpl->utf8_encode($ligne["GroupName"]),true);

    $q2=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q2->QUERY_SQL("SELECT * FROM rbl_reputations WHERE enabled=1");
    $HASH=array();
    if(count($results)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_reput_rules}"));
        echo $tpl->form_outside("ID: $groupid $groupTypeText:",$html,$item_explain,$btname,"$backjs$jsafter");
        return true;
    }
    $HASH[0]="{inactive2}";
    foreach($results as $index=>$ligne2) {
        $ID = $ligne2["ID"];
        $rulename = $ligne2["rulename"];
        $HASH[$ID]=$rulename;
    }

    $html[]=$tpl->field_array_hash($HASH,"repblack","{rulename}",$ligne["repblack"]);
    $html[]=$tpl->field_array_hash($HASH,"repwhite","{rulename} ({whitelist})",$ligne["repwhite"]);
    echo $tpl->form_outside("ID: $groupid $groupTypeText:",$html,$item_explain,$btname,"$backjs$jsafter");
    return true;

}

function proxy_auth_ads(){
    $page=CurrentPageName();
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $jsafter=base64_decode($_GET["js-after"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];
    if($ligne["GroupType"]=="ndpi"){$groupTypeText="Deep packet inspection";}
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after={$_GET["js-after"]}');";
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_activedirectorygrp("GroupName","{group2}",$tpl->utf8_encode($ligne["GroupName"]),true);
    echo $tpl->form_outside("ID: $groupid $groupTypeText:",@implode("\n", $html),$item_explain,$btname,"$backjs$jsafter");
}

function proxy_auth_adou(){
    $page=CurrentPageName();
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $jsafter=base64_decode($_GET["js-after"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after={$_GET["js-after"]}');";
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_text("GroupName","{organization}",$tpl->utf8_encode($ligne["GroupName"]),true);
    echo $tpl->form_outside("ID: $groupid $groupTypeText:",@implode("\n", $html),$item_explain,$btname,"$backjs$jsafter");
}

function proxy_ldap_group(){
    $page=CurrentPageName();
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_sqgroups WHERE ID='$groupid'");
    $gpid=$ligne["params"];
    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after={$_GET["js-after"]}');";
    $jsafter=base64_decode($_GET["js-after"]);
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_text("GroupName","{groupname}",$tpl->utf8_encode($ligne["GroupName"]),true);
    $html[]=$tpl->field_browse_ldapgroups("params","{ldap_group}",$gpid);

    if($gpid>0){
        $gpgrp=new groups($gpid);
        $item_explain="<H2>$gpgrp->groupName</H2><h3>$gpgrp->description</h3> ".count($gpgrp->ARRAY_MEMBERS)." {members}<br>";
    }

    echo $tpl->form_outside("ID: $groupid $groupTypeText:",@implode("\n", $html),$item_explain,$btname,"$backjs$jsafter");
}

function group_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=$_POST["SaveGroup"];
    $GroupNameSave=$_POST["GroupName"];
    $GroupNameSave=utf8_decode($GroupNameSave);

    $ll=array();
    $ll[]="Object Name: $GroupNameSave";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_sqgroups WHERE ID='$gpid'");
    $bulkimport_src=md5($ligne["bulkimport"]);

    $GroupNameSave=$q->sqlite_escape_string2($GroupNameSave);

    $f[]="GroupName='$GroupNameSave'";
    if(isset($_POST["params"])){
        $_POST["params"]=$q->sqlite_escape_string2( $_POST["params"]);
        $f[]="params='{$_POST["params"]}'";
    }

    if(isset($_POST["repblack"])){
        $repblack=intval($_POST["repblack"]);
        $f[]="repblack='$repblack'";
    }
    if(isset($_POST["repwhite"])){
        $repwhite=intval($_POST["repwhite"]);
        $f[]="repwhite='$repwhite'";
    }

    if(isset($_POST["bulkimport"])){
        $ll[]="Mass importation: {$_POST["bulkimport"]}";
        $f[]="bulkimport='{$_POST["bulkimport"]}'";
        $bulkimport_dst=md5($_POST["bulkimport"]);
        if($bulkimport_dst<>$bulkimport_src) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?acls-bulk=yes");
        }
    }


    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET ".@implode(",",$f)." WHERE ID='$gpid'");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
    admin_tracks("Update ACL object ".@implode("; ",$ll));
    return true;
}
function SaveGroupName(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=intval($_POST["groupid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligneGP=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupName=$ligneGP["GroupName"];
    $bulkimport=null;
    $GroupNameSave=$_POST["GroupName"];
    $GroupNameSave=utf8_decode($GroupNameSave);

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $GroupNameSave=$q->sqlite_escape_string2($GroupNameSave);

    $f[]="GroupName='$GroupNameSave'";
    if(isset($_POST["params"])){
        $_POST["params"]=$q->sqlite_escape_string2( $_POST["params"]);
        $f[]="params='{$_POST["params"]}'";
    }
    if(isset($_POST["bulkimport"])){
        $bulkimport="Mass importation to ".$_POST["bulkimport"];
        $f[]="bulkimport='{$_POST["bulkimport"]}'";
    }

    if(isset($_POST["description"])){
        if(!$q->FIELD_EXISTS("webfilters_sqgroups","description")){
            $q->QUERY_SQL("ALTER TABLE webfilters_sqgroups add description TEXT NULL");
        }
        $description=$_POST["description"];
        $description=$q->sqlite_escape_string2($description);
        $f[]="description='$description'";

    }
    $sql="UPDATE webfilters_sqgroups SET ".@implode(",",$f)." WHERE ID='$gpid'";


    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}

    admin_tracks("Updating ACL Group $GroupName to $GroupNameSave and other parameters $bulkimport");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}

function item_url_db_save(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GroupName=$tpl->utf8_decode($_POST["GroupName"]);
    $params=base64_encode(serialize($_POST));
    $GroupID=intval($_POST["url_db"]);
    $ligneGP=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$GroupID'");
    $GroupNameORG=$ligneGP["GroupName"];



    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET GroupName='$GroupName',params='$params' WHERE ID=$GroupID");

    if(isset($_POST["description"])){
        $description=$_POST["description"];
        $description=$q->sqlite_escape_string2($description);
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET description='$description' WHERE ID='$GroupID'");
    }


    if(!$q->ok){echo $q->mysql_error;return false;}

    admin_tracks("Updating ACL Group $GroupNameORG to $GroupName and other parameters");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}
function item_url_db_uploaded(){
    $page=CurrentPageName();
    $file_uploaded=$_GET["file-uploaded"];
    $groupid=intval($_GET["urlsdb-gpid"]);
    // Uploading in /usr/share/artica-postfix/ressources/conf/upload/
    $filename=urlencode($file_uploaded);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/urldb/upload/$groupid/$filename");

    $results=@explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/external_acl_urlsfetchdb.$groupid.logs"));
    $html=array();
    foreach ($results as $line){
        if(preg_match("#^.*?\[[A-Z]+\](.+)#",$line,$re)){
            $html[]="<div style='text-align:left'>{$re[1]}</div>";
        }
    }

    $tpl=new template_admin();
    echo "\nLoadAjax('fw-items-table','$page?item-add=$groupid');\n";
    $tpl->js_display_results(@implode("",$html));

}

function item_url_db(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $groupid=intval($_GET["item-add"]);
    $itemsCheck="&nbsp;";
    $jsafter=base64_decode($_GET["js-after"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $btname="{apply}";
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType,params FROM webfilters_sqgroups WHERE ID='$groupid'");
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after={$_GET["js-after"]}');";
    $form[]=$tpl->field_text("GroupName", "{groupname}",$tpl->utf8_encode($ligne["GroupName"]));
    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $SETTINGS=unserialize(base64_decode($ligne["params"]));
    $WorkDir="/etc/squid3/acls/urlsdb/$groupid";
    $itemsnum="";
    $Count=intval(@file_get_contents("$WorkDir/COUNT"));
    $Checked=intval(@file_get_contents("$WorkDir/CHECK"));

    if($Checked>0){
        $itemsCheckT=distanceOfTimeInWords($Checked,time());
        $itemsCheck="<span class='label label-primary'>{checked}: $itemsCheckT</span>";
    }

    if($Count==0){
        $itemsnum="<span class='label label-danger'>0 {item}</span>";
        $itemsTime="&nbsp;";
    }else{
        $itemsnum="<span class='label label-primary'>".$tpl->FormatNumber($Count)." {item}</span>";
        $UpdateDate=intval(@file_get_contents("$WorkDir/TIME"));
        $itemsTimeT=distanceOfTimeInWords($UpdateDate,time());
        $itemsTime="<span class='label label-primary'>{updated}: $itemsTimeT</span>";
    }

    $buttonUpl=$tpl->button_upload("{import}",$page,"btn-success","&urlsdb-gpid=$groupid");

    $html[]="<div class='col-lg-12'>";
    $html[]="<table style='width:95%'>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;' nowrap cwidth:1%' nowrap><a href='javascript:blur();' OnClick=\"Loadjs('fw.rules.items.urlsdb.php?gpid=$groupid');\">$itemsnum</a></td>";
    $html[]="<td style='width:1%;padding-left:10px' nowrap>$itemsTime</td>";
    $html[]="<td style='width:1%;padding-left:10px' nowrap>$itemsCheck</td>";
    $html[]="<td style='width:99%;text-align: right' nowrap>$buttonUpl</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="</div>";

    $INTERVAL[0]="{never}";
    $INTERVAL[10]="{each} 10 {minutes}";
    $INTERVAL[30]="{each} 30 {minutes}";
    $INTERVAL[60]="{each} 60 {minutes}";
    if($SETTINGS["URL"]==null){$SETTINGS["URL"]="https://openphish.com/feed.txt";}

    $tpl->field_hidden("url_db",$groupid);
    $form[]=$tpl->field_checkbox("AUTOMATIC","{MetaClientAutoUpdate}",intval($SETTINGS["AUTOMATIC"]),"INTERVAL,URL");
    $form[]=$tpl->field_array_hash($INTERVAL,"INTERVAL","nonull:{interval}",intval($SETTINGS["INTERVAL"]));
    $form[]=$tpl->field_text("URL","{url}",$SETTINGS["URL"]);
    $html[]="<p>&nbsp;</p>";
    $html[]=$tpl->form_outside("ID: $groupid $groupTypeText:",$form,$item_explain,$btname,"$backjs$jsafter;Loadjs('fw.acls.filltable.php')");

    echo $tpl->_ENGINE_parse_body($html);

}

function item_time(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $groupid=intval($_GET["item-add"]);
    $jsafter=base64_decode($_GET["js-after"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $btname="{apply}";
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after={$_GET["js-after"]}');";
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $html[]=$tpl->field_text("GroupName", "{groupname}",$tpl->utf8_encode($ligne["GroupName"]));
    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];


    $sql="SELECT other FROM webfilters_sqitems WHERE gpid='$groupid'";
    $ligne=$q->mysqli_fetch_array($sql);
    $pattern=base64_decode($ligne["other"]);
    $TimeSpace=unserialize($pattern);
    $days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");


    foreach ($days as $num=>$val){
        $html[]=$tpl->field_checkbox("day_{$num}","{{$val}}",$TimeSpace["day_{$num}"]);

    }
    $html[]=$tpl->field_hidden("SaveTime", $groupid);
    $html[]=$tpl->field_clock("H1", "{from_time}", $TimeSpace["H1"]);
    $html[]=$tpl->field_clock("H2", "{to_time}", $TimeSpace["H2"]);
    echo $tpl->form_outside("ID: $groupid $groupTypeText:",@implode("\n", $html),$item_explain,$btname,"$backjs$jsafter;Loadjs('fw.acls.filltable.php')");
}

function item_category():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $users=new usersMenus();

    $add_danger="Loadjs('$page?category-add-dangerous-js=$groupid&js-after={$_GET["js-after"]}')";
    $add_polluate="Loadjs('$page?category-add-polluate-js=$groupid&js-after={$_GET["js-after"]}')";
    $add_nonproduct="Loadjs('$page?category-add-nonproductive-js=$groupid&js-after={$_GET["js-after"]}')";
    $add_all="Loadjs('$page?category-add-all-js=$groupid&js-after={$_GET["js-after"]}')";
    $del_button="Loadjs('$page?category-clean=$groupid&js-after={$_GET["js-after"]}')";
    $opts="Loadjs('$page?change-groupname-js=$groupid&js-after={$_GET["js-after"]}');";

    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array("Loadjs('$page?add-categories-js=$groupid')", ico_plus, "{add_category}");
        $topbuttons[] = array($add_danger, ico_plus, "{dangerous_categories}");
        $topbuttons[] = array($add_polluate, ico_plus, "{pollution_categories}");
        $topbuttons[] = array($add_nonproduct, ico_plus, "{nonproductive}");
        $topbuttons[] = array($add_all, ico_plus, "{all_categories}");
        $topbuttons[] = array($del_button, ico_trash, "{delete_all}");
    }
    $topbuttons[] = array($opts, ico_options, "{group_properties}");

    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="<div id='categories-list-$groupid' style='margin-top:0'></div>";
    $html[]="<script>LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function categories_list(){
    include_once(dirname(__FILE__)."/ressources/class.categories.inc");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $EnableNRDS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNRDS"));
    $qpost=new postgres_sql();
    $sql="SELECT *  FROM personal_categories ORDER BY categoryname ASC";
    $results = $qpost->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {

        $CatID=intval($ligne["category_id"]);
        if ($ligne["category_icon"] == null) {
            $ligne["category_icon"] = "img/20-categories-personnal.png";
        }
        if (isset($ALREADY[$CatID])) {
            continue;
        }
        if($CatID==238){
            if($EnableNRDS==0){
                continue;
            }
        }

        $img = "{$ligne["category_icon"]}";
        $ligne['category_description'] = $tpl->utf8_encode($ligne['category_description']);

        $categoryname=$ligne["categoryname"];
        $main[$CatID]["img"]=$img;
        $main[$CatID]["name"]=$categoryname;
        $main[$CatID]["free_category"]=$ligne["free_category"];
        $main[$CatID]["category_description"]= $ligne['category_description'];
        $main[$CatID]["meta"]= $ligne['meta'];


    }


    $cgard=new categories();
    $zgaurd=$cgard->CGuard_categories();
    foreach ($zgaurd as $CatID=>$catname){
            $main[$CatID]["img"]="cguard";
            $main[$CatID]["name"]=$catname;
            $main[$CatID]["free_category"]=0;
            $main[$CatID]["category_description"]="{category} CGuard $catname";
        }


    $tpl=new template_admin();
    $groupid=$_GET["categories-list"];
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$groupid ORDER BY pattern";
    $results = $q->QUERY_SQL($sql);

    $table[]="<table class='table table-hover'><thead>
	<tr>
	<th colspan='2'>{categories}</th>
	<th>&nbsp&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	";

    foreach ($results as $index=>$ligne) {
        VERBOSE("Pattern: [{$ligne["pattern"]}]",__LINE__);
        if(!is_numeric($ligne["pattern"])){
            $sline = $qpost->mysqli_fetch_array("SELECT category_id FROM personal_categories WHERE categoryname='{$ligne["pattern"]}'");
            $category_id=intval($sline["category_id"]);
            if($category_id==0){
                $ligne["pattern"]=$category_id;
                $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE pattern='{$ligne["pattern"]}' AND gpid=$groupid");
                $q->QUERY_SQL("INSERT INTO webfilters_sqitems (pattern,gpid) VALUES ('$category_id','$groupid')");
            }
        }

        $id = md5($ligne["pattern"] . microtime(true));
        $remotecatz_text = null;
        $patternenc = intval(trim($ligne["pattern"]));
        VERBOSE("Pattern: $patternenc",__LINE__);
        $category_id = $patternenc;
        if($category_id==0){continue;}
        $remote_explain=null;
        $meta=0;

        $categoryname       = htmlspecialchars_decode($main[$category_id]["name"]);
        $free_category      = intval($main[$category_id]["free_category"]);
        $text_class         = null;
        if($free_category==1){continue;}

        if(isset($main[$category_id]["meta"])){
            $meta=intval($main[$category_id]["meta"]);
        }

        if($categoryname==null){
            if($category_id>0){$categoryname="{category} $category_id ({$ligne["pattern"]})";}
            if($category_id==0){$categoryname="<span class='text-danger'>{corrupted} {category}: <strong>{$ligne["pattern"]}</strong></span>";}
        }

        if($meta==1){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";

        }


        $ico_img="<img src='{$main[$category_id]["img"]}' alt=''>";
        if($main[$category_id]["img"]=="cguard"){
            $ico_img="<i class='fa-solid fa-shield-quartered'></i>";
        }
        $table[]="<tr id='$id'>";
        $table[]="<td class='$text_class' style='width:1%' nowrap>$ico_img</td>";
        $table[]="<td class='$text_class'><strong>{$categoryname}</strong>: {$main[$category_id]["category_description"]}$remote_explain</small></td>";
        $table[]="<td class='$text_class' style='width:1%' nowrap>". $tpl->icon_delete("Loadjs('$page?category-unlink=$patternenc&gpid=$groupid&md=$id')","AsDansGuardianAdministrator")."</td>";
        $table[]="</tr>";

    }
    $table[]="</tbody></table><script>NoSpinner()</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $table));
    return true;
}

function category_unlink(){
    $category=$_GET["category-unlink"];
    $groupid=$_GET["gpid"];

    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $md=$_GET["md"];
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid AND pattern='$category'");
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return;}
    echo "$('#$md').remove();\n";
}

function categories_select_all(){
    header("content-type: application/x-javascript");
    $groupid=$_GET["category-post-all"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid");
    $qPos=new postgres_sql();

    $sql="SELECT *  FROM personal_categories WHERE free_category=0 ORDER BY categoryname ASC";
    $results = $qPos->QUERY_SQL($sql);

    while ($ligne = pg_fetch_assoc($results)) {
        $category_id=$ligne["category_id"];
        $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,enabled) 
        VALUES ('$groupid','$category_id',1)");
    }
    $page=CurrentPageName();
    echo "LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');\n";
    echo "dialogInstance2.close();\n";

}

function category_post_js(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $category=$_GET["category-post-js"];
    $groupid=$_GET["ID"];
    $md=$_GET["md"];

    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$groupid AND pattern='$category'");
    $q->QUERY_SQL("INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ('$groupid','$category',1)");
    if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return;}

    echo "LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');\n";
    echo "$('#$md').remove();\n";

}

function categories_popup(){
    include_once(dirname(__FILE__)."/ressources/class.categories.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=$_GET["add-categories-popup"];
    $Ccategories=new categories();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $EnableNRDS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNRDS"));

    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$groupid ORDER BY pattern";
    $results = $q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne) {
        $ALREADY[intval($ligne["pattern"])]=true;
    }

    $qPos=new postgres_sql();
    $Ccategories->patches_categories();
    $dans=new dansguardian_rules();
    if($qPos->COUNT_ROWS("personal_categories")==0){$dans->CategoriesTableCache();}

    if(!$qPos->TABLE_EXISTS("personal_categories")){
        $Ccategories->initialize();
    }

    $sql="SELECT *  FROM personal_categories ORDER BY categoryname ASC";
    $results = $qPos->QUERY_SQL($sql);
    if(!$qPos->ok){echo $qPos->mysql_error_html(true);}
    $TRCLASS=null;
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?category-post-all=$groupid');\">";
    $html[]="<i class='fa fa-plus'></i> {select_all} </label>";
    $html[]="</div>";
    $html[]="<table id='table-category-add' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    while ($ligne = pg_fetch_assoc($results)) {
        if(preg_match("#^reserved#",$ligne['categoryname'])){continue;}
        if($ligne["category_icon"]==null){$ligne["category_icon"]="img/20-categories-personnal.png";}
        if(isset($ALREADY[$ligne["category_id"]])){continue;}
        $meta=intval($ligne["meta"]);
        $license=null;$remote_explain=null;
        $img="{$ligne["category_icon"]}";
        $md=md5(serialize($ligne));
        $ligne['category_description']=$tpl->utf8_encode($ligne['category_description']);
        $category_id=$ligne["category_id"];
        $remotecatz=intval($ligne["remotecatz"]);
        if($remotecatz>0){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";

        }
        if($meta==1){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";

        }

        $styleText=null;
        $js="Loadjs('$page?category-post-js=$category_id&ID=$groupid&md=$md')";
        $button="<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";

        if($EnableNRDS==0){
            if($category_id==238){
                $button="<button class='btn btn-default btn-xs' OnClick=\"Blur()\">{disabled}</button>";

                $styleText="style='color:#CCCCCC'";
            }
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><img src='$img' alt='none'></td>";
        $html[]="<td $styleText nowrap style='width:1%'>".$tpl->_ENGINE_parse_body("{$ligne['categoryname']}")."</td>";
        $html[]="<td $styleText style='width:99%;'>".$tpl->_ENGINE_parse_body("{$ligne['category_description']}$remote_explain$license")."</td>";
        $html[]=$tpl->_ENGINE_parse_body("<td>$button</td>");
        $html[]="</tr>";

    }
    $TheShieldsCguard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    if($TheShieldsCguard==1) {
        $cguardico = "<i class='fa-solid fa-shield-quartered'></i>";
        $CGuard_categories = $Ccategories->CGuard_categories();
        foreach ($CGuard_categories as $category_id => $categoryname) {
            if (isset($ALREADY[$ligne["category_id"]])) {
                continue;
            }
            if ($TRCLASS == "footable-odd") {
                $TRCLASS = null;
            } else {
                $TRCLASS = "footable-odd";
            }

            $md = md5("$category_id$categoryname");
            $js = "Loadjs('$page?category-post-js=$category_id&ID=$groupid&md=$md')";
            $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
            $html[] = "<tr class='$TRCLASS' id='$md'>";
            $html[] = "<td style='width:1%'>$cguardico</td>";
            $html[] = "<td>$categoryname</td>";
            $html[] = "<td>$categoryname</td>";
            $html[] = $tpl->_ENGINE_parse_body("<td>$button</td>");
            $html[] = "</tr>";

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
$(document).ready(function() { $('#table-category-add').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}


function time_save(){

    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ID=$_POST["SaveTime"];


    $GroupNameSave=url_decode_special_tool($_POST["GroupName"]);
    $GroupNameSave=$tpl->utf8_decode($GroupNameSave);
    $GroupNameSave=sqlite_escape_string2($GroupNameSave);
    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET GroupName='$GroupNameSave' WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error_html(true);}

    if(isset($_POST["description"])){
        $description=$_POST["description"];
        $description=$q->sqlite_escape_string2($description);
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET description='$description' WHERE ID='$ID'");
    }



    $sql="SELECT other,pattern FROM webfilters_sqitems WHERE gpid='$ID'";


    $ligne=$q->mysqli_fetch_array($sql);

    $H1=$_POST["H1"];
    $H2=$_POST["H2"];

    $H1T=strtotime(date("Y-m-d $H1:00"));
    $H2T=strtotime(date("Y-m-d $H2:00"));

    if($H2T<$H1T){
        $tpl=new templates();
        echo $tpl->javascript_parse_text("{ERROR_SQUID_TIME_ACL}");
        return;
    }


    $pattern=base64_encode(serialize($_POST));
    if(strlen(trim($ligne["pattern"]))<3){
        $sql="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other) VALUES ('NONE','$ID','1','$pattern');";
    }else{
        $sql="UPDATE webfilters_sqitems SET pattern='NONE',other='$pattern' WHERE gpid='$ID'";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
}


function item_add_popup(){
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $jsafter=base64_decode($_GET["js-after"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $qProxy=new mysql_squid_builder(true);
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tt=time();
    $funcjs=null;
    $URL_ADDONS=URL_ADDONS();
    if($function<>null){$funcjs="$function();";}
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid$URL_ADDONS');";
    $btname="{edit}";
    if(strlen($function)>1){
        $backjs="";
        $jsafter="$function();";
    }

    if(!$q->FIELD_EXISTS("webfilters_sqgroups","description")){
        $q->QUERY_SQL("ALTER TABLE webfilters_sqgroups add description TEXT NULL");
    }

    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType,params,bulkimport,description FROM webfilters_sqgroups WHERE ID='$groupid'");
    $item_explain=$tpl->item_explain($ligne["GroupType"]);

    foreach ($qProxy->acl_GroupType as $index=>$value){
        $acl_GroupType[$index]=$value;
    }
    foreach ($qProxy->acl_GroupType_DNSDIST as $index=>$value){
        $acl_GroupType[$index]=$value;
    }
    foreach ($qProxy->acl_GroupType_DNSFW as $index=>$value){
        $acl_GroupType[$index]=$value;
    }

    if(isset($acl_GroupType[$ligne["GroupType"]])) {
        $groupTypeText = $acl_GroupType[$ligne["GroupType"]];
    }else{
        $groupTypeText=$qProxy->GroupTypeToString($ligne["GroupType"]);
    }

    if($ligne["GroupType"]=="reputation"){
        proxy_reputation();
        return;
    }

    if($ligne["GroupType"]=="proxy_auth_ads"){
        proxy_auth_ads();
        return;
    }
    if($ligne["GroupType"]=="proxy_auth_adou"){
        proxy_auth_adou();
        return;
    }
    if($ligne["GroupType"]=="ldap_group"){
        proxy_ldap_group();
        return;
    }

    if($ligne["GroupType"]=="weekrange"){
        $params=unserialize(base64_decode($ligne["params"]));
        $data=base64_encode(serialize($params["TIME"]));
        $params=urlencode($data);
        $tpl->form_add_button("{schedule}","Loadjs('fw.week.selectors.php?EncodedArray=$params&CallBack=SaveWeekrange$tt')");

    }


    if($ligne["GroupType"]=="time"){
        item_time();
        return;
    }
    if($ligne["GroupType"]=="url_db"){
        item_url_db();
        return;
    }


    if($ligne["GroupType"]=="categories"){
        item_category();
        return;
    }
    if($ligne["GroupType"]=="localnet"){
        $qNet=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $results=$qNet->QUERY_SQL("SELECT ipaddr FROM networks_infos WHERE enabled=1");
        foreach ($results as $index=>$ligne2){
            $tt[]=$ligne2["ipaddr"];
        }
        $item_explain=@implode(", ",$tt);
    }

    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$groupid ORDER BY pattern";
    $results = $q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne2){
        $pp[]=$ligne2["pattern"];
    }





    $html[]=$tpl->field_hidden("SaveGroupName", $groupid);
    $html[]=$tpl->field_hidden("groupid", $groupid);
    $html[]=$tpl->field_text("GroupName", "{groupname}",$tpl->utf8_encode($ligne["GroupName"]));

    if($ligne["GroupType"]=="netbiosname"){
        $html[]=$tpl->field_text("params", "{append_domain}",$ligne["params"]);
    }

    $html[]=$tpl->field_textarea("description","{description}",$ligne["description"]);
    if(strlen($ligne["bulkimport"])>6){
        $html[]=$tpl->field_text("bulkimport", "{bulk_import}",$ligne["bulkimport"]);
    }


    //$html[]=$tpl->field_textareacode("pattern","{pattern}",@implode("\n", $pp));
    echo $tpl->form_outside("$groupTypeText: {$ligne["GroupType"]}",@implode("\n", $html),$item_explain,$btname,"$backjs;Loadjs('fw.acls.filltable.php');Loadjs('fw.proxy.acls.bugs.php?refresh=yes');$funcjs;$jsafter","AsDansGuardianAdministrator");

    $html=array();
    $html[]="<script>";
    $html[]="
	var x_SaveAclGroupMode= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		Loadjs('$page?groupid=$groupid$URL_ADDONS');
		Loadjs('fw.proxy.acls.bugs.php?refresh=yes');
		$backjs;Loadjs('fw.acls.filltable.php');$funcjs;$jsafter
	}	
	
	
	function SaveWeekrange$tt(params){
		var XHR = new XHRConnection();
		XHR.appendData('weekrange_gpid', '$groupid');
		XHR.appendData('weekrange_time', params);
		XHR.sendAndLoad('$page', 'POST',x_SaveAclGroupMode);  	
	}";
    $html[]="</script>";

    echo @implode("\n",$html);
}

function weekrange_save(){
    $gpid=$_POST["weekrange_gpid"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'");
    $params=unserialize(base64_decode($ligne["params"]));
    $params["TIME"]=unserialize(base64_decode($_POST["weekrange_time"]));
    $newval=base64_encode(serialize($params));
    $newval=mysql_escape_string2($newval);
    $sql="UPDATE webfilters_sqgroups SET params='$newval' WHERE ID='$gpid'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\nin line:".__LINE__."\n".basename(__FILE__)."\n\n$sql\n";return;}

}

function ndpi_choose(){
    //ndpi-choose=$strlow&gpid=$groupid&md=$md&js-after=$jsafter&function=$function
    $gpid=$_GET["gpid"];
    $md=$_GET["md"];
    $jsafter=base64_decode($_GET["jsafter"]);
    $function=$_GET["function"];
    $ndpi=$_GET["ndpi-choose"];
    $tpl=new template_admin();
    $user=$_SESSION["uid"];
    if($user==-100){$user="Manager";}

    $description="{saved_by} $user";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $date=date("Y-m-d H:i:s");
    $sql="INSERT INTO webfilters_sqitems (zdate,description,gpid,pattern,enabled) VALUES ('$date','$description','$gpid','$ndpi',1)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    if($function<>null){
        echo "$function();\n";
    }
    if($jsafter<>null){
        echo $jsafter."\n";
    }


}

function item_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=intval($_POST["groupid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");


    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupType=$ligne["GroupType"];
    $GroupNameSave=url_decode_special_tool($_POST["GroupName"]);
    $GroupNameSave=$tpl->utf8_decode($GroupNameSave);
    $GroupNameSave=sqlite_escape_string2($GroupNameSave);

    if(isset($_POST["description"])){
        $description=$_POST["description"];
        $description=$q->sqlite_escape_string2($description);
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET description='$description' WHERE ID='$gpid'");
    }

    $q->QUERY_SQL("UPDATE webfilters_sqgroups SET GroupName='$GroupNameSave' WHERE ID='$gpid'");
    if(!$q->ok){echo $q->mysql_error_html(true);}

    if(!isset($_POST["pattern"])){return;}
    $pattern=url_decode_special_tool($_POST["pattern"])."\n";
    $f=explode("\n",$pattern);

    $IPClass=new IP();
    $SQ=array();
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if($GroupType=="src"){
            if(!$IPClass->isIPAddressOrRange($line)){
                echo "<li>$GroupType: $line <b>FALSE</b></li>";
                continue;
            }
        }

        if($GroupType=="dst"){
            $IPClass=new IP();
            if(!$IPClass->isIPAddressOrRange($line)){
                echo "<li>$GroupType: $line <b>FALSE</b></li>";
                continue;
            }
        }

        if($GroupType=="arp"){
            if(!$IPClass->IsvalidMAC($line)){
                echo "<li>$GroupType: $line <b>FALSE</b></li>";
                continue;
            }
        }

        if($GroupType=="dstdomain"){
            if(preg_match("#^(http|https|ftp|ftps):\/#", $line)){$arrM=parse_url($line);$line=$arrM["host"];}
            if(strpos($line, "/")>0){$arrM=explode("/",$line);$line=$arrM[0];}

            if(strpos(" $line", "^")==0){
                $squidfam=new squid_familysite();
                $fam=$squidfam->GetFamilySites($line);
                if($fam<>$line){$line="^$line";}
            }

        }


        $line=sqlite_escape_string2($line);
        $SQ[]="('$gpid','$line',1)";

    }

    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'");
    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ".@implode(",", $SQ);
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "{$_POST["pattern"]}<br>".$q->mysql_error_html(false);}


}

function countries_dselectall(){
    include_once(dirname(__FILE__) . "/ressources/class.geoip-db.inc");
    $page = CurrentPageName();
    $gpid = $_GET["countries-deselectall-js"];
    $jsafter = $_GET["js-after"];
    $function = $_GET["function"];
    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$gpid");
    if($jsafter<>null){ echo base64_decode($jsafter)."\n"; }
    if($function<>null){echo "$function()\n";}
    echo "LoadAjax('table-acls-items-$gpid','$page?countries-search=$gpid&js-after=$jsafter&function=$function');\n";
    echo "Loadjs('fw.proxy.acls.bugs.php?refresh=yes');\n";
}

function countries_selectall(){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=$_GET["countries-selectall-js"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$gpid");
    $GEO_IP_COUNTRIES_LIST=GEO_IP_COUNTRIES_LIST();
    foreach ($GEO_IP_COUNTRIES_LIST as $countryCode=>$CountryName) {
        if (!preg_match("#^[A-Z]+$#", $countryCode)) {
            continue;
        }
        $SQ[] = "('$gpid','$countryCode','1')";
    }

    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ".@implode(",", $SQ);
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    if($jsafter<>null){ echo base64_decode($jsafter)."\n"; }
    if($function<>null){echo "$function()\n";}
    echo "LoadAjax('table-acls-items-$gpid','$page?countries-search=$gpid&js-after=$jsafter&function=$function');\n";

}

function spamc_val(){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $tpl=new template_admin();
    $gpid=$_GET["gpid"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $spmacVal=$_GET["spmac-val"];


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT COUNT(*) as tcount FROM webfilters_sqitems WHERE gpid=$gpid ";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo "//$q->mysql_error\n";}
    echo "//gpid = $gpid\n";
    echo "//$sql\n";

    if(intval($ligne["tcount"])>0){

        $sql="UPDATE webfilters_sqitems SET pattern=$spmacVal WHERE gpid=$gpid";
        echo "//$sql\n";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "//$q->mysql_error\n";}
        if($jsafter<>null){ echo base64_decode($jsafter)."\n"; }
        return;
    }

    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ('$gpid','$spmacVal',1)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "//INSERT $q->mysql_error\n";}
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    if($jsafter<>null){ echo base64_decode($jsafter)."\n"; }
}
function countries_enable(){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $tpl=new template_admin();
    $gpid=$_GET["gpid"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $country=$_GET["countries-enable"];


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT ID FROM webfilters_sqitems WHERE gpid=$gpid AND pattern='$country'";
    $ligne=$q->mysqli_fetch_array($sql);
    if(!$q->ok){echo "//$q->mysql_error\n";}
    echo "//gpid = $gpid\n";
    echo "//$sql\n";
    if(!isset($ligne["ID"])){$ligne["ID"]=0;}
    $ID=intval($ligne["ID"]);
    echo "//ID = $ID\n";
    if($ID>0){

        $sql="DELETE FROM webfilters_sqitems WHERE gpid=$gpid AND pattern='$country'";
        echo "//$sql\n";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "//$q->mysql_error\n";}
        if($jsafter<>null){ echo base64_decode($jsafter)."\n"; }
        return;
    }

    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,enabled) VALUES ('$gpid','$country',1)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "//INSERT $q->mysql_error\n";}
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    if($jsafter<>null){ echo base64_decode($jsafter)."\n"; }

}

function spamc_search()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=$_GET["spamc-search"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $t=time();
    $TRCLASS=null;

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE gpid=$gpid");
    foreach ($results as $index=>$ligne){

        $CN=$ligne["pattern"];
    }
    if($CN==null){$CN=5;}
    $SPAMC[0]=0;
    $SPAMC[1]=1;
    $SPAMC[2]=2;
    $SPAMC[3]=3;
    $SPAMC[3]=3;
    $SPAMC[4]=4;
    $SPAMC[5]=5;
    $SPAMC[6]=6;
    $SPAMC[7]=7;
    $SPAMC[8]=8;
    $SPAMC[9]=9;
    $SPAMC[10]=10;
    //$js="Loadjs('$page?countries-enable=$countryCode&gpid=$gpid&js-after=$jsafter&function={$_GET["function"]}&md=$md')";
    $html[]=$tpl->div_explain("{spamc_explain}");
    $html[]=$tpl->field_array_hash($SPAMC,"spmac","{score}",$CN,true,null,"ChangeSpamc",false);

    $html[]="<script>";
    $html[]="function ChangeSpamc(val){";
    $html[]="\tLoadjs('$page?spmac-val='+val+'&gpid=$gpid&js-after=$jsafter&function={$_GET["function"]}')";
    $html[]="}";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function countries_search():bool{
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=intval($_GET["countries-search"]);
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $t=time();
    $TRCLASS=null;
    $DISPLAY_RECORDS=true;$CHECKDB=false;
    $colspan=4;
    if($gpid==0){
        echo $tpl->div_error("Wrong group id");
        return false;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupType=$ligne["GroupType"];
    if($GroupType=="geoip"){
        $colspan=3;
        $DISPLAY_RECORDS=false;
        $CHECKDB=true;
    }


    $results=$q->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE gpid=$gpid");
    foreach ($results as $index=>$ligne){
        $CN=$ligne["pattern"];
        $ADDED[$CN]=1;
    }
    $GEO_IP_COUNTRIES_LIST=GEO_IP_COUNTRIES_LIST();
    $topbuttons[] = array("Loadjs('$page?countries-selectall-js=$gpid&js-after=$jsafter&function=$function');", ico_plus, "{select_all}");

    $topbuttons[] = array("Loadjs('$page?countries-deselectall-js=$gpid&js-after=$jsafter&function=$function');",ico_trash,"{disable_all}");

    if($CHECKDB){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/xtgeoip"));
        if(!$json->Status){
            echo  $tpl->div_error($json->Error);
            return false;
        }
        if(!property_exists($json,"Bases")){
           echo  $tpl->div_error("Issue on Rest API");
            return false;
        }
        foreach ($GEO_IP_COUNTRIES_LIST as $countryCode=>$null){
            if(!property_exists($json->Bases,$countryCode)){
                unset($GEO_IP_COUNTRIES_LIST[$countryCode]);
            }
        }
    }


    $html[]=$tpl->th_buttons($topbuttons);
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{country}</th>";
    if($DISPLAY_RECORDS) {
        $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
    }
    $html[]="<th data-sortable=true class='center text-capitalize' data-type='text'>{selected}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $qP=new postgres_sql();


    foreach ($GEO_IP_COUNTRIES_LIST as $countryCode=>$CountryName){
        if(!preg_match("#^[A-Z]+$#",$countryCode)){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $enabled=0;
        if(isset($ADDED[$countryCode])){
            $enabled=$ADDED[$countryCode];
        }
        $opts="";
        $countryCodeDown=strtolower($countryCode);
        $img="img/flags/info.png";
        if(is_file("img/flags/$countryCodeDown.png")){
            $img="img/flags/$countryCodeDown.png";
        }else{
            $opts=" ($countryCode)";
        }

        $enable=$tpl->icon_check($enabled,"Loadjs('$page?countries-enable=$countryCode&gpid=$gpid&js-after=$jsafter&function={$_GET["function"]}&md=$md')");


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><img src='$img'></td>";
        $html[]="<td nowrap>$CountryName$opts</td>";

        if($DISPLAY_RECORDS) {
            $Query="SELECT items FROM ipdeny_countgeo WHERE country='$countryCode'";
            $ligne_sum = $qP->mysqli_fetch_array($Query);
            $ligne_sum["items"] = FormatNumber($ligne_sum["items"]);
            $html[] = "<td  nowrap style='width:1%;text-align: right'>{$ligne_sum["items"]}</td>";
        }
        $html[]="<td  style='width:1%' nowrap class='center'>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='$colspan'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function item_search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=$_GET["item-search"];
   $URLADDON=URL_ADDONS();
    echo "<div id='id-button-place-$gpid' style='margin-bottom:10px;margin-top:10px'></div>";
    echo $tpl->search_block($page, "sqlite:/home/artica/SQLITE/acls.db","webfilters_sqitems",null,"&search-gpid=$gpid&$URLADDON&items-refresh=%s");
}

function server_cert_fingerprint_save(){
    include_once(dirname(__FILE__)."/ressources/class.certificate_parser.inc");
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $hostname=trim(strtolower($_POST["fingerprint-hostname"]));
    $port=intval($_POST["fingerprint-port"]);
    $gpid=intval($_POST["new-item-fingerprint"]);
    $description=trim($_POST["fingerprint-desc"]);
    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $zdate=date("Y-m-d H:i:s");
    if($description==null){$description="{by} $uid $zdate";}

    if(!preg_match("#^([0-9a-z:]+$)#",$hostname)){
        $porttext=null;
        if($port<>443){
            $porttext=":$port";
        }
        $sslcer=new certgetter();
        $Fingerprint=$sslcer->Get_sha1_fingherprint("$hostname$porttext");
        if($Fingerprint==null){
            echo $tpl->post_error("Retreive $hostname$porttext fingerprint failed ".$sslcer->error);
            return false;
        }
        $description=$description." $hostname$porttext - $zdate";
    }else{
        $Fingerprint=$hostname;
    }



    $sq="('$zdate','$uid','$gpid','$Fingerprint','$description',1)";

    $sql="INSERT INTO webfilters_sqitems (zdate,uid,gpid,pattern,description,enabled) VALUES $sq";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error("$sql\n$q->mysql_error");}
    admin_tracks("Adding a new SSL fingerprint $Fingerprint ($description) in Proxy Object #$gpid");
    return true;
}

function new_item_header($gpid,$GroupName):bool{
    $tpl=new template_admin();
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $html[]=$tpl->field_hidden("new-item-header-save", $gpid);
    $html[]=$tpl->field_text("header","{header}","",true);
    $html[]=$tpl->field_text("value","{value}","",false);
    if($jsafter<>null) {
        $js[] = base64_decode($jsafter);
    }
    $js[]="dialogInstance7.close()";
    if($function<>null){
        $js[]="$function()";
    }
    $js[]="Loadjs('fw.acls.filltable.php')";
    $backjs=@implode(";", $js);

    echo $tpl->form_outside($tpl->utf8_encode($GroupName),@implode("\n", $html),"{acl_item_header_explain}","{add}","$backjs");
    return true;

}
function new_item_geoipdest($gpid,$GroupName){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $tpl=new template_admin();
    $GEO_IP_COUNTRIES_LIST=GEO_IP_COUNTRIES_LIST();
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];


    $html[]=$tpl->field_hidden("new-item-save", $gpid);
    $html[]=$tpl->field_array_hash($GEO_IP_COUNTRIES_LIST,"pattern","{countryName}");

    if($jsafter<>null) {
        $js[] = base64_decode($jsafter);
    }
    $js[]="dialogInstance7.close()";
    if($function<>null){
        $js[]="$function()";
    }
    $js[]="Loadjs('fw.acls.filltable.php')";
    $backjs=@implode(";", $js);

    echo $tpl->form_outside($tpl->utf8_encode($GroupName),@implode("\n", $html),"{geoipdest_text}","{add}","$backjs");


}

function new_item_server_cert_fingerprint(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $gpid=$_GET["new-item-popup"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
    $item_explain="{$ligne["GroupType"]}:".$tpl->item_explain($ligne["GroupType"]);
    $btname="{add}";

    $tpl->field_hidden("new-item-fingerprint", $gpid);
    $form[]=$tpl->field_text("fingerprint-hostname","{hostname}",null,true);
    $form[]=$tpl->field_numeric("fingerprint-port","{port}","443",true);
    $form[]=$tpl->field_text("fingerprint-desc","{description}",null,false);

    $js[]=base64_decode($jsafter);
    $js[]="$function()";
    $js[]="dialogInstance7.close()";
    if($function<>null){
        $js[]="$function()";
    }
    $js[]="Loadjs('fw.acls.filltable.php')";
    $backjs=@implode(";", $js);

    echo $tpl->form_outside($tpl->utf8_encode($ligne["GroupName"]),@implode("\n", $form),$item_explain,$btname,"$backjs");
}

function new_item_popup():bool{
    //new-item-popup=$groupid&js-after=$jsafter&function=$function
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=$_GET["new-item-popup"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $function_search=$_GET["function-search"];
    $RefreshFunction=$_GET["RefreshFunction"];
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");

    VERBOSE("Gpid: $gpid | GroupeType = {$ligne["GroupType"]}",__LINE__);

    if($ligne["GroupType"]=="accessrule"){
        echo "<div id='acls-item-choose-main-accessrule-$gpid'></div>";
        echo "<script>LoadAjax('acls-item-choose-main-accessrule-$gpid','fw.rules.items.accessrules.php?gpid=$gpid&page=yes&function2=$function');</script>";
        return true;
    }

    if($ligne["GroupType"]=="adfrom"){
        $URLADDON=URL_ADDONS();
        echo "<div id='acls-item-choose-main-ad-$gpid'></div>";
        echo "<script>LoadAjax('acls-item-choose-main-ad-$gpid','fw.rules.items.activedirectory.php?gpid=$gpid&$URLADDON');</script>";
        return true;
    }
    if($ligne["GroupType"]=="adto"){
        $URLADDON=URL_ADDONS();
        echo "<div id='acls-item-choose-main-ad-$gpid'></div>";
        echo "<script>LoadAjax('acls-item-choose-main-ad-$gpid','fw.rules.items.activedirectory.php?gpid=$gpid&$URLADDON');</script>";
        return true;
    }

    if($ligne["GroupType"]=="server_cert_fingerprint"){
        new_item_server_cert_fingerprint();
        return true;
    }

    if($ligne["GroupType"]=="geoipdest" || $ligne["GroupType"]=="geoipsrc"){
        new_item_geoipdest($gpid,$ligne["GroupName"]);
        return true;
    }
    if($ligne["GroupType"]=="header"){
        new_item_header($gpid,$ligne["GroupName"]);
        return true;

    }

    $item_explain="{$ligne["GroupType"]}:".$tpl->item_explain($ligne["GroupType"]);
    $btname="{add}";

    $html[]=$tpl->field_hidden("new-item-save", $gpid);
    $html[]=$tpl->field_textareacode("pattern","{pattern}","\n");

    if($jsafter<>null) {
        $js[] = base64_decode($jsafter);
    }
    $js[]="dialogInstance7.close();";
    if($function<>null){
        $js[]="$function();";
    }
    if(!is_null($function_search)) {
        if (strlen($function_search) > 2) {
            $js[] = "$function_search();";
        }
    }
    if(strlen($RefreshFunction)>2){
        $js[]=base64_decode($RefreshFunction);
    }

    $js[]="Loadjs('fw.acls.filltable.php')";
    $backjs=@implode(";", $js);

    echo $tpl->form_outside($tpl->utf8_encode($ligne["GroupName"]),@implode("\n", $html),$item_explain,$btname,"$backjs");
    return true;
}
function item_enable():bool{
    $tpl=new template_admin();
    $ID=$_GET["item-enable"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT pattern,enabled FROM webfilters_sqitems WHERE ID=$ID");
    $pattern=$ligne["pattern"];
    if(intval($ligne["enabled"])==1){$newenabled=0;}else{$newenabled=1;}
    $q->QUERY_SQL("UPDATE webfilters_sqitems SET enabled=$newenabled WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    admin_tracks("ACL record $pattern was enabled to $newenabled");
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.proxy.acls.bugs.php?refresh=yes');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;


}

function item_delete():bool{
    $tpl=new template_admin();
    $ID=$_GET["item-delete"];
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];
    $md=$_GET["md"];
    $groupid=$_GET["search-gpid"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $ligne=$q->mysqli_fetch_array("SELECT pattern FROM webfilters_sqitems WHERE ID=$ID");
    $pattern=$ligne["pattern"];

    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID=$ID");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    admin_tracks("ACL record $pattern was removed");

    $js[]="$('#$md').remove();";
    $js[]="Loadjs('fw.acls.filltable.php');";
    if($function<>null){
        $js[]="$function();";
    }
    $js[]=$tpl->jsToTry(base64_decode($jsafter));
    echo @implode("\n",$js);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule=$groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;

}

function new_item_header_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=intval($_POST["new-item-header-save"]);
    $header=$_POST["header"];
    $value=$_POST["value"];
    $pattern="$header:$value";
    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $date=date("Y-m-d H:i:s");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupName=$ligne["GroupName"];
    $SQ[]=$gpid;
    $SQ[]="'$pattern'";
    $SQ[]="'$date'";
    $SQ[]="'$uid'";
    $SQ[]=1;

    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) VALUES (".@implode(",", $SQ).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Add a new SMTP Header acls $pattern for Acl object $GroupName");
}

function new_item_save(){
    $gpid=$_POST["new-item-save"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");




    $GroupType=$ligne["GroupType"];
    $GroupName=$ligne["GroupName"];
    $pattern=url_decode_special_tool($_POST["pattern"])."\n";
    $f=explode("\n",$pattern);
    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $date=date("Y-m-d H:i:s");
    $AdminTrack=array();

    $IPClass=new IP();
    $SQ=array();
    $LOGS=array();
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}

        if($GroupType=="src"){

            if(preg_match("#(.*?)\/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#",$line,$re)){
                $line=$IPClass->maskTocdir($re[1],"{$re[2]}.{$re[3]}.{$re[4]}.{$re[5]}");
            }

            if(!$IPClass->isIPAddressOrRange($line)){
                $LOGS[]="Group type: $GroupType [$line] isIPAddressOrRange return false.";
                continue;
            }
        }
        if($GroupType=="dst"){
            if(preg_match("#(.*?)\/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#",$line,$re)){
                $line=$IPClass->maskTocdir($re[1],"{$re[2]}.{$re[3]}.{$re[4]}.{$re[5]}");
            }

            if(!$IPClass->isIPAddressOrRange($line)){
                $LOGS[]="Group type: $GroupType [$line] isIPAddressOrRange return false.";
                continue;
            }
        }

        if($GroupType=="arp"){
            if(!$IPClass->IsvalidMAC($line)){
                $LOGS[]="Group type: $GroupType [$line] IsvalidMAC return false.";
                continue;
            }
        }

        if($GroupType=="dstdomain"){
            if(preg_match("#bulkimport:(.+)#",$line,$re)){
                    $q->QUERY_SQL("UPDATE webfilters_sqgroups 
                        SET bulkimport='{$re[1]}' WHERE ID='$gpid'");
                $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid='$gpid'");
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?acls-bulk=yes");
                return true;

            }



            if(preg_match("#^(http|https|ftp|ftps):\/#", $line)){$arrM=parse_url($line);$line=$arrM["host"];}
            if(strpos($line, "/")>0){$arrM=explode("/",$line);$line=$arrM[0];}

            if(strpos(" $line", "^")==0){
                $squidfam=new squid_familysite();
                $fam=$squidfam->GetFamilySites($line);
                if($fam<>$line){$line="^$line";}
            }
        }


        $line=sqlite_escape_string2($line);
        $SQ[]="('$gpid','$line','$date','$uid',1)";
        $AdminTrack[]="Add $line record into $GroupName ACL";

    }

    if(count($SQ)==0){
        echo "jserror: no data, because:".@implode(", ",$LOGS);
        return false;
    }


    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) VALUES ".@implode(",", $SQ);
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
    foreach ($AdminTrack as $line) {admin_tracks($line);}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid2.php?explain-this-rule=$gpid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}

function item_table(){

    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $function_search="";
    if(!isset($_GET["function"])){$_GET["function"]=null;}
    $gpid=$_GET["search-gpid"];
    $jsafter=$_GET["js-after"];
    if($_GET["search"]==null){$_GET["search"]="*";}
    $function=$_GET["function"];
    if(isset($_GET["function-search"])) {
        $function_search = $_GET["function-search"];
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupType=$ligne["GroupType"];
    VERBOSE("GroupType: $GroupType",__LINE__);
    $IMPORT_BUTTON=true;
    if($GroupType=="ndpi") {$IMPORT_BUTTON=false;}
    if($GroupType=="accessrule") {$IMPORT_BUTTON=false;}


    if(preg_match("#field:#", $_GET["search"])){
        $search=$tpl->query_pattern(trim(strtolower($_GET["search"])),array());
    }else{
        $s_search="*". $_GET["search"]."*";
        $s_search=str_replace("**", "*", "$s_search");
        $s_search=str_replace("**", "*", "$s_search");
        $s_search=str_replace("*", "%", "$s_search");
        $search["Q"]=" WHERE ((pattern LIKE '$s_search') OR (description LIKE '$s_search') )";
    }

    if(!$q->FIELD_EXISTS("webfilters_sqitems", "zdate")){$q->QUERY_SQL("ALTER TABLE webfilters_sqitems ADD `zdate` text");}
    if(!$q->FIELD_EXISTS("webfilters_sqitems", "uid")){$q->QUERY_SQL("ALTER TABLE webfilters_sqitems ADD `uid` text");}
    if(!$q->FIELD_EXISTS("webfilters_sqitems", "description")){$q->QUERY_SQL("ALTER TABLE webfilters_sqitems ADD `description` text");}

    $qlprx=new mysql_squid_builder();
    $ico=$qlprx->acl_GroupTypeIcon[$GroupType];
    $GEO_IP_COUNTRIES_LIST=GEO_IP_COUNTRIES_LIST();
    if(!isset($search["MAX"])){$search["MAX"]=0;}

    if($search["Q"]==null){$search["Q"]="WHERE 1";}
    if($search["MAX"]==0){$search["MAX"]=150;}
    $sql="SELECT * FROM webfilters_sqitems {$search["Q"]} AND gpid=$gpid ORDER BY ID DESC LIMIT 0,{$search["MAX"]}";
    $results=$q->QUERY_SQL($sql);
    $topbuttons=array();
    $t=time();

    if($users->AsDansGuardianAdministrator) {
        $topbuttons[] = array("Loadjs('$page?new-item-js=$gpid&js-after=$jsafter&function=$function&function-search=$function_search');", ico_plus, "{new_item}");

        if ($IMPORT_BUTTON) {
            $topbuttons[] = array("Loadjs('$page?import-item-js=$gpid&js-after=$jsafter&function=$function&function-search=$function_search');", ico_import, "{import}");
        }
    }
    $btns=$tpl->th_buttons($topbuttons);
    $html[]="<input type='hidden' id='proxy-acls-items-function' value='$function'>";
    $html[]="<table id='table-$t' style='margin-top: 15px' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
//

    $btns_text=base64_encode($tpl->_ENGINE_parse_body($btns));

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $IpClass=new IP();
    foreach ($results as $index=>$ligne){
        $FFF=array();
        $description="";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $date=strtotime($ligne["zdate"]);
        $date_text=$tpl->time_to_date($date,true);
        $strlen=strlen($ligne["pattern"]);
        $pattern=htmlspecialchars($ligne["pattern"]);
        $uid=htmlspecialchars($ligne["uid"]);
        $ID=intval($ligne["ID"]);
        if(!is_null($ligne["description"])) {
            $description = $tpl->_ENGINE_parse_body(trim($ligne["description"]));
        }

        if($GroupType=="accessrule") {
            $ruleid=intval($ligne["pattern"]);
            if($ruleid==0){
                continue;
            }
            $sql="SELECT aclname FROM webfilters_simpleacls WHERE ID='$ruleid'";
            $ligneAcls=$q->mysqli_fetch_array($sql);
            $patternText=") ".$tpl->_ENGINE_parse_body($ligneAcls["aclname"]);
            $uid=$tpl->icon_nothing("");
        }



        if($IpClass->IsvalidMAC($pattern)){
            $host=new hosts($pattern);
            if($host->hostname<>null){
                $FFF[]=$host->hostname;
            }
            if($host->proxyalias<>null){
                $FFF[]=$host->proxyalias;
            }

        }

        if(count($FFF)>0){
            $description=$description."&nbsp;<small>".@implode(", ",$FFF)."</small>";
        }

        if($description<>null){
            $org_description=$description;
            if(strlen($description)>27){$description=substr($description,0,24)."...";}
            $description=$tpl->td_href($description,$org_description,"Loadjs('$page?item-description-js=$ID')");
        }else{
            $description=$tpl->icon_nothing("Loadjs('$page?item-description-js=$ID')");
        }

        if($strlen>27){
            $pattern=$tpl->td_href(substr($pattern,0,24)."...",wordwrap($pattern,38,"<br>",true),null);
        }


        $delete=$tpl->icon_delete("Loadjs('$page?item-delete=$ID&js-after=$jsafter&function={$_GET["function"]}&md=$md&search-gpid={$_GET["search-gpid"]}')","AsDansGuardianAdministrator");
        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?item-enable=$ID&js-after=$jsafter&function={$_GET["function"]}&md=$md')",null,"AsDansGuardianAdministrator");
        $ico2=null;
    if($ico<>null){
        $ico2="<i class='$ico'></i>&nbsp;";
    }
        if($GroupType=="geoipdest"){
            $pattern=$GEO_IP_COUNTRIES_LIST[$ligne["pattern"]];
        }



        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%;' nowrap>$date_text</td>";
        $html[]="<td nowrap>$ico2<strong>$pattern$patternText</strong></td>";
        $html[]="<td nowrap><span id='descitem-$ID'>$description</span></td>";
        $html[]="<td style='width:1%;' nowrap>$uid</td>";
        $html[]="<td  style='width:1%;' nowrap class='center'>$enable</td>";
        $html[]="<td  style='width:1%;' nowrap class='center'>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]="document.getElementById('id-button-place-$gpid').innerHTML=base64_decode('$btns_text');";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function item_import_uploaded(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $basename=basename($file);
    $js=array();
    $jsafter=$_SESSION["ACLS"]["JS_AFTER"];
    $function=$_SESSION["ACLS"]["FUNCTION"];
    if($jsafter<>null){$js[]=$jsafter;}
    if($function<>null){$js[]="$function()";}
    $js[]="Loadjs('fw.acls.filltable.php')";

    $groupid=$_SESSION["ACLS"]["GPID"];
    $mainid=$_SESSION["ACLS"]["DIV"];



    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/acls.import.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/acls.import.log";
    $ARRAY["CMD"]="squid2.php?import-acls-items=".urlencode($file)."&groupid=$groupid";
    $ARRAY["TITLE"]="{importing} $basename";
    $ARRAY["AFTER"]=@implode(";", $js);
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$mainid')";
    echo $jsrestart;
}

function ndpi_list(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["ndpi-list"]);
    //ndpi-list=$groupid&js-after=$jsafter&function=$function
    $jsafter=$_GET["js-after"];
    $function=$_GET["function"];


    $f=explode("\n",@file_get_contents("/proc/net/xt_ndpi/proto"));
    $t=time();
    $html=array();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{software}</th>";
    $html[]="<th data-sortable=false></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");;
    $results=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems where gpid=$groupid");
    foreach ($results as $index=>$ligne){
        $pattern=strtolower($ligne["pattern"]);
        $ALREADY[$pattern]=true;
    }

    $TRCLASS=null;
    foreach ($f as $line) {
        $md = md5($line);
        if (!preg_match("#([0-9a-z]+)\s+.+?\s+(.+?)\s+#", $line, $re)) {
            continue;
        }
        $service = $re[2];

        if (preg_match("#custom#", $line)) {
            continue;
        }
        if (is_numeric($service)) {
            continue;
        }
        $srv=strtolower($service);
        if(isset($ALREADY[$srv])){continue;}
        $MAIN[$srv]=$service;

    }

    ksort($MAIN);
    foreach ($MAIN as $strlow=>$strnorm) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"left\"><strong>$strnorm</strong></td>";
        $html[]="<td style='width:1%;' nowrap cwidth:1%'>". $tpl->button_autnonome("{select}","Loadjs('$page?ndpi-choose=$strlow&gpid=$groupid&md=$md&js-after=$jsafter&function=$function')",
                "fas fa-box-check","AsFirewallManager",0,"btn-primary","small")."</td>";
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
<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));



}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}