<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!isReader($users)){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.proxypac.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : TableLink/ProxyPac/firewall/RefreshFunction/items-refresh/gpid etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["TableLink"])){$_GET["TableLink"]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["TableLink"]);}
if(isset($_GET["RefreshFunction"])){$_GET["RefreshFunction"]=jsafter_token($_GET["RefreshFunction"]);}
if(isset($_GET["items-refresh"])){$_GET["items-refresh"]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["items-refresh"]);}
foreach(array("ProxyPac","firewall","item-tabs","item-start","item-search","new-item-popup","search-gpid","gpid") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=intval($_GET[$xssk]);}}
// Politique Meta : un objet tenu (metaid) est en lecture seule ; refus avant tout appel au demon.
fri_meta_guard();
if(isset($_GET["itype-search"])){itype_search();exit;}
if(isset($_POST["itype"])){itype_save();exit;}
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
if(isset($_POST["ritm-delete"])){ritm_delete_save();exit;}
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
if(isset($_GET["fqdn-status"])){fqdn_status();exit;}
if(isset($_GET["search"])){item_table();exit;}
if(isset($_GET["new-item-js"])){new_item_js();exit;}
if(isset($_GET["new-item-popup"])){new_item_popup();exit;}
if(isset($_GET["item-delete"])){item_delete();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
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
            $js_after=jsafter_sanitize(base64_decode($_GET["js-after"]));
        }
    }
    $js="LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');$js_after";
    return $tpl->js_confirm_execute("{delete_all_items}",
        "category-clean",$groupid,
        $js
    );
}
function category_clean_perform():bool{
    $groupid = intval($_POST["category-clean"]);
    if($groupid<=0){return false;}
    list($ok,$data)=acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$groupid");
    if(!$ok){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($data);
        return false;
    }
    admin_tracks("Clean all categories from ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}
function category_nonproductive_js():bool{
    $groupid=intval($_GET["category-add-nonproductive-js"]);
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=jsafter_sanitize(base64_decode($_GET["js-after"]));
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
    $groupid = intval($_POST["category-add-nonproductive"]);
    if($groupid<=0){return false;}
    // Hardcoded "nonproductive" category IDs from legacy code — DO NOT change.
    $cats = [10,14,41,42,45,57,58,132,97,150,148,130,112,109,71,238];
    $items = [];
    foreach($cats as $c){ $items[] = ["pattern"=>(string)$c]; }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid, "items"=>$items, "mode"=>"upsert",
    ]);
    if(!$ok){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($data);
        return false;
    }
    admin_tracks("Add nonproductive categories to ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}
function category_polluate_js():bool{
    $groupid=intval($_GET["category-add-polluate-js"]);
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=jsafter_sanitize(base64_decode($_GET["js-after"]));
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
    $groupid = intval($_POST["category-add-polluate"]);
    if($groupid<=0){return false;}
    // Hardcoded "polluate" category IDs from legacy code — DO NOT change.
    $cats = [143,5,91,238];
    $items = [];
    foreach($cats as $c){ $items[] = ["pattern"=>(string)$c]; }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid, "items"=>$items, "mode"=>"upsert",
    ]);
    if(!$ok){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($data);
        return false;
    }
    admin_tracks("Add polluate categories to ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}
function category_dangerous_js():bool{
    $groupid=intval($_GET["category-add-dangerous-js"]);
    $js_after=null;
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=jsafter_sanitize(base64_decode($_GET["js-after"]));
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
            $js_after=jsafter_sanitize(base64_decode($_GET["js-after"]));
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
    $cats=acls_api_get("/webfilters/categories");
    $f=array();
    if(!is_array($cats)){ return $f; }
    foreach($cats as $ligne){
        $categoryname = $ligne["categoryName"] ?? "";
        $category_id  = $ligne["categoryId"] ?? "";
        $free_category = intval($ligne["freeCategory"] ?? 0);
        $meta          = intval($ligne["meta"] ?? 0);
        if(preg_match("#^reserved#", $categoryname)){ continue; }
        if($free_category==1){ if(!$CORP_LICENSE){ continue; } }
        if($meta==1)         { if(!$CORP_LICENSE){ continue; } }
        if($category_id === ""){ continue; }
        $f[]=$category_id;
    }
    return $f;
}

function category_all_perform():bool{
    $groupid = intval($_POST["category-add-all"]);
    if($groupid<=0){return false;}
    $cats = acls_api_get("/webfilters/categories");
    if(!is_array($cats)){ echo "categories list unavailable"; return false; }
    $items = [];
    foreach($cats as $c){
        $cid = $c["categoryId"] ?? "";
        if($cid === ""){ continue; }
        $items[] = ["pattern"=>(string)$cid];
    }
    if(empty($items)){ return false; }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid, "items"=>$items, "mode"=>"upsert",
    ]);
    if(!$ok){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($data);
        return false;
    }
    admin_tracks("Add ALL categories to ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}
function category_dangerous_perform():bool{
    $groupid = intval($_POST["category-add-dangerous"]);
    if($groupid<=0){return false;}
    // Hardcoded "dangerous" category IDs from legacy code — DO NOT change.
    $cats = [92,135,64,140,181,111,105,46,149,238];
    $items = [];
    foreach($cats as $c){ $items[] = ["pattern"=>(string)$c]; }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid, "items"=>$items, "mode"=>"upsert",
    ]);
    if(!$ok){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($data);
        return false;
    }
    admin_tracks("Add dangerous categories to ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}
function group_chgpname_js(){
    $groupid=intval($_GET["change-groupname-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();

    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $ligne=["GroupName"=>($grp["groupName"] ?? "")];

    $tpl->js_dialog7("{group_properties}: {$ligne["GroupName"]}",
        "$page?change-groupname-popup=$groupid&js-after=".jsafter_token($_GET["js-after"])."",850);

}

function group_chgpname_popup():bool{
    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $groupid=intval($_GET["change-groupname-popup"]);
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));
    $btname="{apply}";
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    if(!$grp){ echo $tpl->_ENGINE_parse_body($tpl->div_error("group $groupid not found")); return false; }
    $ligne=["GroupName"=>$grp["groupName"],"GroupType"=>$grp["groupType"]];
    $backjs="dialogInstance7.close();";

    $item_explain=$tpl->item_explain($ligne["GroupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $FullExplain="<i>{about2}:$item_explain</i>";
    $form[]=$tpl->field_hidden("SaveGroup", $groupid);
    $form[]=$tpl->field_text("GroupName", "{groupname}",$tpl->utf8_encode($ligne["GroupName"]));
    $html[]=fri_meta_lock_form($tpl,$groupid);
    $html[]=$tpl->form_outside($groupTypeText,$form,
        $FullExplain,$btname,"$backjs$jsafter","AsDansGuardianAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function items_description_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-description-js"]);
    $it=acls_api_get("/proxy/acls/sqlite/items/$ID");
    $title=$it ? ($it["pattern"] ?? "") : "";
    $tpl->js_dialog9($title,"$page?item-description-popup=$ID");
}
function items_description_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["item-description-popup"]);
    $it=acls_api_get("/proxy/acls/sqlite/items/$ID");
    $title=$it ? ($it["pattern"] ?? "") : "";
    $currentDesc=items_description_clear($it ? ($it["description"] ?? "") : "");

    $form[]=$tpl->field_hidden("item-description",$ID);
    $form[]=$tpl->field_text("description","{description}",$currentDesc,true);
    $refresh="LoadAjaxSilent('descitem-$ID','$page?fill-description=$ID');";
    echo $tpl->form_outside("",$form,null,"{apply}","dialogInstance9.close();$refresh","AsDansGuardianAdministrator",true);

}

function item_description(){
    $ID=intval($_GET["fill-description"]);
    $it=acls_api_get("/proxy/acls/sqlite/items/$ID");
    if(!$it){ return; }
    echo htmlspecialchars(items_description_clear($it["description"] ?? ""),ENT_QUOTES);
}

// Ticket RWE 16/11 : la popup postait la description encodee (encodeURIComponent)
// sans CLEAN_POST, d'ou "Test%20Description" en base. Les valeurs deja stockees
// ainsi sont decodees a l'affichage, seulement si elles portent une sequence %XX.
function items_description_clear($value):string{
    $value=(string)$value;
    if(preg_match('#%u[0-9A-Fa-f]{4}#',$value)){
        $value=url_decode_special_tool($value);
    }
    if(preg_match('#%[0-9A-Fa-f]{2}#',$value)){
        $value=rawurldecode($value);
    }
    return $value;
}



function items_description_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["item-description"]);
    $description=items_description_clear($_POST["description"] ?? "");
    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/items/$ID",["description"=>$description]);
    if(!$ok){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($data);
        return false;
    }
    admin_tracks("Update description for item $ID");
    return true;
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
    $RefreshTable=jsafter_token($_GET["RefreshTable"]);
    $ProxyPac=$_GET["ProxyPac"];
    $firewall=$_GET["firewall"];
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $jsafter=jsafter_token($_GET["js-after"]);
    $RefreshFunction=$_GET["RefreshFunction"];
    $items_refresh=$_GET["items-refresh"];


    return "&js-after=$jsafter&function=$function&TableLink=$TableLink&RefreshTable=$RefreshTable&ProxyPac=$ProxyPac&firewall=$firewall&RefreshFunction=$RefreshFunction&items-refresh=$items_refresh";
}

function items_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=intval($_GET["groupid"]);
    if($groupid==0){
        echo $tpl->js_error("No Group identifier defined");
        return false;
    }
    $GroupType="";
    $URL_ADDONS=URL_ADDONS();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(isset($_GET["function"])){
        $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    }
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    if(!$grp){ echo $tpl->js_error("group $groupid not found"); return false; }
    $type=(string)($grp["groupType"] ?? "");
    list($ico,$GroupType)=ritm_type_info($type);
    if($type=="ndpi"){$GroupType="Deep Packet inspection";}
    if($type=="geoip"){$GroupType="{geo_location}";}
    $title=ritm_t("{ritm_win_title}",(string)($grp["groupName"] ?? ""));
    if($GroupType!==""){ $title.=" &middot; $GroupType"; }
    return $tpl->js_dialog8($title,"$page?item-tabs=$groupid&function2=$function$URL_ADDONS",850);

}

function new_item_backjs():string{
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $function_search="";
    $items_refresh="";
    if(isset($_GET["function_search"])){
        $function_search=$_GET["function_search"];
    }
    if(isset($_GET["items-refresh"])){
        $items_refresh=$_GET["items-refresh"];
    }
    return "js-after=$jsafter&function=$function&function_search=$function_search&items-refresh=$items_refresh";
}

function new_item_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=intval($_GET["new-item-js"]);
    $new_item_backjs=new_item_backjs();
    $qProxy=new mysql_squid_builder(true);

    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $ligne=["GroupName"=>($grp["groupName"] ?? ""),"GroupType"=>($grp["groupType"] ?? "")];
    $groupname=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $TableLink="";
    if(isset($_GET["TableLink"])){
        $TableLink="&TableLink={$_GET["TableLink"]}";
    }

    if($ligne["GroupType"]=="ndpi"){
        return $tpl->js_dialog7("{new_item} - {group}: {$ligne["GroupName"]}","$page?ndpi-list=$groupid&$new_item_backjs");
    }
    return $tpl->js_dialog7("{new_item} - {group}: {$ligne["GroupName"]} $groupname","$page?new-item-popup=$groupid&$new_item_backjs$TableLink");

}







