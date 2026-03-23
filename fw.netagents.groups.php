<?php
// Network Agents Groups Management Page
// CRUD groups, manage group membership, group-based actions (upgrade, certs, packages)
//
// Entry points:
//   default (no params)                → page shell with groups table
//   ?gpid={id}                         → group detail dialog (called from fw.netagents.list.php)
//   ?groups-table=yes                  → groups list (AJAX)
//   ?group-agents-table={id}           → agents in group table (AJAX inside dialog)
//   ?group-actions={id}                → action results panel (AJAX)
//
// REST API:
//   GET  /netagents/groups             → list all groups
//   POST /netagents/groups             → create group (name, description)
//   GET  /netagents/groups/{id}        → get group with agents
//   POST /netagents/groups/{id}        → update group
//   GET  /netagents/groups/delete/{id} → delete group
//   POST /netagents/groups/{id}/agents/{agent_id}         → add agent
//   GET  /netagents/groups/{id}/agents/delete/{agent_id}  → remove agent

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

// ── POST handlers ──
if(isset($_POST["group_save"])){group_save();exit;}
if(isset($_POST["group-edit-save"])){group_edit_save();exit;}
if(isset($_POST["group-delete"])){group_delete();exit;}
if(isset($_GET["blacklisted"])){blacklisted_save();exit;}

// ── GET handlers ──
if(isset($_GET["auto"])){load_auto_start();exit;}
if(isset($_GET["load-td-dpkg"])){load_td_dpkg();exit;}
if(isset($_GET["load-td-dhcp"])){load_td_dhcp();exit;}
if(isset($_GET["load-td-agents"])){load_td_agents();exit;}


if(isset($_GET["groups-start"])){group_start();exit;}
if(isset($_GET["group-remove-agent"])){group_remove_agent();exit;}
if(isset($_GET["group-delete-js"])){group_delete_js();exit;}
if(isset($_GET["group-add-js"])){group_add_js();exit;}
if(isset($_GET["group-add-popup"])){group_add_popup();exit;}
if(isset($_GET["group-edit-js"])){group_edit_js();exit;}
if(isset($_GET["group-edit-popup"])){group_edit_popup();exit;}
if(isset($_GET["group-add-agent-js"])){group_add_agent_js();exit;}
if(isset($_GET["group-add-agent-popup"])){group_add_agent_popup();exit;}
if(isset($_GET["group-select-agent"])){group_select_agent();exit;}
if(isset($_GET["group-renew-certs"])){group_renew_certs();exit;}
if(isset($_GET["group-upgrade-js"])){group_upgrade_js();exit;}
if(isset($_GET["group-upgrade-popup"])){group_upgrade_popup();exit;}
if(isset($_GET["group-upgrade-api"])){group_upgrade_api();exit;}
if(isset($_GET["group-upgrade-result"])){group_upgrade_result();exit;}
if(isset($_GET["group-packages"])){group_packages();exit;}
if(isset($_GET["group-agents-search"])){group_agents_search();exit;}
if(isset($_GET["group-agents-table"])){group_agents_table();exit;}
if(isset($_GET["group-action-buttons"])){group_agents_buttons();exit;}
if(isset($_GET["gpid"])){group_detail();exit;}
if(isset($_GET["groups-table"])){groups_table();exit;}

page();

// ════════════════════════════════════════════════════════════════
// DEFAULT PAGE SHELL
// ════════════════════════════════════════════════════════════════

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{agent_groups}",
        "fas fa-layer-group","{agent_groups_explain}","$page?groups-start=yes",
        "netagents-groups","progress-netagents-groups",false,"table-netagents-groups");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{agent_groups}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ════════════════════════════════════════════════════════════════
