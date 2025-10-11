<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["nic-settings"])){nic_settings();exit;}
if(isset($_POST["FireHoleStoreEvents"])){logs_settings_save();exit;}
if(isset($_GET["main"])){main();exit;}

page();



function nic_settings(){
	$nic=new system_nic($_POST["nic-settings"]);
	$nic->firewall_policy=$_POST["firewall_policy"];
	$nic->firewall_behavior=$_POST["firewall_behavior"];
	$nic->firewall_masquerade=$_POST["firewall_masquerade"];
	$nic->firewall_artica=$_POST["firewall_artica"];
	//$nic->DenyCountries=$_POST["DenyCountries"];
	$nic->SaveNic();
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{firewall_configuration_file}","fa fa-align-justify","{firewall_configuration_explain}","$page?main=yes","firewall-config",
        "progress-firehol-restart",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker {perimeters}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}



function main(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$id=md5(time());
	$value=@file_get_contents("/usr/sbin/firewall-builder.sh");
    $sock=new sockets();
    $data=$sock->REST_API("/firewall/export");
    $json=json_decode($data);
    $info=nl2br($json->Info);
    $html[]="<H1>{export}</H1>";
    $html[]="<div style='width:100%;min-height:800px !important'>";
    $html[]="<div style='font-family: Courier New,Courier,serif;font-size: 13px;font-weight: bolder;margin-left: 20px;'>";
    $html[]=$info;
    $html[]="</div>";
    $html[]="</div>";

	$html[]="<script>";

	$html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
						
}