function file_uploaded():bool{
    if(!fw_progress_page_priv("AsDansGuardianAdministrator|AsFirewallManager")){return false;}   // task link signed for this session: built only for whom may run it
    header("content-type: application/x-javascript");
    $file=$_GET["file-uploaded"];
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $page=CurrentPageName();
    $fileEn=urlencode($file);
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs("/category/backup/restore/$fileEn",
        "backup_categories.progress",
        "backup_categories.progress.log",
        "backup-categories-progress",
        "Loadjs('$page?file-load-functions=yes&function=$function');");

    echo $jsrestart;
    return true;
}

function file_load_functions():bool{
    header("content-type: application/x-javascript");
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["add-categories-js"]);
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $ligne=["GroupName"=>($grp["groupName"] ?? ""),"GroupType"=>($grp["groupType"] ?? "")];
    $groupname=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $tpl->js_dialog9("{add_category} - {group}: {$ligne["GroupName"]} ($groupname)","$page?add-categories-popup=$groupid",980);

}
function item_tabs(){
    if(!isset($_GET["ProxyPac"])){$_GET["ProxyPac"]=0;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=$_GET["item-tabs"];
    $ProxyPac=intval($_GET["ProxyPac"]);
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $ligne=["GroupName"=>($grp["groupName"] ?? ""),"GroupType"=>($grp["groupType"] ?? ""),"params"=>($grp["params"] ?? ""),"bulkimport"=>($grp["bulkImport"] ?? null)];
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
    $ONEITEM["dstserv"]=true;

    $URL_ADDONS=URL_ADDONS();
    $GroupNameEnc=$tpl->utf8_encode($GroupName);

    VERBOSE("$groupid == {$ligne["bulkimport"]}",__LINE__);
    $STR_BULK=false;
    if(!is_null($ligne["bulkimport"])) {
        if (strlen($ligne["bulkimport"]) > 6) {
            $STR_BULK = true;
        }
    }

    if($GroupType=="spamc"){
        $array["{spamc}"]="$page?item-start=$groupid{$URL_ADDONS}";
    }

    if(!isset($ONEITEM[$GroupType])){
       if(!$STR_BULK){$array["{ritm_items}"]="$page?item-start=$groupid$URL_ADDONS";}
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
    echo proxyobject_meta_banner($tpl,$groupid);
    echo $tpl->tabs_default($array);
}
function items_compiled():bool{
    $tpl=new template_admin();
    $groupid=intval($_GET["items-compiled"]);
    $srcfile="/etc/squid3/acls/container_$groupid.txt";
    $content=@file_get_contents($srcfile);
    $contentz=explode("\n",$content);
    $html[]=$tpl->field_textareacode("container_$groupid",null,$content);
    echo $tpl->form_outside("{compiled_items}: " . $tpl->FormatNumber(count($contentz))." {items}",
        $html,"","","","",false,true);
    return true;
}

function item_start():bool{
    $groupid    = $_GET["item-start"];
    $jsafter    =jsafter_token($_GET["js-after"]);
    $function   = preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $RefreshFunction=$_GET["RefreshFunction"];
    $page       = CurrentPageName();
    $grp        = acls_api_get("/proxy/acls/sqlite/group/".intval($groupid));
    $GroupType  = $grp["groupType"] ?? "";

    if($GroupType=="itype"){
        echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','$page?itype-search=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
        return false;
    }

    if($GroupType=="spamc"){
        echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','$page?spamc-search=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
        return false;
    }
    if($GroupType=="fwgeo" OR $GroupType=="geoip"){
        echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','$page?countries-search=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
        return false;
    }
    if($GroupType=="dnsdynamic"){
        echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','fw.rules.items.dnsdynamic.php?head=yes&gpid=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
        return false;
    }


    // Objet FQDN : état de résolution (daemon, toutes les 5 min) au-dessus de la
    // liste ordinaire des noms.
    if($GroupType=="fqdn"){
        echo "<div id='fqdn-status-$groupid' style='margin-top:10px'></div>
	<script>LoadAjaxSilent('fqdn-status-$groupid','$page?fqdn-status=$groupid');</script>";
    }

    if($GroupType=="categories"){
        $_GET["item-add"]=$groupid;
        item_category();
        return true;
    }


    echo "<div id='table-acls-items-$groupid' style='margin-top:10px'></div>
	<script>LoadAjax('table-acls-items-$groupid','$page?item-search=$groupid&js-after=$jsafter&function=$function&RefreshFunction=$RefreshFunction');</script>";
    return true;
}

// État de résolution d'un objet FQDN (GET /firewall/fqdn/status).
function fqdn_status(){
    $tpl=new template_admin();
    $gid=intval($_GET["fqdn-status"]);
    $html=array();
    $html[]=$tpl->div_explain("{fw_fqdn_object}||{fw_fqdn_help}");
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/fqdn/status"),true);
    $names=(is_array($json) && !empty($json["Status"]) && is_array($json["Data"][$gid] ?? null))?$json["Data"][$gid]:array();
    if(count($names)>0){
        $html[]="<table class='table table-condensed'><tbody>";
        foreach($names as $n){
            $name=htmlspecialchars((string)($n["name"] ?? ""));
            $ok=intval($n["last_ok"] ?? 0);
            $err=(string)($n["error"] ?? "");
            if($err==""){
                $state="<span class='label label-primary'>".intval($n["addrs"] ?? 0)." {addresses}</span>";
            }elseif($ok>0){
                $state="<span class='label label-warning'>{fw_fqdn_unresolved_since} ".date("Y-m-d H:i",$ok)."</span>";
            }else{
                $state="<span class='label label-danger'>{fw_fqdn_unresolved_since}: {fw_never_used}</span>";
            }
            $html[]="<tr><td>$name</td><td style='width:1%' nowrap>$state</td></tr>";
        }
        $html[]="</tbody></table>";
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
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
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));

    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    if(!$grp){ echo $tpl->_ENGINE_parse_body($tpl->div_error("group $groupid not found")); return false; }
    $item_explain=$tpl->item_explain($grp["groupType"]);
    $groupTypeText=$qProxy->acl_GroupType[$grp["groupType"]];
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after=".jsafter_token($_GET["js-after"])."');";
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_text("GroupName","{groupname}",$tpl->utf8_encode($grp["groupName"]),true);

    $reps=acls_api_get("/firewall/rbl-reputations");
    $HASH=array();
    if(!is_array($reps) || count($reps)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_reput_rules}"));
        echo $tpl->form_outside("ID: $groupid $groupTypeText:",$html,$item_explain,$btname,"$backjs$jsafter");
        return true;
    }
    $HASH[0]="{inactive2}";
    foreach($reps as $r) {
        $ID = intval($r["id"] ?? 0);
        $rulename = $r["ruleName"] ?? "";
        $HASH[$ID]=$rulename;
    }

    $html[]=$tpl->field_array_hash($HASH,"repblack","{rulename}",$grp["repblack"]);
    $html[]=$tpl->field_array_hash($HASH,"repwhite","{rulename} ({whitelist})",$grp["repwhite"]);
    echo $tpl->form_outside("ID: $groupid $groupTypeText:",$html,$item_explain,$btname,"$backjs$jsafter");
    return true;

}

function proxy_auth_ads(){
    $page=CurrentPageName();
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $GroupType=$grp ? ($grp["groupType"] ?? "") : "";
    $GroupName=$grp ? ($grp["groupName"] ?? "") : "";
    $item_explain=$tpl->item_explain($GroupType);
    $groupTypeText=$qProxy->acl_GroupType[$GroupType] ?? "";
    if($GroupType=="ndpi"){$groupTypeText="Deep packet inspection";}
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after=".jsafter_token($_GET["js-after"])."');";
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_activedirectorygrp("GroupName","{group2}",$tpl->utf8_encode($GroupName),true);
    echo $tpl->form_outside("ID: $groupid $groupTypeText:",@implode("\n", $html),$item_explain,$btname,"$backjs$jsafter");
}

function proxy_auth_adou(){
    $page=CurrentPageName();
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $GroupType=$grp ? ($grp["groupType"] ?? "") : "";
    $GroupName=$grp ? ($grp["groupName"] ?? "") : "";
    $item_explain=$tpl->item_explain($GroupType);
    $groupTypeText=$qProxy->acl_GroupType[$GroupType] ?? "";
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after=".jsafter_token($_GET["js-after"])."');";
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_text("GroupName","{organization}",$tpl->utf8_encode($GroupName),true);
    echo $tpl->form_outside("ID: $groupid $groupTypeText:",@implode("\n", $html),$item_explain,$btname,"$backjs$jsafter");
}

function proxy_ldap_group(){
    $page=CurrentPageName();
    $qProxy=new mysql_squid_builder(true);
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $GroupType=$grp ? ($grp["groupType"] ?? "") : "";
    $GroupName=$grp ? ($grp["groupName"] ?? "") : "";
    $gpid=$grp ? ($grp["params"] ?? 0) : 0;
    $item_explain=$tpl->item_explain($GroupType);
    $groupTypeText=$qProxy->acl_GroupType[$GroupType] ?? "";
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after=".jsafter_token($_GET["js-after"])."');";
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));
    $btname="{apply}";
    $html[]=$tpl->field_hidden("SaveGroup", $groupid);
    $html[]=$tpl->field_text("GroupName","{groupname}",$tpl->utf8_encode($GroupName),true);
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
    $gpid=intval($_POST["SaveGroup"]);
    if($gpid<=0){return false;}
    $GroupNameSave=utf8_decode_switch($_POST["GroupName"]);

    $ll=array();
    $ll[]="Object Name: $GroupNameSave";

    // Read existing group to compare bulkimport MD5
    $ligne=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!is_array($ligne)){$ligne=[];}
    $bulkimport_src=md5($ligne["bulkimport"] ?? "");

    $body=["name"=>$GroupNameSave];
    if(isset($_POST["params"])){
        $body["params"]=$_POST["params"];
    }
    if(isset($_POST["repblack"])){
        $body["repblack"]=intval($_POST["repblack"]);
    }
    if(isset($_POST["repwhite"])){
        $body["repwhite"]=intval($_POST["repwhite"]);
    }
    if(isset($_POST["bulkimport"])){
        $ll[]="Mass importation: {$_POST["bulkimport"]}";
        $body["bulkImport"]=$_POST["bulkimport"];
        $bulkimport_dst=md5($_POST["bulkimport"]);
        if($bulkimport_dst<>$bulkimport_src){
            acls_api_send("POST","/proxy/acls/bulk/run");
        }
    }

    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",$body);
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($data));return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
    return admin_tracks("Update ACL object ".@implode("; ",$ll));

}
function SaveGroupName(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=intval($_POST["groupid"]);
    if($gpid<=0){return false;}
    $GroupNameSave=utf8_decode_switch($_POST["GroupName"]);

    // Read original name for audit trail
    $ligneGP=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!is_array($ligneGP)){$ligneGP=[];}
    $GroupName=$ligneGP["groupName"] ?? "";

    $bulkimport=null;
    $body=["name"=>$GroupNameSave];
    if(isset($_POST["params"])){
        $body["params"]=$_POST["params"];
    }
    if(isset($_POST["bulkimport"])){
        $bulkimport="Mass importation to ".$_POST["bulkimport"];
        $body["bulkImport"]=$_POST["bulkimport"];
    }
    if(isset($_POST["description"])){
        $body["description"]=$_POST["description"];
    }

    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",$body);
    if(!$ok){echo $tpl->post_error($data);return false;}

    admin_tracks("Updating ACL Group $GroupName to $GroupNameSave and other parameters $bulkimport");

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}

