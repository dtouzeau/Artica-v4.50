<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc');
    include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
    include_once(dirname(__FILE__).'/ressources/class.harmp.inc');

    $GLOBALS["HARMPTYPE"][0]="{none}";
    $GLOBALS["HARMPTYPE"][1]="{APP_NGINX}";

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}
    if(isset($_GET["host-event-search"])){host_events_search();exit;}
    if(isset($_GET["host-top-buttons"])){host_top_buttons();exit;}
    if(isset($_POST["update-agent"])){update_agent();exit;}
    if(isset($_POST["add-node"])){add_node_save();exit;}
    if(isset($_POST["add-ssh"])){add_ssh_save();exit;}
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}
    if(isset($_GET["system-clean"])){system_clean_js();exit;}
    if(isset($_POST["system-clean"])){system_clean_perform();exit;}
    if(isset($_GET["host-js"])){host_js();exit;}
    if(isset($_GET["host-status"])){host_status();exit;}
    if(isset($_GET["host-tab"])){host_tab();exit;}
    if(isset($_GET["host-parameters"])){host_parameters();exit;}
    if(isset($_POST["host-parameters"])){host_parameters_save();exit;}
    if(isset($_GET["update-js"])){update_agent_js();exit;}


    if(isset($_POST["RustDeskEncryptedOnly"])){section_global_save();exit;}

    if(isset($_GET["config-locked"])){config_locked();exit;}
    if(isset($_GET["group-js"])){group_js();exit;}
    if(isset($_GET["group-tab"])){group_tab();exit;}
    if(isset($_GET["group-popup"])){group_popup();exit;}
    if(isset($_POST["groupid"])){group_save();exit;}
    if(isset($_GET["host-events"])){host_events();exit;}
    if(isset($_GET["host-delete"])){host_delete();exit;}
    if(isset($_POST["host-delete"])){host_delete_perform();exit;}

    if(isset($_GET["add-node-js"])){add_node_js();exit;}
    if(isset($_GET["add-ssh-js"])){add_ssh_js();exit;}
    if(isset($_GET["add-ssh-popup"])){add_ssh_popup();exit;}



    if(isset($_GET["add-node-popup"])){add_node_popup();exit;}
    if(isset($_GET["nodes-list"])){nodes_list();exit;}

    if(isset($_POST["DockerExportTime"])){section_export_save();exit;}
    if(isset($_POST["WorkDir"])){section_workingdir_save();exit;}
	
page();

function add_node_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=intval($_GET["add-node-js"]);
    $function=$_GET["function"];
    return $tpl->js_dialog2("{new_node}","$page?add-node-popup=$gpid&function=$function");
}

function update_agent_js():bool{

    $groupid=intval($_GET["update-js"]);
    $tpl=new template_admin();
    $function=$_GET["function"];
    $jsafter=$tpl->framework_buildjs(
        "hamrp.php?update-agent=$groupid","harmp.connect.progress","harmp.connect.log","progress-harmp-restart",
        "$function()"
    );
    $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
    return $tpl->js_confirm_execute("{update_agent} v$ARTICAREST_VERSION","update-agent",$groupid,$jsafter);


}
function update_agent():bool{
    $groupid=$_POST["update-agent"];
    $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
    return admin_tracks("Update Agent v$ARTICAREST_VERSION of group #$groupid");
}

