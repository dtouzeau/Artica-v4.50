<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
$sock=new sockets();
$tpl=new template_admin();
$users=new usersMenus();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["dhclient_interface"])){tests();exit;}
if(isset($_GET["results"])){results_js();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog6("{dhcp_simulation}", "$page?popup=yes",500);
}
function results_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog7("{dhcp_simulation} {results}", "$page?results-popup=yes",880);	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	if(!isset($_SESSION["dhclient_server"])){$_SESSION["dhclient_server"]=null;}
	if($_SESSION["dhclient_server"]==null){$_SESSION["dhclient_server"]="255.255.255.255";}
	if($_SESSION["dhclient_interface"]==null){$_SESSION["dhclient_interface"]="no default";}

    $form[]=$tpl->field_interfaces("dhclient_interface", "{interface}", $_SESSION["dhclient_interface"]);
	$form[]=$tpl->field_ipaddr("dhclient_server", "{dhcp_server}", $_SESSION["dhclient_server"]);
	$html=$tpl->form_outside("{dhcp_simulation}", $form,"{dhcp_simulation_explain}","{run}","Loadjs('$page?results=yes')");
	echo $html;
	
	//MSFT 5.0
	
}

function generateRandomMacAddress() {
    // Generate random hex pairs for MAC address
    $macAddress = [];
    for ($i = 0; $i < 6; $i++) {
        $macAddress[] = sprintf('%02X', mt_rand(0, 255));
    }

    // Ensure the MAC address is a locally administered address (LAA)
    // The second least significant bit of the first octet should be 1
    $macAddress[0] = sprintf('%02X', hexdec($macAddress[0]) | 2);

    return implode(':', $macAddress);
}

function tests(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$tpl->SESSION_POST();
	$sock=new sockets();
	if($_POST["dhclient_mac"]==null){$_POST["dhclient_mac"]=generateRandomMacAddress();}

	$IP=new IP;
	if(!$IP->IsvalidMAC($_POST["dhclient_mac"])){
	    echo "jserror:Invalid MAC";
	    return;
    }


    $_POST["dhclient_interface"]=$tpl->CLEAN_BAD_CHARSNET($_POST["dhclient_interface"]);
    $_POST["dhclient_server"]=$tpl->CLEAN_BAD_CHARSNET($_POST["dhclient_server"]);
	if(trim($_POST["dhclient_interface"])==null){$_POST["dhclient_interface"]="eth0";}

	$json=json_decode($sock->REST_API_POST("/system/dhcpclient",$_POST));
    file_put_contents(PROGRESS_DIR."/dhtest.results",json_encode($json));
}
function results_popup(){
	$tpl=new template_admin();
    $content=@file_get_contents(PROGRESS_DIR."/dhtest.results");
	$data=json_decode($content);

    if(!$data->Status){
        echo $tpl->div_error($data->Error);
        return true;
    }

    $tpl->table_form_field_text("{ipaddr}",$data->Info->IPAddress,ico_computer);
    $tpl->table_form_field_text("{type}",$data->Info->MsgType,ico_proto);
    $tpl->table_form_field_text("{server}",$data->Info->DHCPServerIP,ico_server);
    $tpl->table_form_field_text("DNS",$data->Info->DNS,ico_database);
    $tpl->table_form_field_text("{gateway}",$data->Info->Gateway,ico_sensor);
    $tpl->table_form_field_text("{netmask}",$data->Info->SubnetMask,ico_networks);
    $tpl->table_form_field_text("{time}",$data->Info->LeaseTime,ico_timeout);

    echo $tpl->table_form_compile();
}
