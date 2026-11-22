<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
//Artica Service Pack 8 - Sep 2026
// fw.nginx.docker.php — Catalogue des images Docker PHP (Reverse Dockers).
// CRUD + installation (pull) via /reverse-dockers/images* sur /run/reverse-proxy.sock.
//
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
$users=new usersMenus();
if(!$users->AsWebMaster){exit();}

if(isset($_GET["build-page"]))   {docker_tabs();      exit;}
if(isset($_GET["tab-images"]))   {docker_images();    exit;}
if(isset($_GET["tab-events"]))   {docker_events();    exit;}
if(isset($_GET["search"]))       {images_search();    exit;}
if(isset($_GET["image-js"]))     {image_js();         exit;}
if(isset($_GET["image-popup"]))  {image_popup();      exit;}
if(isset($_POST["image-save"]))  {image_save();       exit;}
if(isset($_GET["delete-js"]))    {image_delete_js();  exit;}
if(isset($_POST["image-delete"])){image_delete();     exit;}
if(isset($_GET["pull-js"]))      {image_pull_js();    exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{application_images}",ico_cd,"{application_images_explain}",
        "$page?build-page=yes","reverse-docker","progress-reverse-docker",false,
        "table-reverse-docker");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{application_images}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function docker_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=array();
    $array["{images}"]="$page?tab-images=yes";
    $array["{events}"]="$page?tab-events=yes";
    // TINY apres les onglets (skill §3b) — les boutons sont emis par images_search().
    echo "<div style='margin-top:10px'>".$tpl->tabs_default($array)."</div>";
}

function docker_events(){
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($tpl->div_info("{events}||{no_data}"));
}

