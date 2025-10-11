<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.wazhu.rest.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["NagiosClientPort"])){main_api_form_save();exit;}
if(isset($_GET["delete-manager-js"])){delete_manager_js();exit;}
if(isset($_GET["delete-manager-popup"])){delete_manager_section();exit;}
if(isset($_GET["delete-manager-search"])){delete_manager_search();exit;}
if(isset($_GET["delete-manager-node"])){delete_manager_node_js();exit;}
if(isset($_POST["delete-manager-node"])){delete_manager_node_perform();exit;}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status-main"])){status_main();exit;}
if(isset($_GET["status-start"])){status_start();exit;}
if(isset($_POST["WazhuClientServer"])){Save();exit;}
if(isset($_GET["nagios-client-status"])){status_client();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_POST["reset"])){reset_save();exit;}
if(isset($_GET["nagios-client-top"])){status_top();exit;}
if(isset($_GET["events-start"])){events_start();exit;}
if(isset($_GET["search"])){search_results();exit;}
if(isset($_GET["nagios-form-js"])){main_form_js();exit;}
if(isset($_GET["nagios-form-popup"])){main_form_popup();exit;}
if(isset($_GET["nagios-api-js"])){main_api_form_js();exit;}
if(isset($_GET["nagios-api-popup"])){main_api_form_popup();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $APP_NAGIOS_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_VERSION");
    $html=$tpl->page_header("{APP_NAGIOS_CLIENT} v$APP_NAGIOS_CLIENT_VERSION",
        "fa-solid fa-sensor","{APP_NAGIOS_CLIENT_EXPLAIN}","$page?tabs=yes","nagios-client",
        "nagios-client-restart",false,"nagios-client-div");



	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}
