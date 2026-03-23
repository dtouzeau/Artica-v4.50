<?php
/**
 * Netagent APT Upgradable Packages
 * Lists packages available for upgrade on a remote agent and allows upgrading individual or all packages.
 * APIs: GET /netagents/packages/list/{id}, POST /netagents/packages/upgrade/{id}/{name},
 *       POST /netagents/packages/upgrade/all/{id}, POST /netagents/packages/fetch/{id}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . '/ressources/class.netagent.artica.inc';

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
if(isset($_GET["top-buttons"])){top_buttons();exit;}
js();

function js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    $id=intval($_GET["id"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/list/$id"));

    // Error from API
    if(isset($json->Status) && !$json->Status){
        $err=$json->Error ?? "Unknown error";
        return $tpl->js_error("{error} $err");
    }

    $net=new ArticaNetAgents($id);
    $AgentName=$net->GetAgentHostname();

    $upgradableCount=intval($json->upgradable_count ?? 0);
    $lastFetch=$json->last_fetch ?? "";
    $fetchError=$json->fetch_error ?? "";

    // Summary bar
    $html=array();
    if($fetchError!=""){
        $html[]=$tpl->div_warning("<i class='fas fa-exclamation-triangle'></i> {last_error}: " . htmlspecialchars($fetchError));
    }

    $lastFetchDate="";
    if($lastFetch!=""){
        $ts=strtotime($lastFetch);
        if($ts>0){$lastFetchDate=$tpl->time_to_date($ts,true);}
    }
    return $tpl->js_dialog4("{upgradable_packages} $upgradableCount $lastFetchDate - $AgentName", "$page?table-start=yes&id=$id", 900);
}

function table_start():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $id=intval($_GET["id"]);

    $html=array();
    $html[]="<div style='margin-bottom:5px;margin-top:5px' id='aptgetupgradesiglebuts'></div>";
    $html[]="</div>";
    $html[]=$tpl->search_block($page,null,null,"","&table=yes&id=$id");
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function top_buttons():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["top-buttons"]);
    $function=$_GET["function"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/list/$id"));
    if(isset($json->Status) && !$json->Status){
        return false;
    }

    $upgradableCount=intval($json->upgradable_count ?? 0);
    $distUpgradeRequired=(bool)($json->dist_upgrade_required ?? false);
    $aptRunning=(bool)($json->apt_running ?? false);

    $topbuttons=[];
    if( !$aptRunning){
        $topbuttons[]=["Loadjs('$page?dist-upgrade-js=yes&id=$id&function=$function');",
            "fas fa-level-up-alt","{dist_upgrade}"];
    }
    if($upgradableCount>0){
        if( !$aptRunning) {
            $topbuttons[] = ["Loadjs('$page?upgrade-all-js=yes&id=$id&function=$function');", "fas fa-arrow-circle-up", "{upgrade_all}"];
        }
    }

    $topbuttons[]=["Loadjs('fw.netagents.aptget.reports.php?id=$id')",ico_list,"{reports}"];

    if(!empty($topbuttons)){
        $topbuttons[]=["Loadjs('$page?refresh-js=yes&id=$id&function=$function');",ico_refresh,"{refresh}"];
        echo $tpl->_ENGINE_parse_body($tpl->table_buttons($topbuttons));
    }

    return true;
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    $search=trim($_GET["search"] ?? "");
    $function=$_GET["function"] ?? "";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/list/$id"));

    // Error from API
    if(isset($json->Status) && !$json->Status){
        $err=$json->Error ?? "{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("ERR.".__LINE__."||".$err));
        return false;
    }

    // No data available yet
    if(isset($json->message) && !isset($json->packages)){
        $html=array();
        $html[]=$tpl->div_info("{no_data}");
        $html[]="<div style='text-align:right;margin-top:10px'>";
        $html[]="<button class='btn btn-primary' onclick=\"Loadjs('$page?refresh-js=yes&id=$id&function=$function');\">";
        $html[]="<i class='fas fa-sync-alt'></i> {refresh}";
        $html[]="</button>";
        $html[]="</div>";
        $html[]="<div id='pkg-result-$id'></div>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$html));
        return true;
    }

    $packages=$json->packages ?? [];
    $upgradableCount=intval($json->upgradable_count ?? 0);
    $hostname=$json->hostname ?? "";
    $lastFetch=$json->last_fetch ?? "";
    $fetchError=$json->fetch_error ?? "";

    // Summary bar
    $html=array();
    $lastFetchDate="";
    if($lastFetch!=""){
        $ts=strtotime($lastFetch);
        if($ts>0){$lastFetchDate=$tpl->time_to_date($ts,true);}
    }
    if($fetchError!=""){
        if(!preg_match("#connection refused#i",$fetchError)){
        $html[]=$tpl->div_warning("{last_error} ($lastFetchDate)||<i class='fas fa-exclamation-triangle'></i>" .
            htmlspecialchars($fetchError));
        }
    }






    // Result div for upgrade feedback
    $html[]="<div id='pkg-result-$id'></div>";

    if(!is_array($packages) || count($packages)==0){
        $html[]=$tpl->div_success("<i class='fas fa-check-circle'></i> {system_is_uptodate} ($upgradableCount)");
        $html[]="<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$html));
        return true;
    }

    // Packages table
    $html[]="<table id='table-pkg-$id' class='footable table table-stripped' data-page-size='100'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{package_name}</th>";
    $html[]="<th data-sortable=true data-type='text'>{version}</th>";
    $html[]="<th data-sortable=true data-type='text'>{new_version}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $count=0;
    foreach($packages as $pkg){
        $name=$pkg->name ?? "";
        $version=$pkg->version ?? "";
        $newVersion=$pkg->new_version ?? "";
        $arch=$pkg->architecture ?? "";
        $desc=$pkg->description ?? "";

        if($search!=""){
            $sl=strtolower($search);
            if(stripos($name,$sl)===false && stripos($desc,$sl)===false && stripos($version,$sl)===false && stripos($newVersion,$sl)===false){
                continue;
            }
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $nameEnc=urlencode($name);
        $upgradeJs="Loadjs('$page?upgrade-single-js=yes&id=$id&name=$nameEnc')";
        $btn=$tpl->button_inline("{upgrade}",$upgradeJs,
            "fas fa-arrow-circle-up",
            "AsArticaMetaAdmin",0,
            "btn-primary","small");

        $versionCell=htmlspecialchars($version);
        if($newVersion!="" && $newVersion!=$version){
            $newVersionCell="<span style='color:#1ab394;font-weight:bold'>".htmlspecialchars($newVersion)."</span>";
        }else{
            $newVersionCell="-";
        }

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:99%' ><strong>".htmlspecialchars($name)."</strong></td>";
        $html[]="<td style='width:1%' nowrap>$versionCell</td>";
        $html[]="<td style='width:1%' nowrap>$newVersionCell</td>";
        $html[]="<td style='width:1%' nowrap>$btn</td>";
        $html[]="</tr>";
        $count++;
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='5'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="LoadAjax('aptgetupgradesiglebuts','$page?top-buttons=$id&function=$function');";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('#table-pkg-$id').footable({";
    $html[]="  \"filtering\": { \"enabled\": false },";
    $html[]="  \"sorting\": { \"enabled\": true },";
    $html[]="  \"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} }";
    $html[]="});});";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

// ==================== Dist-Upgrade (held-back packages) ====================

function dist_upgrade_js():void{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);

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
    $js[]="    \$('#pkg-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {dist_upgrade}...</div>');";
    $js[]="    \$.post('$page',{";
    $js[]="        'dist-upgrade':'yes',";
    $js[]="        'id':'$id'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
}

function dist_upgrade():void{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_POST["id"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/dist-upgrade/$id",[]));

    if(isset($json->Status)&&!$json->Status){
        $err=addslashes($json->Error??"{error}");
        echo "document.getElementById('pkg-result-$id').innerHTML='<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $err</div>';";
        return;
    }
    $msg=isset($json->message)?addslashes($json->message):"{success}";
    echo "document.getElementById('pkg-result-$id').innerHTML='<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $msg</div>';";
    admin_tracks("Meta: dist-upgrade triggered on agent $id");
}

// ==================== Upgrade Single Package ====================

function upgrade_single_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $name=$_GET["name"];
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
    $js[]="    $('#pkg-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {upgrading} $nameSafe...</div>');";
    $js[]="    \$.post('$page',{";
    $js[]="        'upgrade-single':'yes',";
    $js[]="        'id':'$id',";
    $js[]="        'name':'".addslashes($name)."'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
}

function upgrade_single(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_POST["id"]);
    $name=trim($_POST["name"]);
    $nameEnc=urlencode($name);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/upgrade/$id/$nameEnc",array()));

    $nameSafe=addslashes(htmlspecialchars($name));
    if(isset($json->Status) && !$json->Status){
        $err=addslashes($json->Error ?? "{error}");
        echo "document.getElementById('pkg-result-$id').innerHTML='<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $nameSafe: $err</div>';";
        return;
    }

    $msg=isset($json->message) ? addslashes($json->message) : "OK";
    echo "document.getElementById('pkg-result-$id').innerHTML='<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $nameSafe: $msg</div>';";
    admin_tracks("Meta: upgraded package $name on agent $id");
}

// ==================== Upgrade All Packages ====================

function upgrade_all_js():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
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
    $js[]="    $('#pkg-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {upgrading}...</div>');";
    $js[]="    \$.post('$page',{";
    $js[]="        'upgrade-all':'yes',";
    $js[]="        'id':'$id'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
    return true;
}

function upgrade_all():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_POST["id"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/upgrade/all/$id",array()));

    if(isset($json->Status) && !$json->Status){
        $err=addslashes($json->Error ?? "{error}");
        echo "document.getElementById('pkg-result-$id').innerHTML='<div class=\"alert alert-danger\"><i class=\"fas fa-times-circle\"></i> $err</div>';";
        return true;
    }
    $msg=isset($json->message) ? addslashes($json->message) : "{success}";
    echo "document.getElementById('pkg-result-$id').innerHTML='<div class=\"alert alert-success\"><i class=\"fas fa-check-circle\"></i> $msg</div>';";
    return admin_tracks("Meta: upgrade all packages on agent $id");
}

// ==================== Refresh Packages Data ====================

function refresh_js():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $function=$_GET["function"];

    $js=[];
    $js[]="$('#pkg-result-$id').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {refreshing}...</div>');";
    $js[]="$.post('$page',{'refresh-fetch':'yes','id':'$id'},function(){";
    $js[]="    setTimeout(function(){";
    $js[]="        $('#pkg-result-$id').html('');";
    $js[]="        $function()";
    $js[]="    },3000);";
    $js[]="});";
    echo $tpl->_ENGINE_parse_body(implode("\n",$js));
    return true;
}

function refresh_fetch():bool{
    $id=intval($_POST["id"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/fetch/$id",array());
    return true;
}
