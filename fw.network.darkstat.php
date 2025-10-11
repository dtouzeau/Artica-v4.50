<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["DarkStatInterface"])){Save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_DARKSTAT}</h1>
	<p>{APP_DARKSTAT_EXPLAIN}</p>
	</div>

	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-darkstat'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/darkstat');	
	LoadAjax('table-loader-darkstat','$page?table=yes');

	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DARKSTAT} ",$html);
        echo $tpl->build_firewall();
        return;
    }


	echo $tpl->_ENGINE_parse_body($html);

}
function table(){

	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	
	$DarkStatInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatInterface");
	$DarkStatWebInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatWebInterface");
	$DarkStatWebPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatWebPort"));
	
	
	$DarkStatHome=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatHome");
	$DarkStatNetwork=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatNetwork"));
	if($DarkStatNetwork==null){$DarkStatNetwork="192.168.0.0/255.255.0.0";}
	if(!preg_match("#([0-9\.]+)\/([0-9\.]+)#", $DarkStatNetwork)){$DarkStatNetwork="192.168.0.0/255.255.0.0";}
	
	if($DarkStatWebInterface==null){$DarkStatWebInterface="eth0";}
	if($DarkStatWebPort==0){$DarkStatWebPort="663";}
	if($DarkStatInterface==null){$DarkStatInterface="eth0";}
	if($DarkStatHome==null){$DarkStatHome="/home/artica/darkstat";}
	
	$jsafter="LoadAjaxSilent('top-barr','fw-top-bar.php');";
	
	
	$form[]=$tpl->field_interfaces("DarkStatInterface", "{listen_interface}", $DarkStatInterface);
	$form[]=$tpl->field_text("DarkStatNetwork", "{local_network}", $DarkStatNetwork,true,"{DarkStatNetwork}");
	$form[]=$tpl->field_browse_directory("DarkStatHome", "{working_directory}", $DarkStatHome);
	$form[]=$tpl->field_section("{http_engine}");
	$form[]=$tpl->field_interfaces("DarkStatWebInterface", "{listen_interface}", $DarkStatInterface);
	$form[]=$tpl->field_numeric("DarkStatWebPort","{listen_port}",$DarkStatWebPort);
	$html=$tpl->form_outside("{main_parameters}", @implode("\n", $form),null,"{apply}",$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	
	
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, url_decode_special_tool($value));
	}
	
	$sock->getFrameWork("darkstat.php?checks=yes");
	
	
}