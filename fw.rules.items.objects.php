<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    exit();
}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["js"])){js();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["new-item-js"])){new_item_js();exit;}
if(isset($_GET["item-popup"])){item_popup();exit;}
if(isset($_POST["item-add"])){item_add();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_GET["item-delete"])){item_delete();exit;}
if(isset($_POST["item-delete"])){item_delete_perform();exit;}
if(isset($_GET["link-item-js"])){link_item_js();exit;}
if(isset($_GET["link-item-popup"])){link_item_popup();exit;}
if(isset($_GET["search-link"])){link_item_search();exit;}
if(isset($_GET["link-perform"])){link_perform();exit;}
table_start();



function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=intval($_GET["groupid"]);
    unset($_GET["groupid"]);

    $URL_ADDONS=build_suffix();
    $qProxy=new mysql_squid_builder(true);
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $GroupName=is_array($grp) ? $grp["groupName"] : "";
    $GroupType=is_array($grp) ? ($qProxy->acl_GroupType[$grp["groupType"]] ?? $grp["groupType"]) : "";
    $tpl->js_dialog8("{group}: $GroupName $GroupType","$page?table-start=yes&gpid=$groupid&{$URL_ADDONS}",850);
}

function build_suffix(){
    if(isset($_GET["jQueryLjs"])){unset($_GET["jQueryLjs"]);}
    if(isset($_GET["_"])){unset($_GET["_"]);}
    if(isset($_GET["build-table"])){unset($_GET["build-table"]);}
    if(isset($_GET["js"])){unset($_GET["js"]);}
    if(isset($_GET["table"])){unset($_GET["table"]);}
    if(isset($_GET["table-start"])){unset($_GET["table-start"]);}
    if(isset($_GET["ID"])){unset($_GET["ID"]);}
    if(isset($_GET["new-item-js"])){unset($_GET["new-item-js"]);}
    if(isset($_GET["item-popup"])){unset($_GET["item-popup"]);}
    if(isset($_GET["link-item-js"])){unset($_GET["link-item-js"]);}
    if(isset($_GET["link-item-popup"])){unset($_GET["link-item-popup"]);}
    if(isset($_GET["search"])){unset($_GET["search"]);}
    if(isset($_GET["search-link"])){unset($_GET["search-link"]);}

    foreach ($_GET as $key=>$val){
        if($key=="table-start"){continue;}
        $cmds[]="$key=$val";
    }
    return @implode("&",$cmds);

}

// Compteurs d'items de TOUS les groupes en UN appel: /items-count-all fait le
// GROUP BY cote daemon, la page affiche donc un compteur par ligne sans le
// N+1 qu'un GET /items?gpid= par ligne aurait coute.
// Route absente d'un binaire ancien -> tableau vide: la ligne perd son
// compteur mais garde son ID, aucune ligne ne disparait.
function ritobj_items_count_all():array{
    $out=array();
    $counts=acls_api_get("/proxy/acls/sqlite/items-count-all");
    if(!is_array($counts)){return $out;}
    foreach($counts as $c){
        if(!is_array($c)){continue;}
        $out[intval($c["gpid"] ?? 0)]=intval($c["count"] ?? 0);
    }
    return $out;
}

// Les homonymes sont la regle des qu'une regle a ete importee plusieurs fois
// (AclsParse/import_rule.go cree un NOUVEAU groupe a chaque import, sans
// find-or-create): sept lignes portant le meme nom et le meme type se lisent
// comme un doublon d'affichage alors que ce sont sept groupes distincts.
// L'ID et le nombre d'items sont ce qui les separe -- et un groupe a 0 item
// est justement celui qu'il ne faut pas lier.
function ritobj_meta($gpid,$ITEMCOUNT):string{
    $gpid=intval($gpid);
    $meta="#$gpid";
    if(!isset($ITEMCOUNT[$gpid])){return $meta;}
    $n=intval($ITEMCOUNT[$gpid]);
    if($n==0){
        return "$meta &middot; <span style='color:#865300;font-weight:600'>"
              ."<i class='fas fa-triangle-exclamation'></i> {empty}</span>";
    }
    return "$meta &middot; $n {items}";
}

