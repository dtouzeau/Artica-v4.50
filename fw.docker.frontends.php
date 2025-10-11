<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc');
    include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
    include_once(dirname(__FILE__).'/ressources/class.docker.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}

    if(isset($_GET["add-perimeter-js"])){add_perimeter_js();exit;}
    if(isset($_GET["add-perimeter-popup"])){add_perimeter_popup();exit;}
    if(isset($_POST["add-perimeter"])){add_perimeter_save();exit;}
    if(isset($_POST["save-perimeter"])){perimeter_edit_save();exit;}
    if(isset($_GET["perimeter"])){perimeter_edit_js();exit;}
    if(isset($_GET["perimeter-popup"])){perimeter_edit_popup();exit;}

    if(isset($_GET["add-group-js"])){add_group_js();exit;}
    if(isset($_GET["add-group-popup"])){add_group_popup();exit;}
    if(isset($_POST["add-group"])){add_group_save();exit;}

    if(isset($_GET["containers-list"])){containers_list();exit;}
    if(isset($_GET["containers-table"])){containers_table();exit;}

    if(isset($_GET["delete-perimeter"])){delete_perimeter_js();exit;}
    if(isset($_POST["delete-perimeter"])){delete_perimeter_perform();exit;}

    if(isset($_GET["link-container-js"])){link_container_js();exit;}
    if(isset($_GET["link-container-popup"])){link_container_popup();exit;}
    if(isset($_GET["delete-network"])){delete_network();exit;}
    if(isset($_POST["delete-network"])){delete_network_perform();exit;}
    if(isset($_GET["groups-status"])){table_groups();exit;}


    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["search"])){search();exit;}



	
page();


function delete_perimeter_js():bool{
    $ID=$_GET["delete-perimeter"];
    $md=$_GET["md"];
    $function_main=$_GET["function-main"];
    $dock=new dockerd();
    $name=$dock->PerimeterName($ID);

    $tpl=new template_admin();

    $js=$tpl->framework_buildjs("docker.php?delete-perimeter=$ID",
        "docker.perimeter",
        "docker.perimeter.log",
        "progress-docker-wwscopes","$('#$md').remove()"
    );


    return $tpl->js_confirm_delete($name,"delete-perimeter",$ID,$js);
}
function delete_perimeter_perform():bool{
    $ID=$_POST["delete-perimeter"];
    $tpl=new template_admin();
    $dock=new dockerd();
    $name=$dock->PerimeterName($ID);



    admin_tracks("Deleted Web service Perimeter network $name");
    return true;
}

