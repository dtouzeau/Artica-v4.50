<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.categories.mem.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.categories.inc');
include_once("ressources/class.ldap-extern.inc");

if(isset($_GET["js-ID"])){js_id();exit;}
if(isset($_GET["start-list"])){table_start();exit;}
if(isset($_GET["list"])){table();exit;}
if(isset($_GET["category-add-js"])){categoy_add_js();exit;}
if(isset($_GET["category-add"])){categoy_add();exit;}
if(isset($_GET["category-post-js"])){categoy_post();exit;}
if(isset($_GET["category-del-js"])){category_del();exit;}
if(isset($_GET["category-add-dangerous-js"])){category_dangerous_js();exit;}
if(isset($_POST["category-add-dangerous"])){category_dangerous_perform();exit;}
if(isset($_GET["category-add-polluate-js"])){category_polluate_js();exit;}
if(isset($_POST["category-add-polluate"])){category_polluate_perform();exit;}
if(isset($_GET["category-add-nonproductive-js"])){category_nonproductive_js();exit;}
if(isset($_POST["category-add-nonproductive"])){category_nonproductive_perform();exit;}
if(isset($_GET["category-clean"])){category_clean_js();exit;}
if(isset($_POST["category-clean"])){category_clean_perform();exit;}
if(isset($_GET["category-add-all-js"])){category_all_js();exit;}
if(isset($_POST["category-add-all"])){category_all_perform();exit;}


//category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md

page();

function categoy_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $add=array();

    if(isset($_GET["ID"])){$add[]="ID=".$_GET["ID"];}
    if(isset($_GET["modeblk"])){$add[]="modeblk=".$_GET["modeblk"];}
    if(isset($_GET["groupid"])){$add[]="groupid=".$_GET["groupid"];}
    $options=@implode("&",$add);
    $tpl->js_dialog5("{categories}", "$page?category-add=yes&$options");
    return true;
}

function js_id(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["js-ID"]);
    $rulename=null;
    if($ID==0){$rulename="{default}";}


    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $sql="SELECT groupname FROM webfilter_rules WHERE ID=$ID";
        $ligne=$q->mysqli_fetch_array($sql);
        $rulename=$ligne["groupname"];
    }

    $array[0]="{blacklists}";
    $array[1]="{whitelists}";
    $blks=$array[$_GET["modeblk"]];

    $tpl->js_dialog2("$rulename: {categories} $blks","$page?ID=$ID&modeblk={$_GET["modeblk"]}",755);
}


function category_del(){

    $category   = $_GET["category-del-js"];
    $ID         = $_GET["ID"];
    $modeblk    = $_GET["modeblk"];
    $q          = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $tpl        = new template_admin();

    if(preg_match("#GpCatz([0-9]+)#",$category,$re)){
        $MAIN_ID=$re[1];
        $sql = "DELETE FROM webfilter_blklnk WHERE ID=$MAIN_ID";
        $q->QUERY_SQL($sql);
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
        header("content-type: application/x-javascript");
        echo "$('#{$_GET["md"]}').remove();\n";
        echo "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
        return;
    }



    $sql="DELETE FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    header("content-type: application/x-javascript");
    echo "$('#{$_GET["md"]}').remove();\n";
    echo "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
}

