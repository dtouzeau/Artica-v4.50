<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.wazhu.rest.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["WazhuClientApiUser"])){main_api_form_save();exit;}
if(isset($_GET["delete-manager-js"])){delete_manager_js();exit;}
if(isset($_GET["delete-manager-popup"])){delete_manager_section();exit;}
if(isset($_GET["delete-manager-search"])){delete_manager_search();exit;}
if(isset($_GET["delete-manager-node"])){delete_manager_node_js();exit;}
if(isset($_POST["delete-manager-node"])){delete_manager_node_perform();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status-main"])){status_main();exit;}
if(isset($_GET["status-start"])){status_start();exit;}
if(isset($_POST["WazhuClientServer"])){Save();exit;}
if(isset($_GET["wazhu-client-status"])){status_client();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_POST["reset"])){reset_save();exit;}
if(isset($_GET["wazhu-client-top"])){status_top();exit;}
if(isset($_GET["events-start"])){events_start();exit;}
if(isset($_GET["search"])){search_results();exit;}
if(isset($_GET["wazuh-form-js"])){main_form_js();exit;}
if(isset($_GET["wazuh-form-popup"])){main_form_popup();exit;}
if(isset($_GET["wazuh-api-js"])){main_api_form_js();exit;}
if(isset($_GET["wazuh-api-popup"])){main_api_form_popup();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    $html=$tpl->page_header("{APP_WAZHU} v$APP_WAZHU_VERSION",
        "fa-solid fa-sensor","{APP_WAZHU_EXPLAIN}","$page?tabs=yes","wazhu-client",
        "wazhu-client-restart",false,"wazhu-client-div");



	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}
function main_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{Connection}:{parameters}","$page?wazuh-form-popup=yes");
    return true;

}
function main_api_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{api_connection_parameters}","$page?wazuh-api-popup=yes");
    return true;
}
function delete_manager_node_js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $agent_id=$_GET["delete-manager-node"];
    $infos=unserialize(base64_decode($_GET["infos"]));
    $hostname=$infos[0];
    $IP=$infos[1];
    $id=$_GET["id"];

    $tpl->js_confirm_delete("{host} $hostname ($IP)",
        "delete-manager-node","$agent_id","$('#$id').remove()");


    return true;
}
function delete_manager_node_perform():bool{
    $agentid=$_POST["delete-manager-node"];
    $page = CurrentPageName();
    $tpl = new template_admin();

    $wazuh=new wazhu_rest();
    if(!$wazuh->Auth()){
        echo $wazuh->error;
        return false;
    }

    if(!$wazuh->agents_delete($agentid)){
        echo $wazuh->error;
        return false;
    }

    admin_tracks("Remove Wazuh agent $agentid From wazuh master");
    return true;
}


function tabs():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $array["{status}"] = "$page?status-start=yes";
    $array["{events}"] = "$page?events-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function delete_manager_js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->js_dialog2("{manage_agents}: {agents_list}","$page?delete-manager-popup=yes");
    return true;
}
function delete_manager_section():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    echo $tpl->search_block($page,null,null,null,"&delete-manager-search=yes");
    return true;
}

