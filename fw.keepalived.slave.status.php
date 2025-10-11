<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/ressources/class.keepalived.inc");
$users = new usersMenus();
if (!$users->AsVPNManager) {
    exit();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_GET["main"])) {
    main();
    exit;
}
if (isset($_GET["keepalived-status"])) {
    keepalived_status();
    exit;
}
if (isset($_GET["instance-stats-js"])) {
    instance_stats_js();
    exit;
}
if (isset($_GET["instance-stats"])) {
    instance_stats();
    exit;
}
if (isset($_GET["build-progress"])) {
    build_progress();
    exit;
}
page();
function instance_stats_js()
{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $title = $tpl->_ENGINE_parse_body("{statistics} - {$_GET["instance-name"]}");
    $instance_id = $_GET["instance-stats-js"];
    $tpl->js_dialog1($title, "$page?instance-stats=$instance_id");
}

function build_progress()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $sock = new sockets();
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "keepalived.php?reconfigure=yes";
    $ARRAY["TITLE"] = "{APP_KEEPALIVED} {restarting_service}";
    //$ARRAY["AFTER"]="LoadAjaxSilent('keepalived-status','$page?keepalived-status=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    echo $jsrestart;
}

function keepalived_status()
{
    $page = CurrentPageName();


    $sock = new sockets();
    $sock->getFrameWork("keepalived.php?status=yes");
    $bsini = new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/keepalived.status");
    $tpl = new template_admin();


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "keepalived.php?restart=yes";
    $ARRAY["TITLE"] = "{APP_KEEPALIVED} {restarting_service}";
    $ARRAY["AFTER"] = "LoadAjaxSilent('keepalived-status','$page?keepalived-status=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsRestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

    $final[] = $tpl->SERVICE_STATUS($bsini, "APP_KEEPALIVED", $jsRestart);

    VERBOSE(count($final) . " row", __LINE__);
    echo $tpl->_ENGINE_parse_body($final);

}

function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $KEEPALIVED_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION");
    $about = $tpl->_ENGINE_parse_body("{keepalived_what_is}");
    $about = str_replace("#Keepalived#", "<a href=\"https://www.keepalived.org\" target='_NEW' style='text-decoration:underline;font-weight:bold'>Keepalived</a>", $about);
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')","fa-solid fa-headset",null,null,"btn-blue");


    $html = "
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_KEEPALIVED} v$KEEPALIVED_VERSION</h1><p>$about</p>$btn</div>
	
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/failover-status');	
	
	LoadAjax('table-loader','$page?main=yes');

	</script>";

    $tpl = new templates();
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin('Artica: Failover Parameters', $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}

