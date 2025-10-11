<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["folder-instances-search"])){folder_instances_search();exit;}


if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["folder-js"])){folder_js();exit;}
if(isset($_GET["folder-tabs"])){folder_tab();exit;}
if(isset($_GET["folder-popup"])){folder_popup();exit;}
if(isset($_POST["folderid"])){folder_save();exit;}
if(isset($_GET["folder-instances-head"])){folder_instances_head();exit;}
if(isset($_GET["folder-instances-select"])){folder_instances_select();exit;}
if(isset($_GET["folder-instances-unselect"])){folder_instances_unselect();exit;}



page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    $html=$tpl->page_header("{shares_center}",ico_directory,"{SAMBA_BTRFS_SHARES}",
        "$page?table-start=yes","shares","progress-shares-restart",false,"table-loader-shares-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{shares_center} {$version}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function folder_instances_head(){
    $ID=intval($_GET["folder-instances-head"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>".$tpl->search_block($page,null,null,null,"&folder-instances-search=$ID&function1=$function")."</div>";
    return true;
}
function folder_instances_select():bool{
    $folderid=intval($_GET["folder-instances-select"]);
    $serviceid=intval($_GET["serviceid"]);
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
    $zmd5=md5($folderid.$serviceid);
    $q->QUERY_SQL("INSERT INTO link_shared (sharedid,serviceid,zmd5) VALUES ($folderid,$serviceid,'$zmd5')");
    header("content-type: application/x-javascript");
    echo "$function();";

    $sock=new sockets();
    $sock->REST_API_SAMBA("/instance/$serviceid/reconfigure");

    return admin_tracks("Adding a Files server shared folder $folderid to instance $serviceid");
}
function folder_instances_unselect():bool{
    $ID=intval($_GET["folder-instances-unselect"]);
    $q=new lib_sqlite("/home/artica/SQLITE/samba.db");

    $ligne=$q->mysqli_fetch_array("SELECT serviceid FROM link_shared WHERE ID=$ID");
    $serviceid=$ligne["serviceid"];

    $q->QUERY_SQL("DELETE FROM link_shared WHERE ID=$ID");
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "$function();";

    $sock=new sockets();
    $sock->REST_API_SAMBA("/instance/$serviceid/reconfigure");

    return admin_tracks("Removing a Files server shared folder #$ID from #$serviceid rule");
}
function folder_instances_search():bool{
    $ID=intval($_GET["folder-instances-search"]);
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();

    $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
    $results=$q->QUERY_SQL("SELECT ID,serviceid FROM link_shared WHERE sharedid=$ID");
    foreach ($results as $index=>$ligne) {
        $serviceid=$ligne["serviceid"];
        $ENABLES[$serviceid]=$ligne["ID"];
    }


    $json=json_decode($sock->REST_API_SAMBA("/instances/status"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
    }
    $search=$_GET["search"];

    $html[]="<table id='table-instances-shares-$ID' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{name}</th>";
    $html[]="<th style='width:1%' nowrap></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $tdStyle1="style='width:1%' nowrap";

    foreach ($json->instances as $serviceid=>$class) {

        if ($search <> null) {
            if (!preg_match("#$search#i", serialize($class))) {
                continue;
            }
        }
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($class));
        $button="<button OnClick=\"Loadjs('$page?folder-instances-select=$ID&serviceid=$serviceid&function=$function');\" 
        class='btn btn-default btn-xs' type='button'><i class='fas fa-folder-plus'></i>&nbsp;{assign}</button>";

        if(isset($ENABLES[$serviceid])){
            $button="<button OnClick=\"Loadjs('$page?folder-instances-unselect=$ENABLES[$serviceid]&function=$function');\" 
        class='btn btn-danger btn-xs' type='button'><i class='fas fa-folder-minus'></i>&nbsp;{unassign}</button>";
        }

        $serviceName=td_servicename($class);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td nowrap><strong><span id='samba-servicename-$serviceid'>$serviceName</span></td>";
        $html[]="<td $tdStyle1 class='center' nowrap><span id='btn-select-$serviceid'>$button</span></td>";
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


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{shares_center}"]="$page?table-start=yes";
    echo $tpl->tabs_default($array);
}
function table_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
}
function folder_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function_main=$_GET["function"];
    $title="{new_shared_folder}";
    $ID=intval($_GET["folder-js"]);
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
        $ligne=$q->mysqli_fetch_array("SELECT sharename FROM shared WHERE ID=$ID");
        $title=$ligne["sharename"];
    }
    return $tpl->js_dialog1($title,"$page?folder-tabs=$ID&function=$function_main");
}
function folder_tab():bool{
    $title="{new_shared_folder}";
    $function_main=$_GET["function"];
    $ID=intval($_GET["folder-tabs"]);
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
        $ligne=$q->mysqli_fetch_array("SELECT sharename FROM shared WHERE ID=$ID");
        $title=$ligne["sharename"];
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array[$title]="$page?folder-popup=$ID&function=$function_main";
    $array["{instances}"]="$page?folder-instances-head=$ID&function=$function_main";
    echo $tpl->tabs_default($array);
    return true;
}
function folder_popup():bool{
    $button="{create}";
    $js=array();
    $function_main=$_GET["function"];
    $ID=intval($_GET["folder-popup"]);
    $ligne["sharename"]="shared".time();
    $ligne["enabled"]=1;
    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM shared WHERE ID=$ID");
        $button="{apply}";

    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_hidden("folderid",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enable}",$ligne["enabled"]);
    $form[]=$tpl->field_browse_directory("localpath","{path}",$ligne["localpath"],"/");
    $form[]=$tpl->field_text("sharename","{share_name}",$ligne["sharename"]);
    $form[]=$tpl->field_text("comment","{description}",$ligne["comment"]);
    $form[]=$tpl->field_checkbox("browseable","{visible_on_the_network}",$ligne["browseable"]);
    $form[]=$tpl->field_checkbox("readonly","{ro}",$ligne["readonly"]);
    $form[]=$tpl->field_checkbox("public","{PUBLIC_ACCESS}",$ligne["public"],false,"{public_text}");

    if($ID==0){
        $js[]="dialogInstance1.close()";
        $js[]="$function_main()";
    }

    $html[]=$tpl->form_outside("",
        $form,"",$button,@implode(";",$js),"AsSambaAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function folder_save():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/samba.db");

    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $localpath=$_POST["localpath"];
    $sharename=$q->sqlite_escape_string2(trim($_POST["sharename"]));
    $comment=$q->sqlite_escape_string2(trim($_POST["comment"]));
    $folderid=intval($_POST["folderid"]);
    $browsable=intval($_POST["browseable"]);
    $enabled=intval($_POST["enabled"]);
    $readonly=intval($_POST["readonly"]);
    $public=intval($_POST["public"]);
    $sock=new sockets();

    if($folderid>0){
        $q->QUERY_SQL("UPDATE shared SET 
                      localpath='$localpath',
                      enabled=$enabled,
                      sharename='$sharename',
                      readonly=$readonly,
                      comment='$comment',
                      browseable=$browsable,
                      public=$public
                      WHERE ID=$folderid");
        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
        $sock->REST_API_SAMBA("/folder/$folderid/reconfigure");
        return admin_tracks_post("Update Files server shared folder $sharename #$folderid");


    }

    $q->QUERY_SQL("INSERT INTO shared (localpath,enabled,sharename,readonly,comment,browseable,public) VALUES ('$localpath',$enabled,'$sharename',$readonly,'$comment',$browsable,$public)");

    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }

    $sock->REST_API_SAMBA("/instances/sync");



    return admin_tracks("Creating a Files server shared folder $localpath");
}


