<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["link-member"])){link_member_save();exit;}
if(isset($_POST["userid"])){user_save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["group-tab"])){group_tab();exit;}
if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-delete"])){group_delete();exit;}
if(isset($_POST["group-delete"])){group_delete_perform();exit;}
if(isset($_GET["group-params"])){group_params();exit;}
if(isset($_GET["group-members"])){group_members_start();exit;}
if(isset($_GET["groups-members-table"])){group_members_table();exit;}
if(isset($_POST["gpid"])){group_save();exit;}
if(isset($_GET["user-params"])){user_params();exit;}
if(isset($_GET["user-tab"])){user_tab();exit;}
if(isset($_GET["user-js"])){user_js();exit;}

if(isset($_GET["user-groups"])){user_groups_start();exit;}
if(isset($_GET["user-groups-table"])){user_groups_table();exit;}
if(isset($_GET["link-group"])){link_user_to_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_user_to_group_popup();exit;}
if(isset($_GET["after-new-group"])){group_save_after();exit;}


if(isset($_GET["link-user"])){link_group_to_user_js();exit;}
if(isset($_GET["link-user-popup"])){link_group_to_user_popup();exit;}

if(isset($_GET["user-delete"])){user_delete_js();exit;}
if(isset($_POST["user-delete"])){user_delete();exit;}

