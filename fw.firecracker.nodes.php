<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
    include_once('ressources/class.firecracker.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}
    if(isset($_GET["allrows"])){RefreshTableRows();exit;}
    if(isset($_GET["td-row"])){td_row();exit;}
    if(isset($_GET["add-container-js"])){add_container_js();exit;}
    if(isset($_GET["add-container-popup"])){add_container_popup();exit;}
    if(isset($_POST["add-container"])){add_container_save();exit;}
    if(isset($_POST["download-container"])){add_container_choose_perform();exit;}

    if(isset($_GET["restore-image-js"])){restore_image_js();exit;}
    if(isset($_POST["restore-image-js"])){restore_image_perform();exit;}


    if(isset($_GET["delete-container-js"])){delete_container_js();exit;}
    if(isset($_POST["delete-container"])){delete_container_perform();exit;}

    if(isset($_GET["webshell-start"])){webshell_start();exit;}
    if(isset($_GET["webshell-action"])){webshell_action();exit;}
    if(isset($_GET["webshell-status"])){webshell_status();exit;}

    if(isset($_GET["mysql-start"])){mysql_start();exit;}
    if(isset($_GET["mysql-status"])){mysql_status();exit;}
    if(isset($_GET["mysql-action"])){mysql_action();exit;}


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

    if(isset($_GET["firecracker-name-js"])){container_name_js();exit;}
    if(isset($_GET["firecracker-name-popup"])){container_name_popup();exit;}
    if(isset($_POST["firecracker-name"])){container_name_save();exit;}

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

function webshell_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $uuid=$_GET["webshell-start"];
    $html[]="<div id='webshell-progress-$uuid' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:350px'><div id='webshell-status-$uuid'></div></td>";
    $html[]="<td style='width:100%;padding-left:10px;vertical-align: top'><div id='webshell-action-$uuid'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('webshell-action-$uuid','$page?webshell-action=$uuid&function-main=$function');";
    $html[]="LoadAjax('webshell-status-$uuid','$page?webshell-status=$uuid&function-main=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function mysql_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $uuid=$_GET["mysql-start"];
    $html[]="<div id='mysql-progress-$uuid' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:350px'><div id='mysql-status-$uuid'></div></td>";
    $html[]="<td style='width:100%;padding-left:10px;vertical-align: top'><div id='mysql-action-$uuid'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('mysql-action-$uuid','$page?mysql-action=$uuid&function-main=$function');";
    $html[]="LoadAjax('mysql-status-$uuid','$page?mysql-status=$uuid&function-main=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function webshell_action():bool{
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $uuid=$_GET["webshell-action"];
    $tpl=new template_admin();
    $EnableShellInABox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShellInABox$uuid"));
    $html[]="<p style='font-size:16px;margin-bottom:30px'>{SSHOnlyShellInaBox_explain}</p>";
    $html[]="<div style='margin:10px;' class=center>";
    if($EnableShellInABox==0){
        $jsinstall=$tpl->framework_buildjs(
            "firecrack:/firecracker/webshell/install/$uuid",
            "shellinabox.install.$uuid",
            "shellinabox.install.$uuid.log",
            "webshell-progress-$uuid",
            "LoadAjax('webshell-action-$uuid','$page?webshell-action=$uuid&function-main=$function');LoadAjax('webshell-status-$uuid','$page?webshell-status=$uuid&function-main=$function');$function()"
        );
        $html[]=$tpl->button_autnonome("{install} {system_console}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);

    }else{
        $jsinstall=$tpl->framework_buildjs(
            "firecrack:/firecracker/webshell/uninstall/$uuid",
            "shellinabox.install.$uuid",
            "shellinabox.install.$uuid.log",
            "webshell-progress-$uuid",
            "LoadAjax('webshell-action-$uuid','$page?webshell-action=$uuid&function-main=$function');LoadAjax('webshell-status-$uuid','$page?webshell-status=$uuid&function-main=$function');$function()"
        );
        $html[]=$tpl->button_autnonome("{uninstall} {system_console}",$jsinstall,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
    }
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function webshell_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function-main"];
    $uuid=$_GET["webshell-status"];

    $sock=new sockets();
    
    $json=json_decode($sock->REST_API_FIRECR("/firecracker/webshell/status/$uuid"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_SHELLINABOX_$uuid", $jsRestart));
    return true;
}
function mysql_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=$_GET["function-main"];
    $uuid=$_GET["mysql-status"];

    $sock=new firecracker($uuid);
    $json=json_decode($sock->REST_API_FIRECR("/harmp/mariadb/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_MYSQL", $jsRestart));
    return true;
}
function mysql_action(){
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $uuid=$_GET["mysql-action"];
    $tpl=new template_admin();
}
function delete_container_js():bool{
    $tpl=new template_admin();
    $uuid=$_GET["delete-container-js"];
    $md=$_GET["md"];
    $deletejs=$tpl->framework_buildjs(
        "firecrack:/firecracker/jail/remove/$uuid",
        "docker.rm.$uuid.progress",
        "docker.rm.$uuid.log",
        "progress-firecracker-containers",
        "$('#$md').remove()",null,null,"AsDockerAdmin"

    );
    return $tpl->js_confirm_delete("{remove} {container} $uuid","delete-container",$uuid,$deletejs);
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
    return $tpl->js_dialog1("{export} {$_GET["export-container"]}","$page?export-container-popup=$nameec&function-main=$function",540);
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
    $AfTer="LoadAjax('$id','$page?export-container-done=$nameec&function-main=$function')";
    $js=$tpl->framework_buildjs(
        "docker.php?export-container=$nameec",
        "docker.export.$ID",
        "docker.export.$ID.log",$id,
        $AfTer

    );

    $tdir="/home/firecracker-export";
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
    $tdir="/home/firecracker-export";
    $tfile="$tdir/$ID";
    $size=filesize($tfile);
    $size=FormatBytes($size/1024);
    $js="document.location.href='/$page?export-container-download=$nameec&function-main=$function'";
    $html=$tpl->button_autnonome("{download} $Name ($size)",
        $js,ico_download,"AsDockerAdmin",501);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function export_container_download():bool{

    $Name=$_GET["export-container-download"];
    $ID=$_GET["ID"];
    $tdir="/home/firecracker-export";
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
    $uuid=$_GET["start-container"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API_FIRECR("/firecracker/jail/start/$uuid"));

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    return admin_tracks("Starting MicroVM container $uuid..");
}
function info_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uuid=$_GET["info-container"];
    $firecracker=new firecracker();
    $Names=$firecracker->GetContainerName($uuid);
    $function_main=$_GET["function-main"];
    return $tpl->js_dialog($Names,"$page?info-container-tab=$uuid&function-main=$function_main");
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
    $uuid=$_GET["info-container-tab"];
    $function_main=$_GET["function-main"];

    $fire=new firecracker();
    $Names=$fire->GetContainerName($uuid);
    $array[$Names]="$page?info-container-popup=$uuid&function-main=$function_main";
    $array["WebShell"]="$page?webshell-start=$uuid&function-main=$function_main";
    $array["{APP_MYSQL}"]="$page?mysql-start=$uuid&function-main=$function_main";



  //  $array["{networks}"]="$page?info-container-network=$ID&function-main=$function_main";
   // $array["{labels}"]="$page?info-container-labels=$ID&function-main=$function_main";
   // $array["{mounts}"]="$page?info-container-mounts=$ID&function-main=$function_main";
   // $array["{details}"]="$page?info-container-details=$ID&function-main=$function_main";

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
    $html[]="<th  colspan='2'>{driver}</th>";
    $html[]="<th >{name}</th>";
    $html[]="<th  colspan='2'>{scope}</th>";
    $html[]="<th  width=1% nowrap>{network}</th>";
    $html[]="<th  width=1% nowrap>{gateway}</th>";
    $html[]="<th  width=1% nowrap>&nbsp;</th>";
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
        $html[]="<td $tdStyle1><li class='".ico_networks."'></li></td>";
        $html[]="<td $tdStyle1 nowrap>$Driver</td>";
        $html[]="<td nowrap><strong>$Name</strong></td>";
        $html[]="<td $tdStyle1 nowrap><i class=\"fa-solid fa-arrow-right-long-to-line\"></i></td>";
        $html[]="<td  nowrap $tdStyle1 nowrap>$Scope</td>";
        $html[]="<td  nowrap $tdStyle1 nowrap>{$MAIN["Subnet"]}</td>";
        $html[]="<td  nowrap $tdStyle1 nowrap>{$MAIN["Gateway"]}</td>";
        $html[]="<td $tdStyle1 class='center' nowrap>$buton</td>";
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
    $html[]="<th  colspan='2'>{container}</th>";
    $html[]="<th  nowrap>{ipaddr}</th>";
    $html[]="<th  nowrap>{mac}</th>";
    $html[]="<th  nowrap></th>";
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
        $unlink=$tpl->icon_unlink("Loadjs('$page?delete-network=$ContainerID&md=$md&function-main=$function')","AsDockerAdmin");

        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td $tdStyle1><i class='".ico_computer."'></i></td>";
        $html[] = "<td nowrap>$Name</td>";
        $html[] = "<td nowrap $tdStyle1 nowrap><strong>$IPAddress</strong></td>";
        $html[] = "<td $tdStyle1 nowrap>$MacAddress</td>";
        $html[] = "<td $tdStyle1 nowrap>$unlink</td>";
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
    $GET=unserializeb64($_POST["delete-network"]);
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
    $html[]="<th  colspan='2'>{source}</th>";
    $html[]="<th  nowrap>{destination}</th>";
    $html[]="<th  nowrap>{writeable}</th>";
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
        $html[]="<td $tdStyle1><li class='".ico_folder."'></li></td>";
        $html[]="<td $tdStyle1 nowrap>$Source</td>";
        $html[]="<td nowrap><strong>$Destination</strong></td>";
        if($RW) {
            $html[] = "<td $tdStyle1 nowrap><i class=\"" . ico_check . "\"></i></td>";
        }else{
            $html[] = "<td $tdStyle1 nowrap></td>";
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
    $uuid=$_GET["info-container-popup"];
    $function_main=$_GET["function-main"];

    echo "<div id='inspect-container-$uuid'></div>
    <script>LoadAjax('inspect-container-$uuid','$page?info-container-table=$uuid&function-main=$function_main');</script>
    ";
    return true;
}
function container_name_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["firecracker-name-js"];
    return $tpl->js_dialog2("{name}","$page?firecracker-name-popup=$ID&function-main=$function",500);
}
function container_name_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["firecracker-name-popup"];
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

    $form[]=$tpl->field_hidden("firecracker-name",$ID);
    $form[]=$tpl->field_text("name","{name}",$ImageName,true);
    $html[]=$tpl->form_outside("",
        $form,"","{apply}",@implode(";",$js),"AsDockerAdmin");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function container_name_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["firecracker-name"];
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

function restore_image_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uuid=$_GET["restore-image-js"];
    $image=$_GET["image"];
    $function_main=$_GET["function-main"];
    $action=$tpl->framework_buildjs("firecrack:/firecracker/image/restore/$uuid",
    "firecracker.restoreimage.$uuid",
    "firecracker.restoreimage.$uuid.log",
        "info-$uuid-progress",
    "LoadAjax('inspect-container-$uuid','$page?info-container-table=$uuid&function-main=$function_main');");

    return $tpl->js_confirm_execute("{restore} {image} $image","restore-image-js",$uuid,$action);

}
function restore_image_perform(){
    $uuid=$_POST["restore-image-js"];
    admin_tracks("Restore original system for MicroVM $uuid");
}

function info_container_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uuid=$_GET["info-container-table"];
    $function_main=$_GET["function-main"];
    $q=new lib_sqlite("/home/artica/SQLITE/firecracker.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID,machinename,created,enabled,MacAddr,cpu,image,mem,disk,storage,nic FROM firecracker WHERE uuid='$uuid'");
    $ID=intval($ligne["ID"]);


    $topbuttons[] = array("Loadjs('$page?restore-image-js=$uuid&image={$ligne["image"]}&function-main=$function_main');", ico_import, "{restore} {image}");
    echo "<div style='margin-top:10px' id='info-$uuid-progress'></div>";
    echo "<div style='margin-top:5px'>";
    echo $tpl->table_buttons($topbuttons);
    echo "</div>";


    $tpl->table_form_field_text("{uuid}",$uuid,ico_computer);

    $tpl->table_form_field_text("Command","<small style='text-transform: initial'>firecrack-daemon -start-microvm $uuid</small>",ico_run);


    $tpl->table_form_field_text("{mac}",$ligne["MacAddr"],ico_interface);
    $tpl->table_form_field_text("{image}",$ligne["image"],ico_cd);
    $tpl->table_form_field_js("Loadjs('$page?add-container-js=yes&function=$function_main&uuid=$uuid')");

    $tpl->table_form_field_text("{interface}",$ligne["nic"]."/fc$ID-tap",ico_nic);
    $tpl->table_form_field_text("{memory}","{$ligne["mem"]}MB",ico_mem);
    $tpl->table_form_field_text("{nb_cpus}","{$ligne["cpu"]}",ico_cpu);
    $tpl->table_form_field_text("{disksize}","{$ligne["disk"]}GB",ico_weight);


    $Status=$_SESSION["FIRECRACKER"][$uuid];
    if ($Status->pid>0) {
        $fire = new firecracker($uuid);
        $json=json_decode($fire->REST_API_FIRECR("/harmp/status"));
        foreach ($json->Disks as $hd){
            $tpl->table_form_field_text("{disk}","<span style='text-transform:none'>$hd</span>",ico_hd);
        }

    }

    echo $tpl->table_form_compile();
    return true;
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
    $APP_FIRECRACKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");

    $html=$tpl->page_header("{APP_FIRECRACKER} $APP_FIRECRACKER_VERSION &raquo;&raquo; {containers}",
        "fa-solid fa-layer-group",
        "{APP_FIRECRACKER_EXPLAIN}",
        "$page?table=yes","firecracker-containers","progress-firecracker-containers",false,"table-firecracker-containers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker containers",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function add_container_choose_perform(){
    admin_tracks("Downloading docker image {$_POST["download-container"]}");
}
function add_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $uuid=$_GET["uuid"];
    $title="{new_container}";
    if(strlen($uuid)>5){
        $fire=new firecracker();
        $title=$fire->GetContainerName($uuid);
    }
    return $tpl->js_dialog2($title,"$page?add-container-popup=yes&function-main=$function&uuid=$uuid");
}
function add_container_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $crack=new firecracker();
    $uuid=$_GET["uuid"];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_FIRECR("/switch/list"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $switchs=array();
    foreach ($json->switchs as $switch){
        $switchs[$switch]=$switch;
    }
    if( count($switchs)==0){
        echo $tpl->div_error("{error_no_virtual_switch}");
        return false;
    }


    $function_main=$_GET["function-main"];
    $title="{new_container}";
    $button="{create}";
    $form[]=$tpl->field_hidden("add-container","yes");
    if(strlen($uuid)>5){
        $form[]=$tpl->field_hidden("uuid",$uuid);
        $fire=new firecracker();
        $title=$fire->GetContainerName($uuid);
        $button="{apply}";
        $q=new lib_sqlite("/home/artica/SQLITE/firecracker.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID,machinename,created,enabled,MacAddr,cpu,image,mem,disk,storage,nic FROM firecracker WHERE uuid='$uuid'");
    }

    if(!isset($ligne["mem"])){
        $ligne["mem"]=150;
    }
    if(!isset($ligne["cpu"])){
        $ligne["cpu"]=2;
    }
    if(!isset($ligne["disk"])){
        $ligne["disk"]=5;
    }

    $form[]=$tpl->field_text("machinename","{container_name}",$ligne["machinename"],true);
    $form[]=$tpl->field_array_hash($crack->ImagesListNames(),"image","{image}",$ligne["image"],true);
    $form[]=$tpl->field_numeric("cpu","{nb_cpus}",$ligne["cpu"],true);
    $form[]=$tpl->field_numeric("mem","{memres_label} (MB)",$ligne["mem"],true);
    $form[]=$tpl->field_numeric("disk","{disksize} (GB)",$ligne["disk"],true);
    $form[]=$tpl->field_array_hash($switchs,"nic","{virtual_switch}",$ligne["nic"],true);
    $form[]=$tpl->field_hidden("storage","/home");

    $html[]=$tpl->form_outside($title,
        $form,"",$button,"dialogInstance2.close();$function_main();if(document.getElementById('inspect-container-$uuid') ){ LoadAjax('inspect-container-$uuid','$page?info-container-table=$uuid&function-main=$function_main');}","AsDockerAdmin");

    echo $tpl->_ENGINE_parse_body($html);
    
    return true;
}
function add_container_save():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $machinename=$_POST["machinename"];
    $_POST["machinename"]=str_replace("/","_",$machinename);
    if($_POST["image"]==null){
        echo $tpl->post_error("Image required");
        return false;
    }
    $nic=trim($_POST["nic"]);
    if(strlen($nic)<3){
        echo $tpl->post_error("Please, choose a Virtual Switch");
        return false;
    }
    if(strlen($_POST["uuid"])>0){
        $q=new lib_sqlite("/home/artica/SQLITE/firecracker.db");
        $q->QUERY_SQL("UPDATE firecracker SET 
                      cpu={$_POST["cpu"]},
                      mem={$_POST["mem"]},
                      disk={$_POST["disk"]},
                      nic='{$_POST["nic"]}' WHERE uuid='{$_POST["uuid"]}'");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        return admin_tracks_post("Update MicroNode $machinename machine");
    }

    $sock=new sockets();
    $Content=urlencode(base64_encode(serialize($_POST)));
    $json=json_decode($sock->REST_API_FIRECR("/firecracker/jail/create/$Content"));

    if (!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    return admin_tracks("Creating a new MicroNode $machinename");

}


function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
    return true;
}

function lablel_action($json){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$json->id;
    if($json->pid==0){
        return $tpl->icon_run("Loadjs('$page?start-container=$id')","AsDockerAdmin");
    }

    return $tpl->icon_stop("Loadjs('$page?stop-container=$id')");


}



function search():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $users=new usersMenus();
	$t=time();
    $function_main=$_GET["function"];
    $topbuttons=array();

    $APP_FIRECRACKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_FIRECRACKER} $APP_FIRECRACKER_VERSION &raquo;&raquo; {containers}";
    $TINY_ARRAY["ICO"]="fa-solid fa-layer-group";
    $TINY_ARRAY["EXPL"]="{APP_FIRECRACKER_EXPLAIN}";
    $search=null;
    if(isset($_GET["search"])){$search=$_GET["search"];}

    if($users->AsDockerAdmin) {
        $topbuttons[] = array("Loadjs('$page?add-container-js=yes&function=$function_main')", ico_plus, "{new_container}");
    }



    $q=new lib_sqlite("/home/artica/SQLITE/firecracker.db");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table id='table-firecracker-containers' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{status}</th>";
    $html[]="<th>PID</th>";
    $html[]="<th>{name}</th>";
    $html[]="<th>{AgenVersion}</th>";
    $html[]="<th>{uptime}</th>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{cpu}</th>";
    $html[]="<th>{memory}</th>";
    $html[]="<th>bash</th>";
    $html[]="<th>{action}</th>";
    $html[]="<th style='width:1%' nowrap>{created}</th>";
    $html[]="<th style='width:1%' nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();


    $tooltips["paused"]="<label class='label label-warning'>{paused}</label>";
    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["running"]="<label class='label label-primary'>{running}</label>";
    $tooltips["exporting"]="<label class='label label-danger'>{exporting}</label>";
    $tdStyle1="style='width:1%' nowrap";
    $sock=new sockets();
    $results=$q->QUERY_SQL("SELECT * FROM firecracker ORDER BY machinename");

    foreach ($results as $index=>$ligne) {

        if($search<>null){
            if(!preg_match("#$search#i",serialize($ligne))){
                continue;
            }
        }
        $uuid=$ligne["uuid"];
        $machinename=$ligne["machinename"];
        $created=$tpl->time_to_date($ligne["created"]);
        $EnableShellInABox=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableShellInABox$uuid"));
        $json=json_decode($sock->REST_API_FIRECR("/firecracker/jail/status/$uuid"));

        $ipaddr=$ligne["ipaddr"];
        $MemUsage=$json->mem_size_mib."M";
        $Pid=$tpl->icon_nothing();
        if($json->pid>0){
            $Pid=$json->pid;
        }
        $action=lablel_action($json);

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));


        $compile=$tpl->icon_download("Loadjs('$page?compile-container=$ID&name=$uuid&function-main=$function_main')","AsDockerAdmin");

        if($EnableShellInABox==0){
            $bash=$tpl->icon_bash();

        }else{
            $bash=$tpl->icon_bash("s_PopUp('/firessh/$uuid/','1024','768')");
        }
        $cpu=$tpl->icon_nothing();
        $MemPerc=$tpl->icon_nothing();


        $delete=$tpl->icon_delete("Loadjs('$page?delete-container-js=$uuid&md=$md')","AsDockerAdmin");

        $machinename=$tpl->td_href($machinename,null,"Loadjs('$page?info-container=$uuid&function-main=$function_main')");

        $export=$tpl->button_inline("{export}",
            "Loadjs('$page?export-container=$machinename&function-main=$function_main')",
            ico_export,"AsDockerAdmin",0,"btn-primary","small");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdStyle1><span id='firecracker-state-$uuid'></span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='firecracker-pid-$uuid'></span></td>";
        $html[]="<td nowrap><strong>$machinename</strong></td>";
        $html[]="<td $tdStyle1 nowrap><span id='firecracker-agver-$uuid'>-</span></td>";
        $html[]="<td nowrap><span id='firecracker-uptime-$uuid'></span></td>";
        $html[]="<td $tdStyle1 nowrap>$ipaddr</td>";
        $html[]="<td $tdStyle1 nowrap><span id='firecracker-cpu-$uuid'>$cpu</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='firecracker-memory-$uuid'></span></td>";
        $html[]="<td $tdStyle1 nowrap>$bash</td>";
        $html[]="<td $tdStyle1 nowrap><span id='firecracker-action-$uuid'></span></td>";
        $html[]="<td $tdStyle1 style='text-align:right' nowrap>$created</td>";
        $html[]="<td $tdStyle1 class='center' nowrap>$delete</td>";
        $html[]="</tr>";



    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="Loadjs('$page?allrows=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function td_memory($uuid):string{
    $Status=$_SESSION["FIRECRACKER"][$uuid];
    if(!$Status->Status){
        return "-";
    }
    if ($Status->pid==0){
        return "-";
    }
    return FormatBytes($Status->memoryKb);
}
function td_button_action($uuid):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Status=$_SESSION["FIRECRACKER"][$uuid];
    $ProgressF = "firecracker.start.$uuid";
    $ProgressS= "firecracker.stop.$uuid";
    $jsStart=$tpl->framework_buildjs("firecrack:/firecracker/jail/start/$uuid",$ProgressF,
        "$ProgressF.log","progress-firecracker-containers");

    $jsStop=$tpl->framework_buildjs("firecrack:/firecracker/jail/stop/$uuid",$ProgressS,
        "$ProgressF.log","progress-firecracker-containers");

    if(!$Status->Status){
        if($Status->state=="stopped") {
            return $tpl->icon_run($jsStart);
        }
        if ($Status->pid>0){
            return $tpl->icon_stop($jsStop);
        }

        return  $tpl->icon_run($jsStart);
    }

    if ($Status->pid>0){
        if($Status->state=="not started") {
            return "<label class='label label-default'>{stopped}</label>";
        }
    }

    if ($Status->state=="running") {
        return $tpl->icon_stop($jsStop);
    }
    return "<label class='label label-default'>{unknown}</label>";
}
function td_pid($uuid):string{
    $Status=$_SESSION["FIRECRACKER"][$uuid];
    if ($Status->pid>0){
        return "<strong>$Status->pid</strong>";
    }
    return "-";
}
function td_uptime($uuid){
    $Status=$_SESSION["FIRECRACKER"][$uuid];
    if(!$Status->Status){
        return "-";
    }
    if(strlen($Status->uptime)<3){
        return "-";
    }
    return $Status->uptime;
}
function td_status($uuid):string{
    $Status=$_SESSION["FIRECRACKER"][$uuid];

    if($Status->starting){
        return "<label class='label label-warning'>{starting}...</label>";
    }

    if(!$Status->Status){
        if($Status->state=="stopped") {
            return "<label class='label label-danger'>{stopped}</label>";
        }
        return "<label class='label label-danger'>{error}</label>";
    }

    if($Status->stopping){
        return "<label class='label label-warning'>{stopping}</label>";
    }

    if ($Status->pid>0){
        if($Status->state=="not started") {
            return "<label class='label label-warning'>{stopped}</label>";
        }
    }


    if ($Status->state=="running") {
        return "<label class='label label-primary'>{running}</label>";
    }

    return "<label class='label label-default'>{unknown}</label>";

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
    $f[]="<td $tdStyle1><i class='".ico_networks."'></i></td>";
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
        "progress-firecracker-restart","LoadAjaxTiny('$t-status','$page?service-status=$t');",null,null,"AsDockerAdmin");


    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DOCKER",$jsrestart);

	
	echo $tpl->_ENGINE_parse_body($html);
}

