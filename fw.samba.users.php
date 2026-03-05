<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.artica-samba.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$user=new usersMenus();
if(!$user->AsSambaAdministrator){die();}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["user-create-js"])){user_create_js();exit;}
if(isset($_GET["user-create-form"])){user_create_form();exit;}
if(isset($_POST["username"])){user_create_save();exit;}
if(isset($_GET["user-edit-js"])){user_edit_js();exit;}
if(isset($_GET["user-edit-form"])){user_edit_form();exit;}
if(isset($_POST["edit-user"])){user_edit_save();exit;}
if(isset($_GET["user-delete-js"])){user_delete_js();exit;}
if(isset($_POST["delete-user"])){user_delete_perform();exit;}

page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_SAMBA} &raquo;&raquo; {users}",
        "fas fa-users",
        "{samba_users_explain}",
        "$page?start=yes","samba-users","progress-samba-users",false,"table-samba-users");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SAMBA} - {users}",$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $samba=new ArticaSamba();
    try{
        $instances=$samba->listInstances();
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    if(count($instances)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_fs_instances}"));
        return;
    }

    $options=[];
    $firstId="";
    foreach($instances as $inst){
        $id=$inst["id"] ?? "";
        $name=htmlspecialchars($inst["name"] ?? $id);
        $state=$inst["state"] ?? "stopped";
        $options[$id]="$name ($state)";
        if($firstId===""){$firstId=$id;}
    }

    $selectedId=isset($_GET["instance-id"])?$_GET["instance-id"]:$firstId;

    $html[]="<div style='margin-bottom:15px'>";
    $html[]="<label style='margin-right:10px'>{instance}:</label>";
    $html[]="<select id='samba-users-instance-selector' class='form-control' style='width:300px;display:inline-block' OnChange=\"SambaUsersReload();\">";
    foreach($options as $oid=>$olabel){
        $sel=($oid==$selectedId)?"selected":"";
        $html[]="<option value='$oid' $sel>$olabel</option>";
    }
    $html[]="</select>";
    $html[]="</div>";
    $html[]="<div id='users-table-area'></div>";
    $html[]="<script>";
    $html[]="function SambaUsersReload(){";
    $html[]="  var iid=document.getElementById('samba-users-instance-selector').value;";
    $html[]="  LoadAjax('users-table-area','$page?search=yes&instance-id='+iid);";
    $html[]="}";
    $html[]="SambaUsersReload();";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=trim($_GET["instance-id"] ?? "");
    $search=isset($_GET["search"])?$_GET["search"]:"";

    if(strlen($instanceId)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_instance}"));
        return;
    }

    $samba=new ArticaSamba();
    try{
        $users=$samba->listUsers($instanceId);
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    $topbuttons=[];
    $topbuttons[]=array("Loadjs('$page?user-create-js=$instanceId')","fas fa-user-plus","{new_member}");

    $tdStyle1="style='width:1%' nowrap";
    $t=time();
    $html[]=$tpl->table_buttons($topbuttons);
    $html[]="<table id='table-users-$t' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{username}</th>";
    $html[]="<th data-sortable=true data-type='number'>UID</th>";
    $html[]="<th>{enabled}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th $tdStyle1>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach($users as $u){
        $uname=$u["name"] ?? "";
        if(strlen($search)>0 && !preg_match("#$search#i",$uname)){continue;}

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(json_encode($u));
        $uid=$u["uid"] ?? 0;
        $enabled=$u["enabled"] ?? true;
        $enabledIco=$enabled?"<i class='".ico_check."' style='color:#1ab394'></i>":"<i class='fa fa-times' style='color:#ed5565'></i>";

        $unameEnc=urlencode($uname);
        $editBtn=$tpl->icon_edit_field("Loadjs('$page?user-edit-js=$unameEnc&instance-id=$instanceId')","AsSambaAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?user-delete-js=$unameEnc&instance-id=$instanceId&md=$md')","AsSambaAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdStyle1><i class='".ico_member."'></i></td>";
        $html[]="<td><strong>".htmlspecialchars($uname)."</strong></td>";
        $html[]="<td $tdStyle1>$uid</td>";
        $html[]="<td $tdStyle1>$enabledIco</td>";
        $html[]="<td $tdStyle1>$editBtn</td>";
        $html[]="<td $tdStyle1>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-users-$t').footable({";
    $html[]="  \"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":{$GLOBALS["FOOTABLE_PSIZE"]}}";
    $html[]="});});</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Create User ──────────────────────────────────────────────

function user_create_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=$_GET["user-create-js"];
    return $tpl->js_dialog5("{new_member}","$page?user-create-form=$instanceId",500);
}