// GROUPS TABLE (main content)
// ════════════════════════════════════════════════════════════════
function group_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div id='page-netagents-groups'></div>";
    echo $tpl->search_block($page,"","","","&groups-table=yes");
    return true;
}
function groups_table():bool{
    $tpl=new template_admin();
    $function="";
    if(isset($_GET["function"])) {
        $function = $_GET["function"];
    }
    $page=CurrentPageName();
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups");
    $groups=json_decode($raw);
    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->div_error(json_last_error_msg());
        return false;
    }

    // Handle outFalse response
    if(is_object($groups)&&property_exists($groups,"Status")&&!$groups->Status){
        echo $tpl->div_error($groups->Error??"Error");
        return false;
    }
    $AutoID=array();
    $newgroupjs="Loadjs('$page?group-add-js=yes&function=$function');";

    $topbuttons[]=array($newgroupjs, ico_plus,"{new_group}");

    if(!is_array($groups)||count($groups)===0){
        $f=array();
        $TINY_ARRAY["TITLE"]="{agent_groups}";
        $TINY_ARRAY["ICO"]="fas fa-layer-group";
        $TINY_ARRAY["EXPL"]="{agent_groups_explain}";
        $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
        $Add=$tpl->button_autnonome("{new_group}",$newgroupjs,ico_plus,
            "AsArticaMetaAdmin",350,"btn-info");
        $f[]=$tpl->div_warning("{no_data}<div class='center' style='margin:30px'>$Add</div>");
        $f[]="<script>$jstiny</script>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$f));
        return true;
    }

    $t=time();
    $f=array();
    $search=$_GET["search"];


    $f[]="<table id='table-$t' class='table table-striped' data-page-size='50'>";
    $f[]="<thead><tr>";
    $f[]="  <th data-sortable='true' data-type='text'>{name}</th>";
    $f[]="  <th data-sortable='true' data-type='text'>DHCP</th>";
    $f[]="  <th data-sortable='true' data-type='text'>NET</th>";
    $f[]="  <th data-sortable='true' data-type='text'>&nbsp;</th>";
    $f[]="  <th data-sortable='true' data-type='text'>{agents}</th>";
    $f[]="  <th data-sortable='true' data-type='text'>&nbsp;</th>";
    $f[]="  <th data-sortable='true' data-type='text'>{created}</th>";
    $f[]="  <th></th>";
    $f[]="  <th></th>";
    $f[]="  <th></th>";
    $f[]="  <th></th>";
    $f[]="</tr></thead><tbody>";

    $TRCLASS=null;
    $fontSize="26px";
    foreach($groups as $g){
        if(!is_null($search) && strlen($search)>1){
            if(!preg_match("#$search#i",serialize($g))){
                continue;
            }
        }


        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id=intval($g->id);
        $name=htmlspecialchars($g->name);
        $desc=htmlspecialchars($g->description??"");

        $dhcpico_link="";
        $created="";
        if(!empty($g->created_at)&&$g->created_at!=="0001-01-01T00:00:00Z"){
            $dt=new DateTime($g->created_at);
            $created=$tpl->time_to_date($dt->getTimestamp());
        }
        $tr_id="tr-group-$id";
        $blacklisted=$g->blacklisted;
        $name_link=$tpl->td_href($name,"","Loadjs('$page?gpid=$id&function=$function')");
        $name_link="<i class='fas fa-layer-group fa-2x' style='color:#1ab394'></i>&nbsp;&nbsp;<span style='font-size:$fontSize'>$name_link</span> </a>";


        $desc="<div class='text-muted' style='font-size: 16px;font-style: italic;'>$desc</div>";
        $apt_icon="<span id='td-apt-$id'></span>";
        $countBadge="<div id='td-agt-$id' style='min-width: 85px'></div>";
        $AutoID[]=$id;
        $as_dhcp=$g->as_dhcp;
        if($as_dhcp){
            $dhcpico="<span class='label label-success'>DHCP</span>";
            $dhcpico_link=$tpl->td_href($dhcpico,"","Loadjs('fw.netagents.group.dhcp.php?dhcp-js=$id')");
        }


        $f[]="<tr id='$tr_id' class='$TRCLASS'>";
        $f[]="  <td style='width:99%;'>$name_link$desc</td>";
        $f[]="  <td style='width:1%' nowrap><span id='td-dhcp-$id'>$dhcpico_link</span></td>";
        $f[]="  <td style='width:1%' nowrap>".$tpl->icon_parameters("Loadjs('fw.netagents.groups.network.php?id=$id')",
                "AsArticaMetaAdmin","{network_settings}")."</td>";
        $f[]="  <td style='width:1%;text-align:center'>".$tpl->icon_check($blacklisted,
                "Loadjs('$page?blacklisted=$id&function=$function')","","AsArticaMetaAdmin","{deny_from_updates}")."</td>";
        $f[]="  <td style='width:1%;text-align:center'>$countBadge</td>";
        $f[]="  <td style='width:1%' nowrap>$apt_icon</td>";
        $f[]="  <td style='width:1%' nowrap>$created</td>";
        $f[]="  <td style='width:1%' nowrap>".$tpl->icon_add("Loadjs('$page?group-add-agent-js=$id&function=$function')","","{link_agent}")."</td>";
        $f[]="  <td style='width:1%' nowrap>".$tpl->icon_edit_field("Loadjs('$page?group-edit-js=$id')","","{parameters}")."</td>";
        $f[]="  <td style='width:1%' nowrap>".$tpl->icon_cd("Loadjs('fw.netagents.groups.software.php?id=$id')","","{deploy_artica_software}")."</td>";
        $f[]="  <td style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?group-delete-js=$id&md=$tr_id')")."</td>";
        $f[]="</tr>";
    }

    $f[]="</tbody>";
    $f[]="</table>";
    $f[]="  </div>";
    $f[]="</div>";

    $TINY_ARRAY["TITLE"]="{agent_groups}";
    $TINY_ARRAY["ICO"]="fas fa-layer-group";
    $TINY_ARRAY["EXPL"]="{agent_groups_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $ids=@implode(",",$AutoID);
    $refresh=$tpl->RefreshInterval_Loadjs("page-netagents-groups",$page,"auto=$ids",5);

    $f[]="<script>";
    $f[]="NoSpinner();";
    $f[]=implode("\n",$tpl->ICON_SCRIPTS);
    $f[]=$jstiny;
    $f[]=$refresh;
    $f[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}
