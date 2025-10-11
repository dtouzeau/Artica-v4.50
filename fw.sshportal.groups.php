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
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-tabs"])){group_tabs();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_GET["group-popup"])){group_popup();exit;}
if(isset($_POST["id"])){group_save();exit;}
if(isset($_GET["group-delete"])){group_delete();exit;}
if(isset($_POST["group-delete"])){group_delete_real();exit;}
if(isset($_GET["fill"])){group_fill();exit;}
table();



function table_start(){
    $page   = CurrentPageName();
    $html   = "<div id='sshportal-groups-table' style='margin-top: 10px'></div><script>LoadAjax('sshportal-groups-table','$page?table=yes');</script>";
    echo $html;
}

function group_js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $id     = intval($_GET["group-js"]);
    $title  = "{new_group}";

    if($id>0){
        $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
        $ligne  =   $q->mysqli_fetch_array("SELECT name FROM user_groups WHERE id='$id'");
        $title  =   $ligne["name"];
        $tpl->js_dialog1($title,"$page?group-tabs=$id",850);
        return;
    }
    $tpl->js_dialog1($title,"$page?group-popup=$id",850);

}
function group_tabs(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $id     = intval($_GET["group-tabs"]);
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  = $q->mysqli_fetch_array("SELECT name FROM user_groups WHERE id='$id'");
    $title  = $ligne["name"];

    $array[$title]="$page?group-popup=$id";
    $array["{members}"]="fw.sshportal.users.php?start=yes&groupid=$id";
    echo $tpl->tabs_default($array);

}

function group_delete(){
    $tpl    = new template_admin();
    $id     = intval($_GET["group-delete"]);
    $md     = $_GET["md"];
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne  =   $q->mysqli_fetch_array("SELECT name FROM user_groups WHERE id='$id'");
    $title  =   $ligne["name"];

    $tpl->js_confirm_delete($title,"group-delete",$id,"$('#$md').remove()");

}

function group_delete_real(){
    $q      =   new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $q->QUERY_SQL("DELETE FROM user_groups WHERE id='{$_POST["id"]}'");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM user_group_acls WHERE user_group_id='{$_POST["id"]}'");
    $q->QUERY_SQL("DELETE FROM user_user_groups WHERE user_group_id='{$_POST["id"]}'");


}

function group_popup(){
    $id     = intval($_GET["group-popup"]);
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $q      = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $name   = null;
    $url    = null;
    $comment= null;
    $title  = "{new_group}";
    $btname = "{add}";
    $jsafter= "dialogInstance1.close();LoadAjax('sshportal-groups-table','$page?table=yes')";

    if($id>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM user_groups WHERE id='$id'");
        $name       = $ligne["name"];
        $comment    = $ligne["comment"];
        $title      = $name;
        $btname     = "{apply}";
        $jsafter    = "Loadjs('$page?fill=$id');";
    }



    $tpl->field_hidden("id",$id);
    $form[]=$tpl->field_text("name","{groupname}",$name,true);
    $form[]=$tpl->field_text("comment","{comment}",$comment,false);
    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsSystemAdministrator");
}

function group_save(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("sshd.php?sshportal-chown=yes");
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $tpl->CLEAN_POST();
    $comment    = $q->sqlite_escape_string2($_POST["comment"]);
    $name       = $_POST["name"];
    $id         = intval($_POST["id"]);
    $name       = replace_accents($name);
    $name       =str_replace(" ","_",$name);
    $name       =str_replace(".","_",$name);
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
    $created_at = date("Y-m-d H:i:s");
    if($comment==null){
        $Adm=$_SESSION["uid"];
        if($Adm==-100){$Adm="Manager";}
        $comment="{createdate} $created_at {by} $Adm";
    }

    if($id==0){
        $q->QUERY_SQL("INSERT INTO user_groups (comment,name) VALUES ('$comment','$name')");
        if(!$q->ok){echo $q->mysql_error;}
        return true;
    }

    $q->QUERY_SQL("UPDATE user_groups SET `comment`='$comment',`name`='$name' WHERE id='$id'");
    if(!$q->ok){echo $q->mysql_error;}

}
function group_fill(){
    $id         = $_GET["fill"];
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM user_groups WHERE id='$id'");
    $name       = $ligne["name"];
    $comment    = $ligne["comment"];
    $results2   = $q->mysqli_fetch_array("SELECT count(*) as tcount FROM user_user_groups WHERE user_group_id=$id");
    $Users      = intval($results2["tcount"]);

    $comment=str_replace("'","\'",$comment);
    header("content-type: application/x-javascript");
    $f[]="if( document.getElementById('$id-gname') ){";
    $f[]="document.getElementById('$id-gname').innerHTML='$name';";
    $f[]="document.getElementById('$id-gusername').innerHTML='$Users';";
    $f[]="document.getElementById('$id-gcomment').innerHTML='$comment';";
    $f[]="}";
    echo @implode("\n",$f);

}

function table(){
    $tpl        = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page       = CurrentPageName();
    $t          = time();
    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">".
        $tpl->button_label_table("{new_group}", "Loadjs('$page?group-js=0')", "fas fa-users-medical","AllowAddUsers")."</div>");



    $html[]=$tpl->_ENGINE_parse_body("
	<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{comment}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{members}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{delete}</th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $results=$q->QUERY_SQL("SELECT * FROM user_groups ORDER BY name");

//	print_r($hash_full);
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID         = $ligne["id"];
        $md         = md5(serialize($ligne));
        $name       = $ligne["name"];
        $comment    = $ligne["comment"];
        $delete     = $tpl->icon_delete("Loadjs('$page?group-delete=$ID&md=$md')","AllowAddUsers");
        $results2   = $q->mysqli_fetch_array("SELECT count(*) as tcount FROM user_user_groups WHERE user_group_id=$ID");
        $Users      = intval($results2["tcount"]);

        if($ID==1){$delete=$tpl->icon_nothing();}

        $js="Loadjs('$page?group-js=$ID')";
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fas fa-users'></i>&nbsp;".$tpl->td_href("<span id='$ID-gname'>$name</span>","{click_to_edit}",$js)."</td>";
        $html[]="<td class=\"\"><span id='$ID-gcomment'>$comment</span></td>";
        $html[]="<td class=\"\" width='1%' nowrap><i class='fa fa-user'></i>&nbsp;<span id='$ID-gusername'>$Users</span></td>";
        $html[]="<td class=\"center\" width=1% nowrap>$delete</td>";
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
	$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });


</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));




}