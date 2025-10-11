<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__) . "/ressources/class.keepalived.inc");
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
$secondary_nodeIsenable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
if($secondary_nodeIsenable==1){
    echo "Failover secondary_node is installed";
    die();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_GET["table"])) {
    table();
    exit;
}
if (isset($_GET["primary_node-js"])) {
    primary_node_js();
    exit;
}

if (isset($_GET["new-primary_node-wizard-start"])) {
    new_primary_node_wizard_start();
    exit;
}
if (isset($_GET["new-primary_node-wizard-1"])) {
    new_primary_node_wizard_1();
    exit;
}
if (isset($_GET["new-primary_node-wizard-2"])) {
    new_primary_node_wizard_2();
    exit;
}
if (isset($_GET["new-primary_node-wizard-3"])) {
    new_primary_node_wizard_3();
    exit;
}

if (isset($_POST["SAVE_primary_node_WIZARD"])) {
    primary_node_wizard_session_vals();
    exit;
};
if (isset($_POST["new-primary_node-wizard-save"])) {
    create_primary_node_node();
    exit;
}
if (isset($_GET["edit-instance"])) {
    edit_instance();
    exit;
}

if (isset($_GET["primary_node-parameters"])) {
    primary_node_parameters();
    exit;
}
if (isset($_GET["primary_node-parameters-final"])) {
    primary_node_parameters_final();
    exit;
}

if (isset($_GET["primary_node-services"])) {
    primary_node_services();
    exit;
}
if (isset($_GET["primary_node-services-list"])) {
    primary_node_services_list();
    exit;
}
if (isset($_GET["primary_node-services-js"])) {
    primary_node_services_js();
    exit;
}
if (isset($_GET["add-primary_node-service"])) {
    add_primary_node_service();
    exit;
}
if (isset($_POST["save_primary_node_service"])) {
    save_primary_node_service();
    exit;
};
if (isset($_GET["primary_node-services-delete-js"])) {
    delete_services_js();
    exit;
}
if (isset($_POST["delete-service"])) {
    delete_service();
    exit;
};

//SCRIPT PARAMS

if (isset($_GET["primary_node-scripts-params-js"])) {
    primary_node_scripts_params_js();
    exit;
}
if (isset($_GET["add-primary_node-scripts_params"])) {
    add_primary_node_scripts_params();
    exit;
}
if (isset($_POST["save_primary_node_scripts_params"])) {
    save_primary_node_scripts_params();
    exit;
};


if (isset($_GET["primary_node-virtualip"])) {
    primary_node_virtualip();
    exit;
}
if (isset($_GET["primary_node-virtualip-list"])) {
    primary_node_virtualip_list();
    exit;
}
if (isset($_GET["primary_node-virtualip-js"])) {
    primary_node_virtualip_js();
    exit;
}
if (isset($_GET["add-primary_node-virtualip"])) {
    add_primary_node_virtualip();
    exit;
}
if (isset($_POST["save_primary_node_virtualip"])) {
    save_primary_node_virtualip();
    exit;
};
if (isset($_GET["primary_node-virtualip-delete-js"])) {
    delete_virtualip_js();
    exit;
}
if (isset($_POST["delete-virtualip"])) {
    delete_virtualip();
    exit;
};

if (isset($_GET["secondary_node"])) {
    secondary_node();
    exit;
}
if (isset($_GET["secondary_node-list"])) {
    secondary_node_list();
    exit;
}
if (isset($_GET["secondary_node-js"])) {
    secondary_node_js();
    exit;
}
if (isset($_GET["add-secondary_node"])) {
    add_secondary_node();
    exit;
}
if (isset($_POST["save_secondary_node"])) {
    save_secondary_node();
    exit;
};
if (isset($_GET["secondary_node-delete-js"])) {
    delete_secondary_node_js();
    exit;
}
if (isset($_POST["delete-secondary_node"])) {
    delete_secondary_node();
    exit;
};

if (isset($_GET["primary_node-trackinterfaces"])) {
    primary_node_trackinterfaces();
    exit;
}
if (isset($_GET["primary_node-trackinterfaces-list"])) {
    primary_node_trackinterfaces_list();
    exit;
}
if (isset($_GET["primary_node-trackinterfaces-js"])) {
    primary_node_trackinterfaces_js();
    exit;
}
if (isset($_GET["add-primary_node-trackinterfaces"])) {
    add_primary_node_trackinterfaces();
    exit;
}
if (isset($_POST["save_primary_node_trackinterfaces"])) {
    save_primary_node_trackinterfaces();
    exit;
};
if (isset($_GET["primary_node-trackinterfaces-delete-js"])) {
    delete_trackinterfaces_js();
    exit;
}
if (isset($_POST["delete-trackinterfaces"])) {
    delete_trackinterfaces();
    exit;
};


if (isset($_POST["primary_node_id"])) {
    primary_node_parameters_save();
    exit;
}

if (isset($_GET["primary_node-enable-js"])) {
    primary_node_enable_js();
    exit;
}

if (isset($_POST["primary_node-enable"])) {
    primary_node_enable();
    exit;
}

if (isset($_GET["primary_node-delete-js"])) {
    primary_node_delete_js();
    exit;
}
if (isset($_POST["primary_node-delete"])) {
    primary_node_delete();
    exit;
}
if (isset($_GET["secondary_node-enable-js"])) {
    secondary_node_enable_js();
    exit;
}

if (isset($_POST["secondary_node-enable"])) {
    secondary_node_enable();
    exit;
}

if (isset($_GET["secondary_node-delete-js"])) {
    secondary_node_delete_js();
    exit;
}
if (isset($_POST["secondary_node-delete"])) {
    secondary_node_delete();
    exit;
}
if (isset($_GET["primary_node-stats-js"])) {
    primary_node_stats_js();
    exit;
}
if (isset($_GET["primary_node-stats"])) {
    primary_node_stats();
    exit;
}

page();


function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html = "
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_KEEPALIVED} &nbsp;&raquo;&nbsp; {services}</h1>
	<table class='table'>
	<tr class='d-flex'>
	<td class='col-xs-7'><p>{APP_KEEPALIVED_INSTANCES}</p></td><td><img style='width: 100%' src='img/ha-diagram-animated.gif' /></td>
</tr>
</table>
	
	

	</div>

	</div>



	<div class='row'><div id='progress-keepalived-restart'></div>
	<div class='ibox-content'>

	<div id='table-keepalived-services'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/failover-services');		
	LoadAjax('table-keepalived-services','$page?table=yes');

	</script>";

    if (isset($_GET["main-page"])) {
        $tpl = new template_admin(null, $html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl = new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function primary_node_enable_js()
{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node-enable-js"];
    $value = "$primary_node_id";
    $keepalived_node = new keepalives_primary_nodes($primary_node_id);
    $action = "";
    $action_text = "";
    if ($keepalived_node->enable == 1) {
        $action_text = "disable";
        $action = "Disable $keepalived_node->primary_node_name";
    } else {
        $action_text = "enable";
        $action = "Enable $keepalived_node->primary_node_name";
    }
    $extra = "";
    if (intval($keepalived_node->isPrimaryNode) == 1) {
        $extra = "This operation will $action_text all secondary_nodes as well.";
    }
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-keepalived-restart')";


    $tpl->js_confirm_execute("$action. $extra", "primary_node-enable", $value, $jsrestart);
}

function primary_node_enable()
{

    $keepalived_node = new keepalives_primary_nodes($_POST["primary_node-enable"]);
    if ($keepalived_node->enable == 1) {
        $keepalived_node->enable = 0;
    } else {
        $keepalived_node->enable = 1;
    }
    $keepalived_node->save();
}

function primary_node_delete_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $instancename = $_GET["primary_node-name"];
    $primary_node_id = $_GET["primary_node-delete-js"];
    $value = "$instancename|$primary_node_id";
    $keepalived_node = new keepalives_primary_nodes($primary_node_id);
    $extra = "";
    if (intval($keepalived_node->isPrimaryNode) == 1) {
        $extra = "<br><strong>This operation will delete all secondary_nodes as well</strong>";
    }


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-keepalived-restart')";
    $tpl->js_confirm_delete("$instancename/$primary_node_id $extra", "primary_node-delete", $value, $jsrestart);

}

function primary_node_delete()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["primary_node-delete"]);
    $keepalived_node = new keepalives_primary_nodes($f[1]);
    $keepalived_node->delete();

}

function secondary_node_enable_js()
{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $secondary_node_id = $_GET["secondary_node-enable-js"];
    $primary_node_id = $_GET["primary_node_id"];
    $value = "$primary_node_id|$secondary_node_id";
    $keepalived_node = new keepalived_secondary_nodes($primary_node_id, $secondary_node_id);
    $action = "";
    $action_text = "";
    if ($keepalived_node->enable == 1) {
        $action_text = "disable";
        $action = "Disable secondary_node $keepalived_node->secondary_node_ip";
    } else {
        $action_text = "enable";
        $action = "Enable secondary_node $keepalived_node->secondary_node_ip";
    }


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-keepalived-restart')";
    $tpl->js_confirm_execute("$action.", "secondary_node-enable", $value, $jsrestart);
}

function secondary_node_enable()
{
    $ids = explode("|", $_POST["secondary_node-enable"]);
    $keepalived_node = new keepalived_secondary_nodes($ids[0], $ids[1]);
    if ($keepalived_node->enable == 1) {
        $keepalived_node->enable = 0;
    } else {
        $keepalived_node->enable = 1;
    }
    $keepalived_node->save();
}

