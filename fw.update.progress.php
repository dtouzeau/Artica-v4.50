<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");

if(isset($_GET["popup"])){popup();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{update_artica}","far fa-cloud-download",
        "{install_applis_text}<div id='header-update-page'></div>","$page?popup=yes",
        "artica-update","progress-articaupd-restart",false,"main-artica-update-section");
//LoadAjax('main-artica-update-section',$page?tabs=yes');


        $tpl=new template_admin("{update_artica}",$html);
        echo $tpl->build_firewall();
        return true;


}


function popup(){
	$div=md5(time().microtime(true));
	$tpl=new template_admin();

    $jsrestart= $tpl->framework_buildjs(
        "/system/artica/update/official",
        "artica.updatemanu.progress",
        "artica.updatemanu.log",$div,
        "document.location.href='/artica-update'","style:high");

	$html[]="<div id='$div' style='height:150px'></div>";
	$html[]="<script>$jsrestart</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}


