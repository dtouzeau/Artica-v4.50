<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["NetDataListenPort"])){Save();exit;}
if(isset($_GET["status"])){Status();exit;}
if(isset($_GET["ndpid-flat-config"])){flat_config();exit;}
if(isset($_GET["ndpid-top-status"])){Status_top();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $NDPID_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPID_VERSION");

    $html= $tpl->page_header("{traffic_inspection} v$NDPID_VERSION",
        ico_sensor,"{traffic_inspection_explain}",
    "$page?table=yes","netmonix","progress-netmonix-restart",false,"table-loader-netdata");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{traffic_inspection} v$NDPID_VERSION",$html);
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
    $jsrestart=$tpl->framework_buildjs("/ndpid/restart",
        "netmonix.progress","netmonix.progress.log","progress-netmonix-restart");



    $EnableNetMonix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNetMonix"));

    if($EnableNetMonix==0) {

        $install=$tpl->framework_buildjs("/netmonix/install",
            "netmonix.progress","netmonix.progress.log","progress-netmonix-restart");

        $button=$tpl->button_autnonome("{install}", $install,
            ico_cd,null,0,"btn-default");
        $button="<div style='margin-top:30px'>$button</div>";
        echo $tpl->_ENGINE_parse_body("<div style='vertical-align:top;width:335px'>
				<div class='widget gray-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
				<i class='fa-blink far fa-times-circle fa-4x'></i>
				<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>{inactive2}</H3>
				<H2 class='font-bold no-margins'>{not_installed}</H2>$button
				</div>
				</div>");
    }


    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/netmonix/status");
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
            foreach ($ini->_params as $key => $value) {
                if(preg_match("#APP_NETMONIX_(.+)#",$key,$matches)){
                    $Interface=$matches[1];
                    $ini->ChangetitleTo="{APP_NETMONIX} $Interface";
                    $status[] = $tpl->SERVICE_STATUS($ini, $key,$jsrestart);
                }

            }
        }
    }
echo $tpl->_ENGINE_parse_body($status);
}
function Status_top():bool{
    $tpl=new template_admin();
    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/ndpid/metrics");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return false;
    }
    if(!property_exists($json,"Info")){
        return false;
    }
    $Info=$json->Info;

    if(!property_exists($Info,"PacketsReceived")){
        return false;
    }
    $PacketsReceived=$Info->PacketsReceived;
    $BytesReceived=$Info->BytesReceived;
    $Records=$Info->Records;
    $Requests=$Info->Requests;
    $widget_packets=$tpl->widget_style1("gray-bg","fas fa-cloud-showers-heavy","{packets}",0);
    $widget_bytes=$tpl->widget_style1("gray-bg",ico_nic,"{traffic_received}",0);
    $widget_records=$tpl->widget_style1("gray-bg",ico_mem,"{records_in_memory}",0);

    if($PacketsReceived>0){
        $widget_packets=$tpl->widget_style1("navy-bg","fas fa-cloud-showers-heavy","{packets}",$tpl->FormatNumber($PacketsReceived)."/".$tpl->FormatNumber($Requests));
    }
    if($BytesReceived>0){
        $widget_bytes=$tpl->widget_style1("navy-bg",ico_nic,"{traffic_received}",FormatBytes($BytesReceived/1024));
    }
    if($Records>0){
        $widget_records=$tpl->widget_style1("navy-bg",ico_mem,"{records_in_memory}",$tpl->FormatNumber($Records));
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


    $js=$tpl->RefreshInterval_js("netdata-status","$page","status=yes");
    $js1=$tpl->RefreshInterval_js("ndpid-top-status","$page","ndpid-top-status=yes");

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:400px'><div id='netdata-status'></div></td>";
	$html[]="<td style='width:99%;vertical-align: top'>
                <div id='ndpid-top-status'></div>
                <div id='ndpid-flat-config'></div>
    </td>";
    $html[]="</tr>";
    $html[]="</table>";

    $sync=$tpl->framework_buildjs("/ndpid/sync-stats","ndpid.progress","ndpid.log","progress-netmonix-restart");

    $topbuttons[] = array($sync, ico_refresh, "{synchronize}");

    $NDPID_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPID_VERSION");
    $TINY_ARRAY["TITLE"]="{traffic_inspection} v$NDPID_VERSION";
    $TINY_ARRAY["ICO"]=ico_sensor;
    $TINY_ARRAY["EXPL"]="{traffic_inspection_explain}}";
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
    $SuricataPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPurge"));
    if($SuricataPurge==0){$SuricataPurge=15;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$SuricataPurge=2;}

    $q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
    $results=$q->QUERY_SQL("SELECT interface,threads FROM suricata_interfaces WHERE enable=1");
    $c=0;
    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?main-js=yes&ndpid=yes')");
    if($q->ok){
        foreach($results as $ligne){
            $interface=$ligne["interface"];
            $c++;
            $tpl->table_form_field_text("{interface}","$interface",ico_nic);
        }
    }
    if($c==0){
        $tpl->table_form_field_text("{interface}","{none}",ico_nic);
    }

    $tpl->table_form_field_js("Loadjs('fw.ids.settings.php?statistics-js=yes')");
    $CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();


    if($CORP_LICENSE==1) {
        $tpl->table_form_field_text("{retention_days}", $SuricataPurge, ico_hd);
    }else{
        $tpl->table_form_field_text("{retention_days}", 2, ico_hd);
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