function delete_manager_search():bool{
    $search=trim($_GET["search"]);
    $search=str_replace("*","",$search);
    $page = CurrentPageName();
    $t=time();
    $tpl = new template_admin();
    $wazuh=new wazhu_rest();
    if(!$wazuh->Auth()){
        echo $tpl->div_error($wazuh->error);
        return false;
    }

    $array=$wazuh->agents_list($search);
    if(!$wazuh->ok){
        echo $tpl->div_error($wazuh->error);
        return false;
    }

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{hostname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{address}</th>";
    $html[]="<th data-sortable=falsse class='text-capitalize' data-type='text' nowrap>{status}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' nowrap>{last_com}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>Del</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($array as $agentid=>$main){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ec=urlencode("127.0.0.1");

        $id=md5($agentid.serialize($main));
        $infos=base64_encode(serialize($main));
        $hostname=$main[0];
        $IP=$main[1];
        $stats=$main[2];
        $date=strtotime($main[3]);
        $last_com=distanceOfTimeInWords($date,time());
        $delete=$tpl->icon_delete("Loadjs('$page?delete-manager-node=$agentid&infos=$infos&id=$id')","AsSystemAdministrator");
        if($IP=="127.0.0.1"){
            $last_com=null;
            $delete=$tpl->icon_nothing();
        }

        $html[]="<tr class='$TRCLASS' id='$id'>";
        $html[]="<td><strong><i class='fa fa-desktop'></i>&nbsp;$hostname</strong></td>";
        $html[]="<td style='width:1%' nowrap>$IP</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$stats</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$last_com</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$delete</center></td>";
        $html[]="</tr>";

    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script> 
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

    return true;
}

function events_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null);
    return true;
}
function search_results():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();


    $MAIN       = $tpl->format_search_protocol($_GET["search"],false,false,false,true);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/wazhu.client.syslog";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wazhu.client.php?logs=$line");


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{type}</th>
        	<th nowrap>{service}</th>
            <th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";


    $data=@file_get_contents($tfile);
    $results=@explode("\n",$data);
    krsort($results);

    $STATICO["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $STATICO["LOG"]=               "<span class='label'>INFO.</span>";
    $STATICO["INFO"]=               "<span class='label'>INFO.</span>";
    $STATICO["WARNING"]=           "<span class='label label-warning'>WARN.</span>";
    $STATICO["HINT"]=              "<span class='label label-info'>HINT.</span>";
    $STATICO["STATEMENT"]=         "<span class='label label-info'>STAT.</span>";
    $STATICO["NOTICE"]=         "<span class='label label-info'>NOTICE</span>";
    $STATICO["FATAL"]="<span class='label label-danger'>ERROR</span>";

    foreach ($results as $line){
        $WINEVNTS=false;
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+([0-9:]+)\s+(.+?):\s+([A-Z]+):\s+(.+)#",$line,$re)){continue;}
        $date=$re[1]." ".$re[2];
        $service=$re[3];
        $type=$STATICO[$re[4]];
        $line=$re[5];



        $class=null;

        if(preg_match("#(No such file|Invalid|Error querying)#i",$line)){
            $class="text-warning font-bold";
        }
        if(preg_match("#(syntax error|failed to)#",$line)){
            $class="text-danger text-bold";
        }

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$date</td>
				<td style='width:1%;' nowrap class='$class'>$type</td>
				<td style='width:1%;' nowrap class='$class'>$service</td>
				<td width=99% class='$class'>$line</td>
				</tr>";
    }



    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function reset_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{reset}","reset","yes","LoadAjax('table-loader-dwservice-pages','$page?table=yes');");
}

