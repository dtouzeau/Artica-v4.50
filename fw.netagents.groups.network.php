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
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["tabs"])){tabs();exit;}
js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    $gpid=intval($_GET["id"]);
    $grp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$gpid"));
    $groupName=is_object($grp)?htmlspecialchars($grp->name??"Group #$gpid"):"Group #$gpid";
    return $tpl->js_dialog2("{network_settings} - $groupName","$page?tabs=$gpid",950);
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["tabs"]);
    $array["DNS"]="fw.netagents.group.dns.php?id=$gpid";
    echo $tpl->tabs_default($array);
    return true;
}