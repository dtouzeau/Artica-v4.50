<?php
// Failover (keepalived) statistics of one VRRP instance, shared by
// fw.keepalived.instances.php (primary) and fw.keepalived.slave.status.php
// (secondary). The history comes from articarest (/keepalived/stats/history),
// which records the VRRP counters and state every minute; the current
// counters from /keepalived/stats. Graphs as in fw.haproxy.backends.php.
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
$users = new usersMenus();
if (!$users->AsVPNManager) {
    exit();
}
if (isset($_GET["stats-js"])) {
    stats_js();
    exit;
}
if (isset($_GET["stats-popup"])) {
    stats_popup();
    exit;
}
if (isset($_GET["stats-render"])) {
    stats_render();
    exit;
}

function stats_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $id = intval($_GET["stats-js"]);
    // Pentest 2026-11-04 : le nom d'instance est une donnee affichee, encodee.
    $title = $tpl->_ENGINE_parse_body("{statistics} - " . req_html(req_html($_GET["name"] ?? "VI_$id")));
    $tpl->js_dialog1($title, "$page?stats-popup=$id", 1100);
}

function stats_popup()
{
    $page = CurrentPageName();
    $id = intval($_GET["stats-popup"]);
    echo "<div id='kast-stats-$id'></div><script>LoadAjax('kast-stats-$id','$page?stats-render=$id&hours=6');</script>";
}

// Same ranges as the Load-Balancer graphs; the bucket width comes back from
// articarest with the data.
function kast_ranges(): array
{
    return array(1 => "1 {hour}", 6 => "6 {hours}", 24 => "1 {day}", 168 => "7 {days}", 720 => "30 {days}");
}

function kast_label_format(int $hours): string
{
    if ($hours >= 720) {
        return "d/m";
    }
    if ($hours >= 168) {
        return "d/m H\h";
    }
    return "H:i";
}

function kast_state_color(string $state): string
{
    if ($state == "MASTER") {
        return "#047857";
    }
    if ($state == "BACKUP") {
        return "#0284c7";
    }
    if ($state == "") {
        return "#64748b";
    }
    return "#be123c";
}

function kast_tile($label, $value, $color): string
{
    return "<div style='background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px'>" .
        "<div style='font-size:11px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#6c7b6d'>$label</div>" .
        "<div style='font-size:24px;font-weight:700;color:$color;line-height:1.2'>$value</div></div>";
}

function kast_chart_title($label, $hint = ""): string
{
    if ($hint != "") {
        $hint = "<span style='font-weight:400;text-transform:none;letter-spacing:0;color:#8a968b;margin-left:8px'>$hint</span>";
    }
    return "<div style='margin:18px 4px 2px;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#3d4947'>$label$hint</div>";
}

function kast_table($title, $heads, $rows, $empty = "&mdash;"): string
{
    $h = "<div style='background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden'>";
    $h .= "<div style='padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#3d4947'>$title</div>";
    $h .= "<table class='table table-striped' style='margin:0;font-size:13px'><thead><tr>";
    foreach ($heads as $i => $t) {
        $h .= "<th" . ($i > 0 ? " style='text-align:right'" : "") . ">$t</th>";
    }
    $h .= "</tr></thead><tbody>";
    if (count($rows) == 0) {
        $h .= "<tr><td colspan='" . count($heads) . "'>$empty</td></tr>";
    }
    foreach ($rows as $r) {
        $h .= "<tr>";
        foreach ($r as $i => $c) {
            $h .= "<td" . ($i > 0 ? " style='text-align:right'" : "") . ">$c</td>";
        }
        $h .= "</tr>";
    }
    $h .= "</tbody></table></div>";
    return $h;
}