function blacklisted_save():bool{
    $id=intval($_GET["blacklisted"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);

    $blacklistedR=$json["blacklisted"];
    if(!$blacklistedR){
        $txt="Blacklisted from updates";
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/blacklist/group/$id",array());
    }else{
        $txt="Allowed from updates";
        $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/netagents/blacklist/group/$id");
    }
    $name=$json["name"];
    return admin_tracks("Set group $name $txt");
}



// ════════════════════════════════════════════════════════════════
// GROUP DETAIL DIALOG (called from fw.netagents.list.php via Loadjs)
// ════════════════════════════════════════════════════════════════

function group_detail():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["gpid"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        return $tpl->js_error("{error}");
    }
    $name=htmlspecialchars($json->name);
    return $tpl->js_dialog5("<i class='".ico_group."'></i> $name","$page?group-agents-table=$id",950);
}

// ════════════════════════════════════════════════════════════════
// AGENTS TABLE INSIDE GROUP DETAIL DIALOG
// ════════════════════════════════════════════════════════════════
function group_agents_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $id=intval($_GET["group-agents-search"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        echo $tpl->div_error("{error}");
        return false;
    }

    $newgAgentjs="Loadjs('$page?group-add-agent-js=$id&function=$function')";

    $agents=isset($json->agents)&&is_array($json->agents)?$json->agents:array();
    if(count($agents)===0) {
        $Add=$tpl->button_autnonome("{add_agent}",$newgAgentjs,ico_plus,
            "AsArticaMetaAdmin",350,"btn-warning");
        $f[]=$tpl->div_warning("{noagentgroupfound}<div class='center' style='margin:30px'>$Add</div>");
        echo $tpl->_ENGINE_parse_body(implode("\n", $f));
        return true;
    }

    $f[]="<table class='table table-striped table-hover' style='margin-bottom:0'>";
    $f[]="<thead><tr style='background:#f5f5f5'>";
    $f[]="  <th style='width:1%'></th>";
    $f[]="  <th>{status}</th>";
    $f[]="  <th>{hostname}</th>";
    $f[]="  <th>{address}</th>";
    $f[]="  <th>{version}</th>";
    $f[]="  <th></th>";
    $f[]="</tr></thead><tbody>";

    $search=$_GET["search"];

    foreach($agents as $agent){
        $aid=intval($agent->id);
        if(strlen($search)>1){
            if(!preg_match("#$search#",json_encode($agent))){
                continue;
            }
        }


        $hostname=htmlspecialchars($agent->hostname??"#$aid");
        $ip=htmlspecialchars($agent->ip_address??"-");
        $port=intval($agent->port??28811);
        $version=htmlspecialchars($agent->version??"");
        $status=strtolower($agent->status??"unknown");

        $status_class="default";
        $status_label=strtoupper($status);
        if($status==="online"){$status_class="primary";}
        elseif($status==="offline"||$status==="error"){$status_class="danger";}
        elseif($status==="pending"){$status_class="warning";}

        $tr_id="tr-grp-agent-$aid";

        $f[]="<tr id='$tr_id'>";
        $f[]="  <td style='width:1%' nowrap>";
        if(intval($agent->artica??0)===1){
            $f[]="    <i class='fas fa-cube' style='color:#1ab394' title='Artica'></i>";
        }else{
            $f[]="    <i class='".ico_server."' style='color:#888'></i>";
        }
        $f[]="  </td>";
        $f[]="  <td style='width:1%' nowrap><span class='label label-$status_class'>$status_label</span></td>";
        $f[]="  <td><a href='javascript:void(0)' onclick=\"Loadjs('fw.netagents.list.php?agent-info-js=$aid')\" style='font-weight:600'>$hostname</a></td>";
        $f[]="  <td nowrap><span style='font-family:monospace'>$ip:$port</span></td>";
        $f[]="  <td nowrap>$version</td>";
        $f[]="  <td style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?group-remove-agent=$id&agent=$aid&md=$tr_id')")."</td>";
        $f[]="</tr>";
    }
    $f[]="</tbody></table>";
    $f[]="<script>LoadAjaxSilent('group-action-buttons-$id','$page?group-action-buttons=$id&function=$function');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;


}
function group_agents_buttons():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["group-action-buttons"]);
    $function=$_GET["function"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        echo $tpl->div_error("{error}");
        return false;
    }

    $name=htmlspecialchars($json->name);
    $desc=htmlspecialchars($json->description??"");
    $count=intval($json->agent_count??0);

    $f[]="<div style='background:#f9f9f9;border:1px solid #e7eaec;border-radius:3px;padding:12px 15px;margin-bottom:15px'>";
    $f[]="  <div class='row'>";
    $f[]="    <div class='col-md-4'>";
    $f[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>{name}</span><br>";
    $f[]="      <strong style='font-size:16px'><i class='".ico_group."' style='color:#1ab394'></i>&nbsp;$name</strong>";
    $f[]="    </div>";
    $f[]="    <div class='col-md-4'>";
    $f[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>{description}</span><br>";
    $f[]="      <span class='text-muted'>".(!empty($desc)?$desc:"-")."</span>";
    $f[]="    </div>";
    $f[]="    <div class='col-md-2'>";
    $f[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>{agents_list}</span><br>";
    $f[]="      <span class='badge' style='background:#1ab394;color:#fff;font-size:14px'>$count</span>";
    $f[]="    </div>";
    $f[]="    <div class='col-md-2 text-right' style='padding-top:8px'>";
    $f[]="      <a href='javascript:void(0)' onclick=\"Loadjs('$page?group-edit-js=$id&function=$function')\" class='btn btn-xs btn-white'>";
    $f[]="        <i class='".ico_edit."'></i>&nbsp;{edit}";
    $f[]="      </a>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="</div>";

    // ── Action buttons ──
    $f[]="<div style='margin-bottom:15px'>";
    $f[]="  <a href='javascript:void(0)' onclick=\"Loadjs('$page?group-add-agent-js=$id&function=$function')\" class='btn btn-sm btn-primary' style='margin-right:5px'>";
    $f[]="    <i class='".ico_plus."'></i>&nbsp;{link_agent}";
    $f[]="  </a>";
    $f[]="  <a href='javascript:void(0)' onclick=\"Loadjs('$page?group-upgrade-js=$id&function=$function')\" class='btn btn-sm btn-info' style='margin-right:5px'>";
    $f[]="    <i class='".ico_arrow_up."'></i>&nbsp;{update_agent}";
    $f[]="  </a>";
    $f[]="  <a href='javascript:void(0)' onclick=\"LoadAjax('group-action-result','$page?group-renew-certs=$id&function=$function')\" class='btn btn-sm btn-warning' style='margin-right:5px'>";
    $f[]="    <i class='".ico_lock."'></i>&nbsp;{certificate} {refresh}";
    $f[]="  </a>";
    $f[]="  <a href='javascript:void(0)' onclick=\"LoadAjax('group-action-result','$page?group-packages=$id&function=$function')\" class='btn btn-sm btn-default'>";
    $f[]="    <i class='".ico_box."'></i>&nbsp;{packages}";
    $f[]="  </a>";
    $f[]="</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}
function group_agents_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["group-agents-table"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        echo $tpl->div_error("{error}");
        return false;
    }
    $f=array();
    // ── Action result area ──
    $f[]="<div div style='margin-bottom:15px' id='group-action-buttons-$id'></div>";
    $f[]="<div id='group-action-result'></div>";
    $f[]=$tpl->search_block($page,"","","","&group-agents-search=$id");
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ════════════════════════════════════════════════════════════════
// CREATE GROUP
// ════════════════════════════════════════════════════════════════

function group_add_js():bool{
    $function=$_GET['function'];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog10("<i class='".ico_plus."'></i> {new_group}","$page?group-add-popup=yes&function=$function",550);
}

function group_add_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET['function'];
    $form=array();
    $form[]=$tpl->field_text("group_name","{name}","",true);
    $form[]=$tpl->field_text("group_description","{description}","");
    $form[]=$tpl->field_checkbox("group_cluster_mode","{dhcp_cluster_mode}",0,false,"{dhcp_cluster_mode_explain}");
    $form[]=$tpl->field_hidden("group_save","1");
    echo $tpl->form_outside("",
        $form,"","{add}",
        "dialogInstance10.close();$function();",
        "AsArticaMetaAdmin");
    return true;
}

function group_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $name=trim($_POST["group_name"]??"");
    $desc=trim($_POST["group_description"]??"");
    if(strlen($name)<1){
        echo $tpl->post_error("{name}: {required}");
        return false;
    }
    $data=array("name"=>$name,"description"=>$desc,"cluster_mode"=>intval($_POST["group_cluster_mode"]??0));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/groups",$data));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->post_error(__LINE__.") ".$json->Error??"Error");
        return false;
    }
    admin_tracks("Created agent group: $name");
    return true;
}

