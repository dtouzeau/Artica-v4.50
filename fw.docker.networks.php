<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc');
    include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
    include_once(dirname(__FILE__).'/ressources/class.docker.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}

    if(isset($_GET["add-network-js"])){add_network_js();exit;}
    if(isset($_GET["add-network-popup"])){add_network_popup();exit;}
    if(isset($_POST["add-network"])){add_network_save();exit;}


    if(isset($_GET["containers-list"])){containers_list();exit;}
    if(isset($_GET["containers-table"])){containers_table();exit;}

    if(isset($_GET["delete-volume-js"])){delete_volume_js();exit;}
    if(isset($_POST["delete-container"])){delete_container_perform();exit;}

    if(isset($_GET["link-container-js"])){link_container_js();exit;}
    if(isset($_GET["link-container-popup"])){link_container_popup();exit;}
    if(isset($_GET["delete-network"])){delete_network();exit;}
    if(isset($_POST["delete-network"])){delete_network_perform();exit;}



    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["search"])){search();exit;}



	
page();


function delete_network():bool{
    $NetID=$_GET["delete-network"];
    $md=$_GET["md"];
    $function=$_GET["function-main"];
    $dock=new dockerd();
    $NetworkInfo=$dock->NetworkInfo($NetID);
    $text="{$NetworkInfo["Name"]}";
    $tpl=new template_admin();
    return $tpl->js_confirm_delete($text,"delete-network",$NetID,"$('#$md').remove();$function();");
}
function delete_network_perform():bool{
    $NetID=$_POST["delete-network"];
    $tpl=new template_admin();
    $dock=new dockerd();
    $NetworkInfo=$dock->NetworkInfo($NetID);
    $tfile=PROGRESS_DIR."/docker.network.del.$NetID";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?delete-network=$NetID");

    $results=@explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            echo $tpl->post_error(@implode("<br>",$results));
            return false;
        }
    }

    admin_tracks("Deleted docker network {$NetworkInfo["Name"]}");
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
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");

    $html=$tpl->page_header("{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {networks}",
        ico_networks,
        "{APP_DOCKER_EXPLAIN}",
        "$page?table=yes","docker-networks","progress-docker-networks",false,"table-docker-networks");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker containers",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function add_network_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog("{new_network}: {bridge_network}","$page?add-network-popup=yes&function-main=$function");
}
function add_network_popup():bool{
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];

    $rand=rand(10,31);
    $form[]=$tpl->field_hidden("add-network","yes");
    $form[]=$tpl->field_text("Name","{name}",null,true);
    $form[]=$tpl->field_text("subnet","{subnet}","172.$rand.0.0/16",true);
    $form[]=$tpl->field_text("range","{range}","172.$rand.0.0/24",false);
    $form[]=$tpl->field_ipv4("gateway","{gateway}","172.$rand.0.1",false);


    //--driver=bridge --subnet=10.0.0.0/16 --ip-range=10.0.0.0/24 --gateway=10.0.0.1 eth2


    $html[]=$tpl->form_outside("{new_network} - {bridge_network}",$form,null,"{add}","BootstrapDialog1.close();$function_main()","AsDockerAdmin");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function add_network_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Ipclass=new IP();
    if(!$Ipclass->IsACDIR($_POST["subnet"])){
        echo $tpl->post_error($_POST["subnet"]." {invalid}");
        return false;
    }
    if($_POST["range"]<>null) {
        if (!$Ipclass->IsACDIR($_POST["range"])) {
            echo $tpl->post_error($_POST["range"] . " {invalid} {range}");
            return false;
        }
    }
    if($_POST["gateway"]<>null) {
        if (!$Ipclass->isValid($_POST["gateway"])) {
            echo $tpl->post_error($_POST["gateway"] . " {invalid} {gateway}");
            return false;
        }
    }
    $base=base64_encode(serialize($_POST));
    $md5=md5($base);
    $tfile=PROGRESS_DIR."/docker.network.add.$md5";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?network-add=$base&md5=$md5");

    $results=@explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            echo $tpl->post_error(@implode("<br>",$results));
            return false;
        }
    }
    @unlink($tfile);

    admin_tracks_post("Add a new docker network");
    return true;
}
function add_volume_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Size=0;
    if(isset($_POST["Size"])){
        $Size=$_POST["Size"];
    }
    $VOLUME=$_POST["VOLUME"];
    $VOLUME=str_replace([' ',',','&',';','%','/','\\','*','|','(',')','{','}','[',']','=',':','#','~','>','<','?','!','$','¤','"','+'],'',$VOLUME);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?volumes-add=$VOLUME&size=$Size");
    admin_tracks("Add new docker volume $VOLUME size=$Size");
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
	$t=time();
    $topbuttons=array();
    $function_main=$_GET["function"];
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {networks}";
    $TINY_ARRAY["ICO"]=ico_networks;
    $TINY_ARRAY["EXPL"]="{APP_DOCKER_EXPLAIN}";


    $topbuttons[] = array("Loadjs('$page?add-network-js=yes&function=$function_main')", ico_plus, "{new_network} ({bridge_network})","AsDockerAdmin");
    $topbuttons[] = array("Loadjs('$page?prune-volume-js=yes&function=$function_main')", ico_trash, "{rm_unused_vols}","AsDockerAdmin");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

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
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{createdat}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{driver}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{containers}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{scope}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{network}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{gateway}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();

    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["running"]="<label class='label label-primary'>{running}</label>";
    $dock=new dockerd();


    foreach ($f as $line){

        $line=trim($line);
        if($line==null){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($line);
        $json=json_decode($line);
        $zDate="-";
        $ID=$json->ID;
        $Driver=$json->Driver;
        $Name=$json->Name;
        $NameEnc=urlencode($Name);
        $Scope=$json->Scope;
        $CreatedAt=$tpl->time_to_date($dock->DockTimeToInt($json->CreatedAt),true);
        $MAIN=$dock->NetworkInfo($ID);
        if($_GET["search"]<>null){
            $_GET["search"]=str_replace("*",".*?",$_GET["search"]);
            if(!preg_match("#{$_GET["search"]}#i",serialize($MAIN))){continue;}
        }

        $Containers=count($MAIN["Containers"]);
        $buton=$tpl->icon_delete("Loadjs('$page?delete-network=$ID&function-main=$function_main&md=$md')","AsDockerAdmin");
        if($Name=="compose_default"){$buton=null;}
        $Containers_tooltips="<span class='label label-default'>0</span>";
        if($Containers>0){
            $Containers_tooltips=$tpl->td_href("<span class='label label-primary'>$Containers</span>",
                null,"Loadjs('$page?containers-list=$ID&function-main=$function_main');");
        }


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><li class='".ico_networks."'></li></td>";
        $html[]="<td width='1%' class='left' nowrap>$CreatedAt</td>";
        $html[]="<td width='1%' nowrap>$Driver</td>";
        $html[]="<td nowrap><strong>$Name</strong></td>";
        $html[]="<td width='1%'>$Containers_tooltips</td>";
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
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
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






