<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.artica-samba.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$user=new usersMenus();
if(!$user->AsSambaAdministrator){die();}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["share-js"])){share_js();exit;}
if(isset($_GET["share-popup"])){share_popup();exit;}
if(isset($_POST["share-name"])){share_save();exit;}
if(isset($_GET["share-delete-js"])){share_delete_js();exit;}
if(isset($_POST["delete-share"])){share_delete_perform();exit;}
if(isset($_GET["share-advanced-js"])){share_advanced_js();exit;}
if(isset($_GET["share-advanced-popup"])){share_advanced_popup();exit;}
if(isset($_POST["share-advanced"])){share_advanced_save();exit;}

page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_SAMBA} &raquo;&raquo; {shares_center}",
        ico_directory,
        "{SAMBA_BTRFS_SHARES}",
        "$page?start=yes","shares","progress-shares",false,"table-samba-shares");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{shares_center}",$html);
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
    $html[]="<select id='samba-share-instance-selector' class='form-control' style='width:300px;display:inline-block' OnChange=\"SambaSharesReload();\">";
    foreach($options as $oid=>$olabel){
        $sel=($oid==$selectedId)?"selected":"";
        $html[]="<option value='$oid' $sel>$olabel</option>";
    }
    $html[]="</select>";
    $html[]="</div>";
    $html[]="<div id='shares-table-area'></div>";
    $html[]="<script>";
    $html[]="function SambaSharesReload(){";
    $html[]="  var iid=document.getElementById('samba-share-instance-selector').value;";
    $html[]="  LoadAjax('shares-table-area','$page?search=yes&instance-id='+iid);";
    $html[]="}";
    $html[]="SambaSharesReload();";
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
        $shares=$samba->listShares($instanceId);
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    $topbuttons=[];
    $topbuttons[]=array("Loadjs('$page?share-js=0&instance-id=$instanceId')","fas fa-folder-plus","{new_shared_folder}");

    $tdStyle1="style='width:1%' nowrap";
    $t=time();
    $html[]=$tpl->table_buttons($topbuttons);
    $html[]="<table id='table-shares-$t' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true data-type='text'>{path}</th>";
    $html[]="<th data-sortable=true data-type='text'>{description}</th>";
    $html[]="<th>{browseable}</th>";
    $html[]="<th>{ro}</th>";
    $html[]="<th>{guest}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th $tdStyle1>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach($shares as $share){
        $name=$share["name"] ?? "";
        if(strlen($search)>0 && !preg_match("#$search#i",json_encode($share))){continue;}

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(json_encode($share));
        $path=htmlspecialchars($share["path"] ?? "");
        $comment=htmlspecialchars($share["comment"] ?? "");
        $browseable=($share["browseable"] ?? false)?"<i class='".ico_check."' style='color:#1ab394'></i>":"<i class='fa fa-times' style='color:#ccc'></i>";
        $readOnly=($share["read_only"] ?? false)?"<i class='".ico_check."' style='color:#f8ac59'></i>":"<i class='fa fa-times' style='color:#ccc'></i>";
        $guestOk=($share["guest_ok"] ?? false)?"<i class='".ico_check."' style='color:#ed5565'></i>":"<i class='fa fa-times' style='color:#ccc'></i>";

        $nameEnc=urlencode($name);
        $editLink=$tpl->td_href(htmlspecialchars($name),null,"Loadjs('$page?share-js=$nameEnc&instance-id=$instanceId')");
        $advancedBtn=$tpl->icon_edit_field("Loadjs('$page?share-advanced-js=$nameEnc&instance-id=$instanceId')","AsSambaAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?share-delete-js=$nameEnc&instance-id=$instanceId&md=$md')","AsSambaAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdStyle1><i class='fas fa-folder' style='color:#f8ac59'></i></td>";
        $html[]="<td><strong>$editLink</strong></td>";
        $html[]="<td>$path</td>";
        $html[]="<td>$comment</td>";
        $html[]="<td $tdStyle1>$browseable</td>";
        $html[]="<td $tdStyle1>$readOnly</td>";
        $html[]="<td $tdStyle1>$guestOk</td>";
        $html[]="<td $tdStyle1>$advancedBtn</td>";
        $html[]="<td $tdStyle1>&nbsp;</td>";
        $html[]="<td $tdStyle1>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='10'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-shares-$t').footable({";
    $html[]="  \"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":{$GLOBALS["FOOTABLE_PSIZE"]}}";
    $html[]="});});</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Create / Edit Share ──────────────────────────────────────

