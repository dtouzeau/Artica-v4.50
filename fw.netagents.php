<?php
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

if(isset($_GET["widget-agents-upgrade"])){agents_to_upgrade();exit;}
if(isset($_GET["widget-agents-version"])){agents_version();exit;}
if(isset($_GET["widget-repos"])){repository_status();exit;}
if(isset($_GET["agent-events-search"])){agent_events_search();exit;}
if(isset($_GET["agent-events-head"])){agent_events_head();exit;}
if(isset($_GET["agent-events-js"])){events_js();exit;}
if(isset($_GET["td-all"])){td_status_all();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["top-meta-status"])){top_meta_status();exit;}
if(isset($_GET["agents-list"])){agents_list();exit;}
if(isset($_GET["agents-table"])){agents_table();exit;}
if(isset($_GET["agent-add-js"])){agent_add_js();exit;}
if(isset($_GET["agent-add-popup"])){agent_add_popup();exit;}
if(isset($_POST["netagent-hostname"])){agent_add_save();exit;}
if(isset($_GET["agent-delete"])){agent_delete();exit;}
if(isset($_GET["agent-ping"])){agent_ping();exit;}
if(isset($_GET["agent-token-js"])){agent_token_js();exit;}
if(isset($_GET["agent-token-popup"])){agent_token_popup();exit;}
if(isset($_POST["netagent-save-token"])){agent_token_save();exit;}
if(isset($_GET["checkall"])){check_all_agents();exit;}
if(isset($_GET["agent-enroll"])){agent_enroll();exit;}
if(isset($_GET["start"])){start();exit;}

