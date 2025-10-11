<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["start-table"])){start_table();exit;}
if(isset($_POST["NewGroup"])){group_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_GET["new-group-popup"])){new_group_popup();exit;}
if(isset($_GET["group-enable"])){group_enable();exit;}
if(isset($_GET["group-delete"])){group_delete();exit;}
if(isset($_POST["group-delete"])){group_delete_perform();exit;}
if(isset($_GET["ID"])){group_js();exit;}
if(isset($_GET["ID-tab"])){group_tab();exit;}
if(isset($_GET["categories-start"])){categories_start();exit;}
if(isset($_GET["categories-table"])){categories_table();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["unlink-category"])){category_unlink();}
if(isset($_POST["groupname"])){settings_save();exit;}
js();

function group_delete(){
    $ID=intval($_GET["group-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM webfilter_blkgp WHERE ID=$ID");
    $tpl=new template_admin();
    $tpl->js_confirm_delete($ligne["groupname"],"group-delete",$ID,"$('#{$_GET["md"]}').remove();");
}

function group_delete_perform(){
    $ID=intval($_POST["group-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL("DELETE FROM webfilter_blklnk WHERE `webfilter_blkid`='$ID'");
    $q->QUERY_SQL("DELETE FROM webfilter_blkcnt WHERE `webfilter_blkid`='$ID'");
    $q->QUERY_SQL("DELETE FROM webfilter_blkgp WHERE ID=$ID");
}

function group_enable(){
    $ID=intval($_GET["group-enable"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM webfilter_blkgp WHERE ID=$ID");
    $enabled=intval($ligne["enabled"]);
    if($enabled==1){
        $q->QUERY_SQL("UPDATE webfilter_blkgp SET enabled=0 WHERE ID=$ID");
    }else{
        $q->QUERY_SQL("UPDATE webfilter_blkgp SET enabled=1 WHERE ID=$ID");
    }
    if(!$q->ok){
        $tpl=new template_admin();
        $tpl->js_mysql_alert($q->mysql_error);
    }
}

function category_unlink(){
    $ID     = intval($_GET["unlink-category"]);
    header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL("DELETE FROM webfilter_blkcnt WHERE ID='$ID'");
    echo "$('#catz-{$_GET["md"]}').remove();\n";

}

function settings_save(){
    $ID=$_POST["groupid"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL("UPDATE webfilter_blkgp SET groupname='{$_POST["groupname"]}' WHERE ID=$ID");
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);}
}

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{categories_groups}","$page?start-table=yes",980);

}
function new_group_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog3("{categories_groups}: {new_group}","$page?new-group-popup=yes",550);
}
function group_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["ID"];
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM webfilter_blkgp WHERE ID='$ID'");
    $tpl->js_dialog3("{categories_groups}: {$ligne["groupname"]}","$page?ID-tab=$ID",650);
}
function group_tab(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["ID-tab"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM webfilter_blkgp WHERE ID='$ID'");
    $title=$ligne["groupname"];

    if($ID>0){
        $array["{categories}"]="$page?categories-start=$ID";
        $array["$title"]="$page?settings=$ID";

    }

    echo $tpl->tabs_default($array);
}
function categories_start(){
    $page=CurrentPageName();
    $ID=$_GET["categories-start"];
    echo "<div id='categories-table-$ID-div' style='margin-top:10px'></div><script>LoadAjax('categories-table-$ID-div','$page?categories-table=$ID');</script>";
}


function new_group_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $form[]=$tpl->field_text("NewGroup","{group name}",null,true);
    echo $tpl->form_outside("{categories_groups}: {new_group}",$form,null,"{add}","dialogInstance3.close();LoadAjax('categories-group-main-div','$page?table=yes')","AsDansGuardianAdministrator");

}
function settings(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["settings"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_blkgp WHERE ID=$ID");
    $tpl->field_hidden("groupid",$ID);
    $form[]=$tpl->field_text("groupname","{group name}",$ligne["groupname"],true);
    echo $tpl->form_outside("{categories_groups}: {$ligne["groupname"]}",$form,null,"{apply}","LoadAjax('categories-group-main-div','$page?table=yes')","AsDansGuardianAdministrator");
}

function start_table(){
    $page=CurrentPageName();
    echo "<div id='categories-group-main-div'></div><script>LoadAjax('categories-group-main-div','$page?table=yes');</script>";
}
function table(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $TRCLASS    = null;

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-group-js=yes');\"><i class='fa fa-plus'></i> {new_group} </label>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"LoadAjax('categories-group-main-div','$page?table=yes');\"><i class='far fa-sync-alt'></i> {refresh} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{groups2}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{categories}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rules}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{enabled}</th>";
    $html[]="<th data-sortable=false width=1%>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT * FROM webfilter_blkgp ORDER BY groupname");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $groupname  = $ligne["groupname"];
        $ID         = $ligne["ID"];
        $md         = md5($groupname.$ID);


        $ligne2=$q->mysqli_fetch_array("SELECT COUNT(`category`) AS CountDeCats FROM webfilter_blkcnt WHERE `webfilter_blkid`='$ID'");
        $Categories=intval($ligne2["CountDeCats"]);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' nowrap class='center'><i class=\"fas fa-folder-tree\"></i></td>";
        $html[]="<td>". $tpl->td_href($groupname,$groupname,"Loadjs('$page?ID=$ID')")."</td>";
        $html[]="<td width='1%' nowrap class='center'>$Categories</td>";
        $html[]="<td width='1%' nowrap class='center'>". $tpl->icon_delete("Loadjs('$page?group-delete=$ID&md=$md')","AsDansGuardianAdministrator")."</td>";
        $html[]="<td width='1%' nowrap class='center'>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?group-enable=$ID')",null,"AsDansGuardianAdministrator")."</td>";
        $html[]="<td width='1%' nowrap class='center'></td>";
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
    $html[]="";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function categories_table(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $TRCLASS    = null;
    $ID         = intval($_GET["categories-table"]);

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.ufdb.rules.categories.php?category-add-js=yes&groupid=$ID');\"><i class='fa fa-plus'></i> {link_categories} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' >ID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' >{categories}</th>";
    $html[]="<th data-sortable=false width=1%>{unlink}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $catz=new mysql_catz();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT * FROM webfilter_blkcnt WHERE `webfilter_blkid`='$ID'");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $UfdbMasterCache    = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
    $qp                 = new postgres_sql();

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $categoryid         = $ligne["category"];
        $md                 = md5(serialize($ligne));
        $categoryname       = $catz->CategoryIntToStr($categoryid);
        $ID                 = $ligne["ID"];

        $official_category  = 0;
        $free_category      = 0;
        $items              = "-";

        $ligne2             = $qp->mysqli_fetch_array("SELECT * FROM personal_categories WHERE category_id = $categoryid");
        $category_icon      = $ligne2["category_icon"];

        if($category_icon==null){
            $category_icon  = "img/20-categories-personnal.png";
        }

        if(isset($UfdbMasterCache[$categoryid]["official_category"])){
            $official_category=$UfdbMasterCache[$categoryid]["official_category"];
        }
        if(isset($UfdbMasterCache[$categoryid]["free_category"])){
            $free_category=$UfdbMasterCache[$categoryid]["free_category"];
        }

        if($official_category==1){$items=FormatNumber($UfdbMasterCache[$categoryid]["items"]) ." {items}";}
        if($free_category==1){$items=FormatNumber($UfdbMasterCache[$categoryid]["items"])." {items}";}
        $item_text=null;
        if($items>0){
            $item_text="<small>$items {records}</small>";
        }
        $html[]="<tr class='$TRCLASS' id='catz-$md'>";
        $html[]="<td width='1%' nowrap class='center'><small>$ID</small></td>";
        $html[]="<td width='1%' nowrap class='center'><img src='$category_icon'></td>";
        $html[]="<td width='98%'><strong>$categoryname&nbsp;$item_text</strong></td>";
        $html[]="<td width='1%' nowrap class='center'>". $tpl->icon_unlink("Loadjs('$page?unlink-category=$ID&md=$md')","AsDansGuardianAdministrator")."</td>";
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
    $html[]="";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function group_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $q->QUERY_SQL("INSERT INTO `webfilter_blkgp` (groupname,enabled) VALUES ('{$_POST["NewGroup"]}','1')");
    if(!$q->ok){echo $q->mysql_error;}

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}