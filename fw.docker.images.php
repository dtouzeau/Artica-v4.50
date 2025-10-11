<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
    include_once('ressources/class.docker.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}

    if(isset($_GET["add-container-js"])){add_container_js();exit;}
    if(isset($_GET["add-container-search"])){add_container_search();exit;}
    if(isset($_GET["add-container-results"])){add_container_search_results();exit;}
    if(isset($_GET["add-container-choose"])){add_container_choose();exit;}
    if(isset($_POST["download-container"])){add_container_choose_perform();exit;}

    if(isset($_GET["upload-image-js"])){upload_image_js();exit;}
    if(isset($_GET["upload-image-popup"])){upload_image_popup();exit;}
    if(isset($_GET["file-uploaded"])){upload_image_uploaded();exit;}

    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["service-status"])){services_status();exit;}
	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}
    if(isset($_GET["remove-image-js"])){remove_image_js();exit;}
    if(isset($_POST["remove-image"])){remove_image_perform();exit;}
    if(isset($_GET["history-image-js"])){history_image_js();exit;}
    if(isset($_GET["history-image-popup"])){history_image_popup();exit;}

    if(isset($_GET["inspect-image-js"])){inspect_image_js();exit;}
    if(isset($_GET["inspect-image-popup"])){inspect_image_popup();exit;}
    if(isset($_GET["inspect-image-table"])){inspect_image_table();exit;}



    if(isset($_GET["changetag-js"])){changetag_js();exit;}
    if(isset($_GET["changetag-popup"])){changetag_popup();exit;}
    if(isset($_POST["changetag"])){changetag_save();exit;}
    if(isset($_GET["clean-cache-js"])){clean_cache();exit;}
	
page();

