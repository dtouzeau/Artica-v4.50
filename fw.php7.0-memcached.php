<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$users=new usersMenus();
$tpl=new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{system}::php-memcached", "$page?popup=yes",650);
}

function popup(){
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/php7install.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/php7install.log";
    $package="php-memcached";

    $sock=new sockets();
    $data=json_decode($sock->REST_API("/harmp/status"));
    $DebianVersion=$data->DebianVersion;

    if($DebianVersion==12){
        $package="php8.2-memcached";
    }
	$ARRAY["CMD"]="services.php?apt-get-install=$package";
	$ARRAY["TITLE"]="{system} {installing} php-memcached";
	$ARRAY["AFTER"]="dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-php7-restart')";
	$html="<div id='progress-php7-restart'></div><script>$jsrestart</script>";
	echo $html;
}