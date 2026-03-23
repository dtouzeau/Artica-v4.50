<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__) . "/ressources/class.logfile_daemon.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["tooltip-js"])){tooltip_js();exit;}
if(isset($_GET["tooltip1-js"])){tooltip1_js();exit;}
if(isset($_GET["tooltip-popup"])){tooltip_popup();exit;}
if(isset($_GET["tooltip-popup2"])){tooltip_popup2();exit;}
if(isset($_GET["tooltip2-js"])){tooltip2_js();exit;}
if(isset($_POST["tooltip2"])){tooltip2_exec();exit;}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["global-js"])){section_global_js();exit;}
if(isset($_GET["section-global-popup"])){section_global_popup();exit;}

if(isset($_GET["health-check-js"])){section_healthcheck_js();exit;}
if(isset($_GET["section-healthcheck-popup"])){section_healthcheck_popup();exit;}

if(isset($_GET["emergency-js"])){section_emergency_js();exit;}
if(isset($_GET["section-emergency-popup"])){section_emergency_popup();exit;}


if(isset($_GET["cache-js"])){section_cache_js();exit;}
if(isset($_GET["section-cache-popup"])){section_cache_popup();exit;}

if(isset($_GET["logging-js"])){section_logging_js();exit;}
if(isset($_GET["section-logging-popup"])){section_logging_popup();exit;}
if(isset($_GET["agents-start"])){agents_section();exit;}
if(isset($_GET["agents-section-js"])){agents_section_js();exit;}
if(isset($_GET["destinations-prepare"])){destinations_prepare();exit;}
if(isset($_GET["td-destinations"])){td_destinations();exit;}
if(isset($_GET["new-agent-js"])){new_agent_js();exit;}
if(isset($_GET["new-agent-section"])){new_agent_section();exit;}

if(isset($_POST["aaa_auth_mode"])){save();exit;}
if(isset($_POST["aaa_health_check_enable"])){save();exit;}
if(isset($_POST["aaa_emergency_mode"])){save();exit;}
if(isset($_POST["aaa_cache_enable"])){save();exit;}
if(isset($_POST["aaa_log_level"])){save();exit;}
if(isset($_POST["aaa_agent_name"])){save_agent();exit;}
if(isset($_GET["remove-agent-js"])){delete_agent_js();exit;}

if(isset($_GET["edit-agent-js"])){edit_agent_js();exit;}
if(isset($_GET["edit-agent-section"])){edit_agent_popup();exit;}
if (isset($_POST["delete-agent-confirm"])) {
    delete_agent_popup();
    exit;
}
if(isset($_GET["status"])){status();exit;}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();

    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $agents = $q->COUNT_ROWS("aaa_agents");

    if($agents==0){
        $final=$tpl->widget_grey("{ARTICA_AAA_AUTH_AGENT}","{no_connector}",ico_list);
        echo $tpl->_ENGINE_parse_body($final);
        return;
    }


    $json=json_decode($sock->REST_API("/aaa/auth/status"));
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $json=json_decode($sock->REST_API("/aaa/groups/status"));
    $bsiniGroups=new Bs_IniHandler();
    $bsiniGroups->loadString($json->Info);
    $ARTICA_AUTH_HELPER_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_AUTH_HELPER_VER"));
    $ARTICA_GROUPS_HELPER_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_GROUPS_HELPER_VER"));
    $final[]=$tpl->SERVICE_STATUS($bsini, "ARTICA_AAA_AUTH_AGENT",null,$ARTICA_AUTH_HELPER_VER);
    $final[]=$tpl->SERVICE_STATUS($bsiniGroups, "ARTICA_AAA_GROUPS_AGENT",null,$ARTICA_GROUPS_HELPER_VER);
    echo $tpl->_ENGINE_parse_body($final);

};
function edit_agent_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$_GET["edit-agent-js"];
    $name=$_GET["agent-name"];
    $title=$tpl->_ENGINE_parse_body("{edit} {aaa_agent} $name");
    $tpl->js_dialog2($title, "$page?edit-agent-section=$id");
}

function edit_agent_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $agentData = $q->mysqli_fetch_array("SELECT * FROM aaa_agents where ID='{$_GET["edit-agent-section"]}'");
    if($agentData['aaa_agent_name']==""){echo "No Agent Found";return false;}
    $form[]=$tpl->field_hidden("aaa_agent_id","{$agentData['ID']}");
    //$form[]=$tpl->field_text("aaa_agent_name","{name}","{$agentData['aaa_agent_name']}",true);
    $form[]=$tpl->field_certificate("aaa_agent_name","{certificate}","{$agentData['aaa_agent_name']}");
    $form[]=$tpl->field_text("aaa_agent_ip","{ip} / {hostname}","{$agentData['aaa_agent_ip']}",true);
    $form[]=$tpl->field_numeric("aaa_agent_port","gRPC {port}","{$agentData['aaa_agent_port']}");
    $form[]=$tpl->field_numeric("aaa_agent_api_port","API {port}","{$agentData['aaa_agent_api_port']}");
    $form[]=$tpl->field_numeric("aaa_agent_priority","{priority}","{$agentData['aaa_agent_priority']}");
    $form[]=$tpl->field_numeric("aaa_agent_weight","{weight}","{$agentData['aaa_agent_weight']}");
    $form[]=$tpl->field_checkbox("aaa_agent_failover","{failover}","{$agentData['aaa_agent_failover']}");
    $form[]=$tpl->field_interfaces("aaa_bind_interface","{outgoing_interface}","{$agentData['aaa_bind_interface']}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{save}",
        "LoadAjaxSilent('artica-agent-agents-section','$page?save-params=yes');dialogInstance2.close();LoadAjax('artica-agent-agents-section','$page?agents-start=yes');",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function new_agent_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->_ENGINE_parse_body("{create_a_connection}");
    $tpl->js_dialog2($title, "$page?new-agent-section=true");
}