if(isset($_GET["unlink"])){unlink_js();exit;}
if(isset($_POST["unlink"])){unlink_save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $bt[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $bt[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?group-js=0');\">
			<i class='fad fa-users-crown'></i> {new_group} </label>";
    $bt[]="</div>";
    $html=$tpl->page_header("{local_administrators}",
        "fad fa-user-crown","{local_administrators_explain}".@implode("\n",$bt)."",
        "$page?table=yes","admins","table-loader-progress",false,"table-loader-localadmins");

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{local_administrators}",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function unlink_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["unlink"]);
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$ligne=$q->mysqli_fetch_array("SELECT userid,groupid FROM `lnk`WHERE ID='$ID'");
	
	$gpid=$ligne["groupid"];
	$userid=$ligne["userid"];
	
	$ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID='$gpid'");
	$groupname=$ligne["groupname"];
	$ligne=$q->mysqli_fetch_array("SELECT username FROM `users` WHERE ID='$userid'");
	$username=$ligne["username"];
	
	$text="{unlink} <strong>{$username}</strong> {from} <strong>{$groupname}</strong>";
	
	$jsafter[]="LoadAjax('table-loader-localadmins','$page?table=yes');";
	$jsafter[]="LoadAjax('groups-members-$gpid','$page?groups-members-table=$gpid');";
	$jsafter[]="LoadAjax('groups-list-$userid','$page?user-groups-table=$userid');";
	$tpl->js_confirm_execute($text, "unlink", $ID,@implode("",$jsafter));
	
	
}

function group_delete(){
    $tpl=new template_admin();
    $gpid=intval($_GET["group-delete"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID='$gpid'");
    $title=$ligne["groupname"];
    $tpl->js_confirm_delete($title,"group-delete",$gpid,"$('#$md').remove();");

}

function group_delete_perform(){
    header("content-type: application/x-javascript");
    $gpid=intval($_POST["group-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    $q->QUERY_SQL("DELETE FROM lnk WHERE groupid=$gpid");
    $q->QUERY_SQL("DELETE FROM groups WHERE ID=$gpid");
}


function user_delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$userid=intval($_GET["user-delete"]);
	$gpid=intval($_GET["gpid"]);
	$ligne=$q->mysqli_fetch_array("SELECT username FROM `users` WHERE ID='$userid'");
	$username=$ligne["username"];
	
	$jsafter[]="LoadAjax('table-loader-localadmins','$page?table=yes');";
	if($gpid>0){$jsafter[]="LoadAjax('groups-members-$gpid','$page?groups-members-table=$gpid');";}
	$jsafter[]="LoadAjax('groups-list-$userid','$page?user-groups-table=$userid');";
	$tpl->js_confirm_delete($username, "user-delete", $userid,@implode("", $jsafter));
}

function user_delete(){
	$userid=intval($_POST["user-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$q->QUERY_SQL("DELETE FROM `users` WHERE ID=$userid");
	if(!$q->ok){echo "$q->mysql_error";return;}
	$q->QUERY_SQL("DELETE FROM `lnk` WHERE userid=$userid");
	if(!$q->ok){echo "$q->mysql_error";return;}
}



function unlink_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_POST["unlink"]);
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$q->QUERY_SQL("DELETE FROM lnk WHERE ID='$ID'");
}

function group_members_start(){
	$ID=$_GET["group-members"];
	$page=CurrentPageName();
	echo "<div id='groups-members-$ID'></div><script>LoadAjax('groups-members-$ID','$page?groups-members-table=$ID');</script>";
}
function user_groups_start(){
	$ID=$_GET["user-groups"];
	$page=CurrentPageName();
	echo "<div id='groups-list-$ID'></div><script>LoadAjax('groups-list-$ID','$page?user-groups-table=$ID');</script>";
}

function group_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["group-js"]);
	$title="{new_group}";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
		$ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID='$ID'");
		$title=$ligne["groupname"];
	}
	$tpl->js_dialog1($title, "$page?group-tab=$ID");
}
function user_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["user-js"]);
	$title="{new_member}";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
		$ligne=$q->mysqli_fetch_array("SELECT username FROM `users` WHERE ID='$ID'");
		$title=$ligne["username"];
	}
	$tpl->js_dialog3($title, "$page?user-tab=$ID&gpid={$_GET["gpid"]}");
}
function link_user_to_group_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$userid=$_GET["link-group"];
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$ligne=$q->mysqli_fetch_array("SELECT username FROM `users` WHERE ID='$userid'");
	$title=$ligne["username"];
	$tpl->js_dialog4("$title: {link_group}", "$page?link-group-popup=$userid");
}
function link_group_to_user_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$groupid=$_GET["link-user"];
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$ligne=$q->mysqli_fetch_array("SELECT groupname FROM `groups` WHERE ID='$groupid'");
	$title=$ligne["groupname"];
	$tpl->js_dialog4("$title: {link_user}", "$page?link-user-popup=$groupid");
}
function link_group_to_user_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$groupid=$_GET["link-user-popup"];
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$jsafter="dialogInstance4.close();LoadAjax('groups-members-$groupid','$page?groups-members-table=$groupid');;LoadAjax('table-loader-localadmins','$page?table=yes');";
	$ligne=$q->mysqli_fetch_array("SELECT groupname FROM `groups` WHERE ID='$groupid'");
	$title=$ligne["groupname"].": {link_user}";
	
	$sql="SELECT userid FROM `lnk` WHERE groupid='$groupid'";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$RD[$ligne["userid"]]=true;
	
	}
	$sql="SELECT ID,username FROM `users` ORDER BY username";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if(isset($RD[$ligne["ID"]])){continue;}
		$HASH[$ligne["ID"]]=$ligne["username"];
	
	}
	$form[]=$tpl->field_hidden("groupid", $groupid);
	$form[]=$tpl->field_array_hash($HASH,"link-member", "{member}", null);
	$html[]=$tpl->form_outside($title, $form,null,"{link}",$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}


function link_user_to_group_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$userid=$_GET["link-group-popup"];
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$ligne=$q->mysqli_fetch_array("SELECT username FROM `users` WHERE ID='$userid'");
	$title=$ligne["username"].": {link_group}";
	$jsafter="dialogInstance4.close();LoadAjax('groups-list-$userid','$page?user-groups-table=$userid');LoadAjax('table-loader-localadmins','$page?table=yes');";
	
	
	$sql="SELECT groupid  FROM `lnk` WHERE userid='$userid'";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$RD[$ligne["groupid"]]=true;
	
	}
	
	$sql="SELECT ID,groupname FROM `groups` ORDER BY groupname";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if(isset($RD[$ligne["ID"]])){continue;}
		$HASH[$ligne["ID"]]=$ligne["groupname"];
		
	}
	$form[]=$tpl->field_hidden("link-member", $userid);
	$form[]=$tpl->field_array_hash($HASH,"groupid", "{groups2}", null);
	$html[]=$tpl->form_outside($title, $form,null,"{link}",$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}
