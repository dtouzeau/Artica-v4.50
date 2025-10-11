<?php
session_start();
if(isset($_GET["shutdown-js"])){shutdown_js();exit;}
if(isset($_POST["defrag"])){defrag();exit;}
if(isset($_GET["restart-js"])){reboot_js();exit;}
if(isset($_GET["defrag-js"])){defrag_js();exit;}



if(isset($_GET["menus"])){
	echo menus();
	exit;
}

if(isset($_GET["perform"])){
	perform();
	exit;
}

function defrag(){
	include_once(dirname(__FILE__) . "/class.sockets.inc");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}	
	$sock=new sockets();
	$DisableRebootOrShutDown=$sock->GET_INFO('DisableRebootOrShutDown');		
	if($DisableRebootOrShutDown==1){return;}
	$sock->getFrameWork("services.php?system-defrag=yes");	
	echo "See you !! :=)\n";
}
function perform(){
	include_once(dirname(__FILE__) . "/class.sockets.inc");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	
	$sock=new sockets();
	$DisableRebootOrShutDown=$sock->GET_INFO('DisableRebootOrShutDown');		
	if($DisableRebootOrShutDown==1){return;}
	
	if($_GET["perform"]=="reboot"){
		$sock->REST_API("/system/reboot");
	}
	
	if($_GET["perform"]=="shutdown"){
		$sock->REST_API("/system/force-reboot");
	}	
}
function reboot_js(){
	include_once(dirname(__FILE__) . "/class.sockets.inc");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$restart_computer_text=$tpl->javascript_parse_text("{restart_computer_text}");
	header("content-type: application/x-javascript");
	$html="
var x_turnoff$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	window.location ='logoff.php';
}
	
	
function turningoff$t(){
	if(!confirm('$restart_computer_text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('perform','reboot');
	XHR.sendAndLoad('$page', 'GET',x_turnoff$t);
}
	
turningoff$t();
	";
	echo $html;	
	
}

function shutdown_js(){
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.users.menus.inc");
	include_once(dirname(__FILE__) . "/ressources/class.templates.inc");	
	$users=new usersMenus();
	$page=CurrentPageName();
	if(!$users->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	$tpl=new templates();
	$warn=$tpl->javascript_parse_text("{warn_shutdown_computer}");
	header("content-type: application/x-javascript");
	$html="
var x_turnoff= function (obj) {
				var results=obj.responseText;
				if(results.length>0){alert(results);}
				window.location ='$page';
				
			}	
	
	
	function turningoff(){
		if(confirm('$warn')){
			var XHR = new XHRConnection();
			XHR.appendData('perform','shutdown');
			XHR.sendAndLoad('$page', 'GET',x_turnoff);
		}
	}
	
	
	turningoff();
	";
	echo $html;
	
}

function defrag_js(){
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	$tpl=new templates();
	$restart_computer_and_defrag_warn=$tpl->javascript_parse_text("{restart_computer_and_defrag_warn}");
	$users=new usersMenus();
	$page=CurrentPageName();
	if(!$users->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}	
	$t=time();
	header("content-type: application/x-javascript");
echo "
var x_RestartDefragComputer$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    window.location ='$page';
}	
	
	
function RestartDefragComputer$t(){
	if(!confirm('$restart_computer_and_defrag_warn')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('defrag','yes');
	XHR.sendAndLoad('$page', 'POST',x_RestartDefragComputer$t);
}
	
RestartDefragComputer$t();";
	
	
	
}





if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/i/pattern.png")){$pattern="ressources/templates/{$_COOKIE["artica-template"]}/i/pattern.png";}
if($pattern==null){$pattern="ressources/templates/default/img/pattern.png";}

if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/i/fond-artica.png")){$logo="ressources/templates/{$_COOKIE["artica-template"]}/i/fond-artica.png";}

if($logo==null){
	if(is_file("ressources/templates/{$_COOKIE["artica-template"]}/i/logo.png")){$logo="ressources/templates/{$_COOKIE["artica-template"]}/i/logo.png";}
}

if($logo==null){$logo="ressources/templates/{$_COOKIE["artica-template"]}/i/fond-artica.png";}

$GLOBALS["CLASS_SOCKETS"]->SET_INFO($_SESSION["UID_KEY"],"");
unset($_SESSION["privileges_array"]);
unset($_SESSION["FORCED_TEMPLATE"]);
unset($_SESSION["MINIADM"]);
unset($_SESSION["uid"]);
unset($_SESSION["privileges"]);
unset($_SESSION["qaliases"]);
unset($_SERVER['PHP_AUTH_USER']);
unset($_SESSION["ARTICA_HEAD_TEMPLATE"]);
unset($_SESSION['smartsieve']['authz']);
unset($_SESSION["passwd"]);
unset($_SESSION["LANG_FILES"]);
unset($_SESSION["TRANSLATE"]);
unset($_SESSION["__CLASS-USER-MENUS"]);
unset($_SESSION["FONT_CSS"]);
unset($_SESSION["translation"]);
unset($_SESSION["CLASS_TRANSLATE_RIGHTS"]);
if(isset($_SESSION["2FAOK"])){unset($_SESSION["2FAOK"]);}
$_COOKIE["username"]="";
$_COOKIE["password"]="";
$_COOKIE["MINIADM"]="";


setcookie("shellinaboxCooKie", "", time()-3600);
setcookie("AsWebStatisticsCooKie", "", time()-3600);
setcookie("password", "", time()-3600);
setcookie("username", "", time()-3600);

foreach ($_SESSION as $num=>$ligne){
	unset($_SESSION[$num]);
}

session_destroy();
$URL="fw.login.php";
if(isset($_GET["goto"])){
$URL=$_GET["goto"];	
}

echo "
<html>
<head>
<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$URL\"> 
	<link href='css/styles_main.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_header.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_middle.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_forms.css' rel=\"styleSheet\" type='text/css' />
	<link href='css/styles_tables.css' rel=\"styleSheet\" type='text/css' />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />

</head>
<body style='padding:100px;background-image:url($pattern)'>

	<center style='border:3px solid white;padding:5px'><a style='font-size:22px;font-family:arial,tahoma;font-weight:bold;color:white' href='logon.php'>
	Waiting please, redirecting to logon page</a>
	</center>

<center style='padding:15px;background-image:url($logo);background-repeat:no-repeat;background-position:center top;width:100%;height:768px'>

</body>
</html>




";
exit;
?>