// ════════════════════════════════════════════════════════════════
// EDIT GROUP
// ════════════════════════════════════════════════════════════════

function group_edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-edit-js"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $name="";
    if(is_object($json)&&property_exists($json,"name")){
        $name=htmlspecialchars($json->name);
    }
    return $tpl->js_dialog8("<i class='".ico_edit."'></i> $name","$page?group-edit-popup=$id",550);
}

function group_edit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-edit-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        echo $tpl->div_error("{error}");
        return false;
    }
    $form=array();
    $form[]=$tpl->field_text("group-name","{name}",$json->name,true);
    $form[]=$tpl->field_text("group-description","{description}",$json->description??"");
    $clusterMode=intval($json->cluster_mode??0);
    $form[]=$tpl->field_checkbox("group-cluster-mode","{dhcp_cluster_mode}",$clusterMode,false,"{dhcp_cluster_mode_explain}");
    $form[]=$tpl->field_hidden("group-edit-save","$id");
    echo $tpl->form_outside("",
        $form,"","{apply}",
        "dialogInstance8.close();LoadAjax('table-netagents-groups','$page?groups-table=yes');",
        "AsArticaMetaAdmin");
    return true;
}

function group_edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id=intval($_POST["group-edit-save"]);
    $name=trim($_POST["group-name"]);
    $desc=trim($_POST["group-description"]);
    if(strlen($name)<1){
        echo $tpl->post_error("Err.".__LINE__." {name}: {required}");
        return false;
    }
    $data=array("name"=>$name,"description"=>$desc,"cluster_mode"=>intval($_POST["group-cluster-mode"]??0));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/groups/$id",$data));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->post_error("#$id Err.".__LINE__." ".$json->Error??"Error");
        return false;
    }
    admin_tracks("Updated agent group identity #$id: $name ($desc)");
    return true;
}

