<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");

$GLOBALS["CLASS_SOCKETS"] = new sockets();

if (isset($_GET["popup"]))         { popup();         exit; }
if (isset($_GET["services-tab"]))  { services_tab();  exit; }
if (isset($_GET["stats-tab"]))     { stats_tab();     exit; }
if (isset($_GET["action"]))        { service_action();exit; }

js();

// ─────────────────────────────────────────────────────────────────────────
//  Entry point — dialog launcher (kept identical for backwards compat)
// ─────────────────────────────────────────────────────────────────────────

function js() {
	$page = CurrentPageName();
	$tpl  = new template_admin();
	$tpl->js_dialog("{services_status}", "$page?popup=yes");
}

// ─────────────────────────────────────────────────────────────────────────
//  Dialog body — emits the Bootstrap nav-tabs shell. Inner tab bodies
//  are loaded via LoadAjax so each gets its own HTML response (this is
//  arrives in the same response — see CLAUDE.md "Chart.js Race" gotcha).
// ─────────────────────────────────────────────────────────────────────────

function popup() {
	$page  = CurrentPageName();
	$tpl   = new template_admin();
	$tabSv = $tpl->_ENGINE_parse_body("{services}");
	$tabSt = $tpl->_ENGINE_parse_body("{statistics}");

	$html = "
<ul class='nav nav-tabs' id='monit-tabs' role='tablist'>
  <li role='presentation' class='active'>
    <a href='#monit-tab-services' data-toggle='tab' role='tab' aria-controls='monit-tab-services'>$tabSv</a>
  </li>
  <li role='presentation'>
    <a href='#monit-tab-stats' data-toggle='tab' role='tab' aria-controls='monit-tab-stats'>$tabSt</a>
  </li>
</ul>
<div class='tab-content' style='padding-top:12px'>
  <div role='tabpanel' class='tab-pane active' id='monit-tab-services'>
    <div id='monit-services-tab'></div>
  </div>
  <div role='tabpanel' class='tab-pane' id='monit-tab-stats'>
    <div id='monit-stats-tab'></div>
  </div>
</div>
<script>
(function(){
  // Initial load — services tab is the default-active one.
  LoadAjaxSilent('monit-services-tab','$page?services-tab=yes');

  // Lazy-load the stats tab the first time it is shown.
  var statsLoaded = false;
  $('#monit-tabs a[href=\"#monit-tab-stats\"]').on('shown.bs.tab', function(){
    if (!statsLoaded) {
      LoadAjaxSilent('monit-stats-tab','$page?stats-tab=yes');
      statsLoaded = true;
    }
  });
})();
</script>";
	echo $html;
}

// ─────────────────────────────────────────────────────────────────────────
//  Services tab — footable with memory %+MB, peity pie (memory) +
//  peity bar (CPU), and 4 action buttons (start/stop/restart/monitor).
// ─────────────────────────────────────────────────────────────────────────

