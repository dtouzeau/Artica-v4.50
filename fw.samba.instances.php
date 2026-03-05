<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-samba.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$user=new usersMenus();
if(!$user->AsSambaAdministrator){die();}

if(isset($_GET["allrows"])){RefreshTableRows();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["search"])){search();exit;}

if(isset($_GET["add-instance-js"])){add_instance_js();exit;}
if(isset($_GET["add-instance-popup"])){add_instance_popup();exit;}
if(isset($_POST["instance-name"])){add_instance_save();exit;}

if(isset($_GET["start-instance"])){start_instance();exit;}
if(isset($_GET["stop-instance"])){stop_instance();exit;}
if(isset($_GET["restart-instance"])){restart_instance();exit;}

if(isset($_GET["delete-instance-js"])){delete_instance_js();exit;}
if(isset($_POST["delete-instance"])){delete_instance_perform();exit;}

if(isset($_GET["info-instance"])){info_instance_js();exit;}
if(isset($_GET["info-instance-popup"])){info_instance_popup();exit;}
if(isset($_GET["info-instance-table"])){info_instance_table();exit;}

if(isset($_GET["resources-popup"])){resources_popup();exit;}
if(isset($_POST["resources-save"])){resources_save();exit;}

page();


function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_SAMBA} &raquo;&raquo; {instances}",
        "fa-solid fa-layer-group",
        "{APP_SAMBA_INSTANCES_EXPLAIN}",
        "$page?table=yes","share-instances","progress-samba-containers",false,"table-samba-containers");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: File-sharing instances",$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
    return true;
}

function search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $function_main=$_GET["function"];
    $topbuttons=array();
    $search=null;
    if(isset($_GET["search"])){$search=$_GET["search"];}

    if($users->AsSambaAdministrator){
        $topbuttons[]=array("Loadjs('$page?add-instance-js=0&function=$function_main')","fad fa-layer-plus","{new_instance2}");
    }

    $TINY_ARRAY["TITLE"]="{APP_SAMBA} &raquo;&raquo; {instances}";
    $TINY_ARRAY["ICO"]="fa-solid fa-layer-group";
    $TINY_ARRAY["EXPL"]="{APP_SAMBA_INSTANCES_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $samba=new ArticaSamba();
    try{
        $instances=$samba->listInstances();
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return false;
    }

    $tdStyle1="style='width:1%' nowrap";
    $html[]="<table id='table-samba-containers' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{status}</th>";
    $html[]="<th>{name}</th>";
    $html[]="<th>{mode}</th>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{interface}</th>";
    $html[]="<th>{autostart}</th>";
    $html[]="<th>{action}</th>";
    $html[]="<th style='width:1%' nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach($instances as $inst){
        if($search!==null && strlen($search)>0){
            if(!preg_match("#$search#i",json_encode($inst))){
                continue;
            }
        }
        $id=$inst["id"] ?? "";
        $md=md5(json_encode($inst));

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $state=td_state($inst);
        $name=td_name($inst,$function_main);
        $mode=td_mode($inst);
        $ip=td_ip($inst);
        $iface=td_interface($inst);
        $autostart=td_autostart($inst);
        $action=td_action($inst);
        $delete=$tpl->icon_delete("Loadjs('$page?delete-instance-js=$id&md=$md')","AsSambaAdministrator");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdStyle1><span id='samba-state-$id'>$state</span></td>";
        $html[]="<td nowrap><strong><span id='samba-name-$id'>$name</span></strong></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-mode-$id'>$mode</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-ip-$id'>$ip</span></td>";
        $html[]="<td nowrap><span id='samba-iface-$id'>$iface</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-autostart-$id'>$autostart</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-action-$id'>$action</span></td>";
        $html[]="<td $tdStyle1 class='center' nowrap>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $allrow=$tpl->RefreshInterval_Loadjs("table-samba-containers",$page,"allrows=yes&function=$function_main");
    $html[]=$allrow;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function RefreshTableRows(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $samba=new ArticaSamba();
    try{
        $instances=$samba->listInstances();
    }catch(\RuntimeException $e){
        return false;
    }
    $main=array();
    $f=array();
    $function_main=isset($_GET["function"])?$_GET["function"]:"";
    foreach($instances as $inst){
        $id=$inst["id"] ?? "";
        $main["samba-state-$id"]=base64_encode(td_state($inst));
        $main["samba-name-$id"]=base64_encode(td_name($inst,$function_main));
        $main["samba-mode-$id"]=base64_encode(td_mode($inst));
        $main["samba-ip-$id"]=base64_encode(td_ip($inst));
        $main["samba-iface-$id"]=base64_encode(td_interface($inst));
        $main["samba-autostart-$id"]=base64_encode(td_autostart($inst));
        $main["samba-action-$id"]=base64_encode(td_action($inst));
    }
    foreach($main as $id=>$value){
        $f[]="if(document.getElementById('$id')){";
        $f[]="document.getElementById('$id').innerHTML=base64_decode('$value');";
        $f[]="}";
    }
    echo @implode("\n",$f);
    return true;
}