function new_agent_section(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $form[]=$tpl->field_hidden("aaa_agent_id","0");
    $form[]=$tpl->field_certificate("aaa_agent_name","{certificate}","");
    //$form[]=$tpl->field_text("aaa_agent_name","{name}","",true);
    $form[]=$tpl->field_text("aaa_agent_ip","{ip} / {hostname}","",true);
    $form[]=$tpl->field_numeric("aaa_agent_port","gRPC {port}","8443");
    $form[]=$tpl->field_numeric("aaa_agent_api_port","API {port}","8444");
    $form[]=$tpl->field_numeric("aaa_agent_priority","{priority}","1");
    $form[]=$tpl->field_numeric("aaa_agent_weight","{weight}","1");

    $form[]=$tpl->field_checkbox("aaa_agent_failover","{failover}","0");
    $form[]=$tpl->field_interfaces("aaa_bind_interface","{outgoing_interface}","");


    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{save}",
        "LoadAjaxSilent('artica-agent-agents-section','$page?save-params=yes');dialogInstance2.close();LoadAjax('artica-agent-agents-section','$page?agents-start=yes');",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function int_or_default($value, $default, $min = null, $max = null) {
    $value = (int)$value;

    if ($min !== null && $value < $min) return $default;
    if ($max !== null && $value > $max) return $default;

    return $value ?: $default;
}
function save_agent(){

    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    if (empty($_POST['aaa_agent_name'])) {
        echo "jserror:Agent certificate cannot be empty.";
        return;
    }

    $q = new lib_sqlite("/home/artica/SQLITE/aaa.db");

    // Normalize ID
    $agentId = (int)($_POST['aaa_agent_id'] ?? 0);
    $isNew   = ($agentId <= 0);

    // Validate numeric fields
    $_POST['aaa_agent_port']     = int_or_default($_POST['aaa_agent_port'] ?? 0, 8443, 1, 65535);
    $_POST['aaa_agent_api_port'] = int_or_default($_POST['aaa_agent_api_port'] ?? 0, 8444, 1, 65535);
    $_POST['aaa_agent_priority'] = int_or_default($_POST['aaa_agent_priority'] ?? 0, 1, 1);
    $_POST['aaa_agent_weight']   = int_or_default($_POST['aaa_agent_weight'] ?? 0, 1, 1);
    $_POST['aaa_agent_failover'] = (int)($_POST['aaa_agent_failover'] ?? 0);

    // Escape text fields (IMPORTANT)
    $name = $q->sqlite_escape_string2($_POST['aaa_agent_name']);
    $ip   = sqlite_escape_string2($_POST['aaa_agent_ip']);
    $bindInterface   = sqlite_escape_string2($_POST['aaa_bind_interface']);
    // =============================
    // DUPLICATE CHECK (optimized)
    // =============================

    if ($isNew) {
        $check = $q->QUERY_SQL("SELECT id FROM aaa_agents WHERE aaa_agent_name='$name' OR aaa_agent_ip='$ip'");
    } else {
        $check = $q->QUERY_SQL("SELECT id FROM aaa_agents 
                                WHERE (aaa_agent_name='$name' OR aaa_agent_ip='$ip') 
                                AND id <> $agentId");
    }

    if (!empty($check)) {
        echo "jserror:Agent with this Name or IP already exists.";
        return;
    }

    // =============================
    // CREATE
    // =============================
    if ($isNew) {

        $sql = "INSERT INTO aaa_agents (
                    aaa_agent_name,
                    aaa_agent_ip,
                    aaa_agent_port,
                    aaa_agent_api_port,
                    aaa_agent_priority,
                    aaa_agent_weight,
                    aaa_agent_failover,
                    aaa_bind_interface
                ) VALUES (
                    '$name',
                    '$ip',
                    {$_POST['aaa_agent_port']},
                    {$_POST['aaa_agent_api_port']},
                    {$_POST['aaa_agent_priority']},
                    {$_POST['aaa_agent_weight']},
                    {$_POST['aaa_agent_failover']},
                    '$bindInterface'
                )";

        $q->QUERY_SQL($sql);
    }

    // =============================
    // EDIT
    // =============================
    else {

        $sql = "UPDATE aaa_agents SET
                    aaa_agent_name='$name',
                    aaa_agent_ip='$ip',
                    aaa_agent_port={$_POST['aaa_agent_port']},
                    aaa_agent_api_port={$_POST['aaa_agent_api_port']},
                    aaa_agent_priority={$_POST['aaa_agent_priority']},
                    aaa_agent_weight={$_POST['aaa_agent_weight']},
                    aaa_agent_failover={$_POST['aaa_agent_failover']},
                    aaa_bind_interface='$bindInterface'
                WHERE id=$agentId";

        $q->QUERY_SQL($sql);
    }

    // Compile certificate after save
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/aaa/compile/cert/$name");

}

function delete_agent_js(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $agentID = $_GET["remove-agent-js"];
    $agentName = $_GET["agent-name"];
    $jsrestart = "LoadAjaxSilent('artica-agent-agents-section','$page?agents-start=yes');";
    $tpl->js_confirm_delete("$agentName", "delete-agent-confirm", $agentID.'|'.$agentName, $jsrestart);
}

function delete_agent_popup():bool{
    $tpl = new template_admin();
    // Clean input
    $tpl->CLEAN_POST();
    // Generate unique ID
    $data = explode("|", $_POST["delete-agent-confirm"]);
    $name = $data[1];
    $id = (int)($data[0] ?? 0);
    if ($id <= 0) {
        echo "jserror:Invalid ID";
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $sql = "DELETE FROM aaa_agents WHERE id = $id";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/aaa/delete/cert/$name");
    return true;

}

page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{artica_auth_agent}",
        "fas fa-cogs","{feature_artica_auth_agent_explain}","$page?tabs=yes","artica-authentication-agent","progress-articaauth-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}


function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{main}"] = "$page?table=true";
    $array["{agents}"] = "$page?agents-start=true";
    echo $tpl->tabs_default($array);
    return true;
}

function agents_section():bool{
    $page = currentPageName();
    echo "<div id='artica-agent-agents-section-progress' style='margin-top:10px'></div>";
    echo "<div id='artica-agent-agents-section'></div>";
    echo "<script>LoadAjaxSilent('artica-agent-agents-section','$page?agents-section-js=yes');</script>";
    return true;
}

function td_destinations():bool{
    $ID=$_GET["td-destinations"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $idDiv1="rcolor1-$ID";
    $idDiv4="rcolor4-$ID";
    $label="<span class='label label-danger' style='font-size:18px'>{failed_to_connect}</span>";
    $icocl="$('#ico-sensor-$ID').addClass('text-danger')";
    $status="";
    $backends=extract_backends($ID);

    if($backends["Status"]){
        if($backends["Info"]["healthy"]){
            $icocl="$('#ico-sensor-$ID').addClass('text-navy')";
            $label="<span class='label label-success' style='font-size:18px'>{running}</span>";
            $uptime=seconds_to_human($backends["Info"]["uptime_seconds"]);
            $status="<b>{hostname}:</b> {$backends["Info"]["hostname"]}<br><b>{version}:</b> {$backends["Info"]["version"]}<br><b>{aaa_total_auth_requests}:</b> {$backends["Info"]["total_auth_requests"]}<br><b>{aaa_total_group_requests}:</b> {$backends["Info"]["total_group_requests"]}<br><b>{uptime}:</b> $uptime<br>";
        } else {;
            $status = "<b>{$backends["Error"]}</b>";
        }
    } else{
        $status="<b>{$backends["Error"]}</b>";
    }
    $label=base64_encode($tpl->_ENGINE_parse_body($label));
    $status=base64_encode($tpl->_ENGINE_parse_body($status));
    $f[]="$('#ico-sensor-$ID').removeClass('text-danger');";
    $f[]=$icocl;

    $f[]="if( document.getElementById('$idDiv1') ){";
    $f[]="\ttempdata=base64_decode('$label');";
    $f[]="\tdocument.getElementById('$idDiv1').innerHTML=tempdata;";
    $f[]="}";
    $f[]="if( document.getElementById('$idDiv4') ){";
    $f[]="\ttempdata=base64_decode('$status');";
    $f[]="\tdocument.getElementById('$idDiv4').innerHTML=tempdata;";
    $f[]="}";
    echo @implode("\n",$f);
    return true;
}
function destinations_prepare():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    header("content-type: application/x-javascript");
    $data=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["destinations-prepare"]);
    $t=time();
    $Timeout=1000;
    $f=array();
    foreach ($data as $ID=>$md){
        $Timeout=$Timeout+50;
        $idDiv1="rcolor1-$ID";
        $idDiv4="rcolor4-$ID";

        $pl="<i class=\"fas fa-sync fa-spin\" style='color: #1ab394'></i>&nbsp;{please_wait}...";
        $text=base64_encode($tpl->_ENGINE_parse_body($pl));
        $f[]="function DestinationsPrepare$t$ID(){";
        $f[]="\tif( document.getElementById('$idDiv1') ){";
        $f[]="\t\ttempdata=base64_decode('$text');";
        $f[]="\t\tdocument.getElementById('$idDiv1').innerHTML=tempdata;";
        $f[]="\t\tLoadjs('$page?td-destinations=$ID&function=$function&md=$md');";
        $f[]="\t}";
        $f[]="}\n";
        $f[]="setTimeout(\"DestinationsPrepare$t$ID()\",$Timeout);\n\n";
        $f[]="";
        $pl="<i class=\"fas fa-sync fa-spin\" style='color: #1ab394'></i>&nbsp;{please_wait}...";
        $text=base64_encode($tpl->_ENGINE_parse_body($pl));
        $f[]="function DestinationsPrepare$t$ID(){";
        $f[]="\tif( document.getElementById('$idDiv4') ){";
        $f[]="\t\ttempdata=base64_decode('$text');";
        $f[]="\t\tdocument.getElementById('$idDiv4').innerHTML=tempdata;";
        $f[]="\t\tLoadjs('$page?td-destinations=$ID&function=$function&md=$md');";
        $f[]="\t}";
        $f[]="}\n";
        $f[]="setTimeout(\"DestinationsPrepare$t$ID()\",$Timeout);\n\n";
        $f[]="";
    }


    echo @implode("\n",$f);
    return true;
}

