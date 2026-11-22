<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux
// d'entites. Include INCONDITIONNEL: place dans le bootstrap if(!class_exists("sockets"))
// de la ligne 2 il ne s'executerait jamais et la page mourrait sur awc_table_style()
// indefinie -- un spinner qui ne s'arrete plus.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : gpid/group-members/user-groups etait recopie sans encodage dans la page (XSS).
foreach(array("gpid","group-members","user-groups") as $k){if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}}
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

/**
 * GET against the /localadmins/* API (articarest, {success,error,data}
 * envelope). Returns the decoded data on success, null on any failure.
 */
function ladmins_get($path){
	$resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API($path)),true);
	if(!is_array($resp) || empty($resp["success"])){return null;}
	return $resp["data"] ?? null;
}

/**
 * POST/PUT/DELETE against the /localadmins/* API. Returns
 * array(bool $ok, mixed $dataOrError) — on failure the second element is
 * the error string for the caller to surface.
 */
function ladmins_send($method,$path,$body=null){
	switch(strtoupper($method)){
		case "POST":   $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($path,is_array($body)?$body:array()); break;
		case "PUT":    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON($path,is_array($body)?$body:array()); break;
		case "DELETE": $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE($path); break;
		default: return array(false,"unsupported method $method");
	}
	$resp=json_decode(strval($raw),true);
	if(!is_array($resp)){return array(false,"{unable_to_connect_to_the_service}");}
	if(empty($resp["success"])){return array(false,strval($resp["error"] ?? "unknown error"));}
	return array(true,$resp["data"] ?? null);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{local_administrators}",
        "fad fa-user-crown","{lamx_intro}",
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
	$link=ladmins_get("/localadmins/links/$ID");
	if($link===null){
		header("content-type: application/x-javascript");
		echo "alert('link $ID not found');";
		return;
	}
	$gpid=intval($link["groupid"]);
	$userid=intval($link["userid"]);
	$groupname=strval($link["groupname"]);
	$username=strval($link["username"]);

	$text="{unlink} <strong>".lamx_h($username)."</strong> {from} <strong>".lamx_h($groupname)."</strong><br><br>{lamx_unlink_effect}";

	$jsafter[]="LoadAjax('table-loader-localadmins','$page?table=yes');";
	$jsafter[]="LoadAjax('groups-members-$gpid','$page?groups-members-table=$gpid');";
	$jsafter[]="LoadAjax('groups-list-$userid','$page?user-groups-table=$userid');";
	$tpl->js_confirm_execute($text, "unlink", $ID,@implode("",$jsafter));


}

function group_delete(){
    $tpl=new template_admin();
    $gpid=intval($_GET["group-delete"]);
    $group=ladmins_get("/localadmins/groups/$gpid");
    $title=($group!==null)?strval($group["groupname"]):"#$gpid";
    $page=CurrentPageName();
    $text="{lamx_confirm_group}<br><strong style='font-size:16px'>".lamx_h($title)."</strong><br><br>{lamx_confirm_group_effect}";
    $tpl->js_confirm_delete($text,"group-delete",$gpid,"LoadAjax('table-loader-localadmins','$page?table=yes');",true);

}

function group_delete_perform(){
    if(ladmins_membership_denied("delete a local administrators group",true)){return false;}
    header("content-type: application/x-javascript");
    $gpid=intval($_POST["group-delete"]);
    $group=ladmins_get("/localadmins/groups/$gpid");
    $groupname=($group!==null)?strval($group["groupname"]):"#$gpid";
    list($ok,$data)=ladmins_send("DELETE","/localadmins/groups/$gpid");
    if(!$ok){echo "alert('".addslashes(strval($data))."');";return;}
    admin_tracks("Delete local administrators group $groupname ID=$gpid");
}


function user_delete_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$userid=intval($_GET["user-delete"]);
	$gpid=intval($_GET["gpid"]);
	$user=ladmins_get("/localadmins/users/$userid");
	$username=($user!==null)?strval($user["username"]):"#$userid";

	$jsafter[]="LoadAjax('table-loader-localadmins','$page?table=yes');";
	if($gpid>0){$jsafter[]="LoadAjax('groups-members-$gpid','$page?groups-members-table=$gpid');";}
	$jsafter[]="LoadAjax('groups-list-$userid','$page?user-groups-table=$userid');";
	$text="{lamx_confirm_user}<br><strong style='font-size:16px'>".lamx_h($username)."</strong><br><br>{lamx_confirm_user_effect}";
	$tpl->js_confirm_delete($text, "user-delete", $userid,@implode("", $jsafter),true);
}

function user_delete(){
    if(ladmins_membership_denied("delete a local administrator account",true)){return false;}
	$userid=intval($_POST["user-delete"]);
	$user=ladmins_get("/localadmins/users/$userid");
	$username=($user!==null)?strval($user["username"]):"#$userid";
	list($ok,$data)=ladmins_send("DELETE","/localadmins/users/$userid");
	if(!$ok){echo strval($data);return;}
	admin_tracks("Delete local administrator account $username ID=$userid");
}



