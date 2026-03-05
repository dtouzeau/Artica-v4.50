<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["honeypot-top"])){honeypot_top();exit;}
if(isset($_GET["honeypot-center"])){honeypot_center();exit;}
if(isset($_GET["tiny"])){tiny();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["disable-js"])){disable_js();exit;}
if(isset($_GET["delete-port"])){delete_port_js();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_POST["port"])){port_save();exit;}
if(isset($_GET["stats-js"])){honeypot_stats_js();exit;}
if(isset($_GET["stats-popup"])){honeypot_stats();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["chart-data"])){honeypot_chart_data();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;


    $html=$tpl->page_header("Honeypot","fa-regular fa-honey-pot",
        "{decisionIPHoneypotAbout}","$page?table-start=yes",
        "decisionip-honeypot","progress-honeypot-restart",false);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("DecisionIP/Honeypot",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function honeypot_top():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<script>LoadAjaxSilent('decision-ip-honeypot-status','$page?service-status=yes');</script>";

    $isKey=isKeysValid();
    if(!$isKey){
        $widget_enable = $tpl->widget_style1("gray-bg", "fa-solid fa-x",
            "{register}", "{none}", "");
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='width:33%'>$widget_enable</td>";
        $html[]="<td style='width:33%'>&nbsp;</td>";
        $html[]="<td style='width:33%'>&nbsp;</td>";
        $html[]="</tr>";
        $html[]="</table>";

        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $array=getConfig();
   if(!isset($array["success"])){
       $widget_enable = $tpl->widget_style1("bg-red", ico_bug,
           "{protocol_error}", "{error}", "");
       $html[]="<table style='width:100%'>";
       $html[]="<tr>";
       $html[]="<td style='width:33%'>$widget_enable</td>";
       $html[]="<td style='width:33%'>&nbsp;</td>";
       $html[]="<td style='width:33%'>&nbsp;</td>";
       $html[]="</tr>";
       $html[]="</table>";
       echo $tpl->_ENGINE_parse_body($html);
       return true;

   }

   $honeypot_enabled=$array["data"]["sections"]["honeypot"]["honeypot_enabled"];

   $page=CurrentPageName();
   if($honeypot_enabled=="false"){
        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{enable_feature}";
        $btn[0]["icon"] = ico_check;
        $btn[0]["js"] = "Loadjs('$page?enable-js=yes')";

        $widget_enable = $tpl->widget_style1("gray-bg", ico_disabled,
            "HoneyPot", "{inactive2}", $btn);
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='width:33%'>$widget_enable</td>";
        $html[]="<td style='width:33%'>&nbsp;</td>";
        $html[]="<td style='width:33%'>&nbsp;</td>";
        $html[]="</tr>";
        $html[]="</table>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }




    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/honeypot/stats"),true);
    $attempts=0;
    foreach ($json["ports"] as $port){
        if(isset($port["ips"])){
            foreach ($port["ips"] as $ip){
                $attempts=$attempts+intval($ip["attempts"]);
            }
        }
    }
    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{disable_feature}";
    $btn[0]["icon"] = ico_trash;
    $btn[0]["js"] = "Loadjs('$page?disable-js=yes')";
    $widget_enable = $tpl->widget_style1("green-bg", ico_check,
        "HoneyPot", "{active2}", $btn);

    $ports=$array["data"]["sections"]["ports"]["port"];
    $portNum=$tpl->widget_style1("green-bg", ico_nic,
        "{ports}", count($ports), "");

    $attemptsB=$tpl->widget_style1("gray-bg", "fa-solid fa-shield-exclamation",
        "{threats}", 0, "");

    if($attempts>0){
        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{statistics}";
        $btn[0]["icon"] = ico_statistics;
        $btn[0]["js"] = "Loadjs('$page?stats-js=yes')";

        $attemptsB=$tpl->widget_style1("yellow-bg", "fa-solid fa-shield-exclamation",
            "{threats}", $tpl->FormatNumber($attempts), $btn);
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_enable</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$portNum</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$attemptsB</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function honeypot_stats_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{statistics}","$page?stats-popup=yes",1024);
}
function port_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{new_port}","$page?port-popup=yes",650);
}
function port_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $portdef=rand(1024,65535);
    $form[]=$tpl->field_numeric("port","{listen_port}",$portdef,"");
    $form[]=$tpl->field_text("desc","{description}","",true);
    echo $tpl->form_outside("",$form,"","{add}","dialogInstance2.close();LoadAjax('honeypot-center','$page?honeypot-center=yes');");
    return true;
}
function port_save():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $port=intval($_POST['port']);
    $desc=trim($_POST['desc']);
    if($port < 1 || $port > 65535){return false;}
    if(strlen($desc) == 0){return false;}
    $data=array("port"=>$port,"name"=>$desc);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/decisionip/honeypot/ports",$data));
    return admin_tracks("Add the Honeypot port $port ($desc) in DecisionIP feature");
}
function delete_port_js():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $port=intval($_GET["delete-port"]);
    if($port < 1 || $port > 65535){return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/decisionip/honeypot/ports/$port");
    echo "LoadAjax('honeypot-center','$page?honeypot-center=yes');";
    return admin_tracks("Remove the Honeypot port $port in DecisionIP feature");
}
function enable_js():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/honeypot/enable");
    echo "LoadAjax('honeypot-center','$page?honeypot-center=yes');";
    return admin_tracks("Enable the Honeypot in DecisionIP feature");
}
function disable_js():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/honeypot/disable");
    echo "LoadAjax('honeypot-center','$page?honeypot-center=yes');";
    return admin_tracks("Disable the Honeypot in DecisionIP feature");
}
function tiny(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $isKey=isKeysValid();
    $topbuttons=array();
    if($isKey){
        $topbuttons[] = array("Loadjs('$page?port-js=yes')", ico_plus, "{new_port}");
    }


    //
    $TINY_ARRAY["TITLE"]="HoneyPot";
    $TINY_ARRAY["ICO"]="fa-regular fa-honey-pot";
    $TINY_ARRAY["EXPL"]="{decisionIPHoneypotAbout}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";
}
function isKeysValid():bool{
    $json=getConfig();

    if(!isset($json["data"]["sections"])){
        return false;
    }
    $user_key=$json["data"]["sections"]["auth"]["user_key"];
    if(strlen($user_key)<5){
        return false;
    }
    $user_key=$json["data"]["sections"]["auth"]["server_key"];
    if(strlen($user_key)<5){
        return false;
    }
    return true;
}
function getConfig():array{

    if(isset($GLOBALS["DECSIONIP"])){
        return $GLOBALS["DECIONIP"];
    }
    $GLOBALS["DECIONIP"]=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/config"),true);
    return $GLOBALS["DECIONIP"];
}
function table_start():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:330px;vertical-align: top'><div id='decision-ip-honeypot-status' style='width:330px'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>
    <div id='honeypot-top' style='margin-top:-7px'></div>
    <div id='honeypot-center'></div>
    <div id='honeypot-db' style='margin-top:10px'></div>
    </td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("honeypot-top",$page,"honeypot-top=yes");
    $html[]="Loadjs('$page?tiny=yes');";
    $html[]="LoadAjax('honeypot-center','$page?honeypot-center=yes');";
    $html[]=$js;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function honeypot_center(){
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $array=getConfig();
    $honeypot_enabled=intval($array["data"]["sections"]["honeypot"]["honeypot_enabled"]);
    $ports=$array["data"]["sections"]["ports"]["port"];

    $TRCLASS=null;
    $html[]="<table id='table-honeypot-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{listen_port}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:right'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'></center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $td1prc="style='width:1%;' nowrap";
foreach ($ports as $port){
        $text_class="";
        if(!$honeypot_enabled){
            $text_class="text-muted";
        }

        if(!preg_match("#^([0-9]+)\s+(.+)$#",$port,$matches)){
            continue;
        }
        $port=$matches[1];
        $desc=$matches[2];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}


        $delete_port=$tpl->icon_delete("Loadjs('$page?delete-port=$port');");
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $td1prc class=\"$text_class\"><span style='font-size: 18px'>$port</td>";
        $html[]="<td class=\"$text_class\" >$desc</td>";
        $html[]="<td style='width: 1%' nowrap>$delete_port</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-honeypot-rules').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$html));

    return true;
}
function honeypot_chart_data(){
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/honeypot/stats");
    if(empty($data)){
        echo json_encode(array("Status"=>false,"Error"=>"Unable to fetch honeypot statistics"));
        return;
    }
    $json=json_decode($data,true);
    if(!is_array($json)){
        echo json_encode(array("Status"=>false,"Error"=>"Invalid JSON response"));
        return;
    }
    if(isset($json["Status"]) && !$json["Status"]){
        echo json_encode(array("Status"=>false,"Error"=>$json["Error"] ?? "Unknown error"));
        return;
    }
    $ports=$json["ports"] ?? array();
    $totalAttempts=0;
    $uniqueIPs=array();
    $activePorts=0;
    foreach($ports as $portData){
        $ips=$portData["ips"] ?? array();
        if(count($ips)>0){$activePorts++;}
        foreach($ips as $ipData){
            $totalAttempts+=intval($ipData["attempts"]);
            $uniqueIPs[$ipData["ip"]]=true;
        }
    }
    echo json_encode(array(
        "Status"=>true,
        "Data"=>array(
            "window_seconds"=>$json["window_seconds"] ?? 600,
            "generated_at"=>$json["generated_at"] ?? "",
            "ports"=>$ports,
            "total_attempts"=>$totalAttempts,
            "unique_ips"=>count($uniqueIPs),
            "active_ports"=>$activePorts
        )
    ));
}
function honeypot_stats(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]='<style>
        .honeypot-dashboard { padding: 15px; }
        .hp-stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px; margin-bottom: 20px;
        }
        .hp-stat-card {
            background-color: #1ab394; border-radius: 5px;
            padding: 15px 20px; margin-bottom: 10px; margin-top: 10px;
            text-align: center; color: #fff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .hp-stat-value {
            font-family: "lato", "Trebuchet MS", "Helvetica", sans-serif;
            font-size: 30px; font-weight: 600; margin-bottom: 5px;
        }
        .hp-stat-label { font-size: 14px; opacity: 2.9; letter-spacing: 1px; }
        .hp-charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px; margin-bottom: 20px;
        }
        .hp-chart-box {
            background: #fff; border-radius: 10px;
            padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hp-chart-box.full-width { grid-column: 1 / -1; }
        .hp-chart-title {
            font-size: 1.1rem; font-weight: 600; margin-bottom: 15px;
            color: #333; border-left: 4px solid #e74c3c; padding-left: 10px;
        }
        .hp-table { width: 100%; border-collapse: collapse; }
        .hp-table th, .hp-table td {
            padding: 10px; text-align: left; border-bottom: 1px solid #eee;
        }
        .hp-table th {
            background: #f8f9fa; font-weight: 600;
            color: #e74c3c; text-transform: uppercase; font-size: 0.8rem;
        }
        .hp-table tr:hover { background: #f8f9fa; }
        .hp-ip-badge {
            background: #e8eaf6; padding: 4px 8px;
            border-radius: 4px; font-family: monospace; font-size: 0.9rem;
        }
        .hp-port-badge {
            background: #e74c3c; color: #fff; padding: 4px 10px;
            border-radius: 4px; font-weight: 600;
        }
        .hp-count-badge {
            background: #f39c12; color: #fff; padding: 4px 10px;
            border-radius: 4px; font-weight: 600;
        }
        .hp-loading { text-align: center; padding: 40px; color: #999; }
        .hp-refresh-status {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 15px; padding: 8px 15px;
            background: #fce4ec; border-radius: 20px;
            width: fit-content; font-size: 0.85rem; color: #c62828;
        }
        .hp-refresh-dot {
            width: 8px; height: 8px; background: #e74c3c;
            border-radius: 50%; animation: hpPulse 1s infinite;
        }
        @keyframes hpPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    </style>';

    $html[]='<div class="honeypot-dashboard">';

    $html[]=$tpl->_ENGINE_parse_body('<div class="hp-refresh-status">
        <div class="hp-refresh-dot"></div>
        <span>{live_updates} - {refresh_every} 10 {seconds}</span>
    </div>');

    $html[]=$tpl->_ENGINE_parse_body('<div class="hp-stats-summary">
        <div class="hp-stat-card">
            <div class="hp-stat-value" id="hpTotalAttempts">0</div>
            <div class="hp-stat-label">{total_attempts}</div>
        </div>
        <div class="hp-stat-card">
            <div class="hp-stat-value" id="hpUniqueIPs">0</div>
            <div class="hp-stat-label">{unique_ips}</div>
        </div>
        <div class="hp-stat-card">
            <div class="hp-stat-value" id="hpActivePorts">0</div>
            <div class="hp-stat-label">{active_ports}</div>
        </div>
    </div>');

    $html[]=$tpl->_ENGINE_parse_body('<div class="hp-charts-row">
        <div class="hp-chart-box">
            <div class="hp-chart-title">{attempts_by_port}</div>
            <div id="hpPortBarChart"></div>
        </div>
        <div class="hp-chart-box">
            <div class="hp-chart-title">{top_attackers}</div>
            <div id="hpIPBarChart"></div>
        </div>
    </div>');

    $html[]=$tpl->_ENGINE_parse_body('<div class="hp-charts-row">
        <div class="hp-chart-box full-width">
            <div class="hp-chart-title">{details}</div>
            <div id="hpDetailsContainer">
                <div class="hp-loading">{loading}...</div>
            </div>
        </div>
    </div>');

    $html[]='</div>';

    $nodata=$tpl->_ENGINE_parse_body("{no_data}");
    $lport=$tpl->_ENGINE_parse_body("{listen_port}");
    $lipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
    $lattempts=$tpl->_ENGINE_parse_body("{attempts}");

    $html[]='<script>
(function() {
    if (window.HoneypotStats) {
        if (window.HoneypotStats.interval) clearInterval(window.HoneypotStats.interval);
        if (window.HoneypotStats.portChart) window.HoneypotStats.portChart.destroy();
        if (window.HoneypotStats.ipChart) window.HoneypotStats.ipChart.destroy();
    }
    window.HoneypotStats = { portChart: null, ipChart: null, interval: null };
    var HP = window.HoneypotStats;

    function initCharts() {
        var portEl = document.querySelector("#hpPortBarChart");
        var ipEl = document.querySelector("#hpIPBarChart");
        if (!portEl || !ipEl) return false;

        HP.portChart = new ApexCharts(portEl, {
            chart: { type: "bar", height: 300, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true } },
            series: [{ name: "Attempts", data: [] }],
            xaxis: { categories: [] },
            colors: ["#e74c3c","#c0392b","#f39c12","#e67e22","#d35400","#e74c3c","#c0392b","#f39c12"],
            legend: { show: false },
            dataLabels: { enabled: true }
        });
        HP.portChart.render();

        HP.ipChart = new ApexCharts(ipEl, {
            chart: { type: "bar", height: 300, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true } },
            series: [{ name: "Attempts", data: [] }],
            xaxis: { categories: [] },
            colors: ["#8e44ad","#9b59b6","#2c3e50","#34495e","#7f8c8d","#95a5a6","#2980b9","#3498db"],
            legend: { show: false },
            dataLabels: { enabled: true }
        });
        HP.ipChart.render();
        return true;
    }

    function updateCharts(data) {
        if (!data) return;
        var el;
        el = document.getElementById("hpTotalAttempts");
        if (el) el.textContent = fmtNum(data.total_attempts || 0);
        el = document.getElementById("hpUniqueIPs");
        if (el) el.textContent = fmtNum(data.unique_ips || 0);
        el = document.getElementById("hpActivePorts");
        if (el) el.textContent = fmtNum(data.active_ports || 0);

        var ports = data.ports || [];

        var portTotals = [];
        ports.forEach(function(p) {
            var total = 0;
            (p.ips || []).forEach(function(ip) { total += ip.attempts || 0; });
            if (total > 0) portTotals.push({ port: p.port, total: total });
        });
        portTotals.sort(function(a, b) { return b.total - a.total; });
        var topPorts = portTotals.slice(0, 15);

        if (HP.portChart && topPorts.length > 0) {
            HP.portChart.updateOptions({
                xaxis: { categories: topPorts.map(function(p) { return "Port " + p.port; }) }
            });
            HP.portChart.updateSeries([{
                name: "Attempts",
                data: topPorts.map(function(p) { return p.total; })
            }]);
        }

        var ipMap = {};
        ports.forEach(function(p) {
            (p.ips || []).forEach(function(ipData) {
                if (!ipMap[ipData.ip]) ipMap[ipData.ip] = 0;
                ipMap[ipData.ip] += ipData.attempts || 0;
            });
        });
        var ipList = [];
        for (var ip in ipMap) {
            if (ipMap.hasOwnProperty(ip)) ipList.push({ ip: ip, total: ipMap[ip] });
        }
        ipList.sort(function(a, b) { return b.total - a.total; });
        var topIPs = ipList.slice(0, 15);

        if (HP.ipChart && topIPs.length > 0) {
            HP.ipChart.updateOptions({
                xaxis: { categories: topIPs.map(function(i) { return i.ip; }) }
            });
            HP.ipChart.updateSeries([{
                name: "Attempts",
                data: topIPs.map(function(i) { return i.total; })
            }]);
        }

        updateDetailsTable(ports);
    }

    function updateDetailsTable(ports) {
        var container = document.getElementById("hpDetailsContainer");
        if (!container) return;

        var rows = [];
        ports.forEach(function(p) {
            (p.ips || []).forEach(function(ipData) {
                rows.push({ port: p.port, ip: ipData.ip, attempts: ipData.attempts || 0 });
            });
        });
        rows.sort(function(a, b) { return b.attempts - a.attempts; });

        if (rows.length === 0) {
            container.innerHTML = "<div class=\"hp-loading\">'.$nodata.'</div>";
            return;
        }

        var html = "<table class=\"hp-table\">";
        html += "<thead><tr><th>#</th><th>'.$lport.'</th>";
        html += "<th>'.$lipaddr.'</th>";
        html += "<th>'.$lattempts.'</th></tr></thead><tbody>";
        rows.forEach(function(r, i) {
            html += "<tr><td>" + (i + 1) + "</td>";
            html += "<td><span class=\"hp-port-badge\">" + r.port + "</span></td>";
            html += "<td><span class=\"hp-ip-badge\">" + escHtml(r.ip) + "</span></td>";
            html += "<td><span class=\"hp-count-badge\">" + fmtNum(r.attempts) + "</span></td></tr>";
        });
        html += "</tbody></table>";
        container.innerHTML = html;
    }

    function fmtNum(n) {
        if (n >= 1000000) return (n / 1000000).toFixed(1) + "M";
        if (n >= 1000) return (n / 1000).toFixed(1) + "K";
        return n.toString();
    }
    function escHtml(t) {
        var d = document.createElement("div");
        d.appendChild(document.createTextNode(t));
        return d.innerHTML;
    }

    function fetchData() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "' . $page . '?chart-data=yes", true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var result = JSON.parse(xhr.responseText);
                    if (result.Status && result.Data) {
                        updateCharts(result.Data);
                    }
                } catch (e) {
                    console.error("Honeypot: Error parsing data", e);
                }
            }
        };
        xhr.send();
    }

    function init() {
        if (typeof ApexCharts === "undefined") {
            console.error("Honeypot: ApexCharts not loaded");
            return;
        }
        if (initCharts()) {
            fetchData();
            HP.interval = setInterval(fetchData, 10000);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        setTimeout(init, 100);
    }
})();
</script>';

    echo $tpl->_ENGINE_parse_body($html);
}
function service_status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/status"),);

    if(!isset($json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,"{protocol_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,$json->Error,"{error}"));
        return true;
    }
    $restart=$tpl->framework_buildjs("/decisionipapps/restart",
        "decisionip.restart.progress",
        "decisionip.restart.progress.log",
        "progress-honeypot-restart");
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DECISIONIP",$restart);

    $CurDB=CurrentIPset();
    $html[]="<div style='border:1px solid #1ab394;border-radius:5px;padding:5px'>";
    $html[]="<table style='width:100%'>";
    foreach ($CurDB as $db=>$Number){
        $color="#1ab394";
        $html[]="<tr>";
        $tmp="&nbsp;";;
        $html[]="<td style='width:1%;padding-top:5px'>";
        if($Number==0){
            $color="#cccccc";
        }
        if($db=="activeattacks-tmp"){
            continue;
        }
        $Number=$tpl->FormatNumber($Number);
        $html[]=$tpl->Round(32,$color);
        $html[]="</td>";
        $html[]="<td style='width:100%;padding-left:8px;padding-top:5px'><strong>{didb_{$db}}$tmp {$Number} {items}</strong></td>";
        $html[]="</tr>";
    }
    $html[]="</table>";
    $html[]="</div>";


    echo $tpl->_ENGINE_parse_body($html);


    return true;
}
function CurrentIPset():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/ipsets"),true);
    $databases=array();
    foreach ($json as $ipset){
        if(!$ipset["enabled"]){
            continue;
        }
        $databases[$ipset["database"]]=$ipset["entries"];
    }
    return $databases;
}