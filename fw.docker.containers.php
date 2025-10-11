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

    if(isset($_GET["delete-container-js"])){delete_container_js();exit;}
    if(isset($_POST["delete-container"])){delete_container_perform();exit;}


    if(isset($_GET["link-container-js"])){link_container_js();exit;}
    if(isset($_GET["link-container-popup"])){link_container_popup();exit;}
    if(isset($_POST["link-image"])){link_container_save();exit;}

    if(isset($_GET["stop-container"])){stop_container();exit;}
    if(isset($_GET["start-container"])){start_container();exit;}
    if(isset($_GET["unpause-container"])){unpause_container();exit;}


    if(isset($_GET["info-container"])){info_container_js();exit;}
    if(isset($_GET["info-container-popup"])){info_container_popup();exit;}
    if(isset($_GET["info-container-table"])){info_container_table();exit;}
    if(isset($_GET["info-container-tab"])){info_container_tab();exit;}
    if(isset($_GET["info-container-mounts"])){info_container_mounts();exit;}


    if(isset($_GET["info-container-labels"])){info_container_labels();exit;}
    if(isset($_GET["info-container-labels-table"])){info_container_labels_table();exit;}

    if(isset($_GET["info-container-network"])){info_container_network();exit;}
    if(isset($_GET["info-container-network-table"])){info_container_network_table();exit;}
    if(isset($_GET["delete-network"])){delete_container_network();exit;}
    if(isset($_POST["delete-network"])){delete_container_network_perform();exit;}
    if(isset($_GET["network-connect"])){connect_container_js();exit;}
    if(isset($_GET["network-connect-popup"])){connect_container_popup();exit;}
    if(isset($_GET["network-connect-perform"])){connect_container_perform();exit;}

    if(isset($_GET["info-container-details"])){info_container_details();exit;}

    if(isset($_GET["shellinaboxd-install"])){shellinaboxd_install();exit;}
    if(isset($_POST["shellinaboxd-install"])){shellinaboxd_install_perform();exit;}

    if(isset($_GET["docker-name-js"])){container_name_js();exit;}
    if(isset($_GET["docker-name-popup"])){container_name_popup();exit;}
    if(isset($_POST["docker-name"])){container_name_save();exit;}

    if(isset($_GET["compile-container"])){compile_container();exit;}
    if(isset($_GET["container-compile-popup"])){compile_container_popup();exit;}
    if(isset($_POST["compile-container"])){compile_container_save();exit;}

    if(isset($_GET["export-container"])){export_container();exit;}
    if(isset($_GET["export-container-popup"])){export_container_popup();exit;}
    if(isset($_GET["export-container-done"])){export_container_done();exit;}
    if(isset($_GET["export-container-download"])){export_container_download();exit;}

    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["service-status"])){services_status();exit;}
	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}


	
page();

function delete_container_js():bool{
    $tpl=new template_admin();
    $image=$_GET["delete-container-js"];
    $ID=$_GET["ID"];
    $md=$_GET["md"];
    $deletejs=$tpl->framework_buildjs(
        "docker.php?remove-container=$ID",
        "docker.rm.$ID.progress",
        "docker.rm.$ID.log",
        "progress-docker-containers",
        "$('#$md').remove()",null,null,"AsDockerAdmin"

    );


    return $tpl->js_confirm_delete("{remove} {container} $image","delete-container",$image,$deletejs);
}
function delete_container_perform():bool{
    $image=$_POST["delete-container"];
    return admin_tracks("Remove docker container $image");
}
function export_container():bool{
    $ID=$_GET["ID"];
    $nameec=urlencode($_GET["export-container"]);
    $function=$_GET["function-main"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{export} {$_GET["export-container"]}","$page?export-container-popup=$nameec&function-main=$function&ID=$ID",540);
}
function export_container_popup():bool{
    $ID=$_GET["ID"];
    $nameec=urlencode($_GET["export-container-popup"]);
    $Name=$_GET["export-container-popup"];
    $function=$_GET["function-main"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id="aaa-".md5($ID);
    $jsTop=null;
    $AfTer="LoadAjax('$id','$page?export-container-done=$nameec&ID=$ID&function-main=$function')";
    $js=$tpl->framework_buildjs(
        "docker.php?export-container=$nameec&ID=$ID",
        "docker.export.$ID",
        "docker.export.$ID.log",$id,
        $AfTer

    );

    $tdir="/home/docker-export";
    $tfile="$tdir/$ID";
    if(is_file($tfile)){
        $jsTop=$AfTer;
    }

    $html[]="<div id='$id' style='margin-top:20px;margin-bottom:20px'>";
    $html[]="<div class='center'>". $tpl->button_autnonome("{export} $Name: {start}",
            $js,ico_export,"AsDockerAdmin",501);
    $html[]="</div>";
    $html[]="<script>$jsTop</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function export_container_done():bool{
    $ID=$_GET["ID"];
    $nameec=urlencode($_GET["export-container-done"]);
    $Name=$_GET["export-container-done"];
    $function=$_GET["function-main"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tdir="/home/docker-export";
    $tfile="$tdir/$ID";
    $size=filesize($tfile);
    $size=FormatBytes($size/1024);
    $js="document.location.href='/$page?export-container-download=$nameec&ID=$ID&function-main=$function'";
    $html=$tpl->button_autnonome("{download} $Name ($size)",
        $js,ico_download,"AsDockerAdmin",501);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function export_container_download():bool{

    $Name=$_GET["export-container-download"];
    $ID=$_GET["ID"];
    $tdir="/home/docker-export";
    $tfile="$tdir/$ID";

    $type="application/gzip";
    $fsize=@filesize($tfile);
    $timestamp =filemtime($tfile);
    $etag = md5($tfile . $timestamp);

    $basename=$Name;
    $basename=str_replace("/","_",$basename);
    $basename=str_replace(":","_",$basename);
    $tsstring = gmdate('D, d M Y H:i:s ', $timestamp) . 'GMT';
    header("Content-Length: ".$fsize);
    header('Content-type: '.$type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$basename.tar.gz\"");
    header("Cache-Control: no-cache, must-revalidate");
    header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', $timestamp + (60 * 60)));
    header("Last-Modified: $tsstring");
    header("ETag: \"$etag\"");
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($tfile);
    return true;
}
function unpause_container():bool{
    $tpl=new template_admin();
    $image=$_GET["unpause-container"];
    $function=$_GET["function-main"];
    $imageenc=urlencode($image);
    $md5=md5($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?unpause-container=$imageenc&md5=$md5");


    $tfile=PROGRESS_DIR."/docker.$md5.unpause";
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));

        }
    }

    header("content-type: application/x-javascript");
    echo "BootstrapDialog1.close();\n";
    echo "$function();";
    return admin_tracks("Starting $image docker container...");

}
function start_container():bool{
    $tpl=new template_admin();
    $image=$_GET["start-container"];
    $function=$_GET["function-main"];
    $imageenc=urlencode($image);
    $ID=$_GET["ID"];
    $md5=md5($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?start-container=$ID&md5=$md5");


    $tfile=PROGRESS_DIR."/docker.$md5.start";
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));

        }
    }

    header("content-type: application/x-javascript");
    echo "try{\n";
    echo "BootstrapDialog1.close();\n";
    echo "} catch(e) {\n";
    echo "//console.log('detected: variable not exists');\n";
    echo "}\n";

    echo "$function();";
    return admin_tracks("Starting $image docker container...");
}
function info_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["info-container"];
    $dock=new dockerd();
    $Names=$dock->GetContainerName($ID);
    $function_main=$_GET["function-main"];
    return $tpl->js_dialog($Names,"$page?info-container-tab=$ID&function-main=$function_main");
}
function compile_container():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["compile-container"];
    $Name=$_GET["name"];
    $function_main=$_GET["function-main"];
    return $tpl->js_dialog("{docker_build_image} ($Name)","$page?container-compile-popup=$ID&name=$Name&function-main=$function_main");
}