function host_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uuid=$_GET["host-js"];
    $harmpnode=new harmpnode($uuid);
    $hostname=$harmpnode->hostname;
    $function=$_GET["function"];
    return $tpl->js_dialog2("$hostname","$page?host-tab=$uuid&function=$function");
}
function host_tab():bool{
    $page=CurrentPageName();
    $uuid=$_GET["host-tab"];
    $function=$_GET["function"];
    $tpl=new template_admin();
    $array["{status}"]="$page?host-status=$uuid&function=$function";
    $array["{parameters}"]="$page?host-parameters=$uuid&function=$function";
    $array["{events}"]="$page?host-events=$uuid&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}
function host_events(){
    $page=CurrentPageName();
    $uuid=$_GET["host-events"];
    $tpl=new template_admin();
    echo "<div style='margin-top:5px'>";
    echo $tpl->search_block($page,null,null,null,"&host-event-search=$uuid");
    echo "</div>";
}
function host_events_search():bool{
    $uuid=$_GET["host-event-search"];
    $tpl=new template_admin();
    $MAIN       = $tpl->format_search_protocol($_GET["search"]);
    $MAIN["uuid"]=$uuid;
    $line       = base64_encode(serialize($MAIN));
    $target     = PROGRESS_DIR."/$uuid.articarest.search";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hamrp.php?adrest-events=$line");

    $f=explode("\n",@file_get_contents($target));
    krsort($f);

    $tooltips["paused"]="<label class='label label-warning'>{paused}</label>";
    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["warn"]="<label class='label label-warning'>{warn}</label>";
    $tooltips["error"]="<label class='label label-danger'>{error}</label>";

    $text["error"]="text-danger";
    $text["warn"]="text-warning font-bold";

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{level}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    foreach ($f as $line){
        $textclass=null;
        $json=json_decode($line);
        if(!property_exists($json,"level")){continue;}

        $level=$json->level;
        $FTime=$tpl->time_to_date($json->time,true);
        $level_label="<label class='label label-default'>$level</label>";
        $message=$json->message;
        if(isset($tooltips[$level])){
            $level_label=$tooltips[$level];
        }
        if(isset($text[$level])){
            $textclass=$text[$level];
        }

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$textclass'>$FTime</td>
				<td style='width:1%;' nowrap class='$textclass'>$level_label</td>
    			<td class='$textclass'>$message</td>
				</tr>";

    }
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

    return true;
}

function host_status():bool{
    $uuid=$_GET["host-status"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $harmpnode=new harmpnode($uuid);
    $tpl->table_form_field_text("{OS}","$harmpnode->DistributionName",ico_infoi);

    if($harmpnode->nodetype==1){
        if(strlen($harmpnode->NginxVersion)>1){
            $harmpnode->NginxVersion="v$harmpnode->NginxVersion";
        }
        $tpl->table_form_field_text("{type}",$GLOBALS["HARMPTYPE"][$harmpnode->nodetype]." $harmpnode->NginxVersion",ico_infoi);
        if($harmpnode->NginxRun>0){
            $tpl->table_form_field_text("{running}","PID: $harmpnode->NginxRun",ico_run);
        }else{
            $tpl->table_form_field_text("{stopped}","{service_stopped}",ico_stop,true);
        }

    }

    $html[]="<div style='margin-top:15px'>";
    $html[]="<div id='top-buttons-$uuid'></div>";
    $html[]=$tpl->_ENGINE_parse_body($tpl->table_form_compile())."</div>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('top-buttons-$uuid','$page?host-top-buttons=$uuid')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function add_ssh_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gpid=intval($_GET["add-ssh-js"]);
    $function=$_GET["function"];
    return $tpl->js_dialog2("{SSH_deployment}","$page?add-ssh-popup=$gpid&function=$function");
}
function host_delete():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uuid=$_GET["host-delete"];
    $harmpnode=new harmpnode($uuid);
    $hostname=$harmpnode->hostname;
    $md=$_GET["md"];
    return $tpl->js_confirm_delete("$hostname","host-delete",$uuid,"$('#$md').remove();");
}
function host_delete_perform():bool{
    $uuid=$_POST["host-delete"];
    $harmpnode=new harmpnode($uuid);
    $harmpnode->DeleteNode();
    return admin_tracks("Remove the managed reverse-proxy host $harmpnode->hostname");
}

function host_parameters():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uuid=$_GET["host-parameters"];
    $harmpnode=new harmpnode($uuid);
    $function = $_GET["function"];
    $groupid=$harmpnode->groupid;
    $form[]=$tpl->field_hidden("host-parameters",$uuid);
    $form[]=$tpl->field_text("hostname","{hostname}",$harmpnode->hostname);


    $refresh = "LoadAjaxSilent('nodesFor$groupid','$page?nodes-list=$groupid&function=$function');dialogInstance2.close();";
    $jsafter=$tpl->framework_buildjs("hamrp.php?sync-singlenode=$uuid",
        "harmp.connect.progress","harmp.connect.log","sync-$uuid",$refresh);


    $form[]=$tpl->field_array_hash($GLOBALS["HARMPTYPE"],"nodetype","{type}",$harmpnode->nodetype);


    $html[]="<div id='sync-$uuid'></div>";
    $html[]=$tpl->form_outside("", $form, null, "{add}", $jsafter);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function host_top_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $UPDATES_ARRAY  = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo")));
    $uuid=$_GET["host-top-buttons"];
    $harmpnode=new harmpnode($uuid);
    $NodeType=$harmpnode->nodetype;

    if($NodeType==1){
        $BT_UPRADE=false;
        $nginx_version=$harmpnode->NginxVersion;
        if(strlen($nginx_version)>3) {
            $BT_UPRADE=true;
            $AVAILABLE_VER = $tpl->LATEST_AVAILABLE_VERSION($UPDATES_ARRAY, "APP_NGINX", $nginx_version);
            if ($AVAILABLE_VER > 0) {
                $NewVer = $UPDATES_ARRAY["APP_NGINX"][$AVAILABLE_VER]["VERSION"];
                $topbuttons[] = array("Loadjs('fw.system.upgrade-software.php?product=APP_NGINX&jQueryLjs=yes&uuid=$uuid')", ico_cd, "{upgrade} v$NewVer");
            }
        }
        if(!$BT_UPRADE){
            if(isset($UPDATES_ARRAY["APP_NGINX"])) {
                $topbuttons[] = array("Loadjs('fw.system.upgrade-software.php?product=APP_NGINX&jQueryLjs=yes&uuid=$uuid')", ico_cd, "{install_upgrade}");
            }
        }
    }

    echo $tpl->table_buttons($topbuttons);
    return true;
}

