<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
    include_once('ressources/class.firecracker.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}

    if(isset($_POST["createuser"])){info_containers_members_create_perform();exit;}
    if(isset($_POST["serviceid"])){add_container_save();exit;}
    if(isset($_POST["download-container"])){add_container_choose_perform();exit;}

    if(isset($_GET["allrows"])){RefreshTableRows();exit;}
    if(isset($_GET["td-row"])){td_row();exit;}
    if(isset($_GET["add-container-js"])){add_container_js();exit;}
    if(isset($_GET["add-container-popup"])){add_container_popup();exit;}


    if(isset($_GET["restore-image-js"])){restore_image_js();exit;}
    if(isset($_POST["restore-image-js"])){restore_image_perform();exit;}


    if(isset($_GET["delete-container-js"])){delete_container_js();exit;}
    if(isset($_POST["delete-container"])){delete_container_perform();exit;}



    if(isset($_GET["mysql-start"])){mysql_start();exit;}
    if(isset($_GET["mysql-status"])){mysql_status();exit;}
    if(isset($_GET["mysql-action"])){mysql_action();exit;}


    if(isset($_GET["link-container-js"])){link_container_js();exit;}
    if(isset($_GET["link-container-popup"])){link_container_popup();exit;}
    if(isset($_POST["link-image"])){link_container_save();exit;}

    if(isset($_GET["stop-container"])){stop_container();exit;}
    if(isset($_GET["start-container"])){start_container();exit;}
    if(isset($_GET["restart-container-js"])){restart_container();exit;}

    if(isset($_GET["unpause-container"])){unpause_container();exit;}


    if(isset($_GET["info-container"])){info_container_js();exit;}
    if(isset($_GET["info-container-popup"])){info_container_popup();exit;}
    if(isset($_GET["info-container-table"])){info_container_table();exit;}
    if(isset($_GET["info-container-tab"])){info_container_tab();exit;}
    if(isset($_GET["info-container-mounts"])){info_container_mounts();exit;}
    if(isset($_GET["info-container-members"])){info_container_members();exit;}
    if(isset($_GET["info-container-members-search"])){info_container_members_search();exit;}
    if(isset($_GET["info-container-members-buttons"])){info_container_members_buttons();exit;}
    if(isset($_GET["info-containers-members-import-js"])){info_containers_members_import_js();exit;}
    if(isset($_GET["info-containers-members-import-form"])){info_containers_members_import_form();exit;}
    if(isset($_GET["info-containers-members-import-search"])){info_containers_members_import_search();exit;}
    if(isset($_GET["info-containers-members-import-link"])){info_containers_members_import_link();exit;}


    if(isset($_GET["info-containers-members-create-js"])){info_containers_members_create_js();exit;}
    if(isset($_GET["info-containers-members-create-form"])){info_containers_members_create_form();exit;}

    if(isset($_GET["info-containers-members-import-pass"])){info_containers_members_import_pass();exit;}
    if(isset($_GET["info-container-labels"])){info_container_labels();exit;}
    if(isset($_GET["info-container-labels-table"])){info_container_labels_table();exit;}

    if(isset($_GET["info-container-network"])){info_container_network();exit;}
    if(isset($_GET["info-container-network-table"])){info_container_network_table();exit;}
    if(isset($_GET["delete-network"])){delete_container_network();exit;}
    if(isset($_POST["delete-network"])){delete_container_network_perform();exit;}
    if(isset($_GET["network-connect"])){connect_container_js();exit;}
    if(isset($_GET["network-connect-perform"])){connect_container_perform();exit;}

    if(isset($_GET["info-container-details"])){info_container_details();exit;}


    if(isset($_GET["samba-name-js"])){container_name_js();exit;}
    if(isset($_GET["samba-name-popup"])){container_name_popup();exit;}
    if(isset($_POST["samba-name"])){container_name_save();exit;}


    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["service-status"])){services_status();exit;}



	
page();