function extract_backends($id): array
{
    $response = $GLOBALS["CLASS_SOCKETS"]->REST_API("/aaa/ping/$id");

    $json = json_decode($response, true); // <-- important

    return is_array($json) ? $json : [];
}
function agents_section_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=True;
    $TRCLASS=null;
    $function=$_GET["function"];



    $html[] = "</div></p>";
    $html[]="<table id='table-aaa-agents' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{aaa_agent}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%' nowrap>{settings}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $agents = $q->QUERY_SQL("SELECT * FROM aaa_agents");
    $cc=ico_sensor;
    foreach ($agents as $index=>$ligne){
        $id=$ligne['ID'];
        $md="AAA$id";
        $DestinationsPrepare[$id]=$md;

        $agent=$ligne['aaa_agent_name'];
        $address=$ligne['aaa_agent_ip'].":".$ligne['aaa_agent_port'];
        $setting="<b>{priority}:</b> ".$ligne['aaa_agent_priority']."<br><b>{weight}</b>: ".$ligne['aaa_agent_weight']."<br><b>{failover}</b>: ".$ligne['aaa_agent_failover'];
        $row1prc=$tpl->table_td1prc();
        $delete=$tpl->icon_delete("Loadjs('$page?remove-agent-js=$id&agent-name=$agent')","AsSystemAdministrator");

        $pleasewait="<i class=\"fas fa-sync fa-spin\" style='width:35%' ></i>&nbsp;{analyze}...</span>";
        $js="Loadjs('$page?edit-agent-js=$id&agent-name=$agent');";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%;font-size:18px' id='rcolor1-$id' nowrap>$pleasewait</td>";
        $html[]="<td style='width:1%;font-size:18px;padding-left: 10px' nowrap><i id='ico-sensor-$id' class='$cc fa-2x'></i></td>";
        $html[]="<td style=''><span style='font-size:20px'>". $tpl->td_href($agent,"{click_to_edit}",$js)."</span>
            <div style='margin-left:33px'><i style='font-size:16px'>$address</i></td>";
        $html[] = "<td id='rcolor4-$id'>$pleasewait</span>";

        $html[] = "<td $row1prc>$setting</span>";
        $html[]="<td $row1prc>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";




    $jsRestart=$tpl->framework_buildjs("/aaa/build",
        "ArticaAuthenticationAgent.progress","ArticaAuthenticationAgent.log",
        "progress-articaauth-restart");

    $users=new usersMenus();
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?new-agent-js=true');",ico_plus, "{create_a_connection}");


    if($users->AsSystemAdministrator) {
        $topbuttons[] = array($jsRestart, ico_ok, "{reconfigure}");
    }

    $topbuttons[] = array("Loadjs('$page?tooltip-js=yes');",ico_support, "{configuration_wizard}");
    $topbuttons[] = array("LoadAjaxSilent('artica-agent-agents-section','$page?agents-start=yes');","fal fa-sync-alt", "{refresh}");

    $TINY_ARRAY["TITLE"]="{artica_auth_agent}";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{feature_artica_auth_agent_explain}";
    $TINY_ARRAY["URL"]="artica-authentication-agent";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]= @implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=sprintf("Loadjs('$page?destinations-prepare=%s&function=$function')",base64_encode(serialize($DestinationsPrepare)));
    $html[]=$jstiny;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function seconds_to_human($seconds) {

    $days    = floor($seconds / 86400);
    $hours   = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs    = $seconds % 60;

    $parts = [];

    if ($days > 0)    $parts[] = $days . "d";
    if ($hours > 0)   $parts[] = $hours . "h";
    if ($minutes > 0) $parts[] = $minutes . "m";
    if ($secs > 0)    $parts[] = $secs . "s";

    return implode(" ", $parts);
}
function table(){
//    $page=CurrentPageName();
//    echo "<div id='artica-auth-general-table'></div><script>LoadAjax('artica-auth-general-table','$page?table1=yes')</script>";
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:400px;vertical-align:top;'>";
    $html[]="<div id='cluster-status' style='width:400px'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='artica-auth-general-table'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $jsRestart=$tpl->framework_buildjs("/aaa/build",
        "ArticaAuthenticationAgent.progress","ArticaAuthenticationAgent.log",
        "progress-articaauth-restart");


    $topbuttons=array();

    if($users->AsSystemAdministrator) {
        $topbuttons[] = array($jsRestart, ico_ok, "{reconfigure}");
    }
    $s_PopUp="Loadjs('$page?tooltip-js=yes');";
    $topbuttons[] = array($s_PopUp,ico_support, "{configuration_wizard}");



    $TINY_ARRAY["TITLE"]="{artica_auth_agent}";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{feature_artica_auth_agent_explain}";
    $TINY_ARRAY["URL"]="artica-authentication-agent";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jsRefresh=$tpl->RefreshInterval_js("cluster-status",$page,"status=yes");
    $html[]="<script>";
    $html[]="LoadAjax('artica-auth-general-table','$page?table1=yes');";
    $html[]=$jstiny;
    $html[]=$jsRefresh;
    $html[]="</script>";
    echo @implode("",$html);
    return true;

}