// The live entry of the instance in keepalived's dump: "data" (state,
// priority...) and "stats" (the counters the old Stats dialog showed).
function kast_live(int $id): array
{
    $answer = json_decode((string)$GLOBALS['CLASS_SOCKETS']->REST_API("/keepalived/stats"), true);
    if (!is_array($answer) || empty($answer["Status"])) {
        return array();
    }
    $dump = json_decode((string)($answer["Info"] ?? ""), true);
    if (!is_array($dump)) {
        return array();
    }
    foreach ($dump as $val) {
        if (($val['data']['iname'] ?? "") == "VI_$id") {
            return $val;
        }
    }
    return array();
}

// Reloads the open dialog every 60 s (the collector samples once a minute), on
// the range being viewed. One timer per instance: each render re-arms it, and it
// stops once the dialog is closed. Concatenated after the parsing, which would
// read the {...} blocks as tokens.
function kast_autorefresh(string $page, int $id, int $hours): string
{
    return "<script>
(function(){
	if(!window.__kastTimer){window.__kastTimer={};}
	if(window.__kastTimer[$id]){clearTimeout(window.__kastTimer[$id]);}
	window.__kastTimer[$id]=setTimeout(function(){
		delete window.__kastTimer[$id];
		if(!document.getElementById('kast-stats-$id')){return;}
		LoadAjaxSilent('kast-stats-$id','$page?stats-render=$id&hours=$hours');
	},60000);
})();
</script>";
}

