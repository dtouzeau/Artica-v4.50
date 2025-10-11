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
    $title=$tpl->_ENGINE_parse_body("{kernel_update}");
	$tpl->js_dialog2("#modal:$title", "$page?popup=yes",670);
}


function popup(){
	$tpl            = new template_admin();
    $html[]="<div id='upgrade-kernel-progress'></div>";

    $jsrest=$tpl->framework_buildjs("/system/debian/upgradekernel",
        "kernel.upgrade.progress",
        "kernel.upgrade.txt",
        "upgrade-kernel-progress",
        "dialogInstance2.close();Loadjs('fw.system.upgrade-software.php?jsafter=yes');"

);

    $html[]="<div style='margin:30px;font-size:16px'>{upgrade_kernel2}</div>";
    $html[]="<div style='margin:30px;font-size:16px'>";
    $html[]=$tpl->button_autnonome("{kernel_update}",$jsrest,ico_cd,"AsSystemAdministrator",350,"btn-primary",80);
    $html[]="</div>";

	echo $tpl->_ENGINE_parse_body($html);
}