function table1():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS aaa_settings ( ID INTEGER PRIMARY KEY AUTOINCREMENT,aaa_auth_mode TEXT NULL DEFAULT 'Negotiate', aaa_health_check_mode TEXT NOT NULL DEFAULT 'single', aaa_health_check_enable INTEGER NOT NULL DEFAULT 0, aaa_health_check_interval INTEGER NOT NULL DEFAULT 1, aaa_health_check_timeout INTEGER NOT NULL DEFAULT 1, aaa_health_check_failure_threshold INTEGER NOT NULL DEFAULT 1, aaa_health_check_success_threshold INTEGER NOT NULL DEFAULT 2, aaa_timeout INTEGER NOT NULL DEFAULT 1, aaa_emergency_mode INTEGER NOT NULL DEFAULT 0, aaa_emergency_timeout INTEGER NOT NULL DEFAULT 1, aaa_cache_enable INTEGER NOT NULL DEFAULT 0, aaa_cache_auth_ttl INTEGER NOT NULL DEFAULT 5, aaa_cache_groups_ttl INTEGER NOT NULL DEFAULT 15, aaa_cache_negative_ttl INTEGER NOT NULL DEFAULT 60, aaa_workers INTEGER NOT NULL DEFAULT 32,aaa_log_level TEXT NULL DEFAULT 'info', aaa_fallback_basic_auth INTEGER NOT NULL DEFAULT 0,aaa_max_size_mb INTEGER NOT NULL DEFAULT 100, aaa_max_backups INTEGER NOT NULL DEFAULT 3,aaa_max_age_days INTEGER NOT NULL DEFAULT 30)");

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS aaa_agents ( ID INTEGER PRIMARY KEY AUTOINCREMENT, aaa_agent_name TEXT NULL, aaa_agent_ip TEXT NOT NULL, aaa_agent_port INTEGER NOT NULL, aaa_agent_api_port INTEGER NOT NULL, aaa_agent_priority INTEGER NOT NULL, aaa_agent_weight INTEGER NOT NULL, aaa_agent_failover INTEGER NOT NULL,aaa_bind_interface TEXT)");

    if(!$q->FIELD_EXISTS("aaa_settings","aaa_max_size_mb")){
        $q->QUERY_SQL("ALTER TABLE aaa_settings ADD COLUMN aaa_max_size_mb INTEGER NOT NULL DEFAULT 100");
        if(!$q->ok){
            echo $q->mysql_error;
        }
    }
    if(!$q->FIELD_EXISTS("aaa_settings","aaa_max_backups")){
        $q->QUERY_SQL("ALTER TABLE aaa_settings ADD COLUMN aaa_max_backups INTEGER NOT NULL DEFAULT 3");
        if(!$q->ok){
            echo $q->mysql_error;
        }
    }
    if(!$q->FIELD_EXISTS("aaa_settings","aaa_max_age_days")){
        $q->QUERY_SQL("ALTER TABLE aaa_settings ADD COLUMN aaa_max_age_days INTEGER NOT NULL DEFAULT 30");
        if(!$q->ok){
            echo $q->mysql_error;
        }
    }
    if(!$q->FIELD_EXISTS("aaa_agents","aaa_bind_interface")){
        $q->QUERY_SQL("ALTER TABLE aaa_agents ADD COLUMN aaa_bind_interface TEXT");
        if(!$q->ok){
            echo $q->mysql_error;
        }
    }
    $ligne = $q->mysqli_fetch_array("SELECT * FROM aaa_settings");

    $auth_mode=trim($ligne["aaa_auth_mode"]);
    if($auth_mode==""){$auth_mode="Negotiate";}
    $health_check_mode=trim($ligne["aaa_health_check_mode"]);
    if($health_check_mode==""){$health_check_mode="single";}
    $health_check_enable=intval($ligne["aaa_health_check_enable"]);
    $health_check_interval=intval($ligne["aaa_health_check_interval"])?: 1;
    $health_check_timeout=intval($ligne["aaa_health_check_timeout"])?: 1;
    $health_check_failure_threshold=intval($ligne["aaa_health_check_failure_threshold"])?: 1;
    $health_check_success_threshold=intval($ligne["aaa_health_check_success_threshold"])?: 2;
    $timeout=intval($ligne["aaa_timeout"])?: 1;
    $emergency_mode=intval($ligne["aaa_emergency_mode"]);
    $emergency_timeout=intval($ligne["aaa_emergency_timeout"])?: 1;
    $cache_enable=intval($ligne["aaa_cache_enable"]);
    $cache_auth_ttl=intval($ligne["aaa_cache_auth_ttl"])?: 5;

    $cache_groups_ttl=intval($ligne["aaa_cache_groups_ttl"])?: 15;

    $cache_negative_ttl=intval($ligne["aaa_cache_negative_ttl"])?: 60;

    $workers=intval($ligne["aaa_workers"])?: 32;

    $log_level=trim($ligne["aaa_log_level"]);
    if($log_level==""){$log_level="info";}
    $logLevels=array();
    $logLevels["info"]="info";
    $logLevels["debug"]="debug";
    $logLevels["warn"]="warn";
    $logLevels["error"]="error";
    $health_check_modes=array();
    $health_check_modes["failover"]="failover";
    $health_check_modes["load-balance"]="load-balance";
    $auth_modes=array();
    $auth_modes["Negotiate"]="Negotiate";
    $auth_modes["NTLM"]="NTLM";
    $auth_modes["Basic"]="Basic";

    $aaa_fallback_basic_auth=intval($ligne["aaa_fallback_basic_auth"]);
    $max_size_mb=intval($ligne["max_size_mb"])?: 100;
    $max_backups=intval($ligne["max_backups"])?: 3;
    $max_age_days=intval($ligne["max_age_days"])?: 30;
    //Global Settings
    $tpl->table_form_field_js("Loadjs('$page?global-js=yes')");
    $tpl->table_form_section("{settings}");
    $tpl->table_form_field_text("{authentication} {mode}",$auth_mode,ico_audit);
    $tpl->table_form_field_text("{distribution} {mode}",$health_check_mode,ico_load_balancer);
    $tpl->table_form_field_text("{workers}",$workers,ico_cpu);
    $tpl->table_form_field_text("{timeout}",$timeout,ico_timeout);
    $tpl->table_form_field_bool("{LockActiveDirectoryToKerberosBasic}",$aaa_fallback_basic_auth,ico_user);


    //Health Check Section
    $tpl->table_form_field_js("Loadjs('$page?health-check-js=yes')");
    $tpl->table_form_section("{healthcheck}");
    $tpl->table_form_field_bool("{enable}",$health_check_enable,ico_sensor);

    $tpl->table_form_field_text("{interval}",$health_check_interval,ico_timeout);
    $tpl->table_form_field_text("{timeout}",$health_check_timeout,ico_timeout);
    $tpl->table_form_field_text("{aaa_failure_threshold}",$health_check_failure_threshold,ico_timeout);
    $tpl->table_form_field_text("{aaa_success_threshold}",$health_check_success_threshold,ico_timeout);
    //Emergency Section
    $tpl->table_form_field_js("Loadjs('$page?emergency-js=yes')");
    $tpl->table_form_section("{emergency_mode}");
    $tpl->table_form_field_bool("{enable}",$emergency_mode,ico_emergency);
    $tpl->table_form_field_text("{timeout}",$emergency_timeout,ico_timeout);
    //Cache Section
    $tpl->table_form_field_js("Loadjs('$page?cache-js=yes')");
    $tpl->table_form_section("{cache}");
    $tpl->table_form_field_bool("{enable}",$cache_enable,ico_caching);
    $tpl->table_form_field_text("{aaa_cache_auth_ttl}",$cache_auth_ttl,ico_clock_wait);
    $tpl->table_form_field_text("{aaa_cache_groups_ttl}",$cache_groups_ttl,ico_clock_wait);
    $tpl->table_form_field_text("{aaa_cache_negative_ttl}",$cache_negative_ttl,ico_clock_wait);
    //Log Section
    $tpl->table_form_field_js("Loadjs('$page?logging-js=yes')");
    $tpl->table_form_section("{logging}");
    $tpl->table_form_field_text("{level}",$log_level,ico_logsink);
    $tpl->table_form_field_text("{max_size_mb}",$max_size_mb,ico_logsink);
    $tpl->table_form_field_text("{max_backups}",$max_backups,ico_logsink);
    $tpl->table_form_field_text("{max_age_days}",$max_age_days,ico_logsink);

    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function section_global_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{settings}","$page?section-global-popup=yes");
    return true;
}
function section_global_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM aaa_settings");
    $auth_mode=trim($ligne["aaa_auth_mode"]);
    if($auth_mode==""){$auth_mode="Negotiate";}
    $timeout=intval($ligne["aaa_timeout"]?: 1);
    $workers=intval($ligne["aaa_workers"]);
    if($workers==0){
        $workers=32;
    }
    $auth_modes=array();
    $auth_modes["Negotiate"]="Negotiate";
    $auth_modes["NTLM"]="NTLM";
    $auth_modes["Basic"]="Basic";
    $aaa_fallback_basic_auth=intval($ligne["aaa_fallback_basic_auth"]);
    $health_check_mode=trim($ligne["aaa_health_check_mode"]);
    if($health_check_mode==""){$health_check_mode="single";}
    $health_check_modes=array();
    $health_check_modes["single"]="single";
    $health_check_modes["failover"]="failover";
    $health_check_modes["load-balance"]="load-balance";
    $form[]=$tpl->field_section("{settings}");
    $form[]=$tpl->field_array_select($auth_modes,"aaa_auth_mode","{authentication} {mode}",$auth_modes["$auth_mode"]);
    $form[]=$tpl->field_array_hash($health_check_modes,"aaa_health_check_mode","nonull:{distribution} {mode}",$health_check_modes["$health_check_mode"],false,"{aaa_distribution_desc}");

    $form[]=$tpl->field_numeric("aaa_workers","{workers}",$workers,"{artica_auth_workers}");
    $form[]=$tpl->field_numeric("aaa_timeout","{timeout}",$timeout,"{aaa_timeout}");
    $form[]=$tpl->field_checkbox("aaa_fallback_basic_auth","{LockActiveDirectoryToKerberosBasic}",$aaa_fallback_basic_auth);


    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{save}",
        "LoadAjaxSilent('artica-auth-general-table','$page?save-params=yes');BootstrapDialog1.close();LoadAjax('artica-auth-general-table','$page?table1=yes');",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_healthcheck_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{healthcheck}","$page?section-healthcheck-popup=yes");
    return true;
}
function section_healthcheck_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM aaa_settings");

    $health_check_enable=intval($ligne["aaa_health_check_enable"]);
    $health_check_interval=intval($ligne["aaa_health_check_interval"])?: 1;
    $health_check_timeout=intval($ligne["aaa_health_check_timeout"])?: 1;
    $health_check_failure_threshold=intval($ligne["aaa_health_check_failure_threshold"])?: 1;
    $health_check_success_threshold=intval($ligne["aaa_health_check_success_threshold"])?: 2;

    $form[]=$tpl->field_section("{healthcheck}","{aaa_healthcheck_desc}");
    $form[]=$tpl->field_checkbox("aaa_health_check_enable","{enable}",$health_check_enable,"aaa_health_check_interval,aaa_health_check_timeout,aaa_health_check_failure_threshold,aaa_health_check_success_threshold");

    $form[]=$tpl->field_numeric("aaa_health_check_interval","{interval}",$health_check_interval,"{aaa_health_check_interval_desc}");
    $form[]=$tpl->field_numeric("aaa_health_check_timeout","{timeout}",$health_check_timeout,"{aaa_health_check_timeout_desc}");
    $form[]=$tpl->field_numeric("aaa_health_check_failure_threshold","{aaa_failure_threshold}",$health_check_failure_threshold,"{aaa_failure_threshold_desc}");
    $form[]=$tpl->field_numeric("aaa_health_check_success_threshold","{aaa_success_threshold}",$health_check_success_threshold,"{aaa_failure_success_desc}");


    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{save}",
        "LoadAjaxSilent('artica-auth-general-table','$page?save-params=yes');BootstrapDialog1.close();LoadAjax('artica-auth-general-table','$page?table1=yes');",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_emergency_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{emergency_mode}","$page?section-emergency-popup=yes");
    return true;
}
function section_emergency_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM aaa_settings");
    $emergency_mode=intval($ligne["aaa_emergency_mode"]);
    $emergency_timeout=intval($ligne["aaa_emergency_timeout"])?: 1;
    $form[]=$tpl->field_section("{emergency}","{aaa_emergency_section}");
    $form[]=$tpl->field_checkbox("aaa_emergency_mode","{enable}",$emergency_mode,"aaa_emergency_timeout");

    $form[]=$tpl->field_numeric("aaa_emergency_timeout","{timeout}",$emergency_timeout,"{aaa_emergency_timeout_desc}");

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{save}",
        "LoadAjaxSilent('artica-auth-general-table','$page?save-params=yes');BootstrapDialog1.close();LoadAjax('artica-auth-general-table','$page?table1=yes');",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function section_cache_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{cache}","$page?section-cache-popup=yes");
    return true;
}
function section_cache_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM aaa_settings");
    $cache_enable=intval($ligne["aaa_cache_enable"]);
    $cache_auth_ttl=intval($ligne["aaa_cache_auth_ttl"])?: 5;

    $cache_groups_ttl=intval($ligne["aaa_cache_groups_ttl"])?: 15;

    $cache_negative_ttl=intval($ligne["aaa_cache_negative_ttl"])?: 60;
    $form[]=$tpl->field_section("{cache}","{aaa_cache_section}");
    $form[]=$tpl->field_checkbox("aaa_cache_enable","{enable}",$cache_enable,"aaa_cache_auth_ttl,aaa_cache_groups_ttl,aaa_cache_negative_ttl");
    $form[]=$tpl->field_numeric("aaa_cache_auth_ttl","{aaa_cache_auth_ttl}",$cache_auth_ttl,"{aaa_cache_auth_ttl_desc}");
    $form[]=$tpl->field_numeric("aaa_cache_groups_ttl","{aaa_cache_groups_ttl}",$cache_groups_ttl,"{aaa_cache_groups_ttl_desc}");
    $form[]=$tpl->field_numeric("aaa_cache_negative_ttl","{aaa_cache_negative_ttl}",$cache_negative_ttl,"{aaa_cache_negative_ttl_desc}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{save}",
        "LoadAjaxSilent('artica-auth-general-table','$page?save-params=yes');BootstrapDialog1.close();LoadAjax('artica-auth-general-table','$page?table1=yes');",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function tooltip_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog12("{artica_auth_agent}","$page?tooltip-popup=yes",900);
    return true;
}
function tooltip1_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog12("{artica_auth_agent} {activate_the_feature}","$page?tooltip-popup2=yes",900);
    return true;
}
function tooltip_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $f[]="<p style='font-size:18px;margin-bottom: 35px;border:2px solid #C5C5C5;border-radius: 5px;padding:5px;background-color: #fdfcfc'>{feature_artica_auth_agent_explain}</p>";
    $f[]="<div class='center' style='margin-bottom: 35px'><img src='img/aaa.png'></div>";
    $f[]="<table style='width:100%'>";
    $f[]="<tr>";
    $w=270;
    $btn1=$tpl->button_autnonome("{i_am_not_interested}","Loadjs('fw.icon.top.php?SetToken=HideAAAuthIco');dialogInstance12.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');",ico_exit,null,$w,"btn-danger",$w);
    $btn2=$tpl->button_autnonome("WIKI","s_PopUp('https://wiki.articatech.com/en/proxy-service/authentication/artica-authentication-agent','1024','800')",ico_support,null,$w,"btn-warning",$w);
    $btn3=$tpl->button_autnonome("{activate_the_feature}","Loadjs('$page?tooltip1-js=yes')",ico_check_double,null,$w,"btn-primary",$w);
    $f[]="<td style='width:33%'>$btn1</td>";
    $f[]="<td style='width:33%'>$btn2</td>";
    $f[]="<td style='width:33%'>$btn3</td>";
    $f[]="</tr>";
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
    return true;
}
function tooltip_popup2(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $wikipref="https://wiki.articatech.com/en/proxy-service/authentication";
    $s="18";
    $tr="style='height:100px'";
    $pp=";padding-left:20px";
    $f[]="<p style='font-size:18px;margin-bottom: 35px;border:2px solid #C5C5C5;border-radius: 5px;padding:5px;background-color: #fdfcfc'>{aaa_step1}</p>";
    $f[]="<table style='width:100%'>";
    $f[]="<tr $tr>";
    $f[]="<td style='width:1%' nowrap><i class='fa-solid fa-1 fa-3x'></i></td>";
    $f[]="<td style='width:99%$pp'><p style='font-size:{$s}px'>{download_the_windows_client}</p></td>";
    $btn1=$tpl->button_autnonome("artica-agent-setup.exe","document.location.href='https://artica-ad-agent.b-cdn.net/artica-agent-setup.exe'",ico_download,null,0,"btn-primary",0);
    $f[]="<td style='width:1%'>$btn1</td>";
    $f[]="</tr>";
    $f[]="<tr $tr>";
    $f[]="<td style='width:1%' nowrap><i class='fa-solid fa-2 fa-3x'></i></td>";


    $f[]="<td style='width:99%$pp'><p style='font-size:{$s}px'>{install_the_windows_client}<p style='font-size: 12px;
    margin-top: -11px;
    margin-left: 6px;
    margin-right: 10px;'>(". $tpl->td_href("{aaa_step11}","{see_doc}","s_PopUp('https://wiki.articatech.com/en/proxy-service/authentication/artica-authentication-sa','1024','800')").")</></p></td>";
    $btn1=$tpl->button_autnonome("{see_doc}","s_PopUp('$wikipref/artica-authentication-agent-windows-install','1024','800')",ico_support,null,0,"btn-primary",0);
    $f[]="<td style='width:1%'>$btn1</td>";
    $f[]="</tr>";
    $f[]="<tr $tr>";
    $f[]="<td style='width:1%' nowrap><i class='fa-solid fa-3 fa-3x'></i></td>";
    $f[]="<td style='width:99%$pp'><p style='font-size:{$s}px'>{aaa_step3}</p></td>";
    $uri="$wikipref/artica-authentication-agent-windows-install";
    $btn1=$tpl->button_autnonome("{see_doc}","s_PopUp('$uri','1024','800')",ico_support,null,0,"btn-primary",0);
    $f[]="<td style='width:1%'>$btn1</td>";
    $f[]="</tr>";

    $f[]="<tr $tr>";
    $f[]="<td style='width:1%' nowrap><i class='fa-solid fa-4 fa-3x'></i></td>";
    $f[]="<td style='width:99%$pp'><p style='font-size:{$s}px'>{aaa_step4}</p></td>";
    $btn1=$tpl->button_autnonome("{see_doc}","s_PopUp('$wikipref/artica-authentication-agent-certificate','1024','800')",ico_support,null,0,"btn-primary",0);
    $f[]="<td style='width:1%'>$btn1</td>";
    $f[]="</tr>";

    $jsInstall="Loadjs('$page?tooltip2-js=yes')";

    $f[]="<tr $tr>";
    $f[]="<td style='width:1%' nowrap><i class='fa-solid fa-5 fa-3x'></i></td>";
    $f[]="<td style='width:99%$pp'><p style='font-size:{$s}px'>{aaa_step5}</p></td>";
    $btn1=$tpl->button_autnonome("{launch_install}",$jsInstall,ico_support,null,0,"btn-primary",0);
    $f[]="<td style='width:1%'>$btn1</td>";
    $f[]="</tr>";


    $f[]="<tr $tr>";
    $f[]="<td style='width:1%' nowrap><i class='fa-solid fa-6 fa-3x'></i></td>";
    $f[]="<td style='width:99%$pp'><p style='font-size:{$s}px'>{aaa_step6}</p></td>";
    $btn1=$tpl->button_autnonome("{see_doc}","s_PopUp('$wikipref/artica-authentication-cnx','1024','800')",ico_support,null,0,"btn-primary",0);
    $f[]="<td style='width:1%'>$btn1</td>";
    $f[]="</tr>";
    $f[]="<tr $tr>";


    $btn1=$tpl->button_autnonome("{aaa_step5}",
        $jsInstall, ico_cd,null,300,"btn-primary",300);
    $f[]="<td colspan=3 style='text-align:right'>
    <div id='aaa-progress' style='margin-top:5px;margin-bottom: 5px '></div>
    $btn1</td>";
    $f[]="</table>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $f));

}
function tooltip2_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $js=$tpl->framework_buildjs("/aaa/install",
        "ArticaAuthenticationAgent.progress",
        "ArticaAuthenticationAgent.log","aaa-progress",
        "document.location.href='/artica-authentication-agent'");


    return $tpl->js_confirm_execute("{aaa_step_install}","tooltip2","yes",$js);
}
function tooltip2_exec():bool{
    return admin_tracks("Launch the installation of Artica Active Directory Authentication");
}