function secondary_node_delete_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $secondary_node_id = $_GET["secondary_node-delete-js"];
    $value = "$primary_node_id|$secondary_node_id";
    $keepalived_node = new keepalived_secondary_nodes($primary_node_id, $secondary_node_id);
    $extra = "Remove Slave $keepalived_node->secondary_node_ip ";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-keepalived-restart')";
    $tpl->js_confirm_delete("$extra", "secondary_node-delete", $value, $jsrestart);

}

function secondary_node_delete()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["secondary_node-delete"]);
    $keepalived_node = new keepalived_secondary_nodes($f[0], $f[1]);
    $keepalived_node->delete();

}

function primary_node_stats_js()
{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $title = $tpl->_ENGINE_parse_body("{statistics} - {$_GET["primary_node-name"]}");
    $primary_node_id = $_GET["primary_node-stats-js"];
    $tpl->js_dialog1($title, "$page?primary_node-stats=$primary_node_id");
}

function primary_node_stats()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $GLOBALS['CLASS_SOCKETS']->getFrameWork("keepalived.php?stats=true");
    $json_stats = @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/keepalived.json");
    $json_stats = json_decode($json_stats, TRUE);
    $primary_node_id = $_GET["primary_node-stats"];
    //print_r($json_stats);
    $html = array();
    $html[] = "<table id='table-stats' class=\"table table-stripped\"><thead><tr></tr></thead><tbody>";
    $i = 0;
    foreach ($json_stats as $key => $val) {
        if ($val['data']['iname'] == "VI_{$primary_node_id}") {
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

function primary_node_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $new_service = $tpl->_ENGINE_parse_body("{new_instance}");
    $primary_node_id = urlencode($_GET["primary_node-js"]);
    if ($primary_node_id == null) {
        $title = $new_service;
        $tpl->js_dialog2($title, "$page?new-primary_node-wizard-start=yes");
        return;
    }

    $title = $_GET["title"];
    $tpl->js_dialog1($title, "$page?edit-instance=$primary_node_id");
}

function edit_instance()
{

    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["edit-instance"];
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $array["{parameters}"] = "$page?primary_node-parameters=$primary_node_id";
    $array["{services}"] = "$page?primary_node-services=$primary_node_id";
    $array["{virtual_ip}"] = "$page?primary_node-virtualip=$primary_node_id";
    $array["{secondary_nodes}"] = "$page?secondary_node=$primary_node_id";
    $array["{track_interfaces}"] = "$page?primary_node-trackinterfaces=$primary_node_id";
    $masterInfo=new keepalives_primary_nodes($_GET["edit-instance"]);
    $text="{master}";
    if(intval($masterInfo->secondaryNodeIsDisconnected)==1){
        $text="{management_by} {primary_node_keepalived}";

    }
    if(intval($masterInfo->isPrimaryNode)==0){
        $title= '<h3><strong class="text-success">'.$text.' '.$masterInfo->primaryNodeIP.'</strong></h3>';
        echo $tpl->_ENGINE_parse_body($title);
    }
    echo $tpl->tabs_default($array);

}

//ADD INSTANCE Master
function new_primary_node_wizard_start()
{
    $page = CurrentPageName();
    echo "<div id='wizard-primary_node-for-steps'></div>
		<script>LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-1=yes');</script>
	";
}

function new_primary_node_wizard_1()
{
    $users = new usersMenus();
    $page = CurrentPageName();
    $tpl = new template_admin();
    $keepalived_services = new keepalived_services();
    $hostname = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");

    $tpl->field_hidden("SAVE_primary_node_WIZARD", 1);
    $form[] = $tpl->field_text("primary_node_name", "{instance} {hostname} / {ipaddr}", isset($_SESSION["MASTER"]["hostname"]) ? $_SESSION["MASTER"]["hostname"] : $hostname, true);
    $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{listen_interface}", $_SESSION["MASTER"]["interface"], null);
    $form[] = $tpl->field_hidden("primary_node_state", "MASTER");
    $form[] = $tpl->field_hidden("virtual_router_id", 30);
    $form[] = $tpl->field_hidden("priority", 200);
    $form[] = $tpl->field_hidden("advert_int", 1);
    $form[] = $tpl->field_array_hash($keepalived_services->service_array, "service", "{monitor_service}", isset($_SESSION["MASTER"]["service"]) ? $_SESSION["MASTER"]['service'] : 'Proxy', true, "{monitor_service_explain}",null,false);
    $jsafter = "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-2=yes');";

    $html = $tpl->form_outside("{lets_get_start}", $form, "{configure_peers_alert}", "{next}", $jsafter, "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);

}

function new_primary_node_wizard_2()
{
    $users = new usersMenus();
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->field_hidden("SAVE_primary_node_WIZARD", 1);
    $form[] = $tpl->field_ipaddr("virtualip", "{ipaddr}", $_SESSION["MASTER"]["virtualip"], true);
    $form[] = $tpl->field_maskcdir('netmask', '{netmask}', isset($_SESSION["MASTER"]["netmask"]) ? $_SESSION["MASTER"]["netmask"] : 24, true);
    $form[] = $tpl->field_interfaces('dev', "nooloopNoDef:{local_interface_dev}", $_SESSION["MASTER"]["dev"], null);

    $jsafter = "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-3=yes');";

    // Back Button
    $tpl->form_add_button("{back}", "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-1=yes');");

    $html = $tpl->form_outside("{now_lets_configure_the_shared_ip}", $form, "{virtual_ip_explain}", "{next}", $jsafter, "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);

}

function new_primary_node_wizard_3()
{
    $users = new usersMenus();
    $page = CurrentPageName();
    $tpl = new template_admin();

    //$tpl->field_hidden("SAVE_primary_node_WIZARD", 1);
    $tpl->field_hidden("new-primary_node-wizard-save", 1);
    $form[] = $tpl->field_ipv4('secondary_node_ip', '{ipaddr}', $_SESSION['MASTER']['secondary_node_ip'], true);
    $form[] = $tpl->field_numeric('secondary_node_port', '{artica_listen_port}', isset($_SESSION['MASTER']['secondary_node_port']) ? $_SESSION['MASTER']['secondary_node_port'] : 9000, null);


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "dialogInstance2.close();LoadAjax('table-keepalived-services','$page?table=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-keepalived-restart')";


    // Back Button

    $tpl->form_add_button("{back}", "LoadAjax('wizard-primary_node-for-steps','$page?new-primary_node-wizard-2=yes');");

    $html = $tpl->form_outside("{almost_there_secondary_nodes}", $form, "{secondary_nodes_explain}", "{save}", $jsrestart, "AsSquidAdministrator", true);
    echo $tpl->_ENGINE_parse_body($html);

}

function primary_node_wizard_session_vals()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    foreach ($_POST as $key => $value) {
        $_SESSION["MASTER"][$key] = $value;
    }
}

function create_primary_node_node()
{
    $unix = new unix();
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $keepalived_node = new keepalives_primary_nodes();
    $keepalived_vips = new keepalived_vips();
    $keepalived_secondary_node = new keepalived_secondary_nodes();
    $keepalived_services = new keepalived_services();
    foreach ($_POST as $key => $value) {
        $_SESSION["MASTER"][$key] = $value;
    }
    if (empty($_SESSION['MASTER']["secondary_node_port"])) {
        $_SESSION['MASTER']["secondary_node_port"] = 9000;
    }
    $ArticaHttpsPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    if ($ArticaHttpsPort == 0) {
        $ArticaHttpsPort = 9000;
    }
    if ($unix->InterfaceToIPv4($_SESSION['MASTER']['interface']) == $_SESSION['MASTER']['virtualip']) {
        echo "jserror:Listen IP is equal to Virtual IP";
        return false;
    }
    if ($unix->InterfaceToIPv4($_SESSION['MASTER']['interface']) == $_SESSION['MASTER']['secondary_node_ip']) {
        echo "jserror:Listen IP is equal to Slave IP";
        return false;
    }

    if ($_SESSION['MASTER']['virtualip'] == $_SESSION['MASTER']['secondary_node_ip']) {
        echo "jserror:Virtual IP is equal to Slave IP";
        return false;
    }
    if (!filter_var($_SESSION['MASTER']['virtualip'], FILTER_VALIDATE_IP)) {
        echo "jserror:Invalid Virtual IP Address";
        return false;
    }
    if (!filter_var($_SESSION['MASTER']['secondary_node_ip'], FILTER_VALIDATE_IP)) {
        echo "jserror:Invalid Slave IP Address";
        return false;
    }

    $countDups = $keepalived_vips->duplicates($_SESSION['MASTER']['virtualip']);
    if ($countDups > 0) {
        echo "jserror:Virtual IP {$_POST["virtualip"]} already exist";
        return false;
    }

    $countDups = $keepalived_secondary_node->duplicates($_SESSION['MASTER']['secondary_node_ip']);
    if ($countDups > 0) {
        echo "jserror:The Slave with ip {$_POST["virtualip"]} already exist";
        return false;
    }

//    $countDups = $keepalived_services->duplicates($_SESSION['MASTER']['service']);
//    if ($countDups > 0) {
//        echo "jserror:The service {$_POST["service"]} is already monitor by another instance";
//        return false;
//    }
    $countDups = $keepalived_vips->vnicExist($_SESSION['MASTER']['virtualip']);
    if ($countDups > 0) {
        echo "jserror:Virtual IP {$_POST["virtualip"]} already exist";
        return false;
    }



    //SAVE NODE

    $keepalived_node->primary_node_name = $_SESSION['MASTER']['primary_node_name'];
    $keepalived_node->interface = $_SESSION['MASTER']['interface'];
    $keepalived_node->primary_node_state = $_SESSION['MASTER']['primary_node_state'];
    $keepalived_node->virtual_router_id = intval($_SESSION['MASTER']['virtual_router_id']);
    $keepalived_node->priority = intval($_SESSION['MASTER']['priority']);
    $keepalived_node->advert_int = intval($_SESSION['MASTER']['advert_int']);
    $keepalived_node->isPrimaryNode = 1;
    $keepalived_node->primaryNodeIP = $unix->InterfaceToIPv4($_SESSION['MASTER']['interface']);
    $keepalived_node->primaryNodePort = $ArticaHttpsPort;
    $keepalived_node->secondaryNodeIsDisconnected = 0;
    $keepalived_node->synckey = microtime(true);

    $keepalived_node->save();
    $last_id = $keepalived_node->last_id;
    $last_primary_node_info = new keepalives_primary_nodes($last_id);

    //SAVE SERVICE
    $keepalived_services->primary_node_id= $last_id;
    $keepalived_services->service=$_SESSION['MASTER']['service'];
    $keepalived_services->enable=1;
    $keepalived_services->synckey== microtime(true);
    $keepalived_services->save();

    //SAVE VIP

    $keepalived_vips->primary_node_id = $last_id;
    $keepalived_vips->dev = $_SESSION['MASTER']['dev'];
    $keepalived_vips->virtual_ip = $_SESSION['MASTER']['virtualip'];
    $keepalived_vips->netmask = $_SESSION['MASTER']['netmask'];
    $keepalived_vips->synckey = microtime(true);
    $keepalived_vips->save();

    //SAVE SLAVE

    $keepalived_secondary_node->primary_node_id = $last_id;
    $keepalived_secondary_node->secondary_node_ip = $_SESSION['MASTER']["secondary_node_ip"];
    $keepalived_secondary_node->primary_node_ip = $unix->InterfaceToIPv4($_SESSION['MASTER']['interface']);
    $keepalived_secondary_node->secondary_node_can_overwrite_settings = 0;
    $keepalived_secondary_node->synckey = microtime(true);
    $keepalived_secondary_node->priority = $last_primary_node_info->priority - $last_id - $keepalived_secondary_node->count_secondary_nodes;
    $keepalived_secondary_node->save();
    unset($_SESSION['MASTER']);

}

//END ADD INSTANCE

//EDIT INSTANCE
function primary_node_parameters()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-parameters"];
    $primary_node_id = urlencode($primary_node_id);
    $md5 = md5(time() . $primary_node_id);
    echo "<div id='primary_node_parameters_$md5'></div>
    <script>
        function InstanceMainParameters(){
            LoadAjax('primary_node_parameters_$md5','$page?primary_node-parameters-final=$primary_node_id')
            
        }
        InstanceMainParameters();
    </script>
    ";
}

function primary_node_parameters_final()
{

    $tt = $_GET["t"];
    $users = new usersMenus();
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node-parameters-final"];
    $keepalived_node = new keepalives_primary_nodes($primary_node_id);
    $buttonname = "{apply}";
    if ($primary_node_id == null) {
        $buttonname = "{add}";
    }
    $title = "{edit_VRRP_instance}";
    //$jsafter[] = "LoadAjax('table-keepalived-services','$page?table=yes');";


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes');";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";

    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = false;

    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }
    $form[] = $tpl->form_add_button_title('Expert Mode', 'switchModes()');
    $form[] = $tpl->field_hidden("enable", "{$keepalived_node->enable}");
    $form[] = $tpl->field_hidden("primary_node_id", "{$keepalived_node->primary_node_id}");
    $form[] = $tpl->field_text("primary_node_name", "{instance} {hostname} / {ipaddr}", "{$keepalived_node->primary_node_name}", true);
    if ($isDisable) {
        $form[] = $tpl->field_text("interface", "{listen_interface}", "{$keepalived_node->interface}", true, null, $isDisable, $isDisable);
    } else {
        $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{listen_interface}", "{$keepalived_node->interface}", null);
    }


    $form[] = $tpl->field_text("primary_node_state", "{state}", "{$keepalived_node->primary_node_state}", true, null, true, true);
    //EXPERT ONLY
    $form[] = $tpl->field_numeric("virtual_router_id", '{virtual_router_id}', "{$keepalived_node->virtual_router_id}", '{virtual_router_id_explain}');
    $form[] = $tpl->field_numeric("priority", '{priority}', "{$keepalived_node->priority}", '{priority_explain}');
    $form[] = $tpl->field_numeric("advert_int", '{advert_int}', "{$keepalived_node->advert_int}", '{advert_int_explain}');
    $form[] = $tpl->field_checkbox("no_preempt", "{nopreempt_1}", "{$keepalived_node->nopreempt}", false, "{nopreempt}", $isDisable);
    $form[] = $tpl->field_checkbox("use_vmac", "{use_vmac}", "{$keepalived_node->use_vmac}", "vmac_xmit_base", "{use_vmac_explain}");
    $form[] = $tpl->field_checkbox("vmac_xmit_base", "{vmac_xmit_base}", "{$keepalived_node->vmac_xmit_base}", false, "{vmac_xmit_base_explain}");
//    if ($primary_node_info->isPrimaryNode == 1) {
//        $form[] = $tpl->field_hidden("no_preempt", "{$keepalived_node->nopreempt}");
//    } else {
//        $form[] = $tpl->field_checkbox("no_preempt", "{nopreempt_1}", "{$keepalived_node->nopreempt}", false, "{nopreempt}", $isDisable);
//    }
    $form[] = $tpl->field_checkbox("unicast_src_ip", "{unicast_src_ip}", "{$keepalived_node->unicast_src_ip}", false, "{unicast_src_ip_explain}", $isDisable);
    $form[] = $tpl->field_checkbox("enable_peers_ttl", "{enable_ttl}", "{$keepalived_node->enable_peers_ttl}", "min_peers_ttl,max_peers_ttl", null, $isDisable);
    $form[] = $tpl->field_numeric("min_peers_ttl", "{min_ttl}", $keepalived_node->min_peers_ttl);
    $form[] = $tpl->field_numeric("max_peers_ttl", "{max_ttl_1}", $keepalived_node->max_peers_ttl);
    $form[] = $tpl->field_checkbox("auth_enable", "{authentication}", "{$keepalived_node->auth_enable}", "auth_pass", null, $isDisable);
    $form[] = $tpl->field_password("auth_pass", "{password}", "{$keepalived_node->auth_pass}");
    $form[] = $tpl->field_checkbox("notifty_enable", "{enable_notify}", "{$keepalived_node->notifty_enable}", "notifty", null, $isDisable);
    $form[] = $tpl->field_text("notifty", "{notify_script}", "{$keepalived_node->notifty}", false, "{notify_script_explain}");

    $form[] = '<script>';
    if ($isDisable == true) {
        $form[] = '
         $( document ).ready(function() {      
             $("input").attr("readonly", "readonly");
             toggle(false)
         });
        ';
    } else {
        $form[] = '
           $( document ).ready(function() {      
                toggle(false)
            });
       ';
    }
    $form[] = '
    function toggle(display){
        $("input[name=virtual_router_id]").closest("tr").toggle(display);
        $("input[name=priority]").closest("tr").toggle(display);
        $("input[name=advert_int]").closest("tr").toggle(display);
        $("input[name=no_preempt]").closest("tr").toggle(display);
        $("input[name=unicast_src_ip]").closest("tr").toggle(display);
        $("input[name=enable_peers_ttl]").closest("tr").toggle(display);
        $("input[name=min_peers_ttl]").closest("tr").toggle(display);
        $("input[name=max_peers_ttl]").closest("tr").toggle(display);
        $("input[name=auth_enable]").closest("tr").toggle(display);
        $("input[name=auth_pass]").closest("tr").toggle(display);
        $("input[name=notifty_enable]").closest("tr").toggle(display);
        $("input[name=notifty]").closest("tr").toggle(display);
        $("input[name=use_vmac]").closest("tr").toggle(display);
        $("input[name=vmac_xmit_base]").closest("tr").toggle(display);
    }
    function switchModes(){
        
        let btnName=$(".btn-sm").text();
        if(btnName=="Expert Mode"){
            $(".btn-sm").text("Basic Mode");
            toggle(true)
        }
       if(btnName=="Basic Mode"){
            $(".btn-sm").text("Expert Mode");
            toggle(false)
        }
    }
    </script>';

    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $buttonname, $jsafter, "AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}

function primary_node_parameters_save()
{
    $unix = new unix();

    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $keepalived_node = new keepalives_primary_nodes($_POST['primary_node_id']);


    if ($_POST["auth_enable"] == 1) {
        if (empty($_POST["auth_pass"])) {
            echo "jserror:Empty password";
            return false;
        }
    }

    if ($_POST["notifty_enable"] == 1) {
        if (empty($_POST["notifty"])) {
            echo "jserror:Empty notify script";
            return false;
        }
    }

    if (empty($_SESSION['MASTER']["secondary_node_port"])) {
        $_SESSION['MASTER']["secondary_node_port"] = 9000;
    }
    $ArticaHttpsPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    if ($ArticaHttpsPort == 0) {
        $ArticaHttpsPort = 9000;
    }

    if ($_POST["enable_peers_ttl"] == 1) {
        if (empty($_POST["min_peers_ttl"]) || intval($_POST["min_peers_ttl"])==0) {
            echo "jserror:Empty or 0 {min_ttl}";
            return false;
        }
        if (empty($_POST["max_peers_ttl"]) || intval($_POST["max_peers_ttl"])==0) {
            echo "jserror:Empty or 0 {max_ttl}";
            return false;
        }
    }



    //$keepalives_primary_nodes->primary_node_id = $_POST['primary_node_id'];
    $keepalived_node->primary_node_name = $_POST['primary_node_name'];
    $keepalived_node->interface = $_POST['interface'];
    $keepalived_node->virtual_router_id = intval($_POST['virtual_router_id']);
    $keepalived_node->priority = intval($_POST['priority']);
    $keepalived_node->advert_int = intval($_POST['advert_int']);
    $keepalived_node->nopreempt = intval($_POST['no_preempt']);
    $keepalived_node->unicast_src_ip = intval($_POST['unicast_src_ip']);
    $keepalived_node->enable_peers_ttl=intval($_POST['enable_peers_ttl']);
    $keepalived_node->max_peers_ttl=intval($_POST['max_peers_ttl']);
    $keepalived_node->min_peers_ttl=intval($_POST['min_peers_ttl']);
    $keepalived_node->auth_enable = intval($_POST['auth_enable']);
    $keepalived_node->auth_pass = $_POST['auth_pass'];
    $keepalived_node->notifty_enable = intval($_POST['notifty_enable']);
    $keepalived_node->notifty = $_POST['notifty'];
    $keepalived_node->enable = intval($_POST['enable']);
    $keepalived_node->primaryNodeIP = $unix->InterfaceToIPv4($_POST['interface']);
    $keepalived_node->primaryNodePort = $ArticaHttpsPort;
    $keepalived_node->secondaryNodeIsDisconnected = 0;
    $keepalived_node->use_vmac = intval($_POST['use_vmac']);
    $keepalived_node->vmac_xmit_base = intval($_POST['vmac_xmit_base']);
    $keepalived_node->save();

}

//END EDIT INSTANCE

//SERVICES
function primary_node_services()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-services"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = '';
    $go_failver_checker_ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("go-failover-checker-ver");
    if (empty($go_failver_checker_ver)){
        $go_failver_checker_ver="0.0";
    }
    $js = "Loadjs('$page?primary_node-services-js=0&primary_node_id=$primary_node_id');";
    $jsparams = "Loadjs('$page?primary_node-scripts-params-js=0&primary_node_id=$primary_node_id');";
    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $js = "blur()";
        }
    }
    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{monitor_service_explain}<br><b>Failover Checker version $go_failver_checker_ver</b></p>
