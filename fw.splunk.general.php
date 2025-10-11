<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["SplunkServerAddress"])){save();exit;}
if(isset($_GET["splunk-status"])){status();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $version=$GLOBALS['CLASS_SOCKETS']->GET_INFO('APP_SPLUNK_FORWARDER_VERSION');

    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/system/syslog/send-events-to-splunk-server','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_SPLUNK_FORWARDER} {$version}</h1>
	<p>{APP_SPLUNK_FORWARDER_EXPLAIN}</p>
	$btn

	</div>

	</div>


	<div class='row'><div id='progress-splunk-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-splunk-service'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-splunk-service','$page?table=yes');

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
	$SplunkServerAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkServerAddress"));
	$SplunkServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkServerPort"));
    $SplunkServerEnableAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkServerEnableAuth"));
    $SplunkServerUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkServerUsername"));
    $SplunkServerPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkServerPassword"));
    $SplunkServerIndex=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkServerIndex"));

    $jsrestart=$tpl->framework_buildjs("/splunk-uf/reconfigure","splunk.install.prg","splunk.install.log","progress-splunk-restart","LoadAjax('table-loader-splunk-service','$page?table=yes');");

	
	
	$security="AsSystemAdministrator";
	
	
	$form[]=$tpl->field_text("SplunkServerAddress", "{SplunkServerAddress}", $SplunkServerAddress,false);
	$form[]=$tpl->field_numeric("SplunkServerPort", "{listen_port}",$SplunkServerPort,false);
    $form[]=$tpl->field_text("SplunkServerIndex", "{index} ({optional})", $SplunkServerIndex);
    $form[]=$tpl->field_checkbox("SplunkServerEnableAuth","{authentication}",$SplunkServerEnableAuth,"SplunkServerUsername,SplunkServerPassword");
    $form[]=$tpl->field_text("SplunkServerUsername", "{username}", $SplunkServerUsername);
    $form[]=$tpl->field_password("SplunkServerPassword", "{password}", $SplunkServerPassword);

	$myform=$tpl->form_outside("{general_settings}", @implode("\n", $form),"","{apply}",$jsrestart,$security);

    $Interval=$tpl->RefreshInterval_js("splunk-status",$page,"splunk-status=yes",3);

	$html="
	<table style='width:100%'>
		<tr>
			<td valign='top' style='width:450px'><div id='splunk-status' style='width:450px'></div></td>
			<td valign='top' style='padding-left:20px'>$myform</td>
		</tr>	
			
	</table>
	<script>
		$Interval
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
	$sock=new sockets();
    if($_POST["SplunkServerEnableAuth"]==1){
        if(empty($_POST["SplunkServerUsername"])){
            echo "jserror:Username is mandatory";
            return false;
        }
        if(empty($_POST["SplunkServerPassword"])){
            echo "jserror:Password is mandatory";
            return false;
        }
    }

    foreach ($_POST as $num=>$val){
		$_POST[$num]=url_decode_special_tool($val);
		$sock->SET_INFO($num, $_POST[$num]);
	}
	
	
	
}


function status(){
$page=CurrentPageName();
$tpl=new template_admin();
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/splunk-uf/status"));
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $jsRestart=$tpl->framework_buildjs("/splunk-uf/restart","splunk.install.prg",
        "splunk.install.log","progress-splunk-restart","LoadAjaxSilent('table-loader-splunk-service','$page?table=yes')");
    echo $tpl->SERVICE_STATUS($bsini, "APP_SPLUNK_FORWARDER",$jsRestart);



}
