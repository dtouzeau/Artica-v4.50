<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    //$tpl=new templates();


    $about=$tpl->_ENGINE_parse_body("{APP_STRONGSWAN_ABOUT_EXPLAIN}");

    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $about=str_replace("website", "<a href=\"https://www.strongswan.org\" target='_NEW' style='text-decoration:underline;font-weight:bold'>strongSwan website</a>", $about);
    $html="
    <div class=\"row border-bottom white-bg dashboard-header\">
    <div class=\"col-sm-12\"><h1 class=ng-binding>{APP_STRONGSWAN_ABOUT_IT_TITLE}</h1>
    <img src='https://www.strongswan.org/images/strongswan.png'>
    <p>$about</p>
    $btn
    </div>
	
	</div>
    </div>
    <script>$.address.state('/');$.address.value('/ipsec-about');</script>
    ";

    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec About',$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);
}
