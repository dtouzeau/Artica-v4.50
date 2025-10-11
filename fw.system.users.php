<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
if(isset($_GET["ssh-switch"])){groupid_ssh();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_all();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["groupid-js"])){groupid_js();exit;}
if(isset($_GET["groupid-start"])){groupid_start();exit;}
if(isset($_GET["groupid-tab"])){groupid_tab();exit;}
if(isset($_GET["groupid-popup"])){groupid_popup();exit;}
if(isset($_GET["groupid-members"])){groupid_members();exit;}
if(isset($_GET["groupid-search"])){groupid_search();exit;}
if(isset($_GET["groupid-buttons"])){groupid_buttons();exit;}
if(isset($_GET["groupid-useradd-js"])){groupid_useradd_js();exit;}
if(isset($_GET["groupid-useradd-popup"])){groupid_useradd_popup();exit;}
if(isset($_POST["groupid-useradd"])){groupid_useradd_save();exit;}
if(isset($_GET["groupid-userunlink-js"])){groupid_userunlink_js();exit;}
if(isset($_GET["groupid-userlink-js"])){groupid_link_js();exit;}
if(isset($_GET["groupid-userlink-popup"])){groupid_link_popup();exit;}
if(isset($_GET["groupid-userlink-search"])){groupid_link_search();exit;}
if(isset($_GET["groupid-userlink-perform"])){groupid_link_perform();exit;}
if(isset($_GET["groupid-delete-js"])){groupid_delete_js();exit;}
if(isset($_POST["groupid-delete"])){groupid_delete_perform();exit;}
if(isset($_GET["newgroup-created"])){groupid_created();exit;}
if(isset($_GET["user-su-js"])){user_su_js();exit;}
if(isset($_GET["AuthorizedKeys-create-js"])){AuthorizedKeys_create_js();exit;}

if(isset($_GET["user-js"])){user_js();exit;}
if(isset($_GET["user-tab"])){user_tab();exit;}
if(isset($_GET["user-start"])){user_start();exit;}
if(isset($_GET["user-popup"])){user_popup();exit;}

if(isset($_GET["user-shell-js"])){user_shell_js();exit;}
if(isset($_GET["user-shell-popup"])){user_shell_popup();exit;}
if(isset($_POST["user-shell"])){user_shell_save();exit;}

if(isset($_GET["user-gecos-js"])){user_gecos_js();exit;}
if(isset($_GET["user-gecos-popup"])){user_gecos_popup();exit;}
if(isset($_POST["user-gecos"])){user_gecos_save();exit;}
if(isset($_GET["user-ssh-switch"])){user_ssh_switch();exit;}

if(isset($_GET["user-password-js"])){user_password_js();exit;}
if(isset($_GET["user-password-popup"])){user_password_popup();exit;}
if(isset($_POST["user-password"])){user_password_save();exit;}
if(isset($_GET["user-delete-js"])){user_delete_js();exit;}
if(isset($_POST["user-delete"])){user_delete_perform();exit;}
if(isset($_GET["user-sudo"])){user_sudo_popup();exit;}
if(isset($_GET["user-sudo-search"])){user_sudo_search();exit;}
if(isset($_GET["user-sudo-buttons"])){user_sudo_buttons();exit;}
if(isset($_GET["user-sudo-id"])){user_sudo_id_js();exit;}
if(isset($_GET["user-sudo-id-popup"])){user_sudo_id_popup();exit;}
if(isset($_POST["user-sudo-id"])){user_sudo_id_save();exit;}
if(isset($_GET["user-sudo-id-del"])){user_sudo_id_del();exit;}






if(isset($_GET["groupid-sudo"])){groupid_sudo();exit;}
if(isset($_GET["groupid-sudo-search"])){groupid_sudo_table();exit;}
if(isset($_GET["groupid-sudo-buttons"])){groupid_sudo_buttons();exit;}
if(isset($_GET["groupid-sudo-id"])){groupid_sudo_id_js();exit;}
if(isset($_GET["groupid-sudo-id-popup"])){groupid_sudo_id_popup();exit;}
if(isset($_GET["groupid-sudo-id-del"])){groupid_sudo_id_del();exit;}
if(isset($_POST["groupid-sudo-id"])){groupid_sudo_id_save();exit;}


if(isset($_GET["new-group-js"])){groupid_create_js();exit;}
if(isset($_GET["new-group-popup"])){groupid_create_popup();exit;}
if(isset($_POST["newgroup"])){groupid_create_perform();exit;}

if(isset($_GET["search"])){table();exit;}
if(isset($_POST["newswap"])){swap_save();exit;}
if(isset($_GET["swap-delete"])){swap_delete_ask();exit;}

if(isset($_GET["newswap-js"])){newswap_js();exit;}
if(isset($_GET["newswap-popup"])){newswap_popup();exit;}


if(isset($_POST["build"])){Build_save();exit;}
if(isset($_GET["build-after"])){Build_after();exit;}
if(isset($_POST["swap-delete"])){swap_delete_save();exit;}
if(isset($_GET["rescan-js"])){rescan_js();exit;}

page();
function reconfigure_all():bool{
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/reconfigure");
    return $tpl->js_ok("{success}");
}
function groupid_create_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog10("{new_group}", "$page?new-group-popup=yes&function=$function",550);
}
function groupid_create_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    unset($_SESSION["SYSTEM_USERS_GROUP_CREATED"]);
    $form[]=$tpl->field_text("newgroup","{groupname}","");
    $jsafter[]="dialogInstance10.close();";
    $jsafter[]="$function();";
    $jsafter[]="Loadjs('$page?newgroup-created=yes&function=$function');";
    echo $tpl->form_outside("",$form,"","{add}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function groupid_created():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $gid=GroupIDFromName($_SESSION["SYSTEM_USERS_GROUP_CREATED"]);
    if($gid==-1){
        return false;
    }
    if($gid==0){
        return false;
    }
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?groupid-js=$gid&function=$function');";
    return true;
}


