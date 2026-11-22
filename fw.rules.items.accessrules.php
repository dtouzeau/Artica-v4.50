<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}

if(isset($_GET["page"])){page();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["enabled-js"])){enable_switch();exit;}
js();

function js(){
    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    if($gpid==0){
        return $tpl->js_error("gpid == 0 ???");
    }
    $tpl->js_dialog10("{items}","$page?page=$gpid&function2=$function2");

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["gpid"]);
    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }
    if($gpid==0){
        echo $tpl->div_error("gpid == 0 ???");
        return false;
    }

    echo $tpl->search_block($page,null,null,null,"&gpid=$gpid&function2=$function2");
    return true;
}
function enable_switch():bool{
    $ruleid=intval($_GET["enabled-js"]);
    $gpid=intval($_GET["gpid"]);
    $tpl=new template_admin();
    if($gpid==0){
        return $tpl->js_error("gpid == 0 ???");
    }

    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }

    // Check if the item exists via REST API (pattern == ruleid, gpid == $gpid)
    $existing = acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
    $found_id = 0;
    if(is_array($existing)){
        foreach($existing as $item){
            if(intval($item["pattern"] ?? $item["id"] ?? 0) == $ruleid){
                $found_id = intval($item["id"] ?? 0);
                break;
            }
        }
    }
    $tpl=new template_admin();
    if($found_id==0){
        $uid=$_SESSION["uid"];
        if($uid==-100){$uid="Manager";}
        $date=date("Y-m-d H:i:s");
        $desc="by $uid on $date";
        [$ok,$data] = acls_api_send("POST","/proxy/acls/sqlite/items",[
            "pattern"     => (string)$ruleid,
            "gpid"        => $gpid,
            "enabled"     => 1,
            "description" => $desc,
        ]);
        if(!$ok){
            echo $tpl->js_error($data);
            return false;
        }
        header("content-type: application/x-javascript");
        if(strlen($function2)>3){
            echo "$function2()\n";
        }
        return admin_tracks("Add access rule TAG $ruleid to group $gpid");
    }

    [$ok,$data] = acls_api_send("DELETE","/proxy/acls/sqlite/items/$found_id");
    if(!$ok){
        echo $tpl->js_error($data);
        return false;
    }
    header("content-type: application/x-javascript");
    if(strlen($function2)>3){
        echo "$function2()\n";
    }
    return admin_tracks("Remove access rule TAG $ruleid to group $gpid");
}

function search(){
    $tpl=new template_admin();
    $function=$_GET["function"];
    $function2="";
    if(isset($_GET["function2"])) {
        $function2 = $_GET["function2"];
    }
    $page=CurrentPageName();
    $Go_Shield_Server_Enable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));

    if($Go_Shield_Server_Enable==0){
        echo $tpl->div_error("{ERROR_GO_SHIELD_SERVER_DISABLED}");
        return false;
    }
    $gpid=intval($_GET["gpid"]);

    // Build set of item patterns already linked to this group
    $ALREADY=array();
    $existing = acls_api_get("/proxy/acls/sqlite/items?gpid=$gpid");
    if(is_array($existing)){
        foreach($existing as $item){
            $pat = intval($item["pattern"] ?? 0);
            if($pat > 0){ $ALREADY[$pat] = true; }
        }
    }

    $t=time();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{rules}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='rulesitemsac-rows'>";

    $search=trim($_GET["search"]);
    $limit=30;
    if(strlen($search)>1) {
        if(preg_match("#(rows|max|limit)=([0-9]+)#i",$search,$re)){
            $limit=intval($re[2]);
            $search=str_replace("$re[1]=$re[2]","",$search);
        }
    }
    if($limit==0){$limit=30;}

    // Fetch simple ACLs from REST API (ruleaction=2 filter applied client-side)
    $qs = "/proxy/simple-acls";
    $all_acls = acls_api_get($qs);
    if(!is_array($all_acls)){ $all_acls = []; }

    // Filter to ruleaction==2 and optional search
    $filtered = [];
    $search_pat = "";
    if(strlen($search)>1){
        $search_pat = str_replace(["*","%"],"",$search);
    }
    foreach($all_acls as $acl){
        if(intval($acl["ruleaction"] ?? 0) != 2){ continue; }
        if($search_pat != "" && stripos($acl["aclname"] ?? "", $search_pat) === false){ continue; }
        $filtered[] = $acl;
        if(count($filtered) >= $limit){ break; }
    }
    writelogs("simple-acls count=".count($filtered)." gpid=$gpid",__FUNCTION__,__FILE__,__LINE__);

    $TRCLASS="";
    foreach ($filtered as $ligne){
        $ID=$ligne["id"];
        $enabled=0;
        if(isset($ALREADY[$ID])){$enabled=1;}
        $aclname=$ligne["aclname"] ?? "";
        if(strlen($aclname)==0){$aclname="{unknown}";}

        $enableico=$tpl->icon_check($enabled,"Loadjs('$page?enabled-js=$ID&gpid=$gpid&function2=$function2')");;

        $TRCLASS=null;
        $html[]= "<tr class='$TRCLASS'>";
        $html[]= "<td><strong>$aclname</strong></td>";
        $html[]= "<td style='width:1%'>$enableico</td>";
        $html[]= "</tr>";
    }


    $html[]="</tbody>";
    $html[]="";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='rulesitemsac-pager'></span></div></div></div>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rulesitemsac-rows","rulesitemsac-pager");
    return true;
}