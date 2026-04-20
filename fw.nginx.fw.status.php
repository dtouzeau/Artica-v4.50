<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
$users=new usersMenus();if(!$users->AsWebStatisticsAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["ModSecurityAction"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["charts"])){render_charts();exit;}

page();

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $MODSECURITY_VERSION="";
    $MODSECURITY_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_VER"));
    if($MODSECURITY_VER<>null){
        $MODSECURITY_VERSION=" v{$MODSECURITY_VER}";
    }
	$html=$tpl->page_header("{WAF_LONG}$MODSECURITY_VERSION","fab fa-free-code-camp",
        "{APP_NGINX_FW_EXPLAIN}","$page?tabs=yes","web-firewall","progress-web-firewall",false,"webf-section");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: Web Application firewall {status}",$html);
		echo $tpl->build_firewall();
		return true;
	}

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $MODSECURITY_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_INSTALLED"));
	$array["{status}"]="$page?status=yes";
    if($MODSECURITY_INSTALLED==1) {
        $array["{parameters}"]="fw.modsecurity.php?parameters-start=yes";
        $array["{default_rules}"]="fw.modsecurity.defrules.php";
        $array["{service_events}"] = "fw.modsecurity.events.php";
        $array["{update_events}"] = "fw.modsecurity.update.php";
    }
	echo $tpl->tabs_default($array);
    return true;
}

