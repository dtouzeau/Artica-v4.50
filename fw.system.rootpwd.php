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
	$sock=new sockets();
    $tpl=new template_admin();
	$nsswitchEnableLdap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nsswitchEnableLdap"));
	
	if(strpos(" {$_POST["change_password"]}", ":")>0){
        echo $tpl->post_error("`:` not supported !");
		return false;
	}
	
	if(strlen(trim($_POST["change_password"]))>1){
		$_POST["change_password"]=url_decode_special_tool($_POST["change_password"]);
        $len=strlen($_POST["change_password"]);
			
		if($nsswitchEnableLdap==1){
			include_once(dirname(__FILE__))."/ressources/class.samba.inc";
			$smb=new samba();
			if(!$smb->createRootID($_POST["change_password"])){
                echo $tpl->post_error("createRootID failed");
				return false;
			}
		}
		$sock->SET_INFO("RootPasswordChanged", 1);
		$change_password=url_decode_special($_POST["change_password"]);
		$changeRootPasswd=urlencode(base64_encode($change_password));

        $resp=$sock->REST_API("/system/rootpwd/$changeRootPasswd");
        $json=json_decode($resp);

        if (json_last_error()> JSON_ERROR_NONE) {
            echo $tpl->post_error("Decode REST API: ".json_last_error_msg()."<br>$resp");
            return false;
        }

        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }

        return admin_tracks("Success Changing the root password with en length of $len  characters");
			
			
	}
    return false;
}
