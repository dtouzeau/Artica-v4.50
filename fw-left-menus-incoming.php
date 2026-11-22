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

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.postscreen.php?instance-id=$instance_id",
        "ICO"=>"fas fa-shield-alt","TEXT"=>"{postscreen}"));

    $APP_KSMG_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KSMG_INSTALLED"));
    if($APP_KSMG_INSTALLED==1 && $instance_id==0){
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.ksmg.php",
            "ICO"=>"fa-kaspersky","TEXT"=>"Secure Mail Gateway"));
    }

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.rbl.php?instance-id=$instance_id",
        "ICO"=>"fas fa-badge-check","TEXT"=>"{ip_reputation}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.rspamd.php?instance-id=$instance_id",
        "ICO"=>ico_scanner_gun,"TEXT"=>"Anti-Spam"));

    // Feature 032: the quarantine is global (instance 0 owns the release domain and the store).
    if($instance_id==0){
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.quarantine.php",
            "ICO"=>"fas fa-inbox","TEXT"=>"{mail_quarantine}"));
    }

    // Feature 037: URL threat scan (global, like the quarantine).
    if($instance_id==0){
        $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.urlscan.php",
            "ICO"=>"fas fa-link-slash","TEXT"=>"{urlscan_title}"));
    }

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.autoblock.php?instance-id=$instance_id",
        "ICO"=>"fas fa-ban","TEXT"=>"{autoblock.title}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.bounce.guard.php?instance-id=$instance_id",
        "ICO"=>"fas fa-recycle","TEXT"=>"{bounceguard.title}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.client.limits.php?instance-id=$instance_id",
        "ICO"=>"fas fa-gauge-high","TEXT"=>"{clientlimits.title}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.volume.alerts.php?instance-id=$instance_id",
        "ICO"=>"fas fa-chart-area","TEXT"=>"{volumealert.title}"));

    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.postfix.smtp.hardening.php?instance-id=$instance_id",
        "ICO"=>"fas fa-user-shield","TEXT"=>"{smtphard.title}"));




	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}