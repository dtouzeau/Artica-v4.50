<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["after-js"])){after_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["EnableArticaAsGateway"])){save();exit;}
js();

function js(){
	$tpl    = new template_admin();
	$page   = CurrentPageName();
	$tpl->js_dialog6("{gateway_mode}", "$page?popup=yes",650);
}


function popup(){
	$tpl    = new template_admin();
	$page   = CurrentPageName();
	$html[]="<div id='progress-network-restart'></div>";
	
	
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/exec.virtuals-ip.php.html";
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/reconfigure-newtork.progress";
	$ARRAY["CMD"]="/system/network/reconfigure-restart";
	$ARRAY["TITLE"]="{please_wait_building_network}";
	$ARRAY["AFTER"]="dialogInstance6.close();Loadjs('fw.network.apply.php?after-js=yes');";
	$prgress=base64_encode(serialize($ARRAY));



	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-network-restart')";
	$EnableArticaAsGateway=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
	
	$form[]=$tpl->field_checkbox("EnableArticaAsGateway","{ARTICA_AS_GATEWAY}",$EnableArticaAsGateway,"{ARTICA_AS_GATEWAY_EXPLAIN}");
	$html[]=$tpl->form_outside("{gateway_mode}", @implode("\n", $form),"{ARTICA_AS_GATEWAY_EXPLAIN}","{apply}","Loadjs('fw.network.apply.php')","AsSystemAdministrator");
	
	echo @implode("\n", $html);
	
	
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableArticaAsGateway", $_POST["EnableArticaAsGateway"]);
	// Ecrire la cle ne suffit pas : c'est sysctl.Build() qui recalcule le
	// verdict MustBeAgateway et regenere /etc/sysctl.conf. Sans cet appel,
	// fw.network.interfaces.php (qui declenche bien /system/sysctl sur son
	// propre bouton) et cette page ecrivaient la MEME cle avec deux effets
	// differents -- ici, le reglage etait enregistre sans jamais etre applique.
	$sock->REST_API("/system/sysctl");
}

function after_js(){
	header("content-type: application/x-javascript");
	$h[]="if(document.getElementById('route-dump-fields') ){ RouteDumpFields();}";
	echo @implode("\n", $h);
}