function status_start():bool{
    $page=CurrentPageName();
    echo "<div id='wazhu-status-start' style='margin-top:15px'></div>
        <script>LoadAjax('wazhu-status-start','$page?status-main=yes');</script>";
    return true;
}
function main_api_form_popup():bool{
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $WazhuClientApiUser= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiUser"));
    $WazhuClientApiPassword= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiPassword"));
    $WazhuClientApiPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiPort"));
    if($WazhuClientApiUser==null){$WazhuClientApiUser="wazuh";}
    if($WazhuClientApiPassword==null){$WazhuClientApiPassword="wazuh";}
    if($WazhuClientApiPort==0){$WazhuClientApiPort=55000;}

    $form[] = $tpl->field_numeric("WazhuClientApiPort", "{remote_server_port}", $WazhuClientApiPort, true);

    $form[] = $tpl->field_text("WazhuClientApiUser", "{username}", $WazhuClientApiUser, false);

    $form[] = $tpl->field_password2("WazhuClientApiPassword", "{password}", $WazhuClientApiPassword, false);


    $jsrestart="dialogInstance2.close();LoadAjaxSilent('wazhu-client-div','$page?tabs=yes');";
    echo $tpl->form_outside("{webapi_service}",
        $form,null,"{apply}",
        $jsrestart,
        "AsSystemAdministrator");

    return true;
}
function main_api_form_save():bool{
    $tpl            = new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks("Saving Wazuh API credentials to {$_POST["WazhuClientApiUser"]} on Port {$_POST["WazhuClientApiPort"]}");
    return true;
}
function main_form_popup():bool{

    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $WazhuClientServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientServer"));
    $WazhuClientServerPort   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientServerPort"));
    $WazhuClientGroup   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientGroup"));


    if($WazhuClientServerPort==0){$WazhuClientServerPort=1514;}
    if($WazhuClientGroup==null){$WazhuClientGroup="default";}


    $jsrestart="dialogInstance2.close();LoadAjaxSilent('wazhu-client-div','$page?tabs=yes');".$tpl->framework_buildjs("wazhu.client.php?restart=yes","wazhu.client.progress","wazhu.client.progress.log","wazhu-client-restart","LoadAjax('wazhu-status-start','$page?status-main=yes')");

    $form[] = $tpl->field_text("WazhuClientServer", "{remote_server_address}", $WazhuClientServer, true);
    $form[] = $tpl->field_numeric("WazhuClientServerPort", "{remote_server_port}", $WazhuClientServerPort, true);

    $form[] = $tpl->field_text("WazhuClientGroup", "{group}", $WazhuClientGroup, true);
    echo $tpl->form_outside("{Connection}",
        $form,null,"{connect}",
        $jsrestart,
        "AsSystemAdministrator");
    return true;
}