function compile_container_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["container-compile-popup"];
    $Name=$_GET["name"];
    $function_main=$_GET["function-main"];

    $commit=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerCommit-$ID"));
    if(!isset($commit["image"])){
        $commit["image"]="personal/$Name:version1";
    }
    if(!isset($commit["author"])){
        $commit["author"]=null;
    }
    if(!isset($commit["message"])){
        $commit["message"]=null;
    }
    if(!isset($commit["changes"])){
        $commit["changes"]=null;
    }

    $form[]=$tpl->field_hidden("compile-container",$ID);
    $form[]=$tpl->field_hidden("compile-container-name",$Name);
    $form[]=$tpl->field_text("image","{image_name}", $commit["image"]);
    $form[]=$tpl->field_text("author","{author}", $commit["author"]);
    $form[]=$tpl->field_text("message","{description}", $commit["message"]);
    $form[]=$tpl->field_text("changes","{options}",$commit["changes"],false,"{docker_build_changes_explain}");

    $compile=$tpl->framework_buildjs("docker.php?commit=$ID",
    "docker.commit.$ID","docker.commit.$ID.log","commit-$ID","BootstrapDialog1.close();$function_main();",null,null,"AsDockerAdmin");
    $html[]="<div id='commit-$ID'></div>";
    $html[]=$tpl->form_outside("{docker_build_image} ($Name)",
        $form,"{docker_build_image_explain}","{compile}",$compile,"AsDockerAdmin");

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function compile_container_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["compile-container"];
    $Name=$_POST["compile-container-name"];
    $image=$_POST["image"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerCommit-$ID",serialize($_POST));
    admin_tracks("Commit a new docker image $image from $Name");
    return true;
}

function info_container_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["info-container-tab"];
    $function_main=$_GET["function-main"];

    $dock=new dockerd();
    $Names=$dock->GetContainerName($ID);
    $array[$Names]="$page?info-container-popup=$ID&function-main=$function_main";
    $array["{networks}"]="$page?info-container-network=$ID&function-main=$function_main";
    $array["{labels}"]="$page?info-container-labels=$ID&function-main=$function_main";
    $array["{mounts}"]="$page?info-container-mounts=$ID&function-main=$function_main";
    $array["{details}"]="$page?info-container-details=$ID&function-main=$function_main";

    echo $tpl->tabs_default($array);
    return true;
}
function info_container_network():bool{
    $page=CurrentPageName();
    $ID=$_GET["info-container-network"];
    $function=$_GET["function-main"];
    echo "<div id='network-$ID'></div><script>
        LoadAjax('network-$ID','$page?info-container-network-table=$ID&function-main=$function')
     </script>";
    return true;
}
function connect_container_perform():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["network-connect-perform"];
    if(strlen($ID)<3){
        return $tpl->js_error("No ID!");
    }
    $IDNet=$_GET["IDNet"];
    $function_main=$_GET["function-main"];
    $md=$_GET["md"];
    $tfile=PROGRESS_DIR."/docker.network.connect.$ID.$IDNet";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?network-connect=$ID&net=$IDNet");
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));
        }
    }
    $dock=new dockerd();
    $ContainerName=$dock->GetContainerName($ID);
    $NetworkInfo=$dock->NetworkInfo($IDNet);
    admin_tracks("Connect $ContainerName to {$NetworkInfo["Name"]}");
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n$function_main();\nLoadAjax('network-$ID','$page?info-container-network-table=$ID&function-main=$function_main')";
    return true;
}