function docker_images(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $APP_DOCKER_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_INSTALLED"));
    if($APP_DOCKER_INSTALLED==0){
        $TINY=array();
        $TINY["TITLE"]="{application_images}";
        $TINY["ICO"]=ico_cd;
        $TINY["EXPL"]="{application_images_explain}";
        $TINY["URL"]="reverse-docker";
        $TINY["BUTTONS"]=$tpl->table_buttons(array());
        $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
        $jsinstall="Loadjs('fw.system.upgrade-software.php?product=APP_DOCKER&after-js=reload')";
        $button=$tpl->button_autnonome("{install} {APP_DOCKER}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
        echo $tpl->div_error("{APP_DOCKER}||{docker_not_installed}<div style='margin-right:20px;margin-top:20px;text-align:right'>$button</div>");
        echo "<script>$tiny</script>";
        return;
    }

    // Binaires presents ≠ daemon vivant : sans /var/run/docker.sock le pull
    // echoue avec IMAGE_PULL_FAILED/dial unix. Sonde le reverse-proxy.
    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/status"));
    $dockerOK=is_object($st) && !empty($st->Status) && !empty($st->Data->docker_available);
    if(!$dockerOK){
        $err="DOCKER_UNAVAILABLE";
        if(is_object($st) && isset($st->Data->docker_error) && strlen((string)$st->Data->docker_error)>0){
            $err=(string)$st->Data->docker_error;
        }
        echo $tpl->div_warning("{APP_DOCKER}||{daemon_not_running}<div style='margin-top:8px;font-size:12px'>".htmlspecialchars($err)."</div><div style='margin-top:8px'>/etc/init.d/docker start</div>");
    }

    echo "<div style='margin-top:10px'>".$tpl->search_block($page)."</div>";
}

function images_search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    // SECURITE: $function repart dans des OnClick — search_block ne genere que "ss<time>".
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $term=trim($tpl->CLEAN_BAD_XSS(isset($_GET["search"])?$_GET["search"]:""));

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/images");
    $json=json_decode($raw);
    if(!is_object($json) || (isset($json->Status) && !$json->Status)){
        $err=(is_object($json) && isset($json->Error))?$json->Error:"{error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }
    $records=array();
    if(isset($json->Data) && is_array($json->Data)){$records=$json->Data;}

    $html=array();
    $html[]="<div id='progress-reverse-docker-images'></div>";
    $html[]="<div class='awc-wrap'>";
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{image}</th>";
    $html[]="<th class='center'>{type}</th>";
    $html[]="<th class='center'>{php_version}</th>";
    $html[]="<th class='center'>{size}</th>";
    $html[]="<th class='center'>{enabled}</th>";
    $html[]="<th class='center'>{installed}</th>";
    $html[]="<th>{description}</th>";
    $html[]="<th class='center'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='dimg-rows'>";

    $n=0;
    foreach($records as $rec){
        if(!is_object($rec)){continue;}
        $name=(string)(isset($rec->name)?$rec->name:"");
        $tag=(string)(isset($rec->tag)?$rec->tag:"");
        $desc=(string)(isset($rec->description)?$rec->description:"");
        $appType=(string)(isset($rec->app_type)?$rec->app_type:"php");
        $ref=$name.($tag!==""?":".$tag:"");
        if(strlen($term)>0 && $term!="*"){
            if(stripos($ref,$term)===false && stripos($desc,$term)===false && stripos($appType,$term)===false){continue;}
        }
        $n++;
        $html[]=image_row($tpl,$page,$function,$rec,$ref,$desc);
    }
    if($n==0){
        $html[]="<tr><td colspan='8' class='awc-empty'>{no_data}</td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $n</span>"
           ."<span class='awc-pg' id='dimg-pager'></span></div>";
    $html[]="</div></div>";

    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?image-js=yes&id=0&function=$function')",ico_plus,"{new}");
    if(strlen($function)>2){
        $topbuttons[]=array("$function()",ico_refresh,"{refresh}");
    }
    $TINY=array();
    $TINY["TITLE"]="{application_images}";
    $TINY["ICO"]=ico_cd;
    $TINY["EXPL"]="{application_images_explain}";
    $TINY["URL"]="reverse-docker";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        ."<script>$tiny</script>"
        .awc_pager_js(100,"dimg-rows","dimg-pager");
}

function image_row($tpl,$page,$function,$rec,$ref,$desc){


    $ID=intval(isset($rec->id)?$rec->id:0);
    $php=(string)(isset($rec->php_version)?$rec->php_version:"");
    $enabled=intval(isset($rec->enabled)?$rec->enabled:0);
    $digest=(string)(isset($rec->digest)?$rec->digest:"");
    $lastPull=intval(isset($rec->last_pull)?$rec->last_pull:0);
    $lastError=(string)(isset($rec->last_error)?$rec->last_error:"");
    $sizeBytes=intval(isset($rec->size_bytes)?$rec->size_bytes:0);
    $appType=(string)(isset($rec->app_type)?$rec->app_type:"php");
    if($appType!=="wordpress"){$appType="php";}
    $md=md5("dimg-$ID");

    $link=$tpl->td_href(htmlspecialchars($ref),"{edit}",
        "Loadjs('$page?image-js=yes&id=$ID&function=$function')");
    $pull=$tpl->icon_down("Loadjs('$page?pull-js=$ID&function=$function')","AsWebMaster");
    $del=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md&function=$function')","AsWebMaster");

    $badge=$enabled==1
        ? "<span class='awc-badge awc-badge-on'>{enabled}</span>"
        : "<span class='awc-badge awc-badge-off'>{disabled}</span>";

    $typeBadge=$appType==="wordpress"
        ? "<span class='awc-badge awc-badge-on'>wordpress</span>"
        : "<span class='awc-badge'>php</span>";

    $installed="";
    if(strlen($digest)>0 || $lastPull>0){
        $installed="<span class='awc-badge awc-badge-on'>{yes}</span>";
    }elseif(strlen($lastError)>0){
        $installed="<span class='awc-badge awc-badge-off' title='".htmlspecialchars($lastError)."'>{error}</span>";
    }else{
        $installed="<span class='awc-badge awc-badge-off'>{no}</span>";
    }

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico".($enabled==1?"":" awc-ico-off")."'><i class='fab fa-docker'></i></span>";
    $h.="<span class='awc-name".($enabled==1?"":" awc-name-off")."'>$link</span>";
    $h.="</div></td>";
    $h.="<td class='center awc-nowrap'>$typeBadge</td>";
    $h.="<td class='center awc-nowrap awc-mono'>".htmlspecialchars($php)."</td>";
    $h.="<td class='center awc-nowrap awc-mono'>".htmlspecialchars(docker_image_size_label($sizeBytes))."</td>";
    $h.="<td class='center awc-nowrap'>$badge</td>";
    $h.="<td class='center awc-nowrap'>$installed</td>";
    $h.="<td>".htmlspecialchars($desc);
    if(strlen($lastError)>0){
        $h.="<div class='awc-muted' style='font-size:11px;color:#c0392b'>". $tpl->div_error($lastError)."</div>";
    }
    $h.="</td>";
    $h.="<td><div class='awc-acts'>$pull $del</div></td>";
    $h.="</tr>";
    return $h;
}

function docker_image_size_label($bytes){
    $bytes=floatval($bytes);
    if($bytes<=0){return "—";}
    $units=array("B","KB","MB","GB","TB");
    $i=0;
    while($bytes>=1024 && $i<count($units)-1){
        $bytes=$bytes/1024;
        $i++;
    }
    if($i==0){return intval($bytes)." ".$units[$i];}
    return number_format($bytes,1,".","")." ".$units[$i];
}

function image_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval(isset($_GET["id"])?$_GET["id"]:0);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $title=$id>0?"{edit}":"{new}";
    $tpl->js_dialog2($title,"$page?image-popup=yes&id=$id&function=$function",560);
}

function image_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval(isset($_GET["id"])?$_GET["id"]:0);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));

    $name="php";
    $tag="8.3-fpm";
    $php="8.3";
    $desc="";
    $enabled=1;
    $appType="php";
    if($id>0){
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/images/$id");
        $json=json_decode($raw);
        if(is_object($json) && !empty($json->Status) && isset($json->Data)){
            $d=$json->Data;
            $name=(string)(isset($d->name)?$d->name:"");
            $tag=(string)(isset($d->tag)?$d->tag:"");
            $php=(string)(isset($d->php_version)?$d->php_version:"");
            $desc=(string)(isset($d->description)?$d->description:"");
            $enabled=intval(isset($d->enabled)?$d->enabled:1);
            $appType=(string)(isset($d->app_type)?$d->app_type:"php");
            if($appType!=="wordpress"){$appType="php";}
        }
    }

    $form=array();
    $form[]=$tpl->field_hidden("image-save","yes");
    $form[]=$tpl->field_hidden("id",$id);
    $form[]=$tpl->field_text("name","{image}",$name,true,"php");
    $form[]=$tpl->field_text("tag","{tag}",$tag,true,"8.3-fpm");
    $form[]=$tpl->field_text("app_type","{type}",$appType,true,"php|wordpress");
    $form[]=$tpl->field_text("php_version","{php_version}",$php,false,"8.3");
    $form[]=$tpl->field_text("description","{description}",$desc,false);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled);

    $jsafter="dialogInstance2.close();";
    if(strlen($function)>2){$jsafter.="if(typeof $function==='function'){ $function(); }";}
    $bt=$id>0?"{apply}":"{add}";

    $html=array();
    $html[]=$tpl->div_explain("{application_images}||{application_images_explain}");
    $html[]=$tpl->form_outside("",$form,null,$bt,$jsafter,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
}