function host_parameters_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $harmpnode=new harmpnode($_POST["host-parameters"]);
    foreach ($_POST as $key=>$value){
        if(property_exists($harmpnode,$key)){
            $harmpnode->$key=$value;
        }
    }

    if(!$harmpnode->SaveSyncSettings()){
        $tpl->post_error($harmpnode->mysql_error);
        return false;
    }


    return admin_tracks_post("Modify Remote node parameters");

}

function add_node_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=$_GET["add-node-popup"];
    $form[]=$tpl->field_hidden("add-node",$gpid);
    $form[]=$tpl->field_checkbox("UseSSL","{UseSSL}",0);
    $form[]=$tpl->field_ipaddr("ipaddr","{remote_address}",null,true);
    $form[]=$tpl->field_numeric("port","{remote_port}",9503);
    $function=$_GET["function"];

    $jsafter=$tpl->framework_buildjs(
        "hamrp.php?new-node=yes","harmp.connect.progress","harmp.connect.log","progress-harmp-restart",
        "dialogInstance2.close();$function()"
    );

    echo $tpl->form_outside("{new_node}",$form,null,"{add}",$jsafter);
    return true;
}

function add_ssh_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $gpid = $_GET["add-ssh-popup"];
    $form[] = $tpl->field_hidden("add-ssh", $gpid);
    $form[] = $tpl->field_ipaddr("SERVER", "SSH {remote_address}", null, true);
    $form[] = $tpl->field_numeric("PORT", "SSH {remote_port}", 22);
    $form[] = $tpl->field_text("USERNAME", "SSH {username}", null, true);
    $form[] = $tpl->field_password("PASSWORD", "SSH {password}", null, true);
    $form[] = $tpl->field_numeric("RPORT", "Web API {remote_port}", 9503);


    $function = $_GET["function"];

    $jsafter = $tpl->framework_buildjs(
        "hamrp.php?new-ssh=yes", "SSHDeployAgent.progress", "SSHDeployAgent.progress.log", "progress-harmp-restart",
        "dialogInstance2.close();$function()"
    );

    echo $tpl->form_outside("", $form, null, "{add}", $jsafter);
    return true;
}
function add_node_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HarmpNewNode",base64_encode(serialize($_POST)));
    return admin_tracks_post("Create a new HaRMP node");
}
function add_ssh_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDeployAgent",serialize($_POST));
    $USERNAME=$_POST["USERNAME"];
    $SERVER=$_POST["SERVER"];
    $GROUPID=intval($_POST["add-ssh"]);
    if($GLOBALS==0){
        $tpl->post_error("Group IP not set!");
        return false;
    }
    return admin_tracks_post("Deploy via SSH a new node $USERNAME@$SERVER");
}

function system_clean_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?clean-system=yes");
    return admin_tracks("Clean docker prune system cache");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html=$tpl->page_header("{managed_nodes}",
        ico_load_balancer,
        "{APP_HAMRP_ABOUT}",
        "$page?table=yes","hamrp-nodes","progress-harmp-restart",false,"table-hamrp-nodes");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {APP_HAMRP} status",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    echo "<div id='hamrp-nodes' style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}