function upload_image_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    return $tpl->js_dialog1("{upload_an_image}","$page?upload-image-popup=yes&function-main=$function",500);

}
function clean_cache():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?clean-cache=yes");
    $function=$_GET["function-main"];

    $tpl=new template_admin();
    echo "$function();";
    return $tpl->js_display_results("{success}");

}
function changetag_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["changetag-js"];
    return $tpl->js_dialog2("{image_name}","$page?changetag-popup=$ID&function-main=$function",500);

}
function changetag_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["changetag-popup"];
    $docke=new dockerd();
    $ImageName=$docke->GetImageName($ID);


    $tag="latest";
    $ff=explode(",",$ImageName);
    foreach ($ff as $line){
         $ImageName=trim($line);
    }
    if(preg_match("#^(.+?):(.+)#",$ImageName,$re)){
        $ImageName=$re[1];
        $tag=$re[2];
    }

    $js[]="dialogInstance2.close()";
    if($function<>null) {
        $js[] = "$function()";

    }
    $md5=md5($ID);
    $js[]="LoadAjax('inspect-image-$md5','$page?inspect-image-table=$ID&function-main=$function')";

    $form[]=$tpl->field_hidden("changetag",$ID);
    $form[]=$tpl->field_text("image","{image_name}",$ImageName,true);
    $form[]=$tpl->field_text("tag","{tag}",$tag,true);
    $html[]=$tpl->form_outside("",
        $form,"","{apply}",@implode(";",$js),"AsDockerAdmin");

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function changetag_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["changetag"];
    $tag=$_POST["tag"];
    $image=$_POST["image"].":$tag";
    $md5=md5($ID);
    $tfile=PROGRESS_DIR."/changetag.$md5";
    $tinspect=PROGRESS_DIR."/inspect.$ID";
    $image_name=urlencode($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?changetag-image=$ID&name=$image_name&md5=$md5");
    $f=explode("\n",@file_get_contents($tfile));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        echo $line."<br>";
        return true;
    }
    @unlink($tinspect);
    admin_tracks("Change image name $ID to $image");
    return true;
}
function upload_image_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function-main"];
    $t=time();
    $bt_upload=$tpl->button_upload("{image} (*.tar,*.gz)",$page,"BYFORM:btn-success","&function-main=$function&t=$t")."&nbsp;&nbsp;";
    $html[]="<div id='upload-docker-$t'>";
    $html[]=$tpl->div_explain("{docker_upload_image_explain}");
    $html[]="<div class='center' style='margin:30px'>";
    $html[]=$bt_upload;
    $html[]="</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function upload_image_uploaded():bool{

    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $function=$_GET["function-main"];
    $path="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    admin_tracks("Importing docker image $filename");
    $fileencode=urlencode($filename);
    if(!preg_match("#\.(tar|gz)$#", $filename)){
        @unlink($path);
        return $tpl->js_error("$filename {not_supported}");

    }
    if($function<>null){
        $function="$function();";
    }
    $t=$_GET["t"];
    $md5=md5($filename);
    header("content-type: application/x-javascript");
    echo $tpl->framework_buildjs(
        "docker.php?image-uploaded=$fileencode&md5=$md5",
        "docker.image.$md5.progress",
        "docker.image.$md5.log",
        "upload-docker-$t",
        "dialogInstance1.close();$function"
    );
    return true;
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");

    $html=$tpl->page_header("{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {images}",
        ico_cd,
        "{APP_DOCKER_EXPLAIN}",
        "$page?table=yes","docker-images","progress-docker-containers",false,"table-docker-containers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker images",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function remove_image_js():bool{
    $tpl=new template_admin();
    $image=$_GET["remove-image-js"];
    $dock=new dockerd();
    $ImageName=html_entity_decode($dock->GetImageName($image));
    $md=$_GET["md"];
    $js=array();
    if($md<>null){
        $js[]="$('#$md').remove()";
    }

    if(isset($_GET["close"])){
        $close=intval($_GET["close"]);
        if($close>0){
            $js[]="dialogInstance$close.close()";
        }
        if(isset($_GET["function-main"])){
            $function=$_GET["function-main"];
            if($function<>null){
                $js[]="$function()";
            }
        }
    }
    return $tpl->js_confirm_delete("{remove} {image} $ImageName","remove-image",$image,@implode(";",$js));
}
function remove_image_perform():bool{
    $image=$_POST["remove-image"];
    $md5=md5($image);
    $tfile=PROGRESS_DIR."/image.delete.$md5";
    $imageenc=urlencode($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?remove-image=$imageenc&md5=$md5");
    $data=explode("\n",@file_get_contents($tfile));
    @unlink($tfile);
    foreach ($data as $line){
        $line=trim($line);
        if(preg_match("#(deleted|Untagged)#i",$line)){continue;}
        if($line<>null){echo $line."<br>";}
    }

    return admin_tracks("Remove docker image $image");
}
function history_image_js():bool{
    $tpl=new template_admin();
    $image=$_GET["history-image-js"];
    $imgageenc=urlencode($image);
    $page=CurrentPageName();
    return $tpl->js_dialog1("$image: {history}","$page?history-image-popup=$imgageenc");
}
function inspect_image_js():bool{
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $ID=$_GET["inspect-image-js"];
    $Name=$_GET["name"];
    if($ID==null){
        if($Name<>null){
            $dock=new dockerd();
            $ID=$dock->GetImageID($Name);
        }
    }

    $page=CurrentPageName();
    return $tpl->js_dialog1("{image}: $Name","$page?inspect-image-popup=$ID&function-main=$function_main");
}

function add_container_choose():bool{
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $container=$_GET["add-container-choose"];
    $containerEncode=urlencode($container);
    $md5=md5($container);
    $exec=$tpl->framework_buildjs(
        "docker.php?download-container=$containerEncode&md5=$md5",
        "docker.install.$md5",
        "docker.install.$md5.log",
        "progress-docker-containers","$function_main()",null,null,"AsDockerAdmin"
    );

    return $tpl->js_confirm_execute("{download} {image} $container",
        "download-container",$container,"BootstrapDialog1.close();$exec");


}
function add_container_choose_perform():bool{
    admin_tracks("Downloading docker image {$_POST["download-container"]}");
    return true;
}
function add_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog("{new_image}","$page?add-container-search=yes&function-main=$function");
}
function add_container_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_main=$_GET["function-main"];
    echo $tpl->search_block($page,null,null,null,"&add-container-results=yes&function-main=$function_main");
    return true;
}
function add_container_search_results():bool{
    $function_main=$_GET["function-main"];
    $tpl=new template_admin();
    $function=$_GET["function"];
    $md5=md5($_GET["search"]);
    $search=urlencode($_GET["search"]);
    if($search==null){$search="debian";}
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?search-images=$search&md5=$md5");
    $tfile=PROGRESS_DIR."/docker.images.search.$md5.json";
    if(!is_file($tfile)){
        echo $tpl->FATAL_ERROR_SHOW_128("$tfile not exists, framework error");
        return true;
    }
    $f=explode("\n",@file_get_contents($tfile));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{container}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>AUTO</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{official}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{download}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();
    foreach ($f as $line){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($line);
        $json=json_decode($line);
        $Description=$json->Description;
        $ico_IsAutomated="&nbsp;";
        $ico_IsOfficial="&nbsp;";
        $IsAutomated=$tpl->BoolToInteger($json->IsAutomated);
        $IsOfficial=$tpl->BoolToInteger($json->IsOfficial);
        $Name=$json->Name;
        if($IsAutomated==1){
            $ico_IsAutomated="<i class='".ico_check."'></i>";
        }
        if($IsOfficial==1){
            $ico_IsOfficial="<i class='".ico_check."'></i>";
        }
        $Description=str_replace(". ",".<br>",$Description);
        $Name_Encoded=urlencode($Name);
        $buton=$tpl->icon_download("Loadjs('$page?add-container-choose=$Name_Encoded&function-main=$function_main')","AsDockerAdmin");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' ><i class='".ico_cd."'></i></td>";
        $html[]="<td style='width:1%' nowrap><strong>$Name</strong></td>";
        $html[]="<td width='99%'>$Description</td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$ico_IsAutomated</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$ico_IsOfficial</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap>$buton</td>";
        $html[]="</tr>";



    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
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
    $topbuttons=array();
    $t=time();
    $search=$_GET["search"];
    $function=$_GET["function"];
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {images}";
    $TINY_ARRAY["ICO"]=ico_cd;
    $TINY_ARRAY["EXPL"]="{APP_DOCKER_EXPLAIN}";

    if($users->AsDockerAdmin) {
        $topbuttons[] = array("Loadjs('$page?add-container-js=yes&function=$function')", ico_plus, "{new_image}");
        $topbuttons[] = array("Loadjs('$page?upload-image-js=yes&function-main=$function')", ico_upload, "{upload_an_image}");
        $topbuttons[] = array("Loadjs('$page?clean-cache-js=yes&function-main=$function')", ico_trash, "{clean_cache}");
    }
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $results=$q->QUERY_SQL("SELECT * FROM frontends");

    $Perimeter_image=array();
    foreach ($results as $ligne){
        $frontendimageid=$ligne["frontendimageid"];
        $adminimageid=$ligne["adminimageid"];
        $name=$ligne["name"];
        $Perimeter_image[$frontendimageid]="{perimeter} $name";
        $Perimeter_image[$adminimageid]="{perimeter} $name";
    }


    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?list-images=yes");
    $results=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerImagesList"));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{image}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{perimeters}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{containers}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{history}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>TAG</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();
    $DockerImagesContainer=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerImagesContainer"));
    if(!is_array($DockerImagesContainer)){$DockerImagesContainer=array();}

    foreach ($results as $Name=>$line){

        if(strpos($Name,"|")>0){
            $tbr=explode("|",$Name);
            $Name=$tbr[1];
            if(preg_match("#^sha256:(.+)#",$Name,$rz)){
                $Name=substr($rz[1],0,8);
            }
        }
        $Containers=$line["Containers"];
        $CreatedAt=strtotime($line["CreatedAt"]);
        $CreatedSince=$line["CreatedSince"];
        $Digest=$line["Digest"];
        $ID=$line["ID"];
//        $SharedSize=$line["SharedSize"];
        $Size=$line["Size"];
        $Tag=$line["Tag"];
//        $UniqueSize=$line["UniqueSize"];
//        $VirtualSize=$line["VirtualSize"];

        if($search<>null){
            if(!preg_match("#$search#","$Name $Tag")){
                continue;
            }
        }

        $perimeter="&nbsp;";
        if(isset($Perimeter_image[$ID])){
            $perimeter="<span class='label label-info'>$Perimeter_image[$ID]</span>";
        }


        $zdate=$tpl->time_to_date($CreatedAt);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($line));

        $delval=urlencode("$Name");
        $del=$tpl->icon_delete("Loadjs('$page?remove-image-js=$ID&md=$md&function-main=$function')","AsDockerAdmin");

        $history=$tpl->icon_history("Loadjs('$page?history-image-js=$delval&function-main=$function')","AsDockerAdmin");
        $Name=$tpl->td_href($Name,null,"Loadjs('$page?inspect-image-js=$ID&name=$delval&function-main=$function')");
        $containers="<span class='label label-default'>0</span>";
        if(isset($DockerImagesContainer[$ID])){
            $containers="<span class='label label-primary'>".count($DockerImagesContainer[$ID])."</span>";
            $del=$tpl->icon_delete(null,"AsDockerAdmin");
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' ><i class='".ico_archive."'></i></td>";
        $html[]="<td style='width:1%' nowrap><strong>$Name</strong></td>";
        $html[]="<td width='99%'>$zdate <small>($CreatedSince)</small></td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$perimeter</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$containers</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$Size</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$history</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap><strong>$Tag</strong></td>";
        $html[]="<td style='width:1%' class='center' nowrap>$del</td>";
        $html[]="</tr>";



    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function history_image_popup():bool{
    $tpl=new template_admin();
    $image=$_GET["history-image-popup"];
    $imageenc=urlencode($image);
    $md=md5($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?image-history=$imageenc&md5=$md");
    $data=explode("\n",@file_get_contents(PROGRESS_DIR."/history.$md"));
    $TRCLASS=null;

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=99% nowrap>{comment}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{size}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($data as $line){
        $line=trim($line);
        if($line==null){continue;}
        $json=json_decode($line);
        $Comment=$json->Comment;
        $CreatedAt=$json->CreatedAt;
        //$CreatedBy=$json->CreatedBy;
        $CreatedSince=$json->CreatedSince;
        //$ID=$json->ID;
        $Size=$json->Size;
        $CreatedAtTime=0;

        if(preg_match("#^([0-9\-]+)T([0-9:]+)#",$CreatedAt,$ri)){
            $CreatedAtTime=strtotime($ri[1]." ".$ri[2]);

        }



        $zdate=$tpl->time_to_date($CreatedAtTime,true);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($line));

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><i class='".ico_clock."'></i></td>";
        $html[]="<td style='width:1%' nowrap>$zdate <small>($CreatedSince)</small></td>";
        $html[]="<td width='99%'>$Comment</td>";
        $html[]="<td width='1%' class='right' nowrap><strong>$Size</strong></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function inspect_image_popup():bool{
    $page=CurrentPageName();
    $ID=$_GET["inspect-image-popup"];
    $md5=md5($ID);
    $function_main=$_GET["function-main"];
    echo "<div id='inspect-image-$md5'></div>
    <script>LoadAjax('inspect-image-$md5','$page?inspect-image-table=$ID&function-main=$function_main');</script>
    ";
    return true;
}

function inspect_image_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dockerd=new dockerd();
    $ID=$_GET["inspect-image-table"];
    $function_main=$_GET["function-main"];

    $ASSOC=array();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?image-childs=$ID");
    $tfile=PROGRESS_DIR."/image.assoc.$ID";
    $f=explode("\n",@file_get_contents($tfile));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^(.+?)\s+(.+)#",$line,$re)){
            $ASSOC[]=$re[1];
            $ASSOC[]=$re[2];
        }
    }

    $tfile=PROGRESS_DIR."/inspect.$ID";
    if(!is_file($tfile)) {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?image-inspect=$ID");
    }
    $Name="{none}";
    $data=@file_get_contents($tfile);
    $Entrypoint=null;
    $json0=json_decode($data);
    $json=$json0[0];

    if($GLOBALS["VERBOSE"]){
        print_r($json->RepoTags);
    }
    if(isset($json->RepoTags[0])) {
        $Names=array();
        foreach ($json->RepoTags as $xname){$Names[] = $xname;}
        if(count($Names)>0){
            $Name=@implode(", ",$Names);
        }
    }
    $Parent=$json->Parent;
    $Container=$json->Container;
    $Parent_name="{none}";
    if($Parent<>null){
        $zParent_name=$dockerd->GetImageName($Parent);
        if($zParent_name<>null){
            $Parent_name=$zParent_name;
        }
    }
    $ContainerName=$dockerd->GetContainerName($Container);
    if(property_exists($json,"ContainerConfig")) {
        if (is_array($json->ContainerConfig->Entrypoint)) {
            $Entrypoint = @implode(" ", $json->ContainerConfig->Entrypoint);
        }
        $Hostname=$json->ContainerConfig->Hostname;
        if($Hostname==null){$Hostname="{none}";}
        $Image=$json->ContainerConfig->Image;
        if($Image<>null){
            if(preg_match("#^sha256:(.+)#",$Image,$rz)){
                $Image="{unknown} ".substr($rz[1],0,8);
            }

            $Image=" <small>(<i class='".ico_cd."'></i>&nbsp;{image}: $Image)</small>";}
        $tpl->table_form_field_text("{hostname}",$Hostname.$Image,ico_computer);
    }

    $Author=$json->Author;
    if($Author==null){$Author="{none}";}
    if($ContainerName==null){$ContainerName="{none}";}
    $tpl->table_form_field_js("Loadjs('$page?changetag-js=$ID&function-main=$function_main')","AsDockerAdmin");
    $tpl->table_form_field_text("{image}",$Name." (".FormatBytes($json->VirtualSize/1024).")",ico_cd);
    $tpl->table_form_field_js("");
    if($Parent_name<>null){
        $tpl->table_form_field_text("{parent}",$Parent_name,ico_cd);
    }
    if(count($ASSOC)>0){
        foreach ($ASSOC as $sid){
            $sid=trim($sid);
            if($sid==null){continue;}
            if($sid==$ID){continue;}
            $ImageName2=$dockerd->GetImageName($sid);
            if($ImageName2==null){$ImageName2=substr($sid,0,16);}
            $ImageName2Enc=urlencode($ImageName2);
            $js="Loadjs('$page?inspect-image-js=$sid&name=$ImageName2Enc&function-main=$function_main')";
            $tpl->table_form_field_js($js,"AsDockerAdmin");
            $tpl->table_form_field_text("{image}",$ImageName2,ico_link);
        }
    }


    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{entrypoint}","<small>$Entrypoint</small>",ico_script);
    $tpl->table_form_field_text("{author}",$Author,ico_admin);
    $tpl->table_form_field_text("{container}",$ContainerName,ico_computer);

    $GetImageLabels=$dockerd->GetImageLabels($ID);
    foreach ($GetImageLabels as $key=>$val){

        if($key=="com.articatech.artica.build"){
            $tpl->table_form_field_text("{image} Build",$val,ico_infoi);
            continue;
        }
        if($key=="com.articatech.artica.version"){
            $tpl->table_form_field_text("Artica {version}",$val,ico_infoi);
            continue;
        }




        if($key=="com.articatech.artica.scope"){
            $tpl->table_form_field_text("{perimeters}",$dockerd->PerimeterName($val),ico_clouds);
            continue;
        }
        if($key=="com.articatech.artica.type"){
            if($val=="ADM"){
                $tpl->table_form_field_text("{type}","{webconsole}",ico_clouds);
                continue;
            }
            if($val=="LB"){
                $tpl->table_form_field_text("{type}","Load-balancer",ico_clouds);
                continue;
            }
        }

        $tpl->table_form_field_text($key,$val,ico_label);
    }


    $del="Loadjs('$page?remove-image-js=$ID&md=&function-main=$function_main&close=1')";
    $tpl->table_form_button("{delete}",$del,"AsDockerAdmin",ico_trash);

    echo $tpl->table_form_compile();
    return true;
}