function link_member_save(){
	$userid=intval($_POST["link-member"]);
	$groupid=intval($_POST["groupid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$q->QUERY_SQL("INSERT INTO `lnk` (userid,groupid) VALUES ($userid,$groupid)");
	if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";return;}
}


function user_tab(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["user-tab"]);
	$title="{new_member}";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
		$ligne=$q->mysqli_fetch_array("SELECT username FROM `users` WHERE ID='$ID'");
		$title=$ligne["username"];
	}
	$array[$title]="$page?user-params=$ID&gpid={$_GET["gpid"]}";
	if($ID>0){
		$array["{groups2}"]="$page?user-groups=$ID";

        $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
        if($LighttpdArticaClientAuth==1){
            $sarray["uid"]=$ligne["username"];
            $sarray["type"]="LOCAL";
            $sarray["displayname"]=$ligne["username"];
            $sdata=urlencode(base64_encode(serialize($sarray)));
            $array["{client_certificate}"]="fw.member.client.cert.php?sdata=$sdata";
        }
    }
	echo $tpl->tabs_default($array);
}
function group_tab():bool{

    $EnableNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $icon_group=ico_group;
    $icon_user=ico_users;
    $icon_privs=ico_lock;
    $icon_earth=ico_earth;

    $page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["group-tab"]);
    $EnableManagedReverseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableManagedReverseProxy"));
	$title="{new_group}";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
		$ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID='$ID'");
		$title=$ligne["groupname"];
	}
	$array["<i class='$icon_group'></i> $title"]="$page?group-params=$ID";
	if($ID>0){
        $array["<i class='$icon_user'></i> {members}"]="$page?group-members=$ID";

        if(strtolower($title)<>'administrators') {
            $array["<i class='$icon_privs'></i> {privileges}"]="fw.groups.ad.php?privileges=sqlite:$ID";
        }

        if($EnableManagedReverseProxy==1){
            $array["<i class='$icon_earth'></i> {APP_HAMRP}"]="fw.groups.ad.php?privileges-hamrp=sqlite:$ID";
        }
        if($EnableNginx==1){
            $array["<i class='$icon_earth'></i> {websites}"]="fw.nginx.privileges.php?gpid=sqlite:$ID";
        }
	}

	echo $tpl->tabs_default($array);
    return true;
}
function user_params(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title="{new_member}";
	$ID=intval($_GET["user-params"]);
	$gpid=intval($_GET["gpid"]);
	$btname="{add}";
	$ligne["enabled"]=1;
	$jsafter="dialogInstance3.close();LoadAjax('table-loader-localadmins','$page?table=yes');LoadAjax('groups-members-$gpid','$page?groups-members-table=$gpid');";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM `users` WHERE ID='$ID'");
		$title=$ligne["username"];
		$btname="{apply}";
	
	}
    if(!isset($ligne["username"])){$ligne["username"]=null;}
	$form[]=$tpl->field_hidden("groupid", $gpid);
	$form[]=$tpl->field_hidden("userid", $ID);
	if($ID>0){$form[]=$tpl->field_checkbox("enabled","{enabled}",intval($ligne["enabled"]),true);}
	$form[]=$tpl->field_text("username", "{username}", $ligne["username"]);
	$form[]=$tpl->field_password2("password", "{password}", null,true);
	$html[]=$tpl->form_outside($title, $form,null,$btname,$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}

