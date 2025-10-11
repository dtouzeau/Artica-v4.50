<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){global_status();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{KSRN_SERVER2}",
        "fad fa-compress-arrows-alt",
        "<span class='label label-warning'>Feature in Beta stage</span>",
        "$page?tabs=yes","go-shield-central","progress-go-shield-central-restart",
        false,"table-loader-go-shield-central-pages"

    );

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);

}