function new_item_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=intval($_GET["new-item-js"]);
    $URL_ADDONS=build_suffix();

    $qProxy=new mysql_squid_builder(true);
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $GroupName=is_array($grp) ? $grp["groupName"] : "";
    $GroupType=is_array($grp) ? ($qProxy->acl_GroupType[$grp["groupType"]] ?? $grp["groupType"]) : "";
    $tpl->js_dialog7("{new_object} - {group}: $GroupName $GroupType","$page?item-popup=$groupid&{$URL_ADDONS}",850);
}
function link_item_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupid=intval($_GET["link-item-js"]);
    $URL_ADDONS=build_suffix();

    $qProxy=new mysql_squid_builder(true);
    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $GroupName=is_array($grp) ? $grp["groupName"] : "";
    $GroupType=is_array($grp) ? ($qProxy->acl_GroupType[$grp["groupType"]] ?? $grp["groupType"]) : "";
    $tpl->js_dialog7("{link} - {group}: $GroupName $GroupType","$page?link-item-popup=$groupid&{$URL_ADDONS}",850);
}
function link_item_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GroupID=intval($_GET["link-item-popup"]);
    $URL_ADDONS=build_suffix();
    echo $tpl->search_block($page,null,null,null,"&search-link=$GroupID&$URL_ADDONS");

}
function link_perform(){
    $md=$_GET["md"];
    $MainGpid=intval($_GET["link-perform"]);
    $gpid=intval($_GET["gpid"]);
    $run=refresh_get();
    $tpl=new template_admin();

    list($ok,$data)=acls_api_send("POST","/proxy/acls/sqlite/gpslink",["groupID"=>$MainGpid,"gpid"=>$gpid]);
    if(!$ok){
        writelogs("ERROR $data",__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->js_error("API Error\n".$data);
        return false;
    }

    $grpMaster=acls_api_get("/proxy/acls/sqlite/group/$MainGpid");
    $ManGropName=is_array($grpMaster) ? $grpMaster["groupName"] : $MainGpid;
    $grpSlave=acls_api_get("/proxy/acls/sqlite/group/$gpid");
    $GroupNameSlave=is_array($grpSlave) ? $grpSlave["groupName"] : $gpid;
    $GroupType=is_array($grpSlave) ? $grpSlave["groupType"] : "";
    admin_tracks("Link object $GroupNameSlave/$GroupType to $ManGropName");

    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n$run\n";

    return true;
}

function link_item_search(){
   // Array ( [search] => [search-link] => 0 [myRefresh] => myRefresh1659552890 [js-after] => [function] => ss1659552933 [TableLink] => [RefreshTable] => [ProxyPac] => [firewall] => [types] => )
    $page=CurrentPageName();
    $tpl=new template_admin();
    $maingroup_id=intval($_GET["search-link"]);
    $search=trim($_GET["search"]);
    $qProxy=new mysql_squid_builder(true);

    // Build exclusion set: gpids already linked to this group
    $UNSET=array();
    $existing=acls_api_get("/proxy/acls/sqlite/gpslinks?groupid=$maingroup_id");
    if(is_array($existing)){
        foreach($existing as $lnk){
            $UNSET[intval($lnk["gpid"])]=true;
        }
    }

    // Fetch all groups and filter client-side (no search endpoint on groups; reuse /groups/all pattern)
    // out-of-scope: no dedicated search API for sqgroups by name/type — use GET /proxy/acls/sqlite/groups/all if available,
    // otherwise fall back to listing children of the parent and doing a broader fetch.
    // For now, fetch all groups via the existing list endpoint used elsewhere in WebConsole.
    $allGroups=acls_api_get("/proxy/acls/sqlite/groups/all");
    // Build search pattern (same logic as legacy SQL LIKE)
    $searchPattern=null;
    if($search!==""){
        $searchPattern=strtolower("*$search*");
        $searchPattern=str_replace("**","*",$searchPattern);
    }

    $ITEMCOUNT=ritobj_items_count_all();

    $suffix=build_suffix();
    $t=time();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead>";
    // Un <tr> etait ouvert DEUX FOIS: le navigateur refermait le premier de
    // force et l'en-tete comptait une ligne vide.
    $html[]="<tr>";
    $html[]="<th>{objects}</th>";
    $html[]="<th>{type}</th>";
    $html[]="<th>{select}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ritobj1-rows'>";

    $TRCLASS=null;

    if(!is_array($allGroups)){
        $html[]="</tbody></table></div><div class='awc-foot'><span>&nbsp;</span><span class='awc-pg' id='ritobj1-pager'></span></div></div></div>";
        echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ritobj1-rows","ritobj1-pager");
        return;
    }

    foreach ($allGroups as $ligne) {
        $class = "font-bold";
        $GroupName = $ligne["groupName"];
        $GroupType = $ligne["groupType"];
        $gpid=intval($ligne["id"]);
        if($GroupType=="AclsGroup"){continue;}
        if(isset($UNSET[$gpid])){continue;}
        // Apply search filter
        if($searchPattern!==null){
            $nameMatch=fnmatch($searchPattern,strtolower($GroupName));
            $typeMatch=fnmatch($searchPattern,strtolower($GroupType));
            if(!$nameMatch && !$typeMatch){continue;}
        }

        $TRCLASS=null;
        $md=md5(serialize($ligne));

        $js="Loadjs('$page?link-perform=$maingroup_id&gpid=$gpid&md=$md&$suffix');";
        // /groups/all ne renvoie que {id,groupName,groupType} (struct Go
        // AclsSqliteGroupListItem): sans ce defaut, intval(null) grisait
        // TOUTES les lignes -- et emettait un undefined array key en PHP 8.
        $enabled = isset($ligne["enabled"]) ? intval($ligne["enabled"]) : 1;
        $GroupIcon = "<i class='".($qProxy->acl_GroupTypeIcon[$GroupType] ?? "fas fa-cube")."'></i>";
        $GrText = $qProxy->GroupTypeToString($GroupType);
        $button = "<button class='awc-btn' OnClick=\"$js\">{select}</button>";
        if ($enabled == 0) {
            $class="text-muted";
        }

        // La ligne ne fermait PAS son <tr>: le navigateur les refermait de
        // force, et un pager qui compte les <tr> ne voyait qu'une ligne sur deux.
        $html[] = "<tr id='$md'>";
        $html[] = "<td><div class='awc-ident'>"
                 ."<span class='awc-ico".($enabled==0?" awc-ico-off":"")."'>$GroupIcon</span>"
                 ."<span><span class='awc-name".($enabled==0?" awc-name-off":"")."'>".htmlspecialchars((string)$GroupName)."</span>"
                 ."<div class='awc-meta'>".ritobj_meta($gpid,$ITEMCOUNT)."</div></span>"
                 ."</div></td>";
        $html[] = "<td class='awc-nowrap awc-mono'>$GrText <small>(".htmlspecialchars((string)$GroupType).")</small></td>";
        $html[] = "<td><div class='awc-acts' style='opacity:1'>$button</div></td>";
        $html[] = "</tr>";

    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='ritobj1-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse (accolades JavaScript).
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body($html)
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ritobj1-rows","ritobj1-pager");
}


function refresh_get(){
    $js_after=null;
    $RefreshFunction=null;

    $js=array();
    if(isset($_GET["myRefresh"])){
        if (trim($_GET["myRefresh"]) <> null) {
            // Pentest 2026-11-04 : nom de fonction JS, rien d'autre.
            $js[]=preg_replace('/[^A-Za-z0-9_.]/','',(string)$_GET["myRefresh"])."()";
        }
    }

    if(isset($_GET["RefreshFunction"])) {
        if (trim($_GET["RefreshFunction"]) <> null) {
            $RefreshFunction = preg_replace('/[^A-Za-z0-9_.]/','',(string)base64_decode($_GET["RefreshFunction"]));
        }
    }
    if(isset($_GET["js-after"])){
        if (trim($_GET["js-after"]) <> null) {
            $js_after=jsafter_sanitize(base64_decode($_GET["js-after"]));
        }
    }


    if($RefreshFunction<>null){
        $js[]="$RefreshFunction()";
    }
    if($js_after<>null){
        $js[]=$js_after;
    }

    return @implode(";",$js);
}

function item_popup(){

    $groupid=intval($_GET["item-popup"]);
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $btname="{add}";

    $grp=acls_api_get("/proxy/acls/sqlite/group/$groupid");
    $GroupNameMaster=is_array($grp) ? $grp["groupName"] : $groupid;
    $title="{new_object} / $GroupNameMaster";
    $tpl->field_hidden("item-add", $groupid);
    $form[]=$tpl->field_text("GroupName","{object_name}","{new_group}");
    $form[]=$tpl->field_acls_groups("GroupType","{type}",null,true);
    $html=$tpl->form_outside($title,$form,null,$btname,refresh_get());
    echo $html;

}
function  item_delete_perform(){
    $tpl=new template_admin();
    $zmd5=$_POST["item-delete"];

    // Fetch link to get groupid/gpid for audit trail before deleting
    $lnk=acls_api_get("/proxy/acls/sqlite/gpslink/$zmd5");
    $groupid=is_array($lnk) ? intval($lnk["groupID"]) : 0;
    $gpid=is_array($lnk) ? intval($lnk["gpid"]) : 0;

    $grpMaster=($groupid>0) ? acls_api_get("/proxy/acls/sqlite/group/$groupid") : null;
    $GroupNameMaster=is_array($grpMaster) ? $grpMaster["groupName"] : $groupid;
    $grpSlave=($gpid>0) ? acls_api_get("/proxy/acls/sqlite/group/$gpid") : null;
    $GroupNameSlave=is_array($grpSlave) ? $grpSlave["groupName"] : $gpid;

    list($ok,$err)=acls_api_send("DELETE","/proxy/acls/sqlite/gpslink/$zmd5");
    if(!$ok){echo $err;return false; }
    $text="Proxy: Unlink acl object $GroupNameSlave from $GroupNameMaster";
    admin_tracks($text);
    return true;

}
function item_delete(){
    $tpl=new template_admin();
    $zmd5=$_GET["item-delete"];

    $lnk=acls_api_get("/proxy/acls/sqlite/gpslink/$zmd5");
    $groupid=is_array($lnk) ? intval($lnk["groupID"]) : 0;
    $gpid=is_array($lnk) ? intval($lnk["gpid"]) : 0;

    $grpMaster=($groupid>0) ? acls_api_get("/proxy/acls/sqlite/group/$groupid") : null;
    $GroupNameMaster=is_array($grpMaster) ? $grpMaster["groupName"] : $groupid;
    $grpSlave=($gpid>0) ? acls_api_get("/proxy/acls/sqlite/group/$gpid") : null;
    $GroupNameSlave=is_array($grpSlave) ? $grpSlave["groupName"] : $gpid;

    echo $tpl->js_confirm_delete("{unlink} $GroupNameSlave {from} $GroupNameMaster","item-delete",$zmd5,
        "$('#$zmd5').remove();");


}

function item_enable(){
    $tpl=new template_admin();
    $zmd5=$_GET["item-enable"];

    $lnk=acls_api_get("/proxy/acls/sqlite/gpslink/$zmd5");
    if(!is_array($lnk)){echo $tpl->js_error_stop("gpslink not found: $zmd5");return false;}
    $groupid=intval($lnk["groupID"]);
    $gpid=intval($lnk["gpid"]);
    $enabled=intval($lnk["enabled"]);

    $grpMaster=($groupid>0) ? acls_api_get("/proxy/acls/sqlite/group/$groupid") : null;
    $GroupNameMaster=is_array($grpMaster) ? $grpMaster["groupName"] : $groupid;
    $grpSlave=($gpid>0) ? acls_api_get("/proxy/acls/sqlite/group/$gpid") : null;
    $GroupNameSlave=is_array($grpSlave) ? $grpSlave["groupName"] : $gpid;

    $newEnabled=($enabled==0) ? 1 : 0;
    list($ok,$err)=acls_api_send("PUT","/proxy/acls/sqlite/gpslink/$zmd5/enable",["enabled"=>$newEnabled]);
    if(!$ok){echo $tpl->js_error_stop($err);return false;}

    if($newEnabled==1){
        $text="Link acls object $GroupNameSlave to acl object group $GroupNameMaster";
    } else {
        $text="unlink acls object $GroupNameSlave to acl object group $GroupNameMaster";
    }
    admin_tracks($text);
    return true;

}

function item_add(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $GroupName=trim($_POST["GroupName"]);
    $GroupType=$_POST["GroupType"];
    $MainGPID=intval($_POST["item-add"]);

    // Create the new child group via API
    list($okGrp,$grpData)=acls_api_send("POST","/proxy/acls/sqlite/group",[
        "name"    => $GroupName,
        "type"    => $GroupType,
    ]);
    if(!$okGrp){
        writelogs("ERROR creating group: $grpData".__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->post_error("API Error\n".$grpData);
        return false;
    }
    $newgpid=is_array($grpData) ? intval($grpData["id"] ?? 0) : 0;
    if($newgpid==0){
        echo $tpl->post_error("API Error: no group ID returned");
        return false;
    }

    // Link new child group to parent group
    list($okLnk,$lnkErr)=acls_api_send("POST","/proxy/acls/sqlite/gpslink",[
        "groupID" => $MainGPID,
        "gpid"    => $newgpid,
    ]);
    if(!$okLnk){
        writelogs("ERROR creating gpslink: $lnkErr",__FUNCTION__,__FILE__,__LINE__);
        echo $tpl->post_error("API Error\n".$lnkErr);
        return false;
    }

    $grpMaster=acls_api_get("/proxy/acls/sqlite/group/$MainGPID");
    $ManGropName=is_array($grpMaster) ? $grpMaster["groupName"] : $MainGPID;

    admin_tracks("Insert a new object $GroupName/$GroupType to $ManGropName");
    return true;

}

function table_start(){
    $page=CurrentPageName();
    $t=time();
    $suffix=build_suffix();
    echo "<div id='table-$t'></div>\n";
    echo "<script>function myRefresh$t(){\n";
    echo "LoadAjax('table-$t','$page?table=yes&myRefresh=myRefresh$t&$suffix');\n";
    echo "}\n";
    echo "myRefresh$t();\n";
    echo "</script>\n";
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $qProxy=new mysql_squid_builder(true);
    $groupid=intval($_GET["gpid"]);
    unset($_GET["gpid"]);
    $suffix=build_suffix();
    $t=time();

    // Fetch all gpslinks for this group via API (schema managed by Go daemon at startup)
    $links=acls_api_get("/proxy/acls/sqlite/gpslinks?groupid=$groupid");

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-item-js=$groupid&$suffix');\">";
    $html[]="<i class='fa fa-plus'></i> {new_object} </label>";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?link-item-js=$groupid&$suffix');\"><i class='fa fa-link'></i> {link_object} </label>";
    $html[]="</div>";
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'>";
    $html[]="<thead>";
    // Un <tr> etait ouvert DEUX FOIS ici aussi.
    $html[]="<tr>";
    $html[]="<th>{objects}</th>";
    $html[]="<th class='awc-nowrap'>{type}</th>";
    $html[]="<th class='awc-nowrap'>{enable}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='ritobj2-rows'>";

    $TRCLASS=null;
    $addons=build_suffix();

    if(is_array($links)){
        // Un seul appel pour toute la table, et aucun quand elle est vide.
        $ITEMCOUNT=ritobj_items_count_all();
        foreach ($links as $lnk) {
            $TRCLASS=null;
            $md = $lnk["zmd5"];
            $enabled = intval($lnk["enabled"]);
            $gpid=intval($lnk["gpid"]);

            // Fetch child group details
            $grp=acls_api_get("/proxy/acls/sqlite/group/$gpid");
            $GroupType=is_array($grp) ? $grp["groupType"] : "";
            $GroupName=is_array($grp) ? $grp["groupName"] : $gpid;

            $enable=$tpl->icon_check($enabled,"Loadjs('$page?item-enable=$md')",null,"AsDansGuardianAdministrator");
            $GroupTypeText=$qProxy->GroupTypeToString($GroupType);
            $delete=$tpl->icon_delete("Loadjs('$page?item-delete=$md')","AsDansGuardianAdministrator");
            $GroupNameLink=$tpl->td_href($GroupName,null,"Loadjs('fw.rules.items.php?groupid=$gpid&$addons')");
            // La cle peut manquer pour un type inconnu du referentiel.
            $ico2=$qProxy->acl_GroupTypeIcon[$GroupType] ?? "fas fa-cube";

            $html[]="<tr id='$md'>";
            $html[]="<td><div class='awc-ident'>"
                   ."<span class='awc-ico".($enabled==0?" awc-ico-off":"")."'><i class='$ico2'></i></span>"
                   ."<span><span class='awc-name".($enabled==0?" awc-name-off":"")."'>{$GroupNameLink}</span>"
                   ."<div class='awc-meta'>".ritobj_meta($gpid,$ITEMCOUNT)."</div></span>"
                   ."</div></td>";
            $html[]="<td class='awc-nowrap awc-mono'>$GroupTypeText</td>";
            $html[]="<td class='awc-nowrap'>$enable</td>";
            $html[]="<td><div class='awc-acts'>$delete</div></td>";
            $html[]="</tr>";
        }
    }

    if(!is_array($links) || count($links)==0){
        $html[]="<tr><td colspan='4' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='ritobj2-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse (accolades JavaScript).
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"ritobj2-rows","ritobj2-pager");
}