function unlink_save(){
    if(ladmins_membership_denied("unlink a local administrator from a group",true)){return false;}
	$ID=intval($_POST["unlink"]);
	list($ok,$data)=ladmins_send("DELETE","/localadmins/links/$ID");
	if(!$ok){echo strval($data);return;}
	admin_tracks("Unlink local administrator membership ID=$ID");
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
		$group=ladmins_get("/localadmins/groups/$ID");
		if($group!==null){$title=strval($group["groupname"]);}
	}
	$tpl->js_dialog1($title, "$page?group-tab=$ID");
}
function user_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["user-js"]);
	$title="{new_member}";
	if($ID>0){
		$user=ladmins_get("/localadmins/users/$ID");
		if($user!==null){$title=strval($user["username"]);}
	}
	$tpl->js_dialog3($title, "$page?user-tab=$ID&gpid={$_GET["gpid"]}");
}
function link_user_to_group_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$userid=intval($_GET["link-group"]);
	$user=ladmins_get("/localadmins/users/$userid");
	$title=($user!==null)?strval($user["username"]):"#$userid";
	$tpl->js_dialog4("$title: {link_group}", "$page?link-group-popup=$userid");
}
function link_group_to_user_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$groupid=intval($_GET["link-user"]);
	$group=ladmins_get("/localadmins/groups/$groupid");
	$title=($group!==null)?strval($group["groupname"]):"#$groupid";
	$tpl->js_dialog4("$title: {link_user}", "$page?link-user-popup=$groupid");
}
function link_group_to_user_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$groupid=intval($_GET["link-user-popup"]);
	$jsafter="dialogInstance4.close();LoadAjax('groups-members-$groupid','$page?groups-members-table=$groupid');;LoadAjax('table-loader-localadmins','$page?table=yes');";
	$group=ladmins_get("/localadmins/groups/$groupid");
	$title=(($group!==null)?strval($group["groupname"]):"#$groupid").": {link_user}";

	$RD=array();
	$members=ladmins_get("/localadmins/groups/$groupid/members");
	if(is_array($members)){
		foreach ($members as $member){
			$RD[intval($member["id"])]=true;
		}
	}
	$HASH=array();
	$users=ladmins_get("/localadmins/users");
	if(is_array($users)){
		foreach ($users as $user){
			if(isset($RD[intval($user["id"])])){continue;}
			$HASH[intval($user["id"])]=strval($user["username"]);
		}
	}
	$form[]=$tpl->field_hidden("groupid", $groupid);
	$form[]=$tpl->field_array_hash($HASH,"link-member", "{member}", null);
	$html[]=$tpl->form_outside($title, $form,null,"{link}",$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}


function link_user_to_group_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$userid=intval($_GET["link-group-popup"]);
	$user=ladmins_get("/localadmins/users/$userid");
	$title=(($user!==null)?strval($user["username"]):"#$userid").": {link_group}";
	$jsafter="dialogInstance4.close();LoadAjax('groups-list-$userid','$page?user-groups-table=$userid');LoadAjax('table-loader-localadmins','$page?table=yes');";

	$RD=array();
	$ugroups=ladmins_get("/localadmins/users/$userid/groups");
	if(is_array($ugroups)){
		foreach ($ugroups as $g){
			$RD[intval($g["id"])]=true;
		}
	}

	$HASH=array();
	$groups=ladmins_get("/localadmins/groups");
	if(is_array($groups)){
		foreach ($groups as $g){
			if(isset($RD[intval($g["id"])])){continue;}
			$HASH[intval($g["id"])]=strval($g["groupname"]);
		}
	}
	$form[]=$tpl->field_hidden("link-member", $userid);
	$form[]=$tpl->field_array_hash($HASH,"groupid", "{groups2}", null);
	$html[]=$tpl->form_outside($title, $form,null,"{link}",$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}
function link_member_save(){
    if(ladmins_membership_denied("link a local administrator to a group",true)){return false;}
	$tpl=new template_admin();
	$userid=intval($_POST["link-member"]);
	$groupid=intval($_POST["groupid"]);
	list($ok,$data)=ladmins_send("POST","/localadmins/links",array("userid"=>$userid,"groupid"=>$groupid));
	if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text(strval($data)));return;}
	admin_tracks("Link local administrator user ID=$userid to group ID=$groupid");
}