function groupid_create_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $grouname=replace_accents($_POST["newgroup"]);
    $grouname=str_replace(" ",".",$grouname);
    $grouname=str_replace("'",".",$grouname);
    $grouname=str_replace("(",".",$grouname);
    $grouname=str_replace(")",".",$grouname);
    $grouname=str_replace("[",".",$grouname);
    $grouname=str_replace("]",".",$grouname);
    $grouname=str_replace(",",".",$grouname);
    $grouname=str_replace(";",".",$grouname);
    $grouname=str_replace("/",".",$grouname);
    $grouname=str_replace(":",".",$grouname);
    $grouname=str_replace("..",".",$grouname);
    $grouname=str_replace("..",".",$grouname);
    $grouname=str_replace("..",".",$grouname);
    $grouname=urlencode($grouname);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/group/create/$grouname"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $_SESSION["SYSTEM_USERS_GROUP_CREATED"]=$grouname;
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
    return admin_tracks("Create a new system group $grouname");
}
function user_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["user-js"];
    $function=$_GET["function"];
    return $tpl->js_dialog8($id, "$page?user-tab=$id&function=$function");
}
function groupid_delete_js():bool{
    $id=intval($_GET["groupid-delete-js"]);
    $tpl=new template_admin();
    $md=$_GET["md"];
    $Info=GroupInfo($id);
    return $tpl->js_confirm_delete($Info["name"],"groupid-delete",$id,"$('#$md').remove();");
}
function groupid_delete_perform():bool{
    $tpl=new template_admin();
    $id=intval($_POST["groupid-delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/group/delete/$id"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
    return admin_tracks("Delete system group $id");
}
function groupid_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $id=$_GET["groupid-js"];
    $function=$_GET["function"];
    $ar=GroupInfo($id);
	return $tpl->js_dialog($ar["name"], "$page?groupid-tab=$id&function=$function");
}
function user_sudo_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $user=$_GET["user-sudo"];
    $functionSrc="";
    $function2="";
    if(isset($_GET["functionSrc"])){
        $functionSrc=$_GET["functionSrc"];
    }
    if(isset($_GET["function2"])){
        $function2=$_GET["function2"];
    }
    $UserInfo=UserInfo($user);
    $id=$UserInfo["uid"];

    echo "<div id='buttons-sudoers-$id' style='margin:5px'></div>";
    echo $tpl->search_block($page,"","","","&user-sudo-search=$id&functionSrc=$functionSrc&function2=$function2");
    return true;
}
function groupid_sudo():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-sudo"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
    echo "<div id='buttons-sudoers-$id' style='margin:5px'></div>";
    echo $tpl->search_block($page,"","","","&groupid-sudo-search=$id&functionSrc=$functionSrc&function2=$function2");
    return true;
}
function groupid_sudo_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-sudo-search"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
    $results=$q->QUERY_SQL("SELECT * FROM sudoersgroups WHERE gpid=$id");
    $html[]="<table id='table-hd-disks' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    $binmodes[1]="<span class='label label-primary'>{AllowNoPassword}</span>";
    $binmodes[0]="<span class='label label-danger'>{mandatory}</span>";

    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{program}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{password}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";

    $search="";
    if(isset($_GET["search"])){
        $search="*".$_GET["search"]."*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*",".*?",$search);
    }

    foreach ($results as $ligne) {
        $binary=$ligne["binary"];
        $ID=intval($ligne["ID"]);
        $binmode=$binmodes[intval($ligne["binmode"])];
        $md=md5(serialize($ligne));
        $delete=$tpl->icon_delete("Loadjs('$page?groupid-sudo-id-del=$ID&md=$md');","AsSystemAdministrator");
        if(strlen($search)>2){
            if(!preg_match("/".$search."/",serialize($ligne))){
                continue;
            }
        }
        $ico=ico_terminal;
        if($binary=="*"){
            $binary="{all}";
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><strong style='font-size:16px'><i class='$ico'></i>&nbsp;&nbsp;$binary</strong></td>";
        $html[]="<td style='width:1%' nowrap>$binmode</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
	$html[]="LoadAjax('buttons-sudoers-$id','$page?groupid-sudo-buttons=$id&function=$function');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

return true;
}
function groupid_sudo_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["groupid-sudo-buttons"]);
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?groupid-sudo-id=0&gpid=$id&function=$function');", ico_plus, "{new_rule}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function user_sudo_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["user-sudo-buttons"]);
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?user-sudo-id=0&gpid=$id&function=$function');", ico_plus, "{new_rule}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function groupid_sudo_id_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-sudo-id"]);
    $function=$_GET["function"];
    $gpid=intval($_GET["gpid"]);
    $js="Loadjs('$page?groupid-sudo-id-popup=$id&gpid=$gpid&function=$function');";
   return $tpl->js_dialog12("{rule} #$id",$js,550);
}
function user_sudo_id_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["user-sudo-id"]);
    $function=$_GET["function"];
    $gpid=intval($_GET["gpid"]);
    $js="Loadjs('$page?user-sudo-id-popup=$id&gpid=$gpid&function=$function');";
    return $tpl->js_dialog12("{rule} #$id",$js,550);
}
function user_sudo_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["user-sudo-search"];
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    $functionSrc=$_GET["functionSrc"];
    $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
    $results=$q->QUERY_SQL("SELECT * FROM sudoersusers WHERE gpid=$id");
    $html[]="<table id='table-sudo-user-$id' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";

    $binmodes[1]="<span class='label label-primary'>{AllowNoPassword}</span>";
    $binmodes[0]="<span class='label label-danger'>{mandatory}</span>";

    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{program}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{password}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";

    $search="";
    if(isset($_GET["search"])){
        $search="*".$_GET["search"]."*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*",".*?",$search);
    }

    foreach ($results as $ligne) {
        $binary=$ligne["binary"];
        $ID=intval($ligne["ID"]);
        $binmode=$binmodes[intval($ligne["binmode"])];
        $md=md5(serialize($ligne));
        $delete=$tpl->icon_delete("Loadjs('$page?user-sudo-id-del=$ID&md=$md');","AsSystemAdministrator");
        if(strlen($search)>2){
            if(!preg_match("/".$search."/",serialize($ligne))){
                continue;
            }
        }
        $ico=ico_terminal;
        if($binary=="*"){
            $binary="{all}";
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><strong style='font-size:16px'><i class='$ico'></i>&nbsp;&nbsp;$binary</strong></td>";
        $html[]="<td style='width:1%' nowrap>$binmode</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjax('buttons-sudoers-$id','$page?user-sudo-buttons=$id&function=$function');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function groupid_sudo_id_popup():bool{
    $tpl=new template_admin();
    $id=intval($_GET["groupid-sudo-id-popup"]);
    $gpid=intval($_GET["gpid"]);
    $function=$_GET["function"];

    $ligne["binary"]="/bin/ls";
    $ligne["binmode"]=0;
    $btn="{add}";

    if($id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM sudoersgroups WHERE id=$id");
        $btn="{apply}";
    }
    $form[]=$tpl->field_hidden("groupid-sudo-id",$id);
    $form[]=$tpl->field_hidden("gpid",$gpid);
    $form[]=$tpl->field_text("binary","{program}",$ligne["binary"]);
    $form[]=$tpl->field_checkbox("binmode","{AllowNoPassword}","");
    $jsafter[]="dialogInstance12.close()";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"",$btn,@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_sudo_id_popup():bool{
    $tpl=new template_admin();
    $id=intval($_GET["user-sudo-id-popup"]);
    $gpid=intval($_GET["gpid"]);
    $function=$_GET["function"];

    $ligne["binary"]="/bin/ls";
    $ligne["binmode"]=0;
    $btn="{add}";

    if($id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM sudoersusers WHERE id=$id");
        $btn="{apply}";
    }
    $form[]=$tpl->field_hidden("user-sudo-id",$id);
    $form[]=$tpl->field_hidden("gpid",$gpid);
    $form[]=$tpl->field_text("binary","{program}",$ligne["binary"]);
    $form[]=$tpl->field_checkbox("binmode","{AllowNoPassword}","");
    $jsafter[]="dialogInstance12.close()";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"",$btn,@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_sudo_id_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=intval($_POST["user-sudo-id"]);
    $gpid=intval($_POST["gpid"]);
    $binary=trim($_POST["binary"]);

    if(strpos($binary," ")==0){
        if(!is_file($binary)){
            $found=find_program($binary);
            if(strlen($found)>2){
                $binary=$found;
            }
        }
    }
    $binmode=intval($_POST["binmode"]);
    $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
    if($id==0){
        $q->QUERY_SQL("INSERT INTO sudoersusers (gpid,binary,binmode) VALUES ($gpid,'$binary',$binmode)");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
        return admin_tracks("Create a new sudo rule $binary for user $gpid");
    }
    $q->QUERY_SQL("UPDATE sudoersusers SET binary='$binary',binmode=$binmode WHERE id=$id");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
    return admin_tracks("Update a sudo rule $binary for user $gpid");
}
function groupid_sudo_id_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=intval($_POST["groupid-sudo-id"]);
    $gpid=intval($_POST["gpid"]);
    $binary=trim($_POST["binary"]);

    if(strpos($binary," ")==0){
        if(!is_file($binary)){
            $found=find_program($binary);
            if(strlen($found)>2){
                $binary=$found;
            }
        }
    }
    $binmode=intval($_POST["binmode"]);
    $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
    if($id==0){
        $q->QUERY_SQL("INSERT INTO sudoersgroups (gpid,binary,binmode) VALUES ($gpid,'$binary',$binmode)");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
        return admin_tracks("Create a new sudo rule $binary for group $gpid");
    }
    $q->QUERY_SQL("UPDATE sudoersgroups SET binary='$binary',binmode=$binmode WHERE id=$id");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
    return admin_tracks("Update a sudo rule $binary for group $gpid");
}
function find_program($strProgram):string {

    $strProgram=trim($strProgram);
    $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
        '/usr/local/sbin',
        '/usr/kerberos/bin',

    );

    if (function_exists("is_executable")) {
        foreach($arrPath as $strPath) {
            $strProgrammpath = $strPath . "/" . $strProgram;
            if (is_executable($strProgrammpath)) {
                return $strProgrammpath;
            }
        }
    }
    return "";
}
function groupid_sudo_id_del():bool{
    $tpl=new template_admin();
    $id=intval($_GET["groupid-sudo-id-del"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
    $q->QUERY_SQL("DELETE FROM sudoersgroups WHERE id=$id");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    return admin_tracks("Delete a sudo rule for group #$id");
}
function user_sudo_id_del():bool{
    $tpl=new template_admin();
    $id=intval($_GET["user-sudo-id-del"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/sudoers.db");
    $q->QUERY_SQL("DELETE FROM sudoersusers WHERE id=$id");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/sudoers");
    return admin_tracks("Delete a sudo rule for user #$id");
}
function groupid_useradd_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-useradd-js"]);
    $function2="";
    $nogroup="";
    $function=$_GET["function"];
    if(isset($_GET["function2"])){
        $function2=$_GET["function2"];
    }
    $GroupName="";


    if(isset($_GET["nogroup"])){
        $nogroup="&nogroup=yes";
    }else {
        if ($id == 0) {
            $GroupName = "Root:";
        }
    }
    if($id>0){
        $ar = GroupInfo($id);
        $GroupName=$ar["GroupName"].":";
    }
    return $tpl->js_dialog10("$GroupName {new_member}", "$page?groupid-useradd-popup=$id&function=$function&function2=$function2$nogroup");
}
function groupid_link_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-userlink-popup"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
   echo $tpl->search_block($page,"","","","&groupid-userlink-search=$id&functionSrc=$functionSrc&function2=$function2");
    return true;
}
function groupid_link_perform():bool{
    $tpl=new template_admin();
    $groupid=intval($_GET["groupid-userlink-perform"]);
    $member=$_GET["member"];
    $md=$_GET["md"];
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/join/$groupid/$member"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "$function2();\n";
    echo "$functionSrc();\n";
    return admin_tracks("Add a new system user $member to group $groupid");

}

function groupid_link_search():bool{
    $id=intval($_GET["groupid-userlink-search"]);
    $Info=GroupInfo($id);
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    $functionSrc=$_GET["functionSrc"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Members=$Info["members"];
    foreach ($Members as $member) {
        $ALREADY[$member]=true;
    }
    $html[]="<table id='groupid_link_search' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{link}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $search="";
    if(isset($_GET["search"])){
        $search="*".$_GET["search"]."*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*",".*?",$search);
    }
    $TRCLASS="";
    $gico=ico_member;
    $AllMembers=AllMembers();
    $c=0;
    foreach ($AllMembers as $member) {
        if(isset($ALREADY[$member])){
            continue;
        }
        if(strlen($search)>2){
            if(!preg_match("/".$search."/",$member)){
                continue;
            }
        }
        $c++;
        if($c>20){
            break;
        }
        $memberenc=urlencode($member);
        $md=md5($member);
        $js="Loadjs('$page?groupid-userlink-perform=$id&member=$memberenc&function=$function&function2=$function2&functionSrc=$functionSrc&md=$md');";

        $bton=$tpl->th_buttons_label("{select}",$js,ico_link,"AsSystemAdministrator","label-primary");

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:15%' nowrap><strong style='font-size:14px'><i class='$gico'></i>&nbsp;$member</strong></td>";
        $html[]="<td style='width:1%' nowrap><strong>$bton</strong></td>";
        $html[]="</tr>";


    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function groupid_link_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-userlink-js"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
    $ar=GroupInfo($id);
    return $tpl->js_dialog11("{$ar["name"]}: {link_user}", "$page?groupid-userlink-popup=$id&functionSrc=$functionSrc&function2=$function2",550);
}
function groupid_useradd_popup(){
    $tpl=new template_admin();
    $function2="";
    $nogroup=false;

    $shells["/bin/sh"]="/bin/sh";
    $shells["/bin/bash"]="/bin/bash";
    $shells["/usr/bin/bash"]="/usr/bin/bash";
    $shells["/bin/rbash"]="/bin/rbash";
    $shells["/usr/bin/rbash"]="/usr/bin/rbash";
    $shells["/bin/dash"]="/bin/dash";
    $shells["/usr/bin/dash"]="/usr/bin/dash";
    $shells["/bin/tcsh"]="/bin/tcsh";
    $shells["/usr/bin/tcsh"]="/usr/bin/tcsh";
    $shells["/bin/false"]="/bin/false";
    $shells["/sbin/nologin"]="/sbin/nologin";
    $id=intval($_GET["groupid-useradd-popup"]);
    $function=$_GET["function"];
    if(isset($_GET["function2"])){
        $function2=$_GET["function2"];
    }
    if(isset($_GET["nogroup"])){
        $nogroup=true;
    }
    if(!$nogroup) {
        if ($id == 0) {
            echo $tpl->div_warning("{warning_group_root_create_user}");
        }
    }
    if($nogroup){
        $GPRS[0]="{automatic}";
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
        if(!$data->Status){
            echo $tpl->div_error($data->Error);
            return false;
        }
        if(!property_exists($data,"UsersAndGroups")){
            echo $tpl->div_error("{no_data}");
            return false;
        }
       foreach ($data->UsersAndGroups->groups as $jsClass){
           if($jsClass->gid==0){continue;}
           $gid = $jsClass->gid;
           $Name = $jsClass->name;
           $GPRS[$gid] = $Name;
       }
        $form[]=$tpl->field_array_hash($GPRS,"groupid-useradd","nonull:{select_group}","");
        $form[]=$tpl->field_checkbox("asRoot","{group} Root",0);
    }else{
        $form[]=$tpl->field_hidden("groupid-useradd",$id);
        if($id==0){
            $form[]=$tpl->field_hidden("asRoot",1);
        }
    }
    $form[]=$tpl->field_text("username","{username}","");
    $form[]=$tpl->field_password("password","{password}","");
    $form[]=$tpl->field_text("gecos","{description}","");
    $form[]=$tpl->field_browse_directory("home","{homeDirectory}","auto");
    $form[]=$tpl->field_array_hash($shells,"shell","{shell}","/bin/bash");
    $jsafter[]="dialogInstance10.close()";
    $jsafter[]="$function()";
    if(strlen($function2)>2) {
        $jsafter[] = "$function2()";
    }
    echo $tpl->form_outside("",$form,"","{add}",@implode(";",$jsafter),"AsSystemAdministrator");
}
function groupid_useradd_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_POST["gid"]=intval($_POST["groupid-useradd"]);
    $gid=$_POST["gid"];
    $asRoot=intval($_POST["asRoot"]);
    $userName=$_POST['username'];
    $userNameEnc=urlencode($userName);
    if($gid==0 && $asRoot==0){
        $_POST["autogroup"]=$userName;
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/group/create/$userNameEnc"));
        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }
    }

    $data=urlencode(base64_encode(serialize($_POST)));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/add/$data"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/build/sudoers");
    return admin_tracks("Create a new system user {$_POST['username']}");
}
function AllMembers():array{
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
    if(!$data->Status){
        return array();
    }
    if(!property_exists($data,"UsersAndGroups")){
        return array();
    }
    $MAIN=array();
    foreach ($data->UsersAndGroups->groups as $jsClass) {
        $members = $jsClass->members;
        foreach ($members as $member) {
            $MAIN[$member] = $member;
        }
    }
    return $MAIN;
}
function UserInfo($Username):array{
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
    if(!$data->Status){
        return array();
    }
    if(!property_exists($data,"UsersAndGroups")){
        return array();
    }
    foreach ($data->UsersAndGroups->users as $jsClass) {
        $gid = $jsClass->gid;
        $uid = $jsClass->uid;
        $Name = $jsClass->name;
        $Gecos = $jsClass->gecos;
        $Home = $jsClass->home;
        $Shell = $jsClass->shell;
        $type=$jsClass->type;
        if($Name==$Username){
            $Array["uid"] = $uid;
            $Array["gid"] = $gid;
            $Array["name"] = $Name;
            $Array["gecos"] = $Gecos;
            $Array["home"] = $Home;
            $Array["shell"] = $Shell;
            $Array["type"] = $type;
            $Array["group"] = GroupInfo($gid)["name"];
            $Array["memberof"] = isMemberOf($Username,$data->UsersAndGroups);
            return $Array;
        }


    }
    return array();
}
function isMemberOf($Username,$data):array{
    $Array=array();
    foreach ($data->groups as $jsClass) {
        $gid = $jsClass->gid;
        $Name = $jsClass->name;
        $members = $jsClass->members;
        if(in_array($Username,$members)){
            $Array[$gid] = $Name;
        }
    }
    return $Array;
}