// ── Instance Actions ─────────────────────────────────────────

function start_instance():bool{
    $tpl=new template_admin();
    $id=$_GET["start-instance"];
    $samba=new ArticaSamba();
    try{
        $samba->startInstance($id);
    }catch(\RuntimeException $e){
        echo $tpl->js_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Starting File Sharing instance $id");
}

function stop_instance():bool{
    $tpl=new template_admin();
    $id=$_GET["stop-instance"];
    $samba=new ArticaSamba();
    try{
        $samba->stopInstance($id);
    }catch(\RuntimeException $e){
        echo $tpl->js_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Stopping File Sharing instance $id");
}

function restart_instance():bool{
    $tpl=new template_admin();
    $id=$_GET["restart-instance"];
    $samba=new ArticaSamba();
    try{
        $samba->restartInstance($id);
    }catch(\RuntimeException $e){
        echo $tpl->js_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Restarting File Sharing instance $id");
}

// ── Create / Edit Instance ───────────────────────────────────

function add_instance_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $id=$_GET["add-instance-js"];
    $title="{new_instance2}";
    if(strlen($id)>0 && $id!=="0"){
        $samba=new ArticaSamba();
        try{
            $inst=$samba->getInstance($id);
            $title=htmlspecialchars($inst["name"] ?? $id);
        }catch(\RuntimeException $e){}
    }
    return $tpl->js_dialog2($title,"$page?add-instance-popup=$id&function-main=$function");
}

function add_instance_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_main=$_GET["function-main"];
    $id=$_GET["add-instance-popup"];
    $button="{create}";

    $inst=["name"=>"","mode"=>"standalone","interface"=>"","ip_address"=>"","auto_start"=>true,
           "realm"=>"","netbios_name"=>""];
    $editing=false;

    if(strlen($id)>0 && $id!=="0"){
        $samba=new ArticaSamba();
        try{
            $inst=$samba->getInstance($id);
            $button="{apply}";
            $editing=true;
        }catch(\RuntimeException $e){
            echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
            return false;
        }
    }

    $modes=["standalone"=>"{standalone_server}","ad_dc"=>"Active Directory DC","domain_member"=>"{domain_member}"];

    $form[]=$tpl->field_hidden("instance-id",$id);
    $form[]=$tpl->field_text("instance-name","{name}",$inst["name"] ?? "",true);
    $form[]=$tpl->field_array_hash($modes,"instance-mode","{mode}",$inst["mode"] ?? "standalone");
    $form[]=$tpl->field_text("instance-interface","{interface}",$inst["interface"] ?? "",true);
    $form[]=$tpl->field_text("instance-ip","{ipaddr}",$inst["ip_address"] ?? "",true,"{instance_ip_explain}");
    $form[]=$tpl->field_checkbox("instance-autostart","{autostart}",($inst["auto_start"] ?? true)?1:0);
    $form[]=$tpl->field_text("instance-realm","{realm}",$inst["realm"] ?? "",false,"{realm_explain_ad}");
    $form[]=$tpl->field_text("instance-netbios","{netbiosname}",$inst["netbios_name"] ?? "",false);

    if(!$editing){
        $form[]=$tpl->field_password2("instance-admin-password","{password} (Administrator)","",false);
    }

    $js="dialogInstance2.close();$function_main();";
    $html[]=$tpl->form_outside("",$form,"",$button,$js,"AsSambaAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function add_instance_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=trim($_POST["instance-id"] ?? "");
    $name=trim($_POST["instance-name"]);
    $mode=trim($_POST["instance-mode"] ?? "standalone");
    $iface=trim($_POST["instance-interface"] ?? "");
    $ip=trim($_POST["instance-ip"] ?? "");
    $autostart=intval($_POST["instance-autostart"] ?? 0);
    $realm=trim($_POST["instance-realm"] ?? "");
    $netbios=trim($_POST["instance-netbios"] ?? "");
    $adminPass=trim($_POST["instance-admin-password"] ?? "");

    if(strlen($name)<1){
        echo $tpl->post_error("{name} {required}");
        return false;
    }

    $params=[
        "name"=>$name,
        "mode"=>$mode,
        "interface"=>$iface,
        "ip_address"=>$ip,
        "auto_start"=>$autostart==1,
    ];
    if(strlen($realm)>0){$params["realm"]=$realm;}
    if(strlen($netbios)>0){$params["netbios_name"]=$netbios;}
    if(strlen($adminPass)>0){$params["admin_password"]=$adminPass;}

    $samba=new ArticaSamba();
    try{
        if(strlen($id)>0 && $id!=="0"){
            $samba->updateResources($id,$params);
            admin_tracks("Updated File Sharing instance $name #$id");
        }else{
            $samba->createInstance($params);
            admin_tracks("Created File Sharing instance $name ($mode)");
        }
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return true;
}

// ── Delete Instance ──────────────────────────────────────────

function delete_instance_js():bool{
    $tpl=new template_admin();
    $id=$_GET["delete-instance-js"];
    $md=$_GET["md"];
    $deletejs="$('#$md').remove();";
    return $tpl->js_confirm_delete("{remove} {instance} $id","delete-instance",$id,$deletejs);
}

function delete_instance_perform():bool{
    $tpl=new template_admin();
    $id=$_POST["delete-instance"];
    $samba=new ArticaSamba();
    try{
        $samba->deleteInstance($id,true);
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Deleted File Sharing instance $id");
}

// ── Instance Info ────────────────────────────────────────────

function info_instance_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["info-instance"];
    $function_main=$_GET["function-main"];
    $samba=new ArticaSamba();
    $title=$id;
    try{
        $inst=$samba->getInstance($id);
        $title=htmlspecialchars($inst["name"] ?? $id);
    }catch(\RuntimeException $e){}
    return $tpl->js_dialog($title,"$page?info-instance-popup=$id&function-main=$function_main");
}

function info_instance_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["info-instance-popup"];
    $function_main=$_GET["function-main"];
    $array["{details}"]="$page?info-instance-table=$id&function-main=$function_main";
    $array["{resources}"]="$page?resources-popup=$id&function-main=$function_main";
    echo $tpl->tabs_default($array);
    return true;
}

function info_instance_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["info-instance-table"];
    $function_main=$_GET["function-main"];
    $samba=new ArticaSamba();
    try{
        $inst=$samba->getInstance($id);
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return false;
    }

    $tpl->table_form_field_text("{ID}",$id,ico_computer);
    $tpl->table_form_field_text("{name}",htmlspecialchars($inst["name"] ?? ""),ico_computer);
    $tpl->table_form_field_text("{mode}",htmlspecialchars($inst["mode"] ?? ""),"fas fa-layer-group");
    $tpl->table_form_field_text("{status}",td_state($inst),"fas fa-circle");
    $tpl->table_form_field_text("{ipaddr}",htmlspecialchars($inst["ip_address"] ?? ""),ico_nic);
    $tpl->table_form_field_text("{interface}",htmlspecialchars($inst["interface"] ?? ""),ico_interface);
    $tpl->table_form_field_bool("{autostart}",$inst["auto_start"]?1:0,ico_check);

    if(!empty($inst["realm"])){
        $tpl->table_form_field_text("{realm}",htmlspecialchars($inst["realm"]),ico_networks);
    }
    if(!empty($inst["netbios_name"])){
        $tpl->table_form_field_text("{netbiosname}",htmlspecialchars($inst["netbios_name"]),ico_computer);
    }

    $tpl->table_form_field_js("Loadjs('$page?add-instance-js={$id}&function=$function_main')");

    echo $tpl->table_form_compile();
    return true;
}

// ── Resources ────────────────────────────────────────────────

function resources_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["resources-popup"];
    $function_main=$_GET["function-main"];

    $form[]=$tpl->field_hidden("resources-save",$id);
    $form[]=$tpl->field_text("cpu-quota","CPU Quota (%)","100",false,"{cpu_quota_explain}");
    $form[]=$tpl->field_text("memory-max","{memory} Max (MB)","512",false,"{memory_max_explain}");
    $form[]=$tpl->field_text("io-weight","IO Weight (1-10000)","100",false,"{io_weight_explain}");

    $html[]=$tpl->form_outside("{resources}",$form,null,"{apply}","","AsSambaAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function resources_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=$_POST["resources-save"];
    $limits=[];
    if(isset($_POST["cpu-quota"]) && strlen($_POST["cpu-quota"])>0){
        $limits["cpu_quota"]=intval($_POST["cpu-quota"]);
    }
    if(isset($_POST["memory-max"]) && strlen($_POST["memory-max"])>0){
        $limits["memory_max"]=intval($_POST["memory-max"]);
    }
    if(isset($_POST["io-weight"]) && strlen($_POST["io-weight"])>0){
        $limits["io_weight"]=intval($_POST["io-weight"]);
    }
    $samba=new ArticaSamba();
    try{
        $samba->updateResources($id,$limits);
    }catch(\RuntimeException $e){
        echo $tpl->post_error(htmlspecialchars($e->getMessage()));
        return false;
    }
    return admin_tracks("Updated resources for File Sharing instance $id");
}

// ── Table Cell Helpers ───────────────────────────────────────

function td_state(array $inst):string{
    $tpl=new template_admin();
    $state=$inst["state"] ?? "stopped";
    switch($state){
        case "running":
            return $tpl->_ENGINE_parse_body("<span class='label label-primary' style='color:#fff'>{running}</span>");
        case "starting":
            return $tpl->_ENGINE_parse_body("<span class='label label-info' style='color:#fff'>{starting}</span>");
        case "failed":
            return $tpl->_ENGINE_parse_body("<span class='label label-danger' style='color:#fff'>{failed}</span>");
        default:
            return $tpl->_ENGINE_parse_body("<span class='label label-default'>{stopped}</span>");
    }
}

function td_name(array $inst, string $function_main=""):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$inst["id"] ?? "";
    $name=htmlspecialchars($inst["name"] ?? $id);
    return $tpl->td_href($name,null,"Loadjs('$page?info-instance=$id&function-main=$function_main')");
}