function services_tab() {
	$tpl  = new template_admin();
	$page = CurrentPageName();

	$lblService     = $tpl->_ENGINE_parse_body("{services}");
	$lblStatus      = $tpl->_ENGINE_parse_body("{status}");
	$lblUptime      = $tpl->_ENGINE_parse_body("{uptime}");
	$lblChildren    = $tpl->_ENGINE_parse_body("{children}");
	$lblMem         = $tpl->_ENGINE_parse_body("{memory}");
	$lblCPU         = $tpl->_ENGINE_parse_body("{cpu}");
	$lblAction      = $tpl->_ENGINE_parse_body("{action}");
	$title          = $tpl->javascript_parse_text("{APP_MONIT}");
	$txtMonitored   = $tpl->javascript_parse_text("{monitored}");
	$txtInitial     = $tpl->javascript_parse_text("{initializing}");
	$txtStopped     = $tpl->_ENGINE_parse_body("{stopped}");
	$noData         = $tpl->_ENGINE_parse_body("{NO_DATA_COME_BACK_LATER}");

	$Info = monit_decode_jsonstatus();
	if (!is_object($Info) || !property_exists($Info, "Server") || !is_object($Info->Server)) {
		echo $tpl->div_error($noData);
		return;
	}

	$uptimeText = distanceOfTimeInWords(time() - $Info->Server->Uptime, time());
	$ver        = htmlspecialchars($Info->Server->Version);

	$html   = [];
	$html[] = $tpl->_ENGINE_parse_body("<H2>$title v$ver <small>({running_since} $uptimeText)</small></H2>");
	$html[] = "<table id='monit-services-table' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[] = "<thead><tr>";
	$html[] = "<th data-sortable='true' data-type='text'>$lblService</th>";
	$html[] = "<th data-sortable='true' data-type='text'>$lblStatus</th>";
	$html[] = "<th data-sortable='true' data-type='text'>$lblUptime</th>";
	$html[] = "<th data-sortable='true' data-type='numeric'>$lblChildren</th>";
	$html[] = "<th data-sortable='true' data-type='text'>$lblMem</th>";
	$html[] = "<th data-sortable='false'>&nbsp;</th>";
	$html[] = "<th data-sortable='true' data-type='numeric'>$lblCPU</th>";
	$html[] = "<th data-sortable='false'>&nbsp;</th>";
	$html[] = "<th data-sortable='false' class='text-center'>$lblAction</th>";
	$html[] = "</tr></thead><tbody>";

	$users  = new usersMenus();
	$canAct = $users->AsDebianSystem;
	$peity  = []; // collected peity init JS, flushed at end

	$services = $Info->Services ?? [];
	if (is_object($services)) { $services = (array)$services; }

	foreach ($services as $svc) {
		if (!is_object($svc) || !property_exists($svc, "Name")) { continue; }
		$name = trim($svc->Name);
		if ($name === "" || preg_match("#[a-z]+#", $name)) { continue; }

		$display    = $tpl->_ENGINE_parse_body("{{$name}}");
		$uptimeRow  = monit_format_uptime((int)($svc->Uptime ?? 0));
		$children   = (int)($svc->Children ?? 0);
		$cpuPct     = round((float)($svc->CPU->Percent ?? 0), 2);
		$memPct     = round((float)($svc->Memory->PercentTotal ?? $svc->Memory->Percent ?? 0), 1);
		$memKBTotal = (int)($svc->Memory->KilobyteTotal ?? $svc->Memory->Kilobyte ?? 0);
		$memHuman   = FormatBytes($memKBTotal);
		$memCol     = "{$memPct}% <small class='text-muted'>($memHuman)</small>";

		$monitor    = (int)($svc->Monitor ?? 0);
		switch ($monitor) {
			case 1:  $labelClass = "primary"; $labelText = $txtMonitored; break;
			case 2:  $labelClass = "warning"; $labelText = $txtInitial;   break;
			default: $labelClass = "danger";  $labelText = $txtStopped;
		}

		$pieID = "monit-pie-".md5($name);
		$barID = "monit-bar-".md5($name);
		$memInt = max(0, min(100, (int)round($memPct)));
		$cpuInt = max(0, min(100, (int)round($cpuPct)));
		$pieSpan = "<span id='$pieID' class='monit-pie' style='font-size:0;line-height:0'>$memInt,".(100 - $memInt)."</span>";
		$barSpan = "<span id='$barID' class='monit-bar' style='font-size:0;line-height:0'>$cpuInt,".(100 - $cpuInt)."</span>";
		$peity[] = "$('#$pieID').peity('pie',{fill:['#1ab394','#eeeeee'],radius:9});";
		$peity[] = "$('#$barID').peity('bar',{fill:['#1c84c6'],height:18,width:34});";

		$icons = [];
		if ($canAct) {
			$svcEsc = urlencode($name);
			$icons[] = "<a href='javascript:blur()' onclick=\"Loadjs('$page?action=start&svc=$svcEsc')\" title='start'><i class='fa fa-play text-success' style='margin:0 4px'></i></a>";
			$icons[] = "<a href='javascript:blur()' onclick=\"Loadjs('$page?action=stop&svc=$svcEsc')\" title='stop'><i class='fa fa-stop text-danger' style='margin:0 4px'></i></a>";
			$icons[] = "<a href='javascript:blur()' onclick=\"Loadjs('$page?action=restart&svc=$svcEsc')\" title='restart'><i class='fal fa-sync-alt' style='margin:0 4px'></i></a>";
			$icons[] = "<a href='javascript:blur()' onclick=\"Loadjs('$page?action=monitor&svc=$svcEsc')\" title='re-enable monitor'><i class='fa fa-eye text-info' style='margin:0 4px'></i></a>";
		} else {
			$icons[] = $tpl->icon_nothing();
		}

		$html[] = "<tr>";
		$html[] = "<td>$display</td>";
		$html[] = "<td><span class='label label-$labelClass' style='color:#fff'>$labelText</span></td>";
		$html[] = "<td>$uptimeRow</td>";
		$html[] = "<td class='text-right'>$children</td>";
		$html[] = "<td>$memCol</td>";
		$html[] = "<td class='text-center'>$pieSpan</td>";
		$html[] = "<td class='text-right'>{$cpuPct}%</td>";
		$html[] = "<td class='text-center'>$barSpan</td>";
		$html[] = "<td class='text-center'>".implode("", $icons)."</td>";
		$html[] = "</tr>";
	}

	$html[] = "</tbody><tfoot><tr><td colspan='9'><ul class='pagination pull-right'></ul></td></tr></tfoot></table>";
	$html[] = "<div style='text-align:right'>".$tpl->icon_refresh("LoadAjaxSilent('monit-services-tab','$page?services-tab=yes');")."</div>";

	$peityJS = implode("\n  ", $peity);
	$iconJS  = @implode("\n", $tpl->ICON_SCRIPTS);
	$html[]  = "<script src='angular/js/plugins/peity/jquery.peity.min.js'></script>";
	$html[]  = "<script>
NoSpinner();
$iconJS
$(document).ready(function(){
  $('#monit-services-table').footable({\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true}});
  $peityJS
});
</script>";

	echo implode("\n", $html);
}