function user_tab(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["user-tab"]);
	$title="{new_member}";
	$username=null;
	if($ID>0){
		$user=ladmins_get("/localadmins/users/$ID");
		if($user!==null){$title=strval($user["username"]);$username=strval($user["username"]);}
	}
	$array[$title]="$page?user-params=$ID&gpid={$_GET["gpid"]}";
	if($ID>0){
		$array["{groups2}"]="$page?user-groups=$ID";

        $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
        if($LighttpdArticaClientAuth==1){
            $sarray["uid"]=$username;
            $sarray["type"]="LOCAL";
            $sarray["displayname"]=$username;
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
		$group=ladmins_get("/localadmins/groups/$ID");
		if($group!==null){$title=strval($group["groupname"]);}
	}
	$array["<i class='$icon_group'></i> $title"]="$page?group-params=$ID";
	if($ID>0){
        $array["<i class='$icon_user'></i> {members}"]="$page?group-members=$ID";

        if(strtolower($title)<>'administrators') {
            $array["<i class='$icon_privs'></i> {privileges}"]="fw.groups.ad.php?privileges=sqlite:$ID";
            $array["<i class='fab fa-docker'></i> {docker_privs_tab}"]="fw.groups.ad.php?privileges-docker=sqlite:$ID";
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
	$ligne["username"]=null;
	$jsafter="dialogInstance3.close();LoadAjax('table-loader-localadmins','$page?table=yes');LoadAjax('groups-members-$gpid','$page?groups-members-table=$gpid');";
	if($ID>0){
		$user=ladmins_get("/localadmins/users/$ID");
		if($user!==null){
			$ligne["username"]=strval($user["username"]);
			$ligne["enabled"]=intval($user["enabled"]);
			$title=$ligne["username"];
			$btname="{apply}";
		}

	}
	$form[]=$tpl->field_hidden("userid", $ID);
	// Nouveau compte depuis l'en-tete (gpid=0) : le daemon exige un groupe, on le fait choisir.
	if($ID==0 && $gpid==0){
		$hash=array();
		$groups=ladmins_get("/localadmins/groups");
		if(is_array($groups)){foreach($groups as $g){if(is_array($g)){$hash[intval($g["id"])]=lamx_h(strval($g["groupname"]));}}}
		$form[]=$tpl->field_array_hash($hash,"groupid","nonull:{lamx_group}",0,true,"{lamx_group_help}");
	}else{
		$form[]=$tpl->field_hidden("groupid", $gpid);
	}
	// Pas d'interrupteur "enabled" : aucune route /localadmins/* n'ecrit users.enabled
	// (UpdateUser ne touche que username et passmd5), il ne servait a rien.
	$form[]=$tpl->field_text("username", "{username}", $ligne["username"],true,"{lamx_username_help}");
	$form[]=$tpl->field_password2("password", "{password}", null,true,$ID>0?"{lamx_password_help_edit}":"{lamx_password_help}");
	$html[]=$tpl->form_outside($title, $form,null,$btname,$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);

}

function user_save():bool{
    if(ladmins_membership_denied("create or edit a local administrator account",true)){return false;}
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["userid"]);
	$groupid=intval($_POST["groupid"]);

	$passmd5=md5(trim($_POST["password"]));

    $_POST["username"]=$tpl->CLEAN_BAD_XSS($_POST["username"]);
	$username=trim(strval($_POST["username"]));

	if($ID==0){
		if($groupid==0){echo $tpl->post_error("No Group ID!!!");return false;}
		list($ok,$data)=ladmins_send("POST","/localadmins/users",
			array("username"=>$username,"passmd5"=>$passmd5,"groupid"=>$groupid));
		if(!$ok){
			echo $tpl->post_error(strval($data));
			return false;
		}
	    return admin_tracks("Creating Local administrator account $username");
    }

	list($ok,$data)=ladmins_send("PUT","/localadmins/users/$ID",
		array("username"=>$username,"passmd5"=>$passmd5));
	if(!$ok){
		echo $tpl->post_error(strval($data));
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
	$ligne["groupname"]=null;
	$jsafter="dialogInstance1.close();LoadAjax('table-loader-localadmins','$page?table=yes');";
	if($ID>0){
		$group=ladmins_get("/localadmins/groups/$ID");
		if($group!==null){
			$ligne["groupname"]=strval($group["groupname"]);
			$ligne["enabled"]=intval($group["enabled"]);
			$title=$ligne["groupname"];
			$btname="{apply}";
		}

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
    if(ladmins_membership_denied("create or edit a local administrators group",true)){return false;}

	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["gpid"]);
    $_POST["groupname"]=$tpl->CLEAN_BAD_XSS($_POST["groupname"]);
	$groupname=trim(strval($_POST["groupname"]));
	if($ID==0){
		list($ok,$data)=ladmins_send("POST","/localadmins/groups",array("groupname"=>$groupname));
		if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text(strval($data)));return;}
        $_SESSION["SYSTEM_USERS_GROUP_CREATED"]=intval(is_array($data)?($data["id"] ?? 0):0);
        admin_tracks("Creating Local administrators group $groupname");
		return;
	}

	list($ok,$data)=ladmins_send("PUT","/localadmins/groups/$ID",
		array("groupname"=>$groupname,"enabled"=>intval($_POST["enabled"] ?? 1)));
	if(!$ok){echo (new template_admin())->post_error("".$tpl->javascript_parse_text(strval($data)));return;}
	admin_tracks("Updating Local administrators group $groupname ID=$ID");
}
function group_save_after():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $ID=$_SESSION["SYSTEM_USERS_GROUP_CREATED"];
    echo "Loadjs('$page?group-js=$ID');";
    return true;
}