function mysql_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $serviceid=$_GET["mysql-start"];
    $html[]="<div id='mysql-progress-$serviceid' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:350px'><div id='mysql-status-$serviceid'></div></td>";
    $html[]="<td style='width:100%;padding-left:10px;vertical-align: top'><div id='mysql-action-$serviceid'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('mysql-action-$serviceid','$page?mysql-action=$serviceid&function-main=$function');";
    $html[]="LoadAjax('mysql-status-$serviceid','$page?mysql-status=$serviceid&function-main=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function delete_container_js():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["delete-container-js"];
    $md=$_GET["md"];
    $deletejs=$tpl->framework_buildjs(
        "firecrack:/firecracker/jail/remove/$serviceid",
        "docker.rm.$serviceid.progress",
        "docker.rm.$serviceid.log",
        "progress-samba-containers",
        "$('#$md').remove()",null,null,"AsSambaAdministrator"

    );
    return $tpl->js_confirm_delete("{remove} {container} $serviceid","delete-container",$serviceid,$deletejs);
}
function delete_container_perform():bool{
    $image=$_POST["delete-container"];
    return admin_tracks("Remove docker container $image");
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
    $serviceid=$_GET["start-container"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API_SAMBA("/instance/$serviceid/start"));

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    return admin_tracks("Starting File Sharing instance $serviceid..");
}
function restart_container():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["restart-container-js"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API_SAMBA("/instance/$serviceid/restart"));

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    return admin_tracks("Restarting File Sharing instance $serviceid..");
}
function stop_container():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["stop-container"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API_SAMBA("/instance/$serviceid/stop"));

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    return admin_tracks("Stopping File Sharing instance $serviceid..");
}
function GetClass($serviceid){
    $sock=new sockets();
    $json=json_decode($sock->REST_API_SAMBA("/instances/status"));
    $zclass["serviceName"]="Unknown";
    $zclass["id"]=$serviceid;
    $zclass["serviceType"]=0;
    $zclass["interface"]="Unknown";
    $zclass["enabled"]=0;
    $zclass["workgroup"]="workgroup";
    $zclass["homes"]=0;
    $zclass["status"]["running"]=false;
    $zclass["status"]["pid"]=0;
    $zclass["status"]["memory"]=0;
    $zclass["status"]["processesNumber"]=0;
    $zclass["status"]["ttl"]="";

    if(!$json->Status){
        return json_encode($zclass);
    }


    foreach ($json->instances as $id=>$class) {
        if($id==$serviceid){
            return $class;
        }
    }

    return json_encode($zclass);
}

function info_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=$_GET["info-container"];
    $Class=GetClass($serviceid);

    $Names=$Class->serviceName;
    $function_main=$_GET["function-main"];
    return $tpl->js_dialog($Names,"$page?info-container-tab=$serviceid&function-main=$function_main");
}