function categoy_post(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $add=null;
    $category=$_GET["category-post-js"];

    if(preg_match("#^GpCatz([0-9]+)#",$category,$re)){
        $categories_group_id    = $re[1];
        $ID                     = $_GET["ID"];
        $modeblk                = $_GET["modeblk"];
        $md5                    = md5("$categories_group_id$ID$modeblk");

        $q->QUERY_SQL("DELETE FROM webfilter_blklnk WHERE `zmd5`='$md5'");
        $sql="INSERT INTO webfilter_blklnk (`zmd5`,`webfilter_blkid`,`webfilter_ruleid`,`blacklist`)
            VALUES ('$md5','$categories_group_id','$ID','$modeblk')";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {$tpl->js_mysql_alert($q->mysql_error);return;}
        echo "$('#{$_GET["md"]}').remove();\n";
        echo "RefreshUfdbCategoriesList();\n";
        echo "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');\n";
        return;
    }


    if(isset($_GET["ID"])) {
        $ID = $_GET["ID"];
        $modeblk = $_GET["modeblk"];
        $sql = "SELECT ID FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'";
        $ligne = $q->mysqli_fetch_array($sql);
        if(!isset($ligne["ID"])){
            $ligne["ID"]=0;
        }
        if ($ligne["ID"] > 0) {
            return;
        }
        $sql = "INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ID','$category','$modeblk')";
        writelogs($sql, __FUNCTION__, __FILE__, __LINE__);
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            $tpl->js_mysql_alert($q->mysql_error);
            return;
        }
    }

    if(isset($_GET["groupid"])){
        $sql="INSERT INTO webfilter_blkcnt (webfilter_blkid,category) VALUES('{$_GET["groupid"]}','$category')";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo "alert('" . $tpl->javascript_parse_text($q->mysql_error) . "');";
            return;
        }

        $add="LoadAjax('categories-table-{$_GET["groupid"]}-div','fw.ufdb.categories.groups.php?categories-table={$_GET["groupid"]}');";
    }



    echo "$('#{$_GET["md"]}').remove();\n";
    echo "$add\n";
    echo "RefreshUfdbCategoriesList();\n";
    echo "LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');\n";


}

function page():bool{
    $page=CurrentPageName();
    $ID=$_GET["ID"];
    $modeblk=$_GET["modeblk"];
    echo "<div id='ufdb-categories-list-$ID-$modeblk' style='margin-top:20px'></div>
	<script>
		function RefreshUfdbCategoriesList(){
			LoadAjax('ufdb-categories-list-$ID-$modeblk','$page?list=yes&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}');
            LoadAjax('artica-ufdbrules-table','fw.ufdb.rules.php?table=yes');
			
		}
        function RefreshUfdbCategoriesList$ID$modeblk(){
			LoadAjax('ufdb-categories-list-$ID-$modeblk','$page?list=yes&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}');
            LoadAjax('artica-ufdbrules-table','fw.ufdb.rules.php?table=yes');
			
		}
		RefreshUfdbCategoriesList();
	</script>	
	";

    return true;
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
    $MAIN=unserialize(base64_decode($_POST["category-add-all"]));
    $ID=intval($MAIN["ID"]);
    $modeblk=$MAIN["modeblk"];
    $category_list=category_all_list();

    if(count($category_list)==0){
        echo "No Category!!!???\n";
        return false;
    }
    writelogs("Inject category_list into $ID",__FUNCTION__,__FILE__,__LINE__);
    if(!category_inject($category_list,$ID,$modeblk)){
        return false;
    }

    return admin_tracks("Adding All Web-Filtering categories inside rule $ID");

}

