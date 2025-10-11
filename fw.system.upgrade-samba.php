<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}

js();



function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $title=$tpl->_ENGINE_parse_body("{APP_SAMBA}:{upgrade}");
	$tpl->js_dialog2("#modal:$title", "$page?popup=yes",670);
}


function popup(){
	$tpl            = new template_admin();
    $html[]="<div id='upgrade-samba-progress'></div>";

    $jsrest=$tpl->framework_buildjs("system.php?uprade-samba=yes",
        "exec.samba.upgrade.progress",
        "exec.samba.upgrade.txt",
        "upgrade-samba-progress",
        "dialogInstance2.close();Loadjs('fw.system.upgrade-software.php?jsafter=yes');"

    );
    $html[]="<script>$jsrest</script>";

	echo $tpl->_ENGINE_parse_body($html);
}



