<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["NetDataListenPort"])){Save();exit;}
if(isset($_GET["status"])){Status();exit;}
if(isset($_GET["ndpid-flat-config"])){flat_config();exit;}
if(isset($_GET["ndpi-top-status"])){Status_top();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html= $tpl->page_header("{APP_NDPI}",ico_sensor,"{traffic_inspection_explain}","$page?table=yes","dpi","progress-ndpi-restart",false,"table-loader-netdata");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NDPI}",$html);
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
    $jsrestart=$tpl->framework_buildjs("/firewall/ndpi/restart",
        "ndpi.restart.progress","ndpi.restart.progress.log","progress-ndpi-restart");




    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/ndpi/status");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        $status=$tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));

    }else {
        if (!$json->Status) {
            $status = $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));
        }else{
            $ini = new Bs_IniHandler();
            $ini->loadString($json->Info);
            $status=array();
            $status[] = $tpl->SERVICE_STATUS($ini, "APP_NDPI",$jsrestart);
        }
    }
echo $tpl->_ENGINE_parse_body($status);
}
function Status_top():bool{
    $tpl=new template_admin();
    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/ndpi/metrics");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return false;
    }
    if(!property_exists($json,"Info")){
        return false;
    }
    $Info=$json->Info;
    $PacketsReceived=0;
    $BytesReceived=0;
    if(property_exists($Info,"Packets")){
        $PacketsReceived=$Info->Packets;
    }
    if(property_exists($Info,"Bytes")){
        $BytesReceived=$Info->Bytes;
    }
    $NDPI_PROTOS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPI_PROTOS"));
    $widget_packets=$tpl->widget_style1("gray-bg","fas fa-cloud-showers-heavy","{packets}",0);
    $widget_bytes=$tpl->widget_style1("gray-bg",ico_nic,"{traffic_received}",0);
    $widget_records=$tpl->widget_style1("gray-bg",ico_database,"{applications}",0);

    if($PacketsReceived>0){
        $widget_packets=$tpl->widget_style1("navy-bg","fas fa-cloud-showers-heavy","{packets}",$tpl->FormatNumber($PacketsReceived));
    }
    if($BytesReceived>0){
        $widget_bytes=$tpl->widget_style1("navy-bg",ico_nic,"{traffic_received}",FormatBytes($BytesReceived/1024));
    }
    if($NDPI_PROTOS>0){
        $widget_records=$tpl->widget_style1("navy-bg",ico_database,"{applications}",$tpl->FormatNumber($NDPI_PROTOS));
    }

    $html[]="<table style='width:100%;margin-top:-8px;margin-left:5px'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_packets</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_bytes</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$widget_records</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
    $page=CurrentPageName();
	$tpl=new template_admin();


    $js=$tpl->RefreshInterval_js("ndpi-status","$page","status=yes");
    $js1=$tpl->RefreshInterval_js("ndpi-top-status","$page","ndpi-top-status=yes");

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:400px;vertical-align:top'><div id='ndpi-status'></div></td>";
	$html[]="<td style='width:99%;vertical-align: top'>
                <div id='ndpi-top-status'></div>
                <div id='ndpid-flat-config'></div>
    </td>";
    $html[]="</tr>";
    $html[]="</table>";
    $topbuttons=array();
    $NDPI_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPI_VERSION");
    if(strlen($NDPI_VERSION)>2){
        $NDPI_VERSION="v$NDPI_VERSION";
    }
    $TINY_ARRAY["TITLE"]="{APP_NDPI} $NDPI_VERSION";
    $TINY_ARRAY["ICO"]=ico_sensor;
    $TINY_ARRAY["EXPL"]="{traffic_inspection_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="LoadAjaxSilent('ndpid-flat-config','$page?ndpid-flat-config=yes');";
    $html[]=$js;
    $html[]=$js1;
    $html[]=$jstiny;
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function flat_config():bool{
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/ndpi/info"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $tb=explode("\n",$json->Info);

    foreach ($tb as $line){
        if(!preg_match("#^(.+?):\s+(.+)#",$line,$re)) {
            continue;
        }
        $re[1]=strtolower($re[1]);
        if($re[1]=="parm"){$re[1]="option";}
        $re[1]="{".$re[1]."}";
        $tpl->table_form_field_text($re[1], "<small>$re[2]</small>", ico_infoi);
    }


    echo $tpl->table_form_compile();
    return true;

}

function Save(){
	
	
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, url_decode_special_tool($value));
	}
	
	
	
	
}