// ════════════════════════════════════════════════════════════════
// DELETE GROUP
// ════════════════════════════════════════════════════════════════

function group_delete_js():bool{
    $tpl=new template_admin();
    $id=intval($_GET["group-delete-js"]);
    $md=$_GET["md"]??"";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    $name="#$id";
    if(is_object($json)&&property_exists($json,"name")){
        $name=$json->name;
    }
    return $tpl->js_confirm_delete($name,"group-delete",$id,"$('#$md').remove()");
}

function group_delete():bool{
    $tpl=new template_admin();
    $id=intval($_POST["group-delete"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/delete/$id"));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->post_error($json->Error??"Error");
        return false;
    }
    admin_tracks("Deleted agent group #$id");
    return true;
}

// ════════════════════════════════════════════════════════════════
// ADD AGENT TO GROUP
// ════════════════════════════════════════════════════════════════

function group_add_agent_js():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();
    $id=intval($_GET["group-add-agent-js"]);
    return $tpl->js_dialog3("<i class='".ico_plus."'></i> {new_agent}","$page?group-add-agent-popup=$id&function=$function",800);
}

function group_add_agent_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $function=$_GET["function"];
    $id=intval($_GET["group-add-agent-popup"]);

    // Fetch all agents
    $all_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list");
    $all_json=json_decode($all_raw);
    $all_agents=array();
    if(is_object($all_json)&&isset($all_json->agents)&&is_array($all_json->agents)){
        $all_agents=$all_json->agents;
    }

    // Fetch group agents to exclude already-added
    $grp_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id");
    $grp_json=json_decode($grp_raw);
    $existing=array();
    if(is_object($grp_json)&&isset($grp_json->agents)&&is_array($grp_json->agents)){
        foreach($grp_json->agents as $a){
            $existing[intval($a->id)]=true;
        }
    }

    // Build available agents rows
    $rows=array();
    $TRCLASS=null;
    foreach($all_agents as $a){
        $aid=intval($a->id);
        if(isset($existing[$aid])){continue;}
        if(intval($a->enabled??1)===0){continue;}
        $hostname=htmlspecialchars($a->hostname??"#$aid");
        $ip=htmlspecialchars($a->ip_address??"-");
        $port=intval($a->port??28811);
        $version=htmlspecialchars($a->version??"");
        $status=strtolower($a->status??"unknown");

        $status_class="default";
        $status_label=strtoupper($status);
        if($status==="online"){$status_class="primary";}
        elseif($status==="offline"||$status==="error"){$status_class="danger";}
        elseif($status==="pending"){$status_class="warning";}

        $tr_id="sel-agent-$aid";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $select_btn="<a href='javascript:void(0)' ";
        $select_btn.="onclick=\"Loadjs('$page?group-select-agent=$id&agent=$aid&md=$tr_id&function=$function')\" ";
        $select_btn.="class='btn btn-xs btn-primary'>";
        $select_btn.="<i class='".ico_plus."'></i>&nbsp;{select}</a>";

        $rows[]="<tr id='$tr_id' class='$TRCLASS'>";
        $rows[]="  <td><span class='label label-$status_class'>$status_label</span></td>";
        $rows[]="  <td><strong>$hostname</strong></td>";
        $rows[]="  <td>$ip:$port</td>";
        $rows[]="  <td>$version</td>";
        $rows[]="  <td style='text-align:right'>$select_btn</td>";
        $rows[]="</tr>";
    }

    if(count($rows)===0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return true;
    }

    $html=array();
    $html[]="<table id='table-$t' class='footable table table-striped' data-page-size='50'>";
    $html[]="<thead><tr style='background:#f5f5f5'>";
    $html[]="  <th data-sortable=true data-type='text'>{status}</th>";
    $html[]="  <th data-sortable=true data-type='text'>{hostname}</th>";
    $html[]="  <th data-sortable=true data-type='text'>{address}</th>";
    $html[]="  <th data-sortable=true data-type='text'>{version}</th>";
    $html[]="  <th></th>";
    $html[]="</tr></thead><tbody>";
    $html[]=implode("\n",$rows);
    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='5'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){\$('#table-$t').footable({";
    $html[]="  \"filtering\":{\"enabled\":true},";
    $html[]="  \"sorting\":{\"enabled\":true},";
    $html[]="  \"paging\":{\"size\":50}";
    $html[]="});});</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function group_select_agent():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $group_id=intval($_GET["group-select-agent"]);
    $function=$_GET["function"];
    $agent_id=intval($_GET["agent"]??0);
    $md=$_GET["md"]??"";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/groups/$group_id/agents/$agent_id",array()));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        $err=is_object($json)&&property_exists($json,"Error")?$json->Error:"{error}";
        return $tpl->js_error($err);
    }

    admin_tracks("Added agent #$agent_id to group #$group_id");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo "$function();\n";
    return true;
}

// ════════════════════════════════════════════════════════════════
// REMOVE AGENT FROM GROUP
// ════════════════════════════════════════════════════════════════

function group_remove_agent():bool{
    $tpl=new template_admin();
    $group_id=intval($_GET["group-remove-agent"]);
    $agent_id=intval($_GET["agent"]??0);
    $md=$_GET["md"]??"";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$group_id/agents/delete/$agent_id"));
    if(is_object($json)&&property_exists($json,"Status")&&$json->Status){
        header("content-type: application/x-javascript");
        echo "$('#$md').fadeOut(300,function(){\$(this).remove();});\n";
        return true;
    }
    $err=is_object($json)&&property_exists($json,"Error")?$json->Error:"{error}";
    return $tpl->js_error($err);
}

// ════════════════════════════════════════════════════════════════
// GROUP UPGRADE
// ════════════════════════════════════════════════════════════════

function group_upgrade_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-upgrade-js"]);
    $CurVer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebianAgentStorageVersion");
    return $tpl->js_dialog5("<i class='".ico_arrow_up."'></i> {update_agent} v$CurVer","$page?group-upgrade-popup=$id",700);
}