// Liste des groupes d'administrateurs locaux (maquette stitch_local_administrator_console_redesign).
// Prefixe lamx (tokens, CSS, JS). Donnees : /localadmins/groups (groupes + membres + enabled)
// et /localadmins/2fa (une identite locale = "local:<id>"), soit deux appels pour la page.
// 2FA inconnu (route en echec) => pastille "2FA ?" et aucun bandeau, jamais "No 2FA" a tort.
function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$m=lamx_model();
	$h=array();
	$h[]="<div class='lamx' id='lamx-root'>";
	if($m["error"]){
		$h[]=lamx_banner_error();
	}elseif(count($m["groups"])==0){
		$h[]=lamx_empty();
	}else{
		$h[]=lamx_summary($m);
		$h[]=lamx_banner_2fa($m);
		foreach($m["groups"] as $g){$h[]=lamx_group_card($g,$m);}
		$h[]="<div class='lamx-nomatch' hidden>{lamx_no_match}</div>";
	}
	$h[]="</div>";
	$topbuttons=array();
	if(!$m["error"] && count($m["groups"])>0){
		$topbuttons[]=array("Loadjs('$page?user-js=0&gpid=0')",ico_plus,"{lamx_new_member}");
	}
	$topbuttons[]=array("Loadjs('$page?group-js=0')",ico_plus,"{new_group}");
	$topbuttons[]=array("LoadAjax('table-loader-localadmins','$page?table=yes');",ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{local_administrators}";
	$TINY["ICO"]="fad fa-user-crown";
	$TINY["EXPL"]="{lamx_intro}";
	$TINY["URL"]="admins";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$js="<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
	echo lamx_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).lamx_js().$js;
}

function lamx_model():array{
	$m=array("error"=>false,"groups"=>array(),"twofa_known"=>false,"accounts"=>0,"no2fa"=>0,"disabled"=>0,"admins_no2fa"=>0);
	$groups=ladmins_get("/localadmins/groups");
	if(!is_array($groups)){$m["error"]=true;return $m;}
	$tf=array();
	$resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/2fa")),true);
	if(is_array($resp) && !empty($resp["success"])){
		$m["twofa_known"]=true;
		$list=is_array($resp["data"] ?? null)?$resp["data"]:array();
		foreach($list as $r){
			if(!is_array($r)){continue;}
			$k=strval($r["IdentityKey"] ?? "");
			if(strpos($k,"local:")!==0){continue;}
			$st="none";
			if(!empty($r["Enrolled"])){$st="on";}
			if(!empty($r["Locked"])){$st="locked";}
			$tf[intval(substr($k,6))]=$st;
		}
	}
	$seen=array();
	foreach($groups as $g){
		if(!is_array($g)){continue;}
		$name=strval($g["groupname"] ?? "");
		$r=array("id"=>intval($g["id"] ?? 0),"name"=>$name,"enabled"=>intval($g["enabled"] ?? 1)==1,
			"admins"=>strtolower(trim($name))=="administrators","members"=>array());
		$mem=is_array($g["members"] ?? null)?$g["members"]:array();
		foreach($mem as $u){
			if(!is_array($u)){continue;}
			$uid=intval($u["id"] ?? 0);
			$st=$m["twofa_known"]?($tf[$uid] ?? "none"):"unknown";
			$en=intval($u["enabled"] ?? 1)==1;
			$r["members"][]=array("id"=>$uid,"linkid"=>intval($u["linkid"] ?? 0),"username"=>strval($u["username"] ?? ""),"enabled"=>$en,"twofa"=>$st);
			if($r["admins"] && $r["enabled"] && $en && $st==="none"){$m["admins_no2fa"]++;}
			if(isset($seen[$uid])){continue;}
			$seen[$uid]=true;
			$m["accounts"]++;
			if(!$en){$m["disabled"]++;}
			if($en && $st==="none"){$m["no2fa"]++;}
		}
		$m["groups"][]=$r;
	}
	return $m;
}

function lamx_t(string $k,array $args=array()):string{
	$tpl=new template_admin();
	$s=trim(preg_replace('/\s+/',' ',str_replace(array("[br]","<br>")," ",$tpl->_ENGINE_parse_body("{".$k."}"))));
	foreach($args as $a){
		$p=strpos($s,"%s");
		if($p===false){break;}
		$s=substr($s,0,$p).$a.substr($s,$p+2);
	}
	return $s;
}

// Echappement d'un nom saisi : aussi les accolades, que _ENGINE_parse_body lirait comme des {jetons}.
function lamx_h($s):string{
	return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}