<div id='primary_node_save'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px;margin-bottom:-20px'>
		
			<label class=\"btn btn btn-primary\" $isDisable OnClick=\"$js\">
				<i class='fa fa-plus'></i> {new_service} </label>
	<label class=\"btn btn btn-success\" $isDisable OnClick=\"$jsparams\">
				<i class='fas fa-cogs'></i> {parameters} </label>
			</div>");

    echo "<div id='primary_node-services'></div>
	<script>
		LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');
	</script>	
	";

}

function primary_node_services_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $primary_node_id = $_GET["primary_node-services-list"];
    $t = time();
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = false;

    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
        }
    }
    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{monitor_service}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>");

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $sql = "SELECT * FROM keepalived_services WHERE primary_node_id='{$primary_node_id}' ORDER BY ID"; //
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error_html(true);
        return;
    }
    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        $text_class = null;


        $url = "Loadjs('$page?primary_node-services-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}');";
        $href = "<a href=\"javascript:blur()\" OnClick=\"javascript:$url\" style='font-weight:bold'>";
        if ($isDisable) {
            $delete = "";
        } else {
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-services-delete-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}&md={$md}&service={$ligne["service"]}')", "AsSystemAdministrator");
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$href{$ligne["service"]}</a></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\"><center>{$delete}</center></td>");
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='2'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    echo @implode("\n", $html);
}