function search(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $t=time();
    $function_main=$_GET["function"];
    $topbuttons=array();

    $search=null;
    if(isset($_GET["search"])){$search=$_GET["search"];}

    $html[]="<table id='table-samba-shares' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{name}</th>";
    $html[]="<th>{size}</th>";
    $html[]="<th>{path}</th>";
    $html[]="<th>{instances}</th>";
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
    $tdStyle1R="style='width:1%;text-align:right' nowrap";
    $sock=new sockets();
    $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
    $results=$q->QUERY_SQL("SELECT * FROM shared ORDER BY sharename");

    foreach ($results as $index=>$ligne) {

        if($search<>null){
            if(!preg_match("#$search#i",serialize($ligne))){
                continue;
            }
        }
        $ID=$ligne["ID"];
        $localpath=$ligne["localpath"];
        $sharename=$ligne["sharename"];
        $enabled=$ligne["enabled"];
        $size=$ligne["foldersize"];
        $md=md5(serialize($ligne));
        $instances=td_instances($ID);
        $comment=$ligne["comment"];

        $delete=$tpl->icon_delete("Loadjs('$page?delete-folder-js=$ID&function=$function_main')","AsSambaAdministrator");

        if(strlen($ligne["comment"])>2){
            $comment="<div class='text-muted'><i>$comment</i></div>";
        }

        $sharename=$tpl->td_href($sharename,"","Loadjs('$page?folder-js=$ID&function=$function_main')");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><span id='shared-sharename-$ID'><i class='fas fa-folder'></i>&nbsp;$sharename$comment</span></td>";
        $html[]="<td $tdStyle1R><span id='shared-size-$ID'>$size</span></td>";
        $html[]="<td $tdStyle1 nowrap><span id='shared-localpath-$ID'>$localpath</span></td>";
        $html[]="<td><span id='shared-instances-$ID'>$instances</span></td>";
        $html[]="<td $tdStyle1 class='center' nowrap>$delete</td>";
        $html[]="</tr>";



    }
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?folder-js=0&function=$function_main')", "fas fa-folder-plus", "{new_shared_folder}");




    $TINY_ARRAY["TITLE"]="{shares_center}";
    $TINY_ARRAY["ICO"]=ico_directory;
    $TINY_ARRAY["EXPL"]="{SAMBA_BTRFS_SHARES}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function td_instances($ID):string{

    $q=new lib_sqlite("/home/artica/SQLITE/samba.db");
    $results=$q->QUERY_SQL("SELECT 
    link_shared.serviceid,
    instances.servicename 
    FROM link_shared,instances WHERE 
        link_shared.sharedid=$ID 
        AND instances.ID=link_shared.serviceid");
    $c=0;
    $serv=ico_server;
    $f=array();
    foreach ($results as $index=>$ligne) {
        $c++;
        $f[]="<div>";
        $f[]="<i class='$serv'></i>&nbsp;";
        $f[]=$ligne["servicename"];
        $f[]="</div>";
    }
    if($c==0){
        $f[]="<span class='text-danger'>{no_fs_instances}</span>";
    }

    return implode("",$f);
}
function td_servicename($class):string{
        $tpl=new template_admin();
        $text=$class->serviceName;
        $page=CurrentPageName();
        $function_main=$_GET["function"];
        return $tpl->td_href($text,null,"Loadjs('fw.samba.instances.php?info-container=$class->id&function-main=$function_main')");
}