function connect_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["network-connect"];
    $function=$_GET["function-main"];
    $dock=new dockerd();
    $title="{connect}: ".$dock->GetContainerName($ID);
    return $tpl->js_dialog5($title,"$page?network-connect-popup=$ID&function-main=$function");
}
function connect_container_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["network-connect-popup"];
    if(strlen($ID)<3){
        echo $tpl->FATAL_ERROR_SHOW_128("{error}, no id!");
        return true;
    }
    $function_main=$_GET["function-main"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?network-list=yes");
    $tfile=PROGRESS_DIR."/docker.network.list";
    if(!is_file($tfile)){
        echo $tpl->FATAL_ERROR_SHOW_128("$tfile not exists, framework error");
        return true;
    }
    $f=explode("\n",@file_get_contents($tfile));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{driver}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{scope}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{network}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{gateway}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $dock=new dockerd();
    $CurrentNets=$dock->GetContainerNetworks($ID);

    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($line);
        $json=json_decode($line);
        $IDNet=$json->ID;
        if(isset($CurrentNets[$IDNet])){continue;}
        $Driver=$json->Driver;
        $Name=$json->Name;
        $Scope=$json->Scope;
        $MAIN=$dock->NetworkInfo($IDNet);
        $buton=$tpl->icon_select("Loadjs('$page?network-connect-perform=$ID&function-main=$function_main&md=$md&IDNet=$IDNet')","AsDockerAdmin");

        if($Ports<>null){$Ports="($Ports)";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><li class='".ico_networks."'></li></td>";
        $html[]="<td width='1%' nowrap>$Driver</td>";
        $html[]="<td nowrap><strong>$Name</strong></td>";
        $html[]="<td width='1%' nowrap><i class=\"fa-solid fa-arrow-right-long-to-line\"></i></td>";
        $html[]="<td  nowrap width='1%' nowrap>$Scope</td>";
        $html[]="<td  nowrap width='1%' nowrap>{$MAIN["Subnet"]}</td>";
        $html[]="<td  nowrap width='1%' nowrap>{$MAIN["Gateway"]}</td>";
        $html[]="<td width='1%' class='center' nowrap>$buton</td>";
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

function info_container_network_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["info-container-network-table"];
    $function=$_GET["function-main"];
    $dock=new dockerd();
    $Nets=$dock->GetContainerNetworks($ID);

    $topbuttons["SMALL"]=true;
    $topbuttons[] = array("Loadjs('$page?network-connect=$ID&function-main=$function');",ico_link,"{connect}");
    $html[]="<div style='margin-top:20px'></div>";
    $html[]=$tpl->table_buttons($topbuttons);


    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{container}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{mac}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach ($Nets as $ContainerID=>$ligne) {
        $md=md5(serialize($ligne));
        $IPAddress=$ligne["IPAddress"];
        $MacAddress=$ligne["MAC"];
        $Name=$ligne["Name"];
        //
        $unlink=$tpl->icon_unlink("Loadjs('$page?delete-network=$ContainerID&ID=$ID&md=$md&function-main=$function')","AsDockerAdmin");

        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td width='1%'><i class='".ico_computer."'></i></td>";
        $html[] = "<td nowrap>$Name</td>";
        $html[] = "<td nowrap width='1%' nowrap><strong>$IPAddress</strong></td>";
        $html[] = "<td width='1%' nowrap>$MacAddress</td>";
        $html[] = "<td width='1%' nowrap>$unlink</td>";
        $html[] = "</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function delete_container_network():bool{
    $NetID=$_GET["delete-network"];
    $ID=$_GET["ID"];
    $md=$_GET["md"];
    $function=$_GET["function-main"];
    $dock=new dockerd();
    $GET_ENCODED=base64_encode(serialize($_GET));
    $ContainerName=$dock->GetContainerName($ID);
    $NetworkInfo=$dock->NetworkInfo($NetID);
    $text="{disconnect} $ContainerName {from} {$NetworkInfo["Name"]}";
    $tpl=new template_admin();
    return $tpl->js_confirm_delete($text,"delete-network",$GET_ENCODED,"$('#$md').remove();$function();");
}
function delete_container_network_perform():bool{
    $GET=unserialize(base64_decode($_POST["delete-network"]));
    $dock=new dockerd();

    $NetID=$GET["delete-network"];
    $ID=$GET["ID"];
    $ContainerName=$dock->GetContainerName($ID);
    $NetworkInfo=$dock->NetworkInfo($NetID);
    $tfile=PROGRESS_DIR."/docker.netdel.$NetID.$ID";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?container-delnet=$ID&net=$NetID");


    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
           echo implode("<br>",$results);
           return false;

        }
    }

    admin_tracks("Disconnect container $ContainerName from Network {$NetworkInfo["Name"]}");
    return true;
}