// ─────────────────────────────────────────────────────────────────────────
//  Statistics tab — TOP-10 CPU + TOP-10 Memory horizontal bar charts.
//  Chart.js <script src> + inline new Chart() must be in this same
//  response (CLAUDE.md "Chart.js Race" gotcha).
// ─────────────────────────────────────────────────────────────────────────

function stats_tab() {
	$tpl  = new template_admin();
	$page = CurrentPageName();

	$lblTopCPU = $tpl->_ENGINE_parse_body("{top_processes_by_cpu}");
	$lblTopMem = $tpl->_ENGINE_parse_body("{top_processes_by_memory}");
	$noData    = $tpl->_ENGINE_parse_body("{NO_DATA_COME_BACK_LATER}");

	$cpuRows = monit_rest_get("/system/monit/topcpu");
	$memRows = monit_rest_get("/system/monit/topmem");
	$cpuList = (is_object($cpuRows) && property_exists($cpuRows, "processes")) ? $cpuRows->processes : [];
	$memList = (is_object($memRows) && property_exists($memRows, "processes")) ? $memRows->processes : [];

	if (empty($cpuList) && empty($memList)) {
		echo $tpl->div_error($noData);
		return;
	}

	// Use sprintf for floats — avoids PHP's serialize_precision=-1 emitting
	// 50-digit floats via json_encode (CLAUDE.md gotcha).
	$cpuLabels = "[" . implode(",", array_map(function($r){ return "\"" . htmlspecialchars($r->name . " (" . $r->pid . ")", ENT_QUOTES) . "\""; }, $cpuList)) . "]";
	$cpuValues = "[" . implode(",", array_map(function($r){ return sprintf("%.2f", $r->cpu_percent); }, $cpuList)) . "]";
	$memLabels = "[" . implode(",", array_map(function($r){ return "\"" . htmlspecialchars($r->name . " (" . $r->pid . ")", ENT_QUOTES) . "\""; }, $memList)) . "]";
	$memValues = "[" . implode(",", array_map(function($r){ return sprintf("%.1f", $r->mem_mb); }, $memList)) . "]";

	$html = [];
	$html[] = "<div class='row'>";
	$html[] = "  <div class='col-md-6'><h4>$lblTopCPU</h4><div style='position:relative;height:320px'><canvas id='monit-cpu-chart'></canvas></div></div>";
	$html[] = "  <div class='col-md-6'><h4>$lblTopMem</h4><div style='position:relative;height:320px'><canvas id='monit-mem-chart'></canvas></div></div>";
	$html[] = "</div>";
	$html[] = "<div style='text-align:right;margin-top:8px'>".$tpl->icon_refresh("LoadAjaxSilent('monit-stats-tab','$page?stats-tab=yes');")."</div>";

	$html[] = "";
	$html[] = "<script>
(function(){
  var cpuCanvas = document.getElementById('monit-cpu-chart');
  var memCanvas = document.getElementById('monit-mem-chart');
  if (cpuCanvas && Chart.getChart) { var c = Chart.getChart(cpuCanvas); if (c) c.destroy(); }
  if (memCanvas && Chart.getChart) { var c = Chart.getChart(memCanvas); if (c) c.destroy(); }

  new Chart(cpuCanvas, {
    type: 'bar',
    data: { labels: $cpuLabels, datasets: [{ label: 'CPU %', data: $cpuValues, backgroundColor: 'rgba(28,132,198,0.7)', borderColor: 'rgba(28,132,198,1)', borderWidth: 1 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, title: { display: true, text: 'CPU %' } } } }
  });
  new Chart(memCanvas, {
    type: 'bar',
    data: { labels: $memLabels, datasets: [{ label: 'MB', data: $memValues, backgroundColor: 'rgba(26,179,148,0.7)', borderColor: 'rgba(26,179,148,1)', borderWidth: 1 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, title: { display: true, text: 'RSS (MB)' } } } }
  });
})();
// auto-refresh every 60s
setTimeout(function(){
  LoadAjaxSilent('monit-stats-tab','$page?stats-tab=yes');
}, 60000);
</script>";

	echo implode("\n", $html);
}

