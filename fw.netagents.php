<?php
// Network Agents Management Page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");

if(isset($_GET["agents-upgrade-list-js"])){agents_upgrade_list_js();exit;}
if(isset($_GET["agents-upgrade-list-popup"])){agents_upgrade_list_popup();exit;}
if(isset($_GET["agents-upgrade-list-apt-status"])){agents_upgrade_list_apt_status();exit;}

if(isset($_GET["pie-agents-upgrade"])){pie_agents_to_upgrade();exit;}
if(isset($_GET["widget-agents-version"])){pie_agents_version();exit;}
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
if(isset($_GET["pie-upgrades-total"])){pie_upgrades_total();exit;}
if(isset($_GET["pie-debian-repartition"])){pie_debian_repartition();exit;}
if(isset($_GET["start"])){start();exit;}

page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{network_agents}",
        "fas fa-network-wired","{network_agents_explain}","$page?start=yes",
        "netagents","progress-netagents",false,"table-netagents");

    $html=$tpl->page_header("{artica_meta}",
        ico_ameta,"{network_agents_explain}","$page?start=yes",
        "meta-server","progress-netagents",false,"table-netagents");


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
    $html[]="<table style='width:100%'>";
    $html[]="<td style='width:33%'><canvas id='widget-repos-partitionPieChart'></canvas></td>";
    $html[]="<td style='width:33%;padding-left:5px'><canvas id='widget-repos-partitionNetAgents'></canvas></td>";
    $html[]="<td style='width:33%;padding-left:5px'><canvas id='widget-repos-agents-to-upgrade'></canvas></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'><canvas id='widget-repos-partitionDebian'></canvas></td>";
    $html[]="<td style='width:33%;padding-left:5px'><canvas id='widget-dashboard-updates'></canvas></td>";
    $html[]="<td style='width:33%;padding-left:5px'></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<div style='width:100%' id='stopped-units'>&nbsp;</div>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("top-meta-status",$page,"top-meta-status=yes",10);
    $html[]="Loadjs('$page?widget-repos=yes');";
    $html[]="Loadjs('$page?widget-agents-version=yes');";
    $html[]="Loadjs('$page?pie-debian-repartition=yes');";
    //$html[]="Loadjs('$page?pie-agents-upgrade=yes');"; // not use it.
    $html[]="Loadjs('$page?pie-upgrades-total=yes');";
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
    $f[]="</table>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $f));
    return true;
}
function pie_agents_to_upgrade(){
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
    echo "var _ec = Chart.getChart('widget-repos-agents-to-upgrade'); if(_ec){_ec.destroy();}
    var AgentsVersCtx = document.getElementById('widget-repos-agents-to-upgrade').getContext('2d');
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

function pie_agents_version(){

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
    $versionMap=array();
    foreach ($json["agents_versions"] as $version=>$Number) {
        $i++;
        $bgcolors[]="'".$colorz[$i]."'";
        $labels[]="'$version'";
        $data[]="$Number";
        $versionMap[]="'".addslashes($version)."'";
    }

    $data_content=@implode(",", $data);
    $bg_contents=@implode(",", $bgcolors);
    $labels_content=@implode(",", $labels);
    $versionMap_content=@implode(",", $versionMap);

    echo "var _ec = Chart.getChart('widget-repos-partitionNetAgents'); if(_ec){_ec.destroy();}
    var _agentVerMap=[$versionMap_content];
    var AgentsVersCtx = document.getElementById('widget-repos-partitionNetAgents').getContext('2d');
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
            onHover: function(event, elements) {
                event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
            },
            onClick: function(event, elements) {
                if (elements.length > 0) {
                    var idx = elements[0].index;
                    var ver = _agentVerMap[idx];
                    Loadjs('fw.netagents.list.php?agents-upgrade-js=0&agentver=' + encodeURIComponent(ver));
                }
            },
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
";
    return true;
}

function pie_debian_repartition(){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dashboard/updates"),true);

    if(!is_array($json)){
        echo "// pie_debian_repartition: invalid response\n";
        return;
    }
    $colorz=["#d90654","#1ab394","#a383d5","#8783d5","#8399d5","#9bc2da",
        "#9bdab5","#bdda9b","#dada9b","#dac59b","#dab09b","#da9b9b",
        "#da9cb1","#da9cc5","#da9cda","#c59cda","#00aa7f"];

    $data=array();
    $labels=array();
    $bgcolors=array();
    $i=0;

    if(isset($json["DebianVersions"])){
        //print_r($json["DebianVersions"]);
        foreach($json["DebianVersions"] as $index=>$ligne){
            $version=$ligne["debian_version"];
            $Number=$ligne["count"];
            if($version==0){
                continue;
            }
            $bgcolors[]="'".$colorz[$i]."'";
            $labels[]="'Debian $version'";
            $data[]="$Number";
            $i++;
            if($i>=count($colorz)){$i=0;}
        }
    }

    if(count($data)==0){
        echo "var _el=document.getElementById('widget-repos-partitionDebian');"
            ."if(_el){_el.parentNode.innerHTML='<div style=\"text-align:center;padding:40px;color:#888\">"
            ."<i class=\"fab fa-linux\" style=\"font-size:48px\"></i><br><br>No data</div>';}\n";
        return;
    }

    $data_content=@implode(",",$data);
    $bg_contents=@implode(",",$bgcolors);
    $labels_content=@implode(",",$labels);

    echo "var _ec=Chart.getChart('widget-repos-partitionDebian');if(_ec){_ec.destroy();}
var DebianVersCtx=document.getElementById('widget-repos-partitionDebian').getContext('2d');
new Chart(DebianVersCtx,{
    type:'pie',
    data:{
        labels:[$labels_content],
        datasets:[{
            data:[$data_content],
            backgroundColor:[$bg_contents],
            borderWidth:2,
            borderColor:'#fff'
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        plugins:{
            title:{display:true,text:'Debian Versions',font:{size:14}},
            legend:{position:'bottom',labels:{padding:20,usePointStyle:true}},
            tooltip:{
                callbacks:{
                    label:function(context){
                        var value=context.raw;
                        return context.label+': '+value+' Agents';
                    }
                }
            }
        }
    }
});
";
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
    var _ec = Chart.getChart('widget-repos-partitionPieChart'); if(_ec){_ec.destroy();}
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
    if(!is_object($json) || !property_exists($json,"artica_version")){
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

// ── Dashboard Updates Pie Chart ──────────────────────────────────────────────

function pie_upgrades_total(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dashboard/updates"));
    if(!is_object($json)){
        echo "// agents_upgrade_total: invalid response\n";
        return;
    }
    if(isset($json->Status) && !$json->Status){
        echo "// agents_upgrade_total: error: ".htmlspecialchars($json->Error ?? "unknown")."\n";
        return;
    }
if($GLOBALS["VERBOSE"]){
    print_r($json);
}
    $totalSoftwares=intval($json->TotalSoftwares ?? 0);
    $totalApt=intval($json->TotalAptSoftwares ?? 0);
    $totalSP=intval($json->TotalArticaSP ?? 0);
    $totalHotfix=intval($json->TotalArticaHotfix ?? 0);

    $colorMap=["#d90654","#1ab394","#a383d5","#f8ac59"];
    $catNames=["Softwares","AptSoftwares","ArticaSP","ArticaHotfix"];
    $catLabels=["Softwares","APT Packages","Service Packs","Hotfixes"];
    $catValues=[$totalSoftwares,$totalApt,$totalSP,$totalHotfix];

    $labels=[];
    $data=[];
    $colors=[];
    $indexMap=[];

    for($i=0;$i<4;$i++){
        if($catValues[$i]>0){
            $labels[]="'".$catLabels[$i]."'";
            $data[]=$catValues[$i];
            $colors[]="'".$colorMap[$i]."'";
            $indexMap[]="'".$catNames[$i]."'";
        }
    }

    if(count($data)==0){
        echo "var _el=document.getElementById('widget-dashboard-updates');
if(_el){_el.parentNode.innerHTML='<div style=\"text-align:center;padding:40px;color:#888\">"
."<i class=\"fas fa-check-circle\" style=\"font-size:48px;color:#1ab394\"></i>"
."<br><br>No updates required</div>';}
";
        return;
    }
    $title=$tpl->javascript_parse_text("{Update_tasks_by_topic}");
    $labelsJs=implode(",",$labels);
    $dataJs=implode(",",$data);
    $colorsJs=implode(",",$colors);
    $indexMapJs=implode(",",$indexMap);
    $pageJs=addslashes($page);

    echo "
var _ec=Chart.getChart('widget-dashboard-updates');if(_ec){_ec.destroy();}
var _updCatMap=[$indexMapJs];
var _updCtx=document.getElementById('widget-dashboard-updates').getContext('2d');
new Chart(_updCtx,{
    type:'pie',
    data:{
        labels:[$labelsJs],
        datasets:[{
            data:[$dataJs],
            backgroundColor:[$colorsJs],
            borderWidth:2,
            borderColor:'#fff'
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        onHover:function(event,elements){
            event.native.target.style.cursor=elements.length>0?'pointer':'default';
        },
        onClick:function(event,elements){
            if(elements.length>0){
                var idx=elements[0].index;
                var cat=_updCatMap[idx];
                Loadjs('$pageJs?agents-upgrade-list-js=yes&category='+cat);
            }
        },
        plugins:{
            title:{display:true,text:'$title',font:{size:14}},
            legend:{position:'bottom',labels:{padding:20,usePointStyle:true}},
            tooltip:{
                callbacks:{
                    label:function(context){
                        return context.label+': '+context.raw+' agents';
                    }
                }
            }
        }
    }
});
";
}

// ── Dashboard Updates List Table ─────────────────────────────────────────────
function agents_upgrade_list_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category=trim($_GET["category"] ?? "");

    $titles=[
        "Softwares"=>"{softwares_updates}",
        "AptSoftwares"=>"{apt_packages}",
        "ArticaSP"=>"{service_packs}",
        "ArticaHotfix"=>"{hotfixes}"
    ];
    $title=$titles[$category];
    return $tpl->js_dialog2("{Update_tasks_by_topic}: $title","$page?agents-upgrade-list-popup=yes&category=$category",950);
}
function agents_upgrade_list_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category=trim($_GET["category"] ?? "");

    $validCategories=["Softwares","AptSoftwares","ArticaSP","ArticaHotfix"];
    if(!in_array($category,$validCategories)){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{invalid_category}"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/dashboard/list/updates"));
    if(!is_object($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("Failed to fetch dashboard list"));
        return;
    }
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning(htmlspecialchars($json->Error ?? "Unknown error")));
        return;
    }

    $titles=[
        "Softwares"=>"{softwares_updates}",
        "AptSoftwares"=>"{apt_packages}",
        "ArticaSP"=>"{service_packs}",
        "ArticaHotfix"=>"{hotfixes}"
    ];

    $t=time();
    $tdStyle1="style='width:1%' nowrap";
    $tdStyle1R="style='width:1%;text-align:right' nowrap";
    $TRCLASS=null;
    $colspan=4;
    $icoDeb=ico_debian;
    $icopk=ico_package;
    $filter="true";
    $agentsjs=array();

    switch($category){
        case "Softwares":
            $items=isset($json->Softwares)?$json->Softwares:[];
            $colspan=4;

            $html[]="<table id='table-upd-$t' class='table table-stripped' data-page-size='50'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text'>Debian</th>";
            $html[]="<th data-sortable=true data-type='text'>{updates}</th>";
            $html[]="<th>{details}</th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                $hostname=htmlspecialchars($item->hostname ?? "");
                $dv=intval($item->debian_version ?? 0);
                $cnt=intval($item->updates_count ?? 0);
                $id=intval($item->agent_id ?? 0);
                $details=[];
                if($item->debian_version==0){
                    continue;
                }
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                if(isset($item->updates) && is_array($item->updates)){
                    foreach($item->updates as $u){
                        $app=htmlspecialchars($u->app_code ?? "");
                        $inst=htmlspecialchars($u->installed_version ?? "");
                        $avail=htmlspecialchars($u->available_version ?? "");
                        $appText=$tpl->_ENGINE_parse_body("{{$app}}");
                        $appKey=$app;

                        if($appKey=="APP_SQUID"){
                            $appKey="APP_SQUID,APP_SQUID6";
                        }
                        $appjs=$tpl->td_href($appText,"","Loadjs('fw.netagents.update.software.single.php?id=$id&soft=$appKey&debver=$dv')");
                        $details[]="<i class='$icopk'></i>&nbsp;<span style='font-family:monospace'>$appjs</span>: $inst &rarr; <strong>$avail</strong>";
                    }
                }
                $detailStr=implode("<br>",$details);
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='fas fa-server'></i>&nbsp;$hostname</td>";
                $html[]="<td $tdStyle1><i class='$icoDeb fa-2x'></i>&nbsp;<span style='font-size:18px'>$dv</span></td>";
                $html[]="<td $tdStyle1R><span class='badge' style='background:#d90654;color:#fff;font-size:18px'>$cnt</span></td>";
                $html[]="<td>$detailStr</td>";
                $html[]="</tr>";
            }
            break;

        case "AptSoftwares":

            $items=isset($json->AptSoftwares)?$json->AptSoftwares:[];

            $ico_down=ico_download;
            $button_update_all=$tpl->_ENGINE_parse_body($tpl->button_autnonome("{upgrade_all}",
                "Loadjs('fw.netagents.aptget.upgrade.php?upgrade-all-nodes-js=yes')",ico_list));
            $filter="false";
            $colspan=5;
            $html[]="<div'>$button_update_all</div>";
            $html[]="<table id='table-upd-$t' class='table table-stripped' data-page-size='50'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text' nowrap></th>";
            $html[]="<th data-sortable=true data-type='text'>Debian</th>";
            $html[]="<th data-sortable=true data-type='text' nowrap>{packages}</th>";
            $html[]="<th data-sortable=true data-type='text' nowrap></th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                $hostname=htmlspecialchars($item->hostname ?? "");
                $dv=intval($item->debian_version ?? 0);
                $cnt=intval($item->upgradable_count ?? 0);
                $agent_id=intval($item->agent_id ?? 0);
                if($item->debian_version==0){
                    continue;
                }
                $hostname=$tpl->td_href("$hostname","","Loadjs('fw.netagents.aptget.upgrade.php?id=$agent_id')");
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

                $CountLabel=$tpl->td_href(
                    "<span class='badge' style='background:#1ab394;color:#fff;font-size:18px'>$cnt</span>","",
                    "Loadjs('fw.netagents.aptget.upgrade.php?id=$agent_id')");

                $upgJs="Loadjs('fw.netagents.aptget.upgrade.php?upgrade-all-js=yes&id=$agent_id')";
                $upgrade="<button OnClick=\"$upgJs\" class='btn btn-primary' type='button'><i class='$ico_down'></i>&nbsp;{upgrade}</button>";
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='fas fa-server fa-2x'></i>&nbsp;<span style='font-size:18px'>$hostname</span><span id='pkg-result-$agent_id'></span></td>";
                $html[]="<td $tdStyle1><span id='apt-status-$agent_id'></span></td>";
                $html[]="<td $tdStyle1><i class='$icoDeb fa-2x'></i>&nbsp;&nbsp;<span style='font-size:18px'>$dv</span></td>";
                $html[]="<td $tdStyle1>$CountLabel</td>";
                $html[]="<td $tdStyle1><span id='apt-button-$agent_id'>$upgrade</span></td>";
                $agentsjs[]=$agent_id;
                $html[]="</tr>";
            }

            break;

        case "ArticaSP":
            $items=isset($json->ArticaSP)?$json->ArticaSP:[];
            $colspan=4;
            $html[]="<table id='table-upd-$t' class='table table-stripped' data-page-size='50'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text'>{version}</th>";
            $html[]="<th data-sortable=true data-type='text'>{current} SP</th>";
            $html[]="<th data-sortable=true data-type='number'>{available} SP</th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                $hostname=htmlspecialchars($item->hostname ?? "");
                $ver=htmlspecialchars($item->artica_version ?? "");
                $curSP=htmlspecialchars($item->current_service_pack ?? "0");
                $availSP=intval($item->available_service_pack ?? 0);
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='ico ico-artica'></i>&nbsp;$hostname</td>";
                $html[]="<td $tdStyle1>$ver</td>";
                $html[]="<td $tdStyle1>$curSP</td>";
                $html[]="<td $tdStyle1><span class='badge' style='background:#a383d5;color:#fff'>$availSP</span></td>";
                $html[]="</tr>";
            }

            break;

        case "ArticaHotfix":
            $items=isset($json->ArticaHotfix)?$json->ArticaHotfix:[];
            $colspan=4;
            $html[]="<table id='table-upd-$t' class='footable table table-stripped' data-page-size='50'>";
            $html[]="<thead><tr>";
            $html[]="<th data-sortable=true data-type='text'>{hostname}</th>";
            $html[]="<th data-sortable=true data-type='text'>{version}</th>";
            $html[]="<th data-sortable=true data-type='text'>{current} Hotfix</th>";
            $html[]="<th data-sortable=true data-type='number'>{available} Hotfixes</th>";
            $html[]="</tr></thead><tbody>";
            foreach($items as $item){
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                $hostname=htmlspecialchars($item->hostname ?? "");
                $ver=htmlspecialchars($item->artica_version ?? "");
                $curHF=htmlspecialchars($item->current_hotfix ?? "0");
                $availHF=intval($item->available_hotfix_count ?? 0);
                $html[]="<tr class='$TRCLASS'>";
                $html[]="<td><i class='ico ico-artica'></i>&nbsp;$hostname</td>";
                $html[]="<td $tdStyle1>$ver</td>";
                $html[]="<td $tdStyle1>$curHF</td>";
                $html[]="<td $tdStyle1><span class='badge' style='background:#f8ac59;color:#fff'>$availHF</span></td>";
                $html[]="</tr>";
            }

            break;
    }
    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='$colspan'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    if(count($agentsjs)>0){
        $html[]=$tpl->RefreshInterval_Loadjs("table-upd-$t",$page,"agents-upgrade-list-apt-status=".implode("-",$agentsjs),5);
    }

    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}
function agents_upgrade_list_apt_status(){
    $tpl=new template_admin();
    $agentids=intval($_GET["apt-status"]);
    $ico=ico_refresh_animate;
    $js=array();
    $agents=explode("-",$agentids);
    foreach($agents as $agent_id){
        $agent=new ArticaNetAgents($agent_id);
        $Crustatus=$agent->Status();
        if($Crustatus["aptget"]["apt_running"]){
            $js[]="if( document.getElementById('apt-status-$agent_id') ){";
            $js[]="document.getElementById('apt-status-$agent_id').innerHTML=\"<i class='$ico'></i>\";";
            $js[]="document.getElementById('apt-button-$agent_id').innerHTML=\"\";";
            $js[]="}";
            $tpl->table_form_field_text("{system_upgrade}","{running}",ico_refresh_animate);
            return $tpl;
        }

    }

    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
}

