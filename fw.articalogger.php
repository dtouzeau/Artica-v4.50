<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SquidLoggerPort"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_ARTICALOGGER}</h1>
	<p>{APP_ARTICALOGGER_EXPLAIN}</p>
	</div>

	</div>



	<div class='row'><div id='progress-articalogger-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-articalogger-service'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/artica-logger');
	LoadAjax('table-loader-articalogger-service','$page?tabs=yes');

	</script>";
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}
	

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();

	$array["{parameters}"]="$page?table=yes";
	$array["{events}"]="fw.articalogger.events.php";


	echo $tpl->tabs_default($array);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$IPClass=new IP();
	$sock=new sockets();
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:450px;vertical-align:top'>";
	$html[]="<div id='articalogger-status'></div>";
	$html[]="</td>";
	$html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";

	$ElasticsearchAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchAddr"));
	$ElasticsearchBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchBindPort"));
	$SquidLoggerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLoggerPort"));
	$SquidLoggerAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLoggerAddr"));

	$SquidLoggerEnableElasticSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLoggerEnableElasticSearch"));
	$SquidTailDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTailDebug"));
	
	
	if($SquidLoggerPort==0){$SquidLoggerPort=1444;}
	if($SquidLoggerAddr==null){$SquidLoggerAddr="127.0.0.1";}
	if($ElasticsearchAddr==null){$ElasticsearchAddr="127.0.0.1";}
	if($ElasticsearchBindPort==0){$ElasticsearchBindPort=9200;}

	
	
	$INTERFACES["127.0.0.1"]="{loopback}";
	$INTERFACES["0.0.0.0"]="{all_interfaces}";
	
	$security="AsWebStatisticsAdministrator";
		
	$form[]=$tpl->field_section("{service2}");
	$form[]=$tpl->field_numeric("SquidLoggerPort","{listen_port}",$SquidLoggerPort);
	$form[]=$tpl->field_array_hash($INTERFACES, "SquidLoggerAddr", "{listen_interface}", $SquidLoggerAddr);
	$form[]=$tpl->field_checkbox("SquidTailDebug","{debug}",$SquidTailDebug);

	
	$form[]=$tpl->field_section("ElasticSearch");
	$form[]=$tpl->field_checkbox("SquidLoggerEnableElasticSearch","{SquidLoggerEnableElasticSearch}",$SquidLoggerEnableElasticSearch,"ElasticsearchAddr,ElasticsearchBindPort","{SquidLoggerEnableElasticSearch_explain}");
	$form[]=$tpl->field_text("ElasticsearchAddr", "{address}", $ElasticsearchAddr);
	$form[]=$tpl->field_numeric("ElasticsearchBindPort","{listen_port}",$ElasticsearchBindPort);
	
	

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/SquidLogger.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/SquidLogger.restart.progress.log";
	$ARRAY["CMD"]="squidlogger.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="LoadAjaxTiny('articalogger-status','$page?status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-articalogger-restart')";
	
	
	

	$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,$security);
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>LoadAjaxTiny('articalogger-status','$page?status=yes');</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function save(){
	$sock=new sockets();
	$tpl=new template_admin();

	
	
	foreach ($_POST as $num=>$val){
		$_POST[$num]=url_decode_special_tool($val);
		$sock->SET_INFO($num, $_POST[$num]);
	}
	
}

function status(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new template_admin();
	
	$page=CurrentPageName();
	$datas=$sock->getFrameWork("squidlogger.php?status=yes");
	
	
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/squidlogger.status");
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/SquidLogger.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/SquidLogger.restart.progress.log";
	$ARRAY["CMD"]="squidlogger.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="LoadAjaxTiny('articalogger-status','$page?status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-articalogger-restart')";
	
	echo $tpl->SERVICE_STATUS($ini, "APP_ARTICALOGGER",$jsrestart);
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}