function share_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $name=$_GET["share-js"];
    $instanceId=$_GET["instance-id"];
    $title=($name==="0"||strlen($name)==0)?"{new_shared_folder}":urldecode($name);
    return $tpl->js_dialog1($title,"$page?share-popup=$name&instance-id=$instanceId");
}

function share_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $name=urldecode($_GET["share-popup"]);
    $instanceId=$_GET["instance-id"];
    $button="{create}";
    $editing=false;

    $share=["name"=>"","path"=>"","comment"=>"","browseable"=>true,"read_only"=>false,"guest_ok"=>false];

    if($name!=="0" && strlen($name)>0){
        $samba=new ArticaSamba();
        try{
            $shares=$samba->listShares($instanceId);
            foreach($shares as $s){
                if(($s["name"] ?? "")===$name){
                    $share=$s;
                    $editing=true;
                    $button="{apply}";
                    break;
                }
            }
        }catch(\RuntimeException $e){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
            return false;
        }
    }

    $form[]=$tpl->field_hidden("share-instance-id",$instanceId);
    $form[]=$tpl->field_hidden("share-editing",$editing?"1":"0");
    $form[]=$tpl->field_hidden("share-original-name",$name);
    $form[]=$tpl->field_text("share-name","{share_name}",$share["name"] ?? "",true);
    $form[]=$tpl->field_text("share-path","{path}",$share["path"] ?? "",true);
    $form[]=$tpl->field_text("share-comment","{description}",$share["comment"] ?? "");
    $form[]=$tpl->field_checkbox("share-browseable","{visible_on_the_network}",($share["browseable"] ?? true)?1:0);
    $form[]=$tpl->field_checkbox("share-readonly","{ro}",($share["read_only"] ?? false)?1:0);
    $form[]=$tpl->field_checkbox("share-guestok","{PUBLIC_ACCESS}",($share["guest_ok"] ?? false)?1:0,false,"{public_text}");

    $js="dialogInstance1.close();SambaSharesReload();";
    $html[]=$tpl->form_outside("",$form,"",$button,$js,"AsSambaAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function share_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instanceId=trim($_POST["share-instance-id"]);
    $editing=intval($_POST["share-editing"])==1;
    $originalName=trim($_POST["share-original-name"]);
    $name=trim($_POST["share-name"]);
    $path=trim($_POST["share-path"]);
    $comment=trim($_POST["share-comment"] ?? "");
    $browseable=intval($_POST["share-browseable"] ?? 0)==1;
    $readOnly=intval($_POST["share-readonly"] ?? 0)==1;
    $guestOk=intval($_POST["share-guestok"] ?? 0)==1;

    if(strlen($name)<1){
        echo $tpl->post_error("{share_name} {required}");
        return false;
    }
    if(strlen($path)<1){
        echo $tpl->post_error("{path} {required}");
        return false;
    }

    $params=[
        "name"=>$name,
        "path"=>$path,
        "comment"=>$comment,
        "browseable"=>$browseable,
        "read_only"=>$readOnly,
        "guest_ok"=>$guestOk,
    ];

    $samba=new ArticaSamba();
    try{
        if($editing && strlen($originalName)>0 && $originalName!=="0"){
            $samba->updateShare($instanceId,$originalName,$params);
            admin_tracks("Updated share $name on instance $instanceId");
        }else{
            $samba->createShare($instanceId,$params);
            admin_tracks("Created share $name on instance $instanceId");
        }
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return true;
}

// ── Delete Share ─────────────────────────────────────────────

function share_delete_js():bool{
    $tpl=new template_admin();
    $name=urldecode($_GET["share-delete-js"]);
    $md=$_GET["md"];
    $deletejs="$('#$md').remove();";
    return $tpl->js_confirm_delete("{remove} {share_name} $name","delete-share",
        base64_encode(json_encode(["instance_id"=>$_GET["instance-id"],"name"=>$name])),
        $deletejs);
}

function share_delete_perform():bool{
    $tpl=new template_admin();
    $data=json_decode(base64_decode($_POST["delete-share"]),true);
    $instanceId=$data["instance_id"] ?? "";
    $name=$data["name"] ?? "";
    $samba=new ArticaSamba();
    try{
        $samba->deleteShare($instanceId,$name);
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Deleted share $name from instance $instanceId");
}

// ── Advanced Share Settings ──────────────────────────────────

function share_advanced_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $name=urldecode($_GET["share-advanced-js"]);
    $instanceId=$_GET["instance-id"];
    return $tpl->js_dialog2("{advanced_settings}: $name","$page?share-advanced-popup=$name&instance-id=$instanceId",650);
}

function share_advanced_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $name=urldecode($_GET["share-advanced-popup"]);
    $instanceId=$_GET["instance-id"];

    $share=[];
    $samba=new ArticaSamba();
    try{
        $shares=$samba->listShares($instanceId);
        foreach($shares as $s){
            if(($s["name"] ?? "")===$name){$share=$s;break;}
        }
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return false;
    }

    $ap=$share["access_policy"] ?? [];
    $authz=$share["authz"] ?? [];

    $accessModes=["open"=>"{open}","whitelist"=>"{whitelist}","blacklist"=>"{blacklist}"];

    $form[]=$tpl->field_hidden("share-advanced",$name);
    $form[]=$tpl->field_hidden("adv-instance-id",$instanceId);
    $form[]=$tpl->field_array_hash($accessModes,"adv-access-mode","{access_policy}",$ap["mode"] ?? "open");
    $form[]=$tpl->field_textarea("adv-allow-list","{allow_list}",$ap["allow_list"] ?? "",null,"100px");
    $form[]=$tpl->field_textarea("adv-deny-list","{deny_list}",$ap["deny_list"] ?? "",null,"100px");

    $authProviders=["local"=>"Local","ldap"=>"LDAP","ad"=>"Active Directory"];
    $form[]=$tpl->field_array_hash($authProviders,"adv-authz-provider","{auth_provider}",$authz["provider"] ?? "local");
    $form[]=$tpl->field_text("adv-read-groups","{read_groups}",$authz["read_groups"] ?? "");
    $form[]=$tpl->field_text("adv-write-groups","{write_groups}",$authz["write_groups"] ?? "");
    $form[]=$tpl->field_text("adv-deny-groups","{deny_groups}",$authz["deny_groups"] ?? "");
    $form[]=$tpl->field_text("adv-force-group","{force_group}",$authz["force_group"] ?? "");
    $form[]=$tpl->field_checkbox("adv-inherit-acls","{inherit_acls}",($authz["inherit_acls"] ?? false)?1:0);

    $js="dialogInstance2.close();SambaSharesReload();";
    $html[]=$tpl->form_outside("{advanced_settings}",$form,null,"{apply}",$js,"AsSambaAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function share_advanced_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $name=trim($_POST["share-advanced"]);
    $instanceId=trim($_POST["adv-instance-id"]);

    $params=[
        "access_policy"=>[
            "mode"=>trim($_POST["adv-access-mode"] ?? "open"),
            "allow_list"=>trim($_POST["adv-allow-list"] ?? ""),
            "deny_list"=>trim($_POST["adv-deny-list"] ?? ""),
        ],
        "authz"=>[
            "provider"=>trim($_POST["adv-authz-provider"] ?? "local"),
            "read_groups"=>trim($_POST["adv-read-groups"] ?? ""),
            "write_groups"=>trim($_POST["adv-write-groups"] ?? ""),
            "deny_groups"=>trim($_POST["adv-deny-groups"] ?? ""),
            "force_group"=>trim($_POST["adv-force-group"] ?? ""),
            "inherit_acls"=>intval($_POST["adv-inherit-acls"] ?? 0)==1,
        ],
    ];

    $samba=new ArticaSamba();
    try{
        $samba->updateShare($instanceId,$name,$params);
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Updated advanced settings for share $name on instance $instanceId");
}
