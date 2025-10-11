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

if(isset($_GET["js"])){js();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["new-item-js"])){new_item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_POST["item-add"])){item_add();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_GET["item-delete"])){item_delete();exit;}
if(isset($_POST["item-delete"])){item_delete_perform();exit;}
if(isset($_GET["link-item-js"])){link_item_js();exit;}
if(isset($_GET["link-item-popup"])){link_item_popup();exit;}
if(isset($_GET["search-link"])){link_item_search();exit;}
if(isset($_GET["link-perform"])){link_perform();exit;}
table_start();



function js(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $groupid=intval($_GET["groupid"]);
    unset($_GET["groupid"]);

    $URL_ADDONS=build_suffix();
    $qProxy=new mysql_squid_builder(true);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupType=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $tpl->js_dialog8("{group}: {$ligne["GroupName"]} $GroupType","$page?table-start=yes&gpid=$groupid&{$URL_ADDONS}",850);
}

function build_suffix(){
    if(isset($_GET["jQueryLjs"])){unset($_GET["jQueryLjs"]);}
    if(isset($_GET["_"])){unset($_GET["_"]);}
    if(isset($_GET["build-table"])){unset($_GET["build-table"]);}
    if(isset($_GET["js"])){unset($_GET["js"]);}
    if(isset($_GET["table"])){unset($_GET["table"]);}
    if(isset($_GET["table-start"])){unset($_GET["table-start"]);}
    if(isset($_GET["ID"])){unset($_GET["ID"]);}
    if(isset($_GET["new-item-js"])){unset($_GET["new-item-js"]);}
    if(isset($_GET["item-popup"])){unset($_GET["item-popup"]);}
    if(isset($_GET["link-item-js"])){unset($_GET["link-item-js"]);}
    if(isset($_GET["link-item-popup"])){unset($_GET["link-item-popup"]);}
    if(isset($_GET["search"])){unset($_GET["search"]);}
    if(isset($_GET["search-link"])){unset($_GET["search-link"]);}

    foreach ($_GET as $key=>$val){
        if($key=="table-start"){continue;}
        $cmds[]="$key=$val";
    }
    return @implode("&",$cmds);

}
function new_item_js(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $tpl=new template_admin();
    $groupid=intval($_GET["new-item-js"]);
    $URL_ADDONS=build_suffix();

    $qProxy=new mysql_squid_builder(true);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupType=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $tpl->js_dialog7("{new_object} - {group}: {$ligne["GroupName"]} $GroupType","$page?item-popup=$groupid&{$URL_ADDONS}",850);
}
function link_item_js(){
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $tpl=new template_admin();
    $groupid=intval($_GET["link-item-js"]);
    $URL_ADDONS=build_suffix();

    $qProxy=new mysql_squid_builder(true);
    $ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupType=$qProxy->acl_GroupType[$ligne["GroupType"]];
    $tpl->js_dialog7("{link} - {group}: {$ligne["GroupName"]} $GroupType","$page?link-item-popup=$groupid&{$URL_ADDONS}",850);
}
function link_item_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GroupID=intval($_GET["link-item-popup"]);
    $URL_ADDONS=build_suffix();
    echo $tpl->search_block($page,null,null,null,"&search-link=$GroupID&$URL_ADDONS");

}
function link_perform(){
    $md=$_GET["md"];
    $MainGpid=intval($_GET["link-perform"]);
    $gpid=intval($_GET["gpid"]);
    $run=refresh_get();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $m5=md5("$gpid$MainGpid");
    $q->QUERY_SQL("INSERT INTO webfilters_gpslink (zmd5,groupid,gpid,enabled) 
        VALUES ('$m5','$MainGpid','$gpid',1)");
    if(!$q->ok){
        writelogs("ERROR $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->js_error("SQL Error\n".$q->mysql_error);
        return false;
    }

    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$MainGpid'");
    $ManGropName=$ligne["GroupName"];
    $ligne2=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupNameSlave=$ligne2["GroupName"];
    $GroupType=$ligne2["GroupType"];
    admin_tracks("Link object $GroupNameSlave/$GroupType to $ManGropName");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n$run\n";

    return true;
}

function link_item_search(){
   // Array ( [search] => [search-link] => 0 [myRefresh] => myRefresh1659552890 [js-after] => [function] => ss1659552933 [TableLink] => [RefreshTable] => [ProxyPac] => [firewall] => [types] => )
    $page=CurrentPageName();
    $tpl=new template_admin();
    $maingroup_id=intval($_GET["search-link"]);
    $search=trim($_GET["search"]);
    $search="*$search*";
    $search=str_replace("**","*",$search);
    $search=str_replace("*","%",$search);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $qProxy=new mysql_squid_builder(true);

    $UNSET=array();
    $sql="SELECT webfilters_gpslink.gpid,webfilters_sqgroups.ID
       FROM webfilters_gpslink,webfilters_sqgroups
       WHERE webfilters_gpslink.gpid=webfilters_sqgroups.ID
       AND webfilters_gpslink.groupid=$maingroup_id";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    foreach ($results as $index=>$ligne) {
        $UNSET[$ligne["gpid"]]=true;
    }


    $sql="SELECT ID,GroupName,GroupType,enabled FROM webfilters_sqgroups 
        WHERE (GroupName LIKE '$search') OR (GroupType LIKE '$search')";

    $suffix=build_suffix();
    VERBOSE($sql,__LINE__);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $t=time();
    $html[]="<table id='table-$t' style='margin-top: 15px' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{objects}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;

    if(!$q->ok){echo $tpl->div_error("{mysql_error}:$q->mysql_error");}


    foreach ($results as $index=>$ligne) {
        $class = "font-bold";
        $GroupName = $ligne["GroupName"];
        $GroupType = $ligne["GroupType"];
        $gpid=$ligne["ID"];
        if($GroupType=="AclsGroup"){continue;}
        if(isset($UNSET[$gpid])){continue;}

        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $md=md5(serialize($ligne));

        $js="Loadjs('$page?link-perform=$maingroup_id&gpid=$gpid&md=$md&$suffix');";
        $enabled = intval($ligne["enabled"]);
        $GroupIcon = "<span class='".$qProxy->acl_GroupTypeIcon[$GroupType]."'></span>&nbsp;&nbsp;";
        $GrText = $qProxy->GroupTypeToString($GroupType);
        $button = "<button class='btn btn-primary btn-xs' OnClick=\"$js\">{select}</button>";
        if ($enabled == 0) {
            $class="text-muted";
        }

        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td class=\"$class\" width=50% nowrap>$GroupIcon$GroupName</td>";
        $html[] = "<td class=\"$class\" width=50% nowrap>$GrText <small>($GroupType)</small></td>";
        $html[] = "<td class=\"$class\" width=1% nowrap>$button</td>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
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

    echo $tpl->_ENGINE_parse_body($html);
}


function refresh_get(){
    $js_after=null;
    $RefreshFunction=null;

    $js=array();
    if(isset($_GET["myRefresh"])){
        if (trim($_GET["myRefresh"]) <> null) {
            $js[]=$_GET["myRefresh"]."()";
        }
    }

    if(isset($_GET["RefreshFunction"])) {
        if (trim($_GET["RefreshFunction"]) <> null) {
            $RefreshFunction = base64_decode($_GET["RefreshFunction"]);
        }
    }
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=base64_decode($_GET["js-after"]);
        }
    }


    if($RefreshFunction<>null){
        $js[]="$RefreshFunction()";
    }
    if($js_after<>null){
        $js[]=$js_after;
    }

    return @implode(";",$js);
}