function item_url_db_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GroupName=utf8_decode_switch($_POST["GroupName"]);
    $GroupID=intval($_POST["url_db"]);
    if($GroupID<=0){return false;}
    $params=base64_encode(serialize($_POST));

    // Read original name for audit trail
    $ligneGP=acls_api_get("/proxy/acls/sqlite/group/$GroupID");
    if(!is_array($ligneGP)){$ligneGP=[];}
    $GroupNameORG=$ligneGP["groupName"] ?? "";

    $body=["name"=>$GroupName,"params"=>$params];
    if(isset($_POST["description"])){
        $body["description"]=$_POST["description"];
    }

    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/group/$GroupID",$body);
    if(!$ok){echo $data;return false;}

    admin_tracks("Updating ACL Group $GroupNameORG to $GroupName and other parameters");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
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
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));
    $btname="{apply}";
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $ligne=["GroupName"=>($grp["groupName"] ?? ""),"GroupType"=>($grp["groupType"] ?? ""),"params"=>($grp["params"] ?? "")];
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after=".jsafter_token($_GET["js-after"])."');";
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
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));
    $btname="{apply}";
    $backjs="LoadAjax('fw-items-table','$page?item-add=$groupid&js-after=".jsafter_token($_GET["js-after"])."');";
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $metaBanner=fri_meta_lock_form($tpl,$groupid);
    if($metaBanner!==""){echo $metaBanner;}
    $html[]=$tpl->field_text("GroupName", "{groupname}",$tpl->utf8_encode($grp["groupName"] ?? ""));
    $item_explain=$tpl->item_explain($grp["groupType"] ?? "");
    $groupTypeText=$qProxy->acl_GroupType[$grp["groupType"] ?? ""];

    $itemsArr=acls_api_get("/proxy/acls/sqlite/items?gpid=$groupid");
    $itemRow=is_array($itemsArr) && count($itemsArr)>0 ? $itemsArr[0] : [];
    $pattern=base64_decode($itemRow["other"] ?? "");
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

    $add_danger="Loadjs('$page?category-add-dangerous-js=$groupid&js-after=".jsafter_token($_GET["js-after"])."')";
    $add_polluate="Loadjs('$page?category-add-polluate-js=$groupid&js-after=".jsafter_token($_GET["js-after"])."')";
    $add_nonproduct="Loadjs('$page?category-add-nonproductive-js=$groupid&js-after=".jsafter_token($_GET["js-after"])."')";
    $add_all="Loadjs('$page?category-add-all-js=$groupid&js-after=".jsafter_token($_GET["js-after"])."')";
    $del_button="Loadjs('$page?category-clean=$groupid&js-after=".jsafter_token($_GET["js-after"])."')";
    $opts="Loadjs('$page?change-groupname-js=$groupid&js-after=".jsafter_token($_GET["js-after"])."');";

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
    $EnableNRDS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNRDS"));
    $groupid=intval($_GET["categories-list"]);
    VERBOSE("/webfilters/categories",__LINE__);
    $cats_api=acls_api_get("/webfilters/categories");

    $main=[];
    if(is_array($cats_api)){
        foreach($cats_api as $ligne){

            $CatID=intval($ligne["categoryId"] ?? 0);
            if($CatID==0){ continue; }
            if($CatID==238 && $EnableNRDS==0){ continue; }
            $icon=$ligne["categoryIcon"] ?? null;
            if($icon==null){ $icon="img/20-categories-personnal.png"; }
            $main[$CatID]["img"]=$icon;
            $main[$CatID]["name"]=$ligne["categoryName"] ?? "";
            $main[$CatID]["free_category"]=intval($ligne["freeCategory"] ?? 0);
            $main[$CatID]["category_description"]=$tpl->utf8_encode($ligne["categoryDescription"] ?? "");
            $main[$CatID]["meta"]=intval($ligne["meta"] ?? 0);
        }
    }

    $cgard=new categories();
    $zgaurd=$cgard->CGuard_categories();
    foreach ($zgaurd as $CatID=>$catname){
        $main[$CatID]["img"]="cguard";
        $main[$CatID]["name"]=$catname;
        $main[$CatID]["free_category"]=0;
        $main[$CatID]["category_description"]="{category} CGuard $catname";
    }

    // Fetch items via API
    $items_api=acls_api_get("/proxy/acls/sqlite/items?gpid=$groupid");
    $results=is_array($items_api) ? $items_api : [];

    // L'en-tete declarait deux colonnes dont une en colspan=2, pour des lignes
    // qui en emettaient trois: l'icone, le libelle et l'action se retrouvaient
    // sous les mauvais intitules.
    // Recherche cote navigateur. Identifiant distinct de "ritcat-" (le
    // selecteur d'ajout, qui s'ouvre EN MODALE PAR-DESSUS cette liste): les
    // deux tableaux cohabitent dans le DOM.
    $table[]=awc_search_bar("ritcatlist-search","{search}");

    $table[]="<div class='awc-wrap' style='margin-top:-28px'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'><thead>
	<tr>
	<th>{categories}</th>
	<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody id='ritcatlist-rows'>
	";
    $ROWS=0;

    foreach ($results as $index=>$ligne) {
        $raw_pattern=$ligne["pattern"] ?? "";
        VERBOSE("Pattern: [$raw_pattern]",__LINE__);

        // Cleanup pass: non-numeric pattern → lookup category_id from API, fix via DELETE+INSERT
        if(!is_numeric($raw_pattern)){
            $category_id=0;
            if(is_array($cats_api)){
                foreach($cats_api as $c){
                    if(($c["categoryName"] ?? "") === $raw_pattern){
                        $category_id=intval($c["categoryId"] ?? 0);
                        break;
                    }
                }
            }
            if($category_id==0){
                // stale unknown pattern — delete it
                acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$groupid&pattern=".urlencode($raw_pattern));
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
                continue;
            }
            // Replace with numeric ID
            acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$groupid&pattern=".urlencode($raw_pattern));
            acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
                "gpid"=>$groupid,"items"=>[["pattern"=>(string)$category_id]],"mode"=>"upsert",
            ]);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
            $raw_pattern=(string)$category_id;
        }

        $id = md5($raw_pattern . microtime(true));
        $patternenc = intval(trim($raw_pattern));
        VERBOSE("Pattern: $patternenc",__LINE__);
        $category_id = $patternenc;
        if($category_id==0){continue;}
        $remote_explain=null;
        $meta=0;

        $categoryname       = htmlspecialchars_decode($main[$category_id]["name"] ?? "");
        $free_category      = intval($main[$category_id]["free_category"] ?? 0);
        $text_class         = null;
        if($free_category==1){continue;}

        if(isset($main[$category_id]["meta"])){
            $meta=intval($main[$category_id]["meta"]);
        }

        if($categoryname==null || $categoryname===""){
            if($category_id>0){$categoryname="{category} $category_id ($raw_pattern)";}
            if($category_id==0){$categoryname="<span class='text-danger'>{corrupted} {category}: <strong>$raw_pattern</strong></span>";}
        }

        if($meta==1){
            $remote_explain="&nbsp;<small>({use_remote_categories_services})</small>&nbsp;";
        }

        $ico_img="<img src='{$main[$category_id]["img"]}' alt=''>";
        if(($main[$category_id]["img"] ?? "")=="cguard"){
            $ico_img="<i class='fa-solid fa-shield-quartered'></i>";
        }
        // La cellule fermait un </small> jamais ouvert.
        $desc=$main[$category_id]["category_description"] ?? "";
        $ROWS++;
        $table[]="<tr id='$id'>";
        $table[]="<td><div class='awc-ident'>"
                ."<span class='awc-ico'>$ico_img</span>"
                ."<div><div class='awc-name'>".htmlspecialchars((string)$categoryname)."</div>"
                ."<div class='awc-meta'>$desc$remote_explain</div>"
                ."</div></div></td>";
        $table[]="<td><div class='awc-acts'>"
                .$tpl->icon_delete("Loadjs('$page?category-unlink=$patternenc&gpid=$groupid&md=$id')","AsDansGuardianAdministrator")
                ."</div></td>";
        $table[]="</tr>";
    }
    $table[]="</tbody></table></div>";
    $table[]="<div class='awc-foot'><span>".$tpl->FormatNumber($ROWS)." {categories}</span>"
            ."<span class='awc-pg' id='ritcatlist-pager'></span></div>";
    $table[]="</div></div>";
    // CSS et JS se concatenent AUTOUR de _ENGINE_parse_body(): sa regex
    // #{(.+?)}# lirait chaque corps de regle CSS et chaque bloc JS comme un
    // {token}. Le filtre vient APRES le pager, qui lui passe la main par
    // tb.__awcRedraw.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $table))
        ."<script>NoSpinner()</script>"
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ritcatlist-rows","ritcatlist-pager")
        .awc_filter_js("ritcatlist-search","ritcatlist-rows","ritcatlist-pager");
    return true;
}

function category_unlink(){
    $groupid = intval($_GET["gpid"]);
    $category = $_GET["category-unlink"];
    $md = $_GET["md"];

    header("content-type: application/x-javascript");
    if($groupid<=0 || !$category){ return; }
    list($ok,$data)=acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$groupid&pattern=".urlencode($category));
    if(!$ok){
        $tpl=new template_admin();
        echo "alert('".addslashes(strip_tags($data))."');";
        return;
    }
    admin_tracks("Unlink category $category from ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    echo "$('#$md').remove();\n";
}

function categories_select_all(){
    header("content-type: application/x-javascript");
    $groupid = intval($_GET["category-post-all"]);
    if($groupid<=0){return;}
    $cats = acls_api_get("/webfilters/categories");
    if(!is_array($cats)){ echo "alert('categories list unavailable');"; return; }
    $items = [];
    foreach($cats as $c){
        if(intval($c["freeCategory"] ?? 0) != 0){ continue; }
        $cid = $c["categoryId"] ?? "";
        if($cid === ""){ continue; }
        $items[] = ["pattern"=>(string)$cid];
    }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid, "items"=>$items, "mode"=>"replace_all",
    ]);
    if(!$ok){
        $tpl=new template_admin();
        echo "alert('".addslashes(strip_tags($data))."');";
        return;
    }
    admin_tracks("Replace all categories with free-category set in ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    $page=CurrentPageName();
    echo "LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');\n";
    echo "dialogInstance2.close();\n";
}

function category_post_js(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $groupid = intval($_GET["ID"]);
    $category = $_GET["category-post-js"];
    $md = $_GET["md"];

    if($groupid<=0 || !$category){ return; }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid, "items"=>[["pattern"=>(string)$category]], "mode"=>"upsert",
    ]);
    if(!$ok){
        $tpl=new template_admin();
        echo "alert('".addslashes(strip_tags($data))."');";
        return;
    }
    admin_tracks("Add category $category to ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    echo "LoadAjax('categories-list-$groupid','$page?categories-list=$groupid');\n";
    echo "$('#$md').remove();\n";
}

