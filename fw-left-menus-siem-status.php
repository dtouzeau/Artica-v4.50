<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();

function xgen(){
	$tpl=new template_admin();
    $users=new usersMenus();
    $f[]="                	<ul class='nav nav-third-level'>";
    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.statistics.proxies.php", "ICO" => ico_sensor,
        "TEXT" => "{nodes}"));
    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.statistics.install.php", "ICO" => ico_certificate,
        "TEXT" => "{mtls_clients}"));
    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.siem.statistics.php", "ICO" => ico_statistics,
        "TEXT" => "{siem_statistics}"));


    // Notifications système des nœuds (famille 043) : liste, fiche, suppression, purge.
    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.statistics.siem.hosts.events.php", "ICO" => "fas fa-bell",
        "TEXT" => "{siem_hosts_events_title}"));

    // La declaration des tenants est reservee a l'administration de l'appliance: la page
    // elle-meme refuse tout autre role (statistics_appliance::resolve_scope() n'accorde
    // `global` qu'a AsSystemAdministrator / AsAnAdministratorGeneric). L'entree n'a donc rien
    // a faire dans le menu d'un operateur de tenant, a qui elle ne peut offrir qu'un refus.
    if($users->AsSystemAdministrator or $users->AsAnAdministratorGeneric){
        $f[] = $tpl->LeftMenu(
            array("PAGE" => "fw.statistics.tenants.php",
                "ICO" => "fas fa-building", "TEXT" => "{tenants}"));
    }
    $f[] = $tpl->LeftMenu(
        array("PAGE" => "fw.statistics.osint.php",
            "ICO" => "fas fa-radar", "TEXT" => "OSINT"));


    $f[] = $tpl->LeftMenu(
        array("PAGE" => "fw.statistics.settings.php",
            "ICO" => "fas fa-bell", "TEXT" => "{alerts_settings}"));


	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}