function user_create_form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=$_GET["user-create-form"];

    $form[]=$tpl->field_hidden("user-instance-id",$instanceId);
    $form[]=$tpl->field_text("username","{username}","",true);
    $form[]=$tpl->field_password2("password","{password}","",true);

    $js="dialogInstance5.close();SambaUsersReload();";
    $html[]=$tpl->form_outside("",$form,null,"{create}",$js,"AsSambaAdministrator",false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function user_create_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instanceId=trim($_POST["user-instance-id"]);
    $username=trim($_POST["username"]);
    $password=trim($_POST["password"]);

    if(strlen($username)<1){
        echo $tpl->post_error("{username} {required}");
        return false;
    }
    if(strlen($password)<1){
        echo $tpl->post_error("{password} {required}");
        return false;
    }

    $samba=new ArticaSamba();
    try{
        $samba->createUser($instanceId,$username,$password);
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Created Samba user $username on instance $instanceId");
}

// ── Edit User ────────────────────────────────────────────────

function user_edit_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $name=urldecode($_GET["user-edit-js"]);
    $instanceId=$_GET["instance-id"];
    return $tpl->js_dialog5($name,"$page?user-edit-form=".urlencode($name)."&instance-id=$instanceId",500);
}

function user_edit_form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $name=urldecode($_GET["user-edit-form"]);
    $instanceId=$_GET["instance-id"];

    $currentEnabled=1;
    $samba=new ArticaSamba();
    try{
        $users=$samba->listUsers($instanceId);
        foreach($users as $u){
            if(($u["name"] ?? "")===$name){
                $currentEnabled=($u["enabled"] ?? true)?1:0;
                break;
            }
        }
    }catch(\RuntimeException $e){}

    $form[]=$tpl->field_hidden("edit-user",$name);
    $form[]=$tpl->field_hidden("edit-instance-id",$instanceId);
    $form[]=$tpl->field_password2("new-password","{new_password}","",false);
    $form[]=$tpl->field_checkbox("user-enabled","{enabled}",$currentEnabled);

    $js="dialogInstance5.close();SambaUsersReload();";
    $html[]=$tpl->form_outside("",$form,null,"{apply}",$js,"AsSambaAdministrator",false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function user_edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $name=trim($_POST["edit-user"]);
    $instanceId=trim($_POST["edit-instance-id"]);
    $newPassword=trim($_POST["new-password"] ?? "");
    $enabled=intval($_POST["user-enabled"] ?? 1)==1;

    $params=["enabled"=>$enabled];
    if(strlen($newPassword)>0){
        $params["password"]=$newPassword;
    }

    $samba=new ArticaSamba();
    try{
        $samba->updateUser($instanceId,$name,$params);
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Updated Samba user $name on instance $instanceId");
}

// ── Delete User ──────────────────────────────────────────────

function user_delete_js():bool{
    $tpl=new template_admin();
    $name=urldecode($_GET["user-delete-js"]);
    $instanceId=$_GET["instance-id"];
    $md=$_GET["md"];
    $deletejs="$('#$md').remove();";
    return $tpl->js_confirm_delete("{remove} {member} $name","delete-user",
        base64_encode(json_encode(["instance_id"=>$instanceId,"name"=>$name])),
        $deletejs);
}

function user_delete_perform():bool{
    $tpl=new template_admin();
    $data=json_decode(base64_decode($_POST["delete-user"]),true);
    $instanceId=$data["instance_id"] ?? "";
    $name=$data["name"] ?? "";
    $samba=new ArticaSamba();
    try{
        $samba->deleteUser($instanceId,$name);
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Deleted Samba user $name from instance $instanceId");
}