function lamx_banner_error():string{
	return "<div class='lamx-ban lamx-ban-err' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i>"
		."<span class='lamx-grow'>{unable_to_connect_to_the_service}</span>"
		."<button type='button' class='lamx-bbtn' OnClick=\"LoadAjax('table-loader-localadmins','".CurrentPageName()."?table=yes');\">{lamx_retry}</button></div>";
}

function lamx_empty():string{
	$page=CurrentPageName();
	return "<div class='lamx-empty'><span class='lamx-empty-i'><i class='fas fa-user-crown' aria-hidden='true'></i></span>"
		."<div class='lamx-empty-h'>{lamx_empty}</div><div class='lamx-empty-s'>{lamx_empty_help}</div>"
		."<button type='button' class='lamx-btn' OnClick=\"Loadjs('$page?group-js=0');\"><i class='fas fa-plus' aria-hidden='true'></i> {new_group}</button></div>";
}

function lamx_summary(array $m):string{
	$b=function($v){return "<b>".intval($v)."</b>";};
	$parts=array(lamx_t("lamx_sum_groups",array($b(count($m["groups"])))),lamx_t("lamx_sum_accounts",array($b($m["accounts"]))));
	if($m["disabled"]>0){$parts[]=lamx_t("lamx_sum_disabled",array($b($m["disabled"])));}
	$chip="";
	if(!$m["twofa_known"]){
		$chip="<span class='lamx-chip-st lamx-st-unk'><i class='fas fa-circle-question' aria-hidden='true'></i> {lamx_2fa_unknown_all}</span>";
	}elseif($m["no2fa"]>0){
		$chip="<button type='button' class='lamx-sum-chip' aria-pressed='false' OnClick=\"LamxFilter2fa(this);\" title=\"".lamx_t("lamx_filter_2fa_tip")."\">"
			."<i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ".lamx_t("lamx_sum_no2fa",array(intval($m["no2fa"])))."</button>";
	}
	return "<div class='lamx-sum'><span>".implode(" &middot; ",$parts)."</span>$chip<span class='lamx-grow'></span>"
		."<span class='lamx-sum-r'><i class='fas fa-circle-info' aria-hidden='true'></i> {lamx_other_sources}</span></div>";
}

function lamx_banner_2fa(array $m):string{
	if($m["admins_no2fa"]==0){return "";}
	return "<div class='lamx-ban lamx-ban-warn' role='status'><i class='fas fa-shield-halved' aria-hidden='true'></i>"
		."<span class='lamx-grow'>".lamx_t("lamx_admins_no2fa",array("<b>".intval($m["admins_no2fa"])."</b>"))."</span>"
		."<a class='lamx-blink' href='/system-2fa'>{lamx_open_2fa} <i class='fas fa-arrow-right' aria-hidden='true'></i></a></div>";
}

function lamx_member_chip(array $u,array $g):string{
	$page=CurrentPageName();
	$name=lamx_h($u["username"]);
	$cls="lamx-mem".($u["enabled"]?"":" lamx-mem-off");
	$tf=array(
		"on"=>array("lamx-st-ok","fa-shield-check","{lamx_2fa_on}"),
		"none"=>array("lamx-st-warn","fa-triangle-exclamation","{lamx_2fa_none}"),
		"locked"=>array("lamx-st-err","fa-lock","{lamx_2fa_locked}"),
		"unknown"=>array("lamx-st-unk","fa-circle-question","{lamx_2fa_unknown}"),
	);
	$x=$tf[$u["twofa"]];
	$off=$u["enabled"]?"":"<span class='lamx-chip-st lamx-st-off'>{disabled}</span>";
	$unlink=lamx_t("lamx_unlink_aria",array($name,lamx_h($g["name"])));
	$no2fa=($u["enabled"] && $u["twofa"]==="none")?1:0;
	return "<span class='$cls' data-no2fa='$no2fa'><i class='fas fa-user-tie lamx-mem-i' aria-hidden='true'></i>"
		."<a href=\"javascript:blur();\" class='lamx-mem-n' OnClick=\"Loadjs('$page?user-js={$u["id"]}&gpid={$g["id"]}');\">$name</a>"
		."$off<span class='lamx-chip-st {$x[0]}'><i class='fas {$x[1]}' aria-hidden='true'></i> {$x[2]}</span>"
		."<button type='button' class='lamx-unl' aria-label=\"$unlink\" title=\"$unlink\" OnClick=\"Loadjs('$page?unlink={$u["linkid"]}');\"><i class='fas fa-link-slash' aria-hidden='true'></i></button></span>";
}

