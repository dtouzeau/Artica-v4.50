<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
$sock=new sockets();
$tpl=new template_admin();
$users=new usersMenus();
if(!$users->AsPostfixAdministrator){die();}

if(isset($_POST["smtp_sender"])){simulate_email();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["results"])){results_js();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}


js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog6("{smtp_simulation}", "$page?popup=yes",650);
}
function results_js(){
    $fname = PROGRESS_DIR . "/smtp.tool.infos";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data = @file_get_contents($fname);
    if (preg_match("#\[FAILED\]:(.+)#is", $data, $re)) {
        $tpl->js_error($re[1]);
        return false;
    }

    if (preg_match("#\[SUCCESS\]#is", $data, $re)) {
        $tpl->js_display_results("{success}");
        return true;
    }
    $tpl->js_error($data);
    return false;

}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	//$form[]=$tpl->field_m

    if(!isset($_SESSION["subject"])){$_SESSION["subject"]=null;}
    if(!isset($_SESSION["body"])){$_SESSION["body"]=null;}

    if($_SESSION["subject"]==null){$_SESSION["subject"]="Test Artica SMTP Notification from ".php_uname("n");}
	$form[]=$tpl->field_text("smtp_sender", "{smtp_sender}",  $_SESSION["smtp_sender"],true);
    $form[]=$tpl->field_text("smtp_recipient", "{smtp_recipient}",  $_SESSION["smtp_recipient"],true);

    if($_SESSION["body"]==null){
        $_SESSION["body"]="Dear Friend,\nI am writing this message to you to see if you have received it.\nIf you read this message, it means that it has arrived at its destination.\n
If not, you will not be able to read it and I should receive an error message from your mail server.\nI don't expect an answer from you\nSincerely\n\n";
    }
    $_SESSION["body"]=str_replace("\r\n","\n",$_SESSION["body"]);
    $form[]=$tpl->field_text("subject", "{subject}",  $_SESSION["subject"],true);
    $form[]=$tpl->field_textareacode("content","{body}",$_SESSION["body"]);
    if($_SESSION["smtp_server_name"]==null){
        $_SESSION["smtp_server_name"]="127.0.0.1";
    }
    if(intval($_SESSION["smtp_server_port"])==0){
        $_SESSION["smtp_server_port"]="25";
    }
    $form[]=$tpl->field_text("smtp_server_name", "{smtp_server_name}", $_SESSION["smtp_server_name"]);
    $form[]=$tpl->field_numeric("smtp_server_port", "{smtp_server_port}", $_SESSION["smtp_server_port"]);
    $form[]=$tpl->field_text("smtp_auth_user", "{smtp_auth_user}", $_SESSION["smtp_auth_user"]);
    $form[]=$tpl->field_password("smtp_auth_passwd", "{smtp_auth_passwd}", $_SESSION["smtp_auth_passwd"]);
    $form[]=$tpl->field_checkbox("tls_enabled","{tls_enabled}",$_SESSION["tls_enabled"]);


	$html=$tpl->form_outside(null, $form,"","{run}","Loadjs('$page?results=yes')");
	echo $html;
}


function simulate_email(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $_POST["content"]=str_replace("\n","\r\n",$_POST["content"]);
    foreach ($_POST as $key=>$val){
        $_SESSION[$key]=$val;
        $vals[]="$key=[$val]";
    }

    $final=@implode("||",$vals);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SMTPClientTools",$final);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/smtp/test/client");


    return true;
}
function results_popup()
{
    $fname = PROGRESS_DIR . "/smtp.tool.infos";
    $tpl = new template_admin();
    $data = @file_get_contents($fname);
    if (preg_match("#\[FAILED\]:(.+)#is", $data, $re)) {
        echo $tpl->div_error("{failed}||{$re[1]}");
        return false;
    }

    if (preg_match("#\[SUCCESS\]#is", $data, $re)) {
        echo $tpl->div_explain("{success}");
    }

}






