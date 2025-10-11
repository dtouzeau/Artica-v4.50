<?php
$GLOBALS["ICON_FAMILY"]="PARAMETERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');

include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once('ressources/class.samba.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$usersmenus=new usersMenus();

if($usersmenus->AsArticaAdministrator==false){header('location:users.index.php');exit;}

if(isset($_GET["start"])){table_start();exit;}
if(isset($_GET["acl-js"])){acl_js();exit;}
if(isset($_GET["acl-tabs"])){acl_tabs();exit;}
if(isset($_GET["add-popup"])){acl_popup();exit;}
if(isset($_GET["acl-popup"])){acl_popup();exit;}
if(isset($_POST["id"])){acl_save();exit;}

if(isset($_GET["hosts-start"])){hosts_start();exit;}
if(isset($_GET["hosts-table"])){hosts_table();exit;}
if(isset($_GET["hosts-link"])){hosts_link();exit;}
if(isset($_GET["hosts-link-table"])){hosts_link_table();exit;}
if(isset($_GET["hosts-link-perform"])){hosts_link_perform();exit;}
if(isset($_GET["hosts-unlink"])){hosts_unlink();exit;}

if(isset($_GET["users-start"])){users_start();exit;}
if(isset($_GET["users-table"])){users_table();exit;}
if(isset($_GET["users-link"])){users_link();exit;}
if(isset($_GET["users-link-table"])){users_link_table();exit;}
if(isset($_GET["users-link-perform"])){users_link_perform();exit;}
if(isset($_GET["users-unlink"])){users_unlink();exit;}

if(isset($_GET["acl-delete"])){acl_delete();exit;}
if(isset($_POST["acl-delete"])){acl_delete_real();exit;}
if(isset($_GET["fill"])){group_fill();exit;}
table();


function users_unlink(){
    $groupid        = intval($_GET["users-unlink"]);
    $aclid          = intval($_GET["aclid"]);
    $md             = $_GET["md"];
    $q              = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl            = new template_admin();
    $page           = CurrentPageName();

    $q->QUERY_SQL("DELETE FROM user_group_acls WHERE acl_id=$aclid AND user_group_id=$groupid");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "Loadjs('$page?fill=$aclid');";

}

function hosts_unlink(){
    $groupid        = intval($_GET["hosts-unlink"]);
    $aclid          = intval($_GET["aclid"]);
    $md             = $_GET["md"];
    $q              = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl            = new template_admin();
    $page           = CurrentPageName();

    $q->QUERY_SQL("DELETE FROM host_group_acls WHERE acl_id=$aclid AND host_group_id=$groupid");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "Loadjs('$page?fill=$aclid');";

}

function hosts_link_perform(){
    $groupid        = intval($_GET["hosts-link-perform"]);
    $aclid          = intval($_GET["aclid"]);
    $md             = $_GET["md"];
    $q              = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl            = new template_admin();
    $page           = CurrentPageName();

    $q->QUERY_SQL("INSERT INTO host_group_acls (acl_id,host_group_id) VALUES ($aclid,$groupid)");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('sshportal-hosts-$aclid','$page?hosts-table=yes&aclid=$aclid');\n";
    echo "Loadjs('$page?fill=$aclid');";

}

function users_link_perform(){
    $groupid        = intval($_GET["users-link-perform"]);
    $aclid          = intval($_GET["aclid"]);
    $md             = $_GET["md"];
    $q              = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl            = new template_admin();
    $page           = CurrentPageName();

    $q->QUERY_SQL("INSERT INTO user_group_acls (acl_id,user_group_id) VALUES ($aclid,$groupid)");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('sshportal-users-$aclid','$page?users-table=yes&aclid=$aclid');\n";
    echo "Loadjs('$page?fill=$aclid');";



}

function users_start(){
    $page   = CurrentPageName();
    $html   = "<div id='sshportal-users-{$_GET["aclid"]}' style='margin-top: 10px'></div><script>LoadAjax('sshportal-users-{$_GET["aclid"]}','$page?users-table=yes&aclid={$_GET["aclid"]}');</script>";
    echo $html;

}

function hosts_start(){
    $page   = CurrentPageName();
    $html   = "<div id='sshportal-hosts-{$_GET["aclid"]}' style='margin-top: 10px'></div><script>LoadAjax('sshportal-hosts-{$_GET["aclid"]}','$page?hosts-table=yes&aclid={$_GET["aclid"]}');</script>";
    echo $html;

}
function users_link(){
    $aclid  = intval($_GET["users-link"]);
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_dialog4("{link_group}","$page?users-link-table=$aclid",850);
}
function hosts_link(){
    $aclid  = intval($_GET["hosts-link"]);
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_dialog4("{link_group}","$page?hosts-link-table=$aclid",850);
}

function table_start(){
    $page   = CurrentPageName();
    $html   = "<div id='sshportal-acls-table' style='margin-top: 10px'></div><script>LoadAjax('sshportal-acls-table','$page?table=yes');</script>";
    echo $html;
}

function acl_js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $id     = intval($_GET["acl-js"]);
    $title  = "{new_rule}";

    if($id>0){
        $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
        $ligne  =   $q->mysqli_fetch_array("SELECT comment FROM acls WHERE id='$id'");
        $title  =   $ligne["comment"];
        $tpl->js_dialog1($title,"$page?acl-tabs=$id",850);
        return;
    }
    $tpl->js_dialog1($title,"$page?acl-popup=$id",850);

}
function acl_tabs(){
    $page   = CurrentPageName();
    $tpl    =  new template_admin();
    $id     =  intval($_GET["acl-tabs"]);
    $q      =  new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  =  $q->mysqli_fetch_array("SELECT comment FROM acls WHERE id='$id'");
    $title  =  $ligne["comment"];

    $array[$title]="$page?acl-popup=$id";
    $array["{hosts_groups}"]="$page?hosts-start=yes&aclid=$id";
    $array["{members_groups}"]="$page?users-start=yes&aclid=$id";
    echo $tpl->tabs_default($array);

}

