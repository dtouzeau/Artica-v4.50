<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["EnableTFTPDServer"])){Save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_TFTPD}</h1>
	<p>{APP_TFTPD_EXPLAIN}</p>
	</div>

	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-tftpd-service'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-tftpd-service','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$IPClass=new IP();
	$sock=new sockets();
	$EnableTFTPDServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTFTPDServer"));
	$security="AsSystemAdministrator";
	
	if(!$users->TFTPD_INSTALLED){$security="notinstalled";}
	
	
	$TFTPDListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPDListenInterface"));
	$TFTPDDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPDDirectory"));
	$TFTPDListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPDListenPort"));
	if($TFTPDListenPort==0){$TFTPDListenPort=69;}
	if($TFTPDDirectory==null){$TFTPDDirectory="/home/kiosk/tftp";}
	
	$form[]=$tpl->field_checkbox("EnableTFTPDServer","{enable_feature}",true);
	$form[]=$tpl->field_interfaces("TFTPDListenInterface", "{listen_interface}", $TFTPDListenInterface);
	$form[]=$tpl->field_numeric("TFTPDListenPort", "{listen_port}", $TFTPDListenPort);
	$form[]=$tpl->field_browse_directory("TFTPDDirectory", "{directory}", $TFTPDDirectory);
	$html[]=$tpl->form_outside("{general_settings}", @implode("\n", $form),null,"{apply}",null,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function Save(){
	$sock=new sockets();
	
	foreach ($_POST as $num=>$val){
		$sock->SET_INFO($num, url_decode_special_tool($val));
	}
	
	$sock->getFrameWork("tftpd.php?check=yes");

}