function user_save():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["userid"]);
	$groupid=intval($_POST["groupid"]);
	
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$passmd5=md5(trim($_POST["password"]));

    $_POST["username"]=$tpl->CLEAN_BAD_XSS($_POST["username"]);
	$username=sqlite_escape_string2($_POST["username"]);

	if($ID==0){
		if($groupid==0){echo $tpl->post_error("No Group ID!!!");return false;}
		$sql="INSERT INTO `users` (username,passmd5,enabled) VALUES ('$username','$passmd5',1)";
		$q->QUERY_SQL($sql);

        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
		$ligne=$q->mysqli_fetch_array("SELECT ID FROM `users` WHERE username='$username' and passmd5='$passmd5'");
		if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
		$userid=intval($ligne["ID"]);
		if($userid==0){echo $tpl->post_error("ID = 0 {for} $username...");return false;}

        $sql="INSERT INTO lnk (userid,groupid) VALUES ('$userid','$groupid')";
		$q->QUERY_SQL($sql);

        if(!$q->ok){
            echo $tpl->post_error($q->mysql_error);
            return false;
        }
	    return admin_tracks("Creating Local administrator account $username");
    }

	$sql="UPDATE `users` SET username='$username',passmd5='$passmd5' WHERE ID='$ID'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){$tpl=new template_admin();
        echo $tpl->post_error($q->mysql_error);
        return false;
    }

    return admin_tracks("Updating Local administrator account $username ID=$ID");
}

function group_params(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    unset($_SESSION["SYSTEM_USERS_GROUP_CREATED"]);
	$title="{new_group}";
	$ID=intval($_GET["group-params"]);
	$btname="{add}";
	$ligne["enabled"]=1;
	$jsafter="dialogInstance1.close();LoadAjax('table-loader-localadmins','$page?table=yes');";
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
		$ligne=$q->mysqli_fetch_array("SELECT groupname,enabled FROM groups WHERE ID='$ID'");
		$title=$ligne["groupname"];
		$btname="{apply}";
		
	}

    if($ID==0){
        $jsafter=$jsafter."Loadjs('$page?after-new-group=yes');";
    }

	$form[]=$tpl->field_hidden("gpid", $ID);
	if($ID>0){$form[]=$tpl->field_checkbox("enabled","{enabled}",intval($ligne["enabled"]),true);}
	$form[]=$tpl->field_text("groupname", "{groupname}", $ligne["groupname"]);
	$html[]=$tpl->form_outside($title, $form,null,$btname,$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

function group_save(){

	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["gpid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    $_POST["groupname"]=$tpl->CLEAN_BAD_XSS($_POST["groupname"]);
	$groupname=sqlite_escape_string2($_POST["groupname"]);
	if($ID==0){
		$sql="INSERT INTO `groups` (groupname,enabled) VALUES('$groupname',1)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";}
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM groups WHERE groupname='$groupname'");
        $_SESSION["SYSTEM_USERS_GROUP_CREATED"]=intval($ligne["ID"]);

		return;
	}
	
	$sql="UPDATE `groups` SET `groupname`='$groupname', enabled='{$_POST["enabled"]}' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl=new template_admin();$q->mysql_error=$tpl->javascript_parse_text($q->mysql_error);echo "jserror:$q->mysql_error";}
}
function group_save_after():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $ID=$_SESSION["SYSTEM_USERS_GROUP_CREATED"];
    echo "Loadjs('$page?group-js=$ID');";
    return true;
}


function table(){
	
	
	$tpl=new template_admin();
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$page=CurrentPageName();

	
	
	$html[]="<table id='table-admins' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups2}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{members}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;

	$results=$q->QUERY_SQL("SELECT * FROM groups ORDER BY groupname");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$style=null;
		$groupname=$ligne["groupname"];
		if($groupname==null){$groupname="{unknown}";}
		$ID=$ligne["ID"];
		$enabled=$ligne["enabled"];
		if($enabled==0){$style="style='color:#ADADAD !important'";}
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td style='width:1%' nowrap><i class='fas fa-users' $style></i>&nbsp;<span $style>".$tpl->td_href("$groupname","{view2}","Loadjs('$page?group-js=$ID');")."</span></td>";
		$html[]="<td>". USR_LIST($ID)."</td>";
		$html[]="<td style='width:1%' nowrap $style>".$tpl->icon_delete("Loadjs('$page?group-delete=$ID&md=$zmd5')")."</td>";
		$html[]="</tr>";
	
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-admins').footable( {\"filtering\": {\"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function USR_LIST($ID){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$results=$q->QUERY_SQL("SELECT users.ID,users.username FROM `users`,`lnk` 
			WHERE lnk.userid=users.ID
			AND lnk.groupid=$ID
			ORDER BY username");
	
	if(!$q->ok){echo $q->mysql_error_html();}
	$TRCLASS=null;
	$html[]="<table>";
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$username=$ligne["username"];
		$ID=$ligne["ID"];
		$html[]="<tr>";
		$html[]="<td><i class='fas fa-user-tie'></i>&nbsp;".$tpl->td_href("$username","{view2}","Loadjs('$page?user-js=$ID');")."</td>";
		$html[]="<td width=1%>".$tpl->icon_unlink("Loadjs('$page?user-unlink=$ID')","AsSystemAdministrator")."</td>";
		$html[]="</tr>";
	
}

