<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["FreeRadiusListenPort"])){settings_save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["start"])){start();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_VERSION");
    $html=$tpl->page_header("{APP_FREERADIUS} v$version",
    "fa-solid fa-users-rays",
    "{activate_freeradius_explain}",
    "$page?start=yes","radius-status",
    "progress-freeradius-restart",false,"div-loader-freeradius");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_FREERADIUS} v$version",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);


}
function start(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html="<table style='width:100%'>
    <tr>
					<td style='width:220px;vertical-align:top'><div id='freeradius-service'></div></td>
					<td style='width:78%'><div id='table-loader-freeradius-service'></div></td>
				</tr>
			</table>
	<script>
    LoadAjax('table-loader-freeradius-service','$page?table=yes');
	LoadAjax('freeradius-service','$page?status=yes');
	</script>";

    echo $tpl->_ENGINE_parse_body($html);

}


function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
	$FreeRadiusListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenPort"));
	$FreeRadiusListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenInterface"));
	$freeradiusCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("freeradiusCertificate"));
	$FreeRadiusMaxClients=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusMaxClients"));
    $FreeRadiusDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusDebug"));

	if($FreeRadiusListenPort==0){$FreeRadiusListenPort=1812;}
	if($FreeRadiusMaxClients==0){$FreeRadiusMaxClients=150;}
	
	$security="AsSystemAdministrator";
	$form[]=$tpl->field_interfaces("listen_nic","{listen_interface}",$FreeRadiusListenInterface,false,null);
	$form[]=$tpl->field_numeric("FreeRadiusListenPort","{listen_port} (UDP)",$FreeRadiusListenPort,"");
    $form[]=$tpl->field_checkbox("FreeRadiusDebug","{debug}",$FreeRadiusDebug);
	$form[]=$tpl->field_numeric("FreeRadiusMaxClients","{MaxClients}",$FreeRadiusMaxClients,"");
	$form[]=$tpl->field_certificate("freeradiusCertificate", "{certificate}",$freeradiusCertificate);

    $jsrestart=$tpl->framework_buildjs("freeradius.php?restart=yes",
        "freeradius.restart.progress",
        "freeradius.restart.log",
        "progress-freeradius-restart","LoadAjax('freeradius-service','$page?status=yes');");

	
	$tpl->form_add_button("{test_auth}", "Loadjs('fw.freeradius.testauth.php')");
	
	$html[]=$tpl->form_outside("{general_settings}", @implode("\n", $form),null,"{apply}",$jsrestart,$security);
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function settings_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$tpl->SAVE_POSTs();
	

}


function status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();
	$sock->getFrameWork("freeradius.php?status=yes");
	$ini=new Bs_IniHandler('/usr/share/artica-postfix/ressources/logs/web/freeradius.status');

    $jsrestart=$tpl->framework_buildjs("freeradius.php?restart=yes",
        "freeradius.restart.progress",
        "freeradius.restart.log",
        "progress-freeradius-restart","LoadAjax('freeradius-service','$page?status=yes');");

	echo $tpl->SERVICE_STATUS($ini, "APP_FREERADIUS",$jsrestart);
	
	
}