function _info_container_json($xvals=array(),$json):array{
    $html[]="<table id='table2' class=\"table table-stripped\">";

    foreach ($json as $key=>$val){
        $html[]="<tr>";
        $html[]="<td>$key;</td><td>$val</td>";
        $html[]="</tr>";
    }

    $xvals[]=@implode("",$html);
return $xvals;
}
function info_container_parse($array){
    $html[]="<table id='table2' class=\"table table-stripped\">";
    $xvals=array();
    foreach ($array as $key=>$val){

        if(is_numeric($key)){
            if($val instanceof stdClass) {
                $xvals =_info_container_json($xvals,$val);
                continue;}
            $xvals[]=$val;
            continue;
        }
        $html[]="<tr>";

        if(count($xvals)>0){
            $html[]="<tr>";
            $html[]="<td>&nbsp;</td>";
            $html[]="<td id='line.".__LINE__."'>". @implode(" ",$xvals)."</td>";
            $html[]="</tr>";
            $xvals=array();
            continue;
        }

        $key=strtolower($key);
        $html[]="<td style='vertical-align: top'>{{$key}}:</td>";
        if(is_object($val)){
            $html[]="<td>". info_container_parse($val)."</td>";
            $html[]="</tr>";
            continue;
        }
        if(is_array($val)){
            $html[]="<td>". info_container_parse($val)."</td>";
            $html[]="</tr>";
            continue;
        }


        $html[]="<td id='line.".__LINE__."'><strong id='line.".__LINE__."'>$val</strong></td>";
        $html[]="</tr>";
    }
    if(count($xvals)>0) {
        if(!$xvals instanceof stdClass) {
            $html[] = "<tr>";
            $html[] = "<td>&nbsp;</td>";
            $html[] = "<td id='line." . __LINE__ . "'>" . @implode(", ", $xvals) . "</td>";
            $html[] = "</tr>";
        }
    }


    $html[]="</table>";
    $html[]="<!-- FINISH -->";
    return @implode("\n",$html);
}
function info_container_mounts(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["info-container-mounts"];
    $function_main=$_GET["function-main"];

    $Dock=new dockerd();
    $data=json_decode($Dock->GetContainerDetails($ID));
    if(is_null($data)){return false;}
    if(!property_exists($data,"Mounts")){
        echo $tpl->FATAL_ERROR_SHOW_128("No property");
        return false;
    }

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{source}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{destination}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{writeable}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach ($data->Mounts as $json){
        $Type=$json->Type;
        $Source=$json->Source;
        $Destination=$json->Destination;
        $RW=$json->RW;
        $md=md5(serialize($json));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><li class='".ico_folder."'></li></td>";
        $html[]="<td width='1%' nowrap>$Source</td>";
        $html[]="<td nowrap><strong>$Destination</strong></td>";
        if($RW) {
            $html[] = "<td width='1%' nowrap><i class=\"" . ico_check . "\"></i></td>";
        }else{
            $html[] = "<td width='1%' nowrap></td>";
        }
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
function info_container_details():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["info-container-details"];
    $function_main=$_GET["function-main"];

    $Dock=new dockerd();
    $data=json_decode($Dock->GetContainerDetails($ID));

    $jsonRepresentation = json_encode($data,  JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    echo "<textarea style='width:100%;height:450px'>$jsonRepresentation</textarea>";

    return true;
}
function info_container_labels(){
    $page=CurrentPageName();
    $ID=$_GET["info-container-labels"];
    $function_main=$_GET["function-main"];
    $md5=md5($ID);
    echo "<div id='inspect-container-labels-$md5'></div>
    <script>LoadAjax('inspect-container-labels-$md5','$page?info-container-labels-table=$ID&function-main=$function_main');</script>
    ";
    return true;
}
function info_container_labels_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["info-container-labels-table"];
    $function_main=$_GET["function-main"];
    $dock=new dockerd();
    $GetContainerLabels=$dock->GetContainerLabels($ID);
    $zType["LB"]="Load-balancer";
    $zType["BACK"]="Reverse-Proxy";
    $zType["ADM"]="{webconsole}";
    $GPR=false;
    foreach ($GetContainerLabels as $key=>$val){
        $ico=ico_label;
        VERBOSE("$key === [$val]",__LINE__);

        if(!$GPR) {
            if (preg_match("#com\.articatech\.artica\.scope\.([0-9]+)\.webadm\.([0-9]+)#", $key, $re)) {
                $GPR = true;
                $tpl->table_form_field_text("{group}", $dock->PerimeterName($re[1]) . "&nbsp;/&nbsp;" . $dock->GroupName($re[2]), ico_clouds);
                continue;
            }
        }
        if(!$GPR) {
            if (preg_match("#com\.articatech\.artica.scope\.([0-9]+)\.backend\.([0-9]+)#", $key, $re)) {
                $GPR = true;
                $tpl->table_form_field_text("{group}", $dock->PerimeterName($re[1]) . "&nbsp;/&nbsp;" . $dock->GroupName($re[2]), ico_clouds);
                continue;
            }
        }
        if(!$GPR) {
            if (preg_match("#com\.articatech\.artica.group\.([0-9]+)#", $key, $re)) {
                $gp = new dockerd_groups($re[1]);
                $GPR = true;
                $PerimeterID = $gp->GetPermimeterID();
                $tpl->table_form_field_text("{group}", $dock->PerimeterName($PerimeterID) . "&nbsp;/&nbsp;" . $gp->GroupName(), ico_clouds);
                continue;
            }
        }

        if(preg_match("#com.articatech.artica.scope.([0-9]+)$#",$key,$re)){
            $tpl->table_form_field_text("{perimeter}",$dock->PerimeterName($re[1]),ico_clouds);
            continue;
        }
        if(preg_match("#com.articatech.artica.type.([A-Z]+)#",$key,$re)){
            $tpl->table_form_field_text("{type}",$zType[$re[1]],ico_server);
            continue;
        }

        if(preg_match("#com.articatech.artica#",$key)){
            continue;
        }


        $tpl->table_form_field_js("");
        $key=str_replace(array("org.opencontainers.image.","com.articatech.artica.","com.docker.","io.docker.","org.dockerproject."),"",$key);
        $key=str_replace("created","{created}",$key);
        $key=str_replace("description","{description}",$key);
        $key=str_replace("licenses","{licenses}",$key);
        $key=str_replace("build_version","{version}",$key);
        $key=str_replace("vendor","{vendor}",$key);


        if(preg_match("#^http.*?:#",$val)){
            $ico=ico_link;
            $tpl->table_form_field_js("s_PopUpFull('$val','1024','900');");
        }
        $tpl->table_form_field_text($key,$val,$ico);
    }
    echo $tpl->table_form_compile();
}
function info_container_popup():bool{
    $page=CurrentPageName();
    $ID=$_GET["info-container-popup"];
    $function_main=$_GET["function-main"];
    $md5=md5($ID);
    echo "<div id='inspect-container-$md5'></div>
    <script>LoadAjax('inspect-container-$md5','$page?info-container-table=$ID&function-main=$function_main');</script>
    ";
    return true;
}
function container_name_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["docker-name-js"];
    return $tpl->js_dialog2("{name}","$page?docker-name-popup=$ID&function-main=$function",500);
}
function container_name_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["docker-name-popup"];
    $docke=new dockerd();
    $ImageName=$docke->GetContainerName($ID);

    $ff=explode(",",$ImageName);
    foreach ($ff as $line){
        $ImageName=trim($line);
    }


    $js[]="dialogInstance2.close()";
    if($function<>null) {
        $js[] = "$function()";

    }
    $md5=md5($ID);
    $js[]="LoadAjax('inspect-container-$md5','$page?info-container-table=$ID&function-main=$function');";

    if(substr($ImageName,0,1)=="/"){
        $ImageName=substr($ImageName,1,strlen($ImageName));
    }

    $form[]=$tpl->field_hidden("docker-name",$ID);
    $form[]=$tpl->field_text("name","{name}",$ImageName,true);
    $html[]=$tpl->form_outside("",
        $form,"","{apply}",@implode(";",$js),"AsDockerAdmin");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function container_name_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["docker-name"];
    $Name=$_POST["name"];

    if(!preg_match("#^[a-zA-Z0-9_\.\-]+$#",$Name)){
        echo $tpl->post_error("$Name {bad_format}");
        return false;
    }

    $dock=new dockerd();
    $oldname=$dock->GetContainerName($ID);
    $NameEnc=urlencode($Name);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?container-rename=$ID&name=$NameEnc");
    $tfile=PROGRESS_DIR."/docker.$ID.rename";
    $data=explode("\n",@file_get_contents($tfile));
    @unlink($tfile);
    foreach ($data as $line){
        $line=trim($line);
        if(preg_match("#(deleted|Untagged)#i",$line)){continue;}
        if($line<>null){echo $line."<br>";}
    }
    admin_tracks("Change $oldname container name to $Name");
    return true;
}
function info_container_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["info-container-table"];
    $function_main=$_GET["function-main"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?container-details=$ID");
    $docker=new dockerd();
    $json=json_decode($docker->GetContainerDetails($ID));

    $perfs=$docker->GetContainerPerformances($ID);
    $tpl->table_form_field_js("");
    if(isset($perfs["CPUPerc"])){
        $tpl->table_form_field_text("{performance}","CPU: {$perfs["CPUPerc"]}, Memory: {$perfs["MemPerc"]}",ico_memory);
    }


    if(!$json){
        echo $tpl->FATAL_ERROR_SHOW_128("Error $docker->mysql_error");
        return false;
    }
    if(is_null($json)){
        echo $tpl->FATAL_ERROR_SHOW_128("Error $docker->mysql_error");
        return false;
    }
    //



    $Name=$json->Name;
    if(substr($Name,0,1)=="/"){
        $Name=substr($Name,1,strlen($Name));
    }
    $tpl->table_form_field_js("Loadjs('$page?docker-name-js=$ID&function-main=$function_main')","AsDockerAdmin");
    $tpl->table_form_field_text("{name}",$Name,ico_field);
    $tpl->table_form_field_js("");

    if(property_exists($json,"HostConfig")){
        if(property_exists($json->HostConfig,"RestartPolicy")){
            $tpl->table_form_field_text("{restart_policy}","{".$json->HostConfig->RestartPolicy->Name."}",ico_refresh);
        }else{
            $tpl->table_form_field_text("{restart_policy}","{none}",ico_refresh);
        }

    }else{
        $tpl->table_form_field_text("{restart_policy}","{none}",ico_refresh);
    }


    $tpl->table_form_field_text("{createdat}",$json->Created,ico_clock);
    $Path[]=$json->Path;
    if(count($json->Args)>0){
        foreach ($json->Args as $ar){
            $Path[]=$ar;
        }
    }
    $tpl->table_form_field_text("{command}",@implode(" ",$Path),ico_script);
    $ICONS["command"]=ico_script;
    $ICONS["createdat"]=ico_clock;
    $ICONS["id"]=ico_params;
    $ICONS["localvolumes"]=ico_hd;
    $ICONS["mounts"]=ico_folder;
    $ICONS["networks"]=ico_nic;
    $ICONS["runningfor"]=ico_timeout;
    $ICONS["size"]=ico_weight;
    $ICONS["image"]=ico_cd;

    $Image=$json->Image;
    if($Image<>null) {
        $ImageName = $docker->GetImageName($Image);
        $tpl->table_form_field_js("Loadjs('fw.docker.images.php?inspect-image-js=$Image&name=$ImageName&function-main=$function_main')","AsDockerAdmin");
        $tpl->table_form_field_text("{image}", $ImageName, ico_cd);
        $tpl->table_form_field_js("");
    }
    $Path=array();
    if(count($json->Config->Entrypoint)>0){
        foreach ($json->Config->Entrypoint as $ar){
            $Path[]=$ar;
        }
    }
    $tpl->table_form_field_text("{entrypoint}",@implode(" ",$Path),ico_script);

    echo $tpl->table_form_compile();
    return true;
}
function stop_container():bool{
    $tpl=new template_admin();
    $image=$_GET["stop-container"];
    $ID=$_GET["ID"];
    $function=$_GET["function-main"];
    $imageenc=urlencode($image);
    $md5=md5($image);

    $js=$tpl->framework_buildjs(
        "docker.php?stop-container=$ID",
        "docker.$ID.stop",
        "docker.$ID.stop.log",
        "progress-docker-containers","$function();","$function();",null,null,"AsDockerAdmin"
    );

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?stop-container=$ID");


    $tfile=PROGRESS_DIR."/docker.$md5.stop";
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));

        }
    }
    header("content-type: application/x-javascript");
    echo $js;
    return admin_tracks("Stopping $image docker container...");
}

