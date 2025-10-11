<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.monit.xml.inc");



$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["monitor"])){monitor();exit;}
if(isset($_GET["restart"])){restart();exit;}

js();

function js(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{processes}", "$page?popup=yes");
	
	
}

function monitor(){
	$page=CurrentPageName();
	$APP=$_GET["monitor"];
	$sock=new sockets();
	$sock->getFrameWork("monit.php?monitor=$APP");
	echo "LoadAjaxSilent('monit-status','$page?status=yes');";
}
function restart(){
	$page=CurrentPageName();
	$APP=$_GET["restart"];
	$sock=new sockets();
	$sock->getFrameWork("monit.php?restart-app=$APP");
	echo "LoadAjaxSilent('monit-status','$page?status=yes');";
}

function popup(){
	$page=CurrentPageName();
	$html="<div id='htop-status'></div>
	<script>
		LoadAjaxSilent('htop-status','$page?status=yes');
	</script>		
			
	";
	echo $html;
	
	
	
}


function status(){
	$tpl=new template_admin();
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("monit.php?htop=yes");
	$tfile=PROGRESS_DIR."/htop.status";
	$data=@file_get_contents($tfile);
	$html[]=$data;
	echo @implode("\n", $html);
}
?>