function containers_list():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["containers-list"];
    $function=$_GET["function-main"];
    $dock=new dockerd();
    $ARRAY=$dock->NetworkInfo($ID);
    return $tpl->js_dialog1("{$ARRAY["Name"]} {containers}","$page?containers-table=$ID&function-main=$function",550);
}
function containers_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["containers-table"];
    $function=$_GET["function-main"];
    $dock=new dockerd();
    $Nets=$dock->NetworkInfo($ID);


    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{container}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{mac}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach ($Nets["Containers"] as $zid=>$ligne) {
        $md=md5(serialize($ligne));
        $IPAddress=$ligne["IPv4Address"];
        $MacAddress=$ligne["MacAddress"];
        $Name=$ligne["Name"];
        $ContainerID=$ligne["EndpointID"];

        $unlink=$tpl->icon_unlink("Loadjs('fw.docker.containers.php?delete-network=$ID&ID=$zid&md=$md&function-main=$function')","AsDockerAdmin");

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


function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{perimeters}",
        ico_clouds,
        "{APP_DOCKER_SCOPES_EXPLAIN}",
        "$page?table=yes","docker-www-scopes","progress-docker-wwscopes",false,"table-docker-scopes");



    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker {perimeters}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function add_perimeter_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog("{new_perimeter}","$page?add-perimeter-popup=yes&function-main=$function");
}
function perimeter_edit_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_main=$_GET["function-main"];
    $ID=$_GET["perimeter"];
    return $tpl->js_dialog("{perimeter} $ID","$page?perimeter-popup=$ID&function-main=$function_main");
}
function perimeter_edit_popup():bool{
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $dock=new dockerd();
    $HASH=$dock->NetworkList();
    $ID=intval($_GET["perimeter-popup"]);
    $dock=new dockerd();
    $dock->PerimeterName($ID);
    $name=$dock->PerimeterName($ID);
    $form[]=$tpl->field_hidden("save-perimeter",$ID);
    $form[]=$tpl->field_text("Name","{perimeter}: {name}",$name,true);
    $form[]=$tpl->field_array_hash($HASH,"NetID","nonull:{network}",$dock->PerimeterNetwork($ID),true);
    $html[]=$tpl->form_outside($name,$form,"{new_perimeter_docker_explain}","{apply}","BootstrapDialog1.close();$function_main()","AsDockerAdmin");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function perimeter_edit_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["save-perimeter"]);
    $Name=$_POST["Name"];
    $NetID=$_POST["NetID"];
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");

    $q->QUERY_SQL("UPDATE frontends SET name='$Name',networkid='$NetID' WHERE ID='$ID'");
    if(!$q->ok){
        $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks_post("Saving Docker perimeter");
}
function add_group_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog("{new_group}","$page?add-group-popup=yes&function-main=$function");
}
function add_group_popup():bool{
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $results=$q->QUERY_SQL("SELECT * FROM frontends");
    $time=time();
    $HASH=array();
    foreach ($results as $index=>$ligne){
        $HASH[$ligne["ID"]]=$ligne["name"];
    }
    if(count($HASH)==0){
        echo $tpl->FATAL_ERROR_SHOW_128("{error_no_perimeter_defined}");
        return false;
    }
    $js=$tpl->framework_buildjs("docker.php?create-perimeter-group=yes",
        "docker.perimeter.create.group",
        "docker.perimeter.create.group.log",
        "perimeter-group-$time",
        "$function_main();BootstrapDialog1.close()",null,null,"AsDockerAdmin"

    );

    $html[]="<div id='perimeter-group-$time'>";
    $form[]=$tpl->field_hidden("add-group","yes");
    $form[]=$tpl->field_text("name","{group name}",null,true);
    $form[]=$tpl->field_array_hash($HASH,"frontendid","nonull:{perimeter}",null,true);
    $html[]=$tpl->form_outside("{new_group}",$form,"{new_perimeter_group_explain}","{add}",$js,"AsDockerAdmin");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function add_group_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $dock=new dockerd();
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $dock->create_databases();
    $frontendid=$_POST["frontendid"];
    $name=$q->sqlite_escape_string2($_POST["name"]);
    $date=time();
    $tempname=time();
    $q->QUERY_SQL("INSERT INTO groups (name,frontend_id,created) VALUES ('$tempname','$frontendid','$date')");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM groups WHERE name='$tempname'");
    $gpid=intval($ligne["ID"]);
    if($gpid==0){
        echo $tpl->post_error("Unable to get groupid from $tempname");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PERIMETER_GROUP_CREATED",$gpid);
    $q->QUERY_SQL("UPDATE groups SET name='$name' WHERE ID=$gpid");
    $ffname=$dock->PerimeterName($frontendid);
    return admin_tracks("Create a new group $name for the perimeter $ffname");
}
function add_perimeter_popup():bool{
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $dock=new dockerd();
    $HASH=$dock->NetworkList();
    $form[]=$tpl->field_hidden("add-perimeter","yes");
    $form[]=$tpl->field_text("Name","{perimeter}: {name}",null,true);
    $form[]=$tpl->field_array_hash($HASH,"NetID","nonull:{network}",null,true);

    $js=$tpl->framework_buildjs("docker.php?add-perimeter=yes",
        "docker.perimeter",
        "docker.perimeter.log",
        "add-perimeter-div","$function_main();BootstrapDialog1.close()"
    );
    $html[]="<div id='add-perimeter-div'></div>";
    $html[]=$tpl->form_outside("{new_perimeter}",$form,"{new_perimeter_docker_explain}","{add}",$js,"AsDockerAdmin");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function add_network_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DOCKER_PERIMETER_SAVE",serialize($_POST));
    admin_tracks_post("Add a new docker frontend perimeter");
    return true;
}
function add_perimeter_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DOCKER_PERIMETER_SAVE",serialize($_POST));
    admin_tracks_post("Add a new docker frontend perimeter");
    return true;
}


