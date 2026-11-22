<?php
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["change_password"])){passwordch();exit;}
xstart();
function xstart(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog1("{root_password2}", "$page?popup=yes");
	
}

function popup(){
	$tpl=new template_admin();
	$form=$tpl->field_password2("change_password", "{password}", null,true);
	echo $tpl->form_outside("{root_password_not_changed}", $form,"{root_password_not_changed_text}","{apply}","dialogInstance1.close();","AsSystemAdministrator");
	
	
}

function passwordch():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // Mot de passe lu brut, decode une seule fois (meme regle que fw.system.users.php) ; il part dans
    // le CORPS d'un POST JSON : jamais dans une URL, donc jamais dans articarest.query.log.
    $password=isset($_POST["change_password"]) && !is_array($_POST["change_password"])?(string)$_POST["change_password"]:"";
    if(function_exists("url_decode_special_tool")){$password=url_decode_special_tool($password);}
    $len=strlen($password);
    if($len<8 || $len>128 || preg_match('/[\r\n]/',$password)){
        echo $tpl->post_error("{systemusers_password_rule}");
        return false;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/user/chpasswd",array("user"=>"root","password"=>$password));
    $json=json_decode($resp);
    if(!is_object($json)){
        echo $tpl->post_error("Decode REST API: ".json_last_error_msg());
        return false;
    }
    if(empty($json->success)){
        echo $tpl->post_error(htmlspecialchars((string)($json->error ?? "unknown error"),ENT_QUOTES));
        return false;
    }
    $nsswitchEnableLdap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nsswitchEnableLdap"));
    if($nsswitchEnableLdap==1){
        include_once(dirname(__FILE__)."/ressources/class.samba.inc");
        $smb=new samba();
        if(!$smb->createRootID($password)){
            echo $tpl->post_error("createRootID failed");
            return false;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RootPasswordChanged", 1);
    return admin_tracks("Success Changing the root password with a length of $len characters");
}