function info_container_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=$_GET["info-container-tab"];
    $function_main=$_GET["function-main"];
    $Class=GetClass($serviceid);

    $array[$Class->serviceName]="$page?info-container-popup=$serviceid&function-main=$function_main";

    if($Class->serviceType==0){
        $array["{members}"]="$page?info-container-members=$serviceid&function-main=$function_main";

    }

    echo $tpl->tabs_default($array);
    return true;
}
function info_container_members():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $serviceid=$_GET["info-container-members"];
    echo "<div id='info-container-members-$serviceid' style='margin-bottom:10px;margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&info-container-members-search=$serviceid&function-main=$function_main");
    return true;
}
function info_container_members_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=$_GET["info-container-members-buttons"];
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?info-containers-members-import-js=$serviceid&function=$function');", ico_link, "{link_members}");
    $topbuttons[] = array("Loadjs('$page?info-containers-members-create-js=$serviceid&function=$function');", ico_plus, "{new_member}");
    echo $tpl->th_buttons($topbuttons);
    return true;
}
function info_containers_members_import_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=$_GET["info-containers-members-import-js"];
    $function=$_GET["function"];
    return $tpl->js_dialog5("{link_members}","$page?info-containers-members-import-form=$serviceid&function=$function",500);
}
function info_containers_members_import_link():bool{
    $member=$_GET["info-containers-members-import-link"];
    $memberenc=urlencode($member);
    $page=CurrentPageName();
    $serviceid=$_GET["serviceid"];
    $function=$_GET["function"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    return $tpl->js_dialog6($member,"$page?info-containers-members-import-pass=$memberenc&serviceid=$serviceid&function=$function&md=$md",500);
}
function info_containers_members_import_pass():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=$_GET["serviceid"];
    $function=$_GET["function"];
    $member=$_GET["info-containers-members-import-pass"];
    $md=$_GET["md"];
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_hidden("linkuser",$member);
    $form[]=$tpl->field_password2("password","");
    $js[]="$('#$md').remove()";
    $js[]="$function();";
    $js[]="dialogInstance6.close();";


    $html[]=$tpl->form_outside("", $form,null,"{link}",@implode(";",$js),"AsSambaAdministrator",false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function info_containers_members_create_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=$_GET["info-containers-members-create-js"];
    $function=$_GET["function"];
    return $tpl->js_dialog5("{new_member}","$page?info-containers-members-create-form=$serviceid&function=$function",500);
}
function info_containers_members_create_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=$_GET["info-containers-members-create-form"];
    $function=$_GET["function"];
    $js[]="dialogInstance5.close();";
    $js[]="$function();";
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("createuser","{username}","",true);
    $form[]=$tpl->field_password2("password","{password}","",true);
    $html[]=$tpl->form_outside("", $form,null,"{create}",@implode(";",$js),"AsSambaAdministrator",false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function info_containers_members_create_perform():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $serviceid=$_POST["serviceid"];
    $username=$_POST["createuser"];
    $password=$_POST["password"];


    $data["username"]=$username;
    $data["autogroup"]=$username;
    $data["password"]=$password;
    $data["gecos"]=$username;
    $data["home"]="/home/$username";
    $data["shell"]="/bin/bash";

    $dataX=urlencode(base64_encode(serialize($data)));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/add/$dataX"));
    if(!$json->Status){
        echo $tpl->post_error("json:".$json->Error);
        return false;
    }
    $usernameEnc=urlencode($username);
    $passwordEnc=urlencode($password);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SAMBA("/instance/$serviceid/user/add/$usernameEnc/$passwordEnc"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Create new Unix user $username for service #$serviceid");
}

function info_containers_members_import_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=$_GET["info-containers-members-import-form"];
    $function=$_GET["function"];
    echo "<div style='margin:10px 10px 10px 10px'>";
    echo $tpl->search_block($page,null,null,null,"&info-containers-members-import-search=$serviceid&function-main=$function");
    echo "</div>";
    return true;
}
function info_containers_members_import_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=$_GET["info-containers-members-import-search"];
    $function=$_GET["function"];
    $function_main=$_GET["function-main"];

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/simple"));
    if(!$data->Status){
        echo $tpl->div_error($data->Error);
        return false;
    }
    if(!property_exists($data,"Users")){
        echo $tpl->div_error("{no_data}");
        return false;
    }
    $search="";
    if(isset($_GET["search"])){
        $search="*".$_GET["search"]."*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("*",".*?",$search);
    }

    $gico=ico_member;
    $html[]="<table id='table-hd-disks' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($data->Users as $jsClass) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        if(strlen($search)>1){
            if(!preg_match("#$search#",$jsClass->name)){
                continue;
            }
        }

        $Name = $jsClass->name;
        $NameEnc=urlencode($Name);
        $md = md5(json_encode($jsClass));
        $Linkjs="Loadjs('$page?info-containers-members-import-link=$NameEnc&serviceid=$serviceid&function=$function_main&md=$md');";
        $btn="<button class='btn btn-primary btn-xs' OnClick=\"$Linkjs\">{add} $Name</button>";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><span style='font-size:14px'><i class='$gico'></i>&nbsp;$Name</span></td>";
        $html[]="<td style='width:1%' nowrap>$btn</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function info_container_members_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $function=$_GET["function"];
    $search=$_GET["search"];
    $sock=new sockets();
    $serviceid=$_GET["info-container-members-search"];
    $json=json_decode($sock->REST_API_SAMBA("/instance/$serviceid/members"));
    $tdStyle1="style='width:1%' nowrap";
    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th  nowrap>{member}</th>";
    $html[]="<th nowrap></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach ($json->members as $index=>$user) {
        $md=md5(serialize($user));
        if(strlen($search)>0){
            if(!preg_match("#$search#",$user,$md)){
                continue;
            }
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $userEnc=urlencode($user);
        $unlink=$tpl->icon_unlink("Loadjs('$page?info-container-members-unlink=$serviceid&md=$md&user=$userEnc')","AsSambaAdministrator");

        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td><i class='".ico_member."'></i>&nbsp;$user</td>";
        $html[] = "<td $tdStyle1 nowrap>$unlink</td>";
        $html[] = "</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjax('info-container-members-$serviceid','$page?info-container-members-buttons=$serviceid&function=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
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
        $unlink=$tpl->icon_unlink("Loadjs('$page?delete-network=$ContainerID&md=$md&function-main=$function')","AsSambaAdministrator");

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
    $serviceid=$_GET["info-container-popup"];
    $function_main=$_GET["function-main"];

    echo "<div id='inspect-container-$serviceid'></div>
    <script>LoadAjax('inspect-container-$serviceid','$page?info-container-table=$serviceid&function-main=$function_main');</script>
    ";
    return true;
}
function container_name_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["samba-name-js"];
    return $tpl->js_dialog2("{name}","$page?samba-name-popup=$ID&function-main=$function",500);
}
function container_name_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function-main"];
    $ID=$_GET["samba-name-popup"];
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

    $form[]=$tpl->field_hidden("samba-name",$ID);
    $form[]=$tpl->field_text("name","{name}",$ImageName,true);
    $html[]=$tpl->form_outside("",
        $form,"","{apply}",@implode(";",$js),"AsSambaAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function container_name_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["samba-name"];
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
    $serviceid=$_GET["info-container-table"];
    $function_main=$_GET["function-main"];
    $Class=GetClass($serviceid);

    $HasServicesType=HasServicesType();
    $tpl->table_form_field_text("{ID}",$serviceid,ico_computer);
    $tpl->table_form_field_bool("{status}",$Class->enabled,ico_check);
    $tpl->table_form_field_js("Loadjs('$page?add-container-js=yes&function=$function_main&ID=$serviceid')");
    $tpl->table_form_field_text("{interface}",$Class->interface,ico_interface);
    $tpl->table_form_field_text("{servicename}",$Class->serviceName."&nbsp;<small>(".$HasServicesType[$Class->serviceType]." - $Class->protocol)</small>",ico_computer);

    $tpl->table_form_field_text("{workgroup}",$Class->workgroup,ico_networks);
    $tpl->table_form_field_bool("{visible_on_the_network}",$Class->browsable,ico_scanner_gun);


    $tpl->table_form_field_bool("{homes}",$Class->homes,ico_user);





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
        "BootstrapDialog1.close();$function();","BootstrapDialog1.close();$function();",null,null,"AsSambaAdministrator"
    );
    $security="AsSambaAdministrator";
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


    $html=$tpl->page_header("{APP_SAMBA} &raquo;&raquo; {instances}",
        "fa-solid fa-layer-group",
        "{APP_SAMBA_INSTANCES_EXPLAIN}",
        "$page?table=yes","share-instances","progress-samba-containers",false,"table-samba-containers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: File-sharing instances",$html);
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
    $ID=$_GET["ID"];
    $title="{new_instance2}";
    if($ID>0){
        $Class=GetClass($ID);
        $title=$Class->serviceName;
    }
    return $tpl->js_dialog2($title,"$page?add-container-popup=yes&function-main=$function&serviceid=$ID");
}
function HasServicesType():array{
    $ServiceType[0]="{standalone_server}";
    return $ServiceType;
}
function add_container_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["serviceid"]);


    $function_main=$_GET["function-main"];
    $button="{create}";
    $form[]=$tpl->field_hidden("add-container","yes");
    $form[]=$tpl->field_hidden("ID",$serviceid);
    $enabled=1;
    $ligne["MainType"]=0;
    $ligne["interface"]="";
    $ligne["workgroup"]="WORKGROUP";
    $ligne["homes"]=1;
    $ligne["browsable"]=1;


    if($serviceid>0){
        $button="{apply}";
        $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM instances WHERE ID='$serviceid'");
        $enabled=intval($ligne["enabled"]);
    }
    $HasServicesType=HasServicesType();
    $protocols["NT1"]="NT1";
    $protocols["SMB2"]="SMB2";
    $protocols["SMB3"]="SMB3";

    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled,true);
    $form[]=$tpl->field_text("servicename","{servicename}",$ligne["servicename"],true);
    $form[]=$tpl->field_text("workgroup","{workgroup}",$ligne["workgroup"],true);
    $form[]=$tpl->field_checkbox("browsable","{visible_on_the_network}",$ligne["browsable"],false);
    $form[]=$tpl->field_checkbox("homes","{homes}",$ligne["homes"],false,"{HomeUsersFormExplain}");
    $form[]=$tpl->field_interfaces("interface","{listen_interface}",$ligne["interface"]);
    $form[]=$tpl->field_array_buttons($HasServicesType,"MainType","{type}",$ligne["MainType"],true);
    $form[]=$tpl->field_array_buttons($protocols,"protocol","{protocol}",$ligne["protocol"],true);


    $html[]=$tpl->form_outside("",
        $form,"",$button,"dialogInstance2.close();$function_main();if(document.getElementById('inspect-container-$serviceid') ){ LoadAjax('inspect-container-$serviceid','$page?info-container-table=$serviceid&function-main=$function_main');}","AsSambaAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    
    return true;
}
function add_container_save():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $servicename=$_POST["servicename"];
    $interface=trim($_POST["interface"]);
    $protocol=trim($_POST["protocol"]);
    $serviceid=intval($_POST["serviceid"]);
    $browsable=intval($_POST["browsable"]);
    $sock=new sockets();

    if(strlen($interface)<3){
        echo $tpl->post_error("Please, choose a network interface");
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/samba.db");

    if($serviceid>0){
          $q->QUERY_SQL("UPDATE instances SET 
                      MainType={$_POST["MainType"]},
                      enabled={$_POST["enabled"]},
                      homes={$_POST["homes"]},
                      servicename='$servicename',
                      interface='$interface',
                      browsable=$browsable,
		              protocol='$protocol'
                      WHERE ID=$serviceid");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }

        $sock->REST_API_SAMBA("/instance/$serviceid/reconfigure");
        return admin_tracks_post("Update Files server instance $servicename #{$_POST["serviceid"]}");


    }

    $q->QUERY_SQL("INSERT INTO instances (MainType,enabled,servicename,interface,homes,browsable,protocol) VALUES ({$_POST["MainType"]},{$_POST["enabled"]},'$servicename','$interface',{$_POST["homes"]},$browsable,'$protocol')");

    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    
    $sock->REST_API_SAMBA("/instances/sync");
    return admin_tracks("Creating a new Files server instance $servicename");

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
        return $tpl->icon_run("Loadjs('$page?start-container=$id')","AsSambaAdministrator");
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


    $TINY_ARRAY["TITLE"]="{APP_SAMBA} &raquo;&raquo; {instances}";
    $TINY_ARRAY["ICO"]="fa-solid fa-layer-group";
    $TINY_ARRAY["EXPL"]="{APP_SAMBA_INSTANCES_EXPLAIN}";
    $search=null;
    if(isset($_GET["search"])){$search=$_GET["search"];}

    if($users->AsSambaAdministrator) {
        $topbuttons[] = array("Loadjs('$page?add-container-js=yes&function=$function_main')", "fad fa-layer-plus", "{new_instance2}");
    }



    
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table id='table-samba-containers' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{status}</th>";
    $html[]="<th>PID</th>";
    $html[]="<th>{memory}</th>";
    $html[]="<th>{name}</th>";
    $html[]="<th>{type}</th>";
    $html[]="<th>{uptime}</th>";
    $html[]="<th>{interface}</th>";
    $html[]="<th>{action}</th>";
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
    $json=json_decode($sock->REST_API_SAMBA("/instances/status"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
    }


    foreach ($json->instances as $serviceid=>$class) {

        if($search<>null){
            if(!preg_match("#$search#i",serialize($class))){
                continue;
            }
        }
        $action=lablel_action($json);

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($class));

        $delete=$tpl->icon_delete("Loadjs('$page?delete-container-js=$serviceid&md=$md')","AsSambaAdministrator");

        $state=td_state($class);
        $serviceName=td_servicename($class);
        $ServiceType=td_servicetype($class);
        $Uptime=td_uptime($class);
        $Interface=td_interface($class);
        $Memory=td_memory($class);
        $Pid=td_pid($class);
        $action=td_action($class);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdStyle1><span id='samba-state-$serviceid'>$state</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-pid-$serviceid'>$Pid</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-memory-$serviceid'>$Memory</span></td>";
        $html[]="<td nowrap><strong><span id='samba-servicename-$serviceid'>$serviceName</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-agver-$serviceid'>$ServiceType</span></td>";
        $html[]="<td nowrap><span id='samba-uptime-$serviceid'>$Uptime</span></td>";
        $html[]="<td nowrap><span id='samba-interface-$serviceid'>$Interface</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='samba-action-$serviceid'>$action</span></td>";
        $html[]="<td $tdStyle1 class='center' nowrap>$delete</td>";
        $html[]="</tr>";



    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);


    $allrow=$tpl->RefreshInterval_Loadjs("table-samba-containers",$page,"allrows=yes&function=$function_main");

    $html[]=$allrow;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function td_servicename($class):string{
    $tpl=new template_admin();
    $text=$class->serviceName;
    $page=CurrentPageName();
    $function_main=$_GET["function"];
    return $tpl->td_href($text,null,"Loadjs('$page?info-container=$class->id&function-main=$function_main')");
}
function td_interface($class):string{
    $nic=new system_nic($class->interface);
    $text="<i class='".ico_nic."'></i>&nbsp;$nic->NICNAME ($nic->IPADDR)";
    return $text;
}
function td_servicetype($class):string{
    $tpl=new template_admin();
    $types=HasServicesType();
    $text=$tpl->_ENGINE_parse_body($types[$class->serviceType]);
    $page=CurrentPageName();
    $function_main=$_GET["function"];
    $text="<i class='".ico_server."'></i>&nbsp;$text";

    return $tpl->td_href($text,null,"Loadjs('$page?info-container=$class->id&function-main=$function_main')");
}
function td_uptime($class):string{

    if($class->enabled==0 OR !$class->status->running){
        $tpl=new template_admin();
        return $tpl->icon_nothing();
    }

    return $class->status->ttl;
}
function td_action($class):string{
    $tpl=new template_admin();
    if($class->enabled==0){
        return $tpl->icon_nothing();
    }
    $page=CurrentPageName();
    if(!$class->status->running){
        return $tpl->icon_run("Loadjs('$page?start-container=$class->id')","AsSambaAdministrator");

    }
    return $tpl->icon_stop("Loadjs('$page?stop-container=$class->id')","AsSambaAdministrator") ."&nbsp;".
        $tpl->icon_refresh("Loadjs('$page?restart-container-js=$class->id&md=$class->md5')","AsSambaAdministrator");
}


function td_pid($class):string{

    if($class->enabled==0 OR !$class->status->running){
        $tpl=new template_admin();
        return $tpl->icon_nothing();
    }

    return $class->status->pid;
}
function td_state($class):string{
    $tpl=new template_admin();

    if($class->enabled==0){
        return $tpl->_ENGINE_parse_body("<label class='label label-default'>{inactive2}</label>");
    }
    if($class->status->running){
       return $tpl->_ENGINE_parse_body("<label class='label label-primary'>{running}</label>");
    }
    return $tpl->_ENGINE_parse_body("<label class='label label-danger'>{stopped}</label>");
}

function td_memory($class):string{

    if($class->enabled==0 OR !$class->status->running){
        $tpl=new template_admin();
        return $tpl->icon_nothing();
    }
    return FormatBytes($class->status->memory);
}
function td_button_action($serviceid):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Status=$_SESSION["FIRECRACKER"][$serviceid];
    $ProgressF = "firecracker.start.$serviceid";
    $ProgressS= "firecracker.stop.$serviceid";
    $jsStart=$tpl->framework_buildjs("firecrack:/firecracker/jail/start/$serviceid",$ProgressF,
        "$ProgressF.log","progress-samba-containers");

    $jsStop=$tpl->framework_buildjs("firecrack:/firecracker/jail/stop/$serviceid",$ProgressS,
        "$ProgressF.log","progress-samba-containers");

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


function td_status($serviceid):string{
    $Status=$_SESSION["FIRECRACKER"][$serviceid];

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
        "progress-samba-restart","LoadAjaxTiny('$t-status','$page?service-status=$t');",null,null,"AsSambaAdministrator");


    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DOCKER",$jsrestart);

	
	echo $tpl->_ENGINE_parse_body($html);
}
function RefreshTableRows(){
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $sock=new sockets();
    $json=json_decode($sock->REST_API_SAMBA("/instances/status"));

    if(!$json->Status){
        return false;
    }
    $main=array();$f=array();
    foreach ($json->instances as $serviceid=>$class) {
        $main["samba-state-$serviceid"]=base64_encode(td_state($class));
        $main["samba-pid-$serviceid"]=base64_encode(td_pid($class));
        $main["samba-memory-$serviceid"]=base64_encode(td_memory($class));
        $main["samba-servicename-$serviceid"]=base64_encode(td_servicename($class));
        $main["samba-agver-$serviceid"]=base64_encode(td_servicetype($class));
        $main["samba-uptime-$serviceid"]=base64_encode(td_uptime($class));
        $main["samba-interface-$serviceid"]=base64_encode(td_interface($class));
        $main["samba-action-$serviceid"]=base64_encode(td_action($class));



    }
    foreach ($main as $id=>$value){
        $f[]="if (document.getElementById('$id') ){";
        $f[]="document.getElementById('$id').innerHTML=base64_decode('$value');";
        $f[]="}";

    }

    echo @implode("\n",$f);
    return true;
}
function td_row():bool{
    $tpl=new template_admin();
    $serviceid=$_GET["td-row"];
    $sock=new sockets();
    $json=json_decode($sock->REST_API_FIRECR("/firecracker/jail/status/$serviceid"));
    $_SESSION["FIRECRACKER"][$serviceid]=$json;
    $Status=base64_encode($tpl->_ENGINE_parse_body(td_status($serviceid)));
    $Memory=base64_encode($tpl->_ENGINE_parse_body(td_memory($serviceid)));
    $action=base64_encode($tpl->_ENGINE_parse_body(td_button_action($serviceid)));
    $pid=base64_encode($tpl->_ENGINE_parse_body(td_pid($serviceid)));
    $uptime=base64_encode($tpl->_ENGINE_parse_body(td_uptime($serviceid)));
    list($Cpus,$agver)=td_Cpus($serviceid);



    $doc="document.getElementById";
    $innerHTML="innerHTML=base64_decode";

    header("content-type: application/x-javascript");
    $ID=md5($serviceid);
    $f[]="function NodeRow$ID(){";
    $f[]="\tif (!$doc('samba-state-$serviceid') ){";
    $f[]="//alert('samba-state-$serviceid')";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\t$doc('samba-state-$serviceid').$innerHTML('$Status');";
    $f[]="\t$doc('samba-memory-$serviceid').$innerHTML('$Memory');";
    $f[]="\t$doc('samba-cpu-$serviceid').$innerHTML('$Cpus');";
    $f[]="\t$doc('samba-action-$serviceid').$innerHTML('$action');";
    $f[]="\t$doc('samba-pid-$serviceid').$innerHTML('$pid');";
    $f[]="\t$doc('samba-uptime-$serviceid').$innerHTML('$uptime');";
    $f[]="\t$doc('samba-agver-$serviceid').$innerHTML('$agver');";

    $f[]="}";
    $f[]="NodeRow$ID()";
    echo @implode("\n", $f);
    return true;

}

function td_Cpus($serviceid):array{

    $Status=$_SESSION["FIRECRACKER"][$serviceid];
    if(!$Status->Status){
        return array(base64_encode("-"),base64_encode("-"));
    }

    $tpl=new template_admin();
    $fire=new firecracker($serviceid);
    $json=json_decode($fire->REST_API_FIRECR("/harmp/status"));

    $Cpus=base64_encode($json->CpuPourc."%");
    $Agver=base64_encode($json->Version);
    return array("$Cpus","$Agver");


}
