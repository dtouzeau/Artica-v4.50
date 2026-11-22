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
    // --- Service & configuration ---



    // --- Comptes & boites aux lettres ---





    // --- Migration entrante ---
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.import-mailboxes.php?instance-id=$instance_id",
        "ICO"=>"fas fa-file-import","TEXT"=>"{cyrus.imapd.import.title}"));

    // --- Protection des donnees & recuperation ---
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.backups.php?instance-id=$instance_id",
        "ICO"=>"fas fa-box-archive","TEXT"=>"{cyrus.imapd.backups.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.deleted.php?instance-id=$instance_id",
        "ICO"=>"fas fa-trash-can-arrow-up","TEXT"=>"{cyrus.imapd.deleted.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.cold-storage.php?instance-id=$instance_id",
        "ICO"=>"fas fa-hard-drive","TEXT"=>"{cyrus.imapd.coldstorage.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.archive-policies.php?instance-id=$instance_id",
        "ICO"=>"fas fa-clock-rotate-left","TEXT"=>"{cyrus.imapd.policy.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.archive-jobs.php?instance-id=$instance_id",
        "ICO"=>"fas fa-snowflake","TEXT"=>"{cyrus.imapd.archive.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.archive-statistics.php?instance-id=$instance_id",
        "ICO"=>"fas fa-chart-pie","TEXT"=>"{cyrus.imapd.archive.stats.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.deep-archive.php?instance-id=$instance_id",
        "ICO"=>"fas fa-vault","TEXT"=>"{cyrus.imapd.deep.title}"));


    // --- Observabilite & tracabilite ---
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics.php?instance-id=$instance_id",
        "ICO"=>"fas fa-chart-line","TEXT"=>"{cyrus.imapd.stats.title}"));
    // Feature 017 — advanced statistics sub-pages
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-dashboard.php?instance-id=$instance_id",
        "ICO"=>"fas fa-gauge-high","TEXT"=>"{cyrus.imapd.stats.dashboard.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-mailboxes.php?instance-id=$instance_id",
        "ICO"=>"fas fa-inbox","TEXT"=>"{cyrus.imapd.stats.mailboxes.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-domains.php?instance-id=$instance_id",
        "ICO"=>"fas fa-globe","TEXT"=>"{cyrus.imapd.stats.domains.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-instances.php?instance-id=$instance_id",
        "ICO"=>"fas fa-layer-group","TEXT"=>"{cyrus.imapd.stats.instances.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-storage.php?instance-id=$instance_id",
        "ICO"=>"fas fa-database","TEXT"=>"{cyrus.imapd.stats.storage.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-partitions.php?instance-id=$instance_id",
        "ICO"=>"fas fa-hdd","TEXT"=>"{cyrus.imapd.stats.partitions.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-quotas.php?instance-id=$instance_id",
        "ICO"=>"fas fa-scale-balanced","TEXT"=>"{cyrus.imapd.stats.quotas.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-alerts.php?instance-id=$instance_id",
        "ICO"=>"fas fa-triangle-exclamation","TEXT"=>"{cyrus.imapd.stats.alerts.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-activity.php?instance-id=$instance_id",
        "ICO"=>"fas fa-chart-area","TEXT"=>"{cyrus.imapd.stats.activity.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-authentication.php?instance-id=$instance_id",
        "ICO"=>"fas fa-key","TEXT"=>"{cyrus.imapd.stats.auth.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-lmtp.php?instance-id=$instance_id",
        "ICO"=>"fas fa-truck-fast","TEXT"=>"{cyrus.imapd.stats.lmtp.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-search.php?instance-id=$instance_id",
        "ICO"=>"fas fa-magnifying-glass-chart","TEXT"=>"{cyrus.imapd.stats.search.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-collectors.php?instance-id=$instance_id",
        "ICO"=>"fas fa-diagram-project","TEXT"=>"{cyrus.imapd.stats.collectors.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-maintenance.php?instance-id=$instance_id",
        "ICO"=>"fas fa-screwdriver-wrench","TEXT"=>"{cyrus.imapd.stats.maintenance.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-export.php?instance-id=$instance_id",
        "ICO"=>"fas fa-file-export","TEXT"=>"{cyrus.imapd.stats.export.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.statistics-settings.php?instance-id=$instance_id",
        "ICO"=>"fas fa-sliders","TEXT"=>"{cyrus.imapd.stats.settings.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.jobs.php?instance-id=$instance_id",
        "ICO"=>"fas fa-tasks","TEXT"=>"{cyrus.imapd.jobs.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.audit.php?instance-id=$instance_id",
        "ICO"=>"fas fa-clipboard-list","TEXT"=>"{cyrus.imapd.audit.title}"));
    $f[]=$tpl->LeftMenu(array("PAGE"=>"fw.cyrus-imapd.logs.php?instance-id=$instance_id",
        "ICO"=>"fas fa-file-alt","TEXT"=>"{cyrus.imapd.logs.title}"));

	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}