function acl_delete(){
    $tpl    = new template_admin();
    $id     = intval($_GET["acl-delete"]);
    $md     = $_GET["md"];
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  =   $q->mysqli_fetch_array("SELECT comment FROM acls WHERE id='$id'");
    $title  =   $ligne["comment"];

    $tpl->js_confirm_delete($title,"acl-delete",$id,"$('#$md').remove()");

}

function acl_delete_real(){
    $aclid  =   $_POST["id"];
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("DELETE FROM acls WHERE id='$aclid'");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM host_group_acls WHERE acl_id=$aclid");
    $q->QUERY_SQL("DELETE FROM user_group_acls WHERE acl_id=$aclid");

}

function acl_popup(){
    $id     = intval($_GET["acl-popup"]);
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $name   = null;
    $weight = 0;
    $comment= null;
    $title  = "{new_rule}";
    $btname = "{add}";
    $jsafter= "dialogInstance1.close();LoadAjax('sshportal-acls-table','$page?table=yes')";

    if($id>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM acls WHERE id='$id'");
        if(!$q->ok){echo $q->mysql_error_html(true);}
        $comment    = $ligne["comment"];
        $action     = $ligne["action"];
        $title      = $name;
        $btname     = "{apply}";
        $jsafter    = "Loadjs('$page?fill=$id');";
    }

    $actions["allow"]="{allow}";
    $actions["deny"]="{deny}";


    $tpl->field_hidden("id",$id);
    $form[]=$tpl->field_array_hash($actions,"action","{rule}",$action,true);
    $form[]=$tpl->field_text("comment","{rulename}",$comment,false);
    $form[]=$tpl->field_numeric("weight","{weight}",$weight);
    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsSystemAdministrator");
}