function main()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $keepalive_global_settings = new keepalived_global_settings();
    $keepalive_nodes = new keepalives_primary_nodes();
    $last_sync = $keepalive_nodes->lastSync();
    $last_sync = time_since($last_sync);
    $debug = intval($keepalive_global_settings->log_detail);

    $color = "gray";
    if ($debug == 1) {
        $color = "red";
    }
    $debug = ($debug == 0) ? 'Off' : 'On';
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");

    $secondary_node_query = "SELECT * from keepalived_primary_nodes";

    $secondary_node = $q->QUERY_SQL($secondary_node_query);
    if (!$q->ok) {
        echo $q->mysql_error_html();
    }

    $secNodeTXT="";
    $sep="";
    $c=0;
    foreach($secondary_node as $k=>$v){
        if($c>0){$sep="<br>";}
        $secNodeTXT.=$sep.$secondary_node[$k]["primaryNodeIP"];
        $c++;
    }



    $html[] = "
    <table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:width:30%'><div id='keepalived-status' style='margin-top:15px'></div></td>
		<td	style='vertical-align:top;width:70%'>
		<div class='row'>
		     <div class='col-md-12'>{$tpl->widget_h("lazur","fas fa-server","$secNodeTXT","{primary_node_name}")}</div>

             <div class='col-md-6'>{$tpl->widget_h("yellow","fad fa-clock","$last_sync","{last_sync}")}</div>
            <div class='col-md-6'>{$tpl->widget_h("$color","fal fa-debug","$debug","{debug_mode}")}</div>
                            
                    

    
            
            
            <div class='row' style='padding: 15px 30px; '>
            <div class='col-md-12' style='padding: 15px; border-bottom:1px dashed #000;'><i class='fas fa-heartbeat fa-3x animated infinite pulse' style='color: pink;'></i>&nbsp;&nbsp;&nbsp;<strong style='font-size: xx-large'>{health_checks_small}</strong></div>
		    </div>
		    <div class='row' style='padding: 15px 30px; text-transform: uppercase;font-weight: bold;'>
                <div class='col-md-3'>{status}</div><div class='col-md-3'>{services}</div><div class='col-md-3'>{floating_ip}</div><div class='col-md-3'>{actions}</div>";
    foreach($secondary_node as $k=>$v){

        $secondary_node_class = "text-danger";
        $label_secondary_node = "<div style='text-transform: uppercase;' class=\"label label-danger\">{$secondary_node[$k]['service_state']}</div>";
        if ($secondary_node[$k]['service_state'] == "MASTER" || $secondary_node[$k]['service_state'] == "UP") {
            $secondary_node_class = "text-success";
            $label_secondary_node = "<div style='text-transform: uppercase;' class=\"label label-success\">{$secondary_node[$k]['service_state']}</div>";
        }
        if ($secondary_node[$k]['service_state'] == "BACKUP") {
            $secondary_node_class = "text-info";
            $label_secondary_node = "<div style='text-transform: uppercase;' class=\"label label-info\">{$secondary_node[$k]['service_state']}</div>";
        }
        $services = $q->mysqli_fetch_array("SELECT group_concat(service) as services FROM `keepalived_services` WHERE primary_node_id='{$secondary_node[$k]["ID"]}'");
        $services = str_replace(',', '<br/>', $services["services"]);


        $vips = $q->mysqli_fetch_array("select * from keepalived_virtual_interfaces WHERE primary_node_id='{$secondary_node[$k]["ID"]}'");
        $stats = $tpl->icon_stats("Loadjs('$page?instance-stats-js={$secondary_node[$k]['ID']}&instance-name={$secondary_node[$k]['primary_node_name']}')", "AsSquidAdministrator", ($vips['enable'] == 1) ? false : true);
        $html[] = "<div class='col-md-3 $secondary_node_class'>$label_secondary_node</div><div class='col-md-3 $secondary_node_class'>$services</div><div class='col-md-3 $secondary_node_class'>{$vips["virtual_ip"]} ({$vips["label"]})</div><div class='col-md-3 $secondary_node_class'><a href='#' onclick=\"Loadjs('$page?instance-stats-js={$secondary_node[$k]['ID']}&instance-name={$secondary_node[$k]['primary_node_name']}')\"><i class='fas fa-chart-bar $secondary_node_class' style='font-size: 1.5em !important;'></i></a></div><br><hr><br>";}

    $html[] = "</div>
        </div>
        
</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('keepalived-status','$page?keepalived-status=yes');</script>	
	";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


function time_since($time)
{
    $time = time() - $time; // to get the time since that moment
    $time = ($time < 1) ? 1 : $time;
    $tokens = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '');
    }
}

function instance_stats()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $GLOBALS['CLASS_SOCKETS']->getFrameWork("keepalived.php?stats=true");
    $json_stats = @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/keepalived.json");
    $json_stats = json_decode($json_stats, TRUE);
    $instance_id = $_GET["instance-stats"];
    //print_r($json_stats);
    $html = array();
    $html[] = "<table id='table-stats' class=\"table table-stripped\"><thead><tr></tr></thead><tbody>";
    $i = 0;
    foreach ($json_stats as $key => $val) {
        if ($val['data']['iname'] == "VI_{$instance_id}") {
            foreach ($val['stats'] as $k => $v) {
                $class = (0 == $i % 2) ? 'even' : 'odd';
                $html[] = "<tr class='$class'>";
                $html[] = "<td class='text-capitalize' data-type='text'><strong>" . $k . "</strong></td><td>" . $v . "</td>";
                $html[] = "</tr>";
                $i++;
            }
        }
    }

    $html[] = "</tbody></table>";
    $html[] = "
	<script>
	NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "

	


	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    //echo @implode("\n", $html);

}