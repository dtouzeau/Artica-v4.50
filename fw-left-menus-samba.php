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
    // Instance IDs are 8-char hex strings (e.g. "728ec11c"); intval() would
    // truncate them at the first non-digit. Sanitize to alphanumeric instead.
    $instance_id=preg_replace("#[^a-zA-Z0-9]#","",(string)$_GET["instance-id"]);
    $f[]="                	<ul class='nav nav-third-level'>";

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.shares2.php?instance-id=$instance_id",
            "ICO"=>ico_directory,"TEXT"=>"{shares_center}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.users2.php?instance-id=$instance_id",
            "ICO"=>"fas fa-users","TEXT"=>"{users}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.groups2.php?instance-id=$instance_id",
            "ICO"=>"fas fa-user-group","TEXT"=>"{groups}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.security2.php?instance-id=$instance_id",
            "ICO"=>"fa fa-shield-halved","TEXT"=>"{folder_security}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.indexer.php?instance-id=$instance_id",
            "ICO"=>"fas fa-magnifying-glass","TEXT"=>"{articaindexer}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.syncs.php?instance-id=$instance_id",
            "ICO"=>"fas fa-arrows-rotate","TEXT"=>"{sync_rules}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.activity2.php?instance-id=$instance_id",
            "ICO"=>"fas fa-users-viewfinder","TEXT"=>"{live_sessions}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.agents2.php?instance-id=$instance_id",
            "ICO"=>"fas fa-desktop","TEXT"=>"{workstations}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.agents.identities.php?instance-id=$instance_id",
            "ICO"=>"fas fa-id-card","TEXT"=>"{windows_identities}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.metrics2.php?instance-id=$instance_id",
            "ICO"=>"fad fa-chart-bar","TEXT"=>"{statistics}",
        ) );

    $f[]=$tpl->LeftMenu(
        array("PAGE"=>"fw.samba.logs.php?instance-id=$instance_id",
            "ICO"=>ico_eye,"TEXT"=>"{events}",
        ) );


	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}