function categories_popup(){
    include_once(dirname(__FILE__)."/ressources/class.categories.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["add-categories-popup"]);
    $Ccategories=new categories();
    $EnableNRDS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNRDS"));

    // Build ALREADY set from API items
    $items_api=acls_api_get("/proxy/acls/sqlite/items?gpid=$groupid");
    if(is_array($items_api)){
        foreach($items_api as $ligne){
            $ALREADY[intval($ligne["pattern"] ?? 0)]=true;
        }
    }

    // Fetch all categories from API
    $cats_api=acls_api_get("/webfilters/categories");

    $TRCLASS=null;
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?category-post-all=$groupid');\">";
    $html[]="<i class='fa fa-plus'></i> {select_all} </label>";
    $html[]="</div>";

    // Recherche cote navigateur: la liste porte toutes les categories du
    // produit, et ce dialogue sert justement a EN TROUVER une. awc_filter_js()
    // compare le texte de la ligne entiere -- nom comme description -- et il
    // est emis en fin de fonction, APRES le pager.
    $html[]=awc_search_bar("ritcat-search","{search}");

    $html[]="<div class='awc-wrap' style='margin-top:-28px'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>{category}</th>";
    $html[]="<th>{description}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ritcat-rows'>";
    $ROWS=0;
    if(is_array($cats_api)) foreach($cats_api as $ligne){
        $categoryname_raw=$ligne["categoryName"] ?? "";
        if(preg_match("#^reserved#",$categoryname_raw)){continue;}
        $icon=$ligne["categoryIcon"] ?? null;
        if($icon==null){$icon="img/20-categories-personnal.png";}
        $category_id=$ligne["categoryId"] ?? "";
        if($category_id===""){ continue; }
        if(isset($ALREADY[intval($category_id)])){continue;}
        $meta=intval($ligne["meta"] ?? 0);
        $license=null;$remote_explain=null;
        $img="{$icon}";
        $md=md5($category_id.$categoryname_raw);
        $category_description=$tpl->utf8_encode($ligne["categoryDescription"] ?? "");
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
        $TRCLASS=null;
        $ROWS++;
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><img src='$img' alt='none'></td>";
        $html[]="<td $styleText nowrap style='width:1%'>".$tpl->_ENGINE_parse_body("{$categoryname_raw}")."</td>";
        $html[]="<td $styleText style='width:99%;'>".$tpl->_ENGINE_parse_body("{$category_description}$remote_explain$license")."</td>";
        $html[]=$tpl->_ENGINE_parse_body("<td style='width:1%' nowrap>$button</td>");
        $html[]="</tr>";

    }
    $TheShieldsCguard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsCguard"));
    if($TheShieldsCguard==1) {
        $cguardico = "<i class='fa-solid fa-shield-quartered'></i>";
        $CGuard_categories = $Ccategories->CGuard_categories();
        foreach ($CGuard_categories as $category_id => $categoryname) {
            // Le garde testait $ligne["category_id"]: $ligne est la variable
            // RESIDUELLE de la boucle precedente (ses clefs sont "categoryId",
            // pas "category_id"), donc la condition ne partait jamais et une
            // categorie CGuard deja dans le groupe etait reproposee. Pire, si
            // /webfilters/categories n'a rien rendu, $ligne n'existe meme pas
            // -- "Undefined variable" sous PHP 8.
            if (isset($ALREADY[intval($category_id)])) {
                continue;
            }
            $TRCLASS=null;
            $ROWS++;

            $md = md5("$category_id$categoryname");
            $js = "Loadjs('$page?category-post-js=$category_id&ID=$groupid&md=$md')";
            $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
            $html[] = "<tr class='$TRCLASS' id='$md'>";
            $html[] = "<td style='width:1%'>$cguardico</td>";
            $html[] = "<td nowrap style='width:1%'>$categoryname</td>";
            $html[] = "<td style='width:99%'>$categoryname</td>";
            $html[] = $tpl->_ENGINE_parse_body("<td style='width:1%' nowrap>$button</td>");
            $html[] = "</tr>";

        }
    }
    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div>";
    $html[]="<div class='awc-foot'><span>".$tpl->FormatNumber($ROWS)." {categories}</span>"
           ."<span class='awc-pg' id='ritcat-pager'></span></div>";
    $html[]="</div></div>";
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
";
    $html[]="</script>";

    // CSS et JS se concatenent AUTOUR de _ENGINE_parse_body(): sa regex
    // #{(.+?)}# lirait chaque corps de regle CSS et chaque bloc JS comme un
    // {token}. Le filtre vient APRES le pager, qui lui passe la main par
    // tb.__awcRedraw.
    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ritcat-rows","ritcat-pager")
	    .awc_filter_js("ritcat-search","ritcat-rows","ritcat-pager");



}


function time_save(){

    $tpl=new template_admin();
    $ID=intval($_POST["SaveTime"]);
    if($ID<=0){return false;}

    $GroupNameSave=url_decode_special_tool($_POST["GroupName"]);
    $GroupNameSave=utf8_decode_switch($GroupNameSave);

    // Validate hour range before writing anything
    $H1=$_POST["H1"];
    $H2=$_POST["H2"];
    $H1T=strtotime(date("Y-m-d $H1:00"));
    $H2T=strtotime(date("Y-m-d $H2:00"));
    if($H2T<$H1T){
        $tpl2=new templates();
        echo $tpl2->javascript_parse_text("{ERROR_SQUID_TIME_ACL}");
        return false;
    }

    // (a) Update group name / description via PUT /group/{id}
    $groupBody=["name"=>$GroupNameSave];
    if(isset($_POST["description"])){
        $groupBody["description"]=trim(url_decode_special_tool($_POST["description"]));
    }
    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/group/$ID",$groupBody);
    if(!$ok){echo $tpl->_ENGINE_parse_body($data);return false;}

    // (b) Upsert single NONE item carrying the serialized time config
    $pattern=base64_encode(serialize($_POST));
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$ID,
        "mode"=>"upsert",
        "items"=>[["pattern"=>"NONE","enabled"=>1,"other"=>$pattern]],
    ]);
    if(!$ok){echo $tpl->_ENGINE_parse_body($data);return false;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
    return true;
}


function item_add_popup(){
    $tpl=new template_admin();
    $groupid=intval($_GET["item-add"]);
    $jsafter=jsafter_sanitize(base64_decode($_GET["js-after"]));
    $qProxy=new mysql_squid_builder(true);
    $page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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

    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    if(!$grp){ echo $tpl->_ENGINE_parse_body($tpl->div_error("group $groupid not found")); return; }
    $metaBanner=fri_meta_lock_form($tpl,$groupid);
    if($metaBanner!==""){echo $metaBanner;}
    $ligne=["GroupName"=>$grp["groupName"],"GroupType"=>$grp["groupType"],
            "params"=>$grp["params"],"bulkimport"=>($grp["bulkImport"] ?? ""),"description"=>$grp["description"]];
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
        $nets=acls_api_get("/interfaces/networks");
        $tt=[];
        if(is_array($nets)){
            foreach($nets as $net){ $tt[]=is_array($net) ? ($net["ipaddr"] ?? "") : ""; }
        }
        $item_explain=@implode(", ",$tt);
    }

    // pattern list previously fed a commented-out textarea; no longer needed
    $pp=[];





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
    $gpid=intval($_POST["weekrange_gpid"]);
    if($gpid<=0){return false;}
    $tpl=new template_admin();

    // Read existing params blob, merge in the new TIME key, re-encode
    $ligne=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!is_array($ligne)){$ligne=[];}
    $params=unserialize(base64_decode($ligne["params"] ?? ""));
    $params["TIME"]=unserialize(base64_decode($_POST["weekrange_time"]),array("allowed_classes"=>false));
    $newval=base64_encode(serialize($params));

    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",["params"=>$newval]);
    if(!$ok){echo $tpl->_ENGINE_parse_body($data);return false;}
    admin_tracks("Save weekday range for ACL group $gpid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/invalidates");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
}

function ndpi_choose(){
    //ndpi-choose=$strlow&gpid=$groupid&md=$md&js-after=$jsafter&function=$function
    $groupid=intval($_GET["gpid"]);
    $md=$_GET["md"];
    // Pentest 2026-11-04 : jsafter etait decode puis renvoye comme JavaScript.
    $jsafter=jsafter_sanitize(base64_decode($_GET["jsafter"] ?? ""));
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $protocol=$_GET["ndpi-choose"];
    if($groupid<=0 || !$protocol){ echo "invalid params"; return; }
    $tpl=new template_admin();
    $uid=$_SESSION["uid"] ?? "Manager";
    if($uid==-100){$uid="Manager";}
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items",[
        "gpid"=>$groupid,
        "items"=>[["pattern"=>$protocol,"uid"=>$uid,"description"=>"Added by $uid"]],
    ]);
    if(!$ok){ echo $tpl->_ENGINE_parse_body($data); return; }
    admin_tracks("Add nDPI protocol $protocol to group $groupid");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    if($function<>null){
        echo "$function();\n";
    }
    if($jsafter<>null){
        echo $jsafter."\n";
    }
}

function item_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $gpid=intval($_POST["groupid"]);
    if($gpid<=0){echo "invalid gpid"; return false;}

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!is_array($grp)){echo "group not found"; return false;}
    $GroupType=$grp["groupType"] ?? "";

    // Save GroupName (and optional description) via API
    $GroupNameSave=url_decode_special_tool($_POST["GroupName"] ?? "");
    $GroupNameSave=utf8_decode_switch($GroupNameSave);
    $updateFields=["GroupName"=>$GroupNameSave];
    if(isset($_POST["description"])){
        $updateFields["description"]=$_POST["description"];
    }
    list($okG,$dataG)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",$updateFields);
    if(!$okG){echo $tpl->_ENGINE_parse_body($dataG); return false;}

    $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
    if($EnableSuricata==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/acls/explains");
    }

    if(!isset($_POST["pattern"])){return false;}
    $pattern=url_decode_special_tool($_POST["pattern"])."\n";
    $f=explode("\n",$pattern);

    $IPClass=new IP();
    $items=[];
    foreach($f as $line){
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
            if(preg_match("#^(http|https|ftp|ftps):\/#",$line)){$arrM=parse_url($line);$line=$arrM["host"];}
            if(strpos($line,"/")>0){$arrM=explode("/",$line);$line=$arrM[0];}
            if(strpos(" $line","^")==0){
                $squidfam=new squid_familysite();
                $fam=$squidfam->GetFamilySites($line);
                if($fam<>$line){$line="^$line";}
            }
        }

        $items[]=["pattern"=>$line,"enabled"=>1];
    }

    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$gpid,"items"=>$items,"mode"=>"replace_all",
    ]);
    if(!$ok){echo $tpl->_ENGINE_parse_body($data); return false;}
    admin_tracks("Replace all items in ACL group $gpid ({$grp["groupName"]}) — ".count($items)." items");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
    return true;
}

function countries_dselectall(){
    header("content-type: application/x-javascript");
    $groupid = intval($_GET["countries-deselectall-js"]);
    if($groupid<=0){return;}
    list($ok,$data)=acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$groupid");
    if(!$ok){echo "alert('".addslashes($data)."');";return;}
    admin_tracks("Deselect all countries in ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    // La reponse d'un Loadjs est evaluee comme du JS: sans ce rechargement du
    // fragment, les cases restent inchangees et le bouton "semble" ne rien faire.
    countries_reload_js($groupid);
}

function countries_selectall(){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    header("content-type: application/x-javascript");
    $groupid=intval($_GET["countries-selectall-js"]);
    if($groupid<=0){return;}
    $GEO_IP_COUNTRIES_LIST=GEO_IP_COUNTRIES_LIST();
    $items=[];
    foreach($GEO_IP_COUNTRIES_LIST as $countryCode=>$CountryName){
        if(!preg_match("#^[A-Z]+$#",$countryCode)){continue;}
        $items[]=["pattern"=>$countryCode];
    }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid,"items"=>$items,"mode"=>"replace_all",
    ]);
    if(!$ok){echo "alert('".addslashes($data)."');";return;}
    admin_tracks("Select all countries in ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    // La reponse d'un Loadjs est evaluee comme du JS: sans ce rechargement du
    // fragment, les cases restent inchangees et le bouton "semble" ne rien faire.
    countries_reload_js($groupid);
}