function item_popup(){

    $groupid=intval($_GET["item-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $title="{new_object}";
    $btname="{add}";

    $ligne2=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupNameMaster=$ligne2["GroupName"];
    $title="{new_object} / $GroupNameMaster";
    $tpl->field_hidden("item-add", $groupid);
    $form[]=$tpl->field_text("GroupName","{object_name}","{new_group}");
    $form[]=$tpl->field_acls_groups("GroupType","{type}",null,true);
    $html=$tpl->form_outside($title,$form,null,$btname,refresh_get());
    echo $html;

}
function  item_delete_perform(){
    $tpl=new template_admin();
    $zmd5=$_POST["item-delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_gpslink WHERE zmd5='$zmd5'");
    $groupid=$ligne["groupid"];
    $gpid=$ligne["gpid"];


    $ligne2=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupNameMaster=$ligne2["GroupName"];
    $ligne2=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupNameSlave=$ligne2["GroupName"];

    $q->QUERY_SQL("DELETE FROM webfilters_gpslink WHERE zmd5='$zmd5'");
    if(!$q->ok){echo $q->mysql_error;return false; }
    $text="Proxy: Unlink acl object $GroupNameSlave from $GroupNameMaster";
    admin_tracks($text);
    return true;

}
function item_delete(){
    $tpl=new template_admin();
    $zmd5=$_GET["item-delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_gpslink WHERE zmd5='$zmd5'");
    $groupid=$ligne["groupid"];
    $gpid=$ligne["gpid"];


    $ligne2=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupNameMaster=$ligne2["GroupName"];
    $ligne2=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupNameSlave=$ligne2["GroupName"];

    echo $tpl->js_confirm_delete("{unlink} $GroupNameSlave {from} $GroupNameMaster","item-delete",$zmd5,
        "$('#$zmd5').remove();");


}

function item_enable(){
    $tpl=new template_admin();
    $zmd5=$_GET["item-enable"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_gpslink WHERE zmd5='$zmd5'");
    $groupid=$ligne["groupid"];
    $gpid=$ligne["gpid"];
    $enabled=intval($ligne["enabled"]);

    $ligne2=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$groupid'");
    $GroupNameMaster=$ligne2["GroupName"];
    $ligne2=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$gpid'");
    $GroupNameSlave=$ligne2["GroupName"];

    if($enabled==0){
        $text="Link acls object $GroupNameSlave to acl object group $GroupNameMaster";
        $q->QUERY_SQL("UPDATE webfilters_gpslink SET enabled=1 WHERE zmd5='$zmd5'");
        if(!$q->ok){echo $tpl->js_error_stop($q->mysql_error);return false; }
        admin_tracks($text);
        return true;
    }

    $text="unlink acls object $GroupNameSlave to acl object group $GroupNameMaster";
    $q->QUERY_SQL("UPDATE webfilters_gpslink SET enabled=0 WHERE zmd5='$zmd5'");
    if(!$q->ok){echo $tpl->js_error_stop($q->mysql_error);return false; }
    admin_tracks($text);
    return true;

}

function item_add(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $GroupName=$q->sqlite_escape_string2($_POST["GroupName"]);
    $GroupType=$_POST["GroupType"];
    $MainGPID=$_POST["item-add"];


    $params=md5("$GroupName$GroupType$MainGPID");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='$params'");

    if(intval($ligne["ID"])==0) {
        $sqladd = "INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`) VALUES ('$GroupName','$GroupType','1','0','$params','0',0);";

        $q->QUERY_SQL($sqladd);
        if (!$q->ok) {
            writelogs("ERROR $q->mysql_error $sqladd", __FUNCTION__, __FILE__, __LINE__);
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='$params'");
    $newgpid = $ligne["ID"];
    $m5=md5("$newgpid$MainGPID");
    $q->QUERY_SQL("INSERT INTO webfilters_gpslink (zmd5,groupid,gpid,enabled) 
        VALUES ('$m5','$MainGPID','$newgpid',1)");
    if(!$q->ok){
        writelogs("ERROR $q->mysql_error $sqladd",__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->post_error("SQL Error\n".$q->mysql_error);
        return false;
    }

    $ligne=$q->mysqli_fetch_array("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$MainGPID'");
    $ManGropName=$ligne["GroupName"];

    admin_tracks("Insert a new object $GroupName/$GroupType to $ManGropName");
    return true;

}

function table_start(){
    $page=CurrentPageName();
    $t=time();
    $suffix=build_suffix();
    echo "<div id='table-$t'></div>\n";
    echo "<script>function myRefresh$t(){\n";
    echo "LoadAjax('table-$t','$page?table=yes&myRefresh=myRefresh$t&$suffix');\n";
    echo "}\n";
    echo "myRefresh$t();\n";
    echo "</script>\n";
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $qProxy=new mysql_squid_builder(true);
    $groupid=intval($_GET["gpid"]);
    unset($_GET["gpid"]);
    $suffix=build_suffix();
    $t=time();

    if(!$q->TABLE_EXISTS("webfilters_gpslink")){
        $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `webfilters_gpslink` (
		`zmd5` TEXT NOT NULL PRIMARY KEY ,
		`groupid` INTEGER ,
		`gpid` INTEGER,
		`enabled` INTEGER
		)");
    }

    $sql="SELECT webfilters_gpslink.*,webfilters_sqgroups.GroupType,
       webfilters_sqgroups.GroupName
       FROM webfilters_gpslink,webfilters_sqgroups
       WHERE webfilters_gpslink.gpid=webfilters_sqgroups.ID
       AND webfilters_gpslink.groupid=$groupid";
    


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-item-js=$groupid&$suffix');\">";
    $html[]="<i class='fa fa-plus'></i> {new_object} </label>";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?link-item-js=$groupid&$suffix');\"><i class='fa fa-link'></i> {link_object} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' style='margin-top: 15px' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{objects}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enable}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error("{mysql_error}:$q->mysql_error");}
    $addons=build_suffix();

    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = $ligne["zmd5"];
        $enabled = $ligne["enabled"];
        $GroupType=$ligne["GroupType"];
        $GroupName=$ligne["GroupName"];
        $gpid=intval($ligne["gpid"]);
        $enable=$tpl->icon_check($enabled,"Loadjs('$page?item-enable=$md')","AsDansGuardianAdministrator");
        $GroupTypeText=$qProxy->GroupTypeToString($GroupType);
        $delete=$tpl->icon_delete("Loadjs('$page?item-delete=$md')","AsDansGuardianAdministrator");
        $GroupName=$tpl->td_href($GroupName,null,"Loadjs('fw.rules.items.php?groupid=$gpid&$addons')");
        $ico2=$qProxy->acl_GroupTypeIcon[$GroupType];
        if($ico2<>null){$ico2="<span class='$ico2'></span>&nbsp;&nbsp;";}

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap>{$GroupName}</td>";
        $html[]="<td nowrap>$ico2<strong>$GroupTypeText</strong></td>";
        $html[]="<td style='width:1%;' nowrap class='center'>$enable</td>";
        $html[]="<td style='width:1%;' nowrap class='center'>$delete</td>";
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
	</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


