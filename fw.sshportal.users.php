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

if(isset($_GET["users-groups-start"])){users_groups_start();exit;}
if(isset($_GET["users-groups-table"])){users_groups_table();exit;}
if(isset($_GET["group-unlink"])){users_groups_unlink();exit;}
if(isset($_GET["users-groups-link"])){users_groups_link();exit;}
if(isset($_GET["users-groups-link-table"])){users_groups_link_table();exit;}
if(isset($_GET["users-groups-link-perform"])){users_groups_link_perform();exit;}


if(isset($_GET["start"])){table_start();exit;}
if(isset($_GET["user-js"])){user_js();exit;}
if(isset($_GET["user-tabs"])){user_tabs();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_GET["user-popup"])){user_popup();exit;}
if(isset($_POST["id"])){user_save();exit;}
if(isset($_GET["user-delete"])){user_delete();exit;}
if(isset($_POST["user-delete"])){user_delete_real();exit;}
if(isset($_GET["fill"])){user_fill();exit;}
if(isset($_GET["member-link"])){member_link_js();exit;}
if(isset($_GET["member-link-table"])){member_link_table();exit;}
if(isset($_GET["member-link-perform"])){member_link_perform();exit;}
if(isset($_GET["user-unlink"])){member_unlink();exit;}
table();


function member_link_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["member-link"]);
    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne=$q->mysqli_fetch_array("SELECT name FROM user_groups WHERE ID=$ID");
    $title=$ligne["name"];
    $tpl->js_dialog3($title." {link} {members}","$page?member-link-table=$ID",500);

}
function member_link_perform(){
    $page       = CurrentPageName();
    $userid     = $_GET["member-link-perform"];
    $md         = $_GET["md"];
    $gpid       = $_GET["gpid"];
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("INSERT INTO user_user_groups (user_id,user_group_id) VALUES ($userid,$gpid)");
    if(!$q->ok){echo $q->mysql_error;return;}

    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE users SET updated_at='$updated_at' WHERE id='$userid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('sshportal-users-table','$page?table=yes&groupid=$gpid');";
    echo "Loadjs('fw.sshportal.groups.php?fill=$gpid');\n";
}