function section_logging_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog("{logging}","$page?section-logging-popup=yes");
    return true;
}
function section_logging_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";
    $q=new lib_sqlite("/home/artica/SQLITE/aaa.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM aaa_settings");
    $log_level=trim($ligne["aaa_log_level"]);
    if($log_level==""){$log_level="info";}
    $logLevels=array();
    $logLevels["info"]="info";
    $logLevels["debug"]="debug";
    $logLevels["warn"]="warn";
    $logLevels["error"]="error";
    $max_size_mb=intval($ligne["aaa_max_size_mb"])?: 100;
    $max_backups=intval($ligne["aaa_max_backups"])?: 3;
    $max_age_days=intval($ligne["aaa_max_age_days"])?: 30;
    $form[]=$tpl->field_section("{logging}","{aaa_logging_section}");
    $form[]=$tpl->field_array_hash($logLevels,"aaa_log_level","{level}",$logLevels["$log_level"]);
    $form[]=$tpl->field_numeric("aaa_max_size_mb","{max_size_mb}",$max_size_mb,"{max_size_mb_desc}");
    $form[]=$tpl->field_numeric("aaa_max_backups","{max_backups}",$max_backups,"{max_backups_desc}");
    $form[]=$tpl->field_numeric("aaa_max_age_days","{max_age_days}",$max_age_days,"{max_age_days_desc}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{save}",
        "LoadAjaxSilent('artica-auth-general-table','$page?save-params=yes');BootstrapDialog1.close();LoadAjax('artica-auth-general-table','$page?table1=yes');",$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}


