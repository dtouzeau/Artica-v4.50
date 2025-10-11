<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.openvpn.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["PrivoxyPort"])){Save();exit;}
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
    "dialogInstance1.close();LoadAjax('table-privoxystatus','$page?table=yes');"
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
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_PRIVOXY} &raquo;&raquo; {service_status}</h1>
	<p>{privoxy_explain}</p>

	</div>

	</div>
		

		
	<div class='row'><div id='progress-privoxy-restart'></div>
	<div class='ibox-content' style='min-height:600px'>

	<div id='table-privoxystatus'></div>

	</div>
	</div>
		
		
		
	<script>
	LoadAjax('table-privoxystatus','$page?table=yes');
		
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$sock->getFrameWork("privoxy.php?status=yes");
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/web/privoxy.status");
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.txt";
	$ARRAY["CMD"]="privoxy.php?restart=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-privoxystatus','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-privoxy-restart');";
	
	
	
	
	$PrivoxyPatternStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyPatternStatus"));
	if(isset($PrivoxyPatternStatus["TIME"])){
		$ss[]="<div><small>{pattern_date}:<strong>".$tpl->time_to_date($PrivoxyPatternStatus["TIME"],true) ."</strong></small></div>";
	}
	if(isset($PrivoxyPatternStatus["EHR"])){
		$ss[]="<div><small>{Elements_hiding_rules}:<strong>{$PrivoxyPatternStatus["EHR"]}</strong></small></div>";
	}
	if(isset($PrivoxyPatternStatus["RBRFE"])){
		$ss[]="<div><small>{Requestblockrulesforexception}:<strong>{$PrivoxyPatternStatus["RBRFE"]}</strong></small></div>";
	}	
	if(isset($PrivoxyPatternStatus["RBRT"])){
		$ss[]="<div><small>{Requestblockrulestotal}:<strong>{$PrivoxyPatternStatus["RBRT"]}</strong></small></div>";
	}	
	if(isset($PrivoxyPatternStatus["RBRDO"])){
		$ss[]="<div><small>{Requestblockruleswithdomainoption}:<strong>{$PrivoxyPatternStatus["RBRDO"]}</strong></small></div>";
	}
	if(isset($PrivoxyPatternStatus["RBRRTO"])){
		$ss[]="<div><small>{Requestblockruleswithrequesttypeoptions}:<strong>{$PrivoxyPatternStatus["RBRRTO"]}</strong></small></div>";
	}
	if(isset($PrivoxyPatternStatus["RTPO"])){
		$ss[]="<div><small>{Ruleswiththirdpartyoption}:<strong>{$PrivoxyPatternStatus["RTPO"]}</strong></small></div>";
	}

	if(count($ss)>0){

		
		$sss="<div style='min-height:240px;margin-top:2px' class='widget lazur-bg p-lg text-center'>
		<i class='fa fa-info fa-4x'></i>
		<h2 class='font-bold no-margins'>{databases}</h2>
		".@implode("", $ss)."
		</div>";
	}
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px' valign='top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td>
	<div class=\"ibox\">
    	<div class=\"ibox-content\">". $tpl->SERVICE_STATUS($ini, "APP_PRIVOXY",$jsrestart)."$sss</div>
	</div></td></tr>";
	
	$PrivoxyUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyUpdates"));
	if($PrivoxyUpdates==0){$PrivoxyUpdates=3;}
	// 45 0 */3 * *
	$PrivoxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyPort"));
	$PrivoxyAllowSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyAllowSSL"));
	$PrivoxyAllowGWL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyAllowGWL"));
	$PrivoxyAllowPOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyAllowPOST"));
	$FanboyAnnoyanceList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FanboyAnnoyanceList"));
	$FanboySocialBlockingList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FanboySocialBlockingList"));
	$EasyPrivacy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyPrivacy"));
	$EasyListGermany=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListGermany"));
	$EasyListItaly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListItaly"));
	$EasyListDutch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListDutch"));
	$EasyListFrench=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListFrench"));
	$EasyListChina=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListChina"));
	$EasyListBulgarian=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListBulgarian"));
	$EasyListIndonesian=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListIndonesian"));
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress.log";
	$ARRAY["CMD"]="privoxy.php?update=yes";
	$ARRAY["TITLE"]="{update_databases}";
	$ARRAY["AFTER"]="dialogInstance1.close();LoadAjax('table-privoxystatus','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsupdate="Loadjs('fw.progress.php?content=$prgress&mainid=progress-privoxy-restart');";
	
	$ARRAY=array();
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.txt";
	$ARRAY["CMD"]="privoxy.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="dialogInstance1.close();LoadAjax('table-privoxystatus','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-privoxy-restart');";
	
	$TPrivoxyUpdates[1]="1 {day}";
	$TPrivoxyUpdates[2]="2 {days}";
	$TPrivoxyUpdates[3]="3 {days}";
	
	
$html[]="</table></td>";

$html[]="<td style='width:99%;vertical-align:top'>";
$form[]=$tpl->field_numeric("PrivoxyPort","{listen_port}",$PrivoxyPort);
$form[]=$tpl->field_array_hash($TPrivoxyUpdates, "TPrivoxyUpdates","{update_each}", $PrivoxyUpdates);
$form[]=$tpl->field_checkbox("PrivoxyAllowSSL","{forward_ssl_traffic}",$PrivoxyAllowSSL,false,"{forward_ssl_traffic_explain}");
$form[]=$tpl->field_checkbox("PrivoxyAllowPOST","{forward_POST}",$PrivoxyAllowPOST,false,"{forward_POST_explain}");
$form[]=$tpl->field_checkbox("PrivoxyAllowGWL","{forward_global_whitelists}",$PrivoxyAllowGWL,false,"{forward_global_whitelists_explain}");



$form[]=$tpl->field_checkbox("EasyPrivacy","{EasyPrivacy}",$EasyPrivacy,false,"{EasyPrivacy_explain}");
$form[]=$tpl->field_checkbox("FanboyAnnoyanceList","{Fanboy_Annoyance_List}",$FanboyAnnoyanceList,false,"{Fanboy_Annoyance_List_explain}");
$form[]=$tpl->field_checkbox("FanboySocialBlockingList","{Fanboy_Social_Blocking_List}",$FanboyAnnoyanceList,false,"{Fanboy_Social_Blocking_List_explain}");
$form[]=$tpl->field_checkbox("EasyListGermany","EasyList {germany}",$EasyListGermany,false);
$form[]=$tpl->field_checkbox("EasyListItaly","EasyList {italy}",$EasyListItaly,false);
$form[]=$tpl->field_checkbox("EasyListDutch","EasyList {dutch}",$EasyListDutch,false);
$form[]=$tpl->field_checkbox("EasyListFrench","EasyList {french}",$EasyListFrench,false);
$form[]=$tpl->field_checkbox("EasyListChina","EasyList {china}",$EasyListChina,false);
$form[]=$tpl->field_checkbox("EasyListBulgarian","EasyList {bulgarian}",$EasyListBulgarian,false);
$form[]=$tpl->field_checkbox("EasyListIndonesian","EasyList {indonesian}",$EasyListIndonesian,false);

$tpl->form_add_button("{update_databases}", $jsupdate);

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