function stats_render()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $id = intval($_GET["stats-render"]);
    $hours = intval($_GET["hours"] ?? 6);
    if (!isset(kast_ranges()[$hours])) {
        $hours = 6;
    }

    $btns = array();
    foreach (kast_ranges() as $hh => $lbl) {
        $cls = ($hh == $hours) ? "btn-primary" : "btn-white";
        $btns[] = "<label class=\"btn $cls\" OnClick=\"LoadAjax('kast-stats-$id','$page?stats-render=$id&hours=$hh');\">$lbl</label>";
    }

    $json = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/keepalived/stats/history?primaryid=$id&hours=$hours"), true);
    $h = array();
    $h[] = "<div style='display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin:8px 4px'>";
    $h[] = "<div><strong style='font-size:16px'>VI_$id</strong></div>";
    $h[] = "<div class=\"btn-group\" data-toggle=\"buttons\">" . implode("", $btns) . "</div>";
    $h[] = "</div>";

    if (!is_array($json) || empty($json["Status"])) {
        $error = is_array($json) ? (string)($json["Error"] ?? "") : "articarest: no answer";
        $h[] = "<div style='margin:8px 4px'>" . $tpl->div_error(htmlspecialchars($error)) . "</div>";
        echo $tpl->_ENGINE_parse_body(implode("\n", $h)) . kast_autorefresh($page, $id, $hours);
        return;
    }
    $H = $json["history"] ?? array();
    $buckets = is_array($H["buckets"] ?? null) ? $H["buckets"] : array();
    $totals = is_array($H["totals"] ?? null) ? $H["totals"] : array();
    $state = (string)($H["state"] ?? "");
    $priority = intval($H["priority"] ?? 0);
    // No history yet (the first minutes, or the collector not running): the
    // state and priority are read from keepalived right now.
    $live = kast_live($id);
    if ($state == "" && is_array($live["data"] ?? null)) {
        $names = array(0 => "INIT", 1 => "BACKUP", 2 => "MASTER", 3 => "FAULT");
        $n = intval($live["data"]["state"] ?? -1);
        $state = $names[$n] ?? "";
        $priority = intval($live["data"]["effective_priority"] ?? 0);
    }
    $width = max(60, intval($H["width"] ?? 60));

    $failovers = intval($totals["become_master"] ?? 0);
    $errors = intval($H["errors"] ?? 0);
    $h[] = "<div style='display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin:8px 4px'>";
    $h[] = kast_tile("{status}", ($state == "" ? "&mdash;" : htmlspecialchars($state)), kast_state_color($state));
    $h[] = kast_tile("{priority}", $priority, "#0f172a");
    $h[] = kast_tile("{kast_become_master}", $tpl->FormatNumber($failovers), $failovers > 0 ? "#b45309" : "#6c7b6d");
    $h[] = kast_tile("{kast_vrrp_errors}", $tpl->FormatNumber($errors), $errors > 0 ? "#ba1a1a" : "#6c7b6d");
    $h[] = "</div>";

    if (count($buckets) == 0) {
        $h[] = "<div style='margin:8px 4px'>" . $tpl->div_info("{no_data} &mdash; {kast_stats_collecting}") . "</div>";
    } else {
        $fmt = kast_label_format($hours);
        $labels = array();
        $full = array();
        $sent = array();
        $rcvd = array();
        $err = array();
        $master = array();
        $states = array();
        foreach ($buckets as $b) {
            $t = intval($b["t"]);
            $labels[] = date($fmt, $t);
            $end = $t + $width;
            $full[] = date("Y-m-d H:i", $t) . " → " . date((date("Y-m-d", $t) == date("Y-m-d", $end)) ? "H:i" : "Y-m-d H:i", $end);
            $sent[] = intval($b["counters"]["advert_sent"] ?? 0);
            $rcvd[] = intval($b["counters"]["advert_rcvd"] ?? 0);
            $err[] = intval($b["errors"] ?? 0);
            $master[] = intval($b["master_pct"] ?? 0);
            $states[] = (string)($b["state"] ?? "");
        }

        $h[] = kast_chart_title("{kast_master_time}", "% &middot; MASTER");
        $h[] = "<div style='margin:8px 4px;position:relative;height:150px'><canvas id='kast-master-$id'></canvas></div>";
        $h[] = kast_chart_title("{kast_adverts}", "VRRP");
        $h[] = "<div style='margin:8px 4px;position:relative;height:200px'><canvas id='kast-adv-$id'></canvas></div>";
        $h[] = kast_chart_title("{kast_vrrp_errors}");
        $h[] = "<div style='margin:8px 4px;position:relative;height:150px'><canvas id='kast-err-$id'></canvas></div>";

        $lab = json_encode($labels);
        $jfull = json_encode($full);
        $jsent = json_encode($sent);
        $jrcvd = json_encode($rcvd);
        $jerr = json_encode($err);
        $jmaster = json_encode($master);
        $jstates = json_encode($states);
        $sent_t = $tpl->javascript_parse_text("{kast_adverts_sent}");
        $rcvd_t = $tpl->javascript_parse_text("{kast_adverts_rcvd}");
        $err_t = $tpl->javascript_parse_text("{kast_vrrp_errors}");

        // Chart.js on demand, from the same file as the Load-Balancer graphs.
        $h[] = "<script>
(function(){
	function boot(cb){
		if(typeof Chart!=='undefined'){cb();return;}
		if(window.__hst_loading){setTimeout(function(){boot(cb);},200);return;}
		window.__hst_loading=true;
		var sc=document.createElement('script');sc.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';
		sc.onload=function(){cb();};document.head.appendChild(sc);
	}
	boot(function(){
		if(!window.__kast){window.__kast={};}
		['kast-master-$id','kast-adv-$id','kast-err-$id'].forEach(function(k){
			if(window.__kast[k]){try{window.__kast[k].destroy();}catch(e){}}
		});
		var lab=$lab,full=$jfull,states=$jstates;
		function tt(items){return full[items[0].dataIndex]||'';}
		var pr=lab.length<2?5:(lab.length<=40?3:0);
		var phr=Math.max(pr,4);
		var opt={responsive:true,maintainAspectRatio:false,animation:false,
			interaction:{mode:'index',intersect:false},
			plugins:{legend:{display:true,position:'bottom'}},
			scales:{y:{beginAtZero:true},x:{ticks:{maxTicksLimit:12,autoSkip:true}}}};
		var optM=JSON.parse(JSON.stringify(opt));
		optM.plugins.legend.display=false;
		optM.scales.y.max=100;
		optM.plugins.tooltip={mode:'index',intersect:false,callbacks:{title:tt,
			afterBody:function(items){return states[items[0].dataIndex]||'';}}};
		opt.plugins.tooltip={mode:'index',intersect:false,callbacks:{title:tt}};
		var c0=document.getElementById('kast-master-$id');
		if(c0){window.__kast['kast-master-$id']=new Chart(c0,{type:'line',data:{labels:lab,datasets:[
			{label:'MASTER %',data:$jmaster,stepped:true,borderColor:'#047857',backgroundColor:'rgba(4,120,87,0.15)',fill:true,borderWidth:2,pointRadius:0,pointHoverRadius:phr}
		]},options:optM});}
		var c1=document.getElementById('kast-adv-$id');
		if(c1){window.__kast['kast-adv-$id']=new Chart(c1,{type:'line',data:{labels:lab,datasets:[
			{label:'$sent_t',data:$jsent,borderColor:'#2ecc71',backgroundColor:'rgba(46,204,113,0.18)',fill:true,borderWidth:2,pointRadius:pr,pointHoverRadius:phr,tension:0.3},
			{label:'$rcvd_t',data:$jrcvd,borderColor:'#3498db',backgroundColor:'rgba(52,152,219,0.12)',fill:true,borderWidth:2,pointRadius:pr,pointHoverRadius:phr,tension:0.3}
		]},options:opt});}
		var c2=document.getElementById('kast-err-$id');
		if(c2){window.__kast['kast-err-$id']=new Chart(c2,{type:'bar',data:{labels:lab,datasets:[
			{label:'$err_t',data:$jerr,backgroundColor:'rgba(186,26,26,0.6)'}
		]},options:opt});}
	});
})();
</script>";
    }

    // State changes, most recent first.
    $trows = array();
    foreach ((array)($H["transitions"] ?? array()) as $tr) {
        $from = (string)($tr["from"] ?? "");
        $to = (string)($tr["to"] ?? "");
        $trows[] = array(htmlspecialchars($tpl->time_to_date(intval($tr["t"] ?? 0), true)),
            "<span style='color:" . kast_state_color($from) . "'>" . htmlspecialchars($from) . "</span> &rarr; " .
            "<span style='color:" . kast_state_color($to) . ";font-weight:700'>" . htmlspecialchars($to) . "</span>");
    }
    // Error counters over the range, and the current raw counters.
    $erows = array();
    foreach (array("packet_len_err", "advert_interval_err", "ip_ttl_err", "invalid_type_rcvd", "addr_list_err",
                 "invalid_authtype", "authtype_mismatch", "auth_failure") as $k) {
        $n = intval($totals[$k] ?? 0);
        $erows[] = array("<span style='font-family:monospace'>$k</span>",
            ($n > 0 ? "<strong style='color:#ba1a1a'>" . number_format($n) . "</strong>" : "0"));
    }
    $crows = array();
    foreach ((is_array($live["stats"] ?? null) ? $live["stats"] : array()) as $k => $v) {
        $crows[] = array("<span style='font-family:monospace'>" . htmlspecialchars((string)$k) . "</span>", htmlspecialchars((string)$v));
    }
    $h[] = "<div style='display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin:18px 4px 8px'>";
    // No change in the range: say so, with the state held over it, rather than a bare dash.
    $tempty = "&mdash;";
    if (count($buckets) > 0 && $state != "") {
        $first = intval($buckets[0]["t"] ?? 0);
        $tempty = "<span style='color:#6b7a77'>{no_change}</span> &mdash; <span style='color:" . kast_state_color($state) . ";font-weight:700'>" .
            htmlspecialchars($state) . "</span> <span style='color:#6b7a77'>(" . htmlspecialchars($tpl->time_to_date($first, true)) . " &rarr; " . htmlspecialchars($tpl->time_to_date(time(), true)) . ")</span>";
    }
    $h[] = kast_table("{kast_transitions}", array("{date}", "{status}"), $trows, $tempty);
    $h[] = kast_table("{kast_vrrp_errors}", array("{type}", "{events}"), $erows);
    $h[] = kast_table("{kast_current_counters}", array("{type}", "{value}"), $crows);
    $h[] = "</div>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h)) . kast_autorefresh($page, $id, $hours);
}