function save(): bool
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();

    $q = new lib_sqlite("/home/artica/SQLITE/aaa.db");

    $allowedColumns = [
        "aaa_auth_mode",
        "aaa_health_check_mode",
        "aaa_health_check_enable",
        "aaa_health_check_interval",
        "aaa_health_check_timeout",
        "aaa_health_check_failure_threshold",
        "aaa_health_check_success_threshold",
        "aaa_timeout",
        "aaa_emergency_mode",
        "aaa_emergency_timeout",
        "aaa_cache_enable",
        "aaa_cache_auth_ttl",
        "aaa_cache_groups_ttl",
        "aaa_cache_negative_ttl",
        "aaa_workers",
        "aaa_log_level",
        "aaa_fallback_basic_auth",
        "aaa_max_size_mb",
        "aaa_max_backups",
        "aaa_max_age_days"
    ];

    $fields = [];
    $values = [];
    $setParts = [];

    foreach ($_POST as $key => $value) {

        if (!in_array($key, $allowedColumns)) {
            continue;
        }

        if (is_numeric($value)) {
            $value = (int)$value;
            $fields[] = $key;
            $values[] = $value;
            $setParts[] = "$key = $value";
        } else {
            $escaped = $q->sqlite_escape_string2($value);
            $fields[] = $key;
            $values[] = "'$escaped'";
            $setParts[] = "$key = '$escaped'";
        }
    }

    // Check if row exists
    $count = $q->QUERY_SQL("SELECT COUNT(*) as ct FROM aaa_settings");

    $total = 0;

// Make sure we got a valid array
    if (is_array($count) && isset($count[0]['ct'])) {
        $total = (int)$count[0]['ct'];
    }

    // INSERT (with posted values)
    if ($total === 0) {

        if (!empty($fields)) {

            $sql = "INSERT INTO aaa_settings (" .
                implode(",", $fields) .
                ") VALUES (" .
                implode(",", $values) .
                ")";

            $q->QUERY_SQL($sql);
            if(!$q->ok){
                echo $tpl->post_error($q->mysql_error);
                return false;
            }
        } else {
            // nothing posted, insert defaults
            $q->QUERY_SQL("INSERT INTO aaa_settings DEFAULT VALUES");
        }

    }
    // UPDATE
    else {

        if (!empty($setParts)) {
            $sql = "UPDATE aaa_settings SET " .
                implode(",", $setParts) .
                " WHERE ID = 1";

            $q->QUERY_SQL($sql);
            if(!$q->ok){
                echo $tpl->post_error($q->mysql_error);
                return false;
            }
        }
    }

    return true;
}