function search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $t=time();
    $topbuttons=array();
    $function_main=$_GET["function"];
    $t=time();


    $html[]="<table id='table-fireqos-$t' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1%></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{groupname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1%>{actions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();

    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["running"]="<label class='label label-primary'>{running}</label>";


    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $sql="SELECT * FROM groups";
    $results=$q->QUERY_SQL($sql);

    foreach ($results as $ligne){

        $id=md5(serialize($ligne["groupname"]));

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($ligne);
        $groupname=$ligne["groupname"];
        $groupid=$ligne["ID"];
        $topbuttons=array();

        $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
        $buton=$tpl->icon_delete("Loadjs('$page?delete-volume-js=$NameEnc&function-main=$function_main&md=$md')","AsDockerAdmin");
        if($Ports<>null){$Ports="($Ports)";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><li class='".ico_group."'></li></td>";

        $groupname=$tpl->td_href($groupname,null,"Loadjs('$page?group-js=$groupid')");
        $refreshNodesGroup=$tpl->framework_buildjs("hamrp.php?refresh-group=$groupid",
            "harmp.refresh.$groupid.progress","harmp.refresh.$groupid.log","progress-harmp-restart",
        "LoadAjaxSilent('nodesFor$groupid','$page?nodes-list=$groupid&function=$function_main');","LoadAjaxSilent('nodesFor$groupid','$page?nodes-list=$groupid&function=$function_main');");

        $html[]="<td width='1%'>&nbsp;&nbsp;<strong>$groupname</strong></td>";
        $html[]="<td width='99%' nowrap>";
        $topbuttons[] = array("Loadjs('$page?add-node-js=$groupid&function=$function_main');", ico_plus, "{new_node}");
        $topbuttons[] = array("Loadjs('$page?add-ssh-js=$groupid&function=$function_main');", ico_computer_ssh, "{SSH_deployment}");
        $topbuttons[] = array("Loadjs('$page?update-js=$groupid&function=$function_main');", ico_download, "{update} v.$ARTICAREST_VERSION");
        $topbuttons[] = array($refreshNodesGroup, ico_refresh, "{refresh}");


        $html[]=$tpl->_ENGINE_parse_body( $tpl->th_buttons($topbuttons));
        $html[]="</td>";
        $html[]="<td width='1%' class='center' nowrap>$buton</td>";
        $html[]="</tr>";
        
        $html[]="<tr class='$TRCLASS' id='{$md}2'>";
        $html[]="<td width='1%' nowrap>&nbsp;</td>";
        $html[]="<td colspan=3><div id='nodesFor$groupid'></div></td>";
        $js[]="LoadAjaxSilent('nodesFor$groupid','$page?nodes-list=$groupid&function=$function_main');";

    }

    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM hamrp WHERE groupid=0");
    if(intval($ligne["tcount"])>0) {
        $md = md5("unknown");
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td width='1%'><li class='" . ico_group . "'></li></td>";
        $html[] = "<td width='1%'>&nbsp;&nbsp;<strong>{unknown}</strong></td>";
        $html[] = "<td width='99%' nowrap>&nbsp;</td>";
        $html[] = "</tr>";
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd";
        }
        $html[] = "<tr class='$TRCLASS' id='{$md}2'>";
        $html[] = "<td width='1%' nowrap>&nbsp;</td>";
        $html[] = "<td colspan=3><div id='nodesFor0'></div></td>";
        $js[] = "LoadAjaxSilent('nodesFor0','$page?nodes-list=0&function=$function_main');";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";


    $TINY_ARRAY["TITLE"]="{managed_nodes}";
    $TINY_ARRAY["ICO"]=ico_computer;
    $TINY_ARRAY["EXPL"]="{APP_HAMRP_ABOUT}";
    $topbuttons=array();
    if($users->AsWebMaster) {
        $topbuttons[] = array("Loadjs('$page?group-js=0&function=$function_main')", ico_plus, "{new_group}");
    }
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=@implode("\n",$js);
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}