function primary_node_services_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $service_id = intval($_GET["primary_node-services-js"]);
    $service_id_text = "{edit_service}";
    if ($service_id == 0) {
        $service_id_text = "{new_service}";
    }

    $title = "$service_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-service=$service_id&primary_node_id=$primary_node_id");
}

function add_primary_node_service()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $service_id = intval($_GET["add-primary_node-service"]);
    $service_id_text = "{edit_service}";
    if ($service_id == 0) {
        $service_id_text = "{new_service}";
    }

    $title = "$service_id_text";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";

    $title_button = "{add}";
    if ($service_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');";
        $sql = "SELECT * FROM keepalived_services WHERE ID='$service_id' AND primary_node_id='$primary_node_id'";
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $results = $q->QUERY_SQL($sql);
        $ligne = $results[0];
        $title_button = "{apply}";
    } else {
        $ligne["service"] = 'Proxy';
    }
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $primary_node_service_info = new keepalived_services();
    $isDisable = false;

    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }


    $form[] = $tpl->field_hidden("save_primary_node_service", $primary_node_id);
    $form[] = $tpl->field_hidden("service_id", $service_id);
    $form[] = $tpl->field_array_hash($primary_node_service_info->service_array, "service", "{monitor_service}", "{$ligne["service"] }", true, "{monitor_service_explain}", null, false, $isDisable);

    $form[] = '<script>';
    if ($isDisable == true) {
        $form[] = '
         $( document ).ready(function() {      
             $("input").attr("readonly", "readonly");
         });
        ';
    }
    $form[] = '</script>';


    $security = "AsSystemAdministrator";
    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_primary_node_service()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $keepalived_node_services = new keepalived_services($_POST['save_primary_node_service'], $_POST['service_id']);


//    if (intval($keepalived_node_services->service_id) == 0) {
//        $countDups = $keepalived_node_services->duplicates($_POST['service']);
//        if ($countDups > 0) {
//            echo "jserror:The service {$_POST["service"]} is already monitor by another instance";
//            return false;
//        }
//    }


    $keepalived_node_services->primary_node_id = intval($_POST['save_primary_node_service']);
    $keepalived_node_services->service_id = intval($_POST['service_id']);
    $keepalived_node_services->service = $_POST['service'];
    $keepalived_node_services->enable = 1;
    $keepalived_node_services->synckey = microtime(true);

    $keepalived_node_services->save();
}

function delete_services_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $service_id = intval($_GET["primary_node-services-delete-js"]);
    $value = "$primary_node_id|$service_id";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-services','$page?primary_node-services-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";
    $tpl->js_confirm_delete("{$_GET["service"]}", "delete-service", $value, $jsafter);
}

function delete_service()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-service"]);
    $keepalived_node_services = new keepalived_services($f[0], $f[1]);
    $keepalived_node_services->delete();

}
//END SERVICES


//VIRTUAL IPS
function primary_node_virtualip()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-virtualip"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $jsafter = "Loadjs('$page?primary_node-virtualip-js=0&primary_node_id=$primary_node_id')";
    $isDisable = '';

    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $jsafter = "blur()";
        }
    }


    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{keepalived_virtualip}</p>
<div id='primary_node_save'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px;margin-bottom:-20px'>
			
			<label class=\"btn btn btn-primary\" $isDisable OnClick=\"$jsafter\">
				<i class='fa fa-plus'></i> {new_virtualip} </label>

			</div>");

    echo "<div id='primary_node-virtualip'></div>
	<script>
		LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');
	</script>	
	";

}

function primary_node_virtualip_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $primary_node_id = $_GET["primary_node-virtualip-list"];
    $t = time();
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = false;

    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
        }
    }

    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{dev}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{label}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>");

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $sql = "SELECT * FROM keepalived_virtual_interfaces WHERE primary_node_id='{$primary_node_id}' ORDER BY ID"; //
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error_html(true);
        return;
    }

    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        $text_class = null;


        $url = "Loadjs('$page?primary_node-virtualip-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}');";
        $href = "<a href=\"javascript:blur()\" OnClick=\"javascript:$url\" style='font-weight:bold'>";
        if ($isDisable) {
            $delete = "";
        } else {
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-virtualip-delete-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}&md={$md}&virtualip={$ligne["virtual_ip"]}')", "AsSystemAdministrator");
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$href{$ligne["virtual_ip"]}/{$ligne["netmask"]}</a></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["dev"]}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["label"]}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\"><center>{$delete}</center></td>");
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='5'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    echo @implode("\n", $html);
}

function primary_node_virtualip_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $virtualip_id = intval($_GET["primary_node-virtualip-js"]);
    $virtualip_id_text = "{edit_virtualip}";
    if ($virtualip_id == 0) {
        $virtualip_id_text = "{new_virtualip}";
    }

    $title = "$virtualip_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-virtualip=$virtualip_id&primary_node_id=$primary_node_id");
}