function GroupIDFromName($GroupName):int{
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
    if(!$data->Status){
        return -1;
    }
    if(!property_exists($data,"UsersAndGroups")){
        return -1;
    }
    foreach ($data->UsersAndGroups->groups as $jsClass) {
        $gid = $jsClass->gid;
        if(strtolower($GroupName)==strtolower($jsClass->name)){
            return $gid;
        }
    }
    return -1;
}
function GroupInfo($id):array{

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
    if(!$data->Status){
        return array();
    }
    if(!property_exists($data,"UsersAndGroups")){
        return array();
    }


    foreach ($data->UsersAndGroups->groups as $jsClass) {
        $gid = $jsClass->gid;
        $Name = $jsClass->name;

        $members = $jsClass->members;
        if($gid==$id) {
            $Array["name"] = $Name;
            $Array["members"] = $members;
            return $Array;
        }

    }

    return array();
}
function groupid_tab():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $id=intval($_GET["groupid-tab"]);
    $function=$_GET["function"];
    $GroupInfo=GroupInfo($id);
    $array[$GroupInfo["name"]]="$page?groupid-start=$id&function=$function";
    $array["{members}"]="$page?groupid-members=$id&function=$function";
    if($id>0) {
        if(!isBanned($GroupInfo["name"])) {
            $SUDO_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SUDO_INSTALLED"));
            if ($SUDO_INSTALLED == 1) {
                $array["Sudo"] = "$page?groupid-sudo=$id&function=$function";
            }
        }
    }
	echo $tpl->tabs_default($array);
    return true;
}
function user_tab():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $user = $_GET["user-tab"];
    $userenc = urlencode($user);
    $function = $_GET["function"];
    $array[$user] = "$page?user-start=$userenc&function=$function";
    if (!isBanned($user)) {
        $SUDO_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SUDO_INSTALLED"));
        if ($SUDO_INSTALLED == 1) {
            $array["Sudo"] = "$page?user-sudo=$user&function=$function";
        }
    }

    echo $tpl->tabs_default($array);
    return true;
}
function groupid_start():bool{
    $page=CurrentPageName();
    $id=intval($_GET["groupid-start"]);
    $function=$_GET["function"];
    echo "<div id='groupid-start-$id'></div>";
    echo "<script>LoadAjaxSilent('groupid-start-$id','$page?groupid-popup=$id&function=$function');</script>";
    return true;
}
function user_start():bool{
    $page=CurrentPageName();
    $user=$_GET["user-start"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    $uid=md5($user);
    echo "<div id='user-start-$uid'></div>";
    echo "<script>LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');</script>";
    return true;
}
function user_gecos_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-gecos-js"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    return $tpl->js_dialog9("$user: {description}", "$page?user-gecos-popup=$userenc&function=$function",550);
}
function user_shell_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-shell-js"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    return $tpl->js_dialog9("$user: {shell}", "$page?user-shell-popup=$userenc&function=$function",550);
}
function user_shell_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-shell-popup"];
    $form[]=$tpl->field_hidden("user-shell",$user);
    $Info=UserInfo($user);
    $uid=md5($user);
    $userenc=urlencode($user);
    $function=$_GET["function"];

    $shells["/bin/sh"]="/bin/sh";
    $shells["/bin/bash"]="/bin/bash";
    $shells["/usr/bin/bash"]="/usr/bin/bash";
    $shells["/bin/rbash"]="/bin/rbash";
    $shells["/usr/bin/rbash"]="/usr/bin/rbash";
    $shells["/bin/dash"]="/bin/dash";
    $shells["/usr/bin/dash"]="/usr/bin/dash";
    $shells["/bin/tcsh"]="/bin/tcsh";
    $shells["/usr/bin/tcsh"]="/usr/bin/tcsh";
    $shells["/bin/false"]="/bin/false";
    $shells["/sbin/nologin"]="/sbin/nologin";

    $form[]=$tpl->field_array_hash($shells,"shellCommand","{shell}",$Info["shell"]);
    $jsafter[]="dialogInstance9.close()";
    $jsafter[]="LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"","{apply}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_shell_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $user=$_POST["user-shell"];
    $userenc=urlencode($user);
    $shell=urlencode($_POST["shellCommand"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/shell/$userenc/$shell"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error." {$_POST["shellCommand"]}");
        return false;
    }
    return admin_tracks("Change the shell of user {$_POST["user-shell"]} to {$_POST["shell"]}");
}
function user_password_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-password-js"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    return $tpl->js_dialog9("$user: {password}", "$page?user-password-popup=$userenc&function=$function",550);
}
function user_password_popup():bool{
    $tpl=new template_admin();
    $user=$_GET["user-password-popup"];
    $form[]=$tpl->field_hidden("user-password",$user);
    $form[]=$tpl->field_password2("password","{password}","");
    $jsafter[]="dialogInstance9.close()";
    echo $tpl->form_outside("",$form,"","{apply}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_password_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $user=urlencode($_POST["user-password"]);
    $gecos=urlencode(base64_encode($_POST["password"]));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/password/$user/$gecos"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Change the password of the system user $user");
}
function user_gecos_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    $user=$_GET["user-gecos-popup"];
    $userenc=urlencode($user);
    $form[]=$tpl->field_hidden("user-gecos",$user);
    $Info=UserInfo($user);
    $uid=md5($user);

    $form[]=$tpl->field_text("gecos","{description}",$Info["gecos"]);
    $jsafter[]="dialogInstance9.close()";
    $jsafter[]="LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"","{apply}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_gecos_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $user=urlencode($_POST["user-gecos"]);
    $gecos=urlencode(base64_encode($_POST["gecos"]));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/gecos/$user/$gecos"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Change the description of the system user $user");
}
function user_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-popup"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    $Info=UserInfo($user);

    $GroupName=$Info["group"];
    $UserName=$Info["name"];
    $gid=$Info["gid"];
    $uid=$Info["uid"];
    $tpl->table_form_field_text("{type}","{".$Info["type"]."}",ico_infoi);
    $tpl->table_form_field_js("Loadjs('$page?user-password-js=$userenc&function=$function')","AsSystemAdministrator");
    $tpl->table_form_field_text("{password}","* * * *",ico_lock);


    $isMemberOf=$Info["memberof"];
    $Memberof=array();
    $tt=array();
    foreach ($isMemberOf as $gid => $GroupName) {
        $Memberof[$GroupName]=true;
        if($GroupName=="wheel"){
            continue;
        }
        $GroupName=$tpl->td_href($GroupName,"","Loadjs('$page?groupid-js=$gid&function=$function')");
        $tt[]="<span style='text-transform:none'>$GroupName</span>";
    }
    $tpl->table_form_field_js("Loadjs('$page?user-gecos-js=$userenc&function=$function')","AsSystemAdministrator");
    $tpl->table_form_field_text("{description}",$Info["gecos"],ico_infoi);
    $tpl->table_form_field_js("Loadjs('$page?groupid-js=$gid&function=$function')","AsSystemAdministrator");
    $tpl->table_form_field_text("{primary_group}","<span style='text-transform:none'>$GroupName</span>",
        ico_groups_finders);

    if($uid>0) {
        $tpl->table_form_field_js("Loadjs('$page?user-su-js=$userenc&function=$function')", "AsSystemAdministrator");
        if (isset($Memberof["wheel"])) {
            $tpl->table_form_field_bool("{allow} <strong>&laquo;{su}&raquo;</strong>", 1, ico_admin);
        } else {
            $tpl->table_form_field_bool("{allow} <strong>&laquo;{su}&raquo;</strong>", 0, ico_admin);
        }
    }


    if(count($tt)>1) {
        $tt_text=@implode(", ",$tt);
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{isMemberOf}", "<small>$tt_text</small>",
            ico_groups_settings);
    }
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{homeDirectory}","<span style='text-transform:none'>{$Info["home"]}</span>",ico_directory);

    $tpl->table_form_field_js("Loadjs('$page?user-shell-js=$userenc&function=$function')");
    $tpl->table_form_field_text("{shell}","<span style='text-transform:none'>{$Info["shell"]}</span>",ico_terminal);

    $tpl=user_popup_ssh($tpl,$Info);

    if(!isBanned($UserName)){
        $tpl->table_form_button("{remove}","Loadjs('$page?user-delete-js=$uid&name=$userenc&function=$function')","AsSystemAdministrator",ico_trash);

    }

    echo $tpl->table_form_compile();

    return true;
}
function user_su_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-su-js"];
    $Info=UserInfo($user);
    $isMemberOf=$Info["memberof"];
    $Memberof=array();
    $function=$_GET["function"];
    $userenc=urlencode($user);
    foreach ($isMemberOf as $gid => $GroupName) {
        $Memberof[$GroupName]=true;
    }
    if(isset($Memberof["wheel"])){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/ungroup/wheel/$user"));
        if(!$json->Status){
            echo $tpl->js_error($json->Error);
            return false;
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
        header("content-type: application/x-javascript");
        $uid=md5($user);
        echo "LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
        return admin_tracks("Delete the user $user from the wheel group");
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/group/wheel/$user"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    header("content-type: application/x-javascript");
    $uid=md5($user);
    echo "LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
    return admin_tracks("Add the user $user to the wheel group");

}
function user_ssh_switch():bool{
    $tpl=new template_admin();
    $uid=$_GET["uid"];
    $username=$_GET["user-ssh-switch"];
    $userenc=urlencode($username);
    $function=$_GET["function"];
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sshd_allowusers WHERE pattern='$username'");
    if(!isset($ligne["pattern"])){
        $q->QUERY_SQL("INSERT INTO sshd_allowusers (pattern,enabled) VALUES ('$username',1)");
        if(!$q->ok){
            echo $tpl->js_error($q->mysql_error);
            return false;
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
        header("content-type: application/x-javascript");
        echo "LoadAjax('user-start-$username','$page?user-popup=$userenc&function=$function');";
        return admin_tracks("Enable the SSH access of the system user $uid");
    }
    if($ligne["enabled"]==1){
        $q->QUERY_SQL("UPDATE sshd_allowusers SET enabled=0 WHERE pattern='$username'");
        if(!$q->ok){
            echo $tpl->js_error($q->mysql_error);
            return false;
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
        header("content-type: application/x-javascript");
        $uid=md5($username);
        echo "LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
        return admin_tracks("Disable the SSH access of the system user $uid");
    }

    $q->QUERY_SQL("UPDATE sshd_allowusers SET enabled=1 WHERE pattern='$username'");
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    header("content-type: application/x-javascript");
    $uid=md5($username);
    echo "LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
    return admin_tracks("Enable the SSH access of the system user $uid");



}
function user_popup_ssh($tpl,$Info){
    $page=CurrentPageName();
    $function=$_GET["function"];
    if($Info["type"]<>"member") {
        return $tpl;
    }
    if(user_isssh($Info["memberof"])){
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{accept_ssh}",1,ico_terminal);
        VERBOSE("--> memberof",__LINE__);
        return user_popup_sshd_authorizedkeys($tpl,$Info);
    }
    $uid=$Info["uid"];
    $NameEncoded=urlencode($Info["name"]);
    $tpl->table_form_field_js("Loadjs('$page?user-ssh-switch=$NameEncoded&uid=$uid&function=$function');", "AsSystemAdministrator");
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sshd_allowusers WHERE pattern='{$Info["name"]}'");
    if(!isset($ligne["pattern"])){
        $tpl->table_form_field_bool("{accept_ssh}",0,ico_terminal);
        VERBOSE("--> accept_ssh --> 0",__LINE__);
        return $tpl;
    }
    $tpl->table_form_field_bool("{accept_ssh}",$ligne["enabled"],ico_terminal);
// https://192.168.90.53:9000/fw.sshd.php?AuthorizedKeys-create-js=yes&function=ss1753642731&jQueryLjs=yes&_=1753628008726

    return user_popup_sshd_authorizedkeys($tpl,$Info);
}
function AuthorizedKeys_create_js():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    $uid=intval($_GET["AuthorizedKeys-create-js"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/genkeyid/$uid"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $user=$json->username;
    $id=md5($user);
    $userenc=urlencode($user);
    header("content-type: application/x-javascript");
    echo "LoadAjax('user-start-$id','$page?user-popup=$userenc&function=$function');\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Created a SSH Key for uid $uid");
}
function user_popup_sshd_authorizedkeys($tpl,$Info){
    $uid=$Info["uid"];
    $page=CurrentPageName();
    $function=$_GET["function"];
    $usernameEnc=urlencode($Info["name"]);
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    VERBOSE("--> SELECT * FROM sshd_authorizedkeys WHERE username='{$Info["name"]}'",__LINE__);
    $sql="SELECT * FROM sshd_authorizedkeys WHERE username='{$Info["name"]}'";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne) {
        $email=$ligne["email"];
        $zmd5=$ligne["zmd5"];
        $tpl->table_form_field_js("Loadjs('fw.sshd.php?AuthorizedKeys-add-js=yes&zmd5=$zmd5&function=$function&username=$usernameEnc')");
        $tpl->table_form_field_text("{public_ssh_key}",$email,ico_ssl);
    }
    $topbuttons[] = array("Loadjs('$page?AuthorizedKeys-create-js=$uid&function=$function')", ico_plus, "{public_ssh_key}:{generate_new_key}");
    VERBOSE("--> table_form_field_buttons",__LINE__);
    $tpl->table_form_field_buttons($topbuttons);
    return $tpl;
}
function user_delete_js():bool{
    $tpl=new template_admin();
    $id=intval($_GET["user-delete-js"]);
    $function=$_GET["function"];
    $name=$_GET["name"];
    return $tpl->js_confirm_delete($name, "user-delete", $id,"dialogInstance8.close();$function();", "AsSystemAdministrator");
}
function user_delete_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $uid=urlencode($_POST["user-delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/user/delete/id/$uid"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Delete the system user #$uid");
}
function user_isssh($arrayUser):bool{
    $SSHAllowUsers=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHAllowGroups");
    $AllowUsers=unserialize($SSHAllowUsers);
    if(!is_array($AllowUsers)){
        $AllowUsers=array();
    }
    $zAllowsUser=array();
    foreach ($AllowUsers as $User) {
        $zAllowsUser[$User]=true;
    }
    foreach ($arrayUser as $gid => $GroupName) {
        if(isset($zAllowsUser[$GroupName])){
            return true;
        }
    }
    return false;

}
function groupid_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["groupid-popup"]);
    $function=$_GET["function"];
    $Info=GroupInfo($id);
    $name=$Info["name"];
    $m=$Info["members"];
    $c=0;
    foreach ($m as $member) {
        if(strlen($member)<2){continue;}
        $c++;
    }
    $GroupName=$Info["name"];
    $tpl->table_form_field_text("{group}","<span style='text-decoration:none'>$GroupName</span>",
        ico_group);
    $tpl->table_form_field_text("{members}",$c,ico_users);

    if(!isBanned($name)) {
        $EnableOpenSSH = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenSSH"));
        if ($EnableOpenSSH == 1) {
            $SSHAllowGroups = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHAllowGroups");
            $AllowGroups = unserialize($SSHAllowGroups);
            if (!is_array($AllowGroups)) {
                $AllowGroups = array();
            }
            $zAllowsGroup = array();
            foreach ($AllowGroups as $GpName) {
                $zAllowsGroup[$GpName] = true;
            }
            if ($id > 0) {
                $tpl->table_form_field_js("Loadjs('$page?ssh-switch=$id&function=$function');", "AsSystemAdministrator");
            }
            if (isset($zAllowsGroup[$GroupName])) {
                $tpl->table_form_field_bool("{accept_ssh}", 1, ico_terminal);
            } else {
                $tpl->table_form_field_bool("{accept_ssh}", 0, ico_terminal);
            }
        }
    }
    echo $tpl->table_form_compile();
    return true;
}
function groupid_ssh():bool{
    $page=CurrentPageName();
    $id=intval($_GET["ssh-switch"]);
    $Info=GroupInfo($id);
    $groupname=$Info["name"];
    VERBOSE("groupname=$groupname iD:$id",__LINE__);
    $SSHAllowGroups=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHAllowGroups");
    $AllowGroups=unserialize($SSHAllowGroups);
    if(!is_array($AllowGroups)){
        $AllowGroups=array();
    }
    $zAllowsGroup=array();
    foreach ($AllowGroups as $GpName) {
        $zAllowsGroup[$GpName]=true;
    }
    if(isset($zAllowsGroup[$groupname])){
        unset($zAllowsGroup[$groupname]);
        VERBOSE("Remove SSH access for $groupname", true);
        $text="Remove SSH access for $groupname";
    }else{
        VERBOSE("ADD SSH access for $groupname", true);
        $zAllowsGroup[$groupname]=true;
        $text="Add SSH access for $groupname";
    }
    $grps=array();
    foreach ($zAllowsGroup as $groupname => $none) {
        $grps[]=$groupname;
    }
    if($GLOBALS["VERBOSE"]){
       print_r($grps);
    }
    $function=$_GET["function"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHAllowGroups",serialize($grps));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHAllowGroupsForGo",implode(",",$grps));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('groupid-start-$id','$page?groupid-popup=$id&function=$function');";
    return admin_tracks($text);

}
function groupid_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["groupid-buttons"]);
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?groupid-useradd-js=$id&function=$function&function2=$function2');", ico_plus, "{new_member}");
    $topbuttons[] = array("Loadjs('$page?groupid-userlink-js=$id&functionSrc=$function&function2=$function2');", ico_link, "{link_member}");


    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function groupid_members():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $id=intval($_GET["groupid-members"]);
     echo "<div id='buttons-groupid-$id' style='margin:5px'></div>";
    echo $tpl->search_block($page,"","","","&groupid-search=$id&function=$function&function2=$function");
    return true;
}
function membersGroups():array{

    if(!isset($GLOBALS["MEMBERS"])){
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
        if(!$data->Status){

            return array();
        }
        if(!property_exists($data,"UsersAndGroups")){
            return array();
        }

        foreach ($data->UsersAndGroups->users as $jsClass) {

            $name = $jsClass->name;
            $uid = $jsClass->uid;
            $gid = $jsClass->gid;
            $gecos = $jsClass->gecos;
            $home = $jsClass->home;
            $shell = $jsClass->shell;
            $GLOBALS["MEMBERS"][$name]["uid"] = $uid;
            $GLOBALS["MEMBERS"][$name]["gid"] = $gid;
            $GLOBALS["MEMBERS"][$name]["gecos"] = $gecos;
            $GLOBALS["MEMBERS"][$name]["home"] = $home;
            $GLOBALS["MEMBERS"][$name]["shell"] = $shell;

            $GLOBALS["MEMBERS"][$gid]["name"] = $name;
            $GLOBALS["MEMBERS"][$gid]["gid"] = $gid;
            $GLOBALS["MEMBERS"][$gid]["gecos"] = $gecos;
            $GLOBALS["MEMBERS"][$gid]["home"] = $home;
            $GLOBALS["MEMBERS"][$gid]["shell"] = $shell;
        }

    }
    return $GLOBALS["MEMBERS"];
}
function groupid_search():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $function = $_GET["function"];
    $function2 = $_GET["function2"];
    $id = intval($_GET["groupid-search"]);
    $Info = GroupInfo($id);
    //$GroupName=$Info["name"];
    $members = $Info["members"];
    $search = "";
    if (isset($_GET["search"])) {
        $search = "*" . $_GET["search"] . "*";
        $search = str_replace("**", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*", ".*?", $search);
    }

    $membersInfo = membersGroups();

    $html[] = "<table id='groupid_link_search' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>{link}</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";
    $TRCLASS="";
    $bton="";
    $gico=ico_member;
    foreach ($members as $member) {
        if(strlen($member)<2){
            continue;
        }
        if (strlen($search) > 1) {
            if (!preg_match("/" . $search . "/", $member)) {
                continue;
            }
        }
    $info = $membersInfo[$member];
    $gecos = $info["gecos"];
    $uid=intval($info["uid"]);
    if (strlen($gecos) < 2) {$gecos = "Home: {$info["home"]}";}
    if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
    $md=md5("tt".$member);
    if($uid>0) {
        $bton = $tpl->icon_unlink("Loadjs('$page?groupid-userunlink-js=$id&function=$function&function2=$function2&member=$member&md=$md')", "$member");
    }

    $memberencoded=urlencode($member);
    $member=$tpl->td_href($member,"","Loadjs('$page?user-js=$memberencoded&function=$function')");


    $html[] = "<tr class='$TRCLASS' id='$md'>";
    $html[] = "<td style='width:15%' nowrap><strong style='font-size:14px'><i class='$gico'></i>&nbsp;$member</strong> ($gecos)</td>";
     $html[] = "<td style='width:1%' nowrap><strong>$bton</strong></td>";
     $html[] = "</tr>";
    }


    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="
        <script>
        LoadAjaxTiny('buttons-groupid-$id','$page?groupid-buttons=$id&function=$function&function2=$function2');
        NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
        </script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function groupid_userunlink_js():bool{
    $tpl=new template_admin();
    $gpid=intval($_GET["groupid-userunlink-js"]);
    $member=urlencode($_GET["member"]);
    $md=$_GET["md"];
   // $page=CurrentPageName();
   // $function=$_GET["function"];
    $function2=$_GET["function2"];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/unjoin/$gpid/$member"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "$function2();\n";
    return admin_tracks("Removing user $member from group $gpid");
}
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{system_users}",ico_group,"{system_users_explain}","$page?start=yes","system-users","progress-system-users-restart",false,"table-loader-swap");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}
function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
    return true;
}
function isBanned($GroupName):bool{
    $banned["sudo"]=true;
    $banned["clamav"]=true;
    $banned["dnscatz"]=true;
    $banned["postfix"]=true;
    $banned["crontab"]=true;
    $banned["openldap"]=true;
    $banned["munin"]=true;
    $banned["quaggavty"]=true;
    $banned["quagga"]=true;
    $banned["smokeping"]=true;
    $banned["apt-mirror"]=true;
    $banned["manticore"]=true;
    $banned["redsocks"]=true;
    $banned["mosquitto"]=true;
    $banned["ziproxy"]=true;
    $banned["uucp"]=true;
    $banned["sys"]=true;
    $banned["proxy"]=true;
    $banned["man"]=true;
    $banned["mail"]=true;
    $banned["daemon"]=true;
    $banned["kibana"]=true;
    $banned["avahi"]=true;
    $banned["prads"]=true;
    $banned["netdev"]=true;
    $banned["nogroup"]=true;
    $banned["plugdev"]=true;
    $banned["sasl"]=true;
    $banned["policyd-spf"]=true;
    $banned["davfs2"]=true;
    $banned["vde2-net"]=true;
    $banned["glances"]=true;
    $banned["vnstat"]=true;
    $banned["freerad"]=true;
    $banned["mlocate"]=true;
    $banned["postdrop"]=true;
    $banned["tty"]=true;
    $banned["nvram"]=true;
    $banned["mysql"]=true;
    $banned["ntp"]=true;
    $banned["webunix"]=true;
    $banned["systemd-coredump"]=true;
    $banned["squid"]=true;
    $banned["ArticaStats"]=true;
    $banned["stunnel4"]=true;
    $banned["messagebus"]=true;
    $banned["cdrom"]=true;
    $banned["nobody"]=true;
    $banned["root"]=true;
    $banned["www-data"]=true;
    $banned["systemd-journal"]=true;
    $banned["systemd-timesync"]=true;
    $banned["systemd-network"]=true;
    $banned["systemd-resolve"]=true;
    $banned["ssh"]=true;
    $banned["winbindd_priv"]=true;
    $banned["unbound"]=true;
    $banned["wheel"]=true;
    $banned["redis"]=true;
    if(isset($banned[$GroupName])){
        return true;
    }


    return false;
}
function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $topbuttons=array();
    $function=$_GET["function"];
    $feature_disabled="";
    $topbuttons[] = array("Loadjs('$page?new-group-js=yes&function=$function')", ico_add_group, "{new_group}");
    $topbuttons[] = array("Loadjs('$page?groupid-useradd-js=-1&function=$function&nogroup=yes');", ico_add_user, "{create_user}");
    $topbuttons[] = array("Loadjs('$page?reconfigure-js=yes')", ico_retweet, "{reconfigure}");


    $uico=ico_user_circle;

    $EnableOpenSSH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenSSH"));
	$html[]="<table id='table-hd-disks' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>SSH</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$TRCLASS=null;

    $SSHAllowGroups=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHAllowGroups");
    $AllowGroups=unserialize($SSHAllowGroups);
    if(!is_array($AllowGroups)){
        $AllowGroups=array();
    }
    $zAllowsGroup=array();
    foreach ($AllowGroups as $GpName) {
        $zAllowsGroup[$GpName]=true;
    }

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
    if(!$data->Status){
        echo $tpl->div_error($data->Error);
        return false;
    }
    if(!property_exists($data,"UsersAndGroups")){
        echo $tpl->div_error("{no_data}");
        return false;
    }
    $search="";
    if(isset($_GET["search"])){
        $search="*".$_GET["search"]."*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*",".*?",$search);
    }


	foreach ($data->UsersAndGroups->groups as $jsClass){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $UsersFormatted="";
		$Name=$jsClass->name;
		$members=$jsClass->members;
		$gid=$jsClass->gid;
        $zMembers=array();
        $CountOfMembers=0;
        $md=md5(json_encode($jsClass));
        $gico=ico_users;
        if(strlen($search)>1){
            if(!preg_match("/".$search."/",$Name." ".implode(" ",$members))){
                continue;
            }
        }

        $zMembers[]="<table style='width:10%'>";
        $zMembers[]="<tr>";
        $zMembers[]="<td style='width:1%' nowrap>";
        $c=0;
        $ALR=array();
        foreach ($members as $member){
            if(strlen($member)<2){continue;}
            if(isset($ALR[$member])){continue;}
            $CountOfMembers++;
            $c++;
            $memberencoded=urlencode($member);
            $member=$tpl->td_href($member,"","Loadjs('$page?user-js=$memberencoded&function=$function')");
            $zMembers[]="<div><i class='$uico'></i>&nbsp;$member</div>";
            if($c>3){
                $zMembers[]="</td><td style='width:1%;padding-left:10px' nowrap>";
                $c=0;
            }
            $ALR[$member]=true;
        }
        if($CountOfMembers>0) {
            $UsersFormatted =@implode("\n", $zMembers)."</tr></table>";
        }

        $text_ssh="<span class='label label-default'>{inactive2}</span>";

        if(isset($zAllowsGroup[$Name])){
            $text_ssh="<span class='label label-primary'>{active2}</span>";
        }

        if($EnableOpenSSH==0){
            $text_ssh="<span class='label label-default'>{disabled}</span>";
        }

        $class='font-bold';
        $delete=$tpl->icon_delete("Loadjs('$page?groupid-delete-js=$gid&md=$md')");
        if(isBanned($Name)){
            $delete="&nbsp;";
            $gico="far fa-users-cog";
            $class="text-muted";
        }
        $Name=$tpl->td_href($Name,"","Loadjs('$page?groupid-js=$gid&function=$function')");

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:15%' nowrap><span class='$class' style='font-size:14px'><i class='$gico'></i>&nbsp;$Name</span></td>";
		$html[]="<td style='width:99%' class='$class' nowrap>$UsersFormatted</a></td>";
        $html[]="<td style='width:1%' nowrap><strong>$text_ssh</strong></td>";
        $html[]="<td style='width:1%' nowrap><strong>$delete</strong></td>";
		$html[]="</tr>";
    }

    $TINY_ARRAY["TITLE"]="{system_users}$feature_disabled";
    $TINY_ARRAY["ICO"]=ico_group;
    $TINY_ARRAY["EXPL"]="{system_users_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	
	$html[]="</tbody>";
	$html[]="</table>";
	$html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
	return true;
	
}