function section_change_key_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=$_GET["t"];
   return $tpl->js_dialog("{key}: {change}","$page?change-key-popup=yes&t=$t");
}
function group_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["group-js"]);
    $function=$_GET["function"];
    $title="{group} #$groupid";
    if($groupid==0){
        $title="{new_group}";
    }
    return $tpl->js_dialog1($title,"$page?group-tab=$groupid&function=$function");
}
function group_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["group-tab"]);
    $function=$_GET["function"];
    $title="{new_group}";
    if($ID>0){
        $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
        $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID='$ID'");
        $title=$ligne["groupname"];
    }
    $array[$title]="$page?group-popup=$ID&function=$function";
    if($ID>0){
        $array["{privileges}"]="$page?group-privileges=yes";
    }

    echo $tpl->tabs_default($array);
    return true;
}
function group_popup(){
    $id     = intval($_GET["group-popup"]);
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->CLUSTER_CLI=true;
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $name   = null;
    $comment= null;
    $function=$_GET["function"];
    $title  = "{new_group}";
    $btname = "{add}";
    $jsafter= "dialogInstance1.close();$function()";

    if(!$q->FIELD_EXISTS("groups","EnableRedis")){
        $q->QUERY_SQL("ALTER TABLE groups ADD EnableRedis INTEGER NOT NULL DEFAULT 0");
    }

    if($id>0) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM groups WHERE ID='$id'");
        $groupname       = $ligne["groupname"];
        $comment    = $ligne["comment"];
        $title      = $groupname;
        $btname     = "{apply}";
    }
    $tpl->field_hidden("groupid",$id);
    $form[]=$tpl->field_text("groupname","{groupname}",$name,true);
    $form[]=$tpl->field_text("comment","{comment}",$comment,false);
    echo $tpl->form_outside($title, $form,null,$btname,$jsafter,"AsSystemAdministrator");
}
function group_save():bool{
    $tpl=new template_admin();
    $array=$tpl->CLEAN_POST("groupid");
    $ID=intval($_POST["groupid"]);

    $sql="UPDATE groups SET ".@implode(",",$array["EDIT"])." WHERE ID=$ID";
    $sql_add="INSERT INTO groups (".@implode(",",$array["FIELDS_ADD"]).") VALUES (".@implode(",",$array["VALUES_ADD"]).")";

    if($ID==0){
        $sql=$sql_add;
    }
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error."<br>$sql_add");
        return false;
    }
    return admin_tracks_post("Saving HaMRP Group");

}
function nodes_list():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $groupid=intval($_GET["nodes-list"]);
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");

    $sql="SELECT * FROM hamrp WHERE groupid={$groupid} ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);
    $TRCLASS=null;

    $html[]="<table id='table-nodeslist-$groupid' class=\"table-stripped\">";
    $html[]="<tbody>";
    foreach ($results as $ligne){

        $id=md5(serialize($ligne));

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($ligne);
        $uuid=$ligne["uuid"];
        $nodename=$ligne["nodename"];
        $nodetype=$ligne["nodetype"];
        $groupid=$ligne["groupid"];
        $ipaddr=$ligne["ipaddr"];
        $port=$ligne["port"];
        $enabled=$ligne["enabled"];
        $hostname=$ligne["hostname"];
        $status=$ligne["status"];
        $lastsaved=$ligne["lastsaved"];
        $zOrder=$ligne["zOrder"];
        $cpu=$ligne["cpu"];
        $mem=$ligne["mem"];
        $version=$ligne["version"];
        $kernel=$ligne["kernel"];

        $NodStatus=NodStatus($ligne);
        $del=$tpl->icon_delete("Loadjs('$page?host-delete=$uuid&md=$md')","AsWebMaster");
        $hostname=$tpl->td_href($hostname,null,"Loadjs('$page?host-js=$uuid&md=$md')");
        $html[]="<tr class='$TRCLASS' id='$md' style='height:35px'>";
        $html[]="<td width='1%' nowrap><li class='".ico_computer."'></li>&nbsp;&nbsp;$hostname&nbsp;($ipaddr)</td>";
        $html[]="<td width='1%' nowrap>$NodStatus</td>";
        $html[]="<td width='1%' nowrap>$version</td>";
        $html[]="<td width='1%' nowrap>$kernel</td>";
        $html[]="<td width='1%' nowrap>{cpu} $cpu</td>";
        $html[]="<td width='1%' nowrap>{memory} {$mem}MB</td>";
        $html[]="<td width='1%' class='center' nowrap>$del</td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function NodStatus($ligne):string{

    $nodetype=$ligne["nodetype"];
    if($nodetype==0){
        return "<span class='label label-default'>{not_defined}</span>";
    }

    if($nodetype==1){
        $NginxVersion=trim($ligne["NginxVersion"]);
        if($NginxVersion==null){
            return "<span class='label label-danger'>{NOT_INSTALLED}</span>";
        }
        $NginxRun=intval($ligne["NginxRun"]);
        if($NginxRun==0){
            return "<span class='label label-danger'>{stopped}</span>";
        }


        return "<span class='label label-primary'>{APP_NGINX}</span>";
    }

    return "<span class='label label-default'>{unknown}</span>";
}