function add_primary_node_virtualip()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $virtualip_id = intval($_GET["add-primary_node-virtualip"]);
    $virtualip_id_text = "{edit_virtualip}";
    if ($virtualip_id == 0) {
        $virtualip_id_text = "{new_virtualip}";
    }

    $title = "$virtualip_id_text";


    //$jsafter = "LoadAjax('table-keepalived-services','$page?table=yes');";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";

    $title_button = "{add}";
    if ($virtualip_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');";
        $sql = "SELECT * FROM keepalived_virtual_interfaces WHERE ID='$virtualip_id' AND primary_node_id='$primary_node_id'";
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $results = $q->QUERY_SQL($sql);
        $ligne = $results[0];
        $title_button = "{apply}";
    } else {
        $ligne["virtual_ip"] = null;
        $ligne["interface"] = null;
        $ligne["netmask"] = 24;
        $ligne["label"] = null;
    }


    $ip = new networking();
    $Interfaces[null] = "{not_used}";
    $Interfaces = $ip->Local_interfaces();

    unset($Interfaces["lo"]);
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = false;

    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }

    $form[] = $tpl->field_hidden("save_primary_node_virtualip", $primary_node_id);
    $form[] = $tpl->field_hidden("virtualip_id", $virtualip_id);
    $form[] = $tpl->field_ipaddr("virtualip", "{ipaddr}", $ligne["virtual_ip"], true, null, $isDisable);
    $form[] = $tpl->field_maskcdir('netmask', '{netmask}', "{$ligne["netmask"]}", true, null, $isDisable);
    $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{dev}", "$Interfaces", $explain = null);
    $form[] = $tpl->field_hidden("label", "{$ligne["label"]}");


    $security = "AsSystemAdministrator";
    $html = $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_primary_node_virtualip()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $keepalived_node_virtualip = new keepalived_vips($_POST['save_primary_node_virtualip'], $_POST['virtualip_id']);
//    print_r($keepalived_node_virtualip);
//    die();

    if (empty($_POST['netmask'])) {
        $_POST['netmask'] = 24;
    }

    if (!filter_var($_POST['virtualip'], FILTER_VALIDATE_IP)) {
        echo "jserror:Invalid Virtual IP Address";
        return false;
    }
    if (intval($keepalived_node_virtualip->virtualip_id) == 0) {
        $countDups = $keepalived_node_virtualip->duplicates($_POST['virtualip']);
        if ($countDups > 0) {
            echo "jserror:Virtual ip {$_POST["virtualip"]} already exist";
            return false;
        }
    }

    $keepalived_node_virtualip->primary_node_id = $_POST['save_primary_node_virtualip'];
    $keepalived_node_virtualip->virtual_ip = $_POST['virtualip'];
    $keepalived_node_virtualip->netmask = intval($_POST['netmask']);
    $keepalived_node_virtualip->dev = $_POST['interface'];
    $keepalived_node_virtualip->synckey = microtime(true);
    $keepalived_node_virtualip->save();
}

function delete_virtualip_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $virtualip_id = intval($_GET["primary_node-virtualip-delete-js"]);
    $value = "$primary_node_id|$virtualip_id";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-virtualip','$page?primary_node-virtualip-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";
    $tpl->js_confirm_delete("{$_GET["virtualip"]}", "delete-virtualip", $value, $jsafter);
}

function delete_virtualip()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-virtualip"]);
    $keepalived_node_virtualip = new keepalived_vips($f[0], $f[1]);
    $keepalived_node_virtualip->delete();

}

//END VIRTUAL IPS


//TRACK INTERFACES
function primary_node_trackinterfaces()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-trackinterfaces"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = '';
    $js = "Loadjs('$page?primary_node-trackinterfaces-js=0&primary_node_id=$primary_node_id')";
    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $js = "blur()";
        }
    }

    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{keepalived_trackinterfaces}</p>
<div id='primary_node_save'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px;margin-bottom:-20px'>
			
			<label class=\"btn btn btn-primary\" $isDisable OnClick=\"$js\">
				<i class='fa fa-plus'></i> {new_trackinterfaces} </label>
	
			</div>");

    echo "<div id='primary_node-trackinterfaces'></div>
	<script>
		LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');
	</script>	
	";

}

function primary_node_trackinterfaces_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $primary_node_id = $_GET["primary_node-trackinterfaces-list"];
    $t = time();
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = false;
    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
        }
    }
    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{interface}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{weight}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>");

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $sql = "SELECT * FROM keepalived_track_interfaces WHERE primary_node_id='{$primary_node_id}' ORDER BY ID"; //
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error_html(true);
        return;
    }
    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        $text_class = null;


        $url = "Loadjs('$page?primary_node-trackinterfaces-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}');";
        $href = "<a href=\"javascript:blur()\" OnClick=\"javascript:$url\" style='font-weight:bold'>";
        if ($isDisable) {
            $delete = "";
        } else {
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-trackinterfaces-delete-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}&md={$md}&trackinterfaces={$ligne["interface"]}')", "AsSystemAdministrator");
        }
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">$href{$ligne["interface"]}</a></td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\">{$ligne["weight"]}</td>");
        $html[] = $tpl->_ENGINE_parse_body("<td class=\"$text_class\"><center>{$delete}</center></td>");
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='5'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    echo @implode("\n", $html);
}

function primary_node_trackinterfaces_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $trackinterfaces_id = intval($_GET["primary_node-trackinterfaces-js"]);
    $trackinterfaces_id_text = "{edit_trackinterfaces}";
    if ($trackinterfaces_id == 0) {
        $trackinterfaces_id_text = "{new_trackinterfaces}";
    }

    $title = "$trackinterfaces_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-trackinterfaces=$trackinterfaces_id&primary_node_id=$primary_node_id");
}

function add_primary_node_trackinterfaces()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $trackinterfaces_id = intval($_GET["add-primary_node-trackinterfaces"]);
    $trackinterfaces_id_text = "{edit_trackinterfaces}";
    if ($trackinterfaces_id == 0) {
        $trackinterfaces_id_text = "{new_trackinterfaces}";
    }

    $title = "$trackinterfaces_id_text";


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";
    $title_button = "{add}";
    if ($trackinterfaces_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');";
        $sql = "SELECT * FROM keepalived_track_interfaces WHERE ID='$trackinterfaces_id' AND primary_node_id='$primary_node_id'";
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $results = $q->QUERY_SQL($sql);
        $ligne = $results[0];
        $title_button = "{apply}";
    } else {
        $ligne["interface"] = null;
        $ligne["weight"] = 0;
    }

    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = false;
    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }


    $form[] = $tpl->field_hidden("save_primary_node_trackinterfaces", $primary_node_id);
    $form[] = $tpl->field_hidden("trackinterfaces_id", $trackinterfaces_id);
    if ($isDisable) {
        $form[] = $tpl->field_text('interface', "{local_interface}", "{$ligne["interface"]}", false, null, $isDisable, $isDisable);
        $form[] = $tpl->field_text("weight", "{weight}", "{$ligne["weight"]}", false, null, $isDisable, $isDisable);
    } else {
        $form[] = $tpl->field_interfaces('interface', "nooloopNoDef:{local_interface}", "{$ligne["interface"]}", $explain = null);
        $form[] = $tpl->field_numeric("weight", "{weight}", "{$ligne["weight"]}", false, null);
    }


    $security = "AsSystemAdministrator";
    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_primary_node_trackinterfaces()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $keepalived_node_trackinterfaces = new keepalived_trackinterfaces($_POST['save_primary_node_trackinterfaces'], $_POST['trackinterfaces_id']);
    if (intval($keepalived_node_trackinterfaces->trackinterfaces_id) == 0) {
        $countDups = $keepalived_node_trackinterfaces->duplicates($_POST['interface']);
        if ($countDups > 0) {
            echo "jserror:Interface {$_POST["interface"]} already in use";
            return false;
        }
    }

    $keepalived_node_trackinterfaces->primary_node_id = $_POST['save_primary_node_trackinterfaces'];
    $keepalived_node_trackinterfaces->trackinterfaces_id = $_POST['trackinterfaces_id'];
    $keepalived_node_trackinterfaces->interface = $_POST['interface'];
    $keepalived_node_trackinterfaces->weight = intval($_POST['weight']);
    $keepalived_node_trackinterfaces->synckey = microtime(true);
    $keepalived_node_trackinterfaces->save();
}

function delete_trackinterfaces_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $trackinterfaces_id = intval($_GET["primary_node-trackinterfaces-delete-js"]);
    $value = "$primary_node_id|$trackinterfaces_id";

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('primary_node-trackinterfaces','$page?primary_node-trackinterfaces-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";
    $tpl->js_confirm_delete("{$_GET["trackinterfaces"]}", "delete-trackinterfaces", $value, $jsafter);
}

function delete_trackinterfaces()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-trackinterfaces"]);
    $keepalived_node_trackinterfaces = new keepalived_trackinterfaces($f[0], $f[1]);
    $keepalived_node_trackinterfaces->delete();

}

//END TRACK INTERFACES

//scripts_params
function primary_node_scripts_params()
{
    $page = CurrentPageName();
    $primary_node_id = $_GET["primary_node-scripts_params"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = '';
    $js = "Loadjs('$page?primary_node-scripts_params-js=0&primary_node_id=$primary_node_id');";
    $jsparams = "Loadjs('$page?primary_node-scripts_params-parameters-js=0&primary_node_id=$primary_node_id');";
    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = 'disabled';
            $js = "blur()";
        }
    }
    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{monitor_scripts_params_explain}</p>
<div id='primary_node_save'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px;margin-bottom:-20px'>
		
			<label class=\"btn btn btn-primary\" $isDisable OnClick=\"$js\">
				<i class='fa fa-plus'></i> {new_scripts_params} </label>
	<label class=\"btn btn btn-success\" $isDisable OnClick=\"$jsparams\">
				<i class='fas fa-cogs'></i> {parameters} </label>
			</div>");

    echo "<div id='primary_node-scripts_params'></div>
	<script>
		LoadAjax('primary_node-scripts_params','$page?primary_node-scripts_params-list=$primary_node_id');
	</script>	
	";

}