function acl_save(){
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl->CLEAN_POST();
    $comment    = $q->sqlite_escape_string2($_POST["comment"]);
    $id         = intval($_POST["id"]);
    $created_at = date("Y-m-d H:i:s");
    $action     = $_POST["action"];
    $weight     = intval($_POST["weight"]);

    if($action==null){$action="allow";}

    if($comment==null){
        $Adm=$_SESSION["uid"];
        if($Adm==-100){$Adm="Manager";}
        $comment="{createdate} $created_at {by} $Adm";
    }

    if($id==0){
        $q->QUERY_SQL("INSERT INTO acls (created_at,updated_at,comment,`action`,weight) 
        VALUES ('$created_at','$created_at','$comment','$action','$weight')");
        if(!$q->ok){echo $q->mysql_error_html(true);}
        return true;
    }

    $sql="UPDATE acls SET `comment`='$comment',`action`='$action',weight='$weight',updated_at='$created_at' WHERE id='$id'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."<hr>$sql";}

}
function group_fill(){
    $page           = CurrentPageName();
    $id             = intval($_GET["fill"]);
    $q              = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne          = $q->mysqli_fetch_array("SELECT * FROM acls WHERE id='$id'");
    $tpl            = new template_admin();
    $ID             = $ligne["id"];
    $md             = md5(serialize($ligne));
    $comment        = $ligne["comment"];
    $action         = $ligne["action"];
    $weight         = $ligne["weight"];
    $HOSTS          = array();
    $USERS          = array();
    $delete         = $tpl->icon_delete("Loadjs('$page?acl-delete=$ID&md=$md')","AllowAddUsers");
    $icon_servers   = "<i class='fa fa-server'></i>&nbsp;";
    $icon_users     = "<i class='fas fa-users'></i>&nbsp;";



    $results2       = $q->QUERY_SQL("SELECT user_group_id FROM user_group_acls WHERE acl_id=$ID");
    foreach ($results2 as $index2=>$ligne2) {
        $user_group_id = intval($ligne2["user_group_id"]);
        $ligne3 = $q->mysqli_fetch_array("SELECT name FROM user_groups WHERE id=$user_group_id");
        $HotUserName = trim($ligne3["name"]);
        $USERS[] = $HotUserName;
    }


    $results2       = $q->QUERY_SQL("SELECT host_group_id FROM host_group_acls WHERE acl_id=$ID");
    foreach ($results2 as $index2=>$ligne2) {
        $host_group_id = intval($ligne2["host_group_id"]);
        $ligne3 = $q->mysqli_fetch_array("SELECT name FROM host_groups WHERE id=$host_group_id");
        $HotGroupName = trim($ligne3["name"]);
        $HOSTS[] = $HotGroupName;
    }


    if(count($HOSTS)==0){
        $icon_servers=$tpl->icon_nothing();
    }

    if(count($USERS)==0){
        $icon_users=$tpl->icon_nothing();
    }


    $users_text=str_replace("'","\'","$icon_users".@implode(", ",$USERS));
    $servers_text=str_replace("'","\'","$icon_servers".@implode(", ",$HOSTS));



    $comment=str_replace("'","\'",$comment);
    header("content-type: application/x-javascript");
    $f[]="if( document.getElementById('$id-rname') ){";
    $f[]="\tdocument.getElementById('$id-rname').innerHTML='$comment';";
    $f[]="\tdocument.getElementById('$id-rusergroup').innerHTML='$users_text';";
    $f[]="\tdocument.getElementById('$id-rhostgroup').innerHTML='$servers_text';";
    $f[]="}";
    echo @implode("\n",$f);

}

function users_table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $aclid      = intval($_GET["aclid"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]=$tpl->button_label_table("{link_group}", "Loadjs('$page?users-link=$aclid')", "fas fa-link","AllowAddUsers");
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{groupname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS = null;

    $results=$q->QUERY_SQL("SELECT * FROM user_group_acls WHERE acl_id=$aclid");

    if(!$q->ok){echo $q->mysql_error_html(true);}

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id             = $ligne["id"];
        $md             = md5(serialize($ligne));
        $user_group_id  = $ligne["user_group_id"];
        $delete         = $tpl->icon_unlink("Loadjs('$page?users-unlink=$user_group_id&md=$md&aclid=$aclid')","AllowAddUsers");
        $ligne2         = $q->mysqli_fetch_array("SELECT name,comment FROM user_groups WHERE id=$user_group_id");

        if($aclid==1) {
            if ($user_group_id == 1) {
                $delete = $tpl->icon_nothing();
            }
        }


        $name=$ligne2["name"];
        $comment=$ligne2["comment"];
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fa fa-users'></i>&nbsp<strong>$name</strong></td>";
        $html[]="<td class=\"\">$comment</td>";
        $html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
        $html[]="</tr>";
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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function hosts_table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $aclid      = intval($_GET["aclid"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]=$tpl->button_label_table("{link_group}", "Loadjs('$page?hosts-link=$aclid')", "fas fa-link","AllowAddUsers");
    $html[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{groupname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS = null;


    $results=$q->QUERY_SQL("SELECT * FROM host_group_acls WHERE acl_id=$aclid");

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id             = $ligne["id"];
        $md             = md5(serialize($ligne));
        $host_group_id  = $ligne["host_group_id"];
        $delete         = $tpl->icon_unlink("Loadjs('$page?hosts-unlink=$host_group_id&md=$md&aclid=$aclid')","AllowAddUsers");
        $ligne2         = $q->mysqli_fetch_array("SELECT name,comment FROM host_groups WHERE id=$host_group_id");
        if($host_group_id==1){
            $delete=$tpl->icon_nothing();
        }
        $name=$ligne2["name"];
        $comment=$ligne2["comment"];
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fa fa-server'></i>&nbsp<strong>$name</strong></td>";
        $html[]="<td class=\"\">$comment</td>";
        $html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
        $html[]="</tr>";


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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function users_link_table(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $t      = time();
    $aclid  = intval($_GET["users-link-table"]);
    $ALREADY= array();
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");


    $results=$q->QUERY_SQL("SELECT * FROM user_group_acls WHERE acl_id='$aclid'");
    foreach ($results as $index=>$ligne){
        $ALREADY[$ligne["user_group_id"]]=true;
    }
    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='100%' nowrap>{groups2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=$q->QUERY_SQL("SELECT * FROM user_groups ORDER BY name");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $userid=$ligne["id"];
        if(isset( $ALREADY[$userid])){continue;}
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["id"];
        $hostname=$ligne["name"];
        $comment=$ligne["comment"];
//<i class="fas fa-link"></i>
        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fa fa-users'></i>&nbsp;$hostname <small>$comment</small></td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?users-link-perform=$ID&md=$md&aclid=$aclid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function hosts_link_table(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $t      = time();
    $aclid  = intval($_GET["hosts-link-table"]);
    $ALREADY= array();
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");


    $results=$q->QUERY_SQL("SELECT * FROM host_group_acls WHERE acl_id='$aclid'");
    foreach ($results as $index=>$ligne){
        $host_group_id=$ligne["host_group_id"];
        $ALREADY[$host_group_id]=true;
    }
    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='100%' nowrap>{groups2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $results=$q->QUERY_SQL("SELECT * FROM host_groups ORDER BY name");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $userid=$ligne["id"];
        if(isset( $ALREADY[$userid])){continue;}
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["id"];
        $hostname=$ligne["name"];
        $comment=$ligne["comment"];
//<i class="fas fa-link"></i>
        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fa fa-server'></i>&nbsp;$hostname <small>$comment</small></td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?hosts-link-perform=$ID&md=$md&aclid=$aclid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]=$tpl->button_label_table("{new_rule}", "Loadjs('$page?acl-js=0')", "fas fa-plus","AllowAddUsers")."</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{rule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{hosts_groups}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{groups2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $sql="SELECT * FROM acls ORDER BY weight ASC";

    


    $results=$q->QUERY_SQL($sql);
//<i class="fas fa-tasks"></i>
//	print_r($hash_full);
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID             = $ligne["id"];
        $md             = md5(serialize($ligne));
        $comment        = $ligne["comment"];
        $action         = $ligne["action"];
        $weight         = $ligne["weight"];
        $HOSTS          = array();
        $USERS          = array();
        $delete         = $tpl->icon_delete("Loadjs('$page?acl-delete=$ID&md=$md')","AllowAddUsers");
        $icon_servers   = "<i class='fa fa-server'></i>&nbsp;";
        $icon_users     = "<i class='fas fa-users'></i>&nbsp;";

        $results2       = $q->QUERY_SQL("SELECT user_group_id FROM user_group_acls WHERE acl_id=$ID");
        foreach ($results2 as $index2=>$ligne2) {
            $user_group_id = intval($ligne2["user_group_id"]);
            $ligne3 = $q->mysqli_fetch_array("SELECT name FROM user_groups WHERE id=$user_group_id");
            $HotUserName = trim($ligne3["name"]);
            $USERS[] = $HotUserName;
        }


        $results2       = $q->QUERY_SQL("SELECT host_group_id FROM host_group_acls WHERE acl_id=$ID");
        foreach ($results2 as $index2=>$ligne2) {
            $host_group_id = intval($ligne2["host_group_id"]);
            $ligne3 = $q->mysqli_fetch_array("SELECT name FROM host_groups WHERE id=$host_group_id");
            $HotGroupName = trim($ligne3["name"]);
            $HOSTS[] = $HotGroupName;
        }


        if(count($HOSTS)==0){
            $icon_servers=$tpl->icon_nothing();
        }

        if(count($USERS)==0){
            $icon_users=$tpl->icon_nothing();
        }


        if($ID==1){$delete=$tpl->icon_nothing();}

        $js="Loadjs('$page?acl-js=$ID')";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fas fa-tasks'></i>&nbsp;".$tpl->td_href("<span id='$ID-rname'>{{$action}}</span>","{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"\"><span id='$ID-rcomment'>$comment</span></td>";
        $html[]="<td class=\"\"><span id='$ID-rusergroup'>$icon_servers". @implode(", ",$HOSTS)."</span></td>";
        $html[]="<td class=\"\" ><span id='$ID-rhostgroup'>$icon_users". @implode(", ",$USERS)."</span></td>";
        $html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));




}