// Recharge le fragment de la table des pays (div table-acls-items-<gpid>) apres
// une mutation de masse, en propageant js-after (jeton base64) et function
// (nom de fonction, liste blanche car interpole dans du JS).
function countries_reload_js($groupid){
    $page=CurrentPageName();
    $jsafter=rawurlencode(jsafter_token($_GET["js-after"] ?? ""));
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    echo "LoadAjax('table-acls-items-$groupid','$page?countries-search=$groupid&js-after=$jsafter&function=$function');\n";
}

function spamc_val(){
    $groupid=intval($_GET["gpid"]);
    $val=$_GET["spmac-val"];
    if($groupid<=0||!$val){return;}
    $existing=acls_api_get("/proxy/acls/sqlite/items?gpid=$groupid&search=".urlencode($val));
    $found=false;
    if(is_array($existing)){
        foreach($existing as $e){
            $pat=isset($e["pattern"])?$e["pattern"]:"";
            if($pat===$val){$found=true;break;}
        }
    }
    if($found){
        list($ok,$data)=acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$groupid&pattern=".urlencode($val));
    }else{
        list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
            "gpid"=>$groupid,"items"=>[["pattern"=>$val]],"mode"=>"upsert",
        ]);
    }
    if(!$ok){echo "alert('".addslashes($data)."');";return;}
    admin_tracks(($found?"Remove":"Add")." spamc value $val in ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
}
function countries_enable(){
    $groupid=intval($_GET["gpid"]);
    $country=$_GET["countries-enable"];
    if($groupid<=0||!$country){return;}
    $existing=acls_api_get("/proxy/acls/sqlite/items?gpid=$groupid&search=".urlencode($country));
    $found=false;
    if(is_array($existing)){
        foreach($existing as $e){
            $pat=isset($e["pattern"])?$e["pattern"]:"";
            if($pat===$country){$found=true;break;}
        }
    }
    if($found){
        list($ok,$data)=acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$groupid&pattern=".urlencode($country));
    }else{
        list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
            "gpid"=>$groupid,"items"=>[["pattern"=>$country]],"mode"=>"upsert",
        ]);
    }
    if(!$ok){echo "alert('".addslashes($data)."');";return;}
    admin_tracks(($found?"Disable":"Enable")." country $country in ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
}

function spamc_search()
{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=intval($_GET["spamc-search"]);
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $t=time();
    $TRCLASS=null;

    $items_api=acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
    $CN=null;
    if(is_array($items_api)){
        foreach($items_api as $ligne){
            $CN=$ligne["pattern"] ?? null;
        }
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
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $t=time();
    $TRCLASS=null;
    $DISPLAY_RECORDS=true;$CHECKDB=false;
    $colspan=4;
    if($gpid==0){
        echo $tpl->div_error("Wrong group id");
        return false;
    }

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    $GroupType=$grp ? ($grp["groupType"] ?? "") : "";
    if($GroupType=="geoip"){
        $colspan=3;
        $DISPLAY_RECORDS=false;
        $CHECKDB=true;
    }

    $items_api=acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
    $ADDED=[];
    if(is_array($items_api)){
        foreach($items_api as $ligne){
            $CN=$ligne["pattern"] ?? "";
            if($CN !== ""){ $ADDED[$CN]=1; }
        }
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
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>{country}</th>";
    if($DISPLAY_RECORDS) {
        $html[] = "<th>{items}</th>";
    }
    $html[]="<th class='center'>{selected}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ritems1-rows'>";

    // Pre-fetch country geo item counts via API (only when DISPLAY_RECORDS is needed)
    $GEO_ITEMS_MAP=[];
    if($DISPLAY_RECORDS){
        $geo_api=acls_api_get("/webfilters/countries-geo");
        if(is_array($geo_api)){
            foreach($geo_api as $geo){
                $cc=$geo["country"] ?? "";
                if($cc !== ""){ $GEO_ITEMS_MAP[$cc]=$geo["items"] ?? 0; }
            }
        }
    }

    foreach ($GEO_IP_COUNTRIES_LIST as $countryCode=>$CountryName){
        if(!preg_match("#^[A-Z]+$#",$countryCode)){continue;}
        $TRCLASS=null;
        $md=md5($countryCode.$CountryName);
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
            $items_count=FormatNumber($GEO_ITEMS_MAP[$countryCode] ?? 0);
            $html[] = "<td  nowrap style='width:1%;text-align: right'>$items_count</td>";
        }
        $html[]="<td  style='width:1%' nowrap class='center'>$enable</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='ritems1-pager'></span></div></div></div>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ritems1-rows","ritems1-pager");
    return true;
}

function server_cert_fingerprint_save(){
    include_once(dirname(__FILE__)."/ressources/class.certificate_parser.inc");
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $hostname=trim(strtolower($_POST["fingerprint-hostname"]));
    $port=intval($_POST["fingerprint-port"]);
    $gpid=intval($_POST["new-item-fingerprint"]);
    if($gpid<=0){return false;}
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

    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items",[
        "gpid"=>$gpid,
        "items"=>[["pattern"=>$Fingerprint,"uid"=>$uid,"description"=>$description,"enabled"=>1,"zdate"=>$zdate]],
    ]);
    if(!$ok){echo $tpl->post_error($data);return false;}
    admin_tracks("Adding a new SSL fingerprint $Fingerprint ($description) in Proxy Object #$gpid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
    return true;
}
function new_item_header($gpid,$GroupName):bool{
    $tpl=new template_admin();
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $html[]=$tpl->field_hidden("new-item-header-save", $gpid);
    $html[]=$tpl->field_text("header","{header}","",true);
    $html[]=$tpl->field_text("value","{value}","",false);
    if($jsafter<>null) {
        // Pentest 2026-11-04 : js-after etait decode puis execute sans validation (XSS).
        $js[] = jsafter_sanitize(base64_decode($jsafter));
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
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));


    $html[]=$tpl->field_hidden("new-item-save", $gpid);
    $html[]=$tpl->field_array_hash($GEO_IP_COUNTRIES_LIST,"pattern","{countryName}");

    if($jsafter<>null) {
        // Pentest 2026-11-04 : js-after etait decode puis execute sans validation (XSS).
        $js[] = jsafter_sanitize(base64_decode($jsafter));
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
    $tpl=new template_admin();
    $gpid=$_GET["new-item-popup"];
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $grp=acls_api_get("/proxy/acls/sqlite/group/".intval($gpid));
    $groupName=$grp["groupName"] ?? "";
    $groupType=$grp["groupType"] ?? "";
    $item_explain="{$groupType}:".$tpl->item_explain($groupType);
    $btname="{add}";

    $tpl->field_hidden("new-item-fingerprint", $gpid);
    $form[]=$tpl->field_text("fingerprint-hostname","{hostname}",null,true);
    $form[]=$tpl->field_numeric("fingerprint-port","{port}","443",true);
    $form[]=$tpl->field_text("fingerprint-desc","{description}",null,false);

    // Pentest 2026-11-04 : js-after etait decode puis execute sans validation (XSS).
    $js[]=jsafter_sanitize(base64_decode($jsafter));
    $js[]="$function()";
    $js[]="dialogInstance7.close()";
    if($function<>null){
        $js[]="$function()";
    }
    $js[]="Loadjs('fw.acls.filltable.php')";
    $backjs=@implode(";", $js);

    echo $tpl->form_outside($tpl->utf8_encode($groupName),@implode("\n", $form),$item_explain,$btname,"$backjs");
}
function new_item_popup():bool{
    //new-item-popup=$groupid&js-after=$jsafter&function=$function
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_search="";
    $gpid=$_GET["new-item-popup"];
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(isset($_GET["function-search"])) {
        $function_search = $_GET["function-search"];
    }
    $RefreshFunction=$_GET["RefreshFunction"];
    $filltable="Loadjs('fw.acls.filltable.php')";
    $TableLink="";
    $TableLinkUri="";
    if(isset($_GET["TableLink"])){
        $TableLinkUri="&TableLink={$_GET["TableLink"]}";
        $TableLink=$_GET["TableLink"];
    }
    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!$grp){ echo $tpl->_ENGINE_parse_body($tpl->div_error("group $gpid not found")); return false; }
    $ligne=["GroupName"=>$grp["groupName"],"GroupType"=>$grp["groupType"]];

    VERBOSE("Gpid: $gpid | GroupeType = {$ligne["GroupType"]}",__LINE__);

    if($ligne["GroupType"]=="accessrule"){
        echo "<div id='acls-item-choose-main-accessrule-$gpid'></div>";
        echo "<script>LoadAjax('acls-item-choose-main-accessrule-$gpid','fw.rules.items.accessrules.php?gpid=$gpid&page=yes&function2=$function$TableLinkUri');</script>";
        return true;
    }

    if($ligne["GroupType"]=="adfrom"){
        $URLADDON=URL_ADDONS();
        echo "<div id='acls-item-choose-main-ad-$gpid'></div>";
        echo "<script>LoadAjax('acls-item-choose-main-ad-$gpid','fw.rules.items.activedirectory.php?gpid=$gpid&$URLADDON$TableLinkUri');</script>";
        return true;
    }
    if($ligne["GroupType"]=="adto"){
        $URLADDON=URL_ADDONS();
        echo "<div id='acls-item-choose-main-ad-$gpid'></div>";
        echo "<script>LoadAjax('acls-item-choose-main-ad-$gpid','fw.rules.items.activedirectory.php?gpid=$gpid&$URLADDON$TableLinkUri');</script>";
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
        // Pentest 2026-11-04 : js-after etait decode puis execute sans validation (XSS).
        $js[] = jsafter_sanitize(base64_decode($jsafter));
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
    if(!is_null($RefreshFunction)) {
        if (strlen($RefreshFunction) > 2) {
            // Pentest 2026-11-04 : RefreshFunction etait decode puis execute sans validation (XSS).
            $js[] = jsafter_sanitize(base64_decode($RefreshFunction));
        }
    }

    if($TableLink=="suricata_sqacllinks"){
        $filltable="";
    }
    if(strlen($filltable)>1) {
        $js[] = "Loadjs('fw.acls.filltable.php')";
    }
    $backjs=@implode(";", $js);

    echo $tpl->form_outside($tpl->utf8_encode($ligne["GroupName"]),@implode("\n", $html),$item_explain,$btname,"$backjs");
    return true;
}
function item_enable():bool{
    $ID=intval($_GET["item-enable"]);
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if($ID<=0){ echo "alert('invalid id');"; return false; }
    if(!ritm_can_write()){ echo "alert(".json_encode($tpl->javascript_parse_text("{ERROR_NO_PRIVS2}")).");"; return false; }
    $it=acls_api_get("/proxy/acls/sqlite/items/$ID");
    if(!$it){ echo "alert('item $ID not found');"; return false; }
    if(proxyobject_meta_held(intval($it["gpid"] ?? 0))){ echo "alert(".json_encode($tpl->javascript_parse_text("{ritm_meta_locked}")).");"; return false; }
    $new=intval($it["enabled"])==1 ? 0 : 1;
    list($ok,$data)=acls_api_send("PUT","/proxy/acls/sqlite/items/$ID",["enabled"=>$new]);
    if(!$ok){ echo "alert(".json_encode((string)$data).");"; return false; }
    $pattern=$it["pattern"] ?? "item#$ID";
    admin_tracks("ACL record $pattern was enabled to $new");
    $on=$new==1?"true":"false";
    $off=$new==1?"false":"true";
    echo "(function(){var s=document.getElementById('ritm-sw-$ID');if(!s){return;}s.setAttribute('aria-checked','$on');var r=s.closest('tr');if(r){r.classList.toggle('ritm-off',$off);}})();\n";
    return true;
}
function item_delete():bool{
    $ID=intval($_GET["item-delete"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $fn=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if($ID<=0){ echo "alert('invalid id');"; return false; }
    if(!ritm_can_write()){ echo "alert(".json_encode($tpl->javascript_parse_text("{ERROR_NO_PRIVS2}")).");"; return false; }
    $it=acls_api_get("/proxy/acls/sqlite/items/$ID");
    if(!$it){ echo "alert('item $ID not found');"; return false; }
    if(proxyobject_meta_held(intval($it["gpid"] ?? 0))){ echo "alert(".json_encode($tpl->javascript_parse_text("{ritm_meta_locked}")).");"; return false; }
    $after="if(document.getElementById('$md')){\$('#$md').remove();}";
    if($fn!==""){ $after.="if(typeof $fn==='function'){ $fn(); }"; }
    // Plus de suppression au premier clic : confirmation, puis POST ritm-delete.
    $tpl->js_confirm_delete(htmlspecialchars((string)($it["pattern"] ?? "#$ID"),ENT_QUOTES),"ritm-delete",$ID,$after);
    return true;
}
function ritm_delete_save():bool{
    $tpl=new template_admin();
    if(!ritm_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}"); return false; }
    $ID=intval($_POST["ritm-delete"]);
    if($ID<=0){ echo $tpl->post_error("invalid id"); return false; }
    $it=acls_api_get("/proxy/acls/sqlite/items/$ID");
    if(!$it){ echo $tpl->post_error("item $ID not found"); return false; }
    if(proxyobject_meta_held(intval($it["gpid"] ?? 0))){ echo $tpl->post_error("{ritm_meta_locked}"); return false; }
    list($ok,$data)=acls_api_send("DELETE","/proxy/acls/sqlite/items/$ID");
    if(!$ok){ echo $tpl->post_error((string)$data); return false; }
    $pattern=$it["pattern"] ?? "item#$ID";
    admin_tracks("Delete item $pattern (id $ID) from object #".intval($it["gpid"] ?? 0));
    return true;
}
function utf8_decode_switch($value):string{
    if(is_null($value)){
        return "";
    }
    if(PHP_MAJOR_VERSION>7) {
        return $value;
    }
    $tpl=new template_admin();
    return $tpl->utf8_decode($value);
}
function new_item_header_save():bool{
    $tpl=new template_admin();
    $gpid=intval($_POST["new-item-header-save"]);
    $header=trim($_POST["header"] ?? "");
    $value=trim($_POST["value"] ?? "");
    if(!$header){ echo "header required"; return false; }
    $pattern="$header:$value";
    $uid=$_SESSION["uid"] ?? "Manager";
    if($uid==-100){$uid="Manager";}
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items",[
        "gpid"=>$gpid,
        "items"=>[["pattern"=>$pattern,"uid"=>$uid,"description"=>"Added by $uid"]],
    ]);
    if(!$ok){ echo $tpl->_ENGINE_parse_body($data); return false; }
    admin_tracks("Add header rule $pattern to group $gpid");
    return true;
}

