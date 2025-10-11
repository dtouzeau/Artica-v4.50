<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_GET["enabled-js"])){enabled_js();exit;}
if(isset($_GET["delete-js"])){item_delete();exit;}
if(isset($_POST["ID"])){item_save();exit;}

js();
function enabled_js(){
    $aclid=$_GET["enabled-js"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT pattern,enabled FROM pac_except WHERE ID='$aclid'");
    $enabled=$ligne["enabled"];
    if($enabled==1){$enabled=0;}else{$enabled=1;}
    admin_tracks("Enable = $enabled for proxy.pac {$ligne["pattern"]} exception item $aclid");
    $q->QUERY_SQL("UPDATE pac_except SET enabled=$enabled WHERE ID=$aclid");
    if(!$q->ok){echo $q->mysql_error;}
}
function item_delete(){
    $ID=$_GET["delete-js"];
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT pattern FROM pac_except WHERE ID='$ID'");
    $q->QUERY_SQL("DELETE FROM pac_except WHERE ID='$ID'");
    admin_tracks("Removed {$ligne["pattern"]} for proxy.pac exception item $ID");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
}

function js(){
    $ruelid=$_GET["ruleid"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{exceptfor}","$page?table-start=$ruelid",650);
}
function item_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["item-js"];
    $ruleid=$_GET["ruleid"];

    if($ID==0) {
        $tpl->js_dialog2("{exceptfor}: {new_item}", "$page?item-popup=0&ruleid=$ruleid");
        return;
    }
    $tpl->js_dialog2("{exceptfor}: {item} $ID", "$page?item-popup=$ID&ruleid=$ruleid");
}

function table_start(){
    $page=CurrentPageName();
    $ruelid=intval($_GET["table-start"]);
    $html[]="<div id='exceptfor-$ruelid'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('exceptfor-$ruelid','$page?table=$ruelid');";
    $html[]="</script>";
    echo @implode("\n",$html);
}

function item_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["item-popup"];
    $ruleid=$_GET["ruleid"];
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $title="{item} $ID";
    if($ID==0){
        $btname="{add}";
        $title="{new_item}";
        $ligne["enabled"]=1;
        $ligne["type"]="src";
        $addjs="dialogInstance2.close();";
    }else{
        $addjs=null;
        $btname="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM pac_except WHERE ID=$ID");
    }

    $tpl->field_hidden("ID",$ID);
    $tpl->field_hidden("ruleid",$ruleid);

    $type["src"]="{src}";
    $type["browser"]="{browser}";

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"]);
    $form[]=$tpl->field_array_hash($type,"ztype","{type}",$ligne["type"]);
    $form[]=$tpl->field_text("pattern","{pattern}",$ligne["pattern"]);


    echo $tpl->form_outside($title,$form,null,$btname,
        "LoadAjax('exceptfor-$ruleid','$page?table=$ruleid');LoadAjax('table-loader-proxy-pac','fw.proxypac.rules.php?table=yes');$addjs","AsSquidAdministrator",true);

}
function item_save(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if($_POST["ID"]==0){
        $sql="INSERT INTO pac_except (`type`,`pattern`,`ruleid`) VALUES ('{$_POST["ztype"]}','{$_POST["pattern"]}','{$_POST["ruleid"]}')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
        admin_tracks("Created {$_POST["pattern"]} for proxy.pac rule {$_POST["ruleid"]}");
        return false;

    }


    $q->QUERY_SQL("UPDATE pac_except SET `type`='{$_POST["ztype"]}',
    `pattern`='{$_POST["pattern"]}',ruleid='{$_POST["ruleid"]}' WHERE ID={$_POST["ID"]}");


    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}

    admin_tracks("Updated {$_POST["pattern"]} for proxy.pac rule {$_POST["ruleid"]}");

}

function table(){
    $t=time();
    $page=CurrentPageName();
    $ruleid=intval($_GET["table"]);
    $tpl=new template_admin();
    $TRCLASS=null;
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $add="Loadjs('$page?item-js=0&ruleid=$ruleid');";

    $sql="CREATE TABLE IF NOT EXISTS `pac_except` (
            `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`ruleid` INTEGER NOT NULL,
			`type` TEXT,
			`pattern` TEXT ,
			`enabled` INTEGER NOT NULL DEFAULT 1)";
    $q->QUERY_SQL($sql);
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>";
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{pattern}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{type}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{enable}</th>";
    $html[]="<th data-sortable=false>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $sql="SELECT * FROM pac_except WHERE ruleid=$ruleid ORDER BY pattern ASC";
    $results=$q->QUERY_SQL($sql);

    $type["src"]="{src}";
    $type["browser"]="{browser}";

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $editjs="Loadjs('$page?item-js=$ID&ruleid=$ruleid');";
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enabled-js=$ID')",null,null,"AsSquidAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsSquidAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><strong>". $tpl->td_href($ligne["pattern"],null,$editjs)."</strong></td>";
        $html[]="<td width=1% nowrap>{$type[$ligne["type"]]}</center></td>";
        $html[]="<td width=1% nowrap>$check</td>";
        $html[]="<td width=1% class='center' nowrap>$delete</center></td>";
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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

	echo $tpl->_ENGINE_parse_body($html);
}
