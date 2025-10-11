<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ZipProxyListenInterface"])){Save();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}

page();

function ufdbdebug_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{debug_mode}", "$page?ufdbdebug-popup=yes");

}
function ufdbconf_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{file_configuration}", "$page?ufdbconf-popup=yes");
	
}
function ufdbdebug_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$UfdbDebugAll=intval(($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDebugAll")));
	
	if($UfdbDebugAll==1){$UfdbDebugAll=0;}else{$UfdbDebugAll=1;}
	$sock->SET_INFO("UfdbDebugAll", $UfdbDebugAll);
	

    $jsrestart=$tpl->framework_buildjs("/ufdb/compile",
        "dansguardian2.mainrules.progress",
        "dansguardian2.mainrules.progress.log",
        "progress-ufdbs-status",
        "dialogInstance1.close();LoadAjax('table-Ziproxystatus','$page?table=yes');"
    );
	
	
	echo "<div class='row'><div id='progress-ufdbs-status'></div>
	<script>$jsrestart</script>		
	";
	
	
}


function ufdbconf_popup(){
	$tpl=new template_admin();
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	
	foreach ($f as $line){
		if(strlen($line)>86){
			$t[]=substr($line,0,86)."...";
			continue;
		}
		
		$t[]=$line;
	}
	
	$form=$tpl->field_textareacode("xxx", null, @implode("\n", $t));
	echo $tpl->form_outside(null, $form,null,null);
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();



	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_ZIPROXY} &raquo;&raquo; {service_status}</h1>
	<p>{APP_ZIPROXY_ABOUT}</p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-Ziproxy-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-Ziproxystatus'></div>

	</div>
	</div>
		
		
		
	<script>
	LoadAjax('table-Ziproxystatus','$page?table=yes');
		
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$sock->getFrameWork("zipproxy.php?status=yes");
	$ini->loadFile(PROGRESS_DIR."/ziproxy.status");
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/zipproxy.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/zipproxy.progress.log";
	$ARRAY["CMD"]="zipproxy.php?reconfigure=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-Ziproxystatus','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-Ziproxy-restart');";
	
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px' valign='top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td>
	<div class=\"ibox\">
    	<div class=\"ibox-content\">". $tpl->SERVICE_STATUS($ini, "APP_ZIPROXY",$jsrestart)."</div>
	</div></td></tr>";
	

	$ZipProxyListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZipProxyListenInterface"));
	$ZiproxyOutgoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZiproxyOutgoingInterface"));
	$zipproxy_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_port"));
	if($zipproxy_port==0){$zipproxy_port=5561;}
	
	$zipproxy_MaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_MaxSize"));
	if($zipproxy_MaxSize==0){$zipproxy_MaxSize=1048576;}
	$ConvertToGrayscale=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConvertToGrayscale"));
	
	$zipproxy_ProcessHTML=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessHTML"));
	$zipproxy_ProcessCSS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessCSS"));
	$zipproxy_ProcessJS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessJS"));
	
	
	$zipproxy_MaxSize=round($zipproxy_MaxSize/1024);
	
$html[]="</table></td>";

$html[]="<td style='width:99%;vertical-align:top'>";
$form[]=$tpl->field_interfaces("ZipProxyListenInterface", "{listen_interface}", $ZipProxyListenInterface);
$form[]=$tpl->field_interfaces("ZiproxyOutgoingInterface", "{outgoing_interface}", $ZiproxyOutgoingInterface);
$form[]=$tpl->field_numeric("zipproxy_port","{listen_port}",$zipproxy_port);
$form[]=$tpl->field_numeric("zipproxy_MaxSize","{maxsize} (KB)",$zipproxy_MaxSize);
$form[]=$tpl->field_checkbox("ConvertToGrayscale","{ConvertToGrayscale}",$ConvertToGrayscale,false,"{ConvertToGrayscale_explain}");
$form[]=$tpl->field_checkbox("zipproxy_ProcessHTML","{ProcessHTML}",$zipproxy_ProcessHTML,false);
$form[]=$tpl->field_checkbox("zipproxy_ProcessCSS","{ProcessCSS}",$zipproxy_ProcessCSS,false);
$form[]=$tpl->field_checkbox("zipproxy_ProcessJS","{ProcessJS}",$zipproxy_ProcessJS,false);
$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSquidAdministrator");
$html[]="</td>";
$html[]="</tr>";

$html[]="</table>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function Save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$_POST["zipproxy_MaxSize"]=$_POST["zipproxy_MaxSize"]*1024;
	
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
	
}