function status_main():bool{
    $tpl            = new template_admin();
	$page           = CurrentPageName();
	$WazhuClientServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientServer"));
    $WazhuClientServerPort   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientServerPort"));
    $WazhuClientGroup   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientGroup"));
    $WazhuClientEnrollment=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientEnrollment"));
    $WazhuClientApiPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiPort"));
    if($WazhuClientServerPort==0){$WazhuClientServerPort=1514;}
    if($WazhuClientGroup==null){$WazhuClientGroup="default";}
    if($WazhuClientApiPort==0){$WazhuClientApiPort=55000;}
    $WazhuClientApiUser= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientApiUser"));

    if($WazhuClientApiUser==null){$WazhuClientApiUser="wazuh";}
    $jsrestart=$tpl->framework_buildjs("wazhu.client.php?restart=yes","wazhu.client.progress","wazhu.client.progress.log","wazhu-client-restart","LoadAjax('wazhu-status-start','$page?status-main=yes')");

    $tpl->table_form_field_js("Loadjs('$page?wazuh-form-js=yes')");
    $tpl->table_form_field_text("{remote_server}","$WazhuClientServer:$WazhuClientServerPort",ico_server);
    $tpl->table_form_field_text("{group2}","$WazhuClientGroup",ico_directory);


    $tpl->table_form_field_js("Loadjs('$page?wazuh-api-js=yes')");
    $tpl->table_form_field_text("{webapi_service}","$WazhuClientApiUser@$WazhuClientServer:$WazhuClientApiPort",ico_proto);

    $myform=$tpl->table_form_compile();

    $topbuttons[] = array("Loadjs('$page?delete-manager-js=yes');", ico_server, "{manage_agents}");
    $s_PopUp="s_PopUp('https://wiki.articatech.com/system/syslog/log-sink','1024','800')";
    $topbuttons[] = array($s_PopUp, ico_support, "Wiki URL");

    $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_WAZHU} v$APP_WAZHU_VERSION &raquo;&raquo; {services}";
    $TINY_ARRAY["ICO"]="fa-solid fa-sensor";
    $TINY_ARRAY["EXPL"]="{APP_WAZHU_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html="<table style='width:100%;'>
    <tr>
	<td style='vertical-align:top;width:240px'><div id='wazhu-client-status'></div></td>
	<td	style='vertical-align:top;width:90%;padding-left:20px'>
	    <div id='wazhu-client-top' style='width:70%'></div>
	    $myform</td>
	</tr>
	</table>
	<script>
	$headsjs
	LoadAjaxSilent('wazhu-client-status','$page?wazhu-client-status=yes');</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function status_top():bool{
    $page=CurrentPageName();
    $tpl            = new template_admin();
    $statefile_dest=PROGRESS_DIR."/wazuh-agentd.state";
    $f=explode("\n",@file_get_contents($statefile_dest));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        if(preg_match("#^(.+?)='(.+?)'#",$line,$re)){
            $MAIN[trim($re[1])]=trim($re[2]);
        }
    }
    $connected=null;

    if($MAIN["status"]=="connected"){
        $connected=$tpl->widget_vert("{connection}","{connected}",array(),"fa-solid fa-sensor-on");
    }
    if($MAIN["status"]=="pending"){
        $connected=$tpl->widget_jaune("{connection}","{waiting}",array(),"fa-solid fa-sensor-cloud");
    }
    if($MAIN["status"]=="disconnected"){
        $connected=$tpl->widget_rouge("{connection}","{disconnected}",array(),"fa-solid fa-sensor-triangle-exclamation");
    }


    $last_keepalive=strtotime($MAIN["last_keepalive"]);
    if($last_keepalive>0) {
        $last_com_title = distanceOfTimeInWords($last_keepalive, time());
        $last_com=$tpl->widget_vert("{last_com}",$last_com_title,array(),"fa-solid fa-clock-rotate-left");
    }else{
        $last_com=$tpl->widget_grey("{last_com}","-","fa-solid fa-clock-rotate-left");
    }

    $msg_sent=intval($MAIN["msg_sent"]);
    if($msg_sent>0) {
        $msg_sent_title = $tpl->FormatNumber($msg_sent);
        $msg_sent_p=$tpl->widget_vert("{events}",$msg_sent_title,array(),"fa-solid fa-satellite-dish");
    }else{
        $msg_sent_p=$tpl->widget_grey("{events}","-","fa-solid fa-satellite-dish");
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$connected</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>$last_com</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>$msg_sent_p</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function status_client():bool{
    $page=CurrentPageName();
    $tpl            = new template_admin();
    $WazhuClientEnrollment=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientEnrollment"));

    $jsrestart=$tpl->framework_buildjs("wazhu.client.php?restart=yes","wazhu.client.progress","wazhu.client.progress.log","wazhu-client-restart","LoadAjax('wazhu-status-start','$page?status-main=yes')");

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("wazhu.client.php?status=yes");
    $bsini=new Bs_IniHandler(PROGRESS_DIR."/APP_WHAZU_AGENT.status");
    $status[]=$tpl->SERVICE_STATUS($bsini, "APP_WHAZU_AGENTD",$jsrestart);
    $status[]=$tpl->SERVICE_STATUS($bsini, "APP_WHAZU_EXECD",$jsrestart);
    $status[]=$tpl->SERVICE_STATUS($bsini, "APP_WHAZU_MODULESD",$jsrestart);
    $status[]=$tpl->SERVICE_STATUS($bsini, "APP_WHAZU_LOGCOLLECTOR",$jsrestart);
    $status[]=$tpl->SERVICE_STATUS($bsini, "APP_WHAZU_SYSCHECKD",$jsrestart);
    $status[]="<script>";
    $status[]="LoadAjaxTiny('wazhu-client-top','$page?wazhu-client-top=yes')";
    $status[]="</script>";
    echo $tpl->_ENGINE_parse_body($status);
    return true;
}

function Save():bool{
	$tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WazhuClientEnrollment",0);
    admin_tracks_post("Save Wazhu Connection Client settings");
	return true;
}