// ─────────────────────────────────────────────────────────────────────────
//  Action handler — POSTs to /system/monit/action/{name} then refreshes
//  the services tab. Wired via Loadjs → returns JS content-type.
// ─────────────────────────────────────────────────────────────────────────

function service_action() {
	$page   = CurrentPageName();
	$verb   = $_GET["action"] ?? "";
	$name   = $_GET["svc"] ?? "";
	$users  = new usersMenus();

	header("content-type: application/x-javascript");

	if (!$users->AsDebianSystem) {
		echo "alert('permission denied');";
		return;
	}
	if (!preg_match('/^[A-Z0-9_]{3,64}$/', $name)) {
		echo "alert('invalid service name');";
		return;
	}
	if (!in_array($verb, ['start','stop','restart','monitor'], true)) {
		echo "alert('invalid action');";
		return;
	}

	$endpoint = "/system/monit/action/" . urlencode($name);
	$resp = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($endpoint, ["action" => $verb]);
	$json = json_decode($resp);
	if (!is_object($json) || !($json->success ?? false)) {
		$err = is_object($json) ? ($json->error ?? "unknown error") : "no response";
		echo "alert(" . json_encode("$verb $name failed: $err") . ");";
		return;
	}
	echo "LoadAjaxSilent('monit-services-tab','$page?services-tab=yes');";
}

// ─────────────────────────────────────────────────────────────────────────
//  Helpers (file-local, not exported)
// ─────────────────────────────────────────────────────────────────────────

/**
 * Decode the legacy {Status, Info: "<json string>", Data} envelope from
 * /system/monit/jsonstatus into the inner object.
 */
function monit_decode_jsonstatus() {
	$raw = $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/monit/jsonstatus");
	$wrap = json_decode($raw);
	if (!is_object($wrap) || !property_exists($wrap, "Info") || strlen($wrap->Info) < 5) {
		return null;
	}
	return json_decode($wrap->Info);
}

/**
 * GET a new-style envelope endpoint and return $resp->data, or null on failure.
 */
function monit_rest_get($endpoint) {
	$raw = $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
	$j = json_decode($raw);
	if (!is_object($j) || !($j->success ?? false)) {
		return null;
	}
	return $j->data ?? null;
}

function monit_format_uptime($seconds) {
	$days  = (int)floor($seconds / 86400);
	$hours = (int)floor(($seconds % 86400) / 3600);
	$mins  = (int)floor(($seconds % 3600) / 60);
	$out   = "";
	if ($days  > 0) { $out .= "{$days}d ";  }
	if ($hours > 0) { $out .= "{$hours}h "; }
	if ($mins  > 0) { $out .= "{$mins}m";   }
	return $out !== "" ? $out : "<1m";
}