function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $dock=new dockerd();
    $DOCKER_BACKEND_IMAGEID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOCKER_BACKEND_IMAGEID"));
    $DOCKER_WEBADM_IMAGEID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOCKER_WEBADM_IMAGEID"));
    $AdminImageName=$dock->GetImageName($DOCKER_WEBADM_IMAGEID);
    $BackendImageName=$dock->GetImageName($DOCKER_BACKEND_IMAGEID);
    $error=false;
    if(strlen($AdminImageName)<2){
        $error=true;
    }
    if(strlen($BackendImageName)<2){
        $error=true;
    }

    if($error){
        $sinstall=$tpl->framework_buildjs(
            "docker.php?install-artica-images=yes",
            "docker.articatech.images.progress",
            "docker.articatech.images.log",
            "progress-docker-wwscopes",
            "LoadAjax('table-docker-scopes','$page?table=yes')"
        );
        $installImages=$tpl->button_autnonome("{install_images}",$sinstall,ico_cd,"AsDockerAdmin",335,"btn-danger");
        echo $tpl->div_error("{error_no_artica_images}<div style='text-align:right;margin-right:50px'>$installImages</div>");
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
    $topbuttons=array();
    $function_main=$_GET["function"];

    $TINY_ARRAY["TITLE"]="{perimeters}";
    $TINY_ARRAY["ICO"]=ico_clouds;
    $TINY_ARRAY["EXPL"]="{APP_DOCKER_SCOPES_EXPLAIN}";

    $topbuttons[] = array("Loadjs('$page?add-perimeter-js=yes&function=$function_main')", ico_plus, "{new_perimeter} ","AsDockerAdmin");

    $topbuttons[] = array("Loadjs('$page?add-group-js=yes&function=$function_main')", ico_objects_group, "{new_group} ","AsDockerAdmin");

    $DOCKER_BACKEND_IMAGEID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOCKER_BACKEND_IMAGEID"));
    $DOCKER_WEBADM_IMAGEID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOCKER_WEBADM_IMAGEID"));

    if($DOCKER_WEBADM_IMAGEID<>null){
        $topbuttons[] = array("Loadjs('fw.docker.images.php?inspect-image-js=$DOCKER_WEBADM_IMAGEID&function=$function_main')", ico_cd, "{image}:WebAdmin ","AsDockerAdmin");

    }
    if($DOCKER_BACKEND_IMAGEID<>null){
        $topbuttons[] = array("Loadjs('fw.docker.images.php?inspect-image-js=$DOCKER_BACKEND_IMAGEID&function=$function_main')", ico_cd, "{image}:{APP_NGINX} ","AsDockerAdmin");

    }


    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{createdat}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{perimeter}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{containers}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{network}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{image}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();

    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["running"]="<label class='label label-primary'>{running}</label>";
    $dock=new dockerd();
    $dock->create_databases();
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $results=$q->QUERY_SQL("SELECT * FROM frontends");


    foreach ($results as $ligne){

        $ID=$ligne["ID"];
        $name=$ligne["name"];
        $frontendimageid=$ligne["frontendimageid"];
        $adminimageid=$ligne["adminimageid"];
        $networkid=$ligne["networkid"];
        $created=$ligne["created"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $CreatedAt=$tpl->time_to_date($created,true);
        $MAIN=$dock->NetworkInfo($networkid);
        $IPAddress=null;
        if(count($MAIN)>0) {
            if(isset($MAIN["IPv4Address"])) {
                $IPAddress = $MAIN["IPv4Address"];
            }
            $NetName = $MAIN["Name"];
        }else{
            $NetName="<span class='label label-danger'>{error}</span>";
        }

        $AdminImageName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOCKER_WEBADM_IMAGEID"));



        if($adminimageid<>null){
            $AdminImageName=$dock->GetImageName($adminimageid);
        }


        if($AdminImageName==null){
            $AdminImageName=$tpl->td_href("<span class='label label-default'>{install_image}</span>",null);
        }else{
            $AdminImageName=$tpl->td_href($AdminImageName,null,"Loadjs('fw.docker.images.php?inspect-image-js=$adminimageid&name=".urlencode($AdminImageName)."&function-main=$function_main')");
        }


        $ContainersList=$dock->ContainersListByTag("com.articatech.artica.scope.$ID");

        $Containers=count($ContainersList);
        $buton=$tpl->icon_delete("Loadjs('$page?delete-perimeter=$ID&function-main=$function_main&md=$md')","AsDockerAdmin");

        $Containers_tooltips="<span class='label label-default'>0</span>";
        if($Containers>0){
            $Containers_tooltips=$tpl->td_href("<span class='label label-primary'>$Containers</span>",
                null,"Loadjs('fw.docker.networks.php?containers-list=$ID&function-main=$function_main');");
        }

        if($IPAddress<>null){$IPAddress="&nbsp;($IPAddress)";}
        $name=$tpl->td_href("$name","","Loadjs('$page?perimeter=$ID&function-main=$function_main')");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><li class='".ico_server."'></li></td>";
        $html[]="<td width='1%' class='left' nowrap>$CreatedAt</td>";
        $html[]="<td width='99%' nowrap>$name<div style='margin-left:15px;margin-top:5px' id='gps-$ID'></div></td>";
        $html[]="<td width='1%' nowrap class='center'>$Containers_tooltips</td>";
        $html[]="<td width='1%' nowrap>$NetName$IPAddress</td>";
        $html[]="<td width='1%' class='center' nowrap>$buton</td>";
        $html[]="</tr>";
        $scripts[]="LoadAjaxTiny('gps-$ID','$page?groups-status=$ID&function-main=$function_main');";


    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
        $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]=@implode("\n",$scripts);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function table_instances($GroupID):string{
    $tpl=new template_admin();
    $gp=new dockerd_groups($GroupID);
    $PermimeterID=$gp->GetPermimeterID();
    $filter="com.articatech.artica.scope.$PermimeterID.backend.$GroupID";
    $dock=new dockerd();
    $ContainersList=$dock->ContainersListByTag($filter);
    $function_main=$_GET["function-main"];
    $f=array();
    foreach ($ContainersList as $uuid=>$ContName){
        $js="Loadjs('fw.docker.containers.php?info-container=$uuid&function-main=$function_main')";
        $Name=$ContName;
        $xclass=$dock->GetContainerStateClass($uuid);
        $f[]=$tpl->button_tooltip($Name,$js,ico_server,null,"label-$xclass");

    }
    if(count($f)==0){return "";}
    return @implode(" ",$f);

}
function table_groups():bool{
    $FrontendID=intval($_GET["groups-status"]);
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $function_main=$_GET["function-main"];
    $results=$q->QUERY_SQL("SELECT * FROM groups WHERE frontend_id=$FrontendID");
    if(count($results)==0){return "";}


    $html[]="<table style='margin-left:15px;margin-top:5px'>";
    foreach ($results as $ligne){
        $md=md5(serialize($ligne));
        $GroupID=$ligne["ID"];
        $gpdock=new dockerd_groups($GroupID);

        $webconsole_status=WebContainer_status($FrontendID,$GroupID);
        $name=$ligne["name"];
        $MaxInstances=intval($gpdock->Get("MaxInstances"));
        if($MaxInstances==0){$MaxInstances=1;}
        $name=$tpl->td_href($name,null,"Loadjs('fw.docker.frontends.group.php?gpid=$GroupID&function-main=$function_main')");
        $instances=table_instances($GroupID);
        $html[]="<tr id='$md'>";
        $html[]="<td width='1%'><li class='".ico_objects_group."'></li>&nbsp;</td>";
        $html[]="<td width='99%' class='left' nowrap>$name ($MaxInstances {instances}) $instances</td>";
        $html[]="<td width='1%' nowrap>$webconsole_status</td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function WebContainer_status($FrontendID,$GroupID):string{
    $function_main=$_GET["function-main"];
    $dock=new dockerd();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $refreshjs="LoadAjaxTiny('gps-$FrontendID','$page?groups-status=$FrontendID&function-main=$function_main');";
    $ContainersWebAdms=$dock->ContainersListByTag("com.articatech.artica.scope.$FrontendID.webadm.$GroupID",true);
    if(count($ContainersWebAdms)==0){
        $RunWebFrontend=$tpl->framework_buildjs("docker.php?run-webfrontend=$GroupID",
            "docker.perimeter.create.$GroupID",
            "docker.perimeter.create.$GroupID.log",
            "progress-docker-wwscopes",
            "$refreshjs;"
        );

        return $tpl->td_href("<span class='label label-default'>{webconsole}:{install}","{install}",$RunWebFrontend);
    }

    $ContainerID=$ContainersWebAdms[0];

    return $dock->html_container_status($ContainerID,"RefreshStatus$FrontendID$GroupID","{webconsole}").
        "<script>function RefreshStatus$FrontendID$GroupID(){ $refreshjs;$function_main(); }</script>";

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






