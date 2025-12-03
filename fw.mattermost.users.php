<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["member-js"])){member_js();exit;}
if(isset($_GET["member-popup"])){member_popup();exit;}
if(isset($_POST["id"])){member_save();exit;}
if(isset($_POST["adduser"])){member_create();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("proftpd.php?systemusers=yes");

    $html=$tpl->page_header("{APP_MATTERMOST} {members}","far fa-users",
        "{APP_MATTERMOST_EXPLAIN}",
        "$page?start=yes","mattermost-users","progress-mattermostusrs-restart",false,"table-proftpd-members");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_MATTERMOST} {members}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function delete_js():bool{
	$tpl=new template_admin();
	$id=$_GET["delete-js"];
	$json=getMember($id);
    $title=$json->username;
	$md=$_GET["md"];
	return $tpl->js_confirm_delete("$title: {remove_user_ask} ", "delete", $id,"$('#$md').remove()");
}
function delete():bool{
	$id=urlencode($_POST["delete"]);
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mattermost/users/delete/$id"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Deleted Mattermost user $id");
}

function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
    return true;
}

function member_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$title="{new_member}";
    $function=$_GET["function"];
	$id=$_GET["member-js"];

	if(strlen($id)>1){
		$json=getMember($id);
        $title=$json->username;
	}
	
	return $tpl->js_dialog1($title, "$page?member-popup=$id&function=$function");
}

function member_popup():bool{
	$tpl=new template_admin();
	$btname="{add}";
    $id=$_GET["member-popup"];
    $function=$_GET["function"];
    $json=getMember();

    if(strlen($id)>2){
        $json=getMember($id);
        $btname="{apply}";
    }
    $js[]="dialogInstance1.close();";
    $js[]="$function();";


	if(strlen($id)<3) {
        $form[] = $tpl->field_hidden("adduser", "yes");
        $form[] = $tpl->field_checkbox("admin", "{administrator}");
        $form[] = $tpl->field_text("username", "{username}","", true);
        $form[] = $tpl->field_text("firstname", "{firstname}","");
        $form[] = $tpl->field_text("lastname", "{lastname}","");
        $form[] = $tpl->field_text("nickname", "{nickname}","");
        $form[] = $tpl->field_email("email", "{email}","", true);
        $form[] = $tpl->field_password2("passwd", "{password}", "");
        echo $tpl->form_outside("", $form, null, $btname, @implode(";", $js), "AsSystemAdministrator");
        return true;
    }


    $username=$json->username;
    $email=$json->email;
    $is_bot=$json->is_bot;
    $roles=ParseRoles($json->roles);
    $create_at=intval($json->create_at);
    $update_at=intval($json->update_at);
    $createText=$tpl->time_to_date($create_at/1000,true);
    $UpdateText=$tpl->time_to_date($update_at/1000,true);
    $tt=array();
    $ico_user=ico_user;
    $botnet="";
    if(strlen($json->first_name)>1){
        $tt[]=$json->first_name;
    }
    if(strlen($json->last_name)>1){
        $tt[]=$json->last_name;
    }
    if(strlen($json->nickname)>1){
        $tt[]=" ($json->nickname)";
    }
    if($is_bot){
        $ico_user="fas fa-robot";
        $botnet=" <small>(<i>".$json->bot_description."</i>)</small>";
    }

    $tpl->table_form_field_text("{username}","<span style='text-transform: none'>$username</span> $botnet",$ico_user);
    $tpl->table_form_field_text("{email}","<span style='text-transform: lowercase'>$email</span>",ico_email);
    if(count($tt)>0) {
        $tpl->table_form_field_text("{displayName}",implode(" ", $tt),$ico_user);
    }
    if(isset($roles["system_admin"])){
        $tpl->table_form_field_text("{privileges}","{administrator}",ico_admin);
    }
    $tpl->table_form_field_text("{created}",$createText,ico_clock);
    $tpl->table_form_field_text("{updated}",$UpdateText,ico_clock);
    echo $tpl->table_form_compile();
    return true;
}
function member_create():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $f=array();
    unset($_POST["adduser"]);

    if(strlen($_POST["passwd"])<8){
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{error_pass8}"));
        return false;
    }

    foreach($_POST as $k=>$v){
        $v=trim($v);
        if(strlen($v)==0){
            continue;
        }
        $f[$k]=$v;

    }
    $Str=urlencode(base64_encode(json_encode($f)));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mattermost/users/create/$Str"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    unset($_POST["passwd"]);
    return admin_tracks_post("Create Mattermost user");

}

