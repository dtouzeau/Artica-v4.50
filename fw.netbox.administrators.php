<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["delete"])){delete_admin_js();exit;}
if(isset($_POST["delete"])){delete_admin_confirm();exit;}
if(isset($_POST["username"])){create_adm_save();exit;}
if(isset($_GET["flat-start"])){flat_start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["create-adm-js"])){create_adm_js();exit;}
if(isset($_GET["create-adm-popup"])){create_adm_popup();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_NETBOX}",ico_administrator,"{APP_NETBOX_ADMIN_EXPLAIN}","$page?flat-start=yes","netbox-admins","progress-netbox-restart");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NETBOX}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}
function flat_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
    return true;
}
function delete_admin_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["delete"]);
    $username=$_GET["account"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete($username,"delete",$id,"$('#$md').remove()");
}
function delete_admin_confirm():bool{

    $id=intval($_POST["delete"]);
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netbox/admins/delete/$id"));
    if(!$data->Status){
        echo $data->Error;
        return false;
    }

    return admin_tracks("Removed Netbox user $id");

}
function create_adm_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{new_member}","$page?create-adm-popup=yes&function=$function",550);
}
function create_adm_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $form[]=$tpl->field_text("username","{account}","",true);
    $form[]=$tpl->field_password("password","{password}","",true);
    $form[]=$tpl->field_email("email","{email}","",true);
    echo $tpl->form_outside("",$form,"","{add}","dialogInstance2.close();$function()");
}
function create_adm_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $main=base64_encode(serialize($_POST));
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netbox/admins/create/$main"));
    if(!$data->Status){
        echo $tpl->post_error($data->Error);
        return false;
    }

    return admin_tracks("Create a new NetBox user {$_POST["username"]}");
}



function search(){
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netbox/admins/list"));
    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{username}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{email}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{created}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";

    $search=$_GET["search"];
    if(strlen($search)>0){
        $search=str_replace(".", "\.", $search);
        $search=str_replace("*", ".*?", $search);
        $search=str_replace("/", "\/", $search);
    }

    foreach ($data->Info as $Class){
        if($TRCLASS=="footable-odd"){$TRCLASS="";}else{$TRCLASS="footable-odd";}
        $username=$Class->username;
        $id=$Class->id;
        $Display=$Class->display;
        $first_name=$Class->first_name;
        $last_name=$Class->last_name;
        $is_staff=$Class->is_staff;
        $is_active=$Class->is_active;
        $date_joined=$Class->date_joined;
        $email=$Class->email;
        $md5=md5($username.$first_name.$last_name.$email);
        if(strlen($search)>0){
            if(!preg_match("#$search#i","$username.$first_name.$last_name.$email")){
                continue;
            }
        }

        $color="text-primary";
        $img=ico_user;
        if($is_staff){
            $img=ico_administrator;
            $color="text-default";
        }
        $tt=array();
        if(strlen($Display)>1){
            if(strtoupper($Display)<>strtoupper($username)) {
                $tt[] = $Display;
            }
        }
        if(strlen($first_name)>1){
            $tt[]=$first_name;
        }
        if(strlen($last_name)>1){
            $tt[]=$last_name;
        }
        if(count($tt)>0){
            $tt_text="<i>".@implode(" ",$tt)."</i>";
        }
        if(!$is_active){
            $color="text-default";
        }
        $dele=$tpl->icon_delete("Loadjs('$page?delete=$id&account=$username&md=$md5')");
        $html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td width=1% nowrap><i class='$img' class='$color'></td>";
        $html[]="<td><strong style=';color:$color;'>$username $tt_text</strong></td>";
        $html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$email</td>";
        $html[]="<td style='width:1%;color:$color;text-align:right' nowrap>$date_joined</td>";
        $html[]="<td style='width:1%;color:$color;padding-left:35px' nowrap>$dele</td>";
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

    $topbuttons[] = array("Loadjs('$page?create-adm-js=yes&function=$function')", ico_plus, "{new_member}");


    $TINY_ARRAY["TITLE"]="{APP_NETBOX} {administrators}";
    $TINY_ARRAY["ICO"]=ico_administrator;
    $TINY_ARRAY["EXPL"]="{APP_NETBOX_ADMIN_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]=$headsjs;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
}