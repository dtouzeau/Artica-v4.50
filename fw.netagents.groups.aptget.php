<?php
/**
 * Netagent Group APT Upgradable Packages
 * Merges upgradable packages from all agents in a group into a single view.
 * APIs: GET  /netagents/groups/{gpid}
 *       GET  /netagents/packages/list/group/{gpid}
 *       POST /netagents/packages/fetch/group/{gpid}
 *       POST /netagents/packages/upgrade/group/{gpid}/{name}
 *       POST /netagents/packages/upgrade/group/all/{gpid}
 *       POST /netagents/packages/apt-update/group/{gpid}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . '/ressources/class.netagent.artica.inc';

if(!isset($GLOBALS["CLASS_SOCKETS"])){
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["upgrade-single-js"])){upgrade_single_js();exit;}
if(isset($_POST["upgrade-single"])){upgrade_single();exit;}
if(isset($_GET["upgrade-all-js"])){upgrade_all_js();exit;}
if(isset($_POST["upgrade-all"])){upgrade_all();exit;}
if(isset($_GET["dist-upgrade-js"])){dist_upgrade_js();exit;}
if(isset($_POST["dist-upgrade"])){dist_upgrade();exit;}
if(isset($_GET["refresh-js"])){refresh_js();exit;}
if(isset($_POST["refresh-fetch"])){refresh_fetch();exit;}
if(isset($_GET["apt-update-js"])){apt_update_js();exit;}
if(isset($_POST["apt-update"])){apt_update();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
js();

function gpid():int{
    return intval($_GET["gpid"] ?? $_POST["gpid"] ?? 0);
}

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    $gpid=gpid();

    $grp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/groups/$gpid"));
    $groupName=is_object($grp)?htmlspecialchars($grp->name??"Group #$gpid"):"Group #$gpid";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/list/group/$gpid"));
    if(isset($json->Status)&&!$json->Status){
        $err=$json->Error??"Unknown error";
        return $tpl->js_error("{error} $err");
    }

    $totalUpgradable=0;
    foreach(($json->agents??[]) as $agent){
        $totalUpgradable+=intval($agent->upgradable_count??0);
    }

    return $tpl->js_dialog4("{upgradable_packages} $totalUpgradable - $groupName","$page?table-start=yes&gpid=$gpid",950);
}

function table_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=gpid();
    $html=[];
    $html[]="<div style='margin-bottom:5px;margin-top:5px' id='aptgetupgradesiglebuts'></div>";
    $html[]="</div>";
    $html[]=$tpl->search_block($page,null,null,"","&table=yes&gpid=$gpid");
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function top_buttons():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=intval($_GET["top-buttons"]);
    $function=$_GET["function"]??"";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/list/group/$gpid"));
    if(isset($json->Status)&&!$json->Status){ return false; }

    $totalUpgradable=0;
    $anyDistUpgradeRequired=false;
    foreach(($json->agents??[]) as $agent){
        $totalUpgradable+=intval($agent->upgradable_count??0);
        if(!empty($agent->dist_upgrade_required)) $anyDistUpgradeRequired=true;
    }

    $topbuttons=[];
    if($anyDistUpgradeRequired){
        $topbuttons[]=["Loadjs('$page?dist-upgrade-js=yes&gpid=$gpid&function=$function');","fas fa-level-up-alt","{dist_upgrade}"];
    }
    if($totalUpgradable>0){
        $topbuttons[]=["Loadjs('$page?upgrade-all-js=yes&gpid=$gpid&function=$function');","fas fa-arrow-circle-up","{upgrade_all}"];
    }
    $topbuttons[]=['$(".pkg-checkbox").prop("checked",true);',"fas fa-check-square","{select_all}"];
    $topbuttons[]=['$(".pkg-checkbox").prop("checked",false);',"fas fa-square","{unselect_all}"];
    $topbuttons[]=["Loadjs('$page?apt-update-js=yes&gpid=$gpid&function=$function');","fas fa-sync-alt","{apt-get-update}"];
    $topbuttons[]=["Loadjs('$page?refresh-js=yes&gpid=$gpid&function=$function');",ico_refresh,"{refresh}"];
    echo $tpl->_ENGINE_parse_body($tpl->table_buttons($topbuttons));
    return true;
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=gpid();
    $search=trim($_GET["search"]??"");
    $function=$_GET["function"]??"";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/list/group/$gpid"));
    if(isset($json->Status)&&!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning($json->Error??"{error}"));
        return false;
    }

    $agents=$json->agents??[];
    if(empty($agents)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        echo "<script>NoSpinner();</script>";
        return true;
    }

    // Merge packages from all agents, keyed by package name
    $errors=[];
    $merged=[];
    foreach($agents as $agent){
        $aid=intval($agent->agent_id);
        $hostname=htmlspecialchars($agent->hostname??"Agent #$aid");
        if(!empty($agent->fetch_error)){
            $errors[]="$hostname: ".htmlspecialchars($agent->fetch_error);
        }
        foreach(($agent->packages??[]) as $pkg){
            $name=$pkg->name??"";
            if($name==="") continue;
            if(!isset($merged[$name])){
                $merged[$name]=["name"=>$name,"new_version"=>$pkg->new_version??"","agents"=>[]];
            }
            $merged[$name]["agents"][]=[
                "id"=>$aid,
                "hostname"=>$hostname,
                "version"=>$pkg->version??""
            ];
        }
    }

    // Sort: most agents affected first, then alphabetically
    uasort($merged,fn($a,$b)=>count($b["agents"])-count($a["agents"])?:strcmp($a["name"],$b["name"]));

    $html=[];
    if(!empty($errors)){
        $html[]=$tpl->div_warning("<i class='fas fa-exclamation-triangle'></i> ".implode(" &bull; ",$errors));
    }
    $html[]="<div id='pkg-result-$gpid'></div>";

    if(empty($merged)){
        $html[]=$tpl->div_success("<i class='fas fa-check-circle'></i> {system_is_uptodate}");
        $html[]="<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$html));
        return true;
    }

    $t=time();
    $html[]="<table id='table-pkg-$gpid-$t' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{package_name}</th>";
    $html[]="<th data-sortable=true data-type='numeric'>{members}</th>";
    $html[]="<th data-sortable=true data-type='text'>{version}</th>";
    $html[]="<th data-sortable=true data-type='text'>{new_version}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($merged as $item){
        $name=$item["name"];
        $newVersion=$item["new_version"];
        $agentList=$item["agents"];

        if($search!==""){
            $sl=strtolower($search);
            $found=stripos($name,$sl)!==false||stripos($newVersion,$sl)!==false;
            if(!$found){
                foreach($agentList as $a){
                    if(stripos($a["hostname"],$sl)!==false){ $found=true; break; }
                }
            }
            if(!$found) continue;
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $nameEnc=urlencode($name);
        $upgradeJs="Loadjs('$page?upgrade-single-js=yes&gpid=$gpid&name=$nameEnc')";
        $btn=$tpl->button_inline("{upgrade}",$upgradeJs,"fas fa-arrow-circle-up","AsArticaMetaAdmin",0,"btn-primary","small");

        // Agent count badge with tooltip listing hostnames + current version
        $agentCount=count($agentList);
        $agentTooltip=htmlspecialchars(implode(", ",array_map(fn($a)=>$a["hostname"]." (".$a["version"].")",$agentList)));
        $agentBadge="<span class='badge' style='background:#1c84c6;color:#fff' title='$agentTooltip'>$agentCount</span>";

        // Deduplicate versions (agents may have differing installed versions)
        $versions=array_unique(array_map(fn($a)=>$a["version"],$agentList));
        $versionCell=htmlspecialchars(implode(", ",$versions));

        if($newVersion!==""&&!in_array($newVersion,$versions)){
            $newVersionCell="<span style='color:#1ab394;font-weight:bold'>".htmlspecialchars($newVersion)."</span>";
        }else{
            $newVersionCell=htmlspecialchars($newVersion)?:"-";
        }

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%;text-align:center'><input type='checkbox' class='pkg-checkbox' value='".htmlspecialchars($name)."'></td>";
        $html[]="<td style='width:99%'><strong>".htmlspecialchars($name)."</strong></td>";
        $html[]="<td style='width:1%;text-align:center'>$agentBadge</td>";
        $html[]="<td style='width:1%' nowrap>$versionCell</td>";
        $html[]="<td style='width:1%' nowrap>$newVersionCell</td>";
        $html[]="<td style='width:1%' nowrap>$btn</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('aptgetupgradesiglebuts','$page?top-buttons=$gpid&function=$function');";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-pkg-$gpid-$t').footable({";
    $html[]="  'filtering':{'enabled':false},";
    $html[]="  'sorting':{'enabled':true},";
    $html[]="  'paging':{'size':{$GLOBALS['FOOTABLE_PSIZE']}}";
    $html[]="});});";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

// ==================== Dist-Upgrade (group-wide) ====================

function dist_upgrade_js():void{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=gpid();
    $function=$_GET["function"]??"";
    $title=$tpl->javascript_parse_text("{dist_upgrade}");
    $confirm=$tpl->javascript_parse_text("{confirm_dist_upgrade}");

    $js=[];
    $js[]="swal({";
    $js[]="    title:'$title',";
    $js[]="    text:'$confirm',";
    $js[]="    type:'warning',";
    $js[]="    showCancelButton:true,";
    $js[]="    confirmButtonColor:'#ed5565',";
    $js[]="    confirmButtonText:'$title'";
    $js[]="},function(isConfirm){";
    $js[]="    if(!isConfirm)return;";
    $js[]="    \$('#pkg-result-$gpid').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {dist_upgrade}...</div>');";
    $js[]="    \$.post('$page',{";
    $js[]="        'dist-upgrade':'yes',";
    $js[]="        'gpid':'$gpid'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
}

function dist_upgrade():void{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $gpid=gpid();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/dist-upgrade/group/$gpid",[]));

    if(isset($json->Status)&&!$json->Status){
        $err=addslashes($json->Error??"{error}");
        echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $err</div>';";
        return;
    }
    $triggered=intval($json->triggered??0);
    $msg=isset($json->message)?addslashes($json->message):"{success}";
    echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $msg</div>';";
    admin_tracks("Meta: group $gpid dist-upgrade triggered on $triggered agents");
}

// ==================== Upgrade Single Package (group-wide) ====================

function upgrade_single_js():void{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=gpid();
    $name=$_GET["name"]??"";
    $nameSafe=addslashes(htmlspecialchars($name));
    $upgrade_text=$tpl->javascript_parse_text("{upgrade}");

    $js=[];
    $js[]="swal({";
    $js[]="    title:'$upgrade_text',";
    $js[]="    text:'$nameSafe',";
    $js[]="    type:'warning',";
    $js[]="    showCancelButton:true,";
    $js[]="    confirmButtonColor:'#1ab394',";
    $js[]="    confirmButtonText:'$upgrade_text'";
    $js[]="},function(isConfirm){";
    $js[]="    if(!isConfirm)return;";
    $js[]="    \$('#pkg-result-$gpid').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {upgrading} $nameSafe...</div>');";
    $js[]="    \$.post('$page',{";
    $js[]="        'upgrade-single':'yes',";
    $js[]="        'gpid':'$gpid',";
    $js[]="        'name':'".addslashes($name)."'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
}

function upgrade_single():void{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $gpid=gpid();
    $name=trim($_POST["name"]??"");
    $nameEnc=urlencode($name);
    $nameSafe=addslashes(htmlspecialchars($name));

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/upgrade/group/$gpid/$nameEnc",[]));

    if(isset($json->Status)&&!$json->Status){
        $err=addslashes($json->Error??"{error}");
        echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $nameSafe: $err</div>';";
        return;
    }
    $msg=addslashes($json->message??"{success}");
    $agents=intval($json->total_agents??0);
    echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $nameSafe: $msg</div>';";
    admin_tracks("Meta: group $gpid upgraded package $name on $agents agents");
}

// ==================== Upgrade All Packages (group-wide) ====================

function upgrade_all_js():void{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=gpid();
    $upgrade_all_text=$tpl->javascript_parse_text("{upgrade_all}");
    $confirm_text=$tpl->javascript_parse_text("{confirm_upgrade_all_packages}");

    $js=[];
    $js[]="swal({";
    $js[]="    title:'$upgrade_all_text',";
    $js[]="    text:'$confirm_text',";
    $js[]="    type:'warning',";
    $js[]="    showCancelButton:true,";
    $js[]="    confirmButtonColor:'#ed5565',";
    $js[]="    confirmButtonText:'$upgrade_all_text'";
    $js[]="},function(isConfirm){";
    $js[]="    if(!isConfirm)return;";
    $js[]="    \$('#pkg-result-$gpid').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {upgrading}...</div>');";
    $js[]="    \$.post('$page',{";
    $js[]="        'upgrade-all':'yes',";
    $js[]="        'gpid':'$gpid'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
}

function upgrade_all():void{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $gpid=gpid();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/upgrade/group/all/$gpid",[]));

    if(isset($json->Status)&&!$json->Status){
        $err=addslashes($json->Error??"{error}");
        echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $err</div>';";
        return;
    }
    $msg=addslashes($json->message??"{success}");
    $agents=intval($json->total_agents??0);
    echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $msg</div>';";
    admin_tracks("Meta: group $gpid upgrade-all triggered on $agents agents");
}

// ==================== Refresh Packages Data ====================

function refresh_js():void{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=gpid();
    $function=$_GET["function"]??"";

    $js=[];
    $js[]="  \$('#pkg-result-$gpid').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {refreshing}...</div>');";
    $js[]="  \$.post('$page',{'refresh-fetch':'yes','gpid':'$gpid'},function(){";
    $js[]="      setTimeout(function(){";
    $js[]="          \$('#pkg-result-$gpid').html('');";
    $js[]="          $function()";
    $js[]="      },3000);";
    $js[]="  });";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
}

function refresh_fetch():void{
    $gpid=gpid();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/fetch/group/$gpid",[]);
}

// ==================== APT-get update (group-wide) ====================

function apt_update_js():void{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=gpid();
    $title=addslashes($tpl->javascript_parse_text("apt-get update"));
    $confirm=addslashes($tpl->javascript_parse_text("{confirm}"));
    $aptgetupdate=$tpl->javascript_parse_text("{apt-get-update}");
    $js=[];
    $js[]="swal({";
    $js[]="    title:'$title',";
    $js[]="    text:'$confirm',";
    $js[]="    type:'info',";
    $js[]="    showCancelButton:true,";
    $js[]="    confirmButtonColor:'#1c84c6',";
    $js[]="    confirmButtonText:'$title'";
    $js[]="},function(isConfirm){";
    $js[]="    if(!isConfirm)return;";
    $js[]="    \$('#pkg-result-$gpid').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> $aptgetupdate...</div>');";
    $js[]="    \$.post('$page',{";
    $js[]="        'apt-update':'yes',";
    $js[]="        'gpid':'$gpid'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
}

function apt_update():void{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $gpid=gpid();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/apt-update/group/$gpid",[]));

    if(isset($json->Status)&&!$json->Status){
        $err=addslashes($json->Error??"{error}");
        echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $err</div>';";
        return;
    }
    $msg=addslashes($json->message??"{success}");
    echo "document.getElementById('pkg-result-$gpid').innerHTML='<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $msg</div>';";
    admin_tracks("Meta: group $gpid apt-get update triggered");
}