function section_js():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxTiny('docker-config-locked','$page?docker-config-locked=yes');";
}







function services_status():bool{

    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $page=CurrentPageName();
    $status_file=PROGRESS_DIR."/rustdesk.status";
    $t=intval($_GET["service-status"]);
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("rustdesk.php?status=yes");
	$ini->loadFile($status_file);
    $jsrestart=$tpl->framework_buildjs("rustdesk.php?restart=yes",
        "rustdesk.restart.progress","rustdesk.restart.progress.log",
        "progress-harmp-restart","LoadAjaxTiny('$t-status','$page?service-status=$t');",null,null,"AsFirewallManager");

    $html[]=$tpl->SERVICE_STATUS($ini, "APP_RUSTDESKBBS",$jsrestart);
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_RUSTDESKBBR",$jsrestart);
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function GetKeys():array{
    $Data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskKeys");
    if(!is_null($Data)){
        if(strlen($Data)>6){
            $sMain=unserialize(base64_decode($Data));
            if(isset($sMain["PUBKEY"])){
                $PUBKEY=$sMain["PUBKEY"];
                $SECKEY=$sMain["SECKEY"];
                return array($PUBKEY,$SECKEY);
            }
        }
    }
   return array("","");
}

function services_toolbox():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $wbutton[0]["name"]="{online_help}:wiki";
    $wbutton[0]["icon"]="fa-solid fa-square-question";
    $wbutton[0]["js"]="s_PopUpFull('https://wiki.articatech.com/en/network/remote-control/rustdesk',1024,768,'Wiki');";


    $topbuttons=array();
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    if($FireHolEnable==0){
        $FirewallWidget=$tpl->widget_vert("Firewall","{disabled}",$wbutton,ico_firewall);
    }else{
        $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligne = $q->mysqli_fetch_array("SELECT ID,rulename FROM iptables_main 
                WHERE enabled='1' AND service='RustDesk' AND accepttype='ACCEPT'");
        if(!isset($ligne["ID"])){$ligne["ID"]=0;}
        if(!isset($ligne["rulename"])){$ligne["rulename"]="";}
        $ID=intval($ligne["ID"]);
        if($ID==0){
            $FirewallWidget=$tpl->widget_rouge("Firewall","{not_set}",$wbutton,ico_firewall);
        }else{
            $FirewallWidget=$tpl->widget_vert("{$ligne["rulename"]}","{defined}",$wbutton,ico_firewall);
        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/rustdesk.db");
    $Rows=$q->COUNT_ROWS("peer");

    list($PUBKEY,$SECKEY)=GetKeys();
    if($Rows==0){
        $ClientWidget=$tpl->widget_grey("{clients}","{none}",$wbutton,ico_laptop_down);

    }else{
        $ClientWidget=$tpl->widget_vert("{clients}",$Rows,$wbutton,ico_laptop_down);
    }

    if(strlen($PUBKEY)==0){
            $btn[0]["js"] = "Loadjs('$page?change-key-js=yes');";
            $btn[0]["name"] = "{change}";
            $btn[0]["icon"] = ico_key;
            $KeyWidget=$tpl->widget_rouge("{key}","{error}",$btn);

    }else{

            $btn[0]["js"] = "Loadjs('$page?change-key-js=yes');";
            $btn[0]["name"] = "{change}";
            $btn[0]["icon"] = ico_key;

        $KeyWidget=$tpl->widget_vert("{key}","{success}",$btn,ico_key);
    }


    $TINY_ARRAY["TITLE"]="{APP_HAMRP} &raquo;&raquo; {status}";
    $TINY_ARRAY["ICO"]=ico_load_balancer;
    $TINY_ARRAY["EXPL"]="{APP_HAMRP_ABOUT}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$ClientWidget</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$KeyWidget</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$FirewallWidget</td>";
    $html[]="</tr>";
    $html[]="<table>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}