function group_upgrade_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["group-upgrade-popup"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"));
    if(!is_object($json)||!property_exists($json,"name")){
        echo $tpl->div_error("{error}");
        return false;
    }
    $name=htmlspecialchars($json->name);
    $count=intval($json->agent_count??0);

    $f=array();
    $f[]="<div style='background:#f9f9f9;border:1px solid #e7eaec;border-radius:3px;padding:12px 15px;margin-bottom:15px'>";
    $f[]="  <i class='".ico_group."' style='color:#1ab394'></i>&nbsp;&nbsp;<strong>$name</strong>";
    $f[]="  &nbsp;&nbsp;<span class='badge' style='background:#1ab394;color:#fff'>$count {agents_list}</span>";
    $f[]="</div>";

    $f[]="<div style='text-align:center;margin:25px 0'>";
    $f[]="  <a href='javascript:void(0)' onclick=\"LoadAjax('group-upgrade-result','$page?group-upgrade-api=$id')\" class='btn btn-lg btn-primary'>";
    $f[]="    <i class='".ico_arrow_up."'></i>&nbsp;&nbsp;{update_agent}";
    $f[]="  </a>";
    $f[]="</div>";
    $f[]="<div id='group-upgrade-result'></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function group_upgrade_api():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["group-upgrade-api"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/upgrade/group/$id",array()));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->div_error($json->Error??"Error");
        return false;
    }

    $upgrade_id="";
    if(is_object($json)&&property_exists($json,"upgrade_id")){
        $upgrade_id=$json->upgrade_id;
    }

    $f=array();
    $f[]=$tpl->div_success("{success} — Upgrade started");
    if(!empty($upgrade_id)){
        $f[]="<div id='group-upgrade-progress'></div>";
        $enc_uid=urlencode($upgrade_id);
        $f[]="<script>";
        $f[]="function grpUpgPoll(){LoadAjax('group-upgrade-progress','$page?group-upgrade-result=$enc_uid');}";
        $f[]="grpUpgPoll();";
        $f[]="setInterval(grpUpgPoll,5000);";
        $f[]="</script>";
    }
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    admin_tracks("Started group upgrade for group #$id");
    return true;
}