function primary_node_scripts_params_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $scripts_params_id = intval($_GET["primary_node-scripts_params-js"]);
    $scripts_params_id_text = "{edit_scripts_params}";
    if ($scripts_params_id == 0) {
        $scripts_params_id_text = "{new_scripts_params}";
    }

    $title = "$scripts_params_id_text";
    $tpl->js_dialog2($title, "$page?add-primary_node-scripts_params=$scripts_params_id&primary_node_id=$primary_node_id");
}

function add_primary_node_scripts_params()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $primary_node_id_text = "{edit_scripts_params}";
    if ($primary_node_id == 0) {
        $primary_node_id_text = "{new_scripts_params}";
    }

    $title = "$primary_node_id_text";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_scripts_params} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";

    $title_button = "{add}";
    if ($primary_node_id > 0) {
        $title_button = "{apply}";
    }

    $primary_node_info = new keepalives_primary_nodes($primary_node_id);
    $isDisable = false;

    if ($primary_node_info->isPrimaryNode == 0) {
        if ($primary_node_info->secondaryNodeIsDisconnected == 0) {
            $tpl->KEEPALIVED_CLI = true;
            $isDisable = true;
            $jsafter = "blur()";
        }
    }
    $debugLevel[-1]="trace";
    $debugLevel[0]="debug";
    $debugLevel[1]="info";
    $debugLevel[2]="warn";
    $debugLevel[3]="error";
    $debugLevel[4]="fatal";
    $debugLevel[5]="panic";

    $cpu_num=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if ($cpu_num>$primary_node_info->loadlimit){
        $primary_node_info->loadlimit=$cpu_num;
    }

    $form[] = $tpl->field_hidden("save_primary_node_scripts_params",$primary_node_info->primary_node_id);
    $form[] = $tpl->field_numeric("interval", '{interval}', "{$primary_node_info->interval}", '{interval_explain}');
    $form[] = $tpl->field_numeric("fall", '{fall}', "{$primary_node_info->fall}", '{fall_explain}');
    $form[] = $tpl->field_numeric("rise", '{rise}', "{$primary_node_info->rise}", '{rise_explain}');
    $form[] = $tpl->field_numeric("weight", '{weight}', "{$primary_node_info->weight}", '{weight_explain}');
    $form[] = $tpl->field_numeric("timeout", '{timeout}', "{$primary_node_info->timeout}", '{timeout_explain}');
    $form[] = $tpl->field_text("testDom", '{test} {domain} Proxy', "{$primary_node_info->testDom}", true);
    $form[] = $tpl->field_text("testDomDNS", '{test} {domain} DNS', "{$primary_node_info->testDomDNS}", true);
    $form[] = $tpl->field_checkbox("enableProxyCurl","{enable} curl {test} (Proxy)","{$primary_node_info->enableProxyCurl}",'proxytesttimeout');
    $form[] = $tpl->field_checkbox("enableDnsResolver","{enable} DNS {test}","{$primary_node_info->enableDnsResolver}",'dnstesttimeout');
    $form[] = $tpl->field_numeric("proxytesttimeout", 'Curl {test} {timeout}', "{$primary_node_info->proxytesttimeout}");
    $form[] = $tpl->field_numeric("dnstesttimeout", 'DNS {test} {timeout}', "{$primary_node_info->dnstesttimeout}");
    $form[] = $tpl->field_checkbox("checkLoad","{enable} {server} {load} {test}","{$primary_node_info->checkLoad}",'loadlimit');
    $form[] = $tpl->field_numeric("loadlimit", '{server} {load} {limit}', "{$primary_node_info->loadlimit}");
    $form[] = $tpl->field_checkbox("checkRam","{enable} {memory_usage} {test}","{$primary_node_info->checkRam}",'ramlimit');
    $form[] = $tpl->field_numeric("ramlimit", '{memory_usage} {limit}', "{$primary_node_info->ramlimit}");
    $form[] = $tpl->field_checkbox("checkDisk","{enable} {disk_usage} {test}","{$primary_node_info->checkDisk}",'disklimit');
    $form[] = $tpl->field_numeric("disklimit", '{disk_usage} {limit}', "{$primary_node_info->disklimit}");
    $form[] = $tpl->field_array_hash($debugLevel, "checkDebugLevel", "{debug_level}", $primary_node_info->checkDebugLevel);





    $form[] = '<script>';
    if ($isDisable == true) {
        $form[] = '
         $( document ).ready(function() {      
             $("input").attr("readonly", "readonly");
         });
        ';
    }
    $form[] = '</script>';


    $security = "AsSystemAdministrator";
    $html = "<div id='primary_node_save'></div>" . $tpl->form_outside($title, $form, null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_primary_node_scripts_params()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $testDom=trim($_POST["testDom"]);
    if ($testDom==null){
        $testDom="http://articatech.com";
    }

    $testDomDNS=trim($_POST["testDomDNS"]);
    if ($testDomDNS==null){
        $testDomDNS="cloudflare.com";
    }


    $primary_node_info = new keepalives_primary_nodes(intval($_POST["save_primary_node_scripts_params"]));

    $primary_node_info->primary_node_id = intval($_POST['save_primary_node_scripts_params']);
    $primary_node_info->interval = intval($_POST['interval']);
    $primary_node_info->fall = intval($_POST['fall']);
    $primary_node_info->rise = intval($_POST['rise']);
    $primary_node_info->weight = intval($_POST['weight']);
    $primary_node_info->timeout=intval($_POST['timeout']);
    $primary_node_info->testDom=$testDom;
    $primary_node_info->testDomDNS=$testDomDNS;
    $primary_node_info->enableProxyCurl=intval($_POST["enableProxyCurl"]);

    $cpu_num=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if ($cpu_num>intval($_POST["loadlimit"])){
        $_POST["loadlimit"]->loadlimit=$cpu_num;
    }

    $primary_node_info->disklimit = intval($_POST["disklimit"]);
    $primary_node_info->ramlimit = intval($_POST["ramlimit"]);
    $primary_node_info->loadlimit = intval($_POST["loadlimit"]);
    $primary_node_info->checkDisk = intval($_POST["checkDisk"]);
    $primary_node_info->checkRam = intval($_POST["checkRam"]);
    $primary_node_info->checkLoad = intval($_POST["checkLoad"]);
    $primary_node_info->dnstesttimeout = intval($_POST["dnstesttimeout"]);
    $primary_node_info->proxytesttimeout = intval($_POST["proxytesttimeout"]);
    $primary_node_info->checkDebugLevel = intval($_POST["checkDebugLevel"]);
    $primary_node_info->enableDnsResolver = intval($_POST["enableDnsResolver"]);



    $primary_node_info->save();
}


//END scripts_params

//SLAVES
function secondary_node()
{
    $primary_node_id = $_GET["secondary_node"];
    $tpl = new template_admin();
    $page = CurrentPageName();
    $jsafter = "Loadjs('$page?secondary_node-js=0&primary_node_id=$primary_node_id')";

    echo $tpl->_ENGINE_parse_body("
<p style=\"margin-top: 10px\">{nodes_explain}</p>
<div id='primary_node_save'></div>
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px;margin-bottom:-20px'>
			
			<label class=\"btn btn btn-primary\"  OnClick=\"$jsafter\">
				<i class='fa fa-plus'></i> {new_secondary_node} </label>
	
			</div>");

    echo "<div id='secondary_node'></div>
	<script>
		LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');
	</script>	
	";

}

function secondary_node_list()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $primary_node_id = $_GET["secondary_node-list"];
    $t = time();


    $html[] = $tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[] = "<thead>";
    $html[] = "<tr>";
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>");

    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize' data-type='text'>{priority}</th>");
    $html[] = $tpl->_ENGINE_parse_body("<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>");

    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";


    $sql = "SELECT * FROM keepalived_secondary_nodes WHERE primary_node_id='{$primary_node_id}' ORDER BY priority DESC"; //
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error_html(true);
        return;
    }
    $TRCLASS = null;
    foreach ($results as $index => $ligne) {
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        $text_class = null;
        $secondary_node_name = $ligne["secondary_node_ip"];
        if (!empty($ligne["hostname"])) {
            $secondary_node_name = $ligne["hostname"] . " (" . $ligne["secondary_node_ip"] . ")";
        }

        $status = intval($ligne["status"]);

        $ligne_class = "text-danger";
        $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$ligne['errortext']}</span></button>";
        if ($status == 0) {
            $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #f8ac59 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-warning\">{waiting_registration}</span></button>";
            $ligne_class = "text-warning";
        }

        if ($status == 1) {
            $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$ligne["errortext"]}</span></button>";
            $ligne_class = "text-danger";
        }
        if ($status == 2) {
            $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #f8ac59 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-warning\">{waiting_confirmation}</span></button>";
            $ligne_class = "text-warning";
        }

        if ($status == 120) {
            $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #f8ac59 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-warning\">{$ligne["errortext"]}</span></button>";
            $ligne_class = "text-warning";
        }

        if ($status == 110) {

            $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$ligne["errortext"]}</span></button>";
            $ligne_class = "text-danger";
        }

        if ($status == 3) {

            $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$ligne["errortext"]}</span></button>";
            $ligne_class = "text-danger";
        }

        if ($status == 5) {
            $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #23c6c8 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-info\">Ping OK</span></button>";

        }

        if ($status ==100) {
            $ligne_class = "text-danger";
            //$label_secondary_node = "<div style='text-transform: uppercase;' class=\"label label-danger\">{$ligne['service_state']}</div>";
            $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$ligne['service_state']}</span></button>";
            if ($ligne['service_state'] == "MASTER" || $ligne['service_state'] == "UP") {
                $ligne_class = "text-success";
                $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #1c84c6 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-success\">{$ligne['service_state']}</span></button>";
            }
            if ($ligne['service_state'] == "BACKUP") {
                $ligne_class = "text-info";
                $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #23c6c8  !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-info\">{$ligne['service_state']}</span></button>";
            }
        }




        //$secondary_nodelink = $tpl->td_href($secondary_node_name, "", "Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}')");

        $delete = $tpl->icon_delete("Loadjs('$page?secondary_node-delete-js={$ligne["ID"]}&primary_node_id={$ligne["primary_node_id"]}&md={$md}&secondary_node-ip={$ligne["secondary_node_ip"]}')", "AsSystemAdministrator");

        $html[] = "<tr class='$TRCLASS ' id='$md'>";
        //$html[] = $tpl->_ENGINE_parse_body("<td style='width:1%' nowrap class=\"$secondary_node_class\">$label_secondary_node</td>");
        $html[] = "<td >$label_secondary_node</a></td>";

        $html[] = "<td style='width:1%' nowrap>{$ligne["priority"]}</td>";
        $html[] = "<td style='width:1%' nowrap><center>{$delete}</center></td>";
        $html[] = "</tr>";
    }

    $html[] = "</tbody>";
    $html[] = "<tfoot>";

    $html[] = "<tr>";
    $html[] = "<td colspan='3'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
		<script>
		NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
		$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
		</script>";

    echo @implode("\n", $html);
}