function status():bool{
	$page                           = CurrentPageName();
	$tpl                            = new template_admin();
    $refreshAll="Loadjs('fw.progress.php?refresh-menus=yes');";
    $EnableModSecurityIngix         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    $disable_icon                   = "far fa-times-circle";
    $MODSECURITY_INSTALLED      =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_INSTALLED"));
    $EnableNginxFW              = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginxFW"));
    $ModSecurityPatternCount    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternCount"));
    $ModSecurityDisableOWASP    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDisableOWASP"));
    if($MODSECURITY_INSTALLED==0){$EnableModSecurityIngix=0;}

    if($EnableModSecurityIngix==1){
        $uninstall_modsecurity=$tpl->framework_buildjs("nginx:/modsecurity/disable",
            "modsecurity.progress",
            "modsecurity.progress.log",
            "progress-web-firewall","LoadAjaxSilent('webf-section','$page?tabs=yes');$refreshAll");

        $button["name"] = "{uninstall}";
        $button["js"] = $uninstall_modsecurity;
        $button["ico"]="fa fa-trash";

        $MODSECURITY_GLOBAL = $tpl->widget_h("green", "fa fa-thumbs-up", "{enabled}", "{WAF_LONG}",$button);
    }else{

        $install_modsecurity=$tpl->framework_buildjs("nginx:/modsecurity/enable",
            "modsecurity.progress",
            "modsecurity.progress.log",
            "progress-web-firewall","LoadAjaxSilent('webf-section','$page?tabs=yes');$refreshAll");

        $button["name"] = "{install}";
        $button["js"] = $install_modsecurity;
        $button["ico"]="fa-solid fa-compact-disc";

        $MODSECURITY_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{disabled}","{WAF_LONG}",$button);
    }
    if($MODSECURITY_INSTALLED==0){
        $MODSECURITY_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{not_installed}","{WAF_LONG}");
    }

    if($EnableNginxFW==1){
        $FIREWALL_GLOBAL = $tpl->widget_h("green", "fa fa-thumbs-up", "{enabled}", "{firewall_for_web}");
    }else{
        $FIREWALL_GLOBAL=$tpl->widget_h("gray",$disable_icon,"{disabled}","{firewall_for_web}");
    }

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT count(ID) as tcount from service_parameters WHERE zkey='EnableModSecurity' AND zvalue='1'");
    if(!$q->ok){echo $q->mysql_error;}

    $ProtectedSites=intval($ligne["tcount"]);
    $qp=new postgres_sql();
    $modsecurity_events=$qp->COUNT_ROWS("modsecurity_events");

    $jsSchedule=$tpl->RefreshInterval_js("firewall-web-status","fw.nginx.fw.php","fw-status=yes");

	$js[]=$jsSchedule;
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:412px;padding-top:10px;vertical-align: top'>";
	$html[]= $MODSECURITY_GLOBAL;
	$html[]= $FIREWALL_GLOBAL;
    $html[]="<div id='firewall-web-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:75%;vertical-align:top'>";
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='padding-left:10px;padding-top:10px'>";
    if($EnableModSecurityIngix==1) {
        if ($ModSecurityPatternCount > 100) {
            $ModSecurityPatternCount = $tpl->FormatNumber($ModSecurityPatternCount);
            $oldIniArry = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPattern"));
            $ModSecurityPatternVersion=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityPatternVersion"));
            $ModSecurityPatternVersion=str_replace("OWASP_CRS/","",$ModSecurityPatternVersion);
            $xtime = $tpl->time_to_date($oldIniArry["zDate"]);
            $ids_signatures = $tpl->widget_h("green", "fas fa-database",
                $ModSecurityPatternCount."&nbsp;<small style='font-size:11px;color:white'>v$ModSecurityPatternVersion</small>",
                "{signatures} ($xtime)");
        } else {
            $ids_signatures = $tpl->widget_h("red", "fas fa-database", $ModSecurityPatternCount, "{signatures}");
        }

        if ($ModSecurityDisableOWASP == 1) {
            $ids_signatures = $tpl->widget_h("grey", "fas fa-database", "{disabled}", "{signatures}");
        }

        if ($ProtectedSites == 0) {
            $protected_sites = $tpl->widget_h("red", "fas fa-shield-check", 0, "{protected_sites}");
        } else {
            $protected_sites = $tpl->widget_h("green", "fas fa-shield-check", $ProtectedSites, "{protected_sites}");
        }

        if ($modsecurity_events == 0) {
            $modsecurity_evs = $tpl->widget_h("grey", "fas fa-user-secret", 0, "{total}: {EmergingThreats}");
        } else {
            $modsecurity_events = $tpl->FormatNumber($modsecurity_events);
            $modsecurity_evs = $tpl->widget_h("yellow", "fas fa-user-secret", $modsecurity_events, "{total}: {EmergingThreats}");
        }
        $html[] = "<table style='width:100%'>";

        $html[] = "<tr>";
        $html[] = "<td style='width:50%;vertical-align: top;'>$ids_signatures</td>";
        $html[] = "<td style='width:50%;vertical-align: top;padding-left: 10px;'>$protected_sites</td>";
        $html[] = "</tr>";

        $html[] = "<tr><td colspan=2><div id='modsec-charts-zone'></div></td></tr>";

        $js[]="LoadAjax('modsec-charts-zone','$page?charts=yes');";

        $html[]="</table>";
    }
    if($MODSECURITY_INSTALLED==0){
        $js=array();
        $html[]=$tpl->div_warning("{WAF_LONG}: {feature_not_installed}||{extension_missing_upgrade}||wiki:https://wiki.articatech.com/reverse-proxy/update-reverse-proxy");
    }

	$html[]="</div>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>";
    $html[]=@implode("\n",$js);

    $MODSECURITY_VERSION="";
    $MODSECURITY_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MODSECURITY_VER"));
    if($MODSECURITY_VER<>null){$MODSECURITY_VERSION=" v{$MODSECURITY_VER}"; }

    $jsrestart=$tpl->framework_buildjs("nginx:/reverse-proxy/waf/reconfigure",
        "nginx.waf.reconfigure",
        "nginx.waf.reconfigure.log","progress-web-firewall"
    );

    $topbuttons[] = array($jsrestart, ico_retweet, "{reconfigure}");

    $TINY_ARRAY["TITLE"]="{WAF_LONG}$MODSECURITY_VERSION";
    $TINY_ARRAY["ICO"]="fab fa-free-code-camp";
    $TINY_ARRAY["EXPL"]="{APP_NGINX_FW_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";

    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}