page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{network_agents}",
        "fas fa-network-wired","{network_agents_explain}","$page?start=yes",
        "netagents","progress-netagents",false,"table-netagents");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{network_agents}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px'>";
    $html[]="<div>&nbsp;</div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align: top'>";
    $html[]="<div id='top-meta-status'>&nbsp;</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("top-meta-status",$page,"top-meta-status=yes",10);
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function widget_total($json):string{
    $total = isset($json->total) ? intval($json->total) : 0;
    $tpl=new template_admin();
    $button=array();

    if($total==0){
        return $tpl->widget_h("gray", ico_computer, 0, "{total_agents}",$button);
    }

    return $tpl->widget_h("green", ico_computer, $total, "{total_agents}",$button);


}
function widget_online($json):string{
    $online = isset($json->online) ? intval($json->online) : 0;
    $total = isset($json->total) ? intval($json->total) : 0;
    $tpl=new template_admin();
    $page=CurrentPageName();
    $button=array();

    if($total==0) {
        return $tpl->widget_h("gray", ico_computer, "{none}", "{online}", $button);
    }

    if($total>0){
        if($online==0) {
            return $tpl->widget_h("yellow", ico_computer, "{none}", "{online}", $button);
        }
    }
    $rest=$total-$online;
    if($rest==0) {
        return $tpl->widget_h("green", ico_computer, "{all}", "{online}",$button);
    }
    return $tpl->widget_h("yellow", ico_computer, $online, "{online}", $button);
}
function widget_offline($json):string{
    $offline = isset($json->offline) ? intval($json->offline) : 0;
    $total = isset($json->total) ? intval($json->total) : 0;
    $pending = isset($json->pending) ? intval($json->pending) : 0;
    $tpl=new template_admin();
    $page=CurrentPageName();
    $button=array();

    if($total==0) {
        return $tpl->widget_h("grey", ico_computer, "{none}", "{offline}", $button);
    }
    VERBOSE("Offline: $offline Pending: $pending",__LINE__);
    if($offline==0) {
        if($pending>0) {
            return $tpl->widget_h("yellow", ico_computer, $pending, "{pending}",$button);
        }

        return $tpl->widget_h("grey", ico_computer, "{none}", "{offline}", $button);
    }

    if($offline==$total) {
        return $tpl->widget_h("red", ico_computer, "{all}", "{offline}", $button);
    }



    if($pending>0) {
        return $tpl->widget_h("yellow", ico_computer, $pending, "{pending}",$button);
    }
    if($offline>0) {
        return $tpl->widget_h("red", ico_computer, $offline, "{offline}", $button);
    }


    return $tpl->widget_h("grey", ico_computer, "{none}", "{offline}", $button);
}
function top_meta_status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/stats"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    $widget_total=widget_total($json);
    $widget_online=widget_online($json);
    $widget_offline=widget_offline($json);

    $_SESSION["/netagents/packages/stats"]=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/stats"),true);
    $f=array();
    $f[]="<table style='width:100%'>";
    $f[]="<tr>";
    $f[]="<td style='width:33%'>$widget_total</td>";
    $f[]="<td style='width:33%;padding-left:5px'>$widget_online</td>";
    $f[]="<td style='width:33%;padding-left:5px'>$widget_offline</td>";
    $f[]="</tr>";
    $f[]="<tr>";
    $f[]="<td style='width:33%'><canvas id='widget-repos-partitionPieChart'></canvas></td>";
    $f[]="<td style='width:33%;padding-left:5px'><canvas id='widget-repos-partitionNetAgents'></canvas></td>";
    $f[]="<td style='width:33%;padding-left:5px'><canvas id='widget-repos-agents-to-upgrade'></canvas></td>";
    $f[]="</tr>";
    $f[]="<tr>";
    $f[]="<td style='width:33%'><canvas id='widget-repos-partitionDebian'></canvas></td>";
    $f[]="<td style='width:33%;padding-left:5px'></td>";
    $f[]="<td style='width:33%;padding-left:5px'></td>";
    $f[]="</tr>";


    $f[]="</table>";
    $f[]="<script>";
    $f[]="Loadjs('$page?widget-repos=yes');";
    $f[]="Loadjs('$page?widget-agents-version=yes');";
    $f[]="Loadjs('$page?widget-agents-upgrade=yes');";
    $f[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function agents_to_upgrade(){
    $json=$_SESSION["/netagents/packages/stats"];
    $colorz[]="#d90654";
    $colorz[]="#1ab394";
    $colorz[]="#a383d5";
    $colorz[]="#8783d5";
    $colorz[]="#8399d5";
    $colorz[]="#9bc2da";
    $colorz[]="#9bdab5";
    $colorz[]="#bdda9b";
    $colorz[]="#dada9b";
    $colorz[]="#dac59b";
    $colorz[]="#dab09b";
    $colorz[]="#da9b9b";
    $colorz[]="#da9cb1";
    $colorz[]="#da9cc5";
    $colorz[]="#da9cda";
    $colorz[]="#c59cda";
    $colorz[]="#00aa7f";
    $i=0;
    $data=array();
    $labels=array();
    $bgcolors=array();
    foreach ($json["agents_to_upgrade"] as $array) {
        $status=agent_status($array["agent_id"]);
        $Number=$array["count"];
        $hostname=$status["hostname"];

        $i++;
        $bgcolors[]="'".$colorz[$i]."'";
        $labels[]="'$hostname'";
        $data[]="$Number";
        if($i>15){
            break;
        }

    }

    $data_content=@implode(",", $data);
    $bg_contents=@implode(",", $bgcolors);
    $labels_content=@implode(",", $labels);
    echo "var AgentsVersCtx = document.getElementById('widget-repos-agents-to-upgrade').getContext('2d');
    new Chart(AgentsVersCtx, {
        type: 'pie',
        data: {
            labels: [$labels_content],
            datasets: [{
                data: [$data_content],
                backgroundColor: [ $bg_contents ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
            title: {
                    display: true,
                    text: 'Required updates',
                    font: { size: 14 }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var value = context.raw;
                            return context.label + ': ' + value.toFixed(2) + ' packages';
                        }
                    }
                }
            }
        }
    });
";
    return true;

}

function agents_version(){

    $json=$_SESSION["/netagents/packages/stats"];
    $colorz[]="#d90654";
    $colorz[]="#1ab394";
    $colorz[]="#a383d5";
    $colorz[]="#8783d5";
    $colorz[]="#8399d5";
    $colorz[]="#9bc2da";
    $colorz[]="#9bdab5";
    $colorz[]="#bdda9b";
    $colorz[]="#dada9b";
    $colorz[]="#dac59b";
    $colorz[]="#dab09b";
    $colorz[]="#da9b9b";
    $colorz[]="#da9cb1";
    $colorz[]="#da9cc5";
    $colorz[]="#da9cda";
    $colorz[]="#c59cda";
    $colorz[]="#00aa7f";

    $i=0;
    $data=array();
    $labels=array();
    $bgcolors=array();
    foreach ($json["agents_versions"] as $version=>$Number) {
        $i++;
        $bgcolors[]="'".$colorz[$i]."'";
        $labels[]="'$version'";
        $data[]="$Number";

    }


    $data_content=@implode(",", $data);
    $bg_contents=@implode(",", $bgcolors);
    $labels_content=@implode(",", $labels);
    $dataD=array();
    $bgcolorsD=array();
    $labelsD=array();
    $i=0;
    if(isset($json["debian_versions"])) {
        foreach ($json["debian_versions"] as $version => $Number) {
            $i++;
            $bgcolorsD[] = "'" . $colorz[$i] . "'";
            $labelsD[] = "'Debian $version'";
            $dataD[] = "$Number";
        }
    }
    $data_contentD=@implode(",", $dataD);
    $bg_contentsD=@implode(",", $bgcolorsD);
    $labels_contentD=@implode(",", $labelsD);

    echo "var AgentsVersCtx = document.getElementById('widget-repos-partitionNetAgents').getContext('2d');
    new Chart(AgentsVersCtx, {
        type: 'pie',
        data: {
            labels: [$labels_content],
            datasets: [{
                data: [$data_content],
                backgroundColor: [ $bg_contents ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
            title: {
                    display: true,
                    text: 'Agent Versions',
                    font: { size: 14 }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var value = context.raw;
                            return context.label + ': ' + value.toFixed(2) + ' Agents';
                        }
                    }
                }
            }
        }
    });

var DebianVersCtx = document.getElementById('widget-repos-partitionDebian').getContext('2d');
    new Chart(DebianVersCtx, {
        type: 'pie',
        data: {
            labels: [$labels_contentD],
            datasets: [{
                data: [$data_contentD],
                backgroundColor: [ $bg_contentsD ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
            title: {
                    display: true,
                    text: 'Debian Versions',
                    font: { size: 14 }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var value = context.raw;
                            return context.label + ': ' + value.toFixed(1) + ' Version';
                        }
                    }
                }
            }
        }
    });


";
    return true;
}

function repository_status():bool{

    $tpl = new template_admin();
    $page = CurrentPageName();
    $sock = new sockets();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepos/sync/status"));



    $repoPath = isset($json->repo_path) ? $json->repo_path : '/home/artica/artica-repository';
    $repoSizeBytes = isset($json->repo_size_bytes) ? $json->repo_size_bytes : 0;
    $repoSizeHuman = isset($json->repo_size_human) ? $json->repo_size_human : '0 B';
    $repoUsage = isset($json->repo_usage) ? round($json->repo_usage, 2) : 0;

    // Partition info
    $partTotal = isset($json->repo_part->total_bytes) ? $json->repo_part->total_bytes : 0;
    $partUsed = isset($json->repo_part->used_bytes) ? $json->repo_part->used_bytes : 0;
    $partAvail = isset($json->repo_part->available_bytes) ? $json->repo_part->available_bytes : 0;
    $partUsagePct = isset($json->repo_part->usage_percent) ? round($json->repo_part->usage_percent, 2) : 0;
    $partTotalHuman = isset($json->repo_part->total_human) ? $json->repo_part->total_human : '0 B';
    $partUsedHuman = isset($json->repo_part->used_human) ? $json->repo_part->used_human : '0 B';
    $partAvailHuman = isset($json->repo_part->available_human) ? $json->repo_part->available_human : '0 B';

    // Sync status
    $syncRunning = isset($json->running) && $json->running ? 'Yes' : 'No';
    $progress = isset($json->progress) ? $json->progress : 0;

    // Calculate other usage (partition used - repo size)
    $otherUsed = $partUsed - $repoSizeBytes;
    if ($otherUsed < 0) $otherUsed = 0;

    // Convert to GB for chart display (ensure non-negative and proper number format)
    $repoSizeGB = max(0, round($repoSizeBytes / (1024 * 1024 * 1024), 2));
    $otherUsedGB = max(0, round($otherUsed / (1024 * 1024 * 1024), 2));
    $availGB = max(0, round($partAvail / (1024 * 1024 * 1024), 2));
    $repoUsageRemainder = max(0, round(100 - $repoUsage, 2));

    // Format numbers for JavaScript (avoid locale issues)
    $repoSizeGB_js = number_format($repoSizeGB, 2, '.', '');
    $otherUsedGB_js = number_format($otherUsedGB, 2, '.', '');
    $availGB_js = number_format($availGB, 2, '.', '');
    $repoUsage_js = number_format($repoUsage, 2, '.', '');
    $repoUsageRemainder_js = number_format($repoUsageRemainder, 2, '.', '');


    echo "
    if(!document.getElementById('widget-repos-partitionPieChart')){
    alert('widget-repos-partitionPieChart not found');
    }
    var partitionCtx = document.getElementById('widget-repos-partitionPieChart').getContext('2d');
    new Chart(partitionCtx, {
        type: 'pie',
        data: {
            labels: ['Repository',  'Free Space'],
            datasets: [{
                data: [{$repoSizeGB_js}, {$availGB_js}],
                backgroundColor: [
                    '#d90654',  // Green for repository
                    '#1ab394'   // green for free
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
            title: {
                    display: true,
                    text: 'Repository Partition Usage',
                    font: { size: 14 }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var value = context.raw;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': ' + value.toFixed(2) + ' GB (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
";
return true;
}

function agents_list():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $f=array();
    $f[]="<div class='row'>";
    $f[]="  <div class='col-lg-12'>";
    $f[]="    <div class='ibox'>";
    $f[]="      <div class='ibox-title'>";
    $f[]="        <h5>{agents_list}</h5>";
    $f[]="        <div class='ibox-tools'>";
    $f[]="          <button class='btn btn-success btn-sm' OnClick=\"Loadjs('$page?agent-add-js=yes');\"><i class='fas fa-plus'></i> {add}</button>";
    $f[]="        </div>";
    $f[]="      </div>";
    $f[]="      <div class='ibox-content' id='agents-table-container'>";
    $f[]="      </div>";
    $f[]="    </div>";
    $f[]="  </div>";
    $f[]="</div>";
    $f[]="<script>LoadAjax('agents-table-container','$page?agents-table=yes');</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}





function AgenSoft($id){
    if(isset($GLOBALS["AGENTSOFT"][$id])){
        if(!is_object($GLOBALS["AGENTSOFT"][$id])){
            return json_encode(array());
        }
        return $GLOBALS["AGENTSOFT"][$id];
    }
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/softwares/$id"));
    if(!is_object($json)){
        return json_encode(array());
    }
    $GLOBALS["AGENTSOFT"][$id]=$json;
    return $GLOBALS["AGENTSOFT"][$id];
}
function AgentArtica($id):bool{
    $json=AgenSoft($id);
    if(!is_object($json)){
        echo "// AgentArtica -> $id is_object false\n";
        return false;
    }

    if(!property_exists($json,"artica_version") ){
        echo "// AgentArtica -> $id not property_exists\n";
        return false;
    }
    if(strlen($json->artica_version)>3){
        echo "// artica_version -> $id -> $json->artica_version > 3 OK\n";
        return true;
    }
    return false;
}
function AgentArticaVersion($id):string{
    $json=AgenSoft($id);
    if(!property_exists($json,"artica_version")){
        echo "// AgentArticaVersion -> $id not property_exists\n";
        return "";
    }
    if(strlen($json->artica_version)<3){
        echo "// artica_version -> $json->artica_version < 3\n";
        return "";
    }
    $f[]=$json->artica_version;
    if(intval($json->artica_service_pack)>0){
        $f[]="Service Pack $json->artica_service_pack";
    }
    if(strlen($json->artica_hotfix_version)>3){
        $f[]="Hotfix $json->artica_hotfix_version";
    }
    echo "// AgentArticaVersion ".__LINE__." -> ".count($f)."\n";
    return @implode("&nbsp;",$f);
}
function td_type($agentJson):string{
    $tpl=new template_admin();
    $ico="fab fa-linux";
    if(AgentArtica($agentJson->id)){
        $ico="ico ico-artica";
    }
    return "<i class='$ico'></i>";
}



function agent_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{add_agent}","$page?agent-add-popup=yes",550);
}

function agent_add_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsafter="dialogInstance2.close();LoadAjax('agents-table-container','$page?agents-table=yes');";

    $form[]=$tpl->field_text("netagent-hostname","{hostname}","");
    $form[]=$tpl->field_text("netagent-ipaddress","{ipaddr}","");
    $form[]=$tpl->field_numeric("netagent-port","{listen_port}",28811);
    $form[]=$tpl->field_text("netagent-token","{enrollment_token}","");
    $form[]=$tpl->field_text("netagent-description","{description}","");
    echo $tpl->form_outside("{add_agent}",$form,"{enrollment_token_explain}","{add}",$jsafter,"AsSystemAdministrator");
    return true;
}

function agent_add_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $data = array(
        "hostname" => trim($_POST["netagent-hostname"]),
        "ip_address" => trim($_POST["netagent-ipaddress"]),
        "port" => intval($_POST["netagent-port"]),
        "enrollment_token" => trim($_POST["netagent-token"]),
        "description" => trim($_POST["netagent-description"])
    );

    if(empty($data["hostname"]) || empty($data["ip_address"])){
        echo $tpl->post_error("{hostname_and_ip_required}");
        return false;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/add", $data));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg());
        return false;
    }

    if(isset($json->Error) && !empty($json->Error)){
        echo $tpl->post_error("Err.".__LINE__.":".$json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/list"));
    if (json_last_error() > JSON_ERROR_NONE) {
        return false;
    }
    if(!isset($json->agents) || count($json->agents) == 0){
        return true;
    }


    foreach($json->agents as $agent){
        $ip = htmlspecialchars($agent->ip_address);
        $port = intval($agent->port);
        if($ip==$_POST["netagent-ipaddress"] && $port==intval($_POST["netagent-port"])){
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/enroll/$agent->id");
        }
    }
    return true;
}

function agent_delete():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id = intval($_GET["agent-delete"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/delete/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Error) && !empty($json->Error)){
        echo $tpl->div_warning($json->Error);
        return false;
    }

    // Reload the table
    return agents_table();
}



function agent_status($id):array{

    if(isset($GLOBALS["AGENT_STATUS"][$id])){
        return $GLOBALS["AGENT_STATUS"][$id];
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        VERBOSE(json_last_error_msg(),__LINE__);
        return array("hostname"=>"-","uptime"=>0,"cpu"=>0,"mem_pct"=>0);
    }

    if(isset($json->Error) && !empty($json->Error)){
        VERBOSE($json->Error,__LINE__);
        return array("hostname"=>"-","uptime"=>0,"cpu"=>0,"mem_pct"=>0);
    }



    /*object(stdClass)#5 (8) {
        ["hostname"]=> string(9) "appliance"
        ["time"]=> string(30) "2025-12-28T23:05:58.561639299Z"
        ["uptime"]=> string(7) "19h 42m"
        ["cpu_load"]=> object(stdClass)#13 (3) {
            ["load_1"]=> float(0.05)
            ["load_5"]=> float(0.03)
            ["load_15"]=> int(0) }
            ["memory"]=> object(stdClass)#12 (4) {
                ["total_bytes"]=> int(2068910080)
                ["used_bytes"]=> int(325074944)
                ["available_bytes"]=> int(1743835136)
                ["used_percent"]=> float(15.7123766346)
        }
        ["disk"]=> array(4) {
            [0]=> object(stdClass)#11 (6) {
                ["mount_point"]=> string(1) "/"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692)
            }
            [1]=> object(stdClass)#10 (6) {
                ["mount_point"]=> string(21) "/var/lib/debian-agent"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692) }
            [2]=> object(stdClass)#9 (6) {
                ["mount_point"]=> string(21) "/var/log/debian-agent"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692)
            }
            [3]=> object(stdClass)#8 (6) {
                ["mount_point"]=> string(8) "/var/tmp"
                ["device"]=> string(9) "/dev/sda1"
                ["total_bytes"]=> int(15875375104)
                ["used_bytes"]=> int(1109794816)
                ["available_bytes"]=> int(13936840704)
                ["used_percent"]=> float(6.99066830692) }
        } ["network"]=> array(1) {
            [0]=> object(stdClass)#7 (5) {
                ["interface"]=> string(6) "ens192"
                ["bytes_sent"]=> int(2490454)
                ["bytes_recv"]=> int(184754725)
                ["packets_sent"]=> int(27389)
                ["packets_recv"]=> int(599794) }
            }
            ["agent_health"]=> object(stdClass)#6 (5) {
            ["status"]=> string(7) "healthy"
            ["version"]=> string(5) "1.0.0"
            ["start_time"]=> string(30) "2025-12-28T19:04:58.462865872Z"
            ["certificate_count"]=> int(1)
            ["token_count"]=> int(0) }
    }*/

    $hostname = isset($json->hostname) ? htmlspecialchars($json->hostname) : "-";
    $uptime = isset($json->uptime) ? htmlspecialchars($json->uptime) : "-";
    $cpu = isset($json->cpu_load) ? sprintf("%.2f / %.2f / %.2f", $json->cpu_load->load_1, $json->cpu_load->load_5, $json->cpu_load->load_15) : "-";
    $mem_pct = isset($json->memory) ? sprintf("%.1f%%", $json->memory->used_percent) : "-";

    $GLOBALS["AGENT_STATUS"][$id]=array("hostname"=>$hostname,"uptime"=>$uptime,"cpu"=>$cpu,"mem_pct"=>$mem_pct);
    return $GLOBALS["AGENT_STATUS"][$id];


}

function agent_ping():bool{
    $tpl=new template_admin();
    $id = intval($_GET["agent-ping"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/ping/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Status) && $json->Status){
        echo $tpl->div_explain("{agent_online}");
    }else{
        echo $tpl->div_warning("{agent_offline}");
    }
    return true;
}

function agent_token_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id = intval($_GET["agent-token-js"]);
    return $tpl->js_dialog3("{set_token}","$page?agent-token-popup=$id",550);
}



function agent_token_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $id = intval($_POST["netagent-save-token"]);
    $token = trim($_POST["netagent-token-value"]);

    if(empty($token)){
        echo $tpl->post_error("{token_required}");
        return false;
    }

    $data = array(
        "id" => $id,
        "token" => $token
    );

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/settoken", $data));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg());
        return false;
    }

    if(isset($json->Error) && !empty($json->Error)){
        echo $tpl->post_error("Err.".__LINE__.":".$json->Error);
        return false;
    }

    return true;
}

function check_all_agents():bool{
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/checkall"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Status) && $json->Status){
        echo $tpl->div_ok("{check_started}");
    }else{
        echo $tpl->div_warning(isset($json->Error) ? $json->Error : "{unknown_error}");
    }
    return true;
}

function agent_enroll():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id = intval($_GET["agent-enroll"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/enroll/$id"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->div_warning(json_last_error_msg());
        return false;
    }

    if(isset($json->Status) && $json->Status){
        echo $tpl->js_ok("{enrollment_success}");
        echo "<script>setTimeout(function(){LoadAjax('agents-table-container','$page?agents-table=yes');},2000);</script>";
    }else{
        echo $tpl->div_warning("{enrollment_failed}: ".(isset($json->Error) ? $json->Error : "{unknown_error}"));
    }
    return true;
}