function services_toolbox():bool{
return false;
}



function RefreshTableRows(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="function ContainersAllRows(){";
    $f[]="\tif (!document.getElementById('table-firecracker-containers') ){";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tLoadjs('$page?allrows=yes')";
    $f[]="}";
    $f[]="function extractUUID(str) {";
    $f[]="\tconst regex = /[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i;";
    $f[]="\tconst match = str.match(regex);";
    $f[]="\treturn match ? match[0] : null;";
    $f[]="}";
    $f[]="";
    $f[]="function RefreshTableRows(){";
    $f[]="\tif (!document.getElementById('table-firecracker-containers') ){";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tvar i=0;";
    $f[]="\t$(\"span[id^='firecracker-state-']\").each(function() {";
    $f[]="\t\tvar id = $(this).attr('id');";
    $f[]="\t\tvar number = extractUUID(id)";
    $f[]="\t\tif (!document.getElementById('table-firecracker-containers') ){";
    $f[]="\t\t\treturn;";
    $f[]="\t\t}";
    $f[]="\t\ti++;";
    $f[]="\t\txTime=i*1000";
    $f[]="\t\tsetTimeout(() => { Loadjs('$page?td-row='+number); }, xTime);";
    $f[]="\t});";
    $f[]="\tsetTimeout(\"ContainersAllRows()\",6000);";
    $f[]="}";
    $f[]="\tRefreshTableRows();";
    echo @implode("\n", $f);
}
function td_row():bool{
    $tpl=new template_admin();
    $uuid=$_GET["td-row"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API_FIRECR("/firecracker/jail/status/$uuid"));
    $_SESSION["FIRECRACKER"][$uuid]=$json;
    $Status=base64_encode($tpl->_ENGINE_parse_body(td_status($uuid)));
    $Memory=base64_encode($tpl->_ENGINE_parse_body(td_memory($uuid)));
    $action=base64_encode($tpl->_ENGINE_parse_body(td_button_action($uuid)));
    $pid=base64_encode($tpl->_ENGINE_parse_body(td_pid($uuid)));
    $uptime=base64_encode($tpl->_ENGINE_parse_body(td_uptime($uuid)));
    list($Cpus,$agver)=td_Cpus($uuid);



    $doc="document.getElementById";
    $innerHTML="innerHTML=base64_decode";

    header("content-type: application/x-javascript");
    $ID=md5($uuid);
    $f[]="function NodeRow$ID(){";
    $f[]="\tif (!$doc('firecracker-state-$uuid') ){";
    $f[]="//alert('firecracker-state-$uuid')";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\t$doc('firecracker-state-$uuid').$innerHTML('$Status');";
    $f[]="\t$doc('firecracker-memory-$uuid').$innerHTML('$Memory');";
    $f[]="\t$doc('firecracker-cpu-$uuid').$innerHTML('$Cpus');";
    $f[]="\t$doc('firecracker-action-$uuid').$innerHTML('$action');";
    $f[]="\t$doc('firecracker-pid-$uuid').$innerHTML('$pid');";
    $f[]="\t$doc('firecracker-uptime-$uuid').$innerHTML('$uptime');";
    $f[]="\t$doc('firecracker-agver-$uuid').$innerHTML('$agver');";

    $f[]="}";
    $f[]="NodeRow$ID()";
    echo @implode("\n", $f);
    return true;

}

function td_Cpus($uuid):array{

    $Status=$_SESSION["FIRECRACKER"][$uuid];
    if(!$Status->Status){
        return array(base64_encode("-"),base64_encode("-"));
    }

    $tpl=new template_admin();
    $fire=new firecracker($uuid);
    $json=json_decode($fire->REST_API_FIRECR("/harmp/status"));

    $Cpus=base64_encode($json->CpuPourc."%");
    $Agver=base64_encode($json->Version);
    return array("$Cpus","$Agver");


}