function link_container_js():bool{
    $image=$_GET["link-container-js"];
    $imageenc=urlencode($_GET["link-container-js"]);
    $function=$_GET["function-main"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dock=new dockerd();
    $image_name=$dock->GetImageName($image);

    return $tpl->js_dialog("$image_name","$page?link-container-popup=$imageenc&function-main=$function");

}
function link_container_popup():bool{
    $image=$_GET["link-container-popup"];
    $imagemd5=md5($image);
    $imageenc=urlencode($image);
    $function=$_GET["function-main"];
    $tpl=new template_admin();

    $data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerContainersSettings");
    $DockerContainersSettings=unserialize($data);
    if(!isset($DockerContainersSettings[$image])){$DockerContainersSettings[$image]=array();}

    $ContainersSettings=$DockerContainersSettings[$image];

    if(!isset($ContainersSettings["HOSTNAME"])){
        $ContainersSettings["HOSTNAME"]=null;
    }
    if(!isset($ContainersSettings["NAME"])){
        $ContainersSettings["NAME"]=null;
    }
    $dock=new dockerd();
    $image_name=$dock->GetImageName($image);

    $html[]="<div id='bb_$imagemd5' style='margin:10px'></div>";
    $form[]=$tpl->field_hidden("link-image",$image);
    $form[]=$tpl->field_text("HOSTNAME","{hostname}",$ContainersSettings["HOSTNAME"]);
    $form[]=$tpl->field_text("NAME","{container_name}",$ContainersSettings["NAME"],true);
    $form[]=$tpl->field_checkbox("CHANGEENTRYPOINT","{entrypoint}",$ContainersSettings["CHANGEENTRYPOINT"],"USETAIL,ENTRYPOINT");
    $form[]=$tpl->field_checkbox("USETAIL","{entrypoint} (tail)",$ContainersSettings["USETAIL"]);
    $form[]=$tpl->field_text("ENTRYPOINT","{entrypoint}",$ContainersSettings["ENTRYPOINT"]);
    $js=$tpl->framework_buildjs("docker.php?link-container=$imageenc&md5=$imagemd5",
        "docker.link.$imagemd5","docker.link.$imagemd5.log","bb_$imagemd5",
        "BootstrapDialog1.close();$function();","BootstrapDialog1.close();$function();",null,null,"AsDockerAdmin"
    );
    $security="AsDockerAdmin";
    $html[]=$tpl->form_outside($image_name, $form,null,"{import}",$js,$security,false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function link_container_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $image=$_POST["link-image"];

    foreach ($_POST as $key=>$val) {
        writelogs("Saving $key == $val",__FUNCTION__,__FILE__,__LINE__);
    }


    $_POST["NAME"]=str_replace([' ',',','&',';','%','/','\\','*','|','(',')','{','}','[',']','=',':','#','~','>','<','?','!','$','¤','"','+'],'',$_POST["NAME"]);

    $_POST["HOSTNAME"]=str_replace([' ',',','&',';','%','/','\\','*','|','(',')','{','}','[',']','=',':','#','~','>','<','?','!','$','¤','"','+'],'',$_POST["HOSTNAME"]);

    if($_POST["NAME"]<>null) {
        if (!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_.-]+$#", $_POST["NAME"])) {
            echo $tpl->post_error("{$_POST["NAME"]} {invalid}");
            return false;
        }
    }
    $doker=new dockerd();
    $ID=$doker->GetContainerID($_POST["NAME"]);

    if(strlen($ID)>5){
        echo $tpl->post_error("{$_POST["NAME"]} {alreadyexists}");
        return false;
    }


    $DockerContainersSettings=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerContainersSettings"));
    foreach ($_POST as $key=>$val) {
        $LOG[]="$key=$val";
        $DockerContainersSettings[$image][$key] = $val;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerContainersSettings",serialize($DockerContainersSettings));
    admin_tracks("Link docker $image into container ".@implode(", ",$LOG));
    return true;
}


function link_container_js2():bool{
    $tpl=new template_admin();
    $image=$_GET["link-container-js"];
    $function=$_GET["function-main"];
    $imageenc=urlencode($image);
    $md5=md5($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?link-container=$imageenc&md5=$md5");


    $tfile=PROGRESS_DIR."/docker.$md5.run";
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));

        }
    }

    header("content-type: application/x-javascript");
    echo "BootstrapDialog1.close();\n";
    echo "$function();";
    return admin_tracks("Run a new docker container from an image $image");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");

    $html=$tpl->page_header("{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {containers}",
        ico_containers,
        "{APP_DOCKER_EXPLAIN}",
        "$page?table=yes","docker-containers","progress-docker-containers",false,"table-docker-containers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker containers",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function add_container_choose():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
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

    return $tpl->js_confirm_execute("{download} {container} $container",
        "download-container",$container,"BootstrapDialog1.close();$exec");


}
function add_container_choose_perform(){
    admin_tracks("Downloading docker image {$_POST["download-container"]}");
}
function add_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog("{new_container}","$page?add-container-search=yes&function-main=$function");
}
function add_container_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_main=$_GET["function-main"];
    echo $tpl->search_block($page,null,null,null,"&add-container-results=yes&function-main=$function_main");
    return true;
}