function group_upgrade_result():bool{
    $tpl=new template_admin();
    $uid=urldecode($_GET["group-upgrade-result"]);
    $enc=urlencode($uid);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/upgrade/group/status/$enc"));
    if(!is_object($json)){
        echo $tpl->div_warning("{no_data}");
        return true;
    }

    $state=$json->state??"unknown";
    $total=intval($json->total_agents??0);
    $success=intval($json->success_count??0);
    $fail=intval($json->fail_count??0);
    $pending=intval($json->pending_count??0);

    $state_class=($state==="completed")?"success":(($state==="running")?"info":"warning");

    $f=array();
    $f[]="<div class='row' style='margin-bottom:10px'>";
    $f[]="  <div class='col-md-3'><span class='label label-$state_class'>".strtoupper($state)."</span></div>";
    $f[]="  <div class='col-md-3'><span class='badge' style='background:#1ab394;color:#fff'>$success</span> {success}</div>";
    $f[]="  <div class='col-md-3'><span class='badge' style='background:#ed5565;color:#fff'>$fail</span> {failed}</div>";
    $f[]="  <div class='col-md-3'><span class='badge' style='background:#f8ac59;color:#fff'>$pending</span> {pending}</div>";
    $f[]="</div>";

    // Per-agent results
    if(isset($json->agents)&&is_array($json->agents)&&count($json->agents)>0){
        $f[]="<table class='table table-striped' style='font-size:12px;margin-bottom:0'>";
        $f[]="<thead><tr><th>{hostname}</th><th>{status}</th><th>{version}</th></tr></thead><tbody>";
        foreach($json->agents as $a){
            $h=htmlspecialchars($a->hostname??"-");
            $s=$a->state??"pending";
            $sc=($s==="completed"||$s==="success")?"primary":(($s==="failed"||$s==="error")?"danger":"warning");
            $v=htmlspecialchars($a->version??"");
            $f[]="<tr><td>$h</td><td><span class='label label-$sc'>".strtoupper($s)."</span></td><td>$v</td></tr>";
        }
        $f[]="</tbody></table>";
    }

    // If still running, show spinner
    if($state==="running"||$state==="pending"){
        $f[]="<div class='text-center' style='padding:10px;color:#999'>";
        $f[]="  <i class='fa fa-spinner fa-spin'></i>&nbsp;&nbsp;{please_wait}...";
        $f[]="</div>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ════════════════════════════════════════════════════════════════
// RENEW CERTIFICATES FOR GROUP
// ════════════════════════════════════════════════════════════════

function group_renew_certs():bool{
    $tpl=new template_admin();
    $id=intval($_GET["group-renew-certs"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/certs/renew/group/$id",array()));

    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->div_error($json->Error??"Error");
        return false;
    }

    $total=intval($json->total??0);
    $success=intval($json->success_count??0);
    $fail=intval($json->fail_count??0);

    $f=array();
    $f[]="<div class='ibox' style='margin-top:10px'>";
    $f[]="  <div class='ibox-title'><h5><i class='".ico_lock."' style='color:#f8ac59'></i>&nbsp;&nbsp;{certificate} {refresh}</h5></div>";
    $f[]="  <div class='ibox-content'>";

    // Summary
    $f[]="<div class='row' style='margin-bottom:15px'>";
    $f[]="  <div class='col-md-4 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700'>$total</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{total}</div>";
    $f[]="  </div>";
    $f[]="  <div class='col-md-4 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700;color:#1ab394'>$success</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{success}</div>";
    $f[]="  </div>";
    $f[]="  <div class='col-md-4 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700;color:#ed5565'>$fail</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{failed}</div>";
    $f[]="  </div>";
    $f[]="</div>";

    // Per-agent results
    if(isset($json->results)&&is_array($json->results)&&count($json->results)>0){
        $f[]="<table class='table table-striped' style='font-size:12px;margin-bottom:0'>";
        $f[]="<thead><tr><th>{hostname}</th><th>{status}</th><th>{message}</th></tr></thead><tbody>";
        foreach($json->results as $r){
            $h=htmlspecialchars($r->hostname??"-");
            $ok=$r->success??false;
            $msg=htmlspecialchars($r->message??"");
            $sc=$ok?"primary":"danger";
            $sl=$ok?"{success}":"{failed}";
            $f[]="<tr><td>$h</td><td><span class='label label-$sc'>$sl</span></td><td class='text-muted'>$msg</td></tr>";
        }
        $f[]="</tbody></table>";
    }

    $f[]="  </div>";
    $f[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    admin_tracks("Renewed certificates for group #$id: $success/$total succeeded");
    return true;
}

// ════════════════════════════════════════════════════════════════
// GROUP PACKAGES STATS
// ════════════════════════════════════════════════════════════════

function group_packages():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["group-packages"]);

    // Trigger fetch first
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/packages/fetch/group/$id",array());

    // Get stats
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/group/$id?details=1"));
    if(is_object($json)&&property_exists($json,"Status")&&!$json->Status){
        echo $tpl->div_error($json->Error??"Error");
        return false;
    }

    $total_agents=intval($json->total_agents??0);
    $total_upgradable=intval($json->total_upgradable??0);

    $f=array();
    $f[]="<div class='ibox' style='margin-top:10px'>";
    $f[]="  <div class='ibox-title'><h5><i class='".ico_box."' style='color:#1c84c6'></i>&nbsp;&nbsp;{packages}</h5></div>";
    $f[]="  <div class='ibox-content'>";

    // Summary
    $f[]="<div class='row' style='margin-bottom:15px'>";
    $f[]="  <div class='col-md-6 text-center'>";
    $f[]="    <div style='font-size:28px;font-weight:700'>$total_agents</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{agents_list}</div>";
    $f[]="  </div>";
    $f[]="  <div class='col-md-6 text-center'>";
    $upgColor=$total_upgradable>0?"#f8ac59":"#1ab394";
    $f[]="    <div style='font-size:28px;font-weight:700;color:$upgColor'>$total_upgradable</div>";
    $f[]="    <div style='color:#999;font-size:11px;text-transform:uppercase'>{packages}</div>";
    $f[]="  </div>";
    $f[]="</div>";

    // Per-agent breakdown
    if(isset($json->agent_summaries)&&is_array($json->agent_summaries)&&count($json->agent_summaries)>0){
        $f[]="<table class='table table-striped' style='font-size:12px;margin-bottom:0'>";
        $f[]="<thead><tr><th>{hostname}</th><th>{packages}</th><th>{status}</th></tr></thead><tbody>";
        foreach($json->agent_summaries as $s){
            $h=htmlspecialchars($s->hostname??"-");
            $cnt=intval($s->upgradable_count??0);
            $has_err=$s->has_error??false;
            if($has_err){
                $badge="<span class='label label-danger'>{error}</span>";
            }elseif($cnt>0){
                $badge="<span class='badge' style='background:#f8ac59;color:#fff'>$cnt</span>";
            }else{
                $badge="<span class='badge' style='background:#1ab394;color:#fff'>0</span>";
            }
            $st=$has_err?htmlspecialchars($s->error_message??""):"<span class='text-muted'>OK</span>";
            $f[]="<tr><td>$h</td><td>$badge</td><td>$st</td></tr>";
        }
        $f[]="</tbody></table>";
    }

    $f[]="  </div>";
    $f[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

function load_auto_start():bool{
    $page=CurrentPageName();
    $f=array();
    $f[]="$(\"tr[id^='tr-group-']\").each(function () {";
    $f[]="var fullId = $(this).attr(\"id\");";
    $f[]="var id = fullId.replace(\"tr-group-\", \"\"); ";
        $f[]="LoadAjaxSilent('td-dhcp-'+id,'$page?load-td-dhcp='+id);";
        $f[]="LoadAjaxSilent('td-apt-'+id,'$page?load-td-dpkg='+id);";
        $f[]="LoadAjaxSilent('td-agt-'+id,'$page?load-td-agents='+id);";
    $f[]="});";


    header("content-type: application/x-javascript");
    echo implode("\n",$f);
    return true;
}

function load_td_dhcp():bool{
    $tpl=new template_admin();
    $id=intval($_GET["load-td-dhcp"]);
    if(!isset($GLOBALS["/netagents/groups/$id"])) {
        $GLOBALS["/netagents/groups/$id"] = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"), true);
    }
    $ligne=$GLOBALS["/netagents/groups/$id"];


    $as_dhcp=$ligne["as_dhcp"];
    if(!$as_dhcp) {
        return false;
    }
    $title="{APP_DHCP}";
    if($ligne["cluster_mode"]==1){
        $title="{APP_DHCP} ({cluster_mode})";
    }


    $dhcpico="<span class='label label-success'>$title</span>";
    $dhcpico_link=$tpl->td_href($dhcpico,"{dhcp_parameters}","Loadjs('fw.netagents.group.dhcp.php?dhcp-js=$id')");
    echo $tpl->_ENGINE_parse_body($dhcpico_link);
    return true;
}
function load_td_dpkg():bool{

    $id=intval($_GET["load-td-dpkg"]);
    if(!isset($GLOBALS["/netagents/groups/$id"])) {
        $GLOBALS["/netagents/groups/$id"] = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"), true);
    }
    $ligne=$GLOBALS["/netagents/groups/$id"];
    $blacklisted=$ligne["blacklisted"];
    if($blacklisted){
        return false;
    }
    if(!isset($ligne["upgradable_packages"])){
        return false;}
    if($ligne["upgradable_packages"]==0) {
        return false;
    }
    $tpl=new template_admin();
    $aptjs = "Loadjs('fw.netagents.groups.aptget.php?gpid=$id')";
    $apt_icon = $tpl->icon_badge_bell($ligne["upgradable_packages"], $aptjs, "AsArticaMetaAdmin",
        "text-warning","{nb_deb_pkgs}");

    echo $apt_icon;
    return true;
}
function load_td_agents():bool{
    $fontSize="28px";
    $icoA=ico_agent;
    $tpl=new template_admin();

    $id=intval($_GET["load-td-agents"]);

    $ligne=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$id"),true);
    $blacklisted=$ligne["blacklisted"];
    $count=intval($ligne["agent_count"]);
    $upgrade_agents=intval($ligne["upgrade_agents"]);
    list($tt,$none)=$tpl->Tooltips("gp-$id-nb-egnts","{nb_agents}");

    if($count==0){
        echo $tpl->_ENGINE_parse_body("<span class='text-muted' id='gp-$id-nb-egnts' $tt><i class='$icoA fa-2x'></i>&nbsp;<span style='font-weight: bold;font-size:$fontSize'>0</span></span>");
        return false;
    }

    $countBadge="<i class='$icoA fa-2x'></i>&nbsp;<span style='font-weight: bold;font-size:$fontSize'>$count</span>";

    if($upgrade_agents>0){
        $countBadge=$tpl->td_href($countBadge,"{nb_agents}<br><strong>{nb_agents_to_upgrade}","Loadjs('fw.netagents.list.php?agents-upgrade-js=0&gpid=$id')","AsArticaMetaAdmin");
        $countBadge="<span class='text-warning'>$countBadge</span>";
        echo $tpl->_ENGINE_parse_body($countBadge);
        return false;
    }
    $countBadge=$tpl->td_href($countBadge,"{nb_agents}","Loadjs('fw.netagents.list.php?agents-upgrade-js=0&gpid=$id')","AsArticaMetaAdmin");
    echo $tpl->_ENGINE_parse_body($countBadge);
    return false;
}