<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$tpl=new template_admin();if(!$tpl->xPrivs()){die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();

function xgen(){
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $f[]="                	<ul class='nav nav-third-level'>";

    $f[]=$tpl->LeftMenu(array(
        "PAGE"=>"fw.postfix.saslusers.php?instance-id=$instance_id",
        "ICO"=>"fas fa-user-shield",
        "TEXT"=>"{local_members}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.smtpd_sender_login_maps.php?instance-id=$instance_id",
            "ICO"=>ico_users,"TEXT"=>"{smtp_sender_login_maps}"));

    $f[]=$tpl->LeftMenu(array(
        "PAGE"=>"fw.postfix.auth.whitelist.php?instance-id=$instance_id",
        "ICO"=>"fas fa-shield-halved",
        "TEXT"=>"{postfix.auth.whitelist.title2}"));

    $f[]=$tpl->LeftMenu(array(
        "PAGE"=>"fw.postfix.auth.network.php?instance-id=$instance_id",
        "ICO"=>"fas fa-user-lock",
        "TEXT"=>"{postfix.auth.network.title2}"));





	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}