function categoy_add(){

    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $q                  = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $NOGROUP            = false;
    $category           = $tpl->_ENGINE_parse_body("{category}");
    $description        = $tpl->_ENGINE_parse_body("{description}");
    $UfdbMasterCache    = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    $useCGuardCategories = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCGuardCategories");
    $TRCLASS            = null;
    $add=array();
    if(isset($_GET["ID"])){$add[]="ID=".$_GET["ID"];}
    if(isset($_GET["modeblk"])){$add[]="modeblk=".$_GET["modeblk"];}
    if(isset($_GET["groupid"])){$add[]="groupid=".$_GET["groupid"];}
    $url_options=@implode("&",$add);

    if(isset($_GET["ID"])) {
        $sql = "SELECT `category` FROM webfilter_blks WHERE `webfilter_id`={$_GET["ID"]} AND modeblk={$_GET["modeblk"]}";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index => $ligne) {
            $cats[$ligne["category"]] = true;
        }

        $sql = "SELECT `ID` FROM webfilter_blklnk WHERE `webfilter_ruleid`={$_GET["ID"]} AND blacklist={$_GET["modeblk"]}";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index => $ligne) {
            $ID=$ligne["ID"];
            $cats["GpCatz$ID"] = true;
        }


    }

    if(isset($_GET["groupid"])){
        $NOGROUP=true;
        $sql = "SELECT `category` FROM webfilter_blkcnt WHERE `webfilter_blkid`={$_GET["groupid"]}";
        $results = $q->QUERY_SQL($sql);
        foreach ($results as $index => $ligne) {
            $cats[$ligne["category"]] = true;
        }

    }



    $html[]="<table id='table-category-add-{$_GET["modeblk"]}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$category</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    if(!$NOGROUP){
        $results=$q->QUERY_SQL("SELECT * FROM webfilter_blkgp WHERE enabled=1 ORDER BY groupname");
        if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
        foreach ($results as $index=>$ligne){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md         = md5(serialize($ligne));
            $groupname  = $ligne["groupname"];
            $key        = "GpCatz{$ligne["ID"]}";
            $js         = "Loadjs('$page?category-post-js=$key&$url_options&md=$md')";
            $button     = "<button class='btn btn-info btn-xs' OnClick=\"$js\">{select}</button>";
            $ligne2     = $q->mysqli_fetch_array("SELECT COUNT(`category`) AS 
                            CountDeCats FROM webfilter_blkcnt WHERE `webfilter_blkid`='{$ligne["ID"]}'");
            $Categories = intval($ligne2["CountDeCats"]);

            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td style='width:1%'><i class=\"fas fa-folder-tree\"></i></td>";
            $html[]="<td style='width:1%' nowrap><strong>$groupname</strong></td>";
            $html[]="<td>{categories_groups} ($Categories {categories})</td>";
            $html[]="<td style='width:1%' nowrap>$button</td>";
            $html[]="</tr>";

        }


    }
    $q=new postgres_sql();
    if(!$q->TABLE_EXISTS("personal_categories")){
        $categories=new categories();
        $categories->initialize();
    }

    $COUNT_OF_ROWS=$q->COUNT_ROWS("personal_categories");
    VERBOSE("personal_categories: $COUNT_OF_ROWS rows",__LINE__);

    if(!$q->FIELD_EXISTS("personal_categories", "compiledate")){
        $q->QUERY_SQL("alter table personal_categories add column if not exists compiledate bigint;");
        if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    }
    if(!$q->FIELD_EXISTS("personal_categories", "remotecatz")){
        $q->QUERY_SQL("alter table personal_categories add column if not exists remotecatz smallint;");
        if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    }




    $q->QUERY_SQL("UPDATE personal_categories SET remotecatz=0 WHERE serviceid=0");
    $q->QUERY_SQL("UPDATE personal_categories SET remotecatz=0 WHERE remotecatz is NULL");
    $sql="SELECT *  FROM personal_categories WHERE free_category=0 AND meta=0 ORDER BY categoryname";

    if($useCGuardCategories==1){
        $sql="SELECT *  FROM personal_categories WHERE remotecatz=0 OR serviceid=999991 ORDER BY categoryname";

    }

    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    VERBOSE($sql,__LINE__);

    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);exit();}
    $CountOfRows=0;
    if($results) {
        $CountOfRows = pg_num_rows($results);
    }
    VERBOSE("SQL: $CountOfRows rows",__LINE__);
    $categories_explain_no_license=$tpl->_ENGINE_parse_body("{categories_explain_no_license}");
    if(!$CORP_LICENSE) {
        $md=md5(time());
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td style='width:1%'>&nbsp;</td>";
        $html[] = "<td style='width:1%' nowrap><span class='label label-warning'>{license_error}</span></td>";
        $html[] = "<td><strong>$categories_explain_no_license</strong></td>";
        $html[] = "<td>&nbsp;</td>";
        $html[] = "</tr>";
    }
    $BlacklistedCatz[229] = true;
    $BlacklistedCatz[230] = true;
    $BlacklistedCatz[231] = true;
    $BlacklistedCatz[232] = true;
    $BlacklistedCatz[233] = true;
    $BlacklistedCatz[234] = true;
    $BlacklistedCatz[236] = true;
    $BlacklistedCatz[237] = true;
    $BlacklistedCatz[238] = true;

    while ($ligne = pg_fetch_assoc($results)) {
        $categoryname=$ligne["categoryname"];
        $items=$ligne["items"];
        $category_id=intval($ligne["category_id"]);
        if($BlacklistedCatz[$category_id]) {
            continue;
        }
        $category_description=$ligne["category_description"];
        $category_icon=$ligne["category_icon"];
        $official_category=$ligne["official_category"];
        $free_category=$ligne["free_category"];
        if(preg_match("#^reserved#",$categoryname)){continue;}
        if($category_id==238){continue;}


        $ISOFFICIAL=false;
        if($official_category==1){$ISOFFICIAL=true;}
        if($free_category==1){$ISOFFICIAL=true;}
        if($category_icon==null){$category_icon="img/20-categories-personnal.png";}
        if(isset($cats[$category_id])){continue;}
        $category_table_elements=0;
        $elements=null;


        if(!$ISOFFICIAL){
            $category_table_elements=$items;
        }else{
            if(isset($UfdbMasterCache[$category_id]["items"])) {
                $category_table_elements = $UfdbMasterCache[$category_id]["items"];
            }
        }

        $img=$category_icon;
        $md=md5($ligne["categorykey"]);
        $lic_error=null;
        $ligne['description']=$tpl->utf8_encode($category_description);

        $js="Loadjs('$page?category-post-js=$category_id&$url_options&md=$md')";
        $button="<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";

        if($category_table_elements>0){$elements="<br><strong><small>".FormatNumber($category_table_elements)." {items}</small></strong>";}
        if($official_category==1) {
            if (!$CORP_LICENSE) {
                $button = "<button class='btn btn-default btn-xs' OnClick=\"blur()\">{select}</button>";
            }
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><img src='$img' alt=''></td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->_ENGINE_parse_body($categoryname)."</td>";
        $html[]="<td>".$tpl->_ENGINE_parse_body("{$ligne['description']} $elements")."$lic_error</td>";
        $html[]=$tpl->_ENGINE_parse_body("<td style='width:1%' nowrap>$button</td>");
        $html[]="</tr>";

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
$(document).ready(function() { $('#table-category-add-{$_GET["modeblk"]}').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function category_all_js():bool{
    $ID=intval($_GET["ID"]);
    $modeblk=$_GET["modeblk"];
    $tpl=new template_admin();
    $MAIN=base64_encode(serialize($_GET));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{add_all_categories_explain}<br>{category_family_add_expl}",
        "category-add-all",$MAIN,
        $js
    );


}
function category_dangerous_js():bool{
    $ID=intval($_GET["ID"]);
    $modeblk=$_GET["modeblk"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MAIN=base64_encode(serialize($_GET));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{dangerous_categories_explain}<br>{category_family_add_expl}",
        "category-add-dangerous",$MAIN,
        $js
    );
}
function category_nonproductive_js():bool{
    $ID=intval($_GET["ID"]);
    $modeblk=$_GET["modeblk"];
    $tpl=new template_admin();
    $MAIN=base64_encode(serialize($_GET));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{nonproductive_cat_explain}<br>{category_family_add_expl}",
        "category-add-nonproductive",$MAIN,
        $js
    );
}

function category_polluate_js():bool{
    $ID=intval($_GET["ID"]);
    $modeblk=$_GET["modeblk"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MAIN=base64_encode(serialize($_GET));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{pollution_categories_explain}<br>{category_family_add_expl}",
        "category-add-polluate",$MAIN,
        $js
    );
}
function category_clean_js():bool{
    $ID=intval($_GET["ID"]);
    $modeblk=$_GET["modeblk"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $MAIN=base64_encode(serialize($_GET));
    $js="RefreshUfdbCategoriesList$ID$modeblk();";

    return $tpl->js_confirm_execute("{delete_all_items}",
        "category-clean",$MAIN,
        $js
    );
}



function category_dangerous_list():array{
    $list="92,135,64,140,181,111,105,46,149,238";
    return explode(",",$list);
}
function category_nonproductive_list():array{
    $list="10,14,41,42,45,57,58,132,97,150,148,130,112,109,71,238";
    return explode(",",$list);
}
function category_polluate_list():array{
    $list="143,5,91,238";
    return explode(",",$list);
}
function category_inject($Next,$ID,$modeblk):bool{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $CurrentCategories=CurrentCategories($ID,$modeblk);
    foreach ($Next as $catid){
        if(isset($CurrentCategories[$catid])){
            continue;
        }

        $sql = "INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) 
                VALUES ('$ID','$catid','$modeblk')";

        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            $tpl->js_mysql_alert($q->mysql_error);
            return false;
        }

    }
    return true;

}
function CurrentCategories($ID,$modeblk):array{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $SAVED=array();
    $results=$q->QUERY_SQL("SELECT category FROM webfilter_blks WHERE webfilter_id=$ID AND modeblk='$modeblk'");
    foreach ($results as $index=>$ligne){
        $category=$ligne["category"];
        $SAVED[$category]=true;
    }
    return $SAVED;
}
function category_polluate_perform():bool{
    $MAIN=unserialize(base64_decode($_POST["category-add-polluate"]));
    $ID=intval($MAIN["ID"]);
    $modeblk=$MAIN["modeblk"];
    $category_dangerous_list=category_polluate_list();
    if(!category_inject($category_dangerous_list,$ID,$modeblk)){
        return false;
    }

    return admin_tracks("Adding Web-Filtering Polluate categories for rule $ID");
}
function category_dangerous_perform():bool{
    $MAIN=unserialize(base64_decode($_POST["category-add-dangerous"]));
    $ID=intval($MAIN["ID"]);
    $modeblk=$MAIN["modeblk"];
    $category_dangerous_list=category_dangerous_list();
    if(!category_inject($category_dangerous_list,$ID,$modeblk)){
        return false;
    }

    return admin_tracks("Adding Web-Filtering Dangerous categories for rule $ID");

}
function category_clean_perform():bool{
    $MAIN=unserialize(base64_decode($_POST["category-clean"]));
    $ID=intval($MAIN["ID"]);
    $modeblk=$MAIN["modeblk"];

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql = "DELETE FROM webfilter_blks WHERE webfilter_id='$ID'AND modeblk=$modeblk";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo "$sql\n$q->mysql_error";
        return false;
    }
    return admin_tracks("Removing Web-Filtering categories from rule $ID");
}
function category_nonproductive_perform():bool{
    $MAIN=unserialize(base64_decode($_POST["category-add-nonproductive"]));
    $ID=intval($MAIN["ID"]);
    $modeblk=$MAIN["modeblk"];
    $category_dangerous_list=category_nonproductive_list();
    if(!category_inject($category_dangerous_list,$ID,$modeblk)){
        return false;
    }

    return admin_tracks("Adding Web-Filtering Non-productives categories for rule $ID");


}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    FILL_CATEGORIES_MEM();

    $tableProd      = "webfilter_blks";
    $category       = $tpl->_ENGINE_parse_body("{category}");
    $description    = $tpl->_ENGINE_parse_body("{description}");
    $TRCLASS        = null;
    $postgres       = new postgres_sql();
    $btns           = null;
    $t              = time();

    $ruleid=intval($_GET["ID"]);
    $modeblk=$_GET["modeblk"];
    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $add_button="Loadjs('$page?category-add-js=yes&ID=$ruleid&modeblk=$modeblk')";
    $add_danger="Loadjs('$page?category-add-dangerous-js=yes&ID=$ruleid&modeblk=$modeblk')";
    $add_polluate="Loadjs('$page?category-add-polluate-js=yes&ID=$ruleid&modeblk=$modeblk')";
    $add_nonproduct="Loadjs('$page?category-add-nonproductive-js=yes&ID=$ruleid&modeblk=$modeblk')";
    $all_button="Loadjs('$page?category-add-all-js=yes&ID=$ruleid&modeblk=$modeblk')";

    if($PowerDNSEnableClusterSlave==1){
        $html[]=$tpl->div_warning("{formlock_clutser_slave}");
    }else{
        $del_button="Loadjs('$page?category-clean=yes&ID=$ruleid&modeblk=$modeblk')";
        $topbuttons[] = array($add_button,ico_plus,"{add_categories}");
        if($modeblk==0) {
            $topbuttons[] = array($add_danger, ico_plus, "{dangerous_categories}");
            $topbuttons[] = array($add_polluate, ico_plus, "{pollution_categories}");
            $topbuttons[] = array($add_nonproduct, ico_plus, "{nonproductive}");
        }
        $topbuttons[] = array($all_button,ico_plus,"{all_categories}");
        $topbuttons[] = array($del_button,ico_trash,"{delete_all}");
        $btns=$tpl->th_buttons($topbuttons);
    }



    $html[]="<table id='table-all-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th colspan=5>$btns</th></tr>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>$category</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="<th data-sortable=false>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $sql = "SELECT * FROM webfilter_blklnk WHERE `webfilter_ruleid`=$ruleid AND blacklist={$_GET["modeblk"]}";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);
        return false;
    }



    foreach ($results as $index => $ligne) {
        $md             = md5(serialize($ligne));
        $IDrow          = $ligne["ID"];
        $ID             = $ligne["webfilter_blkid"];
        $ligne2         = $q->mysqli_fetch_array("SELECT groupname,enabled FROM webfilter_blkgp WHERE ID=$ID");
        $groupname      = $ligne2["groupname"];
        $enabled        = $ligne2["enabled"];
        $enabled_text   = null;
        $ligne2         = $q->mysqli_fetch_array("SELECT COUNT(`category`) AS 
                            CountDeCats FROM webfilter_blkcnt WHERE `webfilter_blkid`='$ID'");
        $Categories = intval($ligne2["CountDeCats"]);

        if($enabled==0){$enabled_text="&nbsp;({disabled})";}
        $jsdel="Loadjs('$page?category-del-js=GpCatz$IDrow&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md')";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><i class=\"fas fa-folder-tree\"></i></td>";
        $html[]="<td><strong>$groupname$enabled_text</strong></td>";
        $html[]="<td>{categories_groups} ($Categories {categories})</td>";
        $html[]="<td>". $tpl->icon_delete($jsdel,"AsDansGuardianAdministrator")."</td>";
        $html[]="</tr>";

    }



    $sql="SELECT `category` FROM $tableProd WHERE `webfilter_id`=$ruleid AND modeblk={$_GET["modeblk"]}";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error_html(true);}


    foreach ($results as $ligne){
        $ligne2=array();
        $category_id=intval($ligne["category"]);
        if(isset($GLOBALS["categories_descriptions"][$category_id])){
            $ligne2["category_description"]=$GLOBALS["categories_descriptions"][$category_id]["category_description"];
            $ligne2["categoryname"]=$GLOBALS["categories_descriptions"][$category_id]["categoryname"];
            $ligne2["category_icon"]=$GLOBALS["categories_descriptions"][$category_id]["category_icon"];
        }

        if(count($ligne2)==0) {
            $ligne2 = pg_fetch_array($postgres->QUERY_SQL("SELECT * FROM personal_categories WHERE category_id='$category_id'"));
            if(!$ligne2){
                $ligne2=array();
            }
        }




        $categorykey=$category_id;
        $category_description=$ligne2["category_description"];
        $categoryname=$tpl->_ENGINE_parse_body($ligne2["categoryname"]);
        $category_icon=$ligne2["category_icon"];
        if($category_icon==null){
            $category_icon  = "img/20-categories-personnal.png";
        }

        if($categoryname==null){
            $categoryname="{unknown}: {category} $category_id";
            $category_icon="img/20-check-red.png";
        }

        $md=md5("$category_id{$_GET["ID"]}");
        $ligne['description']=$tpl->utf8_encode($tpl->_ENGINE_parse_body($category_description));

        $jsdel="Loadjs('$page?category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md')";
        $delete_icon=$tpl->icon_delete($jsdel,"AsDansGuardianAdministrator");
        if($PowerDNSEnableClusterSlave==1){ $delete_icon=$tpl->icon_nothing(); }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><img src='$category_icon'></td>";
        $html[]="<td nowrap>$categoryname</td>";
        $html[]="<td>{$ligne['description']}</td>";
        $html[]="<td>$delete_icon</td>";
        $html[]="</tr>";

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
$(document).ready(function() { $('#table-all-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}