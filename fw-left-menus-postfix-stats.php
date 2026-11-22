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
    $users=new usersMenus();

    $f[]="                	<ul class='nav nav-third-level'>";

    // Raccordement SMTP/Postfix → Artica SIEM (pendant messagerie de
    // fw.proxy.siem.client.php) — même garde que l'entrée proxy.

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.siem.client.php", "ICO"=>"fas fa-share-nodes","TEXT"=>"{statistics_service}"));

    if(!$users->AllowViewStatistics){
        $f[]="					</ul>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
        return;
    }

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.statistics.php?instance-id=$instance_id",
        "ICO"=>ico_statistics,"TEXT"=>"{smtp_flow}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.statistics.users.php?instance-id=$instance_id",
        "ICO"=>"fas fa-users","TEXT"=>"{smtp_users_statistics}"));

    if($instance_id==0){

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.postfix.refused.php",
            "ICO" => ico_deny, "TEXT" => "{refused}"));

        $f[]=$tpl->LeftMenu(array(
            "PAGE"=>"fw.postfix.statistics.members.php",
            "ICO"=>ico_member,
            "TEXT"=>"{senders}"));

        // Feature 030 : qui s'est CONNECTE aux ports mail, par opposition a qui a envoye du
        // courrier. Les deux populations ne se recouvrent pas : un client qui ouvre quatre mille
        // connexions sans jamais delivrer un message n'apparait dans aucune autre page.
        $f[]=$tpl->LeftMenu(array(
            "PAGE"=>"fw.postfix.statistics.connections.php",
            "ICO"=>"fas fa-network-wired",
            "TEXT"=>"{smtp_connections}"));



    }
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}