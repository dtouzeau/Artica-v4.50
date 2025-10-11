<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["NetDataListenPort"])){Save();exit;}
if(isset($_GET["status"])){Status();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_NETDATA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NETDATA_VERSION");

    $html= $tpl->page_header("{APP_NETDATA} v$APP_NETDATA_VERSION",
        "fa fa-heart","{APP_NETDATA_EXPLAIN}",
    "$page?table=yes","netdata-config","progress-netdata-restart",false,"table-loader-netdata");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NETDATA} v$APP_NETDATA_VERSION",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
function Status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs("/netdata/restart",
        "netdata.progress","netdata.progress.log","progress-netdata-restart");

    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/netdata/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        $status=$tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));

    }else {
        if (!$json->Status) {
            $status = $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));
        }else{
            $ini = new Bs_IniHandler();
            $ini->loadString($json->Info);
            $status = $tpl->SERVICE_STATUS($ini, "APP_NETDATA",$jsrestart);
        }
    }
echo $tpl->_ENGINE_parse_body($status);
}
function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();
	$NetDataListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDataListenPort"));
	if($NetDataListenPort==0){$NetDataListenPort=19999;}
	$NetDataHistory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDataHistory"));



	if($NetDataHistory==0){$NetDataHistory=10800;}
    $NetDataHash[10800]="3 {hours} {disk}: 64MB";
    $NetDataHash[21600]="6 {hours} {disk}: 204MB";
	$NetDataHash[86400]="1 {day} {disk}: 816MB";
	$NetDataHash[172800]="2 {days} {disk}: 1.632GB";
	$NetDataHash[432000]="5 {days} {disk}: 4.080GB";
	
	$MAIN_URI="https://{$_SERVER["SERVER_ADDR"]}:".$_SERVER["SERVER_PORT"]."/netdata/";
	
	$jsafter="LoadAjaxSilent('top-barr','fw-top-bar.php');";
	
	$form[]=$tpl->field_numeric("NetDataListenPort","{listen_port}",$NetDataListenPort);
	$form[]=$tpl->field_array_hash($NetDataHash, "NetDataHistory", "{retention}", $NetDataHistory);
	$form[]=$tpl->field_url("{web_interface}",$MAIN_URI);

	$myform=$tpl->form_outside("{main_parameters}", @implode("\n", $form),null,"{apply}","","AsSystemAdministrator");

    $js=$tpl->RefreshInterval_js("netdata-status","$page","status=yes");

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:400px'><div id='netdata-status'></div></td>";
	$html[]="<td style='width:99%'>$myform</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$js;
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save(){
	
	
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, url_decode_special_tool($value));
	}
	
	
	
	
}