function lamx_group_head(array $g):string{
	$page=CurrentPageName();
	$name=lamx_h($g["name"]);
	$ico=$g["admins"]?"fa-crown":"fa-users";
	$desc=$g["admins"]?"{lamx_desc_admins}":"{lamx_desc_group}";
	if(!$g["enabled"]){$desc="{lamx_desc_disabled}";}
	$pill=$g["enabled"]?"<span class='lamx-pill lamx-pill-on'><i class='fas fa-circle-check' aria-hidden='true'></i> {enabled}</span>"
		:"<span class='lamx-pill lamx-pill-off'><i class='fas fa-power-off' aria-hidden='true'></i> {disabled}</span>";
	$n=count($g["members"]);
	$cnt=lamx_t($n==1?"lamx_one_member":"lamx_n_members",array($n));
	$edit=lamx_t("lamx_edit_group",array($name));
	$del=lamx_t("lamx_delete_group",array($name));
	return "<div class='lamx-gh".($g["enabled"]?"":" lamx-gh-off")."'><span class='lamx-tile' aria-hidden='true'><i class='fas $ico'></i></span>"
		."<div class='lamx-gh-t'><div class='lamx-gh-n'><a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?group-js={$g["id"]}');\">$name</a> $pill</div>"
		."<div class='lamx-gh-d'>$desc</div></div>"
		."<span class='lamx-gh-c'>$cnt</span>"
		."<button type='button' class='lamx-ico' aria-label=\"$edit\" title=\"$edit\" OnClick=\"Loadjs('$page?group-js={$g["id"]}');\"><i class='fas fa-pen' aria-hidden='true'></i></button>"
		."<button type='button' class='lamx-ico lamx-del' aria-label=\"$del\" title=\"$del\" OnClick=\"Loadjs('$page?group-delete={$g["id"]}');\"><i class='fas fa-trash-can' aria-hidden='true'></i></button></div>";
}

function lamx_group_card(array $g,array $m):string{
	$page=CurrentPageName();
	$chips=array();
	foreach($g["members"] as $u){$chips[]=lamx_member_chip($u,$g);}
	if(count($chips)==0){$chips[]="<span class='lamx-none'>{lamx_no_members}</span>";}
	$chips[]="<button type='button' class='lamx-add' OnClick=\"Loadjs('$page?user-js=0&gpid={$g["id"]}');\"><i class='fas fa-plus' aria-hidden='true'></i> {lamx_add_member}</button>";
	return "<section class='lamx-card' data-gid='{$g["id"]}'>".lamx_group_head($g)."<div class='lamx-mems'>".implode("",$chips)."</div></section>";
}

function lamx_js():string{
	return "<script>
window.LamxFilter2fa=function(b){
  var R=document.getElementById('lamx-root'); if(!R){return;}
  var on=b.getAttribute('aria-pressed')!=='true'; b.setAttribute('aria-pressed',on?'true':'false'); b.classList.toggle('on',on);
  var cards=R.querySelectorAll('.lamx-card'), shown=0;
  for(var i=0;i<cards.length;i++){
    var c=cards[i], mem=c.querySelectorAll('.lamx-mem'), n=0;
    for(var j=0;j<mem.length;j++){ var hit=mem[j].getAttribute('data-no2fa')==='1'; mem[j].hidden=on && !hit; if(hit){n++;} }
    c.hidden=on && n===0; if(!c.hidden){shown++;}
  }
  var nm=R.querySelector('.lamx-nomatch'); if(nm){nm.hidden=(shown>0);}
};
</script>";
}

function lamx_style():string{
	$ac=array("accent"=>"#0E7C66","accent_dark"=>"#0b6654","accent_soft"=>"rgba(14,124,102,.12)");
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
		$b=brand_ui_accents(brand_palette());
		$ac["accent"]=$b["accent"];$ac["accent_dark"]=$b["accent_dark"];$ac["accent_soft"]=$b["accent_soft"];
	}
	$A=$ac["accent"];$AD=$ac["accent_dark"];$AS=$ac["accent_soft"];
	return "<style>
