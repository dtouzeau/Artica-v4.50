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

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.sender-security.php?instance-id=$instance_id",
        "ICO"=>"fa-solid fa-passport","TEXT"=>"{smtp_sender_security}"));


    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.transport.security.php?instance-id=$instance_id",
        "ICO"=>"fa-solid fa-lock","TEXT"=>"{transport_security}"));


    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.ratelimit.php?instance-id=$instance_id",
        "ICO"=>ico_timeout,"TEXT"=>"{rate_limiting}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.disclaimers.php?instance-id=$instance_id",
            "ICO"=>ico_note,"TEXT"=>"{disclaimers}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.smime.php?instance-id=$instance_id",
        "ICO"=>"fas fa-signature","TEXT"=>"{smime_signing}"));


    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.oauth.php?instance-id=$instance_id",
        "ICO"=>"fas fa-envelope-circle-check","TEXT"=>"{postfix_oauth}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.egressguard.php?instance-id=$instance_id",
        "ICO"=>"fas fa-user-shield","TEXT"=>"EgressGuard"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.auto-compress.php?instance-id=$instance_id",
        "ICO"=>"fas fa-file-archive","TEXT"=>"{auto-compress}"));

	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}