function member_unlink(){
    $userid     = intval($_GET["user-unlink"]);
    $md         = $_GET["md"];
    $gpid       = intval($_GET["gpid"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("DELETE FROM user_user_groups WHERE user_id=$userid AND user_group_id=$gpid");
    if(!$q->ok){echo $q->mysql_error;return;}
    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE users SET  updated_at='$updated_at' WHERE id='$userid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "Loadjs('fw.sshportal.groups.php?fill=$gpid');\n";

}


function member_link_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $gpid=intval($_GET["member-link-table"]);
    $ALREADY=array();
    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $results=$q->QUERY_SQL("SELECT * FROM user_user_groups WHERE user_group_id='$gpid'");
    foreach ($results as $index=>$ligne){
        $userid=$ligne["user_id"];
        $ALREADY[$userid]=true;


    }

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $results=$q->QUERY_SQL("SELECT * FROM users order by name");

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $userid=$ligne["id"];
        if(isset( $ALREADY[$userid])){continue;}
        $md=md5(serialize($ligne));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["id"];
        $username=$ligne["name"];
        $comment=$ligne["comment"];
//<i class="fas fa-link"></i>
        $gps=array();
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fa fa-user'></i>&nbsp;$username <small>$comment</small></td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?member-link-perform=$ID&md=$md&gpid=$gpid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
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
function table_start(){
    $page   = CurrentPageName();
    if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
    $groupid=intval($_GET["groupid"]);
    $html   = "<div id='sshportal-users-table' style='margin-top: 10px'></div><script>LoadAjax('sshportal-users-table','$page?table=yes&groupid=$groupid');</script>";
    echo $html;
}

function user_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $id         = intval($_GET["user-js"]);
    $groupid    = intval($_GET["groupid"]);
    $title      = "{new_member}";
    $groupname  = null;
    $q          =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");

    if($groupid>0){
        $ligne  =   $q->mysqli_fetch_array("SELECT name FROM user_groups WHERE id='$groupid'");
        $groupname="/{$ligne["name"]}";
    }

    if($id>0){

        $ligne  =   $q->mysqli_fetch_array("SELECT name FROM users WHERE id='$id'");
        $title  =   $ligne["name"];
        $tpl->js_dialog2($title.$groupname,"$page?user-tabs=$id&groupid=$groupid",850);
        return;
    }
    $tpl->js_dialog2($title.$groupname,"$page?user-popup=$id&groupid=$groupid",850);

}
function users_groups_start(){
    if(!isset($_GET["groupid"])){$_GET["groupid"]=0;}
    $groupid=intval($_GET["groupid"]);
    $userid=intval($_GET["users-groups-start"]);
    $page   = CurrentPageName();
    $html   = "<div id='sshportal-host-$userid' style='margin-top: 10px'></div><script>LoadAjax('sshportal-host-$userid','$page?users-groups-table=$userid&groupid=$groupid');</script>";
    echo $html;
}
function users_groups_unlink(){
    $gpid       = intval($_GET["group-unlink"]);
    $groupid    = intval($_GET["groupid"]);
    $md         = $_GET["md"];
    $userid     = intval($_GET["userid"]);
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");

    $q->QUERY_SQL("DELETE FROM user_user_groups WHERE user_id=$userid AND user_group_id=$gpid");
    if(!$q->ok){echo $q->mysql_error;return;}
    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE users SET  updated_at='$updated_at' WHERE id='$userid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "Loadjs('fw.sshportal.hostsgroups.php?fill=$gpid');\n";

}
function users_groups_link(){
    //$page?server-groups-link=$hostid&groupid=$groupid
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $userid = intval($_GET["users-groups-link"]);
    $groupid= intval($_GET["groupid"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  = $q->mysqli_fetch_array("SELECT name FROM users WHERE id=$userid");
    $title  = $ligne["name"];
    $tpl->js_dialog3($title." {link_group}","$page?users-groups-link-table=$userid&groupid=$groupid",500);
}
function users_groups_link_perform(){
    $page       = CurrentPageName();
    $userid     = $_GET["userid"];
    $md         = $_GET["md"];
    $gpid       = $_GET["users-groups-link-perform"];
    $groupid    = $_GET["groupid"];
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("INSERT OR IGNORE INTO user_user_groups (user_id,user_group_id) VALUES ($userid,$gpid)");
    if(!$q->ok){echo $q->mysql_error;return;}

    $updated_at=date("Y-m-d H:i:s");
    $q->QUERY_SQL("UPDATE users SET updated_at='$updated_at' WHERE id='$userid'");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "LoadAjax('sshportal-host-$userid','$page?users-groups-table=$userid&groupid=$groupid');";
    echo "Loadjs('fw.sshportal.hostsgroups.php?fill=$gpid');\n";
}
function users_groups_link_table(){
    //          server-groups-link-table=$hostid&groupid=$groupid
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $userid     = intval($_GET["users-groups-link-table"]);
    $groupid    = intval($_GET["groupid"]);

    $ALREADY=array();
    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $results=$q->QUERY_SQL("SELECT * FROM user_user_groups WHERE user_id='$userid'");
    foreach ($results as $index=>$ligne){
        $ALREADY[$ligne["user_group_id"]]=true;
    }

    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups2}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>&nbsp;</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";



    $results=$q->QUERY_SQL("SELECT * FROM host_groups order by name");
    if(!$q->ok){echo $q->mysql_error_html(true);}

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $id     = $ligne["id"];
        $md     = md5(serialize($ligne));
        $comment= $ligne["comment"];
        $name   = $ligne["name"];
        if(isset( $ALREADY[$id])){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='100%' nowrap><i class='fa fa-users'></i>&nbsp;$name <small>$comment</small></td>";
        $html[]="<td class=\"\" width=1% nowrap>".$tpl->button_autnonome("{link}","Loadjs('$page?users-groups-link-perform=$id&md=$md&userid=$userid&groupid=$groupid')","fas fa-link","AllowAddUsers",0,"btn-primary","small") ."</td>";
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
function users_groups_table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $groupid    = intval($_GET["groupid"]);
    $userid     = intval($_GET["users-groups-table"]);
    $row_delete = "{unlink}";
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $TRCLASS    = null;

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px;'>";
    $html[]=$tpl->button_label_table("{link_group}", "Loadjs('$page?users-groups-link=$userid&groupid=$groupid')", "fas fa-users","AllowAddUsers");
    $html[]="</div>";
    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$row_delete</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=$q->QUERY_SQL("SELECT * FROM user_user_groups WHERE user_id=$userid");
    foreach ($results as $index=>$ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $md            = md5(serialize($ligne));
        $host_group_id = $ligne["user_group_id"];
        $ligne2        = $q->mysqli_fetch_array("SELECT name,comment FROM host_groups WHERE id=$host_group_id");
        $name          = $ligne2["name"];
        $comment       = $ligne2["comment"];
        $delete        = $tpl->icon_unlink("Loadjs('$page?group-unlink=$host_group_id&md=$md&userid=$userid&groupid=$groupid')","AllowAddUsers");

        if($userid==1) {
            if ($host_group_id == 1) {
                $delete = $tpl->icon_nothing();
            }
        }



        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td class=\"\" width='1%' nowrap><i class='fas fa-server'></i>&nbsp;<strong>$name</strong></td>";
        $html[] = "<td class=\"\">$comment</td>";
        $html[] = "<td class=\"center\" width=1% nowrap>$delete</td>";
        $html[] = "</tr>";

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

function user_tabs(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $id     = intval($_GET["user-tabs"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  = $q->mysqli_fetch_array("SELECT name FROM users WHERE id='$id'");
    $title  = $ligne["name"];
    $groupid= intval($_GET["groupid"]);
    $array[$title]="$page?user-popup=$id";
    $array["{groups}"]="$page?users-groups-start=$id&groupid=$groupid";
    echo $tpl->tabs_default($array);
}

function user_delete(){
    $tpl    = new template_admin();
    $id     = intval($_GET["user-delete"]);
    $md     = $_GET["md"];
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  =   $q->mysqli_fetch_array("SELECT name FROM users WHERE id='$id'");
    $title  =   $ligne["name"];
    $tpl->js_confirm_delete($title,"user-delete",$id,"$('#$md').remove()");
}

function user_delete_real(){
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("DELETE FROM users WHERE id='{$_POST["id"]}'");
    $q->QUERY_SQL("DELETE FROM user_user_groups WHERE user_id='{$_POST["id"]}'");
    $q->QUERY_SQL("DELETE FROM user_keys WHERE user_id='{$_POST["id"]}'");
    $q->QUERY_SQL("DELETE FROM sessions WHERE user_id='{$_POST["id"]}'");
    if(!$q->ok){echo $q->mysql_error;}
}

function user_popup(){
    $id     = intval($_GET["user-popup"]);
    $groupid= intval($_GET["groupid"]);
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $name   = null;
    $url    = null;
    $comment= null;
    $title  = "{new_member}";
    $btname = "{add}";
    $jsafter= "dialogInstance2.close();LoadAjax('sshportal-users-table','$page?table=yes')";

    if($id>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM users WHERE id='$id'");
        $name       = $ligne["name"];
        $comment    = $ligne["comment"];
        $email      = $ligne["email"];
        $title      = $name;
        $btname     = "{apply}";
        $jsafter    = "Loadjs('$page?fill=$id');";
    }



    $tpl->field_hidden("id",$id);

    if($id==0){

        if($groupid==0) {
            $results = $q->QUERY_SQL("SELECT * FROM user_groups ORDER BY name");
            foreach ($results as $index => $ligne) {
                $ID = $ligne["id"];
                $gname = $ligne["name"];
                $Groups[$ID] = $gname;
            }

            $form[] = $tpl->field_array_hash($Groups, "group", "{group2}");
        }else{
            $tpl->field_hidden("group",$groupid);
        }

    }


    $form[]=$tpl->field_text("name","{displayname}",$name,true);
    $form[]=$tpl->field_email("email","{email}",$email,true);
    $form[]=$tpl->field_text("comment","{comment}",$comment,false);




    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsSystemAdministrator");
}

function user_save(){
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl->CLEAN_POST();
    $comment    = $q->sqlite_escape_string2($_POST["comment"]);
    $name       = $_POST["name"];
    $email      = $_POST["email"];
    $id         = intval($_POST["id"]);
    $name       = replace_accents($name);
    $name       =str_replace("/","_",$name);
    $name       =str_replace("\\","_",$name);
    $name       =str_replace("@","_",$name);
    $name       =str_replace(":","_",$name);
    $name       =str_replace("$","",$name);
    $name       =str_replace(",","",$name);
    $name       =str_replace(";","",$name);
    $name       =str_replace('"',"",$name);
    $name       =str_replace("'","",$name);
    $name       =str_replace("(","",$name);
    $name       =str_replace(")","",$name);
    $name       =str_replace("[","",$name);
    $name       =str_replace("]","",$name);
    $name       =str_replace("%","",$name);
    $name       =str_replace("^","",$name);
    $name       =str_replace("&","e",$name);
    $name       =str_replace("{","",$name);
    $name       =str_replace("}","",$name);
    $name       =str_replace("!","",$name);
    $name       =str_replace("*","",$name);
    $groupid    = intval($_POST["group"]);
    $created_at = date("Y-m-d H:i:s");



    if($comment==null){
        $Adm=$_SESSION["uid"];
        if($Adm==-100){$Adm="Manager";}
        $comment="{createdate} $created_at {by} $Adm";
    }


    if($id==0){
        $invite_token=generateRandomString();
        $q->QUERY_SQL("INSERT INTO users (comment,name,email,invite_token,created_at,updated_at) VALUES ('$comment','$name','$email','$invite_token','$created_at','$created_at')");
        if(!$q->ok){echo $q->mysql_error;return;}

        if($groupid>0){
            $ligne = $q->mysqli_fetch_array("SELECT id FROM users WHERE invite_token='$invite_token'");
            $id=intval($ligne["id"]);
            $q->QUERY_SQL("INSERT INTO user_user_groups (user_id,user_group_id) VALUES ($id,$groupid)");
            if(!$q->ok){echo $q->mysql_error;return;}
        }

        return true;
    }

    $q->QUERY_SQL("UPDATE users SET `comment`='$comment',`name`='$name',email='$email',updated_at='$created_at' WHERE id='$id'");
    if(!$q->ok){echo $q->mysql_error;return;}

}
function user_fill(){
    $id         = $_GET["fill"];
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM users WHERE id='$id'");
    $name       = $ligne["name"];
    $comment    = $ligne["comment"];
    $email      = $ligne["email"];
    $invite_token = $ligne["invite_token"];

    $comment=str_replace("'","\'",$comment);
    header("content-type: application/x-javascript");
    $f[]="if( document.getElementById('$id-gname') ){";
    $f[]="document.getElementById('$id-uname').innerHTML='$name';";
    $f[]="document.getElementById('$id-email').innerHTML='$email';";
    $f[]="document.getElementById('$id-ucomment').innerHTML='$comment';";
    $f[]="document.getElementById('$id-invite').innerHTML='invite:$invite_token';";

    $f[]="}";
    echo @implode("\n",$f);

}

function table(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();
    $groupid    = intval($_GET["groupid"]);
    $delete_field="{delete}";
    $css_plus   = null;
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]=$tpl->button_label_table("{new_member}", "Loadjs('$page?user-js=0&groupid=$groupid')", "fas fa-user-plus","AllowAddUsers");
    if($groupid>0){
        $html[]=$tpl->button_label_table("{link_member}", "Loadjs('$page?member-link=$groupid')", "fas fa-link","AllowAddUsers");
        $delete_field="{unlink}";
        $css_plus="style='margin-top:10px'";
    }
    $html[]="</div>";


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" $css_plus>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{email}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{invite_token}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>$delete_field</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $sql="SELECT * FROM users ORDER BY name";
    if($groupid>0) {
        //user_user_groups" ("user_id" integer,"user_group_id" integer,
        $sql="SELECT users.* FROM users,user_user_groups WHERE  user_user_groups.user_id=users.id AND user_group_id=$groupid ORDER BY name";


    }
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

//	print_r($hash_full);
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID         = $ligne["id"];
        $md         = md5(serialize($ligne));
        $name       = $ligne["name"];
        $comment    = $ligne["comment"];
        $delete     = $tpl->icon_delete("Loadjs('$page?user-delete=$ID&md=$md')","AllowAddUsers");
        $email      = $ligne["email"];
        $status     = "<i class=\"text-danger fas fa-question\"></i>";
        $invite_token=$ligne["invite_token"];
        if($groupid>0) {
            $delete     = $tpl->icon_unlink("Loadjs('$page?user-unlink=$ID&md=$md&gpid=$groupid')","AllowAddUsers");
        }

        $ligne2=$q->mysqli_fetch_array("SELECT count(id) as tcount FROM user_keys WHERE user_id='$ID'");
        $Keys=intval($ligne2["tcount"]);
        if($Keys>0) {
            $status = "<i class=\"fas fa-thumbs-up\" style='color:#1ab394'></i>";
        }


        if($ID==1){$delete=$tpl->icon_nothing();}

        $js="Loadjs('$page?user-js=$ID')";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td class=\"\" width='1%' nowrap>$status</td>";
        $html[]="<td class=\"\" nowrap><i class='fas fa-user'></i>&nbsp;".$tpl->td_href("<span id='$ID-uname'>$name</span>","{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"\" nowrap><i class='fas fa-envelope'></i>&nbsp;<span id='$ID-email'>$email</span></td>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fas fa-anchor'></i>&nbsp;<span id='$ID-invite'>invite:$invite_token</span></td>";
        $html[]="<td class=\"\"><span id='$ID-ucomment'>$comment</span></td>";

        $html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));




}

function generateRandomString($length = 16) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}