// Echo nothing on success (form_outside contract).
function image_save(){
    $tpl=new template_admin();
    $data=array(
        "name"=>trim(rawurldecode((string)(isset($_POST["name"])?$_POST["name"]:""))),
        "tag"=>trim(rawurldecode((string)(isset($_POST["tag"])?$_POST["tag"]:""))),
        "php_version"=>trim(rawurldecode((string)(isset($_POST["php_version"])?$_POST["php_version"]:""))),
        "description"=>trim(rawurldecode((string)(isset($_POST["description"])?$_POST["description"]:""))),
        "app_type"=>trim(rawurldecode((string)(isset($_POST["app_type"])?$_POST["app_type"]:"php"))),
        "enabled"=>intval(isset($_POST["enabled"])?$_POST["enabled"]:0),
    );
    if($data["app_type"]!=="wordpress"){$data["app_type"]="php";}
    if(strlen($data["name"])<2 || strlen($data["tag"])<1){
        echo htmlspecialchars($tpl->_ENGINE_parse_body("{error}"));
        return;
    }
    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/reverse-dockers/images",$data),true);
    if(!is_array($resp) || empty($resp["Status"])){
        $err=(is_array($resp) && isset($resp["Error"]) && strlen($resp["Error"])>0)?$resp["Error"]:"{error}";
        echo htmlspecialchars($tpl->_ENGINE_parse_body($err));
    }
}