function secondary_node_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = $_GET["primary_node_id"];
    $secondary_node_id = intval($_GET["secondary_node-js"]);
    $secondary_node_id_text = "{edit_secondary_node}";
    if ($secondary_node_id == 0) {
        $secondary_node_id_text = "{new_secondary_node}";
    }

    $title = "$secondary_node_id_text";
    $tpl->js_dialog2($title, "$page?add-secondary_node=$secondary_node_id&primary_node_id=$primary_node_id");
}

function add_secondary_node()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $secondary_node_id = intval($_GET["add-secondary_node"]);

    if ($secondary_node_id == 0) {
        $secondary_node_id_text = "{new_secondary_node}";
    }


    $isDisable = false;

    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";
    $title_button = "{add}";
    if ($secondary_node_id > 0) {
        //$jsafter = "dialogInstance2.close();LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');";
        $sql = "SELECT * FROM keepalived_secondary_nodes WHERE ID='$secondary_node_id' AND primary_node_id='$primary_node_id'";
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $results = $q->QUERY_SQL($sql);
        $ligne = $results[0];
        $secondary_node_name = $ligne["secondary_node_ip"];
        if (!empty($ligne["hostname"])) {
            $secondary_node_name = $ligne["hostname"] . " (" . $ligne["secondary_node_ip"] . ")";
        }
        $secondary_node_id_text = "{edit_secondary_node}: $secondary_node_name";
        $title_button = "{apply}";
        $isDisable = true;
    } else {
        $ligne["secondary_node_port"] = 9000;
        $ligne["priority"] = 100;
        $ligne["allow_secondary_node_overwrite"] = 0;
        $ligne["nopreempt"] = 0;
    }
    $title = "$secondary_node_id_text";

    $form[] = $tpl->field_hidden("save_secondary_node", $primary_node_id);
    $form[] = $tpl->field_hidden("secondary_node_id", $secondary_node_id);
    if ($isDisable) {
        $form[] = $tpl->field_text("secondary_node_ip", "{ipaddr}", $ligne["secondary_node_ip"], true, null, $isDisable, $isDisable);
    } else {
        $form[] = $tpl->field_ipv4("secondary_node_ip", "{ipaddr}", $ligne["secondary_node_ip"], true);

    }
    $form[] = $tpl->field_numeric("secondary_node_port", "{artica_listen_port}", $ligne["secondary_node_port"]);
    $form[] = $tpl->field_numeric("priority", "{priority}", $ligne["priority"], '{priority_explain}');
    $form[] = $tpl->field_checkbox("nopreempt", "{nopreempt_1}", $ligne["nopreempt"], false, "{nopreempt}");

    //$form[] = $tpl->field_section("DANGER ZONE", "If you disconnect the Slave from farm, means that any changes made on Master will not be replicated to the Slave.", true);
    //$form[] = $tpl->field_checkbox("allow_secondary_node_overwrite", "{disonnect_from_farm}", $ligne["secondary_node_can_overwrite_settings"], false, "{allow_overwrite_explain}");


    $security = "AsSystemAdministrator";
    $html = $tpl->form_outside($title, @implode("\n", $form), null, $title_button, $jsafter, $security);
    echo $tpl->_ENGINE_parse_body($html);
}

function save_secondary_node()
{
    $tpl = new template_admin();
    $unix = new unix();
    $tpl->CLEAN_POST();
    $keepalived_secondary_node = new keepalived_secondary_nodes($_POST["save_secondary_node"], $_POST["secondary_node_id"]);
    $keepalived_primary_node = new keepalives_primary_nodes($_POST["save_secondary_node"]);
    if (intval($keepalived_secondary_node->secondary_node_id) == 0) {
        if (!filter_var($_POST['secondary_node_ip'], FILTER_VALIDATE_IP)) {
            echo "jserror:Invalid Slave IP Address";
            return false;
        }

        $countDups = $keepalived_secondary_node->duplicates($_POST['secondary_node_ip']);
        if ($countDups > 0) {
            echo "jserror:A secondary_node with ip {$_POST["secondary_node_ip"]} already exist";
            return false;
        }

        $countDupsPriority = $keepalived_secondary_node->duplicatesPriority($_POST["save_secondary_node"], $_POST['priority']);
        if ($countDupsPriority > 0) {
            echo "jserror:A secondary_node with priority {$_POST["priority"]} already exist";
            return false;
        }


    }

    if (intval($_POST['priority']) >= $keepalived_primary_node->priority) {
        echo "jserror:The priority of secondary_node should be less than the priority of master {$_POST['priority']} $keepalived_primary_node->priority $keepalived_primary_node->primary_node_id";
        return false;
    }


    $keepalived_secondary_node->secondary_node_port = intval($_POST['secondary_node_port']);
    $keepalived_secondary_node->primary_node_id = $keepalived_primary_node->primary_node_id;
    $keepalived_secondary_node->secondary_node_ip = $_POST["secondary_node_ip"];
    $keepalived_secondary_node->primary_node_ip = $unix->InterfaceToIPv4($keepalived_primary_node->interface);
    $keepalived_secondary_node->synckey = microtime(true);
    $keepalived_secondary_node->priority = intval($_POST['priority']);
    $keepalived_secondary_node->secondary_node_can_overwrite_settings = 0;
    $keepalived_secondary_node->nopreempt = intval($_POST['nopreempt']);
    $keepalived_secondary_node->save();


}

function delete_secondary_node_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $primary_node_id = intval($_GET["primary_node_id"]);
    $secondary_node_id = intval($_GET["secondary_node-delete-js"]);
    $value = "$primary_node_id|$secondary_node_id";
    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";
    $ARRAY["AFTER"] = "LoadAjax('secondary_node','$page?secondary_node-list=$primary_node_id');LoadAjax('table-keepalived-services','$page?table=yes')";
    $prgress = base64_encode(serialize($ARRAY));
    $jsafter = "dialogInstance2.close();Loadjs('fw.progress.php?content=$prgress&mainid=primary_node_save')";
    $tpl->js_confirm_delete("Remove Slave {$_GET["secondary_node-ip"]}", "delete-secondary_node", $value, $jsafter);
}

function delete_secondary_node()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $f = explode("|", $_POST["delete-secondary_node"]);
    $keepalived_secondary_node = new keepalived_secondary_nodes($f[0], $f[1]);
    $keepalived_secondary_node->delete();

}

//END SLAVES

//TABLE
function table()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");


    $ARRAY["PROGRESS_FILE"] = PROGRESS_DIR . "/keepalived.progress";
    $ARRAY["LOG_FILE"] = PROGRESS_DIR . "/keepalived.log";
    $ARRAY["CMD"] = "keepalived.php?reconfigure=yes";
    $ARRAY["TITLE"] = "{reconfigure_service} {APP_KEEPALIVED}";

    $prgress = base64_encode(serialize($ARRAY));
    $jsrestart = "Loadjs('fw.progress.php?content=$prgress&mainid=progress-keepalived-restart')";
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?primary_node-js=');\">";
    $html[] = "<i class='fa fa-plus'></i> {new_primary_node} </label>$btn";

    $html[] = "</div>";
    $html[] = "<table id='table-keepalived-instances' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[] = "<tr>";

    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{primary_node_name}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{health_checks}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{floating_ip}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{secondary_nodes}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{active2}</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[] = "<th data-sortable=false class='text-capitalize' data-type='text'>{statistics}</th>";
    $html[] = "</tr>";
    $html[] = "</thead>";
    $html[] = "<tbody>";

    $sql = "SELECT * FROM `keepalived_primary_nodes`";
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error_html();
    }

    $TRCLASS = null;
    $ligne = null;