function render_charts():bool{
    $tpl=new template_admin();
    $q=new postgres_sql();

    $h=array();
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ════════════════════════════════════════════════════════════════════════════
    // ROW 1: Threats by hour today (line) — full width
    // ════════════════════════════════════════════════════════════════════════════
    $currentday=date("Y-m-d");
    $sql="SELECT to_char(date_trunc('hour', created), 'MM-DD-YYYY HH24') AS hour_formatted,
    COUNT(*) AS event_count FROM modsecurity_audit WHERE created>'$currentday 00:00:00' GROUP BY 1 ORDER BY 1;";
    $xLabels=array();$yData=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $hour_formatted=strtotime($ligne["hour_formatted"].":00:00");
        $xLabels[]=json_encode(date("H",$hour_formatted)."h");
        $yData[]=$ligne["event_count"];
    }
    $titleToday=$tpl->javascript_parse_text("{threats} {by_hour} {today}");
    $threatsT=$tpl->javascript_parse_text("{threats}");

    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-clock'></i>&nbsp; $titleToday</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($xLabels)>0){
        $h[]="<div style='position:relative;height:260px'><canvas id='chart-today'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-today'),{type:'line',";
        $h[]="data:{labels:[".implode(",",$xLabels)."],datasets:[{label:'$threatsT',data:[".implode(",",$yData)."],";
        $h[]="borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.15)',fill:true,tension:0.3,pointRadius:3,pointBackgroundColor:'#1ab394'}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},";
        $h[]="scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{ticks:{font:{size:11}}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";

    // ════════════════════════════════════════════════════════════════════════════
    // ROW 2: 10-minute hit rate last 24h (line) from modsec_counters
    // ════════════════════════════════════════════════════════════════════════════
    $yesterday=date("Y-m-d H:i:s",strtotime("-24 hours"));
    $sql="SELECT to_char(zdate,'HH24:MI') AS lbl, hits FROM modsec_counters WHERE zdate>'$yesterday' ORDER BY zdate;";
    $xC=array();$yC=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $xC[]=json_encode($ligne["lbl"]);
        $yC[]=$ligne["hits"];
    }
    $title24h=$tpl->javascript_parse_text("{threats} {last} 24h (10min)");

    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-wave-square'></i>&nbsp; $title24h</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($xC)>0){
        $h[]="<div style='position:relative;height:220px'><canvas id='chart-10min'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-10min'),{type:'line',";
        $h[]="data:{labels:[".implode(",",$xC)."],datasets:[{label:'$threatsT',data:[".implode(",",$yC)."],";
        $h[]="borderColor:'#1c84c6',backgroundColor:'rgba(28,132,198,0.10)',fill:true,tension:0.2,pointRadius:0,borderWidth:1.5}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},";
        $h[]="scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{ticks:{maxTicksLimit:24,font:{size:10}}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";

    // ════════════════════════════════════════════════════════════════════════════
    // ROW 3: Threats by day (bar chart) from modsec_counters_days
    // ════════════════════════════════════════════════════════════════════════════
    $sql="SELECT to_char(zdate,'MM-DD') AS lbl, hits FROM modsec_counters_days ORDER BY zdate;";
    $xD=array();$yD=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $xD[]=json_encode($ligne["lbl"]);
        $yD[]=$ligne["hits"];
    }
    $titleDays=$tpl->javascript_parse_text("{threats} {by_day}");

    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-chart-bar'></i>&nbsp; $titleDays</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($xD)>0){
        $h[]="<div style='position:relative;height:260px'><canvas id='chart-days'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-days'),{type:'bar',";
        $h[]="data:{labels:[".implode(",",$xD)."],datasets:[{label:'$threatsT',data:[".implode(",",$yD)."],";
        $h[]="backgroundColor:'rgba(26,179,148,0.7)',borderColor:'#1ab394',borderWidth:1}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},";
        $h[]="scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{ticks:{font:{size:11}}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";

    // ════════════════════════════════════════════════════════════════════════════
    // ROW 4: 2-column — Severity doughnut + Top attack types bar
    // ════════════════════════════════════════════════════════════════════════════
    $h[]="<table style='width:100%'><tr>";

    // -- Severity doughnut --
    $sql="SELECT severity, COUNT(*) AS cnt FROM modsecurity_audit GROUP BY severity ORDER BY cnt DESC;";
    $sevLabels=array();$sevData=array();$sevColors=array();
    $SEV_MAP=array(0=>"None",1=>"Notice",2=>"Warning",3=>"Error",4=>"Critical",5=>"Emergency");
    $SEV_COLORS=array(0=>"#97c782",1=>"#1ab394",2=>"#f8ac59",3=>"#ed5565",4=>"#c23616",5=>"#470000");
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $sev=intval($ligne["severity"]);
        $sevLabels[]=json_encode($SEV_MAP[$sev] ?? "Sev-$sev");
        $sevData[]=$ligne["cnt"];
        $sevColors[]="'".(isset($SEV_COLORS[$sev]) ? $SEV_COLORS[$sev] : "#676a6c")."'";
    }
    $titleSev=$tpl->javascript_parse_text("{severity}");

    $h[]="<td style='width:40%;vertical-align:top'>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-exclamation-triangle'></i>&nbsp; $titleSev</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($sevLabels)>0){
        $h[]="<div style='position:relative;height:280px'><canvas id='chart-severity'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-severity'),{type:'doughnut',";
        $h[]="data:{labels:[".implode(",",$sevLabels)."],datasets:[{data:[".implode(",",$sevData)."],";
        $h[]="backgroundColor:[".implode(",",$sevColors)."],borderWidth:2}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:8,boxWidth:12}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";
    $h[]="</td>";

    // -- Top attack types (horizontal bar) --
    $sql="SELECT m.explain, COUNT(*) AS cnt FROM modsecurity_audit a
    JOIN modsecurity_messages m ON a.msgid=m.id
    WHERE m.explain<>'none' GROUP BY m.explain ORDER BY cnt DESC LIMIT 10;";
    $atkLabels=array();$atkData=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $label=$ligne["explain"];
        if(strlen($label)>45){$label=substr($label,0,42)."...";}
        $atkLabels[]=json_encode($label);
        $atkData[]=$ligne["cnt"];
    }
    $titleAtk=$tpl->javascript_parse_text("{top} {threats}");
    $atkHeight=max(280,count($atkData)*32);

    $h[]="<td style='width:60%;vertical-align:top;padding-left:10px'>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-bug'></i>&nbsp; $titleAtk</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($atkLabels)>0){
        $h[]="<div style='position:relative;height:{$atkHeight}px'><canvas id='chart-attacks'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-attacks'),{type:'bar',";
        $h[]="data:{labels:[".implode(",",$atkLabels)."],datasets:[{label:'$threatsT',data:[".implode(",",$atkData)."],";
        $h[]="backgroundColor:'#ed5565'}]},";
        $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
        $h[]="plugins:{legend:{display:false}},";
        $h[]="scales:{x:{beginAtZero:true,ticks:{precision:0}},y:{ticks:{font:{size:11}}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";
    $h[]="</td>";
    $h[]="</tr></table>";

    // ════════════════════════════════════════════════════════════════════════════
    // ROW 5: 2-column — Top sites pie + Top IPs pie
    // ════════════════════════════════════════════════════════════════════════════
    $PIE_COLORS=array("#005447","#006455","#1ab394","#3fb88f","#5fbd8b","#7cc287","#97c782","#b0cc5d","#d4e297","#F6F9D1",
        "#1c84c6","#23c6c8","#f8ac59","#ed5565","#676a6c","#ab7df6","#e74c3c","#3498db","#2ecc71","#9b59b6");

    $h[]="<table style='width:100%'><tr>";

    // -- Top exposed websites (pie) --
    $sql="SELECT serviceid, COUNT(*) AS event_count FROM modsecurity_audit GROUP BY serviceid ORDER BY event_count DESC LIMIT 10;";
    $siteLabels=array();$siteData=array();$siteColors=array();
    $results=$q->QUERY_SQL($sql);
    $i=0;
    while($ligne=@pg_fetch_assoc($results)){
        $servicename=get_servicename(intval($ligne["serviceid"]));
        $siteLabels[]=json_encode($servicename);
        $siteData[]=$ligne["event_count"];
        $siteColors[]="'".$PIE_COLORS[$i % count($PIE_COLORS)]."'";
        $i++;
    }
    $titleSites=$tpl->javascript_parse_text("{top_exposed_websites}");

    $h[]="<td style='width:50%;vertical-align:top'>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-globe'></i>&nbsp; $titleSites</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($siteLabels)>0){
        $h[]="<div style='position:relative;height:300px'><canvas id='chart-sites'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-sites'),{type:'pie',";
        $h[]="data:{labels:[".implode(",",$siteLabels)."],datasets:[{data:[".implode(",",$siteData)."],";
        $h[]="backgroundColor:[".implode(",",$siteColors)."],borderWidth:2}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{padding:6,boxWidth:12,font:{size:11}}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";
    $h[]="</td>";

    // -- Top source IPs (pie) --
    $sql="SELECT ipaddr, COUNT(*) AS event_count FROM modsecurity_audit GROUP BY ipaddr ORDER BY event_count DESC LIMIT 10;";
    $ipLabels=array();$ipData=array();$ipColors=array();
    $results=$q->QUERY_SQL($sql);
    $i=0;
    while($ligne=@pg_fetch_assoc($results)){
        $ipLabels[]=json_encode($ligne["ipaddr"]);
        $ipData[]=$ligne["event_count"];
        $ipColors[]="'".$PIE_COLORS[($i+5) % count($PIE_COLORS)]."'";
        $i++;
    }
    $titleIps=$tpl->javascript_parse_text("{top} {src}");

    $h[]="<td style='width:50%;vertical-align:top;padding-left:10px'>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; $titleIps</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($ipLabels)>0){
        $h[]="<div style='position:relative;height:300px'><canvas id='chart-ips'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-ips'),{type:'pie',";
        $h[]="data:{labels:[".implode(",",$ipLabels)."],datasets:[{data:[".implode(",",$ipData)."],";
        $h[]="backgroundColor:[".implode(",",$ipColors)."],borderWidth:2}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{padding:6,boxWidth:12,font:{size:11}}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";
    $h[]="</td>";
    $h[]="</tr></table>";

    // ════════════════════════════════════════════════════════════════════════════
    // ROW 6: Top rule IDs (horizontal bar) — full width
    // ════════════════════════════════════════════════════════════════════════════
    $sql="SELECT ruleid, COUNT(*) AS cnt FROM modsecurity_audit GROUP BY ruleid ORDER BY cnt DESC LIMIT 15;";
    $ruleLabels=array();$ruleData=array();
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $ruleLabels[]=json_encode("Rule ".$ligne["ruleid"]);
        $ruleData[]=$ligne["cnt"];
    }
    $titleRules=$tpl->javascript_parse_text("{top} {rules}");
    $ruleHeight=max(280,count($ruleData)*28);

    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-list-ol'></i>&nbsp; $titleRules</h5></div>";
    $h[]="<div class='ibox-content'>";
    if(count($ruleLabels)>0){
        $h[]="<div style='position:relative;height:{$ruleHeight}px'><canvas id='chart-rules'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('chart-rules'),{type:'bar',";
        $h[]="data:{labels:[".implode(",",$ruleLabels)."],datasets:[{label:'$threatsT',data:[".implode(",",$ruleData)."],";
        $h[]="backgroundColor:'#1c84c6'}]},";
        $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
        $h[]="plugins:{legend:{display:false}},";
        $h[]="scales:{x:{beginAtZero:true,ticks:{precision:0}},y:{ticks:{font:{size:11}}}}}});";
        $h[]="</script>";
    }else{
        $h[]=$tpl->div_info("{no_data}");
    }
    $h[]="</div></div>";

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}

function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
