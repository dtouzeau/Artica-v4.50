<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.docker.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$user=new usersMenus();
if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["main"])){main_start();exit;}
if(isset($_GET["main-table"])){main_table();exit;}
if(isset($_GET["delete-group"])){delete_group_js();exit;}
if(isset($_POST["delete-group"])){delete_group_confirm();exit;}
if(isset($_GET["section-manager"])){section_manager_js();exit;}
if(isset($_GET["section-manager-popup"])){section_manager_popup();exit;}
if(isset($_POST["section-manager"])){section_manager_save();exit;}
js();

function js(){
    $gpid=$_GET["gpid"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gprs=new dockerd_groups($gpid);
    $title=$gprs->GroupNameFull();
    $function_main=$_GET["function-main"];
    return $tpl->js_dialog2($title,"$page?tabs=$gpid&function-main=$function_main");
}
function section_manager_js():bool{
    $tpl=new template_admin();
    $GroupID=$_GET["section-manager"];
    $page=CurrentPageName();
    $gprs=new dockerd_groups($GroupID);
    $title=$gprs->GroupNameFull();
    $function_main=$_GET["function-main"];
    return $tpl->js_dialog3($title." - {parameters}","$page?section-manager-popup=$GroupID&function-main=$function_main");

}
function section_manager_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $GroupID=$_GET["section-manager-popup"];
    $gprs=new dockerd_groups($GroupID);
    $title=$gprs->GroupNameFull();
    $function_main=$_GET["function-main"];
    $gprs=new dockerd_groups($GroupID);

    $Manager=$gprs->Get("manager");
    $MaxInstances=intval($gprs->Get("MaxInstances"));
    if($MaxInstances==0){$MaxInstances=1;}
    if($Manager==null){
        $Manager=serialize(array("USER"=>"Manager","PASS"=>"secret"));
    }
    $MM=unserialize($Manager);
    $js[]="LoadAjaxTiny('frontend-group-main-$GroupID','$page?main-table=$GroupID&function-main=$function_main')";
    $js[]="dialogInstance3.close()";
    if($function_main<>null){
        $js[]="$function_main()";
    }
    $form[]=$tpl->field_hidden("section-manager",$GroupID);
    $form[]=$tpl->field_text("USER","{manager}",$MM["USER"],true);
    $form[]=$tpl->field_password("PASS","{password}",$MM["PASS"],true);
    $form[]=$tpl->field_numeric("MaxInstances","{maxchild}",$MaxInstances);
    echo $tpl->form_outside($title,$form,null,"{apply}",@implode(";",$js),"AsDockerAdmin");
    return true;
}
function section_manager_save():bool{
    $GroupID=$_POST["section-manager"];
    $gprs=new dockerd_groups($GroupID);
    $title=$gprs->GroupNameFull();
    $creds=array("USER"=>$_POST["USER"],"PASS"=>$_POST["PASS"]);
    $gprs->Set("manager",serialize($creds));
    $gprs->Set("MaxInstances",$_POST["MaxInstances"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?group-config=$GroupID");
    return admin_tracks("Save Group $title configuration with Manager:{$_POST["USER"]}, Max Instances {$_POST["MaxInstances"]}");



}


function tabs():bool{
    $gpid=intval($_GET["tabs"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gprs=new dockerd_groups($gpid);
    $GroupName=$gprs->GroupName();
    $function_main=$_GET["function-main"];
    $array[$GroupName]="$page?main=$gpid&function-main=$function_main";
    echo $tpl->tabs_default($array);
    return true;
}

function delete_group_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_GET["delete-group"]);
    $function_main=$_GET["function-main"];
    $gprs=new dockerd_groups($gpid);
    $GroupName=$gprs->GroupName();

    $After="$function_main();dialogInstance2.close();";

    $js=$tpl->framework_buildjs("docker.php?remove-perimeter-group=$gpid",
        "docker.perimeter.delete.group.$gpid",
        "docker.perimeter.delete.group.$gpid.log",
        "progress-group-$gpid",
        $After,null,null,"AsDockerAdmin"

    );

    return $tpl->js_confirm_delete("{action_delete_group} $GroupName","delete-group",$gpid,$js);

}
function delete_group_confirm():bool{
    $gpid=$_POST["delete-group"];
    $gprs=new dockerd_groups($gpid);
    $GetPerimeterName=$gprs->GetPerimeterName();
    $GroupName=$gprs->GroupName();
    return admin_tracks("Remove group $GroupName from perimter $GetPerimeterName");
}
function main_start():bool{
    $page=CurrentPageName();
    $gpid=intval($_GET["main"]);
    $function_main=$_GET["function-main"];
    echo "<div id='frontend-group-main-$gpid'></div>
    <script>LoadAjaxTiny('frontend-group-main-$gpid','$page?main-table=$gpid&function-main=$function_main')</script>";
    return true;
}
function main_table():bool{
    $page=CurrentPageName();
    $gpid=intval($_GET["main-table"]);
    $gprs=new dockerd_groups($gpid);
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $FrontendID=$gprs->GetPermimeterID();
    $dock=new dockerd();

    $ContainersWebAdms=$dock->ContainersListByTag("com.articatech.artica.scope.$FrontendID.webadm.$gpid",true);
    VERBOSE("Group $gpid, Perimeter=$FrontendID Web Containers=".count($ContainersWebAdms),__LINE__);
    if(count($ContainersWebAdms)>0){
        $WebContainerID=$ContainersWebAdms[0];
        $ContainerName=$dock->GetContainerName($WebContainerID);

        $updatejs=$tpl->framework_buildjs(
            "docker.php?update-web-frontend=$WebContainerID",
            "docker.update.$WebContainerID",
            "docker.update.$WebContainerID.log",
            "progress-group-$gpid",
            "LoadAjaxTiny('frontend-group-main-$gpid','$page?main-table=$gpid&function-main=$function_main')"
        );
        VERBOSE("table_form_button -> {update} $ContainerName",__LINE__);
        $tpl->table_form_button("{update} $ContainerName",$updatejs,"AsDockerAdmin",ico_download);
    }

    $tpl->table_form_field_text("{ID}",$gpid,ico_link);
    $tpl->table_form_field_js("Loadjs('$page?section-manager=$gpid&function-main=$function_main')","AsDockerAdmin");
    $Manager=$gprs->Get("manager");
    $MaxInstances=intval($gprs->Get("MaxInstances"));
    if($MaxInstances==0){$MaxInstances=1;}
    if($Manager==null){
        $Manager=serialize(array("USER"=>"Manager","PASS"=>"secret"));
    }
    $MM=unserialize($Manager);
    $tpl->table_form_field_text("{manager}",$MM["USER"].":****",ico_admin);
    $tpl->table_form_field_text("{maxchild}",$MaxInstances,ico_server);


    $tpl->table_form_button("{DeleteThisGroup}","Loadjs('$page?delete-group=$gpid&function-main=$function_main')","AsDockerAdmin",ico_trash);
    $html[]="<div id='progress-group-$gpid'></div>";
    $html[]=$tpl->table_form_compile();
    echo @implode("\n",$html);
    return true;
}