//    $GET_UNICAST = $q->mysqli_fetch_array("SELECT group_concat(secondary_node_ip) as secondary_node_ip FROM keepalived_secondary_nodes");
//    //array_push($GET_UNICAST,'100.100.10.10');
//    $GET_UNICAST['secondary_node_ip']=$GET_UNICAST['secondary_node_ip'].',100.100.10.10';
//    echo serialize($GET_UNICAST);
    foreach ($results as $index => $ligne) {
        $count_rowspwan = $q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM `keepalived_secondary_nodes` WHERE primary_node_id='{$ligne["ID"]}'");
        $rowspwan = $count_rowspwan['tcount'] +1;

        $isDisable = false;

        if ($ligne['isPrimaryNode'] == 0) {
            if ($ligne['secondaryNodeIsDisconnected'] == 0) {
                $tpl->KEEPALIVED_CLI = true;
                $isDisable = true;
                $jsafter[] = "blur()";
            }
        }
        if ($TRCLASS == "footable-odd") {
            $TRCLASS = "footable-even";
        } else {
            $TRCLASS = "footable-odd";
        }
        $md = md5(serialize($ligne));
        if ($ligne["enabled"] == 0) {
            $color = "#8a8a8a";
        }
        $primary_node_id = $ligne["ID"];
        $primary_node_name = $ligne['primary_node_name'];
        //$primary_node_name = $tpl->td_href("$primary_node_name", "{$ligne["id"]}", "Loadjs('$page?primary_node-js=$primary_node_id&title={$ligne['primary_node_name']}')");

        //$label = "<div style='text-transform: uppercase;' class=\"label label-danger\">$primary_node_name {$ligne['service_state']}</a></div>";
        $label ="<button class='btn btn-light animated infinite pulse' style='padding: 0px 10px 0px 0px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?primary_node-js=$primary_node_id&title={$ligne['primary_node_name']}')\"><span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$ligne['service_state']}</span> <strong>$primary_node_name</strong></button>";
        $class = "text-danger";
        $class_vip="text-danger";
        if ($ligne['service_state'] == "MASTER" || $ligne['service_state'] == "UP") {
            //$label = "<div style='text-transform: uppercase; border: 1px solid green' >$primary_node_name <span class=\"label label-primary\">{$ligne['service_state']}</span><span class=\"clear\"></span></div>";
            $class = "text-success";
            $label ="<button class='btn btn-light' style='padding: 0px 10px 0px 0px !important; border:1px solid #1c84c6 !important;' onclick=\"Loadjs('$page?primary_node-js=$primary_node_id&title={$ligne['primary_node_name']}')\"><span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-success\">{$ligne['service_state']}</span> <strong>$primary_node_name</strong></button>";
            $class_vip="success";
        }
        if ($ligne['service_state'] == "BACKUP") {
            //$label = "<div style='text-transform: uppercase;' class=\"label label-success\"><strong>$primary_node_name</strong> {$ligne['service_state']}</a></div>";
            $label ="<button class='btn btn-light' style='padding: 0px 10px 0px 0px !important; border:1px solid #23c6c8 !important;' onclick=\"Loadjs('$page?primary_node-js=$primary_node_id&title={$ligne['primary_node_name']}')\"><span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-info\">{$ligne['service_state']}</span> <strong>$primary_node_name</strong></button>";
            $class = "text-info";

        }



        if ($isDisable) {
            $disable = (intval($ligne["enable"]) == 0) ? "<div style='text-transform: uppercase;' class=\"label label-warning\">{disabled}</div>" : "<div style='text-transform: uppercase;' class=\"label label-info\">{enable}</div>";
            $delete = "";
        } else {
            $disable = $tpl->icon_check($ligne["enable"], "Loadjs('$page?primary_node-enable-js=$primary_node_id')", null, "AsSquidAdministrator");
            $delete = $tpl->icon_delete("Loadjs('$page?primary_node-delete-js=$primary_node_id&primary_node-name={$ligne['primary_node_name']}&md=$md')", "AsSquidAdministrator");
        }


        //GET SERVICES
        $services = $q->mysqli_fetch_array("SELECT group_concat(service) as services FROM `keepalived_services` WHERE primary_node_id='{$ligne["ID"]}'");
        $services=str_replace(',', '<br/>', $services["services"]);

        $nodes="";
        //GET NODES
        $sql = "SELECT * FROM `keepalived_secondary_nodes` WHERE primary_node_id='{$ligne["ID"]}' ORDER BY priority DESC";
        $secondary_nodes = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }
        $count=count($secondary_nodes);
        foreach ($secondary_nodes as $index => $secondary_node) {
            $md = md5(serialize($secondary_node));
            $secondary_node_name = $secondary_node["secondary_node_ip"];
            if (!empty($secondary_node["hostname"])) {
                $secondary_node_name = $secondary_node["hostname"];
            }
            //$secondary_nodelink = $tpl->td_href($secondary_node_name, "", "Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')");

            $status = intval($secondary_node["status"]);
            $secondary_node_class = "text-info";
            $label_secondary_node = "";
            if ($status == 0) {
                //$label_secondary_node = "<div class='label label-info' style='text-transform: uppercase;'>{waiting_registration}</div>";
                $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #f8ac59 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-warning\">{waiting_registration}</span></button>";
                $secondary_node_class = "text-warning";
            }

            if ($status == 1) {
                //$label_secondary_node = "<div class='label label-danger' style='text-transform: uppercase;'>{$secondary_node["errortext"]}</div>";
                $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$secondary_node["errortext"]}</span></button>";
                $secondary_node_class = "text-danger";
            }
            if ($status == 2) {
                //$label_secondary_node = "<div class='label label-warning' style='text-transform: uppercase;'>{waiting_confirmation}</div>";
                $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #f8ac59 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-warning\">{waiting_confirmation}</span></button>";
                $secondary_node_class = "text-warning";
            }

            if ($status == 3) {
                //$label_secondary_node = "<div class='label label-danger' style='text-transform: uppercase;'>{$secondary_node["errortext"]}</div>";
                $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$secondary_node["errortext"]}</span></button>";
                $secondary_node_class = "text-danger";
            }

            if ($status == 5) {
                //$label_secondary_node = "<div class='label label-primary' style='text-transform: uppercase;'>PING OK</div>";
                $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #23c6c8 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-info\">Ping OK</span></button>";
            }

            if ($status == 120) {
                $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #f8ac59 !important;' onclick=\"Loadjs('$page?secondary_node-js={$ligne["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-warning\">{$secondary_node["errortext"]}</span></button>";
                $ligne_class = "text-warning";
            }

            if ($status == 110) {

                $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$secondary_node["errortext"]}</span></button>";
                $ligne_class = "text-danger";
            }

            if ($status == 100) {
                $secondary_node_class = "text-danger";
                //$label_secondary_node = "<div style='text-transform: uppercase;' class=\"label label-danger\">{$secondary_node['service_state']}</div>";
                $label_secondary_node ="<button class='btn btn-light animated infinite pulse' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #ed5565 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-danger\">{$secondary_node['service_state']}</span></button>";
                if ($secondary_node['service_state'] == "MASTER" || $secondary_node['service_state'] == "UP") {
                    $secondary_node_class = "text-success";
                    //$label_secondary_node = "<div style='text-transform: uppercase;' class=\"label label-info\">{$secondary_node['service_state']}</div>";
                    $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #1c84c6 !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-success\">{$secondary_node['service_state']}</span></button>";
                    $class_vip="success";
                }
                if ($secondary_node['service_state'] == "BACKUP") {
                    $secondary_node_class = "text-info";
                    //$label_secondary_node = "<div style='text-transform: uppercase;' class=\"label label-success\">{$secondary_node['service_state']}</div>";
                    $label_secondary_node ="<button class='btn btn-light' style=' display: block !important;padding: 0px 0px 0px 10px !important; border:1px solid #23c6c8  !important;' onclick=\"Loadjs('$page?secondary_node-js={$secondary_node["ID"]}&primary_node_id={$secondary_node["primary_node_id"]}')\"><strong>$secondary_node_name</strong> <span style='text-transform: uppercase;padding: 3.3px 10px!important; border-radius: 0px !important;top: -1.3px !important;' class=\"label label-info\">{$secondary_node['service_state']}</span></button>";

                }

            }
            $br="";
            if($count>1){
                $br="<br/>";
            }
            $nodes.=$label_secondary_node.$br;

        }

        $stats = $tpl->icon_stats("Loadjs('$page?primary_node-stats-js=$primary_node_id&primary_node-name={$ligne['primary_node_name']}&md=$md')", "AsSquidAdministrator", ($ligne['enable'] == 1) ? false : true);
        $interface=$q->mysqli_fetch_array("SELECT * from keepalived_virtual_interfaces where primary_node_id='$primary_node_id'");
        $html[] = "<tr class='$TRCLASS' id='$md'>";
        $html[] = "<td class='$class'>$label</td>";
        $html[] = "<td><strong>$services</strong></td>";
        $html[] = "<td class='$class_vip'><strong>{$interface['virtual_ip']} ({$interface['label']})</strong></td>";
        $html[] = "<td>$nodes</td>";
        $html[] = "<td style='width:1%' nowrap>$disable</td>";
        $html[] = "<td style='width:1%' nowrap>$delete</td>";
        $html[] = "<td style='width:1%' nowrap>$stats</td>";
        $html[] = "</tr>";


    }
    $html[] = "</tbody>";
    $html[] = "<tfoot>";
    $html[] = "<tr>";
    $html[] = "<td colspan='7'>";
    $html[] = "<ul class='pagination pull-right'></ul>";
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</tfoot>";
    $html[] = "</table>";
    $html[] = "
	<script>
	NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "
	$(document).ready(function() { $('#table-keepalived-instances').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}