.lamx{font-size:13px;color:#111827;margin-top:10px;display:flex;flex-direction:column;gap:12px}
.lamx *{box-sizing:border-box}
.lamx-grow{flex:1}
.lamx-ban{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:0 8px 8px 0;font-size:13px}
.lamx-ban>svg,.lamx-ban>i{flex:none;font-size:16px}
.lamx-ban-err{background:#FEF2F2;border-left:4px solid #DC2626;color:#7F1D1D}.lamx-ban-err>svg{color:#DC2626}
.lamx-ban-warn{background:#FFFBEB;border-left:4px solid #D97706;color:#92400E}.lamx-ban-warn>svg{color:#D97706}
.lamx-bbtn{height:32px;padding:0 12px;border:0;border-radius:6px;background:#DC2626;color:#fff;font-weight:600;font-size:12px;cursor:pointer}
.lamx-blink{color:#B45309!important;font-weight:600;white-space:nowrap}
.lamx-sum{display:flex;align-items:center;flex-wrap:wrap;gap:10px;min-height:40px;padding:6px 16px;background:#fff;border:1px solid #E5E7EB;border-radius:8px;color:#6B7280}
.lamx-sum b{color:#111827}
.lamx-sum-r{font-size:12px;color:#6B7280}
.lamx-sum-chip{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 12px;border-radius:999px;border:1px solid #FDE68A;background:#FFFBEB;color:#B45309;font-size:12px;font-weight:600;cursor:pointer}
.lamx-sum-chip.on{box-shadow:0 0 0 2px #D97706}
.lamx-card{background:#fff;border:1px solid #E5E7EB;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.lamx-card[hidden]{display:none}
.lamx-gh{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #F1F5F9}
.lamx-gh-off .lamx-tile,.lamx-gh-off .lamx-gh-n a{opacity:.6}
.lamx-tile{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:6px;background:$AS;color:$AD;flex:none}
.lamx-gh-t{flex:1;min-width:0}
.lamx-gh-n{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.lamx-gh-n a{font-size:15px;font-weight:600;color:#111827!important;text-decoration:none}
.lamx-gh-n a:hover{color:$AD!important}
.lamx-gh-d{font-size:12px;color:#6B7280;margin-top:2px}
.lamx-gh-c{font-size:12px;color:#6B7280;white-space:nowrap;padding-right:8px;border-right:1px solid #E5E7EB}
.lamx-pill{display:inline-flex;align-items:center;gap:4px;padding:1px 8px;border-radius:999px;font-size:11px;font-weight:600}
.lamx-pill-on{background:#ECFDF5;color:#047857;border:1px solid #A7F3D0}
.lamx-pill-off{background:#F3F4F6;color:#6B7280;border:1px solid #E5E7EB}
.lamx-ico{width:32px;height:32px;border:0;border-radius:6px;background:transparent;color:#6B7280;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;flex:none}
.lamx-ico:hover{background:#F3F4F6;color:#111827}
.lamx-del:hover{background:#FEF2F2;color:#DC2626}
.lamx-mems{display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:12px 16px}
.lamx-mem{display:inline-flex;align-items:center;gap:6px;padding:3px 4px 3px 10px;border-radius:999px;background:#F9FAFB;border:1px solid #E5E7EB;font-size:13px}
.lamx-mem[hidden]{display:none}
.lamx-mem-i{color:#9CA3AF}
.lamx-mem-n{color:#111827!important;font-weight:500;text-decoration:none}
.lamx-mem-off .lamx-mem-n{text-decoration:line-through;color:#9CA3AF!important}
.lamx-chip-st{display:inline-flex;align-items:center;gap:4px;padding:0 6px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap}
.lamx-st-ok{background:#ECFDF5;color:#047857}
.lamx-st-warn{background:#FFFBEB;color:#B45309}
.lamx-st-err{background:#FEF2F2;color:#B91C1C}
.lamx-st-unk,.lamx-st-off{background:#F3F4F6;color:#6B7280}
.lamx-unl{width:24px;height:24px;border:0;border-radius:50%;background:transparent;color:#9CA3AF;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;opacity:.55;transition:opacity .15s}
.lamx-mem:hover .lamx-unl,.lamx-unl:focus-visible{opacity:1}
.lamx-unl:hover{background:#FEF2F2;color:#DC2626}
.lamx-none{font-size:12px;color:#9CA3AF;font-style:italic}
.lamx-add{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;border:1px dashed $A;background:transparent;color:$AD;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap}
.lamx-add:hover{background:$AS}
.lamx button:focus-visible,.lamx a:focus-visible{outline:2px solid $A;outline-offset:2px}
.lamx-nomatch{padding:24px;text-align:center;color:#6B7280}
.lamx-nomatch[hidden]{display:none}
.lamx-empty{padding:56px 16px;text-align:center;background:#fff;border:1px solid #E5E7EB;border-radius:8px}
.lamx-empty-i{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:#F3F4F6;color:#9CA3AF;font-size:24px;margin-bottom:12px}
.lamx-empty-h{font-weight:600;color:#374151}
.lamx-empty-s{font-size:12px;color:#6B7280;margin:4px auto 16px}
.lamx-btn{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 16px;border:0;border-radius:6px;background:$A;color:#fff;font-weight:600;cursor:pointer}
@media (prefers-reduced-motion:reduce){.lamx-unl{transition:none}}
@media (max-width:1200px){.lamx-gh{flex-wrap:wrap}.lamx-gh-t{flex-basis:calc(100% - 48px)}}
</style>";
}


function group_members_table(){
	$tpl=new template_admin();
	$gpid=intval($_GET["groups-members-table"]);
	$page=CurrentPageName();

	$members=ladmins_get("/localadmins/groups/$gpid/members");
	if($members===null){
		echo $tpl->FATAL_ERROR_SHOW_128("{unable_to_connect_to_the_service}");
		return;
	}

	$html=array();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?user-js=0&gpid=$gpid');\">
	<i class='fas fa-user-plus'></i> {new_member} </label>";
	$html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?link-user=$gpid');\">
	<i class='fas fa-link'></i> {link_member} </label>";
	$html[]="</div>";

	$rows=array();
	foreach ($members as $ligne){
		$rows[]=ladm_member_row($ligne,$gpid,$tpl,$page);
	}
	$c=count($rows);

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table id='table-admins-users{$gpid}' class='awc-tbl'><thead><tr>";
	$html[]="<th>{members}</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='ladm-mbr-rows-$gpid'>";
	if($c==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_members}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='ladm-mbr-pager-$gpid'></span>";
	$html[]="</div></div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(25,"ladm-mbr-rows-$gpid","ladm-mbr-pager-$gpid");
}

/**
 * Un membre d'un groupe. Les colonnes {unlink} et {delete}, qui portaient une
 * icone chacune sous un en-tete propre, fusionnent dans la cellule d'actions du
 * design awc -- deux en-tetes pour deux icones repetaient l'evidence et volaient
 * la largeur de la colonne des noms.
 */
function ladm_member_row($ligne,$gpid,$tpl,$page):string{
	$username=strval($ligne["username"]);
	if($username==null){$username="{unknown}";}
	$ID=intval($ligne["id"]);
	$tid=intval($ligne["linkid"]);
	$enabled=intval($ligne["enabled"]);
	$zmd5=md5("member-$tid-$ID-$username-$enabled");
	$off=($enabled==0);

	$unlink=$tpl->icon_unlink("Loadjs('$page?unlink=$tid')");
	$del=$tpl->icon_delete("Loadjs('$page?user-delete=$ID&gpid=$gpid')");
	$label=($username=="{unknown}")?$username:htmlspecialchars($username);

	$h ="<tr id='$zmd5'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='fas fa-user-tie'></i></span>";
	$h.="<span><span class='awc-name".($off?" awc-name-off":"")."'>"
	   .$tpl->td_href($label,"{view2}","Loadjs('$page?user-js=$ID&gpid=$gpid');")."</span>";
	$h.="<div class='awc-meta awc-mono'>ID: $ID</div>";
	$h.="</span></div></td>";
	$h.="<td class='center'><span class='awc-badge ".($off?"awc-badge-off'>{disabled}":"awc-badge-on'>{enabled}")."</span></td>";
	$h.="<td><div class='awc-acts'>$unlink$del</div></td>";
	$h.="</tr>";
	return $h;
}

function user_groups_table(){
	$tpl=new template_admin();
	$userid=intval($_GET["user-groups-table"]);
	$page=CurrentPageName();

	$ugroups=ladmins_get("/localadmins/users/$userid/groups");
	if($ugroups===null){
		echo $tpl->FATAL_ERROR_SHOW_128("{unable_to_connect_to_the_service}");
		return;
	}

	$html=array();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?link-group=$userid');\">
	<i class='fas fa-link'></i> {link_group} </label>";
	$html[]="</div>";

	$rows=array();
	foreach ($ugroups as $ligne){
		$rows[]=ladm_usergroup_row($ligne,$tpl,$page);
	}
	$c=count($rows);

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table id='TableAdminGrp$userid' class='awc-tbl'><thead><tr>";
	$html[]="<th>{groups2}</th>";
	$html[]="<th class='center'>{status}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='ladm-ugrp-rows-$userid'>";
	if($c==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_group}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='ladm-ugrp-pager-$userid'></span>";
	$html[]="</div></div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(25,"ladm-ugrp-rows-$userid","ladm-ugrp-pager-$userid");
}

// Un groupe auquel l'utilisateur appartient.
function ladm_usergroup_row($ligne,$tpl,$page):string{
	$groupname=strval($ligne["groupname"]);
	if($groupname==null){$groupname="{unknown}";}
	$ID=intval($ligne["id"]);
	$tid=intval($ligne["linkid"]);
	$enabled=intval($ligne["enabled"]);
	$zmd5=md5("ugroup-$tid-$ID-$groupname-$enabled");
	$off=($enabled==0);

	$unlink=$tpl->icon_unlink("Loadjs('$page?unlink=$tid')");
	$label=($groupname=="{unknown}")?$groupname:htmlspecialchars($groupname);

	$h ="<tr id='$zmd5'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico".($off?" awc-ico-off":"")."'><i class='fas fa-users'></i></span>";
	$h.="<span><span class='awc-name".($off?" awc-name-off":"")."'>"
	   .$tpl->td_href($label,"{view2}","Loadjs('$page?group-js=$ID');")."</span>";
	$h.="<div class='awc-meta awc-mono'>ID: $ID</div>";
	$h.="</span></div></td>";
	$h.="<td class='center'><span class='awc-badge ".($off?"awc-badge-off'>{disabled}":"awc-badge-on'>{enabled}")."</span></td>";
	$h.="<td><div class='awc-acts'>$unlink</div></td>";
	$h.="</tr>";
	return $h;
}

// Appartenance aux groupes (ajout, retrait, nouveau groupe du membre) : administrateur systeme
// seulement, controle COTE SERVEUR. Sans lui, tout compte connecte pouvait s'ajouter a un groupe
// administrateur et heriter de ses droits. Rend true = REFUSE.
function ladmins_membership_denied($what,$post=false):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    admin_tracks("Refused: $what (not a system administrator)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
