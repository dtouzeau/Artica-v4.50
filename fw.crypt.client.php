<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__) . '/ressources/externals/class.aesCrypt.inc');
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
$sock=new sockets();
$tpl=new template_admin();
$users=new usersMenus();

if(isset($_GET["standard"])){popup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["passphrase"])){tests();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog6("{encrypt_tool}", "$page?popup=yes",650);
}


function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    if($_SESSION["aesfilename"]==null){$_SESSION["aesfilename"]="crypted.aes";}
    if($_SESSION["passphrase"]==null){$_SESSION["passphrase"]="ThisisMyPassKey";}
    $form[]=$tpl->field_text("aesfilename", "{filename}",  $_SESSION["aesfilename"],true);
	$form[]=$tpl->field_text("passphrase", "{passphrase}",  $_SESSION["passphrase"],true);
    $form[]=$tpl->field_textarea("crcontent","{content}",$_SESSION["crcontent"]);
	$html=$tpl->form_outside(null, $form,"","{crypt} (aes256)","document.location.href='$page?download=yes';");
	echo $html;
}



function tests(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$tpl->SESSION_POST();
	$sock=new sockets();
	$crcontent=$_POST["crcontent"];
	$passphrase=$_POST["passphrase"];
    $crypt = new AESCrypt($passphrase);
    $sock->SET_INFO("TOOL_CRYPT",base64_encode($crypt->encrypt($crcontent)));
    $sock->SET_INFO("TOOL_CRYPT_FNAME",$_POST["aesfilename"]);
}
function download(){
	$tpl=new template_admin();
    $sock=new sockets();
	$data=base64_decode($sock->GET_INFO("TOOL_CRYPT"));
    $aesfilename=$sock->GET_INFO("TOOL_CRYPT_FNAME");

    header('Content-type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$aesfilename\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = strlen($data);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $data;
}