function getMember($uid=""){

    $fake["username"]="";
    if(strlen($uid)<3){
        return json_encode($fake);
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mattermost/users/list"));

    if(!$json->Status){
        return json_encode($fake);

    }
    foreach ($json->users as $jUser){
        if($jUser->id==$uid){
            return $jUser;
        }
    }
    return json_encode($fake);
}

function table():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mattermost/users/list"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    
    
	
	$html[]="<table id='table-mattermost-users' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{email}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{created}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{updated}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>Del.</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


    $search=trim($_GET["search"]);
    $TRCLASS=null;
	foreach ($json->users as $jUser){
        $icon_user="<i class='fa-solid fa-user-large'></i>";
        if(strlen($search)>1){
            $search=str_replace("*",".*?",$search);
            if(!preg_match("#$search#i",json_encode($jUser))){
                continue;
            }
        }
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(json_encode($jUser));
		$id=$jUser->id;
		$username=$jUser->username;
        $email=$jUser->email;
        $roles=ParseRoles($jUser->roles);
        $create_at=intval($jUser->create_at);
        $update_at=intval($jUser->update_at);
        $is_bot=intval($jUser->is_bot);
        $bot_description="";
        $createText=$tpl->time_to_date($create_at/1000,true);
        $UpdateText=$tpl->time_to_date($update_at/1000,true);

		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$id&md=$md')","AsSystemAdministrator");

        if(isset($roles["system_user"]) && !isset($roles["system_admin"])) {
            $icon_user="<i class='fa-solid ".ico_user."'></i>";
        }
        if(isset($roles["system_user"]) && isset($roles["system_admin"])) {
             $icon_user="<i class='fa-solid ".ico_admin."'></i>";
        }
        if($is_bot){
            $icon_user="<i class='fas fa-robot'></i>";
            $bot_description="&nbsp;<i>".$jUser->bot_description."</i>";
        }


		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap><span style='font-weight:bold'>". $tpl->td_href($icon_user."&nbsp;&nbsp;".$username,null,"Loadjs('$page?member-js=$id');")."</span>$bot_description</td>";
		$html[]="<td style='width:1%' nowrap><span style='font-weight:bold'>". $tpl->td_href($username,null,"Loadjs('$page?member-js=$id');")."</span></td>";
		$html[]="<td><span style='font-weight:bold'>$email</span></td>";
		$html[]="<td style='width:1%' nowrap>$createText</td>";
		$html[]="<td style='width:1%' nowrap>$UpdateText</td>";
		$html[]="<td style='width:1%' nowrap>$delete</td>";
		
		$html[]="</tr>";

		
	}
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    $topbuttons[]=array("Loadjs('$page?member-js=&function=$function')",ico_plus,"{new_member}");

    $TINY_ARRAY["TITLE"]="{APP_MATTERMOST} {members}";
    $TINY_ARRAY["ICO"]="far fa-users";
    $TINY_ARRAY["EXPL"]="{APP_MATTERMOST_EXPLAIN}";
    $TINY_ARRAY["URL"]="mattermost-users";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-mattermost-users').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function ParseRoles($role):array{

    if(strpos($role," ")==0){
        return array($role=>true);
    }
    $t=array();
    $tb=explode(" ",$role);
    foreach($tb as $k=>$v){
        VERBOSE("$k=>$v",__LINE__);
        $v=trim($v);
        if(strlen($v)<2){
            continue;
        }
        $t[$v]=true;
    }
    return $t;
}

function member_save(){
	$tpl=new template_admin();
	
	$tpl->CLEAN_POST();

    foreach ($_POST as $num=>$line){
		$_POST[$num]=mysql_escape_string2($line);
	
	}
	
	$tt=explode(":",$_POST["uid"]);
	$uid=$tt[0];
	$gid=$tt[1];
	$id=$_POST["id"];
	$userid=$_POST["userid"];
	$passwd=$_POST["passwd"];
	$homedir=$_POST["homedir"];
	$date=date("Y-m-d H:i:s");
	if($id==0){
		$sql="INSERT INTO `ftpuser` ( `userid`, `passwd`, `uid`, `gid`, `homedir`, `shell`, `count`, `accessed` , `modified`, `LoginAllowed` )
		VALUES ('$userid', '$passwd', '$uid', '$gid', '$homedir', '/bin/false', '0', '$date', '$date', 'true' );";
	}else{
		$sql="UPDATE `ftpuser` SET
		`userid`='$userid',
		`passwd`='$passwd',
		`uid`=$uid,
		`gid`=$gid,
		`homedir`='$homedir' WHERE id=$id";
	}
	
	$q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."<br>$sql";return;}
	$sock=new sockets();
	$sock->getFrameWork("proftpd.php?chowndirs=yes");
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'):string{$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}