function image_delete_js(){
    $tpl=new template_admin();
    $id=intval(isset($_GET["delete-js"])?$_GET["delete-js"]:0);
    $md=isset($_GET["md"])?$_GET["md"]:"";
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $after="$('#$md').remove();";
    if(strlen($function)>2){$after.="if(typeof $function==='function'){ $function(); }";}
    $tpl->js_confirm_delete("{image} #$id","image-delete","$id",$after);
}

// Echo nothing on success.
function image_delete(){
    $tpl=new template_admin();
    $id=intval(isset($_POST["image-delete"])?$_POST["image-delete"]:0);
    if($id<=0){return;}
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_DELETE("/reverse-dockers/images/$id");
    $resp=json_decode($raw,true);
    if(!is_array($resp) || empty($resp["Status"])){
        $err=(is_array($resp) && isset($resp["Error"]) && strlen($resp["Error"])>0)?$resp["Error"]:"{error}";
        echo htmlspecialchars($tpl->_ENGINE_parse_body($err));
    }
}

// Demarre le pull async puis ouvre la barre de progression (fichier BuildProgress).
function image_pull_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval(isset($_GET["pull-js"])?$_GET["pull-js"]:0);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-dockers/images/$id");
    $json=json_decode($raw);
    if(!is_object($json) || empty($json->Status) || !isset($json->Data)){
        $err=(is_object($json) && isset($json->Error))?$json->Error:"{error}";
        echo "alert(".json_encode($tpl->javascript_parse_text($err)).");\n";
        return;
    }
    $d=$json->Data;
    $name=(string)(isset($d->name)?$d->name:"");
    $tag=(string)(isset($d->tag)?$d->tag:"");
    $ref=$name.($tag!==""?":".$tag:"");
    if(strlen($ref)<3){
        echo "alert(".json_encode($tpl->javascript_parse_text("{error}")).");\n";
        return;
    }

    $resp=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON(
        "/reverse-dockers/images/pull",array("image"=>$ref)),true);
    if(!is_array($resp) || empty($resp["Status"])){
        $err=(is_array($resp) && isset($resp["Error"]) && strlen($resp["Error"])>0)?$resp["Error"]:"{error}";
        echo "alert(".json_encode($tpl->javascript_parse_text($err)).");\n";
        return;
    }

    $progressID="reverse.dockers.image.pull.progress";
    if(isset($resp["Data"]["progress"]) && strlen((string)$resp["Data"]["progress"])>2){
        $progressID=(string)$resp["Data"]["progress"];
    }

    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/".$progressID;
    $ARRAY["TITLE"]="{install} ".$ref;
    $ARRAY["AFTER"]="";
    if(strlen($function)>2){
        $ARRAY["AFTER"]="if(typeof $function==='function'){ $function(); }";
    }
    $prgress=base64_encode(serialize($ARRAY));
    echo "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-reverse-docker-images');\n";
}
