<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["authtype"])){save();exit;}

js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	if(isset($_GET["nas-name"])){$_GET["nas-name"]=urlencode($_GET["nas-name"]);}
	$tpl->js_dialog("{test_auth}", "$page?popup=yes&nas-name={$_GET["nas-name"]}");
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$FreeRadiusRadTest=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusRadTest")));
	
	$auth["pap"]="PAP";
	$auth["chap"]="CHAP";
	$auth["mschap"]="MS-CHAP";
	$auth["eap-md5"]="EAP-MD5";
	
	if(!isset($FreeRadiusRadTest["authtype"])){$FreeRadiusRadTest["authtype"]="pap";}
	if(!isset($FreeRadiusRadTest["nas-name"])){$FreeRadiusRadTest["nas-name"]="127.0.0.1";}
	
	$form[]=$tpl->field_array_hash($auth, "authtype", "{type}", $FreeRadiusRadTest["authtype"]);
	if($_GET["nas-name"]==null){
	$form[]=$tpl->field_ipaddr("nas-name", "{simulate_client_ip}", $FreeRadiusRadTest["nas-name"]);
	$form[]=$tpl->field_password("secret", "{password} ({connection})", $FreeRadiusRadTest["secret"]);
	}else{
		$form[]=$tpl->field_info("nas-name", "{simulate_client_ip}", $_GET["nas-name"]);
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT secret FROM freeradius_clients WHERE ipaddr='{$_GET["nas-name"]}'","artica_backup"));
		$tpl->field_hidden("secret", $ligne["secret"]);
	}
	$form[]=$tpl->field_text("username", "{username}", $FreeRadiusRadTest["username"]);
	$form[]=$tpl->field_password("password", "{password}", $FreeRadiusRadTest["password"]);
	
    $jsrestart=$tpl->framework_buildjs("freeradius.php?radtest=yes",
        "radtest.progress",
        "radtest.log",
        "progress-radtest-restart"
    );
	
	echo "<div id='progress-radtest-restart' style='margin:5px'></div>";
	echo $tpl->form_outside("{parameters}", @implode("\n", $form),"{test_auth_text}<br>{radtest_explain}","{check}",$jsrestart);
	
	
	
	
	
}

function save(){
	
	
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "FreeRadiusRadTest");
	
	
}