<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
    $FORWARD_ZONES=false;
    $users=new usersMenus();
	$tpl=new template_admin();
    $EnableDNSFirewall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFirewall"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $UnboundEnabled     =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));

	$f[]="                	<ul class='nav nav-third-level'>";



    if($EnableDNSDist==1){
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.dnsdist.rules.php", "ICO" => "fas fa-list", "TEXT" => "{firewall_rules}"));


        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.dnsdist.graphs.php", "ICO" => ico_chart_line, "TEXT" => "{requests}"));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.dnsdist.events.php", "ICO" => ico_eye, "TEXT" => "{events}"));

        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.proxy.objects.list.php",
                "ICO"=>"fas fa-cubes","TEXT"=>"{rules_objects}",
            ) );
    }

    if($EnableDNSDist==0) {
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.SafeSearch.php", "ICO" => "fas fa-filter", "TEXT" => "SafeSearch(s)"));
    }

    if($EnableDNSDist==0) {
        $FORWARD_ZONES=true;
        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.dns.forward.zone.php",
                "ICO"=>"far fa-arrows","TEXT"=>"{forward_zones}",
            ) );
    }

    if($UnboundEnabled==1) {
        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.dns.unbound.domains.php",
                "ICO" => "fab fab fa-soundcloud", "TEXT" => "{local_domains}",
            ));
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.unbound.redis.php", "ICO" => ico_database, "TEXT" => "{memory_database}"));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.dns.unbound.records.php", "ICO" => "fa fa-list-ol", "TEXT" => "{DNS_RECORDS}"));


        $f[]=$tpl->LeftMenu(
            array("PAGE"=>"fw.dns.agents.php",
                "ICO"=>"fas fa-project-diagram","TEXT"=>"Artica agents",
            ) );

        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.pdns.rpz.php",
                "ICO" => ico_shield, "TEXT" => "{POLICIES_ZONES}",
            ));

        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.unbound.events.php",
                "ICO" => ico_eye, "TEXT" => "{service_events}",
            ));
    }
	
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}