function main_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{Connection}:{parameters}","$page?nagios-form-popup=yes");
    return true;

}
function main_api_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{settings}","$page?nagios-api-popup=yes");
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

    $nagios=new wazhu_rest();
    if(!$nagios->Auth()){
        echo $nagios->error;
        return false;
    }

    if(!$nagios->agents_delete($agentid)){
        echo $nagios->error;
        return false;
    }

    admin_tracks("Remove nagios agent $agentid From nagios master");
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
    $nagios=new wazhu_rest();
    if(!$nagios->Auth()){
        echo $tpl->div_error($nagios->error);
        return false;
    }

    $array=$nagios->agents_list($search);
    if(!$nagios->ok){
        echo $tpl->div_error($nagios->error);
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

    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));
    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/nagios/events/$ss/$MAX";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);



    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{type}</th>
        	<th nowrap>PID</th>
            <th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";




    $STATICO["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $STATICO["LOG"]=               "<span class='label'>INFO.</span>";
    $STATICO["INFO"]=               "<span class='label'>INFO.</span>";
    $STATICO["WARNING"]=           "<span class='label label-warning'>WARN.</span>";
    $STATICO["HINT"]=              "<span class='label label-info'>HINT.</span>";
    $STATICO["STATEMENT"]=         "<span class='label label-info'>STAT.</span>";
    $STATICO["NOTICE"]=         "<span class='label label-info'>NOTICE</span>";
    $STATICO["FATAL"]="<span class='label label-danger'>ERROR</span>";

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    foreach ($json->Logs as $line){
        $status="INFO";
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+).*?nagios(.+)#",$line,$re)){
            VERBOSE("NO MATCHES [$line]",__LINE__);
            continue;}
        $date=$re[1]." ".$re[2]." ".$re[3];

        $line=$re[4];
        if(preg_match("#^\[([0-9]+)\]:(.+)#",$line,$re)){
            $pid=$re[1];
            $line=$re[2];
        }

        if(preg_match("#:\s+([0-9\-]+)\s+([0-9:]+),.*?\s+([0-9]+)\s+([A-Z]+)\s+(.+)#",$line,$re)){
            $date=$re[1]." ".$re[2];
            $pid=$re[3];
            $status=$re[4];
            $line=$re[5];

        }


        $class=null;

        if(preg_match("#(No such file|Invalid|Error querying)#i",$line)){
            $class="text-warning font-bold";
        }
        if(preg_match("#(syntax error|failed to)#",$line)){
            $class="text-danger text-bold";
        }
        $type=$STATICO[$status];

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$date</td>
				<td style='width:1%;' nowrap class='$class'>$type</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td width=99% class='$class'>$line</td>
				</tr>";
    }



    $html[]="</table>";
    $html[]="<script>";
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
    echo "<div id='nagios-status-start' style='margin-top:15px'></div>
        <script>LoadAjax('nagios-status-start','$page?status-main=yes');</script>";
    return true;
}
function main_api_form_popup():bool{
    $tpl            = new template_admin();
    $page           = CurrentPageName();

    $NagiosClientInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientInterface"));
    $NagiosClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientPort"));
    $NagiosAdminPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosAdminPassword"));
    if($NagiosClientPort==0){$NagiosClientPort=5693;}
    $NagiosAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosAPIKey"));
    if($NagiosAPIKey==null){$NagiosAPIKey="mytoken";}
    $NagiosCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosCertificate"));

    $form[] = $tpl->field_numeric("NagiosClientPort", "{listen_port}", $NagiosClientPort, true);
    $form[] = $tpl->field_interfaces("NagiosClientInterface", "{listen_interface}", $NagiosClientInterface, false);
    $form[] = $tpl->field_certificate("NagiosCertificate", "{certificate}", $NagiosCertificate, false);
    $form[] = $tpl->field_password2("NagiosAdminPassword", "{REMOTE_ARTICA_PASSWORD}", $NagiosAdminPassword, false);
    $form[] = $tpl->field_text("NagiosAPIKey", "{API_KEY}", $NagiosAPIKey, false);

    $jsrestartSrv=$tpl->framework_buildjs("/nagios/restart","nagios.client.progress","nagios.client.progress.log","nagios-client-restart","LoadAjax('nagios-status-start','$page?status-main=yes')");

    $jsrestart="dialogInstance2.close();LoadAjaxSilent('nagios-client-div','$page?tabs=yes');$jsrestartSrv";
    echo $tpl->form_outside("{settings}",
        $form,null,"{apply}",
        $jsrestart,
        "AsSystemAdministrator");

    return true;
}
function main_api_form_save():bool{
    $tpl            = new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Saving Nagios client settings");
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


    $jsrestart="dialogInstance2.close();LoadAjaxSilent('nagios-client-div','$page?tabs=yes');".$tpl->framework_buildjs("/nagios/restart","nagios.client.progress","nagios.client.progress.log","nagios-client-restart","LoadAjax('nagios-status-start','$page?status-main=yes')");

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



    $jsrestart=$tpl->framework_buildjs("nagios.php?restart=yes","nagios.client.progress","nagios.client.progress.log","nagios-client-restart","LoadAjax('nagios-status-start','$page?status-main=yes')");

    $NagiosClientInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientInterface"));
    $NagiosClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientPort"));
    if($NagiosClientPort==0){$NagiosClientPort=5693;}
    if($NagiosClientInterface==null){$NagiosClientInterface="{all}";}
    $NagiosAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosAPIKey"));
    $NagiosCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosCertificate"));
    if($NagiosAPIKey==null){$NagiosAPIKey="mytoken";}
    $NagiosAdminPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosAdminPassword"));
    $tpl->table_form_field_js("Loadjs('$page?nagios-api-js=yes')");

    if($NagiosCertificate==null){$NagiosCertificate="{default}";}
    $tpl->table_form_field_text("{listen_port}","$NagiosClientInterface:$NagiosClientPort",ico_interface);
    $tpl->table_form_field_text("{certificate}","$NagiosCertificate",ico_certificate);



    if($NagiosAdminPassword==null) {
        $tpl->table_form_field_text("{REMOTE_ARTICA_PASSWORD}", "{not_defined}", ico_field);
    }else{
        $tpl->table_form_field_text("{REMOTE_ARTICA_PASSWORD}", "* * * *", ico_field);
    }
    $tpl->table_form_field_text("{API_KEY}","$NagiosAPIKey",ico_lock);

    $myform=$tpl->table_form_compile();


    $s_PopUp="s_PopUp('https://wiki.articatech.com/en/system/monitoring/nagios','1024','800')";
    $topbuttons[] = array($s_PopUp, ico_support, "Wiki URL");

    $APP_NAGIOS_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_NAGIOS_CLIENT} v$APP_NAGIOS_CLIENT_VERSION &raquo;&raquo; {services}";
    $TINY_ARRAY["ICO"]="fa-solid fa-sensor";
    $TINY_ARRAY["EXPL"]="{APP_NAGIOS_CLIENT_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $refesr=$tpl->RefreshInterval_js("nagios-client-status",$page,"nagios-client-status=yes");

	$html="<table style='width:100%;'>
    <tr>
	<td style='vertical-align:top;width:240px'><div id='nagios-client-status'></div></td>
	<td	style='vertical-align:top;width:90%;padding-left:20px'>
	    <div id='nagios-client-top' style='width:70%'></div>
	    $myform</td>
	</tr>
	</table>
	<script>
	$headsjs
	$refesr</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function status_top():bool{

    $tpl            = new template_admin();
    $statefile_dest=PROGRESS_DIR."/nagios-agentd.state";
    $f=explode("\n",@file_get_contents($statefile_dest));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        if(preg_match("#^(.+?)='(.+?)'#",$line,$re)){
            $MAIN[trim($re[1])]=trim($re[2]);
        }
    }


    $NagiosClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientPort"));
    if($NagiosClientPort==0){$NagiosClientPort=5693;}
    $NagiosClientIP=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientIP"));
    if($NagiosClientIP==null){
        $NagiosClientIP=$_SERVER["SERVER_ADDR"];
    }
    if($NagiosClientIP=="0.0.0.0"){
        $NagiosClientIP=$_SERVER["SERVER_ADDR"];
    }
    $s_PopUp="s_PopUp('https://$NagiosClientIP:$NagiosClientPort/login','1024','800')";

    $btn[0]["js"] = $s_PopUp;
    $btn[0]["name"] = "{login}";
    $btn[0]["icon"] = ico_html;
    $WebConsole=$tpl->widget_vert("{webadministration_console}","{webaccess}",$btn,ico_html);



    $s_PopUp="s_PopUp('https://wiki.articatech.com/en/system/monitoring/nagios','1024','800')";

    $btn[0]["js"] = $s_PopUp;
    $btn[0]["name"] = "WIKI";
    $btn[0]["icon"] = ico_support;
    $help=$tpl->widget_vert("","WIKI",$btn,ico_support);



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$WebConsole</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>$help</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>&nbsp;</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function status_client():bool{
    $page=CurrentPageName();
    $tpl            = new template_admin();

    $jsrestart=$tpl->framework_buildjs("/nagios/restart","nagios.client.progress","nagios.client.progress.log","nagios-client-restart","LoadAjax('nagios-status-start','$page?status-main=yes')");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/nagios/status"));


    if (json_last_error()> JSON_ERROR_NONE) {
        $status[]=$tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
        $status[]="<script>";
        $status[]="LoadAjaxSilent('nagios-client-top','$page?nagios-client-top=yes')";
        $status[]="</script>";
        echo $tpl->_ENGINE_parse_body($status);
        return false;

    }else {
        if (!$json->Status) {
            $status[]=$tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
            $status[]="<script>";
            $status[]="LoadAjaxSilent('nagios-client-top','$page?nagios-client-top=yes')";
            $status[]="</script>";
            echo $tpl->_ENGINE_parse_body($status);
            return false;
        } else {
            $bsini=new Bs_IniHandler();
            $bsini->loadString($json->Info);
            $status[]=$tpl->SERVICE_STATUS($bsini, "APP_NAGIOS_CLIENT",$jsrestart);
            $status[]=$tpl->SERVICE_STATUS($bsini, "APP_NAGIOS_CLIENT_PASSIVE",$jsrestart);
        }
    }
    $status[]="<script>";
    $status[]="LoadAjaxSilent('nagios-client-top','$page?nagios-client-top=yes')";
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