function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $DockerAdmin=$users->AsDockerAdmin;

    if(!$DockerAdmin){
        $b=$tpl->button_wiki("https://wiki.articatech.com/en/reverse-proxy/docker-edition/administration/privileges");
        echo $tpl->div_error("{docker_no_privs}<div style='text-align:right;margin-right:50px'>$b</div>");
        return true;
    }

    echo $tpl->search_block($page);
    return true;
}
function search():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $users=new usersMenus();
	$t=time();
    $function_main=$_GET["function"];
    $topbuttons=array();
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {containers}";
    $TINY_ARRAY["ICO"]=ico_containers;
    $TINY_ARRAY["EXPL"]="{APP_DOCKER_EXPLAIN}";
    $search=null;
    if(isset($_GET["search"])){$search=$_GET["search"];}

    if($users->AsDockerAdmin) {
        $topbuttons[] = array("Loadjs('$page?add-container-js=yes&function=$function_main')", ico_plus, "{new_container}");
    }

    $DockerYachtInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerYachtInstalled"));
    if($DockerYachtInstalled==1){
        $DockerYachtPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerYachtPort"));
        if($DockerYachtPort>0){
            $SERVER_NAME=$_SERVER["SERVER_NAME"];
            $topbuttons[] = array("s_PopUpFull('http://$SERVER_NAME:$DockerYachtPort','1024','900');",ico_dashboard,"{APP_YACHT}");

        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $results=$q->QUERY_SQL("SELECT ID FROM frontends");
    foreach ($results as $ligne){
            $frontends[]=$ligne["ID"];
    }



    $dock=new dockerd();
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";





    $DockerContainersStats=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerContainersStats"));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{networks}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{cpu}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{memory}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>bash</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{action}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{image}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{build2}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();


    $tooltips["paused"]="<label class='label label-warning'>{paused}</label>";
    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["running"]="<label class='label label-primary'>{running}</label>";
    $tooltips["exporting"]="<label class='label label-danger'>{exporting}</label>";
    $dock=new dockerd();

    $client = new DockerClient();
    $dockerContainers  = $client->dispatchCommand('/containers/json?all=true&size=true');

    foreach ($dockerContainers as $json) {

        if($search<>null){
            if(!preg_match("#$search#i",serialize($json))){
                continue;
            }
        }
        $ID=$json["Id"];

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(json_encode($json));
       // $json=json_decode($line);

        $time=$json["Created"];


        $Image=$json["Image"];
        if(preg_match("#^sha256:#",$Image)){
            $Image=$dock->GetImageName($Image);
        }

        $ImageEnc=urlencode($Image);
        $Labels=$json["Labels"];
        $Mounts=$json["Mounts"];
        $xnames=array();
        foreach ($json["Names"] as $name){
            if(substr($name,0,1)=="/"){
                $name=substr($name,1,strlen($name));
            }
            $xnames[]=$name;
        }

        $Names=@implode(", ",$xnames);
        $Networks=$json["NetworkSettings"]["Networks"];
        $Ports=table_row_port($json);
        $Size=$json["SizeRw"];
        $State=$json["State"];
        $Status=$json["Status"];
        $labels=$dock->GetContainerLabels($ID);
       $zDate=$tpl->time_to_date($time,true);

        $Name_Encoded=urlencode($Names);
        if($State=="running"){

            $action=$tpl->button_inline("{stop}",
                "Loadjs('$page?stop-container=$Name_Encoded&ID=$ID&function-main=$function_main')",
                ico_stop,"AsDockerAdmin",0,"btn-danger","small");

        }else{

            if($State=="paused"){
                $action=$tpl->button_inline("{start}",
                    "Loadjs('$page?unpause-container=$Name_Encoded&ID=$ID&function-main=$function_main')",
                    ico_run,"AsDockerAdmin",0,"btn-warning","small");

            }else{
                $action=$tpl->button_inline("{start}",
                    "Loadjs('$page?start-container=$Name_Encoded&ID=$ID&function-main=$function_main')",
                    ico_run,"AsDockerAdmin",0,"btn-primary","small");
            }



        }
        $compile=$tpl->icon_download("Loadjs('$page?compile-container=$ID&name=$Name_Encoded&function-main=$function_main')","AsDockerAdmin");

        if(!is_file("/etc/init.d/dock-container-$ID")){
            $bash=$tpl->icon_cd("Loadjs('$page?shellinaboxd-install=$Name_Encoded&ID=$ID&function-main=$function_main')");

        }else{
            $bash=$tpl->icon_bash("s_PopUp('/$ID/','1024','768')");
        }
        $cpu=$tpl->icon_nothing();
        $MemPerc=$tpl->icon_nothing();
        $MemUsage=$tpl->icon_nothing();
        if(isset($DockerContainersStats[$ID])){
            $cpu=$DockerContainersStats[$ID]["CPUPerc"]."%";
            $MemPerc=$DockerContainersStats[$ID]["MemPerc"]."%";
            $MemUsage=$DockerContainersStats[$ID]["MemUsage"];


        }


        $buton=$tpl->icon_delete("Loadjs('$page?delete-container-js=$Name_Encoded&ID=$ID&function-main=$function_main&md=$md')","AsDockerAdmin");



        $Image=$tpl->td_href($Image,null,$dock->GetImageLinkJS($Image));
        $Names=$tpl->td_href($Names,null,"Loadjs('$page?info-container=$ID&function-main=$function_main')");

        $export=$tpl->button_inline("{export}",
            "Loadjs('$page?export-container=$Name_Encoded&ID=$ID&function-main=$function_main')",
            ico_export,"AsDockerAdmin",0,"btn-primary","small");

        $isexport=$dock->isTaskExport($ID);

        if($isexport==1){
            $State="exporting";
            $Status="<i class='fa-duotone fa-arrows-rotate fa-spin'></i>";
            $export=$tpl->icon_nothing();
        }

        $GetContainerNetworks=row_container($dock->GetContainerNetworks($ID));
        $xstate=$tooltips[$State];
        if($Ports<>null){$Ports="($Ports)";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'>$xstate</td>";
        $html[]="<td width='1%' nowrap>$Status</td>";
        $html[]="<td nowrap><strong>$Names $Ports</strong></td>";
        $html[]="<td width='1%' nowrap>$GetContainerNetworks</td>";
        $html[]="<td width='1%' nowrap>$cpu</td>";
        $html[]="<td width='1%' nowrap>$MemPerc</td>";
        $html[]="<td width='1%' nowrap>$MemUsage</td>";
        $html[]="<td width='1%' nowrap>$bash</td>";
        $html[]="<td width='1%' nowrap>$action</td>";
        $html[]="<td width='1%' nowrap>$export</td>";
        $html[]="<td nowrap><i class='".ico_cd."'></i>&nbsp;$Image</td>";
        $html[]="<td width='1%' nowrap>$compile</td>";
        $html[]="<td width='1%' style='text-align:right' nowrap>$zDate</td>";
        $html[]="<td width='1%' class='center' nowrap>$buton</td>";
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
function table_row_port($array):string{
    if(!isset($array["Ports"])){return "";}
    $main=array();
    foreach ($array["Ports"] as $ports){
        $IP=null;
        $Type=$ports["Type"];
        if(isset($ports["IP"])) {
            $IP = $ports["IP"];
        }
        if(!is_null($IP)) {
            if ($IP == "0.0.0.0") {
                $IP = "*";
            }
        }
        $PrivatePort=$ports["PrivatePort"];
        if(isset($ports["PublicPort"])){
            $PublicPort=$ports["PublicPort"];
            $sline="$IP:$PublicPort <i class='fa-solid fa-arrow-right-to-bracket'></i> $PrivatePort";
            if(!isset($main[$sline])) {
                $main[$sline] = $Type;
            }else{
                $main[$sline] = $main[$sline]."|$Type";
            }
            continue;
        }
        $sline="$PrivatePort";
        if(!isset($main[$sline])) {
            $main[$sline] = $Type;
        }else{
            $main[$sline] = $main[$sline]."|$Type";
        }
    }
    $f=array();
    foreach ($main as $line=>$types){
        $f[]="$line $types";
    }
    return @implode(", ",$f);

}

function row_container($array):string{
    if(count($array)==0){return "-";}
    $f[]="<table>";

    foreach ($array as $NetworkID=>$ligne){

        $IPAddress=$ligne["IPAddress"];
        if($IPAddress==null){continue;}

    $f[]="<tr>";
    $f[]="<td width='1%'><i class='".ico_networks."'></i></td>";
    $f[]="<td>$IPAddress</td>";
    $f[]="</tr>";
    
    }
    $f[]="</table>";
    return @implode("",$f);

}

function shellinaboxd_install():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Name=$_GET["shellinaboxd-install"];
    $function_main=$_GET["function-main"];
    $data=base64_encode(serialize($_GET));
    return $tpl->js_confirm_execute("{ask_install_web_console}: $Name","shellinaboxd-install",$data,"$function_main()");
}
function shellinaboxd_install_perform():bool{
    $data=unserialize(base64_decode($_POST["shellinaboxd-install"]));

    $ID=$data["ID"];
    if($ID==null){
        echo "ID is Null\n{$_POST["shellinaboxd-install"]}";
        return false;
    }
    $Name=unserialize($data["shellinaboxd-install"]);
    admin_tracks("Installing ShellInaBox for docker instance $ID/$Name");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?shellinabox-install=$ID");
    return true;
}


function services_status(){
    $tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
    $status_file=PROGRESS_DIR."/dockerd.status";
    $t=intval($_GET["service-status"]);
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?status=yes");
	$ini->loadFile($status_file);

    $jsrestart=$tpl->framework_buildjs("docker.php?restart=yes",
        "docker.progress","docker.progress.logs",
        "progress-docker-restart","LoadAjaxTiny('$t-status','$page?service-status=$t');",null,null,"AsDockerAdmin");


    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DOCKER",$jsrestart);

	
	echo $tpl->_ENGINE_parse_body($html);
}

function services_toolbox():bool{
return false;
}
function add_container_search_results():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_main=$_GET["function-main"];
    $search=$_GET["search"];
    $search=str_replace("*",".*?",$search);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?list-images=yes");
    $results=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerImagesList"));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{image}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>TAG</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();
    foreach ($results as $Name=>$line){
        if(preg_match("#sha256:#",$Name)){continue;}
        if($search<>null){
            if(!preg_match("#$search#i",$Name)){continue;}
        }
        $Containers=$line["Containers"];
        $CreatedAt=strtotime($line["CreatedAt"]);
        $CreatedSince=$line["CreatedSince"];
        $Digest=$line["Digest"];
        $ID=$line["ID"];
        $Size=$line["Size"];
        $Tag=$line["Tag"];
//        $UniqueSize=$line["UniqueSize"];
//        $VirtualSize=$line["VirtualSize"];

        $zdate=$tpl->time_to_date($CreatedAt);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($line));

        $delval=urlencode("$ID");
        $link=$tpl->icon_add("BootstrapDialog1.close();Loadjs('$page?link-container-js=$delval&function-main=$function_main')");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><i class='".ico_archive."'></i></td>";
        $html[]="<td width='99%' nowrap><strong>$Name</strong></td>";
        $html[]="<td width='1%' class='center' nowrap><strong>$Size</strong></td>";
        $html[]="<td width='1%' class='center' nowrap><strong>$Tag</strong></td>";
        $html[]="<td width='1%' class='center' nowrap>$link</td>";
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