function new_item_save(){
    $gpid=intval($_POST["new-item-save"]);
    $tpl=new template_admin();
    $GroupInfo=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    $GroupType=$GroupInfo ? ($GroupInfo["groupType"] ?? "") : "";
    $GroupName=$GroupInfo ? ($GroupInfo["groupName"] ?? "") : "";
    $pattern=url_decode_special_tool($_POST["pattern"])."\n";
    $f=explode("\n",$pattern);
    $uid=$_SESSION["uid"] ?? "Manager";
    if($uid==-100){$uid="Manager";}
    $AdminTrack=array();

    $IPClass=new IP();
    $ITEMS=array();
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
                // bulkimport path: framework script reads bulkimport column, parses,
                // and bulk-inserts via its own pipeline. We just set the column +
                // wipe prior items then hand off to the framework.
                list($ok2,)=acls_api_send("PUT","/proxy/acls/sqlite/group/$gpid",["bulkImport"=>$re[1]]);
                if($ok2){
                    acls_api_send("DELETE","/proxy/acls/sqlite/items?gpid=$gpid");
                }
                acls_api_send("POST","/proxy/acls/bulk/run");
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

        $ITEMS[]= ["pattern"=>$line, "uid"=>$uid, "description"=>"Added by $uid", "enabled"=>1];
        $AdminTrack[]="Add $line record into $GroupName ACL";
    }

    if(count($ITEMS)==0){
        echo (new template_admin())->post_error("no data, because:".@implode(", ",$LOGS));
        return false;
    }

    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items",["gpid"=>$gpid,"items"=>$ITEMS]);
    if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text($data));return false;}
    foreach ($AdminTrack as $line) {admin_tracks($line);}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/lb/acls/sync");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/replic");
    return true;
}
// --- Onglet Items d'un objet (portage Stitch, prefixe ritm_) ---------------------
// En-tete de l'objet (type en clair, phrase d'effet, formats acceptes = item_explain(),
// compteurs sur TOUTE la liste), puis le tableau pagine cote serveur. L'en-tete est injecte
// par item_table() dans un hote pose par item_search() au-dessus de la recherche : il suit
// chaque ajout/suppression puisque la table est rechargee.

// Ecriture : les objets servent au proxy, au pare-feu DNS et au pare-feu.
function ritm_can_write():bool{
    $users=new usersMenus();
    return (bool)($users->AsDansGuardianAdministrator || $users->AsSquidAdministrator || $users->AsDnsAdministrator || $users->AsFirewallManager);
}

function ritm_h($v):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars((string)$v,ENT_QUOTES));
}

function ritm_t(string $token,$value):string{
    $tpl=new template_admin();
    return str_replace("%s",(string)$value,$tpl->_ENGINE_parse_body($token));
}

function ritm_counts(int $gpid,array $rows,bool $isFull):array{
    if(!$isFull){
        $rows=acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid&search=".urlencode("*"));
        if(!is_array($rows)){ return array("total"=>null,"enabled"=>null,"disabled"=>null); }
    }
    $c=array("total"=>0,"enabled"=>0,"disabled"=>0);
    foreach($rows as $r){
        $c["total"]++;
        if(intval($r["enabled"] ?? 1)==1){ $c["enabled"]++; }else{ $c["disabled"]++; }
    }
    return $c;
}

function ritm_num($v):string{
    if($v===null){ return "&mdash;"; }
    return number_format($v,0,".",",");
}

// Libelle en clair et icone FA6 des types courants ; repli sur les tables historiques.
function ritm_type_info(string $type):array{
    $map=array(
        "src"=>array("fa-solid fa-network-wired","{ritm_type_src}"),
        "dst"=>array("fa-solid fa-location-dot","{ritm_type_dst}"),
        "dstdomain"=>array("fa-solid fa-globe","{ritm_type_dstdomain}"),
        "arp"=>array("fa-solid fa-ethernet","{ritm_type_arp}"),
        "proxy_auth"=>array("fa-solid fa-user","{ritm_type_proxy_auth}"),
        "port"=>array("fa-solid fa-plug","{ritm_type_port}"),
        "ssl_sni"=>array("fa-solid fa-lock","{ritm_type_ssl_sni}"),
        "fqdn"=>array("fa-solid fa-server","{ritm_type_fqdn}"),
        "geoipdest"=>array("fa-solid fa-flag","{ritm_type_geoipdest}"),
        "geoipsrc"=>array("fa-solid fa-flag","{ritm_type_geoipsrc}"),
        "header"=>array("fa-solid fa-heading","{ritm_type_header}"),
        "server_cert_fingerprint"=>array("fa-solid fa-certificate","{ritm_type_fingerprint}"),
        "url_regex_extensions"=>array("fa-solid fa-file","{ritm_type_extensions}"),
    );
    if(isset($map[$type])){ return $map[$type]; }
    $qlprx=new mysql_squid_builder();
    $ico=isset($qlprx->acl_GroupTypeIcon[$type])?$qlprx->acl_GroupTypeIcon[$type]:"fa-solid fa-layer-group";
    $label=isset($qlprx->acl_GroupType[$type])?$qlprx->acl_GroupType[$type]:"";
    return array($ico,$label);
}

function ritm_header(array $grp,array $c,bool $write,bool $held,string $newjs):string{
    $tpl=new template_admin();
    $type=(string)($grp["groupType"] ?? "");
    list($ico,$label)=ritm_type_info($type);
    $pill=$label!==""?"<span class='ritm-type'>$label</span>":"";
    $pill.="<span class='ritm-code' title='{ritm_type_code}'>".ritm_h($type)."</span>";
    $explain=trim((string)$tpl->item_explain($type));
    $formats="";
    if($explain!==""){
        $formats="<details class='ritm-formats'><summary><i class='fa-solid fa-circle-info'></i> {ritm_formats}</summary><div class='ritm-formats-b'>$explain<div class='ritm-hint'>{ritm_search_hint}</div></div></details>";
    }else{
        $formats="<div class='ritm-hint'>{ritm_search_hint}</div>";
    }
    $dis=$c["disabled"]!==null && $c["disabled"]>0?" ritm-cnt-warn":"";
    if($held){
        $btn="<span title='{ritm_meta_locked}'><button type='button' class='ak-btn ak-btn-primary ak-btn-sm' disabled><i class='fa-solid fa-lock'></i> {new_item}</button></span>";
    }elseif($write){
        $btn="<button type='button' class='ak-btn ak-btn-primary ak-btn-sm' OnClick=\"$newjs\"><i class='fa-solid fa-plus'></i> {new_item}</button>";
    }else{
        $btn="<span title='{ritm_no_privs}'><button type='button' class='ak-btn ak-btn-sm' disabled><i class='fa-solid fa-lock'></i> {new_item}</button></span>";
    }
    return "<div class='ritm-head'>"
        ."<div class='ritm-head-top'><span class='ritm-ico'><i class='$ico'></i></span>"
        ."<div class='ritm-head-t'><div class='ritm-title'><span class='ritm-name'>".ritm_h($grp["groupName"] ?? "")."</span>$pill</div>"
        ."<div class='ritm-sub'>{ritm_effect}</div></div>"
        ."<div class='ritm-cnts'><div class='ritm-cnt'><b>".ritm_num($c["total"])."</b><span>{ritm_items}</span></div>"
        ."<div class='ritm-cnt ritm-cnt-ok'><b>".ritm_num($c["enabled"])."</b><span>{ritm_enabled}</span></div>"
        ."<div class='ritm-cnt$dis'><b>".ritm_num($c["disabled"])."</b><span>{ritm_disabled}</span></div></div>"
        ."<div class='ritm-head-b'>$btn</div></div>"
        .$formats."</div>";
}