$html[]="</table>";
return @implode("", $html);
}

function group_members_table(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$gpid=intval($_GET["groups-members-table"]);
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$page=CurrentPageName();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?user-js=0&gpid=$gpid');\">
	<i class='fas fa-user-plus'></i> {new_member} </label>";
	$html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?link-user=$gpid');\">
	<i class='fas fa-link'></i> {link_member} </label>";	
	$html[]="</div>";
	
	
	$html[]="<table id='table-admins-users{$gpid}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{unlink}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	$sql="SELECT lnk.ID as tid,users.ID,users.username,users.enabled FROM `users`,`lnk` 
			WHERE lnk.userid=users.ID
			AND lnk.groupid=$gpid
			ORDER BY username";
	
	VERBOSE($sql,__LINE__);
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	
	foreach ($results as $index=>$ligne){
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$style=null;
		$username=$ligne["username"];
		if($username==null){$username="{unknown}";}
		$ID=$ligne["ID"];
		$tid=$ligne["tid"];
		$enabled=$ligne["enabled"];
		if($enabled==0){$style="style='color:#ADADAD !important'";}
	
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td><i class='fas fa-user-tie' $style></i>&nbsp;<span $style>".$tpl->td_href("$username","{view2}","Loadjs('$page?user-js=$ID&gpid=$gpid');")."</span></td>";
		$html[]="<td style='width:1%' nowrap $style><center>".$tpl->icon_unlink("Loadjs('$page?unlink=$tid')")."</center></td>";
		$html[]="<td style='width:1%' nowrap $style><center>".$tpl->icon_delete("Loadjs('$page?user-delete=$ID&gpid=$gpid')")."</center></td>";
		$html[]="</tr>";
	
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-admins-users{$gpid}').footable( {\"filtering\": {\"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function user_groups_table(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$userid=intval($_GET["user-groups-table"]);
	$q=new lib_sqlite("/home/artica/SQLITE/admins.db");
	$page=CurrentPageName();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?link-group=$userid');\">
	<i class='fas fa-link'></i> {link_group} </label>";
	$html[]="</div>";
	
	
	$html[]="<table id='TableAdminGrp$userid' class=\"table table-bordered\" style='margin-top:5px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>{groups2}</th>";
	$html[]="<th>{unlink}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	$sql="
	SELECT lnk.ID as tid,groups.ID,groups.groupname,groups.enabled FROM `groups`,`lnk`
	WHERE groups.ID=lnk.groupid
	AND lnk.userid=$userid
	ORDER BY groupname";
	VERBOSE($sql,__LINE__);
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$style=null;
		$groupname=$ligne["groupname"];
		if($groupname==null){$groupname="{unknown}";}
		$ID=$ligne["ID"];
		$tid=$ligne["tid"];
		$enabled=$ligne["enabled"];
		$userid=$ligne["userid"];
		if($enabled==0){$style="style='color:#ADADAD !important'";}
	
		$html[]="<tr id='$zmd5'>";
		$html[]="<td><i class='fas fa-users' $style></i>&nbsp;<span $style>".$tpl->td_href("$groupname","{view2}","Loadjs('$page?group-js=$ID');")."</span></td>";
		$html[]="<td style='width:1%' nowrap $style><center>".$tpl->icon_unlink("Loadjs('$page?unlink=$tid')")."</center></td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="</table>";
	$html[]="
	<script>
	//NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	//$('#TableAdminGrp$userid').footable( {\"filtering\": {\"enabled\": true },\"sorting\": {\"enabled\": true } } );
	</script>";
	
		echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

