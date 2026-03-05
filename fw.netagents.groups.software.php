<?php


include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");

if (isset($_GET["table-start"])) {table_start();exit;}
if (isset($_GET["table-search"])) {table_search();exit;}
if(isset($_GET["key-search"])){key_search_js();exit;}
if(isset($_GET["key-search-table"])){key_search_table();exit;}
if (isset($_GET["push-js"])) {
    push_js();
    exit;
}
if (isset($_POST["push-install"])) {
    push_install();
    exit;
}

js();

function key_search_js(): bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users = new usersMenus();
    if (!$users->AsArticaMetaAdmin) {
        return $tpl->js_error("{no_privileges}");
    }
    $id = intval($_GET["id"]);
    $soft=$_GET["key-search"];
    $debver=$_GET["deb"];
    $NetAgent=new ArticaNetAgents();
    $NetAgent->SetGroup($id);
    $data=$NetAgent->GroupInfo();
    return $tpl->js_dialog4("{{$soft}} - {$data["name"]}", "$page?key-search-table=$soft&id=$id&deb=$debver", 650);
}
function js(): bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users = new usersMenus();
    if (!$users->AsArticaMetaAdmin) {
        return $tpl->js_error("{no_privileges}");
    }
    $id = intval($_GET["id"]);
    $NetAgent=new ArticaNetAgents();
    $NetAgent->SetGroup($id);
    $data=$NetAgent->GroupInfo();
    return $tpl->js_dialog2("{update_software_packages} - {$data["name"]}", "$page?table-start=yes&id=$id", 650);
}
function table_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["id"];
    $html=array();
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->search_block($page,null,null,"","&table-search=yes&id=$id");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}
function table_search():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users = new usersMenus();
    $NetAgent=new ArticaNetAgents();
    $id=$_GET["id"];
    $NetAgent->SetGroup($id);
    $data=$NetAgent->GroupInfo();
    $debians=array();
    foreach ($data["agents"] as $agents) {
        $debian_version[$agents["debian_version"]]=true;
    }
    foreach ($debian_version as $key => $value) {
        $debians[]=$key;
    }
    $debians_text=implode(",",$debians);
    $results=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/softwares-search/$debians_text"),true);

    if(!isset($results["keys"])){
        $results["keys"]=array();
    }

    $html[]="<table id='table-softs-$id' class='table table-stripped' data-page-size='100' data-paging='true'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true data-type='text'>{software}</th>";
    $html[]="<th data-sortable=true data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $search=$_GET["search"];
    $TRCLASS="";
    $ico=ico_cd;
    foreach ($results["keys"] as $mainkey) {

        $softname=$tpl->_ENGINE_parse_body("{{$mainkey}}");
        if(strlen($search)>0){
            if(!preg_match("#$search#i",$softname)){
                continue;
            }
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $softname=$tpl->td_href($softname,"","Loadjs('$page?key-search=$mainkey&id=$id&deb=$debians_text')");
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%'><i class='$ico'></i></td>";
        $html[]="<td>$softname</td>";

        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='2'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}
function key_search_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    $soft=$_GET["key-search-table"];
    $debver=$_GET["deb"];
    $search=trim($_GET["search"] ?? "");


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/softwares/$debver/$soft"));
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning($json->Error ?? "{error}"));
        return false;
    }

    // Handle both single-key (files array) and multi-key (softwares object) responses

    $html=array();
    $html[]="<table class='table table-stripped' data-page-size='100' data-paging='true'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text' colspan='2'>{version}</th>";
    $html[]="<th data-sortable=true data-type='text'>{filename}</th>";
    $html[]="<th data-sortable=true data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true data-type='text'>{date}</th>";
    $html[]="<th data-sortable=false style='width:1%' nowrap>{install}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    $ico=ico_file_zip;
    foreach($json->files as $ligne){
        $name=$ligne->name ?? "";
        $version=$ligne->version ?? "";
        $filename=$ligne->filename ?? "";
        $localPath=$ligne->local_path ?? "";
        $size=$ligne->size ?? 0;
        $syncedAt=$ligne->synced_at ?? "";
        $debian_version=$ligne->debian_version ?? 0;
        if($search != null){
            $searchLower=strtolower($search);
            if(stripos($version,$searchLower)===false && stripos($filename,$searchLower)===false && stripos($name,$searchLower)===false){
                continue;
            }
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $sizeHuman=FormatBytes($size/1024);
        $dateFormatted="";
        if($syncedAt!=""){
            $ts=strtotime($syncedAt);
            if($ts>0){$dateFormatted=$tpl->time_to_date($ts,true);}
        }

        $fEnc=urlencode($filename);
        $lpEnc=urlencode($localPath);
        $pushJs="Loadjs('$page?push-js=yes&id=$id&soft=$soft&debver=$debian_version&f=$fEnc&lp=$lpEnc')";
        $btn=$tpl->button_inline("{upgrade}",$pushJs,ico_cd,"AsArticaMetaAdmin",0,"btn-primary");

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%' class='center' nowrap><i class='$ico'></i></td>";
        $html[]="<td><strong>$version</strong></td>";
        $html[]="<td>".htmlspecialchars($filename)."</td>";
        $html[]="<td style='width:1%' nowrap>$sizeHuman</td>";
        $html[]="<td style='width:1%' nowrap>$dateFormatted</td>";
        $html[]="<td style='width:1%' nowrap>$btn</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}
function push_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $f=$_GET["f"];
    $lp=$_GET["lp"];
    $soft=$_GET["soft"];
    $debver=$_GET["debver"];
    $fSafe=addslashes(htmlspecialchars($f));
    $upgrade_text=$tpl->javascript_parse_text("{upgrade}");
    $js=[];
    $js[]="swal({";
    $js[]="    title:'$upgrade_text',";
    $js[]="    text:'$fSafe',";
    $js[]="    type:'warning',";
    $js[]="    showCancelButton:true,";
    $js[]="    confirmButtonColor:'#1ab394',";
    $js[]="    confirmButtonText:'$upgrade_text'";
    $js[]="},function(isConfirm){";
    $js[]="    if(!isConfirm)return;";
    $js[]="    \$.post('$page',{";
    $js[]="        'push-install':'yes',";
    $js[]="        'id':'$id',";
    $js[]="        'f':'".addslashes($f)."',";
    $js[]="        'lp':'".addslashes($lp)."',";
    $js[]="        'soft':'".addslashes($soft)."',";
    $js[]="        'debver':'$debver'";
    $js[]="    },function(data){eval(data);});";
    $js[]="});";
    echo implode("\n",$js);
}
function push_install(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_POST["id"]);
    $f=$_POST["f"];
    $lp=$_POST["lp"];
    $soft=$_POST["soft"];
    $debver=$_POST["debver"];

    $NetAgent=new ArticaNetAgents();
    $NetAgent->SetGroup($id);
    $data=$NetAgent->GroupInfo();
    $ids=array();
    foreach ($data["agents"] as $agent){
        $ids[]=$agent["id"];
    }


    $postData=array(
        "agent_ids"=>implode(",",$ids),
        "filename"=>basename($f),
        "type"=>"software",
        "key"=>$soft,
        "debver"=>$debver,
        "sourcefile"=>$lp
    );
    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/articarepos/push",$postData));
    if(isset($result->Error)){
        echo $tpl->javascript_parse_text($result->Error);
        return;
    }
    echo $tpl->javascript_parse_text("{success}");
    admin_tracks("Meta: pushed software $f to agent $id");
}