function ritm_style():string{
    if(isset($GLOBALS["RITM_STYLE"])){ return ""; }
    $GLOBALS["RITM_STYLE"]=true;
    return "<style>
.ritm-head{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;margin:10px 0 12px 0;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.ritm-head-top{display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap}
.ritm-ico{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;flex:0 0 40px;border-radius:10px;background:#f0fdfa;border:1px solid #99f6e4;color:#0f766e;font-size:17px}
.ritm-head-t{flex:1 1 300px;min-width:0}
.ritm-title{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.ritm-name{font-size:15px;font-weight:600;color:#0f172a;overflow-wrap:anywhere}
.ritm-type{display:inline-flex;align-items:center;padding:1px 8px;border-radius:6px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:600}
.ritm-code{display:inline-flex;padding:1px 7px;border-radius:6px;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12px}
.ritm-sub{font-size:13px;color:#64748b;margin-top:4px;line-height:1.45}
.ritm-cnts{display:flex;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;padding:6px 4px}
.ritm-cnt{display:flex;flex-direction:column;align-items:center;min-width:72px;padding:0 10px;border-left:1px solid #e5e7eb}
.ritm-cnt:first-child{border-left:0}
.ritm-cnt b{font-size:17px;color:#0f172a;font-variant-numeric:tabular-nums}
.ritm-cnt span{font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.ritm-cnt-ok b{color:#0f766e}
.ritm-cnt-warn b,.ritm-cnt-warn span{color:#b45309}
.ritm-head-b{display:flex;align-items:center}
.ritm-formats{margin-top:12px;padding-top:10px;border-top:1px solid #f1f5f9}
.ritm-formats summary{cursor:pointer;color:#0f766e;font-weight:600;font-size:13px;list-style:none}
.ritm-formats summary::-webkit-details-marker{display:none}
.ritm-formats summary:focus-visible{outline:2px solid #0f766e;outline-offset:2px}
.ritm-formats-b{margin-top:8px;font-size:13px;color:#334155;line-height:1.5}
.ritm-hint{font-size:12px;color:#64748b;margin-top:6px}
.ritm-tbl{width:100%;border-collapse:collapse;table-layout:fixed}
.ritm-tbl th{font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b;text-align:left;padding:10px 12px;border-bottom:1px solid #e5e7eb;white-space:nowrap}
.ritm-tbl td{padding:10px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;font-size:13px;color:#0f172a}
.ritm-c-val{width:36%}
.ritm-c-desc{width:28%}
.ritm-c-add{width:18%}
.ritm-c-sw{width:84px;text-align:center}
.ritm-c-act{width:84px;text-align:right}
.ritm-val{display:flex;align-items:flex-start;gap:8px}
.ritm-val i{color:#94a3b8;margin-top:3px}
.ritm-pat{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;overflow-wrap:anywhere;word-break:break-word}
.ritm-extra{font-size:12px;color:#64748b;margin-top:2px}
.ritm-desc{color:#334155;overflow-wrap:anywhere;text-decoration:none}
.ritm-desc:hover,.ritm-desc:focus{color:#0f766e;text-decoration:underline}
.ritm-desc-empty{color:#94a3b8;font-style:italic}
.ritm-by{font-size:12px;color:#64748b}
.ritm-row.ritm-off .ritm-c-val,.ritm-row.ritm-off .ritm-c-desc,.ritm-row.ritm-off .ritm-c-add{opacity:.55}
.ritm-row.ritm-off .ritm-pat{text-decoration:line-through}
.ritm-offpill{display:none;margin-top:4px;padding:1px 8px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:12px;font-weight:600}
.ritm-row.ritm-off .ritm-offpill{display:inline-block}
.ritm-sw{position:relative;width:38px;height:22px;border:0;border-radius:999px;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
.ritm-sw span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.ritm-sw[aria-checked=true]{background:var(--ak-fill,#0f766e)}
.ritm-sw[aria-checked=true] span{left:19px}
.ritm-sw[disabled]{opacity:.5;cursor:not-allowed}
.ritm-sw:focus-visible{outline:2px solid var(--ak-fill,#0f766e);outline-offset:2px}
.ritm-empty{display:flex;align-items:center;gap:12px;padding:18px;color:#475569}
.ritm-empty>span{flex:1}
@media (prefers-reduced-motion:reduce){.ritm-sw,.ritm-sw span{transition:none}}
</style>";
}

function item_search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=intval($_GET["item-search"]);
    $URLADDON=URL_ADDONS();
    // Hote de l'en-tete de l'objet, rempli par item_table() a chaque rechargement.
    echo "<div id='ritm-head-$gpid'></div>";
    // Ancien hote du bouton d'ajout : le bouton vit desormais dans l'en-tete.
    echo "<div id='id-button-place-$gpid' style='display:none'></div>";
    echo $tpl->search_block($page, "","webfilters_sqitems",null,"&search-gpid=$gpid&$URLADDON&items-refresh=%s");
}

function item_table(){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_search="";
    $gpid=intval($_GET["search-gpid"]);
    $jsafter=urlencode(jsafter_token($_GET["js-after"] ?? ""));
    if(!isset($_GET["search"]) || $_GET["search"]==null){$_GET["search"]="*";}
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(isset($_GET["function-search"])){ $function_search=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function-search"]); }

    $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    if(!$grp){ echo $tpl->_ENGINE_parse_body($tpl->div_error("group $gpid not found")); return; }
    $GroupType=$grp["groupType"];

    $searchParam="*";
    if(!preg_match("#field:#",$_GET["search"])){
        $searchParam=trim($_GET["search"] ?? "*");
        if(!$searchParam){$searchParam="*";}
    }
    $results=acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid&search=".urlencode($searchParam));
    if(!is_array($results)){$results=[];}
    foreach($results as &$r){ if(isset($r["id"]) && !isset($r["ID"])){$r["ID"]=$r["id"];} }
    unset($r);
    // Compteurs sur TOUTE la liste (pas seulement le resultat de la recherche).
    $isFull=($searchParam==="*" || $searchParam==="");
    $counts=ritm_counts($gpid,$results,$isFull);

    // Tri optionnel + pagination COTE SERVEUR (un groupe peut compter des milliers d'items).
    $dir=strtolower((string)($_GET["dir"] ?? ""));
    if($dir=="asc" || $dir=="desc"){
        $sens=($dir=="asc")?1:-1;
        usort($results,function($a,$b) use($sens){
            $x=@strtotime((string)($a["zdate"] ?? "")); $y=@strtotime((string)($b["zdate"] ?? ""));
            if($x===false){$x=0;} if($y===false){$y=0;}
            if($x==$y){return 0;}
            return ($x<$y?-1:1)*$sens;
        });
    }
    $psize=intval($GLOBALS["FOOTABLE_PSIZE"] ?? 0); if($psize<10){$psize=100;}
    $found=count($results);
    $pages=intval(ceil($found/$psize)); if($pages<1){$pages=1;}
    $pageNo=intval($_GET["page"] ?? 1);
    if($pageNo<1){$pageNo=1;} if($pageNo>$pages){$pageNo=$pages;}
    $from=$found==0?0:(($pageNo-1)*$psize+1);
    $results=array_slice($results,($pageNo-1)*$psize,$psize);
    $to=$from==0?0:($from+count($results)-1);

    $divid=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["div-id"] ?? ""));
    $itbase="$page?search=".urlencode((string)$_GET["search"])."&search-gpid=$gpid"
           ."&function=$function&function-search=$function_search&js-after=$jsafter&div-id=$divid";
    $itdirNext=($dir=="asc")?"desc":"asc";
    $sortIco=($dir=="asc")?"fa-arrow-up-1-9":(($dir=="desc")?"fa-arrow-down-9-1":"fa-sort");
    $ariaSort=($dir=="asc")?"ascending":(($dir=="desc")?"descending":"none");

    // Politique Meta : objet tenu = ni ajout, ni bascule, ni suppression d'element.
    $metaHeld=proxyobject_meta_held($gpid);
    $write=ritm_can_write() && !$metaHeld;
    $newjs="Loadjs('$page?new-item-js=$gpid&js-after=$jsafter&function=$function&function-search=$function_search');";
    $header=ritm_header($grp,$counts,ritm_can_write(),$metaHeld,$newjs);

    list($ico,$tlabel)=ritm_type_info((string)$GroupType);
    $GEO_IP_COUNTRIES_LIST=GEO_IP_COUNTRIES_LIST();
    $IpClass=new IP();
    $lockWhy=$metaHeld?"{ritm_meta_locked}":"{ritm_no_privs}";

    $html=array();
    $html[]="<input type='hidden' id='proxy-acls-items-function' value='$function'>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='ritm-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th class='ritm-c-val' title='{ritm_th_value_help}'>{ritm_value}</th>";
    $html[]="<th class='ritm-c-desc'>{description}</th>";
    $html[]="<th class='ritm-c-add' aria-sort='$ariaSort'><a id='ritems2-th-date' href=\"javascript:blur();\" data-dir='$itdirNext' title='{ritm_th_added_help}' style='color:inherit;text-decoration:none'>{ritm_added} <span class='fa-solid $sortIco' style='opacity:.6'></span></a></th>";
    $html[]="<th class='ritm-c-sw'>{ritm_enabled}</th>";
    $html[]="<th class='ritm-c-act'>{ritm_actions}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='ritems2-rows'>";

    foreach ($results as $ligne){
        $ID=intval($ligne["ID"]);
        $md=md5(serialize($ligne));
        $rawPattern=(string)$ligne["pattern"];
        $pattern=ritm_h($rawPattern);
        $extra=array();
        if($GroupType=="accessrule"){
            $ruleid=intval($rawPattern);
            if($ruleid==0){ continue; }
            $simpleAcl=acls_api_get("/proxy/simple-acls/".intval($ruleid));
            $extra[]=ritm_h(is_array($simpleAcl)?(string)($simpleAcl["aclName"] ?? ""):"");
        }
        if($GroupType=="geoipdest" && isset($GEO_IP_COUNTRIES_LIST[$rawPattern])){
            $extra[]=ritm_h($GEO_IP_COUNTRIES_LIST[$rawPattern]);
        }
        if($IpClass->IsvalidMAC($rawPattern)){
            $host=new hosts($rawPattern);
            if($host->hostname<>null){ $extra[]=ritm_h($host->hostname); }
            if($host->proxyalias<>null){ $extra[]=ritm_h($host->proxyalias); }
        }
        $descRaw="";
        if(!is_null($ligne["description"] ?? null)){
            $descRaw=trim($tpl->_ENGINE_parse_body(trim(items_description_clear($ligne["description"]))));
        }
        $descJs="Loadjs('$page?item-description-js=$ID')";
        if($descRaw!==""){
            $desc="<a href='javascript:void(0)' class='ritm-desc' id='descitem-$ID' title='{ritm_edit_desc}' OnClick=\"$descJs\">".ritm_h($descRaw)."</a>";
        }else{
            $desc="<a href='javascript:void(0)' class='ritm-desc ritm-desc-empty' id='descitem-$ID' OnClick=\"$descJs\"><i class='fa-solid fa-pen'></i> {ritm_add_desc}</a>";
        }
        $zdate=(string)($ligne["zdate"] ?? "");
        $ts=@strtotime($zdate);
        $dateText=($ts!==false && $ts>0)?date("Y-m-d H:i",$ts):"&mdash;";
        $uid=trim((string)($ligne["uid"] ?? ""));
        $by=$uid!==""?"<div class='ritm-by'>".ritm_t("{ritm_by}",ritm_h($uid))."</div>":"";
        $on=intval($ligne["enabled"] ?? 1)==1;
        $aria=$on?"true":"false";
        $lbl=ritm_t("{ritm_enable_x}",$pattern);
        if($write){
            $sw="<button type='button' class='ritm-sw' id='ritm-sw-$ID' role='switch' aria-checked='$aria' aria-label='$lbl' OnClick=\"Loadjs('$page?item-enable=$ID&md=$md');\"><span></span></button>";
            $del="<button type='button' class='ak-act ak-danger' aria-label='".ritm_t("{ritm_delete_x}",$pattern)."' title='{ritm_delete}' OnClick=\"Loadjs('$page?item-delete=$ID&md=$md&function=$function&function-search=$function_search');\"><i class='fa-solid fa-trash'></i></button>";
        }else{
            $sw="<button type='button' class='ritm-sw' role='switch' aria-checked='$aria' aria-label='$lbl' title='$lockWhy' disabled><span></span></button>";
            $del="<span class='ak-act' title='$lockWhy' style='color:#cbd5e1'><i class='fa-solid fa-lock'></i></span>";
        }
        $icoh=$ico!==""?"<i class='$ico'></i>":"";
        $extraH=count($extra)?"<div class='ritm-extra'>".implode(", ",$extra)."</div>":"";
        $cls="ritm-row".($on?"":" ritm-off");
        $html[]="<tr class='$cls' id='$md' data-item='$ID'>";
        $html[]="<td class='ritm-c-val'><div class='ritm-val'>$icoh<div><div class='ritm-pat'>$pattern</div>$extraH<span class='ritm-offpill'><i class='fa-regular fa-circle-xmark'></i> {ritm_off_ignored}</span></div></div></td>";
        $html[]="<td class='ritm-c-desc'>$desc</td>";
        $html[]="<td class='ritm-c-add'>$dateText$by</td>";
        $html[]="<td class='ritm-c-sw'>$sw</td>";
        $html[]="<td class='ritm-c-act'><div class='ak-acts'>$del</div></td>";
        $html[]="</tr>";
    }
    if(count($results)==0){
        if($isFull){
            $btn=$write?"<button type='button' class='ak-btn ak-btn-sm' OnClick=\"$newjs\"><i class='fa-solid fa-plus'></i> {new_item}</button>":"";
            $html[]="<tr><td colspan='5'><div class='ritm-empty'><i class='fa-solid fa-inbox'></i><span>{ritm_empty}</span>$btn</div></td></tr>";
        }else{
            $html[]="<tr><td colspan='5'><div class='ritm-empty'><i class='fa-solid fa-magnifying-glass'></i><span>".ritm_t("{ritm_nomatch}",ritm_h($searchParam))."</span></div></td></tr>";
        }
    }
    $html[]="</tbody>";
    $compte=str_replace(array("%a","%b","%c"),array(ritm_num($from),ritm_num($to),ritm_num($found)),$tpl->_ENGINE_parse_body("{ritm_showing}"));
    $html[]="</table></div><div class='awc-foot'><span>$compte</span>"
           ."<span class='awc-pg'>".awc_pager_server($pageNo,$pages,"ritems2-pg-a")."</span>"
           ."</div></div></div>";

    $headEnc=base64_encode($tpl->_ENGINE_parse_body($header));
    $js=array();
    $js[]="<script>";
    $js[]="NoSpinner();";
    $js[]="if(document.getElementById('ritm-head-$gpid')){document.getElementById('ritm-head-$gpid').innerHTML=base64_decode('$headEnc');}";
    $js[]="</script>";

    $nav ="<script>\n(function(){\n";
    $nav.=" var base=".json_encode($itbase).", div=".json_encode($divid).";\n";
    $nav.=" if(div==\"\"){return;}\n";
    $nav.=" function go(u){ LoadAjaxTiny(div,u); }\n";
    $nav.=" var th=document.getElementById(\"ritems2-th-date\");\n";
    $nav.=" if(th){ th.onclick=function(){ go(base+\"&dir=\"+this.getAttribute(\"data-dir\")); return false; }; }\n";
    $nav.=" var as=document.querySelectorAll(\".ritems2-pg-a\");\n";
    $nav.=" for(var i=0;i<as.length;i++){\n";
    $nav.="   as[i].onclick=function(){ go(base+\"&dir=".$dir."&page=\"+this.getAttribute(\"data-p\")); return false; };\n";
    $nav.=" }\n})();\n</script>";

    echo awc_table_style().ritm_style()
        .$tpl->_ENGINE_parse_body(implode("\n", $html))
        .implode("\n",$js).$nav;
}

function itype_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["itype-search"]);
    $items_api=acls_api_get("/proxy/acls/sqlite/items?gpid=$groupid");
    $ALREADY=[];
    if(is_array($items_api)){
        foreach($items_api as $ligne){
            $pattern=intval($ligne["pattern"] ?? 0);
            $ALREADY[$pattern]=true;
        }
    }

    $f[0]="Echo Reply";
    $f[3]="Destination Unreachable";
    $f[4]="Source Quench (deprecated)";
    $f[5]="Redirect";
    $f[8]="Echo Request (ping)";
    $f[9]="Router Advertisement";
    $f[10]="Router Solicitation";
    $f[11]="Time Exceeded";
    $f[12]="Parameter Problem";
    $f[13]="Timestamp Request";
    $f[14]="Timestamp Reply";
    $f[15]="Information Request (obsolete)";
    $f[16]="Information Reply (obsolete)";
    $f[17]="Address Mask Request";
    $f[18]="Address Mask Reply";

    $form[]=$tpl->field_hidden("itype",$groupid);

    foreach($f as $num=>$expl){
        $val=0;
        if(isset($ALREADY[$num])){
            $val=1;
        }
        $form[]=$tpl->field_checkbox("itype_$num",$expl,$val);
    }
    echo $tpl->form_outside("",$form,"","{apply}","","AsFirewallManager");
    return true;

}
function itype_save():bool{
    $groupid=intval($_POST["itype"]);
    if($groupid<=0){return false;}
    $items=[];
    foreach($_POST as $key=>$value){
        if(intval($value)==0){continue;}
        if(!preg_match("#^itype_([0-9]+)#",$key,$re)){continue;}
        $items[]=["pattern"=>(string)intval($re[1])];
    }
    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/items/bulk",[
        "gpid"=>$groupid,"items"=>$items,"mode"=>"replace_all",
    ]);
    if(!$ok){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($data);
        return false;
    }
    admin_tracks("Save ICMP types for ACL group $groupid");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/parse");
    return true;
}

function ndpi_list(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["ndpi-list"]);
    //ndpi-list=$groupid&js-after=$jsafter&function=$function
    $jsafter=jsafter_token($_GET["js-after"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));


    $f=explode("\n",@file_get_contents("/proc/net/xt_ndpi/proto"));
    $t=time();
    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{software}</th>";
    $html[]="<th></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ritems3-rows'>";

    $existingItems=acls_api_get("/proxy/acls/sqlite/items?gpid=$groupid");
    if(!is_array($existingItems)){$existingItems=[];}
    foreach ($existingItems as $existItem){
        $pattern=strtolower($existItem["pattern"] ?? "");
        if($pattern<>""){$ALREADY[$pattern]=true;}
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
        $TRCLASS=null;
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"left\"><strong>$strnorm</strong></td>";
        $html[]="<td style='width:1%;' nowrap cwidth:1%'>". $tpl->button_autnonome("{select}","Loadjs('$page?ndpi-choose=$strlow&gpid=$groupid&md=$md&js-after=$jsafter&function=$function')",
                "fas fa-box-check","AsFirewallManager",0,"btn-primary","small")."</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='ritems3-pager'></span></div></div></div>";

    $html[]="
<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ritems3-rows","ritems3-pager");



}
function isReader($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }
    if($users->AsProxyMonitor){
        return true;
    }
    if($users->AsDnsAdministrator){
        return true;
    }
    if($users->AsDnsMonitor){
        return true;
    }
    return false;
}
function isWritter($users){
    if($users->AsDansGuardianAdministrator){
        return true;
    }

    return false;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

// ---------------------------------------------------------------------------------------
// Politique Meta (class.proxypac.meta.inc). Chaque ecriture de ce fichier vise un groupe
// (gpid) ou un element (ID d'element) : objet tenu = refus, au format attendu par l'appelant
// (post_error pour form_outside, texte pour js_confirm/XHR, JavaScript pour Loadjs).
// Le demon refuse deja (423) ; cette garde affiche un message traduit et n'appelle rien.
// ---------------------------------------------------------------------------------------
function fri_meta_guard(){
    // POST de form_outside : cle d'aiguillage => source du gpid.
    $posts=array("itype"=>"itype","new-item-fingerprint"=>"new-item-fingerprint",
        "new-item-header-save"=>"new-item-header-save","new-item-save"=>"new-item-save",
        "SaveGroupName"=>"groupid","pattern"=>"groupid","SaveGroup"=>"SaveGroup",
        "SaveTime"=>"SaveTime","url_db"=>"url_db");
    foreach($posts as $k=>$src){
        if(isset($_POST[$k])){
            $m=proxyobject_meta_refuse_post(intval($_POST[$src] ?? 0));
            if($m!==""){echo $m;exit;}
            return;
        }
    }
    if(isset($_POST["item-description"])){
        if(proxyobject_meta_item_held($_POST["item-description"])){
            echo (new template_admin())->post_error("{proxyobject_meta_locked}");exit;
        }
        return;
    }
    // POST de js_confirm_* / XHR (texte de plus de 3 caracteres = erreur affichee).
    foreach(array("weekrange_gpid","category-add-all","category-add-dangerous","category-add-polluate",
        "category-add-nonproductive","category-clean") as $k){
        if(isset($_POST[$k])){
            if(proxyobject_meta_refuse_text(intval($_POST[$k]))){exit;}
            return;
        }
    }
    // Loadjs : cle d'aiguillage => source du gpid.
    $gets=array("urlsdb-gpid"=>"urlsdb-gpid","ndpi-choose"=>"gpid","category-post-all"=>"category-post-all",
        "category-post-js"=>"ID","category-unlink"=>"gpid","countries-enable"=>"gpid","spmac-val"=>"gpid",
        "countries-selectall-js"=>"countries-selectall-js","countries-deselectall-js"=>"countries-deselectall-js",
        "new-item-js"=>"new-item-js","add-categories-js"=>"add-categories-js","category-add-all-js"=>"category-add-all-js",
        "category-add-dangerous-js"=>"category-add-dangerous-js","category-add-polluate-js"=>"category-add-polluate-js",
        "category-add-nonproductive-js"=>"category-add-nonproductive-js","category-clean"=>"category-clean");
    foreach($gets as $k=>$src){
        if(isset($_GET[$k])){
            if(proxyobject_meta_refuse_js(intval($_GET[$src] ?? 0))){exit;}
            return;
        }
    }
    foreach(array("item-delete","item-enable") as $k){
        if(isset($_GET[$k])){
            if(proxyobject_meta_item_held($_GET[$k])){
                header("content-type: application/x-javascript");
                (new template_admin())->js_error("{proxyobject_meta_locked}");
                exit;
            }
            return;
        }
    }
}

// Formulaire de proprietes d'un groupe tenu : bandeau + formulaire grise. "" si libre.
function fri_meta_lock_form($tpl,$gpid):string{
    $banner=proxyobject_meta_banner($tpl,$gpid);
    if($banner===""){return "";}
    $tpl->FORM_LOCKED=true;
    $tpl->this_form_locked_explain="<i class='fa-solid fa-lock'></i>&nbsp;{proxyobject_meta_locked}";
    // item_add_popup() passe la main a item_time() : un seul bandeau par reponse.
    if(isset($GLOBALS["FRI_META_BANNER"])){return "";}
    $GLOBALS["FRI_META_BANNER"]=true;
    return $banner;
}