function td_mode(array $inst):string{
    $mode=$inst["mode"] ?? "standalone";
    $labels=["standalone"=>"Standalone","ad_dc"=>"AD DC","domain_member"=>"Domain Member"];
    return "<i class='fas fa-layer-group'></i>&nbsp;".($labels[$mode] ?? $mode);
}

function td_ip(array $inst):string{
    $ip=$inst["ip_address"] ?? "";
    if(strlen($ip)==0){return "-";}
    return "<i class='".ico_nic."'></i>&nbsp;".$ip;
}

function td_interface(array $inst):string{
    $iface=$inst["interface"] ?? "";
    if(strlen($iface)==0){return "-";}
    return "<i class='".ico_interface."'></i>&nbsp;".$iface;
}

function td_autostart(array $inst):string{
    $tpl=new template_admin();
    $auto=$inst["auto_start"] ?? false;
    if($auto){
        return "<i class='".ico_check."' style='color:#1ab394'></i>";
    }
    return "<i class='fa fa-times' style='color:#ccc'></i>";
}

function td_action(array $inst):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$inst["id"] ?? "";
    $state=$inst["state"] ?? "stopped";

    if($state==="running"){
        return $tpl->icon_stop("Loadjs('$page?stop-instance=$id')","AsSambaAdministrator")
            ."&nbsp;".$tpl->icon_refresh("Loadjs('$page?restart-instance=$id')","AsSambaAdministrator");
    }
    return $tpl->icon_run("Loadjs('$page?start-instance=$id')","AsSambaAdministrator");
}
